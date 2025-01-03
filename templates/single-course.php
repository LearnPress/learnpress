<?php
/**
 * Template for displaying content of single course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.1
 */

use LearnPress\Models\CourseModel;

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
	'post_status' => 'any',
);

// Fix preview course
if ( isset( $_REQUEST['preview'] ) &&
	( isset( $_REQUEST['p'] ) || isset( $_REQUEST['preview_id'] ) ) ) {
	unset( $args['name'] );
	$args['include'] = isset( $_REQUEST['p'] ) ? [ (int) $_REQUEST['p'] ] : [ (int) $_REQUEST['preview_id'] ];
}

$posts = get_posts( $args );
$post  = $posts[0] ?? 0;

if ( $post instanceof WP_Post ) {
	if ( $post->post_status !== 'publish'
		&& ( ! current_user_can( ADMIN_ROLE ) && get_current_user_id() != $post->post_author ) ) {
		$template_404 = get_query_template( '404' );
		if ( $template_404 ) {
			include $template_404;
		}
	} else {
		$courseModel = CourseModel::find( $post->ID, true );

		// hook from @since 4.2.7.5
		do_action( 'learn-press/single-course/layout', $courseModel );

		learn_press_get_template( 'content-single-course' );
	}
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
