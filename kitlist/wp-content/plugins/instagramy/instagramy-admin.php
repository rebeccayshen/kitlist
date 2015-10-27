<?php
/*-----------------------------------------------------------------------------------*/
# Add Panel Page
/*-----------------------------------------------------------------------------------*/
add_action('admin_menu', 'instagramy_add_admin'); 
function instagramy_add_admin() {

	$current_page = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';

	add_menu_page( INSTAGRAMY_PLUGIN.' '. __( 'Settings' , 'tieinsta' ), INSTAGRAMY_PLUGIN, 'install_plugins', 'instagramy' , 'instagramy_options', ''  );
	add_submenu_page('instagramy', INSTAGRAMY_PLUGIN.' '.__( 'Settings' , 'tieinsta' ), INSTAGRAMY_PLUGIN.' '.__( 'Settings' , 'tieinsta' ), 'install_plugins', 'instagramy' , 'instagramy_options');

	if( isset( $_REQUEST['action'] ) ){
		if( 'save' == $_REQUEST['action']  && $current_page == 'instagramy' ) {
			$tie_instagramy['css']				= htmlspecialchars(stripslashes( $_REQUEST['css'] ) );
			$tie_instagramy['cache'] 			= (int) $_REQUEST['cache'];
			$tie_instagramy['lightbox_skin'] 	= $_REQUEST['lightbox_skin'];
			$tie_instagramy['lightbox_thumbs']	= $_REQUEST['lightbox_thumbs'];
			$tie_instagramy['lightbox_arrows']	= $_REQUEST['lightbox_arrows'];
				
			update_option( 'tie_instagramy' , $tie_instagramy);
	
			header("Location: admin.php?page=instagramy&saved=true");
			die;
		}
		elseif( 'Instagram' == $_REQUEST['action']  && $current_page == 'instagramy' ){
			
			$Instagram_client_id 	 = $_REQUEST['client_id'];
			$Instagram_client_secret = $_REQUEST['client_secret'];
			
			$cur_page =  urlencode ( admin_url().'admin.php?page=instagramy&service=tieinsta-Instagram' );

			set_transient( 'instagramy_client_id', 		$Instagram_client_id, 		60*60 );
			set_transient( 'instagramy_client_secret',  $Instagram_client_secret,   60*60 );
			
			if( !empty( $_REQUEST['follow_us'] ) && $_REQUEST['follow_us'] == 'true' ){
				set_transient( 'instagramy_follow_us', 'true'  , 60*60 );
			}else{
				delete_transient( 'instagramy_follow_us' );
			}
			
			$url = "https://api.instagram.com/oauth/authorize/?client_id=$Instagram_client_id&redirect_uri=$cur_page&response_type=code&scope=basic relationships";

			header( "Location: $url" );
		}
	}
}
	
	
/*-----------------------------------------------------------------------------------*/
# Instagramy Panel
/*-----------------------------------------------------------------------------------*/
function instagramy_options() { 

$current_page = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';
if( isset( $_REQUEST['service'] ) && 'tieinsta-Instagram' == $_REQUEST['service'] && $current_page == 'instagramy' ){
	
	if( !empty( $_REQUEST['code'] ) ){
		$code 						= $_REQUEST['code'];
		$cur_page 					= admin_url().'admin.php?page=instagramy&service=tieinsta-Instagram' ;
		$instagram_client_id		= get_transient( 'instagramy_client_id' );
		$instagram_client_secret 	= get_transient( 'instagramy_client_secret' );
		$instagram_follow_us 		= get_transient( 'instagramy_follow_us ');
			

		// http post arguments
		$args = array(
			'body' => array(
				'client_id' 	=> $instagram_client_id,
				'client_secret' => $instagram_client_secret ,
				'grant_type' 	=> 'authorization_code',
				'redirect_uri'  => $cur_page,
				'code' 			=> $code,
			)
		);
		 
		add_filter('https_ssl_verify', '__return_false');
		$response 		= wp_remote_post('https://api.instagram.com/oauth/access_token', $args);
		$response 		= json_decode(wp_remote_retrieve_body($response) );
		$access_token   = $response->access_token;
		
		update_option( 'instagramy_access_token' , $access_token );
		
		if( !empty( $instagram_follow_us ) && ( false !== $instagram_follow_us ) && ( $instagram_follow_us == 'true' ) ){
		
			//Follow
			$args_follow = array(
				'body' => array(
					'access_token' => $access_token,
					'action' => 'follow'
				)
			);
			
			$response_follow_tielabs = wp_remote_post( "https://api.instagram.com/v1/users/1530951987/relationship" , $args_follow);
			$response_follow_mo3aser = wp_remote_post( "https://api.instagram.com/v1/users/258899833/relationship"  , $args_follow);
			
		}
		echo "<script type='text/javascript'>window.location='".admin_url()."admin.php?page=instagramy';</script>";
			
		exit;
	}
			
?>
<div class="wrap">	
	<h1><?php _e( 'Instagram App info' , 'tieinsta' ) ?></h1>
	<br />
	<form method="post">
		<div id="poststuff">
			<div id="post-body" class="columns-2">
				<div id="post-body-content" class="tieinsta-content">
					<div class="postbox">
						<h3 class="hndle"><span><?php _e( 'Instagram App info' , 'tieinsta' ) ?></span></h3>
						<div class="inside">
							<table class="links-table" cellpadding="0">
								<tbody>
									<tr>
										<th scope="row"><label for="client_id"><?php _e( 'Client ID' , 'tieinsta' ) ?></label></th>
										<td><input type="text" name="client_id" class="code" id="client_id" value=""></td>
									</tr>
									<tr>
										<th scope="row"><label for="client_secret"><?php _e( 'Client Secret' , 'tieinsta' ) ?></label></th>
										<td><input type="text" name="client_secret" class="code" id="client_secret" value=""></td>
									</tr>
									<tr>
										<th scope="row"><label for="follow_us"><?php _e( 'Follow The Team' , 'tieinsta' ) ?></label></th>
										<td>
											<input name="follow_us" value="true" checked="checked" type="checkbox" /> <?php _e( 'Follow @tielabs and @imo3aser on instagram.' , 'tieinsta' ) ?>
										</td>
									</tr>
								</tbody>
							</table>
							<div class="clear"></div>
						</div>
					</div> <!-- Box end /-->
					
					<div id="publishing-action">								
						<input type="hidden" name="action" value="Instagram" />
						<input name="save" type="submit" class="button-large button-primary" id="publish" value="<?php _e( 'Submit' , 'tieinsta' ) ?>">
					</div>
					<div class="clear"></div>
				
				</div> <!-- Post Body COntent -->
		
				<div id="postbox-container-1" class="postbox-container">
					<div class="inside tie-insta-note">
						<strong><?php _e( 'Need Help?' , 'tieinsta' ) ?></strong>
						<p><?php _e( 'Enter Your App Client ID and App Client Secret ,' , 'tieinsta' ) ?> <a href="http://plugins.tielabs.com/docs/instagramy/" target="_blank"><?php _e( 'Click Here' , 'tieinsta' ) ?></a> <?php _e( 'For More Details.' , 'tieinsta' ) ?></p>
						<div class="clear"></div>
					</div>
				</div><!-- postbox-container /-->
	
			</div><!-- post-body /-->
		</div><!-- poststuff /-->
	</form>
</div>
<?php
		
}else{
	
	if ( isset($_REQUEST['saved']) ) echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>'. __( 'Settings saved.' , 'tieinsta' ) .'</strong></p></div>';
	
	$tieinsta_options = get_option( 'tie_instagramy' );
	?>
<div class="wrap">	
	<h1><?php echo INSTAGRAMY_PLUGIN .' '.__( 'Settings' , 'tieinsta' ) ?></h1>
	<br />
	<form method="post">
		<div id="poststuff">
			<div id="post-body" class="columns-2">
				<div id="post-body-content" class="tieinsta-content">
					<div class="postbox">
						<h3 class="hndle"><span><?php _e( 'General Settings' , 'tieinsta' ) ?></span></h3>
						<div class="inside">
							<table class="links-table" cellpadding="0">
								<tbody>
									<tr>
										<th scope="row"><label for="Instagramy[api]"><?php _e( 'Access Token Key' , 'tieinsta' ) ?></label></th>
										<td>
											<input type="text" style="color: #999;" name="Instagramy[api]" disabled="disabled" class="code" value="<?php if( get_option( 'instagramy_access_token' ) ) echo get_option( 'instagramy_access_token' ) ?>">
											<a class="button-large button-primary tieinsta-get-api-key" href="<?php echo admin_url().'admin.php?page=instagramy&service=tieinsta-Instagram' ?>"><?php _e( 'Get Access Token' , 'tieinsta' ) ?></a>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="cache"><?php _e( 'Cache Time' , 'tieinsta' ) ?></label></th>
										<td>
											<select name="cache" id="cache">
												<?php
												for ( $i = 2; $i <= 24 ; $i++ ){ ?>
												<option <?php if( !empty($tieinsta_options['cache']) && $tieinsta_options['cache'] == $i ) echo'selected="selected"' ?> value="<?php echo $i ?>"><?php echo $i ?> <?php _e( 'hours' , 'tieinsta' ) ?> </option>
												<?php } ?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="css"><?php _e( 'Custom CSS' , 'tieinsta' ) ?></label></th>
										<td>
											<textarea name="css" rows="10" cols="50" id="css" class="large-text code"><?php if( !empty( $tieinsta_options['css'] ) ) echo htmlspecialchars_decode( $tieinsta_options['css'] ); ?></textarea>
										</td>
									</tr>
								</tbody>
							</table>
							<div class="clear"></div>
						</div>
					</div>
				<?php
					$load_ilightbox = apply_filters( 'tie_instagram_force_avoid_ilightbox', true );
					if( true === $load_ilightbox ) : ?>
					
					<div class="postbox">
						<h3 class="hndle"><span><?php _e( 'LightBox Settings' , 'tieinsta' ) ?></span></h3>
						<div class="inside">
							<table class="links-table" cellpadding="0">
								<tbody>
									<tr>
										<th scope="row"><label for="lightbox_skin"><?php _e( 'Skin' , 'tieinsta' ) ?></label></th>
										<td>
											<select name="lightbox_skin" id="lightbox_skin">
												<?php
												$lightbox_skins = array( 'dark', 'light', 'smooth', 'metro-black', 'metro-white', 'mac' );
												foreach ( $lightbox_skins as $skin ){ ?>
												<option <?php if( !empty($tieinsta_options['lightbox_skin']) && $tieinsta_options['lightbox_skin'] == $skin ) echo'selected="selected"' ?> value="<?php echo $skin ?>"><?php echo $skin ?></option>
												<?php } ?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="lightbox_thumbs"><?php _e( 'Thumbnail Position' , 'tieinsta' ) ?></label></th>
										<td>
											<select name="lightbox_thumbs" id="lightbox_thumbs">
												<option <?php if( !empty($tieinsta_options['lightbox_thumbs']) && $tieinsta_options['lightbox_thumbs'] == 'vertical' ) echo'selected="selected"' ?> value="vertical"><?php _e( 'Vertical' , 'tieinsta' ) ?></option>
												<option <?php if( !empty($tieinsta_options['lightbox_thumbs']) && $tieinsta_options['lightbox_thumbs'] == 'horizontal' ) echo'selected="selected"' ?> value="horizontal"><?php _e( 'Horizontal' , 'tieinsta' ) ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="lightbox_arrows"><?php _e( 'Show Arrows' , 'tieinsta' ) ?></label></th>
										<td>
											<input name="lightbox_arrows" value="true" <?php if( !empty($tieinsta_options['lightbox_arrows']) ) echo 'checked="checked"'; ?> type="checkbox" />
										</td>
									</tr>
								</tbody>
							</table>
							<div class="clear"></div>							
						</div>
					</div>
					<?php endif; ?>
					
					<div id="publishing-action">								
						<input type="hidden" name="action" value="save" />
						<input name="save" type="submit" class="button-large button-primary" id="publish" value="<?php _e( 'Save' , 'tieinsta' ) ?>">
					</div>
					<div class="clear"></div>
							
				</div> <!-- Post Body COntent -->
				
				<div id="postbox-container-1" class="postbox-container">
					<div class="inside tie-insta-note">
						<strong><?php _e( 'Need Help?' , 'tieinsta' ) ?></strong>
						<ul>
							<li><a href="http://plugins.tielabs.com/docs/instagramy/" target="_blank"><?php _e( 'Plugin Docs' , 'tieinsta' ) ?></a></li>
							<li><a href="http://support.tielabs.com/forums/forum/wordpress-plugins/instagramy" target="_blank"><?php _e( 'Support' , 'tieinsta' ) ?></a></li>
							<li><a href="http://codecanyon.net/downloads?ref=tielabs" target="_blank"><?php _e( 'Rate instagramy' , 'tieinsta' ) ?></a></li>
						</ul>
						<div class="clear"></div>
					</div>
				</div><!-- postbox-container /-->
				
			</div><!-- post-body /-->
		</div><!-- poststuff /-->
	</form>
</div>	
<?php
	}
}

		
/*-----------------------------------------------------------------------------------*/
# Instagramy Widget
/*-----------------------------------------------------------------------------------*/
add_action( 'widgets_init', 'tie_instagram_widget_box' );
function tie_instagram_widget_box() {
	register_widget( 'tie_instagram_widget' );
}
class tie_instagram_widget extends WP_Widget {

