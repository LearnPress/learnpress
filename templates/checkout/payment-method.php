<?php
/**
 * Display single method payment in checkout form.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $gateway ) ) {
	return;
}
?>

<li id="learn-press-payment-method-<?php echo $gateway->id;?>" class="lp-payment-method lp-payment-method-<?php echo $gateway->id;?><?php echo $gateway->is_selected ? ' selected' : '';?>">

	<?php

	$display = apply_filters( 'learn-press/display-payment-method', true, $gateway->id );

	// @deprecated
	$display = apply_filters( 'learn_press_display_payment_method', $display, $gateway->id );

	if ( $display ) {
		?>
        <input id="payment_method_<?php echo $gateway->id; ?>"
               type="radio"
               class="input-radio"
               name="payment_method"
               value="<?php echo esc_attr( $gateway->id ); ?>"
			<?php checked( $gateway->is_selected, true ); ?>
               data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>"/>

        <label for="payment_method_<?php echo $gateway->id; ?>">
			<?php echo $gateway->get_title(); ?>
			<?php echo $gateway->get_icon(); ?>
        </label>

		<?php if ( ( $payment_form = $gateway->get_payment_form() ) || ( $payment_form = $gateway->get_description() ) ) { ?>

            <div class="payment-method-form payment_method_<?php echo $gateway->id; ?>">
				<?php echo $payment_form; ?>
            </div>

		<?php }
	}

	?>

</li>
