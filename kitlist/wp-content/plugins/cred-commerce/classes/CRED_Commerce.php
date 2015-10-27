<?php
/**
 * MainClass
 *
 * Main class of the plugin
 * Class encapsulates all hook handlers
 *
 */
final class CRED_Commerce
{
    private static $formdata=false;
    private static $handler=false;
    private static $forced_handler=false;
    
    /*
    * Initialize plugin enviroment
    */
    public static function init($forced_handler=false, $bypass_init_hook=false)
    {
        // force a handler, eg for import/export using Views Demo downloader
        self::$forced_handler=$forced_handler;
        
        if ($bypass_init_hook)
            self::_init_();
        else
            // NOTE Early Init, in order to catch up with ecommerce hooks
            add_action( 'init', array(__CLASS__, '_init_'), 3 ); // early init in order to have all custom post types and taxonomies registered
    }

    public static function _init_()
    {
        global $wp_version, $post, $pagenow;

        // load translations from locale
        load_plugin_textdomain('wp-cred-pay', false, CRED_COMMERCE_LOCALE_PATH);

        if(is_admin())
        {
            if ('1'===get_option('cred_commerce_activated'))
            {
                delete_option('cred_commerce_activated');
                // add a notice
                add_action('admin_notices', array(__CLASS__,'configureNotice'), 5);
            }
            // setup js, css assets
            add_action('admin_enqueue_scripts', array(__CLASS__,'onAdminEnqueueScripts'));
            // Add settings link on plugin page
            add_filter("plugin_action_links_".CRED_COMMERCE_PLUGIN_BASENAME, array(__CLASS__, 'addSettingsLink'));
        }
        
        // exit if cred not active or installed
        if (!defined('CRED_FE_VERSION'))
        {
            if (is_admin())
            {
                if ('plugins.php'==$pagenow )
                {
                    // add a notice
                    add_action('admin_notices', array(__CLASS__,'cred_commerce_display_notice'), 3);
                }
                // add dummy menu
                add_action('admin_menu', array(__CLASS__, 'addDummyMenuItems'));
            }
            return;
        }

        if(is_admin())
        {
            // add this menu to CRED menus panel
            add_action('cred_admin_menu_after_forms', array(__CLASS__, 'addCREDMenuItems'), 3, 1);

            // add custom meta boxes for cred forms
            add_filter('cred_admin_register_meta_boxes', array(__CLASS__, 'registerMetaBoxes'), 20, 1);
            add_action('cred_admin_add_meta_boxes', array(__CLASS__, 'addMetaBoxes'), 20, 1);
            // hook to CRED to add extra data/fields in admin screen
            // add extra fields on CRED notifications
            //add_action('cred_admin_notification_fields_before', array(__CLASS__, 'addCommerceExtraNotificationFields'), 10, 3);
            add_action('cred_admin_notification_notify_event_options_before', array(__CLASS__, 'addCommerceExtraNotificationEventsBefore'), 1, 3);
            add_action('cred_admin_notification_notify_event_options', array(__CLASS__, 'addCommerceExtraNotificationEvents'), 1, 3);
            add_action('cred_admin_notification_recipient_options_before', array(__CLASS__, 'addCommerceExtraNotificationRecipients'), 1, 3);
            
            // add extra options on CRED after submit action
            add_filter('cred_admin_submit_action_options', array(__CLASS__, 'addCommerceExtraPageOptions'), 10, 3);
            // add extra options on Notification codes
            add_filter('cred_admin_notification_subject_codes', array(__CLASS__, 'addCommerceExtraNotificationCodes'), 10, 4);
            add_filter('cred_admin_notification_body_codes', array(__CLASS__, 'addCommerceExtraNotificationCodes'), 10, 4);
            // add extra table columns to CRED forms
            add_filter('manage_' . CRED_FORMS_CUSTOM_POST_NAME . '_posts_columns', array(__CLASS__, 'addCommerceExtraColumns'), 10, 1);
            // render extra table columns to CRED forms
            add_filter('manage_' . CRED_FORMS_CUSTOM_POST_NAME . '_posts_custom_column', array(__CLASS__, 'renderCommerceExtraColumns'), 10, 2);

            // save custom fields of cred forms
            add_action('cred_admin_save_form', array(__CLASS__, 'saveFormCustomFields'), 2, 2);
        }
        // localize custom fields of cred forms
        add_action('cred_localize_form', array(__CLASS__, 'localizeCommerceForm'), 2, 1);

        // setup extra admin hooks for other plugins
        self::setupExtraHooks();
    }

