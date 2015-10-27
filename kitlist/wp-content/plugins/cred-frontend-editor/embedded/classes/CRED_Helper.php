<?php
add_filter('set-screen-option', array('CRED_Helper', 'setScreenOptions'), 10, 3);

function getCurrentScreenPerPage() {
    $user = get_current_user_id();
    $screen = get_current_screen();
    $option = $screen->get_option('per_page', 'option');
    $per_page = get_user_meta($user, $option, true);
    if (empty($per_page) || $per_page < 1) {
        $per_page = $screen->get_option('per_page', 'default');
    }
    return $per_page;
}

/**
 * Helper Class
 *
 */
final class CRED_Helper {

    private static $cred_wpml_option = '_cred_cred_wpml_active';
    private static $caps = null;
    public static $screens = array();
    public static $currentPage = null;
    public static $currentUPage = null;

    public static function setupAdmin() {
        global $wp_version, $post;

// determine current admin page
        self::getAdminPage(array(
            'post_type' => CRED_FORMS_CUSTOM_POST_NAME,
            'base' => 'admin.php',
            'pages' => array('view-archives-editor', 'views-editor', 'CRED_Forms', 'CRED_Fields', 'CRED_Settings', 'CRED_Help')
        ));

        self::getAdminPage(array(
            'post_type' => CRED_USER_FORMS_CUSTOM_POST_NAME,
            'base' => 'admin.php',
            'pages' => array('view-archives-editor', 'views-editor', 'CRED_Forms', 'CRED_Fields', 'CRED_Settings', 'CRED_Help')
        ));

// add plugin menus
        add_action('admin_menu', array(__CLASS__, 'addMenuItems'));

// setup js, css assets
        add_action('admin_enqueue_scripts', array(__CLASS__, 'onAdminEnqueueScripts'));

// add custom js on certain pages
        if (version_compare($wp_version, '3.3', '>=')) {
            add_action('admin_head-post.php', array(__CLASS__, 'jsForCredCustomPost'));
            add_action('admin_head-post-new.php', array(__CLASS__, 'jsForCredCustomPost'));
        }

        if (isset($_GET['page']) && ( $_GET['page'] == 'view-archives-editor' ||
                $_GET['page'] == 'views-editor' )) {
            add_action('admin_head', array(__CLASS__, 'jsForCredCustomPost'));
        }
//        if (version_compare($wp_version, '3.2', '>=')) {
//            if (isset($post) && $post->post_type == CRED_FORMS_CUSTOM_POST_NAME)
//                remove_action('pre_post_update', 'wp_save_post_revision');
//        }

        /**
         * add debug information
         */
        add_filter('icl_get_extra_debug_info', array(__CLASS__, 'getExtraDebugInfo'));
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

        $cred_index = 'CRED_Forms'; //CRED_VIEWS_PATH2.'/forms.php';
        add_menu_page($menu_label, $menu_label, CRED_CAPABILITY, $cred_index, array(__CLASS__, 'FormsMenuPage'), 'none');
// allow 3rd-party menu items to be included
        do_action('cred_admin_menu_top', $cred_index);
        add_submenu_page($cred_index, __('Post Forms', 'wp-cred'), __('Post Forms', 'wp-cred'), CRED_CAPABILITY, 'CRED_Forms', array(__CLASS__, 'FormsMenuPage'));
//        add_submenu_page($cred_index, __('New Form', 'wp-cred'), __('New Form', 'wp-cred'), CRED_CAPABILITY, $url);
// allow 3rd-party menu items to be included
        do_action('cred_admin_menu_after_forms', $cred_index);
//        add_submenu_page($cred_index, __('Custom Fields', 'wp-cred'), __('Custom Fields', 'wp-cred'), CRED_CAPABILITY, 'CRED_Fields', array(__CLASS__, 'FieldsMenuPage'));
// allow 3rd-party menu items to be included
        do_action('cred_admin_menu_after_fields', $cred_index);
//        add_submenu_page($cred_index, __('Settings/Import', 'wp-cred'), __('Settings/Import', 'wp-cred'), CRED_CAPABILITY, 'CRED_Settings', array(__CLASS__, 'SettingsMenuPage'));
// allow 3rd-party menu items to be included
        do_action('cred_admin_menu_after_settings', $cred_index);
//        $hook = add_submenu_page($cred_index, __('Help', 'wp-cred'), __('Help', 'wp-cred'), CRED_CAPABILITY, 'CRED_Help', array(__CLASS__, 'HelpMenuPage'));
//        add_submenu_page(
//                $hook, __('Debug information', 'wp-cred'), __('Debug information', 'wp-cred'), CRED_CAPABILITY, 'cred-debug-information', array(__CLASS__, 'DebugMenuPage')
//        );
// allow 3rd-party menu items to be included
        do_action('cred_admin_menu_bottom', $cred_index);

        CRED_Helper::$screens = array('toplevel_page_CRED_Forms', 'toplevel_page_CRED_User_Forms', 'cred_page_CRED_Forms', 'cred_page_CRED_User_Forms', 'cred_page_CRED_Fields');
        foreach (CRED_Helper::$screens as $screen) {
            add_action("load-" . $screen, array(__CLASS__, 'addScreenOptions'));
        }
    }

//    public static function HelpMenuPage() {
//        CRED_Loader::load('VIEW/help');
//    }

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
                add_filter('toolset_promotion_screen_ids', array(__CLASS__, 'add_toolset_promotion_screen_id'));
                CRED_Loader::get('TABLE/EmbeddedForms');
                break;
            case 'cred_page_CRED_Forms':
                CRED_Loader::get('TABLE/EmbeddedForms');
                break;

            case 'toplevel_page_CRED_User_Forms':
                add_filter('toolset_promotion_screen_ids', array(__CLASS__, 'add_toolset_promotion_screen_id2'));
                CRED_Loader::get('TABLE/EmbeddedUserForms');
                break;
            case 'cred_page_CRED_User_Forms':
                CRED_Loader::get('TABLE/EmbeddedUserForms');
                break;

