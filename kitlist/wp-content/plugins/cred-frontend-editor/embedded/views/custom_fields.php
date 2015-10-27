<?php
if (!defined('ABSPATH'))  die('Security check');
if(!current_user_can(CRED_CAPABILITY)) {
	die('Access Denied');
}

if (isset($_REQUEST['posttype']) && !empty($_REQUEST['posttype']))
{
    $cfmodel=CRED_Loader::get('MODEL/Fields');
    if (isset($_REQUEST['ignorechecked']) && is_array($_REQUEST['ignorechecked']))
    {
        $cfmodel->ignoreCustomFields($_REQUEST['posttype'], $_REQUEST['ignorechecked']);
    }
    if (isset($_REQUEST['unignorechecked']) && is_array($_REQUEST['unignorechecked']))
    {
        $cfmodel->ignoreCustomFields($_REQUEST['posttype'], $_REQUEST['unignorechecked'], 'unignore');
    }
    if (isset($_REQUEST['resetchecked']) && is_array($_REQUEST['resetchecked']))
    {
        $cfmodel->ignoreCustomFields($_REQUEST['posttype'], $_REQUEST['resetchecked'], 'reset');
    }
}
?>

<?php
wp_enqueue_style( 'thickbox' );
wp_print_styles( 'thickbox' );
wp_enqueue_script( 'thickbox' );
wp_print_scripts( 'thickbox' );
?>

<div class="wrap">
    <?php screen_icon('cred-frontend-editor'); ?>
    <h2><?php _e('Manage Other Post Types with CRED','wp-cred') ?></h2><br />
    <form id="custom_fields" action="" method="post">
    <?php
    //print_r(CRED_Loader::get('MODEL/Fields')->getCustomFields());
    // display custom fields table
    $wp_list_table = CRED_Loader::get('TABLE/Custom_Fields');
    $wp_list_table->prepare_items();
    $wp_list_table->display();
    ?>
    </form>
</div>

<script type='text/javascript'>
/* <![CDATA[ */
(function($, undefined)
{
    var not_set_text='<?php echo esc_js(__('Not Set','wp-cred')); ?>';
    
    $(function(){
        $('.ajax-feedback').css('visibility','hidden');
        $('.cred-field-type').each(function(){
            var $_this=$(this);
            if (not_set_text==$_this.text())
            {
                $_this.closest('tr').find('._cred-field-edit').hide();
                $_this.closest('tr').find('._cred-field-set').show();
                $_this.closest('tr').find('._cred-field-remove').hide();
            }
            else
            {
                $_this.closest('tr').find('._cred-field-edit').show();
                $_this.closest('tr').find('._cred-field-set').hide();
                $_this.closest('tr').find('._cred-field-remove').show();
            }
        });
        $('._cred-field-remove').each(function(){
            var $_this=$(this);
            $_this.click(function(event){
                event.preventDefault();
                event.stopPropagation();
                
                $('.ajax-feedback').css('visibility','visible');
                $.get($_this.attr('href'), function(res){
                    if ('true'==res)
                    {
                        $_this.closest('tr').find('.cred-field-type').text(not_set_text);
                        $_this.closest('td').find('._cred-field-edit').hide();
                        $_this.closest('td').find('._cred-field-set').show();
                        $_this.hide();
                        $('.ajax-feedback').css('visibility','hidden');
                    }
                });
            });
        });
    });
})(jQuery);
/* ]]> */
</script>