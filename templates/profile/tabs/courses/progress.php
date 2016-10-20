<?php
/**
 * Progress bar in profile page
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;


$course = learn_press_get_course( $course_id );

learn_press_setup_user_course_data( $user->id, $course_id );

if ( !$user->has_course_status( $course_id, array( 'enrolled', 'finished' ) ) ) {
	return;
}

$force             = isset( $force ) ? $force : false;
$num_of_decimal    = 0;
$result            = ( $user->get_course_info2( $course_id ) );
$current           = absint( $result );
$passing_condition = round( $course->passing_condition, $num_of_decimal );

?>

<div class="learn-press-course-results-progress">
	<div class="course-progress">
		<span class="course-result"><?php echo $result['results'] . '%'; ?></span>
		<div class="lp-course-progress">
			<div class="lp-progress-bar">
				<div class="lp-progress-value" style="width: <?php echo $result['results']; ?>%;">
				</div>
			</div>
			<div class="lp-passing-conditional"
				 data-content="<?php printf( esc_html__( 'Passing condition: %s%%', 'learnpress' ), $passing_condition ); ?>"
				 style="left: <?php echo $passing_condition; ?>%;">
			</div>
		</div>
	</div>
</div>