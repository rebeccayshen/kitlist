<?php

/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk_new/embedded/classes/CredForm.php $
 * $LastChangedDate: 2015-03-20 10:15:22 +0100 (ven, 20 mar 2015) $
 * $LastChangedRevision: 32512 $
 * $LastChangedBy: francesco $
 *
 */

/**
 * Description of CredForm
 *
 * @author onTheGoSystem
 */
class CredForm {

    public static $current_postid;
    public $controls;
    public $is_submit_success = false;
    public $attributes;
    public $language;
    public $method;
    public $actionUri;
    public $preview;
    public $form_properties;
    public $extra_parameters = array();
    public $top_messages = array();
    public $field_messages = array();
    public $preview_messages = array();

    /**
     * @deprecated
     * @var type array()
     */
    public $form_errors = array();

    /**
     * @deprecated
     * @var type array()
     */
    public $form_messages = array();
    private $_request;
    protected $_supported_date_formats = array('F j, Y', //December 23, 2011
        'Y/m/d', // 2011/12/23
        'm/d/Y', // 12/23/2011
        'd/m/Y' // 23/22/2011
    );
    public $_validation_errors = array();

    public function __construct($form_id, $form_type, $current_postid, $actionUri, $preview = false) {
        $this->form_id = $form_id;
        $this->form_type = $form_type;

        self::$current_postid = $current_postid;
        $this->actionUri = $actionUri;
        $this->preview = $preview;
        $this->method = StaticClass::METHOD;

        $_files = array();

        //Unuseful $_FILES value
        /* foreach ($_FILES as $name => $value) {
          if (is_array($value['name'])) {
          foreach ($value['name'] as $i => $iname) {
          if (isset($value['error'][$i]) && $value['error'][$i] == 0) {
          $_files[$name][] = $iname;
          } else {
          $_files[$name][] = "";
          }
          }
          } else {
          if ($value['error'] == 0) {
          $_files[$name] = $value['name'];
          } else {
          $_files[$name] = "";
          }
          }
          }
          $req = array_merge($_REQUEST, $_files);
         */
        $req = $_REQUEST;
        //Fixed https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/191483153/comments#297748160
        $req = stripslashes_deep($req);
        //##########################################################################################################

        $this->_request = $req;
        $this->setControls();
        return $this->getForm();
    }

    /**
     * @deprecated function since 1.2.6
     * @param type $params
     */
    function set_extra_parameters($params) {
        $this->extra_parameters = array_merge($this->extra_parameters, $params);
    }

    /**
     * add a field content to a form
     * @param type $type
     * @param type $name
     * @param type $value
     * @param type $attributes
     * @param type $field
     * @return type
     */
    function add($type, $name, $value, $attributes, $field = null) {
        $title = isset($field) ? $field['name'] : $name;
        $title = isset($field['label']) ? $field['label'] : $title;

        //Check the case when generic field checkbox does not have label property at all
        if ($type == 'checkbox' && !isset($field['plugin_type'])) {
            if (!isset($field['label']))
                $title = "";
        }

        $f = array();
        $f['type'] = $type;
        $f['name'] = $name;
        if (isset($field['cred_custom'])) {
            $f['cred_custom'] = true;
        }
        $f['title'] = $title;
        $f['value'] = $value;
        $f['attr'] = $attributes;
        $f['data'] = is_array($field) && array_key_exists('data', $field) ? @$field['data'] : array();

        if (isset($field['plugin_type'])) {
            $f['plugin_type'] = $field['plugin_type'];
        }
        $this->form_properties['fields'][] = $f;
        return $f;
    }

    /**
     * add virtual information about a field
     * @param type $type
     * @param type $name
     * @param type $value
     * @param type $attributes
     * @param type $field
     * @return type
     */
    function noadd($type, $name, $value, $attributes, $field = null) {
        $title = isset($field) ? $field['name'] : $name;

        $f = array();
        $f['type'] = $type;
        $f['name'] = $name;
        $f['title'] = $title;
        $f['value'] = $value;
        $f['attr'] = $attributes;
        $f['data'] = is_array($field) && array_key_exists('data', $field) ? @$field['data'] : array();

        if (isset($field['plugin_type'])) {
            $f['plugin_type'] = $field['plugin_type'];
        }
        return $f;
    }

    //########### CALLBACKS

    public $form_id;
    public $_post_ID;
    public $out_;
    public $_formHelper;
    public $_formData;
    public $_shortcodeParser;
    public $_form_content;
    public $_js;
    public $_content;
    public $isForm = false;
    public $isUploadForm = false;

// parse form shortcode [credform]
    public function cred_form_shortcode($atts, $content = '') {
        extract(shortcode_atts(array(
            'class' => ''
                        ), $atts));

        // return a placeholder instead and store the content in _form_content var
        $this->_form_content = $content;
        $this->form_id = $this->form_properties['name'];
        $this->isForm = true;

        if (!empty($class)) {
            $this->_attributes['class'] = esc_attr($class);
        }

        return StaticClass::FORM_TAG . '_' . $this->form_properties['name'] . '%';
    }

// parse form shortcode [credform]
    public function cred_user_form_shortcode($atts, $content = '') {
        extract(shortcode_atts(array(
            'class' => ''
                        ), $atts));

        // return a placeholder instead and store the content in _form_content var
        $this->_form_content = $content;
        $this->form_id = $this->form_properties['name'];
        $this->isForm = true;

        if (!empty($class)) {
            $this->_attributes['class'] = esc_attr($class);
        }

        return StaticClass::FORM_TAG . '_' . $this->form_properties['name'] . '%';
    }

