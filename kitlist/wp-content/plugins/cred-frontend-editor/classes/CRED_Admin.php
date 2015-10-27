<?php

include "CRED_Admin_Helper.php";

final class CRED_Admin {

    public static function initAdmin() {
        global $wp_version, $post;

        // add plugin menus
        // setup js, css assets
        CRED_Admin_Helper::setupAdmin();

        CRED_CRED::media();

        CRED_CRED::setFormsAndButtons();

        //TODO: removing before release this new cred embedded version
        //http://wp.localhost:8080/wp-admin/admin.php?page=cred-embedded&cred_id=601
        @add_action('admin_menu', array(CRED_CRED, 'admin_menu'), 20);

        //WATCHOUT: remove custom meta boxes from cred forms (to avoid any problems)
        // add custom meta boxes for cred forms
        add_action('add_meta_boxes_' . CRED_FORMS_CUSTOM_POST_NAME, array(__CLASS__, 'addMetaBoxes'), 20, 1);
        add_action('add_meta_boxes_' . CRED_USER_FORMS_CUSTOM_POST_NAME, array(__CLASS__, 'addMetaBoxes2'), 20, 1);

        // save custom fields of cred forms
        add_action('save_post', array(__CLASS__, 'saveFormCustomFields'), 10, 2);
        // IMPORTANT: drafts should now be left with post_status=draft, maybe show up because of previous versions
        add_filter('wp_insert_post_data', array(__CLASS__, 'forcePrivateforForms'));

        // Remove the Distraction Free Writing button from CRED editors
        add_filter('wp_editor_expand', array(__CLASS__, 'disable_wp_editor_expand_for_cred_forms'), 99, 2);
    }

    public static function forcePrivateforForms($post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post;

        if (CRED_FORMS_CUSTOM_POST_NAME != $post['post_type'] &&
                CRED_USER_FORMS_CUSTOM_POST_NAME != $post['post_type'])
            return $post;

        if (isset($post['ID']) && !current_user_can('edit_post', $post['ID']))
            return $post;

        if (isset($post['ID']) && wp_is_post_revision($post['ID']))
            return $post;

        if ('auto-draft' == $post['post_status'])
            return $post;

        $post['post_status'] = 'private';
        return $post;
    }

    public static function disable_wp_editor_expand_for_cred_forms($return, $post_type = 'post') {
        if (
                $post_type == CRED_FORMS_CUSTOM_POST_NAME || $post_type == CRED_USER_FORMS_CUSTOM_POST_NAME
        ) {
            $return = false;
        }
        return $return;
    }

