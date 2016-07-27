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
$current           = round( $result * 100, $num_of_decimal );
$passing_condition = round( $course->passing_condition, $num_of_decimal );
$passed            = $current >= $passing_condition;
$heading           = apply_filters( 'learn_press_course_progress_heading', $status == 'finished' ? __( 'Your result', 'learnpress' ) : __( 'Learning progress', 'learnpress' ) );
?>
<div class="learn-press-course-results-progress">
	<?php if ( $heading !== false ): ?>
		<h4 class="lp-course-progress-heading"><?php echo $heading; ?></h4>
	<?php endif; ?>
	<?php echo $course->get_course_result_html( null, $force ); ?>
	<div class="lp-course-progress<?php echo $passed ? ' passed' : ''; ?>" data-value="<?php echo $current; ?>" data-passing-condition="<?php echo $passing_condition; ?>">
		<div class="lp-progress-bar">
			<div class="lp-progress-value" style="width: <?php echo $result * 100; ?>%;">
			</div>
		</div>
		<div class="lp-passing-conditional" style="left: <?php echo $passing_condition; ?>%;">
		</div>
	</div>
</div>