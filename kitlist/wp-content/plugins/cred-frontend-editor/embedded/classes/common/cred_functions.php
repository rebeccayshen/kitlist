<?php

if (!function_exists("cred_wrap_esc_like")) {

    function cred_wrap_esc_like($text) {
        global $wpdb;
        if (method_exists($wpdb, 'esc_like')) {
            return $wpdb->esc_like($text);
        } else {
            return addcslashes($text, '_%\\');
        }
    }

}
/*
  function try_to_remove_view_shortcode() {
  remove_shortcode('wpv-post-body');
  }

  function try_to_add_view_shortcode() {
  add_shortcode('wpv-post-body','wpv_shortcode_wpv_post_body');
  }
 */

function cred_mytrimfunction(&$a) {
    return trim($a, ']');
}

function trans_notf_event($event_notf_type) {
    switch ($event_notf_type) {
        case 'form_submit':
            return 'Submition';
        case 'expiration_date':
            return 'Expiration Date';
        default:
            return ucwords(str_replace("_", " ", $event_notf_type));
    }
}

function normalize_notf_txt($txt) {
    switch ($txt) {
        case 'specific_mail':
            return "a specific mail:";
        default:
            return str_replace("_", " ", $txt);
    }
}

function trans_txt($txt) {
    switch ($txt) {
        case 'publish':
            return 'Published';
        default:
            return ucwords(str_replace("_", " ", $txt));
    }
}