    /**
     * CRED-Shortcode: cred_field
     *
     * Description: Render a form field (using fields defined in wp-types plugin and / or Taxonomies)
     *
     * Parameters:
     * 'field' => Field slug name
     * 'post' => [optional] Post Type where this field is defined 
     * 'value'=> [optional] Preset value (does not apply to all field types, eg taxonomies)
     * 'taxonomy'=> [optional] Used by taxonomy auxilliary fields (eg. "show_popular") to signify to which taxonomy this field belongs
     * 'type'=> [optional] Used by taxonomy auxilliary fields (like show_popular) to signify which type of functionality it provides (eg. "show_popular")
     * 'display'=> [optional] Used by fields for Hierarchical Taxonomies (like Categories) to signify the mode of display (ie. "select" or "checkbox")
     * 'single_select'=> [optional] Used by fields for Hierarchical Taxonomies (like Categories) to signify that select field does not support multi-select mode
     * 'max_width'=>[optional] Max Width for image fields
     * 'max_height'=>[optional] Max Height for image fields
     * 'max_results'=>[optional] Max results in parent select field
     * 'order'=>[optional] Order for parent select field (title or date)
     * 'ordering'=>[optional] Ordering for parent select field (asc, desc)
     * 'required'=>[optional] Whether parent field is required, default 'false'
     * 'no_parent_text'=>[optional] Text for no parent selection in parent field
     * 'select_text'=>[optional] Text for required parent selection
     * 'validate_text'=>[optional] Text for error message when parebt not selected
     * 'placeholder'=>[optional] Text to be used as placeholder (HTML5) for text fields, default none
     * 'readonly'=>[optional] Whether this field is readonly (cannot be edited, applies to text fields), default 'false'
     * 'urlparam'=> [optional] URL parameter to be used to give value to the field
     *
     * Example usage:
     *
     *  Render the wp-types field "Mobile" defined for post type Agent
     * [cred_field field="mobile" post="agent" value="555-1234"]
     *
     * Link:
     *
     *
     * Note:
     *  'value'> translated automatically if WPML translation exists
     *  'taxonomy'> used with "type" option
     *  'type'> used with "taxonomy" option
     *
     * */
    // parse field shortcodes [cred_field]
    public function cred_field_shortcodes($atts) {

        $form = &$this->_formData;
        $formHelper = $this->_formHelper;
        $_fields = $form->getFields();
        $form_type = $_fields['form_settings']->form['type'];
        $post_type = $_fields['form_settings']->post['post_type'];

        extract(shortcode_atts(array(
            'class' => '',
            'post' => '',
            'field' => '',
            'value' => null,
            'urlparam' => '',
            'placeholder' => null,
            'escape' => 'false',
            'readonly' => 'false',
            'taxonomy' => null,
            'single_select' => null,
            'type' => null,
            'display' => null,
            'max_width' => null,
            'max_height' => null,
            'max_results' => null,
            'order' => null,
            'ordering' => null,
            'required' => 'false',
            'no_parent_text' => __('No Parent', 'wp-cred'),
            'select_text' => __('-- Please Select --', 'wp-cred'),
            'validate_text' => $formHelper->getLocalisedMessage('field_required'),
            'show_popular' => false
                        ), $atts));

        if ($field == 'form_messages') {
            $post_not_saved_singular = str_replace("%PROBLEMS_UL_LIST", "", $formHelper->getLocalisedMessage('post_not_saved_singular'));
            $post_not_saved_plural = str_replace("%PROBLEMS_UL_LIST", "", $formHelper->getLocalisedMessage('post_not_saved_plural'));
            return '<label id="wpt-form-message" 
                data-message-single="' . esc_js($post_not_saved_singular) . '"
                data-message-plural="' . esc_js($post_not_saved_plural) . '" 
                style="display:none;" class="wpt-top-form-error wpt-form-error">test</label><!CRED_ERROR_MESSAGE!>';
        }
        // make boolean
        $escape = false; //(bool)(strtoupper($escape)==='TRUE');
        // make boolean
        $readonly = (bool) (strtoupper($readonly) === 'TRUE');
        if (!$taxonomy) {
            $fieldObj = null;
            if (
                    array_key_exists('post_fields', $this->out_['fields']) &&
                    is_array($this->out_['fields']['post_fields']) &&
                    in_array($field, array_keys($this->out_['fields']['post_fields']))
            ) {
                if ($post != $post_type)
                    return '';

                $field = $this->out_['fields']['post_fields'][$field];
                $name = $name_orig = $field['slug'];

                if (isset($field['plugin_type_prefix']))
                    $name = /* 'wpcf-' */$field['plugin_type_prefix'] . $name;

                if ('credimage' == $field['type'] ||
                        'image' == $field['type'] ||
                        'file' == $field['type'] ||
                        'credfile' == $field['type']) {
                    $ids = $formHelper->translate_field($name, $field, array(
                        'preset_value' => $value,
                        'urlparam' => $urlparam,
                        'is_tax' => false,
                        'max_width' => $max_width,
                        'max_height' => $max_height));
                } else {
                    $ids = $formHelper->translate_field($name, $field, array(
                        'preset_value' => $value,
                        'urlparam' => $urlparam,
                        'value_escape' => $escape,
                        'make_readonly' => $readonly,
                        'placeholder' => $placeholder));
                }

                if ('credimage' == $field['type'] ||
                        'image' == $field['type'] ||
                        'file' == $field['type'] ||
                        'credfile' == $field['type']) {
                    $fieldObj = $formHelper->cred_translate_field($name, $field, array(
                        'class' => $class,
                        'preset_value' => $value,
                        'urlparam' => $urlparam,
                        'is_tax' => false,
                        'max_width' => $max_width,
                        'max_height' => $max_height));
                } else {
                    $fieldObj = $formHelper->cred_translate_field($name, $field, array(
                        'class' => $class,
                        'preset_value' => $value,
                        'urlparam' => $urlparam,
                        'value_escape' => $escape,
                        'make_readonly' => $readonly,
                        'placeholder' => $placeholder));
                }

                // check which fields are actually used in form
                $this->out_['form_fields'][$name_orig] = $ids;
                $this->out_['form_fields_info'][$name_orig] = array(
                    'type' => $field['type'],
                    'repetitive' => (isset($field['data']['repetitive']) && $field['data']['repetitive']),
                    'plugin_type' => (isset($field['plugin_type'])) ? $field['plugin_type'] : '',
                    'name' => $name,
                );
                /* $out='';
                  foreach ($ids as $id) {
                  $out.= "[render_cred_field post='{$post}' field='{$id}']";
                  }
                  return $out; */
            } elseif (
                    array_key_exists('parents', $this->out_['fields']) &&
                    is_array($this->out_['fields']['parents']) &&
                    in_array($field, array_keys($this->out_['fields']['parents']))
            ) {
                $name = $name_orig = $field;
                $field = $this->out_['fields']['parents'][$field];
                $potential_parents = CRED_Loader::get('MODEL/Fields')->getPotentialParents($field['data']['post_type'], $this->_post_ID, $max_results, 'title', 'ASC');
                $field['data']['options'] = array();

                $default_option = '';
                // enable setting parent form url param
                if (array_key_exists('parent_' . $field['data']['post_type'] . '_id', $_GET))
                    $default_option = $_GET['parent_' . $field['data']['post_type'] . '_id'];

                $required = (bool) (strtoupper($required) === 'TRUE');
                if (!$required) {
                    $field['data']['options']['-1'] = array(
                        'title' => $no_parent_text,
                        'value' => '-1',
                        'display_value' => '-1'
                    );
                } else {
                    $field['data']['options']['-1'] = array(
                        'title' => $select_text,
                        'value' => '',
                        'display_value' => '',
                        'dummy' => true
                    );
                    $field['data']['validate'] = array(
                        'required' => array('message' => $validate_text, 'active' => 1)
                    );
                }
                foreach ($potential_parents as $ii => $option) {
                    $option_id = (string) ($option->ID);
                    $field['data']['options'][$option_id] = array(
                        'title' => $option->post_title,
                        'value' => $option_id,
                        'display_value' => $option_id
                    );
                }
                $field['data']['options']['default'] = $default_option;

                $add_opt = array('preset_value' => $value, 'urlparam' => $urlparam, 'max_width' => $max_width, 'max_height' => $max_height, 'class' => $class);
                $ids = $formHelper->translate_field($name, $field, $add_opt);
                $fieldObj = $formHelper->cred_translate_field($name, $field, $add_opt);

                // check which fields are actually used in form
                $this->out_['form_fields'][$name_orig] = $ids;
                $this->out_['form_fields_info'][$name_orig] = array(
                    'type' => $field['type'],
                    'repetitive' => (isset($field['data']['repetitive']) && $field['data']['repetitive']),
                    'plugin_type' => (isset($field['plugin_type'])) ? $field['plugin_type'] : '',
                    'name' => $name
                );
                /* $out='';
                  foreach ($ids as $id) {
                  $out.= "[render_cred_field field='{$id}']";
                  }
                  return $out; */
            } elseif (
                    (array_key_exists('form_fields', $this->out_['fields']) &&
                    is_array($this->out_['fields']['form_fields']) &&
                    in_array($field, array_keys($this->out_['fields']['form_fields']))) ||
                    (array_key_exists('user_fields', $this->out_['fields']) &&
                    is_array($this->out_['fields']['user_fields']) &&
                    in_array($field, array_keys($this->out_['fields']['user_fields'])))
            ) {
                $name = $name_orig = $field;
                $field = $this->out_['fields']['form_fields'][$field];
                $add_opt = array('preset_value' => $value, 'urlparam' => $urlparam, 'max_width' => $max_width, 'max_height' => $max_height, 'class' => $class);
                $ids = $formHelper->translate_field($name, $field, $add_opt);
                $fieldObj = $formHelper->cred_translate_field($name, $field, $add_opt);

                //https://onthegosystems.myjetbrains.com/youtrack/issue/cred-161
                if ($form_type == 'edit' &&
                        ($fieldObj['name'] == 'user_pass' ||
                        $fieldObj['name'] == 'user_pass2')) {

                    if (isset($fieldObj['data']['validate']) &&
                            isset($fieldObj['data']['validate']['required']))
                        unset($fieldObj['data']['validate']['required']);
                }

                // check which fields are actually used in form
                $this->out_['form_fields'][$name_orig] = $ids;
                $this->out_['form_fields_info'][$name_orig] = array(
                    'type' => $field['type'],
                    'repetitive' => (isset($field['data']['repetitive']) && $field['data']['repetitive']),
                    'plugin_type' => (isset($field['plugin_type'])) ? $field['plugin_type'] : '',
                    'name' => $name
                );
                /* $out='';
                  foreach ($ids as $id) {
                  $out.= "[render_cred_field field='{$id}']";
                  }
                  return $out; */
            } elseif (
                    array_key_exists('extra_fields', $this->out_['fields']) && is_array($this->out_['fields']['extra_fields']) && in_array($field, array_keys($this->out_['fields']['extra_fields']))
            ) {
                $field = $this->out_['fields']['extra_fields'][$field];
                $name = $name_orig = $field['slug'];
                $add_opt = array('preset_value' => $value, 'urlparam' => $urlparam, 'max_width' => $max_width, 'max_height' => $max_height, 'class' => $class);
                $ids = $formHelper->translate_field($name, $field, $add_opt);
                $fieldObj = $formHelper->cred_translate_field($name, $field, $add_opt);
                // check which fields are actually used in form
                $this->out_['form_fields'][$name_orig] = $ids;
                $this->out_['form_fields_info'][$name_orig] = array(
                    'type' => $field['type'],
                    'repetitive' => (isset($field['data']['repetitive']) && $field['data']['repetitive']),
                    'plugin_type' => (isset($field['plugin_type'])) ? $field['plugin_type'] : '',
                    'name' => $name
                );
                /* $out='';
                  foreach ($ids as $id) {
                  $out.= "[render_cred_field field='{$id}']";
                  }
                  return $out; */
            }
            // taxonomy field
            elseif (
                    array_key_exists('taxonomies', $this->out_['fields']) && is_array($this->out_['fields']['taxonomies']) && in_array($field, array_keys($this->out_['fields']['taxonomies']))
            ) {
                $field = $this->out_['fields']['taxonomies'][$field];
                $name = $name_orig = $field['name'];
                $single_select = ($single_select === 'true');
                $add_opt = array('preset_value' => $display, 'is_tax' => true, 'single_select' => $single_select, 'show_popular' => $show_popular);
                $ids = $formHelper->translate_field($name, $field, $add_opt);
                $fieldObj = $formHelper->cred_translate_field($name, $field, $add_opt);
                // check which fields are actually used in form
                $this->out_['form_fields'][$name_orig] = $ids;
                $this->out_['form_fields_info'][$name_orig] = array(
                    'type' => $field['type'],
                    'repetitive' => (isset($field['data']['repetitive']) && $field['data']['repetitive']),
                    'plugin_type' => (isset($field['plugin_type'])) ? $field['plugin_type'] : '',
                    'name' => $name,
                    'display' => $value,
                );
                /* $out='';
                  foreach ($ids as $id) {
                  $out.= "[render_cred_field field='{$id}']";
                  }
                  return $out; */
            }

            if ($fieldObj) {
                return $this->renderField($fieldObj);
            } elseif (current_user_can('manage_options')) {
                return sprintf(
                        '<p class="alert">%s</p>', sprintf(
                                __('There is a problem with %s field. Please check CRED form.', 'wp-cred'), $field
                        )
                );
            }
        } else {
            if (
                    array_key_exists('taxonomies', $this->out_['fields']) && is_array($this->out_['fields']['taxonomies']) && in_array($taxonomy, array_keys($this->out_['fields']['taxonomies'])) && in_array($type, array('show_popular', 'add_new'))
            ) {
                if (// auxilliary field type matches taxonomy type
                        ($type == 'show_popular' && !$this->out_['fields']['taxonomies'][$taxonomy]['hierarchical']) ||
                        ($type == 'add_new' && $this->out_['fields']['taxonomies'][$taxonomy]['hierarchical'])
                ) {
                    // add a placeholder for the 'show_popular' or 'add_new' buttons.
                    // the real buttons will be copied to this position via js
                    //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/195150507/comments
                    //added data-label text from value shortcode attribute
                    return '<div class="js-taxonomy-button-placeholder" data-taxonomy="' . $taxonomy . '" data-label="' . $value . '" style="display:none"></div>';
                    /*
                      $field=array(
                      'taxonomy'=>$this->out_['fields']['taxonomies'][$taxonomy],
                      'type'=>$type,
                      'master_taxonomy'=>$taxonomy
                      );
                      $name=$name_orig=$taxonomy.'_'.$type;
                      $ids=$formHelper->cred_translate_field($name, $field, array('preset_value'=>$value,'is_tax'=>true));
                      // check which fields are actually used in form
                      //$this->_form_fields[$name_orig]=$ids;
                      $out='';
                      foreach ($ids as $id)
                      $out.= "[render_cred_field field='{$id}']";
                      return $out;
                     */
                }
                //return $this->renderField($ids);
            }
        }

        return '';
    }

