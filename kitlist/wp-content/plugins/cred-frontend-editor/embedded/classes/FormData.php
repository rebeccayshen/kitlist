<?php

/**
 * FormData contains settings and other information about CRED form
 *
 * @author onTheGo System
 */
class FormData {

    private $_formData = null;

    public function __construct($form_id, $post_type, $preview) {
        $this->_formData = $this->loadForm($form_id, $post_type, $preview);
        return $this->_formData;
    }

    public function getForm() {
        return $this->_formData->form;
    }

    public function getFields() {
        return $this->_formData->fields;
    }

    public function loadForm($formID, $post_type, $preview = false) {
        global $post, $current_user;

        // reference to the form submission method
        global ${'_' . StaticClass::METHOD};
        $method = & ${'_' . StaticClass::METHOD};

        // load form data
        $fm = ($post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) ? CRED_Loader::get('MODEL/UserForms') : CRED_Loader::get('MODEL/Forms');
        $form = $fm->getForm($formID);

        if (/* false=== */!$form) {
            return $this->error(__('Form does not exist!', 'wp-cred'));
        }

        // preview when form is saved only partially
        if (!isset($form->fields) || !is_array($form->fields) || empty($form->fields)) {
            $form->fields = array();
            if ($preview) {
                unset($form);
                return $this->error(__('Form preview does not exist. Try saving your form first', 'wp-cred'));
            }
        }

        $form->fields = array_merge(
                array(
            'form_settings' => (object) array('form' => array(), 'post' => array()),
            'extra' => (object) array('css' => '', 'js' => ''),
            'notification' => (object) array('enable' => 0, 'notifications' => array())
                ), $form->fields
        );

        if (!isset($form->fields['extra']->css))
            $form->fields['extra']->css = '';
        if (!isset($form->fields['extra']->js))
            $form->fields['extra']->js = '';

        $redirect_delay = isset($form->fields['form_settings']->form['redirect_delay']) ? intval($form->fields['form_settings']->form['redirect_delay']) : self::DELAY;
        $hide_comments = (isset($form->fields['form_settings']->form['hide_comments']) && $form->fields['form_settings']->form['hide_comments']) ? true : false;
        $form->fields['form_settings']->form['redirect_delay'] = $redirect_delay;
        $form->fields['form_settings']->form['hide_comments'] = $hide_comments;

        if ($preview) {
            if (array_key_exists(StaticClass::PREFIX . 'form_preview_post_type', $method))
                $form->fields['form_settings']->post['post_type'] = stripslashes($method[StaticClass::PREFIX . 'form_preview_post_type']);
            else {
                unset($form);
                return $this->error(__('Preview post type not provided', 'wp-cred'));
            }

            if (array_key_exists(StaticClass::PREFIX . 'form_preview_form_type', $method))
                $form->fields['form_settings']->form['type'] = stripslashes($method[StaticClass::PREFIX . 'form_preview_form_type']);
            else {
                unset($form);
                $this->error = __('Preview form type not provided', 'wp-cred');
            }
            if (array_key_exists(StaticClass::PREFIX . 'form_preview_content', $method)) {
                $form->form->post_content = stripslashes($method[StaticClass::PREFIX . 'form_preview_content']);
            } else {
                unset($form);
                return $this->error(__('No preview form content provided', 'wp-cred'));
            }

            if (array_key_exists(StaticClass::PREFIX . 'extra_css_to_use', $method)) {
                $form->fields['extra']->css = trim(stripslashes($method[StaticClass::PREFIX . 'extra_css_to_use']));
            }
            if (array_key_exists(StaticClass::PREFIX . 'extra_js_to_use', $method)) {
                $form->fields['extra']->js = trim(stripslashes($method[StaticClass::PREFIX . 'extra_js_to_use']));
            }
        } else {
            if ($post_type == CRED_USER_FORMS_CUSTOM_POST_NAME)
                $form->fields['form_settings']->post['post_type'] = "user";
        }

        if (!isset($form->fields['extra']->messages)) {
            $form->fields['extra']->messages = $fm->getDefaultMessages();
        }

        //return it
        return $form;
    }

    // whether this form attempts to hide comments
    public function hasHideComments() {
        $fields = $this->getFields();
        return $fields['form_settings']->form['hide_comments'];
    }

    // get extra javascript/css attached to this form
    public function getExtra() {
        $fields = $this->getFields();
        return $fields['extra'];
    }

    public function error($msg = '') {
        return new WP_Error($msg);
    }

}

?>
