<?php
if (!defined('ABSPATH'))  die('Security check');
if(!current_user_can(CRED_CAPABILITY)) {
	die('Access Denied');
}

$_url=admin_url('admin.php').'?page=CRED_Form';

global $action, $typenow, $post, $post_ID, $user_ID, $action, $post_type, $post_type_object, $editing, $active_post_lock;
global $title, $hook_suffix, $current_screen, $wp_locale, $pagenow, $wp_version,
	$current_site, $update_title, $total_update_count, $parent_file;
    
//do_action( 'load-post-new.php' );
//do_action( 'load-page-new.php' );

$post_type = CRED_FORMS_CUSTOM_POST_NAME;
$post_type_object = get_post_type_object( $post_type );
$title = $post_type_object->labels->add_new_item;

$editing = true;

if ( ! current_user_can( $post_type_object->cap->edit_posts ) || ! current_user_can( $post_type_object->cap->create_posts ) ||  ! current_user_can( CRED_CAPABILITY ) )
	wp_die( __( 'Cheatin&#8217; uh?' ) );

// Show post form.
if (!$post)
    $post = get_default_post_to_edit( $post_type, true );
$post_ID = $post->ID;
CRED_Loader::load('VIEW/edit-form-advanced');
