<?php
/**
 * Single quiz title
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$quiz = LP_Global::course_item_quiz();

if ( ! $title = $quiz->get_heading_title() ) {
	return;
}
?>
<h3 class="course-item-title quiz-title"><?php echo $title; ?></h3>
