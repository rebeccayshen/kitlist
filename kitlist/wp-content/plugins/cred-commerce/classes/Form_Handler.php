<?php
/**
*   CRED Commerce Form Handler
*
**/
final class CRED_Commerce_Form_Handler
{

    private $plugin=null;
    private $form=null;
    private $model;
    
    private $_data=false;
    
    public function __construct()
    {
    }
    
    // dependency injection
    public function init($plugin, $model)
    {
        $this->model=$model;
        
        $this->plugin=$plugin;
        
        // add necessary hooks to manage the form submission
        //add_action('cred_save_data', array(&$this, 'onSaveData'), 10, 2);
        add_action('cred_submit_complete', array(&$this, 'onSubmitComplete'), 1, 2);
        add_action('cred_custom_success_action', array(&$this, 'onFormSuccessAction'), 1, 3 );
        //add_action('cred_commerce_payment_complete', array(&$this, 'onPaymentComplete'), 1, 1 );
        $this->plugin->attach('_cred_commerce_order_received', array(&$this, 'onOrderReceived'));
        $this->plugin->attach('_cred_commerce_payment_failed', array(&$this, 'onPaymentFailed'));
        $this->plugin->attach('_cred_commerce_payment_completed', array(&$this, 'onPaymentComplete'));
        $this->plugin->attach('_cred_commerce_order_completed', array(&$this, 'onOrderComplete'));
        $this->plugin->attach('_cred_commerce_order_on_hold', array(&$this, 'onHold'));
        $this->plugin->attach('_cred_commerce_payment_refunded', array(&$this, 'onRefund'));
        $this->plugin->attach('_cred_commerce_payment_cancelled', array(&$this, 'onCancel'));
        $this->plugin->attach('_cred_order_created', array(&$this, 'onOrderCreated'));
        $this->plugin->attach('_cred_order_status_changed', array(&$this, 'onOrderChange'));
    }
    
    public function getProducts()
    {
        return $this->plugin->getProducts();
    }
    
    public function getProduct($id)
    {
        return $this->plugin->getProduct($id);
    }
    
    public function getRelativeProduct($id)  
    { 
        return $this->plugin->getRelativeProduct($id);
    }
    
    public function getAbsoluteProduct($id2)
    { 
        return $this->plugin->getAbsoluteProduct($id2);
    }
    
    public function getCredData()
    {
        return $this->plugin->getCredData();
    }
    
    public function getNewProductLink()
    {
        return $this->plugin->getNewProductLink();
    }
    
    public function onSubmitComplete($post_id, $form_data)
    {
        // get form meta data related to cred commerce
        $this->form=$this->model->getForm($form_data['id'], false);
        
        if ($this->form->isCommerce)
        {
            // HOOKS API
            do_action('cred_commerce_before_add_to_cart', $this->form->ID, $post_id);
            
            // clear cart if needed
            if ($this->form->clearCart)
                $this->plugin->clearCart();
            
            // add product to cart
            if ('post'==$this->form->associateProduct) {
                $product=$this->model->getPostMeta($post_id, $this->form->productField);
			} else {
				if (isset($this->form->product)) {
					$product=$this->form->product;
				} else {
					// No product so return.
					return;
				}
			}
            
            // HOOKS API allow plugins to filter the product
            $product=apply_filters('cred_commerce_add_product_to_cart', $product, $this->form->ID, $post_id);
			
            //if (!$product || empty($product)) return;
            
            $this->plugin->addTocart($product, array('cred_product_id'=>$product, 'cred_form_id'=>$this->form->ID, 'cred_post_id'=>$post_id));
            
            // HOOKS API
            do_action('cred_commerce_after_add_to_cart', $this->form->ID, $post_id);
        }
    }
    
    public function onFormSuccessAction($action, $post_id, $form_data)
    {
        if ($this->form->ID==$form_data['id'] && $this->form->isCommerce)
        {
            // HOOKS API
            do_action('cred_commerce_form_action', $action, $this->form->ID, $post_id, $form_data);
            
            switch ($action)
            {
                case 'cart':
                    wp_redirect($this->plugin->getPageUri('cart'));
                    exit;
                    break;
                case 'checkout':
                    wp_redirect($this->plugin->getPageUri('checkout'));
                    exit;
                    break;
            }
        }
    }
    
