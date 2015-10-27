<?php

/*
 *   Notification Manager
 *
 */

final class CRED_Notification_Manager {

    private static $event = false;

    public static function init() {
        add_action('init', array(__CLASS__, '_init_'), 20);
        //self::_init_();
    }

    public static function _init_() {
        add_action('wp_loaded', array(__CLASS__, 'addHooks'), 10);
    }

    public static function addHooks() {
        add_action('save_post', array(__CLASS__, 'checkForNotifications'), 10, 2);

        /**
         * check if status is changed
         */
        $check_to_status = array('publish', 'pending', 'draft', 'private');
        $check_from_status = array_merge($check_to_status, array('new', 'future', 'trash'));
        foreach ($check_from_status as $from) {
            foreach ($check_to_status as $to) {
                if ($from == $to) {
                    continue;
                }
                $action = sprintf('%s_to_%s', $from, $to);
                add_action('_to_', array(__CLASS__, 'checkForNotifications'), 10, 2);
            }
        }

        $post_types = get_post_types(array('public' => true, 'publicly_queryable' => true, '_builtin' => true), 'names', 'or');
        //cred_log($post_types);
        foreach ($post_types as $pt) {
            //add_action("added_{$pt}_meta", array(__CLASS__, 'updatedMeta'), 20, 4);
            add_action("updated_{$pt}_meta", array(__CLASS__, 'updatedMeta'), 20, 4);
            //add_action("deleted_{$pt}_meta", array(__CLASS__, 'updatedMeta'), 20, 4);
        }
    }

    public static function removeHooks() {
        remove_action('save_post', array(__CLASS__, 'checkForNotifications'), 10, 2);
        $post_types = get_post_types(array('public' => true, 'publicly_queryable' => true, '_builtin' => true), 'names', 'or');
        //cred_log($post_types);
        foreach ($post_types as $pt) {
            //remove_action("added_{$pt}_meta", array(__CLASS__, 'updatedMeta'), 20, 4);
            remove_action("updated_{$pt}_meta", array(__CLASS__, 'updatedMeta'), 20, 4);
            //remove_action("deleted_{$pt}_meta", array(__CLASS__, 'updatedMeta'), 20, 4);
        }
    }

    private static function get_form_type($form_id) {
        return get_post_type($form_id);
    }

    private static function get_model_link($form_id) {
        $form_post_type = self::get_form_type($form_id);
        return (isset($form_post_type) && $form_post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) ? 'MODEL/UserForms' : 'MODEL/Forms';
    }

    public static function set_user_fields() {
        if (!isset(StaticClass::$_password_generated) && isset($_POST['user_pass'])) {
            StaticClass::$_password_generated = $_POST['user_pass'];
        }
        if (!isset(StaticClass::$_username_generated) && isset($_POST['user_login'])) {
            StaticClass::$_username_generated = sanitize_text_field($_POST['user_login']);
        }
        if (!isset(StaticClass::$_nickname_generated) && isset($_POST['nickname'])) {
            StaticClass::$_nickname_generated = sanitize_text_field($_POST['nickname']);
        }
    }

    public static function add($post_id, $form_id, $notifications = array()) {
        self::removeHooks();

        $post_id = intval($post_id);
        $is_user_form = self::get_form_type($form_id) == CRED_USER_FORMS_CUSTOM_POST_NAME;
        $post = ($is_user_form) ? get_userdata($post_id)->data : get_post($post_id);

        if (!$post) {
            self::addHooks();
            return;
        }

        $model = CRED_Loader::get($is_user_form ? 'MODEL/UserForms' : 'MODEL/Forms');
        $attachedData = array();
        $snapshotFields = array();
        if (!empty($notifications)) {
            foreach ($notifications as $ii => $notification) {
                if (isset($notification['event']['condition'])) {
                    foreach ($notification['event']['condition'] as $jj => $condition) {
                        if (isset($condition['only_if_changed']) && $condition['only_if_changed'] && !in_array($condition['field'], $snapshotFields)) {
                            // load all fields that have a changing condition from all notifications at once
                            $snapshotFields[] = $condition['field'];
                        }
                    }
                }
            }

            $snapshotFieldsValuesHash = self::fold(self::doHash($model->getPostFields($post_id, $snapshotFields)));
            $attachedData[$form_id] = array(
                'cred_form' => $form_id,
                'current' => array(
                    'time' => time(),
                    'post_status' => $post->post_status,
                    'snapshot' => $snapshotFieldsValuesHash,
                )
            );
            $model->setAttachedData($post_id, $attachedData);
        }

        self::addHooks();
    }

