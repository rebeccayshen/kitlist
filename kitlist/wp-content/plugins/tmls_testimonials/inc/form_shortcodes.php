<?php
	
	/*========================================================================================================================================================================
		Shortcode
	========================================================================================================================================================================*/

	function tmls_form_shortcode($atts, $content=null) {  

		extract(shortcode_atts( array(  
			'form_width' => 450,
			'position' => 'enabled',
			'company' => 'enabled',
			'rating' => 'enabled',
			'image' => 'disabled',
			'notificationemail' => 'disabled',
			'emailto' => 1,
			'emailsubject' => 'A new testimonil has been received',
			'emailmessage' => 'A new testimonil has been received',
			'success_message' => 'Thank you! Your testimonial has been successfully sent',
			'namerequired_message' => 'Name is required',
			'emailrequired_message' => 'Email is required',
			'testimonialrequired_message' => 'Testimonial is required',
			'invalidemail_message' => 'Invalid email format',
			'invalidcompanywebsite_message' => 'Invalid company website URL',
			'imagefailed_message' => 'Image has failed to upload',
			'selectimageagain_message' => '<b>Note:</b> Please select image again',
			'invalidcaptchaanswer_message' => 'Invalid captcha answer',
			'captchaanswerrequired_message' => 'Captcha answer is required',
			'alreadysent_message' => 'Your testimonial already sent',
			'label_fontcolor' => '#979ca4',
			'label_fontsize' => '13px',
			'label_fontweight' => 'normal',
			'label_fontfamily' => '',
			'validationmessage_fontcolor' => '#e14d43',
			'validationmessage_fontsize' => '13px',
			'validationmessage_fontweight' => 'normal',
			'validationmessage_fontfamily' => '',
			'successmessage_fontcolor' => '#a3b745',
			'successmessage_fontsize' => '13px',
			'successmessage_fontweight' => 'normal',
			'successmessage_fontfamily' => '',
			'inputs_fontcolor' => '#979ca4',
			'inputs_fontsize' => '12px',
			'inputs_fontweight' => 'normal',
			'inputs_fontfamily' => 'verdana',
			'inputs_bordercolor' => '#e5e8ec',
			'inputs_bgcolor' => 'transparent',
			'inputs_borderradius' => 'small_radius',
			'name_label_text' => 'Name',
			'position_label_text' => 'Position',
			'companyname_label_text' => 'Company Name',
			'companywebsite_label_text' => 'Company Website',
			'email_label_text' => 'Email',
			'rating_label_text' => 'Rating',
			'testimonial_label_text' => 'Testimonial',
			'image_label_text' => 'Image',
			'captcha_label_text' => 'Are you Human?',
			'button_text' => 'SEND TESTIMONIAL',
			'button_fontcolor' => '#979ca4',
			'button_fontsize' => '12px',
			'button_fontweight' => 'bold',
			'button_fontfamily' => '',
			'button_bordercolor' => '#e5e8ec',
			'button_bgcolor' => 'transparent',
			'button_borderradius' => 'large_radius',
			'button_hover_fontcolor' => '#979ca4',
			'button_hover_bordercolor' => '#e5e8ec',
			'button_hover_bgcolor' => '#e5e8ec',
			'is_generate_shortcode_page_form' => false,
			'captcha_encryption_key' => str_shuffle('abcdef123456@!')
		), $atts));
		
		$html = "";
		
		$tmls_name = $tmls_position = $tmls_companyName = $tmls_companyWebsite = $tmls_email = $tmls_testimonial = $tmls_captcha_answer = $tmls_captcha_value = "";
		
		$tmls_rating ="five_stars";
		
		$tmls_error_message = "";
		
		$tmls_error = false;
		
		$tmls_input_width=200;
		
		$tmls_math_captcha_firstnum = rand(1,100);
		$tmls_math_captcha_secondnum = rand(1,10);
		$tmls_math_captcha_question= $tmls_math_captcha_firstnum.' + '.$tmls_math_captcha_secondnum.' = ';
		
		$tmls_labels_style = $tmls_validationmessage_style = $tmls_successmessage_style = $tmls_inputs_style = $tmls_button_style ='';
		
		/* ============== Labels Style ============== */
			
		if($label_fontcolor != '') {
			$tmls_labels_style .='color:'.$label_fontcolor.';';
		}
		
		if($label_fontsize != '') {
			$tmls_labels_style .='font-size:'.$label_fontsize.';';
		}
		
		if($label_fontweight != '') {
			$tmls_labels_style .='font-weight:'.$label_fontweight.';';
		}
		
		if($label_fontfamily != '') {
			$tmls_labels_style .='font-family:'.$label_fontfamily.';';
		}
		
		/* ============== Validation Message Style ============== */
			
		if($validationmessage_fontcolor != '') {
			$tmls_validationmessage_style .='color:'.$validationmessage_fontcolor.';';
		}
		
		if($validationmessage_fontsize != '') {
			$tmls_validationmessage_style .='font-size:'.$validationmessage_fontsize.';';
		}
		
		if($validationmessage_fontweight != '') {
			$tmls_validationmessage_style .='font-weight:'.$validationmessage_fontweight.';';
		}
		
		if($validationmessage_fontfamily != '') {
			$tmls_validationmessage_style .='font-family:'.$validationmessage_fontfamily.';';
		}
		
		/* ============== Success Message Style ============== */
		
		if($successmessage_fontcolor != '') {
			$tmls_successmessage_style .='color:'.$successmessage_fontcolor.';';
		}
		
		if($successmessage_fontsize != '') {
			$tmls_successmessage_style .='font-size:'.$successmessage_fontsize.';';
		}
		
		if($successmessage_fontweight != '') {
			$tmls_successmessage_style .='font-weight:'.$successmessage_fontweight.';';
		}
		
		if($successmessage_fontfamily != '') {
			$tmls_successmessage_style .='font-family:'.$successmessage_fontfamily.';';
		}
		
		/* ============== Inputs Style ============== */
		
		if($inputs_fontcolor != '') {
			$tmls_inputs_style .='color:'.$inputs_fontcolor.';';
		}
		
		if($inputs_fontsize != '') {
			$tmls_inputs_style .='font-size:'.$inputs_fontsize.';';
		}
		
		if($inputs_fontweight != '') {
			$tmls_inputs_style .='font-weight:'.$inputs_fontweight.';';
		}
		
		if($inputs_fontfamily != '') {
			$tmls_inputs_style .='font-family:'.$inputs_fontfamily.';';
		}
		
		if($inputs_bordercolor != '') {
			$tmls_inputs_style .='border-color:'.$inputs_bordercolor.' !important;';
		}
		
		if($inputs_bgcolor != '') {
			$tmls_inputs_style .='background-color:'.$inputs_bgcolor.';';
		}
		
		
		/* ============== Submit Button Style ============== */
		
		if($button_fontcolor != '') {
			$tmls_button_style .='color:'.$button_fontcolor.';';
		}
		
		if($button_fontsize != '') {
			$tmls_button_style .='font-size:'.$button_fontsize.';';
		}
		
		if($button_fontweight != '') {
			$tmls_button_style .='font-weight:'.$button_fontweight.';';
		}
		
		if($button_fontfamily != '') {
			$tmls_button_style .='font-family:'.$button_fontfamily.';';
		}
		
		if($button_bordercolor != '') {
			$tmls_button_style .='border-color:'.$button_bordercolor.' !important;';
		}
		
		if($button_bgcolor != '') {
			$tmls_button_style .='background-color:'.$button_bgcolor.';';
		}


		
		
		if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["tmls_form_submit"]) {
			
			if (empty($_POST["tmls_form_name"])) {
				$tmls_error_message .= '<span>'.$namerequired_message.'</span>';
				$tmls_error = true;
			}else {
				$tmls_name = tmls_test_input($_POST["tmls_form_name"]);
			}
			
			if (empty($_POST["tmls_form_position"])) {
				$tmls_position = "";
			}else {
				$tmls_position = tmls_test_input($_POST["tmls_form_position"]);
			}
			
			if (empty($_POST["tmls_form_companyName"])) {
				$tmls_companyName = "";
			}else {
				$tmls_companyName = tmls_test_input($_POST["tmls_form_companyName"]);
			}
			
			if (empty($_POST["tmls_form_companyWebsite"])) {
				$tmls_companyWebsite = "";
			}else {
				$tmls_companyWebsite = tmls_test_input($_POST["tmls_form_companyWebsite"]);

				if (!preg_match("/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/",$tmls_companyWebsite)) {
					$tmls_error_message .= '<span>'.$invalidcompanywebsite_message.'</span>';
					$tmls_error = true;
				}
			}
			
			if (empty($_POST["tmls_form_email"])) {
				$tmls_error_message .= '<span>'.$emailrequired_message.'</span>';
				$tmls_error = true;
			}else {
				$tmls_email = tmls_test_input($_POST["tmls_form_email"]);
				if (!filter_var($tmls_email, FILTER_VALIDATE_EMAIL)) {
					$tmls_error_message .= '<span>'.$invalidemail_message.'</span>';
					$tmls_error = true;
				}
			}
			
			if (empty($_POST["tmls_form_rating"])) {
				$tmls_rating ="";
			}else {
				$tmls_rating = tmls_test_input($_POST["tmls_form_rating"]);
			}
			
			if (empty($_POST["tmls_form_testimonial"])) {
				$tmls_error_message .= '<span>'.$testimonialrequired_message.'</span>';
				$tmls_error = true;
			}else {
				$tmls_testimonial = tmls_test_input($_POST["tmls_form_testimonial"]);
			}
			
			
			
			
			
			if (empty($_POST["tmls_form_captcha_answer"])) {
				$tmls_error_message .= '<span>'.$captchaanswerrequired_message.'</span>';
				$tmls_error = true;
			}else {
				$tmls_captcha_answer = tmls_test_input($_POST["tmls_form_captcha_answer"]);
				$tmls_captcha_value = tmls_test_input($_POST["tmls_form_captcha_value"]);
				
				$tmls_today = getdate();
				$tmls_args =array ( 'post_type' => 'tmls', 'post_status' => 'pending','meta_key' => 'email', 'meta_value' => $tmls_email, 'date_query' => array(array('year'=> $tmls_today['year'],'month' => $tmls_today['mon'],'day' => $tmls_today['mday'])) );
						
				$tmls_count_query = new WP_Query( $tmls_args );
				
				if(strcmp(md5($tmls_captcha_answer.$captcha_encryption_key),$tmls_captcha_value) != 0 || $tmls_count_query->post_count > 0 ) {
					$tmls_error_message .= '<span>'.$invalidcaptchaanswer_message.'</span>';
					$tmls_error = true;
					
					if($tmls_count_query->post_count > 0) {
						$tmls_name = $tmls_position = $tmls_companyName = $tmls_companyWebsite = $tmls_email = $tmls_testimonial = "";
						$tmls_rating ="five_stars";
						$tmls_error_message=$alreadysent_message;
					}
				}
		
			}
			
			
			
			if (isset($_FILES["tmls_form_image"]) && $_FILES["tmls_form_image"]["name"] !='') {
				
				if($tmls_error == false) {
				
					$tmls_imagefiletype = wp_check_filetype($_FILES["tmls_form_image"]["name"]);
					
					if ($tmls_imagefiletype['type'] != "image/gif" && 
						$tmls_imagefiletype['type'] != "image/jpeg" &&
						$tmls_imagefiletype['type'] != "image/jpg" && 
						$tmls_imagefiletype['type'] != "image/png" && 
						$tmls_imagefiletype['type'] != "image/pjpeg" &&
						$tmls_imagefiletype['size'] > 2097152 )
					{
					
						$tmls_error_message .= '<span>'.$imagefailed_message.'</span>';
						$tmls_error = true;

					}
					else {
						
						if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
						
						$tmls_fileIsUpload = false;
						$tmls_uploadedfile = $_FILES['tmls_form_image'];
						$tmls_upload_overrides = array( 'test_form' => false );
						$tmls_movefile = wp_handle_upload( $tmls_uploadedfile, $tmls_upload_overrides );
						
						if ( $tmls_movefile ) {
							$tmls_fileIsUpload = true;
						}
						else {
							$tmls_error_message .= '<span>'.$imagefailed_message.'</span>';
							$tmls_error = true;
						}
						
					}
				
				}
				else if($tmls_error == true){
					$tmls_error_message .= '<span>'.$selectimageagain_message.'</span>';
				}
				
			}
			
			
			if($tmls_error == false) {

				$tmls_post = array(
					'post_title' => $tmls_name,
					'post_status' => 'pending',
					'post_type' => 'tmls'
				);
				
				$tmls_post_id = wp_insert_post( $tmls_post );
				
				$tmls_item = get_post($tmls_post_id);
				
				if($tmls_fileIsUpload==true) 
				{
					$tmls_attachment = array(
						'guid' => $tmls_movefile['url'], 
						'post_mime_type' => $tmls_imagefiletype['type'],
						'post_title' => preg_replace('/\.[^.]+$/', '', basename($tmls_uploadedfile['name'])),
						'post_content' => '',
						'post_status' => 'inherit'
					);
					
					$tmls_attach_id = wp_insert_attachment( $tmls_attachment, $tmls_movefile['url'], $tmls_post_id );
					
					if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) require_once( ABSPATH . 'wp-admin/includes/image.php' );
					
					$tmls_attach_data = wp_generate_attachment_metadata( $tmls_attach_id, $tmls_movefile['url'] );
					wp_update_attachment_metadata( $tmls_attach_id, $tmls_attach_data );
					
					set_post_thumbnail( $tmls_post_id, $tmls_attach_id );
				}
				
				if(isset($tmls_name)) {
					update_post_meta( $tmls_post_id, 'name', $tmls_name );
				}
				if(isset($tmls_position)) {
					update_post_meta( $tmls_post_id, 'position', $tmls_position );
				}
				if(isset($tmls_companyName)) {
					update_post_meta( $tmls_post_id, 'company', $tmls_companyName );
				}
				if(isset($tmls_companyWebsite)) {
					update_post_meta( $tmls_post_id, 'company_website', $tmls_companyWebsite );
				}
				if(isset($tmls_email)) {
					update_post_meta( $tmls_post_id, 'email', $tmls_email );
				}
				
				update_post_meta( $tmls_post_id, 'company_link_target', '_self' );
				
				if(isset($tmls_testimonial)) {
					update_post_meta( $tmls_post_id, 'testimonial_text', $tmls_testimonial );
				}
				if(isset($tmls_rating)) {
					update_post_meta( $tmls_post_id, 'rating', $tmls_rating );
				}
				
				
				
				if($notificationemail=='enabled') {
					$tmls_emailto_user_info = get_userdata($emailto);
					$emailmessage.='<br/><br/>'.get_edit_post_link($tmls_post_id,'?post='.$tmls_post_id.'&action=edit');
					
					add_filter( 'wp_mail_content_type', 'tmls_set_html_content_type' );
					
					wp_mail($tmls_emailto_user_info->user_email, $emailsubject, $emailmessage,'From: '.$tmls_name.' <'.$tmls_email.'> \r\n');
					
					remove_filter( 'wp_mail_content_type', 'tmls_set_html_content_type' );
				}
				
				$html.='<div id="tmls_formmessage" class="tmls_form_success_message" style="'.$tmls_successmessage_style.'">'.$success_message.'</div>';
				
				$tmls_name = $tmls_position = $tmls_companyName = $tmls_companyWebsite = $tmls_email = $tmls_testimonial = "";
				$tmls_rating ="five_stars";

			
			}
			else  {
				$html.='<div id="tmls_formmessage" class="tmls_form_error_message" style="'.$tmls_validationmessage_style.'">'.$tmls_error_message.'</div>';
			}
		}
		
		
		if($is_generate_shortcode_page_form == false) {
			if($form_width < 350 || $position == 'disabled' || $rating == 'disabled') {
				$html.='<form autocomplete="off" id="tmls_form" method="post" action="'.$_SERVER['REQUEST_URI'].'#tmls_formmessage" class="tmls_form tmls_notready tmls_form_onecolumn" style="width:'.$form_width.'px;" enctype="multipart/form-data" >';
			}
			else {
				$html.='<form autocomplete="off" id="tmls_form" method="post" action="'.$_SERVER['REQUEST_URI'].'#tmls_formmessage" class="tmls_form tmls_notready" style="width:'.$form_width.'px;" enctype="multipart/form-data" >';
				$tmls_input_width = ($form_width-20)/2; 
			}
		}
		else if($is_generate_shortcode_page_form == true) {
			if($form_width < 350 || $position == 'disabled' || $rating == 'disabled') {
				$html.='<div class="tmls_form tmls_notready tmls_form_onecolumn" style="width:'.$form_width.'px;" >';
			}
			else {
				$html.='<div class="tmls_form tmls_notready" style="width:'.$form_width.'px;" >';
				$tmls_input_width = ($form_width-20)/2; 
			}
		}
		
		
		$html.='<div class="tmls_form_table">
					<div>
						<div>
							<label for="tmls_form_name" style="'.$tmls_labels_style.'">'.$name_label_text.' *</label>
							<input type="text" name="tmls_form_name" id="tmls_form_name" value="'.$tmls_name.'" style="width:'.$tmls_input_width.'px;'.$tmls_inputs_style.'" class="'.$inputs_borderradius.'" />
						</div>
						
						<div class="tmls_form_space_cell"></div>';
		
		if($position=='enabled') {
			$html.='	<div>
							<label for="tmls_form_position" style="'.$tmls_labels_style.'">'.$position_label_text.'</label>
							<input type="text" name="tmls_form_position" id="tmls_form_position" value="'.$tmls_position.'" style="width:'.$tmls_input_width.'px;'.$tmls_inputs_style.'" class="'.$inputs_borderradius.'" />
						</div>';
		}
		
		$html.='	</div>';
		
		if($company=='enabled') {
			$html.='<div>
						<div>
							<label for="tmls_form_companyName" style="'.$tmls_labels_style.'">'.$companyname_label_text.'</label>
							<input type="text" name="tmls_form_companyName" id="tmls_form_companyName" value="'.$tmls_companyName.'" style="width:'.$tmls_input_width.'px;'.$tmls_inputs_style.'" class="'.$inputs_borderradius.'" />
						</div>
						
						<div class="tmls_form_space_cell"></div>
						
						<div>
							<label for="tmls_form_companyWebsite" style="'.$tmls_labels_style.'">'.$companywebsite_label_text.'</label>
							<input type="text" name="tmls_form_companyWebsite" id="tmls_form_companyWebsite" value="'.$tmls_companyWebsite.'" style="width:'.$tmls_input_width.'px;'.$tmls_inputs_style.'" class="'.$inputs_borderradius.'" />
						</div>
						
					</div>';
		}
		
		$html.='	<div>
						<div>
							<label for="tmls_form_email" style="'.$tmls_labels_style.'">'.$email_label_text.' *</label>
							<input type="text" name="tmls_form_email" id="tmls_form_email" value="'.$tmls_email.'" style="width:'.$tmls_input_width.'px;'.$tmls_inputs_style.'" class="'.$inputs_borderradius.'" />
						</div>
						
						<div class="tmls_form_space_cell"></div>';
						
		if($rating=='enabled') {	
		
			$html.='	<div>
							<label for="tmls_form_rating" style="'.$tmls_labels_style.'" >'.$rating_label_text.'</label>
							<select name="tmls_form_rating" id="tmls_form_rating" style="width:'.$tmls_input_width.'px;'.$tmls_inputs_style.'" class="'.$inputs_borderradius.'" >';
						
						switch ($tmls_rating) {
							case "":
								$html.='
								<option value="" selected >none</option>
								<option value="one_star">1 star</option>
								<option value="two_stars">2 stars</option>
								<option value="three_stars">3 stars</option>
								<option value="four_stars">4 stars</option>
								<option value="five_stars">5 stars</option>';
								break;
							case "one_star":
								$html.='
								<option value="">none</option>
								<option value="one_star" selected >1 star</option>
								<option value="two_stars">2 stars</option>
								<option value="three_stars">3 stars</option>
								<option value="four_stars">4 stars</option>
								<option value="five_stars">5 stars</option>';
								break;
							case "two_stars":
								$html.='
								<option value="">none</option>
								<option value="one_star">1 star</option>
								<option value="two_stars" selected >2 stars</option>
								<option value="three_stars">3 stars</option>
								<option value="four_stars">4 stars</option>
								<option value="five_stars">5 stars</option>';
								break;
							case "three_stars":
								$html.='
								<option value="">none</option>
								<option value="one_star">1 star</option>
								<option value="two_stars">2 stars</option>
								<option value="three_stars" selected >3 stars</option>
								<option value="four_stars">4 stars</option>
								<option value="five_stars">5 stars</option>';
								break;
							case "four_stars":
								$html.='
								<option value="">none</option>
								<option value="one_star">1 star</option>
								<option value="two_stars">2 stars</option>
								<option value="three_stars">3 stars</option>
								<option value="four_stars" selected >4 stars</option>
								<option value="five_stars">5 stars</option>';
								break;
							default:
								$html.='
								<option value="">none</option>
								<option value="one_star">1 star</option>
								<option value="two_stars">2 stars</option>
								<option value="three_stars">3 stars</option>
								<option value="four_stars">4 stars</option>
								<option value="five_stars" selected >5 stars</option>';
						}
								
					
					$html.=	'</select>
						</div>';
				}
				
				
				$html.='</div>	
				
				</div>
				
				<div class="tmls_form_table">
					<div>
						<div>
							<label for="tmls_form_testimonial" style="'.$tmls_labels_style.'">'.$testimonial_label_text.' *</label>
							<textarea name="tmls_form_testimonial" id="tmls_form_testimonial" style="width:'.($tmls_input_width*2+20).'px;'.$tmls_inputs_style.'" class="'.$inputs_borderradius.'" >'.$tmls_testimonial.'</textarea>
						</div>
					</div>';
				
				if($image=='enabled') {
					$html.='
					<div>
						<div>
							<label for="tmls_form_image" style="'.$tmls_labels_style.'">'.$image_label_text.'</label>
							<input type="file" name="tmls_form_image" id="tmls_form_image" style="width:'.($tmls_input_width*2+20).'px;'.$tmls_inputs_style.'" class="'.$inputs_borderradius.'" />
						</div>
					</div>';	
				}	
				
				$html.='
					<div>
						<div>
							<label for="tmls_form_captcha_answer" style="'.$tmls_labels_style.'">'.$captcha_label_text.'</label>
							<label for="tmls_form_captcha_answer" style="'.$tmls_labels_style.'" class="tmls_form_captcha_question_label" >'.$tmls_math_captcha_question.'</label>
							<input type="text" name="tmls_form_captcha_answer" id="tmls_form_captcha_answer" value="" style="'.$tmls_inputs_style.'" class="tmls_form_captcha_input '.$inputs_borderradius.'" />
							<input type="hidden" name="tmls_form_captcha_value" id="tmls_form_captcha_value" value="'.md5(($tmls_math_captcha_firstnum+$tmls_math_captcha_secondnum).$captcha_encryption_key).'" />
						</div>
					</div>
						
					<div>
						<div>';
						if($is_generate_shortcode_page_form == false) {
							$html.=	'<input type="submit" name="tmls_form_submit" id="tmls_form_submit" value="'.$button_text.'" style="'.$tmls_button_style.'" class="tmls_form_submit '.$button_borderradius.'" data-hoverfontcolor="'.$button_hover_fontcolor.'" data-hoverbordercolor="'.$button_hover_bordercolor.'" data-hoverbgcolor="'.$button_hover_bgcolor.'" data-fontcolor="'.$button_fontcolor.'" data-bordercolor="'.$button_bordercolor.'" data-bgcolor="'.$button_bgcolor.'" />';
						}
						else {
							$html.=	'<a style="'.$tmls_button_style.'" href="#" class="tmls_form_submit '.$button_borderradius.'" data-hoverfontcolor="'.$button_hover_fontcolor.'" data-hoverbordercolor="'.$button_hover_bordercolor.'" data-hoverbgcolor="'.$button_hover_bgcolor.'" data-fontcolor="'.$button_fontcolor.'" data-bordercolor="'.$button_bordercolor.'" data-bgcolor="'.$button_bgcolor.'" >'.$button_text.'</a>';
						}
		$html.=	'		</div>
					</div>
							
					
				</div>';
		
		if($is_generate_shortcode_page_form == false) {
			$html.='</form>';
		}
		else if($is_generate_shortcode_page_form == true){
			$html.='</div>';
		}
		
		
		if($is_generate_shortcode_page_form == true) {
			$html.=	'<h3>Success Message:</h3>
					<div id="tmls_formmessage" class="tmls_form_success_message" style="'.$tmls_successmessage_style.'">'.$success_message.'</div>
					
					<h3>Error Messages:</h3>
					<div id="tmls_formmessage" class="tmls_form_error_message" style="'.$tmls_validationmessage_style.'">
						<span>'.$namerequired_message.'</span>
						<span>'.$emailrequired_message.'</span>
						<span>'.$testimonialrequired_message.'</span>
						<span>'.$invalidemail_message.'</span>';
						
			if($company=='enabled') {
				$html.='<span>'.$invalidcompanywebsite_message.'</span>';
			}
						
				$html.='<span>'.$captchaanswerrequired_message.'</span>
						<span>'.$invalidcaptchaanswer_message.'</span>';
						
			if($image=='enabled') {
				$html.='<span>'.$imagefailed_message.'</span>';
				$html.='<span>'.$selectimageagain_message.'</span>';		
			}
			
				$html.='</div>';
		}
		
		return $html;
				  
	}  
	add_shortcode('tmls_form', 'tmls_form_shortcode');
	
	
	// tmls_form_saved Shortcode
	
	function tmls_form_saved_shortcode( $atts ) {
	    
		extract( shortcode_atts( array(
		    'id' => ''
	    ), $atts ) );
		
		$tmls_form_sc = '';
		
		if($id != ''){
			$tmls_form_sc = get_post($id);
			
			if(get_post_meta($tmls_form_sc->ID, 'shortcode', true) != '') {
				return do_shortcode( get_post_meta($tmls_form_sc->ID, 'shortcode', true) );
			}
		}
		
	}
	
	add_shortcode( 'tmls_form_saved', 'tmls_form_saved_shortcode' );
	
	
	
	
	function tmls_test_input($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}
	
	function tmls_set_html_content_type() {
		return 'text/html';
	}
	
?>