<?php
/**
 * Template for displaying content of Course Progress widget.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/widgets/course-progress/default.php.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! $course = LP_Global::course() ) {
	return;
}
?>

<div<?php $widget->get_class( $widget->instance ); ?>>

    <div class="widget-body">

		<?php learn_press_get_template( 'single-course/progress.php' ); ?>

		<?php learn_press_course_remaining_time(); ?>

    </div>

</div>