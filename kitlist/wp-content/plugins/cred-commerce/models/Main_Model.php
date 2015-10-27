<?php
/**************************************************

Cred Commerce settings model

**************************************************/

final class CRED_Commerce_Main_Model implements CREDC_Singleton
{

    protected $wpdb = null;
    private $option_name = 'cred_commerce_settings';
    private $credmodel=null;
    
    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
		if (class_exists('CRED_Loader', false))
        	$this->credmodel=CRED_Loader::get('MODEL/Forms');
    }
    
    public function merge()
    {
        if (func_num_args() < 1) return;
        
        $arrays = func_get_args();
        $merged = array_shift($arrays);
        
        $isTargetObject=false;
        if (is_object($merged))
        {
            $isTargetObject=true;
            $merged=(array)$merged;
        }
        
        foreach ($arrays as $arr)
        {
            $isObject=false;
            if (is_object($arr))
            {
                $isObject=true;
                $arr=(array)$arr;
            }
                
            foreach($arr as $key => $val)
            {
                if(array_key_exists($key, $merged) && (is_array($val) || is_object($val)))
                {
                    $merged[$key] = $this->merge($merged[$key], $arr[$key]);
                    if (is_object($val))
                        $merged[$key]=(object)$merged[$key];
                }
                else
                    $merged[$key] = $val;
            }
            
            /*if ($isObject)
            {
               $arr=(object)$arr; 
            }*/
        }
        if ($isTargetObject)
        {
            $isTargetObject=false;
            $merged=(object)$merged;
        }
        return $merged;
    }
    
    public function applyDefaults($arr, $defaults=array())
    {
        if (!empty($defaults))
        {
            foreach ($arr as $ii=>$item)
                $arr[$ii]=$this->merge($defaults, $arr[$ii]);
        }
        return $arr;
    }
    
    public function filterByKeys($a, $f)
    {
        return array_intersect_key((array)$a, array_flip((array)$f));
    }
    
    protected function esc_data($data)
    {
        if (is_array($data) || is_object($data))
        {
            foreach ($data as $ii=>$data_val)
            {
                if (is_object($data))
                    $data->$ii=$this->esc_data($data_val);
                elseif (is_array($data))
                    $data[$ii]=$this->esc_data($data_val);
            }
        }
        else
            $data=esc_sql($data);
        return $data;
    }
    
    /*public function getPostBy($params)
    {
        $select=false;
        $tables=" p.* FROM {$this->wpdb->posts} AS p ";
        $where=" WHERE 1=1 ";
        $order="";
        $limit="";
        if (isset($params['meta']))
        {
            $select="SELECT ";
            $ii=0;
            foreach($params['meta'] as $mkey=>$mval)
            {
                $ii++;
                $tables.=", {$this->wpdb->postmeta} AS pm{$ii}";
                $where.=$this->wpdb->prepare(" AND (p.ID=pm{$ii}.post_id AND pm{$ii}.meta_key='%s' AND pm{$ii}.meta_value='%s')", $mkey, $mval);
            }
        }
        
        if (isset($params['post']))
        {
            if (!$select)
            {
                $select="SELECT ";
            }
            
            foreach($params['post'] as $pkey=>$pval)
            {
                if (in_array($pkey, array('ID', 'post_title', 'post_status', 'post_type')))
                {
                    $where.=$this->wpdb->prepare(" AND (p.$pkey='%s')", $pval);
                }
            }
        }
        
        if ($select)
        {
            $sql=$select.$tables.$where.$order.$limit;
            return $this->wpdb->get_results($sql);
        }
        
        return false;
    }*/
    
    
    public function prepareDB()
    {
    }
    
    public function getSettings()
    {
        return get_option($this->option_name, array());
    }
    
    public function updateSettings($settings=array())
    {
        return update_option($this->option_name, $settings);
    }
    
    public function getPostBy($params)
    {
        $select=false;
        $tables=" p.* FROM {$this->wpdb->posts} AS p ";
        $where=" WHERE 1=1 ";
        $order="";
        $limit="";
        if (isset($params['meta']))
        {
            $select="SELECT ";
            $ii=0;
            foreach($params['meta'] as $mkey=>$mval)
            {
                $ii++;
                $tables.=", {$this->wpdb->postmeta} AS pm{$ii}";
                $where.=$this->wpdb->prepare(" AND (p.ID=pm{$ii}.post_id AND pm{$ii}.meta_key='%s' AND pm{$ii}.meta_value='%s')", $mkey, $mval);
            }
        }
        
        if (isset($params['post']))
        {
            if (!$select)
            {
                $select="SELECT ";
            }
            
            foreach($params['post'] as $pkey=>$pval)
            {
                if (in_array($pkey, array('ID', 'post_title', 'post_status', 'post_type')))
                {
                    $where.=$this->wpdb->prepare(" AND (p.$pkey='%s')", $pval);
                }
            }
        }
        
        if ($select)
        {
            $sql=$select.$tables.$where.$order.$limit;
            return $this->wpdb->get_results($sql);
        }
        
        return false;
    }
    
    public function getPostMeta($post_id, $meta)
    {
        return $this->credmodel->getPostMeta($post_id, $meta);
    }
    
    public function getFormCustomField($form_id, $field)
    {
        return $this->credmodel->getFormCustomField($form_id, $field);
    }
    
    public function updateFormCustomField($form_id, $field, $data)
    {
        return $this->credmodel->updateFormCustomField($form_id, $field, $data);
    }
    
    public function getForm($form_id, $full=true)
    {
        if ($full)
        {
            $form=$this->credmodel->getForm($form_id, array('commerce'));
            if ($form && isset($form->fields['commerce']))
                $form->commerce=$form->fields['commerce'];
            else
            {
                $form=new stdClass;
                $form->ID=$form_id;
            }
        }
        else
        {
            $form=new stdClass;
            $form->ID=$form_id;
            $form->commerce=$this->credmodel->getFormCustomField($form_id, 'commerce');
        }
        if (isset($form->commerce))
        {
            if (isset($form->commerce['enable'])&&$form->commerce['enable'])
                $form->isCommerce=true;
            else
                $form->isCommerce=false;
            
            if (isset($form->commerce['clear_cart'])&&$form->commerce['clear_cart'])
                $form->clearCart=true;
            else
                $form->clearCart=false;
            
            if (isset($form->commerce['fix_author'])&&$form->commerce['fix_author'])
                $form->fixAuthor=true;
            else
                $form->fixAuthor=false;
                
            if (isset($form->commerce['associate_product']))
                $form->associateProduct=$form->commerce['associate_product'];
            
            if (isset($form->commerce['product']))
                $form->product=$form->commerce['product'];
            
            if (isset($form->commerce['product_field']))
                $form->productField=$form->commerce['product_field'];
        }
        else
        {
            $form->isCommerce=false;
        }
        return $form;    
    }
    
    public function updateForm($form_id, $data)
    {
        $this->credmodel->updateFormCustomField($form_id, 'commerce', $data);
    }
}
