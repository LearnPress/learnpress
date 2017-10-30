<?php
/**
 * Displaying the description of single quiz
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$quiz = LP_Global::course_item_quiz();
if ( ! $content = $quiz->get_content() ) {
	return;
}
?>

<div class="content-item-description quiz-description"><?php echo $content; ?></div>
