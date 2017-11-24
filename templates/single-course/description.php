<?php
/**
 * Template for displaying description of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/description.php.
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

if ( $course->is_viewing_item() ) {
	if ( false === apply_filters( 'learn_press_display_course_description_on_viewing_item', false ) ) {
		return;
	}
}

$description_heading = apply_filters( 'learn_press_single_course_description_heading', __( 'Course description', 'learnpress' ), $course );
?>

<?php if ( $description_heading ) { ?>

    <h3 class="course-description-heading"
        id="learn-press-course-description-heading"><?php echo $description_heading; ?>
    </h3>

<?php } ?>

<div class="course-description" id="learn-press-course-description">

	<?php do_action( 'learn_press_begin_single_course_description' ); ?>

	<?php the_content(); ?>

	<?php do_action( 'learn_press_end_single_course_description' ); ?>

</div>