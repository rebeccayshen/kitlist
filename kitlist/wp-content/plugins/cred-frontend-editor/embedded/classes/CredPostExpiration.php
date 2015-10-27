<?php
define('CRED_PE_SCRIPT_URL', CRED_ASSETS_URL . '/js/');
define('CRED_PE_IMAGE_URL', CRED_ASSETS_URL . '/images/');


if (!function_exists('adodb_mktime')) {
    require_once CRED_PLUGIN_PATH . '/toolset/toolset-common/toolset-forms/lib/adodb-time.inc.php';
}

class CRED_PostExpiration {

    private $_post_expiration_enabled = false;
    private $_credmodel = NULL;
    private $_prefix = '_cred_';
    private $_form_settings = 'form_settings';
    private $_form_post_expiration = 'post_expiration';
    private $_form_notifications = 'notification';
    private $_settings_defaults = array(
        'enable' => 0,
        'action' => array(
            'post_status' => '',
            'custom_actions' => array()
        ),
        'expiration_time' => array(
            'weeks' => 0,
            'days' => 0
        )
    );
    private $_action_post_status = array(
        'original' => 'Keep original status',
        'draft' => 'Draft',
        'pending' => 'Pending Review',
        'private' => 'Private',
        'publish' => 'Published',
        'trash' => 'Trash',
    );
    private $_extra_notification_codes = array(
        '%%EXPIRATION_DATE%%' => 'Expiration Date',
    );
    private $_post_expiration_slug = 'cred-post-expiration';
    private $_post_expiration_time_field = '_cred_post_expiration_time';
    private $_post_expiration_action_field = '_cred_post_expiration_action';
    private $_post_expiration_notifications_field = '_cred_post_expiration_notifications';
    private $_cron_settings_option = 'cred_post_expiration_settings';
    private $_cron_schedule_event_name = 'cred_post_expiration_event';
    private $_post_expiration_custom_actions_filter_name = 'cred_post_expiration_custom_actions';
    private $_localization_context = 'wp-cred';
    private $_date_format = 'F jS, Y';
    private $_shortcodes = array();
    protected static $_supported_date_formats = array(
        'F j, Y', //December 23, 2011
        'Y/m/d', // 2011/12/23
        'm/d/Y', // 12/23/2011
        'd/m/Y' // 23/22/2011
    );
    protected static $_mintimestamp = -12219292800, $_maxtimestamp = 32535215940;

