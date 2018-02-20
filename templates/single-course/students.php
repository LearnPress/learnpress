<?php
/**
 * Template for displaying the students of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.1.4
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$course = LP()->global['course'];

// Do not show if course is no require enrollment
if ( !$course || !$course->is_require_enrollment() ) {
	return;
}
?>

<p class="course-students">
	<?php echo $course->get_students_html(); ?>
</p>
