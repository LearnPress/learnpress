<?php
/**
 * Template for displaying the content of multi-choice question
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$quiz = LP()->global['course-item'];
$user = LP()->user;

$completed   = $user->get_quiz_status( $quiz->id ) == 'completed';
$show_result = $quiz->show_result == 'yes';
$checked     = $user->has_checked_answer( $this->id, $quiz->id ) || $completed;

$args = array();
if ( $show_result && $completed ) {
	$args['classes'] = 'checked';
}

?>
<div <?php learn_press_question_class( $this, $args ); ?> data-id="<?php echo $this->id; ?>" data-type="multi-choice">

	<?php do_action( 'learn_press_before_question_wrap', $this ); ?>

	<!--
	<h4 class="learn-press-question-title"><?php echo get_the_title( $this->id ); ?></h4>

	<div class="question-desc">
		<?php echo apply_filters( 'the_content', $this->post->post_content ); ?>
	</div>
	-->
	<?php do_action( 'learn_press_before_question_options', $this ); ?>


	<?php do_action( 'learn_press_after_question_wrap', $this, $quiz ); ?>

</div>
