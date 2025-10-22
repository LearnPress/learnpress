<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LP_Meta_Box_Select_Field;
use LP_Settings;

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
			'nav_buttons'              => sprintf(
				'<div class="navigation-buttons">
					<button class="btn btn-primary lp-btn-next-step" type="button">%s &rarr;</button>
				</div>',
				esc_html__( 'Next', 'learnpress' )
			),
			'form-end'                 => '</form>>',
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

		$audience_field     = new LP_Meta_Box_Select_Field(
			__( 'Target Audience', 'learnpress' ),
			'',
			'Students',
			array(
				'options'    => $options['audience'] ?? [],
				'tom_select' => true,
				'multiple'   => true,
			)
		);
		$audience_field->id = 'ai-audience';

		ob_start();
		$audience_field->output( 0 );
		$html_audience = ob_get_clean();

		$components = [
			'step'            => '<div id="step-1" class="step-content active">',
			'header'          => sprintf(
				'<div class="step-header"><h2>%s</h2><p>%s</p></div>',
				esc_html__( 'Step 1 — Course Intent', 'learnpress' ),
				esc_html__( 'Define the course goal and the authoring role/persona.', 'learnpress' )
			),
			'role_group'      => sprintf(
				'<div class="form-group"><label for="role-persona">%s</label><input type="text" id="role-persona" value="Front-end Trainer + Instructional Designer"></div>',
				esc_html__( 'Role / Persona (critical)', 'learnpress' )
			),
			//          'audience_group'  => sprintf(
			//              '<div class="form-group"><label for="swal-audience">%s</label><select id="swal-audience" multiple>%s</select></div>',
			//              esc_html__( 'Target audience', 'learnpress' ),
			//              $this->build_select_options( $options['audience'] ?? [] )
			//          ),
			'audience'        => sprintf(
				'<div class="form-group">%s</div>',
				$html_audience
			),
			'objective_group' => sprintf(
				'<div class="form-group"><label for="course-objective">%s</label><textarea id="course-objective">Act as a Front-end Training Expert + Instructional Designer. Create a Basic HTML course for absolute beginners. Learners should understand HTML5 structure, semantic tags, accessibility basics, and simple SEO on-page.</textarea></div>',
				esc_html__( 'Course objective', 'learnpress' )
			),

			'step_close'      => '</div>',
		];

		return Template::combine_components( $components );
	}

	private function html_step_2(): string {
		$options         = $this->config;
		$grid_components = [
			'grid_open'     => '<div class="form-grid">',
			'language'      => sprintf(
				'<div class="form-group"><label for="swal-language">%s</label><select id="swal-language">%s</select></div>',
				esc_html__( 'Language', 'learnpress' ),
				$this->build_select_options( $options['language'] ?? [] )
			),
			'tone'          => sprintf(
				'<div class="form-group"><label for="swal-tone">%s</label><select id="swal-tone" multiple>%s</select></div>',
				esc_html__( 'Tone', 'learnpress' ),
				$this->build_select_options( $options['tone'] ?? [] )
			),
			'lesson_length' => sprintf(
				'<div class="form-group"><label for="lesson-length">%s</label><input type="number" id="lesson-length" value="400"></div>',
				esc_html__( 'Lesson length (words)', 'learnpress' )
			),
			'reading_level' => sprintf(
				'<div class="form-group"><label for="swal-levels">%s</label><select id="swal-levels">%s</select></div>',
				esc_html__( 'Reading level', 'learnpress' ),
				$this->build_select_options( $options['levels'] ?? [] )
			),
			'seo_emphasis'  => sprintf(
				'<div class="form-group"><label for="seo-emphasis">%s</label><input type="text" id="seo-emphasis" value="Basic (title/meta/heading)"></div>',
				esc_html__( 'SEO emphasis', 'learnpress' )
			),
			'keywords'      => sprintf(
				'<div class="form-group"><label for="target-keywords">%s</label><input type="text" id="target-keywords" value="html5, semantic tags, accessibility, seo on-page"></div>',
				esc_html__( 'Target keywords (comma-separated)', 'learnpress' )
			),
			'grid_close'    => '</div>',
		];

		$components = [
			'step_open'   => '<div id="step-2" class="step-content">',
			'header'      => sprintf(
				'<div class="step-header"><h2>%s</h2><p>%s</p></div>',
				esc_html__( 'Step 2 — AI Settings', 'learnpress' ),
				esc_html__( 'Configure content quality controls for ChatGPT output.', 'learnpress' )
			),
			'form_grid'   => Template::combine_components( $grid_components ),
			'nav_buttons' => sprintf(
				'<div class="navigation-buttons"><button class="btn btn-secondary prev-btn">&larr; %s</button><button class="btn btn-primary next-btn">%s &rarr;</button></div>',
				esc_html__( 'Previous', 'learnpress' ),
				esc_html__( 'Next', 'learnpress' )
			),
			'step_close'  => '</div>',
		];

		return Template::combine_components( $components );
	}

	private function html_step_3(): string {
		$grid_components = [
			'grid_open'  => '<div class="form-grid">',
			'sections'   => sprintf(
				'<div class="form-group"><label for="sections">%s</label><input type="number" id="sections" value="2" max="3"></div>',
				esc_html__( 'Sections', 'learnpress' )
			),
			'lessons'    => sprintf(
				'<div class="form-group"><label for="lessons-per-section">%s</label><input type="number" id="lessons-per-section" value="2" max="5"></div>',
				esc_html__( 'Lessons per Section', 'learnpress' )
			),
			'quizzes'    => sprintf(
				'<div class="form-group"><label for="quizzes-per-section">%s</label><input type="number" id="quizzes-per-section" value="2" max="5"></div>',
				esc_html__( 'Quizzes per Section', 'learnpress' )
			),
			'questions'  => sprintf(
				'<div class="form-group"><label for="questions-per-quiz">%s</label><input type="number" id="questions-per-quiz" value="2" max="10"></div>',
				esc_html__( 'Questions per Quiz', 'learnpress' )
			),
			'grid_close' => '</div>',
		];

		$components = [
			'step_open'       => '<div id="step-3" class="step-content">',
			'header'          => sprintf(
				'<div class="step-header"><h2>%s</h2><p>%s</p></div>',
				esc_html__( 'Step 3 — Course Structure', 'learnpress' ),
				esc_html__(
					'Define the LearnPress structure. The Prompt will be generated based on these controls.',
					'learnpress'
				)
			),
			'form_grid'       => Template::combine_components( $grid_components ),
			'generate_prompt' => sprintf(
				'<div class="generate-prompt"><button id="generate-prompt-btn" class="btn btn-primary swal2-confirm">%s</button> %s </div>',
				esc_html__( 'Generate Prompt', 'learnpress' ),
				"<img id='generate-prompt-btn-loading' src=" . esc_url( includes_url() . 'js/tinymce/skins/lightgray/img//loader.gif' ) . ' />'
			),
			'prompt_preview'  => sprintf(
				'<div class="form-group"><label for="prompt-preview">%s</label><textarea id="prompt-preview"></textarea></div>',
				esc_html__( 'Prompt Preview', 'learnpress' ),
			),
			'nav_buttons'     => sprintf(
				'<div class="navigation-buttons"><button class="btn btn-secondary prev-btn">&larr; %s</button><button id="generate-course-btn" class="btn btn-secondary" disabled>%s</button></div>',
				esc_html__( 'Previous', 'learnpress' ),
				esc_html__( 'Generate Course', 'learnpress' )
			),
			'step_close'      => '</div>',
		];

		return Template::combine_components( $components );
	}

	private function html_step_4(): string {
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
			'step_open'  => '<div id="step-4" class="step-content">',
			'content'    => $content,
			//'nav_buttons' => $this->_get_step_4_nav_buttons(),
			'step_close' => '</div>',
		];

		return Template::combine_components( $components );
	}

	private function build_select_options( array $options_arr ): string {
		$html = '';
		foreach ( $options_arr as $key => $value ) {
			$html .= sprintf( '<option value="%s">%s</option>', esc_attr( $key ), esc_html( $value ) );
		}

		return $html;
	}
}
