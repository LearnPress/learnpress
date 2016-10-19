<?php
/**
 * Template for displaying content of quiz's question
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$user   = learn_press_get_current_user();
$course = LP()->global['course'];
$quiz   = isset( $item ) ? $item : LP()->global['course-item'];
if ( !$quiz ) {
	return;
}
$question_id = $user->get_current_quiz_question( $quiz->id, $course->id );//$quiz->get_current_question();

if ( !$question_id ) {
	return;
}
$question = LP_Question_Factory::get_question( $question_id );
?>
<?php if ( false !== ( $title = apply_filters( 'learn_press_quiz_question_title', $question->get_title() ) ) ): ?>
	<h4 class="quiz-question-title"><?php echo $title; ?></h4>
<?php endif; ?>
<div class="quiz-question-content">
	<div method="post" name="quiz-question-content">
		<?php if ( false !== ( $content = apply_filters( 'learn_press_quiz_question_content', $question->get_content() ) ) ): ?>
			<div class="question-content">
				<?php echo $content; ?>
			</div>
		<?php endif; ?>
		<?php
		$question->render( array( 'quiz_id' => $quiz->id, 'course_id' => $course->id ) );
		?>
		<?php learn_press_get_template( 'content-question/hint.php', array( 'quiz' => $quiz ) ); ?>
	</div>
</div>