    // when form is submitted from admin, save the custom fields which describe the form configuration to DB
    public static function saveFormCustomFields($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        if (wp_is_post_revision($post_id))
            return;

        if (CRED_FORMS_CUSTOM_POST_NAME != $post->post_type &&
                CRED_USER_FORMS_CUSTOM_POST_NAME != $post->post_type)
            return;

        if (!current_user_can('edit_post', $post_id))
            return;

        // hook not called from admin edit page, return
        if (empty($_POST) || !isset($_POST['cred-admin-post-page-field']) || !wp_verify_nonce($_POST['cred-admin-post-page-field'], 'cred-admin-post-page-action'))
            return;

        if (isset($_POST['_cred']) && is_array($_POST['_cred']) && !empty($_POST['_cred'])) {
            // new format
            if (CRED_FORMS_CUSTOM_POST_NAME == $post->post_type) {
                $model = CRED_Loader::get('MODEL/Forms');
                $add_merge = array(
                    'hide_comments' => 0,
                    'has_media_button' => 0,
                    'action_message' => ''
                );
            }

            if (CRED_USER_FORMS_CUSTOM_POST_NAME == $post->post_type) {
                $model = CRED_Loader::get('MODEL/UserForms');
                if (isset($_POST['_cred']['form']['user_role'])) {
                    $_POST['_cred']['form']['user_role'] = json_encode($_POST['_cred']['form']['user_role']);
                }
                $add_merge = array(
                    'hide_comments' => 0,
                    'has_media_button' => 0,
                    'action_message' => '',
                    'autogenerate_username_scaffold' => isset($_POST['_cred']['form']['autogenerate_username_scaffold']) ? 1 : 0,
                    'autogenerate_nickname_scaffold' => isset($_POST['_cred']['form']['autogenerate_nickname_scaffold']) ? 1 : 0,
                    'autogenerate_password_scaffold' => isset($_POST['_cred']['form']['autogenerate_password_scaffold']) ? 1 : 0,
                );
            }

            // settings (form, post, actions, messages, css etc..)
            $settings = new stdClass;
            $settings->form = isset($_POST['_cred']['form']) ? $_POST['_cred']['form'] : array();
            $settings->post = isset($_POST['_cred']['post']) ? $_POST['_cred']['post'] : array();
            $settings->form = CRED_Helper::mergeArrays($add_merge, $settings->form);

            // notifications
            $notification = new stdClass;
            $notification->notifications = array();
            // normalize order of notifications using array_values
            $notification->notifications = isset($_POST['_cred']['notification']['notifications']) ? array_values($_POST['_cred']['notification']['notifications']) : array();
            //we have notifications allways enabled
            //$notification->enable=isset($_POST['_cred']['notification']['enable'])?1:0;
            $notification->enable = 1;
            foreach ($notification->notifications as $ii => $nott) {
                if (isset($nott['event']['condition']) && is_array($nott['event']['condition'])) {
                    // normalize order
                    $notification->notifications[$ii]['event']['condition'] = array_values($notification->notifications[$ii]['event']['condition']);
                    $notification->notifications[$ii]['event']['condition'] = CRED_Helper::applyDefaults($notification->notifications[$ii]['event']['condition'], array(
                                'field' => '',
                                'op' => '',
                                'value' => '',
                                'only_if_changed' => 0
                    ));
                } else {
                    $notification->notifications[$ii]['event']['condition'] = array();
                }
            }
            // extra            
            $__allowed_tags = wp_kses_allowed_html('post');
            $__allowed_protocols = array('http', 'https', 'mailto');
            $allowed_tags = $__allowed_tags;
            $allowed_protocols = $__allowed_protocols;
            $extra_js = isset($_POST['_cred']['extra']['js']) ? $_POST['_cred']['extra']['js'] : '';
            $extra_css = isset($_POST['_cred']['extra']['css']) ? $_POST['_cred']['extra']['css'] : '';
            if (!empty($extra_js))
                $extra_js = wp_kses($extra_js, $allowed_tags, $allowed_protocols);
            if (!empty($extra_css))
                $extra_css = wp_kses($extra_css, $allowed_tags, $allowed_protocols);

            $messages = $model->getDefaultMessages();
            $extra = new stdClass;
            $extra->css = $extra_css;
            $extra->js = $extra_js;
            $extra->messages = (isset($_POST['_cred']['extra']['messages'])) ? $_POST['_cred']['extra']['messages'] : $model->getDefaultMessages();

            // update
            $model->updateFormCustomFields($post_id, array(
                'form_settings' => $settings,
                'notification' => $notification,
                'extra' => $extra
            ));

            // wizard
            if (isset($_POST['_cred']['wizard']))
                $model->updateFormCustomField($post_id, 'wizard', intval($_POST['_cred']['wizard']));

            // validation
            if (isset($_POST['_cred']['validation']))
                $model->updateFormCustomField($post_id, 'validation', $_POST['_cred']['validation']);
            else
                $model->updateFormCustomField($post_id, 'validation', array('success' => 1));

            // allow 3rd-party to do its own stuff on CRED form save
            do_action('cred_admin_save_form', $post_id, $post);

            // localize form with WPML
            //THIS code make notification on cred to be lost
            /* $data=CRED_Helper::localizeFormOnSave(array(
              'post'=>$post,
              'notification'=>$notification,
              'message'=>$settings->form['action_message'],
              'messages'=>$extra->messages
              ));
              $model->updateFormCustomField($post_id, 'notification', $data['notification']); */
            CRED_Helper::localizeFormOnSave(array(
                'post' => $post,
                'notification' => $notification,
                'message' => $settings->form['action_message'],
                'messages' => $extra->messages
            ));
        }
    }

