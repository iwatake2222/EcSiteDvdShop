<?php

include 'ChromePhp.php';

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/productMgr.php';
require_once __DIR__ . '/../models/blogMgr.php';
require_once __DIR__ . '/../models/mailer.php';

$oProductMgr = new ProductMgr();
$oBlogMgr = new BlogMgr();

$oApp = new \Slim\Slim (array(
	'templates.path' => __DIR__ . '/../views'
));

date_default_timezone_set('Canada/Saskatchewan');
$oApp->add(new \Slim\Middleware\SessionCookie(array(
	'expires' => '60 minutes',
	'path' => '/',
	'domain' => null,
	'secure' => false,
	'httponly' => false,
	'name' => 'slim_session',
	'secret' => 'CHANGE_ME',
	'cipher' => MCRYPT_RIJNDAEL_256,
	'cipher_mode' => MCRYPT_MODE_CBC
)));


/***
 * Home page
***/
$oApp->get('/', function() use($oApp, $oProductMgr) {
	$oApp->render('home.phtml', array(
		'title' => '',
		'userType' => getUserType(),
		'genreAll' => $oProductMgr->getGenre(),
		'genreSelected' => 'Action',
		'productsInGenre' => $oProductMgr->getProductsByGenre('Action'),
		'featuredProducts' => $oProductMgr->getFeaturedProducts(),
	));
});

$oApp->get('/home/:genre', function($sGenre) use($oApp, $oProductMgr) {
	$oApp->render('home.phtml', array(
		'title' => $sGenre,
		'userType' => getUserType(),
		'genreAll' => $oProductMgr->getGenre(),
		'genreSelected' => $sGenre,
		'productsInGenre' => $oProductMgr->getProductsByGenre($sGenre),
		'featuredProducts' => $oProductMgr->getFeaturedProducts(),
	));
});

// called when user search for items
$oApp->post('/search', function() use($oApp, $oProductMgr) {
	$sKeywords = $oApp->request()->post('keywords');
	$oApp->render('searchResult.phtml', array(
		'title' => $sKeywords,
		'userType' => getUserType(),
		'products' => $oProductMgr->getProductByKeywords($sKeywords),
		'keywords' => $sKeywords,
	));
});

$oApp->get('/search', function() use($oApp, $oProductMgr) {
	$sKeywords = $oApp->request->params('keywords');
	//ChromePhp::info($sKeywords);
	//die();
	$oApp->render('searchResult.phtml', array(
		'title' => $sKeywords,
		'userType' => getUserType(),
		'products' => $oProductMgr->getProductByKeywords($sKeywords),
		'keywords' => $sKeywords,
	));
});

/***
 * Product page
***/
$oApp->get('/product/:productId', function($nProductId) use($oApp, $oProductMgr) {
	if(isset($_SESSION['cart'][$nProductId])) {
		$numInCart = $_SESSION['cart'][$nProductId];
	} else {
		$numInCart = 0;
	}
	$oApp->render('product.phtml', array(
		'title' => $oProductMgr->getProductNameById($nProductId),
		'userType' => getUserType(),
		'product' => $oProductMgr->getProductById($nProductId),
		'reviews' => $oProductMgr->getReviews($nProductId),
		'relatedProducts' => $oProductMgr->getRelatedProducts($nProductId),
		'numInCart' => $numInCart,
	));
});

// called when user posts review
$oApp->post('/product/:productId', function($nProductId) use($oApp, $oProductMgr) {
	$req = $oApp->request();
	$nStar = $req->post('review_star');
	$sComment = $req->post('review_comment');
	$sWriter = $req->post('review_writer');
	$oProductMgr->setReview($nProductId, $nStar, $sComment, $sWriter);
	$oApp->redirect('/product/' . $nProductId );
});

// called when user click 'Add to cart'
$oApp->post('/addCart', function() use($oApp, $oProductMgr) {
	$req = $oApp->request();
	$nProductId = $req->post('productId');

	if(isset($_SESSION['cart'][$nProductId])) {
		$_SESSION['cart'][$nProductId] += 1;
	} else {
		$_SESSION['cart'][$nProductId] = 1;
	}

	$oApp->redirect('/product/' . $nProductId);
});


/***
 * Cart/Order sheet
***/
$oApp->get('/cart', function() use($oApp, $oProductMgr) {
	$cart = array();
	$total = 0;
	if( isset($_SESSION['cart']) ) {
		foreach($_SESSION['cart'] as $nId => $nNum) {
			$oProduct = $oProductMgr->getProductById($nId);
			$oItemInfo = array('name' => $oProduct->name,
				'price' => $oProduct->price,
				'num' => $nNum,
				'subTotal' => $oProduct->price * $nNum
			);
			$total += $oProduct->price * $nNum;
			array_push($cart, $oItemInfo)	;
		}
	} else {
		$cart = array();
		$total=0;
	}

	$oApp->render('cart.phtml', array(
		'title' => 'Cart',
		'userType' => getUserType(),
		'cart' => $cart,
		'total' => $total,
	));
});

