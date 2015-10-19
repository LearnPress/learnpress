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

$payment_heading = apply_filters( 'learn_press_checkout_payment_heading', __( 'Payment Method', 'learn_press' ) );
$order_button_text = apply_filters( 'learn_press_order_button_text', __( 'Place order', 'learn_press' ) );
?>

<?php if ( $payment_heading ) { ?>
	<h3><?php echo $payment_heading; ?></h3>
<?php } ?>

<div id="learn-press-payment" class="learn-press-checkout-payment">
	<ul class="payment-methods">
		<?php
		if ( !empty( $available_gateways ) ) {
			foreach ( $available_gateways as $gateway ) {
				learn_press_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
			}
		} else {
			$no_gateways_message = __( 'No available payment methods.', 'learn_press' );

			echo '<li>' . apply_filters( 'learn_press_no_available_payment_methods_message', $no_gateways_message ) . '</li>';
		}
		?>
	</ul>

	<div class="place-order-action">

	<?php do_action( 'learn_press_order_before_submit' ); ?>

	<?php echo apply_filters( 'learn_press_order_button_html', '<input type="submit" class="button alt" name="learn_press_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '" />' ); ?>

	<?php do_action( 'learn_press_order_after_submit' ); ?>

	</div>
</div>