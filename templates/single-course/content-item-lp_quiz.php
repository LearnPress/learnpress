<?php
/**
 * Template for displaying content of the quiz
 *
 * @author ThimPress
 */
$user        = learn_press_get_current_user();
$course      = LP_Global::course();
$quiz        = LP_Global::course_item_quiz();
$user_course = $user->get_course_data( $course->get_id() );
if ( ! $quiz ) {
	return;
}

$user_item      = $user_course[ $quiz->get_id() ];
$have_questions = $quiz->get_questions();
$can_view_item  = $user->can( 'view-item', $quiz->get_id(), $course->get_id() );

$user_course->get_item_at( 1 );

echo 'Mark:';
print_r( $quiz->get_mark() );
echo ']';
?>

<div class="content-item-summary">

	<?php


	/**
	 *
	 */
	do_action( 'learn-press/before-content-item-summary/' . $quiz->get_item_type() );

	do_action( 'learn-press/content-item-summary/' . $quiz->get_item_type() );

	do_action( 'learn-press/after-content-item-summary/' . $quiz->get_item_type() );

	$position = 0;
	global $wp, $lp_quiz_question;

	$question = $quiz->get_viewing_question();
	$question->render();
	//learn_press_debug( $lp_quiz_question );
	?>

    <ul class="question-numbers">
		<?php foreach ( $have_questions as $question_id ) {
			$position ++; ?>
            <li<?php echo $quiz->is_viewing_question( $question_id ) ? ' class="current"' : ''; ?> >
                <a href="<?php echo $quiz->get_question_link( $question_id ); ?>"><?php echo $position; ?></a>
            </li>
		<?php } ?>
    </ul>

    <div id="content-item-nav">
        <div class="content-item-nav-wrap">
            <form>
                <a href="<?php echo $course->get_next_item(); ?>">Prev</a>
                <button>Next</button>
            </form>
        </div>
    </div>

</div>

<?php return; ?>
<div class="content-item-quiz">
    <div id="content-item-<?php echo $quiz->id; ?>">
        <div class="learn-press-content-item-title content-item-quiz-title">
			<?php if ( false !== ( $item_quiz_title = apply_filters( 'learn_press_item_quiz_title', $quiz->title ) ) ): ?>
                <h4><?php echo $item_quiz_title; ?></h4>
			<?php endif; ?>
            <a href="" class="lp-expand dashicons-editor-expand dashicons"></a>
			<?php $have_questions && learn_press_get_template( 'content-quiz/countdown-simple.php' ); ?>
        </div>

        <div id="quiz-<?php echo $quiz->id; ?>" class="learn-press-content-item-summary">
			<?php if ( $user->has_quiz_status( array( 'completed' ), $quiz->id, $course->get_id() ) ): ?>
				<?php learn_press_get_template( 'content-quiz/description.php' ); ?>
				<?php learn_press_get_template( 'content-quiz/intro.php' ); ?>
				<?php learn_press_get_template( 'content-quiz/result.php' ); ?>

			<?php elseif ( $user->has( 'quiz-status', 'started', $quiz->id, $course->get_id() ) ): ?>
				<?php if ( $have_questions ): ?>
					<?php learn_press_get_template( 'content-quiz/question-content.php' ); ?>
				<?php endif; ?>
			<?php else: ?>

				<?php learn_press_get_template( 'content-quiz/description.php' ); ?>
				<?php learn_press_get_template( 'content-quiz/intro.php' ); ?>

			<?php endif; ?>

			<?php //if ( $have_questions ) { ?>
			<?php learn_press_get_template( 'content-quiz/buttons.php' ); ?>
			<?php // } ?>
        </div>

    </div>
	<?php if ( $have_questions ) { ?>
		<?php learn_press_get_template( 'content-quiz/history.php' ); ?>
		<?php learn_press_get_template( 'content-quiz/questions.php' ); ?>
	<?php } else { ?>
		<?php learn_press_display_message( __( 'No questions', 'learnpress' ) ); ?>
	<?php } ?>

	<?php LP_Assets::add_var( 'LP_Quiz_Params', wp_json_encode( $quiz->get_settings( $user->get_id(), $course->get_id() ) ), '__all' ); ?>

</div>