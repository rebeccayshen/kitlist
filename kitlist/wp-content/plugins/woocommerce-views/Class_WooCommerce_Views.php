<?php
/**
 * WooCommerce Views Class
 * 
 * The WooCommerce Views Class aims PHP code-free implementation of WooCommerce Plugin with Toolset.
 *
 * @class 		Class_WooCommerce_Views
 * @version		2.5.4
 * @package		WooCommerce-Views/Classes
 * @category	Class
 * @author 		OnTheGoSystems
 */ 
class Class_WooCommerce_Views {

	/** @public array WooCommerce Views functions for [wpv-if] conditional evaluations */
	public $wcviews_functions = array();
	
	/** @public array WooCommerce Views shortcodes associated post types */
	public $wcviews_associated_posttypes =array();
	
	/**
	 * Hook in methods
	 */
	
	public function __construct() {
			
		add_action('plugins_loaded', array(&$this,'wcviews_init'),2);
				
		//Aux
		define('WPV_WOOCOMERCE_VIEWS_SHORTCODE', 'wpv-wooaddcart');
		define('WPV_WOOCOMERCEBOX_VIEWS_SHORTCODE', 'wpv-wooaddcartbox');
		add_action( 'admin_menu', array(&$this,'woocommerce_views_add_this_menupage'),50);
		add_action('system_cron_execution_hook',array(&$this,'ajax_process_wc_views_batchprocessing'));		
		add_action('wp_enqueue_scripts', array(&$this,'woocommerce_views_scripts_method'));		
		add_action('wp_ajax_wc_views_ajax_response_admin',array(&$this,'ajax_process_wc_views_batchprocessing'));		
				
		//Ajax hooks for Shortcode GUI
		add_action('wp_ajax_wcviewsgui_wpv_woo_buy_or_select', 'wcviewsgui_wpv_woo_buy_or_select_func');		
		add_action('wp_ajax_wcviewsgui_wpv_woo_buy_options', 'wcviewsgui_wpv_woo_buy_options_func');		
		add_action('wp_ajax_wcviewsgui_wpv_woo_product_image','wcviewsgui_wpv_woo_product_image_func');
		add_action('wp_ajax_wcviewsgui_wpv_woo_productcategory_images','wcviewsgui_wpv_woo_productcategory_images_func');
		
		add_action('admin_enqueue_scripts', array(&$this,'woocommerce_views_scripts_method_backend'));
		add_action('init',array(&$this,'prefix_setup_schedule'));			
		add_action('admin_init',array(&$this,'reset_all_wc_admin_screen'));
		
		//Old shortcodes-all deprecated still added for outputting deprecation notices		
		add_shortcode('wpv-wooaddcart', array(&$this,'wpv_woo_add_to_cart'));
		add_shortcode('wpv-wooaddcartbox', array(&$this,'wpv_woo_add_to_cart_box'));
		add_shortcode('wpv-wooremovecart', array(&$this,'wpv_woo_remove_from_cart'));
		add_shortcode('wpv-woo-carturl', array(&$this,'wpv_woo_cart_url'));
						
		//New shortcodes		
		add_shortcode('wpv-woo-breadcrumb',array(&$this,'wpv_woo_breadcrumb_func'));
		add_shortcode('wpv-woo-show-upsell-items',array(&$this,'wpv_woo_show_upsell_func'));
		add_shortcode('wpv-woo-productcategory-images',array(&$this,'wpv_woo_productcategory_images_func'));
		add_shortcode('wpv-woo-products-rating-listing',array(&$this,'wpv_woo_products_rating_on_listing_func'));
		add_shortcode('wpv-woo-single-products-rating',array(&$this,'wpv_woo_single_products_rating_func'));
		add_shortcode('wpv-woo-related_products',array(&$this,'wpv_woo_related_products_func'));
		add_shortcode('wpv-woo-list_attributes',array(&$this,'wpv_woo_list_attributes_func'));
		add_shortcode('wpv-woo-buy-or-select', array(&$this,'wpv_woo_buy_or_select_func'));		
		add_shortcode('wpv-woo-product-price', array(&$this,'wpv_woo_product_price_func'));		
		add_shortcode('wpv-woo-product-image', array(&$this,'wpv_woo_product_image_func'));
		add_shortcode('wpv-woo-buy-options', array(&$this,'wpv_woo_buy_options_func'));
		add_shortcode('wpv-add-to-cart-message', array(&$this,'wpv_show_add_cart_success_func'));
		add_shortcode('wpv-woo-display-tabs',array(&$this,'wpv_woo_display_tabs_func'));
		add_shortcode('wpv-woo-onsale',array(&$this,'wpv_woo_onsale_func'));	
		add_shortcode('wpv-woo-product-meta', array(&$this,'wpv_woo_product_meta_func'));
		add_shortcode('wpv-woo-cart-count', array(&$this,'wpv_woo_cart_count_func'));
		add_shortcode('wpv-woo-reviews',array(&$this,'wpv_woo_show_displayreviews_func'));
		
		//By default, don't include gallery images in image shortcode at listings
		add_filter('woocommerce_product_gallery_attachment_ids',array(&$this,'remove_gallery_on_main_image_at_listings'),20,2);
		
		//Template loading		
		
		/** We give a priority of 50 since some theme uses template_redirect to load their own template files.
		 *  We want to load them first before doing the final WC Views template redirect.
		 */
		
		add_action( 'template_redirect', array(&$this,'woocommerce_views_activate_template_redirect' ),50);
		add_action( 'template_redirect', array(&$this,'woocommerce_views_activate_archivetemplate_redirect' ),50);
		add_action( 'switch_theme', array(&$this,'wc_views_reset_wc_default_after_theme_switching' ));
		add_action( 'switch_theme', array(&$this,'wc_views_reset_wc_defaultarchive_after_theme_switching' ));
		add_action( 'after_switch_theme', array(&$this,'wc_views_after_theme_switched' ));
		add_action( 'init',array($this,'wcviews_review_templates_handler'));
		
		//Save post meta values when saving the products or updating		
		add_action('save_post',array(&$this,'compute_postmeta_of_products_woocommerce_views'));
		
		//WP-Views plugin hooks	
		//WooCommerce Views 2.4 category image shortcode on category View
		add_filter('editor_addon_menus_wpv-views', array(&$this,'wpv_woo_add_shortcode_in_views_popup_cat'),50);
		add_filter('editor_addon_menus_wpv-views', array(&$this,'wpv_woo_add_shortcode_in_views_popup'));
		add_filter('toolset_editor_addon_post_fields_list',array(&$this,'wpv_woo_add_shortcode_in_views_layout_wizard'));
		
		//Register the computed values as Types fields		
		add_action('wp_loaded',array(&$this,'wpv_register_typesfields_func'));
		
		//CT template override on product pages when using default WC template
		add_action('wp',array(&$this,'wc_views_override_template_on_loaded'));
		
		//WC wrapper hook
		add_action('wp',array(&$this,'wc_views_woocommerce_wrapper_override_loaded'));
		
		//Make sure single-product.php is fully under WooCommerce control		
		add_action( 'init', array( $this, 'wc_views_dedicated_template_loader' ),50);
		add_filter('body_class',array( $this, 'wc_views_add_woocommerce_to_body_class'),9999);
		
		//Make sure archive-product.php is fully under WooCommerce control
		add_action( 'init', array( $this, 'wc_views_dedicated_archivetemplate_loader' ),50);
		
		//WooCommerce Views breadcrumb handler
		add_action('wp',array(&$this,'wc_views_remove_breadcrumb_from_template'));
		
		//Add Layouts rendering support to products template
		add_action('wp_loaded',array(&$this,'wc_views_add_render_view_template'));
		add_action('admin_enqueue_scripts', array($this, 'remove_template_warning_if_layoutset'),20);

		//Put the WooCommerce Views submenu just above Views settings		
		add_filter( 'custom_menu_order', array(&$this,'assign_proper_submenu_order_wcviews'),30 );

		//Layouts support	
		add_filter('get_layout_id_for_render',array(&$this,'use_layouts_shop_if_assigned'), 20,2);
		add_action('wp',array(&$this,'wc_views_check_if_anyproductarchive_has_layout'));
		
		//Fix WooCommerce 2.3.4 - Menu voices take a different name when using with Layouts plugin
		add_action('wp',array($this, 'wcviews_remove_filter_for_wc_endpoint_title'),777);
		
		//Set values for WooCommerce Views functions for conditional evaluation		
		$this->wcviews_functions =		
		array(
				'woo_product_on_sale',
				'woo_product_in_stock',				
				'wpv_woo_single_products_rating_func',
				'wpv_woo_list_attributes_func',
				'wpv_woo_show_upsell_func',
				'wpv_woo_products_rating_on_listing_func',
				'woo_has_product_subcategory'
			  );
		
		//WooCommerce Views shortcodes associated post types
		//Shortcodes can only be added on the edit section of these post types
		//To prevent misuse of these shortcodes in other places.
		
		$this->wcviews_associated_posttypes =
		array(
				'dd_layouts',
				'view',
				'view-template'
		);	

		// Ensure cart contents are updated when products are added to the cart via AJAX (place the following in functions.php)
		// This hooked is used by WooCommerce Views cart count shortcode.
		add_filter( 'woocommerce_add_to_cart_fragments', array(&$this,'woocommerce_views_add_to_cart_fragment' ),10,1);
		
		//Auto-JS handler for WooCommerce Views onsale shortcode in Views AJAX pagination
		add_filter( 'wpv_view_settings', array($this,'wcviews_onsale_pagination_callback_func'), 99, 2 );
		
		//Catch situations where are doing a Views loop
		add_action( 'wpv-before-display-post', array($this,'wcviews_before_display_post'), 99, 2 );
		add_action( 'wpv-after-display-post', array($this,'wcviews_after_display_post'), 99, 2 );
				
	}

	/**
	 * Main init.
	 *
	 * @access public
	 * @return void
	 */
	
	public function wcviews_init(){
		
	    add_action('wp_loaded',array(&$this,'run_wp_loaded_check_required_plugins'));
		    	
		if(get_option('dismiss_wcviews_notice') == 'no' || !get_option('dismiss_wcviews_notice')){
			add_action('admin_notices', array(&$this,'wcviews_help_admin_notice'));
		}
			
		$using_default_wc_template=$this->wc_views_check_if_using_woocommerce_default_template();
		
		if (!($using_default_wc_template)) {
			//If is not using WooCommerce Default Templates, it assumes the user wants to override the default templates.
			//Therefore for this to work, the user should also have Content Template assigned to products.
			 
			//Let's checked..
			$has_content_template_set=$this->check_if_content_template_has_assigned_to_products_wcviews();
			if (!($has_content_template_set)) {
				//Oops, none, let's show a notice to the user.			
				add_action('admin_notices', array(&$this,'no_content_template_set_error'));
			}
		}
		
		//add_filter('wpv_add_media_buttons', 'add_media_button');	
		add_action('admin_enqueue_scripts', array(&$this,'additional_css_js'));
		
		//Remove this hook so users can customize add to cart messages in main shop pages, etc.
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_show_messages', 10 );
		
		//Hook on reducing stock level in WooCommerce
		add_action('woocommerce_reduce_order_stock',array(&$this,'ajax_process_wc_views_batchprocessing'),10,1);
	
		
		/** Add default WooCommerce Views conditional functions to Views */
	    $this->wc_views_add_to_views_conditional_evaluation();

	}

	/**
	 * Layouts uses Views function 'render_view_template' to render Content Template and not the native 'the_content()'
	 * Let's add this automatically to Theme support for Content Templates
	 * So any hooks and filters will be executed.
	 *
	 * @access public
	 * @return void
	 */
	
	public function wc_views_add_render_view_template() {		
		
		if( defined('WPDDL_VERSION') ) {
			
			//Layouts plugin activated
			//Let's check first if all dependencies are set
			$missing_required_plugin=$this->check_missing_plugins_for_woocommerce_views();
		
			//Define default functions
			$wcv_views_default_functions='render_view_template';
		
			if (empty($missing_required_plugin)) {
				
				//All required dependencies are set
				//Get Views setting
				global $wpddlayout;
				
				if (is_object($wpddlayout)) {
					
					//Access Layouts post type object
					$layout_posttype_object=$wpddlayout->post_types_manager;
					
					if (method_exists($layout_posttype_object,'get_layout_to_type_object')) {					
					
						//Check if product post type has been assigned with Layouts					
						$result=$layout_posttype_object->get_layout_to_type_object( 'product' );
					
						if ($result) {
							
							//Product has now layouts assigned
							//Get Views setting	and let's ensure that the render_view_template is added to theme functions					
							$view_settings=get_option('wpv_options');
							
							//Add render_view_template to theme function

							if (isset($view_settings['wpv-theme-function'])) {
								//Set, check value
								$val=$view_settings['wpv-theme-function'];
								if ('render_view_template' != $val) {
									
									//Not updated
									$view_settings['wpv-theme-function']=$wcv_views_default_functions;
									
									//Update back
									update_option( 'wpv_options', $view_settings );									
								}
							} else {
								//Not set, set
								$view_settings['wpv-theme-function']=$wcv_views_default_functions;
								
								//Update back
								update_option( 'wpv_options', $view_settings );
							}						
						}
					}
					
				}

			}		
		} else {
			
			//Layouts plugin not activated
            //Get Views settings
			$view_settings=get_option('wpv_options');
				
			//Remove render_view_template to theme function
			
			if (isset($view_settings['wpv-theme-function'])) {
				//Set, check value
				$val=$view_settings['wpv-theme-function'];
				if ('render_view_template' == $val) {
					
					//Let's roll back
					$view_settings['wpv-theme-function']='';
													
						//Update back
					update_option( 'wpv_options', $view_settings );
			    }
			    
			} 		
		}
		
	}
	/**
	 * Removes default breadcrumb from WooCommerce Template hooks
	 * So it can be overriden with WooCommerce Views breadcrumb shortcode.
	 * Hook is removed ONLY if using non-default WooCommerce Templates.
	 * This will customize the breadcrumbs on single product pages only.
	 * Breadcrumbs on WooCommerce shop listing for example is not affected.
	 * @access public
	 * @return void
	 */	
	