function cred_embedded_html() {

    if (isset($_GET['cred_id']) && is_numeric($_GET['cred_id'])) {
        $cred_id = (int) $_GET['cred_id'];
        //$cred = get_post($cred_id);
        $cred = get_post($cred_id, OBJECT, 'edit');
        //StaticClass::_pre($cred);
        if (null == $cred) {
            wp_die('<div class="wpv-setting-container"><p class="toolset-alert toolset-alert-error">' . __('You attempted to edit a CRED that doesn&#8217;t exist. Perhaps it was deleted?', 'wpv-views') . '</p></div>');
        } elseif ('cred-form' != $cred->post_type) {
            wp_die('<div class="wpv-setting-container"><p class="toolset-alert toolset-alert-error">' . __('You attempted to edit a CRED that doesn&#8217;t exist. Perhaps it was deleted?', 'wpv-views') . '</p></div>');
        } else {

            CRED_Loader::loadAsset('STYLE/cred_codemirror_style_dev', 'cred_codemirror_style', false, CRED_CONCAT_ASSETS);
            wp_enqueue_style('cred_codemirror_style');
            CRED_Loader::loadAsset('SCRIPT/cred_codemirror_dev', 'cred_codemirror_dev', false, CRED_CONCAT_ASSETS);
            wp_enqueue_script('cred_codemirror_dev');

            $sm = CRED_Loader::get('MODEL/Settings');
            $settings = $sm->getSettings();

            $fm = CRED_Loader::get('MODEL/Forms');
            $form_fields = $fm->getFormCustomFields($cred_id, array('form_settings', 'notification', 'extra', 'wizard'));

            $forms_model = CRED_Loader::get('MODEL/Forms');
            $settings = $forms_model->getFormCustomField($cred_id, 'form_settings');

            $fields_model = CRED_Loader::get('MODEL/Fields');
            $fields_all = $fields_model->getFields($cred->post_type);

            if ($settings->post['post_status'] == 'trash') {
                wp_die('<div class="wpv-setting-container"><p class="toolset-alert toolset-alert-error">' . __("You can\'t edit this CRED because it is in the Trash. Please restore it and try again.", 'wpv-views') . '</p></div>');
            }

            $_button_getcred = '<a style="vertical-align: baseline; background: none repeat scroll 0 0 #f6921e;
    border-color: #ef6223;
    box-shadow: 0 1px 0 rgba(239, 239, 239, 0.5) inset, 0 1px 0 rgba(0, 0, 0, 0.15);
    color: #fff;
    text-decoration: none;" class="button button-primary-toolset" title="get cred" target="_blank" href="http://wp-types.com/home/cred/?utm_source=credplugin&utm_campaign=cred&utm_medium=embedded-cred-promotional-link&utm_term=Get CRED">Get CRED</a>';
            $_header = "You are viewing the read-only version of this CRED form. To edit it, you need to get CRED plugin. $_button_getcred";
            $_content = "CRED lets you build forms for editing any WordPress content on the site’s front-end. You can choose if the form creates or edits content and the type of content it will create or edit. The form is designed with simple HTML and shortcodes.";

            $settings_post_type = $settings->post['post_type'];
            $settings_post_status = $settings->post['post_status'];
            $settings_type = $settings->form['type'];
            $settings_action = $settings->form['action'];
            $has_captcha = ($settings->form['include_captcha_scaffold'] == 1) ? " and includes captcha field " : "";

            $notification = $form_fields['notification'];
            if ($notification->enable == 1 && count($notification->notifications) > 0) {
                $notification_txt = "<p>Enabled</p>";
                foreach ($notification->notifications as $n => $notf) {

                    if (count($notf['to']['type']) > 0) {
                        $notification_txt .= "A notification will be sent to ";
                        //StaticClass::_pre($notf['to']['type']);
                        foreach ($notf['to']['type'] as $m => $t) {
                            if ($t == 'wp_user' ||
                                    $t == 'mail_field' ||
                                    $t == 'user_id_field')
                                continue;
                            $notification_txt .= "<b>" . normalize_notf_txt($t) . "</b>";
                        }
                        foreach ($notf['to']['wp_user'] as $a => $b) {
                            if ($b != 'to')
                                $notification_txt .= " <b>" . normalize_notf_txt($b) . "</b> ";
                        }
                    }
                    if (isset($notf['event']) && !empty($notf['event'])) {
                        $notification_event = "<p>The notification event is set to <b>" . trans_notf_event($notf['event']['type']) . "</b></p>";
                        $post_status_event = "<p>The notification post status event is set to <b>" . trans_notf_event($notf['event']['post_status']) . "</b></p>";
                        $condition_event = "";
                        if (!empty($notf['event']['condition']) && count($notf['event']['condition']) > 0)
                            $condition_event = "<p>The notification is <b>Based to conditions</b></p>";
                        $notification_txt .= " $notification_event $post_status_event $condition_event ";
                    }
                    if (isset($notf['to']['author']) && $notf['to']['author'] == 'author')
                        $notification_txt .= "<p>A notification will be <b>Sent to the Author</b></p>";
                }
            } else {
                $notification_txt = "Disabled";
            }

            $settings_txt = "This Form ";
            switch ($settings_type) {
                case 'new':
                    $act = "Creates";
                    $settings_txt .= '<b>Creates Content</b>';
                    break;
                case 'edit':
                    $act = "Edits";
                    $settings_txt .= '<b>Edits Content</b>';
                    break;
            }

            $settings_txt .= " and after submition ";
            switch ($settings_action) {
                case 'form':
                    $settings_txt .= '<b>keeps displaying this form</b>';
                    break;
                case 'message':
                    $settings_txt .= '<b>displays a custom message</b>';
                    break;
                case 'post':
                    $settings_txt .= '<b>displays a post</b>';
                    break;
                case 'page':
                    $settings_txt .= '<b>got to a custom page</b>';
                    break;
            }

            $settings_txt .= ($settings->form['hide_comments'] == 1) ? "<p>Comments are hidden</p>" : "";

            $settings_post_txt = "This Form $act <b>$settings_post_type</b> and the status will be <b>" . trans_txt($settings_post_status) . "</b>" . $has_captcha;

            $extra = $form_fields['extra'];
            $css = $extra->css;
            $css_txt = "";
            $js = $extra->js;
            $css_txt = "Empty";
            if (!empty($css)) {
                $css_txt = $css;
            }
            $js_txt = "Empty";
            if (!empty($js)) {
                $js_txt = $js;
            }
            ?>

            <div style="clear:both;height:20px;"></div>

            <h2><?php echo $cred->post_title; ?></h2>

            <div style="width:950px;height:auto;">
                <div class="toolset-help js-info-box">
                    <div class="toolset-help-content">
                        <h2 style="color: #222;
                            font-size: 1.1em;
                            font-weight:bold;
                            margin: 0.83em 0;"><?php echo $_header; ?></h2>
                        <p><?php echo $_content; ?></p>
                    </div>
                    <div class="toolset-help-sidebar">
                        <div class="toolset-help-sidebar-ico"></div>
                    </div>

                </div>
            </div>


            <h3>Form Settings:</h3> <?php echo $settings_txt; ?>

            <h3>Post Type Settings:</h3> <?php echo $settings_post_txt; ?>

            <h3>Form Content:</h3> 

            <div style="width:950px;height:auto;">
                <textarea id="mycontent"><?php echo $cred->post_content; ?></textarea>
            </div>

            <script>
                jQuery(document).ready(function () {
                    CodeMirror.defineMode("myshortcodes", codemirror_shortcodes_overlay);
                    CodeMirror.fromTextArea(document.getElementById("mycontent")
                            , {
                                mode: 'myshortcodes', //"text/html",
                                tabMode: "indent",
                                lineWrapping: true,
                                lineNumbers: true,
                                readOnly: "nocursor"
                            });
                });
            </script>

            <?php if (false) { ?><div style="padding:5px;margin-left:10px;border:1px #000 solid;width:80%;height:200px;overflow-y:auto;"><?php echo $cred->post_content; ?></div><?php } ?>

            <?php if (false) { ?>
                <h3>JS:</h3> <?php echo $js_txt; ?>

                <h3>CSS:</h3> <?php echo $css_txt; ?>            
                <?php
            }
            ?>
            <h3>Notification:</h3> <?php echo $notification_txt; ?>
            <?php
            //StaticClass::_pre($form_fields['extra']);
            //StaticClass::_pre($cred);
            //StaticClass::_pre($settings);
            //StaticClass::_pre($fields_all);
        }
    } else {
        wp_die('<div class="wpv-setting-container"><p class="toolset-alert toolset-alert-error">' . __('You attempted to edit a View that doesn&#8217;t exist. Perhaps it was deleted?', 'wpv-views') . '</p></div>');
    }
    ?>

    <?php
}