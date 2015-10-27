<?php

/**
 *   CRED XML Processor
 *   handles import-export to/from XML
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk_new/embedded/classes/common/XML_Processor.php $
 * $LastChangedDate: 2015-03-17 08:25:16 +0100 (mar, 17 mar 2015) $
 * $LastChangedRevision: 32390 $
 * $LastChangedBy: gen $
 *
 */
final class CRED_XML_Processor {

    public static $use_zip_if_available = true;
    private static $add_CDATA = false;
    private static $root = 'forms';
    private static $filename = '';

    private static function arrayToXml($array, $depth, $parent) {
        $output = '';
        $indent = str_repeat(' ', $depth * 4);
        $child_key = false;
        if (isset($array['__key'])) {
            $child_key = $array['__key'];
            unset($array['__key']);
        }
        foreach ($array as $key => $value) {
            if (empty($key) && $key !== 0)
                continue;

            if (!(in_array($key, array('settings', 'post_expiration_settings', 'custom_fields')) && $parent == self::$root))
                $key = $child_key ? $child_key : $key;
            if (is_numeric($key))
                $key = $parent . '_item'; //.$key;
            if (!is_array($value) && !is_object($value)) {
                if (self::$add_CDATA && !is_numeric($value) && !empty($value))
                    $output .= $indent . "<$key><![CDATA[" . htmlspecialchars($value, ENT_QUOTES) . "]]></$key>\r\n";
                else
                    $output .= $indent . "<$key>" . htmlspecialchars($value, ENT_QUOTES) . "</$key>\r\n";
            }
            else {
                if (is_object($value))
                    $value = (array) $value;

                //$depth++;
                $output_temp = self::arrayToXml($value, $depth + 1, $key);
                if (!empty($output_temp)) {
                    $output .= $indent . "<$key>\r\n";
                    $output .= $output_temp;
                    $output .= $indent . "</$key>\r\n";
                }
                //$depth--;
            }
        }
        return $output;
    }

    private static function toXml($array, $root_element) {
        if (empty($array))
            return "";
        $xml = "";
        $xml .= "<?xml version=\"1.0\" encoding=\"" . get_option('blog_charset') . "\"?>\r\n";
        $xml .= "<$root_element>\r\n";
        $xml .= self::arrayToXml($array[$root_element], 1, $root_element);
        $xml .="</$root_element>";
        return $xml;
    }

    private static function toArray($element) {
        $element = is_string($element) ? htmlspecialchars_decode(trim($element), ENT_QUOTES) : $element;
        if (!empty($element) && is_object($element)) {
            $element = (array) $element;
        }
        if (empty($element)) {
            $element = '';
        }
        if (is_array($element)) {
            foreach ($element as $k => $v) {
                $v = is_string($v) ? htmlspecialchars_decode(trim($v), ENT_QUOTES) : $v;
                if (empty($v)) {
                    $element[$k] = '';
                    continue;
                }
                $add = self::toArray($v);
                if (!empty($add)) {
                    $element[$k] = $add;
                } else {
                    $element[$k] = '';
                }
                // numeric arrays when -> toXml take '_item' suffixes
                // do reverse process here, now it is generic
                // not used here yet
                /* if (is_array($element[$k]) && isset($element[$k][$k.'_item']))
                  {
                  $element[$k] = array_values((array)$element[$k][$k.'_item']);
                  } */
            }
        }

        if (empty($element)) {
            $element = '';
        }

        return $element;
    }

    private static function denormalizeData($data, $image_data) {
        global $_wp_additional_image_sizes;
        static $attached_images_sizes = null;
        //static $home_url=null;

        if (null === $attached_images_sizes) {
            if (isset($_wp_additional_image_sizes)) {
                // all possible thumbnail sizes for attached images
                $attached_images_sizes = array_merge(
                        // additional thumbnail sizes
                        array_keys($_wp_additional_image_sizes),
                        // wp default thumbnail sizes
                        array('thumbnail', 'medium', 'large')
                );
            } else {
                // all possible thumbnail sizes for attached images
                $attached_images_sizes = array('thumbnail', 'medium', 'large');
            }
            //$home_url=home_url('/');
        }

        // which fields need normalization replacements
        $denormalizedFields = array('post_content');

        foreach ($image_data as $media) {
            // used to replace actual urls with hash placeholders
            $image_replace_map = array();

            $mediaid = $media['id'];
            foreach ($attached_images_sizes as $ts) {
                $mediathumbdata = wp_get_attachment_image_src($mediaid, $ts);
                if (!empty($mediathumbdata) && isset($mediathumbdata[0])) {
                    // custom size hash placeholder
                    $image_replace_map['%%' . $media['image_hash'] . '_' . $ts . '%%'] = $mediathumbdata[0];
                }
            }
            $pattern = '%%' . preg_quote($media['image_hash'], '/') . '_[a-zA-Z0-9\-_]*?%%';

            // do replacements
            foreach ($denormalizedFields as $field) {
                $matched = preg_match_all('/' . $pattern . '/', $data[$field], $matches);
                if (false !== $matched && 0 < $matched) {
                    //cred_log($matches);
                    if (isset($matches[0])) {
                        $replacements = array();
                        foreach ($matches[0] as $match) {
                            //cred_log($match);
                            if (isset($image_replace_map[$match]))
                                $replacements[$match] = $image_replace_map[$match];
                            else
                            // fallback to default size, 'medium'
                                $replacements[$match] = $image_replace_map['%%' . $media['image_hash'] . '_medium%%'];
                        }
                        $before = array_keys($replacements);
                        $after = array_values($replacements);
                        $data[$field] = str_replace($before, $after, $data[$field]);
                    }
                }
            }
        }

        // denormalize post/page ids, by using placeholders of slugs (a little more generic)
        // use  get_page_by_path( $page_path, $output, $post_type ); to reverse this transformation
        /* if (!empty($data['meta']))
          {
          // de-normalize fields
          if (!empty($fields['form_settings']->form_action_page) && !is_numeric($fields['form_settings']->form_action_page))
          {
          $_page_=get_page_by_path($fields['form_settings']->form_action_page, OBJECT, 'page');
          if ($_page_ && isset($_page_->ID))
          $fields['form_settings']->form_action_page=$_page_->ID;
          }
          } */
        return $data;
    }

    // recursive sorting, to normalize order for hashes
    private static function normalizeOrdering($data) {
        $dataobject = false;
        if (is_object($data)) {
            $dataobject = true;
            $data = (array) $data;
        }
        if (is_array($data)) {
            ksort($data, SORT_STRING);
            foreach ($data as $k => $v) {
                $isobject = false;
                if (is_object($v)) {
                    $isobject = true;
                    $v = (array) $v;
                }

                if (is_array($v))
                    $v = self::normalizeOrdering($v);

                if ($isobject)
                    $v = (object) $v;

                $data[$k] = $v;
            }
        }
        if ($dataobject)
            $data = (object) $data;

        return $data;
    }

    private static function normalizeData($data) {
        global $_wp_additional_image_sizes;
        static $attached_images_sizes = null;
        static $home_url = null;

        if (null === $attached_images_sizes) {
            if (isset($_wp_additional_image_sizes)) {
                // all possible thumbnail sizes for attached images
                $attached_images_sizes = array_merge(
                        // additional thumbnail sizes
                        array_keys($_wp_additional_image_sizes),
                        // wp default thumbnail sizes
                        array('thumbnail', 'medium', 'large')
                );
            } else {
                // all possible thumbnail sizes for attached images
                $attached_images_sizes = array('thumbnail', 'medium', 'large');
            }
            $home_url = home_url('/');
        }

        // which fields need normalization replacements
        $normalizedFields = array('post_content');

        // used to replace actual urls with hash placeholders
        $image_replace_map = array();

        // handle media/image attachments
        if (isset($data['media']) && !empty($data['media'])) {
            $attached_media = $data['media'];
            // re-create media array without ordering that breaks hash
            $data['media'] = array();
            foreach ($attached_media as $ii => $media) {
                $mediaid = $media['ID'];
                foreach ($attached_images_sizes as $ts) {
                    $mediathumbdata = wp_get_attachment_image_src($mediaid, $ts);
                    if (!empty($mediathumbdata) && isset($mediathumbdata[0])) {
                        // custom size hash placeholder
                        $image_replace_map[$mediathumbdata[0]] = '%%' . $media['image_hash'] . '_' . $ts . '%%';
                    }
                }

                // normalize guid
                $media['guid'] = $media['image_hash'];
                //$media['base_name']=$media['image_hash'];
                // re-create media array without ordering that breaks hash
                $data['media'][$media['image_hash']] = $media;
                // free some memory
                unset($attached_media[$ii]);
            }
            // free some memory
            unset($attached_media);

            // NOTE: notifications also have numeric ordering, which may not matter
            // however right now the notifications ordering matters in computing the hash
            // do any image replacements to normalize content
            if (!empty($image_replace_map)) {
                $before = array_keys($image_replace_map);
                $after = array_values($image_replace_map);
                foreach ($normalizedFields as $field) {
                    // normalize field by using placeholders
                    if (isset($data[$field]))
                        $data[$field] = str_replace($before, $after, $data[$field]);
                }
            }
        }

        // normalize post/page ids, by using placeholders of slugs (a little more generic)
        // use  get_page_by_path( $page_path, $output, $post_type ); to reverse this transformation
        if (!empty($data['meta'])) {
            //if (isset($data['meta']['form_settings']) && is_numeric($data['meta']['form_settings']->form_action_page))
            //    $data['meta']['form_settings']->form_action_page=/*basename(*/ untrailingslashit(str_replace($home_url, '' /*'%%HOME_URL%%'*/, get_permalink($data['meta']['form_settings']->form_action_page))); //);
            if (isset($data['meta']['form_settings']) && isset($data['meta']['form_settings']->form['action_page']) && is_numeric($data['meta']['form_settings']->form['action_page'])) {
                $_page_id = intval($data['meta']['form_settings']->form['action_page']);
                $data['meta']['form_settings']->form['action_page'] = untrailingslashit(str_replace($home_url, '', get_permalink($_page_id)));
            }
        }

        // normalize ordering
        $data = self::normalizeOrdering($data);

        return $data;
    }

