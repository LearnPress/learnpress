<?php
global $course;
$result = $course->evaluate_course_results();
$num_of_decimal = 0;
?>
<div class="lp-course-progress">
	<?php if ( ( $heading = apply_filters( 'learn_press_course_progress_heading', __( 'Learning progress', 'learn_press' ) ) ) !== false ): ?>
		<p class="lp-course-progress-heading"><?php echo $heading; ?></p>
	<?php endif; ?>
	<div class="lp-progress-bar">
		<div class="lp-progress-value" style="width: <?php echo $result * 100; ?>%;">
			<span><?php echo round( $result * 100, $num_of_decimal ); ?>%</span>
		</div>
		<div class="lp-passing-conditional" style="left: <?php echo $course->passing_condition; ?>%;">
			<span><?php echo __( 'Passing Condition', 'learn_press' ) . ' (' . round( $course->passing_condition , $num_of_decimal) . '%)'; ?></span>
		</div>
	</div>
	<div class="lp-progress-total">100%</div>
</div>