$oApp->post('/order', function() use($oApp, $oProductMgr) {
	$req = $oApp->request();
	$sName = $req->post('order_name');
	$sAddress = $req->post('order_address');
	$sCreditType = $req->post('order_credit_type');
	$nCreditNumber = $req->post('order_credit_number');

	// todo save order information into DB
	//$oProductMgr->makeOrder($sName, $sAddress, $sCreditType, $nCreditNumber, $cart)
	
	// clear cart
	if(isset($_SESSION['cart'])){
		foreach($_SESSION['cart'] as $nId => $nNum){
			unset($_SESSION['cart'][$nId]);
		}
	}
	$oApp->render('order.phtml', array(
		'title' => 'Order',
		'userType' => getUserType(),
		'orderId' => rand(),
	));
});

/***
 * contact page
***/
$oApp->get('/contact(/:msg)', function($sMsg = '') use($oApp) {
	$oApp->render('contact.phtml', array(
		'title' => 'Contact',
		'userType' => getUserType(),
		'msg' => $sMsg));
});

$oApp->post('/mail', function() use($oApp) {
	$req = $oApp->request();
	$sEmail = $req->post('contact_email');
	$sSubject = $req->post('contact_subject');
	$sMessage = $req->post('contact_message');

	$oMailer = new Mailer();
	if($oMailer->Send($sEmail, $sSubject, $sMessage)) {
		$sMsg = 'Your inquiry has been sent successfully';
	} else {
		$sMsg = 'Sorry, your inquiry has not been sent because of technical problem';
	}

	$oApp->redirect('/contact/' . $sMsg);
});


/***
 * About page
***/
$oApp->get('/about', function() use($oApp) {
	$oApp->render('about.phtml', array(
		'title' => 'About',
		'userType' => getUserType(),
		));
});

/***
 * Blog page
***/
$oApp->get('/blog', function() use($oApp, $oBlogMgr) {
	$oApp->render('blog.phtml', array(
		'title' => 'Blog (Change Log)',
		'userType' => getUserType(),
		'blogs' => $oBlogMgr->getAllBlogs(),
	));
});

$oApp->post('/blog', function() use($oApp, $oBlogMgr) {
	$req = $oApp->request();
	$sComment = $req->post('blog_comment');
	$sWriter = $req->post('blog_writer');
	$sPasswd = $req->post('blog_passwd');

	if($oBlogMgr->setBlog($sComment, $sWriter, $sPasswd)) {
		$oApp->redirect('/blog');
	} else {
		// todo: error page
		echo 'Invalid Password<br>';
	}
});

/***
 * Administrator
***/
$oApp->get('/administrator', function() use($oApp, $oProductMgr) {
	if(getUserType() == 'admin'){
		$oApp->render('administrator.phtml', array(
			'title' => 'Administrator',
			'userType' => getUserType(),
		));
	}
});

/* CRUD APIs */
$oApp->post('/getALlProducts', function() use($oApp, $oProductMgr) {
	if(getUserType() != 'admin'){
		$oApp->halt(500, 'You are not login');
		return;
	}
	//ChromePhp::info($oProductMgr->getAllProducts());
	echo json_encode($oProductMgr->getAllProducts());
});

$oApp->post('/addProduct', function() use($oApp, $oProductMgr) {
	if(getUserType() != 'admin'){
		$oApp->halt(500, 'You are not login');
		return;
	}
	$oProduct = json_decode($oApp->request->getBody());
	//ChromePhp::info($oProduct);
	$nRow = $oProductMgr->addProduct($oProduct);
	echo json_encode(array("rows"=>$nRow));
});

$oApp->delete('/deleteProduct/:id', function($nId) use($oApp, $oProductMgr) {
	if(getUserType() != 'admin'){
		$oApp->halt(500, 'You are not login');
		return;
	}
	$nRow = $oProductMgr->deleteProduct($nId);
	echo json_encode(array("rows"=>$nRow));
});

$oApp->post('/updateProduct/:id', function($nId) use($oApp, $oProductMgr) {
	if(getUserType() != 'admin'){
		$oApp->halt(500, 'You are not login');
		return;
	}
	$oProduct = json_decode($oApp->request->getBody());
	//ChromePhp::info($oProduct);
	$nRow = $oProductMgr->updateProduct($oProduct);
	echo json_encode(array("rows"=>$nRow));
});

