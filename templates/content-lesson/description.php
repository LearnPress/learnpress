<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

$course = LP()->global['course'];
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !( $lesson = $course->current_lesson ) ) {
	return;
}
?>
<div class="course-lesson-description">

	<?php if ( $the_content = apply_filters( 'the_content', $lesson->post->post_content ) ) : ?>
		<?php echo $course->get_item_content( $lesson->post->ID ); ?>
	<?php else: ?>

		<?php learn_press_display_message( __( 'This lesson has not got the content', 'learnpress' ) ); ?>

	<?php endif; ?>

</div>
