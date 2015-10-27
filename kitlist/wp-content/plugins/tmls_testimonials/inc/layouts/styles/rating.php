<?php
	
	
	if(get_post_meta($post[$i]->ID, 'rating', true)!='' && $ratingstars == 'enabled') {
		
		$tmls_starsStyle='';
	
		if($ratingstarssize!='') {
			$tmls_starsStyle='font-size:'.$ratingstarssize.';';
		}
		
		if($ratingstarscolor!='') {
			$tmls_starsStyle.='color:'.$ratingstarscolor.';';
		}
	
		$html.='<div style="'.$tmls_starsStyle.'" class="tmls-fa tmls_rating tmls_rating_'.get_post_meta($post[$i]->ID, 'rating', true).'"></div>';
	}
	
?>