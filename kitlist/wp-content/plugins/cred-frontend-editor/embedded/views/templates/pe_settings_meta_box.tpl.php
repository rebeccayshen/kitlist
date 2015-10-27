<?php if (!defined('ABSPATH'))  die('Security check'); ?>
<?php
if (!isset($settings['post_expiration_cron']['schedule'])) $settings['post_expiration_cron']['schedule'] = '';
if (!isset($schedules)) $schedules = wp_get_schedules();
?>
    <br />
    <a id="cred-post-expiration-form"></a>
    <form name="cred-post-expiration-form" enctype="multipart/form-data" action="" method="post">
    <?php wp_nonce_field('cred-settings-action','cred-settings-field'); ?>
    <table class="widefat" id="cred_post_expiration_general_settings_table">
        <thead>
            <tr>
                <th><?php _e('CRED post expiration general settings', $cred_post_expiration->getLocalizationContext()); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <label for="cred_post_expiration_cron"><?php _e('Check for expired content:', $cred_post_expiration->getLocalizationContext()); ?></label>
                    <select id="cred_post_expiration_cron" name="settings[post_expiration_cron][schedule]" class='cred_ajax_change'>
                    <?php foreach ($schedules as $schedule => $schedule_definition) { ?>
                        <option value="<?php echo $schedule; ?>" <?php if ($schedule == $settings['post_expiration_cron']['schedule']) echo 'selected="selected"'; ?>><?php echo $schedule_definition['display']; ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan=2>
                    <p>
                        <input type="hidden" name="cred_settings_action" value="cron" />
                        <input type="submit" name="submit" value="<?php echo esc_attr(__('Update Settings', $cred_post_expiration->getLocalizationContext())); ?>" class="button button-primary button-large"/>
                    </p>
                    </td>
            </tr>
        </tbody>
    </table>
    </form>
