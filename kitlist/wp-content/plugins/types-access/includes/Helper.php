<?php

/*
*   Access Helper
*
*/
final class Access_Helper
{
    static $roles;
    public static function init()
    {
        /*
         * Plus functions.
         */
         
        add_action('plugins_loaded', array(__CLASS__, 'wpcf_access_plugins_loaded'), 11);
		if ( is_admin() ){
			
            if ( !function_exists('wp_get_current_user') ){
                require_once( ABSPATH. 'wp-includes/pluggable.php');
            }
            
            add_action( 'admin_enqueue_scripts', array( __CLASS__,'wpcf_access_select_group_metabox_files' ) );
            add_action( 'admin_head', array(__CLASS__,'wpcf_access_select_group_metabox') );
            
			
            add_action('admin_init', array(__CLASS__,'wpcf_access_check_add_media_permissions') );
            add_filter('icl_get_extra_debug_info', array( __CLASS__, 'add_access_extra_debug_information' ) );
		}
		else{
			add_filter( 'pre_get_posts', array( __CLASS__, 'wpcf_access_show_post_preview' ) );
            add_filter( 'request', array( __CLASS__, 'wpcf_access_set_feed_permissions' ) );	
		}
		
		add_shortcode( 'toolset_access', array(__CLASS__,'wpcf_access_create_shortcode_toolset_access') );
		add_filter('wpv_custom_inner_shortcodes', array(__CLASS__,'wpv_access_string_in_custom_inner_shortcodes'));


		
		//register_deactivation_hook(__FILE__, 'wpcf_access_deactivation');
    }
    
    //Add toolset_access shortcode to Views:Third-party shortcode arguments
    public static function wpv_access_string_in_custom_inner_shortcodes($custom_inner_shortcodes) {
        $custom_inner_shortcodes[] = 'toolset_access';
        
        return $custom_inner_shortcodes;
    }
	/*
	 * Check if user have media permission 
	*/
	public static function wpcf_access_check_if_user_can_do_media( $post_type = 'attachment', $action = 'read' ){
		global $current_user;	
		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();
		
		if ( !isset($settings_access[$post_type]) ){
			return true;	
		}
		if ( $settings_access[$post_type]['mode'] == 'not_managed' ){
			return true;	
		}
		
		$role = self::wpcf_get_current_logged_user_role();
		if ( $role == 'administrator' ){
			return true;
		}
		if ( $role != 'guest'){
			$user_level = self::wpcf_get_current_logged_user_level( $current_user );
		}
		else{
			$user_level = 0;
		}
		if ( $user_level == 10){
			return true;
		}
		$level = str_replace('level_','',self::wpcf_access_role_to_level($settings_access[$post_type]['permissions'][$action]['role']));
		if ( $user_level >= $level ){
			return true;	
		}else{
			return false;	
		}
		
	}
	/*
	 * Disable media upload
	 */
	public static function wpcf_access_check_add_media_permissions(  ){
		global $current_user;
		$role = self::wpcf_get_current_logged_user_role();
		if ( $role == 'administrator' ){
			return true;
		}
		if ( $role != 'guest'){
			$user_level = self::wpcf_get_current_logged_user_level( $current_user );
		}
		else{
			$user_level = 0;
		}
		if ( $user_level == 10){
			return true;
		}
		
		$user_can_edit_own = self::wpcf_access_check_if_user_can_do_media( 'attachment', 'edit_own' );
		$user_can_edit_any = self::wpcf_access_check_if_user_can_do_media( 'attachment', 'edit_any' );
		$user_can_read = self::wpcf_access_check_if_user_can_do_media( 'attachment', 'read' );
		if ( !$user_can_edit_own ){
			remove_submenu_page( 'upload.php', 'media-new.php' );
			add_action('wp_handle_upload_prefilter',array(__CLASS__,'wpcf_access_disable_media_upload'),1);	
		}
		if ( !$user_can_read ){
            global $menu;
            if ( isset($menu) && is_array($menu) ){
                remove_menu_page( 'upload.php' );
            }
			remove_action( 'media_buttons', 'media_buttons' );
		}
		
		if ( $_SERVER['SCRIPT_NAME'] == '/wp-admin/upload.php' ) {
			if ( !$user_can_read ){
				wp_redirect( get_admin_url() ); exit;
			}
			//if ( !$user_can_edit_any ){
				//add_filter('pre_get_posts', array(__CLASS__,'wpcf_access_show_only_user_media'));
			//}
        }
		if ( $_SERVER['SCRIPT_NAME'] == '/wp-admin/media-new.php' ) {
			if ( !$user_can_edit_own ){
				wp_redirect( get_admin_url() . 'upload.php' ); exit;
			}
        }
	}
	public static function wpcf_access_show_only_user_media( $query ){
		if ( $query->query['post_type'] === 'attachment' ){
			global $user_ID; 
	        $query->set('author',  $user_ID);
		}
        return $query;
	}
	
	public static function wpcf_access_disable_media_upload( $file ){
		$file['error'] = __('You have no access to upload files', 'wpcf-access');
	  	return $file;
	}
	
	/*
	 * Show public preview
	 */
	public static function wpcf_access_show_post_preview($query ){
		if ($query->is_main_query() && $query->is_preview() && $query->is_singular() ){
			add_filter( 'posts_results', array( __CLASS__, 'wpcf_access_check_if_user_can_preview_post' ), 10, 2 );	
		}
		return $query;
	}
    
    /*
	 * Add group permissions to feeds
	 */
	public static function wpcf_access_set_feed_permissions( $query ){
		if ( isset($query['feed']) ){
           
            global $current_user, $user_level;
            $role = self::wpcf_get_current_logged_user_role();           
		
            if ($role == ''){
                $role = 'guest';
                $user_level = 0;	
            } 
            
            if ( $role == 'administrator' ){
                return $query;
            }
            if ( $role != 'guest'){
                $user_level = self::wpcf_get_current_logged_user_level( $current_user );
            }		
            $role = self::wpcf_convert_user_role( $role, $user_level );	
		
            $model = TAccess_Loader::get('MODEL/Access');
            $settings_access = $model->getAccessTypes();
            $exclude_ids = array();
            foreach ( $settings_access as $group_slug => $group_data) {
                if ( strpos($group_slug, 'wpcf-custom-group-') === 0 ) {
                    if ( isset($settings_access[$group_slug]['permissions']['read']['users']) && in_array($current_user->data->ID,$settings_access[$post_type]['permissions']['read']['users']) ){
                        continue;
                    }
                    $user_can = self::wpcf_access_check_if_user_can($settings_access[$group_slug]['permissions']['read']['role'], $user_level);
                    if ( !$user_can ){                        
                        $exclude_posts = get_posts( array( 'meta_key' => '_wpcf_access_group', 'meta_value'=>$group_slug, 'post_type' => get_post_types() ) );
                        $temp_posts = wp_list_pluck($exclude_posts,'ID');
                        $exclude_ids = array_merge($exclude_ids,$temp_posts);
                    }
                }
            }
            $query['post__not_in'] = $exclude_ids ;
        }
        return $query;
	}
    
	public static function wpcf_access_check_if_user_can_preview_post( $posts ){
		global $current_user, $user_level;	
		remove_filter( 'posts_results', array( __CLASS__, 'wpcf_access_check_if_user_can_preview_post' ), 10, 2 );
		
		if ( empty( $posts ) ){
			return;
		}
		
		$post_id = $posts[0]->ID;
		
		
		
		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();
		$post_type = get_post_type($post_id);
		
		if ( $post_type == 'publish' ){
			wp_redirect( get_permalink( $post_id ), 301 );
			exit;	
		}
		
		$role = self::wpcf_get_current_logged_user_role();
		if ( $role == 'administrator' ){
			return $posts;
		}
		if ( $role != 'guest'){
			$user_level = self::wpcf_get_current_logged_user_level( $current_user );
		}
		if ( $user_level == 10){
			return $posts;
		}
		
		if ( isset( $settings_access[$post_type] ) && $settings_access[$post_type]['mode'] == 'permissions' ){
			if ( isset($settings_access[$post_type]['permissions']['read_private']['role']) ){
				if ( $settings_access[$post_type]['permissions']['read_private']['role'] == 'guest' ){
					$posts[0]->post_status = 'publish';	
				}elseif ( $settings_access[$post_type]['permissions']['read_private']['role'] != 'guest' && $role != 'guest' ){
					$level = str_replace('level_','',self::wpcf_access_role_to_level($settings_access[$post_type]['permissions']['read_private']['role']));
					if ( $user_level >= $level ){
						$posts[0]->post_status = 'publish';		
					}	
				}
				
		
			}
		
		}
		
		
		
		return $posts;
	}
	
	
	/*
	 * Access shortcode: toolset_access
	 * 
	 * Description: Set access to part of content in posts/pages/content templates/views
	 * 
	 * Parameters:
	 * 'role' => List of roles separated by comma
	 * 'operator' => 'allow|deny' 
	 * allow - show content for only listed roles
	 * deny - deny content for listed roles, all other roles will see this content
	 * 'raw' => "false|true", default false
	 * 
	 * Note: Roles can be uppercase/lowercase
	 * Note: Shortcodes can be used inside toolset_access
	 * 
	 * Example: [toolset_access role="Administrator,guest" operator="allow"]Content here[/toolset_access]
	 * 
	*/
	public static function wpcf_access_create_shortcode_toolset_access( $atts, $content ){
		 extract( shortcode_atts( array(
			      'role' => '',
			      'operator' => 'allow',
			      'raw' => 'false'
		     ), $atts ) );
		 
		 if ( empty($content) ){
		 	return;	
		 }
		 
		 if ( empty($role) ){
		 	return;	
		 }
         
		global $wp_roles;
		$received_roles = explode(',', $role );
        $received_roles_normal = explode(',', strtolower($role) );
		$roles = $wp_roles->roles;
		$recived_roles_fixed = array();
		foreach ($roles as $levels => $roles_data) 
        {
        	if ( in_array($roles_data['name'], $received_roles) || in_array($roles_data['name'], $received_roles_normal) ){
        		$recived_roles_fixed[] = $levels;	
			}
			if ( in_array($levels, $received_roles) ){
        		$recived_roles_fixed[] = $levels;	
			}
		}
		if ( in_array('Guest', $received_roles) || in_array('guest', $received_roles_normal) ){
        		$recived_roles_fixed[] = 'guest';	
		}
		$current_role = self::wpcf_get_current_logged_user_role();
		
		if ( in_array($current_role, $recived_roles_fixed) ){
			if ( $operator == 'allow' ){
				return self::wpcf_access_do_shortcode_content( $content, $raw );	
		 	}		
		}else{
			if ( $operator == 'deny' ){
				return self::wpcf_access_do_shortcode_content( $content, $raw );	
		 	}	
		}
		
	}
	
	/*
	 * Add A-Icon to edit post editor
	 * 
	*/
	public static function wpcf_access_add_editor_icon( $editor_class ){
		global $post, $wp_version, $wp_roles;
        
        if (!isset($post) || empty($post) || empty($editor_class)  ){
			return '';
		}
		
		$out = '<span class="button wpv-shortcode-post-icon js-wpcf-access-editor-button"><i class="icon-access-logo ont-icon-18"></i>' . __( 'Access', 'wpcf-access' ) . '</span>';
		
		$out .= '<div class="editor_addon_dropdown js-wpcf-access-editor-popup" id="editor_addon_dropdown_access_' . rand() . '">
                <h3 class="title">' . __('Insert conditionaly-displayed text', 'wpcf-access') . '</h3>
                <div class="close">&nbsp;</div>
                <div class="editor_addon_dropdown_content">';
        
		$roles = $wp_roles->roles;
		$out .= '<h3>'.__('Select roles: ', 'wpcf-access').'</h3>';
		$out .= '<p class="wpcf-access-margin">';
		foreach ($roles as $levels => $roles_data) 
        {
        	$out .= '<label>
        		<input type="checkbox" class="js-wpcf-access-list-roles" value="'.$roles_data['name'].'" /> '.$roles_data['name'] . '</label><br>';	
		}
		$out .= '<label>
        		<input type="checkbox" class="js-wpcf-access-list-roles" value="Guest" /> '.__('Guest', 'wpcf-access').'</label><br>';	
		$out .= '</p>';
		
		$out .= '<h3>'.__('Enter the text for conditional display: ', 'wpcf-access').'</h3>';
		$out .= '<p class="wpcf-access-margin">
			<input class="js-wpcf-access-conditional-message" /><br>
			<small>'. __('You will be able to add other fields and apply formatting after inserting this text', 'wpcf-access') . '</small>
		</p>';//<textarea class="js-wpcf-access-conditional-message"></textarea>
		$out .= '<h3>'.__('Will these roles see the text? ', 'wpcf-access').'</h3>';
		$out .= '<p class="wpcf-access-margin">
			<label>
        		<input type="radio" class="js-wpcf-access-shortcode-operator" name="wpcf-access-shortcode-operator" value="allow" /> '. __('Only users belonging to these roles will see the text', 'wpcf-access') . '</label><br>
        	<label>
        		<input type="radio" class="js-wpcf-access-shortcode-operator" name="wpcf-access-shortcode-operator" value="deny" /> '. __('Everyone except these roles will see the text', 'wpcf-access') . '</label><br>
		</p>';
        $out .= '        
        </div>
        <div class="otg-access-dialog-footer">
				<button class="button js-dialog-close">'. __('Cancel', 'wpcf-access').'</button>
				<button class="button button-primary js-wpcf-access-add-shortcode" disabled="disabled" data-editor="'. $editor_class .'">'. __('Insert conditional text', 'wpcf-access') .'</button>
		</div>
        </div>';
		
		 if (version_compare($wp_version, '3.1.4', '>')){
         	echo $out;
         }
         else{
         	return $context . $out;
		 }  	  	
	}
	
	/*
	 * Add filters to shortcode content 
	 * 
	*/
	public static function wpcf_access_do_shortcode_content( $content, $raw ) 
    {
    	if ( function_exists( 'WPV_wpcf_record_post_relationship_belongs' ) ) {
			$content = WPV_wpcf_record_post_relationship_belongs( $content );
		}		
    	
		
		if ( class_exists( 'WPV_template' ) ) {
			global $WPV_templates;
			$content = $WPV_templates->the_content($content);
		}
		
		if ( isset( $GLOBALS['wp_embed'] ) ) {
			global $wp_embed;
			$content = $wp_embed->run_shortcode($content);
			$content = $wp_embed->autoembed($content);
		}	
    		
    	if ( function_exists( 'wpv_resolve_internal_shortcodes' ) ) {
			$content = wpv_resolve_internal_shortcodes($content);
		}
		if ( function_exists( 'wpv_resolve_wpv_if_shortcodes' ) ) {
			$content = wpv_resolve_wpv_if_shortcodes($content);
		}
		

		$content = convert_smilies($content);
		//Enable wpautop if raw = false
		if ( $raw == 'false' ){
			$content = wpautop($content);
		}	
		
		$content = shortcode_unautop($content);
		$content = prepend_attachment($content);
		

		$content = do_shortcode($content);
		$content = capital_P_dangit($content);
    	return $content;	
	}
    	
	// TODO add support for the Content Templates here, wee need those scripts and styles to add the button to insert the shortcode
	public static function wpcf_access_select_group_metabox_files( ) 
    {
		
		global $post;
		
		if ( isset($post) && is_object($post) && $post->ID != '' ){
			
			$post_object = get_post_type_object($post->post_type);	
			if (($post_object->publicly_queryable || $post_object->public) && $post_object->name != 'attachment'  ) {
				
			if ( !wp_script_is('toolset-colorbox', 'registered')  ){
				wp_register_script('toolset-colorbox', TACCESS_ASSETS_URL.'/common/res/js/jquery.colorbox-min.js', array('jquery'), WPCF_ACCESS_VERSION, false);
			}
			if ( !wp_script_is('toolset-colorbox', 'enqueued')  ){
				 wp_enqueue_script('toolset-colorbox');
			}
			if ( !wp_script_is('views-utils-script', 'registered')  ){
				wp_register_script('views-utils-script', TACCESS_ASSETS_URL.'/common/utility/js/utils.js', array( 'jquery', 'underscore', 'backbone'), WPCF_ACCESS_VERSION, false);
			}
			if ( !wp_script_is('views-utils-script', 'enqueued')  ){
				 wp_enqueue_script('views-utils-script');
				 $help_box_translations = array(
					'wpv_dont_show_it_again' => __("Got it! Don't show this message again", 'wpcf-access'),
					'wpv_close' => __("Close", 'wpcf-access')
                    );
				 wp_localize_script( 'views-utils-script', 'wpv_help_box_texts', $help_box_translations );
			}
			if ( !wp_script_is('wpcf-access-dev', 'registered')  ){
				wp_register_script('wpcf-access-dev', TACCESS_ASSETS_URL.'/js/basic.js', array('jquery', 'suggest', 'jquery-ui-dialog', 'jquery-ui-tabs', 'wp-pointer'), WPCF_ACCESS_VERSION, false);
			}
			if ( !wp_script_is('wpcf-access-dev', 'enqueued')  ){
				 wp_enqueue_script('wpcf-access-dev');
                 $help_box_translations = array(
					'wpcf_change_perms' => __("Change Permissions", 'wpcf-access'),
							'wpcf_close' => __("Close", 'wpcf-access'),
                            'wpcf_cancel' => __("Cancel", 'wpcf-access'),
                            'wpcf_group_exists' => __("Group title already exists", 'wpcf-access'),
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
                    );
				 wp_localize_script( 'wpcf-access-dev', 'wpcf_access_dialog_texts', $help_box_translations );
			}
			if ( !wp_script_is('wpcf-access-dev', 'enqueued')  ){
				 wp_enqueue_script('wpcf-access-dev');
			}

			if ( !wp_script_is('icl_editor-script', 'registered')  ){
				wp_register_script('icl_editor-script', TACCESS_ASSETS_URL.'/common/visual-editor/res/js/icl_editor_addon_plugin.js', array(), WPCF_ACCESS_VERSION, false);
			}
			if ( !wp_script_is('icl_editor-script', 'enqueued')  ){
				 wp_enqueue_script('icl_editor-script');
			}


			if ( !wp_style_is('editor_addon_menu', 'registered')  ){
				wp_register_style('editor_addon_menu', TACCESS_ASSETS_URL.'/common/visual-editor/res/css/pro_dropdown_2.css', '', WPCF_ACCESS_VERSION);
			}
			if ( !wp_style_is('editor_addon_menu', 'enqueued')  ){
				 wp_enqueue_style('editor_addon_menu');
			}
			if ( !wp_style_is('editor_addon_menu_scroll', 'registered')  ){
				wp_register_style('editor_addon_menu_scroll', TACCESS_ASSETS_URL.'/common/visual-editor/res/css/scroll.css', '', WPCF_ACCESS_VERSION);
			}
			if ( !wp_style_is('editor_addon_menu_scroll', 'enqueued')  ){
				 wp_enqueue_style('editor_addon_menu_scroll');
			}           
           
            if ( !wp_style_is('toolset-colorbox', 'registered')  ){
				wp_register_style('toolset-colorbox', TACCESS_ASSETS_URL.'/common/res/css/colorbox.css', '', WPCF_ACCESS_VERSION);
			}
			if ( !wp_style_is('toolset-colorbox', 'enqueued')  ){
				 wp_enqueue_style('toolset-colorbox');
			}
			
			if ( !wp_style_is('notifications', 'registered')  ){
				wp_register_style('notifications', TACCESS_ASSETS_URL.'/common/utility/css/notifications.css', '', WPCF_ACCESS_VERSION);
			}
			if ( !wp_style_is('wpcf-access-dev', 'registered')  ){
				wp_register_style('wpcf-access-dev', TACCESS_ASSETS_URL.'/css/basic.css', array( 'wp-jquery-ui-dialog' ), WPCF_ACCESS_VERSION);
			}
			if ( !wp_style_is('notifications', 'enqueued')  ){
				 wp_enqueue_style('notifications');
			}
			
			if ( !wp_style_is('wpcf-access-dialogs-css', 'registered')  ){
				wp_register_style('wpcf-access-dialogs-css', TACCESS_ASSETS_URL.'/css/dialogs.css', array( 'wp-jquery-ui-dialog' ), WPCF_ACCESS_VERSION);
			}
			if ( !wp_style_is('wpcf-access-dialogs-css', 'enqueued')  ){
				 wp_enqueue_style('wpcf-access-dialogs-css');
			}
			if ( !wp_style_is('wpcf-access-dev', 'enqueued')  ){
				 wp_enqueue_style('wpcf-access-dev');
			}
			

			}
		
		}
	}
	
