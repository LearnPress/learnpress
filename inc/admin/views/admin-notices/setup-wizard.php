<?php
/**
 * Template for display error wrong permalink structure.
 *
 * @version 1.0.1
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $data ) || ! isset( $data['check'] ) || ! $data['check'] ) {
	return;
}
?>
<div id="notice-install" class="lp-notice notice notice-info">
	<p><?php echo sprintf( '<strong>%s</strong>', __( 'LearnPress LMS is ready to use.', 'learnpress' ) ); ?></p>
	<p>
		<a class="button button-primary" href="<?php echo admin_url( 'index.php?page=lp-setup' ); ?>"><?php _e( 'Quick Setup', 'learnpress' ); ?></a>
		<?php
		if ( isset( $data['allow_dismiss'] ) ) :
			?>
			<button type="button" class="notice-dismiss btn-lp-notice-dismiss" data-dismiss="lp-setup-wizard">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		<?php endif; ?>
	</p>
</div>
