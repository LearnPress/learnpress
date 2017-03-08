<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       2.1.4
 */

defined( 'ABSPATH' ) || exit();

$user        = learn_press_get_current_user();
$course      = LP()->global['course'];
$item_status = $user->get_item_status( $item->ID );
$security    = wp_create_nonce( sprintf( 'complete-item-%d-%d-%d', $user->id, $course->id, $item->ID ) );
$course_item = $course->get_item( $item->ID );
$status      = $user->get_course_status( $course->id );
?>
<div class="course-item-meta">
	<?php do_action( 'learn_press_before_item_meta', $item ); ?>
	<?php if ( $status != 'enrolled' && $course_item->is_preview() && $course->is_required_enroll() ): ?>
		<span class="lp-label lp-label-preview"><?php _e( 'Preview', 'learnpress' ); ?></span>
	<?php endif; ?>
	<?php if ( $user->can_view_item( $item->ID, $course->id ) !== false ) { ?>
		<?php if ( $item->post_type == 'lp_quiz' ) { ?>
			<span class="item-loop-meta-text item-result">
				<?php if ( $result = $user->get_quiz_results( $item->ID ) ): ?>
					<?php echo sprintf( '%d%%', round( $result->mark_percent ) ); ?>
				<?php endif; ?>
			</span>
		<?php } ?>
		<?php if ( $item_status == 'completed' ) { ?>
			<span class="lp-icon item-status" title="<?php esc_attr_e( 'Completed', 'learnpress' ); ?>"></span>
		<?php } elseif ( $item_status == 'started' ) { ?>
			<span class="lp-icon item-status button-complete-item button-complete-lesson" data-security="<?php echo esc_attr( $security ); ?>" title="<?php esc_attr_e( 'Not Completed', 'learnpress' ); ?>"></span>
		<?php } else { ?>
			<span class="lp-icon item-status button-complete-item button-complete-lesson" data-security="<?php echo esc_attr( $security ); ?>" title="<?php esc_attr_e( 'Not Started', 'learnpress' ); ?>"></span>
		<?php } ?>
	<?php } ?>
	<?php do_action( 'learn_press_after_item_meta', $item ); ?>
</div>