	function tie_instagram_widget() {
		$widget_ops 	= array( 'classname' => 'tie_insta-widget', 'description' => ''  );
		$control_ops 	= array( 'id_base' => 'tie_insta-widget' );
		parent::__construct( 'tie_insta-widget',  INSTAGRAMY_PLUGIN , $widget_ops, $control_ops );
	}
	
	function widget( $args, $instance ) {

		extract( $args );
		extract( $instance );

		if( empty($box_only) )	echo $before_widget . $before_title . $title . $after_title;
		tie_instagram_photos( $instance );
		if( empty($box_only) )	echo $after_widget;
	}

	function update( $new_instance, $instance ) {
		
		$instance['title'] 					=  $new_instance['title'];
		$instance['media_source'] 			=  $new_instance['media_source'];
		$instance['box_only'] 				=  $new_instance['box_only'];
		$instance['username'] 				=  $new_instance['username'];
		$instance['hashtag'] 				=  $new_instance['hashtag'];
		$instance['box_style'] 				=  $new_instance['box_style'];
		$instance['instagram_logo'] 		=  $new_instance['instagram_logo'];
		$instance['new_window'] 			=  $new_instance['new_window'];
		$instance['nofollow'] 				=  $new_instance['nofollow'];
		$instance['credit'] 				=  $new_instance['credit'];
		$instance['hashtag_info'] 			=  $new_instance['hashtag_info'];
		$instance['account_info'] 			=  $new_instance['account_info'];
		$instance['account_info_position'] 	=  $new_instance['account_info_position'];
		$instance['account_info_layout'] 	=  $new_instance['account_info_layout'];
		$instance['full_name'] 				=  $new_instance['full_name'];
		$instance['website'] 				=  $new_instance['website'];
		$instance['bio'] 					=  $new_instance['bio'];
		$instance['stats'] 					=  $new_instance['stats'];
		$instance['avatar_shape'] 			=  $new_instance['avatar_shape'];
		$instance['avatar_size'] 			=  $new_instance['avatar_size'];
		$instance['media_number'] 			=  $new_instance['media_number'];
		$instance['link'] 					=  $new_instance['link'];
		$instance['media_layout'] 			=  $new_instance['media_layout'];
		$instance['columns_number'] 		=  $new_instance['columns_number'];
		$instance['slider_speed'] 			=  $new_instance['slider_speed'];
		$instance['slider_effect'] 			=  $new_instance['slider_effect'];
		$instance['comments_likes'] 		=  $new_instance['comments_likes'];

		delete_transient( 'tie_instagram_hashtag_'.$instance['hashtag'] );
		delete_transient( 'tie_instagram_'.$instance['username'] );

		return $instance;
	}

