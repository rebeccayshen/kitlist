<?php

/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk_new/embedded/classes/Form_Builder.php $
 * $LastChangedDate: 2015-04-10 15:33:31 +0200 (ven, 10 apr 2015) $
 * $LastChangedRevision: 32869 $
 * $LastChangedBy: francesco $
 *
 */

/**
 * Form Builder Class
 * Friend Classes (quasi-)Design Pattern
 */
class CRED_Form_Builder implements CRED_Friendable, CRED_FriendableStatic {

    // CONSTANTS
    //const METHOD='POST';                                         // form method POST
    //const PREFIX='_cred_cred_prefix_';                           // prefix for various hidden auxiliary fields
    //const NONCE='_cred_cred_wpnonce';                            // nonce field name
    //const POST_CONTENT_TAG='%__CRED__CRED__POST__CONTENT__%';    // placeholder for post content
    //const FORM_TAG='%__CRED__CRED__FORM___FORM__%';              //
    //const DELAY=0;                                               // seconds delay before redirection

    private $_zebraForm = null;                                   // instance of Zebra form, to render frontend forms
    // INSTANCE  properties
    private $_shortcodeParser = null;                             // instance of shortcode parser
    private $_formHelper = null;                                  // instance of form helper
    private $_formData = null;                                    // current CRED form data, like content, fields, settings etc..
    private $_post_ID = null;                                     // ID of currently edited or created post
    private $_postType = null;
    private $_postData = null;                                    // currently edited post data
    private $_content = '';                                       // currently parsed form content (whole)
    private $_form_content = '';                                  // currently parsed form content (strictly inside the form tags)
    private $_attributes = array();                               // currently parsed form extra attributes
    private $out_ = array(// info about currently output form
        'count' => null,
        'prg_id' => null,
        'js' => '',
        'has_recaptcha' => false,
        'fields' => array(),
        'form_fields' => array(),
        'form_fields_info' => array(),
        'field_values_map' => array(),
        'conditionals' => array(),
        'current_group' => null,
        'child_groups' => null,
        'generic_fields' => array(),
        'taxonomy_map' => array('taxonomy' => array(), 'aux' => array()),
        'controls' => array(),
        'nonce_field' => null,
        'form_id_field' => null,
        'form_count_field' => null,
        'post_id_field' => null,
        'notification_data' => '',
    );
    private $_supportedDateFormats = array(//  supported date formats
        'F j, Y', //December 23, 2011
        'Y/m/d', // 2011/12/23
        'm/d/Y', // 12/23/2011
        'd/m/Y' // 23/12/2011
    );

    /*
     *   Implement Friendable Interface
     */
    private $_____friends_____ = array(/* Friend Instances Hashes as keys Here.. */);

    //private static $_______class_______='CRED_Form_Builder';
    /*
     *   /END Implement Friendable Interface
     */

    /* =============================== STATIC METHODS ======================================== */

    // true if forms have been built for current page
    public static function has_form() {
        return (StaticClass::$_staticGlobal['COUNT'] > 0);
    }

    // init public function
    public static function init() {
//        if (!empty($_POST)){
//        StaticClass::_pre($_POST);
//        StaticClass::_pre($_FILES);die;}
        //CRED_Loader::load('CLASS/Form_Helper');
        // form helper is a friend of form builder, so they can share access between them
        self::addFriendStatic('StaticClass', array(
            'methods' => array(),
            'properties' => array('_staticGlobal')
        ));
        // parse cred form output
        add_action('wp_loaded', array('CRED_Form_Builder', '_init_'), 10);
        // load front end form assets

        add_action('wp_head', array('CRED_Form_Builder_Helper', 'loadFrontendAssets'));
        add_action('wp_footer', array('CRED_Form_Builder_Helper', 'unloadFrontendAssets'));
    }

    // check for form submissions on init
    public static function _init_() {

        // check for cred form submissions
        if (!is_admin()) {
            // reference to the form submission method
            global ${'_' . StaticClass::METHOD};
            $method = & ${'_' . StaticClass::METHOD};

            if (array_key_exists(StaticClass::PREFIX . 'form_id', $method) &&
                    array_key_exists(StaticClass::PREFIX . 'form_count', $method)) {
                $form_id = intval($method[StaticClass::PREFIX . 'form_id']);
                $form_count = intval($method[StaticClass::PREFIX . 'form_count']);

                // edit form
                if (array_key_exists(StaticClass::PREFIX . 'post_id', $method))
                    $post_id = intval($method[StaticClass::PREFIX . 'post_id']);
                else
                    $post_id = false;

                // preview form
                if (array_key_exists(StaticClass::PREFIX . 'form_preview_content', $method))
                    $preview = true;
                else
                    $preview = false;

                // parce and cache form
                self::getCachedForm($form_id, $post_id, $preview, $form_count);
            }
        }
    }

    private static function getCachedForm($form_id, $post_id, $preview, $force_count = false, $specific_post_id = null) {
        global $post;
        StaticClass::$_mail_error = get_option('_' . $form_id . '_last_mail_error', '');
        //put a sanitize someone could add _mail_error text injection?
        StaticClass::$_mail_error = sanitize_text_field(StaticClass::$_mail_error);
        StaticClass::$_cred_container_id = (isset($_POST[StaticClass::PREFIX . 'cred_container_id'])) ? intval($_POST[StaticClass::PREFIX . 'cred_container_id']) : $post->ID;

        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196173458/comments
        //Security Check
        if (isset(StaticClass::$_cred_container_id) && !empty(StaticClass::$_cred_container_id)) {
            if (!is_numeric(StaticClass::$_cred_container_id))
                wp_die('Invalid data');
        }

        $form_count = (false !== $force_count) ? $force_count : StaticClass::$_staticGlobal['COUNT'];

        if (
                false !== $force_count ||
                (!array_key_exists($form_id . '_' . StaticClass::$_staticGlobal['COUNT'], StaticClass::$_staticGlobal['CACHE']))
        ) {
            // parse and cache form
            $fb = new CRED_Form_Builder();
            $form_post_type = get_post_type($form_id);
            $form = ($form_post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) ? $fb->user_form($form_id, $post_id, $preview, $form_count, $specific_post_id) : $fb->form($form_id, $post_id, $preview, $form_count, $specific_post_id);
            /* StaticClass::$_staticGlobal['CACHE'][$form_id.'_'.$form_count]=array(
              'form' =>  $output,
              'count' => $form_count,
              'extra' => $this->_formData->getExtra(),
              'css_to_use' => $this->_formData->getCSS(),
              'js' => $this->getJS(),
              'hide_comments' =>  $this->_formData->hasHideComments(),
              'has_recaptcha' =>  $this->hasRecaptcha()
              ); */

            StaticClass::$_staticGlobal['CACHE'][$form_id . '_' . $form_count] = array(
                'form' => $form,
                'count' => $form_count,
                'extra' => $fb->getExtra(),
                'js' => $fb->getJS(),
                'hide_comments' => $fb->hasHideComments(),
                'has_recaptcha' => $fb->hasRecaptcha()
            );
        }

        if (isset($post_id))
            $parent_post = get_post($post_id);
        // add filter to hide comments (new method)
        if (
        //false!==$force_count && // do lazy, dont hide comments immediately, maybe the form will not show when page loads finally
                StaticClass::$_staticGlobal['CACHE'][$form_id . '_' . $form_count]['hide_comments'] ||
                (isset($parent_post) && $parent_post->comment_status == 'closed')
        )
            CRED_Form_Builder_Helper::hideComments();

        return StaticClass::$_staticGlobal['CACHE'][$form_id . '_' . $form_count]['form'];
    }

    // get form html output for given form (form is processed if data submitted)
    public static function getForm($form_id, $post_id = null, $preview = false, $specific_post_id = null) {
        //CRED_Loader::load('CLASS/Form_Helper');
        CRED_Form_Builder_Helper::initVars();
        ++StaticClass::$_staticGlobal['COUNT'];

        if (is_string($form_id) && !is_numeric($form_id)) {
            //Added html_entity_decode https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/189098085/comments
            $form = get_page_by_title(html_entity_decode($form_id), OBJECT, CRED_FORMS_CUSTOM_POST_NAME);

            if ($form && is_object($form))
                $form_id = $form->ID;
            else
                return '';
        }

        return self::getCachedForm($form_id, $post_id, $preview, false, $specific_post_id);
    }

    public static function getUserForm($form_id, $post_id = null, $preview = false, $specific_post_id = null) {
        //CRED_Loader::load('CLASS/Form_Helper');
        CRED_Form_Builder_Helper::initVars();
        ++StaticClass::$_staticGlobal['COUNT'];

        if (is_string($form_id) && !is_numeric($form_id)) {
            //Added html_entity_decode https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/189098085/comments
            $form = get_page_by_title(html_entity_decode($form_id), OBJECT, CRED_USER_FORMS_CUSTOM_POST_NAME);

            if ($form && is_object($form))
                $form_id = $form->ID;
            else
                return '';
        }

        return self::getCachedForm($form_id, $post_id, $preview, false, $specific_post_id);
    }

    /* =============================== INSTANCE METHODS ======================================== */

    // constuctor, return a CRED form object
    public function __construct() {
        //CRED_Loader::load('CLASS/Form_Helper');
        CRED_Form_Builder_Helper::initVars();

        // shortcodes parsed by custom shortcode parser
        $this->_shortcodeParser = CRED_Loader::get('CLASS/Shortcode_Parser');
        // various functions performed by custom form helper
        $this->_formHelper = CRED_Loader::get('CLASS/Form_Helper', $this);
        // form helper is a friend of form builder, so they can share access between them
        $this->addFriend($this->_formHelper, array(
            'methods' => array(),
            'properties' => array(
                '_formData',
                '_postData',
                'out_',
                '_supportedDateFormats',
                '_zebraForm',
                '_shortcodeParser'
            )
        ));
    }

    private function destroy() {
        
    }

    static $wmsg = false;

    // whether this form attempts to hide comments
    public function hasHideComments() {
        $fields = $this->_formData->getFields();
        return $fields['form_settings']->form['hide_comments'];
    }

    // get extra javascript/css attached to this form
    public function getExtra() {
        $fields = $this->_formData->getFields();
        return $fields['extra'];
    }

    // whether this form has recaptcha field
    public function hasRecaptcha() {
        return $this->out_['has_recaptcha'];
    }

    // get zebra javascript needed by this form
    public function getJS() {
        return $this->out_['js'];
    }

