<?php
	if (!headers_sent()) {
		include_once('../../../../../wp-load.php');
	}
	
	$shortcode="[tmls_form ";
	
	if($_POST['form_width']!=null) {
		$shortcode.='form_width="'.$_POST['form_width'].'" ';
	}
	
	if($_POST['position']!=null) {
		$shortcode.='position="'.$_POST['position'].'" ';
	}
	
	if($_POST['company']!=null) {
		$shortcode.='company="'.$_POST['company'].'" ';
	}
	
	if($_POST['rating']!=null) {
		$shortcode.='rating="'.$_POST['rating'].'" ';
	}
	
	if($_POST['image']!=null) {
		$shortcode.='image="'.$_POST['image'].'" ';
	}
	
	if($_POST['notificationemail']!=null) {
		$shortcode.='notificationemail="'.$_POST['notificationemail'].'" ';
	}
	
	if($_POST['emailto']!=null) {
		$shortcode.='emailto="'.$_POST['emailto'].'" ';
	}
	
	if($_POST['emailsubject']!=null) {
		$shortcode.='emailsubject="'.$_POST['emailsubject'].'" ';
	}
	
	if($_POST['emailmessage']!=null) {
		$shortcode.='emailmessage="'.$_POST['emailmessage'].'" ';
	}
	
	if($_POST['success_message']!=null) {
		$shortcode.='success_message="'.$_POST['success_message'].'" ';
	}
	
	if($_POST['namerequired_message']!=null) {
		$shortcode.='namerequired_message="'.$_POST['namerequired_message'].'" ';
	}
	
	if($_POST['emailrequired_message']!=null) {
		$shortcode.='emailrequired_message="'.$_POST['emailrequired_message'].'" ';
	}
	
	if($_POST['testimonialrequired_message']!=null) {
		$shortcode.='testimonialrequired_message="'.$_POST['testimonialrequired_message'].'" ';
	}
	
	if($_POST['invalidemail_message']!=null) {
		$shortcode.='invalidemail_message="'.$_POST['invalidemail_message'].'" ';
	}
	
	if($_POST['invalidcompanywebsite_message']!=null) {
		$shortcode.='invalidcompanywebsite_message="'.$_POST['invalidcompanywebsite_message'].'" ';
	}
	
	if($_POST['imagefailed_message']!=null) {
		$shortcode.='imagefailed_message="'.$_POST['imagefailed_message'].'" ';
	}
	
	if($_POST['selectimageagain_message']!=null) {
		$shortcode.='selectimageagain_message="'.$_POST['selectimageagain_message'].'" ';
	}
	
	if($_POST['captchaanswerrequired_message']!=null) {
		$shortcode.='captchaanswerrequired_message="'.$_POST['captchaanswerrequired_message'].'" ';
	}
	
	if($_POST['invalidcaptchaanswer_message']!=null) {
		$shortcode.='invalidcaptchaanswer_message="'.$_POST['invalidcaptchaanswer_message'].'" ';
	}
	
	if($_POST['alreadysent_message']!=null) {
		$shortcode.='alreadysent_message="'.$_POST['alreadysent_message'].'" ';
	}
	
	if($_POST['label_fontcolor']!=null) {
		$shortcode.='label_fontcolor="'.$_POST['label_fontcolor'].'" ';
	}
	
	if($_POST['label_fontsize']!=null) {
		$shortcode.='label_fontsize="'.$_POST['label_fontsize'].'" ';
	}
	
	if($_POST['label_fontweight']!=null) {
		$shortcode.='label_fontweight="'.$_POST['label_fontweight'].'" ';
	}
	
	if($_POST['label_fontfamily']!=null) {
		$shortcode.='label_fontfamily="'.$_POST['label_fontfamily'].'" ';
	}
	
	if($_POST['validationmessage_fontcolor']!=null) {
		$shortcode.='validationmessage_fontcolor="'.$_POST['validationmessage_fontcolor'].'" ';
	}
	
	if($_POST['validationmessage_fontsize']!=null) {
		$shortcode.='validationmessage_fontsize="'.$_POST['validationmessage_fontsize'].'" ';
	}
	
	if($_POST['validationmessage_fontweight']!=null) {
		$shortcode.='validationmessage_fontweight="'.$_POST['validationmessage_fontweight'].'" ';
	}
	
	if($_POST['validationmessage_fontfamily']!=null) {
		$shortcode.='validationmessage_fontfamily="'.$_POST['validationmessage_fontfamily'].'" ';
	}
	
	if($_POST['successmessage_fontcolor']!=null) {
		$shortcode.='successmessage_fontcolor="'.$_POST['successmessage_fontcolor'].'" ';
	}
	
	if($_POST['successmessage_fontsize']!=null) {
		$shortcode.='successmessage_fontsize="'.$_POST['successmessage_fontsize'].'" ';
	}
	
	if($_POST['successmessage_fontweight']!=null) {
		$shortcode.='successmessage_fontweight="'.$_POST['successmessage_fontweight'].'" ';
	}
	
	if($_POST['successmessage_fontfamily']!=null) {
		$shortcode.='successmessage_fontfamily="'.$_POST['successmessage_fontfamily'].'" ';
	}
	
	if($_POST['inputs_fontcolor']!=null) {
		$shortcode.='inputs_fontcolor="'.$_POST['inputs_fontcolor'].'" ';
	}
	
	if($_POST['inputs_fontsize']!=null) {
		$shortcode.='inputs_fontsize="'.$_POST['inputs_fontsize'].'" ';
	}
	
	if($_POST['inputs_fontweight']!=null) {
		$shortcode.='inputs_fontweight="'.$_POST['inputs_fontweight'].'" ';
	}
	
	if($_POST['inputs_fontfamily']!=null) {
		$shortcode.='inputs_fontfamily="'.$_POST['inputs_fontfamily'].'" ';
	}
	
	if($_POST['inputs_bordercolor']!=null) {
		$shortcode.='inputs_bordercolor="'.$_POST['inputs_bordercolor'].'" ';
	}
	
	if($_POST['inputs_bgcolor']!=null) {
		$shortcode.='inputs_bgcolor="'.$_POST['inputs_bgcolor'].'" ';
	}
	
	if($_POST['inputs_borderradius']!=null) {
		$shortcode.='inputs_borderradius="'.$_POST['inputs_borderradius'].'" ';
	}
	
	if($_POST['name_label_text']!=null) {
		$shortcode.='name_label_text="'.$_POST['name_label_text'].'" ';
	}
	
	if($_POST['position_label_text']!=null) {
		$shortcode.='position_label_text="'.$_POST['position_label_text'].'" ';
	}
	
	if($_POST['companyname_label_text']!=null) {
		$shortcode.='companyname_label_text="'.$_POST['companyname_label_text'].'" ';
	}
	
	if($_POST['companywebsite_label_text']!=null) {
		$shortcode.='companywebsite_label_text="'.$_POST['companywebsite_label_text'].'" ';
	}
	
	if($_POST['email_label_text']!=null) {
		$shortcode.='email_label_text="'.$_POST['email_label_text'].'" ';
	}
	
	if($_POST['rating_label_text']!=null) {
		$shortcode.='rating_label_text="'.$_POST['rating_label_text'].'" ';
	}
	
	
	if($_POST['testimonial_label_text']!=null) {
		$shortcode.='testimonial_label_text="'.$_POST['testimonial_label_text'].'" ';
	}
	
	if($_POST['image_label_text']!=null) {
		$shortcode.='image_label_text="'.$_POST['image_label_text'].'" ';
	}
	
	if($_POST['captcha_label_text']!=null) {
		$shortcode.='captcha_label_text="'.$_POST['captcha_label_text'].'" ';
	}
	
	if($_POST['button_text']!=null) {
		$shortcode.='button_text="'.$_POST['button_text'].'" ';
	}
	
	if($_POST['button_fontcolor']!=null) {
		$shortcode.='button_fontcolor="'.$_POST['button_fontcolor'].'" ';
	}
	
	if($_POST['button_fontsize']!=null) {
		$shortcode.='button_fontsize="'.$_POST['button_fontsize'].'" ';
	}
	
	if($_POST['button_fontweight']!=null) {
		$shortcode.='button_fontweight="'.$_POST['button_fontweight'].'" ';
	}
	
	if($_POST['button_fontfamily']!=null) {
		$shortcode.='button_fontfamily="'.$_POST['button_fontfamily'].'" ';
	}
	
	if($_POST['button_bordercolor']!=null) {
		$shortcode.='button_bordercolor="'.$_POST['button_bordercolor'].'" ';
	}
	
	if($_POST['button_bgcolor']!=null) {
		$shortcode.='button_bgcolor="'.$_POST['button_bgcolor'].'" ';
	}
	
	if($_POST['button_borderradius']!=null) {
		$shortcode.='button_borderradius="'.$_POST['button_borderradius'].'" ';
	}
	
	if($_POST['button_hover_fontcolor']!=null) {
		$shortcode.='button_hover_fontcolor="'.$_POST['button_hover_fontcolor'].'" ';
	}
	
	if($_POST['button_hover_bordercolor']!=null) {
		$shortcode.='button_hover_bordercolor="'.$_POST['button_hover_bordercolor'].'" ';
	}
	
	if($_POST['button_hover_bgcolor']!=null) {
		$shortcode.='button_hover_bgcolor="'.$_POST['button_hover_bgcolor'].'" ';
	}
	
	
	$shortcode.='is_generate_shortcode_page_form="true" ';
	
	
	$shortcode.="]";
	
	echo do_shortcode( $shortcode );
?>