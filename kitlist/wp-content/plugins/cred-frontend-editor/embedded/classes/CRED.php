<?php
require "StaticClass.php";
require "CredForm.php";
require "common/cred_functions.php";

/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk_new/embedded/classes/CRED.php $
 * $LastChangedDate: 2015-03-31 12:39:37 +0200 (mar, 31 mar 2015) $
 * $LastChangedRevision: 32729 $
 * $LastChangedBy: francesco $
 *
 */

/**
 * Main Class
 *
 * Main class of the plugin
 * Class encapsulates all hook handlers
 *
 */
final class CRED_CRED {

    public static $help = array();
    public static $help_link_target = '_blank';
    public static $settingsPage = null;
    private static $prefix = '_cred_';

    /*
     * Initialize plugin enviroment
     */

//    public static function item_filter($items) {
//        $items[] = "ciao";
//        return $items;
//    }

    public static function init() {

        add_filter('wpcf_exclude_meta_boxes_on_post_type', array('StaticClass', 'my_cred_exclude'), 10, 1);

//        add_filter('get_items_with_flag', array(__CLASS__, "item_filter"), 10, 1);
        // plugin init
        // NOTE Early Init, in order to catch up with early hooks by 3rd party plugins (eg CRED Commerce)
        add_action('init', array('CRED_CRED', '_init_'), 1);
        CRED_Loader::load('CLASS/Notification_Manager');
        CRED_Notification_Manager::init();

        /*
          if (isset($_GET['cred-edit-form'])&&!empty($_GET['cred-edit-form'])) {
          add_action('the_content', 'try_to_remove_view_shortcode', 0);
          add_action('the_content', 'try_to_add_view_shortcode', 9);
          }
         */

        // try to catch user shortcodes (defined by [...]) and solve shortcodes inside shortcodes
        // adding filter with priority before do_shortcode and other WP standard filters
        add_filter('the_content', 'cred_do_shortcode', 9);

        //https://onthegosystems.myjetbrains.com/youtrack/issue/cred-131#
        //if (!is_admin()) add_filter('cf_fields_value_save', array('CRED_CRED', 'cf_sanitize_values_on_save'));
    }

//    public static function cf_sanitize_values_on_save($value) {
//        if (current_user_can('unfiltered_html')) {
//            if (is_array($value)) {
//                $value = @array_map('wp_filter_post_kses', $value);
//            } else {
//                $value = wp_filter_post_kses($value);
//            }
//        } else {
//            if (is_array($value)) {
//                $value = @array_map('wp_filter_kses', $value);
//            } else {
//                $value = wp_filter_kses($value);
//            }
//        }
//        return $value;
//    }

    /**
     * is_embedded if CRED_Admin class does not exist is embedded plugin
     * @return type
     */
    public static function is_embedded() {
        return (false === class_exists('CRED_Admin'));
    }

