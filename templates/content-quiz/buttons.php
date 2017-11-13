<?php
/**
 * Template for displaying buttons of the quiz.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="lp-quiz-buttons">

	<?php do_action( 'learn-press/before-quiz-buttons' ); ?>

	<?php
	/**
	 * @see learn_press_quiz_nav_buttons - 10
	 * @see learn_press_quiz_start_button - 10
	 * @see learn_press_quiz_complete_button - 10
	 * @see learn_press_quiz_redo_button - 10
	 */
	do_action( 'learn-press/quiz-buttons' );
	?>

	<?php do_action( 'learn-press/after-quiz-buttons' ); ?>

</div>

<?php
return;
$course = LP_Global::course();
$quiz   = LP_Global::course_item_quiz();
$user   = LP_Global::user();

$status   = $user->get_quiz_status( $quiz->get_id(), $course->get_id() );
$question = $quiz->get_viewing_question();

?>
<form class="quiz-buttons" method="post">

	<?php if ( $user->has( 'quiz-status', array( 'started' ), $quiz->get_id() ) ): ?>
        <button class="button-prev-question"><?php esc_html_e( 'Previous', 'learnpress' ); ?></button>
        <button class="button-next-question"><?php esc_html_e( 'Next', 'learnpress' ); ?></button>
		<?php if ( ! $user->has_checked_answer( $question->get_id(), $quiz->get_id(), $course->get_id() ) ): ?>
            <button
                    class="button-check-answer"
                    data-id="<?php esc_attr_e( $quiz->get_id() ); ?>"
                    data-action="check-answer"
                    data-security="<?php esc_attr_e( wp_create_nonce( 'check-question-' . $user->get_id() . '-' . $course->get_id() . '-' . $quiz->get_id() ) ); ?>">
				<?php esc_html_e( 'Check', 'learnpress' ); ?>
            </button>
            <button class="button-hint"
                    data-security="<?php esc_attr_e( wp_create_nonce( 'get-question-hint-' . $user->get_id() . '-' . $course->get_id() . '-' . $quiz->get_id() ) ); ?>"><?php esc_html_e( 'Hint', 'learnpress' ); ?></button>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( $user->has( 'quiz-status', 'completed', $quiz->get_id() ) ): ?>

		<?php if ( $remain = $user->can( 'retake-quiz', $quiz->get_id() ) ): ?>
            <button
                    class="button-retake-quiz"
                    data-action="retake-quiz"
                    data-id="<?php esc_attr_e( $quiz->get_id() ); ?>"
                    data-security="<?php esc_attr_e( wp_create_nonce( 'retake-quiz-' . $user->get_id() . '-' . $course->get_id() . '-' . $quiz->get_id() ) ); ?>">
				<?php echo esc_html( sprintf( '%s (+%d)', __( 'Retake', 'learnpress' ), $remain ) ); ?>
            </button>
		<?php endif; ?>

	<?php elseif ( $user->can_do_quiz( $quiz->get_id(), $course->get_id() ) ): ?>
        <button
                class="button-start-quiz"
                data-action="start-quiz"
                data-id="<?php esc_attr_e( $quiz->get_id() ); ?>"
                data-security="<?php esc_attr_e( wp_create_nonce( 'start-quiz-' . $user->get_id() . '-' . $course->get_id() . '-' . $quiz->get_id() ) ); ?>">
			<?php esc_html_e( 'Start Quiz', 'learnpress' ); ?>
        </button>
	<?php endif; ?>

	<?php if ( in_array( $status, array( 'started' ) ) ): ?>
        <button
                class="button-finish-quiz"
                data-action="finish-quiz"
                data-id="<?php esc_attr_e( $quiz->get_id() ); ?>"
                data-security="<?php esc_attr_e( wp_create_nonce( 'finish-quiz-' . $user->get_id() . '-' . $course->get_id() . '-' . $quiz->get_id() ) ); ?>">
			<?php esc_html_e( 'Finish Quiz', 'learnpress' ); ?>
        </button>
	<?php endif; ?>
    <input type="hidden" name="quiz_id" value="<?php echo esc_attr( $quiz->get_id() ); ?>"/>
    <input type="hidden" name="course_id" value="<?php echo esc_attr( $course->get_id() ); ?>"/>
    <input type="hidden" name="security" value=""/>
    <input type="hidden" name="lp-ajax" value=""/>
    <input type="hidden" name="noajax" value="yes"/>
</form>