<?php
/**
 * Template for displaying Next/Prev buttons inside quiz.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();

$user      = LP_Global::user();
$quiz      = LP_Global::course_item_quiz();
$course_id = get_the_ID();

if ( $prev_id = $user->get_prev_question( $quiz->get_id(), $course_id ) ) {
	?>
	<?php do_action( 'learn-press/quiz/before-prev-question-button' ); ?>

    <form name="prev-question" class="prev-question form-button lp-form" method="post"
          action="<?php echo $quiz->get_question_link( $prev_id ); ?>"
          @submit="prevQuestion">

		<?php do_action( 'learn-press/quiz/begin-prev-question-button' ); ?>

        <button type="submit"><?php _e( 'Prev', 'learnpress' ); ?></button>

		<?php do_action( 'learn-press/quiz/end-prev-question-button' ); ?>

		<?php LP_Nonce_Helper::quiz_action( 'nav-question', $quiz->get_id(), $course_id ); ?>
    </form>

	<?php do_action( 'learn-press/quiz/after-prev-question-button' ); ?>
	<?php
}
?>

<?php
if ( $next_id = $user->get_next_question( $quiz->get_id(), $course_id ) ) {
	?>
	<?php do_action( 'learn-press/quiz/before-next-question-button' ); ?>

    <form name="next-question" class="next-question form-button lp-form" method="post"
          action="<?php echo $quiz->get_question_link( $next_id ); ?>"
          @submit="nextQuestion">

		<?php do_action( 'learn-press/quiz/begin-next-question-button' ); ?>

        <button type="submit"><?php _e( 'Next', 'learnpress' ); ?></button>

		<?php do_action( 'learn-press/quiz/end-next-question-button' ); ?>

		<?php LP_Nonce_Helper::quiz_action( 'nav-question', $quiz->get_id(), $course_id ); ?>
    </form>

	<?php do_action( 'learn-press/quiz/after-prev-question-button' ); ?>
	<?php
}
?>

