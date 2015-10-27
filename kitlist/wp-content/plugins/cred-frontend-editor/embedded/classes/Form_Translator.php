<?php
/**
 * CRED Form Translator
 * 
 * 
 */
final class CRED_Form_Translator
{
    private $_form_data;
    private $_strings=array();
    private $_prefix='';

    public function __construct() {  }

    public function setFormData($form_id, $form_name)
    {
        $this->_form_data=array('ID'=>$form_id, 'name'=>$form_name);
    }
    
    public function registerString($name, $value)
    {
        cred_translate_register_string('cred-form-'.$this->_form_data['name'].'-'.$this->_form_data['ID'], $name, $value, false);
    }
    
    public function processFormForStrings($content, $prefix='')
    {
        $this->_prefix=$prefix;
        $shorts=cred_disable_shortcodes();
        add_shortcode('cred-field',array(&$this,'check_strings_in_shortcodes'));
        add_shortcode('cred_field',array(&$this,'check_strings_in_shortcodes'));
        do_shortcode($content);
        remove_shortcode('cred-field',array(&$this,'check_strings_in_shortcodes'));
        remove_shortcode('cred_field',array(&$this,'check_strings_in_shortcodes'));
        cred_re_enable_shortcodes($shorts);
    }

    public function check_strings_in_shortcodes($atts)
    {
        extract( shortcode_atts( array(
            'value' => null,
        ), $atts ) );
        
        if (null!==$value && !empty($value) && is_string($value))
            $this->registerString($this->_prefix.$value, $value);
    }
    
    public function processForm($data)
    {
        if (!isset($data['post'])) return;
        
        $form=$data['post'];
        $message=$data['message'];
        $notification=$data['notification'];
        $messages=$data['messages'];
        
        $this->setFormData($form->ID, $form->post_title);
        //  register field values
        $this->processFormForStrings($form->post_content, 'Value: ');
        // register form title
        $this->registerString('Form Title: '.$form->post_title, $form->post_title);
        
        $this->registerString('Display Message: '.$form->post_title, $message);
        
        // register Notification Data also
        if ($notification && isset($notification->notifications) && is_array($notification->notifications))
        {
            foreach ($notification->notifications as $ii=>$nott)
            {
                // new format
                // these are not relevant in new format for localization
                /*switch($nott['to']['type'])
                {
                    case 'wp_user':
                        $this->registerString('CRED Notification '.$ii.' Mail To', $nott['to']['user']);
                        break;
                    case 'specific_mail':
                        $this->registerString('CRED Notification '.$ii.' Mail To', $nott['to']['address']);
                        if (isset($nott['to']['name']))
                            $this->registerString('CRED Notification '.$ii.' Mail To Name', $nott['to']['name']);
                        if (isset($nott['to']['lastname']))
                            $this->registerString('CRED Notification '.$ii.' Mail To LastName', $nott['to']['lastname']);
                        break;
                    default:
                        break;
                }*/
                $hashSubject=CRED_Helper::strHash($nott['mail']['subject']);
                $hashBody=CRED_Helper::strHash($nott['mail']['body']);
                $this->registerString('CRED Notification Subject '.$hashSubject, $nott['mail']['subject']);
                $this->registerString('CRED Notification Body '.$hashBody, $nott['mail']['body']);
            }
        }
        // register messages also
        foreach ($messages as $msgid=>$msg)
        {
            $this->registerString('Message_'.$msgid, $msg);
        }

		// register options from select and checkboxes/radio fields, force form build
		CRED_Loader::load('CLASS/Form_Builder');
		CRED_Form_Builder::init();
		CRED_Form_Builder::getForm($form->ID, null, false);
        
        // allow 3rd-party to add extra localization
        do_action('cred_localize_form', $data);
    }
    
    public function processAllForms()
    {
        $fm=CRED_Loader::get('MODEL/Forms');
        $forms=$fm->getAllForms();
        foreach ($forms as $form)
        {
            $data=array(
                'post'=>$form,
                'message'=>'',
                'messages'=>array(),
                'notification'=>(object)array(
                    'enable'=>0,
                    'notifications'=>array()
                )
            );
            
            $fields=$fm->getFormCustomFields($form->ID,array('form_settings','notification','extra'));
            $settings=isset($fields['form_settings'])?$fields['form_settings']:false;
            $notification=isset($fields['notification'])?$fields['notification']:false;
            $extra=isset($fields['extra'])?$fields['extra']:false;
            
            // register settings
            if ($settings && isset($settings->form['action_message']))
                $data['message'] = $settings->form['action_message'];
            
            // register Notification Data also
            if ($notification)
            {
                $data['notification']=$notification;
            }
            // register extra fields
            if ($extra && isset($extra->messages))
            {
                // register messages also
                $data['messages']=$extra->messages;
            }
            
            $this->processForm($data);
        }
    }   
}
