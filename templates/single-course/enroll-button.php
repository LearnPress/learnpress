<?php
/**
 * Template for displaying the enroll button
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $course;
$course_status = learn_press_get_user_course_status();
// only show enroll button if user had not enrolled
$button_text  = apply_filters( 'learn_press_take_course_button_text', __( 'Take this course', 'learn_press' ) );
$loading_text = apply_filters( 'learn_press_take_course_button_loading_text', __( 'Processing', 'learn_press' ) );
?>

<?php if ( '' == $course_status && $course->is_require_enrollment() ) :?>

	<form name="enroll-course" class="enroll-course" method="post" enctype="multipart/form-data">
		<?php do_action( 'learn_press_before_enroll_button' ); ?>

		<input type="hidden" name="add-course-to-cart" value="<?php echo $course->id; ?>" />
		<button class="button enroll-button"><?php echo $button_text; ?></button>

		<?php do_action( 'learn_press_after_enroll_button' ); ?>
	</form>

<?php endif; ?>