    public static function wpcf_access_select_group_metabox( ) 
    {
    	global $post, $wp_version;       
        
        if ( isset($post) && is_object($post) && $post->ID != '' ){
			if ( current_user_can('manage_options') || current_user_can('access_change_post_group') || current_user_can('access_create_new_group') ){
                add_meta_box('access_group', __('Access group', 'wpcf-access'), array(__CLASS__,'meta_box'), $post->post_type, 'side', 'high');
            }
            $hide_access_button = apply_filters('toolset_editor_add_access_button', false);
            if ( is_array($hide_access_button) ){
                $current_role = self::wpcf_get_current_logged_user_role();
                if ( in_array($current_role,$hide_access_button) ){
                    return;
                }
            }
            
            
            
			$post_object = get_post_type_object($post->post_type);	
			if (($post_object->publicly_queryable || $post_object->public) && $post_object->name != 'attachment'  ) {
			
				if (version_compare($wp_version, '3.1.4', '>')){
	               add_action('media_buttons', array(__CLASS__, 'wpcf_access_add_editor_icon'),20, 2);
	            }
	            else{
	               add_action('media_buttons_context', array(__CLASS__, 'wpcf_access_add_editor_icon'), 20, 2);
	            }
			}
		
		}
	}
	
	//Post types metabox for select group
	public static function meta_box( $post ){
		$message = __( 'No Access group selected.', 'wpcf-access' );
        $model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();
        if ( isset($settings_access[$post->post_type]['mode']) && $settings_access[$post->post_type]['mode'] != 'not_managed' ){
            if (isset($_GET['post'])) {
                $group = get_post_meta($_GET['post'], '_wpcf_access_group', true);
                
              
                if ( isset($settings_access[$group]) ){
                    $message = sprintf( 
                        __( '<p><strong>%s</strong> permissions will be applied to this post.', 'wpcf-access' ), $settings_access[$group]['title'] ).' 
                        </p>';
                        if ( current_user_can('manage_options') ){
                            $message .= '<p><a href="admin.php?page=types_access#'.$group.'">'.
                            sprintf(__( 'Edit %s group privileges', 'wpcf-access' ), $settings_access[$group]['title']).'</a></p>';
                        }
                }
            } 
            $out = '<div class="js-wpcf-access-post-group">'.$message.'</div>';
            if ( current_user_can('manage_options') ){
                $out .= '<input type="hidden" value="1" id="access-show-edit-link" />';
            }
            $out .= '<input type="button" value="'.__( 'Change Access group', 'wpcf-access' ).'" data-id="'.$post->ID.'" class="js-wpcf-access-assign-post-to-group button">';
            $out .= wp_nonce_field('wpcf-access-error-pages', 'wpcf-access-error-pages', true, false);
        }		
        else{
             $out = '<p>' . __( 'This content type is not currently managed by the Access plugin. To be able to add it to access groups, first go to the Access admin and allow Access to control it.', 'wpcf-access' ).' 
					</p>';
        }
		print $out;
	}
	

	//Check if current user have permission
	public static function wpcf_access_check_if_user_can( $role, $level ) 
    {
    	global $wp_roles;
		$cur_level = 0;
	
		$ordered_roles = Access_Helper::wpcf_access_order_roles_by_level($wp_roles->roles);
		foreach ($ordered_roles as $levels => $roles_data) 
        {
            if (empty($roles_data))
                continue;
			
			foreach ($roles_data as $role_slug => $role_options) 
        	{
        		if($role_slug == $role){
					$cur_level = $levels;		
				}
        	}
        			
		}	
		if ( $level>=$cur_level){
			return true;	
		}else{
			return false;	
		}
	}
	
    /**
     * Init function. 
     */
    public static function wpcf_access_plugins_loaded() 
    {
        // Force roles initialization
        // WP is lazy and it does not initialize $wp_roles if user is not logged in.
        global $wp_roles;
        global $wpcf_access;
        
        // Set main global $wpcf_access
        $wpcf_access = new stdClass();
        $model = TAccess_Loader::get('MODEL/Access');
        
        if (!isset($wp_roles))
            $wp_roles = new WP_Roles();

        // Access works standalone now
        define('WPCF_PLUS', true);
        if (!defined('WPCF_ACCESS_DEBUG'))
            define('WPCF_ACCESS_DEBUG', false);

        // TODO Not used yet
        // Take a snapshot (to restore on deactivation???)
        /*$snapshot = get_option('wpcf_access_snapshot', array());
        if (empty($snapshot)) {
            $snapshot = get_option('wp_user_roles', array());
            update_option('wpcf_access_snapshot', $snapshot);
        }*/

        // Settings
        $wpcf_access->settings = new stdClass;
        
        // TYpes
        $wpcf_access->settings->types = $model->getAccessTypes();
        
        // Taxonomies
        $wpcf_access->settings->tax = $model->getAccessTaxonomies();
        
        // Third party
        $wpcf_access->settings->third_party = $model->getAccessThirdParty();
        
        $wpcf_access->third_party = array();
        $wpcf_access->third_party_post = array();
        $wpcf_access->third_party_caps = array();
        
        // Rules
        $wpcf_access->rules = new stdClass();
        $wpcf_access->rules->types = array();
        $wpcf_access->rules->taxonomies = array();

        // Other
        $wpcf_access->errors = array();
        $wpcf_access->shared_taxonomies = array();
        $wpcf_access->upload_files = array();
        $wpcf_access->debug = array();
        $wpcf_access->debug_hooks_with_args = array();
        $wpcf_access->debug_all_hooks = array();

        $wpcf_access = apply_filters('types_access', $wpcf_access);
        
        // Setup roles
        self::$roles = self::wpcf_get_editable_roles();

        // Set locale
        $locale = get_locale();
        TAccess_Loader::loadLocale('wpcf-access', 'access-' . $locale . '.mo');
        
        // Load admin code
        if (is_admin()) 
        {
            // import/export ajax hook
            add_action('wp_ajax_access_import_export',  array(__CLASS__, 'import_export_hook'));
            
            
            /*
            * Admin functions.
            */
            /*
            * Menu functions.
            */
            add_action('admin_menu', array(__CLASS__, 'wpcf_access_admin_menu_hook'));

            /*
            * Post functions.
            */
            TAccess_Loader::load('CLASS/Post');
            //TAccess_Loader::load('CLASS/Admin');
        }

        add_action('init', array(__CLASS__, 'wpcf_access_init'), 9);
        add_action('init', array(__CLASS__, 'wpcf_access_late_init'), 9999);
        add_action('init', array(__CLASS__, 'wpcf_access_get_taxonomies_shared_init'), 19);

        TAccess_Loader::load('CLASS/Ajax');
        
        /*
         * Hooks to collect and map settings.
         */
        add_filter('wpcf_type', array(__CLASS__, 'wpcf_access_init_types_rules'), 10, 2);
        add_action('wpcf_type_registered', array(__CLASS__,  'wpcf_access_collect_types_rules'));
        add_filter('wpcf_taxonomy_data', array(__CLASS__,  'wpcf_access_init_tax_rules'), 10, 3);
        // WATCHOUT:  this hook callback does not exist
        //add_action('wpcf_taxonomy_registered', array(__CLASS__, 'wpcf_access_collect_tax_rules'));
        add_action('registered_post_type', array(__CLASS__,  'wpcf_access_registered_post_type_hook'), 10, 2);
        add_action('registered_taxonomy', array(__CLASS__,  'wpcf_access_registered_taxonomy_hook'), 10, 3);
        add_filter('types_access_check', array(__CLASS__,  'wpcf_access_filter_rules'), 15, 3);
        //TAccess_Loader::load('CLASS/Collect');
        
        /*
         * Check functions.
         * 
         * 'user_has_cap' is main WP filter we use to filter capability check.
         * All changes are done on-the-fly and per call. No caching.
         * 
         * WP accepts $allcaps array of capabilities returned.
         * It?s actually property of $WP_User->allcaps.
         *
         * TODO we should use other way to assign capabilities
         * This is runing on each current_user_can() call and it might happen docens of times per pagenow
         * At least we have added caching per cap
         * 
         */
       // if (is_admin()){
        //Changed priority to zero because conflict with Wordpres Seo
        //add_filter('user_has_cap', array(__CLASS__,  'wpcf_access_user_has_cap_filter'), 15, 3);
        add_filter('user_has_cap', array(__CLASS__,  'wpcf_access_user_has_cap_filter'), 0, 3);
        //}
        //        add_filter('role_has_cap', 'wpcf_access_role_has_cap_filter', 10, 3);    
        //TAccess_Loader::load('CLASS/Check');
        
        /*
         * Exceptions.
         */
        add_filter('types_access_check', array(__CLASS__,  'wpcf_access_exceptions_check'), 10, 3);
        //TAccess_Loader::load('CLASS/Exceptions');
        
        /*
         * Access hooks.
         */
        //TAccess_Loader::load('CLASS/Hooks');
        
        /*
         * Dependencies definitions.
         */
        add_action('admin_footer', array(__CLASS__,  'wpcf_access_dependencies_render_js'));
        //TODO: Review this hook, why we load this in frontend
        //add_action('wp_footer', array(__CLASS__,  'wpcf_access_dependencies_render_js'));
        
        add_filter('types_access_dependencies', array(__CLASS__,  'wpcf_access_dependencies_filter'));
        //TAccess_Loader::load('CLASS/Dependencies');
        
        TAccess_Loader::load('CLASS/Upload');
        TAccess_Loader::load('CLASS/Debug');

        do_action('wpcf_access_plugins_loaded');
    }
    
    /**
     * Init function. 
     */
    public static function wpcf_access_init() 
    {
        // Add debug info
        if (WPCF_ACCESS_DEBUG) {
            TAccess_Loader::loadAsset('STYLE/types-debug', 'types-debug', false);
            wp_enqueue_style('types-debug');
            wp_enqueue_script('jquery');
            add_action('admin_footer', array('Access_Debug', 'wpcf_access_debug'));
            add_action('wp_footer', array('Access_Debug', 'wpcf_access_debug'));
        }
		
        // Filter WP default capabilities for current user on 'init' hook
        // 
        // We need to remove some capabilities added because of user role.
        // Example: editor has upload_files but may be restricted
        // because of Access settings.
        self::wpcf_access_user_filter_caps();

        do_action('wpcf_access_init');
    }

    /**
     * Post init function. 
     */
    public static function wpcf_access_late_init() 
    {
        // Register all 3rd party hooks now
        // 
        // All 3rd party hooks should be registered all the time.
        // Otherwise they won't be called.
        self::wpcf_access_hooks_collect();

        do_action('wpcf_access_late_init');
    }
    
    /**
     * Sets shared taxonomies check.
     * 
     * @global type $wpcf_access
     * @staticvar null $cache
     * @return null 
     */
    public static function wpcf_access_get_taxonomies_shared_init() 
    {
        self::wpcf_access_get_taxonomies_shared();
    }
    
    /**
     * 'has_cap' filter.
     *
     * Returns all the modified capabilities. Cached per capability
     * NOTE cached per cap checked
     * NOTE maybe it sets them in just the first passand we do not need one per different cap check
     * 
     * @global type $current_user
     * @global type $wpcf_access->rules->types
     * @param array $allcaps All the capabilities of the user
     * @param array $cap     [0] Required capability
     * @param array $args    [0] Requested capability
     *                       [1] User ID
     *                       [2] Associated object ID
     * @return array
     */
    public static function wpcf_access_user_has_cap_filter($allcaps, $caps, $args) 
    {
        //taccess_log(array($caps, $args));
        /*
        $access_cache_user_has_cap = 'access_cache_user_has_cap';
		$arg3 = '';
		if ( isset($args[2]) ){
			$arg3 = '_'.$args[2];	
		}
		$access_cache_user_has_cap_key = md5( 'access::user' . $args[1]  . 'cap' . $args[0].$arg3 );
		$cached = wp_cache_get( $access_cache_user_has_cap_key, $access_cache_user_has_cap );
		if ( false === $cached ) { print_r($args);
			$allcaps = self::wpcf_access_check($allcaps, $caps, $args);
			wp_cache_add( $access_cache_user_has_cap_key, 'cached', $access_cache_user_has_cap );
		}
		*/
		
		if ( is_admin() || ( isset($args[0])  && ( preg_match("/\_cred/",$args[0]) || in_array($args[0], array('edit_post')) ) ) ){
			$allcaps = self::wpcf_access_check($allcaps, $caps, $args);
		}elseif( isset($args[0]) && in_array($args[0], array('unfiltered_html')) ){
			$access_cache_user_has_cap = 'access_cache_user_has_cap';
			$arg3 = '';
			if ( isset($args[2]) ){
				$arg3 = '_'.$args[2];	
			}
			$access_cache_user_has_cap_key = md5( 'access::user' . $args[1]  . 'cap' . $args[0].$arg3 );
			$cached = wp_cache_get( $access_cache_user_has_cap_key, $access_cache_user_has_cap );
			if ( false === $cached ) { 
				$allcaps = self::wpcf_access_check($allcaps, $caps, $args);
				wp_cache_add( $access_cache_user_has_cap_key, 'cached', $access_cache_user_has_cap );
			}	
		}
		return $allcaps;
        //return self::wpcf_access_check($allcaps, $caps, $args);
    }

