<?php
/**
 * Template for displaying content item in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/section/content-item.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $item ) || ! isset( $section ) ) {
	return;
}

$args = array( 'item' => $item, 'section' => $section );

/**
 * @since 3.0.0
 */
do_action( 'learn-press/before-section-loop-item', $item );

learn_press_get_template( "single-course/section/" . $item->get_template(), $args );

/**
 * @since 3.0.0
 *
 * @see   learn_press_section_item_meta()
 */
do_action( 'learn-press/after-section-loop-item', $item, $section );
?>