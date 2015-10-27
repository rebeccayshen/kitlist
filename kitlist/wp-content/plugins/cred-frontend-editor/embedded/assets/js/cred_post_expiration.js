jQuery( document ).ready(function($) {
	try {
		var cred = window.cred_cred;
		if ( $('#credpostexpirationdiv').length ) {
			cred.app.attach('cred.notificationEventChanged', enablePlaceholders);
			$('input[name="_cred_post_expiration[enable]"]').change(function(e) {
				var cred_post_expiration_enabled = $('input[name="_cred_post_expiration[enable]"]').is(':checked');
				if (cred_post_expiration_enabled) {
					$('.cred_post_expiration_panel, .cred_post_expiration_options').fadeIn('fast')
				} else {
					$('.cred_post_expiration_options').each(function(index, element) {
						var $option = $(element);
						if ($('input[type="radio"]', $option).is(':checked')) {
							$('input[type="radio"]:visible:first', $option.siblings()).first().attr('checked', 'checked');
						}
					}).hide();
					$('.cred_post_expiration_panel').fadeOut('fast');
				}
			}).change();
		}
		if ($('#cred_post_expiration_meta_box').length) {
			$('input[name="cred_pe[_cred_post_expiration_time][enable]"]').change(function(e) {
				var cred_post_expiration_enabled = $(this).is(':checked');
				if (cred_post_expiration_enabled) {
					$('.cred_post_expiration_panel').fadeIn('fast')
				} else {
					$('.cred_post_expiration_panel').fadeOut('fast');
				}
			}).change();
		}
	} catch(e) {}

	function enablePlaceholders() {
		try {
			if ($('input[name="_cred_post_expiration[enable]"]').is(':checked')) {
				$('.cred_post_expiration_options').show();
			} else {
				$('.cred_post_expiration_options').each(function(index, element) {
					var $option = $(element);
					if ($('input[type="radio"]', $option).is(':checked')) {
						$('input[type="radio"]:visible:first', $option.siblings()).first().attr('checked', 'checked');
					}
				}).hide();
			}
		} catch(e) {}
	}
	
	$('.js-cred-post-expiration-datepicker').datepicker({
		onSelect: function( dateText, inst ) {
			//	This is going to be nasty and hacky, but we do not have another way of knowing what the auxiliar element is
			var el = $('.js-cred-post-expiration-datepicker'),
			el_aux,
			el_clear;
			el.val('');
			if ( el.closest('.js_cred_post_expiration_panel').length > 0 ) {
				// For post expiration dates
				el_aux = el.closest('.js_cred_post_expiration_panel').find('.js-wpt-date-auxiliar');
				el_clear = el.closest('.js_cred_post_expiration_panel').find('.js-cred-pe-date-clear');
			} else {
				// This should be an empty object, but as we use the variable later we need to set it
				el_aux = el.closest('.js_cred_post_expiration_panel');
				el_clear = el.closest('.js_cred_post_expiration_panel');
			}
			var data = 'date=' + dateText;
			data += '&date-format=' + CREDExpirationScript.dateFormatPhp;
			data += '&action=cred_post_expiration_date';
			$.post( CREDExpirationScript.ajaxurl, data, function( response ) {
				response = $.parseJSON( response );
				if ( el_aux.length > 0 ) {
					el_aux.val( response['timestamp'] );
				}
				el.val( response['display'] );
				el_clear.show();
			});
			//el.trigger('wptDateSelect');
		},
		showOn: "both",
		buttonImage: CREDExpirationScript.buttonImage,
		buttonImageOnly: true,
		buttonText: CREDExpirationScript.buttonText,
		dateFormat: 'ddmmyy',
		changeMonth: true,
		changeYear: true,
		yearRange: CREDExpirationScript.yearMin+':'+CREDExpirationScript.yearMax
	});
	
	$( document ).on( 'click', '.js-cred-pe-date-clear', function() {
		var el = $('.js-cred-post-expiration-datepicker'),
		el_aux;
		el.val('');
		if ( el.closest('.js_cred_post_expiration_panel').length > 0 ) {
			// For post expiration dates
			el_aux = el.closest('.js_cred_post_expiration_panel').find('.js-wpt-date-auxiliar');
		} else {
			// This should be an empty object, but as we use the variable later we need to set it
			el_aux = el.closest('.js_cred_post_expiration_panel');
		}
		el_aux.val( '' ).trigger( 'change' );
		$( '.js-cred-pe-date-hour, .js-cred-pe-date-minute' ).val( '0' );
		$( this ).hide();
	});
	
});
