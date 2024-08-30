<?php
/**
 * Template for displaying paypal settings of setup wizard.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;

$settings = LP_Settings::instance();

?>
<table>
	<tr>
		<th><?php _e( 'Paypal Email', 'learnpress' ); ?></th>
		<td>
			<input class="regular-text" type="email" name="settings[paypal][paypal_email]" id="settings-paypal-email"
				value="<?php echo esc_attr( $settings->get( 'paypal.paypal_email', '' ) ); ?>">
			<p class="description">
				<?php _e( 'Your Paypal email is in live mode.', 'learnpress' ); ?>
			</p>
			<input type="hidden" name="settings[paypal][enable]" value="yes"/>
		</td>
	</tr>

	<tr>
		<th><?php _e( 'Currency', 'learnpress' ); ?></th>
		<td>
			<select id="currency" name="settings[currency][currency]" class="lp-tom-select">
				<?php
				$payment_currencies = learn_press_currencies();

				if ( $payment_currencies ) {
					foreach ( $payment_currencies as $code => $symbol ) {
						?>
						<option value="<?php echo esc_attr( $code ); ?>" data-symbol="<?php echo learn_press_get_currency_symbol( $code ); ?>" <?php selected( $code == 'USD' ); ?>><?php echo esc_html( $symbol ); ?></option>
						<?php
					}
				}
				?>
			</select>
		</td>
	</tr>
</table>

<input type="hidden" name="settings[currency][currency_pos]" value="left"/>
<input type="hidden" name="settings[currency][thousands_separator]" value=","/>
<input type="hidden" name="settings[currency][decimals_separator]" value="."/>
<input type="hidden" name="settings[currency][number_of_decimals]" value="2"/>

<script>
	jQuery(function ($) {
		$('#settings-paypal-email').focus();
	})
</script>
