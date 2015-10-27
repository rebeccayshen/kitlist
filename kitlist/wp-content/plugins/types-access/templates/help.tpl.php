<?php
if (!defined('ABSPATH'))  die('Security check');
if(!current_user_can('manage_options')) {
    die('Access Denied');
}

?>
<div class="wrap">
    <h2><?php _e('Access Help','wpcf-access') ?></h2>
    <h3 style="margin-top:3em;"><?php _e('Documentation and Support', 'wpcf-access'); ?></h3>
    <ul>
        <li><?php printf('<a target="_blank" href="http://wp-types.com/documentation/user-guides/?utm_source=accessplugin&utm_campaign=access&utm_medium=help-page&utm_term=Access manual#Access"><strong>%s</strong></a>'.__(' - everything you need to know about using Access', 'wpcf-access'),__('User Guides', 'wpcf-access')); ?></li>
        <li><?php printf('<a target="_blank" href="http://discover-wp.com/"><strong>%s</strong></a>'.__(' - learn to use Access by experimenting with fully-functional learning sites','wpcf-access'),__('Discover WP','wpcf-access') ); ?></li>
        <li><?php printf('<a target="_blank" href="http://wp-types.com/forums/forum/support-2/?utm_source=accessplugin&utm_campaign=access&utm_medium=help-page&utm_term=Support forum"><strong>%s</strong></a>'.__(' - online help by support staff', 'wpcf-access'),__('Support forum', 'wpcf-access') ); ?></li>
    </ul>
    <h3 style="margin-top:3em;"><?php _e('Debug information', 'wp-cred'); ?></h3>
    <p><?php
    printf(
    __( 'For retrieving debug information if asked by a support person, use the <a href="%s">debug information</a> page.', 'wpcf-access' ),
    admin_url('admin.php?page=types_access_debug')
    );
    ?></p>
</div>
