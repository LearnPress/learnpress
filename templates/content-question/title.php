<?php
/**
 * Template for displaying title of question.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-question/title.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! $quiz = LP_Global::course_item_quiz() ) {
	return;
}

if ( ! $question = LP_Global::quiz_question() ) {
	return;
}
?>

<h4 class="question-title"><?php echo $question->get_title( 'display' ); ?></h4>