    public static function cred_commerce_display_notice()
    {
    ?>
    <div class="error">
        <p><?php _e('CRED Commerce plugin needs the <a href="http://wp-types.com/home/cred/" target="_blank"><strong>CRED Frontend Editor</strong></a> plugin to be installed and activated.','wp-cred-pay'); ?></p>
    </div>
    <?php
    }
    
    public static function addDummyMenuItems()
    {
		$menu_label = 'CRED';

        $cred_index = 'CRED_Commerce';
	    add_menu_page($menu_label, $menu_label, CRED_COMMERCE_CAPABILITY, $cred_index, array(__CLASS__, 'CommerceSettingsPage'), '');
        // allow 3rd-party menu items to be included
        add_submenu_page($cred_index, __( 'CRED Commerce', 'wp-cred-pay' ), __( 'CRED Commerce', 'wp-cred-pay' ), CRED_COMMERCE_CAPABILITY, 'CRED_Commerce', array(__CLASS__, 'CommerceSettingsPage'));
    }
    
    public static function configureNotice()
    {
        $settings_link = '<a href="'.admin_url('admin.php').'?page=CRED_Commerce'.'">'.__('Configure','wp-cred-pay').'</a>';
        ob_start();?>
        <div class="updated"><p>
            <?php printf(__("CRED Commerce has been activated. %s", 'wp-cred-pay'), $settings_link); ?>
            <?php 
                if (!defined('CRED_FE_VERSION') || version_compare(CRED_FE_VERSION, '1.2', '<'))
                    printf('<br />'.__("CRED Commerce requires CRED version %s or higher, to work correctly", 'wp-cred-pay'), '1.2');
            ?>
        </p></div>
        <?php
        echo ob_get_clean();
    }
    
    public static function getCurrentCommercePlugin()
    {
        global $woocommerce;
        
        if (class_exists( 'Woocommerce' ) && $woocommerce && isset($woocommerce->version) && version_compare($woocommerce->version, '2.0', '>='))
        {
            return 'woocommerce';
        }
        
        return false;
    }
    
    public static function setupExtraHooks()
    {
        // init handler
        if (self::$forced_handler)
            $handler = self::$forced_handler;
        else
            $handler = self::getCurrentCommercePlugin();
            
        if ($handler)
        {
            self::$handler=CREDC_Loader::get('CLASS/Form_Handler');
            self::$handler->init(
                CRED_Commerce_Plugin_Factory::getPlugin($handler), 
                CREDC_Loader::get('MODEL/Main')
            );
            
            // add shortcodes
            add_shortcode('cred_checkout_message', array(__CLASS__, 'checkoutMessage'));
            add_shortcode('cred_thankyou_message', array(__CLASS__, 'thankyouMessage'));
            
            // add extra plceholder codes
            add_filter('cred_subject_notification_codes', array(__CLASS__, 'extraNotificationCodes'), 10, 3);
            add_filter('cred_body_notification_codes', array(__CLASS__, 'extraNotificationCodes'), 10, 3);
            
            // add extra notification recipient options
            add_filter('cred_notification_recipients', array(__CLASS__, 'extraNotificationRecipients'), 10, 4);
            
            // add cred commerce data on cred export/import process
            add_filter('cred_export_forms', array(__CLASS__, 'exportCommerceForms'), 1, 1);
            add_filter('cred_import_form', array(__CLASS__, 'importCommerceForm'), 1, 3);
        }
    }

