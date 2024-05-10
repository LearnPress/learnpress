<?php
/**
 * Template for displaying instructor of course within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/loop/course/instructor.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.1
 */

defined( 'ABSPATH' ) || exit();

$course = learn_press_get_course();
if ( ! $course ) {
	return;
}

$author_id  = $course->get_author( 'id' );
$instructor = learn_press_get_user( $author_id );
if ( ! $instructor ) {
	return;
}
?>

<div class="course-instructor">
	<?php echo wp_kses_post( sprintf( '<a href="%s">%s</a>', $instructor->get_url_instructor(), $instructor->get_display_name() ) ); ?>
</div>
