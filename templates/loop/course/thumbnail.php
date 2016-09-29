<?php
/**
 * Template for displaying course thumbnail within the loop
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP()->global['course'];

?>

<div class="course-thumbnail">
	<?php echo $course->get_image( 'course_thumbnail' ) ?>
</div>
