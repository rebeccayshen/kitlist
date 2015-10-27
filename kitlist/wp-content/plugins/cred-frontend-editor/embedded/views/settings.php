<?php
/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk_new/embedded/views/settings.php $
 * $LastChangedDate: 2015-03-03 15:08:45 +0100 (mar, 03 mar 2015) $
 * $LastChangedRevision: 32049 $
 * $LastChangedBy: francesco $
 *
 */
if (!defined('ABSPATH'))
    die('Security check');
if (!current_user_can(CRED_CAPABILITY)) {
    die('Access Denied');
}
$results = '';
$user_results = '';
$cred_import_file = null;
$show_generic_error = false;
if (isset($_POST['import']) && $_POST['import'] == __('Import', 'wp-cred') &&
        isset($_POST['cred-import-nonce']) &&
        wp_verify_nonce($_POST['cred-import-nonce'], 'cred-import-nonce')) {
    if (isset($_FILES['import-file'])) {
        $cred_import_file = $_FILES['import-file'];
        if ($cred_import_file['error'] > 0) {
            $show_generic_error = true;
            $cred_import_file = null;
        }
    } else {
        $cred_import_file = null;
    }

    if ($cred_import_file !== null && !empty($cred_import_file)) {
        $options = array();
        if (isset($_POST["cred-overwrite-forms"]))
            $options['overwrite_forms'] = 1;
        if (isset($_POST["cred-overwrite-settings"]))
            $options['overwrite_settings'] = 1;
        if (isset($_POST["cred-overwrite-custom-fields"]))
            $options['overwrite_custom_fields'] = 1;
        CRED_Loader::load('CLASS/XML_Processor');
        $results = CRED_XML_Processor::importFromXML($cred_import_file, $options);
    }
}

if (isset($_POST['import']) && $_POST['import'] == __('Import', 'wp-cred') &&
        isset($_POST['cred-user-import-nonce']) &&
        wp_verify_nonce($_POST['cred-user-import-nonce'], 'cred-user-import-nonce')) {
    if (isset($_FILES['import-file'])) {
        $cred_import_file = $_FILES['import-file'];

        if ($cred_import_file['error'] > 0) {
            $show_generic_error = true;
            $cred_import_file = null;
        }
    } else {
        $cred_import_file = null;
    }

    if ($cred_import_file !== null && !empty($cred_import_file)) {
        $options = array();
        if (isset($_POST["cred-overwrite-forms"]))
            $options['overwrite_forms'] = 1;
        if (isset($_POST["cred-overwrite-settings"]))
            $options['overwrite_settings'] = 1;
        if (isset($_POST["cred-overwrite-custom-fields"]))
            $options['overwrite_custom_fields'] = 1;
        CRED_Loader::load('CLASS/XML_Processor');
        $user_results = CRED_XML_Processor::importUserFromXML($cred_import_file, $options);
    }
}

$settings_model = CRED_Loader::get('MODEL/Settings');
$settings = $settings_model->getSettings();

