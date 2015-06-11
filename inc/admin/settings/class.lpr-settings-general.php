<?php

/**
 * Class LPR_Settings_General
 */
class LPR_Settings_General {
	function __construct() {
		add_action( 'learn_press_settings_general', array( $this, 'output' ) );
		add_action( 'learn_press_settings_save_general', array( $this, 'save' ) );
	}

	function output() {
		$settings = LPR_Admin_Settings::instance( 'general' );// get_option( '_lpr_general_settings', array() );
		?>
		<h3><?php _e( 'General Settings', 'learn_press' ); ?></h3>
		<table class="form-table">
			<tbody>
			<?php do_action( 'learn_press_before_general_settings_fields', $settings ); ?>
			<tr>
				<th scope="row"><label for="lpr_set_page"><?php _e( 'Profile methods', 'learn_press' ); ?></label></th>
				<td>
					<select id="lpr_set_page" name="learn_press[set_page]">
						<?php if ( $profile_methods = apply_filters( 'learn_press_profile_methods', array() ) ) ?>
						<?php foreach ( $profile_methods as $k => $name ) { ?>
							<?php $selected = selected( $settings->get( 'set_page' ) == $k ? 1 : 0, 1, false ); ?>
							<option <?php echo $selected; ?> value="<?php echo $k; ?>"><?php echo $name; ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="lpr_currency"><?php _e( 'Currency', 'learn_press' ); ?></label></th>
				<td>
					<?php
					$payment = get_option( '_lpr_payment_settings', array() );
					$disable = '';
					if ( isset( $payment['woocommerce']['active'] ) && learn_press_woo_is_active() ) {
						$disable = 'readonly"';
					}
					?>
					<select <?php echo $disable; ?> id="lpr_currency" name="learn_press[currency]">
						<?php if ( $payment_currencies = learn_press_get_payment_currencies() ) foreach ( $payment_currencies as $code => $symbol ) { ?>
							<?php $selected = selected( $settings->get( 'currency' ) == $code ? 1 : 0, 1, false ); ?>
							<option <?php echo $selected; ?> value="<?php echo $code; ?>"><?php echo $symbol; ?></option>
						<?php } ?>
					</select>

					<p class="description"><?php _e( 'For integrated payment method', 'learn_press' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="learn_press_currency_pos"><?php _e( 'Currency Position', 'learn_press' ); ?></label></th>
				<td>
					<select id="learn_press_currency_pos"name="learn_press[currency_pos]" tabindex="-1" title="Currency Position">
						<?php foreach ( learn_press_currency_positions() as $pos => $text ) { ?>
							<option value="<?php echo $pos; ?>" <?php selected( $settings->get( 'currency_pos' ) == $pos ? 1 : 0, 1 ); ?>>
								<?php
								switch ( $pos ) {
									case 'left':
										printf( '%s ( %s%s )', $text, learn_press_get_currency_symbol(), '69.99' );
										break;
									case 'right':
										printf( '%s ( %s%s )', $text, '69.99', learn_press_get_currency_symbol() );
										break;
									case 'left_with_space':
										printf( '%s ( %s %s )', $text, learn_press_get_currency_symbol(), '69.99' );
										break;
									case 'right_with_space':
										printf( '%s ( %s %s )', $text, '69.99', learn_press_get_currency_symbol() );
										break;
								}
								?>
							</option>
						<?php } ?>
						<!--
						<option value="right">Right (99.99R)</option>
						<option value="left_space">Left with space (R 99.99)</option>
						<option value="right_space">Right with space (99.99 R)</option>-->
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="lpr_thousands_sep"><?php _e( 'Thousands Separator', 'learn_press' ); ?></label></th>
				<td>
					<input id="lpr_thousands_sep"class="regular_text" type="text" name="learn_press[thousands_separator]" value="<?php echo $settings->get( 'thousands_separator', ',' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="lpr_decimals_sep"><?php _e( 'Decimals Separator', 'learn_press' ); ?></label></th>
				<td>
					<input id="lpr_decimals_sep" class="regular_text" type="text" name="learn_press[decimals_separator]" value="<?php echo $settings->get( 'decimals_separator', '.' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="lpr_decimals_num"><?php _e( 'Number of Decimals', 'learn_press' ); ?></label></th>
				<td>
					<input id="lpr_decimals_num"class="regular_text" type="text" name="learn_press[number_of_decimals]" value="<?php echo $settings->get( 'number_of_decimals', 2 ); ?>" />
				</td>
			</tr>
			<?php do_action( 'learn_press_after_general_settings_fields', $settings ); ?>
			</tbody>
		</table>
	<?php
	}

	function save() {
		$settings = LPR_Admin_Settings::instance( 'general' );// $_POST['lpr_settings']['general'];
		$settings->bind( $_POST['learn_press'] );
		$settings->update();
	}
}

new LPR_Settings_General();