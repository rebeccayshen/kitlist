<?php if (!defined('ABSPATH'))  die('Security check');

// field options
$options=array(
    'hidden'=>array(
        'has_default_value'=>true,
        'additional'=>array(
                "<input type='text' value='' name='additional_options[generic_type]' /> <span>".__('Generic field type','wp-cred')."</span>"
            ),
        'default_selector'=>'',
        'value_label'=>__('Default value:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),

    'password'=>array(
        'has_default_value'=>false,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default value:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),

    'text'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default value:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),

    'textfield'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default value:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),
    
    /**********************************************************/
    //Added for https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/187372519/comments
    'colorpicker'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default value:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),
    'embed'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default value:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),
    /**********************************************************/
    
    'numeric'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default value:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),

    'integer'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default value:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),
    
    'file'=>array(
        'has_default_value'=>false,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>'',
        'value_field'=>''
    ),
    
    /**********************************************************/
    //Added for https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/187372012/comments
    'video'=>array(
        'has_default_value'=>false,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>'',
        'value_field'=>''
    ),
    
    'audio'=>array(
        'has_default_value'=>false,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>'',
        'value_field'=>''
    ),
    /**********************************************************/

    'image'=>array(
        'has_default_value'=>false,
        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/189585510/comments
        /*'additional'=>array(
                "<input type='text' size='5' value='' name='additional_options[max_width]' /> <span>".__('Max. Width','wp-cred')."</span>",
                "<input type='text' size='5' value='' name='additional_options[max_height]' /> <span>".__('Max. Height','wp-cred')."</span>"
            ),*/
        'default_selector'=>'',
        'value_label'=>'',
        'value_field'=>''
    ),

    'checkbox'=>array(
        'has_default_value'=>true,
        'additional'=>"<label class='cred-label'><input type='checkbox' class='cred-checkbox' value='1' name='additional_options[checked]' /></label> <span class='label'>".__('Checked by default','wp-cred')."</span>",
        'default_selector'=>'',
        'value_label'=>__('Set value:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),

    'select'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>"<label class='cred-label'><input type='radio' value='1' class='cred-radio' name='option_default' /> <span>".__('Default','wp-cred')."</span></label>",
        'value_label'=>'',
        'value_field'=>''
    ),

    'multiselect'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>"<label class='cred-label'><input type='checkbox' value='1' class='cred-checkbox' name='option_default' /> <span>".__('Selected','wp-cred')."</span></label>",
        'value_label'=>'',
        'value_field'=>''
    ),

    'radio'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>"<label class='cred-label'><input type='radio' value='1' class='cred-radio' name='option_default' /> <span>".__('Default','wp-cred')."</span></label>",
        'value_label'=>'',
        'value_field'=>''
    ),

    'checkboxes'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>"<label class='cred-label'><input type='checkbox' value='1' class='cred-checkbox' name='option_default[]' /> <span>".__('Checked','wp-cred')."</span> </label>",
        'value_label'=>'',
        'value_field'=>''
    ),

    'skype'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default Skypename:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),

    'email'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default email:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),

    'url'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default URL:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),

    'phone'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default phone:','wp-cred'),
        'value_field'=>"<input type='text' name='default' value='' />"
    ),

    'textarea'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default value:','wp-cred'),
        'value_field'=>"<textarea rows='10' style='overflow-y:auto;width:300px;' name='default'></textarea>"
    ),

    'wysiwyg'=>array(
        'has_default_value'=>true,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>__('Default value:','wp-cred'),
        'value_field'=>"<textarea rows='10' style='overflow-y:auto;width:300px;' name='default'></textarea>"
    ),

    'date'=>array(
        'has_default_value'=>false,
        'additional'=>'',
        'default_selector'=>'',
        'value_label'=>'',
        'value_field'=>''
    )
);
?>
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
<script id='option-template' type='text/html-template'>
<li class='sub-row sortable-item'>
    <span class='cell'>
        <a class='move-option' href='javascript:;' title='<?php echo esc_attr(__('Move option','wp-cred')); ?>'></a>
    </span>
    <span class='cell'>
        <?php echo $options[$field['type']]['default_selector']; ?>
    </span>
    <span class='cell'>
        <span class='label'><?php _e('Label:','wp-cred'); ?></span><span class='value'><input type='text' size='7' name='label[]' value='' /></span>
    </span>
    <span class='cell'>
        <span class='label'><?php _e('Value:','wp-cred'); ?></span><span class='value'><input type='text' size='7' name='value[]' value='' /></span>
    </span>
	 <span class='cell remove-option-wrapper'>
        <a class='remove-option' href='javascript:;' title='<?php echo esc_attr(__('Remove option','wp-cred')); ?>'></a>
    </span>
