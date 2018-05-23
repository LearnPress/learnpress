<?php
/**
 * Template for displaying Next/Prev buttons in quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/buttons/nav.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$user      = LP_Global::user();
$quiz      = LP_Global::course_item_quiz();
$current_question_id = $quiz->get_viewing_question( 'id' );
$question = LP_Global::quiz_question();
$course_id = get_the_ID();
$hide_next = get_post_meta($quiz->get_id(), '_lp_show_submit_hide_next', true);
$has_checked =  $user->has_checked_answer( $current_question_id, $quiz->get_id(), get_the_ID() ); 
?>

<?php if ( $prev_id = $user->get_prev_question( $quiz->get_id(), $course_id ) ) { ?>

	<?php do_action( 'learn-press/quiz/before-prev-question-button' ); ?>

    <form name="prev-question" class="prev-question form-button lp-form" method="post"
          action="<?php echo $quiz->get_question_link( $prev_id ); ?>">

		<?php do_action( 'learn-press/quiz/begin-prev-question-button' ); ?>

        <button type="submit"><?php echo esc_html_x( 'Prev', 'quiz-question-navigation', 'learnpress' ); ?></button>
        <input type="hidden" name="question-id" value="<?php echo $current_question_id; ?>">

		<?php do_action( 'learn-press/quiz/end-prev-question-button' ); ?>

		<?php LP_Nonce_Helper::quiz_action( 'nav-question', $quiz->get_id(), $course_id ); ?>
    </form>

	<?php do_action( 'learn-press/quiz/after-prev-question-button' ); ?>

<?php } ?>

<?php if ( $next_id = $user->get_next_question( $quiz->get_id(), $course_id ) ) { ?>
<?php if (!$hide_next || $has_checked) { ?>
	<?php do_action( 'learn-press/quiz/before-next-question-button' ); ?>

    <form name="next-question" class="next-question form-button lp-form" method="post"
          action="<?php echo $quiz->get_question_link( $next_id ); ?>">

		<?php do_action( 'learn-press/quiz/begin-next-question-button' ); ?>

        <button type="submit"><?php echo esc_html_x( 'Next', 'quiz-question-navigation', 'learnpress' ); ?></button>
        <input type="hidden" name="question-id" value="<?php echo $current_question_id; ?>">

		<?php do_action( 'learn-press/quiz/end-next-question-button' ); ?>

		<?php LP_Nonce_Helper::quiz_action( 'nav-question', $quiz->get_id(), $course_id ); ?>
    </form>

	<?php do_action( 'learn-press/quiz/after-prev-question-button' ); ?>
<?php } ?>
<?php } ?>

<?php if ( ( $next_id = $user->get_next_question( $quiz->get_id(), $course_id ) ) && ! $user->has_completed_quiz( $quiz->get_id(), $course_id ) ) { ?>

	<?php do_action( 'learn-press/quiz/before-skip-question-button' ); ?>

    <form name="skip-question" class="skip-question form-button lp-form" method="post"
          action="<?php echo $quiz->get_question_link( $next_id ); ?>">

		<?php do_action( 'learn-press/quiz/begin-skip-question-button' ); ?>

        <button type="submit"><?php echo esc_html_x( 'Skip', 'quiz-question-navigation', 'learnpress' ); ?></button>
        <input type="hidden" name="question-id" value="<?php echo $current_question_id; ?>">

		<?php do_action( 'learn-press/quiz/end-skip-question-button' ); ?>

		<?php LP_Nonce_Helper::quiz_action( 'nav-question', $quiz->get_id(), $course_id ); ?>
    </form>

	<?php do_action( 'learn-press/quiz/after-skip-question-button' ); ?>

<?php } ?>