    // manage form submission / validation and rendering and return rendered html
    public function form($form_id, $post_id = null, $preview = false, $force_form_count = false, $specific_post_id = null) {
        $bypass_form = apply_filters('cred_bypass_process_form_' . $form_id, false, $form_id, $post_id, $preview);
        $bypass_form = apply_filters('cred_bypass_process_form', $bypass_form, $form_id, $post_id, $preview);

        require_once 'FormData.php';
        $this->_formData = new FormData($form_id, CRED_FORMS_CUSTOM_POST_NAME, $preview);
        $form = &$this->_formData;
        $formHelper = $this->_formHelper;

        // reference to the form submission method
        global ${'_' . StaticClass::METHOD};
        $method = & ${'_' . StaticClass::METHOD};

        // if some error happened, display a message instead        
        $parse = $this->parseInputs($form_id, $post_id, $preview, $force_form_count, $specific_post_id);
        if ($formHelper->isError($parse))
            return $formHelper->getError($parse);

        $zebraForm = $this->_zebraForm;
        $zebraForm->extra_parameters = $this->_formData->getExtra();

        $form_id = $form->getForm()->ID;
        $_fields = $form->getFields();
        $form_type = $_fields['form_settings']->form['type'];

        $prg_id = $this->out_['prg_id'];
        $form_count = $this->out_['count'];
        //$post_type=$form->fields['form_settings']->post['post_type'];
        $post_type = $this->_postType;

        //Removed this because we made it in parseInputs
        //Fixing: https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/190656261/comments#295538767
        //$post_id=$this->_post_ID;
        // define global $post from $post_id
        global $post;
        if (is_int($post_id) && $post_id > 0) {
            if (!isset($post->ID) || (isset($post->ID) && $post->ID != $post_id)) {
                $post = get_post($post_id);
            }
        }

        // show display message from previous submit of same create form (P-R-G pattern)
        if (
                !$zebraForm->preview && /* 'edit'!=$form_type && (isset($_GET['action']) && $_GET['action'] == 'edit_translation' && 'translation'!=$form_type) && */
                isset($_GET['_success_message']) &&
                $_GET['_success_message'] == $prg_id &&
                'message' == $_fields['form_settings']->form['action']
        ) {
            $zebraForm->is_submit_success = true;
            return $formHelper->displayMessage($form);
        } else
            $zebraForm->is_submit_success = $this->isSubmitted();

        $this->CRED_build();

        // no message to display if not submitted
        $message = false;

        // add notification message from previous submit of same create form (P-R-G pattern)
        /* if (($n_data=$formHelper->readCookie('_cred_cred_notifications'.$prg_id)))
          {
          $formHelper->clearCookie('_cred_cred_notifications'.$prg_id);
          if (isset($n_data['sent']))
          {
          foreach ((array)$n_data['sent'] as $ii)
          $zebraForm->add_form_message('notification_'.$ii, $formHelper->getLocalisedMessage('notification_was_sent'));
          }
          if (isset($n_data['failed']))
          {
          foreach ((array)$n_data['failed'] as $ii)
          $zebraForm->add_form_message('notification_'.$ii, $formHelper->getLocalisedMessage('notification_failed'));
          }
          } */

        $thisform = array(
            'id' => $form_id,
            'post_type' => $post_type,
            'form_type' => $form_type
        );

        //Check dates
        foreach ($_POST as $name => &$value) {
            if ($name == StaticClass::NONCE)
                continue;
            if (is_array($value) && isset($value['datepicker'])) {
                if (!function_exists('adodb_date')) {
                    require_once WPTOOLSET_FORMS_ABSPATH . '/lib/adodb-time.inc.php';
                }

                $date_format = get_option('date_format');
                $date = $value['datepicker'];
                $value['datetime'] = adodb_date("Y-m-d", $date);
                $value['hour'] = isset($value['hour']) ? $value['hour'] : "00";
                $value['minute'] = isset($value['minute']) ? $value['minute'] : "00";
                $value['timestamp'] = strtotime($value['datetime'] . " " . $value['hour'] . ":" . $value['minute'] . ":00");
            }
        }

        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196177636/comments#309966145
        //Centralized the mime types
        $mime_types = wp_get_mime_types();
        StaticClass::$_allowed_mime_types = array_merge($mime_types, array('xml' => 'text/xml'));
        StaticClass::$_allowed_mime_types = apply_filters('upload_mimes', StaticClass::$_allowed_mime_types);

        /**
         * sanitize input data
         */
        if (!array_key_exists('post_fields', $this->out_['fields'])) {
            $this->out_['fields']['post_fields'] = array();
        }
        //fixed Server side error messages should appear next to the field with the problem
        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/186243370/comments
        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196177636/comments
        $formHelper->checkFilePost($zebraForm, $this->out_['fields']['post_fields']);
        if (isset($this->out_['fields']['post_fields']) && isset($this->out_['form_fields_info']))
            $formHelper->checkFilesType($this->out_['fields']['post_fields'], $this->out_['form_fields_info'], $zebraForm, $error_files);
        //##########################################################################################                
        //if (!$bypass_form && $_zebraForm->validate($post_id, $_zebraForm->form_properties['fields']))
        if (!$bypass_form && $this->validate($error_files)) {
            if (!$zebraForm->preview) {
                // save post data
                $bypass_save_form_data = apply_filters('cred_bypass_save_data_' . $form_id, false, $form_id, $post_id, $thisform);
                $bypass_save_form_data = apply_filters('cred_bypass_save_data', $bypass_save_form_data, $form_id, $post_id, $thisform);

                if (!$bypass_save_form_data) {
                    $model = CRED_Loader::get('MODEL/Forms');
                    $attachedData = $model->getAttachedData($post_id);
                    $post_id = $this->CRED_save($post_id);
                }

                if (is_wp_error($post_id)) {
                    $zebraForm->add_field_message($post_id->get_error_message(), 'Post Name');
                } else {
                    if (is_int($post_id) && $post_id > 0) {
                        // set global $post
                        $post = get_post($post_id);

                        // send notifications
                        //list($n_sent, $n_failed)=$this->notify($post_id);
                        // enable notifications and notification events if any
                        $this->notify($post_id, $attachedData);
                        unset($attachedData);

                        // save results for later messages if PRG
                        //$formHelper->setCookie('_cred_cred_notifications'.$prg_id, array('sent'=>$n_sent, 'failed'=>$n_failed));
                        // do custom action here
                        // user can redirect, display messages, overwrite page etc..
                        $bypass_credaction = apply_filters('cred_bypass_credaction_' . $form_id, false, $form_id, $post_id, $thisform);
                        $bypass_credaction = apply_filters('cred_bypass_credaction', $bypass_credaction, $form_id, $post_id, $thisform);

                        //Emerson:->https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/185572863/comments
                        /* Add cred_submit_complete_form_ hook in CRED 1.3 */
                        $form_slug = $form->getForm()->post_name;
                        do_action('cred_submit_complete_form_' . $form_slug, $post_id, $thisform);
                        do_action('cred_submit_complete_' . $form_id, $post_id, $thisform);
                        do_action('cred_submit_complete', $post_id, $thisform);

                        // no redirect url
                        $url = false;
                        // do success action
                        if ($bypass_credaction) {
                            $credaction = 'form';
                        } else {
                            $credaction = $_fields['form_settings']->form['action'];
                        }
                        // do default or custom actions
                        switch ($credaction) {
                            case 'post':
                                $url = $formHelper->getLocalisedPermalink($post_id, $_fields['form_settings']->post['post_type']); //get_permalink($post_id);
                                break;
                            case 'page':
                                $url = (!empty($_fields['form_settings']->form['action_page'])) ? $formHelper->getLocalisedPermalink($_fields['form_settings']->form['action_page'], 'page')/* get_permalink($_fields['form_settings']->form['action_page']) */ : false;
                                break;
                            case 'message':
                            case 'form':
                            // custom 3rd-party action
                            default:
                                if ('form' != $credaction && 'message' != $credaction) {
                                    // add hooks here, to do custom action when custom cred action has been selected
                                    do_action('cred_custom_success_action_' . $form_id, $credaction, $post_id, $thisform);
                                    do_action('cred_custom_success_action', $credaction, $post_id, $thisform);
                                }

                                // if previous did not do anything, default to display form
                                if ('form' != $credaction && 'message' != $credaction)
                                    $credaction = 'form';

                                // no redirect url
                                $url = false;

                                // PRG (POST-REDIRECT-GET) pattern,
                                // to avoid resubmit on browser refresh issue, and also keep defaults on new form !! :)
                                if ('message' == $credaction) {
                                    $url = $formHelper->currentURI(array(
                                        '_tt' => time(),
                                        '_success_message' => $prg_id,
                                        '_target' => $post_id
                                    ));
                                } else {
                                    $url = $formHelper->currentURI(array(
                                        '_tt' => time(),
                                        '_success' => $prg_id
                                    ));
                                }
                                $url = $url . '#cred_form_' . $prg_id;
                                // do PRG, redirect now
                                $formHelper->redirect($url, array("HTTP/1.1 303 See Other"));
                                exit;  // just in case
                                break;
                        }

                        // do redirect action here
                        if (false !== $url) {
                            if ('form' != $credaction && 'message' != $credaction) {
                                $url = apply_filters('cred_success_redirect_form_' . $form_slug, $url, $post_id, $thisform);
                                $url = apply_filters('cred_success_redirect_' . $form_id, $url, $post_id, $thisform);
                                $url = apply_filters('cred_success_redirect', $url, $post_id, $thisform);
                            }

                            if (false !== $url) {
                                $redirect_delay = $_fields['form_settings']->form['redirect_delay'];
                                if ($redirect_delay <= 0)
                                    $formHelper->redirect($url);
                                else
                                    $formHelper->redirectDelayed($url, $redirect_delay);
                            }
                        }

                        $saved_message = $formHelper->getLocalisedMessage('post_saved');
                        $saved_message = apply_filters('cred_data_saved_message_' . $form_id, $saved_message, $form_id, $post_id, $preview);
                        $saved_message = apply_filters('cred_data_saved_message', $saved_message, $form_id, $post_id, $preview);
                        // add success message
                        //$zebraForm->add_form_message('data-saved', $formHelper->getLocalisedMessage('post_saved'));
                        $zebraForm->add_success_message($saved_message);
                    }
                    else {
                        if (isset($_FILES) && count($_FILES) > 0) {
                            // TODO check if this wp_list_pluck works with repetitive files... maybe in_array( array(1), $errors_on_files ) does the trick...
                            $errors_on_files = $food_names = wp_list_pluck($_FILES, 'error');
                            if (in_array(1, $errors_on_files) || in_array(2, $errors_on_files)) {
                                $zebraForm->add_field_message($formHelper->getLocalisedMessage('no_data_submitted'));
                            } else {
                                $zebraForm->add_field_message($formHelper->getLocalisedMessage('post_not_saved'));
                            }
                        } else {
                            // else just show the form again
                            $zebraForm->add_field_message($formHelper->getLocalisedMessage('post_not_saved'));
                        }
                    }
                }
            } else {
                //$zebraForm->add_form_message('preview-form',__('Preview Form submitted','wp-cred'));
                $zebraForm->add_field_message(__('Preview Form submitted', 'wp-cred'));
            }
        } else if ($this->isSubmitted()) {
            $form_name = $formHelper->createFormID($form_id, $form_count);
            $top_messages = isset($zebraForm->top_messages[$form_name]) ? $zebraForm->top_messages[$form_name] : array();
            if (empty($method)) {
                $not_saved_message = $formHelper->getLocalisedMessage('no_data_submitted');
            } else {
                //$not_saved_message=$formHelper->getLocalisedMessage('post_not_saved'); // Replaced to new custom error message by Gen
                if (count($top_messages) == 1) {
                    $tmpmsg = str_replace("<br />%PROBLEMS_UL_LIST", "", $formHelper->getLocalisedMessage('post_not_saved_singular'));
                    $not_saved_message = $tmpmsg . "<br />%PROBLEMS_UL_LIST";
                } else {
                    $tmpmsg = str_replace("<br />%PROBLEMS_UL_LIST", "", $formHelper->getLocalisedMessage('post_not_saved_plural'));
                    $not_saved_message = $tmpmsg . "<br />%PROBLEMS_UL_LIST";
                }

                $error_list = '<ul>';
                foreach ($top_messages as $id_field => $text) {
                    $error_list .= '<li>' . $text . '</li>';
                }
                $error_list .= '</ul>';
                $not_saved_message = str_replace(array('%PROBLEMS_UL_LIST', '%NN'), array($error_list, count($top_messages)), $not_saved_message);
            }
            $not_saved_message = apply_filters('cred_data_not_saved_message_' . $form_id, $not_saved_message, $form_id, $post_id, $preview);
            $not_saved_message = apply_filters('cred_data_not_saved_message', $not_saved_message, $form_id, $post_id, $preview);
            //$zebraForm->add_form_message('data-saved', $not_saved_message);
            $zebraForm->add_field_message($not_saved_message);

//            if ( !empty( $zebraForm->form_errors ) ) {
//                foreach( $zebraForm->form_errors as $error_element_id => $error_message ) {
//                    $zebraForm->add_form_message('data-saved', $error_message );
//                }
//            }
        } else if (
                isset($_GET['_success']) &&
                $_GET['_success'] == $prg_id
        ) {
            // add success message from previous submit of same any form (P-R-G pattern)
            $saved_message = $formHelper->getLocalisedMessage('post_saved');
            $saved_message = apply_filters('cred_data_saved_message_' . $form_id, $saved_message, $form_id, $post_id, $preview);
            $saved_message = apply_filters('cred_data_saved_message', $saved_message, $form_id, $post_id, $preview);
            //$zebraForm->add_form_message('data-saved', $saved_message);
            $zebraForm->add_success_message($saved_message);
        }

//        $msgs = "";
//        $msg_block = "data-saved";
//        if (isset($zebraForm->form_messages[$msg_block])&&count($zebraForm->form_messages[$msg_block])>0) {
//           foreach ($zebraForm->form_messages[$msg_block] as $text) {
//               $msgs .= "<label class=\"wpt-form-error\">$text</label><div style='clear:both;'></div>";
//           }
//        }

        $msgs = $zebraForm->getFieldsSuccessMessages();
        $msgs .= $zebraForm->getFieldsErrorMessages();
        $js = $zebraForm->getFieldsErrorMessagesJs();

        if (false !== $message)
            $output = $message;
        else
            $output = $this->CRED_render($msgs, $js);

        return $output;
    }

