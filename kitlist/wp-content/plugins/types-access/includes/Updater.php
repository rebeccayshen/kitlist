<?php
/*
*   Access updater
*/

final class Access_Updater
{

    private static $db_ver=false;
    
    public static function maybeUpdate()
    {
        $model=TAccess_Loader::get('MODEL/Access');
        self::$db_ver=$model->getAccessVersion();
        
        if (!self::$db_ver)
            self::$db_ver=array();
        
        //taccess_log(array('updater', self::$db_ver, TACCESS_VERSION));
        
        if (!isset(self::$db_ver[TACCESS_VERSION]))
            self::update();
    }
    
    private static function update()
    {
        if (!isset(self::$db_ver['1.1.6']))
            // update to 1.1.6
            self::update_to_116();
        
        self::$db_ver = array_merge(self::$db_ver, array(TACCESS_VERSION => 1));
        TAccess_Loader::get('MODEL/Access')->updateAccessVersion(self::$db_ver);
    }
    
    // 1.1.6 uses its own DB options to save all settings and does not depend on Types options
    private static function update_to_116()
    {
        //taccess_log(array('update to 1.1.6', self::$db_ver, TACCESS_VERSION));
        
        $model = TAccess_Loader::get('MODEL/Access');
        
        // Post Types
        $access_types = $model->getAccessTypes();
        $wpcf_types = $model->getWpcfTypes();
        
        // merge with Access settings saved in Types tables, since Access is standalone now
        foreach ($wpcf_types as $t=>$d)
        {
            if (isset($d['_wpcf_access_capabilities']))
            {
                if (!isset($access_types[$t]))
                    $access_types[$t] = $d['_wpcf_access_capabilities'];
                unset($wpcf_types[$t]['_wpcf_access_capabilities']);
            }
        }
        $model->updateWpcfTypes($wpcf_types);
        $model->updateAccessTypes($access_types);
        unset($wpcf_types);
        unset($access_types);
        
        // Taxonomies
        $access_taxonomies = $model->getAccessTaxonomies();
        $wpcf_taxonomies = $model->getWpcfTaxonomies();
        
        // merge with Access settings saved in Types tables, since Access is standalone now
        foreach ($wpcf_taxonomies as $t=>$d)
        {
            if (isset($d['_wpcf_access_capabilities']))
            {
                if (!isset($access_taxonomies[$t]))
                    $access_taxonomies[$t] = $d['_wpcf_access_capabilities'];
                unset($wpcf_taxonomies[$t]['_wpcf_access_capabilities']);
            }
        }
        $model->updateWpcfTaxonomies($wpcf_taxonomies);
        $model->updateAccessTaxonomies($access_taxonomies);
        unset($wpcf_taxonomies);
        unset($access_taxonomies);
        
        self::$db_ver = array_merge(self::$db_ver, array('1.1.6' => 1));
    }
}