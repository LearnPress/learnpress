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
$course = learn_press_get_the_course();
$quiz   = LP()->global['course-item'];
$user   = LP()->user;

$completed   = $user->has_quiz_status( 'completed', $quiz->id, $course->id );
$show_result = $quiz->show_result == 'yes';
$checked     = $user->has_checked_answer( $this->id, $quiz->id, $course->id );
$args = array();
if ( $show_result && $completed ) {
	$args['classes'] = 'checked';
}
?>
<?php if ( $answers = $this->answers ): ?>
	<ul class="learn-press-question-options">
		<?php

		foreach ( $answers as $k => $answer ):
			$answer_class = array( 'answer-option' );
			if ( $completed && $show_result || $checked ) {
				$answer_correct = true;
				if ( $checked && $answer['is_true'] == 'yes' ) {
					$answer_class[] = 'answer-true';
				}
				if ( $answer['is_true'] == 'yes' && !$this->is_selected_option( $answer, $answered ) ) {
					//$answer_correct = false;
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
					<p class="auto-check-lines"><?php echo apply_filters( 'learn_press_question_answer_text', $answer['text'], $answer, $this ); ?></p>
				</label>

				<?php do_action( 'learn_press_after_question_answer_text', $answer, $this ); ?>

			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>