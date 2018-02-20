<<<<<<< HEAD
<?php
/**
 * Template for displaying the tags of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP()->global['course'];

$tags = apply_filters( 'learn_press_course_tags', get_the_term_list( $course->id, 'course_tag', __( 'Tags: ', 'learnpress' ), ', ', '' ) );
if ( !$tags ) {
	return;
}
?>

=======
<?php
/**
 * Template for displaying the tags of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP()->global['course'];

$tags = apply_filters( 'learn_press_course_tags', get_the_term_list( $course->id, 'course_tag', __( 'Tags: ', 'learnpress' ), ', ', '' ) );
if ( !$tags ) {
	return;
}
?>

>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
<span class="course-tags"><?php echo $tags; ?></span>