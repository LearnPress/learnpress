<?php
/**
 * Created by PhpStorm.
 * User: foobla
 * Date: 4/3/2015
 * Time: 10:51 AM
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Add meta box prerequisite courses
 * 
 * @param $meta_boxes
 *
 * @return mixed
 */
function learn_press_prerequisite_add_on( $meta_boxes ) {

    $prerequisite = array(
        'name'          =>  __( 'Prerequisite Courses', 'learn_press' ),
        'id'            =>  "_lpr_course_prerequisite",
        'type'          =>  'post',
        'post_type'     =>  'lpr_course',
        'field_type'    =>  'select_advanced',
        'multiple'      =>  true,
        'desc'          =>  'Course you have to finish before you can enroll to this course',
        'placeholder'   =>  __( 'Course Prerequisite', 'learn_press' ),
        'std'           =>  ''
    );

    array_unshift( $meta_boxes['fields'], $prerequisite );

    return $meta_boxes;
}
add_filter('learn_press_course_settings_meta_box_args', 'learn_press_prerequisite_add_on', 11);

/**
 * @param $course_id
 * @param $user_id
 *
 * @return bool
 */
function check_prerequisite_course( $course_id, $user_id ) {
    $prerequisite = get_post_meta( $course_id, '_lpr_course_prerequisite', false );
    $allow_take_course = true;
    $require_courses = array();
    if ( $prerequisite ) {
        $course_completed = get_user_meta( $user_id, '_lpr_course_completed', true );
        foreach ( $prerequisite as $prerequi ) {
            if ( $course_completed ) {
                if ( !array_key_exists( $prerequi, $course_completed ) ) {
                    array_push( $require_courses, $prerequi );
                    $allow_take_course = false;
                }
            } else {
                array_push( $require_courses, $prerequi );
                $allow_take_course = false;
            }
        }
    }
    if(  !$allow_take_course  ) {
		wp_dequeue_script('lpr-learnpress-js');
        wp_enqueue_script( 'lpr-learnpress-prerequisite-js', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/assets/prerequisite.js', array( 'jquery' ) );
        echo "<h3> You have to finish all the courses below before you can join this course: </h4>";
        echo "<ul>";
        foreach( $require_courses as $require_course ) {
            echo '<li><a href="'. get_the_permalink( $require_course ) .'" >'. get_the_title( $require_course ) .'</a></li>';
        }
        echo "</ul>";
    }
    return $allow_take_course;
}
//add_action( 'learn_press_before_take_course', 'check_prerequisite_course', 10 ,2  );

/**
 * Dequeue old javascript file
 */
function learn_press_prerequisite_dequeue_script() {
    wp_dequeue_script('lpr-learnpress-js');
}