<<<<<<< HEAD
<?php
/**
 * Template for displaying template of login form
 *
 * @author  ThimPress
 * @package Templates
 * @version 2.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Overwrite template in your theme at [YOUR_THEME]/learnpress/profile/login-form.php.
 * By default, it load default login form of WP core.
 */
if ( !isset( $redirect ) ) {
	$redirect = false;
}
$login_args = array(
	'form_id' => 'learn-press-form-login',
	'context' => 'learn-press-login'
);
if ( $redirect ) {
	$login_args['redirect'] = $redirect;
}
=======
<?php
/**
 * Template for displaying template of login form
 *
 * @author  ThimPress
 * @package Templates
 * @version 2.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Overwrite template in your theme at [YOUR_THEME]/learnpress/profile/login-form.php.
 * By default, it load default login form of WP core.
 */
if ( !isset( $redirect ) ) {
	$redirect = false;
}
$login_args = array(
	'form_id' => 'learn-press-form-login',
	'context' => 'learn-press-login'
);
if ( $redirect ) {
	$login_args['redirect'] = $redirect;
}
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
wp_login_form( $login_args );