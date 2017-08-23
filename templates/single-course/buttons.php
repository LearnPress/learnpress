<?php
/**
 * Template for displaying the enroll button
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.1.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP()->global['course'];

?>
<div class="lp-course-buttons">

	<?php do_action( 'learn-press/before-course-buttons' ); ?>

	<?php
	/**
	 * @hooked learn_press_purchase_course_button - 10
	 * @hooked learn_press_enroll_course_button - 10
	 * @hooked learn_press_retake_course_button - 10
	 */
	do_action( 'learn-press/course-buttons' );
	?>

	<?php do_action( 'learn-press/after-course-buttons' ); ?>

</div>