<?php
/**
*
*   Base Plugin, used as Skeleton
*
**/
abstract class CRED_Commerce_Plugin_Base implements CRED_Commerce_Plugin_Interface
{
    private $events=array();
    
    /**
     * __construct function.
     *
     * @access public
     */
    public function __construct() 
    {
        $this->events=array();
    }
    
    public function attach($event, $callable, $unique=true, $order=false)
    {
        if ($event && ''!=$event && is_callable($callable))
        {
            if (!isset($this->events[$event]))
                $this->events[$event]=array();
                
            if ($unique && in_array($callable, $this->events[$event]))
                return;
                
            $this->events[$event][]=$callable;
            /*if (false===$order)
                $this->events[$event][]=$callable;
            else
                $this->events[$event][]=$callable;*/
        }
    }
    
    public function detach($event, $callable)  { }
    
    public function dispatch($event)
    {
        if (!empty($this->events[$event]))
        {
            $args = array_slice(func_get_args(), 1); // Get pure arguments
            foreach ($this->events[$event] as $callable)
                call_user_func_array( $callable, $args );
        }
    }
    
    public function getProducts($order='title', $ordering='ASC') {  }
    
    public function getProduct($id)  { }
    
    public function getRelativeProduct($id)  { }
    
    public function getAbsoluteProduct($id2)  { }
    
    public function getCustomer($post_id, $form_id) { }
    
    public function getNewProductLink() { }
    
    public function getPageUri($what='checkout') { }
 
    public function clearCart() { }
    
    public function getCredData() { }
    
    public function addToCart($product_id, $extra_data=array()) { }
}
