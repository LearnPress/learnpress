<?php
/**
 * Template for display a notice in admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$classes = array( 'learn-press-notice', 'notice', 'notice-' . $type );
?>
<div id="<?php echo $id; ?>" class="<?php echo join( ' ', $classes ); ?>"
     data-nonce="<?php echo wp_create_nonce( 'admin-notice-' . $id ); ?>">
	<?php echo wpautop( $message ); ?>
	<?php if ( $dismiss ) { ?>
        <a class="dismiss" href=""><?php esc_html_e( 'Close', 'learnpress' ); ?></a>
	<?php } ?>
</div>