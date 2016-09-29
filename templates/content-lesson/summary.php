<?php
/**
 * Template for displaying lesson content in a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

global $course;

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( $course->is( 'viewing' ) != 'lesson' && $course->is( 'viewing' ) != 'quiz' ) {
	return;
}
$item         = $course->current_item;
$item_heading = apply_filters( 'learn_press_course_item_content_heading', apply_filters( 'the_title', $item->post->post_title ), $item, $course );
?>
<?php if ( $item_heading ) { ?>

	<h3 class="course-lesson-heading" id="learn-press-course-lesson-heading"><?php echo $item_heading; ?></h3>

<?php } ?>
<div class="course-lesson-summary" id="learn-press-course-lesson-summary">

	<?php do_action( 'learn_press_course_lesson_summary' ); ?>

</div>

