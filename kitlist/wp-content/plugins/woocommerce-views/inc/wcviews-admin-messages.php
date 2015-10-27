<?php
/**
 * wcviews-admin-messages.php
 *
 * Messages and Help Text for Tooltips.
 * Formatting and Editing Instructions Text as it appears in Views
 *
 * @package WooCommerce Views
 *
 * @since 2.4
 */

global $wcviews_edit_help;

$wcviews_edit_help =
array(
		/** ASSIGN PAGE TEMPLATES FOR WOOCOMMERCE SINGLE PRODUCTS SECTION TEXT */
		
		'template_assignment_section' =>
		array(				
				// Tooltip title
				
				'title' => htmlentities( __('Assign PHP Page Templates', 'woocommerce_views'), ENT_QUOTES ),
				
				// Tooltip content 
				
				'content' => htmlentities( __('For maximum flexibility, you should choose a PHP template that outputs as little as possible. You will be able to add all product fields using Views.', 'woocommerce_views'), ENT_QUOTES ),
		        
				// Bottom message content and link /
				
				'message_for_link' => NULL
	
		),
		
		'archive_template_assignment_section' =>
		array(
				// Tooltip title
		
				'title' => htmlentities( __('Assign PHP Product Archive Templates', 'woocommerce_views'), ENT_QUOTES ),
		
				// Tooltip content
		
				'content' => htmlentities( __('For maximum flexibility, you should choose a product archive template that outputs as little as possible. You will be able to add all product fields using Views.', 'woocommerce_views'), ENT_QUOTES ),
		
				// Bottom message content and link /
		
				'message_for_link' => NULL
		
		),
				
		/** WOOCOMMERCE STYLING SECTION TEXT */		
		
		'woocommerce_styling' =>
		array(
				// Tooltip title 
				
				'title' => htmlentities( __('WooCommerce Styling', 'woocommerce_views'), ENT_QUOTES ),
				
				// Tooltip content /
				
				'content' => htmlentities( __('Allows you to add a container with classes that help display WooCommerce elements correctly. Normally, you should leave this option selected, unless you are going to add these classes manually.', 'woocommerce_views'), ENT_QUOTES ),
				
				// Bottom message content and link 
				
				'message_for_link' => NULL
		),
		
		/** BATCH PROCESSING OPTIONS FOR UPDATING CALCULATED PRODUCT FIELDS SECTION TEXT */
		
		'batch_processing_options' =>
		array(
				// Tooltip title
				
				'title' => htmlentities( __('Batch processing options', 'woocommerce_views'), ENT_QUOTES ),
				
				// Tooltip content /
				
				'content' => htmlentities( __('Calculated product fields allow you to create parametric searches for WooCommerce products. You can create them manually or automatically via a cron job.', 'woocommerce_views'), ENT_QUOTES ),

				// Bottom message content and link
				
				'message_for_link' => NULL
		),
		
		'top_general_helpbox' =>
		array(
				// Tooltip title
		
				'title' => NULL,
		
				// Tooltip content /
		
				'content' => NULL,
		
				// Bottom message content and link
		
				'message_for_link' => sprintf( __('Visit','woocommerce_views').' '.'%s<strong>'.__('WooCommerce Views documentation','woocommerce_views').'</strong>%s'.' '.__('to learn how to develop your own custom WooCommerce sites with Views.', 'woocommerce_views'), '<a href="' . WCVIEWS_TOPHELP_BOX_LINK . '" target="_blank">', '</a>' ),	
		)
);

//Formatting and Editing Instructions
//https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193242285/comments

add_action( 'init', 'wpv_add_wcviews_usage_instructions' );

function wpv_add_wcviews_usage_instructions() {
	
		// Register the section
		add_filter( 'wpv_filter_formatting_help_layout', 'wpv_register_wcviews_section' );
		add_filter( 'wpv_filter_formatting_help_inline_content_template', 'wpv_register_wcviews_section' );
		add_filter( 'wpv_filter_formatting_help_layouts_content_template_cell', 'wpv_register_wcviews_section' );
		add_filter( 'wpv_filter_formatting_help_combined_output', 'wpv_register_wcviews_section' );
		add_filter( 'wpv_filter_formatting_help_content_template', 'wpv_register_wcviews_section' );
		
		// Register the section content
		add_filter( 'wpv_filter_formatting_instructions_section', 'wpv_wcviews_editing_instructions', 10, 2 );
	
}

