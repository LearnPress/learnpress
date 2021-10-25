<?php
/**
 * Template for display a notice in admin
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $message ) ) {
	return;
}

if ( ! isset( $type ) ) {
	$type = 'success';
}

if ( ! isset( $dismissible ) ) {
	$dismissible = true;
}

if ( strpos( $type, 'notice-' ) === false ) {
	$type = "notice-{$type}";
}

$classes = array( 'lp-notice', 'notice', $type );
?>

<div id="<?php echo esc_attr( $id ?? '' ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<p><?php echo esc_html( $message ); ?></p>

	<?php if ( $dismissible ) : ?>
		<button class="notice-dismiss" data-dismiss-notice="<?php echo esc_attr( $id ?? '' ); ?>"></button>
	<?php endif; ?>
</div>
