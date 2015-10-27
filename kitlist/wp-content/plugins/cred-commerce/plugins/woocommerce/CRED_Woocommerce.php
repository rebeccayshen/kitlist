<?php
/**
*
*   Glue plugin for woocommerce, should work for both 1.x and 2.0.x versions of woocommerce
*
**/
final class CRED_Woocommerce_Plugin extends CRED_Commerce_Plugin_Base implements CRED_Commerce_Plugin_Interface
{
    /**
     * __construct function.
     *
     * @access public
     */
    public function __construct() 
    {
        parent::__construct();
        
        // Add hooks
        add_action( 'woocommerce_new_order', array( &$this, 'onNewOrder'), 100, 1 );
        add_action('woocommerce_checkout_update_order_meta', array( &$this, 'onCheckout'), 100, 1);
        add_filter( 'woocommerce_get_cart_item_from_session', array( &$this, 'loadExtraCartData'), 100, 3);
        add_action('woocommerce_order_status_pending', array( &$this, 'onOrderReceived'), 100, 1);
        add_action('woocommerce_order_status_failed', array( &$this, 'onPaymentFailed'), 100, 1);
        add_action('woocommerce_order_status_processing', array( &$this, 'onPaymentComplete'), 100, 1);
        add_action('woocommerce_order_status_completed', array( &$this, 'onOrderComplete'), 100, 1);
        add_action('woocommerce_order_status_on-hold', array( &$this, 'onHold'), 100, 1);
        add_action('woocommerce_order_status_cancelled', array( &$this, 'onCancel'), 100, 1);
        add_action('woocommerce_order_status_refunded', array( &$this, 'onRefund'), 100, 1);
        // notify on order status change
        add_action('woocommerce_order_status_changed', array(&$this, 'onOrderChange'), 200, 3);
		// auto-complete for after successful payment (auto-payment for free products)
		//add_filter('woocommerce_payment_complete_order_status', array(&$this, 'autoCompleteOrderStatus'), 10, 2);
    }
    
    public function getProducts($order='title', $ordering='ASC')
    {
        $actual_order='title';
        $actual_ordering='ASC';
        switch ($order)
        {
            case 'date':
                $actual_order='post_date';
                break;
            case 'title':
            default:
                $actual_order='title';
                break;
        }
        switch (strtoupper($ordering))
        {
            case 'DESC':
                $actual_ordering='DESC';
                break;
            case 'ASC':
            default:
                $actual_ordering='ASC';
                break;
        }
        
        $products=get_posts(array(
            'numberposts'     => -1,
            'offset'          => 0,
            'orderby'         => $actual_order,
            'order'           => $actual_ordering,
            'include'         => '',
            'exclude'         => '',
            'meta_key'        => '',
            'meta_value'      => '',
            'post_type'       => 'product',
            'post_status'     => 'publish',
            'suppress_filters' => false // allow WPML to filter
        ));
        // make an associative array
        $returned_products=array();
        foreach ($products as $ii=>$product)
        {
            $returned_products[$product->ID]=$product->post_title;
            unset($products[$ii]);
        }
        unset($products);
        return $returned_products;
    }
    
    public function getProduct($id)
    {
        $product = get_post($id);
        if ($product)
        {
            $return_product=(object)array(
                'ID'=>$product->ID,
                'title'=>$product->post_title,
                'name'=>$product->post_name,
                // get price also
                'price'=>get_post_meta($product->ID, '_price', true)
            );
        }
        else
            $return_product=false;
        return $return_product;
    }
    
    public function getRelativeProduct($id)
    {
        $product=$this->getProduct($id);
        if ($product)
            return $product->name;
        return false;
    }
    
    public function getAbsoluteProduct($id2)
    {
        $products=get_posts(array(
            'numberposts'     => -1,
            'offset'          => 0,
            'post_type'=>'product',
            'name'=>$id2,
            'post_status'=>'publish',
            'suppress_filters'=>false
        ));
        if ($products)
            return $products[0]->ID;
        return false;
    }
    
    public function getNewProductLink()
    {
        return admin_url('post-new.php').'?post_type=product';
    }
    
    public function getPageUri($what='checkout')
    {
        global $woocommerce;
        
        $uri='';
        switch ($what)
        {
            case 'cart':
                $uri = $woocommerce->cart->get_cart_url();
                break;
            case 'checkout':
            default:
                $uri = $woocommerce->cart->get_checkout_url();
                break;
        }
        return $uri;
    }
 
    public function clearCart()
    {
        global $woocommerce;
        $woocommerce->cart->empty_cart(true);
    }
    
    public function addToCart($product_id, $extra_data=array())
    {
        global $woocommerce;
        $woocommerce->cart->add_to_cart( $product_id, 1, '', '', array('cred_meta' => $extra_data) );
        //cred_log($woocommerce->cart->cart_contents);
    }
    
