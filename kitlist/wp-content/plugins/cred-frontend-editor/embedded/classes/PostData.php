<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PostData
 *
 * @author Franko
 */
class PostData {

    public function __construct() {
        
    }

    public function getPostData($post_id) {
        if ($post_id && is_numeric($post_id)) {
            $fm = CRED_Loader::get('MODEL/Forms');
            $data = $fm->getPost($post_id);
            //StaticClass::_pre($data);
            if ($data && isset($data[0])) {
                $mypost = $data[0];
                $myfields = isset($data[1]) ? $data[1] : array();
                $mytaxs = isset($data[2]) ? $data[2] : array();
                $myextra = isset($data[3]) ? $data[3] : array();
                if (isset($mypost->post_title))
                    $myfields['post_title'] = array($mypost->post_title);
                if (isset($mypost->post_content))
                    $myfields['post_content'] = array($mypost->post_content);
                if (isset($mypost->post_excerpt))
                    $myfields['post_excerpt'] = array($mypost->post_excerpt);
                if (isset($mypost->post_parent))
                    $myfields['post_parent'] = array($mypost->post_parent);

                return (object) array(
                            'post' => $mypost,
                            'fields' => $myfields,
                            'taxonomies' => $mytaxs,
                            'extra' => $myextra
                );
            }
            return $this->error(__('Post does not exist', 'wp-cred'));
        }
        return null;
    }

    public function getUserData($post_id) {
        if ($post_id && is_numeric($post_id)) {
            $fm = CRED_Loader::get('MODEL/UserFields');
            $fields = $fm->getFields(array());

            //$_data = get_user_by("id", $post_id);
            $_data = get_userdata($post_id);

            $_nickname = get_user_meta($post_id, 'nickname', true);            
            
            if (!isset($_nickname) || empty($_nickname)) 
                return $this->error(__('User does not exist', 'wp-cred'));
            
            $_data->data->nickname = $_nickname;

            if ($_data) {
                $data = (array) $_data->data;

                $myfields = array();
                foreach ($fields['form_fields'] as $key => $value) {
                    if ($key == 'user_pass')
                        continue;
                    if (isset($data[$key]))
                        $myfields[$key][] = $data[$key];
                }
                foreach ($fields['custom_fields'] as $key => $value) {
                    if (!isset($value['meta_key'])) {
                        $myfields[$key][] = "";
                        continue;
                    }                    
                    $user_meta = get_user_meta($post_id, $value['meta_key'], !(isset($value['data']['repetitive'])&&$value['data']['repetitive']==1));
                    $myfields[$value['meta_key']][] = $user_meta;
                }

                $data = (object) $data;
                $data->post_type = 'user';

                return (object) array(
                            'post' => $data,
                            'fields' => $myfields,
                            'taxonomies' => array(),
                            'extra' => array());
            }
            return $this->error(__('User does not exist', 'wp-cred'));
        }
        return null;
    }

    public function error($msg = '') {
        return new  WP_Error('error', $msg);
    }

}

?>
