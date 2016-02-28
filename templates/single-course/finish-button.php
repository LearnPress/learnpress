<?php
/**
 * Template for displaying the button let user can finish a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */
defined( 'ABSPATH' ) || exit();
if ( !LP()->user->can( 'finish-course', get_the_ID() ) ) {
	return;
}
?>
<button id="learn-press-finish-course" data-id="<?php the_ID(); ?>" data-nonce="<?php echo wp_create_nonce( 'learn-press-finish-course-' . get_the_ID() );?>">
	<?php _e( 'Finish this course', 'learnpress' ); ?>
</button>