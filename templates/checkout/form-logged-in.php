<?php
/**
 * Template for displaying logged in form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/form-logged-in.php.
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
if ( ! is_user_logged_in() ) {
	return;
}

global $user_identity;
?>

<p>
	<?php printf( __( 'Logged in as <a href="%1$s">%2$s</a>.' ), get_edit_user_link(), $user_identity ); ?>

    <a href="<?php echo wp_logout_url( get_permalink() ); ?>"
       title="<?php esc_attr_e( 'Log out of this account', 'learnpress' ); ?>"><?php _e( 'Log out &raquo;', 'learnpress' ); ?></a>
</p>
