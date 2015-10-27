<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of StaticToGlobal
 *
 * @author Franko
 */
class StaticClass {

    public static $_cred_container_id;
    public static $_____friendsStatic_____ = array(/* Friend Class Hashes as keys Here.. */);

    const METHOD = 'POST';                                         // form method POST
    const PREFIX = '_cred_cred_prefix_';
    // prefix for various hidden auxiliary fields
    const NONCE = '_cred_cred_wpnonce';                            // nonce field name
    const POST_CONTENT_TAG = '%__CRED__CRED__POST__CONTENT__%';    // placeholder for post content
    const FORM_TAG = '%__CRED__CRED__FORM___FORM__%';              // 
    const DELAY = 0;

    //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196177636/comments#309966145
    public static $_allowed_mime_types;
    public static $_mail_error = "";
    // STATIC Properties
    public static $_staticGlobal = array(
        'ASSETS_PATH' => null, // physical path to files needed for Zebra form
        'ASSETS_URL' => null, // url for this physical path
        'MIMES' => array(), // WP allowed mime types (for file uploads)
        'LOCALES' => null, // global strings localization
        'RECAPTCHA' => false, // settings for recaptcha API
        'RECAPTCHA_LOADED' => false, // flag indicating whether recaptcha API has been loaded
        'COUNT' => 0, // number of forms rendered on same page
        'CACHE' => array(), // cache rendered forms here for future reference (eg by shortcodes)
        'CSS_LOADED' => array(), // references to CSS files that have been loaded
        'CURRENT_USER' => null                                    // info about current user using the forms
    );
    public static $_username_generated = null;
    public static $_password_generated = null;
    public static $_nickname_generated = null;

    public static function my_cred_exclude($post_types) {
        $post_types[] = CRED_FORMS_CUSTOM_POST_NAME;
        $post_types[] = CRED_USER_FORMS_CUSTOM_POST_NAME;
        return $post_types;
    }

    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function generateUsername($email) {
        $username = sanitize_user(current(explode('@', $email)), true);

        // Ensure username is unique
        $append = 1;
        $o_username = $username;

        while (username_exists($username)) {
            $username = $o_username . $append;
            $append ++;
        }
        return $username;
    }

    public static function cf_sanitize_values_on_save($value) {
        if (current_user_can('unfiltered_html')) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $val = self::cf_sanitize_values_on_save($val);
                }
            } else {
                $value = wp_filter_post_kses($value);
            }
        } else {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $val = self::cf_sanitize_values_on_save($val);
                }
            } else {
                $value = wp_filter_kses($value);
            }
        }
        return $value;
    }

    //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196177636/comments#309966145
    public static function cred__add_custom_mime_types($mimes) {
        return StaticClass::$_allowed_mime_types = array_merge($mimes, StaticClass::$_allowed_mime_types);
    }

    public static function _pre($v) {
        echo "<pre>";
        print_r($v);
        echo "</pre>";
    }

    public static function parseFriendCallStatic($the) {
        $what = explode('_1_1_1_', $the);
        if (isset($what[0]) && isset($what[1])) {
            $hash = $what[0];
            $whatExactly = $what[1];
            $ref = false;
            if ($whatExactly && '&' == $whatExactly[0]) {
                $ref = true;
                $whatExactly = substr($whatExactly, 1);
            }
            return array($hash, $whatExactly, $ref);
        }
        return array(false, false, false);
    }

    public static function __getPrivStatic($prop) {
        return self::$$prop;
    }

    public static function &__getPrivStaticRef($prop) {
        return self::$$prop;
    }

}

?>
