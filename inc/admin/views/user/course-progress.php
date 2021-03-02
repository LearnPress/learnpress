<?php
$course_data       = $user->get_course_data( $course->get_id() );
$course_results    = $course_data->get_results( false );
$passing_condition = $course->get_passing_condition();
?>

<div class="course-results-progress">
	<div class="items-progress lp-progress-row">

		<?php $heading = apply_filters( 'learn-press/course/items-completed-heading', esc_html__( 'Items completed:', 'learnpress' ) ); ?>

		<?php if ( $heading ) : ?>
			<h4 class="lp-course-progress-heading"><?php echo esc_html__( 'Items completed:', 'learnpress' ); ?></h4>
		<?php endif; ?>

		<span class="number"><?php printf( __( '%1$d of %2$d items', 'learnpress' ), $course_results['completed_items'], $course->count_items( '', true ) ); ?></span>

		<div class="learn-press-progress lp-course-progress">
			<div class="progress-bg lp-progress-bar">
				<div class="progress-active lp-progress-value" style="left: <?php echo $course_results['count_items'] ? absint( $course_results['completed_items'] / $course_results['count_items'] * 100 ) : 0; ?>%;">
				</div>
			</div>
		</div>
	</div>

	<div class="course-progress lp-progress-row">

		<?php $heading = apply_filters( 'learn-press/course/result-heading', esc_html__( 'Course progress:', 'learnpress' ) ); ?>

		<?php if ( $heading ) : ?>
			<h4 class="lp-course-progress-heading">
				<?php echo esc_html( $heading ); ?>
			</h4>
		<?php endif; ?>

		<div class="lp-course-status">
			<span class="number">
				<?php echo round( $course_results['result'], 2 ); ?>
				<span class="percentage-sign">%</span>
			</span>

			<?php $graduation = $course_data->get_graduation(); ?>
			<?php if ( $graduation ) : ?>
				<span class="lp-graduation <?php echo esc_attr( $graduation ); ?>" style="color: #222; font-weight: 600;">
				- <?php learn_press_course_grade_html( $graduation ); ?>
				</span>
			<?php endif; ?>
		</div>

		<div class="learn-press-progress lp-course-progress <?php echo $course_data->is_passed() ? ' passed' : ''; ?>" data-value="<?php echo $course_results['result']; ?>" data-passing-condition="<?php echo $passing_condition; ?>">
			<div class="progress-bg lp-progress-bar">
				<div class="progress-active lp-progress-value" style="left: <?php echo esc_attr( $course_results['result'] ); ?>%;"></div>
			</div>
			<div class="lp-passing-conditional" data-content="<?php printf( esc_html__( 'Passing condition: %s%%', 'learnpress' ), $passing_condition ); ?>" style="left: <?php echo esc_attr( $passing_condition ); ?>%;"></div>
		</div>
	</div>
</div>
