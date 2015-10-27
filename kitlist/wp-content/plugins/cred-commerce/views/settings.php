<?php
if (!defined('ABSPATH'))  die('Security check');
if(!current_user_can('manage_options')) die('Access Denied');

/* get settings */
$settings=CREDC_Loader::get('MODEL/Main')->getSettings();
/* get current tab */
$url=remove_query_arg( array( 'tab' ), $_SERVER['REQUEST_URI'] );
$tab='general';
?>
<div class="wrap cred-commerce-settings">
    <?php screen_icon('cred-frontend-editor'); ?>
    <h2><?php _e('CRED Commerce','wp-cred-pay') ?></h2>
    <form method="post" action="">
    <div id="general" class="nav-tab-content">
        <div class="cred-fieldset">
        	<h3><?php _e('System Check','wp-cred-pay'); ?></h3>
        	<ul>
				<?php if (defined('CRED_FE_VERSION') && version_compare(CRED_FE_VERSION, '1.2', '<')) : ?>
					<li><i class="icon-warning-sign"></i> <?php printf(__('%1$s  is installed, but with incorrect version. You need %1$s %2$s or higher. ','wp-cred-pay'),'<strong>CRED</strong>', '1.2'); ?> <a href="http://wp-types.com/home/cred/" target="_blank"><?php _e('Update CRED','wp-cred-pay'); ?></a></li>
				<?php elseif (defined('CRED_FORMS_CUSTOM_POST_NAME')) : ?>
					<li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.','wp-cred-pay'),'<strong>CRED</strong>'); ?></li>
				<?php else : ?>
					<li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not activated.','wp-cred-pay'),'<strong>CRED</strong>'); ?> <a href="http://wp-types.com/home/cred/" target="_blank"><?php _e('Buy CRED','wp-cred-pay'); ?></a></li>
				<?php endif; ?>
				<?php global $woocommerce;
                if (class_exists( 'Woocommerce' ) && $woocommerce && isset($woocommerce->version) && version_compare($woocommerce->version, '2.0', '<')) : 
                ?>
					<li><i class="icon-warning-sign"></i> <?php  printf(__('%1$s  is installed, but with incorrect version. You need %1$s %2$s or higher. ','wp-cred-pay'),'<strong>WooCommerce</strong>', '2.0'); ?> <a href="http://wordpress.org/extend/plugins/woocommerce/" target="_blank"><?php _e('Download WooCommerce','wp-cred-pay'); ?></a></li>
                <?php elseif (class_exists( 'Woocommerce' )) :  ?>
					<li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.','wp-cred-pay'),'<strong>WooCommerce</strong>'); ?></li>
				<?php else : ?>
					<li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not activated.','wp-cred-pay'),'<strong>WooCommerce</strong>'); ?> <a href="http://wordpress.org/extend/plugins/woocommerce/" target="_blank"><?php _e('Download WooCommerce','wp-cred-pay'); ?></a></li>
				<?php endif; ?>
        	</ul>
        </div>
		<div class="cred-fieldset get-started">
			<h3><?php _e('How CRED Commerce works','wp-cred-pay'); ?></h3>
			<p><?php _e('CRED Commerce lets you charge payments when submitting forms. It uses WooCommerce plugin to handle the actual payment workflow.','wp-cred-pay'); ?></p>
			<p><?php _e('You will need to create products in WooCommerce and select them in the CRED forms that require payment.','wp-cred-pay'); ?></p>
			<p><?php _e('When visitors submit these payments, they will be sent to buy the selected products.','wp-cred-pay'); ?></p>
			<table class="wp-list-table widefat">
				<thead>
					<tr><th><?php _e('Step','wp-cred-pay'); ?></th><th><?php _e('Result','wp-cred-pay'); ?></th></tr>
				</thead>
				<tbody>
					<tr><td>1. <?php _e('Visitor submits a form','wp-cred-pay'); ?></td><td><?php _e('CRED creates the post from the form and sends the visitor to buy the product that you set up for the form.','wp-cred-pay'); ?></td></tr>
					<tr><td>2. <?php _e('Visitor goes to checkout page','wp-cred-pay'); ?></td><td><?php _e('The visitor can complete the payment using any of the available payment options by the e-commerce plugin.','wp-cred-pay'); ?></td></tr>
					<tr><td>3. <?php _e('Visitor completes payment','wp-cred-pay'); ?></td><td><?php _e('CRED updates the post status and sends notification emails to you and the customer','wp-cred-pay'); ?></td></tr>
				</tbody>
			</table>
			<h3><?php _e('Getting started','wp-cred-pay'); ?></h3>
			<ol>
				<li>
					<?php _e('Set up products in the e-commerce plugin - you will need to create a different product for each different payment option.','wp-cred-pay'); ?>
				</li>
				<li>
					<?php _e('When editing a CRED form, scroll to the bottom and enable payment for the form.','wp-cred-pay'); ?>
				</li>
				<li>
					<?php _e('Select the product for the form and the payment flow that you prefer.','wp-cred-pay'); ?>
				</li>
			</ol>
			<p><?php _e('Remember that the payments will go through the e-commerce plugin, so be sure to setup the payment processing options in WooCommerce.','wp-cred-pay'); ?></p>
		</div>
    </div>
    </form>
</div>
<script type="text/javascript">
/* <![CDATA[ */
/*(function($){
    $(function(){
        $('.nav-tab-content').hide();
        $('#<?php echo $tab; ?>').stop().slideDown('fast');
        $('.nav-tab').removeClass('nav-tab-active');
        $('a[rel="#<?php echo $tab; ?>"]').addClass('nav-tab-active');

        $('.nav-tab').click(function(event){
            event.preventDefault();
            event.stopPropagation();
            $('.nav-tab-content').hide();
            $($(this).attr('rel')).slideDown('fast');
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active').blur();
            return false;
        });
    });
})(jQuery);*/
/* ]]> */
</script>