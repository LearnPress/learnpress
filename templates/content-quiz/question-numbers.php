<?php
/**
 * Template for displaying number question in quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/question-numbers.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$quiz = LP_Global::course_item_quiz();

if ( ! $questions = $quiz->get_questions() ) {
	return;
}

$questions = array_values( $questions );
?>

<ul class="question-numbers">

	<?php foreach ( $questions as $position => $question_id ) {
		$class = $quiz->get_question_number_class( $question_id, ++ $position ); ?>
        <li class="<?php echo join( ' ', $class ); ?>">
            <a href="<?php echo $quiz->get_question_link( $question_id ); ?>">
                <span><?php echo $position; ?></span>
            </a>
        </li>
	<?php } ?>

</ul>
