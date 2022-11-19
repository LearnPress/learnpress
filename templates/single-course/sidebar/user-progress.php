<?php
/**
 * Template for displaying progress of single course.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.2
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $user ) || ! isset( $course ) || ! isset( $course_data ) || ! isset( $course_results ) ) {
	return;
}

$passing_condition = $course->get_passing_condition();
?>

<div class="course-results-progress">
	<?php
	if ( ! empty( $course_results['items'] ) && $course_results['items']['lesson']['total'] ) :
		?>
		<div class="items-progress">
			<h4 class="items-progress__heading">
				<?php esc_html_e( 'Lessons completed:', 'learnpress' ); ?>
			</h4>
			<span class="number"><?php esc_attr( printf( '%1$d/%2$d', $course_results['items']['lesson']['completed'], $course_results['items']['lesson']['total'] ) ); ?></span>
		</div>
	<?php endif; ?>

	<?php
	if ( ! empty( $course_results['items'] ) && $course_results['items']['quiz']['total'] ) :
		$quiz_false = $course_results['items']['quiz']['completed'] - $course_results['items']['quiz']['passed'];
		?>
		<div class="items-progress">
			<h4 class="items-progress__heading">
				<?php esc_html_e( 'Quizzes finished:', 'learnpress' ); ?>
			</h4>
			<span class="number" title="<?php echo esc_attr( sprintf( __( 'Failed %1$d, Passed %2$d', 'learnpress' ), $quiz_false, $course_results['items']['quiz']['passed'] ) ); ?>"><?php printf( __( '%1$d/%2$d', 'learnpress' ), $course_results['items']['quiz']['completed'], $course_results['items']['quiz']['total'] ); ?></span>
		</div>
	<?php endif; ?>

	<?php do_action( 'learn-press/user-item-progress', $course_results, $course_data, $user, $course ); ?>

	<div class="course-progress">
		<h4 class="items-progress__heading">
			<?php esc_html_e( 'Course progress:', 'learnpress' ); ?>
		</h4>

		<div class="lp-course-status">
			<span class="number"><?php echo esc_html( $course_results['result'] ); ?><span class="percentage-sign">%</span></span>
		</div>

		<div class="learn-press-progress lp-course-progress <?php echo esc_attr( $course_data->is_passed() ? ' passed' : '' ); ?>"
			data-value="<?php echo esc_attr( $course_results['result'] ); ?>"
			data-passing-condition="<?php echo esc_attr( $passing_condition ); ?>"
			title="<?php echo esc_attr( learn_press_translate_course_result_required( $course ) ); ?>">
			<div class="progress-bg lp-progress-bar">
				<div class="progress-active lp-progress-value" style="left: <?php echo esc_attr( $course_results['result'] ); ?>%;">
				</div>
			</div>
			<div class="lp-passing-conditional"
				data-content="<?php esc_attr( printf( esc_html__( 'Passing condition: %s%%', 'learnpress' ), $passing_condition ) ); ?>"
				style="left: <?php echo esc_attr( $passing_condition ); ?>%;">
			</div>
		</div>
	</div>

</div>
