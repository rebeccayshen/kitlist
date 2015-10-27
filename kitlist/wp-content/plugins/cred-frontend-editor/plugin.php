<?php

/*
  Plugin Name: CRED Frontend Editor
  Plugin URI: http://wp-types.com/home/cred/
  Description: Create Edit Delete Wordpress content (ie. posts, pages, custom posts) from the front end using fully customizable forms
  Version: 1.4.1
  Author: OnTheGoSystems
  Author URI: http://www.onthegosystems.com/
  License: GPLv2
 *
 */

/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk/plugin.php $
 * $LastChangedDate: 2014-11-11 10:17:03 +0100 (mar, 11 nov 2014) $
 * $LastChangedRevision: 28729 $
 * $LastChangedBy: francesco $
 *
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set("display_errors", 1);
}

if (defined('CRED_FE_VERSION'))
    return;



// TODO add hook when cloning, so 3rd-party can add its own
// TODO use WP Cache object to cache queries(in base model) and templates(in loader DONE)
/* removed */
// current version
define('CRED_FE_VERSION', '1.4.1');
// configuration constants
define('CRED_NAME', 'CRED');
define('CRED_CAPABILITY', 'manage_options');
define('CRED_FORMS_CUSTOM_POST_NAME', 'cred-form');
//CredUserForms
define('CRED_USER_FORMS_CUSTOM_POST_NAME', 'cred-user-form');
// for module manager cred support
define('_CRED_MODULE_MANAGER_KEY_', 'cred');
define('_CRED_MODULE_MANAGER_USER_KEY_', 'cred-user');
// enable loading grouped assets with one call, much faster
//define('CRED_CONCAT_ASSETS', true);
// used for DEV or DEBUG purposes, should NOT be used on live
//define('CRED_DEV',true);
define('CRED_DEBUG', false);
//define('CRED_DEBUG_ACCESS',true);
//define('CRED_DISABLE_SUBMISSION', true);
//to prevent strict warnings in debug
date_default_timezone_set(@date_default_timezone_get());

define('CRED_NOTIFICATION_4_AUTOGENERATION', true);

//if ( function_exists('realpath') ) {    
//    define('CRED_FILE_PATH', realpath(__FILE__));
//} else {
//    define('CRED_FILE_PATH', __FILE__);
//}
//
//define('CRED_FILE_NAME', basename(CRED_FILE_PATH));
//define('CRED_PLUGIN_PATH', dirname(CRED_FILE_PATH));
//define('CRED_PLUGIN_FOLDER', basename(CRED_PLUGIN_PATH));
//echo "<br>".basename(CRED_FILE_PATH);
//echo "<br>".dirname(CRED_FILE_PATH);
//echo "<br>".basename(dirname(CRED_FILE_PATH));

define('CRED_ROOT_PLUGIN_PATH', dirname(__FILE__));
define('CRED_ROOT_CLASSES_PATH', CRED_ROOT_PLUGIN_PATH . '/classes');

define('CRED_FILE_PATH', CRED_ROOT_PLUGIN_PATH);
define('CRED_FILE_NAME', CRED_ROOT_PLUGIN_PATH);
define('CRED_PLUGIN_PATH', CRED_ROOT_PLUGIN_PATH . "/embedded");
define('CRED_PLUGIN_FOLDER', basename(CRED_FILE_PATH) . "/embedded");

define('CRED_PLUGIN_NAME', CRED_PLUGIN_FOLDER . '/' . CRED_FILE_NAME);

if (function_exists('plugin_basename')) {
    define('CRED_PLUGIN_BASENAME', plugin_basename(__FILE__));
} else {
    define('CRED_PLUGIN_BASENAME', CRED_PLUGIN_NAME);
}

