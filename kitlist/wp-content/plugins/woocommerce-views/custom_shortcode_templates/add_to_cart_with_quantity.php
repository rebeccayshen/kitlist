<?php
/**
 * Loop Add to Cart
 * This is an add to cart loop button customization to display quantity next to the button.
 * Original code is here: http://docs.woothemes.com/document/override-loop-template-and-show-quantities-next-to-add-to-cart-buttons/
 * Modified to support latest templates and different WooCommerce versions.
 */
 
global $product,$new_wc_codes; 

if( $product->get_price() === '' && $product->product_type != 'external' ) return;
?>

<?php if ( ! $product->is_in_stock() ) : ?>
		
	<a href="<?php echo get_permalink($product->id); ?>" class="button"><?php echo apply_filters('out_of_stock_add_to_cart_text', __('Read More', 'woocommerce')); ?></a>

<?php else : ?>
	
	<?php 
	
		switch ( $product->product_type ) {
			case "variable" :		
				if ($new_wc_codes) {		
					$link = $product->add_to_cart_url();
					$label 	= $product->add_to_cart_text();
				} else {
					$link 	= get_permalink($product->id);
					$label 	= apply_filters('variable_add_to_cart_text', __('Select options', 'woocommerce'));					
				}
			break;
			case "grouped" :	
				if ($new_wc_codes) {			
					$link = $product->add_to_cart_url();
					$label 	= $product->add_to_cart_text();
				} else {
					$link 	= get_permalink($product->id);
					$label 	= apply_filters('grouped_add_to_cart_text', __('View options', 'woocommerce'));					
				}
			break;
			case "external" :				
				if ($new_wc_codes) { 
					$link = $product->add_to_cart_url();
					$label 	= $product->add_to_cart_text();
				} else {
					$link 	= get_permalink($product->id);
					$label 	= apply_filters('external_add_to_cart_text', __('Read More', 'woocommerce'));
				}
			break;
			default :
				if ($new_wc_codes) {
					$link = $product->add_to_cart_url();
					$label 	= $product->add_to_cart_text();
				} else {
					$link 	= esc_url( $product->add_to_cart_url() );
					$label 	= apply_filters('add_to_cart_text', __('Add to cart', 'woocommerce'));					
				}
			break;
		}

		if ( $product->product_type == 'simple' ) {
			
			?>
			<form action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="cart" method="post" enctype='multipart/form-data'>
		
			 	<?php woocommerce_quantity_input(); ?>
		
			 	<button type="submit" class="button alt"><?php echo $label; ?></button>
		
			</form>
			<?php
			
		} else {
			
			printf('<a href="%s" rel="nofollow" data-product_id="%s" class="button add_to_cart_button product_type_%s">%s</a>', $link, $product->id, $product->product_type, $label);
			
		}

	?>

<?php endif; ?>