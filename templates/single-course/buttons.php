<?php
/**
 * Template for displaying the enroll button
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.1.6
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP()->global['course'];
?>
<div class="learn-press-course-buttons">
	<?php do_action( 'learn_press_before_course_buttons', $course->id ); ?>
	<?php
	if ( $external_link = $course->get_external_link() ):
		$external_button_text = apply_filters( 'learn_press_course_external_link_button_text', __( 'Buy this course', 'learnpress' ) );
		?>
		<?php do_action( 'learn_press_before_external_link_buy_course' ); ?>
		<div class="purchase-course">
			<a href="<?php echo esc_url( $external_link ); ?>" class="purchase-button">
				<?php echo $external_button_text; ?>
			</a>
		</div>
		<?php do_action( 'learn_press_after_external_link_buy_course' ); ?>
	<?php else:

		if ( $course->is_required_enroll() ) {


			$course_status = learn_press_get_user_course_status();
			$user          = learn_press_get_current_user();
			$in_cart       = learn_press_is_added_to_cart( $course->id );
			// only show enroll button if user had not enrolled
			$purchase_button_text  = apply_filters( 'learn_press_purchase_button_text', __( 'Buy this course', 'learnpress' ) );
			$enroll_button_text    = apply_filters( 'learn_press_enroll_button_text', __( 'Enroll', 'learnpress' ) );
			$retake_button_text    = apply_filters( 'learn_press_retake_button_text', __( 'Retake', 'learnpress' ) );
			$notice_enough_student = apply_filters( 'learn_press_course enough students_notice', __( 'The class is full so the enrollment is close. Please contact the site admin.', 'learnpress' ) );

			# -------------------------------
			# Finished Course
			# -------------------------------
			if ( $user->has( 'finished-course', $course->id ) ): ?>
				<?php if ( $count = $user->can( 'retake-course', $course->id ) ): ?>
					<button
						class="button button-retake-course"
						data-course_id="<?php echo esc_attr( $course->id ); ?>"
						data-security="<?php echo esc_attr( wp_create_nonce( sprintf( 'learn-press-retake-course-%d-%d', $course->id, $user->id ) ) ); ?>">
						<?php echo esc_html( sprintf( __( 'Retake course (+%d)', 'learnpress' ), $count ) ); ?>
					</button>
				<?php endif; ?>
				<?php

			# -------------------------------
			# Enrolled Course
			# -------------------------------
			elseif ( $user->has( 'enrolled-course', $course->id ) ): ?>
				<?php
				$can_finish = $user->can_finish_course( $course->id );
				//if ( $can_finish ) {
				$finish_course_security = wp_create_nonce( sprintf( 'learn-press-finish-course-' . $course->id . '-' . $user->id ) );
				//} else {
				//$finish_course_security = '';
				//}
				?>
				<button
					id="learn-press-finish-course"
					class="button-finish-course<?php echo !$can_finish ? ' hide-if-js' : ''; ?>"
					data-id="<?php echo esc_attr( $course->id ); ?>"
					data-security="<?php echo esc_attr( $finish_course_security ); ?>">
					<?php esc_html_e( 'Finish course', 'learnpress' ); ?>
				</button>
			<?php elseif ( $user->can( 'enroll-course', $course->id ) === true ) : ?>
				<form name="enroll-course" class="enroll-course" method="post" enctype="multipart/form-data">
					<?php do_action( 'learn_press_before_enroll_button' ); ?>

					<input type="hidden" name="lp-ajax" value="enroll-course" />
					<input type="hidden" name="enroll-course" value="<?php echo $course->id; ?>" />
					<button class="button enroll-button" data-block-content="yes"><?php echo $enroll_button_text; ?></button>

					<?php do_action( 'learn_press_after_enroll_button' ); ?>
				</form>
			<?php elseif ( $user->can( 'purchase-course', $course->id ) ) : ?>

				<form name="purchase-course" class="purchase-course" method="post" enctype="multipart/form-data">
					<?php do_action( 'learn_press_before_purchase_button' ); ?>
					<button class="button purchase-button" data-block-content="yes">
						<?php echo $course->is_free() ? $enroll_button_text : $purchase_button_text; ?>
					</button>
					<?php do_action( 'learn_press_after_purchase_button' ); ?>
					<input type="hidden" name="purchase-course" value="<?php echo $course->id; ?>" />
					<input type="hidden" value="user can purchase course" />
				</form>

			<?php elseif ( $user->can( 'enroll-course', $course->id ) === 'enough' ) : ?>
				<p class="learn-press-message"><?php echo $notice_enough_student; ?></p>
			<?php else: ?>
				<?php $order_status = $user->get_order_status( $course->id ); ?>
				<?php if ( in_array( $order_status, array( 'lp-pending', 'lp-refunded', 'lp-cancelled', 'lp-failed' ) ) ) { ?>
					<form name="purchase-course" class="purchase-course" method="post" enctype="multipart/form-data">
						<?php do_action( 'learn_press_before_purchase_button' ); ?>
						<button class="button purchase-button" data-block-content="yes">
							<?php echo $course->is_free() ? $enroll_button_text : $purchase_button_text; ?>
						</button>
						<?php do_action( 'learn_press_after_purchase_button' ); ?>
						<input type="hidden" name="purchase-course" value="<?php echo $course->id; ?>" />
						<input type="hidden" value="user order cancelled" />

					</form>
				<?php } elseif ( in_array( $order_status, array( 'lp-processing', 'lp-on-hold' ) ) ) { ?>
					<?php learn_press_display_message( '<p>' . apply_filters( 'learn_press_user_course_pending_message', __( 'Your order is processing. Please wait for approval.', 'learnpress' ), $course, $user ) . '</p>' ); ?>
				<?php } elseif ( $order_status && $order_status != 'lp-completed' ) { ?>
					<?php learn_press_display_message( '<p>' . apply_filters( 'learn_press_user_can_not_purchase_course_message', __( 'Sorry, you can not purchase this course', 'learnpress' ), $course, $user ) . '</p>' ); ?>
				<?php } ?>
			<?php endif;
		}
	endif;
	?>
	<?php do_action( 'learn_press_after_course_buttons', $course->id ); ?>
</div>