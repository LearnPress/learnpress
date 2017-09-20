<?php
/**
 * Template for displaying Hint button.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();
$quiz = LP_Global::course_item_quiz();
?>

<?php do_action( 'learn-press/quiz/before-question-hint-button' );?>

<form name="question-hint" class="question-hint form-button" method="post" enctype="multipart/form-data" @submit="showHint">

	<?php do_action( 'learn-press/quiz/begin-question-hint-button' );?>

	<?php if ( 0 > $quiz->get_show_hint() ) { ?>

        <button type="submit"><?php _e( 'Hint', 'learnpress' ); ?></button>

	<?php } else { ?>

        <button type="submit"><?php printf( __( 'Hint (+%d)', 'learnpress' ), $quiz->can_hint_answer() ); ?></button>

	<?php } ?>

	<?php do_action( 'learn-press/quiz/end-question-hint-button' );?>

	<?php LP_Nonce_Helper::quiz_action( 'show-hint', $quiz->get_id(), get_the_ID() ); ?>

</form>

<?php do_action( 'learn-press/quiz/after-question-hint-button' );?>