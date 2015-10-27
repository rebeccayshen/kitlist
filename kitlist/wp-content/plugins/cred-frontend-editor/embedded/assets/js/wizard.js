(function (window, $, settings, cred, gui, undefined)
{
    // private methods/properties
    var self, edit_url = cred.settings.editurl, form_controller_url = cred.settings.form_controller_url, wizard_url = cred.settings.wizard_url, _cred_wpnonce = cred.settings._cred_wpnonce, newform;
    var _current_page = cred.settings._current_page;

    function submithandler(event)
    {
        event.preventDefault();
        var post = $(this);
        var form_id = $('#post_ID').val();
        if (cred.doCheck(self.step))
        {
            $.ajax({
                url: cred.route(form_controller_url),
                data: 'form_id=' + form_id + '&field=wizard' + '&value=' + self.completed_step + '&_wpnonce=' + _cred_wpnonce,
                type: 'post',
                success: function (result) {
                    post.unbind('submit', submithandler);
                    post.submit();
                    return true;
                }
            });
        }
        return false;
    }
    ;

    function serialize(what)
    {
        var values, index, newvals = cred.getContents();

        // Get the parameters as an array
        values = $(what).serializeArray();

        // Find and replace `content` if there
        for (index = 0; index < values.length; ++index)
        {
            if (newvals[values[index].name])
            {
                values[index].value = newvals[values[index].name];
            }
        }
        // Convert to URL-encoded string
        return $.param(values);
    }
    ;

    function checkClassButton($button)
    {
        if ('disabled' == $button.attr('disabled'))
        {
            if ($button.hasClass('button-primary'))
            {
                $button.removeClass('button-primary').addClass('button-secondary');
            }
        }
        else
        {
            if ($button.hasClass('button-secondary'))
            {
                $button.removeClass('button-secondary').addClass('button-primary');
            }
        }
    }
    ;


    var wizardNextB, wizardPrevB, wizardQuitB, wizardProgressbar, wizardProgressbarInner;

    // public methods/properties
    self =
            {
                hasSidebar: false,
                step: 1,
                prevstep: 0,
                completed_step: 0,
                steps: [
                    // step 1
                    {
                        title: cred.settings.locale.step_1_title,
                        completed: false,
                        execute: function ()
                        {
                            // setup
                            $('#postbox-container-2 #normal-sortables, #post-body-content').children(':not(.cred-not-hide)').hide();
                            $('#titlediv').show();
                            wizardPrevB.hide();

                            if (!self.steps[self.step - 1].completed)
                            {
                                wizardNextB.attr('disabled', 'disabled');
                                checkClassButton(wizardNextB);
                                wizardProgressbarInner.css('width', '20%');
                                // keep checking
                                var _tim = setInterval(function () {
                                    if (!self.steps[self.step - 1].completed)
                                    {
                                        var $el = $('#title'), val = $.trim($el.val());
                                        if ('' != val)
                                        {
                                            clearInterval(_tim);
                                            self.steps[self.step - 1].completed = true;
                                            self.completed_step = self.step;
                                            wizardNextB.removeAttr('disabled');
                                            checkClassButton(wizardNextB);
                                        }
                                    }
                                    else
                                    {
                                        clearInterval(_tim);
                                    }
                                }, 500);
                            }
                            else
                            {
                                self.steps[self.step - 1].completed = true;
                                self.completed_step = self.step;
                                wizardNextB.removeAttr('disabled');
                                checkClassButton(wizardNextB);
                            }
                        }
                    },
                    // step 2
                    {
                        title: cred.settings.locale.step_2_title,
                        completed: false,
                        execute: function (prev)
                        {
                            // setup
                            $('#postbox-container-2 #normal-sortables, #post-body-content').children(':not(.cred-not-hide)').hide();
                            $('#credformtypediv').removeClass('closed').show();
                            wizardPrevB.show();

                            if (!self.steps[self.step - 1].completed)
                            {
                                wizardNextB.attr('disabled', 'disabled');
                                checkClassButton(wizardNextB);
                                wizardProgressbarInner.css('width', '40%');
                                // keep checking
                                var _tim = setInterval(function () {
                                    if (!self.steps[self.step - 1].completed)
                                    {
                                        var $el = $('select[name="_cred[form][type]"]'), val = $.trim($el.val());
                                        var is_user_form = $('#cred_form_user_role').length;
                                        
                                        if (is_user_form) {
                                            var $el2 = $('select[name="_cred[form][user_role][]"]'), val2 = $.trim($el2.val());
                                        }

                                        if (!is_user_form && '' != val || (is_user_form && '' != val && '' != val2 || ('' == val2 && val == 'edit')))
                                        {
                                            clearInterval(_tim);
                                            self.steps[self.step - 1].completed = true;
                                            self.completed_step = self.step;
                                            wizardNextB.removeAttr('disabled');
                                            checkClassButton(wizardNextB);
                                        }
                                    }
                                    else
                                    {
                                        clearInterval(_tim);
                                    }
                                }, 500);
                            }
                            else
                            {
                                self.steps[self.step - 1].completed = true;
                                self.completed_step = self.step;
                                wizardNextB.removeAttr('disabled');
                                checkClassButton(wizardNextB);
                            }
                        }
                    },
                    // step 3
                    {
                        title: cred.settings.locale.step_3_title,
                        completed: false,
                        execute: function (prev)
                        {
                            // setup
                            $('#postbox-container-2 #normal-sortables, #post-body-content').children(':not(.cred-not-hide)').hide();
                            $('#credposttypediv').removeClass('closed').show();
                            wizardPrevB.show();

                            if (!self.steps[self.step - 1].completed)
                            {
                                wizardNextB.attr('disabled', 'disabled');
                                checkClassButton(wizardNextB);
                                wizardProgressbarInner.css('width', '60%');
                                // keep checking
                                var _tim = setInterval(function () {
                                    if (!self.steps[self.step - 1].completed)
                                    {
                                        var $el = $('select[name="_cred[post][post_type]"]'), val = $.trim($el.val());
                                        if ('' != val)
                                        {
                                            clearInterval(_tim);
                                            self.steps[self.step - 1].completed = true;
                                            self.completed_step = self.step;
                                            wizardNextB.removeAttr('disabled');
                                            checkClassButton(wizardNextB);
                                        }
                                    }
                                    else
                                    {
                                        clearInterval(_tim);
                                    }
                                }, 500);
                            }
                            else
                            {
                                self.steps[self.step - 1].completed = true;
                                self.completed_step = self.step;
                                wizardNextB.removeAttr('disabled');
                                checkClassButton(wizardNextB);
                            }
                        }
                    },
                    // step 4
                    {
                        title: cred.settings.locale.step_4_title,
                        completed: false,
                        execute: function (prev)
                        {
                            $('#postbox-container-2 #normal-sortables, #post-body-content').children(':not(.cred-not-hide)').hide();
                            $('#credformcontentdiv').removeClass('closed').show();
                            wizardPrevB.show();
                            if (!self.steps[self.step - 1].completed)
                            {
                                wizardNextB.attr('disabled', 'disabled');
                                checkClassButton(wizardNextB);
                                wizardProgressbarInner.css('width', '80%');
                                // keep checking
                                var _tim = setInterval(function () {
                                    if (!self.steps[self.step - 1].completed)
                                    {
                                        var content = cred.getContents(), val = $.trim(content['content']);
                                        if ('' != val)
                                        {
                                            clearInterval(_tim);
                                            self.completed_step = self.step;
                                            self.steps[self.step - 1].completed = true;
                                            wizardNextB.removeAttr('disabled');
                                            checkClassButton(wizardNextB);
                                        }
                                    }
                                    else
                                    {
                                        clearInterval(_tim);
                                    }
                                }, 500);
                            }
                            else
                            {
                                self.steps[self.step - 1].completed = true;
                                self.completed_step = self.step;
                                wizardNextB.removeAttr('disabled');
                                checkClassButton(wizardNextB);
                            }
                        }
                    },
                    // step 5
                    {
                        title: cred.settings.locale.step_5_title,
                        completed: false,
                        execute: function (prev)
                        {
                            $('#postbox-container-2 #normal-sortables, #post-body-content').children(':not(.cred-not-hide)').hide();
                            $('#crednotificationdiv').removeClass('closed').show();
                            wizardPrevB.show();
                            // make this step optional
                            wizardNextB.removeAttr('disabled');
                            checkClassButton(wizardNextB);
                            self.completed_step = self.step;
                            self.steps[self.step - 1].completed = true;
                        }
                    }
                ],
                prevStep: function ()
                {
                    self.goToStep(self.step - 1);
                },
                nextStep: function ()
                {
                    if (newform && 1 == self.step && !self.steps[1].completed)
                    {
                        var form_id = $('#post_ID').val();
                        if (cred.doCheck(self.step))
                        {
                            $.ajax({
                                url: edit_url,
                                data: serialize('#post'),
                                type: 'post',
                                success: function (result) {
                                    $.ajax({
                                        url: cred.route(form_controller_url),
                                        data: 'form_id=' + form_id + '&field=wizard' + '&value=' + self.completed_step + '&_wpnonce=' + _cred_wpnonce,
                                        type: 'post',
                                        success: function (result) {
                                            document.location = edit_url + '?action=edit&post=' + form_id;
                                        }
                                    });
                                }
                            });
                        }
                    }
                    else
                    {
                        // save this step
                        if (cred.doCheck(self.step))
                        {
                            var wizard_step = self.completed_step;
                            if (self.completed_step == self.steps.length)
                                wizard_step = -1;
                            var action_message = '';
                            if (tinyMCE && tinyMCE.get('credformactionmessage') && tinyMCE.get('credformactionmessage').getContent() != '') {
                                action_message = '&_cred[form][action_message]=' + tinyMCE.get('credformactionmessage').getContent();
                            }
                            $.ajax({
                                url: document.location,
                                data: serialize('#post') + '&_cred[wizard]=' + wizard_step + action_message,
                                type: 'post',
                                success: function () {
                                }
                            });
                            self.goToStep(self.step + 1);
                        }
                    }
                },
                goToStep: function (step)
                {
                    if (undefined === step)
                        return;
                    step = parseInt(step, 10);
                    if (step <= self.steps.length + 1 && step >= 1)
                        self.step = step;
                    else
                        return;

                    if (self.step == self.steps.length + 1)
                    {
                        self.finish();
                        return;
                    }

                    if (self.steps[self.step - 1] && $.isFunction(self.steps[self.step - 1].execute))
                    {
                        if (self.step == self.steps.length)
                            wizardNextB.val(cred.settings.locale.finish_text).show();
                        else
                            wizardNextB.val(cred.settings.locale.next_text).show();
                        self.steps[self.step - 1].execute();
                        cred.app.dispatch('cred.wizardChangedStep');
                        return;
                    }
                },
                start: function () {
                    // setup
                    $('#postbox-container-2').append('<div class="cred-not-hide cred-wizard-buttons"> <input id="cred_wizard_quit_button" type="button" class="button" value="' + cred.settings.locale.quit_wizard_text + '" /> <input type="button" id="cred-wizard-button-prev" class="button" value="' + cred.settings.locale.prev_text + '" /> <input type="button" id="cred-wizard-button-next" class="button-primary" value="' + cred.settings.locale.next_text + '" /></div>');
                    // progress bar
                    $('#post-body-content').prepend('<div class="cred-not-hide cred-progress"><div id="cred-progress-bar"><div id="cred-progress-bar-inner"></div></div></div>');
                    if ($('#post-body').hasClass('columns-2'))
                    {
                        self.hasSidebar = true;
                        $('#postbox-container-1').hide();
                        $('#post-body').removeClass('columns-2').addClass('columns-1');
                    }
                    $('#cred-submit, #cred_add_forms_to_site_help').hide();
                    //$('#postbox-container-2 .postbox:not(.cred-not-hide)').hide();
                    $('#post').bind('submit', submithandler);

                    wizardNextB = $('#cred-wizard-button-next');
                    wizardPrevB = $('#cred-wizard-button-prev');
                    wizardQuitB = $('#cred_wizard_quit_button');
                    wizardProgressbar = $('#cred-progress-bar');
                    wizardProgressbarInner = $('#cred-progress-bar-inner');

                    for (var i = 0, l = self.steps.length; i < l; i++)
                    {
                        var progress_step = $('<span class="cred-progress-step">' + self.steps[i].title + '</span>');
                        progress_step.insertBefore(wizardProgressbar).css({'left': Math.floor(100 * (i + 1) / l) + '%', 'margin-left': -progress_step.width() + 'px'});
                    }

                    $('#post').on('click', '#cred_wizard_quit_button', function () {

                        form_id = $('#post_ID').val();

                        cred.gui.Popups.confirm({
                            'class': 'cred-dialog',
                            'message': cred.settings.locale.quit_wizard_confirm_text,
                            'buttons': [cred.settings.locale.quit_wizard_this_form, cred.settings.locale.quit_wizard_all_forms, cred.settings.locale.cancel_text],
                            'callback': function (result) {
                                if (result == cred.settings.locale.quit_wizard_all_forms)
                                {
                                    $.ajax({
                                        url: cred.route(wizard_url),
                                        type: 'POST',
                                        data: 'cred_wizard=false',
                                        dataType: 'html',
                                        success: function () {
                                        }
                                    });
                                }
                                if (result == cred.settings.locale.quit_wizard_this_form)
                                {
                                    $.ajax({
                                        url: cred.route(form_controller_url),
                                        data: 'form_id=' + form_id + '&field=wizard' + '&value=-1&_wpnonce=' + _cred_wpnonce,
                                        type: 'post',
                                        success: function () {
                                        }
                                    });
                                }
                                if (result == cred.settings.locale.quit_wizard_all_forms || result == cred.settings.locale.quit_wizard_this_form)
                                {
                                    $('#post').unbind('submit', submithandler);
                                    self.finish();
                                }
                            }
                        });
                    });

                    $('#post').on('click', '#cred-wizard-button-next', function () {
                        self.nextStep();
                    });
                    $('#post').on('click', '#cred-wizard-button-prev', function () {
                        self.prevStep();
                    });

                    // go
                    for (var i = 1; i <= self.step; i++)
                    {
                        if (self.steps[i - 1])
                            self.steps[i - 1].completed = true;
                    }
                    self.completed_step = self.step;
                    wizardProgressbarInner.css('width', (100 * self.step / self.steps.length) + '%');
                    if (self.step < self.steps.length)
                        self.goToStep(self.step + 1);
                },
                finish: function ()
                {
                    self.completed_step = -1;
                    $('#post').find('.cred-not-hide')/*.hide()*/.remove();
                    if (self.hasSidebar)
                    {
                        $('#post-body').removeClass('columns-1').addClass('columns-2');
                        $('#postbox-container-1').show();
                        self.hasSidebar = false;
                    }
                    $('#postbox-container-2 #normal-sortables, #post-body-content').children().show();
                    $('#cred-submit, #cred_add_forms_to_site_help, .cred_related').show();
                    if ($('#credformcontentdiv').hasClass('closed'))
                        $('h3.hndle', '#credformcontentdiv').triggerHandler('click');
                    cred.app.dispatch('cred.wizardFinished');
                },
                init: function (step, new_form)
                {
                    if (_current_page == 'cred-user-form') {
                        self.steps.splice(2, 1);
                    }
                    // save data
                    newform = new_form;
                    if (step >= 0)
                    {
                        self.step = step;
                        self.start();
                    }
                }
            }

    // make it publicly available
    window.cred_wizard = self;
})(window, jQuery, cred_settings, cred_cred, cred_gui);