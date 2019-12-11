<?php
/**
 * Template for displaying log in form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/form-login.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( is_user_logged_in() ) {
	return;
}

$profile = LP_Global::profile();
$fields  = $profile->get_login_fields();

?>
<input type="radio" id="xxxx" name="xxxx" />
<div id="checkout-account-login" class="lp-checkout-block left">

    <ul class="form-fields">
		<?php foreach ( $fields as $field ) { ?>
            <li class="form-field">
				<?php LP_Meta_Box_Helper::show_field( $field ); ?>
            </li>
		<?php } ?>
    </ul>

	<?php
	/**
	 * LP Hook
	 *
	 * @since 4.0.0
	 */
	do_action( 'learn-press/after-checkout-account-login-fields' );
	?>

    <input type="hidden" name="learn-press-login-nonce"
           value="<?php echo wp_create_nonce( 'learn-press-login' ); ?>">
    <p>
        <label>
            <input type="checkbox" name="rememberme"/>
			<?php _e( 'Remember me', 'learnpress' ); ?>
        </label>

        <a class="lp-lost-password-link"
           href="<?php echo wp_lostpassword_url(); ?>"><?php _e( 'Lost your password?', 'learnpress' ); ?></a>
    </p>

    <p class="lp-checkout-sign-up-link">
		<?php esc_html_e( 'Don\'t have an account?', 'learnpress' ); ?>
        <label for="yyyy"><?php echo _x( 'Sign up.', 'checkout sign up link', 'learnpress' ); ?></label>
    </p>
</div>