	public function wc_views_remove_breadcrumb_from_template() {
		
		$using_default_wc_template=$this->wc_views_check_if_using_woocommerce_default_template();
		
		global $woocommerce;
		if (is_object($woocommerce)) {
			if (is_product()) {
				//Remove default WooCommerce breadcrumb so it will replaceable with WooCommerce Views breadcrumb shortcode
				//Remove only if not using default WooCommerce Templates
				if (!($using_default_wc_template)) {
					remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );
				}
			}
		}		
		
	}
	/**
	 * Adds default WC Views functions to be used for WP Views plugin wpv-if statements:
	 * woo_product_on_sale() 
	 * woo_product_in_stock()
	 * wpv_woo_single_products_rating_func()
	 * This will automatically these functions to Views -> Settings -> Functions inside conditional evaluations
	 *
	 * @access public
	 * @return void
	 */	
	
	public function wc_views_add_to_views_conditional_evaluation() {
		
		//Let's check first if all dependencies are set
		$missing_required_plugin=$this->check_missing_plugins_for_woocommerce_views();
		
		//Define default functions
		$wcv_views_default_functions=$this->wcviews_functions;
		
		if (empty($missing_required_plugin)) {
			//All required dependencies are set
			//Get Views setting
			$views_setting= get_option('wpv_options');
			if ($views_setting) {
			   //Views settings exists
			   //Check if conditional functions are set by user previously
			   if (isset($views_setting['wpv_custom_conditional_functions'])) {
			   	  
			   	  //User has already set this, retrieved existing setting
			   	  $existing_conditional_functions_setting=$views_setting['wpv_custom_conditional_functions'];
	
			   	  if (is_array($existing_conditional_functions_setting)) {
			   	  	//$existing_conditional_functions_setting should be an array
			   	  	//Check if WCV Views functions are already there
			   	  	$all_set=$this->wc_views_all_conditional_functions_set($wcv_views_default_functions,$existing_conditional_functions_setting);
			   	  	
			   	  	if ($all_set === TRUE) {
			   	  		//Already there, do nothing...
			   	  	} else {
			   	  		//Not yet, let's add..loop through the default functions.
			   	  		foreach ($all_set as $k=>$v) {
			   	  			$views_setting['wpv_custom_conditional_functions'][]=$v;
			   	  		}
			   	  				   	  		
			   	  		//Let's update back
			   	  		update_option( 'wpv_options',$views_setting );
			   	  	} 	
			   	  	
			   	  }
			   } else {
			   	 	 //Not yet set
			   		foreach ($wcv_views_default_functions as $k=>$v) {
			   			$views_setting['wpv_custom_conditional_functions'][]=$v;
			   		}
	
			   		//Let's update back
			   		update_option( 'wpv_options',$views_setting );
			   }
			}		
		}
	}

	/**
	 * Aux method to check if all functions for conditional evaluations are set
	 *
	 * @access public
	 * @param  array $wcv_views_default_functions
	 * @param  array $existing_conditional_functions_setting
	 * @return mixed
	 */
	
	public function wc_views_all_conditional_functions_set($wcv_views_default_functions,$existing_conditional_functions_setting) {

		//Let's looped through the $wcv_views_default_functions array and check if they are all in $existing_conditional_functions_setting
		$not_set=array();
		foreach ($wcv_views_default_functions as $k=>$v) {
			
			if (!(in_array($v,$existing_conditional_functions_setting))) {
				//Not in array
				$not_set[]=$v;
			}			
		}
		
		if (empty($not_set)) {
			//All in array, return TRUE
			return TRUE;
		} else {
			//Not all is there, return FALSE
			return $not_set;
		}
		
	}
	
	/**
	 * Adds default WooCommerce div classes.
	 * This is configurable in 
	 * Woocommerce Views Settings -> WooCommerce Styling -> Add a container DIV around the post body for WooCommerce styling. 
	 *
	 * @access public
	 * @return void
	 */	
	
	public function wc_views_woocommerce_wrapper_override_loaded() {
		
		global $woocommerce;
		
		if (is_object($woocommerce)) {
			
			//WooCommerce plugin activated
			$is_product=is_product();
			$settings_wrapper_woocommerce= get_option('woocommerce_views_wrap_the_content');
			
			if (!($settings_wrapper_woocommerce)) {
			
				//Not yet set, use "yes" as default
				$settings_wrapper_woocommerce='yes';
			
			}			
			
			//Fall back to Content Template if Layouts plugin is activated but no Layouts has been assigned to WooCommerce Products
			add_filter('get_layout_content_for_render', array(&$this,'wc_views_fallback_to_ct'), 10,4 );

			if (($settings_wrapper_woocommerce=='yes') && ($is_product)) {
				
				/** Yes to wrapping and this is a product page */
				
				//User wants to wrap the DIV with WooCommerce classes, add the filter
				add_filter('wpv_filter_content_template_output', array(&$this,'wc_views_prefix_add_wrapper'), 10, 4);

				//Layouts support
				add_filter('get_layout_content_for_render', array(&$this,'wc_views_prefix_add_wrapper_layouts'), 20,4 );
			}
		}	
	}
	
	/**
	 * Filter for adding WooCommerce Views classes wrapping around a div container outputted by Layouts
	 *
	 * @access public
	 * @return string
	 */
	
	public function wc_views_prefix_add_wrapper_layouts( $content, $object_passed, $layout, $args ) {

		global $wpddlayout,$post;
		
		if (is_object($wpddlayout)) {
				
			//Access Layouts post type object
			$layout_posttype_object=$wpddlayout->post_types_manager;
				
			if (method_exists($layout_posttype_object,'get_layout_to_type_object')) {
					
				//Check if product post type has been assigned with Layouts
				$result=$layout_posttype_object->get_layout_to_type_object( 'product' );
					
				if ($result) {
					//WooCommmerce product post type has assigned Layouts
					
					if (isset($post->ID)) {
						$post_id=$post->ID;
						$post_classes = get_post_class( 'clearfix', $post_id );
						global $post_classes_wc_added;
						if (!($post_classes_wc_added)) {
							$post_classes_wc_added=TRUE;
							$content = '<div class="' . implode( ' ', $post_classes ) . '">'. $content . '</div>';
						}						
					}					
					
				}
			}
		}

		return $content;
	
	}
	
	/**
	 * Method for falling back to use Content Templates if Layouts plugin is activated but no Layouts has been assigned to products
	 * @access public
	 * @return string
	 */
	
	public function wc_views_fallback_to_ct( $content, $object_passed, $layout, $args ) {
	
		global $wpddlayout,$post;
		$is_product=is_product();
		$settings_wrapper_woocommerce= get_option('woocommerce_views_wrap_the_content');
		
		if ((is_object($wpddlayout)) && ($is_product)) {
			
			//Layouts plugin activated and this is product
			//Access Layouts post type object
			$layout_posttype_object=$wpddlayout->post_types_manager;
	
			if (method_exists($layout_posttype_object,'get_layout_to_type_object')) {
					
				//Check if product post type has been assigned with Layouts
				$result=$layout_posttype_object->get_layout_to_type_object( 'product' );
					
				if ($result) {
					//Layouts assigned, do nothing...						
				} else {
					//Products has not been assigned with Layouts
					//Let's checked if a Content Template has been assigned instead.
					$has_ct=$this->check_if_content_template_has_assigned_to_products_wcviews();
					if ($has_ct) {
						//Has content template assigned						
						$content='';						
						$content_template_options=get_option('wpv_options');
						
						if (isset($content_template_options)) {
							if (!(empty($content_template_options))) {
								if (isset($content_template_options['views_template_for_product'])) {
									//Product content template is set
									//Check if its not null
									$null_check=$content_template_options['views_template_for_product'];
									$null_check=intval($null_check);
									if ($null_check > 0) {
										//Sensible id for CT, use it
										if (is_object($post)) {
											$content = render_view_template($null_check, $post );
											
											if ('yes' == $settings_wrapper_woocommerce) {
												//WooCommerce Classes wrapping
												if (isset($post->ID)) {
													$post_id=$post->ID;
													$post_classes = get_post_class( 'clearfix', $post_id );
													global $post_classes_wc_added;
													if (!($post_classes_wc_added)) {
														$post_classes_wc_added=TRUE;
														$content = '<div class="' . implode( ' ', $post_classes ) . '">'. $content . '</div>';
													}
												}																
											}

										}
									}
								}
							}
						}					
					}
				}
			}
		}
	
		return $content;
	
	}	
	/**
	 * Force Template override.
	 * If using WooCommerce default templates on a product page,
	 * no Content Template should be applied to posts if using WooCommerce default templates.
	 *
	 * @access public
	 * @return void
	 */
	
	public function wc_views_override_template_on_loaded() {
		
		global $woocommerce;
		if (is_object($woocommerce)) {
			//WooCommerce plugin activated
			$is_product=is_product();
			$check_if_currently_using_wc_defaults=$this->wc_views_check_if_using_woocommerce_default_template();
			if (($check_if_currently_using_wc_defaults) && ($is_product)) {
				//No content template should be applied to posts if using WooCommerce default templates
				add_filter('wpv_filter_force_template', array(&$this,'wc_views_override_any_content_templates_default_wc'), 10, 3);
			}	
		}
	}
	
	/**
	 * Helper method: Make sure single-product.php is fully under WooCommerce control.
	 * Adds a filter hooked to template_include
	 *
	 * @access public
	 * @return void
	 */
		
	public function wc_views_dedicated_template_loader() {
		
		add_filter( 'template_include',array( $this, 'wc_views_template_loader' ) );
			
	}
	
	/**
	 * Helper method: Adds WooCommerce class to body class.
	 * Hooked to body_class filter
	 * @access public
	 * @param  array $classes
	 * @return array
	 */	
	
	public function wc_views_add_woocommerce_to_body_class($classes) {
		
	    //Check if WooCommerce is activated
		if (class_exists('woocommerce')){
	
			//Check woocommerce class exist
			if (!(in_array('woocommerce',$classes))) {
					
				//Does not exist
				$classes[] = 'woocommerce';
								
			}		
				
		}
		return $classes;	
		
	}
	
	/**
	 * Helper method: Filter function for $template.
	 * Ensures it returns single-product.php from the WooCommerce plugin templates.
	 * Hooked to template_include filter.
	 * @access public
	 * @param  string $template
	 * @return string
	 */	
	
	public function wc_views_template_loader($template) {
		
		global $woocommerce;	
		
		$is_single= is_single();
		$get_post_type_var=get_post_type();
		
		if ( is_single() && get_post_type() == 'product' ) {	
			
			$file='single-product.php';
			$template = $woocommerce->plugin_path() . '/templates/' . $file;
		
		}
		
		return $template;
	}
	
	/**
	 * Check required plugins for WooCommerce Views.
	 * Hooked to wp_loaded.
	 * @access public
	 * @return void
	 */
		
	public function run_wp_loaded_check_required_plugins() {
		
		$this->run_woocommerce_views_required_plugins();
		
	}
	
	/**
	 * Check for missing plugins when WooCommerce Views is activated.
	 * @access public
	 * @return array
	 */
		
	public function check_missing_plugins_for_woocommerce_views() {
		
		$missing_required_plugin=array();
		
		//Check plugin requirements
		if (!class_exists('woocommerce')){
		
			//WooCommerce plugin is not activated
			$missing_required_plugin[]='woocommerce';
		}
		if (!(defined('WPV_VERSION'))){
		
			//Views plugin is not activated
			$missing_required_plugin[]='views';
		}
		if (!(defined('WPCF_VERSION'))){
		
			//Types plugin is not activated
			$missing_required_plugin[]='types';
		}
	
		return $missing_required_plugin;
		
	}
	
	/**
	 * Output missing plugins notices.
	 * Hooked to admin_notices.
	 * @access public
	 * @return void
	 */	
	
	public function missing_plugins_wcviews_check() {
		
	 global $custom_missing_required_plugin;
		
	 ?>
<div class="message wcviews_plugin_error error">
	<p><?php _e('The following plugins are required for WooCommerce Views to run properly:','woocommerce_views');?></p>
	<ol>
		<?php 
			  foreach ($custom_missing_required_plugin as $k=>$v) {
		?>
		<li>
		<?php
		if ($v=='views') {
		?>
		<a target="_blank"
			href="http://wp-types.com/home/views-create-elegant-displays-for-your-content/">Views</a>
	     <?php
	    } elseif ($v=='types') {
	    ?>
	   <a target="_blank" href="http://wordpress.org/plugins/types/">Types</a>
	    <?php
		} elseif ($v=='woocommerce') {
		?>
		<a target="_blank" href="http://wordpress.org/plugins/woocommerce/">WooCommerce</a>
		 <?php
		}
		?>
		</li>
		<?php
		 }
		 ?>
		 </ol>
</div>
<?php						
				
	}

	/**
	 * Output missing WooCommerce pages.
	 * Hooked to admin_notices
	 * @access public
	 * @return void
	 */
	
	public function missing_woocommerce_pages_wc_views() {
	
		?>
<div class="message wcviews_plugin_error error">
	<p><?php _e('Please install WooCommerce Pages before you can fully start with WooCommerce Views.','woocommerce_views');?></p>
</div>
<?php						
				
	}
	
	/**
	 * Check if a Content Template is assigned to WooCommerce products.
	 * Returns TRUE if as Content Template has been assigned, otherwise FALSE.
	 * @access public
	 * @return boolean
	 */	
	
	public function check_if_content_template_has_assigned_to_products_wcviews() {
		
		//Check if a content template has been assigned to a product	
		$content_template_options=get_option('wpv_options');	
		
		if (isset($content_template_options)) {
			if (!(empty($content_template_options))) {
				if (isset($content_template_options['views_template_for_product'])) {
					//Product content template is set
					//Check if its not null
					$null_check=$content_template_options['views_template_for_product'];
					$null_check=intval($null_check);
					if ($null_check > 0) {
						return TRUE;
					} else {
					   //Template exist but not assigned
					   return FALSE;	
					}
				} else {
					
					return FALSE;
				}
		
		
			} else {
				
			   return FALSE;	
			}
		
		} else {
	
		return FALSE;
			
		}
	}
	
	/**
	 * Check if a Views WP Archive is assigned to WooCommerce products archive loop.
	 * Returns TRUE if an WP archive has been assigned, otherwise FALSE.
	 * @access public
	 * @return boolean
	 */
		
	public function check_if_wp_archive_has_already_been_assigned_wc_views() {
		
		$content_template_options = get_option('wpv_options');
		if (isset($content_template_options)) {
			if (!(empty($content_template_options))) {
				if (isset($content_template_options['view_cpt_product'])) {
					//Archive for shop page already defined
					return TRUE;
				} else {
		
					return FALSE;
				}
		
		
			} else {
					
				return FALSE;
			}
		
		} else {
		
			return FALSE;
		
		}	
		
	}
	
	/**
	 * Run plugin dependency checks as required by WooCommerce Views.
	 * Returns admin notices if missing plugins exists.
	 * @access public
	 * @return mixed
	 */	
	
	public function run_woocommerce_views_required_plugins() {
		global $custom_missing_required_plugin;
	    $custom_missing_required_plugin=$this->check_missing_plugins_for_woocommerce_views();
		
	    //Check if WooCommerce pages are successfully installed
	    $wc_needs_pages=get_option('_wc_needs_pages');    
	    
		//Check if there is a missing plugin required
		if (!(empty($custom_missing_required_plugin))) {
		
			//Some required plugin is missing, pass
			add_action('admin_notices',array(&$this,'missing_plugins_wcviews_check'));
	
			return false;
	    } elseif ((empty($custom_missing_required_plugin)) && ($wc_needs_pages)) {
	        //Plugins are secured, but WooCommerce pages are not installed
	         
	    	add_action('admin_notices',array(&$this,'missing_woocommerce_pages_wc_views'));
	    	return false;
	    } 
	}
	
	/**
	 * Enqueue script on WordPress front end.
	 * This method includes enqueing needed JS and CSS.
	 * Hooked to wp_enqueue_scripts
	 * @access public
	 * @return void
	 */	
	
	public function woocommerce_views_scripts_method() {
	    global $post,$woocommerce;
	    if (is_object($woocommerce)) {
	    	//WooCommerce plugin activated
	    	$lightbox_en_woocommerce= get_option( 'woocommerce_enable_lightbox' ) == 'yes' ? true : false;
	    	$suffix_woocommerce	= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	    	$woocommerce_plugin_url=$woocommerce->plugin_url();
	    	$woocommerce_version=$woocommerce->version;
	    
	    	//Enqueue prettyPhoto   
	    	if ($lightbox_en_woocommerce)  {  
	    		wp_enqueue_script( 'prettyPhoto', $woocommerce_plugin_url . '/assets/js/prettyPhoto/jquery.prettyPhoto' . $suffix_woocommerce . '.js', array( 'jquery' ), '3.1.5', true );
	    		wp_enqueue_script( 'prettyPhoto-init', $woocommerce_plugin_url . '/assets/js/prettyPhoto/jquery.prettyPhoto.init' . $suffix_woocommerce . '.js', array( 'jquery' ), $woocommerce->version, true );
	    		wp_enqueue_style( 'woocommerce_prettyPhoto_css', $woocommerce_plugin_url . '/assets/css/prettyPhoto.css' );   
	    	}
		}
	    //Enqueue special onsale badge CSS and JS, load only if woocommerce plugin is set
	    if ((is_object($woocommerce)) && (defined('WC_VIEWS_VERSION'))) {
	    	//WooCommerce plugin and WooCommerce Views activated
	    	$wc_views_version=WC_VIEWS_VERSION;
	    	if (!(empty($wc_views_version))) { 
	    		
	    		/** Some themes de-queue default WooCommerce CSS and loads their own WooCommerce CSS.
	    		Let's give them a filter so they can set their own WooCommerce CSS as the correct dependency instead of Default WC plugin CSS. */
	    		
	    		$default_css=array('woocommerce-general');
	    		$woocommerce_css_loaded=apply_filters('wcviews_woocommmerce_css_override',$default_css);   		
	    		wp_enqueue_style('woocommerce_views_onsale_badge', plugins_url('res/css/wcviews-onsalebadge.css',__FILE__),$woocommerce_css_loaded,$wc_views_version);  
	    		wp_enqueue_script('woocommerce_views_onsale_badge_js', plugins_url('res/js/wcviews-onsalebadge.js',__FILE__),array('jquery'),$wc_views_version);
	    	}
	    }
	}
	
	/**
	 * Enqueue script on WordPress backend.
	 * This method includes enqueing needed JS and CSS.
	 * Hooked to admin_enqueue_scripts.
	 * @access public
	 * @return void
	 */
	
	public function woocommerce_views_scripts_method_backend() {
		
		global $woocommerce;
		
		$screen_output_wc_views= get_current_screen();
		$screen_output_id= $screen_output_wc_views->id;
		
		//Get WooCommerce activated template path ->Default with backward compatibility
		$single_product_wc_template_path='';
		$archive_product_wc_template_path='';
		if (is_object($woocommerce)){
			
			if (function_exists('WC')) {
				
				if (method_exists('woocommerce','plugin_path')) {
					$wc_plugin_path=WC()->plugin_path();
					$single_product_wc_template_path = WC()->plugin_path() . '/templates/single-product.php';
				} else {
					
					$single_product_wc_template_path=$woocommerce->plugin_path() . '/templates/single-product.php';
				}
				
			} else {				
				$single_product_wc_template_path=$woocommerce->plugin_path() . '/templates/single-product.php';
			}
			
			if (function_exists('WC')) {
			
				if (method_exists('woocommerce','plugin_path')) {
					$wc_plugin_path=WC()->plugin_path();
					$archive_product_wc_template_path = WC()->plugin_path() . '/templates/archive-product.php';
				} else {
					$archive_product_wc_template_path=$woocommerce->plugin_path() . '/templates/archive-product.php';
				}
			} else {
				$archive_product_wc_template_path=$woocommerce->plugin_path() . '/templates/archive-product.php';
			}
		}

		//Show path and hide path translatable text
		$show_path=__('Show template','woocommerce_views');
		$hide_path=__('Hide template','woocommerce_views');
		
		//Used for wizard, check if custom fields updating are done
		$check_if_done_cf_updating_wcviews=get_option('woocommerce_last_run_update');
		if ($check_if_done_cf_updating_wcviews) {
	       $cf_field_status_wizard="true";       
	    } else {
		   $cf_field_status_wizard="false";
	    }
	    
	    $admin_url_wcviews=admin_url().'admin.php?page=wpv_wc_views';
	    
	    /*Handle for enabling and disabling of next button in wizard*/
	    //Step1. Saving PHP templates
	    $check_if_template_already_defined_wizard=get_option('woocommerce_views_theme_template_file');
	    if ($check_if_template_already_defined_wizard) {
	       $localize_php_template_already_defined="true";
	    } else {
		   $localize_php_template_already_defined="false";
	    } 
	    
	    /*Handle for content templates next button enabling in wizard*/
	    
	    $check_if_content_template_assigned_wizard=$this->check_if_content_template_has_assigned_to_products_wcviews();
	    if ($check_if_content_template_assigned_wizard) {
	     	$localize_contenttemplate_already_defined="true";
	    } else {
	     	$localize_contenttemplate_already_defined="false";
	    }
	   
	   /*Handle for WP archive next button enabling in wizard*/
	    $check_if_wp_archive_has_already_been_assigned_wc_views_wizard=$this->check_if_wp_archive_has_already_been_assigned_wc_views();
	    if ($check_if_wp_archive_has_already_been_assigned_wc_views_wizard) {
	    	$localize_wparchive_already_defined="true";
	    } else {
	    	$localize_wparchive_already_defined="false";
	    } 
	      
		if  ('views_page_wpv_wc_views' == $screen_output_id) {
			//Enqueue only on WC Views admin screen	
			wp_enqueue_script('woocommerce_views_custom_script_backend', plugins_url( 'res/js/woocommerce_custom_js_backend.js', __FILE__ ), array( 'jquery' ));
			
			wp_localize_script('woocommerce_views_custom_script_backend', 'the_ajax_script_wc_views',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'wc_views_ajax_response_admin_nonce'=> wp_create_nonce('wc_views_ajax_response_admin'),
				'wc_views_ajax_response_modulesdownload_nonce'=> wp_create_nonce('wc_views_ajax_response_modulesdownload'),
				'wc_views_ajax_response_thumbnail_overlay_nonce'=> wp_create_nonce('wc_views_ajax_response_thumbnail_overlay'),
				'wc_views_ajax_response_content_template_post_updating_nonce'=> wp_create_nonce('wc_views_ajax_response_content_template_post_updating'),
				'wc_views_ajax_ajax_loader_gif' =>plugins_url( 'res/img/ajax-loader.gif', __FILE__),
				'wc_views_last_run_translatable_text' => __('Calculated Product fields were last updated: ','woocommerce_views'),
				'wc_views_wizard_php_template_already_defined' => $localize_php_template_already_defined,
				'wc_views_wizard_content_template_already_defined' => $localize_contenttemplate_already_defined,
				'wc_views_wizard_wp_archive_already_defined' => $localize_wparchive_already_defined,
				'wc_views_cf_fields_update_check_wizard' =>$cf_field_status_wizard,
				'wc_views_admin_screen_page_url' => $admin_url_wcviews,
				'wc_views_next_button_text_translatable' =>__('Next','woocommerce_views'),
				'wc_views_finish_wizard_text_translatable'=>__('Skip the setup wizard','woocommerce_views'),
				'wc_views_wc_default_single_product_template' =>$single_product_wc_template_path,
				'wc_views_wc_default_archive_product_template' =>$archive_product_wc_template_path,
				'wc_views_show_path_text' =>$show_path,
				'wc_views_hide_path_text' =>$hide_path
				 )
			);

			//Load common graphics (as of version 2.5.2)
			wp_register_style( 'wcviews-common-utility', plugins_url( 'toolset-common-utility/css/notifications.css',__FILE__), array('wcviews-style'), WC_VIEWS_VERSION );
			wp_enqueue_style('wcviews-common-utility');
		
		}
		
		//Juan: Check the Views version so we load the dialogs.css and utils.js script files that were moved in 1.3.1 -> 1.4 release and will not be loaded by default in post.php and post-new.php pages
		$screen_output_base = $screen_output_wc_views->base;
	        if( defined( 'WPV_VERSION' ) && defined( 'WPV_URL' ) && $screen_output_base == 'post') {
			if ( version_compare( WPV_VERSION, '1.4' ) < 0 ) {
				
				wp_deregister_script( 'toolset-colorbox' );
				wp_register_script( 'toolset-colorbox' , WPV_URL . '/res/js/redesign/lib/jquery.colorbox-min.js', array('jquery'), WPV_VERSION);
				wp_deregister_script( 'views-select2-script' );
				wp_register_script( 'views-select2-script' , WPV_URL . '/res/js/redesign/lib/select2/select2.min.js', array('jquery'), WPV_VERSION);
				wp_deregister_script( 'views-utils-script' );
				wp_register_script( 'views-utils-script' , WPV_URL . '/res/js/redesign/utils.js', array('jquery','toolset-colorbox', 'views-select2-script'), WPV_VERSION);
				if ( !wp_script_is( 'views-utils-script' ) ) {
					wp_enqueue_script( 'views-utils-script');
					$help_box_translations = array(
					'wpv_dont_show_it_again' => __("Got it! Don't show this message again", 'wpv-views'),
					'wpv_close' => __("Close", 'wpv-views')
					);
					wp_localize_script( 'views-utils-script', 'wpv_help_box_texts', $help_box_translations );
				}
				
				wp_deregister_style( 'views-dialogs-css' );
				wp_register_style( 'views-dialogs-css', WPV_URL . '/res/css/dialogs.css', array(), WPV_VERSION );
				if ( !wp_style_is( 'views-dialogs-css' ) ) {
					wp_enqueue_style('views-dialogs-css');
				}
				
			}
	        }
	}
	
	/**
	 * Auto-register the computed values as Types fields.
	 * This method automatically creates the Woocommerce Views filter custom fields and group.
	 * Hooked to wp_loaded
	 * @access public
	 * @return void
	 */	
	
	public function wpv_register_typesfields_func() {
		
		//Define WC Views canonical custom field array
		$wc_views_custom_fields_array=array('views_woo_price','views_woo_on_sale','views_woo_in_stock');
		
		//Preparation to Types control
		$wc_views_fields_array=array();
		$string_wpcf_not_controlled=md5( 'wpcf_not_controlled');
		foreach ($wc_views_custom_fields_array as $key=>$value) {
			$wc_views_fields_array[]=$value.'_'.$string_wpcf_not_controlled;		
		}
		
	   if (defined('WPCF_INC_ABSPATH')) {
	   	   //First, check if WC Views Types Group field does not exist
	   	   if (!($this->check_if_types_group_exist('WooCommerce Views filter fields'))) {
	       	require_once WPCF_INC_ABSPATH . '/fields.php';
	       	//Part 1: Assign to Types Control
	       	//Get Fields
	       	$fields = wpcf_admin_fields_get_fields(false, true);
	       	$fields_bulk = wpcf_types_cf_under_control('add',array('fields' => $wc_views_fields_array));
	       
	       	foreach ($fields_bulk as $field_id) {
		
	        	  if (isset($fields[$field_id])) {
	        	        $fields[$field_id]['data']['disabled'] = 0;
	        	  }
		
	       	}
	       	//Save fields
	       	wpcf_admin_fields_save_fields($fields);    
	
	       	//Retrieve updated fields
	       	$fields = wpcf_admin_fields_get_fields(false, false);
	       
	       	//Assign names
	       	foreach ($fields as $key=>$value) {
	       		  if ($key=='views_woo_price') {
	       		  	$fields['views_woo_price']['name']='WooCommerce Product Price';
	       		  } elseif ($key=='views_woo_on_sale') {
	       		  	$fields['views_woo_on_sale']['name']='Product On Sale Status';
	       		  } elseif ($key=='views_woo_in_stock') {
	       		  	$fields['views_woo_in_stock']['name']='Product In Stock Status';
	       	 	 }       	
	       	}
	       
	       	//Save fields
	       	wpcf_admin_fields_save_fields($fields);
	       	
	       	//Define group
	       	$group=array(
	       	'name' => 'WooCommerce Views filter fields',
	       	'description' => '',
	       	'filters_association' => 'any',
	       	'conditional_display' => array('relation'=>'AND','custom'=>''),
	       	'preview' =>	'edit_mode',
	       	'admin_html_preview' =>'',
	       	'admin_styles' =>'',
	       	'slug' => 'wc-views-types-groups-fields');
	       
	       	//Save group
	       	$group_id=wpcf_admin_fields_save_group($group);
	       
	       	//Save group fields       
	       	wpcf_admin_fields_save_group_fields($group_id,$fields_bulk);  
	   		}   
	       
	   }
	}
	
	/**
	 * Helper method to check if the WooCommerce Views filter groups field already exist.
	 * Parameter is the $title which is the Types group post title in WordPress post table.
	 * @param $title
	 * @access public
	 * @return boolean
	 */	
	
	public function check_if_types_group_exist( $title ) {
		global $wpdb;		
		$return= $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title=%s && post_status = 'publish' && post_type = 'wp-types-group' ", $title),'ARRAY_N');
		if( empty( $return ) ) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Adds WooCommerce Views admin settings menu page on backend.
	 * Hooks to admin_menu
	 * @access public
	 * @return void
	 */	
	
	public function woocommerce_views_add_this_menupage() {
		    
	    //Retrieved missing plugins information
		$missing_required_plugin=$this->check_missing_plugins_for_woocommerce_views();
		
	    //Add admin screen only when all required plugins are activated
	    if (empty($missing_required_plugin)) {			

			/** WooCommerce Views 2.4+ -> Transfer the WC Views menu to Views menu as one of its submenu */
			
			add_submenu_page( 
							//Parent slug
							'views',
							//Page Title
							__('WooCommerce Views', 'woocommerce_views'),
							//Menu Title
							__('WooCommerce Views', 'woocommerce_views'),
							//Capability
							'manage_options', 
							//Menu Slug
							'wpv_wc_views',
							//Function
			 				array(&$this, 'woocommerce_views_admin_screen')
							);
		}		 	
	}
	
	/**
	 * Setup WP Cron processing for WooCommerce Views filter custom field batch updates.
	 * Hooks to init.
	 * @access public
	 * @return void
	 */

	public function prefix_setup_schedule() {
		
		//Retrieved current batch processing settings	
		$batch_processing_settings_saved=get_option('woocommerce_views_batch_processing_settings');
		$settings_set=$batch_processing_settings_saved['woocommerce_views_batchprocessing_settings'];
		$intervals_set=$batch_processing_settings_saved['batch_processing_intervals_woocommerce_views'];
	
		//Retrieved available schedules and formulate cron hook name dynamically
		$available_cron_schedules=wp_get_schedules();
		$cron_hookname=array();
		foreach ($available_cron_schedules as $key_cron=>$value_cron) {
			$cron_hookname['prefix_'.trim($key_cron).'_event']=$key_cron;
		}
		
		//Run this function only if using wordpress cron
		if ($settings_set=='using_wordpress_cron') {
			//Using WP cron
			//Dynamically scheduled events based on user settings
			if (is_array($cron_hookname) && (!(empty($cron_hookname)))) {
				
				foreach ($cron_hookname as $key_hookname=>$value_hookname) {
					//If hook is not scheduled AND also the current settings; schedule this event
					if ((!wp_next_scheduled($key_hookname)) && ($intervals_set==$value_hookname)) {
						wp_schedule_event( time(), $value_hookname, $key_hookname);
					}		
				}
				
			}
			//Dynamically add hooks based on user settings
			if (is_array($cron_hookname) && (!(empty($cron_hookname)))) {
				foreach ($cron_hookname as $key_hookname=>$value_hookname) {
					if ($intervals_set==$value_hookname) {
						add_action($key_hookname, array(&$this,'ajax_process_wc_views_batchprocessing'));					
					}	
				}			
			}
		} else {
			//Not using WP Cron, make sure all schedules are cleared
			if (is_array($cron_hookname) && (!(empty($cron_hookname)))) {
				foreach ($cron_hookname as $key_hookname=>$value_hookname) {				
					wp_clear_scheduled_hook($key_hookname);		
				}
			}
		}	
	
	}
	
	/**
	 * Method to get all WooCommerce product IDs in database.
	 * @access public
	 * @return array
	 */	
	
	public function wc_views_get_all_product_ids_clean() {
	
		global $wpdb;
		$all_product_ids=$wpdb->get_results("SELECT ID FROM $wpdb->posts where post_status='publish' AND post_type='product'",ARRAY_N);
		$clean_ids_for_processing=array();
		if ((is_array($all_product_ids)) && (!(empty($all_product_ids)))) {
			foreach ($all_product_ids as $key=>$value) {
				$clean_ids_for_processing[]=reset($value);
			}
		}
	
		return $clean_ids_for_processing;
	
	}
	
	/**
	 * Reset WooCommerce Views products Content Template
	 * @access public
	 * @return void
	 */	

	public function wc_views_reset_products_content_template() {
	
		$clean_ids_for_processing=$this->wc_views_get_all_product_ids_clean();
		
		//Reset product template to none
		//Set their templates to none
		if ((is_array($clean_ids_for_processing)) && (!(empty($clean_ids_for_processing)))) {
			foreach ($clean_ids_for_processing as $k=>$v) {
				$success_updating_template=update_post_meta($v, '_views_template', '');
			}
		}	
	}
	
	/**
	 * Runtime template checker.
	 * Detects changes in theme templates used with WooCommerce Views.
	 * @access public
	 * @return void
	 */	
	
	public function wc_views_runtime_template_checker() {
	
		//Runtime template checker for single products
	
		$runtime_active_template=get_stylesheet();
		$template_in_db_wc_template=get_option('woocommerce_views_theme_template_file');
		if ((is_array($template_in_db_wc_template)) && (!(empty($template_in_db_wc_template))))  {
			$template_in_db_wc_template_value=key($template_in_db_wc_template);
		
			if ($runtime_active_template != $template_in_db_wc_template_value) {
				 
				//User must have been switched to a different template, use default
				//Update to dB
				$runtime_settings_value=array();
				$runtime_option_name='woocommerce_views_theme_template_file';
				$runtime_settings_value[$runtime_active_template]='Use WooCommerce Default Templates';
				$runtime_updating_success=update_option( $runtime_option_name, $runtime_settings_value);
				
				//Reset content templates
				$this->wc_views_reset_products_content_template();

				//Archives too
				$archiveruntime_settings_value=array();
				$archiveruntime_option_name='woocommerce_views_theme_archivetemplate_file';
				$archiveruntime_settings_value[$runtime_active_template]='Use WooCommerce Default Archive Templates';
				$archiveruntime_updating_success=update_option( $archiveruntime_option_name, $archiveruntime_settings_value);
				
				//Reset archives
				$this->reset_wp_archives_wcviews_settings();				
			}
		}
	
		//Runtime template checker for product archives
		$template_in_db_wc_archivetemplate=get_option('woocommerce_views_theme_archivetemplate_file');
		if (!($template_in_db_wc_archivetemplate)) {
			
		   //No template set, could be first time use.
		   //Let's checked whether this user overrides archive template in theme
		   //Since WooCommerce plugin uses that, we will use this as default too
		   $archive_template_theme_override=$this->wc_views_check_if_product_archive_template_exists();
		   if ($archive_template_theme_override) {
		   	 //Archive template override exist
		   	 $this->wcviews_save_php_archivetemplate_settings($archive_template_theme_override);
		   }
		}
		
	}
	
	/**
	 * Save WooCommerce Views template settings to the options table.
	 * Automatically assign Content Templates based on user selection.
	 * @param  string $woocommerce_views_template_to_override
	 * @access public
	 * @return void
	 */	
	
	public function wcviews_save_php_template_settings($woocommerce_views_template_to_override) {
	
		//Save template settings to options table
		$option_name='woocommerce_views_theme_template_file';
		
		//Template validation according to the status of Layouts plugin		
		$layouts_plugin_status=$this->wc_views_check_status_of_layouts_plugin();
		$woocommerce_views_supported_templates= $this->load_correct_template_files_for_editing_wc();
		$woocommerce_views_template_to_override_slashed_removed=stripslashes(trim($woocommerce_views_template_to_override));
		
		//Let's handle if user is originally using non-Layout supported PHP templates
		//Then user activates Layouts plugin
		if ($layouts_plugin_status) {
			
			//Layouts activated
			if (!(in_array($woocommerce_views_template_to_override_slashed_removed,$woocommerce_views_supported_templates))) {
					
				//User saved a PHP template which is not Layouts supported
				//Automatically use default WooCommerce Templates
				$woocommerce_views_template_to_override = 'Use WooCommerce Default Templates';
			}
		} elseif (!(($layouts_plugin_status))) {
			//Layouts deactivated			
			
			if (!(in_array($woocommerce_views_template_to_override_slashed_removed,$woocommerce_views_supported_templates))) {
					
				//User saved a PHP template which is not Loops supported
				//Automatically use default WooCommerce Templates
				$woocommerce_views_template_to_override = 'Use WooCommerce Default Templates';
			}				
		}
		
		$template_associated=get_stylesheet();
		$settings_value=array();
		$settings_value[$template_associated]=stripslashes(trim($woocommerce_views_template_to_override));
		$success=update_option( $option_name, $settings_value);
	
		//Reset content templates to none if using Default WooCommerce Template
		//Template saved
		$template_saved= stripslashes(trim($woocommerce_views_template_to_override));
	
		$clean_ids_for_processing=$this->wc_views_get_all_product_ids_clean();
		
		if ($template_saved=='Use WooCommerce Default Templates') {
		 
			//Reset product template to none
			//Set their templates to none
			if ((is_array($clean_ids_for_processing)) && (!(empty($clean_ids_for_processing)))) {
				foreach ($clean_ids_for_processing as $k=>$v) {
					$success_updating_template=update_post_meta($v, '_views_template', '');
				}
			}		
	
		} else {
			//Non-Default, switch back to content templates
			global $WP_Views;
			$content_template_options = $WP_Views->get_options();
			if (isset($content_template_options)) {
				if (!(empty($content_template_options))) {
					if (isset($content_template_options['views_template_for_product'])) {
						//Product content template is set, re-assigned
						$content_template_products=$content_template_options['views_template_for_product'];
							if ($content_template_products) {
							if ((is_array($clean_ids_for_processing)) && (!(empty($clean_ids_for_processing)))) {
								foreach ($clean_ids_for_processing as $k=>$v) {
									$success_updating_template=update_post_meta($v, '_views_template', $content_template_products);
								}
							}
						}
		
					}
		
				}	
			}
		}
	}
	
	/**
	 * Save batch processing related settings.
	 * Parameter taken from $_POST:
	 * 
	 * 	raw $_POST['woocommerce_views_batchprocessing_settings'] = $woocommerce_views_cf_batchprocessing_settings
	 *  raw $_POST['batch_processing_intervals_woocommerce_views'] =$woocommerce_views_cf_interval_settings
	 *  raw $_POST['system_cron_access_url'] =$woocommerce_views_cf_syscronurl_settings
	 *  
	 * @param  string $woocommerce_views_cf_batchprocessing_settings
	 * @param  string $woocommerce_views_cf_interval_settings
	 * @param  string $woocommerce_views_cf_syscronurl_settings
	 * @access public
	 * @return boolean mixed
	 */	
	
	public function wcviews_save_batch_processing_related_settings($woocommerce_views_cf_batchprocessing_settings,
															 $woocommerce_views_cf_interval_settings,
															 $woocommerce_views_cf_syscronurl_settings) {
	
		//Save batch processing related settings
		$option_name_batch_processing_settings='woocommerce_views_batch_processing_settings';
		$woocommerce_views_batchprocessing_settings=trim($woocommerce_views_cf_batchprocessing_settings);
		$batch_processing_intervals_woocommerce_views=trim($woocommerce_views_cf_interval_settings);
		$system_cron_access_url=stripslashes(trim($woocommerce_views_cf_syscronurl_settings));
	
		$batch_processing_settings_value=array();
		if (isset($woocommerce_views_batchprocessing_settings)) {
			$batch_processing_settings_value['woocommerce_views_batchprocessing_settings']=$woocommerce_views_batchprocessing_settings;
		}
		if (isset($batch_processing_intervals_woocommerce_views)) {
			$batch_processing_settings_value['batch_processing_intervals_woocommerce_views']=$batch_processing_intervals_woocommerce_views;
		}
		if (isset($system_cron_access_url)) {
			$batch_processing_settings_value['system_cron_access_url']=$system_cron_access_url;
		}
		
		//Update options
		$success_batch_processing_settings=update_option( $option_name_batch_processing_settings, $batch_processing_settings_value);	
		
		if ($success_batch_processing_settings) {
	         return TRUE;
	    }
	}
	
	/* Show error when no content templates are set but user is overriding PHP templates*/
	public function no_content_template_set_error() {
		//For sure, no Content Templates assigned to Products here..
		//Run the entire message display only on WCV admin screen
		$screen_output_wc_views= get_current_screen();
		$screen_output_id= $screen_output_wc_views->id;
		
		//Define message to null.
		$message='';		
		
		if  ('views_page_wpv_wc_views' == $screen_output_id) {
			//WCV admin screen

			if( defined('WPDDL_VERSION') ) {
				 
				//Layouts is activated on this site
			
				global $wpddlayout;
				 
				if (is_object($wpddlayout)) {
					 
					//Access Layouts post type object
					$layout_posttype_object=$wpddlayout->post_types_manager;
					 
					if (method_exists($layout_posttype_object,'get_layout_to_type_object')) {
			
						//Check if product post type has been assigned with Layouts
						$result=$layout_posttype_object->get_layout_to_type_object( 'product' );
						 
						if ($result) {
							//Products post type, now assigned with Layouts. Do nothing.
						} else {
							//Products post type has not been assigned with Layouts.
							//Offer to use Layouts to customize product page
							$layouts_admin_list=admin_url().'admin.php?page=dd_layouts';
							//Revised message when
							//Layouts plugin is active, but no layout assigned to products, let's only ask to create a layout.
							//https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/193346933/comments
			
							$message= __('Congrats','woocommerce-views').'!'.' '.
				        		__('The products template you selected uses Layouts. Next, you need to','woocommerce-views').' '.
									'<a target="_blank" href="'.$layouts_admin_list.'">'.__('create a layout for products','woocommerce-views').'</a>'.'.';
						}
		        	}
				}
									 
									 
			} else {
						//No layouts plugin, suggest to use Content Templates
						//Let's make sure no Content Templates are assigned
							
						$this->wc_views_reset_products_content_template();
						$ct_admin_list=admin_url().'admin.php?page=view-templates';
			
						$get_current_settings_wc_template=get_option('woocommerce_views_theme_template_file');
						$woocommerce_views_supported_templates= $this->load_correct_template_files_for_editing_wc();
			
						if 	(($get_current_settings_wc_template) && (is_array($woocommerce_views_supported_templates))) {
			
						//Settings initialized
						$get_key_template=key($get_current_settings_wc_template);
						$get_current_settings_wc_template_path=$get_current_settings_wc_template[$get_key_template];
				   
				   
						//Let's double check first if this is not using default WooCommerce templates
						if (!(in_array($get_current_settings_wc_template_path,$woocommerce_views_supported_templates))) {
				   
						//In this case, user is previously using a PHP template that only supports Layouts not Content Templates,we don't show warning in this case
							//Since it will revert to WooCommerce default template automatically.
			
						} else {
						//Qualified template selected but no Content Template, let's show message
						//If Layouts isn't active and Views is (but no CT assigned to products), display:
							$message= __('Congrats','woocommerce-views').'!'.' '.
							__("You've selected your own blank template for products. Next, you need to",'woocommerce-views').' '.
							'<a target="_blank" href="'.$ct_admin_list.'">'.__('create a Content Template for products','woocommerce-views').'</a>'.'.';
	   					}
	   					}
	   	  }			
		}
	   //Let's check if we have $message to output
	   if (!(empty($message))) {
	   	  //Has message to output	   
	   ?>
<div class="error">
	<p>		        
		   		<?php 
		  		    echo $message;
		  		?>
		        </p>
</div>
<?php
	   }
	}
		
	/* WooCommerce Views admin screen */
	public function woocommerce_views_admin_screen() {
	
		if (isset($_POST['woocommerce_views_nonce'])) {
			if (( wp_verify_nonce( $_POST['woocommerce_views_nonce'], 'woocommerce_views_nonce' )) && (isset($_POST['woocommerce_views_template_to_override'])))  {
	            
	            //Save PHP template settings
	            $woocommerce_views_template_to_override=$_POST['woocommerce_views_template_to_override'];
	            $this->wcviews_save_php_template_settings($woocommerce_views_template_to_override);                 
	      
	            //Save PHP archive template settings
	            $woocommerce_views_archivetemplate_to_override=$_POST['woocommerce_views_archivetemplate_to_override'];
	            $this->wcviews_save_php_archivetemplate_settings($woocommerce_views_archivetemplate_to_override);
	            	            
	            //Save WooCommerce wrapper on the_content() div
	            
	            if (isset($_POST['container_div_wrapper_wc'])) {
					$container_div_wrapper_wc=$_POST['container_div_wrapper_wc'];
	            	update_option('woocommerce_views_wrap_the_content',$container_div_wrapper_wc);
	            } else {
	                //Save "no"
	                $container_div_wrapper_wc='no';
	                update_option('woocommerce_views_wrap_the_content',$container_div_wrapper_wc);
	            }
	            
	            //Save batch processing related settings
	                        
	            $woocommerce_views_batchprocessing_settings_post=$_POST['woocommerce_views_batchprocessing_settings'];
	            $batch_processing_intervals_woocommerce_views_post=$_POST['batch_processing_intervals_woocommerce_views'];
	            $system_cron_access_url_post=$_POST['system_cron_access_url'];
	            
	            $this->wcviews_save_batch_processing_related_settings($woocommerce_views_batchprocessing_settings_post,
																	  $batch_processing_intervals_woocommerce_views_post,
																	  $system_cron_access_url_post);            
	                   
				header("Location: admin.php?page=wpv_wc_views&update=true");		
									
			}
		}	
	
	?>	
	    <?php 
	    $this->wc_views_runtime_template_checker();
	    ?>
<div class="wrap">
	<div id="icon-options-general" class="icon32">
		<br />
	</div>
	<h2><?php _e('WooCommerce Views','woocommerce_views');?></h2>
			
			<?php
			 if (isset($_GET['update'])) {
	           $update_value=trim($_GET['update']);
	           if ($update_value=='true') {
	         ?>         
	 		 	<div id="update_settings_div_wc_views"
		class="updated wpv-setting-container">
	         	<?php _e('Settings have been updated.','woocommerce_views');?>         
	         	</div>                
	         <?php                                  
	         	}
	         } elseif (isset($_GET['reset'])) {
				$reset_value=trim($_GET['reset']);
				if ($reset_value=='true') {
	         ?>     
	         <div id="update_settings_div_wc_views"
		class="updated wpv-setting-container">
	         <?php _e('Resetting successful.','woocommerce_views');?>         
	         </div>         
	         <?php 
	            }         
	         } elseif (isset($_GET['modulesdownload'])) {
				$downloadsuccess_value=trim($_GET['modulesdownload']);
				if ($downloadsuccess_value=='true') {
					?>
				         <div id="update_settings_div_wc_views"
		class="updated wpv-setting-container">
				         <?php _e('Modules download completed.','woocommerce_views');?>         
				         </div>         
				         <?php 
				}
	         }
	         ?>
	         					
			<form id="woocommerce_views_form"
		action="<?php echo admin_url('admin.php?page=wpv_wc_views&noheader=true'); ?>"
		method="post">
		<input type="submit" id="wcviews-submit-form" class="hidden" />
				<?php 
				wp_nonce_field( 'woocommerce_views_nonce', 'woocommerce_views_nonce'); 			
				$this->wc_views_display_php_template_html();
				$this->wc_views_display_php_archive_template_html();                  
				$this->wc_views_display_gui_adding_container_wc_div();
	            $this->wc_views_display_custom_fields_form_update_html('standard');           
	
	 		if (isset($_GET['update_needed'])) {
				$updateneeded_value=trim($_GET['update_needed']);
				if ($updateneeded_value=='true') {
	        	?>       	
	         <div id="update_needed_wcviews" class="error">
	         <?php _e('Select "Manually" and then please click "Calculate Now" to update fields.','woocommerce_views');?>         
	         </div>
	         <?php 
	            }      
	         }			
			?>		
			<p class="submit">				
				<label class="button-primary" for="wcviews-submit-form"><?php _e('Save all Settings','woocommerce_views');?></label>				
			</p>
		<form id="resetformwoocommerce" method="post" action="">
			<?php wp_nonce_field( 'woocommerce_views_resetnonce', 'woocommerce_views_resetnonce'); ?>	
			    <input type="submit" class="button" id="wc_viewsresetbutton"
				value="Restore default settings"
				onclick="return confirm( '<?php echo esc_js(__('Are you sure? This will revert to default settings and your own settings will be lost!','woocommerce_views')); ?>' );"
				name="reset"> <input type="hidden"
				name="wc_views_resetrequestactivated" value="reset" />
		</form>

</div>
<?php				
	}
	
	/*public function to display GUI for adding a container DIV around the post body for WooCommerce styling.*/
	public function wc_views_display_gui_adding_container_wc_div() {
	    global $wcviews_edit_help;
		$get_settings_wrapper=get_option('woocommerce_views_wrap_the_content');
		if (!($get_settings_wrapper)) {
	    
	    	//FALSE, stil not set,
	    	//Default to yes
	    	$get_settings_wrapper='yes';
	
		}
	?>
<div class="wpv-setting-container">
	<div class="wpv-settings-header wcviews_header_views">
		<h3 id="wc_view_woocommerce_styling_settings"><?php _e('WooCommerce Styling','woocommerce_views');?>
		<i class="icon-question-sign js-wcviews-display-tooltip"
				data-header="<?php echo $wcviews_edit_help['woocommerce_styling']['title']?>"
				data-content="<?php echo $wcviews_edit_help['woocommerce_styling']['content']?>"></i>
		</h3>
	</div>
	<div class="wpv-setting wc_view_woocommerce_styling_class">
		<input type="checkbox"
			<?php if ($get_settings_wrapper=='yes') { echo 'CHECKED'; }?>
			name="container_div_wrapper_wc" value="yes"><?php _e('Add a container DIV around the post body for WooCommerce styling.','woocommerce_views');?>
		</div>
</div>
<?php 
	 if (!(empty($wcviews_edit_help['woocommerce_styling']['message_for_link']))) {
	?>
<div class="toolset-help js-woocommerce-defaultstyling">
	<div class="toolset-help-content">
		<p><?php echo $wcviews_edit_help['woocommerce_styling']['message_for_link']?></p>
	</div>
	<div class="toolset-help-sidebar">
		
	</div>
</div>
<?php }?>
	<?php 
	}
	
	/* public function to display WooCommerce Views Custom Fields filter and updating form */
	public function wc_views_display_custom_fields_form_update_html($wcview_cf_rendering_mode) {
		global $wcviews_edit_help;
	?>
	    <?php if ($wcview_cf_rendering_mode=='standard') {?>
<div class="wpv-setting-container">
	<div class="wpv-settings-header wcviews_header_views"> 
	    <?php  }?>
		  <h3><?php _e('Static Product Fields for Parametric Searches','woocommerce_views');?>
		  <i class="icon-question-sign js-wcviews-display-tooltip"
				data-header="<?php echo $wcviews_edit_help['batch_processing_options']['title']?>"
				data-content="<?php echo $wcviews_edit_help['batch_processing_options']['content']?>"></i>
		</h3>
		<?php if ($wcview_cf_rendering_mode=='standard') {?>
		   </div>
	<div class="wpv-setting wc_view_batch_process_class">
		<div id="ajax_result_batchprocessing"></div>
		<div id="ajax_result_batchprocessing_logging">
			<div id="ajax_result_batchprocessing_time">	
		<?php
		$updated_batch_processing_time=get_option('woocommerce_last_run_update');
		if ((isset($updated_batch_processing_time)) && (!(empty($updated_batch_processing_time)))) {
		$last_run_text=__('Calculated Product fields were last updated: ','woocommerce_views');
		   echo $last_run_text.$updated_batch_processing_time;
		} else {
	       $default_run_text_without_set=__('Static product fields have never been calculated, so you cannot create parametric searches for WooCommerce products. Choose one of the automated schedules for calculating fields, or click on "Calculate Now", to calculate manually.','woocommerce_views');
	       echo $default_run_text_without_set;
	    }
		?>		
		</div>
		</div>
		<?php }?>					
		<div id="batchprocessing_woocommerce_views">
		<?php 
		//Retrieved settings from database
		$batch_processing_settings_from_db=get_option('woocommerce_views_batch_processing_settings');
		if (!(empty($batch_processing_settings_from_db))) {
	       //Settings set
	                     
	       if (isset($batch_processing_settings_from_db['woocommerce_views_batchprocessing_settings'])) {
	 	      $form_woocommerce_views_batchprocessing_settings=$batch_processing_settings_from_db['woocommerce_views_batchprocessing_settings'];
	       } else {
	          //Default to manually
			  $form_woocommerce_views_batchprocessing_settings='manually';
	       }
	
	       if (isset($batch_processing_settings_from_db['batch_processing_intervals_woocommerce_views'])) {
	           $form_batch_processing_intervals_woocommerce_views=$batch_processing_settings_from_db['batch_processing_intervals_woocommerce_views'];
	       } else {
	           //Default to daily
	           $form_batch_processing_intervals_woocommerce_views='daily';
	       }
	       
	       if (isset($batch_processing_settings_from_db['system_cron_access_url'])) {
	           $form_system_cron_access_url=$batch_processing_settings_from_db['system_cron_access_url'];
	       } else {
	           //Default 
			   //$plugin_abs_path_retrieved=plugin_dir_path( __FILE__ );
			   //Revise to URL path
			   $plugin_abs_path_retrieved=plugins_url( 'system_cron/run_wc_views_cron.php', __FILE__ );
			   $form_system_cron_access_url=$plugin_abs_path_retrieved;
	       }
	                      
	    } else {
	        //Batch processing options not set, define defaults
	        $form_woocommerce_views_batchprocessing_settings='manually';
	        $form_batch_processing_intervals_woocommerce_views='daily';                        
	       //$plugin_abs_path_retrieved=plugin_dir_path( __FILE__ );
	        $form_system_cron_access_url=$this->wc_views_generate_cron_access_url_settings();                                          
	   }
	   ?>
	   <?php if ($wcview_cf_rendering_mode=='standard') {?>
	   <p><?php _e('Select when to update the static product fields:','woocommerce_views');?></p>
	   <?php } ?>
	   <?php if ($wcview_cf_rendering_mode=='wizard') {?>
	   <p><?php _e('To automatically update, simply click the "Next" button, otherwise you can skip this step.','woocommerce_views');?></p>
			<p><?php _e('You can also update these fields manually or automatically after this wizard.','woocommerce_views');?></p>
	   <?php }?>
	   <?php if ($wcview_cf_rendering_mode=='standard') {?>
	   <p>
				<input type="radio"
					name="woocommerce_views_batchprocessing_settings"
					id="system_cron_id_wc_views" value="using_system_cron"
					<?php if ($form_woocommerce_views_batchprocessing_settings=='using_system_cron') { echo "checked"; }?>> <?php _e('Using a system cron, by calling this URL:','woocommerce_views');?><input
					readonly="readonly" type="text" name="system_cron_access_url"
					id="wc_views_sys_cron_path"
					value="<?php echo $form_system_cron_access_url;?>">
			</p>
			<p>
				<input type="radio"
					name="woocommerce_views_batchprocessing_settings"
					id="wp_cron_id_wc_views" value="using_wordpress_cron"
					<?php if ($form_woocommerce_views_batchprocessing_settings=='using_wordpress_cron') { echo "checked"; }?>> <?php _e('Using the WordPress cron','woocommerce_views');?>
		<select name="batch_processing_intervals_woocommerce_views">
		<?php 
		//Dynamically retrieved available schedules for cron
		$available_schedules_for_cron=wp_get_schedules();					
			foreach ($available_schedules_for_cron as $key_schedule=>$value_schedule) {
		?>
			<option
						<?php if ($form_batch_processing_intervals_woocommerce_views==$key_schedule) { echo 'selected';}?>
						value="<?php echo $key_schedule;?>"><?php echo $available_schedules_for_cron[$key_schedule]['display'];?></option>					
	  <?php } ?>
		</select>
			</p>
			<p>
				<input type="radio"
					name="woocommerce_views_batchprocessing_settings"
					id="manual_id_wc_views" value="manually"
					<?php if ($form_woocommerce_views_batchprocessing_settings=='manually') { echo "checked"; }?>> <?php _e('Manually','woocommerce_views');?></p>									
	   <?php } ?>
	   </div>
	   <?php if ($wcview_cf_rendering_mode=='standard') {?>
	   </form>  
	   <?php 
		$this->wc_views_display_calculate_product_attributes_form_html();
	    } 
	   ?>
	    <?php if ($wcview_cf_rendering_mode=='standard') {?>
	      </div>
</div>
<?php 
	    if (!(empty($wcviews_edit_help['batch_processing_options']['message_for_link']))) {
	    ?>
<div class="toolset-help js-wcviews_batchprocessing">
	<div class="toolset-help-content">
		<p><?php echo $wcviews_edit_help['batch_processing_options']['message_for_link']?></p>
	</div>
	<div class="toolset-help-sidebar">
		
	</div>
</div>
<?php }?>	    
	    <?php  }?>
	<?php 
	}
	
	/*public function for generating cron access URL*/
	public function wc_views_generate_cron_access_url_settings() {
	
		//Revise to URL path
		//First time executed, generate secret key
		$length=12;
		$generated_secret_key=$this->wc_views_generaterandomkey($length);
		
		//Store this secret key as options for easy verification
		$value_changed=update_option('wc_views_sys_cron_key',$generated_secret_key);
		$plugin_abs_path_retrieved=plugins_url( 'system_cron/run_wc_views_cron.php?cron_key='.$generated_secret_key, __FILE__ );
	
		return $plugin_abs_path_retrieved;
	}
	
	/* public function to display calculator product attributes form */
	public function wc_views_display_calculate_product_attributes_form_html() {
	   
	    //Independently triggered
	?>
<form id="requestformanualbatchprocessing" method="post" action="">
	<input id="woocommerce_batchprocessing_submit" type="submit"
		name="Submit" class="button-secondary"
		onclick="return confirm( '<?php echo esc_js(__('Are you sure you want to manually run this batch processing?','woocommerce_views')); ?>' );"
		value="<?php _e('Calculate Now','woocommerce_views');?>" />
</form>
<?php 
	}
	
	/**
	 * Method for checking the status of Layouts plugin
	 * @access public
	 * @return boolean
	 */
	
	public function wc_views_check_status_of_layouts_plugin() {
		
		global $wpddlayout;
		
		if( defined('WPDDL_VERSION') ) {

			$wpddl_version=WPDDL_VERSION;
			
			if ((!(empty($wpddl_version))) && (is_object($wpddlayout))) {
				
				//Layouts is full activated and ready to use
				return TRUE;				
			} else {
				
			    return FALSE;	
			}			
		} else {
			
		   return FALSE;
			
		}
	}	
	
	/**
	 * Method for displaying the PHP template selection
	 * @access public
	 * @return void
	 */
	
	public function wc_views_display_php_template_html($wcview_cf_rendering_mode='standard') {
		
	global $wcviews_edit_help;
	$woocommerce_views_supported_templates= $this->load_correct_template_files_for_editing_wc();
	$single_product_php_template_check=$this->wc_views_check_if_single_product_template_exists();
	$layouts_plugin_status=$this->wc_views_check_status_of_layouts_plugin();
	?>
	<?php 
		if ($wcview_cf_rendering_mode=='standard') {
	?>
		<?php 
	    if (!(empty($wcviews_edit_help['top_general_helpbox']['message_for_link']))) {
	    ?>
<div class="toolset-help js-wcviews_top_general_helpbox">
	<div class="toolset-help-content">
		<p><?php echo $wcviews_edit_help['top_general_helpbox']['message_for_link']?></p>
	</div>
	<div class="toolset-help-sidebar">
		
	</div>
</div>
<?php }?>

<div class="wpv-setting-container">
	<div class="wpv-settings-header wcviews_header_views">
	<?php
 		}
	?>
				<h3>
					<?php _e('Product Template File','woocommerce_views');?>
					<i class="icon-question-sign js-wcviews-display-tooltip"
				data-header="<?php echo $wcviews_edit_help['template_assignment_section']['title']?>"
				data-content="<?php echo $wcviews_edit_help['template_assignment_section']['content']?>"></i>
		</h3>
				
	<?php 
		if ($wcview_cf_rendering_mode=='standard') {
	?>
		   		</div>
	<div class="wpv-setting">
	<?php
   		}
		?>			
					<div id="phptemplateassignment_wc_views">
			<p><?php _e('Select the PHP template which will be used for WooCommerce single-product pages:','woocommerce_views');?></p>
			<p>
					<?php 
					if (!(empty($woocommerce_views_supported_templates))) {
					?>	
	
					<?php
			 		if ($wcview_cf_rendering_mode=='wizard') {
						$var_selector='id="woocommerce_views_template_to_override_unique_id"';
			 		} else {
	    	 		   $var_selector='';
	    	 		}
	
					$get_current_settings_wc_template=get_option('woocommerce_views_theme_template_file');
					if 	($get_current_settings_wc_template) {
		
	    	    		//Settings initialized	
						$get_key_template=key($get_current_settings_wc_template);
						$get_current_settings_wc_template_path=$get_current_settings_wc_template[$get_key_template];
						
						//Backward compatibility, removing the PHP template with layouts and merging templates into one canonical
						//https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/193344321/comments
						
						if ((strpos($get_current_settings_wc_template_path, 'single-product-layouts.php') !== false)) {
							
							//Using Layouts template, deprecated	
							//Ensure its now using canonical template						
							$canonical_product_template[$get_key_template] = $single_product_php_template_check;
	    					update_option('woocommerce_views_theme_template_file',$canonical_product_template);
							
							//Use canonical template
							$get_current_settings_wc_template_path=$single_product_php_template_check;	

						}
						
						//Let's handle if user is originally using non-Layout supported PHP templates
						//Then user activates Layouts plugin
						if ($layouts_plugin_status) {														
						    //Layouts activated
							if (!(in_array($get_current_settings_wc_template_path,$woocommerce_views_supported_templates))) {
							
								//User originally selected PHP template is not Layouts supported
								//Automatically use default WooCommerce Templates
								$this->wcviews_save_php_template_settings('Use WooCommerce Default Templates');
								$get_current_settings_wc_template_path='Use WooCommerce Default Templates';									
							}							
						} elseif (!(($layouts_plugin_status))) {
						   //Layouts deactivated
							if (!(in_array($get_current_settings_wc_template_path,$woocommerce_views_supported_templates))) {
									
								//User originally selected PHP template is not Layouts supported
								//Automatically use default WooCommerce Templates
								$this->wcviews_save_php_template_settings('Use WooCommerce Default Templates');
								$get_current_settings_wc_template_path='Use WooCommerce Default Templates';
							}						   	
							
						}
	
						if ((is_array($woocommerce_views_supported_templates)) && (!(empty($woocommerce_views_supported_templates)))) {
					     	$counter_p=1;
	    	      	     	foreach ($woocommerce_views_supported_templates as $template_file_name=>$theme_server_path) {				
					?>
	    			<?php 
	    			$p_id='ptag_'.$counter_p;
	    			?>
					
			
			
			<div class="template_selector_wc_views_div" id="<?php echo $p_id;?>">
				<input <?php echo $var_selector;?> type="radio"
					name="woocommerce_views_template_to_override"
					value="<?php echo $theme_server_path?>"
					<?php if ($get_current_settings_wc_template_path==$theme_server_path) { echo "CHECKED";} ?>>				
						<?php 
						    if ('Use WooCommerce Default Templates' ==$template_file_name) {
						       //Clarity
						    	if ($layouts_plugin_status) {
						    		$template_file_name = "WooCommerce Plugin Default Template (doesn't display layouts)";
						    	} else {
						    		$template_file_name = 'WooCommerce Plugin Default Templates';
						    	}
						    	
						    }
							echo $template_file_name;
						?>
						<a class="show_path_link" href="javascript:void(0)"><?php _e('Show template','woocommerce_views');?></a>
				<div class="show_path_wcviews_div" style="display: none;">
					<textarea rows="2" cols="50" class="inputtextpath" readonly />
					</textarea>
				</div>
			</div>
	    			<?php 
	    			$counter_p++;
	    			?>
	         	    	<?php
						  }
	        	     	} else {
	        	     	//not loaded                  
	         	    	?>
	        	     		<p>
				<input type="radio" name="woocommerce_views_template_to_override"
					value="Use WooCommerce Default Templates">
	        	     		<?php _e('Use WooCommerce Default Templates','woocommerce_views');?>
	        	     		</p>             			
	        	     	<?php
 						}
 						?>
	        	 <?php
 				 } else {
	        	     	
	        	     	//Not initialized 
		
	        	     	//Check if no template is saved yet.
	        	     	$status_template=$this->wc_views_check_if_using_woocommerce_default_template();
	        	     	
	        	     	if ((is_array($woocommerce_views_supported_templates)) && (!(empty($woocommerce_views_supported_templates)))) {
	        	     		$counter_p=1;
	        	     		foreach ($woocommerce_views_supported_templates as $template_file_name=>$theme_server_path) {
	       	             $file_basename=basename($theme_server_path);  
	       	            
	       	             $p_id='ptag_'.$counter_p;
	       	             
	       	     ?>             
	 				<div class="template_selector_wc_views_div"
				id="<?php echo $p_id;?>">
				<input <?php echo $var_selector;?> type="radio"
					name="woocommerce_views_template_to_override"
					value="<?php echo $theme_server_path?>"
					<?php 
	 					if ($file_basename=='single-product.php') {
	 						//Don't checked this if there is still no template set
	 						if (!($status_template)) {
								echo "CHECKED";
	 						}
	       	         } elseif (($file_basename=='page.php') && (!($single_product_php_template_check))) {
	       	             //Should be checked if single-product is not available
	       	             if ($wcview_cf_rendering_mode=='wizard') { 
						 		echo "CHECKED";
	        	            } else {
								if ($get_current_settings_wc_template) {
	                	            //Not being resetting on admin screen
	             	               echo "CHECKED";
	             	           }
	             	       }
						} elseif ($file_basename=='Use WooCommerce Default Templates')  {
		
	           	       echo "CHECKED";
	          	        
	          	      } 
						 ?>>
						 <?php
						 if ('Use WooCommerce Default Templates' ==$template_file_name) {
						 	//Clarity
						 	    if ($layouts_plugin_status) {
						    		$template_file_name = "WooCommerce Plugin Default Template (doesn't display layouts)";
						    	} else {
						    		$template_file_name = 'WooCommerce Plugin Default Templates';
						    	}
						 }
						 
						 echo $template_file_name;
						 ?>
						<a class="show_path_link" href="javascript:void(0)"><?php _e('Show template','woocommerce_views');?></a>
				<div class="show_path_wcviews_div" style="display: none;">
					<textarea rows="2" cols="50" class="inputtextpath" readonly />
					</textarea>
				</div>
			</div> 
					<?php $counter_p++;?>           		
	        	  	<?php }     
	       	      	}    
	       	      }
	      	       ?>
	    			<?php    
	    				if (!($single_product_php_template_check)) {
	    			?>              
							<strong><?php _e('Tip','woocommerce_views')?></strong>: <?php _e('You can actually create your own single-product.php template. Please refer to this','woocommerce_views');?> <a
				href="#"><?php _e('explanation','woocommerce_views');?></a>.
					<?php
 						} 
 					?>
			<?php 
				} else {
			?>
					<p class="no_template_found_wc_views_class"><?php _e('ERROR: Your theme does not have compatible templates with WooCommerce Views.','woocommerce_views');?></p>
			<p class="no_template_found_wc_views_class"><?php _e('Suggested workaround:','woocommerce_views');?></p>
			<p class="no_template_found_wc_views_class">- <?php _e('Theme templates like page.php should be found in your theme root directory like','woocommerce_views');?> <em>/wp-content/themes/yourtheme/page.php</em>
			</p>
			<p class="no_template_found_wc_views_class">- <?php _e('Theme templates for usage should contain','woocommerce_views');?> <a
					href="http://codex.wordpress.org/The_Loop"><?php _e('WordPress loops','woocommerce_views')?></a> <?php _e('within their source code','woocommerce_views')?>.</p>
			<p class="no_template_found_wc_views_class">- <?php _e('Use any theme with compatible templates to WooCommerce like','woocommerce_views');?> <a
					href="http://wp-types.com/home/toolset-bootstrap/">Toolset
					Bootstrap</a>.
			</p>
	    	<?php 
				}
			?>
					</p>
		</div>	
						<?php 
						if ($wcview_cf_rendering_mode=='standard') {
						?>
	  			</div>
</div>
<?php if (!(empty($wcviews_edit_help['template_assignment_section']['message_for_link']))) {?>
<div class="toolset-help js-phptemplatesection">
	<div class="toolset-help-content">
		<p><?php echo $wcviews_edit_help['template_assignment_section']['message_for_link']?></p>
	</div>
	<div class="toolset-help-sidebar">
		
	</div>
</div>
<?php }?>
						<?php
 						}
 						?>
	<?php 
	}
	
	/* Reset admin screen settings to default */
	public function reset_all_wc_admin_screen() {
	
		if(isset($_REQUEST['wc_views_resetrequestactivated']))
		{		
			//Verify nonce
			if (isset($_POST['woocommerce_views_resetnonce'])) {
				if ( wp_verify_nonce( $_POST['woocommerce_views_resetnonce'], 'woocommerce_views_resetnonce' ))  {		
			
					//reset to defaults
	       		 //Option names
					$option_name_one='woocommerce_views_theme_template_file';					
					$option_name_two='woocommerce_views_batch_processing_settings';
					$option_name_three='woocommerce_last_run_update';
					$option_name_four='woocommerce_views_wrap_the_content';
					$option_name_five='woocommerce_views_theme_archivetemplate_file';
			
					delete_option($option_name_one);
					delete_option($option_name_two);
					delete_option($option_name_three);	
					delete_option($option_name_four);
					delete_option($option_name_five);
					
					$clean_ids_for_processing_reset=$this->wc_views_get_all_product_ids_clean();
					
					//Reset product template to none
							
					if ((is_array($clean_ids_for_processing_reset)) && (!(empty($clean_ids_for_processing_reset)))) {
						foreach ($clean_ids_for_processing_reset as $k=>$v) {
							$success_updating_template_reset=update_post_meta($v, '_views_template', '');
						}
					}	
	

					$this->reset_wp_archives_wcviews_settings();
	
					//Find out if we need to update
					if (!($views_settings_options == $views_settings_options_original)) {
						//Array is changed;
						$success_updating_wpv_options=update_option('wpv_options',$views_settings_options);					
					}
					
					//redirect to reset =true
					header("Location: admin.php?page=wpv_wc_views&reset=true");
					
				}
			}
		}
	
	}
	
	/*public function to display HTML of exiting wizard*/
	public function display_wc_views_user_exit_wizard_form_html() {
	?>
<form id="request_for_exit_wizard" method="post" action="">
	        	<?php wp_nonce_field('wcviews_exit_wizard_nonce','wcviews_exit_wizard_nonce') ?>
	           	<input id="exit_wizard_button_id" type="submit"
		name="Submit" class="button-secondary"
		onclick="return confirm( '<?php echo esc_js(__('Are you sure you want to exit wizard?','woocommerce_views')); ?>' );"
		value="<?php _e('Exit wizard','woocommerce_views');?>" /> <input
		type="hidden" name="wc_views_exit_wizard_requested"
		value="exit_this_wizard" />
</form>
<?php 
	}
	
	/*public function to display HTML of skipping steps in wizard*/
	public function display_wc_views_user_skipstep_in_wizardform($steps) {
	?>

<form id="request_for_skippingstep_wizard" method="post" action="">
	        	<?php wp_nonce_field('wcviews_skipstep_wizard_nonce','wcviews_skipstep_wizard_nonce') ?>
	           	<input id="skipstep_wizard_button_id" type="submit"
		name="Submit" class="button-secondary"
		value="<?php _e('Skip Step','woocommerce_views');?>" /> <input
		type="hidden" name="wc_views_skipstep_wizard_requested"
		value="<?php echo $steps;?>" />
</form>
<?php 
	}
	
	public function ajax_process_wc_views_batchprocessing($wc_view_woocommerce_orderobject='') {
	
		global $wpdb,$woocommerce;
	    $doing_ajax_batch_processing=false;
	    
		if (defined('DOING_AJAX') && DOING_AJAX ) {			
			//Doing AJAX          
			//Let's catch those sent for AJAX batch processing
			if (isset($_POST['action'])) {
				//action set
				$the_action=$_POST['action'];
				if ('wc_views_ajax_response_admin' == $the_action) {
					$doing_ajax_batch_processing= true;					
				}				
			}
			
			//Catch wrong nonce
           if ((isset($_POST['wpv_wc_views_ajax_response_admin_nonce'])) && ($doing_ajax_batch_processing)) {
           	if (!( wp_verify_nonce( $_POST['wpv_wc_views_ajax_response_admin_nonce'], 'wc_views_ajax_response_admin' )))  {
           			$response['status']='error';
					$response['batch_processing_output'] = __('Batch processing output is not successful because nonce is invalid.','woocommerce_views');
					echo json_encode($response);
					die();
           	}
           }
		}
		
		//Define custom field names
		$views_woo_price = 'views_woo_price';
		$views_woo_on_sale = 'views_woo_on_sale';
		$views_woo_in_stock = 'views_woo_in_stock';
		
		$response=array();
		
		//Get all product ids
		$woocommerce_product_ids=$wpdb->get_results("SELECT ID FROM $wpdb->posts where post_status='publish' AND post_type='product'",ARRAY_N);
		$woocommerce_clean_ids=array();	
		if ((is_array($woocommerce_product_ids)) && (!(empty($woocommerce_product_ids)))) {
			foreach ($woocommerce_product_ids as $key=>$value) {
				$woocommerce_clean_ids[]=reset($value);
			}
		} else {
	        $response['status']='error';
			$response['batch_processing_output'] = __('Batch processing output is not successful because it looks like you do not have products yet.','woocommerce_views');
	    }
		
		if ((is_array($woocommerce_clean_ids)) && (!(empty($woocommerce_clean_ids)))) {
	        //Loop through individual products, get the updated product data needed and save to custom fields
			foreach ($woocommerce_clean_ids as $k=>$v) {
	        $post=get_post($v);         
	        $product = $this->wcviews_setup_product_data($post);
	        	if (isset($product)) {
		           //Retrieve product price
		           $product_price=$product->get_price();
		           
		           //Check if product is on sale
		           $product_on_sale_boolean=$product->is_on_sale();
		           
		           //Check if product is in stock
		           $product_on_stock_boolean=$product->is_in_stock();
		           
		           //"0" adjustment
		           $product_on_stock_boolean=$this->for_views_null_equals_zero_adjustment($product_on_stock_boolean);
		           $product_on_sale_boolean=$this->for_views_null_equals_zero_adjustment($product_on_sale_boolean);
		           
		           //Save to custom fields
		           $success_price=update_post_meta($v,$views_woo_price,$product_price);
		           $success_on_sale=update_post_meta($v,$views_woo_on_sale,$product_on_sale_boolean);
		           $success_stock=update_post_meta($v,$views_woo_in_stock,$product_on_stock_boolean);
	
		           $response['status']='updated';
		           $response['batch_processing_output'] = __('Batch processing output completed.','woocommerce_views');   
	
		        } else {
	
				   $response['status']='error';
				   $response['batch_processing_output'] = __('Batch processing output is not successful because it looks like you do not have products yet.','woocommerce_views');
	           }
	        }    	
	    } else {
			$response['status']='error';
			$response['batch_processing_output'] = __('Batch processing output is not successful because it looks like you do not have products yet.','woocommerce_views');
	    }
	
		/*AJAX SECTION WORKING ON THE BACKEND ADMIN SCREEN*/    
	   
		if ($response['status']='updated') {
		   $response['last_run'] = date_i18n('Y-m-d G:i:s');
		   $success_last_run_update=update_option( 'woocommerce_last_run_update',$response['last_run']);
		}
		
		//Run the ajax response only if object is not set
		
		if ($this->wc_views_two_point_two_above()) {
	       //WC Views 2.2+      
	
			if (!(isset($wc_view_woocommerce_orderobject->post_status))) {
	             //Status NOT set, this is not "checking out". Output this AJAX response.
				if (defined('DOING_AJAX') && DOING_AJAX ) {
					echo json_encode($response);
					die();
				}
			}
	       
	
	    } else {
	       //using before 2.2, working fine.
	
			if (!(isset($wc_view_woocommerce_orderobject->status))) {
				if (defined('DOING_AJAX') && DOING_AJAX ) {
				echo json_encode($response);
				die();
				}
			}
	    }
		
	
		
	} 
	
	public function for_views_null_equals_zero_adjustment($boolean_test) {
	
		if (!($boolean_test)) {
		//False
		return '0';
		
		} else {
		//True
		return '1';
		}
	
	}
	//Generate random key
	public function wc_views_generaterandomkey($length) {
	
		$string='';
		$characters = "0123456789abcdef";
		for ($p = 0; $p < $length ; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
		}
	
		return $string;
	}
	
	public function wc_views_filter_only_relevant_wc_templates_innerdir($complete_template_files_list) {
	
	  if ((is_array($complete_template_files_list)) && (!(empty($complete_template_files_list)))) {
	
	      $sanitized_complete_template_files_list=array();
	      
	      //Loop through the array and sanitize the array
	      foreach ($complete_template_files_list as $unclean_template_name=>$template_path) {
	         
	         $template_name=basename($unclean_template_name);
	         
	         if ($template_name != $unclean_template_name) {
	
	             //File belongs one directory deeper
	             //Check if its a WooCommerce single-products.php template
	             //Two possibilities- single-product.php and archive-product.
	             //This is all we need for now with WooCommerce Views.
	             $unclean_template_name=strtolower($unclean_template_name);
	             if ($unclean_template_name =='woocommerce/archive-product.php') {
	                 $sanitized_complete_template_files_list[$template_name]=$template_path;
	             } elseif ($unclean_template_name =='woocommerce/single-product.php') {
	             	$sanitized_complete_template_files_list[$template_name]=$template_path;
	             }

	         } else {
	
	             //Usual flat directory theme files
	             $sanitized_complete_template_files_list[$unclean_template_name]=$template_path;
	         }        
	     }
	     return $sanitized_complete_template_files_list;
	  }
	}
	
	/**
	 * Aux method to retrieved equivalence between template path and theme name
	 * @access public
	 * @return array
	 */
	public function theme_name_and_template_path($theme) {
		
		$stylesheet=$theme->stylesheet;
		$template=$theme->template;
		
		$information=array();
		
		//First, let's check if site is using child theme
		$is_using_child_theme=FALSE;
		if ($stylesheet != $template ) {
			
			//Templates and Stylesheet are not the same.
			//Site is using child theme
			$is_using_child_theme=TRUE;
			
		}
		
		if ($is_using_child_theme) {
			
			//Let's store the child theme name
			$child_theme_name=$theme->get( 'Name' );
			$information[$stylesheet] = $child_theme_name;
			
			//Let's get the parent theme name
			$parent_theme_name=$theme->parent_theme;
			$information[$template]=$parent_theme_name;	
			
		} else {

			//Only parent theme activated
			$information[$template]=$theme->get( 'Name' );
			
		}
		
		return $information;
	}
	
	/**
	 * Method to check for the existence and use of Genesis Frameworks
	 * @access public
	 * @return boolean
	 */
	public function wc_views_is_using_genesis_framework($theme) {

		$using_genesis_framework=false;		
		
		if (is_object($theme)) {
			
			$current_theme_name ='';
			$parent_headers_name ='';
			
			if (isset($theme->name)) {				
				$current_theme_name=$theme->name;				
			}			
			if (isset($theme->parent_theme)) {				
				$parent_headers_name =$theme->parent_theme;
			}

			$genesis_func_exist= false;
			if (function_exists('genesis')) {				
				$genesis_func_exist=true;
			}
			
			if ((('Genesis' == $current_theme_name) || ('Genesis' == $parent_headers_name)) && ($genesis_func_exist)) {				
				$using_genesis_framework=true;				
			}			
		}	
		
		return $using_genesis_framework;
	}
	
	/**
	 * Method for loading correct template files for using with WooCommerce Views
	 * @access public
	 * @return array
	 */	

	public function load_correct_template_files_for_editing_wc() {
	
		//Get all information about the parent and child theme!
		$theme = wp_get_theme();
		$get_custom_theme_info=$this->theme_name_and_template_path($theme);
		$complete_template_files_list = $theme->get_files( 'php', 1,true);
		$complete_template_files_list = $this->wc_views_filter_only_relevant_wc_templates_innerdir($complete_template_files_list );
		$headers_for_theme_files=$theme->get_page_templates();

		//Retrieve stylesheet directory URI for the current theme/child theme 
		$get_stylesheet_directory_data=get_stylesheet_directory();
		
		//Checked for Genesis Frameworks which uses specialized loops
		$is_using_genesis= $this->wc_views_is_using_genesis_framework($theme);
	
		if ((is_array($complete_template_files_list)) && (!(empty($complete_template_files_list)))) {
	    $correct_templates_list= array();
	    $layouts_plugin_status=$this->wc_views_check_status_of_layouts_plugin();
	    
		foreach ($complete_template_files_list as $key=>$values) {
	       $pos_single = stripos($key, 'single');
	       $pos_page =  stripos($key, 'page');       
	       if (($pos_single !== false) || ($pos_page !== false)) {

	          //https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/193344239/comments
	          //When Layouts plugin is active, only show templates that have the_ddlayouts integration	  
	          $is_theme_template_has_ddlayout =FALSE;
	          $is_theme_template_looped =FALSE;
	          
	          if ($layouts_plugin_status) {
	          	global $wpddlayout;
	          	//Layouts activated

	          	//Ensure single-product is checked at correct path
	          	$template_lower_case= strtolower($key);
	          	if ((strpos($template_lower_case, 'single-product.php') !== false)) {
	          		//This is a single product template at the user theme directory
	          		$key = str_replace($get_stylesheet_directory_data, "", $values);	          			
	          		$key =ltrim($key,'/');          			
	          	}
	          	
	          	$is_theme_template_has_ddlayout= $this->wcviews_template_have_layout($key);	          		
	          		          	
	          } else {
	          	//Layouts inactive, lets fallback to usual PHP looped templates
	          	//Emerson: Qualified theme templates should contain WP loops for WC hooks and Views to work
	          	$is_theme_template_looped= $this->check_if_php_template_contains_wp_loop($values, $is_using_genesis);	          	
	          }
	          
	          //Add those qualified PHP templates only once
	          if ($is_theme_template_looped) {
	          	$correct_templates_list[$key]=$values;
	          } elseif ($is_theme_template_has_ddlayout) {
	          	 //This has a call to ddlayout
	          	$correct_templates_list[$key]=$values;	          	
	          }
	       }      
		}
	       
		   if (!(empty($correct_templates_list))) {
	
	           //Has templated loops to return
	       	   $correct_templates_list['Use WooCommerce Default Templates']='Use WooCommerce Default Templates';
	
	       	   //Append the template name to the file names
	       	   $correct_template_list_final=$this->wcviews_append_templatename_to_templatefilename($correct_templates_list,$headers_for_theme_files,$get_custom_theme_info);
	       	   
	       	   //Include WooCommerce Views Default single-product.php template
	       	   if(defined('WOOCOMMERCE_VIEWS_PLUGIN_PATH')) {
	       	   	 
	       	   	$template_path=WOOCOMMERCE_VIEWS_PLUGIN_PATH.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'single-product.php';	       	   
	       	   	
	       	   	if (file_exists($template_path)) {
	       	   		//Template exist
	       	   		$correct_template_list_final['WooCommerce Views plugin default single product template']=$template_path;       	   		
	       	   	}	       	   	
	       	   	 
	       	   }
	       	   
	           return $correct_template_list_final; 
	                 
	       } else {
		       //In this scenario, no eligible templates are found from the clients theme.
		       //Let's provide the defaults from templates inside the WooCommerce Views plugin
		       
	       		$correct_templates_list['Use WooCommerce Default Templates']='Use WooCommerce Default Templates';
	       	
	       		if(defined('WOOCOMMERCE_VIEWS_PLUGIN_PATH')) {
	       		 
	       			$template_path=WOOCOMMERCE_VIEWS_PLUGIN_PATH.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'single-product.php';	       			
	       	
	       			if (file_exists($template_path)) {
	       			//Template exist
	       				$correct_templates_list['WooCommerce Views plugin default product template (single-product.php)']=$template_path;
	       			}
	       		}	       
	       	
	           return $correct_templates_list;
	       }
		}
	}
	
	/**
	 * Method for verifying if the template has a call to ddlayout (with child theme compatibility)
	 * $file is a complete path to the theme directory to be checked
	 * @access public
	 * @return string
	 */
		
	public function wcviews_template_have_layout( $file )
	{
	
		$bool = false;
	
		$file_abs_child  = get_stylesheet_directory() . '/' . $file;	
		$file_abs_parent = get_template_directory() . '/' . $file;
		
		//Check for file existence 
		//In WordPres child themes, if template exist in child theme directory
		//It overrides the parent, so check if it exists in child first
		//If not check if it exists on parent directory
		
		$file_abs ='';
		
		if (file_exists($file_abs_child)) {
			//It exists in child
			$file_abs = $file_abs_child;			
		} elseif (file_exists($file_abs_parent)) {
		    $file_abs = $file_abs_parent;	
		}

		if (!(empty($file_abs))) {
			//Let's retrieved the contents of this template
			$file_data = @file_get_contents( $file_abs );
	
			if ($file_data !== false) {
				if (strpos($file_data, 'the_ddlayout') !== false) {
					$bool = true;
				}
			}	
		}	 
	
		return $bool;
	}	
	
	/**
	 * Method for getting the theme name based on template path
	 * @access public
	 * @return string
	 */	
	
	public function get_theme_name_based_on_path($template_path,$get_custom_theme_info) {		
		
		//Set empty string
		$theme_name='';
		
		//Get the first degree folder path
		$template_belong=basename(dirname($template_path));		

		if (isset($get_custom_theme_info[$template_belong])) {
			 
			$theme_name=$get_custom_theme_info[$template_belong];
			 
		} else {
			
			//Maybe an internal folder, climb one step			
			$template_belong=basename(dirname(dirname($template_path)));
			if (isset($get_custom_theme_info[$template_belong])) {
				
				$theme_name=$get_custom_theme_info[$template_belong];
			}
		}
		
		return $theme_name;
	}
	
	/**
	 * Method for appending Theme names to templates for clarity purposes.
	 * @access public
	 * @return array
	 */	
	
	public function wcviews_append_templatename_to_templatefilename($correct_template_list,$headers_for_theme_files,$get_custom_theme_info) {
	
	  $correct_template_list_final=array();
	
	  //The defaults array
	  $defaults_name_array=array('page.php'=>__('Theme Page Template','woocommerce_views'),'single.php'=>__("Theme Single Posts Template","woocommerce_views"));
	  
	  if (is_array($correct_template_list)) {
	
	    //Loop through the correct template list
	    foreach ($correct_template_list as $template_file_name=>$template_path) {
			
			//Check if the template filename is in the WP core default page template name array
	       if (isset($headers_for_theme_files[$template_file_name])) {
	           //contain in default array
	           $template_name_retrieved=$headers_for_theme_files[$template_file_name];
               $theme_name=$this->get_theme_name_based_on_path($template_path,$get_custom_theme_info);
               
               //Append names correctly
               $theme_append=$this->theme_append_wcviews_name_correctly($theme_name);
	           $belongs_to_theme=$theme_name.' '.$theme_append;	           
	           $template_name_appended="$belongs_to_theme $template_name_retrieved";
	           $correct_template_list_final[$template_name_appended]=$template_path;           
	
	       } elseif (isset($defaults_name_array[$template_file_name])) {
	           //not included in default WP core page array
	           //Check if included in basic template name array
	 		   $template_name_retrieved=$defaults_name_array[$template_file_name];

	 		   //Append theme name for clarity
	 		   $theme_name=$this->get_theme_name_based_on_path($template_path,$get_custom_theme_info);	 

	 		   //Get correct theme name append
	 		   $theme_append=$this->theme_append_wcviews_name_correctly($theme_name);
			   
	 		   if (empty($theme_append)) {
	 		   	  //Theme name already contains 'theme' word, remove 'theme' from $template_name_retrieved
	 		   	   $template_name_retrieved=str_replace('Theme', '', $template_name_retrieved);	 		   	
	 		   }
	 		   $template_name_appended="$theme_name $template_name_retrieved";
	           $correct_template_list_final[$template_name_appended]=$template_path;          
	
	       } elseif ($template_file_name != 'Use WooCommerce Default Templates') {
	            //No match, dissect the filename
	            
	       		//Append theme name for clarity
	       		$theme_name=$this->get_theme_name_based_on_path($template_path,$get_custom_theme_info);
	       	
	            $dissected_template_file_name=$this->dissect_file_name_to_convert_to_templatename($template_file_name,$theme_name);
	            $dissected_template_file_name= $theme_name.' '.$dissected_template_file_name;
	            $correct_template_list_final[$dissected_template_file_name]=$template_path;
	       } else {			
				$correct_template_list_final['Use WooCommerce Default Templates']='Use WooCommerce Default Templates';
	       }
	
	    }
	    
	    return $correct_template_list_final; 
	
	  }
	
	}
	
	/**
	 * Return 'theme' word if the theme name does not have it.
	 * @access public
	 * @return string
	 */	
	public function theme_append_wcviews_name_correctly($theme_name) {
		
		$theme_name=strtolower($theme_name);
		
		if ((strpos($theme_name, 'theme') !== false)) {
				
			//Found
			$theme_append='';
			
		} else {
			
			//Not found
			$theme_append=__('Theme','woocommerce_views');
		}	

		return $theme_append;
		
	}
	
	public function dissect_file_name_to_convert_to_templatename($template_file_name,$theme_name) {
	
		$exploded_template_file_name=explode(".",$template_file_name);
	
		$is_a_page_template = $this->wcviews_array_find('page', $exploded_template_file_name);
		$is_a_single_template = $this->wcviews_array_find('single', $exploded_template_file_name);
		$is_a_product_template = $this->wcviews_array_find('product', $exploded_template_file_name);
		$is_a_layouts_template= $this->wcviews_array_find('layouts', $exploded_template_file_name);
		$is_a_prod_archive_template= $this->wcviews_array_find('archive-product', $exploded_template_file_name);
	
		//Append word 'Theme' only when its not on the theme name		
		$theme_append=$this->theme_append_wcviews_name_correctly($theme_name);
		
		if ($is_a_page_template !== false) {	        
	        $custom_page_template=$theme_append.' '.__('Custom Page Template','woocommerce_views');
		    return $custom_page_template;
		} elseif ($is_a_single_template !== false) {	
			
			//This is a single template, let's check if this is a product
			if ($is_a_product_template  !== false) {
                //Product!
                //Let's check if this is a Layouts template
                
				if ($is_a_layouts_template  !== false) {
					
				    //Layouts Template!
					$custom_post_template=$theme_append.' '.__('Custom Product Layouts Template','woocommerce_views');
					
				} else {
					//Not a Layouts template!
					$custom_post_template=$theme_append.' '.__('Custom Product Template','woocommerce_views');					
				}
				
			} else {
				//Nope
				$custom_post_template=$theme_append.' '.__('Custom Post Template','woocommerce_views');				
			}
			
			return $custom_post_template;
	    } else {
	    		
	    	if ($is_a_prod_archive_template  !== false) {		
				$custom_template=$theme_append.' '.__('Custom Product Archive Template','woocommerce_views');
	    	} else {
	    		$custom_template=$theme_append.' '.__('Custom Template','woocommerce_views');
	    	}
	    	
			return $custom_template;
	    }
	 
	}
	
	public function check_if_php_template_contains_wp_loop($template,$is_using_genesis=false) {
	
		$handle = fopen($template, "r");
		$contents = fread($handle,filesize($template));
		$pieces = explode("\n", $contents);
		$have_post_key = $this->wcviews_array_find('have_posts()', $pieces);
		$the_post_key = $this->wcviews_array_find('the_post()', $pieces);
		$the_loop_key = $this->wcviews_array_find('loop', $pieces);
	
		$the_genesis_key =false;
		
		if ($is_using_genesis) {
		    //Genesis Framework Activated, check if this is single products
			$template_basename= basename($template);
			if ('single-product.php' == $template_basename) {
				$the_genesis_key = $this->wcviews_array_find('genesis()', $pieces);
			}
		}
		fclose($handle);
	
		if ((($have_post_key) && ($the_post_key)) || ($the_loop_key) || ($the_genesis_key)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function wcviews_array_find($needle, $haystack, $search_keys = false) {
		if(!is_array($haystack)) return false;
		foreach($haystack as $key=>$value) {
			$what = ($search_keys) ? $key : $value;
			if(strpos($what, $needle)!==false) return $key;
		}
		return false;
	}
	
	/**
	 * Adds admin notice.
	 */
	public function wcviews_help_admin_notice(){
		global $pagenow;
		
		/** Let's show this notice only there are WooCommerce products exist AND all required plugins are set! */
		
		//Check if we have any WooCommmerce products here
		$wc_products_available=$this-> wc_views_get_all_product_ids_clean();
		
		//Check if required plugins are settled
		$required_plugins=$this->check_missing_plugins_for_woocommerce_views();
		
		if ((!(empty($wc_products_available))) && (empty($required_plugins))) {
			//In this case, there are products available and all required plugins are there. OK proceed.
			if ( $pagenow == 'plugins.php' ) {
	    	    //Show this only in plugins page
				if(!get_option('dismiss_wcviews_notice')){
	 				
	     	       //Show this notice if products exists and not using embedded Types/Views and NOT a fresh installation
					if ((!defined('WPVDEMO_VERSION'))){
						//Products exists
						//Admin URL to plugins page
						$admin_url_wcviews=admin_url().'admin.php?page=wpv_wc_views&update_needed=true';
	?>
<div id="message" class="updated message fade"
	style="clear: both; margin-top: 5px;">
	<p><?php _e('WooCommerce Views needs to scan your products once and create calculated fields for Views filters.','woocommerce_views');?> <a
			href="<?php echo $admin_url_wcviews;?>"><strong><?php _e('Run this scan now','woocommerce_views');?></strong></a>
	</p>
</div>
<?php	
					    //Show this message only once		    				
		
					} 
					update_option('dismiss_wcviews_notice', 'yes');
				}
	   		}
		}
	}
	
	//Reset dismiss_wcviews_notice option after deactivation
	public function wcviews_request_to_reset_field_option() {
	  delete_option('dismiss_wcviews_notice');
	}
	/**
	 * Adds question mark icon
	 * @return <type>
	 */
	public function add_media_button($output){
		// avoid duplicated question mark icons (post-new.php)
		$pos = strpos($output, "Insert Types Shortcode");
		
		if($pos == false && !(isset($_GET['post_type']) && $_GET['post_type'] == 'view')){
			$output .= '<ul class="editor_addon_wrapper"><li><img src="'. plugins_url() . '/' . basename(dirname(__FILE__)) . "/res/img/question-mark-icon.png" .'"><ul class="editor_addon_dropdown"><li><div class="title">Learn how to use these Views</div><div class="close">&nbsp;</div></li><li><div>These Views let you insert product sliders, grids and tables to your content. <br /><br /><a href="http://wp-types.com/documentation/views-inside/woocommerce-views/" target="_blank" style="text-decoration: underline; font-weight: bold; color: blue;">Learn how to use these Views</a></div></li></ul></li></ul>';
		}
	
		return $output;
	}
	
	/**
	 * Adds "OLD" CSS and Custom JS for Views
	 */
	public function additional_css_js() {
	
	//Everything about this is only needed in the WC Views settings page, so load only on this page
	
		$screen_output_wc_views= get_current_screen();
		$screen_output_id= $screen_output_wc_views->id;
	
		if ('views_page_wpv_wc_views' == $screen_output_id) {
			
			//Tooltips
			$font_awesome = plugins_url() . '/' . basename(dirname(__FILE__)) . '/res/css/font-awesome/css/font-awesome.min.css';
			wp_enqueue_style('wcviews-fontawesome', $font_awesome,array(),WC_VIEWS_VERSION);
			
			//Main style
			$stylesheet = plugins_url() . '/' . basename(dirname(__FILE__)) . '/res/css/wcviews-style.css';	
			wp_enqueue_style('wcviews-style', $stylesheet,array('wcviews-fontawesome'),WC_VIEWS_VERSION);
			wp_enqueue_script('jquery');			

		}	
	}
	
	//
	//
	//
	//
	//
	//
	// Merged with other plugin
	/*Not anymore used starting version 2.0, public function remains for backward compatibility*/
	public function wpv_woo_add_to_cart($atts) {
	
		_deprecated_function( 'wpv-wooaddcart', '2.5.1','wpv-woo-buy-options' );
	}
	
	/**Emerson: NEW VERSION
	[wpv-woo-buy-or-select]
	Description: Displays 'add to cart' or 'select' button in product listings.
	Will work only in product listing or main shop page.
	
	Attributes/Parameters:
	
	add_to_cart_text = Set the text in the simple product button if desired.
	link_to_product_text = Set the text in the variation product button if desired.
	
	Example using the two attributes: 
	
	[wpv-woo-buy-or-select add_to_cart_text="Buy this now" link_to_product_text="Product options"]
	
	Defaults to WooCommerce text.
	**/
	public function add_to_cart_buy_or_select_closures($argument_one=null,$argument_two=null) {
	
	    $is_using_revised_wc= $this->wcviews_using_woocommerce_two_point_one_above();
	    
	    if ($is_using_revised_wc) {
	    	//Check product type
	    	$product_type_passed=$argument_two->product_type;
	    	if ($product_type_passed=='simple') {
	
				global $add_to_cart_text_product_listing_translated;
				return $add_to_cart_text_product_listing_translated;
				
			} else {
		
	    	    return $argument_one;
	    	}
	    } else {
	       //Old WC
			global $add_to_cart_text_product_listing_translated;
			return $add_to_cart_text_product_listing_translated;       
	        
	    }
	
	}
	
	public function add_to_cart_buy_or_select_closures_listing($argument_one=null,$argument_two=null) {
	
		$is_using_revised_wc= $this->wcviews_using_woocommerce_two_point_one_above();
		
		if ($is_using_revised_wc) {
			//Check product type
			$product_type_passed=$argument_two->product_type;
			if ($product_type_passed=='variable') {
	
				global $link_product_listing_translated;
				return $link_product_listing_translated;
	
	        } else {
	            return $argument_one;
	
	        }
	
	    } else {
	      //Old WC
		  global $link_product_listing_translated;
		  return $link_product_listing_translated;
		  
	    }
	}
	
	public function wpv_woo_buy_or_select_func($atts) {	
		
		/*Add to cart in loops	  
		 */
	
		global $post, $wpdb, $woocommerce;
		
		if ( 'product' == $post->post_type ) {        
	       
	        //Run only on page with products       
			
			$product =$this->wcviews_setup_product_data($post);
			
			if (isset($atts['add_to_cart_text'])) {
	            //User is setting add to cart text customized	
	             if (!(empty($atts['add_to_cart_text']))) {
					$add_to_cart_text_product_listing=trim($atts['add_to_cart_text']);
					
					//START support for string translation
					if (function_exists('icl_register_string')) {
						//Register add to cart text product listing for translation
						icl_register_string('woocommerce_views', 'add_to_cart_text',$add_to_cart_text_product_listing);
					}
					global $add_to_cart_text_product_listing_translated;  
					if (!function_exists('icl_t')) {
						//String translation plugin not available use original text
						$add_to_cart_text_product_listing_translated=$add_to_cart_text_product_listing;
						 
					} else {
						//String translation plugin available return translation
						$add_to_cart_text_product_listing_translated=icl_t('woocommerce_views', 'add_to_cart_text',$add_to_cart_text_product_listing);
					}				
					
					$is_using_revised_wc_simple=$this->wcviews_using_woocommerce_two_point_one_above();
					
					if ($is_using_revised_wc_simple) {
					
						//Updated WC
						add_filter('woocommerce_product_add_to_cart_text',array(&$this,'add_to_cart_buy_or_select_closures'),10,2);
							
					} else {
					
						//Old WC
						add_filter('add_to_cart_text', array(&$this,'add_to_cart_buy_or_select_closures'));
							
					}				
					
			
				}
	        }
	        
	        if (isset($atts['link_to_product_text'])) {
	        	//User is setting link to product text customized
	 		   if (!(empty($atts['link_to_product_text']))) {       
	    		$link_product_listing=trim($atts['link_to_product_text']);
	
	    		//START support for string translation
	    		if (function_exists('icl_register_string')) {
	    			//Register add to cart text product listing for translation
	    			icl_register_string('woocommerce_views', 'link_to_product_text',$link_product_listing);
	    		}
	    	    global $link_product_listing_translated;
	    		if (!function_exists('icl_t')) {
	    			//String translation plugin not available use original text
	    			$link_product_listing_translated=$link_product_listing;
	    		 
	    		} else {
	    			//String translation plugin available return translation
	    			$link_product_listing_translated=icl_t('woocommerce_views', 'link_to_product_text',$link_product_listing);
	    		}
	    		
	    		//END support for string translation        
	            $is_using_revised_wc=$this->wcviews_using_woocommerce_two_point_one_above();
	            
	            if ($is_using_revised_wc) {
	        	
					add_filter('woocommerce_product_add_to_cart_text',array(&$this,'add_to_cart_buy_or_select_closures_listing'),10,2);
	            
				} else {
	
					add_filter('variable_add_to_cart_text',array(&$this,'add_to_cart_buy_or_select_closures_listing'));
	
	            }
	    		
	
	          }
	        
	        }
	        
	        //Let's check the rendering template based on quantity field parameter
	        //https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/193031409/comments
	        if (isset($atts['show_quantity_in_button'])) {
	        	$show_quantity=	$atts['show_quantity_in_button'];
	        	$show_quantity =strtolower($show_quantity);
	        		
	        	if ('yes' == $show_quantity) {
	        		//User wants to display quantities next to add to cart button in Loops	        		
	        		add_filter( 'wc_get_template', array(&$this,'custom_add_to_cart_template_with_qty'),15,5);	
	        		        
	        	} else {
	        		
	        		remove_filter( 'wc_get_template', array(&$this,'custom_add_to_cart_template_with_qty'),15,5);
	        		
	        	}
	        } else {

	        	remove_filter( 'wc_get_template', array(&$this,'custom_add_to_cart_template_with_qty'),15,5);
	        	
	        }
	        
	        
			if (isset($product)) {
				ob_start();	
				if (isset($atts['show_variation_options'])) {
				   //Variation option is set
				   $show_variation_options=trim($atts['show_variation_options']);
				   $show_variation_options=strtolower($show_variation_options);
				   if ('yes' == $show_variation_options) {
				   	//User wants to display variation options on listing pages
					   	if('variable' == $product->product_type  ) {
					   		//This is a variable product, display.
					   		do_action( 'woocommerce_variable_add_to_cart');
					   	} else {
	                        //Not variable product, ignore it just display the usual thing
					   		woocommerce_template_loop_add_to_cart();
					   	}
				   } else {
				   	 	//Here user sets a different value for show variation options, its not 'yes', so just display the usual thing.
				   		woocommerce_template_loop_add_to_cart();
				   }
				} else {
					//Variation option is not yet, just display normally	
					woocommerce_template_loop_add_to_cart();
				}
					
				return ob_get_clean();
			} else {
	             return '';
	        }  	
		}
	}
	
	/**
	 * Use custom add to cart listing button template for displaying quantities when a user needs it.
	 * Template comes from WooCommerce: http://docs.woothemes.com/document/override-loop-template-and-show-quantities-next-to-add-to-cart-buttons/
	 * @access public
	 * @return string
	 */	
	
	public function custom_add_to_cart_template_with_qty($located, $template_name, $args, $template_path, $default_path ) {
		
		//Ensure we are filtering correctly..
		if ('loop/add-to-cart.php' == $template_name) {
			
			global $new_wc_codes;
			$new_wc_codes= $this->wcviews_using_woocommerce_two_point_one_above();
						
			//Yes, we are filtering the add to cart loop template
			//Define new $located
			$located=WOOCOMMERCE_VIEWS_PLUGIN_PATH.DIRECTORY_SEPARATOR.'custom_shortcode_templates'.DIRECTORY_SEPARATOR.'add_to_cart_with_quantity.php';
			
		}
		
		return $located;
		
		
	}
	
	/**Emerson: NEW VERSION
	[wpv-woo-product-price]
	Description: Displays the product price in product listing and single product pages.
	**/
	
	public function wpv_woo_product_price_func($atts) {	
		 
	   global $post,$woocommerce;    
	      
	   $product =$this->wcviews_setup_product_data($post);
	   if (isset($product)) {
	
	   $product_price=$product->get_price_html();
	   
	   /*https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/177801662/comments */
	   /*Output the p class='price' wrapper as part of the price shortcode*/
	   /*Emerson: Added a filter, allowing user to override if needed*/
	   
	   $wrapper_start= apply_filters('wc_views_price_start_wrapper','<p class="price">');
	   $wrapper_end= apply_filters('wc_views_price_end_wrapper','</p>');
	   
	   //Now with wrapper
	   $product_price_with_wrapper = $wrapper_start.$product_price.$wrapper_end;
	   return $product_price_with_wrapper;   
	   }
	
	}
	
	//
	/**Emerson: NEW VERSION
	 [wpv-woo-product-image]
	Description: Displays the product image, which starts with the featured image and changes to the variation image.
	
	$atts: size
	Options:
	
	WordPress image sizes (configured at Settings --> Media --> Image sizes):
	
	thumbnail = Wordpress image thumbnail size e.g. 150x150
	medium    = Wordpress image medium size e.g. 300 x 300
	large = Wordpress full image size e.g. 1024 x 1024
	
	WooCommerce specific image sizes (configured at WooCommerce --> Settings --> Catalog --> Image Options)
	shop_single = single product page size equivalent to medium ("Single Product Image")
	shop_catalog= smaller than thumbnail images ("Catalog Images").
	shop_thumbnail =similar to Wordpress thumbnail size ("Product Thumbnails").
	
	Example usage:
	[wpv-woo-product-image size="thumbnail"]
	[wpv-woo-product-image size="shop_single"]
	[wpv-woo-product-image size="medium"]
	
	Defaults to shop_single
	**/
	
	public function wcviews_set_image_size_closures() {
		global $attribute_image_size;	
	
		return $attribute_image_size;
	}
	
	public function wpv_woo_product_image_func($atts) {    
	    	
	    	if ((isset($atts)) && (!(empty($atts)))) {
	    		
	            //Process size attributes
	            if (isset($atts['size'])) {  		
	                    global $attribute_image_size;
	                    $attribute_image_size=$atts['size'];		
						add_filter('single_product_large_thumbnail_size',array(&$this,'wcviews_set_image_size_closures'));
	            }
	                        
	            //Filter for raw image output, not image link
	            if (isset($atts['output'])) {
	            	if ($atts['output']=='img_tag') {
	            		add_filter('woocommerce_single_product_image_html',array(&$this,'show_raw_image_html_wc_views'),10,2);            
	            	} elseif ($atts['output']=='raw') {
						add_filter('woocommerce_single_product_image_html',array(&$this,'show_raw_image_url_wc_views'),20,2);
	                }   
	            }

	            //Filter for display of galleries in listings
	            if (isset($atts['gallery_on_listings'])) {
	            	//Retrieved value
	            	$gallery_on_listings= $atts['gallery_on_listings'];
	            	if (!(empty($gallery_on_listings))) {	            		
	            		$gallery_on_listings = strtolower($gallery_on_listings);
	            		if ('yes' ==$gallery_on_listings) {
	            			remove_filter('woocommerce_product_gallery_attachment_ids',array(&$this,'remove_gallery_on_main_image_at_listings'),20,2);
	            		}
	            	}
	            }	            
	   		
			}
	
		//Reordered
		ob_start();	
		global $post,$woocommerce;	
		$product =$this->wcviews_setup_product_data($post);
		
		//Fix placeholder image size for those without featured image set
		if (!(has_post_thumbnail())) {
			add_filter('woocommerce_single_product_image_html',array(&$this,'adjust_wc_views_image_placeholder'),10,2);		
		} else {
			remove_filter('woocommerce_single_product_image_html',array(&$this,'adjust_wc_views_image_placeholder'),10,2);
	    }
		
		if (isset($product)) {
			woocommerce_show_product_images();
			$image_content = ob_get_contents();
			//Image processing to remove Woocommerce <div> tags around the image HTML if user wants to output img_tag only or raw URL
			if (isset($atts['output'])) {
	           if (($atts['output']=='img_tag') || ($atts['output']=='raw')) {
					$image_content=trim(strip_tags($image_content, '<img>'));
	            }
	        }
			ob_end_clean();
		} else {
			$image_content = ob_get_contents();
			ob_end_clean();
	    }    
	
		return $image_content;	
		
	}
	
	/**
	 * Outputs the default WooCommerce on-sale badge icon appended to WooCommerce product image.
	 * Tested to work on single product and WooCommerce product listing pages (including shop page).
	 * IMPORTANT: For this to work , it should be placed right directly 'before' the product image shortcode:
	 * 
	 * @access public
	 * @return void
	 */
		
	public function wpv_woo_onsale_func($atts) {
	
		global $post,$woocommerce; 
		
		$product =$this->wcviews_setup_product_data($post);	
	
		if (isset($product)) {
		
	    	//Start span wrapper
	    	$start_span_wrapper='<span class="onsale">';
	    	$on_sale_text=apply_filters('wpv_woo_onsale_text_display_override',__('Sale!','woocommerce_views'));
	        $end_span_wrapper='</span>';
	        $on_sale_badge_html=$start_span_wrapper.$on_sale_text.$end_span_wrapper;
	        
	        //Trapped on-sale products
	        //Check if product is on-sale
	        
	        $onsale_status_check=$this->woo_product_on_sale();
	        
	        if ($onsale_status_check) {
	        
	        	return $on_sale_badge_html;
	        
	        } else {
	
	            return '';
	        }         
		}
	}
	
	public function adjust_wc_views_image_placeholder($imagehtml,$postid) {
	
		//Get user image size
		$user_image_size_set=apply_filters( 'single_product_large_thumbnail_size', 'shop_single' );
		
		//Get available image sizes	
		$image_sizes_available=$this->wc_views_list_image_sizes();
		
		//Get image size for user settings
		if (isset($image_sizes_available[$user_image_size_set])) {
	       $image_dimensions_for_place_holder=$image_sizes_available[$user_image_size_set];
	
	    } else {
	        //Default to thumbnail
			$image_dimensions_for_place_holder=array(0=>'150',1=>'150');
	    }   
	    $placeholder_width=$image_dimensions_for_place_holder[0];
	    $placeholder_height=$image_dimensions_for_place_holder[1];
	    $image_src_source=simplexml_load_string($imagehtml);
	    $image_src_source_url= (string) $image_src_source->attributes()->src;
	    
	    //New in version 2.5.1, ensure placeholder width and height is enforced.
	    //Use style="width:[$placeholder_width]px;height:[$placeholder_height]px;"
	    
	    /** In pixels */
	    //Set responsive width   
	    $placeholder_width_pixels= '100%';
	    $placeholder_height_pixels= $placeholder_height.'px';	    
	    
	    $output_image_placeholder_html='<img src="'.$image_src_source_url.'" alt="Placeholder" style="width:'.$placeholder_width_pixels.';height:'.$placeholder_height_pixels.'" />';
	    
	    return $output_image_placeholder_html;
		
	}
	
	public function wc_views_list_image_sizes(){
		global $_wp_additional_image_sizes;
		$sizes = array();
		foreach( get_intermediate_image_sizes() as $s ){
			$sizes[ $s ] = array( 0, 0 );
			if( in_array( $s, array( 'thumbnail', 'medium', 'large' ) ) ){
				$sizes[ $s ][0] = get_option( $s . '_size_w' );
				$sizes[ $s ][1] = get_option( $s . '_size_h' );
			}else{
				if( isset( $_wp_additional_image_sizes ) && isset( $_wp_additional_image_sizes[ $s ] ) )
					$sizes[ $s ] = array( $_wp_additional_image_sizes[ $s ]['width'], $_wp_additional_image_sizes[ $s ]['height'], );
			}
		}
	
		return $sizes;
	}
	
	public function show_raw_image_html_wc_views($imagehtml,$id) {
	    //Convert image link to raw image src output
		preg_match_all('#<img\b[^>]*>#', $imagehtml, $match);
		$img_tag_html = implode("\n", $match[0]);
		$img_tag_html_array=explode("\n",$img_tag_html);
		if (isset($img_tag_html_array[0])) {
			$imagehtml=$img_tag_html_array[0];
		}
		
		//Return raw output
		return $imagehtml;	
	
	}
	public function show_raw_image_url_wc_views($imagehtml,$id) {
		preg_match_all('#<img\b[^>]*>#', $imagehtml, $match);
		$img_tag_html = implode("\n", $match[0]);
		$img_tag_html_array=explode("\n",$img_tag_html);
		if (isset($img_tag_html_array[0])) {
			$imagehtml=$img_tag_html_array[0];
		}
	
		$image_src_source=simplexml_load_string($imagehtml);
		$image_src_source_url= (string) $image_src_source->attributes()->src;	
		return $image_src_source_url; 
	}
	//
	
	/**Emerson: NEW VERSION
	[wpv-woo-buy-options]
	Description: Displays 'add to cart' or 'select options' box for single product pages.
	Attributes: add_to_cart_text
	**/
	public function single_add_to_cart_text_closure_func() {
		global $add_to_cart_text_product_page_translated;
		return $add_to_cart_text_product_page_translated;
	}
	
	public function wpv_woo_buy_options_func($atts) {
	
	global $post, $wpdb, $woocommerce;
		
		if ( 'product' == $post->post_type ) {        
	       
	        //Run only on single product page
	        if (is_product()) {						
				$product =$this->wcviews_setup_product_data($post);
				
				if (isset($atts['add_to_cart_text'])) {
	              if (!(empty($atts['add_to_cart_text']))) {
					//User is setting add to cart text customized
				
					$add_to_cart_text_product_page=trim($atts['add_to_cart_text']);
				
					//START support for string translation
					if (function_exists('icl_register_string')) {
						//Register add to cart text product listing for translation
						icl_register_string('woocommerce_views', 'product_add_to_cart_text',$add_to_cart_text_product_page);
					}
					
					global $add_to_cart_text_product_page_translated;
						
					if (!function_exists('icl_t')) {
						//String translation plugin not available use original text
						$add_to_cart_text_product_page_translated=$add_to_cart_text_product_page;
				
					} else {
						//String translation plugin available return translation
						$add_to_cart_text_product_page_translated=icl_t('woocommerce_views', 'product_add_to_cart_text',$add_to_cart_text_product_page);
					}			
				
					$using_revised_woocommerce=$this->wcviews_using_woocommerce_two_point_one_above();
					
					if ($using_revised_woocommerce) {
						
						add_filter('woocommerce_product_single_add_to_cart_text',array(&$this,'single_add_to_cart_text_closure_func'));
	
					} else {
	
						add_filter('single_add_to_cart_text',array(&$this,'single_add_to_cart_text_closure_func'));
					
					}
				  }
				}
					
				ob_start();
			
				if ('simple' ==$product->product_type) {
								
					do_action( 'woocommerce_simple_add_to_cart');
				
				} elseif ('variable' ==$product->product_type) {
	 				    
					do_action( 'woocommerce_variable_add_to_cart'); 
					
				} elseif ('grouped' == $product->product_type) {
					
					do_action( 'woocommerce_grouped_add_to_cart');
					
				} elseif ('external' == $product->product_type) {
					
					do_action( 'woocommerce_external_add_to_cart');
					
				} else {
					
				   /** https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/195444907/comments */
				   /** Let's handle any peculiar WooCommerce product post types not covered above */
				   /** First let's double check if $product->product_type exists */
					
				   if (isset($product->product_type)) {
				   	
				   	    //product_type is set
				   	    $product_type_passed= $product->product_type;
				   	    
				   	    if (!(empty($product_type_passed))) {
				   	    	
				   	        //Has sensible value, let's call 'woocommerce_template_single_add_to_cart' core function to display add to cart
				   	    	if ( function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
								
								//Function exist, call!
				   	    		woocommerce_template_single_add_to_cart();

				   	    	}				   	    	
				   	    }				   	
				   }
					
				}
				
				return ob_get_clean();
		  	}
		} 
	}
	
	/**Emerson: NEW VERSION
	[wpv-add-to-cart-message]
	Description: Displays add to cart success message and link to cart for product variation
	Or you can add the hook directly to the theme template
	
	do_action( 'woocommerce_before_single_product' );
	
	preferably after get_header();
	**/
	
	public function wpv_show_add_cart_success_func($atts) {
		global $post, $wpdb, $woocommerce;
		
		$check_if_using_revised_wc=$this->wcviews_using_woocommerce_two_point_one_above();
		
		if (!($check_if_using_revised_wc)) {
			if (( isset($woocommerce->messages) ) || (isset($woocommerce->errors))) {		   	
	  
	    		 $html_result=$this->wcviews_add_to_cart_success_html(); 
	    		 return $html_result;
	    	}
	    } else {
	           //Using revised WC
	           
	           $cart_contents=$woocommerce->cart;
	           $cart_contents_array=$cart_contents->cart_contents;           
	           
	           if (!(empty($cart_contents_array))) {
	
					$html_result=$this->wcviews_add_to_cart_success_html();
	                return $html_result;
	           }
	   }  
	
	}
	
	public function wcviews_add_to_cart_success_html() {
	
		$check_if_using_revised_wc=$this->wcviews_using_woocommerce_two_point_one_above();
		
		//Has message defined
		//Can be reordered anywhere
		
		ob_start();
	 
		if (is_product()) {
			do_action( 'woocommerce_before_single_product' );
		} else {
			
			//https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193583262/comments#comment_303204369
			if ($check_if_using_revised_wc) {

				 //Using revised WC
				 //Use wc_print_notices()
				wc_print_notices();
				
			} else {
				//Old WC, backward compatibility
				woocommerce_show_messages();
			}			
		}
		$add_to_cart_success_content = ob_get_contents();
		ob_end_clean();
	
		return $add_to_cart_success_content;
	
	}
	
	/**Emerson: NEW VERSION
	Description: woo_product_on_sale() - This public function returns true if the product is on sale
	**/
	public function woo_product_on_sale() {
	
	global $post, $woocommerce;
	
	if ((isset($woocommerce)) && (isset($post))) {
		
		$product =$this->wcviews_setup_product_data($post);	
		
		if (isset($product)) {
			if ($product->is_on_sale()) {
		
				return TRUE;
		
			} else {
		
				return FALSE;
		
			}
		}
	}
	}
	
	/**Emerson: NEW VERSION
	 Description: woo_product_in_stock() - This public function returns true if the product is on stock
	**/
	
	public function woo_product_in_stock() {
		global $post;
		
		if (isset($post->ID)) {
			$post_id = $post->ID;
			$stock_status = get_post_meta($post_id, '_stock_status',true);
			
			if ($stock_status== 'outofstock') {
		    
	 	     return FALSE;
	 	     
	 	   } elseif ($stock_status== 'instock') {
		
		      return TRUE;
		      
		    }
	    }
	}
	
	/**Emerson: NEW VERSION
	 Description: Allow user to set the PHP template for single products from the plugin admin
	**/
	
	public function woocommerce_views_activate_template_redirect()
	{			
		
		//This affects the front end
		
		global $woocommerce;
		if (is_object($woocommerce)) {
			//WooCommerce plugin activated
	    	if (is_product()) {
	    		//Single Product page!
	    		//Get template settings
	    		
	    		$get_template_wc_template=get_option('woocommerce_views_theme_template_file');
	    		
	    		if ((is_array($get_template_wc_template)) && (!(empty($get_template_wc_template)))) {
	    		
	    			$live_active_template=get_stylesheet();    	
	    			$template_name_for_redirect=key($get_template_wc_template);
	    			$template_path_for_redirect=$get_template_wc_template[$template_name_for_redirect];	    			
	    			
	    			//https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/193344321/comments#303054421
	    			//See if we can merge the WCV single-product templates with and without Layouts
	    			
	    			if ((strpos($template_path_for_redirect, 'single-product-layouts.php') !== false)) {
	    				
	    				//Using Layouts template, deprecated	    					
	    				$single_product_php_template_check=$this->wc_views_check_if_single_product_template_exists();
	    				
	    				if (file_exists($single_product_php_template_check)) {		

	    					//Ensure this template is updated on database
	    					$canonical_product_template[$template_name_for_redirect] = $single_product_php_template_check;
	    					update_option('woocommerce_views_theme_template_file',$canonical_product_template);
	    					
	    					//use canonical single-product.php template
	    					$template_path_for_redirect=$single_product_php_template_check;
	    				}	    					
	    			}

	    			    //Make sure this template change makes sense
	      	    	  if ($live_active_template==$template_name_for_redirect) {      
			
	      	    	  	//Template settings exists, but don't do anything unless specified
	      	     	 	if (!($template_path_for_redirect=='Use WooCommerce Default Templates')) {
			
	       	     		    //Template file selected, load it
	      	     	 	    if (file_exists($template_path_for_redirect)) {
	      		 	     	    include($template_path_for_redirect);
	      			      	    exit();
	            		    }
	            		}
	            	   }
	        	}	
			}
		}
		
	}
	/**
	 * WooCommerce hooks to WordPress the_post
	 * add_action( 'the_post', 'wc_setup_product_data' );
	 * When the_post is called, put product data into a global.
	 * This hook is added in woocommerce/includes/wc-template-functions.php
	 *
	 * Before calling WooCommerce core functions to render front end output of WooCommerce Views shortcodes
	 * Let's make sure that the global $product is set up correctly to avoid any fatal errors associated with this.
	 *
	 * @access public
	 * @return void
	 */
	
	public function set_wc_views_products($post) {
	
		//Let's define the globals for WooCommerce
		global $woocommerce,$product;
	
		//Let's proceed only if post and WooCommerce object is set
		if ((is_object($post)) && (is_object($woocommerce))) {
				
			if (function_exists('wc_setup_product_data')) {
				//wp_setup_product_data function exists
				if (!(is_object($product))) {
	
					//Product is not object, its because the the_post hook is not called (in cases where Layouts are used)
					//Setup product data
					wc_setup_product_data( $post );
				}
			}
		}
	}
		
	/**Emerson: NEW VERSION
	[wpv-woo-display-tabs]
	Description: Displays additional information and reviews tab.
	For best results, you might want to disable comment section in products pages in your theme
	so it will be replaced with this shortcode
	This will replace the comment section for WooCommerce single product pages
	**/
	
	public function wpv_woo_display_tabs_func() {
	
		global $woocommerce;
		if (is_object($woocommerce)) {
			if (is_product()) {
		
				global $woocommerce, $WPV_templates,$post;
		
				//Check for empty WooCommerce product content, if empty.
				//Apply the removal of filter for the_content only if content is set
		
				if (isset($post->post_content)) {
					$check_product_has_content=$post->post_content;
					if (!(empty($check_product_has_content))) {
		
						//Has content, Remove this filter, run only once -prevent endless loop due to WP core apply_filters on the_content hook
						remove_filter('the_content', array($WPV_templates, 'the_content'), 1, 1);
						
						//https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/193776982/comments
						//User adds the tab shortcode itself inside the Edit products in WC, this can cause infinite loop
						//Lets check if this content has the tab shortcodes itself and handle this.
						$checked_content=$post->post_content;
						if ((strpos($checked_content, '[wpv-woo-display-tabs]') !== false)) {
							
							  //Has instance of tab shortcode							 
							 remove_shortcode('wpv-woo-display-tabs');
						}
					}
				}
	
				ob_start();
				
				//Ensure $product is set
				$this->set_wc_views_products($post);
				
				//External WC core function call
				woocommerce_output_product_data_tabs();
				
				$version_quick_check=$this->wcviews_using_woocommerce_two_point_one_above();
				if ($version_quick_check) {
	    	         //WC 2.1+
	    	         add_filter('comments_template',array(&$this,'wc_views_comments_template_loader'),999);
	    	    } elseif (!($version_quick_check)) {
	    	        //Old WC
					remove_filter( 'comments_template', array( $woocommerce, 'comments_template_loader' ) );
				}
				$content = ob_get_contents();
				ob_end_clean();
				add_filter('the_content', array($WPV_templates, 'the_content'), 1, 1);
		
				return $content;
			}
		}
	}
	
	public function wc_views_comments_template_loader($template) {
	
		if (isset($template)) {
	
			$basefile=basename($template);
			if ($basefile=='single-product-reviews.php') {
				//Don't show any redundant comment templates
				return '';
			} else {
				//Return unfiltered
				return $template;
			}
		} else {
			//Return unfiltered
			return $template;
		}
		return '';
	
	}
	
	/*Emerson: NEW VERSION
	public function that runs through all products and calculates computed postmeta from WooCommerce functions
	*/
	public function compute_postmeta_of_products_woocommerce_views() {
	
	//Define custom field names
	$views_woo_price = 'views_woo_price';
	$views_woo_on_sale = 'views_woo_on_sale';
	$views_woo_in_stock = 'views_woo_in_stock';
	
	if (!(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) { 
	
		//Detection when saving and updating a post, not on autosave
		//Updated custom values is on the $_POST
		if ((isset($_POST)) && (!(empty($_POST)))) {
	       
			//Run this hook on WooCommerce edit pages
			if (isset($_POST['post_type'])) {
	
	          if ($_POST['post_type']=='product') {
	
	            /*Handle Quick Edit Mode*/
	            //Check if doing quick edit
	            
	            if (isset($_POST['woocommerce_quick_edit_nonce'])) {
	
	                   //Doing quick edits!
	                   define('WC_VIEWS_DOING_QUICK_EDIT', true);
	                   
	                   //Now lets define product type
	                   if ((empty($_POST['_regular_price'])) && (empty($_POST['_sale_price']))) {
	                       
	                       //This must be a variation
	                       $_POST['product-type']='variable';
	                       
	                   } else {
	
							//This must be a simple product
							$_POST['product-type']='simple';
	                   }                  
	            }
			
	       		//$_POST is set
	       		//https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/188028069/comments
	       		if (isset($_POST['ID'])) {
	            	$post_id_transacted=trim($_POST['ID']);
	       		}
	       		
	       		if (isset($_POST['product-type'])) {
	       			$product_type_transacted= trim($_POST['product-type']);
	       		}       		
	       		if ((isset($post_id_transacted)) && (isset($product_type_transacted))) {
	       			if ($product_type_transacted=='simple') {
	       				//Get the price of simple product
	       				//Check if on sale or not
	       				if (empty($_POST['_sale_price'])) {
	            	       //Not on sale, get regular price
	       					$simple_product_price=trim($_POST['_regular_price']);
	       					$onsale_status=FALSE;
	       					$onsale_status=$this->for_views_null_equals_zero_adjustment($onsale_status);
	       					$on_sale_success= update_post_meta($post_id_transacted,$views_woo_on_sale,$onsale_status);       				
	       				} else {
							//On sale, get sales price
							$simple_product_price=trim($_POST['_sale_price']);
							//Save custom field on sale
							$onsale_status=TRUE;
							$onsale_status=$this->for_views_null_equals_zero_adjustment($onsale_status);
							$on_sale_success= update_post_meta($post_id_transacted,$views_woo_on_sale,$onsale_status);
	            	    }
	       				//Save as custom field of simple product
	       				if ((!(empty($simple_product_price))) && ($simple_product_price != '0')) {
	 	      				$success= update_post_meta($post_id_transacted,$views_woo_price,$simple_product_price);
	       				}
	       				//Save on stock status for simple products
	       				if (isset($_POST['_stock_status'])) {
	            	        if (!(empty($_POST['_stock_status']))) {
	            	            $on_stock_status=trim($_POST['_stock_status']); 
	            	            if ($on_stock_status=='instock') {
	            	               $on_stock_status=TRUE;
	            	               $on_stock_status=$this->for_views_null_equals_zero_adjustment($on_stock_status);
	            	            } elseif ($on_stock_status=='outofstock') {
									$on_stock_status=FALSE;
									$on_stock_status=$this->for_views_null_equals_zero_adjustment($on_stock_status);							
	               	         }
	               	         if (isset($on_stock_status)) {
									$success_on_stock_status= update_post_meta($post_id_transacted,$views_woo_in_stock,$on_stock_status);
								}
	               	     }
	              	  }
	           	 } elseif (($product_type_transacted=='variable') && (isset($_POST['variable_regular_price']))) {
					
						//Variable price is only updated when NOT doing quick edit.
					
						if (!defined('WC_VIEWS_DOING_QUICK_EDIT')) {
					
							//Get the price of simple product
							$variable_product_price=array();
							$variable_product_price=$_POST['variable_regular_price'];
							
							//Find the minimum
							if (!(empty($variable_product_price))) {
	 	                
	 	         	      $minimum_variation_price_set=min($variable_product_price);
	              	  
	 	          	     }
							//Save as custom field of simple product
							if ((!(empty($minimum_variation_price_set))) && ($minimum_variation_price_set !='0')) {
								$success= update_post_meta($post_id_transacted,$views_woo_price,$minimum_variation_price_set);
							}
						
						}
					
						//Save on stock status for variation products
						if (isset($_POST['_stock_status'])) {
							if (!(empty($_POST['_stock_status']))) {
								$on_stock_status=trim($_POST['_stock_status']);
								
								//Doing quick edit mode
								if (defined('WC_VIEWS_DOING_QUICK_EDIT')) {
	                	            
	                	            if ($on_stock_status=='outofstock') {
										$on_stock_status=FALSE;
										$on_stock_status=$this->for_views_null_equals_zero_adjustment($on_stock_status);
									} else {
										$on_stock_status=TRUE;
										$on_stock_status=$this->for_views_null_equals_zero_adjustment($on_stock_status);
	                	            }
	                	            	$success_on_stock_status= update_post_meta($post_id_transacted,$views_woo_in_stock,$on_stock_status);                            
	                	        }
	                        
							if (isset($_POST['variable_stock'])) {
	                           if (is_array($_POST['variable_stock'])) {
	                               $total_stock_qty_variation=array_sum($_POST['variable_stock']);
	                               $variable_stock_quantity_wcviews=trim($_POST['_stock_status']);
	                               if (($on_stock_status=='instock') && ($total_stock_qty_variation > 0)) {
	                               	$on_stock_status=TRUE;
	                               	$on_stock_status=$this->for_views_null_equals_zero_adjustment($on_stock_status);
	                               } elseif ($on_stock_status=='outofstock') {
	                               	$on_stock_status=FALSE;
	                               	$on_stock_status=$this->for_views_null_equals_zero_adjustment($on_stock_status);
	                               } elseif ($total_stock_qty_variation <= 0) {
									$on_stock_status=FALSE;
									$on_stock_status=$this->for_views_null_equals_zero_adjustment($on_stock_status);
	                               }
	                               if (isset($on_stock_status)) {
	                               	$success_on_stock_status= update_post_meta($post_id_transacted,$views_woo_in_stock,$on_stock_status);
	                               }
	                           }
	
	                        }
							}
						}	
				   	//Logic on saving variation product is on_sale
				   	if (isset($_POST['variable_sale_price'])) {
	                	 $variable_sales_price_array=array();
	               	  $variable_sales_price_array=$_POST['variable_sale_price'];
	               	  //Test if sales price exists
	               	  $sum_sales_test=array_sum($variable_sales_price_array);
	               	  if ($sum_sales_test==0) {
	               	     //Product is not on sale
							//Save custom field not on sale
							$onsale_status_variation=FALSE;
							$onsale_status_variation=$this->for_views_null_equals_zero_adjustment($onsale_status_variation);
							$on_sale_success_variation= update_post_meta($post_id_transacted,$views_woo_on_sale,$onsale_status_variation);                    
	                	 } else {
							//Product is on sale
							//Save custom field on sale
							$onsale_status_variation=TRUE;
							$onsale_status_variation=$this->for_views_null_equals_zero_adjustment($onsale_status_variation);
							$on_sale_success_variation= update_post_meta($post_id_transacted,$views_woo_on_sale,$onsale_status_variation);		
		
	                 	}
	
	               	}			
	              }
	            }       		
	       	
	       	  }
	       	
	       	}      
		}
	
	}
	}
	
	/*[wpv_woo_add_to_cart_box] is not anymore used starting version 2.0, public function remains for backward compatibility*/
	public function wpv_woo_add_to_cart_box($atts) {    
	
		_deprecated_function( 'wpv-wooaddcartbox', '2.5.1','wpv-woo-buy-options' );
		
	}
	
	public function wpv_woo_remove_from_cart($atts) {
		_deprecated_function( 'wpv-wooremovecart', '2.5.1' );
	}
	
	public function wpv_woo_cart_url($atts) {
		_deprecated_function( 'wpv-woo-carturl', '2.5.1' );
	}
	
	public function wpv_woo_add_shortcode_in_views_popup($items){
	   /*Old shortcode, functions not removed for backward compatibility*/
	   //$items already contains some shortcodes from previous processing
	   //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/194295655/comments#304646798
	   //Those $items should also be returned by this filter unharmed.
	   
		global $post;
		
		//Let's not add the WooCommerce Views shortcodes in the 'Edit Product'
		//To prevent misuse of these shortcodes.
		//Related to this problem:
		//https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/193776982/comments
		
		$associated_posttypes= $this->wcviews_associated_posttypes;
		if (is_object($post)) {
			
			//Post object defined
			if (isset($post->post_type)) {
				
			  $posttype=$post->post_type;
			  if (in_array($posttype, $associated_posttypes)) {

			  	//Bingo, post type is associated with WCV shortcodes, show in Editor.			  	
			  	//OK here we passed $items so other shortcodes will still show after filtering
			  	//https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/194295655/comments#304646798
			  	//WC Views shortcodes will be added in addition to previous $items
		  	    $items=$this->wpv_woo_add_shortcode_in_views_popup_aux($items);
			  }				
			}			
		} elseif (!(isset($post))) {
			//In cases where $post is not defined, it could be the shortcode insertion inside the Content Template cell in Layouts
			//OK here we passed $items so other shortcodes will still show after filtering
			//WC Views shortcodes will be added in addition to previous $items
			$items=$this->wpv_woo_add_shortcode_in_views_popup_aux($items);
			
		}
		
		return $items;
	}
	
	public function wpv_woo_add_shortcode_in_views_popup_aux($items) {		
		
		$items['WooCommerce']['productbuyorselect'] = array(
				'Add to cart button - product listing pages',
				'wpv-woo-buy-or-select',
				'Basic',
				'wcviews_insert_wpv_woo_buy_or_select(); return false;'
		);
		//[wpv-woo-product-price]
		$items['WooCommerce']['productpricedisplay'] = array(
				'Product price',
				'wpv-woo-product-price',
				'Basic',
				''
		);
		//[wpv-woo-buy-options]
		$items['WooCommerce']['productbuyoptions'] = array(
				'Add to cart button - single product page',
				'wpv-woo-buy-options',
				'Basic',
				'wcviews_insert_wpv_woo_buy_options(); return false;'
		);
		//[wpv-woo-product-image]
		$items['WooCommerce']['productimagewoocommerceviews'] = array(
				'Product image',
				'wpv-woo-product-image',
				'Basic',
				'wcviews_insert_wpv_woo_product_image(); return false;'
		);
		//[wpv-show-add-cart-success]
		$items['WooCommerce']['productaddtocartsuccess'] = array(
				'Add to cart message',
				'wpv-add-to-cart-message',
				'Basic',
				''
		);
		//[wpv-woo-display-tabs]
		$items['WooCommerce']['productwoodisplayingtabs'] = array(
				'Product tabs - single product page',
				'wpv-woo-display-tabs',
				'Basic',
				''
		);
		//[wpv-woo-onsale]
		$items['WooCommerce']['productdisplayonsalebadge'] = array(
				'Onsale badge',
				'wpv-woo-onsale',
				'Basic',
				''
		);
		
		//[wpv-woo-list_attributes]
		$items['WooCommerce']['productlistattributes'] = array(
				'Product attributes',
				'wpv-woo-list_attributes',
				'Basic',
				''
		);
		
		//[wpv-woo-related_products]
		$items['WooCommerce']['productwoocommercerelated'] = array(
				'Related Products',
				'wpv-woo-related_products',
				'Basic',
				''
		);
		
		//[wpv-woo-single-products-rating]
		$items['WooCommerce']['productratingsinglepage'] = array(
				'Product Rating - single product page',
				'wpv-woo-single-products-rating',
				'Basic',
				''
		);
		
		//[wpv-woo-products-rating-listing]
		$items['WooCommerce']['productratinglistingpage'] = array(
				'Product Rating - product listing pages',
				'wpv-woo-products-rating-listing',
				'Basic',
				''
		);
		 
		//[wpv-woo-productcategory-images]
		$items['WooCommerce']['catproductimagewoocommerceviews'] = array(
				'Product Category Image',
				'wpv-woo-productcategory-images',
				'Basic',
				'wcviews_insert_wpv_woo_productcategory_images(); return false;'
		);
		
		//[wpv-woo-show-upsell-items]
		$items['WooCommerce']['productupsellwoocommerceviews'] = array(
				'Product Upsell',
				'wpv-woo-show-upsell-items',
				'Basic',
				''
		);
		
		//[wpv-woo-breadcrumb]
		$items['WooCommerce']['woocommercebreadcrumbdefault'] = array(
				'Breadcrumb',
				'wpv-woo-breadcrumb',
				'Basic',
				''
		);
		//[wpv-woo-product-meta]
		$items['WooCommerce']['woocommerceproductmeta'] = array(
				'Product meta',
				'wpv-woo-product-meta',
				'Basic',
				''
		);
		//[wpv-woo-cart-count]
		$items['WooCommerce']['woocommercecartcount'] = array(
				'Cart Count',
				'wpv-woo-cart-count',
				'Basic',
				''
		);				

		//[wpv-woo-reviews]
		$items['WooCommerce']['woocommercedisplayreviews'] = array(
				'Reviews',
				'wpv-woo-reviews',
				'Basic',
				''
		);		
		return $items;
		
	}
	public function wpv_woo_add_shortcode_in_views_popup_cat($items){
			
		global $post;
		
		//Let's not add the WooCommerce Views shortcodes in the 'Edit Product'
		//To prevent misuse of these shortcodes.
		//Related to this problem:
		//https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/193776982/comments
		
		$associated_posttypes= $this->wcviews_associated_posttypes;
		
		if (is_object($post)) {
				
			//Post object defined
			if (isset($post->post_type)) {
		
				$posttype=$post->post_type;
				if (in_array($posttype,$associated_posttypes)) {
					
					//Bingo, post type is associated with WCV shortcodes, show in Editor.
					//[wpv-woo-productcategory-images]
					$items['WooCommerce']['catproductimagewoocommerceviews'] = array(
							'Product Category Image',
							'wpv-woo-productcategory-images',
							'Basic',
							'wcviews_insert_wpv_woo_productcategory_images(); return false;'
					);
					
				}
			}
		} elseif (!(isset($post))) {
			
			//In cases where $post is not defined, it could be the shortcode insertion inside the Content Template cell in Layouts
			$items['WooCommerce']['catproductimagewoocommerceviews'] = array(
					'Product Category Image',
					'wpv-woo-productcategory-images',
					'Basic',
					'wcviews_insert_wpv_woo_productcategory_images(); return false;'
			);
			
		}

		return $items;
	}	
	
	public function wpv_woo_add_shortcode_in_views_layout_wizard($items){
	
		//Please sync with wpv_woo_add_shortcode_in_views_popup() above.
	
		$modern_toolbox=$this->wc_views_modern_views_toolbox_check();
		
		if (!($modern_toolbox)) {
			
			//[wpv-woo-buy-or-select]
			$items[] = array(
					'Add to cart button - product listing pages',
					'wpv-woo-buy-or-select',
					'WooCommerce',
					'wcviews_insert_wpv_woo_buy_or_select(); return false;'
			);
			//[wpv-woo-product-price]
			$items[] = array(
					'Product price',
					'wpv-woo-product-price',
					'WooCommerce',
					''
			);
			//[wpv-woo-buy-options]
			$items[] = array(
					'Add to cart button - single product page',
					'wpv-woo-buy-options',
					'WooCommerce',
					'wcviews_insert_wpv_woo_buy_options(); return false;'
			);
			//[wpv-woo-product-image]
			$items[] = array(
					'Product image',
					'wpv-woo-product-image',
					'WooCommerce',
					'wcviews_insert_wpv_woo_product_image(); return false;'
			);
			//[wpv-show-add-cart-success]
			$items[] = array(
					'Add to cart message',
					'wpv-add-to-cart-message',
					'WooCommerce',
					''
			);
			//[wpv-woo-display-tabs]
			$items[] = array(
					'Product tabs - single product page',
					'wpv-woo-display-tabs',
					'WooCommerce',
					''
			);
			//[wpv-woo-onsale]
			$items[] = array(
				'Onsale badge',
				'wpv-woo-onsale',
				'WooCommerce',
				''
			);
		
			//[wpv-woo-list_attributes]
			$items[] = array(
				'Product attributes',
				'wpv-woo-list_attributes',
				'WooCommerce',
				''
			);
			
			//[wpv-woo-related_products]
			$items[] = array(
					'Related Products',
					'wpv-woo-related_products',
					'WooCommerce',
					''
			);	
			
			//[wpv-woo-single-products-rating]
			$items[] = array(
					'Product Rating - single product page',
					'wpv-woo-single-products-rating',
					'WooCommerce',
					''
			);	
			
			//[wpv-woo-products-rating-listing]
			$items[] = array(
					'Product Rating - product listing pages',
					'wpv-woo-products-rating-listing',
					'WooCommerce',
					''
			);
			//[wpv-woo-productcategory-images]
			$items[] = array(
					'Product Category Image',
					'wpv-woo-productcategory-images',
					'WooCommerce',
					'wcviews_insert_wpv_woo_productcategory_images; return false;'
			);	
			
			//[wpv-woo-show-upsell-items]
			$items[] = array(
					'Product Upsell',
					'wpv-woo-show-upsell-items',
					'WooCommerce',
					''
			);
			
			//[wpv-woo-breadcrumb]
			$items[] = array(
					'Breadcrumb',
					'wpv-woo-breadcrumb',
					'WooCommerce',
					''
			);
	
			//[wpv-woo-product-meta]
			$items[] = array(
					'Product meta',
					'wpv-woo-product-meta',
					'WooCommerce',
					''
			);	
	
			//[wpv-woo-cart-count]
			$items[] = array(
					'Cart Count',
					'wpv-woo-cart-count',
					'WooCommerce',
					''
			);	
			//[wpv-woo-reviews]	
			$items[] = array(
					'Reviews',
					'wpv-woo-reviews',
					'WooCommerce',
					''
			);
		}
		
		return $items;
	}
	
	//Returns TRUE if using woocommerce default template
	public function wc_views_check_if_using_woocommerce_default_template() {
	
		$the_active_php_template_option_thumbnails=get_option('woocommerce_views_theme_template_file');
		
		if ((is_array($the_active_php_template_option_thumbnails)) && (!(empty($the_active_php_template_option_thumbnails)))) {
			$the_active_php_template_thumbnails=reset($the_active_php_template_option_thumbnails);
			
			if ($the_active_php_template_thumbnails=='Use WooCommerce Default Templates') {
	
	           return TRUE;
	
	        } else {
	
	           return FALSE;
	        }	
		} else {
	
	        //If option does not exist, return TRUE since it make sense that it defaults to WooCommerce Templates
	        return TRUE;
	    }
	
	}
	
	//Warning HTML if using default WooCommerce Templates, cannot set Content Template
	public function wc_views_warning_cannot_set_ct_template_using_default_wc() {
	?>
<div class="wcviews_warning">
	<p><?php _e('You cannot select a Content Template for product - WooCommerce default templates have been selected to display products. You need to assign a page template in the settings.','woocommerce_views');?></p>
</div>
<?php 
	}
	
	//Method to check if single-product.php template exist on theme directory
	public function wc_views_check_if_single_product_template_exists() {
	
		$woocommerce_views_supported_templates= $this->load_correct_template_files_for_editing_wc();
		
		$single_product_template_found=FALSE;
		
		//Loop through the PHP templates array
		if ((is_array($woocommerce_views_supported_templates)) && (!(empty($woocommerce_views_supported_templates)))) {
		
	        foreach ($woocommerce_views_supported_templates as $template_name=>$template_path) {
	
	           $template_file_name= basename($template_path);
	           if ($template_file_name=='single-product.php') {
	                 return $template_path;
	                 break;
	           }
	        }
	    }
	    
	    return $single_product_template_found; 
		
	}
	
	//Filter on adding WC Classes wrapper around Content Template the_content() modification
	
	public function wc_views_prefix_add_wrapper( $content, $template_selected, $id, $kind ) {
	
		global $woocommerce;
	    $views_settings_options=get_option('wpv_options');
	    
	    if (isset($views_settings_options['views_template_for_product'])) {
	    
	        //Retrieve content template ID
	        $product_template_id=$views_settings_options['views_template_for_product'];
	        
	        /** WooCommerce Views 2.5.3 */
	        /** It's possible that user will assign a Content Template to a product on a per product basis not to the entire products */
	       
	    	if (( $kind == 'single-product' ) && (is_object($woocommerce))) {
	         

	    		/** WooCommerce Views 2.5.4 */
	    		/** User sometimes use [wpv-post-body view_template="None"] inside Content Template cell for sites with layouts
	    		 * We don't need to wrap them with these classes since they are wrapped already by the Layouts
	    		 */
	    		 
	    		global $wpddlayout;
	    		$exception=false;
	    		if (is_object($wpddlayout)) {
	    			if (method_exists($wpddlayout,'get_layout_slug_for_post_object')) {
	    				//Site is using Toolset Layouts.
	    				//Let's checked if there is a Layout assigned to this product
	    				$layout_assigned_to_product=$wpddlayout->get_layout_slug_for_post_object($id);
	    				$layout_assigned_to_product=trim($layout_assigned_to_product);
	    				
	    				//Let's checked if this function is attempted to wrap its own content
	    				$product_object= get_post($id);
	    				$content_to_check= $product_object->post_content;
	    				if ((!(empty($layout_assigned_to_product))) && ($content_to_check ==$content )) {
	    				  //Layouts set to this product and we are about to wrap the products own text content
	    				  $exception=true;
	    				}
	    			}
	    		
	    		}
	    		
	    		/** Here we have products loaded that is controlled by WooCommerce */
	    		/** Let's wrapped with its classes */
	    		global $post_classes_wc_added;
	    		
	    		if ((!($post_classes_wc_added)) && (!($exception))) {
	    			//Not yet wrapped and no Layouts assigned
	    			$post_classes_wc_added=TRUE;
	    			$post_classes = get_post_class( 'clearfix', $id );
	    			$content = '<div class="' . implode( ' ', $post_classes ) . '">'. $content . '</div>';
	    		}

			}
		}
	
		return $content;
	
	}
	
	//Filter to override  any content templates set with full plugin version when using default WooCommerce templates
	
	public function wc_views_override_any_content_templates_default_wc($template_selected, $id, $kind) {
	
			$template_selected=0;
			return $template_selected;		
	
	}
	
	//public function to reset automatically to default WooCommerce templates after theme switching activity
	
	public function wc_views_reset_wc_default_after_theme_switching() {
	
	    //Run the method to use WooCommerce default templates   	
	    $is_using_wc_default_template=$this->wc_views_check_if_using_woocommerce_default_template();
	    
	    //Reset this option by deletion
	    delete_option('wc_views_nondefaulttemplate_changed');
	    if (!($is_using_wc_default_template)) {
	    	
	       //Using non-default template,
	    	update_option('wc_views_nondefaulttemplate_changed','yes');    	 
	    	  	
	    } else {
	    	
	    	//Using default WooCommmerce template,
	    	update_option('wc_views_nondefaulttemplate_changed','no');    	
	    }
	    	
		$this->wcviews_save_php_template_settings('Use WooCommerce Default Templates');
	
	}
	
	public function wc_views_after_theme_switched() {
	
		$wc_views_nondefaulttemplate_changed=get_option('wc_views_nondefaulttemplate_changed');
		
		if ($wc_views_nondefaulttemplate_changed) {			
			if ('yes' == $wc_views_nondefaulttemplate_changed) {
				//Inform user that a theme switch occurs and that he needs to set the WooCommerce Views templates again
				add_action('admin_notices', array(&$this,'wcviews_needs_to_update_templates_notice'));
			}
			
			//Done using this option, delete.
			delete_option('wc_views_nondefaulttemplate_changed');
		}
	
	}
	
	/* Show warning that user needs to update templates again*/
	public function wcviews_needs_to_update_templates_notice() {
		?>
<div class="update-nag">
	<p>
		        <?php 
		        $admin_url_wcviews=admin_url().'admin.php?page=wpv_wc_views';
		        $message  = '<p>'.__( 'You have switched to another theme. Single product templates are resetted to WooCommerce defaults.','woocommerce_views').'</p>';
		        $message .= '<p>'.__( 'Please go to','woocommerce_views').' '.'<a href="'.$admin_url_wcviews.'">'.__('WooCommerce Views settings','woocommerce_views').'</a>';
		        $message .= ' '.__('section. And assign another single product page templates if necessary.', 'woocommerce_views').'</p>';
		        ?>
		        <?php 
		        echo $message;
		        ?>
		        </p>
</div>
<?php
	}
	
	/**
	 * Output the list of product attributes in WooCommerce. 
	 * Product attributes are set when editing WooCommerce products in backend. 
	 * Then go to "Product data" -> Attributes. 
	 * Tested to work on single product and WooCommerce shop pages.
	 * @access public
	 * @return void
	 */	
	
	public function wpv_woo_list_attributes_func() {
	
		global $post,$woocommerce;
	
		ob_start();
		
		$product =$this->wcviews_setup_product_data($post);
	
		//Check if $product is set
		if (isset($product)) {
			//Let's checked if product type is set
			if (isset($product->product_type)) {				
				//Let's checked if it contains sensible value
				$product_type=$product->product_type;
				if (!(empty($product_type))) {
				    //Yes product types exist and set
					$product->list_attributes();					
					return ob_get_clean();					
					
				}				
			}
		}
	
	}
	
	//WooCommerce Views setup product data public function based on WooCommerce functions
	//Updated to be compatible with WC version 2.1+ with backward compatibility
	
	public function wcviews_setup_product_data($post) {
	
		if (function_exists('wc_setup_product_data')) {
			//Using WooCommerce Plugin version 2.1+
			$product_information=wc_setup_product_data( $post );
			return $product_information;
	
		} else {
	
			//Probably still using older woocommerce versions
			global $woocommerce;
	
			if (is_object($woocommerce)) {
				$product_information = $woocommerce->setup_product_data( $post );
				return $product_information;
			}
		}
	}
	
	//NEW: Compatibilty public function to check for WooCommerce versions
	//Returns TRUE if using WooCommerce version 2.1.0+
	public function wcviews_using_woocommerce_two_point_one_above() {
	   
	   global $woocommerce;
	   if (is_object($woocommerce)) {
	
	   	$woocommerce_version_running=$woocommerce->version;
	
			if (version_compare($woocommerce_version_running, '2.1.0', '<')) {
	
	            return FALSE;
	
	    	} else {
	
	            return TRUE;
	            
	        }
	   }
	
	}
	
	//NEW: Compatibilty public function to check for WooCommerce versions
	//Returns TRUE if using WooCommerce version 2.2.0+
	public function wc_views_two_point_two_above() {
	
		global $woocommerce;
		if (is_object($woocommerce)) {
	
			$woocommerce_version_running=$woocommerce->version;
	
			if (version_compare($woocommerce_version_running, '2.2.0', '<')) {
	
				return FALSE;
	
			} else {
	
				return TRUE;
	
			}
		}
	
	}
	
	/**
	 * Outputs WooCommerce related products using its own matching algorithm (using product categories and tags).
	 * Tested to work only on Single Product pages. This is not meant to be used on product loops.
	 * @access public
	 * @return void
	 */	
	
	public function wpv_woo_related_products_func() {
		
		global $post,$woocommerce;
		
		ob_start();
	
		//Check if $product is set
		if (is_object($woocommerce)) {
			//WooCommerce plugin activated
			//Get products
			$product =$this->wcviews_setup_product_data($post);
			
			if ((isset($product)) && (is_product())) {
				//Executable only on single product page
				
				//We need to verify if product_type is duly set and exist
				if (isset($product->product_type)) {
					//Set,
					$product_type=$product->product_type;
					
					if (!(empty($product_type))) {
						//Set and exist
						//Simple or variable products
						if (function_exists( 'woocommerce_output_related_products' ) ) {
						
							//Call WooCommerce core public function on oututting related products exists.
							woocommerce_output_related_products();
						
						}
													
						return ob_get_clean();					
						
					}					
				}
			}
		}	
	}	

	/**
	 * Outputs WooCommerce product rating on single product pages.
	 * Tested to work only on Single Product pages. This is not meant to be used on product loops.
	 * @access public
	 * @return void
	 */
		
	public function wpv_woo_single_products_rating_func() {
	
		global $post,$woocommerce;
	
		ob_start();
		
		if (is_object($woocommerce)) {
			//WooCommerce plugin activated
			//Get products
			$product =$this->wcviews_setup_product_data($post);
	
			if ((isset($product)) && (is_product())) {
				
				//Let's check if product_type is set
				if (isset($product->product_type)) {
					
					//Set
					$product_type=$product->product_type;
					if (!(empty($product_type))) {
						//Defined, exist
						//Simple or variable products
						if (function_exists( 'woocommerce_template_single_rating' ) ) {
								
							//Call WooCommerce core public function on outputting single product rating on single product page
							woocommerce_template_single_rating();
								
						}						
						return ob_get_clean();						
					}					
				}
			}
		}
	}
	
	/**
	 * Outputs WooCommerce product rating on product listing and loops.
	 * Tested to work only on product listing and loops. Not meant for product pages.
	 * @access public
	 * @return void
	 */
		
	public function wpv_woo_products_rating_on_listing_func() {
	
		global $post,$woocommerce;
	
		ob_start();
	
		if (is_object($woocommerce)) {
			//WooCommerce plugin activated
			
			//Check if this is a product listing page
			$product_listing_check =$this->wcviews_is_woocommerce_listing();
	
			if ($product_listing_check) {
					
				//Executable only on product listing pages with sensible $products
				$product =$this->wcviews_setup_product_data($post);
				
				if (isset($product)) {
					if (function_exists( 'woocommerce_template_loop_rating' )) {
		
						//Call WooCommerce core public function on outputting product ratings on listing pages
						woocommerce_template_loop_rating();
					}
					
					$listing_rating_output=ob_get_clean();					
					$listing_rating_output=trim($listing_rating_output);					

					return $listing_rating_output;
				}
			}
		}
	}
	
	//Outputs TRUE if on WooCommerce listing pages
	public function wcviews_is_woocommerce_listing() {
		
	  global $woocommerce;	
		
	  $is_wc_listing_page=FALSE;
	  
	  if (is_object($woocommerce)) {
	  	//WooCommerce plugin activated
	  	//Check if this NOT a product page
	  	
	  	if (!(is_product())) {
	  	   //Not a product page
	  	    $is_wc_listing_page=TRUE;   	
	  		
	  	}
	  	
	  }
	  
	  return $is_wc_listing_page;
		
	}
	
	/**
	 * Outputs WooCommerce product category image set in the backend. (Products -> Categories)
	 * Tested to work loops outputting categories.
	 * @access public
	 * @return void
	 */
	
	public function wpv_woo_productcategory_images_func($atts) {
		
		global $woocommerce,$WP_Views;
		$image_content='';
		
		if ((is_object($woocommerce)) && (is_object($WP_Views))) {
		
			//WooCommerce and Views plugin activated
		
			//Get available image sizes
			$image_sizes_available=$this->wc_views_list_image_sizes();
		
	        //Let's checked the $atts passed
	        if (empty($atts)) {
	        	
	        	//No attributes passed , define defaults
	        	$atts=array();
	            $atts['size']='shop_single';
	            $atts['output']='raw';                	
	        	
	        }
	        
	        //Retrieved settings
	        if (isset($atts['size'])) {
	        	
	        	$size=$atts['size'];        	
	        } else {
	           //Not set, use defaults
	        	$size='shop_single';
	        }
	        
	        if (isset($atts['output'])) {
	        	 
	        	$outputformat=$atts['output'];
	        } else {
	            //Not set, use defaults
	            $outputformat='raw';        	
	        }
			
			//Check if this is a WooCommerce product category
			//Get Taxonomy info
			$taxonomydata_passed_by_views=$WP_Views->taxonomy_data;
			
			if (!(empty($taxonomydata_passed_by_views))) {
				//Don't proceed further if $taxonomydata_passed_by_views is empty.
				//Get Term info
				$term_info_tax=$taxonomydata_passed_by_views['term'];
			
				//Get Term ID
				$term_id_tax=$term_info_tax->term_id;
			
				//Get Thumbnail ID assigned to that term ID
				$thumbnail_id = get_woocommerce_term_meta( $term_id_tax, 'thumbnail_id', true );
			
				//Get attachment image
				//Return image content that depends on $output_format
				//$image_content by default is img HTML tag element
				$image_content = wp_get_attachment_image( $thumbnail_id, $size );
			
				if (('raw' == $outputformat) && (!(empty($image_content)))) {
					
					//Don't run this block if $image_content is empty			
					$image_src_source=simplexml_load_string($image_content);
					$image_content= (string) $image_src_source->attributes()->src;
				
				}	
			}	
				
		}
		
		return $image_content;
	}
	
	/**
	 * Outputs items for upsell.
	 * You can configure items for upsell by following this WooCommerce guide.
	 * http://docs.woothemes.com/document/related-products-up-sells-and-cross-sells/
	 * This is tested to work on single-product pages.
	 * @access public
	 * @return void
	 */	
	
	public function wpv_woo_show_upsell_func() {
	
		global $post,$woocommerce;
	
		ob_start();
	
		if (is_object($woocommerce)) {
			//WooCommerce plugin activated
			//Get products
			$product =$this->wcviews_setup_product_data($post);
	
			if ((isset($product)) && (is_product())) {
					
				//Executable only on single product page
				//Check if product_type is set
				if (isset($product->product_type)) {
					
					//Set check if defined
					$product_type=$product->product_type;
					
					if (!(empty($product_type))) {
						
						//Defined
						//Simple or variable products
						if (function_exists( 'woocommerce_upsell_display' ) ) {
						
							//Call WooCommerce core public function on outputting upsell items
							woocommerce_upsell_display();
						
						}						
						return ob_get_clean();						
					}
					
				}
			}
		}
	}
	
	/**
	 * Outputs the default WooCommerce breadcrumb.
	 * This is meant for single-product pages only.
	 * @access public
	 * @return void
	 */	
	public function wpv_woo_breadcrumb_func() {
		
		global $post,$woocommerce;
		
		ob_start();
		
		if (is_object($woocommerce)) {
			
			//WooCommerce plugin activated
			//Get products
			$product =$this->wcviews_setup_product_data($post);
		
			if ((isset($product)) && (is_product())) {
					
				//Check if product_type is set
				if (isset($product->product_type)) {
					
					//Set, check if defined
					$product_type=$product->product_type;
					
					if (!(empty($product_type))) {
						//Defined
						//Simple or variable products
						if (function_exists( 'woocommerce_breadcrumb' ) ) {
						
							//Call WooCommerce core public function on outputting WooCommerce Breadcrumb
							woocommerce_breadcrumb();
						
						}						
						return ob_get_clean();
					}
					
				}
			}
		}		
		
	}
	
	/** Export Woocommerce Views settings */
	/** Returns XML content of export if sensible, otherwise FALSE*/
	public function wcviews_export_settings() {
	
		$woocommerce_views_export_xml =FALSE;
		
		if(defined('WOOCOMMERCE_VIEWS_PLUGIN_PATH')) {
			
			//Define the parser path
			$array_xml_parser=WOOCOMMERCE_VIEWS_PLUGIN_PATH.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'array2xml.php';
		
			if (file_exists($array_xml_parser)) {
	
				//Define array of important WooCommerce Views settings for exporting.
				$woocommerceviews_options_for_exporting=array(
						'woocommerce_views_theme_template_file',
						'woocommerce_views_theme_archivetemplate_file',					
						'woocommerce_views_wrap_the_content'
				);
				
				$woocommerce_views_settings=array();
				
				//Loop through the settings and assign to array
				foreach ($woocommerceviews_options_for_exporting as $key=>$value) {
				
					$the_value=get_option($value);
					if ($the_value) {
						$woocommerce_views_settings[$value]=$the_value;
					}			
				}
				
				//Parser exists, require once
				require_once $array_xml_parser;
				
				//Instantiate
				$xml = new ICL_Array2XML();
	
				//Define anchor name
				$anchor_name='woocommerce_views_export_settings';
				
				//Get XML only if array is not empty
				if (!(empty($woocommerce_views_settings))) {
					$woocommerce_views_export_xml = $xml->array2xml($woocommerce_views_settings, $anchor_name);	
				}		
									
			}
		}
		
		return $woocommerce_views_export_xml;
	}
	/** Import Woocommerce Views settings */
	/** Otherwise FALSE*/
	
	public function wcviews_import_settings($xml) {
	
		if ($xml) {
			//$xml is sensible
			
			//Require $wpdb
			global $wpdb;
			
			if (function_exists('wpv_admin_import_export_simplexml2array')) {
				//public function exists get import data
				$import_data = wpv_admin_import_export_simplexml2array($xml);
				
				//Loop through the settings and update WooCommerce options
				$updated_settings=array();
				foreach ($import_data as $key=>$value) {
					if ('woocommerce_views_theme_template_file' == $key) {
						//Assign compatible templates at import site
						$updated_value=$this->wcviews_fix_theme_templates_after_import($value);
						
					} elseif ('woocommerce_views_theme_archivetemplate_file' == $key) {
						
						//Assign compatible templates at import site
						$updated_value=$this->wcviews_fix_theme_archivetemplates_after_import($value);		
									
					} else {
						
						update_option( $key, $value);
					}
				}
				
			}
		}
	}
	
	public function wcviews_fix_theme_templates_after_import($reference_site_theme_data) {	
		
		//Set woocommerce_views_theme_template_file
		
		//Get currently active theme information
		$theme_information=wp_get_theme();
		
		//Retrieved the currently activated theme name
		$name_of_template=$theme_information->stylesheet;
	
		//Retrieved the reference site theme name
		if ((is_array($reference_site_theme_data)) && (!(empty($reference_site_theme_data)))) {
			
			$imported_site_theme= key($reference_site_theme_data);
		
			//Extract PHP template of reference site
			$refsite_template_path= reset($reference_site_theme_data);
			
			$non_default_origin='plugin';
			//Here we checked if non-default comes from inside the theme or the WooCommerce Views plugin itself
			if ((strpos($refsite_template_path, '/themes/') !== false)) {
				$non_default_origin = 'theme';
			}
			
			$reference_site_php_template=basename($refsite_template_path);
			
			if ($name_of_template == $imported_site_theme) {
				
				//Import only if the activated theme matches with the reference site
				//Get theme root path			
				$theme_root_template=$theme_information->theme_root;			
				
			    //Define path to new PHP template after import unless its using default WooCommerce Templates
			    if ('Use WooCommerce Default Templates' == $reference_site_php_template) {
			    	//Using default WC Templates
			    	$path_to_pagetemplate=$reference_site_php_template;		  
			    	  	
			    } else {
			    	//Non-default
			    	//Verify origin
			    	if ('theme' == $non_default_origin) {
						$path_to_pagetemplate=$theme_root_template.DIRECTORY_SEPARATOR.$name_of_template.DIRECTORY_SEPARATOR.$reference_site_php_template;
			    	} elseif ('plugin' == $non_default_origin) {
			    		$path_to_pagetemplate=WOOCOMMERCE_VIEWS_PLUGIN_PATH.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'single-product.php'; 			    		
			    	}
			    }
				
			    if ($path_to_pagetemplate == $reference_site_php_template) {
			    	//Using default WC Templates
			    	$this->wcviews_save_php_template_settings($path_to_pagetemplate);
			    	
			    } else {
			    	//Non-default
			    	if (file_exists($path_to_pagetemplate)) {
			    	
			    		//Associated this PHP template with the Views Content Templates
			    		$this->wcviews_save_php_template_settings($path_to_pagetemplate);
			    	
			    	}		    	
			    }
			}
		}	
	}

	/**
	 * During plugin deactivation, let's clear the functions for conditional evaulations in Views.
	 * @access public
	 * @return void
	 */
		
	public function wcviews_clear_all_func_conditional_eval() {		

	   	//Define WC Views default functions
	   	$wcv_views_default_functions=$this->wcviews_functions;	   			
	   			
	   	//Get Views setting
	   	$views_setting= get_option('wpv_options');
	   	
	   	if ($views_setting) {
	   		
	   		//Views settings exists
	   		//Check if conditional functions are set by user previously
	   		if (isset($views_setting['wpv_custom_conditional_functions'])) {
	   					 
	   			//User has already set this, retrieved existing setting
	   			$existing_conditional_functions_setting=$views_setting['wpv_custom_conditional_functions'];
	   			
				if (is_array($existing_conditional_functions_setting)) {
					//$existing_conditional_functions_setting should be an array
					
					//Now let's loop through $existing_conditional_functions_setting then let's clear all WC Views functions on it
					$unsetted=array();
					foreach ($existing_conditional_functions_setting as $k=>$v) {
						
						if (in_array($v, $wcv_views_default_functions)) {
														
						     //This function is a WC Views function, unset
						     $unsetted[]=$v;
							unset($views_setting['wpv_custom_conditional_functions'][$k]);					     					
						}						
					}

					//Done, looping let's update the settings back to database					
					if (!(empty($unsetted))) {
						update_option('wpv_options',$views_setting);						
					}
					
	   			}
	   		} 
	   	}   		

	}
	
	/**
	 * in WooCommerce Views 2.4+, the admin setting is now included within Views.
	 * We want to make sure its before "settings" section of Views.
	 * 
	 * @access public
	 * @return void
	 */
	
	public function assign_proper_submenu_order_wcviews($menu_ord) {
		
		//Let's check if $menu_ord is TRUE
		if ($menu_ord) {
		   
			//Double check that all dependencies are set, proceed only if meet
			$missing_required_plugin=$this->check_missing_plugins_for_woocommerce_views();			
			
			if (empty($missing_required_plugin)) {
				
		   		//Set to true, access $submenu global
				global $submenu;			
			
				//Let's access Views sub-menus
			
				if (isset($submenu['views'])) {
				
				    //Views menu set.	
					//We only want to customized menu order if we are sure WooCommerce Views are set.
			 	    //Let's check if we can find the WooCommerce Views unordered submenu on it
				 
					$views_menu=$submenu['views'];
					$woocommerce_views_submenu_check=$this->wcviews_recursive_array_search('wpv_wc_views',$views_menu);
				    $views_settings_submenu_check=	 $this->wcviews_recursive_array_search('views-settings',$views_menu);
				    
				    if (($woocommerce_views_submenu_check) && ($views_settings_submenu_check)) {
				    	
				    	//All arrays set				    	
				    	$wc_views_submenu_array=array($views_menu[$woocommerce_views_submenu_check]);
				    	unset($views_menu[$woocommerce_views_submenu_check]);				    	
				    	array_splice($views_menu, $views_settings_submenu_check, 0,$wc_views_submenu_array);
				    	
				    	unset($submenu['views']);
				    	
				    	$submenu['views'] = $views_menu;
				    	
				    }
				
				}
			
			}
			
			
		}
				
		return $menu_ord;		
		
	}
	
	/**
	 * Aux function for recursive array search
	 *
	 * @access public
	 * @return mixed
	 */	
	public function wcviews_recursive_array_search($needle,$haystack) {
	    foreach($haystack as $key=>$value) {
	        $current_key=$key;
	        if($needle===$value OR (is_array($value) && ($this->wcviews_recursive_array_search($needle,$value) !== false))) {
	            return $current_key;
	        }
	    }
	    return false;
	}
	
	/**
	 * Remove unnecessary template warnings in edit product page when a layout has been assigned
	 *
	 * @access public
	 * @return void
	 */	
	
	public function remove_template_warning_if_layoutset() {
		
		$screen_output= get_current_screen();
		$current_screen_loaded=$screen_output->id;
		
		global $woocommerce;
		
		if (is_object($woocommerce)) {
			
		    //WooCommerce plugin activated
		    if (isset($_GET['action']))	{
		    	
		    	$action=$_GET['action'];
		    	
		    	//Check if we are on product edit and this post type should be under WooCommerce control.
		    	if (('edit'==$action) && ('product'==$current_screen_loaded)) {
		    		
		    		if( defined('WPDDL_VERSION') ) {
		    				
		    			//Layouts plugin activated
		    			//Let's check first if all dependencies are set
		    			
		    			$missing_required_plugin=$this->check_missing_plugins_for_woocommerce_views();

						if (empty($missing_required_plugin)) {
		    		
		    				//All required dependencies are set
		    				//Get Views setting
		    				global $wpddlayout;
		    		
		    				if (is_object($wpddlayout)) {
		    					
		    					//Access Layouts post type object
		    					$layout_posttype_object=$wpddlayout->post_types_manager;
		    						
		    					if (method_exists($layout_posttype_object,'get_layout_to_type_object')) {
		    						
		    						//Check if product post type has been assigned with Layouts
		    						$result=$layout_posttype_object->get_layout_to_type_object( 'product' );
		    						$check_if_wc_using_layouts=get_option('woocommerce_views_theme_template_file');
		    						
		    						if (is_array($check_if_wc_using_layouts)) {
		    						   //Template file set, let's check if its Layouts template
		    						   $value=reset($check_if_wc_using_layouts);
		    						   $template_file=basename($value);	
		    						   
		    						   if (('single-product.php' ==  $template_file) && ($result)) {
		    						   	
		    						   		//Enqueue
		    						   		//Product has now layouts assigned
		    						   		$wc_views_version=WC_VIEWS_VERSION;
		    						   	  	wp_enqueue_script('wc-views-remove-layout-warnings', plugins_url('res/js/wcviews-removewarnings.js',__FILE__),array('jquery'),$wc_views_version);		    						   	
		    						   	
		    						   }  						   
		    							
		    						}

		    					}
		    				}
						}
		    		}
		    		
		    	}
		    }
			
		}
		
		
	}
	
	/**
	 * If client assigns a Layout to shop page, make sure this one is used!
	 * Otherwise fall back to default product archive,etc.
	 *
	 * @access public
	 * @return integer
	 */
	public function use_layouts_shop_if_assigned($the_id,$the_layout) {		
		
		if( defined('WPDDL_VERSION') ) {
				
			//Layouts plugin activated
			//Let's check first if all dependencies are set
			$missing_required_plugin=$this->check_missing_plugins_for_woocommerce_views();
			
			if (empty($missing_required_plugin)) {
				
				//All dependencies are set
				//OK, first we need to know if we are on the shop page
				if (is_shop()) {
				   
				   //WooCommerce says this is a shop page, next we need to know if client has Layout assigned to this shop page
					global $wpddlayout;
					if (is_object($wpddlayout)) {
						if ((method_exists($wpddlayout,'get_layout_slug_for_post_object')) && (method_exists($wpddlayout,'get_layout_id_by_slug'))) {
							
							//We need to get the WooCommerce shop page ID
							if (function_exists('wc_get_page_id')) {
								$shop_page_id= wc_get_page_id( 'shop' );
								$shop_page_id = intval($shop_page_id);
								if ($shop_page_id > 0) {
								   //WooCommerce shop page available
								   $layout_slug_for_shop=$wpddlayout->get_layout_slug_for_post_object( $shop_page_id );
								   
								   if ($layout_slug_for_shop) {
								   		//OK we have Layouts assigned to this shop page, get its equivalent Layouts ID
								   		$the_id_shop= $wpddlayout->get_layout_id_by_slug( $layout_slug_for_shop );
								   		$the_id_shop= intval($the_id_shop);
								   		if ($the_id_shop > 0) {
								   			
								   			$the_id = $the_id_shop;
								   		}
								   }									
								}								
							}							
						}

					}
				 
				}				
			}
			
		}	

		return $the_id;
	}
	
	/**
	 * By default, let's not show gallery images in listings.
	 * Since 2.4.1
	 * @access public
	 * @return array
	 */	
	public function remove_gallery_on_main_image_at_listings($attachment_ids,$postobject) {		
		$is_listing=FALSE;		
		$is_listing=$this->wcviews_is_woocommerce_listing();

		if ((is_array($attachment_ids)) && (!(empty($attachment_ids)))) {
			//Image with galleries, let's check if we are on listing
			if ($is_listing) {
				//This is a WooCommerce product list page
				//Don't show galleries
				$attachment_ids=array();
			} else {
			  //Catch situations when we are doing a Views loop and we are not showing any of these galleries in the images
				if ( defined('WCVIEWS_DOING_LOOP') ) {
					if (WCVIEWS_DOING_LOOP) {						
						$attachment_ids=array();
					}						
				}				
			}
		}		
		
		return $attachment_ids;
	}
	
	/**
	 * Display selection for PHP template archive in WooCommerce Views
	 * Since 2.4.1
	 * @access public
	 * @return void
	 */	
	
	public function wc_views_display_php_archive_template_html() {
		global $wcviews_edit_help;
		$woocommerce_views_supported_templates= $this->load_correct_archivetemplate_files_for_editing_wc();		
		$layouts_plugin_status=$this->wc_views_check_status_of_layouts_plugin();
		?>
	<div class="wpv-setting-container">
	<div class="wpv-settings-header wcviews_header_views">

		<h3>
		<?php _e('Product Archive Template File','woocommerce_views');?>
		<i class="icon-question-sign js-wcviews-display-tooltip"
				data-header="<?php echo $wcviews_edit_help['archive_template_assignment_section']['title']?>"
				data-content="<?php echo $wcviews_edit_help['archive_template_assignment_section']['content']?>"></i>
		</h3>
	</div>
			<div class="wpv-setting">

				<div id="archivephptemplateassignment_wc_views">
					<p><?php _e('Select the PHP template which will be used for WooCommerce product archive pages:','woocommerce_views');?></p>
					<p>
		<?php 
		if (!(empty($woocommerce_views_supported_templates))) {

		    $var_selector='';
			$get_current_settings_wc_template=get_option('woocommerce_views_theme_archivetemplate_file');
			if 	($get_current_settings_wc_template) {
				
			   //Settings initialized	
				$get_key_template=key($get_current_settings_wc_template);
				$get_current_settings_wc_template_path=$get_current_settings_wc_template[$get_key_template];

				//Let's handle if user is originally using non-Layout supported PHP templates
				//Then user activates Layouts plugin
				if ($layouts_plugin_status) {														
					//Layouts activated
					if (!(in_array($get_current_settings_wc_template_path,$woocommerce_views_supported_templates))) {
									
						//User originally selected PHP template is not Layouts supported
						//Automatically use default WooCommerce Templates
						$this->wcviews_save_php_template_settings('Use WooCommerce Default Archive Templates');
						$get_current_settings_wc_template_path='Use WooCommerce Default Archive Templates';									
					}							
				} elseif (!(($layouts_plugin_status))) {
					   //Layouts deactivated
				   	   if (!(in_array($get_current_settings_wc_template_path,$woocommerce_views_supported_templates))) {
											
						//User originally selected PHP template is not Layouts supported
						//Automatically use default WooCommerce Templates
							$this->wcviews_save_php_template_settings('Use WooCommerce Default Archive Templates');
							$get_current_settings_wc_template_path='Use WooCommerce Default Archive Templates';
						}						   										
				}
			
				if ((is_array($woocommerce_views_supported_templates)) && (!(empty($woocommerce_views_supported_templates)))) {
					$counter_p=1;
			    	foreach ($woocommerce_views_supported_templates as $template_file_name=>$theme_server_path) {			

			    		$p_id='ptag_archive_'.$counter_p;
	   ?>
						
					
					
					<div class="template_selector_wc_views_div"
						id="<?php echo $p_id;?>">
						<input <?php echo $var_selector;?> type="radio"
							name="woocommerce_views_archivetemplate_to_override"
							value="<?php echo $theme_server_path?>"
							<?php if ($get_current_settings_wc_template_path==$theme_server_path) { echo "CHECKED";} ?>>				
							<?php 
								    if ('Use WooCommerce Default Archive Templates' ==$template_file_name) {
								       //Clarity
								    	if ($layouts_plugin_status) {
								    		$template_file_name = "WooCommerce Plugin Default Archive Template (doesn't display layouts)";
								    	} else {
								    		$template_file_name = 'WooCommerce Plugin Default Archive Templates';
								    	}
								    	
								    }
									echo $template_file_name;
							?>
							<a class="show_path_link" href="javascript:void(0)"><?php _e('Show template','woocommerce_views');?></a>
						<div class="show_path_wcviews_div" style="display: none;">
							<textarea rows="2" cols="50" class="inputtextpath" readonly />
							</textarea>
						</div>
					</div>
			    		<?php 
			    		$counter_p++;
			    		?>
			         	 <?php
					}
			    } else {
			        	     	//not loaded                  
			         	 ?>
			       <p>
						<input type="radio" name="woocommerce_views_template_to_override"
							value="Use WooCommerce Default Archive Templates">
			       	<?php _e('Use WooCommerce Default Archive Templates','woocommerce_views');?>
			       </p>             			
			        <?php
		 		}
		 			?>
			        <?php
		 	} else {
			        	     	
			       //Settings for archive as in dB options, not yet initialized				
			       //Check if no template is saved yet.
			        $status_template=$this->wc_views_check_if_using_woocommerce_default_archive_template();
			        	     	
			        if ((is_array($woocommerce_views_supported_templates)) && (!(empty($woocommerce_views_supported_templates)))) {
			        	 $counter_p=1;
			        	 foreach ($woocommerce_views_supported_templates as $template_file_name=>$theme_server_path) {
			       	         $file_basename=basename($theme_server_path);  	            
			       	         $p_id='ptag_archive_'.$counter_p;
			       	             
			       	     ?>             
			 			<div class="template_selector_wc_views_div"
						id="<?php echo $p_id;?>">
						<input <?php echo $var_selector;?> type="radio"
							name="woocommerce_views_archivetemplate_to_override"
							value="<?php echo $theme_server_path?>"
							<?php 
							 //At this point, we can consider using default archive if client is not overriding templates
							 if ($file_basename=='Use WooCommerce Default Archive Templates')  {				
			           	       echo "CHECKED";			          	        
			          	      } 
							?>>
							<?php
								 if ('Use WooCommerce Default Archive Templates' ==$template_file_name) {
								 	//Clarity
								 	    if ($layouts_plugin_status) {
								    		$template_file_name = "WooCommerce Plugin Default Archive Template (doesn't display layouts)";
								    	} else {
								    		$template_file_name = 'WooCommerce Plugin Default Archive Template';
								    	}
								 }
								 
								 echo $template_file_name;
								 ?>
								<a class="show_path_link" href="javascript:void(0)"><?php _e('Show template','woocommerce_views');?></a>
						<div class="show_path_wcviews_div" style="display: none;">
							<textarea rows="2" cols="50" class="inputtextpath" readonly />
							</textarea>
						</div>
					</div> 
							<?php $counter_p++;?>           		
			        	  	<?php
 					   }     
			       }    
			 }
			      	       ?>
		<?php 
		} 
		?>
		</p>
				</div>

			</div>
		</div>
    <?php if (!(empty($wcviews_edit_help['archive_template_assignment_section']['message_for_link']))) {?>
		<div class="toolset-help js-phptemplatesection">
			<div class="toolset-help-content">
				<p><?php echo $wcviews_edit_help['archive_template_assignment_section']['message_for_link']?></p>
			</div>
			<div class="toolset-help-sidebar">
				
			</div>
		</div>
   <?php
         }
  }
	/**
	 * Method for loading correct archive template files for using with WooCommerce Views
	 * 
	 * @access public
	 * @return array
	 */
	public function load_correct_archivetemplate_files_for_editing_wc() {
		
		// Get all information about the parent and child theme!
		$theme = wp_get_theme ();
		$get_custom_theme_info = $this->theme_name_and_template_path ( $theme );
		$complete_template_files_list = $theme->get_files ( 'php', 1, true );
		$complete_template_files_list = $this->wc_views_filter_only_relevant_wc_templates_innerdir ( $complete_template_files_list );
		//$headers_for_theme_files = $theme->get_page_templates ();
		
		//Retrieve stylesheet directory URI for the current theme/child theme
		$get_stylesheet_directory_data=get_stylesheet_directory();
		
		if ((is_array ( $complete_template_files_list )) && (! (empty ( $complete_template_files_list )))) {
			$correct_templates_list = array ();
			$layouts_plugin_status = $this->wc_views_check_status_of_layouts_plugin ();
			
			foreach ( $complete_template_files_list as $key => $values ) {
				$pos_page = stripos ( $key, 'archive-product' );
								
				if ($pos_page !== false) {
					
					// https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/193344239/comments
					// When Layouts plugin is active, only show templates that have the_ddlayouts integration
					$is_theme_template_has_ddlayout = FALSE;
					$is_theme_template_looped = FALSE;
					
					if ($layouts_plugin_status) {
						//Layouts plugin activated
						//Ensure archive-product.php is checked at correct path
						$template_lower_case= strtolower($key);
						if ((strpos($template_lower_case, 'archive-product.php') !== false)) {
							//This is an archive product template at the user theme directory
							$key = str_replace($get_stylesheet_directory_data, "", $values);
							$key =ltrim($key,'/');
						}
						
						$is_theme_template_has_ddlayout= $this->wcviews_template_have_layout($key);						
						
					} else {
						// Layouts inactive, lets fallback to usual PHP looped templates
						// Emerson: Qualified theme templates should contain WP loops for WC hooks and Views to work
						$is_theme_template_looped = $this->check_if_php_template_contains_wp_loop ( $values );
					}
					
					// Add those qualified PHP templates only once
					if ($is_theme_template_looped) {
						$correct_templates_list [$key] = $values;
					} elseif ($is_theme_template_has_ddlayout) {
						// This has a call to ddlayout
						$correct_templates_list [$key] = $values;
					}
				}
			}
			
			if (! (empty ( $correct_templates_list ))) {
				
				// Has templated loops to return
				$correct_templates_list ['Use WooCommerce Default Archive Templates'] = 'Use WooCommerce Default Archive Templates';
				
				// Append the template name to the file names
				$correct_template_list_final = $this->wcviews_append_archivetemplatename_to_templatefilename ( $correct_templates_list, $get_custom_theme_info );
				
				// Include WooCommerce Views Default archive-product.phpp template
				if (defined ( 'WOOCOMMERCE_VIEWS_PLUGIN_PATH' )) {
					
					$template_path = WOOCOMMERCE_VIEWS_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'archive-product.php';
					
					if (file_exists ( $template_path )) {
						// Template exist
						$correct_template_list_final ['WooCommerce Views plugin default product archive template'] = $template_path;
					}
				}
				
				return $correct_template_list_final;
			} else {
				// In this scenario, no eligible templates are found from the clients theme.
				// Let's provide the defaults from templates inside the WooCommerce Views plugin
				
				$correct_templates_list ['Use WooCommerce Default Archive Templates'] = 'Use WooCommerce Default Archive Templates';
				
				if (defined ( 'WOOCOMMERCE_VIEWS_PLUGIN_PATH' )) {
					
					$template_path = WOOCOMMERCE_VIEWS_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'archive-product.php';
					
					if (file_exists ( $template_path )) {
						// Template exist
						$correct_templates_list ['WooCommerce Views plugin default product archive template'] = $template_path;
					}
				}
				
				return $correct_templates_list;
  			}
  		}
  	}
  	
  	/**
  	 * Append correct archive template name
  	 * Since 2.4.1
  	 * @access public
  	 * @return array
  	 */
  	
  	public function wcviews_append_archivetemplatename_to_templatefilename($correct_template_list,$get_custom_theme_info) {
  	
  		$correct_template_list_final=array();
  	
  		//The defaults array
  		$defaults_name_array=array('woocommerce/archive-product.php'=>__('Theme Custom Product Archive Template','woocommerce_views'));
  		 
  		if (is_array($correct_template_list)) {
  	
  			//Loop through the correct template list
  			foreach ($correct_template_list as $template_file_name=>$template_path) {
  					
			if (isset($defaults_name_array[$template_file_name])) {
  					//not included in default WP core page array
  					//Check if included in basic template name array
  					$template_name_retrieved=$defaults_name_array[$template_file_name];
  	
  					//Append theme name for clarity
  					$theme_name=$this->get_theme_name_based_on_path($template_path,$get_custom_theme_info);
  	
  					//Get correct theme name append
  					$theme_append=$this->theme_append_wcviews_name_correctly($theme_name);
  	
  					if (empty($theme_append)) {
  						//Theme name already contains 'theme' word, remove 'theme' from $template_name_retrieved
  						$template_name_retrieved=str_replace('Theme', '', $template_name_retrieved);
  					}
  					$template_name_appended="$theme_name $template_name_retrieved";
  					$correct_template_list_final[$template_name_appended]=$template_path;
  	
  			} elseif ($template_file_name != 'Use WooCommerce Default Archive Templates') {
  					//No match, dissect the filename
  					 
  					//Append theme name for clarity
  					$theme_name=$this->get_theme_name_based_on_path($template_path,$get_custom_theme_info);
  		    
  					$dissected_template_file_name=$this->dissect_file_name_to_convert_to_templatename($template_file_name,$theme_name);
  					$dissected_template_file_name= $theme_name.' '.$dissected_template_file_name;
  					$correct_template_list_final[$dissected_template_file_name]=$template_path;
  			} else {
  					$correct_template_list_final['Use WooCommerce Default Archive Templates']='Use WooCommerce Default Archive Templates';
  			}
  	
  		}
  		  
  			return $correct_template_list_final;
  	
  		}
  	
  	}
  	
  	/**
  	 * Check if using Default WooCommerce core plugin archive templates
  	 * Since 2.4.1
  	 * @access public
  	 * @return boolean
  	 */
  	
  	public function wc_views_check_if_using_woocommerce_default_archive_template() {
  	
  		$the_active_php_template_option_thumbnails=get_option('woocommerce_views_theme_archivetemplate_file');
  	
  		if ((is_array($the_active_php_template_option_thumbnails)) && (!(empty($the_active_php_template_option_thumbnails)))) {
  			$the_active_php_template_thumbnails=reset($the_active_php_template_option_thumbnails);
  				
  			if ($the_active_php_template_thumbnails=='Use WooCommerce Default Archive Templates') {
  	
  				return TRUE;
  	
  			} else {
  	
  				return FALSE;
  			}
  		} else {
  	
  			//If option does not exist, return TRUE since it make sense that it defaults to WooCommerce Templates
  			return TRUE;
  		}
  	
  	}
  	
  	/**
  	 * Check if custom product archive PHP template exists inside theme directory
  	 * Since 2.4.1
  	 * @access public
  	 * @return string
  	 */
  	
  	public function wc_views_check_if_product_archive_template_exists() {
  	
  		$woocommerce_views_supported_templates= $this->load_correct_archivetemplate_files_for_editing_wc();
  	
  		$archive_product_template_found=FALSE;
  	
  		//Loop through the PHP templates array
  		if ((is_array($woocommerce_views_supported_templates)) && (!(empty($woocommerce_views_supported_templates)))) {
  	
  			foreach ($woocommerce_views_supported_templates as $template_name=>$template_path) {
  	
  				$template_file_name= basename($template_path);
  				if ($template_file_name=='archive-product.php') {
  					//Make sure this does not belong to WooCommerce Views
  					if ('WooCommerce Views plugin default product archive template' != $template_name) {
  						//Exist
  						return $template_path;
  					}  					
  					break;
  				}
  			}
  		}
  		 
  		return $archive_product_template_found;
  	
  	}
  	
  	/**
  	 * Save WooCommerce Views archive template settings to the options table.
  	 * @param  string $woocommerce_views_template_to_override
  	 * @access public
  	 * @return void
  	 */
  	
  	public function wcviews_save_php_archivetemplate_settings($woocommerce_views_template_to_override) {
  	
  		//Save template settings to options table
  		$option_name='woocommerce_views_theme_archivetemplate_file';
  	
  		//Template validation according to the status of Layouts plugin
  		$layouts_plugin_status=$this->wc_views_check_status_of_layouts_plugin();
  		$woocommerce_views_supported_templates= $this->load_correct_archivetemplate_files_for_editing_wc();
  		$woocommerce_views_template_to_override_slashed_removed=stripslashes(trim($woocommerce_views_template_to_override));
  	
  		//Let's handle if user is originally using non-Layout supported PHP templates
  		//Then user activates Layouts plugin
  		if ($layouts_plugin_status) {
  				
  			//Layouts activated
  			if (!(in_array($woocommerce_views_template_to_override_slashed_removed,$woocommerce_views_supported_templates))) {
  					
  				//User saved a PHP template which is not Layouts supported
  				//Automatically use default WooCommerce Templates
  				$woocommerce_views_template_to_override = 'Use WooCommerce Default Archive Templates';
  			}
  		} elseif (!(($layouts_plugin_status))) {
  			//Layouts deactivated
  				
  			if (!(in_array($woocommerce_views_template_to_override_slashed_removed,$woocommerce_views_supported_templates))) {
  					
  				//User saved a PHP template which is not Loops supported
  				//Automatically use default WooCommerce Templates
  				$woocommerce_views_template_to_override = 'Use WooCommerce Default Archive Templates';
  			}
  		}
  	
  		$template_associated=get_stylesheet();
  		$settings_value=array();
  		$settings_value[$template_associated]=stripslashes(trim($woocommerce_views_template_to_override));
  		$success=update_option( $option_name, $settings_value);
  	
  		//Reset content templates to none if using Default WooCommerce Template
  		//Template saved
  		$template_saved= stripslashes(trim($woocommerce_views_template_to_override));
  		  	
  		if ($template_saved=='Use WooCommerce Default Archive Templates') {
  				
  			//Reset WP archives to none
  			//All settings
			$this->reset_wp_archives_wcviews_settings();
  	
  		}
  	}

  	/**
  	 * Attempt to reset wp archives settings
  	 * Since 2.4.1
  	 * @access public
  	 * @return void
  	 */
  	
  	public function reset_wp_archives_wcviews_settings() {
  		
  		//Reset WP archives template
  		global $WP_Views;
  		$views_settings_options_original = $WP_Views->get_options();
  		$views_settings_options = $views_settings_options_original;
  			
  		//Shop page reset
  		if (isset($views_settings_options['view_cpt_product'])) {
  		
  			//Make sure its not null
  			if (!(empty($views_settings_options['view_cpt_product']))) {
  				//Backup last archive template options table
  					
  				$last_wp_archive_template_used=$views_settings_options['view_cpt_product'];
  				update_option('wc_views_last_archive_template_used',$last_wp_archive_template_used);
  				 
  				//Set archive template to null
  				$views_settings_options['view_cpt_product']='';
  			}
  		}
  			
  		//Product cat reset
  		if (isset($views_settings_options['view_taxonomy_loop_product_cat'])) {
  				
  			//Make sure its not null
  			if (!(empty($views_settings_options['view_taxonomy_loop_product_cat']))) {
  				//Backup last archive template options table
  					
  				$last_wp_archive_cat_template_used=$views_settings_options['view_taxonomy_loop_product_cat'];
  				update_option('wc_views_last_catarchive_template_used',$last_wp_archive_cat_template_used);
  					
  				//Set archive template to null
  				$views_settings_options['view_taxonomy_loop_product_cat']='';
  		
  			}
  		}
  		
  		//Product tag reset
  		if (isset($views_settings_options['view_taxonomy_loop_product_tag'])) {
  				
  			//Make sure its not null
  			if (!(empty($views_settings_options['view_taxonomy_loop_product_tag']))) {
  				//Backup last archive template options table
  					
  				$last_wp_archive_tag_template_used=$views_settings_options['view_taxonomy_loop_product_tag'];
  				update_option('wc_views_last_tagarchive_template_used',$last_wp_archive_tag_template_used);
  					
  				//Set archive template to null
  				$views_settings_options['view_taxonomy_loop_product_tag']='';
  		
  			}
  		}  		
  	}
  	
  	/**
  	 * Load WooCommerce Views archive template PHP settings (as defined on its backend)
  	 * Since 2.4.1
  	 * @access public
  	 * @return void
  	 */
  	
  	public function woocommerce_views_activate_archivetemplate_redirect()
  	{
  	
  		//This affects the front end
  	
  		global $woocommerce;
  		if (is_object($woocommerce)) {
  			//WooCommerce plugin activated
  			if ((is_shop()) ||
  			    (is_product_category()) ||
                (is_product_tag()) ||
  				(is_product_taxonomy())) 			 
  			 {
  				//Any WooCommerce product archives!
  				//Get template settings
  		   
  				$get_template_wc_template=get_option('woocommerce_views_theme_archivetemplate_file');
  		   
  				if ((is_array($get_template_wc_template)) && (!(empty($get_template_wc_template)))) {
  					 
  					$live_active_template=get_stylesheet();
  					$template_name_for_redirect=key($get_template_wc_template);
  					$template_path_for_redirect=$get_template_wc_template[$template_name_for_redirect];
  	
  					//Make sure this template change makes sense
  					if ($live_active_template==$template_name_for_redirect) {
  							
  						//Template settings exists, but don't do anything unless specified
  						if (!($template_path_for_redirect=='Use WooCommerce Default Archive Templates')) {
  								
  							//Template file selected, load it
  							if (file_exists($template_path_for_redirect)) {
  								include($template_path_for_redirect);
  								exit();
  							}
  						}
  					}
  				}
  			}
  		}
  	
  	}
  	
  	/**
  	 * Load WooCommerce Views archive template PHP settings (as defined on its backend)
  	 * Since 2.4.1
  	 * @access public
  	 * @return void
  	 */
  	
  	public function wc_views_reset_wc_defaultarchive_after_theme_switching() {

  		//Run the method to use WooCommerce default templates
  		$is_using_wc_default_template=$this->wc_views_check_if_using_woocommerce_default_archive_template();
  		 
  		//Reset this option by deletion
  		delete_option('wc_views_nondefaultarchivetemplate_changed');
  		if (!($is_using_wc_default_template)) {
  		
  			//Using non-default template,
  			update_option('wc_views_nondefaultarchivetemplate_changed','yes');
  			 
  		} else {
  		
  			//Using default WooCommmerce template,
  			update_option('wc_views_nondefaultarchivetemplate_changed','no');
  		}
  		
  		$this->wcviews_save_php_archivetemplate_settings('Use WooCommerce Default Archive Templates');
  		
  	}
  	
  	/**
  	 * Helper method: Filter function for $template.
  	 * Ensures it returns archive-product.php from the WooCommerce plugin templates.
  	 * Hooked to template_include filter.
  	 * @access public
  	 * @param  string $template
  	 * @return string
  	 */
  	
  	public function wc_views_archivetemplate_loader($template) {
  	
  		global $woocommerce;
  	
  		if (is_object($woocommerce)) {
  			
  		    //OK, We have WooCommerce activated
  		    //These functions are safe to use
  			if ((is_shop()) ||
  					(is_product_category()) ||
  					(is_product_tag()) ||
  					(is_product_taxonomy())) {
  						
  						/** EMERSON: These are not rendered or read when using the setting 'WooCommerce Views plugin default product archive template' */
  						/** This setting is found in 'Product Archive Template File' section in WooCommerce Views settings.
  						/** This is only read if the user is selecting the setting 'WooCommerce Plugin Default Archive Templates'*/
  						
  						/** However, there are cases beyond WooCommerce Views and WooCommerce plugin controls where there are archive template overrides present in the theme */
  						/** For example using woocommerce.php in the theme root */
  						
  						/** According to WC note: http://docs.woothemes.com/document/template-structure/
  						 *  When creating woocommerce.php in your theme’s folder, you won’t then be able to override the 
  						 *  woocommerce/archive-product.php custom template (in your theme) as woocommerce.php has the priority over all other template files. 
  						 *  This is intended to prevent display issues.
  						 */
  						/** Presence of this wooommerce.php template file assumes user wants to render this and not default WooCommerce core plugin templates */
  						/** So let's checked if the template to be filtered is woocommerce.php and let it pass */
  						
  						$basename_template = basename($template);
  						
  						if ('woocommerce.php' != $basename_template) {
  							
  							//Template is not the woocommerce.php, proceed to loading WC core archive default template
  							$file='archive-product.php';
  							$template = $woocommerce->plugin_path() . '/templates/' . $file;
  						}
  						 
  					}		    				
  		}

  		return $template;
  	}
  	
  	/**
  	 * Ensure that when setting to default WooCommerce, its own templates should be loaded.
  	 * Since 2.4.1
  	 * @access public
  	 * @return void
  	 */
  	
  	public function wc_views_dedicated_archivetemplate_loader() {
  	
  		add_filter( 'template_include',array( $this, 'wc_views_archivetemplate_loader' ) );
  			
  	}
  	
  	/**
  	 * Helper method to fall back to WooCommerce core archive front end rendering or Views archive
  	 * If Layouts plugin is activated but no Layouts has been assigned to an archive
  	 * Since 2.4.1
  	 * @access public
  	 * @return void
  	 */
  	
  	public function wc_views_check_if_anyproductarchive_has_layout() {
  		
  		if( defined('WPDDL_VERSION') ) {
  				
  			//Layouts is activated on this site
  				
  			global $wpddlayout,$woocommerce;
  				
  			if ((is_object($wpddlayout)) & (is_object($woocommerce))) {
  				
  				//Rule below applies only to WooCommerce product archives
  				if ((is_shop()) || (is_product_category()) || (is_product_tag()) || (is_product_taxonomy())) {
  					if (class_exists('WPDD_Layouts_RenderManager')) {
  						$layouts_render_manager_instance=WPDD_Layouts_RenderManager::getInstance();
  						if (method_exists($layouts_render_manager_instance,'get_layout_id_for_render')) {
  								
  							$layouts_id_to_render=$layouts_render_manager_instance->get_layout_id_for_render( false, $args = null );
  							$layouts_id= intval($layouts_id_to_render);
  							if ($layouts_id > 0) {  		
  								//This constant defined only once							
  								define('WC_VIEWS_ARCHIVES_LAYOUTS', true);
  							}  								
  						}  						
  					}
  				}	
  			} 			
  		
  		}  		
  	}
  	
  	/**
  	 * Import archive settings correctly
  	 * Since 2.4.1
  	 * @access public
  	 * @return void
  	 */
  	
  	public function wcviews_fix_theme_archivetemplates_after_import($reference_site_theme_data) {
  	
  		//Set woocommerce_views_theme_template_file
  	
  		//Get currently active theme information
  		$theme_information=wp_get_theme();
  	
  		//Retrieved the currently activated theme name
  		$name_of_template=$theme_information->stylesheet;
  	
  		//Retrieved the reference site theme name
  		if ((is_array($reference_site_theme_data)) && (!(empty($reference_site_theme_data)))) {
  				
  			$imported_site_theme= key($reference_site_theme_data);
  	
  			//Extract PHP template of reference site
  			$refsite_template_path= reset($reference_site_theme_data);
  			
  			$non_default_origin='plugin';
  			//Here we checked if non-default comes from inside the theme or the WooCommerce Views plugin itself
  			if ((strpos($refsite_template_path, '/themes/') !== false)) {
  				$non_default_origin = 'theme';
  			}
  			
  			$reference_site_php_template=basename($refsite_template_path);
  				
  			if ($name_of_template == $imported_site_theme) {
  	
  				//Import only if the activated theme matches with the reference site
  				//Get theme root path
  				$theme_root_template=$theme_information->theme_root;
  	
  				//Define path to new PHP template after import unless its using default WooCommerce Templates
  				if ('Use WooCommerce Default Archive Templates' == $reference_site_php_template) {
  					//Using default WC Templates
  					$path_to_pagetemplate=$reference_site_php_template;
  					 
  				} else {
  					
  					//Non-default
  					//Verify origin
  					if ('theme' == $non_default_origin) {
  						$path_to_pagetemplate=$theme_root_template.DIRECTORY_SEPARATOR.$name_of_template.DIRECTORY_SEPARATOR.$reference_site_php_template;
  					} elseif ('plugin' == $non_default_origin) {
  						$path_to_pagetemplate=WOOCOMMERCE_VIEWS_PLUGIN_PATH.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'archive-product.php';
  					}
  					
  				}
  	
  				if ($path_to_pagetemplate == $reference_site_php_template) {
  					//Using default WC Templates
  					$this->wcviews_save_php_archivetemplate_settings($path_to_pagetemplate);
  	
  				} else {
  					//Non-default
  					if (file_exists($path_to_pagetemplate)) {  	
  						//Associated this PHP template with the Views Content Templates
  						$this->wcviews_save_php_archivetemplate_settings($path_to_pagetemplate);  	
  					}
  				}
  			}
  		}
  	}

  	/**
  	 * Fix new nav menus occurring in WooCommerce 2.3.4
  	 * Since 2.5
  	 * @access public
  	 * @return void
  	 */
  	
  	public function wcviews_remove_filter_for_wc_endpoint_title() {

  		if( defined('WPDDL_VERSION') ) {
  				
  			//Layouts plugin activated

  			//All required dependencies are set
  			//Get Views setting
  			global $wpddlayout;
  		
  			if (is_object($wpddlayout)) {
  				remove_filter( 'the_title', 'wc_page_endpoint_title' );
  			}
  		} 		
  	}

  	/**
  	 * Product meta shortcode
  	 * Since 2.5
  	 * @access public
  	 * @return void
  	 */
  	
  	public function wpv_woo_product_meta_func($atts) {
  		
  		global $post,$woocommerce;
  		
  		ob_start();
  		
  		$product =$this->wcviews_setup_product_data($post);
  		
  		//Check if $product is set
  		if (isset($product)) {
  			//Let's checked if product type is set
  			if (isset($product->product_type)) {
  				//Let's checked if it contains sensible value
  				$product_type=$product->product_type;
  				if (!(empty($product_type))) {
  					//Yes product types exist and set
  					
  					if ( function_exists( 'woocommerce_template_single_meta' ) ) {
  						
  						woocommerce_template_single_meta();
  						return ob_get_clean();
  						
  					}	
  				}
  			}
  		}  		
  	}
  	
  	/**
  	 * Displays the number of items added in WooCommerce Cart
  	 * Please synchronize any changes on this method with 'woocommerce_views_add_to_cart_fragment'
  	 * Original source is derived from this doc: http://docs.woothemes.com/document/show-cart-contents-total/
  	 * Since 2.5.1
  	 * @access public
  	 * @return void
  	 */
  	
  	public function wpv_woo_cart_count_func($atts) {
  		
  		global $woocommerce;
  		
  		ob_start();
  		
  		if (is_object($woocommerce)) {
           //WooCommerce plugin activated
           //Let's checked the cart count
           
            $cart_count=WC()->cart->cart_contents_count;
            $cart_count= intval($cart_count); 
            
            //Add count to class
            $cart_class = $cart_count;

            if ($cart_count < 1) {
                //Nothing is added on cart
                $cart_count='';    
                $cart_class = 0;
            }
	?>	        
	        <span class='wcviews_cart_count_output wcviews_cart_count_<?php echo $cart_class;?>'><?php echo $cart_count; ?></span>
	<?php 	       
	  		return ob_get_clean();
  		}
  	}
  	
  	/**
  	 * Ajaxify version-Displays the number of items added in WooCommerce Cart
  	 * Original source is derived from this doc: http://docs.woothemes.com/document/show-cart-contents-total/
  	 * Since 2.5.1
  	 * @access public
  	 * @return void
  	 */
  	public function woocommerce_views_add_to_cart_fragment( $fragments ) {

  		global $woocommerce;
  	
  		ob_start();
  	
	  	if (is_object($woocommerce)) {
	  		//WooCommerce plugin activated
	  		//Let's checked the cart count
	  		 
	  		$cart_count=WC()->cart->cart_contents_count;
	  		$cart_count= intval($cart_count);

	  		//Add count to class
	  		$cart_class = $cart_count;
	  		
	  		if ($cart_count < 1) {
	  			//Nothing is added on cart
	  			$cart_count='';
	  			$cart_class = 0;
	  			 
	  		}
	?>
			<span class='wcviews_cart_count_output wcviews_cart_count_<?php echo $cart_class;?>'><?php echo $cart_count; ?></span>  	
	<?php 		  		
	  		//Doing AJAX	
	  		$fragments['span.wcviews_cart_count_output'] = ob_get_clean();
	  		return $fragments;

	  	}
  	}
  	
  	/**
  	 * JS handler for WooCommerce Views onsale shortcode so it will display properly on Views AJAX paginated pages.
  	 * Since 2.5.1
  	 * @access public
  	 * @return array
  	 */
  	public function wcviews_onsale_pagination_callback_func($view_settings, $view_id) {
  		
  	   //Step1, we need to ensure 'WooCommerce' plugin is activated
  		global $woocommerce;
  		 
  		if (is_object($woocommerce)) {
  			//Step2. We need to check for Views that loads 'product' post type
  			//This 'product' post type is now WooCommerce controlled
  			if (isset($view_settings['post_type'])) {  				
  				$post_types=$view_settings['post_type'];  				
  				if (in_array('product',$post_types)) {
  					
  				    //Product post type set  				    	
  				    //Step3,we need to check if pagination is enabled to AJAX  				    
  				    if (isset($view_settings['pagination']['mode'])) {
  				    	
  				    	$pagination_mode=$view_settings['pagination']['mode'];
  				    	
  				    	if ('paged' == $pagination_mode) {
  				    		
  				    		//It's paginated
  				    		//Check if we have callback_next set, don't override user-defined functions
  				    		$call_back_set=false;
  				    		if (isset($view_settings['pagination']['callback_next'])) {  				    		   
  				    		   //Set, let's checked if defined.
  				    		   $call_back_next=$view_settings['pagination']['callback_next'];
  				    		   if (!(empty( $call_back_next))) {  				    		   	
  				    		   	  //Defined,
  				    		   	  $call_back_set=true;  				    		   	
  				    		   }
  				    		}

  				    		if (!($call_back_set)) {
  				    		   //Let's load default WooCommerce Views JS onsale shortcode pagination handler  				    		   	
  				    		   	$view_settings['pagination']['callback_next'] = 'wcviews_onsale_pagination_callback';
  				    		}
  				    	}
  				        	
  				    }
  					
  				}
  				
  			}
  		}
  	
  	   //For anything else, return unfiltered settings
  	   return $view_settings;
  		
  	}
  	
  	/**
  	 * WooCommerce Views shortcode to display only the reviews and not in tab.
  	 * Since 2.5.1
  	 * @access public
  	 * @return array
  	 */
  	
  	public function wpv_woo_show_displayreviews_func($atts) {
  		 
  		global $woocommerce;
  		if (is_object($woocommerce)) {
  			if (is_product()) {
  	
  				ob_start();
  				if (  function_exists( 'woocommerce_default_product_tabs' ) ) {
  						
  					//Tabs set
  						
  					$tabs_default= woocommerce_default_product_tabs();
  					if (isset($tabs_default['reviews'])) {
  	
  						//Reviews tab
  						$key='reviews';
  						$tab=$tabs_default['reviews'];
  						call_user_func( $tab['callback'], $key, $tab );
  	
  					}
  				}
  	
  				return ob_get_clean();
  			}
  		}
  	}
  	
  	public function wcviews_before_display_post ($post, $view_id) {
  		
  		if (is_object($post)) {
  			$post_type=$post->post_type;
  			global $woocommerce;
  			if ('product' == $post_type) {
  				if (is_object($woocommerce)) {
  					if ( !defined('WCVIEWS_DOING_LOOP') ) {
  						define('WCVIEWS_DOING_LOOP', true);
  					}  					
  				} 				
  			}  			
  		}
  	}
  	
  	public function wcviews_after_display_post ($post, $view_id) {
  	
  		if (is_object($post)) {
  			$post_type=$post->post_type;
  			global $woocommerce;
  			if ('product' == $post_type) {
  				if (is_object($woocommerce)) {
  					if ( !defined('WCVIEWS_DOING_LOOP') ) {
  						define('WCVIEWS_DOING_LOOP', false);
  					}
  				}
  			}
  		}
  	}
  	
  	/** Check if a product category being loaded has a subcategory */
  	/** Example usage:
  	 [wpv-if evaluate="woo_has_product_subcategory() = 1"]
  	 This product category has a subcategory.
  	 [/wpv-if]
  	 [wpv-if evaluate="woo_has_product_subcategory() = 0"]
  	 This product category has does not have a subcategory.
  	 [/wpv-if]
  	 */
  	public function woo_is_product_subcategory_func() {
  	
  		global $woocommerce;
  		$bool=FALSE;
  		if (is_object($woocommerce)) {
  			if (function_exists('is_product_category')) {
  				if ( is_product_category() ) {
  						
  					//This is a product subcategory
  					$term 			= get_queried_object();
  					$parent_id 		= empty( $term->term_id ) ? 0 : $term->term_id;
  						
  						
  					// NOTE: using child_of instead of parent - this is not ideal but due to a WP bug ( http://core.trac.wordpress.org/ticket/15626 ) pad_counts won't work
  					$product_categories = get_categories( apply_filters( 'woocommerce_product_subcategories_args', array(
  							'parent'       => $parent_id,
  							'menu_order'   => 'ASC',
  							'hide_empty'   => 0,
  							'hierarchical' => 1,
  							'taxonomy'     => 'product_cat',
  							'pad_counts'   => 1
  					) ) );
  					
  					if (is_array($product_categories)) {
  						
  						if (!(empty($product_categories))) {
  							
  							$bool=TRUE;
  							return $bool;	
  						}
  						
  					}
  						
  				}	
  			}  			
  		}
  		return $bool;
  	} 

  	/** For WooCommerce review system compatibility, we removed this comment_clauses so it will be handled solely by WooCommerce review templates*/
  	public function wcviews_multilingual_reviews_func() {  		
  		global $sitepress,$woocommerce;
  		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {  			
  			if ((is_object($sitepress)) && (is_object($woocommerce))) {							
				if (is_admin()) {			
  					remove_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2 );  
				} else {
					if (is_product()) {
						remove_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2 );						
					}					
				}  				  				
  			}  			
  		}  		
  	}
  	
  	public function wcviews_review_templates_handler() {
  		add_action( 'wp',array($this,'wcviews_multilingual_reviews_func'),20);
  		add_action( 'wp_loaded',array($this,'wcviews_multilingual_reviews_func'),20);
  	}
  	//Returns TRUE if using Views 1.10+
  	public function wc_views_modern_views_toolbox_check() {

  		if ( defined( 'WPV_VERSION' )) {
  			if (version_compare(WPV_VERSION, '1.10', '<')) {
  	
  				return FALSE;
  	
  			} else {  	
  				return TRUE;  	
  			}  	
  		} else {
  		  return FALSE;	
  		}  	
  	}  	
}