<?php
/**
 * Template for display a notice in admin
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}
$classes = array( $type );
?>
<div<?php if ( !empty( $id ) ) echo ' id="' . $id . '"'; ?> class="<?php echo join( ' ', $classes ); ?>">
	<p><?php echo $message; ?></p>
</div>