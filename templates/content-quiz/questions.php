<?php
/**
 * Template for displaying list of questions in quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/questions.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
$course = LP()->global['course'];
$quiz   = LP()->global['course-item'];
$user   = LP()->user;
?>

<?php if ( ! $quiz || learn_press_quiz_is_hide_question( $quiz->id ) ) {
	return;
} ?>

<?php
$status       = $user->get_quiz_status( $quiz->id, $course->get_id() );
$heading      = apply_filters( 'learn_press_list_questions_heading', __( 'List of questions', 'learnpress' ) );
$no_permalink = $user->has_quiz_status( array( '', 'completed', 'viewed' ), $quiz->id, $course->get_id() );
?>

<?php if ( $heading ) { ?>
    <h4 class="lp-group-heading-title toggle-on"
        onclick="LP.toggleGroupSection('#learn-press-quiz-questions', this);"><?php echo $heading; ?><span
                class="toggle-icon"></span></h4>
<?php } ?>

<?php if ( $quiz->has( 'questions' ) ) { ?>

    <div class="quiz-questions lp-group-content-wrap" id="learn-press-quiz-questions">

		<?php do_action( 'learn_press_before_quiz_questions' ); ?>

        <ol class="quiz-questions-list">

			<?php if ( $questions = $quiz->get_questions() ) {
				foreach ( $questions as $question ) { ?>
                    <li <?php learn_press_question_class( $question->ID, array( 'user' => $user, 'quiz' => $quiz ) ); ?>
                            data-id="<?php echo $question->ID; ?>">

						<?php do_action( 'learn_press_before_quiz_question_title', $question->ID, $quiz->id ); ?>

						<?php if ( $no_permalink ) { ?>
							<?php printf( '<p class="question-title">%s</p>', get_the_title( $question->ID ) ); ?>
						<?php } else { ?>
							<?php printf( '<a class="question-title js-action" href="%s">%s</a>', $quiz->get_question_link( $question->ID ), get_the_title( $question->ID ) ); ?>
						<?php } ?>

						<?php do_action( 'learn_press_after_quiz_question_title', $question->ID, $quiz->id ); ?>

                    </li>
				<?php }
			} ?>
        </ol>

		<?php do_action( 'learn_press_after_quiz_questions' ); ?>

    </div>

<?php } else { ?>

	<?php learn_press_display_message( apply_filters( 'learn_press_quiz_no_questions_notice', __( 'This quiz hasn\'t got any questions', 'learnpress' ) ) ); ?>

<?php } ?>






