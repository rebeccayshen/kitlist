<?php
	if (!headers_sent()) {
		include_once('../../../../../wp-load.php');
	}
	
	$shortcode="[tmls ";
	
	if($_POST['wpml_current_lang']!=null) {
		$shortcode.='wpml_current_lang="'.$_POST['wpml_current_lang'].'" ';
	}
	
	if($_POST['category']!=null) {
		$shortcode.='category="'.$_POST['category'].'" ';
	}
	
	if($_POST['layout']!=null) {
		$shortcode.='layout="'.$_POST['layout'].'" ';
	}
	
	if($_POST['style']!=null) {
		$shortcode.='style="'.$_POST['style'].'" ';
	}
	
	if($_POST['dialog_radius']!=null) {
		$shortcode.='dialog_radius="'.$_POST['dialog_radius'].'" ';
	}
	
	if($_POST['image_size']!=null) {
		$shortcode.='image_size="'.$_POST['image_size'].'" ';
	}
	
	if($_POST['image_radius']!=null) {
		$shortcode.='image_radius="'.$_POST['image_radius'].'" ';
	}
	
	if($_POST['dialogbgcolor']!=null) {
		$shortcode.='dialogbgcolor="'.$_POST['dialogbgcolor'].'" ';
	}
	
	if($_POST['dialogbordercolor']!=null) {
		$shortcode.='dialogbordercolor="'.$_POST['dialogbordercolor'].'" ';
	}
	
	if($_POST['text_font_family']!=null) {
		$shortcode.='text_font_family="'.$_POST['text_font_family'].'" ';
	}
	
	if($_POST['text_font_color']!=null) {
		$shortcode.='text_font_color="'.$_POST['text_font_color'].'" ';
	}
	
	if($_POST['text_font_size']!=null) {
		$shortcode.='text_font_size="'.$_POST['text_font_size'].'" ';
	}
	
	if($_POST['name_font_family']!=null) {
		$shortcode.='name_font_family="'.$_POST['name_font_family'].'" ';
	}
	
	if($_POST['name_font_color']!=null) {
		$shortcode.='name_font_color="'.$_POST['name_font_color'].'" ';
	}
	
	if($_POST['neme_font_size']!=null) {
		$shortcode.='neme_font_size="'.$_POST['neme_font_size'].'" ';
	}
	
	if($_POST['neme_font_weight']!=null) {
		$shortcode.='neme_font_weight="'.$_POST['neme_font_weight'].'" ';
	}
	
	if($_POST['position_font_family']!=null) {
		$shortcode.='position_font_family="'.$_POST['position_font_family'].'" ';
	}
	
	if($_POST['position_font_color']!=null) {
		$shortcode.='position_font_color="'.$_POST['position_font_color'].'" ';
	}
	
	if($_POST['position_font_size']!=null) {
		$shortcode.='position_font_size="'.$_POST['position_font_size'].'" ';
	}
	
	if($_POST['order_by']!=null) {
		$shortcode.='order_by="'.$_POST['order_by'].'" ';
	}
	
	if($_POST['order']!=null) {
		$shortcode.='order="'.$_POST['order'].'" ';
	}
	
	if($_POST['number']!=null) {
		$shortcode.='number="'.$_POST['number'].'" ';
	}
	
	if($_POST['auto_play']!=null) {
		$shortcode.='auto_play="'.$_POST['auto_play'].'" ';
	}
	
	if($_POST['transitioneffect']!=null) {
		$shortcode.='transitioneffect="'.$_POST['transitioneffect'].'" ';
	}
	
	if($_POST['pause_on_hover']!=null) {
		$shortcode.='pause_on_hover="'.$_POST['pause_on_hover'].'" ';
	}
	
	if($_POST['next_prev_visibility']!=null) {
		$shortcode.='next_prev_visibility="'.$_POST['next_prev_visibility'].'" ';
	}
	
	if($_POST['next_prev_radius']!=null) {
		$shortcode.='next_prev_radius="'.$_POST['next_prev_radius'].'" ';
	}
	
	if($_POST['next_prev_position']!=null) {
		$shortcode.='next_prev_position="'.$_POST['next_prev_position'].'" ';
	}
	
	if($_POST['next_prev_bgcolor']!=null) {
		$shortcode.='next_prev_bgcolor="'.$_POST['next_prev_bgcolor'].'" ';
	}
	
	if($_POST['next_prev_arrowscolor']!=null) {
		$shortcode.='next_prev_arrowscolor="'.$_POST['next_prev_arrowscolor'].'" ';
	}
	
	if($_POST['scroll_duration']!=null) {
		$shortcode.='scroll_duration="'.$_POST['scroll_duration'].'" ';
	}
	
	if($_POST['pause_duration']!=null) {
		$shortcode.='pause_duration="'.$_POST['pause_duration'].'" ';
	}
	
	if($_POST['border_style']!=null) {
		$shortcode.='border_style="'.$_POST['border_style'].'" ';
	}
	
	if($_POST['border_color']!=null) {
		$shortcode.='border_color="'.$_POST['border_color'].'" ';
	}
	
	if($_POST['columns_number']!=null) {
		$shortcode.='columns_number="'.$_POST['columns_number'].'" ';
	}
	
	if($_POST['ratingstars']!=null) {
		$shortcode.='ratingstars="'.$_POST['ratingstars'].'" ';
	}
	
	if($_POST['ratingstarssize']!=null) {
		$shortcode.='ratingstarssize="'.$_POST['ratingstarssize'].'" ';
	}
	
	if($_POST['ratingstarscolor']!=null) {
		$shortcode.='ratingstarscolor="'.$_POST['ratingstarscolor'].'" ';
	}
	
	if($_POST['grayscale']!=null) {
		$shortcode.='grayscale="'.$_POST['grayscale'].'" ';
	}
	
	if($_POST['slider2_unselectedoverlaybgcolor']!=null) {
		$shortcode.='slider2_unselectedoverlaybgcolor="'.$_POST['slider2_unselectedoverlaybgcolor'].'" ';
	}
	
	
	$shortcode.="]";
	
	echo do_shortcode( $shortcode );
?>