    public static function localizeCommerceForm($data)
    {
        if (!isset($data['post'])) return;
        $model=CREDC_Loader::get('MODEL/Main');
        $form_id=$data['post']->ID;
        $form=$model->getForm($form_id, false);
        
        if ($form->isCommerce)
        {
            // localise messages
            if (isset($form->commerce['messages']) && isset($form->commerce['messages']['checkout']))
            {
                cred_translate_register_string('cred-form-'.$form_id, 'cred_commerce_checkout_message', $form->commerce['messages']['checkout'], false);
            }
            if (isset($form->commerce['messages']) && isset($form->commerce['messages']['thankyou']))
            {
                cred_translate_register_string('cred-form-'.$form_id, 'cred_commerce_thankyou_message', $form->commerce['messages']['thankyou'], false);
            }
        }
    }
    
    public static function exportCommerceForms($forms)
    {
        $model=CREDC_Loader::get('MODEL/Main');
        foreach (array_keys($forms) as $k)
        {
            $data=$model->getFormCustomField($forms[$k]->ID, 'commerce');
            if ($data)
            {
                if (!isset($forms[$k]->meta))
                    $forms[$k]->meta=array();
                    
                if (isset($data['product']))
                    $data['product']=self::$handler->getRelativeProduct($data['product']);
                
                $forms[$k]->meta['commerce']=$data;
            }
        }
        return $forms;
    }
    
    public static function importCommerceForm($results, $form_id, $data)
    {
        if (isset($data['meta']) && isset($data['meta']['commerce']))
        {
            $model=CREDC_Loader::get('MODEL/Main');
            $cdata=$data['meta']['commerce'];
            //cred_log($data);
            if (isset($cdata['product']))
            {
                $product=self::$handler->getAbsoluteProduct($cdata['product']);
                if (!$product && isset($results['errors']))
                {
                    $results['errors'][]=sprintf(__('Product %s does not exist on this site. You will need to set the CRED Commerce settings for <a href="%s">form %s</a> manually.','wp-cred-pay'), $cdata['product'], CRED_CRED::getFormEditLink($form_id), $form_id);
                }
                $cdata['product']=$product;
            }
            $model->updateFormCustomField($form_id, 'commerce', $cdata);
        }
        return $results;
    }
    
    // when form is submitted from admin, save the custom fields which describe the form configuration to DB
    public static function saveFormCustomFields($form_id, $form)
    {
        if (isset($_POST['_cred_commerce']))
        {
            $data=$_POST['_cred_commerce'];
            if (isset($data['notification']['notifications']) && is_array($data['notification']['notifications']))
                // normalize order of fields
                $data['notification']['notifications']=array_values($data['notification']['notifications']);
            $model=CREDC_Loader::get('MODEL/Main');
            $model->updateForm($form_id, $data);
        }
    }

    // add custom classes to our metaboxes, so they can be handled as needed
    public static function registerMetaboxes($cred_meta_boxes)
    {
        array_push($cred_meta_boxes, 'credcommercediv');
        return $cred_meta_boxes;
    }

    // add meta boxes in admin pages which manipulate forms
    public static function addMetaBoxes($form)
    {
        global $pagenow;
        // cred commerce meta box
        add_meta_box('credcommercediv',__('CRED Commerce','wp-cred'),array(__CLASS__, 'addCommerceMetaBox'),null,'normal','high',array());
    }

