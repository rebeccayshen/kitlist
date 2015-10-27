<?php
$themename = "Sahifa-child";
$themefolder = "sahifa-child";

define ('theme_name', $themename );
define ('theme_ver' , 1 );
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}

remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
add_filter( 'woocommerce_payment_complete_order_status', 'virtual_order_payment_complete_order_status', 10, 2 );

/*function virtual_order_payment_complete_order_status( $order_status, $order_id ) {
    $order = new WC_Order( $order_id );
    if ( 'processing' == $order_status &&
        ( 'on-hold' == $order->status || 'pending' == $order->status || 'failed' == $order->status ) ) {
        $virtual_order = null;
        if ( count( $order->get_items() ) > 0 ) {
            foreach( $order->get_items() as $item ) {
                if ( 'line_item' == $item['type'] ) {
                    $_product = $order->get_product_from_item( $item );
                    if ( ! $_product->is_virtual() ) {
                        // once we've found one non-virtual product we know we're done, break out of the loop
                        $virtual_order = false;
                        break;
                    } else {
                        $virtual_order = true;
                    }
                }
            }
        }
        // virtual order, mark as completed
        if ( $virtual_order ) {
            return 'completed';
        }
    }
    // non-virtual order, return original status
    return $order_status;
}*/

/**
 * Auto Complete all WooCommerce orders.
 * Add to theme functions.php file
 */

add_action( 'woocommerce_thankyou', 'custom_woocommerce_auto_complete_order' );
function custom_woocommerce_auto_complete_order( $order_id ) {
	global $woocommerce;

	if ( !$order_id )
		return;
	$order = new WC_Order( $order_id );
	$order->update_status( 'completed' );
}
// Add WooCommerce customer username to edit/view order admin page
add_action( 'woocommerce_admin_order_data_after_billing_address', 'woo_display_order_username', 10, 1 );

function woo_display_order_username( $order ){

	global $post;

	$customer_user = get_post_meta( $post->ID, '_customer_user', true );
	echo '<p><strong style="display: block;">'.__('Customer Username').':</strong> <a href="user-edit.php?user_id=' . $customer_user . '">' . get_user_meta( $customer_user, 'nickname', true ) . '</a></p>';
}

//REBECCA CUSTOMIZATION


add_action('woocommerce_payment_complete_order_status', 'kit_set_custom_order_fields',10, 2);
function kit_set_custom_order_fields(){


}



remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

add_filter( 'woocommerce_payment_complete_order_status', 'virtual_order_payment_complete_order_status', 10, 2 );
function virtual_order_payment_complete_order_status( $order_status, $order_id ) {
    $order = new WC_Order( $order_id );
    if ( 'processing' == $order_status &&
        ( 'on-hold' == $order->status || 'pending' == $order->status || 'failed' == $order->status ) ) {
        $virtual_order = null;
        if ( count( $order->get_items() ) > 0 ) {
            foreach( $order->get_items() as $item ) {
                if ( 'line_item' == $item['type'] ) {
                    $_product = $order->get_product_from_item( $item );
                    if ( ! $_product->is_virtual() ) {
                        // once we've found one non-virtual product we know we're done, break out of the loop
                        $virtual_order = false;
                        break;
                    } else {
                        $virtual_order = true;
                    }
                }
            }
        }
        // virtual order, mark as completed
        if ( $virtual_order ) {
            return 'completed';
        }
    }
    // non-virtual order, return original status
    return $order_status;
}



//CRED form customization

//Categorizes the job post as non tech or tech depending on the radio button
//and subtract one from the job posting allowance
add_action('cred_save_data_533', 'save_data_for_form_with_id_533', 10, 2);
function save_data_for_form_with_id_533($post_id, $form_data) {

    $job_type= types_render_field("job-type", array("raw" => "true"));

    if ($job_type == '1') {
        $term_list= array('28');
    }
    elseif ($job_type == '2') {
        $term_list = array('29');
    }
    else {
        $term_list= array('8');
    }

    $my_taxonomy = 'category';

    /*
    if ($form_data['job-type']== '1') {  //tech job
        $term_list = array('28');
    }
    elseif ($form_data['job-type']== '2'){  //non-tech job
        $term_list= array('29');
    }
    else {
        $term_list=array('8');
    }
*/
    wp_set_post_terms($post_id, $term_list, $my_taxonomy);

}



//woocommerce order information customization:  displaying additional information for orders

//add total number of posts before the subtotal
add_action('woocommerce_order_items_table', 'kit_order_items_table', 10, 1);

//add caveat about 30 days from order placed.
add_action('woocommerce_order_details_after_order_table', 'kit_order_details_after_order_table', 10, 1);



//do action to display total number of posts available in this order
function kit_order_items_table($order){

    $total_posts = kit_get_total_posts_from_order($order);
    ?>
    <th scope="row">Number of Posts For the Order:*</th>

    <td><?php echo kit_get_total_posts_from_order($order);?></td>
    </tr>
<?php
}


//Returns total number of posts from an order
function kit_get_total_posts_from_order($order){
    $sum=0;
    foreach( $order->get_items() as $item_id => $item ) {
        $qty = $item['qty'];
        $_product = apply_filters('woocommerce_order_item_product', $order->get_product_from_item($item), $item);
        $t_array = get_post_custom_values('kit_numb_posts', $_product->id);

        for ($x = 0; $x <= count($t_array); $x++) {
            $sum = $sum + ($t_array[$x] * $qty);
        }

    }
    return $sum;
}

