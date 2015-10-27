<?php

/**
 * Admin Helper Class
 *
 */
final class CRED_Admin_Helper {

    public static function setupAdmin() {
        global $wp_version, $post;

        // determine current admin page
        CRED_Helper::getAdminPage(array(
            'post_type' => CRED_FORMS_CUSTOM_POST_NAME,
            'base' => 'admin.php',
            'pages' => array('view-archives-editor', 'views-editor', 'CRED_Forms', 'CRED_Fields', 'CRED_Settings', 'CRED_Help')
        ));

        CRED_Helper::getAdminPage(array(
            'post_type' => CRED_USER_FORMS_CUSTOM_POST_NAME,
            'base' => 'admin.php',
            'pages' => array('view-archives-editor', 'views-editor', 'CRED_User_Forms', 'CRED_Fields', 'CRED_Settings', 'CRED_Help')
        ));

        // add plugin menus
        add_action('admin_menu', array(__CLASS__, 'addMenuItems'));

        CRED_Helper::setJSAndCSS();

        if (version_compare($wp_version, '3.2', '>=')) {
            if (isset($post) && ($post->post_type == CRED_FORMS_CUSTOM_POST_NAME ||
                    $post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME))
                remove_action('pre_post_update', 'wp_save_post_revision');
        }

        /**
         * add debug information
         */
        add_filter('icl_get_extra_debug_info', array(__CLASS__, 'getExtraDebugInfo'));
    }

    // add custom classes to our metaboxes, so they can be handled as needed
    public static function addMetaboxClasses($classes) {
        array_push($classes, 'cred_related');
        return $classes;
    }

    public static function addMetaboxClasses2($classes) {
        array_push($classes, 'cred_related');
        return $classes;
    }

    /**
     * add setting to debug
     * get setting functio
     */
    public static function getExtraDebugInfo($extra_debug) {
        $sm = CRED_Loader::get('MODEL/Settings');
        $extra_debug['CRED'] = $sm->getSettings();
        if (isset($extra_debug['CRED']['recaptcha'])) {
            unset($extra_debug['CRED']['recaptcha']);
        }
        return $extra_debug;
    }

    // setup CRED menus in admin
    public static function addMenuItems() {
        $menu_label = CRED_NAME; //__( 'CRED','wp-cred' );

        $url = CRED_CRED::getNewFormLink(false); //'post-new.php?post_type='.CRED_FORMS_CUSTOM_POST_NAME;
        $cf_url = CRED_CRED::getNewUserFormLink(false); //'post-new.php?post_type='.CRED_FORMS_CUSTOM_POST_NAME;

        $cred_index = 'CRED_Forms'; //CRED_VIEWS_PATH2.'/forms.php';
        add_menu_page($menu_label, $menu_label, CRED_CAPABILITY, $cred_index, array(__CLASS__, 'FormsMenuPage'), 'none');
        // allow 3rd-party menu items to be included
        do_action('cred_admin_menu_top', $cred_index);
        add_submenu_page($cred_index, __('Post Forms', 'wp-cred'), __('Post Forms', 'wp-cred'), CRED_CAPABILITY, 'CRED_Forms', array(__CLASS__, 'FormsMenuPage'));
        add_submenu_page($cred_index, __('New Post Form', 'wp-cred'), __('New Post Form', 'wp-cred'), CRED_CAPABILITY, $url);

        // CredUserForms
        add_submenu_page($cred_index, __('User Forms', 'wp-cred'), __('User Forms', 'wp-cred'), CRED_CAPABILITY, 'CRED_User_Forms', array(__CLASS__, 'UserFormsMenuPage'));
        add_submenu_page($cred_index, __('New User Form', 'wp-cred'), __('New User Form', 'wp-cred'), CRED_CAPABILITY, $cf_url);
        // allow 3rd-party menu items to be included
        do_action('cred_admin_menu_after_forms', $cred_index);
        add_submenu_page($cred_index, __('Custom Fields', 'wp-cred'), __('Custom Fields', 'wp-cred'), CRED_CAPABILITY, 'CRED_Fields', array(__CLASS__, 'FieldsMenuPage'));
        // allow 3rd-party menu items to be included
        do_action('cred_admin_menu_after_fields', $cred_index);
        add_submenu_page($cred_index, __('Settings/Import', 'wp-cred'), __('Settings/Import', 'wp-cred'), CRED_CAPABILITY, 'CRED_Settings', array(__CLASS__, 'SettingsMenuPage'));
        // allow 3rd-party menu items to be included
        do_action('cred_admin_menu_after_settings', $cred_index);
        $hook = add_submenu_page($cred_index, __('Help', 'wp-cred'), __('Help', 'wp-cred'), CRED_CAPABILITY, 'CRED_Help', array(__CLASS__, 'HelpMenuPage'));
        add_submenu_page(
                $hook, __('Debug information', 'wp-cred'), __('Debug information', 'wp-cred'), CRED_CAPABILITY, 'cred-debug-information', array(__CLASS__, 'DebugMenuPage')
        );
        // allow 3rd-party menu items to be included
        do_action('cred_admin_menu_bottom', $cred_index);

        CRED_Helper::$screens = array('toplevel_page_CRED_Forms', 'toplevel_page_CRED_User_Forms', 'cred_page_CRED_Forms', 'cred_page_CRED_User_Forms', 'cred_page_CRED_Fields');
        foreach (CRED_Helper::$screens as $screen) {
            add_action("load-" . $screen, array(__CLASS__, 'addScreenOptions'));
        }
    }