    // functions to display actual meta boxes (better to use templates here.., done using template snipetts to separate the code a bit)
    public static function addCommerceMetaBox($form)
    {
        if (!self::$formdata)
        {
            $model=CREDC_Loader::get('MODEL/Main');
            self::$formdata=$model->getForm($form->ID, false);
        }
        if (isset(self::$formdata->commerce))
            $data=self::$formdata->commerce;
        else
            $data=array();
            
        $ecommerce=true;
        $productlink='';
        $commerceplugin='Woocommerce';
        if (self::$handler)
        {
            $products=self::$handler->getProducts();
            $productlink='<a href="'.self::$handler->getNewProductLink().'">'.__('Add products','wp-cred-pay').'</a>';
            $producthref=self::$handler->getNewProductLink();
        }
        else
        {
            $ecommerce=false;
            $productlink='<a href="'.admin_url('admin.php').'?page=CRED_Commerce'.'">'.__('Compatible e-commerce plugins','wp-cred-pay').'</a>';
            $producthref=admin_url('admin.php').'?page=CRED_Commerce';
            $products=array();
        }
        echo CREDC_Loader::tpl('commerce-settings-meta-box', array(
            'data'=>$data,
            'codes'=>array_keys(self::getExtraCommerceCodes()),
            'products'=>$products,
            'productlink'=>$productlink,
            'producthref'=>$producthref,
            'commerceplugin'=>$commerceplugin,
            'ecommerce'=>$ecommerce
        ));
    }

    public static function extraNotificationRecipients($recipients, $notification, $form_id, $post_id)
    {
        $model=CREDC_Loader::get('MODEL/Main');
        $form=$model->getForm($form_id, false);
        
        if ($form->isCommerce)
        {
            if (in_array('customer', $notification['to']['type']))
            {
                $customer=self::$handler->getCustomer($post_id, $form_id);
                //cred_log($customer);
                if ($customer)
                {
                    $to=(isset($notification['to']['customer'])&&isset($notification['to']['customer']['to_type']))?$notification['to']['customer']['to_type']:'to';
                    $recipients[]=array(
                        'to'=>$to,
                        'address'=>isset($customer->user_email)?$customer->user_email:false,
                        'name'=>(isset($customer->user_firstname)&&!empty($customer->user_firstname))?$customer->user_firstname:false,
                        'lastname'=>(isset($customer->user_lasttname)&&!empty($customer->user_lasttname))?$customer->user_lastname:false
                    );
                }
            }
        }
        
        return $recipients;
    }
    
    public static function extraNotificationCodes($codes, $form_id, $post_id)
    {
        $model=CREDC_Loader::get('MODEL/Main');
        $form=$model->getForm($form_id, false);
        
        if ($form->isCommerce)
        {
            $product=false;
            if ('post'==$form->associateProduct && isset($form->productField))
                $product=$model->getPostMeta($post_id, $form->productField);
            elseif (isset($form->product))
                $product=$form->product;
             
            $product=self::$handler->getProduct($product);
            if ($product)
            {
                $codes['%%PRODUCT_ID%%']=$product->ID;
                $codes['%%PRODUCT_NAME%%']=$product->title;
                $codes['%%PRODUCT_PRICE%%']=$product->price;
            }
            $customer=self::$handler->getCustomer($post_id, $form_id);
            if ($customer)
            {
                $codes['%%CUSTOMER_ID%%']=$customer->ID;
                $codes['%%CUSTOMER_EMAIL%%']=$customer->user_email;
                $codes['%%CUSTOMER_DISPLAYNAME%%']=$customer->display_name;
                $codes['%%CUSTOMER_FIRSTNAME%%']=$customer->user_firstname;
                $codes['%%CUSTOMER_LASTNAME%%']=$customer->user_lastname;
            }
        }
        return $codes;
    }
    
    public static function addCommerceExtraPageOptions($options, $action, $form)
    {
        $options['cart']=__('Go to cart page','wp-cred-pay');
        $options['checkout']=__('Go to checkout page','wp-cred-pay');
        return $options;
    }

    public static function getExtraCommerceCodes()
    {
        return array(
            // product
            '%%PRODUCT_ID%%'=>__('Product ID','wp-cred-pay'),
            '%%PRODUCT_NAME%%'=>__('Product Name','wp-cred-pay'),
            '%%PRODUCT_PRICE%%'=>__('Product Price','wp-cred-pay'),
            // customer
            '%%CUSTOMER_ID%%'=>__('Customer ID','wp-cred-pay'),
            '%%CUSTOMER_EMAIL%%'=>__('Customer Email','wp-cred-pay'),
            '%%CUSTOMER_DISPLAYNAME%%'=>__('Customer Display Name','wp-cred-pay'),
            '%%CUSTOMER_FIRSTNAME%%'=>__('Customer First Name','wp-cred-pay'),
            '%%CUSTOMER_LASTNAME%%'=>__('Customer Last Name','wp-cred-pay')
        );
    }
    
