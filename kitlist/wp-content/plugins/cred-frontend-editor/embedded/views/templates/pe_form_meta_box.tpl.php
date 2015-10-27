<?php if (!defined('ABSPATH'))  die('Security check'); ?>
<?php
if (empty($settings_defaults)) exit;
if (empty($settings)) $settings=array();
$settings = CRED_PostExpiration::array_merge_distinct($settings_defaults, $settings);
?>
<div>
    <div class="cred-fieldset">
    	<p>
			<label>
				<input type="checkbox" name="<?php echo $field_name; ?>[enable]" value="1" <?php if (1 == $settings['enable']) echo 'checked="checked"'; ?>>
				<span><?php _e('Automatic expire date for this form', $cred_post_expiration->getLocalizationContext()); ?></span>
			</label>
		</p>
    </div>

    <div class="cred_post_expiration_panel" style="display: none;">
        <fieldset class="cred-fieldset">
            <p class="cred-explain-text"><?php _e('The expiration time is set according to the publish date. This means it will take effect some time after the publish date of the post.', $cred_post_expiration->getLocalizationContext()); ?></p>
            <p class="cred-label-holder">
                <label for="cred_post_expiration_time"><?php _e('Set the expiration time:', $cred_post_expiration->getLocalizationContext()); ?></label>
            </p>
            <select id="cred_post_expiration_week" name="<?php echo $field_name; ?>[expiration_time][weeks]" class='cred_ajax_change'>
            <?php for ($i = 0; $i <= 52; $i++) { ?>
                <option value="<?php echo $i; ?>" <?php if ($settings['expiration_time']['weeks']==$i) echo 'selected="selected"'; ?>><?php _e($i, $cred_post_expiration->getLocalizationContext()); ?></option>
                <?php } ?>
            </select>
            <span><?php _e('Weeks', $cred_post_expiration->getLocalizationContext()); ?></span>
             <select id="cred_post_expiration_days" name="<?php echo $field_name; ?>[expiration_time][days]" class='cred_ajax_change'>
            <?php for ($i = 0; $i <= 6; $i++) { ?>
                <option value="<?php echo $i; ?>" <?php if ($settings['expiration_time']['days']==$i) echo 'selected="selected"'; ?>><?php _e($i, $cred_post_expiration->getLocalizationContext()); ?></option>
                <?php } ?>
            </select>
            <span><?php _e('Days', $cred_post_expiration->getLocalizationContext()); ?></span>
		</fieldset>
        <fieldset class="cred-fieldset">
        
            <p class="cred-label-holder">
                <label for="cred_post_expiration_post_status"><?php _e('After expiration change the status of the post to:',$cred_post_expiration->getLocalizationContext()); ?></label>
            </p>
<?php
$options = apply_filters('cred_pe_post_expiration_post_status', $cred_post_expiration->getActionPostStatus());
?>
            <select id="cred_post_expiration_post_status" name="<?php echo $field_name; ?>[action][post_status]" class="cred_ajax_change">
            <?php foreach ($options as $value => $text) { ?>
                <option value="<?php echo $value; ?>" <?php if ($value == $settings['action']['post_status']) echo 'selected="selected"'; ?>><?php echo $text; ?></option>
                <?php } ?>
            </select>
            
		</fieldset>
    </div>
</div>