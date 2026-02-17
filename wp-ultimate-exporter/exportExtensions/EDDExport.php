<?php

/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\SMEXP;

if (!defined('ABSPATH'))
	exit; // Exit if accessed directly

/** 
 * Class EDDExport
 * Handles all Easy Digital Downloads export functionality
 * @package Smackcoders\WCSV
 */
class EDDExport
{
	protected static $instance = null, $export_instance;
	public $totalRowCount;
	public $plugin;

	public static function getInstance()
	{
		if (null == self::$instance) {
			self::$instance = new self;
			EDDExport::$export_instance = ExportExtension::getInstance();
		}
		return self::$instance;
	}
	/**
	 * Check if EDD 3.0+ is active (uses custom order tables)
	 * 
	 * @return bool True if EDD 3.0+, false otherwise
	 */
	private function is_edd_3_0_plus()
	{
		if (!class_exists('Easy_Digital_Downloads')) {
			return false;
		}

		// Check for EDD 3.0+ Order class
		if (class_exists('\\EDD\\Orders\\Order')) {
			return true;
		}

		// Check for Order_Query class
		if (class_exists('\\EDD\\Orders\\Order_Query')) {
			return true;
		}

		// Check version if function exists
		if (function_exists('edd_get_version')) {
			$version = edd_get_version();
			if ($version && version_compare($version, '3.0', '>=')) {
				return true;
			}
		}

		return false;
	}




	/**
	 * EDDExport constructor.
	 */
	public function __construct()
	{
		$this->plugin = Plugin::getInstance();
	}