    // main init hook
    public static function _init_() {
        global $wp_version, $post;

        // load help settings (once)
        self::$help = CRED_Loader::getVar(CRED_INI_PATH . "/help.ini.php");
        // set up models and db settings
        CRED_Helper::prepareDB();
        // needed by others
        self::$settingsPage = admin_url('admin.php') . '?page=CRED_Settings';
        // localize forms, support for WPML
        CRED_Helper::localizeForms();
        // setup custom capabilities
        CRED_Helper::setupCustomCaps();
        // setup custom user caps
        CRED_Helper::setupCustomUserCaps();
        // setup extra admin hooks for other plugins
        CRED_Helper::setupExtraHooks();

        if (is_admin()) {

            if (self::is_embedded()) {
                self::initAdmin();
            } else {
                CRED_Admin::initAdmin();
            }
//            if ($_GET['a']=='1') {
//                require_once CRED_CLASSES_PATH . "/CredUserFormCreator.php";
//                CredUserFormCreator::cred_create_form(time(), 'edit', array('subscriber','author'), false, false, false);
//            }
        } else {
            // init form processing to check for submits
            CRED_Loader::load('CLASS/Form_Builder');
            CRED_Form_Builder::init();
        }
        // add form short code hooks and filters, to display forms on front end
        CRED_Helper::addShortcodesAndFilters();

        // handle Ajax calls
        CRED_Router::addCalls(array(
            'cred_skype_ajax' => array(
                'nopriv' => true,
                'callback' => array(__CLASS__, 'cred_skype_ajax')
            ),
            /* 'cred-ajax-tag-search' => array(
              'nopriv' => true,
              'callback' => array(__CLASS__, 'cred_ajax_tag_search')
              ), */
            'cred-ajax-delete-post' => array(
                'nopriv' => true,
                'callback' => array(__CLASS__, 'cred_ajax_delete_post')
            )
        ));

        CRED_Router::addRoutes('cred', array(
            'Forms' => 0, // Forms controller
            'Posts' => 0, // Posts controller
            'Settings' => 0, // Settings controller
            'Generic_Fields' => 0  // Generic Fields controller
        ));
        /* CRED_Router::addPages('cred', array(
          )); */
    }

    public static function initAdmin() {
        global $wp_version, $post;

        // add plugin menus
        // setup js, css assets
        CRED_Helper::setupAdmin();

        add_action('admin_menu', array(__CLASS__, 'admin_menu'), 20);

        // add media buttons for cred forms at editor
//        if (version_compare($wp_version, '3.1.4', '>')) {
//            add_action('media_buttons', array(__CLASS__, 'addFormsButton'), 20, 2);            
//        } else {
//            add_action('media_buttons_context', array(__CLASS__, 'addFormsButton'), 20, 2);
//        }
        // integrate with Views
        add_filter('wpv_meta_html_add_form_button', array(__CLASS__, 'addCREDButton'), 20, 2);

        //WATCHOUT: remove custom meta boxes from cred forms (to avoid any problems)
        // add custom meta boxes for cred forms
        //add_action('add_meta_boxes_' . CRED_FORMS_CUSTOM_POST_NAME, array(__CLASS__, 'addMetaBoxes'), 20, 1);
        // save custom fields of cred forms
        //add_action('save_post', array(__CLASS__, 'saveFormCustomFields'), 10, 2);
        // IMPORTANT: drafts should now be left with post_status=draft, maybe show up because of previous versions
        //add_filter('wp_insert_post_data', array(__CLASS__, 'forcePrivateforForms'));
    }

    public static function admin_menu() {
        if (isset($_GET['page']) && 'cred-embedded' == $_GET['page']) {
            $cap = 'manage_options';
            // DEVCYCLE this should not be in the tools.php menu at all
            add_submenu_page(
                    'admin.php', __('Embedded CRED', 'wp-cred'), __('Embedded CRED', 'wp-cred'), CRED_CAPABILITY, 'cred-embedded', 'cred_embedded_html');
        }
    }

    public static function media() {
        global $wp_version;
        // add media buttons for cred forms at editor
        if (version_compare($wp_version, '3.1.4', '>')) {
            add_action('media_buttons', array(__CLASS__, 'addFormsButton'), 20, 2);
        } else {
            add_action('media_buttons_context', array(__CLASS__, 'addFormsButton'), 20, 2);
        }
    }

    public static function setFormsAndButtons() {
        // integrate with Views
        add_filter('wpv_meta_html_add_form_button', array(__CLASS__, 'addCREDButton'), 20, 2);
    }

