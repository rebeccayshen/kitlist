<?php
	
	$current_column+=1;
	
	if($current_column==1) {
		$html.='<div class="tmls_row">';
	}
	
	if($style=='style1') {
		$html.='<div class="tmls_column" style="width:'.(100/$columns_number).'%; border-color:'.$border_color.';">';
		include('styles/style1.php');
		$html.='</div>';
	}
	elseif($style=='style2') {
		$html.='<div class="tmls_column" style="width:'.(100/$columns_number).'%; border-color:'.$border_color.';">';
		include('styles/style2.php');
		$html.='</div>';
	}
	elseif($style=='style3') {
		$html.='<div class="tmls_column" style="width:'.(100/$columns_number).'%; border-color:'.$border_color.';">';
		include('styles/style3.php');
		$html.='</div>';
	}
	elseif($style=='style4') {
		$html.='<div class="tmls_column" style="width:'.(100/$columns_number).'%; border-color:'.$border_color.';">';
		include('styles/style4.php');
		$html.='</div>';
	}
	elseif($style=='style5') {
		$html.='<div class="tmls_column" style="width:'.(100/$columns_number).'%; border-color:'.$border_color.';">';
		include('styles/style5.php');
		$html.='</div>';
	}
	
	if($current_column==$columns_number) {
		$html.='</div>';
		$current_column=0;
	}
	
	
	
?>