    public function getCustomer($post_id, $form_id)
    {
        return $this->plugin->getCustomer($post_id, $form_id);
    }
    
    // trigger notifications on order created (on checkout)
    public function onOrderCreated($data)
    {
        // HOOKS API
        //do_action('cred_commerce_before_send_notifications', $data);
        //cred_log($data);
        if (isset($data['cred_meta']) && $data['cred_meta'])
        {
            $model=CREDC_Loader::get('MODEL/Main');
            CRED_Loader::load('CLASS/Notification_Manager');
            foreach ($data['cred_meta'] as $ii=>$meta)
            {
                if (!isset($meta['cred_form_id'])) continue;
                $form_id=$meta['cred_form_id'];
                $post_id=$meta['cred_post_id'];
                $form=$model->getForm($form_id, true);
                if ($form->isCommerce && isset($form->fields['notification']))
                {
                    $this->_data=array(
                        'order_id'=>$data['order_id'],
                        'cred_meta'=>$meta
                    );
                    add_filter('cred_custom_notification_event', array(&$this, 'notificationOrderCreatedEvent'), 1, 4);
                    CRED_Notification_Manager::triggerNotifications($post_id, array(
                        'event'=>'order_created',
                        'form_id'=>$form_id,
                        'notification'=>$form->fields['notification']
                    ));
                    remove_filter('cred_custom_notification_event', array(&$this, 'notificationOrderCreatedEvent'), 1, 4);
                    $this->_data=false;
                }
            }
        }
        
        // HOOKS API
        //do_action('cred_commerce_after_send_notifications', $data);
    }
    
    // trigger notifications on order status change
    public function onOrderChange($data)
    {
        // HOOKS API
        //do_action('cred_commerce_before_send_notifications', $data);
        
        // send notifications
        if (!isset($data['new_status']) || !in_array($data['new_status'], array('pending', 'failed', 'processing', 'completed', 'on-hold', 'cancelled', 'refunded')))
            return; // not spam with useless notifications ;)
            
        if (isset($data['cred_meta']) && $data['cred_meta'])
        {
            $model=CREDC_Loader::get('MODEL/Main');
            CRED_Loader::load('CLASS/Notification_Manager');
            foreach ($data['cred_meta'] as $ii=>$meta)
            {
                if (!isset($meta['cred_form_id'])) continue;
                $form_id=$meta['cred_form_id'];
                $form_slug='';
				$cred_form_post = get_post($form_id);
				if ($cred_form_post) {
					$form_slug = $cred_form_post->post_name;
				}
                $post_id=$meta['cred_post_id'];
                $form=$model->getForm($form_id, true);
                if ($form->isCommerce && isset($form->fields['notification']))
                {
                    $this->_data=array(
                        'order_id'=>$data['order_id'],
                        'previous_status'=>$data['previous_status'],
                        'new_status'=>$data['new_status'],
                        'cred_meta'=>$meta
                    );
                    add_filter('cred_custom_notification_event', array(&$this, 'notificationOrderEvent'), 1, 4);
                    CRED_Notification_Manager::triggerNotifications($post_id, array(
                        'event'=>'order_modified',
                        'form_id'=>$form_id,
                        'notification'=>$form->fields['notification']
                    ));
                    remove_filter('cred_custom_notification_event', array(&$this, 'notificationOrderEvent'), 1, 4);
                    $this->_data=false;
                }
            }
        }
        
        // HOOKS API
        do_action('cred_commerce_after_send_notifications_form_'.$form_slug, $data);
        do_action('cred_commerce_after_send_notifications', $data);
    }
    
    public function notificationOrderCreatedEvent($result, $notification, $form_id, $post_id)
    {
        //cred_log(array($notification, $form_id, $post_id, $this->_data));
        if ($this->_data)
        {
            if (
                'order_created'==$notification['event']['type'] &&
                $form_id==$this->_data['cred_meta']['cred_form_id'] &&
                $post_id==$this->_data['cred_meta']['cred_post_id']
            )
                $result=true;
        }
        //cred_log($result);
        return $result;
    }
    
