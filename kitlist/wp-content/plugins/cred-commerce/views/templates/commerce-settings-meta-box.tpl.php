<?php if (!defined('ABSPATH'))  die('Security check'); ?>
<?php
if (empty($data)) $data=array();
$data=array_merge(array(
    'enable'=>0,
    'associate_product'=>'form',
    'messages'=>array(
        'checkout'=>'',
        'thankyou'=>''
    ),
    'product'=>'',
    'product_field'=>'',
    'clear_cart'=>1,
    'order_processing'=>array('post_status'=>'pending'),
    'order_completed'=>array('post_status'=>'publish'),
    'order_refunded'=>array('post_status'=>'draft'),
    'order_cancelled'=>array('post_status'=>'draft'),
    'fix_author'=>1
),(array)$data);
?>
<div>

    <?php if (!$ecommerce) { ?>

	<div class="cred-commerce-message cred-notification cred-error">
        <p>
			<i class="icon-warning-sign"></i>
            <?php printf(__('To charge payment for this form, you first have to install an e-commerce plugin and create products. Then, return here to select the product for the form and continue the setup. %s', 'wp-cred-pay'), $productlink); ?>
        </p>
    </div>

    <?php }  elseif (!$products || empty($products)) { ?>
	<div class="cred-commerce-message cred-notification cred-error">
		<p>
			<i class="icon-warning-sign"></i>
			<?php printf(__('To charge payment for this form, you first have to <a href="%1$s">create products in %2$s</a>. Then, return here to select the product for the form and continue the setup.', 'wp-cred-pay'), $producthref, $commerceplugin); ?>
		</p>
	</div>

    <?php } ?>

    <div class="cred-fieldset">
    	<p>
			<label>
				<input type="checkbox" name="_cred_commerce[enable]" value="1" <?php if (isset($data['enable'])&&$data['enable']) echo 'checked="checked"'; ?>/>
				<span><?php _e('Charge payment with this form', 'wp-cred-pay'); ?></span>
			</label>
		</p>
    </div>

    <div class="cred_commerce_panel">
        <fieldset class="cred-fieldset">
		<p>
			<?php _e('When visitors submit this form, CRED will ask them to pay by buying a product. If you have not done so already, you need to first create a product in the e-commerce plugin.','wp-cred-pay'); ?>
		</p>
			<h4>
				<?php _e('Product to buy when submitting the form','wp-cred-pay'); ?>
			</h4>
        	<p>
        	    <label>
        	        <input type="radio" name="_cred_commerce[associate_product]" value="form" <?php if (isset($data['associate_product'])&&'form'==$data['associate_product']) echo 'checked="checked"'; ?> />
        	        <span><?php _e('Always this product, regardless of form inputs:','wp-cred-pay'); ?></span>
					<span class="cred-tip-link" data-pointer-content="#single_product_tip">
						<i class="icon-question-sign"></i>
					</span>
        	    </label>
        	    <span>
                    <i title="<?php echo esc_attr(__('Please select a product', 'wp-cred-pay')); ?>" id="commerce_product_required" class="icon-warning-sign" style="display:none;">&nbsp;</i>&nbsp;
                    <select name="_cred_commerce[product]">
                        <optgroup label="<?php echo esc_attr(__('Select a product','wp-cred-pay')); ?>">
                            <option value='' disabled selected style='display:none;'><?php _e('Select a product','wp-cred-pay'); ?></option>
                            <?php foreach ($products as $product_id=>$product_title) { ?>
                            <option value="<?php echo $product_id; ?>" <?php if (isset($data['product'])&&$product_id==$data['product']) echo 'selected="selected"'; ?>><?php echo $product_title; ?></option>
                            <?php } ?>
                        </optgroup>
                    </select>
                </span>
        	</p>
        	<p>
        	    <label>
        	        <input type="radio" name="_cred_commerce[associate_product]" value="post" <?php if (isset($data['associate_product'])&&'post'==$data['associate_product']) echo 'checked="checked"'; ?> />
        	        <span><?php _e('The form specifies the product according to the value of this custom field:', 'wp-cred-pay'); ?></span>
					<span class="cred-tip-link" data-pointer-content="#flexible_product_tip">
						<i class="icon-question-sign"></i>
					</span>
        	    </label>
        	    <span>
                    <i title="<?php echo esc_attr(__('Please select a product field', 'wp-cred-pay')); ?>" id="commerce_product_field_required" class="icon-warning-sign" style="display:none;"></i>
                    <select name="_cred_commerce[product_field]" data-cred-bind="{ action: 'set', what: { options: '_cred[_persistent_select_fields]' } }">
                        <optgroup label="<?php echo esc_attr(__('Select a custom field','wp-cred-pay')); ?>">
                            <?php if (isset($data['product_field']) && ''!=$data['product_field']) { ?>
                            <option value='' disabled style='display:none;' data-dummy-option='1'><?php _e('Select a custom field','wp-cred-pay'); ?></option>
                            <option value="<?php echo $data['product_field']; ?>" selected="selected"><?php echo $data['product_field']; ?></option>
                            <?php } else { ?>
                            <option value='' disabled selected style='display:none;' data-dummy-option='1'><?php _e('Select a custom field','wp-cred-pay'); ?></option>
                            <?php } ?>
                        </optgroup>
                    </select>&nbsp;
                    <a href="javascript:;" data-cred-bind="{ event: 'click', action: 'refreshFormFields' }" class='icon-refresh cred-refresh-button' title="<?php echo esc_attr(__('Click to refresh (if settings changed)','wp-cred-pay')); ?>">&nbsp;</a>
                </span>
        	</p>
        </fieldset>

        <fieldset class="cred-fieldset">
			<h4>
				<?php _e('Checkout process','wp-cred-pay') ?>
				<span class="cred-tip-link" data-pointer-content="#checkout_process_tip">
					<i class="icon-question-sign"></i>
				</span>
			</h4>
			<p>
				<label>
					<input type="radio" name="_cred_commerce[clear_cart]" value="1" <?php if (isset($data['clear_cart'])&&$data['clear_cart']) echo 'checked="checked"'; ?> />
					<span><?php _e('Clear the cart and include only the selected product', 'wp-cred-pay'); ?></span>
					<span class="cred-tip-link" data-pointer-content="#clear_cart_yes_tip">
						<i class="icon-question-sign"></i>
					</span>
				</label>
			</p>
			<p>
				<label>
					<input type="radio" name="_cred_commerce[clear_cart]" value="0" <?php if (!isset($data['clear_cart'])||!$data['clear_cart']) echo 'checked="checked"'; ?>/>
					<span><?php _e('Leave the cart content and add the selected product', 'wp-cred-pay'); ?></span>
					<span class="cred-tip-link" data-pointer-content="#clear_cart_no_tip">
						<i class="icon-question-sign"></i>
					</span>
				</label>
			</p>
			<p class="cred-explain-text">
				<?php printf(__("Note: remember to also select either 'cart page' or 'checkout page' as %s action after submitting the form %s.",'wp-cred-pay'),'<a href="#credformtypediv">','</a>'); ?>
			</p>
        </fieldset>

        <fieldset class="cred-fieldset">
			<h4>
				<?php _e('Messages','wp-cred-pay') ?>
			</h4>
			<strong><?php _e('Message on checkout page (optional):', 'wp-cred-pay'); ?></strong>
            <span class="cred-tip-link" data-pointer-content="#checkout_message_tip">
                <i class="icon-question-sign"></i>
            </span>
            <p class="cred-explain-text"><?php _e('You can use HTML tags and shortcodes in this message. To display it, add [cred_checkout_message] to the checkout page.','wp-cred-pay'); ?></p>
			<p>
				<label>
					<textarea style="position:relative;width:90%" rows="8" name="_cred_commerce[messages][checkout]"><?php if (isset($data['messages']['checkout'])) echo esc_textarea($data['messages']['checkout']); ?></textarea>
				</label>
			</p>
			<strong><?php _e('Message on thank-you page (optional):', 'wp-cred-pay'); ?></strong>
            <span class="cred-tip-link" data-pointer-content="#thankyou_message_tip">
                <i class="icon-question-sign"></i>
            </span>
			<p class="cred-explain-text"><?php _e('You can use HTML tags and shortcodes in this message. To display it, add [cred_thankyou_message] to the thankyou page.','wp-cred-pay'); ?></p>
            <p>
				<label>
					<textarea style="position:relative;width:90%" rows="8" name="_cred_commerce[messages][thankyou]"><?php if (isset($data['messages']['thankyou'])) echo esc_textarea($data['messages']['thankyou']); ?></textarea>
				</label>
			</p>
        </fieldset>

        <fieldset class="cred-fieldset">
        	<h4>
				<?php _e('Post status when the payment status updates','wp-cred-pay') ?>
				<span class="cred-tip-link" data-pointer-content="#post_status_tip">
					<i class="icon-question-sign"></i>
				</span>
			</h4>
        	<table>
                <tbody>
                	<tr>
                        <td><?php _e('Purchase processing', 'wp-cred-pay'); ?></td>
                        <td>
                        <select name="_cred_commerce[order_processing][post_status]">
                            <optgroup label="<?php echo esc_attr(__( 'Select post status', 'wp-cred-pay' )); ?>">
                                <option value='' disabled selected style='display:none;'><?php _e( 'Select post status', 'wp-cred-pay' ); ?></option>
                                <option value='original' <?php if (isset($data['order_processing']['post_status'])&&'original'==$data['order_processing']['post_status']) echo 'selected="selected"'; ?>><?php _e('No change','wp-cred-pay') ?></option>
                                <option value='draft'<?php if (isset($data['order_processing']['post_status'])&&'draft'==$data['order_processing']['post_status']) echo 'selected="selected"'; ?>><?php _e('Draft','wp-cred-pay') ?></option>
                                <option value='pending'<?php if (isset($data['order_processing']['post_status'])&&'pending'==$data['order_processing']['post_status']) echo 'selected="selected"'; ?>><?php _e('Pending Review','wp-cred-pay') ?></option>
                                <option value='private'<?php if (isset($data['order_processing']['post_status'])&&'private'==$data['order_processing']['post_status']) echo 'selected="selected"'; ?>><?php _e('Private','wp-cred-pay') ?></option>
                                <option value='publish'<?php if (isset($data['order_processing']['post_status'])&&'publish'==$data['order_processing']['post_status']) echo 'selected="selected"'; ?>><?php _e('Published','wp-cred-pay') ?></option>
                            </optgroup>
                        </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Purchase complete', 'wp-cred-pay'); ?></td>
                        <td>
                        <select name="_cred_commerce[order_completed][post_status]">
                            <optgroup label="<?php echo esc_attr(__( 'Select post status', 'wp-cred-pay' )); ?>">
                                <option value='' disabled selected style='display:none;'><?php _e( 'Select post status', 'wp-cred-pay' ); ?></option>
                                <option value='original' <?php if (isset($data['order_completed']['post_status'])&&'original'==$data['order_completed']['post_status']) echo 'selected="selected"'; ?>><?php _e('No change','wp-cred-pay') ?></option>
                                <option value='draft'<?php if (isset($data['order_completed']['post_status'])&&'draft'==$data['order_completed']['post_status']) echo 'selected="selected"'; ?>><?php _e('Draft','wp-cred-pay') ?></option>
                                <option value='pending'<?php if (isset($data['order_completed']['post_status'])&&'pending'==$data['order_completed']['post_status']) echo 'selected="selected"'; ?>><?php _e('Pending Review','wp-cred-pay') ?></option>
                                <option value='private'<?php if (isset($data['order_completed']['post_status'])&&'private'==$data['order_completed']['post_status']) echo 'selected="selected"'; ?>><?php _e('Private','wp-cred-pay') ?></option>
                                <option value='publish'<?php if (isset($data['order_completed']['post_status'])&&'publish'==$data['order_completed']['post_status']) echo 'selected="selected"'; ?>><?php _e('Published','wp-cred-pay') ?></option>
                            </optgroup>
                        </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Purchase refunded', 'wp-cred-pay'); ?></td>
                        <td>
                        <select name="_cred_commerce[order_refunded][post_status]">
                            <optgroup label="<?php echo esc_attr(__( 'Select post status', 'wp-cred-pay' )); ?>">
                                <option value='' disabled selected style='display:none;'><?php _e( 'Select post status', 'wp-cred-pay' ); ?></option>
                                <option value='original' <?php if (isset($data['order_refunded']['post_status'])&&'original'==$data['order_refunded']['post_status']) echo 'selected="selected"'; ?>><?php _e('No change','wp-cred-pay') ?></option>
                                <option value='trash' <?php if (isset($data['order_refunded']['post_status'])&&'trash'==$data['order_refunded']['post_status']) echo 'selected="selected"'; ?>><?php _e('Move to trash','wp-cred-pay') ?></option>
                                <option value='delete' <?php if (isset($data['order_refunded']['post_status'])&&'delete'==$data['order_refunded']['post_status']) echo 'selected="selected"'; ?>><?php _e('Delete','wp-cred-pay') ?></option>
                                <option value='draft'<?php if (isset($data['order_refunded']['post_status'])&&'draft'==$data['order_refunded']['post_status']) echo 'selected="selected"'; ?>><?php _e('Draft','wp-cred-pay') ?></option>
                                <option value='pending'<?php if (isset($data['order_refunded']['post_status'])&&'pending'==$data['order_refunded']['post_status']) echo 'selected="selected"'; ?>><?php _e('Pending Review','wp-cred-pay') ?></option>
                                <option value='private'<?php if (isset($data['order_refunded']['post_status'])&&'private'==$data['order_refunded']['post_status']) echo 'selected="selected"'; ?>><?php _e('Private','wp-cred-pay') ?></option>
                                <option value='publish'<?php if (isset($data['order_refunded']['post_status'])&&'publish'==$data['order_refunded']['post_status']) echo 'selected="selected"'; ?>><?php _e('Published','wp-cred-pay') ?></option>
                            </optgroup>
                        </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Purchase cancelled', 'wp-cred-pay'); ?></td>
                        <td>
                        <select name="_cred_commerce[order_cancelled][post_status]">
                            <optgroup label="<?php echo esc_attr(__( 'Select post status', 'wp-cred-pay' )); ?>">
                                <option value='' disabled selected style='display:none;'><?php _e( 'Select post status', 'wp-cred-pay' ); ?></option>
                                <option value='original' <?php if (isset($data['order_cancelled']['post_status'])&&'original'==$data['order_cancelled']['post_status']) echo 'selected="selected"'; ?>><?php _e('No change','wp-cred-pay') ?></option>
                                <option value='trash' <?php if (isset($data['order_cancelled']['post_status'])&&'trash'==$data['order_cancelled']['post_status']) echo 'selected="selected"'; ?>><?php _e('Move to trash','wp-cred-pay') ?></option>
                                <option value='delete' <?php if (isset($data['order_cancelled']['post_status'])&&'delete'==$data['order_cancelled']['post_status']) echo 'selected="selected"'; ?>><?php _e('Delete','wp-cred-pay') ?></option>
                                <option value='draft'<?php if (isset($data['order_cancelled']['post_status'])&&'draft'==$data['order_cancelled']['post_status']) echo 'selected="selected"'; ?>><?php _e('Draft','wp-cred-pay') ?></option>
                                <option value='pending'<?php if (isset($data['order_cancelled']['post_status'])&&'pending'==$data['order_cancelled']['post_status']) echo 'selected="selected"'; ?>><?php _e('Pending Review','wp-cred-pay') ?></option>
                                <option value='private'<?php if (isset($data['order_cancelled']['post_status'])&&'private'==$data['order_cancelled']['post_status']) echo 'selected="selected"'; ?>><?php _e('Private','wp-cred-pay') ?></option>
                                <option value='publish'<?php if (isset($data['order_cancelled']['post_status'])&&'publish'==$data['order_cancelled']['post_status']) echo 'selected="selected"'; ?>><?php _e('Published','wp-cred-pay') ?></option>
                            </optgroup>
                        </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </fieldset>

		<fieldset class="cred-fieldset">
			<h4>
				<?php _e('Set the post author when payment completes','wp-cred-pay') ?>
				<span class="cred-tip-link" data-pointer-content="#post_author_tip">
					<i class="icon-question-sign"></i>
				</span>
			</h4>
			<p>
				<label>
					<input type="radio" name="_cred_commerce[fix_author]" value="1" <?php if (isset($data['fix_author'])&&$data['fix_author']) echo 'checked="checked"'; ?> />
					<span>
						<?php _e('After payment completes, make the client the author of the post', 'wp-cred-pay'); ?>
					</span>
				</label>
			</p>
			<p>
				<label>
					<input type="radio" name="_cred_commerce[fix_author]" value="0" <?php if (!isset($data['fix_author'])||!$data['fix_author']) echo 'checked="checked"'; ?> />
					<span><?php _e('Leave the post author unchanged after payment completes', 'wp-cred-pay'); ?></span>
				</label>
			</p>
		</fieldset>

    </div>
