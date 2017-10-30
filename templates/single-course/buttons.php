<?php
/**
 * Template for displaying buttons of the course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="lp-course-buttons">

	<?php do_action( 'learn-press/before-course-buttons' ); ?>

	<?php
	/**
	 * @see learn_press_course_purchase_button - 10
	 * @see learn_press_course_enroll_button - 10
	 * @see learn_press_course_retake_button - 10
	 */
	do_action( 'learn-press/course-buttons' );
	?>

	<?php do_action( 'learn-press/after-course-buttons' ); ?>

</div>