    public function user_form($form_id, $post_id = null, $preview = false, $force_form_count = false, $specific_post_id = null) {
        $bypass_form = apply_filters('cred_bypass_process_form_' . $form_id, false, $form_id, $post_id, $preview);
        $bypass_form = apply_filters('cred_bypass_process_form', $bypass_form, $form_id, $post_id, $preview);

        require_once 'FormData.php';
        $this->_formData = new FormData($form_id, CRED_USER_FORMS_CUSTOM_POST_NAME, $preview);
        $form = &$this->_formData;
        $formHelper = $this->_formHelper;

        // reference to the form submission method
        global ${'_' . StaticClass::METHOD};
        $method = & ${'_' . StaticClass::METHOD};

        // if some error happened, display a message instead
        $parse = $this->parseUserInputs($form_id, $post_id, $preview, $force_form_count, $specific_post_id);        
        if ($formHelper->isError($parse))
            return $formHelper->getError($parse);

        $zebraForm = $this->_zebraForm;
        $zebraForm->extra_parameters = $this->_formData->getExtra();

        $form_id = $form->getForm()->ID;
        $_fields = $form->getFields();
        $form_type = $_fields['form_settings']->form['type'];
        $user_role = $_fields['form_settings']->form['user_role'];

        if (empty($user_role))
            $user_role = array('subscriber');

        $prg_id = $this->out_['prg_id'];
        $form_count = $this->out_['count'];
        //$post_type=$form->fields['form_settings']->post['post_type'];
        $post_type = $this->_postType;

        //Removed this because we made it in parseInputs
        //Fixing: https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/190656261/comments#295538767
        //$post_id=$this->_post_ID;
        // define global $post from $post_id
        global $post;
        if (is_int($post_id) && $post_id > 0) {
            if (!isset($post->ID) || (isset($post->ID) && $post->ID != $post_id)) {
                $post = get_post($post_id);
            }
        }
        //TODO: get user ???
        // show display message from previous submit of same create form (P-R-G pattern)
        if (
                !$zebraForm->preview && /* 'edit'!=$form_type && (isset($_GET['action']) && $_GET['action'] == 'edit_translation' && 'translation'!=$form_type) && */
                isset($_GET['_success_message']) &&
                $_GET['_success_message'] == $prg_id &&
                'message' == $_fields['form_settings']->form['action']
        ) {
            $zebraForm->is_submit_success = true;
            return $formHelper->displayMessage($form);
        } else
            $zebraForm->is_submit_success = $this->isSubmitted();

        $this->CRED_User_build();

        // no message to display if not submitted
        $message = false;

        // add notification message from previous submit of same create form (P-R-G pattern)
        /* if (($n_data=$formHelper->readCookie('_cred_cred_notifications'.$prg_id)))
          {
          $formHelper->clearCookie('_cred_cred_notifications'.$prg_id);
          if (isset($n_data['sent']))
          {
          foreach ((array)$n_data['sent'] as $ii)
          $zebraForm->add_form_message('notification_'.$ii, $formHelper->getLocalisedMessage('notification_was_sent'));
          }
          if (isset($n_data['failed']))
          {
          foreach ((array)$n_data['failed'] as $ii)
          $zebraForm->add_form_message('notification_'.$ii, $formHelper->getLocalisedMessage('notification_failed'));
          }
          } */

        $thisform = array(
            'id' => $form_id,
            'post_type' => $post_type,
            'form_type' => $form_type
        );

        //Check dates
        foreach ($_POST as $name => &$value) {
            if ($name == StaticClass::NONCE)
                continue;
            if (is_array($value) && isset($value['datepicker'])) {
                if (!function_exists('adodb_date')) {
                    require_once WPTOOLSET_FORMS_ABSPATH . '/lib/adodb-time.inc.php';
                }

                $date_format = get_option('date_format');
                $date = $value['datepicker'];
                $value['datetime'] = adodb_date("Y-m-d", $date);
                $value['hour'] = isset($value['hour']) ? $value['hour'] : "00";
                $value['minute'] = isset($value['hour']) ? $value['minute'] : "00";
                $value['timestamp'] = strtotime($value['datetime'] . " " . $value['hour'] . ":" . $value['minute'] . ":00");
            }
        }

        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196177636/comments#309966145
        //Centralized the mime types
        $mime_types = wp_get_mime_types();
        StaticClass::$_allowed_mime_types = array_merge($mime_types, array('xml' => 'text/xml'));
        StaticClass::$_allowed_mime_types = apply_filters('upload_mimes', StaticClass::$_allowed_mime_types);

        /**
         * sanitize input data
         */
        if (!array_key_exists('post_fields', $this->out_['fields'])) {
            $this->out_['fields']['post_fields'] = array();
        }
        //fixed Server side error messages should appear next to the field with the problem
        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/186243370/comments
        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196177636/comments
        $formHelper->checkFilePost($zebraForm, $this->out_['fields']['post_fields']);
        if (isset($this->out_['fields']['post_fields']) && isset($this->out_['form_fields_info']))
            $formHelper->checkFilesType($this->out_['fields']['post_fields'], $this->out_['form_fields_info'], $zebraForm, $error_files);
        //##########################################################################################
        //if (!$bypass_form && $_zebraForm->validate($post_id, $_zebraForm->form_properties['fields']))
        if (!$bypass_form && $this->validate($error_files)) {
            if (!$zebraForm->preview) {
                // save post data
                $bypass_save_form_data = apply_filters('cred_bypass_save_data_' . $form_id, false, $form_id, $post_id, $thisform);
                $bypass_save_form_data = apply_filters('cred_bypass_save_data', $bypass_save_form_data, $form_id, $post_id, $thisform);

                if (!$bypass_save_form_data) {
                    $model = CRED_Loader::get('MODEL/UserForms');
                    $attachedData = $model->getAttachedData($post_id);
                    $user_id = $this->CRED_user_save($user_role, $post_id);
                }

                if (is_wp_error($user_id)) {
                    $zebraForm->add_field_message($user_id->get_error_message(), 'User Name');
                } else {
                    if (is_int($user_id) && $user_id > 0) {
                        // set global $post
                        $post = get_post($post_id);

                        // send notifications
                        //list($n_sent, $n_failed)=$this->notify($post_id);
                        // enable notifications and notification events if any
                        $this->notify($user_id, $attachedData);
                        unset($attachedData);

                        // save results for later messages if PRG
                        //$formHelper->setCookie('_cred_cred_notifications'.$prg_id, array('sent'=>$n_sent, 'failed'=>$n_failed));
                        // do custom action here
                        // user can redirect, display messages, overwrite page etc..
                        $bypass_credaction = apply_filters('cred_bypass_credaction_' . $form_id, false, $form_id, $post_id, $thisform);
                        $bypass_credaction = apply_filters('cred_bypass_credaction', $bypass_credaction, $form_id, $post_id, $thisform);

                        //Emerson:->https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/185572863/comments
                        /* Add cred_submit_complete_form_ hook in CRED 1.3 */
                        $form_slug = $form->getForm()->post_name;
                        do_action('cred_submit_complete_form_' . $form_slug, $post_id, $thisform);
                        do_action('cred_submit_complete_' . $form_id, $post_id, $thisform);
                        do_action('cred_submit_complete', $post_id, $thisform);

                        // no redirect url
                        $url = false;
                        // do success action
                        if ($bypass_credaction) {
                            $credaction = 'form';
                        } else {
                            $credaction = $_fields['form_settings']->form['action'];
                        }

                        // do default or custom actions
                        switch ($credaction) {
                            case 'post':
                                //$url = get_edit_user_link($user_id); // $formHelper->getLocalisedPermalink($post_id, $_fields['form_settings']->post['post_type']); //get_permalink($post_id);
                                $url = get_author_posts_url($user_id);
                                break;
                            case 'page':
                                $url = (!empty($_fields['form_settings']->form['action_page'])) ? $formHelper->getLocalisedPermalink($_fields['form_settings']->form['action_page'], 'page')/* get_permalink($_fields['form_settings']->form['action_page']) */ : false;
                                break;
                            case 'message':
                            case 'form':
                            // custom 3rd-party action
                            default:
                                if ('form' != $credaction && 'message' != $credaction) {
                                    // add hooks here, to do custom action when custom cred action has been selected
                                    do_action('cred_custom_success_action_' . $form_id, $credaction, $post_id, $thisform);
                                    do_action('cred_custom_success_action', $credaction, $post_id, $thisform);
                                }

                                // if previous did not do anything, default to display form
                                if ('form' != $credaction && 'message' != $credaction)
                                    $credaction = 'form';

                                // no redirect url
                                $url = false;

                                // PRG (POST-REDIRECT-GET) pattern,
                                // to avoid resubmit on browser refresh issue, and also keep defaults on new form !! :)
                                if ('message' == $credaction) {
                                    $url = $formHelper->currentURI(array(
                                        '_tt' => time(),
                                        '_success_message' => $prg_id,
                                        '_target' => $post_id
                                    ));
                                } else {
                                    $url = $formHelper->currentURI(array(
                                        '_tt' => time(),
                                        '_success' => $prg_id
                                    ));
                                }
                                $url = $url . '#cred_form_' . $prg_id;
                                // do PRG, redirect now
                                $formHelper->redirect($url, array("HTTP/1.1 303 See Other"));
                                exit;  // just in case
                                break;
                        }

                        // do redirect action here
                        if (false !== $url) {
                            if ('form' != $credaction && 'message' != $credaction) {
                                $url = apply_filters('cred_success_redirect_form_' . $form_slug, $url, $post_id, $thisform);
                                $url = apply_filters('cred_success_redirect_' . $form_id, $url, $post_id, $thisform);
                                $url = apply_filters('cred_success_redirect', $url, $post_id, $thisform);
                            }

                            if (false !== $url) {
                                $redirect_delay = $_fields['form_settings']->form['redirect_delay'];
                                if ($redirect_delay <= 0)
                                    $formHelper->redirect($url);
                                else
                                    $formHelper->redirectDelayed($url, $redirect_delay);
                            }
                        }

                        $saved_message = $formHelper->getLocalisedMessage('post_saved');
                        $saved_message = apply_filters('cred_data_saved_message_' . $form_id, $saved_message, $form_id, $post_id, $preview);
                        $saved_message = apply_filters('cred_data_saved_message', $saved_message, $form_id, $post_id, $preview);
                        // add success message
                        //$zebraForm->add_form_message('data-saved', $formHelper->getLocalisedMessage('post_saved'));
                        $zebraForm->add_success_message($saved_message);
                    }
                    else {
                        if (isset($_FILES) && count($_FILES) > 0) {
                            // TODO check if this wp_list_pluck works with repetitive files... maybe in_array( array(1), $errors_on_files ) does the trick...
                            $errors_on_files = $food_names = wp_list_pluck($_FILES, 'error');
                            if (in_array(1, $errors_on_files) || in_array(2, $errors_on_files)) {
                                //$zebraForm->add_form_message('data-saved', $formHelper->getLocalisedMessage('no_data_submitted'));
                                $zebraForm->add_field_message($formHelper->getLocalisedMessage('no_data_submitted'));
                            } else {
                                // else just show the form again, another error happening here
                                //$zebraForm->add_form_message('data-saved', $formHelper->getLocalisedMessage('post_not_saved'));
                                //$zebraForm->add_field_message($formHelper->getLocalisedMessage('post_not_saved'));
//                                    $form_name = $formHelper->createFormID($form_id, $form_count);
//                                    $field_messages = $zebraForm->field_messages[$form_name];
//                                    if ( count($field_messages) == 1){
//                                        $not_saved_message=$formHelper->getLocalisedMessage('post_not_saved_singular');
//                                    }else{
//                                        $not_saved_message=$formHelper->getLocalisedMessage('post_not_saved_plural');
//                                    }
//                                    $error_list = '<ul>';
//                                    foreach ($field_messages as $id_field=>$text) {
//                                            $error_list .= '<li>'. $text .'</li>';
//                                    }
//                                    $error_list .= '</ul>';
//                                    $not_saved_message = str_replace( array('%PROBLEMS_UL_LIST','%NN'), array($error_list, count($field_messages)), $not_saved_message);
//                                    $zebraForm->add_field_message($not_saved_message);
                            }
                        } else {
                            // else just show the form again
                            //$zebraForm->add_form_message('data-saved', $formHelper->getLocalisedMessage('post_not_saved'));
                            $zebraForm->add_field_message($formHelper->getLocalisedMessage('post_not_saved'));
                        }
                    }
                }
            } else {
                //$zebraForm->add_form_message('preview-form',__('Preview Form submitted','wp-cred'));
                $zebraForm->add_field_message(__('Preview Form submitted', 'wp-cred'));
            }
        } else if ($this->isSubmitted()) {
            $form_name = $formHelper->createFormID($form_id, $form_count);
            $top_messages = isset($zebraForm->top_messages[$form_name]) ? $zebraForm->top_messages[$form_name] : array();
            if (empty($method)) {
                $not_saved_message = $formHelper->getLocalisedMessage('no_data_submitted');
            } else {
                //$not_saved_message=$formHelper->getLocalisedMessage('post_not_saved'); // Replaced to new custom error message by Gen
                if (count($top_messages) == 1) {
                    $tmpmsg = str_replace("<br />%PROBLEMS_UL_LIST", "", $formHelper->getLocalisedMessage('post_not_saved_singular'));
                    $not_saved_message = $tmpmsg . "<br />%PROBLEMS_UL_LIST";
                } else {
                    $tmpmsg = str_replace("<br />%PROBLEMS_UL_LIST", "", $formHelper->getLocalisedMessage('post_not_saved_plural'));
                    $not_saved_message = $tmpmsg . "<br />%PROBLEMS_UL_LIST";
                }

                $error_list = '<ul>';
                foreach ($top_messages as $id_field => $text) {
                    $error_list .= '<li>' . $text . '</li>';
                }
                $error_list .= '</ul>';
                $not_saved_message = str_replace(array('%PROBLEMS_UL_LIST', '%NN'), array($error_list, count($top_messages)), $not_saved_message);
            }
            $not_saved_message = apply_filters('cred_data_not_saved_message_' . $form_id, $not_saved_message, $form_id, $post_id, $preview);
            $not_saved_message = apply_filters('cred_data_not_saved_message', $not_saved_message, $form_id, $post_id, $preview);
            //$zebraForm->add_form_message('data-saved', $not_saved_message);
            $zebraForm->add_field_message($not_saved_message);

//            if ( !empty( $zebraForm->form_errors ) ) {
//                foreach( $zebraForm->form_errors as $error_element_id => $error_message ) {
//                    $zebraForm->add_form_message('data-saved', $error_message );
//                }
//            }
        } else if (
                isset($_GET['_success']) &&
                $_GET['_success'] == $prg_id
        ) {
            // add success message from previous submit of same any form (P-R-G pattern)
            $saved_message = $formHelper->getLocalisedMessage('post_saved');
            $saved_message = apply_filters('cred_data_saved_message_' . $form_id, $saved_message, $form_id, $post_id, $preview);
            $saved_message = apply_filters('cred_data_saved_message', $saved_message, $form_id, $post_id, $preview);
            //$zebraForm->add_form_message('data-saved', $saved_message);
            $zebraForm->add_success_message($saved_message);
        }

//        $msgs = "";
//        $msg_block = "data-saved";
//        if (isset($zebraForm->form_messages[$msg_block])&&count($zebraForm->form_messages[$msg_block])>0) {
//           foreach ($zebraForm->form_messages[$msg_block] as $text) {
//               $msgs .= "<label class=\"wpt-form-error\">$text</label><div style='clear:both;'></div>";
//           }
//        }

        $msgs = $zebraForm->getFieldsSuccessMessages();
        $msgs .= $zebraForm->getFieldsErrorMessages();
        $js = $zebraForm->getFieldsErrorMessagesJs();

        if (false !== $message)
            $output = $message;
        else
            $output = $this->CRED_render($msgs, $js);

        return $output;
    }

