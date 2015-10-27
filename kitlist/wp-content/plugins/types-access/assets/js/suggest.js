var wpcfAccess = wpcfAccess || {};

(function( window, $, undefined ) {

$.typesSuggest = function($input, $container, $dropdown, $img, ajax_action, callback_select) {
    var action = ajax_action,
        callback = callback_select,
        selected_values = {},
        prevLength = $input.val().length,
		xhr = null;

	attach_listener();

    function attach_listener() {
        var searchTimer, q;

        $input.keyup( function(e) {
            if (e.preventDefault)
                e.preventDefault();
            if (e.stopPropagation)
                e.stopPropagation();

            e.cancelBubble = true;
            e.returnValue = false;

            q = $input.val();

            if ( 13 == e.keyCode ) {
                update(q);
                return false;
            }
            if ( e.keyCode == 40 && $dropdown.length) {
                $dropdown.focus();

            } else if ( e.keyCode >= 32 && e.keyCode <=127 || e.keyCode == 8) {
                if (prevLength != q.length) {
                    prevLength = q.length;
                    if ( searchTimer ) clearTimeout(searchTimer);

                    searchTimer = setTimeout(function() {
                        update($input.val());
//                        prevLength=$input.val().length;
                    }, 500);
                }
            }
        }).attr('autocomplete','off');

        select_listener();

        $input.focus( function() {
            toggle_dropdown(true);
        }).blur( function() {
            toggle_dropdown(false);
        });
    }

    function toggle_dropdown(toggle) {
        if (toggle) {

            $dropdown.css('visibility', 'visible');
            $container.find('.toggle').show();
        } else {

            setTimeout( function() {
                if ( !$dropdown.is(':focus') ) {
                    $dropdown.css('visibility', 'hidden');
                    $container.find('.toggle').hide();
                }
            }, 100);
        }
    }

    function update(q) {
        var params, minSearchLength = 2;

        if ( q.length < minSearchLength ) {
            return;
        }

        params = {
            'action': action,
            'q': q,
            'wpnonce' : jQuery('#wpcf-access-error-pages').attr('value')
        };

        // abort previous request
        if (xhr) xhr.abort();

        $img.show();

        xhr = $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: params,
            dataType: 'json',           
            success: function(response) {
                xhr = null;
                $img.hide();
                $dropdown.empty();
                var count = 0;
                var options = '';
                for (var key in response) {
                    count++;
                    options = options+'<option value="'+key+'">'+response[key]+'</option>';
                }
                if ( count > 0 ) {
                    var resize = count;
                    if ( count > 5 ) {
                        resize = 5;
                    }
                    $dropdown.append(options).attr('size', resize).css('visibility', 'visible').find('option:first-child').attr('selected', 'selected');
                } else {
                    $dropdown.css('visibility', 'hidden');
                }
            }
        });
    }

    function select_listener() {

		$dropdown.on('change', function() {
            select( $(this).find(':selected') );
        });

        $dropdown.on('keydown', function(e) {
            if (e.keyCode == 13) {
                select( $(this).find(':selected') );
                e.preventDefault();
            }
        });
        return;
    }

    function select(object) {
        callback(object.val(), object.text(), $container);
    }
};

$.fn.typesSuggest = function(ajax_action, callback_select) {

	this.each( function() {
        var $this = $(this), $dropdown = $this.find('.dropdown'), $img = $this.find('.img-waiting');

        $this.find('.input').each(function() {
            new $.typesSuggest( $(this), $this, $dropdown, $img, ajax_action, callback_select);
        });
    });
	//            return this;
};

