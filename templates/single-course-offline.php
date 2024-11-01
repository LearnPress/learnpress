<?php
/**
 * Template for displaying content of single course offline.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.2.7
 * @version 1.0.1
 */

use LearnPress\Models\CourseModel;

defined( 'ABSPATH' ) || exit;

/**
 * Header for page
 */
if ( ! wp_is_block_theme() ) {
	do_action( 'learn-press/template-header' );
}

$course_id = get_the_ID();
if ( $course_id ) {
	$course = CourseModel::find( $course_id, true );
	do_action( 'learn-press/single-course/offline/layout', $course );
}

/**
 * Footer for page
 */
if ( ! wp_is_block_theme() ) {
	do_action( 'learn-press/template-footer' );
}