</div>

<div style="display:none">
    <div id="post_author_tip">
        <h3><?php _e('Post Author', 'wp-cred-pay'); ?></h3>
        <p><?php _e('The e-commerce plugin will create a new user after payment completes. Choose if you want to make that user the author of the submitted post.', 'wp-cred-pay'); ?></p>
    </div>
    <div id="post_status_tip">
        <h3><?php _e('Post status', 'wp-cred-pay'); ?></h3>
        <p><?php _e('CRED Commerce can update the status of the post, created by the form, when the payment status changes. This will let you automatically publish and hide posts when payments complete or are cancelled.', 'wp-cred-pay'); ?></p>
    </div>
    <div id="thankyou_message_tip">
        <h3><?php _e('Message on thank-you page', 'wp-cred-pay'); ?></h3>
        <p><?php _e('This field allows you to add a message to the thank-you page, which clients will see once their payment is complete. In this message, you can describe what your clients should expect next. Use shortcodes to link to the newly-create post or other personalized information. CRED shortcodes', 'wp-cred-pay'); ?></p>
    </div>
    <div id="checkout_message_tip">
        <h3><?php _e('Message on checkout', 'wp-cred-pay'); ?></h3>
        <p><?php _e('This field allows you to add a message to the checkout page. In this message, you can include description of the payment process and what clients should expect once payments complete. For the message to display, you will need to add [cred_checkout_message] to the content of the checkout page.', 'wp-cred-pay'); ?></p>
    </div>
    <div id="clear_cart_no_tip">
        <h3><?php _e('Accumulate products and pay for all together', 'wp-cred-pay'); ?></h3>
        <p><?php _e('Choose this option if you want to let visitors pay for several forms together. The cart will remain uncleared and the selected product will be added to it. Remember to also select \'Go to cart page\' in the setting for what to do when submitting the form (at the top of this page).', 'wp-cred-pay'); ?></p>
    </div>
    <div id="clear_cart_yes_tip">
        <h3><?php _e('Pay for one product at a time', 'wp-cred-pay'); ?></h3>
        <p><?php _e('Choose this option if you want to send clients to payment directly after submitting the form. The cart will be cleared and the product for this payment will be added to it. Remember to also select \'Go to checkout page\' in the setting for what to do when submitting the form (at the top of this page).', 'wp-cred-pay'); ?></p>
    </div>
    <div id="checkout_process_tip">
        <h3><?php _e('Checkout Process', 'wp-cred-pay'); ?></h3>
        <p><?php _e('The e-commerce plugin works with a cart. You can choose to go through the cart or skip it and go directly to the checkout page.', 'wp-cred-pay'); ?></p>
    </div>
    <div id="flexible_product_tip">
        <h3><?php _e('Flexible product selection', 'wp-cred-pay'); ?></h3>
        <p><?php _e('Use this option if you want to allow multiple products for the form. You will need to set up a custom field (<strong>select or radio</strong>) that holds the <strong>ID</strong> of the product. Then, select the custom field using the dropdown.', 'wp-cred-pay'); ?></p>
    </div>
    <div id="single_product_tip">
        <h3><?php _e('Single product option', 'wp-cred-pay'); ?></h3>
        <p><?php _e('This option lets you select a single product that will always be purchased when submitting this form.', 'wp-cred-pay'); ?></p>
    </div>