    /**
     * Main check function.
     * 
     * @global type $wpcf_access
     * @global type $post
     * @global type $pagenow
     * @staticvar null $current_user
     * @param array $allcaps All the capabilities of the user
     * @param array $cap     [0] Required capability
     * @param array $args    [0] Requested capability
     *                       [1] User ID
     *                       [2] Associated object ID
     * @param bool $parse true|false to return $allcaps or boolean
     * @return array|boolean 
     */
    public static function wpcf_access_check($allcaps, $caps, $args, $parse = true) 
    {
        global $wpcf_access;
        
        // Set user (changed after noticed WP signon empty user)
        static $current_user = null, $_user_id=-1;
        /*
        if (is_null($current_user)) 
        {
            if (isset($_POST['log'])
                    && basename($_SERVER['PHP_SELF']) == 'wp-login.php') 
                    {
                $current_user = get_user_by('login', esc_sql($_POST['log']));
            } 
            else 
            {
                $current_user = new WP_User(get_current_user_id());
            }
        }*/
        $current_user = wp_get_current_user();
        // this is number but users stored are strings
        $_user_id = $current_user->ID;
        
        // Debug if some args[0] is array
        if (WPCF_ACCESS_DEBUG) 
        {
            if (empty($args[0]) || !is_string($args[0])) 
            {
                $wpcf_access->errors['cap_args'][] = array(
                    'file' => __FILE__ . ' #' . __LINE__,
                    'args' => func_get_args(),
                    'debug_backtrace' => debug_backtrace(),
                );
            }
        }
        if (empty($args[0]) || !is_string($args[0])) 
        {
            return $allcaps;
        }

        // Main capability queried
        $capability_requested = $capability_original = $args[0];
        
        // Other capabilities required to be true
        $caps_clone = $caps;

        // All user capabilities
        $allcaps_clone = $allcaps;

       // $map = self::wpcf_access_role_to_level_map();
		$map = wp_cache_get( 'wpcf_access_role_to_level_map_cache' );
		if ( false === $map ) {
			$map = self::wpcf_access_role_to_level_map();
			wp_cache_set( 'wpcf_access_role_to_level_map_cache', $map );
		} 
        $allow = null;
        $parse_args = array(
            'caps' => $caps_clone,
            'allcaps' => $allcaps_clone,
            'data' => array(), // default settings
            'args' => func_get_args(),
            'role' => false,
            'users' => false
        );

        // Allow check to be altered
        list($capability_requested, $parse_args) = apply_filters('types_access_check',
                array($capability_requested, $parse_args, $args));

        // TODO Monitor this
        // I saw mixup of $key => $cap and $cap => $true filteres by collect.php
        // Also we're adding sets of capabilities to 'caps'
    //    foreach ($parse_args['caps'] as $k => $v) {
    //        if (is_string($k)) {
    //            $parse_args['caps'][] = $k;
    //            unset($parse_args['caps'][$k]);
    //        }
    //    }
        // Debug
        if ($capability_original != $capability_requested) 
        {
            $wpcf_access->converted[$capability_original][$capability_requested] = 1;
        }

        $parse_args['cap'] = $capability_requested;

        // Allow rules to be altered
        $wpcf_access->rules = apply_filters('types_access_rules',
                $wpcf_access->rules, $parse_args);

        $override = apply_filters('types_access_check_override', null, $parse_args);
        if (!is_null($override)) 
        {
            return $override;
        }

        // Check post_types($wpcf_access->rules->types)
        // See if main requested capability ($capability_requested)
        // is in collected post types rules and process it.

        /*$log=0;
        if ('read'==$capability_original || 'read'==$capability_requested)
        {
            $log=1;
            taccess_log(array($capability_original, $capability_requested));
        }*/
        if (!empty($wpcf_access->rules->types[$capability_requested])) 
        {
            $types = $wpcf_access->rules->types[$capability_requested];
            $types_role = !empty($types['role']) ? $types['role'] : false;
            $types_role_mapped = !empty($map[$types_role]) ? $map[$types_role] : false;
            $types_users = !empty($types['users']) ? $types['users'] : false;
            $parse_args['role'] = $types_role;
            $parse_args['users'] = $types_users;
            
            /*if ('author'==$types_role)
            {
                taccess_log(array($capability_requested, $wpcf_access->rules->types[$capability_requested], $args));
            }*/
            
            // Return true for guest
            // Presumption that any capability that requires user to be not-logged
            // (guest) should be allowed. Because other roles have level ranked higher
            // than guest, means it's actually unrestricted by any means.
            if ($types_role == 'guest') 
            {
                return $parse ? self::wpcf_access_parse_caps(true, $parse_args) : true;
            }

            // Set data
            $parse_args['data'] = self::wpcf_access_types_caps();
            $parse_args['data'] = isset($parse_args['data'][$capability_requested]) ? $parse_args['data'][$capability_requested] : array();
            // Set level and user checks
            $level_needed = $types_role && $types_role_mapped ? $types_role_mapped : false;
            $user_needed = $types_users ? $types_users : false;

            $level_passed = false;

            if ($level_needed || is_array($user_needed)) 
            {
                $allow = false;

                // Check level
                if ($level_needed) 
                {
                    if (!empty($current_user->allcaps[$level_needed])) 
                    {
                        $allow = $level_passed = true;
                    }
                }

                // Check user
                if (!$level_passed && is_array($user_needed)) 
                {
                    //taccess_log(array($capability_requested, $user_needed));
                    $log=1;
                    if (in_array($_user_id, $user_needed)) 
                    {
                        $allow = true;
                    }
                }
            }
            $return  = $parse ?  self::wpcf_access_parse_caps((bool) $allow, $parse_args) : (bool) $allow;
            /*if ($log)
            {
                //taccess_log(array($capability_requested, $return));
            }*/
            return $return;
        }

        // Check taxonomies ($wpcf_access->rules->taxonomies)
        // See if main requested capability ($capability_requested)
        // is in collected taxonomies rules and process it.

        //taccess_log($capability_requested);
        if (!empty($wpcf_access->rules->taxonomies[$capability_requested])) 
        {
            //taccess_log($wpcf_access->rules->taxonomies);
            
            $tax = $wpcf_access->rules->taxonomies[$capability_requested];
            
            $tax_role = !empty($tax['role']) ? $tax['role'] : false;
            $tax_role_mapped = !empty($map[$tax_role]) ? $map[$tax_role] : false;
            $tax_users = !empty($tax['users']) ? $tax['users'] : false;
            $parse_args['role'] = $tax_role;
            $parse_args['users'] = $tax_users;

            // Check taxonomies 'follow'
            if (!isset($tax['taxonomy'])) 
            {
                $wpcf_access->errors['no_taxonomy_recorded'] = $tax;
            }
            $shared = self::wpcf_access_is_taxonomy_shared($tax['taxonomy']);
            //$follow = $shared ? false : $tax['follow'];
            if ($shared)
                $follow = false;
            elseif (isset($tax['follow']))
                $follow = $tax['follow'];
            else
                $follow = false;
            
            // have hardcoded the 'follow' capabilities,
            // so management is same as no follow mode
            $follow = false;

            // Return true for guest (same as for post types)
            if ($tax_role == 'guest') 
            {
                return $parse ? self::wpcf_access_parse_caps(true, $parse_args) : true;
            }

            // Set level and user
            $level_needed = $tax_role && $tax_role_mapped ? $tax_role_mapped : false;
            $user_needed = $tax_users ? $tax_users : false;

            $level_passed = false;

            // Set data
            $parse_args['data'] = self::wpcf_access_tax_caps();
            $parse_args['data'] = isset($parse_args['data'][$capability_requested]) ? $parse_args['data'][$capability_requested] : array();

            // Check if taxonomy use 'Same as parent' setting ('follow').
            if (!$follow) 
            {
                if ($level_needed || is_array($user_needed)) 
                {
                    $allow = false;
                    if ($level_needed) 
                    {
                        if (!empty($current_user->allcaps[$level_needed])) 
                        {
                            $allow = $level_passed = true;
                        }
                    }
                    if (!$level_passed && is_array($user_needed)) 
                    {
                        if (in_array($_user_id, $user_needed)) 
                        {
                            $allow = true;
                        }
                    }
                    return $parse ? self::wpcf_access_parse_caps((bool) $allow,
                                    $parse_args) : (bool) $allow;
                }
            } 
            /*else 
            {
                global $post, $pagenow;
                // Determine post type
                $post_type = self::wpcf_access_determine_post_type();

                // If no post type determined, return FALSE
                if (!$post_type) 
                {
                    $allow = false;
                    return $parse ? self::wpcf_access_parse_caps((bool) $allow,
                                    $parse_args) : (bool) $allow;
                } 
                else 
                {
                    $post_type = get_post_type_object($post_type);
                    $post_type = sanitize_title($post_type->labels->name);
                    $tax_caps = self::wpcf_access_tax_caps();
                    foreach ($tax_caps as $tax_cap_slug => $tax_slug_data) 
                    {
                        foreach ($tax_slug_data['match'] as $match => $replace) 
                        {
                            $level_passed = true;
                            if (strpos($capability_requested, $match) === 0) 
                            {
                                $post_type_check = $post_type;
                                if (
                                        $post_type_check && 
                                        !empty($wpcf_access->rules->types[$replace['match'] . $post_type_check])
                                ) 
                                {
                                    $level_needed = !empty($wpcf_access->rules->types[$replace['match'] . $post_type_check]['role']) && isset($map[$wpcf_access->rules->types[$replace['match'] . $post_type_check]['role']]) ? $map[$wpcf_access->rules->types[$replace['match'] . $post_type_check]['role']] : false;
                                    $user_needed = !empty($wpcf_access->rules->types[$replace['match'] . $post_type_check]['users']) ? $wpcf_access->rules->types[$replace['match'] . $post_type_check]['users'] : false;
                                    if ($level_needed || is_array($user_needed)) 
                                    {
                                        $allow = false;
                                        if ($level_needed) 
                                        {
                                            if (!empty($current_user->allcaps[$level_needed])) 
                                            {
                                                $allow = $level_passed = true;
                                            }
                                        }
                                        if (!$level_passed && is_array($user_needed)) 
                                        {
                                            if (in_array($current_user->ID, $user_needed)) 
                                            {
                                                $allow = true;
                                            }
                                        }
                                        return $parse ? self::wpcf_access_parse_caps((bool) $allow,
                                                        $parse_args) : (bool) $allow;
                                    }
                                } 
                                else if (!empty($allcaps_clone[$replace['default']])) 
                                {
                                    $allow = true;
                                    return $parse ? self::wpcf_access_parse_caps((bool) $allow,
                                                    $parse_args) : (bool) $allow;
                                }
                            }
                        }
                    }
                }
            }*/
        }


        // Check 3rd party saved settings (option 'wpcf-access-3rd-party')
        // After that check on-the-fly registered capabilities to use default data
        // This is already collected with wpcf_access_hooks_collect

        if (!empty($wpcf_access->third_party_caps[$capability_requested])) 
        {
            // check only requested cap not all
            $data=$wpcf_access->third_party_caps[$capability_requested];
            //foreach ($wpcf_access->third_party_caps as $cap => $data) {
            $wpcf_access->third_party_debug[$capability_requested] = 1;

            // Set saved role if available
            if (isset($data['saved_data']['role'])) 
            {
                $data['role'] = $data['saved_data']['role'];
            }
            // Set saved users if available
            $data['users'] = isset($data['saved_data']['users']) ? $data['saved_data']['users'] : false;
            
            $parse_args['role'] = $data['role'];
            $parse_args['users'] = $data['users'];
            
            // Return true for guest (same as post_types)
            if ($data['role'] == 'guest') 
            {
                return $parse ? self::wpcf_access_parse_caps(true, $parse_args) : true;
            }
            // removing level testing for custom 3rd party capabilities
            $level_needed = isset($map[$data['role']]) ? $map[$data['role']] : false;
            $user_needed = !empty($data['users']) ? $data['users'] : false;

            $level_passed = false;

            if ($level_needed || is_array($user_needed)) 
            {
                $parse_args['data'] = array();
                $allow = false;
                if ($level_needed) 
                {
                    if (!empty($current_user->allcaps[$level_needed])) 
                    {
                        $allow = $level_passed = true;
                    }
                }
                if (!$level_passed && is_array($user_needed)) 
                {
                    if (in_array($_user_id, $user_needed)) 
                    {
                        $allow = true;
                    }
                }
                return $parse ? self::wpcf_access_parse_caps((bool) $allow,
                                $parse_args) : (bool) $allow;
            }
            //}
        }
        $wpcf_access->debug_all_hooks[$capability_requested][] = $parse_args;
        return is_null($allow) ? $allcaps : self::wpcf_access_parse_caps((bool) $allow,
                        $parse_args);
    }

    /**
     * Parses caps.
     * 
     * @param type $allow
     * @param type $cap
     * @param type $caps
     * @param type $allcaps 
     */
    public static function wpcf_access_parse_caps($allow, $args) 
    {
        // Set vars
        $args_clone = $args;
        $cap = $args['cap'];
        $caps = $args['caps'];
        $allcaps = $args['allcaps'];
        $data = $args['data'];
    //    $role = $args['role'];
        $args = $args['args'];
        
        if ($allow) 
        {
            // If true - force all caps to true

            $allcaps[$cap] = 1;
            foreach ($caps as $c) 
            {
                // TODO - this is temporary solution for comments
                if ($cap == 'edit_comment'
                        && (strpos($c, 'edit_others_') === 0
                        || strpos($c, 'edit_published_') === 0)) {
                    $allcaps[$c] = 1;
                }
                // TODO Monitor this - tricky, WP requires that all required caps
                // to be true in order to allow cap.
                if (!empty($data['fallback'])) 
                {
                    foreach ($data['fallback'] as $fallback) 
                    {
                        $allcaps[$fallback] = 1;
                    }
                } 
                else 
                {
                    $allcaps[$c] = 1;
                }
            }
        } 
        else 
        {
            // If false unset caps in allcaps
            unset($allcaps[$cap]);

            // TODO Monitor this
            // Do we want to unset allcaps?
            foreach ($caps as $c) 
            {
                unset($allcaps[$c]);
            }
        }

        if (WPCF_ACCESS_DEBUG) 
        {
            global $wpcf_access;
            $debug_caps = array();
            foreach ($caps as $cap) 
            {
                $debug_caps[$cap] = isset($allcaps[$cap]) ? $allcaps[$cap] : 0;
            }
            $wpcf_access->debug[$cap][] = array(
                'parse_args' => $args_clone,
                'dcaps' => $debug_caps,
            );
        }
        return $allcaps;
    }

/**
 * 'role_has_cap' filter.
 * 
 * @global type $current_user
 * @global type $wpcf_access->rules->types
 * @param type $capabilities
 * @param type $cap
 * @param type $role
 * @return int 
 */
//function wpcf_access_role_has_cap_filter($capabilities, $cap, $role) {}

    /**
     * Adds capabilities on WPCF types before registration hook.
     * 
     * Access insists on using map_meta_cap true. It sets all post types to use
     * mapped capabilities.
     * 
     * Examples:
     * 'edit_posts => 'edit_types'
     * 'edit_others_posts => 'edit_others_views'
     * 'edit_published_posts => 'edit_published_cred'
     * 
     * This prevents using shared capabilities across post types
     * and so matching wrong settings.
     * 
     * If in debug mode, debug output will show if any capabilities are overlapping.
     * 
     * @param type $data
     * @param type $post_type
     * @return boolean 
     */
    public static function wpcf_access_init_types_rules($data, $post_type) 
    {
        $isTypesActive = self::wpcf_access_is_wpcf_active();
        if (!$isTypesActive)    return $data;
        
        $model = TAccess_Loader::get('MODEL/Access');
        
        $types = array();
        $types = $model->getAccessTypes();
        // Check if managed
        if (isset($types[$post_type]['mode'])) 
        {
            if ($types[$post_type]['mode'] === 'not_managed')
                return $data;

            // Set capability type (singular and plural names needed)
            if (!self::wpcf_is_object_valid('type', $data))
            {
                $types[$post_type]['mode'] = 'not_managed';
                $model->updateAccessTypes($types);
                return $data;
            }
            
            $data['capability_type'] = array(
                sanitize_title($data['labels']['singular_name']),
                sanitize_title($data['labels']['name'])
            );
            
            // Flag WP to use meta mapping
            $data['map_meta_cap'] = true;
        }
        return $data;
    }

    /**
     * Adds capabilities on WPCF taxonomies before registration hook.
     * 
     * Same as for post types. Create own capabilities for each taxonomy.
     * 
     * @global type $wpcf_access->rules->taxonomies
     * @param type $data
     * @param type $taxonomy
     * @param type $object_types
     * @return type 
     */
    public static function wpcf_access_init_tax_rules($data, $taxonomy, $object_types) 
    {
        global $wpcf_access;

        $isTypesActive = self::wpcf_access_is_wpcf_active();
        if (!$isTypesActive)    return $data;
        
        $model = TAccess_Loader::get('MODEL/Access');
        
        $taxs = array();
        $taxs = $model->getAccessTaxonomies();
        
        // Check if managed
        if (empty($taxs[$taxonomy]['mode']))
            return $data;
        
        $settings = $taxs[$taxonomy]; //$data['_wpcf_access_capabilities'];
        $mode = isset($settings['mode']) ? $settings['mode'] : 'not_managed';
        if ($mode == 'not_managed')
            return $data;

        // Match only predefined capabilities
        $caps = self::wpcf_access_tax_caps();
        foreach ($caps as $cap_slug => $cap_data) 
        {

            // Create capability slug
            $new_cap_slug = str_replace('_terms',
                    '_' . sanitize_title($data['labels']['name']), $cap_slug);
            $data['capabilities'][$cap_slug] = $new_cap_slug;
            // Set mode
            $wpcf_access->rules->taxonomies[$new_cap_slug]['follow'] = $mode == 'follow';

            // If mode is not 'folow' and settings are determined
            if (/*$mode != 'follow' &&*/ isset($settings['permissions'][$cap_slug])) 
            {
                $wpcf_access->rules->taxonomies[$new_cap_slug]['role'] = $settings['permissions'][$cap_slug]['role'];
                $wpcf_access->rules->taxonomies[$new_cap_slug]['users'] = isset($settings['permissions'][$cap_slug]['users']) ? $settings['permissions'][$cap_slug]['users'] : array();
            }

            // Add to rules
            $wpcf_access->rules->taxonomies[$new_cap_slug]['taxonomy'] = $taxonomy;
        }
        return $data;
    }

    /**
     * Sets rules for WPCF types after registration hook.
     * 
     * @global type $wpcf_access_types_rules
     * @param type $data 
     */
    public static function wpcf_access_collect_types_rules($data) 
    {
        global $wpcf_access, $wp_post_types, $current_user;
        
        //taccess_log($data);
        
        $model = TAccess_Loader::get('MODEL/Access');
        $type = $data->slug;
        $types = array();
        $types = $model->getAccessTypes();
        
        if (!isset($types[$type]))
            return false;
        
        $settings = $types[$type]; // $data->_wpcf_access_capabilities;
        if ($settings['mode'] == 'not_managed' || empty($settings['permissions']))
            return false;
        
        $caps = self::wpcf_access_types_caps();
        $mapped = array();

        // Map predefined to existing capabilities
        foreach ($caps as $cap_slug => $cap_spec) 
        {
            if (isset($settings['permissions'][$cap_spec['predefined']])) {
                $mapped[$cap_slug] = $settings['permissions'][$cap_spec['predefined']];
            } else {
                $mapped[$cap_slug] = $cap_spec['predefined'];
            }
        }

        // Set rule settings for post type by pre-defined caps
        foreach ($data->cap as $cap_slug => $cap_spec) 
        {
            if (isset($mapped[$cap_slug])) {
                if (isset($mapped[$cap_slug]['role'])) {
                    $wpcf_access->rules->types[$cap_spec]['role'] = $mapped[$cap_slug]['role'];
                } else {
                    $wpcf_access->rules->types[$cap_spec]['role'] = 'administrator';
                }
                $wpcf_access->rules->types[$cap_spec]['users'] = isset($mapped[$cap_slug]['users']) ? $mapped[$cap_slug]['users'] : array();
                $wpcf_access->rules->types[$cap_spec]['types'][$data->slug] = 1;
            }
        }
    }