    /**
     * CRED-Shortcode: cred_show_group
     *
     * Description: Show/Hide a group of fields based on conditional logic and values of form fields
     *
     * Parameters:
     * 'if' => Conditional Expression
     * 'mode' => Effect for show/hide group, values are: "fade-slide", "fade", "slide", "none"
     *  
     *   
     * Example usage:
     * 
     *    [cred_show_group if="$(date) gt TODAY()" mode="fade-slide"]
     *       //rest of content to be hidden or shown
     *      // inside the shortcode body..
     *    [/cred_show_group]
     *
     * Link:
     *
     *
     * Note:
     *
     *
     * */
    // parse conditional shortcodes (nested allowed) [cred_show_group]
    public function cred_conditional_shortcodes($atts, $content = '') {
        static $condition_id = 0;

        shortcode_atts(array(
            'if' => '',
            'mode' => 'fade-slide'
                ), $atts); //);

        if (empty($atts['if']) || !isset($content) || empty($content))
            return ''; // ignore

        if (defined('WPTOOLSET_FORMS_VERSION')) {
            $form = &$this->_formData;
            $shortcodeParser = $this->_shortcodeParser;
            ++$condition_id;

            require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.cred.php';
            $conditional = WPToolset_Cred::filterConditional($atts['if'], $this->_post_ID);

            $id = $form->getForm()->ID . '_condition_' . $condition_id;

            $config = array('id' => $id, 'conditional' => $conditional);

            $passed = wptoolset_form_conditional_check($config);

            wptoolset_form_add_conditional($this->form_id, $config);

            $style = ($passed) ? "" : " style='display:none;'";
            $effect = '';
            if (isset($atts['mode'])) {
                $effect = " data-effectmode='" . esc_attr($atts['mode']) . "'";
            }
            $html = "<div class='cred-group {$id}'{$style}{$effect}>";
            $html .= $content;
            $html .= "</div>";
            return $html;
        }

        return '';

        /*
          $out_=&$this->out_;
          $zebraForm=$this->_zebraForm;
          // render conditional group
          $group=$this->add_conditional_group( $form->form->ID.'_condition_'.$condition_id );
          // add child groups from prev level
          if ($shortcodeParser->depth>0 && isset($shortcodeParser->child_groups[$shortcodeParser->depth-1]))
          {
          foreach ($out_['child_groups'][$shortcodeParser->depth-1] as $child_group)
          $group->addControl($child_group);
          }
          // add this group to child groups for next level
          if (!isset($out_['child_groups'][$shortcodeParser->depth]))
          $out_['child_groups'][$shortcodeParser->depth]=array();
          $out_['child_groups'][$shortcodeParser->depth][]=$group;

          // render conditional groups hierarchically
          if (null!==$out_['current_group'])
          $out_['current_group']->addControl($group);
          $prev_group=$out_['current_group'];
          $out_['current_group']=$group;
          $content=$shortcodeParser->do_shortcode($content);
          $out_['current_group']=$prev_group;
          // process this later, before render
          $condition=array(
          'id'=>$group->attributes['id'],
          'container_id'=>$group->attributes['id'],
          'condition' => $atts['if'],
          'replaced_condition'=>'',
          'mode' => isset($atts['mode'])?$atts['mode']:'fade-slide',
          'valid'=>false,
          'var_field_map'=>array()
          );

          $out_['conditionals'][$group->attributes['id']]=$condition;
          return $group->render($content); */
    }

