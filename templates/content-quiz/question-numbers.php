<?php
/**
 * Template for displaying number question in quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/question-numbers.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $quiz ) || ! isset( $questions ) ) {
	return;
}
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
