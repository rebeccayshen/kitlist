/*
* This is final version.
*
* No more code added here.
* Only bugfixes.
*
*/
var wpcfAccess = wpcfAccess || {};

(function(window, $, undefined) {

$(document).ready(function() {
    
    var $dialog_callback = '',
    $dialog_callback_params = [];
    wpcfAccessSpinnerContent = $(
        '<div style="min-height: 150px;">' +
            '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
                '<div class="otg-access-spinner"><i class="icon icon-spinner icon-spin"></i></div>' +
            '</div>' +
        '</div>'
    );
    
    $( window ).load(function() {
        if ( $('#wpcf_access_admin_form input').length > $('#js-max-input-vars').val() ){
            $access_dialog_open( 500 );
        
        $( '.js-wpcf-access-gui-close' ).css('display','none');
		$( '.js-wpcf-access-process-button .ui-button-text' ).html( $('#js-max-input-vars').data('btn') );
        $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html( $('#js-max-input-vars').data('header') );
        
        wpcf_access_dialog_container.html( wpcfAccessSpinnerContent );
        
        var data = {
				action : 'wpcf_access_delete_role_form',
				role : $(this).data('role'),
				wpnonce : $('#wpcf-access-error-pages').attr('value')
		};
		
        $dialog_callback = $hide_fields_message_dialog;
        $dialog_callback_params = [];
        data = ''+$('#js-max-input-vars').data('message').replace('<!NUM!>', $('#wpcf_access_admin_form input').length)
            +'<p><br><input type="checkbox" value="1" id="js-hide-max-fields-message" /> <label for="js-hide-max-fields-message">'+$('#js-max-input-vars').data('hide_error') + '</label></p>';
            
			
    	 wpcf_access_dialog_container.html( data );
                $( '.js-wpcf-access-process-button' )
					.addClass( 'button-primary' )
					.removeClass( 'button-secondary' )
					.prop( 'disabled', false );       
        
        }
    });
    
    $hide_fields_message_dialog = function(e) {
		if ( $('#js-hide-max-fields-message').prop('checked') ){
            var data = {
                action : 'wpcf_hide_max_fields_message',
                wpnonce : $('#wpcf-access-edit').attr('value')
            };
            $.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
                wpcf_access_dialog_container.dialog('close');
            }
          });
        }
	};
    
    $(document).on('click', '.js-dialog-close', function(e) {
        e.preventDefault();
        $.colorbox.close();
    });

	// Show tooltips
	$('.js-tooltip').hover( function() {
		var $this = $(this);
		var $tooltip = $('<div class="tooltip">' + $this.text() + '</div>');

		if( $this.children().outerWidth() < $this.children()[0].scrollWidth ) {
			$tooltip
					.appendTo($this)
					.css({
						'visibility': 'visible',
						'left': -1 * ($tooltip.outerWidth() / 2) + $this.width() / 2
					})
					.hide()
					.fadeIn('600');
		};
	}, function() {
		$(this)
				.find('.tooltip')
				.remove();
	});

	// Count table columns
	$.each( $('.js-access-table'), function() {
		var columns = $(this).find('th').length;
		$(this).addClass('columns-'+ columns);
	});
    
   
    
    
	$(document).on('click', '.js-wpcf-access-delete-role', function(e) {
		e.preventDefault();
        
        $access_dialog_open( 500 );
        
        $( '.js-wpcf-access-gui-close .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_close );
		$( '.js-wpcf-access-process-button .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_save );
        $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html( wpcf_access_dialog_texts.wpcf_delete_role );
        
        wpcf_access_dialog_container.html( wpcfAccessSpinnerContent );
        
        var data = {
				action : 'wpcf_access_delete_role_form',
				role : $(this).data('role'),
				wpnonce : $('#wpcf-access-error-pages').attr('value')
		};
		
        $dialog_callback = $confirm_remove_role;
        $dialog_callback_params['role'] = $(this).data('role');
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
                wpcf_access_dialog_container.html( data );
                $( '.js-wpcf-access-process-button' )
					.addClass( 'button-primary' )
					.removeClass( 'button-secondary' )
					.prop( 'disabled', false );               	
            }
        });       
    });
    
    $confirm_remove_role =  function( params) {
        if ( $(this).attr('disabled') ) {
            return false;
        }
        var role = params['role'];
        var data = {
				action : 'wpcf_access_delete_role',
                wpcf_access_delete_role: role,                
                wpcf_reassign : $('[name="wpcf_reassign"]').val(),
				wpnonce : $('#wpcf-access-error-pages').attr('value')
		};
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
                window.location = 'admin.php?page=wpcf-access#custom-roles';
                window.location.reload(true);             	
            }
        });     
    };
	
	jQuery(document).on('click', '.js-dialog-close', function(e){
        jQuery('.editor_addon_dropdown').css({
            'visibility': 'hidden'
            //'display' : 'inline'
        });
    });
	
	// Show editor dropdown
	$(document).on('click', '.js-wpcf-access-editor-button', function(e) {
		e.preventDefault();
		var drop_down = $(this).parent().find('.js-wpcf-access-editor-popup');
		
		if ( drop_down.css('visibility') === 'hidden' ) {
            icl_editor_popup(drop_down);
        }
        jQuery('#content_ifr').contents().bind('click', function(e) {
            jQuery('.editor_addon_dropdown').css({
                'visibility': 'hidden'
                //'display' : 'inline'
            });
        });
        var textbox = $(document.createElement('textarea')).attr({
            "class" : 'js-wpcf-access-conditional-message',                  
            rows : 6
        });
        $(this).parent().find('input.js-wpcf-access-conditional-message').replaceWith(textbox);
		$('.js-wpcf-access-list-roles').prop('checked',false);
		$('.js-wpcf-access-shortcode-operator').eq(0).prop('checked',true);
		$('.js-wpcf-access-add-shortcode').prop('disabled', true).addClass('button-secondary').removeClass('button-primary');
		$('.js-wpcf-access-conditional-message').val('');

        // Bind Escape
        jQuery(document).bind('keyup', function(e) {
            if (e.keyCode == 27) {
                jQuery('.editor_addon_dropdown').css({
                    'visibility': 'hidden'
                    //'display' : 'inline'
                });
                jQuery(this).unbind(e);
            }
        });
	});
	
	$(document).on('click', '.js-wpcf-access-import-button', function(e) {
		$('.toolset-alert').remove();
		if ( $('.js-wpcf-access-import-file').val() === '' ){
			$('<p class="toolset-alert toolset-alert-error" style="display: block; opacity: 1;">'+$(this).data('error')+'</p>').insertAfter( ".js-wpcf-access-import-button" )
			return false;
		}else{
			return true;	
		}
	});
	$(document).on('change', '.js-wpcf-access-import-file', function(e) {
		$('.toolset-alert').remove();	
	});
	
	//Enable insert shortocde button when one or more roles selected
	$(document).on('change', '.js-wpcf-access-list-roles', function() {
		$('.js-wpcf-access-add-shortcode').prop('disabled', true).addClass('button-secondary').removeClass('button-primary');
		if ( $('.js-wpcf-access-list-roles:checked').length > 0 ){
			$('.js-wpcf-access-add-shortcode').prop('disabled',false).addClass('button-primary').removeClass('button-secondary');	
		}
	});
	
	//Insert shortocde to editor
	$(document).on('click', '.js-wpcf-access-add-shortcode', function(e) {
		shortcode = '[toolset_access role="';
		shortcode += $('.js-wpcf-access-list-roles:checked').map(function(){
			return $(this).val();
		}).get().join(",");
		shortcode += '" operator="'+ $('input[name="wpcf-access-shortcode-operator"]:checked').val() +'"]'+ $(this).parent().parent().find('.js-wpcf-access-conditional-message').val() +'[/toolset_access]';
		window.wpcfActiveEditor = jQuery(this).data('editor');
        icl_editor.insert(shortcode);
        jQuery('.editor_addon_dropdown').css({'visibility': 'hidden'});
		return false;
	});

    //Show Role caps (read only)
    $(document).on('click', '.wpcf-access-view-caps', function(e) {    
        e.preventDefault();
        
        $access_dialog_open( 400 );
        
        $( '.js-wpcf-access-gui-close .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_close );
		$( '.js-wpcf-access-process-button').css('display','none');
        $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html( wpcf_access_dialog_texts.wpcf_role_permissions );
        
        wpcf_access_dialog_container.html( wpcfAccessSpinnerContent );
        
        var data = {
				action : 'wpcf_access_show_role_caps',
				role : $(this).data('role'),
				wpnonce : $('#wpcf-access-error-pages').attr('value')
		};
		
        $dialog_callback = '';
        $dialog_callback_params = [];
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
                wpcf_access_dialog_container.html( data );
           	
            }
        });
	});
    
    
    
    
    //Access dialog
    $( 'body' ).append( '<div id="js-wpcf-access-dialog-container" class="toolset-shortcode-gui-dialog-container wpcf-access-dialog-container js-wpcf-access-dialog-container"></div>' );
    var wpcf_access_dialog_container = $( "#js-wpcf-access-dialog-container" ).dialog({
		autoOpen: false,
		modal: true,
		minWidth: 450,
		show: { 
			effect: "blind", 
        		duration: 800 
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
                   $( '.js-wpcf-access-process-button ' )
					.addClass( 'button-secondary' )
					.removeClass( 'button-primary ui-button-disabled ui-state-disabled' )
					.prop( 'disabled', true )
                    .css( {'marginLeft':'15px', 'display':'inline'} );
                    $( '.js-wpcf-access-gui-close' ).css('display','inline');
			},
			close: function( event, ui ) {                    
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'button-secondary js-wpcf-access-gui-close',
					text: wpcf_access_dialog_texts.wpcf_close,
					click: function() {
                           $( this ).dialog( "close" );
					}
				},
				{
					class: 'button-primary js-wpcf-access-process-button',
					text: '',
					click: function() {                        
						if ( $dialog_callback != '' ){
                               $dialog_callback.call(null,$dialog_callback_params);
                               $( '.js-wpcf-access-process-button .ui-button-text' ).append(' <i class="js-otg-access-spinner icon icon-spinner icon-spin"></i>');
                           }
					}
				}
            ]
	});
    
    $access_dialog_open = function ( width ){
        var dialog_height = $(window).height() - 100;        
        wpcf_access_dialog_container.dialog('open').dialog({
            title: wpcf_access_dialog_texts.wpcf_change_perms,
            width: width,
            maxHeight: dialog_height,
            draggable: false,
            resizable: false,
			position: { my: "center top+50", at: "center top", of: window }
        });
    }
    
    //Show popup: change custom role permissions
    $(document).on('click', '.wpcf-access-change-caps', function( e ) {
        e.preventDefault();
        
        $access_dialog_open( 800 );
        
        $( '.js-wpcf-access-gui-close .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_close );
		$( '.js-wpcf-access-process-button .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_change_perms );
        $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html( wpcf_access_dialog_texts.wpcf_change_perms );
        
        wpcf_access_dialog_container.html( wpcfAccessSpinnerContent );
        
        var data = {
				action : 'wpcf_access_change_role_caps',
				role : $(this).data('role'),
				wpnonce : $('#wpcf-access-error-pages').attr('value')
		};
		
        $dialog_callback = $role_caps_process;
        $dialog_callback_params = [];
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
                wpcf_access_dialog_container.html( data );
                $( '.js-wpcf-access-process-button' )
					.addClass( 'button-primary' )
					.removeClass( 'button-secondary' )
					.prop( 'disabled', false );               	
            }
        });        
	});
    
    
    //Process: change custom role permissions
	$role_caps_process = function() {
		var caps = [];
		if ( typeof $('input[name="assigned-posts"]') !== 'undefined' ){
			$('input[name="current_role_caps[]"]:checked').each(function() {caps.push($(this).val());});
		}
		var data = {
				action : 'wpcf_process_change_role_caps',
				wpnonce : $('#wpcf-access-error-pages').attr('value'),
				role : $('.js-wpcf-current-edit-role').val(),
				caps : caps
		};
		$('.js-wpcf-access-role-caps-process').prop('disabled', true);
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
               	wpcf_access_dialog_container.dialog('close');
            }
        });

		return false;
	};
    
	
	//Open for for new custom cap
	$(document).on('click', '.js-wpcf-access-add-custom-cap', function() {
		$(this).hide();
		$('.js-wpcf-create-new-cap-form').show();
		$('#js-wpcf-new-cap-slug').focus();
	
		return false;
	});
	
	$(document).on('input', '#js-wpcf-new-cap-slug', function() {
		$('.js-wpcf-new-cap-add').prop('disabled',true).removeClass('button-primary');
		$('.toolset-alert').remove();
		if ( $(this).val() !== '' ){
			$('.js-wpcf-new-cap-add').prop('disabled',false).addClass('button-primary');
		}	
	});
	
	$(document).on('click', '.js-wpcf-new-cap-cancel', function() {
		$('.js-wpcf-access-add-custom-cap').show();
		$('.js-wpcf-create-new-cap-form').hide();
		return false;
	});
	
	$(document).on('click', '.js-wpcf-remove-custom-cap a, .js-wpcf-remove-cap-anyway', function() {
		var div = $(this).data('object');
		var cap = $(this).data('cap');
		var remove = $(this).data('remove');
		var $thiz = $(this);
		var ajaxSpinner = $(this).parent().find('.spinner');
		ajaxSpinner.css('visibility', 'visible');
		var data = {
				action : 'wpcf_delete_cap',
				wpnonce : $('#wpcf-access-error-pages').attr('value'),
				cap_name : cap,
				remove_div : div,
				remove: remove,
				edit_role: $('.js-wpcf-current-edit-role').val()
		};
		$thiz.hide();
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
               	ajaxSpinner.css('visibility', 'hidden');               	
                if ( data == 1 ){
                	$( '#wpcf-custom-cap-'+ cap ).remove();
                	if ( $('.js-wpcf-remove-custom-cap').length == 0 ){
                		$('.js-wpcf-no-custom-caps').show();	
                	}
                }else{
                	$(data).insertAfter( $thiz );	
                }
               	
            }
        });
		return false;
	});
	
	$(document).on('click', '.js-wpcf-remove-cap-cancel', function() {
		$( '.js-wpcf-remove-custom-cap_'+$(this).data('cap') ).find('a').show();
		$( '.js-removediv_'+$(this).data('cap') ).remove();
		return false;
	});
	
	
	
	$(document).on('click', '.js-wpcf-new-cap-add', function(e) {
		var test_cap_name = /^[a-z0-9_]*$/.test($('#js-wpcf-new-cap-slug').val());
		$('.js-wpcf-create-new-cap-form').find('.toolset-alert').remove();
		if ( test_cap_name === false ) {
		    $('.js-wpcf-create-new-cap-form').append('<p class="toolset-alert toolset-alert-error" style="display: block; opacity: 1;">'+$(this).data('error')+'</p>');
		    return false;
		}
		
		var ajaxSpinner = $('.js-new-cap-spinner');
		ajaxSpinner.css('visibility', 'visible');
		var data = {
				action : 'wpcf_create_new_cap',
				wpnonce : $('#wpcf-access-error-pages').attr('value'),
				cap_name : $('#js-wpcf-new-cap-slug').val(),
				cap_description : $('#js-wpcf-new-cap-description').val()
		};
		$('.js-wpcf-new-cap-add').prop('disabled',true).removeClass('button-primary');
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            dataType: 'json',
            success: function(data) {
               	ajaxSpinner.css('visibility', 'hidden');
                 
               	if ( data[0] == 1){
               		$('.js-wpcf-list-custom-caps').append(data[1]);
               		$('#js-wpcf-new-cap-slug,#js-wpcf-new-cap-description').val('');
               		$('.js-wpcf-access-add-custom-cap').show();
					$('.js-wpcf-create-new-cap-form, .js-wpcf-no-custom-caps').hide();
					
               	}else{
               		$('.js-wpcf-create-new-cap-form').append('<p class="toolset-alert toolset-alert-error" style="display: block; opacity: 1;">'+data[1]+'</p>');	
               	}
            }
        });
        return false;
	});
	
    //Show popup from edit post page (assign post to group)
    $(document).on('click', '.js-wpcf-access-assign-post-to-group', function(e) {
		e.preventDefault();
        
        $access_dialog_open( 500 );

        $( '.js-wpcf-access-gui-close .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_cancel );
		$( '.js-wpcf-access-process-button .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_assign_group );
        $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html( wpcf_access_dialog_texts.wpcf_access_group );
        
        wpcf_access_dialog_container.html( wpcfAccessSpinnerContent );
        
        var data = {
				action : 'wpcf_select_access_group_for_post',
				id : $(this).data('id'),
				wpnonce : $('#wpcf-access-error-pages').attr('value')
		};
		
        $dialog_callback = $process_access_assign_post_to_group;
        $dialog_callback_params['id'] = $(this).data('id');
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
                wpcf_access_dialog_container.html( data );
                if ( $('input[name="wpcf-access-group-method"]:checked').val() == 'existing_group' ){
                    $( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
                    $('select[name="wpcf-access-existing-groups"]').show();
                }    
            }
        });       
        
	});
    
    $process_access_assign_post_to_group = function( params ) {
		id = params['id'];
		var data = {
				action : 'wpcf_process_select_access_group_for_post',
				wpnonce : $('#wpcf-access-error-pages').attr('value'),
				id : id,
				methodtype : $('input[name="wpcf-access-group-method"]:checked').val(),
				group : $('select[name="wpcf-access-existing-groups"]').val(),
				new_group :  $('input[name="wpcf-access-new-group"]').val()
		};
		$( '.js-wpcf-access-process-button ' )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary ui-button-disabled ui-state-disabled' )
						.prop( 'disabled', true );
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
               	if ( data != 'error'){
               		$('.js-wpcf-access-post-group').html(data);
               		wpcf_access_dialog_container.dialog('close');
               	} else {
               		$('.js-error-container').html('<p class="toolset-alert toolset-alert-error " style="display: block; opacity: 1;">'+wpcf_access_dialog_texts.wpcf_group_exists+'</p>');
                    $('.js-otg-access-spinner').remove();
               		$( '.js-wpcf-access-process-button ' )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary ui-button-disabled ui-state-disabled' )
						.prop( 'disabled', true );
               	}
            }
        });

		return false;
	};

	$(document).on('change', 'input[name="wpcf-access-group-method"]', function() {
		$('select[name="wpcf-access-existing-groups"],input[name="wpcf-access-new-group"]').hide();
		$( '.js-wpcf-access-process-button ' )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary ui-button-disabled ui-state-disabled' )
						.prop( 'disabled', true );
		if ( $(this).val() == 'existing_group' ){
			$('select[name="wpcf-access-existing-groups"]').show();
			if ( $('select[name="wpcf-access-existing-groups"]').val() != '' ){
				$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
			}
		}
		else{
			$('input[name="wpcf-access-new-group"]').show();
			$('input[name="wpcf-access-new-group"]').focus();
			if ( $('input[name="wpcf-access-new-group"]').val() !== '' ){
				$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
			}
		}
	});

    $(document).on('change', 'select[name="wpcf-access-existing-groups"]', function() {
		$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
	});

	$(document).on('input', 'input[name="wpcf-access-new-group"]', function() {
		$( '.js-wpcf-access-process-button ' )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary ui-button-disabled ui-state-disabled' )
						.prop( 'disabled', true );
		$('.js-error-container').html('');
		if ( $(this).val() != '' ){
			$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
		}
	});

	

    $(document).on('click', '.js-wpcf-add-error-page', function(e) {
		e.preventDefault();
        
        $access_dialog_open( 500 );

        $( '.js-wpcf-access-gui-close .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_cancel );
		$( '.js-wpcf-access-process-button .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_set_errors );
        $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html( wpcf_access_dialog_texts.wpcf_set_errors );
        
        wpcf_access_dialog_container.html( wpcfAccessSpinnerContent );
        
        var data = {
				action : 'wpcf_access_show_error_list',
				access_type : $(this).data('typename'),
				access_value : $(this).data('valuename'),
				cur_type : $(this).data('curtype'),
				cur_value : $(this).data('curvalue'),
				access_archivetype : $(this).data('archivetypename'),
				access_archivevalue : $(this).data('archivevaluename'),
				cur_archivetype : $(this).data('archivecurtype'),
				cur_archivevalue : encodeURIComponent($(this).data('archivecurvalue')),
				posttype: $(this).data('posttype'),
				is_archive: $(this).data('archive'),
				forall : $(this).data('forall'),
				wpnonce : $('#wpcf-access-error-pages').attr('value')
		};
		
        $dialog_callback = $set_error_page;
        $dialog_callback_params['id'] = [];
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
                wpcf_access_dialog_container.html( data );
                check_errors_form();                    
            }
        });
        
	});
    
    // 'Set error page' popup
	$set_error_page = function() {
		var text = valname = typename = archivevalname = archivetypename = '';

		typename = $('input[name="error_type"]:checked').val();
		archivetypename = $('input[name="archive_error_type"]:checked').val();

		if ( $('input[name="error_type"]:checked').val() === 'error_php' ) {
			text = wpcf_access_dialog_texts.wpcf_info2 + ': ' + $('select[name="wpcf-access-php"] option:selected').text();
			valname = $('select[name="wpcf-access-php"]').val();
			link_error = wpcf_access_dialog_texts.wpcf_error3 + valname;

		} else if ( $('input[name="error_type"]:checked').val() === 'error_ct' ) {
			text = wpcf_access_dialog_texts.wpcf_info1 + ': ' + $('select[name="wpcf-access-ct"] option:selected').text();
			valname = $('select[name="wpcf-access-ct"]').val();
			link_error = wpcf_access_dialog_texts.wpcf_error2 + $('select[name="wpcf-access-ct"] option:selected').text();
		} else if ( $('input[name="error_type"]:checked').val() === 'error_404' ) {
			text = '404';
			link_error = wpcf_access_dialog_texts.wpcf_error1;
			archivetypename = '';
		} else {
			text = '';
			typename = '';
			link_error = '';
		}
		
		
		if ( $('input[name="archive_error_type"]').val() !== "undefined" ) {
			if ( $('input[name="archive_error_type"]:checked').val() === 'error_php' ) {
				archivetext =  wpcf_access_dialog_texts.wpcf_info3 + ': ' + $('select[name="wpcf-access-archive-php"] option:selected').text();
				archivevalname = $('select[name="wpcf-access-archive-php"]').val();
                archivetypename = $('input[name="archive_error_type"]:checked').val();
               
			} else if ( $('input[name="archive_error_type"]:checked').val() === 'error_ct' ) {
				archivetext =  wpcf_access_dialog_texts.wpcf_info4 + ': ' + $('select[name="wpcf-access-archive-ct"] option:selected').text();
				archivevalname = $('select[name="wpcf-access-archive-ct"]').val();
                archivetypename = $('input[name="archive_error_type"]:checked').val();
			} else if ( $('input[name="archive_error_type"]:checked').val() === 'default_error' ) {
				archivetext =  wpcf_access_dialog_texts.wpcf_info5;
				archivevalname = '';
                archivetypename = '';
			} else {
				archivetext = '';
				archivetypename = '';
			}
		}

        $('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('.js-error-page-name').html(text);
		$('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('a').data('curtype', typename);
		$('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('a').data('curvalue', valname);
		$('input[name="' + $('input[name="valuename"]').val() + '"]').val(valname);
		$('input[name="' + $('input[name="typename"]').val() + '"]').val(typename);
		$('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('.js-wpcf-add-error-page').attr("title",link_error);
		if ( $('input[name="archive_error_type"]').val() !== "undefined" ) {
			$('input[name="' + $('input[name="archivetypename"]').val() + '"]').parent().find('.js-archive_error-page-name').html(archivetext);
			$('input[name="' + $('input[name="archivevaluename"]').val() + '"]').val(archivevalname);
			$('input[name="' + $('input[name="archivetypename"]').val() + '"]').val(archivetypename);
            $('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('a').data('archivecurtype', archivetypename);
            $('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('a').data('archivecurvalue', archivevalname);
		}

		wpcf_access_dialog_container.dialog('close');
	};
    
    function check_errors_form(){
		
		$('select[name="wpcf-access-ct"], select[name="wpcf-access-php"]').hide();
		$( '.js-wpcf-access-process-button' )
            .removeClass( 'button-primary' )
            .addClass( 'button-secondary' )
            .prop( 'disabled', true );

		if ( $('input[name="error_type"]:checked').val() == 'error_php' ) {
			$('select[name="wpcf-access-php"]').show();
			if ( $('select[name="wpcf-access-php"]').val() !== '') {
				$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
			}else{
				return;	
			}
		} else if ( $('input[name="error_type"]:checked').val() == 'error_ct' ) {
			$('select[name="wpcf-access-ct"]').show();
			if ( $('select[name="wpcf-access-ct"]').val() !== '') {
				$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
			}else{
				return;	
			}
		} else {
			$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
		}
		
		
		$('select[name="wpcf-access-archive-ct"], select[name="wpcf-access-archive-php"],.js-wpcf-error-php-value-info,.js-wpcf-error-ct-value-info').hide();
		$( '.js-wpcf-access-process-button' )
            .removeClass( 'button-primary' )
            .addClass( 'button-secondary' )
            .prop( 'disabled', true );

		if ( $('input[name="archive_error_type"]:checked').val() == 'error_php' ) {
			$('select[name="wpcf-access-archive-php"], .js-wpcf-error-php-value-info').show();
			if ( $('select[name="wpcf-access-archive-php"]').val() !== '') {
				$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
			}else{
				return;	
			}
		} else if ( $('input[name="archive_error_type"]:checked').val() == 'error_ct' ) {
			$('select[name="wpcf-access-archive-ct"], .js-wpcf-error-ct-value-info').show();
			if ( $('select[name="wpcf-access-archive-ct"]').val() !== '') {
				$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
			}else{
				return;	
			}
		} else {
			$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
		}
	}
    
	$(document).on('change', '.js-wpcf-access-type-archive', function() {
		check_errors_form();
	});

	$(document).on('change', '.js-wpcf-access-type', function() {
		check_errors_form();
	});

	$(document).on('change', 'select[name="wpcf-access-php"], select[name="wpcf-access-ct"], select[name="wpcf-access-archive-php"], select[name="wpcf-access-archive-ct"]', function() {
		$( '.js-wpcf-access-process-button' )
            .removeClass( 'button-primary' )
            .addClass( 'button-secondary' )
            .prop( 'disabled', true );

		if ( $(this).val() !== '' ) {
			$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
		}
	});

	$(document).on('click', '.js-wpcf-search-posts', function() {

		$('.js-wpcf-search-posts').prop('disabled', true);
		var data = {
				action : 'wpcf_search_posts_for_groups',
				wpnonce : $('#wpcf-access-error-pages').attr('value'),
				title : $('#wpcf-access-suggest-posts').val()
		};
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
				$('.js-use-search').hide();
				$('.js-wpcf-suggested-posts ul').html(data);
				$('.js-wpcf-search-posts').prop('disabled', false);
            }
        });

		return false;
	});
    
    $(document).on('click', '.js-wpcf-search-posts-clear', function() {
        $('#wpcf-access-suggest-posts').val('');
        $('.js-wpcf-suggested-posts ul li').remove();

		return false;
	});

	// Add posts
	$(document).on('click', '.js-wpcf-add-post-to-group', function() {
		var li = '.js-assigned-access-post-' + $(this).data('id');

		if (typeof $(li).html() === 'undefined') {
			$('.js-no-posts-assigned').hide();
			$( ".js-wpcf-assigned-posts ul" ).append('<li class="js-assigned-access-post-'+$(this).data('id')+'">'+
			$(this).data('title')+' <a href="" class="js-wpcf-unassign-access-post" data-id="'+$(this).data('id')+'">Remove</a>'+
			'<input type="hidden" value="'+ $(this).data('id')+'" name="assigned-posts[]"></li>');

			$(this)
					.parent()
					.remove();
		}

		if ( $('.js-wpcf-suggested-posts ul').is(':empty') ) {
			$('.js-use-search').fadeIn('fast');
		}

		return false;
	});

	// Remove posts
	$(document).on('click', '.js-wpcf-unassign-access-post', function() {
		var li = '.js-assigned-access-post-'+$(this).data('id');
		$(li).remove();
		var data = {
				action : 'wpcf_remove_postmeta_group',
				wpnonce : $('#wpcf-access-error-pages').attr('value'),
				id : $(this).data('id')
		};
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
				if ( $('.js-wpcf-assigned-posts ul').is(':empty') ) {
					$('.js-no-posts-assigned').fadeIn('fast');
				}
            }
        });
		return false;
	});


    $(document).on('click', '.js-wpcf-remove-group', function(e) {
		e.preventDefault();
        
        $access_dialog_open( 400 );

        $( '.js-wpcf-access-gui-close .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_cancel );
		$( '.js-wpcf-access-process-button .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_remove_group );
        $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html( wpcf_access_dialog_texts.wpcf_custom_access_group );
        
        wpcf_access_dialog_container.html( wpcfAccessSpinnerContent );
        
        var data = {
				action : 'wpcf_remove_group',
				group_id : $(this).data('group'),
				wpnonce : $('#wpcf-access-error-pages').attr('value')
		};

        $dialog_callback = $delete_group_process;
        $dialog_callback_params['id'] = $(this).data('group');
        $dialog_callback_params['divid'] = $(this).data('groupdiv');
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
                wpcf_access_dialog_container.html( data );
                if ( $('.js-wpcf-assigned-posts ul').is(':empty') ) {
					$('.js-no-posts-assigned').show();
				}
                $( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
                  
            }
        });  
        
	});

	$delete_group_process = function( params ) {
        group_id = params['id'];
		var data = {
				action : 'wpcf_remove_group_process',
				wpnonce : $('#wpcf-access-error-pages').attr('value'),
				group_id : group_id
		};

		$( '.js-wpcf-access-process-button' )
                        .removeClass( 'button-primary' )
                        .addClass( 'button-secondary' )
                        .prop( 'disabled', true );
        
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
               var div = '#js-box-'+params['divid'];
               $(div).fadeOut('500', function() {
				   $(div).remove();
			   });
               wpcf_access_dialog_container.dialog('close');
            }
        });
		
	};



	$(document).on('click', '.js-wpcf-add-new-access-group', function(e) {
		
        e.preventDefault();
        
        $access_dialog_open( 500 );

        $( '.js-wpcf-access-gui-close .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_cancel );
		$( '.js-wpcf-access-process-button .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_add_group );
        $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html( wpcf_access_dialog_texts.wpcf_custom_access_group );
        
        wpcf_access_dialog_container.html( wpcfAccessSpinnerContent );
        
        var data = {
				action : 'wpcf_access_add_new_group_form',
				wpnonce : $('#wpcf-access-error-pages').attr('value')
		};
		
        $dialog_callback = $process_new_access_group;
        $dialog_callback_params['id'] = [];
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
                wpcf_access_dialog_container.html( data );
                $( '.js-wpcf-access-process-button' )
                        .removeClass( 'button-primary' )
                        .addClass( 'button-secondary' )
                        .prop( 'disabled', true );
                  
            }
        });        
        
	});
    
    $process_new_access_group = function() {
		var posts = [];

		if ( typeof $('input[name="assigned-posts"]') !== 'undefined' ) {
			$('input[name="assigned-posts[]"]').each(function() {
				posts.push( $(this).val() );
			});
		}

		var data = {
				action : 'wpcf_process_new_access_group',
				wpnonce : $('#wpcf-access-error-pages').attr('value'),
				title : $('#wpcf-access-new-group-title').val(),
				add : $('#wpcf-access-new-group-action').val(),
				posts : posts
		};

		$( '.js-wpcf-access-process-button' )
                        .removeClass( 'button-primary' )
                        .addClass( 'button-secondary' )
                        .prop( 'disabled', true );

		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
               	if ( data != 'error') {
               		$('.wpcf-access-type-item').last().after(data);
					wpcfAccess.addSuggestedUser();
					wpcf_access_dialog_container.dialog('close');
               	} else {
               		$('.js-error-container').html('<p class="toolset-alert toolset-alert-error " style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.wpcf_group_exists + '</p>');
                    $('.js-otg-access-spinner').remove();
               		$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
               	}
            }
        });		
	};
    
    $(document).on('input', '#wpcf-access-new-group-title', function() {
		$('.js-error-container').html('');

		if ( $(this).val() !== '') {
			$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
		 } else {
			$( '.js-wpcf-access-process-button' )
                        .removeClass( 'button-primary' )
                        .addClass( 'button-secondary' )
                        .prop( 'disabled', true );
		}
	});
    
	$(document).on('click', '.js-wpcf-modify-group', function(e) {
        e.preventDefault();
        
        $access_dialog_open( 500 );

        $( '.js-wpcf-access-gui-close .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_cancel );
		$( '.js-wpcf-access-process-button .ui-button-text' ).html( wpcf_access_dialog_texts.wpcf_modify_group );
        $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html( wpcf_access_dialog_texts.wpcf_custom_access_group );
        
        wpcf_access_dialog_container.html( wpcfAccessSpinnerContent );
        
        var data = {
				action : 'wpcf_access_add_new_group_form',
				modify : $(this).data('group'),
				wpnonce : $('#wpcf-access-error-pages').attr('value')
		};

        $dialog_callback = $process_modify_access_group;
        $dialog_callback_params['id'] = $(this).data('group');
        $dialog_callback_params['divid'] = $(this).data('groupdiv');
        
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
                wpcf_access_dialog_container.html( data );
                if ( $('.js-wpcf-assigned-posts ul').is(':empty') ) {
					$('.js-no-posts-assigned').show();
				}
                $( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
                  
            }
        });  
	});
    
    $process_modify_access_group = function( params ) {
		var posts = [];
		if ( typeof $('input[name="assigned-posts"]') !== 'undefined' ) {
			$('input[name="assigned-posts[]"]').each(function() {
				posts.push( $(this).val() );
			});
		}
        
		id = params['id'];
		var data = {
				action : 'wpcf_process_modify_access_group',
				wpnonce : $('#wpcf-access-error-pages').attr('value'),
				title : $('#wpcf-access-new-group-title').val(),
				add : $('#wpcf-access-new-group-action').val(),
				id : id,
				posts : posts
		};
		$( '.js-wpcf-access-process-button' )
                        .removeClass( 'button-primary' )
                        .addClass( 'button-secondary' )
                        .prop( 'disabled', true );
		$.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
               	if ( data != 'error') {
                 	$('#js-box-'+params['divid'])
							.find('h4')
							.eq(0)
							.html( $('#wpcf-access-new-group-title').val() );
               		wpcf_access_dialog_container.dialog('close');
               	} else {

               		$('.js-error-container').html('<p class="toolset-alert toolset-alert-error js-toolset-alert" style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.wpcf_group_exists + '</p>');
               		$( '.js-wpcf-access-process-button' )
                        .addClass( 'button-primary' )
                        .removeClass( 'button-secondary' )
                        .prop( 'disabled', false );
               	}
            }
        });

		return false;
	};
    
	$(document).on('submit', '.wpcf-access-set_error_page', function() {
		return false;
	});


    // EXPAND/COLLAPSE (NOT USED)
    $('.wpcf-access-edit-type').click(function() {
        $(this).hide().parent().find('.wpcf-access-mode').slideToggle();
    });
    $('.wpcf-access-edit-type-done').click(function() {
        $(this).parents('.wpcf-access-mode').slideToggle().parent().find('.wpcf-access-edit-type').show();
    });

    $('select[name^="wpcf_access_bulk_set"]').change( function() {
        var value = $(this).val();
        if (value != '0') {

            $(this).parent().find('select').each(function() {
				$(this).val(value);
            });
        }
    });

    // ASSIGN LEVELS
    $('#wpcf_access_admin_form').on('click', '.wpcf-access-change-level', function() {
		$(this).hide();
        $(this)
				.closest('.wpcf-access-roles')
				.find('.wpcf-access-custom-roles-select-wrapper')
				.slideDown();
    });

    $('#wpcf_access_admin_form').on('click', '.wpcf-access-change-level-cancel', function(e) {
		e.preventDefault();

		$(this)
				.closest('.js-access-custom-roles-selection')
				.hide()
				.parent()
				.find('.wpcf-access-change-level')
				.show();
//        $(this).parent().hide()
//				.parent().find('.wpcf-access-change-level').fadeIn('fast');
    });

    $('#wpcf_access_admin_form').on('click', '.wpcf-access-change-level-apply', function(e) {
		e.preventDefault();

        if ( $(this).data('message') !== "undefined" && confirm($(this).data('message')) ) {
			wpcfAccess.ApplyLevels($(this));
//			return false;
		} else {
			return false;
		}
	});
	
	
	// SAVE SETTINGS SECTIONS
    $(document).on('click', '.wpcf-access-submit-section', function(e) {
        e.preventDefault();

		var object = $(this);
        var img = $(this).next();
		var ajaxSpinner = $(this).closest('.wpcf-access-buttons-wrap').find('.spinner');
		var section_form = $(this).parent().parent();
        $('#wpcf_access_admin_form')
				.find('.dep-message')
				.hide();
        img
				.css('visibility', 'visible')
				.animate({
			opacity: 1
        }, 0);
		ajaxSpinner.css('visibility', 'visible');

        $.ajax({
            url: ajaxurl,
            type: 'post',
            //            dataType: 'json',
            data: $(section_form).find('input').serialize()
            +'&_wpnonce='+$('#_wpnonce').val()
            +'&_wp_http_referer='+$('input[name=_wp_http_referer]').val()
            +'&action=wpcf_access_save_settings_section',
            cache: false,
            beforeSend: function() {
                object
						.parents('.wpcf-access-type-item')
						.css('background-color', "#FFFF9C");
            },
            success: function(data) {
                img.animate({opacity: 0}, 200);
                object.parents('.wpcf-access-type-item').css('background-color', "#F7F7F7");
				ajaxSpinner.css('visibility', 'hidden');	//spinner

                if (''!=data)
                {
                    $('#wpcf_access_notices')
							.empty()
							.html(data);
                }
            }
        });
        return false;
    });
	
    // SAVE SETTINGS
    $('#wpcf_access_admin_form').on('click', '.wpcf-access-submit', function(e) {
        e.preventDefault();

		var object = $(this);
        var img = $(this).next();
		var ajaxSpinner = $(this).parent().find('.spinner');

        $('#wpcf_access_admin_form')
				.find('.dep-message')
				.hide();
        
		ajaxSpinner.css({'visibility':'visible','display':'block'});

        $.ajax({
            url: ajaxurl,
            type: 'post',
            //            dataType: 'json',
            data: $('#wpcf_access_admin_form').serialize(),
            cache: false,
            success: function(data) {
               
				ajaxSpinner.css({'visibility':'hidden','display':'none'});	//spinner

                if (''!=data)
                {
                    $('#wpcf_access_notices')
							.empty()
							.html(data);
                }
            }
        });
        return false;
    });


    // NEW ROLE
    $('#wpcf-access-new-role .js-access-add-new-role').click( function(e) {
		e.preventDefault();

        $('#wpcf-access-new-role .toggle')
				.fadeIn('fast')
				.find('.input').val('').focus();
        $('#wpcf-access-new-role .ajax-response').html('');
    });

    $('#wpcf-access-new-role .cancel').click(function() {
        $('#wpcf-access-new-role .confirm').attr('disabled', 'disabled');
        $('#wpcf-access-new-role .toggle')
				.hide()
				.find('.input').val('');
        $('#wpcf-access-new-role .ajax-response').html('');
    });

    $('#wpcf-access-new-role .confirm').click(function() {
        if ( $(this).attr('disabled') ) {
            return false;
        }
        $(this).attr('disabled', 'disabled');
        $('#wpcf-access-new-role .img-waiting').show();
        $('#wpcf-access-new-role .ajax-response').html('');

		$.ajax({
            url: ajaxurl,
            type: 'post',
            dataType: 'json',
            data: 'action=wpcf_access_add_role&role='+$('#wpcf-access-new-role .input').val(),
            wpnonce : $('#wpcf-access-edit').attr('value'),
            cache: false,
            beforeSend: function() {},
            success: function(data) {
                $('#wpcf-access-new-role .img-waiting').hide();
                if (data.error == 'false') {

                    $('#wpcf-access-new-role .input').val('');
                    $('#wpcf-access-custom-roles-wrapper').html(data.output);
                } else {

                    $('#wpcf-access-new-role .ajax-response').html(data.output);
                }
                window.location = 'admin.php?page=wpcf-access#custom-roles';
                window.location.reload(true);
            }
        });
    });

    $('#wpcf-access-new-role .input').keyup(function() {
        $('#wpcf-access-new-role .ajax-response').html('');
        if ( $(this).val().length > 4 ) {

            $('#wpcf-access-new-role .confirm').removeAttr('disabled');
        } else {

            $('#wpcf-access-new-role .confirm').attr('disabled', 'disabled');
        }
    });

    // DELETE ROLE
    $(/*'#wpcf_access_admin_form'*/'body').on('click', '#wpcf-access-delete-role', function() {
        $(this).next().show();
    });

    

    $('.wpcf-access-reassign-role select').change( function() {
        $(this)
				.parents('.wpcf-access-reassign-role')
				.find('.confirm')
				.removeAttr('disabled');
    });

    // ADD DEPENDENCY MESSAGE
    $('.wpcf-access-type-item')
			.find('.wpcf-access-mode')
			.prepend('<div class="dep-message toolset-alert toolset-alert-info hidden"></div>');

    // Disable admin checkboxes
    $(':checkbox[value="administrator"]')
			.prop('disabled', true)
			.prop('readonly', true)
			.prop('checked', true);

	// Initialize buttons properties
	$.each( $('.js-wpcf-access-reset, .js-wpcf-access-submit, .js-wpcf-access-submit-section'), function() {

		// Store element state using .data()
		if ( $(this).hasClass('button-primary') ) {

			$(this).data('isPrimary', true);

			// Set button-secondary class for disabled items
			if ( $(this).prop('disabled') ) {
				$(this)
					.removeClass('button-primary')
					.addClass('button-secondary');
			}
		}
		if ( $(this).hasClass('button-secondary') ) {
			$(this).data('isSecondary', true);
		}

	});

	// Initialize  "same as parent" checkboxes properties
	// TOTO:
	$.each( $('.js-wpcf-follow-parent'), function() {
		var $manageByAccessCheckbox = $(this)
					.closest('.js-wpcf-access-type-item')
					.find('.js-wpcf-enable-access');
		
		if ( ! $manageByAccessCheckbox.is(':checked') ) {
			$(this)
				.prop('disabled', true)
				.prop('readonly', true);
		}
		
		
		var $container = $(this).closest('.js-wpcf-access-type-item');
		var checked = $(this).is(':checked');
		var $tableInputs = $container.find('table :checkbox, table input[type=text]');
	
		$tableInputs = $tableInputs.filter(function() { // All elements except 'administrator' role checkboxes
			return ( $(this).val() !== 'administrator' );
		});
		if ( checked) {
			wpcfAccess.DisableTableInputs($tableInputs, $container);
			$container.find('.js-wpcf-access-reset').prop('disabled', true);
		} 
		
		
	});

});