    public static function update($post_id, $form_id) {
        self::removeHooks();

        $post_id = intval($post_id);
        $is_user_form = self::get_form_type($form_id) == CRED_USER_FORMS_CUSTOM_POST_NAME;
        $post = ($is_user_form) ? get_userdata($post_id)->data : get_post($post_id);

        if (!$post) {
            self::addHooks();
            return;
        }

        $model = CRED_Loader::get($is_user_form ? 'MODEL/UserForms' : 'MODEL/Forms');
        $notificationData = $model->getFormCustomField($form_id, 'notification');
        if (
                isset($notificationData->enable) &&
                $notificationData->enable &&
                isset($notificationData->notifications)
        )
            $notifications = $notificationData->notifications;
        else
            $notifications = array();

        $attachedData = array();
        $snapshotFields = array();
        if (!empty($notifications)) {
            foreach ($notifications as $ii => $notification) {
                if (isset($notification['event']['condition'])) {
                    foreach ($notification['event']['condition'] as $jj => $condition) {
                        if (isset($condition['only_if_changed']) && $condition['only_if_changed'] && !in_array($condition['field'], $snapshotFields)) {
                            // load all fields that have a changing condition from all notifications at once
                            $snapshotFields[] = $condition['field'];
                        }
                    }
                }
            }

            $snapshotFieldsValuesHash = self::fold(self::doHash($model->getPostFields($post_id, $snapshotFields)));
            $attachedData[$form_id] = array(
                'cred_form' => $form_id,
                'current' => array(
                    'time' => time(),
                    'post_status' => $post->post_status,
                    'snapshot' => $snapshotFieldsValuesHash,
                )
            );
            //cred_log(array($snapshotFields, $snapshotFieldsValuesHash));
            $model->setAttachedData($post_id, $attachedData);
        } else {
            $model->removeAttachedData($post_id);
        }

        self::addHooks();
    }

    public static function evaluate($params) {
        extract($params);
        $is_user_form = self::get_form_type($params['form_id']) == CRED_USER_FORMS_CUSTOM_POST_NAME;
        switch ($notification['event']['type']) {
            case 'form_submit':
                if (self::$event && 'form_submit' == self::$event)
                    return self::evaluateConditions($notification, $fields, $snapshot);
                break;
            case 'post_modified':
                if ($is_user_form || ($post->post_status == $notification['event']['post_status'] && $post->post_status != $snapshot['post_status'])) {
                    return self::evaluateConditions($notification, $fields, $snapshot);
                }
                break;
            case 'meta_modified':
                return self::evaluateConditions($notification, $fields, $snapshot);
                break;
            // custom event
            default:
                if (
                        self::$event && $notification['event']['type'] == self::$event &&
                        apply_filters('cred_custom_notification_event', false, $notification, $form_id, $post->ID)
                ) {
                    return self::evaluateConditions($notification, $fields, $snapshot);
                }
                break;
        }
        return false;
    }

    public static function evaluateConditions($notification, $fields, $snapshot) {
        if (!isset($notification['event']['condition']) || empty($notification['event']['condition']))
            return true;

        // to check if fields have changed
        $snapshotFieldsHash = self::unfold($snapshot['snapshot']);
        $fieldsHash = self::doHash($fields);
        if (isset($notification['event']['any_all']))
            $ALL = ('ALL' == $notification['event']['any_all']);
        else
            $ALL = true;

        $totalresult = ($ALL) ? true : false;
        
        foreach ($notification['event']['condition'] as $jj => $condition) {
            $result = false;
            $field = $condition['field'];
            $value = $condition['value'];
            $op = $condition['op'];

            if (isset($fields[$field])) {
                $fieldvalue = $fields[$field];
                if (is_array($fieldvalue) && isset($fieldvalue[0]))
                    $fieldvalue = $fieldvalue[0];
            }
            else {
                $fieldvalue = null;
            }

            if (isset($fieldvalue) && is_array($fieldvalue)) {
                $fieldvalue = current($fieldvalue);
                $fieldvalue = array_filter($fieldvalue);
            }

            // evaluate an individual condition here
            switch ($op) {
                case '=':
                    $result = (bool) ($fieldvalue == $value);
                    break;
                case '>':
                    $result = (bool) ($fieldvalue > $value);
                    break;
                case '>=':
                    $result = (bool) ($fieldvalue >= $value);
                    break;
                case '<':
                    $result = (bool) ($fieldvalue < $value);
                    break;
                case '<=':
                    $result = (bool) ($fieldvalue <= $value);
                    break;
                case '<>':
                    $result = (bool) ($fieldvalue != $value);
                    break;
                default:
                    $result = false;
                    break;
            }

            if ($condition['only_if_changed'] && isset($snapshotFieldsHash[$field]) && isset($snapshotHash[$field]))
                $result = ($result && ($snapshotFieldsHash[$field] != $fieldsHash[$field]));

            if ($ALL)
                $totalresult = (bool) ($result && $totalresult);
            else
                $totalresult = (bool) ($result || $totalresult);

            // short-circuit the evaluation here to speed-up things
            if ($ALL && !$totalresult)
                break;
            elseif (!$ALL && $totalresult)
                break;
        }

        //cred_log(array($notification['event']['condition'], $snapshotFieldsHash, $fieldsHash, $totalresult));
        return $totalresult;
    }

