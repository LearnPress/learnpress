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

if ( $course->is( 'viewing' ) != 'quiz' ) {
	return;
}

$quiz = $course->current_item;
?>
<?php echo apply_filters( 'the_content', $quiz->post->post_content ); ?>
<?php if ( $quiz->has( 'questions' ) ) { ?>

	<a href="<?php echo get_the_permalink( $quiz->id ); ?>" target="_blank"><?php _e( 'Do this quiz', 'learnpress' ); ?></a>

<?php } else { ?>

	<?php learn_press_display_message( __( 'This quiz has not got any questions', 'learnpress' ), 'error' ); ?>

<?php } ?>
