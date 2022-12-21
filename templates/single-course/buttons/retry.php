<?php
/**
 * Template for displaying Retake button in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/buttons/retake.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $can_retake_times ) ) {
	return;
}

$course = learn_press_get_course();
if ( ! $course ) {
	return;
}

$message_data_confirm = sprintf(
	'%s "%s"',
	esc_html__( 'Do you want to retake the course', 'learnpress' ),
	$course->get_title()
);
?>

<?php do_action( 'lp/tmpl/course/button-retry/form/before' ); ?>

<form name="lp-form-retake-course" class="lp-form-retake-course" method="post" enctype="multipart/form-data"
	data-confirm="<?php echo esc_attr( $message_data_confirm ); ?>">

	<?php do_action( 'lp/tmpl/course/button-retry/before' ); ?>

	<input type="hidden" name="retake-course" value="<?php echo esc_attr( $course->get_id() ); ?>"/>

	<button class="lp-button button button-retake-course">
		<?php echo sprintf( '%s (%d)', esc_html__( 'Retake course', 'learnpress' ), $can_retake_times ); ?>
	</button>

	<div class="lp-ajax-message"></div>

	<?php do_action( 'lp/tmpl/course/button-retry/after' ); ?>

</form>

<?php do_action( 'lp/tmpl/course/button-retry/form/after' ); ?>