    public function loadExtraCartData($data, $values, $key)
    {
        //cred_log($values);
        if (isset($values['cred_meta']))
            $data['cred_meta']=$values['cred_meta'];
        return $data;
    }

	public function autoCompleteOrderStatus($order_status, $order_id)
	{
		$order=new WC_Order($order_id);
		// auto-complete only for 'processing' and free-products (orders)
		if ('processing'==$order_status && isset($order->order_total) && 0==$order->order_total)
			return 'completed';
		return $order_status;
	}
    
    public function onOrderChange($order_id, $previous_status, $new_status)
    {
        $cred_meta=get_post_meta($order_id, '_cred_meta', true);
        if ($cred_meta)
        {
            $this->dispatch('_cred_order_status_changed', array(
                'order_id'=>$order_id,
                'previous_status'=>$previous_status,
                'new_status'=>$new_status,
                'cred_meta'=>maybe_unserialize($cred_meta)
            ));
        }
    }
    
    public function onCheckout($order_id)
    {
        $cred_meta=get_post_meta($order_id, '_cred_meta', true);
        if ($cred_meta)
        {
            // notify that cred commerce order is created
            $this->dispatch('_cred_order_created', array(
                'order_id'=>$order_id,
                'cred_meta'=>maybe_unserialize($cred_meta)
            ));
        }
    }
    
    public function onNewOrder($order_id)
    {
        global $woocommerce;
        
        //cred_log($order_id);
        //cred_log($woocommerce->cart->cart_contents);
        $cred_meta=array();
        $cred_post_ids=array();
        $cred_form_ids=array();
        foreach ($woocommerce->cart->cart_contents as $cart_key=>$cart_data)
        {
            $data=(array)$cart_data;
            if (isset($data['cred_meta']))
            {
                $cred_meta[]=$data['cred_meta'];
                if (isset($data['cred_meta']['cred_post_id']))
                    $cred_post_ids[]=$data['cred_meta']['cred_post_id'];
                if (isset($data['cred_meta']['cred_form_id']))
                    $cred_form_ids[]=$data['cred_meta']['cred_form_id'];
            }
        }
        //cred_log(array($cred_meta, $cred_post_ids, $cred_form_ids));
        if (!empty($cred_meta))
        {
            // add meta fields related to CRED forms, on current order
            add_post_meta($order_id, '_cred_meta', serialize($cred_meta));
            
            // add these to speed-up searching
            foreach ($cred_post_ids as $cred_post_id)
                add_post_meta($order_id, '_cred_post_id', $cred_post_id, false);
            
            foreach ($cred_form_ids as $cred_form_id)
                add_post_meta($order_id, '_cred_form_id', $cred_form_id, false);
        }
    }
    
    public function getCustomer($post_id, $form_id)
    {
        global $woocommerce;
        
        $model=CREDC_Loader::get('MODEL/Main');
        
        $orders=$model->getPostBy(array(
            'meta'=>array(
                '_cred_post_id'=>$post_id,
                '_cred_form_id'=>$form_id,
            ),
            'post'=>array(
                'post_type'=>'shop_order'
            )
        ));
        
        //cred_log(array($post_id, $form_id, $orders));
        
        if ($orders)
        {
            $order=end($orders);
            $order=new WC_Order($order->ID);
            //cred_log($order);
            if ($order && $order->user_id)
            {
                
                $user = get_userdata( $order->user_id );
                // add some extra fields
                if ($user)
                {
                    if (!isset($user->user_firstname))
                        $user->user_firstname=get_user_meta($user->ID, 'user_firstname', true);
                    if (!isset($user->user_lastname))
                        $user->user_lastname=get_user_meta($user->ID, 'user_lastname', true);
                }
                return $user;
            }
        }
        return false;
    }
    
    public function getCredData()
    {
        global $woocommerce;
        
        $cred_meta=array();
        foreach ($woocommerce->cart->cart_contents as $cart_key=>$cart_data)
        {
            $data=(array)$cart_data;
            if (isset($data['cred_meta']))
                $cred_meta[]=$data['cred_meta'];
        }
        return $cred_meta;
    }
    
