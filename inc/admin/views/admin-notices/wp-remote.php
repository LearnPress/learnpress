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
?>

<div class="lp-admin-notice">
	<p>
		<?php echo sprintf( '%s %s', '<strong>wp_remote_get</strong>: ', $data['error'] ?? '' ); ?>
	</p>
</div>
