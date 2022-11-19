<?php
/**
 * Template for display error wrong permalink structure.
 *
 * @since 3.0.0
 * @version 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $data ) || ! isset( $data['check'] ) ) {
	return;
}

if ( ! $data['check'] ) {
	return;
}
?>

<div class="lp-admin-notice">
	<p>
		<?php
		echo sprintf(
			'LearnPress requires permalink option <strong>Post name</strong> is enabled. Please enable it <a href="%s">here</a> to ensure that all functions work properly.',
			admin_url( 'options-permalink.php' )
		)
		?>
	</p>
</div>
