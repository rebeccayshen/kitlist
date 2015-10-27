<?php
if (!defined('ABSPATH'))  die('Security check');
if(!current_user_can('manage_options')) {
	die('Access Denied');
}

$action_url=admin_url('admin-ajax.php').'?action=access_import_export';
?>
<div class="wrap">
    <?php screen_icon('wpcf-access'); ?>
    <h2><?php _e('Access Settings','wpcf-access') ?></h2>
    <br />
    
    <form name="access-export-form" action="<?php echo $action_url; ?>" target="_blank" method="post">
    <?php wp_nonce_field('access-export-form','access-export-form'); ?>
    <table class="widefat">
    <thead>
        <tr>
            <th><?php _e('Export Access Settings','wpcf-access'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <input class="button button-primary" type="submit" value="<?php echo esc_attr(__('Export','wpcf-access')); ?>" name="access-export" />
            </td>
        </tr>
    </tbody>
    </table>
    </form>
    
    <br /><br />
    
    <form name="access-import-form" enctype="multipart/form-data" action="" method="post">
    <?php wp_nonce_field('access-import-form','access-import-form'); ?>
    <table class="widefat">
    <thead>
        <tr>
            <th><?php _e('Import Access Settings','wpcf-access'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <?php
                if (isset($results))
                {
                if (is_wp_error($results))
                {
                    echo '<div style="color:red;font-weight:bold">'.$results->get_error_message($results->get_error_code()).'</div>';
                }
                elseif (is_array($results))
                {
                ?>
            <ul style="font-style:italic">
                <li><?php _e('Settings Imported','wpcf-access'); ?> : <?php echo $results['new']; ?></li>
                <li><?php _e('Settings Overwritten','wpcf-access'); ?> : <?php echo $results['updated']; ?></li>
                <li><?php _e('Settings Deleted','wpcf-access'); ?> : <?php echo $results['deleted']; ?></li>
            </ul>
            <?php } } ?>
                <ul>
                    <li>
                        <label><input type="checkbox" name="access-overwrite-existing-settings" value="1" />
                        <span><?php _e('Overwrite existing settings','wpcf-access'); ?></span></label>
                        <span class="access-tip-link" data-pointer-content="#overwrite_tip">
                        <i class="icon-question-sign"></i>
                        </span>                    
                    </li>
                    <li>
                        <label><input type="checkbox" name="access-remove-not-included-settings" value="1" />
                        <span><?php _e('Delete Access settings not included in the imported file','wpcf-access'); ?></span></label>
                        <span class="access-tip-link" data-pointer-content="#delete_tip">
                        <i class="icon-question-sign"></i>
                        </span>                    
                    </li>
                </ul>
                <label for="upload-cred-file"><?php __('Select the xml file to upload:&nbsp;','wpcf-access'); ?></label>

                <input type="file" name="access-import-file" class="js-wpcf-access-import-file" />

                <input class="button button-primary js-wpcf-access-import-button" data-error="<?php echo esc_attr(__('Please add file.','wpcf-access')); ?>" type="submit" value="<?php echo esc_attr(__('Import','wpcf-access')); ?>" name="access-import" />
                </td>
            </tr>
        </tbody>
    </table>
    </form>
</div>
<!-- Tips texts -->
<div style="display:none">
    <div id="overwrite_tip">
        <h3><?php _e('Overwrite existing settings', 'wpcf-access'); ?></h3>
        <p><?php _e('When you import from a site that has different settings, selecting this option would update and overwrite the existing settings.', 'wpcf-access'); ?></p>
    </div>
    <div id="delete_tip">
        <h3><?php _e('Delete Access settings not included', 'wpcf-access'); ?></h3>
        <p><?php _e('When this option is enabled, any settings that are made in your site and not included in the site you are importing from will be deleted. This allows replacing entire site settings with new settings.', 'wpcf-access'); ?></p>
    </div>
</div>
<!-- /End tips texts -->

<script type="text/javascript">
/*<![CDATA[*/
(function($, utils){
    $(function(){
        // handle pointer tips
        $('.access-tip-link .icon-question-sign').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            
            var $el=$(this), $elp=$el.parent();
            
            if ($el.hasClass('active'))
            {
                $el[0]._pointer && $el[0]._pointer.pointer('close');
                return;
            }
            
            $el.addClass('active');
            // GUI framework handles pointers now
            $el[0]._pointer=utils.Popups.pointer($el, {
                message: $($elp.data('pointer-content')).html(),
                callback: function(){
                    $el.removeClass('active');
                }
            });
        });
    });
})(jQuery, access_utils);
/*]]>*/
</script>