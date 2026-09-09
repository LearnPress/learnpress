<?php
/**
 * Template for displaying payment and currency settings in the setup wizard.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.3.2
 */

use LearnPress\TemplateHooks\Admin\AdminTemplate;

defined( 'ABSPATH' ) || exit;

$settings         = LP_Settings::instance();
$currency         = $settings->get( 'currency', 'USD' );
$currency_presets = array(
	'USD' => array( 'flag' => '🇺🇸', 'label' => 'USD ($)' ),
	'VND' => array( 'flag' => '🇻🇳', 'label' => 'VND (₫)' ),
	'EUR' => array( 'flag' => '🇪🇺', 'label' => 'EUR (€)' ),
	'JPY' => array( 'flag' => '🇯🇵', 'label' => 'JPY (¥)' ),
);
$paypal_settings_url = admin_url( 'admin.php?page=learn-press-settings&tab=payments&section=paypal' );
$offline_enabled     = 'yes' === $settings->get( 'offline-payment.enable', 'yes' );
$paypal_enabled      = 'yes' === $settings->get( 'paypal.enable', 'no' );
?>
<section class="lp-setup-payment">
	<header class="lp-setup-section-header">
		<h2><?php esc_html_e( 'Step 3: Payment & Currency', 'learnpress' ); ?></h2>
		<p><?php esc_html_e( 'Choose your currency and set up payment options.', 'learnpress' ); ?></p>
	</header>

	<section class="lp-setup-payment__section">
		<h3><?php esc_html_e( 'Currency Settings', 'learnpress' ); ?></h3>
		<div class="lp-setup-currency-panel lp-setup-card">
			<div class="lp-setup-currency-presets">
				<span class="lp-setup-currency-presets__label"><?php esc_html_e( 'Quick Pick preset :', 'learnpress' ); ?></span>
				<?php foreach ( $currency_presets as $preset_code => $preset ) { ?>
					<button type="button" class="button lp-setup-currency-preset<?php echo $currency === $preset_code ? ' is-active' : ''; ?>" data-currency-preset="<?php echo esc_attr( $preset_code ); ?>">
						<span aria-hidden="true"><?php echo esc_html( $preset['flag'] ); ?></span>
						<?php echo esc_html( $preset['label'] ); ?>
					</button>
				<?php } ?>
			</div>

			<div class="lp-setup-currency-fields">
				<label class="lp-setup-payment-field" for="currency">
					<span><?php esc_html_e( 'Currency:', 'learnpress' ); ?></span>
					<select id="currency" name="settings[currency][currency]">
						<?php foreach ( learn_press_currencies() as $code => $name ) { ?>
							<option value="<?php echo esc_attr( $code ); ?>"<?php selected( $currency, $code ); ?>><?php echo esc_html( $name ); ?></option>
						<?php } ?>
					</select>
				</label>

				<label class="lp-setup-payment-field" for="currency-pos">
					<span><?php esc_html_e( 'Currency Position:', 'learnpress' ); ?></span>
					<select id="currency-pos" name="settings[currency][currency_pos]">
						<?php foreach ( learn_press_currency_positions() as $position => $label ) { ?>
							<option value="<?php echo esc_attr( $position ); ?>"<?php selected( $settings->get( 'currency_pos', 'left' ), $position ); ?>><?php echo esc_html( $label ); ?></option>
						<?php } ?>
					</select>
				</label>

				<label class="lp-setup-payment-field" for="thousands-separator">
					<span><?php esc_html_e( 'Thousands Separator:', 'learnpress' ); ?></span>
					<input id="thousands-separator" type="text" name="settings[currency][thousands_separator]" value="<?php echo esc_attr( $settings->get( 'thousands_separator', ',' ) ); ?>">
				</label>

				<label class="lp-setup-payment-field" for="decimals-separator">
					<span><?php esc_html_e( 'Decimal Separator:', 'learnpress' ); ?></span>
					<input id="decimals-separator" type="text" name="settings[currency][decimals_separator]" value="<?php echo esc_attr( $settings->get( 'decimals_separator', '.' ) ); ?>">
				</label>

				<label class="lp-setup-payment-field" for="number-of-decimals">
					<span><?php esc_html_e( 'Number of Decimals:', 'learnpress' ); ?></span>
					<input id="number-of-decimals" type="number" min="0" max="8" name="settings[currency][number_of_decimals]" value="<?php echo esc_attr( $settings->get( 'number_of_decimals', '2' ) ); ?>">
				</label>
			</div>
		</div>
	</section>

	<section class="lp-setup-payment__section lp-setup-payment__section--gateways">
		<h3><?php esc_html_e( 'Payment Gateways', 'learnpress' ); ?></h3>
		<article class="lp-setup-gateway-card lp-setup-card">
			<div class="lp-setup-gateway-card__icon lp-setup-gateway-card__icon--offline" aria-hidden="true"><span class="lp-icon-money-bill-alt"></span></div>
			<div class="lp-setup-gateway-card__content">
				<strong><?php esc_html_e( 'Offline / Manual Payment', 'learnpress' ); ?></strong>
				<span><?php esc_html_e( 'Allow students to pay via bank transfer or cash.', 'learnpress' ); ?></span>
			</div>
			<span class="screen-reader-text"><?php esc_html_e( 'Enable Offline / Manual Payment', 'learnpress' ); ?></span>
			<?php echo AdminTemplate::html_toggle_enable( array( 'name' => 'settings[offline-payment][enable]', 'value' => $offline_enabled, 'classes' => 'lp-setup-gateway-toggle' ) ); ?>
		</article>

		<article class="lp-setup-gateway-card lp-setup-gateway-card--paypal lp-setup-card">
			<div class="lp-setup-gateway-card__icon lp-setup-gateway-card__icon--paypal" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9.2 4h6.1c3 0 4.8 1.7 4.3 4.5-.6 3.3-3 5.1-6.4 5.1h-1.7L10.7 18H6.5z"/><path d="M7.2 7h6.1c3 0 4.8 1.7 4.3 4.5-.6 3.3-3 5.1-6.4 5.1H9.5L8.7 21H4.5z"/></svg></div>
			<div class="lp-setup-gateway-card__content">
				<strong><?php esc_html_e( 'PayPal Standard', 'learnpress' ); ?></strong>
				<span><?php esc_html_e( 'Accept credit cards and PayPal balance online worldwide.', 'learnpress' ); ?></span>
				<a class="button button-primary lp-setup-connect-paypal" href="<?php echo esc_url( $paypal_settings_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Connect with PayPal', 'learnpress' ); ?></a>
			</div>
			<span class="screen-reader-text"><?php esc_html_e( 'Enable PayPal Standard', 'learnpress' ); ?></span>
			<?php echo AdminTemplate::html_toggle_enable( array( 'name' => 'settings[paypal][enable]', 'value' => $paypal_enabled, 'classes' => 'lp-setup-gateway-toggle' ) ); ?>
		</article>
	</section>
</section>