wpcfAccess.Reset = function (object) {
    $('#wpcf_access_admin_form')
			.find('.dep-message')
			.fadeOut('fast');

    $.ajax({
        url: object.attr('href')+'&button_id='+object.attr('id'),
        type: 'get',
        dataType: 'json',
        //            data: ,
        cache: false,
        beforeSend: function() {},
        success: function(data) {
            if (data !== null) {
                if (typeof data.output !== 'undefined' && typeof data.button_id !== 'undefined') {

                    var parent = $('#'+data.button_id).closest('.js-wpcf-access-type-item');

                    $.each(data.output, function(index, value) {
                        object = parent.find('input[id*="_permissions_'+index+'_'+value+'_role"]');
						object
							.trigger('click')
							.prop('checked', true);
                    });
                }
            }
        }
    });
    return false;
};

wpcfAccess.ApplyLevels = function (object) {
    $.ajax({
        url: ajaxurl,
        type: 'post',
        dataType: 'json',
        data: object.closest('.js-access-custom-roles-selection').find('.wpcf-access-custom-roles-select').serialize() +
        '&_wpnonce=' + $('#wpcf-access-error-pages').attr('value') + '&action=wpcf_access_ajax_set_level',
        cache: false,
        beforeSend: function() {
            $('#wpcf-access-custom-roles-table-wrapper').css('opacity', 0.5);
        },
        success: function(data) {
            if (data != null) {
                if (typeof data.output != 'undefined') {
                    //                    $('#wpcf-access-custom-roles-wrapper').css('opacity', 1).replaceWith(data.output);
                    window.location = 'admin.php?page=wpcf-access#custom-roles';
                    window.location.reload(true);
                }
            }
        }
    });
    return false;
};


