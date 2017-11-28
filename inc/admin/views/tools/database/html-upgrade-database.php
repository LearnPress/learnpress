<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();
?>

<div class="card">
	<h2><?php _e( 'Upgrade Database', 'learnpress' ); ?></h2>
	<p><?php _e( 'Force to upgrade database.', 'learnpress' ); ?></p>
	<p class="tools-button">
		<a class="button"
		   href="<?php echo admin_url( 'index.php?page=lp-database-updater' ); ?>"><?php esc_html_e( 'Upgrade', 'learnpress' ); ?></a>
	</p>
</div>