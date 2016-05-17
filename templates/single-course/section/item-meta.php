<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();

global $course;
?>
<div class="course-item-meta">

	<?php do_action( 'learn_press_before_item_meta', $item );?>

	<span class="lp-label lp-label-viewing"><?php _e( 'Viewing', 'learnpress' );?></span>

	<span class="lp-label lp-label-completed"><?php _e( 'Completed', 'learnpress' );?></span>

	<?php learn_press_item_meta_type( $course, $item ); ?>

	<?php learn_press_item_meta_format( $item->ID ); ?>

	<?php do_action( 'learn_press_after_item_meta', $item ); ?>

</div>