    public function onOrderReceived($data)
    {
        global $woocommerce;
        
        //cred_log($order_id);
        $cred_meta=get_post_meta($order_id, '_cred_meta', true);
        //cred_log($cred_meta);
        if ($cred_meta && ''!=$cred_meta)
            $cred_meta=maybe_unserialize($cred_meta);
        else
            $cred_meta=false;
            
        // not related to cred commerce, bypass
        if (false===$cred_meta) return;
        
        // check if order already is processed by CRED Commerce
        $cred_commerce_pending = get_post_meta( $order_id, 'cred_commerce_pending', true);

        // if not, run processing the order
        if (
            !$cred_commerce_pending || 
            ''==$cred_commerce_pending || 
            '1'!=$cred_commerce_pending 
        ) 
        {
            $order=new WC_Order($order_id);
            
            $email = $order->billing_email;

            $user_id = 0;
            if ( email_exists($email)) 
            {
                $user_id = email_exists($email);
            }		
            
            // add meta value to indicate CRED_Commerce processing has taken place
            add_post_meta($order->id, 'cred_commerce_pending', '1', true);
            
            // update order status
            //if ('woocommerce_order_status_processing'==current_filter())
              //  $order->update_status('completed');
            
            // call 3rd party to do further processing on completion
            $this->dispatch('_cred_commerce_order_received', array('user_id'=>$user_id, 'transaction_id'=>$order_id, 'extra_data'=>$cred_meta));
        }
    }
    
    public function onPaymentFailed($data)
    {
        global $woocommerce;
        
        //cred_log($order_id);
        $cred_meta=get_post_meta($order_id, '_cred_meta', true);
        //cred_log($cred_meta);
        if ($cred_meta && ''!=$cred_meta)
            $cred_meta=maybe_unserialize($cred_meta);
        else
            $cred_meta=false;
            
        // not related to cred commerce, bypass
        if (false===$cred_meta) return;
        
        // check if order already is processed by CRED Commerce
        $cred_commerce_failed = get_post_meta( $order_id, 'cred_commerce_failed', true);

        // if not, run processing the order
        if (
            !$cred_commerce_failed || 
            ''==$cred_commerce_failed || 
            '1'!=$cred_commerce_failed 
        ) 
        {
            $order=new WC_Order($order_id);
            
            $email = $order->billing_email;

            $user_id = 0;
            if ( email_exists($email)) 
            {
                $user_id = email_exists($email);
            }		
            
            // add meta value to indicate CRED_Commerce processing has taken place
            add_post_meta($order->id, 'cred_commerce_failed', '1', true);
            
            // update order status
            //if ('woocommerce_order_status_processing'==current_filter())
              //  $order->update_status('completed');
            
            // call 3rd party to do further processing on completion
            $this->dispatch('_cred_commerce_payment_failed', array('user_id'=>$user_id, 'transaction_id'=>$order_id, 'extra_data'=>$cred_meta));
        }
    }

    public function onPaymentComplete($order_id) 
    {
        global $woocommerce;
        
        //cred_log($order_id);
        $cred_meta=get_post_meta($order_id, '_cred_meta', true);
        //cred_log($cred_meta);
        if ($cred_meta && ''!=$cred_meta)
            $cred_meta=maybe_unserialize($cred_meta);
        else
            $cred_meta=false;
            
        // not related to cred commerce, bypass
        if (false===$cred_meta) return;
        
        // check if order already is processed by CRED Commerce
        $cred_commerce_processing = get_post_meta( $order_id, 'cred_commerce_processing', true);

        // if not, run processing the order
        if (
            !$cred_commerce_processing || 
            ''==$cred_commerce_processing || 
            '1'!=$cred_commerce_processing 
        ) 
        {
            $order=new WC_Order($order_id);
            
            $email = $order->billing_email;

            $user_id = 0;
            if ( email_exists($email)) 
            {
                $user_id = email_exists($email);
            }		
            
            // add meta value to indicate CRED_Commerce processing has taken place
            add_post_meta($order->id, 'cred_commerce_processing', '1', true);
            
            // update order status
            //if ('woocommerce_order_status_processing'==current_filter())
              //  $order->update_status('completed');
            
            // call 3rd party to do further processing on completion
            $this->dispatch('_cred_commerce_payment_completed', array('user_id'=>$user_id, 'transaction_id'=>$order_id, 'extra_data'=>$cred_meta));
        }
    }
    
    public function onOrderComplete($order_id) 
    {
        global $woocommerce;
        
        //cred_log($order_id);
        $cred_meta=get_post_meta($order_id, '_cred_meta', true);
        //cred_log($cred_meta);
        if ($cred_meta && ''!=$cred_meta)
            $cred_meta=maybe_unserialize($cred_meta);
        else
            $cred_meta=false;
            
        // not related to cred commerce, bypass
        if (false===$cred_meta) return;
        
        // check if order already is processed by CRED Commerce
        $cred_commerce_processed = get_post_meta( $order_id, 'cred_commerce_processed', true);

        // if not, run processing the order
        if (
            !$cred_commerce_processed || 
            ''==$cred_commerce_processed || 
            '1'!=$cred_commerce_processed 
        ) 
        {
            $order=new WC_Order($order_id);
            
            $email = $order->billing_email;

            $user_id = 0;
            if ( email_exists($email)) 
            {
                $user_id = email_exists($email);
            }		
            
            // add meta value to indicate CRED_Commerce processing has taken place
            add_post_meta($order->id, 'cred_commerce_processed', '1', true);
            
            // update order status
            //if ('woocommerce_order_status_processing'==current_filter())
              //  $order->update_status('completed');
            
            // call 3rd party to do further processing on completion
            $this->dispatch('_cred_commerce_order_completed', array('user_id'=>$user_id, 'transaction_id'=>$order_id, 'extra_data'=>$cred_meta));
        }
    }
    