    /**
     * CRED-Shortcode: cred_generic_field
     *
     * Description: Render a form generic field (general fields not associated with types plugin)
     *
     * Parameters:
     * 'field' => Field name (name like used in html forms)
     * 'type' => Type of input field (eg checkbox, email, select, radio, checkboxes, date, file, image etc..)
     * 'class'=> [optional] Css class to apply to the element
     * 'urlparam'=> [optional] URL parameter to be used to give value to the field
     * 'placeholder'=>[optional] Text to be used as placeholder (HTML5) for text fields, default none
     *  
     *  Inside shortcode body the necessary options and default values are defined as JSON string (autogenerated by GUI)
     *   
     * Example usage:
     * 
     *    [cred_generic_field field="gmail" type="email" class=""]
     *    {
     *    "required":0,
     *    "validate_format":0,
     *    "default":""
     *    }
     *    [/cred_generic_field]
     *
     * Link:
     *
     *
     * Note:
     *
     *
     * */
    // parse generic input field shortcodes [cred_generic_field]
    public function cred_generic_field_shortcodes($atts, $content = '') {
        $atts = shortcode_atts(array(
            'field' => '',
            'type' => '',
            'class' => '',
            'placeholder' => null,
            'urlparam' => ''
                ), $atts);
        if (empty($atts['field']) || empty($atts['type']) || null == $content || empty($content))
            return ''; // ignore

        $field_data = json_decode(preg_replace('/[\r\n]/', '', $content), true); // remove NL (crlf) to prevent json_decode from failing        
        // only for php >= 5.3.0
        if (
                (function_exists('json_last_error') && json_last_error() != JSON_ERROR_NONE) ||
                empty($field_data) /* probably JSON decode error */
        ) {
            return ''; //ignore not valid json
        }

        $formHelper = $this->_formHelper;

        $field = array(
            'id' => $atts['field'],
            'cred_generic' => true,
            'slug' => $atts['field'],
            'type' => $atts['type'],
            'name' => $atts['field'],
            'data' => array(
                'repetitive' => 0,
                'validate' => array(
                    'required' => array(
                        'active' => $field_data['required'],
                        'value' => $field_data['required'],
                        'message' => $formHelper->getLocalisedMessage('field_required')
                    )
                ),
                'validate_format' => $field_data['validate_format'],
                'persist' => isset($field_data['persist']) ? $field_data['persist'] : 0
            )
        );

        $default = $field_data['default'];
        $class = ( isset($atts['class']) ) ? $atts['class'] : '';

        switch ($atts['type']) {
            case 'checkbox':
                $field['label'] = isset($field_data['label']) ? $field_data['label'] : '';
                $field['data']['set_value'] = $field_data['default'];
                if ($field_data['checked'] != 1)
                    $default = null;
                break;
            case 'checkboxes':
                $field['data']['options'] = array();
                foreach ($field_data['options'] as $ii => $option) {
                    $option_id = $option['value'];
                    //$option_id=$atts['field'].'_option_'.$ii;
                    $field['data']['options'][$option_id] = array(
                        'title' => $option['label'],
                        'set_value' => $option['value']
                    );
                    if (in_array($option['value'], $field_data['default'])) {
                        $field['data']['options'][$option_id]['checked'] = true;
                    }
                    /**
                     * check post data, maybe this form fail validation
                     */
                    if (
                            !empty($_POST) && array_key_exists($field['id'], $_POST) && is_array($_POST[$field['id']]) && in_array($option['value'], $_POST[$field['id']])
                    ) {
                        $field['data']['options'][$option_id]['checked'] = true;
                    }
                }
                $default = null;
                break;
            case 'date':
                $field['data']['validate']['date'] = array(
                    'active' => $field_data['validate_format'],
                    'format' => 'mdy',
                    'message' => $formHelper->getLocalisedMessage('enter_valid_date')
                );
                $field['data']['date_and_time'] = isset($field_data['date_and_time']) ? $field_data['date_and_time'] : '';
                // allow a default value
                //$default=null;
                break;
            case 'hidden':
                $field['data']['validate']['hidden'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => $formHelper->getLocalisedMessage('values_do_not_match')
                );
                break;
            case 'radio':
            case 'select':
                $field['data']['options'] = array();
                $default_option = 'no-default';
                foreach ($field_data['options'] as $ii => $option) {
                    $option_id = $option['value'];
                    //$option_id=$atts['field'].'_option_'.$ii;
                    $field['data']['options'][$option_id] = array(
                        'title' => $option['label'],
                        'value' => $option['value'],
                        'display_value' => $option['value']
                    );
                    if (!empty($field_data['default']) && $field_data['default'][0] == $option['value'])
                        $default_option = $option_id;
                }
                $field['data']['options']['default'] = $default_option;
                $default = null;
                break;
            case 'multiselect':
                $field['data']['options'] = array();
                $default_option = array();
                foreach ($field_data['options'] as $ii => $option) {
                    $option_id = $option['value'];
                    //$option_id=$atts['field'].'_option_'.$ii;
                    $field['data']['options'][$option_id] = array(
                        'title' => $option['label'],
                        'value' => $option['value'],
                        'display_value' => $option['value']
                    );
                    if (!empty($field_data['default']) && in_array($option['value'], $field_data['default']))
                        $default_option[] = $option_id;
                }
                $field['data']['options']['default'] = $default_option;
                $field['data']['is_multiselect'] = 1;
                $default = null;
                break;
            case 'email':
                $field['data']['validate']['email'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => $formHelper->getLocalisedMessage('enter_valid_email')
                );
                break;
            case 'numeric':
                $field['data']['validate']['number'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => $formHelper->getLocalisedMessage('enter_valid_number')
                );
                break;
            case 'integer':
                $field['data']['validate']['integer'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => $formHelper->getLocalisedMessage('enter_valid_number')
                );
                break;
            case 'embed':
            case 'url':
                $field['data']['validate']['url'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => $formHelper->getLocalisedMessage('enter_valid_url')
                );
                break;
            default:
                $default = $field_data['default'];
                break;
        }

        $name = $field['slug'];
        if ($atts['type'] == 'image' || $atts['type'] == 'file') {
            if (isset($field_data['max_width']) && is_numeric($field_data['max_width']))
                $max_width = intval($field_data['max_width']);
            else
                $max_width = null;
            if (isset($field_data['max_height']) && is_numeric($field_data['max_height']))
                $max_height = intval($field_data['max_height']);
            else
                $max_height = null;

            if (isset($field_data['generic_type']))
                $generic_type = intval($field_data['generic_type']);
            else
                $generic_type = null;

            $ids = $formHelper->translate_field($name, $field, array(
                'preset_value' => $default,
                'urlparam' => $atts['urlparam'],
                'is_tax' => false,
                'max_width' => $max_width,
                'max_height' => $max_height));
            $fieldObj = $formHelper->cred_translate_field($name, $field, array(
                'class' => $class,
                'preset_value' => $default,
                'urlparam' => $atts['urlparam'],
                'generic_type' => $generic_type)
            );
        }
        else if ($atts['type'] == 'hidden') {
            if (isset($field_data['generic_type']))
                $generic_type = intval($field_data['generic_type']);
            else
                $generic_type = null;

            $ids = $formHelper->translate_field($name, $field, array(
                'preset_value' => $default,
                'urlparam' => $atts['urlparam'],
                'generic_type' => $generic_type)
            );

            $fieldObj = $formHelper->cred_translate_field($name, $field, array(
                'class' => $class,
                'preset_value' => $default,
                'urlparam' => $atts['urlparam'],
                'generic_type' => $generic_type)
            );
        }
        else {
            $ids = $formHelper->translate_field($name, $field, array(
                'preset_value' => $default,
                'placeholder' => $atts['placeholder'],
                'urlparam' => $atts['urlparam']));

            $fieldObj = $formHelper->cred_translate_field($name, $field, array(
                'class' => $class,
                'preset_value' => $default,
                'placeholder' => $atts['placeholder'],
                'urlparam' => $atts['urlparam']));
        }

        if ($field['data']['persist']) {
            // this field is going to be saved as custom field to db
            $this->out_['fields']['post_fields'][$name] = $field;
        }
        // check which fields are actually used in form
        $this->out_['form_fields'][$name] = $ids;
        $this->out_['form_fields_info'][$name] = array(
            'type' => $field['type'],
            'repetitive' => (isset($field['data']['repetitive']) && $field['data']['repetitive']),
            'plugin_type' => (isset($field['plugin_type'])) ? $field['plugin_type'] : '',
            'name' => $name
        );
        $this->out_['generic_fields'][$name] = $ids;
        if (!empty($atts['class'])) {
            $atts['class'] = esc_attr($atts['class']);
            /* foreach ($ids as $id)
              $this->_zebraForm->controls[$id]->set_attributes(array('class'=>$atts['class']),false); */
        }
        /* $out='';
          foreach ($ids as $id)
          $out.= "[render_cred_field field='{$id}']"; */
        return $this->renderField($fieldObj);
    }

