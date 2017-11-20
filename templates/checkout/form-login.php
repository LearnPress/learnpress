<?php
/**
 * Template for displaying log in form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/form-login.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
if ( is_user_logged_in() ) {
	return;
}
?>

<form id="learn-press-checkout-login" class="learn-press-form login">

	<?php
	/**
	 * @deprecated
	 */
	do_action( 'learn_press_checkout_before_user_login_form' );

	?>

    <h4><?php _e( 'Returning customer', 'learnpress' ) ?></h4>
    <p><?php _e( 'I am a returning customer.', 'learnpress' ) ?></p>

	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/before-checkout-login-form-fields' );

	?>
    <div id="checkout-login-form">
        <ul class="form-fields">

			<?php
			/**
			 * @deprecated
			 */
			do_action( 'learn_press_checkout_user_login_before_form_fields' );

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/begin-checkout-login-form-fields' );

			?>

            <li class="form-field">
                <label for="user_login">
                    <span class="field-label"><?php _e( 'Username' ); ?></span>
                    <span class="required">*</span>
                </label>
                <input class="field-input" type="text" id="user_login" name="user_login"/>
            </li>
            <li class="form-field">
                <label for="user_password">
                    <span class="field-label"><?php _e( 'Password' ); ?></span>
                    <span class="required">*</span>
                </label>
                <input class="field-input" type="password" id="user_password" name="user_password"/>
            </li>

			<?php
			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/end-checkout-login-form-fields' );

			/**
			 * @deprecated
			 */
			do_action( 'learn_press_checkout_user_login_after_form_fields' );
			?>

        </ul>

		<?php
		/**
		 * @since 3.0.0
		 */
		do_action( 'learn-press/before-checkout-login-form-button' );
		?>
        <p>
            <button type="button" id="learn-press-checkout-login-button"><?php _e( 'Login', 'learnpress' ); ?></button>
            <a href="" class="checkout-login-form-toggle" data-toggle="hide"><?php _e( 'Cancel', 'learnpress' ); ?></a>
        </p>
    </div>

    <a href="" class="checkout-login-form-toggle" data-toggle="show"><?php _e( 'Login', 'learnpress' ); ?></a>

	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/after-checkout-login-form-fields' );

	/**
	 * @deprecated
	 */
	do_action( 'learn_press_checkout_after_user_login_form' );
	?>

</form>