<?php
/**
 * Template for displaying course students within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/loop/course/students.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! $course = learn_press_get_course() ) {
	return;
}

$count  = $course->count_students();
?>

<span class="course-students">

    <?php echo $count > 1 ? sprintf( __( '<span class="meta-number">%d</span> students', 'learnpress' ), $count ) : sprintf( __( '<span class="meta-number">%d</span> student', 'learnpress' ), $count ); ?>

</span>
