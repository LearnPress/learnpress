<?php
/**
 * Template for displaying lesson item content in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-item-lp_lesson.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
$course = LP_Global::course();
$item   = LP_Global::course_item();
?>

<div class="content-item-summary">

	<?php
	do_action( 'learn-press/before-content-item-summary/' . $item->get_item_type(), $course->get_id(), $item->get_id() );

	do_action( 'learn-press/content-item-summary/' . $item->get_item_type(), $course->get_id(), $item->get_id() );

	do_action( 'learn-press/after-content-item-summary/' . $item->get_item_type(), $course->get_id(), $item->get_id() );
    ?>

</div>