$url = admin_url('admin.php') . '?page=CRED_Settings';
$doaction = isset($_POST['cred_settings_action']) ? $_POST['cred_settings_action'] : false;
if ($doaction) {    
    check_admin_referer('cred-settings-action', 'cred-settings-field');
    switch ($doaction) {
        case 'edit':
            $settings = isset($_POST['settings']) ? (array) $_POST['settings'] : array();
            if (!isset($settings['wizard']))
                $settings['wizard'] = 0;
            $settings_model->updateSettings($settings);
            break;
    }
    // CRED_PostExpiration
    do_action('cred_settings_action', $doaction, $settings);
}
?>
<div class="wrap">
    <?php screen_icon('cred-frontend-editor'); ?>
    <h2><?php _e('CRED Settings', 'wp-cred') ?>
        <a class="cred-help-link" title="<?php echo esc_attr(CRED_CRED::$help['add_forms_to_site']['text']); ?>" href="<?php echo CRED_CRED::$help['add_forms_to_site']['link']; ?>" target="_blank">
            <i class="icon-question-sign"></i>
        </a>
    </h2>
    <br />
    <!-- use WP Tabs here -->
    <!--    <h2 class="nav-tab-wrapper">
            <a class='nav-tab' href="#cred-general-settings"><?php //_e('General Settings','wp-cred');         ?></a>
            <a class='nav-tab' href="#cred-import"><?php //_e('Import','wp-cred');          ?></a>
        </h2>
        <a id="cred-general-settings"></a>-->
    <form method="post" action="">
        <?php wp_nonce_field('cred-settings-action', 'cred-settings-field'); ?>
        <table class="widefat" id="cred_general_settings_table">
            <thead>
                <tr>
                    <th><?php _e('General Settings', 'wp-cred'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <table>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[wizard]" value="1" <?php if (isset($settings['wizard']) && $settings['wizard']) echo "checked='checked'"; ?> /><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Create new forms using the CRED Wizard', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[syntax_highlight]" value="1" <?php if (isset($settings['syntax_highlight']) && $settings['syntax_highlight']) echo "checked='checked'"; ?> /><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Enable Syntax Highlight for CRED Forms', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[export_settings]" value="1" <?php if (isset($settings['export_settings']) && $settings['export_settings']) echo "checked='checked'"; ?> /><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Export Settings also when exporting Forms', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[export_custom_fields]" value="1" <?php if (isset($settings['export_custom_fields']) && $settings['export_custom_fields']) echo "checked='checked'"; ?> /><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Export Custom Fields also when exporting Forms', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>

                            <?php
// CRED_PostExpiration
                            do_action('cred_pe_general_settings', $settings);
                            ?>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[use_bootstrap]" value="1" <?php if (isset($settings['use_bootstrap']) && $settings['use_bootstrap']) echo "checked='checked'"; ?> /><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Use bootstrap in CRED Forms.', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2>
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[dont_load_cred_css]" value="1" <?php if (isset($settings['dont_load_cred_css']) && $settings['dont_load_cred_css']) echo "checked='checked'"; ?> /><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Do not load CRED style sheets on front-end.', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2>
                                    <p>
                                        <?php _e('CRED Forms allow integration with ReCaptcha API,', 'wp-cred'); ?><br>
                                        <?php _e('a free CAPTCHA service that helps digitize books while protecting against bots messing with your forms', 'wp-cred'); ?>
                                    </p>
                                    <p>
                                        <?php _e('The following are needed only if you plan to use ReCaptcha support for your forms (recommended)', 'wp-cred'); ?>
                                    </p>
                                    <a target="_blank" href='https://www.google.com/recaptcha/admin#whyrecaptcha'><?php _e('Sign Up to use ReCaptcha API', 'wp-cred'); ?></a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="text" size='50' name="settings[recaptcha][public_key]" value="<?php if (isset($settings['recaptcha']['public_key'])) echo $settings['recaptcha']['public_key']; ?>"  />
                                    <strong><?php _e('Public Key for ReCaptcha API', 'wp-cred'); ?></strong>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="text" size='50' name="settings[recaptcha][private_key]" value="<?php if (isset($settings['recaptcha']['private_key'])) echo $settings['recaptcha']['private_key']; ?>"  />
                                    <strong><?php _e('Private Key for ReCaptcha API', 'wp-cred'); ?></strong>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    <strong><?php _e('Content Filter', 'wp-cred'); ?></strong><br>
                                    <?php _e('CRED filters the content that is submitted by a form.', 'wp-cred'); ?>
                                </td>
                            </tr>
                            <tr class='my_allowed_tags' style="display:none;">
                                <td>
                                    <?php _e('The following tags are allowed and all other tags will be discarded:', 'wp-cred'); ?>
                                </td>
                            </tr>
                            <tr class='my_allowed_tags' style="display:none;">
                                <td>
                                    <div id="all_checks">
                                        <div style="width: 150px; display: inline-block;">
                                            <?php
                                            $_tags = wp_kses_allowed_html('post');
                                            $_tags['no_one'] = 1;

                                            if (!isset($settings['allowed_tags'])) {
                                                $settings['allowed_tags'] = array();
                                                foreach ($_tags as $key => $value) {
                                                    $settings['allowed_tags'][$key] = 1;
                                                }
                                            }
                                            $allowed_tags = $settings['allowed_tags'];

                                            $i = 0;
                                            $rows = 16;
                                            foreach ($_tags as $key => $value) {
                                                $checked = (isset($settings['allowed_tags'][$key])) ? "checked" : "";

                                                // If we've reached our last row, move over to a new div
                                                if (!($i % $rows) && $i > 0) {
                                                    echo "</div><div style=\"width: 150px; display: inline-block\">";
                                                }

                                                if ($key == 'no_one') {
                                                    ?>
                                                    <div style="height:40px;"></div>
                                                    <div>
                                                        <input type="checkbox" id="check_uncheck_all" size='50' onclick="if (this.checked) {
                                                                            jQuery('#all_checks').find('input:checked:not(#check_uncheck_all)').removeAttr('checked');
                                                                        } else {
                                                                            jQuery('#all_checks').find('input:not(#check_uncheck_all)').prop('checked', true);
                                                                        }" name="settings[allowed_tags][no_one]" value="1"  />
                                                        <strong><?php echo "None" ?></strong>
                                                    </div>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <div>
                                                        <input <?php echo $checked; ?> type="checkbox" size='50' name="settings[allowed_tags][<?php echo $key; ?>]" value="1"  />
                                                        <strong><?php echo $key; ?></strong>
                                                    </div>
                                                    <?php
                                                }
                                                $i++;
                                            }
                                            ?>                                        
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr class='my_allowed_tags'>
                                <td colspan=2>
                                    <p>                                        
                                        <input type="button" name="show_hide_allowed_tags" onclick="jQuery('.my_allowed_tags').toggle();" value="<?php echo esc_attr(__('Allowed Tags', 'wp-cred')); ?>" class="button button-secondary button-large"/>
                                    </p>
                                </td>
                            </tr>

                            <?php
                            //by default use notification for autogeneration email
                            $use_notification_for_autogeneration = defined('CRED_NOTIFICATION_4_AUTOGENERATION') ? CRED_NOTIFICATION_4_AUTOGENERATION : true;
                            if (!$use_notification_for_autogeneration) {
                                ?>
                                <tr>
                                    <td>

                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <strong><?php _e('CRED User Form', 'wp-cred'); ?></strong>                                    
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <strong><?php _e('Email Autogeneration', 'wp-cred'); ?></strong><br>
                                        <?php _e('If autogenerate username and/or password is set in CRED User Form settings the following Email will be autogenerated.', 'wp-cred'); ?>
                                    </td>
                                </tr>
                                <tr class='cuf_autogeneration_email'>
                                    <td>
                                        <strong><?php _e('Email Subject', 'wp-cred'); ?></strong><br>
                                        <input type="text" name="settings[autogeneration_email][subject]" value="<?php if (isset($settings['autogeneration_email']['subject'])) echo $settings['autogeneration_email']['subject']; ?>" />
                                    </td>
                                </tr>                            
                                <tr class='cuf_autogeneration_email'>
                                    <td>
                                        <strong><?php _e('Email Body', 'wp-cred'); ?></strong><br>
                                        <textarea cols="50" rows="10" name="settings[autogeneration_email][body]"><?php if (isset($settings['autogeneration_email']['body'])) echo $settings['autogeneration_email']['body']; ?></textarea>
                                    </td>
                                </tr>   
                            <?php } ?>

                            <tr>
                                <td colspan=2>
                                    <p>
                                        <input type="hidden" name="cred_settings_action" value="edit" />
                                        <input type="submit" name="submit" value="<?php echo esc_attr(__('Update Settings', 'wp-cred')); ?>" class="button button-primary button-large"/>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
    <?php
