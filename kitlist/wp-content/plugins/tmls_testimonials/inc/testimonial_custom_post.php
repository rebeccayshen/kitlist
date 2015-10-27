<?php

	/*========================================================================================================================================================================
		Register Testimonial Post Type
	========================================================================================================================================================================*/
	
	add_action('init', 'tmls_init');
	function tmls_init() 
	{
		/*----------------------------------------------------------------------
			testimonial Post Type Labels
		----------------------------------------------------------------------*/
		
		$labels = array(
			'name' => _x('Testimonials', 'Post type general name'),
			'singular_name' => _x('Testimonials', 'Post type singular name'),
			'add_new' => _x('Add new testimonial', 'Testimonial Item'),
			'add_new_item' => __('Add new testimonial'),
			'edit_item' => __('Edit testimonial'),
			'new_item' => __('New testimonial'),
			'all_items' => __('All testimonials'),
			'view_item' => __('View'),
			'search_items' => __('Search'),
			'not_found' =>  __('No testimonials found.'),
			'not_found_in_trash' => __('No testimonials found.'), 
			'parent_item_colon' => '',
			'menu_name' => 'Testimonials'
		);
		
		/*----------------------------------------------------------------------
			testimonial Post Type Properties
		----------------------------------------------------------------------*/
		
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true, 
			'show_in_menu' => true, 
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array('thumbnail','page-attributes')
		);
		
		/*----------------------------------------------------------------------
			testimonial Post Type Categories Register
		----------------------------------------------------------------------*/
		
		register_taxonomy(
			'tmlscategory',
			array('tmls'),
			array(
				'hierarchical' => true,
				'labels' => array( 'name'=>'Categories', 'add_new_item' => 'Add New Category', 'parent_item' => 'Parent Category'),
				'query_var' => true,
				'rewrite' => array( 'slug' => 'tmlscategory' )
			)
		);
		
		/*----------------------------------------------------------------------
			Register testimonial Post Type Function
		----------------------------------------------------------------------*/
		
		register_post_type('tmls',$args);
		
		//Enabling Support for Post Thumbnails
		add_theme_support( 'post-thumbnails');
	}
	
	
	/*========================================================================================================================================================================
		testimonial Post Type All Themes Table Columns
	========================================================================================================================================================================*/
	
	/*----------------------------------------------------------------------
		Columns Declaration Function
	----------------------------------------------------------------------*/
	function tmls_columns($tmls_columns){
		
		$order='asc';
		
		if($_GET['order']=='asc') {
			$order='desc';
		}
		
		$tmls_columns = array(

			"cb" => "<input type=\"checkbox\" />",
			
			"order" => "<a href='?post_type=tmls&orderby=menu_order&order=".$order."'>
								<span>Order</span>
								<span class='sorting-indicator'></span>
							</a>",
							
			"thumbnail" => "Image",

			"title" => "Name",
			
			"position" => "Position",
			
			"company" => "Company",
			
			"rating" => "Rating",

			"author" => "Author",
			
			"tmlscategories" => "Categories",
			
			"date" => "Date",

		);

		return $tmls_columns;

	}
	
	/*----------------------------------------------------------------------
		testimonial Value Function
	----------------------------------------------------------------------*/
	function tmls_columns_display($tmls_columns, $post_id){
		
		global $post;
		
		$width = (int) 80;
		$height = (int) 80;
		
		if ( 'thumbnail' == $tmls_columns ) {
			
			if ( has_post_thumbnail($post_id)) {
				$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );
				$thumb = wp_get_attachment_image( $thumbnail_id, array($width, $height), true );
				echo $thumb;
			}
			else 
			{
				echo __('None');
			}

		}
		
		if ( 'order' == $tmls_columns ) {
			echo $post->menu_order;
		}
		
		if ( 'position' == $tmls_columns ) {
			echo get_post_meta($post_id, 'position', true);
		}
		
		if ( 'company' == $tmls_columns ) {
			echo '<a href="http://'.get_post_meta($post_id, 'company_website', true).'" target="'.get_post_meta($post_id, 'company_link_target', true).'">'.get_post_meta($post_id, 'company', true).'</a>';
		}
		
		if ( 'rating' == $tmls_columns ) {
			if(get_post_meta($post_id, 'rating', true)!='') {
				echo "<div class='tmls-fa tmls_rating tmls_rating_".get_post_meta($post_id, 'rating', true)."'></div>";
			}
		}
		
		if ( 'tmlscategories' == $tmls_columns ) {
			
			$terms = get_the_terms( $post_id , 'tmlscategory');
			$count = count($terms);
			
			if ( $terms ){
				
				$i = 0;
				
				foreach ( $terms as $term ) {
					echo '<a href="'.admin_url( 'edit.php?post_type=tmls&tmlscategory='.$term->slug ).'">'.$term->name.'</a>';	
					
					if($i+1 != $count) {
						echo " , ";
					}
					$i++;
				}
				
			}
		}
		
	}
	
	/*----------------------------------------------------------------------
		Add manage_tmls_posts_columns Filter 
	----------------------------------------------------------------------*/
	add_filter("manage_tmls_posts_columns", "tmls_columns");
	
	/*----------------------------------------------------------------------
		Add manage_tmls_posts_custom_column Action
	----------------------------------------------------------------------*/
	add_action("manage_tmls_posts_custom_column",  "tmls_columns_display", 10, 2 );
	
	/*========================================================================================================================================================================
		Add Meta Box For testimonial Post Type
	========================================================================================================================================================================*/
	
	/*----------------------------------------------------------------------
		add_meta_boxes Action For testimonial Post Type
	----------------------------------------------------------------------*/
	
	add_action( 'add_meta_boxes', 'tmls_add_custom_box' );
	
	/*----------------------------------------------------------------------
		Properties Of testimonial Options Meta Box 
	----------------------------------------------------------------------*/
	
	function tmls_add_custom_box() {
		add_meta_box( 
			'tmls_sectionid',
			__( 'Options', 'tmls_textdomain' ),
			'tmls_inner_custom_box',
			'tmls'
		);
	}
	
	/*----------------------------------------------------------------------
		Content Of Testimonials Options Meta Box 
	----------------------------------------------------------------------*/
	
	function tmls_inner_custom_box( $post ) {

		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'tmls_noncename' );
		
		?>
					
		<!-- Styles -->
		<link rel="stylesheet" href="<?php echo get_template_directory_uri().'/css/admin.css'; ?>">

		
		<!-- Name -->
							
		<p><label for="title"><strong>Name</strong></label></p>
		
		<input type="text" name="post_title" id="title" class="regular-text code" value="<?php echo get_post_meta($post->ID, 'name', true); ?>" />
		
		<hr class="horizontalRuler"/>
		
		
		<!-- Position -->
							
		<p><label for="position_input"><strong>Position</strong></label></p>
		
		<input type="text" name="position_input" id="position_input" class="regular-text code" value="<?php echo get_post_meta($post->ID, 'position', true); ?>" />
		
		<hr class="horizontalRuler"/>
		
		<!-- Company Name -->
							
		<p><label for="company_input"><strong>Company Name</strong></label></p>
		
		<input type="text" name="company_input" id="company_input" class="regular-text code" value="<?php echo get_post_meta($post->ID, 'company', true); ?>" />
		
		<hr class="horizontalRuler"/>
		
		<!-- Company Website -->
							
		<p><label for="company_website_input"><strong>Company Website</strong></label></p>
		
		<input type="text" name="company_website_input" id="company_website_input" class="regular-text code" value="<?php echo get_post_meta($post->ID, 'company_website', true); ?>" />
							
		<p><span class="description">Example: (www.example.com)</span></p>
		
		<hr class="horizontalRuler"/>
		
		<!-- Company Link Target -->
		
		<p><label for="company_link_target_list"><strong>Company Link Target</strong></label></p>
			
		<select id="company_link_target_list" name="company_link_target_list">
			<option value="_blank" <?php if(get_post_meta($post->ID, 'company_link_target', true)=='_blank') { echo 'selected'; } ?> >blank</option>
			<option value="_self" <?php if(get_post_meta($post->ID, 'company_link_target', true)=='_self') { echo 'selected'; } ?> >self</option>
        </select>
		
		<hr class="horizontalRuler"/>
		
		<!-- Email -->
							
		<p><label for="email_input"><strong>Email</strong></label></p>
		
		<input type="text" name="email_input" id="email_input" class="regular-text code" value="<?php echo get_post_meta($post->ID, 'email', true); ?>" />
		
		<p><span class="description">Example: (email@example.com)</span></p>
		
		<hr class="horizontalRuler"/>
		
		<!-- Testimonial Text -->
							
		<p><label for="testimonial_text_input"><strong>Testimonial Text</strong></label></p>
		
		<textarea type="text" name="testimonial_text_input" id="testimonial_text_input" class="regular-text code" rows="5" cols="40" ><?php echo get_post_meta($post->ID, 'testimonial_text', true); ?></textarea>
		
		<hr class="horizontalRuler"/>
		
		<!-- Rating -->
		
		<p><label for="rating_list"><strong>Rating</strong></label></p>
			
		<select id="rating_list" name="rating_list">
			<option value="" <?php if(get_post_meta($post->ID, 'rating', true)=='') { echo 'selected'; } ?> >none</option>
			<option value="one_star" <?php if(get_post_meta($post->ID, 'rating', true)=='one_star') { echo 'selected'; } ?> >1 star</option>
			<option value="two_stars" <?php if(get_post_meta($post->ID, 'rating', true)=='two_stars') { echo 'selected'; } ?> >2 stars</option>
			<option value="three_stars" <?php if(get_post_meta($post->ID, 'rating', true)=='three_stars') { echo 'selected'; } ?> >3 stars</option>
			<option value="four_stars" <?php if(get_post_meta($post->ID, 'rating', true)=='four_stars') { echo 'selected'; } ?> >4 stars</option>
			<option value="five_stars" <?php if(get_post_meta($post->ID, 'rating', true)=='five_stars') { echo 'selected'; } ?> >5 stars</option>
        </select>
		
		<?php
	}
	
	/*========================================================================================================================================================================
		Save testimonial Options Meta Box Function
	========================================================================================================================================================================*/
	
	function tmls_save_meta_box($post_id) 
	{
		/*----------------------------------------------------------------------
			Name
		----------------------------------------------------------------------*/
		if(isset($_POST['post_title'])) {
			update_post_meta($post_id, 'name', $_POST['post_title']);
		}
	
		/*----------------------------------------------------------------------
			Position
		----------------------------------------------------------------------*/
		if(isset($_POST['position_input'])) {
			update_post_meta($post_id, 'position', $_POST['position_input']);
		}
		
		/*----------------------------------------------------------------------
			Company
		----------------------------------------------------------------------*/
		if(isset($_POST['company_input'])) {
			update_post_meta($post_id, 'company', $_POST['company_input']);
		}
		
		/*----------------------------------------------------------------------
			company website
		----------------------------------------------------------------------*/
		if(isset($_POST['company_website_input'])) {
			update_post_meta($post_id, 'company_website', $_POST['company_website_input']);
		}
		
		/*----------------------------------------------------------------------
			company link target
		----------------------------------------------------------------------*/
		if(isset($_POST['company_link_target_list'])) {
			update_post_meta($post_id, 'company_link_target', $_POST['company_link_target_list']);
		}
		
		/*----------------------------------------------------------------------
			email
		----------------------------------------------------------------------*/
		if(isset($_POST['email_input'])) {
			update_post_meta($post_id, 'email', $_POST['email_input']);
		}
		
		/*----------------------------------------------------------------------
			testimonial text
		----------------------------------------------------------------------*/
		if(isset($_POST['testimonial_text_input'])) {
			update_post_meta($post_id, 'testimonial_text', $_POST['testimonial_text_input']);
		}
		
		
		/*----------------------------------------------------------------------
			Rating
		----------------------------------------------------------------------*/
		if(isset($_POST['rating_list'])) {
			update_post_meta($post_id, 'rating', $_POST['rating_list']);
		}
		
	}
	
	/*----------------------------------------------------------------------
		Save testimonial Options Meta Box Action
	----------------------------------------------------------------------*/
	add_action('save_post', 'tmls_save_meta_box');

?>