    // function to handle the media buttons associated to forms, like  Scaffold,Insert Shortcode, etc..
    public static function addFormsButton($context, $text_area = 'textarea#content') {

        if (!apply_filters('toolset_editor_add_form_buttons', true)) {
            return;
        }

        global $wp_version, $post;
        //static $add_only_once=0;

        if (!isset($post) || empty($post) || !isset($post->post_type)) {
            return '';
        }

        if ($post->post_type == CRED_FORMS_CUSTOM_POST_NAME) {
            // WP 3.3 changes ($context arg is actually a editor ID now)
            if (version_compare($wp_version, '3.1.4', '>') && !empty($context)) {
                $text_area = $context;
            }

            $out = '';
            if ('content' == $context) {
                $addon_buttons = array();
                $shortcode_but = '';
                $shortcode_but = CRED_Loader::tpl('insert-field-shortcode-button', array(
                            'help' => self::$help,
                            'help_target' => self::$help_link_target
                ));

                $shortcode2_but = '';
                $fields_model = CRED_Loader::get('MODEL/Fields');
                $shortcode2_but = CRED_Loader::tpl('insert-generic-field-shortcode-button', array(
                            'gfields' => $fields_model->getTypesDefaultFields(),
                            'help' => self::$help,
                            'help_target' => self::$help_link_target
                ));

                $forms_model = CRED_Loader::get('MODEL/Forms');
                $settings = $forms_model->getFormCustomField($post->ID, 'form_settings');
                $scaffold_but = '';
                $scaffold_but = CRED_Loader::tpl('scaffold-button', array(
                            'include_captcha_scaffold' => isset($settings->form['include_captcha_scaffold']) ? $settings->form['include_captcha_scaffold'] : false,
                            'include_wpml_scaffold' => isset($settings->form['include_wpml_scaffold']) ? $settings->form['include_wpml_scaffold'] : false,
                            'help' => self::$help,
                            'help_target' => self::$help_link_target
                ));

                $preview_but = '';
                ob_start();
                ?><span id="cred-preview-button" class="cred-media-button">
                    <a class='button cred-button' href="javascript:;" title='<?php _e('Preview', 'wp-cred'); ?>'><i class="icon-camera ont-icon-18"></i> <?php _e('Preview', 'wp-cred'); ?></a>
                </span><?php
                $preview_but = ob_get_clean();

                $addon_buttons['scaffold'] = $scaffold_but;
                $addon_buttons['post_fields'] = $shortcode_but;
                $addon_buttons['generic_fields'] = $shortcode2_but;
                $addon_buttons['preview'] = $preview_but;
                $addon_buttons = apply_filters('cred_wpml_glue_generate_insert_button_block', $addon_buttons, $insert_after = 2);
                $out = implode('&nbsp;', array_values($addon_buttons));
            }

            // WP 3.3 changes
            if (version_compare($wp_version, '3.1.4', '>')) {
                echo $out;
            } else {
                return $context . $out;
            }
        } else {
            if ($post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) {
                // WP 3.3 changes ($context arg is actually a editor ID now)
                if (version_compare($wp_version, '3.1.4', '>') && !empty($context)) {
                    $text_area = $context;
                }

                $out = '';
                if ('content' == $context) {
                    $addon_buttons = array();
                    $shortcode_but = '';
                    $shortcode_but = CRED_Loader::tpl('insert-user-field-shortcode-button', array(
                                'help' => self::$help,
                                'help_target' => self::$help_link_target
                    ));

                    $shortcode2_but = '';
                    $fields_model = CRED_Loader::get('MODEL/Fields');
                    $shortcode2_but = CRED_Loader::tpl('insert-generic-field-shortcode-button', array(
                                'gfields' => $fields_model->getTypesDefaultFields(),
                                'help' => self::$help,
                                'help_target' => self::$help_link_target
                    ));

                    $forms_model = CRED_Loader::get('MODEL/UserForms');
                    $settings = $forms_model->getFormCustomField($post->ID, 'form_settings');
                    $scaffold_but = '';
                    $scaffold_but = CRED_Loader::tpl('user-scaffold-button', array(
                                
                        'autogenerate_username_scaffold' => isset($settings->form['autogenerate_username_scaffold']) ? $settings->form['autogenerate_username_scaffold'] : true,
                        'autogenerate_nickname_scaffold' => isset($settings->form['autogenerate_nickname_scaffold']) ? $settings->form['autogenerate_nickname_scaffold'] : true,
                        'autogenerate_password_scaffold' => isset($settings->form['autogenerate_password_scaffold']) ? $settings->form['autogenerate_password_scaffold'] : true,
                        
                                'include_captcha_scaffold' => isset($settings->form['include_captcha_scaffold']) ? $settings->form['include_captcha_scaffold'] : false,
                                'include_wpml_scaffold' => isset($settings->form['include_wpml_scaffold']) ? $settings->form['include_wpml_scaffold'] : false,
                                'help' => self::$help,
                                'help_target' => self::$help_link_target
                    ));

                    $preview_but = '';
                    ob_start();
                    ?><span id="cred-preview-button" class="cred-media-button">
                        <a class='button cred-button' href="javascript:;" title='<?php _e('Preview', 'wp-cred'); ?>'><i class="icon-camera ont-icon-18"></i> <?php _e('Preview', 'wp-cred'); ?></a>
                    </span><?php
                    $preview_but = ob_get_clean();

                    $addon_buttons['scaffold'] = $scaffold_but;
                    $addon_buttons['post_fields'] = $shortcode_but;
                    $addon_buttons['generic_fields'] = $shortcode2_but;
                    $addon_buttons['preview'] = $preview_but;
                    $addon_buttons = apply_filters('cred_wpml_glue_generate_insert_button_block', $addon_buttons, $insert_after = 2);
                    $out = implode('&nbsp;', array_values($addon_buttons));
                }

                // WP 3.3 changes
                if (version_compare($wp_version, '3.1.4', '>')) {
                    echo $out;
                } else {
                    return $context . $out;
                }
            } else {
                if (is_string($context) && 'content' != $context) { // allow button only on main area
                    $out = ''; //self::addCREDButton('', $context);
                    // WP 3.3 changes
                    if (version_compare($wp_version, '3.1.4', '>')) {
                        echo $out;
                        return;
                    } else {
                        return $context . $out;
                    }
                }
                $fm = CRED_Loader::get('MODEL/Forms');
                $forms = $fm->getFormsForTable(0, -1);

                $fm = CRED_Loader::get('MODEL/UserForms');
                $user_forms = $fm->getFormsForTable(0, -1);

                // WP 3.3 changes ($context arg is actually a editor ID now)
                if (version_compare($wp_version, '3.1.4', '>') && !empty($context)) {
                    $text_area = $context;
                }

                $addon_buttons = array();
                $shortcode_but = '';
                $shortcode_but = CRED_Loader::tpl('insert-form-shortcode-button', array(
                            'forms' => $forms,
                            'user_forms' => $user_forms,
                            'help' => self::$help,
                            'help_target' => self::$help_link_target
                ));
                $addon_buttons['cred_shortcodes'] = $shortcode_but;
                $out = implode('&nbsp;', array_values($addon_buttons));

                // WP 3.3 changes
                if (version_compare($wp_version, '3.1.4', '>')) {
                    echo $out;
                } else {
                    return $context . $out;
                }
            }
        }
    }

