<?php
	/*
	Plugin Name: Testimonials Wordpress plugin
	Plugin URI: http://codecanyon.net/user/husamrayan
	Description: Testimonials Wordpress plugin.
	Version: 1.5
	Author: husamrayan
	*/
	
	


	/*==========================================================================
		enqueue
	==========================================================================*/

	function tmls_theme_enqueue() {
		wp_register_style( 'tmls-testimonials', plugins_url('css/testimonials.css', __FILE__) );
		wp_enqueue_style( 'tmls-testimonials' );
		
		wp_enqueue_script('jquery');
		
		wp_register_script( 'tmls_ba_throttle_debounce', plugins_url('js/helper-plugins/jquery.ba-throttle-debounce.min.js', __FILE__), null, null, true );
		wp_enqueue_script( 'tmls_ba_throttle_debounce' );
		
		wp_register_script( 'tmls_mousewheel', plugins_url('js/helper-plugins/jquery.mousewheel.min.js', __FILE__), null, null, true );
		wp_enqueue_script( 'tmls_mousewheel' );
		
		wp_register_script( 'tmls_touchSwipe', plugins_url('js/helper-plugins/jquery.touchSwipe.min.js', __FILE__), null, null, true );
		wp_enqueue_script( 'tmls_touchSwipe' );
		
		wp_register_script( 'tmls_transit', plugins_url('js/helper-plugins/jquery.transit.min.js', __FILE__), null, null, true );
		wp_enqueue_script( 'tmls_transit' );
		
		wp_register_script( 'tmls-carouFredSel', plugins_url('js/jquery.carouFredSel-6.2.1.js', __FILE__), null, null, true );
		wp_enqueue_script( 'tmls-carouFredSel' );
		
		wp_register_script( 'tmls-testimonials-js', plugins_url('js/testimonials.js', __FILE__), null, null, true );
		wp_enqueue_script( 'tmls-testimonials-js' );
	}
	
	add_action( 'wp_enqueue_scripts', 'tmls_theme_enqueue' );
	
	function tmls_admin_enqueue($hook) {
		
		global $post;
		
		if ( ($post->post_type == 'tmls_sc' || $post->post_type == 'tmls_form_sc') && ($hook == 'post-new.php' || $hook == 'post.php') ) {
			
			wp_register_style( 'tmls-testimonials', plugins_url('css/testimonials.css', __FILE__) );
			wp_enqueue_style( 'tmls-testimonials' );
			
			wp_register_style( 'tmls-admin-style', plugins_url('css/admin.css', __FILE__) );
			wp_enqueue_style( 'tmls-admin-style' );
			
			wp_register_script( 'tmls-testimonials-js', plugins_url('js/testimonials.js', __FILE__), null, null, true );
			wp_enqueue_script( 'tmls-testimonials-js' );
			
			if ( $post->post_type == 'tmls_sc' ) {
			
				wp_register_script( 'tmls_ba_throttle_debounce', plugins_url('js/helper-plugins/jquery.ba-throttle-debounce.min.js', __FILE__), null, null, true );
				wp_enqueue_script( 'tmls_ba_throttle_debounce' );
				
				wp_register_script( 'tmls_mousewheel', plugins_url('js/helper-plugins/jquery.mousewheel.min.js', __FILE__), null, null, true );
				wp_enqueue_script( 'tmls_mousewheel' );
				
				wp_register_script( 'tmls_touchSwipe', plugins_url('js/helper-plugins/jquery.touchSwipe.min.js', __FILE__), null, null, true );
				wp_enqueue_script( 'tmls_touchSwipe' );
				
				wp_register_script( 'tmls_transit', plugins_url('js/helper-plugins/jquery.transit.min.js', __FILE__), null, null, true );
				wp_enqueue_script( 'tmls_transit' );
				
				wp_register_script( 'tmls-carouFredSel', plugins_url('js/jquery.carouFredSel-6.2.1.js', __FILE__), null, null, true );
				wp_enqueue_script( 'tmls-carouFredSel' );
				
				wp_register_script( 'tmls-testimonials-generate-shortcode', plugins_url('js/testimonials_generate_shortcode.js', __FILE__), null, null, true );
				wp_enqueue_script( 'tmls-testimonials-generate-shortcode' );
				
			}
			
			if ( $post->post_type == 'tmls_form_sc' ) {
				
				wp_register_script( 'tmls-form-generate-shortcode', plugins_url('js/form_generate_shortcode.js', __FILE__), null, null, true );
				wp_enqueue_script( 'tmls-form-generate-shortcode' );
			
			}
			
			global $wp_version;
		
			 //If the WordPress version is greater than or equal to 3.5, then load the new WordPress color picker.
			if ($wp_version >= 3.5){
				//Both the necessary css and javascript have been registered already by WordPress, so all we have to do is load them with their handle.
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );

			}
			//If the WordPress version is less than 3.5 load the older farbtasic color picker.
			else {
				//As with wp-color-picker the necessary css and javascript have been registered already by WordPress, so all we have to do is load them with their handle.
				wp_enqueue_style( 'farbtastic' );
				wp_enqueue_script( 'farbtastic' );
			}
			
		}
		else if($hook == 'index.php') {
		
			wp_register_style( 'tmls-admin-style', plugins_url('css/admin.css', __FILE__) );
			wp_enqueue_style( 'tmls-admin-style' );
			
		}
		
	}
	
	add_action( 'admin_enqueue_scripts', 'tmls_admin_enqueue' );
	
	
	/*==========================================================================
		Testimonial custom post type
	============================================================================*/
	
	include('inc/testimonial_custom_post.php');
	
	
	/*==========================================================================
		Testimonials Shortcodes
	============================================================================*/
	
	include('inc/testimonials_shortcodes.php');
	
	/*==========================================================================
		Submission Form Shortcodes
	============================================================================*/
	
	include('inc/form_shortcodes.php');
	
	
	/*==========================================================================
		Register tmls_sc Post Type
	============================================================================*/
	
	include('inc/testimonials_generate_shortcode/generate_shortcode.php');
	
	/*==========================================================================
		Register tmls_form Post Type
	============================================================================*/
	
	include('inc/form_generate_shortcode/generate_shortcode.php');
	
	
	/*==========================================================================
		Admin Menu
	============================================================================*/
	
	add_action('admin_menu', 'tmls_register_my_custom_submenu_page');

	function tmls_register_my_custom_submenu_page() {
		
		// Generate Shortcode Page
		add_submenu_page( 'edit.php?post_type=tmls', 'Generate shortcode', 'Generate shortcode', 'manage_options', 'post-new.php?post_type=tmls_sc' );
		
		// Saved Shortcodes Page
		add_submenu_page( 'edit.php?post_type=tmls', 'Saved Shortcodes', 'Saved Shortcodes', 'manage_options', 'edit.php?post_type=tmls_sc' );
		
		// form (Generate Shortcode) Page
		add_submenu_page( 'edit.php?post_type=tmls', 'Submission form', 'Submission form', 'manage_options', 'post-new.php?post_type=tmls_form_sc' );
		
		// form (Saved Shortcodes) Page
		add_submenu_page( 'edit.php?post_type=tmls', 'Saved forms', 'Saved forms', 'manage_options', 'edit.php?post_type=tmls_form_sc' );
		
	}
	
	/*==========================================================================
		Shortcode Widget
	============================================================================*/
	
	include('inc/widget.php');
	
	
	/*==========================================================================
		Dashboard Widget
	============================================================================*/
	
	include('inc/dashboard_widget.php');
	
	
	/*==========================================================================
		Integrate With VC
	============================================================================*/
	
	include('inc/integrate_with_vc.php');
	
	
?>