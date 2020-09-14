<?php
/**
 * Template for display a notice in admin
 */

defined( 'ABSPATH' ) || exit;

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

<div
<?php
if ( ! empty( $id ) ) {
	echo ' id="' . $id . '" ';
}
?>
class="<?php echo implode( ' ', $classes ); ?>"
>
	<p><?php echo $message; ?></p>

	<?php if ( $dismissible ) : ?>
		<button class="notice-dismiss" data-dismiss-notice="<?php echo $id; ?>"></button>
	<?php endif; ?>
</div>
