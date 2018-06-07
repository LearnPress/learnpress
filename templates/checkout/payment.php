<?php
/**
 * Template for displaying payment form for checkout page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/payment.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
$order_button_text            = apply_filters( 'learn_press_order_button_text', __( 'Place order', 'learnpress' ) );
$order_button_text_processing = apply_filters( 'learn_press_order_button_text_processing', __( 'Processing', 'learnpress' ) );
$show_button                  = true;
$available_gateways           = ! empty( $available_gateways ) ? $available_gateways : false;
$count_gateways               = $available_gateways ? sizeof( $available_gateways ) : 0;
?>

<div id="learn-press-payment" class="learn-press-checkout-payment">

	<?php if ( LP()->cart->needs_payment() ) { ?>

		<?php if ( ! $count_gateways ) { ?>

			<?php $show_button = false; ?>

			<?php if ( $message = apply_filters( 'learn_press_no_available_payment_methods_message', __( 'No payment method is available.', 'learnpress' ) ) ) { ?>
				<?php learn_press_display_message( $message, 'error' ); ?>
			<?php } ?>

		<?php } else { ?>

            <h4><?php _e( 'Payment Method', 'learnpress' ); ?></h4>

			<?php do_action( 'learn-press/before-payment-methods' ); ?>

            <ul class="payment-methods">

				<?php
				/**
				 * @deprecated
				 */
				do_action( 'learn_press_before_payments' );

				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/begin-payment-methods' );
				?>

				<?php $order = 1;
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
				} ?>

				<?php
				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/end-payment-methods' );

				/**
				 * @deprecated
				 */
				do_action( 'learn_press_after_payments' );
				?>

            </ul>

			<?php do_action( 'learn-press/after-payment-methods' ); ?>

		<?php } ?>

	<?php } ?>

	<?php do_action( 'learn-press/payment-form' ); ?>

	<?php if ( $show_button ) { ?>

        <div id="checkout-order-action" class="place-order-action">

			<?php
			// @deprecated
			do_action( 'learn_press_order_before_submit' );

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/before-checkout-submit-button' );
			?>

			<?php echo apply_filters( 'learn_press_order_button_html',
				sprintf(
					'<button type="submit" class="lp-button button alt" name="learn_press_checkout_place_order" id="learn-press-checkout-place-order" data-processing-text="%s" data-value="%s">%s</button>',
					esc_attr( $order_button_text_processing ),
					esc_attr( $order_button_text ),
					esc_attr( $order_button_text )
				)
			);
			?>

			<?php
			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/after-checkout-submit-button' );

			// @deprecated
			do_action( 'learn_press_order_after_submit' );
			?>

			<?php if ( ! is_user_logged_in() ) { ?>
                <button type="button" class="lp-button lp-button-guest-checkout"
                        id="learn-press-button-guest-checkout-back"><?php _e( 'Back', 'learnpress' ); ?></label></button>
			<?php } ?>

        </div>

	<?php } ?>

</div>