<?php
/**
 * Template for displaying content of Course Progress widget.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/widgets/course-progress/default.php.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Templates
 * @version  4.1.4
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $course ) || ! isset( $user ) || ! isset( $instance ) || ! isset( $course_results ) || ! isset( $course_data ) ) {
	return;
}
?>

<div class="lp_widget_course_progress <?php echo esc_attr( $instance['css_class'] ); ?>">
	<h3><?php echo $course->get_title(); ?></h3>

	<?php
	learn_press_get_template(
		'single-course/sidebar/user-progress.php',
		compact( 'user', 'course', 'course_data', 'course_results' )
	);
	?>
</div>
