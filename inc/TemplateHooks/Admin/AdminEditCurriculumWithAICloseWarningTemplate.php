<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
/**
 * Class AdminEditCurriculumWithAICloseWarningTemplate
 *
 * @since 4.3.0
 * @version 1.0.0
 */
class AdminEditCurriculumWithAICloseWarningTemplate {
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
		$screen  = get_current_screen();
		$screens = [
			LP_COURSE_CPT,
		];
		if ( ! $screen || in_array( $screen, $screens ) ) {
			return;
		}

		echo $this->html();
	}

	public function html(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-close-warning-edit-curriculum-ai">',
			'wrap'                     => '<div class="lp-close-warning-edit-curriculum-ai-wrap">',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'Generating course curriculum is closed', 'learnpress' )
			),
			'desc' 					   => '<p class="desc">'.esc_html__( 'The process of generating course curriculum has been canceled.', 'learnpress' ).'</p>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}
}
