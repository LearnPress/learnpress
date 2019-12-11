<?php
/**
 * Template for displaying single method payment in checkout form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/payment-method.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $gateway ) || ! $gateway->is_display() ) {
	return;
}

?>
<li class="lp-payment-method lp-payment-method-<?php echo $gateway->id; ?><?php echo $gateway->is_selected ? ' selected' : ''; ?>"
    id="learn-press-payment-method-<?php echo $gateway->id; ?>">
    <label for="payment_method_<?php echo $gateway->id; ?>">
        <input type="radio" class="input-radio" name="payment_method"
               id="payment_method_<?php echo $gateway->id; ?>"
               value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->is_selected, true ); ?>
               data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>"/>
		<?php echo $gateway->get_title(); ?>
		<?php echo $gateway->get_icon(); ?>
    </label>

	<?php if ( ( $payment_form = $gateway->get_payment_form() ) || ( $payment_form = $gateway->get_description() ) ) { ?>
        <div class="payment-method-form payment_method_<?php echo $gateway->id; ?>">
			<?php echo $payment_form; ?>
        </div>
	<?php } ?>
</li>
