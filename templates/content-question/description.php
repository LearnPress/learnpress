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

?>
<div class="quiz-question-desc">
	<?php echo $question->get_content(); ?>
</div>
