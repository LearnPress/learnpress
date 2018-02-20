<<<<<<< HEAD
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
=======
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
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
</div>