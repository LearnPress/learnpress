<?php

namespace learnpress\inc\TemplateHooks\Admin\AI;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;

/**
 * Class AdminCreateCourseAITemplate
 *
 * @since 4.3.0
 * @version 1.0.1
 */
class AdminAICloseWarningTemplate {
	use Singleton;

	/**
	 * @var array|null
	 */
	private ?array $config;

	/**
	 * Init hooks.
	 */
	public function init() {
		add_action( 'admin_footer', [ $this, 'layout_popup' ] );
	}

	public function layout_popup() {
		$screen = get_current_screen();
		if ( ! $screen || $screen->id != 'edit-' . LP_COURSE_CPT ) {
			return;
		}

		echo $this->template();
	}

	public function template(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-close-warning-ai">',
			'wrap'                     => '<div class="lp-close-warning-data-ai-wrap">',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'Generating data is stopped', 'learnpress' )
			),
			'desc'                     => sprintf(
				'<p class="desc">%s</p>',
				esc_html__( 'The process of generating data has been canceled.', 'learnpress' )
			),
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}
}
