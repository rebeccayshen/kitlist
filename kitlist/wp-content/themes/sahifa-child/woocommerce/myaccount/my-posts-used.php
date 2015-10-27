<?php
/**
* Created by IntelliJ IDEA.
* @author: Rebecca Shen for KIT List
* Date: 6/4/15
* Time: 3:04 PM
* Created to show how many posts were used by the user
**  @version     2.2.0*
*/

/**
 * My posts used
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$user_id = get_current_user_id();
$job_posts = get_view_query_results('818');

if ( $job_posts ) : ?>

<h2><?php echo apply_filters( 'woocommerce_my_account_my_orders_title', __( 'Recent Posts', 'woocommerce' ) ); ?></h2>

<table class="shop_table shop_table_responsive my_account_orders">
    <thead>
    <tr>
        <th class="order-number"><span class="nobr"><?php _e( 'Post ID', 'woocommerce' ); ?></span></th>
        <th class="order-date"><span class="nobr"><?php _e( 'Date', 'woocommerce' ); ?></span></th>
        <th class="order-total"><span class="nobr"><?php _e( 'Job Title', 'woocommerce' ); ?></span></th>
        <th class="order-actions">&nbsp;</th>
    </tr>
    </thead>
    <tbody><?php
        foreach($job_posts as $job_post) { ?>
       <tr class="order">

            <td class="order-number" data-title="<?php _e('Post ID', 'woocommerce'); ?>"> 
                <a href="<?php echo esc_url(get_permalink($job_post -> ID) );?>"> 
                    <?php echo $job_post -> ID; ?>  </a> </td> 

            <td class="orer-date" data-title="<?php _e('Date', 'woocommerce'); ?>"> 

                <time datetime="<?php echo date( 'Y-m-d', strtotime( $job_post->post_date ) ); ?>"
                      title="<?php echo esc_attr( strtotime( $job_post->post_date ) ); ?>">
                    <?php echo date_i18n( get_option( 'date_format' ), strtotime( $job_post->post_date ) ); ?></time> 
            </td> 

            <td class="order-action" data-title ="<?php _e('Title', 'woocommerce'); ?>"
                style="text-align:left; white-space:nowrap;"> 
                <?php _e($job_post -> post_title ); ?>
            </td> 



            </tr><?php
    }
    ?></tbody>

</table>

<?php endif; ?>

