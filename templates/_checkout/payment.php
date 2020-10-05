<?php
/**
 * Template for displaying payment form for checkout page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/payment.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$order_button_text            = apply_filters( 'learn_press_order_button_text', __( 'Place order', 'learnpress' ) );
$order_button_text_processing = apply_filters( 'learn_press_order_button_text_processing', __( 'Processing', 'learnpress' ) );
$show_button                  = true;
$available_gateways           = ! empty( $available_gateways ) ? $available_gateways : false;
$count_gateways               = $available_gateways ? sizeof( $available_gateways ) : 0;
?>

<div id="learn-press-payment" class="learn-press-checkout-payment">

	<?php
	if ( LP()->cart->needs_payment() ) {
		if ( ! $count_gateways ) {
			$show_button = false;
			$message     = apply_filters( 'learn_press_no_available_payment_methods_message', esc_html__( 'No payment method is available.', 'learnpress' ) );

			if ( $message ) {
				learn_press_display_message( $message, 'error' );
			}
		} else {
			?>
			<h4><?php esc_html_e( 'Payment Method', 'learnpress' ); ?></h4>

			<?php do_action( 'learn-press/before-payment-methods' ); ?>

			<ul class="payment-methods">
				<?php do_action( 'learn-press/begin-payment-methods' ); ?>

				<?php
				$order = 1;
				foreach ( $available_gateways as $gateway ) {
					if ( $order == 1 ) {
						learn_press_get_template(
							'checkout/payment-method.php',
							array(
								'gateway'  => $gateway,
								'selected' => $gateway->id,
							)
						);
					} else {
						learn_press_get_template(
							'checkout/payment-method.php',
							array(
								'gateway'  => $gateway,
								'selected' => '',
							)
						);
					}
					$order ++;
				}
				?>

				<?php do_action( 'learn-press/end-payment-methods' ); ?>
			</ul>

			<?php do_action( 'learn-press/after-payment-methods' ); ?>

		<?php } ?>

	<?php } ?>

	<?php do_action( 'learn-press/payment-form' ); ?>

	<?php if ( $show_button ) { ?>
		<div id="checkout-order-action" class="place-order-action">

			<?php do_action( 'learn-press/before-checkout-submit-button' ); ?>

			<?php
			echo apply_filters(
				'learn_press_order_button_html',
				sprintf(
					'<button type="submit" class="lp-button button alt" name="learn_press_checkout_place_order" id="learn-press-checkout-place-order" data-processing-text="%s" data-value="%s">%s</button>',
					esc_attr( $order_button_text_processing ),
					esc_attr( $order_button_text ),
					esc_attr( $order_button_text )
				)
			);
			?>

			<?php do_action( 'learn-press/after-checkout-submit-button' ); ?>

			<?php if ( ! is_user_logged_in() ) { ?>
				<button type="button" class="lp-button lp-button-guest-checkout" id="learn-press-button-guest-checkout-back"><?php esc_html_e( 'Back', 'learnpress' ); ?></label></button>
			<?php } ?>
		</div>
	<?php } ?>
</div>
