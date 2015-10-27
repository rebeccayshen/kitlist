<?php

final class CRED_Forms_Controller extends CRED_Abstract_Controller {

    public function testNotification() {
        if (
                isset($_POST['cred_form_id']) &&
                isset($_POST['cred_test_notification_data'])
        //&& verify nonce
        ) {
            $notification = $_POST['cred_test_notification_data'];
            $form_id = intval($_POST['cred_form_id']);
            CRED_Loader::load('CLASS/Notification_Manager');
            $results = CRED_Notification_Manager::sendTestNotification($form_id, $notification);
            echo json_encode($results);
            die();
        }
        echo json_encode(array('error' => 'not allowed'));
        die();
    }

    public function suggestUserMail($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        global $wpdb;

        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/187413931/comments
        $user = esc_sql(cred_wrap_esc_like($post['user']));
        $sql = "SELECT user_nicename AS label, user_email AS value FROM {$wpdb->users} WHERE user_nicename LIKE '%$user%' ORDER BY user_email,user_nicename LIMIT 0, 100";
        $results = $wpdb->get_results($sql);

        echo json_encode($results);
    }

    public function updateFormFields($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        $form_id = intval($post['form_id']);
        $fields = $post['fields'];
        $fm = CRED_Loader::get('MODEL/Forms');
        $fm->updateFormCustomFields($form_id, $fields);

        echo json_encode(true);
        die();
    }

    public function updateFormField($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (!isset($post['_wpnonce']) ||
                !wp_verify_nonce($post['_wpnonce'], '_cred_wpnonce')) {
            echo json_encode("wpnonce error");
            die();
        }

        if (!isset($post['form_id'])) {
            echo json_encode(false);
            die();
        }

        $form_id = intval($post['form_id']);
        $field = sanitize_text_field($post['field']);
        $value = sanitize_text_field($post['value']);
        $fm = CRED_Loader::get('MODEL/Forms');
        $fm->updateFormCustomField($form_id, $field, $value);

        echo json_encode(true);
        die();
    }

    public function getPostFields($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (!isset($post['_wpnonce']) || !wp_verify_nonce($post['_wpnonce'], '_cred_wpnonce')) {
            echo "wpnonce error";
            die();
        }

        if (!isset($post['post_type']))
            die();

        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196173458/comments
        //Security Fix
        $post_type = sanitize_text_field($post['post_type']);

        $fields_model = CRED_Loader::get('MODEL/Fields');
        $fields_all = $fields_model->getFields($post_type);

        $settings_model = CRED_Loader::get('MODEL/Settings');
        $settings = $settings_model->getSettings();
        $publickey = $settings['recaptcha']['public_key'];
        $privatekey = $settings['recaptcha']['private_key'];

        $fields_all['extra_fields']['recaptcha']['public_key'] = $publickey;
        $fields_all['extra_fields']['recaptcha']['private_key'] = $privatekey;

        echo json_encode($fields_all);
        die();
    }

    public function getFormFields($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (!isset($post['form_id'])) {
            die();
        }
        $form_id = intval($post['form_id']);
        $fm = CRED_Loader::get('MODEL/Forms');
        $fields = $fm->getFormCustomFields($form_id);

        echo json_encode($fields);
        die();
    }

    public function getFormField($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (!isset($post['form_id'])) {
            die();
        }
        $form_id = intval($post['form_id']);
        $field = sanitize_text_field($post['field']);
        $fm = CRED_Loader::get('MODEL/Forms');
        $value = $fm->getFormCustomField($form_id, $field);

        echo json_encode($value);
        die();
    }

    // export forms to XML and download
    public function exportForm($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (isset($get['form']) && isset($get['_wpnonce'])) {
            if (wp_verify_nonce($get['_wpnonce'], 'cred-export-' . $get['form'])) {
                CRED_Loader::load('CLASS/XML_Processor');
                //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196173458/comments
                //Security Fix added validate_file and sanitize                
                $filename = isset($get['filename']) && validate_file($get['filename']) ? urldecode($get['filename']) : '';
                if (isset($get['type']) && $get['type'] == 'user')
                    CRED_XML_Processor::exportUsersToXML(array($get['form']), isset($get['ajax']), $filename);
                else
                    CRED_XML_Processor::exportToXML(array($get['form']), isset($get['ajax']), $filename);
                die();
            }
        }
        die();
    }

    public function exportSelected($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (isset($_REQUEST['checked']) && is_array($_REQUEST['checked'])) {
            check_admin_referer('cred-bulk-selected-action', 'cred-bulk-selected-field');
            CRED_Loader::load('CLASS/XML_Processor');
            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196173458/comments
            //Security Fix added validate_file and sanitize            
            $filename = isset($_REQUEST['filename']) && validate_file($_REQUEST['filename']) ? urldecode($_REQUEST['filename']) : '';
            if (isset($get['type']) && $get['type'] == 'user')
                CRED_XML_Processor::exportUsersToXML((array) $_REQUEST['checked'], isset($get['ajax']), $filename);
            else
                CRED_XML_Processor::exportToXML((array) $_REQUEST['checked'], isset($get['ajax']), $filename);
            die();
        }
        die();
    }

    public function exportAll($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (isset($get['all']) && isset($get['_wpnonce'])) {
            if (wp_verify_nonce($get['_wpnonce'], 'cred-export-all')) {
                CRED_Loader::load('CLASS/XML_Processor');
                //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196173458/comments
                //Security Fix added validate_file and sanitize
                $filename = isset($get['filename']) && validate_file($get['filename']) ? urldecode($get['filename']) : '';
                if (isset($get['type']) && $get['type'] == 'user')
                    CRED_XML_Processor::exportUsersToXML('all', isset($get['ajax']), $filename);
                else
                    CRED_XML_Processor::exportToXML('all', isset($get['ajax']), $filename);
                die();
            }
        }
        die();
    }

    /**
     * getUserFields
     * @param type $get
     * @param type $post
     */
    public function getUserFields($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (!isset($post['_wpnonce']) || !wp_verify_nonce($post['_wpnonce'], '_cred_wpnonce')) {
            echo "wpnonce error";
            die();
        }

        $autogenerate = array(
            'username' => isset($post['ag_uname']) ? $post['ag_uname'] : 1,
            'nickname' => isset($post['ag_nname']) ? $post['ag_nname'] : 1,
            'password' => isset($post['ag_pass']) ? $post['ag_pass'] : 1);

        $role = isset($post['role']) ? $post['role'] : "";
        
        $fields_model = CRED_Loader::get('MODEL/UserFields');
        $fields_all = $fields_model->getFields($autogenerate, $role);

        $settings_model = CRED_Loader::get('MODEL/Settings');
        $settings = $settings_model->getSettings();
        $publickey = $settings['recaptcha']['public_key'];
        $privatekey = $settings['recaptcha']['private_key'];

        $fields_all['extra_fields']['recaptcha']['public_key'] = $publickey;
        $fields_all['extra_fields']['recaptcha']['private_key'] = $privatekey;

        echo json_encode($fields_all);
        die();
    }

}
