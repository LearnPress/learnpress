<?php
/**
 * Template for displaying user email field which enable checkout as guest.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or exit();

$checkout  = LP()->checkout();
$is_exists = $checkout->checkout_email_exists();
?>
<div id="checkout-guest-email" class="<?php echo $is_exists ? ' email-exists' : ''; ?>">
    <h4 class="form-heading"><?php _e( 'Your email', 'learnpress' ); ?></h4>
    <p class="form-desc"><?php _e( 'Your real email we will send order code.', 'learnpress' ); ?></p>
    <input type="email" value="<?php echo $checkout->get_checkout_email(); ?>" name="checkout-email"/>

    <input type="hidden" name="guest-checkout" value="<?php echo wp_create_nonce( 'guest-checkout' ); ?>">
    <ul id="checkout-guest-options">

        <li id="checkout-existing-account">
            <label>
                <input type="checkbox" name="checkout-email-option" value="existing-account">
				<?php _e( 'Your email is already exists. Checkout as this account?', 'learnpress' ); ?>
            </label>
        </li>

        <li id="checkout-new-account">
            <label>
                <input type="checkbox" name="checkout-email-option" value="new-account">
				<?php _e( 'Create new account with this email?', 'learnpress' ); ?>
            </label>
            <p><?php _e( 'We will send you the account information to this email.', 'learnpress' ); ?></p>
        </li>

    </ul>
</div>