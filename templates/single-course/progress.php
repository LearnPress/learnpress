<?php
global $course;
$result = $course->evaluate_course_results();
$num_of_decimal = 0;
$current = round( $result * 100, $num_of_decimal );
$passing_condition = round( $course->passing_condition , $num_of_decimal);
?>
<div class="lp-course-progress<?php echo $current >= $passing_condition ? ' passed' : '';?>">
	<?php if ( ( $heading = apply_filters( 'learn_press_course_progress_heading', __( 'Learning progress', 'learn_press' ) ) ) !== false ): ?>
		<p class="lp-course-progress-heading"><?php echo $heading; ?></p>
	<?php endif; ?>
	<div class="lp-progress-bar">
		<div class="lp-progress-value" style="width: <?php echo $result * 100; ?>%;">
			<span><?php echo sprintf( __( 'Your progress (%s%%)', 'learn_press' ), $current ); ?></span>
		</div>
		<div class="lp-passing-conditional" style="left: <?php echo $course->passing_condition; ?>%;">
			<span><?php echo sprintf( __( 'Passing Condition (%s%%)', 'learn_press' ), $passing_condition ); ?></span>
		</div>
	</div>
	<div class="lp-progress-total">100%</div>
</div>