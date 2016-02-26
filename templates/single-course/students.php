<?php
/**
 * Template for displaying the students of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $course;

?>

<span class="course-students">
	<?php echo $course->get_students_html(); ?>
</span>