function wpv_register_wcviews_section( $sections ) {
	if ( ! in_array( 'woocommerce-views-shortcodes', $sections ) ) {		
		
		$sections[]='woocommerce-views-shortcodes';
	}
	
	if ( ! in_array( 'woocommerce-views-functions', $sections ) ) {
	
		$sections[]='woocommerce-views-functions';
	}
	
	return $sections;
}

function wpv_wcviews_editing_instructions( $return, $section ) {
	if ( 'woocommerce-views-shortcodes' == $section ) {
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-woocommerce-views-shortcodes-section',
				'title' => __( 'WooCommerce Views Shortcodes', 'woocommerce_views' ),
				'content' => '',
				'table' => array(
					//Add to cart button - product listing pages	
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-buy-or-select]</span>',
						'description' => __( "Output 'add to cart' button for the product listing page For variable products the 'select options' button will be displayed.", 'woocommerce_views' )
					),
				    //Product price
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-product-price]</span>',
						'description' => __( "Output the product price. For variable products the 'select options' button will be displayed.", 'woocommerce_views' )
					),
					//Add to cart button - single product page
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-buy-options]</span>',
						'description' => __( "Output 'add to cart' button on product detail pages.", 'woocommerce_views' )
					),
					//Product image
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-product-image]</span>',
						'description' => __( "Displays the product image (featured image), wrapped in a link to the full size image.", 'woocommerce_views' )
					),
					//Add to cart message
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-add-to-cart-message]</span>',
						'description' => __( "Displays a success message when a Product is added to the cart. Or exception messages, for example – if there is insufficient stock.", 'woocommerce_views' )
					),
					//Product tabs - single product page
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-display-tabs]</span>',
						'description' => __( "Displays WooCommerce product tabs. By default this will display product reviews and product attributes.", 'woocommerce_views' )
					),
					//Onsale badge
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-onsale]</span>',
						'description' => __( "This shortcode outputs the default WooCommerce on-sale badge icon which is appended to the WooCommerce product image.", 'woocommerce_views' )
					),	
					//Product Attributes																																										
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-list_attributes]</span>',
						'description' => __( 'This shortcode outputs product attributes in WooCommerce. Set product attributes when editing WooCommerce products in the back-end, then go to Product data -> Attributes. Additionally, attributes can be set in Products -> Attributes.', 'woocommerce_views' )
					),
					//Related Products	
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-related_products]</span>',
						'description' => __( 'This shortcode outputs WooCommerce related products.', 'woocommerce_views' )
					),	
					//Product Rating - single product page
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-single-products-rating]</span>',
						'description' => __( 'This shortcode outputs WooCommerce product ratings to single product pages.', 'woocommerce_views' )
					),
					//Product Rating - product listing pages
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-products-rating-listing]</span>',
						'description' => __( 'This shortcode outputs WooCommerce product ratings to product listings and loops.', 'woocommerce_views' )
					),
					//Product Category Image
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-productcategory-images]</span>',
						'description' => __( 'This shortcode outputs WooCommerce product category images set in the back-end. (Products -> Categories). This has been tested to work in loops outputting product categories.', 'woocommerce_views' )
					),	
					//Product Upsell
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-show-upsell-items]</span>',
						'description' => __( 'This shortcode outputs items for upselling. You can configure items for upselling by following this WooCommerce guide:
http://docs.woothemes.com/document/related-products-up-sells-and-cross-sells/', 'woocommerce_views' )
					),
					//Breadcrumb
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-woo-breadcrumb]</span>',
						'description' => __( 'This shortcode outputs default WooCommerce breadcrumbs to anywhere on the Content Template (as specified by user). This allows the user to customize the location of this breadcrumb on a single product page.', 'woocommerce_views' )
						),																																		
				),
				'content_extra' => ''
			);
	}
	
	if ( 'woocommerce-views-functions' == $section ) {
		$return = array(
				'classname' => 'js-wpv-editor-instructions-for-woocommerce-views-functions-section',
				'title' => __( 'WooCommerce Views Public Functions', 'wpv-views' ),
				'content' => '',
				'table' => array(
						array(
						//woo_product_on_sale()
								'element' => '<span class="wpv-code wpv-code-html">woo_product_on_sale()</span>',
								'description' => __( 'Check if product is on sale.', 'wpv-views' ).'<br />'
								.__('Example usage with wpv-if').':'.'<br />'															
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="woo_product_on_sale() = 1"]
                					This product is on sale
              						[/wpv-if]</span>').'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="woo_product_on_sale() = 0"]
                					This product is NOT on sale
              					[/wpv-if]</span>').'<br />'						
						),
						//woo_product_in_stock()
						array(
								'element' => '<span class="wpv-code wpv-code-html">woo_product_in_stock()</span>',
								'description' => __( 'Check if stock is available.', 'wpv-views' ).'<br />'
								.__('Example usage with wpv-if').':'.'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="woo_product_in_stock() = 1"]Stock is available for this product.[/wpv-if]</span>').'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="woo_product_in_stock() = 0"]Stock is not available for this product.[/wpv-if]</span>').'<br />'
						),
						//wpv_woo_single_products_rating_func()
						array(
								'element' => '<span class="wpv-code wpv-code-html">wpv_woo_single_products_rating_func()</span>',
								'description' => __( 'Check if a rating is available for this product (single product).', 'wpv-views' ).'<br />'
								.__('Example usage with wpv-if').':'.'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="wpv_woo_single_products_rating_func() != \'\'"]A rating is available for this product.[/wpv-if]</span>').'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="wpv_woo_single_products_rating_func() = \'\'"]A rating is not available for this product.[/wpv-if]    </span>').'<br />'
						),
						//wpv_woo_list_attributes_func()
						array(
								'element' => '<span class="wpv-code wpv-code-html">wpv_woo_list_attributes_func()</span>',
								'description' => __( 'Check if this product has attributes set.', 'wpv-views' ).'<br />'
								.__('Example usage with wpv-if').':'.'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="wpv_woo_list_attributes_func() != \'\'"]This product has attributes set.[/wpv-if]</span>').'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="wpv_woo_list_attributes_func() = \'\'"]This product still does not have attributes set.[/wpv-if]</span>').'<br />'
						),
						//wpv_woo_show_upsell_func()
						array(
								'element' => '<span class="wpv-code wpv-code-html">wpv_woo_show_upsell_func()</span>',
								'description' => __( 'Check if this item has an upsell product assigned (single product pages ).', 'wpv-views' ).'<br />'
								.__('Example usage with wpv-if').':'.'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="wpv_woo_show_upsell_func() != \'\'"]This item has an associated upsell product assigned.[/wpv-if]</span>').'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="wpv_woo_show_upsell_func() = \'\'"]This item does not have any associated upsell item.[/wpv-if]</span>').'<br />'
						),
						//wpv_woo_products_rating_on_listing_func()
						array(
								'element' => '<span class="wpv-code wpv-code-html">wpv_woo_products_rating_on_listing_func()</span>',
								'description' => __( 'Check if a rating is available for this product (product listing).', 'wpv-views' ).'<br />'
								.__('Example usage with wpv-if').':'.'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="wpv_woo_products_rating_on_listing_func() != \'\'"]This item has a rating, let\'s show in this product listing.[/wpv-if]</span>').'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="wpv_woo_products_rating_on_listing_func() = \'\'"]Product is not yet rated[/wpv-if]</span>').'<br />'
						),
						//woo_has_product_subcategory()
						array(
								'element' => '<span class="wpv-code wpv-code-html">woo_has_product_subcategory()</span>',
								'description' => __( 'Check if a product category being loaded has a subcategory.', 'wpv-views' ).'<br />'
								.__('Example usage with wpv-if').':'.'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="woo_has_product_subcategory() = 1"]This product category has a subcategory.[/wpv-if]</span>').'<br />'
								.__('<span class="wpv-code wpv-code-shortcode">[wpv-if evaluate="woo_has_product_subcategory() = 0"]This product category has does not have a subcategory.[/wpv-if]</span>').'<br />'
						),																																				
				),
				'content_extra' => ''
		);
	}	
	return $return;
}