</li>
</script>
<script id='field-with-options-template' type='text/html-template'>
<form>
<table>
    <tr class='row'>
        <td class='cell'>
            <span class='label'><?php _e('Field name:','wp-cred'); ?></span>
        </td>
        <td class='cell'>
            <input type='text' name='name' value='' />
        </td>
    </tr>
</table>
<div class='mysep'></div>
<table>
    <tr class='row'>
        <td class='toptop'>
            <?php _e('Use a shortcode instead of options','wp-cred'); ?>
        </td>
        <td class='toptop'>
            <input type="text" name="shortcode" size='20' value="" placeholder="<?php _e('paste shortcode','wp-cred'); ?>" />
            <span class="cred-tip-link" data-pointer-content="#shortcode_tip">
                <i class="icon-question-sign"></i>
            </span>
        </td>
    </tr>
    <tr class='row'>
        <td class='cell toptop'>
            <span class='label'><?php _e('Options:','wp-cred'); ?></span>
        </td>
        <td class='cell'>
            <ul id='options-container' class='ui-sortable'>
                <li class='sub-row sortable-item'>
                    <span class='cell'>
                        <a class='move-option' href='javascript:;' title='<?php echo esc_attr(__('Move option','wp-cred')); ?>'></a>
                    </span>
                    <span class='cell'>
                        <?php echo $options[$field['type']]['default_selector']; ?>
                    </span>
                    <span class='cell'>
                        <span class='label'><?php _e('Label:','wp-cred'); ?></span>
						<span class='value'> <input type='text' size='7' name='label[]' value='' />
					</span>
                    </span>
                    <span class='cell'>
                        <span class='label'><?php _e('Value:','wp-cred'); ?></span>
						<span class='value'><input type='text' size='7' name='value[]' value='' /></span>
                    </span>
					<span class='cell remove-option-wrapper'>
                        <a class='remove-option' href='javascript:;' title='<?php echo esc_attr(__( 'Remove option', 'wp-cred' )); ?>'></a>
                    </span>
                </li>
            </ul>
			<p class="add-option-wrapper">
				<a href='javascript:;' class='add-option button' title='<?php echo esc_attr(__('Add option','wp-cred')); ?>'><?php _e('Add option','wp-cred'); ?></a>
			</p>
        </td>
    </tr>
</table>
<div class='mysep'></div>
<table>
    <tr class='row'>
        <td class='cell'>
            <span class='label'><?php _e('CSS Class:','wp-cred'); ?></span>
        </td>
        <td class='cell'>
            <input type='text' name='class' value='' />
        </td>
    </tr>
</table>
<div class='mysep'></div>
<table>
    <tr class='row'>
        <td class='cell'>
            <ul>
            <?php if ($field['type']!='checkboxes') { ?>
            	<li>
            		<label class='cred-label'>
						<input type='checkbox' class='cred-checkbox' name='required' value='1' />
						<span class='label'><?php _e('Required','wp-cred'); ?></span>
					</label>
            	</li>
            <?php } ?>

            	<li>
            		<label class='cred-label'>
						<input type='checkbox' class='cred-checkbox' name='validate_format' value='1' />
						<span class='label'><?php _e('Validate Format','wp-cred'); ?></span>
					</label>
            	</li>

            	<li>
            		<label class='cred-label'>
						<input type='checkbox' class='cred-checkbox' name='persist' value='1' />
						<span class='label'><?php _e('Save in Database','wp-cred'); ?></span>
					</label>
            	</li>
            </ul>
        </td>
    </tr>
</table>
<table>
    <tr class='row'>
        <td class='cell'>
            <p class="cred-buttons-holder">
				<a href='javascript:;' id='cancel' class='button' title='<?php echo esc_attr(__('Cancel','wp-cred')); ?>'><?php _e('Cancel','wp-cred'); ?></a>
				<input id='submit' type='button' class='button button-primary' value='<?php echo esc_attr(__('Insert','wp-cred')); ?>' />
			</p>
        </td>
    </tr>
</table>
</form>
</script>
<script id='simple-field-template' type='text/html-template'>
<form>
<table>
    <tr class='row'>
        <td class='cell'>
            <span class='label'><?php _e('Field name:','wp-cred'); ?>dddd</span>
        </td>
        <td class='cell'>
            <input type='text' name='name' value='' />
        </td>
    </tr>