    private static function excludeFields($data, $include) {
        $dataobject = false;
        if (is_object($data)) {
            $data = (array) $data;
            $dataobject = true;
        }

        foreach ($data as $k => $v) {
            if (!isset($include[$k]) && !isset($include['*'])) {
                unset($data[$k]);
                continue;
            }
            if (isset($include[$k]) && is_array($include[$k])) {
                $data[$k] = self::excludeFields($data[$k], $include[$k]);
            } elseif (isset($include['*']) && is_array($include['*'])) {
                $data[$k] = self::excludeFields($data[$k], $include['*']);
            }
        }

        if ($dataobject)
            $data = (object) $data;

        return $data;
    }

    private static function cloneData($data) {
        /* $isObject=false;
          if (is_object($data))
          {
          $data=(array)$data;
          $isObject=true;
          }
          if (is_array($data))
          {
          $clone=array();
          foreach ($data as $key=>$val)
          {
          if (is_array($val) || is_object($val))
          $clone[$key]=self::cloneData($val);
          else
          $clone[$key]=$val;
          }
          }
          else
          $clone=$data;

          if ($isObject)
          $clone=(object)$clone;

          return $clone; */

        // should work great for just data structures
        return unserialize(serialize($data));
    }

    private static function doHash($data1, $normalizeData = true) {
        // STEP 0: if using reference clone the data
        $data = self::cloneData($data1);

        // STEP 1: normalize placeholders, ordering etc..
        // normalized data if needed
        if ($normalizeData)
            $data = self::normalizeData($data);

        // STEP 2: exclude fields not relevant to hash computation
        // hash computed on only these data fields
        $hashFields = array(
            'post_content' => true,
            'post_title' => true,
            'post_type' => true,
            'meta' => true,
            'media' => array(
                '*' => array(
                    //'post_title' => true,
                    'post_content' => true,
                    'post_excerpt' => true,
                    'post_status' => true,
                    'post_type' => true,
                    'post_mime_type' => true,
                    //'guid' => true,
                    'alt' => true,
                    /* 'image_data' => true, */
                    'image_hash' => true
                )
            )
        );

        //cred_log($data);
        // if field is NOT relevant to hash computation, ignore (remove)
        $data = self::excludeFields($data, $hashFields);

        // STEP 3: normalize spaces, new lines etc..
        // collapse spaces, new lines etc.. ghost new lines break hash comparisons
        if (isset($data['post_content']))
            $data['post_content'] = preg_replace('/\s+/', '', $data['post_content']);

        if (isset($data['meta'])) {
            if (isset($data['meta']['extra'])) {
                if (isset($data['meta']['extra']->css))
                    $data['meta']['extra']->css = preg_replace('/\s+/', '', $data['meta']['extra']->css);
                if (isset($data['meta']['extra']->js))
                    $data['meta']['extra']->js = preg_replace('/\s+/', '', $data['meta']['extra']->js);
            }
            if (isset($data['meta']['form_settings'])) {
                if (isset($data['meta']['form_settings']->form['action_message']))
                    $data['meta']['form_settings']->form['action_message'] = preg_replace('/\s+/', '', $data['meta']['form_settings']->form['action_message']);
            }
            if (isset($data['meta']['notification'])) {
                if (isset($data['meta']['notification']->notifications) && is_array($data['meta']['notification']->notifications)) {
                    foreach ($data['meta']['notification']->notifications as $ii => $notif)
                        $data['meta']['notification']->notifications[$ii]['mail']['body'] = preg_replace('/\s+/', '', $data['meta']['notification']->notifications[$ii]['mail']['body']);
                }
            }
            //EMERSON: Increase consistency of hashes check in module manager 1.1
            /* START */
            $data['meta']['form_settings'] = get_object_vars($data['meta']['form_settings']);
            $data['meta']['notification'] = get_object_vars($data['meta']['notification']);

            if ((isset($data['meta']['form_settings']['form'])) && (!(empty($data['meta']['form_settings']['form'])))) {

                $set_to_integer_hashing = array('has_media_button', 'hide_comments', 'include_captcha_scaffold', 'include_wpml_scaffold', 'redirect_delay');

                foreach ($data['meta']['form_settings']['form'] as $k1 => $v1) {

                    if (($k1 == 'action_page') || ($k1 == 'action_message')) {

                        unset($data['meta']['form_settings']['form'][$k1]);
                    }

                    if (in_array($k1, $set_to_integer_hashing)) {

                        $data['meta']['form_settings']['form'][$k1] = (integer) $data['meta']['form_settings']['form'][$k1];
                    }
                }
            }
            if ((isset($data['meta']['notification']['notifications'])) && (!(empty($data['meta']['notification']['notifications'])))) {

                foreach ($data['meta']['notification']['notifications'] as $k2 => $v2) {

                    foreach ($v2 as $k3 => $v3) {

                        if ($k3 == 'to') {

                            if ((isset($data['meta']['notification']['notifications'][$k2]['to']['type'])) && (!(empty($data['meta']['notification']['notifications'][$k2]['to']['type'])))) {

                                if (!(is_array($data['meta']['notification']['notifications'][$k2]['to']['type']))) {
                                    $data['meta']['notification']['notifications'][$k2]['to']['type'] = array($data['meta']['notification']['notifications'][$k2]['to']['type']);
                                }
                            }
                        }
                    }
                }
            }
            /* END */
        }

        //cred_log($data);
        // STEP 4: compute and return actual hash now, on normalized data

        $hash = sha1(serialize($data));
        //cred_log($hash);
        return $hash;
    }

    public static function getSelectedFormsForExport($form_ids = array(), $options = array(), &$mode, &$hashes = false) {
        if (empty($form_ids))
            return array();

        $data = array();

        $forms = CRED_Loader::get('MODEL/Forms')->getFormsForExport($form_ids);

        $mode = 'forms';
        if (!empty($forms) && count($forms) > 0) {
            if ('all' == $form_ids) {
                $mode = 'all-post-forms';
            } elseif (count($forms) == 1) {
                $mode = sanitize_title($forms[0]->post_title);
            } else {
                $mode = 'selected-forms';
            }
        }
        // hashes data
        if (false !== $hashes)
            $hashes = array();

        if (!empty($forms)) {
            $export_tags = array('ID', 'post_content', 'post_title', 'post_name', 'post_type');
            $data[self::$root] = array('__key' => 'form');

            // allow 3rd-party to add extra data on export
            $forms = apply_filters('cred_export_forms', $forms);

            foreach ($forms as $key => $form) {
                $form = (array) $form;
                // normalize data

                $form = self::normalizeData($form);
                //cred_log($form);
                // compute and store (unique) hash
                if (isset($options['hash']) && $options['hash'] && false !== $hashes) {
                    // compute hash without doing additional normalization
                    $hashes[$form['ID']] = self::doHash($form, false);
                }

                if ($form['post_name']) {
                    $form_data = array();
                    foreach ($export_tags as $e_tag) {
                        if (isset($form[$e_tag])) {
                            $form_data[$e_tag] = $form[$e_tag];
                        }
                    }
                    $data[self::$root]['form-' . $form['ID']] = $form_data;
                    if (!empty($form['meta'])) {
                        $data[self::$root]['form-' . $form['ID']]['meta'] = array();
                        foreach ($form['meta'] as $meta_key => $meta_value) {
                            $data[self::$root]['form-' . $form['ID']]['meta'][$meta_key] = maybe_unserialize($meta_value);
                        }
                        if (empty($data[self::$root]['form-' . $form['ID']]['meta'])) {
                            unset($data[self::$root]['form-' . $form['ID']]['meta']);
                        }
                    }
                    if (!empty($form['media'])) {
                        // covert back to numeric array ordering for xml export (changed when data were normalized)
                        $form['media'] = array_values($form['media']);

                        $data['form']['form-' . $form['ID']]['media'] = array();
                        foreach ($form['media'] as $media_key => $media_value) {
                            $data[self::$root]['form-' . $form['ID']]['media'][$media_key] = maybe_unserialize($media_value);
                        }
                        if (empty($data[self::$root]['form-' . $form['ID']]['media'])) {
                            unset($data[self::$root]['form-' . $form['ID']]['media']);
                        }
                    }
                }
            }
        }
        return $data;
    }

    public static function getSelectedUserFormsForExport($form_ids = array(), $options = array(), &$mode, &$hashes = false) {
        if (empty($form_ids))
            return array();

        $data = array();

        $forms = CRED_Loader::get('MODEL/UserForms')->getFormsForExport($form_ids);

        $mode = 'forms';
        if (!empty($forms) && count($forms) > 0) {
            if ('all' == $form_ids) {
                $mode = 'all-user-forms';
            } elseif (count($forms) == 1) {
                $mode = sanitize_title($forms[0]->post_title);
            } else {
                $mode = 'selected-forms';
            }
        }
        // hashes data
        if (false !== $hashes)
            $hashes = array();

        if (!empty($forms)) {
            $export_tags = array('ID', 'post_content', 'post_title', 'post_name', 'post_type', 'user_role');
            $data[self::$root] = array('__key' => 'form');

            // allow 3rd-party to add extra data on export
            $forms = apply_filters('cred_export_forms', $forms);

            foreach ($forms as $key => $form) {
                $form = (array) $form;
                // normalize data

                $form = self::normalizeData($form);
                //cred_log($form);
                // compute and store (unique) hash
                if (isset($options['hash']) && $options['hash'] && false !== $hashes) {
                    // compute hash without doing additional normalization
                    $hashes[$form['ID']] = self::doHash($form, false);
                }

                if ($form['post_name']) {
                    $form_data = array();
                    foreach ($export_tags as $e_tag) {
                        if (isset($form[$e_tag])) {
                            $form_data[$e_tag] = $form[$e_tag];
                        }
                    }
                    $data[self::$root]['form-' . $form['ID']] = $form_data;
                    if (!empty($form['meta'])) {
                        $data[self::$root]['form-' . $form['ID']]['meta'] = array();
                        foreach ($form['meta'] as $meta_key => $meta_value) {
                            $data[self::$root]['form-' . $form['ID']]['meta'][$meta_key] = maybe_unserialize($meta_value);
                        }
                        if (empty($data[self::$root]['form-' . $form['ID']]['meta'])) {
                            unset($data[self::$root]['form-' . $form['ID']]['meta']);
                        }
                    }
                    if (!empty($form['media'])) {
                        // covert back to numeric array ordering for xml export (changed when data were normalized)
                        $form['media'] = array_values($form['media']);

                        $data['form']['form-' . $form['ID']]['media'] = array();
                        foreach ($form['media'] as $media_key => $media_value) {
                            $data[self::$root]['form-' . $form['ID']]['media'][$media_key] = maybe_unserialize($media_value);
                        }
                        if (empty($data[self::$root]['form-' . $form['ID']]['media'])) {
                            unset($data[self::$root]['form-' . $form['ID']]['media']);
                        }
                    }
                }
            }
        }
        return $data;
    }

