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

define( 'COURSE_REVIEWS_PER_PAGE', 5 );
/**
 * @param int $course_id
 * @param int $paged
 * @param int $per_page
 * @return mixed|void
 */
function learn_press_get_course_review( $course_id, $paged = 1, $per_page = COURSE_REVIEWS_PER_PAGE ) {

    if( empty( $GLOBALS['course_reviews'] ) ) $GLOBALS['course_reviews'] = array();

    if( ! empty( $GLOBALS['course_reviews'][ $course_id ] ) ) return $GLOBALS['course_reviews'][ $course_id ];
    global $wpdb;
    $per_page = apply_filters( 'learn_press_course_reviews_per_page', $per_page );
    $start  = ( $paged - 1 ) * $per_page;
    $start  = max( $start, 0 );
    $per_page = max( $per_page, 1 );

    $results = array(
        'reviews'    => array(),
        'paged'      => $paged,
        'total'     => 0,
        'per_page'  => $per_page
    );

    $query = $wpdb->prepare("
        SELECT SQL_CALC_FOUND_ROWS u.*, cm1.meta_value as title, c.comment_content as content, cm2.meta_value as rate
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->comments} c ON p.ID = c.comment_post_ID
        INNER JOIN {$wpdb->users} u ON u.ID = c.user_id
        INNER JOIN {$wpdb->commentmeta} cm1 ON cm1.comment_id = c.comment_ID AND cm1.meta_key=%s
        INNER JOIN {$wpdb->commentmeta} cm2 ON cm2.comment_id = c.comment_ID AND cm2.meta_key=%s
        WHERE p.ID=%d AND c.comment_type=%s
        ORDER BY c.comment_date DESC
        LIMIT %d, %d
    ", '_lpr_review_title', '_lpr_rating', $course_id, 'review', $start, $per_page );
    $course_review = $wpdb->get_results( $query );
    if( $course_review ) {
        $results['reviews'] = $course_review;
        $results['total']   = $wpdb->get_var( "SELECT FOUND_ROWS();");
        if( $results['total'] <= $start + $per_page ){
            $results['finish'] = true;
        }
    }
    $GLOBALS['course_reviews'][ $course_id ] = apply_filters( 'learn_press_get_course_review', $results );
    return $GLOBALS['course_reviews'][ $course_id ];
}

/**
 * @param $course_id
 *
 * @return mixed|void
 */
function learn_press_get_course_rate( $course_id ) {
    global $wpdb;
    $query = $wpdb->prepare("
      SELECT avg( cm1.meta_value ) as rated
      FROM {$wpdb->posts} p
      INNER JOIN {$wpdb->comments} c ON p.ID = c.comment_post_ID
      INNER JOIN {$wpdb->commentmeta} cm1 ON cm1.comment_id = c.comment_ID AND cm1.meta_key='_lpr_rating'
      WHERE p.ID=%d
      AND c.comment_type=%s
    ", $course_id, 'review');

    $rated = $wpdb->get_var( $query );
    return apply_filters( 'learn_press_get_course_rate', $rated );
}

function learn_press_get_course_rate_total( $course_id ) {

    $course_review = learn_press_get_course_review( $course_id );

    return $course_review['total'];

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
function learn_press_get_user_rate( $course_id = null, $user_id = null ){
    $course_id = learn_press_get_course_id( $course_id );
    if( ! $user_id ) $user_id = get_current_user_id();
    if( ! $course_id || ! $user_id ) return false;

    $comment = false;
    $args = array(
        'user_id' => $user_id,
        'post_id' => $course_id,
        'include_unapproved' => true,
        'type'  => 'review'
    );
    $comments = get_comments( $args );

    if( $comments ){
        $comment =  $comments[0];
        $comment->comment_title = get_comment_meta( $comment->comment_ID, '_lpr_review_title', true );
        $comment->rating        = get_comment_meta( $comment->comment_ID, '_lpr_rating', true );
    }
    return $comment;
}

/**
 * @param int $course_id
 * @param int $review_rate
 * @param string $review_title
 * @param string $review_content
 */
function learn_press_save_course_review( $course_id, $review_rate, $review_title, $review_content) {
    $user = wp_get_current_user();
    $course_review = get_post_meta( $course_id, '_lpr_course_review', true );
    if( !isset($course_review) || !is_array($course_review) ) {
        $course_review = array();
        $course_review['rate_value'] = [0, 0, 0, 0, 0, 0, 0];

        $course_review['user'] = array();
        $course_review['rate'] = array();
        $course_review['review_title'] = array();
        $course_review['review_content'] = array();
    }
    $comment_id = wp_new_comment(
        array(
            'comment_post_ID' => $course_id,
            'comment_author' => 'LearnPress',
            'comment_author_email' => $user->user_email,
            'comment_author_url' => '',
            'comment_content' => $review_content,
            'comment_type' => '',
            'comment_parent' => 0,
            'user_id'   => $user->ID,
            'comment_approved' => 0,
            'comment_type'  => 'review'
        )
    );
    if( $comment_id ){
        add_comment_meta( $comment_id, '_lpr_rating', $review_rate );
        add_comment_meta( $comment_id, '_lpr_review_title', $review_title );
    }

    return $comment_id;
    $course_review['rate_value'][$review_rate]++;
    array_push($course_review['user'], $user_id);
    $course_review['rate'][$user_id] = $review_rate;    
    $course_review['review_title'][$user_id] = $review_title;
    $course_review['review_content'][$user_id] = $review_content;
    update_post_meta( $course_id, '_lpr_course_review', $course_review );
}

function learn_press_add_course_review() {    
    $user_id        = get_current_user_id();
    $course_id      = $_POST['course_id'];
    $user_review    = learn_press_get_user_rate( $course_id, $user_id );//learn_press_get_user_review_title( $course_id, $user_id );
    if( ! $user_review ) {
        $review_title   = $_POST['review_title'];
        $review_content = $_POST['review_content'];
        $review_rate    = $_POST['review_rate'];
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
function learn_press_print_rate( ) {
    learn_press_get_template('addons/course-review/course-rate.php');
}
add_action('learn_press_after_the_title', 'learn_press_print_rate', 10 , 1);


function learn_press_print_review() {
    learn_press_get_template('addons/course-review/course-review.php');
}
add_action( 'learn_press_course_landing_content', 'learn_press_print_review', 80 );

function learn_press_add_review_button( ) {
    if( ! learn_press_get_user_rate() ) {
        learn_press_get_template('addons/course-review/add-review.php');
    }
}
add_action('learn_press_course_learning_content', 'learn_press_add_review_button', 5);

function learn_press_review_assets(){
    wp_enqueue_script('lpr-print-rate-js', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/course-review.js', array('jquery'), '', true);
    wp_enqueue_style('lpr-print-rate-css', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/course-review.css');
}
add_action( 'wp_enqueue_scripts', 'learn_press_review_assets' );

function learn_press_course_review_init(){
    $paged = ! empty( $_REQUEST['paged'] ) ? intval( $_REQUEST['paged'] ) : 1;
    learn_press_get_course_review( get_the_ID(), $paged );
}
add_action( 'wp', 'learn_press_course_review_init' );
