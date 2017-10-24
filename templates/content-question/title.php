<?php
/**
 * Template for displaying title of the question.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();

if ( ! $quiz = LP_Global::course_item_quiz() ) {
	return;
}

if ( ! $question = LP_Global::quiz_question() ) {
	return;
}
?>
<!--<h4>--><?php //echo apply_filters( 'learn-press/quiz-question-title', sprintf( __( 'Question %d', '' ), $quiz->get_question_index( $question->get_id() ) + 1 ) ); ?><!--</h4>-->
<h4 class="question-title"><?php echo $question->get_title();?></h4>