$oApp->post('/updateFeaturedProducts', function() use($oApp, $oProductMgr) {
	if(getUserType() != 'admin'){
		$oApp->halt(500, 'You are not login');
		return;
	}
	$oFeaturedProductList = json_decode($oApp->request->getBody());
	//ChromePhp::info($oFeaturedProductList);
	$oProductMgr->updateFeaturedProduct($oFeaturedProductList);
});

$oApp->post('/getFeaturedProducts', function() use($oApp, $oProductMgr) {
	if(getUserType() != 'admin'){
		$oApp->halt(500, 'You are not login');
		return;
	}
	echo json_encode($oProductMgr->getFeaturedProductIds());
});

$oApp->get('/createReviews', function() use($oApp, $oProductMgr) {
	if(getUserType() != 'admin'){
		$oApp->halt(500, 'You are not login');
		return;
	}
	$oProductMgr->createReviews_test();
});

$oApp->get('/clearReviews', function() use($oApp, $oProductMgr) {
	if(getUserType() != 'admin'){
		$oApp->halt(500, 'You are not login');
		return;
	}
	$oProductMgr->deleteReviews_test();
});
/* !CRUD APIs */

/***
 * Login/Logout
***/
$oApp->get('/backdoor', function() use($oApp, $oProductMgr) {
	$_SESSION['loggedin'] = True;
	$_SESSION['isAdmin'] = True;
	$oApp->redirect('/');
});

$oApp->get('/logout', function() use($oApp, $oProductMgr) {
	$_SESSION['loggedin'] = False;
	$_SESSION['isAdmin'] = False;
	$oApp->redirect('/');
});

$oApp->get('/doLogin', function() use($oApp, $oProductMgr) {
	$oCreds = json_decode(file_get_contents(__DIR__ . "/../creds/google.json"));
	define('CLIENT_ID', $oCreds->ClientID);
	define('CALLBACK_URL', (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . "/login");
	define('AUTH_URL', 'https://accounts.google.com/o/oauth2/auth');
	$params = array(
		'client_id' => CLIENT_ID,
		'redirect_uri' => CALLBACK_URL,
		'scope' => 'openid profile email',
		'response_type' => 'code',
	);

	$oApp->redirect(AUTH_URL . '?' . http_build_query($params));
});

$oApp->get('/login', function() use($oApp, $oProductMgr) {
	if(!isset($_GET['code'])){
		// refuse direct access
		$oApp->render('error.phtml');
		return;
	}

	$oCreds = json_decode(file_get_contents(__DIR__ . "/../creds/google.json"));
	define('CLIENT_ID', $oCreds->ClientID);
	define('CLIENT_SECRET', $oCreds->ClientSecret);
	define('CALLBACK_URL', (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . "/login");
	define('TOKEN_URL', 'https://accounts.google.com/o/oauth2/token');
	define('INFO_URL', 'https://www.googleapis.com/oauth2/v1/userinfo');

	$params = array(
		'code' => $_GET['code'],
		'grant_type' => 'authorization_code',
		'redirect_uri' => CALLBACK_URL,
		'client_id' => CLIENT_ID,
		'client_secret' => CLIENT_SECRET,
	);

	$params = http_build_query($params, "", "&");
	$header = array(
		"Content-Type: application/x-www-form-urlencoded",
		"Content-Length: " . strlen($params)
	);

	$options = array('http' => array(
		'method' => 'POST',
		'header' => implode("\r\n", $header),
		'content' => $params
	));

	$oRes = file_get_contents(TOKEN_URL, false, stream_context_create($options));

	$token = json_decode($oRes, true);
	if(isset($token['error'])){
		ChromePhp::error('error');
		exit;
	}
	$access_token = $token['access_token'];

	$params = array('access_token' => $access_token);
	$oRes = file_get_contents(INFO_URL . '?' . http_build_query($params));
	$oUserInfo = json_decode($oRes);
	ChromePhp::info($oUserInfo);
	ChromePhp::info("GetUserInfo success");
	
	if(isset($oCreds->Users)){
		foreach($oCreds->Users as $sEmail){
			if($sEmail == $oUserInfo->email){
				$_SESSION['loggedin'] = True;
				ChromePhp::info("Login success");
				break;
			}
		}
		if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == True){
			foreach($oCreds->Admins as $sEmail){
				if($sEmail == $oUserInfo->email){
					$_SESSION['isAdmin'] = True;
					ChromePhp::info("Admin Login success");
					break;
				}
			}
		} else {
			$oApp->render('error.phtml');
		}
	}

	$oApp->redirect('/');

});

/***
 * Inner functions
***/
function getUserType() {
	if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == True){
		if(isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == True){
			return 'admin';
		} else {
			return 'user';
		}
	} else {
		return '';
	}
}

$oApp->run();

