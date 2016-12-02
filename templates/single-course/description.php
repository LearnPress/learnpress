<?php
/**
 * Displaying the description of single course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.0.6
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP()->global['course'];

if ( $course->is( 'viewing-item' ) ) {
	if ( false === apply_filters( 'learn_press_display_course_description_on_viewing_item', false ) ) {
		return;
	}
}

$description_heading = apply_filters( 'learn_press_single_course_description_heading', __( 'Course Description', 'learnpress' ), $course );
?>

<?php if ( $description_heading ) { ?>

	<h3 class="course-description-heading" id="learn-press-course-description-heading"><?php echo $description_heading; ?></h3>

<?php } ?>

<div class="course-description" id="learn-press-course-description">

	<?php do_action( 'learn_press_begin_single_course_description' ); ?>

	<?php the_content(); ?>

	<?php do_action( 'learn_press_end_single_course_description' ); ?>

</div>