<?php
/**
 * Template for displaying register form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/form-register.php.
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
?>

<div id="learn-press-checkout-register" class="learn-press-form register">

	<?php
	/**
	 * @deprecated
	 */
	do_action( 'learn_press_checkout_before_user_register_form' );

	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/before-checkout-form-register' );
	?>

    <h4><?php echo __( 'New Customer', 'learnpress' ); ?></h4>

    <p><?php echo __( 'Register Account', 'learnpress' ); ?></p>

    <p><?php _e( 'By creating an account you will be able to keep track of the course\'s progress you have previously enrolled.', 'learnpress' ); ?></p>

    <div id="checkout-form-register">

		<?php learn_press_get_template( 'global/form-register.php' ); ?>

        <p>
            <a href="" class="checkout-form-register-toggle"
               data-toggle="show"><?php _e( 'Register', 'learnpress' ); ?></a>
        </p>
    </div>

	<?php
	// @deprecated
	do_action( 'learn_press_checkout_after_user_register_form' );

	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/after-checkout-form-register' );
	?>

</div>