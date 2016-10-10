<?php
/**
 * Display content item
 *
 * @author  ThimPress
 * @version 1.1
 */
$course = learn_press_get_the_course();
$item   = LP()->global['course-item'];
$user   = learn_press_get_current_user();
if ( !$item ) {
	return;
}
$item_id = isset( $item->id ) ? $item->id : ( isset( $item->ID ) ? $item->ID : 0 );
?>
<div id="learn-press-content-item">
	<?php if ( $item ) { ?>
		<?php if ( $user->can( 'view-item', $item->id, $course->id ) ) { ?>

			<?php do_action( 'learn_press_course_item_content', $item ); ?>

		<?php } else { ?>

			<?php learn_press_get_template( 'single-course/content-protected.php', array( 'item' => $item ) ); ?>

		<?php } ?>

	<?php } ?>

	<?php if ( $user->can_edit_item( $item_id, $course->id ) ): ?>
		<p class="edit-course-item-link">
			<a class="" href="<?php echo get_edit_post_link( $item_id ); ?>"><?php _e( 'Edit this item', 'learnpress' ); ?></a>
		</p>
	<?php endif; ?>
</div>