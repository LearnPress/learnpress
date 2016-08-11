<?php
/**
 * Template for display content of lesson
 *
 * @author  ThimPress
 * @version 1.0
 */
$user     = learn_press_get_current_user();
$course   = LP()->global['course'];
$security = wp_create_nonce( sprintf( 'complete-item-%d-%d-%d', $user->id, $course->id, $item->ID ) );
?>
<h4 class="learn-press-content-item-title"><?php echo $item->title; ?></h4>
<div class="learn-press-content-item-summary">

	<?php echo $item->content; ?>
	<?php if ( $user->has_completed_lesson( $item->ID, $course->id ) ) { ?>
		<?php _e( 'Completed', 'learnpress' ); ?>
	<?php } else if ( !$user->has( 'finished-course', $course->id ) ) { ?>
		<button class="button-complete-item button-complete-lesson" data-security="<?php echo esc_attr( $security ); ?>"><?php _e( 'Complete', 'learnpress' ); ?></button>
	<?php } ?>
</div>
