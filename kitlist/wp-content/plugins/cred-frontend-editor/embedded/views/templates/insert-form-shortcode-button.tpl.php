<?php if (!defined('ABSPATH')) die('Security check'); ?>
<!-- span inline-block as container for IE :p -->
<span id="cred-form-shortcode-button" class="cred-media-button">

    <a href='javascript:;' id="cred-form-shortcode-button-button" class='button cred-icon-button' title='<?php echo esc_attr(__('Insert Forms', 'wp-cred')); ?>'>
        <i class="icon-cred-logo ont-icon-18"></i><?php echo __('CRED Forms', 'wp-cred'); ?></a>

    <div id="cred-form-shortcodes-box" class="cred-popup-box">

        <div class='cred-popup-heading'>
            <h3><?php _e('Choose which form to insert', 'wp-cred'); ?></h3>
            <i title='<?php echo esc_attr(__('Close', 'wp-cred')); ?>' class='icon-remove cred-close-button cred-cred-cancel-close'></i>
        </div>

        <?php //print_r($forms); ?>
        <div id="cred-form-shortcodes-box-inner" class="cred-popup-inner">
            <div id='cred-form-shortcode-types-select-container'>
                <table class='cred-table cred-table-choose-form' cellpadding=0 cellspacing=0>

                    <tr>
                        <td colspan=2>
                            <h3>CRED Post Forms</h3>
                        </td>
                    </tr>

                    <tr>
                        <td colspan=2>
                            <label class='cred-label'><input type='radio' class='cred-shortcode-container-radio cred-radio-10' name='cred-shortcode-container' id='cred-post-creation-container' value='1' />
                                <span><?php _e('New Post Form', 'wp-cred'); ?></span></label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=2>
                            <label class='cred-label'><input type='radio' class='cred-shortcode-container-radio cred-radio-10' name='cred-shortcode-container' id='cred-post-edit-container' value='2' />
                                <span><?php _e('Edit Post Form', 'wp-cred'); ?></span></label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=2>
                            <label class='cred-label'><input type='radio' class='cred-shortcode-container-radio cred-radio-10' name='cred-shortcode-container' id='cred-post-delete-link-container' value='3' />
                                <span><?php _e('Delete Post Link', 'wp-cred'); ?></span></label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=2>
                            <label class='cred-label'><input type='radio' class='cred-shortcode-container-radio cred-radio-10' name='cred-shortcode-container' id='cred-post-child-link-container' value='4' />
                                <span><?php _e('Create Child Post Link', 'wp-cred'); ?></span></label>
                        </td>
                    </tr>

                    <tr>
                        <td colspan=2>
                            <hr/>
                        </td>
                    </tr>

                    <tr>
                        <td colspan=2>
                            <h3>CRED User Forms</h3>
                        </td>
                    </tr>


                    <tr>
                        <td colspan=2>
                            <label class='cred-label'><input type='radio' class='cred-shortcode-container-radio cred-radio-10' name='cred-shortcode-container' id='cred-user-creation-container' value='5' />
                                <span><?php _e('New User Form', 'wp-cred'); ?></span></label>
                        </td>
                    </tr>

                    <tr>
                        <td colspan=2>
                            <label class='cred-label'><input type='radio' class='cred-shortcode-container-radio cred-radio-10' name='cred-shortcode-container' id='cred-user-edit-container' value='6' />
                                <span><?php _e('Edit User Form', 'wp-cred'); ?></span></label>
                        </td>
                    </tr>


                    <tr>
                        <td colspan=2>
                            <hr/>
                        </td>
                    </tr>
                </table>
            </div>


            <div id='_cred-user-edit-container' class='cred-shortcodes-container'>
                <table class='cred-table' cellpadding=0 cellspacing=0>
                    <tr>
                        <td>
                            <label for="cred_user_form-edit-shortcode-select"><?php _e('Select user form', 'wp-cred'); ?></label>
                            <select style='position:relative;' id="cred_user_form-edit-shortcode-select" name="cred_user_form-edit-shortcode-select">
                                <optgroup label="<?php echo esc_attr(__('Select user form', 'wp-cred')); ?>">
                                    <option value='' disabled selected style='display:none;'><?php _e('Select user form', 'wp-cred'); ?></option>

                                    <?php
                                    foreach ($user_forms as $form) {
                                        if (isset($form->meta->form['type']) && $form->meta->form['type'] == 'edit') {
                                            echo "<option class='_cred_cred_{$form->meta->post['post_type']}' value='{$form->ID}'>{$form->post_title}</option>";
                                        }
                                    }
                                    ?>
                                </optgroup>
                            </select>
                            <a class='cred-help-link' href='<?php echo $help['content_edit_shortcode_settings']['link']; ?>' target='<?php echo $help_target; ?>' title="<?php echo esc_attr($help['content_edit_shortcode_settings']['text']); ?>">
                                <i class="icon-question-sign"></i>
                                <span><?php echo $help['content_edit_shortcode_settings']['text']; ?></span>
                            </a>
                        </td>
                    <a href='javascript:;' class='cred-show-hide-advanced'></a>
                    </tr>
                </table>

                <div id='cred-user-edit-container-advanced' class='cred-shortcodes-container-advanced'>
                    <fieldset class='cred-fieldset'>
                        <legend><b><?php _e('What to edit', 'wp-cred'); ?></b></legend>
                        <table class='cred-table' cellpadding=0 cellspacing=0>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' checked='checked' class='cred-advanced-options-radio cred-radio-10' name='cred-user-edit-what-to-edit' value='edit-current-user' />
                                        <span><?php _e('Edit current user (in Loop)', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                            
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' class='cred-advanced-options-radio cred-radio-10' name='cred-user-edit-what-to-edit' value='edit-other-user' />
                                        <span><?php _e('Edit another user', 'wp-cred'); ?></span></label>
                                    <div id='cred-edit-other-user-more' style='display:inline-block'>
                                        <div style='display:inline-block' class='cred_ajax_loader_small' id='cred-form-addtional-loader'></div>
                                        <select id="cred-edit-user-select" name="cred-edit-user-select">
                                            <optgroup label="<?php echo esc_attr(__('Select user', 'wp-cred')); ?>">
                                                <option value='' disabled selected style='display:none;'><?php _e('Select user', 'wp-cred'); ?></option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                            
                        </table>
                    </fieldset>
                    
                    <fieldset class='cred-fieldset'>
                        <legend><b><?php _e('How to display the user form', 'wp-cred'); ?></b></legend>
                        <table class='cred-table' cellpadding=0 cellspacing=0>
                            <?php if (false) {?>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' class='cred-advanced-options-radio cred-radio-10' name='cred-user-edit-how-to-display' value='insert-link' />
                                        <span><?php _e('Insert a link to edit', 'wp-cred'); ?></span>
                                        <span id='cred-edit-link-text-container'>
                                            (<?php _e('text:', 'wp-cred'); ?><input type='text' id='cred-edit-html-text' name='cred-edit-html-text' value='Edit %TITLE%' />)
                                        </span>
                                    </label>
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' checked='checked' class='cred-advanced-options-radio cred-radio-10' name='cred-user-edit-how-to-display' value='insert-form' checked="checked" />
                                        <span><?php _e('Insert the user form itself', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                    
                    <?php if (false) {?>
                    <fieldset class='cred-fieldset' id='cred-edit-html-fieldset'>
                        <legend><b><?php _e('HTML attributes for edit link (advanced)', 'wp-cred'); ?></b></legend>
                        <table class='cred-table' cellpadding=0 cellspacing=0>
                            <tr>
                                <td><?php _e('class:', 'wp-cred'); ?></td>
                                <td><input type='text' id='cred-edit-html-class' name='cred-edit-html-class' value='' /></td>
                            </tr>
                            <tr>
                                <td><?php _e('style:', 'wp-cred'); ?></td>
                                <td><input type='text' id='cred-edit-html-style' name='cred-edit-html-style' value='' /></td>
                            </tr>
                            <tr>
                                <td><?php _e('target:', 'wp-cred'); ?> </td>
                                <td>
                                    <select id='cred-edit-html-target' name='cred-edit-html-target'>
                                        <option value="_self" selected='selected'><?php _e('Current Window', 'wp-cred'); ?></option>
                                        <option value="_top"><?php _e('Parent Window', 'wp-cred'); ?></option>
                                        <option value="_blank"><?php _e('New Window', 'wp-cred'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('more attributes:', 'wp-cred'); ?></td>
                                <td><input type='text' id='cred-edit-html-attributes' name='cred-edit-html-attributes' value='' /></td>
                            </tr>
                        </table>
                    </fieldset>
                    <?php } ?>
                </div>
            </div>       


            <div id='_cred-user-creation-container' class='cred-shortcodes-container'>
                <table class='cred-table' cellpadding=0 cellspacing=0>
                    <tr>
                        <td>
                            <label for="cred_user_form-new-shortcode-select"><?php _e('Select user form', 'wp-cred'); ?></label>
                            <select id="cred_user_form-new-shortcode-select" name="cred_user_form-new-shortcode-select">
                                <optgroup label="<?php echo esc_attr(__('Select user form', 'wp-cred')); ?>">
                                    <option value='' disabled selected style='display:none;'><?php _e('Select user form', 'wp-cred'); ?></option>

                                    <?php
                                    foreach ($user_forms as $form) {
                                        if (isset($form->meta->form['type']) && $form->meta->form['type'] == 'new') {
                                            echo "<option value='{$form->ID}'>{$form->post_title}</option>";
                                        }
                                    }
                                    ?>
                                </optgroup>
                            </select>
                            <a class='cred-help-link' href='<?php echo $help['content_creation_shortcode_settings']['link']; ?>' target='<?php echo $help_target; ?>' title="<?php echo esc_attr($help['content_creation_shortcode_settings']['text']); ?>" >
                                <i class="icon-question-sign"></i>
                                <span><?php echo $help['content_creation_shortcode_settings']['text']; ?></span>
                            </a>
                        </td>
                    </tr>
                </table>

            </div>            


            <div id='_cred-post-creation-container' class='cred-shortcodes-container'>
                <table class='cred-table' cellpadding=0 cellspacing=0>
                    <tr>
                        <td>
                            <label for="cred_form-new-shortcode-select"><?php _e('Select post form', 'wp-cred'); ?></label>
                            <select id="cred_form-new-shortcode-select" name="cred_form-new-shortcode-select">
                                <optgroup label="<?php echo esc_attr(__('Select post form', 'wp-cred')); ?>">
                                    <option value='' disabled selected style='display:none;'><?php _e('Select post form', 'wp-cred'); ?></option>

                                    <?php
                                    foreach ($forms as $form) {
                                        if (isset($form->meta->form['type']) && $form->meta->form['type'] == 'new') {
                                            echo "<option value='{$form->ID}'>{$form->post_title}</option>";
                                        }
                                    }
                                    ?>
                                </optgroup>
                            </select>
                            <a class='cred-help-link' href='<?php echo $help['content_creation_shortcode_settings']['link']; ?>' target='<?php echo $help_target; ?>' title="<?php echo esc_attr($help['content_creation_shortcode_settings']['text']); ?>" >
                                <i class="icon-question-sign"></i>
                                <span><?php echo $help['content_creation_shortcode_settings']['text']; ?></span>
                            </a>
                        </td>
                    </tr>
                </table>

            </div>

            <div id='_cred-post-child-link-container' class='cred-shortcodes-container'>
                <fieldset class='cred-fieldset'>
                    <legend><b><?php _e('Child Form Page', 'wp-cred'); ?></b></legend>
                    <table class='cred-table' cellpadding=0 cellspacing=0>
                        <tr>
                            <td colspan=2><div style='display:inline-block' class='cred_ajax_loader_small' id='cred-form-suggest-child-form-loader'></div><span style='font-style:italic;font-size:10px'><?php _e('Type some characters from title of the page and a suggestion popup will appear..', 'wp-cred'); ?></span></td>
                        </tr>
                        <tr>
                            <td><?php _e('Page:', 'wp-cred'); ?></td>
                            <td><input  type='text' id='cred-child-form-page' name='cred-child-form-page' value='' placeholder="<?php echo esc_attr(__('Type some characters..', 'wp-cred')); ?>" /></td>
                        </tr>
                        <tr>
                            <td><?php _e('link text:', 'wp-cred'); ?></td>
                            <td><input   type='text' id='cred-child-link-text' name='cred-child-link-text' value='' /></td>
                        </tr>
                    </table>
                </fieldset>
                
                <fieldset class='cred-fieldset'>
                    <legend><b><?php _e('How do you want to set the parent in child form?', 'wp-cred'); ?></b></legend>
                    <table class='cred-table' cellpadding=0 cellspacing=0>
                        <tr>
                            <td colspan=2>
                                <label class='cred-label'><input type='radio' checked='checked' class='cred-advanced-options-radio cred-radio-10' name='cred-post-child-parent-action' value='current' />
                                    <span><?php _e('Set the parent according to the currently displayed content', 'wp-cred'); ?></span></label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan=2>
                                <label class='cred-label'><input type='radio' class='cred-advanced-options-radio cred-radio-10' name='cred-post-child-parent-action' value='other' />
                                    <span><?php _e('Choose a specific parent', 'wp-cred'); ?></span></label>
                                <input type='text' id='cred_post_child_parent_id' name='cred_post_child_parent_id' value='' size='15'  placeholder="<?php echo esc_attr(__('Type some characters..', 'wp-cred')); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <td colspan=2>
                                <label class='cred-label'><input type='radio' class='cred-advanced-options-radio cred-radio-10' name='cred-post-child-parent-action' value='form' />
                                    <span><?php _e('The form includes the parent selector', 'wp-cred'); ?></span></label>
                            </td>
                        </tr>
                    </table>
                </fieldset>
                
                <a href='javascript:;' class='cred-show-hide-advanced'></a>
                
                <div id='cred-post-child-link-container-advanced' class='cred-shortcodes-container-advanced'>
                    <fieldset class='cred-fieldset' id='cred-edit-html-fieldset'>
                        <legend><b><?php _e('HTML attributes for child content link (advanced)', 'wp-cred'); ?></b></legend>

                        <table class='cred-table' cellpadding=0 cellspacing=0>
                            <tr>
                                <td><?php _e('class:', 'wp-cred'); ?></td>
                                <td><input type='text' id='cred-child-html-class' name='cred-child-html-class' value='' /></td>
                            </tr>
                            <tr>
                                <td><?php _e('style:', 'wp-cred'); ?></td>
                                <td><input type='text' id='cred-child-html-style' name='cred-child-html-style' value='' /></td>
                            </tr>
                            <tr>
                                <td><?php _e('target:', 'wp-cred'); ?></td>
                                <td>
                                    <select id='cred-child-html-target' name='cred-child-html-target'>
                                        <option value="_self" selected='selected'><?php _e('Current Window', 'wp-cred'); ?></option>
                                        <option value="_top"><?php _e('Parent Window', 'wp-cred'); ?></option>
                                        <option value="_blank"><?php _e('New Window', 'wp-cred'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('more attributes:', 'wp-cred'); ?></td>
                                <td><input type='text' id='cred-child-html-attributes' name='cred-child-html-attributes' value='' /></td>
                            </tr>
                        </table>
                    </fieldset>
                </div>
            </div>

            <div id='_cred-post-delete-link-container' class='cred-shortcodes-container'>
                <fieldset class='cred-fieldset'>
                    <legend><b><?php _e('HTML attributes for delete link', 'wp-cred'); ?></b></legend>
                    <table class='cred-table' cellpadding=0 cellspacing=0>
                        <tr>
                            <td><?php _e('text:', 'wp-cred'); ?></td>
                            <td><input   type='text' id='cred-delete-html-text' name='cred-delete-html-text' value='Delete %TITLE%' /></td>
                        </tr>
                        <tr>
                            <td colspan=2><div style='display:inline-block' class='cred_ajax_loader_small' id='cred-form-suggest-child-form-loader'></div><span style='font-style:italic;font-size:10px'><?php _e('Type some characters from title of the page and a suggestion popup will appear..', 'wp-cred'); ?></span></td>
                        </tr>
                        <tr><td colspan="2"><div id="cred-delete-redirect-page-error"></div></td></tr>
                        <tr>
                            <td><?php _e('Redirect to Post ID:', 'wp-cred'); ?></td>
                            <td><input  type='text' id='cred-delete-redirect-page' name='cred-delete-redirect-page' value='' placeholder="<?php echo esc_attr(__('Type some characters..', 'wp-cred')); ?>" /></td>
                        </tr>
                    </table>
                </fieldset>
                <p style="position: relative;z-index:999;">
                    <a class='cred-help-link' href='<?php echo $help['content_delete_shortcode_settings']['link']; ?>' target='<?php echo $help_target; ?>' title="<?php echo esc_attr($help['content_delete_shortcode_settings']['text']); ?>">
                        <i class="icon-question-sign"></i>
                        <span><?php echo $help['content_delete_shortcode_settings']['text']; ?></span>
                    </a>
                </p>
                <a href='javascript:;' class='cred-show-hide-advanced'></a>
                <div id='cred-post-delete-link-container-advanced' class='cred-shortcodes-container-advanced'>
                    <fieldset class='cred-fieldset'>
                        <legend><b><?php _e('What to delete', 'wp-cred'); ?></b></legend>
                        <table class='cred-table' cellpadding=0 cellspacing=0>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' checked='checked' class='cred-advanced-options-radio cred-radio-10' name='cred-delete-what-to-delete' value='delete-current-post' />
                                        <span><?php _e('Delete current post (in Loop)', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' class='cred-advanced-options-radio  cred-radio-10' name='cred-delete-what-to-delete' value='delete-other-post' />
                                        <span><?php _e('Delete another post (post ID)', 'wp-cred'); ?></span></label>
                                    <input type='text' id='cred_post_delete_id' name='cred_post_delete_id' value='' size='5' />
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                    <fieldset class='cred-fieldset'>
                        <legend><b><?php _e('Delete action', 'wp-cred'); ?></b></legend>
                        <table class='cred-table' cellpadding=0 cellspacing=0>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' checked='checked' class='cred-advanced-options-radio cred-radio-10' name='cred-delete-delete-action' value='trash' />
                                        <span><?php _e('Trash post', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' class='cred-advanced-options-radio cred-radio-10' name='cred-delete-delete-action' value='delete' />
                                        <span><?php _e('Delete post', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='checkbox' id='cred-refresh-after-action' class='cred-refresh-after-action cred-checkbox-10' name='cred-refresh-after-action' value='refresh' checked='checked'/><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Refresh page after deletion', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                    <fieldset class='cred-fieldset'>
                        <legend><b><?php _e('HTML attributes for delete link (advanced)', 'wp-cred'); ?></b></legend>
                        <table class='cred-table' cellpadding=0 cellspacing=0>
                            <tr>
                                <td><?php _e('class:', 'wp-cred'); ?></td>
                                <td><input  type='text' id='cred-delete-html-class' name='cred-delete-html-class' value='' /></td>
                            </tr>
                            <tr>
                                <td><?php _e('style:', 'wp-cred'); ?></td>
                                <td><input   type='text' id='cred-delete-html-style' name='cred-delete-html-style' value='' /></td>
                            </tr>
                        </table>
                    </fieldset>
                    <fieldset class='cred-fieldset'>
                        <legend><b><?php _e('Messages', 'wp-cred'); ?></b></legend>
                        <table class='cred-table' cellpadding=0 cellspacing=0>

                            <tr>
                                <td colspan="2"><?php _e('Display a confirmation message before deleting:', 'wp-cred'); ?></td>
                            </tr>

                            <tr>
                                <td><input type='checkbox' id='cred-delete-html-message-checkbox' checked="1" /></td>
                                <td><input type='text' id='cred-delete-html-message' name='cred-delete-html-message' value='<?php _e('Are you sure you want to delete this post?', 'wp-cred'); ?>' /></td>
                            </tr>

                            <tr>
                                <td colspan="2"><?php _e('Display a confirmation message after deleting:', 'wp-cred'); ?></td>
                            </tr>

                            <tr>
                                <td><input type='checkbox' id='cred-delete-html-message-after-checkbox' /></td>
                                <td><input type='text' id='cred-delete-html-message-after' name='cred-delete-html-message-after' data-val='<?php _e('Post deleted', 'wp-cred'); ?>' style="display:none;" /></td>
                            </tr>

                        </table>
                    </fieldset>
                </div>
            </div>

            <div id='_cred-post-edit-container' class='cred-shortcodes-container'>
                <table class='cred-table' cellpadding=0 cellspacing=0>
                    <tr>
                        <td>
                            <label for="cred_form-edit-shortcode-select"><?php _e('Select post form', 'wp-cred'); ?></label>
                            <select style='position:relative;' id="cred_form-edit-shortcode-select" name="cred_form-edit-shortcode-select">
                                <optgroup label="<?php echo esc_attr(__('Select post form', 'wp-cred')); ?>">
                                    <option value='' disabled selected style='display:none;'><?php _e('Select post form', 'wp-cred'); ?></option>

                                    <?php
                                    foreach ($forms as $form) {
                                        if (isset($form->meta->form['type']) && $form->meta->form['type'] == 'edit') {
                                            echo "<option class='_cred_cred_{$form->meta->post['post_type']}' value='{$form->ID}'>{$form->post_title}</option>";
                                        }
                                    }
                                    ?>
                                </optgroup>
                            </select>
                            <a class='cred-help-link' href='<?php echo $help['content_edit_shortcode_settings']['link']; ?>' target='<?php echo $help_target; ?>' title="<?php echo esc_attr($help['content_edit_shortcode_settings']['text']); ?>">
                                <i class="icon-question-sign"></i>
                                <span><?php echo $help['content_edit_shortcode_settings']['text']; ?></span>
                            </a>
                        </td>
                    <a href='javascript:;' class='cred-show-hide-advanced'></a>
                    </tr>
                </table>

                <div id='cred-post-edit-container-advanced' class='cred-shortcodes-container-advanced'>
                    <fieldset class='cred-fieldset'>
                        <legend><b><?php _e('What to edit', 'wp-cred'); ?></b></legend>
                        <table class='cred-table' cellpadding=0 cellspacing=0>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' checked='checked' class='cred-advanced-options-radio cred-radio-10' name='cred-edit-what-to-edit' value='edit-current-post' />
                                        <span><?php _e('Edit current post (in Loop)', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' class='cred-advanced-options-radio cred-radio-10' name='cred-edit-what-to-edit' value='edit-other-post' />
                                        <span><?php _e('Edit another post', 'wp-cred'); ?></span></label>
                                    <div id='cred-edit-other-post-more' style='display:inline-block'>
                                        <div style='display:inline-block' class='cred_ajax_loader_small' id='cred-user-form-addtional-loader'></div>
                                        <select id="cred-edit-post-select" name="cred-edit-post-select">
                                            <optgroup label="<?php echo esc_attr(__('Select post', 'wp-cred')); ?>">
                                                <option value='' disabled selected style='display:none;'><?php _e('Select post', 'wp-cred'); ?></option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                    <fieldset class='cred-fieldset'>
                        <legend><b><?php _e('How to display the form', 'wp-cred'); ?></b></legend>
                        <table class='cred-table' cellpadding=0 cellspacing=0>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' checked='checked' class='cred-advanced-options-radio cred-radio-10' name='cred-edit-how-to-display' value='insert-link' />
                                        <span><?php _e('Insert a link to edit', 'wp-cred'); ?></span>
                                        <span id='cred-edit-link-text-container'>
                                            (<?php _e('text:', 'wp-cred'); ?><input type='text' id='cred-edit-html-text' name='cred-edit-html-text' value='Edit %TITLE%' />)
                                        </span>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type='radio' class='cred-advanced-options-radio cred-radio-10' name='cred-edit-how-to-display' value='insert-form' />
                                        <span><?php _e('Insert the form itself', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                    <fieldset class='cred-fieldset' id='cred-edit-html-fieldset'>
                        <legend><b><?php _e('HTML attributes for edit link (advanced)', 'wp-cred'); ?></b></legend>
                        <table class='cred-table' cellpadding=0 cellspacing=0>
                            <tr>
                                <td><?php _e('class:', 'wp-cred'); ?></td>
                                <td><input type='text' id='cred-edit-html-class' name='cred-edit-html-class' value='' /></td>
                            </tr>
                            <tr>
                                <td><?php _e('style:', 'wp-cred'); ?></td>
                                <td><input type='text' id='cred-edit-html-style' name='cred-edit-html-style' value='' /></td>
                            </tr>
                            <tr>
                                <td><?php _e('target:', 'wp-cred'); ?> </td>
                                <td>
                                    <select id='cred-edit-html-target' name='cred-edit-html-target'>
                                        <option value="_self" selected='selected'><?php _e('Current Window', 'wp-cred'); ?></option>
                                        <option value="_top"><?php _e('Parent Window', 'wp-cred'); ?></option>
                                        <option value="_blank"><?php _e('New Window', 'wp-cred'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('more attributes:', 'wp-cred'); ?></td>
                                <td><input type='text' id='cred-edit-html-attributes' name='cred-edit-html-attributes' value='' /></td>
                            </tr>
                        </table>
                    </fieldset>
                </div>
            </div>

        </div>

        <p class="cred-buttons-holder">
            <a id="cred-popup-cancel" class="button" href="javascript:;"><?php _e('Cancel', 'wp-cred'); ?></a>
            <a id="cred-insert-shortcode" disabled='disabled' class="button button-secondary" href="javascript:void(0);"><?php _e('Insert shortcode', 'wp-cred'); ?></a>
        </p>

    </div>

</span>
