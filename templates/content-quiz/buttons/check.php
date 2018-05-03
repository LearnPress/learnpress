<?php
/**
 * Template for displaying Check question answer button in quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/buttons/check.php.
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
$quiz     = LP_Global::course_item_quiz();
$question = LP_Global::quiz_question();
$user     = LP_Global::user();

$checked     = $user->has_checked_answer( $question->get_id(), $quiz->get_id(), get_the_ID() );
$button_text = $checked ? __( 'Checked', 'learnpress' ) : __( 'Check', 'learnpress' );
?>

<?php do_action( 'learn-press/quiz/before-check-answer-button' ); ?>

    <form name="check-answer-question" class="check-answer-question form-button lp-form" method="post"
          enctype="multipart/form-data" @submit="checkQuestion">

		<?php do_action( 'learn-press/quiz/begin-check-answer-button' ); ?>

		<?php if ( 0 > $quiz->get_show_check_answer() ) { ?>

            <button type="submit" <?php disabled( $checked ); ?>><?php echo $button_text; ?></button>

		<?php } else { ?>

            <button type="submit"
                    class="button-check-answer"
                    data-counter="<?php echo $user->can_check_answer( $quiz->get_id() ); ?>"
				<?php disabled( $checked ); ?>>
                <?php echo $button_text; ?>
            </button>

		<?php } ?>
        <input type="hidden" name="noajax" value="yes">
		<?php do_action( 'learn-press/quiz/end-check-answer-button' ); ?>

		<?php LP_Nonce_Helper::quiz_action( 'check-answer', $quiz->get_id(), get_the_ID(), true ); ?>

        <input type="hidden" name="question-id" value="<?php echo $question->get_id(); ?>">

    </form>

<?php do_action( 'learn-press/quiz/after-check-answer-button' ); ?>