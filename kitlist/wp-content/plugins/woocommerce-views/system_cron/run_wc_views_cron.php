<?php
/*Option to run WooCommerce Views Update for Custom Fields using System Cron*/
/*You need to schedule this script to your hosting cpanel Cron*/
/*For best results run as cURL that includes the cron_key from the WC Views admin e.g. (when configuring to cron)
 * 
 * curl http://wpsubversion.tld/wp-content/plugins/woocommerce-views-trunk/system_cron/run_wc_views_cron.php?cron_key=2bc08615cc1f
 *
 */
function get_wordpress_base_path_cron() {
	$dir = dirname(__FILE__);
	do {
		if( file_exists($dir."/wp-load.php") ) {
			return $dir;
		}
	} while( $dir = realpath("$dir/..") );
	return null;
}

$root_path = get_wordpress_base_path_cron();
	    
// Load WordPress
if(file_exists($root_path . '/wp-load.php')) {
require_once($root_path . '/wp-load.php');
}

//Get settings
//Retrieved current batch processing settings
$batch_processing_settings_sys=get_option('woocommerce_views_batch_processing_settings');
$settings_set_sys=$batch_processing_settings_sys['woocommerce_views_batchprocessing_settings'];
$secret_key_for_verification=get_option('wc_views_sys_cron_key');

//Get the secret key passed to the server
if (isset($_GET['cron_key'])) {
	$passed_secret_key=trim($_GET['cron_key']);
	
	//Run everything if secret key makes sense
	if ($passed_secret_key==$secret_key_for_verification) { 
	
		if ($settings_set_sys=='using_system_cron') {
		//Instantiate
			if(!isset($Class_WooCommerce_Views))
			{
			$Class_WooCommerce_Views = new Class_WooCommerce_Views;
			}
			if (class_exists('Class_WooCommerce_Views')) {
			//Call update method
			$Class_WooCommerce_Views->ajax_process_wc_views_batchprocessing();
			}
		}
	
	}
}
?>