    /**
     * Maps rules and settings for post types registered outside of Types.
     * 
     * @param type $post_type
     * @param type $args 
     */
    public static function wpcf_access_registered_post_type_hook($post_type, $args) 
    {
        global $wpcf_access, $wp_post_types;
        static $_builtin_types=null;
        
        $model = TAccess_Loader::get('MODEL/Access');
        
        $settings_access = $model->getAccessTypes();
        if (isset($wp_post_types[$post_type]))
        {
            // Force map meta caps, if not builtin
            if (in_array($post_type, array('post', 'page')))
            {
                switch ($post_type)
                {
                    case 'page':
                        $_sing='page';
                        $_plur='pages';
                        break;
                    case 'post':
                    default:
                        $_sing='post';
                        $_plur='posts';
                        break;
                }
            }
            else
            {
                // else use singular/plural names
                $_sing=sanitize_title($wp_post_types[$post_type]->labels->singular_name);
                $_plur=sanitize_title($wp_post_types[$post_type]->labels->name);
				if ( $_sing == $_plur ){
					$_plur = $_plur.'_s';	
				}
            }
            $capability_type=array( $_sing, $_plur );
            
            // set singular / plural caps based on names or default for builtins
            $tmp=unserialize(serialize($wp_post_types[$post_type]));
            $tmp->capability_type = $capability_type;
            $tmp->map_meta_cap = true;
            $tmp->capabilities = array();
            $tmp->cap = get_post_type_capabilities($tmp);
          
            
            // provide access pointers
            $wp_post_types[$post_type]->__accessIsCapValid=!self::wpcf_check_cap_conflict(array_values((array)$tmp->cap));
            $wp_post_types[$post_type]->__accessIsNameValid=self::wpcf_is_object_valid('type', self::wpcf_object_to_array($tmp));
            $wp_post_types[$post_type]->__accessNewCaps=$tmp->cap;
            
            if (isset($settings_access[$post_type])) 
            {
                $data = $settings_access[$post_type /*$args->name*/];

                /*if (null===$_builtin_types)
                {
                    $_builtin_types=get_post_types(array('_builtin' => true), 'names');
                }*/
                
                // Mark that will inherit post settings
                // TODO New types to be added
                if (
                    !in_array($post_type, array('post', 'page', 'attachment', 'media'))
                    && (empty($wp_post_types[$post_type]->capability_type)
                    || $wp_post_types[$post_type]->capability_type == 'post')
                ) 
                {
                    $wp_post_types[$post_type]->_wpcf_access_inherits_post_cap = 1;
                }

                if (
                    $data['mode'] == 'not_managed' ||
                    !$wp_post_types[$post_type]->__accessIsCapValid || 
                    !$wp_post_types[$post_type]->__accessIsNameValid
                )
                {
                    $settings_access[$post_type]['mode']='not_managed';
                    $model->updateAccessTypes($settings_access);
                    return false;
                }
                
                $caps = self::wpcf_access_types_caps();
                $mapped = array();
                // Map predefined
                foreach ($caps as $cap_slug => $cap_spec) 
                {
                    if (isset($data['permissions'][$cap_spec['predefined']])) 
                    {
                        $mapped[$cap_slug] = $data['permissions'][$cap_spec['predefined']];
                    } 
                    else 
                    {
                        $mapped[$cap_slug] = $cap_spec['predefined'];
                    }
                }
                
                // set singular / plural caps based on names or default for builtins
                $wp_post_types[$post_type]->capability_type = $capability_type;
                $wp_post_types[$post_type]->map_meta_cap = true;
                $wp_post_types[$post_type]->capabilities = array();
                $wp_post_types[$post_type]->cap = get_post_type_capabilities($wp_post_types[$post_type]);
                //$wp_post_types[$post_type]=$tmp;
                unset($wp_post_types[$post_type]->capabilities);
                
                // Set rule settings for post type by pre-defined caps
                foreach ($args->cap as $cap_slug => $cap_spec) 
                {
                    if (isset($mapped[$cap_slug])) 
                    {
                        if (isset($mapped[$cap_slug]['role'])) 
                        {
                            $wpcf_access->rules->types[$cap_spec]['role'] = $mapped[$cap_slug]['role'];
                        } 
                        else 
                        {
                            $wpcf_access->rules->types[$cap_spec]['role'] = 'administrator';
                        }
                        
                        $wpcf_access->rules->types[$cap_spec]['users'] = isset($mapped[$cap_slug]['users']) ? $mapped[$cap_slug]['users'] : array();
                        $wpcf_access->rules->types[$cap_spec]['types'][$post_type/*$args->name*/] = 1;
                    }
                }
                
                //taccess_log(array($post_type, $args->cap, $mapped, $wpcf_access->rules->types));
                
                // TODO create_posts set manually for now
                // Monitor WP changes
                if (!isset($wpcf_access->rules->types['create_posts']) && isset($wpcf_access->rules->types['edit_posts'])) 
                {
                    $wpcf_access->rules->types['create_posts'] = $wpcf_access->rules->types['edit_posts'];
                }
                /*if (!isset($wpcf_access->rules->types['create_pages']) && isset($wpcf_access->rules->types['edit_pages'])) {
                    $wpcf_access->rules->types['create_pages'] = $wpcf_access->rules->types['edit_pages'];
                }*/
                if (!isset($wpcf_access->rules->types['create_post']) && isset($wpcf_access->rules->types['edit_post'])) 
                {
                    $wpcf_access->rules->types['create_post'] = $wpcf_access->rules->types['edit_post'];
                }
                /*if (!isset($wpcf_access->rules->types['create_page']) && isset($wpcf_access->rules->types['edit_page'])) {
                    $wpcf_access->rules->types['create_page'] = $wpcf_access->rules->types['edit_page'];
                }*/
                // Check frontend read
                if ( $data['mode'] != 'not_managed' && !is_admin()) {
                    // Check read
                    if ( $data['mode'] != 'not_managed' ) {
                        // Set min reading role
                        if ( isset( $data['permissions']['read']['role'] ) ) {
                            self::set_frontend_read_permissions( $data['permissions']['read']['role'],
                                    $post_type );
                        } else {
                            // Missed setting? Debug that!
                            $wpcf_access->errors['missing_settings']['read'][] = array(
                                'caps' => $caps,
                                'data' => $data,
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Maps rules and settings for taxonomies registered outside of Types.
     * 
     * @param type $post_type
     * @param type $args 
     */
    public static function wpcf_access_registered_taxonomy_hook($taxonomy, $object_type, $args) 
    {
        global $wp_taxonomies, $wpcf_access;
        
        $model = TAccess_Loader::get('MODEL/Access');
        
        $settings_access = $model->getAccessTaxonomies();
        
        // do basic access tests
        if (isset($wp_taxonomies[$taxonomy])) 
        {
            $caps = self::wpcf_access_tax_caps();

            // Map pre-defined capabilities
            $new_caps=array();
            $valid=true;
            foreach ($caps as $cap_slug => $cap_data) 
            {
                // Create cap slug
                $new_cap_slug = str_replace('_terms',
                        '_' . sanitize_title($args['labels']->name), $cap_slug);
                
                if (!empty($args['_builtin']) || (isset($args['cap']->$cap_slug)
                    && $args['cap']->$cap_slug == $cap_data['default'])
                )
                {                
                    $new_caps[$cap_slug] = $new_cap_slug;
                }
                else if (isset($args['cap']->$cap_slug)  && 
                        isset($wpcf_access->rules->taxonomies[$args['cap']->$cap_slug])
                ) 
                {
                    $new_caps[$cap_slug] = $args['cap']->$cap_slug;
                }
            }
            
            // provide access pointers
            $wp_taxonomies[$taxonomy]->__accessIsCapValid=!self::wpcf_check_cap_conflict(array_values($new_caps));
            $wp_taxonomies[$taxonomy]->__accessIsNameValid=self::wpcf_is_object_valid('taxonomy', self::wpcf_object_to_array($wp_taxonomies[$taxonomy]));
            $wp_taxonomies[$taxonomy]->__accessNewCaps=$new_caps;
            
            taccess_log(array($taxonomy, $wp_taxonomies[$taxonomy]));
            
            if (isset($settings_access[$taxonomy]))
            {
                $data = $settings_access[$taxonomy];
                $mode = isset($data['mode']) ? $data['mode'] : 'not_managed';
                $data['mode'] = $mode;
                
                if (
                    $mode == 'not_managed' || 
                    !$wp_taxonomies[$taxonomy]->__accessIsCapValid ||
                    !$wp_taxonomies[$taxonomy]->__accessIsNameValid
                )
                {
                    // check capabilities
                    $settings_access[$taxonomy]['mode']='not_managed';
                    $model->updateAccessTaxonomies($settings_access);
                    return false;
                }
                
                foreach ($new_caps as $cap_slug=>$new_cap_slug)
                {
                    // Alter if tax is built-in or other has default capability settings
                    if (!empty($args['_builtin']) || (isset($args['cap']->$cap_slug)
                        && $args['cap']->$cap_slug == $caps[$cap_slug]['default'])
                    ) 
                    {
                        $wp_taxonomies[$taxonomy]->cap->$cap_slug = $new_cap_slug;
                        $wpcf_access->rules->taxonomies[$new_cap_slug]['follow'] = $mode == 'follow';
                        if (/*$mode != 'follow' &&*/ isset($data['permissions'][$cap_slug])) 
                        {
                            $wpcf_access->rules->taxonomies[$new_cap_slug]['role'] = $data['permissions'][$cap_slug]['role'];
                            $wpcf_access->rules->taxonomies[$new_cap_slug]['users'] = isset($data['permissions'][$cap_slug]['users']) ? $data['permissions'][$cap_slug]['users'] : array();
                        }

                        // Otherwise just map capabilities
                    } 
                    else if (isset($args['cap']->$cap_slug)  && 
                            isset($wpcf_access->rules->taxonomies[$args['cap']->$cap_slug])
                    ) 
                    {
                        $wpcf_access->rules->taxonomies[$args['cap']->$cap_slug]['follow'] = $mode == 'follow';
                        if (/*$mode != 'follow' &&*/ isset($data['permissions'][$cap_slug])) 
                        {
                            $wpcf_access->rules->taxonomies[$args['cap']->$cap_slug]['role'] = $data['permissions'][$cap_slug]['role'];
                            $wpcf_access->rules->taxonomies[$args['cap']->$cap_slug]['users'] = isset($data['permissions'][$cap_slug]['users']) ? $data['permissions'][$cap_slug]['users'] : array();
                        }
                    }
                    $wpcf_access->rules->taxonomies[$args['cap']->$cap_slug]['taxonomy'] = $taxonomy;
                }
            }
        }
        //unset($wpcf_access->rules->taxonomies[$args['cap']->$cap_slug]);
    }

    /**
     * Filters rules according to sets permitted.
     * 
     * Settings are defined in /includes/dependencies.php
     * Each capability is in relationship with some other and can't be used solely
     * without other.
     * 
     * @global type $current_user
     * @global type $wpcf_access
     * @staticvar null $cache
     * @return null 
     */
    public static function wpcf_access_filter_rules() 
    {
        global $current_user, $wpcf_access;
		
        static $cache = null;
        
        
        $args = func_get_args();
		$key_var1 = serialize($args[0][0]);
		$key_var2 = serialize($args[0][1]);
		$key_var3 = serialize($args[0][2]);
		$ckey = 'access_'.$key_var1.'_'.$key_var2.'_'.$key_var3;
		$result = wp_cache_get( $ckey );
		$cap = $args[0][0];
        $parse_args = $args[0][1];
        $args = $args[0][2];
		if ( false !== $result ) {
			return $result;
		} 
       
		$found = self::wpcf_access_search_cap($cap);
        if ($found) {
            $wpcf_access->debug_fallbacks_found[$cap] = $found;
        } else {
            $wpcf_access->debug_fallbacks_missed[$cap] = 1;
			wp_cache_set( $ckey, array($cap, $parse_args, $args) );
            return array($cap, $parse_args, $args);
        }

        $set = self::wpcf_access_user_get_caps_by_type($current_user->ID,
                $found['_context']);

        if (empty($set)) {
            $wpcf_access->debug_missing_context[$found['_context']][$cap]['user'] = $current_user->ID;
			wp_cache_set( $ckey, array($cap, $parse_args, $args) );
            return array($cap, $parse_args, $args);
        }

        // Set allowed caps accordin to sets allowed
        // /includes/dependencies.php will hook on 'access_dependencied' filter
        // and map capabilities in two arrays depending on main capability.
        // 
        // Example:
        // 'edit_own' disabled will have:
        // 'disallowed_caps' => ('edit_any', 'delete_any', 'publish')
        // 
        // 'edit_own' enabled will have:
        // 'allowed_caps' => ('read')
        
        $allowed_caps = $disallowed_caps = array();

        // Apply dependencies filter
        list($allowed_caps, $disallowed_caps) = apply_filters('types_access_dependencies',
                array($allowed_caps, $disallowed_caps, $set));

        $filtered = array();

        // TODO Monitor this
        foreach ($disallowed_caps as $disallowed_cap) 
        {
            if (in_array($disallowed_cap, $parse_args['caps'])) 
            {
                // Just messup checked caps
                $filtered['caps'] = array();
                $parse_args = array_merge($parse_args, $filtered);
                $wpcf_access->debug_caps_disallowed[$found['_context']][$cap][] = $disallowed_cap;
				wp_cache_set( $ckey, array($cap, $parse_args) );
                return array($cap, $parse_args);
            }
        }

        // TODO Monitor this
        foreach ($allowed_caps as $allowed_cap) 
        {
            $parse_args['caps'][] = $allowed_cap;
            $filtered['allcaps'][$allowed_cap] = true;
            $wpcf_access->debug_caps_allowed[$found['_context']][$cap][] = $allowed_cap;
        }

        $parse_args = array_merge($parse_args, $filtered);
        wp_cache_set( $ckey, array($cap, $parse_args) );
		return array($cap, $parse_args);
    }    

    /**
     * Defines dependencies.
     * 
     * @return array 
     */
    public static function wpcf_access_dependencies() 
    {
        $deps = array(
            // post types
            'edit_own' => array(
                'true_allow' => array('read'),
                'false_disallow' => array('edit_any', 'publish')
            ),
            'edit_any' => array(
                'true_allow' => array('read', 'edit_own'),
            ),
            'publish' => array(
                'true_allow' => array('read', 'edit_own', 'delete_own'),
            ),
            'delete_own' => array(
                'true_allow' => array('read'),
                'false_disallow' => array('delete_any', 'publish'),
            ),
            'delete_any' => array(
                'true_allow' => array('read', 'delete_own'),
            ),
            'read' => array(
                'false_disallow' => array('edit_own', 'delete_own', 'edit_any',
                    'delete_any', 'publish', 'read_private'),
            ),
            'read_private' => array(
                'true_allow' => array('read'),
            ),
            // taxonomies
            'edit_terms' => array(
                'false_disallow' => array('manage_terms'),
            ),
            'manage_terms' => array(
                'true_allow' => array('edit_terms', 'delete_terms')
            ),
            'delete_terms' => array(
                'true_allow' => array('manage_terms')
            ),
            
            'assign_terms' => array(),
            /*// comments
            'edit_own_comments' => array(
                'true_allow'=>array('read', 'publish')
            ),
            'edit_any_comments' => array(
                'true_allow'=>array('read', 'publish')
            )*/
        );
        return $deps;
    }

    /**
     * Renders JS 
     */
    public static function wpcf_access_dependencies_render_js() 
    {
        $deps = self::wpcf_access_dependencies();
        $output = '';
        $output .= "\n\n<script type=\"text/javascript\">\n/*<![CDATA[*/\n";
        $active = array();
        $inactive = array();
        $active_message = array();
        $inactive_message = array();

        $output .= 'var wpcf_access_dep_active_messages_pattern_singular = "'
                . __("Since you enabled '%cap', '%dcaps' has also been enabled.",
                        'wpcf-access')
                . '";' . "\n";
        $output .= 'var wpcf_access_dep_active_messages_pattern_plural = "'
                . __("Since you enabled '%cap', '%dcaps' have also been enabled.",
                        'wpcf-access')
                . '";' . "\n";
        $output .= 'var wpcf_access_dep_inactive_messages_pattern_singular = "'
                . __("Since you disabled '%cap', '%dcaps' has also been disabled.",
                        'wpcf-access')
                . '";' . "\n";
        $output .= 'var wpcf_access_dep_inactive_messages_pattern_plural = "'
                . __("Since you disabled '%cap', '%dcaps' have also been disabled.",
                        'wpcf-access')
                . '";' . "\n";
        /*$output .= 'var wpcf_access_edit_comments_inactive = "'
                . __("Since you disabled '%dcaps' user/role will not be able to edit comments also.",
                        'wpcf-access')
                . '";' . "\n";*/

        foreach ($deps as $dep => $data) 
        {
            $dep_data = self::wpcf_access_get_cap_predefined_settings($dep);
            $output .= 'var wpcf_access_dep_' . $dep . '_title = "'
                    . $dep_data['title']
                    . '";' . "\n";
            foreach ($data as $dep_active => $dep_set) 
            {
                if (strpos($dep_active, 'true_') === 0) 
                {
                    $active[$dep][] = '\'' . implode('\', \'', $dep_set) . '\'';
                    foreach ($dep_set as $cap) 
                    {
                        $_cap = self::wpcf_access_get_cap_predefined_settings($cap);
                        $active_message[$dep][] = $_cap['title'];
                    }
                } 
                else 
                {
                    $inactive[$dep][] = '\'' . implode('\', \'', $dep_set) . '\'';
                    foreach ($dep_set as $cap) 
                    {
                        $_cap = self::wpcf_access_get_cap_predefined_settings($cap);
                        $inactive_message[$dep][] = $_cap['title'];
                    }
                }
            }
        }

        foreach ($active as $dep => $array) 
        {
            $output .= 'var wpcf_access_dep_true_' . $dep . ' = ['
                    . implode(',', $array) . '];' . "\n";
            $output .= 'var wpcf_access_dep_true_' . $dep . '_message = [\''
                    . implode('\',\'', $active_message[$dep]) . '\'];' . "\n";
        }

        foreach ($inactive as $dep => $array) 
        {
            $output .= 'var wpcf_access_dep_false_' . $dep . ' = ['
                    . implode(',', $array) . '];' . "\n";
            $output .= 'var wpcf_access_dep_false_' . $dep . '_message = [\''
                    . implode('\',\'', $inactive_message[$dep]) . '\'];' . "\n";
        }

        $output .= "/*]]>*/\n</script>\n\n";
        echo $output;
    }

    /**
     * Returns specific cap dependencies.
     * 
     * @param type $cap
     * @param type $true
     * @return type 
     */
    public static function wpcf_access_dependencies_get($cap, $true = true) 
    {
        $deps = self::wpcf_access_dependencies();
        $_deps = array();
        if (isset($deps[$cap])) 
        {
            foreach ($deps[$cap] as $dep_active => $data) 
            {
                if ($true && strpos($dep_active, 'true_') === 0) {
                    $_deps[substr($dep_active, 5)] = $data;
                } else {
                    $_deps[substr($dep_active, 6)] = $data;
                }
            }
        }
        return $_deps;
    }

    /**
     * Filters dependencies.
     * 
     * @param type $args 
     */
    public static function wpcf_access_dependencies_filter($args) 
    {
        $allow = $args[0];
        $disallow = $args[1];
        $set = $args[2];
		$cache_key = 'wpcf_access_dependencies_filter_'.serialize($args[0]).'_'.serialize($args[1]).'_'.serialize($args[2]);
		$result = wp_cache_get( $cache_key );
		if ( false !== $result ) {
			return $result;
		}
        foreach ($set as $data) 
        {
            $context = $data['context'] == 'taxonomies' ? 'taxonomy' : 'post_type';
            $name = $data['parent'];
            $caps = $data['caps'];

            // Check dependencies and map them to WP readable
            foreach ($caps as $_cap => $true) 
            {
                $true = (bool) $true;

                // Get dependencies settings by cap
                $deps = self::wpcf_access_dependencies_get($_cap, $true);

                // Map to WP rules
                if (!empty($deps['allow'])) 
                {
                    foreach ($deps['allow'] as $__cap) 
                    {
                        $caps_readable = self::wpcf_access_predefined_to_wp_caps($context,
                                $name, $__cap);
                        $allow = $caps_readable + $allow;
                    }
                }
                if (!empty($deps['disallow'])) 
                {
                    foreach ($deps['disallow'] as $__cap) 
                    {
                        $caps_readable = self::wpcf_access_predefined_to_wp_caps($context,
                                $name, $__cap);
                        $disallow = $caps_readable + $disallow;
                    }
                }
            }
        }
		wp_cache_set( $cache_key, array($allow, $disallow) );
        return array($allow, $disallow);
    }    
    
    /**
     * Filters cap.
     * 
     * @param type $capability_requested
     * @return string 
     */
    public static function wpcf_access_exceptions_check() 
    {
        $args = func_get_args();
        $capability_requested = $args[0][0];
        $parse_args = $args[0][1];
        $args = $args[0][2];
		
        $found = self::wpcf_access_search_cap($capability_requested);
        // Allow filtering
        list($capability_requested, $parse_args, $args) = apply_filters('wpcf_access_exceptions',
                array($capability_requested, $parse_args, $args, $found));

        switch ($capability_requested) 
        {
            case 'edit_comment':
                $post_type='posts';
                foreach ($parse_args['caps'] as $kk=>$cc)
                {
                    if (0===strpos($cc, 'edit_published_'))
                    {
                        $post_type=str_replace('edit_published_', '', $cc);
                        break;
                    }
                    elseif (0===strpos($cc, 'edit_others_'))
                    {
                        $post_type=str_replace('edit_others_', '', $cc);
                        break;
                    }
                }
                if ( !current_user_can("edit_others_{$post_type}")) {
                    $user_id = $args[1];
                    $comment_id = $args[2];
                    $comment = get_comment($comment_id);
                    if ( !empty($comment->comment_post_ID) ) {
                        $post = get_post( $comment->comment_post_ID );
                        if (!empty($post->ID) && $post->post_author != $user_id ) {
                            return array('cannot_edit_comment', array('caps' => array()), $args);
                        }
                    }
                }
                
                $capability_requested = 'edit_'.$post_type;
                $parse_args['caps'] = array('edit_published_'.$post_type, 'edit_others_'.$post_type, 'edit_comment');
                break;

            case 'moderate_comments':
                $post_type='posts';
                foreach ($parse_args['caps'] as $kk=>$cc)
                {
                    if (0===strpos($cc, 'edit_published_'))
                    {
                        $post_type=str_replace('edit_published_', '', $cc);
                        break;
                    }
                    elseif (0===strpos($cc, 'edit_others_'))
                    {
                        $post_type=str_replace('edit_others_', '', $cc);
                        break;
                    }
                }
                $capability_requested = 'edit_others_'.$post_type;
                $parse_args['caps'] = array('edit_published_'.$post_type, 'edit_others_'.$post_type, 'edit_comment', 'moderate_comments');
                break;
                
    //        case 'delete_post':
    //        case 'edit_post':
            default:
                // TODO Watchout for more!
                if (isset($args[1]) && isset($args[2])) 
                {
                    $user = get_userdata(intval($args[1]));
                    $post_id = intval($args[2]);
                    $post = get_post($post_id);

                    if (!empty($user->ID) && !empty($post)) 
                    {
                        $parse_args_clone = $parse_args;
                        $args_clone = $args;
                        // check post id is valid, avoid capabilities warning
                        if (intval($post->ID)>0) 
                        {
                            $map = map_meta_cap($capability_requested, $user->ID,
                                    $post->ID);
                            if (is_array($map) && !empty($map[0])) 
                            {
                                foreach ($map as $cap) 
                                {
                                    $args_clone = array($cap);
                                    $result = self::wpcf_access_check($parse_args_clone['allcaps'],
                                            $map, $args_clone, false);
                                    if (!$result)
                                        $parse_args['caps'] = array();
                                }
                            }
                        }
                        // Not sure why we didn't use this mapping before
                        $capability_requested = self::wpcf_access_map_cap($capability_requested,
                                $post_id);
                    }

                    if (WPCF_ACCESS_DEBUG) 
                    {
                        global $wpcf_access;
                        $wpcf_access->debug_hooks_with_args[$capability_requested][] = array(
                            'args' => $args,
                        );
                    }
                }
                break;
        }
        return array($capability_requested, $parse_args, $args);
    }    

    /**
     * Register caps general settings.
     * 
     * @global type $wpcf_access
     * @param type $args
     * @return boolean 
     */
    public static function wpcf_access_register_caps($args) 
    {
        global $wpcf_access;
        foreach (array('area', 'group') as $check) {
            if (empty($args[$check])) {
                return false;
            }
        }
        if (in_array($args['area'], array('types', 'tax'))) {
            return false;
        }
        extract($args);
        if (!isset($caps)) {
            $caps = array($cap_id => $args);
        }
        foreach ($caps as $cap) {
            foreach (array('cap_id', 'title', 'default_role') as $check) {
                if (empty($cap[$check])) {
                    continue;
                }
            }
            extract($cap);
            $wpcf_access->third_party[$area][$group]['permissions'][$cap_id] = array(
                'cap_id' => $cap_id,
                'title' => $title,
                'role' => $default_role,
                'saved_data' => isset($wpcf_access->settings->third_party[$area][$group]['permissions'][$cap_id]) ? $wpcf_access->settings->third_party[$area][$group]['permissions'][$cap_id] : array('role' => $default_role),
            );
            return $wpcf_access->third_party[$area][$group]['permissions'][$cap_id];
        }
        return false;
    }

    /**
     * Returns specific post access settings.
     * 
     * @global type $post
     * @param type $post_id
     * @param type $area
     * @param type $group
     * @param type $cap_id
     * @return type 
     */
    public static function wpcf_access_get_post_access($post_id = null, $area = null,
            $group = null, $cap_id = null) 
    {
        if (is_null($post_id)) 
        {
            global $post;
            if (empty($post->ID)) 
            {
                return array();
            }
            $post_id = $post->ID;
        }
        $model = TAccess_Loader::get('MODEL/Access');
        $meta = $model->getAccessMeta($post_id); //get_post_custom($post_id, 'wpcf-access', true);
        if (empty($meta)) 
        {
            return array();
        }
        if (!empty($area) && empty($group)) 
        {
            return !empty($meta[$area]) ? $meta[$area] : array();
        }
        if (!empty($area) && !empty($group) && empty($cap_id)) 
        {
            return !empty($meta[$area][$group]) ? $meta[$area][$group] : array();
        }
        if (!empty($area) && !empty($group) && !empty($cap_id)) 
        {
            return !empty($meta[$area][$group]['permissions'][$cap_id]) ? $meta[$area][$group]['permissions'][$cap_id] : array();
        }
        return array();
    }
    
    /**
     * Register caps per post.
     * 
     * @global type $wpcf_access
     * @param type $args
     * @return boolean 
     */
    public static function wpcf_access_register_caps_post($args) 
    {
        global $wpcf_access, $post;
        foreach (array('area', 'group') as $check) 
        {
            if (empty($args[$check]))
                return false;
        }
        if (in_array($args['area'], array('types', 'tax')))
            return false;
        
        extract($args);
        if (!isset($caps))
            $caps = $args;
        
        foreach ($caps as $cap) 
        {
            foreach (array('cap_id', 'title', 'default_role') as $check) 
            {
                if (empty($cap[$check]))
                    continue;
            }
            extract($cap);
            $saved_data = self::wpcf_access_get_post_access($post->ID, $area, $group,
                    $cap_id);
            $wpcf_access->third_party_post[$post->ID][$area][$group]['permissions'][$cap_id] = array(
                'cap_id' => $cap_id,
                'title' => $title,
                'role' => $default_role,
                'saved_data' => !empty($saved_data) ? $saved_data : array('role' => $default_role),
            );
        }
    }

    /**
     * Collect all 3rd party hooks.
     * 
     * @global type $wpcf_access
     * @return type 
     */
    public static function wpcf_access_hooks_collect() 
    {
        global $wpcf_access;
        $r = array();
        
        $a = apply_filters('types-access-area', array());
        if (!is_array($a)) $a=array();
        
        foreach ($a as $area) 
        {
            if (!isset($r[$area['id']]))
                $r[$area['id']]=array();
                
            $g = apply_filters('types-access-group', array(), $area['id']);
            if (!is_array($g)) $g=array();
            foreach ($g as $group) 
            {
                if (!isset($r[$area['id']][$group['id']]))
                    $r[$area['id']][$group['id']]=array();
                
                $c = apply_filters('types-access-cap', array(), $area['id'],
                        $group['id']);
                if (!is_array($c)) $c=array();
                
                foreach ($c as $cap) 
                {
                    $r[$area['id']][$group['id']][$cap['cap_id']] = $cap;
                    $cap['area'] = $area['id'];
                    $cap['group'] = $group['id'];
                    $cap_reg_data = self::wpcf_access_register_caps($cap);
                    $wpcf_access->third_party_caps[$cap['cap_id']] = $cap_reg_data;
                }
            }
        }
        return $r;
    }

	public static function wpcf_access_get_current_page( ){
		global $wpdb;
		$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $post_types = get_post_types( '', 'names' );
        $check_post_id = true;
        $stored_post_types = wp_cache_get( 'wpcf-access-current-post-types' );
        if ( false === $stored_post_types ) {
            wp_cache_set( 'wpcf-access-current-post-types', $post_types );
            $check_post_id = true;
        }else{
            if ( $post_types == $stored_post_types ){
                $check_post_id = false;
            }else{
                wp_cache_set( 'wpcf-access-current-post-types', $post_types );
                $check_post_id = true;
            }
        }
		$post_id = wp_cache_get( 'wpcf-access-current-post-id' );
		if ( false === $post_id || $check_post_id ) {
			
			$post_id = url_to_postid( $url );
			if ( !isset($post_id)  || empty($post_id) || $post_id == 0 ){
				if ( count($_GET) == 1 && get_option('permalink_structure') == ''){
					foreach ( $_GET as $key => $val ) {
                        $val = self::wpcf_esc_like($val);
                        $key = self::wpcf_esc_like($key);
						if ( post_type_exists($key) ){ 
							$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = '%s' and post_type='%s'", $val, $key));
						}
					}
				}	 
			}
			
            if ( empty($post_id) ){
                $homepage = get_option( 'page_on_front' );
                if ( get_home_url().'/' == $url && $homepage != '' ){
                    $post_id = $homepage;
                }
            }
            
			if ( !isset($post_id) || empty($post_id) ){
				$post_id = '';	
			}
			if ( $post_id > 0 ){
				wp_cache_set( 'wpcf-access-current-post-id', $post_id );
			}
		}
		return $post_id;		
	}
    
    /**
     * Hides post type on frontend.
     * 
     * Checks if user is logged and if has required level to read posts.
     * This was determined only by role.
     *
     * In theory this is only run when registerin a post type so it changes its frontend settings
     * But this is being used too for frontend single pages by checking the group and possible error settings
     *
     * TODO This frontend settings per post should be done on another hook and on a singular / archive basis
     * 
     * @todo Check if checking by user_id is needed
     * 
     * @global type $wpcf_access
     * @global type $wp_post_types
     * @global type $current_user
     * @param type $role
     * @param type $post_type 
     */
    public static function set_frontend_read_permissions($role, $post_type) 
    {
        global $wpcf_access, $wp_post_types, $user_level,$wpdb;
		$current_user = wp_get_current_user();
		if ($role == 'guest'){
			$user_level = 0;	
		} 
		if ( $role != 'guest'){
			$user_level = self::wpcf_get_current_logged_user_level( $current_user );
		}
        
        $hide = false;
		
		$settings_access = $wpcf_access->settings->types;
        // Hide posts if user not logged and role is different than 'guest'
        if (empty($current_user->ID) && $role != 'guest') 
        {
            $hide = true;
        }
		
		// Check if user has required level according to role.
        // Instead may use:
        // wpcf_access_is_role_ranked_higher($role, $compare);
        // /embedded.php
        $level = self::wpcf_access_role_to_level($role);
        if ($level && (empty($current_user->ID)
                || !array_key_exists($level, $current_user->allcaps))) 
        {
            $hide = true;
        }
        
        /*
            @srdjan
            Access_Helper::set_frontend_read_permissions()
            
            there is missing user ID check
             - so it hides the post
            I added
                    $model = TAccess_Loader::get('MODEL/Access');
                    $settings_access = $model->getAccessTypes();
                    if ($hide && isset($settings_access[$post_type]['permissions']['read']['users'])
                            && !empty($current_user->ID)
                            && in_array($current_user->ID, $settings_access[$post_type]['permissions']['read']['users'])) {
                        $hide = false;
                    }
            after line #1700
             
             ---
             
            about read permission
            WP do not explicitly check read cause it assumes anyone can read posts. 
            so Access use set_frontend_read_permissions() to set post_type non-public if settings do not match.
        */
        
        if ($hide && !empty($current_user->ID))
        {
            // not hide if *specific user* has permissions to access it
            /*$model = TAccess_Loader::get('MODEL/Access');*/
            
            
            //taccess_log(array($settings_access, $wpcf_access));
            
            if (
                isset($settings_access[$post_type]) &&
                'permissions'==$settings_access[$post_type]['mode'] && // managed by access
                isset($settings_access[$post_type]['permissions']['read']['users']) &&
                in_array($current_user->ID, $settings_access[$post_type]['permissions']['read']['users'])
            ) 
            {
                $hide = false;
            }
        }
		
			$post_id = '';
			$is_custom = self::wpcf_access_check_custom_error($post_type, $role);
			
			if ( isset($is_custom[0]) && $is_custom[0] == 1){
				if ( $is_custom[1] == 'unhide' ){
					$hide = false;					
				}
				if ( $is_custom[1] == 'hide' ){
					$hide = true;		
				}
				if ( $is_custom[2] ){
					add_filter('comments_open', array('Access_Helper', 'wpcf_access_disable_comments'), 1);	
				}
				$post_id = self::wpcf_access_get_current_page();
			}
			
		//$hide = false;
        // Set post type properties to hide on frontend
        if ($hide && isset($wp_post_types[$post_type])) 
        {
            $wp_post_types[$post_type]->public = false;
			$wp_post_types[$post_type]->show_in_nav_menus = false;
            $wp_post_types[$post_type]->exclude_from_search = true;
            $wpcf_access->debug_hidden_post_types[] = $post_type;
			$wpcf_access->hide_built_in[] = $post_type;
          	if ( $post_type !== 'attachment' ){
          		$is_custom_archive = wp_cache_get( 'wpcf-access-archive-permissions-'.$post_type );
				if ( false === $is_custom_archive ) {
					$is_custom_archive = self::wpcf_access_check_archive_for_errors($post_type); 
					wp_cache_set( 'wpcf-access-archive-permissions-'.$post_type, $is_custom_archive );
				}		
          		
				if ( isset($is_custom_archive[0]) && empty($post_id) ){
						
					if ($is_custom_archive[0] == 'unhide'){
						// $wp_post_types[$post_type]->public = true;
				        // $wp_post_types[$post_type]->publicly_queryable = true;
				        // $wp_post_types[$post_type]->show_in_nav_menus = true;
						 $wpcf_access->hide_built_in = array_diff($wpcf_access->hide_built_in, array($post_type));
						 
						 if ( $is_custom_archive[1] == 'view' ){
						 	if ( function_exists('wpv_force_wordpress_archive') ){
						 		add_filter( 'wpv_filter_force_wordpress_archive', array( __CLASS__,'wpcf_access_replace_archive_view' ) );
							}	
						 }
						 if ( $is_custom_archive[1] == 'php' ){
						 	add_action( 'template_redirect', array( __CLASS__,'wpcf_access_replace_archive_php_template' ) );
						 }
					}	
					
				}
				
			}
          	
           
			
			//This hiden because we need to show that post types exists but nothinf found.
			//TODO GEN, check this
			//$wp_post_types[$post_type]->publicly_queryable = false;
			
			
            // Trigger change for posts and pages
            // Built-in post types can only be excluded from search
            // using following filters: 'posts_where', 'get_pages', 'the_comments'
            //if (in_array($post_type, array('post', 'page'))) 
            //{
                // If debug mode - record call
               

                // Register filters
                add_filter('posts_where', array('Access_Helper', 'wpcf_access_filter_posts'));
                add_filter('get_pages', array('Access_Helper', 'wpcf_access_exclude_pages'));
                add_filter('the_comments', array('Access_Helper', 'wpcf_access_filter_comments'));
				
            //}
        } 
        else if ($wp_post_types[$post_type]) 
        {
            $wp_post_types[$post_type]->public = true;
            $wp_post_types[$post_type]->publicly_queryable = true;
            $wp_post_types[$post_type]->show_in_nav_menus = true;
            $wp_post_types[$post_type]->exclude_from_search = false;
            $wpcf_access->debug_visible_post_types[] = $post_type;
        }
    }

	
	public static function wpcf_access_replace_archive_php_template(){
		global  $wp_query;
		
		$post_type_object = $wp_query->get_queried_object();
		if ($post_type_object) {
			$post_type = $post_type_object->name;
			$error = wp_cache_get( 'wpcf_archive_error_value_'.$post_type );
			if ( false !== $error ) {
				$tempalte = $error;
				if ( file_exists($tempalte) ){
					include( $tempalte );
					wp_cache_delete('wpcf_archive_error_value_'.$post_type);
					exit;
				}
			}
		}	
	}
	
	/*
	 * Override existing WPA for post type 
	 */
	public static function wpcf_access_replace_archive_view($view){
		global  $wp_query;
		
		$post_type_object = $wp_query->get_queried_object();
		
		if ($post_type_object) {
			$post_type = $post_type_object->name;
			$error = wp_cache_get( 'wpcf_archive_error_value_'.$post_type );
			if ( false !== $error ) {
				$view = $error;				
				wp_cache_delete('wpcf_archive_error_value_'.$post_type);
			}	
		}

		return $view;	
	}
	
	/*
	 * check if archive have custom errors
	 */
	public static function wpcf_access_check_archive_for_errors($post_type){
		global $wp_query,$current_user, $user_level;
		
		$role = self::wpcf_get_current_logged_user_role();
		if ($role == ''){
			$role = 'guest';
			$user_level = 0;	
		} 
				
		if ( $role == 'administrator' ){
			return;	
		}
		if ( $role != 'guest'){
			$user_level = self::wpcf_get_current_logged_user_level( $current_user );
		}
		$role = self::wpcf_convert_user_role( $role, $user_level);
		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes(); 
		
		if ( isset($settings_access['_archive_custom_read_errors'][$post_type]['permissions']['read']) && 
			isset($settings_access['_archive_custom_read_errors'][$post_type]['permissions']['read'][$role]) ){
			$error_type = $settings_access['_archive_custom_read_errors'][$post_type]['permissions']['read'][$role];
			
			if ( $error_type == 'error_ct' ){
				if ( isset($settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read']) ){
					$error_value = $settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read'][$role];
					wp_cache_set( 'wpcf_archive_error_value_'.$post_type, $error_value );
					return array('unhide','view',$error_value);
				}
				else{
					return;	
				}
			}
			if ( $error_type == 'error_php' ){
				if ( isset($settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read']) ){
					$error_value = $settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read'][$role];
					wp_cache_set( 'wpcf_archive_error_value_'.$post_type, $error_value );
					return array('unhide','php',$error_value);
				}
				else{
					return;	
				}
			}
			
			if ( $error_type == '' &&  !empty($settings_access['_archive_custom_read_errors'][$post_type]['permissions']['read']['everyone']) ){
				$error_type = $settings_access['_archive_custom_read_errors'][$post_type]['permissions']['read']['everyone'];
				if ( $error_type == 'error_ct' ){
					if ( isset($settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read']) ){
						$error_value = $settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'];
						
						wp_cache_set( 'wpcf_archive_error_value_'.$post_type, $error_value );
						return array('unhide','view',$error_value);
					}
					else{
						return;	
					}
				}
				if ( $error_type == 'error_php' ){
					if ( isset($settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read']) ){
						$error_value = $settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'];
						wp_cache_set( 'wpcf_archive_error_value_'.$post_type, $error_value );
						return array('unhide','php',$error_value);
					}
					else{
						return;	
					}
				}	
			}

		}	 
	}
	
	
	/*
	 * Replace archive output when no read permissions
	 */
	public static function wpcf_access_replace_archive_output($query){
		global  $wp_query;
		
		if ( is_post_type_archive() ) {
			
			
			$post_type_object = $wp_query->get_queried_object();

	        // See if we have a setting for this post type
	        if ($post_type_object) {
	        	global $wp_query,$current_user, $user_level;
				$role = self::wpcf_get_current_logged_user_role();				
				
				if ($role == ''){
					$role = 'guest';
					$user_level = 0;	
				} 
				
				if ( $role == 'administrator' ){
					return;	
				}
				if ( $role != 'guest'){
					$user_level = self::wpcf_get_current_logged_user_level( $current_user );
				}

				$role = self::wpcf_convert_user_role( $role, $user_level);				
				
				$model = TAccess_Loader::get('MODEL/Access');
				$settings_access = $model->getAccessTypes(); 
	        	
				if ( isset($settings_access['_archive_custom_read_errors'][$post_type_object->name]) ){
					if ( isset($settings_access['_archive_custom_read_errors_value'][$post_type_object->name]['permissions']['read']) ){
						$error_type = $settings_access['_archive_custom_read_errors'][$post_type_object->name]['permissions']['read'][$role];
						$error_value = $settings_access['_archive_custom_read_errors_value'][$post_type_object->name]['permissions']['read'][$role];
					}else{
						return;	
					}
				}else{
					return;	
				}				
			}			
		}
	}
	
	
	/*
	 * Disable comments on page where custom error - Content template 
	 */
	public static function wpcf_access_disable_comments(){
		return false;
	}
	
	/*
	 * Get current user role
	 */
	public static function wpcf_get_current_logged_user_role(){
		global $wp_query,$current_user, $user_level;
		$role = '';
		if ( is_user_logged_in() ){			
			if ( is_array($current_user->roles) ){					
				$role_temp = $current_user->roles;
				$role = array_shift($role_temp);
			}else{
				$role = $current_user->roles;	
			}
		}
		if ($role == ''){
			$role = 'guest';		
		} 
		return $role;
	}
	
	/*
	 * Get current user role
	 */
	public static function wpcf_get_current_logged_user_level( $user ){
		if ( isset($user->allcaps) ){
			$caps = $user->allcaps;
			for ($i=10;$i>=0;$i--){
				if ( isset($caps['level_'.$i] ) ){
					return $i;	
				}	
			}
			return 0;
		}else{
			return 0;	
		}
	}
	
	/*
	 * 
	 */
	public static function wpcf_convert_user_role( $role, $user_level ){
		
		if ($role == 'guest'){
			return $role;	
		}
		
		$managed_roles = array();
    	$roles = Access_Helper::wpcf_get_editable_roles();
		$default_roles = Access_Helper::wpcf_get_default_roles();
		foreach ($roles as $role => $details)
    	{
    		
    		for ($i=10;$i>=0;$i--){
    			if ( isset( $details['capabilities']['level_'.$i]) ){
    				if ( !isset( $managed_roles[$i] ) ){
    					$managed_roles[$i] = $role;
						$i=-1;
					}	
				}	
			}	
		}
		
		if ( isset($managed_roles[$user_level]) ){
			return $managed_roles[$user_level];
		}else{
			return 'guest';	
		}
	}
	/*
	 * Set error on page when custom error
	 */
	public static function wpcf_access_get_custom_error( $post_id ){
		global $wp_query,$current_user, $user_level;
		$role = self::wpcf_get_current_logged_user_role();		
		
		if ($role == ''){
			$role = 'guest';
			$user_level = 0;	
		} 
		
		if ( $role == 'administrator' ){
			return;	
		}
		if ( $role != 'guest'){
			$user_level = self::wpcf_get_current_logged_user_level( $current_user );
		}
		
		$role = self::wpcf_convert_user_role( $role, $user_level);		
		
		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();
		$post_type = get_post_type($post_id);
		$template_id = $show = '';
		$group = get_post_meta( $post_id, '_wpcf_access_group', true);
		$go = true;
		$read = false;
		
		//Specific user
		if ( isset($current_user->data->ID) ){
			if ( isset($settings_access[$post_type]['permissions']['read']['users']) && in_array($current_user->data->ID,$settings_access[$post_type]['permissions']['read']['users']) ){
				return array($show, '', true); 
			}	
			
		}
		//If group assigned to this post
		if ( isset($group) && !empty($group) && isset($settings_access[$group]) ){
			$show = '';
			$read = false;
			if ( isset($current_user->data->ID) ){
				if ( isset($settings_access[$group]['permissions']['read']['users']) && in_array($current_user->data->ID,$settings_access[$group]['permissions']['read']['users']) ){
					return array($show, '', true); 
				}	
				
			}
			if ( $settings_access[$group]['permissions']['read']['role'] != 'guest'){
				if ( $role != 'guest' ){
					$user_can = self::wpcf_access_check_if_user_can($settings_access[$group]['permissions']['read']['role'], $user_level);
					if ( $user_can == 1){
						$go = false;
						$read = true;
					}
				}else{
					$read = false;
				}
			}
			else{
				return array($show, '', true); 
			}
			
			//Check if current post and role have specific error.	
			if ( isset($settings_access['_custom_read_errors'][$group]['permissions']['read'][$role]) && $go ){
				if ( $settings_access['_custom_read_errors'][$group]['permissions']['read'][$role] == 'error_404'){
					$show = $settings_access['_custom_read_errors'][$group]['permissions']['read'][$role];
					$go = false;
					
				}
				if ( $settings_access['_custom_read_errors'][$group]['permissions']['read'][$role] == 'error_ct' &&
					isset($settings_access['_custom_read_errors_value'][$group]['permissions']['read'][$role])){
					$show = $settings_access['_custom_read_errors'][$group]['permissions']['read'][$role];
					$template_id = $settings_access['_custom_read_errors_value'][$group]['permissions']['read'][$role];
					$go = false;
					$read = true;
				}
				if ( $settings_access['_custom_read_errors'][$group]['permissions']['read'][$role] == 'error_php' &&
					isset($settings_access['_custom_read_errors_value'][$group]['permissions']['read'][$role])){
					$show = $settings_access['_custom_read_errors'][$group]['permissions']['read'][$role];
					$template_id = $settings_access['_custom_read_errors_value'][$group]['permissions']['read'][$role];
					$go = false;
				}
			}
			
			//Check if current group have specific error
			if ( isset($settings_access['_custom_read_errors'][$group]['permissions']['read']['everyone']) && $go ){				
				
				if ( $settings_access['_custom_read_errors'][$group]['permissions']['read']['everyone'] == 'error_404'){
					$show = $settings_access['_custom_read_errors'][$group]['permissions']['read']['everyone'];
					$go = false;
				}
				if ( $settings_access['_custom_read_errors'][$group]['permissions']['read']['everyone'] == 'error_ct' &&
					isset($settings_access['_custom_read_errors_value'][$group]['permissions']['read']['everyone'])){
					$show = $settings_access['_custom_read_errors'][$group]['permissions']['read']['everyone'];
					$template_id = $settings_access['_custom_read_errors_value'][$group]['permissions']['read']['everyone'];
					$go = false;
				}
				if ( $settings_access['_custom_read_errors'][$group]['permissions']['read']['everyone'] == 'error_php' &&
					isset($settings_access['_custom_read_errors_value'][$group]['permissions']['read']['everyone'])){
					$show = $settings_access['_custom_read_errors'][$group]['permissions']['read']['everyone'];
					$template_id = $settings_access['_custom_read_errors_value'][$group]['permissions']['read']['everyone'];
					$go = false;
				}	
			}
			
			return array($show, $template_id, $read); 
			
		}
		if ( !isset($settings_access[$post_type]['permissions']['read']['role']) ){
				$go = false;
				return array($show, '', true); 	
		}	
		if ( $go && $settings_access[$post_type]['mode'] === 'not_managed' ){
			$go = false;
			return array($show, '', true); 
		}
		
		if ( $role == 'guest'){
			if ( $settings_access[$post_type]['permissions']['read']['role'] == 'guest' ){
				$go = false;
				return array($show, '', true); 	
			}		
		}else{
			$user_can = self::wpcf_access_check_if_user_can($settings_access[$post_type]['permissions']['read']['role'], $user_level);
			if ( $user_can == 1){
				$go = false;
				return array($show, '', true); 
			}
		}
		
		if ( $go && $settings_access[$post_type]['mode'] != 'not_managed' ){
			if ( $settings_access[$post_type]['permissions']['read']['role'] != 'guest'){
				if ( $role != 'guest' ){
					$user_can = self::wpcf_access_check_if_user_can($settings_access[$post_type]['permissions']['read']['role'], $user_level);
					if ( $user_can == 1){
						$go = false;
						$read = true;
					}
				}else{
					$read = false;
				}
			}
			else{
				return array($show, '', true); 
			}
			
			
			//Check if current post and role have specific error.	
			if ( isset($settings_access['_custom_read_errors'][$post_type]['permissions']['read'][$role]) && $go ){
				
				if ( $settings_access['_custom_read_errors'][$post_type]['permissions']['read'][$role] == 'error_404'){
					$show = $settings_access['_custom_read_errors'][$post_type]['permissions']['read'][$role];
					$go = false;
					$read = false;
				}
				if ( $settings_access['_custom_read_errors'][$post_type]['permissions']['read'][$role] == 'error_ct' &&
					isset($settings_access['_custom_read_errors_value'][$post_type]['permissions']['read'][$role])){
					$show = $settings_access['_custom_read_errors'][$post_type]['permissions']['read'][$role];
					$template_id = $settings_access['_custom_read_errors_value'][$post_type]['permissions']['read'][$role];
					$go = false;
				}
				if ( $settings_access['_custom_read_errors'][$post_type]['permissions']['read'][$role] == 'error_php' &&
					isset($settings_access['_custom_read_errors_value'][$post_type]['permissions']['read'][$role])){
					$show = $settings_access['_custom_read_errors'][$post_type]['permissions']['read'][$role];
					$template_id = $settings_access['_custom_read_errors_value'][$post_type]['permissions']['read'][$role];
					$go = false;
				}
			}

			//Check if current group have specific error
			if ( isset($settings_access['_custom_read_errors'][$post_type]['permissions']['read']['everyone']) && $go ){
				if ( $settings_access['_custom_read_errors'][$post_type]['permissions']['read']['everyone'] == 'error_404'){
					$show = $settings_access['_custom_read_errors'][$post_type]['permissions']['read']['everyone'];
					$go = false;
					$read = false;
				}
				if ( $settings_access['_custom_read_errors'][$post_type]['permissions']['read']['everyone'] == 'error_ct' &&
					isset($settings_access['_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'])){
					$show = $settings_access['_custom_read_errors'][$post_type]['permissions']['read']['everyone'];
					$template_id = $settings_access['_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'];
					$go = false;
				}
				if ( $settings_access['_custom_read_errors'][$post_type]['permissions']['read']['everyone'] == 'error_php' &&
					isset($settings_access['_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'])){
					$show = $settings_access['_custom_read_errors'][$post_type]['permissions']['read']['everyone'];
					$template_id = $settings_access['_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'];
					$go = false;
				}	
			}

		}
		
		return array($show, $template_id, $read);
	}


	/*
	 * Check for custom error
	 */
	public static function wpcf_access_check_custom_error($post_type, $role){
		global $wpdb,$current_user, $user_level;
		
		$role = self::wpcf_get_current_logged_user_role();
		if ( $role == 'administrator' ){
			return;	
		}
		if ( $role != 'guest'){
			$user_level = self::wpcf_get_current_logged_user_level( $current_user );
		}
		if ( $user_level == 10){
			return;	
		}
		
		$post_id = self::wpcf_access_get_current_page();
		if ( !isset($post_id) || empty($post_id) ){
			return array(0,'');	
		}
		
		$return = 0;
		$do = '';
		$template = wp_cache_get( 'wpcf-access-post-permissions-'.$post_id );
		if ( false === $template ) {
			$template = self::wpcf_access_get_custom_error($post_id); 	
			wp_cache_set( 'wpcf-access-post-permissions-'.$post_id, $template );
		}	
		
		$disable_comments = false;
		if ( isset($template[0]) && isset($template[1]) && $template[0] == 'error_ct' ){
			$do = 'unhide';
			$return = 1;
			$disable_comments = true;
			add_filter('wpv_filter_force_template', array('Access_Helper', 'wpv_access_error_content_template'), 20, 3);
		}
		if ( isset($template[0]) && isset($template[1]) && $template[0] == 'error_php' && !$template[2] ){
			$do = 'unhide';
			$return = 1;
			add_action( 'template_redirect', array('Access_Helper', 'wpv_access_error_php_tempalte'), $template[1] );
		}
		if ( isset($template[0]) && isset($template[1]) && $template[0] == 'error_404' && !$template[2] ){
			$do = 'hide';
			add_action( 'pre_get_posts', array('Access_Helper', 'wpcf_exclude_selected_post_from_single'), 0 );
			$return = 1;
		}
		if ( $template[2] ){
			$do = 'unhide';
			$return = 1;	
		}
		if ( !$template[2] &&  empty($template[0])){
			$do = 'hide';
			$return = 1;	
		}
		return array($return, $do, $disable_comments);	
	}
	
	/*
	 * Exclude current post from list of queries
	 */
	public static function wpcf_exclude_selected_post_from_single( $query ){
		global $post;
 			if ( !is_admin() && $query->is_main_query() ) {

			$post_id = self::wpcf_access_get_current_page();
			if ( !isset($post_id) || empty($post_id)){
				return;	
			}
			$not_in =  $query->get('post__not_in');
			$not_in[] = $post_id;
			$query->set('post__not_in', $not_in);
			}
		
	}
	
	/*
	 * Load PHP Tempalte error
	 */
	public static function wpv_access_error_php_tempalte( $template ){
		global $post;
		
		if ( !isset($post) || empty($post)){
			return;	
		}
		$post_id = $post->ID;
		$template = self::wpcf_access_get_custom_error($post_id);
		$templates = wp_get_theme()->get_page_templates();
		if ( !empty($templates) ){
             $file = '';
			 foreach ( $templates as $template_name => $template_filename ) {
				 	if ( $template_filename == $template[1] ){
				 		$file = $template_name;
					}
			 }
             if ( !empty($file) && file_exists(get_template_directory() . '/'. $file) ){
                include( get_template_directory() . '/'. $file );
             }
             elseif(  !empty($file) && file_exists(get_stylesheet_directory() . '/'. $file) ){
                include( get_stylesheet_directory() . '/'. $file );
             }
             else{
                echo '<h2>' . __('Can\'t find php template', 'wpcf-access') . '</h2>';  
             }
			 exit;
		}
		else{
			return;	
		}
		
	}
	
	/*
	 * Load Content template error
	 */
	public static function wpv_access_error_content_template( $template_selected, $post_id, $kind = '' ){
		$template = self::wpcf_access_get_custom_error($post_id);
		if ( isset($template[0]) && !empty($template[0])){
			return $template[1];	
		}else{
			return;	
		}
		
	}
	
	
	
    /**
     * Filters posts.
     * 
     * @global type $wpcf_access
     * @global type $wpdb
     * @param type $args
     * @return type 
     */
    public static function wpcf_access_filter_posts($args) 
    {
        global $wpcf_access, $wpdb;
        if (!empty($wpcf_access->hide_built_in)) {
            foreach ($wpcf_access->hide_built_in as $post_type) {
                $args .= " AND $wpdb->posts.post_type <> '$post_type'";
            }
        }
        return $args;
    }

    /**
     * Excludes pages if necessary.
     * 
     * @global type $wpcf_access
     * @param type $pages
     * @return type 
     */
    public static function wpcf_access_exclude_pages($pages) 
    {
        global $wpcf_access;
        if (!empty($wpcf_access->hide_built_in)) {
            if (in_array('page', $wpcf_access->hide_built_in)) {
                return array();
            }
        }
        return $pages;
    }

    /**
     * Filters comments.
     * 
     * @global type $wpcf_access
     * @param type $comments
     * @return type 
     */
    public static function wpcf_access_filter_comments($comments) 
    {
        global $wpcf_access;
        if (!empty($wpcf_access->hide_built_in)) {
            foreach ($comments as $key => $comment) {
                // TODO Monitor this: only posts comment missing post_type?
                // Set 'post' as default
                if (!isset($comment->post_type)) {
                    $wpcf_access->errors['filter_comments_no_post_type'][] = $comment;
                    $comment->post_type = get_post_type($comment->comment_post_ID);
                }
                if (in_array($comment->post_type, $wpcf_access->hide_built_in)) {
                    unset($comments[$key]);
                }
            }
        }
        return $comments;
    }

    /**
     * Filters default WP capabilities for user.
     * 
     * WP adds default capabilities depending on built-in role
     * that sometimes by-pass user_can() check.
     * 
     * @todo Check if upload_files should be suspended from 3.5
     * @global type $current_user
     * @global type $wpcf_access 
     */
    public static function wpcf_access_user_filter_caps() 
    {
        $current_user = wp_get_current_user();
        if (!empty($current_user->allcaps)) {
            list($role, $level) = self::wpcf_access_rank_user($current_user->ID);
            foreach ($current_user->allcaps as $cap => $true) {
                $cap_found = self::wpcf_access_search_cap($cap);
                if (!empty($cap_found)) {
                    $allow = self::wpcf_access_is_role_ranked_higher($role,
                            $cap_found['role']);
                    if (!$allow) {
                        $allow = in_array($current_user->ID, $cap_found['users']);
                    }
                    if (!$allow) {
                        unset($current_user->allcaps[$cap]);
                    }
                }
            }
        }
    }

    /**
     * Determines post type.
     * 
     * @global type $post
     * @global type $pagenow
     * @return string 
     */
    public static function wpcf_access_determine_post_type() 
    {
        global $post;
        $post_type = false;
        $post_id = self::wpcf_access_determine_post_id();
        if (!empty($post) || !empty($post_id)) {
            if (get_post($post_id)) {
                return get_post_type($post_id);
            }
            $post_type = get_post_type($post);
        } /*else if (isset($_GET['post_type'])) {
            $post_type = $_GET['post_type'];
        } else if (isset($_POST['post_type'])) {
            $post_type = $_POST['post_type'];
        }*/
        else if (isset($_GET['post'])) {
            $post_id = intval($_GET['post']);
            $post_type = get_post_type($post_id);
        }
        else if (isset($_GET['post_id'])) {
            $post_id = intval($_GET['post_id']);
            $post_type = get_post_type($post_id);
        } else if (isset($_POST['post_id'])) {
            $post_id = intval($_POST['post_id']);
            $post_type = get_post_type($post_id);
        } else if (isset($_POST['post'])) {
            $post_id = intval($_POST['post']);
            $post_type = get_post_type($post_id);
        } else if (isset($_SERVER['HTTP_REFERER'])) {
            $split = explode('?', $_SERVER['HTTP_REFERER']);
            if (isset($split[1])) {
                parse_str($split[1], $vars);
                if (isset($vars['post_type'])) {
                    $post_type = $vars['post_type'];
                } else if (isset($vars['post'])) {
                    $post_type = get_post_type($vars['post']);
                } else if (strpos($split[1], 'post-new.php') !== false) {
                    $post_type = 'post';
                }
            } else if (strpos($_SERVER['HTTP_REFERER'], 'post-new.php') !== false
                    || strpos($_SERVER['HTTP_REFERER'], 'edit-tags.php') !== false
                    || strpos($_SERVER['HTTP_REFERER'], 'edit.php') !== false) {
                $post_type = 'post';
            }
        }
        return $post_type;
    }

    /**
     * Determines post ID.
     * 
     * @global type $post
     * @global type $pagenow
     * @return string bbbb
     */
    public static function wpcf_access_determine_post_id() 
    {
        global $post;
        if (!empty($post)) {
            return $post->ID;
        } else if (isset($_GET['post'])) {
            return intval($_GET['post']);
        } else if (isset($_POST['post'])) {
            return intval($_POST['post']);
        } else if (isset($_GET['post_id'])) {
            return intval($_GET['post_id']);
        } else if (isset($_POST['post_id'])) {
            return intval($_POST['post_id']);
        } else if (defined('DOING_AJAX') && isset($_SERVER['HTTP_REFERER'])) {
            $split = explode('?', $_SERVER['HTTP_REFERER']);
            if (isset($split[1])) {
                parse_str($split[1], $vars);
                if (isset($vars['post'])) {
                    return intval($vars['post']);
                } else if (isset($vars['post_id'])) {
                    return intval($vars['post_id']);
                }
            }
        }
        return false;
    }

    /**
     * Gets attachment parent post type.
     * 
     * @return boolean
     */
    public static function wpcf_access_attachment_parent_type() 
    {
        if (isset($_POST['attachment_id'])) {
            $post_id = intval($_POST['attachment_id']);
        } else if (isset($_GET['attachment_id'])) {
            $post_id = intval($_GET['attachment_id']);
        } else {
            return false;
        }
        $post = get_post($post_id);
        if (!empty($post->post_parent)) {
            $post_parent = get_post($post->post_parent);
            if (!empty($post_parent->post_type)) {
                return $post_parent->post_type;
            }
        }
        return false;
    }

    /**
     * Maps predefinied capabilities to specific post_type or taxonomy capability.
     * 
     * Example in case of Page post type:
     * edit_post => edit_page
     * 
     * @param type $context
     * @param type $name
     * @param type $cap
     * @return type 
     */
    public static function wpcf_access_predefined_to_wp_caps($context = 'post_type',
            $name = 'post', $cap = 'read') {

        // Get WP type object data
        $data = $context == 'taxonomy' ? get_taxonomy($name) : get_post_type_object($name);
        if (empty($data)) {
            return array();
        }

        // Get defined capabilities
        $caps = $context == 'taxonomy' ? self::wpcf_access_tax_caps() : self::wpcf_access_types_caps();

        // Set mapped WP capabilities
        $caps_mapped = array();
        foreach ($caps as $_cap => $_data) {
            if ($_data['predefined'] == $cap) {
                if (!empty($data->cap->{$_cap})) {
                    $caps_mapped[$data->cap->{$_cap}] = $data->cap->{$_cap};
                }
            }
        }
        return array_keys($caps_mapped);
    }

    /**
     * Check Media post type.
     * 
     * @global type $wp_version
     * @return type 
     */
    public static function wpcf_access_is_media_registered() 
    {
        global $wp_version;
        // WP 3.5
        return version_compare($wp_version, '3.4.3', '>');
    }

    /**
     * Maps capability according to current user and post_id.
     * 
     * @param type $parse_args
     * @param type $post_id
     * @return type 
     */
    public static function wpcf_access_map_cap($cap, $post_id) 
    {
        $current_user = wp_get_current_user();
        // do check for 0 post id
        if (intval($post_id)>0)
        {
            $map = map_meta_cap($cap, $current_user->ID, $post_id);
            if (is_array($map) && !empty($map[0])) {
                return $map[0];
            }
        }
        return $cap;
    }    
    
    /**
     * Returns cap settings declared in embedded.php
     * 
     * @param type $cap
     * @return type 
     */
    public static function wpcf_access_get_cap_settings($cap) 
    {
        $caps_types = self::wpcf_access_types_caps();
        if (isset($caps_types[$cap]))
            return $caps_types[$cap];
        
        $caps_tax = self::wpcf_access_tax_caps();
        if (isset($caps_tax[$cap]))
            return $caps_tax[$cap];
        
        return array(
            'title' => $cap,
            'role' => 'administrator',
            'predefined' => 'edit_any',
        );
    }

    /**
     * Returns cap settings declared in embedded.php
     * 
     * @param type $cap
     * @return type 
     */
    public static function wpcf_access_get_cap_predefined_settings($cap) 
    {
        $predefined = self::wpcf_access_types_caps_predefined();
        if (isset($predefined[$cap]))
            return $predefined[$cap];
        // If not found, try other caps
        return self::wpcf_access_get_cap_settings($cap);
    }
    
    public static function wpcf_access_get_taxonomies_shared(/*$tax=false*/) 
    {
        global $wpcf_access;
        static $cache = null;
        static $failed = array();
        
        /*if (!is_null($cache) && $tax && !isset($cache[$tax]))
        {
            if (!isset($failed[$tax]))
                $failed[$tax]=0;
            $failed[$tax]++;
        }*/
        if (is_null($cache) /*|| ($tax && isset($failed[$tax]) && $failed[$tax]<2)*/) 
        {
            $found = array();
            $model = TAccess_Loader::get('MODEL/Access');
            $taxonomies = $model->getTaxonomies(null);
            foreach ($taxonomies as $slug => $data) 
            {
                if (count($data->object_type) > 1) {
                    $found[$slug] = $data->object_type;
                }
            }
            $cache = $wpcf_access->shared_taxonomies = $found;
        }
        /*if ($tax && isset($cache[$tax]))
            return $cache[$tax];
        else if ($tax && !isset($cache[$tax]))
            return null;
        else*/
            return $cache;
    }
    
    /**
     * Checks if taxonomy is shared.
     * 
     * @param type $taxonomy
     * @return type 
     */
    public static function wpcf_access_is_taxonomy_shared($taxonomy) 
    {
        $shared = self::wpcf_access_get_taxonomies_shared(/*$taxonomy*/);
        return !empty($shared[$taxonomy]) ? $shared[$taxonomy] : false;
    }

    /**
     * Sets taxonomy mode.
     * 
     * @param type $taxonomy
     * @param type $mode
     * @return type 
     */
    public static function wpcf_access_get_taxonomy_mode($taxonomy, $mode = 'follow') 
    {
        // default to 'not_managed' if shared to have uniform handling of imported caps
        return self::wpcf_access_is_taxonomy_shared($taxonomy) ? /*'permissions'*/'not_managed' : $mode;
    }
    
    /**
     * Adds or removes caps for roles down to level.
     * 
     * @param type $role
     * @param type $cap
     * @param type $allow
     * @param type $distinct 
     */
    public static function wpcf_access_assign_cap_by_level($role, $cap) 
    {
        $ordered_roles = self::wpcf_access_order_roles_by_level(self::$roles);
        $flag = $found = false;
        foreach ($ordered_roles as $level => $roles) 
        {
            foreach ($roles as $role_name => $role_data) 
            {
                $role_set = get_role($role_name);
                if (!$flag)
                    $role_set->add_cap($cap);
                else
                    $role_set->remove_cap($cap);
                if ($role == $role_name)
                    $found = true;
            }
            if ($found)
                $flag = true;
        }
    }
    
    /**
     * Sorts default capabilities by predefined key.
     * 
     * @return type 
     */
    public static function wpcf_access_sort_default_types_caps_by_predefined() 
    {
        $default_caps = self::wpcf_access_types_caps();
        $caps = array();
        foreach ($default_caps as $cap => $cap_data) 
            $caps[$cap_data['predefined']][] = $cap;
        return $caps;
    }
    
    public static function wpcf_access_get_areas($overwrite=false) 
    {
        static $areas=null;
        
        if (is_null($areas) || $overwrite)
        {
            $areas = apply_filters('types-access-show-ui-area', array());
        }
        return $areas;
    }
    
    /**
     * Menu hook. 
     */
    public static function wpcf_access_admin_menu_hook() 
    {
        $pages = array(
            'types_access' => array(
                'title' => __('Access', 'wpcf-access'),
                'menu' => __('Access', 'wpcf-access'),
                'function' => array('Access_Helper', 'wpcf_access_admin_menu_page'),
                'load_hook' => array('Access_Helper', 'wpcf_access_admin_menu_load'),
                'subpages' => array(
                    'types_access_settings' => array(
                        'title' => __('Import/Export', 'wpcf-access'),
                        'menu' => __('Import/Export', 'wpcf-access'),
                        'function' => array('Access_Helper', 'wpcf_access_admin_import_export_page'),
                        'load_hook' => array('Access_Helper', 'wpcf_access_admin_import_export_load'),
                    ),
                    'types_access_help' => array(
                        'title' => __('Help', 'wpcf-access'),
                        'menu' => __('Help', 'wpcf-access'),
                        'function' => array('Access_Helper', 'wpcf_access_admin_help_page'),
                        'subpages' => array(
                            'types_access_debug' => array(
                                'title' => __('Debug information', 'wpcf-access'),
                                'menu' => __('Debug information', 'wpcf-access'),
                                'function' => array('Access_Helper', 'wpcf_access_admin_debug_page'),
                            ),
                        ),
                    ),
                ),
            ),
        );
        self::add_to_menu( $pages );
    }

    /**
     * Adds items to admin menu.
     *
     * @param array $menu array of menu items
     * @param string $parent_slug menu slug, if exist item is added as submenu
     *
     * @return void function do not return anything
     *
     */
    public static function add_to_menu($menu, $parent_slug = null)
    {
        foreach( $menu as $menu_slug => $data ) {
            $slug = null;
            if ( empty($parent_slug) ) {
                $slug = add_menu_page(
                    $data['title'],
                    $data['menu'],
                    'manage_options',
                    $menu_slug,
                    $data['function']
                );
            } else {
                $slug = add_submenu_page(
                    $parent_slug,
                    $data['title'],
                    $data['menu'],
                    'manage_options',
                    $menu_slug,
                    $data['function']
                );
            }
            /**
             * add load hook if is defined
             */
            if (!empty($slug) && isset($data['load_hook'])) {
                add_action('load-'.$slug, $data['load_hook']);
            }
            /**
             * add subpages
             */
            if (isset($data['subpages'])){
                self::add_to_menu($data['subpages'],$menu_slug);
            }
        }
    }

    /**
     * Adds help on admin pages.
     * 
     * @param type $contextual_help
     * @param type $screen_id
     * @param type $screen
     * @return type 
     */
    public static function wpcf_access_admin_plugin_help( $hook, $page='' ) 
    {
        global $wp_version;
        $call = false;
        $contextual_help = '';
        //$contextual_help = wpcf_access_admin_help( $call, $contextual_help );
        // WP 3.3 changes
        if ( version_compare( $wp_version, '3.2.1', '>' ) ) 
        {
            //set_current_screen( $hook );
            $screen = get_current_screen();
            if ( !is_null( $screen ) && $screen->id==$hook) 
            {
                $args = array(
                    'title' => __( 'Access', 'wpcf-access' ),
                    'id' => 'wpcf-access',
                    'content' => $contextual_help,
                    'callback' => false,
                );
                $screen->add_help_tab( $args );
            }
        } 
        else 
        {
            add_contextual_help( $hook, $contextual_help );
        }
    }
    
    /**
     * Menu page load hook. 
     */
    public static function wpcf_access_admin_menu_load() 
    {
        TAccess_Loader::loadAsset('STYLE/wpcf-access-dev', 'wpcf-access');
        TAccess_Loader::loadAsset('STYLE/types-suggest-dev', 'types-suggest');
        TAccess_Loader::loadAsset('SCRIPT/wpcf-access-dev', 'wpcf-access');
        TAccess_Loader::loadAsset('SCRIPT/types-suggest-dev', 'types-suggest');
		TAccess_Loader::loadAsset('STYLE/toolset-colorbox', 'toolset-colorbox');
		TAccess_Loader::loadAsset('STYLE/wpcf-access-dialogs-css', 'wpcf-access-dialogs-css');
		TAccess_Loader::loadAsset('SCRIPT/toolset-colorbox', 'toolset-colorbox');
        TAccess_Loader::loadAsset('SCRIPT/views-utils-script', 'views-utils-script');
		TAccess_Loader::loadAsset('STYLE/notifications', 'notifications'); 
        add_thickbox();
    }

    public static function wpcf_access_admin_import_export_load() 
    {
    	 TAccess_Loader::loadAsset('SCRIPT/wpcf-access-dev', 'wpcf-access');
        TAccess_Loader::loadAsset('STYLE/wpcf-access-dev', 'wpcf-access');
        TAccess_Loader::loadAsset('SCRIPT/wpcf-access-utils-dev', 'wpcf-access-utils');
		TAccess_Loader::loadAsset('STYLE/toolset-colorbox', 'toolset-colorbox');
		TAccess_Loader::loadAsset('STYLE/wpcf-access-dialogs-css', 'wpcf-access-dialogs-css');
		TAccess_Loader::loadAsset('SCRIPT/toolset-colorbox', 'toolset-colorbox');
        TAccess_Loader::loadAsset('SCRIPT/views-utils-script', 'views-utils-script');
		TAccess_Loader::loadAsset('STYLE/notifications', 'notifications');
		add_thickbox(); 
    }
    
    /**
     * Menu page render hook. 
     */
    public static function wpcf_access_admin_menu_page() 
    {
        TAccess_Loader::load('CLASS/Admin_Edit');
        echo "\r\n" . '<div class="wrap">
        <div id="icon-wpcf-access" class="icon32"><br /></div>
        <h2>' . __('Access', 'wpcf-access') . '</h2>' . "\r\n";
        Access_Admin_Edit::wpcf_access_admin_edit_access();
        echo "\r\n" . '</div>' . "\r\n";
    }
    
    /**
     * Import/Export page render hook. 
     */
    public static function wpcf_access_admin_import_export_page() 
    {
        if (isset($_FILES['access-import-file']) && isset($_POST['access-import']) && wp_verify_nonce($_POST['access-import-form'], 'access-import-form'))
        {
            TAccess_Loader::load('CLASS/XML_Processor');
            $options=array();
            if (isset($_POST['access-overwrite-existing-settings']))
            {
                $options['access-overwrite-existing-settings']=1;
            }
            if (isset($_POST['access-remove-not-included-settings']))
            {
                $options['access-remove-not-included-settings']=1;
            }
            $results=Access_XML_Processor::importFromXML($_FILES['access-import-file'], $options);
            echo TAccess_Loader::tpl('import-export', array('results'=>$results));
        }
        else
            echo TAccess_Loader::tpl('import-export');
    }

    /**
     * debug page render hook.
     */
    public static function wpcf_access_admin_debug_page()
    {
        include_once TACCESS_INCLUDES_PATH.'/debug/debug-information.php';
    }

    /**
     * Help page render hook.
     */
    public static function wpcf_access_admin_help_page()
    {
        echo TAccess_Loader::tpl('help');
    }

    // ajax hook
    public static function import_export_hook($action)
    {
        if (isset($_POST['access-export']) && wp_verify_nonce($_POST['access-export-form'], 'access-export-form'))
        {
            TAccess_Loader::load('CLASS/XML_Processor');
            Access_XML_Processor::exportToXML('all');
        }
    }
    
    public static function wpcf_access_is_wpcf_active() 
    {
        if (defined('WPCF_VERSION') || defined('WPCF_RUNNING_EMBEDDED'))
            return true;
        return false;
    }
    
    /**
     * Parses submitted data.
     * 
     * @param type $data
     * @return type 
     */
    public static function wpcf_access_parse_permissions($data, $caps, $custom = false) 
    {
        $permissions = array();
        // TODO Monitor this (fails sometimes as 3.5)
        if (empty($data['__permissions']))
            return $permissions;
        
        foreach ($data['__permissions'] as $cap => $data_cap) 
        {
            $cap = sanitize_text_field($cap);
            // WATCHOUT: it had: isset($data_cap['users']) ? $data_cap : array();
            $users = isset($data_cap['users']) ? $data_cap['users'] : array();
            // Check if submitted
            if (isset($data['permissions'][$cap])) 
            {
                $permissions[$cap] = $data['permissions'][$cap];
            } 
            else 
            {
                $permissions[$cap] = $data_cap;
            }
            
            if (!isset($permissions[$cap]['role']) || empty($permissions[$cap]['role']))
            {
                //taccess_log($permissions[$cap]);
                // this can be empty on $_POST, but if so, the admin role is implied
                // so make it so ;)
                $permissions[$cap] = array_merge($permissions[$cap], array('role'=>'administrator'));
                //taccess_log($permissions[$cap]);
            }
            
            // Make sure only pre-defined are used on ours, third-party rules
            // can have anything they want.
            if (!$custom && !isset($caps[$cap])) 
            {
                unset($permissions[$cap]);
                continue;
            }
            
            // Add users
            if (!empty($users)) 
            {
                $permissions[$cap]['users'] = array_values($users);
            }
        }
        return $permissions;
    }
    
    /**
     * Defines predefined capabilities.
     * 
     * @return array 
     */
    public static function wpcf_access_types_caps_predefined() 
    {
        $modes = array(
            // posts
            'read' => array(
                'title' => __('Read', 'wpcf-access'),
                'role' => 'guest',
                'predefined' => 'read',
            ),
            'read_private' => array(
                'title' => __('Preview any', 'wpcf-access'),
                'role' => 'administrator',
                'predefined' => 'read_private',
            ),
            'edit_own' => array(
                'title' => __('Edit own', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_own',
            ),
            'delete_own' => array(
                'title' => __('Delete own', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'delete_own',
            ),
            'edit_any' => array(
                'title' => __('Edit any', 'wpcf-access'),
                'role' => 'editor',
                'predefined' => 'edit_any',
            ),
            'delete_any' => array(
                'title' => __('Delete any', 'wpcf-access'),
                'role' => 'editor',
                'predefined' => 'delete_any',
            ),
            'publish' => array(
                'title' => __('Publish', 'wpcf-access'),
                'role' => 'author',
                'predefined' => 'publish',
            )/*,
            'read_comments' => array(
                'title' => __('Read Comments', 'wpcf-access'),
                'role' => 'guest',
                'predefined' => 'read',
            ),
            'edit_own_comments' => array(
                'title' => __('Edit own Comments', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_own_comments',
            ),
            'edit_any_comments' => array(
                'title' => __('Edit any Comments', 'wpcf-access'),
                'role' => 'editor',
                'predefined' => 'edit_any_comments',
            )*/
        );
        return $modes;
    }
    
    public static function wpcf_check_cap_conflict($caps)
    {
        $wp_default_caps=array(
            'activate_plugins',
            'add_users',
            'create_users',
            'delete_plugins',
            'delete_themes',
            'delete_users',
            'edit_dashboard',
            'edit_files',
            'edit_plugins',
            'edit_theme_options',
            'edit_themes',
            'edit_users',
            'export',
            'import',
            'install_plugins',
            'install_themes',
            'list_users',
            'manage_options',
            'promote_users',
            'remove_users',
            'switch_themes',
            'unfiltered_html',
            //'unfiltered_upload',
            'update_core',
            'update_plugins',
            'update_themes',
            //'upload_files'
        );
        
        $cap_conflict=array_intersect($wp_default_caps, (array)$caps);
        
        if (!empty($cap_conflict))
            return true;
        return false;
    }
    
    public static function wpcf_get_types_caps_default()
    {
        return array(
            // posts
            'read' => array(
                'role' => 'guest'
            ),
            'edit_own' => array(
                'role' => 'contributor'
            ),
            'delete_own' => array(
                'role' => 'contributor'
            ),
            'edit_any' => array(
                'role' => 'editor'
            ),
            'delete_any' => array(
                'role' => 'editor'
            ),
            'publish' => array(
                'role' => 'author'
            ));
    }
    
    public static function wpcf_get_taxs_caps_default()
    {
        return array(
            'manage_terms' => array(
                'role' => 'editor'
            ),
            'edit_terms' => array(
                'role' => 'contributor'
            ),
            'delete_terms' => array(
                'role' => 'contributor'
            ),
            'assign_terms' => array(
                'role' => 'contributor'
            ),
        );
    }
    
    /**
     * Defines capabilities.
     * 
     * @return type 
     */
    public static function wpcf_access_types_caps() 
    {
        $caps = array(
            //
            // READ
            //
            'read_post' => array(
                'title' => __('Read post', 'wpcf-access'),
                'role' => 'guest',
                'predefined' => 'read',
            ),
            'read_private_posts' => array(
                'title' => __('Read private posts', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_own',
            ),
            //
            // EDIT OWN
            //
            'create_post' => array(
                'title' => __('Create post', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_own',
            ),
            'create_posts' => array(
                'title' => __('Create posts', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_own',
            ),
            /*'create_page' => array(
                'title' => __('Create page', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_own',
            ),
            'create_pages' => array(
                'title' => __('Create pages', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_own',
            ),*/
            'edit_post' => array(
                'title' => __('Edit post', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_own',
            ),
            'edit_posts' => array(
                'title' => __('Edit posts', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_own',
            ),
            /*'edit_page' => array(
                'title' => __('Edit page', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_own',
            ),
            'edit_pages' => array(
                'title' => __('Edit pages', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_own',
            ),*/
            'edit_comment' => array(
                'title' => __('Moderate comments', 'wpcf-access'),
                'role' => 'author',
                'predefined' => 'edit_own',//'edit_own_comments',
                'fallback' => array('edit_published_posts', 'edit_others_posts'),
            ),
            //
            // DELETE OWN
            //
            'delete_post' => array(
                'title' => __('Delete post', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'delete_own',
            ),
            'delete_posts' => array(
                'title' => __('Delete posts', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'delete_own',
            ),
            'delete_private_posts' => array(
                'title' => __('Delete private posts', 'wpcf-access'),
                'role' => 'editor',
                'predefined' => 'delete_own',
            ),
            //
            // EDIT ANY
            //
            'edit_others_posts' => array(
                'title' => __('Edit others posts', 'wpcf-access'),
                'role' => 'editor',
                'predefined' => 'edit_any',
            ),
            // TODO this should go in publish
            'edit_published_posts' => array(
                'title' => __('Edit published posts', 'wpcf-access'),
                'role' => 'author',
                'predefined' => 'edit_own',
            ),
            'edit_private_posts' => array(
                'title' => __('Edit private posts', 'wpcf-access'),
                'role' => 'editor',
                'predefined' => 'edit_any',
            ),
            'moderate_comments' => array(
                'title' => __('Moderate comments', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit_any_comments',
                'fallback' => array('edit_others_posts', 'edit_published_posts'),
            ),
            //
            // DELETE ANY
            //
            'delete_others_posts' => array(
                'title' => __('Delete others posts', 'wpcf-access'),
                'role' => 'editor',
                'predefined' => 'delete_any',
            ),
            // TODO this should go in publish
            'delete_published_posts' => array(
                'title' => __('Delete published posts', 'wpcf-access'),
                'role' => 'author',
                'predefined' => 'delete_own',
            ),
            //
            // PUBLISH
            //
            'publish_posts' => array(
                'title' => __('Publish post', 'wpcf-access'),
                'role' => 'author',
                'predefined' => 'publish',
            ),
            //
            // NOT SURE
            //
    //        'read' => array(
    //            'title' => __('Read', 'wpcf-access'),
    //            'role' => 'guest',
    //            'predefined' => 'read',
    //        ),
        );
        return apply_filters('wpcf_access_types_caps', $caps);
    }

    public static function wpcf_get_default_roles()
    {
        return array('administrator', 'editor', 'author', 'contributor', 'subscriber');
    }
    
    public static function wpcf_types_to_tax_caps($tax, $taxdata, $post_caps)
    {
        $tax_caps_map = self::wpcf_access_tax_caps();
        $tax_caps = array();
        
        $tax_map_cap = isset($taxdata['cap']) ? $taxdata['cap'] : array();
        
        if (!isset($post_caps['permissions']))
            return $tax_caps;
            
        foreach ($tax_caps_map as $tcap => $mdata)
        {
        	$match_var = array_keys($mdata['match']);
            $match = array_shift($match_var);
            $replace = $mdata['match'][$match];
            $tax_cap = $tcap ; //isset($tax_map_cap[$tcap]) ? $tax_map_cap[$tcap] : $match.$tax_plural;
            foreach ($post_caps['permissions'] as $cap=>$data)
            {
                if (0===strpos($cap, $replace['match_access']))
                {
                    // copy roles and users from post type caps to associated tax caps
                    // follow , ;)
                    $tax_caps[$tax_cap]=$data;
                    break;
                }
            }
            // use a default here
            if (!isset($tax_caps[$tax_cap]))
            {
                $tax_caps[$tax_cap]=array('role'=>'administrator');
            }
        }
        return $tax_caps;
    }
    
    /**
     * Defines capabilities.
     * 
     * @return type 
     */
    public static function wpcf_access_tax_caps() 
    {
        $caps = array(
            'manage_terms' => array(
                'title' => __('Manage terms', 'wpcf-access'),
                'role' => 'editor',
                'predefined' => 'manage',
                'match' => array(
                    'manage_' => array(
                        'match_access' => 'edit_any',
                        'match' => 'edit_others_',
                        'default' => 'manage_categories',
                    ),
                ),
                'default' => 'manage_categories',
            ),
            'edit_terms' => array(
                'title' => __('Edit terms', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit',
                'match' => array(
                    'edit_' => array(
                        'match_access' => 'edit_any',
                        'match' => 'edit_others_',
                        'default' => 'manage_categories',
                    ),
                ),
                'default' => 'manage_categories',
            ),
            'delete_terms' => array(
                'title' => __('Delete terms', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit',
                'match' => array(
                    'delete_' => array(
                        'match_access' => 'edit_any',
                        'match' => 'edit_others_',
                        'default' => 'manage_categories',
                    ),
                ),
                'default' => 'manage_categories',
            ),
            'assign_terms' => array(
                'title' => __('Assign terms', 'wpcf-access'),
                'role' => 'contributor',
                'predefined' => 'edit',
                'match' => array(
                    'assign_' => array(
                        'match_access' => 'edit_',
                        'match' => 'edit_',
                        'default' => 'edit_posts',
                    ),
                ),
                'default' => 'edit_posts',
            ),
        );
        return apply_filters('wpcf_access_tax_caps', $caps);
    }
    
    /**
     * Maps role to level.
     *
     * Returns an array of roles => levels
     * As this is used a lot of times, we added caching
     * 
     * @return array $map
     */
    public static function wpcf_access_role_to_level_map() 
    {
        $access_cache_map_group = 'access_cache_map_group';
		$access_cache_map_key = md5( 'access::role_to_level_map' );
		$map = wp_cache_get( $access_cache_map_key, $access_cache_map_group );
		if ( false === $map ) {
        
			$default_roles=self::wpcf_get_default_roles();
			
			$map = array(
				'administrator' => 'level_10',
				'editor' => 'level_7',
				'author' => 'level_2',
				'contributor' => 'level_1',
				'subscriber' => 'level_0',
			);
			require_once ABSPATH . '/wp-admin/includes/user.php';
			$roles = self::$roles;
			foreach ($roles as $role => $data) 
			{
				$role_data = get_role($role);
				if (!empty($role_data))
				{
					for ($index = 10; $index > -1; $index--) 
					{
						if (isset($data['capabilities']['level_' . $index])) 
						{
							$map[$role] = 'level_' . $index;
							break;
						}
					}
					// try to deduce the required level
					if (!isset($map[$role]))
					{
						foreach ($default_roles as $r)
						{
							if ($role_data->has_cap($r))
							{
								$map[$role] = $map[$r];
								break;
							}
						}
					}
					// finally use a default here, level_0, subscriber
					if (!isset($map[$role]))
						$map[$role] = 'level_0';
				}
			}
			wp_cache_add( $access_cache_map_key, $map, $access_cache_map_group );
		}
        return $map;
    }

    /**
     * Maps role to level.
     * 
     * @param type $role
     * @return type 
     */
    public static function wpcf_access_role_to_level($role) 
    {
        $map = self::wpcf_access_role_to_level_map();
        return isset($map[$role]) ? $map[$role] : false;
    }

    /**
     * Checks if role is ranked higher.
     * 
     * @param type $role
     * @param type $compare
     * @return boolean 
     */
    public static function wpcf_access_is_role_ranked_higher($role, $compare) 
    {
        if ($role == $compare)
            return true;
        $level_role = self::wpcf_access_role_to_level($role);
        $level_compare = self::wpcf_access_role_to_level($compare);
        return self::wpcf_access_is_level_ranked_higher($level_role, $level_compare);
    }

    /**
     * Checks if level is ranked higher.
     * 
     * @param type $level
     * @param type $compare
     * @return boolean 
     */
    public static function wpcf_access_is_level_ranked_higher($level, $compare) 
    {
        if ($level == $compare) {
            return true;
        }
        $level = strpos($level, 'level_') === 0 ? substr($level, 6) : $level;
        $compare = strpos($compare, 'level_') === 0 ? substr($compare, 6) : $compare;
        return intval($level) > intval($compare);
    }

    /**
     * Orders roles by level.
     * 
     * @param type $roles
     * @return type 
     */
    public static function wpcf_access_order_roles_by_level($roles) 
    {
        $ordered_roles = array();
        for ($index = 10; $index > -1; $index--) {
            foreach ($roles as $role => $data) {
                if (isset($data['capabilities']['level_' . $index])) {
                    $ordered_roles[$index][$role] = $data;
                    unset($roles[$role]);
                }
            }
        }
        $ordered_roles['not_set'] = !empty($roles) ? $roles : array();
        return $ordered_roles;
    }

    /**
     * Gets all caps by level.
     * 
     * Loops over all collected rules and sees each one matches current user.
     * 
     * @global type $wpcf_access
     * @param type $level
     * @param type $context
     * @return type 
     */
    public static function wpcf_access_user_get_caps_by_type($user_id, $context = 'types') 
    {
        global $wpcf_access;
        static $cache = array();
        if (isset($cache[$user_id][$context])) {
            return $cache[$user_id][$context];
        }
        list($role, $level) = self::wpcf_access_rank_user($user_id);
        if (empty($role) || $level === false || empty($wpcf_access->settings->{$context})) {
            return array();
        }
        $caps = array();
        foreach ($wpcf_access->settings->{$context} as $type => $data) {
            if (empty($data['mode']) || 'not_managed'==$data['mode']) continue;
            if (!empty($data['permissions']) && is_array($data['permissions'])) {
                foreach ($data['permissions'] as $_cap => $_data) {
                    if (isset($_data['role'])) {
                        $can = self::wpcf_access_is_level_ranked_higher($level,
                                self::wpcf_access_role_to_level($_data['role']));
                        $cap_data['context'] = $context;
                        $cap_data['parent'] = $type;
                        $cap_data['caps'][$_cap] = (bool) $can;
                        $caps[$type] = $cap_data;
                    }
                }
            }
        }
        $cache[$user_id][$context] = $caps;
        return $caps;
    }

    /**
     * Determines highest ranked role and it's level.
     * 
     * @param type $user_id
     * @param type $rank
     * @return type 
     */
    public static function wpcf_access_rank_user($user_id, $rank = 'high') 
    {
        global $wpcf_access;
        static $cache = array();
        $user = get_userdata($user_id);
        if (empty($user)) {
            $wpcf_access->user_rank['not_found'][$user_id] = array('guest', false);
            return array('guest', false);
        }
        if (!empty($cache[$user_id])) {
            return $cache[$user_id];
        }
        $roles = self::$roles;
        $levels = self::wpcf_access_order_roles_by_level($roles);
        $role = false;
        $level = false;
        foreach ($levels as $_levels => $_level) {
            $current_level = $_levels;
            foreach ($_level as $_role => $_role_data) {
                if (in_array($_role, $user->roles)) {
                    $role = $_role;
                    $level = $current_level;
                    if ($rank != 'low') {
                        $cache[$user_id] = array($role, $level);
                        $wpcf_access->user_rank[$user_id] = $cache[$user_id];
                        return $cache[$user_id];
                    }
                }
            }
        }
        if (!$role || !$level) {
            return array('guest', false);
        }
        $cache[$user_id] = array($role, $level);
        
        $wpcf_access->user_rank[$user_id] = $cache[$user_id];
        
        return array($role, $level);
    }

    /**
     * Search for cap in collected rules.
     * 
     * @global type $wpcf_access
     * @param type $cap
     * @return type 
     */
    public static function wpcf_access_search_cap($cap) 
    {
        global $wpcf_access;
        $settings = array();
        if (isset($wpcf_access->rules->types[$cap])) {
            $settings = $wpcf_access->rules->types[$cap];
            $settings['_context'] = 'types';
        } else if (isset($wpcf_access->rules->tax[$cap])) {
            $settings = $wpcf_access->rules->tax[$cap];
            $settings['_context'] = 'tax';
        }
        return empty($settings) ? false : $settings;
    }

    /**
     * Track fetching editable roles.
     * 
     * Sometimes WP includes get_editable_role func too late.
     * Especially if user is not logged.
     * 
     * @global type $wpcf_access
     * @return type 
     */
    public static function wpcf_get_editable_roles() 
    {
        if ( !is_null( self::$roles ) ) {
            return self::$roles;
        }
        global $wpcf_access;
        if (!function_exists('get_editable_roles')) {
    //        $wpcf_access->errors['editable_roles'] = debug_backtrace();
            include_once ABSPATH . '/wp-admin/includes/user.php';
        }
        if (!function_exists('get_editable_roles')) {
            $wpcf_access->errors['get_editable_roles-missing_func'] = debug_backtrace();
            return array();
        }
        return get_editable_roles();
    }

    /*
    *   Auxilliary function
    */
    public static function wpcf_object_to_array($data, $depth = 1) 
    {
        if ( $depth > 4 ) {
            return array();
        }
        
        if (is_array($data) || is_object($data)) 
        {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = self::wpcf_object_to_array($value, $depth + 1);
            }
            return $result;
        }
        return $data;
    }
    
    /**
     * Check if object (post type or taxonomy) is valid to be managed by Access
     * 
     * @return bool 
     */
    public static function wpcf_is_object_valid( $type, $data ) 
    {
        global $wpcf_access;
        
        if (!in_array($type,array('type','taxonomy')))
            return false;
        
        $data=self::wpcf_object_to_array($data);
        
        // valid for builtin types/taxes as they have predefined caps regardless of locale and labels
        if (isset($data['_builtin']) && $data['_builtin'])
            return true;
            
        $whitelist=array(
            'type'=>array('Media'),
            'taxonomy'=>array()
            );
            
        // no label, bypass
        if (!isset($data) || empty($data) || !isset($data['labels'])) {
            return false;
        } else {
            // same plural and singular names, bypass, else problems (NOTE the actual label to test is menu_name, which by default is equal to (plural) name)
            $singular=$data['labels']['singular_name'];
            $plural=(isset($data['labels']['menu_name'])&&$data['labels']['name']!=$data['labels']['menu_name'])?$data['labels']['menu_name']:$data['labels']['name'];
            
            if ($plural==$singular && !in_array($data['labels']['name'],$whitelist[$type])) {
               // return false;
            }
        }
        return true;
    }

    /**
     * Get extra debug information.
     *
     * Get extra debug information for debug page.
     *
     * @param array debug information table
     *
     * @return array debug information table
     */
    public static function add_access_extra_debug_information($extra_debug)
    {
        global $wpcf_access;
        $clone = clone $wpcf_access;
        $extra_debug['access'] = array();
        foreach( array('rules', 'debug', 'settings', 'errors') as $key ) {
            $extra_debug['access'][$key] = (array)$clone->$key;
        }
        unset($clone);
        return $extra_debug;
    }
    
    public static function wpcf_esc_like( $text ) { 
        global $wpdb; 
        if ( method_exists( $wpdb, 'esc_like' ) ) { 
             return $wpdb->esc_like( $text ); 
        } else { 
             return like_escape( esc_sql( $text ) ); 
        } 
     }
}
