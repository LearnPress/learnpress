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

<?php if ( $gateway instanceof LP_Gateway_Abstract ) : ?>

	<?php if ( apply_filters( 'learn_press_display_payment_method', true, $gateway->id ) ) { ?>
		<li>
			<label>
				<input id="payment_method_<?php echo $gateway->id; ?>" type="radio" class="input-radio" <?php checked( $selected, $gateway->id, true ); ?> name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( LP()->session->get( 'chosen_payment_method' ) == $gateway->id, true ); ?> data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />
				<?php echo( $gateway->get_title() ); ?>
			</label>
			<?php if ( ( $payment_form = $gateway->get_payment_form() ) || ( $payment_form = $gateway->get_description() ) ) { ?>
				<div class="payment-method-form payment_method_<?php echo $gateway->id; ?>"><?php echo $payment_form; ?></div>
			<?php } ?>
		</li>
	<?php } ?>

<?php else: ?>

	<?php do_action( 'learn_press_display_payment_method_form', $gateway ); ?>

<?php endif; ?>
