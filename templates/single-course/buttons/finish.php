<?php
/**
 * Template for displaying Finish button in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/buttons/finish.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.2.7.5
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
$user   = LP_Global::user();

$finish_not_passed = absint( $user->can_finish_course_not_passed( $course ) );
$finish_passed     = absint( $user->can_finish_course_passed( $course ) );

$message = LP_Strings::esc_attr( 'confirm-finish-course', '', array( $course->get_title() ) );

if ( $finish_not_passed && ! $finish_passed ) {
	$message = LP_Strings::esc_attr( 'confirm-finish-course-not-passed', '', array( absint( $course->get_passing_condition() ) . '%' ) );
}
?>

<form class="lp-form form-button form-button-finish-course" method="post" data-confirm="<?php echo $message ?>">
	<button class="lp-button"><?php _e( 'Finish course', 'learnpress' ); ?></button>
	<input type="hidden" name="course-id" value="<?php echo $course->get_id(); ?>"/>
	<input type="hidden" name="finish-course-nonce"
		   value="<?php echo esc_attr( wp_create_nonce( sprintf( 'finish-course-%d-%d', $course->get_id(), $user->get_id() ) ) ); ?>"/>
	<input type="hidden" name="lp-ajax" value="finish-course"/>
	<input type="hidden" name="noajax" value="yes"/>

</form>
