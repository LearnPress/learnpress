<?php
/**
 * Template for displaying quiz item content in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-item-lp_quiz.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

use LearnPress\TemplateHooks\Quiz\QuizTemplate;

defined( 'ABSPATH' ) || exit();

$quiz = LP_Global::course_item_quiz();
?>

<div id="content-item-quiz" class="content-item-summary">
	<?php
	/**
	 * @see learn_press_content_item_summary_title()
	 * @see learn_press_content_item_summary_content()
	 */
	do_action( 'learn-press/before-content-item-summary/' . $quiz->get_item_type() );
	?>

	<?php
	/**
	 * @see learn_press_content_item_summary_question()
	 */
	if ( has_action( 'learn-press/content-item-summary/lp_quiz' ) ) {
		_deprecated_hook( 'learn-press/content-item-summary/lp_quiz', '4.3.2.3' );
		do_action( 'learn-press/content-item-summary/' . $quiz->get_item_type() );
	}

	QuizTemplate::instance()->quiz_content_item_summary();
	?>

</div>
