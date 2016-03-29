<?php
/**
 * Template for displaying the buttons of a quiz
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$quiz = LP()->quiz;
$user = LP()->user;
if ( !$quiz ) {
	return;
}
$status = $user->get_quiz_status( $quiz->id );
?>
<div class="quiz-buttons">

	<?php if ( !$user->has( 'started-quiz', $quiz->id ) ): ?>
		<button class="button-start-quiz" data-id="<?php esc_attr_e( $quiz->id ); ?>" data-start-quiz-nonce="<?php esc_attr_e( wp_create_nonce( 'start-quiz-' . $quiz->id ) ); ?>"><?php _e( "Start Quiz", "learnpress" ); ?></button>
	<?php endif; ?>

	<button class="button-finish-quiz<?php echo !$status ? ' hide-if-js' : ''; ?>" data-id="<?php esc_attr_e( $quiz->id ); ?>" data-finish-quiz-nonce="<?php esc_attr_e( wp_create_nonce( 'finish-quiz-' . $quiz->id ) ); ?>"><?php _e( "Finish Quiz", "learnpress" ); ?></button>

	<?php if ( $remain = $user->can( 'retake-quiz', $quiz->id ) ): ?>
		<button class="button-retake-quiz<?php echo $status != 'completed' ? ' hide-if-js' : ''; ?>" data-id="<?php esc_attr_e( $quiz->id ); ?>" data-retake-quiz-nonce="<?php esc_attr_e( wp_create_nonce( 'retake-quiz-' . $quiz->id ) ); ?>"><?php echo sprintf( '%s (+%d)', __( 'Retake', 'learnpress' ), $remain ); ?></button>
	<?php endif; ?>

</div>