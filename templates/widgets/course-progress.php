<?php
/**
 * Template for displaying content of Course Progress widget.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/widgets/course-progress/default.php.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();

if ( ! $course ) {
	return;
}
?>

<div class="lp_widget_course_progress <?php echo esc_attr( $instance['css_class'] ); ?>">
	<div class="widget-body">
		<?php learn_press_get_template( 'single-course/sidebar/user-progress.php' ); ?>
	</div>
</div>