    var $_current_post = 0;
    var $_post_to_create;

    private function parseInputs($form_id, &$post_id = null, $preview = false, $force_form_count = false, $specific_post_id = null) {
        global $post, $_post_to_create, $_current_post;

        // reference to the form submission method
        global ${'_' . StaticClass::METHOD};
        $method = & ${'_' . StaticClass::METHOD};

        // get post inputs
        if (isset($post_id) && !empty($post_id) && null !== $post_id && false !== $post_id && !$preview)
            $post_id = intval($post_id);
        elseif (isset($post->ID) && !$preview)
            $post_id = $post->ID;
        else
            $post_id = false;

        $formHelper = $this->_formHelper;

        // get recaptcha settings
        StaticClass::$_staticGlobal['RECAPTCHA'] = $formHelper->getRecaptchaSettings(StaticClass::$_staticGlobal['RECAPTCHA']);

        $form = $this->_formData;

        $this->_post_ID = $post_id;
        // load form data
        //$this->_formData=$formHelper->loadForm($form_id, $preview);
        if ($formHelper->isError($form))
            return $form;

        $form_id = $form->getForm()->ID;
        $_fields = $form->getFields();
        $form_type = $_fields['form_settings']->form['type'];
        $post_type = $_fields['form_settings']->post['post_type'];

        // if this is an edit form and no post id given
        if ((('edit' == $form_type && false === $post_id && !$preview) ||
                (isset($_GET['action']) && $_GET['action'] == 'edit_translation' && 'translation' == $form_type)) &&
                false === $post_id && !$preview) {
            return $formHelper->error(__('No post specified', 'wp-cred'));
        }

        // if this is a new form or preview
        if ('new' == $form_type || $preview ||
                (isset($_GET['action']) && $_GET['action'] == 'create_translation' && 'translation' == $form_type) || $preview) {
            //if (isset($post_id)&&!empty($post_id)) $_post_to_create = $post_id;
            // always get new dummy id, to avoid the issue of editing the post on browser back button
            //$post_id=get_default_post_to_edit( $post_type, true )->ID;
            if (!isset($_post_to_create) || empty($_post_to_create)) {
                //Fix
                global $wpdb;
                $querystr = $wpdb->prepare("SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'auto-draft' AND $wpdb->posts.post_type = %s ORDER by ID desc Limit 1", $post_type);
                $_myposts = $wpdb->get_results($querystr, OBJECT);
                //Fix https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/192489607/comments
                if (!empty($_myposts)) {
                    $mypost = get_post($_myposts[0]->ID);
                    $mypost->post_title = 'Auto Draft';
                    $mypost->post_content = '';
                } else {
                    //$post_id
                    //Fixed https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/192489607/comments
                    $mypost = get_default_post_to_edit($post_type, true);
                    //################################################################################################
                }
                $_post_to_create = $mypost->ID;
                $this->_post_ID = $_post_to_create;

                //Fix https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193427659/comments#303699314
                //If post_id is not null and is not auto-draft means that the user used 
                //back arrow of the browser
                //So i set post_id with a new one
                if ($post_id != null) {
                    $mycheckpost = get_post($post_id);
                    if ($mycheckpost->post_status != 'auto-draft')
                        $post_id = $_post_to_create;
                }
                //#########################################################################
            } else {
                $this->_post_ID = $_post_to_create;
            }
        }

        // get existing post data if edit form and post given
        if ((('edit' == $form_type && !$preview) ||
                (isset($_GET['action']) && $_GET['action'] == 'edit_translation' && 'translation' == $form_type)) && !$preview) {
            if ($_current_post == 0) {
                $_current_post = $post_id;
            }

            $is_current_edit = false;
            if (is_null($specific_post_id) && $_current_post > 0) {
                $is_current_edit = true;
                $post_id = $_current_post;
            }

            $this->_post_ID = $post_id;

            //$this->_postData=$formHelper->getPostData($post_id);
            require_once "PostData.php";
            $postdata = new PostData();
            $this->_postData = $postdata->getPostData($post_id);

            if ($is_current_edit) {
                $post_type = $this->_postData->post->post_type;
            }

            if ($formHelper->isError($this->_postData))
                return $this->_postData;

            if (!$is_current_edit && $this->_postData->post->post_type != $post_type) {
                return $formHelper->error(__('Form type and post type do not match', 'wp-cred'));
            }
        } else {
            //Fixed https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/186563662/comments
            //conditional not working after failed submition
            //$this->_post_ID=$post_id;
            //removed creates side effect to other stuff !
        }

        // check if user has access to this form
        if (!$preview && !$formHelper->checkFormAccess($form_type, $form_id, $this->_postData)) {
            return $formHelper->error();
        }

        // Deprecated
        // set allowed file types      
        StaticClass::$_staticGlobal['MIMES'] = $formHelper->getAllowedMimeTypes();

        // get custom post fields
        $fields_settings = $formHelper->getFieldSettings($post_type);

        // instantiate Zebra Form
        if (false !== $force_form_count)
            $form_count = $force_form_count;
        else
            $form_count = StaticClass::$_globalStatic['COUNT'];

        // strip any unneeded parsms from current uri
        $actionUri = $formHelper->currentURI(array(
            '_tt' => time()       // add time get bypass cache
                ), array(
            '_success', // remove previous success get if set
            '_success_message'   // remove previous success get if set
        ));

        $prg_form_id = $formHelper->createPrgID($form_id, $form_count);
        $my_form_id = $formHelper->createFormID($form_id, $form_count);

        $this->_zebraForm = new CredForm($my_form_id, $form_type, $post_id, $actionUri, $preview);
        $this->_zebraForm->setLanguage(StaticClass::$_staticGlobal['LOCALES']);


        if ($formHelper->isError($this->_zebraForm)) {
            return $this->_zebraForm;
        }

        // all fine here        
        $this->_postType = $post_type;
        $this->_content = $form->getForm()->post_content;

        $this->out_['fields'] = $fields_settings;
        $this->out_['count'] = $form_count;
        $this->out_['prg_id'] = $prg_form_id;

        return true;
    }

    private function parseUserInputs($form_id, &$post_id = null, $preview = false, $force_form_count = false, $specific_post_id = null) {
        global $post, $_post_to_create, $_current_post;

        // reference to the form submission method
        global ${'_' . StaticClass::METHOD};
        $method = & ${'_' . StaticClass::METHOD};

        // get post inputs
        if (isset($post_id) && !empty($post_id) && null !== $post_id && false !== $post_id && !$preview)
            $post_id = intval($post_id);
        elseif (isset($post->ID) && !$preview)
            $post_id = $post->ID;
        else
            $post_id = false;

        $formHelper = $this->_formHelper;

        // get recaptcha settings
        StaticClass::$_staticGlobal['RECAPTCHA'] = $formHelper->getRecaptchaSettings(StaticClass::$_staticGlobal['RECAPTCHA']);

        $form = $this->_formData;

        $this->_post_ID = $post_id;
        // load form data
        //$this->_formData=$formHelper->loadForm($form_id, $preview);
        if ($formHelper->isError($form))
            return $form;

        $form_id = $form->getForm()->ID;
        $_fields = $form->getFields();
        $form_type = $_fields['form_settings']->form['type'];
        $post_type = $_fields['form_settings']->post['post_type'];

        // if this is an edit form and no post id given
        if ((('edit' == $form_type && false === $post_id && !$preview) ||
                (isset($_GET['action']) && $_GET['action'] == 'edit_translation' && 'translation' == $form_type)) &&
                false === $post_id && !$preview) {
            return $formHelper->error(__('No post specified', 'wp-cred'));
        }

        // get existing post data if edit form and post given
        if ((('edit' == $form_type && !$preview) ||
                (isset($_GET['action']) && $_GET['action'] == 'edit_translation' && 'translation' == $form_type)) &&
                !$preview) {
            if ($_current_post == 0) {
                $_current_post = $post_id;
            }

            $is_current_edit = false;
            if (is_null($specific_post_id) && $_current_post > 0) {
                $is_current_edit = true;
                $post_id = $_current_post;
            }

            $this->_post_ID = $post_id;

            //$this->_postData=$formHelper->getPostData($post_id);
            require_once "PostData.php";
            $postdata = new PostData();
            $this->_postData = $postdata->getUserData($post_id);

            if ($formHelper->isError($this->_postData))
                return $this->_postData;

            if ($is_current_edit) {
                $post_type = $this->_postData->post->post_type;
            }

            if (!$is_current_edit && $this->_postData->post->post_type != $post_type) {
                return $formHelper->error(__('User Form type and post type do not match', 'wp-cred'));
            }
        } else {
            //Fixed https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/186563662/comments
            //conditional not working after failed submition
            //$this->_post_ID=$post_id;
            //removed creates side effect to other stuff !
        }

        // check if user has access to this form
        if (!$preview && !$formHelper->checkUserFormAccess($form_type, $form_id, $this->_postData)) {
            return $formHelper->error();
        }

        // Deprecated
        // set allowed file types      
        StaticClass::$_staticGlobal['MIMES'] = $formHelper->getAllowedMimeTypes();

        // get custom post fields
        $fields_settings = $formHelper->getFieldSettings($post_type);

        // instantiate Zebra Form
        if (false !== $force_form_count)
            $form_count = $force_form_count;
        else
            $form_count = StaticClass::$_globalStatic['COUNT'];

        // strip any unneeded parsms from current uri
        $actionUri = $formHelper->currentURI(array(
            '_tt' => time()       // add time get bypass cache
                ), array(
            '_success', // remove previous success get if set
            '_success_message'   // remove previous success get if set
        ));

        $prg_form_id = $formHelper->createPrgID($form_id, $form_count);
        $my_form_id = $formHelper->createFormID($form_id, $form_count);

        $this->_zebraForm = new CredForm($my_form_id, $form_type, $post_id, $actionUri, $preview);
        $this->_zebraForm->setLanguage(StaticClass::$_staticGlobal['LOCALES']);

        if ($formHelper->isError($this->_zebraForm)) {
            return $this->_zebraForm;
        }

        // all fine here        
        $this->_postType = $post_type;
        $this->_content = $form->getForm()->post_content;

        $this->out_['fields'] = $fields_settings;
        $this->out_['count'] = $form_count;
        $this->out_['prg_id'] = $prg_form_id;

        return true;
    }