    // add screen options to table screens
    public static function addScreenOptions() {
        $screen = get_current_screen();
        if (!is_array(CRED_Helper::$screens) || !in_array($screen->id, CRED_Helper::$screens))
            return;

        $args = array(
            'label' => __('Per Page', 'wp-cred'),
            'default' => 10,
            'option' => 'cred_per_page'
        );
        add_screen_option('per_page', $args);

        // instantiate table now to take care of column options
        switch ($screen->id) {
            case 'toplevel_page_CRED_Forms':
            case 'cred_page_CRED_Forms':
                CRED_Loader::get('TABLE/Forms');
                break;
            case 'toplevel_page_CRED_User_Forms':
            case 'cred_page_CRED_User_Forms':
                CRED_Loader::get('TABLE/UserForms');
                break;
            case 'cred_page_CRED_Fields':
                CRED_Loader::get('TABLE/Custom_Fields');
                break;
        }
    }

    public static function FormsMenuPage() {
        CRED_Loader::load('VIEW/forms');
    }

    public static function UserFormsMenuPage() {
        CRED_Loader::load('VIEW/user_forms');
    }

    public static function FieldsMenuPage() {
        CRED_Loader::load('VIEW/custom_fields');
    }

    public static function SettingsMenuPage() {
        CRED_Loader::load('VIEW/settings');
    }

    public static function HelpMenuPage() {
        CRED_Loader::load('VIEW/help');
    }

    public static function DebugMenuPage() {
        include_once WPTOOLSET_COMMON_PATH . '/debug/debug-information.php';
    }

    // metabox placeholder for Module Manager plugin
    public static function addModManMetaBox($form) {
        $key = ($form->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) ? _CRED_MODULE_MANAGER_USER_KEY_ : _CRED_MODULE_MANAGER_KEY_;
        $element = array('id' => $key . $form->ID, 'title' => $form->post_title, 'section' => $key);
        do_action('wpmodules_inline_element_gui', $element);
    }

    // placeholder
    public static function addFormContentMetaBox($form) {
        
    }

