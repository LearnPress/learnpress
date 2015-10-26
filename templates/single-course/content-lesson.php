<?php
/**
 * Template for displaying lesson content in a course
 *
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

global $course;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( $course->is( 'viewing' ) != 'lesson' ){
	return;
}
$lesson = $course->current_lesson;
$lesson_heading = apply_filters( 'learn_press_lesson_content_heading', apply_filters( 'the_title', $lesson->post->post_title ) , $lesson, $course );

?>

<?php if( $lesson_heading ) { ?>

	<h3 class="course-lesson-heading" id="learn-press-course-lesson-heading"><?php echo $lesson_heading;?></h3>

<?php }?>

<div class="course-lesson-summary" id="learn-press-course-lesson-summary">

	<?php do_action( 'learn_press_course_lesson_summary' );?>

</div>