define('CRED_ASSETS_PATH', CRED_PLUGIN_PATH . '/assets');
define('CRED_CLASSES_PATH', CRED_PLUGIN_PATH . '/classes');
define('CRED_COMMON_PATH', CRED_PLUGIN_PATH . '/classes/common');
define('CRED_CONTROLLERS_PATH', CRED_PLUGIN_PATH . '/controllers');
define('CRED_MODELS_PATH', CRED_PLUGIN_PATH . '/models');
define('CRED_VIEWS_PATH', CRED_PLUGIN_PATH . '/views');
define('CRED_VIEWS_PATH2', CRED_PLUGIN_FOLDER . '/views');
define('CRED_TABLES_PATH', CRED_PLUGIN_PATH . '/views/tables');
define('CRED_TEMPLATES_PATH', CRED_PLUGIN_PATH . '/views/templates');
//define('CRED_LOCALE_PATH_DEFAULT',CRED_PLUGIN_FOLDER.'/locale');// Old definition, DEPRECATED
define('CRED_LOGS_PATH', CRED_PLUGIN_PATH . '/logs');
define('CRED_INI_PATH', CRED_PLUGIN_PATH . '/classes/ini');

// allow to define locale path externally
/*
  if (!defined('CRED_LOCALE_PATH')) {
  define('CRED_LOCALE_PATH',CRED_LOCALE_PATH_DEFAULT);// Old definition, DEPRECATED
  }
 */
if (!interface_exists('CRED_Friendable')) {
    /*
     *   Friend Classes (quasi-)Design Pattern
     */

    interface CRED_Friendable {
        
    }

    interface CRED_FriendableStatic {
        
    }

    interface CRED_Friendly {
        
    }

    interface CRED_FriendlyStatic {
        
    }

}
/*
 *
 * Load common and local localization
 */
define('CRED_LOCALE_PATH', CRED_PLUGIN_PATH);
if (!defined('WPT_LOCALIZATION')) {
    require_once( CRED_PLUGIN_PATH . '/toolset/toolset-common/localization/wpt-localization.php' );
}
new WPToolset_Localization('wp-cred', CRED_LOCALE_PATH, 'wp-cred-%s');

// Path to common code
if (!defined('WPTOOLSET_COMMON_PATH')) {
    define('WPTOOLSET_COMMON_PATH', CRED_PLUGIN_PATH . '/toolset/toolset-common');
}

// include loader
include(CRED_PLUGIN_PATH . '/loader.php');

if (function_exists('plugins_url')) {
    define('CRED_PLUGIN_URL', plugins_url() . '/' . CRED_PLUGIN_FOLDER);
} else {
    // determine plugin url manually, as robustly as possible
    define('CRED_PLUGIN_URL', CRED_Loader::getFileUrl(CRED_FILE_PATH));
}
define('CRED_FILE_URL', CRED_PLUGIN_URL . '/' . CRED_FILE_NAME);
define('CRED_ASSETS_URL', CRED_PLUGIN_URL . '/assets');

// load on the go resources
require_once CRED_PLUGIN_PATH . '/toolset/onthego-resources/loader.php';
onthego_initialize(CRED_PLUGIN_PATH . '/toolset/onthego-resources/', CRED_PLUGIN_URL . '/toolset/onthego-resources/');

// whether to try to load assets in concatenated form, much faster
// tested on single site/multisite subdomains/multisite subfolders
if (!defined('CRED_CONCAT_ASSETS'))
    define('CRED_CONCAT_ASSETS', false); // I've disabled this as it was causing compatibility issues with font-awesome in Views 1.3

    
// enable CRED_DEBUG, on top of this file
/* cred_log($_SERVER);
  cred_log(CRED_Loader::getDocRoot());
  cred_log(CRED_Loader::getBaseUrl());
  cred_log(CRED_PLUGIN_URL); */