    public static function addFormSettingsMetaBox($form, $args) {
        //cred_log($args);
        $settings = $args['args']['form_settings']->form;

        $page_query = new WP_Query(array('post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1));
        ob_start();
        if ($page_query->have_posts()) {
            while ($page_query->have_posts()) {
                $page_query->the_post();
                ?>
                <option value="<?php the_ID() ?>" <?php if (isset($settings['action_page']) && $settings['action_page'] == get_the_ID()) echo 'selected="selected"'; ?>><?php the_title(); ?></option>
                <?php
            }
        }
        // just in case
        wp_reset_postdata();
        $form_action_pages = ob_get_clean();
        echo CRED_Loader::tpl('form-settings-meta-box', array(
            'form' => $form,
            'settings' => $settings,
            //'cred_themes'=>array('minimal'=>__('Simple CSS','wp-cred'),'styled'=>__('Bootstrap CSS','wp-cred')),
            'form_action_pages' => $form_action_pages,
            'help' => CRED_CRED::$help,
            'help_target' => CRED_CRED::$help_link_target
        ));
    }

    public static function addUserFormSettingsMetaBox($form, $args) {
        //cred_log($args);
        $settings = $args['args']['form_settings']->form;

        $page_query = new WP_Query(array('post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1));
        ob_start();
        if ($page_query->have_posts()) {
            while ($page_query->have_posts()) {
                $page_query->the_post();
                ?>
                <option value="<?php the_ID() ?>" <?php if (isset($settings['action_page']) && $settings['action_page'] == get_the_ID()) echo 'selected="selected"'; ?>><?php the_title(); ?></option>
                <?php
            }
        }
        global $wp_roles;
        // just in case
        wp_reset_postdata();
        $form_action_pages = ob_get_clean();
        echo CRED_Loader::tpl('user-form-settings-meta-box', array(
            'form' => $form,
            'settings' => $settings,
            //'cred_themes'=>array('minimal'=>__('Simple CSS','wp-cred'),'styled'=>__('Bootstrap CSS','wp-cred')),
            'form_action_pages' => $form_action_pages,
            'user_roles' => $wp_roles->roles,
            'help' => CRED_CRED::$help,
            'help_target' => CRED_CRED::$help_link_target
        ));
    }

    public static function addPostTypeMetaBox($form, $args) {
        $settings = $args['args']['form_settings'];
        echo CRED_Loader::tpl('post-type-meta-box', array(
            'post_types' => CRED_Loader::get('MODEL/Fields')->getPostTypes(),
            'settings' => $settings,
            'help' => CRED_CRED::$help,
            'help_target' => CRED_CRED::$help_link_target
        ));
    }

    public static function addNotificationMetaBox($form, $args) {
        $notification = $args['args']['notification'];
        $enable = (isset($notification->enable) && $notification->enable) ? 1 : 0;
        $notts = isset($notification->notifications) ? (array) $notification->notifications : array();

        echo CRED_Loader::tpl('notification-meta-box', array(
            'form' => $form,
            'enable' => $enable,
            'notifications' => $notts,
            'enableTestMail' => !CRED_Helper::$currentPage->isCustomPostNew,
            'help' => CRED_CRED::$help,
            'help_target' => CRED_CRED::$help_link_target
        ));
    }

    public static function addNotificationMetaBox2($form, $args) {
        $notification = $args['args']['notification'];
        $enable = (isset($notification->enable) && $notification->enable) ? 1 : 0;
        $notts = isset($notification->notifications) ? (array) $notification->notifications : array();

        echo CRED_Loader::tpl('notification-user-meta-box', array(
            'form' => $form,
            'enable' => $enable,
            'notifications' => $notts,
            'enableTestMail' => !CRED_Helper::$currentPage->isCustomPostNew,
            'help' => CRED_CRED::$help,
            'help_target' => CRED_CRED::$help_link_target
        ));
    }

    public static function addExtraAssetsMetaBox($form, $args) {
        $extra = $args['args']['extra'];

        echo CRED_Loader::tpl('extra-meta-box', array(
            'css' => isset($extra->css) ? $extra->css : '',
            'js' => isset($extra->js) ? $extra->js : '',
            'help' => CRED_CRED::$help,
            'help_target' => CRED_CRED::$help_link_target
        ));
    }

    public static function addMessagesMetaBox($form, $args) {
        $extra = $args['args']['extra'];
        if (isset($extra->messages))
            $messages = $extra->messages;
        else
            $messages = false;
        $model = CRED_Loader::get('MODEL/Forms');
        if (!$messages)
            $messages = $model->getDefaultMessages();

        echo CRED_Loader::tpl('text-settings-meta-box', array(
            'messages' => $messages,
            'descriptions' => $model->getDefaultMessageDescriptions()
        ));
    }

    public static function addMessagesMetaBox2($form, $args) {
        $extra = $args['args']['extra'];
        if (isset($extra->messages))
            $messages = $extra->messages;
        else
            $messages = false;
        $model = CRED_Loader::get('MODEL/UserForms');
        if (!$messages)
            $messages = $model->getDefaultMessages();

        echo CRED_Loader::tpl('text-settings-meta-box', array(
            'messages' => $messages,
            'descriptions' => $model->getDefaultMessageDescriptions()
        ));
    }

}
