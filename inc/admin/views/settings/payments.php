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
		<th scope="row"><label for="learn_press_paypal_enable"><?php _e( 'Enable', 'learnpress' ); ?></label></th>
		<td>
			<input type="hidden" name="<?php echo $this->get_field_name( 'paypal_enable' ); ?>" value="no">
			<input type="checkbox" id="learn_press_paypal_enable" name="<?php echo $this->get_field_name( 'paypal_enable' ); ?>" value="yes" <?php checked( $settings->get( 'paypal_enable', 'yes' ) == 'yes', true ); ?> />
		</td>
	</tr>
	<!--<tr class="" data-learn_press_paypal_enable="yes">
		<th scope="row"><label for="learn_press_paypal_type"><?php _e( 'Type', 'learnpress' ); ?></label></th>
		<td>
			<select id="learn_press_paypal_type" name="<?php echo $this->get_field_name( 'paypal_type' ); ?>">
				<option value="basic"<?php selected( $settings->get( 'paypal_type' ) == 'basic' ? 1 : 0, 1 ); ?>><?php _e( 'Basic', 'learnpress' ); ?></option>
				<option value="security" <?php selected( $settings->get( 'paypal_type' ) == 'security' ? 1 : 0, 1 ); ?>><?php _e( 'Security', 'learnpress' ); ?></option>
			</select>
		</td>
	</tr>-->
	<tr data-learn_press_paypal_enable="yes">
		<th scope="row"><label for="learn_press_paypal_email"><?php _e( 'Email Address', 'learnpress' ); ?></label>
		</th>
		<td>
			<input type="email" class="regular-text" name="<?php echo $this->get_field_name( 'paypal_email' ); ?>" value="<?php echo $settings->get( 'paypal_email', '' ); ?>" />
		</td>
	</tr>
 	<!--
	<tr data-learn_press_paypal_enable="yes" class="learn_press_paypal_type_security<?php echo $settings->get( 'paypal_type' ) != 'security' ? ' hide-if-js' : ''; ?>">
		<th scope="row">
			<label for="learn_press_paypal_api_name"><?php _e( 'API Username', 'learnpress' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="<?php echo $this->get_field_name( 'paypal_api_username' ); ?>" value="<?php echo $settings->get( 'paypal_api_username', '' ); ?>" />
		</td>
	</tr>
	<tr data-learn_press_paypal_enable="yes" class="learn_press_paypal_type_security<?php echo $settings->get( 'paypal_type' ) != 'security' ? ' hide-if-js' : ''; ?>">
		<th scope="row">
			<label for="learn_press_paypal_api_pass"><?php _e( 'API Password', 'learnpress' ); ?></label></th>
		<td>
			<input type="password" class="regular-text" name="<?php echo $this->get_field_name( 'paypal_api_password' ); ?>" value="<?php echo $settings->get( 'paypal_api_password', '' ); ?>" />
		</td>
	</tr>
	<tr data-learn_press_paypal_enable="yes" class="learn_press_paypal_type_security<?php echo $settings->get( 'paypal_type' ) != 'security' ? ' hide-if-js' : ''; ?>">
		<th scope="row">
			<label for="learn_press_paypal_api_sign"><?php _e( 'API Signature', 'learnpress' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="<?php echo $this->get_field_name( 'paypal_api_signature' ); ?>" value="<?php echo $settings->get( 'paypal_api_signature', '' ); ?>" />
		</td>
	</tr>
	<!-- sandbox mode -->
<?php
$show_or_hide = $settings->get( 'paypal_type' ) == 'security' ? '' : ' hide-if-js';
$readonly     = $settings->get( 'paypal_sandbox' ) ? '' : ' readonly="readonly"';
?>
	<tr>
		<th scope="row">
			<label for="learn_press_paypal_sandbox_mode"><?php _e( 'Sandbox Mode', 'learnpress' ); ?></label></th>
		<td>
			<input type="hidden" name="<?php echo $this->get_field_name( 'paypal_sandbox' ); ?>" value="no">
			<input type="checkbox" id="learn_press_paypal_sandbox_mode" name="<?php echo $this->get_field_name( 'paypal_sandbox' ); ?>" value="yes" <?php checked( $settings->get( 'paypal_sandbox', 'no' ) == 'yes', true ); ?> />
		</td>
	</tr>
	<tr class="sandbox">
		<th scope="row">
			<label for="learn_press_paypal_sandbox_email"><?php _e( 'Sandbox Email Address', 'learnpress' ); ?></label>
		</th>
		<td>
			<input type="email" class="regular-text"<?php echo $readonly; ?> name="<?php echo $this->get_field_name( 'paypal_sandbox_email' ); ?>" value="<?php echo $settings->get( 'paypal_sandbox_email', '' ); ?>" />
		</td>
	</tr>
<!--
	<tr class="learn_press_paypal_type_security sandbox<?php echo $show_or_hide; ?>">
		<th scope="row">
			<label for="learn_press_paypal_sandbox_name"><?php _e( 'Sandbox API Username', 'learnpress' ); ?></label>
		</th>
		<td>
			<input type="text" class="regular-text"<?php echo $readonly; ?> name="<?php echo $this->get_field_name( 'paypal_sandbox_api_username' ); ?>" value="<?php echo $settings->get( 'paypal_sandbox_api_username', '' ); ?>" />
		</td>
	</tr>
	<tr class="learn_press_paypal_type_security sandbox<?php echo $show_or_hide; ?>">
		<th scope="row">
			<label for="learn_press_paypal_sandbox_pass"><?php _e( 'Sandbox API Password', 'learnpress' ); ?></label>
		</th>
		<td>
			<input type="password" class="regular-text"<?php echo $readonly; ?> name="<?php echo $this->get_field_name( 'paypal_sandbox_api_password' ); ?>" value="<?php echo $settings->get( 'paypal_sandbox_api_password', '' ); ?>" />
		</td>
	</tr>
	<tr class="learn_press_paypal_type_security sandbox<?php echo $show_or_hide; ?>">
		<th scope="row">
			<label for="learn_press_paypal_sandbox_sign"><?php _e( 'Sandbox API Signature', 'learnpress' ); ?></label>
		</th>
		<td>
			<input type="text" class="regular-text"<?php echo $readonly; ?> name="<?php echo $this->get_field_name( 'paypal_sandbox_api_signature' ); ?>" value="<?php echo $settings->get( 'paypal_sandbox_api_signature', '' ); ?>" />
		</td>
	</tr>-->
<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings ); ?>