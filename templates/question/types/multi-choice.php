<?php
/**
 * Template for displaying the content of multi-choice question
 *
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $quiz;

?>
<div <?php learn_press_question_class( $this );?> data-id="<?php echo $this->id; ?>" data-type="multi-choice">

<?php do_action( 'learn_press_before_question_wrap', $this );?>

	<h4 class="learn-press-question-title"><?php echo get_the_title( $this->id ); ?></h4>

<?php do_action( 'learn_press_before_question_options', $this ); ?>

	<ul class="learn-press-question-options">
		<?php if ( $answers = $this->answers ) foreach ( $answers as $k => $answer ): ?>
			<li>

				<?php do_action( 'learn_press_before_question_answer_text', $answer, $this );?>

				<label>
					<input type="checkbox" name="learn-press-question-<?php echo $this->id; ?>[]" <?php checked( $this->is_selected_option( $answer, $answered ) ); ?> value="<?php echo $answer['value']; ?>" />
					<?php echo apply_filters( 'learn_press_question_answer_text', $answer['text'], $answer, $this ); ?>
				</label>

				<?php do_action( 'learn_press_before_question_answer_text', $answer, $this );?>

			</li>
		<?php endforeach; ?>
	</ul>
	<input type="hidden" name="learn-press-question-permalink" value="<?php echo esc_url( $quiz->get_question_link( $this->id ) );?>" />
<?php do_action( 'learn_press_after_question_wrap', $this );?>
	</div>
