<?php
// Declare the interface
interface CRED_Commerce_Plugin_Interface
{
    // events handlers
    public function attach($event, $callable, $unique=true, $order=false);
    public function detach($event, $callable);
    public function dispatch($event);
    
    // commerce methods
    public function getProducts($order='title', $ordering='ASC');
    public function getProduct($id);
    public function getRelativeProduct($id);
    public function getAbsoluteProduct($id2);
    public function getCustomer($post_id, $form_id);
    public function addToCart($product_id, $extra_data=array());
    public function clearCart();
    public function getPageUri($what='checkout');
    public function getNewProductLink();
    public function getCredData();
}
