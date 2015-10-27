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
 * Admin class
 *
 * @since 1.0
 */
class WC_Admin_Custom_Order_Fields_Admin {


	/** @var string page suffix ID */
	public $page_id;

	/** @var WC_Admin_Custom_Order_Fields_Shop_Order_CPT instance */
	public $cpt;


	/**
	 * Init the class
	 *
	 * @since 1.0
	 * @return \WC_Admin_Custom_Order_Fields_Admin
	 */
	public function __construct() {

		$this->field_types = apply_filters( 'wc_admin_custom_order_fields_field_types', array(
			'text'        => __( 'Text', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			'textarea'    => __( 'Text Area', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			'select'      => __( 'Select', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			'multiselect' => __( 'Multiselect', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			'radio'       => __( 'Radio', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			'checkbox'    => __( 'Checkbox', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			'date'        => __( 'Date', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
		) );

		$this->field_attributes = apply_filters( 'wc_admin_custom_order_fields_field_attributes', array(
			'visible'    => __( 'Show in My Orders / Emails', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			'required'   => __( 'Required', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			'listable'   => __( 'Display in View Orders screen', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			'sortable'   => __( 'Allow Sorting on View Orders screen', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			'filterable' => __( 'Allow Filtering on View Orders screen', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN )
		) );

		// load view order list table / edit order screen customizations
		require_once( 'post-types/class-wc-admin-custom-order-fields-shop-order-cpt.php' );
		$this->cpt = new WC_Admin_Custom_Order_Fields_Shop_Order_CPT();

		// load styles/scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

		// load WC styles / scripts on editor screen
		add_filter( 'woocommerce_screen_ids', array( $this, 'load_wc_scripts' ) );

		// add 'Custom Order Fields' link under WooCommerce menu
		add_action( 'admin_menu', array( $this, 'add_menu_link' ) );
	}


	/**
	 * Load admin js/css
	 *
	 * @since 1.0
	 * @param string $hook_suffix the current URL filename, ie edit.php, post.php, etc
	 */
	public function load_styles_scripts( $hook_suffix ) {
		global $post_type, $wp_scripts;

		// load admin css only on view orders / edit order screens
		if ( ( 'shop_order' == $post_type && ( 'post.php' == $hook_suffix || 'post-new.php' == $hook_suffix ) ) || $this->page_id === $hook_suffix ) {

			// admin CSS
			wp_enqueue_style( 'wc-admin-custom-order-fields-admin', wc_admin_custom_order_fields()->get_plugin_url() . '/assets/css/admin/wc-admin-custom-order-fields.min.css', array( 'woocommerce_admin_styles' ), WC_Admin_Custom_Order_Fields::VERSION );

			// load JS only on editor screen
			if ( $this->page_id === $hook_suffix ) {

				// admin JS
				wp_enqueue_script( 'wc-admin-custom-order-fields-admin', wc_admin_custom_order_fields()->get_plugin_url() . '/assets/js/admin/wc-admin-custom-order-fields.min.js', array( 'jquery', 'jquery-ui-sortable', 'woocommerce_admin' ), WC_Admin_Custom_Order_Fields::VERSION );

				$params = array(
					'new_row'                  => str_replace( array( "\n", "\t" ), '', $this->get_row_html() ),
					'label_required_text'      => __( 'Label is a required field', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
					'option_required_text'     => __( 'A select/multiselect/checkbox/radio field must have at least one option', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
					'default_placeholder_text' => __( 'Pipe (|) separates options', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
				);

				// add HTML for adding new fields
				wp_localize_script( 'wc-admin-custom-order-fields-admin', 'wc_admin_custom_order_fields_params', $params );

				// add jQuery DatePicker
				wp_enqueue_script( 'jquery-ui-datepicker' );

				// get jQuery UI version
				$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

				// enqueue UI CSS
				wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css' );
			}
		}
	}


	/**
	 * Add settings/export screen ID to the list of pages for WC to load its JS on
	 *
	 * @since 1.0
	 * @param array $screen_ids
	 * @return array
	 */
	public function load_wc_scripts( $screen_ids ) {

		// sub-menu page screen ID
		$screen_ids[] = 'woocommerce_page_wc_admin_custom_order_fields';

		return $screen_ids;
	}


	/**
	 * Add 'Order Custom Fields' sub-menu link under 'WooCommerce' top level menu
	 *
	 * @since 1.0
	 */
	public function add_menu_link() {

		$this->page_id = add_submenu_page(
			'woocommerce',
			__( 'Custom Order Fields', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			__( 'Custom Order Fields', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ),
			'manage_woocommerce',
			'wc_admin_custom_order_fields',
			array( $this, 'render_editor_screen' )
		);
	}


	/**
	 * Render the custom order fields editor
	 *
	 * @since 1.0
	 */
	public function render_editor_screen() {

		?>
		<div class="wrap woocommerce">
			<form method="post" id="mainform" action="" enctype="multipart/form-data" class="wc-admin-custom-order-fields">
				<div id="icon-woocommerce" class="icon32"><br /></div>
				<h2><?php _e( 'Custom Order Fields Editor', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ); ?></h2> <?php

				// save custom fields
				if ( ! empty( $_POST ) ) {
					$this->save_custom_fields();
				}

				// show custom field editor
				$this->render_editor();

		?></form></div> <?php
	}


	/**
	 * Render the custom order fields editor table
	 *
	 * @since 1.0
	 */
	private function render_editor() {
		?>
			<div class="wc-admin-custom-order-fields-editor-content">
				<table class="widefat wc-admin-custom-order-fields-editor">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox" /></th>
							<th class="wc-custom-order-field-label"><?php _e( 'Label', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN); ?></th>
							<th width="1%" class="wc-custom-order-field-type"><?php _e( 'Type', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ); ?></th>
							<th class="wc-custom-order-field-description"><?php _e( 'Description', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ); ?></th>
							<th class="wc-custom-order-field-default-values"><?php _e( 'Default / Values', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ); ?></th>
							<th class="wc-custom-order-field-attributes"><?php _e( 'Attributes', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ); ?></th>
							<th class="js-wc-custom-order-field-draggable"></th>
						</tr>
					</thead>
					<tfoot>
					<tr>
						<th colspan="3">
							<button type="button" class="button button-secondary js-wc-admin-custom-order-fields-add-field">&nbsp;&#43; <?php _e( 'Add Field', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ); ?></button>
							<button type="button" class="button button-secondary js-wc-admin-custom-order-fields-remove"><?php _e( 'Remove Selected', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ); ?></button>
						</th>
						<th colspan="5"><input type="submit" class="button-primary" value="<?php _e( 'Save Fields', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ); ?>"/></th>
					</tr>
					</tfoot>
					<tbody>
						<?php
							$index = 0;

							foreach ( wc_admin_custom_order_fields()->get_order_fields() as $custom_field_id => $custom_field ) {

								echo $this->get_row_html( $index, $custom_field_id, $custom_field );

								$index++;
							}
						?>
					</tbody>
				</table>
			</div>
		<?php

		wp_nonce_field( __FILE__ );
	}


	/**
	 * Return the HTML for a new field row
	 *
	 * @since 1.0
	 * @param int $index the row index
	 * @param int $field_id the ID of the custom field
	 * @param array $field the custom field data
	 * @return string the HTML
	 */
	private function get_row_html( $index = null, $field_id = null, $field = null ) {

		$field_types      = $this->field_types;
		$field_attributes = $this->field_attributes;

		if ( is_object( $field ) ) {

			// convert options and defaults back into a simple string
			if ( $field->has_options() ) {

				$values = array();

				foreach( $field->get_options() as $option ) {

					// skip blank option added for non-required select/multiselect
					if ( empty( $option['label'] ) )
						continue;

					$values[] = $option['selected'] ? '**' . $option['label'] . '**' : $option['label'];
				}

				$field->default = implode( ' | ', $values );
			}

			// convert date field default from timestamp into string
			if ( 'date' === $field->type && isset( $field->default ) && 'now' !== $field->default ) {
				$field->default = date( 'Y-m-d', $field->default );
			}
		}

		ob_start();

		require( 'views/html-field-editor-table-row.php' );

		return ob_get_clean();
	}


	/**
	 * Save the custom fields
	 *
	 * @since 1.0
	 */
	private function save_custom_fields() {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], __FILE__ ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ) );
		}

		$fields = array();

		if ( ! empty( $_POST['wc-custom-order-field-id'] ) ) {

			for ( $index = 0; $index < count( $_POST['wc-custom-order-field-id'] ); $index++ ) {

				// ID - assigned if empty
				$field_id = ( empty( $_POST['wc-custom-order-field-id'][ $index ] ) ) ? $this->get_next_field_id() : absint( $_POST['wc-custom-order-field-id'][ $index ] );

				$fields[ $field_id ] = array();

				// label
				$fields[ $field_id ]['label'] = sanitize_text_field( stripslashes( $_POST['wc-custom-order-field-label'][ $index ] ) );

				// type
				$fields[ $field_id ]['type'] = ( in_array( $_POST['wc-custom-order-field-type'][ $index], array_keys( $this->field_types ) ) ) ? $_POST['wc-custom-order-field-type'][ $index ] : 'text';

				// description
				if ( ! empty( $_POST['wc-custom-order-field-description'][ $index ] ) ) {
					$fields[ $field_id ]['description'] = sanitize_text_field( stripslashes( $_POST['wc-custom-order-field-description'][ $index ] ) );
				}

				// default / values
				if ( ! empty( $_POST['wc-custom-order-field-default-values'][ $index ] ) ) {

					switch ( $fields[ $field_id ]['type'] ) {

						// text/textarea fields have simple text defaults and no options
						case 'text':
						case 'textarea':
							$fields[ $field_id ]['default'] = sanitize_text_field( stripslashes( $_POST['wc-custom-order-field-default-values'][ $index ] ) );
							break;

						// select/checkbox/radio fields have multiple options and a single default, multiselect has multiple options and multiple defaults
						case 'select':
						case 'multiselect':
						case 'checkbox':
						case 'radio':
							$options = array_map( 'sanitize_text_field', explode( '|', $_POST['wc-custom-order-field-default-values'][ $index ] ) );

							foreach ( $options as $option ) {

								$fields[ $field_id ]['options'][] = array(
									'default' => false !== strpos( $option, '**' ),
									'label'   => stripslashes( str_replace( '**', '', $option ) ),
									'value'   => sanitize_key( $option ),
								);
							}
							break;

						// date is saved as a unix timestamp (UTC), `now` is a special default
						case 'date':
							$fields[ $field_id ]['default'] = ( 'now' === $_POST['wc-custom-order-field-default-values'][ $index ] ) ? 'now' : strtotime( $_POST['wc-custom-order-field-default-values'][ $index ] );
							break;

						// allow custom field types
						default:
							$fields[ $field_id ]['default'] = apply_filters( 'wc_admin_custom_order_fields_' . $fields[ $field_id ]['type'] . '_default', '',      $_POST['wc-custom-order-field-default-values'][ $index ], $field_id );
							$fields[ $field_id ]['options'] = apply_filters( 'wc_admin_custom_order_fields_' . $fields[ $field_id ]['type'] . '_options', array(), $_POST['wc-custom-order-field-default-values'][ $index ], $field_id );
					}

				} else {

					$fields[ $field_id ]['default'] = null;
				}

				// attributes - true/false for each
				if ( ! empty( $_POST['wc-custom-order-field-attributes'][ $index ] ) ) {

					foreach ( array( 'required', 'visible', 'listable', 'sortable', 'filterable' ) as $attribute ) {
						$fields[ $field_id ][ $attribute ] = ( in_array( $attribute, $_POST['wc-custom-order-field-attributes'][ $index ] ) );
					}

					// add the listable attribute if either sortable or filterable were added
					if ( ! $fields[ $field_id ]['listable'] && ( $fields[ $field_id ]['sortable'] || $fields[ $field_id ]['filterable'] ) ) {
						$fields[ $field_id ]['listable'] = true;
					}
				}

				// scope (not exposed in editor right now)
				$fields[ $field_id ]['scope'] = 'order';
			}
		}

		if ( true === update_option( 'wc_admin_custom_order_fields', $fields ) ) {
			echo '<div class="updated"><p>' . __( 'Custom Fields Saved', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ) . '</p></div>';
		}
	}


	/**
	 * Get the next available custom field ID
	 *
	 * @since 1.0
	 * @return int the next available field ID
	 */
	private function get_next_field_id() {

		$next_field_id = get_option( 'wc_admin_custom_order_fields_next_field_id' );

		update_option( 'wc_admin_custom_order_fields_next_field_id', ++$next_field_id );

		return $next_field_id;
	}


} // end \WC_Admin_Custom_Order_Fields_Admin class
