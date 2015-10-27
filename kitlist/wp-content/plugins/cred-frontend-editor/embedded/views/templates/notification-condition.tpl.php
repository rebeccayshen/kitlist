<?php

/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk_new/embedded/views/templates/notification-condition.tpl.php $
 * $LastChangedDate: 2014-07-21 16:02:20 +0200 (lun, 21 lug 2014) $
 * $LastChangedRevision: 25151 $
 * $LastChangedBy: marcin $
 *
 */


if (!defined('ABSPATH')) {
    die('Security check');
}

if (!is_array($condition))  $condition=array();

$condition=CRED_Helper::mergeArrays(array(
    'field'=>'',
    'op'=>'=',
    'value'=>'',
    'only_if_changed'=>1
    ), $condition);
?>
<p id="cred_notification_field_condition-<?php echo $ii; ?>-<?php echo $jj; ?>">
    <i title="<?php echo esc_attr(__('Please select a field', 'wp-cred')); ?>" id="notification_field_required-<?php echo $ii; ?>-<?php echo $jj; ?>" class="icon-warning-sign" style="display:none;"></i>
    <select data-cred-bind="{ validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_field_required-<?php echo $ii; ?>-<?php echo $jj; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    }, action: 'set', what: { options: '_cred[_all_persistent_meta_fields]' } }" name="_cred[notification][notifications][<?php echo $ii; ?>][event][condition][<?php echo $jj; ?>][field]">
        <optgroup label="<?php echo esc_attr(__('Please Select..','wp-cred')); ?>">
            <option value='' disabled selected style='display:none;'  data-dummy-option="1"><?php _e('Select field..','wp-cred'); ?></option>
            <?php if (''!=$condition['field']) {
                ?><option value='<?php echo $condition['field']; ?>' selected="selected" ><?php echo $condition['field']; ?></option><?php
            }?>
        </optgroup>
    </select>
    <select name="_cred[notification][notifications][<?php echo $ii; ?>][event][condition][<?php echo $jj; ?>][op]">
        <option value='=' <?php if ('='==$condition['op']) echo 'selected="selected"'; ?>>=</option>
        <option value='<>' <?php if ('<>'==$condition['op']) echo 'selected="selected"'; ?>>&lt;&gt;</option>
        <option value='>=' <?php if ('>='==$condition['op']) echo 'selected="selected"'; ?>>&gt;=</option>
        <option value='<=' <?php if ('<='==$condition['op']) echo 'selected="selected"'; ?>>&lt;=</option>
        <option value='>' <?php if ('>'==$condition['op']) echo 'selected="selected"'; ?>>&gt;</option>
        <option value='<' <?php if ('<'==$condition['op']) echo 'selected="selected"'; ?>>&lt;</option>
    </select>
    <label>
        <input name="_cred[notification][notifications][<?php echo $ii; ?>][event][condition][<?php echo $jj; ?>][value]" type="text" style="width:auto" value="<?php echo $condition['value']; ?>" />
    </label>
    <label class="cred_notification_field_only_if_changed">
        <input name="_cred[notification][notifications][<?php echo $ii; ?>][event][condition][<?php echo $jj; ?>][only_if_changed]" type="checkbox" value="1" <?php if ($condition['only_if_changed']) echo 'checked="checked"'; ?> />
        <span><?php _e('Only if field value has changed', 'wp-cred'); ?></span>
    </label>
    <a href="javascript:;" data-cred-bind="{ 
        event: 'click', 
        action: 'refreshFormFields' 
    }" class='icon-refresh cred-refresh-button' title="<?php echo esc_attr(__('Click to refresh (if settings changed)','wp-cred')); ?>">&nbsp;</a>&nbsp;
    <a class="icon-remove" data-cred-bind="{
	   event: 'click',
	   action: 'removeItem',
	   domRef: '#cred_notification_field_condition-<?php echo $ii; ?>-<?php echo $jj; ?>',
	   modelRef: '_cred[notification][notifications][<?php echo $ii; ?>][event][condition][<?php echo $jj; ?>]'
       }" title="<?php echo esc_attr(__( 'Remove', 'wp-cred' )); ?>">&nbsp;</a>
</p>