// CRED_PostExpiration
    do_action('cred_ext_metabox_settings', $settings);
    ?>
    <br />    
    <a id="cred-import"></a>

    <form name="cred-import-form" enctype="multipart/form-data" action="" method="post">
        <?php wp_nonce_field('cred-settings-action', 'cred-settings-field'); ?>
        <table class="widefat" id="cred_general_settings_table">
            <thead>
                <tr>
                    <th><?php _e('Import CRED Post Forms', 'wp-cred'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?php
                        if ($show_generic_error && isset($_POST['type']) && $_POST['type'] == 'post_forms') {
                            echo '<div style="color:red;font-weight:bold">' . __('Upload error or file not valid', 'wp-cred') . '</div>';
                        }
                        if (is_wp_error($results)) {
                            echo '<div style="color:red;font-weight:bold">' . $results->get_error_message($results->get_error_code()) . '</div>';
                        } elseif (is_array($results)) {
                            ?>
                            <ul style="font-style:italic">
                                <?php
                                /**
                                 * show settings imported message
                                 */
                                if ($results['settings']) {
                                    printf('<li>%s</li>', __('General Settings Updated', 'wp-cred'));
                                }
                                ?>
                                <li><?php _e('Custom Fields Imported', 'wp-cred'); ?> : <?php echo $results['custom_fields']; ?></li>
                                <li><?php _e('Forms overwritten', 'wp-cred'); ?> : <?php echo $results['updated']; ?></li>
                                <li><?php _e('Forms added', 'wp-cred'); ?> : <?php echo $results['new']; ?></li>
                            </ul>
                            <?php if (!empty($results['errors'])) { ?>
                                <ul>
                                    <?php foreach ($results['errors'] as $err) { ?>
                                        <li style="color:red;"><?php echo $err; ?></li>
                                    <?php } ?>
                                </ul>
                            <?php } ?>
                        <?php } ?>

                        <ul>
                            <li>
                                <label class='cred-label'><input id="checkbox-1" type="checkbox" class='cred-checkbox-invalid' name="cred-overwrite-forms" value="1" /><span class='cred-checkbox-replace'></span>
                                    <span><?php _e('Overwrite existing post forms', 'wp-cred'); ?></span></label>
                            </li>
                            <input type="hidden" name="type" value="post_forms">
                            <!--<li>
                                <input id="checkbox-2" type="checkbox" name="cred-delete-other-forms"  value="1" />
                                <label for="checkbox-2"><?php //_e('Delete forms not included in the import','wp-cred');         ?></label>
                            </li>-->
                            <li>
                                <label class='cred-label'>
                                    <input id="checkbox-5" type="checkbox" class='cred-checkbox-invalid' name="cred-overwrite-settings" value="1" />
                                    <span class='cred-checkbox-replace'></span>
                                    <span><?php _e('Import and Overwrite CRED Settings', 'wp-cred'); ?></span></label>
                            </li>
                            <li>
                                <label class='cred-label'>
                                    <input id="checkbox-6" type="checkbox" class='cred-checkbox-invalid' name="cred-overwrite-custom-fields" value="1" />
                                    <span class='cred-checkbox-replace'></span>
                                    <span><?php _e('Import and Overwrite CRED Custom Fields', 'wp-cred'); ?></span></label>
                            </li>
                        </ul>
                        <label for="upload-cred-file"><?php __('Select the xml file to upload:&nbsp;', 'wp-cred'); ?></label>

                        <input type="file" class='cred-filefield' id="upload-cred-file" name="import-file" />

                        <input id="cred-import" class="button button-primary" type="submit" value="<?php echo esc_attr(__('Import', 'wp-cred')); ?>" name="import" />

                        <?php wp_nonce_field('cred-import-nonce', 'cred-import-nonce'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>

    <br />
    <a id="cred-user-import"></a>
    <form name="cred-import-user-form" enctype="multipart/form-data" action="" method="post">
        <?php wp_nonce_field('cred-user-settings-action', 'cred-user-settings-field'); ?>
        <table class="widefat" id="cred_general_settings_table">
            <thead>
                <tr>
                    <th><?php _e('Import CRED User Forms', 'wp-cred'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?php
                        if ($show_generic_error && isset($_POST['type']) && $_POST['type'] == 'user_forms') {
                            echo '<div style="color:red;font-weight:bold">' . __('Upload error or file not valid', 'wp-cred') . '</div>';
                        }
                        if (is_wp_error($user_results)) {
                            echo '<div style="color:red;font-weight:bold">' . $user_results->get_error_message($user_results->get_error_code()) . '</div>';
                        } elseif (is_array($user_results)) {
                            ?>
                            <ul style="font-style:italic">
                                <?php
                                /**
                                 * show settings imported message
                                 */
                                if ($user_results['settings']) {
                                    printf('<li>%s</li>', __('General Settings Updated', 'wp-cred'));
                                }
                                ?>
                                <?php if (false) { ?>
                                    <li><?php _e('Custom Fields Imported', 'wp-cred'); ?> : <?php echo $user_results['custom_fields']; ?></li>
                                <?php } ?>
                                <li><?php _e('User Forms overwritten', 'wp-cred'); ?> : <?php echo $user_results['updated']; ?></li>
                                <li><?php _e('User Forms added', 'wp-cred'); ?> : <?php echo $user_results['new']; ?></li>
                            </ul>
                            <?php if (!empty($user_results['errors'])) { ?>
                                <ul>
                                    <?php foreach ($user_results['errors'] as $err) { ?>
                                        <li style="color:red;"><?php echo $err; ?></li>
                                    <?php } ?>
                                </ul>
                            <?php } ?>
                        <?php } ?>

                        <ul>
                            <li>
                                <label class='cred-label'><input id="checkbox-1" type="checkbox" class='cred-checkbox-invalid' name="cred-overwrite-forms" value="1" /><span class='cred-checkbox-replace'></span>
                                    <span><?php _e('Overwrite existing user forms', 'wp-cred'); ?></span></label>
                            </li>
                            <input type="hidden" name="type" value="user_forms">
                            <!--<li>
                                <input id="checkbox-2" type="checkbox" name="cred-delete-other-forms"  value="1" />
                                <label for="checkbox-2"><?php //_e('Delete forms not included in the import','wp-cred');         ?></label>
                            </li>-->
                            <li>
                                <label class='cred-label'>
                                    <input id="checkbox-5" type="checkbox" class='cred-checkbox-invalid' name="cred-overwrite-settings" value="1" />
                                    <span class='cred-checkbox-replace'></span>
                                    <span><?php _e('Import and Overwrite CRED Settings', 'wp-cred'); ?></span></label>
                            </li>
                            <?php if (false) { ?>                            <li>
                                    <label class='cred-label'>
                                        <input id="checkbox-6" type="checkbox" class='cred-checkbox-invalid' name="cred-overwrite-custom-fields" value="1" />
                                        <span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Import and Overwrite CRED Custom Fields', 'wp-cred'); ?></span></label>
                                </li>
                            <?php } ?>
                        </ul>
                        <label for="upload-cred-file"><?php __('Select the xml file to upload:&nbsp;', 'wp-cred'); ?></label>

                        <input type="file" class='cred-filefield' id="upload-cred-file" name="import-file" />

                        <input id="cred-import" class="button button-primary" type="submit" value="<?php echo esc_attr(__('Import', 'wp-cred')); ?>" name="import" />

                        <?php wp_nonce_field('cred-user-import-nonce', 'cred-user-import-nonce'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>
