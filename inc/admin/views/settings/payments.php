<?php
/**
 * Display settings for payments
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$settings = LP()->settings;
?>
<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>
	<tr>
		<th scope="row"><label for="learn_press_paypal_enable"><?php _e( 'Enable', 'learn_press' ); ?></label></th>
		<td>
			<input type="checkbox" id="learn_press_paypal_enable" name="lpr_settings[<?php echo $this->id; ?>][enable]" <?php checked( $settings->get( 'paypal.enable', '' ) ? 1 : 0, 1 ); ?> />
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="learn_press_paypal_type"><?php _e( 'Type', 'learn_press' ); ?></label></th>
		<td>
			<select id="learn_press_paypal_type" name="lpr_settings[<?php echo $this->id; ?>][type]" value="<?php echo $settings->get( 'paypal.type', '' ); ?>">
				<option value="basic"<?php selected( $settings->get( 'paypal.type' ) == 'basic' ? 1 : 0, 1 ); ?>><?php _e( 'Basic', 'learn_press' ); ?></option>
				<option value="security" <?php selected( $settings->get( 'paypal.type' ) == 'security' ? 1 : 0, 1 ); ?>><?php _e( 'Security', 'learn_press' ); ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="learn_press_paypal_email"><?php _e( 'Email Address', 'learn_press' ); ?></label>
		</th>
		<td>
			<input type="email" class="regular-text" id="learn_press_paypal_email" name="lpr_settings[<?php echo $this->id; ?>][paypal_email]" value="<?php echo $settings->get( 'paypal.paypal_email', '' ); ?>" />
		</td>
	</tr>
	<tr class="learn_press_paypal_type_security<?php echo $settings->get( 'paypal.type' ) != 'security' ? ' hide-if-js' : ''; ?>">
		<th scope="row">
			<label for="learn_press_paypal_api_name"><?php _e( 'API Username', 'learn_press' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" id="learn_press_paypal_api_name" name="lpr_settings[<?php echo $this->id; ?>][paypal_api_username]" value="<?php echo $settings->get( 'paypal.paypal_api_username', '' ); ?>" />
		</td>
	</tr>
	<tr class="learn_press_paypal_type_security<?php echo $settings->get( 'paypal.type' ) != 'security' ? ' hide-if-js' : ''; ?>">
		<th scope="row">
			<label for="learn_press_paypal_api_pass"><?php _e( 'API Password', 'learn_press' ); ?></label></th>
		<td>
			<input type="password" class="regular-text" id="learn_press_paypal_api_pass" name="lpr_settings[<?php echo $this->id; ?>][paypal_api_password]" value="<?php echo $settings->get( 'paypal.paypal_api_password', '' ); ?>" />
		</td>
	</tr>
	<tr class="learn_press_paypal_type_security<?php echo $settings->get( 'paypal.type' ) != 'security' ? ' hide-if-js' : ''; ?>">
		<th scope="row">
			<label for="learn_press_paypal_api_sign"><?php _e( 'API Signature', 'learn_press' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" id="learn_press_paypal_api_sign" name="lpr_settings[<?php echo $this->id; ?>][paypal_api_signature]" value="<?php echo $settings->get( 'paypal.paypal_api_signature', '' ); ?>" />
		</td>
	</tr>
	<!-- sandbox mode -->
<?php
$show_or_hide = $settings->get( 'paypal.type' ) == 'security' ? '' : ' hide-if-js';
$readonly     = $settings->get( 'paypal.sandbox' ) ? '' : ' readonly="readonly"';
?>
	<tr>
		<th scope="row">
			<label for="learn_press_paypal_sandbox_mode"><?php _e( 'Sandbox Mode', 'learn_press' ); ?></label></th>
		<td>
			<input type="checkbox" id="learn_press_paypal_sandbox_mode" name="lpr_settings[<?php echo $this->id; ?>][sandbox]" value="1" <?php checked( $settings->get( 'paypal.sandbox' ) ? 1 : 0, 1 ); ?> />
		</td>
	</tr>
	<tr class="sandbox">
		<th scope="row">
			<label for="learn_press_paypal_sandbox_email"><?php _e( 'Sandbox Email Address', 'learn_press' ); ?></label>
		</th>
		<td>
			<input type="email" id="learn_press_paypal_sandbox_email" class="regular-text"<?php echo $readonly; ?> name="lpr_settings[<?php echo $this->id; ?>][paypal_sandbox_email]" value="<?php echo $settings->get( 'paypal.paypal_sandbox_email', '' ); ?>" />
		</td>
	</tr>
	<tr class="learn_press_paypal_type_security sandbox<?php echo $show_or_hide; ?>">
		<th scope="row">
			<label for="learn_press_paypal_sandbox_name"><?php _e( 'Sandbox API Username', 'learn_press' ); ?></label>
		</th>
		<td>
			<input type="text" id="learn_press_paypal_sandbox_name" class="regular-text"<?php echo $readonly; ?> name="lpr_settings[<?php echo $this->id; ?>][paypal_sandbox_api_username]" value="<?php echo $settings->get( 'paypal.paypal_sandbox_api_username', '' ); ?>" />
		</td>
	</tr>
	<tr class="learn_press_paypal_type_security sandbox<?php echo $show_or_hide; ?>">
		<th scope="row">
			<label for="learn_press_paypal_sandbox_pass"><?php _e( 'Sandbox API Password', 'learn_press' ); ?></label>
		</th>
		<td>
			<input type="password" id="learn_press_paypal_sandbox_pass" class="regular-text"<?php echo $readonly; ?> name="lpr_settings[<?php echo $this->id; ?>][paypal_sandbox_api_password]" value="<?php echo $settings->get( 'paypal.paypal_sandbox_api_password', '' ); ?>" />
		</td>
	</tr>
	<tr class="learn_press_paypal_type_security sandbox<?php echo $show_or_hide; ?>">
		<th scope="row">
			<label for="learn_press_paypal_sandbox_sign"><?php _e( 'Sandbox API Signature', 'learn_press' ); ?></label>
		</th>
		<td>
			<input type="text" id="learn_press_paypal_sandbox_sign" class="regular-text"<?php echo $readonly; ?> name="lpr_settings[<?php echo $this->id; ?>][paypal_sandbox_api_signature]" value="<?php echo $settings->get( 'paypal.paypal_sandbox_api_signature', '' ); ?>" />
		</td>
	</tr>
<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>