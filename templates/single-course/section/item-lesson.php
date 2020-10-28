<?php
/**
 * Template for displaying lesson item section in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/section/item-lesson.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $item ) ) {
	return;
}
?>

<span class="item-name"><?php echo esc_html( $item->get_title( 'display' ) ); ?></span>