            case 'cred_page_CRED_Fields':
                CRED_Loader::get('TABLE/Custom_Fields');
                break;
        }
    }

    public static function add_toolset_promotion_screen_id($ids) {
        $ids[] = 'toplevel_page_CRED_Forms';
        return $ids;
    }

    public static function add_toolset_promotion_screen_id2($ids) {
        $ids[] = 'toplevel_page_CRED_User_Forms';
        return $ids;
    }

    public static function FormsMenuPage() {
        CRED_Loader::load('VIEW/embedded-forms');
    }

    public static function setJSAndCSS() {
        global $wp_version;

// setup js, css assets
        add_action('admin_enqueue_scripts', array(__CLASS__, 'onAdminEnqueueScripts'));

// add custom js on certain pages
        if (version_compare($wp_version, '3.3', '>=')) {
            add_action('admin_head-post.php', array(__CLASS__, 'jsForCredCustomPost'));
            add_action('admin_head-post-new.php', array(__CLASS__, 'jsForCredCustomPost'));
        }

        if (isset($_GET['page'])) {
            $page = $_GET['page'];

            $set_on_pages = array('view-archives-editor', 'views-editor');

            /**
             * Get admin page slugs where CRED assets, used in form create and edit admin pages,
             * should be enueued.
             *
             * @param array $set_on_pages Array of page slugs.
             *
             * @since 1.3.6.1
             */
            $set_on_pages = apply_filters('cred_get_custom_pages_to_load_assets', $set_on_pages);

            if (in_array($page, $set_on_pages)) {
                add_action('admin_head', array(__CLASS__, 'jsForCredCustomPost'));
            }
        }
    }

    protected static function getCurrentPostType() {
        if (is_admin()) {
            if (isset($_REQUEST['post_type'])) {
                return sanitize_text_field($_REQUEST['post_type']);
            } else {
                if (isset($_REQUEST['action']) && isset($_REQUEST['post'])) {
                    $postid = intval($_REQUEST['post']);
                    return get_post_type($postid);
                } else {
                    return null;
                }
            }
        }
    }

    public static function onAdminEnqueueScripts() {
// On what admin pages should CRED assets be loaded?
        $set_on_pages = array('view-archives-editor', 'views-editor', 'CRED_User_Forms', 'CRED_Forms', 'CRED_Fields', 'CRED_Settings', 'CRED_Help');

// Filter description is placed in setJSAndCSS().
        $set_on_pages = apply_filters('cred_get_custom_pages_to_load_assets', $set_on_pages);

// setup css js
// determine current admin page
        self::getAdminPage(array(
            'post_type' => CRED_FORMS_CUSTOM_POST_NAME,
            'base' => 'admin.php',
            'pages' => $set_on_pages
        ));

        self::getAdminPage(array(
            'post_type' => CRED_USER_FORMS_CUSTOM_POST_NAME,
            'base' => 'admin.php',
            'pages' => $set_on_pages
        ));

        CRED_Loader::loadAsset('STYLE/cred_utility_css', 'cred_utility_css', false, CRED_CONCAT_ASSETS);
        wp_enqueue_style('cred_utility_css');

        wp_register_style('utility-style', CRED_PLUGIN_URL . '/toolset/toolset-common/utility/css/notifications.css', array(), time());
        wp_enqueue_style('utility-style');

        if ((self::$currentPage->isCustomPostEdit || self::$currentPage->isCustomPostNew) ||
                self::$currentUPage->isCustomPostEdit || self::$currentUPage->isCustomPostNew) {
            wp_dequeue_script('autosave');
            wp_deregister_script('autosave');

            global $post;

// add form saved admin message
            if ($post) {
                if ($post->post_type == CRED_FORMS_CUSTOM_POST_NAME) {
                    $form_validation = CRED_Loader::get('MODEL/Forms')->getFormCustomField($post->ID, 'validation');
                    if (
                            (isset($_GET['message']) && '4' == $_GET['message']) &&
                            (isset($form_validation) && isset($form_validation['fail']) && $form_validation['fail'])
                    ) {
                        $form_saved_and_valid = false;
                        add_action('admin_notices', array(__CLASS__, 'formNotValidNotice'), 10);
// force opne metabox if validation issues
                        add_filter('postbox_classes_' . CRED_FORMS_CUSTOM_POST_NAME . "_crednotificationdiv", array(__CLASS__, 'forceMetaboxOpen'));
                    } elseif (
                            (isset($_GET['message']) && '4' == $_GET['message']) &&
                            (!isset($form_validation) || !isset($form_validation['fail']) || !$form_validation['fail'])
                    ) {
                        $form_saved_and_valid = true;
                        add_action('admin_notices', array(__CLASS__, 'formValidNotice'), 10);
                    }
                }
                if ($post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) {
                    $form_validation = array("success" => 1);
                    if (
                            (isset($_GET['message']) && '4' == $_GET['message']) &&
                            (isset($form_validation) && isset($form_validation['fail']) && $form_validation['fail'])
                    ) {
                        $form_saved_and_valid = false;
                        add_action('admin_notices', array(__CLASS__, 'formNotValidNotice'), 10);
// force opne metabox if validation issues
                        add_filter('postbox_classes_' . CRED_USER_FORMS_CUSTOM_POST_NAME . "_crednotificationdiv", array(__CLASS__, 'forceMetaboxOpen'));
                    } elseif (
                            (isset($_GET['message']) && '4' == $_GET['message']) &&
                            (!isset($form_validation) || !isset($form_validation['fail']) || !$form_validation['fail'])
                    ) {
                        $form_saved_and_valid = true;
                        add_action('admin_notices', array(__CLASS__, 'formValidNotice'), 10);

                        //https://onthegosystems.myjetbrains.com/youtrack/issue/cred-147
                        //#########################################################################################################################
                        $fm = CRED_Loader::get('MODEL/UserForms');
                        $form_fields = $fm->getFormCustomFields($post->ID, array('form_settings', 'notification'));

                        $show_notification_alert = false;
                        $correct_notification_set = false;
                        //at least 1 autogeneration field is set
                        if (/* $form_fields['form_settings']->form['autogenerate_username_scaffold'] == 1 ||
                          $form_fields['form_settings']->form['autogenerate_nickname_scaffold'] == 1 || */
                                $form_fields['form_settings']->form['autogenerate_password_scaffold'] == 1
                        ) {
                            //checking each notification
                            foreach ($form_fields['notification']->notifications as $n => $notification) {
                                if (isset($notification['event'])) {
                                    if (
                                            (isset($notification['event']['type']) && $notification['event']['type'] == 'form_submit') &&
                                            (isset($notification['event']['post_status']) && $notification['event']['post_status'] == 'publish') &&
                                            (isset($notification['to']['mail_field']['to_type']) && $notification['to']['mail_field']['to_type'] == 'to') &&
                                            (isset($notification['to']['mail_field']['address_field']) && $notification['to']['mail_field']['address_field'] == 'user_email')
                                    ) {
                                        //at least body must contains placeholder
                                        if (isset($notification['mail']['body']) &&
                                                ($form_fields['form_settings']->form['autogenerate_password_scaffold'] == 1 && preg_match('/%%USER_PASSWORD%%/', $notification['mail']['body'])) /* &&
                                          ($form_fields['form_settings']->form['autogenerate_username_scaffold'] == 1 && preg_match('/%%USER_USERNAME%%/', $notification['mail']['body'])) &&
                                          ($form_fields['form_settings']->form['autogenerate_nickname_scaffold'] == 1 && preg_match('/%%USER_NICKNAME%%/', $notification['mail']['body'])) */
                                        ) {
                                            foreach ($notification['to']['type'] as $m => $type) {
                                                //at least email notification to the user must be set
                                                if ($type == 'mail_field') {
                                                    $correct_notification_set = true;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                if ($correct_notification_set)
                                    break;
                            }
                            $show_notification_alert = !$correct_notification_set;
                        }

                        if ($show_notification_alert) {
                            add_action('admin_notices', array(__CLASS__, 'userFormAlertNotice'), 10);
                        }
                        //#########################################################################################################################
                    }
                }
            }
        }

        if ((self::$currentPage->isPostEdit ||
                self::$currentPage->isPostNew ||
                self::$currentPage->isCustomAdminPage) ||
                (self::$currentUPage->isPostEdit ||
                self::$currentUPage->isPostNew ||
                self::$currentUPage->isCustomAdminPage)) {

            if ((self::$currentPage->isCustomPostEdit || self::$currentPage->isCustomPostNew) ||
                    (self::$currentUPage->isCustomPostEdit || self::$currentUPage->isCustomPostNew)) {
                CRED_Loader::loadAsset('SCRIPT/cred_cred_dev', 'cred_cred', false, CRED_CONCAT_ASSETS);
                CRED_Loader::loadAsset('SCRIPT/cred_wizard_dev', 'cred_wizard');
                CRED_Loader::loadAsset('STYLE/cred_cred_style_dev', 'cred_cred_style', false, CRED_CONCAT_ASSETS);
// WordPress 4.0 compatibility: remove all the new fancy editor enhancements that break the highlighting and toolbars
                wp_dequeue_script('editor-expand');
            } else {
                CRED_Loader::loadAsset('SCRIPT/cred_cred_post_dev', 'cred_cred', false, CRED_CONCAT_ASSETS);
                CRED_Loader::loadAsset('STYLE/cred_cred_style_nocodemirror_dev', 'cred_cred_style', false, CRED_CONCAT_ASSETS);
            }

// enqueue them with dependencies
            wp_enqueue_style('cred_cred_style');
            wp_enqueue_script('cred_cred');

            $fm = CRED_Loader::get('MODEL/UserForms');
            $form_fields = $fm->getFormCustomFields($post->ID, array('form_settings', 'notification'));

// Inline Settings/Localization
            wp_localize_script('cred_cred', 'cred_settings', array(
                '_current_page' => self::getCurrentPostType(),
                '_cred_wpnonce' => wp_create_nonce('_cred_wpnonce'),
                'autogenerate_username_scaffold' => $form_fields['form_settings']->form['autogenerate_username_scaffold'],
                'autogenerate_nickname_scaffold' => $form_fields['form_settings']->form['autogenerate_nickname_scaffold'],
                'autogenerate_password_scaffold' => $form_fields['form_settings']->form['autogenerate_password_scaffold'],
                // settings
                'assets' => CRED_ASSETS_URL,
                'ajaxurl' => admin_url('admin-ajax.php'),
                'editurl' => admin_url('post.php'),
                'form_controller_url' => '/Forms/updateFormField',
                'wizard_url' => '/Settings/disableWizard',
                'homeurl' => home_url('/'),
                'settingsurl' => CRED_CRED::$settingsPage,
                // help
                'help' => CRED_CRED::$help,
                // locale
                'locale' => array(
                    'OK' => __('OK', 'wp-cred'),
                    'Yes' => __('Yes', 'wp-cred'),
                    'No' => __('No', 'wp-cred'),
                    'syntax_button_title' => __('Syntax', 'wp-cred'),
                    'text_button_title' => __('Text'/* , 'wp-cred' */),
                    'title_explain_text' => __('Set the title for this new form.', 'wp-cred'),
                    'content_explain_text' => __('Build the form using HTML and CRED shortcodes. Click on the <strong>Auto-Generate Form</strong> button to create the form with default fields. Use the <strong>Add User/Post Fields</strong> button to add fields that belong to this post type, or <strong>Add Generic Fields</strong> to add any other inputs.', 'wp-cred'),
                    'next_text' => __('Next', 'wp-cred'),
                    'prev_text' => __('Previous', 'wp-cred'),
                    'finish_text' => __('Finish', 'wp-cred'),
                    'quit_wizard_text' => __('Exit Wizard Mode', 'wp-cred'),
                    'quit_wizard_confirm_text' => sprintf(__('Do you want to disable the Wizard for this form only, or disable the Wizard for all future forms as well? <br /><br /><span style="font-style:italic">(You can re-enable the Wizard at the %s Settings Page if you change your mind)</span>', 'wp-cred'), CRED_NAME),
                    'quit_wizard_all_forms' => __('All forms', 'wp-cred'),
                    'quit_wizard_this_form' => __('This form', 'wp-cred'),
                    'cancel_text' => __('Cancel', 'wp-cred'),
                    'form_type_missing' => __('You must select the form type for the form', 'wp-cred'),
                    'post_type_missing' => __('You must select a post type for the form', 'wp-cred'),
                    'ok_text' => __('OK', 'wp-cred'),
                    'step_1_title' => __('Title', 'wp-cred'),
                    'step_2_title' => __('Settings', 'wp-cred'),
                    'step_3_title' => __('Post Type', 'wp-cred'),
                    'step_4_title' => __('Build Form', 'wp-cred'),
                    'step_5_title' => __('Notifications', 'wp-cred'),
                    'submit_but' => __('Update', 'wp-cred'),
                    'form_content' => __('Form Content', 'wp-cred'),
                    'form_fields' => __('Form Fields', 'wp-cred'),
                    'post_fields' => __('Standard Post Fields', 'wp-cred'),
                    //Added
                    'user_fields' => __('Standard User Fields', 'wp-cred'),
                    'custom_fields' => __('Custom Fields', 'wp-cred'),
                    'taxonomy_fields' => __('Taxonomies', 'wp-cred'),
                    'parent_fields' => __('Parents', 'wp-cred'),
                    'extra_fields' => __('Extra Fields', 'wp-cred'),
                    'form_types_not_set' => __('Form Type or Post Type is not set!'),
                    'set_form_title' => __('Please set the form Title', 'wp-cred'),
                    'create_new_content_form' => __('(Create a new-post form first)', 'wp-cred'),
                    'create_edit_content_form' => __('(Create an edit-post form first)', 'wp-cred'),
                    'create_new_content_user_form' => __('(Create a new-user form first)', 'wp-cred'),
                    'create_edit_content_user_form' => __('(Create an edit-user form first)', 'wp-cred'),
                    'show_advanced_options' => __('Show advanced options', 'wp-cred'),
                    'hide_advanced_options' => __('Hide advanced options', 'wp-cred'),
                    'select_form' => __('Please select a form first', 'wp-cred'),
                    'select_post' => __('Please select a post first', 'wp-cred'),
                    'insert_post_id' => __('Please insert a valid post ID', 'wp-cred'),
                    'insert_shortcode' => __('Click to insert the specified shortcode', 'wp-cred'),
                    'select_shortcode' => __('Please select a shortcode first', 'wp-cred'),
                    'post_types_dont_match' => __('This post type is incompatible with the selected form', 'wp-cred'),
                    'post_status_must_be_public' => __('In order to display the post, post status must be set to Publish', 'wp-cred'),
                    'refresh_done' => __('Refresh Complete', 'wp-cred'),
                    'enable_popup_for_preview' => __('You have to enable popup windows in order for Preview to work!', 'wp-cred'),
                    'show_syntax_highlight' => __('Enable Syntax Highlight', 'wp-cred'),
                    'hide_syntax_highlight' => __('Revert to default editor', 'wp-cred'),
                    'syntax_highlight_on' => __('Syntax Highlight On', 'wp-cred'),
                    'syntax_highlight_off' => __('Syntax Highlight Off', 'wp-cred'),
                    'invalid_title' => __('Title should contain only letters, numbers and underscores/dashes', 'wp-cred'),
                    'form_user_not_set' => __('Form User Fields not set!'),
                    'invalid_user_role' => __('Allowed User Role option cannot be empty.', 'wp-cred'),
                    'invalid_form_type' => __('Allowed Form Type option cannot be empty.', 'wp-cred'),
                //'autogeneration_alert' => __('This form will auto-generate the password for the new user.<br><br>In order for the user to receive the password, you need to create a notification which will include that password.<br><br><a href="">How to create notifications for sending passwords</a>', 'wp-cred'),
                )
            ));
        }
        if ((self::$currentPage->isCustomPostEdit || self::$currentPage->isCustomPostNew || self::$currentPage->isCustomAdminPage) ||
                (self::$currentUPage->isCustomPostEdit || self::$currentUPage->isCustomPostNew || self::$currentUPage->isCustomAdminPage)) {
            ?><style type="text/css">
                /* CRED plugin ICONS */
                #icon-CRED_Forms.icon32-posts-cred-form,
                #icon-edit.icon32-posts-cred-form,
                #icon-cred-frontend-editor {
                    background: transparent no-repeat 0 0;
                }

            <?php
            if ((self::$currentPage->isCustomPostEdit || self::$currentPage->isCustomPostNew) ||
                    (self::$currentUPage->isCustomPostEdit || self::$currentUPage->isCustomPostNew)) {
                ?>
                    #credformactionmessage ,
                    #cred_form_action_message {
                        height:200px;
                    }
                    /* reduce FOUC a bit */
                    #screen-meta-links,
                    .postbox:not(.cred_related),
                    #post .postbox:not(.cred_related),
                    .wrap div.error, .wrap div.updated {
                        display:none !important;
                    }
                    /*div.wrap {
                        padding-bottom:140px;
                    }*/
                    /*#wpbody-content, div.wrap, form#post {
                        position:relative;
                        overflow:visible;
                        min-height:100%;
                        padding-bottom:140px;
                    }*/
            <?php } ?>
            </style><?php
            }
        }

        public static function formNotValidNotice() {
            ?><div class="cred-notification cred-error">
            <p>
                <i class="icon-warning-sign"></i>
        <?php _e('The form was saved, but some settings are not complete, please review the alert icons below', 'wp-cred'); ?>
            </p>
        </div><?php
    }

    public static function formValidNotice() {
        ?><div class="cred-notification cred-success">
            <p>
                <i class="icon-ok"></i>
        <?php printf(__('The form was successfully saved. %s', 'wp-cred'), '<a target="_blank" href="' . CRED_CRED::$help['add_forms_to_site']['link'] . '">' . CRED_CRED::$help['add_forms_to_site']['text'] . '</a>'); ?>
            </p>
        </div><?php
    }

    public static function userFormAlertNotice() {
        ?><div class="cred-notification cred-error">
            <p>
                <i class="icon-warning-sign"></i>
        <?php printf(__('This form will auto-generate the password for the new user.<br>In order for the user to receive the password, you need to create a notification which will include that password.<br>%s', 'wp-cred'), '<a target="_blank" href="' . CRED_CRED::$help['autogeneration_notification_missing_alert']['link'] . '">' . CRED_CRED::$help['autogeneration_notification_missing_alert']['text'] . '</a>'); ?>
            </p>
        </div><?php
    }

    public static function getAdminPage($custom_data = array()) {
        global $pagenow, $post, $post_type;

        $pageData = (object) array(
                    'post_type' => $custom_data['post_type'],
                    'isAdmin' => false,
                    'isAdminAjax' => false,
                    'isPostEdit' => false,
                    'isPostNew' => false,
                    'isCustomPostEdit' => false,
                    'isCustomPostNew' => false,
                    'isCustomAdminPage' => false
        );

        if (!is_admin()) {
            if ($custom_data['post_type'] == CRED_FORMS_CUSTOM_POST_NAME)
                self::$currentPage = $pageData;
            if ($custom_data['post_type'] == CRED_USER_FORMS_CUSTOM_POST_NAME)
                self::$currentUPage = $pageData;
            return $pageData;
        }

        $pageData->isAdmin = true;
        $pageData->isPostEdit = (bool) ('post.php' === $pagenow);
        $pageData->isPostNew = (bool) ('post-new.php' === $pagenow);
        if (!empty($custom_data)) {
            $custom_post_type = isset($custom_data['post_type']) ? $custom_data['post_type'] : false;
            $pageData->isCustomPostEdit = (bool) ($pageData->isPostEdit && $custom_post_type === $post_type);
            $pageData->isCustomPostNew = (bool) ($pageData->isPostNew && isset($_GET['post_type']) && $custom_post_type === $_GET['post_type']);
        }
        if (!empty($custom_data)) {
            $custom_admin_base = isset($custom_data['base']) ? $custom_data['base'] : false;
            $custom_admin_pages = isset($custom_data['pages']) ? (array) $custom_data['pages'] : array();
            $pageData->isCustomAdminPage = (bool) ($custom_admin_base === $pagenow && isset($_GET['page']) && in_array($_GET['page'], $custom_admin_pages));
        }

        if ($custom_data['post_type'] == CRED_FORMS_CUSTOM_POST_NAME)
            self::$currentPage = $pageData;
        if ($custom_data['post_type'] == CRED_USER_FORMS_CUSTOM_POST_NAME)
            self::$currentUPage = $pageData;
        return $pageData;
    }

// js used in form create and edit admin pages
    public static function jsForCredCustomPost() {
        global $post;
        $screen = get_current_screen();
        $current_post_type = ( isset($_GET['post_type']) ) ? sanitize_text_field($_GET['post_type']) :
                isset($post->post_type) ? $post->post_type :
                        '';

        if ((self::$currentPage->isCustomPostEdit || self::$currentPage->isCustomPostNew) ||
                (self::$currentUPage->isCustomPostEdit || self::$currentUPage->isCustomPostNew)) {
            $newform = false;
            if (
                    (
                    self::$currentPage->isCustomPostNew || self::$currentUPage->isCustomPostNew
                    ) && (
                    CRED_FORMS_CUSTOM_POST_NAME == $current_post_type || CRED_USER_FORMS_CUSTOM_POST_NAME == $current_post_type
                    )
            )
                $newform = true;

            $sm = CRED_Loader::get('MODEL/Settings');
            $settings = $sm->getSettings();
            $fm = (CRED_USER_FORMS_CUSTOM_POST_NAME == $current_post_type) ? CRED_Loader::get('MODEL/UserForms') : CRED_Loader::get('MODEL/Forms');
            $form_fields = $fm->getFormCustomFields($post->ID, array('form_settings', 'notification', 'extra', 'wizard'));

            $add_wizard = false;
            if ($settings['wizard']) {
                $wizard = ($post && isset($form_fields['wizard'])) ? $form_fields['wizard'] : 0;
                if ($wizard == false || $wizard == null || $wizard == '-1')
                    $wizard = -1;
                $wizard = intval($wizard);
                if (0 != $wizard && $newform)
                    $wizard = 0;

                if (0 <= $wizard)
                    $add_wizard = true;

                if ($add_wizard)
// include wizard
                    wp_enqueue_script('cred_wizard');
            }
            if (isset($settings['syntax_highlight']) && $settings['syntax_highlight'])
                $syntaxhi = 'true';
            else
                $syntaxhi = 'false';

// add these to be same as template input fields
// format the data for the view/model data
            $form_fields['form'] = isset($form_fields['form_settings']->form) ? $form_fields['form_settings']->form : array();
            $form_fields['post'] = isset($form_fields['form_settings']->post) ? $form_fields['form_settings']->post : array();
            ?>
            <script type='text/javascript'>
                /*<![CDATA[ */
                var _credFormData =<?php echo json_encode($form_fields); ?>;
                (function ($) {
                    $(function () {
                        cred_cred.forms(<?php echo $syntaxhi; ?>);
            <?php if ($add_wizard) { ?>
                            cred_wizard.init(<?php echo $wizard; ?>, <?php
                if ($newform)
                    echo 'true';
                else
                    echo 'false';
                ?>);
            <?php } ?>
                    });
                })(jQuery);
                /*]]>*/
            </script>
            <?php
        }
        elseif (self::$currentPage->isPostEdit || self::$currentPage->isPostNew ||
                self::$currentUPage->isPostEdit || self::$currentUPage->isPostNew ||
                $screen->id == 'views-new_page_views-editor' || $screen->id == 'views-new_page_view-archives-editor') {
            ?>
            <script type='text/javascript'>
                /*<![CDATA[ */
                jQuery(function () {
                    cred_cred.posts();
                });
                /*]]>*/
            </script>
            <?php
        }
    }

    public static function getLocalisedID($id, $type = null) {
        static $_cache = array();

        if (!isset($_cache[$id])) {
            /*
              WPML localised ID
              function icl_object_id($element_id, $element_type='post',
              $return_original_if_missing=false, $ulanguage_code=null)
             */
            if (function_exists('icl_object_id')) {
                if (null === $type)
                    $type = get_post_type($id);
                $loc_id = icl_object_id($id, $type, true);
            }
            else {
                $loc_id = $id;
            }
            $_cache[$id] = $loc_id;
        }
        return $_cache[$id];
    }

    public static function localizeFormOnSave($data) {
// if WMPL string is active, process form content for strings in shortcode attributes for translation
        if (self::check_wpml_string()) {
            $cfp = CRED_Loader::get('CLASS/Form_Translator');
            $cfp->processForm($data);
        } else {
            update_option(self::$cred_wpml_option, 'no');
        }
    }

    public static function localizeForms() {
// stub wpml-string shortcode
        if (!self::check_wpml_string()) {
// WPML string translation is not active
// Add our own do nothing shortcode
            add_shortcode('wpml-string', 'cred_stub_wpml_string_shortcode');
        } else {
            $wpml_was_active = get_option(self::$cred_wpml_option);
// if changes before wpml activated, re-process all forms
            if ($wpml_was_active && $wpml_was_active == 'no') {
                $cfp = CRED_Loader::get('CLASS/Form_Translator');
                $cfp->processAllForms();
                update_option(self::$cred_wpml_option, 'yes');
            }
        }
// localize terms
        add_filter('wpml_create_term_lang', array(__CLASS__, 'getWPMLCreateTermLang'), 10, 1);
    }

// add CRED support for WPML creating terms
    public static function getWPMLCreateTermLang($term_lang) {
        global $sitepress;
        if (is_object($sitepress) && empty($term_lang)) {
            $term_lang = $sitepress->get_current_language();
        }
        return $term_lang;
    }

// setup necessary DB model settings
    public static function prepareDB() {
        $forms_model = CRED_Loader::get('MODEL/Forms');
        $forms_model->prepareDB();

        $user_forms_model = CRED_Loader::get('MODEL/UserForms');
        $user_forms_model->prepareDB();

        $settings_model = CRED_Loader::get('MODEL/Settings');
        $settings_model->prepareDB();
    }

// add custom classes to our metaboxes, so they can be handled as needed
    public static function forceMetaboxOpen($classes) {
        return array_diff($classes, array('closed'));
    }

    public static function renderWithPost($content, $_post_id, $rich = true) {
        if (!$_post_id) {
            if ($rich)
                $output = self::renderWithBasicFilters($content);
            else
                $output = do_shortcode($content);
        }
        else {
            $isViews = function_exists('WPV_wpcf_switch_post_from_attr_id');
            if ($isViews)
                $post_id_switch = WPV_wpcf_switch_post_from_attr_id(array('id' => $_post_id));
            if ($rich)
                $output = self::renderWithBasicFilters($content);
            else
                $output = do_shortcode($content);
            if ($isViews)
                unset($post_id_switch);
        }
        return $output;
    }

    public static function renderWithBasicFilters($content) {
        if (isset($_GET['_target']) && is_numeric($_GET['_target'])) {
            global $post;
            $post = get_post($_GET['_target']);
        }
//return do_shortcode( prepend_attachment( shortcode_unautop( wpautop( convert_chars( convert_smilies( wptexturize(                     $content                 ) ) ) ) ) ) );
        return apply_filters('the_content', $content);
    }

    public static function setScreenOptions($status, $option, $value) {
        if ('cred_per_page' == $option)
            return $value;
    }

    public static function setupExtraHooks() {
// setup module manager hooks and actions
        if (defined('MODMAN_PLUGIN_NAME')) {
            $section_id = _CRED_MODULE_MANAGER_KEY_;
            $section_id2 = _CRED_MODULE_MANAGER_USER_KEY_;

            add_filter('wpmodules_register_sections', array(__CLASS__, 'register_modules_cred_sections'), 30, 1);

            add_filter('wpmodules_register_items_' . $section_id, array(__CLASS__, 'register_modules_cred_items'), 10, 1);
            add_filter('wpmodules_export_items_' . $section_id, array(__CLASS__, 'export_modules_cred_items'), 10, 2);
            add_filter('wpmodules_import_items_' . $section_id, array(__CLASS__, 'import_modules_cred_items'), 10, 3);
            add_filter('wpmodules_items_check_' . $section_id, array(__CLASS__, 'modules_cred_items_exist'), 10, 1);

            add_filter('wpmodules_register_sections', array(__CLASS__, 'register_modules_cred_user_sections'), 30, 1);

            add_filter('wpmodules_register_items_' . $section_id2, array(__CLASS__, 'register_modules_cred_user_items'), 10, 1);
            add_filter('wpmodules_export_items_' . $section_id2, array(__CLASS__, 'export_modules_cred_user_items'), 10, 2);
            add_filter('wpmodules_import_items_' . $section_id2, array(__CLASS__, 'import_modules_cred_user_items'), 10, 3);
            add_filter('wpmodules_items_check_' . $section_id2, array(__CLASS__, 'modules_cred_user_items_exist'), 10, 1);

            //Module manager: Hooks for adding plugin version            

            /* Export */
            add_filter('wpmodules_export_pluginversions_' . $section_id, array(__CLASS__, 'modules_cred_pluginversion'));
            /* Import */
            add_filter('wpmodules_import_pluginversions_' . $section_id, array(__CLASS__, 'modules_cred_pluginversion'));

            /* Export */
            add_filter('wpmodules_export_pluginversions_' . $section_id2, array(__CLASS__, 'modules_cred_pluginversion'));
            /* Import */
            add_filter('wpmodules_import_pluginversions_' . $section_id2, array(__CLASS__, 'modules_cred_pluginversion'));

            /* Link to read-only versions of elements in installed modules */
            add_action('wpmodules_library_link_components', array(__CLASS__, 'cred_modules_library_link_components'), 10, 2);
        }
        // setup cred bypass form submissions
        if (defined('CRED_DISABLE_SUBMISSION') && CRED_DISABLE_SUBMISSION) {
            add_filter('cred_bypass_save_data', array(__CLASS__, '__true'), 20);
            add_filter('cred_bypass_credaction', array(__CLASS__, '__true'), 20);
            add_filter('cred_data_saved_message', array(__CLASS__, 'disableCREDSubmitMessage'), 20);
            add_filter('cred_data_not_saved_message', array(__CLASS__, 'disableCREDSubmitMessage'), 20);
        }
    }

    /**
     * cred_modules_library_link_components
     *
     * Hooks into the Module Manager Library listing and offers links to edit/readonly versions of each CRED component
     *
     * @param $current_module
     * @param $modman_modules (array) installed modules as stored in the Options table
     *
     * @since 1.3.4
     */
    public static function cred_modules_library_link_components($current_module = array(), $modman_modules = array()) {
        $this_module_data = array();
        foreach ($modman_modules as $hackey => $hackhack) {
            if (strtolower($hackey) == strtolower($current_module['name'])) {
                $this_module_data = $hackhack;
            }
        }
        $embedded = CRED_CRED::is_embedded();
        if (
                ( isset($this_module_data[_CRED_MODULE_MANAGER_KEY_]) &&
                is_array($this_module_data[_CRED_MODULE_MANAGER_KEY_]))
        ) {
            global $wpdb;
            ?>
            <div class="module-elements-container">
                <h4><?php _e('CRED elements in this Module', 'wp-cred'); ?></h4>
                <ul class="module-elements">
            <?php
            if (isset($this_module_data[_CRED_MODULE_MANAGER_KEY_]) &&
                    is_array($this_module_data[_CRED_MODULE_MANAGER_KEY_])) {
                $cred_titles = array();
                foreach ($this_module_data[_CRED_MODULE_MANAGER_KEY_] as $this_cred) {
                    $cred_titles[] = $this_cred['title'];
                }
                $cred_titles_flat = implode("','", $cred_titles);
                $cred_pairs = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_title IN ('{$cred_titles_flat}') AND post_type = 'cred-form'");
                if ($cred_pairs) {
                    $suffix = 'editor';
                    if ($embedded) {
                        $suffix = 'embedded';
                    }
                    foreach ($cred_pairs as $cred_data) {
                        $prefix = 'cred';
                        echo '<li class="cred-element"><a href="' . admin_url() . 'admin.php?page=' . $prefix . '-' . $suffix . '&cred_id=' . $cred_data->ID . '"><i class="icon-cred ont-icon-19 ont-color-orange"></i>' . $cred_data->post_title . '</a></li>';
                    }
                }
            }
            ?>
                </ul>
            </div>
            <?php
        }
        if (
                ( isset($this_module_data[_CRED_MODULE_MANAGER_USER_KEY_]) &&
                is_array($this_module_data[_CRED_MODULE_MANAGER_USER_KEY_]))
        ) {
            global $wpdb;
            ?>
            <div class="module-elements-container">
                <h4><?php _e('CRED elements in this Module', 'wp-cred'); ?></h4>
                <ul class="module-elements">
            <?php
            if (isset($this_module_data[_CRED_MODULE_MANAGER_USER_KEY_]) &&
                    is_array($this_module_data[_CRED_MODULE_MANAGER_USER_KEY_])) {
                $cred_titles = array();
                foreach ($this_module_data[_CRED_MODULE_MANAGER_USER_KEY_] as $this_cred) {
                    $cred_titles[] = $this_cred['title'];
                }
                $cred_titles_flat = implode("','", $cred_titles);
                $cred_pairs = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_title IN ('{$cred_titles_flat}') AND post_type = 'cred-user-form'");
                if ($cred_pairs) {
                    $suffix = 'editor';
                    if ($embedded) {
                        $suffix = 'embedded';
                    }
                    foreach ($cred_pairs as $cred_data) {
                        $prefix = 'cred';
                        echo '<li class="cred-element"><a href="' . admin_url() . 'admin.php?page=' . $prefix . '-' . $suffix . '&cred_id=' . $cred_data->ID . '"><i class="icon-cred ont-icon-19 ont-color-orange"></i>' . $cred_data->post_title . '</a></li>';
                    }
                }
            }
            ?>
                </ul>
            </div>
            <?php
        }
    }

    public static function modules_cred_pluginversion() {
        return CRED_FE_VERSION;
    }

    public static function disableCREDSubmitMessage($m) {
        return __('Form data saving has been disabled', 'wp-cred');
    }

    public static function register_modules_cred_sections($sections) {
        //TODO: change with fonctcustom icon
        $sections[_CRED_MODULE_MANAGER_KEY_] = array(
            'title' => __('CRED Post Forms', 'wp-cred'),
            'icon' => CRED_ASSETS_URL . '/images/CRED-icon-color_12X12.png',
            'icon_css' => 'icon-cred-logo ont-icon-16 ont-color-orange'
        );

        return $sections;
    }

    public static function register_modules_cred_user_sections($sections) {
        $sections[_CRED_MODULE_MANAGER_USER_KEY_] = array(
            'title' => __('CRED User Forms', 'wp-cred'),
            'icon' => CRED_ASSETS_URL . '/images/CRED-icon-color_12X12.png',
            'icon_css' => 'icon-cred-logo ont-icon-16 ont-color-orange'
        );

        return $sections;
    }

    public static function register_modules_cred_items($items) {
        $forms = self::getAllFormsCached();

        foreach ($forms as $form) {
            if (isset($form->meta->form['type'])) {
                if ('edit' == $form->meta->form['type'])
                    $details = sprintf(__('This form edits posts of post type "%s".', 'wp-cred'), $form->meta->post['post_type']);
                elseif ('new' == $form->meta->form['type'])
                    $details = sprintf(__('This form creates posts of post type "%s".', 'wp-cred'), $form->meta->post['post_type']);
                else
                    $details = __('Type not set or unknown.', 'wp-cred');
            } else
                $details = __('Type not set or unknown.', 'wp-cred');

            $items[] = array(
                'id' => _CRED_MODULE_MANAGER_KEY_ . $form->ID,
                'title' => $form->post_title,
                'details' => '<p style="padding:5px;">' . $details . '</p>'
            );
        }
        return $items;
    }

    public static function register_modules_cred_user_items($items) {
        $forms = self::getAllUserFormsCached();
        foreach ($forms as $form) {
            if (isset($form->meta->form['type'])) {
                if ('edit' == $form->meta->form['type'])
                    $details = sprintf(__('This form edits users.', 'wp-cred'), $form->meta->post['post_type']);
                elseif ('new' == $form->meta->form['type'])
                    $details = sprintf(__('This form creates users.', 'wp-cred'), $form->meta->post['post_type']);
                else
                    $details = __('Type not set or unknown.', 'wp-cred');
            } else
                $details = __('Type not set or unknown.', 'wp-cred');

            $items[] = array(
                'id' => _CRED_MODULE_MANAGER_USER_KEY_ . $form->ID,
                'title' => $form->post_title,
                'details' => '<p style="padding:5px;">' . $details . '</p>'
            );
        }
        return $items;
    }

    public static function export_modules_cred_items($res, $items) {
        $newitems = array();
// items is now, whole array, not just IDs
        foreach ($items as $ii => $item) {
            $newitems[$ii] = str_replace(_CRED_MODULE_MANAGER_KEY_, '', $item['id']);
        }
        CRED_Loader::load('CLASS/XML_Processor');
        $hashes = array();
        $xmlstring = CRED_XML_Processor::exportToXMLString($newitems, array('hash' => true), $hashes);
        if (!empty($hashes)) {
            foreach ($items as $ii => $item) {
                $id = str_replace(_CRED_MODULE_MANAGER_KEY_, '', $item['id']);
                if (isset($hashes[$id]))
                    $items[$ii]['hash'] = $hashes[$id];
            }
        }
        return array('xml' => $xmlstring, 'items' => $items);
    }

    public static function export_modules_cred_user_items($res, $items) {
        $newitems = array();
// items is now, whole array, not just IDs
        foreach ($items as $ii => $item) {
            $newitems[$ii] = str_replace(_CRED_MODULE_MANAGER_USER_KEY_, '', $item['id']);
        }
        CRED_Loader::load('CLASS/XML_Processor');
        $hashes = array();
        $xmlstring = CRED_XML_Processor::exportUsersToXMLString($newitems, array('hash' => true), $hashes);
        if (!empty($hashes)) {
            foreach ($items as $ii => $item) {
                $id = str_replace(_CRED_MODULE_MANAGER_USER_KEY_, '', $item['id']);
                if (isset($hashes[$id]))
                    $items[$ii]['hash'] = $hashes[$id];
            }
        }
        return array('xml' => $xmlstring, 'items' => $items);
    }

    public static function import_modules_cred_items($res, $xmlstring, $selecteditems = false, $allitems = false) {
        CRED_Loader::load('CLASS/XML_Processor');
//cred_log($selecteditems);
        if (false !== $selecteditems && is_array($selecteditems)) {
            $import_items = array();
            foreach ($selecteditems as $item)
                $import_items[] = str_replace(_CRED_MODULE_MANAGER_KEY_, '', $item);
            unset($selecteditems);
            $results = CRED_XML_Processor::importFromXMLString($xmlstring, array('overwrite_forms' => true, 'items' => $import_items, 'return_ids' => true));
        } else {
            $results = CRED_XML_Processor::importFromXMLString($xmlstring);
        }
        if (false === $results || is_wp_error($results)) {
            $error = (false === $results) ? __('Error during CRED Post Forms import', 'wp-cred') : $results->get_error_message($results->get_error_code());
            $results = array('new' => 0, 'updated' => 0, 'failed' => 0, 'errors' => array($error));
        }
        unset($results['settings']);
// for module manager
        if (isset($results['items'])) {
            foreach ($results['items'] as $old_id => $new_id) {
                $results['items'][_CRED_MODULE_MANAGER_KEY_ . $old_id] = _CRED_MODULE_MANAGER_KEY_ . $new_id;
                unset($results['items'][$old_id]);
            }
        }

        return $results;
    }

    public static function import_modules_cred_user_items($res, $xmlstring, $selecteditems = false, $allitems = false) {
        CRED_Loader::load('CLASS/XML_Processor');
//cred_log($selecteditems);
        if (false !== $selecteditems && is_array($selecteditems)) {
            $import_items = array();
            foreach ($selecteditems as $item)
                $import_items[] = str_replace(_CRED_MODULE_MANAGER_USER_KEY_, '', $item);
            unset($selecteditems);
            $results = CRED_XML_Processor::importUsersFromXMLString($xmlstring, array('overwrite_forms' => true, 'items' => $import_items, 'return_ids' => true));
        } else {
            $results = CRED_XML_Processor::importUserFromXMLString($xmlstring);
        }
        if (false === $results || is_wp_error($results)) {
            $error = (false === $results) ? __('Error during CRED User Forms import', 'wp-cred') : $results->get_error_message($results->get_error_code());
            $results = array('new' => 0, 'updated' => 0, 'failed' => 0, 'errors' => array($error));
        }
        unset($results['settings']);
// for module manager
        if (isset($results['items'])) {
            foreach ($results['items'] as $old_id => $new_id) {
                $results['items'][_CRED_MODULE_MANAGER_USER_KEY_ . $old_id] = _CRED_MODULE_MANAGER_USER_KEY_ . $new_id;
                unset($results['items'][$old_id]);
            }
        }

        return $results;
    }

    public static function modules_cred_items_exist($items) {
        foreach ($items as $key => $item) {
// item exists already
            $form = get_page_by_title($item['title'], OBJECT, CRED_FORMS_CUSTOM_POST_NAME);
            if ($form) {
                $items[$key]['exists'] = true;
                if (isset($item['hash'])) {
                    CRED_Loader::load('CLASS/XML_Processor');
                    $hash = CRED_XML_Processor::computeHashForForm($form->ID);
                    if ($hash && $item['hash'] != $hash)
                        $items[$key]['is_different'] = true; //$hashes[$form->ID];
                    else
                        $items[$key]['is_different'] = false; //$hashes[$form->ID];
                }
            }
            else {
                $items[$key]['exists'] = false;
            }
        }
        return $items;
    }

    public static function modules_cred_user_items_exist($items) {
        foreach ($items as $key => $item) {
// item exists already
            $form = get_page_by_title($item['title'], OBJECT, CRED_USER_FORMS_CUSTOM_POST_NAME);
            if ($form) {
                $items[$key]['exists'] = true;
                if (isset($item['hash'])) {
                    CRED_Loader::load('CLASS/XML_Processor');
                    $hash = CRED_XML_Processor::computeHashForUserForm($form->ID);
                    if ($hash && $item['hash'] != $hash)
                        $items[$key]['is_different'] = true; //$hashes[$form->ID];
                    else
                        $items[$key]['is_different'] = false; //$hashes[$form->ID];
                }
            }
            else {
                $items[$key]['exists'] = false;
            }
        }
        return $items;
    }

    public static function getAllFormsCached() {
        static $cache = null;
        if (null === $cache) {
            $cache = CRED_Loader::get('MODEL/Forms')->getFormsForTable(1, -1);
        }
        return $cache;
    }

    public static function getAllUserFormsCached() {
        static $cache = null;
        if (null === $cache) {
            $cache = CRED_Loader::get('MODEL/UserForms')->getFormsForTable(1, -1);
        }
        return $cache;
    }

    private static function buildCredCaps() {
//public static $caps = null;

        if (!isset($caps)) {
            $caps = array();

            $forms = self::getAllFormsCached();
// register custom CRED Frontend capabilities specific to each form type
            foreach ($forms as $form) {
                $settings = isset($form->meta) ? maybe_unserialize($form->meta) : false;
// caps for forms that create
                if ($settings && $settings->form['type'] == 'new') {
                    $cred_cap = 'create_posts_with_cred_' . $form->ID;
                    $caps[] = $cred_cap;
                } elseif ($settings && $settings->form['type'] == 'edit') {
                    $cred_cap = 'edit_own_posts_with_cred_' . $form->ID;
                    $caps[] = $cred_cap;
                    $cred_cap = 'edit_other_posts_with_cred_' . $form->ID;
                    $caps[] = $cred_cap;
                }
            }
// these caps do not require a specific form
            $cred_cap = 'delete_own_posts_with_cred';
            $caps[] = $cred_cap;
            $cred_cap = 'delete_other_posts_with_cred';
            $caps[] = $cred_cap;
        }
        return $caps;
    }

    private static function buildCredUserCaps() {
//static $caps = null;

        if (!isset($caps)) {
            $caps = array();

            $forms = self::getAllUserFormsCached();
// register custom CRED Frontend capabilities specific to each form type
            foreach ($forms as $form) {
                $settings = isset($form->meta) ? maybe_unserialize($form->meta) : false;
// caps for forms that create
                if ($settings && $settings->form['type'] == 'new') {
                    $cred_cap = 'create_users_with_cred_' . $form->ID;
                    $caps[] = $cred_cap;
                } elseif ($settings && $settings->form['type'] == 'edit') {
                    $cred_cap = 'edit_own_user_with_cred_' . $form->ID;
                    $caps[] = $cred_cap;
                    $cred_cap = 'edit_other_users_with_cred_' . $form->ID;
                    $caps[] = $cred_cap;
                }
            }
// these caps do not require a specific form
            $cred_cap = 'delete_own_user_with_cred';
            $caps[] = $cred_cap;
            $cred_cap = 'delete_other_users_with_cred';
            $caps[] = $cred_cap;
        }
        return $caps;
    }

// add current CRED caps to specific role/user
    private static function addCredCapsToRoleUser($r) {
        $caps = (isset($r->capabilities)) ? $r->capabilities : array();
        $obsolete_cred_caps = array();

// clear forms caps which do not exist any more
        foreach (array_keys($caps) as $cap) {
            if (preg_match('/^create\_posts\_with\_cred\_\d+$/', $cap)) {
                $obsolete_cred_caps[] = $cap;
            } elseif (preg_match('/^edit\_own\_posts\_with\_cred\_\d+$/', $cap)) {
                $obsolete_cred_caps[] = $cap;
            } elseif (preg_match('/^edit\_other\_posts\_with\_cred\_\d+$/', $cap)) {
                $obsolete_cred_caps[] = $cap;
            }
        }
//Get caps for current role
        $caps = self::buildCredCaps();

//Remove unused caps
        foreach ($obsolete_cred_caps as $cred_obsolete_cap) {
            if (!in_array($cred_obsolete_cap, $caps)) {
                $r->remove_cap($cred_obsolete_cap);
            }
        }

// register custom CRED Frontend capabilities specific to each form type for existing forms
// Add only new caps
        foreach ($caps as $cred_cap) {
            if (!$r->has_cap($cred_cap)) {
                $r->add_cap($cred_cap);
            }
        }
    }

    private static function addCredUserCapsToRoleUser($r) {
        $caps = (isset($r->capabilities)) ? $r->capabilities : array();
        $obsolete_cred_caps = array();

// clear forms caps which do not exist any more
        foreach (array_keys($caps) as $cap) {
            if (preg_match('/^create\_users\_with\_cred\_\d+$/', $cap)) {
                $obsolete_cred_caps[] = $cap;
            } elseif (preg_match('/^edit\_own\_user\_with\_cred\_\d+$/', $cap)) {
                $obsolete_cred_caps[] = $cap;
            } elseif (preg_match('/^edit\_other\_users\_with\_cred\_\d+$/', $cap)) {
                $obsolete_cred_caps[] = $cap;
            }
        }
//Get caps for current role
        $caps = self::buildCredUserCaps();

//Remove unused caps
        foreach ($obsolete_cred_caps as $cred_obsolete_cap) {
            if (!in_array($cred_obsolete_cap, $caps)) {
                $r->remove_cap($cred_obsolete_cap);
            }
        }

// register custom CRED Frontend capabilities specific to each form type for existing forms
// Add only new caps
        foreach ($caps as $cred_cap) {
            if (!$r->has_cap($cred_cap)) {
                $r->add_cap($cred_cap);
            }
        }
    }

    public static function setupCustomCaps() {
        global $wp_roles;

        if (
                function_exists('wpcf_access_register_caps')
        ) { // integrate with Types Access
//cred_log('Access Active', 'access.log');
            add_filter('types-access-area', array(__CLASS__, 'register_access_cred_area'));
            add_filter('types-access-group', array(__CLASS__, 'register_access_cred_group'), 10, 2);
            add_filter('types-access-cap', array(__CLASS__, 'register_access_cred_caps'), 10, 3);
// do any necessary changes when access imports / exports custom capabilities
            add_filter('access_import_custom_capabilities_' . '__CRED_CRED', array(__CLASS__, 'import_access_cred_caps'), 1, 2);
            add_filter('access_export_custom_capabilities_' . '__CRED_CRED', array(__CLASS__, 'export_access_cred_caps'), 1, 2);
        } elseif (
                function_exists('ure_not_edit_admin') /* User Role Editor plugin */ ||
                class_exists('Members_Load') /* Members plugin */
        ) { // export custom cred caps to admin role for other plugins to manipulate them (eg User Role Editor or Members)
            if (!isset($wp_roles) && class_exists('WP_Roles')) {
                $wp_roles = new WP_Roles();
            }
            $wp_roles->use_db = true;
            if ($wp_roles->is_role('administrator')) {
                $administrator = $wp_roles->get_role('administrator');
            } else {
                $administrator = false;
                trigger_error(__('Administrator Role not found! CRED capabilities will not work', 'wp-cred'), E_USER_NOTICE);
                return;
            }

            if ($administrator) {
                self::addCredCapsToRoleUser($administrator);
            }
        } else {
            self::$caps = self::buildCredCaps();
            add_filter('user_has_cap', array('CRED_Helper', 'defaultCredCapsFilter'), 5, 3);
        }
    }

    public static function setupCustomUserCaps() {
        global $wp_roles;

        if (
                function_exists('wpcf_access_register_caps')
        ) { // integrate with Types Access
//cred_log('Access Active', 'access.log');
            add_filter('types-access-area', array(__CLASS__, 'register_access_cred_user_area'));
            add_filter('types-access-group', array(__CLASS__, 'register_access_cred_user_group'), 10, 2);
            add_filter('types-access-cap', array(__CLASS__, 'register_access_cred_user_caps'), 10, 3);
// do any necessary changes when access imports / exports custom capabilities
            add_filter('access_import_custom_capabilities_' . '__CRED_CRED_USER', array(__CLASS__, 'import_access_cred_user_caps'), 1, 2);
            add_filter('access_export_custom_capabilities_' . '__CRED_CRED_USER', array(__CLASS__, 'export_access_cred_user_caps'), 1, 2);
        } elseif (
                function_exists('ure_not_edit_admin') /* User Role Editor plugin */ ||
                class_exists('Members_Load') /* Members plugin */
        ) { // export custom cred caps to admin role for other plugins to manipulate them (eg User Role Editor or Members)
            if (!isset($wp_roles) && class_exists('WP_Roles')) {
                $wp_roles = new WP_Roles();
            }
            $wp_roles->use_db = true;
            if ($wp_roles->is_role('administrator')) {
                $administrator = $wp_roles->get_role('administrator');
            } else {
                $administrator = false;
                trigger_error(__('Administrator Role not found! CRED Users capabilities will not work', 'wp-cred'), E_USER_NOTICE);
                return;
            }

            if ($administrator) {
                self::addCredUserCapsToRoleUser($administrator);
            }
        } else {
            self::$caps = array_merge(self::$caps, self::buildCredUserCaps());
            add_filter('user_has_cap', array('CRED_Helper', 'defaultCredCapsFilter'), 5, 3);
        }
    }

// default cred caps filter, all true
    public static function defaultCredCapsFilter($allcaps, $caps, $args) {
        foreach (self::$caps as $cred_cap)
            $allcaps[$cred_cap] = true;
        return $allcaps;
    }

// register a new Types Access Area for custom CRED Frontend capabilities
    public static function register_access_cred_area($areas) {
        $CRED_ACCESS_AREA_NAME = __('CRED Frontend Access', 'wp-cred');
        $CRED_ACCESS_AREA_ID = '__CRED_CRED';
        $CRED_ACCESS_GROUP_NAME = __('CRED Frontend Access Group', 'wp-cred');
        $CRED_ACCESS_GROUP_ID = '__CRED_CRED_GROUP';
        $areas[] = array('id' => $CRED_ACCESS_AREA_ID, 'name' => $CRED_ACCESS_AREA_NAME);
//cred_log('Access Areas after CRED', 'access.log');
//cred_log($areas, 'access.log');
        return $areas;
    }

    public static function register_access_cred_user_area($areas) {
        $CRED_ACCESS_AREA_NAME = __('CRED Users Frontend Access', 'wp-cred');
        $CRED_ACCESS_AREA_ID = '__CRED_CRED_USER';
        $CRED_ACCESS_GROUP_NAME = __('CRED Users Frontend Access Group', 'wp-cred');
        $CRED_ACCESS_GROUP_ID = '__CRED_CRED_USER_GROUP';
        $areas[] = array('id' => $CRED_ACCESS_AREA_ID, 'name' => $CRED_ACCESS_AREA_NAME);
//cred_log('Access Areas after CRED', 'access.log');
//cred_log($areas, 'access.log');
        return $areas;
    }

// register a new Types Access Group within Area for custom CRED Frontend capabilities
    public static function register_access_cred_group($groups, $id) {
        $CRED_ACCESS_AREA_NAME = __('CRED Frontend Access', 'wp-cred');
        $CRED_ACCESS_AREA_ID = '__CRED_CRED';
        $CRED_ACCESS_GROUP_NAME = __('CRED Frontend Access Group', 'wp-cred');
        $CRED_ACCESS_GROUP_ID = '__CRED_CRED_GROUP';
        if ($id == $CRED_ACCESS_AREA_ID) {
            $groups[] = array('id' => $CRED_ACCESS_GROUP_ID, 'name' => $CRED_ACCESS_GROUP_NAME);
//cred_log('Access Groups after CRED', 'access.log');
//cred_log($groups, 'access.log');
        }
        return $groups;
    }

    public static function register_access_cred_user_group($groups, $id) {
        $CRED_ACCESS_AREA_NAME = __('CRED Users Frontend Access', 'wp-cred');
        $CRED_ACCESS_AREA_ID = '__CRED_CRED_USER';
        $CRED_ACCESS_GROUP_NAME = __('CRED Users Frontend Access Group', 'wp-cred');
        $CRED_ACCESS_GROUP_ID = '__CRED_CRED_USER_GROUP';
        if ($id == $CRED_ACCESS_AREA_ID) {
            $groups[] = array('id' => $CRED_ACCESS_GROUP_ID, 'name' => $CRED_ACCESS_GROUP_NAME);
//cred_log('Access Groups after CRED', 'access.log');
//cred_log($groups, 'access.log');
        }
        return $groups;
    }

// register custom CRED Frontend capabilities specific to each form type
    public static function register_access_cred_caps($caps, $area_id, $group_id) {
        $CRED_ACCESS_AREA_NAME = __('CRED Frontend Access', 'wp-cred');
        $CRED_ACCESS_AREA_ID = '__CRED_CRED';
        $CRED_ACCESS_GROUP_NAME = __('CRED Frontend Access Group', 'wp-cred');
        $CRED_ACCESS_GROUP_ID = '__CRED_CRED_GROUP';
        $default_role = 'author';

        if ($area_id == $CRED_ACCESS_AREA_ID && $group_id == $CRED_ACCESS_GROUP_ID) {
            $forms = self::getAllFormsCached();
            foreach ($forms as $form) {
                $settings = isset($form->meta) ? maybe_unserialize($form->meta) : false;
// caps for forms that create
                if ($settings && isset($settings->form['type']) && 'new' == $settings->form['type']) {
                    $cred_cap = 'create_posts_with_cred_' . $form->ID;
                    $caps[$cred_cap] = array(
                        'cap_id' => $cred_cap,
                        'title' => sprintf(__('Create Custom Post with CRED Form "%s"', 'wp-cred'), $form->post_title),
                        'default_role' => $default_role
                    );
                } elseif ($settings && isset($settings->form['type']) && 'edit' == $settings->form['type']) {
                    $cred_cap = 'edit_own_posts_with_cred_' . $form->ID;
                    $caps[$cred_cap] = array(
                        'cap_id' => $cred_cap,
                        'title' => sprintf(__('Edit Own Custom Post with CRED Form "%s"', 'wp-cred'), $form->post_title),
                        'default_role' => $default_role
                    );
                    $cred_cap = 'edit_other_posts_with_cred_' . $form->ID;
                    $caps[$cred_cap] = array(
                        'cap_id' => $cred_cap,
                        'title' => sprintf(__('Edit Others Custom Post with CRED Form "%s"', 'wp-cred'), $form->post_title),
                        'default_role' => $default_role
                    );
                }
            }
// these caps do not require a specific form
            $caps['delete_own_posts_with_cred'] = array(
                'cap_id' => 'delete_own_posts_with_cred',
                'title' => __('Delete Own Posts using CRED', 'wp-cred'),
                'default_role' => $default_role
            );
            $caps['delete_other_posts_with_cred'] = array(
                'cap_id' => 'delete_other_posts_with_cred',
                'title' => __('Delete Others Posts using CRED', 'wp-cred'),
                'default_role' => $default_role
            );
        }
        return $caps;
    }

    public static function register_access_cred_user_caps($caps, $area_id, $group_id) {
        $CRED_ACCESS_AREA_NAME = __('CRED USers Frontend Access', 'wp-cred');
        $CRED_ACCESS_AREA_ID = '__CRED_CRED_USER';
        $CRED_ACCESS_GROUP_NAME = __('CRED Users Frontend Access Group', 'wp-cred');
        $CRED_ACCESS_GROUP_ID = '__CRED_CRED_USER_GROUP';
        $default_role = 'administrator';

        if ($area_id == $CRED_ACCESS_AREA_ID && $group_id == $CRED_ACCESS_GROUP_ID) {
            $forms = self::getAllUserFormsCached();
            foreach ($forms as $form) {
                $settings = isset($form->meta) ? maybe_unserialize($form->meta) : false;
// caps for forms that create
                if ($settings && isset($settings->form['type']) && 'new' == $settings->form['type']) {
                    $cred_cap = 'create_users_with_cred_' . $form->ID;
                    $caps[$cred_cap] = array(
                        'cap_id' => $cred_cap,
                        'title' => sprintf(__('Create Custom User with CRED Form "%s"', 'wp-cred'), $form->post_title),
                        'default_role' => $default_role
                    );
                } elseif ($settings && isset($settings->form['type']) && 'edit' == $settings->form['type']) {
                    $cred_cap = 'edit_own_user_with_cred_' . $form->ID;
                    $caps[$cred_cap] = array(
                        'cap_id' => $cred_cap,
                        'title' => sprintf(__('Edit Own Custom User with CRED Form "%s"', 'wp-cred'), $form->post_title),
                        'default_role' => $default_role
                    );
                    $cred_cap = 'edit_other_users_with_cred_' . $form->ID;
                    $caps[$cred_cap] = array(
                        'cap_id' => $cred_cap,
                        'title' => sprintf(__('Edit Others Custom User with CRED Form "%s"', 'wp-cred'), $form->post_title),
                        'default_role' => $default_role
                    );
                }
            }
// these caps do not require a specific form
            $caps['delete_own_user_with_cred'] = array(
                'cap_id' => 'delete_own_user_with_cred',
                'title' => __('Delete Own Users using CRED', 'wp-cred'),
                'default_role' => $default_role
            );
            $caps['delete_other_users_with_cred'] = array(
                'cap_id' => 'delete_other_users_with_cred',
                'title' => __('Delete Others Users using CRED', 'wp-cred'),
                'default_role' => $default_role
            );
        }
        return $caps;
    }

    public static function export_access_cred_caps($caps, $area) {
        $CRED_ACCESS_AREA_ID = '__CRED_CRED';
        $CRED_ACCESS_GROUP_ID = '__CRED_CRED_GROUP';

        if (isset($caps[$CRED_ACCESS_GROUP_ID]['permissions'])) {
            foreach ($caps[$CRED_ACCESS_GROUP_ID]['permissions'] as $cap => $cdata) {
                if (false !== strpos($cap, 'create_posts_with_cred_')) {
                    $id = intval(str_replace('create_posts_with_cred_', '', $cap));
                    $form = get_post($id);
                    if ($form) {
                        $newcap = 'create_posts_with_cred_' . $form->post_name;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                } elseif (false !== strpos($cap, 'edit_own_posts_with_cred_')) {
                    $id = intval(str_replace('edit_own_posts_with_cred_', '', $cap));
                    $form = get_post($id);
                    if ($form) {
                        $newcap = 'edit_own_posts_with_cred_' . $form->post_name;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                } elseif (false !== strpos($cap, 'edit_other_posts_with_cred_')) {
                    $id = intval(str_replace('edit_other_posts_with_cred_', '', $cap));
                    $form = get_post($id);
                    if ($form) {
                        $newcap = 'edit_other_posts_with_cred_' . $form->post_name;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                }
            }
        }
        return $caps;
    }

    public static function export_access_cred_user_caps($caps, $area) {
        $CRED_ACCESS_AREA_ID = '__CRED_CRED_USER';
        $CRED_ACCESS_GROUP_ID = '__CRED_CRED_USER_GROUP';

        if (isset($caps[$CRED_ACCESS_GROUP_ID]['permissions'])) {
            foreach ($caps[$CRED_ACCESS_GROUP_ID]['permissions'] as $cap => $cdata) {
                if (false !== strpos($cap, 'create_users_with_cred_')) {
                    $id = intval(str_replace('create_users_with_cred_', '', $cap));
                    $form = get_post($id);
                    if ($form) {
                        $newcap = 'create_users_with_cred_' . $form->post_name;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                } elseif (false !== strpos($cap, 'edit_own_user_with_cred_')) {
                    $id = intval(str_replace('edit_own_user_with_cred_', '', $cap));
                    $form = get_post($id);
                    if ($form) {
                        $newcap = 'edit_own_user_with_cred_' . $form->post_name;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                } elseif (false !== strpos($cap, 'edit_other_users_with_cred_')) {
                    $id = intval(str_replace('edit_other_users_with_cred_', '', $cap));
                    $form = get_post($id);
                    if ($form) {
                        $newcap = 'edit_other_users_with_cred_' . $form->post_name;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                }
            }
        }
        return $caps;
    }

    public static function import_access_cred_caps($caps, $area) {
        $CRED_ACCESS_AREA_ID = '__CRED_CRED';
        $CRED_ACCESS_GROUP_ID = '__CRED_CRED_GROUP';

        if (isset($caps[$CRED_ACCESS_GROUP_ID]['permissions'])) {
            foreach ($caps[$CRED_ACCESS_GROUP_ID]['permissions'] as $cap => $cdata) {
                if (false !== strpos($cap, 'create_posts_with_cred_')) {
                    $name = str_replace('create_posts_with_cred_', '', $cap);
                    $args = array(
                        'name' => $name,
                        'post_type' => CRED_FORMS_CUSTOM_POST_NAME,
                        'post_status' => 'private',
                        'posts_per_page' => 1
                    );
                    $form = get_posts($args);
                    if ($form) {
                        $newcap = 'create_posts_with_cred_' . $form[0]->ID;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                } elseif (false !== strpos($cap, 'edit_own_posts_with_cred_')) {
                    $name = str_replace('edit_own_posts_with_cred_', '', $cap);
                    $args = array(
                        'name' => $name,
                        'post_type' => CRED_FORMS_CUSTOM_POST_NAME,
                        'post_status' => 'private',
                        'posts_per_page' => 1
                    );
                    $form = get_posts($args);
                    if ($form) {
                        $newcap = 'edit_own_posts_with_cred_' . $form[0]->ID;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                } elseif (false !== strpos($cap, 'edit_other_posts_with_cred_')) {
                    $name = str_replace('edit_other_posts_with_cred_', '', $cap);
                    $args = array(
                        'name' => $name,
                        'post_type' => CRED_FORMS_CUSTOM_POST_NAME,
                        'post_status' => 'private',
                        'posts_per_page' => 1
                    );
                    $form = get_posts($args);
                    if ($form) {
                        $newcap = 'edit_other_posts_with_cred_' . $form[0]->ID;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                }
            }
        }
        return $caps;
    }

    public static function import_access_cred_user_caps($caps, $area) {
        $CRED_ACCESS_AREA_ID = '__CRED_CRED_USER';
        $CRED_ACCESS_GROUP_ID = '__CRED_CRED_USER_GROUP';

        if (isset($caps[$CRED_ACCESS_GROUP_ID]['permissions'])) {
            foreach ($caps[$CRED_ACCESS_GROUP_ID]['permissions'] as $cap => $cdata) {
                if (false !== strpos($cap, 'create_users_with_cred_')) {
                    $name = str_replace('create_users_with_cred_', '', $cap);
                    $args = array(
                        'name' => $name,
                        'post_type' => CRED_USER_FORMS_CUSTOM_POST_NAME,
                        'post_status' => 'private',
                        'posts_per_page' => 1
                    );
                    $form = get_posts($args);
                    if ($form) {
                        $newcap = 'create_posts_with_cred_' . $form[0]->ID;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                } elseif (false !== strpos($cap, 'edit_own_user_with_cred_')) {
                    $name = str_replace('edit_own_user_with_cred_', '', $cap);
                    $args = array(
                        'name' => $name,
                        'post_type' => CRED_USER_FORMS_CUSTOM_POST_NAME,
                        'post_status' => 'private',
                        'posts_per_page' => 1
                    );
                    $form = get_posts($args);
                    if ($form) {
                        $newcap = 'edit_own_user_with_cred_' . $form[0]->ID;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                } elseif (false !== strpos($cap, 'edit_other_users_with_cred_')) {
                    $name = str_replace('edit_other_users_with_cred_', '', $cap);
                    $args = array(
                        'name' => $name,
                        'post_type' => CRED_USER_FORMS_CUSTOM_POST_NAME,
                        'post_status' => 'private',
                        'posts_per_page' => 1
                    );
                    $form = get_posts($args);
                    if ($form) {
                        $newcap = 'edit_other_users_with_cred_' . $form[0]->ID;
                        $caps[$CRED_ACCESS_GROUP_ID]['permissions'][$newcap] = $cdata;
                        unset($caps[$CRED_ACCESS_GROUP_ID]['permissions'][$cap]);
                    }
                }
            }
        }
        return $caps;
    }

public static function cred_delete_post_link($post_id = false, $text = '', $action = '', $class = '', $style = '', $message = '', $message_after = '', $message_show = 1, $redirect = '') {
        global $post, $current_user;
        static $idcount = 0;

        if (!current_user_can('delete_own_posts_with_cred') && $current_user->ID == $post->post_author) {
//return '<strong>'.__('Do not have permission (delete own)','wp-cred').'</strong>';
            return '';
        }
        if (!current_user_can('delete_other_posts_with_cred') && $current_user->ID != $post->post_author) {
//return '<strong>'.__('Do not have permission (delete other)','wp-cred').'</strong>';
            return '';
        }

        if ($post_id === false || empty($post_id) || !isset($post_id) || !is_numeric($post_id)) {
            if (!isset($post->ID))
                return '<strong>' . __('No post specified', 'wp-cred') . '</strong>';
            else
                $post_id = $post->ID;
        }

// localise the ID
        $post_id = self::getLocalisedID(intval($post_id));
// provide WPML localization for hardcoded texts
        $text = str_replace(array('%TITLE%', '%ID%'), array(get_the_title($post_id), $post_id), cred_translate('Delete Link Text', $text, 'CRED Shortcodes'));

        $link_id = '_cred_cred_' . $post_id . '_' . ++$idcount . '_' . rand(1, 10);
        $_wpnonce = wp_create_nonce($link_id . '_' . $action);
        $link = CRED_CRED::routeAjax('cred-ajax-delete-post&cred_post_id=' . $post_id . '&cred_action=' . $action . '&redirect=' . $redirect . '&_wpnonce=' . $_wpnonce);

        $_atts = array();
        if (!empty($class))
            $_atts[] = 'class="' . esc_attr(str_replace('"', "'", $class)) . '"';
        if (!empty($style))
            $_atts[] = 'style="' . esc_attr(str_replace('"', "'", $style)) . '"';

        $dps = "";
        if ($idcount == 1) {
            //$dps = self::get_delete_post_link_js($message_after);
            global $message_after;
            add_action('wp_footer', array('CRED_Helper', 'get_delete_post_link_js'), 100);
        }

        return CRED_Loader::tpl('delete-post-link', array(
                    'link' => $link,
                    'text' => $text,
                    'link_id' => $link_id,
                    'link_atts' => (!empty($_atts)) ? implode(' ', $_atts) : false,
                    /* 'include_js' => $dps, */
                    'message' => $message,
                    'message_after' => $message_after,
                    'message_show' => $message_show,
                    'js' => $dps
        ));
    }    

    public static function get_delete_post_link_js() {
        global $message_after;
        $add_string = "";
        if (!empty($message_after)) {
            $add_string = 'alert(\'';
            $add_string .= esc_js($message_after);
            $add_string .='\');';
            $add_string .= PHP_EOL;
        }

        $v = "<script type='text/javascript'>     
            function _cred_cred_parse_url(__url__, __params__)
            {
                var __urlparts__ = __url__.split('?'), __urlparamblocks__, __paramobj__, __p__, __v__, __query_string__ = [], __ii__;
                if (__urlparts__.length >= 2)
                {

                    __urlparamblocks__ = __urlparts__[1].split(/[&;]/g);
                    for (__ii__ = 0; __ii__ < __urlparamblocks__.length; __ii__++)
                    {
                        var u = __urlparamblocks__[__ii__];
                        __paramobj__ = u.split('=');
                        var v = __paramobj__[0];
                        __p__ = decodeURIComponent(v);
                        var t = __paramobj__[1];
                        if (t)
                            __v__ = decodeURIComponent(t);
                        else
                            __v__ = false;

                        if (__params__.remove && __params__.remove.length)
                        {
                            if (__params__.remove.indexOf(__p__) > -1)
                                continue;
                        }
                        if (__v__)
                            __query_string__.push(encodeURIComponent(__p__) + '=' + encodeURIComponent(__v__));
                        else
                            __query_string__.push(encodeURIComponent(__p__));
                    }
                    if (__params__.add)
                    {
                        for (__ii__ in __params__.add)
                        {
                            if (__params__.add.hasOwnProperty(__ii__))
                            {
                                if (__params__.add[__ii__])
                                    __query_string__.push(encodeURIComponent(__ii__) + '=' + encodeURIComponent(__params__.add[__ii__]));
                                else
                                    __query_string__.push(encodeURIComponent(__ii__));
                            }
                        }
                    }
                    if (__query_string__.length)
                    {
                        __query_string__ = __query_string__.join('&');
                        __url__ = __urlparts__[0] + '?' + __query_string__;
                    }
                    else
                    {
                        __url__ = __urlparts__[0];
                    }
                }
                return __url__;
            }

            function _cred_cred_delete_post_handler(__isFromLink__, __link__, __url__, __result__, __message__, __message_show__)
            {
                var __ltext__ = '';

                /*if (typeof __isFromLink__=='undefined')
                 __isFromLink__=false;*/

                if (__isFromLink__) // callback from link click
                {
                    if (__message_show__) {
                        if (undefined === __message__)
                        {
                            __message__ = '';
                        }
                        var __go__ = confirm(__message__ == '' ? '" . esc_js(__('Are you sure you want to delete this post?', 'wp-cred')) . "' : __message__);
                        if (!__go__)
                            return false;
                    }

                    if (__link__.text)
                        __ltext__ = __link__.text;
                    else if (__link__.innerText)
                        __ltext__ = __link__.innerText;

                    var __deltext__ = '" . esc_js(__('Deleting..', 'wp-cred')) . "';
                    // static storage of reference texts of related post delete links
                    _cred_cred_delete_post_handler.refs = _cred_cred_delete_post_handler.refs || {};
                    if (!_cred_cred_delete_post_handler.refs[__link__.id])
                        _cred_cred_delete_post_handler.refs[__link__.id] = __ltext__;
                    if (__link__.text)
                        __link__.text = __deltext__;
                    else if (__link__.innerText)
                        __link__.innerText = __deltext__;

                    __link__.href = _cred_cred_parse_url(__link__.href, {
                        remove: ['_cred_link_id', '_cred_url'],
                        add: {
                            '_cred_link_id': __link__.id,
                            '_cred_url': ''
                        }
                    });

                    // this is set to refresh page
                    if (__link__.className.indexOf('cred-refresh-after-delete') >= 0)
                        __link__.href = _cred_cred_parse_url(__link__.href, {
                            remove: ['_cred_url'],
                            add: {
                                '_cred_url': document.location
                            }
                        });

                    return true;
                }
                else // callback from iframe return function
                {
                    // success
                    if (__result__ && 101 == __result__)
                    {
                    " . $add_string . "
                        var __linkel__ = document.getElementById(__link__);
                        if (__linkel__.text)
                            __linkel__.text = _cred_cred_delete_post_handler.refs[__link__];
                        else if (__linkel__.innerText)
                            __linkel__.innerText = _cred_cred_delete_post_handler.refs[__link__];

                        if (__url__ && __linkel__.className.indexOf('cred-refresh-after-delete') >= 0)
                        {
                            // refresh current page
                            if (__url__ == document.location)
                                location.reload();
                            else // redirect
                                document.location = __url__;
                        }
                    }
                    else
                    {
                        /*if (202 == __result__)
                         alert('" . esc_js(__('Post delete failed', 'wp-cred')) . "');
                         else*/ if (404 == __result__)
                            alert('" . esc_js(__('No post defined', 'wp-cred')) . "');
                        else if (505 == __result__)
                            alert('" . esc_js(__('Permission denied', 'wp-cred')) . "');
                    }
                }
            }
            </script>";

        echo $v;
    }

    public static function cred_edit_post_link($form, $post_id = false, $text = '', $class = '', $style = '', $target = '', $attributes = '') {
        global $post, $current_user;

        if (empty($form))
            return '<strong>' . __('No form specified', 'wp-cred') . '</strong>';

        if ($post_id === false || empty($post_id) || !isset($post_id) || !is_numeric($post_id)) {
            if (!isset($post->ID))
                return '<strong>' . __('No post specified', 'wp-cred') . '</strong>';
            else
                $post_id = $post->ID;
        }

// localise the ID
        if (apply_filters('cred_wpml_get_localised_id', true)) {
            $post_id = self::getLocalisedID(intval($post_id));
        }

        if (!is_numeric($form)) {
            $form_post = get_page_by_title($form, OBJECT, CRED_FORMS_CUSTOM_POST_NAME);
            if (!$form_post)
                return '<strong>' . __('Form does not exist', 'wp-cred') . '</strong>';
            $form = $form_post->ID;
        } else {
            $form = intval($form);
        }

        /**
         * get form settings to check form type
         * 'type' == 'edit'
         * 'post_type' == $post->post_type
         */
        $form_settings = (array) get_post_meta($form, '_cred_form_settings', true);
        if (
                0 || !is_array($form_settings) || empty($form_settings) || !array_key_exists('form', $form_settings) || !array_key_exists('type', $form_settings['form']) || !array_key_exists('post', $form_settings) || !array_key_exists('post_type', $form_settings['post'])
        ) {
            if (current_user_can('manage_options')) {
                return sprintf(
                        '<p class="alert"><strong>%s</strong></p>', __('Missing form configuration.', 'wp-cred')
                );
            }
            return;
        }
        if (get_post_type($post_id) != $form_settings['post']['post_type']) {
            if (current_user_can('manage_options')) {
                return sprintf(
                        '<p class="alert"><strong>%s</strong></p>', __('Edit form link can not be displayed (post type mismatch).', 'wp-cred')
                );
            }
            return;
        }

        if (!current_user_can('edit_own_posts_with_cred_' . $form) && $current_user->ID == $post->post_author) {
//return '<strong>'.__('Do not have permission (edit own with this form)','wp-cred').'</strong>';
            return '';
        }
        if (!current_user_can('edit_other_posts_with_cred_' . $form) && $current_user->ID != $post->post_author) {
//return '<strong>'.__('Do not have permission (edit other with this form)','wp-cred').'</strong>';
            return '';
        }

        $link = get_permalink($post_id);
        $link = esc_url(add_query_arg(array('cred-edit-form' => $form), $link));
// provide WPML localization for hardcoded texts
        $text = str_replace(array('%TITLE%', '%ID%'), array(get_the_title($post_id), $post_id), cred_translate('Edit Link Text', $text, 'CRED Shortcodes'));

        $_atts = array();
        if (!empty($class))
            $_atts[] = 'class="' . esc_attr(str_replace('"', "'", $class)) . '"';
        if (!empty($style))
            $_atts[] = 'style="' . esc_attr(str_replace('"', "'", $style)) . '"';
        if (!empty($target))
            $_atts[] = 'target="' . esc_attr(str_replace('"', "'", $target)) . '"';
        if (!empty($attributes)) {
            $_atts[] = str_replace(array('%eq%', '%dbquo%', '%quot%'), array("=", '"', "'"), $attributes);
        }
        return "<a href='{$link}' " . implode(' ', $_atts) . ">" . $text . "</a>";
    }

    public static function cred_child_link_form($form, $parent_id = null, /* $parent_type='', */ $text = '', $class = '', $style = '', $target = '', $attributes = '') {
        global $post;

        if (empty($form) || !is_numeric($form))
            return '<strong>' . __('No Child Form Page specified', 'wp-cred') . '</strong>';

        $form = intval($form);

        $link = get_permalink($form);

        if ($parent_id !== null) {
            $parent_id = intval($parent_id);

            if ($parent_id < 0 /* && $post->post_type==$parent_type */)
                $parent_id = $post->ID;
            /* elseif ($parent_id<0)
              $parent_id=null; */
        }

        if ($parent_id !== null) {
            $parent_type = get_post_type($parent_id);
// localise the ID
            $parent_id = self::getLocalisedID($parent_id, $parent_type);
            if ($parent_type === false)
                return __('Unknown Parent Type', 'wp-cred');
            $link = esc_url(add_query_arg(array('parent_' . $parent_type . '_id' => $parent_id), $link));
        }

        $_atts = array();
        if (!empty($class))
            $_atts[] = 'class="' . esc_attr(str_replace('"', "'", $class)) . '"';
        if (!empty($style))
            $_atts[] = 'style="' . esc_attr(str_replace('"', "'", $style)) . '"';
        if (!empty($target))
            $_atts[] = 'target="' . esc_attr(str_replace('"', "'", $target)) . '"';
        if (!empty($attributes)) {
            $_atts[] = str_replace(array('%eq%', '%dbquo%', '%quot%'), array("=", '"', "'"), $attributes);
        }
// provide WPML localization for hardcoded texts
        return "<a href='{$link}' " . implode(' ', $_atts) . ">" . cred_translate('Child Link Text', $text, 'CRED Shortcodes') . "</a>";
    }

    public static function cred_form($form, $post_id = false) {
        global $post;

        $specific_post_id = $post_id;

        if (empty($form))
            return '<strong>' . __('No form specified', 'wp-cred') . '</strong>';

        if (empty($post_id) || $post_id === false || !is_numeric($post_id)) {
            if (isset($post->ID))
                $post_id = $post->ID;
        }

// localise the ID
        if ($post_id)
            $post_id = self::getLocalisedID(intval($post_id));

        CRED_Loader::load('CLASS/Form_Builder');
// prevent recursion if form shortcode inside posts content
        remove_shortcode('cred-form', array(__CLASS__, 'credFormShortcode'));
        remove_shortcode('cred_form', array(__CLASS__, 'credFormShortcode'));
        $output = CRED_Form_Builder::getForm($form, $post_id, false, $specific_post_id);
        add_shortcode('cred-form', array(__CLASS__, 'credFormShortcode'));
        add_shortcode('cred_form', array(__CLASS__, 'credFormShortcode'));
        return $output;
    }

    public static function cred_user_form($form, $post_id = false) {
        global $post;

        $specific_post_id = $post_id;

        if (empty($form))
            return '<strong>' . __('No form specified', 'wp-cred') . '</strong>';

        if (empty($post_id) || $post_id === false || !is_numeric($post_id)) {
            if (isset($post->ID))
                $post_id = $post->ID;
        }

// localise the ID
        /* if ($post_id)
          $post_id = self::getLocalisedID(intval($post_id)); */

        CRED_Loader::load('CLASS/Form_Builder');
// prevent recursion if form shortcode inside posts content
        remove_shortcode('cred-user-form', array(__CLASS__, 'credUserFormShortcode'));
        remove_shortcode('cred_user_form', array(__CLASS__, 'credUserFormShortcode'));
        $output = CRED_Form_Builder::getUserForm($form, $post_id, false, $specific_post_id);
        add_shortcode('cred-user-form', array(__CLASS__, 'credUserFormShortcode'));
        add_shortcode('cred_user_form', array(__CLASS__, 'credUserFormShortcode'));
        return $output;
    }

    public static function addShortcodesAndFilters() {
// redirect to localised edit form if needed and possible
        /* if (isset($_GET{'cred-edit-form'}))
          {
          $form_id=intval($_GET{'cred-edit-form'});
          $loc_form_id=intval(self::getLocalisedID($form_id));
          // localised ID is different redirect
          if ($form_id!=$loc_form_id)
          {
          // Remove the previous edit form ID
          $uri=add_query_arg( array('cred-edit-form' => false) );
          // Add localised edit form ID
          $uri=add_query_arg( array('cred-edit-form' => $loc_form_id), $uri );
          // redirect
          wp_redirect($uri);
          exit();
          }
          } */

// check to see if form preview is required
        if (isset($_REQUEST['cred_user_form_preview']))
            add_filter('the_posts', array(__CLASS__, 'preview_user_form'), 5000);
        else
            add_filter('the_posts', array(__CLASS__, 'preview_form'), 5000);

// IMPORTANT: add both formats of shortcodes, because the dashes are strange in shortcodes, so use underscores
// delete post link shortcode
        add_shortcode('cred-delete-post-link', array(__CLASS__, 'credDeletePostLinkShortcode'));
        add_shortcode('cred_delete_post_link', array(__CLASS__, 'credDeletePostLinkShortcode'));

// edit post form link shortcode
        add_shortcode('cred-link-form', array(__CLASS__, 'credFormLinkShortcode'));
        add_shortcode('cred_link_form', array(__CLASS__, 'credFormLinkShortcode'));

// link to child form
        add_shortcode('cred-child-link-form', array(__CLASS__, 'credChildFormLinkShortcode'));
        add_shortcode('cred_child_link_form', array(__CLASS__, 'credChildFormLinkShortcode'));

// form display shortcode
        add_shortcode('cred-form', array(__CLASS__, 'credFormShortcode'));
        add_shortcode('cred_form', array(__CLASS__, 'credFormShortcode'));

// form display shortcode
        add_shortcode('cred-user-form', array(__CLASS__, 'credUserFormShortcode'));
        add_shortcode('cred_user_form', array(__CLASS__, 'credUserFormShortcode'));

// replace content when preview or edit form
        add_action('loop_start', array(__CLASS__, 'overrideContentFilter'), 1000);
        add_action('ddl-layouts-render-start-post-content', array(__CLASS__, 'overrideContentFilter'), 1000);

        if (array_key_exists('cred_form_preview', $_GET)) {
            add_filter('wpv_filter_force_wordpress_archive', array(__CLASS__, 'overrideViewArchive'));
        }
    }

    public static function overrideViewArchive($view_id) {
        return 0;
    }

    public static function preview_form($posts) {
        global $wp, $wp_query, $post;
        static $preview_done = false;

        if (!$preview_done) {

            $preview_done = true;

// allow preview only if form preview key set
            if (!array_key_exists('cred_form_preview', $_GET))
                return $posts;

            $posts = array();
            $posts[] = get_post(intval($_GET['cred_form_preview']));
//Not sure if this one is necessary but might as well set it like a true page
            $wp_query->is_singular = true;
//$wp_query->is_home = false;
            $wp_query->is_archive = false;
            $wp_query->is_category = false;
//Longer permalink structures may not match the fake post slug and cause a 404 error so we catch the error here
            unset($wp_query->query["error"]);
            $wp_query->query_vars["error"] = "";
            $wp_query->is_404 = false;
        }

        return $posts;
    }

    public static function preview_user_form($posts) {
        global $wp, $wp_query, $post;
        static $preview_done = false;

        if (!$preview_done) {

            $preview_done = true;

// allow preview only if form preview key set
            if (!array_key_exists('cred_user_form_preview', $_GET))
                return $posts;

            $posts = array();
            $posts[] = get_post(intval($_GET['cred_user_form_preview']));

//Not sure if this one is necessary but might as well set it like a true page
            $wp_query->is_singular = true;
//$wp_query->is_home = false;
            $wp_query->is_archive = false;
            $wp_query->is_category = false;
//Longer permalink structures may not match the fake post slug and cause a 404 error so we catch the error here
            unset($wp_query->query["error"]);
            $wp_query->query_vars["error"] = "";
            $wp_query->is_404 = false;
        }

        return $posts;
    }

    public static function overrideContentFilter() {
        global $wp_query, $post;

// if it is front page and form preview is required
        if (((array_key_exists('cred_form_preview', $_GET) ||
                array_key_exists('cred_user_form_preview', $_GET)))
// if post edit url is given
                || (array_key_exists('cred-edit-form', $_GET) && is_singular())) {
// remove prev filters
//cred_disable_filters_for('the_content'); //this was causing problems when view templates were added in sidebar widgets
// replace post content with edit form if post editing url is given
            add_filter('the_content', array(__CLASS__, 'replaceContentWithForm'), 1000);
        }
    }

//https://onthegosystems.myjetbrains.com/youtrack/issue/refsites-46
    public static function replaceContentWithForm($content) {
        global $post, $wp_query;

//resolve problem when view templates are added in sidebar widgets
        remove_filter('the_content', array(__CLASS__, 'replaceContentWithForm'), 1000);

// if it is front page and form preview is required
        if (array_key_exists('cred_form_preview', $_GET) /* && is_front_page() */) {
            CRED_Loader::load('CLASS/Form_Builder');
            return CRED_Form_Builder::getForm(intval($_GET['cred_form_preview']), null, true);
        }
        if (array_key_exists('cred_user_form_preview', $_GET) /* && is_front_page() */) {
            CRED_Loader::load('CLASS/Form_Builder');
            return CRED_Form_Builder::getUserForm(intval($_GET['cred_user_form_preview']), null, true);
        }

        global $_creds_created;

        if (!isset($_creds_created))
            $_creds_created = array();

        if (!empty($_creds_created) && in_array($_GET['cred-edit-form'], $_creds_created))
            return apply_filters('the_content', $content);


        if ((strpos($content, 'cred-edit-form=' . $_GET['cred-edit-form']) !== false) ||
                (array_key_exists('cred-edit-form', $_GET) /* && is_singular() */ && !is_admin())) {
            array_push($_creds_created, $_GET['cred-edit-form']);
            CRED_Loader::load('CLASS/Form_Builder');
// get a localised form if exists
            return CRED_Form_Builder::getForm(self::getLocalisedID(intval($_GET['cred-edit-form'])), $post->ID, false);
        }

        if (array_key_exists('cred-edit-form', $_GET) /* && is_singular() */ && !is_admin()) {

            if (strpos($content, 'cred-edit-form=' . $_GET['cred-edit-form']) !== false) {
                array_push($_creds_created, $_GET['cred-edit-form']);
// Show if the content has a cred-edit-form link.
                CRED_Loader::load('CLASS/Form_Builder');
// get a localised form if exists
                return CRED_Form_Builder::getForm(self::getLocalisedID(intval($_GET['cred-edit-form'])), $post->ID, false);
            } else {
// Check if it's called from the_content function or wpv-post-body function.
                $db = debug_backtrace();
//StaticClass::_pre($db);
                foreach ($db as $n => $dbf) {
                    if (isset($dbf['function']) &&
                            (
                            ($dbf['function'] == 'the_content' || $dbf['function'] == 'wpv_shortcode_wpv_post_body')) ||
                            ($dbf['function'] == 'apply_filters' && in_array('the_content', $dbf['args']))
                    ) {
                        array_push($_creds_created, $_GET['cred-edit-form']);
                        CRED_Loader::load('CLASS/Form_Builder');
                        return CRED_Form_Builder::getForm(self::getLocalisedID(intval($_GET['cred-edit-form'])), $post->ID, false);
                    }
                }

//                if (isset($db[3]['function']) && ($db[3]['function'] == 'the_content' || $db[3]['function'] == 'wpv_shortcode_wpv_post_body')) {
//                    CRED_Loader::load('CLASS/Form_Builder');
//                    return CRED_Form_Builder::getForm(self::getLocalisedID(intval($_GET['cred-edit-form'])), $post->ID, false);
//                }
            }
        }

// else do nothing
        return $content;
    }

    /**
     * CRED-Shortcode: cred_delete_post_link
     *
     * Description: Display a link to delete a post
     *
     * Parameters:
     * 'action'=> either 'trash' (sent post to Trash) or 'delete' (completely delete post)
     * 'post' => [optional] Post ID of post to delete
     * 'text'=> [optional] Text to use for link
     * 'class'=> [optional] css class to apply to link
     * 'style'=> [optional] css style to apply to link
     * 'message'=> [optional] message to confirm action
     *
     * Example usage:
     *
     *  Display link for deleting car custom post with ID 145
     * [cred_delete_post_link post="145" text="Delete this car"]
     *
     * There is also a php tag to use in templates and themes that has the same functionality as the shortcode
     * <?php cred_delete_post_link($post_id, $text, $action, $class, $style); ?>
     *
     * Link:
     *
     *
     * Note:
     *  'post'> if post is omitted then current post_id will be used, for example inside Loop
     *  'text'> can use meta-variables like %TITLE% and %ID%
     *
     *
     * */
    public static function credDeletePostLinkShortcode($atts) {
        global $post, $current_user;

        $params = shortcode_atts(array(
            'post' => '',
            'text' => '',
            'redirect' => '',
            'action' => '',
            'class' => '',
            'style' => '',
            'message' => '',
            'message_show' => 1,
            'message_after' => ''
                ), $atts);

        return self::cred_delete_post_link($params['post'], $params['text'], $params['action'], $params['class'], $params['style'], $params['message'], $params['message_after'], $params['message_show'], isset($params['redirect']) && !empty($params['redirect']) ? $params['redirect'] : 0);
    }

    /**
     * CRED-Shortcode: cred_link_form
     *
     * Description: Display a link to edit a post with given form
     *
     * Parameters:
     * 'form' => Form Title or Form ID of form to use.
     * 'post' => [optional] Post ID of post to edit with this form
     * 'text'=> [optional] Text to use for link
     * 'class'=> [optional] css class to apply to link
     * 'style'=> [optional] css style to apply to link
     * 'target'=> [optional] open link in the specific target (_blank,_self,_top)
     * 'attributes'=> [optional] additional html attrubutes (eg onclick)
     *
     * Example usage:
     *
     *  Display link for editing car custom post with ID 145 (use form with title "Edit Car")
     * [cred_link_form form="Edit Car" post="145" text="Edit this car"]
     *
     * There is also a php tag to use in templates and themes that has the same functionality as the shortcode
     * <?php cred_edit_post_link($form, $post_id, $text, $class, $style, $target, $attributes); ?>
     *
     * Link:
     *
     *
     * Note:
     *  'post'> if post is omitted then current post_id will be used, for example inside Loop
     *  'text'> can use meta-variables like %TITLE% and %ID%
     *
     * */
    public static function credFormLinkShortcode($atts) {
        global $post;

        $params = shortcode_atts(array(
            'form' => '',
            'post' => '',
            'text' => '',
            'class' => '',
            'style' => '',
            'target' => '',
            'attributes' => ''
                ), $atts);

        return self::cred_edit_post_link($params['form'], $params['post'], $params['text'], $params['class'], $params['style'], $params['target'], $params['attributes']);
    }

    /**
     * CRED-Shortcode: cred_child_link_form
     *
     * Description: Display a link to create a child post with given form and parent
     *
     * Parameters:
     * 'form' => Page ID containing teh child form.
     * 'parent_type' => Post Type of Parent
     * 'parent_id' => [optional] Parent to set for the child
     * 'text'=> [optional] Text to use for link
     * 'class'=> [optional] css class to apply to link
     * 'style'=> [optional] css style to apply to link
     * 'target'=> [optional] open link in the specific target (_blank,_self,_top)
     * 'attributes'=> [optional] additional html attrubutes (eg onclick)
     *
     * Example usage:
     *
     *  Display link for editing car custom post with ID 145 (use form with title "Edit Car")
     * [cred_child_link_form form="New  Review" parent="145" parent_type='book' text="Add new Review"]
     *
     * Link:
     *
     *
     * Note:
     *
     *
     * */
    public static function credChildFormLinkShortcode($atts) {
        global $post;

        $params = shortcode_atts(array(
            'form' => null,
            /* 'parent_type' => null, */
            'parent_id' => -1,
            'text' => '',
            'class' => '',
            'style' => '',
            'target' => '_self',
            'attributes' => ''
                ), $atts);

        return self::cred_child_link_form($params['form'], $params['parent_id']/* ,$params['parent_type'] */, $params['text'], $params['class'], $params['style'], $params['target'], $params['attributes']);
    }

    /**
     * CRED-Shortcode: cred_form
     *
     * Description: Display a CRED form
     *
     * Parameters:
     * 'form' => Form Title or Form ID of form to display.
     * 'post' => [optional] Post ID of post to edit with this form
     *
     * Example usage:
     *
     *  Display form for editing car custom post with ID 145 (use form with title "Edit Car")
     * [cred-form form="Edit Car" post="145"]
     *  Display form to create a car post (use form with title "Create Car")
     * [cred_form form="Create Car"]
     *  Display form with ID 120
     * [cred_form form="120"]
     *
     * There is also a php tag to use in templates and themes that has the same functionality as the shortcode
     * <?php cred_form($form,$post); ?>
     *
     * Link:
     *
     *
     * Note:
     *  'post'> if post is omitted and form is an edit form, then current post_id will be used, for example inside Loop
     *
     * */
    public static function credFormShortcode($atts) {
        global $post;
        if (is_null($post))
            return null;
        /**
         * clone post object to revert after form
         */
        $orginal = clone $post;
        $params = shortcode_atts(array(
            'form' => '',
            'post' => '',
                ), $atts);

        $out = self::cred_form($params['form'], $params['post']);
        wp_reset_query();
        /**
         * revert orginal $post
         */
        $post = $orginal;
        unset($orginal);
        return $out;
    }

    public static function credUserFormShortcode($atts) {
        global $post;
        if (is_null($post))
            return null;
        /**
         * clone post object to revert after form
         */
        $orginal = clone $post;
        $params = shortcode_atts(array(
            'form' => '',
            'post' => '',
                ), $atts);

        $type = "edit";
        if (empty($params['post'])) {
            $form = get_page_by_title(html_entity_decode($params['form']), OBJECT, CRED_USER_FORMS_CUSTOM_POST_NAME);
            require_once 'FormData.php';
            $formData = new FormData($form->ID, CRED_USER_FORMS_CUSTOM_POST_NAME, false);
            $fields = $formData->getFields();
            $type = $fields['form_settings']->form['type'];
        }
        if ($type == 'edit') {
            if (empty($params['post'])) {
                $user_id = get_current_user_id();
                if ($user_id == 0) {
                    $out = 'You are currently not logged in.';
                } else {
                    $params['post'] = $user_id;
                    $out = self::cred_user_form($params['form'], $params['post']);
                }
            } else
                $out = self::cred_user_form($params['form'], $params['post']);
        } else
            $out = self::cred_user_form($params['form'], $params['post']);

        wp_reset_query();
        /**
         * revert orginal $post
         */
        $post = $orginal;
        unset($orginal);
        return $out;
    }

// auxiliary functions
    public static function __true() {
        return true;
    }

    public static function __false() {
        return false;
    }

    public static function getMediaButtons($id, $params = array()) {
        global $wp_version;
        global $wp_filter;
        static $template_with_media = null, $template_no_media = null, $dummy = "___%%ID%%___";

        $output = '';

        $params = array_merge(array(
            'no_media_button' => true,
            'extra' => ''
                ), (array) $params);

        $no_media_button = (bool) ($params['no_media_button']);

// remove the media button hook
        if ($no_media_button) {
            if (is_null($template_no_media)) {
                $media_button_priority = false;
                $media_filter_hook = null;

                /* if ( !function_exists('media_buttons') )
                  include(ABSPATH . 'wp-admin/includes/media.php'); */

                $media_button_priority = has_action('media_buttons', 'media_buttons');
                if (false !== $media_button_priority) {
                    $media_filter_hook = $wp_filter['media_buttons'][$media_button_priority]['media_buttons'];
                    remove_action('media_buttons', 'media_buttons', $media_button_priority);
                }

                $template_no_media = '';

// render media buttons
                if (version_compare($wp_version, '3.1.4', '>')) {
                    ob_start();
                    do_action('media_buttons', $dummy, '#' . $dummy);
                    $template_no_media = ob_get_clean();
                } else {
                    ob_start();
                    do_action('media_buttons_context', $dummy, '#' . $dummy);
                    $template_no_media = ob_get_clean();
                }

// add again the media button hook
                if (false !== $media_button_priority) {
//add_action('media_buttons', 'media_buttons', $media_button_priority);
// this is better since it prepends the media buttons first and not append it
                    if (!isset($wp_filter['media_buttons'][$media_button_priority])) {
                        $wp_filter['media_buttons'][$media_button_priority] = array();
                    }
                    $wp_filter['media_buttons'][$media_button_priority] = array('media_buttons' => $media_filter_hook) + $wp_filter['media_buttons'][$media_button_priority];
                }
            }
// computed once statically, ;)
            $output = str_replace($dummy, $id, $template_no_media);
        } else {
            if (is_null($template_with_media)) {
                /* if ( !function_exists('media_buttons') )
                  include(ABSPATH . 'wp-admin/includes/media.php'); */


                $template_with_media = '';

// render media buttons for cred forms at editor
                if (version_compare($wp_version, '3.1.4', '>')) {
                    ob_start();
                    do_action('media_buttons', $dummy, '#' . $dummy);
                    $template_with_media = ob_get_clean();
                } else {
                    ob_start();
                    do_action('media_buttons_context', $dummy, '#' . $dummy);
                    $template_with_media = ob_get_clean();
                }
            }
// computed once statically, ;)
            $output = str_replace($dummy, $id, $template_with_media);
        }

// add any extra buttons here
        if (!empty($params['extra'])) {
            $output .= (string) ($params['extra']);
        }
        return $output;
    }

    public static function dummyMediaButtons() {
// use a placeholder
        echo '######__DUMMY_MEDIA_BUTTONS__######';
    }

    public static function getRichEditor($id, $name, $content, $settings = array(), $params = array()) {
        $settings = array_merge(array(
            'textarea_name' => $name,
            'editor_height' => 360,
            'wpautop' => true, // use wpautop?
            'media_buttons' => true, // show insert/upload button(s)
            'tabindex' => '',
            'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the <style> tags, can use "scoped".
            'editor_class' => '', // intended for extra CSS Classes to append to the Editor textarea
            'teeny' => false, // output the minimal editor config used in Press This
            'dfw' => false, // replace the default fullscreen with DFW (needs specific DOM elements and css)
            'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
            'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
                ), (array) $settings);

        $params = array_merge(array(
            'custom_media_buttons' => false,
            'extra' => ''
                ), (array) $params);

// handle custom media buttons
        if ($params['custom_media_buttons']) {
            $custom_buttons = ($settings['media_buttons']) ? self::getMediaButtons($id, array_merge($params, array('no_media_button' => false))) : self::getMediaButtons($id, array_merge($params, array('no_media_button' => true)));
// make sure action is added
            if (!function_exists('media_buttons'))
                include(ABSPATH . 'wp-admin/includes/media.php');
            $prev_buttons = cred_disable_filters_for('media_buttons');
            add_action('media_buttons', array(__CLASS__, 'dummyMediaButtons'));
        }

        add_filter('user_can_richedit', array(__CLASS__, '__true'), 100);
        ob_start();
        wp_editor($content, $id, $settings);
        $output = ob_get_clean();
        remove_filter('user_can_richedit', array(__CLASS__, '__true'), 100);

// handle custom media buttons
        if ($params['custom_media_buttons']) {
            cred_re_enable_filters_for('media_buttons', $prev_buttons);
            $output = str_replace('######__DUMMY_MEDIA_BUTTONS__######', $custom_buttons, $output);
        }

        return $output;
    }

    public static function strHash($str) {
        return md5(preg_replace('/\s+/', '', $str));
    }

    public static function mergeArrays() {
        if (func_num_args() < 1)
            return;

        $arrays = func_get_args();
        $merged = array_shift($arrays);

        $isTargetObject = false;
        if (is_object($merged)) {
            $isTargetObject = true;
            $merged = (array) $merged;
        }

        foreach ($arrays as $arr) {
            $isObject = false;
            if (is_object($arr)) {
                $isObject = true;
                $arr = (array) $arr;
            }

            foreach ($arr as $key => $val) {
                if (array_key_exists($key, $merged) && (is_array($val) || is_object($val))) {
                    $merged[$key] = self::mergeArrays($merged[$key], $arr[$key]);
                    if (is_object($val))
                        $merged[$key] = (object) $merged[$key];
                } else
                    $merged[$key] = $val;
            }

            /* if ($isObject)
              {
              $arr=(object)$arr;
              } */
        }
        if ($isTargetObject) {
            $isTargetObject = false;
            $merged = (object) $merged;
        }
        return $merged;
    }

    public static function applyDefaults($arr, $defaults = array()) {
        if (!empty($defaults)) {
            foreach ($arr as $ii => $item)
                $arr[$ii] = self::mergeArrays($defaults, $arr[$ii]);
        }
        return $arr;
    }

    public static function filterByKeys($a, $f) {
        return array_intersect_key((array) $a, array_flip((array) $f));
    }

    public static function check_wpml_string() {
        global $WPML_String_Translation;

        if (!isset($WPML_String_Translation) || !function_exists('icl_register_string')) {
            return false;
        }
        global $sitepress, $iclTranslationManagement;
        if (is_object($sitepress) && (!is_object($iclTranslationManagement) ||
                'TranslationManagement' != get_class($iclTranslationManagement))) {
//require_once( ICL_PLUGIN_PATH . '/lib/icl_api.php' );
//require_once( ICL_PLUGIN_PATH . '/lib/xml2array.php' );
            require_once( ICL_PLUGIN_PATH . '/inc/translation-management/translation-management.class.php' );
//require_once( ICL_PLUGIN_PATH . '/inc/translation-management/pro-translation.class.php' );
//global $ICL_Pro_Translation;
            $iclTranslationManagement = new TranslationManagement();
//$ICL_Pro_Translation = new ICL_Pro_Translation();
        }

        return true;
    }

    public static function getUsersByRole($roles) {
        global $wpdb;
        if (!is_array($roles)) {
            $roles = explode(",", $roles);
            array_walk($roles, 'trim');
        }
        $sql = '
            SELECT  u.ID, u.display_name, u.user_email
            FROM        ' . $wpdb->users . ' AS u INNER JOIN ' . $wpdb->usermeta . ' AS um
            ON      u.ID  = um.user_id
            WHERE   um.meta_key     =       \'' . $wpdb->prefix . 'capabilities\'
            AND     (
        ';
        $i = 1;
        foreach ($roles as $role) {
            $sql .= ' um.meta_value LIKE    \'%"' . cred_wrap_esc_like($role) . '"%\' ';
            if ($i < count($roles))
                $sql .= ' OR ';
            $i++;
        }
        $sql .= ' ) ';
        $sql .= ' ORDER BY u.display_name ';
        $users = $wpdb->get_results($sql);
        return $users;
    }

}
