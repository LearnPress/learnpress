<?php
/**
 * @author Thimpress
 * @version 1.0.1
 */

if ( ! isset( $user ) || ! isset( $course ) || ! isset( $course_result ) || ! isset( $course_data ) ) {
	return;
}

$passing_condition = $course->get_passing_condition();
/*$completed_percent = 0;

if ( isset( $course_result['count_items'] ) && isset( $course_result['completed_items'] )
	&& $course_result['count_items'] > 0 ) {
	$completed_percent = round( $course_result['completed_items'] * 100 / $course_result['count_items'], 2 );
}*/

?>

<div class="course-results-progress">
	<div class="items-progress lp-progress-row">

		<?php $heading = apply_filters( 'learn-press/course/items-completed-heading', esc_html__( 'Items completed:', 'learnpress' ) ); ?>

		<?php if ( $heading ) : ?>
			<h4 class="lp-course-progress-heading"><?php echo esc_html__( 'Items completed:', 'learnpress' ); ?></h4>
		<?php endif; ?>

		<span class="number"><?php printf( __( '%1$d of %2$d items', 'learnpress' ), $course_result['completed_items'] ?? 0, $course->count_items() ); ?></span>
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
				<?php echo round( $course_result['result'], 2 ); ?>
				<span class="percentage-sign">%</span>
			</span>

			<?php $graduation = $course_data->get_graduation(); ?>
			<?php if ( $graduation ) : ?>
				<span class="lp-graduation <?php echo esc_attr( $graduation ); ?>" style="color: #222; font-weight: 600;">
				- <?php learn_press_course_grade_html( $graduation ); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>
</div>
