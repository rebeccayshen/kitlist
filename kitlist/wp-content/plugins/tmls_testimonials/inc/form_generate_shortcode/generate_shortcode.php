<?php

	/*========================================================================================================================================================================
		Register tmls_form_sc Post Type
	========================================================================================================================================================================*/
	
	add_action('init', 'tmls_form_sc_init');
	function tmls_form_sc_init() 
	{
		/*----------------------------------------------------------------------
			tmls_form_sc Post Type Labels
		----------------------------------------------------------------------*/
		
		$labels = array(
			'name' => _x('Saved Shortcodes', 'Post type general name'),
			'singular_name' => _x('Saved Shortcodes', 'Post type singular name'),
			'add_new' => _x('Generate new shortcode', 'Submission form shortcode'),
			'add_new_item' => __('Generate new shortcode'),
			'edit_item' => __('Edit shortcode'),
			'new_item' => __('Generate shortcode'),
			'all_items' => __('Saved shortcodes'),
			'view_item' => __('View'),
			'search_items' => __('Search'),
			'not_found' =>  __('No shortcodes found.'),
			'not_found_in_trash' => __('No shortcodes found.'), 
			'parent_item_colon' => '',
			'menu_name' => 'Submission Forms Shortcodes'
		);
		
		/*----------------------------------------------------------------------
			tmls_form_sc Post Type Properties
		----------------------------------------------------------------------*/
		
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true, 
			'show_in_menu' => false, 
			'show_in_admin_bar' => false,
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array('title')
		);
		
		/*----------------------------------------------------------------------
			Register tmls_form_sc Post Type Function
		----------------------------------------------------------------------*/
		
		register_post_type('tmls_form_sc',$args);

	}
	
	
	/*========================================================================================================================================================================
		tmls_form_sc Post Type All Themes Table Columns
	========================================================================================================================================================================*/
	
	/*----------------------------------------------------------------------
		tmls_form_sc Declaration Function
	----------------------------------------------------------------------*/
	function tmls_form_sc_columns($tmls_sc_columns){
		
		$tmls_form_sc_columns = array(

			"cb" => "<input type=\"checkbox\" />",

			"title" => "Title",
			
			"shortcode" => "Shortcode",

			"author" => "Author",
			
			"date" => "Date",

		);

		return $tmls_form_sc_columns;

	}
	
	/*----------------------------------------------------------------------
		tmls_form_sc Value Function
	----------------------------------------------------------------------*/
	function tmls_form_sc_columns_display($tmls_form_sc_columns, $post_id){
		
		global $post;
		
		if ( 'shortcode' == $tmls_form_sc_columns ) {
			
			echo '[tmls_form_saved id="'.$post_id.'"]';
		
		}
		
	}
	
	/*----------------------------------------------------------------------
		Add manage_tmls_form_sc_posts_columns Filter 
	----------------------------------------------------------------------*/
	add_filter("manage_tmls_form_sc_posts_columns", "tmls_form_sc_columns");
	
	/*----------------------------------------------------------------------
		Add manage_tmls_form_sc_posts_custom_column Action
	----------------------------------------------------------------------*/
	add_action("manage_tmls_form_sc_posts_custom_column",  "tmls_form_sc_columns_display", 10, 2 );
	
	/*========================================================================================================================================================================
		Add Meta Box For tmls_sc Post Type
	========================================================================================================================================================================*/
	
	/*----------------------------------------------------------------------
		add_meta_boxes Action For tmls_form_sc Post Type
	----------------------------------------------------------------------*/
	
	add_action( 'add_meta_boxes', 'tmls_form_sc_add_custom_box' );
	
	/*----------------------------------------------------------------------
		Properties Of tmls_form_sc Meta Boxes 
	----------------------------------------------------------------------*/
	
	function tmls_form_sc_add_custom_box() {
		add_meta_box( 
			'tmls_form_sc_options_metabox',
			'Options',
			'tmls_form_sc_options_metabox',
			'tmls_form_sc',
			'side'
		);
		
		add_meta_box( 
			'tmls_form_sc_preview_metabox',
			'Preview',
			'tmls_form_sc_preview_metabox',
			'tmls_form_sc',
			'advanced'
		);
	}
	
	/*----------------------------------------------------------------------
		Content Of tmls_form_sc Options Meta Box 
	----------------------------------------------------------------------*/
	
	function tmls_form_sc_preview_metabox( $post ) {
		?>
		<p id="noteParagraph">
			<strong>Note: </strong>Please copy and paste a shortcode in the yellow box below into page, post editor and testimonials widget. 
			<?php if ( defined( 'WPB_VC_VERSION' ) ) { echo 'Also you can use testimonials submission form element into visual composer page builder to insert saved shortcodes.'; } ?>
		</p>
		
		<div id="tmls_form_div_shortcode" style="<?php if($post->post_status !='auto-draft'){ echo 'display:none;'; } ?>" >[tmls_form]</div>
		
		<div id="tmls_form_div_shortcode_saved" style="<?php if($post->post_status =='auto-draft'){ echo 'display:none;'; } ?>">[tmls_form_saved id="<?php echo $post->ID; ?>"]</div>
		
		<input type="hidden" name="tmls_form_shortcode" id="tmls_form_shortcode" value="<?php echo get_post_meta($post->ID, 'shortcode', true); ?>" />
		
		<div id="tmls_form_gene_short_preview">loading ...</div>
		<?php
	}
	
	
	/*----------------------------------------------------------------------
		Content Of tmls_form_sc Options Meta Box 
	----------------------------------------------------------------------*/
	
	function tmls_form_sc_options_metabox( $post ) {
		
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'tmls_form_noncename' );
		
		if($_GET['action']=='edit') {
			
			// form width
			$form_width = get_post_meta($post->ID, 'form_width', true);
			
			// position
			$position = get_post_meta($post->ID, 'position', true);
			
			// company
			$company = get_post_meta($post->ID, 'company', true);
			
			// rating
			$rating = get_post_meta($post->ID, 'rating', true);
			
			// image
			$image = get_post_meta($post->ID, 'image', true);
			
			// notification email
			$notificationemail = get_post_meta($post->ID, 'notificationemail', true);
			
			// email to
			$emailto = get_post_meta($post->ID, 'emailto', true);
			
			// email subject
			$emailsubject = get_post_meta($post->ID, 'emailsubject', true);
			
			// email message
			$emailmessage = get_post_meta($post->ID, 'emailmessage', true);
			
			// success message
			$success_message = get_post_meta($post->ID, 'success_message', true);
			
			// name required message
			$namerequired_message = get_post_meta($post->ID, 'namerequired_message', true);
			
			// email required message
			$emailrequired_message = get_post_meta($post->ID, 'emailrequired_message', true);
			
			// testimonial required message
			$testimonialrequired_message = get_post_meta($post->ID, 'testimonialrequired_message', true);
			
			// invalid email message
			$invalidemail_message = get_post_meta($post->ID, 'invalidemail_message', true);
			
			// invalid company website message
			$invalidcompanywebsite_message = get_post_meta($post->ID, 'invalidcompanywebsite_message', true);
			
			// image failed message
			$imagefailed_message = get_post_meta($post->ID, 'imagefailed_message', true);
			
			// select image again message
			$selectimageagain_message = get_post_meta($post->ID, 'selectimageagain_message', true);
			
			// invalid captcha answer message
			$invalidcaptchaanswer_message = get_post_meta($post->ID, 'invalidcaptchaanswer_message', true);
			
			// captcha answer required message
			$captchaanswerrequired_message = get_post_meta($post->ID, 'captchaanswerrequired_message', true);
			
			// already sent message
			$alreadysent_message = get_post_meta($post->ID, 'alreadysent_message', true);
			
			// label font color
			$label_fontcolor = get_post_meta($post->ID, 'label_fontcolor', true);
			
			// label font size
			$label_fontsize = get_post_meta($post->ID, 'label_fontsize', true);
			
			// label font weight
			$label_fontweight = get_post_meta($post->ID, 'label_fontweight', true);
			
			// label font family
			$label_fontfamily = get_post_meta($post->ID, 'label_fontfamily', true);
			
			// validation message font color
			$validationmessage_fontcolor = get_post_meta($post->ID, 'validationmessage_fontcolor', true);
			
			// validation message font size
			$validationmessage_fontsize = get_post_meta($post->ID, 'validationmessage_fontsize', true);
			
			// validation message font weight
			$validationmessage_fontweight = get_post_meta($post->ID, 'validationmessage_fontweight', true);
			
			// validation message font family
			$validationmessage_fontfamily = get_post_meta($post->ID, 'validationmessage_fontfamily', true);
			
			// success message font color
			$successmessage_fontcolor = get_post_meta($post->ID, 'successmessage_fontcolor', true);
			
			// success message font size
			$successmessage_fontsize = get_post_meta($post->ID, 'successmessage_fontsize', true);
			
			// success message font weight
			$successmessage_fontweight = get_post_meta($post->ID, 'successmessage_fontweight', true);
			
			// success message font family
			$successmessage_fontfamily = get_post_meta($post->ID, 'successmessage_fontfamily', true);
			
			// inputs font color
			$inputs_fontcolor = get_post_meta($post->ID, 'inputs_fontcolor', true);
			
			// inputs font size
			$inputs_fontsize = get_post_meta($post->ID, 'inputs_fontsize', true);
			
			// inputs font weight
			$inputs_fontweight = get_post_meta($post->ID, 'inputs_fontweight', true);
			
			// inputs font family
			$inputs_fontfamily = get_post_meta($post->ID, 'inputs_fontfamily', true);
			
			// inputs border color
			$inputs_bordercolor = get_post_meta($post->ID, 'inputs_bordercolor', true);
			
			// inputs bg color
			$inputs_bgcolor = get_post_meta($post->ID, 'inputs_bgcolor', true);
			
			// inputs border radius
			$inputs_borderradius = get_post_meta($post->ID, 'inputs_borderradius', true);
			
			// name label text
			$name_label_text = get_post_meta($post->ID, 'name_label_text', true);
			
			// position label text
			$position_label_text = get_post_meta($post->ID, 'position_label_text', true);
			
			// company name label text
			$companyname_label_text = get_post_meta($post->ID, 'companyname_label_text', true);
			
			// company website label text
			$companywebsite_label_text = get_post_meta($post->ID, 'companywebsite_label_text', true);
			
			// email label text
			$email_label_text = get_post_meta($post->ID, 'email_label_text', true);
			
			// rating label text
			$rating_label_text = get_post_meta($post->ID, 'rating_label_text', true);
			
			// testimonial label text
			$testimonial_label_text = get_post_meta($post->ID, 'testimonial_label_text', true);
			
			// image label text
			$image_label_text = get_post_meta($post->ID, 'image_label_text', true);
			
			// captcha label text
			$captcha_label_text = get_post_meta($post->ID, 'captcha_label_text', true);
			
			// button text
			$button_text = get_post_meta($post->ID, 'button_text', true);
			
			// button font color
			$button_fontcolor = get_post_meta($post->ID, 'button_fontcolor', true);
			
			// button font size
			$button_fontsize = get_post_meta($post->ID, 'button_fontsize', true);
			
			// button font weight
			$button_fontweight = get_post_meta($post->ID, 'button_fontweight', true);
			
			// button font family
			$button_fontfamily = get_post_meta($post->ID, 'button_fontfamily', true);
			
			// button border color
			$button_bordercolor = get_post_meta($post->ID, 'button_bordercolor', true);
			
			// button bg color
			$button_bgcolor = get_post_meta($post->ID, 'button_bgcolor', true);
			
			// button border radius
			$button_borderradius = get_post_meta($post->ID, 'button_borderradius', true);
			
			// button hover fontcolor
			$button_hover_fontcolor = get_post_meta($post->ID, 'button_hover_fontcolor', true);
			
			// button hover border color
			$button_hover_bordercolor = get_post_meta($post->ID, 'button_hover_bordercolor', true);
			
			// button hover bg color
			$button_hover_bgcolor = get_post_meta($post->ID, 'button_hover_bgcolor', true);
		
		}
		else {
			
			$form_width = 450;
			$position ='enabled';
			$company ='enabled';
			$rating ='enabled';
			$image ='disabled';
			$notificationemail = 'disabled';
			$emailto = 1;
			$emailsubject = 'A new testimonial has been received';
			$emailmessage = 'A new testimonial message is waiting for approval.';
			$success_message = 'Thank you! Your testimonial has been successfully sent';
			$namerequired_message = 'Name is required';
			$emailrequired_message = 'Email is required';
			$testimonialrequired_message = 'Testimonial is required';
			$invalidemail_message = 'Invalid email format';
			$invalidcompanywebsite_message = 'Invalid company website URL';
			$imagefailed_message = 'Image has failed to upload';
			$selectimageagain_message = '<b>Note:</b> Please select image again';
			$invalidcaptchaanswer_message = 'Invalid captcha answer';
			$captchaanswerrequired_message = 'Captcha answer is required';
			$alreadysent_message = 'Your testimonial already sent';
			$label_fontcolor = '#999999';
			$label_fontsize = '13px';
			$label_fontweight = 'normal';
			$label_fontfamily = '';
			$validationmessage_fontcolor = '#e14d43';
			$validationmessage_fontsize = '13px';
			$validationmessage_fontweight = 'normal';
			$validationmessage_fontfamily = '';
			$successmessage_fontcolor = '#a3b745';
			$successmessage_fontsize = '13px';
			$successmessage_fontweight = 'normal';
			$successmessage_fontfamily = '';
			$inputs_fontcolor = '#999999';
			$inputs_fontsize = '12px';
			$inputs_fontweight = 'normal';
			$inputs_fontfamily = '';
			$inputs_bordercolor = '#eeeeee';
			$inputs_bgcolor = 'transparent';
			$inputs_borderradius = 'small_radius';
			$name_label_text = 'Name';
			$position_label_text = 'Position';
			$companyname_label_text = 'Company Name';
			$companywebsite_label_text = 'Company Website';
			$email_label_text = 'Email';
			$rating_label_text = 'Rating';
			$testimonial_label_text = 'Testimonial';
			$image_label_text = 'Image';
			$captcha_label_text = 'Are you Human?';
			$button_text = 'SEND TESTIMONIAL';
			$button_fontcolor = '#999999';
			$button_fontsize = '12px';
			$button_fontweight = 'bold';
			$button_fontfamily = '';
			$button_bordercolor = '#eeeeee';
			$button_bgcolor = 'transparent';
			$button_borderradius = 'small_radius';
			$button_hover_fontcolor = '#999999';
			$button_hover_bordercolor = '#eeeeee';
			$button_hover_bgcolor = '#f5f5f5';
			
		}
		
		?>
		
		<div id="tmls_gene_short_leftSidebar">
		
			<div class="tmls_sectionTitle">General Settings</div>
			
			<div class="tmls_rowsContainer tmls_rowsContainerOpend" >
				<div class="row">
					<label for="tmls_form_width">Form Width (px)</label>
					<input type="text" id="tmls_form_width" name="tmls_form_width" value="<?php echo $form_width; ?>" />
				</div>
				
				<div class="row">
					<label for="tmls_position">Position</label>
					<select id="tmls_position" name="tmls_position">
						<option value="enabled" <?php if($position == 'enabled'){ echo 'selected'; } ?> >Enabled</option>
						<option value="disabled" <?php if($position == 'disabled'){ echo 'selected'; } ?> >Disabled</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_company">Company</label>
					<select id="tmls_company" name="tmls_company">
						<option value="enabled" <?php if($company == 'enabled'){ echo 'selected'; } ?> >Enabled</option>
						<option value="disabled" <?php if($company == 'disabled'){ echo 'selected'; } ?> >Disabled</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_rating">Rating</label>
					<select id="tmls_rating" name="tmls_rating">
						<option value="enabled" <?php if($rating == 'enabled'){ echo 'selected'; } ?> >Enabled</option>
						<option value="disabled" <?php if($rating == 'disabled'){ echo 'selected'; } ?> >Disabled</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_image">Image</label>
					<select id="tmls_image" name="tmls_image">
						<option value="enabled" <?php if($image == 'enabled'){ echo 'selected'; } ?> >Enabled</option>
						<option value="disabled" <?php if($image == 'disabled'){ echo 'selected'; } ?> >Disabled</option>
					</select>
				</div>
				
			</div>
			
			<div class="tmls_sectionTitle">Notification email</div>
			
			<div class="tmls_rowsContainer" >
				<div class="row">
					<label for="tmls_notificationEmail">Notification Email</label>
					<select id="tmls_notificationEmail" name="tmls_notificationEmail">
						<option value="enabled" <?php if($notificationemail == 'enabled'){ echo 'selected'; } ?> >Enabled</option>
						<option value="disabled" <?php if($notificationemail == 'disabled'){ echo 'selected'; } ?> >Disabled</option>
					</select>
				</div>
				
				<div class="row notificationEmail_options">
					<label for="tmls_emailTo">Send Email To</label>
					<?php wp_dropdown_users(array('name' => 'tmls_emailTo','id' => 'tmls_emailTo','selected'=>$emailto)); ?>
				</div>
				
				<div class="row notificationEmail_options">
					<label for="tmls_emailSubject">Notification Email Subject</label>
					<input type="text" id="tmls_emailSubject" name="tmls_emailSubject" value="<?php echo $emailsubject; ?>" />
				</div>
				
				<div class="row notificationEmail_options">
					<label for="tmls_emailMessage">Notification Email Message</label>
					<textarea id="tmls_emailMessage" name="tmls_emailMessage" ><?php echo $emailmessage; ?></textarea>
				</div>
			</div>
			
			
			<div class="tmls_sectionTitle">Labels Text</div>
			
			<div class="tmls_rowsContainer" >
				<div class="row">
					<label for="tmls_name_label_text">Name Label Text</label>
					<input type="text" id="tmls_name_label_text" name="tmls_name_label_text" value="<?php echo $name_label_text; ?>" />
				</div>
				
				<div class="row tmls_position_option">
					<label for="tmls_position_label_text">Position Label Text</label>
					<input type="text" id="tmls_position_label_text" name="tmls_position_label_text" value="<?php echo $position_label_text; ?>" />
				</div>
				
				<div class="row tmls_company_option">
					<label for="tmls_companyname_label_text">Company Name label Text</label>
					<input type="text" id="tmls_companyname_label_text" name="tmls_companyname_label_text" value="<?php echo $companyname_label_text; ?>" />
				</div>
				
				<div class="row tmls_company_option">
					<label for="tmls_companywebsite_label_text">Company Website label Text</label>
					<input type="text" id="tmls_companywebsite_label_text" name="tmls_companywebsite_label_text" value="<?php echo $companywebsite_label_text; ?>" />
				</div>
				
				<div class="row">
					<label for="tmls_email_label_text">Email Label Text</label>
					<input type="text" id="tmls_email_label_text" name="tmls_email_label_text" value="<?php echo $email_label_text; ?>" />
				</div>
				
				<div class="row tmls_rating_option">
					<label for="tmls_rating_label_text">Rating Label Text</label>
					<input type="text" id="tmls_rating_label_text" name="tmls_rating_label_text" value="<?php echo $rating_label_text; ?>" />
				</div>
				
				<div class="row">
					<label for="tmls_testimonial_label_text">Testimonial Label Text</label>
					<input type="text" id="tmls_testimonial_label_text" name="tmls_testimonial_label_text" value="<?php echo $testimonial_label_text; ?>" />
				</div>
				
				<div class="row tmls_image_option">
					<label for="tmls_image_label_text">Image Label Text</label>
					<input type="text" id="tmls_image_label_text" name="tmls_image_label_text" value="<?php echo $image_label_text; ?>" />
				</div>
				
				<div class="row">
					<label for="tmls_captcha_label_text">Captcha Label Text</label>
					<input type="text" id="tmls_captcha_label_text" name="tmls_captcha_label_text" value="<?php echo $captcha_label_text; ?>" />
				</div>
			</div>
			
			<div class="tmls_sectionTitle">Labels Style</div>
			
			<div class="tmls_rowsContainer" >
				<div class="row">
					<label for="tmls_label_fontcolor">Labels Font Color</label>
					<input type="text" id="tmls_label_fontcolor" name="tmls_label_fontcolor" value="<?php echo $label_fontcolor; ?>" />
					<div id="tmls_label_fontcolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_label_fontcolor_btn" name="tmls_label_fontcolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<div class="row">
					<label for="tmls_label_fontsize">Labels Font Size (px)</label>
					<select id="tmls_label_fontsize" name="tmls_label_fontsize">
						<option value="9px" <?php if($label_fontsize == '9px'){ echo 'selected'; } ?> >9px</option>
						<option value="10px" <?php if($label_fontsize == '10px'){ echo 'selected'; } ?> >10px</option>
						<option value="11px" <?php if($label_fontsize == '11px'){ echo 'selected'; } ?> >11px</option>
						<option value="12px" <?php if($label_fontsize == '12px'){ echo 'selected'; } ?> >12px</option>
						<option value="13px" <?php if($label_fontsize == '13px'){ echo 'selected'; } ?> >13px</option>
						<option value="14px" <?php if($label_fontsize == '14px'){ echo 'selected'; } ?> >14px</option>
						<option value="15px" <?php if($label_fontsize == '15px'){ echo 'selected'; } ?> >15px</option>
						<option value="16px" <?php if($label_fontsize == '16px'){ echo 'selected'; } ?> >16px</option>
						<option value="17px" <?php if($label_fontsize == '17px'){ echo 'selected'; } ?> >17px</option>
						<option value="18px" <?php if($label_fontsize == '18px'){ echo 'selected'; } ?> >18px</option>
						<option value="19px" <?php if($label_fontsize == '19px'){ echo 'selected'; } ?> >19px</option>
						<option value="20px" <?php if($label_fontsize == '20px'){ echo 'selected'; } ?> >20px</option>
						<option value="21px" <?php if($label_fontsize == '21px'){ echo 'selected'; } ?> >21px</option>
						<option value="22px" <?php if($label_fontsize == '22px'){ echo 'selected'; } ?> >22px</option>
						<option value="23px" <?php if($label_fontsize == '23px'){ echo 'selected'; } ?> >23px</option>
						<option value="24px" <?php if($label_fontsize == '24px'){ echo 'selected'; } ?> >24px</option>
						<option value="25px" <?php if($label_fontsize == '25px'){ echo 'selected'; } ?> >25px</option>
						<option value="26px" <?php if($label_fontsize == '26px'){ echo 'selected'; } ?> >26px</option>
						<option value="27px" <?php if($label_fontsize == '27px'){ echo 'selected'; } ?> >27px</option>
						<option value="28px" <?php if($label_fontsize == '28px'){ echo 'selected'; } ?> >28px</option>
						<option value="29px" <?php if($label_fontsize == '29px'){ echo 'selected'; } ?> >29px</option>
						<option value="30px" <?php if($label_fontsize == '30px'){ echo 'selected'; } ?> >30px</option>
						<option value="31px" <?php if($label_fontsize == '31px'){ echo 'selected'; } ?> >31px</option>
						<option value="32px" <?php if($label_fontsize == '32px'){ echo 'selected'; } ?> >32px</option>
						<option value="33px" <?php if($label_fontsize == '33px'){ echo 'selected'; } ?> >33px</option>
						<option value="34px" <?php if($label_fontsize == '34px'){ echo 'selected'; } ?> >34px</option>
						<option value="35px" <?php if($label_fontsize == '35px'){ echo 'selected'; } ?> >35px</option>
						<option value="36px" <?php if($label_fontsize == '36px'){ echo 'selected'; } ?> >36px</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_label_fontweight">Labels Font Weight</label>
					<select id="tmls_label_fontweight" name="tmls_label_fontweight">
						<option value="bold" <?php if($label_fontweight == 'bold'){ echo 'selected'; } ?> >bold</option>
						<option value="normal" <?php if($label_fontweight == 'normal'){ echo 'selected'; } ?> >normal</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_label_fontfamily">Labels Font Family</label>
					<select id="tmls_label_fontfamily" name="tmls_label_fontfamily">
						<option value="" <?php if($label_fontfamily == ""){ echo 'selected'; } ?> >current theme font</option>
						<option value="Georgia, serif" <?php if($label_fontfamily == "Georgia, serif"){ echo 'selected'; } ?> >Georgia</option>
						<option value="'Palatino Linotype', 'Book Antiqua', Palatino, serif" <?php if($label_fontfamily == "'Palatino Linotype', 'Book Antiqua', Palatino, serif"){ echo 'selected'; } ?> >Palatino Linotype</option>
						<option value="'Times New Roman', Times, serif" <?php if($label_fontfamily == "'Times New Roman', Times, serif"){ echo 'selected'; } ?> >Times New Roman</option>
						<option value="Arial, Helvetica, sans-serif" <?php if($label_fontfamily == "Arial, Helvetica, sans-serif"){ echo 'selected'; } ?> >Arial</option>
						<option value="'Arial Black', Gadget, sans-serif" <?php if($label_fontfamily == "'Arial Black', Gadget, sans-serif"){ echo 'selected'; } ?> >Arial Black</option>
						<option value="'Comic Sans MS', cursive, sans-serif" <?php if($label_fontfamily == "'Comic Sans MS', cursive, sans-serif"){ echo 'selected'; } ?> >Comic Sans MS</option>
						<option value="Impact, Charcoal, sans-serif" <?php if($label_fontfamily == "Impact, Charcoal, sans-serif"){ echo 'selected'; } ?> >Impact</option>
						<option value="'Lucida Sans Unicode', 'Lucida Grande', sans-serif" <?php if($label_fontfamily == "'Lucida Sans Unicode', 'Lucida Grande', sans-serif"){ echo 'selected'; } ?> >Lucida Sans Unicode</option>
						<option value="Tahoma, Geneva, sans-serif" <?php if($label_fontfamily == "Tahoma, Geneva, sans-serif"){ echo 'selected'; } ?> >Tahoma</option>
						<option value="'Trebuchet MS', Helvetica, sans-serif" <?php if($label_fontfamily == "'Trebuchet MS', Helvetica, sans-serif"){ echo 'selected'; } ?> >Trebuchet MS</option>
						<option value="Verdana, Geneva, sans-serif" <?php if($label_fontfamily == "Verdana, Geneva, sans-serif"){ echo 'selected'; } ?> >Verdana</option>
						<option value="'Courier New', Courier, monospace" <?php if($label_fontfamily == "'Courier New', Courier, monospace"){ echo 'selected'; } ?> >Courier New</option>
						<option value="'Lucida Console', Monaco, monospace" <?php if($label_fontfamily == "'Lucida Console', Monaco, monospace"){ echo 'selected'; } ?> >Lucida Console</option>
					</select>
				</div>
			
			</div>
			
			<div class="tmls_sectionTitle">Inputs Style</div>
			
			<div class="tmls_rowsContainer" >
				<div class="row">
					<label for="tmls_inputs_fontcolor">Inputs Font Color</label>
					<input type="text" id="tmls_inputs_fontcolor" name="tmls_inputs_fontcolor" value="<?php echo $inputs_fontcolor; ?>" />
					<div id="tmls_inputs_fontcolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_inputs_fontcolor_btn" name="tmls_inputs_fontcolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<div class="row">
					<label for="tmls_inputs_fontsize">Inputs Font Size (px)</label>
					<select id="tmls_inputs_fontsize" name="tmls_inputs_fontsize">
						<option value="9px" <?php if($inputs_fontsize == '9px'){ echo 'selected'; } ?> >9px</option>
						<option value="10px" <?php if($inputs_fontsize == '10px'){ echo 'selected'; } ?> >10px</option>
						<option value="11px" <?php if($inputs_fontsize == '11px'){ echo 'selected'; } ?> >11px</option>
						<option value="12px" <?php if($inputs_fontsize == '12px'){ echo 'selected'; } ?> >12px</option>
						<option value="13px" <?php if($inputs_fontsize == '13px'){ echo 'selected'; } ?> >13px</option>
						<option value="14px" <?php if($inputs_fontsize == '14px'){ echo 'selected'; } ?> >14px</option>
						<option value="15px" <?php if($inputs_fontsize == '15px'){ echo 'selected'; } ?> >15px</option>
						<option value="16px" <?php if($inputs_fontsize == '16px'){ echo 'selected'; } ?> >16px</option>
						<option value="17px" <?php if($inputs_fontsize == '17px'){ echo 'selected'; } ?> >17px</option>
						<option value="18px" <?php if($inputs_fontsize == '18px'){ echo 'selected'; } ?> >18px</option>
						<option value="19px" <?php if($inputs_fontsize == '19px'){ echo 'selected'; } ?> >19px</option>
						<option value="20px" <?php if($inputs_fontsize == '20px'){ echo 'selected'; } ?> >20px</option>
						<option value="21px" <?php if($inputs_fontsize == '21px'){ echo 'selected'; } ?> >21px</option>
						<option value="22px" <?php if($inputs_fontsize == '22px'){ echo 'selected'; } ?> >22px</option>
						<option value="23px" <?php if($inputs_fontsize == '23px'){ echo 'selected'; } ?> >23px</option>
						<option value="24px" <?php if($inputs_fontsize == '24px'){ echo 'selected'; } ?> >24px</option>
						<option value="25px" <?php if($inputs_fontsize == '25px'){ echo 'selected'; } ?> >25px</option>
						<option value="26px" <?php if($inputs_fontsize == '26px'){ echo 'selected'; } ?> >26px</option>
						<option value="27px" <?php if($inputs_fontsize == '27px'){ echo 'selected'; } ?> >27px</option>
						<option value="28px" <?php if($inputs_fontsize == '28px'){ echo 'selected'; } ?> >28px</option>
						<option value="29px" <?php if($inputs_fontsize == '29px'){ echo 'selected'; } ?> >29px</option>
						<option value="30px" <?php if($inputs_fontsize == '30px'){ echo 'selected'; } ?> >30px</option>
						<option value="31px" <?php if($inputs_fontsize == '31px'){ echo 'selected'; } ?> >31px</option>
						<option value="32px" <?php if($inputs_fontsize == '32px'){ echo 'selected'; } ?> >32px</option>
						<option value="33px" <?php if($inputs_fontsize == '33px'){ echo 'selected'; } ?> >33px</option>
						<option value="34px" <?php if($inputs_fontsize == '34px'){ echo 'selected'; } ?> >34px</option>
						<option value="35px" <?php if($inputs_fontsize == '35px'){ echo 'selected'; } ?> >35px</option>
						<option value="36px" <?php if($inputs_fontsize == '36px'){ echo 'selected'; } ?> >36px</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_inputs_fontweight">Inputs Font Weight</label>
					<select id="tmls_inputs_fontweight" name="tmls_inputs_fontweight">
						<option value="bold" <?php if($inputs_fontweight == 'bold'){ echo 'selected'; } ?> >bold</option>
						<option value="normal" <?php if($inputs_fontweight == 'normal'){ echo 'selected'; } ?> >normal</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_inputs_fontfamily">Inputs Font Family</label>
					<select id="tmls_inputs_fontfamily" name="tmls_inputs_fontfamily">
						<option value="" <?php if($inputs_fontfamily == ""){ echo 'selected'; } ?> >current theme font</option>
						<option value="Georgia, serif" <?php if($inputs_fontfamily == "Georgia, serif"){ echo 'selected'; } ?> >Georgia</option>
						<option value="'Palatino Linotype', 'Book Antiqua', Palatino, serif" <?php if($inputs_fontfamily == "'Palatino Linotype', 'Book Antiqua', Palatino, serif"){ echo 'selected'; } ?> >Palatino Linotype</option>
						<option value="'Times New Roman', Times, serif" <?php if($inputs_fontfamily == "'Times New Roman', Times, serif"){ echo 'selected'; } ?> >Times New Roman</option>
						<option value="Arial, Helvetica, sans-serif" <?php if($inputs_fontfamily == "Arial, Helvetica, sans-serif"){ echo 'selected'; } ?> >Arial</option>
						<option value="'Arial Black', Gadget, sans-serif" <?php if($inputs_fontfamily == "'Arial Black', Gadget, sans-serif"){ echo 'selected'; } ?> >Arial Black</option>
						<option value="'Comic Sans MS', cursive, sans-serif" <?php if($inputs_fontfamily == "'Comic Sans MS', cursive, sans-serif"){ echo 'selected'; } ?> >Comic Sans MS</option>
						<option value="Impact, Charcoal, sans-serif" <?php if($inputs_fontfamily == "Impact, Charcoal, sans-serif"){ echo 'selected'; } ?> >Impact</option>
						<option value="'Lucida Sans Unicode', 'Lucida Grande', sans-serif" <?php if($inputs_fontfamily == "'Lucida Sans Unicode', 'Lucida Grande', sans-serif"){ echo 'selected'; } ?> >Lucida Sans Unicode</option>
						<option value="Tahoma, Geneva, sans-serif" <?php if($inputs_fontfamily == "Tahoma, Geneva, sans-serif"){ echo 'selected'; } ?> >Tahoma</option>
						<option value="'Trebuchet MS', Helvetica, sans-serif" <?php if($inputs_fontfamily == "'Trebuchet MS', Helvetica, sans-serif"){ echo 'selected'; } ?> >Trebuchet MS</option>
						<option value="Verdana, Geneva, sans-serif" <?php if($inputs_fontfamily == "Verdana, Geneva, sans-serif"){ echo 'selected'; } ?> >Verdana</option>
						<option value="'Courier New', Courier, monospace" <?php if($inputs_fontfamily == "'Courier New', Courier, monospace"){ echo 'selected'; } ?> >Courier New</option>
						<option value="'Lucida Console', Monaco, monospace" <?php if($inputs_fontfamily == "'Lucida Console', Monaco, monospace"){ echo 'selected'; } ?> >Lucida Console</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_inputs_bordercolor">Inputs Border Color</label>
					<input type="text" id="tmls_inputs_bordercolor" name="tmls_inputs_bordercolor" value="<?php echo $inputs_bordercolor; ?>" />
					<div id="tmls_inputs_bordercolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_inputs_bordercolor_btn" name="tmls_inputs_bordercolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<div class="row">
					<label for="tmls_inputs_bgcolor">Inputs Background Color</label>
					<input type="text" id="tmls_inputs_bgcolor" name="tmls_inputs_bgcolor" value="<?php echo $inputs_bgcolor; ?>" />
					<div id="tmls_inputs_bgcolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_inputs_bgcolor_btn" name="tmls_inputs_bgcolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<div class="row">
					<label for="tmls_inputs_borderradius">Inputs Border Radius</label>
					<select id="tmls_inputs_borderradius" name="tmls_inputs_borderradius">
						<option value="large_radius" <?php if($inputs_borderradius == 'large_radius'){ echo 'selected'; } ?> >large radius</option>
						<option value="medium_radius" <?php if($inputs_borderradius == 'medium_radius'){ echo 'selected'; } ?> >medium radius</option>
						<option value="small_radius" <?php if($inputs_borderradius == 'small_radius'){ echo 'selected'; } ?> >small radius</option>
						<option value="no_radius" <?php if($inputs_borderradius == 'no_radius'){ echo 'selected'; } ?> >without radius</option>
					</select>
				</div>
			</div>
			
			<div class="tmls_sectionTitle">Messages Text</div>
			
			<div class="tmls_rowsContainer" >
				<div class="row">
					<label for="tmls_success_message">Success Message</label>
					<input type="text" id="tmls_success_message" name="tmls_success_message" value="<?php echo $success_message; ?>" />
				</div>
				
				<div class="row">
					<label for="tmls_namerequired_message">Name Required Message</label>
					<input type="text" id="tmls_namerequired_message" name="tmls_namerequired_message" value="<?php echo $namerequired_message; ?>" />
				</div>
				
				<div class="row">
					<label for="tmls_emailrequired_message">Email Required Message</label>
					<input type="text" id="tmls_emailrequired_message" name="tmls_emailrequired_message" value="<?php echo $emailrequired_message; ?>" />
				</div>
				
				<div class="row">
					<label for="tmls_testimonialrequired_message">Testimonial Required Message</label>
					<input type="text" id="tmls_testimonialrequired_message" name="tmls_testimonialrequired_message" value="<?php echo $testimonialrequired_message; ?>" />
				</div>
				
				<div class="row">
					<label for="tmls_invalidemail_message">Invalid Email Message</label>
					<input type="text" id="tmls_invalidemail_message" name="tmls_invalidemail_message" value="<?php echo $invalidemail_message; ?>" />
				</div>
				
				<div class="row tmls_company_option">
					<label for="tmls_invalidcompanywebsite_message">Invalid Company Website Message</label>
					<input type="text" id="tmls_invalidcompanywebsite_message" name="tmls_invalidcompanywebsite_message" value="<?php echo $invalidcompanywebsite_message; ?>" />
				</div>
				
				<div class="row tmls_image_option">
					<label for="tmls_imagefailed_message">Image Failed To Upload Message</label>
					<input type="text" id="tmls_imagefailed_message" name="tmls_imagefailed_message" value="<?php echo $imagefailed_message; ?>" />
				</div>
				
				
				<div class="row tmls_image_option">
					<label for="tmls_selectimageagain_message">Select Image Again Message</label>
					<input type="text" id="tmls_selectimageagain_message" name="tmls_selectimageagain_message" value="<?php echo $selectimageagain_message; ?>" />
				</div>

				<div class="row">
					<label for="tmls_captchaanswerrequired_message">Captcha Answer Required Message</label>
					<input type="text" id="tmls_captchaanswerrequired_message" name="tmls_captchaanswerrequired_message" value="<?php echo $captchaanswerrequired_message; ?>" />
				</div>
				
				<div class="row">
					<label for="tmls_invalidcaptchaanswer_message">Invalid Captcha Answer Message</label>
					<input type="text" id="tmls_invalidcaptchaanswer_message" name="tmls_invalidcaptchaanswer_message" value="<?php echo $invalidcaptchaanswer_message; ?>" />
				</div>
				
				<div class="row">
					<label for="tmls_alreadysent_message">Testimonial already sent Message</label>
					<input type="text" id="tmls_alreadysent_message" name="tmls_alreadysent_message" value="<?php echo $alreadysent_message; ?>" />
				</div>
				
			</div>
			
			
			<div class="tmls_sectionTitle">Messages Style</div>
			
			<div class="tmls_rowsContainer" >
			
				<div class="row">
					<label for="tmls_validationmessage_fontcolor">Validation Message Font Color</label>
					<input type="text" id="tmls_validationmessage_fontcolor" name="tmls_validationmessage_fontcolor" value="<?php echo $validationmessage_fontcolor; ?>" />
					<div id="tmls_validationmessage_fontcolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_validationmessage_fontcolor_btn" name="tmls_validationmessage_fontcolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<div class="row">
					<label for="tmls_validationmessage_fontsize">Validation Message Font Size (px)</label>
					<select id="tmls_validationmessage_fontsize" name="tmls_validationmessage_fontsize">
						<option value="9px" <?php if($validationmessage_fontsize == '9px'){ echo 'selected'; } ?> >9px</option>
						<option value="10px" <?php if($validationmessage_fontsize == '10px'){ echo 'selected'; } ?> >10px</option>
						<option value="11px" <?php if($validationmessage_fontsize == '11px'){ echo 'selected'; } ?> >11px</option>
						<option value="12px" <?php if($validationmessage_fontsize == '12px'){ echo 'selected'; } ?> >12px</option>
						<option value="13px" <?php if($validationmessage_fontsize == '13px'){ echo 'selected'; } ?> >13px</option>
						<option value="14px" <?php if($validationmessage_fontsize == '14px'){ echo 'selected'; } ?> >14px</option>
						<option value="15px" <?php if($validationmessage_fontsize == '15px'){ echo 'selected'; } ?> >15px</option>
						<option value="16px" <?php if($validationmessage_fontsize == '16px'){ echo 'selected'; } ?> >16px</option>
						<option value="17px" <?php if($validationmessage_fontsize == '17px'){ echo 'selected'; } ?> >17px</option>
						<option value="18px" <?php if($validationmessage_fontsize == '18px'){ echo 'selected'; } ?> >18px</option>
						<option value="19px" <?php if($validationmessage_fontsize == '19px'){ echo 'selected'; } ?> >19px</option>
						<option value="20px" <?php if($validationmessage_fontsize == '20px'){ echo 'selected'; } ?> >20px</option>
						<option value="21px" <?php if($validationmessage_fontsize == '21px'){ echo 'selected'; } ?> >21px</option>
						<option value="22px" <?php if($validationmessage_fontsize == '22px'){ echo 'selected'; } ?> >22px</option>
						<option value="23px" <?php if($validationmessage_fontsize == '23px'){ echo 'selected'; } ?> >23px</option>
						<option value="24px" <?php if($validationmessage_fontsize == '24px'){ echo 'selected'; } ?> >24px</option>
						<option value="25px" <?php if($validationmessage_fontsize == '25px'){ echo 'selected'; } ?> >25px</option>
						<option value="26px" <?php if($validationmessage_fontsize == '26px'){ echo 'selected'; } ?> >26px</option>
						<option value="27px" <?php if($validationmessage_fontsize == '27px'){ echo 'selected'; } ?> >27px</option>
						<option value="28px" <?php if($validationmessage_fontsize == '28px'){ echo 'selected'; } ?> >28px</option>
						<option value="29px" <?php if($validationmessage_fontsize == '29px'){ echo 'selected'; } ?> >29px</option>
						<option value="30px" <?php if($validationmessage_fontsize == '30px'){ echo 'selected'; } ?> >30px</option>
						<option value="31px" <?php if($validationmessage_fontsize == '31px'){ echo 'selected'; } ?> >31px</option>
						<option value="32px" <?php if($validationmessage_fontsize == '32px'){ echo 'selected'; } ?> >32px</option>
						<option value="33px" <?php if($validationmessage_fontsize == '33px'){ echo 'selected'; } ?> >33px</option>
						<option value="34px" <?php if($validationmessage_fontsize == '34px'){ echo 'selected'; } ?> >34px</option>
						<option value="35px" <?php if($validationmessage_fontsize == '35px'){ echo 'selected'; } ?> >35px</option>
						<option value="36px" <?php if($validationmessage_fontsize == '36px'){ echo 'selected'; } ?> >36px</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_validationmessage_fontweight">Validation Message Font Weight</label>
					<select id="tmls_validationmessage_fontweight" name="tmls_validationmessage_fontweight">
						<option value="bold" <?php if($validationmessage_fontweight == 'bold'){ echo 'selected'; } ?> >bold</option>
						<option value="normal" <?php if($validationmessage_fontweight == 'normal'){ echo 'selected'; } ?> >normal</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_validationmessage_fontfamily">Validation Message Font Family</label>
					<select id="tmls_validationmessage_fontfamily" name="tmls_validationmessage_fontfamily">
						<option value="" <?php if($validationmessage_fontfamily == ""){ echo 'selected'; } ?> >current theme font</option>
						<option value="Georgia, serif" <?php if($validationmessage_fontfamily == "Georgia, serif"){ echo 'selected'; } ?> >Georgia</option>
						<option value="'Palatino Linotype', 'Book Antiqua', Palatino, serif" <?php if($validationmessage_fontfamily == "'Palatino Linotype', 'Book Antiqua', Palatino, serif"){ echo 'selected'; } ?> >Palatino Linotype</option>
						<option value="'Times New Roman', Times, serif" <?php if($validationmessage_fontfamily == "'Times New Roman', Times, serif"){ echo 'selected'; } ?> >Times New Roman</option>
						<option value="Arial, Helvetica, sans-serif" <?php if($validationmessage_fontfamily == "Arial, Helvetica, sans-serif"){ echo 'selected'; } ?> >Arial</option>
						<option value="'Arial Black', Gadget, sans-serif" <?php if($validationmessage_fontfamily == "'Arial Black', Gadget, sans-serif"){ echo 'selected'; } ?> >Arial Black</option>
						<option value="'Comic Sans MS', cursive, sans-serif" <?php if($validationmessage_fontfamily == "'Comic Sans MS', cursive, sans-serif"){ echo 'selected'; } ?> >Comic Sans MS</option>
						<option value="Impact, Charcoal, sans-serif" <?php if($validationmessage_fontfamily == "Impact, Charcoal, sans-serif"){ echo 'selected'; } ?> >Impact</option>
						<option value="'Lucida Sans Unicode', 'Lucida Grande', sans-serif" <?php if($validationmessage_fontfamily == "'Lucida Sans Unicode', 'Lucida Grande', sans-serif"){ echo 'selected'; } ?> >Lucida Sans Unicode</option>
						<option value="Tahoma, Geneva, sans-serif" <?php if($validationmessage_fontfamily == "Tahoma, Geneva, sans-serif"){ echo 'selected'; } ?> >Tahoma</option>
						<option value="'Trebuchet MS', Helvetica, sans-serif" <?php if($validationmessage_fontfamily == "'Trebuchet MS', Helvetica, sans-serif"){ echo 'selected'; } ?> >Trebuchet MS</option>
						<option value="Verdana, Geneva, sans-serif" <?php if($validationmessage_fontfamily == "Verdana, Geneva, sans-serif"){ echo 'selected'; } ?> >Verdana</option>
						<option value="'Courier New', Courier, monospace" <?php if($validationmessage_fontfamily == "'Courier New', Courier, monospace"){ echo 'selected'; } ?> >Courier New</option>
						<option value="'Lucida Console', Monaco, monospace" <?php if($validationmessage_fontfamily == "'Lucida Console', Monaco, monospace"){ echo 'selected'; } ?> >Lucida Console</option>
					</select>
				</div>
				
				
				
				
				
				
				
				
				
				
				<div class="row">
					<label for="tmls_successmessage_fontcolor">Success Message Font Color</label>
					<input type="text" id="tmls_successmessage_fontcolor" name="tmls_successmessage_fontcolor" value="<?php echo $successmessage_fontcolor; ?>" />
					<div id="tmls_successmessage_fontcolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_successmessage_fontcolor_btn" name="tmls_successmessage_fontcolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<div class="row">
					<label for="tmls_successmessage_fontsize">Success Message Font Size (px)</label>
					<select id="tmls_successmessage_fontsize" name="tmls_successmessage_fontsize">
						<option value="9px" <?php if($successmessage_fontsize == '9px'){ echo 'selected'; } ?> >9px</option>
						<option value="10px" <?php if($successmessage_fontsize == '10px'){ echo 'selected'; } ?> >10px</option>
						<option value="11px" <?php if($successmessage_fontsize == '11px'){ echo 'selected'; } ?> >11px</option>
						<option value="12px" <?php if($successmessage_fontsize == '12px'){ echo 'selected'; } ?> >12px</option>
						<option value="13px" <?php if($successmessage_fontsize == '13px'){ echo 'selected'; } ?> >13px</option>
						<option value="14px" <?php if($successmessage_fontsize == '14px'){ echo 'selected'; } ?> >14px</option>
						<option value="15px" <?php if($successmessage_fontsize == '15px'){ echo 'selected'; } ?> >15px</option>
						<option value="16px" <?php if($successmessage_fontsize == '16px'){ echo 'selected'; } ?> >16px</option>
						<option value="17px" <?php if($successmessage_fontsize == '17px'){ echo 'selected'; } ?> >17px</option>
						<option value="18px" <?php if($successmessage_fontsize == '18px'){ echo 'selected'; } ?> >18px</option>
						<option value="19px" <?php if($successmessage_fontsize == '19px'){ echo 'selected'; } ?> >19px</option>
						<option value="20px" <?php if($successmessage_fontsize == '20px'){ echo 'selected'; } ?> >20px</option>
						<option value="21px" <?php if($successmessage_fontsize == '21px'){ echo 'selected'; } ?> >21px</option>
						<option value="22px" <?php if($successmessage_fontsize == '22px'){ echo 'selected'; } ?> >22px</option>
						<option value="23px" <?php if($successmessage_fontsize == '23px'){ echo 'selected'; } ?> >23px</option>
						<option value="24px" <?php if($successmessage_fontsize == '24px'){ echo 'selected'; } ?> >24px</option>
						<option value="25px" <?php if($successmessage_fontsize == '25px'){ echo 'selected'; } ?> >25px</option>
						<option value="26px" <?php if($successmessage_fontsize == '26px'){ echo 'selected'; } ?> >26px</option>
						<option value="27px" <?php if($successmessage_fontsize == '27px'){ echo 'selected'; } ?> >27px</option>
						<option value="28px" <?php if($successmessage_fontsize == '28px'){ echo 'selected'; } ?> >28px</option>
						<option value="29px" <?php if($successmessage_fontsize == '29px'){ echo 'selected'; } ?> >29px</option>
						<option value="30px" <?php if($successmessage_fontsize == '30px'){ echo 'selected'; } ?> >30px</option>
						<option value="31px" <?php if($successmessage_fontsize == '31px'){ echo 'selected'; } ?> >31px</option>
						<option value="32px" <?php if($successmessage_fontsize == '32px'){ echo 'selected'; } ?> >32px</option>
						<option value="33px" <?php if($successmessage_fontsize == '33px'){ echo 'selected'; } ?> >33px</option>
						<option value="34px" <?php if($successmessage_fontsize == '34px'){ echo 'selected'; } ?> >34px</option>
						<option value="35px" <?php if($successmessage_fontsize == '35px'){ echo 'selected'; } ?> >35px</option>
						<option value="36px" <?php if($successmessage_fontsize == '36px'){ echo 'selected'; } ?> >36px</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_successmessage_fontweight">Success Message Font Weight</label>
					<select id="tmls_successmessage_fontweight" name="tmls_successmessage_fontweight">
						<option value="bold" <?php if($successmessage_fontweight == 'bold'){ echo 'selected'; } ?> >bold</option>
						<option value="normal" <?php if($successmessage_fontweight == 'normal'){ echo 'selected'; } ?> >normal</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_successmessage_fontfamily">Success Message Font Family</label>
					<select id="tmls_successmessage_fontfamily" name="tmls_successmessage_fontfamily">
						<option value="" <?php if($successmessage_fontfamily == ""){ echo 'selected'; } ?> >current theme font</option>
						<option value="Georgia, serif" <?php if($successmessage_fontfamily == "Georgia, serif"){ echo 'selected'; } ?> >Georgia</option>
						<option value="'Palatino Linotype', 'Book Antiqua', Palatino, serif" <?php if($successmessage_fontfamily == "'Palatino Linotype', 'Book Antiqua', Palatino, serif"){ echo 'selected'; } ?> >Palatino Linotype</option>
						<option value="'Times New Roman', Times, serif" <?php if($successmessage_fontfamily == "'Times New Roman', Times, serif"){ echo 'selected'; } ?> >Times New Roman</option>
						<option value="Arial, Helvetica, sans-serif" <?php if($successmessage_fontfamily == "Arial, Helvetica, sans-serif"){ echo 'selected'; } ?> >Arial</option>
						<option value="'Arial Black', Gadget, sans-serif" <?php if($successmessage_fontfamily == "'Arial Black', Gadget, sans-serif"){ echo 'selected'; } ?> >Arial Black</option>
						<option value="'Comic Sans MS', cursive, sans-serif" <?php if($successmessage_fontfamily == "'Comic Sans MS', cursive, sans-serif"){ echo 'selected'; } ?> >Comic Sans MS</option>
						<option value="Impact, Charcoal, sans-serif" <?php if($successmessage_fontfamily == "Impact, Charcoal, sans-serif"){ echo 'selected'; } ?> >Impact</option>
						<option value="'Lucida Sans Unicode', 'Lucida Grande', sans-serif" <?php if($successmessage_fontfamily == "'Lucida Sans Unicode', 'Lucida Grande', sans-serif"){ echo 'selected'; } ?> >Lucida Sans Unicode</option>
						<option value="Tahoma, Geneva, sans-serif" <?php if($successmessage_fontfamily == "Tahoma, Geneva, sans-serif"){ echo 'selected'; } ?> >Tahoma</option>
						<option value="'Trebuchet MS', Helvetica, sans-serif" <?php if($successmessage_fontfamily == "'Trebuchet MS', Helvetica, sans-serif"){ echo 'selected'; } ?> >Trebuchet MS</option>
						<option value="Verdana, Geneva, sans-serif" <?php if($successmessage_fontfamily == "Verdana, Geneva, sans-serif"){ echo 'selected'; } ?> >Verdana</option>
						<option value="'Courier New', Courier, monospace" <?php if($successmessage_fontfamily == "'Courier New', Courier, monospace"){ echo 'selected'; } ?> >Courier New</option>
						<option value="'Lucida Console', Monaco, monospace" <?php if($successmessage_fontfamily == "'Lucida Console', Monaco, monospace"){ echo 'selected'; } ?> >Lucida Console</option>
					</select>
				</div>
			
			
			
			</div>
			
			
			
			
			
			
			<div class="tmls_sectionTitle">Button Settings</div>
			
			<div class="tmls_rowsContainer" >
				<div class="row">
					<label for="tmls_button_text">Button Text</label>
					<input type="text" id="tmls_button_text" name="tmls_button_text" value="<?php echo $button_text; ?>" />
				</div>
				
				<div class="row">
					<label for="tmls_button_fontcolor">Button Font Color</label>
					<input type="text" id="tmls_button_fontcolor" name="tmls_button_fontcolor" value="<?php echo $button_fontcolor; ?>" />
					<div id="tmls_button_fontcolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_button_fontcolor_btn" name="tmls_button_fontcolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<div class="row">
					<label for="tmls_button_fontsize">Button Font Size (px)</label>
					<select id="tmls_button_fontsize" name="tmls_button_fontsize">
						<option value="9px" <?php if($button_fontsize == '9px'){ echo 'selected'; } ?> >9px</option>
						<option value="10px" <?php if($button_fontsize == '10px'){ echo 'selected'; } ?> >10px</option>
						<option value="11px" <?php if($button_fontsize == '11px'){ echo 'selected'; } ?> >11px</option>
						<option value="12px" <?php if($button_fontsize == '12px'){ echo 'selected'; } ?> >12px</option>
						<option value="13px" <?php if($button_fontsize == '13px'){ echo 'selected'; } ?> >13px</option>
						<option value="14px" <?php if($button_fontsize == '14px'){ echo 'selected'; } ?> >14px</option>
						<option value="15px" <?php if($button_fontsize == '15px'){ echo 'selected'; } ?> >15px</option>
						<option value="16px" <?php if($button_fontsize == '16px'){ echo 'selected'; } ?> >16px</option>
						<option value="17px" <?php if($button_fontsize == '17px'){ echo 'selected'; } ?> >17px</option>
						<option value="18px" <?php if($button_fontsize == '18px'){ echo 'selected'; } ?> >18px</option>
						<option value="19px" <?php if($button_fontsize == '19px'){ echo 'selected'; } ?> >19px</option>
						<option value="20px" <?php if($button_fontsize == '20px'){ echo 'selected'; } ?> >20px</option>
						<option value="21px" <?php if($button_fontsize == '21px'){ echo 'selected'; } ?> >21px</option>
						<option value="22px" <?php if($button_fontsize == '22px'){ echo 'selected'; } ?> >22px</option>
						<option value="23px" <?php if($button_fontsize == '23px'){ echo 'selected'; } ?> >23px</option>
						<option value="24px" <?php if($button_fontsize == '24px'){ echo 'selected'; } ?> >24px</option>
						<option value="25px" <?php if($button_fontsize == '25px'){ echo 'selected'; } ?> >25px</option>
						<option value="26px" <?php if($button_fontsize == '26px'){ echo 'selected'; } ?> >26px</option>
						<option value="27px" <?php if($button_fontsize == '27px'){ echo 'selected'; } ?> >27px</option>
						<option value="28px" <?php if($button_fontsize == '28px'){ echo 'selected'; } ?> >28px</option>
						<option value="29px" <?php if($button_fontsize == '29px'){ echo 'selected'; } ?> >29px</option>
						<option value="30px" <?php if($button_fontsize == '30px'){ echo 'selected'; } ?> >30px</option>
						<option value="31px" <?php if($button_fontsize == '31px'){ echo 'selected'; } ?> >31px</option>
						<option value="32px" <?php if($button_fontsize == '32px'){ echo 'selected'; } ?> >32px</option>
						<option value="33px" <?php if($button_fontsize == '33px'){ echo 'selected'; } ?> >33px</option>
						<option value="34px" <?php if($button_fontsize == '34px'){ echo 'selected'; } ?> >34px</option>
						<option value="35px" <?php if($button_fontsize == '35px'){ echo 'selected'; } ?> >35px</option>
						<option value="36px" <?php if($button_fontsize == '36px'){ echo 'selected'; } ?> >36px</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_button_fontweight">Button Font Weight</label>
					<select id="tmls_button_fontweight" name="tmls_button_fontweight">
						<option value="bold" <?php if($button_fontweight == 'bold'){ echo 'selected'; } ?> >bold</option>
						<option value="normal" <?php if($button_fontweight == 'normal'){ echo 'selected'; } ?> >normal</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_button_fontfamily">Button Font Family</label>
					<select id="tmls_button_fontfamily" name="tmls_button_fontfamily">
						<option value="" <?php if($button_fontfamily == ""){ echo 'selected'; } ?> >current theme font</option>
						<option value="Georgia, serif" <?php if($button_fontfamily == "Georgia, serif"){ echo 'selected'; } ?> >Georgia</option>
						<option value="'Palatino Linotype', 'Book Antiqua', Palatino, serif" <?php if($button_fontfamily == "'Palatino Linotype', 'Book Antiqua', Palatino, serif"){ echo 'selected'; } ?> >Palatino Linotype</option>
						<option value="'Times New Roman', Times, serif" <?php if($button_fontfamily == "'Times New Roman', Times, serif"){ echo 'selected'; } ?> >Times New Roman</option>
						<option value="Arial, Helvetica, sans-serif" <?php if($button_fontfamily == "Arial, Helvetica, sans-serif"){ echo 'selected'; } ?> >Arial</option>
						<option value="'Arial Black', Gadget, sans-serif" <?php if($button_fontfamily == "'Arial Black', Gadget, sans-serif"){ echo 'selected'; } ?> >Arial Black</option>
						<option value="'Comic Sans MS', cursive, sans-serif" <?php if($button_fontfamily == "'Comic Sans MS', cursive, sans-serif"){ echo 'selected'; } ?> >Comic Sans MS</option>
						<option value="Impact, Charcoal, sans-serif" <?php if($button_fontfamily == "Impact, Charcoal, sans-serif"){ echo 'selected'; } ?> >Impact</option>
						<option value="'Lucida Sans Unicode', 'Lucida Grande', sans-serif" <?php if($button_fontfamily == "'Lucida Sans Unicode', 'Lucida Grande', sans-serif"){ echo 'selected'; } ?> >Lucida Sans Unicode</option>
						<option value="Tahoma, Geneva, sans-serif" <?php if($button_fontfamily == "Tahoma, Geneva, sans-serif"){ echo 'selected'; } ?> >Tahoma</option>
						<option value="'Trebuchet MS', Helvetica, sans-serif" <?php if($button_fontfamily == "'Trebuchet MS', Helvetica, sans-serif"){ echo 'selected'; } ?> >Trebuchet MS</option>
						<option value="Verdana, Geneva, sans-serif" <?php if($button_fontfamily == "Verdana, Geneva, sans-serif"){ echo 'selected'; } ?> >Verdana</option>
						<option value="'Courier New', Courier, monospace" <?php if($button_fontfamily == "'Courier New', Courier, monospace"){ echo 'selected'; } ?> >Courier New</option>
						<option value="'Lucida Console', Monaco, monospace" <?php if($button_fontfamily == "'Lucida Console', Monaco, monospace"){ echo 'selected'; } ?> >Lucida Console</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_button_bordercolor">Button Border Color</label>
					<input type="text" id="tmls_button_bordercolor" name="tmls_button_bordercolor" value="<?php echo $button_bordercolor; ?>" />
					<div id="tmls_button_bordercolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_button_bordercolor_btn" name="tmls_button_bordercolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<div class="row">
					<label for="tmls_button_bgcolor">Button Background Color</label>
					<input type="text" id="tmls_button_bgcolor" name="tmls_button_bgcolor" value="<?php echo $button_bgcolor; ?>" />
					<div id="tmls_button_bgcolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_button_bgcolor_btn" name="tmls_button_bgcolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<div class="row">
					<label for="tmls_button_borderradius">Button Border Radius</label>
					<select id="tmls_button_borderradius" name="tmls_button_borderradius">
						<option value="large_radius" <?php if($button_borderradius == 'large_radius'){ echo 'selected'; } ?> >large radius</option>
						<option value="medium_radius" <?php if($button_borderradius == 'medium_radius'){ echo 'selected'; } ?> >medium radius</option>
						<option value="small_radius" <?php if($button_borderradius == 'small_radius'){ echo 'selected'; } ?> >small radius</option>
						<option value="no_radius" <?php if($button_borderradius == 'no_radius'){ echo 'selected'; } ?> >without radius</option>
					</select>
				</div>
				
				<div class="row">
					<label for="tmls_button_hover_fontcolor">Button Hover Font Color</label>
					<input type="text" id="tmls_button_hover_fontcolor" name="tmls_button_hover_fontcolor" value="<?php echo $button_hover_fontcolor; ?>" />
					<div id="tmls_button_hover_fontcolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_button_hover_fontcolor_btn" name="tmls_button_hover_fontcolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<div class="row">
					<label for="tmls_button_hover_bordercolor">Button Hover Border Color</label>
					<input type="text" id="tmls_button_hover_bordercolor" name="tmls_button_hover_bordercolor" value="<?php echo $button_hover_bordercolor; ?>" />
					<div id="tmls_button_hover_bordercolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_button_hover_bordercolor_btn" name="tmls_button_hover_bordercolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<div class="row">
					<label for="tmls_button_hover_bgcolor">Button Hover Background Color</label>
					<input type="text" id="tmls_button_hover_bgcolor" name="tmls_button_hover_bgcolor" value="<?php echo $button_hover_bgcolor; ?>" />
					<div id="tmls_button_hover_bgcolor_colorpicker" class="tmls_farbtastic"></div>
					<input type="button" id="tmls_button_hover_bgcolor_btn" name="tmls_button_hover_bgcolor_btn" value="View Color" class="button-primary" />
				</div>
				
				<input type="hidden" id="tmls_captcha_encryption_key" name="tmls_captcha_encryption_key" value="<?php echo str_shuffle('abcdef123456@!'); ?>" />
				
			</div>
			
		</div>
	
		<?php
	}
	
	/*========================================================================================================================================================================
		Save tmls_form_sc Options Meta Box Function
	========================================================================================================================================================================*/
	
	function tmls_form_sc_save_meta_box($post_id) 
	{
		
		/*----------------------------------------------------------------------
			shortcode
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_form_shortcode'])) {
			update_post_meta($post_id, 'shortcode', $_POST['tmls_form_shortcode']);
		}
		
		/*----------------------------------------------------------------------
			form width
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_form_width'])) {
			update_post_meta($post_id, 'form_width', $_POST['tmls_form_width']);
		}
		
		/*----------------------------------------------------------------------
			position
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_position'])) {
			update_post_meta($post_id, 'position', $_POST['tmls_position']);
		}
		
		/*----------------------------------------------------------------------
			company
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_company'])) {
			update_post_meta($post_id, 'company', $_POST['tmls_company']);
		}
		
		/*----------------------------------------------------------------------
			rating
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_rating'])) {
			update_post_meta($post_id, 'rating', $_POST['tmls_rating']);
		}
		
		/*----------------------------------------------------------------------
			image
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_image'])) {
			update_post_meta($post_id, 'image', $_POST['tmls_image']);
		}
		
		/*----------------------------------------------------------------------
			notification email
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_notificationEmail'])) {
			update_post_meta($post_id, 'notificationemail', $_POST['tmls_notificationEmail']);
		}
		
		/*----------------------------------------------------------------------
			email to
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_emailTo'])) {
			update_post_meta($post_id, 'emailto', $_POST['tmls_emailTo']);
		}
		
		/*----------------------------------------------------------------------
			email subject
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_emailSubject'])) {
			update_post_meta($post_id, 'emailsubject', $_POST['tmls_emailSubject']);
		}
		
		/*----------------------------------------------------------------------
			email message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_emailMessage'])) {
			update_post_meta($post_id, 'emailmessage', $_POST['tmls_emailMessage']);
		}
		
		/*----------------------------------------------------------------------
			success message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_success_message'])) {
			update_post_meta($post_id, 'success_message', $_POST['tmls_success_message']);
		}
		
		/*----------------------------------------------------------------------
			name required message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_namerequired_message'])) {
			update_post_meta($post_id, 'namerequired_message', $_POST['tmls_namerequired_message']);
		}
		
		/*----------------------------------------------------------------------
			email required message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_emailrequired_message'])) {
			update_post_meta($post_id, 'emailrequired_message', $_POST['tmls_emailrequired_message']);
		}
		
		/*----------------------------------------------------------------------
			testimonial required message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_testimonialrequired_message'])) {
			update_post_meta($post_id, 'testimonialrequired_message', $_POST['tmls_testimonialrequired_message']);
		}
		
		/*----------------------------------------------------------------------
			invalid email message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_invalidemail_message'])) {
			update_post_meta($post_id, 'invalidemail_message', $_POST['tmls_invalidemail_message']);
		}
		
		/*----------------------------------------------------------------------
			invalid company website message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_invalidcompanywebsite_message'])) {
			update_post_meta($post_id, 'invalidcompanywebsite_message', $_POST['tmls_invalidcompanywebsite_message']);
		}
		
		/*----------------------------------------------------------------------
			image failed to upload message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_imagefailed_message'])) {
			update_post_meta($post_id, 'imagefailed_message', $_POST['tmls_imagefailed_message']);
		}
		
		/*----------------------------------------------------------------------
			select image again message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_selectimageagain_message'])) {
			update_post_meta($post_id, 'selectimageagain_message', $_POST['tmls_selectimageagain_message']);
		}
		
		
		/*----------------------------------------------------------------------
			invalid captcha answer message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_invalidcaptchaanswer_message'])) {
			update_post_meta($post_id, 'invalidcaptchaanswer_message', $_POST['tmls_invalidcaptchaanswer_message']);
		}
		
		/*----------------------------------------------------------------------
			captcha answer required message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_captchaanswerrequired_message'])) {
			update_post_meta($post_id, 'captchaanswerrequired_message', $_POST['tmls_captchaanswerrequired_message']);
		}
		
		/*----------------------------------------------------------------------
			already sent message
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_alreadysent_message'])) {
			update_post_meta($post_id, 'alreadysent_message', $_POST['tmls_alreadysent_message']);
		}
		
		/*----------------------------------------------------------------------
			label font color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_label_fontcolor'])) {
			update_post_meta($post_id, 'label_fontcolor', $_POST['tmls_label_fontcolor']);
		}
		
		/*----------------------------------------------------------------------
			label font size
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_label_fontsize'])) {
			update_post_meta($post_id, 'label_fontsize', $_POST['tmls_label_fontsize']);
		}
		
		/*----------------------------------------------------------------------
			label font weight
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_label_fontweight'])) {
			update_post_meta($post_id, 'label_fontweight', $_POST['tmls_label_fontweight']);
		}
		
		/*----------------------------------------------------------------------
			label font family
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_label_fontfamily'])) {
			update_post_meta($post_id, 'label_fontfamily', $_POST['tmls_label_fontfamily']);
		}
		
		/*----------------------------------------------------------------------
			validation message font color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_validationmessage_fontcolor'])) {
			update_post_meta($post_id, 'validationmessage_fontcolor', $_POST['tmls_validationmessage_fontcolor']);
		}
		
		/*----------------------------------------------------------------------
			validation message font size
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_validationmessage_fontsize'])) {
			update_post_meta($post_id, 'validationmessage_fontsize', $_POST['tmls_validationmessage_fontsize']);
		}
		
		/*----------------------------------------------------------------------
			validation message font weight
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_validationmessage_fontweight'])) {
			update_post_meta($post_id, 'validationmessage_fontweight', $_POST['tmls_validationmessage_fontweight']);
		}
		
		/*----------------------------------------------------------------------
			validation message font family
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_validationmessage_fontfamily'])) {
			update_post_meta($post_id, 'validationmessage_fontfamily', $_POST['tmls_validationmessage_fontfamily']);
		}
		
		/*----------------------------------------------------------------------
			success message font color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_successmessage_fontcolor'])) {
			update_post_meta($post_id, 'successmessage_fontcolor', $_POST['tmls_successmessage_fontcolor']);
		}
		
		/*----------------------------------------------------------------------
			success message font size
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_successmessage_fontsize'])) {
			update_post_meta($post_id, 'successmessage_fontsize', $_POST['tmls_successmessage_fontsize']);
		}
		
		/*----------------------------------------------------------------------
			success message font weight
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_successmessage_fontweight'])) {
			update_post_meta($post_id, 'successmessage_fontweight', $_POST['tmls_successmessage_fontweight']);
		}
		
		/*----------------------------------------------------------------------
			success message font family
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_successmessage_fontfamily'])) {
			update_post_meta($post_id, 'successmessage_fontfamily', $_POST['tmls_successmessage_fontfamily']);
		}
		
		/*----------------------------------------------------------------------
			inputs font color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_inputs_fontcolor'])) {
			update_post_meta($post_id, 'inputs_fontcolor', $_POST['tmls_inputs_fontcolor']);
		}
		
		/*----------------------------------------------------------------------
			inputs font size
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_inputs_fontsize'])) {
			update_post_meta($post_id, 'inputs_fontsize', $_POST['tmls_inputs_fontsize']);
		}
		
		/*----------------------------------------------------------------------
			inputs font weight
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_inputs_fontweight'])) {
			update_post_meta($post_id, 'inputs_fontweight', $_POST['tmls_inputs_fontweight']);
		}
		
		/*----------------------------------------------------------------------
			inputs font family
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_inputs_fontfamily'])) {
			update_post_meta($post_id, 'inputs_fontfamily', $_POST['tmls_inputs_fontfamily']);
		}
		
		/*----------------------------------------------------------------------
			inputs border color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_inputs_bordercolor'])) {
			update_post_meta($post_id, 'inputs_bordercolor', $_POST['tmls_inputs_bordercolor']);
		}
		
		/*----------------------------------------------------------------------
			inputs bg color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_inputs_bgcolor'])) {
			update_post_meta($post_id, 'inputs_bgcolor', $_POST['tmls_inputs_bgcolor']);
		}
		
		/*----------------------------------------------------------------------
			inputs border radius
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_inputs_borderradius'])) {
			update_post_meta($post_id, 'inputs_borderradius', $_POST['tmls_inputs_borderradius']);
		}
		
		/*----------------------------------------------------------------------
			name label text
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_name_label_text'])) {
			update_post_meta($post_id, 'name_label_text', $_POST['tmls_name_label_text']);
		}
		
		/*----------------------------------------------------------------------
			position label text
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_position_label_text'])) {
			update_post_meta($post_id, 'position_label_text', $_POST['tmls_position_label_text']);
		}
		
		/*----------------------------------------------------------------------
			company name label text
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_companyname_label_text'])) {
			update_post_meta($post_id, 'companyname_label_text', $_POST['tmls_companyname_label_text']);
		}
		
		/*----------------------------------------------------------------------
			company website label text
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_companywebsite_label_text'])) {
			update_post_meta($post_id, 'companywebsite_label_text', $_POST['tmls_companywebsite_label_text']);
		}
		
		/*----------------------------------------------------------------------
			email label text
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_email_label_text'])) {
			update_post_meta($post_id, 'email_label_text', $_POST['tmls_email_label_text']);
		}
		
		/*----------------------------------------------------------------------
			rating label text
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_rating_label_text'])) {
			update_post_meta($post_id, 'rating_label_text', $_POST['tmls_rating_label_text']);
		}
		
		/*----------------------------------------------------------------------
			testimonial label text
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_testimonial_label_text'])) {
			update_post_meta($post_id, 'testimonial_label_text', $_POST['tmls_testimonial_label_text']);
		}
		
		/*----------------------------------------------------------------------
			image label text
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_image_label_text'])) {
			update_post_meta($post_id, 'image_label_text', $_POST['tmls_image_label_text']);
		}
		
		/*----------------------------------------------------------------------
			captcha label text
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_captcha_label_text'])) {
			update_post_meta($post_id, 'captcha_label_text', $_POST['tmls_captcha_label_text']);
		}
		
		/*----------------------------------------------------------------------
			button text
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_button_text'])) {
			update_post_meta($post_id, 'button_text', $_POST['tmls_button_text']);
		}
		
		/*----------------------------------------------------------------------
			button font color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_button_fontcolor'])) {
			update_post_meta($post_id, 'button_fontcolor', $_POST['tmls_button_fontcolor']);
		}
		
		/*----------------------------------------------------------------------
			button font size
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_button_fontsize'])) {
			update_post_meta($post_id, 'button_fontsize', $_POST['tmls_button_fontsize']);
		}
		
		/*----------------------------------------------------------------------
			button font weight
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_button_fontweight'])) {
			update_post_meta($post_id, 'button_fontweight', $_POST['tmls_button_fontweight']);
		}
		
		/*----------------------------------------------------------------------
			button font family
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_button_fontfamily'])) {
			update_post_meta($post_id, 'button_fontfamily', $_POST['tmls_button_fontfamily']);
		}
		
		/*----------------------------------------------------------------------
			button border color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_button_bordercolor'])) {
			update_post_meta($post_id, 'button_bordercolor', $_POST['tmls_button_bordercolor']);
		}
		
		/*----------------------------------------------------------------------
			button bg color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_button_bgcolor'])) {
			update_post_meta($post_id, 'button_bgcolor', $_POST['tmls_button_bgcolor']);
		}
		
		/*----------------------------------------------------------------------
			button border radius
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_button_borderradius'])) {
			update_post_meta($post_id, 'button_borderradius', $_POST['tmls_button_borderradius']);
		}
		
		/*----------------------------------------------------------------------
			button hover font color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_button_hover_fontcolor'])) {
			update_post_meta($post_id, 'button_hover_fontcolor', $_POST['tmls_button_hover_fontcolor']);
		}
		
		/*----------------------------------------------------------------------
			button hover border color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_button_hover_bordercolor'])) {
			update_post_meta($post_id, 'button_hover_bordercolor', $_POST['tmls_button_hover_bordercolor']);
		}
		
		/*----------------------------------------------------------------------
			button hover bg color
		----------------------------------------------------------------------*/
		if(isset($_POST['tmls_button_hover_bgcolor'])) {
			update_post_meta($post_id, 'button_hover_bgcolor', $_POST['tmls_button_hover_bgcolor']);
		}
		
		
	}
	
	/*----------------------------------------------------------------------
		Save tmls_form_sc Options Meta Box Action
	----------------------------------------------------------------------*/
	add_action('save_post', 'tmls_form_sc_save_meta_box');

?>