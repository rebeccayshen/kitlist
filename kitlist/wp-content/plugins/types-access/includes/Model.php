<?php

/*
*   Access Model
*
*/

final class Access_Model implements TAccess_Singleton
{

    private $wpdb;
    
    public function __construct()
    {
        global $wpdb;
        
        $this->wpdb=$wpdb;
    }
    
    public function getAccessVersion()
    {
        return get_option('wpcf-access-version-check', false);
    }
    
    public function updateAccessVersion($data)
    {
        return update_option('wpcf-access-version-check', $data);
    }
    
    // WATCHOUT: in some places 'wpcf-access' was used
    public function getAccessMeta($post_id)
    {
        return get_post_meta($post_id, '_types_access', true);
    }
    
    public function updateAccessMeta($post_id, $data)
    {
        return update_post_meta($post_id, '_types_access', $data);
    }
    
    public function deleteAccessMeta($post_id)
    {
        return delete_post_meta($post_id, '_types_access');
    }
    
    public function getAccessRoles()
    {
        return get_option('wpcf-access-roles', array());
    }
    
    public function updateAccessRoles($settings)
    {
        return update_option('wpcf-access-roles', $settings);
    }
    
    public function getAccessTypes()
    {
        $access_types = get_option('wpcf-access-types', array());
        
        /*
        // merge with Access settings saved in Types tables, since Access is standalone now
       $isTypesActive = Access_Helper::wpcf_access_is_wpcf_active();
        
        if ($isTypesActive)
        {
            $wpcf_types = $this->getWpcfTypes();
            
            //taccess_log(array($access_types, $wpcf_types));
            
            foreach ($wpcf_types as $t=>$d)
            {
                if (isset($d['_wpcf_access_capabilities']))
                {
                    if (!isset($access_types[$t]))
                        $access_types[$t] = $d['_wpcf_access_capabilities'];
                    unset($wpcf_types[$t]['_wpcf_access_capabilities']);
                }
            }
            $this->updateWpcfTypes($wpcf_types);
        }
        //taccess_log($access_types);
        */
        return $access_types;
    }
    
    public function updateAccessTypes($settings)
    {
        return update_option('wpcf-access-types', $settings);
    }
    
    public function getAccessTaxonomies()
    {
        $access_taxs = get_option('wpcf-access-taxonomies', array());
        
        /*
        // merge with Access settings saved in Types tables, since Access is standalone now
        $isTypesActive = Access_Helper::wpcf_access_is_wpcf_active();
        
        if ($isTypesActive)
        {
            $wpcf_taxs = $this->getWpcfTaxonomies();
            
            //taccess_log(array($access_taxs, $wpcf_taxs));
            
            foreach ($wpcf_taxs as $t=>$d)
            {
                if (isset($d['_wpcf_access_capabilities']))
                {
                    if (!isset($access_taxs[$t]))
                        $access_taxs[$t] = $d['_wpcf_access_capabilities'];
                    unset($wpcf_taxs[$t]['_wpcf_access_capabilities']);
                }
            }
            $this->updateWpcfTaxonomies($wpcf_taxs);
        }
        //taccess_log($access_taxs);
        */
        return $access_taxs;
    }
    
    public function updateAccessTaxonomies($settings)
    {
        return update_option('wpcf-access-taxonomies', $settings);
    }
    
    public function getAccessThirdParty()
    {
        return get_option('wpcf-access-3rd-party', array());
    }
    
    public function updateAccessThirdParty($settings)
    {
        return update_option('wpcf-access-3rd-party', $settings);
    }
    
    public function getWpcfTypes()
    {
        return get_option('wpcf-custom-types', array());
    }
    
    public function updateWpcfTypes($settings)
    {
        return update_option('wpcf-custom-types', $settings);
    }
    
    public function getWpcfTaxonomies()
    {
        return get_option('wpcf-custom-taxonomies', array());
    }
    
    public function updateWpcfTaxonomies($settings)
    {
        return update_option('wpcf-custom-taxonomies', $settings);
    }
    
    public function getWpcfActiveTypes()
    {
        $types=$this->getWpcfTypes();
        foreach ($types as $type => $data) 
        {
            if (!empty($data['disabled']))
                unset($types[$type]);
        }
        return $types;
    }
    
    public function getWpcfActiveTaxonomies()
    {
        $taxonomies=$this->getWpcfTaxonomies();
        foreach ($taxonomies as $taxonomy => $data) 
        {
            if (!empty($data['disabled']))
                unset($taxonomies[$taxonomy]);
        }
        return $taxonomies;
    }
    
    public function getPostTypes($args=false)
    {
        if (false===$args)
            $args=array('show_ui' => true);
            
        return get_post_types($args, 'objects');
    }
    
    public function getTaxonomies($args=false)
    {
        if (false===$args)
            $args=array('show_ui' => true);
            
        return get_taxonomies($args, 'objects');
    }
}