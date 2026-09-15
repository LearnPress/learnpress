<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Singleton;

defined( 'ABSPATH' ) || exit();

/**
 * Admin Add-ons page renderer.
 *
 * @since 4.2.8
 */
class AdminAddonsPage {
	use Singleton;

	/**
	 * Singleton initialization hook.
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Render the LearnPress Add-ons page.
	 *
	 * @return void
	 */
	public function html_page() {
		ob_start();
		lp_skeleton_animation_html( 20 );
		$html_loading = ob_get_clean();

		ob_start();
		?>
		<p class="lp-addons-page-subtitle">
			<?php esc_html_e( 'Discover high-performance Premium & Education themes optimized 100% for LearnPress LMS.', 'learnpress' ); ?>
		</p>
		<div class="lp-addons-page">
			<?php echo $html_loading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local skeleton markup. ?>
		</div>
		<?php
		$content = ob_get_clean();

		echo AdminTemplate::html_on_wp_admin_screen(
			array(
				'content' => $content,
				'title'   => __( 'LearnPress Add-ons New', 'learnpress' ),
				'id'      => 'learn-press-addons',
			)
		);
	}
}
