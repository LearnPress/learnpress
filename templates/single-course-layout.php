<?php
/**
 * Template for displaying content of single course offline.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.2.7.6
 * @version 1.0.1
 */

use LearnPress\Models\CourseModel;

defined( 'ABSPATH' ) || exit;

/**
 * Header for page
 */
if ( ! wp_is_block_theme() ) {
	get_header( 'course' );
}

$course_id = get_the_ID();
if ( $course_id ) {
	/**
	 * If course has set password
	 */
	if ( post_password_required() ) {
		echo '<div class="lp-content-area">';
		echo get_the_password_form();
		echo '</div>';
		return;
	}

	$courseModel = CourseModel::find( $course_id, true );
	if ( ! $courseModel ) {
		return;
	}

	do_action( 'learn-press/single-course/layout', $courseModel );
}

/**
 * Footer for page
 */
if ( ! wp_is_block_theme() ) {
	get_footer( 'course' );
}
