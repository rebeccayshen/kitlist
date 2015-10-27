<?php

if (!defined('ABSPATH'))
    die('Security check');
if (!current_user_can(CRED_CAPABILITY)) {
    die('Access Denied');
}

$_url = admin_url('admin.php') . '?page=CRED_Form';

global $action, $typenow, $post, $post_ID, $user_ID, $action, $post_type, $post_type_object, $editing, $active_post_lock;
global $title, $hook_suffix, $current_screen, $wp_locale, $pagenow, $wp_version,
 $current_site, $update_title, $total_update_count, $parent_file;

wp_reset_vars(array('action', 'safe_mode', 'withcomments', 'posts', 'content', 'edited_post_title', 'comment_error', 'profile', 'trackback_url', 'excerpt', 'showcomments', 'commentstart', 'commentend', 'commentorder'));

//cred_log($action);

if (isset($_GET['form']))
    $post_id = $post_ID = (int) $_GET['form'];
elseif (isset($_POST['post_ID']))
    $post_id = $post_ID = (int) $_POST['post_ID'];
else
    $post_id = $post_ID = 0;

$post = $post_type = $post_type_object = null;

$_url = esc_url(add_query_arg('form', $post_id, $_url));

if ($post_id)
    $post = get_post($post_id, OBJECT, 'edit');
//$post = get_post( $post_id );

if ($post) {
    $post_type = $post->post_type;
    $post_type_object = get_post_type_object($post_type);
}

$editing = true;
$p = $post_id;

if (empty($post->ID))
    wp_die(__('You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?'));

if (null == $post_type_object)
    wp_die(__('Unknown post type.'));

if (!current_user_can($post_type_object->cap->edit_post, $post_id) || !current_user_can(CRED_CAPABILITY))
    wp_die(__('You are not allowed to edit this item.'));

if ('trash' == $post->post_status)
    wp_die(__('You can&#8217;t edit this item because it is in the Trash. Please restore it and try again.'));

$post_type = $post->post_type;
if ('post' == $post_type) {
    $parent_file = "edit.php";
    $submenu_file = "edit.php";
    $post_new_file = "post-new.php";
} elseif ('attachment' == $post_type) {
    $parent_file = 'upload.php';
    $submenu_file = 'upload.php';
    $post_new_file = 'media-new.php';
} else {
    if (isset($post_type_object) && $post_type_object->show_in_menu && $post_type_object->show_in_menu !== true)
        $parent_file = $post_type_object->show_in_menu;
    else
        $parent_file = "edit.php?post_type=$post_type";
    $submenu_file = "edit.php?post_type=$post_type";
    $post_new_file = "post-new.php?post_type=$post_type";
}

if ($last = wp_check_post_lock($post->ID)) {
    add_action('admin_notices', '_admin_notice_post_locked');
} else {
    $active_post_lock = wp_set_post_lock($post->ID);
}

$title = $post_type_object->labels->edit_item;
//$post = get_post($post_id, OBJECT, 'edit');

/**
 * Redirect to previous page.
 *
 * @param int $post_id Optional. Post ID.
 */
function redirect_post($post_id = '', $_url) {
    $_url = esc_url(add_query_arg('form', $post_id, $_url));

    if (isset($_POST['save']) || isset($_POST['publish'])) {
        $status = get_post_status($post_id);

        if (isset($_POST['publish'])) {
            switch ($status) {
                case 'pending':
                    $message = 8;
                    break;
                case 'future':
                    $message = 9;
                    break;
                default:
                    $message = 6;
            }
        } else {
            $message = 'draft' == $status ? 10 : 1;
        }

        $location = add_query_arg('message', $message, $_url /* get_edit_post_link( $post_id, 'url' ) */);
    } else {
        $location = add_query_arg('message', 4, $_url /* get_edit_post_link( $post_id, 'url' ) */);
    }

    wp_redirect(esc_url($location) /* apply_filters( 'redirect_post_location', $location, $post_id ) */);
    exit;
}

/* $sendback = wp_get_referer();
  if ( ! $sendback ||
  strpos( $sendback, 'post.php' ) !== false ||
  strpos( $sendback, 'post-new.php' ) !== false ) {
  if ( 'attachment' == $post_type ) {
  $sendback = admin_url( 'upload.php' );
  } else {
  $sendback = admin_url( 'edit.php' );
  $sendback .= ( ! empty( $post_type ) ) ? '?post_type=' . $post_type : '';
  }
  } else {
  $sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), $sendback );
  } */

switch ($action) {
    case 'post':
        check_admin_referer('add-' . $post_type);

        $_POST['publish'] = 'publish'; // tell write_post() to publish
        $post_id = write_post();
        redirect_post($post_id, $_url);
        exit();
        break;

    case 'editpost':
        check_admin_referer('update-post_' . $post_id);
        $post_id = edit_post();
        redirect_post($post_id, $_url); // Send user on their way while we keep working
        exit();
        break;

    case 'edit':
    default:
        $editing = true;
        if (empty($post_id)) {
            wp_redirect(admin_url('admin.php') . '?page=CRED_Forms');
            exit();
        }
        $p = $post_id;
        if (empty($post->ID))
            wp_die(__('You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?'));
        if (null == $post_type_object)
            wp_die(__('Unknown post type.'));
        if (!current_user_can($post_type_object->cap->edit_post, $post_id) || !current_user_can(CRED_CAPABILITY))
            wp_die(__('You are not allowed to edit this item.'));
        if ('trash' == $post->post_status)
            wp_die(__('You can&#8217;t edit this item because it is in the Trash. Please restore it and try again.'));

        $post_type = $post->post_type;
        if ('post' == $post_type) {
            $parent_file = "edit.php";
            $submenu_file = "edit.php";
            $post_new_file = "post-new.php";
        } elseif ('attachment' == $post_type) {
            $parent_file = 'upload.php';
            $submenu_file = 'upload.php';
            $post_new_file = 'media-new.php';
        } else {
            if (isset($post_type_object) && $post_type_object->show_in_menu && $post_type_object->show_in_menu !== true)
                $parent_file = $post_type_object->show_in_menu;
            else
                $parent_file = "edit.php?post_type=$post_type";
            $submenu_file = "edit.php?post_type=$post_type";
            $post_new_file = "post-new.php?post_type=$post_type";
        }

        if ($last = wp_check_post_lock($post->ID)) {
            add_action('admin_notices', '_admin_notice_post_locked');
        } else {
            $active_post_lock = wp_set_post_lock($post->ID);
        }

        $title = $post_type_object->labels->edit_item;
        $post = get_post($post_id, OBJECT, 'edit');

        CRED_Loader::load('VIEW/edit-form-advanced');

        break;
} // end switch


