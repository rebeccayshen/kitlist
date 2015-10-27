<?php
/*
Plugin Name: CRED Commerce
Plugin URI: http://wp-types.com/home/cred-commerce/
Description: Integrate 3rd-party E-Commerce payments to CRED Frontend Editor plugin
Version: 0.9.6
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
*/

// current version
define('CRED_COMMERCE_VERSION','0.9.6');
define('CRED_COMMERCE_NAME','CRED_COMMERCE');
define('CRED_COMMERCE_CAPABILITY','manage_options');
if ( function_exists('realpath') )
    define('CRED_COMMERCE_PLUGIN_PATH', realpath(dirname(__FILE__)));
else
    define('CRED_COMMERCE_PLUGIN_PATH', dirname(__FILE__));
define('CRED_COMMERCE_PLUGIN_FOLDER', basename(CRED_COMMERCE_PLUGIN_PATH));
define('CRED_COMMERCE_PLUGIN_URL',plugins_url().'/'.CRED_COMMERCE_PLUGIN_FOLDER);
define('CRED_COMMERCE_ASSETS_URL',CRED_COMMERCE_PLUGIN_URL.'/assets');
define('CRED_COMMERCE_ASSETS_PATH',CRED_COMMERCE_PLUGIN_PATH.'/assets');
define('CRED_COMMERCE_LOCALE_PATH',CRED_COMMERCE_PLUGIN_FOLDER.'/locale');
define('CRED_COMMERCE_VIEWS_PATH',CRED_COMMERCE_PLUGIN_PATH.'/views');
define('CRED_COMMERCE_VIEWS_PATH2',CRED_COMMERCE_PLUGIN_FOLDER.'/views');
define('CRED_COMMERCE_TEMPLATES_PATH',CRED_COMMERCE_PLUGIN_PATH.'/views/templates');
define('CRED_COMMERCE_TABLES_PATH',CRED_COMMERCE_PLUGIN_PATH.'/views/tables');
define('CRED_COMMERCE_CLASSES_PATH',CRED_COMMERCE_PLUGIN_PATH.'/classes');
define('CRED_COMMERCE_CONTROLLERS_PATH',CRED_COMMERCE_PLUGIN_PATH.'/controllers');
define('CRED_COMMERCE_MODELS_PATH',CRED_COMMERCE_PLUGIN_PATH.'/models');
define('CRED_COMMERCE_LOGS_PATH',CRED_COMMERCE_PLUGIN_PATH.'/logs');
define('CRED_COMMERCE_PLUGINS_PATH',CRED_COMMERCE_PLUGIN_PATH.'/plugins');

// define plugin name (path)
define('CRED_COMMERCE_PLUGIN_NAME',CRED_COMMERCE_PLUGIN_FOLDER.'/'.basename(__FILE__));
define('CRED_COMMERCE_PLUGIN_BASENAME',plugin_basename( __FILE__ ));

// load on the go resources
require_once CRED_COMMERCE_PLUGIN_PATH. '/onthego-resources/loader.php';
onthego_initialize(CRED_COMMERCE_PLUGIN_PATH . '/onthego-resources', CRED_COMMERCE_PLUGIN_URL . '/onthego-resources/' );


//define('CRED_COMMERCE_DEBUG',true);
//define('CRED_COMMERCE_DEV',true);

function cred_commerce_activated()
{
  add_option('cred_commerce_activated', '1');
}
register_activation_hook( __FILE__, 'cred_commerce_activated');


include (CRED_COMMERCE_PLUGIN_PATH.'/loader.php');
CREDC_Loader::load('CLASS/CRED_Commerce');
CRED_Commerce::init();