    private function CRED_build() {

        // get refs here
        $out_ = &$this->out_;
        $formHelper = $this->_formHelper;
        $shortcodeParser = $this->_shortcodeParser;

        $zebraForm = $this->_zebraForm;
        $zebraForm->out_ = &$out_;
        $zebraForm->_shortcodeParser = $shortcodeParser;
        $zebraForm->_formHelper = $formHelper;
        $zebraForm->_formData = $this->_formData;
        $zebraForm->_post_ID = $this->_post_ID;

        if ($zebraForm->preview)
            $preview_content = $this->_content;

        // remove any HTML comments before parsing, allow to comment-out parts
        $this->_content = $shortcodeParser->removeHtmlComments($this->_content);
        // do WP shortcode here for final output, moved here to avoid replacing post_content
        // call wpv_do_shortcode instead to fix render wpv shortcodes inside other shortcodes
        $this->_content = apply_filters('cred_content_before_do_shortcode', $this->_content);

        //New CRED shortcode to retrieve current container post_id
        if (isset(StaticClass::$_cred_container_id))
            $this->_content = str_replace("[cred-container-id]", StaticClass::$_cred_container_id, $this->_content);

        //_pre($this->_content);
        if (function_exists('wpv_do_shortcode')) {
            $this->_content = wpv_do_shortcode($this->_content);
        } else {
            $this->_content = do_shortcode($this->_content);
        }

        // parse all shortcodes internally
        $shortcodeParser->remove_all_shortcodes();
        $shortcodeParser->add_shortcode('credform', array(&$zebraForm, 'cred_form_shortcode'));
        $this->_content = $shortcodeParser->do_shortcode($this->_content);
        $shortcodeParser->remove_shortcode('credform', array(&$zebraForm, 'cred_form_shortcode'));

        // add any custom attributes eg class
        /* if (
          isset($zebraForm->form_properties['attributes'])
          && is_array($zebraForm->form_properties['attributes'])
          && !empty($zebraForm->form_properties['attributes'])
          )
          $zebraForm->form_properties['attributes']=array_merge($zebraForm->form_properties['attributes'],
          $this->_attributes);
          else
          $zebraForm->form_properties['attributes']=$this->_attributes; */

        // render any external third-party shortcodes first (enables using shortcodes as values to cred shortcodes)
        $zebraForm->_form_content = do_shortcode($zebraForm->_form_content);

        // build shortcodes, (backwards compatibility, render first old shortcode format with dashes)
        $shortcodeParser->add_shortcode('cred-field', array(&$zebraForm, 'cred_field_shortcodes'));
        $shortcodeParser->add_shortcode('cred-generic-field', array(&$zebraForm, 'cred_generic_field_shortcodes'));
        $shortcodeParser->add_shortcode('cred-show-group', array(&$zebraForm, 'cred_conditional_shortcodes'));

        // build shortcodes, render new shortcode format with underscores        
        $shortcodeParser->add_shortcode('cred_field', array(&$zebraForm, 'cred_field_shortcodes'));
        $shortcodeParser->add_shortcode('cred_generic_field', array(&$zebraForm, 'cred_generic_field_shortcodes'));
        $shortcodeParser->add_shortcode('cred_show_group', array(&$zebraForm, 'cred_conditional_shortcodes'));
        $out_['child_groups'] = array();
        //$this->_form_content=$shortcodeParser->do_recursive_shortcode('cred-show-group', $this->_form_content);
        $zebraForm->_form_content = $shortcodeParser->do_recursive_shortcode('cred_show_group', $zebraForm->_form_content);
        $out_['child_groups'] = array();

        /* Watch out for Toolset forms library in commons outputting HTML before header()
         * In the do_shortcode parser
         * https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/185336518/comments#282283111
         */
        $zebraForm->_form_content = $shortcodeParser->do_shortcode($zebraForm->_form_content);
        $shortcodeParser->remove_shortcode('cred_show_group', array(&$zebraForm, 'cred_conditional_shortcodes'));
        $shortcodeParser->remove_shortcode('cred_generic_field', array(&$zebraForm, 'cred_generic_field_shortcodes'));
        $shortcodeParser->remove_shortcode('cred_field', array(&$zebraForm, 'cred_field_shortcodes'));

        $shortcodeParser->remove_shortcode('cred-show-group', array(&$zebraForm, 'cred_conditional_shortcodes'));
        $shortcodeParser->remove_shortcode('cred-generic-field', array(&$zebraForm, 'cred_generic_field_shortcodes'));
        $shortcodeParser->remove_shortcode('cred-field', array(&$zebraForm, 'cred_field_shortcodes'));

        // add some auxilliary fields to form
        // add nonce hidden field
        //$nonceobj=$zebraForm->add('hidden', StaticClass::NONCE, wp_create_nonce($zebraForm->form_properties['name']), array('style'=>'display:none;'));
        if (is_user_logged_in())
            $nonceobj = $zebraForm->add2form_content('hidden', StaticClass::NONCE, wp_create_nonce($zebraForm->form_properties['name']), array('style' => 'display:none;'));
        //$out_['nonce_field']=$nonce_field;
        // add post_id hidden field
        if ($this->_post_ID) {
            $post_id_obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'post_id', $this->_post_ID, array('style' => 'display:none;'));
            //$out_['post_id_field']=$post_id_obj->attributes['id'];
        }

