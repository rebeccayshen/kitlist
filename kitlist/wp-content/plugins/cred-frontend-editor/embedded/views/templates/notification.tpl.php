<?php

/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk_new/embedded/views/templates/notification.tpl.php $
 * $LastChangedDate: 2014-07-18 14:52:48 +0200 (ven, 18 lug 2014) $
 * $LastChangedRevision: 25108 $
 * $LastChangedBy: marcin $
 *
 */

if (!defined('ABSPATH')){
    die('Security check');
}

if (!is_array($notification))  $notification=array();

$notification=CRED_Helper::mergeArrays(array(
        'event'=>array(
            'type'=>'form_submit',
            'post_status'=>'publish',
            'condition'=>array(
            ),
            'any_all'=>'ALL'
        ),
        'to'=>array(
            'type'=>array(),
            'wp_user'=>array(
                'to_type'=>'to',
                'user'=>''
            ),
            'mail_field'=>array(
                'to_type'=>'to',
                'address_field'=>'',
                'name_field'=>'',
                'lastname_field'=>''
            ),
			'user_id_field'=>array(
				'to_type'=>'to',
				'field_name'=>''
			),
            'specific_mail'=>array(
                'address'=>''
            )
        ),
        'from'=>array(
            'address'=>'',
            'name'=>''
        ),
        'mail'=>array(
            'subject'=>'',
            'body'=>''
        )
    ), $notification);
    
// make sure everything needign to be array, is array
$notification=(array)$notification;
$notification['event']=(array)$notification['event'];
$notification['to']=(array)$notification['to'];
if (!isset($notification['to']['type']))
    $notification['to']['type']=array();
if (!is_array($notification['to']['type']))
    $notification['to']['type']=(array)$notification['to']['type'];