    private static function output($xml, $ajax, $mode) {
        $sitename = sanitize_key(get_bloginfo('name'));
        if (!empty($sitename)) {
            $sitename .= '-';
        }

        $filename = $sitename . $mode . '-' . date('Y-m-d') . '.xml';

        $data = $xml;

        if (self::$use_zip_if_available && class_exists('ZipArchive')) {
            $zipname = $filename . '.zip';
            $zip = new ZipArchive();
            $tmp = 'tmp';
            // http://php.net/manual/en/function.tempnam.php#93256
            if (function_exists('sys_get_temp_dir'))
                $tmp = sys_get_temp_dir();
            $file = tempnam($tmp, "zip");
            $zip->open($file, ZipArchive::OVERWRITE);

            $res = $zip->addFromString($filename, $xml);
            $zip->close();
            $data = file_get_contents($file);
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=" . $zipname);
            header("Content-Type: application/zip");
            header("Content-length: " . strlen($data) . "\n\n");
            header("Content-Transfer-Encoding: binary");
            if ($ajax)
                header("Set-Cookie: __CREDExportDownload=true; path=/");
            echo $data;
            unset($data);
            unset($xml);
            unlink($file);
            die();
        }
        else {
            // download the xml.
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=" . $filename);
            header("Content-Type: application/xml");
            header("Content-length: " . strlen($data) . "\n\n");
            if ($ajax)
                header("Set-Cookie: __CREDExportDownload=true; path=/");
            echo $data;
            unset($data);
            unset($xml);
            die();
        }
    }

    private static function readXML($file) {
        $data = array();
        $info = pathinfo($file['name']);
        $is_zip = $info['extension'] == 'zip' ? true : false;
        if ($is_zip) {
            $zip = zip_open(urldecode($file['tmp_name']));
            if (is_resource($zip)) {
                $zip_entry = zip_read($zip);
                if (is_resource($zip_entry) && zip_entry_open($zip, $zip_entry)) {
                    $data = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                    zip_entry_close($zip_entry);
                } else
                    return new WP_Error('could_not_open_file', __('No zip entry', 'wp-cred'));
            }
            else {
                return new WP_Error('could_not_open_file', __('Unable to open zip file', 'wp-cred'));
            }
        } else {
            $fh = fopen($file['tmp_name'], 'r');
            if ($fh) {
                $data = fread($fh, $file['size']);
                fclose($fh);
            }
        }

        if (!empty($data)) {

            if (!function_exists('simplexml_load_string')) {
                return new WP_Error('xml_missing', __('The Simple XML library is missing.', 'wp-cred'));
            }
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($data);
            //print_r($xml);

            if (!$xml) {
                return new WP_Error('not_xml_file', sprintf(__('The XML file (%s) could not be read.', 'wp-cred'), $file['name']));
            }

            $import_data = self::toArray($xml);
            unset($xml);
            //print_r($import_data);
            return $import_data;
        } else {
            return new WP_Error('could_not_open_file', __('Could not read the import file.', 'wp-cred'));
        }
        return new WP_Error('unknown error', __('Unknown error during import', 'wp-cred'));
    }

