<?php
/**
 * Template for displaying course students within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/loop/course/students.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php $course = LP()->global['course']; ?>

<span class="course-students">

	<?php echo $course->get_students_html(); ?>

</span>
