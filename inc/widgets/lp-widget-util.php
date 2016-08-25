<?php
/**
 * define common functions for widgets
 */

defined( 'ABSPATH' ) || exit();

if(!defined('LP_WIDGETS_DIR')){
    define('LP_WIDGETS_DIR', LP_PLUGIN_PATH."/inc/widgets");
}

if(!function_exists('lp_php_file_filter')){
    function lp_php_file_filter($file_name){
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        return ($file_ext == "php");
    }
}

if(!function_exists('lp_trim_file_extension')){
    function lp_trim_file_extension(&$file_name){
        $file_name = pathinfo($file_name, PATHINFO_FILENAME);
        return 1;
    }
}

if(!function_exists('lp_is_paid_course')){
    function lp_is_paid_course($course_id){
        $meta_value =  get_post_meta($course_id, '_lp_payment', true);
        $bool_value = filter_var($meta_value, FILTER_VALIDATE_BOOLEAN);
        return $bool_value;
    }
}