//return number of posts used to date by a user id
function kit_jobs_post_count_by_user($user_id) {

    return count_user_posts($user_id, 'kit-job-post');
}

function kit_jobs_posted_by_current_user() {
    $user_id = get_current_user_id();

    return count_user_posts($user_id, 'kit-job-post');
}


//return an array of posts for the given user and post type
function kit_get_posts($user_id, $post_type) {
    $args = array(
//        'numberposts' => -1,
  //      'meta_key'    => '_customer_user',
        'meta_value'  => $user_id,
//        'post_type'   => $post_type,
 //       'post_status' => 'publish' ,
 //       'date_query' => array(
 //           'after' => date('Y-m-d', strtotime('-30 days')))
    );

    $all_posts = get_posts($args);

    return $all_posts;


}


//set up queries that get the customer orders within the last 30 days
function kit_get_customer_orders(){
    $customer_orders = get_posts(apply_filters('woocommerce_my_account_my_orders_query', array(
        'numberposts' => $order_count,
        'meta_key' => '_customer_user',
        'meta_value' => get_current_user_id(),
        'post_type' => wc_get_order_types('view-orders'),
        'post_status' => array_keys( wc_get_order_statuses() ),
        'date_query' => array(
            'after' => date('Y-m-d', strtotime('-30 days')))
    )));

    return $customer_orders;

}


//return number of posts still available by a user id only from orders with 'completed' status
//only consider orders within the last 30 days
function kit_job_posts_available($user_id)
{
    $customer_orders = kit_get_customer_orders($user_id);
    $sum = 0;
    foreach ( $customer_orders as $customer_order ) {
        $order = wc_get_order($customer_order);
        $order->populate($customer_order);
 //       $item_count = $order->get_item_count();
        $post_count = kit_get_total_posts_from_order($order);

        $sum = $sum + $post_count;
    }
    $posted = kit_jobs_post_count_by_user($user_id);
    $tresult = $sum - $posted;

    return $tresult;
}



//adding caveat information at the end of the order detail
function kit_order_details_after_order_table($order) {
    ?>*The number of posts are valid for 30 days from purchase<br/><?php

}


function kit_job_posts_available_for_current_user()
{
    $user_id = get_current_user_id();
    return kit_job_posts_available($user_id);


}


add_shortcode('posts_available', 'kit_job_posts_available_for_current_user');
add_shortcode('jobs_posted', 'kit_jobs_posted_by_current_user');





/**
 * Add new register fields for  registration.
 *
 * @return string Register fields HTML.
 */
function kit_extra_register_fields() {
    ?>

    <p class="form-row form-row-first">
        <label for="reg_billing_first_name"><?php _e( 'First name', 'woocommerce' ); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if ( ! empty( $_POST['billing_first_name'] ) ) esc_attr_e( $_POST['billing_first_name'] ); ?>" />
    </p>

    <p class="form-row form-row-last">
        <label for="reg_billing_last_name"><?php _e( 'Last name', 'woocommerce' ); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) esc_attr_e( $_POST['billing_last_name'] ); ?>" />
    </p>

    <div class="clear"></div>

    <p class="form-row form-row-wide">
        <label for="reg_billing_phone"><?php _e( 'Phone', 'woocommerce' ); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php if ( ! empty( $_POST['billing_phone'] ) ) esc_attr_e( $_POST['billing_phone'] ); ?>" />
    </p>

<?php
}

add_action( 'woocommerce_register_form_start', 'kit_extra_register_fields' );

/**
 * Save the extra register fields.
 *
 * @param  int  $customer_id Current customer ID.
 *
 * @return void
 */
function kit_save_extra_register_fields( $customer_id )
{
    if (isset($_POST['billing_first_name'])) {
        // WordPress default first name field.
        update_user_meta($customer_id, 'first_name', sanitize_text_field($_POST['billing_first_name']));

        // WooCommerce billing first name.
        update_user_meta($customer_id, 'billing_first_name', sanitize_text_field($_POST['billing_first_name']));
    }

    if (isset($_POST['billing_last_name'])) {
        // WordPress default last name field.
        update_user_meta($customer_id, 'last_name', sanitize_text_field($_POST['billing_last_name']));

        // WooCommerce billing last name.
        update_user_meta($customer_id, 'billing_last_name', sanitize_text_field($_POST['billing_last_name']));
    }

}

add_action( 'woocommerce_created_customer', 'kit_save_extra_register_fields' );

/**
 * Validate the extra register fields.
 *
 * @param  string $username          Current username.
 * @param  string $email             Current email.
 * @param  object $validation_errors WP_Error object.
 *
 * @return void
 */
function kit_validate_extra_register_fields( $username, $email, $validation_errors ) {
    if ( isset( $_POST['billing_first_name'] ) && empty( $_POST['billing_first_name'] ) ) {
        $validation_errors->add( 'billing_first_name_error', __( '<strong>Error</strong>: First name is required!', 'woocommerce' ) );
    }

    if ( isset( $_POST['billing_last_name'] ) && empty( $_POST['billing_last_name'] ) ) {
        $validation_errors->add( 'billing_last_name_error', __( '<strong>Error</strong>: Last name is required!.', 'woocommerce' ) );
    }


}

add_action( 'woocommerce_register_post', 'kit_validate_extra_register_fields', 10, 3 );

?>