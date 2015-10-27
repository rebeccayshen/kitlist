<?php
/**
*
*   Factory class, instantiates the appropriate payment integratio plugin
*
**/
final class CRED_Commerce_Plugin_Factory
{
    public static function getPlugin($commerce_plugin)
    {
        $plugin=null;
        switch ($commerce_plugin)
        {
            case 'woocommerce':
                $plugin = CREDC_Loader::get('PLUGIN/Woocommerce');
                break;
        }
        return $plugin;
    }
}
