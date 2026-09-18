<?php
/**
 * Template for displaying payment and currency settings in the setup wizard.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.3.2
 */

use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\Helpers\Config;

defined( 'ABSPATH' ) || exit;

$settings            = LP_Settings::instance();
$currency            = $settings->get( 'currency', 'USD' );
$currency_presets    = Config::instance()->get( 'currency-presets', 'setup-wizard' );
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
				<span class="lp-setup-currency-presets__label"><?php esc_html_e( 'Quick Pick Preset :', 'learnpress' ); ?></span>
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
			<div class="lp-setup-gateway-card__icon lp-setup-gateway-card__icon--offline" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none">
					<path d="M2.625 11.3749H5.25V18.3749H3.5C3.26794 18.3749 3.04538 18.4671 2.88128 18.6312C2.71719 18.7953 2.625 19.0179 2.625 19.2499C2.625 19.482 2.71719 19.7046 2.88128 19.8687C3.04538 20.0328 3.26794 20.1249 3.5 20.1249H24.5C24.7321 20.1249 24.9546 20.0328 25.1187 19.8687C25.2828 19.7046 25.375 19.482 25.375 19.2499C25.375 19.0179 25.2828 18.7953 25.1187 18.6312C24.9546 18.4671 24.7321 18.3749 24.5 18.3749H22.75V11.3749H25.375C25.5654 11.3748 25.7505 11.3125 25.9023 11.1976C26.0541 11.0826 26.1642 10.9213 26.2161 10.7381C26.2679 10.555 26.2586 10.3599 26.1895 10.1824C26.1204 10.005 25.9953 9.85501 25.8333 9.7551L14.4583 2.7551C14.3204 2.67035 14.1618 2.62549 14 2.62549C13.8382 2.62549 13.6796 2.67035 13.5417 2.7551L2.16672 9.7551C2.00466 9.85501 1.8796 10.005 1.81052 10.1824C1.74144 10.3599 1.7321 10.555 1.78393 10.7381C1.83576 10.9213 1.94592 11.0826 2.09771 11.1976C2.24949 11.3125 2.43462 11.3748 2.625 11.3749ZM7 11.3749H10.5V18.3749H7V11.3749ZM15.75 11.3749V18.3749H12.25V11.3749H15.75ZM21 18.3749H17.5V11.3749H21V18.3749ZM14 4.52698L22.2841 9.62494H5.71594L14 4.52698ZM27.125 22.7499C27.125 22.982 27.0328 23.2046 26.8687 23.3687C26.7046 23.5328 26.4821 23.6249 26.25 23.6249H1.75C1.51794 23.6249 1.29538 23.5328 1.13128 23.3687C0.967187 23.2046 0.875 22.982 0.875 22.7499C0.875 22.5179 0.967187 22.2953 1.13128 22.1312C1.29538 21.9671 1.51794 21.8749 1.75 21.8749H26.25C26.4821 21.8749 26.7046 21.9671 26.8687 22.1312C27.0328 22.2953 27.125 22.5179 27.125 22.7499Z" fill="currentColor"/>
				</svg>
			</div>
			<div class="lp-setup-gateway-card__content">
				<strong><?php esc_html_e( 'Offline / Manual Payment', 'learnpress' ); ?></strong>
				<span><?php esc_html_e( 'Allow students to pay via bank transfer or cash.', 'learnpress' ); ?></span>
			</div>
			<span class="screen-reader-text"><?php esc_html_e( 'Enable Offline / Manual Payment', 'learnpress' ); ?></span>
			<?php
			echo AdminTemplate::html_toggle_enable(
				array(
					'name' => 'settings[offline-payment][enable]',
					'value' => $offline_enabled,
					'classes' => 'lp-setup-gateway-toggle',
				)
			);
			?>
		</article>

		<article class="lp-setup-gateway-card lp-setup-gateway-card--paypal lp-setup-card">
			<div class="lp-setup-gateway-card__icon lp-setup-gateway-card__icon--paypal" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="23" height="28" viewBox="0 0 23 28" fill="none">
					<path d="M19.363 6.41882C19.4306 2.90231 16.5302 0.20459 12.5419 0.20459H4.29234C4.10009 0.204499 3.91414 0.273062 3.76796 0.397928C3.62179 0.522793 3.52501 0.695754 3.49505 0.885653L0.189676 21.5406C0.174888 21.6342 0.180559 21.7299 0.206299 21.8211C0.232039 21.9123 0.277237 21.9968 0.33878 22.0689C0.400324 22.1409 0.476751 22.1988 0.5628 22.2385C0.648848 22.2781 0.742474 22.2987 0.837232 22.2987H5.72454L4.96074 27.0813C4.94595 27.1749 4.95162 27.2706 4.97736 27.3618C5.0031 27.453 5.0483 27.5376 5.10984 27.6096C5.17139 27.6817 5.24781 27.7395 5.33386 27.7792C5.41991 27.8189 5.51354 27.8394 5.60829 27.8395H9.58965C9.78171 27.8395 9.95292 27.7706 10.0995 27.6461C10.2448 27.521 10.2688 27.3486 10.2991 27.1584L11.4679 20.2821C11.4976 20.0925 11.5942 19.8461 11.7402 19.721C11.8861 19.5959 12.0137 19.5277 12.2064 19.5277H14.6425C18.5487 19.5277 21.8623 16.751 22.4681 12.8896C22.8971 10.1489 21.722 7.65522 19.363 6.41882Z" fill="#001C64"/>
					<path d="M6.7759 14.6811L5.5585 22.4002L4.79407 27.2422C4.77937 27.3358 4.78513 27.4315 4.81096 27.5227C4.83678 27.6138 4.88205 27.6983 4.94365 27.7703C5.00525 27.8423 5.08173 27.9001 5.1678 27.9397C5.25387 27.9793 5.34751 27.9998 5.44226 27.9997H9.65611C9.84817 27.9996 10.0339 27.931 10.1798 27.8061C10.3258 27.6813 10.4223 27.5084 10.4521 27.3187L11.5628 20.2812C11.5927 20.0914 11.6894 19.9185 11.8354 19.7937C11.9815 19.6688 12.1673 19.6002 12.3594 19.6001H14.8397C18.7459 19.6001 22.0595 16.7508 22.666 12.8893C23.0956 10.1487 21.7158 7.65437 19.3568 6.41797C19.3505 6.70985 19.3252 7.00111 19.2804 7.28983C18.6745 11.15 15.3596 14 11.4547 14H7.57256C7.38037 14.0002 7.19455 14.069 7.04853 14.1939C6.9025 14.3189 6.80583 14.4912 6.7759 14.6811Z" fill="#0070E0"/>
					<path d="M5.55754 22.4005H0.655068C0.56033 22.4005 0.466722 22.38 0.380697 22.3403C0.294673 22.3006 0.218279 22.2427 0.156783 22.1707C0.0952871 22.0986 0.0501524 22.014 0.0244912 21.9228C-0.00117008 21.8316 -0.00674739 21.736 0.00814383 21.6424L3.31352 0.681064C3.34346 0.491271 3.44015 0.318395 3.58619 0.193543C3.73223 0.0686908 3.91804 6.00694e-05 4.11017 0H12.5353C16.5237 0 19.4241 2.90242 19.3565 6.4183C18.364 5.89771 17.1977 5.60014 15.9203 5.60014H8.8964C8.70415 5.60005 8.5182 5.66861 8.37203 5.79347C8.22585 5.91834 8.12907 6.0913 8.09911 6.2812L6.7762 14.6814L5.5569 22.4005H5.55754Z" fill="#003087"/>
				</svg>
			</div>
			<div class="lp-setup-gateway-card__content">
				<strong><?php esc_html_e( 'PayPal Standard', 'learnpress' ); ?></strong>
				<span><?php esc_html_e( 'Accept credit cards and PayPal balance online worldwide.', 'learnpress' ); ?></span>
				<a class="button button-primary lp-setup-connect-paypal" href="<?php echo esc_url( $paypal_settings_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Connect with PayPal', 'learnpress' ); ?></a>
			</div>
			<span class="screen-reader-text"><?php esc_html_e( 'Enable PayPal Standard', 'learnpress' ); ?></span>
			<?php
			echo AdminTemplate::html_toggle_enable(
				array(
					'name' => 'settings[paypal][enable]',
					'value' => $paypal_enabled,
					'classes' => 'lp-setup-gateway-toggle',
				)
			);
			?>
		</article>
	</section>
</section>
