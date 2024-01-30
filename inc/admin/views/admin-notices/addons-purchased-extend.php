<?php
/**
 * Template for display message notification addons purchased need extend.
 *
 * @version 1.0.0
 * @since 4.2.5.9
 */

use LearnPress\Helpers\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $data ) || empty( $data['need-extend'] ) ) {
	return;
}

if ( empty( $data['dismiss'] ) ) {
	?>
	<div class="lp-notice notice notice-info">
		<?php
		if ( isset( $data['allow_dismiss'] ) ) {
			Template::instance()->get_admin_template( 'admin-notices/button-dismiss.php', array( 'key' => 'lp-addons-new-version' ) );
		}
		?>
		<p>
			<?php echo sprintf(
				'<strong>%s %s</strong>',
				__( 'You have a LearnPress add-ons license that needs to be extended.', 'learnpress' ),
				sprintf(
					'<a style="color: #E64B50" href="%s">%s</a>',
					admin_url( 'admin.php?page=learn-press-addons&tab=license' ),
					__( 'Check now!', 'learnpress' )
				)
			);
			?>
		</p>
	</div>
	<?php
}
?>
