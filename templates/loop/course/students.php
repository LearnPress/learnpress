<?php
/**
 * Template for displaying course students within the loop.
 *
 * Do not use in LP4.
 * Will remove after LearnPress and Eduma and all guest update 4.0.0
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
?>

<span class="course-students">

	<?php echo $course->get_users_enrolled(); ?>

</span>
