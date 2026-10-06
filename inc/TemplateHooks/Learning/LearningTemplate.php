<?php
/**
 * Template hooks Single Course Online.
 *
 * @since 4.2.7
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Learning;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;

defined( 'ABSPATH' ) || exit;

class LearningTemplate {
	use Singleton;

	public function init(): void {}

	public function html_addon_content_bar(): string {
		$section = apply_filters(
			'learn-press/learning-template/addon-content-bar',
			[
				'wrap'        => '<div class="lp-addon-content-bar">',
				'head'        => sprintf(
					'<div class="lp-addon-content-bar__head">
						<div class="lp-addon-content-bar__left">Note ...</div>
						<span type="button" class="lp-icon-close" aria-label="%1$s" title="%1$s"></span>
					</div>',
					esc_attr__( 'Close', 'learnpress' )
				),
				'content'     => '<div class="lp-addon-content-bar__content"></div>',
				'wrapper_end' => '</div>',
			]
		);

		return Template::combine_components( $section );
	}
}
