<?php
/**
 * Template for displaying content of Course Info widget.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/widgets/course-info/default.php.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Templates
 * @version  4.1.3
 */

defined( 'ABSPATH' ) || exit();

if ( ! $course ) {
	return;
}
?>

<div class="lp_widget_course_info <?php echo esc_attr( $instance['css_class'] ); ?>">
	<h3><?php echo $course->get_title(); ?></h3>

	<ul class="lp-course-info-fields">
		<li class="lp-course-info lessons">
			<label><?php esc_html_e( 'Lessons', 'learnpress' ); ?></label>
			<?php learn_press_label_html( $course->count_items( LP_LESSON_CPT ) ); ?>
		</li>

		<li class="lp-course-info quizzes">
			<label><?php esc_html_e( 'Quizzes', 'learnpress' ); ?></label>
			<?php learn_press_label_html( $course->count_items( LP_QUIZ_CPT ) ); ?>
		</li>

		<li class="lp-course-info all-items">
			<label><?php esc_html_e( 'All', 'learnpress' ); ?></label>
			<?php learn_press_label_html( $course->count_items() ); ?>
		</li>
	</ul>
</div>

