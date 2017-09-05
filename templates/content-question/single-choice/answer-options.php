<?php
/**
 * Template for displaying content of single-choice question
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.1.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$course = LP_Global::course();
$quiz   = LP_Global::course_item_quiz();
$user   = learn_press_get_current_user();

/**$completed       = $user->get_quiz_status( $quiz->get_id(), $course->get_id() ) == 'completed';
 * $checked         = $user->has_checked_answer( $this->get_id(), $quiz->get_id(), $course->get_id() );
 * $show_result     = ! $completed ? $checked : $quiz->get_data( 'show_result' ) == 'yes';
 * $course_finished = $user->has_finished_course( $course->get_id() );*/

if ( ! $answers = $question->get_answers() ) {
	return;
}
?>
<ul class="answer-options">
	<?php
	foreach ( $answers as $k => $answer ):
//		$answer_class = ! $completed ? array( 'answer-option' ) : array( 'answer-option-result' );
//		$disabled = '';
//		if ( $completed && $show_result || $checked || $course_finished ) {
//			$answer_correct = true;
//			if ( $checked && $answer['is_true'] == 'yes' ) {
//				$answer_class[] = 'answer-true';
//			}
//			if ( $answer['is_true'] == 'yes' && ! $this->is_selected_option( $answer, $answered ) ) {
//				//$answer_correct = false;
//			}
//			if ( $answer['is_true'] != 'yes' && $this->is_selected_option( $answer, $answered ) ) {
//				$answer_correct = false;
//			}
//			if ( ! $answer_correct ) {
//				$answer_class[] = 'user-answer-false';
//			} else {
//				if ( $answer['is_true'] == 'yes' ) {
//					$answer_class[] = 'answer-true';
//				}
//			}
//			$disabled = ' disabled="disabled"';
//		}
		$disabled = '';
		$id = uniqid( 'option-' );
		$checked = '';
		?>
        <li <?php echo $answer->option_class(); ?> @click="toggle">
            <input type="radio"
                   class="option-check"
                   name="learn-press-question-<?php echo $question->get_id(); ?>"
                   value="<?php echo $answer->get_value(); ?>"
				<?php checked( $question->is_selected_option( $answer, true ) ); ?>
				<?php echo $disabled; ?> />
            <div class="option-title">
                <div class="option-title-content"><?php echo apply_filters( 'learn_press_question_answer_text', $answer->get_title(), $answer, $question ); ?></div>
            </div>

			<?php do_action( 'learn_press_after_question_answer_text', $answer, $question ); ?>

        </li>
	<?php endforeach; ?>
	<?php if ( $checked && $explanation = $this->explanation ): ?>
		<?php learn_press_get_template( 'content-question/explanation.php', array( 'explanation' => $explanation ) ); ?>
	<?php endif; ?>
</ul>
