<?php

namespace LearnPress\TemplateHooks\Admin\AI;

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LP_Debug;
use LP_Settings;
use Throwable;

/**
 * Class AdminEditWithAITemplate
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
		try {
			if ( ! function_exists( 'get_current_screen' ) ) {
				return;
			}

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
			echo $this->html_edit_image_via_ai();
			echo $this->html_edit_curriculum_via_ai();
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
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
			'btn-close'                =>
				'<button type="button" class="lp-btn-close-ai-popup">
					<i class="lp-icon-remove"></i>
				</button>',
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
					<button class="btn btn-secondary lp-btn-step lp-hidden"
					data-step-show="2,3,4"
					data-action="prev" type="button">&larr; %s
					</button>
					<button class="btn btn-primary lp-btn-step"
						data-step-show="1"
						data-action="next" type="button">%s &rarr;
					</button>
					<button class="lp-button btn-primary lp-btn-generate-prompt lp-hidden"
						data-step-show="2"
						data-send="%s" type="button">%s
					</button>
					<button class="lp-button btn-primary lp-btn-call-open-ai lp-hidden"
						data-step-show="3"
						data-send="%s" type="button">%s
					</button>
				</div>',
				esc_html__( 'Previous', 'learnpress' ),
				esc_html__( 'Next', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action'         => 'openai_generate_prompt',
						'lp-prompt-type' => 'course-title', // define type prompt to generate title.
						'id_url'         => 'generate_prompt_openai',
					]
				),
				esc_html__( 'Generate Prompt', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action'         => 'openai_generate_data',
						'lp-prompt-type' => 'course-title', // define type prompt to generate title.
						'target-apply'   => 'set-wp-title', // Click apply to apply title to this field.
						'id_url'         => 'submit_to_openai',
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
				'<div class="step-item" data-step="4"><span class="step-number">4</span><span class="step-text">%s</span></div>',
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
				esc_html__( 'Step 1 — Configure Course Title', 'learnpress' ),
			),
			'description'    => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Provide the basic information to generate your course title.', 'learnpress' )
			),
			'describe-topic' => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<textarea type="text" name="topic" placeholder="%s"></textarea>
				</div>',
				esc_html__( 'Describe what your course is about', 'learnpress' ),
				esc_html__( 'Provide a short explanation of the subject or skills your course covers. This helps AI understand the overall direction of your title.', 'learnpress' )
			),
			'describe-goals' => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<textarea type="text" name="goals" placeholder="%s"></textarea>
				</div>',
				esc_html__( 'Describe the main goals of your course', 'learnpress' ),
				esc_html__( 'Summarize what learners will achieve. AI uses this to make the title more accurate and meaningful.', 'learnpress' )
			),
			'length'         => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="text" name="length" value="60" />
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Title Length (characters)', 'learnpress' ),
				esc_html__( 'Set the maximum number of characters for the generated course title. Ideal for SEO and platform display constraints.', 'learnpress' )
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
					<p class="field-description">%s</p>
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
				),
				esc_html__( 'Identifies who will take the course so the content matches their background and skill level.', 'learnpress' ),
			),
			'tone'          => sprintf(
				'<div class="form-group">
					<label for="swal-tone">%s</label>%s
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Tone', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'          => 'tone',
						'options'       => $options['tone'] ?? [],
						'multiple'      => true,
						'default_value' => [ 'Analytical' ],
					]
				),
				esc_html__( 'Controls the writing style (e.g., friendly, formal, story-telling) so the content matches your brand and audience.', 'learnpress' )
			),
			'language'      => sprintf(
				'<div class="form-group">
					<label>%s</label>%s
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Language', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'    => 'language',
						'options' => $options['language'] ?? [],
					]
				),
				esc_html__( 'Sets the output language for all generated course content.', 'learnpress' )
			),
			'outputs'       => sprintf(
				'<div class="form-group">
					<label>%s</label>%s
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Outputs', 'learnpress' ),
				'<input name="outputs" value="2" />',
				esc_html__( 'Select how many title options the system will generate for you.', 'learnpress' )
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
					<i>%s</i>
				</div>',
				esc_html__( 'Generated Prompt', 'learnpress' ),
				'<textarea name="lp-openai-prompt-generated-field"></textarea>',
				__( 'Shows the auto-generated AI prompt, allowing further adjustments before submission.', 'learnpress' )
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

	/**
	 * HTML list results openAI returned.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function html_list_results( array $args ): string {
		$index = $args['index'] ?? 0;
		$value = $args['value'] ?? '';
		// Target is element html to apply value.
		$target = $args['target-apply'] ?? '';

		$section = [
			'wrap'         => '<div class="lp-ai-generated-result-item form-group">',
			'label'        => sprintf(
				'<label>%s</label>',
				sprintf( __( 'Result %d', 'learnpress' ), $index + 1 )
			),
			'textarea'     => sprintf(
				'<textarea class="lp-ai-string-result" cols="3">%s</textarea>',
				esc_attr( $value )
			),
			'copy_button'  => sprintf(
				'<button class="button lp-btn-copy" data-copy="%s" type="button">%s</button>',
				esc_attr( $value ),
				__( 'Copy', 'learnpress' )
			),
			'apply_button' => sprintf(
				'<button class="button lp-btn-apply button-primary"
					data-apply="%s" type="button" data-target="%s">%s
				</button>',
				esc_attr( $value ),
				esc_attr( $target ),
				__( 'Apply', 'learnpress' )
			),
			'wrap_end'     => '</div>',
		];

		return Template::combine_components( $section );
	}

	/***************** Description *****************/
	/**
	 * HTML generate title with AI.
	 *
	 * @return string
	 */
	public function html_edit_description_via_ai(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-edit-description-ai">',
			'wrap'                     => '<div class="lp-generate-data-ai-wrap">',
			'btn-close'                =>
				'<button type="button" class="lp-btn-close-ai-popup">
					<i class="lp-icon-remove"></i>
				</button>',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'Generate Course Description', 'learnpress' )
			),
			'header'                   => $this->html_description_step_header(),
			'form'                     => '<form class="lp-form-generate-data-ai">',
			'wrap-fields'              => '<div class="lp-form-fields">',
			'step_1'                   => $this->html_description_step_1(),
			'step_2'                   => $this->html_description_step_2(),
			'step_3'                   => $this->html_description_step_3(),
			'step_4'                   => $this->html_description_step_4(),
			'wrap-fields-end'          => '</div>',
			'buttons'                  => sprintf(
				'<div class="button-actions" data-step="1" data-step-max="4">
					<button class="btn btn-secondary lp-btn-step lp-hidden"
						data-step-show="2,3,4"
						data-action="prev" type="button">&larr; %s
					</button>
					<button class="btn btn-primary lp-btn-step"
						data-step-show="1"
						data-action="next" type="button">%s &rarr;
					</button>
					<button class="lp-button btn-primary lp-btn-generate-prompt lp-hidden"
						data-step-show="2"
						data-send="%s" type="button">%s
					</button>
					<button class="lp-button btn-primary lp-btn-call-open-ai lp-hidden"
						data-step-show="3"
						data-send="%s" type="button">%s
					</button>
				</div>',
				esc_html__( 'Previous', 'learnpress' ),
				esc_html__( 'Next', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action'         => 'openai_generate_prompt',
						'lp-prompt-type' => 'course-description',
						'id_url'         => 'generate_prompt_openai',
					]
				),
				esc_html__( 'Generate Prompt', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action'         => 'openai_generate_data',
						'lp-prompt-type' => 'course-description',
						'target-apply'   => 'set-wp-editor-content', // Click apply to apply title to this field.
						'id_url'         => 'submit_to_openai',
					]
				),
				esc_html__( 'Generate Description Course', 'learnpress' ),
			),
			'form-end'                 => '</form>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}

	public function html_description_step_header(): string {
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
				'<div class="step-item" data-step="4"><span class="step-number">4</span><span class="step-text">%s</span></div>',
				esc_html__( 'Result', 'learnpress' )
			),
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_description_step_1(): string {
		$options = $this->config;

		$components = [
			'step'           => '<div class="step-content active" data-step="1">',
			'title'          => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 1 — Configure Course Description', 'learnpress' ),
			),
			'description'    => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Provide the information needed to generate your course description.', 'learnpress' )
			),
			'refer-title'    => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input class="title-refer" type="text" name="post-title" readonly />
					<p class="lp-ai-warning-refer lp-hidden"><i class="lp-icon-warning"></i>%s</p>
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Title refer', 'learnpress' ),
				esc_html__( 'The title refer to generate a relevant course description. Please enter title first', 'learnpress' ),
				esc_html__( 'The course title is automatically imported from the previous step. It will guide the AI to build a structured curriculum.', 'learnpress' )
			),
			'describe-topic' => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<textarea type="text" name="topic" placeholder="%s"></textarea>
				</div>',
				esc_html__( 'Describe what makes this course stand out?', 'learnpress' ),
				esc_html__( 'Provide the main strengths or unique selling points to help the system build a compelling course description.', 'learnpress' )
			),
			'length'         => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="text" name="length" value="1000" />
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Description Length (words)', 'learnpress' ),
				esc_html__( 'Set the maximum number of characters for the generated description.', 'learnpress' )
			),
			'step_close'     => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_description_step_2(): string {
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
					<p class="field-description">%s</p>
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
				),
				esc_html__( 'Identifies who will take the course so the content matches their background and skill level.', 'learnpress' ),
			),
			'tone'          => sprintf(
				'<div class="form-group">
					<label for="swal-tone">%s</label>%s
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Tone', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'          => 'tone',
						'options'       => $options['tone'] ?? [],
						'multiple'      => true,
						'default_value' => [ 'Analytical' ],
					]
				),
				esc_html__( 'Controls the writing style (e.g., friendly, formal, story-telling) so the content matches your brand and audience.', 'learnpress' )
			),
			'language'      => sprintf(
				'<div class="form-group">
					<label>%s</label>%s
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Language', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'    => 'language',
						'options' => $options['language'] ?? [],
					]
				),
				esc_html__( 'Sets the output language for all generated course content.', 'learnpress' )
			),
			'outputs'       => sprintf(
				'<div class="form-group">
					<label>%s</label>%s
				</div>',
				esc_html__( 'Outputs', 'learnpress' ),
				'<input name="outputs" value="2" />'
			),
			'form-grid-end' => '</div>',
			'step_close'    => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_description_step_3(): string {
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
					<i>%s</i>
				</div>',
				esc_html__( 'Generated Prompt', 'learnpress' ),
				'<textarea name="lp-openai-prompt-generated-field"></textarea>',
				__( 'Shows the auto-generated AI prompt, allowing further adjustments before submission.', 'learnpress' )
			),
			'step_close' => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_description_step_4(): string {
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

	/***************** Image *****************/
	public function html_edit_image_via_ai(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-edit-image-ai">',
			'wrap'                     => '<div class="lp-generate-data-ai-wrap">',
			'btn-close'                =>
				'<button type="button" class="lp-btn-close-ai-popup">
					<i class="lp-icon-remove"></i>
				</button>',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'Generate Course Image', 'learnpress' )
			),
			'header'                   => $this->html_image_step_header(), // You can add header if needed.
			'form'                     => '<form class="lp-form-generate-data-ai">',
			'wrap-fields'              => '<div class="lp-form-fields">',
			'step-1'                   => $this->html_image_step_1(),
			'step-2'                   => $this->html_image_step_2(),
			'step-3'                   => $this->html_image_step_3(),
			'wrap-fields-end'          => '</div>',
			'buttons'                  => sprintf(
				'<div class="button-actions" data-step="1" data-step-max="3">
					<button class="btn btn-secondary lp-btn-step lp-hidden"
						data-step-show="2,3"
						data-action="prev" type="button">&larr; %s
					</button>
					<button class="btn btn-primary lp-btn-step lp-hidden"
						data-step-show="0"
						data-action="next" type="button">%s &rarr;
					</button>
					<button class="lp-button btn-primary lp-btn-generate-prompt"
						data-step-show="1"
						data-send="%s" type="button">%s
					</button>
					<button class="lp-button btn-primary lp-btn-call-open-ai lp-hidden"
						data-step-show="2"
						data-send="%s" type="button">%s
					</button>
				</div>',
				esc_html__( 'Previous', 'learnpress' ),
				esc_html__( 'Next', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action'         => 'openai_generate_prompt',
						'lp-prompt-type' => 'course-image',
						'id_url'         => 'generate_prompt_openai',
					]
				),
				esc_html__( 'Generate Prompt', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action'         => 'openai_generate_image',
						'lp-prompt-type' => 'course-image',
						'target-apply'   => 'set-wp-editor-content', // Click apply to apply title to this field.
						'id_url'         => 'submit_to_openai',
					]
				),
				esc_html__( 'Generate Image Course', 'learnpress' ),
			),
			'post-id'                  => sprintf(
				'<input type="hidden" name="post-id" value="%d" />',
				get_the_ID()
			),
			'form-end'                 => '</form>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}

	public function html_image_step_header(): string {
		$components = [
			'wrap'     => '<div class="step-header">',
			'step_1'   => sprintf(
				'<div class="step-item active" data-step="1"><span class="step-number">1</span><span class="step-text">%s</span></div>',
				esc_html__( 'Course Image config', 'learnpress' )
			),
			'step_2'   => sprintf(
				'<div class="step-item" data-step="2"><span class="step-number">2</span><span class="step-text">%s</span></div>',
				esc_html__( 'Prompt', 'learnpress' )
			),
			'step_3'   => sprintf(
				'<div class="step-item" data-step="3"><span class="step-number">3</span><span class="step-text">%s</span></div>',
				esc_html__( 'Result', 'learnpress' )
			),
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_image_step_1(): string {
		$model_type   = LP_Settings::instance()->get( 'open_ai_image_model_type' );
		$options      = $this->config;
		$size_opts    = $options[ "image-size-$model_type" ] ?? [];
		$quality_opts = $options[ "image-quality-$model_type" ] ?? $options['image-quality'] ?? [];

		$components = [
			'step'          => '<div class="step-content active" data-step="1">',
			'title'         => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 1 — Config Image', 'learnpress' ),
			),
			'description'   => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Config your image you want, data will refer course title, course description to generate image.', 'learnpress' )
			),
			'from-title'    => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input class="title-refer" type="text" name="post-title" readonly />
					<p class="lp-ai-warning-refer lp-hidden"><i class="lp-icon-warning"></i>%s</p>
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Title Refer', 'learnpress' ),
				esc_html__( 'The title refer to generate a relevant course image. Please enter title first', 'learnpress' ),
				esc_html__( 'The current course title that will be used as reference during image generation.', 'learnpress' )
			),
			'goal'          => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<textarea type="text" name="goal" placeholder="%s"></textarea>
				</div>',
				esc_html__( 'Goal', 'learnpress' ),
				esc_html__( 'A brief description of the image you want to generate.', 'learnpress' )
			),
			'form-grid'     => '<div class="form-grid">',
			'style'         => sprintf(
				'<div class="form-group">
					<label>%s</label>
					%s
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Style', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'          => 'style',
						'options'       => $options['image-style'] ?? [],
						'default_value' => 'Realistic',
					]
				),
				esc_html__( 'Select the visual style such as modern, minimalist, illustration, 3D, etc.', 'learnpress' )
			),
			/*'write-requirement' => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="text" name="topic" placeholder="%s" />
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Images or icons should be include', 'learnpress' ),
				esc_html__( 'e.g., books, laptop, graduation cap', 'learnpress' ),
				esc_html__( 'List the specific elements or icons that should appear in the generated image.', 'learnpress' )
			),*/
			'size'          => sprintf(
				'<div class="form-group">
					<label>%s</label>
					%s
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Size', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'    => 'size',
						'options' => $size_opts,
					]
				),
				esc_html__( 'Set the output.', 'learnpress' )
			),
			'quality'       => sprintf(
				'<div class="form-group">
					<label>%s</label>
					%s
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Quality', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'    => 'quality',
						'options' => $quality_opts,
					]
				),
				esc_html__( 'Select the desired image quality such as standard, high, or premium.', 'learnpress' )
			),
			'outputs'       => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input name="outputs" value="2" type="number" />
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Outputs', 'learnpress' ),
				esc_html__( 'Number of images you want the system to generate (model dall-e-3 only 1 supported).', 'learnpress' )
			),
			'form-grid-end' => '</div>',
			'step_close'    => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_image_step_2(): string {
		$options = $this->config;

		$components = [
			'step'       => '<div class="step-content" data-step="2">',
			'title'      => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 2 — Prompt Generated', 'learnpress' ),
			),
			'prompt'     => sprintf(
				'<div class="form-group">
					<label>%s</label>%s
					<i>%s</i>
				</div>',
				esc_html__( 'Generated Prompt', 'learnpress' ),
				'<textarea name="lp-openai-prompt-generated-field"></textarea>',
				__( 'Shows the auto-generated AI prompt, allowing further adjustments before submission.', 'learnpress' )
			),
			'step_close' => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_image_step_3(): string {
		$options = $this->config;

		$components = [
			'step'        => '<div class="step-content" data-step="3">',
			'title'       => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 3 — Result', 'learnpress' ),
			),
			'description' => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Note: when applying an image, the process can be very slow (about 1 minute or more), depends on the image size. Please wait until it finishes.', 'learnpress' )
			),
			'results'     => '<div class="lp-ai-generated-results"></div>',
			'step_close'  => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_feature_image_created( $args ): string {
		$src           = $args['src'] ?? '';
		$post_id       = $args['post-id'] ?? '';
		$attachment_id = $args['attachment-id'] ?? '';

		$section = [
			'wrap'     => '<div class="inside">',
			'preview'  => sprintf(
				'<p class="hide-if-no-js">
				<a href="%s" id="set-post-thumbnail" aria-describedby="set-post-thumbnail-desc" class="thickbox">
					<img width="266" height="266" src="%s" alt="" />
				</a>
			</p>',
				admin_url( "media-upload.php?post_id=$post_id&amp;type=image&amp;TB_iframe=1" ),
				$src
			),
			'desc'     => '<p class="hide-if-no-js howto" id="set-post-thumbnail-desc">Click the image to edit or update</p>',
			'remove'   => sprintf(
				'<p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail">%s</a></p>',
				__( 'Remove featured image' )
			),
			'input'    => sprintf(
				'<input type="hidden" id="_thumbnail_id" name="_thumbnail_id" value="%d" />',
				$attachment_id
			),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/***************** Curriculum *****************/
	public function html_edit_curriculum_via_ai(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-edit-curriculum-ai">',
			'wrap'                     => '<div class="lp-generate-data-ai-wrap">',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'Generate Course Curriculum', 'learnpress' )
			),
			'header'                   => '', // You can add header if needed.
			'form'                     => '<form class="lp-form-generate-data-ai">',
			'wrap-fields'              => '<div class="lp-form-fields">',
			// Add steps for curriculum generation here.
			'wrap-fields-end'          => '</div>',
			'buttons'                  => sprintf(
				'<div class="button-actions" data-step="1" data-step-max="1">
					<button class="lp-button btn-primary lp-btn-call-open-ai lp-hidden"
						data-send="%s" type="button">%s
					</button>
				</div>',
				Template::convert_data_to_json(
					[
						'action'         => 'openai_generate_data',
						'lp-prompt-type' => 'course-curriculum',
						'target-apply'   => 'set-course-curriculum', // Click apply to apply curriculum to this field.
						'id_url'         => 'submit_to_openai',
					]
				),
				esc_html__( 'Generate Course Curriculum', 'learnpress' ),
			),
			'post-id'                  => sprintf(
				'<input type="hidden" name="post-id" value="%d" />',
				get_the_ID()
			),
			'form-end'                 => '</form>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}
}