<?php if ($field['type']=='checkbox') { ?>
    <tr class='row'>
        <td class='cell'>
            <span class='label'><?php _e('Label:','wp-cred'); ?></span>
        </td>
        <td class='cell'>
            <input type='text' name='label' value='' />
        </td>
    </tr>    
<?php } ?>
</table>
<div class='mysep'></div>
<table>
    <?php if ($options[$field['type']]['has_default_value']) { ?>
    <tr class='row'>
        <td class='cell'>
            <span class='label'><?php echo $options[$field['type']]['value_label']; ?></span>
        </td>
        <td class='cell'>
            <?php echo $options[$field['type']]['value_field']; ?><br />
        </td>
    </tr>
    <?php } ?>
    <?php if (!empty($options[$field['type']]['additional'])) { ?>
    <tr class='row'>
        <td class='cell'>
            <span class='label'><?php _e('Additional Options:','wp-cred'); ?></span>
        </td>
        <td class='cell'>
            <?php echo (is_array($options[$field['type']]['additional']))?implode('<br />',$options[$field['type']]['additional']):$options[$field['type']]['additional']; ?>
        </td>
    </tr>
    <?php } ?>
</table>
<div class='mysep'></div>
<table>
    <tr class='row'>
        <td class='cell'>
            <span class='label'><?php _e('CSS Class:','wp-cred'); ?></span>
        </td>
        <td class='cell'>
            <input type='text' name='class' value='' />
        </td>
    </tr>
</table>
<div class='mysep'></div>
<table>
    <tr class='row'>
		<td class='cell' >
			<ul>
	<?php if ($field['type']!='hidden') { ?>
				<li>
					<label class='cred-label'>
						<input type='checkbox' class='cred-checkbox' name='required' value='1' />
                        <span class='label'><?php _e('Required','wp-cred'); ?></span>
					</label>
				</li>
	<?php } ?>
				<li>
					<label class='cred-label'>
                        <input type='checkbox' class='cred-checkbox' name='validate_format' value='1' />
                        <span class='label'><?php _e('Validate Format','wp-cred'); ?></span>
                    </label>
				</li>

				<li>
					<label class='cred-label'>
                        <input type='checkbox' class='cred-checkbox' name='persist' value='1' />
                        <span class='label'><?php _e('Save in Database','wp-cred'); ?></span>
                    </label>
				</li>
			</ul>
        </td>
    </tr>
</table>
<table class="submit-table">
    <tr class='row'>
        <td class='cell'>
            <p class="cred-buttons-holder">
				<a href='javascript:;' id='cancel' class='button' title='<?php echo esc_attr(__('Cancel','wp-cred')); ?>'><?php _e('Cancel','wp-cred'); ?></a>
				<input id='submit' type='button' class='button button-primary' value='<?php echo esc_attr(__('Insert','wp-cred')); ?>' />
			</p>
        </td>
    </tr>