    public static function triggerNotifications($post_id, $data, $attachedData = null) {
        $form_id = $data['form_id'];

        $is_user_form = self::get_form_type($form_id) == CRED_USER_FORMS_CUSTOM_POST_NAME;

        if ($is_user_form) {
            $post = get_userdata($post_id)->data;
        } else {
            if (isset($data['post'])) {
                $post = $data['post'];
            } else {
                $post = get_post($post_id);
            }
        }

        if (!$post) {
            return;
        }
        $model = CRED_Loader::get($is_user_form ? 'MODEL/UserForms' : 'MODEL/Forms');
        if (empty($attachedData)) {
            $attachedData = $model->getAttachedData($post_id);
        }

        // trigger for this event, if set
        if (isset($data['event'])) {
            self::$event = $data['event'];
        } else {
            self::$event = false;
        }

        $notification = isset($data['notification']) ? $data['notification'] : false;

        if (
                (!$attachedData && !$is_user_form) ||
                !$notification ||
                !isset($notification->enable) ||
                !$notification->enable ||
                empty($notification->notifications)
        ) {
            return;
        }

        $notificationsToSent = array();
        foreach ($notification->notifications as $ii => $notif) {
            $send_notification = false;

            if (isset($notif['event'])) {
                $conditionFields = array();
                $_conditionFields = array();
                if (isset($notif['event']['condition']) && !empty($notif['event']['condition'])) {
                    foreach ($notif['event']['condition'] as $jj => $condition)
                        $conditionFields[] = $condition['field'];

                    $_conditionFields = $model->getPostFields($post_id, $conditionFields);
                }

                $send_notification = self::evaluate(array(
                            'form_id' => $form_id,
                            'post' => $post,
                            'notification' => $notif,
                            'fields' => $_conditionFields,
                            'snapshot' => isset($attachedData[$form_id]) ? $attachedData[$form_id]['current'] : array()
                ));
            }

            if ($send_notification) {
                $notificationsToSent[] = $notif;
            }
        }

        //cred_log($notificationsToSent);
        // removed but it's necessary further debugging 'Notification is being sent when visit the post edit screen'
        // if (!is_admin()&&!empty($notificationsToSent))
        if (!empty($notificationsToSent)) {
            self::sendNotifications($post_id, $form_id, $notificationsToSent);
        }
    }

    public static function updatedMeta($meta_id, $object_id, $meta_key, $_meta_value) {
        switch ($meta_key) {
            case '_edit_lock':
                break;
            default:
                self::checkForNotifications($object_id, get_post($object_id));
                break;
        }
    }

    public static function checkForNotifications($post_id, $post) {
        $model = CRED_Loader::get('MODEL/Forms');
        $attachedData = $model->getAttachedData($post_id);
        if (!$attachedData)
            return;

        $notification = false;
        foreach ($attachedData as $form_id => $data) {
            $notification = $model->getFormCustomField($form_id, 'notification');
            break;
        }
        if ($notification) {
            self::triggerNotifications($post_id, array(
                'notification' => $notification,
                'form_id' => $form_id,
                'post' => $post
            ));
        }
        // keep up-to-date with notification settings for form and post field values
        self::update($post_id, $form_id);
    }

    private static function hash($value) {
        // use simple crc-32 for speed and space issues, 
        // not concerned with hash security here
        // http://php.net/manual/en/function.crc32.php
        $hash = hash("crc32b", $value);
        //return $key.'##'.$value;
        return $hash;
    }

