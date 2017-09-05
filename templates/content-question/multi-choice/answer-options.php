<?php
/**
 * Template for displaying the content of multi-choice question
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
$user   = LP_Global::user();

isset( $question ) or die( __( 'Invalid question!', 'learnpress' ) );

$question = LP_Question_Factory::get_question( $question->get_id() );

$completed       = $user->has_quiz_status( 'completed', $quiz->get_id(), $course->get_id() );
$course_finished = $user->has_finished_course( $course->get_id() );
$checked         = $user->has_checked_answer( $question->get_id(), $quiz->get_id(), $course->get_id() );
$show_result     = $quiz->get_data( 'show_result' ) == 'yes';
$args            = array();

if ( $show_result && $completed ) {
	$args['classes'] = 'checked';
}

if ( ! $answers = $question->get_answers() ) {
	return;
}
//learn_press_debug( $answers );
?>
<ul class="answer-options">
	<?php
	foreach ( $answers as $k => $answer ):


//		$answer_class = ! $completed ? array( 'answer-option' ) : array( 'answer-option-result' );
//
//		$disabled = '';
//		if ( $completed && $show_result || $checked || $course_finished ) {
//			$answer_correct = true;
//			if ( $checked && $answer['is_true'] == 'yes' ) {
//				$answer_class[] = 'answer-true';
//			}
//			if ( $answer['is_true'] == 'yes' && ! $question->is_selected_option( $answer, $answered ) ) {
//				//$answer_correct = false;
//			}
//			if ( $answer['is_true'] != 'yes' && $question->is_selected_option( $answer, $answered ) ) {
//				$answer_correct = false;
//			}
//			if ( ! $answer_correct ) {
//				$answer_class[] = 'user-answer-false';
//			} else {
//				if ( $answer['is_true'] == 'yes' ) {
//					$answer_class[] = 'answer-true';
//				}
//			}
//			$answer_class = array_filter( $answer_class );
//			$disabled     = ' disabled="disabled"';
//		}
		$disabled = '';
		$id = uniqid( 'option-' );
		?>
        <li <?php echo $answer->option_class(); ?>>
            <input type="checkbox"
                   name="learn-press-question-<?php echo $question->get_id(); ?>[]"
                   value="<?php echo $answer->get_value(); ?>"
				<?php checked( $question->is_selected_option( $answer, false ) ); ?>
				<?php echo $disabled; ?> />
            <div class="option-title"><?php echo apply_filters( 'learn_press_question_answer_text', $answer->get_title(), $answer, $question ); ?></div>

			<?php do_action( 'learn_press_after_question_answer_text', $answer, $question ); ?>

        </li>
		<?php
	endforeach;

	?>
	<?php if ( $checked && $explanation = $question->get_data( 'explanation' ) ): ?>
		<?php learn_press_get_template( 'content-question/explanation.php', array( 'explanation' => $explanation ) ); ?>

	<?php endif; ?>
</ul>
