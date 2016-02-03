<?php
global $course;
$result = $course->evaluate_course_results();
?>
<div class="lp-course-progress">
	<?php if( ( $heading = apply_filters( 'learn_press_course_progress_heading', __( 'Learning progress', 'learn_press' ) ) ) !== false ): ?>
	<p class="lp-course-progress-heading"><?php echo $heading;?></p>
	<?php endif; ?>
	<div class="lp-progress-bar">
		<div class="lp-progress-value" style="width: <?php echo $result * 100;?>%;">
			<span><?php echo $result * 100;?>%</span>
		</div>
		<div class="lp-passing-conditional" style="left: <?php echo $course->passing_condition ;?>%;">
			<span><?php _e( 'Passing Conditional', 'learn_press' );?></span>
		</div>
	</div>
	<div class="lp-progress-total">100%</div>
</div>