<?php

/**
 *
 *   CRED forms model
 *
 *   (uses custom posts and fields to store form data)
 *
 * */
final class CRED_Forms_Model extends CRED_Abstract_Model implements CRED_Singleton {

    private $post_type_name = '';
    private $form_meta_fields = array('form_settings', 'wizard', 'post_expiration', 'notification', 'extra');
    private $prefix = '_cred_';

    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct();

        $this->post_type_name = CRED_FORMS_CUSTOM_POST_NAME;
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
                'name' => __('CRED Post Forms', 'wp-cred'),
                'singular_name' => __('CRED Post Form', 'wp-cred'),
                'add_new' => __('Add New', 'wp-cred'),
                'add_new_item' => __('Add New CRED Post Form', 'wp-cred'),
                'edit_item' => __('Edit CRED Post Form', 'wp-cred'),
                'new_item' => __('New CRED Post Form', 'wp-cred'),
                'view_item' => __('View CRED Post Form', 'wp-cred'),
                'search_items' => __('Search CRED Post Forms', 'wp-cred'),
                'not_found' => __('No forms found', 'wp-cred'),
                'not_found_in_trash' => __('No form found in Trash', 'wp-cred'),
                'parent_item_colon' => '',
                'menu_name' => 'CRED Post Forms'
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
                'cred_message_user_saved' => 'User ' . __('Saved', 'wp-cred'),
                'cred_message_post_saved' => 'Post ' . __('Saved', 'wp-cred'),
                //'cred_message_post_not_saved'=>'Post Not Saved',
                'cred_message_post_not_saved_singular' => __('The post was not saved because of the following problem:', 'wp-cred'),
                'cred_message_post_not_saved_plural' => __('The post was not saved because of the following %NN problems:', 'wp-cred'),
                //'cred_message_notification_was_sent'=>'Notification was sent',
                //'cred_message_notification_failed'=>'Notification failed',
                'cred_message_invalid_form_submission' => 'Invalid Form Submission (nonce failure)',
                'cred_message_no_data_submitted' => 'Invalid Form Submission (maybe a file has a size greater than allowed)',
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
                'cred_message_post_saved' => __('Post saved Message', 'wp-cred'),
                //'cred_message_post_not_saved'=>__('Post not saved message','wp-cred'),
                'cred_message_post_not_saved_singular' => __('Post not saved message (one problem)', 'wp-cred'),
                'cred_message_post_not_saved_plural' => __('Post not saved message (several problems)', 'wp-cred'),
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

    public function getFormCustomFields($id, $include = array()) {
        $fieldsraw = get_post_custom(intval($id));
        $fields = array();
        $form_fields = array_merge($include, $this->form_meta_fields);

        $prefix = '/^' . preg_quote($this->prefix, '/') . '/';
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
                    'redirect_delay' => isset($s->redirect_delay) ? $s->redirect_delay : 0,
                    'hide_comments' => isset($s->hide_comments) ? $s->hide_comments : 0,
                    'theme' => isset($s->cred_theme_css) ? $s->cred_theme_css : 'minimal',
                    'has_media_button' => isset($s->has_media_button) ? $s->has_media_button : 0,
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
                    'redirect_delay' => 0,
                    'hide_comments' => 0,
                    'theme' => 'minimal',
                    'has_media_button' => 0,
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

    public function getPostMeta($post_id, $meta) {
        static $post_meta = array();

        if (!isset($post_meta[$meta]))
            $post_meta[$meta] = get_post_meta($post_id, $meta, true);

        if (!$post_meta[$meta])
            $post_meta[$meta] = false;

        return $post_meta[$meta];
    }

    public function setAttachedData($post_id, $data) {
        return update_post_meta(intval($post_id), '__cred_notification_data', $data); // serialize
    }

    public function removeAttachedData($post_id) {
        return delete_post_meta(intval($post_id), '__cred_notification_data');
    }

    public function getAttachedData($post_id) {
        return get_post_meta(intval($post_id), '__cred_notification_data', true); // unserialize
    }

    public function deletePost($post_id, $force_delete = true) {
        if ($force_delete)
            $result = wp_delete_post($post_id, $force_delete);
        else
            $result = wp_trash_post($post_id);
        return ($result !== false);
    }

    public function getPostFields($post_id, $only = null) {
        $fields = get_post_custom($post_id);
        foreach ($fields as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $ii => $val_single) {
                    $fields[$key][$ii] = maybe_unserialize(maybe_unserialize($val_single));
                }
            } else
                $fields[$key] = maybe_unserialize($val);
        }
        if (null !== $only && empty($only))
            $fields = array();
        elseif ($only && is_array($only) && !empty($only))
            $fields = array_intersect_key($fields, array_flip($only));