    public static function doHash($data = array()) {
        if (empty($data))
            return array();
        $hashes = array();
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value))
                $value = serialize($value);
            $hashes[$key] = self::hash($value);
        }
        return $hashes;
    }

    public static function fold($hashes) {
        $hash = array();
        foreach ($hashes as $key => $value) {
            $hash[] = $key . '##' . $value;
        }
        return implode('|', $hash);
    }

    public static function unfold($hash) {
        if (empty($hash) || '' == $hash)
            return array();
        $hasharray = explode('|', $hash);
        $undohash = array();
        foreach ($hasharray as $hash1) {
            $tmp = explode('##', $hash1);
            $undohash[$tmp[0]] = $tmp[1];
        }
        return $undohash;
    }

    public static function getCurrentUserData() {
        global $current_user;

        get_currentuserinfo();

        $user_data = new stdClass;

        $user_data->ID = isset($current_user->ID) ? $current_user->ID : 0;
        $user_data->roles = isset($current_user->roles) ? $current_user->roles : array();
        $user_data->role = isset($current_user->roles[0]) ? $current_user->roles[0] : '';
        $user_data->login = isset($current_user->data->user_login) ? $current_user->data->user_login : '';
        $user_data->display_name = isset($current_user->data->display_name) ? $current_user->data->display_name : '';

        //print_r($user_data);
        return $user_data;
    }

    // translate codes in notification fields of cred form (like %%POST_ID%% to post id etc..)
    public static function replacePlaceholders($field, $data) {
        return str_replace(array_keys($data), array_values($data), $field);
    }

    public static function sendTestNotification($form_id, $notification) {
        // bypass if nothing
        if (!$notification || empty($notification) /* || !isset($notification['to']['type']) */)
            return array('error' => __('No Notification given', 'wp-cred'));

        // dummy
        $post_id = null;
        // custom action hooks here, for 3rd-party integration
        //do_action('cred_before_send_notifications_'.$form_id, $post_id);
        //do_action('cred_before_send_notifications', $post_id);
        // get Model
        $model = CRED_Loader::get('MODEL/Forms');

        // get Mailer
        $mailer = CRED_Loader::get('CLASS/Mail_Handler');

        // get current user
        $user = self::getCurrentUserData();

        // get some data for placeholders
        $form_post = get_post($form_id);
        $form_title = ($form_post) ? $form_post->post_title : '';
        //$link=get_permalink( $post_id );
        //$title=get_the_title( $post_id );
        //$admin_edit_link=get_edit_post_link( $post_id );
        //$date=date('d/m/Y H:i:s');        
        $date = date('Y-m-d H:i:s', current_time('timestamp'));


        // placeholder codes, allow to add custom
        $data_subject = apply_filters('cred_subject_notification_codes', array(
            '%%USER_LOGIN_NAME%%' => $user->login,
            '%%USER_DISPLAY_NAME%%' => $user->display_name,
            '%%POST_ID%%' => 'DUMMY_POST_ID',
            '%%POST_TITLE%%' => 'DUMMY_POST_TITLE',
            '%%FORM_NAME%%' => $form_title,
            '%%DATE_TIME%%' => $date
                ), $form_id, $post_id);

        // placeholder codes, allow to add custom
        $data_body = apply_filters('cred_body_notification_codes', array(
            '%%USER_LOGIN_NAME%%' => $user->login,
            '%%USER_DISPLAY_NAME%%' => $user->display_name,
            '%%POST_ID%%' => 'DUMMY_POST_ID',
            '%%POST_TITLE%%' => 'DUMMY_POST_TITLE',
            '%%POST_LINK%%' => 'DUMMY_POST_LINK',
            '%%POST_ADMIN_LINK%%' => 'DUMMY_ADMIN_POST_LINK',
            '%%FORM_NAME%%' => $form_title,
            '%%DATE_TIME%%' => $date
                ), $form_id, $post_id);

        //cred_log(array($post_id, $form_id, $data_subject, $data_body));
        // reset mail handler
        $mailer->reset();
        $mailer->setHTML(true, false);
        $recipients = array();

        // parse Notification Fields

        if (!isset($notification['to']['type']))
            $notification['to']['type'] = array();
        if (!is_array($notification['to']['type']))
            $notification['to']['type'] = (array) $notification['to']['type'];

        // notification to a mail field (which is saved as post meta)
        // bypass since no actual post type
        /* if (
          in_array('mail_field', $notification['to']['type']) &&
          isset($notification['to']['mail_field']['address_field']) &&
          !empty($notification['to']['mail_field']['address_field'])
          )
          {
          $_to_type='to';
          $_addr=false;
          $_addr_name=false;
          $_addr_lastname=false;

          $_addr=$model->getPostMeta($post_id, $notification['to']['mail_field']['address_field']);

          if (
          isset($notification['to']['mail_field']['to_type']) &&
          in_array($notification['to']['mail_field']['to_type'], array('to', 'cc', 'bcc'))
          )
          {
          $_to_type=$notification['to']['mail_field']['to_type'];
          }

          if (
          isset($notification['to']['mail_field']['name_field']) &&
          !empty($notification['to']['mail_field']['name_field']) &&
          '###none###'!=$notification['to']['mail_field']['name_field']
          )
          {
          $_addr_name=$model->getPostMeta($post_id, $notification['to']['mail_field']['name_field']);
          }

          if (
          isset($notification['to']['mail_field']['lastname_field']) &&
          !empty($notification['to']['mail_field']['lastname_field']) &&
          '###none###'!=$notification['to']['mail_field']['lastname_field']
          )
          {
          $_addr_lastname=$model->getPostMeta($post_id, $notification['to']['mail_field']['lastname_field']);
          }

          // add to recipients
          $recipients[]=array(
          'to'=>$_to_type,
          'address'=>$_addr,
          'name'=>$_addr_name,
          'lastname'=>$_addr_lastname
          );
          } */

        // notification to an exisiting wp user
        // bypass 
        /* if (in_array('wp_user', $notification['to']['type']))
          {
          $_to_type='to';
          $_addr=false;
          $_addr_name=false;
          $_addr_lastname=false;

          if (
          isset($notification['to']['wp_user']['to_type']) &&
          in_array($notification['to']['wp_user']['to_type'], array('to', 'cc', 'bcc'))
          )
          {
          $_to_type=$notification['to']['wp_user']['to_type'];
          }

          $_addr=$notification['to']['wp_user']['user'];
          $user_id = email_exists($_addr);
          if ($user_id)
          {
          $user_info = get_userdata($user_id);
          $_addr_name = (isset($user_info->user_firstname)&&!empty($user_info->user_firstname))?$user_info->user_firstname:false;
          $_addr_lastname = (isset($user_info->user_lasttname)&&!empty($user_info->user_lasttname))?$user_info->user_lastname:false;

          // add to recipients
          $recipients[]=array(
          'to'=>$_to_type,
          'address'=>$_addr,
          'name'=>$_addr_name,
          'lastname'=>$_addr_lastname
          );
          }
          } */

        // notification to specific recipients
        if (in_array('specific_mail', $notification['to']['type']) && isset($notification['to']['specific_mail']['address'])) {
            $tmp = explode(',', $notification['to']['specific_mail']['address']);
            foreach ($tmp as $aa)
                $recipients[] = array(
                    'address' => $aa,
                    'to' => false,
                    'name' => false,
                    'lastname' => false
                );
            unset($tmp);
        }

        // add custom recipients by 3rd-party
        //$recipients=apply_filters('cred_notification_recipients', $recipients, $notification, $form_id, $post_id);

        if (!$recipients || empty($recipients))
            return array('error' => __('No recipients specified', 'wp-cred'));

        // build recipients
        foreach ($recipients as $ii => $recipient) {
            // nowhere to send, bypass
            if (!isset($recipient['address']) || !$recipient['address']) {
                unset($recipients[$ii]);
                continue;
            }

            if (false === $recipient['to']) {
                // this is already formatted
                $recipients[$ii] = $recipient['address'];
                continue;
            }

            $tmp = '';
            $tmp.=$recipient['to'] . ': ';
            $tmp2 = array();
            if ($recipient['name'])
                $tmp2[] = $recipient['name'];
            if ($recipient['lastname'])
                $tmp2[] = $recipient['lastname'];
            if (!empty($tmp2)) {
                $tmp.=implode(' ', $tmp2) . ' <' . $recipient['address'] . '>';
            } else
                $tmp.=$recipient['address'];

            $recipients[$ii] = $tmp;
        }
        $mailer->addRecipients($recipients);

        // build SUBJECT
        $_subj = '';
        if (isset($notification['mail']['subject']))
            $_subj = $notification['mail']['subject'];

        // provide WPML localisation        
        if (isset($notification['_cred_icl_string_id']['subject'])) {
            $notification_subject_string_translation_name = self::getNotification_translation_name($notification['_cred_icl_string_id']['subject']);
            if ($notification_subject_string_translation_name) {
                $_subj = cred_translate($notification_subject_string_translation_name, $_subj, 'cred-form-' . $form_title . '-' . $form_id);
            }
        }

        // replace placeholders
        $_subj = self::replacePlaceholders($_subj, $data_subject);

        // parse shortcodes if necessary relative to $post_id
        $_subj = CRED_Helper::renderWithPost(stripslashes($_subj), $post_id, false);

        $mailer->setSubject($_subj);

        // build BODY
        $_bod = '';
        if (isset($notification['mail']['body']))
            $_bod = $notification['mail']['body'];

        // provide WPML localisation        
        if (isset($notification['_cred_icl_string_id']['body'])) {
            $notification_body_string_translation_name = self::getNotification_translation_name($notification['_cred_icl_string_id']['body']);
            if ($notification_body_string_translation_name) {
                $_bod = cred_translate($notification_body_string_translation_name, $_bod, 'cred-form-' . $form_title . '-' . $form_id);
            }
        }

        // replace placeholders
        $_bod = self::replacePlaceholders($_bod, $data_body);

        // parse shortcodes/rich text if necessary relative to $post_id
        $_bod = CRED_Helper::renderWithPost($_bod, $post_id);

        //https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/195775787/comments#310779109
        $_bod = stripslashes($_bod);

        $mailer->setBody($_bod);

        // build FROM address / name, independantly
        $_from = array();
        if (isset($notification['from']['address']) && !empty($notification['from']['address']))
            $_from['address'] = $notification['from']['address'];
        if (isset($notification['from']['name']) && !empty($notification['from']['name']))
            $_from['name'] = $notification['from']['name'];
        if (!empty($_from))
            $mailer->setFrom($_from);

        // send it
        $_send_result = $mailer->send();

        // custom action hooks here, for 3rd-party integration
        //do_action('cred_after_send_notifications_'.$form_id, $post_id);
        //do_action('cred_after_send_notifications', $post_id);

        if (!$_send_result) {
            if (empty($_bod)) {
                return array('error' => __('Body content is required', 'wp-cred'));
            } else {
                return array('error' => __('Mail failed to be sent', 'wp-cred'));
            }
        }
        return array('success' => __('Mail sent succesfully', 'wp-cred'));
    }

    public static function sendNotifications($post_id, $form_id, $notificationsToSent) {
        // custom action hooks here, for 3rd-party integration
        //do_action('cred_before_send_notifications_'.$form_id, $post_id, $form_id, $notificationsToSent);
        //do_action('cred_before_send_notifications', $post_id, $form_id, $notificationsToSent);
        // get Mailer
        $mailer = CRED_Loader::get('CLASS/Mail_Handler');

        // get current user
        $user = self::getCurrentUserData();

        $is_user_form = self::get_form_type($form_id) == CRED_USER_FORMS_CUSTOM_POST_NAME;

        // get Model        
        $model = $is_user_form ? CRED_Loader::get('MODEL/UserForms') : CRED_Loader::get('MODEL/Forms');

        //user created/updated
        $the_user = ($is_user_form) ? get_userdata($post_id)->data : null;

        // get some data for placeholders
        $form_post = get_post($form_id);
        $form_title = ($form_post) ? $form_post->post_title : '';
        $link = get_permalink($post_id);
        $title = get_the_title($post_id);
        $admin_edit_link = CRED_CRED::getPostAdminEditLink($post_id); //get_edit_post_link( $post_id );
        //$date=date('d/m/Y H:i:s');
        $date = date('Y-m-d H:i:s', current_time('timestamp'));

        // placeholder codes, allow to add custom
        $data_subject = apply_filters('cred_subject_notification_codes', array(
            '%%USER_USERID%%' => (isset($the_user) && isset($the_user->ID)) ? $the_user->ID : '',
            '%%USER_EMAIL%%' => (isset($the_user) && isset($the_user->user_email)) ? $the_user->user_email : '',
            '%%USER_USERNAME%%' => isset(StaticClass::$_username_generated) ? StaticClass::$_username_generated : '',
            '%%USER_PASSWORD%%' => isset(StaticClass::$_password_generated) ? StaticClass::$_password_generated : '',
            '%%USER_NICKNAME%%' => isset(StaticClass::$_nickname_generated) ? StaticClass::$_nickname_generated : '',
            '%%USER_LOGIN_NAME%%' => $user->login,
            '%%USER_DISPLAY_NAME%%' => $user->display_name,
            '%%POST_ID%%' => $post_id,
            '%%POST_TITLE%%' => $title,
            '%%FORM_NAME%%' => $form_title,
            '%%DATE_TIME%%' => $date
                ), $form_id, $post_id);

        // placeholder codes, allow to add custom
        $data_body = apply_filters('cred_body_notification_codes', array(
            '%%USER_USERID%%' => (isset($the_user) && isset($the_user->ID)) ? $the_user->ID : '',
            '%%USER_EMAIL%%' => (isset($the_user) && isset($the_user->user_email)) ? $the_user->user_email : '',
            '%%USER_USERNAME%%' => isset(StaticClass::$_username_generated) ? StaticClass::$_username_generated : '',
            '%%USER_PASSWORD%%' => isset(StaticClass::$_password_generated) ? StaticClass::$_password_generated : '',
            '%%USER_NICKNAME%%' => isset(StaticClass::$_nickname_generated) ? StaticClass::$_nickname_generated : '',
            '%%USER_LOGIN_NAME%%' => $user->login,
            '%%USER_DISPLAY_NAME%%' => $user->display_name,
            '%%POST_ID%%' => $post_id,
            '%%POST_TITLE%%' => $title,
            '%%POST_LINK%%' => $link,
            '%%POST_ADMIN_LINK%%' => $admin_edit_link,
            '%%FORM_NAME%%' => $form_title,
            '%%DATE_TIME%%' => $date
                ), $form_id, $post_id);

        //cred_log(array($post_id, $form_id, $data_subject, $data_body, $notificationsToSent));

        foreach ($notificationsToSent as $notification) {
            // bypass if nothing
            if (
                    !$notification || empty($notification) || !(isset($notification['to']['type']) || isset($notification['to']['author']))
            ) {
                continue;
            }

            // reset mail handler
            $mailer->reset();
            $mailer->setHTML(true, false);
            $recipients = array();

            if (isset($notification['to']['author']) && 'author' == $notification['to']['author']) {
                $author_post_id = isset($_POST['form_' . $form_id . '_referrer_post_id']) ? $_POST['form_' . $form_id . '_referrer_post_id'] : 0;
                if (0 == $author_post_id && $post_id) {
                    $mypost = get_post($post_id);
                    $author_id = $mypost->post_author;
                } else {
                    $mypost = get_post($author_post_id);
                    $author_id = $user->ID;
                    if (!isset($author_id)) {
                        $author_id = $mypost->post_author;
                    }
                }
                if ($author_id) {
                    $_to_type = 'to';
                    $user_info = get_userdata($author_id);
                    $_addr_name = (isset($user_info) && isset($user_info->user_firstname) && !empty($user_info->user_firstname)) ? $user_info->user_firstname : false;
                    $_addr_lastname = (isset($user_info) && isset($user_info->user_lasttname) && !empty($user_info->user_lasttname)) ? $user_info->user_lastname : false;
                    $_addr = $user_info->user_email;

                    if (isset($_addr)) {
                        $recipients[] = array(
                            'to' => $_to_type,
                            'address' => $_addr,
                            'name' => $_addr_name,
                            'lastname' => $_addr_lastname
                        );
                    }
                }
            }

            // parse Notification Fields
            if (!isset($notification['to']['type']))
                $notification['to']['type'] = array();
            if (!is_array($notification['to']['type']))
                $notification['to']['type'] = (array) $notification['to']['type'];

            // notification to a mail field (which is saved as post meta)
            if (
                    in_array('mail_field', $notification['to']['type']) &&
                    isset($notification['to']['mail_field']['address_field']) &&
                    !empty($notification['to']['mail_field']['address_field'])
            ) {
                $_to_type = 'to';
                $_addr = false;
                $_addr_name = false;
                $_addr_lastname = false;

                if ($is_user_form) {
                    $_addr = $the_user->user_email;
                } else
                    $_addr = $model->getPostMeta($post_id, $notification['to']['mail_field']['address_field']);

                if (
                        isset($notification['to']['mail_field']['to_type']) &&
                        in_array($notification['to']['mail_field']['to_type'], array('to', 'cc', 'bcc'))
                ) {
                    $_to_type = $notification['to']['mail_field']['to_type'];
                }

                if (
                        isset($notification['to']['mail_field']['name_field']) &&
                        !empty($notification['to']['mail_field']['name_field']) &&
                        '###none###' != $notification['to']['mail_field']['name_field']
                ) {
                    $_addr_name = $is_user_form ? $model->getUserMeta($post_id, $notification['to']['mail_field']['name_field']) : $model->getPostMeta($post_id, $notification['to']['mail_field']['name_field']);
                }

                if (
                        isset($notification['to']['mail_field']['lastname_field']) &&
                        !empty($notification['to']['mail_field']['lastname_field']) &&
                        '###none###' != $notification['to']['mail_field']['lastname_field']
                ) {
                    $_addr_lastname = $is_user_form ? $model->getUserMeta($post_id, $notification['to']['mail_field']['lastname_field']) : $model->getPostMeta($post_id, $notification['to']['mail_field']['lastname_field']);
                }

                // add to recipients
                $recipients[] = array(
                    'to' => $_to_type,
                    'address' => $_addr,
                    'name' => $_addr_name,
                    'lastname' => $_addr_lastname
                );
            }

            // notification to an exisiting wp user
            if (in_array('wp_user', $notification['to']['type'])) {
                $_to_type = 'to';
                $_addr = false;
                $_addr_name = false;
                $_addr_lastname = false;

                if (
                        isset($notification['to']['wp_user']['to_type']) &&
                        in_array($notification['to']['wp_user']['to_type'], array('to', 'cc', 'bcc'))
                ) {
                    $_to_type = $notification['to']['wp_user']['to_type'];
                }

                $_addr = $notification['to']['wp_user']['user'];
                $user_id = email_exists($_addr);
                if ($user_id) {
                    $user_info = get_userdata($user_id);
                    $_addr_name = (isset($user_info->user_firstname) && !empty($user_info->user_firstname)) ? $user_info->user_firstname : false;
                    $_addr_lastname = (isset($user_info->user_lasttname) && !empty($user_info->user_lasttname)) ? $user_info->user_lastname : false;

                    // add to recipients
                    $recipients[] = array(
                        'to' => $_to_type,
                        'address' => $_addr,
                        'name' => $_addr_name,
                        'lastname' => $_addr_lastname
                    );
                }
            }

            // notification to an exisiting wp user
            if (in_array('user_id_field', $notification['to']['type'])) {
                $_to_type = 'to';
                $_addr = false;
                $_addr_name = false;
                $_addr_lastname = false;

                if (
                        isset($notification['to']['user_id_field']['to_type']) &&
                        in_array($notification['to']['user_id_field']['to_type'], array('to', 'cc', 'bcc'))
                ) {
                    $_to_type = $notification['to']['user_id_field']['to_type'];
                }

                //$user_id = $is_user_form ? @trim($model->getUserMeta($post_id, $notification['to']['user_id_field']['field_name'])) : @trim($model->getPostMeta($post_id, $notification['to']['user_id_field']['field_name']));
                $user_id = $is_user_form ? $post_id : @trim($model->getPostMeta($post_id, $notification['to']['user_id_field']['field_name']));

                if ($user_id) {
                    $user_info = get_userdata($user_id);
                    if ($user_info) {
                        $_addr = (isset($user_info->user_email) && !empty($user_info->user_email)) ? $user_info->user_email : false;
                        $_addr_name = (isset($user_info->user_firstname) && !empty($user_info->user_firstname)) ? $user_info->user_firstname : false;
                        $_addr_lastname = (isset($user_info->user_lasttname) && !empty($user_info->user_lasttname)) ? $user_info->user_lastname : false;

                        // add to recipients
                        $recipients[] = array(
                            'to' => $_to_type,
                            'address' => $_addr,
                            'name' => $_addr_name,
                            'lastname' => $_addr_lastname
                        );
                    }
                }
            }

            // notification to specific recipients
            if (in_array('specific_mail', $notification['to']['type']) && isset($notification['to']['specific_mail']['address'])) {
                $tmp = explode(',', $notification['to']['specific_mail']['address']);
                foreach ($tmp as $aa)
                    $recipients[] = array(
                        'address' => $aa,
                        'to' => false,
                        'name' => false,
                        'lastname' => false
                    );
                unset($tmp);
            }

            // add custom recipients by 3rd-party
            //cred_log(array('cred_notification_recipients', $recipients, $notification, $form_id, $post_id));
            //$recipients=apply_filters('cred_notification_recipients', $recipients, array('form_id'=>$form_id, 'post_id'=>$post_id, 'notification'=>$notification));
            $recipients = apply_filters('cred_notification_recipients', $recipients, $notification, $form_id, $post_id);
            if (!$recipients || empty($recipients))
                continue;

            // build recipients
            foreach ($recipients as $ii => $recipient) {
                // nowhere to send, bypass
                if (!isset($recipient['address']) || !$recipient['address']) {
                    unset($recipients[$ii]);
                    continue;
                }

                if (false === $recipient['to']) {
                    // this is already formatted
                    $recipients[$ii] = $recipient['address'];
                    continue;
                }

                $tmp = '';
                $tmp.=$recipient['to'] . ': ';
                $tmp2 = array();
                if ($recipient['name'])
                    $tmp2[] = $recipient['name'];
                if ($recipient['lastname'])
                    $tmp2[] = $recipient['lastname'];
                if (!empty($tmp2)) {
                    $tmp.=implode(' ', $tmp2) . ' <' . $recipient['address'] . '>';
                } else
                    $tmp.=$recipient['address'];

                $recipients[$ii] = $tmp;
            }


            //cred_log($recipients);
            $mailer->addRecipients($recipients);

            if (isset($_POST[StaticClass::PREFIX . 'cred_container_id']))
                $notification['mail']['body'] = str_replace("[cred-container-id]", StaticClass::$_cred_container_id, $notification['mail']['body']);

            global $current_user_id;
            $current_user_id = $user_id;
            if (!$user_id && $is_user_form) {
                $current_user_id = $post_id;
            }

            // build SUBJECT
            $_subj = '';
            if (isset($notification['mail']['subject']))
                $_subj = $notification['mail']['subject'];

            // build BODY
            $_bod = '';
            if (isset($notification['mail']['body']))
                $_bod = $notification['mail']['body'];

            // replace placeholders
            $_subj = self::replacePlaceholders($_subj, $data_subject);

            // replace placeholders
            $_bod = self::replacePlaceholders($_bod, $data_body);

            //fixing https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/188538611/comments
            if (defined('WPCF_EMBEDDED_ABSPATH') && WPCF_EMBEDDED_ABSPATH) {
                require_once WPCF_EMBEDDED_ABSPATH . '/frontend.php';
            }            

            // provide WPML localisation            
            if (isset($notification['_cred_icl_string_id']['subject'])) {
                $notification_subject_string_translation_name = self::getNotification_translation_name($notification['_cred_icl_string_id']['subject']);
                if ($notification_subject_string_translation_name) {
                    $_subj = cred_translate($notification_subject_string_translation_name, $_subj, 'cred-form-' . $form_title . '-' . $form_id);
                }
            }

            // provide WPML localisation           
            if (isset($notification['_cred_icl_string_id']['body'])) {
                $notification_body_string_translation_name = self::getNotification_translation_name($notification['_cred_icl_string_id']['body']);
                if ($notification_body_string_translation_name) {
                    $_bod = cred_translate($notification_body_string_translation_name, $_bod, 'cred-form-' . $form_title . '-' . $form_id);
                }
            }

            // parse shortcodes if necessary relative to $post_id
            $_subj = CRED_Helper::renderWithPost(stripslashes($_subj), $post_id, false);

            $mailer->setSubject($_subj);

            // parse shortcodes/rich text if necessary relative to $post_id
            $_bod = CRED_Helper::renderWithPost($_bod, $post_id);

            //https://icanlocalize.basecamphq.com/projects/11629195-toolset-peripheral-work/todo_items/195775787/comments#310779109
            $_bod = stripslashes($_bod);
            $mailer->setBody($_bod);

            // build FROM address / name, independantly
            $_from = array();
            if (isset($notification['from']['address']) && !empty($notification['from']['address']))
                $_from['address'] = $notification['from']['address'];
            if (isset($notification['from']['name']) && !empty($notification['from']['name']))
                $_from['name'] = $notification['from']['name'];
            if (!empty($_from))
                $mailer->setFrom($_from);

            // send it
            $_send_result = $mailer->send();

            if ($_send_result !== true) {
                update_option('_' . $form_id . '_last_mail_error', $_send_result);
            }
        }
        // custom action hooks here, for 3rd-party integration
        //do_action('cred_after_send_notifications_'.$form_id, $post_id);
        //do_action('cred_after_send_notifications', $post_id);
    }

    //retrieve string translation name of the notification based on string ID (icl string id)
    public static function getNotification_translation_name($id) {

        if (function_exists('icl_t')) {
            global $wpdb;
            $dBtable = $wpdb->prefix . "icl_strings";
            $string_translation_name_notifications = $wpdb->get_var($wpdb->prepare("SELECT name FROM $dBtable WHERE id=%d", $id));

            if ($string_translation_name_notifications) {
                return $string_translation_name_notifications;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}
