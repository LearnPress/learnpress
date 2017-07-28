<?php
/**
 * Template for displaying checkout form
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

learn_press_print_notices();

$checkout = LP()->checkout();

/**
 * @deprecated
 */
do_action( 'learn_press_before_checkout_form', $checkout );

/**
 * @since 3.x.x
 *
 * @see   learn_press_checkout_form_login()
 * @see   learn_press_checkout_form_register()
 */
do_action( 'learn-press/before-checkout-form' );

// Guest checkout is disabled
if ( ! $checkout->is_enable_guest_checkout() && ! is_user_logged_in() ) {
	echo apply_filters( 'learn-press/checkout-require-login-message', __( 'Please login to checkout.', 'learnpress' ) );
} else {
	?>
    <p><label><input type="checkbox" ><?php _e('Continue checkout as Guest?', 'learnpress');?></label></p>
    <form method="post" id="learn-press-checkout" name="learn-press-checkout" class="learn-press-checkout checkout"
          action="<?php echo esc_url( learn_press_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<?php

		/**
		 * @deprecated
		 */
		do_action( 'learn_press_checkout_before_order_review' );

		// @since 3.x.x
		do_action( 'learn-press/before-checkout-order-review' );
		?>

        <div id="order_review" class="learn-press-checkout-review-order">

			<?php

			/**
			 * @deprecated
			 */
			do_action( 'learn_press_checkout_order_review' );

			// @since 3.x.x
			do_action( 'learn-press/checkout-order-review' );
			?>

        </div>

		<?php
		// @since 3.x.x
		do_action( 'learn-press/after-checkout-order-review' );

		/**
		 * @deprecated
		 */
		do_action( 'learn_press_checkout_after_order_review' );
		?>

    </form>

	<?php
}
// @since 3.x.x
do_action( 'learn-press/after-checkout-form' );

/**
 * @deprecated
 */
do_action( 'learn_press_after_checkout_form', $checkout );
?>