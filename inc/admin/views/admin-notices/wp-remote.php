<?php
/**
 * Template for display error wrong name plugin learnpress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $data ) || ! isset( $data['error'] ) ) {
	return;
}
?>

<div class="notice <?php echo esc_attr( $data['class'] ?? '' ); ?>">
	<p>
		<?php echo sprintf( '%s %s', '<strong>wp_remote_get</strong>: ', $data['error'] ?? '' ); ?>
	</p>
</div>
