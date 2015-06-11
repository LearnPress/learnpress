<?php
/**
 * Created by PhpStorm.
 * User: foobla
 * Date: 4/7/2015
 * Time: 4:38 PM
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Register course-review course addon
 */
function learn_press_register_course_review() {

    $prerequisite = array(
        'course-review-add-on' => array(
            'name'              => __( 'Course Review', 'learn_press' ),
            'description'       => __( 'Adding review for course ', 'learn_press' ),
            'author'            => 'foobla',
            'author_url'        => 'http://thimpress.com',
            'file'              => LPR_PLUGIN_PATH . '/inc/core-addons/course-review/course-review.php',
            'category'          => 'courses',
            'tag'               => 'core',
            'settings-callback' => '',
        )
    );
    $prerequisite = apply_filters( 'learn_press_course_review', $prerequisite );
    learn_press_addon_register( 'course-review-add-on', $prerequisite['course-review-add-on'] );
}
add_action( 'learn_press_register_add_ons', 'learn_press_register_course_review' );