    public static function addCREDButton($v, $area) {
        static $id = 1;

        $id++;
        $m = CRED_Loader::get('MODEL/Forms');
        $forms = $m->getFormsForTable(0, -1);

        $m = CRED_Loader::get('MODEL/UserForms');
        $uforms = $m->getFormsForTable(0, -1);

        $shortcode_but = '';
        $shortcode_but = CRED_Loader::tpl('insert-form-shortcode-button-extra', array(
                    'id' => $id,
                    'forms' => $forms,
                    'user_forms' => $uforms,
                    'help' => self::$help,
                    'content' => $area,
                    'help_target' => self::$help_link_target
        ));

        $out = $shortcode_but;

        return $out;
    }

    public static function route($path = '', $params = null, $raw = true) {
        return CRED_Router::getRoute('cred', $path, $params, $raw);
    }

    //Fix issue about https on frontend
    public static function routeAjax($action) {
        $url = admin_url('admin-ajax.php', 'http') . '?action=' . $action;
        //if is_ssl and url does not contains https
        if (is_ssl() && strpos($url, 'https://') === false) {
            $url = str_replace("http", "https", $url);
        }
        return $url;
    }

    /**
     * @deprecated since version 1.3.4
     * @global type $wpdb
     * @global type $sitepress
     * @global type $wp_version
     */
    // duplicated from wp ajax function
    public static function cred_ajax_tag_search() {
        global $wpdb;

        if (isset($_GET['tax'])) {
            $taxonomy = sanitize_key($_GET['tax']);
            $tax = get_taxonomy($taxonomy);
            if (!$tax)
                wp_die(0);
            // possible issue here, anyway bypass for now
            /* if ( ! current_user_can( $tax->cap->assign_terms ) )
              wp_die( -1); */
        } else {
            wp_die(0);
        }

        $s = stripslashes($_GET['q']);

        $comma = _x(',', 'tag delimiter');
        if (',' !== $comma)
            $s = str_replace($comma, ',', $s);
        if (false !== strpos($s, ',')) {
            $s = explode(',', $s);
            $s = $s[count($s) - 1];
        }
        $s = trim($s);
        if (strlen($s) < 2)
            wp_die(); // require 2 chars for matching

        global $sitepress, $wp_version;
        $post_id = intval($_GET['post_id']);
        if (isset($sitepress) && isset($post_id)) {
            $post_type = get_post_type($post_id);
            $post_language = $sitepress->get_element_language_details($post_id, 'post_' . $post_type);
            $lang = $post_language->language_code;
            $current_language = $sitepress->get_current_language();
            //$sitepress->switch_lang($post_language->language_code, false);            
            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/187413931/comments
            $results = $wpdb->get_col($wpdb->prepare("SELECT t.name FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id JOIN {$wpdb->prefix}icl_translations tr ON tt.term_taxonomy_id = tr.element_id WHERE tt.taxonomy = %s AND tr.language_code = %s AND tr.element_type = %s AND t.name LIKE (%s)", $taxonomy, $lang, 'tax_' . $taxonomy, '%' . cred_wrap_esc_like($s) . '%'));
            //$sitepress->switch_lang($current_language);
        } else {
            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/187413931/comments
            $results = $wpdb->get_col($wpdb->prepare("SELECT t.name FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.name LIKE (%s)", $taxonomy, '%' . cred_wrap_esc_like($s) . '%'));
        }

        echo join($results, "\n");
        wp_die();
    }