wpcfAccess.enableElement = function( $obj ) {
	if ( $obj.data('isPrimary') ) {
		$obj.addClass('button-primary');
	}
	if ( $obj.data('isSecondary') ) {
		$obj.addClass('button-secondary');
	}
	$obj
		.prop('disabled', false)
		.prop('readonly', false);
};

wpcfAccess.disableElement = function( $obj ) {
	if ($obj.data('isPrimary')) {
		$obj
			.removeClass('button-primary')
			.addClass('button-secondary');
	}
	$obj.prop('disabled', true);
};

wpcfAccess.EnableTableInputs = function( $inputs, $container ) {
	$container.addClass('is-enabled');
	$.each( $inputs, function() {
		wpcfAccess.enableElement( $(this) );
	});

};

wpcfAccess.DisableTableInputs = function( $inputs, $container ) {
	$container.removeClass('is-enabled');
	$.each( $inputs, function() {
		wpcfAccess.disableElement($(this));
	});
};



// Enable/Disable inputs
$(document).on('change', '.js-wpcf-enable-access, .js-wpcf-follow-parent', function() {
	var $container = $(this).closest('.js-wpcf-access-type-item');
	var checked = $(this).is(':checked');
	var $tableInputs = $container.find('table :checkbox, table input[type=text]');
	var $buttons = $container.find('.js-wpcf-access-submit, .js-wpcf-access-reset, .js-wpcf-access-submit-section');

	$tableInputs = $tableInputs.filter(function() { // All elements except 'administrator' role checkboxes
		return ( $(this).val() !== 'administrator' );
	});

	if ( $(this).is('.js-wpcf-enable-access') ) {
		if (checked) {

			wpcfAccess.EnableTableInputs($tableInputs, $container);
			wpcfAccess.EnableTableInputs($buttons, $container);
			wpcfAccess.enableElement( $container.find('.js-wpcf-follow-parent') );
			$container.find('.js-wpcf-access-reset').prop('disabled', false);
		} else {
			$container.find('.js-wpcf-access-reset').prop('disabled', true);
			wpcfAccess.DisableTableInputs($tableInputs, $container);
			wpcfAccess.disableElement( $container.find('.js-wpcf-follow-parent') );

		}
	}
	else if ( $(this).is('.js-wpcf-follow-parent') ) {
		if (checked) {
			$container.find('.js-wpcf-access-reset').prop('disabled', true);
			wpcfAccess.DisableTableInputs($tableInputs, $container);
		} else {
			$container.find('.js-wpcf-access-reset').prop('disabled', false);
			wpcfAccess.EnableTableInputs($tableInputs, $container);
		}
	}
});

