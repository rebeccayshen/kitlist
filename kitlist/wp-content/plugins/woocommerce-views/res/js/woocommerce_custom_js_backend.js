jQuery( document ).ready( function( $ ) {
	$('#ajax_result_batchprocessing').hide();
	//https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/193656037/comments
	//$('#update_settings_div_wc_views').fadeOut(5000);
	var status=$('input[name=woocommerce_views_batchprocessing_settings]:checked', '#woocommerce_views_form').val();
	
	if (!(status=='manually')) {
		$('#woocommerce_batchprocessing_submit').hide();
	}
	$('#manual_id_wc_views').click(function(){
		$('#woocommerce_batchprocessing_submit').show();		
	});
	$('#wp_cron_id_wc_views').click(function(){
		$('#woocommerce_batchprocessing_submit').hide();		
	});
	$('#system_cron_id_wc_views').click(function(){
		$('#woocommerce_batchprocessing_submit').hide();		
	});	
	
	$("#requestformanualbatchprocessing").submit(function (e) {
	    e.preventDefault();    
        //AJAX request for batch processing
	    
	    //Retrieve the manual parameter
	    var manual_parameter=$('#woocommerce_batchprocessing_submit').val();
	    
	    $('#ajax_result_batchprocessing').removeClass('updated');
	    $('#ajax_result_batchprocessing').removeClass('error');
	    $('#ajax_result_batchprocessing').css('display','block');
		var html_ajax_loader='<img src="'+the_ajax_script_wc_views.wc_views_ajax_ajax_loader_gif+'" />';
		$('#ajax_result_batchprocessing').html(html_ajax_loader);	  
			
	    var data = {
	      			action: 'wc_views_ajax_response_admin',
	   				dataType: 'json',	   				
	 	    		wpv_wc_views_ajax_response_admin_nonce: the_ajax_script_wc_views.wc_views_ajax_response_admin_nonce,
	 		    	wpv_manual_parameter:manual_parameter	 			    					
	    			};	

	        //Do an ajax request 			

	 	     $.post(the_ajax_script_wc_views.ajaxurl,data, function(response) {
	 		 var myObj_wc_views_status = $.parseJSON(response);
	         
	 		 if (myObj_wc_views_status.status=='updated'){
	 			$('#ajax_result_batchprocessing').show();
	 			$('#ajax_result_batchprocessing').addClass(myObj_wc_views_status.status);
	 			$('#ajax_result_batchprocessing').text(myObj_wc_views_status.batch_processing_output);
	 			$('#ajax_result_batchprocessing').fadeOut(5000);
	 			//Remove first update if it exist
	 			$('#update_needed_wcviews').remove();
	 			//Display last run date and time
	 			$('#ajax_result_batchprocessing_time').text(the_ajax_script_wc_views.wc_views_last_run_translatable_text +myObj_wc_views_status.last_run);
	 			
	 		 }
	 		 if (myObj_wc_views_status.status=='error') {
		 			$('#ajax_result_batchprocessing').addClass(myObj_wc_views_status.status);
		 			$('#ajax_result_batchprocessing').text(myObj_wc_views_status.batch_processing_output);		 			
		 		 }	 		 
	 	     });	     
	    
	});	
	
	/*WooCommerce Views 2.4*/

	$('a.show_path_link').click(function() {	

		//Get div tag unique selector unique to this clicked element
		var div_tag_selector=$(this).parent('div');		
		var div_tag_id=div_tag_selector.attr('id');	
		
		if (div_tag_id.toLowerCase().indexOf("archive_") >= 0) {
			var template_nick_name= 'woocommerce_views_archivetemplate_to_override';
		} else if (div_tag_id.toLowerCase().indexOf("ptag_") >= 0) {
			var template_nick_name= 'woocommerce_views_template_to_override';		
		}
		//Show path link is clicked
		//Retrieved paths based on value unique to this clicked element		
		var wcviews_template_path=$('div#'+div_tag_id+' '+'input[name="'+template_nick_name+'"]').val();
		
		//Get WooCommerce default single product path
		var default_wc_single_prod_path=the_ajax_script_wc_views.wc_views_wc_default_single_product_template;
		
		//Get WooCommerce default archive product path
		var default_wc_archive_prod_path= the_ajax_script_wc_views.wc_views_wc_default_archive_product_template;
		
		//Get Show path and hide path translatable text
		var show_path_text=the_ajax_script_wc_views.wc_views_show_path_text;
		var hide_path_text= the_ajax_script_wc_views.wc_views_hide_path_text;
		
		//Replace 'Use WooCommerce Default Tempalates' text with actual path
		if ('Use WooCommerce Default Templates' == wcviews_template_path) {			
			wcviews_template_path = default_wc_single_prod_path;			
		}
		if ('Use WooCommerce Default Archive Templates' == wcviews_template_path) {			
			wcviews_template_path = default_wc_archive_prod_path;			
		}
		//Add value to div input text area
		$('div#'+div_tag_id+' '+'.show_path_wcviews_div .inputtextpath').val(wcviews_template_path);	
		
		//Slide down path
		$('div#'+div_tag_id+' '+'.show_path_wcviews_div').slideToggle().toggleClass('opened');	
		
		var isVisible = $('div#'+div_tag_id+' '+'.show_path_wcviews_div').is( ".opened" );
		
		if (isVisible === true ){ 
			
			//Show hide path text
			$('div#'+div_tag_id+' '+'.show_path_link').text(hide_path_text);			
			
		} else {
			
			//Show path text
			$('div#'+div_tag_id+' '+'.show_path_link').text(show_path_text);
		} 

	});

	//Tooltip pointers
	$('.js-wcviews-display-tooltip').click(function(){
		var $thiz = $(this);
        
		// hide this pointer if other pointer is opened.
		$('.wp-pointer').fadeOut(100);

		$(this).pointer({
			content: '<h3>'+$thiz.data('header')+'</h3><p>'+$thiz.data('content')+'</p>',
			position: {
				edge: 'left',
				align: 'center',
				offset: '15 0'
			}
		}).pointer('open');
	});	

});