<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
/**
 * Class AdminEditWithAICloseWarningTemplate
 *
 * @since 4.3.0
 * @version 1.0.0
 */
class AdminEditWithAICloseWarningTemplate {
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

		echo $this->html_title();
		echo $this->html_desc();
		echo $this->html_image();
	}

	public function html_title(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-close-warning-edit-title-ai">',
			'wrap'                     => '<div class="lp-close-warning-edit-title-ai-wrap">',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'Generating course title is closed', 'learnpress' )
			),
			'desc' 					   => '<p class="desc">'.esc_html__( 'The process of generating course title has been canceled.', 'learnpress' ).'</p>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}

	public function html_desc(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-close-warning-edit-description-ai">',
			'wrap'                     => '<div class="lp-close-warning-edit-description-ai-wrap">',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'Generating course description is closed', 'learnpress' )
			),
			'desc' 					   => '<p class="desc">'.esc_html__( 'The process of generating course description has been canceled.', 'learnpress' ).'</p>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}

	public function html_image(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-close-warning-edit-image-ai">',
			'wrap'                     => '<div class="lp-close-warning-edit-image-ai-wrap">',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'Generating course image is closed', 'learnpress' )
			),
			'desc' 					   => '<p class="desc">'.esc_html__( 'The process of generating course image has been canceled.', 'learnpress' ).'</p>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}
}
