<?php
/**
 * Template for displaying register form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/form-register.php.
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

<form id="learn-press-checkout-register" class="learn-press-form register" method="post" enctype="multipart/form-data">

	<?php
	/**
	 * @deprecated
	 */
	do_action( 'learn_press_checkout_before_user_register_form' );

	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/before-checkout-register-form' );
	?>

    <h4><?php echo __( 'New Customer', 'learnpress' ); ?></h4>

    <p><?php echo __( 'Register Account', 'learnpress' ); ?></p>

    <p><?php _e( 'By creating an account you will be able to keep track of the course\'s progress you have previously enrolled.', 'learnpress' ); ?></p>

    <div id="checkout-register-form">

        <ul class="form-fields">

            <li class="form-field">
                <label for="user_name">
                    <span class="field-label"><?php _e( 'Username', 'learnpress' ); ?></span>
                    <span class="required">*</span>
                </label>
                <input class="field-input" type="text" id="user_login" name="user_login">
            </li>
            <li class="form-field">
                <label for="user_password">
                    <span class="field-label"><?php _e( 'Password', 'learnpress' ); ?></span>
                    <span class="required">*</span>
                </label>
                <input class="field-input" type="password" id="user_password" name="user_password">
            </li>
            <li class="form-field">
                <label for="user_email">
                    <span class="field-label"><?php _e( 'Email', 'learnpress' ); ?></span>
                    <span class="required">*</span>
                </label>
                <input class="field-input" type="email" id="user_email" name="user_email">
            </li>
        </ul>

        <p>
            <button><?php _e( 'Register', 'learnpress' ); ?></button>
            <a href="" class="checkout-register-form-toggle"
               data-toggle="hide"><?php _e( 'Cancel', 'learnpress' ); ?></a>
        </p>
    </div>

    <a href="" class="checkout-register-form-toggle" data-toggle="show"><?php _e( 'Register', 'learnpress' ); ?></a>

	<?php
	// @deprecated
	do_action( 'learn_press_checkout_after_user_register_form' );

	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/after-checkout-register-form' );
	?>

</form>