</table>
</form>
</script>
<!-- templates end -->
<!-- logic -->
<script type='text/javascript'>
/* <![CDATA[ */
(function(window, $, cred, gui, undefined){
    $(function(){
        var field, tmpl, $container=$('#container');
        field=/*$.parseJSON('*/<?php echo json_encode($field); ?>/*')*/;
        
        if (field.parameters.options)
            tmpl=$('#field-with-options-template').html();
        else
            tmpl=$('#simple-field-template').html();

        $container.empty().append(tmpl);

        // add handlers
        $container.on('click', '.cred-tip-link .icon-question-sign',function(e){
            e.preventDefault();
            e.stopPropagation();
            
            var $this=$(this), $el=$this.parent();
            
            if ($this.hasClass('active'))
            {
                $this[0]._pointer && $this[0]._pointer.pointer('close');
                return;
            }
            
            $this.addClass('active');
            // GUI framework handles pointers now
            $this[0]._pointer=gui.Popups.pointer($el, {
                message: $($el.data('pointer-content')).html(),
                class: 'cred-pointer',
                position: {
                    edge: 'top',
                    align: 'center',
                    offset: '15 0'
                },
               callback: function(){
                    //$this[0]._pointer=null;
                    $this.removeClass('active');
                }
            });
        });
        
        $container.on('click','.add-option',function(){
            var option=$($('#option-template').html());
            $('#options-container').append(option);
            option.hide().fadeIn('slow');
            $('#options-container.ui-sortable').sortable( 'refresh' );
        });
        
        $container.on('click','.remove-option',function(){
            var option=$(this).closest(".sortable-item");
            option.fadeOut('slow',function(){
                $(this).remove();
                $('#options-container.ui-sortable').sortable( 'refresh' );
            });
        });
        
        // Sort and Drag
        $('#options-container.ui-sortable').sortable({
            //revert: true,
            items: '.sortable-item',
            containment: 'parent',
            placeholder: 'sortable-placeholder',
            axis: 'y',
            forcePlaceholderSize: true,
            tolerance: 'pointer',
            cursor:'move',
            handle: 'a.move-option'
        });

        // cancel
        $container.on('click', '#cancel',function(event){
            event.preventDefault();
            window.parent.jQuery('#TB_closeWindowButton').trigger('click');
            return false;
        });

        // submit
        $container.on('click', '#submit',function(e){
            e.preventDefault();
            var _name,_default='',_shortcode, _class,_required,_validate_format,_persist,
                _default_option=[],_options,_additional_options={
                    'checked':0,
                    'max_width':null,
                    'max_height':null
                };
                
            if ($('input[name="label"]').val()!='') {
                var _label;
                _label=$.trim($('input[name="label"]').val());
            }

            // get check values
            _name=$.trim($('input[name="name"]').val());
            _class=$.trim($('input[name="class"]').val());
            _required=$('input[name="required"]').length && $('input[name="required"]').is(':checked');
            _validate_format=$('input[name="validate_format"]').length && $('input[name="validate_format"]').is(':checked');
            _persist=$('input[name="persist"]').length && $('input[name="persist"]').is(':checked');
            if (!_name || _name=='')
            {
                alert('<?php echo esc_js(__('No name for field!','wp-cred')); ?>');
                $('input[name="name"]').focus();
                return false;
            }
            var forbidden=/[#@&\^\*\+\=\!\~\"\'\!\%\$\(\)\{\}\s]/g;
            if (forbidden.test(_name))
            {
                alert('<?php echo esc_js(__('Field name contains special characters or spaces!','wp-cred')); ?>');
                $('input[name="name"]').focus();
                return false;
            }
            if (field.parameters.options)
            {
                var options_container=$('#options-container');
                
                _shortcode=$.trim($('input[name="shortcode"]').val());
                
                if ((!_shortcode || ''==_shortcode) && options_container.children('.sortable-item').length==0)
                {
                    alert('<?php echo esc_js(__('No options for field!','wp-cred')); ?>');
                    return false;
                }

                _options=[];
                options_container.children('.sortable-item').each(function(){
                    var _label,_value;

                    _label=$.trim($(this).find('input[name="label[]"]').val());
                    _value=$.trim($(this).find('input[name="value[]"]').val());

                    if ($(this).find('input[name^="option_default"]').is(':checked'))
                    {
                        _default_option.push(_value);
                    }
                    _options.push({'label':_label,'value':_value});
                });
            }
            else
            {
                if ($('input[name="default"]').length)
                    _default=$.trim($('input[name="default"]').val());
                else if ($('textarea[name="default"]').length)
                    _default=$.trim($('textarea[name="default"]').val());
                _default=_default.replace(/([\n\r\t])/g,function(a, b){
                    switch(b)
                    {
                        case '\n':return '\\n';
                        case '\r':return '\\r';
                        case '\t':return '\\t';
                        default: return b;
                    }
                });
                /*if (field.type=='numeric' && !(/^\d*$/.test(_default)))
                {
                    alert('<?php _e('Value not numeric!','wp-cred'); ?>');
                    $('input[name="default"]').focus();
                    return false;
                }*/ // allow shortcodes
                if (field.type=='checkbox' && _default=='')
                {
                    alert('<?php echo esc_js(__('Checkbox should have a value other than blank!','wp-cred')); ?>');
                    $('input[name="default"]').focus();
                    return false;
                }
            }

            // generate shortcode
            var shortcode=''
            if (field.parameters.options)
            {
                shortcode='[cred_generic_field field="'+_name+'" type="'+field.type+'" class="'+_class+'" urlparam=""]\n';
                shortcode+='{\n';
                shortcode+='"required":'+((_required)?1:0)+',\n';
                shortcode+='"validate_format":'+((_validate_format)?1:0)+',\n';
                shortcode+='"persist":'+((_persist)?1:0)+',\n';
                if (_default_option.length==0)
                    shortcode+='"default":[],\n';
                else
                {
                    shortcode+='"default":[';
                    for (var ii=0; ii<_default_option.length; ii++)
                    {
                        shortcode+='"'+_default_option[ii]+'"'+((ii<_default_option.length-1)?',':'');
                    }
                    shortcode+='],\n';
                }
                if (!_shortcode || ''==_shortcode)
                {
                    shortcode+='"options":[\n';
                    for (var ii=0; ii<_options.length ; ii++)
                    {
                        //Fixing double quote escape
                        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/180402027/comments
                        var ov = _options[ii].value;
                        ov = ov.replace(/"/g, '\\"');
                        var ol = _options[ii].label;
                        ol = ol.replace(/"/g, '\\"');
                        //##############################################################
                        
                        shortcode+='{"value":"'+ ov +'","label":"'+ ol +'"}';
                        if (ii==_options.length-1)
                            shortcode+='\n';
                        else
                            shortcode+=',\n';
                    }
                    shortcode+=']\n';
                }
                // allow to populate options with a custom shortcode
                else
                {
                    shortcode+='"options":[ ' + _shortcode + ' ]\n';
                }
                if (field.type=='checkbox') {
                    if (!_label) _label = '';
                    shortcode+=',"label":"'+_label+'"\n';
                }
                shortcode+='}\n';
                shortcode+='[/cred_generic_field]\n';
            }
            else
            {

                var _additional='';
                if (field.type=='checkbox')
                {
                   if ($('input[name="additional_options[checked]"]').is(':checked'))
                    _additional+='"checked":1,\n';
                    else
                    _additional+='"checked":0,\n';
                }
                if (field.type=='image')
                {
                    var _max_width=$.trim($('input[name="additional_options[max_width]"]').val());
                    if (/^\d*$/.test(_max_width))
                    {
                        if (_max_width!='')
                            _additional+='"max_width":'+_max_width+',\n';
                    }
                    else
                    {
                        alert('<?php echo esc_js(__('Max. Width not numeric','wp-cred')); ?>');
                        return false;
                    }

                    var _max_height=$.trim($('input[name="additional_options[max_height]"]').val());
                    if (/^\d*$/.test(_max_height))
                    {
                        if (_max_height!='')
                            _additional+='"max_height":'+_max_height+',\n';
                    }
                    else
                    {
                        alert('<?php echo esc_js(__('Max. Height not numeric','wp-cred')); ?>');
                        return false;
                    }
                }
                if (field.type=='hidden')
                {
                    var _generic_type=$.trim($('input[name="additional_options[generic_type]"]').val());
					if (_generic_type!='')
						_additional+='"generic_type":"'+_generic_type+'",\n';
                }
                shortcode='[cred_generic_field field="'+_name+'" type="'+field.type+'" class="'+_class+'" urlparam=""]\n';
                shortcode+='{\n';
                shortcode+='"required":'+((_required)?1:0)+',\n';
                shortcode+='"validate_format":'+((_validate_format)?1:0)+',\n';
                shortcode+='"persist":'+((_persist)?1:0)+',\n';
                shortcode+=_additional;
                
                //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196897073/comments
                /*_default = _default.replace(/[\u00A0-\u2666<>\&]/g, function(i) {
                    return '&#'+i.charCodeAt(0)+';';
                 });*/
                
                _default = jQuery('<div />').text(_default).html();
                _default = _default.replace(/\"/g,'&quot;');
                
                shortcode+='"default":"'+_default+'"';
                if (field.type=='checkbox') {
                    if (!_label) _label = '';
                    shortcode+=',\n"label":"'+_label+'"\n';
                } else {
                    shortcode+='\n';
                }
                shortcode+='}\n';
                shortcode+='[/cred_generic_field]\n';
            }
            cred.app.insert(shortcode);
            cred.app.dispatch('cred.insertField');
            window.parent.jQuery('#TB_closeWindowButton').trigger('click');
           // window.parent.jQuery('#wp-content-media-buttons i.cred-close-button').trigger('click');
            return false;
        });

    });
})(window, jQuery, window.parent.cred_cred, cred_gui);
/* ]]> */
</script>
</head>

<body id='cred_generic_fields' class="wp-core-ui">
<div class='cred-header'><i class="icon-cred-logo ont-icon-32"></i><?php _e('Generic Field','wp-cred'); ?></div>
    <!-- container -->
    <div id='container'>
    </div>
    <a class='cred-help-link-white' style='position:absolute;top:10px;right:10px' href='<?php echo $help['generic_fields_settings']['link']; ?>' target='<?php echo $help_target; ?>' title="<?php echo esc_attr($help['generic_fields_settings']['text']); ?>">
		<i class="icon-question-sign"></i>
		<span><?php echo $help['generic_fields_settings']['text']; ?></span>
	</a>
    
    <!-- tisp -->
    <div style="display:none">
        <div id="shortcode_tip">
            <h3><?php _e('Use a Shortcode', 'wp-cred-pay'); ?></h3>
            <p><?php _e('When options for a generic field need to be generated dynamically, a shortcode can be used to generate the field options on the fly. Using a View shortcode to generate options based on criteria is a usual case. Any shortcode which will generate a valid JSON format of options can be used.', 'wp-cred'); ?></p>
        </div>
    </div>
</body>
</html>
