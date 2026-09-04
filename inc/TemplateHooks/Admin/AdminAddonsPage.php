<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;

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

		$section = apply_filters(
			'learn-press/admin/manager-addons/section',
			array(
				'label'      => sprintf(
					'<h1>%s</h1>',
					__( 'LearnPress Add-ons', 'learnpress' )
				),
				'note-theme' => sprintf(
					'<p style="color: rgba(255,0,0,0.76)"><strong><i>%s</i></strong></p>',
					__( '* If you use a Premium Theme that includes LearnPress add-ons, you can go to the <strong>Plugins</strong> tab on Dashboard of theme to download or update them.', 'learnpress' )
				),
				'note-addon' => sprintf(
					'<p>%s</p>',
					sprintf(
						__( 'If you have purchased a premium add-on separately, you can enter your purchase code (%s) to download or update the add-ons here.', 'learnpress' ),
						sprintf(
							'<a href="%s" target="_blank">%s</a>',
							'https://thimpress.com/my-account/',
							__( 'get from your account', 'learnpress' )
						)
					)
				),
				'list'       => sprintf( '<div class="lp-addons-page">%s</div>', $html_loading ),
			)
		);

		echo Template::combine_components( $section );
	}
}