    private static function importSingleForm($form_data, $fmodel, &$options, &$results) {
        $form = new stdClass;
        $form->ID = '';
        $form->post_title = $form_data['post_title'];
        $form->post_content = isset($form_data['post_content']) ? $form_data['post_content'] : '';
        if (isset($options['force_skip_post_name']) || isset($options['force_overwrite_post_name']) || isset($options['force_duplicate_post_name'])) {
            $form->post_name = isset($form_data['post_name']) ? $form_data['post_name'] : '';
        }
        $form->post_status = 'private';
        $form->post_type = CRED_FORMS_CUSTOM_POST_NAME;

        $fields = array();
        if (isset($form_data['meta']) && is_array($form_data['meta']) && !empty($form_data['meta'])) {
            // old format, backwards compatibility
            if (
                    isset($form_data['meta']['form_settings']['form_type']) ||
                    isset($form_data['meta']['form_settings']['post_type']) ||
                    isset($form_data['meta']['form_settings']['cred_theme_css'])
            ) {
                $fields['form_settings'] = new stdClass;
                $fields['form_settings']->form_type = isset($form_data['meta']['form_settings']['form_type']) ? $form_data['meta']['form_settings']['form_type'] : '';
                $fields['form_settings']->form_action = isset($form_data['meta']['form_settings']['form_action']) ? $form_data['meta']['form_settings']['form_action'] : '';
                $fields['form_settings']->form_action_page = isset($form_data['meta']['form_settings']['form_action_page']) ? $form_data['meta']['form_settings']['form_action_page'] : '';
                $fields['form_settings']->redirect_delay = isset($form_data['meta']['form_settings']['redirect_delay']) ? intval($form_data['meta']['form_settings']['redirect_delay']) : 0;
                $fields['form_settings']->message = isset($form_data['meta']['form_settings']['message']) ? $form_data['meta']['form_settings']['message'] : '';
                $fields['form_settings']->hide_comments = (isset($form_data['meta']['form_settings']['hide_comments']) && $form_data['meta']['form_settings']['hide_comments'] == '1') ? 1 : 0;
                $fields['form_settings']->include_captcha_scaffold = (isset($form_data['meta']['form_settings']['include_captcha_scaffold']) && $form_data['meta']['form_settings']['include_captcha_scaffold'] == '1') ? 1 : 0;
                $fields['form_settings']->include_wpml_scaffold = (isset($form_data['meta']['form_settings']['include_wpml_scaffold']) && $form_data['meta']['form_settings']['include_wpml_scaffold'] == '1') ? 1 : 0;
                $fields['form_settings']->has_media_button = (isset($form_data['meta']['form_settings']['has_media_button']) && $form_data['meta']['form_settings']['has_media_button'] == '1') ? 1 : 0;
                $fields['form_settings']->post_type = isset($form_data['meta']['form_settings']['post_type']) ? $form_data['meta']['form_settings']['post_type'] : '';
                $fields['form_settings']->post_status = isset($form_data['meta']['form_settings']['post_status']) ? $form_data['meta']['form_settings']['post_status'] : 'draft';
                $fields['form_settings']->cred_theme_css = isset($form_data['meta']['form_settings']['cred_theme_css']) ? $form_data['meta']['form_settings']['cred_theme_css'] : 'minimal';

                $fields['wizard'] = isset($form_data['meta']['wizard']) ? intval($form_data['meta']['wizard']) : -1;

                $fields['extra'] = new stdClass;
                $fields['extra']->css = isset($form_data['meta']['extra']['css']) ? $form_data['meta']['extra']['css'] : '';
                $fields['extra']->js = isset($form_data['meta']['extra']['js']) ? $form_data['meta']['extra']['js'] : '';

                $fields['extra']->messages = $fmodel->getDefaultMessages();

                if (isset($form_data['meta']['extra']['messages']['messages_item'])) {
                    // make it array
                    if (!isset($form_data['meta']['extra']['messages']['messages_item'][0]))
                        $form_data['meta']['extra']['messages']['messages_item'] = array($form_data['meta']['extra']['messages']['messages_item']);

                    foreach ($form_data['meta']['extra']['messages']['messages_item'] as $msg) {
                        foreach (array_keys($fields['extra']->messages) as $msgid) {
                            if (isset($msg[$msgid]))
                                $fields['extra']->messages[$msgid] = $msg;
                        }
                    }
                }

                $fields['notification'] = new stdClass;
                $fields['notification']->notifications = array();
                if (isset($form_data['meta']['notification']['notifications']['notifications_item'])) {
                    // make it array
                    if (!isset($form_data['meta']['notification']['notifications']['notifications_item'][0]))
                        $form_data['meta']['notification']['notifications']['notifications_item'] = array($form_data['meta']['notification']['notifications']['notifications_item']);

                    foreach ($form_data['meta']['notification']['notifications']['notifications_item'] as $notif) {
                        $tmp = array();
                        $tmp['mail_to_type'] = isset($notif['mail_to_type']) ? $notif['mail_to_type'] : '';
                        $tmp['mail_to_user'] = isset($notif['mail_to_user']) ? $notif['mail_to_user'] : '';
                        $tmp['mail_to_field'] = isset($notif['mail_to_field']) ? $notif['mail_to_field'] : '';
                        $tmp['mail_to_specific'] = isset($notif['mail_to_specific']) ? $notif['mail_to_specific'] : '';
                        // add new fields From Addr, From Name
                        $tmp['from_addr'] = isset($notif['from_addr']) ? $notif['from_addr'] : '';
                        $tmp['from_name'] = isset($notif['from_name']) ? $notif['from_name'] : '';
                        $tmp['subject'] = isset($notif['subject']) ? $notif['subject'] : '';
                        $tmp['body'] = isset($notif['body']) ? $notif['body'] : '';
                        $fields['notification']->notifications[] = $tmp;
                    }
                }
                $fields['notification']->enable = (isset($form_data['meta']['notification']['enable']) && $form_data['meta']['notification']['enable'] == '1') ? 1 : 0;
            }
            // new cred fields format here
            else {
                $fields['form_settings'] = (object) array(
                            'form' => array(),
                            'post' => array()
                );
                $fields['form_settings']->form['type'] = isset($form_data['meta']['form_settings']['form']['type']) ? $form_data['meta']['form_settings']['form']['type'] : '';
                $fields['form_settings']->form['action'] = isset($form_data['meta']['form_settings']['form']['action']) ? $form_data['meta']['form_settings']['form']['action'] : '';
                $fields['form_settings']->form['action_page'] = isset($form_data['meta']['form_settings']['form']['action_page']) ? $form_data['meta']['form_settings']['form']['action_page'] : '';
                $fields['form_settings']->form['redirect_delay'] = isset($form_data['meta']['form_settings']['form']['redirect_delay']) ? intval($form_data['meta']['form_settings']['form']['redirect_delay']) : 0;
                $fields['form_settings']->form['action_message'] = isset($form_data['meta']['form_settings']['form']['action_message']) ? $form_data['meta']['form_settings']['form']['action_message'] : '';
                $fields['form_settings']->form['hide_comments'] = (isset($form_data['meta']['form_settings']['form']['hide_comments']) && $form_data['meta']['form_settings']['form']['hide_comments'] == '1') ? 1 : 0;
                $fields['form_settings']->form['include_captcha_scaffold'] = (isset($form_data['meta']['form_settings']['form']['include_captcha_scaffold']) && $form_data['meta']['form_settings']['form']['include_captcha_scaffold'] == '1') ? 1 : 0;
                $fields['form_settings']->form['include_wpml_scaffold'] = (isset($form_data['meta']['form_settings']['form']['include_wpml_scaffold']) && $form_data['meta']['form_settings']['form']['include_wpml_scaffold'] == '1') ? 1 : 0;
                $fields['form_settings']->form['has_media_button'] = (isset($form_data['meta']['form_settings']['form']['has_media_button']) && $form_data['meta']['form_settings']['form']['has_media_button'] == '1') ? 1 : 0;
                $fields['form_settings']->post['post_type'] = isset($form_data['meta']['form_settings']['post']['post_type']) ? $form_data['meta']['form_settings']['post']['post_type'] : '';
                $fields['form_settings']->post['post_status'] = isset($form_data['meta']['form_settings']['post']['post_status']) ? $form_data['meta']['form_settings']['post']['post_status'] : 'draft';
                $fields['form_settings']->form['theme'] = isset($form_data['meta']['form_settings']['form']['theme']) ? $form_data['meta']['form_settings']['form']['theme'] : 'minimal';

                $fields['wizard'] = isset($form_data['meta']['wizard']) ? intval($form_data['meta']['wizard']) : -1;

                $fields['extra'] = (object) array(
                            'css' => '',
                            'js' => '',
                            'messages' => $fmodel->getDefaultMessages()
                );
                $fields['extra']->css = isset($form_data['meta']['extra']['css']) ? $form_data['meta']['extra']['css'] : '';
                $fields['extra']->js = isset($form_data['meta']['extra']['js']) ? $form_data['meta']['extra']['js'] : '';

                //EMERSON: Fix bug on Form text messages value not imported in CRED 1.2.2
                //This will cause the hash to be different after import,e.g. in Module manager 1.1
                //Commented are old codes
                /* START */

                //if (isset($form_data['meta']['extra']['messages']['messages_item']))
                if (isset($form_data['meta']['extra']['messages'])) {
                    // make it array
                    /*
                      if (!isset($form_data['meta']['extra']['messages']['messages_item'][0]))
                      $form_data['meta']['extra']['messages']['messages_item']=array($form_data['meta']['extra']['messages']['messages_item']);
                     */
                    if (!isset($form_data['meta']['extra']['messages']))
                        $form_data['meta']['extra']['messages'] = array($form_data['meta']['extra']['messages']);

                    //foreach ($form_data['meta']['extra']['messages']['messages_item'] as $msg)
                    foreach ($form_data['meta']['extra']['messages'] as $msg) {
                        /*
                          foreach (array_keys($fields['extra']->messages) as $msgid)
                          {
                          if (isset($msg[$msgid]))
                          $fields['extra']->messages[$msgid]=$msg;
                          }
                         */
                        foreach (($fields['extra']->messages) as $msgid_key => $msgid_value) {

                            if (isset($form_data['meta']['extra']['messages'][$msgid_key]) && $form_data['meta']['extra']['messages'][$msgid_key] != $msgid_value) {

                                $fields['extra']->messages[$msgid_key] = $form_data['meta']['extra']['messages'][$msgid_key];
                            }
                        }
                    }
                }
                /* END */

                $fields['notification'] = (object) array(
                            'enable' => 0,
                            'notifications' => array()
                );
                if (isset($form_data['meta']['notification']['notifications']['notifications_item'])) {
                    // make it array
                    if (!isset($form_data['meta']['notification']['notifications']['notifications_item'][0]))
                        $form_data['meta']['notification']['notifications']['notifications_item'] = array($form_data['meta']['notification']['notifications']['notifications_item']);

                    foreach ($form_data['meta']['notification']['notifications']['notifications_item'] as $notif) {
                        $tmp = array();
                        $tmp['event'] = isset($notif['event']) ? $notif['event'] : array();
                        if (isset($tmp['event']['condition']['condition_item'])) {
                            if (!isset($tmp['event']['condition']['condition_item'][0]))
                                $tmp['event']['condition']['condition_item'] = array($tmp['event']['condition']['condition_item']);
                            $tmp['event']['condition'] = $tmp['event']['condition']['condition_item'];
                        }
                        $tmp['to'] = isset($notif['to']) ? $notif['to'] : array();
                        if (isset($tmp['to']['type']['type_item'])) {
                            if (!is_array($tmp['to']['type']['type_item']))
                                $tmp['to']['type']['type_item'] = array($tmp['to']['type']['type_item']);
                            $tmp['to']['type'] = $tmp['to']['type']['type_item'];
                        }
                        // add new fields From Addr, From Name
                        $tmp['from'] = isset($notif['from']) ? $notif['from'] : array();
                        $tmp['mail'] = isset($notif['mail']) ? $notif['mail'] : array();
                        $fields['notification']->notifications[] = $tmp;
                    }
                }
                $fields['notification']->enable = (isset($form_data['meta']['notification']['enable']) && $form_data['meta']['notification']['enable'] == '1') ? 1 : 0;

                //CRED post expiration import (new version 1.2.6)
                /* START */

                $fields['post_expiration'] = array(
                    'action' => array(),
                    'enable' => 0,
                    'expiration_time' => array()
                );

                if (isset($form_data['meta']['post_expiration'])) {

                    if (isset($form_data['meta']['post_expiration']['action'])) {
                        $fields['post_expiration']['action'] = $form_data['meta']['post_expiration']['action'];
                    }
                    if (isset($form_data['meta']['post_expiration']['enable'])) {
                        $fields['post_expiration']['enable'] = $form_data['meta']['post_expiration']['enable'];
                    }
                    if (isset($form_data['meta']['post_expiration']['expiration_time'])) {
                        $fields['post_expiration']['expiration_time'] = $form_data['meta']['post_expiration']['expiration_time'];
                    }
                }
                /* END */
            }

            // change format here and provide defaults also
            $fields = array_merge(array(
                'form_settings' => (object) array(
                    'form' => array(),
                    'post' => array()
                ),
                'notification' => (object) array(
                    'enable' => 0,
                    'notifications' => array()
                ),
                'extra' => (object) array(
                    'css' => '',
                    'js' => '',
                    'messages' => $fmodel->getDefaultMessages()
                )
                    ), $fmodel->changeFormat($fields)
            );
        }

        // de-normalize fields
        if (!empty($fields['form_settings']->form['action_page']) && !is_numeric($fields['form_settings']->form['action_page'])) {
            $_page_ = get_page_by_path($fields['form_settings']->form['action_page'], OBJECT, 'page');
            if ($_page_ && isset($_page_->ID))
                $fields['form_settings']->form['action_page'] = $_page_->ID;
            else
                $fields['form_settings']->form['action_page'] = '';
        }
        $_form_id = false;
        if (isset($options['overwrite_forms']) && $options['overwrite_forms']) {

            if (isset($options['force_skip_post_name']) || isset($options['force_overwrite_post_name']) || isset($options['force_duplicate_post_name'])) {
                $old_form = get_page_by_path($form->post_name, OBJECT, CRED_FORMS_CUSTOM_POST_NAME);
            } else {
                $old_form = get_page_by_title($form->post_title, OBJECT, CRED_FORMS_CUSTOM_POST_NAME);
            }
            if ($old_form) {
                $form->ID = $old_form->ID;
                $_form_id = $form->ID;
                if ($fmodel->updateForm($form, $fields)) {
                    $results['updated'] ++;
                } else {
                    $results['failed'] ++;
                    $results['errors'][] = sprintf(__('Item %s could not be saved', 'wp-cred'), $form->post_title);
                }
            } else {
                $_form_id = $fmodel->saveForm($form, $fields);
                if ($_form_id) {
                    $results['new'] ++;
                } else {
                    $results['failed'] ++;
                    $results['errors'][] = sprintf(__('Item %s could not be saved', 'wp-cred'), $form->post_title);
                }
            }
        } else {
            $_form_id = $fmodel->saveForm($form, $fields);
            $results['new'] ++;
        }

        if ($_form_id)
        // allow 3rd-party to import extra data per form and update results variable accordingly
            $results = apply_filters('cred_import_form', $results, $_form_id, $form_data);

        return $_form_id;
    }

