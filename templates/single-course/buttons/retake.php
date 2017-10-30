<?php
/**
 * Template for displaying Retake course button.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit();
$course = LP_Global::course();
$user   = LP_Global::user();

if ( 0 >= ( $count = $user->can_retake_course( $course->get_id() ) ) ) {
	return;
}
?>

<?php do_action( 'learn-press/before-retake-form' ); ?>

    <form name="retake-course" class="retake-course" method="post" enctype="multipart/form-data">

		<?php do_action( 'learn-press/before-retake-button' ); ?>

        <input type="hidden" name="retake-course" value="<?php echo esc_attr( $course->get_id() ); ?>"/>
        <input type="hidden" name="retake-course-nonce"
               value="<?php echo esc_attr( wp_create_nonce( sprintf( 'retake-course-%d-%d', $course->get_id(), $user->get_id() ) ) ); ?>"/>

        <button class="button button-retake-course">
			<?php echo esc_html( sprintf( apply_filters( 'learn-press/retake-course-button-text', __( 'Retake course (+%d)', 'learnpress' ) ), $count ) ); ?>
        </button>

        <input type="hidden" name="lp-ajax" value="retake-course"/>
        <input type="hidden" name="noajax" value="yes"/>

		<?php do_action( 'learn-press/after-retake-button' ); ?>

    </form>

<?php do_action( 'learn-press/after-retake-form' ); ?>