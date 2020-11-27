<?php
/**
 * Template for displaying title of lesson.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$title = $lesson->get_title( 'display' );

if ( ! $title ) {
	return;
}
?>

<h3 class="course-item-title question-title"><?php echo esc_html( $title ); ?></h3>
