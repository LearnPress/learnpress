<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;

/**
 * Class AdminEditCourseAI
 *
 * @since 4.2.9
 * @version 1.1.0
 */
class AdminEditWithAITemplate {
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

		$this->config = Config::instance()->get( 'open-ai-modal', 'settings' );
		echo $this->html_edit_title_via_ai();
		echo $this->html_edit_description_via_ai();
	}

	/**
	 * HTML generate title with AI.
	 *
	 * @return string
	 */
	public function html_edit_title_via_ai(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-edit-title-ai">',
			'wrap'                     => '<div class="lp-generate-data-ai-wrap">',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'Generate Course Title', 'learnpress' )
			),
			'header'                   => $this->html_title_step_header(),
			'form'                     => '<form class="lp-form-generate-data-ai">',
			'wrap-fields'              => '<div class="lp-form-fields">',
			'step_1'                   => $this->html_title_step_1(),
			'step_2'                   => $this->html_title_step_2(),
			'step_3'                   => $this->html_title_step_3(),
			'step_4'                   => $this->html_title_step_4(),
			'wrap-fields-end'          => '</div>',
			'buttons'                  => sprintf(
				'<div class="button-actions" data-step="1" data-step-max="4">
					<button class="btn btn-secondary lp-btn-step lp-hidden" data-action="prev" type="button">&larr; %s</button>
					<button class="btn btn-primary lp-btn-step" data-action="next" type="button">%s &rarr;</button>
					<button class="lp-button btn-primary lp-btn-generate-prompt lp-hidden"
						data-send="%s" type="button">%s
					</button>
					<button class="lp-button btn-primary lp-btn-call-open-ai lp-hidden"
						data-send="%s" type="button">%s
					</button>
				</div>',
				esc_html__( 'Previous', 'learnpress' ),
				esc_html__( 'Next', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action' => 'openai_generate_prompt_title',
						'id_url' => 'generate_prompt_openai',
					]
				),
				esc_html__( 'Generate Prompt', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action' => 'openai_generate_title',
						'id_url' => 'submit_to_openai',
					]
				),
				esc_html__( 'Generate Title Course', 'learnpress' ),
			),
			'form-end'                 => '</form>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}

	public function html_title_step_header(): string {
		$components = [
			'wrap'     => '<div class="step-header">',
			'step_1'   => sprintf(
				'<div class="step-item active" data-step="1"><span class="step-number">1</span><span class="step-text">%s</span></div>',
				esc_html__( 'Course Goal', 'learnpress' )
			),
			'step_2'   => sprintf(
				'<div class="step-item" data-step="2"><span class="step-number">2</span><span class="step-text">%s</span></div>',
				esc_html__( 'AI Settings', 'learnpress' )
			),
			'step_3'   => sprintf(
				'<div class="step-item" data-step="3"><span class="step-number">3</span><span class="step-text">%s</span></div>',
				esc_html__( 'Prompt', 'learnpress' )
			),
			'step_4'   => sprintf(
				'<div class="step-item" data-step="3"><span class="step-number">3</span><span class="step-text">%s</span></div>',
				esc_html__( 'Result', 'learnpress' )
			),
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_title_step_1(): string {
		$options = $this->config;

		$components = [
			'step'           => '<div class="step-content active" data-step="1">',
			'title'          => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 1 — Config title', 'learnpress' ),
			),
			'description'    => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Config your title you want.', 'learnpress' )
			),
			'describe-topic' => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<textarea type="text" name="topic">Create title about PHP</textarea>
				</div>',
				esc_html__( 'Describe what your course is about', 'learnpress' )
			),
			'describe-goals' => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<textarea type="text" name="goals">I want create title course advanced PHP 8 function</textarea>
				</div>',
				esc_html__( 'Describe the main goals of your course', 'learnpress' )
			),
			'length'         => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="text" name="length" value="60" />
				</div>',
				esc_html__( 'Title Length (characters)', 'learnpress' )
			),
			'step_close'     => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_title_step_2(): string {
		$options = $this->config;

		$components = [
			'step'          => '<div class="step-content" data-step="2">',
			'title'         => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 2 — AI Settings', 'learnpress' ),
			),
			'description'   => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Configure content quality controls for ChatGPT output.', 'learnpress' )
			),
			'form-grid'     => '<div class="form-grid">',
			'audience'      => sprintf(
				'<div class="form-group">
					<label>%s</label>%s
				</div>',
				esc_html__( 'Target Audience', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'          => 'target_audience',
						'class_name'    => '',
						'options'       => $options['audience'] ?? [],
						'default_value' => 'Students',
						'multiple'      => true,
					]
				)
			),
			'tone'          => sprintf(
				'<div class="form-group">
					<label for="swal-tone">%s</label>%s
				</div>',
				esc_html__( 'Tone', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'          => 'tone',
						'options'       => $options['tone'] ?? [],
						'multiple'      => true,
						'default_value' => [ 'Analytical' ],
					]
				)
			),
			'language'      => sprintf(
				'<div class="form-group">
					<label>%s</label>%s
				</div>',
				esc_html__( 'Language', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'    => 'language',
						'options' => $options['language'] ?? [],
					]
				)
			),
			'output'        => sprintf(
				'<div class="form-group">
					<label>%s</label>%s
				</div>',
				esc_html__( 'Outputs', 'learnpress' ),
				'<input name="output" value="2" />'
			),
			'form-grid-end' => '</div>',
			'step_close'    => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_title_step_3(): string {
		$options = $this->config;

		$components = [
			'step'       => '<div class="step-content" data-step="3">',
			'title'      => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 3 — Prompt Generated', 'learnpress' ),
			),
			'prompt'     => sprintf(
				'<div class="form-group">
					<label>%s</label>%s
				</div>',
				esc_html__( 'Generated Prompt', 'learnpress' ),
				'<textarea name="lp-openai-prompt-generated-field"></textarea>',
			),
			'step_close' => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_title_step_4(): string {
		$options = $this->config;

		$components = [
			'step'       => '<div class="step-content" data-step="4">',
			'title'      => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 4 — Result', 'learnpress' ),
			),
			'results'    => '<div class="lp-ai-generated-results"></div>',
			'step_close' => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_edit_description_via_ai(): string {
		return '';
	}
}
