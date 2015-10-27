<?php if (!defined('ABSPATH'))  die('Security check'); ?>
<div class="cred-fieldset">
    <p>
        <label>
            <input type="checkbox" name="cred_pe[<?php echo $time_field_name; ?>][enable]" value="1" <?php if (!empty( $post_expiration_time)) echo 'checked="checked"'; ?>>
            <span><?php _e('Automatic expire date for this post', $cred_post_expiration->getLocalizationContext()); ?></span>
        </label>
    </p>
</div>
<div class="cred_post_expiration_panel js_cred_post_expiration_panel" style="display: none;">
<?php
wp_nonce_field('cred-post-expiration-date', 'cred-post-expiration-nonce');
$field = array(
	'#type' => 'textfield',
	'#id' => $post_expiration_slug . '-datepicker',
	'#title' => __( 'Choose the date for expiration of the post', $cred_post_expiration->getLocalizationContext() ),
	'#name' => '',
	'#value' => $values['date'],
	'#attributes' => array(
			'class' => 'js-cred-post-expiration-datepicker',
			'style' => 'width:150px;',
			'readonly' => 'readonly',
			'title' => esc_attr( __( 'Select date', $cred_post_expiration->getLocalizationContext() ) )
		),
	'#inline' => true,
);
$field_aux = array(
	'#type' => 'hidden',
	'#id' => $post_expiration_slug . '-datepicker-aux',
	'#attributes' => array('class' => 'js-wpt-date-auxiliar' ),
	'#name' => 'cred_pe[' . $time_field_name . '][date]',
	'#value' => $post_expiration_time,
);
$field_output = $cred_post_expiration->cred_pe_form_simple( array($field['#id'] => $field) );
$field_output .= $cred_post_expiration->cred_pe_form_simple( array($field_aux['#id'] => $field_aux) );
//$field_output = wpcf_form_simple( array($field['#id'] => $field) );
//$field_output .= wpcf_form_simple( array($field_aux['#id'] => $field_aux) );
echo $field_output;
$delete_date_image = CRED_ASSETS_URL . '/images/delete-2.png';
$delete_date_image = apply_filters( 'wptoolset_filter_wptoolset_delete_date_image', $delete_date_image );
$clear_date_showhide = '';
if ( empty( $post_expiration_time ) ) {
	$clear_date_showhide = 'display:none;';
}
echo '<img src="' . $delete_date_image . '" title="' . esc_attr( __( 'Clear date', $cred_post_expiration->getLocalizationContext() ) ) . '" alt="' . esc_attr( __( 'Clear date', $cred_post_expiration->getLocalizationContext() ) ) . '" class="js-cred-pe-date-clear cred-pe-date-clear" style="cursor:pointer;' . $clear_date_showhide . '" />';
?>
<p>
<select name="cred_pe[<?php echo $time_field_name; ?>][hours]" id="<?php echo $time_field_name; ?>-hours" class="js-cred-pe-date-hour">
<?php for ( $i = 0; $i < 24 ; $i++ ) {?>
    <option value="<?php echo $i; ?>" <?php if (intval($values['hours']) === $i) echo 'selected="selected"'; ?>><?php echo sprintf("%02d", $i) .':00'; ?></option>
<?php } ?>
</select>
<label for="<?php echo $time_field_name; ?>-hours"><?php _e( 'Hours', $cred_post_expiration->getLocalizationContext() ); ?></label>
<select name="cred_pe[<?php echo $time_field_name; ?>][minutes]" id="<?php echo $time_field_name; ?>-minutes" class="js-cred-pe-date-minute">
<?php for ( $i = 0; $i < 4 ; $i++ ) {?>
    <option value="<?php echo $i * 15 ; ?>" <?php if (intval($values['minutes']) === $i * 15) echo 'selected="selected"'; ?>><?php echo sprintf("%02d", $i * 15) ; ?></option>
<?php } ?>
</select>
<label for="<?php echo $time_field_name; ?>-minutes"><?php _e( 'Minutes', $cred_post_expiration->getLocalizationContext() ); ?></label>
</p>
        <fieldset class="cred-fieldset">
        
            <p class="cred-label-holder">
                <label for="cred_post_expiration_post_status"><?php _e('After expiration change the status of the post to:',$cred_post_expiration->getLocalizationContext()); ?></label>
            </p>
<?php
$options = apply_filters('cred_pe_post_expiration_post_status', $cred_post_expiration->getActionPostStatus());
?>
            <select id="cred_post_expiration_post_status" name="cred_pe[<?php echo $action_field_name; ?>][post_status]">
            <?php foreach ($options as $value => $text) { ?>
                <option value="<?php echo $value; ?>" <?php if ($value == $post_expiration_action['post_status']) echo 'selected="selected"'; ?>><?php echo $text; ?></option>
                <?php } ?>
            </select>
            
		</fieldset>
</div>