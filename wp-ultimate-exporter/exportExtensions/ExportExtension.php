<?php
/**
 * WP Ultimate Exporter plugin file.
 *
 * Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com
 */

namespace Smackcoders\SMEXP;

if (!defined('ABSPATH')) exit; // Exit if accessed directly
$parent_autoload_path = WP_PLUGIN_DIR . '/wp-ultimate-csv-importer/vendor/autoload.php';
if (file_exists($parent_autoload_path)) {
    require_once $parent_autoload_path;
}
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
// require_once dirname(__FILE__) . '/WPQueryExport.php';
// require_once dirname(__FILE__) . '/WPQueryExport.php';

if (class_exists('\Smackcoders\FCSV\MappingExtension'))
{

	class ExportExtension extends \Smackcoders\FCSV\MappingExtension
	{
		public $allacf;

		public $allpodsfields;

		public $alltoolsetfields;

		public $typeOftypesField;
		public $offset=0;
		public $checkSplit;
		public $mode;
		public $totalRowCount;
		public $response = array();
		public $headers = array();
		public $module;
		public $exportType = 'csv';
		public $optionalType = null;
		public $conditions = array();
		public $eventExclusions = array();
		public $fileName;
		public $data = array();
		public $heading = true;
		public $delimiter = ',';
		public $enclosure = '"';
		public $auto_preferred = ",;\t.:|";
		public $output_delimiter = ',';
		public $linefeed = "\r\n";
		public $export_mode;
		public $export_log = array();
		public $limit;
		protected static $instance = null, $mapping_instance, $metabox_export, $jet_reviews_export, $jet_book_export, $jetengine_export, $export_handler, $post_export, $woocom_export, $review_export, $ecom_export, $learnpress_export,$wpquery_export;
		protected $plugin, $activateCrm, $crmFunctionInstance;
		public $plugisnScreenHookSuffix = null;
		public $random_data = '';

		public static function getInstance()
		{
			if (null == self::$instance)
			{
				self::$instance = new self;
				ExportExtension::$export_handler = ExportHandler::getInstance();
				ExportExtension::$post_export = PostExport::getInstance();
				ExportExtension::$woocom_export = WooCommerceExport::getInstance();
				ExportExtension::$review_export = CustomerReviewExport::getInstance();
				ExportExtension::$learnpress_export = LearnPressExport::getInstance();
				ExportExtension::$jetengine_export = JetEngineExport::getInstance();
				ExportExtension::$jet_book_export = JetBookingExport::getInstance();
				ExportExtension::$jet_reviews_export = JetReviewsExport::getInstance();
				ExportExtension::$metabox_export = metabox::getInstance();
				// ExportExtension::$wpquery_export = WPQueryExport::getInstance();

				self::$instance->doHooks();
			}
			return self::$instance;
		}

		public function doHooks()
		{
			$plugin_pages = ['com.smackcoders.csvimporternew.menu'];
			require_once WP_PLUGIN_DIR . '/wp-ultimate-exporter/wp-exp-hooks.php';
			global $plugin_ajax_hooks;

			$request_page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
			$request_action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
			if (in_array($request_page, $plugin_pages) || in_array($request_action, $plugin_ajax_hooks))
			{
				add_action('wp_ajax_parse_data', array(
					$this,
					'parseData'
				));
				add_action('wp_ajax_nopriv_parse_data', array(
					$this,
					'parseData'
				));
				add_action('wp_ajax_total_records', array(
					$this,
					'totalRecords'
				));
				add_action('wp_ajax_get_download', array(
					$this,
					'downloadFunction'
				));
			}
		}

		public function downloadFunction(){

			check_ajax_referer('smack-ultimate-csv-importer', 'securekey');            
			
			//Vulnerability fix - Arbitrary file download
			if (!is_user_logged_in() || !current_user_can('administrator')) {
				wp_die('You do not have sufficient permissions to access this file.');
			}

			$file_name = sanitize_file_name($_POST['fileName'] ?? '');
			$file_path = $_POST['filePath'] ?? '';	
			$random_folder = wp_generate_password(16, false); // 16-character random folder name
   	
			$allowed_directory = wp_upload_dir()['basedir'] . '/smack_uci_uploads/exports/'; // Example allowed directory
			
			$real_file_path = realpath($file_path);
			$real_allowed_directory = realpath($allowed_directory);	
		
			if (strpos($real_file_path, $real_allowed_directory) !== 0) {
				wp_die('Invalid file path or file not found.', 'Error', ['response' => 400]);
			}
						
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . $file_name . '"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($real_file_path));
			ob_clean();
			flush();
			readfile($real_file_path);
			wp_die();           
			
		}

		public function totalRecords()
		{
			
			check_ajax_referer('smack-ultimate-csv-importer', 'securekey');
			global $wpdb;
			$module = sanitize_text_field($_POST['module']);

			$optionalType = isset($_POST['optionalType']) ? sanitize_text_field($_POST['optionalType']) : '';
			if ($module == 'WooCommerceOrders')
			{
				$module = 'shop_order';
			}
			elseif ($module == 'WooCommerceCoupons')
			{
				$module = 'shop_coupon';
			}
			elseif ($module == 'WooCommerceRefunds')
			{
				$module = 'shop_order_refund';
			}
			elseif ($module == 'WooCommerceVariations')
			{
				$module = 'product_variation';
			}
			elseif ($module == 'WPeCommerceCoupons')
			{
				$module = 'wpsc-coupon';
			}
			elseif ($module == 'Users')
			{
				$get_available_user_ids = "select DISTINCT ID from $wpdb->users u join $wpdb->usermeta um on um.user_id = u.ID";
				$availableUsers = $wpdb->get_col($get_available_user_ids);
				$total = count($availableUsers);
				return $total;
			}
			elseif ($module == 'Tags')
			{
				$get_all_terms = get_tags('hide_empty=0');
				return count($get_all_terms);
				wp_die();
			}
			elseif ($module == 'Categories')
			{
				$get_all_terms = get_categories('hide_empty=0');
				return count($get_all_terms);
				wp_die();
			}
			elseif ($module == 'CustomPosts' && $optionalType == 'nav_menu_item')
			{
				$get_menu_ids = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}terms AS t LEFT JOIN {$wpdb->prefix}term_taxonomy AS tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'nav_menu' ", ARRAY_A);
				echo wp_json_encode(count($get_menu_ids));
				wp_die();
			}
			// elseif($module == 'CustomPosts' && $optionalType == 'widgets'){
			// 	echo wp_json_encode(1);
			// 	wp_die();
			// }
			else
			{
				$optional_type = NULL;
				if ($module == 'CustomPosts')
				{
					$optional_type = $optionalType;
				}
				$module = ExportExtension::$post_export->import_post_types($module, $optional_type);
			}
			// JetBooking module logic
			if ($module == 'JetBooking') {
				if (!is_plugin_active('jet-booking/jet-booking.php')) {
					echo wp_json_encode(0); // Return 0 if JetBooking plugin is not active
					wp_die();
				}
		
				// Get JetBooking bookings count
				$result = jet_abaf_get_bookings(['return' => 'arrays']);
				$total = $result ? count($result) : 0; // Count bookings or return 0
				echo wp_json_encode($total);
				wp_die();
			}

			// WooCommerceCustomer module logic
			if ($module == 'WooCommerceCustomer') {
				$user_count = count_users();
				$result = isset($user_count['avail_roles']['customer']) ? $user_count['avail_roles']['customer'] : 0;
				$total = $result;
				echo wp_json_encode($total);
				wp_die();
			}
			
			elseif ($module == 'JetReviews') {
				global $wpdb;
			
				// Verify if the JetReviews plugin is active
				if (!is_plugin_active('jet-reviews/jet-reviews.php')) {
					echo wp_json_encode(0);
					wp_die();
				}
			
				// Query to count approved reviews
				$query = "SELECT COUNT(*) FROM {$wpdb->prefix}jet_reviews";
				$count = $wpdb->get_var($query);
			
				// Ensure count is an integer and return the result
				$count = ($count !== null) ? intval($count) : 0;
				echo wp_json_encode($count);
				wp_die();
			}
			
			if(is_plugin_active('jet-engine/jet-engine.php')){
				$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
				foreach($get_slug_name as $key=>$get_slug){
					$value=$get_slug->slug;
					$optional_type=$value;	
					if($optionalType == $optional_type){
						$table_name='jet_cct_'.$optional_type;
						$get_menu= $wpdb->get_results("SELECT * FROM {$wpdb->prefix}$table_name");
						if(is_array($get_menu))
						$total = count($get_menu);
						else
						$total = 0;
							echo wp_json_encode($total);
							wp_die();
					}
				}
			}
			$get_post_ids = "select DISTINCT ID from $wpdb->posts";
			$get_post_ids .= " where post_type = '$module'";
		
			/**
			 * Check for specific status
			 */
			if($module == 'product' && is_plugin_active('woocommerce/woocommerce.php')){
				if(is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php') || is_plugin_active('polylang-wc/polylang-wc.php')){
					//TODO temporary fix 
					//wc_get_products only exports default language product 
					$products = "select DISTINCT ID from {$wpdb->prefix}posts";
					$products .= " where post_type = '$module'";
					$products .= "and post_status in ('publish','draft','future','private','pending') ";
					$products = $wpdb->get_col($products);

				}
				else{
					$product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
					$products = wc_get_products(array('status' => $product_statuses , 'limit' => -1));
				}
				$total = count($products);
				return $total;

			}
			elseif($module == 'shop_order'){
				if( is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php') || is_plugin_active('polylang-wc/polylang-wc.php')){
					$order_statuses = array('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending');
					$orders_id = wc_get_orders(array('status' => $order_statuses , 'limit' => -1));
					$get_post_ids = array();
					foreach($orders_id as $my_orders){
						$get_post_ids[] = $my_orders->get_id();
					} 
					foreach($get_post_ids as $ids){
						$module =$wpdb->get_var("SELECT post_type FROM {$wpdb->prefix}posts where id=$ids");
					}
					if($module == 'shop_order_placehold'){
						$orders = "select DISTINCT p.ID from {$wpdb->prefix}posts as p inner join {$wpdb->prefix}wc_orders as wc ON p.ID=wc.id";
						$orders.= " where p.post_type = '$module'";
						$orders .= "and wc.status in ('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending')";
						$orders = $wpdb->get_col($orders);
					}
					else{
						$orders = "select DISTINCT ID from {$wpdb->prefix}posts";
						$orders.= " where post_type = '$module'";
						$orders .= "and post_status in ('wc-completed','wc-cancelled','wc-on-hold','wc-processing','wc-pending')";
						$orders = $wpdb->get_col($orders);
					}

				}
				else{
					$order_statuses = array('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending');
					$orders = wc_get_orders(array('status' => $order_statuses , 'limit' => -1));
				}
				$total = count($orders);
				return $total;

			}
			elseif($module  == 'product_variation'){
				if(is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php') || is_plugin_active('polylang-wc/polylang-wc.php')){
					$extracted_ids = "select DISTINCT ID from {$wpdb->prefix}posts";
					$extracted_ids .= " where post_type = '$module'";
					$extracted_ids .= "and post_status in ('publish','draft','future','private','pending') AND post_parent !=0";
					$extracted_id = $wpdb->get_col($extracted_ids);
					$extracted_ids =array();
					//fix added for prema
					foreach($extracted_id as $ids){
						$parent_id = $wpdb->get_var("SELECT post_parent FROM {$wpdb->prefix}posts where ID=$ids");
						$post_status =$wpdb->get_var("SELECT post_status FROM {$wpdb->prefix}posts where ID=$parent_id");
						if(!empty($post_status )){
							if($post_status !='trash' && $post_status != 'inherit'){
								$extracted_ids [] =$ids;
							}

						}

					}

				}
				else{
					$product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
					$products = wc_get_products(array('status' => $product_statuses , 'limit' => -1));
					$variable_product_ids = [];
					foreach($products as $product){
						if ($product->is_type('variable')) {
							$variable_product_ids[] = $product->get_id();
						}
					}	
					$variation_count = 0;
					$variation_ids = array();
					foreach($variable_product_ids as $variable_product_id){
						$variable_product = wc_get_product($variable_product_id);
						$variation_ids[]  = $variable_product->get_children();
					}
					$extracted_ids = [];
					foreach ($variation_ids as $v_ids) {
						foreach ($v_ids as $v_id) {
							$extracted_ids[] = $v_id;
						}
					}
				}

				// $product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
				// $products = wc_get_products(array('status' => $product_statuses));
				// $variable_product_ids = [];
				// foreach($products as $product){
				//     if ($product->is_type('variable')) {
				//         $variable_product_ids[] = $product->get_id();
				//     }
				// }	
				// $variation_count = 0;
				// foreach($variable_product_ids as $variable_product_id){
				//     $variations = wc_get_products(array('parent_id' => $variable_product_id,'type' => 'variation','limit'=> -1));
				//     $variation_count += count($variations);
				// }	
				// $total = $variation_count;
				$total=count($extracted_ids);
				return $total;			
			}
			elseif ($module == 'shop_coupon')
			{
				$get_post_ids .= " and post_status in ('publish','draft','pending')";

			}
			elseif ($module == 'shop_order_refund')
			{

			}
			elseif ($module == 'forum')
			{
				$get_post_ids .= " and post_status in ('publish','draft','future','private','pending','hidden')";
			}
			elseif ($module == 'topic')
			{
				$get_post_ids .= " and post_status in ('publish','draft','future','open','pending','closed','spam')";
			}
			elseif ($module == 'reply')
			{
				$get_post_ids .= " and post_status in ('publish','spam','pending')";
			}
			$get_post_ids .= " and post_status in ('publish','draft','future','private','pending')";
			$get_total_row_count = $wpdb->get_col($get_post_ids);
			$total = count($get_total_row_count);
			return $total;
		}

		/**
		 * ExportExtension constructor.
		 * Set values into global variables based on post value
		 */
		public function __construct()
		{
			$this->plugin = Plugin::getInstance();
		}

		public function parseData()
		{
			if (!is_user_logged_in() || !current_user_can('manage_options')) {
				wp_send_json_error(['message' => 'Unauthorized access.'], 403);
				return;
			}
	
			// check_ajax_referer('smack-ultimate-csv-importer', 'securekey');
			if (!empty($_POST))
			{
				$query_data = isset($_POST['query_data'])?sanitize_text_field($_POST['query_data']):'';
        		$type = isset($_POST['type'])?sanitize_text_field($_POST['type']):'';
					
				$this->module = sanitize_text_field($_POST['module']);
				$this->random_data = sanitize_text_field($_POST['random_data']);
				//Whitelist of allowed export types
				$allowed_export_types = ['csv', 'xls', 'xlsx', 'json', 'xml', 'tsv'];
				// Sanitize and validate the export type
				$export_type = isset($_POST['exp_type']) ? sanitize_text_field($_POST['exp_type']) : 'csv';
				$this->exportType = in_array($export_type, $allowed_export_types) ? $export_type : 'csv';	
				$conditions = str_replace("\\", '', sanitize_text_field($_POST['conditions']));
				$conditions = json_decode($conditions, True);
				$conditions['specific_period']['to'] = date("Y-m-d", strtotime($conditions['specific_period']['to']));
				$conditions['specific_period']['from'] = date("Y-m-d", strtotime($conditions['specific_period']['from']));
				$this->conditions = isset($conditions) && !empty($conditions) ? $conditions : array();
				if ($this->module == 'Taxonomies' || $this->module == 'CustomPosts')
				{
					$this->optionalType = sanitize_text_field($_POST['optionalType']);
				}
				else
				{
					$this->optionalType = $this->getOptionalType($this->module);
				}
				$eventExclusions = str_replace("\\", '', sanitize_text_field(isset($_POST['eventExclusions']) ? sanitize_text_field($_POST['eventExclusions']) : ''));
				$eventExclusions = json_decode($eventExclusions, True);
				$this->eventExclusions = isset($eventExclusions) && !empty($eventExclusions) ? $eventExclusions : array();
				$this->fileName = isset($_POST['fileName']) ? sanitize_text_field($_POST['fileName']) : '';
				if (empty($_POST['offset']) || sanitize_text_field($_POST['offset']) == 'undefined')
				{
					$this->offset = 0;
				}
				else
				{
					$this->offset = isset($_POST['offset']) ? sanitize_text_field($_POST['offset']) : 0;
				}
				if (!empty($_POST['limit']))
				{
					$this->limit = isset($_POST['limit']) ? sanitize_text_field($_POST['limit']) : 1000;
				}
				else
				{
				if(!empty($conditions['specific_iteration_id']['is_check']) && $conditions['specific_iteration_id']['is_check'] == 'true') {
						$this->limit = !empty($conditions['specific_iteration_id']['iteration_id']) ? $conditions['specific_iteration_id']['iteration_id'] : '';
					}
					else{
						$this->limit           = 50;
					}

				}
				if (!empty($this->conditions['delimiter']['optional_delimiter']))
				{
					$this->delimiter = $this->conditions['delimiter']['optional_delimiter'] ? $this->conditions['delimiter']['optional_delimiter'] : ',';
				}
				elseif (!empty($this->conditions['delimiter']['delimiter']))
				{
					$this->delimiter = $this->conditions['delimiter']['delimiter'] ? $this->conditions['delimiter']['delimiter'] : ',';
					if ($this->delimiter == '{Tab}')
					{
						$this->delimiter = " ";
					}
					elseif ($this->delimiter == '{Space}')
					{
						$this->delimiter = " ";
					}
				}

				$this->export_mode = 'normal';
				$this->checkSplit = isset($_POST['is_check_split']) ? sanitize_text_field($_POST['is_check_split']) : 'false';
				if($type == 'post'){
					ExportExtension::$wpquery_export = new WPQueryExport();
					ExportExtension::$wpquery_export->exportwpquery($query_data);
				}
				elseif($type == 'user'){
					ExportExtension::$wpquery_export = new WPQueryExport();
					ExportExtension::$wpquery_export->exportwpquery_user($query_data);	
				}
				elseif($type == 'comment'){
					ExportExtension::$wpquery_export = new WPQueryExport();
					ExportExtension::$wpquery_export->exportwpquery_comment($query_data);		
				}
				else{
					$this->exportData();
				}
			
			}
		}

		public function commentsCount($mode = null)
		{
			global $wpdb;
			self::generateHeaders($this->module, $this->optionalType);
			$get_comments = "select * from {$wpdb->prefix}comments";
			// Check status
			if ($this->conditions['specific_status']['is_check'] == 'true')
			{
				if ($this->conditions['specific_status']['status'] == 'Pending') $get_comments .= " where comment_approved = '0'";
				elseif ($this->conditions['specific_status']['status'] == 'Approved') $get_comments .= " where comment_approved = '1'";
				else $get_comments .= " where comment_approved in ('0','1')";
			}
			else $get_comments .= " where comment_approved in ('0','1')";
			// Check for specific period
			if ($this->conditions['specific_period']['is_check'] == 'true')
			{
				if ($this->conditions['specific_period']['from'] == $this->conditions['specific_period']['to'])
				{
					$get_comments .= " and comment_date >= '" . $this->conditions['specific_period']['from'] . "'";
				}
				else
				{
					$get_comments .= " and comment_date >= '" . $this->conditions['specific_period']['from'] . "' and comment_date <= '" . $this->conditions['specific_period']['to'] . "'";
				}
			}
			// Check for specific authors
			if ($this->conditions['specific_authors']['is_check'] == '1')
			{
				if (isset($this->conditions['specific_authors']['author']))
				{
					$get_comments .= " and comment_author_email = '" . $this->conditions['specific_authors']['author'] . "'";
				}
			}
			$get_comments .= " order by comment_ID";
			$comments = $wpdb->get_results($get_comments);
			$totalRowCount = count($comments);
			return $totalRowCount;
		}

		public function getOptionalType($module)
		{
			if ($module == 'Tags')
			{
				$optionalType = 'post_tag';
			}
			elseif ($module == 'Posts')
			{
				$optionalType = 'posts';
			}
			elseif ($module == 'Pages')
			{
				$optionalType = 'pages';
			}
			elseif ($module == 'Categories')
			{
				$optionalType = 'category';
			}
			elseif ($module == 'Users')
			{
				$optionalType = 'users';
			}
			elseif ($module == 'Comments')
			{
				$optionalType = 'comments';
			}
			elseif($module == 'JetBooking'){
				$optionalType = 'JetBooking';
			}
			elseif ($module == 'CustomerReviews')
			{
				$optionalType = 'wpcr3_review';
			}
			elseif ($module == 'WooCommerce' || $module == 'WooCommerceOrders' || $module == 'WooCommerceCoupons' || $module == 'WooCommerceRefunds' || $module == 'WooCommerceVariations')
			{
				$optionalType = 'product';
			}
			elseif ($module == 'WooCommerce')
			{
				$optionalType = 'product';
			}
			elseif ($module == 'WooCommerceCustomer')
			{
				$optionalType = 'users';
			}
			elseif ($module == 'WPeCommerce')
			{
				$optionalType = 'wpsc-product';
			}
			elseif($module == 'JetReviews'){
				$optionalType = 'JetReviews';
			}
			elseif ($module == 'WPeCommerce' || $module == 'WPeCommerceCoupons')
			{
				$optionalType = 'wpsc-product';
			}
			return $optionalType;
		}

		/**
		 * set the delimiter
		 */
		public function setDelimiter($conditions)
		{
			if (isset($conditions['optional_delimiter']) && $conditions['optional_delimiter'] != '')
			{
				return $conditions['optional_delimiter'];
			}
			elseif (isset($conditions['delimiter']) && $conditions['delimiter'] != 'Select')
			{
				if ($conditions['delimiter'] == '{Tab}') return "\t";
				elseif ($conditions['delimiter'] == '{Space}') return " ";
				else return $conditions['delimiter'];
			}
			else
			{
				return ',';
			}
		}

		/**
		 * Export records based on the requested module
		 */
		public function exportData()
		{
			$this->mode = isset($this->mode) ? $this->mode : '';
			switch ($this->module)
			{
			case 'Posts':
			case 'Pages':
			case 'CustomPosts':
			case 'WooCommerce':
			case 'WooCommerceVariations':
			case 'WooCommerceOrders':
			case 'WooCommerceCoupons':
			case 'WooCommerceRefunds':
			case 'WPeCommerce':
			case 'WPeCommerceCoupons':
				self::FetchDataByPostTypes();
				break;
			case 'Users':
			case 'WooCommerceCustomer':
				self::FetchUsers($this->module, $this->optionalType, $this->conditions, $this->offset, $this->limit, $this->mode);
				break;
			// case 'WooCommerceCustomer':
			// 	ExportExtension::$wc_customer->FetchWooCommerceCustomer($this->module, $this->optionalType, $this->conditions, $this->offset, $this->limit, $this->mode);
			// 	break;
			case 'WooCommerceReviews':
			case 'Comments':
				self::FetchComments();
				break;
			case 'CustomerReviews':
				ExportExtension::$review_export->FetchCustomerReviews($this->module, $this->optionalType, $this->conditions, $this->offset, $this->limit, $this->mode);
				break;
			case 'Categories':
				ExportExtension::$post_export->FetchCategories($this->module, $this->optionalType);
				break;
				case 'JetBooking':
					$result = self::FetchDataByPostTypes();
					break;
					case 'JetReviews':
						$result = self::FetchDataByPostTypes($mod,$cat, $is_filter);
						break;
		
			case 'Tags':
				ExportExtension::$post_export->FetchTags($this->mode, $this->module, $this->optionalType);
			case 'Taxonomies':
				ExportExtension::$woocom_export->FetchTaxonomies($this->module, $this->optionalType);
				break;

			}
		}

		/**
		 * Fetch users and their meta information
		 * @param $mode
		 *
		 * @return array
		 */
		public function FetchUsers($module, $optionalType, $conditions, $offset, $limit,$mode = null)
		{
			global $wpdb;
			self::generateHeaders($this->module, $this->optionalType);
			// 	$get_available_user_ids = "select DISTINCT ID from {$wpdb->prefix}users u join {$wpdb->prefix}usermeta um on um.user_id = u.ID";

			// // Check for specific period
			// if ($this->conditions['specific_period']['is_check'] == 'true')
			// {
			// 	if ($this->conditions['specific_period']['from'] == $this->conditions['specific_period']['to'])
			// 	{
			// 		$get_available_user_ids .= " where u.user_registered >= '" . $this->conditions['specific_period']['from'] . "'";
			// 	}
			// 	else
			// 	{
			// 		$get_available_user_ids .= " where u.user_registered >= '" . $this->conditions['specific_period']['from'] . "' and u.user_registered <= '" . $this->conditions['specific_period']['to'] . " 23:00:00'";
			// 	}
			// }
			// $availableUsers = $wpdb->get_col($get_available_user_ids);

			// if (!empty($this->conditions['specific_period']['is_check']) && $this->conditions['specific_period']['is_check'] == 'true')
			// {
			// 	if ($this->conditions['specific_period']['from'] == $this->conditions['specific_period']['to'])
			// 	{
			// 		$availableUserss = array();
			// 		foreach ($availableUsers as $user_value)
			// 		{
			// 			$get_user_date_time = $wpdb->get_results("SELECT user_registered FROM {$wpdb->prefix}users WHERE ID={$user_value}", ARRAY_A);
			// 			$get_user_date = date("Y-m-d", strtotime($get_user_date_time[0]['user_registered']));
			// 			if ($get_user_date == $this->conditions['specific_period']['from'])
			// 			{
			// 				$get_user_id_value[] = $user_value;
			// 			}

			// 		}
			// 		$this->totalRowCount = count($get_user_id_value);
			// 		$availableUserss = $get_user_id_value;
			// 	}
			// 	else
			// 	{
			// 		$this->totalRowCount = count($availableUsers);
			// 		$get_available_user_ids .= " order by ID asc limit $this->offset, $this->limit";
			// 		$availableUserss = $wpdb->get_col($get_available_user_ids);
			// 	}
			// }
			// else
			// {
			// 	$this->totalRowCount = count($availableUsers);
			// 	$get_available_user_ids .= " order by ID asc limit $this->offset, $this->limit";
			// 	$availableUserss = $wpdb->get_col($get_available_user_ids);
			// }
			if ($module == 'WooCommerceCustomer') {
				$this->module = 'Users';
				// Get only users with the "customer" role
				$args = [
					'role'   => 'customer',
					'fields' => 'ID'
				];

				// Check for specific period
				if ($this->conditions['specific_period']['is_check'] == 'true') {
					$from = $this->conditions['specific_period']['from'];
					$to   = $this->conditions['specific_period']['to'];

					if ($from == $to) {
						$args['date_query'] = [
							[
								'after'     => $from,
								'inclusive' => true,
							]
						];
					} else {
						$args['date_query'] = [
							[
								'after'     => $from,
								'before'    => $to . ' 23:59:59',
								'inclusive' => true,
							]
						];
					}
				}

				// Get customer user IDs
				$availableUserss = get_users($args);
			} else {
				// Fetch all user IDs with the additional condition
				$get_available_user_ids = "SELECT DISTINCT u.ID 
										FROM {$wpdb->prefix}users u 
										JOIN {$wpdb->prefix}usermeta um ON um.user_id = u.ID";

				// Check for specific period
				if ($this->conditions['specific_period']['is_check'] == 'true') {
					if ($this->conditions['specific_period']['from'] == $this->conditions['specific_period']['to']) {
						$get_available_user_ids .= " WHERE u.user_registered >= '" . esc_sql($this->conditions['specific_period']['from']) . "'";
					} else {
						$get_available_user_ids .= " WHERE u.user_registered >= '" . esc_sql($this->conditions['specific_period']['from']) . "' 
													AND u.user_registered <= '" . esc_sql($this->conditions['specific_period']['to']) . " 23:59:59'";
					}
				}

				$availableUserss = $wpdb->get_col($get_available_user_ids);
			}


			if (!empty($availableUserss))
			{
				$this->totalRowCount = count($availableUserss);
				$whereCondition = '';
				foreach ($availableUserss as $userId)
				{
					if ($whereCondition != '')
					{
						$whereCondition = $whereCondition . ',' . $userId;
					}
					else
					{
						$whereCondition = $userId;
					}
					// Prepare the user details to be export
					$query_to_fetch_users = "SELECT * FROM {$wpdb->prefix}users where ID in ($whereCondition);";
					$users = $wpdb->get_results($query_to_fetch_users);
					if (!empty($users))
					{
						foreach ($users as $userInfo)
						{
							foreach ($userInfo as $userKey => $userVal)
							{
								$this->data[$userId][$userKey] = $userVal;
							}
						}
					}
					$userMeta = $wpdb->get_results("SELECT user_id, meta_key, meta_value FROM {$wpdb->prefix}users wp JOIN {$wpdb->prefix}usermeta wpm ON wpm.user_id = wp.ID WHERE ID = {$userId}");
					$wptypesfields = get_option('wpcf-usermeta');
					$wptypesfields = get_option('wpcf-usermeta');

					if (!empty($wptypesfields))
					{
						$i = 1;
						foreach ($wptypesfields as $key => $value)
						{
							$typesf[$i] = 'wpcf-' . $key;
							$typeOftypesField[$typesf[$i]] = $value['type'];
							$i++;
						}
					}
					if (!empty($userMeta))
					{
						foreach ($userMeta as $userMetaInfo)
						{
							if ($userMetaInfo->meta_key == $wpdb->prefix.'capabilities')
							{
								
								if(is_plugin_active('members/members.php')){
									$data = unserialize($userMetaInfo->meta_value);
									$roles = array_keys(array_filter($data));
									$role = implode('|', $roles);
									$this->data[ $userId ][ 'multi_user_role' ] = $role;
								}
								else{
									$userRole = $this->getUserRole($userMetaInfo->meta_value);
									$this->data[ $userId ][ 'role' ] = $userRole;
								}

							}
							elseif ($userMetaInfo->meta_key == 'description')
							{
								$this->data[$userId]['biographical_info'] = $userMetaInfo->meta_value;
							}
							elseif ($userMetaInfo->meta_key == 'comment_shortcuts')
							{
								$this->data[$userId]['enable_keyboard_shortcuts'] = $userMetaInfo->meta_value;
							}
							elseif ($userMetaInfo->meta_key == 'show_admin_bar_front')
							{
								$this->data[$userId]['show_toolbar'] = $userMetaInfo->meta_value;
							}
							elseif ($userMetaInfo->meta_key == 'rich_editing')
							{
								$this->data[$userId]['disable_visual_editor'] = $userMetaInfo->meta_value;
							}
							elseif ($userMetaInfo->meta_key == 'locale')
							{
								$this->data[$userId]['language'] = $userMetaInfo->meta_value;
							}
							elseif (isset($typesf) && in_array($userMetaInfo->meta_key, $typesf))
							{
								$typeoftype = $typeOftypesField[$userMetaInfo->meta_key];
								if (is_serialized($userMetaInfo->meta_value))
								{
									$typefileds = unserialize($userMetaInfo->meta_value);
									$typedata = "";
									foreach ($typefileds as $key2 => $value2)
									{
										if (is_array($value2))
										{
											foreach ($value2 as $key3 => $value3)
											{
												$typedata .= $value3 . ',';
											}
										}
										else $typedata .= $value2 . ',';
									}
									if (preg_match('/wpcf-/', $userMetaInfo->meta_key))
									{
										$userMetaInfo->meta_key = preg_replace('/wpcf-/', '', $userMetaInfo->meta_key);
										$this->data[$userId][$userMetaInfo->meta_key] = substr($typedata, 0, -1);
									}
								}
								elseif ($typeoftype == 'date')
								{
									$this->data[$userId][$userMetaInfo->meta_key] = date('Y-m-d', $userMetaInfo->meta_value);
								}
								$multi_row = '_' . $userMetaInfo->meta_key . '-sort-order';

								$multi_data = get_user_meta($userId, $multi_row);
								$multi_data = $multi_data[0];
								if (is_array($multi_data))
								{
									foreach ($multi_data as $k => $mid)
									{
										$m_data = $this->get_common_post_metadata($mid);
										if ($typeoftype == 'date') $multi_data[$k] = date('Y-m-d H:i:s', $m_data['meta_value']);
										else $multi_data[$k] = $m_data['meta_value'];
									}
									$this->data[$userId][$userMetaInfo->meta_key] = implode('|', $multi_data);
									if (preg_match('/wpcf-/', $userMetaInfo->meta_key))
									{
										$userMetaInfo->meta_key = preg_replace('/wpcf-/', '', $userMetaInfo->meta_key);

										$this->data[$userId][$userMetaInfo->meta_key] = implode('|', $multi_data);
									}
								}
								else
								{
									if (preg_match('/wpcf-/', $userMetaInfo->meta_key))
									{
										$userMetaInfo->meta_key = preg_replace('/wpcf-/', '', $userMetaInfo->meta_key);
										$this->data[$userId][$userMetaInfo
	       ->meta_key] = $userMetaInfo->meta_value;
									}
								}
							}

							else
							{

								$this->data[$userId][$userMetaInfo
	     ->meta_key] = $userMetaInfo->meta_value;
							}
						}
						// Prepare the buddy meta details to be export
						if (is_plugin_active('buddypress/bp-loader.php'))
						{
							$query_to_fetch_buddy_meta = $wpdb->prepare("SELECT user_id,field_id,value,name FROM {$wpdb->prefix}bp_xprofile_data bxd inner join {$wpdb->prefix}users wp  on bxd.user_id = wp.ID inner join {$wpdb->prefix}bp_xprofile_fields bxf on bxf.id = bxd.field_id where user_id=%d", $userId);
							$buddy = $wpdb->get_results($query_to_fetch_buddy_meta);
							if (!empty($buddy))
							{
								foreach ($buddy as $buddyInfo)
								{
									foreach ($buddyInfo as $field_id => $value)
									{
										$this->data[$userId][$buddyInfo
	       ->name] = $buddyInfo->value;
									}
								}
							}
						}
						ExportExtension::$post_export->getPostsMetaDataBasedOnRecordId($userId, $this->module, $this->optionalType);
					}
				}
			}

			$result = self::finalDataToExport($this->data, $this->module,$this->optionalType);
			if ($mode == null) self::proceedExport($result);
			else return $result;
		}

		public function mergeWithUserMeta($acf_field_values)
		{

			foreach ($acf_field_values as $acf_field_value)
			{

			}
		}

		/**
		 * Fetch all Comments
		 * @param $mode
		 *
		 * @return array
		 */
		public function FetchComments($mode = null)
		{
		
			global $wpdb;
			self::generateHeaders($this->module, $this->optionalType);
			$get_comments = "select * from {$wpdb->prefix}comments";
			// Check status
			if (isset($this->conditions['specific_status']['is_check']) && $this->conditions['specific_status']['is_check'] == 'true')
			{
				if ($this->conditions['specific_status']['status'] == 'Pending') $get_comments .= " where comment_approved = '0'";
				elseif ($this->conditions['specific_status']['status'] == 'Approved') $get_comments .= " where comment_approved = '1'";
				else $get_comments .= " where comment_approved in ('0','1')";
			}
			else $get_comments .= " where comment_approved in ('0','1')";
			// Check for specific period
			if ($this->conditions['specific_period']['is_check'] == 'true')
			{
				if ($this->conditions['specific_period']['from'] == $this->conditions['specific_period']['to'])
				{
					$get_comments .= " and comment_date >= '" . $this->conditions['specific_period']['from'] . "'";
				}
				else
				{
					$get_comments .= " and comment_date >= '" . $this->conditions['specific_period']['from'] . "' and comment_date <= '" . $this->conditions['specific_period']['to'] . " 23:00:00'";
				}
			}
			// Check for specific authors
			if ($this->conditions['specific_authors']['is_check'] == '1')
			{
				if (isset($this->conditions['specific_authors']['author']))
				{
					$get_comments .= " and comment_author_email = '" . $this->conditions['specific_authors']['author'] . "'";
				}
			}

			if($this->module == 'WooCommerceReviews'){
			 $get_comments .= " and comment_type = 'review'";
			}
	
			$comments = $wpdb->get_results($get_comments);

			if (!empty($this->conditions['specific_period']['is_check']) && $this->conditions['specific_period']['is_check'] == 'true')
			{
				if ($this->conditions['specific_period']['from'] == $this->conditions['specific_period']['to'])
				{
					$limited_comments = array();
					foreach ($comments as $comments_value)
					{
						// $get_comment_date_time = $wpdb->get_results($wpdb->prepare("SELECT comment_date FROM {$wpdb->prefix}comments WHERE comment_id=$comments_value->comment_ID") , ARRAY_A);
						$get_comment_date_time = $wpdb->get_results("SELECT comment_date FROM {$wpdb->prefix}comments WHERE comment_id = {$comments_value->comment_ID}", ARRAY_A);
						$get_comment_date = date("Y-m-d", strtotime($get_comment_date_time[0]['comment_date']));
						if ($get_comment_date == $this->conditions['specific_period']['from'])
						{
							$get_comment_date_value[] = $comments_value;
						}

					}
					$this->totalRowCount = count($get_comment_date_value);
					$limited_comments = $get_comment_date_value;
				}
				else
				{
					$this->totalRowCount = count($comments);
					$get_comments .= " order by comment_ID asc limit $this->offset, $this->limit";
					$limited_comments = $wpdb->get_results($get_comments);
				}
			}
			else
			{
				$this->totalRowCount = count($comments);
				$get_comments .= " order by comment_ID asc limit $this->offset, $this->limit";
				$limited_comments = $wpdb->get_results($get_comments);
			}

			if (!empty($limited_comments))
			{
				foreach ($limited_comments as $commentInfo)
				{
					$user_id = $commentInfo->user_id;
					if (!empty($user_id))
					{
						$users_login = $wpdb->get_results("SELECT user_login FROM {$wpdb->prefix}users WHERE ID = '$user_id'");
						foreach ($users_login as $users_key => $users_value)
						{
							foreach ($users_value as $u_key => $u_value)
							{
								$users_id = $u_value;
							}
						}
					}
					foreach ($commentInfo as $commentKey => $commentVal)
					{
						$this->data[$commentInfo->comment_ID][$commentKey] = $commentVal;
						$this->data[$commentInfo->comment_ID]['user_id'] = isset($users_id) ? $users_id : '';
					}
					$get_comment_rating = get_comment_meta($commentInfo->comment_ID, 'rating', true);
					if(!empty($get_comment_rating)){
						$this->data[$commentInfo->comment_ID]['comment_rating'] = $get_comment_rating;
					}
				}
			}
			$result = self::finalDataToExport($this->data, $this->module,$this->optionalType);
			if ($mode == null) self::proceedExport($result);
			else return $result;
		}

		/**
		 * Generate CSV headers
		 *
		 * @param $module       - Module to be export
		 * @param $optionalType - Exclusions
		 */
		public function generateHeaders($module, $optionalType)
		{
			if ($module == 'CustomPosts' || $module == 'Taxonomies' || $module == 'Categories' || $module == 'Tags')
			{
				if(is_plugin_active('events-manager/events-manager.php') &&$optionalType == 'event'){
					$optionalType = 'Events';
				}
				$default = $this->get_fields($optionalType); // Call the super class function

			}
			else
			{
				$default = $this->get_fields($module);
			}
			$headers = [];
			foreach ($default as $key => $fields)
			{
				foreach ($fields as $groupKey => $fieldArray)
				{

					foreach ($fieldArray as $fKey => $fVal)
					{
						if (is_array($fVal) || is_object($fVal))
						{
							foreach ($fVal as $rKey => $rVal)
							{
								if (!in_array($rVal['name'], $headers)) $headers[] = $rVal['name'];
							}
						}
					}

				}
			}
			if ($optionalType == 'elementor_library')
			{
				$headers = [];
				$headers = ['ID', 'Template title', 'Template content', 'Style', 'Template type', 'Created time', 'Created by', 'Template status', 'Category'];
			}
			if (isset($this->eventExclusions['is_check']) && $this->eventExclusions['is_check'] == 'true'):
				$headers_with_exclusion = self::applyEventExclusion($headers, $optionalType);
			$this->headers = $headers_with_exclusion;
			else:
			$this->headers = $headers;
			endif;
		}

		/**
		 * Fetch data by requested Post types
		 * @param $mode
		 * @return array
		 */
		public function FetchDataByPostTypes($mode = null)
		{
			$exp_module = '';
			if (empty($this->headers)) $this->generateHeaders($this->module, $this->optionalType);
			$recordsToBeExport = ExportExtension::$post_export->getRecordsBasedOnPostTypes($this->module, $this->optionalType, $this->conditions, $this->offset, $this->limit, $this->headers);
			if (!empty($recordsToBeExport))
			{
				foreach ($recordsToBeExport as $postId)
				{
					$exp_module = $this->module;
					if($exp_module !== 'JetBooking' && $exp_module !== 'JetReviews') {
						$this->data[$postId] = $this->getPostsDataBasedOnRecordId($postId,$this->module);	
					}				
					$this->data[$postId] = $this->getPostsDataBasedOnRecordId($postId,$this->module);
					if ($exp_module == 'Posts' || $exp_module == 'WooCommerce' || $exp_module == 'CustomPosts' || $exp_module == 'Categories' || $exp_module == 'Tags' || $exp_module == 'Taxonomies' || $exp_module == 'Pages')
					{
						$this->getWPMLData($postId, $this->optionalType, $exp_module);
					}

					if ($exp_module == 'Posts' || $exp_module == 'CustomPosts' || $exp_module == 'Pages' || $exp_module == 'WooCommerce')
					{
						if(is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php') || is_plugin_active('polylang-wc/polylang-wc.php')){
							$this->getPolylangData($postId, $this->optionalType, $exp_module);
						}
					}
					if($exp_module == 'CustomPosts'){
						if(is_plugin_active('geodirectory/geodirectory.php')){
							$this->getGeoPlaceData($postId,$this->optionalType,$exp_module);
						}
					}	
					ExportExtension::$post_export->getPostsMetaDataBasedOnRecordId($postId, $this->module, $this->optionalType);
					$this->getTermsAndTaxonomies($postId, $this->module, $this->optionalType);
					if($this->module == 'JetBooking')
					ExportExtension::$jet_book_export->getJetBookingData($postId, $this->module, $this->optionalType);
					if ($this->module == 'JetReviews') {
					ExportExtension::$jet_reviews_export->getJetReviewsData($postId, $this->module, $this->optionalType);
					}
					if ($this->module == 'WooCommerce') ExportExtension::$woocom_export->getProductData($postId, $this->module, $this->optionalType);
					if ($this->module == 'WooCommerceRefunds') ExportExtension::$woocom_export->getWooComCustomerUser($postId, $this->module, $this->optionalType);
					if ($this->module == 'WooCommerceOrders') ExportExtension::$woocom_export->getWooComOrderData($postId, $this->module, $this->optionalType);
					if ($this->module == 'WooCommerceVariations') ExportExtension::$woocom_export->getVariationData($postId, $this->module, $this->optionalType);
					if($this->module == 'WooCommerceCoupons') ExportExtension::$woocom_export->getCouponsData($postId, $this->module, $this->optionalType);
					if ($this->module == 'WPeCommerce') ExportExtension::$ecom_export->getEcomData($postId, $this->module, $this->optionalType);
					if ($this->module == 'WPeCommerceCoupons') ExportExtension::$ecom_export->getEcomCouponData($postId, $this->module, $this->optionalType);

					if ($this->optionalType == 'lp_course') ExportExtension::$learnpress_export->getCourseData($postId);
					if ($this->optionalType == 'lp_lesson') ExportExtension::$learnpress_export->getLessonData($postId);
					if ($this->optionalType == 'lp_quiz') ExportExtension::$learnpress_export->getQuizData($postId);
					if ($this->optionalType == 'lp_question') ExportExtension::$learnpress_export->getQuestionData($postId);
					if ($this->optionalType == 'lp_order') ExportExtension::$learnpress_export->getOrderData($postId);

					if ($this->optionalType == 'stm-courses') ExportExtension::$woocom_export->getCourseDataMasterLMS($postId);

					if ($this->optionalType == 'stm-questions') ExportExtension::$woocom_export->getQuestionDataMasterLMS($postId);

					if ($this->optionalType == 'stm-lessons') ExportExtension::$woocom_export->getLessonDataMasterLMS($postId);
					if ($this->optionalType == 'stm-orders') ExportExtension::$woocom_export->orderDataMasterLMS($postId);
					if ($this->optionalType == 'stm-quizzes') ExportExtension::$woocom_export->quizzDataMasterLMS($postId);
					if ($this->optionalType == 'elementor_library') ExportExtension::$woocom_export->elementor_export($postId);
					if ($this->optionalType == 'nav_menu_item') ExportExtension::$woocom_export->getMenuData($postId);

					if ($this->optionalType == 'widgets') self::$instance->getWidgetData($postId, $this->headers);
				}
			}
			$exp_module = $this->module; 
			if(is_plugin_active('jet-engine/jet-engine.php')){
				global $wpdb;
				$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
		
				foreach($get_slug_name as $key=>$get_slug){
					$value = $get_slug->slug;
					$optional_type = $value;
					if($this->optionalType == $optional_type){
						$table_name='jet_cct_'.$this->optionalType;										
	
						$jet_values = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}$table_name order by _ID asc limit $this->offset,$this->limit ");
						if(!empty($jet_values)) {
							foreach($jet_values as $jet_value) {
								foreach($jet_value as $field_id => $value) {
									$this->data[$jet_value->_ID][$field_id] = $value;					
	
								}
							}
						}
						foreach($this->data as $id => $value){
							ExportExtension::$post_export->getPostsMetaDataBasedOnRecordId($id, $this->module, $this->optionalType);
						}
					}
				}
				$slug = $this->optionalType;
				$getarg = $wpdb->get_results("SELECT args from {$wpdb->prefix}jet_post_types where slug = '$slug' and status = 'content-type'",ARRAY_A);		
				foreach($getarg as $key => $value){				
					$arg_data = $value['args'];				
					break;
				}
				if(!empty($arg_data)){
					$arg_data = unserialize($arg_data);
					if(!empty($arg_data) && array_key_exists('has_single',$arg_data) && $arg_data['has_single']){
						$this->data[$id]['cct_single_post_title'] = $arg_data['related_post_type_title'] ?? '';
						$this->data[$id]['cct_single_post_content'] = $arg_data['related_post_type_content'] ?? '';				
					}
				}	
			}
			/** Added post format for 'standard' property */
			if ($exp_module == 'Posts' || $exp_module == 'CustomPosts' || $exp_module == 'WooCommerce')
			{
				foreach ($this->data as $id => $records)
				{
					if (!array_key_exists('post_format', $records))
					{
						$records['post_format'] = 'standard';
						$this->data[$id] = $records;
					}
				}

			}
			if($this->optionalType == 'course'){
				foreach($this->data as $id => $records){
					if(array_key_exists('_llms_instructors',$records)){
						$instructor=unserialize($records['_llms_instructors']);
						if(is_array($instructor)){
							$arr_ins=array();
							foreach($instructor as $ins_val){
								$arr_val=array_values($ins_val);
								unset($arr_val[0]);
								unset($arr_val[2]);
								$arr_ins[] = implode(',',$arr_val);

							}
							$records['_llms_instructors'] = implode('|',$arr_ins);
							$this->data[$id] = $records;

						}


					}
				}
			}


			/** End post format */

			$result = self::finalDataToExport($this->data, $this->module,$this->optionalType);
			if ($mode == null) self::proceedExport($result);
			else return $result;
		}

		public function getWidgetData($postId, $headers)
		{

			global $wpdb;
			$get_sidebar_widgets = get_option('sidebars_widgets');

			$total_footer_arr = [];

			foreach ($get_sidebar_widgets as $footer_key => $footer_arr)
			{
				if ($footer_key != 'wp_inactive_widgets' || $footer_key != 'array_version')
				{
					if (strpos($footer_key, 'sidebar') !== false)
					{
						$get_footer = explode('-', $footer_key);
						$footer_number = $get_footer[1];

						foreach ($footer_arr as $footer_values)
						{
							$total_footer_arr[$footer_values] = $footer_number;
						}
					}
				}
			}

			foreach ($headers as $key => $value)
			{
				$get_widget_value[$value] = $wpdb->get_row("SELECT option_value FROM {$wpdb->prefix}options where option_name = '{$value}'", ARRAY_A);

				$header_key = explode('widget_', $value);

				if ($value == 'widget_recent-posts')
				{
					$recent_posts = unserialize($get_widget_value[$value]['option_value']);
					$recent_post = '';
					foreach ($recent_posts as $dk => $dv)
					{
						if ($dk != '_multiwidget')
						{
							$post_key = $header_key[1] . '-' . $dk;
							$recent_post .= $dv['title'] . ',' . $dv['number'] . ',' . $dv['show_date'] . '->' . $total_footer_arr[$post_key] . '|';
						}
					}
					$recent_post = rtrim($recent_post, '|');
				}
				elseif ($value == 'widget_pages')
				{
					$recent_pages = unserialize($get_widget_value[$value]['option_value']);
					$recent_page = '';
					foreach ($recent_pages as $dk => $dv)
					{
						if (isset($dv['exclude']))
						{
							$exclude_value = str_replace(',', '/', $dv['exclude']);
						}

						if ($dk != '_multiwidget')
						{
							$page_key = $header_key[1] . '-' . $dk;
							$recent_page .= $dv['title'] . ',' . $dv['sortby'] . ',' . $exclude_value . '->' . $total_footer_arr[$page_key] . '|';
						}
					}
					$recent_page = rtrim($recent_page, '|');
				}
				elseif ($value == 'widget_recent-comments')
				{
					$recent_comments = unserialize($get_widget_value[$value]['option_value']);
					$recent_comment = '';
					foreach ($recent_comments as $dk => $dv)
					{
						if ($dk != '_multiwidget')
						{
							$comment_key = $header_key[1] . '-' . $dk;
							$recent_comment .= $dv['title'] . ',' . $dv['number'] . '->' . $total_footer_arr[$comment_key] . '|';
						}
					}
					$recent_comment = rtrim($recent_comment, '|');
				}
				elseif ($value == 'widget_archives')
				{
					$recent_archives = unserialize($get_widget_value[$value]['option_value']);
					$recent_archive = '';
					foreach ($recent_archives as $dk => $dv)
					{
						if ($dk != '_multiwidget')
						{
							$archive_key = $header_key[1] . '-' . $dk;
							$recent_archive .= $dv['title'] . ',' . $dv['count'] . ',' . $dv['dropdown'] . '->' . $total_footer_arr[$archive_key] . '|';
						}
					}
					$recent_archive = rtrim($recent_archive, '|');
				}
				elseif ($value == 'widget_categories')
				{
					$recent_categories = unserialize($get_widget_value[$value]['option_value']);
					$recent_category = '';
					foreach ($recent_categories as $dk => $dv)
					{
						if ($dk != '_multiwidget')
						{
							$cat_key = $header_key[1] . '-' . $dk;
							$recent_category .= $dv['title'] . ',' . $dv['count'] . ',' . $dv['hierarchical'] . ',' . $dv['dropdown'] . '->' . $total_footer_arr[$cat_key] . '|';
						}
					}
					$recent_category = rtrim($recent_category, '|');
				}
			}

			$this->data[$postId]['widget_recent-posts'] = $recent_post;
			$this->data[$postId]['widget_pages'] = $recent_page;
			$this->data[$postId]['widget_recent-comments'] = $recent_comment;
			$this->data[$postId]['widget_archives'] = $recent_archive;
			$this->data[$postId]['widget_categories'] = $recent_category;
		}

		/**
		 * Function used to fetch the Terms & Taxonomies for the specific posts
		 *
		 * @param $id
		 * @param $type
		 * @param $optionalType
		 */
		public function getTermsAndTaxonomies($id, $type, $optionalType)
		{
			$TermsData = array();

			if ($type == 'WooCommerce' || ($type == 'CustomPosts' && $type == 'WooCommerce'))
			{
				$type = 'product';
				$postTags = '';
				$taxonomies = get_object_taxonomies($type);
				$get_tags = get_the_terms($id, 'product_tag');
				if ($get_tags)
				{
					foreach ($get_tags as $tags)
					{
						$postTags .= $tags->name . ',';
					}
				}
				$postTags = substr($postTags, 0, -1);
				$this->data[$id]['product_tag'] = $postTags;
				foreach ($taxonomies as $taxonomy)
				{
					$postCategory = '';
					if ($taxonomy == 'product_cat' || $taxonomy == 'product_category')
					{
						$get_categories = get_the_terms($id, $taxonomy);
						if ($get_categories)
						{
							$postCategory = $this->hierarchy_based_term_name($get_categories, $taxonomy);
							// foreach($get_categories as $category){
							// 	$postCategory .= $this->hierarchy_based_term_name($category, $taxonomy) . ',';
							// }

						}
						$postCategory = substr($postCategory, 0, -1);
						$this->data[$id]['product_category'] = $postCategory;
					}
					else
					{
						$get_categories = get_the_terms($id, $taxonomy);
						if ($get_categories)
						{
							$postCategory = $this->hierarchy_based_term_name($get_categories, $taxonomy);
							// foreach($get_categories as $category){
							// 	$postCategory .= $this->hierarchy_based_term_name($category, $taxonomy) . ',';
							// }

						}
						$postCategory = substr($postCategory, 0, -1);
						$this->data[$id][$taxonomy] = $postCategory;
					}
				}
				if ($type == 'WooCommerce' && $type != 'CustomPosts')
				{
					$product = wc_get_product($id);
					$pro_type = $product->get_type();
					switch ($pro_type)
					{
					case 'simple':
						$product_type = 1;
						break;
					case 'grouped':
						$product_type = 2;
						break;
					case 'external':
						$product_type = 3;
						break;
					case 'variable':
						$product_type = 4;
						break;
					case 'subscription':
						$product_type = 5;
						break;
					case 'variable-subscription':
						$product_type = 6;
						break;
					case 'bundle':
						$product_type = 7;
						break;
					default:
						$product_type = 1;
						break;
					}
					$this->data[$id]['product_type'] = $product_type;
				}

				//product_shipping_class
				$shipping = get_the_terms($id, 'product_shipping_class');
				if ($shipping)
				{
					$taxo_shipping = $shipping[0]->name;
					$this->data[$id]['product_shipping_class'] = $taxo_shipping;
				}
				//product_shipping_class

			}
			else if ($type == 'WPeCommerce')
			{
				$type = 'wpsc-product';
				$postTags = $postCategory = '';
				$taxonomies = get_object_taxonomies($type);
				$get_tags = get_the_terms($id, 'product_tag');
				if ($get_tags)
				{
					foreach ($get_tags as $tags)
					{
						$postTags .= $tags->name . ',';
					}
				}
				$postTags = substr($postTags, 0, -1);
				$this->data[$id]['product_tag'] = $postTags;
				foreach ($taxonomies as $taxonomy)
				{
					$postCategory = '';
					if ($taxonomy == 'wpsc_product_category')
					{
						$get_categories = wp_get_post_terms($id, $taxonomy);
						if ($get_categories)
						{
							$postCategory = $this->hierarchy_based_term_name($get_categories, $taxonomy);

						}
						$postCategory = substr($postCategory, 0, -1);
						$this->data[$id]['product_category'] = $postCategory;
					}
					else
					{
						$get_categories = wp_get_post_terms($id, $taxonomy);
						if ($get_categories)
						{
							$postCategory = $this->hierarchy_based_term_name($get_categories, $taxonomy);

						}
						$postCategory = substr($postCategory, 0, -1);
						$this->data[$id]['product_category'] = $postCategory;
					}
				}
			}
			else
			{
				global $wpdb;
				$postTags = $postCategory = '';
				// $taxonomyId = $wpdb->get_col($wpdb->prepare("select term_taxonomy_id from {$wpdb->prefix}term_relationships where object_id = %d", $id));
				$taxonomyId = $wpdb->get_col("SELECT term_taxonomy_id FROM {$wpdb->prefix}term_relationships WHERE object_id = {$id}");
				$taxo = [];
				$termTaxonomyIds = array();
				foreach ($taxonomyId as $taxonomyIds)
				{

					// $termTaxonomyId = $wpdb->get_results($wpdb->prepare("select term_id from {$wpdb->prefix}term_taxonomy where term_taxonomy_id = %d", $taxonomyIds));
					$termTaxonomyId = $wpdb->get_results("SELECT term_id FROM {$wpdb->prefix}term_taxonomy WHERE term_taxonomy_id = {$taxonomyIds}");
					foreach ($termTaxonomyId as $term)
					{
						$termTaxonomyIds[] = $term->term_id;
					}
				}
				foreach ($termTaxonomyIds as $taxonomy)
				{

					$taxo[] = get_term($taxonomy);
				}
				foreach ($taxonomyId as $taxonomy)
				{
					$taxonomytypeid = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}term_taxonomy WHERE term_taxonomy_id='$taxonomy' ");
					if ($taxonomytypeid[0]->taxonomy == 'course_category')
					{
						$taxonomyTypeId = $wpdb->get_col($wpdb->prepare("select term_id from {$wpdb->prefix}term_taxonomy where term_taxonomy_id = %d", $taxonomytypeid[0]->term_taxonomy_id));
						$taxonomy_Type_Id = $taxonomyTypeId[0];
						$taxo0[] = get_term($taxonomy_Type_Id);
					}
					if ($taxonomytypeid[0]->taxonomy == 'course_tag')
					{
						$taxonomyTypeId1 = $wpdb->get_col($wpdb->prepare("select term_id from {$wpdb->prefix}term_taxonomy where term_taxonomy_id = %d", $taxonomytypeid[0]->term_taxonomy_id));
						$taxonomy_Type_Id1 = $taxonomyTypeId1[0];
						$taxo2[] = get_term($taxonomy_Type_Id1);
					}
				}

				if (!empty($taxo))
				{
					foreach ($taxo as $key => $taxo_val)
					{
						if ($taxo_val->taxonomy == 'category')
						{
							$taxo1[] = $taxo_val;
						}
					}
				}

				if (!empty($taxonomyId))
				{
					foreach ($taxonomyId as $taxonomy)
					{
						$taxonomyType = $wpdb->get_col($wpdb->prepare("select taxonomy from {$wpdb->prefix}term_taxonomy where term_taxonomy_id = %d", $taxonomy));
						if (!empty($taxonomyType))
						{
							foreach ($taxonomyType as $taxanomy_name)
							{
								if ($taxanomy_name == 'category')
								{
									$termName = 'post_category';
								}
								else
								{
									$termName = $taxanomy_name;
								}
								if (in_array($termName, $this->headers))
								{
									if ($termName != 'post_tag' && $termName != 'post_category')
									{
										$postterm1 = $postterm2 = '';
										$taxonomyData = $wpdb->get_col($wpdb->prepare("select name from {$wpdb->prefix}terms where term_id = %d", $taxonomy));
										if (!empty($taxonomyData))
										{

											if (isset($TermsData[$termName]))
											{
												$this->data[$id][$termName] = $TermsData[$termName] . ',' . $taxonomyData[0];
											}
											else
											{
												$get_exist_data = isset($this->data[$id][$termName]) ? $this->data[$id][$termName] : '';
											}

											if ($get_exist_data == '')
											{
												$this->data[$id][$termName] = $taxonomyData[0];
											}
											else
											{
												$taxonomyID = $wpdb->get_col($wpdb->prepare("select term_id from {$wpdb->prefix}terms where name = %s", $taxonomyData[0]));
												if ($taxanomy_name == 'course_category')
												{
													foreach ($taxo0 as $taxo_key => $taxo_value)
													{
														$postterm1 .= $taxo_value->name . ',';
													}
													$this->data[$id][$termName] = rtrim($postterm1, ',');
												}
												elseif ($taxanomy_name == 'course_tag')
												{
													foreach ($taxo2 as $taxo_key1 => $taxo_value1)
													{
														$postterm2 .= $taxo_value1->name . ',';
													}
													$this->data[$id][$termName] = rtrim($postterm2, ',');
												}
												else
												{
													$postterm = substr($this->hierarchy_based_term_name($taxo, $taxanomy_name) , 0, -1);
													$this->data[$id][$termName] = $postterm;
												}
											}

										}
									}
									else
									{
										if (!isset($TermsData['post_tag']))
										{
											if ($termName == 'post_tag')
											{
												$postTags = '';
												$get_tags = wp_get_post_tags($id, array(
													'fields' => 'names'
												));
												foreach ($get_tags as $tags)
												{
													$postTags .= $tags . ',';
												}
												$postTags = substr($postTags, 0, -1);
												$this->data[$id][$termName] = $postTags;
											}
											if ($termName == 'post_category')
											{
												$postCategory = '';
												$get_categories = wp_get_post_categories($id, array(
													'fields' => 'names'
												));

												$postterm1 = substr($this->hierarchy_based_term_name($taxo1, $taxanomy_name) , 0, -1);
												$this->data[$id][$termName] = $postterm1;

											}

										}
									}
								}
								else
								{
									$this->data[$id][$termName] = '';
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Get user role based on the capability
		 * @param null $capability  - User capability
		 * @return int|string       - Role of the user
		 */
		public function getUserRole($capability = null)
		{
			if ($capability != null)
			{
				$getRole = unserialize($capability);
				foreach ($getRole as $roleName => $roleStatus)
				{
					$role = $roleName;
				}
				return $role;
			}
			else
			{
				return 'subscriber';
			}
		}

		/**
		 * Get activated plugins
		 * @return mixed
		 */
		public function get_active_plugins()
		{
			$active_plugins = get_option('active_plugins');
			return $active_plugins;
		}
		public function array_to_xml( $data, &$xml_data ) {
			foreach( $data as $key => $value ) {
				if( is_numeric($key) ){
					$key = 'item'; 
				}
				if (strpos($key, '::') !== false) {
					$key = str_replace('::', '_COLON_', $key);
					$key = str_replace(' ', '_', $key);
				}
				if( is_array($value) ) {
					$subnode = $xml_data->addChild($key);
					$this->array_to_xml($value, $subnode);
				} else {
					$xml_data->addChild("$key",htmlspecialchars("$value"));
				}
			}
		}
		public function getPolylangData($id, $optional_type, $exp_module=null)
		{
			global $wpdb;
			global $sitepress;
			$post_title = '';
			if($exp_module == 'Categories' || $exp_module == 'Tags' || $exp_module == 'Taxonomies'){
                $terms = $wpdb->get_results("select term_taxonomy_id from {$wpdb->prefix}term_taxonomy where description like '%$id%'",ARRAY_A);
                $terms_id = json_decode(json_encode($terms) , true);
            }
            else{
				$terms = $wpdb->get_results("select term_taxonomy_id from $wpdb->term_relationships where object_id ='{$id}' order by term_taxonomy_id desc");
                $terms_id = json_decode(json_encode($terms) , true);
			}
			if(is_plugin_active('polylang-pro/polylang.php')){
				if($exp_module == 'Categories' || $exp_module == 'Tags' || $exp_module == 'Taxonomies'){
					$get_language = pll_get_term_language($id);
					$get_translation = pll_get_term_translations($id);
					unset($get_translation[$get_language]);
					$this->data[$id]['language_code'] = $get_language;
					foreach($get_translation as $trans_key => $trans_val){
						$title = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms where term_id=$trans_val");
						$post_title .= $title.',';
					}
					$this->data[$id]['translated_taxonomy_title'] = rtrim($post_title,',');
				}
				else{
					$get_language=pll_get_post_language( $id );
					$get_translation=pll_get_post_translations($id);
					unset($get_translation[$get_language]);
					$this->data[$id]['language_code'] = $get_language;
					foreach($get_translation as $trans_key => $trans_val){
						$title = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts where id=$trans_val");
						$post_title .= $title.',';
					}
					$this->data[$id]['translated_post_title'] = rtrim($post_title,',');
				}
			}
			else{
				foreach ($terms_id as $termkey => $termvalue)
				{
					$post_title = '';
					$termids = $termvalue['term_taxonomy_id'];
					$check = $wpdb->get_var("select taxonomy from $wpdb->term_taxonomy where term_taxonomy_id ='{$termids}'");
					if ($check == 'category')
					{
						$category = $wpdb->get_var("select name from $wpdb->terms where term_id ='{$termids}'");
					}
					elseif ($check == 'language')
					{
						$language = $wpdb->get_var("select description from $wpdb->term_taxonomy where term_id ='{$termids}'");
						$lang = unserialize($language);
						$langcode = explode('_', $lang['locale']);
						$lang_code = $langcode[0];
						$this->data[$id]['language_code'] = $lang_code;

					}

					elseif($check == 'term_language'){
						if($exp_module == 'Categories' || $exp_module == 'Tags' || $exp_module == 'Taxonomies'){
							$language = $wpdb->get_var("select description from $wpdb->term_taxonomy where term_taxonomy_id ='{$termids}'");
							$lang = unserialize($language);
							$langcode = explode('_', $lang['locale']);
							$lang_code = $langcode[0];
							if(empty($this->data[$id]['language_code'])){
								$this->data[$id]['language_code'] = $lang_code;
							}

						}
					}
					elseif(($exp_module == 'Categories' || $exp_module == 'Tags' || $exp_module == 'Taxonomies') && $check == 'term_translations'){
						$description = $wpdb->get_var("select description from $wpdb->term_taxonomy where term_taxonomy_id ='{$termids}'");	
						$desc = unserialize($description);
						//$post_id = is_array($desc) ? array_values($desc) : array();
						$post_id = is_array($desc) ? $desc : array();

						// $postid = min($post_id);
						foreach($post_id as $post_key => $post_value){
							if($id == $post_value){
								$this->data[$id]['language_code'] = $post_key;
								unset($post_id[$post_key]);
							}
						}

						foreach($post_id as $trans_key => $trans_val){
							$title = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms where term_id=$trans_val");
							$post_title .= $title.',';
						}

						$this->data[$id]['translated_taxonomy_title'] = rtrim($post_title,',');
					}
					elseif (($exp_module !== 'Categories' && $exp_module !== 'Tags') &&  $check == 'post_translations')
					{
						$description = $wpdb->get_var("select description from $wpdb->term_taxonomy where term_id ='{$termids}'");
						$desc = unserialize($description);
						$post_id = is_array($desc) ? array_values($desc) : array();
						// $postid = min($post_id);
						foreach($post_id as $post_key => $post_value){
							if($id == $post_value){
								unset($post_id[$post_key]);
							}
						}
						foreach($post_id as $trans_key => $trans_val){
							$post_title = $wpdb->get_var("select post_title from $wpdb->posts where ID ='{$trans_val}'");
							$this->data[$id]['translated_post_title'] = $post_title;
						}
					}
					elseif ($check == 'post_tag')
					{
						$tag = $wpdb->get_var("select name from $wpdb->terms where term_id ='{$termids}'");

					}
				}
			}
		}

		public function getGeoPlaceData ($id,$optional_type,$exp_module) {

			$post_info = geodir_get_post_info($id);
			foreach ($post_info as $gdKey => $gdVal){				
				if(!empty($gdVal)){
					$this->data[$id][ $gdKey ] = $gdVal ;
				}
			} 											
		}

		public  function getPostTypes(){
			$custom_array = array('post', 'page', 'wpsc-product', 'product_variation', 'shop_order', 'shop_coupon', 'shop_order_refund','mp_product_variation');
			$other_posttypes = array('attachment','revision','wpsc-product-file','mp_order','shop_webhook','custom_css','customize_changeset','oembed_cache','user_request','_pods_template','wpmem_product','wp-types-group','wp-types-user-group','wp-types-term-group','gal_display_source','display_type','displayed_gallery','wpsc_log','lightbox_library','scheduled-action','cfs','_pods_pod','_pods_field','acf-field','acf-field-group','wp_block','ngg_album','ngg_gallery','nf_sub','wpcf7_contact_form','iv_payment','llms_quiz','llms_question','llms_membership','llms_engagement','llms_order','llms_transaction','llms_achievement','llms_my_achievement','llms_my_certificate','llms_email','llms_voucher','llms_access_plan','llms_form','section','llms_certificate');
			$importas = array(
				'Posts' => 'Posts',
				'Pages' => 'Pages',
				'Users' =>'Users',
				'Comments' => 'Comments',
	
			);
			$all_post_types = get_post_types();
			array_push($all_post_types, 'widgets');
			// To avoid toolset repeater group fields from post types in dropdown
			global $wpdb;
			$fields = $wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key = '_wp_types_group_fields' ");
			foreach($fields as $value){
				$repeat_values = $value->meta_value;
				$types_fields = explode( ',', $repeat_values);
	
				foreach($types_fields as $types_value){
					$explode = explode('_',$types_value);
					if (count($explode)>1) {
						if (in_array('repeatable',$explode)) {
							$name = $wpdb->get_results("SELECT post_name FROM ".$wpdb->prefix."posts WHERE id ='{$explode[3]}'");	
							$type_repeat_value =  $name[0]->post_name;
	
							if(in_array($type_repeat_value , $all_post_types)){
								unset($all_post_types[$type_repeat_value]);
							}
						}else{
	
						}
					}else{
	
					}
				}	
			}
	
			foreach($other_posttypes as $ptkey => $ptvalue) {
				if (in_array($ptvalue, $all_post_types)) {
					unset($all_post_types[$ptvalue]);
				}
			}
			foreach($all_post_types as $key => $value) {
				if(!in_array($value, $custom_array)) {
					if(is_plugin_active('events-manager/events-manager.php') && $value == 'event') {
						$importas['Events'] = $value;
					} elseif(is_plugin_active('events-manager/events-manager.php') && $value == 'event-recurring') {
						$importas['Recurring Events'] = $value;
					} elseif(is_plugin_active('events-manager/events-manager.php') && $value == 'location') {
						$importas['Event Locations'] = $value;
					} else {
						$importas[$value] = $value;
					}
					$custompost[$value] = $value;
				}
			}
			//Ticket import
			if(is_plugin_active('events-manager/events-manager.php')){
				$importas['Tickets'] = 'ticket';
			}
			if(is_plugin_active('wp-customer-reviews/wp-customer-reviews-3.php') || is_plugin_active('wp-customer-reviews/wp-customer-reviews.php') ){
				$importas['Customer Reviews'] = 'CustomerReviews';
				if(isset($importas['wpcr3_review'])) {
					unset($importas['wpcr3_review']);
				}
			}
	
		 // Add JetReviews if the JetReviews plugin is active
			 if(is_plugin_active('jet-reviews/jet-reviews.php')) {
			$importas['JetReviews'] = 'jetreviews';
			}
			if(is_plugin_active('woocommerce/woocommerce.php')){
				$importas['WooCommerce Product'] ='WooCommerce';
			//	$importas['WooCommerce Product Variations'] ='WooCommerceVariations';
				$importas['WooCommerce Orders'] = 'WooCommerceOrders';
				$importas['WooCommerce Customer'] = 'WooCommerceCustomer';
				$importas['WooCommerce Reviews'] ='WooCommerceReviews';
				$importas['WooCommerce Coupons'] = 'WooCommerceCoupons';
				$importas['WooCommerce Refunds'] = 'WooCommerceRefunds';
				unset($importas['product']);
			}
			if(is_plugin_active('wp-e-commerce/wp-shopping-cart.php')){
				$importas['WPeCommerce Products'] ='WPeCommerce';
				$importas['WPeCommerce Coupons'] = 'WPeCommerceCoupons';
			}
			if(is_plugin_active('gravityforms/gravityforms.php')){
				$importas['GFEntries'] ='GFEntries';
				
			}
			if(is_plugin_active('jet-engine/jet-engine.php')){
				$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
			
				if(!empty($get_slug_name)){
					foreach($get_slug_name as $key => $get_slug){
						$value = $get_slug->slug;
						$importas[$value] = $value;
					}
				}
			}
			if(is_plugin_active('jet-booking/jet-booking.php')){
				$importas['JetBooking'] ='JetBooking';
			}
			return $importas;
			
		}

		public function getTaxonomies(){
			$i = 0;
			foreach (get_taxonomies() as $key => $value) {
					$response['taxonomies'][$i] = $value;
					$i++;
			}
			return $response;
			
		}

		/**
		 * Export Data
		 * @param $data
		 */
		public function proceedExport($data)
		{
//need this revert again
			 if (is_user_logged_in() && current_user_can('administrator'))
			 {
				$upload_dir = ABSPATH . 'wp-content/uploads/smack_uci_uploads/exports/';
				$index_php_file = $upload_dir . 'index.php';
				
				if (!file_exists($index_php_file)) {
					$file_content = '<?php' . PHP_EOL . '?>';
					file_put_contents($index_php_file, $file_content);
				}

				if (empty($this->random_data))
				{
					$random_folder = wp_generate_password(16, false); // 16-character random folder name
					$upload_dir = $upload_dir  . $random_folder . '/';
				}
				else{
					$random_folder = $this->random_data;
					$upload_dir = $upload_dir . $random_folder . '/';				}
				if (!is_dir($upload_dir))
				{
					wp_mkdir_p($upload_dir);
				}
				$base_dir = wp_upload_dir();
				$upload_url = $base_dir['baseurl'] . '/smack_uci_uploads/exports/'.$random_folder . '/';
				chmod($upload_dir, 0777);
			}

			if ($this->checkSplit == 'true')
			{
				$i = 1;
				while ($i != 0)
				{
					$file = $upload_dir . $this->fileName . '_' . $i . '.' . $this->exportType;
					if (file_exists($file))
					{
						$allfiles[$i] = $file;
						$i++;
					}
					else break;
				}
				$fileURL = $upload_url . $this->fileName . '_' . $i . '.' . $this->exportType;
			}
			else
			{
				$file = $upload_dir . $this->fileName . '.' . $this->exportType;
				$fileURL = $upload_url . $this->fileName . '.' . $this->exportType;
			}

			$spsize = 100;
			if ($this->offset == 0)
			{
				if (file_exists($file)) unlink($file);
			}

			$checkRun = "no";
			if ($this->checkSplit == 'true' && ($this->totalRowCount - $this->offset) > 0)
			{
				$checkRun = 'yes';
			}
			if ($this->checkSplit != 'true')
			{
				$checkRun = 'yes';
			}

			if ($checkRun == 'yes')
			{
				$this->isPreview = $_POST['isPreview'];
				if ($this->exportType == 'xml')
				{
					$xml_data = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');
					

				//	Check if preview is true
					if ($this->isPreview === 'true' ) {		
				
						$limitedData = array_slice($data, 0, 10);
						$this->array_to_xml($limitedData, $xml_data);	
						$dom = new \DOMDocument('1.0', 'UTF-8');
						$dom->preserveWhiteSpace = false;
						$dom->formatOutput = true;
						$dom->loadXML($xml_data->asXML());

						echo $dom->saveXML();
						wp_die();
					}
					else{
						$this->array_to_xml($data, $xml_data);
						$result = $xml_data->asXML($file);
					}
				}
				elseif($this->exportType == 'tsv'){
					$files = fopen($file, "w");
					$headers = array_keys(reset($data)); // Get the keys from the first post
					fputcsv($files, $headers, "\t"); 
					
					foreach ($data as $row) {
						fputcsv($files, $row, "\t"); // Use tab as delimiter
					}

					if ($this->isPreview === 'true' ) {
						$privewJson = array_slice($data, 0, 10);
						$jsonData = json_encode($privewJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
				
						$data = json_decode($jsonData, true);

						// Get headers from the first item
						$headers = array_keys($data[0]);

						// Slice first 10 items (or less if not available)
						$first10 = array_slice($data, 0, 10);

						// Convert each item to a row of values
						$rows = array_map(function($item) use ($headers) {
							return array_map(fn($key) => $item[$key], $headers);
						}, $first10);

						// Final result: headers + first 10 rows
						$result = array_merge([$headers], $rows);

						// Preview as JSON
						header('Content-Type: application/json');
						echo json_encode($result, JSON_PRETTY_PRINT);
						wp_die();
					}
					
				}
				else
				{
					if ($this->exportType == 'json')

					{
						$csvData = json_encode($data);
	
						if ($this->isPreview === 'true' ) {
							
							$privewJson = array_slice($data, 0, 10);
							header('Content-Type: application/json; charset=utf-8');
							echo json_encode($privewJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
							wp_die();
	}
						}
					else {
					$csvData = $this->unParse($data, $this->headers);
				
					// Check if migration is true
					if ($this->isPreview === 'true' ) {
						$privewJson = array_slice($data, 0, 10);
						$jsonData = json_encode($privewJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
				
						$data = json_decode($jsonData, true);

						// Get headers from the first item
						$headers = array_keys($data[0]);

						// Slice first 10 items (or less if not available)
						$first10 = array_slice($data, 0, 10);

						// Convert each item to a row of values
						$rows = array_map(function($item) use ($headers) {
							return array_map(fn($key) => $item[$key], $headers);
						}, $first10);

						// Final result: headers + first 10 rows
						$result = array_merge([$headers], $rows);

						// Preview as JSON
						header('Content-Type: application/json');
						echo json_encode($result, JSON_PRETTY_PRINT);
						wp_die();
					}
					}
					try
					{

						file_put_contents($file, $csvData, FILE_APPEND | LOCK_EX);
						
				}
				catch(\Exception $e)
				{
					// TODO - write exception in log

				}
				}
				
				}

				$this->offset = $this->offset + $this->limit;

				$filePath = $upload_dir . $this->fileName . '.' . $this->exportType;
				$filename = $fileURL;
				if (($this->offset) > ($this->totalRowCount) && $this->checkSplit == 'true')
				{
					$allfiles[$i] = $file;
					$zipname = $upload_dir . $this->fileName . '.' . 'zip';
					$zip = new \ZipArchive;
					$zip->open($zipname, \ZipArchive::CREATE);
					foreach ($allfiles as $allfile)
					{
						$newname = str_replace($upload_dir, '', $allfile);
						$zip->addFile($allfile, $newname);
				}
				$zip->close();
				$fileURL = $upload_url . $this->fileName . '.' . 'zip';
				foreach ($allfiles as $removefile)
				{
					unlink($removefile);
				}
				$filename = $upload_url . $this->fileName . '.' . 'zip';
				}

					// Define the export file (CSV or other type)
					$file = $upload_dir . $this->fileName . '.' . $this->exportType;
					$allfiles[] = $file;
				$this->isMigration = $_POST['isMigrate'];
					// Check if migration is true
					if ($this->isMigration === 'true' && (($this->offset) > ($this->totalRowCount))) {
						
						$module = $this->module;
						// Create JSON file
						$headers = self::generateHeaders($this->module, $this->optionalType);
						if ($module == 'CustomPosts' || $module == 'Taxonomies' || $module == 'Categories' || $module == 'Tags')
						{
							if(is_plugin_active('events-manager/events-manager.php') &&$optionalType == 'event'){
								$optionalType = 'Events';
							}
							$default = $this->get_fields($optionalType); // Call the super class function
			
						}
						else
						{
							$default = $this->get_fields($module);
						}

						
						function transformFieldsArray($fieldsArray) {
							$csv_fields = [];
							$fields = [];
						
							foreach ($fieldsArray as $fieldGroup) {
								$groupKey = key($fieldGroup); // Get the group name (e.g., "core_fields", "terms_and_taxonomies")
								$groupFields = [];
						
								foreach ($fieldGroup[$groupKey] as $field) {
									$groupFields[] = [
										'label' => $field['label'],
										'name' => $field['name']
									];
									$csv_fields[] = $field['name']; // Collect all field names for CSV
								}
						
								$fields[] = [$groupKey => $groupFields];
							}
						
							return [
								'csv_fields' => array_values(array_unique($csv_fields)), // Ensure unique field names
								'fields' => $fields
							];
						}
						
					
						$fieldsArray = $default['fields'];
						$headers =  transformFieldsArray($fieldsArray);


						$jsonFile = $upload_dir . $this->fileName . '.json';
						
						if($this->exportType == 'json'){
							$jsonFile = $upload_dir . $this->fileName .'config'. '.json';
						}
						else{
						    $jsonFile = $upload_dir . $this->fileName . '.json';
						}

						$postTypes = $this->getPostTypes();
					
						$import_record_post = array_keys($postTypes);
				// 	if(is_plugin_active('woocommerce/woocommerce.php')){
				// 		$importas = [
				// 			'WooCommerce' => 'WooCommerce Product' ,
				// 		   //'WooCommerce Product Variations' , 'WooCommerceVariations',
				// 			'WooCommerceOrders' => 'WooCommerce Orders' ,
				// 			'WooCommerceCustomer' => 'WooCommerce Customer' , 
				// 			'WooCommerceReviews' => 'WooCommerce Reviews' ,
				// 			'WooCommerceCoupons' => 'WooCommerce Coupons' ,
				// 			'WooCommerceRefunds' => 'WooCommerce Refunds' ,
				// 	   ];
						
				// 	   if (isset($importas[$this->module])) {		
				// 			$this->module = $importas[$this->module];
				// 	   }
						
				// }
					
					
					

					
						$taxonomies = $this->getTaxonomies();
						$jsonData = [
							'file_name' => $this->fileName,
							'total_rows' => $this->totalRowCount,
							'selectedtype' => $this->module,
							'optionalType' => $this->optionalType,
							'headers' => $headers,
							'export_time' => date('Y-m-d H:i:s'),
							'status' => 'completed',
							'posttype' => $import_record_post,
							'taxonomy' => $taxonomies['taxonomies'],
							'currentuser' => 'administrator',
							'get_key' => false,
							'show_template' => false,
							'file_iteration' => 5,
							'MediaType' => 'Local',
							'update_fields' => ['ID', 'post_title', 'post_name'],
							'use_ExistingImage' => true,
							'media_handle_option' => true,
							'postContent_image_option' => false,
							'highspeed' => false,
							'mappingFilterCheck' => false
						];
						
						file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT));
				
						// Add JSON file to the list of files to zip
						$allfiles[] = $jsonFile;
				
						// Create ZIP file (smbundle_ prefix)
						$zipname = $upload_dir . 'smbundle_' . $this->fileName . '.zip';
						$zip = new \ZipArchive;
				
						if ($zip->open($zipname, \ZipArchive::CREATE) === true) {
							foreach ($allfiles as $allfile) {
								// Ensure only CSV, JSON, or related export files are included
								if (preg_match('/\.(csv|json|xml|xls|xlsx|tsv' . preg_quote($this->exportType, '/') . ')$/', $allfile)) {
									$newname = str_replace($upload_dir, '', $allfile);
									$zip->addFile($allfile, $newname);
								}
							}
							$zip->close();
						}
				
						$zipURL = $upload_url  . 'smbundle_' . $this->fileName . '.zip';
				
						// Remove original files after zipping
						foreach ($allfiles as $removefile) {
							//unlink($removefile);
						}
				
						$filename = $upload_dir  . 'smbundle_' . $this->fileName . '.zip';

						$responseTojQuery = array(
							'success' => true,
							'new_offset' => $this->offset,
							'limit' => $this->limit,
							'total_row_count' => $this->totalRowCount,
							'exported_file' => $fileURL ,
							'zip_file' => $zipURL,
							'exported_path' => $filename,
							'export_type' => $this->exportType	
						);
						echo wp_json_encode($responseTojQuery);
					wp_die();
					}
				//}
				
				if ($this->checkSplit == 'true' && !($this->offset) > ($this->totalRowCount))
				{
					$responseTojQuery = array(
						'success' => false,
						'new_offset' => $this->offset,
						'limit' => $this->limit,
						'total_row_count' => $this->totalRowCount,
						'exported_file' => $zipname,
						'exported_path' => $zipname,
						'export_type' => $this->exportType
					);
				}
				elseif ($this->checkSplit == 'true' && (($this->offset) > ($this->totalRowCount)))
				{
					if ($this->exportType == 'xls' || $this->exportType == 'xlsx') {
						// Convert CSV to XLS or XLSX depending on the export type
						$newpath = str_replace('.csv', '.' . $this->exportType, $filePath);
						$newfilename = str_replace('.csv', '.' . $this->exportType, $fileURL);
						if($this->exportType == 'xlsx'){
							$reader = IOFactory::createReader('Xlsx');
						}else{
							$reader = IOFactory::createReader('Xls');
						}
						if (file_exists($filePath)) {
							$objPHPExcel = $reader->load($filePath);
							$spreadsheet = new Spreadsheet(); // Create new Spreadsheet object
							if($this->exportType == 'xlsx'){
								$objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
							}else{
								$objWriter = IOFactory::createWriter($spreadsheet, 'Xls');
							}
							$objWriter->save($newpath);
						}
				
						// Response after conversion
						$responseTojQuery = array(
							'success' => true,
							'new_offset' => $this->offset,
							'limit' => $this->limit,
							'total_row_count' => $this->totalRowCount,
							'exported_file' => $newfilename,
							'exported_path' => $newpath,
							'export_type' => $this->exportType
						);
					}				
					else {
						// If export type is neither XLS nor XLSX, return original file
						$responseTojQuery = array(
							'success' => true,
							'new_offset' => $this->offset,
							'limit' => $this->limit,
							'total_row_count' => $this->totalRowCount,
							'exported_file' => $fileURL,
							'exported_path' => $fileURL,
							'export_type' => $this->exportType
						);
					}
				}
				elseif (!(($this->offset) > ($this->totalRowCount)))
				{
					$responseTojQuery = array(
						'success' => false,
						'new_offset' => $this->offset,
						'limit' => $this->limit,
						'total_row_count' => $this->totalRowCount,
						// 'exported_file' => $random_folder.'/'.$filename,
						// 'exported_path' => $random_folder.'/'.$filePath,
						'exported_file' => $filename,
						'exported_path' => $filePath,
						'export_type' => $this->exportType,
						'random_data' => $random_folder
					);
				}
				else
				{
					// General case where we perform export
					if ($this->exportType == 'xls' || $this->exportType == 'xlsx') {
						// Convert CSV to XLS or XLSX depending on the export type
						$newpath = str_replace('.csv', '.' . $this->exportType, $filePath);
						$newfilename = str_replace('.csv', '.' . $this->exportType, $fileURL);
				
						// Load the CSV and save as XLS or XLSX based on export type
						$reader = IOFactory::createReader('Csv');
						$objPHPExcel = $reader->load($filePath);
						if ($this->exportType == 'xls') {
							// If export type is XLS, save it as XLS
							$objWriter = IOFactory::createWriter($objPHPExcel, 'Xls');
						} else {
							// If export type is XLSX, save it as XLSX
							$objWriter = IOFactory::createWriter($objPHPExcel, 'Xlsx');
						}
						$objWriter->save($newpath);
						
						$responseTojQuery = array(
							'success' => true,
							'new_offset' => $this->offset,
							'limit' => $this->limit,
							'total_row_count' => $this->totalRowCount,
							'exported_file' => $newfilename,
							'exported_path' => $newpath,
							'export_type' => $this->exportType
						);
					} else {
						// If export type is neither XLS nor XLSX, return original file
						$responseTojQuery = array(
							'success' => true,
							'new_offset' => $this->offset,
							'limit' => $this->limit,
							'total_row_count' => $this->totalRowCount,
							'exported_file' => $filename,
							'exported_path' => $filePath,
							'export_type' => $this->exportType
						);
					}
				}
				// $responseTojQuery["file_path"]=WP_PLUGIN_DIR . '/wp-ultimate-exporter/download.php';
				if ($this->export_mode == 'normal')
				{
					echo wp_json_encode($responseTojQuery);
					wp_die();
				}
				elseif ($this->export_mode == 'FTP')
				{
					$this->export_log = $responseTojQuery;
				}
				}

				/**
				 * Fetch ACF field information to be export
				 * @param $recordId - Id of the Post (or) Page (or) Product (or) User
				 */
				public function FetchACFData($recordId)
				{

				}

				/**
				 * Get post data based on the record id
				 * @param $id       - Id of the records
				 * @return array    - Data based on the requested id.
				 */
				public function getPostsDataBasedOnRecordId($id,$module = null)
				{
					global $wpdb;
					$PostData = array();
					$query1 = $wpdb->prepare("SELECT wp.* FROM {$wpdb->prefix}posts wp where ID=%d", $id);
					$result_query1 = $wpdb->get_results($query1);
					if (!empty($result_query1))
					{
						foreach ($result_query1 as $posts)
						{
							if($posts->post_type =='event' ||$posts->post_type =='event-recurring'){

								$loc=get_post_meta($id , '_location_id' , true);
								$event_id=get_post_meta($id , '_event_id' , true);
								$res = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}em_locations WHERE location_id='$loc' "); 

								if($res){
									foreach($res as $location){
										unset($location-> post_content);	
										$posts=array_merge((array)$posts,(array)$location);
									}
								}

								$ticket = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}em_tickets WHERE event_id='$event_id' "); 

								$ticket[0]=isset($ticket[0])?$ticket[0]:'';
								$ticket_meta= $ticket[0];
								if(isset($ticket_meta->{'ticket_meta'})){
									$ticket_meta_value=$ticket_meta->{'ticket_meta'};
								}
								$ticket_meta_value=isset($ticket_meta_value)?$ticket_meta_value:'';
								$ticket_value=unserialize($ticket_meta_value);
								if(isset($ticket_id)){
									$ticket_values = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}em_tickets WHERE ticket_id='$ticket_id' ");
								}
								$count=count($ticket);
								if($count>1){
									$ticknamevalue = '';
									$tickidvalue = '';
									$eventidvalue = '';
									$tickdescvalue = '';
									$tickpricevalue = '';
									$tickstartvalue = '';
									$tickendvalue = '';
									$tickminvalue = '';
									$tickmaxvalue = '';
									$tickspacevalue = '';
									$tickmemvalue = '';
									$tickmemrolevalue = '';									
									$tickguestvalue = '';
									$tickreqvalue = '';
									$tickparvalue = '';
									$tickordervalue = '';
									$tickmetavalue = '';
									$tickstartdays = '';
									$tickenddays = '';
									$tickstarttime = '';
									$tickendtime = '';
									$t=0;

									foreach($ticket as $tic => $ticval){
										$ticknamevalue .= $ticval->ticket_name . ', ';
										$tickidvalue .=$ticval->ticket_id . ', ';
										$eventidvalue .=$ticval->event_id . ', ';
										$tickdescvalue .=$ticval->ticket_description . ', ';
										$tickpricevalue .=$ticval->ticket_price . ', ';
										$tickstartvalue .=$ticval->ticket_start . ', ';
										$tickendvalue .=$ticval->ticket_end . ', ';
										$tickminvalue .=$ticval->ticket_min . ', ';
										$tickmaxvalue .=$ticval->ticket_max . ', ';
										$tickspacevalue .=$ticval->ticket_spaces . ', ';
										$tickmemvalue .=$ticval->ticket_members . ', ';
										$tickmemroles =unserialize($ticval->ticket_members_roles);
										$tickmemroleval=implode('| ',(array)$tickmemroles);
										$tickmemrolevalue .=$tickmemroleval . ', ';


										$tickguestvalue .=$ticval->ticket_guests . ', ';
										$tickreqvalue .=$ticval->ticket_required . ', ';
										$tickparvalue .=$ticval->ticket_parent . ', ';
										$tickordervalue .=$ticval->ticket_order . ', ';
										$tickmetavalue .=$ticval->ticket_meta . ', ';
										$ticket[$t]=isset($ticket[$t])?$ticket[$t]:'';
										$ticket_meta= $ticket[$t];
										if(isset($ticket_meta->{'ticket_meta'})){
											$ticket_meta_value=$ticket_meta->{'ticket_meta'};
										}
										$ticket_meta_value=isset($ticket_meta_value)?$ticket_meta_value:'';
										if(!empty($ticket_meta_value)){
											$ticket_value=unserialize($ticket_meta_value);
										}

										foreach($ticket_value as $tickval => $val){
											$tickstartdays .= $val['start_days'].', ';
											$tickenddays .= $val['end_days'].', ';
											$tickstarttime .= $val['start_time'].', ';
											$tickendtime .= $val['end_time'].', ';
										}

										$ticknamevalues = rtrim($ticknamevalue, ', ');
										$tickidvalues = rtrim($tickidvalue, ', ');
										$eventidvalues=rtrim($eventidvalue, ', ');
										$tickdescvalues=rtrim($tickdescvalue, ', ');
										$tickpricevalues =rtrim($tickpricevalue, ', ');
										$tickstartvalues   =rtrim($tickstartvalue, ', ');
										$tickendvalues   =rtrim($tickendvalue, ', ');
										$tickminvalues   =rtrim($tickminvalue, ', ');
										$tickmaxvalues =rtrim($tickmaxvalue, ', ');
										$tickspacevalues =rtrim($tickspacevalue, ', ');	
										$tickmemvalues	=rtrim($tickmemvalue, ', ');
										$tickmemrolevalues	=rtrim($tickmemrolevalue, ', ');
										$tickguestvalues	=rtrim($tickguestvalue, ', ');
										$tickreqvalues	=rtrim($tickreqvalue, ', ');
										$tickparvalues	=rtrim($tickparvalue, ', ');
										$tickordervalues	=rtrim($tickordervalue, ', ');	
										$tickmetavalues	=rtrim($tickmetavalue, ', ');	
										$tickstartdaysvalues = rtrim($tickstartdays, ', ');
										$tickenddaysvalues = rtrim($tickenddays, ', ');
										$tickstarttimevalues = rtrim($tickstarttime, ', ');
										$tickendtimevalues = rtrim($tickendtime, ', ');	


										$tic_key1 = array('ticket_id', 'event_id', 'ticket_name','ticket_description','ticket_price','ticket_start','ticket_end','ticket_min','ticket_max','ticket_spaces','ticket_members','ticket_members_roles','ticket_guests','ticket_required','ticket_parent','ticket_order','ticket_meta','start_days','end_days','start_time','end_time');
										$tic_val1 = array($tickidvalues,$eventidvalues, $ticknamevalues,$tickdescvalues,$tickpricevalues,$tickstartvalues,$tickendvalues,$tickminvalues,$tickmaxvalues,$tickspacevalues,$tickmemvalues,$tickmemrolevalues,$tickguestvalues,$tickreqvalues,$tickparvalues,$tickordervalues,$tickmetavalues,$tickstartdaysvalues,$tickenddaysvalues,$tickstarttimevalues,$tickendtimevalues);

										$tickets1 = array_combine($tic_key1,$tic_val1);
										$posts=array_merge((array)$posts,(array)$tickets1);
										$ticket_start[] = $ticval->ticket_start;

										$ticket_start_date = '';
										$ticket_start_time ='';
										foreach(  $ticket_start as $loc =>$locval){
											$date = strtotime($locval);
											$ticket_start_date .= date('Y-m-d', $date) . ', ';

											$ticket_start_time .= date('H:i:s',$date) .', ';	


										}
										$ticket_start_times = rtrim($ticket_start_time, ', ');
										$ticket_start_dates = rtrim($ticket_start_date, ', ');
										$ticket_end[] = trim($ticval->ticket_end);
										$ticket_end_time = '';
										$ticket_end_date = '';
										foreach($ticket_end as $loc => $locvalend){											
											if(isset($locvalend) && !empty($locvalend)){
												$time = strtotime($locvalend);
												$ticket_end_date .= date('Y-m-d', $time) .', ';
												$ticket_end_time .= date('H:i:s',$time) .', ';
											}

										}	
										if(isset($ticket_start_date) && !empty($ticket_start_date)){   
											$ticket_end_times = rtrim($ticket_end_time, ', ');
											$ticket_end_dates = rtrim($ticket_end_date, ', ');
											$tic_key = array('ticket_start_date', 'ticket_start_time', 'ticket_end_date','ticket_end_time');
											$tic_val = array($ticket_start_dates,$ticket_start_times, $ticket_end_dates,$ticket_end_times);
											$tickets = array_combine($tic_key,$tic_val);
											$posts=array_merge((array)$posts,(array)$tickets);
										}

									}

								}
								else{
									foreach($ticket as $tic => $ticval){
										$posts=array_merge((array)$posts,(array)$ticval);
										if(isset($ticval->ticket_start)){
											$ticket_start=$ticval->ticket_start;
										}
										if(is_array($ticket_value)){
											foreach($ticket_value as $tick => $val){
												$posts=array_merge((array)$posts,(array)$val);
											}
										}										
										if(isset($ticket_start) && ($ticket_start != null)){
											$date = strtotime($ticket_start);																						
											$ticket_start_date = date('Y-m-d', $date);
											$ticket_start_time= date('H:i:s',$date);
											$ticket_end=$ticval->ticket_end;
											$time = strtotime($ticket_end);
											$ticket_end_date = date('Y-m-d', $time);
											$ticket_end_time= date('H:i:s',$time);
											$tic_key = array('ticket_start_date', 'ticket_start_time', 'ticket_end_date','ticket_end_time');
											$tic_val = array($ticket_start_date,$ticket_start_time, $ticket_end_date,$ticket_end_time);
											$tickets = array_combine($tic_key,$tic_val);
											$posts=array_merge((array)$posts,(array)$tickets);

										}
									}
								}

							}

							//pods export
							$post_type = isset($posts->post_type) ? $posts->post_type : '';
							$p_type = $post_type;
							$posid = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts  where post_name='$p_type' and post_type='_pods_pod'");
							foreach ($posid as $podid)
							{
								$pods_id = $podid->ID;
								$storage = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}postmeta  where post_id=$pods_id AND meta_key='storage'");
								foreach ($storage as $pod_storage)
								{
									$pod_stype = $pod_storage->meta_value;
								}
							}
							if (isset($pod_stype) && $pod_stype == 'table')
							{
								$tab = 'pods_' . $p_type;
								$tab_val = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}$tab where id=$id");
								foreach ($tab_val as $table_key => $table_val)
								{
									$posts = array_merge((array)$posts, (array)$table_val);
								}
							}

							foreach ($posts as $post_key => $post_value)
							{
								if ($post_key == 'post_status')
								{
									if (is_sticky($id))
									{
										$PostData[$post_key] = 'Sticky';
										$post_status = 'Sticky';
									}
									else
									{
										$PostData[$post_key] = $post_value;
										$post_status = $post_value;
									}
								}
								else
								{
									$PostData[$post_key] = $post_value;
								}
								if ($post_key == 'post_password')
								{
									if ($post_value)
									{
										$PostData['post_status'] = "{" . $post_value . "}";
									}
									else
									{
										$PostData['post_status'] = $post_status;
									}
								}

								if ($post_key == 'post_author')
								{
									$user_info = get_userdata($post_value);
									$PostData['post_author'] = $user_info->user_login;
								}
							}
						}
					}

					return $PostData;
				}

				public function getWPMLData($id, $optional_type, $exp_module)
				{
					global $wpdb;
					global $sitepress;
					if($sitepress != null) {
						$icl_translation_table = $wpdb->prefix.'icl_translations';

						$get_element_type = 'post_'.$optional_type;
						$args = array('element_id' => $id ,'element_type' => $get_element_type);
						$get_language_code = apply_filters( 'wpml_element_language_code', null, $args );
						$get_source_language = $wpdb->get_var("select source_language_code from {$icl_translation_table} where element_id ='{$id}' and language_code ='{$get_language_code}'");

						$this->data[$id]['language_code'] = $get_language_code;	

						$get_trid = apply_filters( 'wpml_element_trid', NULL, $id,$get_element_type );
						$translations_query = $wpdb->prepare(
							"SELECT element_id
							FROM {$wpdb->prefix}icl_translations
							WHERE trid = %d
							AND language_code != %s", $get_trid, $get_language_code
						);
						$element_id = $wpdb->get_results( $translations_query );
						$translated_post_title  = '';
						foreach ($element_id as $translation) {
							$element_id = $translation->element_id;
							if(trim($exp_module) == 'Posts' || trim($exp_module) == 'Pages'){							
								$element_title = $wpdb->get_var("select post_title from $wpdb->posts where ID ='{$element_id}'");
								$translated_post_title .= $element_title.",";
							}
						}
						$this->data[$id]['translated_post_title'] = rtrim($translated_post_title, ",");



						return $this->data[$id];
					}
				}

				public function getAttachment($id)
				{
					global $wpdb;
					$get_attachment = $wpdb->prepare("select guid from {$wpdb->prefix}posts where ID = %d AND post_type = %s", $id, 'attachment');
					$attachment = $wpdb->get_results($get_attachment);
					$attachment_file = $attachment[0]->guid;
					return $attachment_file;

				}

				public function getRepeater($parent)
				{
					global $wpdb;
					$get_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts where post_parent = %d", $parent) , ARRAY_A);
					$i = 0;
					foreach ($get_fields as $key => $value)
					{
						$array[$i] = $value['post_excerpt'];
						$i++;
					}
					return $array;
				}

				/**
				 * Get types fields
				 * @return array    - Types fields
				 */
				public function getTypesFields()
				{
					$getWPTypesFields = get_option('wpcf-fields');
					$typesFields = array();
					if (!empty($getWPTypesFields) && is_array($getWPTypesFields))
					{
						foreach ($getWPTypesFields as $fKey)
						{
							$typesFields[$fKey['meta_key']] = $fKey['name'];
						}
					}
					return $typesFields;
				}

				/**
				 * Final data to be export
				 * @param $data     - Data to be export based on the requested information
				 * @return array    - Final data to be export
				 */
				public function finalDataToExport ($data, $module = false , $optionalType = false) {
					global $wpdb;				
					$result = array();
					foreach ($this->headers as $key => $value)
					{
						if (empty($value))
						{
							unset($this->headers[$key]);
						}
					}
					// Fetch Category Custom Field Values
					if ($module)
					{
						if ($module == 'Categories')
						{
							return $this->fetchCategoryFieldValue($data, $this->module);
						}
					}
					foreach ( $data as $recordId => $rowValue ) {
						$optional_type = '';
						if(is_plugin_active('jet-engine/jet-engine.php')){
							global $wpdb;
							$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
							foreach($get_slug_name as $key=>$get_slug){
								$value=$get_slug->slug;
								$optionaltype=$value;
								if($optionalType == $optionaltype){
									$optional_type=$optionaltype;
								}
							}
						}
	
						foreach ($this->headers as $htemp => $hKey) {
							if(is_array($rowValue) && array_key_exists($hKey, $rowValue) && (!empty($rowValue[$hKey])) ){
								
								if(!empty($optional_type) && $optionalType == $optional_type){								
									if(is_plugin_active('jet-engine/jet-engine.php')){
										$result = $this->getJetCCTValue($data,$optionalType);							
										if(is_array($result)){
											return $result;
										}
										else{
											$result[$recordId][$hKey] = $this->returnMetaValueAsCustomerInput($rowValue[$hKey], $hKey);return $result;	
										}		
									}							
								}		
							else{
									$result[$recordId][$hKey] = $this->returnMetaValueAsCustomerInput($rowValue[$hKey], $hKey);
								}
							}
							else
							{
								$key = $hKey;
								$rowValue['post_type'] = isset($rowValue['post_type']) ? $rowValue['post_type'] : '';
								// Replace the third party plugin name from the fieldname
								$key = $this->replace_prefix_aioseop_from_fieldname($key);
								$key = $this->replace_prefix_yoast_wpseo_from_fieldname($key);
								$key = $this->replace_prefix_wpcf_from_fieldname($key);
								$key = $this->replace_prefix_wpsc_from_fieldname($key);
								$key = $this->replace_underscore_from_fieldname($key);
								$key = $this->replace_wpcr3_from_fieldname($key);
								// Change fieldname depends on the post type
								$key = $this->change_fieldname_depends_on_post_type($rowValue['post_type'], $key);

								if (isset($rowValue['wpcr3_' . $key]))
								{
									$rowValue[$key] = $this->returnMetaValueAsCustomerInput($rowValue['wpcr3_' . $key], $hKey);
								}
								else
								{
									if (isset($rowValue['_yoast_wpseo_' . $key]))
									{ // Is available in yoast plugin
										$rowValue[$key] = $this->returnMetaValueAsCustomerInput($rowValue['_yoast_wpseo_' . $key]);
									}
									else if (isset($rowValue['_aioseop_' . $key]))
									{ // Is available in all seo plugin
										$rowValue[$key] = $this->returnMetaValueAsCustomerInput($rowValue['_aioseop_' . $key]);
									}
									else if (isset($rowValue['_' . $key]))
									{ // Is wp custom fields
										$rowValue[$key] = $this->returnMetaValueAsCustomerInput($rowValue['_' . $key], $hKey);
									}
									else if ($fieldvalue = $this->getWoocommerceMetaValue($key, $rowValue['post_type'], $rowValue))
									{
										$rowValue[$key] = $fieldvalue;
									}
									else if (isset($rowValue['ID']) && $aioseo_field_value = $this->getaioseoFieldValue($rowValue['ID']))
									{
										$rowValue['og_title'] = $aioseo_field_value[0]->og_title;
										$rowValue['og_description'] = $aioseo_field_value[0]->og_description;
										$rowValue['custom_link'] = $aioseo_field_value[0]->canonical_url;
										$rowValue['og_image_type'] = $aioseo_field_value[0]->og_image_type;
										$rowValue['og_image_custom_url'] = $aioseo_field_value[0]->og_image_custom_url;
										$rowValue['og_image_custom_fields'] = $aioseo_field_value[0]->og_image_custom_fields;
										$rowValue['og_video'] = $aioseo_field_value[0]->og_video;
										$rowValue['og_object_type'] = $aioseo_field_value[0]->og_object_type;
										$value = $aioseo_field_value[0]->og_article_tags;
										$article_tags = json_decode($value);
										$og_article_tags = $article_tags[0]->value;
										$rowValue['og_article_tags'] = $og_article_tags;
										$rowValue['og_article_section'] = $aioseo_field_value[0]->og_article_section;
										$rowValue['twitter_use_og'] = $aioseo_field_value[0]->twitter_use_og;
										$rowValue['twitter_card'] = $aioseo_field_value[0]->twitter_card;
										$rowValue['twitter_image_type'] = $aioseo_field_value[0]->twitter_image_type;
										$rowValue['twitter_image_custom_url'] = $aioseo_field_value[0]->twitter_image_custom_url;
										$rowValue['twitter_image_custom_fields'] = $aioseo_field_value[0]->twitter_image_custom_fields;
										$rowValue['twitter_title'] = $aioseo_field_value[0]->twitter_title;
										$rowValue['twitter_description'] = $aioseo_field_value[0]->twitter_description;
										$rowValue['robots_default'] = $aioseo_field_value[0]->robots_default;
										// $rowValue['robots_noindex'] = $aioseo_field_value[0]->robots_noindex;
										$rowValue['robots_noarchive'] = $aioseo_field_value[0]->robots_noarchive;
										$rowValue['robots_nosnippet'] = $aioseo_field_value[0]->robots_nosnippet;
										// $rowValue['robots_nofollow'] = $aioseo_field_value[0]->robots_nofollow;
										$rowValue['robots_noimageindex'] = $aioseo_field_value[0]->robots_noimageindex;
										$rowValue['noodp'] = $aioseo_field_value[0]->robots_noodp;
										$rowValue['robots_notranslate'] = $aioseo_field_value[0]->robots_notranslate;
										$rowValue['robots_max_snippet'] = $aioseo_field_value[0]->robots_max_snippet;
										$rowValue['robots_max_videopreview'] = $aioseo_field_value[0]->robots_max_videopreview;
										$rowValue['robots_max_imagepreview'] = $aioseo_field_value[0]->robots_max_imagepreview;
										$rowValue['aioseo_title'] = $aioseo_field_value[0]->title;
										$rowValue['aioseo_description'] = $aioseo_field_value[0]->description;
										$key = $aioseo_field_value[0]->keyphrases;

										$key1 = json_decode($key);
										$rowValue['keyphrases'] = $key1
											->focus->keyphrase;
									}
									else
									{
										$rowValue[$key] = isset($rowValue[$key]) ? $rowValue[$key] : '';
										$rowValue[$key] = $this->returnMetaValueAsCustomerInput($rowValue[$key], $hKey);
									}
								}
								global $wpdb;
								//Added for user export
								if ($key == 'user_login')
								{
									$wpsc_query = $wpdb->prepare("select ID from {$wpdb->prefix}users where user_login =%s", $rowValue['user_login']);
									$wpsc_meta = $wpdb->get_results($wpsc_query, ARRAY_A);
								}
								if (isset($rowValue['_bbp_forum_type']) && ($rowValue['_bbp_forum_type'] == 'forum' || $rowValue['_bbp_forum_type'] == 'category'))
								{
									if ($key == 'Visibility')
									{
										$rowValue[$key] = $rowValue['post_status'];
									}
									if ($key == 'bbp_moderators')
									{
										$get_forum_moderator_ids = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $recordId AND meta_key = '_bbp_moderator_id' ", ARRAY_A);
										$forum_moderators = '';
										foreach ($get_forum_moderator_ids as $get_moderator_id)
										{
											$forum_user_meta = get_user_by('id', $get_moderator_id['meta_value']);
											$forum_user = $forum_user_meta
												->data->user_login;
											$forum_moderators .= $forum_user . ',';
										}

										$rowValue[$key] = rtrim($forum_moderators, ',');
									}

								}
								if ($key == 'topic_status' || $key == 'author' || $key == 'topic_type')
								{
									$rowValue['topic_status'] = $rowValue['post_status'];
									$rowValue['author'] = $rowValue['post_author'];
									if ($key == 'topic_type')
									{
										$Topictype = get_post_meta($rowValue['_bbp_forum_id'], '_bbp_sticky_topics');
										$topic_types = get_option('_bbp_super_sticky_topics');
										$rowValue['topic_type'] = 'normal';
										if ($Topictype)
										{
											foreach ($Topictype as $t_type)
											{
												if ($t_type['0'] == $recordId)
												{
													$rowValue['topic_type'] = 'sticky';
												}
											}
										}
										elseif (!empty($topic_types))
										{
											foreach ($topic_types as $top_type)
											{
												if ($top_type == $rowValue['ID'])
												{
													$rowValue['topic_type'] = 'super sticky';
												}
											}
										}
									}
								}
								if ($key == 'reply_status' || $key == 'reply_author')
								{
									$rowValue['reply_status'] = $rowValue['post_status'];
									$rowValue['reply_author'] = $rowValue['post_author'];
								}
								if (array_key_exists($hKey, $rowValue))
								{
									$result[$recordId][$hKey] = $rowValue[$hKey];
								}
								else
								{
									$result[$recordId][$hKey] = '';
								}
							}
						}
					}
					return $result;
				}

				function get_common_post_metadata($meta_id)
				{
					global $wpdb;
					$mdata = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}usermeta WHERE umeta_id = %d", $meta_id) , ARRAY_A);
					return $mdata[0];
				}

				function get_common_unserialize($serialize_data)
				{

					return json_decode($serialize_data, true);
				}

				/**
				 * Create CSV data from array
				 * @param array $data       2D array with data
				 * @param array $fields     field names
				 * @param bool $append      if true, field names will not be output
				 * @param bool $is_php      if a php die() call should be put on the first
				 *                          line of the file, this is later ignored when read.
				 * @param null $delimiter   field delimiter to use
				 * @return string           CSV data (text string)
				 */
				public function unParse($data = array() , $fields = array() , $append = false, $is_php = false, $delimiter = null)
				{
					if (!is_array($data) || empty($data)) $data = & $this->data;
					if (!is_array($fields) || empty($fields)) $fields = & $this->titles;
					if ($delimiter === null) $delimiter = $this->delimiter;

					$string = ($is_php) ? "<?php header('Status: 403'); die(' '); ?>" . $this->linefeed : '';
					$entry = array();

					// create heading
					if ($this->offset == 0 || $this->checkSplit == 'true')
					{
						if ($this->heading && !$append && !empty($fields))
						{
							foreach ($fields as $key => $value)
							{
								$entry[] = $this->_enclose_value($value);
				}
				$string .= implode($delimiter, $entry) . $this->linefeed;
				$entry = array();
				}
				}

				// create data
				foreach ($data as $key => $row)
				{
					foreach ($row as $field => $value)
					{
						$entry[] = $this->_enclose_value($value);
				}
				$string .= implode($delimiter, $entry) . $this->linefeed;
				$entry = array();
				}
				return $string;
				}

				/**
				 * Enclose values if needed
				 *  - only used by unParse()
				 * @param null $value
				 * @return mixed|null|string
				 */
				public function _enclose_value($value = null)
				{
					if ($value !== null && $value != '')
					{
						$delimiter = preg_quote($this->delimiter, '/');
						$enclosure = preg_quote($this->enclosure, '/');
				
						if ( is_array($value) && isset($value[0]) && $value[0] == '=' ) {
							$value = "'" . $value; // Fix for the comma-separated vulnerabilities.
						}
						// Add a check to ensure $value is not an object
						if ( isset($value) && is_string($value) && preg_match("/".$delimiter."|".$enclosure."|\n|\r/i", $value) ||
							 !is_object($value) && isset($value[0]) && ($value[0] == ' ' || isset($value) && substr($value, -1) == ' ') ) {
							// Handle enclosure
							$value = str_replace($this->enclosure, $this->enclosure.$this->enclosure, $value);
							$value = $this->enclosure.$value.$this->enclosure;
						}
						else{
							if(is_string($value) || is_numeric($value)){
								$value = $this->enclosure.$value.$this->enclosure;
							}
							else {
								$value = '';
							}
						}
					}
					return $value;
				}

				/**
				 * Apply exclusion before export
				 * @param $headers  - Apply exclusion headers
				 * @return array    - Available headers after applying the exclusions
				 */
				public function applyEventExclusion($headers, $optionalType)
				{
					$header_exclusion = array();
					$exclusion = $this->eventExclusions['exclusion_headers']['header'];
					$this->eventExclusions['exclusion_headers']['header'] = $exclusion;
					$required_header = $this->eventExclusions['exclusion_headers']['header'];

					if ($optionalType == 'elementor_library')
					{
						$required_head = array();

						if (isset($required_header['ID']))
						{
							$required_head['ID'] = $required_header['ID'];
						}
						if (isset($required_header['Template title']))
						{
							$required_head['Template title'] = $required_header['Template title'];
						}
						if (isset($required_header['Template content']))
						{
							$required_head['Template content'] = $required_header['Template content'];
						}
						if (isset($required_header['Style']))
						{
							$required_head['Style'] = $required_header['Style'];
						}
						if (isset($required_header['Template type']))
						{
							$required_head['Template type'] = $required_header['Template type'];
						}
						if (isset($required_header['Created time']))
						{
							$required_head['Created time'] = $required_header['Created time'];
						}
						if (isset($required_header['Template status']))
						{
							$required_head['Template status'] = $required_header['Template status'];
						}
						if (isset($required_header['Category']))
						{
							$required_head['Category'] = $required_header['Category'];
						}
						if (isset($required_header['Created by']))
						{
							$required_head['Created by'] = $required_header['Created by'];
						}
						if (!empty($required_head))
						{
							foreach ($headers as $hVal)
							{
								if (array_key_exists($hVal, $required_head))
								{
									$header_exclusion[] = $hVal;
								}
							}
							return $header_exclusion;
						}
						else
						{
							return $headers;
						}
					}
					else
					{
						if (!empty($required_header))
						{
							foreach ($headers as $hVal)
							{
								if (array_key_exists($hVal, $required_header))
								{
									$header_exclusion[] = $hVal;
								}
							}
							return $header_exclusion;
						}
						else
						{
							return $headers;
						}
					}
				}

				public function replace_prefix_aioseop_from_fieldname($fieldname)
				{
					if (preg_match('/_aioseop_/', $fieldname))
					{
						return preg_replace('/_aioseop_/', '', $fieldname);
					}

					return $fieldname;
				}
				public function getaioseoFieldValue($post_id)
				{
					if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php'))
					{
						global $wpdb;
						$aioseo_slug = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aioseo_posts WHERE post_id='$post_id' ");
						return $aioseo_slug;
					}

				}

				public function replace_prefix_pods_from_fieldname($fieldname)
				{
					if (preg_match('/_pods_/', $fieldname))
					{
						return preg_replace('/_pods_/', '', $fieldname);
					}

					return $fieldname;
				}

				public function replace_prefix_yoast_wpseo_from_fieldname($fieldname)
				{

					if (preg_match('/_yoast_wpseo_/', $fieldname))
					{
						$fieldname = preg_replace('/_yoast_wpseo_/', '', $fieldname);

						if ($fieldname == 'focuskw')
						{
							$fieldname = 'focus_keyword';
						}
						else if ($fieldname == 'bread-crumbs-title')
						{ // It is comming as bctitle nowadays
							$fieldname = 'bctitle';
						}
						elseif ($fieldname == 'metadesc')
						{
							$fieldname = 'meta_desc';
						}
					}

					return $fieldname;
				}

				public function replace_prefix_wpcf_from_fieldname($fieldname)
				{
					if (preg_match('/_wpcf/', $fieldname))
					{
						return preg_replace('/_wpcf/', '', $fieldname);
					}

					return $fieldname;
				}

				public function replace_prefix_wpsc_from_fieldname($fieldname)
				{
					if (preg_match('/_wpsc_/', $fieldname))
					{
						return preg_replace('/_wpsc_/', '', $fieldname);
					}

					return $fieldname;
				}

				public function replace_wpcr3_from_fieldname($fieldname)
				{
					if (preg_match('/wpcr3_/', $fieldname))
					{
						$fieldname = preg_replace('/wpcr3_/', '', $fieldname);
					}

					return $fieldname;
				}

				public function change_fieldname_depends_on_post_type($post_type, $fieldname)
				{
					if ($post_type == 'wpcr3_review')
					{
						switch ($fieldname)
						{
						case 'ID':
							return 'review_id';
						case 'post_status':
							return 'status';
						case 'post_content':
							return 'review_text';
						case 'post_date':
							return 'date_time';
						default:
							return $fieldname;
						}
					}
					if ($post_type == 'shop_order_refund')
					{
						switch ($fieldname)
						{
						case 'ID':
							return 'REFUNDID';
						default:
							return $fieldname;
						}
					}
					else if ($post_type == 'shop_order')
					{
						switch ($fieldname)
						{
						case 'ID':
							return 'ORDERID';
						case 'post_status':
							return 'order_status';
						case 'post_excerpt':
							return 'customer_note';
						case 'post_date':
							return 'order_date';
						default:
							return $fieldname;
						}
					}
					else if ($post_type == 'shop_coupon')
					{
						switch ($fieldname)
						{
						case 'ID':
							return 'COUPONID';
						case 'post_status':
							return 'coupon_status';
						case 'post_excerpt':
							return 'description';
						case 'post_date':
							return 'coupon_date';
						case 'post_title':
							return 'coupon_code';
						default:
							return $fieldname;
						}
					}
					else if ($post_type == 'product_variation')
					{
						switch ($fieldname)
						{
						case 'ID':
							return 'VARIATIONID';
						case 'post_parent':
							return 'PRODUCTID';
						case 'sku':
							return 'VARIATIONSKU';
						default:
							return $fieldname;
						}
					}

					return $fieldname;
				}

				public function replace_underscore_from_fieldname($fieldname)
				{
					if (preg_match('/_/', $fieldname))
					{
						$fieldname = preg_replace('/^_/', '', $fieldname);
					}

					return $fieldname;
				}

				public function fetchCategoryFieldValue($categories)
				{

					global $wpdb;
					$bulk_category = [];

					foreach ($categories as $category_id => $category)
					{
						$term_meta = get_term_meta($category_id);
						$single_category = [];
						foreach ($this->headers as $header)
						{

							if ($header == 'name')
							{
								$cato[] = get_term($category_id);
								$single_category[$header] = $this->hierarchy_based_term_cat_name($cato, 'category');
								continue;
							}

							if (array_key_exists($header, $category))
							{
								$single_category[$header] = $category[$header];
							}
							else
							{
								if (isset($term_meta[$header]))
								{
									$single_category[$header] = $this->returnMetaValueAsCustomerInput($term_meta[$header]);
								}
								else
								{
									$single_category[$header] = null;
								}
							}
						}
						array_push($bulk_category, $single_category);
					}
					return $bulk_category;
				}
				public function getJetCCTValue($data, $type, $data_type = false){
					global $wpdb;
					$jet_data = $this->JetEngineCCTFields($type);
					$darray_value=array();		
					$darray2 = array();		
					$cct_rel = [];
	
					foreach ($data as $key => $dvalue) {
						$get_guid ='';
						$select_value='';
						$checkbox_key_value='';
						$checkbox_key_value1 ='';
						foreach($dvalue as $dkey=>$value){						
							if($dkey == '_ID'){
								$darray[$dkey] = $value;
							}
							elseif($dkey =='cct_status'){
								$darray[$dkey] = $value;
							}
	
							//JET CCT Relation
							if(!empty($jet_data)){
								if(in_array($dkey,$this->headers) && !array_key_exists($dkey,$jet_data['JECCT']) ){							
										$cct_rel[$key][$dkey] = $data[$key][$dkey];
								}
								
								if(array_key_exists($dkey,$jet_data['JECCT'])){		
									if(empty($value))					{
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
									}
									else {
										if($jet_data['JECCT'][$dkey]['type'] == 'text'){	
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
										}
										elseif($jet_data['JECCT'][$dkey]['type'] == 'textarea'){
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
										}
										elseif($jet_data['JECCT'][$dkey]['type'] == 'colorpicker'){
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
										}
										elseif($jet_data['JECCT'][$dkey]['type'] == 'iconpicker'){
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
										}
										elseif($jet_data['JECCT'][$dkey]['type'] == 'radio'){
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
										}
										elseif($jet_data['JECCT'][$dkey]['type'] == 'number'){
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
										}
										elseif($jet_data['JECCT'][$dkey]['type'] == 'wysiwyg'){
											$value = preg_replace('/\s+/', ' ', $value);
	
											// Minify the HTML content
											$value = trim($value);
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
										}
										elseif($jet_data['JECCT'][$dkey]['type'] == 'switcher'){
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
										}
										elseif($jet_data['JECCT'][$dkey]['type'] == 'time'){
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
										} 
										elseif( $jet_data['JECCT'][$dkey]['type'] == 'media'){									
											if(is_numeric($value)){
												if($value != 0) {
												$get_guid_name = $wpdb->get_results("SELECT guid FROM {$wpdb->prefix}posts WHERE id = '$value'");									
												foreach($get_guid_name as $media_key=>$value){
													$darray1[$jet_data['JECCT'][$dkey]['name']]=$value->guid;
												}
											}
											else {
												
												$darray1[$jet_data['JECCT'][$dkey]['name']]=$value;									
											}
											}
											elseif(is_serialized($value)){
												$media_value=unserialize($value);
												$darray1[$jet_data['JECCT'][$dkey]['name']] = $media_value['url'];	
											}
											else{
												$media_field_val=$value;
												$darray1[$jet_data['JECCT'][$dkey]['name']]=$media_field_val;
											}								
										}
										elseif( $jet_data['JECCT'][$dkey]['type'] == 'gallery'){
											$get_meta_list = explode(',', $value);
											$get_guid ='';
											foreach($get_meta_list as $get_meta){	
												if(is_numeric($get_meta)){
													$get_guid_name = $wpdb->get_results("SELECT guid FROM {$wpdb->prefix}posts WHERE id = '$get_meta'");
													foreach($get_guid_name as $gallery_key=>$value){		
														$get_guid.=$value->guid.',';
													}
												}
												elseif(is_serialized($get_meta)){
													$gal_value=unserialize($get_meta);
													foreach($gal_value as $gal_key1=>$gal_val){
														$get_guid.=$gal_val['url'].',';
													}	
												}
												else{
													$get_guid .= $get_meta.',';
												}
											}
											$darray1[$jet_data['JECCT'][$dkey]['name']]=rtrim($get_guid,',');
										}						
						
										elseif($jet_data['JECCT'][$dkey]['type'] == 'date'){
											if(!empty($value)){
												if(strpos($value, '-') !== FALSE){
													$date_value= $value;
												}else{
													$date_value = date('Y-m-d', $value);
												}
											}
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $date_value;
										}
	
										elseif($jet_data['JECCT'][$dkey]['type'] == 'datetime-local'){
											if(!empty($value)){
												if(strpos($value, '-') !== FALSE){
													$datetime_value = $value;
												}else{
													$datetime_value = date('Y-m-d H:i', $value);
												}
												$datetime_value = str_replace(' ', 'T', $datetime_value);
											}
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $datetime_value;
										}
	
										elseif($jet_data['JECCT'][$dkey]['type'] == 'checkbox'){
											if($jet_data['JECCT'][$dkey]['is_array'] == 1){									
												$checkbox_value=unserialize($value);																		
												if (is_array($checkbox_value)) {
													$darray1[$jet_data['JECCT'][$dkey]['name']] = implode(',', $checkbox_value);
												} else {
													$darray1[$jet_data['JECCT'][$dkey]['name']] = ''; // or handle as needed
												}
											}
											else{
												$checkbox_value=unserialize($value);
												$checkbox_key_value='';
												foreach($checkbox_value as $check_key=>$check_val){
													if($check_val == 'true'){
														$checkbox_key_value.=$check_key.',';
													}
												}
												$darray1[$jet_data['JECCT'][$dkey]['name']] = rtrim($checkbox_key_value,',');
											}				
										}
						
										elseif($jet_data['JECCT'][$dkey]['type'] == 'posts'){
											if(is_serialized($value)){
												$jet_posts = unserialize($value);
												$jet_posts_value='';
												foreach($jet_posts as $posts_key=>$post_val){
														$query = "SELECT post_title FROM {$wpdb->prefix}posts WHERE id ='{$post_val}' AND post_status='publish'";
														$name = $wpdb->get_results($query);
														if (!empty($name)) {
															$jet_posts_value.=$name[0]->post_title.',';
														}
												}
												$post_names=rtrim($jet_posts_value,',');
											}
											else{
												$query = "SELECT post_title FROM {$wpdb->prefix}posts WHERE id ='{$value}' AND post_status='publish'";
												$name = $wpdb->get_results($query);
												if (!empty($name)) {
													$post_names=$name[0]->post_title;
												}
											}
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $post_names;
										}
										elseif($jet_data['JECCT'][$dkey]['type'] == 'select'){
											if(is_serialized($value)){
												$select_value='';
												$gal_value=unserialize($value);
												foreach($gal_value as $select_key=>$gal_val){
													$select_value.=$gal_val.',';
												}	
											}
											else{
												$select_val=$value;
												$select_value=$select_val;
											}
	
											$darray1[$jet_data['JECCT'][$dkey]['name']] = rtrim($select_value,',');
										}  
									}
								}
												
							}											
						}																
						if(!empty($darray1) && empty($darray2)){
						$data_array_values=array_merge($darray,$darray1);
						}
						elseif(empty($darray1) && !empty($darray2)){
							$data_array_values=array_merge($darray,$darray2);
						}
						else if (!empty($darray1) && !empty($darray2)){
							$data_array_values=array_merge($darray,$darray1,$darray2);
						}

						$darray_value[$key]=$data_array_values;												
					}	
							//CCT Relation
							if(!empty($cct_rel) && !empty($darray_value)){
							foreach($cct_rel as $id => $value){
								unset($value['_ID']);
								unset($value['cct_status']);
								$get_val = $darray_value[$id];							
								$all_data[$id] = array_merge($get_val,$value);
							}	
							$darray_value = $all_data;
						}		
						//End CCT Relation	
						//For correct the CSV columns 					
						foreach($this->headers as $row_header){
						foreach($darray_value as $key => $value){
							if(!empty($value)){
								if(array_key_exists($row_header,$value)){
								$new_data[$key][$row_header] = $value[$row_header];
								}
								else {
									$new_data[$key][$row_header] = $value[$row_header];
								}
							}
						} 
						}	
						$darray_value = $new_data;	
					//added
					if(!empty($darray_value)){	
						return $darray_value;
					}
					else{
						return ;
					}	
				}
				public function JetEngineCCTFields($type){
					global $wpdb;	
					$jet_field = array();
					$customFields = [];
					$get_meta_fields = $wpdb->get_results($wpdb->prepare("select id, meta_fields from {$wpdb->prefix}jet_post_types where slug = %s and status = %s", $type, 'content-type'));
					
					if(!empty($get_meta_fields)){
						$unserialized_meta = maybe_unserialize($get_meta_fields[0]->meta_fields);
				
						foreach($unserialized_meta as $jet_key => $jet_value){
							$customFields["JECCT"][ $jet_value['name']]['label'] = $jet_value['title'];
							$customFields["JECCT"][ $jet_value['name']]['name']  = $jet_value['name'];
							$customFields["JECCT"][ $jet_value['name']]['type']  = $jet_value['type'];
							$customFields["JECCT"][ $jet_value['name']]['options'] = isset($jet_value['options']) ? $jet_value['options'] : '';
							$customFields["JECCT"][ $jet_value['name']]['is_multiple'] = isset($jet_value['is_multiple']) ? $jet_value['is_multiple'] : '';
							$customFields["JECCT"][ $jet_value['name']]['is_array'] = isset($jet_value['is_array']) ? $jet_value['is_array'] : '';
							$jet_field[] = $jet_value['name'];
						}
					}
					return $customFields;	
				}

				public function returnMetaValueAsCustomerInput($meta_value, $header = false)
				{
					if ($header == 'rating_data') {
						return $meta_value; 
					}	
					if ($header != 'jet_abaf_price'  && $header != 'jet_abaf_custom_schedule'  && $header != 'jet_abaf_configuration'  && $header != '_elementor_css'  && $header != '_elementor_controls_usage'  && $header != 'elementor_library_category'  && $header != '_elementor_page_assets'  && $header != '_elementor_page_settings'  &&  $header != '_elementor_data'){

						if (is_array($meta_value))
						{
							$meta_value = $meta_value[0];
							if (!empty($meta_value))
							{
								if (is_serialized($meta_value))
								{								
									return json_decode($meta_value, true);
								}
								else if (is_array($meta_value))
								{
									return implode('|', $meta_value);
								}
								else if (is_string($meta_value))
								{
									return $meta_value;
								}
								else if ($this->isJSON($meta_value) === true)
								{
									return json_decode($meta_value);
								}

								return $meta_value;
							}

							return $meta_value;
						}
						else
						{
							if (is_serialized($meta_value))
							{
								$meta_value = unserialize($meta_value);
								if (is_array($meta_value))
								{
									$meta_value = array_map('strval', $meta_value);
									return implode('|', $meta_value);
								}
								return $meta_value;
							}
							else if (is_array($meta_value))
							{
								return implode('|', $meta_value);
							}
							else if (is_string($meta_value))
							{
								return $meta_value;
							}
							else if ($this->isJSON($meta_value) === true)
							{
								return json_decode($meta_value);
							}
						}
					}
					elseif($header == '_elementor_data'){
						$meta_value = base64_encode($meta_value);
					}


					return $meta_value;
				}

				public function isJSON($meta_value)
				{
					$json = json_decode($meta_value);
					return $json && $meta_value != $json;
				}

				public function hierarchy_based_term_name($term, $taxanomy_type)
				{

					$tempo = array();
					$termo = '';
					$i = 0;
					foreach ($term as $termkey => $terms)
					{
						$tempo[] = $terms->name;
						$temp_hierarchy_terms = [];

						if (!empty($terms->parent))
						{
							$temp1 = $terms->name;
							$i++;

							$termexp = explode(',', $termo);

							$termo = implode(',', $termexp);
							$temp_hierarchy_terms[] = $terms->name;
							$hierarchy_terms = $this->call_back_to_get_parent($terms->parent, $taxanomy_type, $tempo, $temp_hierarchy_terms);
							$parent_name = get_term($terms->parent);
							$termo .= $this->split_terms_by_arrow($hierarchy_terms, $parent_name->name) . ',';

						}
						else
						{

							if (in_array($terms->name, $tempo))
							{

								$termo .= $terms->name . ',';

							}
						}
					}
					return $termo;

				}

				public function hierarchy_based_term_cat_name($term, $taxanomy_type)
				{
					$tempo = array();
					$termo = '';
					foreach ($term as $terms)
					{
						$tempo[] = $terms->name;
						$temp_hierarchy_terms = [];
						if (!empty($terms->parent))
						{
							$temp_hierarchy_terms[] = $terms->name;
							$hierarchy_terms = $this->call_back_to_get_parent($terms->parent, $taxanomy_type, $tempo, $temp_hierarchy_terms);
							$parent_name = get_term($terms->parent);
							$termo = $this->split_terms_by_arrow($hierarchy_terms, $parent_name->name);

						}
						else
						{
							$termo = $terms->name;

						}
					}
					return $termo;
				}
				public function call_back_to_get_parent($term_id, $taxanomy_type, $tempo, $temp_hierarchy_terms = [])
				{
					$term = get_term($term_id, $taxanomy_type);
					if (!empty($term->parent))
					{
						if (in_array($term->name, $tempo))
						{

							$temp_hierarchy_terms[] = $term->name;

							$temp_hierarchy_terms = $this->call_back_to_get_parent($term->parent, $taxanomy_type, $tempo, $temp_hierarchy_terms);
						}
						else
						{
							$temp_hierarchy_terms[] = '';

							$temp_hierarchy_terms = $this->call_back_to_get_parent($term->parent, $taxanomy_type, $tempo, $temp_hierarchy_terms);
						}

					}
					else
					{
						if (in_array($term->name, $tempo))
						{
							$temp_hierarchy_terms[] = $term->name;
						}
						else
						{
							$temp_hierarchy_terms[] = '';
						}
					}
					return $temp_hierarchy_terms;
				}
				// public function call_back_to_get_parent($term_id, $taxanomy_type, $temp_hierarchy_terms = []){
				// 	$term = get_term($term_id, $taxanomy_type);
				// 	if(!empty($term->parent)){
				// 		$temp_hierarchy_terms[] = $term->name;
				// 		$temp_hierarchy_terms = $this->call_back_to_get_parent($term->parent, $taxanomy_type, $temp_hierarchy_terms);
				// 	}else{
				// 		$temp_hierarchy_terms[] = $term->name;
				// 	}
				// 	return $temp_hierarchy_terms;
				// }
				public function split_terms_by_arrow($hierarchy_terms, $termParentName)
				{

					krsort($hierarchy_terms);
					$terms_value = $termParentName . '>' . $hierarchy_terms[0];
					//return implode('>', $hierarchy_terms);
					return $terms_value;
				}

				public function getWoocommerceMetaValue($fieldname, $post_type, $post)
				{
					$post_type = isset($post_type) ? $post_type : '';
					if ($post_type == 'shop_order_refund')
					{
						switch ($fieldname)
						{
						case 'REFUNDID':
							return $post['ID'];
						default:
							return $post[$fieldname];
						}
					}
					else if ($post_type == 'shop_order')
					{
						switch ($fieldname)
						{
						case 'ORDERID':
							return $post['ID'];
						case 'order_status':
							return $post['post_status'];
						case 'customer_note':
							return $post['post_excerpt'];
						case 'order_date':
							return $post['post_date'];
						default:
							return $post[$fieldname];
						}
					}
					else if ($post_type == 'shop_coupon')
					{
						switch ($fieldname)
						{
						case 'COUPONID':
							return $post['ID'];
						case 'coupon_status':
							return $post['post_status'];
						case 'description':
							return $post['post_excerpt'];
						case 'coupon_date':
							return $post['post_date'];
						case 'coupon_code':
							return $post['post_title'];
						case 'expiry_date':
							if (isset($post['date_expires']))
							{
								$timeinfo = date('m/d/Y', $post['date_expires']);
							}
							$timeinfo = isset($timeinfo) ? $timeinfo : '';
							return $timeinfo;
						default:
							return $post[$fieldname];
						}
					}
					else if ($post_type == 'product_variation')
					{
						switch ($fieldname)
						{
						case 'VARIATIONID':
							return $post['ID'];
						case 'PRODUCTID':
							return $post['post_parent'];
						case 'VARIATIONSKU':
							return $post['sku'];
						default:
							return $post[$fieldname];
						}
					}
					return false;
				}

				}

				return new exportExtension();
				}

