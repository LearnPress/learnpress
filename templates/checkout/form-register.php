<?php
/**
 * Output register form.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

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
	 * @since 3.x.x
	 */
	do_action( 'learn-press/before-checkout-register-form' );
	?>

    <h4><?php echo __( 'New Customer', 'learnpress' ); ?></h4>

    <p><?php echo __( 'Register Account', 'learnpress' ); ?></p>

    <p><?php _e( 'By creating an account you will be able to keep track of the course\'s progress you have previously enrolled.', 'learnpress' ); ?></p>

    <p>
        <a href="<?php echo esc_url( learn_press_get_register_url() ); ?>"><?php _e( 'Register', 'learnpress' ); ?></a>
    </p>

	<?php
	// @deprecated
	do_action( 'learn_press_checkout_after_user_register_form' );

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/after-checkout-register-form' );
	?>

</form>