<?php
/**
 * Template for displaying content of Course Progress widget.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/widgets/course-progress/default.php.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Templates
 * @version  4.1.3
 */

defined( 'ABSPATH' ) || exit();

if ( ! $course || ! $user ) {
	return;
}
?>

<div class="lp_widget_course_progress <?php echo esc_attr( $instance['css_class'] ); ?>">
	<h3><?php echo $course->get_title(); ?></h3>

	<?php
	learn_press_get_template(
		'single-course/sidebar/user-progress.php',
		array(
			'user'   => $user,
			'course' => $course,
		)
	);
	?>
</div>
