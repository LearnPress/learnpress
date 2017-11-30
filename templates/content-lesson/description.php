<?php
/**
 * Template for displaying description of lesson.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-lesson/description.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
$course = LP_Global::course();
$lesson = LP_Global::course_item();
$user   = LP_Global::user();

$is_admin      = in_array( 'administrator', $user->get_data( 'roles' ) );
$block_content = $course->get_data( 'block_lesson_content' );
?>

<?php
// block lesson content when course expired
if ( ! $is_admin && $course->is_expired() <= 0 && ( $block_content == 'yes' ) && ( get_post_meta( $lesson->get_id(), '_lp_preview', true ) !== 'yes' ) ) {
	learn_press_get_template( 'content-lesson/block-content.php' );

	return;
}

// lesson no content
if ( ! $content = $lesson->get_content() ) {
	learn_press_get_template( 'content-lesson/no-content.php' );

	return;
} ?>

<div class="content-item-description lesson-description"><?php echo $content; ?></div>