<?php
/**
 * Template for displaying quiz item content in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-item-lp_quiz.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
$user        = learn_press_get_current_user();
$course      = LP_Global::course();
$quiz        = LP_Global::course_item_quiz();
$user_course = $user->get_course_data( $course->get_id() );
?>
<?php if ( ! $quiz ) {
	return;
} ?>

<?php
$user_item      = $user_course[ $quiz->get_id() ];
$have_questions = $quiz->get_questions();
$can_view_item  = $user->can( 'view-item', $quiz->get_id(), $course->get_id() );
?>

<div id="content-item-quiz" class="content-item-summary">

	<?php
	/**
	 * @see learn_press_content_item_summary_title()
	 * @see learn_press_content_item_summary_content()
	 */
	do_action( 'learn-press/before-content-item-summary/' . $quiz->get_item_type() );

	?>

	<?php
	/**
	 * @see learn_press_content_item_summary_question()
	 */
	do_action( 'learn-press/content-item-summary/' . $quiz->get_item_type() );
	?>

	<?php
	/**
	 * @see learn_press_content_item_summary_question_numbers()
	 */
	do_action( 'learn-press/after-content-item-summary/' . $quiz->get_item_type() );
	?>

</div>