    private static function importSingleUserForm($form_data, $fmodel, &$options, &$results) {
        $form = new stdClass;
        $form->ID = '';
        $form->post_title = $form_data['post_title'];
        $form->post_content = isset($form_data['post_content']) ? $form_data['post_content'] : '';
        if (isset($options['force_skip_post_name']) || isset($options['force_overwrite_post_name']) || isset($options['force_duplicate_post_name'])) {
            $form->post_name = isset($form_data['post_name']) ? $form_data['post_name'] : '';
        }
        $form->post_status = 'private';
        $form->post_type = CRED_USER_FORMS_CUSTOM_POST_NAME;

        $fields = array();
        if (isset($form_data['meta']) && is_array($form_data['meta']) && !empty($form_data['meta'])) {
            // old format, backwards compatibility
            if (
                    isset($form_data['meta']['form_settings']['form_type']) ||
                    isset($form_data['meta']['form_settings']['post_type']) ||
                    isset($form_data['meta']['form_settings']['cred_theme_css'])
            ) {
                $fields['form_settings'] = new stdClass;
                $fields['form_settings']->form_type = isset($form_data['meta']['form_settings']['form_type']) ? $form_data['meta']['form_settings']['form_type'] : '';
                $fields['form_settings']->form_action = isset($form_data['meta']['form_settings']['form_action']) ? $form_data['meta']['form_settings']['form_action'] : '';
                $fields['form_settings']->form_action_page = isset($form_data['meta']['form_settings']['form_action_page']) ? $form_data['meta']['form_settings']['form_action_page'] : '';
                $fields['form_settings']->redirect_delay = isset($form_data['meta']['form_settings']['redirect_delay']) ? intval($form_data['meta']['form_settings']['redirect_delay']) : 0;
                $fields['form_settings']->message = isset($form_data['meta']['form_settings']['message']) ? $form_data['meta']['form_settings']['message'] : '';
                $fields['form_settings']->hide_comments = (isset($form_data['meta']['form_settings']['hide_comments']) && $form_data['meta']['form_settings']['hide_comments'] == '1') ? 1 : 0;
                $fields['form_settings']->include_captcha_scaffold = (isset($form_data['meta']['form_settings']['include_captcha_scaffold']) && $form_data['meta']['form_settings']['include_captcha_scaffold'] == '1') ? 1 : 0;
                $fields['form_settings']->include_wpml_scaffold = (isset($form_data['meta']['form_settings']['include_wpml_scaffold']) && $form_data['meta']['form_settings']['include_wpml_scaffold'] == '1') ? 1 : 0;
                $fields['form_settings']->has_media_button = (isset($form_data['meta']['form_settings']['has_media_button']) && $form_data['meta']['form_settings']['has_media_button'] == '1') ? 1 : 0;
                $fields['form_settings']->post_type = isset($form_data['meta']['form_settings']['post_type']) ? $form_data['meta']['form_settings']['post_type'] : '';
                $fields['form_settings']->post_status = isset($form_data['meta']['form_settings']['post_status']) ? $form_data['meta']['form_settings']['post_status'] : 'draft';
                $fields['form_settings']->cred_theme_css = isset($form_data['meta']['form_settings']['cred_theme_css']) ? $form_data['meta']['form_settings']['cred_theme_css'] : 'minimal';

                $fields['wizard'] = isset($form_data['meta']['wizard']) ? intval($form_data['meta']['wizard']) : -1;

                $fields['extra'] = new stdClass;
                $fields['extra']->css = isset($form_data['meta']['extra']['css']) ? $form_data['meta']['extra']['css'] : '';
                $fields['extra']->js = isset($form_data['meta']['extra']['js']) ? $form_data['meta']['extra']['js'] : '';

                $fields['extra']->messages = $fmodel->getDefaultMessages();

                if (isset($form_data['meta']['extra']['messages']['messages_item'])) {
                    // make it array
                    if (!isset($form_data['meta']['extra']['messages']['messages_item'][0]))
                        $form_data['meta']['extra']['messages']['messages_item'] = array($form_data['meta']['extra']['messages']['messages_item']);

                    foreach ($form_data['meta']['extra']['messages']['messages_item'] as $msg) {
                        foreach (array_keys($fields['extra']->messages) as $msgid) {
                            if (isset($msg[$msgid]))
                                $fields['extra']->messages[$msgid] = $msg;
                        }
                    }
                }

                $fields['notification'] = new stdClass;
                $fields['notification']->notifications = array();
                if (isset($form_data['meta']['notification']['notifications']['notifications_item'])) {
                    // make it array
                    if (!isset($form_data['meta']['notification']['notifications']['notifications_item'][0]))
                        $form_data['meta']['notification']['notifications']['notifications_item'] = array($form_data['meta']['notification']['notifications']['notifications_item']);

                    foreach ($form_data['meta']['notification']['notifications']['notifications_item'] as $notif) {
                        $tmp = array();
                        $tmp['mail_to_type'] = isset($notif['mail_to_type']) ? $notif['mail_to_type'] : '';
                        $tmp['mail_to_user'] = isset($notif['mail_to_user']) ? $notif['mail_to_user'] : '';
                        $tmp['mail_to_field'] = isset($notif['mail_to_field']) ? $notif['mail_to_field'] : '';
                        $tmp['mail_to_specific'] = isset($notif['mail_to_specific']) ? $notif['mail_to_specific'] : '';
                        // add new fields From Addr, From Name
                        $tmp['from_addr'] = isset($notif['from_addr']) ? $notif['from_addr'] : '';
                        $tmp['from_name'] = isset($notif['from_name']) ? $notif['from_name'] : '';
                        $tmp['subject'] = isset($notif['subject']) ? $notif['subject'] : '';
                        $tmp['body'] = isset($notif['body']) ? $notif['body'] : '';
                        $fields['notification']->notifications[] = $tmp;
                    }
                }
                $fields['notification']->enable = (isset($form_data['meta']['notification']['enable']) && $form_data['meta']['notification']['enable'] == '1') ? 1 : 0;
            }
            // new cred fields format here
            else {
                $fields['form_settings'] = (object) array(
                            'form' => array(),
                            'post' => array()
                );
                $fields['form_settings']->form['type'] = isset($form_data['meta']['form_settings']['form']['type']) ? $form_data['meta']['form_settings']['form']['type'] : '';
                $fields['form_settings']->form['action'] = isset($form_data['meta']['form_settings']['form']['action']) ? $form_data['meta']['form_settings']['form']['action'] : '';
                $fields['form_settings']->form['action_page'] = isset($form_data['meta']['form_settings']['form']['action_page']) ? $form_data['meta']['form_settings']['form']['action_page'] : '';
                $fields['form_settings']->form['redirect_delay'] = isset($form_data['meta']['form_settings']['form']['redirect_delay']) ? intval($form_data['meta']['form_settings']['form']['redirect_delay']) : 0;
                $fields['form_settings']->form['action_message'] = isset($form_data['meta']['form_settings']['form']['action_message']) ? $form_data['meta']['form_settings']['form']['action_message'] : '';
                $fields['form_settings']->form['hide_comments'] = (isset($form_data['meta']['form_settings']['form']['hide_comments']) && $form_data['meta']['form_settings']['form']['hide_comments'] == '1') ? 1 : 0;

                $fields['form_settings']->form['autogenerate_username_scaffold'] = (isset($form_data['meta']['form_settings']['form']['autogenerate_username_scaffold']) && $form_data['meta']['form_settings']['form']['autogenerate_username_scaffold'] == '1') ? 1 : 0;
                $fields['form_settings']->form['autogenerate_nickname_scaffold'] = (isset($form_data['meta']['form_settings']['form']['autogenerate_nickname_scaffold']) && $form_data['meta']['form_settings']['form']['autogenerate_nickname_scaffold'] == '1') ? 1 : 0;
                $fields['form_settings']->form['autogenerate_password_scaffold'] = (isset($form_data['meta']['form_settings']['form']['autogenerate_password_scaffold']) && $form_data['meta']['form_settings']['form']['autogenerate_password_scaffold'] == '1') ? 1 : 0;

                $fields['form_settings']->form['include_captcha_scaffold'] = (isset($form_data['meta']['form_settings']['form']['include_captcha_scaffold']) && $form_data['meta']['form_settings']['form']['include_captcha_scaffold'] == '1') ? 1 : 0;
                $fields['form_settings']->form['include_wpml_scaffold'] = (isset($form_data['meta']['form_settings']['form']['include_wpml_scaffold']) && $form_data['meta']['form_settings']['form']['include_wpml_scaffold'] == '1') ? 1 : 0;
                $fields['form_settings']->form['has_media_button'] = (isset($form_data['meta']['form_settings']['form']['has_media_button']) && $form_data['meta']['form_settings']['form']['has_media_button'] == '1') ? 1 : 0;
                $fields['form_settings']->form['user_role'] = isset($form_data['meta']['form_settings']['form']['user_role']) ? $form_data['meta']['form_settings']['form']['user_role'] : 'subscriber';
                $fields['form_settings']->post['post_type'] = isset($form_data['meta']['form_settings']['post']['post_type']) ? $form_data['meta']['form_settings']['post']['post_type'] : '';
                $fields['form_settings']->post['post_status'] = isset($form_data['meta']['form_settings']['post']['post_status']) ? $form_data['meta']['form_settings']['post']['post_status'] : 'draft';

                $fields['form_settings']->form['theme'] = isset($form_data['meta']['form_settings']['form']['theme']) ? $form_data['meta']['form_settings']['form']['theme'] : 'minimal';

                $fields['wizard'] = isset($form_data['meta']['wizard']) ? intval($form_data['meta']['wizard']) : -1;

                $fields['extra'] = (object) array(
                            'css' => '',
                            'js' => '',
                            'messages' => $fmodel->getDefaultMessages()
                );
                $fields['extra']->css = isset($form_data['meta']['extra']['css']) ? $form_data['meta']['extra']['css'] : '';
                $fields['extra']->js = isset($form_data['meta']['extra']['js']) ? $form_data['meta']['extra']['js'] : '';

                //EMERSON: Fix bug on Form text messages value not imported in CRED 1.2.2
                //This will cause the hash to be different after import,e.g. in Module manager 1.1
                //Commented are old codes
                /* START */

                //if (isset($form_data['meta']['extra']['messages']['messages_item']))
                if (isset($form_data['meta']['extra']['messages'])) {
                    // make it array
                    /*
                      if (!isset($form_data['meta']['extra']['messages']['messages_item'][0]))
                      $form_data['meta']['extra']['messages']['messages_item']=array($form_data['meta']['extra']['messages']['messages_item']);
                     */
                    if (!isset($form_data['meta']['extra']['messages']))
                        $form_data['meta']['extra']['messages'] = array($form_data['meta']['extra']['messages']);

                    //foreach ($form_data['meta']['extra']['messages']['messages_item'] as $msg)
                    foreach ($form_data['meta']['extra']['messages'] as $msg) {
                        /*
                          foreach (array_keys($fields['extra']->messages) as $msgid)
                          {
                          if (isset($msg[$msgid]))
                          $fields['extra']->messages[$msgid]=$msg;
                          }
                         */
                        foreach (($fields['extra']->messages) as $msgid_key => $msgid_value) {

                            if (isset($form_data['meta']['extra']['messages'][$msgid_key]) && $form_data['meta']['extra']['messages'][$msgid_key] != $msgid_value) {

                                $fields['extra']->messages[$msgid_key] = $form_data['meta']['extra']['messages'][$msgid_key];
                            }
                        }
                    }
                }
                /* END */

                $fields['notification'] = (object) array(
                            'enable' => 0,
                            'notifications' => array()
                );
                if (isset($form_data['meta']['notification']['notifications']['notifications_item'])) {
                    // make it array
                    if (!isset($form_data['meta']['notification']['notifications']['notifications_item'][0]))
                        $form_data['meta']['notification']['notifications']['notifications_item'] = array($form_data['meta']['notification']['notifications']['notifications_item']);

                    foreach ($form_data['meta']['notification']['notifications']['notifications_item'] as $notif) {
                        $tmp = array();
                        $tmp['event'] = isset($notif['event']) ? $notif['event'] : array();
                        if (isset($tmp['event']['condition']['condition_item'])) {
                            if (!isset($tmp['event']['condition']['condition_item'][0]))
                                $tmp['event']['condition']['condition_item'] = array($tmp['event']['condition']['condition_item']);
                            $tmp['event']['condition'] = $tmp['event']['condition']['condition_item'];
                        }
                        $tmp['to'] = isset($notif['to']) ? $notif['to'] : array();
                        if (isset($tmp['to']['type']['type_item'])) {
                            if (!is_array($tmp['to']['type']['type_item']))
                                $tmp['to']['type']['type_item'] = array($tmp['to']['type']['type_item']);
                            $tmp['to']['type'] = $tmp['to']['type']['type_item'];
                        }
                        // add new fields From Addr, From Name
                        $tmp['from'] = isset($notif['from']) ? $notif['from'] : array();
                        $tmp['mail'] = isset($notif['mail']) ? $notif['mail'] : array();
                        $fields['notification']->notifications[] = $tmp;
                    }
                }
                $fields['notification']->enable = (isset($form_data['meta']['notification']['enable']) && $form_data['meta']['notification']['enable'] == '1') ? 1 : 0;

                //CRED post expiration import (new version 1.2.6)
                /* START */

                $fields['post_expiration'] = array(
                    'action' => array(),
                    'enable' => 0,
                    'expiration_time' => array()
                );

                if (isset($form_data['meta']['post_expiration'])) {

                    if (isset($form_data['meta']['post_expiration']['action'])) {
                        $fields['post_expiration']['action'] = $form_data['meta']['post_expiration']['action'];
                    }
                    if (isset($form_data['meta']['post_expiration']['enable'])) {
                        $fields['post_expiration']['enable'] = $form_data['meta']['post_expiration']['enable'];
                    }
                    if (isset($form_data['meta']['post_expiration']['expiration_time'])) {
                        $fields['post_expiration']['expiration_time'] = $form_data['meta']['post_expiration']['expiration_time'];
                    }
                }
                /* END */
            }

            // change format here and provide defaults also
            $fields = array_merge(array(
                'form_settings' => (object) array(
                    'form' => array(),
                    'post' => array()
                ),
                'notification' => (object) array(
                    'enable' => 0,
                    'notifications' => array()
                ),
                'extra' => (object) array(
                    'css' => '',
                    'js' => '',
                    'messages' => $fmodel->getDefaultMessages()
                )
                    ), $fmodel->changeFormat($fields)
            );
        }

        // de-normalize fields
        if (!empty($fields['form_settings']->form['action_page']) && !is_numeric($fields['form_settings']->form['action_page'])) {
            $_page_ = get_page_by_path($fields['form_settings']->form['action_page'], OBJECT, 'page');
            if ($_page_ && isset($_page_->ID))
                $fields['form_settings']->form['action_page'] = $_page_->ID;
            else
                $fields['form_settings']->form['action_page'] = '';
        }
        $_form_id = false;
        if (isset($options['overwrite_forms']) && $options['overwrite_forms']) {

            if (isset($options['force_skip_post_name']) ||
                    isset($options['force_overwrite_post_name']) ||
                    isset($options['force_duplicate_post_name'])) {
                $old_form = get_page_by_path($form->post_name, OBJECT, CRED_USER_FORMS_CUSTOM_POST_NAME);
            } else {
                $old_form = get_page_by_title($form->post_title, OBJECT, CRED_USER_FORMS_CUSTOM_POST_NAME);
            }
            if ($old_form) {
                $form->ID = $old_form->ID;
                $_form_id = $form->ID;
                if ($fmodel->updateForm($form, $fields)) {
                    $results['updated'] ++;
                } else {
                    $results['failed'] ++;
                    $results['errors'][] = sprintf(__('Item %s could not be saved', 'wp-cred'), $form->post_title);
                }
            } else {
                $_form_id = $fmodel->saveForm($form, $fields);
                if ($_form_id) {
                    $results['new'] ++;
                } else {
                    $results['failed'] ++;
                    $results['errors'][] = sprintf(__('Item %s could not be saved', 'wp-cred'), $form->post_title);
                }
            }
        } else {
            $_form_id = $fmodel->saveForm($form, $fields);
            $results['new'] ++;
        }

        if ($_form_id)
        // allow 3rd-party to import extra data per form and update results variable accordingly
            $results = apply_filters('cred_import_form', $results, $_form_id, $form_data);

        return $_form_id;
    }

