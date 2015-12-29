<?php

class ProductMgr
{
	const LIMIT_LENGTH_DESCRIPTION = 300;
	private $oDb = null;

	public function getAllProducts() {
		$this->openDb();
		$oStmt = $this->oDb->prepare('SELECT * FROM products;');
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();
		foreach($oResult as $product) {
			$this->convertPrice($product);
			$this->limitDescription($product);
			$this->checkImageFile($product);
		}
		return $oResult;
	}

	public function getProductById($nId) {
		$this->openDb();
		$oStmt = $this->oDb->prepare('SELECT * FROM products WHERE id = :id');
		$oStmt->bindParam('id', $nId);
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();
		foreach($oResult as $product) {
			$this->convertPrice($product);
			$this->checkImageFile($product);
		}
		return $oResult[0];
	}

	public function getProductNameById($nId) {
		$this->openDb();
		$oStmt = $this->oDb->prepare('SELECT name FROM products WHERE id = :id');
		$oStmt->bindParam('id', $nId);
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();
		return $oResult[0]->name;
	}

	public function addProduct($productInfo) {
		$this->openDb();
		$oStmt = $this->oDb->prepare('
			INSERT INTO products ("name","genre","price","priceExp","description","starring","date","image", "stockQuantity")
			VALUES (:name, :genre, :price, :priceExp, :description, :starring, :date, :image, :stockQuantity);
		');

		if(!isset($productInfo->name)) $productInfo->name='no name';
		if(!isset($productInfo->genre)) $productInfo->genre='Etc';
		if(!isset($productInfo->price)) $productInfo->price='0';
		if(!isset($productInfo->description)) $productInfo->description='';
		if(!isset($productInfo->actors)) $productInfo->actors='';
		if(!isset($productInfo->date)) $productInfo->date='';
		if(!isset($productInfo->image)) $productInfo->image='';
		if(!isset($productInfo->stockQuantity)) $productInfo->stockQuantity='999';
		$productInfo->price = (int)((double)$productInfo->price*100);
		$productInfo->priceExp = -2;
		$oStmt->bindParam('name', $productInfo->name);
		$oStmt->bindParam('genre', $productInfo->genre);
		$oStmt->bindParam('price', $productInfo->price);
		$oStmt->bindParam('priceExp', $productInfo->priceExp);
		$oStmt->bindParam('description', $productInfo->description);
		$oStmt->bindParam('starring', $productInfo->starring);
		$oStmt->bindParam('date', $productInfo->date);
		$oStmt->bindParam('image', $productInfo->image);
		$oStmt->bindParam('stockQuantity', $productInfo->stockQuantity);
		$oStmt->execute();
		$this->closeDb();
		return $oStmt->rowCount();
	}

	public function deleteProduct($nId) {
		$this->openDb();
		$oStmt = $this->oDb->prepare('DELETE FROM products WHERE id = :id;');
		$oStmt->bindParam('id', $nId);
		$oStmt->execute();
		$this->closeDb();

		return $oStmt->rowCount();
	}

	public function updateProduct($productInfo) {
		$this->openDb();
		$oStmt = $this->oDb->prepare('
			UPDATE products SET name = :name, genre = :genre, price = :price, priceExp = :priceExp,
			description = :description, starring = :starring, date = :date, image = :image, stockQuantity = :stockQuantity
			WHERE id = :id;
		');
		$productInfo->price = (int)((double)$productInfo->price*100);
		$productInfo->priceExp = -2;
		$oStmt->bindParam('name', $productInfo->name);
		$oStmt->bindParam('genre', $productInfo->genre);
		$oStmt->bindParam('price', $productInfo->price);
		$oStmt->bindParam('priceExp', $productInfo->priceExp);
		$oStmt->bindParam('description', $productInfo->description);
		$oStmt->bindParam('starring', $productInfo->starring);
		$oStmt->bindParam('date', $productInfo->date);
		$oStmt->bindParam('image', $productInfo->image);
		$oStmt->bindParam('stockQuantity', $productInfo->stockQuantity);
		$oStmt->bindParam('id', $productInfo->id);
		$oStmt->execute();
		$this->closeDb();

		return $oStmt->rowCount();
	}

	public function getProductsByGenre($sGenre) {
		$this->openDb();
		$oStmt = $this->oDb->prepare('SELECT * FROM products WHERE genre = :genre');
		$oStmt->bindParam('genre', $sGenre);
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();
		foreach($oResult as $product) {
			$this->convertPrice($product);
			$this->limitDescription($product);
			$this->checkImageFile($product);
		}
		return $oResult;
	}

	public function getGenre() {
		$this->openDb();
		$oStmt = $this->oDb->prepare('SELECT * FROM genre');
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();
		return $oResult;
	}

	public function getFeaturedProducts() {
		$this->openDb();
		$oStmt = $this->oDb->prepare('SELECT productId, name, image FROM featured INNER JOIN products ON(featured.productId = products.id)');
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();
		foreach($oResult as $product) {
			$this->checkImageFile($product);
		}
		return $oResult;
	}

	public function getFeaturedProductIds() {
		$this->openDb();
		$oStmt = $this->oDb->prepare('SELECT productId FROM featured');
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();
		return $oResult;
	}

	public function updateFeaturedProduct($aIdList) {
		$this->openDb();
		$oStmt = $this->oDb->prepare('DELETE FROM featured');
		$oStmt->execute();
		foreach($aIdList as $productId) {
			$oStmt = $this->oDb->prepare('INSERT INTO featured ("productId") VALUES (:productId)');
			$oStmt->bindParam('productId', $productId);
			$oStmt->execute();
		}
		$this->closeDb();
		return;
	}

	public function getReviews($nProductId) {
		$this->openDb();
		$oStmt = $this->oDb->prepare('SELECT * FROM review WHERE productId = :productId ORDER BY date DESC');
		$oStmt->bindParam('productId', $nProductId);
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();
		foreach($oResult as $review) {
			$this->convertTimeUnixToStr($review);
			$this->convertSpecialChars($review);
		}
		return $oResult;
	}

	public function getRelatedProducts($nProductId) {
		$this->openDb();
		$oStmt = $this->oDb->prepare('
			SELECT id, name, image FROM products
			WHERE genre = (SELECT genre FROM products WHERE id = :productId)
				AND id != :productId
		');
		$oStmt->bindParam('productId', $nProductId);
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();
		return $oResult;
	}

	public function getProductByKeywords($sKeywords) {
		$this->openDb();
		$aKeys = explode(' ', $sKeywords);
		$strCondition ='';

		$nKeyIndex = 0;
		foreach($aKeys as $key){
			$strCondition .= ' (name LIKE :key' . $nKeyIndex . ' OR genre LIKE :key' . $nKeyIndex . ' OR description LIKE :key' . $nKeyIndex . ' OR starring LIKE :key' . $nKeyIndex . ') AND ';
			$nKeyIndex++;
		}

		$strCondition .= 1;	// just to ignore the final AND
		$oStmt = $this->oDb->prepare('SELECT * FROM products WHERE' . $strCondition);

		$nKeyIndex = 0;
		foreach($aKeys as $key){
			$key = "%".$key."%";
			$oStmt->bindParam('key' . $nKeyIndex, $key);
			$nKeyIndex++;
		}
		
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();
		foreach($oResult as $product) {
			$this->convertPrice($product);
			$this->limitDescription($product);
			$this->checkImageFile($product);
		}
		return $oResult;
	}

	public function setReview($nProductId, $nRate, $sComment, $sWriter) {
		$nTime = time();
		if($sWriter == '') $sWriter='Anonymous';
		$this->openDb();
		$oStmt = $this->oDb->prepare('
			INSERT INTO "main"."review" ("productId","rate","comment","writer","date")
			VALUES (:productId, :rate, :comment, :writer, :date);'
		);
		$oStmt->bindParam('productId', $nProductId);
		$oStmt->bindParam('rate', $nRate);
		$oStmt->bindParam('comment', $sComment);
		$oStmt->bindParam('writer', $sWriter);
		$oStmt->bindParam('date', $nTime);
		$oStmt->execute();
		$this->closeDb();
		return;
	}

	public function createReviews_test() {
		$this->openDb();
		$oStmt = $this->oDb->prepare('SELECT id FROM products;');
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();

		foreach($oResult as $product) {
			$nProductId = $product->id;
			$nRate = rand()%5 + 1;
			if ($nRate>=5) $sComment = 'I love it';
			else if ($nRate>=3) $sComment = 'I like it';
			else $sComment = 'Not bad...';
			$sWriter = 'Bot_' . rand();
			$this->setReview($nProductId, $nRate, $sComment, $sWriter);
		}
		return;
	}

	public function deleteReviews_test() {
		$this->openDb();
		$oStmt = $this->oDb->prepare('DELETE FROM review;');
		$oStmt->execute();
		$this->closeDb();

		return;
	}

	private function openDb() {
		$this->oDb = new PDO('sqlite:' . __DIR__ . '/../db/products.sqlite');
	}

	private function closeDb() {
		$this->oDb = null;
	}
	private function convertTimeUnixToStr ($oReview) {
		$zone = 3600*-5;	//USA
		$oReview->date = gmdate('D M Y H:i', $oReview->date + $zone);
	}
	private function convertPrice ($oProduct) {
		$oProduct->price *= pow(10,$oProduct->priceExp);
	}

	private function checkImageFile ($oProduct) {
		if(!file_exists("../www" . $oProduct->image) || $oProduct->image == ""){
			$oProduct->image = "/images/no_image.jpg";
		}
	}

	private function limitDescription ($oProduct) {
		if($oProduct->description == '') return;
		if(strlen($oProduct->description)>self::LIMIT_LENGTH_DESCRIPTION) {
			$oProduct->description = substr($oProduct->description, 0, self::LIMIT_LENGTH_DESCRIPTION);
			$oProduct->description .= ' ...';
		} else {
			$oProduct->description = substr($oProduct->description, 0, self::LIMIT_LENGTH_DESCRIPTION);
		}
	}

	private function convertSpecialChars ($oReview) {
		$oReview->comment = nl2br(htmlspecialchars($oReview->comment));
		$oReview->writer = nl2br(htmlspecialchars($oReview->writer));
	}

}
