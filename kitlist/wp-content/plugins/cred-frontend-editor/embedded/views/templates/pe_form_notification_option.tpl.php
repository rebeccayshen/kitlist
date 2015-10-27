<?php if (!defined('ABSPATH'))  die('Security check'); ?>
        <p class="cred_post_expiration_options">
			<label class="cred-label">
				<input data-cred-bind="{
                                    validate: {
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_event_required-<?php echo $ii; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        }
                                    }
                                }" type="radio" class="cred-radio-10" name="_cred[notification][notifications][<?php echo $ii; ?>][event][type]" value="expiration_date" <?php if ('expiration_date'==$notification['event']['type']) echo 'checked="checked"'; ?> />
				<span><?php _e('Number of days before the automatic expiration date:', $cred_post_expiration->getLocalizationContext()); ?></span>
			</label>
			<span data-cred-bind="{ action:'show', condition:'_cred[notification][notifications][<?php echo $ii; ?>][event][type]=expiration_date' }">
				<select class="cred_when_status_changes" name="_cred[notification][notifications][<?php echo $ii; ?>][event][expiration_date]">
					<?php for ($i = 0; $i <= 6; $i++) { ?>
                    <option value="<?php echo $i; ?>" <?php if ($notification['event']['expiration_date']==$i) echo 'selected="selected"'; ?>><?php _e($i, $cred_post_expiration->getLocalizationContext()); ?></option>
                    <?php } ?>
				</select>
			</span>
		</p>