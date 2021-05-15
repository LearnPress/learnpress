<?php
/**
 * Template tool create indexs fo tables database.
 *
 * @template html-create-indexs-tables
 * @author  tungnx
 * @package learnpress/admin/views/tools/database
 * @version 1.0.0
 * @since 4.0.3
 */

defined( 'ABSPATH' ) or die();
?>

<div class="card" id="lp-tool-create-indexes-tables">
	<h2><?php echo sprintf( '%s', __( 'Create Database Indexes', 'learnpress' ) ); ?></h2>
	<p><?php _e( 'Re-create or create new indexes for tables.', 'learnpress' ); ?></p>
	<p class="tools-button">
		<button type="button" class="button lp-btn lp-btn-create-indexes"><?php esc_html_e( 'Create now', 'learnpress' ); ?></button>
	</p>

	<div class="wrapper-lp-loading" style="display: none">
		<?php lp_skeleton_animation_html( 7 ); ?>
	</div>
</div>

