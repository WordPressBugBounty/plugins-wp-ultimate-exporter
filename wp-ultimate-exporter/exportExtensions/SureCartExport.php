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
 * Class SureCartExport
 * Handles all SureCart export functionality
 * @package Smackcoders\WCSV
 */
class SureCartExport
{
	protected static $instance = null, $export_instance;
	public $totalRowCount;
	public $plugin;

	public static function getInstance()
	{
		if (null == self::$instance) {
			self::$instance = new self;
			SureCartExport::$export_instance = ExportExtension::getInstance();
		}
		return self::$instance;
	}

	/**
	 * SureCartExport constructor.
	 */
	public function __construct()
	{
		$this->plugin = Plugin::getInstance();
	}

	/**
	 * Export SureCart Products data
	 * 
	 * @param int $id Product ID
	 * @return void
	 */
	public function getSureCartProductDataMaster($id)
	{
		global $wpdb;

		if (!is_plugin_active('surecart/surecart.php')) {
			return;
		}

		$post_type = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_type FROM {$wpdb->posts} WHERE ID = %d",
				$id
			)
		);

		if ($post_type !== 'sc_product') {
			return;
		}


		// Get product meta
		$sku = get_post_meta($id, 'sku', true);
		$price = get_post_meta($id, 'price', true);
		if (empty($price))
			$price = get_post_meta($id, 'min_price_amount', true); // Fallback
		$sale_price = get_post_meta($id, 'sale_price', true);
		if (empty($sale_price))
			$sale_price = get_post_meta($id, 'scratch_display_amount', true); // Fallback

		$stock_enabled = get_post_meta($id, 'stock_enabled', true);
		$stock_quantity = get_post_meta($id, 'available_stock', true); // matched debug
		$allow_purchase_out_of_stock = get_post_meta($id, 'allow_out_of_stock_purchases', true); // matched debug
		$tax_enabled = get_post_meta($id, 'tax_enabled', true);
		$tax_status = get_post_meta($id, 'tax_status', true);
		$product_type = get_post_meta($id, 'product_type', true);
		$recurring_interval = get_post_meta($id, 'recurring_interval', true);
		$recurring_period = get_post_meta($id, 'recurring_period', true);
		$recurring_price = get_post_meta($id, 'recurring_price', true);
		$variants = get_post_meta($id, 'variants', true);
		$download_files = get_post_meta($id, 'download_files', true);

		// Featured image
		$thumbnail_id = (int) get_post_thumbnail_id($id);
		$featured_image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';

		// Gallery images
		$gallery_ids = get_post_meta($id, 'gallery_images', true);
		$gallery_urls = [];
		if (!empty($gallery_ids) && is_array($gallery_ids)) {
			foreach ($gallery_ids as $gallery_id) {
				$gallery_url = wp_get_attachment_url($gallery_id);
				if ($gallery_url) {
					$gallery_urls[] = $gallery_url;
				}
			}
		}

		// Product categories and tags
		$categories = wp_get_post_terms($id, 'sc_product_category', ['fields' => 'names']);
		if (is_wp_error($categories))
			$categories = [];
		$tags = wp_get_post_terms($id, 'sc_product_tag', ['fields' => 'names']);
		if (is_wp_error($tags))
			$tags = [];

		// Format download files
		$download_files_output = '';
		if (!empty($download_files) && is_array($download_files)) {
			foreach ($download_files as $file) {
				if (!empty($file['file'])) {
					$download_files_output .=
						'file_name:' . ($file['name'] ?? '') .
						',file_url:' . ($file['file'] ?? '') . ' | ';
				}
			}
			$download_files_output = rtrim($download_files_output, ' | ');
		}

		// Format variants
		$variants_output = '';
		if (!empty($variants) && is_array($variants)) {
			$variants_output = json_encode($variants);
		}

		// Get all custom meta
		$all_meta = get_post_meta($id);
		$custom_meta = [];
		foreach ($all_meta as $key => $values) {
			// Skip internal keys and already processed keys
			if (strpos($key, '_') === 0)
				continue;

			if (
				!in_array($key, [
					'sku',
					'price',
					'min_price_amount',
					'max_price_amount',
					'display_amount',
					'sale_price',
					'scratch_display_amount',
					'range_display_amount',
					'stock_enabled',
					'available_stock',
					'stock_quantity',
					'allow_out_of_stock_purchases',
					'purchase_limit',
					'tax_enabled',
					'tax_status',
					'shipping_enabled',
					'product_type',
					'recurring',
					'recurring_interval',
					'recurring_period',
					'recurring_price',
					'variants',
					'download_files',
					'gallery_images',
					'sc_id',
					'product'
				])
			) {
				$custom_meta[$key] = is_array($values) ? $values[0] : $values;
			}
		}

		$product_meta_output = '';
		if (!empty($custom_meta)) {
			foreach ($custom_meta as $key => $value) {
				$product_meta_output .= $key . ':' . (is_array($value) ? json_encode($value) : $value) . '|';
			}
			$product_meta_output = rtrim($product_meta_output, '|');
		}

		// Set export data
		SureCartExport::$export_instance->data[$id]['product_id'] = $id;
		SureCartExport::$export_instance->data[$id]['product_name'] = get_the_title($id);
		SureCartExport::$export_instance->data[$id]['post_title'] = get_the_title($id);
		SureCartExport::$export_instance->data[$id]['post_content'] = get_post_field('post_content', $id);
		SureCartExport::$export_instance->data[$id]['post_excerpt'] = get_post_field('post_excerpt', $id);
		SureCartExport::$export_instance->data[$id]['post_status'] = get_post_status($id);
		SureCartExport::$export_instance->data[$id]['sku'] = $sku ?: '';
		SureCartExport::$export_instance->data[$id]['price'] = $price ?: '';
		SureCartExport::$export_instance->data[$id]['sale_price'] = $sale_price ?: '';
		SureCartExport::$export_instance->data[$id]['stock_enabled'] = $stock_enabled ? 'yes' : 'no';
		SureCartExport::$export_instance->data[$id]['stock_quantity'] = $stock_quantity ?: 0;
		SureCartExport::$export_instance->data[$id]['allow_purchase_out_of_stock'] = $allow_purchase_out_of_stock ? 'yes' : 'no';
		SureCartExport::$export_instance->data[$id]['tax_enabled'] = $tax_enabled ? 'yes' : 'no';
		SureCartExport::$export_instance->data[$id]['tax_status'] = $tax_status ?: '';
		SureCartExport::$export_instance->data[$id]['featured_image'] = $featured_image_url;
		SureCartExport::$export_instance->data[$id]['gallery_images'] = implode(',', $gallery_urls);
		SureCartExport::$export_instance->data[$id]['product_type'] = $product_type ?: '';
		SureCartExport::$export_instance->data[$id]['recurring_interval'] = $recurring_interval ?: '';
		SureCartExport::$export_instance->data[$id]['recurring_period'] = $recurring_period ?: '';
		SureCartExport::$export_instance->data[$id]['recurring_price'] = $recurring_price ?: '';
		SureCartExport::$export_instance->data[$id]['variants'] = $variants_output;
		SureCartExport::$export_instance->data[$id]['product_categories'] = !empty($categories) ? implode(',', $categories) : '';
		SureCartExport::$export_instance->data[$id]['product_tags'] = !empty($tags) ? implode(',', $tags) : '';
		SureCartExport::$export_instance->data[$id]['download_files'] = $download_files_output;
		SureCartExport::$export_instance->data[$id]['product_meta'] = $product_meta_output;
	}


	/**
	 * Export SureCart Customers data
	 * 
	 * @param int $id Customer ID
	 * @return void
	 */
	public function getSureCartCustomerDataMaster($id)
	{
		if (!is_plugin_active('surecart/surecart.php') || !class_exists('\\SureCart\\Models\\Customer')) {
			return;
		}

		// Support both cloud UUID (from PostExport) and WP post ID (from WPQueryExport)
		$lookup_id = $id;
		if (is_numeric($id) && (int) $id === (float) $id && $id < 2147483647) {
			$sc_id = get_post_meta($id, 'sc_id', true);
			if ($sc_id) {
				$lookup_id = $sc_id;
			}
		}

		$customer = \SureCart\Models\Customer::find($lookup_id);
		// Handle WP_Error or empty result - try post meta sc_id if first find failed
		if ((is_wp_error($customer) || !$customer) && $lookup_id === $id) {
			$sc_id = get_post_meta($id, 'sc_id', true);
			if ($sc_id) {
				$customer = \SureCart\Models\Customer::find($sc_id);
				if ($customer) {
					$lookup_id = $sc_id;
				}
			}
		}

		// Use meta fallback if still no valid customer object
		$use_meta_fallback = false;
		if (is_wp_error($customer) || !$customer) {
			$use_meta_fallback = true;
		}
		$purchase_count = 0;
		$lifetime_value = 0;
		$order_ids = '';
		$notes = '';

		if (!$use_meta_fallback) {
			// Get customer data from Model
			$customer_email = $customer->email ?? '';
			$customer_name = $customer->name ?? '';
			$first_name = $customer->first_name ?? '';
			$last_name = $customer->last_name ?? '';
			$user_id = $customer->wp_user_id ?? 0;
			$phone_number = $customer->phone ?? $customer->phone_number ?? '';
			$date_created = $customer->created_at ?? '';

			// Billing Address (Customer level)
			$billing_address = $customer->billing_address ?? [];
			if (is_object($billing_address) && method_exists($billing_address, 'toArray')) {
				$billing_address = $billing_address->toArray();
			} elseif (is_object($billing_address)) {
				$billing_address = json_decode(wp_json_encode($billing_address), true);
			} else {
				$billing_address = (array) $billing_address;
			}

			// Metadata
			$metadata = (array) ($customer->metadata ?? []);

			// Notes from customer
			$notes = $customer->notes ?? '';
			if (is_array($notes)) {
				$notes = implode("\n", $notes);
			}

			// Fetch purchase_count, lifetime_value, order_ids from Order API
			if (class_exists('\\SureCart\\Models\\Order') && !empty($customer->id)) {
				try {
					$orders = \SureCart\Models\Order::where(['customer_id' => $customer->id, 'limit' => 500])->get();
					if (!empty($orders)) {
						$order_id_list = [];
						$total = 0;
						foreach ($orders as $o) {
							$order_id_list[] = $o->id;
							$total += (int) ($o->total_amount ?? 0);
						}
						$purchase_count = count($order_id_list);
						$lifetime_value = $total / 100; // API amounts are typically in cents
						$order_ids = implode(',', $order_id_list);
					}
				} catch (\Throwable $e) {
				}
			}
		} else {
			// Fallback to Post Meta
			$customer_email = get_post_meta($id, 'sc_customer_email', true);
			$first_name = get_post_meta($id, 'sc_first_name', true);
			$last_name = get_post_meta($id, 'sc_last_name', true);
			$customer_name = $first_name . ' ' . $last_name;
			if (empty(trim($customer_name))) {
				$customer_name = get_post_meta($id, 'sc_customer_name', true);
			}
			if (empty(trim($customer_name))) {
				$customer_name = get_the_title($id);
			}

			// User ID Lookup
			$user_id = get_post_meta($id, 'sc_wp_user_id', true);
			if (empty($user_id) && !empty($customer_email)) {
				$user = get_user_by('email', $customer_email);
				if ($user) {
					$user_id = $user->ID;
				}
			}
			if (!$user_id)
				$user_id = 0;

			$phone_number = get_post_meta($id, 'sc_phone_number', true);
			$date_created = get_the_date('Y-m-d H:i:s', $id);

			// Construct billing address from meta
			// 1. Try generic array field
			$meta_billing = get_post_meta($id, 'sc_billing_address', true);
			if (is_array($meta_billing)) {
				$billing_address = [
					'address_1' => $meta_billing['line_1'] ?? $meta_billing['address_1'] ?? '',
					'address_2' => $meta_billing['line_2'] ?? $meta_billing['address_2'] ?? '',
					'city' => $meta_billing['city'] ?? '',
					'state' => $meta_billing['state'] ?? '',
					'postal_code' => $meta_billing['postal_code'] ?? '',
					'country' => $meta_billing['country'] ?? '',
				];
			} else {
				// 2. Try individual fields
				$billing_address = [
					'address_1' => get_post_meta($id, 'sc_billing_address_line_1', true),
					'address_2' => get_post_meta($id, 'sc_billing_address_line_2', true),
					'city' => get_post_meta($id, 'sc_billing_city', true),
					'state' => '',
					'postal_code' => '',
					'country' => get_post_meta($id, 'sc_billing_country', true),
				];
			}

			// Metadata (raw or processed?)
			$metadata = [];
			// Maybe sc_metadata?
			$meta_json = get_post_meta($id, 'sc_metadata', true);
			if ($meta_json) {
				if (is_array($meta_json))
					$metadata = $meta_json;
				else
					$metadata = json_decode($meta_json, true) ?: [];
			}

			$notes = get_post_meta($id, 'sc_notes', true) ?: '';
		}


		$billing_address_line_1 = $billing_address['line_1'] ?? $billing_address['address_1'] ?? '';
		$billing_address_line_2 = $billing_address['line_2'] ?? $billing_address['address_2'] ?? '';
		$billing_city = $billing_address['city'] ?? '';
		$billing_state = $billing_address['state'] ?? '';
		$billing_postal_code = $billing_address['postal_code'] ?? '';
		$billing_country = $billing_address['country'] ?? '';

		$user_login = '';
		$user_email = '';
		if ($user_id > 0) {
			$user_data = get_userdata($user_id);
			if ($user_data) {
				$user_login = $user_data->user_login;
				$user_email = $user_data->user_email;
			}
		}

		// Set export data
		$customer_sc_id = !$use_meta_fallback ? ($customer->id ?? '') : get_post_meta($id, 'sc_id', true);
		SureCartExport::$export_instance->data[$id]['sc_id'] = $customer_sc_id ?: '';
		SureCartExport::$export_instance->data[$id]['customer_id'] = $id;
		SureCartExport::$export_instance->data[$id]['customer_email'] = $customer_email ?: '';
		SureCartExport::$export_instance->data[$id]['customer_name'] = $customer_name ?: '';
		SureCartExport::$export_instance->data[$id]['first_name'] = $first_name ?: '';
		SureCartExport::$export_instance->data[$id]['last_name'] = $last_name ?: '';
		SureCartExport::$export_instance->data[$id]['user_id'] = $user_id ?: 0;
		SureCartExport::$export_instance->data[$id]['user_login'] = $user_login;
		SureCartExport::$export_instance->data[$id]['user_email'] = $user_email;
		SureCartExport::$export_instance->data[$id]['phone_number'] = $phone_number ?: '';
		SureCartExport::$export_instance->data[$id]['date_created'] = $date_created;
		SureCartExport::$export_instance->data[$id]['billing_address_line_1'] = $billing_address_line_1 ?: '';
		SureCartExport::$export_instance->data[$id]['billing_address_line_2'] = $billing_address_line_2 ?: '';
		SureCartExport::$export_instance->data[$id]['billing_city'] = $billing_city ?: '';
		SureCartExport::$export_instance->data[$id]['billing_state'] = $billing_state ?: '';
		SureCartExport::$export_instance->data[$id]['billing_postal_code'] = $billing_postal_code ?: '';
		SureCartExport::$export_instance->data[$id]['billing_country'] = $billing_country ?: '';
		SureCartExport::$export_instance->data[$id]['purchase_count'] = $purchase_count;
		SureCartExport::$export_instance->data[$id]['lifetime_value'] = $lifetime_value;
		SureCartExport::$export_instance->data[$id]['order_ids'] = $order_ids ?: '';
		SureCartExport::$export_instance->data[$id]['notes'] = $notes ?: '';

		$metadata_output = '';
		if (!empty($metadata)) {
			foreach ($metadata as $key => $value) {
				$metadata_output .= $key . ':' . (is_array($value) ? json_encode($value) : $value) . '|';
			}
		}
		SureCartExport::$export_instance->data[$id]['customer_meta'] = rtrim($metadata_output, '|');
	}

	/**
	 * Export SureCart Coupons data
	 * 
	 * @param int $id Coupon ID
	 * @return void
	 */
	public function getSureCartCouponDataMaster($id)
	{
		// 1. Determine if $id is a WP Post ID or a SureCart UUID
		$is_uuid = !is_numeric($id) && strlen($id) > 10; // Simple heuristic for UUID

		$coupon_code = '';
		$coupon_model = null;

		if (!$is_uuid) {
			// It's a WP Post ID (Imported Coupon)
			$coupon_code = get_post_meta($id, 'sc_coupon_code', true);

			// Try getting model via meta
			if (is_plugin_active('surecart/surecart.php') && class_exists('\\SureCart\\Models\\Coupon')) {
				$sc_id = get_post_meta($id, 'sc_id', true);
				if (!empty($sc_id)) {
					try {
						$coupon_model = \SureCart\Models\Coupon::find($sc_id);
					} catch (\Exception $e) { /* ignore */
					}
				}
			}
		} else {
			// It's a SureCart UUID (Native Coupon exported via custom flow?)
			// We cannot use get_post_meta on a UUID.
			if (is_plugin_active('surecart/surecart.php') && class_exists('\\SureCart\\Models\\Coupon')) {
				try {
					$coupon_model = \SureCart\Models\Coupon::find($id);
				} catch (\Exception $e) { /* ignore */
				}
			}
		}

		if (empty($coupon_code)) {
			if ($coupon_model) {
				$coupon_code = $coupon_model->code;
			} else {
				// Fallback to title
				$post = get_post($id);
				if ($post) {
					$coupon_code = $post->post_title;
				}
			}
		}


		// Define mapping: Export Key => [Meta Key, Model Property]
		$field_map = [
			'promotion_codes' => ['sc_promotion_code', 'promotion_code'], // New field
			'discount_type' => ['sc_discount_type', 'discount_type'],
			'discount_amount' => ['sc_discount_amount', 'amount'],
			'status' => ['sc_status', 'status'],
			'usage_limit' => ['sc_usage_limit', 'max_redemptions'],
			'usage_count' => ['sc_usage_count', 'times_redeemed'],
			'usage_limit_per_user' => ['sc_usage_limit_per_user', 'max_redemptions_per_customer'],
			'start_date' => ['sc_start_date', 'start_date'],
			'end_date' => ['sc_end_date', 'end_date'],
			'minimum_amount' => ['sc_minimum_amount', 'min_subtotal_amount'], // FIXED: min_amount -> min_subtotal_amount
			'maximum_amount' => ['sc_maximum_amount', 'max_subtotal_amount'], // FIXED: max_amount -> max_subtotal_amount
			'applies_to' => ['sc_applies_to', 'applies_to'],
			'duration' => ['sc_duration', 'duration'],
			'duration_in_months' => ['sc_duration_in_months', 'duration_in_months'],
			'currency' => ['sc_currency', 'currency'],
			'archived' => ['sc_archived', 'archived'],
			// Arrays need special handling
			'product_requirements' => ['sc_product_ids', 'product_ids'],
			'excluded_products' => ['sc_excluded_product_ids', 'excluded_product_ids'],
			'category_requirements' => ['sc_category_ids', 'category_ids'],
			'excluded_categories' => ['sc_excluded_category_ids', 'excluded_category_ids']
		];

		// Initialize data array
		SureCartExport::$export_instance->data[$id]['coupon_id'] = $id;

		// 1. Code
		$code_val = '';
		if (!$is_uuid) {
			$code_val = get_post_meta($id, 'sc_coupon_code', true);
		}
		if (empty($code_val) && $coupon_model) {
			$code_val = $coupon_model->code ?? $coupon_model->promotion_code ?? $coupon_model->name;
		}
		if (empty($code_val) && !$is_uuid) {
			$post = get_post($id);
			if ($post)
				$code_val = $post->post_title;
		}
		SureCartExport::$export_instance->data[$id]['coupon_code'] = $code_val;

		// NEW: Promotion Codes (if distinct from coupon_code)
		// Usually coupon_code IS the promotion code. But if user requests it separate:
		$promo_codes_val = '';
		if (!$is_uuid) {
			$promo_codes_val = get_post_meta($id, 'sc_promotion_code', true);
		}
		if (empty($promo_codes_val) && $coupon_model) {
			$p_code = $coupon_model->promotion_code ?? null;
			if (!empty($p_code)) {
				$promo_codes_val = $p_code;
			} else {
				$promo_codes_val = $code_val;
			}
		}
		SureCartExport::$export_instance->data[$id]['promotion_codes'] = $promo_codes_val;

		// 2. Discount Type & Amount
		$disc_type = '';
		$disc_amount = '';
		if (!$is_uuid) {
			$disc_type = get_post_meta($id, 'sc_discount_type', true);
			$disc_amount = get_post_meta($id, 'sc_discount_amount', true);
		}

		if (empty($disc_type) && $coupon_model) {
			if (!empty($coupon_model->percent_off)) {
				$disc_type = 'percentage';
				$disc_amount = $coupon_model->percent_off;
			} elseif (!empty($coupon_model->amount_off)) {
				$disc_type = 'fixed';
				$disc_amount = $coupon_model->amount_off;
			} else {
				$disc_type = 'fixed';
				$disc_amount = 0;
			}
		}
		SureCartExport::$export_instance->data[$id]['discount_type'] = $disc_type;
		SureCartExport::$export_instance->data[$id]['discount_amount'] = $disc_amount;

		// 3. Status
		$status = '';
		if (!$is_uuid) {
			$status = get_post_meta($id, 'sc_status', true);
		}
		if (empty($status) && $coupon_model) {
			if (!empty($coupon_model->archived)) {
				$status = 'archived';
			} elseif (!empty($coupon_model->expired)) {
				$status = 'expired';
			} else {
				$status = 'active';
			}
		}
		SureCartExport::$export_instance->data[$id]['status'] = $status;

		// 4. Dates
		$start_date = '';
		if (!$is_uuid) {
			$start_date = get_post_meta($id, 'sc_start_date', true);
		}
		if (empty($start_date) && $coupon_model) {
			$start_date = $coupon_model->created_at;
			if (is_numeric($start_date))
				$start_date = date('Y-m-d H:i:s', $start_date);
		}
		SureCartExport::$export_instance->data[$id]['start_date'] = $start_date;

		$end_date = '';
		if (!$is_uuid) {
			$end_date = get_post_meta($id, 'sc_end_date', true);
		}
		if (empty($end_date) && $coupon_model) {
			$end_date = $coupon_model->redeem_by;
			if (is_numeric($end_date))
				$end_date = date('Y-m-d H:i:s', $end_date);
		}
		SureCartExport::$export_instance->data[$id]['end_date'] = $end_date;

		// 5. Limits & Amounts
		// Iterate through map
		foreach ($field_map as $export_key => $sources) {
			$meta_key = $sources[0];
			$model_prop = $sources[1];

			// Skip manually handled fields
			if (in_array($export_key, ['promotion_codes', 'discount_type', 'discount_amount', 'status', 'start_date', 'end_date']))
				continue;

			$val = '';
			if (!$is_uuid) {
				$val = get_post_meta($id, $meta_key, true);
			}
			if (empty($val) && $coupon_model) {
				$p_val = $coupon_model->$model_prop ?? null;
				if (!is_null($p_val)) {
					if (is_array($p_val))
						$val = implode(',', $p_val);
					else
						$val = $p_val;
				}
			}
			SureCartExport::$export_instance->data[$id][$export_key] = $val;
		}

		// 6. Applies To
		// (Handled in loop above via field map? No, appies_to needs logic?)
		// Actually, field map handles basic scalar. Check logic below.

		// Re-check logic for applies_to if logic is complex
		$applies_to = '';
		if (!$is_uuid) {
			$applies_to = get_post_meta($id, 'sc_applies_to', true);
		}
		if (empty($applies_to) && $coupon_model) {
			$applies_to = $coupon_model->filter_match_type ?? 'all';
		}
		SureCartExport::$export_instance->data[$id]['applies_to'] = $applies_to ?: 'all';


		// 7. First Order Only
		$first_order = '';
		if (!$is_uuid) {
			$first_order = get_post_meta($id, 'sc_first_order_only', true);
		}
		if ($first_order === '' && $coupon_model) {
			$first_order = $coupon_model->first_order_only ?? 0;
		}
		SureCartExport::$export_instance->data[$id]['first_order_only'] = $first_order;


		// Handle Coupon Meta (JSON)
		$sc_metadata = '';
		if (!$is_uuid) {
			$sc_metadata = get_post_meta($id, 'sc_metadata', true);
		}
		if (empty($sc_metadata) && $coupon_model) {
			$sc_metadata = $coupon_model->metadata;
		}

		$metadata_output = '';
		if (!empty($sc_metadata)) {
			$sc_metadata = (array) $sc_metadata;
			foreach ($sc_metadata as $key => $value) {
				$metadata_output .= $key . ':' . (is_array($value) ? json_encode($value) : $value) . '|';
			}
		}
		SureCartExport::$export_instance->data[$id]['coupon_meta'] = rtrim($metadata_output, '|');
	}
	

}

