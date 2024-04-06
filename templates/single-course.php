<?php
/**
 * Template for displaying content of single course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Header for page
 */
if ( ! wp_is_block_theme() ) {
	do_action( 'learn-press/template-header' );
}

/**
 * @since 3.0.0
 */
do_action( 'learn-press/before-main-content' );
do_action( 'learn-press/before-main-content-single-course' );

// WP 6.4 with Block theme can't detect single course, so code while ( have_posts() ) not run.
$args = array(
	'name'        => get_query_var( LP_COURSE_CPT ),
	'post_type'   => LP_COURSE_CPT,
	'numberposts' => 1,
);

// Fix preview course
if ( isset( $_REQUEST['preview'] ) && isset( $_REQUEST['p'] ) ) {
	unset( $args['name'] );
	$args['include']     = [ (int) $_REQUEST['p'] ];
	$args['post_status'] = 'any';
}

$posts = get_posts( $args );
$post  = $posts[0] ?? 0;

if ( $post instanceof WP_Post ) {
	learn_press_get_template( 'content-single-course' );
}
/*while ( have_posts() ) {
	the_post();
	learn_press_get_template( 'content-single-course' );
}*/

/**
 * @since 3.0.0
 */
do_action( 'learn-press/after-main-content-single-course' );
do_action( 'learn-press/after-main-content' );

/**
 * LP sidebar
 */
do_action( 'learn-press/sidebar' );

/**
 * Footer for page
 */
if ( ! wp_is_block_theme() ) {
	do_action( 'learn-press/template-footer' );
}
