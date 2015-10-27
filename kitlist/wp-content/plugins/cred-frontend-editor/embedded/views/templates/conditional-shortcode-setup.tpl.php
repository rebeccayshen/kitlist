<?php if (!defined('ABSPATH'))  die('Security check'); ?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php
// include jquery from wp-admin and styles
CRED_Loader::loadAsset('STYLE/cred_template_style', 'cred_template_style', true);
wp_print_styles('cred_template_style');
CRED_Loader::loadAsset('SCRIPT/cred_template_script', 'cred_template_script', true);
//wp_enqueue_script('jquery-ui-sortable');
wp_print_scripts('cred_template_script');


wp_register_style('onthego-admin-styles', ON_THE_GO_SYSTEMS_BRANDING_REL_PATH .'onthego-styles/onthego-styles.css');
wp_print_styles( 'onthego-admin-styles' );
?>
<!-- templates -->
<script id='condtional-term-template' type='text/html-template'>
<tr class='expression-term-single row2'>
	<td class='cell2'>
		<select class='expression-term'>
		</select>
	</td>
	<td class='cell2'>
		<select class='expression-comparison-op'>
			<option value='eq'>=</value>
			<option value='ne'>!=</value>
			<option value='gt'>&gt;</value>
			<option value='lt'>&lt;</value>
			<option value='gte'>&gt;=</value>
			<option value='lte'>&lt;=</value>
		</select>
	</td>
	<td class='cell2'>
		<input type='text' size='7' class='expression-term-value' value='' />
		<span class='date-value'>
			<input type='text' size='7' class='expression-term-date-value' value='TODAY()' />
			<select class='expression-term-date-format'>
				<option value='d/m/Y'>d/m/Y</option>
				<option value='m/d/Y'>m/d/Y</option>
			</select>
		</span>
	</td>
	<td class='cell2'>
		<select class='expression-logical-op'>
			<option value='AND'>AND</value>
			<option value='OR'>OR</value>
		</select>
	</td>
	<td class='cell2'>
		<a class='remove-option' href='javascript:;' title='<?php echo esc_attr(__( 'Remove term', 'wp-cred' )); ?>'></a>
	</td>
</tr>
</script>
<!-- templates end -->