// Set hidden input val and show/hide messages
$(document).on('change', '.js-wpcf-enable-access', function() {
	var $container = $(this).closest('.js-wpcf-access-type-item');
	var checked = $(this).is(':checked');
	var $hiddenInput = $container.find('.js-wpcf-enable-set');
	var $message = $container.find('.js-warning-fallback');
	var $depMessage = $container.find('.dep-message');

	if (checked) {

		$hiddenInput.val( $(this).val() );
		$message.hide();
	} else {

		$hiddenInput.val('not_managed');
		$message.fadeIn('fast');
		$depMessage.hide();
	}
});


// Auto check/uncheck checkboxes
wpcfAccess.AutoThick = function (object, cap, name) {
    var thick = new Array();
    var thickOff = new Array();
    var active = object.is(':checked');
    var role = object.val();
    var cap_active = 'wpcf_access_dep_true_'+cap;
    var cap_inactive = 'wpcf_access_dep_false_'+cap;
    var message = new Array();

    if (active) {
        if (typeof window[cap_active] != 'undefined') {
            thick = thick.concat(window[cap_active]);
        }
    } else {
        if (typeof window[cap_inactive] != 'undefined') {
            thickOff = thickOff.concat(window[cap_inactive]);
        }
    }

    // FIND DEPENDABLES
    //
    // Check ONs
    $.each(thick, function(index, value) {
        object.parents('table').find(':checkbox').each( function() {

            if ( $(this).attr('id') != object.attr('id') ) {

                if ( $(this).val() == role && $(this).hasClass('wpcf-access-'+value) ) {
                    // Mark for message
                    if ( $(this).is(':checked') == false ) {
                        message.push( $(this).data('wpcfaccesscap') );
                    }
                    // Set element form name
                    $(this).attr('checked', 'checked').attr('name', $(this).data('wpcfaccessname'));
                    wpcfAccess.ThickTd($(this), 'prev', true);
                }
            }
        });
    });

    // Check OFFs
    $.each(thickOff, function(index, value) {
        object.parents('table').find(':checkbox').each( function() {

            if ( $(this).attr('id') != object.attr('id') ) {

                if ( $(this).val() == role && $(this).hasClass('wpcf-access-'+value) ) {

					// Mark for message
                    if ( $(this).is(':checked') ) {
                        message.push( $(this).data('wpcfaccesscap') );
                    }
                    $(this).removeAttr('checked').attr('name', 'dummy');

					// Set element form name
//                    var prevSet = $(this).parent().prev().find(':checkbox');
                    var prevSet = $(this).closest('td').prev().find(':checkbox');

					if (prevSet.is(':checked')) {
						prevSet.attr('checked', 'checked').attr('name', prevSet.data('wpcfaccessname'));
                    }
                    wpcfAccess.ThickTd( $(this), 'next', false );
                }
            }
        });
    });

    // Thick all checkboxes
    wpcfAccess.ThickTd(object, 'next', false);
    wpcfAccess.ThickTd(object, 'prev', true);

    // SET NAME
    //
    // Find previous if switched off
    if (object.is(':checked')) {
        object.attr('name', name);

    } else {
        object.attr('name', 'dummy');
        object
				.closest('td')
				.prev()
				.find(':checkbox')
				.attr('checked', 'checked')
				.attr('name', name);
    }
    // Set true if admnistrator
    if (object.val() == 'administrator') {
        object
				.attr('name', name)
				.attr('checked', 'checked');
    }

    // Alert
    wpcfAccess.DependencyMessageShow(object, cap, message, active);
}

