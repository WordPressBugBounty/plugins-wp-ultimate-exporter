<?php
/**
 * WP Ultimate Exporter plugin file.
 *
 * Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com
 */

namespace Smackcoders\SMEXP;
use Smackcoders\SMEXP\WC_Coupon;
if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

/**
 * Class WooCommerceExport
 * @package Smackcoders\WCSV
 */
// require_once dirname(__FILE__) . '/ExportExtension.php';


require_once dirname(__FILE__) . '/ElementorExport.php';

// use Smackcoders\SMEXP\ExportExtension;

class WooCommerceExport extends ExportExtension{
	protected static $instance = null,$mapping_instance,$export_handler,$export_instance,$post_export;
	public $offset = 0;	
	public $limit = 1000;	
	public $totalRowCount;	
	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
			WooCommerceExport::$export_instance = ExportExtension::getInstance();
			WooCommerceExport::$post_export = PostExport::getInstance();
		}
		return self::$instance;
	}

	/**
	 * WooCommerceExport constructor.
	 */
	public function __construct() {
		$this->plugin = Plugin::getInstance();
	}

	/**
	 * Export woocommerce orders
	 * @param $id
	 * @param $type
	 * @param $optional
	 * @return bool
	 */
	public function getCouponsData($id, $type, $optionalType){
		$coupon_id = $id;
		$c = new \WC_Coupon($coupon_id);
		$DiscountType = $c->get_discount_type();	
		$CouponAmount = $c->get_amount();
		$IndividualUse = $c->get_individual_use();
		$ProductIDs = $c->get_product_ids();
		$ExcludeProductIDs = $c->get_excluded_product_ids(); 
		$UsageLimit = $c->get_usage_limit();
		$UsageLimitPerUser = $c->get_usage_limit_per_user();
		$LimitUsageToXItems = $c->get_limit_usage_to_x_items();
		$ExpiryDate = $c->get_date_expires();
		$FreeShipping = $c->get_free_shipping();
		$ExcludeSaleItems = $c->get_exclude_sale_items();
		$MinimumAmount = $c->get_minimum_amount();
		$MaximumAmount = $c->get_maximum_amount();
		$CustomerEmail = $c->get_email_restrictions();
		$ExcludedProductCategories = $c->get_excluded_product_categories();
		$ProductCategories = $c->get_product_categories();

		$product_Ids = array(); 
		$Exclude_Product_IDs =array();
		$product_cat = array();
		$exclude_cat = array();
		$cus_email =  array();
		foreach ($ProductCategories as $p_IDs) {
			$product_cat[] = $p_IDs;
		}
		foreach ($ExcludedProductCategories as $p_IDs) {
			$exclude_cat[] = $p_IDs;
		}

		foreach ($CustomerEmail as $p_IDs) {
			$cus_email[] = $p_IDs;
		}

		foreach ($ProductIDs as $p_IDs) {
			$product_Ids[] = $p_IDs;
		}
		foreach($ExcludeProductIDs as $Exclude_PIds){
			$Exclude_Product_IDs[] = $Exclude_PIds;
		}

		WooCommerceExport::$export_instance->data[$id]['exclude_product_categories'] = !empty($exclude_cat) ? implode(',', $exclude_cat) : '';
		WooCommerceExport::$export_instance->data[$id]['customer_email'] = !empty($cus_email) ? implode(',', $cus_email) : '';
		WooCommerceExport::$export_instance->data[$id]['product_categories'] = !empty($product_cat) ? implode(',', $product_cat) : '';



		WooCommerceExport::$export_instance->data[$id]['discount_type'] = $DiscountType;
		WooCommerceExport::$export_instance->data[$id]['coupon_amount'] = $CouponAmount;
		WooCommerceExport::$export_instance->data[$id]['individual_use'] = $IndividualUse ? 'yes' : 'no';
		WooCommerceExport::$export_instance->data[$id]['usage_limit'] = $UsageLimit;
		WooCommerceExport::$export_instance->data[$id]['usage_limit_per_user'] = $UsageLimitPerUser;
		WooCommerceExport::$export_instance->data[$id]['limit_usage_to_x_items'] = $LimitUsageToXItems;
		WooCommerceExport::$export_instance->data[$id]['expiry_date'] = $ExpiryDate ? $ExpiryDate->date('Y-m-d H:i:s') : null;
		WooCommerceExport::$export_instance->data[$id]['free_shipping'] = $FreeShipping ? 'yes' : 'no';
		WooCommerceExport::$export_instance->data[$id]['exclude_sale_items'] = $ExcludeSaleItems ? 'yes' : 'no';
		WooCommerceExport::$export_instance->data[$id]['minimum_amount'] = $MinimumAmount;
		WooCommerceExport::$export_instance->data[$id]['maximum_amount'] = $MaximumAmount;	
		// Handle the product IDs and excluded product IDs with implode only if they are not empty
		WooCommerceExport::$export_instance->data[$id]['product_ids'] = !empty($product_Ids) ? implode(',', $product_Ids) : '';
		WooCommerceExport::$export_instance->data[$id]['exclude_product_ids'] = !empty($Exclude_Product_IDs) ? implode(',', $Exclude_Product_IDs) : '';

	}
	public function getWooComOrderData($id, $type, $optional)
	{ 
		$order = wc_get_order($id);
		$order_data = $order->get_data();
		$orderid = self::$export_instance->data[$id]['ID'];
		WooCommerceExport::$export_instance->data[$id]['ORDERID'] = $orderid;
		//fetch the order data
		$date_created_object = $order_data['date_created'];
		$formatted_date_time = $date_created_object->date('m/d/Y H:i:s');
		WooCommerceExport::$export_instance->data[$id]['order_date'] = $formatted_date_time;
		$customer_note = $order_data['customer_note'];
		WooCommerceExport::$export_instance->data[$id]['customer_note'] = $customer_note;
		$billing_details = $order_data['billing'];
		$order_status = $order_data['status'];
		WooCommerceExport::$export_instance->data[$id]['order_status'] = 'wc-'.$order_status;
		$_billing_first_name  = $billing_details['first_name'];
		WooCommerceExport::$export_instance->data[$id]['_billing_first_name'] = $_billing_first_name;
		$_billing_last_name   = $billing_details['last_name'];
		WooCommerceExport::$export_instance->data[$id]['_billing_last_name'] = $_billing_last_name;
		$_billing_company     = $billing_details['company'];
		WooCommerceExport::$export_instance->data[$id]['_billing_company'] = $_billing_company;
		$_billing_address_1   = $billing_details['address_1'];
		WooCommerceExport::$export_instance->data[$id]['_billing_address_1'] = $_billing_address_1;
		$_billing_address_2   = $billing_details['address_2'];
		WooCommerceExport::$export_instance->data[$id]['_billing_address_2'] = $_billing_address_2;
		$_billing_city        = $billing_details['city'];
		WooCommerceExport::$export_instance->data[$id]['_billing_city'] = $_billing_city;
		$_billing_state       = $billing_details['state'];
		WooCommerceExport::$export_instance->data[$id]['_billing_state'] = $_billing_state;
		$_billing_postcode    = $billing_details['postcode'];
		WooCommerceExport::$export_instance->data[$id]['_billing_postcode'] = $_billing_postcode;
		$_billing_country     = $billing_details['country'];
		WooCommerceExport::$export_instance->data[$id]['_billing_country'] = $_billing_country;
		$_order_currency = $order_data['currency'];
		WooCommerceExport::$export_instance->data[$id]['_order_currency'] = $_order_currency;
		$_order_shipping_tax = $order_data['shipping_tax'];
		WooCommerceExport::$export_instance->data[$id]['_order_shipping_tax'] = $_order_shipping_tax;
		$_order_shipping = $order->get_shipping_total();
		WooCommerceExport::$export_instance->data[$id]['_order_shipping'] = $_order_shipping;
		$_order_tax = $order_data['cart_tax'];
		WooCommerceExport::$export_instance->data[$id]['_order_tax'] = $_order_tax;	
		$_customer_user = $order_data['customer_id'];
		if($_customer_user != 0){
			$user_data = get_userdata($_customer_user);
			$user_email = $user_data->user_email;
			WooCommerceExport::$export_instance->data[$id]['_customer_user'] = $user_email ;
		}	
		$_billing_email = $billing_details['email'];
		WooCommerceExport::$export_instance->data[$id]['_billing_email'] = $_billing_email;

		$_billing_phone = $billing_details['phone'];
		WooCommerceExport::$export_instance->data[$id]['_billing_phone'] = $_billing_phone;

		$_shipping_first_name = $order_data['shipping']['first_name'];
		WooCommerceExport::$export_instance->data[$id]['_shipping_first_name'] = $_shipping_first_name;

		$_shipping_last_name = $order_data['shipping']['last_name'];
		WooCommerceExport::$export_instance->data[$id]['_shipping_last_name'] = $_shipping_last_name;

		$_shipping_company = $order_data['shipping']['company'];
		WooCommerceExport::$export_instance->data[$id]['_shipping_company'] = $_shipping_company;

		$_shipping_address_1 = $order_data['shipping']['address_1'];
		WooCommerceExport::$export_instance->data[$id]['_shipping_address_1'] = $_shipping_address_1;

		$_shipping_address_2 = $order_data['shipping']['address_2'];
		WooCommerceExport::$export_instance->data[$id]['_shipping_address_2'] = $_shipping_address_2;

		$_shipping_city = $order_data['shipping']['city'];
		WooCommerceExport::$export_instance->data[$id]['_shipping_city'] = $_shipping_city;

		$_shipping_state = $order_data['shipping']['state'];
		WooCommerceExport::$export_instance->data[$id]['_shipping_state'] = $_shipping_state;

		$_shipping_postcode = $order_data['shipping']['postcode'];
		WooCommerceExport::$export_instance->data[$id]['_shipping_postcode'] = $_shipping_postcode;

		$_shipping_country = $order_data['shipping']['country'];
		WooCommerceExport::$export_instance->data[$id]['_shipping_country'] = $_shipping_country;

		$_shipping_phone = $order_data['shipping']['phone'];
		WooCommerceExport::$export_instance->data[$id]['_shipping_phone'] = $_shipping_phone;

		$_order_total = $order_data['total'];
		WooCommerceExport::$export_instance->data[$id]['_order_total'] = $_order_total;

		$_cart_discount = $order_data['discount_total'];
		WooCommerceExport::$export_instance->data[$id]['_cart_discount'] = $_cart_discount;

		$_cart_discount_tax = $order_data['discount_tax'];
		WooCommerceExport::$export_instance->data[$id]['_cart_discount_tax'] = $_cart_discount_tax;

		$_payment_method = $order_data['payment_method'];
		WooCommerceExport::$export_instance->data[$id]['_payment_method'] = $_payment_method;

		$_recorded_sales= $order_data['recorded_sales'];
		if($_recorded_sales > 0){
			$_recorded_sales = 'yes';
		}else{
			$_recorded_sales = 'no';
		}
		WooCommerceExport::$export_instance->data[$id]['recorded_sales'] = $_recorded_sales;

		$_payment_method_title = $order_data['payment_method_title'];
		WooCommerceExport::$export_instance->data[$id]['_payment_method_title'] = $_payment_method_title;

		$_transaction_id = $order_data['transaction_id'];
		WooCommerceExport::$export_instance->data[$id]['_transaction_id'] = $_transaction_id;
		$line_items = $order->get_items();
		$mycoupon = $order->get_coupons();

		if (!empty($mycoupon)) {
			foreach ($mycoupon as $coupon) {
				$coupon_code = $coupon->get_code();
			}
		}
		if(!empty($line_items)){
			foreach ($line_items as $item_id => $item) {
				$item_names[] = $item->get_name();
				$product_type[] = $item->get_type();
				$variation_id[] = $item->get_variation_id();
				$product_id[] = $item->get_product_id();
				$subtotal[] = $item->get_subtotal();
				$subtotal_tax[] = $item->get_subtotal_tax();
				$total[] = $item->get_total();
				$total_tax[] = $item->get_total_tax();
				$item_tax_data[] = $item->get_taxes();
				if (!empty($item_tax_data) && is_array($item_tax_data)) {
					if(!empty($item_tax_data[0]['total']) || !empty($item_tax_data[0]['subtotal'])){
						$line_tax[] = implode(',', $item_tax_data[0]['total']);
						$line_tax[] = implode(',', $item_tax_data[0]['subtotal']);
					}else{
						$line_tax = array();
					}
				}	
				$tax_class[] = $item->get_tax_class();
				$quantity[] = $item->get_quantity();
			}
			if (!empty($coupon_code)) {
				$item_names[] = $coupon_code;
				$product_type[] = 'coupon';
			}
			//itemdata
			self::$export_instance->data[$id]['item_name'] = implode(',', $item_names);
			self::$export_instance->data[$id]['item_type'] = implode(',', $product_type);
			self::$export_instance->data[$id]['item_variation_id'] = implode(',', $variation_id);
			self::$export_instance->data[$id]['item_product_id']  = implode(',', $product_id);
			self::$export_instance->data[$id]['item_line_subtotal'] = implode(',', $subtotal);
			self::$export_instance->data[$id]['item_line_subtotal_tax'] = implode(',', $subtotal_tax);
			self::$export_instance->data[$id]['item_line_total'] = implode(',', $total);
			self::$export_instance->data[$id]['item_line_tax'] = implode(',', $total_tax);
			self::$export_instance->data[$id]['item_line_tax_data'] = implode(',', $line_tax);
			self::$export_instance->data[$id]['item_tax_class'] = implode(',', $tax_class);
			self::$export_instance->data[$id]['item_qty'] = implode(',', $quantity);
		}
		//fee data
		$fees = $order->get_fees();
		if(!empty($fees)){
			foreach ($fees as $fee) {
				$fee_name[] = $fee->get_name();
				$fee_type[] = $fee->get_type(); // Assuming there's a get_type() method; check the documentation
				$fee_tax_class[] = $fee->get_tax_class();
				$fee_line_total[] = $fee->get_total();
				$fee_line_tax[] = $fee->get_total_tax();
				// $fee_line_subtotal[] = $fee->get_subtotal();
				// $fee_line_subtotal_tax[] = $fee->get_subtotal_tax();
				$tax_data = $fee->get_taxes();
				if(!empty($tax_data['total'])){
					$fee_line_tax_data[] = $tax_data['total'];
				}else{
					$fee_line_tax_data = array();
				}	
			}
			//fee data
			self::$export_instance->data[$id]['fee_name'] = implode(',', $fee_name);
			self::$export_instance->data[$id]['fee_type'] = implode(',', $fee_type);
			//self::$export_instance->data[$id]['fee_line_subtotal'] = implode(',', $fee_line_subtotal);
			//self::$export_instance->data[$id]['fee_line_subtotal_tax'] = implode(',', $fee_line_subtotal_tax);
			self::$export_instance->data[$id]['fee_line_total'] = implode(',', $fee_line_total);
			self::$export_instance->data[$id]['fee_line_tax'] = implode(',', $fee_line_tax);
			self::$export_instance->data[$id]['fee_line_tax_data'] = implode(',',$fee_line_tax_data );	
			self::$export_instance->data[$id]['fee_tax_class'] = implode(',', $fee_tax_class);
		}
		//shipment data fetch
		$shipping_methods = $order->get_shipping_methods();
		if(!empty($shipping_methods)){
			foreach ($shipping_methods as $shipping_method) {
				$shipment_name[] = $shipping_method->get_method_title(); 
				$shipment_method_id[] = $shipping_method->get_method_id(); 
				$shipment_cost[] = $shipping_method->get_total(); 
				$shipment_taxe_data = $shipping_method->get_taxes(); 
				if(!empty($shipment_taxe_data['total'])){
					$shipment_taxes[] = $shipment_taxe_data['total'];
				}else{
					$shipment_taxes = array();
				}	
			}
			// shipment data
			self::$export_instance->data[$id]['shipment_name'] = implode(',', $shipment_name);
			self::$export_instance->data[$id]['shipment_method_id'] = implode(',', $shipment_method_id);
			self::$export_instance->data[$id]['shipment_cost'] = implode(',', $shipment_cost);	
			self::$export_instance->data[$id]['shipment_taxes'] = implode(',', $shipment_taxes);
		}

		if(is_plugin_active('advanced-product-fields-for-woocommerce/advanced-product-fields-for-woocommerce.php') ) {
			$this->getPPOMData($id,$product_id);
		}
        if (is_plugin_active('woo-extra-product-options/woo-extra-product-options.php')) {
			$this->getEPOData($id,$product_id);
		}
		if (is_plugin_active('woo-custom-product-addons/start.php')) {
			$this->getWCPAData($id,$product_id);
		}	
		if ( is_plugin_active( 'yith-woocommerce-order-tracking-premium/init.php' ) ) {
			self::$export_instance->data[$id]['ywot_tracking_code'] = 	$order->get_meta('ywot_tracking_code', true);
			self::$export_instance->data[$id]['ywot_tracking_postcode'] = $order->get_meta('ywot_tracking_postcode', true);
			self::$export_instance->data[$id]['ywot_carrier_id'] = $order->get_meta('ywot_carrier_id', true);
			self::$export_instance->data[$id]['ywot_pick_up_date'] =  $order->get_meta('ywot_pick_up_date', true);
			self::$export_instance->data[$id]['ywot_estimated_delivery_date'] =  $order->get_meta('ywot_estimated_delivery_date', true);
			self::$export_instance->data[$id]['ywot_picked_up'] =  $order->get_meta('ywot_picked_up', true);	
		}
	

	}

	/**
	 * Code for PPOM export
	 * @param $id
	 * @param $product_id
	 */
	public function getPPOMData($order_id, $product_ids)
	{

		global $wpdb;
		$table_name = $wpdb->prefix . 'nm_personalized';

		// Fetch all the PPOM field setups from the `wp_nm_personalized` table
		$field_setups = $wpdb->get_results( "SELECT * FROM $table_name" );

		if ( !empty( $field_setups ) ) {

			foreach ( $field_setups as $setup ) {
				$meta_data = json_decode( $setup->the_meta, true );  // Unserialize the field meta data


				// Display the unserialized field data if available
				if ( is_array( $meta_data ) ) {

					foreach ( $meta_data as $key => $field ) {
						$pro_meta_fields[$field['title']] = $field['data_name'];
					}


				} 
			}

		} 



		// Get order items for the specified order ID
		$order_items = wc_get_order( $order_id )->get_items();

		foreach ( $order_items as $item_id => $item ) {
			// Get the meta data for each order item
			$item_meta = wc_get_order_item_meta( $item_id, '', false );

			foreach ( $item_meta as $meta_key => $meta_value ) {

				if ( array_key_exists($meta_key, $pro_meta_fields)) { // Change this condition based on your actual meta key format

					self::$export_instance->data[$order_id][$meta_key] = $meta_value[0]; // Store each field key-value pair

				}
			}
		}


	}
	public function getEPOData($order_id, $product_ids)
	{
		global $wpdb;
	
		// Fetch all EPO field setups from the `wp_woocommerce_order_itemmeta` table
		$table_name = $wpdb->prefix . 'woocommerce_order_itemmeta';
		
		$epo_data = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE order_item_id IN (SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d)",
			$order_id
		) );
	
		$pro_meta_fields = [];
	
		if ( !empty( $epo_data ) ) {
			foreach ( $epo_data as $data ) {
				if ( is_serialized( $data->meta_value ) ) {
					$meta_data = unserialize( $data->meta_value );
				} else {
					$meta_data = $data->meta_value;
				}
				
				if ( is_array( $meta_data ) ) {
					foreach ( $meta_data as $key => $field ) {
						if (isset($field['title']) && isset($field['data_name'])) {
							$pro_meta_fields[$field['title']] = $field['data_name'];
						} else {
						}
					}
				} else {
					$pro_meta_fields[$data->meta_key] = $meta_data;
				}
			}
		}
	
	
		$order_items = wc_get_order($order_id)->get_items();
	
		foreach ($order_items as $item_id => $item) {
			$item_meta = wc_get_order_item_meta($item_id, '', false);
	
			foreach ($item_meta as $meta_key => $meta_value) {
				if (array_key_exists($meta_key, $pro_meta_fields)) {
					if (is_array($meta_value) && isset($meta_value[0])) {
						self::$export_instance->data[$order_id][$meta_key] = $meta_value[0];
					} else {
					}
				}
			}
		}
	}

	public function getWCPAData($order_id, $product_ids)
{
    global $wpdb;

    // Fetch all WCPA field setups from the `wp_woocommerce_order_itemmeta` table
    $table_name = $wpdb->prefix . 'woocommerce_order_itemmeta';

    // Fetching metadata for the given order items
    $wcpa_data = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE order_item_id IN (SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d)",
        $order_id
    ));

    $pro_meta_fields = [];

    if (!empty($wcpa_data)) {
        foreach ($wcpa_data as $data) {
            // Check if the meta_value is serialized, then unserialize it
            if (is_serialized($data->meta_value)) {
                $meta_data = unserialize($data->meta_value);
            } else {
                $meta_data = $data->meta_value;
            }

            // Check if the meta_data is an array
            if (is_array($meta_data)) {
                foreach ($meta_data as $key => $field) {
                    if (isset($field['title']) && isset($field['data_name'])) {
                        // Adjusting keys to match export requirements
                        $pro_meta_fields[$field['label']] = $field['value']; // Use label and value instead of title and data_name
                    }
                }
            } else {
                // If it's not an array, directly assign the meta_key to the fields
                $pro_meta_fields[$data->meta_key] = $meta_data;
            }
        }
    }

    // Fetching all order items for the given order_id
    $order_items = wc_get_order($order_id)->get_items();

    foreach ($order_items as $item_id => $item) {
        // Get item metadata for each order item
        $item_meta = wc_get_order_item_meta($item_id, '', false);

        // Loop through the item meta and check if the meta_key exists in the pro_meta_fields
        foreach ($item_meta as $meta_key => $meta_value) {
            if (array_key_exists($meta_key, $pro_meta_fields)) {
                // Adjusting keys to match export requirements
                if (is_array($meta_value) && isset($meta_value[0])) {
                    self::$export_instance->data[$order_id][str_replace(' ', '-', strtolower($meta_key))] = $meta_value[0]; // Convert key format
                }
            }
        }
    }
}

	/**
	 * Code for Woocommerce Refund export
	 * @param $id
	 * @param $type
	 * @param $optionalType
	 */
	public function getWooComCustomerUser($id, $type, $optionalType)
	{
		global $wpdb;
		$parent = WooCommerceExport::$export_instance->data[$id]['post_parent'];
		$query = $wpdb->prepare("SELECT post_id,meta_key,meta_value FROM {$wpdb->prefix}postmeta where post_id = %d", $parent);
		$result = $wpdb->get_results($query);
		if(!empty($result)){
			foreach ($result as $key => $value) {
				if($value->meta_key == '_customer_user'){
					$cus_user = $value->meta_value;
				}
			}
		}
		WooCommerceExport::$export_instance->data[$id]['customer_user'] = $cus_user;
	}

	/**
	 * Export woocommerce product and variation
	 * @param $id
	 * @param $type
	 * @param $optionalType
	 */
	public function getVariationData($id, $type, $optionalType)
	{
		$variations_data = wc_get_product($id);
		$variation_datas = $variations_data->get_data();
		$variations[] = wc_get_product($id);
		foreach ($variations as $attr_key => $variation) {
			$parent_id = $variation->get_parent_id();
			$parent_product = wc_get_product($parent_id);
			$parent_sku = $parent_product->get_sku();
			$variation_sku = $variation->get_sku();
			$variation_id = $variation->get_id();
			$featured_image_id = $variation->get_image_id();
			$featured_image_url = wp_get_attachment_image_url($featured_image_id, 'full');
			$product_attributes = $parent_product->get_attributes();
			$attribute_name = $att_values = $is_visible = $is_variation = $position = $is_taxonomy = [];

			foreach ($product_attributes as $attribute_key => $attribute) {
				$attribute_name[] = str_replace('pa_', '', $attribute['name']);
				$taxonomy_name = $attribute['taxonomy'];
				$attribute_options = $attribute['options'];
				$term_names = [];
				foreach ($attribute_options as $term_key => $term_id) {
					$term = get_term_by('id', $term_id, $taxonomy_name);
					if (!empty($term->name)) {
						$term_names[] = $term->name;
					}
				}
				if (!empty($term_names)) {
					$att_values[] = implode('|', $term_names); // Combine term names with |
				}
				$is_visible[] = $attribute['is_visible'];
				$is_variation[] = $attribute['is_variation'];
				$position[] = $attribute['position'];
			}
			$attachment_meta = wp_get_attachment_metadata($featured_image_id);
			$product_caption = isset($attachment_meta['image_meta']['caption']) ? $attachment_meta['image_meta']['caption'] : '';
			$product_alt_text = get_post_meta($featured_image_id, '_wp_attachment_image_alt', true);
			$product_description = get_post_field('post_content', $featured_image_id);

			$product_title = $parent_product->get_title();
			$is_featured = get_post_meta($parent_product->get_id(), 'featured', true) ? '1' : '0';
			$downloadable = $variation->is_downloadable() ? 'yes' : 'no';
			if ($variation->is_downloadable()) {
				$download_limit = $variation->get_download_limit();
				$download_expiry = $variation->get_download_expiry();
				$downloadable_files = [];
				$download_type = [];
				$downloads = $variation->get_downloads(); 
				foreach ($downloads as $download) {
					$file_string = $download['name'].','.$download['file'];
					$downloadable_files[] = $file_string;
				}
				$download_file_str = implode('|', $downloadable_files);
				WooCommerceExport::$export_instance->data[$id]['downloadable_files'] = $download_file_str ?? '';	
				WooCommerceExport::$export_instance->data[$id]['download_limit'] = $download_limit ?? '';
				WooCommerceExport::$export_instance->data[$id]['download_expiry'] = $download_expiry ?? '';		
			}
			$price  = $variation->get_price();
			$sale_price_dates_from = $variation->get_date_on_sale_from();
			$sale_price_dates_to = $variation->get_date_on_sale_to();
			$regular_price = $variation->get_regular_price();
			$sale_price = $variation->get_sale_price();
			$purchase_note = $variation->get_purchase_note();
			$default_attributes = $variation->get_default_attributes();
			$attribute_default = [];
			foreach ($default_attributes as $def_attribute_key => $def_attribute_value) {
				$attribute_default[] = str_replace('pa_', '', $def_attribute_key) . '|' . $def_attribute_value;
			}
			$custom_attributes = $variation->get_attributes();
			$attribute_names = [];
			foreach ($custom_attributes as $cus_attribute_key => $cus_attribute_value) {
				if(strpos($cus_attribute_value,'-')!== false){
					$cus_attribute_value = str_replace('-',' ',$cus_attribute_value);
				}
				$attribute_names[] = str_replace('pa_', '', $cus_attribute_key) . '|' . $cus_attribute_value;
			}
			$tax_status = $variation->get_tax_status();
			$tax_status_mapping = array('taxable' => 1 , 'shipping' => 2, 'none' => 3);
			$is_virtual = $variation->is_virtual() ? 'yes' : 'no';
			if(!$variation->is_virtual()){
				$weight = $variation->get_weight();
				$length = $variation->get_length();
				$width = $variation->get_width();
				$height = $variation->get_height();	
			}
			$manages_stock = $variation->managing_stock() ? 'yes' : 'no';
			$stock_status = $variation->get_stock_status();	
			$sold_individually = $variation->get_sold_individually();
			if($variation->managing_stock()){
				$stock_qty = $variation->get_stock_quantity();
				$low_stock_amount = $variation->get_low_stock_amount();
				$backorders = $variation->get_backorders();
				switch ($backorders) {
				case 'no':
					$backorders_no= '1';
					break;

				case 'notify':
					$backorders_no= '2';
					break;

				case 'yes':
					$backorders_no = '3';
					break;

				default:
					$backorders_no = '';
					break;
				}					
			}
			$var_description = $variation->get_description();
			$var_class_id = $variation->get_shipping_class_id();
			$shipping_class_term = get_term($var_class_id, 'product_shipping_class');
		}
		WooCommerceExport::$export_instance->data[$id]['PARENTSKU'] = $parent_sku ; 
		WooCommerceExport::$export_instance->data[$id]['VARIATIONSKU'] = $variation_sku; 
		WooCommerceExport::$export_instance->data[$id]['VARIATIONID'] = $variation_id; 
		WooCommerceExport::$export_instance->data[$id]['product_attribute_name'] = implode('|',$attribute_name); 
		WooCommerceExport::$export_instance->data[$id]['product_attribute_value'] = implode(',', $att_values);
		WooCommerceExport::$export_instance->data[$id]['product_attribute_visible'] = implode('|', $is_visible);
		WooCommerceExport::$export_instance->data[$id]['product_attribute_variation'] = implode('|', $is_variation);
		WooCommerceExport::$export_instance->data[$id]['product_attribute_position'] = implode('|', $position);

		WooCommerceExport::$export_instance->data[$id]['product_caption'] = $product_caption ?? '';
		WooCommerceExport::$export_instance->data[$id]['product_alt_text'] = $product_alt_text ?? '';
		WooCommerceExport::$export_instance->data[$id]['product_description'] = $product_description ?? '';	

		WooCommerceExport::$export_instance->data[$id]['product_title'] = $product_title ?? '';
		WooCommerceExport::$export_instance->data[$id]['featured'] = $is_featured ?? '';

		WooCommerceExport::$export_instance->data[$id]['price'] = $price ?? '';
		WooCommerceExport::$export_instance->data[$id]['sale_price_dates_from'] = $sale_price_dates_from ?? '';
		WooCommerceExport::$export_instance->data[$id]['sale_price_dates_to'] = $sale_price_dates_to ?? '';
		WooCommerceExport::$export_instance->data[$id]['regular_price'] = $regular_price ?? '';
		WooCommerceExport::$export_instance->data[$id]['sale_price'] = $sale_price ?? '';
		WooCommerceExport::$export_instance->data[$id]['purchase_note'] = $purchase_note ?? '';

		WooCommerceExport::$export_instance->data[$id]['default_attributes'] = implode(',',$attribute_default) ?? '';
		WooCommerceExport::$export_instance->data[$id]['custom_attributes'] = implode(',',$attribute_names) ?? '';
		WooCommerceExport::$export_instance->data[$id]['_downloadable'] = $downloadable ?? '';

		WooCommerceExport::$export_instance->data[$id]['tax_class'] = $variation_datas['tax_class'] ?? '';
		WooCommerceExport::$export_instance->data[$id]['tax_status'] = array_key_exists($tax_status,$tax_status_mapping) ? $tax_status_mapping[$tax_status] : '' ;

		WooCommerceExport::$export_instance->data[$id]['weight'] = $weight ?? '';
		WooCommerceExport::$export_instance->data[$id]['length'] = $length ?? '';
		WooCommerceExport::$export_instance->data[$id]['width'] = $width ?? '';
		WooCommerceExport::$export_instance->data[$id]['height'] = $height ?? '';

		WooCommerceExport::$export_instance->data[$id]['virtual'] = $is_virtual ?? '';

		WooCommerceExport::$export_instance->data[$id]['manage_stock'] = $manages_stock ?? '';
		WooCommerceExport::$export_instance->data[$id]['_stock'] = $stock_qty ?? ''; 
		WooCommerceExport::$export_instance->data[$id]['_stock_status'] = $stock_status ?? '';
		WooCommerceExport::$export_instance->data[$id]['_low_stock_threshold'] = $low_stock_amount ?? '';
		WooCommerceExport::$export_instance->data[$id]['_stock_qty'] = $stock_qty ?? ''; 
		WooCommerceExport::$export_instance->data[$id]['backorders'] = $backorders_no;
		WooCommerceExport::$export_instance->data[$id]['sold_individually'] = ($sold_individually > 0) ? 'yes' : 'no';
		WooCommerceExport::$export_instance->data[$id]['_thumbnail_id'] = $featured_image_url ?? ''; 

		WooCommerceExport::$export_instance->data[$id]['variation_description'] = $var_description ?? ''; 
		WooCommerceExport::$export_instance->data[$id]['variation_shipping_class'] = $shipping_class_term ? $shipping_class_term->name : '';		
	}	
	// public function getProductData($id, $type, $optionalType)
	// {
	// 	$product = wc_get_product($id);
	// 	$product_datas = $product->get_data();
	// 	$product_sku = $product->get_sku();
	// 	WooCommerceExport::$export_instance->data[$id]['PRODUCTSKU'] = $product_sku; 
	// 	$product_attributes = $product->get_attributes();
	// 	if (!empty($product_attributes)) {
	// 		foreach ($product_attributes as $attribute_key => $attribute) {
	// 			$attribute_name[] = str_replace('pa_', '', $attribute['name']);
	// 			$taxonomy_name = $attribute['taxonomy'];
	// 			$attribute_options = $attribute['options'];
	// 			$term_names = $att_values = [];
	// 			foreach ($attribute_options as $term_key => $term_id) {
	// 				$term = get_term_by('id', $term_id, $taxonomy_name);
	// 				if (!empty($term->name)) {
	// 					$term_names[] = $term->name;
	// 				}
	// 			}
	// 			if (!empty($term_names)) {
	// 				$att_values[] = implode('|', $term_names); 
	// 			}
	// 			$is_visible[] = $attribute['is_visible'];
	// 			$is_variation[] = $attribute['is_variation'];
	// 			$position[] = $attribute['position'];
	// 			$is_taxonomy[] = $attribute['is_taxonomy'];
	// 		}
	// 		WooCommerceExport::$export_instance->data[$id]['product_attribute_name'] = implode('|', $attribute_name);
	// 		WooCommerceExport::$export_instance->data[$id]['product_attribute_value'] = implode(',', $att_values);
	// 		WooCommerceExport::$export_instance->data[$id]['product_attribute_visible'] = implode('|', $is_visible);
	// 		WooCommerceExport::$export_instance->data[$id]['product_attribute_variation'] = implode('|', $is_variation);
	// 		WooCommerceExport::$export_instance->data[$id]['product_attribute_position'] = implode('|', $position);
	// 		WooCommerceExport::$export_instance->data[$id]['product_attribute_taxonomy'] = implode('|', $is_taxonomy);
	// 	}


	// 	$get_catalog_visibility = $product->get_catalog_visibility();
	// 	$visibility_mapping = array('visible' => '1','catalog' => '2','search'  => '3','hidden'  => '4');
	// 	WooCommerceExport::$export_instance->data[$id]['visibility'] = array_key_exists($get_catalog_visibility,$visibility_mapping) ? $visibility_mapping[$get_catalog_visibility] : '';

	// 	$tax_status = $product->get_tax_status();
	// 	$tax_status_mapping = array('taxable' => 1 , 'shipping' => 2, 'none' => 3);
	// 	WooCommerceExport::$export_instance->data[$id]['tax_status'] = array_key_exists($tax_status,$tax_status_mapping) ? $tax_status_mapping[$tax_status] : '' ;

	// 	$product_type = $product->get_type();
	// 	$product_type_mapping = array('simple' => 1 , 'grouped' => 2, 'external' => 3, 'variable' => 4 , 'subscription' => 5 , 'variable-subscription' => 6 ,'bundle' => 7);
	// 	WooCommerceExport::$export_instance->data[$id]['product_type'] = array_key_exists($product_type,$product_type_mapping) ? $product_type_mapping[$product_type] : '' ;

	// 	if (has_term('featured', 'product_visibility', $id)) {
	// 		WooCommerceExport::$export_instance->data[$id]['featured_product'] = '1';
	// 	}
	// 	WooCommerceExport::$export_instance->data[$id]['tax_class'] = $product_datas['tax_class'] ?? '';
	// 	if (method_exists($product, 'get_file_paths')) {
	// 		$file_paths = $product->get_file_paths();
	// 		WooCommerceExport::$export_instance->data[$id]['_file_paths'] = isset($file_paths) ? $file_paths : '';
	// 	} else {
	// 		WooCommerceExport::$export_instance->data[$id]['_file_paths'] = '';
	// 	}
	// 	$edit_last = get_post_meta($id, '_edit_last', true);
	// 	WooCommerceExport::$export_instance->data[$id]['_edit_last'] = $edit_last ?? '';
	// 	$edit_lock = get_post_meta($id, '_edit_lock', true);
	// 	WooCommerceExport::$export_instance->data[$id]['_edit_lock'] = $edit_lock ?? '';
	// 	$thumbnail_id = get_post_thumbnail_id($product->get_id());
	// 	$attachment_url = wp_get_attachment_url($thumbnail_id);
	// 	WooCommerceExport::$export_instance->data[$id]['_thumbnail_id'] = $attachment_url ?? ''; 
	// 	WooCommerceExport::$export_instance->data[$id]['manage_stock'] = $product->get_manage_stock() ? '1' : '';
	// 	$stock_quantity = $product->get_stock_quantity();
	// 	$stock_status = $product->get_stock_status();
	// 	$low_stock_threshold = $product->get_low_stock_amount();
	// 	$total_sales = $product->get_total_sales();
	// 	WooCommerceExport::$export_instance->data[$id]['_stock'] = $stock_quantity ?? ''; 
	// 	WooCommerceExport::$export_instance->data[$id]['_stock_status'] = $stock_status ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['_low_stock_threshold'] = $low_stock_threshold ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['_stock_qty'] = $stock_quantity ?? ''; 
	// 	WooCommerceExport::$export_instance->data[$id]['_total_sales'] = $total_sales ?? '';
	// 	$downloadable = $product->is_downloadable() ? 'yes' : 'no';
	// 	$virtual = $product->is_virtual() ? 'yes' : 'no';
	// 	$regular_price = $product->get_regular_price();
	// 	$sale_price = $product->get_sale_price();
	// 	$purchase_note = $product->get_purchase_note();	

	// 	WooCommerceExport::$export_instance->data[$id]['_downloadable'] = $downloadable ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['_virtual'] = $virtual ?? ''; 
	// 	WooCommerceExport::$export_instance->data[$id]['_regular_price'] = $regular_price ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['_sale_price'] = $sale_price ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['_purchase_note'] = $purchase_note ?? '';

	// 	$weight = $product->get_weight();
	// 	$length = $product->get_length();
	// 	$width = $product->get_width();
	// 	$height = $product->get_height();
	// 	$upsell_ids = $product->get_upsell_ids();
	// 	$upsell_product_names = [];
	// 	foreach ($upsell_ids as $up_id) {
	// 		$upsell_product = wc_get_product($up_id);
	// 		$upsell_product_names[] = $upsell_product ? $upsell_product->get_name() : '';
	// 	}
	// 	$cross_sell_ids = $product->get_cross_sell_ids();
	// 	$cross_sell_product_names = [];
	// 	foreach ($cross_sell_ids as $cross_id) {
	// 		$cross_sell_product = wc_get_product($cross_id);
	// 		$cross_sell_product_names[] = $cross_sell_product ? $cross_sell_product->get_name() : '';
	// 	}
	// 	WooCommerceExport::$export_instance->data[$id]['weight'] = $weight ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['length'] = $length ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['width'] = $width ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['height'] = $height ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['upsell_ids'] = implode(',', $upsell_product_names) ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['crosssell_ids'] = implode(',', $cross_sell_product_names) ?? '';	

	// 	$grouping_product_ids = []; // Initialize an array to store child product IDs
	// 	if ($product->is_type('grouped')) {
	// 		$children = $product->get_children();
	// 		foreach ($children as $child_id) {
	// 			$child = wc_get_product($child_id);
	// 			if (!empty($child)) {
	// 				$grouping_product_ids[] = $child_id;
	// 			}
	// 		}
	// 	}
	// 	$grouping_product_ids_string = implode(',', $grouping_product_ids);
	// 	WooCommerceExport::$export_instance->data[$id]['grouping_product'] = $grouping_product_ids_string;

	// 	$sale_price_dates_from = $product->get_date_on_sale_from();
	// 	$sale_price_dates_to = $product->get_date_on_sale_to();
	// 	$sold_individually = $product->get_sold_individually();
	// 	$backorders = $product->get_backorders();
	// 	switch ($backorders) {
	// 	case 'no':
	// 		$backorders_no= '1';
	// 		break;

	// 	case 'notify':
	// 		$backorders_no= '2';
	// 		break;

	// 	case 'yes':
	// 		$backorders_no = '3';
	// 		break;

	// 	default:
	// 		$backorders_no = '';
	// 		break;
	// 	}	
	// 	// WooCommerceExport::$export_instance->data[$id]['grouping_product'] = implode(',',$grouping_products);
	// 	WooCommerceExport::$export_instance->data[$id]['sale_price_dates_from'] = $sale_price_dates_from ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['sale_price_dates_to'] = $sale_price_dates_to ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['sold_individually'] = ($sold_individually > 0) ? 'yes' : 'no';
	// 	WooCommerceExport::$export_instance->data[$id]['backorders'] = $backorders_no;
	// 	$product_url = ($product->is_type('external')) ? $product->get_product_url() : '';
	// 	$button_text = ($product->is_type('external')) ? $product->single_add_to_cart_text() : '';
	// 	$featured =get_post_meta($product->get_id(), 'featured', true) ? '1' : '0';
	// 	WooCommerceExport::$export_instance->data[$id]['product_url'] = $product_url;
	// 	WooCommerceExport::$export_instance->data[$id]['button_text'] = $button_text;
	// 	WooCommerceExport::$export_instance->data[$id]['featured'] = $featured;	
	// 	$downloadable_files = [];
	// 	$download_type = [];
	// 	if(($product->is_type('variable'))){
	// 		$download_limits = [];
	// 		$download_expiries = [];
	// 		$variations = $product->get_children();
	// 		foreach ($variations as $variation_id) {
	// 			$variation = wc_get_product($variation_id);
	// 			// Check if the variation is downloadable
	// 			if ($variation->is_downloadable()) {
	// 				$downloads = $variation->get_downloads();
	// 				foreach ($downloads as $download) {
	// 					$file_url = $download->get_file(); // Get the file URL
	// 					$file_name = $download->get_name(); // Get the file name
	// 					$file_string = $file_name.','.$file_url;
	// 					$downloadable_files[] = $file_string;
	// 					if (strpos($file_url, 'http') === 0) {
	// 						$download_type[] = 'external'; // If URL starts with http, it's an external URL
	// 					} else {
	// 						$download_type[] = 'file'; // Otherwise, it's a file URL
	// 					}

	// 				}
	// 				$download_limit = $variation->get_download_limit(); // Default is -1 for unlimited
	// 				$download_expiry = $variation->get_download_expiry(); // Default is -1 for no expiry
	// 				// Store the values
	// 				$download_limits[] = $download_limit;
	// 				$download_expiries[] = $download_expiry;

	// 			}
	// 			$download_limit = !empty($download_limit) ? implode('|', $download_limits) : '';
	// 			$download_expiry = !empty($download_expiry) ? implode('|', $download_expiries) : '';
	// 			$download_type_str = !empty($download_type) ? implode('|', $download_type) : '';
	// 			$download_file_str = !empty($downloadable_files) ? implode('|', $downloadable_files) : '';
	// 		}
	// 	}else{
	// 		$downloads = $product->get_downloads();
	// 		foreach ($downloads as $download) {
	// 			$file_string = $download['name'].','.$download['file'];
	// 			$downloadable_files[] = $file_string;
	// 			if (strpos($download['file'], 'http') === 0) {
	// 				$download_type[] = 'external'; // If URL starts with http, it's an external URL
	// 			} else {
	// 				$download_type[] = 'file'; // Otherwise, it's a file URL
	// 			}
	// 		}
	// 		$download_type_str = !empty($download_type) ? implode('|', $download_type) : '';
	// 		$download_file_str = !empty($downloadable_files) ? implode('|', $downloadable_files) : '';

	// 		$download_limit = $product->get_download_limit();
	// 		$download_expiry = $product->get_download_expiry();
	// 	}

	// 	WooCommerceExport::$export_instance->data[$id]['downloadable_files'] = $download_file_str;
	// 	WooCommerceExport::$export_instance->data[$id]['download_limit'] = ($download_limit > -1) ? $download_limit : '';
	// 	WooCommerceExport::$export_instance->data[$id]['download_expiry'] = ($download_expiry > -1) ? $download_expiry : '';
	// 	WooCommerceExport::$export_instance->data[$id]['download_type'] = $download_type_str;

	// 	$subscription_period = get_post_meta($id, '_subscription_period', true);
	// 	$subscription_period_interval = get_post_meta($id, '_subscription_period_interval', true);
	// 	$subscription_length = get_post_meta($id, '_subscription_length', true);
	// 	$subscription_trial_period = get_post_meta($id, '_subscription_trial_period', true);
	// 	$subscription_trial_length = get_post_meta($id, '_subscription_trial_length', true);
	// 	$subscription_price = get_post_meta($id, '_subscription_price', true);
	// 	$subscription_sign_up_fee = get_post_meta($id, '_subscription_sign_up_fee', true);

	// 	WooCommerceExport::$export_instance->data[$id]['_subscription_period'] = $subscription_period ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['_subscription_period_interval'] = $subscription_period_interval ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['_subscription_length'] = $subscription_length ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['_subscription_trial_period'] = $subscription_trial_period ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['_subscription_trial_length'] = $subscription_trial_length ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['_subscription_price'] = $subscription_price ?? '';
	// 	WooCommerceExport::$export_instance->data[$id]['_subscription_sign_up_fee'] = $subscription_sign_up_fee ?? '';	
	// 	// Retrieve barcode details
	// 	if(is_plugin_active('yith-woocommerce-barcodes-premium/init.php' )){
	// 		$barcode_protocol = get_post_meta($id, '_ywbc_barcode_protocol', true);
	// 		$barcode_value = get_post_meta($id, '_ywbc_barcode_value', true);
	// 		$barcode_display_value = get_post_meta($id, '_ywbc_barcode_display_value', true);
	// 		WooCommerceExport::$export_instance->data[$id]['_ywbc_barcode_display_value'] = $barcode_display_value ?? '';
	// 		WooCommerceExport::$export_instance->data[$id]['_ywbc_barcode_value'] = $barcode_value ?? '';
	// 		WooCommerceExport::$export_instance->data[$id]['_ywbc_barcode_protocol'] = $barcode_protocol ?? '';
	// 	}


	// 	//woocommerce-product-bundles plugin
	// 	//woocommerce-product-bundles plugin
	// 	if (is_plugin_active('woocommerce-product-bundles/woocommerce-product-bundles.php')) {
	// 		// Get the bundle product
	// 		$bundle_product = wc_get_product($id);

	// 		if ($bundle_product && $bundle_product->is_type('bundle')) {
	// 			// Initialize default values
	// 			$bundle_items = array();
	// 			$optional_values = array();
	// 			$quantity_min = array();
	// 			$quantity_max = array();
	// 			$price_values = array();
	// 			$discount_values = array();
	// 			$product_values = array();
	// 			$cart_values = array();
	// 			$order_values = array();
	// 			$product_price_values = array();
	// 			$cart_price_values = array();
	// 			$order_price_values = array();
	// 			$thumb_values = array();
	// 			$override_values = array();
	// 			$description_values = array();
	// 			$override_title_values = array();
	// 			$override_description_values = array();

	// 			// Get bundle settings
	// 			$min_bundle_size = $bundle_product->get_min_bundle_size();
	// 			$max_bundle_size = $bundle_product->get_max_bundle_size();
	// 			$edit_in_cart = $bundle_product->get_editable_in_cart() ? 'Yes' : 'No';
	// 			$layout = $bundle_product->get_layout(); // Assuming this method exists
	// 			$get_mode = $bundle_product->get_group_mode();
	// 			if($get_mode == 'noindent'){
	// 				$item_grouping = 'flat';
	// 			}else if($get_mode == 'parent'){
	// 				$item_grouping = 'grouped';
	// 			}else{
	// 				$item_grouping = '';
	// 			}
	// 			foreach ($bundle_product->get_bundled_items() as $bundled_item) {
	// 				// Get bundled item product
	// 				$bundled_product = $bundled_item->get_product();
	// 				$bundle_items[] = $bundled_product->get_name();

	// 				// Retrieve settings for each bundled item
	// 				$optional_values[] = method_exists($bundled_item, 'is_optional') && $bundled_item->is_optional() ? 'Yes' : 'No';
	// 				$quantity_min[] = method_exists($bundled_item, 'get_min_quantity') ? $bundled_item->get_min_quantity() : '';
	// 				$quantity_max[] = method_exists($bundled_item, 'get_max_quantity') ? $bundled_item->get_max_quantity() : '';
	// 				$price_values[] = method_exists($bundled_item, 'is_priced_individually') && $bundled_item->is_priced_individually() ? 'Yes' : 'No';
	// 				$discount_values[] = method_exists($bundled_item, 'get_discount') ? $bundled_item->get_discount() : '';
	// 				$product_values[] = method_exists($bundled_item, 'get_single_product_visibility') && $bundled_item->get_single_product_visibility() ? 'Yes' : 'No';
	// 				$cart_values[] = method_exists($bundled_item, 'get_cart_visibility') && $bundled_item->get_cart_visibility() ? 'Yes' : 'No';
	// 				$order_values[] = method_exists($bundled_item, 'get_order_visibility') && $bundled_item->get_order_visibility() ? 'Yes' : 'No';
	// 				$product_price_values[] = method_exists($bundled_item, 'get_single_product_price_visibility') && $bundled_item->get_single_product_price_visibility() ? 'Yes' : 'No';
	// 				$cart_price_values[] = method_exists($bundled_item, 'get_cart_price_visibility') && $bundled_item->get_cart_price_visibility() ? 'Yes' : 'No';
	// 				$order_price_values[] = method_exists($bundled_item, 'get_order_price_visibility') && $bundled_item->get_order_price_visibility() ? 'Yes' : 'No';
	// 				$thumb_values[] = method_exists($bundled_item, 'get_hide_thumbnail') && $bundled_item->get_hide_thumbnail() ? 'Yes' : 'No';
	// 				$override_values[] = method_exists($bundled_item, 'get_override_title') && $bundled_item->get_override_title() ? 'Yes' : 'No';
	// 				$description_values[] = method_exists($bundled_item, 'get_override_description') && $bundled_item->get_override_description() ? 'Yes' : 'No';
	// 				$override_title_values[] = method_exists($bundled_item, 'get_override_title_value') ? $bundled_item->get_override_title_value() : '';
	// 				$override_description_values[] = method_exists($bundled_item, 'get_override_description_value') ? $bundled_item->get_override_description_value() : '';
	// 			}

	// 			// Prepare export data
	// 			WooCommerceExport::$export_instance->data[$id]['product_bundle_items'] = implode('|', $bundle_items) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['optional'] = implode('|', $optional_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['quantity_min'] = implode('|', $quantity_min) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['quantity_max'] = implode('|', $quantity_max) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['priced_individually'] = implode('|', $price_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['discount'] = implode('|', $discount_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['single_product_visibility'] = implode('|', $product_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['cart_visibility'] = implode('|', $cart_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['order_visibility'] = implode('|', $order_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['single_product_price_visibility'] = implode('|', $product_price_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['cart_price_visibility'] = implode('|', $cart_price_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['order_price_visibility'] = implode('|', $order_price_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['hide_thumbnail'] = implode('|', $thumb_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['override_title'] = implode('|', $override_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['override_description'] = implode('|', $description_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['override_title_value'] = implode('|', $override_title_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['override_description_value'] = implode('|', $override_description_values) ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['pb_sale_price'] = $bundle_product->get_sale_price() ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['pb_regular_price'] = $bundle_product->get_regular_price() ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['min_bundle_size'] = $min_bundle_size ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['max_bundle_size'] = $max_bundle_size ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['edit_in_cart'] = $edit_in_cart ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['layout'] = $layout ?? '';
	// 			WooCommerceExport::$export_instance->data[$id]['item_grouping'] = $item_grouping ?? '';
	// 		}
	// 	}	

	// }
	public function getProductData($id, $type, $optionalType)
	{
		global $wpdb;
		$product = wc_get_product($id);
		
	if(!empty($product)){
		$booking_type ='';
		$product_type = $product->get_type();
		if($product_type == 'variation'){
			$parent_id = $product->get_parent_id();
			$parent_product = wc_get_product($parent_id);
			$parent_sku = $parent_product->get_sku();
			WooCommerceExport::$export_instance->data[$id]['parent'] = $parent_sku;		
		}
		$product_datas = $product->get_data();
		$product_sku = $product->get_sku();
		WooCommerceExport::$export_instance->data[$id]['PRODUCTSKU'] = $product_sku ?? ''; 
		$product_attributes = $product->get_attributes();
		if($product_type !=='variation'){
			if (!empty($product_attributes)) {
				$count =count($product_attributes);
				$new_attr_array =array();
				foreach ($product_attributes as $attribute_key => $attribute) {
					$new_attr_array[] = $attribute;
				}
				array_unshift($new_attr_array,"");
				unset($new_attr_array[0]);
				$att_values = [];
				$stored_id = [];
				if(!empty($new_attr_array)){	
					foreach ($new_attr_array as $attribute_key => $attribute) {
						if (is_a($attribute, 'WC_Product_Attribute')) {
							if(strpos($attribute['name'],'pa_') !== false){
								$attr_name = str_replace('pa_', '', $attribute['name']);
								$attr_name = $wpdb->get_var("SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name='$attr_name'");
							}
							else{
								$attr_name = $attribute['name'];
							}
							$swatch_values = [];
					
							WooCommerceExport::$export_instance->data[$id]['product_attribute_name'.$attribute_key]= $attr_name;
		
							
		
							$taxonomy_name = $attribute['taxonomy'];
							$attribute_options = $attribute['options'];
							$term_names = $att_values = array();
							foreach ($attribute_options as $term_key => $term_id) {
								if(is_numeric($term_id)){
									$term = get_term_by('id', $term_id, $taxonomy_name);
									if (!empty($term->name)) {
										$term_names[] = $term->name;	
											// Retrieve swatch type and value if available
										if(is_plugin_active( 'woo-variation-swatches/woo-variation-swatches.php' ) || is_plugin_active( 'woo-variation-swatches-pro/woo-variation-swatches.php' )){
											$term_meta = $wpdb->get_results("SELECT meta_key,meta_value FROM {$wpdb->termmeta} WHERE term_id = $term->term_id ",ARRAY_A);
											$attri_type = $wpdb->get_results( "SELECT attribute_type FROM {$wpdb->prefix}woocommerce_attribute_taxonomies where attribute_label = '$attr_name' ",ARRAY_A);
											$attri_type = !empty($attri_type[0]['attribute_type']) ? $attri_type[0]['attribute_type'] : '';
											$term_meta = end($term_meta);
											$swatch_value = '';
											$swatch_types = [
												'product_attribute_color' => 'color',
												'product_attribute_image' => 'image',
												'product_attribute_button' => 'button',
												'product_attribute_radio' => 'radio'
											];
											
											if (!empty($term_meta) && array_key_exists($term_meta['meta_key'], $swatch_types)) {
												$swatch_type = $swatch_types[$term_meta['meta_key']];
												$swatch_value = $term_meta['meta_value'];
											} else {
												$swatch_type = '';
												$swatch_value = '';
											}
											$swatch_values[] = !empty($swatch_value) ? $term->name . '->' . $swatch_value : '';
										}
											
									}
									
								}
								else{ 
									$term_names [] = $term_id;
								}
							}
		
		
							if (is_array($swatch_values) && array_filter($swatch_values)) {
								$attribute_name = $attr_name . '->' . $swatch_type;
								$att_values = implode(',', $swatch_values);
							} else {
								if (!empty($attri_type) && !empty($term_names) && !empty($swatch_type)) {
									$attribute_name = $attr_name . '->' . $attri_type;
									$att_values = implode(',', $term_names);
								} else if (!empty($term_names)) {
									$attr_name = $wpdb->get_var("SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name='$attr_name'");
									$attribute_name = $attr_name;
									$att_values = implode(',', $term_names);
								}
							}
							if (is_plugin_active('woo-variation-swatches/woo-variation-swatches.php') || is_plugin_active('woo-variation-swatches-pro/woo-variation-swatches.php')) {
								WooCommerceExport::$export_instance->data[$id]['product_attribute_name' . $attribute_key] = $attribute_name;
								WooCommerceExport::$export_instance->data[$id]['product_attribute_value' . $attribute_key]  = $att_values;
							}else{
								$att_values = implode(',', $term_names);
								WooCommerceExport::$export_instance->data[$id]['product_attribute_value' . $attribute_key]  = $att_values;
							}
							// if (!empty($term_names)) {
							// 	$att_values = implode(',', $term_names);
							// }


							WooCommerceExport::$export_instance->data[$id]['product_attribute_visible' . $attribute_key] = $attribute['is_visible'];
							$is_variation[] = $attribute['is_variation'];
							$position[] = $attribute['position'];
							$is_taxonomy[] = $attribute['is_taxonomy'];
						}

					}	
				}
			}
		}
		else{
			if (!empty($product_attributes)) {
				$i=1;
				foreach ($product_attributes as $attribute_key => $attribute) {
					$attr_name = str_replace('pa_', '', $attribute_key);
					$attr_name = $wpdb->get_var("SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name='$attr_name'");
					$attr_value = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE slug = '$attribute'");
					WooCommerceExport::$export_instance->data[$id]['product_attribute_name'.$i] = $attr_name;
					WooCommerceExport::$export_instance->data[$id]['product_attribute_value'.$i] = $attr_value;
					$i++;
				}
			}	
		}


		$get_catalog_visibility = $product->get_catalog_visibility();
		$visibility_mapping = array('visible' => '1','catalog' => '2','search'  => '3','hidden'  => '4');
		WooCommerceExport::$export_instance->data[$id]['visibility'] = array_key_exists($get_catalog_visibility,$visibility_mapping) ? $visibility_mapping[$get_catalog_visibility] : '';
		
		$tax_status = $product->get_tax_status();
		$tax_status_mapping = array('taxable' => 1 , 'shipping' => 2, 'none' => 3);
		WooCommerceExport::$export_instance->data[$id]['tax_status'] = array_key_exists($tax_status,$tax_status_mapping) ? $tax_status_mapping[$tax_status] : '' ;

		$product_type = $product->get_type();
		$product_type_mapping = array('simple' => 1 , 'grouped' => 2, 'external' => 3, 'variable' => 4 , 'subscription' => 5 , 'variable-subscription' => 6 ,'bundle' => 7,'variation' => '8' , 'jet_booking' => 9);
		WooCommerceExport::$export_instance->data[$id]['product_type'] = array_key_exists($product_type,$product_type_mapping) ? $product_type_mapping[$product_type] : '' ;

		if (has_term('featured', 'product_visibility', $id)) {
			WooCommerceExport::$export_instance->data[$id]['featured_product'] = '1';
		}
		WooCommerceExport::$export_instance->data[$id]['tax_class'] = $product_datas['tax_class'] ?? '';
		if (method_exists($product, 'get_file_paths')) {
			$file_paths = $product->get_file_paths();
			WooCommerceExport::$export_instance->data[$id]['_file_paths'] = isset($file_paths) ? $file_paths : '';
		} else {
			WooCommerceExport::$export_instance->data[$id]['_file_paths'] = '';
		}
		$edit_last = get_post_meta($id, '_edit_last', true);
		WooCommerceExport::$export_instance->data[$id]['_edit_last'] = $edit_last ?? '';
		$edit_lock = get_post_meta($id, '_edit_lock', true);
		WooCommerceExport::$export_instance->data[$id]['_edit_lock'] = $edit_lock ?? '';
		$thumbnail_id = get_post_thumbnail_id($product->get_id());
		$attachment_url = wp_get_attachment_url($thumbnail_id);
		WooCommerceExport::$export_instance->data[$id]['_thumbnail_id'] = $attachment_url ?? ''; 
		WooCommerceExport::$export_instance->data[$id]['manage_stock'] = $product->get_manage_stock() ? '1' : '';
		$stock_quantity = $product->get_stock_quantity();
		$stock_status = $product->get_stock_status();
		$low_stock_threshold = $product->get_low_stock_amount();
		$total_sales = $product->get_total_sales();
		WooCommerceExport::$export_instance->data[$id]['_stock'] = $stock_quantity ?? ''; 
		WooCommerceExport::$export_instance->data[$id]['_stock_status'] = $stock_status ?? '';
		WooCommerceExport::$export_instance->data[$id]['_low_stock_threshold'] = $low_stock_threshold ?? ''; 
		WooCommerceExport::$export_instance->data[$id]['_total_sales'] = $total_sales ?? '';
		$downloadable = $product->is_downloadable() ? 'yes' : 'no';
		$virtual = $product->is_virtual() ? 'yes' : 'no';
		$regular_price = $product->get_regular_price();
		$sale_price = $product->get_sale_price();
		$purchase_note = $product->get_purchase_note();	

		WooCommerceExport::$export_instance->data[$id]['_downloadable'] = $downloadable ?? '';
		WooCommerceExport::$export_instance->data[$id]['_virtual'] = $virtual ?? ''; 
		WooCommerceExport::$export_instance->data[$id]['_regular_price'] = $regular_price ?? '';
		WooCommerceExport::$export_instance->data[$id]['_sale_price'] = $sale_price ?? '';
		WooCommerceExport::$export_instance->data[$id]['_purchase_note'] = $purchase_note ?? '';

		$weight = $product->get_weight();
		$length = $product->get_length();
		$width = $product->get_width();
		$height = $product->get_height();
		$upsell_ids = $product->get_upsell_ids();
		$upsell_product_names = [];
		foreach ($upsell_ids as $up_id) {
			$upsell_product = wc_get_product($up_id);
			$upsell_product_names[] = $upsell_product ? $upsell_product->get_name() : '';
		}
		$cross_sell_ids = $product->get_cross_sell_ids();
		$cross_sell_product_names = [];
		foreach ($cross_sell_ids as $cross_id) {
			$cross_sell_product = wc_get_product($cross_id);
			$cross_sell_product_names[] = $cross_sell_product ? $cross_sell_product->get_name() : '';
		}
		WooCommerceExport::$export_instance->data[$id]['weight'] = $weight ?? '';
		WooCommerceExport::$export_instance->data[$id]['length'] = $length ?? '';
		WooCommerceExport::$export_instance->data[$id]['width'] = $width ?? '';
		WooCommerceExport::$export_instance->data[$id]['height'] = $height ?? '';
		WooCommerceExport::$export_instance->data[$id]['upsell_ids'] = !empty($upsell_product_names) ? implode(',', $upsell_product_names) : '';
		WooCommerceExport::$export_instance->data[$id]['crosssell_ids'] = !empty($cross_sell_product_names) ? implode(',', $cross_sell_product_names) : '';
	
		$grouping_product_ids = []; // Initialize an array to store child product IDs
		if ($product->is_type('grouped')) {
			$children = $product->get_children();
			foreach ($children as $child_id) {
				$child = wc_get_product($child_id);
				if (!empty($child)) {
					$grouping_product_ids[] = $child_id;
				}
			}
		}
		$grouping_product_ids_string = !empty($grouping_product_ids) ? implode(',', $grouping_product_ids) : '';
		WooCommerceExport::$export_instance->data[$id]['grouping_product'] = $grouping_product_ids_string;
		
		$sale_price_dates_from = $product->get_date_on_sale_from();
		$sale_price_dates_to = $product->get_date_on_sale_to();
		$sold_individually = $product->get_sold_individually();
		$backorders = $product->get_backorders();
		switch ($backorders) {
			case 'no':
				$backorders_no= '1';
				break;
		
			case 'notify':
				$backorders_no= '2';
				break;
		
			case 'yes':
				$backorders_no = '3';
				break;
		
			default:
				$backorders_no = '';
				break;
		}
		WooCommerceExport::$export_instance->data[$id]['sale_price_dates_from'] = $sale_price_dates_from ?? '';
		WooCommerceExport::$export_instance->data[$id]['sale_price_dates_to'] = $sale_price_dates_to ?? '';
		WooCommerceExport::$export_instance->data[$id]['sold_individually'] = ($sold_individually > 0) ? 'yes' : 'no';
		WooCommerceExport::$export_instance->data[$id]['backorders'] = $backorders_no;
		// $product_url = ($product->is_type('external')) ? $product->get_product_url() : '';
		$product_url = get_permalink($id);
		
		$button_text = ($product->is_type('external')) ? $product->single_add_to_cart_text() : '';
		$featured = get_post_meta($product->get_id(), 'featured', true) ? '1' : '0';
		WooCommerceExport::$export_instance->data[$id]['product_url'] = $product_url;
		WooCommerceExport::$export_instance->data[$id]['button_text'] = $button_text;
		WooCommerceExport::$export_instance->data[$id]['featured'] = $featured;	
		$downloadable_files = [];
		$download_type = [];
		if(($product->is_type('variable'))){
			$download_limits = [];
			$download_expiries = [];
			$variations = $product->get_children();
			foreach ($variations as $variation_id) {
				$variation = wc_get_product($variation_id);
				// Check if the variation is downloadable
				if ($variation->is_downloadable()) {
					$downloads = $variation->get_downloads();
					foreach ($downloads as $download) {
						$file_url = $download->get_file(); // Get the file URL
						$file_name = $download->get_name(); // Get the file name
						$file_string = $file_name.','.$file_url;
						$downloadable_files[] = $file_string;
						if (strpos($file_url, 'http') === 0) {
							$download_type[] = 'external'; // If URL starts with http, it's an external URL
						} else {
							$download_type[] = 'file'; // Otherwise, it's a file URL
						}

					}
					$download_limit = $variation->get_download_limit(); // Default is -1 for unlimited
					$download_expiry = $variation->get_download_expiry(); // Default is -1 for no expiry
					// Store the values
					$download_limits[] = $download_limit;
					$download_expiries[] = $download_expiry;

				}
				$download_limit = !empty($download_limit) ? implode('|', $download_limits) : '';
				$download_expiry = !empty($download_expiry) ? implode('|', $download_expiries) : '';
				$download_type_str = !empty($download_type) ? implode('|', $download_type) : '';
				$download_file_str = !empty($downloadable_files) ? implode('|', $downloadable_files) : '';
			}
		}else{
			$downloads = $product->get_downloads();
			foreach ($downloads as $download) {
				$file_string = $download['name'].','.$download['file'];
				$downloadable_files[] = $file_string;
				if (strpos($download['file'], 'http') === 0) {
					$download_type[] = 'external'; // If URL starts with http, it's an external URL
				} else {
					$download_type[] = 'file'; // Otherwise, it's a file URL
				}
			}
			$download_type_str = !empty($download_type) ? implode('|', $download_type) : '';
			$download_file_str = !empty($downloadable_files) ? implode('|', $downloadable_files) : '';

			$download_limit = $product->get_download_limit();
			$download_expiry = $product->get_download_expiry();
		}

		WooCommerceExport::$export_instance->data[$id]['downloadable_files'] = $download_file_str;
		WooCommerceExport::$export_instance->data[$id]['download_limit'] = ($download_limit > -1) ? $download_limit : '';
		WooCommerceExport::$export_instance->data[$id]['download_expiry'] = ($download_expiry > -1) ? $download_expiry : '';
		WooCommerceExport::$export_instance->data[$id]['download_type'] = $download_type_str;

		$subscription_period = get_post_meta($id, '_subscription_period', true);
		$subscription_period_interval = get_post_meta($id, '_subscription_period_interval', true);
		$subscription_length = get_post_meta($id, '_subscription_length', true);
		$subscription_trial_period = get_post_meta($id, '_subscription_trial_period', true);
		$subscription_trial_length = get_post_meta($id, '_subscription_trial_length', true);
		$subscription_price = get_post_meta($id, '_subscription_price', true);
		$subscription_sign_up_fee = get_post_meta($id, '_subscription_sign_up_fee', true);

		WooCommerceExport::$export_instance->data[$id]['_subscription_period'] = $subscription_period ?? '';
		WooCommerceExport::$export_instance->data[$id]['_subscription_period_interval'] = $subscription_period_interval ?? '';
		WooCommerceExport::$export_instance->data[$id]['_subscription_length'] = $subscription_length ?? '';
		WooCommerceExport::$export_instance->data[$id]['_subscription_trial_period'] = $subscription_trial_period ?? '';
		WooCommerceExport::$export_instance->data[$id]['_subscription_trial_length'] = $subscription_trial_length ?? '';
		WooCommerceExport::$export_instance->data[$id]['_subscription_price'] = $subscription_price ?? '';
		WooCommerceExport::$export_instance->data[$id]['_subscription_sign_up_fee'] = $subscription_sign_up_fee ?? '';
		
		if ( is_plugin_active( 'yith-woocommerce-barcodes-premium/init.php' )){
		// Retrieve barcode details
		$barcode_protocol = get_post_meta($id, '_ywbc_barcode_protocol', true);
		$barcode_value = get_post_meta($id, '_ywbc_barcode_value', true);
		$barcode_display_value = get_post_meta($id, '_ywbc_barcode_display_value', true);
		WooCommerceExport::$export_instance->data[$id]['_ywbc_barcode_display_value'] = $barcode_display_value ?? '';
		WooCommerceExport::$export_instance->data[$id]['_ywbc_barcode_value'] = $barcode_value ?? '';
		WooCommerceExport::$export_instance->data[$id]['_ywbc_barcode_protocol'] = $barcode_protocol ?? '';
		}


	}
		
		// //woocommerce-product-bundles plugin
		if (is_plugin_active('woocommerce-product-bundles/woocommerce-product-bundles.php')) {
			// Get the bundle product
			$bundle_product = wc_get_product($id);

			if ($bundle_product && $bundle_product->is_type('bundle')) {
				// Initialize default values
				$bundle_items = array();
				$optional_values = array();
				$quantity_min = array();
				$quantity_max = array();
				$price_values = array();
				$discount_values = array();
				$product_values = array();
				$cart_values = array();
				$order_values = array();
				$product_price_values = array();
				$cart_price_values = array();
				$order_price_values = array();
				$thumb_values = array();
				$override_values = array();
				$description_values = array();
				$override_title_values = array();
				$override_description_values = array();

				// Get bundle settings
				$min_bundle_size = $bundle_product->get_min_bundle_size();
				$max_bundle_size = $bundle_product->get_max_bundle_size();
				$edit_in_cart = $bundle_product->get_editable_in_cart() ? 'Yes' : 'No';
				$layout = $bundle_product->get_layout(); // Assuming this method exists
				$get_mode = $bundle_product->get_group_mode();
				if($get_mode == 'noindent'){
					$item_grouping = 'flat';
				}else if($get_mode == 'parent'){
					$item_grouping = 'grouped';
				}else{
					$item_grouping = '';
				}
				foreach ($bundle_product->get_bundled_items() as $bundled_item) {
					// Get bundled item product
					$bundled_product = $bundled_item->get_product();
					$bundle_items[] = $bundled_product->get_name();

					// Retrieve settings for each bundled item
					$optional_values[] = method_exists($bundled_item, 'is_optional') && $bundled_item->is_optional() ? 'Yes' : 'No';
					$quantity_min[] = method_exists($bundled_item, 'get_min_quantity') ? $bundled_item->get_min_quantity() : '';
					$quantity_max[] = method_exists($bundled_item, 'get_max_quantity') ? $bundled_item->get_max_quantity() : '';
					$price_values[] = method_exists($bundled_item, 'is_priced_individually') && $bundled_item->is_priced_individually() ? 'Yes' : 'No';
					$discount_values[] = method_exists($bundled_item, 'get_discount') ? $bundled_item->get_discount() : '';
					$product_values[] = method_exists($bundled_item, 'get_single_product_visibility') && $bundled_item->get_single_product_visibility() ? 'Yes' : 'No';
					$cart_values[] = method_exists($bundled_item, 'get_cart_visibility') && $bundled_item->get_cart_visibility() ? 'Yes' : 'No';
					$order_values[] = method_exists($bundled_item, 'get_order_visibility') && $bundled_item->get_order_visibility() ? 'Yes' : 'No';
					$product_price_values[] = method_exists($bundled_item, 'get_single_product_price_visibility') && $bundled_item->get_single_product_price_visibility() ? 'Yes' : 'No';
					$cart_price_values[] = method_exists($bundled_item, 'get_cart_price_visibility') && $bundled_item->get_cart_price_visibility() ? 'Yes' : 'No';
					$order_price_values[] = method_exists($bundled_item, 'get_order_price_visibility') && $bundled_item->get_order_price_visibility() ? 'Yes' : 'No';
					$thumb_values[] = method_exists($bundled_item, 'get_hide_thumbnail') && $bundled_item->get_hide_thumbnail() ? 'Yes' : 'No';
					$override_values[] = method_exists($bundled_item, 'get_override_title') && $bundled_item->get_override_title() ? 'Yes' : 'No';
					$description_values[] = method_exists($bundled_item, 'get_override_description') && $bundled_item->get_override_description() ? 'Yes' : 'No';
					$override_title_values[] = method_exists($bundled_item, 'get_override_title_value') ? $bundled_item->get_override_title_value() : '';
					$override_description_values[] = method_exists($bundled_item, 'get_override_description_value') ? $bundled_item->get_override_description_value() : '';
				}

				// Prepare export data
				WooCommerceExport::$export_instance->data[$id]['product_bundle_items'] = implode('|', $bundle_items) ?? '';
				WooCommerceExport::$export_instance->data[$id]['optional'] = implode('|', $optional_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['quantity_min'] = implode('|', $quantity_min) ?? '';
				WooCommerceExport::$export_instance->data[$id]['quantity_max'] = implode('|', $quantity_max) ?? '';
				WooCommerceExport::$export_instance->data[$id]['priced_individually'] = implode('|', $price_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['discount'] = implode('|', $discount_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['single_product_visibility'] = implode('|', $product_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['cart_visibility'] = implode('|', $cart_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['order_visibility'] = implode('|', $order_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['single_product_price_visibility'] = implode('|', $product_price_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['cart_price_visibility'] = implode('|', $cart_price_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['order_price_visibility'] = implode('|', $order_price_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['hide_thumbnail'] = implode('|', $thumb_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['override_title'] = implode('|', $override_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['override_description'] = implode('|', $description_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['override_title_value'] = implode('|', $override_title_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['override_description_value'] = implode('|', $override_description_values) ?? '';
				WooCommerceExport::$export_instance->data[$id]['pb_sale_price'] = $bundle_product->get_sale_price() ?? '';
				WooCommerceExport::$export_instance->data[$id]['pb_regular_price'] = $bundle_product->get_regular_price() ?? '';
				WooCommerceExport::$export_instance->data[$id]['min_bundle_size'] = $min_bundle_size ?? '';
				WooCommerceExport::$export_instance->data[$id]['max_bundle_size'] = $max_bundle_size ?? '';
				WooCommerceExport::$export_instance->data[$id]['edit_in_cart'] = $edit_in_cart ?? '';
				WooCommerceExport::$export_instance->data[$id]['layout'] = $layout ?? '';
				WooCommerceExport::$export_instance->data[$id]['item_grouping'] = $item_grouping ?? '';
			}
		}	

	}

	/**
	 * Fetch Terms & Taxonomies
	 * @param $mode
	 * @param $module
	 * @param $optionalType
	 * @return array
	 */
	public function FetchTaxonomies($module, $optionalType, $mode = null) {

		global $wpdb;
		$terms_table = $wpdb->prefix."terms";
		$terms_taxo_table = $wpdb->prefix."term_taxonomy";
		self::$export_instance->generateHeaders($module, $optionalType);
		$taxonomy = $optionalType;
		$query = $wpdb->prepare("SELECT * FROM $terms_table t INNER JOIN $terms_taxo_table tax ON `tax`.term_id = `t`.term_id WHERE `tax`.taxonomy = %s ", $taxonomy);

		$get_all_taxonomies =  $wpdb->get_results($query);
		self::$export_instance->totalRowCount = count($get_all_taxonomies);
		if(!empty($get_all_taxonomies)) {
			foreach( $get_all_taxonomies as $termKey => $termValue ) {
				$termID = $termValue->term_id;
				$termMeta = get_term_meta($termID);
				//wpsc_meta data starts
				if(in_array('wp-e-commerce/wp-shopping-cart.php', self::$export_instance->get_active_plugins())) {
					$wpsc_query = $wpdb->prepare("select meta_key,meta_value from {$wpdb->prefix}wpsc_meta where object_id = %d AND object_type = %s", $termID, 'wpsc_category');
					$wpsc_meta = $wpdb->get_results($wpsc_query,ARRAY_A);
					foreach($wpsc_meta as $mk => $mv){
						if($mv['meta_key'] == 'image'){
							if($mv['meta_value']){
								$udir = wp_upload_dir();
								$img_path = $udir['baseurl'] . "/wpsc/category_images/".$mv['meta_value'];
								self::$export_instance->data[$termID]['category_image'] = $img_path; 
							}else{
								self::$export_instance->data[$termID]['category_image'] = ''; 
							}
						}elseif($mv['meta_key'] == 'display_type'){
							self::$export_instance->data[$termID]['catelog_view'] = $mv['meta_value'];
						}elseif($mv['meta_key'] == 'uses_billing_address'){
							self::$export_instance->data[$termID]['address_calculate'] = $mv['meta_value'];                                           			       }elseif($mv['meta_key'] == 'image_width'){
							self::$export_instance->data[$termID]['category_image_width'] = $mv['meta_value'];                                                                      }elseif($mv['meta_key'] == 'image_height'){
							self::$export_instance->data[$termID]['category_image_height'] = $mv['meta_value'];                                                                     }else{
							self::$export_instance->data[$termID][$mv['meta_key']] = $mv['meta_value'];
						}
					}
				}
				//wpsc_meta data ends
				//woocommerce meta data starts
				if(in_array('woocommerce/woocommerce.php', self::$export_instance->get_active_plugins())){
					if(isset($termMeta['thumbnail_id'][0])){
						$thum_id = $termMeta['thumbnail_id'][0];
						self::$export_instance->data[$termID]['image'] = self::$export_instance->getAttachment($thum_id);
					}
					if(!empty($termMeta['display_type'][0])){
						self::$export_instance->data[$termID]['display_type'] = $termMeta['display_type'][0];
					}
					if(!empty($termMeta['cat_meta'][0])){
						$cat_meta = unserialize($termMeta['cat_meta'][0]);
						self::$export_instance->data[$termID]['top_content'] = $cat_meta['cat_header'];
						self::$export_instance->data[$termID]['bottom_content'] = $cat_meta['cat_footer'];
					}
				}
				//woocommerce meta data ends
				$termName = $termValue->name;
				$termSlug = $termValue->slug;
				$termDesc = $termValue->description;
				$termParent = $termValue->parent;
				if($termParent == 0) {
					self::$export_instance->data[$termID]['name'] = $termName;
				} else {
					$termParentName = get_cat_name( $termParent );
					self::$export_instance->data[$termID]['name'] = $termParentName . '|' . $termName;
				}
				self::$export_instance->data[$termID]['slug'] = $termSlug;
				self::$export_instance->data[$termID]['description'] = $termDesc;
				self::$export_instance->data[$termID]['TERMID'] = $termID;
				self::$export_instance->data[$termID]['parent'] = $termParent;
				self::$export_instance->getWPMLData($termID,$optionalType,$module);

				WooCommerceExport::$post_export->getPostsMetaDataBasedOnRecordId($termID, $module, $optionalType);

				if(in_array('wordpress-seo/wp-seo.php', self::$export_instance->get_active_plugins())) {
					$seo_yoast_taxonomies = get_option( 'wpseo_taxonomy_meta' );
					if ( isset( $seo_yoast_taxonomies[$optionalType] ) ) {
						self::$export_instance->data[ $termID ]['title'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_title'];
						self::$export_instance->data[ $termID ]['meta_desc'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_desc'];
						self::$export_instance->data[ $termID ]['canonical'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_canonical'];
						self::$export_instance->data[ $termID ]['bctitle'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_bctitle'];
						self::$export_instance->data[ $termID ]['meta-robots-noindex'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_noindex'];
						self::$export_instance->data[ $termID ]['sitemap-include'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_sitemap_include'];
						self::$export_instance->data[ $termID ]['opengraph-title'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_opengraph-title'];
						self::$export_instance->data[ $termID ]['opengraph-description'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_opengraph-description'];
						self::$export_instance->data[ $termID ]['opengraph-image'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_opengraph-image'];
						self::$export_instance->data[ $termID ]['twitter-title'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_twitter-title'];
						self::$export_instance->data[ $termID ]['twitter-description'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_twitter-description'];
						self::$export_instance->data[ $termID ]['twitter-image'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_twitter-image'];
						self::$export_instance->data[ $termID ]['focus_keyword'] = $seo_yoast_taxonomies[$optionalType][$termID]['wpseo_focuskw'];

					}
				}
			}
		}

		$result = self::$export_instance->finalDataToExport(self::$export_instance->data , $module);
		if($mode == null)
			self::$export_instance->proceedExport($result);
		else
			return $result;
	}

	public function getCourseData($id)
	{
		global $wpdb;

		$get_section_details = $wpdb->get_results("SELECT section_id, section_name, section_description FROM {$wpdb->prefix}learnpress_sections WHERE section_course_id = $id ", ARRAY_A);
		$section_names = '';
		$section_descriptions = '';
		$get_lesson_name = '';
		$get_lesson_description = '';
		$get_lesson_duration = '';
		$get_lesson_preview = '';
		$get_quiz_name = '';
		$get_quiz_description = '';
		$get_quiz_meta = [];

		foreach($get_section_details as $section_details){
			$section_names .= $section_details['section_name'] . '|';
			$section_descriptions .= $section_details['section_description'] . '|';

			$section_id = $section_details['section_id'];
			$get_section_item_details = $wpdb->get_results("SELECT item_id, item_type FROM {$wpdb->prefix}learnpress_section_items WHERE section_id = $section_id ", ARRAY_A);

			$lesson_name = '';
			$lesson_description = '';
			$quiz_name = '';
			$quiz_description = '';
			$lesson_duration = '';
			$lesson_preview = '';
			$quiz_metas = [];

			foreach($get_section_item_details as $section_item_details){
				$section_item_id = $section_item_details['item_id'];
				if($section_item_details['item_type'] == 'lp_lesson'){
					$lesson_name .= $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $section_item_id ") . ', ';
					$lesson_description .= $wpdb->get_var("SELECT post_content FROM {$wpdb->prefix}posts WHERE ID = $section_item_id "). ', ';
					$lesson_duration .= $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $section_item_id AND meta_key = '_lp_duration' ") . ', ';
					$lesson_preview .= $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $section_item_id AND meta_key = '_lp_preview' ") . ', ';
				}
				elseif($section_item_details['item_type'] == 'lp_quiz'){
					$quiz_name .= $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $section_item_id ") . ', ';
					$quiz_description .= $wpdb->get_var("SELECT post_content FROM {$wpdb->prefix}posts WHERE ID = $section_item_id "). ', ';

					$quiz_meta = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $section_item_id AND meta_key LIKE '_lp_%' ", ARRAY_A);
					foreach($quiz_meta as $quiz_meta_values){
						$quiz_key = $quiz_meta_values['meta_key'];
						$quiz_value = $quiz_meta_values['meta_value'] . ', ';

						if($quiz_key != '_lp_hidden_questions'){
							if($quiz_key == '_lp_retake_count'){
								$quiz_key = '_lp_quiz_retake_count';
							}
							$quiz_metas[$quiz_key] = $quiz_value;
						}
					}
				}
			}

			$get_lesson_name .= rtrim($lesson_name, ', ') . '|';
			$get_lesson_description .= rtrim($lesson_description, ', ') . '|';
			$get_quiz_name .= rtrim($quiz_name, ', ') . '|';
			$get_quiz_description .= rtrim($quiz_description, ', ') . '|';
			$get_lesson_duration .= rtrim($lesson_duration, ', ') . '|';
			$get_lesson_preview .= rtrim($lesson_preview, ', ') . '|';

			foreach($quiz_metas as $quiz_meta_keys => $quiz_meta_values){	
				$get_quiz_meta[$quiz_meta_keys] = rtrim($quiz_meta_values, ', ') . '|';
			}
		}

		WooCommerceExport::$export_instance->data[$id]['curriculum_name'] = rtrim($section_names, '|');
		WooCommerceExport::$export_instance->data[$id]['curriculum_description'] = rtrim($section_descriptions, '|');
		WooCommerceExport::$export_instance->data[$id]['lesson_name'] = rtrim($get_lesson_name, '|');
		WooCommerceExport::$export_instance->data[$id]['lesson_description'] = rtrim($get_lesson_description, '|');
		WooCommerceExport::$export_instance->data[$id]['quiz_name'] = rtrim($get_quiz_name, '|');
		WooCommerceExport::$export_instance->data[$id]['quiz_description'] = rtrim($get_quiz_description, '|');
		WooCommerceExport::$export_instance->data[$id]['_lp_lesson_duration'] = rtrim($get_lesson_duration, '|');
		WooCommerceExport::$export_instance->data[$id]['_lp_preview'] = rtrim($get_lesson_preview, '|');

		foreach($get_quiz_meta as $get_quiz_meta_keys => $get_quiz_meta_values){
			WooCommerceExport::$export_instance->data[$id][$get_quiz_meta_keys] = rtrim($get_quiz_meta_values, '|');
		}
	}

	public function getLessonData($id){
		global $wpdb;
		$lesson_duration = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id AND meta_key = '_lp_duration' ");
		WooCommerceExport::$export_instance->data[$id]['_lp_lesson_duration'] = $lesson_duration;

		$get_section_id = $wpdb->get_var("SELECT section_id FROM {$wpdb->prefix}learnpress_section_items WHERE item_id = $id AND item_type = 'lp_lesson' ");
		if(!empty($get_section_id)){
			$get_section_name = $wpdb->get_var("SELECT section_name FROM {$wpdb->prefix}learnpress_sections WHERE section_id = $get_section_id ");
			$get_section_course_id = $wpdb->get_var("SELECT section_course_id FROM {$wpdb->prefix}learnpress_sections WHERE section_id = $get_section_id ");

			WooCommerceExport::$export_instance->data[$id]['curriculum_name'] = $get_section_name;
			WooCommerceExport::$export_instance->data[$id]['course_id'] = $get_section_course_id;
		}
	}

	public function getQuizData($id){
		global $wpdb;
		$quiz_retake_count = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id AND meta_key = '_lp_retake_count' ");
		WooCommerceExport::$export_instance->data[$id]['_lp_quiz_retake_count'] = $quiz_retake_count;

		$get_section_id = $wpdb->get_var("SELECT section_id FROM {$wpdb->prefix}learnpress_section_items WHERE item_id = $id AND item_type = 'lp_quiz' ");
		if(!empty($get_section_id)){
			$get_section_name = $wpdb->get_var("SELECT section_name FROM {$wpdb->prefix}learnpress_sections WHERE section_id = $get_section_id ");
			$get_section_course_id = $wpdb->get_var("SELECT section_course_id FROM {$wpdb->prefix}learnpress_sections WHERE section_id = $get_section_id ");

			WooCommerceExport::$export_instance->data[$id]['curriculum_name'] = $get_section_name;
			WooCommerceExport::$export_instance->data[$id]['course_id'] = $get_section_course_id;
		}

		$get_question_title = '';
		$get_question_content = '';
		$get_question_mark = '';
		$get_question_explanation = '';
		$get_question_hint = '';
		$get_question_type = '';
		$get_option_value = '';

		$get_question_ids = $wpdb->get_results("SELECT question_id FROM {$wpdb->prefix}learnpress_quiz_questions WHERE quiz_id = $id ", ARRAY_A);
		foreach($get_question_ids as $question_ids){
			$question_id = $question_ids['question_id'];
			$get_question_title .= $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $question_id ") . ',';
			$get_question_content .= $wpdb->get_var("SELECT post_content FROM {$wpdb->prefix}posts WHERE ID = $question_id ") . ',';

			$get_question_mark .= $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $question_id AND meta_key = '_lp_mark' ") . ', ';	
			$get_question_explanation .= $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $question_id AND meta_key = '_lp_explanation' ") . ',';	
			$get_question_hint .= $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $question_id AND meta_key = '_lp_hint' ") . ',';	
			$get_question_type .= $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $question_id AND meta_key = '_lp_type' ") . ',';	

			$get_question_options = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}learnpress_question_answers WHERE question_id = $question_id ", ARRAY_A);
			$option_value = '';
			foreach($get_question_options as $question_option){
				if(empty($question_option['is_true'])){
					$question_option['is_true'] = 'no';
				}

				$option_value .= $question_option['title'] .'|'. $question_option['is_true'] . '->';
			}
			$get_option_value .=  rtrim($option_value, '->') . ',';
		}

		WooCommerceExport::$export_instance->data[$id]['question_title'] = rtrim($get_question_title, ',');
		WooCommerceExport::$export_instance->data[$id]['question_description'] = rtrim($get_question_content, ',');
		WooCommerceExport::$export_instance->data[$id]['_lp_mark'] = rtrim($get_question_mark, ', ');
		WooCommerceExport::$export_instance->data[$id]['_lp_explanation'] = rtrim($get_question_explanation, ',');
		WooCommerceExport::$export_instance->data[$id]['_lp_hint'] = rtrim($get_question_hint, ',');
		WooCommerceExport::$export_instance->data[$id]['_lp_type'] = rtrim($get_question_type, ',');
		WooCommerceExport::$export_instance->data[$id]['question_options'] = rtrim($get_option_value, ',');

	}

	public function getQuestionData($id){
		global $wpdb;
		$get_quiz_id = $wpdb->get_var("SELECT quiz_id FROM {$wpdb->prefix}learnpress_quiz_questions WHERE question_id = $id ");

		$get_question_options = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}learnpress_question_answers WHERE question_id = $id ", ARRAY_A);
		$option_value = '';
		foreach($get_question_options as  $question_options){
			if(empty($question_options['is_true'])){
				$question_options['is_true'] = 'no';
			}
			$option_value .= $question_options['title'] .'|'. $question_options['is_true'] . '->';
		}

		if(!empty($get_quiz_id)){
			$get_section_id = $wpdb->get_var("SELECT section_id FROM {$wpdb->prefix}learnpress_section_items WHERE item_id = $get_quiz_id AND item_type = 'lp_quiz' ");
			if(!empty($get_section_id)){
				$get_section_name = $wpdb->get_var("SELECT section_name FROM {$wpdb->prefix}learnpress_sections WHERE section_id = $get_section_id ");
				$get_section_course_id = $wpdb->get_var("SELECT section_course_id FROM {$wpdb->prefix}learnpress_sections WHERE section_id = $get_section_id ");

				WooCommerceExport::$export_instance->data[$id]['curriculum_name'] = $get_section_name;
				WooCommerceExport::$export_instance->data[$id]['course_id'] = $get_section_course_id;
			}
			WooCommerceExport::$export_instance->data[$id]['quiz_id'] = $get_quiz_id;
		}

		WooCommerceExport::$export_instance->data[$id]['question_options'] = rtrim($option_value, '->');
	}

	public function getOrderData($id){
		global $wpdb;

		$order_status = $wpdb->get_var("SELECT post_status FROM {$wpdb->prefix}posts WHERE ID = $id ");
		$order_date = $wpdb->get_var("SELECT post_date FROM {$wpdb->prefix}posts WHERE ID = $id ");
		$order_total = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id AND meta_key = '_order_total' ");
		$order_subtotal = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id AND meta_key = '_order_subtotal' ");
		$user_id = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id AND meta_key = '_user_id' ");

		$get_order_items = $wpdb->get_results("SELECT order_item_id FROM {$wpdb->prefix}learnpress_order_items WHERE order_id = $id ",ARRAY_A);
		$course_id = '';
		$item_quantity = '';
		$item_total = '';
		$item_subtotal = '';
		foreach($get_order_items as $get_order_values){
			$order_item_id = $get_order_values['order_item_id'];

			$course_id .= $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}learnpress_order_itemmeta WHERE learnpress_order_item_id = $order_item_id AND meta_key = '_course_id' ") . ', ';
			$item_quantity .= $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}learnpress_order_itemmeta WHERE learnpress_order_item_id = $order_item_id AND meta_key = '_quantity' ") . ', ';
			$item_total .= $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}learnpress_order_itemmeta WHERE learnpress_order_item_id = $order_item_id AND meta_key = '_subtotal' ") . ', ';
			$item_subtotal .= $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}learnpress_order_itemmeta WHERE learnpress_order_item_id = $order_item_id AND meta_key = '_total' ") . ', ';
		}

		WooCommerceExport::$export_instance->data[$id]['order_status'] = $order_status;
		WooCommerceExport::$export_instance->data[$id]['order_date'] = $order_date;
		WooCommerceExport::$export_instance->data[$id]['_order_total'] = $order_total;
		WooCommerceExport::$export_instance->data[$id]['_order_subtotal'] = $order_subtotal;
		WooCommerceExport::$export_instance->data[$id]['user_id'] = $user_id;
		WooCommerceExport::$export_instance->data[$id]['item_id'] = rtrim($course_id, ', ');
		WooCommerceExport::$export_instance->data[$id]['item_quantity'] = rtrim($item_quantity, ', ');
		WooCommerceExport::$export_instance->data[$id]['_item_total'] = rtrim($item_total, ', ');
		WooCommerceExport::$export_instance->data[$id]['_item_subtotal'] = rtrim($item_subtotal, ', ');	
	}

	public function elementor_export($id){
		$elementorExport = new ElementorExport;
		$variable= $elementorExport->templateExport();
		if(!empty($variable)){
			foreach($variable as $key=>$value){
				if ($value['ID'] == $id) {
					WooCommerceExport::$export_instance->data[$id]['ID'] = $id;
					WooCommerceExport::$export_instance->data[$id]['Template title'] = $value['Template title'];
					WooCommerceExport::$export_instance->data[$id]['Template content'] = $value['Template content'];
					WooCommerceExport::$export_instance->data[$id]['Style'] = $value['Style'];
					WooCommerceExport::$export_instance->data[$id]['Template type'] = $value['Template type'];
					WooCommerceExport::$export_instance->data[$id]['Created time'] = $value['Created time'];
					WooCommerceExport::$export_instance->data[$id]['Created by'] = $value['Created by'];
					WooCommerceExport::$export_instance->data[$id]['Template status'] = $value['Template status'];
					WooCommerceExport::$export_instance->data[$id]['Category'] = $value['Category'];
				}
			}
		}
	}

	public function getCourseDataMasterLMS($id) {
		global $wpdb;

		$get_item_details = $wpdb->get_results("SELECT  item_id, item_title
			FROM {$wpdb->prefix}stm_lms_curriculum_log WHERE course_id = $id", ARRAY_A);


		$curicullam = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='curriculum' ");
		$curicullam_array = explode(',', $curicullam);
		foreach ($curicullam_array as &$value) {
			if (ctype_digit($value)) {
				$value = '';
			}
		}
		$curicullam = implode(',', $curicullam_array);
		$curicullam = trim($curicullam, ',');	
		$curicullam_withoutId = preg_replace('/,{2,}/', ',', $curicullam);

		$faq = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='faq' ");
		$faq_array = json_decode($faq, true);
		$faq_output = '';
		foreach ($faq_array as $faq_item) {
			$faq_output .= 'question:' . $faq_item['question'] . ',answer:' . $faq_item['answer'] . ' | ';
		}
		$faq_output = rtrim($faq_output, ' | ');


		$course_files_pack = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='course_files_pack' ");
		$course_files_pack_array = json_decode($course_files_pack, true);
		$course_files_pack_output = '';
		foreach ($course_files_pack_array as $course_files_pack_item) {
			$course_files_pack_output .= 'course_files_label:' . $course_files_pack_item['course_files_label'];
		}
		$status_dates = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='status_dates' ");
		$status_dates_start = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='status_dates_start' ");
		$views = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='views'");
		$duration_info = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='duration_info'");
		$featured = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='featured'");
		$level = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='level'");
		$current_students = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='current_students'");
		$video_duration = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='video_duration'");
		$price = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='price'");
		$sale_price = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='sale_price'");
		$status = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='status'");
		$expiration_course = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='expiration_course'");		
		$not_membership = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='not_membership'");
		$end_time = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='end_time'");
		$announcement = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='announcement'");
		$course_files_pack = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='course_files_pack'");
		$status_dates_end = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='status_dates_end' ");
		$status_dates_start = date('Y-m-d', $status_dates_start/1000);
		$status_dates_end = date('Y-m-d', $status_dates_end/1000);
		$status_dates_array = explode(',', $status_dates);
		$status_dates_formatted = array();

		foreach ($status_dates_array as $date) {
			$status_dates_formatted[] = date('Y-m-d', $date/1000);
		}

		$status_dates_formatted_str = implode(',', $status_dates_formatted);

		$get_lesson_id = '';
		$get_lesson_name = '';
		$get_quiz_id = '';
		$get_quiz_name = '';

		foreach ($get_item_details as $item_details) {
			$item_id = $item_details['item_id'];

			$lesson_id = '';
			$lesson_name = '';
			$quiz_id = '';
			$quiz_name = '';

			$get_section_item_details = $wpdb->get_results("SELECT distinct item_id, item_type FROM {$wpdb->prefix}stm_lms_curriculum_log WHERE item_id = $item_id ", ARRAY_A);

			foreach ($get_section_item_details as $section_item_details) {
				if ($section_item_details['item_type'] == 'stm-lessons') {
					$lesson_id .= $item_id . ', ';
					$lesson_name .= $wpdb->get_var("SELECT distinct post_title FROM {$wpdb->prefix}posts WHERE ID = $item_id ") . ',';
				} else if ($section_item_details['item_type'] == 'stm-quizzes') {
					$quiz_id .= $item_id . ', ';
					$quiz_name .= $wpdb->get_var("SELECT  distinct post_title FROM {$wpdb->prefix}posts WHERE ID = $item_id ") . ',';
				}
			}

			$get_lesson_id .= rtrim($lesson_id, ', ') . '|';
			$get_lesson_name .= rtrim($lesson_name, ',') . '|';
			$get_quiz_id .= rtrim($quiz_id, ', ') . '|';
			$get_quiz_name .= rtrim($quiz_name, ',') . '|';

			$get_lesson_id = str_replace('||', '|', $get_lesson_id);
			$get_lesson_name = str_replace('||', '|', $get_lesson_name);
			$get_quiz_id = str_replace('||', '|', $get_quiz_id);
			$get_quiz_name = str_replace('||', '|', $get_quiz_name);
		}

		WooCommerceExport::$export_instance->data[$id]['lesson_id'] = $get_lesson_id;
		WooCommerceExport::$export_instance->data[$id]['lesson_name'] = $get_lesson_name;
		WooCommerceExport::$export_instance->data[$id]['quiz_id'] = $get_quiz_id;
		WooCommerceExport::$export_instance->data[$id]['quiz_name'] = $get_quiz_name;
		WooCommerceExport::$export_instance->data[$id]['status_dates'] = $status_dates_formatted_str;
		WooCommerceExport::$export_instance->data[$id]['status_dates_start'] = $status_dates_start;
		WooCommerceExport::$export_instance->data[$id]['status_dates_end'] = $status_dates_end;
		WooCommerceExport::$export_instance->data[$id]['curriculum'] = $curicullam_withoutId;
		WooCommerceExport::$export_instance->data[$id]['video_duration'] = $video_duration;
		WooCommerceExport::$export_instance->data[$id]['views'] = $views;
		WooCommerceExport::$export_instance->data[$id]['price'] = $price;
		WooCommerceExport::$export_instance->data[$id]['sale_price'] = $sale_price;
		WooCommerceExport::$export_instance->data[$id]['status'] = $status;
		WooCommerceExport::$export_instance->data[$id]['expiration_course'] = $expiration_course;
		WooCommerceExport::$export_instance->data[$id]['not_membership'] = $not_membership;
		WooCommerceExport::$export_instance->data[$id]['end_time'] = $end_time;
		WooCommerceExport::$export_instance->data[$id]['announcement'] = $announcement;
		WooCommerceExport::$export_instance->data[$id]['course_files_pack'] = $course_files_pack;
		WooCommerceExport::$export_instance->data[$id]['duration_info'] = $duration_info;
		WooCommerceExport::$export_instance->data[$id]['featured'] = $featured;
		WooCommerceExport::$export_instance->data[$id]['level'] = $level;
		WooCommerceExport::$export_instance->data[$id]['current_students'] = $current_students;
		WooCommerceExport::$export_instance->data[$id]['faq'] = $faq_output;
		WooCommerceExport::$export_instance->data[$id]['course_files_pack'] = $course_files_pack_output;	

	}

	public function getQuestionDataMasterLMS($id) {

		global $wpdb;
		$type = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='type' ");
		$answers = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='answers' ");
		$question_explanation = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='question_explanation' ");
		$question = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='question' ");
		$question_hint = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='question_hint' ");
		$question_view_type = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='question_view_type' ");
		$image = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='image' ");
		$answers = unserialize($answers);
		$answers_string = '';
		foreach ($answers as $answer) {
			$answers_string .= $answer['text'] . ',' . $answer['isTrue'] . '|';
		}

		WooCommerceExport::$export_instance->data[$id]['type'] = $type;
		WooCommerceExport::$export_instance->data[$id]['answers'] = rtrim($answers_string);
		WooCommerceExport::$export_instance->data[$id]['question_explanation'] = $question_explanation;
		WooCommerceExport::$export_instance->data[$id]['question'] = $question;
		WooCommerceExport::$export_instance->data[$id]['question_hint'] = $question_hint;
		WooCommerceExport::$export_instance->data[$id]['question_view_type'] = $question_view_type;
		WooCommerceExport::$export_instance->data[$id]['image'] = $image;		
	}	
	public function orderDataMasterLMS($id) {
		global $wpdb;
		$status = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='status' ");

		WooCommerceExport::$export_instance->data[$id]['status'] = $status;
	}
	public function quizzDataMasterLMS($id) {
		global $wpdb;
		$duration = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='duration' ");
		$thumbnail_id = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='thumbnail_id' ");
		$lesson_excerpt = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_excerpt' ");
		$quiz_style = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='quiz_style' ");
		$correct_answer = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='correct_answer' ");
		$passing_grade = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='passing_grade' ");
		$re_take_cut = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='re_take_cut' ");
		$random_questions = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='random_questions' ");
		$questions = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='questions' ");

		WooCommerceExport::$export_instance->data[$id]['duration'] = $duration;
		WooCommerceExport::$export_instance->data[$id]['thumbnail_id'] = $thumbnail_id;
		WooCommerceExport::$export_instance->data[$id]['lesson_excerpt'] = $lesson_excerpt;
		WooCommerceExport::$export_instance->data[$id]['quiz_style'] = $quiz_style;
		WooCommerceExport::$export_instance->data[$id]['correct_answer'] = $correct_answer;
		WooCommerceExport::$export_instance->data[$id]['passing_grade'] = $passing_grade;
		WooCommerceExport::$export_instance->data[$id]['re_take_cut'] = $re_take_cut;
		WooCommerceExport::$export_instance->data[$id]['random_questions'] = $random_questions;
		WooCommerceExport::$export_instance->data[$id]['questions'] = $questions;
	}	

	public function getLessonDataMasterLMS($id)
	{
		global $wpdb;
		$type = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='type'");
		$duration = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='duration' ");
		$preview = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='preview' ");
		$lesson_excerpt = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_excerpt' ");
		$_thumbnail_id = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='_thumbnail_id' ");
		$video_type = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='video_type' ");
		$lesson_youtube_url = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_youtube_url' ");
		$presto_player_idx = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='presto_player_idx' ");
		$lesson_video = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_video' ");
		$lesson_video_poster = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_video_poster' ");
		$lesson_video_width = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_video_width' ");
		$lesson_shortcode = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_shortcode' ");
		$lesson_embed_ctx = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_embed_ctx' ");
		$lesson_stream_url = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_stream_url' ");
		$lesson_vimeo_url = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_vimeo_url' ");
		$lesson_ext_link_url = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_ext_link_url' ");
		$lesson_files_pack = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id and meta_key='lesson_files_pack' ");

		$lesson_files_pack_array = json_decode($lesson_files_pack, true);
		$lesson_files_pack_output = '';
		foreach ($lesson_files_pack_array as $lesson_files_pack_item) {
			if (!empty($lesson_files_pack_item['closed_tab'])) {
				$lesson_files_pack_output .= 'closed_tab:' . $lesson_files_pack_item['closed_tab'] . ',';
			}
			$lesson_files_pack_output .= 'lesson_files_label:' . $lesson_files_pack_item['lesson_files_label'] . ',';
			$lesson_files_pack_output = rtrim($lesson_files_pack_output, ',');		}

			WooCommerceExport::$export_instance->data[$id]['type'] = $type;
		WooCommerceExport::$export_instance->data[$id]['duration'] = $duration;
		WooCommerceExport::$export_instance->data[$id]['preview'] = $preview;
		WooCommerceExport::$export_instance->data[$id]['lesson_excerpt'] = $lesson_excerpt;
		WooCommerceExport::$export_instance->data[$id]['_thumbnail_id'] = $_thumbnail_id;
		WooCommerceExport::$export_instance->data[$id]['video_type'] = $video_type;
		WooCommerceExport::$export_instance->data[$id]['lesson_youtube_url'] = $lesson_youtube_url;
		WooCommerceExport::$export_instance->data[$id]['presto_player_idx'] = $presto_player_idx;
		WooCommerceExport::$export_instance->data[$id]['lesson_video'] = $lesson_video;
		WooCommerceExport::$export_instance->data[$id]['lesson_video_poster'] = $lesson_video_poster;
		WooCommerceExport::$export_instance->data[$id]['lesson_video_width'] = $lesson_video_width;
		WooCommerceExport::$export_instance->data[$id]['lesson_shortcode'] = $lesson_shortcode;
		WooCommerceExport::$export_instance->data[$id]['lesson_embed_ctx'] = $lesson_embed_ctx;
		WooCommerceExport::$export_instance->data[$id]['lesson_stream_url'] = $lesson_stream_url;
		WooCommerceExport::$export_instance->data[$id]['lesson_vimeo_url'] = $lesson_vimeo_url;
		WooCommerceExport::$export_instance->data[$id]['lesson_ext_link_url'] = $lesson_ext_link_url;
		WooCommerceExport::$export_instance->data[$id]['lesson_files_pack'] = $lesson_files_pack_output;
	}


	public function getMenuData($term_id){
		global $wpdb;
		$term_name = get_term( $term_id )->name;
		$get_object_ids = $wpdb->get_results("SELECT p.* FROM {$wpdb->prefix}posts AS p 
			LEFT JOIN {$wpdb->prefix}term_relationships AS tr ON tr.object_id = p.ID
			LEFT JOIN {$wpdb->prefix}term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			WHERE p.post_type = 'nav_menu_item'
			AND tt.term_id = $term_id ", ARRAY_A);

		$menu_item_types = '';	
		$menu_object_ids = '';
		$menu_objects = '';
		$menu_urls = '';
		foreach($get_object_ids as $object_ids){
			$object_id = $object_ids['ID'];

			$get_object_meta = $wpdb->get_results("SELECT meta_key , meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $object_id", ARRAY_A);
			$object_meta_key  = array_column($get_object_meta, 'meta_key');
			$object_meta_value  = array_column($get_object_meta, 'meta_value');
			$object_array = array_combine($object_meta_key, $object_meta_value);

			$menu_item_type = $object_array['_menu_item_type'];
			$menu_item_object = $object_array['_menu_item_object'];
			$menu_object_item_id = $object_array['_menu_item_object_id'];

			if(($menu_item_type == 'post_type') && ($menu_item_object == 'post' || $menu_item_object == 'page')){
				$menu_object_item_title = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $menu_object_item_id AND post_type = '$menu_item_object' ");
			}
			elseif($menu_item_type == 'custom' && $menu_item_object == 'custom'){
				$menu_object_item_title = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $menu_object_item_id AND post_type = 'nav_menu_item' ");
			}
			elseif($menu_item_type == 'taxonomy'){
				$category_data = get_term_by('id', $menu_object_item_id, $menu_item_object);
				$menu_object_item_title = $category_data->name;
			}
			else{
				$menu_object_item_title = $menu_object_item_id;
			}

			$menu_item_types .= $menu_item_type . ',';
			$menu_object_ids .= $menu_object_item_title . ','; 
			$menu_objects .= $menu_item_object . ','; 
			$menu_urls .= $object_array['_menu_item_url'] . ','; 
		}	

		WooCommerceExport::$export_instance->data[$term_id]['menu_title'] = $term_name;
		WooCommerceExport::$export_instance->data[$term_id]['_menu_item_type'] = rtrim($menu_item_types, ',');
		WooCommerceExport::$export_instance->data[$term_id]['_menu_item_object_id'] = rtrim($menu_object_ids, ',');
		WooCommerceExport::$export_instance->data[$term_id]['_menu_item_object'] = rtrim($menu_objects, ',');
		WooCommerceExport::$export_instance->data[$term_id]['_menu_item_url'] = rtrim($menu_urls, ',');

		$get_nav_options = get_option("nav_menu_options");
		if(!empty($get_nav_options['auto_add'])){
			if(in_array($term_id, $get_nav_options['auto_add'])){
				WooCommerceExport::$export_instance->data[$term_id]['menu_auto_add'] = 'yes';
			}else{
				WooCommerceExport::$export_instance->data[$term_id]['menu_auto_add'] = 'no';
			}
		}
		else{
			WooCommerceExport::$export_instance->data[$term_id]['menu_auto_add'] = 'no';
		}

		$get_navigation_locations = get_nav_menu_locations();
		foreach($get_navigation_locations as $nav_keys => $nav_values){
			if($nav_values == $term_id){
				WooCommerceExport::$export_instance->data[$term_id][$nav_keys] = 'yes';
			}else{
				WooCommerceExport::$export_instance->data[$term_id][$nav_keys] = 'no';
			}
		}
	}

}

global $woocom_exp_class;
//$woocom_exp_class = WooCommerceExport::getInstance();
$woocom_exp_class = new WooCommerceExport();