wpcfAccess.addSuggestedUser = function() {
	    typesSuggestMarkAsSuggested('.wpcf-access-user-list input[type="hidden"]', 'td');

		$('.types-suggest-user').typesSuggest('wpcf_access_suggest_user', wpcfAccess.Suggest);

		$('.types-suggest').find('.confirm').click(function(e) {
			e.preventDefault();

			var container = $(this).parents('.types-suggest');
			var name = container.parents('td').find('.wpcf-access-name-holder').val() + '[users][]';
			var selected = container.find('.dropdown :selected');
			var value = selected.val();
			var text = selected.text();

			container.find('.dropdown').css('visibility', 'hidden');
			container.find('.toggle').hide();

			if (typeof value == 'undefined') {
				return false;
			}
			if (typesSuggestIsSelected(value, container.attr('id'))) {
				return false;
			}
			var html = '<div class="wpcf-access-remove-user-wrapper"><a href="javascript:void(0);" class="wpcf-access-remove-user"></a> <input type="hidden" name="' + name + '" value="' + value + '" />' + text + '</div>';

			container.parent().find('.wpcf-access-user-list').append(html);
			wpcfAccess.DependencyAddUser($(this), html);
		});

		$('.types-suggest').find('.cancel').click(function(e) {
			e.preventDefault();

			var container = $(this).parents('.types-suggest');
			container.find('.dropdown').css('visibility', 'hidden');
			container.find('.toggle').hide();
		});

		$('#wpcf_access_admin_form').on('click', '.wpcf-access-remove-user', function() {
			wpcfAccess.DependencyRemoveUser($(this));
			$(this).parent().fadeOut(function() {
				$(this).remove();
			});
		});
};

$(document).ready(function() {
	wpcfAccess.addSuggestedUser();
});

wpcfAccess.Suggest = function (value, text, container) {}

function typesSuggestMarkAsSuggested(selector, parent) {
    $(selector).each(function() {
        var $el = $(this),
		id = $el.closest(parent).find('.types-suggest').attr('id');
        typesSuggestIsSelected( $el.val(), id );
    });
}

function typesSuggestIsSelected(value, id) {
    var store = value + id;
    if (typeof typesSuggestIsSelected.selected == 'undefined') {
        typesSuggestIsSelected.selected = new Array;
    }
    if ($.inArray(store, typesSuggestIsSelected.selected) == -1) {
        typesSuggestIsSelected.selected.push(store);
        return false;
    }
    return true;
}

function typesSuggestUnMark(value, id) {
    var store = value + id;

    if (typeof typesSuggestIsSelected.selected == 'undefined') {
        typesSuggestIsSelected.selected = new Array;
    }
    var index = $.inArray(store, typesSuggestIsSelected.selected);

    if (index != -1) {
        typesSuggestIsSelected.selected.splice(index);
    }
}


wpcfAccess.DependencyAddUser = function (object, html) {
    var table = object.parents('table');
    var cap = object.parents('td').find('.wpcf-access-name-holder').data('wpcfaccesscap');
    var caps = new Array();

    if (typeof window['wpcf_access_dep_true_'+cap] != 'undefined') {
        $.each(window['wpcf_access_dep_true_'+cap], function(index, value) {
            table.find('.wpcf-access-name-holder[data-wpcfaccesscap="' + value + '"]').each(function() {

                var td = $(this).parents('td');
                var name_holder = td.find('.wpcf-access-name-holder');
                var cap_new = name_holder.data('wpcfaccesscap');
                var user_list = td.find('.wpcf-access-user-list');
                var user_id = $(html).find('input').attr('value');
                var insert_html = $(html);
                var name = name_holder.val();
                var duplicate = user_list.find('input[value="'+user_id+'"]');

				insert_html.find('input').attr('name', name + '[users][]');

				if (duplicate.length < 1) {
                    caps.push(cap_new);
                    user_list.append(insert_html);
                }
            });
        });
    }
    wpcfAccess.DependencyMessageShow(object, cap, caps, true);
};

wpcfAccess.DependencyRemoveUser = function (object) {
    var table = object.parents('table');
    var user_id = object.parent().find('input').val();
    var td = object.parents('td');
    var name_holder = td.find('.wpcf-access-name-holder');
    var cap = name_holder.data('wpcfaccesscap');
    var caps = new Array();
    var container = td.find('.types-suggest');

    if ( typeof window['wpcf_access_dep_false_' + cap] !== 'undefined' ) {

		$.each( window['wpcf_access_dep_false_' + cap], function(index, value) {
            table.find('.wpcf-access-name-holder[data-wpcfaccesscap="'+value+'"]').each(function() {
                var td_new = $(this).parents('td');
                var found = td_new.find('.wpcf-access-remove-user-wrapper').find('input[value="'+user_id+'"]');

				if (found.length > 0) {
                    caps.push(value);
                    found.parent().remove();
                }
                typesSuggestUnMark(user_id, container.attr('id'));

            });
        });
    }
    wpcfAccess.DependencyMessageShow(object, cap, caps, false);
};
})( window, jQuery );
