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
get_header( 'course' );

/**
 * @since 3.0.0
 */
do_action( 'learn-press/before-main-content' );

while ( have_posts() ) {
	the_post();
	learn_press_get_template( 'content-single-course' );
}

/**
 * @since 3.0.0
 */
do_action( 'learn-press/after-main-content' );

/**
 * LP Hook
 */
do_action( 'learn-press/sidebar' );

/**
 * Footer for page
 */
get_footer( 'course' );
