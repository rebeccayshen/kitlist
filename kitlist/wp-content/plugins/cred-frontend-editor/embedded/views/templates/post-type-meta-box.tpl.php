<?php if (!defined('ABSPATH'))  die('Security check'); ?>
<?php
$settings=CRED_Helper::mergeArrays(array(
    'post'=>array(
        'post_type'=>'post',
        'post_status'=>'draft'
    ),
    'form'=>array(
        'type'=>'new',
        'action'=>'form',
        'action_page'=>'',
        'action_message'=>'',
        'redirect_delay'=>0,
        'hide_comments'=>0,
        'theme'=>'minimal',
        'has_media_button'=>0,
        'include_wpml_scaffold'=>0,
        'include_captcha_scaffold'=>0
    )
), (array)$settings);
?>
        <p class='cred-label-holder'>
			<label for="cred_post_type"><?php _e('Choose the type of content this form will create or modify:','wp-cred'); ?></label>
		</p>
		<select id="cred_post_type" name="_cred[post][post_type]" class='cred_ajax_change'>
		<?php
			foreach ($post_types as $pt ) {
                         if(!has_filter('cred_wpml_glue_is_translated_and_unique_post_type') || apply_filters('cred_wpml_glue_is_translated_and_unique_post_type',$pt['type'])){  
			  if ($settings['post']['post_type']==$pt['type'] || (isset($_GET['glue_post_type']) && $pt['type']==$_GET['glue_post_type']))
				echo '<option value="'.$pt['type'].'" selected="selected">'. $pt['name']. '</option>';
			  else
				echo '<option value="'.$pt['type'].'">'. $pt['name']. '</option>';
			}
}
		?>
		</select>
        <p class="cred-label-holder">
			<label for="cred_post_status"><?php _e('Select the status of content created by this form:','wp-cred'); ?></label>
		</p>
		<select id="cred_post_status" name="_cred[post][post_status]" class='cred_ajax_change'>
            <option value='original' <?php if ($settings['post']['post_status']=='original') echo 'selected="selected"'; ?>><?php _e('Keep original status','wp-cred'); ?></option>
            <option value='draft' <?php if ($settings['post']['post_status']=='draft') echo 'selected="selected"'; ?>><?php _e('Draft','wp-cred'); ?></option>
            <option value='pending' <?php if ($settings['post']['post_status']=='pending') echo 'selected="selected"'; ?>><?php _e('Pending Review','wp-cred'); ?></option>
            <option value='private' <?php if ($settings['post']['post_status']=='private') echo 'selected="selected"'; ?>><?php _e('Private','wp-cred'); ?></option>
            <option value='publish' <?php if ($settings['post']['post_status']=='publish') echo 'selected="selected"'; ?>><?php _e('Published','wp-cred'); ?></option>
		</select>
        <p>
        	<label class='cred-label'>
        	    <input type='checkbox' class='cred-checkbox-10' name='_cred[form][has_media_button]' id='cred_content_has_media_button' value='1' <?php if ($settings['form']['has_media_button']) echo 'checked="checked"'; ?> /><span class='cred-checkbox-replace'></span>
        	    <span><?php _e('Allow Media Insert button in Post Content Rich Text Editor','wp-cred'); ?></span>
        	</label>
        	<a class="cred-help-link" style="position:absolute;top:5px;right:10px;" href="<?php echo $help['post_type_settings']['link']; ?>" target="<?php echo $help_target; ?>" title="<?php echo esc_attr($help['post_type_settings']['text']); ?>" >
				<i class="icon-question-sign"></i>
				<span><?php echo $help['post_type_settings']['text']; ?></span>
			</a>
        </p>