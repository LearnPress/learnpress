<?php
/**
 * Template for displaying instructor of single course.
 *
 * Do not use in LP4.
 * Will remove after LearnPress and Eduma and all guest update 4.0.0
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = learn_press_get_course();
if ( ! $course ) {
	return;
}

$instructor = $course->get_instructor();
if ( ! $instructor instanceof LP_User ) {
	return;
}

$img_profile = $instructor->get_profile_picture();
?>

<div class="course-author">

	<h3><?php _e( 'About the Instructor', 'learnpress' ); ?></h3>

	<p class="author-name">
		<?php echo wp_kses_post( $img_profile ); ?>
		<?php echo wp_kses_post( $course->get_instructor_html() ); ?>
	</p>
	<div class="author-bio">
		<?php echo wp_kses_post( wpautop( $course->get_author()->get_description() ) ); ?>
	</div>

</div>