    //########### CALLBACKS

    /**
     * function used to set controls in order to do not lost filled field values after a failed form submition 
     */
    private function setControls() {
        $this->controls = array();
        foreach ($this->_request as $key => $value) {
            $this->controls[$key] = $value;
        }
        //No need anymore
        unset($this->_request);
    }

    /**
     * get the current form
     * @return boolean|\CredForm
     */
    public function getForm() {
        if (!function_exists('wptoolset_form_field')) {
            echo "error";
            return false;
        }

        $this->form_properties = array();

        $this->form_properties['doctype'] = 'xhtml';
        $this->form_properties['action'] = htmlspecialchars($_SERVER['REQUEST_URI']);
        //Fix for todo https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193382255/comments#303088273
        if (preg_match("/admin-ajax.php/", $this->form_properties['action'])) {
            $this->form_properties['action'] = ( wp_get_referer() ) ? wp_get_referer() : get_home_url();
        }
        $this->form_properties['method'] = 'post';

        $this->form_properties['name'] = $this->form_id;
        $this->form_properties['fields'] = array();

        return $this;
    }

    public function setLanguage($lang) {
        $this->language = $lang;
    }

    /**
     * render callback
     * @param type $controls
     * @param type $objs
     * @return type
     */
    public function render_callback($controls, &$objs) {
        $out_ = &$this->out_;
        $shortcodeParser = $this->_shortcodeParser;
        $out_['controls'] = $controls;
        // render shortcodes, _form_content is being continuously replaced recursively
        $this->_form_content = $shortcodeParser->do_shortcode($this->_form_content);
        return $this->_form_content;
    }

    /**
     * render function
     * @return type
     */
    public function render() {
        $html = "";
        if ($this->isForm) {
            $this->isForm = false;
            $enctype = "";
            if ($this->isUploadForm) {
                $this->isUploadForm = false;
                $enctype = 'enctype="multipart/form-data"';
            }

            $amp = '?';
            $_tt = '_tt=' . time();

            if (!empty($_SERVER['QUERY_STRING']))
                $amp = '&';

            $this->_form_content = '<form ' . $enctype . ' ' .
                    ($this->form_properties['doctype'] == 'html' ? 'name="' . $this->form_properties['name'] . '" ' : '') .
                    'id="' . $this->form_properties['name'] . '" ' .
                    'class="' . ((isset($this->_attributes['class']) && !empty($this->_attributes['class'])) ? $this->_attributes['class'] : "") . '" ' .
                    'action="' . $this->form_properties['action'] . $amp . $_tt . '" ' .
                    'method="' . strtolower($this->form_properties['method']) . '">' . $this->_form_content . "</form>";
        }

        return $this->_form_content;
    }

    private function typeMessage2textMessage($txt) {
        switch ($txt) {
            case "date":
                return "cred_message_enter_valid_date";
            case "embed":
            case "url":
                return "cred_message_enter_valid_url";
            case "email":
                return "cred_message_enter_valid_email";
            case "integer":
            case "number":
                return "cred_message_enter_valid_number";
            case "captcha":
                return "cred_message_enter_valid_captcha";
            case "button":
                return "cred_message_edit_skype_button";
            case "image":
                return "cred_message_not_valid_image";
            default:
                return "cred_message_field_required";
        }
    }

    private function typeMessage2id($txt) {
        switch ($txt) {
            case "date":
                return "cred_message_enter_valid_date";
            case "embed":
            case "url":
                return "cred_message_enter_valid_url";
            case "email":
                return "cred_message_enter_valid_email";
            case "integer":
            case "number":
                return "cred_message_enter_valid_number";
            case "captcha":
                return "cred_message_enter_valid_captcha";
            case "button":
                return "cred_message_edit_skype_button";
            case "image":
                return "cred_message_not_valid_image";
            default:
                return "cred_message_field_required";
        }
    }

