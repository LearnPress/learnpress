<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! is_user_logged_in() ) {
	return;
}

global $user_identity;
?>

<p>
	<?php
	printf(
		wp_kses( __( 'Logged in as <a href="%1$s">%2$s</a>.', 'learnpress' ), array( 'a' => array( 'href' => array() ) ) ),
		get_edit_user_link(),
		$user_identity
	);
	?>
    <a href="<?php echo wp_logout_url( get_permalink() ); ?>"
       title="<?php esc_attr_e( 'Log out of this account', 'learnpress' ); ?>"><?php _e( 'Log out &raquo;', 'learnpress' ); ?></a>
</p>
