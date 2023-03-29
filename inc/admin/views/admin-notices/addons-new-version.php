<?php
/**
 * Template for display error wrong permalink structure.
 *
 * @version 1.0.1
 * @since 3.0.0
 */

use LearnPress\Helpers\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $data ) || empty( $data['addons'] ) ) {
	return;
}

if ( empty( $data['dismiss'] ) ) {
	?>
	<div id="notice-install" class="lp-notice notice notice-info">
		<?php
		if ( isset( $data['allow_dismiss'] ) ) {
			Template::instance()->get_admin_template( 'admin-notices/button-dismiss.php', array( 'key' => 'lp-addons-new-version' ) );
		}
		?>
		<p><?php echo sprintf( '<strong>%s</strong>', __( 'New version Addons.', 'learnpress' ) ); ?></p>
		<p style="display: flex;gap: 5px">
			<?php
			foreach ( $data['addons'] as $addon ) {
				echo sprintf(
					'<a href="%s" class="button button-primary">%s</a>',
					admin_url( 'admin.php?page=learn-press-addons&tab=update' ),
					$addon->name
				);
			}
			?>
		</p>
	</div>
	<?php
}
?>
<input type="hidden" name="lp-addons-new-version-totals" value="<?php echo count( $data['addons'] ); ?>"/>
