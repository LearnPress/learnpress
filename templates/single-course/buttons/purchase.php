<?php
/**
 * Template for displaying Buy this course button.
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

// If user has already finished course
if ( $user->has( 'finished-course', $course->get_id() ) ) {
	return;
}

// If user can not purchase course
if ( ! $user->can( 'purchase-course', $course->get_id() ) ) {
	return;
}

?>

<?php do_action( 'learn-press/before-purchase-form' ); ?>

    <form name="purchase-course" class="purchase-course" method="post" enctype="multipart/form-data">

		<?php do_action( 'learn-press/before-purchase-button' ); ?>

        <input type="hidden" name="purchase-course" value="<?php echo esc_attr( $course->get_id() ); ?>"/>
        <input type="hidden" name="purchase-course-nonce"
               value="<?php echo esc_attr( LP_Nonce_Helper::create_course( 'purchase' ) ); ?>"/>

        <button class="button button-purchase-course">
			<?php echo esc_html( apply_filters( 'learn-press/purchase-course-button-text', __( 'Buy this course', 'learnpress' ) ) ); ?>
        </button>

		<?php do_action( 'learn-press/after-purchase-button' ); ?>

    </form>

<?php do_action( 'learn-press/after-purchase-form' ); ?>