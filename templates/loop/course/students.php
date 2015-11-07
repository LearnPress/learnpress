<?php
/**
 * Template for displaying course students within the loop
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
