<?php
/**
 * Checkout Payment Section
 *
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

?>
<li>
	<label>
		<input id="payment_method_<?php echo $gateway->id; ?>" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( LP()->session->get('chosen_payment_method') == $gateway->id, true ); ?> data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />
		<?php echo( $gateway->get_title() ); ?>
	</label>
	<?php if ( $payment_form = $gateway->get_payment_form() ) { ?>
		<div class="payment-method-form payment_method_<?php echo $gateway->id; ?>"><?php echo $payment_form; ?></div>
	<?php } ?>
</li>
