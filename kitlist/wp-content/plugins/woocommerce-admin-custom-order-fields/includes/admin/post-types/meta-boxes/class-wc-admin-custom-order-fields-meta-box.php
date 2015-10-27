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
 * Meta-box adds, renders, and save the custom order fields displayed on the Edit Order screen
 *
 * @since 1.0
 */
class WC_Admin_Custom_Order_Fields_Meta_Box {


	/**
	 * Add actions
	 *
	 * @since 1.0
	 */
	public function __construct() {

		// add the meta box
		add_action( 'add_meta_boxes', array( $this, 'add' ) );

		// save the meta box
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save' ) );
	}

	/**
	 * Add the meta-box
	 *
	 * @since 1.0
	 */
	public function add() {

		add_meta_box( 'wc-order-custom-fields', __( 'Order Custom Fields', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ), array( $this, 'render' ), 'shop_order', 'normal', 'default' );
	}


	/**
	 * Order custom fields meta box
	 *
	 * Displays the order custom fields meta box - for displaying and configuring
	 * any custom fields attached to the order
	 */
	/**
	 * Render the custom order fields
	 *
	 * @since 1.0
	 */
	public function render() {
		global $post;

		$order_fields = wc_admin_custom_order_fields()->get_order_fields( $post->ID );

		?>
		<ul>
			<?php foreach ( $order_fields as $field ) : ?>
				<li class="form-field">
					<label for="wc-admin-custom-order-fields-input-<?php echo esc_attr( $field->id ); ?>">
						<?php _e( $field->label, WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ); ?> <?php if ( $field->is_required() ) echo '<span class="required">*</span>'; ?> <?php if ( isset( $field->description ) ) : ?><img class="help_tip" width="16" height="16"  data-tip="<?php echo esc_attr( $field->description ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" /><?php endif; ?></label>
					<?php
					$name = sprintf( 'wc-admin-custom-order-fields[%s]', esc_attr( $field->id ) );
					$id   = sprintf( 'wc-admin-custom-order-fields-input-%s', esc_attr( $field->id ) );

					switch( $field->type ) :

						case 'text':
							printf( '<input type="text" name="%s" id="%s" value="%s" />', $name, $id, esc_attr( $field->get_value() ) );
							break;

						case 'textarea':
							printf( '<textarea name="%s" id="%s">%s</textarea>', $name, $id, esc_textarea( $field->get_value() ) );
							break;

						case 'date':
							printf( '<input type="text" name="%s" id="%s" class="date-picker-field" maxlength="10" value="%s" />', $name, $id, ( $field->get_value() ) ? date( 'Y-m-d', $field->get_value() ) : '' );
							break;

						case 'select':
						case 'multiselect':
							printf( '<select name="%s" id="%s" class="wc-enhanced-select chosen_select" style="width: 95%%" %s %s/>', 'multiselect' === $field->type ? $name . '[]' : $name, $id, 'multiselect' === $field->type ? 'multiple="multiple"' : '', 'data-placeholder="' . ( 'multiselect' === $field->type ? __( 'Select Some Options', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ) : __( 'Select an Option', WC_Admin_Custom_Order_Fields::TEXT_DOMAIN ) ) . '"' );
							foreach( $field->get_options() as $option )
								printf( '<option value="%s" %s>%s</option>', esc_attr( $option['value'] ), selected( $option['selected'], true, false ), esc_html( $option['label'] ) );
							echo '</select';
							break;

						case 'checkbox':
						case 'radio':
							$option_count = 0;
							foreach ( $field->get_options() as $option ) {
								printf( '<label for="%s-%s">%s</label>', $id, $option_count, esc_html( $option['label'] ) );
								printf( '<input type="%s" name="%s" id="%s-%s" value="%s" %s />', esc_attr( $field->type ), 'checkbox' === $field->type ? $name . '[]' : $name, $id, $option_count, esc_attr( $option['value'] ), checked( $option['selected'], true, false ) );
								$option_count++;
							}
							break;
					endswitch;
					?>
				</li>
			<?php endforeach; ?>
			</ul><div style="clear: both;"></div>
		<?php
	}


	/**
	 * Persist any order custom fields
	 *
	 * @since 1.0
	 * @param int $order_id the WC_Order ID
	 */
	public function save( $order_id ) {

		if ( empty( $_POST['wc-admin-custom-order-fields'] ) ) {
			return;
		}

		$order_fields = wc_admin_custom_order_fields()->get_order_fields();

		foreach ( $_POST['wc-admin-custom-order-fields'] as $field_id => $field_value ) {

			if ( ! isset( $order_fields[ $field_id ] ) ) {
				continue;
			}

			if ( 'date' === $order_fields[ $field_id ]->type ) {
				$field_value = strtotime( $field_value );

				$order_fields[ $field_id ]->set_value( $field_value );

				// this column is used so that date fields can be searchable.  not a perfect solution, but a compromise
				update_post_meta( $order_id, $order_fields[ $field_id ]->get_meta_key() . '_formatted', $order_fields[ $field_id ]->get_value_formatted() );
			}

			update_post_meta( $order_id, $order_fields[ $field_id ]->get_meta_key(), $field_value );
		}
	}


} // end \WC_Admin_Custom_Order_Fields_Meta_Box class
