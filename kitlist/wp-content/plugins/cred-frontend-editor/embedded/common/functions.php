<?php

if (!function_exists('is_cred_embedded')) {
    function is_cred_embedded() {
        return CRED_CRED::is_embedded();
    }
}

if (function_exists('add_action')) {
    add_action('init', 'cred_common_path');
    function cred_common_path() {
        if (!defined('ICL_COMMON_FUNCTIONS')) {
            require_once CRED_PLUGIN_PATH . '/toolset/toolset-common/functions.php';
        }
        if (!defined('WPTOOLSET_FORMS_VERSION')) {
            require_once WPTOOLSET_COMMON_PATH . '/toolset-forms/bootstrap.php';
        }
    }

}

if (!function_exists('cred_log')) {
    function cred_log($message, $file = null, $type = null, $level = 1) {
        // debug levels
        $dlevels = array(
            'default' => true, //defined('CRED_DEBUG') && CRED_DEBUG,
            'access' => false, //defined('CRED_DEBUG_ACCESS') && CRED_DEBUG_ACCESS
        );

        // check if we need to log..
        if (!$dlevels['default'])
            return false;
        if ($type == null)
            $type = 'default';
        if (!isset($dlevels[$type]) || !$dlevels[$type])
            return false;

        // full path to log file
        if ($file == null) {
            $file = 'debug.log';
        }

        if ('access.log' == $file && !$dlevels['access'])
            return;

        $file = CRED_LOGS_PATH . DIRECTORY_SEPARATOR . $file;

        /* backtrace */
        $bTrace = debug_backtrace(); // assoc array

        /* Build the string containing the complete log line. */
        $line = PHP_EOL . sprintf('[%s, <%s>, (%d)]==> %s', date("Y/m/d h:i:s" /* ,time() */), basename($bTrace[0]['file']), $bTrace[0]['line'], print_r($message, true));

        if ($level > 1) {
            $i = 0;
            $line.=PHP_EOL . sprintf('Call Stack : ');
            while (++$i < $level && isset($bTrace[$i])) {
                $line.=PHP_EOL . sprintf("\tfile: %s, function: %s, line: %d" . PHP_EOL . "\targs : %s", isset($bTrace[$i]['file']) ? basename($bTrace[$i]['file']) : '(same as previous)', isset($bTrace[$i]['function']) ? $bTrace[$i]['function'] : '(anonymous)', isset($bTrace[$i]['line']) ? $bTrace[$i]['line'] : 'UNKNOWN', print_r($bTrace[$i]['args'], true));
            }
            $line.=PHP_EOL . sprintf('End Call Stack') . PHP_EOL;
        }
        // log to file
        file_put_contents($file, $line, FILE_APPEND);

        return true;
    }
}

// CRED PHP Tags, to be used inside Theme templates
function cred_delete_post_link($post_id = false, $text = '', $action = '', $class = '', $style = '', $return = false, $message = '', $message_after = '', $message_show = 1) {
    $output = CRED_Helper::cred_delete_post_link($post_id, $text, $action, $class, $style, $message, $message_after, $message_show);
    if ($return)
        return $output;
    echo $output;
}

function cred_edit_post_link($form, $post_id = false, $text = '', $class = '', $style = '', $target = '', $attributes = '', $return = false) {
    $output = CRED_Helper::cred_edit_post_link($form, $post_id, $text, $class, $style, $target, $attributes);
    if ($return)
        return $output;
    echo $output;
}

function cred_form($form, $post_id = false, $return = false) {
    $output = CRED_Helper::cred_form($form, $post_id);
    if ($return)
        return $output;
    echo $output;
}

// function to be used in templates (eg for hiding comments)
function has_cred_form() {
    if (!class_exists('CRED_Form_Builder', false))
        return false;
    return CRED_Form_Builder::has_form();
}

/**
 * public API to import from XML string
 *
 * @param string $xml
 * @param array $options
 *     'overwrite_forms'=>(0|1)             // Overwrite existing forms
 *     'overwrite_settings'=>(0|1)          // Import and Overwrite CRED Settings
 *     'overwrite_custom_fields'=>(0|1)     // Import and Overwrite CRED Custom Fields
 *     'force_overwrite_post_name'=>array   // Skip all, overwrite only forms from array
 *     'force_skip_post_name'=>array        // Skip forms from array
 *     'force_duplicate_post_name'=>array   // Skip all, duplicate only from array
 * @return array
 *     'settings'=>(int),
 *     'custom_fields'=>(int),
 *     'updated'=>(int),
 *     'new'=>(int),
 *     'failed'=>(int),
 *     'errors'=>array()
 *
 * example:
 *   $result = cred_import_xml_from_string($import_xml_string, array('overwrite_forms'=>1, 'overwrite_settings'=>0, 'overwrite_custom_fields'=>1));
 * note:
 * force_duplicate_post_name, force_skip_post_name, force_overwrite_post_name - can work together
 */
function cred_import_xml_from_string($xml, $options = array()) {
    CRED_Loader::load('CLASS/XML_Processor');
    $result = CRED_XML_Processor::importFromXMLString($xml, $options);
    return $result;
}

function cred_user_import_xml_from_string($xml, $options = array()) {
    CRED_Loader::load('CLASS/XML_Processor');
    $result = CRED_XML_Processor::importUsersFromXMLString($xml, $options);
    return $result;
}

/*
  public API to export to XML string
 */

function cred_export_to_xml_string($forms) {
    CRED_Loader::load('CLASS/XML_Processor');
    $xmlstring = CRED_XML_Processor::exportToXMLString($forms);
    return $xmlstring;
}

// auxilliary global functions
/**
 * WPML translate call.
 *
 * @param type $name
 * @param type $string
 * @return type
 */
function cred_translate($name, $string, $context = 'CRED_CRED') {
    if (!function_exists('icl_t'))
        return $string;

    return icl_t($context, $name, stripslashes($string));
}

/**
 * Registers WPML translation string.
 *
 * @param type $context
 * @param type $name
 * @param type $value
 */
function cred_translate_register_string($context, $name, $value, $allow_empty_value = false) {
    if (function_exists('icl_register_string')) {
        icl_register_string($context, $name, stripslashes($value), $allow_empty_value);
    }
}

// stub wpml=string shortcode
if (!function_exists('cred_stub_wpml_string_shortcode')) {

    function cred_stub_wpml_string_shortcode($atts, $content = '') {
        // return un-processed.
        return do_shortcode($content);
    }

}

/**
 * Filter the_content tag 
 * Added support for resolving third party shortcodes in cred shortcodes
 */
function cred_do_shortcode($content) {
    $shortcodeParser = CRED_Loader::get('CLASS/Shortcode_Parser');
    $content = $shortcodeParser->parse_content_shortcodes($content);

    return $content;
}

function cred_disable_shortcodes() {
    global $shortcode_tags;

    $shortcode_back = $shortcode_tags;
    $shortcode_tags = array();
    return($shortcode_back);
}

function cred_re_enable_shortcodes($shortcode_back) {
    global $shortcode_tags;

    $shortcode_tags = $shortcode_back;
}

function cred_disable_filters_for($hook) {
    global $wp_filter;
    if (isset($wp_filter[$hook])) {
        $wp_filter_back = $wp_filter[$hook];
        $wp_filter[$hook] = array();
    } else
        $wp_filter_back = array();
    return($wp_filter_back);
}

function cred_re_enable_filters_for($hook, $back) {
    global $wp_filter;
    $wp_filter[$hook] = $back;
}
