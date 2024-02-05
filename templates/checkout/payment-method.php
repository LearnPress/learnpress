<?php
/**
 * Template for displaying single method payment in checkout form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/payment-method.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $gateway ) ) {
	return;
}

$icon = $gateway->get_icon();
?>

<li id="learn-press-payment-method-<?php echo esc_attr( $gateway->id ); ?>"
	class="lp-payment-method lp-payment-method-<?php echo esc_attr( $gateway->id ); ?><?php echo esc_html( $gateway->is_selected ? ' selected' : '' ); ?>">
	<label for="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
		<input type="radio" class="gateway-input" name="payment_method"
				id="payment_method_<?php echo esc_attr( $gateway->id ); ?>"
				value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->is_selected, true ); ?>
				data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>"/>
		<?php echo wp_kses_post( ! empty( $icon ) ? $icon : $gateway->get_title() ); ?>
	</label>

	<?php
	$payment_form = $gateway->get_payment_form();
	$style        = $gateway->is_selected ? 'display: block' : 'display: none';
	if ( $payment_form ) :
		?>
		<div class="payment-method-form payment_method_<?php echo esc_attr( $gateway->id ); ?>"
			style="<?php echo esc_attr( $style ); ?>">
			<?php learn_press_echo_vuejs_write_on_php( $payment_form ); ?>
		</div>
	<?php endif; ?>
</li>
