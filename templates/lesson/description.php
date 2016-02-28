<?php
/**
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

global $course;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( ! ( $lesson = $course->current_lesson ) ){
	return;
}

?>
<div class="course-lesson-description">

	<?php if( $the_content = apply_filters( 'the_content', $lesson->post->post_content ) ) :?>

		<?php echo $the_content; ?>

	<?php else: ?>

		<?php learn_press_display_message( __( 'This lesson has not got the content' , 'learnpress' ) );?>

	<?php endif;?>

</div>
