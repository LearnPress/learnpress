<?php
/**
 * Template for displaying Retake course button.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $course ) ) {
	$course = learn_press_get_course();
}

if ( ! isset( $user ) ) {
	$user = learn_press_get_current_user();
}

// If user has not finished course
if ( ! $user->has( 'finished-course', $course->get_id() ) ) {
	return;
}

if ( 0 >= ( $count = $user->can( 'retake-course', $course->get_id() ) ) ) {
	return;
}
?>

<?php do_action( 'learn-press/before-retake-form' ); ?>

    <form name="retake-course" class="retake-course" method="post" enctype="multipart/form-data">

		<?php do_action( 'learn-press/before-retake-button' ); ?>

        <input type="hidden" name="retake-course" value="<?php echo esc_attr( $course->get_id() ); ?>"/>
        <input type="hidden" name="retake-course-nonce"
               value="<?php echo esc_attr( LP_Nonce_Helper::create_course( 'retake' ) ); ?>"/>

        <button class="button button-retake-course">
			<?php echo esc_html( sprintf( apply_filters( 'learn-press/retake-course-button-text', __( 'Retake course (+%d)', 'learnpress' ) ), $count ) ); ?>
        </button>

		<?php do_action( 'learn-press/after-retake-button' ); ?>

    </form>

<?php do_action( 'learn-press/after-retake-form' ); ?>