    public static function cred_ajax_delete_post() {
        CRED_Loader::get("CONTROLLER/Posts")->deletePost($_GET, $_POST);
        wp_die();
    }

    // link CRED ajax call to wp-types ajax call (use wp-types for this)
    public static function cred_skype_ajax() {
        do_action('wp_ajax_wpcf_ajax');
        wp_die();
    }

    public static function getPostAdminEditLink($post_id) {
        return admin_url('post.php') . '?action=edit&post=' . $post_id;
    }

    public static function getFormEditLink($form_id) {
        //return admin_url('post.php').'?action=edit&post='.$form_id;
        if (self::is_embedded())
            return admin_url('admin.php') . '?page=cred-embedded&cred_id=' . $form_id;
        else
            return get_edit_post_link($form_id);
    }

    public static function getNewFormLink($abs = true) {
        return ($abs) ? admin_url('post-new.php') . '?post_type=' . CRED_FORMS_CUSTOM_POST_NAME : 'post-new.php?post_type=' . CRED_FORMS_CUSTOM_POST_NAME;
    }

    public static function getNewUserFormLink($abs = true) {
        return ($abs) ? admin_url('post-new.php') . '?post_type=' . CRED_USER_FORMS_CUSTOM_POST_NAME : 'post-new.php?post_type=' . CRED_USER_FORMS_CUSTOM_POST_NAME;
    }

}
