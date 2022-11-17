<?php
/**
 * Template for display error wrong name plugin learnpress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $data ) || ! isset( $data['check'] ) ) {
	return;
}

if ( ! is_wp_error( $data['check'] ) ) {
	return;
}

$message_error = $data['check'] instanceof WP_Error ? $data['check']->get_error_message() : 'Result do not match!';
?>

<div class="lp-admin-notice notice notice-error">
	<p>
		<?php echo sprintf( '%s %s', '<strong>wp_remote_get</strong>: ', $message_error ); ?>
	</p>
</div>
