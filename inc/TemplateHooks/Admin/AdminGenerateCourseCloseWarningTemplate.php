<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;

/**
 * Class AdminCreateCourseAITemplate
 *
 * @since 4.3.0
 * @version 1.0.0
 */
class AdminGenerateCourseCloseWarningTemplate {
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

		echo $this->html_close_generate_course_via_ai();
	}

	public function html_close_generate_course_via_ai(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-close-warning-course-ai">',
			'wrap'                     => '<div class="lp-close-warning-data-ai-wrap">',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'Generating course data is closed', 'learnpress' )
			),
			'desc' 					   => '<p class="desc">'.esc_html__( 'The process of generating course data has been canceled.', 'learnpress' ).'</p>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}
}
