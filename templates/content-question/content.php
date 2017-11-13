<?php
/**
 * Template for displaying content of question.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit();
$question = LP_Global::quiz_question();
?>

<div class="content-question-summary" id="content-question-<?php echo $question->get_id(); ?>">
	<?php
	/**
	 * @see learn_press_content_item_summary_question_title()
	 * @see learn_press_content_item_summary_question_content()
	 * @see learn_press_content_item_summary_question()
	 */
	do_action( 'learn-press/question-content-summary' );

	?>
</div>