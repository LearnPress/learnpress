<?php
/**
 * Template tool Reupgrade database.
 *
 * @template html-reupgrade-db
 * @author  tungnx
 * @package learnpress/admin/views/tools/database
 * @version 1.0.0
 * @since 4.0.2
 */

defined( 'ABSPATH' ) or die();
?>

<div class="card" id="lp-tool-re-upgrade-db" style="display: none">
	<h2><?php echo sprintf( '%s', __( 'Reupgrade Database.', 'learnpress' ) ); ?></h2>
	<p><?php _e( '1. Tool only one apply for case Update from LP3 to LP4 didn\'t success', 'learnpress' ); ?></p>
	<p><?php _e( '2. Please sure what you doing', 'learnpress' ); ?></p>
	<p class="tools-button">
		<button type="button" class="button lp-btn lp-btn-re-upgrade-db">
			<?php esc_html_e( 'Run now', 'learnpress' ); ?>
		</button>
	</p>
	<div class="learn-press-message" style="display: none"></div>
</div>