    /**
     * Hook into the appropriate actions when the class is constructed.
     */
    function __construct() {
        global $pagenow;
        // exit if cred not active or installed
        if (!defined('CRED_FE_VERSION')) {
            if (is_admin()) {
                if ('plugins.php' == $pagenow) {
                    // add a notice
                    add_action('admin_notices', array($this, 'cred_pe_display_notice'), 3);
                }
            }
            return;
        }

        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'cred_pe_scripts'));
        add_action('cred_pe_general_settings', array($this, 'cred_pe_general_settings'));
        add_action('cred_ext_metabox_settings', array($this, 'addCredPostExpireMetaboxSettings'));
        add_action('cred_admin_notification_notify_event_options_before', array($this, 'cred_pe_add_notification_option'), 10, 3);
        add_action('cred_settings_action', array($this, 'cred_settings_action'), 10, 2);
        add_action('cred_admin_save_form', array($this, 'cred_admin_save_form'), 10, 2);
        add_action('cred_save_data', array($this, 'cred_save_data'), 10, 2);
        add_action($this->_cron_schedule_event_name, array($this, 'cred_pe_schedule_event_action'), 11);
        add_action($this->_cron_schedule_event_name, array($this, 'cred_pe_schedule_event_notifications'), 10);
        add_action('add_meta_boxes', array($this, 'cred_pe_add_post_meta_box'));
        add_action('save_post', array($this, 'cred_pe_post_save'));
        add_filter('cred_ext_general_settings_options', array($this, 'cred_pe_general_settings_options'));        
        add_filter('cred_ext_meta_boxes', array($this, 'cred_pe_meta_boxes'), 10, 2);
        add_filter('cred_admin_notification_subject_codes', array($this, 'addExtraNotificationCodes'), 10, 4);
        add_filter('cred_admin_notification_body_codes', array($this, 'addExtraNotificationCodes'), 10, 4);
        //add specific placeholders to CRED notification mail subject and body
        add_filter('cred_subject_notification_codes', array($this, 'extraSubjectNotificationCodes'), 10, 3);
        add_filter('cred_body_notification_codes', array($this, 'extraBodyNotificationCodes'), 10, 3);

        add_action('wp_ajax_cred_post_expiration_date', array($this, 'cred_post_expiration_date'));
        add_action('wp_ajax_nopriv_cred_post_expiration_date', array($this, 'cred_post_expiration_date'));

        add_filter('cron_schedules', array($this, 'cred_pe_add_cron_schedules'), 10, 1);
        // shortcodes
        $this->_shortcodes['cred-post-expiration'] = array($this, 'cred_pe_shortcode_cred_post_expiration');
        add_filter('wpv_custom_inner_shortcodes', array($this, 'cred_pe_shortcodes'));
        foreach ($this->_shortcodes as $tag => $function) {
            add_shortcode($tag, $function);
        }
    }

    function __destruct() {
        
    }

    function init() {
        /* CRED must be activated */
        if (!class_exists('CRED_Loader', false))
            return;

        // init same internal vars for localization
        foreach ($this->_action_post_status as $value => $text) {
            $this->_action_post_status[$value] = __($text, $this->_localization_context);
        }
        foreach ($this->_extra_notification_codes as $code => $text) {
            $this->_extra_notification_codes[$code] = __($text, $this->_localization_context);
        }

        /* get the settings option for auto expire date feature */
        $settings_model = CRED_Loader::get('MODEL/Settings');
        $settings = $settings_model->getSettings();
        $this->_post_expiration_enabled = (isset($settings['enable_post_expiration']) ? $settings['enable_post_expiration'] : false);
        $this->cred_pe_setup_schedule();
        /* get the CRED Form model */
        $this->_credmodel = CRED_Loader::get('MODEL/Forms');
        /* register our script. */
        wp_register_script('script-cred-post-expiration', CRED_PE_SCRIPT_URL . 'cred_post_expiration.js', array('jquery-ui-datepicker'), '1.0.0', true);
    }

    public function cred_pe_display_notice() {
        ?>
        <div class="error">
            <p><?php _e('CRED Post Expiration plugin needs the <a href="http://wp-types.com/home/cred/" target="_blank"><strong>CRED Frontend Editor</strong></a> plugin to be installed and activated.', $this->_localization_context); ?></p>
        </div>
        <?php
    }

    public function getLocalizationContext() {
        return $this->_localization_context;
    }

    public function getActionPostStatus() {
        return $this->_action_post_status;
    }

    /**
     * enqueue scripts and styles
     */
    function cred_pe_scripts() {
        if ($this->_post_expiration_enabled) {
            $screen_ids = array();
            $settings = $this->getCredPESettings();
            if (isset($settings['post_expiration_post_types'])) {
                $screen_ids = $settings['post_expiration_post_types'];
            }
            $screen_ids[] = 'cred-form';
            $screen = get_current_screen();
            if (isset($screen->id) && in_array($screen->id, $screen_ids)) {
                //wp_enqueue_style('style-name', get_stylesheet_uri());
                wp_enqueue_script('script-cred-post-expiration');
                $calendar_image = CRED_PE_IMAGE_URL . 'calendar.gif';
                $calendar_image = apply_filters('wptoolset_filter_wptoolset_calendar_image', $calendar_image);
                $date_format = self::getDateFormat();
                $js_data = array(
                    'buttonImage' => $calendar_image,
                    'buttonText' => __('Select date', $this->_localization_context),
                    'dateFormatPhp' => $date_format,
                    'yearMin' => intval(self::timetodate(self::$_mintimestamp, 'Y')) + 1,
                    'yearMax' => self::timetodate(self::$_maxtimestamp, 'Y'),
                    'ajaxurl' => admin_url('admin-ajax.php', null)
                );
                wp_localize_script('script-cred-post-expiration', 'CREDExpirationScript', $js_data);
            }
            // Enqueue Datepicker jQuery UI CSS
            wp_enqueue_style('jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/smoothness/jquery-ui.css', true);
        }
    }

    public static function timetodate($timestamp, $format = null) {
        if (is_null($format)) {
            $format = self::getDateFormat();
        }
        return self::_isTimestampInRange($timestamp) ? @adodb_date($format, $timestamp) : false;
    }

    public function cred_post_expiration_date() {
        $date_format = $_POST['date-format'];
        if ($date_format == '') {
            $date_format = get_option('date_format');
        }
        $date = $_POST['date'];
        $date = adodb_mktime(0, 0, 0, substr($date, 2, 2), substr($date, 0, 2), substr($date, 4, 4));
        $date_format = str_replace('\\\\', '\\', $date_format);
        echo json_encode(array('display' => adodb_date($date_format, $date), 'timestamp' => $date));
        die();
    }

    /**
     * perform CRED Settings save actions
     */
    function cred_settings_action($doaction, $settings) {
        switch ($doaction) {
            case 'edit':
                if (empty($settings['enable_post_expiration'])) {
                    $this->deleteCredPESettings();
                } else {
                    $settings = $this->getCredPESettings();
                    if (!isset($settings['post_expiration_cron']['schedule'])) {
                        $schedules = wp_get_schedules();
                        foreach ($schedules as $schedule => $schedule_definition) {
                            $settings['post_expiration_cron']['schedule'] = $schedule;
                            $this->setCronSettings($settings);
                            break;
                        }
                    }
                }
                break;
            case 'cron':
                if ($this->_post_expiration_enabled) {
                    $settings = isset($_POST['settings']) ? (array) $_POST['settings'] : array();
                    $settings = self::array_merge_distinct($this->getCredPESettings(), $settings);
                    $this->setCronSettings($settings);
                } else {
                    $this->deleteCredPESettings();
                }
                break;
        }
    }

    /**
     * perform extra CRED Form save actions
     */
    function cred_admin_save_form($post_id, $post) {
        if ($this->_post_expiration_enabled) {
            $form_expiration_settings = $this->_settings_defaults;
            if (isset($_POST['_cred_post_expiration'])) {
                $form_expiration_settings = self::array_merge_distinct($this->_settings_defaults, $_POST['_cred_post_expiration']);
            }
            $this->updateForm($post_id, $form_expiration_settings);

            if ($form_expiration_settings['enable']) {
                $settings = $this->getCredPESettings();
                if (!isset($settings['post_expiration_post_types'])) {
                    $settings['post_expiration_post_types'] = array();
                }
                $form_settings = $this->getFormMeta($post_id, $this->_form_settings);
                if (isset($form_settings->post['post_type'])) {
                    $settings['post_expiration_post_types'][] = $form_settings->post['post_type'];
                    $settings['post_expiration_post_types'] = array_unique($settings['post_expiration_post_types']);
                    $this->setCredPESettings($settings);
                }
            }
        } else {
            
        }
    }

    /**
     * perform post expire actions when saving post
     */
    function cred_save_data($post_id, $form_data) {
        if ($this->_post_expiration_enabled) {
            $settings = $this->getFormMeta($form_data['id']);
            // only set expire time if post expire is enabled
            if ($settings['enable']) {
                // expire time default is 0, that means no expiration
                $expire_time = 0;
                $days_value = 0;
                $weeks_value = 0;
                //Convert to integer value to make sure $expire_time is set correctly
                if (isset($settings['expiration_time']['days'])) {
                    $days_value = intval($settings['expiration_time']['days']);
                }
                if (isset($settings['expiration_time']['weeks'])) {
                    $weeks_value = intval($settings['expiration_time']['weeks']);
                }
                if ($weeks_value > 0 || $days_value > 0) {
                    // calculate expiration time and get the corresponding timestamp
                    $expire_time = strtotime('+' . $weeks_value . ' weeks +' . $days_value . ' days');
                }
                update_post_meta($post_id, $this->_post_expiration_time_field, $expire_time);
                // actions on expiration
                $settings['action']['custom_actions'] = isset($settings['action']['custom_actions']) ? $settings['action']['custom_actions'] : array();
                $form = get_post($form_data['id']);
                $form_slug = isset($form->post_name) ? $form->post_name : '';
                $settings['action']['custom_actions'] = apply_filters($this->_post_expiration_custom_actions_filter_name . '_' . $form_slug, $settings['action']['custom_actions'], $post_id, $form_data);
                $settings['action']['custom_actions'] = apply_filters($this->_post_expiration_custom_actions_filter_name . '_' . $form_data['id'], $settings['action']['custom_actions'], $post_id, $form_data);
                $settings['action']['custom_actions'] = apply_filters($this->_post_expiration_custom_actions_filter_name, $settings['action']['custom_actions'], $post_id, $form_data);
                if (!is_array($settings['action']['custom_actions'])) {
                    $settings['action']['custom_actions'] = array();
                }
                update_post_meta($post_id, $this->_post_expiration_action_field, $settings['action']);
                // check for notifications
                $notifications = $this->getFormMeta($form_data['id'], $this->_form_notifications);
                if (isset($notifications->notifications)) {
                    // get only 'expiration_date' notifications
                    $aux_array = array();
                    foreach ($notifications->notifications as $notification) {
                        if ('expiration_date' == $notification['event']['type']) {
                            $notification['form_id'] = $form_data['id'];
                            $aux_array[] = $notification;
                        }
                    }
                    $notifications = $aux_array;
                    update_post_meta($post_id, $this->_post_expiration_notifications_field, $notifications);
                }
            }
        }
    }

    /**
     * insert Enable Automatic Expiration Date CRED settings option
     */
    function cred_pe_general_settings($settings) {
        $this->_post_expiration_enabled = (isset($settings['enable_post_expiration']) ? $settings['enable_post_expiration'] : false);
        ?>
        <tr>
            <td colspan=2>
                <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[enable_post_expiration]" value="1" <?php if (isset($settings['enable_post_expiration']) && $settings['enable_post_expiration']) echo "checked='checked'"; ?> /><span class='cred-checkbox-replace'></span>
                    <span><?php _e('Enable Automatic Expiration of Post options for CRED Post Forms', $this->_localization_context); ?></span></label>
            </td>
        </tr>
        <?php
    }

    /**
     * set Enable Automatic Expire Date CRED settings option default
     */
    function cred_pe_general_settings_options($defaults) {
        $defaults['enable_post_expiration'] = 0;
        return $defaults;
    }

    /**
     * render Post Expiration Metabox Settings
     */
    function addCredPostExpireMetaboxSettings($settings) {
        if ($this->_post_expiration_enabled) {
            $settings = $this->getCredPESettings();
            echo CRED_Loader::tpl('pe_settings_meta_box', array(
                'cred_post_expiration' => $this,
                'settings' => $settings
            ));
        }
    }

    /**
     * define Enable Automatic Expiration Date CRED Form meta box
     */
    function cred_pe_meta_boxes($metaboxes, $form_fields) {
        global $post; 
        if ($post && $post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) return $metaboxes;
        if ($this->_post_expiration_enabled) {
            $aux_metaboxes = array();
            foreach ($metaboxes as $mt => $mt_definition) {
                if ('crednotificationdiv' == $mt) {
                    $aux_metaboxes['credpostexpirationdiv'] = array(
                        'title' => __('Automatic Expiration Date', $this->_localization_context),
                        'callback' => array($this, 'addCredPostExpireMetaBox'),
                        'post_type' => NULL,
                        'context' => 'normal',
                        'priority' => 'high',
                        'callback_args' => $form_fields
                    );
                }
                $aux_metaboxes[$mt] = $mt_definition;
            }
            $metaboxes = $aux_metaboxes;
        }
        return $metaboxes;
    }

    /**
     * render Enable Automatic Expiration Date CRED Form meta box
     */
    function addCredPostExpireMetaBox($form, $args) {
        $settings = $this->getFormMeta($form->ID);
        $settings_defaults = $this->_settings_defaults;
        echo CRED_Loader::tpl('pe_form_meta_box', array(
            'cred_post_expiration' => $this,
            'settings' => $settings,
            'settings_defaults' => $settings_defaults,
            'field_name' => $this->_prefix . $this->_form_post_expiration
        ));
    }

    /**
     * get CRED PE Settings
     */
    public function getCredPESettings() {
        return get_option($this->_cron_settings_option, array());
    }

    /**
     * set CRED PE Settings
     */
    public function setCredPESettings($settings) {
        return update_option($this->_cron_settings_option, $settings);
    }

    /**
     * get CRED PE Settings
     */
    public function deleteCredPESettings() {
        delete_option($this->_cron_settings_option);
    }

    /**
     * set CRED Settings cron
     */
    public function setCronSettings($settings) {
        $result = $this->setCredPESettings($settings);
        $this->cred_pe_setup_schedule();
        return $result;
    }

    /**
     * get CRED Form post_meta
     */
    protected function getFormMeta($form_id, $meta = '') {
        if ($this->_credmodel) {
            if (empty($meta)) {
                $meta = $this->_form_post_expiration;
            }
            return $this->_credmodel->getPostMeta($form_id, $this->_prefix . $meta);
        }
    }

    /**
     * set CRED Form post_meta
     */
    protected function updateForm($form_id, $data, $meta = '') {
        if ($this->_credmodel) {
            if (empty($meta)) {
                $meta = $this->_form_post_expiration;
            }
            $this->_credmodel->updateFormCustomField($form_id, $meta, $data);
        }
    }

    /**
     * add custom schedules of cron in wordpress
     */
    function cred_pe_add_cron_schedules($schedules) {
        /* default array for schedules of cron in Wordpress
          $schedules = array(
          'hourly'     => array( 'interval' => HOUR_IN_SECONDS,      'display' => __( 'Once Hourly' ) ),
          'twicedaily' => array( 'interval' => 12 * HOUR_IN_SECONDS, 'display' => __( 'Twice Daily' ) ),
          'daily'      => array( 'interval' => DAY_IN_SECONDS,       'display' => __( 'Once Daily' ) ),
          );
          $schedules['fifteenmin'] = array( 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => __( 'Every 15 minutes' ) );
         */
        $schedules = apply_filters('cred_post_expiration_cron_schedules', $schedules);
        return $schedules;
    }

    /**
     * enable/disable the schedule task for CRED post expiration
     */
    function cred_pe_setup_schedule() {
        if ($this->_post_expiration_enabled) {
            $schedule = wp_get_schedule($this->_cron_schedule_event_name);
            $settings = $this->getCredPESettings();
            if (isset($settings['post_expiration_cron']['schedule'])) {
                if ($schedule != $settings['post_expiration_cron']['schedule']) {
                    wp_clear_scheduled_hook($this->_cron_schedule_event_name);
                    wp_schedule_event(time(), $settings['post_expiration_cron']['schedule'], $this->_cron_schedule_event_name);
                }
            } else {
                wp_clear_scheduled_hook($this->_cron_schedule_event_name);
            }
        } else {
            wp_clear_scheduled_hook($this->_cron_schedule_event_name);
        }
    }

    /**
     * action for the CRED post expiration scheduled task
     */
    function cred_pe_schedule_event_action() {
        if ($this->_post_expiration_enabled) {
            global $wpdb;
            /*
              $posts_expired = $wpdb->get_results( $wpdb->prepare(
              "
              SELECT m1.post_id, m2.meta_value AS action
              FROM $wpdb->postmeta m1 INNER JOIN $wpdb->postmeta m2
              ON m1.post_id = m2.post_id AND m1.meta_key = %s AND m2.meta_key = %s
              WHERE m1.meta_value > 0 AND m1.meta_value < %d
              ",
              $this->_post_expiration_time_field,
              $this->_post_expiration_action_field,
              time()
              ) );
             */
            $posts_expired = $wpdb->get_results($wpdb->prepare(
                            "
					SELECT m1.post_id, m2.meta_value AS action
					FROM $wpdb->postmeta m1 INNER JOIN $wpdb->postmeta m2
						ON m1.post_id = m2.post_id AND m1.meta_key = %s AND m2.meta_key = %s
					WHERE m1.meta_value < %d
				", $this->_post_expiration_time_field, $this->_post_expiration_action_field, time()
            ));
            $posts_expired_ids = array();
            foreach ($posts_expired as $post_meta) {
                $posts_expired_ids[] = $post_meta->post_id;
                $post_meta->action = maybe_unserialize($post_meta->action);
                if (isset($post_meta->action['post_status']) && 'original' != $post_meta->action['post_status']) {
                    if ('trash' == $post_meta->action['post_status']) {
                        wp_trash_post($post_meta->post_id);
                    } else {
                        $update_post = get_post($post_meta->post_id, ARRAY_A);
                        if (isset($update_post['ID'])) {
                            $update_post['post_status'] = $post_meta->action['post_status'];
                            wp_insert_post($update_post);
                        }
                    }
                }
                // run custom actions
                foreach ($post_meta->action['custom_actions'] as $action) {
                    if (!empty($action['meta_key'])) {
                        update_post_meta($post_meta->post_id, $action['meta_key'], isset($action['meta_value']) ? $action['meta_value'] : '');
                    }
                }
            }
            if (!empty($posts_expired_ids)) {
                $posts_expired_ids = implode(',', $posts_expired_ids);
                $wpdb->query($wpdb->prepare(
                                "
						UPDATE $wpdb->postmeta
						SET meta_value = 0
						WHERE post_id IN ({$posts_expired_ids}) AND meta_key = %s
					", $this->_post_expiration_time_field
                ));
            }
        }
    }

    /**
     * notifications for the CRED post expiration scheduled task
     */
    function cred_pe_schedule_event_notifications() {
        if ($this->_post_expiration_enabled) {
            global $wpdb;
            /*
              $posts_for_notifications = $wpdb->get_results( $wpdb->prepare(
              "
              SELECT m1.post_id, m1.meta_value AS expiration_time, m2.meta_value AS notifications
              FROM $wpdb->postmeta m1 INNER JOIN $wpdb->postmeta m2
              ON m1.post_id = m2.post_id AND m1.meta_key = %s AND m2.meta_key = %s
              WHERE m1.meta_value > 0
              ",
              $this->_post_expiration_time_field,
              $this->_post_expiration_notifications_field
              ) );
             */
            $posts_for_notifications = $wpdb->get_results($wpdb->prepare(
                            "
					SELECT m1.post_id, m1.meta_value AS expiration_time, m2.meta_value AS notifications
					FROM $wpdb->postmeta m1 INNER JOIN $wpdb->postmeta m2
						ON m1.post_id = m2.post_id AND m1.meta_key = %s AND m2.meta_key = %s
					WHERE m1.meta_value IS NOT NULL
				", $this->_post_expiration_time_field, $this->_post_expiration_notifications_field
            ));
            $now = time();
            $posts_ids_for_notifications = array();
            foreach ($posts_for_notifications as $post_meta) {
                $post_meta->notifications = $remaining_notifications = maybe_unserialize($post_meta->notifications);
                // check wicth notification is to be activated
                foreach ($post_meta->notifications as $key => $notification) {
                    $notification_time = $post_meta->expiration_time - $notification['event']['expiration_date'] * DAY_IN_SECONDS;
                    if ($notification_time <= $now) {
                        // notify
                        $posts_ids_for_notifications[] = $post_meta->post_id;
                        $form_id = isset($notification['form_id']) ? $notification['form_id'] : NULL;
                        if (isset($notification['to']['author']) && 'author' == $notification['to']['author']) {
                            $_POST['form_' . $form_id . '_referrer_post_id'] = $post_meta->post_id;
                        }
                        // get Notification Manager object
                        CRED_Loader::load('CLASS/Notification_Manager');
                        // add extra plceholder codes
                        add_filter('cred_subject_notification_codes', array(&$this, 'extraSubjectNotificationCodes'), 5, 3);
                        add_filter('cred_body_notification_codes', array(&$this, 'extraBodyNotificationCodes'), 5, 3);
                        // send notification now
                        CRED_Notification_Manager::sendNotifications($post_meta->post_id, $form_id, array($notification));
                        // remove extra plceholder codes
                        remove_filter('cred_subject_notification_codes', array(&$this, 'extraSubjectNotificationCodes'), 5, 3);
                        remove_filter('cred_body_notification_codes', array(&$this, 'extraBodyNotificationCodes'), 5, 3);
                        // remove notification from list
                        unset($remaining_notifications[$key]);
                    }
                }
                // update notifications list
                if (empty($remaining_notifications)) {
                    delete_post_meta($post_meta->post_id, $this->_post_expiration_notifications_field);
                } else {
                    sort($remaining_notifications);
                    update_post_meta($post_meta->post_id, $this->_post_expiration_notifications_field, $remaining_notifications);
                }
            }
        }
    }

    /**
     * Adds the meta box container.
     */
    public function cred_pe_add_post_meta_box($post_type) {
        if ($this->_post_expiration_enabled) {
            $settings = $this->getCredPESettings();
            if (isset($settings['post_expiration_post_types'])) {
                $post_types = $settings['post_expiration_post_types'];
                if (in_array($post_type, $post_types)) {
                    add_meta_box(
                            'cred_post_expiration_meta_box'
                            , __('Settings for Post Expiration Date', $this->_localization_context)
                            , array($this, 'cred_pe_render_meta_box_content')
                            , $post_type
                            , 'side'
                            , 'high'
                    );
                }
            }
        }
    }

    /**
     * Save the meta when the post is saved.
     * */
    public function cred_pe_post_save($post_id) {

        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */

        // Check if our nonce is set.
        if (!isset($_POST['cred-post-expiration-nonce']))
            return $post_id;

        $nonce = $_POST['cred-post-expiration-nonce'];

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($nonce, 'cred-post-expiration-date'))
            return $post_id;

        // If this is an autosave, our form has not been submitted,
        //     so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;

        // Check the user's permissions.
        if ('page' == $_POST['post_type']) {

            if (!current_user_can('edit_page', $post_id))
                return $post_id;
        } else {

            if (!current_user_can('edit_post', $post_id))
                return $post_id;
        }

        /* OK, its safe for us to save the data now. */
        if ($this->_post_expiration_enabled && isset($_POST['cred_pe'][$this->_post_expiration_time_field]['date']) && !empty($_POST['cred_pe'][$this->_post_expiration_time_field]['date'])) {
            // Sanitize the user input.
            $enabled = (isset($_POST['cred_pe'][$this->_post_expiration_time_field]['enable']) && 1 == $_POST['cred_pe'][$this->_post_expiration_time_field]['enable'] ? true : false);
            $expiration_time = sanitize_text_field($_POST['cred_pe'][$this->_post_expiration_time_field]['date']);

            //StaticClass::_pre($expiration_time);

            /**
             * remove previosly defined time
             */
            $expiration_time = strtotime(date('Y-m-d', $expiration_time));

            if (!$enabled || empty($expiration_time)) {
                $expiration_time = 0;
            } else {
                $hours = sanitize_text_field($_POST['cred_pe'][$this->_post_expiration_time_field]['hours']);
                $expiration_time += intval($hours) * HOUR_IN_SECONDS;
                $minutes = sanitize_text_field($_POST['cred_pe'][$this->_post_expiration_time_field]['minutes']);
                $expiration_time += intval($minutes) * MINUTE_IN_SECONDS;
            }

            // Update the meta field.
            // expire time default is 0, that means no expiration
            update_post_meta($post_id, $this->_post_expiration_time_field, $expiration_time);
            if (self::_isTimestampInRange($expiration_time)) {
                // save expiration action
                $post_status = array('post_status' => (isset($_POST['cred_pe'][$this->_post_expiration_action_field]['post_status']) ? $_POST['cred_pe'][$this->_post_expiration_action_field]['post_status'] : ''));
                $post_action = get_post_meta($post_id, $this->_post_expiration_action_field, true);
                if (!is_array($post_action)) {
                    $post_action = array(
                        'post_status' => '',
                        'custom_actions' => array()
                    );
                }
                $post_action = self::array_merge_distinct($post_action, $post_status);
                update_post_meta($post_id, $this->_post_expiration_action_field, $post_action);
            }
        }
    }

    // We need to keep this for backwards compatibility
    // Note that this function will only convert dates coming on a string:
    // - in english
    // - inside the valid PHP date range
    // We are only using this when the value being checked is not a timestamp
    // And we have tried to avoid that situation from happening
    // But for old implementation, this happens for date conditions on conditional fields
    public static function strtotime($value, $format = null) {
        if (is_null($format)) {
            $format = self::getDateFormat();
        }
        if (strpos($format, 'd/m/Y') !== false) {
            // strtotime requires a dash or dot separator to determine dd/mm/yyyy format
            preg_match('/\d{2}\/\d{2}\/\d{4}/', $value, $matches);
            if (!empty($matches)) {
                foreach ($matches as $match) {
                    $value = str_replace($match, str_replace('/', '-', $match), $value);
                }
            }
        }
        try {
            $date = new DateTime($value);
        } catch (Exception $e) {
            return false;
        }
        $timestamp = $date->format("U");
        return self::_isTimestampInRange($timestamp) ? $timestamp : false;
    }

    public static function getDateFormat() {
        $date_format = get_option('date_format');
        if (!in_array($date_format, self::$_supported_date_formats)) {
            $date_format = 'F j, Y';
        }
        return $date_format;
    }

    protected static function _isTimestampInRange($timestamp) {
        return self::$_mintimestamp <= $timestamp && $timestamp <= self::$_maxtimestamp;
    }

    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     * */
    public function cred_pe_render_meta_box_content($post) {
        $post_expiration_time = get_post_meta($post->ID, $this->_post_expiration_time_field, true);
        if (empty($post_expiration_time)) {
            $values = array(
                'date' => '',
                'hours' => 0,
                'minutes' => 0
            );
        } else {
            $values = adodb_getdate($post_expiration_time);
            $values['minutes'] = floor($values['minutes'] / 15) * 15;
            $date_format = preg_replace('/(\s)*[:@aAghGHisTcr]+(\s)*/', '', get_option('date_format'));
            $date_format = preg_replace('/[,-\/\s]+$/', '', $date_format);
            $values['date'] = adodb_date($date_format, $post_expiration_time);
        }
        $post_expiration_action = get_post_meta($post->ID, $this->_post_expiration_action_field, true);
        if (!isset($post_expiration_action['post_status'])) {
            $post_expiration_action['post_status'] = '';
        }
        echo CRED_Loader::tpl('pe_post_meta_box', array(
            'cred_post_expiration' => $this,
            'post_expiration_time' => $post_expiration_time,
            'post_expiration_action' => $post_expiration_action,
            'values' => $values,
            'post_expiration_slug' => $this->_post_expiration_slug,
            'time_field_name' => $this->_post_expiration_time_field,
            'action_field_name' => $this->_post_expiration_action_field
        ));
    }

    /**
     * add extra CRED Post Expiration notifications placeholders
     * */
    public function addExtraNotificationCodes($options, $form, $ii, $notif) {
        if ($form->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) return $options;
        $options = self::array_merge_distinct($options, $this->_extra_notification_codes);
        return $options;
    }

    /**
     * set specific CRED Form notifications placeholders to '' because this information is unavailable
     * */
    public function extraSubjectNotificationCodes($codes, $form_id, $post_id) {
        $extra_codes = array(
            '%%POST_PARENT_TITLE%%' => ''
        );
        foreach ($extra_codes as $placeholder => $replace) {
            if (!isset($codes[$placeholder])) {
                $codes[$placeholder] = $replace;
            }
        }
        $codes['%%EXPIRATION_DATE%%'] = '';
        $post_expiration_time = get_post_meta($post_id, $this->_post_expiration_time_field, true);
        if (self::_isTimestampInRange($post_expiration_time)) {
            $format = get_option('date_format');
            $codes['%%EXPIRATION_DATE%%'] = apply_filters('the_time', adodb_date($format, $post_expiration_time));
        }
        return $codes;
    }

    /**
     * set specific CRED Form notifications placeholders to '' because this information is unavailable
     * */
    public function extraBodyNotificationCodes($codes, $form_id, $post_id) {
        $extra_codes = array(
            '%%FORM_DATA%%' => '&nbsp;',
            '%%POST_PARENT_TITLE%%' => '&nbsp;',
            '%%POST_PARENT_LINK%%' => '&nbsp;',
            '%%CRED_NL%%' => "\r\n"
        );
        foreach ($extra_codes as $placeholder => $replace) {
            if (!isset($codes[$placeholder])) {
                $codes[$placeholder] = $replace;
            }
        }
        $codes['%%EXPIRATION_DATE%%'] = '';
        $post_expiration_time = get_post_meta($post_id, $this->_post_expiration_time_field, true);
        if (self::_isTimestampInRange($post_expiration_time)) {
            $format = get_option('date_format');
            $codes['%%EXPIRATION_DATE%%'] = apply_filters('the_time', adodb_date($format, $post_expiration_time));
        }
        return $codes;
    }

    /**
     * Render CRED Form notification option for post expiration.
     * */
    public function cred_pe_add_notification_option($form, $options, $notification) {        
        if ($form->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) return;
        list($ii, $name, $type) = $options;
        $notification = self::array_merge_distinct(array('event' => array('expiration_date' => 0)), $notification);
        echo CRED_Loader::tpl('pe_form_notification_option', array(
            'cred_post_expiration' => $this,
            'notification' => $notification,
            'ii' => $ii,
            'name' => $name,
            'type' => $type
        ));
    }

    /**
     * our array_merge function
     * */
    public static function array_merge_distinct(array $array1, array &$array2) {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged [$key]) && is_array($merged [$key])) {
                $merged [$key] = self::array_merge_distinct($merged [$key], $value);
            } else {
                $merged [$key] = $value;
            }
        }
        return $merged;
    }

    /**
     * cred-post-expiration-shortcode: cred-post-expiration
     *
     * Description: Display the expiration date/time of the current post
     *
     * Parameters:
     * id => post ID, defaults to global $post->ID
     * format => Format string for the date. Defaults to Wordpress settings option (date_format)
     * 
     * Example usage:
     * Expiration on [cred-post-expiration format="F jS, Y"]
     *
     * Link:
     * Format parameter is the same as here: http://codex.wordpress.org/Formatting_Date_and_Time
     *
     * Note:
     *
     */
    function cred_pe_shortcode_cred_post_expiration($atts) {
        extract(
                shortcode_atts(array(
            'id' => '',
            'format' => get_option('date_format')
                        ), $atts)
        );

        $out = '';
        $post_id = $id;
        global $post;
        if (empty($post_id) && isset($post->ID)) {
            $post_id = $post->ID;
        }
        if (!empty($post_id)) {
            $post_expiration_time = get_post_meta($post_id, $this->_post_expiration_time_field, true);
            if (self::_isTimestampInRange($post_expiration_time)) {
                $out = apply_filters('the_time', adodb_date($format, $post_expiration_time));
            }
        }
        return $out;
    }

    /**
     * Filter the custom inner shortcodes array to add CRED post expiration shortcodes
     * @param $shortcodes (array)
     * @return $shortcodes
     */
    function cred_pe_shortcodes($shortcodes) {
        foreach ($this->_shortcodes as $tag => $function) {
            $shortcodes[] = $tag;
        }
        return $shortcodes;
    }

    //render datepicker like wp-types do it
    function cred_pe_form_simple($elements) {
        static $form = NULL;
        if (file_exists(CRED_CLASSES_PATH . '/CredPostExpiration_forms.php')) {
            require_once(CRED_CLASSES_PATH . '/CredPostExpiration_forms.php');
            if (is_null($form)) {
                $form = new CRED_PostExpiration_Form();
            }
            return $form->renderElements($elements);
        }
        return '';
    }

}

global $cred_post_expiration;
$cred_post_expiration = new CRED_PostExpiration;
?>
