var _original_cred_autogenerate_username_scaffold;
var _original_cred_autogenerate_nickname_scaffold;
var _original_cred_autogenerate_password_scaffold;

(function (window, $, settings, utils, gui, mvc, undefined) {
    // uses WordPress 3.3+ features of including jquery-ui effects

    // oonstants
    var KEYCODE_ENTER = 13, KEYCODE_ESC = 27, PREFIX = '_cred_cred_prefix_',
            PAD = '\t', NL = '\r\n';

    // private properties
    var form_id = 0,
            settingsPage = null,
            form_name = '',
            field_data = null,
            CodeMirrorEditors = {},
            // used for MV framework, bindings and interaction
            _credModel, credView;

    var cred_media_buttons,
            cred_popup_boxes,
            checkButtonTimer
            ;


    // auxilliary functions
    var aux = {
        update_autogeneration_fields: function () {
            if ($('#cred_autogenerate_username_scaffold').length) {
                var ag_un_is_checked = $('#cred_autogenerate_username_scaffold');
                ag_un_is_checked = ag_un_is_checked[0].checked;
            }
            if ($('#cred_autogenerate_nickname_scaffold').length) {
                var ag_nn_is_checked = $('#cred_autogenerate_nickname_scaffold');
                ag_nn_is_checked = ag_nn_is_checked[0].checked;
            }
            if ($('#cred_autogenerate_password_scaffold').length) {
                var ag_p_is_checked = $('#cred_autogenerate_password_scaffold');
                ag_p_is_checked = ag_p_is_checked[0].checked;
            }

            _original_cred_autogenerate_username_scaffold = ag_un_is_checked != undefined ? ag_un_is_checked : cred_settings.autogenerate_username_scaffold;
            _original_cred_autogenerate_nickname_scaffold = ag_nn_is_checked != undefined ? ag_nn_is_checked : cred_settings.autogenerate_nickname_scaffold;
            _original_cred_autogenerate_password_scaffold = ag_p_is_checked != undefined ? ag_p_is_checked : cred_settings.autogenerate_password_scaffold;
        },
        toggleHighlight: function ()
        {
            var highlight = '0', active = 'switch-html', inactive = 'switch-tmce',
                    $this = $(this), $cont = $this.closest('.wp-editor-wrap'),
                    qts_cont = $cont.find('.quicktags-toolbar'),
                    qts = $cont.find('.ed_button:not(.qt-fullscreen)'),
                    textb = $cont.find('.switch-text'), syntaxb = $cont.find('.switch-syntax'), switches = $cont.find('.wp-switch-editor')
                    ;

            $cont.find('.qt-fullscreen').hide();

            if ($this.hasClass('switch-text'))
            {
                qts_cont.show();
                qts.show();
                switches.removeClass(active).addClass(inactive);
                textb.removeClass(inactive).addClass(active);
                aux.toggleCodeMirror(false);
                highlight = '0';
            }
            else
            {
                qts_cont.hide();
                qts.hide();
                switches.removeClass(active).addClass(inactive);
                syntaxb.removeClass(inactive).addClass(active);
                aux.toggleCodeMirror(true);
                highlight = '1';
            }
            // set setting on DB also
            $.ajax({
                url: self.route('/Settings/toggleHighlight'),
                timeout: 10000,
                type: 'POST',
                data: {'cred_highlight': highlight},
                dataType: 'html',
                success: function (result) {
                },
                error: function () {
                }
            });
        },
        makeResizable: function ($textarea)
        {
            var interv, dragging = false, id = $textarea.attr('id');
            // simulate codemirror resize based on wp handler resize
            var resizeCodemirror = function ()
            {
                if (CodeMirrorEditors[id])
                {
                    CodeMirrorEditors[id].setSize(/*null*/$('#wp-content-wrap').width() - 4, $textarea.height());
                }
            };

            // late init to have the element
            utils.waitUntilElement('#content-resize-handle', function () {
                $('#content-resize-handle').on('mousedown', function () {
                    if (!dragging)
                    {
                        dragging = true;
                        interv = setInterval(resizeCodemirror, 100);
                    }
                });
                $(document).on('mouseup', function () {
                    if (dragging)
                    {
                        clearInterval(interv);
                        dragging = false;
                    }
                });

                $(window).resize(function () {
                    resizeCodemirror();
                });
            });
        },
        makeAreaResizable: function ($textarea)
        {
            // resizable textarea#content
            (function () {
                var isDragging = false, id = $textarea.attr('id');
                // simulate codemirror resize based on wp handler resize
                var resizeCodemirror = function ()
                {
                    if (CodeMirrorEditors[id])
                    {
                        CodeMirrorEditors[id].setSize(/*null*/$('#wp-content-wrap').width() - 4, $textarea.height());
                    }
                };

                var offset = null, el;
                // No point for touch devices
                if (!$textarea.length || 'ontouchstart' in window)
                    return;

                function dragging(e) {
                    $textarea.height(Math.max(50, offset + e.pageY) + 'px');
                    resizeCodemirror();
                    return false;
                }

                function endDrag(e) {
                    var height;

                    $textarea.focus();
                    $(document).unbind('mousemove', dragging).unbind('mouseup', endDrag);

                    height = parseInt($textarea.css('height'), 10);

                    // sanity check
                    if (height && height > 50 && height < 5000)
                        utils.setUserSetting('cred_settings', id + '_size', height);
                }

                $textarea.css('resize', 'none');
                el = $textarea.closest('.cred-editor-wrap').find('.cred-content-resize-handle');
                el.on('mousedown', function (e) {
                    offset = $textarea.height() - e.pageY;
                    $textarea.blur();
                    $(document).mousemove(dragging).mouseup(endDrag);
                    return false;
                });
            })();
        },
        enableExtraCodeMirror: function ()
        {
            // if codemirror activated, enable syntax highlight
            if (window.CodeMirror)
            {
                var $css_ed = $("#cred-extra-css-editor"), $js_ed = $("#cred-extra-js-editor"), height;

                utils.swapEl($css_ed, function () {
                    CodeMirrorEditors['cred-extra-css-editor'] = CodeMirror.fromTextArea($css_ed[0], {
                        mode: "css",
                        tabMode: "indent",
                        lineWrapping: true,
                        lineNumbers: true
                    });
                    // needed for scrolling
                    height = Math.min(5000, Math.max(50, utils.getUserSetting('cred_settings', 'cred-extra-css-editor_size', 300)));
                    $css_ed.css('resize', 'none').height(height + 'px');
                    CodeMirrorEditors['cred-extra-css-editor'].setSize(null, height);
                    aux.makeAreaResizable($css_ed);
                });
                $css_ed.hide();

                utils.swapEl($js_ed, function () {
                    CodeMirrorEditors['cred-extra-js-editor'] = CodeMirror.fromTextArea($js_ed[0], {
                        mode: "javascript",
                        tabMode: "indent",
                        lineWrapping: true,
                        lineNumbers: true
                    });
                    // needed for scrolling
                    height = Math.min(5000, Math.max(50, utils.getUserSetting('cred_settings', 'cred-extra-js-editor_size', 300)));
                    $js_ed.css('resize', 'none').height(height + 'px');
                    CodeMirrorEditors['cred-extra-js-editor'].setSize(null, height);
                    aux.makeAreaResizable($js_ed);
                });
                $js_ed.hide();

            }
        },
        toggleCodeMirror: function (on)
        {
            var last;
            // if codemirror activated, enable syntax highlight
            if (window.CodeMirror)
            {
                if (!on && CodeMirrorEditors['content'])
                {
                    CodeMirrorEditors['content'].toTextArea();
                    CodeMirrorEditors['content'] = false;
                    // WP 4.3 compat: make sure the #content is shown
                    $('#content').height('300px').css('padding', '10px');
                    return !on;
                }
                else if (on && !CodeMirrorEditors['content'])
                {
                    CodeMirror.defineMode("myshortcodes", codemirror_shortcodes_overlay);

                    var $_metabox = $('#content').closest('.postbox'),
                            _metabox_closed = false,
                            _metabox_display = false;

                    if ($_metabox.hasClass('closed') || 'none' == $_metabox.css('display'))
                    {
                        _metabox_closed = true;
                        $_metabox.removeClass('closed');
                    }
                    if ('none' == $_metabox.css('display'))
                    {
                        _metabox_display = 'none';
                        $_metabox.css('display', 'block');
                    }

                    var old_style_code_mirror = undefined;
                    var old_style_content = undefined;
                    var old_style_gutters = undefined;
                    var _is_small = true;
                    CodeMirrorEditors['content'] = CodeMirror.fromTextArea(document.getElementById("content"), {
                        mode: 'myshortcodes', //"text/html",
                        tabMode: "indent",
                        lineWrapping: true,
                        lineNumbers: true,
                        // enable WP word count, using same function
                        onKeyEvent: function (ed, e) {
                            var k = e.keyCode || e.charCode;

                            if (k == last)
                                return;

                            if (13 == k || 8 == last || 46 == last)
                                jQuery(document).triggerHandler('wpcountwords', [ed.getValue()]);

                            last = k;

                            //_set_area_large();
                        }
                    });

                    var _buttons_fixed = false;

                    jQuery(window).scroll(function () {

                        // determine if the buttons should be fixed in position.

                        var _min = parseInt(jQuery("#wp-content-wrap").offset().top);
                        var _bottom = _min + jQuery("#wp-content-wrap").height() - jQuery("#post-status-info").height();
                        var _top = parseInt(jQuery("#wp-content-editor-tools").offset().top);
                        var _scroll_y = jQuery(window).scrollTop();

                        if (!_buttons_fixed && _scroll_y > _top && _scroll_y < _bottom) {

                            // The buttons should be fixed in position.

                            var buttons_div = jQuery("#wp-content-media-buttons");
                            var admin_bar_size = 0;
                            if (jQuery("#wpadminbar").is(':visible')) {
                                admin_bar_size = jQuery("#wpadminbar").height();
                            }

                            buttons_div.attr('style', 'width:100%; position:fixed; background-color:#eee; display:block;');


                            var _left_offset = buttons_div.offset().left;
                            var _parent_left = jQuery("#credformcontentdiv").offset().left;
                            buttons_div.css({
                                'left': _parent_left - 9,
                                'padding-left': _left_offset - _parent_left,
                                'padding-top': '4px',
                                'top': admin_bar_size
                            });
                            _buttons_fixed = true;
                        }

                        if (_buttons_fixed && (_scroll_y < _top || _scroll_y > _bottom)) {

                            // The buttons should scroll as normal.

                            jQuery("#wp-content-media-buttons").removeAttr('style');
                            _buttons_fixed = false;
                        }

                    });

//                    jQuery("#cred-scaffold-button-button").on("click", function (e) {
//                        e.preventDefault();
//                        _set_area_large();
//                    });
//                    var _last_click;
//                    jQuery(document).mousedown(function (e) {
//                        _last_click = $(e.target);                        
//                        console.log(jQuery(_last_click).attr("id") + " - " + jQuery(_last_click).attr("class"));
//                        var closest = jQuery(_last_click).closest('div').parent().attr("class");                        
//                        if ((closest == 'CodeMirror-code' || jQuery(_last_click).attr("class") == 'CodeMirror-scroll')) {
//                            console.log(jQuery(_last_click).parent().parent().attr("class") + " _is_small=" + _is_small);
//                            _set_area_large();
//                        }
//                    });
//                    jQuery(document).mouseup(function (e) {
//                        _last_click = null;
//                    });
//                    function _set_area_small() {
//                        console.log("_set_area_small _is_small=" + _is_small);
//                        if (_is_small == false) {
//                            jQuery(window).scroll(function () {
//                            });
//                            CodeMirrorEditors['content'].on("blur", function () {
//                            });
//                            jQuery(".CodeMirror-wrap").css('height', old_style_code_mirror);
//                            jQuery(".CodeMirror-vscrollbar").show();
//                            jQuery("#content").css('height', old_style_content);
//                            jQuery(".CodeMirror-gutters").css('height', old_style_gutters);
//                            jQuery("#wp-content-media-buttons").removeAttr("style");
//                            _is_small = true;
//                        }
//                    }
//                    function _set_area_large() {
//                        console.log("_set_area_large _is_small=" + _is_small);
//                        if (_is_small == true) {
//                            var _min = parseInt(jQuery("#wp-content-wrap").offset().top);
//                            jQuery(window).scrollTop(_min);
//                            old_style_code_mirror = jQuery(".CodeMirror-wrap").css('height');
//                            old_style_content = jQuery("#content").css('height');
//                            old_style_gutters = jQuery(".CodeMirror-gutters").css('height');
//                            jQuery(".CodeMirror-wrap").css('height', '3000px');
//                            jQuery(".CodeMirror-vscrollbar").hide();
//                            jQuery("#content").css('height', '3000px');
//                            jQuery(".CodeMirror-gutters").css('height', '3000px');
//                            jQuery("#wp-content-media-buttons").attr('style', 'width:100%; position:fixed; top:35px; background-color:#eee; display:block');
//                            CodeMirrorEditors['content'].on("blur", function (target) {
//                                if (jQuery(_last_click).attr('id') && jQuery(_last_click).attr('id') == 'cred-scaffold-button-button') {
//                                    _last_click = null;
//                                    return;
//                                }
//                                console.log(jQuery(_last_click).attr('id'));
//                                _set_area_small();
//                            });
//                            jQuery(window).scroll(function () {
//                                var _min = parseInt(jQuery("#wp-content-wrap").offset().top);
//                                var _height = parseInt(jQuery(".CodeMirror-lines").height());
//                                if (jQuery(window).scrollTop() <= parseInt(_min - 300) || 
//                                        jQuery(window).scrollTop() >= parseInt(_min + _height + 500)) {
//                                    _set_area_small();
//                                }
//
//                            });
//                            _is_small = false;
//                        }
//                    }
                    // needed for scrolling
                    var height = Math.min(5000, Math.max(50, getUserSetting('ed_size')));
                    if (getUserSetting('ed_size') === '') {
                        height = 300;
                    }
                    $('#content').css('resize', 'none').height(height + 'px');
                    CodeMirrorEditors['content'].setSize($('#wp-content-wrap').width() - 2, height < 250 ? 250 : height);

                    if ('none' == _metabox_display)
                    {
                        $_metabox.css('display', 'none');
                    }
                    if (_metabox_closed)
                    {
                        $_metabox.addClass('closed');
                    }

                    // WP 4.3 compat: make sure the #content is hidden
                    $('#content').height('-20px').css('padding', '0px');
                    return on;
                }
            }
            return false;
        },
        genScaffold: function ()
        {
            if (cred_settings._current_page == 'cred-user-form') {
                return aux.genUserScaffold();
            }
            var resp = field_data;

            if (!resp || !resp.post_fields)
            {
                gui.Popups.alert({message: settings.locale.form_types_not_set, class: 'cred-dialog'});
                return false;
            }

            var includeWPML = false;
            if ($('#cred_include_wpml_scaffold').is(':checked'))
                includeWPML = true;

            var form_name_1 = $('#title').val();
            if ($.trim(form_name_1) == '')
            {
                gui.Popups.alert({message: settings.locale.set_form_title, class: 'cred-dialog'});
                return false;
            }
            var form_id_1 = $('#post_ID').val();

            var cont = $('#cred-scaffold-area');
            var groups_out = '';
            var groups = {};
            var nlcnt = 0;
            for (var f in resp.groups)
            {
                if (resp.groups.hasOwnProperty(f))
                {
                    nlcnt++;
                    var fields = resp.groups[f];
                    groups[f] = fields;
                    fields = fields.split(',');
                    groups_out += aux.groupOutput(f, fields, resp.groups_conditions, resp.custom_fields, form_id_1, form_name_1, includeWPML, PAD) + NL;
                }
            }

            var taxs_out = '';
            if (parseInt(resp.taxonomies_count, 10) > 0)
            {
                for (var f in resp.taxonomies)
                {
                    if (resp.taxonomies.hasOwnProperty(f))
                    {
                        taxs_out += aux.taxOutput(resp.taxonomies[f], form_id_1, form_name_1, includeWPML, '') + NL;
                    }
                }
            }
            var parents_out = '';
            if (parseInt(resp.parents_count, 10) > 0)
            {
                for (var f in resp.parents)
                {
                    if (resp.parents.hasOwnProperty(f))
                    {
                        parents_out += aux.fieldOutput(resp.parents[f], form_id_1, form_name_1, includeWPML, '',
                                // extra params
                                'date', 'desc', 0,
                                false, 'No Parent', '-- Select ' + resp.parents[f].data.post_type + ' --', resp.parents[f].data.post_type + ' must be selected') + NL;
                    }
                }
            }
            // add fields
            var out = '';
            if ('minimal' == _credModel.get('[form][theme]')/*$('input[name="_cred[form][theme]"]:checked').val()*/) // bypass script and other styles added to form, minimal
                out += "[credform class='cred-form cred-keep-original']" + NL + NL;
            else
                out += "[credform class='cred-form']" + NL + NL;
            out += PAD + aux.shortcode(resp.form_fields['form_messages']) + NL + NL;
            out += aux.fieldOutput(resp.post_fields['post_title'], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;
            if (resp.post_fields['post_content'].supports)
            {
                out += aux.fieldOutput(resp.post_fields['post_content'], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;
            }
            if (resp.post_fields['post_excerpt'].supports)
            {
                out += aux.fieldOutput(resp.post_fields['post_excerpt'], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;
            }
            if (resp.extra_fields['_featured_image'].supports)
                out += aux.fieldOutput(resp.extra_fields['_featured_image'], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;
            /*out+=aux.groupOutputContent('all', resp._post_data.singular_name+' Properties',
             groups_out+taxs_out+parents_out,
             PAD)+NL+NL;*/
            out += groups_out;
            if (parseInt(resp.taxonomies_count, 10) > 0)
                out += aux.groupOutputContent('taxonomies', 'Taxonomies', taxs_out, PAD) + NL + NL;
            if (parseInt(resp.parents_count, 10) > 0)
                out += aux.groupOutputContent('parents', 'Parents', parents_out, PAD) + NL + NL;
            if ($('#cred_include_captcha_scaffold').is(':checked')) {
                if (resp.extra_fields['recaptcha']['private_key'] != '' && resp.extra_fields['recaptcha']['public_key'] != '')
                    out += PAD + '<div class="cred-field cred-field-recaptcha">' + aux.shortcode(resp.extra_fields['recaptcha']) + '</div>' + NL + NL;
                else {
                    $('#cred_include_captcha_scaffold').attr("checked", false);
                    alert('Captcha keys are empty !');
                }
            }
            out += PAD + aux.shortcode(resp.form_fields['form_submit']) + NL + NL;
            out += '[/credform]' + NL;
            cont.val(out);
            return true;
        },
        reloadUserFields: function (role) {
            var ag_username_is_checked = $('#cred_autogenerate_username_scaffold');
            if (ag_username_is_checked[0] != undefined)
                ag_username_is_checked = ag_username_is_checked[0].checked;
            var ag_nickname_is_checked = $('#cred_autogenerate_nickname_scaffold');
            if (ag_nickname_is_checked[0] != undefined)
                ag_nickname_is_checked = ag_nickname_is_checked[0].checked;
            var ag_password_is_checked = $('#cred_autogenerate_password_scaffold');
            if (ag_password_is_checked[0] != undefined)
                ag_password_is_checked = ag_password_is_checked[0].checked;
            credView.reloadUserFields(ag_username_is_checked, ag_nickname_is_checked, ag_password_is_checked, role);
        },
        genUserScaffold: function ()
        {
            var resp = field_data;

            if (!resp || !resp.user_fields)
            {
                gui.Popups.alert({message: settings.locale.form_user_not_set, class: 'cred-dialog'});
                return false;
            }

            var includeWPML = false;
            if ($('#cred_include_wpml_scaffold').is(':checked'))
                includeWPML = true;

            var form_name_1 = $('#title').val();
            if ($.trim(form_name_1) == '')
            {
                gui.Popups.alert({message: settings.locale.set_form_title, class: 'cred-dialog'});
                return false;
            }
            var form_id_1 = $('#post_ID').val();

            var cont = $('#cred-scaffold-area');
            var groups_out = '';
            var groups = {};
            var nlcnt = 0;
            for (var f in resp.groups)
            {
                if (resp.groups.hasOwnProperty(f))
                {
                    nlcnt++;
                    var fields = resp.groups[f];
                    groups[f] = fields;
                    fields = fields.split(',');
                    groups_out += aux.groupOutput(f, fields, resp.groups_conditions, resp.custom_fields, form_id_1, form_name_1, includeWPML, PAD) + NL;
                }
            }

            var taxs_out = '';
            if (parseInt(resp.taxonomies_count, 10) > 0)
            {
                for (var f in resp.taxonomies)
                {
                    if (resp.taxonomies.hasOwnProperty(f))
                    {
                        taxs_out += aux.taxOutput(resp.taxonomies[f], form_id_1, form_name_1, includeWPML, '') + NL;
                    }
                }
            }
            var parents_out = '';
            if (parseInt(resp.parents_count, 10) > 0)
            {
                for (var f in resp.parents)
                {
                    if (resp.parents.hasOwnProperty(f))
                    {
                        parents_out += aux.fieldOutput(resp.parents[f], form_id_1, form_name_1, includeWPML, '',
                                // extra params
                                'date', 'desc', 0,
                                false, 'No Parent', '-- Select ' + resp.parents[f].data.post_type + ' --', resp.parents[f].data.post_type + ' must be selected') + NL;
                    }
                }
            }
            // add fields
            var out = '';
            if ('minimal' == _credModel.get('[form][theme]')/*$('input[name="_cred[form][theme]"]:checked').val()*/) // bypass script and other styles added to form, minimal
                out += "[creduserform class='cred-user-form cred-keep-original']" + NL + NL;
            else
                out += "[creduserform class='cred-user-form']" + NL + NL;
            out += PAD + aux.shortcode(resp.form_fields['form_messages']) + NL + NL;
            out += aux.fieldOutput(resp.user_fields['user_login'], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;
            out += aux.fieldOutput(resp.user_fields['user_pass'], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;
            out += aux.fieldOutput(resp.user_fields['user_pass2'], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;
            out += aux.fieldOutput(resp.user_fields['user_email'], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;
            out += aux.fieldOutput(resp.user_fields['nickname'], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;
            //out += aux.fieldOutput(resp.user_fields['user_url'], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;

            for (var f in resp.custom_fields) {
                if (resp.custom_fields.hasOwnProperty(f))
                {
                    if (resp.custom_fields[f].meta_key == 'description' && resp.custom_fields[f].post_type == 'user' && resp.custom_fields[f].name == 'Biographical Info') {
                    }
                    else
                        out += aux.fieldOutput(resp.custom_fields[f], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;
                }
            }

            if (resp.extra_fields['_featured_image'].supports)
                out += aux.fieldOutput(resp.extra_fields['_featured_image'], form_id_1, form_name_1, includeWPML, PAD) + NL + NL;
            /*out+=aux.groupOutputContent('all', resp._post_data.singular_name+' Properties',
             groups_out+taxs_out+parents_out,
             PAD)+NL+NL;*/
            out += groups_out;
            if (parseInt(resp.taxonomies_count, 10) > 0)
                out += aux.groupOutputContent('taxonomies', 'Taxonomies', taxs_out, PAD) + NL + NL;
            if (parseInt(resp.parents_count, 10) > 0)
                out += aux.groupOutputContent('parents', 'Parents', parents_out, PAD) + NL + NL;
            if ($('#cred_include_captcha_scaffold').is(':checked')) {
                if (resp.extra_fields['recaptcha']['private_key'] != '' && resp.extra_fields['recaptcha']['public_key'] != '')
                    out += PAD + '<div class="cred-field cred-field-recaptcha">' + aux.shortcode(resp.extra_fields['recaptcha']) + '</div>' + NL + NL;
                else {
                    $('#cred_include_captcha_scaffold').attr("checked", false);
                    alert('Captcha keys are empty !');
                }
            }
            out += PAD + aux.shortcode(resp.form_fields['form_submit']) + NL + NL;
            out += '[/creduserform]' + NL;
            cont.val(out);
            return true;
        },
        onLoad: function (resp)
        {
            var data = null, cont2, cont3;
            var cont = $('#cred-shortcodes-box-inner');
            cont.empty();

            // save data for future refernce
            field_data = resp;

            if (resp.form_fields && parseInt(resp.form_fields_count) > 0)
            {
                cont2 = $('<div class="cred-accordeon-item"><a href="javascript:;" class="cred-fields-group-heading">' + settings.locale.form_fields + '</a></div>');
                cont3 = $('<div class="cred-accordeon-item-inside"></div>');
                cont2.append(cont3);
                cont.append(cont2);
                resp2 = resp.form_fields;
                for (var f in resp2)
                {
                    if (resp2.hasOwnProperty(f))
                    {
                        data = $("<a href='javascript:;' class='button cred_field_add' title='" + resp2[f].description + "'>" + resp2[f].name + "</a>");
                        data.data('field', resp2[f]);
                        cont3.append(data);
                    }
                }
            }
            if (resp.post_fields && parseInt(resp.post_fields_count) > 0)
            {
                cont2 = $('<div class="cred-accordeon-item"><a href="javascript:;" class="cred-fields-group-heading">' + settings.locale.post_fields + '</a></div>');
                cont3 = $('<div class="cred-accordeon-item-inside"></div>');
                cont2.append(cont3);
                cont.append(cont2);
                resp2 = resp.post_fields;
                for (var f in resp2)
                {
                    if (resp2.hasOwnProperty(f))
                    {
                        data = $("<a href='javascript:;' class='button cred_field_add' title='" + resp2[f].description + "'>" + resp2[f].name + "</a>");
                        data.data('field', resp2[f]);
                        cont3.append(data);
                    }
                }
            }
            if (resp.custom_fields && parseInt(resp.custom_fields_count) > 0)
            {
                cont2 = $('<div class="cred-accordeon-item"><a href="javascript:;" class="cred-fields-group-heading">' + settings.locale.custom_fields + '</a></div>');
                cont3 = $('<div class="cred-accordeon-item-inside"></div>');
                cont2.append(cont3);
                cont.append(cont2);
                resp2 = resp.custom_fields;
                for (var f in resp2)
                {
                    if (resp2.hasOwnProperty(f))
                    {
                        data = $("<a href='javascript:;' class='button cred_field_add' title='" + resp2[f].description + "'>" + resp2[f].name + "</a>");
                        data.data('field', resp2[f]);
                        cont3.append(data);
                    }
                }
            }
            if (resp.taxonomies && parseInt(resp.taxonomies_count) > 0)
            {
                cont2 = $('<div class="cred-accordeon-item"><a href="javascript:;" class="cred-fields-group-heading">' + settings.locale.taxonomy_fields + '</a></div>');
                cont3 = $('<div class="cred-accordeon-item-inside"></div>');
                cont2.append(cont3);
                cont.append(cont2);
                resp2 = resp.taxonomies;
                for (var f in resp2)
                {
                    if (resp2.hasOwnProperty(f))
                    {
                        resp2[f].taxonomy = true;
                        data = $("<a href='javascript:;' class='button cred_field_add'>" + resp2[f].label + "</a>");
                        data.data('field', resp2[f]);
                        cont3.append(data);
                        if (resp2[f].hierarchical)
                        {
                            resp2[f].aux = {master_taxonomy: resp2[f].name, name: resp2[f].name + '_add_new', add_new_taxonomy: true};
                            data = $("<a href='javascript:;' class='button cred_field_add'>" + resp2[f].label + ' Add New' + "</a>");
                            data.data('field', resp2[f].aux);
                            cont3.append(data);
                        }
                        else
                        {
                            resp2[f].aux = {master_taxonomy: resp2[f].name, name: resp2[f].name + '_popular', popular: true};
                            data = $("<a href='javascript:;' class='button cred_field_add'>" + resp2[f].label + ' Popular' + "</a>");
                            data.data('field', resp2[f].aux);
                            cont3.append(data);
                        }
                    }
                }
            }
            if (resp.parents && parseInt(resp.parents_count) > 0)
            {
                cont2 = $('<div class="cred-accordeon-item"><a href="javascript:;" class="cred-fields-group-heading">' + settings.locale.parent_fields + '</a></div>');
                cont3 = $('<div class="cred-accordeon-item-inside"></div>');
                cont2.append(cont3);
                cont.append(cont2);
                resp2 = resp.parents;
                for (var f in resp2)
                {
                    if (resp2.hasOwnProperty(f))
                    {
                        data = $("<a href='javascript:;' class='button cred_field_add' title='" + resp2[f].description + "'>" + resp2[f].name + "</a>");
                        data.data('field', resp2[f]);
                        cont3.append(data);
                    }
                }
            }
            if (resp.extra_fields && parseInt(resp.extra_fields_count) > 0)
            {
                cont2 = $('<div class="cred-accordeon-item"><a href="javascript:;" class="cred-fields-group-heading">' + settings.locale.extra_fields + '</a></div>');
                cont3 = $('<div class="cred-accordeon-item-inside"></div>');
                cont2.append(cont3);
                cont.append(cont2);
                resp2 = resp.extra_fields;
                var disabled_fields = [];
                for (var f in resp2)
                {
                    if (resp2.hasOwnProperty(f))
                    {
                        if (!resp2[f].disabled)
                        {
                            data = $("<a href='javascript:;' class='button cred_field_add' title='" + resp2[f].description + "'>" + resp2[f].name + "</a>");
                            data.data('field', resp2[f]);
                            cont3.append(data);
                        }
                        else
                        {
                            data = $("<div class='cred_disabled_container'><a href='javascript:;' class='button cred_field_disabled' disabled='disabled'>" + resp2[f].name + "</a><span class='cred-field-disabled-reason'>" + resp2[f].disabled_reason + "</span></div>");
                            data.data('field', resp2[f]);
                            // add them at the end
                            disabled_fields.push(data);
                        }
                    }
                }
                for (var i = 0; i < disabled_fields.length; i++)
                    cont3.append(disabled_fields[i]);
            }
        },
        shortcode: function (field, extra)
        {
            /* use underscores in shortcodes and not hyphens anymore,
             try to keep compatibility */
            var field_out = '';
            var post_type = '';
            var value = " value=''";
            if (field && field.slug)
            {
                if (field.post_type)
                {
                    post_type = " post='" + field.post_type + "'";
                }
                if (field.value)
                {
                    value = " value='" + field.value + "'";
                }
                // add url parameter
                if (!field.taxonomy && !field.is_parent && 'form_messages' != field.type)
                    value += " urlparam=''";

                if (field.type == 'image' || field.type == 'file')
                {
                    var max_width = (extra && extra.max_width) ? extra.max_width : false;
                    var max_height = (extra && extra.max_height) ? extra.max_height : false;
                    if (max_width && !isNaN(max_width))
                        value += " max_width='" + max_width + "'";
                    if (max_height && !isNaN(max_height))
                        value += " max_height='" + max_height + "'";
                }
                if (field.is_parent)
                {
                    var parent_order = (extra && extra.parent_order) ? extra.parent_order : false;
                    var parent_ordering = (extra && extra.parent_ordering) ? extra.parent_ordering : false;
                    var parent_results = (extra && extra.parent_max_results) ? extra.parent_max_results : false;
                    var required = (extra && extra.required) ? extra.required : false;
                    var no_parent_text = (extra && extra.no_parent_text) ? extra.no_parent_text : false;
                    var select_parent_text = (extra && extra.select_parent_text) ? extra.select_parent_text : false;
                    var validate_parent_text = (extra && extra.validate_parent_text) ? extra.validate_parent_text : false;
                    if (parent_results !== false && !isNaN(parent_results))
                        value += " max_results='" + parent_results + "'";
                    if (parent_order)
                        value += " order='" + parent_order + "'";
                    if (parent_ordering)
                        value += " ordering='" + parent_ordering + "'";
                    if (required)
                        value += " required='" + required.toString() + "'";
                    if (required && select_parent_text !== false)
                        value += " select_text='" + select_parent_text + "'";
                    if (required && validate_parent_text !== false)
                        value += " validate_text='" + validate_parent_text + "'";
                    if (!required && no_parent_text !== false)
                        value += " no_parent_text='" + no_parent_text + "'";
                }
                if (field.type == 'textfield' ||
                        field.type == 'textarea' ||
                        field.type == 'wysiwyg' ||
                        field.type == 'date' ||
                        field.type == 'phone' ||
                        field.type == 'url' ||
                        field.type == 'numeric' ||
                        field.type == 'email')
                {
                    var readonly = (extra && extra.readonly) ? extra.readonly : false;
                    var escape = (extra && extra.escape) ? extra.escape : false;
                    var placeholder = (extra && extra.placeholder) ? extra.placeholder : false;
                    if (readonly)
                        value += " readonly='" + readonly.toString() + "'";
                    if (escape)
                        value += " escape='" + escape.toString() + "'";
                    if (placeholder && '' != placeholder)
                        value += " placeholder='" + placeholder + "'";
                }
                field_out = "[cred_field field='" + field.slug + "'" + post_type + value + "]";
            }
            if (field && field.taxonomy)
            {
                if (field.hierarchical)
                    field_out = "[cred_field field='" + field.name + "' display='checkbox']";
                else
                    field_out = "[cred_field field='" + field.name + "']";
            }
            if (field && field.popular)
            {
                field_out = "[cred_field field='" + field.name + "' taxonomy='" + field.master_taxonomy + "' type='show_popular']";
            }
            if (field && field.add_new_taxonomy)
            {
                field_out = "[cred_field field='" + field.name + "' taxonomy='" + field.master_taxonomy + "' type='add_new']";
            }
            return field_out;
        },
        fieldOutput: function (field, form_id, form_name, WPML, pad)
        {

            if (!pad)
                pad = '';
            var field_out = [];
            var post_type = '';
            var value = '';
            WPML = WPML || false;

            if (field)
            {
                field_out.push(pad + '<div class="cred-field cred-field-' + field.slug + '">');
                if ('checkbox' != field.type) {
                    field_out.push(pad + PAD + '<label class="cred-label">');
                    if (WPML)
                    {
                        field_out.push("[wpml-string context='cred-form-" + form_name + "-" + form_id + "' name='" + field.name + "']" + field.name + "[/wpml-string]");
                    }
                    else
                    {
                        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/191070793/comments
                        if (field.name == 'Post Description')
                            field.name = 'Body Content';
                        //##########################################################################################
                        field_out.push(field.name);
                    }
                    field_out.push('</label>');
                }
                var args = [field];
                if (arguments.length == 5)
                    args = args.concat(Array.prototype.slice.call(arguments, 6));
                else
                    args = args.concat(Array.prototype.slice.call(arguments, 5));
                field_out.push(pad + PAD + aux.shortcode.apply(null, args));

                field_out.push(pad + '</div>');
            }
            return field_out.join(NL);
        },
        groupOutput: function (group, fields, conditions, obj, form_id, form_name, WPML, pad)
        {
            if (!pad)
                pad = '';
            var group_out = [];
            var group_conditional = false,
                    group_conditiona_string = '';
            if (conditions.hasOwnProperty(group)) {
                group_conditional = true;
                group_conditiona_string = conditions[group];
            }
            var group_class_slug = 'cred-group-' + group.replace(/\s+/g, '-');
            if (group_conditional) {
                group_out.push(pad + "[cred_show_group if='" + group_conditiona_string + "'  mode='fade-slide']");
            }
            group_out.push(pad + '<div class="cred-group ' + group_class_slug + '">');
            if (WPML)
            {
                group_out.push(pad + PAD + "<div class='cred-header'><h3>[wpml-string context='cred-form-" + form_name + "-" + form_id + "' name='" + group + "']" + group + "[/wpml-string]</h3></div>");
            }
            else
            {
                group_out.push(pad + PAD + '<div class="cred-header"><h3>' + group + '</h3></div>');
            }
            for (var ii = 0; ii < fields.length; ii++)
            {
                if (obj[fields[ii]] && obj[fields[ii]]._cred_ignore)
                    continue;
                group_out.push(aux.fieldOutput(obj[fields[ii]], form_id, form_name, WPML, pad + PAD));
            }
            group_out.push(pad + '</div>');
            if (group_conditional) {
                group_out.push(pad + '[/cred_show_group]');
            }
            return group_out.join(NL) + NL;
        },
        groupOutputContent: function (slug, group_name, content, pad)
        {
            if (!pad)
                pad = '';
            var group_out = [];
            var group_class_slug = 'cred-group-' + slug.replace(/\s+/g, '-');
            group_out.push(pad + '<div class="cred-group ' + group_class_slug + '">');
            //group_out.push(pad+PAD+'<div><h2>'+group_name+'</h2></div>');
            var lines = content.split(NL);
            for (var i = 0; i < lines.length; i++)
            {
                lines[i] = pad + PAD + lines[i];
            }
            content = lines.join(NL);
            group_out.push(content);
            group_out.push(pad + '</div>');
            return group_out.join(NL);
        },
        taxOutput: function (tax, form_id, form_name, WPML, pad)
        {
            WPML = WPML || false;
            if (!pad)
                pad = '';
            var tax_out = [];
            tax_out.push(pad + '<div class="cred-taxonomy cred-taxonomy-' + tax.name + '">');

            if (WPML)
                tax_out.push(pad + PAD + "<div class='cred-header'><h3>[wpml-string context='cred-form-" + form_name + "-" + form_id + "' name='" + tax.label + "']" + tax.label + "[/wpml-string]</h3></div>");
            else
                tax_out.push(pad + PAD + '<div class="cred-header"><h3>' + tax.label + '</h3></div>');
            tax_out.push(pad + PAD + aux.shortcode(tax));
            tax_out.push(pad + PAD + '<div class="cred-taxonomy-auxilliary cred-taxonomy-auxilliary-' + tax.aux.name + '">');
            tax_out.push(pad + PAD + PAD + aux.shortcode(tax.aux));
            tax_out.push(pad + PAD + '</div>');
            tax_out.push(pad + '</div>');
            return tax_out.join(NL);
        },
        formPreview: function ()
        {
            if (cred_settings._current_page == 'cred-user-form') {
                return aux.userFormPreview();
            }
            var data = utils.getContent($('#content'));

            if ('' == $.trim(data))
                return false;

            var post_type = _credModel.get('[post][post_type]'), //$('#cred_post_type').val();
                    form_type = _credModel.get('[form][type]'), //$('#cred_form_type').val();
                    css_to_use = _credModel.get('[form][theme]'), //$('input[name="_cred[form][theme]"]:checked').val();
                    extra_css = utils.getContent($('#cred-extra-css-editor')),
                    extra_js = utils.getContent($('#cred-extra-js-editor')),
                    previewForm, previewPopup, id, target, action;

            if (!post_type || post_type == '' || !form_type || form_type == '')
            {
                gui.Popups.alert({message: settings.locale.form_types_not_set, class: 'cred-dialog'});
                return false;
            }

            id = $('#post_ID').val();
            target = 'CRED_Preview_' + id;
            action = settings.homeurl + 'index.php?cred_form_preview=' + id;

            previewPopup = window.open('', target, "status=0,title=0,height=600,width=800,scrollbars=1,resizable=1");
            if (previewPopup)
            {
                previewForm = $("<form style='display:none' name='cred_form_preview_form' method='post' target='" + target + "' action='" + action + "'><input type='hidden' name='" + PREFIX + "form_css_to_use' value='" + css_to_use + "' /><input type='hidden' name='" + PREFIX + "form_preview_post_type' value='" + post_type + "' /><input type='hidden' name='" + PREFIX + "form_preview_form_type' value='" + form_type + "' /><textarea  name='" + PREFIX + "form_preview_content'>" + utils.stripslashes(data) + "</textarea><textarea  name='" + PREFIX + "extra_css_to_use'>" + extra_css + "</textarea><textarea  name='" + PREFIX + "extra_js_to_use'>" + extra_js + "</textarea></form>");
                // does not work in IE(9), so add it to current doc and submit
                //$(previewPopup.document.body).append(previewForm);
                $(document.body).append(previewForm);
                previewForm.submit();
                // remove it after a while
                setTimeout(function ()
                {
                    previewForm.remove();
                }, 1000);

            }
            else
                gui.Popups.alert({message: settings.locale.enable_popup_for_preview, class: 'cred-dialog'});

            return false;
        },
        userFormPreview: function ()
        {
            var data = utils.getContent($('#content'));

            if ('' == $.trim(data))
                return false;

            var post_type = _credModel.get('[post][post_type]'), //$('#cred_post_type').val();
                    form_type = _credModel.get('[form][type]'), //$('#cred_form_type').val();
                    css_to_use = _credModel.get('[form][theme]'), //$('input[name="_cred[form][theme]"]:checked').val();
                    extra_css = utils.getContent($('#cred-extra-css-editor')),
                    extra_js = utils.getContent($('#cred-extra-js-editor')),
                    previewForm, previewPopup, id, target, action;

//            if (!post_type || post_type == '' || !form_type || form_type == '')
//            {
//                gui.Popups.alert({message: settings.locale.form_types_not_set, class: 'cred-dialog'});
//                return false;
//            }

            id = $('#post_ID').val();
            target = 'CRED_Preview_' + id;
            action = settings.homeurl + 'index.php?cred_user_form_preview=' + id;

            previewPopup = window.open('', target, "status=0,title=0,height=600,width=800,scrollbars=1,resizable=1");
            if (previewPopup)
            {
                post_type = 'user';
                previewForm = $("<form style='display:none' name='cred_user_form_preview_form' method='post' target='" + target + "' action='" + action + "'><input type='hidden' name='" + PREFIX + "form_css_to_use' value='" + css_to_use + "' /><input type='hidden' name='" + PREFIX + "form_preview_post_type' value='" + post_type + "' /><input type='hidden' name='" + PREFIX + "form_preview_form_type' value='" + form_type + "' /><textarea  name='" + PREFIX + "form_preview_content'>" + utils.stripslashes(data) + "</textarea><textarea  name='" + PREFIX + "extra_css_to_use'>" + extra_css + "</textarea><textarea  name='" + PREFIX + "extra_js_to_use'>" + extra_js + "</textarea></form>");
                // does not work in IE(9), so add it to current doc and submit
                //$(previewPopup.document.body).append(previewForm);
                $(document.body).append(previewForm);
                previewForm.submit();
                // remove it after a while
                setTimeout(function ()
                {
                    previewForm.remove();
                }, 1000);

            }
            else
                gui.Popups.alert({message: settings.locale.enable_popup_for_preview, class: 'cred-dialog'});

            return false;
        }
    };

    // public methods / properties
    var self = {
        // add the extra Modules as part of main CRED Module
        app: utils,
        gui: gui,
        mvc: mvc,
        settings: settings,
        route: function (path, params, raw)
        {
            return utils.route('cred', settings.ajaxurl, path, params, raw);
        },
        getFormFields: function ($area)
        {
            if (!field_data)
                return [];

            var content = utils.getContent($area).replace(/[\n\r]+/g, ' '), // normalize, remove \n
                    fields = field_data,
                    patterns = [
                        // custom fields
                        {rx: /\[cred[\-_]field\b[^\[\]]*field=[\"\']([\d\w\-_]+)[\"\'][^\[\]]*value=[\"\']([\w\-\[\]\"\'=\s]+)?[\"\'][^\[\]]*?\]/g, field: 1, type: false, generic: false},
                        // generic fields variation 1
                        {rx: /\[cred\-generic\-field\b([^\[\]]*?)\](.+?)\[\/cred\-generic\-field\]/g, atts: 1, content: 2, generic: true},
                        // generic fields variation 2
                        {rx: /\[cred_generic_field\b([^\[\]]*?)\](.+?)\[\/cred_generic_field\]/g, atts: 1, content: 2, generic: true}

                    ],
                    returned_fields = [], pat, patl, match, match2, name, field, type, repetitive, generic, persistent,
                    field_type_rxp = /field=[\"\']([\d\w\-_]+?)[\"\'][^\[\]]*?\btype=[\"\']([\d\w\-_]+?)[\"\']/,
                    type_field_rxp = /type=[\"\']([\d\w\-_]+?)[\"\'][^\[\]]*?\bfield=[\"\']([\d\w\-_]+?)[\"\']/,
                    persist_rxp = /["']persist["']\s*\:\s*(\d)/,
                    generic_field_type_rxp = /["']generic_type["']\s*\:\s*["']([\w_]+)["']/
                    ;

            //compatibility for cred user forms
            if (!fields.post_fields && fields.user_fields) {
                fields.post_fields = fields.user_fields;
            }

            // parse content
            for (pat = 0, patl = patterns.length; pat < patl; pat++)
            {
                if (patterns[pat].generic)
                {
                    while (match = patterns[pat].rx.exec(content))
                    {
                        field = false;
                        type = false;
                        name = field;
                        repetitive = false;
                        generic = true;
                        persistent = false;

                        var
                                match_field_type = (patterns[pat].atts && match[patterns[pat].atts]) ? field_type_rxp.exec(match[patterns[pat].atts]) : false,
                                match_type_field = (patterns[pat].atts && match[patterns[pat].atts]) ? type_field_rxp.exec(match[patterns[pat].atts]) : false,
                                match_persist = (patterns[pat].content && match[patterns[pat].content]) ? persist_rxp.exec(match[patterns[pat].content]) : false,
                                match_generic_field_type = (patterns[pat].content && match[patterns[pat].content]) ? generic_field_type_rxp.exec(match[patterns[pat].content]) : false
                                ;

                        //console.log(match);
                        //console.log(match[patterns[pat].atts]);
                        //console.log(match[patterns[pat].content]);

                        if (match_field_type)
                        {
                            field = match_field_type[1];
                            type = match_field_type[2];
                            name = field;
                            //console.log(match_field_type);
                        }
                        else if (match_type_field)
                        {
                            type = match_type_field[1];
                            field = match_type_field[2];
                            name = field;
                            //console.log(match_type_field);
                        }
                        else
                        {
                            //console.log('continue');
                            continue;
                        }

                        if (match_persist && match_persist[1] && '1' == match_persist[1])
                        {
                            persistent = true;
                        }
                        else
                        {
                            persistent = false;
                        }

                        if (match_generic_field_type && match_generic_field_type[1] && '' != match_generic_field_type[1])
                        {
                            type = match_generic_field_type[1];
                        }

                        //console.log(match_persist);

                        returned_fields.push({name: field, field: field, type: type, repetitive: repetitive, persistent: persistent, generic: generic});
                    }
                }
                else
                {
                    while (match = patterns[pat].rx.exec(content))
                    {
                        field = false;
                        type = false;
                        name = field;
                        repetitive = false;
                        generic = false;
                        persistent = true;

                        field = (false !== patterns[pat].field && match[patterns[pat].field]) ? match[patterns[pat].field] : false;
                        type = (false !== patterns[pat].type && match[patterns[pat].type]) ? match[patterns[pat].type] : false;
                        name = field;
                        repetitive = false;
                        generic = false;
                        persistent = true;

                        if (field)
                        {
                            if (fields.post_fields[field])
                            {
                                type = fields.post_fields[field]['type'];
                                if (
                                        fields.post_fields[field]['data'] &&
                                        fields.post_fields[field]['data']['repetitive'] &&
                                        fields.post_fields[field]['data']['repetitive'] == '1'
                                        )
                                    repetitive = true;
                                if (fields.post_fields[field]['plugin_type_prefix'])
                                    field = fields.post_fields[field]['plugin_type_prefix'] + name;
                                returned_fields.push({name: name, field: field, type: type, repetitive: repetitive, persistent: persistent, generic: generic});
                            }
                            else if (fields.custom_fields[field])
                            {
                                type = fields.custom_fields[field]['type'];
                                if (
                                        fields.custom_fields[field]['data'] &&
                                        fields.custom_fields[field]['data']['repetitive'] &&
                                        fields.custom_fields[field]['data']['repetitive'] == '1'
                                        )
                                    repetitive = true;
                                if (fields.custom_fields[field]['plugin_type_prefix'])
                                    field = fields.custom_fields[field]['plugin_type_prefix'] + name;
                                returned_fields.push({name: name, field: field, type: type, repetitive: repetitive, persistent: persistent, generic: generic});
                            }
                            else if (fields.parents[field])
                            {
                                type = fields.parents[field]['type'];
                                if (
                                        fields.parents[field]['data'] &&
                                        fields.parents[field]['data']['repetitive'] &&
                                        fields.parents[field]['data']['repetitive'] == '1'
                                        )
                                    repetitive = true;
                                if (fields.parents[field]['plugin_type_prefix'])
                                    field = fields.parents[field]['plugin_type_prefix'] + name;
                                returned_fields.push({name: name, field: field, type: type, repetitive: repetitive, persistent: persistent, generic: generic});
                            }
                            else if (fields.taxonomies[field])
                            {
                                type = 'taxonomy';
                                if (fields.taxonomies[field]['plugin_type_prefix'])
                                    field = fields.taxonomies[field]['plugin_type_prefix'] + name;
                                returned_fields.push({name: name, field: field, type: type, repetitive: repetitive, persistent: persistent, generic: generic});
                            }
                        }
                    }
                }
            }

            return returned_fields;
        },
        isVisible: function (id) {
            var element = $('#' + id);
            if (element.length > 0 &&
                    element.css('visibility') !== 'hidden' &&
                    element.css('display') !== 'none') {
                return true;
            } else {
                return false;
            }
        },
        doCheck: function (step)
        {
            //https://onthegosystems.myjetbrains.com/youtrack/issue/cred-128
            //###############################################################
            var is_form_settings = ((!step || (step && step == 2)) && self.isVisible('cred_form_type')); //$('#cred_form_type').length;
            var type_form = $('#cred_form_type').val();
            if (is_form_settings && !type_form) {
                gui.Popups.alert({message: settings.locale.invalid_form_type, class: 'cred-dialog'});
                return false;
            }

            var is_user_form = ((!step || (step && step == 2)) && self.isVisible('cred_form_user_role')); //$('#cred_form_user_role').length;
            var type_user_form = $('#cred_form_type').val();
            if (is_user_form && type_user_form == 'new' && ($('#cred_form_user_role').val() == null || $('#cred_form_user_role').val() == '')) {
                gui.Popups.alert({message: settings.locale.invalid_user_role, class: 'cred-dialog'});
                return false;
            }
            //###############################################################

            // title check
            var title = $('#title').val();
            if (/[\#\@\[\]\'\"\!\/\\]/g.test(title) || title.length <= 0)
            {
                gui.Popups.alert({message: settings.locale.invalid_title, class: 'cred-dialog'});
                return false;
            }

            return true;
        },
        getContents: function ()
        {
            return {
                'content': utils.getContent($('#content')),
                'cred-extra-css-editor': utils.getContent($('#cred-extra-css-editor')),
                'cred-extra-js-editor': utils.getContent($('#cred-extra-js-editor'))
            };
        },
        getFieldData: function () {
            return field_data;
        },
        getCodeMirror: function () {
            return CodeMirrorEditors['content'];
        },
        getCSSCodeMirror: function () {
            return CodeMirrorEditors['cred-extra-css-editor'];
        },
        getJSCodeMirror: function () {
            return CodeMirrorEditors['cred-extra-js-editor'];
        },
        getModel: function () {
            return _credModel
        },
        getView: function () {
            return credView
        },
        forms: function (useCodeMirror)
        {
            var doinit = true,
                    firstLoad = true,
                    $_post = $('#post'),
                    formtypediv = $('#credformtypediv'),
                    posttypediv = $('#credposttypediv'),
                    contentdiv = $('#credformcontentdiv'),
                    postdivrich = $('#postdivrich'),
                    extradiv = $('#credextradiv'),
                    messagesdiv = $('#credmessagesdiv'),
                    notificationdiv = $('#crednotificationdiv'),
                    loader = $('#cred_ajax_loader_small_id');

            // init model with current form data (one model for all data)
            _credModel = new mvc.Model('_cred', window._credFormData);
            // can use multiple views per same model
            credView = new mvc.View('cred', _credModel, {
                reloadUserFields: function (ag_username_is_checked, ag_nickname_is_checked, ag_password_is_checked, role) {
                    aux.update_autogeneration_fields();
                    loader.show();
                    $('#cred-scaffold-insert').hide();
                    $('#cred_autogenerate_username_scaffold').prop("disabled", "disabled");
                    $('#cred_autogenerate_nickname_scaffold').prop("disabled", "disabled");
                    $('#cred_autogenerate_password_scaffold').prop("disabled", "disabled");

                    $.ajax({
                        url: self.route('/Forms/getUserFields'),
                        timeout: 10000,
                        type: 'POST',
                        data: 'role=' + role + '&ag_pass=' + ag_password_is_checked + '&ag_uname=' + ag_username_is_checked + '&ag_nname=' + ag_nickname_is_checked + '&_wpnonce=' + settings._cred_wpnonce,
                        dataType: 'json',
                        success: function (resp) {
                            // load and dispatch event of fields loaded
                            aux.onLoad(resp);
                            if (role != null) {
                                aux.genScaffold();
                            }
                            utils.dispatch('cred.fieldsLoaded');
                            loader.hide();
                            $('#cred-scaffold-insert').show();
                            $('#cred_autogenerate_username_scaffold').prop("disabled", false);
                            $('#cred_autogenerate_nickname_scaffold').prop("disabled", false);
                            $('#cred_autogenerate_password_scaffold').prop("disabled", false);
                        }
                    });
                },
                init: function () {

                    check_cred_form_type(aux);
                    $('#cred_form_type').bind('change', function () {
                        check_cred_form_type(aux);
                    });

                    var view = this,
                            model = this._model;

                    // assume View is valid initially
                    view.isValid = true;

                    view
                            // add some custom actions
                            /*.action('insert', function($el, data){
                             if (!data.bind) return;
                             data=data.bind;
                             if (data['domRef'])
                             $to=$(data['domRef']);
                             else return;
                             utils.InsertAtCursor($to, $el.val());
                             })*/
                            .action('styleForm', function ($el, data) {
                                var codemirror = utils.isCodeMirror($('#content'));
                                if (codemirror)
                                {
                                    codemirror.focus();
                                    codemirror.execCommand('selectAll');
                                    var content = utils.getContent($('#content')).replace(/[\n\r]+/g, '%%NL%%'); // normalize, remove \n
                                    // remove class "cred-keep-original", if exists
                                    content = content.replace(/\[credform([^\]]*) class="(.*?)[\s]*?cred-keep-original(.*?)"([^\]]*)\]/gi, '[credform$1 class="$2$3"$4]');

                                    if ('minimal' == $el.val())
                                    {
                                        // add class attribute if not exists
                                        content = content.replace(/\[credform(((?!class="(.*?)")[^\]])*)\]/gi, '[credform$1 class=""]');
                                        // add class "cred-keep-original"
                                        content = content.replace(/\[credform([^\]]*) class="(.*?)"([^\]]*)\]/gi, '[credform$1 class="%%SPLIT%%$2 cred-keep-original%%SPLIT%%"$3]').split('%%SPLIT%%');
                                        content[1] = $.trim(content[1]);
                                        content = content.join('');
                                    }

                                    utils.InsertAtCursor($('#content'), content.replace(/%%NL%%/g, NL));

                                }
                            })
                            .action('refreshFormFields', function ($el, data) {
                                refreshFromFormFields();
                                gui.Popups.flash({
                                    message: settings.locale.refresh_done,
                                    class: 'cred-dialog'
                                });
                            })
                            .action('validateSection', function ($el, data) {
                                if ($el[0] && data && undefined !== data.validationResult)
                                    $el[0].__isCredValid = data.validationResult;
                            })
                            .action('fadeSlide', function ($el, data) {
                                if (!data.bind)
                                    return;
                                data = data.bind;
                                if (data['domRef'])
                                    $el = $(data['domRef']);

                                if (data['condition'])
                                {
                                    data['condition'] = model.eval(data['condition']);
                                    if (undefined !== data['condition'])
                                    {
                                        (data['condition'])
                                                ? $el.slideFadeDown('slow', 'quintEaseOut')
                                                : $el.slideFadeUp('slow', 'quintEaseOut');
                                    }
                                }
                                else
                                    $el.slideFadeDown('slow', 'quintEaseOut');
                            })
                            .action('fadeIn', function ($el, data) {
                                if (!data.bind)
                                    return;
                                data = data.bind;
                                if (data['domRef'])
                                    $el = $(data['domRef']);

                                if (data['condition'])
                                {
                                    data['condition'] = model.eval(data['condition']);
                                    if (undefined !== data['condition'])
                                        (data['condition'])
                                                ? $el.stop().fadeIn('slow')
                                                : $el.stop().fadeOut('slow', function () {
                                            $(this).hide();
                                        });
                                }
                                else
                                    $el.stop().fadeIn('slow');
                            })
                            // custom confirm box
                            .func('confirm', function (msg, callback) {
                                gui.Popups.confirm({
                                    message: msg,
                                    class: 'cred-dialog',
                                    buttons: [settings.locale.Yes, settings.locale.No],
                                    primary: settings.locale.Yes,
                                    callback: function (button) {
                                        if ($.isFunction(callback))
                                        {
                                            if (button == settings.locale.Yes)
                                                callback.call(view, true);
                                            else
                                                callback.call(view, false);
                                        }
                                    }
                                });
                            })
                            // add another hook when model changes
                            .event('model:change', function (e, data) {

                                if ('[post][post_type]' == data.key)
                                {
                                    loader.show();
                                    $('#cred_autogenerate_username_scaffold').prop("disabled", "disabled");
                                    $('#cred_autogenerate_nickname_scaffold').prop("disabled", "disabled");
                                    $('#cred_autogenerate_password_scaffold').prop("disabled", "disabled");

                                    if (cred_settings._current_page == 'cred-user-form') {
                                        function start_role_call() {
                                            console.log("start_role_call");
                                            setTimeout(function () {
                                                var role = $('#cred_form_user_role').val();
                                                if (role == null)
                                                    return;
                                                if (role !== "") {
                                                    aux.update_autogeneration_fields();
                                                    console.log(role);
                                                    aux.reloadUserFields(role);
                                                } else {
                                                    start_role_call();
                                                }
                                            }, 3000);
                                        }
                                        start_role_call();

//                                        $.ajax({
//                                            url: self.route('/Forms/getUserFields'),
//                                            timeout: 10000,
//                                            type: 'POST',
//                                            data: 'role=' + role + '&ag_uname=' + _original_cred_autogenerate_username_scaffold + '&ag_nname=' + _original_cred_autogenerate_nickname_scaffold + '&ag_pass=' + _original_cred_autogenerate_password_scaffold + '&_wpnonce=' + settings._cred_wpnonce,
//                                            dataType: 'json',
//                                            success: function (resp) {
//                                                // load and dispatch event of fields loaded
//                                                aux.onLoad(resp);
//                                                utils.dispatch('cred.fieldsLoaded');
//                                                loader.hide();
//                                                $('#cred_autogenerate_username_scaffold').prop("disabled", false);
//                                                $('#cred_autogenerate_nickname_scaffold').prop("disabled", false);
//                                                $('#cred_autogenerate_password_scaffold').prop("disabled", false);
//                                            }
//                                        });
                                    } else {
                                        $.ajax({
                                            url: self.route('/Forms/getPostFields'),
                                            timeout: 10000,
                                            type: 'POST',
                                            data: 'post_type=' + data.value + '&_wpnonce=' + settings._cred_wpnonce,
                                            dataType: 'json',
                                            success: function (resp) {
                                                // load and dispatch event of fields loaded
                                                aux.onLoad(resp);
                                                utils.dispatch('cred.fieldsLoaded');
                                                loader.hide();
                                            }
                                        });
                                    }
                                }
                                else if (/^\[notification\]\[notifications\]\[\d+\]/.test(data.key))
                                {

                                    /*
                                     var nlength=model.count('[notification][notifications]'),
                                     enable=model.get('[notification][enable]');
                                     if (0==nlength && ('1'==enable || 1==enable))
                                     model.set('[notification][enable]', '', true);
                                     */

                                    var match = /^\[notification\]\[notifications\]\[(\d+)\]$/.exec(data.key);
                                    if (match)
                                    {
                                        // activate tinyMCE for notification body
                                        if (window.tinyMCEPreInit)
                                        {
                                            var newId = 'credmailbody' + match[1];

                                            if (typeof (window.tinyMCE) == 'object' && window.tinyMCEPreInit.mceInit['credmailbody__i__'])
                                            {
                                                // notification is removed
                                                if (window.tinyMCEPreInit.mceInit[newId])
                                                {
                                                    try {
                                                        if (window.tinyMCE.get(newId)) {
                                                            window.tinyMCE.execCommand('mceFocus', false, newId);
                                                            window.tinyMCE.execCommand('mceRemoveControl', false, newId);
                                                        }
                                                        delete window.tinyMCEPreInit.mceInit[newId];
                                                    } catch (e) {
                                                    }
                                                }
                                                else
                                                {
                                                    window.tinyMCEPreInit.mceInit[newId] = window.tinyMCE.extend({}, window.tinyMCEPreInit.mceInit['credmailbody__i__']);
                                                    for (var att in {'body_class': 1, 'elements': 1})
                                                    {
                                                        if (window.tinyMCEPreInit.mceInit[newId][att])
                                                            window.tinyMCEPreInit.mceInit[newId][att] = window.tinyMCEPreInit.mceInit[newId][att].replace(/__i__/g, match[1]);
                                                    }

                                                    // init tinyMCE on new dynamic area
                                                    try {
                                                        var ed = new tinymce.Editor(newId, window.tinyMCEPreInit.mceInit[newId], tinymce.EditorManager);
                                                        ed.on('mousedown', function (e) {
                                                            if (this.id)
                                                                window.wpActiveEditor = window.wpcfActiveEditor = this.id.slice(3, -5);
                                                        });
                                                        ed.render();
                                                    } catch (e) {
                                                    }
                                                }
                                            }
                                            if (window.tinyMCEPreInit.qtInit && window.tinyMCEPreInit.qtInit['credmailbody__i__'])
                                            {
                                                // notification is removed
                                                if (window.tinyMCEPreInit.qtInit[newId])
                                                {
                                                    try {
                                                        delete window.tinyMCEPreInit.qtInit[newId];
                                                    } catch (e) {
                                                    }
                                                }
                                                else
                                                {
                                                    window.tinyMCEPreInit.qtInit[newId] = $.extend({}, window.tinyMCEPreInit.qtInit['credmailbody__i__']);
                                                    for (var att in {'id': 1})
                                                    {
                                                        if (window.tinyMCEPreInit.qtInit[newId][att])
                                                            window.tinyMCEPreInit.qtInit[newId][att] = window.tinyMCEPreInit.qtInit[newId][att].replace(/__i__/g, match[1]);
                                                    }

                                                    if (typeof (window.tinyMCE) != 'object')
                                                    {
                                                        var el = window.tinyMCEPreInit.qtInit[newId].id;
                                                        document.getElementById('wp-' + el + '-wrap').onmousedown = function () {
                                                            window.wpActiveEditor = window.wpcfActiveEditor = this.id.slice(3, -5);
                                                        }
                                                    }

                                                    if (typeof (window.QTags) == 'function')
                                                    {
                                                        // init quicktags on new dynamic area
                                                        var instances_bak = window.QTags.instances, ed;
                                                        window.QTags.instances = {};
                                                        try {
                                                            ed = window.quicktags(window.tinyMCEPreInit.qtInit[newId]);
                                                            window.QTags.instances[newId] = ed;
                                                            window.QTags._buttonsInit();
                                                        } catch (e) {
                                                        }
                                                        window.QTags.instances = instances_bak;
                                                        window.QTags.instances[newId] = ed;
                                                        $('#wp-' + newId + '-wrap').removeClass('html-active').addClass('tmce-active');
                                                        if (window.tinyMCE.get(newId))
                                                            window.switchEditors.go(newId, 'tmce');
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    else
                                    {
                                        // enable/disable some placeholders
                                        var match = /^\[notification\]\[notifications\]\[(\d+)\]\[event\]\[type\]$/.exec(data.key);
                                        if (match)
                                        {
                                            var placeholders = view.getElements('#cred_mail_subject_placeholders-' + match[1])
                                                    .find('a.cred_field_add_code')
                                                    .add(
                                                            view.getElements('#cred_mail_body_placeholders-' + match[1])
                                                            .find('a.cred_field_add_code')
                                                            );
                                            if ('form_submit' == data.value)
                                            {
                                                placeholders.filter(function () {
                                                    var val = $(this).data('value');
                                                    if ($.inArray(val, ['%%FORM_DATA%%', '%%POST_PARENT_TITLE%%', '%%POST_PARENT_LINK%%']) > -1)
                                                        return true;
                                                    return false;
                                                }).prop('disabled', false).show();
                                            }
                                            else
                                            {
                                                placeholders.filter(function () {
                                                    var val = $(this).data('value');
                                                    if ($.inArray(val, ['%%FORM_DATA%%', '%%POST_PARENT_TITLE%%', '%%POST_PARENT_LINK%%']) > -1)
                                                        return true;
                                                    return false;
                                                }).prop('disabled', true).hide();
                                            }
                                            utils.dispatch('cred.notificationEventChanged', data.value, placeholders)
                                        }
                                    }
                                }
                                /*
                                 else if (/^\[notification\]\[enable\]/.test(data.key))
                                 {
                                 var enabled = (data.value == '1');
                                 //view.getElements('#cred_notification_add_container').toggle(enabled);
                                 }
                                 */

                                // display validation messages per section
                                validateView();
                            })
                            // add another hook when view changes
                            .event('view:change', function (e, data) {
                                if (data.el && data.bind)
                                {
                                    if (data.el.hasClass('cred-notification-add-button'))
                                    {
                                        /*
                                         var nlength=model.count('[notification][notifications]'),
                                         enable=model.get('[notification][enable]');
                                         if (1==nlength && (''==enable || 0==enable))
                                         model.set('[notification][enable]', '1', true);
                                         */
                                    }
                                }

                                // display validation messages per section
                                validateView();
                            });

                    function validateView()
                    {
                        // display validation messages per section
                        view.isValid = true;
                        // use caching here
                        view.getElements('.cred_validation_section').each(function () {
                            var $this = $(this);
                            isValid = true;

                            $this.find('input, select, textarea').each(function () {
                                var $this2 = $(this);
                                if (undefined !== $this2[0].__isCredValid)
                                {
                                    if (!$this2[0].__isCredValid)
                                        isValid = false;
                                }
                            });

                            $this.find('input').each(function () {
                                if ($(this).val() == 'author' && $(this).is(':checked')) {
                                    isValid = true;
                                }
                            });

                            if (!isValid)
                            {
                                view.isValid = false;
                                //Attention: added delay action for preventing loss of click after change event processing (case of notifications alert messages)
                                $('.cred-notification.cred-error.cred-section-validation-message', $this).delay(100).show(0);
                            }
                            else
                            {
                                //Attention: added delay action for preventing loss of click after change event processing (case of notifications alert messages)
                                $('.cred-notification.cred-error.cred-section-validation-message', $this).delay(100).hide(0);
                            }
                        });
                    }

                    // setup some handlers
                    function refreshFromFormFields()
                    {
                        if (!(field_data && (field_data.post_fields || field_data.user_fields)))
                            return;

                        var fields = self.getFormFields();

                        var _persistent_mail_fields = [],
                                _persistent_user_id_fields = [],
                                _persistent_text_fields = [],
                                _persistent_checkbox_fields = [],
                                _persistent_select_fields = [];

                        for (var i = 0, l = fields.length; i < l; i++)
                        {
                            if (fields[i].persistent)
                            {
                                if (fields[i].type == 'mail' || fields[i].type == 'email')
                                    _persistent_mail_fields.push({value: fields[i].field, label: fields[i].name});

                                if (fields[i].type == 'user_id')
                                    _persistent_user_id_fields.push({value: fields[i].field, label: fields[i].name});

                                if (
                                        (-1 == $.inArray(fields[i].field, ['post_title', 'post_content', 'post_excerpt'])) &&
                                        (
                                                fields[i].type == 'text'
                                                || fields[i].type == 'textfield'
                                                || fields[i].type == 'numeric'
                                                || fields[i].type == 'integer'
                                                )
                                        )
                                    _persistent_text_fields.push({value: fields[i].field, label: fields[i].name});

                                if (
                                        (fields[i].type == 'select' || fields[i].type == 'radio')
                                        )
                                    _persistent_select_fields.push({value: fields[i].field, label: fields[i].name});

                                if (
                                        (fields[i].type == 'checkboxes' || fields[i].type == 'checkbox')
                                        )
                                    _persistent_checkbox_fields.push({value: fields[i].field, label: fields[i].name});
                            }
                        }

                        //console.log('UpdatedFormFields');
                        //console.log(fields);

                        // update model
                        model
                                .set('[_persistent_mail_fields]', _persistent_mail_fields)
                                .set('[_persistent_user_id_fields]', _persistent_user_id_fields)
                                .set('[_persistent_text_fields]', _persistent_text_fields)
                                .set('[_persistent_select_fields]', _persistent_select_fields)
                                .set('[_all_persistent_meta_fields]', [].concat(
                                        _persistent_mail_fields,
                                        _persistent_user_id_fields,
                                        _persistent_text_fields,
                                        _persistent_select_fields,
                                        _persistent_checkbox_fields
                                        ))
                                // notify
                                .trigger('change');
                    }

                    // add custom events callbacks
                    utils.attach('cred.fieldsLoaded cred.insertField cred.insertScaffold', refreshFromFormFields);
                    utils.attach('cred.wizardFinished', function () {
                        // lets refresh the view
                        //view.trigger('change');
                        // let's force resize of CodeMirrors
                        for (codemirror in CodeMirrorEditors) {
                            if (codemirror == 'content') {
                                var $_metabox = $('#' + codemirror).closest('.postbox');
                                if (!$_metabox.hasClass('closed') && 'none' != $_metabox.css('display'))
                                    CodeMirrorEditors[codemirror].setSize($('#' + codemirror).width() + 18, $('#' + codemirror).height());
                            }
                        }

                        // trigger scroll event to fix toolbar buttons being shown in wrong spot on chrome.
                        var _scroll_y = jQuery(window).scrollTop();
                        if (_scroll_y === 0) {
                            $("html, body").animate({scrollTop: 1});
                        } else {
                            $("html, body").animate({scrollTop: 0});
                        }

                    });

                    // handle tooltips with pointers
                    $_post
                            .on('click', '.cred-tip-link .icon-question-sign', function (e) {
                                e.preventDefault();
                                e.stopPropagation();

                                var $this = $(this), $el = $this.parent();

                                if ($this.hasClass('active'))
                                {
                                    $this[0]._pointer && $this[0]._pointer.pointer('close');
                                    return;
                                }

                                $this.addClass('active');
                                // GUI framework handles pointers now
                                $this[0]._pointer = gui.Popups.pointer($el, {
                                    message: $($el.data('pointer-content')).html(),
                                    class: 'cred-pointer',
                                    callback: function () {
                                        //$this[0]._pointer=null;
                                        $this.removeClass('active');
                                    }
                                });
                            })
                            .on('click', '.cred_field_add_code', function (e) {
                                e.stopPropagation();
                                e.preventDefault();
                                var $el = $(this), $to = $($el.data('area')), v = $el.data('value');
                                utils.InsertAtCursor($to, v);
                                setTimeout(function () {
                                    $el.closest('.cred-popup-box').__hide();
                                    $el.closest('.cred-icon-button').css('z-index', 1);
                                }, 50);
                                return false;
                            })
                            .on('paste keyup change', '.js-test-notification-to', function (e) {
                                //e.preventDefault();
                                var $el = $(this), val = $el.val(), $but = $($el.data('sendbutton'));

                                if ('' == val)
                                {
                                    //$but.prop('disabled', true);
                                    $but.attr('disabled', 'disabled');
                                }
                                else
                                {
                                    //$but.prop('disabled', false);
                                    $but.removeAttr('disabled');
                                }
                            })
                            .on('click', '.js-send-test-notification', function (e) {

                                e.preventDefault();

                                var $el = $(this),
                                        xhr = null, notification = {}, data,
                                        to = $.trim($($el.data('addressfield')).val()),
                                        cancel = $($el.data('cancelbutton')),
                                        loader = $($el.data('loader')),
                                        resultsCont = $($el.data('results')).empty().hide(),
                                        form_id = $('#post_ID').val(), fromCancel = false
                                        ;

                                // nowhere to send
                                if ('' == to)
                                    return false;

                                var doFinish = function () {
                                    if (xhr)
                                    {
                                        xhr.abort();
                                        xhr = false;
                                    }
                                    cancel.unbind('click', doFinish);
                                    $el.removeAttr('disabled');
                                };

                                var editor_id = 'credmailbody' + $el.data('notification');
                                if (typeof (window.tinyMCE) == 'object' && window.tinyMCEPreInit.mceInit[editor_id])
                                {
                                    var editor = window.tinyMCE.get(editor_id);
                                    if (editor)
                                    {
                                        var content = editor.getContent();
                                        model.set('[notification][notifications][' + $el.data('notification') + '][mail][body]', content, true);
                                    }
                                }

                                notification = $.extend(notification, model.get('[notification][notifications][' + $el.data('notification') + ']'));
                                delete notification['event'];
                                notification['to']['type'] = ['specific_mail'];
                                notification['to']['specific_mail']['address'] = to;
                                //console.log(notification);
                                data = {
                                    'cred_test_notification_data': notification,
                                    'cred_form_id': form_id
                                };

                                // send it
                                //console.log('sending..');
                                cancel.unbind('click', doFinish).bind('click', doFinish);
                                $el.attr('disabled', 'disabled');
                                resultsCont.html('sending test notification to &quot;' + to + '&quot; ..').show();
                                loader.show();

                                xhr = $.ajax(self.route('/Forms/testNotification'), {
                                    data: data,
                                    dataType: 'json',
                                    type: 'POST',
                                    success: function (result) {
                                        if (result.error)
                                        {
                                            resultsCont.html('<div class="cred-error">' + result.error + '</div>');
                                        }
                                        else
                                        {
                                            resultsCont.html('<div class="cred-success">' + result.success + '</div>');
                                        }
                                        resultsCont.hide().fadeIn('slow');
                                        loader.hide();
                                        xhr = false;
                                        doFinish();
                                        //console.log(result);
                                    },
                                    error: function (xhr1/*, status, text*/) {
                                        loader.hide();
                                        resultsCont.empty().hide();

                                        gui.Popups.alert({
                                            message: 'AJAX Request failed!<br /><br />Response Code: ' + xhr1.status + '<br /><br />Response Message: ' + xhr1.responseText,
                                            class: 'cred-dialog'
                                        });

                                        xhr = false;
                                        doFinish();
                                    }
                                });
                            });

                    // handle Preview button
                    $('#cred-preview-button a').unbind('click').bind('click', function (event) {
                        event.preventDefault();
                        return aux.formPreview();
                    });


                    var _do_submit = function () {
                        if (self.doCheck())
                        {
                            var nbox = $('#crednotificationdiv');

                            // notification metabox is closed
                            if (nbox.hasClass('closed'))
                            {
                                // open it and save it
                                //nbox.children('.handlediv').trigger('click');
                                nbox.removeClass('closed');

                                // make view re-validate
                                view.forceValidation();
                                validateView();

                                //console.log(view.isValid);
                                if (view.isValid)
                                {
                                    // close it and save it
                                    //nbox.children('.handlediv').trigger('click');
                                    nbox.addClass('closed');
                                }

                                //return false;
                            }

                            if (!view.isValid)
                                $_post.append('<input style="display:none" type="hidden" id="_cred_form_not_valid" name="_cred[validation][fail]" value="1" />');
                            else
                                $('#_cred_form_not_valid').remove();
                            return true;
                        }
                        return false;
                    };

                    $_post.bind('submit', function (event) {
                        _do_submit();
                    });

                    /*
                     var popup_shows = false;
                     $_post.bind('submit', function (event) {
                     
                     var form_type = $('#cred_form_type').val();
                     var is_new_form = (form_type == 'new');
                     var not_sections_len = $(".cred_validation_section").length;
                     var agp_is_checked = $('#cred_autogenerate_password_scaffold');
                     agp_is_checked = agp_is_checked[0].checked;
                     
                     if (!popup_shows) {
                     popup_shows = true;
                     console.log(form_type);
                     if (form_type == 'new' &&
                     is_new_form && 
                     agp_is_checked &&
                     not_sections_len <= 0
                     ) {                          
                     
                     gui.Popups.confirm({
                     'class': 'cred-dialog',
                     'message': settings.locale.autogeneration_alert,
                     'buttons': [settings.locale.cancel_text, settings.locale.ok_text],
                     'callback': function (result) {
                     if (result == settings.locale.cancel_text)
                     {
                     popup_shows = false;
                     return false;
                     }                              
                     if (_do_submit()) {
                     $_post.submit();
                     }
                     }
                     });                        
                     return false;
                     }
                     }
                     
                     return _do_submit();
                     });   
                     */

                    var $cred_shortcodes_box = $('#cred-shortcodes-box');
                    // setup other interaction handlers and  initialize fields show/hide according to values
                    $cred_shortcodes_box.on('click', 'a.cred-fields-group-heading', function (e) {
                        e.stopPropagation();
                        e.preventDefault();
                        var thisinside = $(this).next();
                        $('#cred-shortcodes-box-inner .cred-accordeon-item').removeClass('cred-accordeon-item-active') // remove active class from
                        $(this).closest('.cred-accordeon-item').addClass('cred-accordeon-item-active') // add .active class to parent .cred-fields-group-heading;
                        $('#cred-shortcodes-box-inner .cred-accordeon-item-inside').not(thisinside).stop(false).slideUp('fast');
                        thisinside.stop(false).slideDown({duration: 'slow', easing: 'quintEaseOut'});
                    });

                    $cred_shortcodes_box.on('click', 'a.cred_field_add', function (e) {
                        e.stopPropagation();
                        e.preventDefault();
                        var el = $(this);
                        var data = el.data('field');
                        var shortcode;
                        // remove all popups
                        $_post.find('.additional_field_options_popup').remove();
                        if (data.slug == 'credform')
                        {
                            //shortcode='['+data.slug+']\n[/'+data.slug+']';
                            if ('minimal' == _credModel.get('[form][theme]')) // bypass script and other styles added to form, minimal
                                shortcode = "[" + data.slug + " class='cred-form cred-keep-original']\n[/" + data.slug + "]";
                            else
                                shortcode = "[" + data.slug + " class='cred-form']\n[/" + data.slug + "]";
                        }
                        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/189585510/comments
                        //hide of additional maxwidth maxheight of image type field
                        else if (data['type'] == '__image' /*&& data['type']!='file'*/)
                        {
                            // load template
                            $($('#cred_image_dimensions_validation_template').html()).appendTo('#cred-shortcodes-box');
                            $('#cred_image_dimensions_validation').__show();
                            $('#cred_image_dimensions_cancel_button').unbind('click').click(function (event) {
                                event.stopPropagation();
                                event.preventDefault();
                                setTimeout(function () {
                                    $('#cred_image_dimensions_validation').__hide();
                                }, 50);
                            });
                            $('#cred_image_dimensions_validation_button').unbind('click').click(function (event) {
                                event.stopPropagation();
                                event.preventDefault();

                                var max_width = parseInt($.trim($('#cred_max_width').val()), 10);
                                var max_height = parseInt($.trim($('#cred_max_height').val()), 10);
                                shortcode = aux.shortcode(data, {max_width: max_width, max_height: max_height});
                                utils.InsertAtCursor($('#content'), shortcode);
                                utils.dispatch('cred.insertField');
                                setTimeout(function () {
                                    $('#cred-shortcodes-box').__hide();
                                    $('#cred-shortcode-button').css('z-index', 1);
                                }, 50);

                            });
                            return false;
                        }
                        // fields can have, placeholder, readonly and escape attributes
                        else if (data['type'] == 'textfield' ||
                                data['type'] == 'textarea' ||
                                data['type'] == 'wysiwyg' ||
                                data['type'] == 'date' ||
                                data['type'] == 'url' ||
                                data['type'] == 'phone' ||
                                data['type'] == 'numeric' ||
                                data['type'] == 'email')
                        {
                            // load template
                            $($('#cred_text_extra_options_template').html()).appendTo('#cred-shortcodes-box');
                            $('#cred_text_extra_options').__show();
                            $('#cred_text_extra_options_cancel_button').unbind('click').click(function (event) {
                                event.stopPropagation();
                                event.preventDefault();
                                setTimeout(function () {
                                    $('#cred_text_extra_options').__hide();
                                }, 50);
                            });
                            $('#cred_text_extra_options_button').unbind('click').click(function (event) {
                                event.stopPropagation();
                                event.preventDefault();

                                var placeholder = $.trim($('#cred_text_extra_placeholder').val());
                                var readonly = $('#cred_text_extra_readonly').is(':checked');
                                var escape = false; //$('#cred_text_extra_escape').is(':checked');
                                shortcode = aux.shortcode(data, {placeholder: placeholder, readonly: readonly, escape: escape});
                                utils.InsertAtCursor($('#content'), shortcode);
                                utils.dispatch('cred.insertField');
                                setTimeout(function () {
                                    $('#cred-shortcodes-box').__hide();
                                    $('#cred-shortcode-button').css('z-index', 1);
                                }, 50);

                            });
                            return false;
                        }
                        else if (data.is_parent)
                        {
                            // load template
                            $($('#cred_parent_field_settings_template').html()).appendTo('#cred-shortcodes-box');
                            $('#cred_parent_field_settings #cred_parent_required').unbind('change').bind('change', function () {
                                if ($(this).is(':checked'))
                                {
                                    $('#cred_parent_field_settings #cred_parent_select_text_container').stop(true).slideFadeDown('fast');
                                    $('#cred_parent_field_settings #cred_parent_no_parent_container').stop(true).slideFadeUp('fast');
                                }
                                else
                                {
                                    $('#cred_parent_field_settings #cred_parent_select_text_container').stop(true).slideFadeUp('fast');
                                    $('#cred_parent_field_settings #cred_parent_no_parent_container').stop(true).slideFadeDown('fast');
                                }
                            });

                            // set default values
                            $('#cred_parent_select_text').val('--- Select ' + data.data.post_type + ' ---');
                            $('#cred_parent_validation_text').val(data.data.post_type + ' must be selected');
                            $('#cred_parent_no_parent_text').val('No Parent');

                            setTimeout(function () {
                                $('#cred_parent_field_settings #cred_parent_required').trigger('change');
                            }, 50);

                            $('#cred_parent_field_settings').__show();
                            $('#cred_parent_extra_cancel_button').unbind('click').click(function (event) {
                                event.stopPropagation();
                                event.preventDefault();
                                setTimeout(function () {
                                    $('#cred_parent_field_settings').__hide();
                                }, 50);
                            });
                            $('#cred_parent_extra_button').unbind('click').click(function (event) {
                                event.stopPropagation();
                                event.preventDefault();

                                var parent_order = $('#cred_parent_order_by').val();
                                var parent_ordering = $('#cred_parent_ordering').val();
                                var parent_max_results = parseInt($.trim($('#cred_parent_max_results').val()), 10);
                                var required = $('#cred_parent_required').is(':checked');
                                var no_parent_text = $('#cred_parent_no_parent_text').val();
                                var select_parent_text = $('#cred_parent_select_text').val();
                                var validate_parent_text = $('#cred_parent_validation_text').val();
                                shortcode = aux.shortcode(data, {parent_order: parent_order, parent_ordering: parent_ordering, parent_max_results: parent_max_results, required: required, no_parent_text: no_parent_text, select_parent_text: select_parent_text, validate_parent_text: validate_parent_text});
                                utils.InsertAtCursor($('#content'), shortcode);
                                utils.dispatch('cred.insertField');
                                setTimeout(function () {
                                    $('#cred-shortcodes-box').__hide();
                                    $('#cred-shortcode-button').css('z-index', 1);
                                }, 50);

                            });
                            return false;
                        }
                        else
                            shortcode = aux.shortcode(data);
                        utils.InsertAtCursor($('#content'), shortcode);
                        utils.dispatch('cred.insertField');
                        setTimeout(function () {
                            $('#cred-shortcodes-box').__hide();
                            $('#cred-shortcode-button').css('z-index', 1);
                        }, 50);
                    });

                    //If i click on close i set original situation on checkboxes
                    $('#cred-scaffold-box').on('click', '#cred-popup-cancel', function (e) {
                        $("#cred_autogenerate_username_scaffold").prop("checked", _original_cred_autogenerate_username_scaffold);
                        $("#cred_autogenerate_nickname_scaffold").prop("checked", _original_cred_autogenerate_nickname_scaffold);
                        $("#cred_autogenerate_password_scaffold").prop("checked", _original_cred_autogenerate_password_scaffold);
                    });

                    $('#cred-scaffold-box').on('click', '#cred-popup-cancel2', function (e) {
                        $("#cred_autogenerate_username_scaffold").prop("checked", _original_cred_autogenerate_username_scaffold);
                        $("#cred_autogenerate_nickname_scaffold").prop("checked", _original_cred_autogenerate_nickname_scaffold);
                        $("#cred_autogenerate_password_scaffold").prop("checked", _original_cred_autogenerate_password_scaffold);
                    });

                    $('#cred-scaffold-box').on('click', '#cred-scaffold-insert', function (e) {
                        e.stopPropagation();
                        e.preventDefault();
                        var scaffold = $('#cred-scaffold-area').val();

                        utils.InsertAtCursor($('#content'), scaffold);
                        utils.dispatch('cred.insertScaffold');
                        setTimeout(function () {
                            $('#cred-scaffold-box').__hide();
                            $('#cred-scaffold-button').css('z-index', 1);
                        }, 50);
                    });

                    $_post.on('click', '#cred-shortcode-button-button', function (e) {
                        e.stopPropagation();
                        e.preventDefault();
                        /*cred_media_buttons*/$_post.find('.cred-media-button').css('z-index', 1);
                        /*cred_popup_boxes*/$_post.find('.cred-popup-box').hide();

                        $(this).closest('.cred-media-button').css('z-index', 100);
                        $_post.find('.additional_field_options_popup').hide();
                        $('#cred-shortcodes-box').__show();
                        if ($('.cred-accordeon-item-active').length === 0) { // make fist accordion item active but only if there was no accordion item opened before. It makes last opened accordeon item opened.
                            $('.cred-accordeon-item:first-child').addClass('cred-accordeon-item-active').find('.cred-accordeon-item-inside').stop().slideDown('fast');
                        }

                    });

                    $_post.on('click', '#cred-generic-shortcode-button-button', function (e) {
                        e.stopPropagation();
                        e.preventDefault();
                        /*cred_media_buttons*/$_post.find('.cred-media-button').css('z-index', 1);
                        /*cred_popup_boxes*/$_post.find('.cred-popup-box').hide();

                        $(this).closest('.cred-media-button').css('z-index', 100);
                        $('#cred-generic-shortcodes-box').__show();
                    });

                    // re-generate scaffold when this option changes
                    $('#cred_include_captcha_scaffold').change(function () {
                        aux.genScaffold();
                    });
                    $('#cred_include_wpml_scaffold').change(function () {
                        aux.genScaffold();
                    });

                    $('.cred_autogenerate_scaffold').change(function () {
                        var role = $('#cred_form_user_role').val();
                        console.log(role);
                        aux.reloadUserFields(role);
                    });

                    $_post.on('click', '#cred-scaffold-button-button', function (e) {
                        e.stopPropagation();
                        e.preventDefault();
                        /*cred_media_buttons*/$_post.find('.cred-media-button').css('z-index', 1);
                        /*cred_popup_boxes*/$_post.find('.cred-popup-box').hide();

                        if (!aux.genScaffold())
                            return false;

                        $(this).closest('.cred-media-button').css('z-index', 100);
                        $('#cred-scaffold-box').__show();
                    });

                    $_post.on('click', '#cred-user-scaffold-button-button', function (e) {
                        e.stopPropagation();
                        e.preventDefault();
                        /*cred_media_buttons*/$_post.find('.cred-media-button').css('z-index', 1);
                        /*cred_popup_boxes*/$_post.find('.cred-popup-box').hide();

                        if (!aux.genUserScaffold())
                            return false;

                        $(this).closest('.cred-media-button').css('z-index', 100);
                        $('#cred-scaffold-box').__show();

                        aux.update_autogeneration_fields();
                    });

                    $_post.on('click', '.cred-icon-button', function (e) {
                        e.stopPropagation();
                        e.preventDefault();
                        /*cred_media_buttons*/$_post.find('.cred-media-button').css('z-index', 1);
                        /*cred_popup_boxes*/$_post.find('.cred-popup-box').hide();

                        $(this).closest('.cred-media-button').css('z-index', 100);
                        $(this).next('.cred-popup-box').__show();
                    });

                    $(document).mouseup(function (e) {
                        if (
                                /*cred_popup_boxes*/$_post.find('.cred-popup-box').filter(function () {
                            return $(this).is(':visible');
                        }).has(e.target).length === 0
                                )
                        {
                            /*cred_media_buttons*/$_post.find('.cred-media-button').css('z-index', 1);
                            /*cred_popup_boxes*/$_post.find('.cred-popup-box').hide();
                        }
                    });

                    $(document).keyup(function (e) {
                        if (e.keyCode == KEYCODE_ESC)
                        {
                            /*cred_media_buttons*/$_post.find('.cred-media-button').css('z-index', 1);
                            /*cred_popup_boxes*/$_post.find('.cred-popup-box').hide();
                        }
                    });


                    // cancel buttons
                    $_post.on('click', '.cred-cred-cancel-close', function (e) {
                        /*cred_media_buttons*/$_post.find('.cred-media-button').css('z-index', 1);
                        /*cred_popup_boxes*/$_post.find('.cred-popup-box').hide();
                    });

                    // chain it
                    return this;
                }
            });
            settingsPage = settings.settingsurl;
            //cred_media_buttons=$('.cred-media-button');
            //cred_popup_boxes=$('.cred-popup-box');

            /*
             *
             *  ===================== init layout ================================
             *
             */

            // add explain texts for title and content
            $('#titlediv')
                    .prepend('<p class="cred-explain-text">' + settings.locale.title_explain_text + '</p>')
                    .prepend('<a id="cred_add_forms_to_site_help" class="cred-help-link" style="position:absolute;top:0;right:0;" href="' + settings.help['add_forms_to_site']['link'] + '" target="_blank" title="' + settings.help['add_forms_to_site']['text'] + '">' + settings.help['add_forms_to_site']['text'] + '</a>');

            postdivrich
                    .prepend('<p class="cred-explain-text">' + settings.locale.content_explain_text + '</p>');

            $_post
                    .append('<input id="cred-submit" type="submit" class="button button-primary button-large" value="' + settings.locale.submit_but + '" />');

            // reduce FOUC a bit
            // re-arrange meta boxes
            var pdro = postdivrich.detach().appendTo('#credformcontentdiv .inside');
            if (extradiv.length > 0)
            {
                extradiv.insertAfter(postdivrich);
                extradiv.addClass('cred-exclude');
            }
            $_post.find('.cred_related').removeClass('hide-if-js');

            // hide some stuff
            loader.hide();
            if (
                    !$('#postbox-container-1').find('.cred_related').length
                    )
            {
                // if not module manager sidebar meta box exists
                $('#postbox-container-1').hide();
                $('#post-body').removeClass('columns-2').addClass('columns-1');
                $('#poststuff').removeClass('has-right-sidebar');
                $('#poststuff .inner-sidebar').hide();
            }

            // enable codemirror for main area
            var text_button = utils.addEditorSwitchButton(settings.locale.text_button_title).addClass('switch-text').click(aux.toggleHighlight);
            var syntax_button = utils.addEditorSwitchButton(settings.locale.syntax_button_title).addClass('switch-syntax').click(aux.toggleHighlight);
            if (useCodeMirror)
            {
                utils.waitUntilElement('#ed_toolbar .ed_button', function () {
                    syntax_button.trigger('click');
                    aux.makeResizable($('#content'));
                });

                // for CodeMirror compability with Wordpress 'send_to_editor' function
                // keep original function as 'cred_send_to_editor' for use if not CodeMirror editor
                if (undefined === window.cred_send_to_editor) {
                    window.cred_send_to_editor = window.send_to_editor;
                    window.send_to_editor = function (content) {
                        try {
                            if (wpActiveEditor) {
                                var cm = utils.isCodeMirror($('#' + wpActiveEditor));
                                if (cm) {
                                    utils.InsertAtCursor($('#' + wpActiveEditor), content.replace(/%%NL%%/g, NL));
                                    try {
                                        tb_remove();
                                    } catch (e) {
                                    }
                                    ;
                                    return;
                                }
                            }
                        } catch (e) {
                        }
                        // if not used for CodeMirror, execute Wordpress standard function
                        cred_send_to_editor(content);
                    }
                }
            }
            else
            {
                utils.waitUntilElement('#ed_toolbar .ed_button', function () {
                    text_button.trigger('click');
                    aux.makeResizable($('#content'));
                });
            }

            // enable CodeMirror for CSS/JS Editors
            aux.enableExtraCodeMirror();
            if (extradiv.length > 0)
            {
                if ($('#cred-extra-css-editor').hasClass('cred-always-open') || $('#cred-extra-js-editor').hasClass('cred-always-open')) {
                    extradiv.removeClass('closed');
                } else {
                    // Delay hiding the section. Otherwise the codemirror
                    // control doesn't initialize correctly sometimes.
                    $(document).ready(function () {
                        _.delay(function () {
                            extradiv.addClass('closed');
                        }, 1000);
                    });
                }
            }

            /*
             *
             *  ===================== init user interaction/bindings ================================
             *
             */
            // init View to handle user interaction and bindings
            credView
                    .init()
                    .autobind(true)                      // autobind input fields, with model keys as names, to model
                    .bind(['change', 'click'], '#post')  // bind view to 'change' and 'click' events to elements under '#post'
                    .sync();                             // synchronize view to model

            // trigger the ajax now
            _credModel.trigger('change', {key: '[post][post_type]', value: _credModel.get('[post][post_type]')});

            doinit = false;
        }
    };

    // make public methods/properties available
    window.cred_cred = self;

})(window, jQuery, cred_settings, cred_utils, cred_gui, cred_mvc);

function cred_field_add_code_buttons() {
    jQuery('.cred_field_add_code').each(function (index) {
        if (jQuery(this).attr('data-value') == '%%USER_PASSWORD%%')
            jQuery(this).hide();
        else
            jQuery(this).show();
        //console.log(index + ": " + jQuery(this).attr('data-value'));
    });
}

function check_cred_form_type(aux) {
    var is_user_form = jQuery('#cred_form_user_role').length > 0;
    jQuery('.cred_field_add_code').hide();
    //console.log(jQuery('#cred_form_type'));
    if (jQuery('#cred_form_type')) {

        if (is_user_form) {
            jQuery('#cred_form_type').change(function () {
                var role = jQuery('#cred_form_user_role').val();
                if (role == null)
                    role = "";
                console.log(role);
                aux.reloadUserFields(role);
            });
        }

        if ('new' == jQuery('#cred_form_type').val()) {
            jQuery('.cred_field_add_code').show();
            //console.log("is new");
            jQuery('.cred_notification_field_only_if_changed input[type=checkbox]').attr('disabled', 'disabled');
            jQuery('.cred_notification_field_only_if_changed').hide();
            if (jQuery('.when_submitting_form_text').length) {
                jQuery('.when_submitting_form_text').html('When a new user is created by this form');
            }

            if (is_user_form) {
                jQuery('#cred_form_user_role').change(function () {
                    var role = jQuery(this).val();
                    console.log(role);
                    aux.reloadUserFields(role);
                });
            }

        } else {
            cred_field_add_code_buttons();
            //console.log("is edit");
            jQuery('.cred_notification_field_only_if_changed').show();
            jQuery('.cred_notification_field_only_if_changed input[type=checkbox]').removeAttr('disabled');
            if (jQuery('.when_submitting_form_text').length) {
                jQuery('.when_submitting_form_text').html('When a new user is updated by this form');
            }

            if (is_user_form) {
                aux.reloadUserFields("");
                jQuery('#cred_form_user_role').change(function () {
                });
            }
        }
    }
}

// When clicking on first-level buttons on the submit message editor, set the wpcfActiveEditor to the right textarea

jQuery(document).on('click', '#wp-credformactionmessage-media-buttons > .button ', function () {
    window.wpcfActiveEditor = 'credformactionmessage';
});
jQuery(document).on('click', '.wp-media-buttons > span.button ', function () {
    var data_editor = jQuery(this).parent('div').attr("data-editor");
    if (data_editor == undefined) {
        var id = jQuery(this).parent("div").attr('id');
        var spt = id.split('-');
        data_editor = spt[1];
    }
    window.wpcfActiveEditor = data_editor;
});