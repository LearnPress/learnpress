<?php
/**
 * Display content item
 *
 * @author  ThimPress
 * @version 2.1.7
 */
$course = learn_press_get_the_course();
$item   = LP()->global['course-item'];
$user   = learn_press_get_current_user();
if ( ! $item ) {
	return;
}
$item_id = isset( $item->id ) ? $item->id : ( isset( $item->ID ) ? $item->ID : 0 );
?>
<div id="learn-press-content-item">
	<?php do_action( 'learn_press/before_course_item_content', $item_id, $course->id ); ?>
	<?php if ( $item ) { ?>
		<?php if ( $user->can( 'view-item', $item->id, $course->id ) ) { ?>

			<?php do_action( 'learn_press_course_item_content', $item ); ?>

		<?php } else { ?>

			<?php learn_press_get_template( 'single-course/content-protected.php', array( 'item' => $item ) ); ?>

		<?php } ?>

	<?php } ?>
	<?php //do_action( 'learn_press_after_content_item', $item_id, $course->id, true ); ?>
	<?php do_action( 'learn_press/after_course_item_content', $item_id, $course->id ); ?>

</div>