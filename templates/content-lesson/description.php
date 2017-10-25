<?php
/**
 * Displaying the description of single quiz
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$lesson = LP_Global::course_item();
if ( ! $content = $lesson->get_content() ) {
	return;
}
?>

<div class="content-item-description lesson-description"><?php echo $content; ?></div>