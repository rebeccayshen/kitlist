<?php
	$html.='<div class="tmls_item" style="border-color:'.$border_color.';">
				
				<div class="tmls_text dialog_box '.$dialog_radius.'" style="'.$text_font_family.' '.$text_font_color.' font-size:'.$text_font_size.'; background-color:'.$dialogbgcolor.'; border-color:'.$dialogbordercolor.';">'.get_post_meta($post[$i]->ID, 'testimonial_text', true).'<span class="tmls_arrow">
				<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
					 width="20px" height="11px" viewBox="0 0 20 11" enable-background="new 0 0 20 11" xml:space="preserve">
				<polygon fill="'.$dialogbgcolor.'" points="10,10.05 2,0 10.002,0 18.005,0 "/>
				<polyline fill="none" stroke="'.$dialogbordercolor.'" stroke-miterlimit="10" points="20,0.5 18,0.5 10,10.05 2,0.5 0,0.5 "/>
				</svg>
				</span></div>
				
				<div class="tmls_image_position">
					<div class="tmls_image '.$image_radius.'" style="'.$bg_img.'"></div>
				
					<div class="tmls_name" style="'.$name_font_family.' '.$name_font_color.' font-size:'.$neme_font_size.'; font-weight:'.$neme_font_weight.';">'.get_post_meta($post[$i]->ID, 'name', true).'</div>
					<div class="tmls_position" style="'.$position_font_family.' '.$position_font_color.' font-size:'.$position_font_size.';">'.$position.$company.'</div>';
					
					include('rating.php');
				
	$html.='	</div>
				

			</div>';
?>