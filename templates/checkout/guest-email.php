<?php
/**
 * Template for displaying user email field which enable checkout as guest.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/guest-email.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
if ( ! isset( $checkout ) || ! isset( $is_exists ) ) {
	return;
}
?>

<div id="checkout-guest-email" class="<?php echo $is_exists ? ' email-exists' : ''; ?>">
	<h4 class="form-heading"><?php _e( 'Your email', 'learnpress' ); ?></h4>
	<p class="form-desc"><?php _e( 'Your real email we will send the order code.', 'learnpress' ); ?></p>
	<input type="email" value="<?php echo $checkout->get_checkout_email(); ?>" name="checkout-email"/>

	<input type="hidden" name="guest-checkout" value="<?php echo wp_create_nonce( 'guest-checkout' ); ?>">
	<ul id="checkout-guest-options">

		<li id="checkout-existing-account">
			<label>
				<input type="checkbox" name="checkout-email-option"
					   value="existing-account"<?php checked( $checkout->get_checkout_email() == $checkout->get_user_waiting_payment(), true ); ?>>
				<?php _e( 'Your email is already exists. Checkout as this account?', 'learnpress' ); ?>
			</label>
		</li>

		<li id="checkout-new-account">
			<label>
				<input type="checkbox" name="checkout-email-option" value="new-account">
				<?php _e( 'Create new account with this email? Account information will be sent to this email.', 'learnpress' ); ?>
			</label>
		</li>

	</ul>
</div>