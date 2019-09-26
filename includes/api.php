<?php
error_reporting(0);
// AUTHENTICATION
	function create_item_permissions_check(WP_REST_Request $request){
		if (get_option('api-checkbox-auth') == 1) {
			$header = $request->get_params();
			global $wpdb;
			$consumer_key = wc_api_hash( sanitize_text_field($header['consumer_key']));
			$consumer_secret = $header['consumer_secret'];

			$auth = $wpdb->get_results( $wpdb->prepare("
			    SELECT consumer_key, consumer_secret, permissions
			    FROM {$wpdb->prefix}woocommerce_api_keys
			    WHERE consumer_key = '$consumer_key' AND consumer_secret = '$consumer_secret'
			", ''), ARRAY_A);

			if (!empty($auth)) {
				
				$permission = $auth[0]['permissions'];
				$route = $request->get_route();
				$perm_Read = array(
					'/v1/woo-api/get-products',
					'/v1/woo-api/get-category',
					'/v1/woo-api/get-customer',
				);
				$perm_Write = array(
					'/v1/woo-api/create-product',
					'/v1/woo-api/create-customer',
				);
				$perm_Read_Write = array(
					'/v1/woo-api/get-products',
					'/v1/woo-api/create-product',
					'/v1/woo-api/get-category',
					'/v1/woo-api/get-customer',
					'/v1/woo-api/create-customer',
				);
				if ($permission == 'read_write') {
					if(in_array($route,$perm_Read_Write)){
						return true;
					}else{
						return false;
					}
				}elseif ($permission == 'read') {
					if(in_array($route,$perm_Read)){
						return true;
					}else{
						return false;
					}
				}elseif ($permission == 'write') {
					if(in_array($route,$perm_Write)){
						return true;
					}else{
						return false;
					}
				}else{
					return false;
				}
			}else{
				return false;
			}
		}else{
			return true;
		}
	}
// END AUTH

// GET PRODUCTS
	add_action( 'rest_api_init', 'woo_api_register_route_products' );
	function woo_api_register_route_products() {
	    register_rest_route( 'v1/woo-api/','get-products', array(
	            'methods' => 'GET',
	            'callback' => 'woo_api_get_products',
	            'permission_callback' => 'create_item_permissions_check',
	        )
	    );
	}
	function woo_api_get_products(WP_REST_Request $request) {
		if (!empty($_REQUEST['id'])) {
			$product_Id = $_REQUEST['id'];
			$product = wc_get_product( $product_Id );
			if (!empty($product)) {
				$responce = array(
					'id'			=>	$product->get_id(),
					'sku'			=>	$product->get_sku(),
					'name'			=>	$product->get_name(),
					'type'			=>	$product->get_type(),
					'image'			=>	wp_get_attachment_image_src(get_post_thumbnail_id($product->get_id()),array(100, 100)),
					'category_id'	=>	$product->get_category_ids(),
					'price'			=>	$product->get_price(),
					'regular_price'	=>	$product->get_regular_price(),
					'sale_price'	=>	$product->get_sale_price(),
					'meta_data'		=>	$product->get_meta_data()
				);
			$response = new WP_REST_Response($responce);
			$response->set_status(200);
			}else{
				return new WP_Error( 'invalid_id', 'product does not exists', array('status' => 404) );
			}
		}else{
			if (empty($_REQUEST['per_page'])) {
				$per_page = -1;
			}else{
				$per_page = $_REQUEST['per_page'];
			}
			if (empty($_REQUEST['page'])) {
				$page = 0;
			}else{
				$page = $_REQUEST['page']*$per_page;
			}
			
			$all_ids = get_posts( array(
		        'post_type' => 'product',
		        'post_status' => 'publish',
		        'posts_per_page' => $per_page,
		        'offset'	=>	$page,
		        'fields' => 'ids',
			) );
			$responce = array();
			foreach ( $all_ids as $id ) {
			    $product = wc_get_product( $id );
			    $product = array(
					'id'			=>	$product->get_id(),
					'sku'			=>	$product->get_sku(),
					'name'			=>	$product->get_name(),
					'type'			=>	$product->get_type(),
					'image'			=>	wp_get_attachment_image_src(get_post_thumbnail_id($product->get_id()),array(100, 100)),
					'category_id'	=>	$product->get_category_ids(),
					'price'			=>	$product->get_price(),
					'regular_price'	=>	$product->get_regular_price(),
					'sale_price'	=>	$product->get_sale_price(),
					'meta_data'		=>	$product->get_meta_data()
				);

				array_push($responce, $product);
			}
			$response = new WP_REST_Response($responce);
			$response->set_status(200);
		}
	    return $response;
	}
// END API

// CREATE PRODUCT
	add_action( 'rest_api_init', 'woo_api_register_route_create_product' );
	function woo_api_register_route_create_product() {
	    register_rest_route( 'v1/woo-api/','create-product', array(
	            'methods' => 'POST',
	            'callback' => 'woo_api_create_product',
	            'permission_callback' => 'create_item_permissions_check',
	        )
	    );
	}
	function woo_api_create_product(WP_REST_Request $request){
		$data = $request->get_json_params();

		$name = $data['name'];
		$sku = $data['sku'];
		$type = $data['type'];
		$regular_price = $data['regular_price'];
		$status = $data['status'];
		
		global $wpdb;

		$check_exists = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );

		if (!empty($check_exists)) {
			return new WP_Error( 'error', 'product already exists', array('status' => 404) );
			// return rest_ensure_response( array('product_id' => $check_exists, 'status' => 'Product already exists') );
		}else{
			$post = array(
		        'post_title' => $name, //$item->name,
		        'post_content' => '',
		        'post_status' => $status,
		        'post_excerpt' => '',
		        'post_type' => "product",
		    );

			//Create post
			$post_id = wp_insert_post( $post, $wp_error );
			if($post_id){
			    $attach_id = get_post_meta($product->parent_id, "_thumbnail_id", true);
			    add_post_meta($post_id, '_thumbnail_id', $attach_id);
			}

			wp_set_object_terms( $post_id, 'Races', 'product_cat' );
			wp_set_object_terms($post_id, $type, 'product_type');

			update_post_meta( $post_id, '_visibility', 'visible' );
			update_post_meta( $post_id, '_stock_status', 'instock');
			update_post_meta( $post_id, 'total_sales', '0');
			update_post_meta( $post_id, '_downloadable', 'yes');
			update_post_meta( $post_id, '_virtual', 'yes');
			update_post_meta( $post_id, '_regular_price', $regular_price );
			update_post_meta( $post_id, '_sale_price', "" );
			update_post_meta( $post_id, '_purchase_note', "" );
			update_post_meta( $post_id, '_featured', "no" );
			update_post_meta( $post_id, '_weight', "" );
			update_post_meta( $post_id, '_length', "" );
			update_post_meta( $post_id, '_width', "" );
			update_post_meta( $post_id, '_height', "" );
			update_post_meta( $post_id, '_sku', $sku);
			update_post_meta( $post_id, '_product_attributes', array());
			update_post_meta( $post_id, '_sale_price_dates_from', "" );
			update_post_meta( $post_id, '_sale_price_dates_to', "" );
			update_post_meta( $post_id, '_price', $regular_price );
			update_post_meta( $post_id, '_sold_individually', "" );
			update_post_meta( $post_id, '_manage_stock', "no" );
			update_post_meta( $post_id, '_backorders', "no" );
			update_post_meta( $post_id, '_stock', "" );

			$response = new WP_REST_Response(array('product_id' => $post_id, 'status' => 'Product create successful'));
			$response->set_status(200);
			// return rest_ensure_response( array('product_id' => $post_id, 'status' => 'Product create successful') );
			return $response;
		}
	}
// END API

// GET CATEGORY API
	add_action( 'rest_api_init', 'woo_api_register_route_category' );
	function woo_api_register_route_category() {
	    register_rest_route( 'v1/woo-api/','get-category', array(
	            'methods' => 'GET',
	            'callback' => 'woo_api_get_category',
	            'permission_callback' => 'create_item_permissions_check',
	        )
	    );
	}
	function woo_api_get_category() {
		if (!empty($_REQUEST['id'])) {
			$cate_Id = $_REQUEST['id'];
			$args = array(
			    'taxonomy'   => "product_cat",
			    'number'     => '',
			    'orderby'    => '',
			    'order'      => '',
			    'hide_empty' => '',
			    'include'    => $cate_Id
			);
			$category = get_terms($args);
		}else{
			$args = array(
			    'taxonomy'   => "product_cat",
			    'number'     => '',
			    'orderby'    => '',
			    'order'      => '',
			    'hide_empty' => '',
			    'include'    => ''
			);
			$category = get_terms($args);
		}
		$response = new WP_REST_Response($category);
		$response->set_status(200);
	    return $response;
	}
// END API

// GET USERS TYPE CUSTOMERS
	add_action( 'rest_api_init', 'woo_api_register_route_customer' );
	function woo_api_register_route_customer() {
	    register_rest_route( 'v1/woo-api/','get-customer', array(
	            'methods' => 'GET',
	            'callback' => 'woo_api_get_customer',
	            'permission_callback' => 'create_item_permissions_check',
	        )
	    );
	}
	function woo_api_get_customer() {
		if (!empty($_REQUEST['id'])) {
			$customer_ID = $_REQUEST['id'];
			$users_info = get_user_meta ( $customer_ID );
			if (!empty($users_info)) {
				$userArray = array(
							"customerId"	=> $customer_ID,
							"first_name" 	=> $users_info["first_name"][0],
							"last_name"		=> $users_info["last_name"][0],
							"address1" 		=> $users_info["shipping_address_1"][0],
							"address2" 		=> $users_info["shipping_address_2"][0],
							"city" 			=> $users_info["shipping_city"][0],
							"mobile"		=> $users_info["billing_phone"][0],
							"country" 		=> WC()->countries->countries[ $users_info["billing_country"][0] ],
							"postalCode" 	=> $users_info["shipping_postcode"][0],
							"email" 		=> $users_info["billing_email"][0],
		                );
			}else{
				return new WP_Error( 'error', 'wrong customer id', array('status' => 404) );
			}
		}else{
			$customers = get_users( array('role'=>'customer') );
			$userArray = array();
			foreach ($customers as $customer ) {
				$users_info = get_user_meta ( $customer->ID);
				$customerId = $customer->ID;
				$array = array(
							'customerId'	=> $customerId,	
							"first_name" 	=> $users_info["first_name"][0],
							"last_name"		=> $users_info["last_name"][0],
							"address1" 		=> $users_info["shipping_address_1"][0],
							"address2" 		=> $users_info["shipping_address_2"][0],
							"city" 			=> $users_info["shipping_city"][0],
							"mobile"		=> $users_info["billing_phone"][0],
							"country" 		=> WC()->countries->countries[ $users_info["billing_country"][0] ],
							"postalCode" 	=> $users_info["shipping_postcode"][0],
							"email" 		=> $users_info["billing_email"][0],
		                );
				array_push($userArray, $array);		
			}
		}
		$response = new WP_REST_Response($userArray);
		$response->set_status(200);
	    return $response;
	}
// END API

// CREATE CUSTOMER
	add_action( 'rest_api_init', 'woo_api_register_route_create_customer' );
	function woo_api_register_route_create_customer() {
	    register_rest_route( 'v1/woo-api/','create-customer', array(
	            'methods' => 'POST',
	            'callback' => 'woo_api_create_customer',
	            'permission_callback' => 'create_item_permissions_check',
	        )
	    );
	}
	function woo_api_create_customer(WP_REST_Request $request){
		$data = $request->get_json_params();
		$name = $data['name'];
		$username = $data['username'];
		$password = $data['password'];
		$email = $data['email'];

		$user_id = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ){
			return new WP_Error( 'error', $user_id->get_error_message(), array('status' => 404) );
   			// return rest_ensure_response(array('status'=>$user_id->get_error_message()) );
		}else{
			$user_id_role = new WP_User($user_id);
	        $user_id_role->set_role('customer');
	        update_user_meta( $user_id, "first_name", $name );
	        // return rest_ensure_response( array('customer_id' => $user_id, 'status' => 'Customer create successful') );
	        $response = new WP_REST_Response( array('customer_id' => $user_id, 'status' => 'Customer create successful') );
			$response->set_status(200);
		    return $response;
		}
	}
// END API
