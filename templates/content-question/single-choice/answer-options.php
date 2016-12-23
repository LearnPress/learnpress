<?php
/**
 * Template for displaying content of single-choice question
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$course          = LP()->global['course'];
$quiz            = LP()->global['course-item'];
$user            = learn_press_get_current_user();
$completed       = $user->get_quiz_status( $quiz->id, $course->id ) == 'completed';
$checked         = $user->has_checked_answer( $this->id, $quiz->id, $course->id );
$show_result     = !$completed ? $checked : $quiz->show_result == 'yes';
$course_finished = $user->has_finished_course( $course->id );

?>
<?php if ( $answers = $this->answers ) : ?>

	<ul class="learn-press-question-options">
		<?php
		foreach ( $answers as $k => $answer ):
			$answer_class = !$completed ? array( 'answer-option' ) : array( 'answer-option-result' );
			$disabled = '';
			if ( $completed && $show_result || $checked || $course_finished ) {
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
				$disabled = ' disabled="disabled"';
			}
			?>
			<li<?php echo $answer_class ? ' class="' . join( ' ', $answer_class ) . '"' : ''; ?> >

				<?php do_action( 'learn_press_before_question_answer_text', $answer, $this ); ?>
				<label>
					<input type="radio" name="learn-press-question-<?php echo $this->id; ?>" <?php checked( $this->is_selected_option( $answer, $answered ) ); ?> value="<?php echo $answer['value']; ?>" <?php echo $disabled; ?>>
					<p class="auto-check-lines"><?php echo apply_filters( 'learn_press_question_answer_text', $answer['text'], $answer, $this ); ?></p>
				</label>

				<?php do_action( 'learn_press_after_question_answer_text', $answer, $this ); ?>

			</li>
		<?php endforeach; ?>
		<?php if ( $checked && $explanation = $this->explanation ): ?>
			<?php learn_press_get_template( 'content-question/explanation.php', array( 'explanation' => $explanation ) ); ?>
		<?php endif; ?>
	</ul>
<?php endif; ?>