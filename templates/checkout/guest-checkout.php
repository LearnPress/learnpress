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

$checkout  = LP()->checkout();
$is_exists = $checkout->checkout_email_exists();
?>

<input type="radio" id="checkout-account-switch-to-guest" name="checkout-account-switch-form" value="guest"/>
<div id="checkout-account-guest" class="lp-checkout-block<?php echo $is_exists ? ' email-exists' : ''; ?>">
    <h4><?php esc_html_e( 'Your email', 'learnpress' ); ?></h4>
    <ul class="lp-form-fields">
        <li class="form-field">
            <div class="rwmb-field rwmb-text-wrapper">
                <div class="rwmb-input">
                    <input size="30" placeholder="Email" type="text" id="guest_email" class="rwmb-text"
                           name="guest_email" autocomplete="off">
                    <p class="description">
		                <?php esc_html_e( 'Your real email we will send the order code.', 'learnpress' ); ?>
                    </p>
                    <div class="lp-guest-checkout-switch-back">
<!--                        <label for="checkout-account-switch-to-login">-->
<!--                            <a href="javascript:void(0)">--><?php //esc_html_e( 'Sign in', 'learnpress' ); ?><!--</a>-->
<!--                        </label> -->
                        <label for="checkout-account-switch-to-register">
                            <a href="javascript:void(0)"><?php esc_html_e( 'Sign up', 'learnpress' ); ?></a>
                        </label>
                    </div>
                </div>
            </div>
        </li>
    </ul>
    <input type="hidden" name="learn-press-checkout-nonce"
           value="<?php echo esc_attr( wp_create_nonce( 'learn-press-guest-checkout' ) ); ?>"/>


    <!--    <h4 class="form-heading">--><?php //_e( 'Your email', 'learnpress' ); ?><!--</h4>-->
    <!--    <p class="form-desc">-->
	<?php //_e( 'Your real email we will send the order code.', 'learnpress' ); ?><!--</p>-->
    <!--    <input type="email" value="-->
	<?php //echo $checkout->get_checkout_email(); ?><!--" name="checkout-email"/>-->
    <!---->
    <!--    <input type="hidden" name="guest-checkout" value="-->
	<?php //echo wp_create_nonce( 'guest-checkout' ); ?><!--">-->
    <!--    <ul id="checkout-guest-options">-->
    <!---->
    <!--        <li id="checkout-existing-account">-->
    <!--            <label>-->
    <!--                <input type="checkbox" name="checkout-email-option"-->
    <!--                       value="existing-account"-->
	<?php //checked( $checkout->get_checkout_email() == $checkout->get_user_waiting_payment(), true ); ?>
    <!--				--><?php //_e( 'Your email is already exists. Checkout as this account?', 'learnpress' ); ?>
    <!--            </label>-->
    <!--        </li>-->
    <!---->
    <!--        <li id="checkout-new-account">-->
    <!--            <label>-->
    <!--                <input type="checkbox" name="checkout-email-option" value="new-account">-->
    <!--				--><?php //_e( 'Create new account with this email? Account information will be sent to this email.', 'learnpress' ); ?>
    <!--            </label>-->
    <!--        </li>-->
    <!---->
    <!--    </ul>-->
</div>
<label for="checkout-account-switch-to-guest"
       class="lp-button"
       id="btn-checkout-account-switch-to-guest"><?php esc_html_e( 'Continue as Guest', 'learnpress' ); ?></label>

