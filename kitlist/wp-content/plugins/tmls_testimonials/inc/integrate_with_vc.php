<?php


	if ( defined( 'WPB_VC_VERSION' ) ) {
		add_action( 'vc_before_init', 'tmls_integrateWithVC' );	
	}
	
	function tmls_integrateWithVC() {
		
		// Saved testimonials shortcodes
		
		$tmls_args =	array ( 'post_type' => 'tmls_sc',
						'posts_per_page' => -1,
						'orderby' => 'post_title',
						'suppress_filters' => 0);
						
		$tmls_saved_sc = get_posts( $tmls_args );
		$tmls_saved_sc_array = array();
		
		foreach ( $tmls_saved_sc as $post ) {
		   $tmls_saved_sc_array[$post->post_title] = $post->ID;
		}
	   
	    vc_map( array(
		    "name" => "Testimonials",
		    "base" => "tmls_saved",
		    "category" => "Content",
		    "description" => "Place testimonials",
			"icon" => plugins_url('../images/composer-icon.png', __FILE__),
		    "params" => array(
			    array(
				    "type" => "dropdown",
				    "heading" => "Saved Shortcode",
				    "param_name" => "id",
				    "value" => $tmls_saved_sc_array,
					"admin_label" => true,
				    "description" => "Please select the saved shortcode name from the dropdown list."
			    )
		    )
			
	    ) );
		
		// Saved submission forms shortcodes
		
		$tmls_form_args =	array ( 'post_type' => 'tmls_form_sc',
						'posts_per_page' => -1,
						'orderby' => 'post_title',
						'suppress_filters' => 0);
						
		$tmls_form_saved_sc = get_posts( $tmls_form_args );
		$tmls_form_saved_sc_array = array();
		
		foreach ( $tmls_form_saved_sc as $post ) {
		   $tmls_form_saved_sc_array[$post->post_title] = $post->ID;
		}
	   
	    vc_map( array(
		    "name" => "Submission Form",
		    "base" => "tmls_form_saved",
		    "category" => "Content",
		    "description" => "Place submission form",
			"icon" => plugins_url('../images/composer-icon.png', __FILE__),
		    "params" => array(
			    array(
				    "type" => "dropdown",
				    "heading" => "Saved Shortcode",
				    "param_name" => "id",
				    "value" => $tmls_form_saved_sc_array,
					"admin_label" => true,
				    "description" => "Please select the saved shortcode name from the dropdown list."
			    )
		    )
			
	    ) );
	   
	}
	
?>