<?php
/**
 * Template for displaying Enroll button.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $course ) ) {
	$course = learn_press_get_course();
}

if ( ! isset( $user ) ) {
	$user = learn_press_get_current_user();
}

// Course is not require enrolling
if ( ! $course->is_required_enroll() ) {
	return;
}

// If user has already finished course
if ( $user->has( 'finished-course', $course->get_id() ) ) {
	return;
}

// If user has already enrolled course
if ( $user->has( 'enrolled-course', $course->get_id() ) ) {
	return;
}

?>

<?php do_action( 'learn-press/before-enroll-form' ); ?>

    <form name="enroll-course" class="enroll-course" method="post" enctype="multipart/form-data">

		<?php do_action( 'learn-press/before-enroll-button' ); ?>

        <input type="hidden" name="enroll-course" value="<?php echo esc_attr( $course->get_id() ); ?>"/>
        <input type="hidden" name="enroll-course-nonce"
               value="<?php echo esc_attr( LP_Nonce_Helper::create_course( 'enroll' ) ); ?>"/>

        <button class="button button-enroll-course">
			<?php echo esc_html( apply_filters( 'learn-press/enroll-course-button-text', __( 'Enroll', 'learnpress' ) ) ); ?>
        </button>

		<?php do_action( 'learn-press/after-enroll-button' ); ?>

    </form>

<?php do_action( 'learn-press/after-enroll-form' ); ?>