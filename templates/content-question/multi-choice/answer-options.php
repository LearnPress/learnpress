<?php
/**
 * Template for displaying answer options of multi-choice question.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-question/multi-choice/answer-options.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

isset( $question ) or die( __( 'Invalid question!', 'learnpress' ) );

if ( ! $answers = $question->get_answers() ) {
	return;
}

$quiz = LP_Global::course_item_quiz();
$question->setup_data( $quiz->get_id() );


?>

<ul id="answer-options-<?php echo $question->get_id(); ?>" <?php echo $answers->answers_class(); ?>>

	<?php foreach ( $answers as $k => $answer ) { ?>

        <li <?php echo $answer->option_class(); ?>>
            <input type="checkbox" class="option-check" name="learn-press-question-<?php echo $question->get_id(); ?>[]"
                   value="<?php echo $answer->get_value(); ?>"
				<?php $answer->checked(); ?>
				<?php $answer->disabled(); ?> />
            <div class="option-title">
                <div class="option-title-content"><?php echo $answer->get_title( 'display' ); ?></div>
            </div>

			<?php do_action( 'learn_press_after_question_answer_text', $answer, $question ); ?>

        </li>

	<?php } ?>

</ul>
