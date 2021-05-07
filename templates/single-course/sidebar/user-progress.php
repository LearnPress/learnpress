<?php
/**
 * Template for displaying progress of single course.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$course_data       = $user->get_course_data( $course->get_id() );
$course_results    = $course_data->calculate_course_results();
$passing_condition = $course->get_passing_condition();
$quiz_false        = 0;

if ( ! empty( $course_results['items'] ) ) {
	$quiz_false = $course_results['items']['quiz']['completed'] - $course_results['items']['quiz']['passed'];
}
?>

<div class="course-results-progress">
	<div class="items-progress">
		<h4 class="items-progress__heading">
			<?php esc_html_e( 'Lessons completed:', 'learnpress' ); ?>
		</h4>
		<span class="number"><?php printf( '%1$d/%2$d', $course_results['items']['lesson']['completed'], $course_results['items']['lesson']['total'] ); ?></span>
	</div>

	<div class="items-progress">
		<h4 class="items-progress__heading">
			<?php esc_html_e( 'Quizzes finished:', 'learnpress' ); ?>
		</h4>
		<span class="number" title="<?php esc_attr( sprintf( __( 'Failed %1$d, Passed %2$d', 'learnpress' ), $quiz_false, $course_results['items']['quiz']['passed'] ) ); ?>"><?php printf( __( '%1$d/%2$d', 'learnpress' ), $course_results['items']['quiz']['completed'], $course_results['items']['quiz']['total'] ); ?></span>
	</div>

	<?php do_action( 'learn-press/user-item-progress' ); ?>

	<div class="course-progress">
		<h4 class="items-progress__heading">
			<?php esc_html_e( 'Course progress:', 'learnpress' ); ?>
		</h4>

		<div class="lp-course-status">
			<span class="number"><?php echo round( $course_results['result'], 2 ); ?><span class="percentage-sign">%</span></span>
		</div>

		<div class="learn-press-progress lp-course-progress <?php echo $course_data->is_passed() ? ' passed' : ''; ?>" data-value="<?php echo $course_results['result']; ?>" data-passing-condition="<?php echo $passing_condition; ?>" title="<?php echo esc_attr( learn_press_translate_course_result_required( $course ) ); ?>">
			<div class="progress-bg lp-progress-bar">
				<div class="progress-active lp-progress-value" style="left: <?php echo $course_results['result']; ?>%;">
				</div>
			</div>
			<div class="lp-passing-conditional" data-content="<?php printf( esc_html__( 'Passing condition: %s%%', 'learnpress' ), $passing_condition ); ?>" style="left: <?php echo $passing_condition; ?>%;">
			</div>
		</div>
	</div>

</div>
