<?php
/**
 * Template for displaying question's hint
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
if ( !$quiz || $user->get_quiz_status( $quiz->id ) != 'started' ) {
	return;
}
$question = $quiz->get_current_question( $user->id );

if ( !$question ) {
	return;
}

$hint = apply_filters( 'learn_press_question_hint', get_post_meta( $question->id, '_lp_hint', true ) );

if ( !$hint ) {
	return;
}
?>
<div class="learn-press-question-hint hide-if-js" data-title="<?php echo esc_attr( __( 'Hint', 'learnpress' ) ); ?>">
	<strong class="hint-title"><?php esc_html_e('Hint:', 'learnpress');?></strong>
	<?php echo $hint; ?>
</div>