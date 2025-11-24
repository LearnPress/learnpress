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
class AdminCreatingCourseAITemplate {
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

		echo $this->html_create_course_via_ai_success();
	}

	public function html_create_course_via_ai_success(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-creating-course-ai">',
			'wrap'                     => '<div class="lp-creating-course-ai">',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'Creating course', 'learnpress' )
			),
			'desc' 					   => '<p class="desc">'.esc_html__( 'Creating course. This may take a few moments...', 'learnpress' ).'</p>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}
}