<!-- logic -->
<script type='text/javascript'>
/* <![CDATA[ */
(function(window, $, cred, undefined){

    $.fn.slideFadeDown = function(speed, easing, callback) {
        return this.each(function(){$(this).stop(true).animate({opacity: 'show', height: 'show'}, speed, easing || 'linear', function() {
                if ($.browser && $.browser.msie) { this.style.removeAttribute('filter'); }
                if ($.isFunction(callback)) { callback.call(this); }
            });
        });
    };
    $.fn.slideFadeUp = function(speed, easing, callback) {
        return this.each(function(){
            if ($(this).is(':hidden'))
                $(this).hide(); // makes element not lose height if already hidden (eg by parent element)
            else
            {
                $(this).stop(true).animate({opacity: 'hide', height: 'hide'}, speed, easing || 'linear', function() {
                    if ($.browser && $.browser.msie) { this.style.removeAttribute('filter'); }
                    if ($.isFunction(callback)) { callback.call(this); }
                });
            }
        });
    };

    $(function(){

        var fields, content, match, tmpl, overlay,
            fields=cred.getFormFields(),
            conditionalfields=[], map={}, useCustom=false;

        tmpl=$('#condtional-term-template').html();
        overlay=$('<div class="overlay-disabled">&nbsp;</div>');

        // parse content for allowed fields for conditionals
        for (var ii=0; ii<fields.length; ii++)
        {
            if (
                //fields[ii].type!='skype' &&
                //fields[ii].type!='image' &&
                //fields[ii].type!='file' &&
				// We only leave out WYSIWYG and repetitive fields
				fields[ii].type!='wysiwyg' &&
                !fields[ii].repetitive
            )
            conditionalfields.push({field:fields[ii].name, type:fields[ii].type});
        }

        fields=null;

        for (var ii=0, ll=conditionalfields.length; ii<ll; ii++)
        {
            map[conditionalfields[ii].field]=conditionalfields[ii].type;
        }

        function refreshExpression()
        {
            if (useCustom) return;
            
            var expr='', expr2;
            
            $('#terms tbody tr').each(function(){
                var $this=$(this),
                    term=$this.find('.expression-term'),
                    op=$this.find('.expression-comparison-op'),
                    val=$this.find('.expression-term-value'),
                    term1=term.val(),
                    op1=op.val(),
                    val1=val.val(),
                    date, format;

                // these fields have multiple values or a non-numeric value and comparison is not possible
                if (
                    (map[term1]=='checkbox' ||
                    map[term1]=='checkboxes' ||
                    /*map[term1]=='select' ||*/
                    map[term1]=='taxonomy') ||
					map[term1]=='skype' ||
					map[term1]=='file' ||
					map[term1]=='image' ||
					map[term1]=='audio' ||
					map[term1]=='video'
					
                )
                {
                    $('option',op).filter(function(){
                        var op1=$(this).attr('value');
                        if ((op1=='lt' || op1=='gt' || op1=='gte' || op1=='lte' ||
                                op1=='<' || op1=='>' || op1=='>=' || op1=='<='))
                            return true;
                        return false;
                    }).attr('disabled','disabled');
                    if ((op1=='lt' || op1=='gt' || op1=='gte' || op1=='lte' ||
                    op1=='<' || op1=='>' || op1=='>=' || op1=='<='))
                    {
                        op.val('eq');
                        op1='eq';
                    }
                }
                else
                {
                    $('option', op).removeAttr('disabled');
                }

                if (map[term1]=='date')
                {
                    $this.find('.expression-term-value').hide();
                    $this.find('.date-value').show();
                    date=$this.find('.expression-term-date-value').val();
                    format=$this.find('.expression-term-date-format').val()
                    if (date!='TODAY()')
                        val1="DATE('"+date+"','"+format+"')";
                    else
                        val1=date;
                }
                else
                {
                    $this.find('.expression-term-value').show();
                    $this.find('.date-value').hide();
                    val1="'"+val1+"'";
                }
                expr2='';
                expr2+='$('+term1+')';
                expr2+=' '+op1+' ';
                expr2+=' '+val1+' ';
                expr2='('+expr2+')';
                expr+=expr2;
                if (!$this.is(':last-child'))
                    expr+=' '+$this.find('.expression-logical-op').val()+' ';
            });
            $('#_conditional_expression').val(expr);
        }

        $('#terms').on(
            'change', 
            '.expression-term, .expression-comparison-op, .expression-term-value, .expression-term-date-value, .expression-term-date-format, .expression-logical-op', 
            refreshExpression
        );

        $('#container').on('click','.remove-option',function(){
            var option=$(this).closest("tr");
            option.fadeOut('slow',function(){
                $(this).remove();
            });
            refreshExpression();
        });

        $('#container').on('click','.add-option',function(){
            var term=$(tmpl),
                sel=term.find('.expression-term'), selstr='';
            for (var ii=0, ll=conditionalfields.length; ii<ll; ii++)
                selstr+='<option value="'+conditionalfields[ii].field+'">'+conditionalfields[ii].field+'</option>';
            sel.append(selstr);
            $('#terms tbody').append(term);
            if (conditionalfields &&  conditionalfields.length && conditionalfields[0].type!='date')
                term.find('.date-value').hide();
            term.hide().fadeIn('slow');
        });

        $('#useCustomExpression').change(function(){
            if ($(this).is(':checked'))
            {
                refreshExpression();
                useCustom=true;
                overlay.hide().appendTo($('#mygui')).fadeIn('fast');
                $('#_expression_container').slideFadeDown('fast');
            }
            else
            {
                useCustom=false;
                refreshExpression();
                overlay.stop().fadeOut('fast',function(){
                    $(this).remove();
                });
                $('#_expression_container').slideFadeUp('fast');
            }
        });
        $('#useCustomExpression').trigger('change');

        // add first term
        $('#container .add-option').trigger('click');

        // cancel
        $('#container').on('click', '#cancel',function(e){
            e.preventDefault();
            window.parent.jQuery('#TB_closeWindowButton').trigger('click');
            return false;
        });

        // submit
        $('#container').on('click', '#submit',function(e){
            e.preventDefault();
            refreshExpression();
            var shortcodeStart="\n"+'[cred_show_group if="'+$('#_conditional_expression').val().replace(/\"/gm,"'")+'"  mode="'+$('#_fx').val()+'"]'+"\n",
                shortcodeEnd="\n"+'[/cred_show_group]'+"\n";
            cred.app.wrapOrPaste(shortcodeStart, shortcodeEnd);
            window.parent.jQuery('#TB_closeWindowButton').trigger('click');
            return false;
        });
		
		// window ready (needed if any special field is the first on the form)
		$(window).ready(function(){
			refreshExpression();
		});
    });
})(window, jQuery, window.parent.cred_cred);
/* ]]> */
</script>
</head>

<body id='cred_conditional_group' class="wp-core-ui">
    <div class='cred-header'><i class="icon-cred-logo ont-icon-32"><?php _e('Conditional Group','wp-cred'); ?></i></div>
    <p class="cred-header-tip">
		<strong><?php _e("Tip:",'wp-cred'); ?></strong> <?php _e("Make a selection in the editor and the conditional group will wrap around it when inserted.",'wp-cred'); ?>
    </p>
    <!-- container -->
    <div id='container'>
    <form>

        <div>
            <?php _e('Show/Hide Effect:','wp-cred'); ?>
            <select id='_fx'>
            <option value='fade-slide'><?php _e('Fade-Slide','wp-cred'); ?></option>
            <option value='slide'><?php _e('Slide','wp-cred'); ?></option>
            <option value='fade'><?php _e('Fade','wp-cred'); ?></option>
            <option value='none'><?php _e('Use (user-defined) CSS','wp-cred'); ?></option>
            </select>
        </div>

        <div class='mysep'></div>

		<p class="custom-expression-container">
			<label class='cred-label'><input type='checkbox' class='cred-checkbox' id='useCustomExpression' value='1' />
        	    <span><?php _e('Use my Custom Expression','wp-cred'); ?></span>
        	</label>
        </p>

        <div id='_expression_container'>
            <strong><?php _e('Expression:','wp-cred'); ?></strong>
			<span><?php _e('Check Documentation for details and examples','wp-cred'); ?></span>
            <textarea id='_conditional_expression' style='position:relative;width:90%;overflow-y:auto;' rows='5'></textarea>
        </div>

		 <div class='mysep'></div>

		<div id='mygui'>
            <table id='terms'>
                <thead>
					<tr>
						<td><strong><?php _e( 'Field', 'wp-cred' ); ?></strong></td>
						<td><strong><?php _e( 'Operator', 'wp-cred' ); ?></strong></td>
						<td><strong><?php _e( 'Value', 'wp-cred' ); ?></strong></td>
						<td><strong><?php _e( 'Connect', 'wp-cred' ); ?></strong></td>
						<td></td>
					</tr>
                </thead>
                <tbody>
                </tbody>
            </table>
			<p class="add-option-wrapper">
				<a href='javascript:;' class='add-option button' title='<?php echo esc_attr(__( 'Add term', 'wp-cred' )); ?>'><?php _e( 'Add term', 'wp-cred' ); ?></a>
			</p>
        </div>

        <p class="cred-buttons-holder">
			<a href='javascript:;' id='cancel' class='button' title='<?php echo esc_attr(__('Cancel','wp-cred')); ?>'><?php _e('Cancel','wp-cred'); ?></a>
			<input id='submit' type='button' class='button button-primary' value='<?php echo esc_attr(__('Insert','wp-cred')); ?>' />
        </p>
    </form>
    </div>
    <a class='cred-help-link-white' style='position:absolute;top:10px;right:10px' href='<?php echo $help['conditionals']['link']; ?>' target='<?php echo $help_target; ?>' title="<?php echo esc_attr($help['conditionals']['text']); ?>">
        <i class="icon-question-sign"></i>
        <span><?php echo $help['conditionals']['text']; ?></span>
    </a>
</body>
</html>