wpcfAccess.ThickTd = function (object, direction, checked) {
    if (direction == 'next') {
        var cbs = object
						.closest('td')
						.nextAll('td')
						.find(':checkbox');
    } else {
        var cbs = object
						.closest('td')
						.prevAll('td')
						.find(':checkbox');
    }
    if (checked) {
        cbs.each( function() {
            $(this)
					.prop('checked', true)
					.prop('name', 'dummy');
//			$(this).parent().find('.wpcf-add-error-page,.error-page-name-wrap').hide();
        });
    } else {
        cbs.each( function() {
            $(this)
					.prop('checked', false)
					.prop('name', 'dummy');
//            $(this).parent().find('.wpcf-add-error-page,.error-page-name-wrap').attr('style','');
        });
    }
};

wpcfAccess.DependencyMessageShow = function (object, cap, caps, active) {
    var update_message = wpcfAccess.DependencyMessage(cap, caps, active);
	var update = object.parents('.wpcf-access-type-item').find('.dep-message');

    update.hide().html('');
    if (update_message != false) {
        update.html(update_message).show();
    }
}

wpcfAccess.DependencyMessage = function (cap, caps, active) {
    var active_pattern_singular = window['wpcf_access_dep_active_messages_pattern_singular'];
    var active_pattern_plural = window['wpcf_access_dep_active_messages_pattern_plural'];
    var inactive_pattern_singular = window['wpcf_access_dep_inactive_messages_pattern_singular'];
    var inactive_pattern_plural = window['wpcf_access_dep_inactive_messages_pattern_singular'];
    /*var no_edit_comments = window['wpcf_access_edit_comments_inactive'];*/
    var caps_titles = new Array();
    var update_message = false;

    $.each(caps, function(index, value) {
        if (active) {

            var key = window['wpcf_access_dep_true_'+cap].indexOf(value);
            caps_titles.push(window['wpcf_access_dep_true_'+cap+'_message'][key]);
        } else {

            var key = window['wpcf_access_dep_false_'+cap].indexOf(value);
            caps_titles.push(window['wpcf_access_dep_false_'+cap+'_message'][key]);
        }
    });

    if (caps.length > 0) {
        if (active) {
            if (caps.length < 2) {

                var update_message = active_pattern_singular.replace('%cap', window['wpcf_access_dep_'+cap+'_title']);
            } else {

                var update_message = active_pattern_plural.replace('%cap', window['wpcf_access_dep_'+cap+'_title']);
            }
        } else {
            if (caps.length < 2) {

                var update_message = inactive_pattern_singular.replace('%cap', window['wpcf_access_dep_'+cap+'_title']);
            } else {

                var update_message = inactive_pattern_plural.replace('%cap', window['wpcf_access_dep_'+cap+'_title']);
            }
        }
        update_message = update_message.replace('%dcaps', caps_titles.join('\', \''));
    }
    return update_message;
}

// export it
window.wpcfAccess=window.wpcfAccess || {};
$.extend(window.wpcfAccess, wpcfAccess);
})(window, jQuery);