    //Client-side validation is not using the custom messages provided in CRED forms for CRED custom fields
    //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/187800735/comments
    /**
     * fixCredCustomFieldMessages
     * Fix CRED controlled custom fields validation message
     * replace with cred form settings messages and localize messages
     * @param type $field
     * @return type
     */
    public function fixCredCustomFieldMessages(&$field) {
        if (!isset($field['cred_custom']) || isset($field['cred_custom']) && !$field['cred_custom'])
            return;
        $cred_messages = $this->extra_parameters->messages;
        foreach ($field['data']['validate'] as $a => &$b) {
            $idmessage = $this->typeMessage2textMessage($a);
            $b['message'] = $cred_messages[$idmessage];
            $b['message'] = cred_translate(
                    CRED_Form_Builder_Helper::MSG_PREFIX . $idmessage, $cred_messages[$idmessage], 'cred-form-' . $this->_formData->getForm()->post_title . '-' . $this->_formData->getForm()->ID
            );
        }
    }

    /**
     * This function render the single field
     * @global type $post
     * @param type $field
     * @param type $add2form_content
     * @return type
     */
    public function renderField($field, $add2form_content = false) {
        global $post;
        //echo "<br>{$field['name']}";

        if (defined('WPTOOLSET_FORMS_ABSPATH') &&
                function_exists('wptoolset_form_field')) {
            require_once WPTOOLSET_FORMS_ABSPATH . '/api.php';
            require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.fieldconfig.php';
            require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.types.php';
            require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.cred.php';

            $id = $this->form_id;
            $field['id'] = $id;

            if ($field['type'] == 'messages') {
                $this->isForm = true;
                return;
            }
            
            if ($this->form_type == 'edit' && $field['name']=='user_login') {
                $field['attr']['readonly'] = "readonly";
                $field['attr']['style'] = "background-color:#ddd;";
                $field['attr']['onclick'] = "blur();";
            }

            if ($field['type'] == 'credfile' ||
                    $field['type'] == 'credaudio' ||
                    $field['type'] == 'credvideo' ||
                    $field['type'] == 'credimage' ||
                    $field['type'] == 'file') {
                $this->isUploadForm = true;
                //$field['type'] = 'credfile';
            }

            /* $validation = array(
              'validation' => array(
              'required' => array(
              'args' => array($field['value'], true),
              'message' => 'Required'),
              'maxlength' => array(
              'args' => array($field['value'], 12),
              'message' => 'maxlength of 12 exceeded'
              ),
              'rangelength' => array(
              'args' => array($field['value'], 3, 25),
              'message' => 'input range from 3 to 25'
              ),
              )); */

            //#############################################################################################################################################################
            //Client-side validation is not using the custom messages provided in CRED forms for CRED custom fields
            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/187800735/comments
            $this->fixCredCustomFieldMessages($field);
            //#############################################################################################################################################################

            $mytype = $this->transType($field['type']);
            $fieldConfig = new FieldConfig();
            $fieldConfig->setDefaultValue($field['type'], $field);
            $fieldConfig->setOptions($field['name'], $field['type'], $field['value'], $field['attr']);
            $fieldConfig->setId($this->form_properties['name'] . "_" . $field['name']);
            $fieldConfig->setName($field['name']);
            $this->cleanAttr($field['attr']);
            $fieldConfig->setAttr($field['attr']);

            $_curr_value = "";
            //#############################################################################################################################################################
            //Fix https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/192427784/comments#300129108
            //losting filled data if using hook validate system            
            if (isset($this->controls[$field['name']]) && !empty($this->controls[$field['name']])) {
                $_curr_value = $this->controls[$field['name']];
            } else {
                if (isset($field['value']) && !empty($field['value'])) {
                    $_curr_value = $field['value'];
                }
            }
            //#############################################################################################################################################################

            if (!$this->is_submit_success &&
                    ($mytype != 'credfile' &&
                    $mytype != 'credaudio' &&
                    $mytype != 'credvideo' &&
                    $mytype != 'credimage')) {
                $_curr_value = isset($this->controls[$field['name']]) ? $this->controls[$field['name']] : $field['value'];
            } else {
                if (isset($field['attr']['preset_value']) &&
                        !empty($field['attr']['preset_value'])) {
                    $_curr_value = $field['attr']['preset_value'];
                }
            }

            $fieldConfig->setValue($_curr_value);
            $fieldConfig->setDescription(!empty($field['description']) ? $field['description'] : "");
            $fieldConfig->setTitle($field['title']);
            $fieldConfig->setType($mytype);

            if (isset($field['data']) && isset($field['data']['repetitive'])) {
                $fieldConfig->setRepetitive((bool) $field['data']['repetitive']);
            }

            if (isset($field['attr']) && isset($field['attr']['type'])) {
                $fieldConfig->setDisplay($field['attr']['type']);
            }
            $config = $fieldConfig->createConfig();

            // Modified by Srdjan
            // Validation and conditional filtering
            if (isset($field['plugin_type']) && $field['plugin_type'] == 'types') {
                // This is not set in DB
                $field['meta_key'] = WPToolset_Types::getMetakey($field);
                $config['validation'] = WPToolset_Types::filterValidation($field);

                if ($post)
                    $config['conditional'] = WPToolset_Types::filterConditional($field, $post->ID);
            } else {
                $config['validation'] = WPToolset_Cred::filterValidation($field);
            }
            // Modified by Srdjan END

            $_values = array();
            if (isset($field['data']['repetitive']) && $field['data']['repetitive'] == 1) {
                //$_values = $field['value'];
                $_values = $_curr_value;
            } else {
                //$_values = array($field['value']);
                $_values = array($_curr_value);
            }

            // Added by Srdjan
            /*
             * Use $_validation_errors
             * set in $this::validate()
             */
            if (isset($this->_validation_errors['fields'][$config['id']])) {
                $config['validation_error'] = $this->_validation_errors['fields'][$config['id']];
            }

            if ($this->form_type == 'edit' && $mytype == 'checkbox')
                unset($config['default_value']);


            // Added by Srdjan END            
            $html = wptoolset_form_field($this->form_id, $config, $_values);
            if ($add2form_content)
                $this->_form_content.=$html;
            else
                return $html;

            /*
              <input id="cred_form_853_1__cred_cred_wpnonce" type="hidden" user_defined="" value="d9f46d7082" name="_cred_cred_wpnonce">
              <input id="cred_form_853_1__cred_cred_prefix_form_id" type="hidden" user_defined="" value="853" name="_cred_cred_prefix_form_id">
              <input id="cred_form_853_1__cred_cred_prefix_form_count" type="hidden" user_defined="" value="1" name="_cred_cred_prefix_form_count">
              <input id="cred_form_853_1_name_cred_form_853_1" type="hidden" user_defined="" value="cred_form_853_1" name="name_cred_form_853_1">
             */
        }
    }

    /**
     * clean attr variable
     * @param type $attrs
     * @return type
     */
    public function cleanAttr(&$attrs) {
        if (empty($attrs))
            return;
        foreach ($attrs as $n => $v) {
            if (is_array($v))
                continue;
            $attrs[$n] = esc_attr($v);
        }
        $attrs = array_filter($attrs);
    }

    /**
     * add field content to the form
     * @param type $type
     * @param type $name
     * @param type $value
     * @param type $attributes
     * @param type $field
     * @return type
     */
    public function add2form_content($type, $name, $value, $attributes, $field = null) {
        $objField2Render = $this->add($type, $name, $value, $attributes);
        return $this->renderField($objField2Render, true);
    }

    /**
     * add js content to the form
     * @param type $js
     */
    public function addJsFormContent($js) {
        //$js = str_replace("'","\'",$js);        
        $this->_js = "<script language='javascript'>{$js}</script>";
    }

