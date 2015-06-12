<?php
/**
 * Created by PhpStorm.n
 * User: foobla
 * Date: 4/7/2015
 * Time: 4:38 PM
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * @param $course_id
 *
 * @return mixed|void
 */
function learn_press_get_course_review( $course_id ) {
    $course_review = get_post_meta( $course_id, '_lpr_course_review', true );
    if( $course_review ) {
        return apply_filters( 'learn_press_get_course_review', $course_review );
    }    
    return false;
}

/**
 * @param $course_id
 *
 * @return mixed|void
 */
function learn_press_get_course_rate( $course_id ) {
    $course_review = get_post_meta( $course_id, '_lpr_course_review', true );
    $total = 0;
    $rate = 0;
    if( $course_review ) {
        for( $i = 1; $i<6; $i++ ) {
            $total += $course_review['rate_value'][$i];
            $rate += $course_review['rate_value'][$i] * $i;
        }
        $rate = $rate / $total;
    }    
    return apply_filters( 'learn_press_get_course_rate', $rate );
}

function learn_press_get_course_rate_total( $course_id ) {
    $course_review = get_post_meta( $course_id, '_lpr_course_review', true );
    $total = 0;
    $rate = 0;
    if( $course_review ) {
        for( $i = 1; $i<6; $i++ ) {
            $total += $course_review['rate_value'][$i];        
        }
    }    
    return apply_filters( 'learn_press_get_course_rate', $total );
}

/**
 * @param $course_id
 * @param $user_id
 *
 * @return mixed|void
 */
function learn_press_get_user_review_title( $course_id, $user_id ) {
    $course_review = get_post_meta( $course_id, '_lpr_course_review', true );
    if( $course_review && array_key_exists($user_id, $course_review['review_title'])) {
        return apply_filters( 'learn_press_get_user_review', $course_review['review_title'][$user_id] );
    }    
    return false;
}

/**
 * @param $course_id
 * @param $user_id
 *
 * @return mixed|void
 */
function learn_press_get_user_rate( $course_id, $user_id ) {
    $course_review = get_post_meta( $course_id, '_lpr_course_review', true );
    if( $course_review && array_key_exists($user_id, $course_review['rate']) ) {
        return apply_filters( 'learn_press_get_user_review', $course_review['rate'][$user_id] );
    }    
    return false;
}

/**
 * @param $course_id
 * @param $course_rate
 * @param $review
 * @param $user_rate
 */
function learn_press_save_course_review( $course_id, $review_rate, $review_title, $review_content) {
    $user_id = get_current_user_id();
    $course_review = get_post_meta( $course_id, '_lpr_course_review', true );
    if( !isset($course_review) || !is_array($course_review) ) {
        $course_review = array();
        $course_review['rate_value'] = [0, 0, 0, 0, 0, 0, 0];

        $course_review['user'] = array();
        $course_review['rate'] = array();
        $course_review['review_title'] = array();
        $course_review['review_content'] = array();
    }
    $course_review['rate_value'][$review_rate]++;
    array_push($course_review['user'], $user_id);
    $course_review['rate'][$user_id] = $review_rate;    
    $course_review['review_title'][$user_id] = $review_title;
    $course_review['review_content'][$user_id] = $review_content;
    update_post_meta( $course_id, '_lpr_course_review', $course_review );
}

function learn_press_add_course_review() {    
    $user_id        = get_current_user_id();
    $review_title   = $_POST['review_title'];
    $review_content = $_POST['review_content'];
    $review_rate    = $_POST['review_rate'];
    $course_id      = $_POST['course_id'];
    $user_review    = learn_press_get_user_review_title( $course_id, $user_id );    
    if( ! $user_review ) {
        learn_press_save_course_review( $course_id, $review_rate, $review_title, $review_content );        
    }
    global $post;
    $post = get_post( $course_id );
    setup_postdata($post);
    learn_press_get_template('addons/course-review/course-rate.php');
    die();
}
add_action( 'wp_ajax_learn_press_add_course_review', 'learn_press_add_course_review' );

/**
 * Print rate for course
 */
function learn_press_print_rate( $course_id ) {
//    echo get_the_ID();
    wp_enqueue_style( 'lpr-print-rate-css', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/assets/course-review.css' );
    wp_enqueue_script( 'lpr-print-rate-js', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/assets/course-review.js' ,array('jquery'), '', true );
    learn_press_get_template('addons/course-review/course-rate.php');    
}
add_action('learn_press_after_the_title', 'learn_press_print_rate', 10 , 1);


function learn_press_print_review( $course_id ) {
    wp_enqueue_style( 'lpr-print-rate-css', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/assets/course-review.css' );
    wp_enqueue_script( 'lpr-print-rate-js', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/assets/course-review.js' ,array('jquery'), '', true );
    learn_press_get_template('addons/course-review/course-review.php');
}
add_action( 'learn_press_course_landing_content', 'learn_press_print_review', 80 );

function learn_press_add_review_button( $course_id ) {    
    wp_enqueue_script( 'lpr-print-rate-js', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/assets/course-review.js' ,array('jquery'), '', true );
    wp_enqueue_style( 'lpr-print-rate-css', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/assets/course-review.css' );
    learn_press_get_template('addons/course-review/add-review.php');
}
add_action('learn_press_course_learning_content', 'learn_press_add_review_button', 5);
