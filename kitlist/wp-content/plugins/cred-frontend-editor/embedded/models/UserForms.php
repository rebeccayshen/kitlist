<?php

/**
 *
 *   CRED forms model
 *
 *   (uses custom posts and fields to store form data)
 *
 * */
final class CRED_User_Forms_Model extends CRED_Abstract_Model implements CRED_Singleton {

    private $post_type_name = '';
    private $form_meta_fields = array('form_settings', 'wizard', 'post_expiration', 'notification', 'extra');
    private $prefix = '_cred_';

    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct();
        $this->post_type_name = CRED_USER_FORMS_CUSTOM_POST_NAME;
    }

    public function prepareDB() {
        $this->register_form_type();
    }

    public function disable_richedit_for_cred_forms($default) {
        global $post;
        if ($this->post_type_name == get_post_type($post))
            return false;
        return $default;
    }

    private function register_form_type() {
        $args = array(
            'labels' => array(
                'name' => __('CRED User Forms', 'wp-cred'),
                'singular_name' => __('CRED User Form', 'wp-cred'),
                'add_new' => __('Add New', 'wp-cred'),
                'add_new_item' => __('Add New CRED User Form', 'wp-cred'),
                'edit_item' => __('Edit CRED User Form', 'wp-cred'),
                'new_item' => __('New CRED User Form', 'wp-cred'),
                'view_item' => __('View CRED User Form', 'wp-cred'),
                'search_items' => __('Search CRED User Forms', 'wp-cred'),
                'not_found' => __('No user forms found', 'wp-cred'),
                'not_found_in_trash' => __('No user form found in Trash', 'wp-cred'),
                'parent_item_colon' => '',
                'menu_name' => 'CRED User Forms'
            ),
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'query_var' => false,
            'rewrite' => false,
            'can_export' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 80,
            //'supports' => array()
            'supports' => array('title', 'editor' /* ,'author' */)
        );
        register_post_type($this->post_type_name, $args);

        add_filter('user_can_richedit', array(&$this, 'disable_richedit_for_cred_forms'));
    }

    public function getDefaultMessages() {
        static $messages = false;

        if (!$messages) {
            $messages = array(
                /* 'cred_message_add_new_repeatable_field' =>'Add Another',
                  'cred_message_remove_repeatable_field'   =>'Remove',
                  'cred_message_cancel_upload_text' =>'Retry Upload', */
                'cred_message_post_saved' => 'User ' . __('Saved', 'wp-cred'),
                //'cred_message_post_not_saved'=>'Post Not Saved',
                'cred_message_post_not_saved_singular' => __('The user was not saved because of the following problem:', 'wp-cred'),
                'cred_message_post_not_saved_plural' => __('The user was not saved because of the following %NN problems:', 'wp-cred'),
                //'cred_message_notification_was_sent'=>'Notification was sent',
                //'cred_message_notification_failed'=>'Notification failed',
                'cred_message_invalid_form_submission' => 'Invalid User Form Submission (nonce failure)',
                'cred_message_no_data_submitted' => 'Invalid User Form Submission (maybe a file has a size greater than allowed)',
                'cred_message_upload_failed' => 'Upload Failed',
                'cred_message_field_required' => 'This field is required',
                'cred_message_enter_valid_date' => 'Please enter a valid date',
                'cred_message_values_do_not_match' => 'Field values do not match',
                'cred_message_enter_valid_email' => 'Please enter a valid email address',
                'cred_message_enter_valid_number' => 'Please enter numeric data',
                'cred_message_enter_valid_url' => 'Please enter a valid URL address',
                'cred_message_enter_valid_captcha' => 'Wrong CAPTCHA',
                'cred_message_show_captcha' => 'Show CAPTCHA',
                'cred_message_edit_skype_button' => 'Edit Skype Button',
                'cred_message_not_valid_image' => 'Not Valid Image',
                'cred_message_file_type_not_allowed' => 'File type not allowed',
                'cred_message_image_width_larger' => 'Image width larger than %dpx',
                'cred_message_image_height_larger' => 'Image height larger than %dpx',
                'cred_message_show_popular' => 'Show Popular',
                'cred_message_hide_popular' => 'Hide Popular',
                'cred_message_add_taxonomy' => 'Add',
                'cred_message_remove_taxonomy' => 'Remove',
                'cred_message_add_new_taxonomy' => 'Add New',
            );
        }
        return $messages;
    }

    public function getDefaultMessageDescriptions() {
        static $desc = false;

        if (!$desc) {
            $desc = array(
                /* 'cred_message_add_new_repeatable_field' =>__('Add another repetitive field','wp-cred'),
                  'cred_message_remove_repeatable_field'   =>  __('Remove repetitive field','wp-cred'),
                  'cred_message_cancel_upload_text' => __('Retry Upload file/image','wp-cred'), */
                'cred_message_post_saved' => __('User saved Message', 'wp-cred'),
                //'cred_message_post_not_saved'=>__('Post not saved message','wp-cred'),
                'cred_message_post_not_saved_singular' => __('User not saved message (one problem)', 'wp-cred'),
                'cred_message_post_not_saved_plural' => __('User not saved message (several problems)', 'wp-cred'),
                //'cred_message_notification_was_sent'=>__('Notification sent message','wp-cred'),
                //'cred_message_notification_failed'=>__('Notification failed message','wp-cred'),
                'cred_message_invalid_form_submission' => __('Invalid submission message', 'wp-cred'),
                'cred_message_no_data_submitted' => __('Invalid Form Submission (maybe a file has a size greater than allowed)', 'wp-cred'),
                'cred_message_upload_failed' => __('Upload failed message', 'wp-cred'),
                'cred_message_field_required' => __('Required field message', 'wp-cred'),
                'cred_message_enter_valid_date' => __('Invalid date message', 'wp-cred'),
                'cred_message_values_do_not_match' => __('Invalid hidden field value message', 'wp-cred'),
                'cred_message_enter_valid_email' => __('Invalid email message', 'wp-cred'),
                'cred_message_enter_valid_number' => __('Invalid numeric field message', 'wp-cred'),
                'cred_message_enter_valid_url' => __('Invalid URL message', 'wp-cred'),
                'cred_message_enter_valid_captcha' => __('Invalid captcha message', 'wp-cred'),
                'cred_message_show_captcha' => __('Show captcha button', 'wp-cred'),
                'cred_message_edit_skype_button' => __('Edit skype button', 'wp-cred'),
                'cred_message_not_valid_image' => __('Invalid image message', 'wp-cred'),
                'cred_message_file_type_not_allowed' => __('Invalid file type message', 'wp-cred'),
                'cred_message_image_width_larger' => __('Invalid image width message', 'wp-cred'),
                'cred_message_image_height_larger' => __('Invalid image height message', 'wp-cred'),
                'cred_message_show_popular' => __('Taxonomy show popular message', 'wp-cred'),
                'cred_message_hide_popular' => __('Taxonomy hide popular message', 'wp-cred'),
                'cred_message_add_taxonomy' => __('Add taxonomy term', 'wp-cred'),
                'cred_message_remove_taxonomy' => __('Remove taxonomy term', 'wp-cred'),
                'cred_message_add_new_taxonomy' => __('Add new taxonomy message', 'wp-cred')
            );
        }
        return $desc;
    }

    private function getFieldkeys($with_quotes = '', $with_prefix = true) {
        if ($with_prefix)
            $prefix = $this->prefix;
        else
            $prefix = '';

        $keys = array();
        foreach ($this->form_meta_fields as $fkey) {
            $keys[] = $with_quotes . $prefix . $fkey . $with_quotes;
        }
        return $keys;
    }

    public function getForm($id_or_title, $include = array()) {
        $form = false;
        if (is_string($id_or_title) && !is_numeric($id_or_title))
            $form = get_page_by_title($id_or_title, OBJECT, $this->post_type_name);
        elseif (is_numeric($id_or_title))
            $form = get_post(intval($id_or_title));

        if ($form)
            $id = $form->ID;
        else
            return false;

        $formObj = new stdClass;
        $formObj->form = $form;
        $formObj->fields = $this->getFormCustomFields($id, $include);
        return $formObj;
    }

    public function getUsers() {
//        $users = array();
//
//        $roles = array();
//        global $wp_roles;
//        $user_roles = $wp_roles->roles;
//        $role = array();
//        foreach ($user_roles as $r) {
//            $rrr = strtolower($r['name']);
//            if ($rrr!='administrator')
//            $role[] = $rrr;
//        }        
//        foreach ($roles as $role) :
//            $users_query = new WP_User_Query(array(
//                'fields' => 'all_with_meta',
//                /*'role' => $role,*/
//                'orderby' => 'display_name'
//            ));
//            $results = $users_query->get_results();
//            if ($results)
//                $users = array_merge($users, $results);
//        endforeach;
//
//        return $users;

        return get_users('orderby=nicename');
    }

    public function getFormCustomFields($id, $include = array()) {
        $fieldsraw = get_post_custom(intval($id));
        $fields = array();
        $form_fields = array_merge($include, $this->form_meta_fields);

        $prefix = '/^' . preg_quote($this->prefix, '/') . '/';
        if (isset($fieldsraw) && !empty($fieldsraw))
            foreach ($fieldsraw as $key => $fieldraw) {
                $key = preg_replace($prefix, '', $key);
                if (in_array($key, $form_fields)) {
                    $fields[$key] = $this->unesc_meta_data(maybe_unserialize($fieldraw[0]));
                }
            }
        unset($fieldsraw);

        // change format here and provide defaults also
        $fields = $this->changeFormat($fields);
        return $fields;
    }

    public function getFormCustomField($id, $field) {
        $field_db = $this->prefix . $field;
        $fieldvalue = get_post_meta(intval($id), $field_db, true);
        if (false != $fieldvalue && !empty($fieldvalue)) {
            $fieldvalue = $this->unesc_meta_data(maybe_unserialize($fieldvalue));
        }
        // change format here
        $fields = $this->changeFormat(array($field => $fieldvalue));

        return $fields[$field];
    }

    public function changeFormat($fields) {
        // change format here
        if (isset($fields['form_settings'])) {
            $s = $fields['form_settings'];
            if (!isset($s->form)) {
                if (isset($s->message)) {
                    if (is_string($s->message))
                        $_message = $s->message;
                    else
                        $_message = '';
                } else
                    $_message = '';

                $setts = new stdClass;
                $setts->form = array(
                    'type' => isset($s->form_type) ? $s->form_type : '',
                    'action' => isset($s->form_action) ? $s->form_action : '',
                    'action_page' => isset($s->form_action_page) ? $s->form_action_page : '',
                    'action_message' => $_message,
                    'user_role' => isset($s->user_role) ? $s->user_role : '',
                    'redirect_delay' => isset($s->redirect_delay) ? $s->redirect_delay : 0,
                    'hide_comments' => isset($s->hide_comments) ? $s->hide_comments : 0,
                    'theme' => isset($s->cred_theme_css) ? $s->cred_theme_css : 'minimal',
                    'has_media_button' => isset($s->has_media_button) ? $s->has_media_button : 0,
                    'autogenerate_username_scaffold' => isset($s->autogenerate_username_scaffold) ? $s->autogenerate_username_scaffold : 0,
                    'autogenerate_nickname_scaffold' => isset($s->autogenerate_nickname_scaffold) ? $s->autogenerate_nickname_scaffold : 0,
                    'autogenerate_password_scaffold' => isset($s->autogenerate_password_scaffold) ? $s->autogenerate_password_scaffold : 0,
                    'include_wpml_scaffold' => isset($s->include_wpml_scaffold) ? $s->include_wpml_scaffold : 0,
                    'include_captcha_scaffold' => isset($s->include_captcha_scaffold) ? $s->include_captcha_scaffold : 0
                );
                $setts->post = array(
                    'post_type' => isset($s->post_type) ? $s->post_type : '',
                    'post_status' => isset($s->post_status) ? $s->post_status : ''
                );
                unset($fields['form_settings']);
                $fields['form_settings'] = $setts;
            }
        }

        if (isset($fields['extra'])) {
            // reformat messages
            if (isset($fields['extra']->messages)) {
                foreach ($fields['extra']->messages as $mid => $msg) {
                    if (is_array($msg) && isset($msg['msg'])) {
                        $fields['extra']->messages[$mid] = $msg['msg'];
                    }
                }
            }
        }

        if (isset($fields['notification'])) {
            $nt = (object) $fields['notification'];
            $notts = new stdClass;
            $notts->enable = isset($nt->enable) ? $nt->enable : 0;
            $notts->notifications = isset($nt->notifications) ? $nt->notifications : array();
            foreach ($notts->notifications as $ii => $n) {
                if (isset($n['mail_to_type'])) {
                    $_type = isset($n['mail_to_type']) ? $n['mail_to_type'] : '';
                    $notts->notifications[$ii] = array(
                        'event' => array(
                            'type' => 'form_submit',
                            'post_status' => '',
                            'condition' => array(),
                            'any_all' => ''
                        ),
                        'to' => array(
                            'type' => array(
                                $_type
                            ),
                            'wp_user' => array(
                                'to_type' => 'to',
                                'user' => isset($n['mail_to_user']) ? $n['mail_to_user'] : ''
                            ),
                            'mail_field' => array(
                                'to_type' => 'to',
                                'address_field' => isset($n['mail_to_field']) ? $n['mail_to_field'] : '',
                                'name_field' => '',
                                'lastname_field' => ''
                            ),
                            'user_id_field' => array(
                                'to_type' => 'to',
                                'field_name' => isset($n['mail_to_user_id_field']) ? $n['mail_to_user_id_field'] : ''
                            ),
                            'specific_mail' => array(
                                'address' => isset($n['mail_to_specific']) ? $n['mail_to_specific'] : '',
                            )
                        ),
                        'from' => array(
                            'address' => isset($n['from_addr']) ? $n['from_addr'] : '',
                            'name' => isset($n['from_name']) ? $n['from_name'] : ''
                        ),
                        'mail' => array(
                            'subject' => isset($n['subject']) ? $n['subject'] : '',
                            'body' => isset($n['body']) ? $n['body'] : ''
                        )
                    );
                }

                // make sure a [to][type] key exists and is array, even if empty
                /* $notts->notifications[$ii]=array_merge_recursive($notts->notifications[$ii], array(
                  'to'=>array(
                  'type'=>array()
                  )
                  )); */

                // apply some defaults
                $notts->notifications[$ii] = $this->merge(array(
                    'event' => array(
                        'type' => 'form_submit',
                        'post_status' => 'publish',
                        'condition' => array(
                        ),
                        'any_all' => 'ALL'
                    ),
                    'to' => array(
                        'type' => array(),
                        'wp_user' => array(
                            'to_type' => 'to',
                            'user' => ''
                        ),
                        'mail_field' => array(
                            'to_type' => 'to',
                            'address_field' => '',
                            'name_field' => '',
                            'lastname_field' => ''
                        ),
                        'user_id_field' => array(
                            'to_type' => 'to',
                            'field_name' => ''
                        ),
                        'specific_mail' => array(
                            'address' => ''
                        )
                    ),
                    'from' => array(
                        'address' => '',
                        'name' => ''
                    ),
                    'mail' => array(
                        'subject' => '',
                        'body' => ''
                    )
                        ), $notts->notifications[$ii]);
            }
            unset($fields['notification']);
            $fields['notification'] = $notts;
        }

        // provide some defaults
        $fields = $this->merge(array(
            'form_settings' => (object) array(
                'post' => array(
                    'post_type' => '',
                    'post_status' => ''
                ),
                'form' => array(
                    'type' => '',
                    'action' => '',
                    'action_page' => '',
                    'action_message' => '',
                    'user_role' => '',
                    'redirect_delay' => 0,
                    'hide_comments' => 0,
                    'theme' => 'minimal',
                    'has_media_button' => 0,
                    'autogenerate_username_scaffold' => 1,
                    'autogenerate_nickname_scaffold' => 1,
                    'autogenerate_password_scaffold' => 1,
                    'include_wpml_scaffold' => 0,
                    'include_captcha_scaffold' => 0
                )
            ),
            'extra' => (object) array(
                'css' => '',
                'js' => '',
                'messages' => $this->getDefaultMessages()
            ),
            'notification' => (object) array(
                'enable' => 0,
                'notifications' => array()
            )
                ), $fields);

        return $fields;
    }

    public function deleteForm($id) {
        return !(wp_delete_post($id, true) === false);
    }

    public function saveForm($form, $fields = array()) {
        global $user_ID;

        $new_post = array(
            'ID' => '',
            'post_title' => $form->post_title,
            'post_content' => $form->post_content,
            'post_status' => 'private',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_date' => date('Y-m-d H:i:s'),
            'post_author' => $user_ID,
            'post_type' => $this->post_type_name
                //'post_category' => array(0)
        );
        $post_id = wp_insert_post($new_post);
        $this->addFormCustomFields($post_id, $fields);

        return ($post_id);
    }

    public function updateForm($form, $fields = array()) {
        global $user_ID;

        $up_post = array(
            'ID' => $form->ID,
            'post_title' => $form->post_title,
            'post_content' => $form->post_content,
            'post_status' => 'private',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_date' => date('Y-m-d H:i:s'),
            'post_author' => $user_ID,
            'post_type' => $this->post_type_name
                //'post_category' => array(0)
        );
        $post_id = wp_insert_post($up_post);
        $this->updateFormCustomFields($post_id, $fields);

        return ($post_id);
    }

    public function addFormCustomFields($id, $fields) {
        if (empty($fields) || !is_array($fields))
            return;
        $fields = $this->esc_data($fields);
        foreach ($fields as $meta_key => $meta_value) {
            add_post_meta($id, $this->prefix . $meta_key, $meta_value, false /* $unique */);
        }
    }

    public function updateFormCustomFields($id, $fields) {
        if (empty($fields) || !is_array($fields))
            return;
        $fields = $this->esc_meta_data($fields);
        foreach ($fields as $meta_key => $meta_value) {
            delete_post_meta($id, $this->prefix . $meta_key);
            update_post_meta($id, $this->prefix . $meta_key, $meta_value, false /* $unique */);
        }
    }

    public function updateFormCustomField($id, $field, $value) {
        delete_post_meta($id, $this->prefix . $field);
        update_post_meta($id, $this->prefix . $field, $this->esc_meta_data($value), false /* $unique */);
        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/195822950/comments
        //added postmeta for installer
        if ($_REQUEST['action'] == 'editpost' && isset($_REQUEST['_wp_http_referer'])) {
            update_post_meta($id, '_toolset_edit_last', time(), get_post_meta($id, '_toolset_edit_last', true));
        }
    }

    public function updateFormData($form_data) {
        $post_id = wp_update_post($form_data);
        return ($post_id);
    }

    public function cloneForm($form_id, $cloned_form_title = null) {
        $form = $this->getForm($form_id);
        if ($form) {
            if ($cloned_form_title == null || empty($cloned_form_title))
                $cloned_form_title = $form->form->post_title . ' Copy';
            $form->form->post_title = preg_replace('/[^\w\-_\. ]/', '', $cloned_form_title);
            $form->form->ID = '';
            return $this->saveForm($form->form, $form->fields);
        }
        return false;
    }

    public function getForms($page = 0, $perpage = 10, $with_fields = false) {
        $args = array(
            'numberposts' => intval($perpage),
            'offset' => intval($page) * intval($perpage),
            'category' => 0,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'include' => array(),
            'exclude' => array(),
            'meta_key' => '_cred_form_settings', // prevent drafts
            'post_type' => $this->post_type_name,
            'post_status' => 'private',
            'suppress_filters' => true
        );
        $forms = get_posts($args);

        return $forms;
    }

    public function getAllForms() {
        $args = array(
            'numberposts' => -1,
            'category' => 0,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'include' => array(),
            'exclude' => array(),
            'meta_key' => '_cred_form_settings', // prevent drafts
            'post_type' => $this->post_type_name,
            'post_status' => 'private',
            'suppress_filters' => true
        );
        $forms = get_posts($args);

        return $forms;
    }

    public function getFormsCount() {
        //$auto_draft=__('Auto Draft');
        $sql = '
            SELECT count(p.ID) FROM ' . $this->wpdb->posts . ' as p, ' . $this->wpdb->postmeta . ' as pm 
            WHERE 
                p.ID=pm.post_id
                AND
                pm.meta_key="' . $this->prefix . 'form_settings"
                AND
                p.post_type="' . $this->post_type_name . '" 
                AND 
                p.post_status="private" 
            ORDER BY p.post_date DESC
        ';
        $count = $this->wpdb->get_var($sql);
        return intval($count);
    }

    /* public function array_pluck($key, $array)
      {
      $funct = create_function('$e', 'if (is_array($e) && array_key_exists("'.$key.'",$e)) return $e["'. $key .'"]; elseif (is_object($e) && isset($e->'.$key.')) return $e->'.$key.'; else return null;');
      return array_map($funct, $array);
      } */

    public function getFormsForTable($page, $perpage, $orderby = 'post_title', $order = 'asc') {
        $p = intval($page);
        if ($p <= 0)
            $p = 1;
        $pp = intval($perpage);
        $limit = '';
        if ($pp != -1 && $pp <= 0)
            $pp = 10;
        if ($pp != -1)
            $limit = 'LIMIT ' . ($p - 1) * $pp . ',' . $pp;

        if (!in_array($orderby, array('post_title', 'post_date')))
            $orderby = 'post_title';

        $order = strtoupper($order);
        if (!in_array($order, array('ASC', 'DESC')))
            $order = 'ASC';

        $sql = "
        SELECT p.ID, p.post_title, p.post_name, pm.meta_value as meta FROM {$this->wpdb->posts}  p, {$this->wpdb->postmeta} pm  
        WHERE (
            p.ID=pm.post_id
            AND
            pm.meta_key='{$this->prefix}form_settings'
            AND
            p.post_type='{$this->post_type_name}' 
            AND 
            p.post_status='private'
        ) 
        ORDER BY p.{$orderby} {$order} 
        {$limit}
        ";


        $forms = $this->wpdb->get_results($sql);
        foreach ($forms as $key => $form) {
            $fields = $this->changeFormat(array('form_settings' => maybe_unserialize($forms[$key]->meta)));
            $forms[$key]->meta = $fields['form_settings'];
        }
        return $forms;
    }

    public function getFormsForExport($ids) {
        if ('all' != $ids)
            $ids = implode(',', array_map('intval', $ids));
        $meta_keys = implode(',', $this->getFieldkeys('"', true));

        // AND p.post_status="private"
        if ('all' != $ids)
            $sql1 = '
            SELECT p.* FROM ' . $this->wpdb->posts . ' as p, ' . $this->wpdb->postmeta . ' as pm 
            WHERE 
                p.ID=pm.post_id
                AND
                pm.meta_key="' . $this->prefix . 'form_settings"
                AND
                p.post_type="' . $this->post_type_name . '" 
                AND 
                p.post_status="private" 
                AND p.ID IN (' . $ids . ')
            ORDER BY p.post_date DESC
        ';
        else
            $sql1 = '
            SELECT p.* FROM ' . $this->wpdb->posts . ' as p, ' . $this->wpdb->postmeta . ' as pm 
            WHERE 
                p.ID=pm.post_id
                AND
                pm.meta_key="' . $this->prefix . 'form_settings"
                AND
                p.post_type="' . $this->post_type_name . '" 
                AND 
                p.post_status="private" 
            ORDER BY p.post_date DESC
        ';

        if ('all' != $ids)
            $sql2 = 'SELECT p.ID, pm.meta_key, pm.meta_value FROM ' . $this->wpdb->posts . ' AS p INNER JOIN ' . $this->wpdb->postmeta . ' AS pm ON p.ID=pm.post_id WHERE (p.post_type="' . $this->post_type_name . '" AND p.post_status="private"  ANd p.ID IN (' . $ids . ') AND pm.meta_key IN (' . $meta_keys . '))';
        else
            $sql2 = 'SELECT p.ID, pm.meta_key, pm.meta_value FROM ' . $this->wpdb->posts . ' AS p INNER JOIN ' . $this->wpdb->postmeta . ' AS pm ON p.ID=pm.post_id WHERE (p.post_type="' . $this->post_type_name . '" AND p.post_status="private"  AND pm.meta_key IN (' . $meta_keys . '))';

        $forms = $this->wpdb->get_results($sql1);
        $meta = $this->wpdb->get_results($sql2);
        $prefix = '/^' . preg_quote($this->prefix, '/') . '/';
        foreach ($forms as $key => $form) {
            $forms[$key]->meta = array();
            $forms[$key]->media = $this->getFormAttachedMediaForExport($form->ID);
            foreach ($meta as $m) {
                if ($form->ID == $m->ID) {
                    $meta_key = preg_replace($prefix, '', $m->meta_key);
                    $forms[$key]->meta[$meta_key] = maybe_unserialize($m->meta_value);
                }
            }
            // change format here
            $forms[$key]->meta = $this->changeFormat($forms[$key]->meta);
        }
        unset($meta);

        return $forms;
    }

    public function getFormAttachedMediaForExport($id /* $form */) {
        $att_args = array('post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => intval($id));
        $attachments = get_posts($att_args);
        $media = array();

        //cred_log($attachments);
        if ($attachments) {
            foreach ($attachments as $ii => $attachment) {
                // if media is image mime type
                if (in_array($attachment->post_mime_type, array('image/png', 'image/gif', 'image/jpg', 'image/jpeg'))) {
                    $idata = base64_encode(file_get_contents($attachment->guid));
                    $ihash = sha1($idata);
                    $media[$ii] = array(
                        'ID' => $attachment->ID,
                        'post_title' => $attachment->post_title,
                        'post_content' => $attachment->post_content,
                        'post_excerpt' => $attachment->post_excerpt,
                        'post_status' => $attachment->post_status,
                        'post_type' => $attachment->post_type,
                        'post_mime_type' => $attachment->post_mime_type,
                        'guid' => $attachment->guid,
                        'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
                        'image_data' => $idata,
                        'image_hash' => $ihash,
                        'base_name' => basename($attachment->guid)
                    );
                } else
                    unset($attachments[$ii]);
            }
        }
        unset($attachments);

        return $media;
    }

//=================== GENERAL (CUSTOM) POST HANDLING METHODS ====================================================

    public function getUserMeta($user_id, $meta) {
        static $user_meta = array();

        if (!isset($post_meta[$meta]))
            $user_meta[$meta] = get_user_meta($user_id, $meta, true);

        if (!$user_meta[$meta])
            $user_meta[$meta] = false;

        return $user_meta[$meta];
    }

    public function getPostFields($post_id, $only = null) {
        return $this->getUserFields($post_id, $only);
    }

    public function getUserFields($user_id, $only = null) {
        $fields = CRED_Loader::get('MODEL/UserFields')->getCustomFields();
        foreach ($fields as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $ii => $val_single) {
                    $fields[$key][$ii] = maybe_unserialize(maybe_unserialize($val_single));
                }
            } else
                $fields[$key] = maybe_unserialize($val);
        }
        $intersected = array();
        if (isset($only) && !empty($only)) {
            foreach ($fields as $key => $value) {
                if (isset($value['plugin_type_prefix'])) {
                    $key = $value['plugin_type_prefix'] . $key;
                }
                if (in_array($key, $only)) {
                    $intersected[$key] = $value;
                }
            }
        }
        unset($fields);

        $user = new WP_User($user_id);
        $values = (array) $user->data;
        $values['first_name'] = get_user_meta($user_id, 'first_name', true);
        $values['last_name'] = get_user_meta($user_id, 'last_name', true);
        $values['nickname'] = get_user_meta($user_id, 'nickname', true);

        $new_array = array();
        if (!empty($intersected))
            foreach ($intersected as $key => $val) {
                if (isset($values[$key])) {
                    $new_array[$key] = $values[$key];
                } else {
                    $values[$key] = get_user_meta($user_id, $key, true);
                    if (isset($values[$key]))
                        $new_array[$key] = $values[$key];
                }
            }
        unset($fields);
        unset($user);
        return $new_array;
    }

    public function setAttachedData($user_id, $data) {
        return update_user_meta(intval($user_id), '__cred_user_notification_data', $data); // serialize
    }

    public function removeAttachedData($user_id) {
        return delete_user_meta(intval($user_id), '__cred_user_notification_data');
    }

    public function getAttachedData($user_id) {
        return get_user_meta(intval($user_id), '__cred_user_notification_data', true); // unserialize
    }

    public function deleteUser($user_id, $reassign_user_id = null) {
        $result = wp_delete_user($user_id, $reassign_user_id);
        return ($result !== false);
    }

    public function addUser($userdata, $usermeta, $fieldsInfo, $removed_fields = null) {
        $allowed_tags = $allowed_protocols = array();
        $this->setAllowed($allowed_tags, $allowed_protocols);

        //CHECK Userdata
        //$post->post_title = wp_kses($post->post_title, $allowed_tags, $allowed_protocols);

        if (!isset($userdata['user_role']) || empty($userdata['user_role'])) {
            global $wp_roles;
            $_roles = array_reverse($wp_roles->roles);
            foreach ($_roles as $k => $v) {
                $userdata['user_role'] = array($k);
                break;
            }
        }

        $user_role = is_array($userdata['user_role']) ? $userdata['user_role'] : json_decode($userdata['user_role'], true);
        $user_role = $user_role[0];

        unset($userdata['user_role']);
        unset($userdata['ID']);

        //$user_id = wp_create_user( $userdata['user_login'], $userdata['user_pass'], $userdata['user_email'] );
        $user_id = wp_insert_user($userdata);

        $user = new WP_User($user_id);
        $user->set_role($user_role);

        if (!is_wp_error($user_id)) {            
            if (isset($removed_fields) && is_array($removed_fields)) {
                // remove the fields that need to be removed
                foreach ($removed_fields as $meta_key) {
                    delete_user_meta($user_id, $meta_key);
                }
            }
            $usermeta = $this->esc_data($usermeta);
            foreach ($usermeta as $meta_key => $meta_value) {
                delete_user_meta($user_id, $meta_key);
                if (is_array($meta_value) && !$fieldsInfo[$meta_key]['save_single']) {
                    foreach ($meta_value as $meta_value_single) {
                        $meta_value_single = wp_kses($meta_value_single, $allowed_tags, $allowed_protocols);
                        $meta_value_single = str_replace("&amp;", "&", $meta_value_single);
                        //##########################################################################################
                        add_user_meta($user_id, $meta_key, $meta_value_single, false /* $unique */);
                    }
                } else {
                    $meta_value = wp_kses($meta_value, $allowed_tags, $allowed_protocols);
                    $meta_value = str_replace("&amp;", "&", $meta_value);
                    //##########################################################################################
                    add_user_meta($user_id, $meta_key, $meta_value, false /* $unique */);
                }
            }
        }
        return ($user_id);
    }

    public function updateUser($userdata, $usermeta, $fieldsInfo, $removed_fields = null) {

        $allowed_tags = $allowed_protocols = array();
        $this->setAllowed($allowed_tags, $allowed_protocols);

        //CHECK Userdata
        //$post->post_title = wp_kses($post->post_title, $allowed_tags, $allowed_protocols);
        $user_role = $userdata['user_role'];
        unset($userdata['user_role']);

        $user_id = wp_update_user($userdata);

        //$user = new WP_User($user_id);
        //$user->set_role($user_role);
        if (!is_wp_error($user_id)) {
            if (isset($removed_fields) && is_array($removed_fields)) {
                // remove the fields that need to be removed
                foreach ($removed_fields as $meta_key) {
                    delete_user_meta($user_id, $meta_key);
                }
            }
            $usermeta = $this->esc_data($usermeta);
            foreach ($usermeta as $meta_key => $meta_value) {
                delete_user_meta($user_id, $meta_key);
                if (is_array($meta_value) && !$fieldsInfo[$meta_key]['save_single']) {
                    foreach ($meta_value as $meta_value_single) {
                        $meta_value_single = wp_kses($meta_value_single, $allowed_tags, $allowed_protocols);
                        $meta_value_single = str_replace("&amp;", "&", $meta_value_single);
                        //##########################################################################################
                        add_user_meta($user_id, $meta_key, $meta_value_single, false /* $unique */);
                    }
                } else {
                    $meta_value = wp_kses($meta_value, $allowed_tags, $allowed_protocols);
                    $meta_value = str_replace("&amp;", "&", $meta_value);
                    //##########################################################################################
                    add_user_meta($user_id, $meta_key, $meta_value, false /* $unique */);
                }
            }
        }
        return ($user_id);
    }

    private function setAllowed(&$allowed_tags, &$allowed_protocols) {
        $__allowed_tags = wp_kses_allowed_html('post');
        $__allowed_protocols = array('http', 'https', 'mailto');
        $settings_model = CRED_Loader::get('MODEL/Settings');
        $settings = $settings_model->getSettings();
        $allowed_tags = isset($settings['allowed_tags']) ? $settings['allowed_tags'] : $__allowed_tags;
        foreach ($__allowed_tags as $key => $value) {
            if (!isset($allowed_tags[$key])) {
                unset($__allowed_tags[$key]);
            }
        }
        $allowed_tags = $__allowed_tags;
        $allowed_protocols = $__allowed_protocols;
    }

}
