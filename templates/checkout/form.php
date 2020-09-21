<?php
/**
 * Template for displaying checkout form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/form.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
learn_press_print_messages();

if ( ! isset( $checkout ) ) {
	return;
}

/**
 * @since 3.0.0
 *
 * @see   learn_press_checkout_form_login()
 * @see   learn_press_checkout_form_register()
 */
do_action( 'learn-press/before-checkout-form' );
?>


	<form method="post" id="learn-press-checkout" name="learn-press-checkout"
		  class="learn-press-checkout checkout<?php echo ! is_user_logged_in() ? " guest-checkout" : ""; ?>"
		  action="<?php echo esc_url( learn_press_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<?php
		// @since 3.0.0
		do_action( 'learn-press/before-checkout-order-review' );
		?>

		<div id="learn-press-order-review" class="checkout-review-order">

			<?php
			/**
			 * @deprecated
			 */
			do_action( 'learn_press_checkout_order_review' );

			/**
			 * @since 3.0.0
			 *
			 * @see   learn_press_order_review()
			 * @see   learn_press_order_comment()
			 * @see   learn_press_order_payment()
			 */
			do_action( 'learn-press/checkout-order-review' );
			?>

		</div>

		<?php
		// @since 3.0.0
		do_action( 'learn-press/after-checkout-order-review' );
		?>
	</form>

<?php if ( ! is_user_logged_in() && ! LP()->checkout()->is_enable_login() && ! LP()->checkout()->is_enable_register() ) { ?>
	<p><?php printf( __( 'Please login to continue checkout. %s', 'learnpress' ), sprintf( '<a href="%s">%s</a>', learn_press_get_login_url(), __( 'Login?', 'learnpress' ) ) ); ?></p>
<?php } ?>

<?php if ( ! is_user_logged_in() && LP()->checkout()->is_enable_guest_checkout() ) { ?>

	<p class="button-continue-guest-checkout">
		<button type="button" class="lp-button lp-button-guest-checkout"
				id="learn-press-button-guest-checkout"><?php _e( 'Continue checkout as Guest?', 'learnpress' ); ?></label></button>
	</p>
<?php } ?>

<?php

// @since 3.0.0
do_action( 'learn-press/after-checkout-form' );