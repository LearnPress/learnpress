<?php
/**
 * Template for displaying lesson item content in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-item-lp_lesson.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

defined( 'ABSPATH' ) || exit();

$item = LP_Global::course_item();
?>

<div <?php learn_press_content_item_summary_class(); ?>>

	<?php
	do_action( 'learn-press/before-content-item-summary/' . $item->get_item_type() );

	do_action( 'learn-press/content-item-summary/' . $item->get_item_type() );

	do_action( 'learn-press/after-content-item-summary/' . $item->get_item_type() );
	?>

</div>
