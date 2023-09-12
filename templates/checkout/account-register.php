<?php
/**
 * Template for displaying register form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/form-register.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();
?>

<input type="radio" id="checkout-account-switch-to-register" name="checkout-account-switch-form" checked="checked" value="register"/>
<div id="checkout-account-register" class="lp-checkout-block left">

	<h4><?php esc_html_e( 'Sign up', 'learnpress' ); ?></h4>

	<ul class="lp-form-fields">
		<?php do_action( 'learn-press/before-form-register-fields' ); ?>

		<li class="form-field">
			<label for="reg_email"><?php esc_html_e( 'Email address', 'learnpress' ); ?>&nbsp;<span class="required">*</span></label>
			<input id ="reg_email" name="reg_email" type="text" placeholder="<?php esc_attr_e( 'Email', 'learnpress' ); ?>" autocomplete="email" value="<?php echo esc_attr( LP_Helper::sanitize_params_submitted( $_POST['reg_email'] ?? '' ) ); ?>">
		</li>
		<li class="form-field">
			<label for="reg_username"><?php esc_html_e( 'Username', 'learnpress' ); ?>&nbsp;<span class="required">*</span></label>
			<input id ="reg_username" name="reg_username" type="text" placeholder="<?php esc_attr_e( 'Username', 'learnpress' ); ?>" autocomplete="username" value="<?php echo esc_attr( LP_Helper::sanitize_params_submitted( $_POST['reg_username'] ?? '' ) ); ?>">
		</li>
		<li class="form-field">
			<label for="reg_password"><?php esc_html_e( 'Password', 'learnpress' ); ?>&nbsp;<span class="required">*</span></label>
			<input id ="reg_password" name="reg_password" type="password" placeholder="<?php esc_attr_e( 'Password', 'learnpress' ); ?>" autocomplete="new-password">
		</li>
		<li class="form-field">
			<label for="reg_password2"><?php esc_html_e( 'Confirm Password', 'learnpress' ); ?>&nbsp;<span class="required">*</span></label>
			<input id ="reg_password2" name="reg_password2" type="password" placeholder="<?php esc_attr_e( 'Password', 'learnpress' ); ?>" autocomplete="off">
		</li>

		<?php do_action( 'learn-press/after-form-register-fields' ); ?>
	</ul>

	<?php
	// Add hook of WordPress
	do_action( 'register_form' );
	?>

	<?php //wp_nonce_field( 'learn-press-checkout-register', 'learn-press-checkout-nonce' ); ?>

	<p class="lp-checkout-sign-in-link">
		<?php if ( LearnPress::instance()->checkout()->is_enable_login() ) : ?>
			<?php esc_html_e( 'Already had an account?', 'learnpress' ); ?>
			<a href="javascript: void(0);">
				<label for="checkout-account-switch-to-login"><?php esc_html_e( 'Sign in', 'learnpress' ); ?></label>
			</a>.
		<?php endif; ?>

		<?php learn_press_get_template( 'checkout/guest-checkout-link' ); ?>
	</p>

	<?php do_action( 'learn-press/after-checkout-form-register' ); ?>
</div>
