<?php
/**
 * Template for displaying description of question.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();
$quiz = LP_Global::course_item_quiz();

if ( ! $question = LP_Global::quiz_question() ) {
	return;
}

$user = LP_Global::user();
$quiz_data = $user->get_quiz_data($quiz->get_id());
$result = $quiz_data->get_result();

unset($result['questions']);
learn_press_debug($quiz_data);

if ( ! $content = $question->get_content() ) {
	return;
}
?>
<div class="quiz-question-desc">
	<?php echo $content; ?>
</div>
