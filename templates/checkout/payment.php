<?php
/**
 * Template for displaying payment form for checkout page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/payment.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$order_button_text  = learn_press_get_checkout_proceed_button_text();
$available_gateways = LP_Gateways::instance()->get_available_payment_gateways();
?>
<div id="checkout-payment" class="lp-checkout-block left">

	<?php

	/**
	 * LP Hook
	 */
	do_action( 'learn-press/before-payment-methods' );

	// Show payments if cart total > 0 and have at least one payment method.
	if ( LP()->cart->needs_payment() && $available_gateways ) { ?>

        <h4>
			<?php _e( 'Payment', 'learnpress' ); ?>
            <span class="secure-connection">
                <i class="fas fa-lock"></i>
	            <?php echo _x( 'Secure Connection', 'payment method', 'learnpress' ); ?>
            </span>
        </h4>

        <ul class="payment-methods">
			<?php
			$order = 1;
			foreach ( $available_gateways as $gateway ) {
				if ( $order == 1 ) {
					learn_press_get_template( 'checkout/payment-method.php', array(
						'gateway'  => $gateway,
						'selected' => $gateway->id
					) );
				} else {
					learn_press_get_template( 'checkout/payment-method.php', array(
						'gateway'  => $gateway,
						'selected' => ''
					) );
				}
				$order ++;
			}
			?>

        </ul>

		<?php
	} // End if

	/**
	 * LP Hook
	 */
	do_action( 'learn-press/payment-form' );

	?>

    <div id="checkout-order-action" class="place-order-action">

		<?php
		/**
		 * @since 3.0.0
		 */
		do_action( 'learn-press/before-checkout-submit-button' );

		// Allow to custom html of 'Process' button, default false.
		if ( $button_proceed = apply_filters( 'learn-press/checkout-proceed-button-html', false ) ) {
			echo $button_proceed;
		} else {
			?>
            <button type="submit" class="lp-button button alt" name="learn_press_checkout_place_order"
                    id="learn-press-checkout-place-order"><?php echo $order_button_text; ?>
            </button>
			<?php
		}

		if ( is_user_logged_in() ) {
			wp_nonce_field( 'learn-press-user-logged', 'learn-press-checkout-nonce' );
		}

		/**
		 * @since 3.0.0
		 */
		do_action( 'learn-press/after-checkout-submit-button' );

		?>

    </div>

	<?php
	/**
	 * LP Hook
	 */
	do_action( 'learn-press/after-payment-form' );
	?>

</div>