    function sanitize_me($s) {
        return htmlspecialchars($s, ENT_QUOTES, 'utf-8');
    }

    /**
     * translate special field types
     * @param type $type
     * @return type
     */
    private function transType($type) {
        switch ($type) {

            case 'text':
                $ret = 'textfield';
                break;

            default:
                $ret = $type;
                break;
        }

        //Forcing
        //$ret = 'textfield';
        return $ret;
    }

    /**
     * is submitted checks function
     * @return boolean
     */
    function isSubmitted() {
        foreach ($_REQUEST as $name => $value) {
            if (strpos($name, 'form_submit') !== false) {
                return true;
            }
        }
        if (empty($_POST) && isset($_GET['_tt']) && !isset($_GET['_success']) && !isset($_GET['_success_message'])) {
            // HACK in this case, we have used the form to try to upload a file with a size greater then the maximum allowed by PHP
            // The form was indeed submitted, but no data was passed and no redirection was performed
            // We return true here and handle the error in the Form_Builder::form() method
            return true;
        }
        return false;
    }

    /**
     * set the current fields
     * @param type $fields
     */
    public function set_submitted_values($fields) {
        $this->form_properties['fields'] = $fields;
    }

    /**
     * @deprecated deprecated since version 1.2.6
     */
    function get_submitted_values() {
        return true;
    }

    /**
     * Get all POST/FILES values and set to class object variable
     * @return type
     */
    public function get_form_field_values() {
        $fields = array();

        //FIX validation for files elements
        $files = array();
        foreach ($_FILES as $name => $value) {
            $files[$name] = $value['name'];
        }
        $reqs = array_merge($_REQUEST, $files);

        foreach ($this->form_properties['fields'] as $n => $field) {
            if ($field['type'] != 'messages') {
                $value = isset($reqs[$field['name']]) ? $reqs[$field['name']] : "";

                $fields[$field['name']] = array(
                    'value' => $value,
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'repetitive' => isset($field['data']['repetitive']) ? $field['data']['repetitive'] : false
                );
            }
        }
        return $fields;
    }

    /**
     * New validation using API calls from toolset-forms.
     *
     * Uses API cals
     * @uses wptoolset_form_validate_field()
     * @uses wptoolset_form_conditional_check()
     *
     * @todo Make it work with other fields (generic)
     *
     * @param type $post_id
     * @param type $values
     * @return boolean
     */
    function validate($post_id, $values) {
        $form_id = $this->form_id;
        $valid = true;
        // Loop over fields
        $form_source_data = $this->_formData->getForm()->post_content;
        preg_match_all("/\[cred_show_group.*cred_show_group\]/Uism", $form_source_data, $res);
        $conditional_group_fields = array();
        if (count($res[0]) > 0) {
            for ($i = 0, $res_limit = count($res[0]); $i < $res_limit; $i++) {
                preg_match_all("/field=\"([^\"]+)\"/Uism", $res[0][$i], $parsed_fields);
                if (count($parsed_fields[1]) > 0) {
                    for ($j = 0, $count_parsed_fields = count($parsed_fields); $j < $count_parsed_fields; $j++) {
                        if (!empty($parsed_fields[1][$j])) {
                            $conditional_group_fields[] = trim($parsed_fields[1][$j]);
                        }
                    }
                }
            }
        }

        foreach ($this->form_properties['fields'] as $field) {
            if (in_array(str_replace('wpcf-', '', $field['name']), $conditional_group_fields)) {
                continue;
            }
            // If Types field
            if (isset($field['plugin_type']) && $field['plugin_type'] == 'types') {
                $field_name = $field['name'];

                if (!isset($_POST[$field_name]))
                    continue;

                /* 	
                  // Adjust field ID
                  $field['id'] = strpos( $field['name'], 'wpcf-' ) === 0 ? substr( $field['name'], 5 ) : $field['name'];
                  // CRED have synonym 'text' for textfield
                  if ( $field['type'] == 'text' ) {
                  $field['type'] = 'textfield';
                  } */
                $field = wpcf_fields_get_field_by_slug(str_replace('wpcf-', '', $field['name']));
                if (empty($field)) {
                    continue;
                }
                // Skip copied fields
                if (isset($_POST['wpcf_repetitive_copy'][$field['slug']])) {
                    continue;
                }
                // Set field config
                $config = wptoolset_form_filter_types_field($field, $post_id);

                //Add custom colorpicer hexadecimal backend validator
                //Fix https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193712665/comments
                //Todo in future add hexadecimal check in types
                if ($field['type'] == 'colorpicker') {
                    $config['validation']['hexadecimal'] = array('args' => array('$value', '1'), 'message' => 'Enter valid hexadecimal value');
                }

                if (isset($config['conditional']['values'])) {
                    foreach ($config['conditional']['values'] as $post_key => $post_value) {
                        if (isset($this->form_properties['fields'][$post_key])) {
                            $config['conditional']['values'][$post_key] = $this->form_properties['fields'][$post_key]['value'];
                        }
                    }
                }
                // Set values to loop over
                $_values = !empty($values[$field_name]) ? $values[$field_name]['value'] : null;
                if (empty($config['repetitive'])) {
                    $_values = array($_values);
                }

                // Loop over each value
                if (is_array($_values)) {
                    foreach ($_values as $value) {
                        $validation = wptoolset_form_validate_field($form_id, $config, $value);
                        $conditional = wptoolset_form_conditional_check($config);

                        /**
                         * add form_errors messages
                         */
                        if (is_wp_error($validation) && $conditional) {
                            $error_data = $validation->get_error_data();
                            if (isset($error_data[0])) {
                                $this->add_top_message($error_data[0], $config['id']);
                            } else {
                                $this->add_top_message($validation->get_error_message(), $config['id']);
                            }
                            $valid = false;
                            if (empty($ret_validation)) {
                                continue;
                            }
//                            foreach( $errors as $error ) {
//                                $error = explode( ' ', $error );
//                                $key = array_shift($error);
//                                $error = implode( ' ', $error );
//                                $this->form_errors[$key] = $error;
//                                $this->form_messages[$key] = $validation->get_error_message();                                
//                            }
                        }
                    }
                }
            } elseif (!isset($field['plugin_type']) && isset($field['validation'])) {

                if (!isset($_POST[$field['name']]))
                    continue;

                $config = array(
                    'id' => $field['name'],
                    'type' => $field['type'],
                    'slug' => $field['name'],
                    'title' => $field['name'],
                    'description' => '',
                    'name' => $field['name'],
                    'repetitive' => $field['repetitive'],
                    'validation' => $field['validation'],
                    'conditional' => array()
                );

                $value = $field['value'];
                require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.types.php';
                $validation = array();
                foreach ($field['validation'] as $rule => $settings) {
                    if ($settings['active']) {
                        $id = $config['slug'];
                        $validation[$rule] = array(
                            'args' => isset($settings['args']) ? array_unshift($value, $settings['args']) : array($value, true),
                            'message' => WPToolset_Types::translate('field ' . $id . ' validation message ' . $rule, $settings['message'])
                        );
                    }
                }
                $config['validation'] = $validation;

                $validation = wptoolset_form_validate_field($form_id, $config, $value);
                if (is_wp_error($validation)) {
                    $error_data = $validation->get_error_data();
                    //TODO: replace id with name
                    if (isset($error_data[0])) {
                        $this->add_top_message($error_data[0], $config['id']);
                    } else {
                        $this->add_top_message($validation->get_error_message(), $config['id']);
                    }
                    $valid = false;
                    if (empty($ret_validation)) {
                        continue;
                    }
//                    foreach( $errors as $error ) {
//                        $error = explode( ' ', $error );
//                        $key = array_shift($error);
//                        $error = implode( ' ', $error );
//                        $this->form_errors[$key] = $error;
//                        $this->form_messages[$key] = $validation->get_error_message();                                
//                    }	
                }
            }
        }
        return $valid;
    }

