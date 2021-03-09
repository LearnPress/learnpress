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
		<a class="button button-primary lp-btn-go-upgrade-db" data-context="message" href="<?php echo esc_url( admin_url( 'admin.php?page=learn-press-tools&tab=database&action=upgrade-db' ) ); ?>"><?php esc_html_e( 'Go to Update', 'learnpress' ); ?></a>
	</p>
</div>