        return $fields;
    }

    public function getPostTaxonomies($post) {
        $all_taxonomies = get_taxonomies(array(
            'public' => true,
            '_builtin' => false,
                //'object_type'=>array($post_type_orig)
                ), 'objects', 'or');
        $taxonomies = array();
        foreach ($all_taxonomies as $taxonomy) {
            if (!in_array($post->post_type, $taxonomy->object_type))
                continue;
            if (in_array($taxonomy->name, array('post_format')))
                continue;

            $key = $taxonomy->name;
            $taxonomies[$key] = array(
                'label' => $taxonomy->label,
                'name' => $taxonomy->name,
                'hierarchical' => $taxonomy->hierarchical,
            );
            $taxonomies[$key]['terms'] = $this->buildTerms(wp_get_post_terms($post->ID, $taxonomy->name, array("fields" => "all")));
            /* if ($taxonomy->hierarchical)
              {
              $taxonomies[$key]['all']= $this->buildTerms(get_terms($taxonomy->name,array('hide_empty'=>0,'fields'=>'all')));

              }
              else
              {
              $taxonomies[$key]['most_popular']= $this->buildTerms(get_terms($taxonomy->name,array('number'=>5,'order_by'=>'count','fields'=>'all')));
              } */
        }
        unset($all_taxonomies);
        return $taxonomies;
    }

    public function getPost($post_id) {
        $post_id = intval($post_id);

        // get post
        $post = get_post($post_id);

        // get post meta fields
        $fields = $this->getPostFields($post_id);

        // get post type taxonomies
        $taxonomies = $this->getPostTaxonomies($post);

        // extra fields
        $extra = array(
            'featured_img_html' => get_the_post_thumbnail($post_id, 'thumbnail' /* , $attr */)
        );
        return array($post, $fields, $taxonomies, $extra);
    }

    private function buildTerms($obj_terms) {
        $tax_terms = array();
        foreach ($obj_terms as $term) {
            $tax_terms[] = array(
                'name' => $term->name,
                'count' => $term->count,
                'parent' => $term->parent,
                'term_taxonomy_id' => $term->term_taxonomy_id,
                'term_id' => $term->term_id
            );
        }
        return $tax_terms;
    }

    public function addPost($post, $fields, $taxonomies = null) {
        global $user_ID;
        $allowed_tags = $allowed_protocols = array();
        $this->setAllowed($allowed_tags, $allowed_protocols);

        if (isset($post->post_title))
            $post->post_title = wp_kses($post->post_title, $allowed_tags, $allowed_protocols);
        if (isset($post->post_content))
            $post->post_content = wp_kses($post->post_content, $allowed_tags, $allowed_protocols);
        if (isset($post->post_excerpt))
            $post->post_excerpt = wp_kses($post->post_excerpt, $allowed_tags, $allowed_protocols);

        $up_post = array(
            'ID' => $post->ID,
            'post_date' => date('Y-m-d H:i:s', current_time('timestamp')),
            'post_type' => $post->post_type,
            'post_category' => array(0)
        );

        if (isset($post->post_author))
            $up_post['post_author'] = $post->post_author;
        if (isset($post->post_title))
            $up_post['post_title'] = $post->post_title;
        if (isset($post->post_content))
            $up_post['post_content'] = $post->post_content;
        if (isset($post->post_excerpt))
            $up_post['post_excerpt'] = $post->post_excerpt;
        if (isset($post->post_status))
            $up_post['post_status'] = $post->post_status;
        if (isset($post->post_parent))
            $up_post['post_parent'] = $post->post_parent;
        if (isset($post->post_type))
            $up_post['post_type'] = $post->post_type;

        $post_id = wp_insert_post($up_post, true);

        if (!is_wp_error($post_id)) {
            if (isset($fields['removed']) && is_array($fields['removed'])) {
                // remove the fields that need to be removed
                foreach ($fields['removed'] as $meta_key) {
                    delete_post_meta($post_id, $meta_key);
                }
            }
            $fields['fields'] = $this->esc_data($fields['fields']);
            foreach ($fields['fields'] as $meta_key => $meta_value) {
                if (is_array($meta_value) && !$fields['info'][$meta_key]['save_single']) {
                    foreach ($meta_value as $meta_value_single) {
                        $meta_value_single = wp_kses($meta_value_single, $allowed_tags, $allowed_protocols);
                        add_post_meta($post_id, $meta_key, $meta_value_single, false /* $unique */);
                    }
                } else {
                    $meta_value = wp_kses($meta_value, $allowed_tags, $allowed_protocols);
                    add_post_meta($post_id, $meta_key, $meta_value, false /* $unique */);
                }
            }

            if ($taxonomies) {
                $taxonomies = $this->esc_data($taxonomies);
                foreach ($taxonomies['flat'] as $tax) {
                    // attach them to post
                    wp_set_post_terms($post_id, $tax['add'], $tax['name'], false);
                }

                foreach ($taxonomies['hierarchical'] as $tax) {
                    foreach ($tax['add_new'] as $ii => $addnew) {
                        /**
                         * if numeric parent, then check is there such a taxonomy
                         */
                        if (is_numeric($addnew['parent']) && is_object(get_term($addnew['parent'], $tax['name']))) {
                            $pid = (int) $addnew['parent'];
                            if ($pid < 0)
                                $pid = 0;

                            $result = wp_insert_term($addnew['term'], $tax['name'], array('parent' => $pid));
                            if (!is_wp_error($result)) {
                                $tax['add_new'][$ii]['id'] = $result['term_id'];
                                $ind = array_search($addnew['term'], $tax['terms']);
                                if ($ind !== false)
                                    $tax['terms'][$ind] = $result['term_id'];
                            }
                        }
                        else {
                            $par_id = false;
                            foreach ($tax['add_new'] as $ii2 => $addnew2) {
                                if ($addnew['parent'] == $addnew2['term'] && isset($addnew2['id'])) {
                                    $par_id = $addnew2['id'];
                                    break;
                                }
                            }
                            if ($par_id !== false) {
                                $pid = (int) $par_id;
                                if ($pid < 0)
                                    $pid = 0;

                                $result = wp_insert_term($addnew['term'], $tax['name'], array('parent' => $pid));
                            } else
                                $result = wp_insert_term($addnew['term'], $tax['name'], array('parent' => 0));

                            if (!is_wp_error($result)) {
                                $tax['add_new'][$ii]['id'] = $result['term_id'];
                                $ind = array_search($addnew['term'], $tax['terms']);
                                if ($ind !== false)
                                    $tax['terms'][$ind] = $result['term_id'];
                            }
                        }
                        delete_option($tax['name'] . "_children"); // clear the cache
                    }
                    // attach them to post
                    wp_set_post_terms($post_id, $tax['terms'], $tax['name'], false);
                }
            }
        }
        return ($post_id);
    }

    private function delete_post_taxonomies($post) {
        $taxonomies = $this->getPostTaxonomies($post);

        foreach ($taxonomies as $taxonomy) {
            //Delete all terms only if taxonomy does exist on frontend
            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/187668009/comments
            $todelete = "new_tax_text_" . $taxonomy['name'];
            if (!isset($_POST[$todelete]))
                continue;
            //########################################################

            if (count($taxonomy['terms']) > 0) {
                $delete = array();

                foreach ($taxonomy['terms'] as $terms) {
                    $delete[] = $terms['term_id'];
                }
                wp_remove_object_terms($post->ID, $delete, $taxonomy['name']);
            }
        }
        return $taxonomies;
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

    public function updatePost($post, $fields, $taxonomies = null) {
        global $user_ID;
        $allowed_tags = $allowed_protocols = array();
        $this->setAllowed($allowed_tags, $allowed_protocols);

        if (isset($post->post_title))
            $post->post_title = wp_kses($post->post_title, $allowed_tags, $allowed_protocols);
        if (isset($post->post_content))
            $post->post_content = wp_kses($post->post_content, $allowed_tags, $allowed_protocols);
        if (isset($post->post_excerpt))
            $post->post_excerpt = wp_kses($post->post_excerpt, $allowed_tags, $allowed_protocols);

        $post_id = $post->ID;
        $up_post = array(
            'ID' => $post->ID,
            //'post_author' => $user_ID,
            'post_type' => $post->post_type
        );
        if (isset($post->post_author))
            $up_post['post_author'] = $post->post_author;
        if (isset($post->post_status))
            $up_post['post_status'] = $post->post_status;
        if (isset($post->post_title))
            $up_post['post_title'] = $post->post_title;
        if (isset($post->post_content))
            $up_post['post_content'] = $post->post_content;
        if (isset($post->post_excerpt))
            $up_post['post_excerpt'] = $post->post_excerpt;
        if (isset($post->post_parent))
            $up_post['post_parent'] = $post->post_parent;

        wp_update_post($up_post);

        if (isset($fields['removed']) && is_array($fields['removed'])) {
            // remove the fields that need to be removed
            foreach ($fields['removed'] as $meta_key) {
                delete_post_meta($post_id, $meta_key);
            }
        }
        $fields['fields'] = $this->esc_data($fields['fields']);
        foreach ($fields['fields'] as $meta_key => $meta_value) {
            delete_post_meta($post_id, $meta_key);
            if (is_array($meta_value) && !$fields['info'][$meta_key]['save_single']) {
                foreach ($meta_value as $meta_value_single) {
                    $meta_value_single = wp_kses($meta_value_single, $allowed_tags, $allowed_protocols);
                    //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/195693727/comments
                    //avoid & to be replaced with &amp;
                    $meta_value_single = str_replace("&amp;", "&", $meta_value_single);
                    //##########################################################################################
                    add_post_meta($post_id, $meta_key, $meta_value_single, false /* $unique */);
                }
            } else {
                $meta_value = wp_kses($meta_value, $allowed_tags, $allowed_protocols);
                //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/195693727/comments
                //avoid & to be replaced with &amp;
                $meta_value = str_replace("&amp;", "&", $meta_value);
                //##########################################################################################
                add_post_meta($post_id, $meta_key, $meta_value, false /* $unique */);
            }
        }

        if ($taxonomies) {
            $post_taxonomies = $this->delete_post_taxonomies($post);

            $taxonomies = $this->esc_data($taxonomies);
            foreach ($taxonomies['flat'] as $tax) {
                $old_terms = wp_get_post_terms($post_id, $tax['name'], array("fields" => "names"));
                // remove deleted terms
                $new_terms = (!empty($old_terms) && (isset($tax['remove']) && !empty($tax['remove']))) ? array_diff($old_terms, $tax['remove']) : array();
                // add new terms
                $new_terms = (!empty($new_terms)) ? array_merge($new_terms, $tax['add']) : $tax['add'];
                // attach them to post
                wp_set_post_terms($post_id, $new_terms, $tax['name'], false);
            }

            if (!empty($taxonomies['hierarchical'])) {
                foreach ($taxonomies['hierarchical'] as $tax) {
                    foreach ($tax['add_new'] as $ii => $addnew) {
                        $_gterms = get_term($addnew['parent'], $tax['name']);
                        if (is_numeric($addnew['parent']) && is_object($_gterms)) {
                            $pid = (int) $addnew['parent'];
                            if ($pid < 0)
                                $pid = 0;
                            $result = wp_insert_term($addnew['term'], $tax['name'], array('parent' => $pid));
                            if (!is_wp_error($result)) {
                                $tax['add_new'][$ii]['id'] = $result['term_id'];
                                $ind = array_search($addnew['term'], $tax['terms']);
                                if ($ind !== false)
                                    $tax['terms'][$ind] = $result['term_id'];
                            } else {
                                continue;
                            }
                        } else {
                            $par_id = false;
                            foreach ($tax['add_new'] as $ii2 => $addnew2) {
                                if ($addnew['parent'] == $addnew2['term'] && isset($addnew2['id'])) {
                                    $par_id = $addnew2['id'];
                                    break;
                                }
                            }
                            if ($par_id !== false) {
                                $pid = (int) $par_id;
                                if ($pid < 0)
                                    $pid = 0;

                                $result = wp_insert_term($addnew['term'], $tax['name'], array('parent' => $pid));
                            } else
                                $result = wp_insert_term($addnew['term'], $tax['name'], array('parent' => 0));

                            if (!is_wp_error($result)) {
                                $tax['add_new'][$ii]['id'] = $result['term_id'];
                                $ind = array_search($addnew['term'], $tax['terms']);
                                if ($ind !== false)
                                    $tax['terms'][$ind] = $result['term_id'];
                            } else {
                                continue;
                            }
                        }
                    }
                    // attach them to post
                    wp_set_post_terms($post_id, $tax['terms'], $tax['name'], false);
                }
            } else {
                //Fixed set uncategorized
                //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/195034694/comments
                //check if category is a term of this post
                if (isset($post_taxonomies['category']))
                    wp_set_post_categories($post_id, array(1));
            }
        }

        return ($post_id);
    }

}