    /**
     * @deprecated deprecated since version 1.2.6
     */
    function add_repeatable($type) {
        return;
    }

    /**
     * @deprecated deprecated since version 1.2.6
     */
    function add_conditional_group($id) {
        return;
    }

    /**
     * Function that handles warning/error message to top form
     * @param type $message
     * @param type $field_slug
     */
    function add_top_message($message, $field_slug = 'generic') {
        $form_id = $this->form_id;
        if ($message == '') {
            return;
        }
        if (!isset($this->top_messages[$form_id]))
            $this->top_messages[$form_id] = array();
        //Fix slug with name
        $message = str_replace("post_title", "Post Name", $message);
        $message = str_replace("post_content", "Description", $message);
        if (!empty($message) && !in_array(trim($message), $this->top_messages[$form_id])) {
            $this->top_messages[$form_id][] = $message;
        }
    }

    /**
     * Function that handles warning/error message to a field or a form
     * @param type $message
     * @param type $field_slug
     */
    function add_field_message($message, $field_slug = 'generic') {
        $form_id = $this->form_id;
        if ($message == '') {
            return;
        }
        if (!isset($this->field_messages[$form_id]))
            $this->field_messages[$form_id] = array();
        if (!isset($this->field_messages[$form_id][$field_slug]))
            $this->field_messages[$form_id][$field_slug] = array();
        if (!empty($message) && !in_array(trim($message), $this->field_messages[$form_id]))
            $this->field_messages[$form_id][$field_slug] = $message;
    }

    function add_success_message($message, $field_slug = 'generic') {
        $form_id = $this->form_id;
        if ($message == '') {
            return;
        }
        if (!isset($this->succ_messages[$form_id]))
            $this->succ_messages[$form_id] = array();
        if (!isset($this->succ_messages[$form_id][$field_slug]))
            $this->succ_messages[$form_id][$field_slug] = array();
        if (!empty($message) && !in_array(trim($message), $this->succ_messages[$form_id]))
            $this->succ_messages[$form_id][$field_slug] = $message;
    }

    function add_preview_message($message) {
        $this->preview_messages[] = $message;
    }

    function getFieldsSuccessMessages() {
        $form_id = $this->form_id;
        //
        $msgs = "";
        if (!isset($this->succ_messages) || (isset($this->succ_messages) && empty($this->succ_messages)))
            return $msgs;

        $field_messages = $this->succ_messages[$form_id];
        foreach ($field_messages as $id_field => $text) {
            //if ($id_field!='generic') $text = "<b>".$id_field."</b>: ".$text;
            $msgs .= "<label id=\"lbl_$id_field\" class=\"wpt-form-success\">$text</label><div style='clear:both;'></div>";
        }
        return $msgs;
    }

    /**
     * function to grep all error messages
     * @return type
     */
    function getFieldsErrorMessages() {
        $form_id = $this->form_id;
        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/195892843/comments#309778558
        //Created separated preview message
        $msgs = "";
        if (!empty($this->preview_messages)) {
            $msgs .= "<label id=\"lbl_preview\" style='background-color: #ffffe0;
                border: 1px solid #e6db55;
                display: block;
                margin: 10px 0;
                padding: 5px 10px;
                width: auto;'>" . $this->preview_messages[0] . "</label><div style='clear:both;'></div>";
        }
        if (!isset($this->field_messages) || (isset($this->field_messages) && empty($this->field_messages)))
            return $msgs;

        $field_messages = $this->field_messages[$form_id];
        foreach ($field_messages as $id_field => $text) {
            //if ($id_field!='generic') $text = "<b>".$id_field."</b>: ".$text;
            $msgs .= "<label id=\"lbl_$id_field\" class=\"wpt-form-error\">$text</label><div style='clear:both;'></div>";
        }
        return $msgs;
    }

    /**
     * Javascript functions that moves error messages close to related field
     * @return string
     */
    function getFieldsErrorMessagesJs() {
        $form_id = $this->form_id;
        if (!isset($this->field_messages) || (isset($this->field_messages) && empty($this->field_messages)))
            return;
        $field_messages = $this->field_messages[$form_id];
        $js = '<script language="javascript">
            jQuery(document).ready(function(){';
        foreach ($field_messages as $id_field => $text) {
            if ($id_field != 'generic')
                $js.='if (jQuery(\'[data-wpt-name="' . $id_field . '"]:first\').length) jQuery("#lbl_' . $id_field . '").detach().insertAfter(\'[data-wpt-name="' . $id_field . '"]:first\');';
            //$js.='if (jQuery(\'[name="'.$id_field.'"]:first\').length) jQuery("#lbl_'.$id_field.'").detach().insertAfter(\'[name="'.$id_field.'"]:first\');';
            //$js.='if (jQuery(\'[name="'.$id_field.'[0]"]:first\').length) jQuery("#lbl_'.$id_field.'").detach().insertAfter(\'[name="'.$id_field.'[0]"]:first\');';            
            //$js.='if (jQuery(\'[data-wpt-name="'.$id_field.'"]:first\').length) jQuery("#lbl_'.$id_field.'").detach().insertAfter(\'[data-wpt-name="'.$id_field.'"]:first\');';
        }
        $js .= '});
            </script>';

        return $js;
    }

    /**
     * @deprecated function since CRED 1.3b3
     * @param type $error_block
     * @param type $error_message
     */
    function add_form_error($error_block, $error_message) {
        // if the error block was not yet created, create the error block
        if (!isset($this->form_errors[$error_block]))
            $this->form_errors[$error_block] = array();
        if (is_array($error_message))
            $error_message = isset($error_message[0]) ? $error_message[0] : "";
        // if the same exact message doesn't already exists
        if (!empty($error_message) && !in_array(trim($error_message), $this->form_errors[$error_block]))
            $this->form_errors[$error_block][] = trim($error_message);
    }

    /**
     * @deprecated function since CRED 1.3b3
     * @param type $msg_block
     * @param type $message
     */
    function add_form_message($msg_block, $message) {
        // if the error block was not yet created, create the error block
        if (!isset($this->form_messages[$msg_block]))
            $this->form_messages[$msg_block] = array();

        if (is_array($message))
            $message = isset($message[0]) ? $message[0] : "";

        // if the same exact message doesn't already exists
        if (!empty($message) && !in_array(trim($message), $this->form_messages[$msg_block]))
            $this->form_messages[$msg_block][] = trim($message);
    }

    /**
     * get format of wordpress date
     * @return string
     */
    function getDateFormat() {
        $date_format = get_option('date_format');
        if (!in_array($date_format, $this->_supported_date_formats)) {
            $date_format = 'F j, Y';
        }
        return $date_format;
    }

    /**
     * get a field information from id
     * @param type $id
     * @param type $field
     * @return string
     */
    function getFileData($id, $field) {
        $ret = array();
        $ret['value'] = $field['name'];
        $ret['file_data'] = array();
        $ret['file_data'][$id] = array();
        $ret['file_data'][$id] = $field;
        $ret['file_upload'] = "";
        return $ret;
    }

}

?>
