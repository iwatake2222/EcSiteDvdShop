angular.module("appAdmin", []).controller("ctrlAdmin", function ($scope, $http) {
	$scope.showResult = false;
	$scope.productList = [];
	$scope.currentProduct = {};
	$scope.featuredProduct = {};
	var selectedProductIndex = -1;

	getAllProducts();
	resetFeaturedProducts();


	// CRUD for product
	$scope.checkDeleteProduct = function(nIndex) {
		selectedProductIndex = nIndex;  // not use
		angular.extend($scope.currentProduct, $scope.productList[nIndex]);
	};
	
	$scope.deleteProduct = function() {
		$http.delete('/deleteProduct/' + $scope.currentProduct.id).then(
			function(oRes){
				getAllProducts();
				$scope.showResult = true;
				$scope.result = oRes.data.rows + " rows are deleted";
			},
			handleError
		);
	};
	
	$scope.checkEditProduct = function(nIndex) {
		selectedProductIndex = nIndex;
		angular.extend($scope.currentProduct, $scope.productList[nIndex]);
		$scope.title = "Edit Product";
	};
	
	$scope.checkAddProduct = function(nIndex) {
		selectedProductIndex = -1;
		$scope.currentProduct = {};
		$scope.title = "Add Product";
	};
	
	$scope.saveProduct = function() {
		if(selectedProductIndex < 0) {
			$http.post('/addProduct', $scope.currentProduct).then(
				function(oRes) {
					getAllProducts();
					$scope.showResult = true;
					$scope.result = oRes.data.rows + " rows are created";
				},
				handleError
			);
		} else {
			$http.post('/updateProduct/' + $scope.currentProduct.id, $scope.currentProduct).then(
				function(oRes) {
					getAllProducts();
					$scope.showResult = true;
					$scope.result = oRes.data.rows + " rows are updated";
				},
				handleError
			);
		}
	};

	function getAllProducts() {
		$http.post('/getALlProducts').then(
			function(oRes) {
				$scope.productList = oRes.data;
				//console.log($scope.productList);
			},
			handleError
		);
	}
	
	// FeaturedProduct managing
	$scope.updateFeaturedProducts = function() {
		$scope.featuredProduct.str.replace('\r\n', 'n').replace('\r', '\n');;
		$scope.featuredProduct.idArray = $scope.featuredProduct.str.split('\n');
		$http.post('/updateFeaturedProducts', $scope.featuredProduct.idArray).then(
			function(oRes){
				// do nothing
			},
			handleError
		);
	};

	$scope.resetFeaturedProducts = function() {
		resetFeaturedProducts();
	};

	function resetFeaturedProducts() {
		$scope.featuredProduct.idArray = [];
		$http.post('/getFeaturedProducts').then(
			function(oRes){
				//$scope.featuredProduct.str="a,2,3";
				//$scope.featuredProduct.idArray = [1,4,5];
				//console.log(oRes);
				for(var i=0; i<oRes.data.length; i++) {
					$scope.featuredProduct.idArray.push(oRes.data[i].productId);
					//console.log( oRes.data[i].productId);
				}
				$scope.featuredProduct.str = $scope.featuredProduct.idArray.join('\r\n');
			},
			handleError
		);
	};
	
	// Review managing
	$scope.createReviews = function() {
		$http.get('/createReviews').then(
			function(oRes){
				// do nothing
			},
			handleError
		);
	};

	$scope.clearReviews = function() {
		$http.get('/clearReviews').then(
			function(oRes){
				// do nothing
			},
			handleError
		);
	};
	
	// common
	function handleError(sErr) {
		alert("Error: " + sErr.data);
	}
});
