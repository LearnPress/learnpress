<?php
/**
 * Template for displaying the content of multi-choice question
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

$completed   = $user->get_quiz_status( $quiz->id ) == 'completed';
$show_result = $quiz->show_result == 'yes';
$checked     = $user->has_checked_answer( $this->id, $quiz->id ) || $completed;

$args = array();
if ( $show_result && $completed ) {
	$args['classes'] = 'checked';
}

?>
<div <?php learn_press_question_class( $this, $args ); ?> data-id="<?php echo $this->id; ?>" data-type="multi-choice">

	<?php do_action( 'learn_press_before_question_wrap', $this ); ?>

	<h4 class="learn-press-question-title"><?php echo get_the_title( $this->id ); ?></h4>

	<?php do_action( 'learn_press_before_question_options', $this ); ?>

	<ul class="learn-press-question-options">
		<?php if ( $answers = $this->answers ) foreach ( $answers as $k => $answer ): ?>
			<?php
			$answer_class = array();
			if ( $completed && $show_result ) {
				$answer_class   = array();
				$answer_correct = true;
				if ( $completed && $show_result && $answer['is_true'] == 'yes' ) {
					$answer_class[] = 'answer-true';
				}
				if ( $answer['is_true'] == 'yes' && !$this->is_selected_option( $answer, $answered ) ) {
					$answer_correct = false;
				}
				if ( $answer['is_true'] != 'yes' && $this->is_selected_option( $answer, $answered ) ) {
					$answer_correct = false;
				}
				if ( !$answer_correct ) {
					$answer_class[] = 'user-answer-false';
				}
			}
			?>
			<li<?php echo $answer_class ? ' class="' . join( ' ', $answer_class ) . '"' : ''; ?>>

				<?php do_action( 'learn_press_before_question_answer_text', $answer, $this ); ?>

				<label>
					<input type="checkbox" name="learn-press-question-<?php echo $this->id; ?>[]" <?php checked( $this->is_selected_option( $answer, $answered ) ); ?> value="<?php echo $answer['value']; ?>" <?php echo $checked ? 'disabled="disabled"' : ''; ?> />
					<?php echo apply_filters( 'learn_press_question_answer_text', $answer['text'], $answer, $this ); ?>
				</label>

				<?php do_action( 'learn_press_after_question_answer_text', $answer, $this ); ?>

			</li>
		<?php endforeach; ?>
	</ul>

	<?php do_action( 'learn_press_after_question_wrap', $this, $quiz ); ?>

</div>
