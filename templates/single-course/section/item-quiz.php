<?php
/**
 * Template for displaying quiz item section in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/section/item-quiz.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $item ) ) {
	return;
} ?>

<span class="item-name"><?php echo $item->get_title( 'display' ); ?></span>