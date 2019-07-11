<?php
/**
 * Template for display a notice in admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

if ( $dismissible ) {
}
?>
<div<?php if ( ! empty( $id ) ) {
	echo ' id="' . $id . '"';
} ?> class="<?php echo join( ' ', $classes ); ?>">
    <p><?php echo $message; ?></p>
	<?php if ( $dismissible ) { ?>
        <button class="notice-dismiss" data-dismiss-notice="<?php echo $id;?>"></button>
	<?php } ?>
</div>