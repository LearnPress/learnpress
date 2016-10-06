<?php
/**
 * The template for display the content of single course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP()->global['course'];

if ( post_password_required() ) {
	echo get_the_password_form();
	return;
}
?>
<?php do_action( 'learn_press_before_main_content' ); ?>

	<?php do_action( 'learn_press_before_single_course' ); ?>

		<?php do_action( 'learn_press_before_single_course_summary' ); ?>

		<div class="course-summary">

			<?php if ( LP()->user->has( 'enrolled-course', $course->id ) || LP()->user->has( 'finished-course', $course->id ) ) { ?>

				<?php learn_press_get_template( 'single-course/content-learning.php' ); ?>

			<?php } else { ?>

				<?php learn_press_get_template( 'single-course/content-landing.php' ); ?>

			<?php } ?>

		</div>

		<?php do_action( 'learn_press_after_single_course_summary' ); ?>

	<?php do_action( 'learn_press_after_single_course' ); ?>

<?php do_action( 'learn_press_after_main_content' ); ?>