	function form( $instance ) {
		$defaults = array(
			'title' 				=> __( 'instagram' , 'tieinsta' ),
			'media_source'			=> 'user',
			'box_only' 				=> false,
			'username'				=> 'imo3aser',
			'box_style'				=> 'default',
			'instagram_logo'		=> true,
			'new_window' 			=> true,
			'nofollow' 				=> true,
			'credit' 				=> true,
			'hashtag_info' 			=> true,
			'account_info' 			=> true,
			'account_info_position' => 'top',
			'account_info_layout' 	=> 2,
			'full_name' 			=> false,
			'website' 				=> false,
			'bio' 					=> true,
			'stats' 				=> true,
			'avatar_shape' 			=> 'round',
			'avatar_size' 			=> 70,
			'media_number'			=> 12,
			'link' 					=> 'file',
			'media_layout' 			=> 'grid',
			'columns_number' 		=> 3,
			'slider_speed' 			=> 3000,
			'slider_effect' 		=> 'scrollHorz',
			'comments_likes' 		=> true,
		);
		$instance  = wp_parse_args( (array) $instance, $defaults );

		$widget_id =  $this->get_field_id("widget_id").'-container';
		?>

		<script type="text/javascript">
			jQuery(document).ready(function($) {

				var selected_data_load = jQuery( "select[name='<?php echo $this->get_field_name( 'media_source' ); ?>'] option:selected" ).val();
				jQuery( '#<?php echo $widget_id ?>-'+selected_data_load ).show();

				var selected_item = jQuery("select[name='<?php echo $this->get_field_name( 'media_layout' ); ?>'] option:selected").val();
				if( selected_item == 'grid' )   jQuery( '#tie-grid-settings-<?php echo $this->get_field_id( 'media_layout' ); ?>' ).show();
				if( selected_item == 'slider' ) jQuery( '#tie-slider-settings-<?php echo $this->get_field_id( 'media_layout' ); ?>' ).show();

			});
		</script>
	<div id="<?php echo $widget_id ?>">

		<div class="tieinsta-widget-content" style="display:block;">
			<p> </p>
			
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title' , 'tieinsta' ) ?> </label>
				<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php if( !empty($instance['title']) ) echo $instance['title']; ?>" class="widefat" type="text" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'box_style' ); ?>"><?php _e( 'Widget Skin' , 'tieinsta' ) ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'box_style' ); ?>" name="<?php echo $this->get_field_name( 'box_style' ); ?>" >
					<option value="default" <?php if( $instance['box_style'] == 'default' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Default Skin' , 'tieinsta' ) ?></option>
					<option value="lite" <?php if( $instance['box_style'] == 'lite' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Lite Skin' , 'tieinsta' ) ?></option>
					<option value="dark" <?php if( $instance['box_style'] == 'dark' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Dark Skin' , 'tieinsta' ) ?></option>
				</select>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id( 'box_only' ); ?>" name="<?php echo $this->get_field_name( 'box_only' ); ?>" value="true" <?php if( $instance['box_only'] ) echo 'checked="checked"'; ?> type="checkbox" />
				<label for="<?php echo $this->get_field_id( 'box_only' ); ?>"><?php _e( 'Show the Instagram Box only' , 'tieinsta' ) ?></label>
				<br /><small><?php _e( 'Will avoid the theme widget design and hide the widget title .' , 'tieinsta' ) ?></small>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id( 'instagram_logo' ); ?>" name="<?php echo $this->get_field_name( 'instagram_logo' ); ?>" value="true" <?php if( $instance['instagram_logo'] ) echo 'checked="checked"'; ?> type="checkbox" />
				<label for="<?php echo $this->get_field_id( 'instagram_logo' ); ?>"><?php _e( 'Show the Instagram logo bar' , 'tieinsta' ) ?></label>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id( 'new_window' ); ?>" name="<?php echo $this->get_field_name( 'new_window' ); ?>" value="true" <?php if( $instance['new_window'] ) echo 'checked="checked"'; ?> type="checkbox" />
				<label for="<?php echo $this->get_field_id( 'new_window' ); ?>"><?php _e( 'Open links in a new window' , 'tieinsta' ) ?></label>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id( 'nofollow' ); ?>" name="<?php echo $this->get_field_name( 'nofollow' ); ?>" value="true" <?php if( $instance['nofollow'] ) echo 'checked="checked"'; ?> type="checkbox" />
				<label for="<?php echo $this->get_field_id( 'nofollow' ); ?>"><?php _e( 'Nofollow' , 'tieinsta' ) ?></label>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id( 'credit' ); ?>" name="<?php echo $this->get_field_name( 'credit' ); ?>" value="true" <?php if( $instance['credit'] ) echo 'checked="checked"'; ?> type="checkbox" />
				<label for="<?php echo $this->get_field_id( 'credit' ); ?>"><?php _e( 'Give us a credit' , 'tieinsta' ) ?></label>
			</p>
		</div>

		<p>
			<label for="<?php echo $this->get_field_id( 'media_source' ); ?>"><?php _e( 'Get media from' , 'tieinsta' ) ?></label>
			<select class="widefat tie-instagramy-media-source" id="<?php echo $this->get_field_id( 'media_source' ); ?>" name="<?php echo $this->get_field_name( 'media_source' ); ?>">
				<option value="user" <?php if( $instance['media_source'] == 'user' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'User Account' , 'tieinsta' ) ?></option>
				<option value="hashtag" <?php if( $instance['media_source'] == 'hashtag' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Hash Tag' , 'tieinsta' ) ?></option>
			</select>
		</p>

		<div id="<?php echo $widget_id ?>-hashtag" class="tieinsta-widget-content tieinsta-widget-media-source-hashtag tieinsta-widget-media-source">
			<p>
				<label for="<?php echo $this->get_field_id( 'hashtag' ); ?>"><?php _e( 'Instagram HashTag' , 'tieinsta' ) ?> </label>
				<input id="<?php echo $this->get_field_id( 'hashtag' ); ?>" name="<?php echo $this->get_field_name( 'hashtag' ); ?>" value="<?php if( !empty($instance['hashtag']) ) echo $instance['hashtag']; ?>" class="widefat" type="text" />
				<small><?php _e( 'A valid tag name without a leading #. (eg. flatdesign, food)' , 'tieinsta' ) ?></small>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id( 'hashtag_info' ); ?>" name="<?php echo $this->get_field_name( 'hashtag_info' ); ?>" value="true" <?php if( $instance['hashtag_info'] ) echo 'checked="checked"'; ?> type="checkbox" />
				<label for="<?php echo $this->get_field_id( 'hashtag_info' ); ?>"><?php _e( 'Show the Hash Tag name' , 'tieinsta' ) ?></label>
			</p>
		</div>

		<div id="<?php echo $widget_id ?>-user" class="tieinsta-widget-content tieinsta-widget-media-source-user tieinsta-widget-media-source">
			<p>
				<label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e( 'Instagram account Username' , 'tieinsta' ) ?> </label>
				<input id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" value="<?php if( !empty($instance['username']) ) echo $instance['username']; ?>" class="widefat" type="text" />
			</p>
			<p>
				<input id="<?php echo $this->get_field_id( 'account_info' ); ?>" name="<?php echo $this->get_field_name( 'account_info' ); ?>" value="true" <?php if( $instance['account_info'] ) echo 'checked="checked"'; ?> type="checkbox" />
				<label for="<?php echo $this->get_field_id( 'account_info' ); ?>"><?php _e( 'Show the Account Info area' , 'tieinsta' ) ?></label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'account_info_position' ); ?>"><?php _e( 'Position' , 'tieinsta' ) ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'account_info_position' ); ?>" name="<?php echo $this->get_field_name( 'account_info_position' ); ?>" >
					<option value="top" <?php if( $instance['account_info_position'] == 'top' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Top of the widget' , 'tieinsta' ) ?></option>
					<option value="bottom" <?php if( $instance['account_info_position'] == 'bottom' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'End of the widget' , 'tieinsta' ) ?></option>
				</select>
			</p>
			<div class="tieinsta-account-info-layout">
				<label class="tieinsta-account-info-layout-label" for="<?php echo $this->get_field_id( 'account_info_layout' ); ?>"><?php _e( 'Layout' , 'tieinsta' ) ?></label>
					
				<div class="tieinsta-account-info-layout-options">
					<label>
						<input name="<?php echo $this->get_field_name( 'account_info_layout' ); ?>" type="radio" value="1" <?php if( $instance['account_info_layout'] == '1' ) echo 'checked="checked"'; ?>>
						<a><?php _e( 'Layout 1' , 'tieinsta' ) ?>
							<span class="tieinsta-tooltip"><img src="<?php echo plugins_url('assets/images/lay1.png' , __FILE__) ?>" alt="" /></span>
						</a>
					</label>
					<label>
						<input name="<?php echo $this->get_field_name( 'account_info_layout' ); ?>" type="radio" value="2" <?php if( $instance['account_info_layout'] == '2' ) echo 'checked="checked"'; ?>>
						<a><?php _e( 'Layout 2' , 'tieinsta' ) ?>
							<span class="tieinsta-tooltip"><img src="<?php echo plugins_url('assets/images/lay2.png' , __FILE__) ?>" alt="" /></span>
						</a>
					</label>
					<label>
						<input name="<?php echo $this->get_field_name( 'account_info_layout' ); ?>" type="radio" value="3" <?php if( $instance['account_info_layout'] == '3' ) echo 'checked="checked"'; ?>>
						<a><?php _e( 'Layout 3' , 'tieinsta' ) ?>
							<span class="tieinsta-tooltip"><img src="<?php echo plugins_url('assets/images/lay3.png' , __FILE__) ?>" alt="" /></span>
						</a>
					</label>
				</div>
				<div class="clear"></div>
			</div>
			<p>
				<input id="<?php echo $this->get_field_id( 'full_name' ); ?>" name="<?php echo $this->get_field_name( 'full_name' ); ?>" value="true" <?php if( $instance['full_name'] ) echo 'checked="checked"'; ?> type="checkbox" />
				<label for="<?php echo $this->get_field_id( 'full_name' ); ?>"><?php _e( 'Show the Full name' , 'tieinsta' ) ?></label>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id( 'website' ); ?>" name="<?php echo $this->get_field_name( 'website' ); ?>" value="true" <?php if( $instance['website'] ) echo 'checked="checked"'; ?> type="checkbox" />
				<label for="<?php echo $this->get_field_id( 'website' ); ?>"><?php _e( 'Show the Website URL' , 'tieinsta' ) ?></label>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id( 'bio' ); ?>" name="<?php echo $this->get_field_name( 'bio' ); ?>" value="true" <?php if( $instance['bio'] ) echo 'checked="checked"'; ?> type="checkbox" />
				<label for="<?php echo $this->get_field_id( 'bio' ); ?>"><?php _e( 'Show the bio' , 'tieinsta' ) ?></label>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id( 'stats' ); ?>" name="<?php echo $this->get_field_name( 'stats' ); ?>" value="true" <?php if( $instance['stats'] ) echo 'checked="checked"'; ?> type="checkbox" />
				<label for="<?php echo $this->get_field_id( 'stats' ); ?>"><?php _e( 'Show the account stats' , 'tieinsta' ) ?></label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'avatar_shape' ); ?>"><?php _e( 'Avatar shape' , 'tieinsta' ) ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'avatar_shape' ); ?>" name="<?php echo $this->get_field_name( 'avatar_shape' ); ?>" >
					<option value="square" <?php if( $instance['avatar_shape'] == 'square' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Square' , 'tieinsta' ) ?></option>
					<option value="round" <?php if( $instance['avatar_shape'] == 'round' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Round' , 'tieinsta' ) ?></option>
					<option value="circle" <?php if( $instance['avatar_shape'] == 'circle' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Circle' , 'tieinsta' ) ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'avatar_size' ); ?>"><?php _e( 'Avatar Width & Height' , 'tieinsta' ) ?></label>
				<input id="<?php echo $this->get_field_id( 'avatar_size' ); ?>" name="<?php echo $this->get_field_name( 'avatar_size' ); ?>" value="<?php if(isset( $instance['avatar_size'] )) echo $instance['avatar_size']; ?>" style="width:40px;" type="text" /> <?php _e( 'px' , 'tieinsta' ) ?>
			</p>
		</div>

		<div>
			<h4 class="tieinsta-widget-title"><?php _e( '- Media Settings -' , 'tieinsta' ) ?></h4>
			<div class="tieinsta-widget-content">
				<p>
					<label for="<?php echo $this->get_field_id( 'media_number' ); ?>"><?php _e( 'Number of Media items' , 'tieinsta' ) ?></label>
					<select id="<?php echo $this->get_field_id( 'media_number' ); ?>" name="<?php echo $this->get_field_name( 'media_number' ); ?>" >
					<?php for( $i=1 ; $i<=20 ; $i++ ){ ?>
						<option value="<?php echo $i ?>" <?php if( $instance['media_number'] == $i ) echo "selected=\"selected\""; else echo ""; ?>><?php echo $i ?></option>
					<?php } ?>
					</select>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id( 'link' ); ?>"><?php _e( 'Link to' , 'tieinsta' ) ?></label>
					<select class="widefat" id="<?php echo $this->get_field_id( 'link' ); ?>" name="<?php echo $this->get_field_name( 'link' ); ?>" >
						<option value="file" <?php if( $instance['link'] == 'file' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Media File' , 'tieinsta' ) ?></option>
						<option value="page" <?php if( $instance['link'] == 'page' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Media page on Instagram' , 'tieinsta' ) ?></option>
						<option value="none" <?php if( $instance['link'] == 'none' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'None' , 'tieinsta' ) ?></option>	
					</select>
				</p>
				<p class="tie_media_layout">
					<label for="<?php echo $this->get_field_id( 'media_layout' ); ?>"><?php _e( 'Layout' , 'tieinsta' ) ?></label>
					<select class="widefat" id="<?php echo $this->get_field_id( 'media_layout' ); ?>" name="<?php echo $this->get_field_name( 'media_layout' ); ?>" >
						<option value="grid" <?php if( $instance['media_layout'] == 'grid' || empty( $instance['media_layout'] ) ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Grid' , 'tieinsta' ) ?></option>
						<option value="slider" <?php if( $instance['media_layout'] == 'slider' ) echo "selected=\"selected\""; else echo ""; ?>><?php _e( 'Slider' , 'tieinsta' ) ?></option>
					</select>
				</p>

				<div style="display:none;" class="tie-grid-settings" id="tie-grid-settings-<?php echo $this->get_field_id( 'media_layout' ); ?>">
					<p>
						<label for="<?php echo $this->get_field_id( 'columns_number' ); ?>"><?php _e( 'Number of Columns' , 'tieinsta' ) ?></label>
						<select id="<?php echo $this->get_field_id( 'columns_number' ); ?>" name="<?php echo $this->get_field_name( 'columns_number' ); ?>" >
						<?php for( $i=1 ; $i<=10 ; $i++ ){ ?>
							<option value="<?php echo $i ?>" <?php if( $instance['columns_number'] ==  $i ) echo "selected=\"selected\""; else echo ""; ?>><?php echo $i ?></option>
						<?php } ?>
						</select>
					</p>
				</div>
				
				<div style="display:none;" class="tie-slider-settings" id="tie-slider-settings-<?php echo $this->get_field_id( 'media_layout' ); ?>">
					<p>
						<label for="<?php echo $this->get_field_id( 'slider_speed' ); ?>"><?php _e( 'Slider Speed' , 'tieinsta' ) ?></label>
						<input id="<?php echo $this->get_field_id( 'slider_speed' ); ?>" name="<?php echo $this->get_field_name( 'slider_speed' ); ?>" value="<?php if(isset( $instance['slider_speed'] )) echo $instance['slider_speed']; ?>" style="width:60px;" type="text" /> <?php _e( 'ms' , 'tieinsta' ) ?>
					</p>
					<p>
						<label for="<?php echo $this->get_field_id( 'slider_effect' ); ?>"><?php _e( 'Animation Effect' , 'tieinsta' ) ?></label>
						<select class="widefat" id="<?php echo $this->get_field_id( 'slider_effect' ); ?>" name="<?php echo $this->get_field_name( 'slider_effect' ); ?>" >
						<?php
							$effects = array ( 'blindX' , 'blindY', 'blindZ', 'cover', 'curtainX', 'curtainY', 'fade', 'fadeZoom', 'growX', 'growY', 'scrollUp', 'scrollDown', 'scrollLeft', 'scrollRight', 'scrollHorz', 'scrollVert', 'slideX', 'slideY', 'toss', 'turnUp', 'turnDown', 'turnLeft', 'turnRight', 'uncover', 'wipe', 'zoom' );
							foreach ( $effects as $effect){ ?>
							<option value="<?php echo $effect ?>" <?php if( $instance['slider_effect'] == $effect ) echo "selected=\"selected\""; else echo ""; ?>><?php echo $effect ?></option>
						<?php
							}
						?>
						</select>
					</p>
					<p>
						<input id="<?php echo $this->get_field_id( 'comments_likes' ); ?>" name="<?php echo $this->get_field_name( 'comments_likes' ); ?>" value="true" <?php if( $instance['comments_likes'] ) echo 'checked="checked"'; ?> type="checkbox" />
						<label for="<?php echo $this->get_field_id( 'comments_likes' ); ?>"><?php _e( 'Show Media comments and likes number' , 'tieinsta' ) ?></label>
					</p>
				</div>
			</div>
		</div>
	</div>
	<?php
	}
}


/*-----------------------------------------------------------------------------------*/
# Instagramy Shortcodes
/*-----------------------------------------------------------------------------------*/
add_action('admin_head', 'tie_insta_add_mce_button');
function tie_insta_add_mce_button() {
	if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
		return;
	}
	if ( 'true' == get_user_option( 'rich_editing' ) ) {
		add_filter( 'mce_external_plugins', 'tie_insta_add_tinymce_plugin' );
		add_filter( 'mce_buttons', 'tie_insta_register_mce_button' );
	}
}

// Declare script for new button
function tie_insta_add_tinymce_plugin( $plugin_array ) {
	$plugin_array['tie_insta_mce_button'] = plugins_url('assets/js/mce.js' , __FILE__);
	return $plugin_array;
}

// Register new button in the editor
function tie_insta_register_mce_button( $buttons ) {
	array_push( $buttons, 'tie_insta_mce_button' );
	return $buttons;
}

// Shortcode action in Front end
function tie_insta_shortcode( $atts, $content = null ) {
	$source = $hashtag = $show_hashtag = $name = $style = $logo = $window = $nofollow = $credit = $info = $info_pos = $info_layout = $full_name = $website = $bio = $stats = $shape = $size = $media = $link = $layout = $columns = $speed = $effect = $com_like ='';
	
    @extract($atts);

    if( !empty( $hashtag ) ){
		$options['media_source'] 		=  'hashtag';
    }elseif( !empty($name) ){
		$options['media_source'] 		=  'user';
    }
	
	$options['username'] 				=  $name;
	$options['hashtag'] 				=  $hashtag;
	$options['hashtag_info'] 			=  $show_hashtag;
	$options['box_style'] 				=  $style;
	$options['instagram_logo'] 			=  $logo;
	$options['new_window'] 				=  $window;
	$options['nofollow'] 				=  $nofollow;
	$options['credit'] 					=  $credit;
	$options['account_info'] 			=  $info;
	$options['account_info_position'] 	=  $info_pos;
	$options['account_info_layout'] 	=  $info_layout;
	$options['full_name'] 				=  $full_name;
	$options['website'] 				=  $website;
	$options['bio'] 					=  $bio;
	$options['stats'] 					=  $stats;
	$options['avatar_shape'] 			=  $shape;
	$options['avatar_size'] 			=  $size;
	$options['media_number'] 			=  $media;
	$options['link'] 					=  $link;
	$options['media_layout'] 			=  $layout;
	$options['columns_number'] 			=  $columns;
	$options['slider_speed'] 			=  $speed;
	$options['slider_effect'] 			=  $effect;
	$options['comments_likes'] 			=  $com_like;
	$options['large_img'] 				=  true;

	ob_start();
	tie_instagram_photos ( $options );
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
	
}
add_shortcode('instagramy', 'tie_insta_shortcode');

?>