?>
<div id="cred_notification_settings_panel-<?php echo $ii; ?>" class='cred_notification_settings_panel cred_validation_section'>

    <div  id="notification_validation_error-<?php echo $ii; ?>" class="cred-notification cred-error cred-section-validation-message" style="display:none">
        <p>
			<i class="icon-warning-sign"></i>
            <?php _e('This notification is not setup properly because some settings are not complete. Please review and select values.', 'wp-cred'); ?>
        </p>
    </div>

    <?php do_action('cred_admin_notification_fields_before', $form, $ii, $notification); ?>

	<fieldset class="cred-fieldset cred-notification-event-fieldset">
		<h4><i title="<?php echo esc_attr(__('Please select the notification trigger event', 'wp-cred')); ?>" id="notification_event_required-<?php echo $ii; ?>" class="icon-warning-sign" style="display:none;"></i>
        <?php _e('When to send this notification:','wp-cred'); ?>
        </h4>
        
		<?php do_action('cred_admin_notification_notify_event_options_before', $form, array($ii, "_cred[notification][notifications][$ii][event][type]", $notification['event']['type']), $notification); ?>
		
        <p>
			<label class='cred-label'>
                <input data-cred-bind="{
                                    validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_event_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    }
                                }" type='radio' class='cred-radio-10' name='_cred[notification][notifications][<?php echo $ii; ?>][event][type]' value='form_submit' <?php if ('form_submit'==$notification['event']['type']) echo 'checked="checked"'; ?> />
				<span><?php _e('When submitting the form','wp-cred'); ?></span>
			</label>
		</p>
		<p>
			<label class='cred-label'>
				<input data-cred-bind="{
                                    validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_event_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    }
                                }" type='radio' class='cred-radio-10' name='_cred[notification][notifications][<?php echo $ii; ?>][event][type]' value='post_modified' <?php if ('post_modified'==$notification['event']['type']) echo 'checked="checked"'; ?> />
				<span><?php _e('When post status changes to:','wp-cred'); ?></span>
			</label>
			<span data-cred-bind="{ action:'show', condition:'_cred[notification][notifications][<?php echo $ii; ?>][event][type]=post_modified' }">
				<select class="cred_when_status_changes" name="_cred[notification][notifications][<?php echo $ii; ?>][event][post_status]">
                    <option value='draft' <?php if ($notification['event']['post_status']=='draft') echo 'selected="selected"'; ?>><?php _e('Draft','wp-cred'); ?></option>
                    <option value='pending' <?php if ($notification['event']['post_status']=='pending') echo 'selected="selected"'; ?>><?php _e('Pending Review','wp-cred'); ?></option>
                    <option value='private' <?php if ($notification['event']['post_status']=='private') echo 'selected="selected"'; ?>><?php _e('Private','wp-cred'); ?></option>
                    <option value='publish' <?php if ($notification['event']['post_status']=='publish') echo 'selected="selected"'; ?>><?php _e('Published','wp-cred'); ?></option>
				</select>
			</span>
		</p>
		<?php do_action('cred_admin_notification_notify_event_options', $form, array($ii, "_cred[notification][notifications][$ii][event][type]", $notification['event']['type']), $notification); ?>
		<p>
			<label class='cred-label'>
				<input data-cred-bind="{
                                    validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_event_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    }
                                }" type='radio' class='cred-radio-10' name='_cred[notification][notifications][<?php echo $ii; ?>][event][type]' value='meta_modified' <?php if ('meta_modified'==$notification['event']['type']) echo 'checked="checked"'; ?> />
				<span><?php _e('When custom fields are modified','wp-cred'); ?></span>
			</label>
			<span style="display:inline-block;" data-cred-bind="{ action:'show', condition: '_cred[notification][notifications][<?php echo $ii; ?>][event][type]=meta_modified' }">
				<span style="display:inline-block;" class="cred-notification cred-error" data-cred-bind="{ action: 'show', condition: '_cred[notification][notifications][<?php echo $ii; ?>][event][condition]:count=0' }">
                    <i class="icon-warning-sign"></i>
                    <?php _e('You need to add fields to trigger notifications.', 'wp-cred'); ?>
				</span>
			</span>
		</p>

		<p class='cred-explain-text'><?php _e('Additional field conditions:', 'wp-cred'); ?></p>
		<div class="cred_notify_event_field_conditions_container">
			<div id="cred_notify_event_field_conditions_container_fields-<?php echo $ii; ?>" class="cred_notify_event_field_conditions_container_fields">
				<?php
					foreach (array_values($notification['event']['condition']) as $jj=>$condition) {
					   echo CRED_Loader::tpl('notification-condition', array(
							'condition'=>$condition,
							'ii'=>$ii,
							'jj'=>$jj
					   )); // not cache
					}
				?>
			</div>

			<div data-cred-bind="{ action: 'show', condition: '_cred[notification][notifications][<?php echo $ii; ?>][event][condition]:count>=2' }">
				<p>
					<label>
						<input type="radio" name="_cred[notification][notifications][<?php echo $ii; ?>][event][any_all]" value="ALL" <?php if ('ALL'==$notification['event']['any_all']) echo 'checked="checked"'; ?>/>
						<span><?php _e('All are true', 'wp-cred'); ?></span>
					</label>
					<label>
						<input type="radio" name="_cred[notification][notifications][<?php echo $ii; ?>][event][any_all]" value="ANY" <?php if ('ANY'==$notification['event']['any_all']) echo 'checked="checked"'; ?>/>
						<span><?php _e('Any is true', 'wp-cred'); ?></span>
					</label>
				</p>
			</div>

			<a href="javascript:;" data-cred-bind="{
												event: 'click',
												action: 'addItem',
												tmplRef: '#cred_notification_field_condition_template',
												modelRef: '_cred[notification][notifications][<?php echo $ii; ?>][event][condition][__j__]',
												domRef: '#cred_notify_event_field_conditions_container_fields-<?php echo $ii; ?>',
												replace: [
													'__'+'i__',  '<?php echo $ii; ?>',
													'__j__',  { next: '_cred[notification][notifications][<?php echo $ii; ?>][event][condition]'}
												]
									}" class="cred_add_notify_event_field_condition button"><?php _e('Add condition by field', 'wp-cred'); ?>
			</a>

		</div>

	</fieldset>

	<fieldset class="cred-fieldset cred-notification-recipient-fieldset">
		<h4><i title="<?php echo esc_attr(__('Please select recipients', 'wp-cred')); ?>" id="notification_recipient_required-<?php echo $ii; ?>" class="icon-warning-sign" style="display:none;">&nbsp;</i>&nbsp;
        <?php _e('Where to send this notification:','wp-cred'); ?><span class="cred-tip-link" data-pointer-content="#recipients_tip"><i class="icon-question-sign"></i></span>
        </h4>
		<?php do_action('cred_admin_notification_recipient_options_before', $form, array($ii, "_cred[notification][notifications][$ii][to][type][]", $notification['to']['type']), $notification); ?>
		<p>
			<label class='cred-label'>
				<input data-cred-bind="{ validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_recipient_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    } }" type='checkbox' class='cred-checkbox-10' name='_cred[notification][notifications][<?php echo $ii; ?>][to][type][]' value='wp_user' <?php if (in_array('wp_user', $notification['to']['type'])) echo 'checked="checked"'; ?> />
				<span><?php _e('Send notification to a WordPress user:','wp-cred'); ?></span>
			</label><br />
			<span data-cred-bind="{ action: 'show', condition: '_cred[notification][notifications][<?php echo $ii; ?>][to][type] has wp_user' }">
				<select name="_cred[notification][notifications][<?php echo $ii; ?>][to][wp_user][to_type]">
                    <option value="to" <?php if ('to'==$notification['to']['wp_user']['to_type']) echo 'selected="selected"'; ?>><?php _e('To:', 'wp-cred'); ?></option>
                    <option value="cc" <?php if ('cc'==$notification['to']['wp_user']['to_type']) echo 'selected="selected"'; ?>><?php _e('Cc:', 'wp-cred'); ?></option>
                    <option value="bcc" <?php if ('bcc'==$notification['to']['wp_user']['to_type']) echo 'selected="selected"'; ?>><?php _e('Bcc:', 'wp-cred'); ?></option>
				</select>
                <i title="<?php echo esc_attr(__('Please select a user', 'wp-cred')); ?>" id="notification_user_required-<?php echo $ii; ?>" class="icon-warning-sign" style="display:none;">&nbsp;</i>&nbsp;
                <input data-cred-bind="{                                      
                                        validate: {
                                            required: {
                                                actions: [
                                                    {   
                                                        action: 'validationMessage', 
                                                        domRef: '#notification_user_required-<?php echo $ii; ?>' 
                                                    },
                                                    {
                                                        action: 'validateSection' 
                                                    }
                                                ]
                                            }
                                        }, 
                                        event: 'init', 
                                        action: {
                                            suggest: {
                                                url: '<?php echo CRED_CRED::route('/Forms/suggestUserMail'); ?>', 
                                                param: 'user', 
                                                loader: '#cred_notification_user_mail_suggest_loader_<?php echo $ii; ?>'
                                            }                                            
                                         }
                                      }"
                        type="text" class="cred_mail_to_user" style="width:200px" name="_cred[notification][notifications][<?php echo $ii; ?>][to][wp_user][user]" placeholder="<?php echo esc_attr(__('-- Choose user --','wp-cred')); ?>" value="<?php if (isset($notification['to']['wp_user']['user'])) echo $notification['to']['wp_user']['user']; ?>"/>
                        <span style="display:none" id="cred_notification_user_mail_suggest_loader_<?php echo $ii; ?>" class='cred_ajax_loader_small_1'>&nbsp;</span>
			</span>
		</p>
                                
		<p>
			<label class='cred-label'>
				<input data-cred-bind="{ validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_recipient_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    } }" type='checkbox' class='cred-checkbox-10' name='_cred[notification][notifications][<?php echo $ii; ?>][to][type][]' value='mail_field' <?php if (in_array('mail_field', $notification['to']['type'])) echo 'checked="checked"'; ?> />
			<span><?php _e('Send notification to an email specified in a form field:','wp-cred'); ?></span>
			</label><br />
			<span data-cred-bind="{ action: 'show', condition: '_cred[notification][notifications][<?php echo $ii; ?>][to][type] has mail_field' }">
                <select name="_cred[notification][notifications][<?php echo $ii; ?>][to][mail_field][to_type]">
					<option value="to" <?php if ('to'==$notification['to']['mail_field']['to_type']) echo 'selected="selected"'; ?>><?php _e('To:', 'wp-cred'); ?></option>
					<option value="cc" <?php if ('cc'==$notification['to']['mail_field']['to_type']) echo 'selected="selected"'; ?>><?php _e('Cc:', 'wp-cred'); ?></option>
					<option value="bcc" <?php if ('bcc'==$notification['to']['mail_field']['to_type']) echo 'selected="selected"'; ?>><?php _e('Bcc:', 'wp-cred'); ?></option>
				</select>
				<i title="<?php echo esc_attr(__('Please select an email field', 'wp-cred')); ?>" id="notification_mail_field_required-<?php echo $ii; ?>" class="icon-warning-sign" style="display:none;">&nbsp;</i>&nbsp;
                <select data-cred-bind="{ validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_mail_field_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    }, action: 'set', what: { options: '_cred[_persistent_mail_fields]' } }" name="_cred[notification][notifications][<?php echo $ii; ?>][to][mail_field][address_field]">
					<optgroup label="<?php echo esc_attr(__('-- Choose email field --','wp-cred')); ?>">
						<option value='' disabled selected style='display:none;' data-dummy-option='1'><?php _e('-- Choose email field --','wp-cred'); ?></option>
						<?php
							if (''!=$notification['to']['mail_field']['address_field']) {
								?><option value="<?php echo $notification['to']['mail_field']['address_field']; ?>" selected="selected"><?php echo $notification['to']['mail_field']['address_field']; ?></option><?php
							}
						?>
					</optgroup>
				</select>
				<select data-cred-bind="{ action: 'set', what: { options: '_cred[_persistent_text_fields]' } }" name="_cred[notification][notifications][<?php echo $ii; ?>][to][mail_field][name_field]">
					<optgroup label="<?php echo esc_attr(__('-- Name field --','wp-cred')); ?>">
						<option value='' disabled selected style='display:none;' data-dummy-option='1'><?php _e('-- Name field --','wp-cred'); ?></option>
						<option value='###none###' data-dummy-option='1' <?php if ('###none###'==$notification['to']['mail_field']['name_field']) echo 'selected="selected"'; ?>><?php _e('-- none --','wp-cred'); ?></option>
						<?php
							if (''!=$notification['to']['mail_field']['name_field'] && '###none###'!=$notification['to']['mail_field']['name_field']) {
								?><option value="<?php echo $notification['to']['mail_field']['name_field']; ?>" selected="selected"><?php echo $notification['to']['mail_field']['name_field']; ?></option><?php
							}
						?>
					</optgroup>
				</select>
				<select data-cred-bind="{ action: 'set', what: { options: '_cred[_persistent_text_fields]' } }" name="_cred[notification][notifications][<?php echo $ii; ?>][to][mail_field][lastname_field]">
					<optgroup label="<?php echo esc_attr(__('-- Lastname field --','wp-cred')); ?>">
						<option value='' disabled selected style='display:none;' data-dummy-option='1'><?php _e('-- Lastname field --','wp-cred'); ?></option>
						<option value='###none###' data-dummy-option='1' <?php if ('###none###'==$notification['to']['mail_field']['lastname_field']) echo 'selected="selected"'; ?>><?php _e('-- none --','wp-cred'); ?></option>
						<?php
							if (''!=$notification['to']['mail_field']['lastname_field'] && '###none###'!=$notification['to']['mail_field']['lastname_field']) {
								?><option value="<?php echo $notification['to']['mail_field']['lastname_field']; ?>" selected="selected"><?php echo $notification['to']['mail_field']['lastname_field']; ?></option><?php
							}
						?>
					</optgroup>
				</select>&nbsp;
                <a href="javascript:;" data-cred-bind="{ event: 'click', action: 'refreshFormFields' }" class='icon-refresh cred-refresh-button' title="<?php echo esc_attr(__('Click to refresh (if settings changed)','wp-cred')); ?>">&nbsp;</a>
			</span>
		</p>
        
		<p>
			<label class='cred-label'>
				<input data-cred-bind="{ validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_user_id_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    } }" type='checkbox' class='cred-checkbox-10' name='_cred[notification][notifications][<?php echo $ii; ?>][to][type][]' value='user_id_field' <?php if (in_array('user_id_field', $notification['to']['type'])) echo 'checked="checked"'; ?> />
			<span><?php _e('Send notification to a WordPress user specified in a form field:','wp-cred'); ?></span>
			</label><br />
			<span data-cred-bind="{ action: 'show', condition: '_cred[notification][notifications][<?php echo $ii; ?>][to][type] has user_id_field' }">
                <select name="_cred[notification][notifications][<?php echo $ii; ?>][to][user_id_field][to_type]">
					<option value="to" <?php if ('to'==$notification['to']['user_id_field']['to_type']) echo 'selected="selected"'; ?>><?php _e('To:', 'wp-cred'); ?></option>
					<option value="cc" <?php if ('cc'==$notification['to']['user_id_field']['to_type']) echo 'selected="selected"'; ?>><?php _e('Cc:', 'wp-cred'); ?></option>
					<option value="bcc" <?php if ('bcc'==$notification['to']['user_id_field']['to_type']) echo 'selected="selected"'; ?>><?php _e('Bcc:', 'wp-cred'); ?></option>
				</select>
				<i title="<?php echo esc_attr(__('Please select a user id field', 'wp-cred')); ?>" id="notification_user_id_required-<?php echo $ii; ?>" class="icon-warning-sign" style="display:none;">&nbsp;</i>&nbsp;
                <select data-cred-bind="{ validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_user_id_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    }, action: 'set', what: { options: '_cred[_persistent_user_id_fields]' } }" name="_cred[notification][notifications][<?php echo $ii; ?>][to][user_id_field][field_name]">
					<optgroup label="<?php echo esc_attr(__('-- Choose user id field --','wp-cred')); ?>">
						<option value='' disabled selected style='display:none;' data-dummy-option='1'><?php _e('-- Choose user id field --','wp-cred'); ?></option>
						<?php
							if (''!=$notification['to']['user_id_field']['field_name']) {
								?><option value="<?php echo $notification['to']['user_id_field']['field_name']; ?>" selected="selected"><?php echo $notification['to']['user_id_field']['field_name']; ?></option><?php
							}
						?>
					</optgroup>
				</select>&nbsp;
                <a href="javascript:;" data-cred-bind="{ event: 'click', action: 'refreshFormFields' }" class='icon-refresh cred-refresh-button' title="<?php echo esc_attr(__('Click to refresh (if settings changed)','wp-cred')); ?>">&nbsp;</a>
			</span>
		</p>
        
		<p class="cred-label-holder">
			<label class='cred-label'>
                <input data-cred-bind="{ validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_recipient_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    } }" type='checkbox' class='cred-checkbox-10' name='_cred[notification][notifications][<?php echo $ii; ?>][to][type][]' value='specific_mail' <?php if (in_array('specific_mail', $notification['to']['type'])) echo 'checked="checked"'; ?> />
				<span><?php _e('Send notification to a specific email address:','wp-cred'); ?></span>
			</label>
		</p>

		<div data-cred-bind="{ action: 'show', condition: '_cred[notification][notifications][<?php echo $ii; ?>][to][type] has specific_mail' }">
			<p class="cred-label-holder">
				<label for="cred-additional-recipients-<?php echo $ii; ?>"><?php _e('Additional Recipients:','wp-cred'); ?></label>
				<span class="cred-tip-link" data-pointer-content="#additional_recipients_tip">
					<i class="icon-question-sign"></i>
				</span>
			</p>
			<i title="<?php echo esc_attr(__('Please enter an email address', 'wp-cred')); ?>" id="notification_mail_required-<?php echo $ii; ?>" class="icon-warning-sign" style="display:none;">&nbsp;</i>&nbsp;
            <input data-cred-bind="{
                                    validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_mail_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    }
                                }" type="text" id="cred-additional-recipients-<?php echo $ii; ?>" name='_cred[notification][notifications][<?php echo $ii; ?>][to][specific_mail][address]' value="<?php echo $notification['to']['specific_mail']['address']; ?>" />
		</div>

               <p>
			<label class='cred-label'>
				<input data-cred-bind="{
                                    validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage2', domRef: '#notification_recipient_required-<?php echo $ii; ?>' },
                                            ]
                                        }
                                    }
                                }" type='checkbox' class='cred-checkbox-<?php echo $ii; ?>' id='send_to_author' name='_cred[notification][notifications][<?php echo $ii; ?>][to][author]' value='author' <?php if (isset($notification['to']['author'])) echo 'checked="checked"'; ?> />
				<span><?php _e('Send notification to Author','wp-cred'); ?></span>
			</label>
               </p>                
                
	</fieldset>

    <?php do_action('cred_admin_notification_recipient_options_after', $form, array($ii, "_cred[notification][notifications][$ii][to][type][]", $notification['to']['type']), $notification); ?>

	<fieldset class="cred-fieldset">
		<h4><?php _e('Set From details','wp-cred'); ?>:</h4>
		<p class="cred-label-holder">
			<label for="cred-notification-sender-email-<?php echo $ii; ?>">
				<?php _e('Email (leave blank for default):','wp-cred'); ?><br />
			</label>
		</p>
		<input type="text" id="cred-notification-sender-email-<?php echo $ii; ?>" name='_cred[notification][notifications][<?php echo $ii; ?>][from][address]' value="<?php echo $notification['from']['address']; ?>" />
		<p class="cred-label-holder">
			<label for="cred-notification-sender-name-<?php echo $ii; ?>">
				<?php _e('Name (leave blank for default):','wp-cred'); ?><br />
			</label>
		</p>
		<input type="text" id="cred-notification-sender-name-<?php echo $ii; ?>" name='_cred[notification][notifications][<?php echo $ii; ?>][from][name]' value="<?php echo $notification['from']['name']; ?>" />
	</fieldset>

	<fieldset class="cred-fieldset">
		<h4><?php _e('Subject of emails:','wp-cred'); ?></h4>
		<p class="cred-label-holder">
			<label>
				<?php _e('Notification mail subject:','wp-cred'); ?>
			</label>
		</p>
		<div id="cred_mail_subject_placeholders-<?php echo $ii;?>" data-editor="credmailsubject<?php echo $ii;?>" class="wp-media-buttons" style="display:inline-block;position:relative;margin:0;padding:0;vertical-align:middle;">
        <?php echo CRED_Helper::getMediaButtons("credmailsubject{$ii}", array(
                    'no_media_button'=>true, 
                    'extra'=>CRED_Loader::tpl('notification-subject-codes', array(
                            'area_id'=>"credmailsubject{$ii}", 
                            'form'=>$form, 
                            'ii'=>$ii, 
                            'notification'=>$notification
                            ))
                    )); ?>
        </div>
        
        <p class="cred-label-holder">
			<i title="<?php echo esc_attr(__('Please enter a title', 'wp-cred')); ?>" id="credmailsubject_required-<?php echo $ii; ?>" class="icon-warning-sign" style="display:none;">&nbsp;</i>&nbsp;
            <input data-cred-bind="{
                                    validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#credmailsubject_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    }
                                }" type="text" id="credmailsubject<?php echo $ii ?>" style="position:relative;width:95%;" name="_cred[notification][notifications][<?php echo $ii; ?>][mail][subject]" value="<?php echo $notification['mail']['subject']; ?>" />
		</p>
	</fieldset>

   <fieldset class="cred-fieldset">
    	<h4><?php _e('Body of emails','wp-cred'); ?></h4>
		<div id="cred_mail_body_placeholders-<?php echo $ii;?>" class="cred-label-holder wpcf-wysiwyg">
			<label><?php _e( 'Notification mail body:', 'wp-cred' ); ?></label>
            <?php echo CRED_Helper::getRichEditor(
                        "credmailbody{$ii}", 
                        "_cred[notification][notifications][{$ii}][mail][body]", 
                        $notification['mail']['body'], 
                        array(
                            'wpautop'=>false, 
                            'teeny'=>true, 
                            'editor_height' => 200,
							'editor_class' => 'wpcf-wysiwyg' // add class for Types and Views use
                        ), 
                        array(
                            'custom_media_buttons' => true, 
                            'extra'=>CRED_Loader::tpl('notification-body-codes', array(
                                        'area_id'=>"credmailbody{$ii}", 
                                        'form'=>$form, 
                                        'ii'=>$ii, 
                                        'notification'=>$notification
                                    ))
                        )); ?>
		</div>

    </fieldset>

    <?php do_action('cred_admin_notification_fields_after', $form, $ii, $notification); ?>

    <?php if ($enableTestMail) : ?>
    <p>
         <a class='button' href='javascript:;' data-cred-bind="{
                                        event: 'click',
                                        action: 'show',
                                        domRef: '#cred_notification_test_container_<?php echo $ii; ?>'
                                }"><?php _e('Send a test email','wp-cred'); ?></a>
    </p>
    
    <div style="display:none;" id="cred_notification_test_container_<?php echo $ii; ?>" class='cred-notification-test-container'>
         <label>
            <span style="margin-right:10px;"><?php _e('Send a test notification to:', 'wp-cred'); ?></span> 
            <input type="text" class="js-test-notification-to" data-sendbutton="#send_test_notification_<?php echo $ii; ?>" id="test_notification_to_<?php echo $ii; ?>" style="width:180px;" value="" placeholder="<?php _e('Enter an email address', 'wp-cred'); ?>" />
        </label>
        <a disabled="disabled" class="button js-send-test-notification" id="send_test_notification_<?php echo $ii; ?>" data-cancelbutton="#cancel_test_notification_<?php echo $ii; ?>" data-results="#send_test_notification_results_<?php echo $ii; ?>" data-loader="#send_test_notification_loader_<?php echo $ii; ?>" data-notification="<?php echo $ii; ?>" data-addressfield="#test_notification_to_<?php echo $ii; ?>"><?php _e('Send now', 'wp-cred'); ?></a>
        <a class='button' id="cancel_test_notification_<?php echo $ii; ?>" href='javascript:;' data-cred-bind="{
                                    event: 'click',
                                    action: 'hide',
                                    delay: 100,
                                    domRef: '#cred_notification_test_container_<?php echo $ii; ?>'
                            }"><?php _e('Cancel', 'wp-cred'); ?></a>
        <span style="display:none" id="send_test_notification_loader_<?php echo $ii; ?>" class='cred_ajax_loader_small_1'>&nbsp;</span>        
        <div id="send_test_notification_results_<?php echo $ii; ?>">
        </div>
    </div>
    <?php endif; ?>
    
    <p class='cred-notification-remove-container'>
         <a class='button cred-remove-notification cred-remove-button1' href='javascript:;' data-cred-bind="{
                                        event: 'click',
                                        action: 'removeItem',
                                        confirm: '<?php _e('Are you sure you want to remove this notification?', 'wp-cred'); ?>',
                                        domRef: '#cred_notification_settings_panel-<?php echo $ii; ?>',
                                        modelRef: '_cred[notification][notifications][<?php echo $ii; ?>]'
                                }">
		<i class="icon-remove"></i> <?php _e('Remove this notification','wp-cred'); ?>
		</a>
    </p>
</div>
