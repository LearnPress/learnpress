<?php
/**
 * Template for display error wp_remote.
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

/**
 * @var WP_Error $wp_error
 */
$wp_error      = $data['check'];
$message_error = $wp_error->get_error_message();
?>

<div class="lp-admin-notice notice notice-error">
	<p>
		<?php
		echo sprintf(
			'%s %s. <a href="%s">%s</a>',
			'<strong>wp_remote_get</strong>: ',
			$message_error,
			admin_url( 'site-health.php' ),
			__( 'Check Site Health', 'learnpress' )
		);
		?>
	</p>
</div>
