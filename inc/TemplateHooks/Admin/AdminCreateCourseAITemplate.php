<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;

/**
 * Class AdminEditCourseAI
 *
 * @since 4.3.0
 * @version 1.0.0
 */
class AdminCreateCourseAITemplate {
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

		$this->config = Config::instance()->get( 'open-ai-modal', 'settings' );
		echo $this->html_create_course_via_ai();
	}

	public function html_create_course_via_ai(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-create-course-ai">',
			'wrap'                     => '<div class="lp-create-course-ai-wrap">',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'AI Course Builder for LearnPress', 'learnpress' )
			),
			'header'                   => $this->html_step_header(),
			'form'                     => '<form class="lp-form-create-course-ai">',
			'step_1'                   => $this->html_step_1(),
			'step_2'                   => $this->html_step_2(),
			'step_3'                   => $this->html_step_3(),
			'step_4'                   => $this->html_step_4(),
			'buttons'                  => sprintf(
				'<div class="button-actions" data-step="1" data-step-max="4">
					<button class="btn btn-secondary lp-btn-step lp-hidden" data-action="prev" type="button">&larr; %s</button>
					<button class="btn btn-primary lp-btn-step" data-action="next" type="button">%s &rarr;</button>
					<button class="lp-button btn-primary lp-btn-generate-prompt lp-hidden" data-action="openai_generate_prompt_course" type="button">%s</button>
					<button class="lp-button btn-primary lp-btn-create-course lp-hidden" type="button">%s</button>
				</div>',
				esc_html__( 'Previous', 'learnpress' ),
				esc_html__( 'Next', 'learnpress' ),
				esc_html__( 'Generate Prompt', 'learnpress' ),
				esc_html__( 'Create Course', 'learnpress' ),
			),
			'form-end'                 => '</form>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}

	public function html_step_header(): string {
		$components = [
			'wrap'     => '<div class="step-header">',
			'step_1'   => sprintf(
				'<div class="step-item active" data-step="1"><span class="step-number">1</span><span class="step-text">%s</span></div>',
				esc_html__( 'Course Intent', 'learnpress' )
			),
			'step_2'   => sprintf(
				'<div class="step-item" data-step="2"><span class="step-number">2</span><span class="step-text">%s</span></div>',
				esc_html__( 'AI Settings', 'learnpress' )
			),
			'step_3'   => sprintf(
				'<div class="step-item" data-step="3"><span class="step-number">3</span><span class="step-text">%s</span></div>',
				esc_html__( 'Course Structure', 'learnpress' )
			),
			'step_4'   => sprintf(
				'<div class="step-item" data-step="4"><span class="step-number">4</span><span class="step-text">%s</span></div>',
				esc_html__( 'Generate', 'learnpress' )
			),
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $components );
	}

	private function html_step_1(): string {
		$options = $this->config;

		$components = [
			'step'        => '<div class="step-content active" data-step="1">',
			'title'       => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 1 — Course Intent', 'learnpress' ),
			),
			'description' => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Define the course goal and the authoring role/persona.', 'learnpress' )
			),
			'role'        => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="text" name="role_persona" value="Front-end Trainer + Instructional Designer">
				</div>',
				esc_html__( 'Role / Persona (critical)', 'learnpress' )
			),
			'audience'    => sprintf(
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
			'objective'   => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<textarea name="course_objective">Act as a Front-end Training Expert + Instructional Designer. Create a Basic HTML course for absolute beginners. Learners should understand HTML5 structure, semantic tags, accessibility basics, and simple SEO on-page.</textarea>
				</div>',
				esc_html__( 'Course objective', 'learnpress' )
			),
			'step_close'  => '</div>',
		];

		return Template::combine_components( $components );
	}

	/**
	 * Step 2 HTML.
	 *
	 * @return string
	 */
	public function html_step_2(): string {
		$options         = $this->config;
		$grid_components = [
			'grid'          => '<div class="form-grid">',
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
			'reading_level' => sprintf(
				'<div class="form-group">
					<label for="swal-levels">%s</label>%s
				</div>',
				esc_html__( 'Reading level', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'    => 'reading_level',
						'options' => $options['reading_level'] ?? [],
					]
				)
			),
			'seo_emphasis'  => sprintf(
				'<div class="form-group">
					<label for="seo-emphasis">%s</label>
					<input type="text" name="seo_emphasis" value="Basic (title/meta/heading)">
				</div>',
				esc_html__( 'SEO emphasis', 'learnpress' )
			),
			'keywords'      => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="text" name="target_keywords" value="html5, semantic tags, accessibility, seo on-page">
				</div>',
				esc_html__( 'Target keywords (comma-separated)', 'learnpress' )
			),
			'grid-end'      => '</div>',
		];

		$components = [
			'step'        => '<div class="step-content" data-step="2">',
			'title'       => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 2 — AI Settings', 'learnpress' ),
			),
			'description' => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Configure content quality controls for ChatGPT output.', 'learnpress' )
			),
			'form_grid'   => Template::combine_components( $grid_components ),
			'step-end'    => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_step_3(): string {
		$grid_components = [
			'grid'                  => '<div class="form-grid">',
			'sections-number'       => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="section_number" value="2" min="0">
				</div>',
				esc_html__( 'Sections number', 'learnpress' )
			),
			'sections-title-length' => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="section_title_length" value="2" min="0">
				</div>',
				esc_html__( 'Each section title length', 'learnpress' )
			),
			'sections-des-length'   => sprintf(
				'<div class="form-group">
					<labe>%s</labe>
					<input type="number" name="section_description_length" value="2" min="0">
				</div>',
				esc_html__( 'Each section description length', 'learnpress' )
			),
			'lesson-number'         => sprintf(
				'<div class="form-group">
					<label for="lessons-per-section">%s</label>
					<input type="number" name="lessons_per_section" value="2" min="0">
				</div>',
				esc_html__( 'Lessons per Section', 'learnpress' )
			),
			'lesson-title-length'   => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="lessons_title_length" value="2" min="0">
				</div>',
				esc_html__( 'Lessons per Section', 'learnpress' )
			),
			'lesson-des-length'     => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="lessons_description_length" value="2" min="0">
				</div>',
				esc_html__( 'Lessons per Section', 'learnpress' )
			),
			'quizzes'               => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="quizzes_per_section" value="2" min="0">
				</div>',
				esc_html__( 'Quizzes per Section', 'learnpress' )
			),
			'questions'             => sprintf(
				'<div class="form-group">
					<label for="questions-per-quiz">%s</label>
				<input type="number" name="questions_per_quiz" value="2" min="0"></div>',
				esc_html__( 'Questions per Quiz', 'learnpress' )
			),
			'grid-end'              => '</div>',
		];

		$components = [
			'step'        => '<div class="step-content" data-step="3">',
			'title'       => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 3 — Course Structure', 'learnpress' ),
			),
			'description' => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Define the LearnPress structure. The Prompt will be generated based on these controls.', 'learnpress' )
			),
			'form_grid'   => Template::combine_components( $grid_components ),
			'step-end'    => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_step_4(): string {
		$layout_components = [
			'layout_open'  => '<div class="step4-layout">',
			//'left_panel'   => $this->_get_step_4_left_panel(),
			//'right_panel'  => $this->_get_step_4_right_panel(),
			'layout_close' => '</div>',
		];
		$layout            = Template::combine_components( $layout_components );

		$content_components = [
			//'header' => $this->_get_step_4_header(),
			'layout' => $layout,
		];
		$content            = Template::combine_components( $content_components );

		$components = [
			'step'        => '<div class="step-content" data-step="4">',
			'title'       => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 4 — Generated Course', 'learnpress' ),
			),
			'description' => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Review the generated course structure and content below.', 'learnpress' )
			),
			'content'     => $content,
			'step-end'    => '</div>',
		];

		return Template::combine_components( $components );
	}
}
