<?php
/**
 * Template for displaying confirm message after order is placed.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/order/confirm.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $order ) ) {
	$order = learn_press_get_order();
}
?>

<?php if ( $order ) { ?>

	<?php if ( $order->has_status( 'failed' ) ) : ?>
		<p>
			<?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'learnpress' ); ?>
		</p>

		<p>
			<?php
			if ( is_user_logged_in() ) {
				esc_html_e( 'Please attempt your purchase again or go to your account page.', 'learnpress' );
			} else {
				esc_html_e( 'Please attempt your purchase again.', 'learnpress' );
			}
			?>
		</p>

	<?php else : ?>
		<?php $confirm_text = $order->get_confirm_order_received_text(); ?>

		<?php if ( false !== $confirm_text ) : ?>
			<p class="confirm-order-received-text"><?php echo esc_html( $confirm_text ); ?></p>
		<?php endif; ?>

		<?php do_action( 'learn-press/before-confirm-order-details', $order->get_id() ); ?>

		<ul class="order_details">
			<li class="order">
				<?php esc_html_e( 'Order Number:', 'learnpress' ); ?>
				<strong><?php echo esc_html( $order->get_order_number() ); ?></strong>
			</li>

			<li class="date">
				<?php esc_html_e( 'Date:', 'learnpress' ); ?>
				<strong><?php echo date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ); ?></strong>
			</li>

			<li class="total">
				<?php esc_html_e( 'Total:', 'learnpress' ); ?>
				<strong><?php echo esc_html( $order->get_formatted_order_total() ); ?></strong>
			</li>

			<?php $payment_method_title = $order->get_payment_method_title(); ?>
			<?php if ( $payment_method_title ) : ?>
				<li class="method">
					<?php esc_html_e( 'Payment Method:', 'learnpress' ); ?>
					<strong><?php echo wp_kses_post( $payment_method_title ); ?></strong>
				</li>
			<?php endif; ?>

			<li class="status">
				<?php esc_html_e( 'Status:', 'learnpress' ); ?>
				<strong><?php echo esc_html( $order->get_status() ); ?></strong>
			</li>
		</ul>

		<?php do_action( 'learn-press/after-confirm-order-details', $order->get_id() ); ?>
	<?php endif; ?>

	<?php do_action( 'learn_press_confirm_order' . $order->transaction_method, $order->get_id() ); ?>
	<?php do_action( 'learn_press_confirm_order', $order->get_id() ); ?>

<?php } else { ?>

	<p><?php echo esc_html( $order->get_thankyou_message() ); ?></p>

<?php } ?>