    public static function addCommerceExtraNotificationCodes($options, $form, $ii, $notif)
    {
        $options=array_merge($options, self::getExtraCommerceCodes());
        return $options;
    }
    
    public static function addCommerceExtraNotificationRecipients($form, $data, $notification)
    {
        ob_start();
        if (!$notification || empty($notification))
        {
            // used for template, return dummy
            ?>
            <p>
                <label class='cred-label'>
                    <input data-cred-bind="{ 
                                    validate: { 
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_recipient_required-<?php echo $data[0]; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        } 
                                    } 
                                }" type='checkbox' class='cred-checkbox-10' name="<?php echo $data[1]; ?>" value="customer" />
                    <span><?php _e('Send this notification to the customer (created by CRED Commerce, when payment completes)', 'wp-cred-pay'); ?></span>
                </label>
                <span data-cred-bind="{ action: 'show', condition: '_cred[notification][notifications][<?php echo $data[0]; ?>][to][type] has customer' }">
                    <select style="width:60px" name="_cred[notification][notifications][<?php echo $data[0]; ?>][to][customer][to_type]">
                        <option value="to"><?php _e('To:', 'wp-cred'); ?></option>
                        <option value="cc"><?php _e('Cc:', 'wp-cred'); ?></option>
                        <option value="bcc"><?php _e('Bcc:', 'wp-cred'); ?></option>
                    </select><br />
                </span>
            </p>
            <?php
        }
        else
        {
            // actual notification data
            $to_type='to';
            if (isset($notification['to']['customer']) && isset($notification['to']['customer']['to_type']))
                $to_type=$notification['to']['customer']['to_type'];
            ?>
            <p>
                <label class='cred-label'>
                    <input data-cred-bind="{ 
                                    validate: { 
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_recipient_required-<?php echo $data[0]; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        } 
                                    } 
                                }" type='checkbox' class='cred-checkbox-10' name="<?php echo $data[1]; ?>" value="customer" <?php if ($data[2]=='customer') echo 'checked="checked"'; ?>/>
                    <span><?php _e('Send this notification to the customer (created by CRED Commerce, when payment completes)', 'wp-cred-pay'); ?></span>
                </label>
                <span data-cred-bind="{ action: 'show', condition: '_cred[notification][notifications][<?php echo $data[0]; ?>][to][type] has customer' }">
                    <select style="width:60px" name="_cred[notification][notifications][<?php echo $data[0]; ?>][to][customer][to_type]">
                        <option value="to" <?php if ('to'==$to_type) echo 'selected="selected"'; ?>><?php _e('To:', 'wp-cred'); ?></option>
                        <option value="cc" <?php if ('cc'==$to_type) echo 'selected="selected"'; ?>><?php _e('Cc:', 'wp-cred'); ?></option>
                        <option value="bcc" <?php if ('bcc'==$to_type) echo 'selected="selected"'; ?>><?php _e('Bcc:', 'wp-cred'); ?></option>
                    </select><br />
                </span>
            </p>
            <?php
        }
        echo ob_get_clean();
    }

    public static function addCommerceExtraNotificationEventsBefore($form, $data, $notification)
    {
        ob_start();
        if (!$notification)
        {
            // used for template, return dummy
            ?>
            <p>
                <label>
                    <input data-cred-bind="{ 
                                    validate: { 
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_event_required-<?php echo $data[0]; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        } 
                                    } 
                                }" type="radio" class="cred-radio-10 cred-commerce-event-type" name="<?php echo $data[1]; ?>" value="order_created" />
                    <span><?php _e('When submitting the form with payment details', 'wp-cred-pay'); ?></span>
                </label>
            </p>
            <?php
        }
        else
        {
            // actual notification data
            ?>
            <p>
                <label>
                    <input data-cred-bind="{ 
                                    validate: { 
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_event_required-<?php echo $data[0]; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        } 
                                    } 
                                }" type="radio" class="cred-radio-10 cred-commerce-event-type" name="<?php echo $data[1]; ?>" value="order_created" <?php if ($data[2]=='order_created') echo 'checked="checked"'; ?> />
                    <span><?php _e('When submitting the form with payment details', 'wp-cred-pay'); ?></span>
                </label>
            </p>
            <?php
        }
        echo ob_get_clean();
    }
    
    public static function addCommerceExtraNotificationEvents($form, $data, $notification)
    {
        ob_start();
        if (!$notification)
        {
            // used for template, return dummy
            ?>
            <p>
                <label>
                    <input data-cred-bind="{ 
                                    validate: { 
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_event_required-<?php echo $data[0]; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        } 
                                    } 
                                }" type="radio" class="cred-radio-10 cred-commerce-event-type" name="<?php echo $data[1]; ?>" value="order_modified" />
                    <span><?php _e('When the purchase status changes to:', 'wp-cred-pay'); ?></span>
                </label>
                <span data-cred-bind="{ action: 'show', condition: '<?php echo $data[1]; ?>=order_modified' }">
                    <select class="cred_commerce_when_order_status_changes" name="_cred[notification][notifications][<?php echo $data[0]; ?>][event][order_status]">
                        <option value='pending'><?php _e('Pending','wp-cred-pay'); ?></option>
                        <option value='failed'><?php _e('Failed','wp-cred-pay'); ?></option>
                        <option value='processing'><?php _e('Processing','wp-cred-pay'); ?></option>
                        <option value='completed'><?php _e('Completed','wp-cred-pay'); ?></option>
                        <option value='on-hold'><?php _e('On-Hold','wp-cred-pay'); ?></option>
                        <option value='cancelled'><?php _e('Cancelled','wp-cred-pay'); ?></option>
                        <option value='refunded'><?php _e('Refunded','wp-cred-pay'); ?></option>
                    </select>
                </span>
            </p>
            <?php
        }
        else
        {
            if (!self::$formdata)
            {
                $model=CREDC_Loader::get('MODEL/Main');
                self::$formdata=$model->getForm($form->ID, false);
            }
            $order_status=null;
            if (
                self::$formdata->isCommerce && 
                isset($notification['event']['order_status'])
            )
                $order_status=$notification['event']['order_status'];
            // actual notification data
            ?>
            <p>
                <label>
                    <input data-cred-bind="{ 
                                    validate: { 
                                        required: {
                                            actions: [
                                                {action: 'validationMessage', domRef: '#notification_event_required-<?php echo $data[0]; ?>' },
                                                {action: 'validateSection' }
                                            ]
                                        } 
                                    } 
                                }" type="radio" class="cred-radio-10 cred-commerce-event-type" name="<?php echo $data[1]; ?>" value="order_modified" <?php if ($data[2]=='order_modified') echo 'checked="checked"'; ?> />
                    <span><?php _e('When the purchase status changes to:', 'wp-cred-pay'); ?></span>
                </label>
                <span data-cred-bind="{ action: 'show', condition: '<?php echo $data[1]; ?>=order_modified' }">
                    <select class="cred_commerce_when_order_status_changes" name="_cred[notification][notifications][<?php echo $data[0]; ?>][event][order_status]">
                        <option value='pending' <?php if ('pending'==$order_status) echo 'selected="selected"'; ?>><?php _e('Pending','wp-cred-pay'); ?></option>
                        <option value='failed' <?php if ('failed'==$order_status) echo 'selected="selected"'; ?>><?php _e('Failed','wp-cred-pay'); ?></option>
                        <option value='processing' <?php if ('processing'==$order_status) echo 'selected="selected"'; ?>><?php _e('Processing','wp-cred-pay'); ?></option>
                        <option value='completed' <?php if ('completed'==$order_status) echo 'selected="selected"'; ?>><?php _e('Completed','wp-cred-pay'); ?></option>
                        <option value='on-hold' <?php if ('on-hold'==$order_status) echo 'selected="selected"'; ?>><?php _e('On-Hold','wp-cred-pay'); ?></option>
                        <option value='cancelled' <?php if ('cancelled'==$order_status) echo 'selected="selected"'; ?>><?php _e('Cancelled','wp-cred-pay'); ?></option>
                        <option value='refunded' <?php if ('refunded'==$order_status) echo 'selected="selected"'; ?>><?php _e('Refunded','wp-cred-pay'); ?></option>
                    </select>
                </span>
            </p>
            <?php
        }
        echo ob_get_clean();
    }
    
    public static function checkoutMessage($atts, $content)
    {
        $added = array();
        $cred_data=self::$handler->getCredData();
        $message='';
        
        $model=CREDC_Loader::get('MODEL/Main');
        //echo "<pre>";print_r($cred_data);echo "</pre>";
        foreach ($cred_data as $ii=>$data)
        {
            if (isset($data['cred_form_id']))
            {
                $form=$model->getForm($data['cred_form_id'], false);
                if (isset($form->commerce['messages']['checkout'])&&!in_array($form->commerce['messages']['checkout'], $added))
                {
                    $added[]=$form->commerce['messages']['checkout'];
                    // allow WPML string localization
                    $message.=do_shortcode(cred_translate(
                        'cred_commerce_checkout_message', 
                        $form->commerce['messages']['checkout'], 
                        'cred-form-'.$form->ID
                    ));
                    $message.=" ";
                }
            }
        }
        //return '<pre>'.print_r($cred_data, true).'</pre>';
        return $message;
    }
    
    public static function thankyouMessage($atts, $content)
    {
        $added = array();
        $cred_data=self::$handler->getCredData();
        $message='';
        
        $model=CREDC_Loader::get('MODEL/Main');
        foreach ($cred_data as $ii=>$data)
        {
            if (isset($data['cred_form_id']))
            {
                $form=$model->getForm($data['cred_form_id'], false);
                if (isset($form->commerce['messages']['thankyou'])&&!in_array($form->commerce['messages']['thankyou'], $added))
                {
                    $added[]=$form->commerce['messages']['thankyou'];
                    // allow WPML string localization
                    $message.=do_shortcode(cred_translate(
                        'cred_commerce_thankyou_message', 
                        $form->commerce['messages']['thankyou'], 
                        'cred-form-'.$form->ID
                    ));
                    $message.=" ";
                }
            }
        }
        //return '<pre>'.print_r($cred_data, true).'</pre>';
        return $message;
    }
    
    public static function addCommerceExtraColumns($columns)
    {
        $columns['cred_commerce'] = __('E-Commerce', 'wp-cred-pay');
        return $columns;
    }

    public static function renderCommerceExtraColumns($column_name, $post_ID)
    {
        if ('cred_commerce'==$column_name)
        {
            $data=CREDC_Loader::get('MODEL/Main')->getForm($post_ID, false);
            if (isset($data->commerce))
            {
                $data=$data->commerce;
                if (isset($data['associate_product']) && 'form'==$data['associate_product'] && isset($data['product']))
                {
                    $product=(self::$handler)?self::$handler->getProduct($data['product']):false;
                    if ($product)
                    {
                        printf (__('Product: %s', 'wp-cred-pay'), $product->title);
                    }
                    else
                    {
                        echo '<strong>'.__('Not Set', 'wp-cred-pay').'</strong>';
                    }
                }
                elseif (isset($data['associate_product']) && 'post'==$data['associate_product'] && isset($data['product_field']))
                {
                    printf (__('Product Field: %s', 'wp-cred-pay'), $data['product_field']);
                }
                else
                {
                    echo '<strong>'.__('Not Set', 'wp-cred-pay').'</strong>';
                }
            }
            else
            {
                echo '<strong>'.__('Not Set', 'wp-cred-pay').'</strong>';
            }
        }
    }

    public static function getAdminPage($custom_data=array())
    {
        global $pagenow, $post, $post_type;
        static $pageData=null;
        static $_custom_data=null;

        if (null==$pageData || (!empty($custom_data) && $_custom_data!=$custom_data))
        {
            $_custom_data!=$custom_data;

            $pageData=(object)array(
                'isAdmin'=>false,
                'isAdminAjax'=>false,
                'isPostEdit'=>false,
                'isPostNew'=>false,
                'isCustomPostEdit'=>false,
                'isCustomPostNew'=>false,
                'isCustomAdminPage'=>false
            );

            if (!is_admin())    return $pageData;

            $pageData->isAdmin=true;
            $pageData->isPostEdit=(bool)('post.php'===$pagenow);
            $pageData->isPostNew=(bool)('post-new.php'===$pagenow);
            if (!empty($custom_data))
            {
                $custom_post_type=isset($custom_data['post_type'])?$custom_data['post_type']:false;
                $pageData->isCustomPostEdit=(bool)($pageData->isPostEdit && $custom_post_type===$post_type);
                $pageData->isCustomPostNew=(bool)($pageData->isPostNew && isset($_GET['post_type']) && $custom_post_type===$_GET['post_type']);
            }
            if (!empty($custom_data))
            {
                $custom_admin_base=isset($custom_data['base'])?$custom_data['base']:false;
                $custom_admin_pages=isset($custom_data['pages'])?(array)$custom_data['pages']:array();
                $pageData->isCustomAdminPage=(bool)($custom_admin_base===$pagenow && isset($_GET['page']) && in_array($_GET['page'], $custom_admin_pages));
            }
        }
        return $pageData;
    }
    
    public static function onAdminEnqueueScripts()
    {
        // setup css js
        global $pagenow;
        
        $pageData=self::getAdminPage(array(
            'post_type'=>defined('CRED_FORMS_CUSTOM_POST_NAME')?CRED_FORMS_CUSTOM_POST_NAME:false,
            'base'=>'admin.php',
            'pages'=>array('CRED_Commerce')
        ));
        
        if
            (
               $pageData->isCustomPostEdit || $pageData->isCustomPostNew || 
               $pageData->isCustomAdminPage
            )
        {
            if ( $pageData->isCustomAdminPage )
            {
                if (defined('CRED_ASSETS_URL'))
                    wp_enqueue_style('cred_cred_style', CRED_ASSETS_URL.'/css/cred.min.css', null, CRED_FE_VERSION);
            }
			wp_register_style('font-awesome', CRED_COMMERCE_ASSETS_URL.'/css/font-awesome.min.css', null, CRED_COMMERCE_VERSION);
			wp_register_style('cred_commerce_style', CRED_COMMERCE_ASSETS_URL.'/css/cred-commerce.css', null, CRED_COMMERCE_VERSION);
			wp_enqueue_style('font-awesome');
			wp_enqueue_style('cred_commerce_style');
        }
    }

    // setup CRED menus in admin
    public static function addCREDMenuItems($cred_menu_index)
    {
        add_submenu_page($cred_menu_index, __( 'CRED Commerce', 'wp-cred-pay' ), __( 'CRED Commerce', 'wp-cred-pay' ), CRED_CAPABILITY, 'CRED_Commerce', array(__CLASS__, 'CommerceSettingsPage'));
    }

    // setup settings menu link on plugins page
    public static function addSettingsLink($links)
    {
        if (
            (defined('CRED_CAPABILITY')&&current_user_can(CRED_CAPABILITY)) ||
            current_user_can(CRED_COMMERCE_CAPABILITY)
        )
        {
            $settings_link = '<a href="'.admin_url('admin.php').'?page=CRED_Commerce'.'">'.__('Settings','wp-cred-pay').'</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }

    public static function CommerceSettingsPage()
    {
        CREDC_Loader::load('VIEW/settings');
    }
}
