<?php if (!defined('ABSPATH'))  die('Security check'); ?>
<span class="cred-media-button cred-media-button2">
    <a href='javascript:;' class='button cred-icon-button' title='<?php echo esc_attr(__('Insert Body Codes','wp-cred')); ?>'>
        <i class="icon-cred-logo ont-icon-18 ont-color-gray"></i><?php echo __( 'Insert Body Codes', 'wp-cred' ); ?></a>
    <div class="cred-popup-box">
        <div class='cred-popup-heading'>
        <h3><?php _e('Body Codes (click to insert)','wp-cred'); ?></h3>
        <i title='<?php echo esc_attr(__('Close','wp-cred')); ?>' class='icon-remove cred-close-button cred-cred-cancel-close'></i>
        </div>
        <div class="cred-popup-inner cred-notification-body-codes">
            <?php
            $notification_codes=apply_filters('cred_admin_notification_body_codes', array(
                '%%POST_ID%%'=> __('Post ID','wp-cred'),
                '%%POST_TITLE%%'=> __('Post Title','wp-cred'),
                '%%POST_LINK%%'=> __('Post Link','wp-cred'),
                '%%POST_PARENT_TITLE%%'=> __('Parent Title','wp-cred'),
                '%%POST_PARENT_LINK%%'=> __('Parent Link','wp-cred'),
                '%%POST_ADMIN_LINK%%'=> __('Post Admin Link','wp-cred'),
                '%%USER_LOGIN_NAME%%'=> __('(Logged) User Login Name','wp-cred'),
                '%%USER_DISPLAY_NAME%%'=> __('(Logged) User Display Name','wp-cred'),
                '%%FORM_NAME%%'=> __('Form Name','wp-cred'),
                '%%FORM_DATA%%'=> __('Form Data','wp-cred'),
                '%%DATE_TIME%%'=> __('Date/Time','wp-cred')
            ), $form, $ii, $notification);
            foreach ($notification_codes as $_v=>$_l)
            {
                ?><a href="javascript:;" class='button cred_field_add_code' data-area="#<?php echo $area_id; ?>" data-value="<?php echo $_v; ?>"><?php echo $_l; ?></a><?php
            }
            ?>
        </div>
    </div>
</span>