<?php
/**
 * Template for displaying instructor of single course.
 *
 * Do not use in LP4.
 * Will remove after LearnPress and Eduma and all guest update 4.0.0
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
?>

<div class="course-author">

	<h3><?php _e( 'About the Instructor', 'learnpress' ); ?></h3>

	<p class="author-name">
		<?php echo wp_kses_post( $course->get_instructor()->get_profile_picture() ); ?>
		<?php echo wp_kses_post( $course->get_instructor_html() ); ?>
	</p>
	<div class="author-bio">
		<?php echo wp_kses_post( wpautop( $course->get_author()->get_description() ) ); ?>
	</div>

</div>
