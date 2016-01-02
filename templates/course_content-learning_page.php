<?php
/**
 * Template for displaying content of course is learning
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
<?php do_action( 'learn_press_before_course_learning_content' ); ?>

<div id="course-learning">
	<?php do_action( 'learn_press_course_learning_content' ); ?>
</div>

<?php do_action( 'learn_press_after_course_learning_content' ); ?>
