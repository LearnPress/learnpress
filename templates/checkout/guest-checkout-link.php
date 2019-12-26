<?php
/**
 * Template for displaying link to show form for Guest checkout.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;

if ( ! LP()->checkout()->is_enable_guest_checkout() ) {
	return;
}

esc_html_e( 'Or quick checkout as', 'learnpress' ); ?>
<a href="javascript: void(0);">
    <label for="checkout-account-switch-to-guest"><?php echo _x( 'guest', 'checkout guest link', 'learnpress' ); ?></label>
</a>.