<?php if (!defined('ABSPATH'))  die('Security check'); ?>
<p class='cred-explain-text'></p>
<a class='cred-help-link' style='position:absolute;top:5px;right:10px;z-index:1000' href='<?php echo $help['css_settings']['link']; ?>' target='<?php echo $help_target; ?>' title="<?php echo esc_attr($help['css_settings']['text']); ?>">
    <i class="icon-question-sign"></i>
    <span><?php echo $help['css_settings']['text']; ?></span>
</a>
<div id='cred_extra_settings_panel_container' class='cred_extra_settings_panel_container'>
    <div class='cred_extra_css_settings_panel' style='position:relative;'>
    <p>CSS:</p>
    <div class="cred-editor-wrap">
    <textarea id='cred-extra-css-editor' name='_cred[extra][css]' style="position:relative;overflow-y:auto;" class="cred-extra-css-editor<?php if ($css && !empty($css)) echo ' cred-always-open'; ?>"><?php if ($css && !empty($css)) echo $css; ?></textarea>
    <div class="cred-content-resize-handle" title="<?php _e('Resize', 'wp-cred'); ?>"><br></div>
    </div>
    </div>
    <br />
    <div class='cred_extra_js_settings_panel' style='position:relative;'>
    <p>Javascript:</p>
    <div class="cred-editor-wrap">
    <textarea id='cred-extra-js-editor' name='_cred[extra][js]' style="position:relative;overflow-y:auto;" class="cred-extra-js-editor<?php if ($js && !empty($js)) echo ' cred-always-open'; ?>"><?php if ($js && !empty($js)) echo $js; ?></textarea>
    <div class="cred-content-resize-handle" title="<?php _e('Resize', 'wp-cred'); ?>"><br></div>
    </div>
    </div>
</div>