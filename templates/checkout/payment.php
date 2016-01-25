<?php
/**
 * Checkout Payment Section
 *
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

$payment_heading              = apply_filters( 'learn_press_checkout_payment_heading', __( 'Payment Method', 'learn_press' ) );
$order_button_text            = apply_filters( 'learn_press_order_button_text', __( 'Place order', 'learn_press' ) );
$order_button_text_processing = apply_filters( 'learn_press_order_button_text_processing', __( 'Processing', 'learn_press' ) );
?>

<?php if ( $payment_heading ) { ?>
	<h3><?php echo $payment_heading; ?></h3>
<?php } ?>
<?php

?>
<div id="learn-press-payment" class="learn-press-checkout-payment">

	<?php if ( !empty( $available_gateways ) ) { ?>

		<ul class="payment-methods">

			<?php do_action( 'learn_press_before_payments' ); ?>

			<?php foreach ( $available_gateways as $gateway ) { ?>

				<?php learn_press_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) ); ?>

			<?php } ?>

			<?php do_action( 'learn_press_after_payments' ); ?>

		</ul>

		<div class="place-order-action">

			<?php do_action( 'learn_press_order_before_submit' ); ?>

			<?php echo apply_filters( 'learn_press_order_button_html', '<input type="submit" class="button alt" name="learn_press_checkout_place_order" id="place_order" data-processing-text="' . esc_attr( $order_button_text_processing ) . '" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '" />' ); ?>

			<?php do_action( 'learn_press_order_after_submit' ); ?>

		</div>

	<?php } else { ?>
		<?php if ( $message = apply_filters( 'learn_press_no_available_payment_methods_message', __( 'No payment methods is available.', 'learn_press' ) ) ) { ?>
			<?php learn_press_display_message( $message, 'error' ); ?>
		<?php } ?>
	<?php } ?>

</div>