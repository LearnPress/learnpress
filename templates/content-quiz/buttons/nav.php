<?php
/**
 * Template for displaying Next/Prev buttons inside quiz.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();

$user      = LP_Global::user();
$quiz      = LP_Global::course_item_quiz();
$course_id = get_the_ID();

if ( $prev_id = $user->get_prev_question( $quiz->get_id(), $course_id ) ) {
	?>
    <form name="prev-question" class="prev-question form-button" method="post"
          action="<?php echo $quiz->get_question_link( $prev_id ); ?>">
        <button type="submit"><?php _e( 'Prev', 'learnpress' ); ?></button>
    </form>
	<?php
}
?>

<?php
if ( $next_id = $user->get_next_question( $quiz->get_id(), $course_id ) ) {
	?>
    <form name="next-question" class="next-question form-button" method="post"
          action="<?php echo $quiz->get_question_link( $next_id ); ?>"
          @click="nextQuestion">
        <button type="submit"><?php _e( 'Next', 'learnpress' ); ?></button>
		<?php LP_Nonce_Helper::quiz_action( 'next-question', $quiz->get_id(), $course_id ); ?>
    </form>
	<?php
}
?>

