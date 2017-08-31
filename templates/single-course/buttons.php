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

$user = LP_Global::user();

?>
<div class="lp-course-buttons">

	<?php do_action( 'learn-press/before-course-buttons' ); ?>

	<?php
	/**
	 * @see learn_press_purchase_course_button - 10
	 * @see learn_press_enroll_course_button - 10
	 * @see learn_press_retake_course_button - 10
	 */
	do_action( 'learn-press/course-buttons' );
	?>

	<?php do_action( 'learn-press/after-course-buttons' ); ?>

</div>