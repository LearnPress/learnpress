<?php
/**
 * Template for displaying update message
 *
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="notice notice-warning lp-notice-update-database">
	<p>
		<?php _e( '<strong>LearnPress update</strong> – We need to update your database to the latest version.', 'learnpress' ); ?>
	</p>
	<p>
		<a class="button button-primary lp-button-upgrade" data-context="message" href="<?php echo esc_url( admin_url( 'index.php?do-update-learnpress=yes' ) ); ?>"><?php esc_html_e( 'Update Now', 'learnpress' ); ?></a>
	</p>
</div>
