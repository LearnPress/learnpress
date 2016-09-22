<?php
/**
 * Display content item
 *
 * @author  ThimPress
 * @version 1.1
 */

$item_id = LP()->global['course-item'];
$user    = learn_press_get_current_user();
?>
<div id="learn-press-content-item">
	<?php if ( $item_id ) { ?>

		<?php if ( $user->can( 'view-item', LP()->global['course-item']->ID ) ) { ?>

			<?php do_action( 'learn_press_course_item_content', LP()->global['course-item'] ); ?>

		<?php } else { ?>

			<?php learn_press_get_template( 'single-course/content-protected.php' ); ?>

		<?php } ?>

	<?php } ?>
</div>