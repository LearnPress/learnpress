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
	//return;
}

$profile = LP_Global::profile();
$fields  = $profile->get_register_fields();

?>
<input type="radio" id="yyyy" name="xxxx" checked="checked"/>
<div id="checkout-account-register" class="lp-checkout-block left">


	<?php
	/**
	 * LP Hook
	 */
	do_action( 'learn-press/before-form-register-fields' );
	?>

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
	 */
	do_action( 'learn-press/after-form-register-fields' );

	wp_nonce_field( 'learn-press-register', 'learn-press-register-nonce' );
	?>

    <p class="lp-checkout-sign-in-link">
		<?php esc_html_e( 'Already had an account?', 'learnpress' ); ?>
        <label for="xxxx"><?php esc_html_e( 'Sign in', 'learnpress' ); ?></label>
    </p>

	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/after-checkout-form-register' );
	?>

</div>