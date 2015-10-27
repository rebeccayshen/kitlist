<?php
/**************************************************

Cred base model

**************************************************/
abstract class CRED_Abstract_Model
{
    protected $wpdb = null;
    
    function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
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
            $data=str_replace(array('\r', '\n'), array("\r", "\n"), esc_sql($data));
        return $data;
    }

    protected function esc_meta_data($data)
    {
		//special escape for meta data to prevent serialize eliminate CRLF (\r\n)
        if (is_array($data) || is_object($data))
        {
            foreach ($data as $ii=>$data_val)
            {
                if (is_object($data))
                    $data->$ii=$this->esc_meta_data($data_val);
                elseif (is_array($data))
                    $data[$ii]=$this->esc_meta_data($data_val);
            }
        }
        else
            $data=esc_sql(preg_replace('/\r\n?|\n/', '%%CRED_NL%%', $data));
        return $data;
    }

    protected function unesc_meta_data($data)
    {
        //reverse special escape for meta data to prevent serialize eliminate CRLF (\r\n)
        if (is_array($data) || is_object($data))
        {
            foreach ($data as $ii=>$data_val)
            {
                if (is_object($data))
                    $data->$ii=$this->unesc_meta_data($data_val);
                elseif (is_array($data))
                    $data[$ii]=$this->unesc_meta_data($data_val);
            }
        }
        else
            $data=preg_replace('/%%CRED_NL%%/', "\r\n", $data);
        return $data;
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
}