    // add meta boxes in admin pages which manipulate forms
    public static function addMetaBoxes($form) {
        global $pagenow;

        if (CRED_FORMS_CUSTOM_POST_NAME == $form->post_type) {
            $model = CRED_Loader::get('MODEL/Forms');
            $form_fields = $model->getFormCustomFields($form->ID, array('form_settings', 'notification', 'extra'));

            // add cred related classes to our metaboxes
            $metaboxes = array(
                // form type meta box
                'credformtypediv' => array(
                    'title' => __('Form Settings', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addFormSettingsMetaBox'),
                    'post_type' => NULL,
                    'context' => 'normal',
                    'priority' => 'high',
                    'callback_args' => $form_fields),
                // post type meta box
                'credposttypediv' => array(
                    'title' => __('Post Type Settings', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addPostTypeMetaBox'),
                    'post_type' => NULL,
                    'context' => 'normal',
                    'priority' => 'high',
                    'callback_args' => $form_fields),
                // content meta box to wrap rich editor, acts as placeholder
                'credformcontentdiv' => array(
                    'title' => __('Form Content', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addFormContentMetaBox'),
                    'post_type' => NULL,
                    'context' => 'normal',
                    'priority' => 'high',
                    'callback_args' => array()),
                // extra meta box (css, js) (placed inside editor meta box)
                'credextradiv' => array(
                    'title' => __('CSS and Javascript for this form', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addExtraAssetsMetaBox'),
                    'post_type' => NULL,
                    'context' => 'normal',
                    'priority' => 'high',
                    'callback_args' => $form_fields),
                // email notification meta box
                'crednotificationdiv' => array(
                    'title' => __('Notification Settings', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addNotificationMetaBox'),
                    'post_type' => NULL,
                    'context' => 'normal',
                    'priority' => 'high',
                    'callback_args' => $form_fields),
                // messages meta box
                'credmessagesdiv' => array(
                    'title' => __('Form Texts', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addMessagesMetaBox'),
                    'post_type' => NULL,
                    'context' => 'normal',
                    'priority' => 'high',
                    'callback_args' => $form_fields)
            );
            // CRED_PostExpiration
            $metaboxes = apply_filters('cred_ext_meta_boxes', $metaboxes, $form_fields);
            if (defined('MODMAN_PLUGIN_NAME'))
            // module manager sidebar meta box
                $metaboxes['modulemanagerdiv'] = array(
                    'title' => __('Module Manager', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addModManMetaBox'),
                    'post_type' => NULL,
                    'context' => 'side',
                    'priority' => 'default',
                    'callback_args' => array()
                );

            // do same for any 3rd-party metaboxes added to CRED forms screens
            $extra_metaboxes = apply_filters('cred_admin_register_meta_boxes', array());
            if (!empty($extra_metaboxes)) {
                foreach ($extra_metaboxes as $mt)
                    add_filter('postbox_classes_' . CRED_FORMS_CUSTOM_POST_NAME . "_$mt", array('CRED_Admin_Helper', 'addMetaboxClasses'));
            }

            // add defined meta boxes
            foreach ($metaboxes as $mt => $mt_definition) {
                add_filter('postbox_classes_' . CRED_FORMS_CUSTOM_POST_NAME . "_$mt", array('CRED_Admin_Helper', 'addMetaboxClasses'));
                add_meta_box($mt, $mt_definition['title'], $mt_definition['callback'], $mt_definition['post_type'], $mt_definition['context'], $mt_definition['priority'], $mt_definition['callback_args']);
            }

            // allow 3rd-party to add meta boxes to CRED form admin screen
            do_action('cred_admin_add_meta_boxes', $form);
        }
    }

    public static function addMetaBoxes2($form) {
        global $pagenow;

        if (CRED_USER_FORMS_CUSTOM_POST_NAME == $form->post_type) {
            $model = CRED_Loader::get('MODEL/UserForms');
            $form_fields = $model->getFormCustomFields($form->ID, array('form_settings', 'notification', 'extra'));

            // add cred related classes to our metaboxes
            $metaboxes = array(
                // form type meta box
                'credformtypediv' => array(
                    'title' => __('Form Settings', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addUserFormSettingsMetaBox'),
                    'post_type' => NULL,
                    'context' => 'normal',
                    'priority' => 'high',
                    'callback_args' => $form_fields),
                // post type meta box
//                'credposttypediv' => array(
//                    'title' => __('Post Type Settings', 'wp-cred'),
//                    'callback' => array('CRED_Admin_Helper', 'addPostTypeMetaBox'),
//                    'post_type' => NULL,
//                    'context' => 'normal',
//                    'priority' => 'high',
//                    'callback_args' => $form_fields),
                // content meta box to wrap rich editor, acts as placeholder
                'credformcontentdiv' => array(
                    'title' => __('Form Content', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addFormContentMetaBox'),
                    'post_type' => NULL,
                    'context' => 'normal',
                    'priority' => 'high',
                    'callback_args' => array()),
                // extra meta box (css, js) (placed inside editor meta box)
                'credextradiv' => array(
                    'title' => __('CSS and Javascript for this form', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addExtraAssetsMetaBox'),
                    'post_type' => NULL,
                    'context' => 'normal',
                    'priority' => 'high',
                    'callback_args' => $form_fields),
                // email notification meta box
                'crednotificationdiv' => array(
                    'title' => __('Notification Settings', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addNotificationMetaBox2'),
                    'post_type' => NULL,
                    'context' => 'normal',
                    'priority' => 'high',
                    'callback_args' => $form_fields),
                // messages meta box
                'credmessagesdiv' => array(
                    'title' => __('Form Texts', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addMessagesMetaBox2'),
                    'post_type' => NULL,
                    'context' => 'normal',
                    'priority' => 'high',
                    'callback_args' => $form_fields)
            );

            // CRED_PostExpiration
            $metaboxes = apply_filters('cred_ext_meta_boxes', $metaboxes, $form_fields);
            if (defined('MODMAN_PLUGIN_NAME'))
            // module manager sidebar meta box
                $metaboxes['modulemanagerdiv'] = array(
                    'title' => __('Module Manager', 'wp-cred'),
                    'callback' => array('CRED_Admin_Helper', 'addModManMetaBox'),
                    'post_type' => NULL,
                    'context' => 'side',
                    'priority' => 'default',
                    'callback_args' => array()
                );

            // do same for any 3rd-party metaboxes added to CRED forms screens
            $extra_metaboxes = apply_filters('cred_admin_register_meta_boxes', array());
            if (!empty($extra_metaboxes)) {
                foreach ($extra_metaboxes as $mt)
                    add_filter('postbox_classes_' . CRED_USER_FORMS_CUSTOM_POST_NAME . "_$mt", array('CRED_Admin_Helper', 'addMetaboxClasses2'));
            }

            // add defined meta boxes
            foreach ($metaboxes as $mt => $mt_definition) {
                add_filter('postbox_classes_' . CRED_USER_FORMS_CUSTOM_POST_NAME . "_$mt", array('CRED_Admin_Helper', 'addMetaboxClasses2'));
                add_meta_box($mt, $mt_definition['title'], $mt_definition['callback'], $mt_definition['post_type'], $mt_definition['context'], $mt_definition['priority'], $mt_definition['callback_args']);
            }

            // allow 3rd-party to add meta boxes to CRED form admin screen
            do_action('cred_user_admin_add_meta_boxes', $form);
        }
    }

}
