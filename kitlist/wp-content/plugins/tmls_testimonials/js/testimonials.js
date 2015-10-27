(function($){
$(document).ready(function(){
	
	var tmls_forms = $('.tmls_form.tmls_notready');
	var tmls_sliders = $('.tmls.tmls_notready .tmls_slider, .tmls.tmls_notready .tmls_slider2');
	var tmls_style3 = $('.style3.tmls_style3_notready');
	
	if ( self != top ) {
		$.tmls_findNotReadyInserted();
	}
	else {
		
		// Submission Form
	
		if (tmls_forms.length )
		{
			tmls_forms.each(function(){
				$(this).removeClass('tmls_notready');
				$.tmls_runFormsScripts($(this));
			});
		}
		
		
		// Sliders
	
		if (tmls_sliders.length )
		{
			tmls_sliders.each(function(){
				$(this).parent().removeClass('tmls_notready');
				$.tmls_runSlidersScripts($(this));
			});
		}
		
		// Style3
	
		if (tmls_style3.length )
		{
			tmls_style3.each(function(){
				$(this).removeClass('tmls_style3_notready');
				$.tmls_runStyle3Scripts($(this));
			});
		}
		
		
		
		
	}
		
});


$.tmls_findNotReadyInserted = function() {
	
	var tmls_forms = $('.tmls_form.tmls_notready');
	var tmls_sliders = $('.tmls.tmls_notready .tmls_slider, .tmls.tmls_notready .tmls_slider2');
	var tmls_style3 = $('.style3.tmls_style3_notready');
	
	// Submission Form
	
	if (tmls_forms.length )
	{
		tmls_forms.each(function(){
			$(this).removeClass('tmls_notready');
			$.tmls_runFormsScripts($(this));
		});
	}
		
		
	// Sliders
	
	if (tmls_sliders.length )
	{
		tmls_sliders.each(function(){
			$.tmls_runSlidersScripts($(this));
			$(this).parent().parent().removeClass('tmls_notready');
		});
	}
		
	// Style3
	
	if (tmls_style3.length )
	{
		tmls_style3.each(function(){
			$(this).removeClass('tmls_style3_notready');
			$.tmls_runStyle3Scripts($(this));
		});
	}
	
	setTimeout(function() {
		$.tmls_findNotReadyInserted();
	},1000);
	
}



$.tmls_runFormsScripts = function( tmls_form ) {
	
	var tmls_form_submit = tmls_form.find('.tmls_form_submit');
	
	if (tmls_form_submit.length )
	{
		tmls_form_submit.mouseover(function(){
			$(this).css('color',$(this).attr('data-hoverfontcolor'));
			$(this).css('border-color',$(this).attr('data-hoverbordercolor'));
			$(this).css('background-color',$(this).attr('data-hoverbgcolor'));
		});
		
		tmls_form_submit.mouseleave(function(){
			$(this).css('color',$(this).attr('data-fontcolor'));
			$(this).css('border-color',$(this).attr('data-bordercolor'));
			$(this).css('background-color',$(this).attr('data-bgcolor'));
		});
	}
	
}

$.tmls_runSlidersScripts = function( tmls_slider ) {
	
	/*======================== Slider ========================*/
		
	var tmls_visible_slider_buttons = tmls_slider.parent().parent().find('.tmls_next_prev.tmls_visible');

	
	tmls_slider_play(tmls_slider);
			
	tmls_slider.parent().parent().mouseenter(function(){
		$(this).children('.tmls_show_on_hover').slideToggle();
	});
			
	tmls_slider.parent().parent().mouseleave(function(){
		$(this).children('.tmls_show_on_hover').slideToggle();
	});
			
		
	tmls_visible_slider_buttons.fadeIn();

}

$.tmls_runStyle3Scripts = function( tmls_style3 ) {
	
	var tmls_style3_names = tmls_style3.find('.tmls_name');
	
	if(tmls_style3_names.length) {
		tmls_style3_names.each(function(){
				
			var tmls_style3_infos_height_sum = ($(this).height()+ 2.5 + $(this).parent().children('.tmls_position').height()+$(this).parent().children('.tmls_rating').height()+5);
			var tmls_style3_img_height = $(this).parent().children('.tmls_image').height();
				
			if(tmls_style3_infos_height_sum < tmls_style3_img_height && $(this).parent().children('.tmls_image').css('display')!='none' ) {
				$(this).css('padding-top', (tmls_style3_img_height/2) - (tmls_style3_infos_height_sum/2));
			}
			else {
				$(this).css('padding-top',0);
			}
				
		});
	}
	
}

$(window).load(function(){

	var tmls_sliders = $('.tmls_slider, .tmls_slider2');
	tmls_sliders.each(function(){
		tmls_slider_play($(this));
	});
	
});

$(window).resize(function() {

	var tmls_sliders = $('.tmls_slider, .tmls_slider2');
	var tmls_style3 = $('.style3');
	
	tmls_slider_play(tmls_sliders);
	
	if (tmls_style3.length )
	{
		tmls_style3.each(function(){
			$.tmls_runStyle3Scripts($(this));
		});
	}
	
});


function tmls_slider_play(tmls_slider) {
	
	tmls_slider.carouFredSel({
		responsive: true,
		width:'variable',
		height:'variable',
		prev: {
			button: function() {
				return $(this).parents().children(".tmls_next_prev").children(".tmls_prev");
			}
		},
		next: {
			button: function() {
				return $(this).parents().children(".tmls_next_prev").children(".tmls_next");
			}
		},
		pagination: {
			container: function() {
				return $(this).parents().next().children('.tmls_paginationContainer');
			},
			anchorBuilder	: function(nr) {
				return "<div class='tmls_image_container "+$(this).attr('data-imageradius')+"'><div class='tmls_image' style='"+$(this).attr('data-bgimg')+"'></div><div class='tmls_image_overlay' style='background-color:"+$(this).parent().attr('data-slider2unselectedoverlaybgcolor')+"'></div></div>";
			}
		},
		scroll: {
			items:1,          
			duration: tmls_slider.data('scrollduration'),
			fx: tmls_slider.data('transitioneffect')
		},
		auto: {
			play: tmls_slider.data('autoplay'),
			timeoutDuration:tmls_slider.data('pauseduration'),
			pauseOnHover:tmls_slider.data('pauseonhover')
		},
		items: {
			width:700
		},
		swipe: {
			onMouse: false,
			onTouch: true
		}
				
	});
			
}

}) (jQuery);