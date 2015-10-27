<?php
/**
 * The Template for displaying all single products.
 * Modified to add support for Toolset WooCommerce Views and Layouts
 *
 * Override this template by copying it to yourtheme/woocommerce/single-product.php
 *
 * @author 		WooThemes/OnTheGoSystems
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	if ( function_exists( 'the_ddlayout' ) ) {
		
		/** Layouts plugin activated, use Layouts */
		get_header('layouts'); 
		
	} else {
		
		/** Otherwise use the usual shop */
		get_header('shop');	
			
	}
?>
	<?php
		/**
		 * woocommerce_before_main_content hook
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20
		 */
		do_action('woocommerce_before_main_content');
	?>
            <?php 
            if(defined('WC_VIEWS_VERSION')) {
            	
            	/** WooCommerce Views activated */
            	
            	if ( function_exists( 'the_ddlayout' ) ) {
            		
            		/** Layouts activated, use the Layout content rendering function */
            		the_ddlayout();
            		
            	} else {
            		
            		/** Layouts not activated, default to usual WordPress the_content() function */
            		while ( have_posts() ) {
            			the_post();
            			the_content();
            		}            		
            	}   
            	         	
            } else {
            	while ( have_posts() ) {
            		the_post();
            		/** WooCommerce Views not activated, default to WooCommerce content rendering templates */
					woocommerce_get_template_part( 'content', 'single-product' );
            	}           	
            }
            ?>
	<?php
		/**
		 * woocommerce_after_main_content hook
		 *
		 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
		 */
		do_action('woocommerce_after_main_content');
	?>

	<?php
		/**
		 * woocommerce_sidebar hook
		 *
		 * @hooked woocommerce_get_sidebar - 10
		 */
		do_action('woocommerce_sidebar');
	?>

<?php 
	if ( function_exists( 'the_ddlayout' ) ) {
		
		/** Layouts plugin activated, use Layouts */
		get_footer('layouts');
		
	} else {
		
		/** Otherwise use the usual shop */
		get_footer( 'shop' );
	}   
?>