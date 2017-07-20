<?php
/**
 * Displaying the description of single course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $course;
?>
<div class="course-description" id="learn-press-course-description">

	<?php
	/**
	 * @deprecated
	 */
	do_action( 'learn_press_begin_single_course_description' );

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/before-single-course-description' );

	echo $course->get_content();

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/after-single-course-description' );

	/**
	 * @deprecated
	 */
	do_action( 'learn_press_end_single_course_description' );
	?>

</div>