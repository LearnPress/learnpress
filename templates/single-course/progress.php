<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();

$course = LP()->course;
if ( !$course ) {
	return;
}
$status = LP()->user->get( 'course-status', $course->id );
if ( !$status ) {
	return;
}

$force = isset( $force ) ? $force : false;

$num_of_decimal    = 0;
$result            = $course->evaluate_course_results( null, $force );
$current           = absint( $result );
$passing_condition = round( $course->passing_condition, $num_of_decimal );
$passed            = $current >= $passing_condition;
$heading           = apply_filters( 'learn_press_course_progress_heading', $status == 'finished' ? __( 'Your results', 'learnpress' ) : __( 'Learning progress', 'learnpress' ) );
$course_items      = sizeof( $course->get_curriculum_items() );
$completed_items   = $course->count_completed_items();
$course_results    = $course->evaluate_course_results();
?>
<div class="learn-press-course-results-progress">
	<div class="items-progress">
		<?php if ( $heading !== false ): ?>
			<h4 class="lp-course-progress-heading"><?php echo 'Items completed'; ?></h4>
		<?php endif; ?>
		<?php ///echo $course->get_course_result_html( null, $force ); ?>
		<div>
			<span class="number"><?php echo $completed_items; ?></span>
			<span class="percentage-sign">/<?php echo $course_items; ?></span>
		</div>

		<div class="lp-course-progress">
			<div class="lp-progress-bar">
				<div class="lp-progress-value" style="width: <?php echo $course_items ? absint( $completed_items / $course_items * 100 ) : '0'; ?>%;">
				</div>
			</div>
		</div>
	</div>
	<div class="course-progress">
		<h4 class="lp-course-progress-heading">
			<?php esc_html_e( 'Your course results currently is' ); ?>
			<?php
			if ( $course->is_evaluation( 'evaluate_final_quiz' ) ) {
				$tooltip = __( "Evaluated by results of final quiz", 'learnpress' );
			} elseif ( $course->is_evaluation( 'evaluate_quizzes' ) ) {
				$tooltip = __( "Evaluated by average results of quizzes", 'learnpress' );
			} else {
				$tooltip = __( "Evaluated by items completed", 'learnpress' );
			}
			?>
			<span class="learn-press-tooltip" data-content="<?php echo esc_html( $tooltip ); ?>"></span>
		</h4>
		<div>
			<span class="number"><?php echo $current ?></span><span class="percentage-sign">%</span>
		</div>
		<div class="lp-course-progress<?php echo $passed ? ' passed' : ''; ?>" data-value="<?php echo $current; ?>" data-passing-condition="<?php echo $passing_condition; ?>">
			<div class="lp-progress-bar">
				<div class="lp-progress-value" style="width: <?php echo $current; ?>%;">
				</div>
			</div>
			<div class="lp-passing-conditional" style="left: <?php echo $passing_condition; ?>%;">
			</div>
		</div>
	</div>
</div>