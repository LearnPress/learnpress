<?php
/**
 * Template for displaying content of single-choice question
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $quiz;

?>
<div <?php learn_press_question_class( $this );?> data-id="<?php echo $this->id; ?>" data-type="single-choice">

	<?php do_action( 'learn_press_before_question_wrap', $this );?>

	<h4 class="learn-press-question-title"><?php echo get_the_title( $this->id ); ?></h4>

	<?php do_action( 'learn_press_before_question_options', $this ); ?>

	<ul class="learn-press-question-options">
		<?php if ( $answers = $this->answers ) foreach ( $answers as $k => $answer ): ?>
			<li>

				<?php do_action( 'learn_press_before_question_answer_text', $answer, $this );?>

				<label>
					<input type="radio" name="learn-press-question-<?php echo $this->id; ?>" <?php checked( $this->is_selected_option( $answer, $answered ) ); ?> value="<?php echo $answer['value']; ?>">
					<?php echo apply_filters( 'learn_press_question_answer_text', $answer['text'], $answer, $this ); ?>
				</label>

				<?php do_action( 'learn_press_before_question_answer_text', $answer, $this );?>

			</li>
		<?php endforeach; ?>
	</ul>
	<input type="hidden" name="learn-press-question-permalink" value="<?php echo esc_url( $quiz->get_question_link( $this->id ) );?>" />
	<?php do_action( 'learn_press_before_question_wrap', $this );?>

	<?php
	/*$question         = get_post( $this->get( 'ID' ) );
	$question_content = $question->post_content;
	if ( !empty( $question_content ) ) :
		?>

		<div id="question-hint" class="question-hint-wrap">
			<h5 class="question-hint-title"><?php _e( 'Question hint', 'learn_press' ); ?></h5>

			<div class="question-hint-content">
				<p><?php echo apply_filters( 'the_content', $question_content ); ?></p>
			</div>
		</div>
		<script type="text/javascript">
			jQuery('.question-hint-content').hide();
			jQuery('#question-hint').on('click', function () {
				jQuery('.question-hint-content').fadeToggle();
			});
		</script>

	<?php endif; */?>
</div>

<?php
//$a = array ( 'current_question' => 1, 'question_answers' => array ( 688 => array ( 'option_third', '5632e12a6d216' ), 507 => 'option_seconds' ) );
//print_r(serialize($a));