// register assets
CRED_Loader::add('assets', array(
    'SCRIPT' => array(
        'cred_console_polyfill' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => null,
            'path' => CRED_ASSETS_URL . '/common/js/console_polyfill.js',
            'src' => CRED_ASSETS_PATH . '/common/js/console_polyfill.js'
        ),
        'cred_template_script' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('jquery', 'jquery-ui-sortable', 'jquery-ui-dialog', 'wp-pointer'),
            'path' => CRED_ASSETS_URL . '/common/js/gui.js',
            'src' => CRED_ASSETS_PATH . '/common/js/gui.js'
        ),
        'cred_codemirror_dev' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => null,
            'path' => CRED_ASSETS_URL . '/third-party/codemirror.js',
            'src' => CRED_ASSETS_PATH . '/third-party/codemirror.js'
        ),
        'cred_extra' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('jquery', 'jquery-effects-scale'),
            'path' => CRED_ASSETS_URL . '/common/js/extra.js',
            'src' => CRED_ASSETS_PATH . '/common/js/extra.js'
        ),
        'cred_utils' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('jquery', 'cred_extra'),
            'path' => CRED_ASSETS_URL . '/common/js/utils.js',
            'src' => CRED_ASSETS_PATH . '/common/js/utils.js'
        ),
        'cred_gui' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('jquery', 'jquery-ui-dialog', 'wp-pointer'),
            'path' => CRED_ASSETS_URL . '/common/js/gui.js',
            'src' => CRED_ASSETS_PATH . '/common/js/gui.js'
        ),
        'cred_mvc' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('jquery'),
            'path' => CRED_ASSETS_URL . '/common/js/mvc.js',
            'src' => CRED_ASSETS_PATH . '/common/js/mvc.js'
        ),
        'cred_cred_dev' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('jquery', 'underscore', 'cred_console_polyfill', 'cred_codemirror_dev', 'cred_extra', 'cred_utils', 'cred_gui', 'cred_mvc'),
            'path' => CRED_ASSETS_URL . '/js/cred.js',
            'src' => CRED_ASSETS_PATH . '/js/cred.js'
        ),
        'cred_cred_nocodemirror_dev' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('jquery', 'underscore', 'cred_console_polyfill', 'cred_extra', 'cred_utils', 'cred_gui', 'cred_mvc'),
            'path' => CRED_ASSETS_URL . '/js/cred.js',
            'src' => CRED_ASSETS_PATH . '/js/cred.js'
        ),
        'cred_cred_post_dev' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('jquery', 'cred_console_polyfill', 'cred_extra', 'cred_utils', 'cred_gui'),
            'path' => CRED_ASSETS_URL . '/js/post.js',
            'src' => CRED_ASSETS_PATH . '/js/post.js'
        ),
        'cred_cred_nocodemirror' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('jquery', 'underscore', 'jquery-ui-dialog', 'wp-pointer', 'jquery-effects-scale', 'cred_extra', 'cred_utils', 'cred_gui', 'cred_mvc'),
            'path' => CRED_ASSETS_URL . '/js/cred.js',
            'src' => CRED_ASSETS_PATH . '/js/cred.js'
        ),
        'cred_wizard_dev' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('cred_cred_dev'),
            'path' => CRED_ASSETS_URL . '/js/wizard.js',
            'src' => CRED_ASSETS_PATH . '/js/wizard.js'
        ),
    ),
    'STYLE' => array(
        'cred_template_style' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('wp-admin', 'colors-fresh', 'toolset-font-awesome', 'cred_cred_style_nocodemirror_dev'),
            'path' => CRED_ASSETS_URL . '/css/gfields.css',
            'src' => CRED_ASSETS_PATH . '/css/gfields.css'
        ),
        'cred_codemirror_style_dev' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => null,
            'path' => CRED_ASSETS_URL . '/third-party/codemirror.css',
            'src' => CRED_ASSETS_PATH . '/third-party/codemirror.css'
        ),
        'toolset-font-awesome' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => null,
            'path' => CRED_ASSETS_URL . '/common/css/font-awesome.min.css',
            'src' => CRED_ASSETS_PATH . '/common/css/font-awesome.min.css'
        ),
        'cred_cred_style_dev' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('toolset-font-awesome', 'cred_codemirror_style_dev', 'wp-jquery-ui-dialog', 'wp-pointer'),
            'path' => CRED_ASSETS_URL . '/css/cred.css',
            'src' => CRED_ASSETS_PATH . '/css/cred.css'
        ),
        'cred_cred_style_nocodemirror_dev' => array(
            'loader_url' => CRED_FILE_URL,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('toolset-font-awesome', 'wp-jquery-ui-dialog', 'wp-pointer'),
            'path' => CRED_ASSETS_URL . '/css/cred.css',
            'src' => CRED_ASSETS_PATH . '/css/cred.css'
        )
    )
));