</div>

<script type="text/javascript">
/* <![CDATA[ */
(function($, cred, undefined){
    $(function(){
		var $_post=$('#post'),
            $credCommerceEnable=$('input[name="_cred_commerce[enable]"]'),
            $credCommercePanel = $('.cred_commerce_panel'),
            $credCommerceProduct = $('select[name="_cred_commerce[product]"]'),
            $credCommerceProductField = $('select[name="_cred_commerce[product_field]"]'),
            $credCommerceAssociateProduct = $('input[name="_cred_commerce[associate_product]"]');
        
        var commercecodes=<?php echo json_encode($codes); ?>;
        
        var warnProduct=$('#commerce_product_required'),
            warnProductField=$('#commerce_product_field_required');
        
        function enablePlaceholders(value, placeholders)
        {
            if ($credCommerceEnable.is(':checked'))
            {
                placeholders.filter(function(){
                    var val=$(this).data('value');
                    if ($.inArray(val, commercecodes)>-1)
                        return true;
                    return false;
                }).prop('disabled', false).show();
            }
            else
            {
                placeholders.filter(function(){
                    var val=$(this).data('value');
                    if ($.inArray(val, commercecodes)>-1)
                        return true;
                    return false;
                }).prop('disabled', true).hide();
            }
        }
        
        cred.app.attach('cred.notificationEventChanged', enablePlaceholders);
        
        function isRequired($el)
        {
			var el=$el[0], v=cred.mvc.utils.validator.getValue($el);

            if ( el.nodeName.toLowerCase() === "select" ) {
				// could be an array for select-multiple or a string, both are fine this way
				var val = $el.val();
				return val && val.length > 0;
			}
			if ( cred.mvc.utils.validator.isCheckable($el) ) {
				return cred.mvc.utils.validator.getLength($el, v) > 0;
			}
			return $.trim(v).length > 0;
        }

        function checkRequired()
        {
            if (isRequired($credCommerceProduct))
                warnProduct.hide();
            else
                warnProduct.show();

            if (isRequired($credCommerceProductField))
                warnProductField.hide();
            else
                warnProductField.show();
        }
        
        function replaceFormSubmitEventOption()
        {
            if ($credCommerceEnable.is(':checked'))
            {
                $_post.find('.cred-notification-event-fieldset').each(function(){
                    var $this=$(this), trigger=0,
                        form_submit=$this.find('input[value="form_submit"]'),
                        order_created=$this.find('input[value="order_created"]'),
                        order_modified=$this.find('input[value="order_modified"]'),
                        input_name=form_submit.attr('name'),
                        inputs=$this.find('input[name="'+input_name+'"]'),
                        checked_inputs=inputs.filter(':checked');
                        
                        if (form_submit.is(':checked'))
                        {
                            form_submit.prop('checked', false);
                            order_created.prop('checked', true);
                            trigger=1;
                        }
                        
                        if (typeof(order_modified[0].__credCommercePrevValue)!='undefined')
                            order_modified.prop('checked',order_modified[0].__credCommercePrevValue);
                        
                        form_submit.closest('p').hide();
                        order_created.closest('p').show();
                        order_modified.closest('p').show();
                        if (trigger)
                            order_created.trigger('change');
                        else
                            checked_inputs.trigger('change');
                        //order_modified.trigger('change');
                });
                $_post.find('.cred-notification-recipient-fieldset').each(function(){
                    var $this=$(this), 
                        to_customer=$this.find('input[value="customer"]');
                        to_customer.closest('p').show();
                        
                        if (typeof(to_customer[0].__credCommercePrevValue)!='undefined')
                        {
                            to_customer.prop('checked', to_customer[0].__credCommercePrevValue);
                            to_customer.trigger('change');
                        }
                });
            }
            else
            {
                $_post.find('.cred-notification-event-fieldset').each(function(){
                    var $this=$(this), trigger=0
                        form_submit=$this.find('input[value="form_submit"]'),
                        order_created=$this.find('input[value="order_created"]'),
                        order_modified=$this.find('input[value="order_modified"]'),
                        input_name=form_submit.attr('name'),
                        inputs=$this.find('input[name="'+input_name+'"]'),
                        checked_inputs=inputs.filter(':checked');
                        
                    // save prev values
                    inputs.each(function(){
                        var $el=$(this), el=$el[0];
                        el.__credCommercePrevValue=$el.is('checked');
                    });
                    
                    if (order_created.is(':checked'))
                    {
                        order_created.prop('checked', false);
                        form_submit.prop('checked', true);
                        trigger=1;
                    }
                    else if (order_modified.is(':checked'))
                    {
                        order_modified.prop('checked', false);
                        form_submit.prop('checked', true);
                        trigger=1;
                    }
                    
                    order_modified.prop('checked', false);
                    order_created.closest('p').hide();
                    order_modified.closest('p').hide();
                    form_submit.closest('p').show();
                    if (trigger)
                        form_submit.trigger('change');
                    else
                        checked_inputs.trigger('change');
                    
                });
                $_post.find('.cred-notification-recipient-fieldset').each(function(){
                    var $this=$(this), 
                        to_customer=$this.find('input[value="customer"]');
                        to_customer[0].__credCommercePrevValue=to_customer.is(':checked');
                        to_customer.prop('checked', false);
                        to_customer.closest('p').hide();
                        to_customer.trigger('change');
                });
            }
        }
        
        // do this on every new notification
        $_post.on('click', '#cred-notification-add-button', function(e){
            replaceFormSubmitEventOption();
        });
        
        $_post.on('change', 'input[name="_cred_commerce[enable]"]', function(event){
            replaceFormSubmitEventOption();
            if ($(this).is(':checked'))
                $credCommercePanel.stop().slideFadeDown('fast');
            else
                $credCommercePanel.stop().slideFadeUp('fast');
        });
        $credCommerceEnable.trigger('change');
        
        $_post.on('change', 'select[name="_cred_commerce[product]"]', checkRequired);
        $_post.on('change', 'select[name="_cred_commerce[product_field]"]', checkRequired);
        checkRequired();
        
        $_post.on('change', 'input[name="_cred_commerce[associate_product]"]', function(event){
            var $this=$(this), value=$this.val();
            if ($this.is(':checked') && 'post'==value)
            {
                $credCommerceProduct.parent('span').hide();
                $credCommerceProductField.parent('span').show();
            }
            if ($this.is(':checked') && 'form'==value)
            {
                $credCommerceProductField.parent('span').hide();
                $credCommerceProduct.parent('span').show();
            }
            checkRequired();
        });
        $credCommerceAssociateProduct.trigger('change');
    });
})(jQuery, cred_cred);
/* ]]> */
</script>