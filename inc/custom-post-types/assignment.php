<?php
/**
 * Project:     learnpress.
 * Author:      GiapNV
 * Date:        1/16/15
 *
 * Copyright 2007-2014 thimpress.com. All rights reserved.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'LPR_ASSIGNMENT_SLUG', 'assignments' );
define( 'LPR_ASSIGNMENT_TYPE_SLUG', 'assignments-type' );

if( ! class_exists( 'LPR_Assignment_Post_Type' ) ){
    class LPR_Assignment_Post_Type{
        function register_post_type(){
            register_post_type( LPR_ASSIGNMENT_CPT,
                array(
                    'labels'             => array(
                        'name'          => __( 'Assignments', 'learn_press' ),
                        'menu_name'     => __( 'Assignments', 'learn_press' ),
                        'singular_name' => __( 'Assignment', 'learn_press' ),
                        'add_new_item'  => __( 'Add New Assignment', 'learn_press' ),
                        'edit_item'     => __( 'Edit Assignment', 'learn_press' ),
                        'all_items'     => __( 'All Assignments', 'learn_press' ),
                    ),
                    'public'             => true,
                    'taxonomies'         => array( 'assignment-type' ),
                    'publicly_queryable' => true,
                    'show_ui'            => true,
                    'has_archive'        => true,
                    'capability_type'    => LPR_LESSON_CPT,
                    'map_meta_cap'       => true,
                    'show_in_menu'       => 'learn_press',
                    'show_in_admin_bar'  => true,
                    'show_in_nav_menus'  => true,
                    'supports'           => array(
                        'title',
                        'editor',
                        'author',
                        'revisions',
                    ),
                    'hierarchical'       => true,
                    'rewrite'            => array( 'slug' => LPR_ASSIGNMENT_SLUG, 'hierarchical' => true, 'with_front' => true )
                )
            );

            register_taxonomy( 'assignment-type', array( LPR_ASSIGNMENT_CPT ),
                array(
                    'labels'            => array(
                        'name'          => __( 'Assignment type', 'learn_press' ),
                        'menu_name'     => __( 'Assignment type', 'learn_press' ),
                        'singular_name' => __( 'Assignment type', 'learn_press' ),
                        'add_new_item'  => __( 'Add New Assignment type', 'learn_press' ),
                        'all_items'     => __( 'All Assignment types', 'learn_press' )
                    ),
                    'public'            => true,
                    'hierarchical'      => true,
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'show_in_admin_bar' => true,
                    'show_in_nav_menus' => true,
                    'rewrite'           => array(
                        'slug'         => LPR_ASSIGNMENT_TYPE_SLUG,
                        'hierarchical' => true,
                        'with_front'   => false
                    ),
                )
            );
            add_post_type_support( LPR_ASSIGNMENT_CPT, 'comments' );
        }
        function register_meta_box(){
            $max_upload   = (int) ( ini_get( 'upload_max_filesize' ) );
            $max_post     = (int) ( ini_get( 'post_max_size' ) );
            $memory_limit = (int) ( ini_get( 'memory_limit' ) );
            $upload_size  = min( $max_upload, $max_post, $memory_limit );

            $prefix       = '_lpr_';
            $meta_box = array(
                'title'      => __( 'LearnPress Assignment Settings', 'learn_press' ),
                'post_types' => LPR_ASSIGNMENT_CPT,
                'context'    => 'normal',
                'priority'   => 'high',
                'autosave'   => true,
                'fields'     => array(
                    array(
                        'name' => __( 'Assignment subtitle', 'learn_press' ),
                        'id'   => "limit_course",
                        'desc' => __( 'Assignment subtitle', 'learn_press' ),
                        'type' => 'text',
                    ),
                    array(
                        'name' => __( 'Assignment maximum mark', 'learn_press' ),
                        'id'   => "{$prefix}assignment_max_mark",
                        'desc' => __( 'Set maximum marks for the assignment', 'learn_press' ),
                        'type' => 'number',
                        'min'  => 0,
                        'step' => 1,
                    ),
                    array(
                        'name' => __( 'Assignment duration time', 'learn_press' ),
                        'id'   => "{$prefix}assignment_duration",
                        'desc' => __( 'Set duration time for the assignment (in days)', 'learn_press' ),
                        'type' => 'number',
                    ),
                    array(
                        'name' => __( 'Include in Course Evaluation', 'learn_press' ),
                        'id'   => "{$prefix}assignment_evaluation",
                        'type' => 'checkbox',
                    ),
                    /* TODO: implement lpr-course later!! */
                    array(
                        'name'        => __( 'Include in Course', 'learn_press' ),
                        'id'          => "{$prefix}assignment_course",
                        'desc'        => __( 'Assignments marks will be shown/used in course evaluation', 'learn_press' ),
                        'type'        => 'post',
                        'post_type'   => LPR_COURSE_CPT,
                        'field_type'  => 'select_advanced',
                        'placeholder' => __( 'Select a Course', 'learn_press' ),
                        'query_args'  => array(
                            'post_status'    => 'publish',
                            'posts_per_page' => - 1,
                        )
                    ),
                    array(
                        'name'    => __( 'Submission Type', 'learn_press' ),
                        'id'      => "{$prefix}assignment_submission_type",
                        'desc'    => __( 'Select type of assignment submissions', 'learn_press' ),
                        'type'    => 'select',
                        'options' => array(
                            'upload'   => __( 'File Upload', 'learn_press' ),
                            'textarea' => __( 'Text Area', 'learn_press' ),
                        ),
                    ),
                    array(
                        'name'     => __( 'Attachment Type', 'learn_press' ),
                        'id'       => "{$prefix}assignment_attachment_type",
                        'type'     => 'select_advanced',
                        'multiple' => true,
                        'desc'     => __( 'Select allowed attachment type(s)', 'learn_press' ),
                        'options'  => array(
                            'JPG'  => __( 'JPG', 'learn_press' ),
                            'GIF'  => __( 'GIF', 'learn_press' ),
                            'PNG'  => __( 'PNG', 'learn_press' ),
                            'PDF'  => __( 'PDF', 'learn_press' ),
                            'DOC'  => __( 'DOC', 'learn_press' ),
                            'DOCX' => __( 'DOCX', 'learn_press' ),
                            'PPT'  => __( 'PPT', 'learn_press' ),
                            'PPTX' => __( 'PPTX', 'learn_press' ),
                            'PPS'  => __( 'PPS', 'learn_press' ),
                            'PPSX' => __( 'PPSX', 'learn_press' ),
                            'ODT'  => __( 'ODT', 'learn_press' ),
                            'XLS'  => __( 'XLS', 'learn_press' ),
                            'XLSX' => __( 'XLSX', 'learn_press' ),
                            'MP3'  => __( 'MP3', 'learn_press' ),
                            'M4A'  => __( 'M4A', 'learn_press' ),
                            'OGG'  => __( 'OGG', 'learn_press' ),
                            'WAV'  => __( 'WAV', 'learn_press' ),
                            'WMA'  => __( 'WMA', 'learn_press' ),
                            'MP4'  => __( 'MP4', 'learn_press' ),
                            'M4V'  => __( 'M4V', 'learn_press' ),
                            'MOV'  => __( 'MOV', 'learn_press' ),
                            'WMV'  => __( 'WMV', 'learn_press' ),
                            'AVI'  => __( 'AVI', 'learn_press' ),
                            'MPG'  => __( 'MPG', 'learn_press' ),
                            'OGV'  => __( 'OGV', 'learn_press' ),
                            '3GP'  => __( '3GP', 'learn_press' ),
                            '3G2'  => __( '3G2', 'learn_press' ),
                            'FLV'  => __( 'FLV', 'learn_press' ),
                            'WEBM' => __( 'WEBM', 'learn_press' ),
                            'APK'  => __( 'APK', 'learn_press' ),
                            'RAR'  => __( 'RAR', 'learn_press' ),
                            'ZIP'  => __( 'ZIP', 'learn_press' ),
                        ),
                    ),
                    array(
                        'name'       => __( 'Attachment Size (in MB)', 'learn_press' ),
                        'desc'       => __( 'Set Maximum Attachment size for upload ( set less than ', 'vibe' ) . $upload_size . ' MB)',
                        'id'         => "{$prefix}attachment_size",
                        'type'       => 'slider',
                        'suffix'     => __( ' MB', 'learn_press' ),
                        // jQuery UI slider options. See here http://api.jqueryui.com/slider/
                        'js_options' => array(
                            'min'  => 1,
                            'max'  => $upload_size,
                            'step' => 1,
                        ),
                    )
                )
            );
        }
    }
}