// init loader for this specific plugin and load assets if needed
CRED_Loader::init(CRED_FILE_PATH);

// if called when loading assets, ;)
if (!function_exists('add_action'))
    return; /* exit; */

if (defined('ABSPATH')) {
// register dependencies
    CRED_Loader::add('dependencies', array(
        'CONTROLLER' => array(
            '%%PARENT%%' => array(
                array(
                    'class' => 'CRED_Abstract_Controller',
                    'path' => CRED_CONTROLLERS_PATH . '/Abstract.php'
                )
            ),
            'Forms' => array(
                array(
                    'class' => 'CRED_Forms_Controller',
                    'path' => CRED_CONTROLLERS_PATH . '/Forms.php'
                )
            ),
            'Posts' => array(
                array(
                    'class' => 'CRED_Posts_Controller',
                    'path' => CRED_CONTROLLERS_PATH . '/Posts.php'
                )
            ),
            'Settings' => array(
                array(
                    'class' => 'CRED_Settings_Controller',
                    'path' => CRED_CONTROLLERS_PATH . '/Settings.php'
                )
            ),
            'Generic_Fields' => array(
                array(
                    'class' => 'CRED_Generic_Fields_Controller',
                    'path' => CRED_CONTROLLERS_PATH . '/Generic_Fields.php'
                )
            )
        ),
        'MODEL' => array(
            '%%PARENT%%' => array(
                array(
                    'class' => 'CRED_Abstract_Model',
                    'path' => CRED_MODELS_PATH . '/Abstract.php'
                )
            ),
            'Forms' => array(
                // dependencies
                array(
                    'path' => ABSPATH . '/wp-admin/includes/post.php'
                ),
                array(
                    'class' => 'CRED_Forms_Model',
                    'path' => CRED_MODELS_PATH . '/Forms.php'
                )
            ),
            'UserForms' => array(
                // dependencies
                array(
                    'path' => ABSPATH . '/wp-admin/includes/post.php'
                ),
                array(
                    'class' => 'CRED_User_Forms_Model',
                    'path' => CRED_MODELS_PATH . '/UserForms.php'
                )
            ),
            'Settings' => array(
                array(
                    'class' => 'CRED_Settings_Model',
                    'path' => CRED_MODELS_PATH . '/Settings.php'
                )
            ),
            'Fields' => array(
                array(
                    'class' => 'CRED_Fields_Model',
                    'path' => CRED_MODELS_PATH . '/Fields.php'
                )
            ),
            'UserFields' => array(
                array(
                    'class' => 'CRED_User_Fields_Model',
                    'path' => CRED_MODELS_PATH . '/UserFields.php'
                )
            )
        ),
        'TABLE' => array(
            '%%PARENT%%' => array(
                array(
                    'class' => 'WP_List_Table',
                    'path' => ABSPATH . '/wp-admin/includes/class-wp-list-table.php'
                )
            ),
            'EmbeddedForms' => array(
                array(
                    'class' => 'CRED_Forms_List_Table',
                    'path' => CRED_TABLES_PATH . '/EmbeddedForms.php'
                )
            ),
            'Forms' => array(
                array(
                    'class' => 'CRED_Forms_List_Table',
                    'path' => CRED_TABLES_PATH . '/Forms.php'
                )
            ),
            'UserForms' => array(
                array(
                    'class' => 'CRED_Forms_List_Table',
                    'path' => CRED_TABLES_PATH . '/UserForms.php'
                )
            ),
            'Custom_Fields' => array(
                array(
                    'class' => 'CRED_Custom_Fields_List_Table',
                    'path' => CRED_TABLES_PATH . '/Custom_Fields.php'
                )
            )
        ),
        'CLASS' => array(
            'CRED_Helper' => array(
                array(
                    'class' => 'CRED_Helper',
                    'path' => CRED_CLASSES_PATH . '/CRED_Helper.php'
                )
            ),
            'CRED' => array(
                array(
                    'class' => 'CRED_Admin',
                    'path' => CRED_ROOT_CLASSES_PATH . '/CRED_Admin.php'
                ),
                // make CRED Helper a depenency of CRED
                array(
                    'class' => 'CRED_Helper',
                    'path' => CRED_CLASSES_PATH . '/CRED_Helper.php'
                ),
                // make CRED Router a depenency of CRED
                array(
                    'class' => 'CRED_Router',
                    'path' => CRED_COMMON_PATH . '/Router.php'
                ),
                array(
                    'class' => 'CRED_CRED',
                    'path' => CRED_CLASSES_PATH . '/CRED.php'
                ),
                array(
                    'class' => 'CRED_PostExpiration',
                    'path' => CRED_CLASSES_PATH . '/CredPostExpiration.php'
                )
            ),
            'Form_Helper' => array(
                array(
                    'class' => 'CRED_Form_Builder_Helper',
                    'path' => CRED_CLASSES_PATH . '/Form_Builder_Helper.php'
                )
            ),
            'Form_Builder' => array(
                // make Form Helper a depenency of Form Builder
                array(
                    'class' => 'CRED_Form_Builder_Helper',
                    'path' => CRED_CLASSES_PATH . '/Form_Builder_Helper.php'
                ),
                array(
                    'class' => 'CRED_Form_Builder',
                    'path' => CRED_CLASSES_PATH . '/Form_Builder.php'
                )
            ),
            'Form_Translator' => array(
                array(
                    'class' => 'CRED_Form_Translator',
                    'path' => CRED_CLASSES_PATH . '/Form_Translator.php'
                )
            ),
            'XML_Processor' => array(
                array(
                    'class' => 'CRED_XML_Processor',
                    'path' => CRED_COMMON_PATH . '/XML_Processor.php'
                )
            ),
            'Mail_Handler' => array(
                array(
                    'class' => 'CRED_Mail_Handler',
                    'path' => CRED_COMMON_PATH . '/Mail_Handler.php'
                )
            ),
            'Notification_Manager' => array(
                array(
                    'class' => 'CRED_Notification_Manager',
                    'path' => CRED_CLASSES_PATH . '/Notification_Manager.php'
                )
            ),
            'Shortcode_Parser' => array(
                array(
                    'class' => 'CRED_Shortcode_Parser',
                    'path' => CRED_COMMON_PATH . '/Shortcode_Parser.php'
                )
            ),
            'Router' => array(
                array(
                    'class' => 'CRED_Router',
                    'path' => CRED_COMMON_PATH . '/Router.php'
                )
            )
        /* 'Settings_Manager' => array(
          array(
          'class' => 'CRED_Settings_Manager',
          'path' => CRED_COMMON_PATH.'/Settings_Manager.php'
          )
          ) */
        ),
        'VIEW' => array(
            'custom_fields' => array(
                array(
                    'path' => CRED_VIEWS_PATH . '/custom_fields.php'
                )
            ),
            'forms' => array(
                array(
                    'path' => CRED_VIEWS_PATH . '/forms.php'
                )
            ),
            'user_forms' => array(
                array(
                    'path' => CRED_VIEWS_PATH . '/user_forms.php'
                )
            ),
            'embedded-forms' => array(
                array(
                    'path' => CRED_VIEWS_PATH . '/embedded-forms.php'
                )
            ),
            'settings' => array(
                array(
                    'path' => CRED_VIEWS_PATH . '/settings.php'
                )
            ),
            'help' => array(
                array(
                    'path' => CRED_VIEWS_PATH . '/help.php'
                )
            )
        ),
        'TEMPLATE' => array(
            'insert-form-shortcode-button-extra' => array(
                'path' => CRED_TEMPLATES_PATH . '/insert-form-shortcode-button-extra.tpl.php'
            ),
            'insert-field-shortcode-button' => array(
                'path' => CRED_TEMPLATES_PATH . '/insert-field-shortcode-button.tpl.php'
            ),
            'insert-user-field-shortcode-button' => array(
                'path' => CRED_TEMPLATES_PATH . '/insert-user-field-shortcode-button.tpl.php'
            ),
            'insert-generic-field-shortcode-button' => array(
                'path' => CRED_TEMPLATES_PATH . '/insert-generic-field-shortcode-button.tpl.php'
            ),
            'scaffold-button' => array(
                'path' => CRED_TEMPLATES_PATH . '/scaffold-button.tpl.php'
            ),
            'user-scaffold-button' => array(
                'path' => CRED_TEMPLATES_PATH . '/user-scaffold-button.tpl.php'
            ),
            'insert-form-shortcode-button' => array(
                'path' => CRED_TEMPLATES_PATH . '/insert-form-shortcode-button.tpl.php'
            ),
            'form-settings-meta-box' => array(
                'path' => CRED_TEMPLATES_PATH . '/form-settings-meta-box.tpl.php'
            ),
            'user-form-settings-meta-box' => array(
                'path' => CRED_TEMPLATES_PATH . '/user-form-settings-meta-box.tpl.php'
            ),
            'post-type-meta-box' => array(
                'path' => CRED_TEMPLATES_PATH . '/post-type-meta-box.tpl.php'
            ),
            'notification-meta-box' => array(
                'path' => CRED_TEMPLATES_PATH . '/notification-meta-box.tpl.php'
            ),
            'notification-user-meta-box' => array(
                'path' => CRED_TEMPLATES_PATH . '/notification-user-meta-box.tpl.php'
            ),
            'extra-meta-box' => array(
                'path' => CRED_TEMPLATES_PATH . '/extra-meta-box.tpl.php'
            ),
            'text-settings-meta-box' => array(
                'path' => CRED_TEMPLATES_PATH . '/text-settings-meta-box.tpl.php'
            ),
            'delete-post-link' => array(
                'path' => CRED_TEMPLATES_PATH . '/delete-post-link.tpl.php'
            ),
            'generic-field-shortcode-setup' => array(
                'path' => CRED_TEMPLATES_PATH . '/generic-field-shortcode-setup.tpl.php'
            ),
            'conditional-shortcode-setup' => array(
                'path' => CRED_TEMPLATES_PATH . '/conditional-shortcode-setup.tpl.php'
            ),
            'custom-field-setup' => array(
                'path' => CRED_TEMPLATES_PATH . '/custom-field-setup.tpl.php'
            ),
            'notification-condition' => array(
                'path' => CRED_TEMPLATES_PATH . '/notification-condition.tpl.php'
            ),
            'notification-subject-codes' => array(
                'path' => CRED_TEMPLATES_PATH . '/notification-subject-codes.tpl.php'
            ),
            'notification-user-subject-codes' => array(
                'path' => CRED_TEMPLATES_PATH . '/notification-user-subject-codes.tpl.php'
            ),
            'notification-body-codes' => array(
                'path' => CRED_TEMPLATES_PATH . '/notification-body-codes.tpl.php'
            ),
            'notification-user-body-codes' => array(
                'path' => CRED_TEMPLATES_PATH . '/notification-user-body-codes.tpl.php'
            ),
            'notification' => array(
                'path' => CRED_TEMPLATES_PATH . '/notification.tpl.php'
            ),
            'notification-user' => array(
                'path' => CRED_TEMPLATES_PATH . '/notification-user.tpl.php'
            ),
            'pe_form_meta_box' => array(
                'path' => CRED_TEMPLATES_PATH . '/pe_form_meta_box.tpl.php'
            ),
            'pe_form_notification_option' => array(
                'path' => CRED_TEMPLATES_PATH . '/pe_form_notification_option.tpl.php'
            ),
            'pe_post_meta_box' => array(
                'path' => CRED_TEMPLATES_PATH . '/pe_post_meta_box.tpl.php'
            ),
            'pe_settings_meta_box' => array(
                'path' => CRED_TEMPLATES_PATH . '/pe_settings_meta_box.tpl.php'
            )
        )
    ));
}

require_once "embedded/common/functions.php";

//function cred_auto_load() {
//    // load basic classes
//    CRED_Loader::load('CLASS/CRED');
//    // init them
//    CRED_CRED::init();
//}
//
//add_action('cred_loader_auto_load', 'cred_auto_load', 1);
// load basic classes
CRED_Loader::load('CLASS/CRED');
// init them
CRED_CRED::init();
