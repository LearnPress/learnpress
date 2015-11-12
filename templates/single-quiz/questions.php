<?php
/**
 * Template for displaying the list of questions for the quiz
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $quiz;
$heading = apply_filters( 'learn_press_list_questions_heading', __( 'List of questions', 'learn_press' ) );
$has_finished = LP()->user->has('completed-quiz', $quiz->id);

?>

<?php if ( $heading ) { ?>
	<h4><?php echo $heading;?></h4>
<?php } ?>

<?php if ( $quiz->has( 'questions' ) ): ?>

	<div class="quiz-questions" id="learn-press-quiz-questions">

		<?php do_action( 'learn_press_before_quiz_questions' ); ?>

		<ul class="quiz-questions-list">
			<?php if ( $questions = $quiz->get_questions() ) foreach ( $questions as $question ) { ?>
				<li data-id="<?php echo $question->ID; ?>">
					<?php if( $has_finished ){?>
					<?php printf( '<span>%s</span>', get_the_title( $question->ID ) ); ?>
					<?php }else{?>
					<?php printf( '<a href="%s">%s</a>', $quiz->get_question_link( $question->ID ), get_the_title( $question->ID ) ); ?>
					<?php }?>
				</li>
			<?php } ?>
		</ul>

		<?php do_action( 'learn_press_after_quiz_questions' ); ?>

	</div>

<?php else: ?>

	<?php learn_press_display_message( apply_filters( 'learn_press_quiz_no_questions_notice', __( 'This quiz hasn\'t got any questions', 'learn_press' ) ) ); ?>

<?php endif; ?>






