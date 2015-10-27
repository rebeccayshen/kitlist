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
* @package     WC-Admin-Custom-Order-Fields/Views
* @author      SkyVerge
* @copyright   Copyright (c) 2012-2015, SkyVerge, Inc.
* @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * View for a new field row
 *
 * @since 1.0
 * @version 1.0
 */
?>
<tr class="wc-custom-order-field">
	<td class="check-column">
		<input type="checkbox" />
		<input type="hidden" name="wc-custom-order-field-id[<?php echo $index; ?>]" value="<?php echo esc_attr( $field_id ); ?>" />
	</td>
	<td class="wc-custom-order-field-label">
		<input type="text" name="wc-custom-order-field-label[<?php echo $index; ?>]" value="<?php echo esc_attr( isset( $field->label ) ? $field->label : null ); ?>" class="js-wc-custom-order-field-label" />
		<span class="wc-custom-order-field-id"><?php echo $field_id ? 'ID: ' .  $field_id : ''; ?></span>
	</td>
	<td class="wc-custom-order-field-type">
		<select name="wc-custom-order-field-type[<?php echo $index; ?>]" class="js-wc-custom-order-field-type wc-enhanced-select chosen_select" style="width: 100px;">
			<?php foreach ( $field_types as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $field->type ) ? $field->type : null, $value );?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</td>
	<td class="wc-custom-order-field-description">
		<input type="text" name="wc-custom-order-field-description[<?php echo $index; ?>]" value="<?php echo esc_attr( isset( $field->description ) ? $field->description : null ); ?>" class="js-wc-custom-order-field-description" />
	</td>
	<td class="wc-custom-order-field-default-values">
		<input type="text" name="wc-custom-order-field-default-values[<?php echo $index; ?>]" value="<?php echo esc_attr( isset( $field->default ) ? $field->default : null ); ?>" class="js-wc-custom-order-field-default-values placeholder" placeholder="<?php _e( 'Pipe (|) separates options', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ); ?>" />
		<img class="help_tip" width="16" height="16"  data-tip='<?php _e( 'Use Pipe (|) to separate options and surround options with double stars (**) to set as a default.', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
	</td>
	<td class="wc-custom-order-field-attributes">
		<select name="wc-custom-order-field-attributes[<?php echo $index; ?>][]" class="js-wc-custom-order-field-attributes wc-enhanced-select chosen_select" multiple="multiple" style="width: 250px;">
			<?php foreach ( $field_attributes as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $field->$value ) ? $field->$value : null );?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</td>
	<td class="js-wc-custom-order-field-draggable">
		<img src="<?php echo wc_admin_custom_order_fields()->get_plugin_url() ?>/assets/images/draggable-handle.png" />
	</td>
</tr>