	/**
	 * Export EDD Downloads data
	 * 
	 * @param int $id Download ID
	 * @return void
	 */
	public function getEDDDownloadDataMaster($id)
	{
		error_log("EDD Export function started. ID: " . $id);
		global $wpdb;

		// Validate download post type
		$post_type = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_type FROM {$wpdb->posts} WHERE ID = %d",
				$id
			)
		);

		if ($post_type !== 'download') {
			return;
		}


		$download_files_raw = get_post_meta($id, 'edd_download_files', true);
		$download_files_output = '';

		if (!empty($download_files_raw) && is_array($download_files_raw)) {
			foreach ($download_files_raw as $file) {
				if (!empty($file['file'])) {
					$download_files_output .=
						'file_name:' . ($file['name'] ?? '') .
						',file_url:' . ($file['file'] ?? '') . ' | ';
				}
			}
			$download_files_output = rtrim($download_files_output, ' | ');
		}


		$variable_prices_raw = get_post_meta($id, 'edd_variable_prices', true);
		$variable_pricing = (int) get_post_meta($id, '_variable_pricing', true);
		$variable_prices_output = '';

		if ($variable_pricing === 1 && !empty($variable_prices_raw) && is_array($variable_prices_raw)) {
			foreach ($variable_prices_raw as $price_id => $price) {
				$variable_prices_output .=
					'price_id:' . $price_id .
					',name:' . ($price['name'] ?? '') .
					',amount:' . ($price['amount'] ?? '') .
					',sale_price:' . ($price['sale_price'] ?? '') . ' | ';
			}
			$variable_prices_output = rtrim($variable_prices_output, ' | ');
		}


		$faq_raw = get_post_meta($id, 'edd_faq', true);
		$faq_output = '';

		if (!empty($faq_raw) && is_array($faq_raw)) {
			foreach ($faq_raw as $faq) {
				$faq_output .=
					'question:' . ($faq['question'] ?? '') .
					',answer:' . ($faq['answer'] ?? '') . ' | ';
			}
			$faq_output = rtrim($faq_output, ' | ');
		}


		$thumbnail_id = (int) get_post_thumbnail_id($id);
		$price = get_post_meta($id, 'edd_price', true);
		$sales = (int) get_post_meta($id, '_edd_download_sales', true);
		$earnings = get_post_meta($id, '_edd_download_earnings', true);
		$download_limit = (int) get_post_meta($id, '_edd_download_limit', true);
		$button_behavior = get_post_meta($id, '_edd_button_behavior', true);
		$sku = get_post_meta($id, 'edd_sku', true);
		$featured = (int) get_post_meta($id, '_edd_featured', true);
		$product_notes = get_post_meta($id, 'edd_product_notes', true);
		$default_price_id = (int) get_post_meta($id, '_edd_default_price_id', true);
		$price_options_mode = (int) get_post_meta($id, '_edd_price_options_mode', true);
		$refund_window = (int) get_post_meta($id, '_edd_refund_window', true);
		$hide_purchase_link = (int) get_post_meta($id, '_edd_hide_purchase_link', true);
		$bundle_conditions = get_post_meta($id, '_edd_bundled_products_conditions', true);
		$bundle_products = get_post_meta($id, '_edd_bundled_products', true);
		$edit_lock = get_post_meta($id, '_edit_lock', true);
		$edit_last = get_post_meta($id, '_edit_last', true);

		$bundle_conditions = is_array($bundle_conditions)
			? $bundle_conditions
			: (strlen($bundle_conditions) ? [$bundle_conditions] : []);

		$bundle_products = is_array($bundle_products)
			? $bundle_products
			: (strlen($bundle_products) ? [$bundle_products] : []);

		// Convert to comma-separated strings
		$bundle_conditions = implode('|', $bundle_conditions);
		$bundle_products = implode('|', $bundle_products);

		// Log
		error_log('Bundle conditions: ' . $bundle_conditions);
		error_log('Bundle products: ' . $bundle_products);

		$product_type = get_post_meta($id, '_edd_product_type', true);
		$feature_download = (int) get_post_meta($id, 'edd_feature_download', true);

		$status = get_post_status($id);
		$publish_date = get_post_field('post_date', $id);


		$categories = wp_get_post_terms($id, 'download_category', ['fields' => 'names']);
		$tags = wp_get_post_terms($id, 'download_tag', ['fields' => 'names']);




		EDDExport::$export_instance->data[$id]['thumbnail_id'] = $thumbnail_id ?: 0;
		EDDExport::$export_instance->data[$id]['price'] = $price ?: '';
		EDDExport::$export_instance->data[$id]['variable_pricing'] = $variable_pricing ?: 0;
		EDDExport::$export_instance->data[$id]['variable_prices'] = $variable_prices_output ?: '';
		EDDExport::$export_instance->data[$id]['default_price_id'] = $default_price_id ?: 0;
		EDDExport::$export_instance->data[$id]['price_options_mode'] = $price_options_mode ?: 0;
		EDDExport::$export_instance->data[$id]['download_sales'] = $sales ?: 0;
		EDDExport::$export_instance->data[$id]['download_earnings'] = $earnings ?: '';
		EDDExport::$export_instance->data[$id]['download_limit'] = $download_limit ?: 0;
		EDDExport::$export_instance->data[$id]['refund_window'] = $refund_window ?: 0;
		EDDExport::$export_instance->data[$id]['hide_purchase_link'] = $hide_purchase_link ?: 0;
		EDDExport::$export_instance->data[$id]['button_behavior'] = $button_behavior ?: '';
		EDDExport::$export_instance->data[$id]['sku'] = $sku ?: '';
		EDDExport::$export_instance->data[$id]['product_type'] = $product_type ?: '';
		EDDExport::$export_instance->data[$id]['featured'] = $featured ?: 0;
		EDDExport::$export_instance->data[$id]['feature_download'] = $feature_download ?: 0;
		EDDExport::$export_instance->data[$id]['product_notes'] = $product_notes ?: '';
		EDDExport::$export_instance->data[$id]['download_files'] = $download_files_output ?: '';
		EDDExport::$export_instance->data[$id]['bundled_products_conditions'] = $bundle_conditions ?: '';
		EDDExport::$export_instance->data[$id]['bundled_products'] = $bundle_products ?: '';
		EDDExport::$export_instance->data[$id]['edit_lock'] = $edit_lock ?: '';
		EDDExport::$export_instance->data[$id]['edit_last'] = $edit_last ?: '';

		EDDExport::$export_instance->data[$id]['download_category'] = !empty($categories) ? implode(',', $categories) : '';
		EDDExport::$export_instance->data[$id]['download_tag'] = !empty($tags) ? implode(',', $tags) : '';
		EDDExport::$export_instance->data[$id]['faq'] = $faq_output ?: '';

		error_log("EDD Export completed for ID: " . $id);
	}

	/**
	 * Export EDD Customers data
	 * 
	 * @param int $id Customer ID
	 * @return void
	 */
	public function getEDDCustomerDataMaster($id)
	{
		global $wpdb;

		if (!class_exists('Easy_Digital_Downloads')) {
			return;
		}

		$customers_table = $wpdb->prefix . 'edd_customers';
		$customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $customers_table WHERE id = %d", $id));

		if (!$customer) {
			return;
		}

		// Split name into first and last name
		$name_parts = explode(' ', trim($customer->name), 2);
		$first_name = $name_parts[0] ?? '';
		$last_name = $name_parts[1] ?? '';

		// Get customer address from wp_edd_customer_addresses
		$addresses_table = $wpdb->prefix . 'edd_customer_addresses';
		$address_data = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $addresses_table WHERE customer_id = %d AND type = 'billing' AND is_primary = 1 LIMIT 1",
			$id
		));

		// Get phone number and notes from customermeta
		$meta_table = $wpdb->prefix . 'edd_customermeta';
		$phone_meta = $wpdb->get_var($wpdb->prepare(
			"SELECT meta_value FROM $meta_table WHERE edd_customer_id = %d AND meta_key = 'phone'",
			$id
		));

		// Get customer notes from wp_edd_notes table (EDD 3.0+)
		$notes_table = $wpdb->prefix . 'edd_notes';
		$notes_data = $wpdb->get_results($wpdb->prepare(
			"SELECT content FROM $notes_table WHERE object_id = %d AND object_type = 'customer' ORDER BY date_created ASC",
			$id
		));

		$notes_array = array();
		foreach ($notes_data as $note) {
			$notes_array[] = $note->content;
		}
		$notes = implode('|', $notes_array);


		// Get purchase history (order IDs) - Support both EDD 2.x and 3.0+
		$order_ids = array();

		if ($this->is_edd_3_0_plus()) {
			// EDD 3.0+ - Query from custom tables
			$orders_table = $wpdb->prefix . 'edd_orders';
			$orders = $wpdb->get_results($wpdb->prepare(
				"SELECT id FROM $orders_table WHERE customer_id = %d ORDER BY id ASC",
				$id
			));
			foreach ($orders as $order) {
				$order_ids[] = $order->id;
			}
		} else {
			// Legacy EDD 2.x - Query from posts table
			$payments = $wpdb->get_results($wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'edd_payment' 
                 AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_edd_payment_customer_id' AND meta_value = %d)",
				$id
			));
			foreach ($payments as $payment) {
				$order_ids[] = $payment->ID;
			}
		}


		// Get WordPress user data if user_id exists
		$user_data = null;
		$username = '';
		$user_status = '';
		if ($customer->user_id > 0) {
			$user_data = get_userdata($customer->user_id);
			if ($user_data) {
				$username = $user_data->user_login;
				// Get user status - check if user exists and is not deleted
				$user_status = $user_data ? 'active' : 'inactive';
			}
		}

		// Export all fields
		EDDExport::$export_instance->data[$id]['id'] = $customer->id;
		EDDExport::$export_instance->data[$id]['email'] = $customer->email;
		EDDExport::$export_instance->data[$id]['name'] = $customer->name;
		EDDExport::$export_instance->data[$id]['first_name'] = $first_name;
		EDDExport::$export_instance->data[$id]['last_name'] = $last_name;
		EDDExport::$export_instance->data[$id]['user_id'] = $customer->user_id;
		EDDExport::$export_instance->data[$id]['username'] = $username;
		EDDExport::$export_instance->data[$id]['user_status'] = $user_status;
		EDDExport::$export_instance->data[$id]['status'] = $customer->status ?: '';
		EDDExport::$export_instance->data[$id]['phone'] = $phone_meta ?: '';
		EDDExport::$export_instance->data[$id]['address'] = $address_data ? $address_data->address : '';
		EDDExport::$export_instance->data[$id]['address2'] = $address_data ? $address_data->address2 : '';
		EDDExport::$export_instance->data[$id]['city'] = $address_data ? $address_data->city : '';
		EDDExport::$export_instance->data[$id]['region'] = $address_data ? $address_data->region : '';
		EDDExport::$export_instance->data[$id]['postal_code'] = $address_data ? $address_data->postal_code : '';
		EDDExport::$export_instance->data[$id]['country'] = $address_data ? $address_data->country : '';
		EDDExport::$export_instance->data[$id]['purchase_count'] = $customer->purchase_count;
		EDDExport::$export_instance->data[$id]['purchase_value'] = $customer->purchase_value;
		EDDExport::$export_instance->data[$id]['date_created'] = $customer->date_created;
		EDDExport::$export_instance->data[$id]['order_ids'] = implode(',', $order_ids);
		EDDExport::$export_instance->data[$id]['notes'] = $notes ?: '';
	}

	/**
	 * Export EDD Discounts data
	 * 
	 * @param int $id Discount ID
	 * @return void
	 */
	public function getEDDDiscountDataMaster($id)
        {
                global $wpdb;
                error_log('DEBUG: getEDDDiscountDataMaster called for ID: ' . $id);

		if (!class_exists('Easy_Digital_Downloads')) {
			return;
		}


		// Check EDD version and use appropriate API
		if ($this->is_edd_3_0_plus()) {
			// EDD 3.0+ - Query from wp_edd_adjustments table
			try {
				$adjustments_table = $wpdb->prefix . 'edd_adjustments';
				$discount = $wpdb->get_row($wpdb->prepare(
					"SELECT * FROM $adjustments_table WHERE id = %d AND type = 'discount'",
					$id
				));

				if (!$discount) {
					// Try alternative: check if discount code exists
					$discount_by_code = $wpdb->get_row($wpdb->prepare(
						"SELECT * FROM $adjustments_table WHERE code = %s AND type = 'discount'",
						$id
					));
					if ($discount_by_code) {
						$discount = $discount_by_code;
					} else {
						return;
					}
				}

				// Get discount meta from wp_edd_adjustmentmeta
				$meta_table = $wpdb->prefix . 'edd_adjustmentmeta';
				$discount_meta = $wpdb->get_results($wpdb->prepare(
					"SELECT meta_key, meta_value FROM $meta_table WHERE edd_adjustment_id = %d",
					$discount->id
				));

				$product_requirements = array();
				$excluded_products = array();
				$category_requirements = array();
				$excluded_categories = array();
				$min_price = $discount->min_charge_amount ?: '';
				$max_price = '';
				$max_uses_per_user = $discount->once_per_customer ?: '';
				$product_condition = '';

				foreach ($discount_meta as $meta) {
					switch ($meta->meta_key) {
						case 'product_requirements':
						case 'product_requirement':
							$val = maybe_unserialize($meta->meta_value);
							$product_requirements = is_array($val) ? $val : array($val);
							break;
						case 'excluded_products':
						case 'excluded_product':
							$val = maybe_unserialize($meta->meta_value);
							$excluded_products = is_array($val) ? $val : array($val);
							break;
						case 'category_requirements':
						case 'categories':
							$val = maybe_unserialize($meta->meta_value);
							$category_requirements = is_array($val) ? $val : array($val);
							break;
						case 'excluded_categories':
						case 'excluded_category':
							$val = maybe_unserialize($meta->meta_value);
							$excluded_categories = is_array($val) ? $val : array($val);
							break;
						case 'min_price':
							$min_price = $meta->meta_value;
							break;
						case 'max_price':
							$max_price = $meta->meta_value;
							break;
						case 'max_uses_per_user':
							$max_uses_per_user = $meta->meta_value;
							break;
						case 'product_condition':
							$product_condition = $meta->meta_value;
							break;
					}
				}

				// Map adjustment fields to discount fields
				EDDExport::$export_instance->data[$id]['name'] = $discount->name ?: '';
				EDDExport::$export_instance->data[$id]['code'] = $discount->code ?: '';
				EDDExport::$export_instance->data[$id]['amount'] = $discount->amount ?: 0;
				EDDExport::$export_instance->data[$id]['type'] = $discount->amount_type ?: 'percentage';
				EDDExport::$export_instance->data[$id]['status'] = $discount->status ?: 'active';
				EDDExport::$export_instance->data[$id]['start_date'] = $discount->start_date ?: '';
				EDDExport::$export_instance->data[$id]['end_date'] = $discount->end_date ?: '';
				EDDExport::$export_instance->data[$id]['max_uses'] = $discount->max_uses ?: 0;
				EDDExport::$export_instance->data[$id]['use_count'] = $discount->use_count ?: 0;
				EDDExport::$export_instance->data[$id]['product_requirements'] = is_array($product_requirements) ? implode(',', $product_requirements) : '';
				EDDExport::$export_instance->data[$id]['product_condition'] = $product_condition;
				EDDExport::$export_instance->data[$id]['excluded_products'] = is_array($excluded_products) ? implode(',', $excluded_products) : '';
				EDDExport::$export_instance->data[$id]['category_requirements'] = is_array($category_requirements) ? implode(',', $category_requirements) : '';
				EDDExport::$export_instance->data[$id]['min_price'] = $min_price;
				EDDExport::$export_instance->data[$id]['max_uses_per_user'] = $max_uses_per_user;

				// Get discount notes from wp_edd_notes
				$notes_table = $wpdb->prefix . 'edd_notes';
				$notes_data = $wpdb->get_results($wpdb->prepare(
					"SELECT content FROM $notes_table WHERE object_id = %d AND object_type = 'discount'",
					$discount->id
				));
				$discount_notes = array();
				foreach ($notes_data as $note) {
					$discount_notes[] = $note->content;
				}
				EDDExport::$export_instance->data[$id]['notes'] = implode('|', $discount_notes);

				return;
			} catch (Exception $e) {
				// Fallback to legacy method
			}
		}

		// Legacy EDD 2.x - Query from wp_edd_discounts table
		$discounts_table = $wpdb->prefix . 'edd_discounts';

		// Legacy EDD 2.x - Query from wp_edd_discounts table

		$discounts_table = $wpdb->prefix . 'edd_discounts';

		if (!$wpdb->get_var("SHOW TABLES LIKE '$discounts_table'")) {
			return;
		}

		$discount = $wpdb->get_row($wpdb->prepare("SELECT * FROM $discounts_table WHERE id = %d", $id));

		if (!$discount) {
			return;
		}

		// Get discount meta for restrictions
		$meta_table = $wpdb->prefix . 'edd_discountmeta';
		$discount_meta = $wpdb->get_results($wpdb->prepare(
			"SELECT meta_key, meta_value FROM $meta_table WHERE discount_id = %d",
			$id
		));

		$product_requirements = array();
		$excluded_products = array();
		$category_requirements = array();
		$excluded_categories = array();
		$min_price = '';
		$max_price = '';
		$max_uses_per_user = '';
		$product_condition = '';

		foreach ($discount_meta as $meta) {
			if (empty($meta->meta_key))
				continue;

			switch ($meta->meta_key) {
				case '_edd_discount_product_reqs':
					$product_requirements = maybe_unserialize($meta->meta_value);
					break;
				case '_edd_discount_excluded_products':
					$excluded_products = maybe_unserialize($meta->meta_value);
					break;
				case '_edd_discount_product_condition':
					$product_condition = $meta->meta_value;
					break;
				case '_edd_discount_category_reqs':
					$category_requirements = maybe_unserialize($meta->meta_value);
					break;
				case '_edd_discount_excluded_categories':
					$excluded_categories = maybe_unserialize($meta->meta_value);
					break;
				case '_edd_discount_min_price':
					$min_price = $meta->meta_value;
					break;
				case '_edd_discount_max_price':
					$max_price = $meta->meta_value;
					break;
				case '_edd_discount_max_uses_per_user':
					$max_uses_per_user = $meta->meta_value;
					break;
			}
		}

		EDDExport::$export_instance->data[$id]['code'] = $discount->code;
		EDDExport::$export_instance->data[$id]['amount'] = $discount->amount;
		EDDExport::$export_instance->data[$id]['type'] = $discount->type;
		EDDExport::$export_instance->data[$id]['status'] = $discount->status;
		EDDExport::$export_instance->data[$id]['start_date'] = $discount->start_date;
		EDDExport::$export_instance->data[$id]['end_date'] = $discount->end_date;
		EDDExport::$export_instance->data[$id]['max_uses'] = $discount->max_uses;
		EDDExport::$export_instance->data[$id]['use_count'] = $discount->use_count;
		EDDExport::$export_instance->data[$id]['excluded_products'] = is_array($excluded_products) ? implode(',', $excluded_products) : '';
		EDDExport::$export_instance->data[$id]['category_requirements'] = is_array($category_requirements) ? implode(',', $category_requirements) : '';
		EDDExport::$export_instance->data[$id]['excluded_categories'] = is_array($excluded_categories) ? implode(',', $excluded_categories) : '';
		EDDExport::$export_instance->data[$id]['min_price'] = $min_price;
		EDDExport::$export_instance->data[$id]['max_uses_per_user'] = $max_uses_per_user;
		EDDExport::$export_instance->data[$id]['notes'] = get_post_meta($id, '_edd_discount_notes', true);
	}

	/**
	 * Export EDD Reports (Sales / Earnings / Tax / Fees)
	 * 
	 * @param string $report_type Type of report (sales, earnings, tax, fees, gateway_wise, product_wise)
	 * @param string $start_date Start date for report
	 * @param string $end_date End date for report
	 * @return void
	 */
	public function getEDDReportsDataMaster($report_type = 'sales', $start_date = '', $end_date = '')
	{
		global $wpdb;

		if (!class_exists('Easy_Digital_Downloads')) {
			return;
		}

		$payments_table = $wpdb->prefix . 'posts';
		$postmeta_table = $wpdb->prefix . 'postmeta';
		$customers_table = $wpdb->prefix . 'edd_customers';

		// Set default date range if not provided
		if (empty($start_date)) {
			$start_date = date('Y-m-d', strtotime('-30 days'));
		}
		if (empty($end_date)) {
			$end_date = current_time('mysql');
		}

		$date_condition = $wpdb->prepare(
			"AND p.post_date >= %s AND p.post_date <= %s",
			$start_date,
			$end_date
		);

		$reports_data = array();
		$index = 0;

		switch ($report_type) {
			case 'sales':
			case 'earnings':
				// Get sales/earnings by date
				$query = "
					SELECT 
						DATE(p.post_date) as date,
						COUNT(DISTINCT p.ID) as order_count,
						SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_earnings,
						SUM(CAST(pm_tax.meta_value AS DECIMAL(10,2))) as total_tax,
						SUM(CAST(pm_fee.meta_value AS DECIMAL(10,2))) as total_fees
					FROM $payments_table p
					LEFT JOIN $postmeta_table pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_edd_payment_total'
					LEFT JOIN $postmeta_table pm_tax ON p.ID = pm_tax.post_id AND pm_tax.meta_key = '_edd_payment_tax'
					LEFT JOIN $postmeta_table pm_fee ON p.ID = pm_fee.post_id AND pm_fee.meta_key = '_edd_payment_fee'
					
        // Check EDD version and use appropriate tables
        if ($this->is_edd_3_0_plus() && class_exists('\EDD\Orders\Order_Query')) {
            // EDD 3.0+ - Query from custom tables
            $orders_table = $wpdb->prefix . 'edd_orders';
            $order_items_table = $wpdb->prefix . 'edd_order_items';
            $adjustments_table = $wpdb->prefix . 'edd_adjustments';
            
            // Update queries to use custom tables instead of posts
            // This is a placeholder - actual implementation depends on report type
            // For now, we'll use the Order_Query class
            $order_query = new \EDD\Orders\Order_Query();
            $order_query->set('number', -1); // Get all orders
            if (!empty($start_date)) {
                $order_query->set('date_query', array(
                    array(
                        'after' => $start_date,
                        'before' => $end_date,
                        'inclusive' => true,
                    ),
                ));
            }
            $orders = $order_query->get_orders();
            
            // Process orders for reports
            // Note: This is a simplified version - full implementation would
            // need to handle all report types (sales, earnings, gateway-wise, etc.)
            return; // Early return to use EDD 3.0+ data
        }
        
        // Legacy EDD 2.x - Query from posts table

            WHERE p.post_type = 'edd_payment'
					AND p.post_status = 'publish'
					$date_condition
					GROUP BY DATE(p.post_date)
					ORDER BY date DESC
				";
				$results = $wpdb->get_results($query);
				foreach ($results as $row) {
					EDDExport::$export_instance->data[$index]['report_date'] = $row->date;
					EDDExport::$export_instance->data[$index]['order_count'] = $row->order_count;
					EDDExport::$export_instance->data[$index]['total_earnings'] = $row->total_earnings;
					EDDExport::$export_instance->data[$index]['total_tax'] = $row->total_tax ?: 0;
					EDDExport::$export_instance->data[$index]['total_fees'] = $row->total_fees ?: 0;
					$index++;
				}
				break;

			case 'gateway_wise':
				// Get sales by payment gateway
				$query = "
					SELECT 
						pm_gateway.meta_value as gateway,
						COUNT(DISTINCT p.ID) as order_count,
						SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_earnings
					FROM $payments_table p
					LEFT JOIN $postmeta_table pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_edd_payment_total'
					LEFT JOIN $postmeta_table pm_gateway ON p.ID = pm_gateway.post_id AND pm_gateway.meta_key = '_edd_payment_gateway'
					WHERE p.post_type = 'edd_payment'
					AND p.post_status = 'publish'
					$date_condition
					GROUP BY pm_gateway.meta_value
					ORDER BY total_earnings DESC
				";
				$results = $wpdb->get_results($query);
				foreach ($results as $row) {
					EDDExport::$export_instance->data[$index]['gateway'] = $row->gateway ?: 'manual';
					EDDExport::$export_instance->data[$index]['order_count'] = $row->order_count;
					EDDExport::$export_instance->data[$index]['total_earnings'] = $row->total_earnings;
					$index++;
				}
				break;

			case 'product_wise':
				// Get sales by product
				$payments = $wpdb->get_results($wpdb->prepare(
					"SELECT p.ID, pm_total.meta_value as total, pm_meta.meta_value as meta
					 FROM $payments_table p
					 LEFT JOIN $postmeta_table pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_edd_payment_total'
					 LEFT JOIN $postmeta_table pm_meta ON p.ID = pm_meta.post_id AND pm_meta.meta_key = '_edd_payment_meta'
					 WHERE p.post_type = 'edd_payment' AND p.post_status = 'publish' $date_condition
					 LIMIT 1000"
				));

				$product_stats = array();
				foreach ($payments as $payment) {
					$meta = maybe_unserialize($payment->meta);
					if (isset($meta['cart_details']) && is_array($meta['cart_details'])) {
						foreach ($meta['cart_details'] as $item) {
							$download_id = isset($item['id']) ? $item['id'] : 0;
							if ($download_id > 0) {
								if (!isset($product_stats[$download_id])) {
									$product_stats[$download_id] = array(
										'download_id' => $download_id,
										'download_name' => get_the_title($download_id),
										'order_count' => 0,
										'total_earnings' => 0
									);
								}
								$product_stats[$download_id]['order_count']++;
								$product_stats[$download_id]['total_earnings'] += floatval($payment->total);
							}
						}
					}
				}

				foreach ($product_stats as $stat) {
					EDDExport::$export_instance->data[$index]['download_id'] = $stat['download_id'];
					EDDExport::$export_instance->data[$index]['download_name'] = $stat['download_name'];
					EDDExport::$export_instance->data[$index]['order_count'] = $stat['order_count'];
					EDDExport::$export_instance->data[$index]['total_earnings'] = $stat['total_earnings'];
					$index++;
				}
				break;

			case 'tax':
			case 'fees':
				// Get tax/fee breakdown
				$query = "
					SELECT 
						DATE(p.post_date) as date,
						SUM(CAST(pm_tax.meta_value AS DECIMAL(10,2))) as total_tax,
						SUM(CAST(pm_fee.meta_value AS DECIMAL(10,2))) as total_fees
					FROM $payments_table p
					LEFT JOIN $postmeta_table pm_tax ON p.ID = pm_tax.post_id AND pm_tax.meta_key = '_edd_payment_tax'
					LEFT JOIN $postmeta_table pm_fee ON p.ID = pm_fee.post_id AND pm_fee.meta_key = '_edd_payment_fee'
					WHERE p.post_type = 'edd_payment'
					AND p.post_status = 'publish'
					$date_condition
					GROUP BY DATE(p.post_date)
					ORDER BY date DESC
				";
				$results = $wpdb->get_results($query);
				foreach ($results as $row) {
					EDDExport::$export_instance->data[$index]['report_date'] = $row->date;
					EDDExport::$export_instance->data[$index]['total_tax'] = $row->total_tax ?: 0;
					EDDExport::$export_instance->data[$index]['total_fees'] = $row->total_fees ?: 0;
					$index++;
				}
				break;
		}
	}

    /**
     * Export EDD Orders data
     * @param int $id
     */
    public function getEDDOrderDataMaster($id)
    {
        error_log("Exporting EDD Order ID: " . $id);
        
        if ($this->is_edd_3_0_plus()) {
            try {
                $order = edd_get_order($id);
                if (!$order) {
                    error_log("Order not found for ID: " . $id);
                    return;
                }
                
                EDDExport::$export_instance->data[$id]['order_id'] = $order->id;
                EDDExport::$export_instance->data[$id]['order_number'] = $order->number;
                EDDExport::$export_instance->data[$id]['status'] = $order->status;
                EDDExport::$export_instance->data[$id]['customer_id'] = $order->customer_id;
                EDDExport::$export_instance->data[$id]['payment_method'] = $order->payment_method;
                EDDExport::$export_instance->data[$id]['transaction_id'] = $order->transaction_id;
                EDDExport::$export_instance->data[$id]['ip'] = $order->ip;
                EDDExport::$export_instance->data[$id]['total'] = $order->total;
                EDDExport::$export_instance->data[$id]['tax'] = $order->tax;
                EDDExport::$export_instance->data[$id]['currency'] = $order->currency;
                EDDExport::$export_instance->data[$id]['date_created'] = $order->date_created;
                EDDExport::$export_instance->data[$id]['completed_date'] = $order->completed_date;
                EDDExport::$export_instance->data[$id]['mode'] = $order->mode;
                EDDExport::$export_instance->data[$id]['parent_payment_id'] = $order->parent_payment_id;
                EDDExport::$export_instance->data[$id]['user_id'] = $order->user_id;
                
                // Address (if available)
                if (isset($order->billing_address)) {
                    EDDExport::$export_instance->data[$id]['billing_address_1'] = isset($order->billing_address['line1']) ? $order->billing_address['line1'] : '';
                    EDDExport::$export_instance->data[$id]['billing_address_2'] = isset($order->billing_address['line2']) ? $order->billing_address['line2'] : '';
                    EDDExport::$export_instance->data[$id]['billing_city'] = isset($order->billing_address['city']) ? $order->billing_address['city'] : '';
                    EDDExport::$export_instance->data[$id]['billing_zip'] = isset($order->billing_address['zip']) ? $order->billing_address['zip'] : '';
                    EDDExport::$export_instance->data[$id]['billing_state'] = isset($order->billing_address['state']) ? $order->billing_address['state'] : '';
                    EDDExport::$export_instance->data[$id]['billing_country'] = isset($order->billing_address['country']) ? $order->billing_address['country'] : '';
                }

                error_log("Successfully exported data for Order ID: " . $id);

            } catch (\Exception $e) {
                error_log("Error exporting EDD Order ID " . $id . ": " . $e->getMessage());
            }
        } else {
             // Fallback for older EDD versions (if needed, but usually wp_posts based)
             // For now assume EDD 3.0+ as per task context
        }
    }
}