        if (isset(StaticClass::$_cred_container_id))
            $cred_container_id_obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'cred_container_id', StaticClass::$_cred_container_id, array('style' => 'display:none;'));

        // add to form
        $_fields = $this->_formData->getFields();
        $form_type = $_fields['form_settings']->form['type'];
        $form_id = $this->_formData->getForm()->ID;
        $form_count = $out_['count'];
        $post_type = $_fields['form_settings']->post['post_type'];
        //$post_type=$this->_postType;

        if ($zebraForm->preview) {
            // add temporary content for form preview
            //$obj=$zebraForm->add('textarea', StaticClass::PREFIX.'form_preview_content', $preview_content, array('style'=>'display:none;'));
            $zebraForm->add2form_content('textarea', StaticClass::PREFIX . 'form_preview_content', $preview_content, array('style' => 'display:none;'));
            // add temporary content for form preview (not added automatically as there is no shortcode to render this)
            //$this->_form_content.=$obj->toHTML();
            // hidden fields are rendered automatically
            //$obj=$zebraForm->add('hidden',StaticClass::PREFIX.'form_preview_post_type', $post_type, array('style'=>'display:none;'));
            $obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'form_preview_post_type', $post_type, array('style' => 'display:none;'));
            //$obj=$zebraForm->add('hidden',StaticClass::PREFIX.'form_preview_form_type', $form_type, array('style'=>'display:none;'));
            $obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'form_preview_form_type', $form_type, array('style' => 'display:none;'));

            if ($_fields['form_settings']->form['has_media_button']) {
                //$zebraForm->add_form_error('preview_media', __('Media Upload will not work with form preview','wp-cred'));
                $zebraForm->add_field_message(__('Media Upload will not work with form preview', 'wp-cred'));
            }

            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/195892843/comments#309778558
            //Created a separated preview messages
            //$zebraForm->add_form_message('preview_mode', __('Form Preview Mode','wp-cred'));
            $zebraForm->add_preview_message(__('Form Preview Mode', 'wp-cred'));
        }
        // hidden fields are rendered automatically
        // add form id
        //$obj=$zebraForm->add('hidden', StaticClass::PREFIX.'form_id', $form_id, array('style'=>'display:none;'));
        $obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'form_id', $form_id, array('style' => 'display:none;'));
        //$out_['form_id_field']=$obj->attributes['id'];
        // add form count
        //$obj=$zebraForm->add('hidden', StaticClass::PREFIX.'form_count', $form_count, array('style'=>'display:none;'));
        $obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'form_count', $form_count, array('style' => 'display:none;'));
        //$out_['form_count_field']=$obj->attributes['id'];
        // check conditional expressions for javascript
        //$formHelper->parseConditionalExpressions($out_);

        if (!empty(StaticClass::$_mail_error)) {
            echo '<label id="lbl_generic" class="wpt-form-error">' . StaticClass::$_mail_error . "</label>";
            StaticClass::$_mail_error = "";
            delete_option('_' . $form_id . '_last_mail_error');
        }

        // Set cache variable for all forms ( Custom JS)
        $js_content = $_fields['extra']->js;
        if (!empty($js_content)) {
            $custom_js_cache = wp_cache_get('cred_custom_js_cache');
            if (false === $custom_js_cache) {
                $custom_js_cache = '';
            }
            $custom_js_cache .= "\n\n" . $js_content;
            wp_cache_set('cred_custom_js_cache', $custom_js_cache);
        }

        // Set cache variable for all forms ( Custom CSS)
        $css_content = $_fields['extra']->css;
        if (!empty($css_content)) {
            $custom_css_cache = wp_cache_get('cred_custom_css_cache');
            if (false === $custom_css_cache) {
                $custom_css_cache = '';
            }
            $custom_css_cache .= "\n\n" . $css_content;
            wp_cache_set('cred_custom_css_cache', $custom_css_cache);
        }

        //$js_content = $_fields['extra']->js;
        //$zebraForm->addJsFormContent($js_content);
        //$css_content = $js_content = $_fields['extra']->css;
    }

    private function CRED_User_build() {
        // get refs here
        $out_ = &$this->out_;
        $formHelper = $this->_formHelper;
        $shortcodeParser = $this->_shortcodeParser;

        $zebraForm = $this->_zebraForm;
        $zebraForm->out_ = &$out_;
        $zebraForm->_shortcodeParser = $shortcodeParser;
        $zebraForm->_formHelper = $formHelper;
        $zebraForm->_formData = $this->_formData;
        $zebraForm->_post_ID = $this->_post_ID;

        if ($zebraForm->preview)
            $preview_content = $this->_content;

        // remove any HTML comments before parsing, allow to comment-out parts
        $this->_content = $shortcodeParser->removeHtmlComments($this->_content);
        // do WP shortcode here for final output, moved here to avoid replacing post_content
        // call wpv_do_shortcode instead to fix render wpv shortcodes inside other shortcodes
        $this->_content = apply_filters('cred_content_before_do_shortcode', $this->_content);

        //New CRED shortcode to retrieve current container post_id
        if (isset(StaticClass::$_cred_container_id))
            $this->_content = str_replace("[cred-container-id]", StaticClass::$_cred_container_id, $this->_content);

        //_pre($this->_content);
        if (function_exists('wpv_do_shortcode')) {
            $this->_content = wpv_do_shortcode($this->_content);
        } else {
            $this->_content = do_shortcode($this->_content);
        }

        // parse all shortcodes internally
        $shortcodeParser->remove_all_shortcodes();
        $shortcodeParser->add_shortcode('creduserform', array(&$zebraForm, 'cred_user_form_shortcode'));
        $this->_content = $shortcodeParser->do_shortcode($this->_content);
        $shortcodeParser->remove_shortcode('creduserform', array(&$zebraForm, 'cred_user_form_shortcode'));

        // add any custom attributes eg class
        /* if (
          isset($zebraForm->form_properties['attributes'])
          && is_array($zebraForm->form_properties['attributes'])
          && !empty($zebraForm->form_properties['attributes'])
          )
          $zebraForm->form_properties['attributes']=array_merge($zebraForm->form_properties['attributes'],
          $this->_attributes);
          else
          $zebraForm->form_properties['attributes']=$this->_attributes; */

        // render any external third-party shortcodes first (enables using shortcodes as values to cred shortcodes)
        $zebraForm->_form_content = do_shortcode($zebraForm->_form_content);

        // build shortcodes, (backwards compatibility, render first old shortcode format with dashes)
        $shortcodeParser->add_shortcode('cred-field', array(&$zebraForm, 'cred_field_shortcodes'));
        $shortcodeParser->add_shortcode('cred-generic-field', array(&$zebraForm, 'cred_generic_field_shortcodes'));
        $shortcodeParser->add_shortcode('cred-show-group', array(&$zebraForm, 'cred_conditional_shortcodes'));

        // build shortcodes, render new shortcode format with underscores        
        $shortcodeParser->add_shortcode('cred_field', array(&$zebraForm, 'cred_field_shortcodes'));
        $shortcodeParser->add_shortcode('cred_generic_field', array(&$zebraForm, 'cred_generic_field_shortcodes'));
        $shortcodeParser->add_shortcode('cred_show_group', array(&$zebraForm, 'cred_conditional_shortcodes'));
        $out_['child_groups'] = array();
        //$this->_form_content=$shortcodeParser->do_recursive_shortcode('cred-show-group', $this->_form_content);
        $zebraForm->_form_content = $shortcodeParser->do_recursive_shortcode('cred_show_group', $zebraForm->_form_content);
        $out_['child_groups'] = array();

        /* Watch out for Toolset forms library in commons outputting HTML before header()
         * In the do_shortcode parser
         * https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/185336518/comments#282283111
         */
        $zebraForm->_form_content = $shortcodeParser->do_shortcode($zebraForm->_form_content);
        $shortcodeParser->remove_shortcode('cred_show_group', array(&$zebraForm, 'cred_conditional_shortcodes'));
        $shortcodeParser->remove_shortcode('cred_generic_field', array(&$zebraForm, 'cred_generic_field_shortcodes'));
        $shortcodeParser->remove_shortcode('cred_field', array(&$zebraForm, 'cred_field_shortcodes'));

        $shortcodeParser->remove_shortcode('cred-show-group', array(&$zebraForm, 'cred_conditional_shortcodes'));
        $shortcodeParser->remove_shortcode('cred-generic-field', array(&$zebraForm, 'cred_generic_field_shortcodes'));
        $shortcodeParser->remove_shortcode('cred-field', array(&$zebraForm, 'cred_field_shortcodes'));

        // add some auxilliary fields to form
        // add nonce hidden field
        //$nonceobj=$zebraForm->add('hidden', StaticClass::NONCE, wp_create_nonce($zebraForm->form_properties['name']), array('style'=>'display:none;'));
        if (is_user_logged_in())
            $nonceobj = $zebraForm->add2form_content('hidden', StaticClass::NONCE, wp_create_nonce($zebraForm->form_properties['name']), array('style' => 'display:none;'));
        //$out_['nonce_field']=$nonce_field;
        // add post_id hidden field
        if ($this->_post_ID) {
            $post_id_obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'post_id', $this->_post_ID, array('style' => 'display:none;'));
            //$out_['post_id_field']=$post_id_obj->attributes['id'];
        }

        if (isset(StaticClass::$_cred_container_id))
            $cred_container_id_obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'cred_container_id', StaticClass::$_cred_container_id, array('style' => 'display:none;'));

        // add to form
        $_fields = $this->_formData->getFields();
        $form_type = $_fields['form_settings']->form['type'];
        $form_id = $this->_formData->getForm()->ID;
        $form_count = $out_['count'];
        $post_type = $_fields['form_settings']->post['post_type'];
        //$post_type=$this->_postType;

        if ($zebraForm->preview) {
            // add temporary content for form preview
            //$obj=$zebraForm->add('textarea', StaticClass::PREFIX.'form_preview_content', $preview_content, array('style'=>'display:none;'));
            $zebraForm->add2form_content('textarea', StaticClass::PREFIX . 'form_preview_content', $preview_content, array('style' => 'display:none;'));
            // add temporary content for form preview (not added automatically as there is no shortcode to render this)
            //$this->_form_content.=$obj->toHTML();
            // hidden fields are rendered automatically
            //$obj=$zebraForm->add('hidden',StaticClass::PREFIX.'form_preview_post_type', $post_type, array('style'=>'display:none;'));
            $obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'form_preview_post_type', $post_type, array('style' => 'display:none;'));
            //$obj=$zebraForm->add('hidden',StaticClass::PREFIX.'form_preview_form_type', $form_type, array('style'=>'display:none;'));
            $obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'form_preview_form_type', $form_type, array('style' => 'display:none;'));

            if ($_fields['form_settings']->form['has_media_button']) {
                //$zebraForm->add_form_error('preview_media', __('Media Upload will not work with form preview','wp-cred'));
                $zebraForm->add_field_message(__('Media Upload will not work with form preview', 'wp-cred'));
            }

            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/195892843/comments#309778558
            //Created a separated preview messages
            //$zebraForm->add_form_message('preview_mode', __('Form Preview Mode','wp-cred'));
            $zebraForm->add_preview_message(__('Form Preview Mode', 'wp-cred'));
        }
        // hidden fields are rendered automatically
        // add form id
        //$obj=$zebraForm->add('hidden', StaticClass::PREFIX.'form_id', $form_id, array('style'=>'display:none;'));
        $obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'form_id', $form_id, array('style' => 'display:none;'));
        //$out_['form_id_field']=$obj->attributes['id'];
        // add form count
        //$obj=$zebraForm->add('hidden', StaticClass::PREFIX.'form_count', $form_count, array('style'=>'display:none;'));
        $obj = $zebraForm->add2form_content('hidden', StaticClass::PREFIX . 'form_count', $form_count, array('style' => 'display:none;'));
        //$out_['form_count_field']=$obj->attributes['id'];
        // check conditional expressions for javascript
        //$formHelper->parseConditionalExpressions($out_);

        if (!empty(StaticClass::$_mail_error)) {
            echo '<label id="lbl_generic" class="wpt-form-error">' . StaticClass::$_mail_error . "</label>";
            StaticClass::$_mail_error = "";
            delete_option('_' . $form_id . '_last_mail_error');
        }

        // Set cache variable for all forms ( Custom JS)
        $js_content = $_fields['extra']->js;
        if (!empty($js_content)) {
            $custom_js_cache = wp_cache_get('cred_custom_js_cache');
            if (false === $custom_js_cache) {
                $custom_js_cache = '';
            }
            $custom_js_cache .= "\n\n" . $js_content;
            wp_cache_set('cred_custom_js_cache', $custom_js_cache);
        }

        // Set cache variable for all forms ( Custom CSS)
        $css_content = $_fields['extra']->css;
        if (!empty($css_content)) {
            $custom_css_cache = wp_cache_get('cred_custom_css_cache');
            if (false === $custom_css_cache) {
                $custom_css_cache = '';
            }
            $custom_css_cache .= "\n\n" . $css_content;
            wp_cache_set('cred_custom_css_cache', $custom_css_cache);
        }

        //$js_content = $_fields['extra']->js;
        //$zebraForm->addJsFormContent($js_content);
        //$css_content = $js_content = $_fields['extra']->css;
    }

    // check if submitted
    private function isSubmitted() {
        return ($this->_zebraForm->isSubmitted());
    }

    // validate form
    private function validate(&$error_files) {
        // reference to the form submission method
        global ${'_' . StaticClass::METHOD};
        $method = & ${'_' . StaticClass::METHOD};

        $form = $this->_formData;
        $formHelper = $this->_formHelper;
        $zebraForm = $this->_zebraForm;

        $result = false;
        $more_result = true;
        if ($zebraForm->isSubmitted()) {
            $result = empty($error_files);
            // verify that some data got passed
            if (empty($method)) {
                // This happens when the form is submitted but no data was posted
                // We are trying to upload a file greater then the maximum allowed size
                // So we should display a custom error
                //$zebraForm->add_form_error('security', $formHelper->getLocalisedMessage('no_data_submitted'));
                $zebraForm->add_top_message($formHelper->getLocalisedMessage('no_data_submitted'));
                $zebraForm->add_field_message($formHelper->getLocalisedMessage('no_data_submitted'));
                $result = false;
                return $result;
            }

            // verify nonce field
            if (is_user_logged_in()) {
                if (!array_key_exists(StaticClass::NONCE, $method) ||
                        !wp_verify_nonce($method[StaticClass::NONCE], $zebraForm->form_properties['name'])) {
                    //$zebraForm->add_form_error('security', $formHelper->getLocalisedMessage('invalid_form_submission'));
                    $zebraForm->add_top_message($formHelper->getLocalisedMessage('invalid_form_submission'));
                    $zebraForm->add_field_message($formHelper->getLocalisedMessage('invalid_form_submission'));
                    $result = false;
                    return $result;
                }
            }

            if (isset($_POST['_recaptcha'])) {
                //add check control to cred form if has recaptcha
                if
                (
                        (isset($_POST["recaptcha_challenge_field"]) && !empty($_POST["recaptcha_challenge_field"])) &&
                        (isset($_POST["recaptcha_response_field"]) && !empty($_POST["recaptcha_response_field"]))
                ) {
                    require_once ( WPTOOLSET_FORMS_ABSPATH . "/js/recaptcha-php-1.11/recaptchalib.php");
                    $settings_model = CRED_Loader::get('MODEL/Settings');
                    $settings = $settings_model->getSettings();
                    $publickey = $settings['recaptcha']['public_key'];
                    $privatekey = $settings['recaptcha']['private_key'];

                    if (empty($privatekey) || empty($publickey)) {
                        //$zebraForm->add_form_error('security', $formHelper->getLocalisedMessage('no_recaptcha_keys'));
                        $zebraForm->add_top_message($formHelper->getLocalisedMessage('no_recaptcha_keys'));
                        $zebraForm->add_field_message($formHelper->getLocalisedMessage('no_recaptcha_keys'));
                        $result = false;
                        //return $result;
                    } else {

                        $rcfield = (!empty($_POST["recaptcha_challenge_field"])) ? sanitize_text_field($_POST["recaptcha_challenge_field"]) : "";
                        $rrfield = (!empty($_POST["recaptcha_response_field"])) ? sanitize_text_field($_POST["recaptcha_response_field"]) : "";
                        $resp = recaptcha_check_answer($privatekey, $_SERVER["REMOTE_ADDR"], $rcfield, $rrfield);

                        if (!$resp->is_valid) {
                            //$zebraForm->add_form_error('security', $formHelper->getLocalisedMessage('enter_valid_captcha'));
                            $zebraForm->add_top_message($formHelper->getLocalisedMessage('enter_valid_captcha'));
                            $zebraForm->add_field_message($formHelper->getLocalisedMessage('enter_valid_captcha'));
                            $result = false;
                            //return $result;
                        }
                    }
                } else {
                    if (empty($_POST['recaptcha_response_field'])) {
                        $zebraForm->add_top_message($formHelper->getLocalisedMessage('enter_valid_captcha'));
                        $zebraForm->add_field_message($formHelper->getLocalisedMessage('enter_valid_captcha'));
                        $result = false;
                        //return $result;
                    }
                }
            }

            // get values
            $_fields = $form->getFields();
            $form_id = $form->getForm()->ID;
            $form_type = $_fields['form_settings']->form['type'];
            $post_type = $_fields['form_settings']->post['post_type'];

            $is_user_form = false;
            if ($form->getForm()->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) {
                $is_user_form = true;
            }

            if ($is_user_form) {

                if (isset($_POST['user_pass'])) {
                    if ($form_type == 'edit' && empty($_POST['user_pass']) && empty($_POST['user_pass2'])) {
                        //Fixing https://onthegosystems.myjetbrains.com/youtrack/issue/cred-161
                        unset($_POST['user_pass']);
                        unset($_POST['user_pass2']);
                    }
                }


                if (isset($_POST['user_pass'])) {
                    if (empty($_POST['user_pass']) ||
                            (!empty($_POST['user_pass']) &&
                            $_POST['user_pass'] != $_POST['user_pass2'])) {
                        $zebraForm->add_top_message('Passwords do not match');
                        $zebraForm->add_field_message('Password: Has to match with the Repeat Password field', 'user_pass');
                        $zebraForm->add_field_message('Repeat Password: Has to match with the Password field', 'user_pass2');
                        $result = false;
                    }
                }

                if ($form_type == 'edit') {
                    $user_id_to_edit = $_POST[StaticClass::PREFIX . 'post_id'];

                    $_user = new WP_User($user_id_to_edit);
                    $user_role_to_edit = strtolower($_user->roles[0]);
                    $user_role_can_edit = json_decode($_fields['form_settings']->form['user_role'], true);
                    if (!empty($user_role_can_edit) && !in_array($user_role_to_edit, $user_role_can_edit)) {
                        $zebraForm->add_top_message('You can edit only users with following roles: <b>' . implode(", ", $user_role_can_edit) . '</b>');
                        $result = false;
                    }
                }
            }

            $thisform = array(
                'id' => $form_id,
                'post_type' => $post_type,
                'form_type' => $form_type
            );
            $fields = $formHelper->get_form_field_values();
            $zebraForm->set_submitted_values($fields);

            $errors = array();
            //From CRED 1.2.6
            $form_slug = $form->getForm()->post_name;
            list($fields, $errors) = apply_filters('cred_form_validate_form_' . $form_slug, array($fields, $errors), $thisform);
            list($fields, $errors) = apply_filters('cred_form_validate_' . $form_id, array($fields, $errors), $thisform);
            list($fields, $errors) = apply_filters('cred_form_validate', array($fields, $errors), $thisform);

            if (!empty($errors)) {
                //Added result to fix conditional elements of this todo
                //Notice: Undefined index: cred_form_6_1_wysiwyg-field in with validation hook
                //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/189015783/comments
                $more_result = true;
                foreach ($errors as $fname => $err) {
                    if ($form->getForm()->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) {
                        if (strpos($fname, "wpcf-") !== false) {
                            $ofname = $fname;
                            $fname = str_replace("wpcf-", "", $fname);
                        }

                        if (array_key_exists($fname, $this->out_['form_fields']) ||
                                array_key_exists($ofname, $this->out_['form_fields'])) {
                            //Added result to fix conditional elements of this todo
                            //Notice: Undefined index: cred_form_6_1_wysiwyg-field in with validation hook
                            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/189015783/comments
                            if (isset($this->out_['fields']['user_fields'][$fname]) &&
                                    isset($this->out_['fields']['user_fields'][$fname]['plugin_type_prefix'])) {
                                $tmp = $this->out_['fields']['user_fields'][$fname]['plugin_type_prefix'] . $fname;
                                //Fixed issues on images validation validation i forgot to check $_FILES
                                //Fixed the same for checkboxes/checkbox/radio
                                if (
                                        ($this->out_['fields']['user_fields'][$fname]['type'] != 'checkboxes' &&
                                        $this->out_['fields']['user_fields'][$fname]['type'] != 'checkbox' &&
                                        $this->out_['fields']['user_fields'][$fname]['type'] != 'radio') &&
                                        !isset($_POST[$tmp]) && !isset($_FILES[$tmp])) {
                                    continue;
                                }
                            }
                            //##########################################################################################                            
                            //Fix of cred_form_validate_form_'.$form_slug doesn't work
                            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/188882358/comments
                            $myfname = isset($this->out_['fields']['form_fields'][$fname]['post_labels']) ? $this->out_['fields']['form_fields'][$fname]['post_labels'] : (isset($this->out_['fields']['custom_fields'][$fname]['name']) ? $this->out_['fields']['custom_fields'][$fname]['name'] : (isset($this->out_['fields']['extra_fields'][$fname]['name']) ? $this->out_['fields']['extra_fields'][$fname]['name'] : $fname));
                            $zebraForm->add_top_message($myfname . ": " . $err);
                            //$zebraForm->controls[$this->out_['form_fields'][$fname][0]]->addError($err);
                            //############################################################
                            $more_result = false;
                        }
                    } else {
                        if (array_key_exists($fname, $this->out_['form_fields'])) {
                            //Added result to fix conditional elements of this todo
                            //Notice: Undefined index: cred_form_6_1_wysiwyg-field in with validation hook
                            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/189015783/comments
                            if (isset($this->out_['fields']['post_fields'][$fname]) &&
                                    isset($this->out_['fields']['post_fields'][$fname]['plugin_type_prefix'])) {
                                $tmp = $this->out_['fields']['post_fields'][$fname]['plugin_type_prefix'] . $fname;
                                //Fixed issues on images validation validation i forgot to check $_FILES
                                //Fixed the same for checkboxes/checkbox/radio
                                if (
                                        ($this->out_['fields']['post_fields'][$fname]['type'] != 'checkboxes' &&
                                        $this->out_['fields']['post_fields'][$fname]['type'] != 'checkbox' &&
                                        $this->out_['fields']['post_fields'][$fname]['type'] != 'radio') &&
                                        !isset($_POST[$tmp]) && !isset($_FILES[$tmp])) {
                                    continue;
                                }
                            }
                            //##########################################################################################                            
                            //Fix of cred_form_validate_form_'.$form_slug doesn't work
                            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/188882358/comments
                            $myfname = isset($this->out_['post_fields'][$fname]['name']) ? $this->out_['post_fields'][$fname]['name'] : (isset($this->out_['fields']['custom_fields'][$fname]['name']) ? $this->out_['fields']['custom_fields'][$fname]['name'] : (isset($this->out_['fields']['extra_fields'][$fname]['name']) ? $this->out_['fields']['extra_fields'][$fname]['name'] : $fname));
                            $zebraForm->add_top_message($myfname . ": " . $err);
                            //$zebraForm->controls[$this->out_['form_fields'][$fname][0]]->addError($err);
                            //############################################################
                            $more_result = false;
                        }
                    }
                }
            }
            $last_result = $zebraForm->validate($this->_post_ID, /* $formHelper->get_form_field_values() */ $zebraForm->form_properties['fields']);
            if (!$more_result || !$result || !$last_result)
                return false;
            return true;
        }
        return $result;
    }

    function wpml_save_post_lang($lang) {
        global $sitepress;
        if (isset($sitepress)) {
            if (empty($_POST['icl_post_language'])) {
                if (isset($_GET['lang'])) {
                    $lang = $_GET['lang'];
                } else {
                    $lang = $sitepress->get_current_language();
                }
            }
        }
        return $lang;
    }

    function terms_clauses($clauses) {
        global $sitepress;
        if (isset($sitepress)) {
            if (isset($_GET['source_lang'])) {
                $src_lang = $_GET['source_lang'];
            } else {
                $src_lang = $sitepress->get_current_language();
            }
            if (isset($_GET['lang'])) {
                $lang = sanitize_text_field($_GET['lang']);
            } else {
                $lang = $src_lang;
            }
            $clauses['where'] = str_replace("icl_t.language_code = '" . $src_lang . "'", "icl_t.language_code = '" . $lang . "'", $clauses['where']);
        }
        return $clauses;
    }

    /**
     * CRED_save
     * @param type $post_id
     * @return boolean
     */
    private function CRED_save($post_id = null) {
        // reference to the form submission method
        global ${'_' . StaticClass::METHOD};
        $method = & ${'_' . StaticClass::METHOD};

        $formHelper = $this->_formHelper;
        $zebraForm = $this->_zebraForm;
        $form = &$this->_formData;
        $form_id = $form->getForm()->ID;
        $_fields = $form->getFields();
        $form_type = $_fields['form_settings']->form['type'];

        $out_ = &$this->out_;

        $post_type = $this->_postType;

        $thisform = array(
            'id' => $form_id,
            'post_type' => $post_type,
            'form_type' => $form_type,
            'container_id' => StaticClass::$_cred_container_id,
        );

        // do custom actions before post save
        do_action('cred_before_save_data_' . $form_id, $thisform);
        do_action('cred_before_save_data', $thisform);

        // track form data for notification mail
        $trackNotification = false;
        if (
                isset($_fields['notification']->enable) &&
                $_fields['notification']->enable &&
                !empty($_fields['notification']->notifications)
        )
            $trackNotification = true;

        // save result (on success this is post ID)
        $new_post_id = false;

        // Check if we are posting nothing, in which case we are dealing with uploads greater than the size limit
        if (empty($method) && isset($_GET['_tt'])) {
            return $new_post_id;
        }

        // default post fields
        $post = $formHelper->CRED_extractPostFields($post_id, $trackNotification);

        // custom fields, taxonomies and file uploads; also, catch error_files for sizes lower than the server maximum but higher than the form/site maximum
        list($fields, $fieldsInfo, $taxonomies, $files, $removed_fields, $error_files) = $formHelper->CRED_extractCustomFields($post_id, $trackNotification);

        // upload attachments
        $extra_files = array();
        if (count($error_files) > 0) {
            $all_ok = false;
        } else {
            $all_ok = $formHelper->CRED_uploadAttachments($post_id, $fields, $files, $extra_files, $trackNotification);
        }

        if ($all_ok) {
            add_filter('terms_clauses', array(&$this, 'terms_clauses'));
            add_filter('wpml_save_post_lang', array(&$this, 'wpml_save_post_lang'));
            //add_filter('wpml_save_post_trid_value',array(&$this,'wpml_save_post_trid_value'),10,2);
            // save everything
            $model = CRED_Loader::get('MODEL/Forms');

            if (!isset($post->post_type) || empty($post->post_type))
                $post->post_type = $post_type;

            //https://onthegosystems.myjetbrains.com/youtrack/issue/cred-131#            
            $fields = StaticClass::cf_sanitize_values_on_save($fields);

            if (empty($post->ID)) {
                $new_post_id = $model->addPost($post, array('fields' => $fields, 'info' => $fieldsInfo, 'removed' => $removed_fields), $taxonomies);
            } else {
                $new_post_id = $model->updatePost($post, array('fields' => $fields, 'info' => $fieldsInfo, 'removed' => $removed_fields), $taxonomies);
            }

            //cred_log(array('fields'=>$fields, 'info'=>$fieldsInfo, 'removed'=>$removed_fields));
            if (is_int($new_post_id) && $new_post_id > 0) {
                $formHelper->attachUploads($new_post_id, $fields, $files, $extra_files);
                // save notification data (pre-formatted)
                if ($trackNotification)
                    $out_['notification_data'] = $formHelper->trackData(null, true);
                // for WooCommerce products only (update prices in products)
                if (class_exists('Woocommerce') && 'product' == get_post_type($new_post_id)) {
                    if (isset($fields['_regular_price']) && !isset($fields['_price'])) {
                        $regular_price = $fields['_regular_price'];
                        update_post_meta($new_post_id, '_price', $regular_price);
                        $sale_price = get_post_meta($new_post_id, '_sale_price', true);
                        // Update price if on sale
                        if ($sale_price != '') {
                            $sale_price_dates_from = get_post_meta($new_post_id, '_sale_price_dates_from', true);
                            $sale_price_dates_to = get_post_meta($new_post_id, '_sale_price_dates_to', true);
                            if ($sale_price_dates_to == '' && $sale_price_dates_to == '')
                                update_post_meta($new_post_id, '_price', $sale_price);
                            else if ($sale_price_dates_from && strtotime($sale_price_dates_from) < strtotime('NOW', current_time('timestamp')))
                                update_post_meta($new_post_id, '_price', $sale_price);
                            if ($sale_price_dates_to && strtotime($sale_price_dates_to) < strtotime('NOW', current_time('timestamp')))
                                update_post_meta($new_post_id, '_price', $regular_price);
                        }
                    }
                    else if (isset($fields['_price']) && !isset($fields['_regular_price'])) {
                        update_post_meta($new_post_id, '_regular_price', $fields['_price']);
                    }
                }

                // do custom actions on successful post save
                /* EMERSON: https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/185624661/comments
                  /*Add cred_save_data_form_ hook on CRED 1.3 */
                $form_slug = $form->getForm()->post_name;
                do_action('cred_save_data_form_' . $form_slug, $new_post_id, $thisform);
                do_action('cred_save_data_' . $form_id, $new_post_id, $thisform);
                do_action('cred_save_data', $new_post_id, $thisform);
            }
        } else {
            $WP_Error = new WP_Error();
            $WP_Error->add('upload', 'Error some required upload field failed.');
            $new_post_id = $WP_Error;
        }
        // return saved post_id as result
        return $new_post_id;
    }

    private function CRED_user_save($user_role, $user_id = null) {
        // reference to the form submission method
        global ${'_' . StaticClass::METHOD};
        $method = & ${'_' . StaticClass::METHOD};

        $formHelper = $this->_formHelper;
        $zebraForm = $this->_zebraForm;
        $form = &$this->_formData;
        $form_id = $form->getForm()->ID;
        $_fields = $form->getFields();
        $form_type = $_fields['form_settings']->form['type'];

        $out_ = &$this->out_;

        $post_type = $this->_postType;

        $thisform = array(
            'id' => $form_id,
            'post_type' => $post_type,
            'form_type' => $form_type,
            'container_id' => StaticClass::$_cred_container_id,
        );

        // do custom actions before post save
        do_action('cred_before_save_data_' . $form_id, $thisform);
        do_action('cred_before_save_data', $thisform);

        // track form data for notification mail
        $trackNotification = false;
        if (
                isset($_fields['notification']->enable) &&
                $_fields['notification']->enable &&
                !empty($_fields['notification']->notifications)
        )
            $trackNotification = true;

        // save result (on success this is post ID)
        $new_user_id = false;

        // Check if we are posting nothing, in which case we are dealing with uploads greater than the size limit
        if (empty($method) && isset($_GET['_tt'])) {
            return $new_user_id;
        }

        // default post fields
        $user = $formHelper->CRED_extractUserFields($user_id, $user_role, $trackNotification);

        $all_ok = false;
        if ($user)
            $all_ok = true;

        // custom fields, taxonomies and file uploads; also, catch error_files for sizes lower than the server maximum but higher than the form/site maximum
        list($fields, $fieldsInfo, $files, $removed_fields, $error_files) = $formHelper->CRED_extractCustomUserFields($user_id, $trackNotification);
        
        // upload attachments
        $extra_files = array();
        if (count($error_files) > 0) {
            $all_ok = false;
        } else {
            $all_ok = $formHelper->CRED_uploadAttachments($user->ID, $fields, $files, $extra_files, $trackNotification);
        }

        if ($all_ok) {
            add_filter('terms_clauses', array(&$this, 'terms_clauses'));
            add_filter('wpml_save_post_lang', array(&$this, 'wpml_save_post_lang'));

            //add_filter('wpml_save_post_trid_value',array(&$this,'wpml_save_post_trid_value'),10,2);
            //https://onthegosystems.myjetbrains.com/youtrack/issue/cred-131#            
            $fields = StaticClass::cf_sanitize_values_on_save($fields);

            // save everything
            $model = CRED_Loader::get('MODEL/UserForms');

            if ($form_type == 'edit' && isset($user_id)) {
                $new_user_id = $model->updateUser($user, $fields, $fieldsInfo, $removed_fields);
            } else {
                $new_user_id = $model->addUser($user, $fields, $fieldsInfo, $removed_fields);
            }

            //cred_log(array('fields'=>$fields, 'info'=>$fieldsInfo, 'removed'=>$removed_fields));
            if (is_int($new_user_id) && $new_user_id > 0) {
                $formHelper->attachUploads($new_user_id, $fields, $files, $extra_files);
                // save notification data (pre-formatted)
                if ($trackNotification)
                    $out_['notification_data'] = $formHelper->trackData(null, true);
                // for WooCommerce products only (update prices in products)
                if (class_exists('Woocommerce') && 'product' == get_post_type($new_user_id)) {
                    if (isset($fields['_regular_price']) && !isset($fields['_price'])) {
                        $regular_price = $fields['_regular_price'];
                        update_post_meta($new_user_id, '_price', $regular_price);
                        $sale_price = get_post_meta($new_user_id, '_sale_price', true);
                        // Update price if on sale
                        if ($sale_price != '') {
                            $sale_price_dates_from = get_post_meta($new_user_id, '_sale_price_dates_from', true);
                            $sale_price_dates_to = get_post_meta($new_user_id, '_sale_price_dates_to', true);
                            if ($sale_price_dates_to == '' && $sale_price_dates_to == '')
                                update_post_meta($new_user_id, '_price', $sale_price);
                            else if ($sale_price_dates_from && strtotime($sale_price_dates_from) < strtotime('NOW', current_time('timestamp')))
                                update_post_meta($new_user_id, '_price', $sale_price);
                            if ($sale_price_dates_to && strtotime($sale_price_dates_to) < strtotime('NOW', current_time('timestamp')))
                                update_post_meta($new_user_id, '_price', $regular_price);
                        }
                    }
                    else if (isset($fields['_price']) && !isset($fields['_regular_price'])) {
                        update_post_meta($new_user_id, '_regular_price', $fields['_price']);
                    }
                }

                // do custom actions on successful post save
                /* EMERSON: https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/185624661/comments
                  /*Add cred_save_data_form_ hook on CRED 1.3 */
                $form_slug = $form->getForm()->post_name;
                do_action('cred_save_data_form_' . $form_slug, $new_user_id, $thisform);
                do_action('cred_save_data_' . $form_id, $new_user_id, $thisform);
                do_action('cred_save_data', $new_user_id, $thisform);
            }
        } else {
            $WP_Error = new WP_Error();
            $WP_Error->add('upload', 'Error some required upload field failed.');
            $new_post_id = $WP_Error;
        }
        // return saved post_id as result
        return $new_user_id;
    }

    // send notifications for the form
    private function notify($post_id, $attachedData = null) {
        $form = &$this->_formData;
        $_fields = $form->getFields();

        // init notification manager if needed
        if (
                isset($_fields['notification']->enable) &&
                $_fields['notification']->enable &&
                !empty($_fields['notification']->notifications)
        ) {
            // add extra plceholder codes
            add_filter('cred_subject_notification_codes', array(&$this, 'extraSubjectNotificationCodes'), 5, 3);
            add_filter('cred_body_notification_codes', array(&$this, 'extraBodyNotificationCodes'), 5, 3);

            CRED_Loader::load('CLASS/Notification_Manager');
            if ($form->getForm()->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME)
                CRED_Notification_Manager::set_user_fields();
            // add the post to notification management
            CRED_Notification_Manager::add($post_id, $form->getForm()->ID, $_fields['notification']->notifications);
            // send any notifications now if needed
            CRED_Notification_Manager::triggerNotifications($post_id, array(
                'event' => 'form_submit',
                'form_id' => $form->getForm()->ID,
                'notification' => $_fields['notification']
                    ), $attachedData);

            // remove extra plceholder codes
            remove_filter('cred_subject_notification_codes', array(&$this, 'extraSubjectNotificationCodes'), 5, 3);
            remove_filter('cred_body_notification_codes', array(&$this, 'extraBodyNotificationCodes'), 5, 3);
        }
    }

    public function extraSubjectNotificationCodes($codes, $form_id, $post_id) {
        $form = $this->_formData;
        if ($form_id == $form->getForm()->ID) {
            $codes['%%POST_PARENT_TITLE%%'] = $this->cred_parent(array('get' => 'title'));
        }
        return $codes;
    }

    public function extraBodyNotificationCodes($codes, $form_id, $post_id) {
        $form = $this->_formData;
        if ($form_id == $form->getForm()->ID) {
            $codes['%%FORM_DATA%%'] = isset($this->out_['notification_data']) ? $this->out_['notification_data'] : '';
            $codes['%%POST_PARENT_TITLE%%'] = $this->cred_parent(array('get' => 'title'));
            $codes['%%POST_PARENT_LINK%%'] = $this->cred_parent(array('get' => 'url'));
        }
        return $codes;
    }

    private function CRED_render($msgs = "", $js = "") {
        $shortcodeParser = $this->_shortcodeParser;
        $zebraForm = $this->_zebraForm;

        $shortcodeParser->remove_all_shortcodes();

        $zebraForm->render();
        // post content area might contain shortcodes, so return them raw by replacing with a dummy placeholder
        //By Gen, we use placeholder <!CRED_ERROR_MESSAGE!> in content for errors

        $this->_content = str_replace(StaticClass::FORM_TAG . '_' . $zebraForm->form_properties['name'] . '%', $zebraForm->_form_content, $this->_content) . $js;
        $this->_content = str_replace('<!CRED_ERROR_MESSAGE!>', $msgs, $this->_content);
        // parse old shortcode first (with dashes)
        $shortcodeParser->add_shortcode('cred-post-parent', array(&$this, 'cred_parent'));
        $this->_content = $shortcodeParser->do_shortcode($this->_content);
        $shortcodeParser->remove_shortcode('cred-post-parent', array(&$this, 'cred_parent'));
        // parse new shortcode (with underscores)
        $shortcodeParser->add_shortcode('cred_post_parent', array(&$this, 'cred_parent'));
        $this->_content = $shortcodeParser->do_shortcode($this->_content);
        $shortcodeParser->remove_shortcode('cred_post_parent', array(&$this, 'cred_parent'));

        return $this->_content;
    }

    // parse form shortcode [credform]
    public function cred_form_shortcode($atts, $content = '') {
        extract(shortcode_atts(array(
            'class' => ''
                        ), $atts));

        if (!empty($class))
            $this->_attributes['class'] = esc_attr($class);
        // return a placeholder instead and store the content in _form_content var
        $this->_form_content = $content;
        return StaticClass::FORM_TAG . '_' . $this->_zebraForm->form_properties['name'] . '%';
    }

    /**
     * CRED-Shortcode: cred_parent
     *
     * Description: Render data relating to pre-selected parent of the post the form will manipulate
     *
     * Parameters:
     * 'post_type' => [optional] Define a specifc parent type
     * 'get' => Which information to render (title, url)
     *
     * Example usage:
     *
     *
     * [cred_parent get="url"]
     *
     * Link:
     *
     *
     * Note:
     *  'post_type'> necessary if there are multiple parent types
     *
     * */
    public function cred_parent($atts) {
        extract(shortcode_atts(array(
            'post_type' => null,
            'get' => 'title'
                        ), $atts));

        $parent_id = null;
        if ($post_type) {
            if (isset($this->out_['fields']['parents']['_wpcf_belongs_' . $post_type . '_id']) && isset($_GET['parent_' . $post_type . '_id'])) {
                $parent_id = intval($_GET['parent_' . $post_type . '_id']);
            }
        } else {
            if (isset($this->out_['fields']['parents']))
                foreach ($this->out_['fields']['parents'] as $parentdata) {
                    if (isset($_GET['parent_' . $parentdata['data']['post_type'] . '_id'])) {
                        $parent_id = intval($_GET['parent_' . $parentdata['data']['post_type'] . '_id']);
                        break;
                    }
                }
        }

        if ($parent_id !== null) {
            switch ($get) {
                case 'title':
                    return get_the_title($parent_id);
                case 'url':
                    return get_permalink($parent_id);
                case 'id':
                    return $parent_id;
                default:
                    return '';
            }
        } else {
            //TODO: check this
            //https://onthegosystems.myjetbrains.com/youtrack/issue/tssupp-139
            switch ($get) {
                case 'title':
                    return _('Previous Page');
                case 'url':
                    $back = $_SERVER['HTTP_REFERER'];
                    return (isset($back) && !empty($back)) ? $back : '';
                case 'id':
                    return '';
                default:
                    return '';
            }
        }
        return '';
    }

    // parse final shortcodes (internal) which render the actual html fields [render_cred_field]
    public function render_cred_shortcodes($atts, $content = '') {
        extract(shortcode_atts(array(
            'post' => '',
            'field' => '',
                        ), $atts));
        $out_ = &$this->out_;
        //$sync = false;
        $sync = apply_filters('glue_check_sync', false, $this->_zebraForm->controls[$field]->prime_name);

        if (isset($out_['controls'][$field]) && !$sync)
            return $out_['controls'][$field];
        return '';
    }

    // render the whole form (called from Zebra_Form)
    public function render_callback($controls, &$objs) {
        $out_ = &$this->out_;
        $shortcodeParser = $this->_shortcodeParser;

        $out_['controls'] = $controls;
        // render shortcodes, _form_content is being continuously replaced recursively
        $this->_form_content = $shortcodeParser->do_shortcode($this->_form_content);
        return $this->_form_content;
    }

    /*
     *   Implement Friendable Interface
     *
     */

    private function friendHash($obj) {
        // use __toString, to return friend token
        return sprintf('%s', $obj . '');
    }

    private static function friendHashStatic($class) {
        return sprintf('%s', (string) $class . '');
    }

    private function addFriend($fr, array $shared = array()) {
        if (!is_array($this->_____friends_____))
            $this->_____friends_____ = array();

        $hash = $this->friendHash($fr);
        $this->_____friends_____[$hash] = array_merge(
                array(
            'methods' => array(),
            'properties' => array()
                ), (array) $shared
        );
    }

    private static function addFriendStatic($fr, array $shared = array()) {
        if (!is_array(StaticClass::$_____friendsStatic_____))
            StaticClass::$_____friendsStatic_____ = array();

        $hash = self::friendHashStatic($fr);
        StaticClass::$_____friendsStatic_____[$hash] = array_merge(
                array(
            'methods' => array(),
            'properties' => array()
                ), (array) $shared
        );
    }

    private function sayByeToFriend($fr) {
        $hash = $this->friendHash($fr);
        if (isset($this->_____friends_____[$hash]))
            unset($this->_____friends_____[$hash]);
    }

    private static function sayByeToFriendStatic($fr) {
        $hash = StaticClass::friendHashStatic($fr);
        if (isset(StaticClass::$_____friendsStatic_____[$hash]))
            unset(StaticClass::$_____friendsStatic_____[$hash]);
    }

    private function parseFriendCall($the) {
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

    // use these "magic" methods to share with friends
    public function _call_($method) {
        list($hash, $method) = $this->parseFriendCall($method);
        if ($method && method_exists($this, $method)) {
            if ($hash && isset($this->_____friends_____[$hash])) {
                if (isset($this->_____friends_____[$hash]['methods']) && in_array($method, $this->_____friends_____[$hash]['methods'])) {
                    $args = array_slice(func_get_args(), 1);
                    return call_user_func_array(array(&$this, $method), $args);
                }
            }
        }
        trigger_error("Not available method '$method'", E_USER_WARNING);
        return null;
    }

    // use these "magic" methods to share with friends
    public static function _callStatic_($method) {
        list($hash, $method) = StaticClass::parseFriendCallStatic($method);
        if ($method && method_exists(__CLASS__, $method)) {
            if ($hash && isset(StaticClass::$_____friendsStatic_____[$hash])) {
                if (isset(StaticClass::$_____friendsStatic_____[$hash]['methods']) && in_array($method, StaticClass::$_____friendsStatic_____[$hash]['methods'])) {
                    $args = array_slice(func_get_args(), 1);
                    return call_user_func_array(array(__CLASS__, $method), $args);
                }
            }
        }
        trigger_error("Not available static method '$method'", E_USER_WARNING);
        return null;
    }

    // use these "magic" methods to share with friends
    public function _set_($prop, $val) {
        list($hash, $prop) = $this->parseFriendCall($prop);
        if ($prop && property_exists($this, $prop)) {
            if ($hash && isset($this->_____friends_____[$hash])) {
                if (isset($this->_____friends_____[$hash]['properties']) && in_array($prop, $this->_____friends_____[$hash]['properties'])) {
                    return ($this->{$prop} = $val);
                }
            }
        }
        trigger_error("Not available property '$prop'", E_USER_WARNING);
        return null;
    }

    // use these "magic" methods to share with friends
    public static function _setStatic_($prop, $val) {
        list($hash, $prop) = StaticClass::parseFriendCallStatic($prop);
        if ($prop && property_exists(__CLASS__, $prop)) {
            if ($hash && isset(StaticClass::$_____friendsStatic_____[$hash])) {
                if (isset(StaticClass::$_____friendsStatic_____[$hash]['properties']) && in_array($prop, StaticClass::$_____friendsStatic_____[$hash]['properties'])) {
                    // PHP > 5.1
                    //$reflection = new ReflectionClass(StaticClass::$_______class_______);
                    //return $reflection->setStaticPropertyValue($prop);
                    // http://stackoverflow.com/questions/1279081/getting-static-property-from-a-class-with-dynamic-class-name-in-php
                    /* Since I cannot trust the value of $val
                     * I am putting it in single quotes (I don't
                     * want its value to be evaled. Now it will
                     * just be parsed as a variable reference).
                     */
                    try {
                        eval(__CLASS__ . '::$' . $prop . '=$val;');
                    } catch (Exception $e) {
                        return false;
                    }
                    return true;
                }
            }
        }
        trigger_error("Not available static property '$prop'", E_USER_WARNING);
        return null;
    }

    // use these "magic" methods to share with friends
    // http://stackoverflow.com/questions/4527175/working-with-get-by-reference
    // http://stackoverflow.com/questions/4310473/using-set-with-arrays-solved-but-why
    // http://stackoverflow.com/questions/3479036/emulate-public-private-properties-with-get-and-set
    // http://php.net/manual/en/class.arrayaccess.php
    // http://www.php.net/manual/en/language.references.return.php
    // http://stackoverflow.com/questions/5966918/return-null-by-reference-via-get
    public function &_get_($prop) {
        list($hash, $prop, $ref) = $this->parseFriendCall($prop);
        $null = null;
        if ($prop && property_exists($this, $prop)) {
            if ($hash && isset($this->_____friends_____[$hash])) {
                if (isset($this->_____friends_____[$hash]['properties']) && in_array($prop, $this->_____friends_____[$hash]['properties'])) {
                    if ($ref)
                        $v = &$this->__getPrivRef($prop);
                    else
                        $v = $this->__getPriv($prop);
                    return $v;
                }
            }
        }
        trigger_error("Not available property '$prop'", E_USER_WARNING);
        return $null;
    }

    // use these "magic" methods to share with friends
    public static function &_getStatic_($prop) {
        //Doing this whan is a ajax call (defined( 'DOING_AJAX' ) && DOING_AJAX)        
        if (!($prop && property_exists("StaticClass", $prop))) {
            self::addFriendStatic('StaticClass', array(
                'methods' => array(),
                'properties' => array('_staticGlobal')
            ));
        }

        list($hash, $prop, $ref) = StaticClass::parseFriendCallStatic($prop);
        $null = null;

        if ($prop && property_exists(__CLASS__, $prop) ||
                $prop && property_exists("StaticClass", $prop)) {
            if ($hash && isset(StaticClass::$_____friendsStatic_____[$hash])) {
                if (isset(StaticClass::$_____friendsStatic_____[$hash]['properties']) &&
                        in_array($prop, StaticClass::$_____friendsStatic_____[$hash]['properties'])) {
                    //$_staticVars = get_class_vars(StaticClass::$_______class_______);
                    //return $_staticVars[$prop];
                    // PHP > 5.1
                    //$reflection = new ReflectionClass(StaticClass::$_______class_______);
                    //return $reflection->getStaticPropertyValue($prop);
                    if ($ref)
                        $v = &StaticClass::__getPrivStaticRef($prop);
                    else
                        $v = StaticClass::__getPrivStatic($prop);
                    return $v;
                }
            }
        }
        trigger_error("Not available static property '$prop'", E_USER_WARNING);
        return $null;
    }

    // actual get methods
    private function __getPriv($prop) {
        return $this->{$prop};
    }

    private function &__getPrivRef($prop) {
        return $this->{$prop};
    }

    /*
     *   /END Implement Friendable Interface
     *
     */
}
