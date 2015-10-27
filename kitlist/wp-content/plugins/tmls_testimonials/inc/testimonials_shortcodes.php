<?php
	
	/*========================================================================================================================================================================
		Shortcode
	========================================================================================================================================================================*/

	function tmls_shortcode($atts, $content=null) {  
		extract(shortcode_atts( array(  
			'category' => '-1',
			'layout' => 'tmls_slider',
			'style' => 'style1',
			'dialog_radius' => 'small_radius',
			'image_size' => 'large_image',
			'image_radius' => 'large_radius',
			'dialogbgcolor' => '#f5f5f5',
			'dialogbordercolor' => '#DDDDDD',
			'text_font_family' => '',
			'text_font_color' => '',
			'text_font_size' => '14',
			'name_font_family' => '',
			'name_font_color' => '',
			'neme_font_size' => '15',
			'neme_font_weight' => 'bold',
			'position_font_family' => '',
			'position_font_color' => '',
			'position_font_size' => '12',
			'order_by' => 'date',
			'order' => 'DESC',
			'number' => '-1',
			'auto_play' => 'true',
			'transitioneffect' => 'crossfade',
			'pause_on_hover' => 'false',
			'next_prev_visibility' => 'tmls_visible',
			'next_prev_radius' => 'small_radius',
			'next_prev_position' => '',
			'next_prev_bgcolor' => '#F5F5F5',
			'next_prev_arrowscolor' => 'tmls_lightgrayarrows',
			'scroll_duration' => '500',
			'pause_duration' => '9000',
			'border_style' => 'tmls_border tmls_dashed_border',
			'border_color' => '#DDDDDD',
			'columns_number' => '2',
			'ratingstars' => 'enabled',
			'ratingstarssize' => '16px',
			'ratingstarscolor' => '#F47E00',
			'grayscale' => 'disabled',
			'slider2_unselectedoverlaybgcolor' => '#FFFFFF',
			'wpml_current_lang' =>''
		), $atts));
		
		$tmls_suppress_filters = false;
		
		// 	query posts
		
		
		if(function_exists('icl_object_id') && $wpml_current_lang != '') {
			global $sitepress;
			$sitepress->switch_lang($wpml_current_lang);
		}
		
		if($category != '-1') {
			$tmls_suppress_filters = true;
		}
		
		$args =	array ( 'post_type' => 'tmls',
						'post_status' => 'publish',
						'posts_per_page' => $number,
						'orderby' => $order_by,
						'order' => $order,
						'suppress_filters' => $tmls_suppress_filters	);
		
		if($category > -1) {
			$args['tax_query'] = array(array('taxonomy' => 'tmlscategory','field' => 'id','terms' => $category ));
		}
		
		$testimonials_query = new WP_Query( $args );
		
		$html='';

		if ($testimonials_query->have_posts()) {
			
			if($text_font_family!=''){
				$text_font_family='font-family:'.$text_font_family.';';
			}
			
			if($name_font_family!=''){
				$name_font_family='font-family:'.$name_font_family.';';
			}
			
			if($position_font_family!=''){
				$position_font_family='font-family:'.$position_font_family.';';
			}
			
			if($text_font_color!=''){
				$text_font_color='color:'.$text_font_color.';';
			}
			
			if($name_font_color!=''){
				$name_font_color='color:'.$name_font_color.';';
			}
			
			if($position_font_color!=''){
				$position_font_color='color:'.$position_font_color.';';
			}
			
			$grayscale_class='';
			
			if($grayscale=='enabled'){
				$grayscale_class='tmls_grayscale';
			}
			
			if($layout=='tmls_slider2') {
				$html='<div class="tmls tmls_notready style1" >';
			}
			else {
			
				if($layout=='tmls_grid') {
					$html='<div class="tmls tmls_overflow_hidden ';
				}
				if($layout=='tmls_slider') {
					$html='<div class="tmls tmls_notready ';
				}
				else {
					$html='<div class="tmls ';
				}
				
				if($style == 'style3') {
					$html.='tmls_style3_notready ';
				}
				
				$html.=$style.' '.$image_size.' '.$grayscale_class.'" >';
			}
			
			if($layout=='tmls_slider') {
				
				$tmls_next_prev_style='';
				
				if($next_prev_bgcolor!='') {
					$tmls_next_prev_style='background-color:'.$next_prev_bgcolor.';';
				}
				
				$html.='<div class="tmls_next_prev '.$next_prev_visibility.' '.$next_prev_position.'">
							<a href="#" style="'.$tmls_next_prev_style.'" class="tmls_prev '.$next_prev_radius.' '.$next_prev_arrowscolor.'"></a>
							<a href="#" style="'.$tmls_next_prev_style.'" class="tmls_next '.$next_prev_radius.' '.$next_prev_arrowscolor.'"></a>
						</div>
						
						<div class="tmls_container '.$layout.'" data-autoplay="'.$auto_play.'" data-pauseonhover="'.$pause_on_hover.'" data-scrollduration="'.$scroll_duration.'" data-pauseduration="'.$pause_duration.'" data-transitioneffect="'.$transitioneffect.'">';
			}
			elseif($layout=='tmls_slider2') {
				$html.='<div class="tmls_container '.$layout.'" data-autoplay="'.$auto_play.'" data-pauseonhover="'.$pause_on_hover.'" data-scrollduration="'.$scroll_duration.'" data-pauseduration="'.$pause_duration.'" data-transitioneffect="'.$transitioneffect.'" data-slider2unselectedoverlaybgcolor="'.$slider2_unselectedoverlaybgcolor.'">';
			}
			else {
						
				$html.='<div class="tmls_container '.$layout.' '.$border_style.'" >';
			
			}	
			
			$i = 0;
			$current_column=0;
			
			while ($i < $testimonials_query->post_count) {
			
				$post = $testimonials_query->posts;
				
				$thumbnailsrc='';
				$bg_img='';
				$company='';
				$company_website ='';
				$position='';
				
	
				// if has post thumbnail		
				if ( has_post_thumbnail($post[$i]->ID)) {
					$thumbnailsrc = wp_get_attachment_url(get_post_meta($post[$i]->ID, '_thumbnail_id', true));	
					$bg_img='background-image:url('.$thumbnailsrc.');';
				}
				
				if(get_post_meta($post[$i]->ID, 'company', true)!='') {
					
					if(get_post_meta($post[$i]->ID, 'company_website', true)!='') {
					
						$company_website = get_post_meta($post[$i]->ID, 'company_website', true);
						
						if (strpos($company_website, 'http://') === false && strpos($company_website, 'https://') === false ) {
							$company_website='http://'.get_post_meta($post[$i]->ID, 'company_website', true);
						}
						
						$company='<a style="'.$position_font_color.'" href="'.$company_website.'" target="'.get_post_meta($post[$i]->ID, 'company_link_target', true).'">'.get_post_meta($post[$i]->ID, 'company', true).'</a>';
					
					}
					else {
						$company=get_post_meta($post[$i]->ID, 'company', true);
					}
					
				}
				
				if(get_post_meta($post[$i]->ID, 'position', true)!='') {
					
					if(get_post_meta($post[$i]->ID, 'company', true)!='') {
						$position=get_post_meta($post[$i]->ID, 'position', true).' / ';
					}
					else {
						$position=get_post_meta($post[$i]->ID, 'position', true);
					}
					
				}
				
				
				
				if($layout=='tmls_slider') {
					include('layouts/slider.php');
				}
				elseif($layout=='tmls_slider2') {
					include('layouts/slider2.php');
				}
				elseif($layout=='tmls_grid') {
					include('layouts/grid.php');
				}
				else {
					include('layouts/list.php');
				}
				
				$i++;
				
			}
			
			$grid_column_class='';
			
			if($layout=='tmls_grid' && $current_column!=0) {
				
				while($current_column < $columns_number) {
					
					$html.='<div class="tmls_column '.$grid_column_class.'" style="width:'.(100/$columns_number).'%; border-color:'.$border_color.';"></div>';
					
					$grid_column_class='no_left_border';
					
					$current_column+=1;
				}
				
				$html.='</div>';
			}
			
			
			$html.='</div></div>';
			
			if($layout=='tmls_slider2') {
				$html.='<div class="tmls_images_pagination '.$image_size.' '.$grayscale_class.'"><div class="tmls_paginationContainer"></div></div>';
			}
			
		}
		
		return $html;  
		
			
		  
	}  
	add_shortcode('tmls', 'tmls_shortcode');
	
	
	
	// tmls_saved Shortcode
	
	function tmls_saved_shortcode( $atts ) {
	    
		extract( shortcode_atts( array(
		    'id' => ''
	    ), $atts ) );
		
		$tmls_sc = '';
		
		if($id != ''){
			$tmls_sc = get_post($id);
			
			if(get_post_meta($tmls_sc->ID, 'shortcode', true) != '') {
				return do_shortcode( get_post_meta($tmls_sc->ID, 'shortcode', true) );
			}
		}
		
	}
	
	add_shortcode( 'tmls_saved', 'tmls_saved_shortcode' );
	
?>