<?php
// make sure it is unique
if (!class_exists('TAccess_Loader', false))
{ 
/**
 *  TAccess_Loader
 * 
 *  This class is responsible for loading/including all files and getting instances of all objects
 *  in an efficient and abstract manner, abstracts all hardcoded paths and dependencies and manages singleton instances
 */
 
// define interfaces used to implement design patterns
if (!interface_exists('TAccess_Singleton', false))
{
    /**
    * Singleton interface used as "tag" to mark singleton classes.
    */
    interface TAccess_Singleton
    {
    }
}

final class TAccess_Loader
{
    // pool of singleton instances, implement singleton factory, tag with singleton interface
    private static $__singleton_instances__ = array();
    
    // some dependencies here
    private static $__dependencies__=array();
    private static $__loaded_dependencies__=array();
    private static $__assets__=array();
    
    public static function init()
    {
        self::_init_();
        // late init, to cope for any additional dependencies
        add_action('plugins_loaded', array(__CLASS__, '_init_'), 7);
    }
    
    public static function _init_()
    {
        // init assets
        if (empty(self::$__assets__))
        {
            self::$__assets__=array(
                'SCRIPT'=>array(
                    'wpcf-access-utils-dev'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array('jquery', 'wp-pointer'),
                        'path'=>TACCESS_ASSETS_URL.'/js/utils.js'
                    ),
                    'types-suggest-dev'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array('jquery'),
                        'path'=>TACCESS_ASSETS_URL.'/js/suggest.js'
                    ),
                    'wpcf-access-dev'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array('jquery', 'suggest', 'jquery-ui-dialog', 'jquery-ui-tabs', 'wp-pointer'),
                        'path'=>TACCESS_ASSETS_URL.'/js/basic.js',
						'localization_name'=>'wpcf_access_dialog_texts',
						'localization_data'=>array(
							'wpcf_change_perms' => __("Change Permissions", 'wpcf-access'),
							'wpcf_close' => __("Close", 'wpcf-access'),
                            'wpcf_cancel' => __("Cancel", 'wpcf-access'),
                            'wpcf_group_exists' => __("Group already exists", 'wpcf-access'),
                            'wpcf_assign_group' => __("Assign group", 'wpcf-access'),
                            'wpcf_set_errors' => __("Set errors", 'wpcf-access'),
                            'wpcf_error1' => __("Show 404 - page not found", 'wpcf-access'),
                            'wpcf_error2' => __("Show Content Template", 'wpcf-access'),
                            'wpcf_error3' => __("Show Page template", 'wpcf-access'),
                            'wpcf_info1' => __("Template", 'wpcf-access'),
                            'wpcf_info2' => __("PHP Template", 'wpcf-access'),
                            'wpcf_info3' => __("PHP Archive", 'wpcf-access'),
                            'wpcf_info4' => __("View Archive", 'wpcf-access'),
                            'wpcf_info5' => __("Display: 'No posts found'", 'wpcf-access'),
                            'wpcf_access_group' => __("Access group", 'wpcf-access'),
                            'wpcf_custom_access_group' => __("Custom Access Group", 'wpcf-access'),
                            'wpcf_add_group' => __("Add Group", 'wpcf-access'),
                            'wpcf_modify_group' => __("Modify Group", 'wpcf-access'),
                            'wpcf_remove_group' => __("Remove Group", 'wpcf-access'),
                            'wpcf_role_permissions' => __("Role permissions", 'wpcf-access'),                            
                            'wpcf_delete_role' => __("Delete role", 'wpcf-access'),
                            'wpcf_save' => __("Save", 'wpcf-access'),
                            
						)
                    ),
                    'toolset-colorbox'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array('jquery'),
                        'path'=>TACCESS_ASSETS_URL.'/common/res/js/jquery.colorbox-min.js'
                    ),
                    'views-utils-script'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array( 'jquery', 'underscore', 'backbone'),
                        'path'=>TACCESS_ASSETS_URL.'/common/utility/js/utils.js',
						'localization_name'=>'wpv_help_box_texts',
						'localization_data'=>array(
							'wpv_dont_show_it_again' => __("Got it! Don't show this message again", 'wpcf-access'),
							'wpv_close' => __("Close", 'wpcf-access')
						)
                    )
                ),
                'STYLE'=>array(
                    'font-awesome'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/font-awesome.min.css'
                    ),
                    'types-debug'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/pre.css'
                    ),
                    'types-suggest-dev'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/suggest.css'
                    ),
                    'wpcf-access-dev'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array('font-awesome', 'wp-pointer', 'wp-jquery-ui-dialog' ),
                        'path'=>TACCESS_ASSETS_URL.'/css/basic.css'
                    ),
                    'toolset-colorbox'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/colorbox.css'
                    ),
                    'notifications'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/notifications.css'
                    ),
                    'wpcf-access-dialogs-css'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/dialogs.css'
                    )
                )
            );
        }
        
        // init dependencies paths, if any
        if (empty(self::$__dependencies__))
        {
            self::$__dependencies__=array(
                'MODEL'=>array(
                    'Access' => array(
                        array(
                            'class' => 'Access_Model',
                            'path' => TACCESS_INCLUDES_PATH.'/Model.php'
                        )
                    ),
                ),
                'CLASS'=>array(
                    'XML_Processor' => array(
                        array(
                            'class' => 'Access_XML_Processor',
                            'path' => TACCESS_INCLUDES_PATH.'/XML_Processor.php'
                        )
                    ),
                    'Updater' => array(
                        array(
                            'class' => 'Access_Updater',
                            'path' => TACCESS_INCLUDES_PATH.'/Updater.php'
                        )
                    ),
                    'Helper' => array(
                        array(
                            'class' => 'Access_Helper',
                            'path' => TACCESS_INCLUDES_PATH.'/Helper.php'
                        )
                    ),
                    /*'Plus' => array(
                        array(
                            'path' => TACCESS_PLUGIN_PATH.'/plus.php'
                        )
                    ),
                    'Embedded' => array(
                        array(
                            'path' => TACCESS_PLUGIN_PATH.'/embedded.php'
                        )
                    ),*/
                    'Admin' => array(
                        array(
                            'class' => 'Access_Admin',
                            'path' => TACCESS_INCLUDES_PATH.'/Admin.php'
                        )
                    ),
                    'Admin_Edit' => array(
                        array(
                            'class' => 'Access_Admin_Edit',
                            'path' => TACCESS_INCLUDES_PATH.'/Admin_Edit.php'
                        )
                    ),
                    'Ajax' => array(
                        array(
                            'class' => 'Access_Ajax_Helper',
                            'path' => TACCESS_INCLUDES_PATH.'/Ajax_Helper.php'
                        )
                    ),
                    'Upload' => array(
                        array(
                            'class' => 'Access_Upload_Helper',
                            'path' => TACCESS_INCLUDES_PATH.'/Upload_Helper.php'
                        )
                    ),
                    'Debug' => array(
                        array(
                            'class' => 'Access_Debug',
                            'path' => TACCESS_INCLUDES_PATH.'/Debug.php'
                        )
                    ),
                    'Post' => array(
                        array(
                            'class' => 'Access_Post_Helper',
                            'path' => TACCESS_INCLUDES_PATH.'/Post_Helper.php'
                        )
                    )
                )
            );
        }        
        
    }
    
    public static function loadLocale($id, $locale_file)
    {
        load_textdomain($id, TACCESS_LOCALE_PATH . '/' . $locale_file);
    }
    
    // load an asset with dependencies
    public static function loadAsset($qclass, $registerAs=false, $enqueueIt=true)
    {
        list($type, $class)=explode('/', $qclass, 2);
        
        if ( 
            isset(self::$__assets__[$type]) && 
            isset(self::$__assets__[$type][$class]) 
        )
        {
            $_type=&self::$__assets__[$type];
            $_class=&$_type[$class];
            if (is_array($_class['dependencies']) && !empty($_class['dependencies']))
            {
                foreach ($_class['dependencies'] as $_dep)
                {
                    if (isset(self::$__assets__[$type][$_dep]))
                    {
                        // recursively register dependencies
                        self::loadAsset("$type/$_dep", false, false);
                    }
                }
            }
            $registerAs=($registerAs)?$registerAs:$class;
			
			if ('SCRIPT'==$type && isset($_class['path']))
            {
                $isFooter=isset($_class['footer'])?$_class['footer']:false;
                if ( !wp_script_is($registerAs, 'registered') ){
                	wp_register_script($registerAs, $_class['path'], $_class['dependencies'], $_class['version'], $isFooter);
				}
                if ($enqueueIt) {
                    wp_enqueue_script($registerAs);
					if ( isset( $_class['localization_name'] ) && isset( $_class['localization_data'] ) ) {
						wp_localize_script( $registerAs, $_class['localization_name'], $_class['localization_data'] );
					}
				}
            }
            elseif ('STYLE'==$type && isset($_class['path']))
            {
                if ( isset($_GET['page']) && $_GET['page'] == 'types_access' && $registerAs == 'font-awesome' ){
                    wp_deregister_style($registerAs);
                }
                wp_register_style($registerAs, $_class['path'], $_class['dependencies'], $_class['version']);
                if ($enqueueIt)
                    wp_enqueue_style($registerAs);
            }
        }
    }
    
    // include a php file
    private static function getFile($path, $_in='require_once')
    {
        if( !is_file($path) )
        {
            printf(__('File "%s" doesn\'t exist!', 'wpcf-access'), $path);
            return false;
        }
        
        switch ($_in)
        {
            case 'include':
                include $path;
                break;
            
            case 'include_once':
                include_once $path;
                break;
                
            case 'require':
                require $path;
                break;
            
            case 'require_once':
                require_once $path;
                break;
        }
        
        return true;
    }

    // import a php class
    private static function getClass($class, $path, $_in='require_once')
    {
        if ( !class_exists( $class, false ) )
            self::getFile( $path, $_in );    
    }
    
    public static function loadScoped($qclass, $__glo__=true, $_in='require_once')
    {
        list($type, $class)=explode('/', $qclass, 2);
        
        if ( 
            isset(self::$__dependencies__[$type]) && 
            isset(self::$__dependencies__[$type][$class]) 
        )
        {
            
            if ($__glo__)
            {
                // auto-register globals in scope
                foreach (array_diff(array_keys($GLOBALS), array('GLOBALS', '_POST', '_GET', '_COOKIES', '_SERVER', '_FILES') ) as $__v)
                    global $$__v;
            }
            
            $_type=&self::$__dependencies__[$type];
            $_class=&$_type[$class];
            if ( isset($_type['%%PARENT%%']) && is_array($_type['%%PARENT%%']) )
            {
                $_parent=&$_type['%%PARENT%%'];
                foreach ($_parent as $_dep)
                {
                    if (isset($_dep['class']))
                        self::getClass($_dep['class'], $_dep['path']);
                    else
                        self::getFile($_dep['path']);
                }
            }
            foreach ($_class as $_dep)
            {
                if (isset($_dep['class']))
                    self::getClass($_dep['class'], $_dep['path']);
                else
                {
                    switch ($_in)
                    {
                        case 'include':
                            include $_dep['path'];
                            break;
                        
                        case 'include_once':
                            include_once $_dep['path'];
                            break;
                            
                        case 'require':
                            require $_dep['path'];
                            break;
                        
                        case 'require_once':
                            require_once $_dep['path'];
                            break;
                    }
                }
            }
        }
    }
    
    // load a class with dependencies if needed
    public static function load($qclass)
    {
        list($type, $class)=explode('/', $qclass, 2);
        
        // try to optimize a little bit
        if ( isset(self::$__loaded_dependencies__[$qclass]) )
        {
            $is_loaded=true;
        }
        else 
        {
            $is_loaded=false;
            self::$__loaded_dependencies__[$qclass]=1;
        }
            
        if ( 
            isset(self::$__dependencies__[$type]) && 
            isset(self::$__dependencies__[$type][$class]) 
        )
        {
            $_type=&self::$__dependencies__[$type];
            $_class=&$_type[$class];
            if ( !$is_loaded )
            {
                if ( isset($_type['%%PARENT%%']) && is_array($_type['%%PARENT%%']) )
                {
                    $_parent=&$_type['%%PARENT%%'];
                    foreach ($_parent as $_dep)
                    {
                        if (isset($_dep['class']))
                            self::getClass($_dep['class'], $_dep['path']);
                        else
                            self::getFile($_dep['path']);
                    }
                }
                foreach ($_class as $_dep)
                {
                    if (isset($_dep['class']))
                        self::getClass($_dep['class'], $_dep['path']);
                    else
                        self::getFile($_dep['path']);
                }
            }
            $class=end($_class);
            if (isset($class['class']))
                $class=$class['class'];
        }
        elseif ( !$is_loaded )
        {
            self::getFile($qclass);
            $class=$qclass;
        }
        return array(false, $class);
   }
    
    // singleton factory pattern, to enable singleton in php 5.2, etc..
    // http://stackoverflow.com/questions/7902586/extend-a-singleton-with-php-5-2-x
    // http://stackoverflow.com/questions/7987313/how-to-subclass-a-singleton-in-php
    // use tags (interfaces) to denote singletons, and then use "singleton factory"
    // http://phpgoodness.wordpress.com/2010/07/21/singleton-and-multiton-with-a-different-approach/
    // ability to pass parameters, and to avoid the singleton flag
    public static function get($qclass)
    {
        // if instance is in pool, return it immediately, only singletons are in the pool
        if ( isset(self::$__singleton_instances__[$qclass]) )
            return self::$__singleton_instances__[$qclass];
        
        $instance = null;
        
        // load it if needed
        list($type, $class) = self::load($qclass);
        // make sure it is loaded (exists) and is not interface
        // make sure it is not an interface (PHP 5)
        //if ( !$reflection->isInterface() )
        if ( class_exists( $class, false ) && !interface_exists( $class, false ))
        {
            // Parameters to call constructor (in case) and multiton's getFace (in case) with.
            $args = func_get_args();
            array_shift( $args );

            if ( empty($args) )  // (PHP 5)
            {
                // If the object doesn't have arguments to be passed, we just instantiate it.
                // It might be quicker to use - new $class_name -.
                $instance = new $class(); //$reflection->newInstance(); // PHP 5
            }
            else
            {
                // delay using reflection unless absolutely needed (eg there are args to be passed)
                // Here's the point where we have to instantiate the class anyway. (supports PHP 5)
                $reflection = new ReflectionClass( $class );
                if ( null !== $reflection->getConstructor() )   // (PHP 5)
                    // If the object does have constructor, we pass the additional parameters we got in this method.
                    $instance = $reflection->newInstanceArgs( $args ); // PHP > 5.1.3
                else
                    // If the object doesn't have constructor, we just instantiate it.
                    // It might be quicker to use - new $class_name -.
                    $instance = new $class(); //$reflection->newInstance(); // PHP 5
            }

            if ($instance)
            {
                // If it's a singleton, we have to keep track of it. (PHP 5)
                // If might be quicker to use - $new_instance instanceof Singleton -.
                if ( $instance instanceof TAccess_Singleton /*$reflection->isSubclassOf( 'TAccess_Singleton' )*/ )
                {
                    self::$__singleton_instances__[$qclass] = $instance;
                }
                return $instance;
            }
        }
        // sth failed, no instance, return null here finally
        return null;
    }
    
    // USE WP Object_Cache API to cache templates (so 3rd-party cache plugins can be used also)
    public static function tpl($template, array $args=array(), $cache=false)
    {
        $template_path = TACCESS_TEMPLATES_PATH . DIRECTORY_SEPARATOR . $template . '.tpl.php';
        
        // NEW use caching of templates
        $output=false;
        if ($cache)
        {
            $group='_TAccess_';
            $key=md5(serialize(array($template_path, $args)));
            $output=wp_cache_get( $key, $group );
        }
        
        if (false === $output)
        {
            if (!is_file($template_path))
            {
                printf(__('File "%s" doesn\'t exist!', 'wpcf-access'), $template_path);
                return '';
            }
            $output = self::getTpl($template_path, $args);
            if ($cache) wp_cache_set( $key, $output, $group/*, $expire*/ );
        }
        return $output;
    }
    
    private static function getTpl($______templatepath________, array $______args______=array())
    {
        ob_start();
            if (!empty($______args______)) extract($______args______);
            include($______templatepath________);
        return ob_get_clean();
    }
	
}

// init on load
TAccess_Loader::init();

}