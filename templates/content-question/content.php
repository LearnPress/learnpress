<?php
/**
 * Template for displaying content of question.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-question/content.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $question ) ) {
	return;
}
?>

<div class="content-question-summary" id="content-question-<?php echo $question->get_id(); ?>">
	<?php
	/**
	 * @see learn_press_content_item_summary_question_title()
	 * @see learn_press_content_item_summary_question_content()
	 * @see learn_press_content_item_summary_question()
	 */
	do_action( 'learn-press/question-content-summary' ); ?>
</div>