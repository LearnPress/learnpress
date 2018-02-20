<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();
?>
<?php if ( $order ) : ?>

	<?php if ( $order->has_status( 'failed' ) ) : ?>

		<p><?php _e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'learnpress' ); ?></p>

		<p><?php
			if ( is_user_logged_in() )
				_e( 'Please attempt your purchase again or go to your account page.', 'learnpress' );
			else
				_e( 'Please attempt your purchase again.', 'learnpress' );
			?></p>

		<p>
			<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php _e( 'Pay', 'learnpress' ) ?></a>
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( get__permalink( 'myaccount' ) ); ?>" class="button pay"><?php _e( 'My Account', 'learnpress' ); ?></a>
			<?php endif; ?>
		</p>

	<?php else : ?>

		<p><?php echo apply_filters( 'learn_press_confirm_order_received_text', __( 'Thank you. Your order has been received.', 'learnpress' ), $order ); ?></p>

		<ul class="order_details">
			<li class="order">
				<?php _e( 'Order Number:', 'learnpress' ); ?>
				<strong><?php echo $order->get_order_number(); ?></strong>
			</li>
			<li class="date">
				<?php _e( 'Date:', 'learnpress' ); ?>
				<strong><?php echo date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ); ?></strong>
			</li>
			<li class="total">
				<?php _e( 'Total:', 'learnpress' ); ?>
				<strong><?php echo $order->get_formatted_order_total(); ?></strong>
			</li>
			<?php if ( $payment_method_title = $order->get_payment_method_title() ) : ?>
				<li class="method">
					<?php _e( 'Payment Method:', 'learnpress' ); ?>
					<strong><?php echo $payment_method_title; ?></strong>
				</li>
			<?php endif; ?>
			<li class="status">
				<?php _e( 'Status:', 'learnpress' ); ?>
				<strong><?php echo $order->get_status(); ?></strong>
			</li>
		</ul>
		<div class="clear"></div>

	<?php endif; ?>

	<?php do_action( 'learn_press_confirm_order' . $order->transaction_method, $order->id ); ?>
	<?php do_action( 'learn_press_confirm_order', $order->id ); ?>

<?php else : ?>

	<p><?php echo apply_filters( 'learn_press_confirm_order_received_text', __( 'Thank you. Your order has been received.', 'learnpress' ), null ); ?></p>

<?php endif; ?>