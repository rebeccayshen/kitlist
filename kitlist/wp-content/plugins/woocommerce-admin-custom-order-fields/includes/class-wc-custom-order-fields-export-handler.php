<?php
/**
 * WooCommerce Admin Custom Order Fields
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Admin Custom Order Fields to newer
 * versions in the future. If you wish to customize WooCommerce Admin Custom Order Fields for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-admin-custom-order-fields/ for more information.
 *
 * @package     WC-Admin-Custom-Order-Fields/Classes
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2015, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Custom Order Fields Export Handler class
 *
 * @since 1.2.0
 */
class WC_Admin_Custom_Order_Fields_Export_Handler {


	/**
	 * Setup class
	 *
	 * @since 1.2.0
	 */
	public function __construct() {

		// Customer / Order CSV Export column headers/data
		add_filter( 'wc_customer_order_csv_export_order_headers', array( $this, 'add_fields_to_csv_export_column_headers' ), 10, 2 );
		add_filter( 'wc_customer_order_csv_export_order_row',     array( $this, 'add_fields_to_csv_export_column_data' ), 10, 4 );
	}


	/**
	 * Adds support for Customer/Order CSV Export by adding a column header for
	 * each registered admin order field
	 *
	 * @since 1.2.0
	 * @param array $headers existing array of header key/names for the CSV export
	 * @return array
	 */
	public function add_fields_to_csv_export_column_headers( $headers, $csv_generator ) {

		$field_headers = array();

		foreach ( wc_admin_custom_order_fields()->get_order_fields() as $field_id => $field ) {
			$field_headers[ 'admin_custom_order_field_' . $field_id ] = 'admin_custom_order_field:' . str_replace( '-', '_', sanitize_title( $field->label ) ) . '_' . $field_id;
		}

		return array_merge( $headers, $field_headers );
	}


	/**
	 * Adds support for Customer/Order CSV Export by adding data for admin order fields
	 *
	 * @since 1.2.0
	 * @param array $order_data generated order data matching the column keys in the header
	 * @param WC_Order $order order being exported
	 * @param \WC_Customer_Order_CSV_Export_Generator $csv_generator instance
	 * @return array
	*/
	public function add_fields_to_csv_export_column_data( $order_data, $order, $csv_generator ) {

		$field_data = array();

		foreach ( wc_admin_custom_order_fields()->get_order_fields( $order->id ) as $field_id => $field ) {
			$field_data[ 'admin_custom_order_field_' . $field_id ] = $field->get_value_formatted();
		}

		$new_order_data = array();

		if ( isset( $csv_generator->order_format ) && ( 'default_one_row_per_item' == $csv_generator->order_format || 'legacy_one_row_per_item' == $csv_generator->order_format ) ) {

			foreach ( $order_data as $data ) {
				$new_order_data[] = array_merge( $field_data, (array) $data );
			}

		} else {

			$new_order_data = array_merge( $field_data, $order_data );
		}

		return $new_order_data;
	}


} // end \WC_Admin_Custom_Order_Fields_Export_Handler class
