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
 * Custom Order Field class
 *
 * @since 1.0
 */
class WC_Custom_Order_Field {


	/** @var array the custom field raw data */
	private $data;

	/** @var string the custom field ID */
	private $id;

	/** @var mixed the custom field value */
	public $value;

	/** @var boolean have we run the field options filter already? */
	private $has_run_field_options_filter = false;


	/**
	 * Setup the custom field
	 *
	 * @since 1.0
	 * @param int $id the custom field ID
	 * @param array $data the custom field raw data
	 * @return \WC_Custom_Order_Field
	 */
	public function __construct( $id, array $data ) {

		$this->id   = $id;
		$this->data = $data;
	}



	/**
	 * Magic method for getting custom field properties
	 *
	 * @since 1.0
	 * @param string $key the class member name
	 * @return mixed
	 */
	public function __get( $key ) {

		switch ( $key ) {

			case 'id':
				return $this->id;

			case 'label':
				return $this->data['label'];

			case 'type':
				return $this->data['type'];

			case 'default':
				return isset( $this->data['default'] ) ? $this->data['default'] : null;

			case 'description':
				return isset( $this->data['description'] ) ? $this->data['description'] : null;

			case 'required':
				return $this->is_required();

			case 'visible':
				return $this->is_visible();

			case 'listable':
				return $this->is_listable();

			case 'sortable':
				return $this->is_sortable();

			case 'filterable':
				return $this->is_filterable();

			default:
				return null;
		}
	}


	/**
	 * Magic method for checking if custom field properties are set
	 *
	 * @since 1.0
	 * @param string $key the class member name
	 * @return bool
	 */
	public function __isset( $key ) {

		switch( $key ) {

			// field properties are always set
			case 'required':
			case 'visible':
			case 'listable':
			case 'sortable':
			case 'filterable':
				return true;

			case 'value':
				return isset( $this->value );

			default:
				return isset( $this->data[ $key ] );
		}
	}


	/**
	 * Gets order meta name for the field, which is the field ID prefixed with `_wc_acof_`
	 *
	 * @since 1.0
	 * @return string database option name for this field
	 */
	public function get_meta_key() {

		return '_wc_acof_' . $this->id;
	}


	/**
	 * Sets the value for this field
	 *
	 * @since 1.0
	 * @param mixed $value the value
	 */
	public function set_value( $value ) {

		$this->value = $value;

		// for multi items, remove any default selections, and select the actual values
		if ( $this->has_options() ) {

			if ( ! is_array( $value ) ) {
				$value = array( $value );
			}

			// make sure the options have been populated
			$this->get_options();

			foreach ( $this->data['options'] as $key => $option ) {

				if ( in_array( $option['value'], $value ) )
					$this->data['options'][ $key ]['selected'] = true;
				else
					$this->data['options'][ $key ]['selected'] = false;
			}
		}
	}


	/**
	 * Gets the field value, using the default if any and no value has been set
	 *
	 * @since 1.0
	 * @return mixed the field value
	 */
	public function get_value() {

		$value = $this->value;

		if ( ! isset( $this->value ) && $this->default ) {

			if ( 'date' == $this->type && 'now' == $this->default ) {
				$value = time();
			} else {
				$value = $this->default;
			}
		}

		return $value;
	}


	/**
	 * Get the custom field value, formatted based on field type.  This will not
	 * be the default if no value has been set yet.
	 *
	 * @since 1.0
	 * @return string formatted value
	 */
	public function get_value_formatted() {

		// note we use value directly to avoid returning a default that would be displayed to a user
		switch ( $this->type ) {

			case 'date':
				return $this->value ? date_i18n( get_option( 'date_format' ), $this->value ) : '';

			case 'select':
			case 'multiselect':
			case 'checkbox':
			case 'radio':

				$options = $this->get_options();

				$value = array();

				foreach ( $options as $option ) {

					if ( $option['selected'] ) {

						$value[] = $option['label'];
					}
				}

				return implode( ', ', $value );

			default:
				return $this->value;
		}
	}


	/**
	 * Returns true if this is a multi-item field (select, multiselect, radio,
	 * checkbox)
	 *
	 * @since 1.0
	 * @return bool true if this is a multi-item field, false otherwise
	 */
	public function has_options() {

		return in_array( $this->type, array( 'select', 'multiselect', 'radio', 'checkbox' ) );
	}


	/**
	 * Get the options for the select, multiselect, radio and checkbox types.
	 * If no value has been set, items are marked as selected according to any
	 * configured defaults.
	 *
	 * @since 1.0
	 * @return array of arrays containing 'default', 'selected', 'label', 'value' keys
	 */
	public function get_options() {

		if ( ! $this->has_options() ) return null;

		// configured options
		$options = isset( $this->data['options'] ) && $this->data['options'] ? $this->data['options'] : array();

		// allow other plugins to hook in and supply their own options, but only run this filter once to avoid duplicate intensive operations
		if ( ! $this->has_run_field_options_filter ) {
			$this->data['options'] = $options = apply_filters( 'wc_admin_custom_order_field_options', $options, $this );
			$this->has_run_field_options_filter = true;
		}

		// set default values if no value provided
		if ( ! isset( $this->value ) ) {

			foreach ( $options as $key => $option ) {

				if ( $option['default'] ) {
					$options[ $key ]['selected'] = true;
				} else {
					$options[ $key ]['selected'] = false;
				}
			}
		}

		// add an empty option for non-required select/multiselect
		if ( ! $this->is_required() && ( 'select' === $this->type || 'multiselect' === $this->type ) ) {
			array_unshift( $options, array( 'default' => false, 'label' => '', 'value' => '', 'selected' => false ) );
		}

		return $options;
	}


	/**
	 * Returns true if this is a required field, false otherwise
	 *
	 * @since 1.0
	 * @return bool true if this field is required, false otherwise
	 */
	public function is_required() {

		return isset( $this->data['required'] ) && $this->data['required'];
	}

	/**
	 * Returns true if this field is visible to the customer (in order emails/my account > order views), false otherwise
	 *
	 * @since 1.0
	 * @return bool true if this field is required, false otherwise
	 */
	public function is_visible() {

		return isset( $this->data['visible'] ) && $this->data['visible'];
	}


	/**
	 * Returns true if this custom field should be displayed in the Order admin
	 * list
	 *
	 * @since 1.0
	 * @return bool true if the field should be displayed in the orders list
	 */
	public function is_listable() {

		return isset( $this->data['listable'] ) && $this->data['listable'];
	}


	/**
	 * Returns true if this listable custom field is also sortable
	 *
	 * @since 1.0
	 * @return bool true if the field should be sortable in the orders list
	 */
	public function is_sortable() {

		return $this->is_listable() && isset( $this->data['sortable'] ) && $this->data['sortable'];
	}


	/**
	 * Returns true if this listable custom field is also filterable in the
	 * Orders admin
	 *
	 * @since 1.0
	 * @return bool true if the field is both listable and filterable
	 */
	public function is_filterable() {

		return $this->is_listable() && isset( $this->data['filterable'] ) && $this->data['filterable'];
	}


	/**
	 * Returns true if the custom field is numeric
	 *
	 * @return bool true if the the field is numeric, false otherwise
	 */
	public function is_numeric() {

		return $this->type == 'date' || ( isset( $this->data['is_numeric'] ) && $this->data['is_numeric'] );
	}


} // end \WC_Custom_Order_Field class