    public function notificationOrderEvent($result, $notification, $form_id, $post_id)
    {
        if ($this->_data)
        {
            if (
                'order_modified'==$notification['event']['type'] &&
                $form_id==$this->_data['cred_meta']['cred_form_id'] &&
                $post_id==$this->_data['cred_meta']['cred_post_id'] &&
                isset($notification['event']['order_status']) && 
                isset($this->_data['new_status']) &&
                $this->_data['new_status']==$notification['event']['order_status']
            )
                $result=true;
        }
        return $result;
    }
    
    public function onOrderReceived($data)
    {
        //cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data']))
        {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data)
            {
                // get form meta data related to cred commerce
                $this->form=isset($cred_data['cred_form_id'])?$this->model->getForm($cred_data['cred_form_id'], false):false;
                $post_id=isset($cred_data['cred_post_id'])?intval($cred_data['cred_post_id']):false;
                $user_id=isset($data['user_id'])?intval($data['user_id']):false;
                if ($this->form && $this->form->isCommerce)
                {
                    if ($post_id)
                    {
                        // check if post actually exists !!
                        $_post=get_post($post_id);
                        //if (!$_post) return;
                        
                        if ($_post)
                        {
                            $postdata=array();
                            if ($this->form->fixAuthor && $user_id)
                            {
                                $postdata['post_author']=$user_id;
                            }
                            if (
                                isset($this->form->commerce['order_pending']) && 
                                isset($this->form->commerce['order_pending']['post_status']) &&
                                in_array($this->form->commerce['order_pending']['post_status'], array('draft','pending','private','publish'))
                            )
                            {
                                $postdata['post_status']=$this->form->commerce['order_pending']['post_status'];
                            }
                            if (!empty($postdata))
                            {
                                $postdata['ID']=$post_id;
                                wp_update_post($postdata);
                            }
                        }
                    }
                    
                    // HOOKS API
                    do_action('cred_commerce_after_order_received', $data);
                }
            }
        }
    }
    
    public function onPaymentFailed($data)
    {
        //cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data']))
        {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data)
            {
                // get form meta data related to cred commerce
                $this->form=isset($cred_data['cred_form_id'])?$this->model->getForm($cred_data['cred_form_id'], false):false;
                $post_id=isset($cred_data['cred_post_id'])?intval($cred_data['cred_post_id']):false;
                $user_id=isset($data['user_id'])?intval($data['user_id']):false;
                if ($this->form && $this->form->isCommerce)
                {
                    if ($post_id)
                    {
                        // check if post actually exists !!
                        $_post=get_post($post_id);
                        //if (!$_post) return;
                        
                        if ($_post)
                        {
                            $postdata=array();
                            if ($this->form->fixAuthor && $user_id)
                            {
                                $postdata['post_author']=$user_id;
                            }
                            if (
                                isset($this->form->commerce['order_failed']) && 
                                isset($this->form->commerce['order_failed']['post_status']) &&
                                in_array($this->form->commerce['order_failed']['post_status'], array('draft','pending','private','publish'))
                            )
                            {
                                $postdata['post_status']=$this->form->commerce['order_failed']['post_status'];
                            }
                            if (!empty($postdata))
                            {
                                $postdata['ID']=$post_id;
                                wp_update_post($postdata);
                            }
                        }
                    }
                    
                    // HOOKS API
                    do_action('cred_commerce_after_payment_failed', $data);
                }
            }
        }
    }
    
    public function onPaymentComplete($data)
    {
        //cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data']))
        {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data)
            {
                // get form meta data related to cred commerce
                $this->form=isset($cred_data['cred_form_id'])?$this->model->getForm($cred_data['cred_form_id'], false):false;
                $post_id=isset($cred_data['cred_post_id'])?intval($cred_data['cred_post_id']):false;
                $user_id=isset($data['user_id'])?intval($data['user_id']):false;
                if ($this->form && $this->form->isCommerce)
                {
                    if ($post_id)
                    {
                        // check if post actually exists !!
                        $_post=get_post($post_id);
                        //if (!$_post) return;
                        
                        if ($_post)
                        {
                            $postdata=array();
                            if ($this->form->fixAuthor && $user_id)
                            {
                                $postdata['post_author']=$user_id;
                            }
                            if (
                                isset($this->form->commerce['order_processing']) && 
                                isset($this->form->commerce['order_processing']['post_status']) &&
                                in_array($this->form->commerce['order_processing']['post_status'], array('draft','pending','private','publish'))
                            )
                            {
                                $postdata['post_status']=$this->form->commerce['order_processing']['post_status'];
                            }
                            if (!empty($postdata))
                            {
                                $postdata['ID']=$post_id;
                                wp_update_post($postdata);
                            }
                        }
                    }
                    
                    // HOOKS API
                    do_action('cred_commerce_after_payment_completed', $data);
                }
            }
        }
    }
    
    public function onOrderComplete($data)
    {
        //cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data']))
        {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data)
            {
                // get form meta data related to cred commerce
                $this->form=isset($cred_data['cred_form_id'])?$this->model->getForm($cred_data['cred_form_id'], false):false;
                $form_slug='';
				if (isset($cred_data['cred_form_id'])) {
					$cred_form_post = get_post($cred_data['cred_form_id']);
					if ($cred_form_post) {
						$form_slug = $cred_form_post->post_name;
					}
				}
                $post_id=isset($cred_data['cred_post_id'])?intval($cred_data['cred_post_id']):false;
                $user_id=isset($data['user_id'])?intval($data['user_id']):false;
                if ($this->form && $this->form->isCommerce)
                {
                    if ($post_id)
                    {
                        // check if post actually exists !!
                        $_post=get_post($post_id);
                        //if (!$_post) return;
                        
                        if ($_post)
                        {
                            $postdata=array();
                            if ($this->form->fixAuthor && $user_id)
                            {
                                $postdata['post_author']=$user_id;
                            }
                            if (
                                isset($this->form->commerce['order_completed']) && 
                                isset($this->form->commerce['order_completed']['post_status']) &&
                                in_array($this->form->commerce['order_completed']['post_status'], array('draft','pending','private','publish'))
                            )
                            {
                                $postdata['post_status']=$this->form->commerce['order_completed']['post_status'];
                            }
                            if (!empty($postdata))
                            {
                                $postdata['ID']=$post_id;
                                wp_update_post($postdata);
                            }
                        }
                    }
                    
                    // HOOKS API
                    do_action('cred_commerce_after_order_completed_form_'.$form_slug, $data);
                    do_action('cred_commerce_after_order_completed', $data);
                }
            }
        }
    }
    
    public function onHold($data)
    {
        //cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data']))
        {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data)
            {
                // get form meta data related to cred commerce
                $this->form=isset($cred_data['cred_form_id'])?$this->model->getForm($cred_data['cred_form_id'], false):false;
                $post_id=isset($cred_data['cred_post_id'])?intval($cred_data['cred_post_id']):false;
                $user_id=isset($data['user_id'])?intval($data['user_id']):false;
                if ($this->form && $this->form->isCommerce)
                {
                    if ($post_id)
                    {
                        // check if post actually exists !!
                        $_post=get_post($post_id);
                        //if (!$_post) return;
                        
                        if ($_post)
                        {
                            $postdata=array();
                            if ($this->form->fixAuthor && $user_id)
                            {
                                $postdata['post_author']=$user_id;
                            }
                            if (
                                isset($this->form->commerce['order_on_hold']) && 
                                isset($this->form->commerce['order_on_hold']['post_status']) &&
                                in_array($this->form->commerce['order_on_hold']['post_status'], array('draft','pending','private','publish'))
                            )
                            {
                                $postdata['post_status']=$this->form->commerce['order_on_hold']['post_status'];
                            }
                            if (!empty($postdata))
                            {
                                $postdata['ID']=$post_id;
                                wp_update_post($postdata);
                            }
                        }
                    }
                    
                    // HOOKS API
                    do_action('cred_commerce_after_order_on_hold', $data);
                }
            }
        }
    }
    
    public function onRefund($data)
    {
        //cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data']))
        {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data)
            {
                // get form meta data related to cred commerce
                $this->form=isset($cred_data['cred_form_id'])?$this->model->getForm($cred_data['cred_form_id'], false):false;
                $post_id=isset($cred_data['cred_post_id'])?intval($cred_data['cred_post_id']):false;
                $user_id=isset($data['user_id'])?intval($data['user_id']):false;
                if ($this->form && $this->form->isCommerce)
                {
                    if ($post_id)
                    {
                        // check if post actually exists !!
                        $_post=get_post($post_id);
                        //if (!$_post) return;
                        
                        if ($_post)
                        {
                            $postdata=array();
                            if (
                                isset($this->form->commerce['order_refunded']) && 
                                isset($this->form->commerce['order_refunded']['post_status']) &&
                                in_array($this->form->commerce['order_refunded']['post_status'], array('draft','pending','private','publish'))
                            )
                            {
                                $postdata['post_status']=$this->form->commerce['order_refunded']['post_status'];
                            }
                            if (!empty($postdata))
                            {
                                $postdata['ID']=$post_id;
                                wp_update_post($postdata);
                            }
                            
                            if (
                                isset($this->form->commerce['order_refunded']) && 
                                isset($this->form->commerce['order_refunded']['post_status']) &&
                                'trash'==$this->form->commerce['order_refunded']['post_status']
                            )
                            {
                                // move to trash
                                wp_delete_post( $post_id, false );
                            }
                            elseif (
                                isset($this->form->commerce['order_refunded']) && 
                                isset($this->form->commerce['order_refunded']['post_status']) &&
                                'delete'==$this->form->commerce['order_refunded']['post_status']
                            )
                            {
                                // delete
                                wp_delete_post( $post_id, true );
                            }
                        }
                    }
                    
                    // HOOKS API
                    do_action('cred_commerce_after_payment_refunded', $data);
                }
            }
        }
    }
    
    public function onCancel($data)
    {
        //cred_log($data);
        // get form data
        if (isset($data['extra_data']) && $data['extra_data'] && is_array($data['extra_data']))
        {
            // possible to be multiple commerce forms/posts on same order
            foreach ($data['extra_data'] as $cred_data)
            {
                // get form meta data related to cred commerce
                $this->form=isset($cred_data['cred_form_id'])?$this->model->getForm($cred_data['cred_form_id'], false):false;
                $post_id=isset($cred_data['cred_post_id'])?intval($cred_data['cred_post_id']):false;
                $user_id=isset($data['user_id'])?intval($data['user_id']):false;
                if ($this->form && $this->form->isCommerce)
                {
                    if ($post_id)
                    {
                        // check if post actually exists !!
                        $_post=get_post($post_id);
                        //if (!$_post) return;
                        
                        if ($_post)
                        {
                            $postdata=array();
                            if (
                                isset($this->form->commerce['order_cancelled']) && 
                                isset($this->form->commerce['order_cancelled']['post_status']) &&
                                in_array($this->form->commerce['order_cancelled']['post_status'], array('draft','pending','private','publish'))
                            )
                            {
                                $postdata['post_status']=$this->form->commerce['order_cancelled']['post_status'];
                            }
                            if (!empty($postdata))
                            {
                                $postdata['ID']=$post_id;
                                wp_update_post($postdata);
                            }
                            
                            if (
                                isset($this->form->commerce['order_cancelled']) && 
                                isset($this->form->commerce['order_cancelled']['post_status']) &&
                                'trash'==$this->form->commerce['order_cancelled']['post_status']
                            )
                            {
                                // move to trash
                                wp_delete_post( $post_id, false );
                            }
                            elseif (
                                isset($this->form->commerce['order_cancelled']) && 
                                isset($this->form->commerce['order_cancelled']['post_status']) &&
                                'delete'==$this->form->commerce['order_cancelled']['post_status']
                            )
                            {
                                // delete
                                wp_delete_post( $post_id, true );
                            }
                        }
                    }
                    
                    // HOOKS API
                    do_action('cred_commerce_after_payment_cancelled', $data);
                }
            }
        }
    }
}
