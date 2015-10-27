<?php
	
	// Function that outputs the contents of the dashboard widget
	function tmls_dashboard_widget_function( $post, $callback_args ) {
		
		$tmls_count_posts = wp_count_posts('tmls');
		
		if($tmls_count_posts->pending > 0) {
			echo 'There is <b><a href="'.admin_url('edit.php?post_status=pending&post_type=tmls').'" style="color:#d64e07;" >'.$tmls_count_posts->pending.' pending</a></b> testimonials.';
			
			$args =	array ( 'post_type' => 'tmls',
						'post_status' => 'pending',
						'posts_per_page' => 3,
						'suppress_filters' => true);
						
			$testimonials_query = new WP_Query( $args );
			
			if ($testimonials_query->have_posts()) {
				
				$i = 0;
				
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
							
							$company='<a style="'.$position_font_color.'" href="'.$company_website.'" target="_blank" >'.get_post_meta($post[$i]->ID, 'company', true).'</a>';
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
					
					
					echo '<div class="tmls_pending_testimonial">
							<div class="tmls_pending_img" style="'.$bg_img.'"></div>
							<div class="tmls_pending_text">'.get_post_meta($post[$i]->ID, 'testimonial_text', true).'
								</br></br>
								<b>'.get_post_meta($post[$i]->ID, 'name', true).'</b>
								</br>
								'.$position.$company.'
								</br>
								</br>
								<span class="tmls_pending_date">'.$post[$i]->post_date.'</span>
							</div>
							<a class="tmls_pending_edit" href="'.admin_url('post.php?post='.$post[$i]->ID.'&action=edit').'">Edit</a>
						</div>';
					
					$i++;
				}
				
				
					
			}
				
			echo '<div id="tmls_dashboard_widget_allPendingLink">
					<a href="'.admin_url('edit.php?post_status=pending&post_type=tmls&order_by=date&order=DESC').'">View all pending Testimonials</a>
				</div>';
		}
		else {
			echo "No pending testimonials found.";
		}
		
		echo '<div id="tmls_dashboard_status_list">
				<span><a href="'.admin_url('edit.php?post_status=publish&post_type=tmls').'">Published</a> ('.$tmls_count_posts->publish.')</span>
				<span><a href="'.admin_url('edit.php?post_status=pending&post_type=tmls').'">Pending</a> ('.$tmls_count_posts->pending.')</span>
				<span><a href="'.admin_url('edit.php?post_status=draft&post_type=tmls').'">Draft</a> ('.$tmls_count_posts->draft.')</span>
			</div>';
	
	}

	// Function used in the action hook
	function tmls_add_dashboard_widgets() {
		wp_add_dashboard_widget('dashboard_widget', 'Pending Testimonials', 'tmls_dashboard_widget_function');
	}

	// Register the new dashboard widget with the 'wp_dashboard_setup' action
	add_action('wp_dashboard_setup', 'tmls_add_dashboard_widgets' );

?>