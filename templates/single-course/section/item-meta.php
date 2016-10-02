<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();

$user        = LP()->user;
$course      = LP()->global['course'];
$item_status = $user->get_item_status( $item->ID );
$security    = wp_create_nonce( sprintf( 'complete-item-%d-%d-%d', $user->id, $course->id, $item->ID ) );

?>
<div class="course-item-meta">

	<?php do_action( 'learn_press_before_item_meta', $item ); ?>

	<!--<span class="lp-label lp-label-viewing"><?php _e( 'Viewing', 'learnpress' ); ?></span>-->

	<?php if ( $user->can_view_item( $item->ID ) !== false ) { ?>
		<?php if ( $item->post_type == 'lp_quiz' && $result = $user->get_quiz_results( $item->ID ) ) {?>
			<span class="item-loop-meta-text"><?php echo sprintf( '%d%%', $result->mark_percent );?></span>
		<?php }	?>
		<?php if ( $item_status == 'completed' ) { ?>
			<span class="lp-icon item-status" title="<?php esc_attr_e( 'Completed', 'learnpress' ); ?>"></span>
		<?php } elseif ( $item_status == 'started' ) { ?>
			<span class="lp-icon item-status button-complete-item button-complete-lesson" data-security="<?php echo esc_attr( $security ); ?>" title="<?php esc_attr_e( 'Not Completed', 'learnpress' ); ?>"></span>
		<?php } else { ?>
			<span class="lp-icon item-status button-complete-item button-complete-lesson" data-security="<?php echo esc_attr( $security ); ?>" title="<?php esc_attr_e( 'Not Started', 'learnpress' ); ?>"></span>
		<?php } ?>
	<?php } ?>

	<?php //learn_press_item_meta_type( $course, $item ); ?>

	<?php do_action( 'learn_press_after_item_meta', $item ); ?>

</div>