    private static function importForms($data, $options) {
        $results = array(
            'settings' => 0,
            'custom_fields' => 0,
            'updated' => 0,
            'new' => 0,
            'failed' => 0,
            'errors' => array()
        );

        $newitems = array();

        if (isset($data['settings']) && isset($options['overwrite_settings']) && $options['overwrite_settings']) {
            $setmodel = CRED_Loader::get('MODEL/Settings');
            $oldsettings = $setmodel->getSettings();
            $newsettings = array();

            $fields = array(
                'dont_load_cred_css',
                'enable_post_expiration',
                'export_custom_fields',
                'export_settings',
                'recaptcha',
                'syntax_highlight',
                'use_bootstrap',
                'wizard',
            );
            foreach ($fields as $key) {
                $newsettings[$key] = null;
                if (array_key_exists($key, $data['settings']) && isset($data['settings'][$key])) {
                    $newsettings[$key] = $data['settings'][$key];
                }
            }
            $setmodel->updateSettings($newsettings);

            //Import CRED Post Expiration to options table
            global $cred_post_expiration;
            if ($newsettings['enable_post_expiration'] && isset($data['post_expiration_settings']) && !(empty($data['post_expiration_settings']))) {
                $oldsettings_expiration = $cred_post_expiration->getCredPESettings();
                $newsettings_expiration = $data['post_expiration_settings'];
                $newsettings_expiration['post_expiration_post_types'] = array();
                if (isset($data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'])) {
                    // make it array
                    if (!is_array($data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item']))
                        $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'] = array($data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item']);
                    $newsettings_expiration['post_expiration_post_types'] = $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'];
                }
                $newsettings_expiration = CRED_PostExpiration::array_merge_distinct($oldsettings_expiration, $newsettings_expiration);
                $cred_post_expiration->setCronSettings($newsettings_expiration);
            } else {
                $cred_post_expiration->deleteCredPESettings();
            }

            $results['settings'] = 1;
            unset($oldsettings);
        }

        if (isset($data['settings'])) {
            unset($data['settings']);
        }

        if (isset($data['post_expiration_settings'])) {
            unset($data['post_expiration_settings']);
        }

        if (isset($data['custom_fields']) && isset($options['overwrite_custom_fields']) && $options['overwrite_custom_fields']) {
            $custom_fields_model = CRED_Loader::get('MODEL/Fields');
            $old_custom_fields = $custom_fields_model->getCustomFields();
            foreach ($data['custom_fields'] as $post_type => $field) {
                foreach ($field as $field_slug => $field_data) {
                    $custom_fields_model->setCustomField($field_data);
                    $results['custom_fields'] ++;
                }
            }
            unset($old_custom_fields);
        }

        if (isset($data['custom_fields']))
            unset($data['custom_fields']);

        $fmodel = CRED_Loader::get('MODEL/Forms');

        if (isset($data['form']) && !empty($data['form']) && is_array($data['form'])) {
            if (!isset($options['items']))
                $items = false;
            else
                $items = $options['items'];

            if (!isset($data['form'][0]))
                $data['form'] = array($data['form']); // make it array









                
// create tmp upload dir, to handle imported media attached to forms
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['basedir'];
            $upload_directory = $upload_dir['baseurl'];
            $_tmp = $upload_path . DIRECTORY_SEPARATOR . '__cred__tmp__';
            $_tmpuri = $upload_directory . '/__cred__tmp__';

            if (!is_dir($_tmp))
                mkdir($_tmp);

            if (is_dir($_tmp)) {
                // include only if necessary
                include_once( ABSPATH . 'wp-admin/includes/file.php' );
                include_once( ABSPATH . 'wp-admin/includes/media.php' );
                include_once( ABSPATH . 'wp-admin/includes/image.php');
            }

            foreach ($data['form'] as $key => $form_data) {
                if (!isset($form_data['post_title']))
                    continue;
                // import only selected items
                if (false !== $items && !in_array($form_data['ID'], $items))
                    continue;

                $_form_id = self::importSingleForm($form_data, $fmodel, $options, $results);

                if ($_form_id) {
                    //Remove is_edited flag
                    delete_post_meta($_form_id, '_toolset_edit_last');
                    // add attached media (only images)
                    if (isset($form_data['media']['media_item']) && is_array($form_data['media']['media_item']) && !empty($form_data['media']['media_item']) && is_dir($_tmp)) {
                        $_att_results = self::importAttachedMedia($_form_id, $form_data['media']['media_item'], $_tmp, $_tmpuri);
                        //cred_log($_att_results);

                        if (!empty($_att_results['errors'])) {
                            $results['errors'] = array_merge($results['errors'], $_att_results['errors']);
                            $results['failed'] ++;
                        }
                        if (!empty($_att_results['data'])) {
                            // denormalize image hash placeholders
                            $form_data = self::denormalizeData($form_data, $_att_results['data']);
                            $fmodel->updateFormData(array('ID' => $_form_id, 'post_content' => $form_data['post_content']));
                        }
                    }

                    // for module manager
                    if (isset($options['return_ids']) && $options['return_ids'])
                        $newitems[$form_data['ID']] = $_form_id;
                }
            }

            if (is_dir($_tmp)) {
                // remove custom tmp dir
                @rmdir($_tmp);
            }
        }
        // for module manager
        if (isset($options['return_ids']) && $options['return_ids'])
            $results['items'] = $newitems;

        return $results;
    }

    private static function importUserForms($data, $options) {
        $results = array(
            'settings' => 0,
            'custom_fields' => 0,
            'updated' => 0,
            'new' => 0,
            'failed' => 0,
            'errors' => array()
        );

        $newitems = array();

        if (isset($data['settings']) && isset($options['overwrite_settings']) && $options['overwrite_settings']) {
            $setmodel = CRED_Loader::get('MODEL/Settings');
            $oldsettings = $setmodel->getSettings();
            $newsettings = array();

            $fields = array(
                'dont_load_cred_css',
                'enable_post_expiration',
                'export_custom_fields',
                'export_settings',
                'recaptcha',
                'syntax_highlight',
                'use_bootstrap',
                'wizard',
            );
            foreach ($fields as $key) {
                $newsettings[$key] = null;
                if (array_key_exists($key, $data['settings']) && isset($data['settings'][$key])) {
                    $newsettings[$key] = $data['settings'][$key];
                }
            }
            $setmodel->updateSettings($newsettings);

            //Import CRED Post Expiration to options table
            global $cred_post_expiration;
            if ($newsettings['enable_post_expiration'] && isset($data['post_expiration_settings']) && !(empty($data['post_expiration_settings']))) {
                $oldsettings_expiration = $cred_post_expiration->getCredPESettings();
                $newsettings_expiration = $data['post_expiration_settings'];
                $newsettings_expiration['post_expiration_post_types'] = array();
                if (isset($data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'])) {
                    // make it array
                    if (!is_array($data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item']))
                        $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'] = array($data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item']);
                    $newsettings_expiration['post_expiration_post_types'] = $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'];
                }
                $newsettings_expiration = CRED_PostExpiration::array_merge_distinct($oldsettings_expiration, $newsettings_expiration);
                $cred_post_expiration->setCronSettings($newsettings_expiration);
            } else {
                $cred_post_expiration->deleteCredPESettings();
            }

            $results['settings'] = 1;
            unset($oldsettings);
        }

        if (isset($data['settings'])) {
            unset($data['settings']);
        }

        if (isset($data['post_expiration_settings'])) {
            unset($data['post_expiration_settings']);
        }

        if (isset($data['custom_fields']) && isset($options['overwrite_custom_fields']) && $options['overwrite_custom_fields']) {
            $custom_fields_model = CRED_Loader::get('MODEL/UserFields');
            $old_custom_fields = $custom_fields_model->getCustomFields();
            foreach ($data['custom_fields'] as $post_type => $field) {
                foreach ($field as $field_slug => $field_data) {
                    //TODO: complete custom fields in user
                    //$custom_fields_model->setCustomField($field_data);
                    $results['custom_fields'] ++;
                }
            }
            unset($old_custom_fields);
        }

        if (isset($data['custom_fields']))
            unset($data['custom_fields']);

        $fmodel = CRED_Loader::get('MODEL/UserForms');

        if (isset($data['form']) && !empty($data['form']) && is_array($data['form'])) {
            if (!isset($options['items']))
                $items = false;
            else
                $items = $options['items'];

            if (!isset($data['form'][0]))
                $data['form'] = array($data['form']); // make it array




                
// create tmp upload dir, to handle imported media attached to forms
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['basedir'];
            $upload_directory = $upload_dir['baseurl'];
            $_tmp = $upload_path . DIRECTORY_SEPARATOR . '__cred__tmp__';
            $_tmpuri = $upload_directory . '/__cred__tmp__';

            if (!is_dir($_tmp))
                mkdir($_tmp);

            if (is_dir($_tmp)) {
                // include only if necessary
                include_once( ABSPATH . 'wp-admin/includes/file.php' );
                include_once( ABSPATH . 'wp-admin/includes/media.php' );
                include_once( ABSPATH . 'wp-admin/includes/image.php');
            }

            foreach ($data['form'] as $key => $form_data) {
                if (!isset($form_data['post_title']))
                    continue;
                // import only selected items
                if (false !== $items && !in_array($form_data['ID'], $items))
                    continue;

                $_form_id = self::importSingleUserForm($form_data, $fmodel, $options, $results);

                if ($_form_id) {
                    //Remove is_edited flag
                    delete_post_meta($_form_id, '_toolset_edit_last');
                    // add attached media (only images)
                    if (isset($form_data['media']['media_item']) && is_array($form_data['media']['media_item']) && !empty($form_data['media']['media_item']) && is_dir($_tmp)) {
                        $_att_results = self::importAttachedMedia($_form_id, $form_data['media']['media_item'], $_tmp, $_tmpuri);
                        //cred_log($_att_results);

                        if (!empty($_att_results['errors'])) {
                            $results['errors'] = array_merge($results['errors'], $_att_results['errors']);
                            $results['failed'] ++;
                        }
                        if (!empty($_att_results['data'])) {
                            // denormalize image hash placeholders
                            $form_data = self::denormalizeData($form_data, $_att_results['data']);
                            $fmodel->updateFormData(array('ID' => $_form_id, 'post_content' => $form_data['post_content']));
                        }
                    }

                    // for module manager
                    if (isset($options['return_ids']) && $options['return_ids'])
                        $newitems[$form_data['ID']] = $_form_id;
                }
            }

            if (is_dir($_tmp)) {
                // remove custom tmp dir
                @rmdir($_tmp);
            }
        }
        // for module manager
        if (isset($options['return_ids']) && $options['return_ids'])
            $results['items'] = $newitems;

        return $results;
    }

    private static function importAttachedMedia($id, $media, $_tmp, $_tmpuri) {
        $errors = array();
        $id = intval($id);
        $data = array();

        //###################################################################################################
        //Fix: https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/186012110/comments
        if (count($media) > 0 && !isset($media[0])) {
            $attach = $media;
            $hasError = false;
            if (
                    isset($attach['image_data']) &&
                    isset($attach['base_name']) &&
                    isset($attach['post_mime_type']) &&
                    in_array($attach['post_mime_type'], array('image/png', 'image/gif', 'image/jpg', 'image/jpeg'))
            ) {
                //  decode attachment data and create the file
                $imgdata = base64_decode($attach['image_data']);
                file_put_contents($_tmp . DIRECTORY_SEPARATOR . $attach['base_name'], $imgdata);
                // upload the file using WordPress API and add it to the post as attachment
                // preserving all fields but alt
                $tmpfile = download_url($_tmpuri . '/' . $attach['base_name']);

                if (is_wp_error($tmpfile)) {
                    try {
                        @unlink($tmpfile);
                        @unlink($_tmp . DIRECTORY_SEPARATOR . $attach['base_name']);
                    } catch (Exception $e) {
                        
                    }
                    $errors[] = $tmpfile->get_error_message($tmpfile->get_error_code());
                    $hasError = true;
                    array('errors' => $errors, 'data' => $data);
                }
                $file_array['name'] = $attach['base_name'];
                $file_array['tmp_name'] = $tmpfile;
                $att_data = array();
                if (isset($attach['post_title']))
                    $att_data['post_title'] = $attach['post_title'];
                if (isset($attach['post_content']))
                    $att_data['post_content'] = $attach['post_content'];
                if (isset($attach['post_excerpt']))
                    $att_data['post_excerpt'] = $attach['post_excerpt'];
                if (isset($attach['post_status']))
                    $att_data['post_status'] = $attach['post_status'];
                $att_id = media_handle_sideload($file_array, $id, null, $att_data);
                if (is_wp_error($att_id)) {
                    try {
                        @unlink($file_array['tmp_name']);
                        @unlink($_tmp . DIRECTORY_SEPARATOR . $attach['base_name']);
                    } catch (Exception $e) {
                        
                    }
                    $errors[] = $att_id->get_error_message($att_id->get_error_code());
                    $hasError = true;
                    array('errors' => $errors, 'data' => $data);
                }
                // update alt field
                if (isset($attach['alt']))
                    update_post_meta($att_id, '_wp_attachment_image_alt', $attach['alt']);

                // remove custom tmp file
                @unlink($_tmp . DIRECTORY_SEPARATOR . $attach['base_name']);

                // return data for replacements if needed
                if (isset($attach['image_hash'])) {
                    $data[$attach['image_hash']] = array(
                        'guid' => $attach['guid'],
                        'image_hash' => $attach['image_hash'],
                        'id' => $att_id
                    );
                }
            }
        } else {
            //###################################################################################################
            foreach ($media as $ii => $attach) {
                $hasError = false;
                if (
                        isset($attach['image_data']) &&
                        isset($attach['base_name']) &&
                        isset($attach['post_mime_type']) &&
                        in_array($attach['post_mime_type'], array('image/png', 'image/gif', 'image/jpg', 'image/jpeg'))
                ) {
                    //  decode attachment data and create the file
                    $imgdata = base64_decode($attach['image_data']);
                    file_put_contents($_tmp . DIRECTORY_SEPARATOR . $attach['base_name'], $imgdata);
                    // upload the file using WordPress API and add it to the post as attachment
                    // preserving all fields but alt
                    $tmpfile = download_url($_tmpuri . '/' . $attach['base_name']);

                    if (is_wp_error($tmpfile)) {
                        try {
                            @unlink($tmpfile);
                            @unlink($_tmp . DIRECTORY_SEPARATOR . $attach['base_name']);
                        } catch (Exception $e) {
                            
                        }
                        $errors[] = $tmpfile->get_error_message($tmpfile->get_error_code());
                        $hasError = true;
                        continue;
                    }
                    $file_array['name'] = $attach['base_name'];
                    $file_array['tmp_name'] = $tmpfile;
                    $att_data = array();
                    if (isset($attach['post_title']))
                        $att_data['post_title'] = $attach['post_title'];
                    if (isset($attach['post_content']))
                        $att_data['post_content'] = $attach['post_content'];
                    if (isset($attach['post_excerpt']))
                        $att_data['post_excerpt'] = $attach['post_excerpt'];
                    if (isset($attach['post_status']))
                        $att_data['post_status'] = $attach['post_status'];
                    $att_id = media_handle_sideload($file_array, $id, null, $att_data);
                    if (is_wp_error($att_id)) {
                        try {
                            @unlink($file_array['tmp_name']);
                            @unlink($_tmp . DIRECTORY_SEPARATOR . $attach['base_name']);
                        } catch (Exception $e) {
                            
                        }
                        $errors[] = $att_id->get_error_message($att_id->get_error_code());
                        $hasError = true;
                        continue;
                    }
                    // update alt field
                    if (isset($attach['alt']))
                        update_post_meta($att_id, '_wp_attachment_image_alt', $attach['alt']);

                    // remove custom tmp file
                    @unlink($_tmp . DIRECTORY_SEPARATOR . $attach['base_name']);

                    // return data for replacements if needed
                    if (isset($attach['image_hash'])) {
                        $data[$attach['image_hash']] = array(
                            'guid' => $attach['guid'],
                            'image_hash' => $attach['image_hash'],
                            'id' => $att_id
                        );
                    }
                }
            }
        }
        return array('errors' => $errors, 'data' => $data);
    }

    // public wrapper methods to use
    public static function computeHashForForm($id) {
        $forms = CRED_Loader::get('MODEL/Forms')->getFormsForExport(array($id));
        //cred_log($forms);
        if ($forms && isset($forms[0])) {
            // get first element
            $form = $forms[0];
            return self::doHash((array) $form);
        }
        return false;
    }

    public static function computeHashForUserForm($id) {
        $forms = CRED_Loader::get('MODEL/UserForms')->getFormsForExport(array($id));
        //cred_log($forms);
        if ($forms && isset($forms[0])) {
            // get first element
            $form = $forms[0];
            return self::doHash((array) $form);
        }
        return false;
    }

    public static function exportUsersToXML($forms, $ajax = false) {
        $mode = 'forms';
        $data = self::getSelectedUserFormsForExport($forms, array('media' => true), $mode);
        $setts = CRED_Loader::get('MODEL/Settings')->getSettings();

        // Export CRED post expiration settings
        global $cred_post_expiration;
        $cred_post_expiration_setts = $cred_post_expiration->getCredPESettings();

        if (isset($setts['export_settings']) && $setts['export_settings']) {
            $data[self::$root]['settings'] = $setts;
        }

        if (isset($cred_post_expiration_setts['post_expiration_cron']) && $cred_post_expiration_setts['post_expiration_cron']) {
            $data[self::$root]['post_expiration_settings'] = $cred_post_expiration_setts;
        }

        if (isset($setts['export_custom_fields']) && $setts['export_custom_fields']) {
            $custom_fields = CRED_Loader::get('MODEL/UserFields')->getCustomFields();
            $data[self::$root]['custom_fields'] = $custom_fields;
        }
        $xml = self::toXml($data, self::$root);
        self::output($xml, $ajax, $mode);
    }

    public static function exportToXML($forms, $ajax = false) {
        $mode = 'forms';
        $data = self::getSelectedFormsForExport($forms, array('media' => true), $mode);
        $setts = CRED_Loader::get('MODEL/Settings')->getSettings();

        // Export CRED post expiration settings
        global $cred_post_expiration;
        $cred_post_expiration_setts = $cred_post_expiration->getCredPESettings();

        if (isset($setts['export_settings']) && $setts['export_settings']) {
            $data[self::$root]['settings'] = $setts;
        }

        if (isset($cred_post_expiration_setts['post_expiration_cron']) && $cred_post_expiration_setts['post_expiration_cron']) {
            $data[self::$root]['post_expiration_settings'] = $cred_post_expiration_setts;
        }

        if (isset($setts['export_custom_fields']) && $setts['export_custom_fields']) {
            $custom_fields = CRED_Loader::get('MODEL/Fields')->getCustomFields();
            $data[self::$root]['custom_fields'] = $custom_fields;
        }
        $xml = self::toXml($data, self::$root);
        self::output($xml, $ajax, $mode);
    }

    public static function exportToXMLString($forms, $options = array(), &$extra = false) {
        $mode = 'forms';
        // add hashes as extra
        $data = self::getSelectedFormsForExport($forms, $options, $mode, $extra);
        $setts = CRED_Loader::get('MODEL/Settings')->getSettings();

        // Export CRED post expiration settings
        global $cred_post_expiration;
        $cred_post_expiration_setts = $cred_post_expiration->getCredPESettings();

        if (isset($setts['export_settings']) && $setts['export_settings']) {
            $data[self::$root]['settings'] = $setts;
        }

        if (isset($cred_post_expiration_setts['post_expiration_cron']) && $cred_post_expiration_setts['post_expiration_cron']) {
            $data[self::$root]['post_expiration_settings'] = $cred_post_expiration_setts;
        }

        if (isset($setts['export_custom_fields']) && $setts['export_custom_fields']) {
            $custom_fields = CRED_Loader::get('MODEL/Fields')->getCustomFields();
            $data[self::$root]['custom_fields'] = $custom_fields;
        }
        $xml = self::toXml($data, self::$root);
        return $xml;
    }

    public static function exportUsersToXMLString($forms, $options = array(), &$extra = false) {
        $mode = 'forms';
        // add hashes as extra
        $data = self::getSelectedUserFormsForExport($forms, $options, $mode, $extra);
        $setts = CRED_Loader::get('MODEL/Settings')->getSettings();

        // Export CRED post expiration settings
        //global $cred_post_expiration;
        //$cred_post_expiration_setts = $cred_post_expiration->getCredPESettings();
        //if (isset($setts['export_settings']) && $setts['export_settings']) {
        //    $data[self::$root]['settings'] = $setts;
        //}
//        if (isset($cred_post_expiration_setts['post_expiration_cron']) && $cred_post_expiration_setts['post_expiration_cron']) {
//            $data[self::$root]['post_expiration_settings'] = $cred_post_expiration_setts;
//        }

        if (isset($setts['export_custom_fields']) && $setts['export_custom_fields']) {
            $custom_fields = CRED_Loader::get('MODEL/UserFields')->getFields();
            $data[self::$root]['custom_fields'] = $custom_fields;
        }
        $xml = self::toXml($data, self::$root);
        return $xml;
    }

    public static function importFromXML($file, $options = array()) {
        $dataresult = self::readXML($file);
        if ($dataresult !== false && !is_wp_error($dataresult)) {
            if (isset($dataresult['form'])) {
                if (isset($dataresult['form']['post_type'])) {
                    if ($dataresult['form']['post_type'] != CRED_FORMS_CUSTOM_POST_NAME)
                        return new WP_Error('not_xml_file', __('The XML file does not contain valid Post Forms.', 'wp-cred'));
                } else {
                    foreach ($dataresult['form'] as $n => $f) {
                        if ($f['post_type'] != CRED_FORMS_CUSTOM_POST_NAME)
                            return new WP_Error('not_xml_file', __('The XML file does not contain valid Post Forms.', 'wp-cred'));
                    }
                }
            }
            $results = self::importForms($dataresult, $options);
            return $results;
        } else {
            return $dataresult;
        }
    }

    public static function importUserFromXML($file, $options = array()) {
        $dataresult = self::readXML($file);
        if ($dataresult !== false && !is_wp_error($dataresult)) {

            if (isset($dataresult['form'])) {
                if (isset($dataresult['form']['post_type'])) {
                    if ($dataresult['form']['post_type'] != CRED_USER_FORMS_CUSTOM_POST_NAME)
                        return new WP_Error('not_xml_file', __('The XML file does not contain valid User Forms.', 'wp-cred'));
                } else {
                    foreach ($dataresult['form'] as $n => $f) {
                        if ($f['post_type'] != CRED_USER_FORMS_CUSTOM_POST_NAME)
                            return new WP_Error('not_xml_file', __('The XML file does not contain valid User Forms.', 'wp-cred'));
                    }
                }
            }

            $results = self::importUserForms($dataresult, $options);
            return $results;
        } else {
            return $dataresult;
        }
    }

    public static function importFromXMLString($xmlstring, $options = array()) {
        if (!function_exists('simplexml_load_string')) {
            return new WP_Error('xml_missing', __('The Simple XML library is missing.', 'wp-cred'));
        }
        $xml = simplexml_load_string($xmlstring);

        $dataresult = self::toArray($xml);

        if (!isset($dataresult['form'][0]) && ( isset($options['force_skip_post_name']) || isset($options['force_overwrite_post_name']) || isset($options['force_duplicate_post_name']) )) {
            $dataresult['form'] = array($dataresult['form']);
        }

        //Installer/Importer skip, duplicate, owerwrite
        $new_list = array();
        if (isset($options['force_skip_post_name'])) {
            foreach ($dataresult['form'] as $key => $form_data) {
                if (in_array($form_data['post_name'], $options['force_skip_post_name'])) {
                    unset($dataresult['form'][$key]);
                } else {
                    $new_list[$key] = $form_data;
                }
            }
        }

        //Skip all forms, import only selected
        if (isset($options['force_overwrite_post_name'])) {
            foreach ($dataresult['form'] as $key => $form_data) {
                if (in_array($form_data['post_name'], $options['force_overwrite_post_name'])) {
                    $new_list[$key] = $form_data;
                }
            }
        }


        if (isset($options['force_duplicate_post_name'])) {
            foreach ($dataresult['form'] as $key => $form_data) {
                if (in_array($form_data['post_name'], $options['force_duplicate_post_name'])) {
                    $form_data['post_title'] .= ' ' . date('l jS \of F Y h:i:s A');
                    $form_data['post_name'] = sanitize_title_with_dashes($form_data['post_title']);
                    $new_list[$key] = $form_data;
                }
            }
        }
        //print_r($new_list);exit;
        if (count($new_list) > 0) {

            $dataresult['form'] = $new_list;
        }

        if (false !== $dataresult && !is_wp_error($dataresult)) {
            $results = self::importForms($dataresult, $options);
            return $results;
        } else {
            return $dataresult;
        }
    }

    public static function importUsersFromXMLString($xmlstring, $options = array()) {
        if (!function_exists('simplexml_load_string')) {
            return new WP_Error('xml_missing', __('The Simple XML library is missing.', 'wp-cred'));
        }
        $xml = simplexml_load_string($xmlstring);

        $dataresult = self::toArray($xml);

        if (!isset($dataresult['form'][0]) && ( isset($options['force_skip_post_name']) || isset($options['force_overwrite_post_name']) || isset($options['force_duplicate_post_name']) )) {
            $dataresult['form'] = array($dataresult['form']);
        }

        //Installer/Importer skip, duplicate, owerwrite
        $new_list = array();
        if (isset($options['force_skip_post_name'])) {
            foreach ($dataresult['form'] as $key => $form_data) {
                if (in_array($form_data['post_name'], $options['force_skip_post_name'])) {
                    unset($dataresult['form'][$key]);
                } else {
                    $new_list[$key] = $form_data;
                }
            }
        }

        //Skip all forms, import only selected
        if (isset($options['force_overwrite_post_name'])) {
            foreach ($dataresult['form'] as $key => $form_data) {
                if (in_array($form_data['post_name'], $options['force_overwrite_post_name'])) {
                    $new_list[$key] = $form_data;
                }
            }
        }


        if (isset($options['force_duplicate_post_name'])) {
            foreach ($dataresult['form'] as $key => $form_data) {
                if (in_array($form_data['post_name'], $options['force_duplicate_post_name'])) {
                    $form_data['post_title'] .= ' ' . date('l jS \of F Y h:i:s A');
                    $form_data['post_name'] = sanitize_title_with_dashes($form_data['post_title']);
                    $new_list[$key] = $form_data;
                }
            }
        }
        //print_r($new_list);exit;
        if (count($new_list) > 0) {

            $dataresult['form'] = $new_list;
        }

        if (false !== $dataresult && !is_wp_error($dataresult)) {
            $results = self::importUserForms($dataresult, $options);
            return $results;
        } else {
            return $dataresult;
        }
    }

}
