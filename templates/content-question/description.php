<?php
/**
 * Template for displaying description of question.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();
$quiz = LP_Global::course_item_quiz();

if ( ! $question = LP_Global::quiz_question() ) {
	return;
}

if ( ! $content = $question->get_content() ) {
	return;
}
?>
<div class="quiz-question-desc">
	<?php echo $content; ?>
</div>
