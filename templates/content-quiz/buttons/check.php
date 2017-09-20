<?php
/**
 * Template for displaying Check Answer button.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();
$quiz = LP_Global::course_item_quiz();
?>

<?php do_action( 'learn-press/quiz/before-check-answer-button' ); ?>

    <form name="check-answer-question" class="check-answer-question form-button" method="post"
          enctype="multipart/form-data" @submit="checkQuestion">

		<?php do_action( 'learn-press/quiz/begin-check-answer-button' ); ?>

		<?php if ( 0 > $quiz->get_show_check_answer() ) { ?>

            <button type="submit"><?php _e( 'Check', 'learnpress' ); ?></button>

		<?php } else { ?>

            <button type="submit"><?php printf( __( 'Check (+%d)', 'learnpress' ), $quiz->can_check_answer() ); ?></button>

		<?php } ?>

		<?php do_action( 'learn-press/quiz/end-check-answer-button' ); ?>

		<?php LP_Nonce_Helper::quiz_action( 'check-answer', $quiz->get_id(), get_the_ID() ); ?>

    </form>

<?php do_action( 'learn-press/quiz/after-check-answer-button' ); ?>