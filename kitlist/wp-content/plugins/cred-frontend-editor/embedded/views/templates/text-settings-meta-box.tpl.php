<?php if (!defined('ABSPATH'))  die('Security check'); ?>
<p class='cred-explain-text'>
	<?php _e('These are texts that display for different form operations. If you are using WPML, you will be able to translate them via WPML->String Translation.', 'wp-cred'); ?>
</p>
<table class="cred-form-texts">
<tbody>
<?php
foreach ($messages as $msgid=>$msg)
{
    if (isset($descriptions[$msgid])) {
    ?><tr>
        <td class="cred-form-texts-desc"><?php echo $descriptions[$msgid]; ?></td>
        <td class="cred-form-texts-msg"><input name='_cred[extra][messages][<?php echo $msgid; ?>]' type='text' value='<?php echo esc_attr($msg); ?>' /></td>
    </tr><?php }
}
?>
</tbody>
</table>