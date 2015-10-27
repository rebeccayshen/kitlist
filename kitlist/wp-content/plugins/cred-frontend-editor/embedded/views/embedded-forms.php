<?php
if (!defined('ABSPATH'))  die('Security check');
if(!current_user_can(CRED_CAPABILITY)) {
	die('Access Denied');
}

// include needed files
$wp_list_table = CRED_Loader::get('TABLE/EmbeddedForms');
$doaction = $wp_list_table->current_action();

$url = CRED_CRED::getNewFormLink();
$form_id = '';
$form_name = '';
$form_type = '';
$post_type = '';
$form_content= '';
$fields='';

// Handle Table Action
if($doaction)
{
    $forms_model = CRED_Loader::get('MODEL/Forms');    
    
    $redurl = "?page=".$_REQUEST['page'];
    if (headers_sent()) {
        //die("Redirect failed. Please click on this link: <a href=...>");
        echo "<script language='javascript'>window.location='{$redurl}';</script>";
        die();
    } else{
        exit(wp_redirect("?page=".$_REQUEST['page']));
    }    
    //exit();
}

// Display Table
?>
<div class="cred_overlay_loader"></div>
<div class="wrap">
    <h2><?php _e('CRED Post Forms', 'wp-cred'); ?><a class="add-new-h2 js-open-promotional-message" href="<?php echo CRED_CRED::$help['add_forms_to_site']['link']; ?>"><?php _e('Add New', 'wp-cred'); ?></a>
    <a class="cred-help-link js-open-promotional-message" href="<?php echo CRED_CRED::$help['add_forms_to_site']['link']; ?>" target="_blank" title="<?php echo esc_attr(CRED_CRED::$help['add_forms_to_site']['text']); ?>">
		<i class="icon-question-sign"></i>
	</a>
    </h2><br />
    <form id="list" action="" method="post">
    <?php
    if ( function_exists('wp_nonce_field') )
	wp_nonce_field('cred-bulk-selected-action','cred-bulk-selected-field');
    $wp_list_table->prepare_items();
    $wp_list_table->display();
    ?>
    </form>
</div>

<script>
    jQuery(".bulkactions").remove();
    jQuery(".check-column").remove();
    jQuery(".row-actions").remove();
</script>
