<?php
/**
 * Single lesson title
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$lesson = LP_Global::course_item();

if ( ! $title = $lesson->get_title() ) {
	return;
}
?>
<h3 class="course-item-title quiz-title"><?php echo $title; ?></h3>