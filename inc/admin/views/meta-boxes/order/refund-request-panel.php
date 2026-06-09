<?php
/**
 * Admin order detail: pending refund request panel.
 *
 * Rendered via learn_press_admin_view() from learn_press_admin_order_refund_request_panel().
 *
 * @since 4.3.4
 * @version 1.0.0
 *
 * @var int    $order_id
 * @var bool   $is_pending
 * @var float  $order_total
 * @var string $order_total_formatted
 * @var string $refund_amount_formatted Empty unless the refund has been approved/auto-approved.
 * @var string $requester
 * @var string $requested_time
 * @var string $requester_email
 * @var string $refund_reason
 * @var string $status_label
 */
defined( 'ABSPATH' ) || exit();
?>
<?php if ( ! $is_pending ) : ?>
	<?php if ( ! empty( $refund_amount_formatted ) ) : ?>
		<div class="order-data-field order-data-refund-request">
			<p class="description order-data-refund-amount">
				<strong><?php esc_html_e( 'Refund amount:', 'learnpress' ); ?></strong>
				<?php echo wp_kses_post( $refund_amount_formatted ); ?>
			</p>
		</div>
	<?php endif; ?>
	<?php return; ?>
<?php endif; ?>
<div
	class="order-data-field order-data-refund-request"
	data-order-id="<?php echo esc_attr( $order_id ); ?>"
	data-order-total="<?php echo esc_attr( $order_total ); ?>"
	data-order-total-formatted="<?php echo esc_attr( $order_total_formatted ); ?>"
	data-confirm-title="<?php esc_attr_e( 'Approve refund?', 'learnpress' ); ?>"
	data-confirm-text="<?php esc_attr_e( 'Enter an optional message and the amount to refund.', 'learnpress' ); ?>"
	data-message-label="<?php esc_attr_e( 'Message to payer', 'learnpress' ); ?>"
	data-message-placeholder="<?php esc_attr_e( 'Enter an optional refund message.', 'learnpress' ); ?>"
	data-amount-label="<?php esc_attr_e( 'Refund amount', 'learnpress' ); ?>"
	data-amount-invalid="<?php echo esc_attr( sprintf( __( 'Refund amount must be greater than 0 and must not exceed %s.', 'learnpress' ), $order_total_formatted ) ); ?>"
	data-confirm-button="<?php esc_attr_e( 'Approve Refund', 'learnpress' ); ?>"
	data-cancel-button="<?php esc_attr_e( 'Cancel', 'learnpress' ); ?>"
>
	<label><?php esc_html_e( 'Refund Request', 'learnpress' ); ?></label>
	<p class="description">
		<?php
		echo esc_html(
			sprintf(
				__( 'Requested by %1$s at %2$s.', 'learnpress' ),
				$requester,
				$requested_time
			)
		);
		?>
	</p>
	<?php if ( ! empty( $requester_email ) ) : ?>
		<p class="description">
			<?php echo esc_html( sprintf( __( 'Requester email: %s', 'learnpress' ), $requester_email ) ); ?>
		</p>
	<?php endif; ?>
	<?php if ( ! empty( $refund_reason ) ) : ?>
		<p class="description">
			<strong><?php esc_html_e( 'Reason:', 'learnpress' ); ?></strong>
			<?php echo wp_kses_post( nl2br( esc_html( $refund_reason ) ) ); ?>
		</p>
	<?php endif; ?>

	<?php if ( ! empty( $status_label ) ) : ?>
		<p class="description">
			<?php echo esc_html( sprintf( __( 'Request status: %s', 'learnpress' ), $status_label ) ); ?>
		</p>
	<?php endif; ?>
	<p>
		<button type="button" class="button button-primary lp-admin-refund-order-action" data-refund-action="approve">
			<?php esc_html_e( 'Approve Refund', 'learnpress' ); ?>
		</button>
		<button type="button" class="button lp-admin-refund-order-action" data-refund-action="deny">
			<?php esc_html_e( 'Deny Refund', 'learnpress' ); ?>
		</button>
	</p>
</div>
