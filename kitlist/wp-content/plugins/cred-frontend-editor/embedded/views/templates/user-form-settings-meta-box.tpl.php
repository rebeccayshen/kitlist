<?php if (!defined('ABSPATH')) die('Security check'); ?>
<?php
$settings = CRED_Helper::mergeArrays(array(
            'type' => 'new',
            'action' => 'form',
            'user_role' => '',
            'action_page' => '',
            'action_message' => '',
            'redirect_delay' => 0,
            'hide_comments' => 0,
            'theme' => 'minimal',
            'has_media_button' => 0,
            'include_wpml_scaffold' => 0,
            'include_captcha_scaffold' => 0
                ), (array) $settings);
?>
<fieldset class="cred-fieldset">
    <h4><?php _e('Basic Settings', 'wp-cred'); ?></h4>

    <?php wp_nonce_field('cred-admin-post-page-action', 'cred-admin-post-page-field'); ?>

    <p class='cred-explain-text'><?php _e('Forms can create new user or edit existing user. Choose what this form will do:', 'wp-cred'); ?></p>
    <select id="cred_form_type" name="_cred[form][type]">
        <?php
        $form_types = apply_filters('cred_admin_form_type_options', array(
            "new" => __('Create a user', 'wp-cred'),
            "edit" => __('Edit a user', 'wp-cred')
                ), $settings['type'], $form);
        foreach ($form_types as $_v => $_l) {
            if ($settings['type'] == $_v) {
                ?><option value="<?php echo $_v; ?>" selected="selected"><?php echo $_l; ?></option><?php
            } else {
                ?><option value="<?php echo $_v; ?>"><?php echo $_l; ?></option><?php
            }
        }
        ?>
    </select>
    
<div style="position:relative">
    <p class='cred-explain-text'><?php _e('Select allowed User Role', 'wp-cred'); ?>:</p>

    <?php
    $settings['user_role'] = json_decode($settings['user_role'], true);
    foreach ($user_roles as $k => $v) {
        ?><p class="roles_checkboxes" style="margin-left:10px;"><?php
        ?><input class="roles_checkboxes" id="role_<?php echo $k; ?>" type="checkbox" name="_cred[form][user_role][]" value="<?php echo $k; ?>"><?php echo $v['name']; ?><?php
        ?></p><?php
    }
    ?>
    </div>

    <select class="roles_selectbox" id="cred_form_user_role" name="_cred[form][user_role][]">
        <?php
        foreach ($user_roles as $k => $v) {
            ?><option value="<?php echo $k; ?>"><?php echo $v['name']; ?></option><?php
        }
        ?>
    </select>   
    
    <script>
        function role_switch() {
            if (jQuery("#cred_form_type").val() == 'edit') {
                jQuery(".roles_checkboxes").prop("disabled", false);
                jQuery(".roles_checkboxes").show();
                jQuery(".roles_selectbox").prop("disabled", true);
                jQuery(".roles_selectbox").hide();
            } else {
                jQuery(".roles_checkboxes").prop("disabled", true);
                jQuery(".roles_checkboxes").hide();
                jQuery(".roles_selectbox").prop("disabled", false);
                jQuery(".roles_selectbox").show();
            }
        }

        jQuery("#cred_form_type").on("change", function () {
            role_switch();
        });

        jQuery(document).ready(function () {
            role_switch();
<?php
foreach ($user_roles as $k => $v) {
    if ($settings['type'] == 'edit')
        echo (is_array($settings['user_role']) && in_array($k, $settings['user_role'])) ? "jQuery('#role_{$k}').prop( 'checked', true );" : "";
    else
        echo ((!is_array($settings['user_role']) && $settings['user_role'] == $k) ||
        (is_array($settings['user_role']) && in_array($k, $settings['user_role']))) ? "jQuery('#cred_form_user_role option[value={$k}]').prop('selected','selected');" : "";
}
?>
        });
    </script>     

    <p class='cred-explain-text'><?php _e('Choose what to do after visitors submit this form:', 'wp-cred'); ?></p>
    <select id="cred_form_success_action" name="_cred[form][action]">
        <?php
        $form_actions = apply_filters('cred_admin_submit_action_options', array(
            "form" => __('Keep displaying this form', 'wp-cred'),
            "message" => __('Display a message instead of the form...', 'wp-cred'),
            "post" => __('Display the user', 'wp-cred'),
            "page" => __('Go to a page...', 'wp-cred')
                ), $settings['action'], $form);
        foreach ($form_actions as $_v => $_l) {
            if ($settings['action'] == $_v) {
                ?><option value="<?php echo $_v; ?>" selected="selected"><?php echo $_l; ?></option><?php
            } else {
                ?><option value="<?php echo $_v; ?>"><?php echo $_l; ?></option><?php
            }
        }
        ?>
    </select>

    <span data-cred-bind="{ action: 'show', condition: '_cred[form][action]=page' }">
        <select id="cred_form_success_action_page" name="_cred[form][action_page]">
            <optgroup label="<?php echo esc_attr(__('Please Select..', 'wp-cred')); ?>">
                <?php echo $form_action_pages; ?>
            </optgroup>
        </select>
    </span>

    <span data-cred-bind="{ action: 'show', condition: '_cred[form][action] in [post,page]' }">
        <?php _e('Redirect delay (in seconds)', 'wp-cred'); ?>
        <input type='text' size='3' id='cred_form_redirect_delay' name='_cred[form][redirect_delay]' value='<?php echo esc_attr($settings['redirect_delay']); ?>' />
    </span>        

    <div data-cred-bind="{ action: 'fadeSlide', condition: '_cred[form][action]=message' }">
        <p class='cred-explain-text'><?php _e('Enter the message to display instead of the form. You can use HTML and shortcodes.', 'wp-cred'); ?></p>
        <?php echo CRED_Helper::getRichEditor('credformactionmessage', '_cred[form][action_message]', $settings['action_message'], array('wpautop' => true, 'teeny' => true, 'editor_height' => 200, 'editor_class' => 'wpcf-wysiwyg')); ?>
        <!--<textarea id='cred_form_action_message' name='_cred[form][action_message]' style="position:relative; width:95%;"><?php //echo esc_textarea($settings['action_message']);                ?></textarea>-->
        <!-- correct initial value -->
        <script type='text/javascript'>
            /* <![CDATA[ */
            (function (window, $, undefined) {
                $(function () {
                    try {
                        $('#credformactionmessage').val($('#credformactionmessage').text());
                    } catch (e) {
                    }
                });
            })(window, jQuery);
            /* ]]> */
        </script>
    </div>             

</fieldset>

<a class='cred-help-link' href='<?php echo $help['general_form_settings']['link']; ?>' target='<?php echo $help_target; ?>' title="<?php echo esc_attr($help['general_form_settings']['text']); ?>" style="position:absolute;top:5px;right:10px">
    <i class="icon-question-sign"></i>
    <span><?php echo $help['general_form_settings']['text']; ?></span>
</a>
