<<<<<<< HEAD
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

$course = LP()->global['course'];

?>

<span class="course-students">

	<?php echo $course->get_students_html(); ?>

</span>
=======
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

$course = LP()->global['course'];

?>

<span class="course-students">

	<?php echo $course->get_students_html(); ?>

</span>
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
