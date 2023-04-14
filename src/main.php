<?php
	error_reporting("E_ALL ^ E_NOTICE");
	date_default_timezone_set('Asia/Jakarta');
	require_once 'src/lib.php';
	$dbsql = new DATABASE();			
	if(isset($_GET['module'])){
		switch ($_GET['module']){
			case 'home':
				require_once('pages/home.html');
				break;

			case 'about':
				require_once('pages/about.html');
				break;

			case 'branch':
				require_once('pages/branch.html');
				break;
				
			case 'product':		
				$search_product = null;
				$principal_id = null;
				$productcategory_id = null;

				if(isset($_POST['search-product'])){
					if($_POST['search-product'] !== null && $_POST['search-product'] !== '') {
						$search_product = $_POST['search-product'];					 
					}
				}			
				if(isset($_GET['search-product'])){
					if($_GET['search-product'] !== null && $_GET['search-product'] !== '') {
						$search_product = $_GET['search-product'];
					}
				}			
				if(isset($_GET['productcategory_id'])){
					if($_GET['productcategory_id'] !== null && $_GET['productcategory_id'] !== ''){
						$productcategory_id = $_GET['productcategory_id'];
					}
				}	
				if(isset($_GET['principal_id'])){
					if($_GET['principal_id'] !== null && $_GET['principal_id'] !== ''){
						$principal_id = $_GET['principal_id'];
					}
				}
				if(isset($_GET['page'])){
					if($_GET['page'] !== null && $_GET['page'] !== ''){
						$page = $_GET['page'];
					}
				}				
				require_once('pages/product.html');
				break;

			case 'product_detail_description':
				if(isset($_GET['product_id'])){
					if($_GET['product_id'] !== null && $_GET['product_id'] !== ''){
						$product_id = $_GET['product_id'];
						require_once("pages/product_detail_description.html");						
					}
				}				
				break;

			case 'services':
				require_once('pages/services.html');			
				break;			

			case 'contact':
				require_once('pages/contact.html');			
				break;				
		}			
	}else{
		require_once('pages/home.html');	
	}	
?>	