<?php
/**
 * Shortcodes to display archive courses
 */

defined( 'ABSPATH' ) || exit();

/**
 * Shortcode to display all newest courses
 *
 * @param  mixed|array
 *
 * @return string
 */
function learn_press_newest_courses_shortcode( $atts ) {
	return '';
}

add_shortcode( 'learn_press_newest_courses', 'learn_press_newest_courses_shortcode' );

/**
 * Shortcode to display all free courses
 *
 * @param  mixed|array
 *
 * @return string
 */
function learn_press_free_courses_shortcode( $atts ) {
	return '';
}

add_shortcode( 'learn_press_free_courses', 'learn_press_free_courses_shortcode' );

/**
 * Shortcode to display paid courses
 *
 * @param mixed|array
 *
 * @return string
 */
function learn_press_paid_courses_shortcode( $atts ) {
	return '';
}

add_shortcode( 'learn_press_paid_courses', 'learn_press_paid_courses_shortcode' );
