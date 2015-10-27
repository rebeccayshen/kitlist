<?php
if (!class_exists('CREDC_Loader', false))
{

/**
 *  CREDC_Loader
 * 
 *  This class is responsible for loading/including all files and getting instances of all objects
 *  in an efficient and abstract manner, abstracts all hardcoded paths and dependencies and manages singleton instances automatically
 *
 *  Also it renders and caches templates even from 3rd-party plugins (that want to use the templating engine, eg CRED Commerce)
 *
 */

// define interfaces used to implement design patterns
if (!interface_exists('CREDC_Singleton', false))
{
    /**
    * Singleton interface used as "tag" to mark singleton classes.
    */
    interface CREDC_Singleton
    {
    }
}

// it is unique
final class CREDC_Loader
{
    // pool of singleton instances, implement singleton factory, tag with singleton interface
    private static $__singleton_instances__ = array();
    
    // some dependencies here
    private static $__dependencies__=array();
    private static $__loaded_dependencies__=array();
    private static $__assets__=array();
    
    private static $metaboxes=array();
    
    // allow 3rd-party plugins to add their own dependencies here to be managed by loader (using filter hook)
    public static function init()
    {
        //$requested_script = (function_exists('realpath')) ? realpath($_SERVER['SCRIPT_NAME'])  : $_SERVER['SCRIPT_NAME'];
        $requested_script = (isset($_SERVER['SCRIPT_NAME'])) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : -1;
        $this_script = (isset($_SERVER['DOCUMENT_ROOT'])) ? str_replace( $_SERVER['DOCUMENT_ROOT'], '', str_replace('\\', '/', ''.__FILE__) ) : -2;
        
        if (
                $requested_script == $this_script && 
                ( isset($_GET['css']) || isset($_GET['js']) ) &&
                !defined('LOADING_ASSETS_' . __CLASS__)
        )
        {
            // call to load concatenated assets, css or js
            //if ( !defined('LOADING_ASSETS_' . __CLASS__) )
            define('LOADING_ASSETS_' . __CLASS__, 1);
            
            self::loadAssets();
        }
        elseif (!defined('LOADING_ASSETS_' . __CLASS__))
        {
            // called from WP
            self::_init_();
        }
    }
    
    public static function _init_()
    {
        // init assets
        self::initAssets();
            
        // init dependencies paths, if any
        self::initDeps();
    }
    
    public static function initAssets()
    {
        // init assets
    }
    
    public static function initDeps()
    {
        // init dependencies paths, if any
        if (empty(self::$__dependencies__))
        {
            self::$__dependencies__=array (
                'MODEL'=>array(
                    'Main' => array(
                        array(
                            'class' => 'CRED_COMMERCE_Main_Model',
                            'path' => CRED_COMMERCE_MODELS_PATH.'/Main_Model.php'
                        )
                    )
                ),
                'CLASS'=>array(
                    'CRED_Commerce' => array(
                        array(
                            'class' => 'CRED_Commerce',
                            'path' => CRED_COMMERCE_CLASSES_PATH.'/CRED_Commerce.php'
                        )
                    ),
                    'Form_Handler' => array(
                        array(
                            'class' => 'CRED_Commerce_Plugin_Factory',
                            'path' => CRED_COMMERCE_CLASSES_PATH.'/Plugin_Factory.php'
                        ),
                        array(
                            'class' => 'CRED_Commerce_Form_Handler',
                            'path' => CRED_COMMERCE_CLASSES_PATH.'/Form_Handler.php'
                        )
                    ),
                    'Factory' => array(
                        array(
                            'class' => 'CRED_Commerce_Plugin_Factory',
                            'path' => CRED_COMMERCE_CLASSES_PATH.'/Plugin_Factory.php'
                        )
                    )
                ),
                'PLUGIN'=>array(
                    '%%PARENT%%' => array(
                        array(
                            'class' => 'CRED_Commerce_Plugin_Interface',
                            'path' => CRED_COMMERCE_CLASSES_PATH.'/Plugin_Interface.php'
                        ),
                        array(
                            'class' => 'CRED_Commerce_Plugin_Base',
                            'path' => CRED_COMMERCE_CLASSES_PATH.'/Plugin_Base.php'
                        )
                    ),
                    'Woocommerce' => array(
                        array(
                            'class' => 'CRED_Woocommerce_Plugin',
                            'path' => CRED_COMMERCE_PLUGINS_PATH.'/woocommerce/CRED_Woocommerce.php'
                        )
                    )
                ),
                'VIEW'=>array(
                    'settings' => array(
                        array(
                            'path' => CRED_COMMERCE_VIEWS_PATH.'/settings.php'
                        )
                    )
                )
            );
        }
    }
    
    // check if a dependency is registered
    public static function has($type='dependency', $deps='')
    {
        if ('dependency'==$type)
        {
            if (isset(self::$__dependencies__[$type]))
                return true;
            return false;
        }
        elseif ('asset'==$type)
        {
            if (isset(self::$__assets__[$type]))
                return true;
            return false;
        }
        return false;
    }
    
    // add dependencies manually
    public static function add($type='dependencies', $deps=array())
    {
        if ('dependencies'==$type)
        {
            self::$__dependencies__=array_merge(self::$__dependencies__, $deps);
        }
        elseif ('assets'==$type)
        {
            self::$__assets__=array_merge(self::$__assets__, $deps);
        }
    }
    
    
    public static function loadLocale($id, $locale_file=false)
    {
        /*load_textdomain($id, CRED_COMMERCE_LOCALE_PATH . '/' . $locale_file);*/
        load_plugin_textdomain($id, false, CRED_COMMERCE_LOCALE_PATH);
    }
    
    private static function get_file($path) 
    {

        if ( function_exists('realpath') )
            $path = realpath($path);

        if ( ! $path || ! @is_file($path) )
            return '';

        return @file_get_contents($path);
    }
    
    private static function buildAbsPath($rel, $base)
    {
        // is relative path
        if (0===strpos($rel, '.'))
        {
            $parts = explode('/', str_replace('\\', '/', $rel));
            foreach (array_keys($parts) as $ii)
            {
                if ('.' == $parts[$ii])
                {
                    $parts[$ii] = $base;
                }
                elseif ('..' == $parts[$ii])
                {
                    $base=dirname($base);
                    $parts[$ii] = $base;
                }
            }
            $rel = implode('/', $parts);
        }
        return $rel;
    }
    
    // perform same functionality like WP => load-scripts.php, load-styles.php scripts
    public static function loadAssets()
    {
        /*
            one problem is how to indentify the assets without hardcoding the paths,
            since this is called without loading WP, it is called as standalone script [FIXED, see below]
            
            one solution is to include the main plugin file which has all the hardcoded definitions,
            a problem is how to make this generic, without introducing LFI, RFI security issues, [see NOTE below]
        */
        
        /**
         * Disable error reporting
         *
         * Set this to error_reporting( E_ALL ) or error_reporting( E_ALL | E_STRICT ) for debugging
         */
        //error_reporting( E_ALL );
        error_reporting(0);
        
        $load = false;
        $out = '';
        
        if (isset($_GET['js']))
        {
            $load = urldecode($_GET['js']);
            $load = preg_replace( '/[^a-z0-9,_-]+/i', '', $load );
            $load = explode(',', $load);
            
            if ($load && !empty($load))
            {
                include dirname(__FILE__).'/plugin.php'; // IMPORTANT: any way to avoid hardcoding this here ????
                
                // build the asset definitions
                self::initAssets();
                
                $compress = ( isset($_GET['c']) && $_GET['c'] );
                $force_gzip = ( $compress && 'gzip' == $_GET['c'] );
                $expires_offset = 31536000; // 1 year
                
                foreach( $load as $handle ) 
                {
                    if ( !isset(self::$__assets__['SCRIPT'][$handle]) || !isset(self::$__assets__['SCRIPT'][$handle]['src']))
                        continue;

                    $out .= '/* '. $handle .' */' . "\n" . self::get_file(self::$__assets__['SCRIPT'][$handle]['src']) . "\n\n\n";
                }

                header('Content-Type: application/x-javascript; charset=UTF-8');
                header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT');
                header("Cache-Control: public, max-age=$expires_offset");

                if ( 
                        $compress && 
                        ! ini_get('zlib.output_compression') && 
                        'ob_gzhandler' != ini_get('output_handler') 
                        && isset($_SERVER['HTTP_ACCEPT_ENCODING']) 
                ) 
                {
                    header('Vary: Accept-Encoding'); // Handle proxies
                    if ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') && function_exists('gzdeflate') && ! $force_gzip ) 
                    {
                        header('Content-Encoding: deflate');
                        $out = gzdeflate( $out, 3 );
                    } 
                    elseif ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && function_exists('gzencode') ) 
                    {
                        header('Content-Encoding: gzip');
                        $out = gzencode( $out, 3 );
                    }
                }

                echo $out;
            }
        }
        elseif (isset($_GET['css']))
        {
            $load = urldecode($_GET['css']);
            $load = preg_replace( '/[^a-z0-9,_-]+/i', '', $load );
            $load = explode(',', $load);
            
            if ($load && !empty($load))
            {
                include dirname(__FILE__).'/plugin.php'; // IMPORTANT: any way to avoid hardcoding this here ????
                
                // build the asset definitions
                self::initAssets();
                
                $compress = ( isset($_GET['c']) && $_GET['c'] );
                $force_gzip = ( $compress && 'gzip' == $_GET['c'] );
                $expires_offset = 31536000; // 1 year
                
                foreach( $load as $handle ) 
                {
                    if ( !isset(self::$__assets__['STYLE'][$handle]) || !isset(self::$__assets__['STYLE'][$handle]['src']))
                        continue;
                    
                    // handle (relative) urls in CSS
                    $content = self::get_file(self::$__assets__['STYLE'][$handle]['src']);
                    $css_path = dirname(self::$__assets__['STYLE'][$handle]['path']);
                    if (preg_match_all('#url\s*\(([^\)]+?)\)#', $content, $m))
                    {
                        $matches = $m[1];
                        unset($m);
                        foreach ($matches as $match)
                        {
                            $rel = trim( trim( trim( $match ), '"' ), "'" );
                            $abs = self::buildAbsPath($rel, $css_path);
                            $content = str_replace($rel, $abs, $content);
                        }
                    }
                    
                    $out .= '/* '. $handle .' */' . "\n" . $content . "\n\n\n";
                }

                header('Content-Type: text/css');
                header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT');
                header("Cache-Control: public, max-age=$expires_offset");

                if ( 
                        $compress && 
                        ! ini_get('zlib.output_compression') && 
                        'ob_gzhandler' != ini_get('output_handler') && 
                        isset($_SERVER['HTTP_ACCEPT_ENCODING']) 
                ) 
                {
                    header('Vary: Accept-Encoding'); // Handle proxies
                    if ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') && function_exists('gzdeflate') && ! $force_gzip ) 
                    {
                        header('Content-Encoding: deflate');
                        $out = gzdeflate( $out, 3 );
                    } 
                    elseif ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && function_exists('gzencode') ) 
                    {
                        header('Content-Encoding: gzip');
                        $out = gzencode( $out, 3 );
                    }
                }

                echo $out;
            }
        }
        
        exit;
    }
    
    private static function getAssetDeps($type, $class)
    {
        $assets=array();
        $deps=array();
        
        if ( isset(self::$__assets__[$type][$class]) && isset(self::$__assets__[$type][$class]['dependencies']) )
        {
            foreach ( self::$__assets__[$type][$class]['dependencies'] as $_dep )
            {
                if (isset(self::$__assets__[$type][$_dep]))
                {
                    if (!in_array($_dep, $assets))
                    {
                        $assets = array_merge($assets, array($_dep));
                        
                        list($assets2, $deps2) = self::getAssetDeps($type, $_dep);
                        
                        $assets = array_merge(array_diff($assets2, $assets), $assets);
                        $deps = array_merge(array_diff($deps2, $deps), $deps);
                    }
                }
                else
                {
                    if (!in_array($_dep, $deps))
                        $deps = array_merge($deps, array($_dep));
                }
            }
        }
        
        return array($assets, $deps);
    }
    
    // load an asset with dependencies
    public static function loadAsset($qclass, $registerAs=false, $enqueueIt=false, $concat=false, $nocache=false)
    {
        list($type, $class)=explode('/', $qclass, 2);
        
        if ( 
            isset(self::$__assets__[$type]) && 
            isset(self::$__assets__[$type][$class]) 
        )
        {
            $_type=&self::$__assets__[$type];
            $_class=&$_type[$class];
            
            if ($concat)
            {
                $this_url = plugins_url( basename(__FILE__) , __FILE__ ); //basename(__FILE__);
                list($assets, $deps) = self::getAssetDeps($type, $class);
                // add the required asset last, after all dependencies
                $assets[] = $class;
                //cred_log($assets);
            }
            else
            {
                if (is_array($_class['dependencies']) && !empty($_class['dependencies']))
                {
                    foreach ($_class['dependencies'] as $_dep)
                    {
                        if (isset(self::$__assets__[$type][$_dep]))
                        {
                            // recursively register dependencies
                            self::loadAsset("$type/$_dep", false, false, false);
                        }
                    }
                }
            }
            
            $registerAs=($registerAs)?$registerAs:$class;
            if ('SCRIPT'==$type && isset($_class['path']))
            {
                $isFooter=isset($_class['footer'])?$_class['footer']:false;
                
                if ($concat)
                {
                    $uri = $this_url.'?js='.urlencode(implode(',', $assets));
                    if ($nocache)
                        $uri .= '&_nocache='.time();
                    $depends = $deps;
                }
                else
                {
                    $uri = $_class['path'];
                    $depends = $_class['dependencies'];
                }
                
                wp_register_script($registerAs, $uri, $depends, $_class['version'], $isFooter);
                if ($enqueueIt)
                    wp_enqueue_script($registerAs);
            }
            elseif ('STYLE'==$type && isset($_class['path']))
            {
                if ($concat)
                {
                    $uri = $this_url.'?css='.urlencode(implode(',', $assets));
                    if ($nocache)
                        $uri .= '&_nocache='.time();
                    $depends = $deps;
                }
                else
                {
                    $uri = $_class['path'];
                    $depends = $_class['dependencies'];
                }
                
                wp_register_style($registerAs, $uri, $depends, $_class['version']);
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
            printf('File "%s" doesn\'t exist!', $path);
            //print_r(self::$__dependencies__);
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
                if ( $instance instanceof CREDC_Singleton /*$reflection->isSubclassOf( 'CREDC_Singleton' )*/ )
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
        $template_path = CRED_COMMERCE_TEMPLATES_PATH . DIRECTORY_SEPARATOR . $template . '.tpl.php';
        
        // NEW use caching of templates
        $output=false;
        if ($cache)
        {
            $group='_CRED_';
            $key=md5(serialize(array($template_path, $args)));
            $output=wp_cache_get( $key, $group );
        }
        
        if (false === $output)
        {
            if (!is_file($template_path))
            {
                printf('File "%s" doesn\'t exist!', $template_path);
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
CREDC_Loader::init();

}
