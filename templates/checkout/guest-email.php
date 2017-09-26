<?php
/**
 * Template for displaying user email field which enable checkout as guest.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or exit();

?>
<div id="checkout-guest-email">
    <h4 class="form-heading"><?php _e( 'Your email', 'learnpress' ); ?></h4>
    <p class="form-desc"><?php _e( 'Your real email we will send order code.', 'learnpress' ); ?></p>
    <input type="email" value="<?php echo LP()->session->get('checkout-email'); ?>" name="checkout-email"/>

    <input type="hidden" name="guest-checkout" value="<?php echo wp_create_nonce( 'guest-checkout' ); ?>">
</div>