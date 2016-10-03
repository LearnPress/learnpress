<?php
/**
 * Template for display content of lesson
 *
 * @author  ThimPress
 * @version 1.0
 */
global $lp_query, $wp_query;
$user          = learn_press_get_current_user();
$course        = LP()->global['course'];
$item          = LP()->global['course-item'];
$security      = wp_create_nonce( sprintf( 'complete-item-%d-%d-%d', $user->id, $course->id, $item->ID ) );
$can_view_item = $user->can( 'view-item', $item->id, $course->id );
?>
<h2 class="learn-press-content-item-title"><?php echo $item->title; ?></h2>
<div class="learn-press-content-item-summary">
	<?php echo apply_filters( 'learn_press_course_lesson_content', $item->content ); ?>
	<?php if ( $user->has_completed_lesson( $item->ID, $course->id ) ) { ?>
		<?php learn_press_display_message( __( 'Congrats! You have completed this lesson', 'learnpress' ) ); ?>
		<button class="" disabled="disabled"> <?php _e( 'Completed', 'learnpress' ); ?></button>
	<?php } else if ( !$user->has( 'finished-course', $course->id ) && $can_view_item != 'preview' ) { ?>
		<button class="button-complete-item button-complete-lesson" data-security="<?php echo esc_attr( $security ); ?>"><?php _e( 'Complete', 'learnpress' ); ?></button>
	<?php } ?>
	<?php if ( $user->can_edit_item( $item->id, $course->id ) ): ?>
		<p class="edit-course-item-link"><a class="" href="<?php echo get_edit_post_link( $item->id ); ?>"><?php _e( 'Edit lesson', 'learnpress' ); ?></a></p>
	<?php endif; ?>
</div>