    public function onHold($data)
    {
        global $woocommerce;
        
        //cred_log($order_id);
        $cred_meta=get_post_meta($order_id, '_cred_meta', true);
        //cred_log($cred_meta);
        if ($cred_meta && ''!=$cred_meta)
            $cred_meta=maybe_unserialize($cred_meta);
        else
            $cred_meta=false;
            
        // not related to cred commerce, bypass
        if (false===$cred_meta) return;
        
        // check if order already is processed by CRED Commerce
        $cred_commerce_on_hold = get_post_meta( $order_id, 'cred_commerce_on_hold', true);

        // if not, run processing the order
        if (
            !$cred_commerce_on_hold || 
            ''==$cred_commerce_on_hold || 
            '1'!=$cred_commerce_on_hold 
        ) 
        {
            $order=new WC_Order($order_id);
            
            $email = $order->billing_email;

            $user_id = 0;
            if ( email_exists($email)) 
            {
                $user_id = email_exists($email);
            }		
            
            // add meta value to indicate CRED_Commerce processing has taken place
            add_post_meta($order->id, 'cred_commerce_on_hold', '1', true);
            
            // update order status
            //if ('woocommerce_order_status_processing'==current_filter())
              //  $order->update_status('completed');
            
            // call 3rd party to do further processing on completion
            $this->dispatch('_cred_commerce_order_on_hold', array('user_id'=>$user_id, 'transaction_id'=>$order_id, 'extra_data'=>$cred_meta));
        }
    }
    
    public function onRefund($order_id) 
    {
        global $woocommerce;
        
        //cred_log($order_id);
        $cred_meta=get_post_meta($order_id, '_cred_meta', true);
        //cred_log($cred_meta);
        if ($cred_meta && ''!=$cred_meta)
            $cred_meta=maybe_unserialize($cred_meta);
        else
            $cred_meta=false;
            
        // not related to cred commerce, bypass
        if (false===$cred_meta) return;
        
        // check if order already is processed by CRED Commerce
        $cred_commerce_refunded = get_post_meta( $order_id, 'cred_commerce_refunded', true);

        // if not, run processing the order
        if (
            !$cred_commerce_refunded || 
            ''==$cred_commerce_refunded || 
            '1'!=$cred_commerce_refunded 
        ) 
        {
            $order=new WC_Order($order_id);
            
            $email = $order->billing_email;

            $user_id = 0;
            if ( email_exists($email)) 
            {
                $user_id = email_exists($email);
            }		
            
            // add meta value to indicate CRED_Commerce processing has taken place
            add_post_meta($order->id, 'cred_commerce_refunded', '1', true);
            
            // call 3rd party to do further processing on completion
            $this->dispatch('_cred_commerce_payment_refunded', array('user_id'=>$user_id, 'transaction_id'=>$order_id, 'extra_data'=>$cred_meta));
        }
    }
    
    public function onCancel($order_id) 
    {
        global $woocommerce;
        
        //cred_log($order_id);
        $cred_meta=get_post_meta($order_id, '_cred_meta', true);
        //cred_log($cred_meta);
        if ($cred_meta && ''!=$cred_meta)
            $cred_meta=maybe_unserialize($cred_meta);
        else
            $cred_meta=false;
            
        // not related to cred commerce, bypass
        if (false===$cred_meta) return;
        
        // check if order already is processed by CRED Commerce
        $cred_commerce_cancelled = get_post_meta( $order_id, 'cred_commerce_cancelled', true);

        // if not, run processing the order
        if (
            !$cred_commerce_cancelled || 
            ''==$cred_commerce_cancelled || 
            '1'!=$cred_commerce_cancelled 
        ) 
        {
            $order=new WC_Order($order_id);
            
            $email = $order->billing_email;

            $user_id = 0;
            if ( email_exists($email)) 
            {
                $user_id = email_exists($email);
            }		
            
            // add meta value to indicate CRED_Commerce processing has taken place
            add_post_meta($order->id, 'cred_commerce_cancelled', '1', true);
            
            // call 3rd party to do further processing on completion
            $this->dispatch('_cred_commerce_payment_cancelled', array('user_id'=>$user_id, 'transaction_id'=>$order_id, 'extra_data'=>$cred_meta));
        }
    }
}
