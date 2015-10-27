<?php
if (!defined('ABSPATH'))  die('Security check');
if(!current_user_can(CRED_CAPABILITY)) {
	die('Access Denied');
}

$_url=admin_url('admin.php').'?page=CRED_Form';

global $action, $typenow, $post, $post_ID, $user_ID, $action, $post_type, $post_type_object, $editing, $active_post_lock;
global $title, $hook_suffix, $current_screen, $wp_locale, $pagenow, $wp_version,
	$current_site, $update_title, $total_update_count, $parent_file;

$GLOBALS['post']=$post;

//cred_log($GLOBALS['post']);
/**
 * Post ID global
 * @name $post_ID
 * @var int
 */
$post_ID = isset($post_ID) ? (int) $post_ID : 0;
$user_ID = isset($user_ID) ? (int) $user_ID : 0;
$action = isset($action) ? $action : '';

$messages = array();
$messages['post'] = array(
	 0 => '', // Unused. Messages start at index 1.
	 1 => sprintf( __('Post updated. <a href="%s">View post</a>'), esc_url( get_permalink($post_ID) ) ),
	 2 => __('Custom field updated.'),
	 3 => __('Custom field deleted.'),
	 4 => __('Post updated.'),
	/* translators: %s: date and time of the revision */
	 5 => isset($_GET['revision']) ? sprintf( __('Post restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	 6 => sprintf( __('Post published. <a href="%s">View post</a>'), esc_url( get_permalink($post_ID) ) ),
	 7 => __('Post saved.'),
	 8 => sprintf( __('Post submitted. <a target="_blank" href="%s">Preview post</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	 9 => sprintf( __('Post scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview post</a>'),
		// translators: Publish box date format, see http://php.net/date
		date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
	10 => sprintf( __('Post draft updated. <a target="_blank" href="%s">Preview post</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
);

$message = false;
/*if ( isset($_GET['message']) ) {
	$_GET['message'] = absint( $_GET['message'] );
	if ( isset($messages[$post_type][$_GET['message']]) )
		$message = $messages[$post_type][$_GET['message']];
	elseif ( !isset($messages[$post_type]) && isset($messages['post'][$_GET['message']]) )
		$message = $messages['post'][$_GET['message']];
}*/
$notice = false;
$form_extra = '';

$form_action = 'editpost';
$nonce_action = 'update-post_' . $post_ID;
$form_extra .= "<input type='hidden' id='post_ID' name='post_ID' value='" . esc_attr($post_ID) . "' />";

$post_type_object = get_post_type_object($post_type);

// All meta boxes should be defined and added before the first do_meta_boxes() call (or potentially during the do_meta_boxes action).
//include ABSPATH.'/wp-admin/includes/meta-boxes.php';

//do_action('add_meta_boxes', $post_type, $post);
//do_action('add_meta_boxes_' . $post_type, $post);
do_action('add_cred_meta_boxes', $post);
/*do_action('do_cred_meta_boxes', 'normal', $post);
do_action('do_cred_meta_boxes', 'advanced', $post);
do_action('do_cred_meta_boxes', 'side', $post);*/

add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
?>
<div class="wrap">
<?php screen_icon('cred-frontend-editor'); ?>
<h2><?php
echo esc_html( $title );
if ( isset( $post_new_file ) && current_user_can( $post_type_object->cap->create_posts ) )
	echo ' <a href="' . esc_url( $post_new_file ) . '" class="add-new-h2">' . esc_html( $post_type_object->labels->add_new ) . '</a>';
?></h2>

<?php do_action('cred_admin_notices'); ?>

<form name="post" action="<?php echo esc_url( add_query_arg('form', $post_ID, $_url) ); ?>" method="post" id="post">
<?php wp_nonce_field($nonce_action); ?>
<input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
<input type="hidden" id="hiddenaction" name="action" value="<?php echo esc_attr( $form_action ) ?>" />
<input type="hidden" id="originalaction" name="originalaction" value="<?php echo esc_attr( $form_action ) ?>" />
<input type="hidden" id="post_author" name="post_author" value="<?php echo esc_attr( $post->post_author ); ?>" />
<input type="hidden" id="post_type" name="post_type" value="<?php echo esc_attr( $post_type ) ?>" />
<input type="hidden" id="original_post_status" name="original_post_status" value="<?php echo esc_attr( $post->post_status) ?>" />
<input type="hidden" id="referredby" name="referredby" value="<?php echo esc_url(stripslashes(wp_get_referer())); ?>" />
<?php if ( ! empty( $active_post_lock ) ) { ?>
<input type="hidden" id="active_post_lock" value="<?php echo esc_attr( implode( ':', $active_post_lock ) ); ?>" />
<?php
}
if ( 'draft' != get_post_status( $post ) )
	wp_original_referer_field(true, 'previous');

echo $form_extra;

wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
?>
<div id="poststuff">

<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
<div id="post-body-content">
<?php if ( post_type_supports($post_type, 'title') ) { ?>
<div id="titlediv">
<a id="cred_add_forms_to_site_help" class="cred-help-link" style="position:absolute;top:0;right:0;" href="<?php echo CRED_CRED::$help['add_forms_to_site']['link']; ?>" target="_blank" title="<?php echo CRED_CRED::$help['add_forms_to_site']['text']; ?>"><?php echo CRED_CRED::$help['add_forms_to_site']['text']; ?></a>
<p class="cred-explain-text"><?php _e('Set the title for this new form.', 'wp-cred'); ?></p>
<div id="titlewrap">
	<label class="screen-reader-text" id="title-prompt-text" for="title"><?php _e( 'Enter title here' ); ?></label>
	<input type="text" name="post_title" size="30" value="<?php echo esc_attr( htmlspecialchars( $post->post_title ) ); ?>" id="title" autocomplete="off" />
</div>
<div class="inside">
</div>
</div><!-- /titlediv -->
<?php } ?>
</div><!-- /post-body-content -->

<div id="postbox-container-1" class="postbox-container"><?php CRED_Loader::do_meta_boxes(null, 'side', $post); ?></div>
<div id="postbox-container-2" class="postbox-container"><?php CRED_Loader::do_meta_boxes(null, 'normal', $post); ?></div>
</div><!-- /post-body -->
<br class="clear" />
</div><!-- /poststuff -->
<input id="cred-submit" type="submit" class="button button-primary button-large" value="<?php _e('Update','wp-cred'); ?>" />
</form>
</div>

<?php if ( (isset($post->post_title) && '' == $post->post_title) || (isset($_GET['message']) && 2 > $_GET['message']) ) : ?>
<script type="text/javascript">
try{document.post.title.focus();}catch(e){}
</script>
<?php endif; ?>