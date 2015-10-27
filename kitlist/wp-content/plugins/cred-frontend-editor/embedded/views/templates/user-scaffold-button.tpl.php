<?php if (!defined('ABSPATH')) die('Security check'); ?>
<span id="cred-scaffold-button" class="cred-media-button">
    <a href='javascript:;' id="cred-user-scaffold-button-button" class='button cred-button' title='<?php echo esc_attr(__('Auto-Generate Form', 'wp-cred')); ?>'>
        <i class="icon-cred-logo ont-icon-18"></i><?php _e('Auto-Generate User Form', 'wp-cred'); ?></a>
    <div id="cred-scaffold-box" class="cred-popup-box">
        <div class='cred-popup-heading'>
            <h3><?php _e('Auto-generate Form Content', 'wp-cred'); ?></h3>
            <i title='<?php echo esc_attr(__('Close', 'wp-cred')); ?>' class='icon-remove cred-close-button cred-cred-cancel-close'></i>
        </div>
        <div id="cred-scaffold-box-inner" class="cred-popup-inner">
            <textarea id="cred-scaffold-area" rows=8>
            </textarea>
            <p>
                <?php _e("This auto-generator includes inputs for all the fields that belong to the form's user role.", 'wp-cred'); ?>
                <?php _e("After inserting it, you can style and edit the form content using the editor.", 'wp-cred'); ?>
            </p>
            <p>
                <strong><?php _e("Tip:", 'wp-cred'); ?></strong>
                <?php _e("Make a selection in the editor and auto-generator will replace it.", 'wp-cred'); ?>
            </p>
            <ul>
                <li>
                    <label class='cred-label'>
                        <input type='checkbox' class='cred_autogenerate_scaffold' name='_cred[form][autogenerate_username_scaffold]' id='cred_autogenerate_username_scaffold' value='1' <?php if (!isset($autogenerate_username_scaffold)) echo 'checked="checked"'; else echo ($autogenerate_username_scaffold==true) ? 'checked="checked"' : ''; ?> /><span class='cred-checkbox-replace'></span>
                        <span><?php _e('Autogenerate Username', 'wp-cred'); ?></span>
                    </label>
                </li>
                <li>
                    <label class='cred-label'>
                        <input type='checkbox' class='cred_autogenerate_scaffold' name='_cred[form][autogenerate_nickname_scaffold]' id='cred_autogenerate_nickname_scaffold' value='1' <?php if (!isset($autogenerate_nickname_scaffold)) echo 'checked="checked"'; else echo ($autogenerate_nickname_scaffold==true) ? 'checked="checked"' : ''; ?> /><span class='cred-checkbox-replace'></span>
                        <span><?php _e('Autogenerate Nickname', 'wp-cred'); ?></span>
                    </label>
                </li>
                <li>
                    <label class='cred-label'>
                        <input type='checkbox' class='cred_autogenerate_scaffold' name='_cred[form][autogenerate_password_scaffold]' id='cred_autogenerate_password_scaffold' value='1' <?php if (!isset($autogenerate_password_scaffold)) echo 'checked="checked"'; else echo ($autogenerate_password_scaffold==true) ? 'checked="checked"' : ''; ?> /><span class='cred-checkbox-replace'></span>
                        <span><?php _e('Autogenerate Password', 'wp-cred'); ?></span>
                    </label>
                </li>
                <li>
                    <label class='cred-label'>
                        <input type='checkbox' class='cred-checkbox-10' name='_cred[form][include_captcha_scaffold]' id='cred_include_captcha_scaffold' value='1' <?php if (isset($include_captcha_scaffold) && $include_captcha_scaffold) echo 'checked="checked"'; ?> />
                        <span><?php _e('Include reCaptcha field', 'wp-cred'); ?></span>
                    </label>
                </li>
                <li>
                    <label class='cred-label'>
                        <input type='checkbox' class='cred-checkbox-10' name='_cred[form][include_wpml_scaffold]' id='cred_include_wpml_scaffold' value='1' <?php if (isset($include_wpml_scaffold) && $include_wpml_scaffold) echo 'checked="checked"'; ?> /><span class='cred-checkbox-replace'></span>
                        <span><?php _e('Include WPML localization', 'wp-cred'); ?></span>
                    </label>
                </li>

                <?php echo apply_filters('cred_wpml_glue_generate_insert_original_button', false); ?>

            </ul>

        </div>

        <p class="cred-scaffold-buttons-holder cred-buttons-holder">
            <a id="cred-popup-cancel" class="button cred-cred-cancel-close" href="javascript:;" title="<?php echo esc_attr(__('Cancel', 'wp-cred')); ?>"><?php _e('Cancel', 'wp-cred'); ?></a>
            <a id="cred-scaffold-insert" class="button-primary" href="javascript:;" title="<?php echo esc_attr(__('Insert', 'wp-cred')); ?>"><?php _e('Insert', 'wp-cred'); ?></a>
        </p>

        <a class='cred-help-link' href='<?php echo $help['scaffold_settings']['link']; ?>' target='<?php echo $help_target; ?>'  title="<?php echo esc_attr($help['scaffold_settings']['text']); ?>">
            <i class="icon-question-sign"></i>
            <span><?php echo $help['scaffold_settings']['text']; ?></span>
        </a>

    </div>
</span>
<span style='display:inline-block' id="cred_ajax_loader_small_id" class='cred_ajax_loader_small'></span>