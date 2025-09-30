<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LP_Settings;

/**
 * Class AdminEditCourseAI
 *
 * @since 4.2.9
 * @version 1.1.0
 */
class AdminEditCourseAI {



	use Singleton;

	/**
	 * @var array|null
	 */
	private $config = null;

	/**
	 * Init hooks.
	 */
	public function init() {
		add_action( 'current_screen', [ $this, 'add_modal_templates_to_footer' ] );
	}

	public function add_modal_templates_to_footer() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, [ 'lp_course', 'edit-lp_course' ] ) ) {
			return;
		}

		$ai_course_modal_html = $this->_get_html_ai_course_modal();
		printf(
			'<script type="text/template" id="lp-ai-course-modal-template">%s</script>',
			$ai_course_modal_html
		);
		$title_modal_html = $this->_get_html_course_title_modal();
		printf(
			'<script type="text/template" id="lp-ai-title-modal-template">%s</script>',
			$title_modal_html
		);

		$description_modal_html = $this->_get_html_course_description_modal();
		printf(
			'<script type="text/template" id="lp-ai-description-modal-template">%s</script>',
			$description_modal_html
		);

		$course_curriculum_modal_html = $this->_get_html_course_curriculum_modal();
		printf(
			'<script type="text/template" id="lp-ai-course-curriculum-modal-template">%s</script>',
			$course_curriculum_modal_html
		);

		$course_feature_image_modal_html = $this->_get_html_course_feature_image_modal();
		printf(
			'<script type="text/template" id="lp-ai-course-feature-image-modal-template">%s</script>',
			$course_feature_image_modal_html
		);
	}

	/**
	 * Get config for OpenAI settings.
	 *
	 * @return array
	 */
	private function _get_config(): array {
		if ( is_null( $this->config ) ) {
			$this->config = Config::instance()->get( 'open-ai-modal', 'settings' );
		}

		return $this->config;
	}

	private function _get_html_course_curriculum_modal(): string {
		$options = $this->_get_config();

		$build_select_options = function ( array $options_arr ) {
			$html = '';
			foreach ( $options_arr as $key => $value ) {
				$html .= sprintf( '<option value="%s">%s</option>', esc_attr( $key ), esc_html( $value ) );
			}
			return $html;
		};

		$components = [
			'modal_content_open'   => '<div class="modal-content">',
			'input_section_open'   => '<div class="input-section">',

			'form_wrap_open'       => '<div class="lp-ai-modal-form lp-ai-modal-grid">',
			'sections_input'       => sprintf(
				'<div><label>%s</label><input type="number" id="swal-curriculum-sections" class="swal2-input" value="2"></div>',
				esc_html__( 'Sections', 'learnpress' )
			),
			'lessons_input'        => sprintf(
				'<div><label>%s</label><input type="number" id="swal-curriculum-lessons" class="swal2-input" value="2"></div>',
				esc_html__( 'Lessons per section', 'learnpress' )
			),
			'quiz_input'           => sprintf(
				'<div><label>%s</label><input type="number" id="swal-curriculum-quiz" class="swal2-input" value="1"></div>',
				esc_html__( 'Quiz per section', 'learnpress' )
			),
			'levels_select'        => sprintf(
				'<div><label>%s</label><select id="swal-levels">%s</select></div>',
				esc_html__( 'Levels', 'learnpress' ),
				$build_select_options( $options['levels'] ?? [] )
			),
			'topics_textarea'      => sprintf(
				'<div class="full-width"><label>%s</label><textarea id="swal-curriculum-topics" class="swal2-textarea" placeholder="e.g. Common mistakes, best practices"></textarea></div>',
				esc_html__( 'Specific key topics', 'learnpress' )
			),
			'language_select'      => sprintf(
				'<div><label>%s</label><select id="swal-language">%s</select></div>',
				esc_html__( 'Output Language', 'learnpress' ),
				$build_select_options( $options['language'] ?? [] )
			),
			'outputs_control'      => sprintf(
				'<div class="outputs-control"><h3>%s</h3><div class="outputs-control-content"><input type="number" id="lp-ai-output-count" class="output-number-selector" value="2">%s</div></div>',
				esc_html__( 'Outputs', 'learnpress' ),
				$this->getHtmlLoading()
			),
			'form_wrap_close'      => '</div>',
			'input_section_close'  => '</div>',
			'output_section_open'  => '<div class="output-section">',
			'output_header'        => '<div class="output-header"><h3>Output</h3><div class="header-icons"><button class="icon-button"><img width="18" height="18" src="https://cdn-icons-png.flaticon.com/512/6808/6808309.png" alt="Maximize"/></button></div></div>',
			'output_prompt'        => sprintf(
				'<div class="output-item" id="lp-ai-output-prompt"><p class="prompt">%s</p><textarea class="prompt-text" rows="6" id="lp-ai-output-prompt-desc" placeholder=""></textarea>%s</div>',
				esc_html__( 'Prompt:', 'learnpress' ),
				$this->getHtmlLoading()
			),
			'output_suggestion'    => '<div id="lp-ai-output-suggestion"></div>',
			'output_section_close' => '</div>',
			'modal_content_close'  => '</div>',
		];

		return Template::combine_components( $components );
	}

	private function _get_html_course_description_modal(): string {

		$options = $this->_get_config();

		$build_select_options = function ( $options_arr ) {
			$html = '';
			foreach ( $options_arr as $key => $value ) {
				$html .= sprintf( '<option value="%s">%s</option>', esc_attr( $key ), esc_html( $value ) );
			}

			return $html;
		};

		$components = [
			'modal_content_open'    => '<div class="modal-content">',
			'input_section_open'    => '<div class="input-section">',
			// Stand out description
			'heading_stand_out'     => sprintf(
				'<h3>%s</h3>',
				esc_html__( 'Describe what makes this course stand out?', 'learnpress' )
			),
			'textarea_stand_out'    => '<textarea id="swal-course-desc" placeholder="e.g. A course to teach how to use LearnPress"></textarea>',
			// Audience
			'heading_audience'      => sprintf( '<h3>%s</h3>', esc_html__( 'Audience', 'learnpress' ) ),
			'select_audience_open'  => '<select id="swal-audience" multiple>',
			'options_audience'      => $build_select_options( $options['audience'] ?? [] ),
			'select_audience_close' => '</select>',
			'heading_characters'    => sprintf( '<h3>%s</h3>', esc_html__( 'Description Length (characters)', 'learnpress' ) ),
			'input_characters'      => '<input type="number" id="lp-ai-course-desc-characters" class="swal2-input" value="1000">',

			// Tone
			'heading_tone'          => sprintf( '<h3>%s</h3>', esc_html__( 'Tone', 'learnpress' ) ),
			'select_tone_open'      => '<select id="swal-tone" multiple>',
			'options_tone'          => $build_select_options( $options['tone'] ?? [] ),
			'select_tone_close'     => '</select>',
			// Language
			'heading_language'      => sprintf( '<h3>%s</h3>', esc_html__( 'Output Language', 'learnpress' ) ),
			'select_language_open'  => '<select id="swal-language">',
			'options_language'      => $build_select_options( $options['language'] ?? [] ),
			'select_language_close' => '</select>',
			// Outputs control
			'outputs_control'       => sprintf(
				'<div class="outputs-control"><h3>%s</h3><div class="outputs-control-content"><input type="number" id="lp-ai-output-count" class="output-number-selector" value="2">%s</div></div>',
				esc_html__( 'Outputs', 'learnpress' ),
				$this->getHtmlLoading()
			),
			'input_section_close'   => '</div>',
			'output_section_open'   => '<div class="output-section">',
			'output_header'         => sprintf(
				'<div class="output-header"><h3>Output</h3><div class="header-icons"><button class="icon-button"><img width="18" height="18" src="https://cdn-icons-png.flaticon.com/512/6808/6808309.png" alt="Maximize"/></button></div></div>'
			),
			'output_prompt'         => sprintf(
				'<div class="output-item" id="lp-ai-output-prompt"><p class="prompt">%s</p><textarea class="prompt-text" rows="6" id="lp-ai-output-prompt-desc" placeholder="e.g. A course to teach how to use LearnPress"></textarea>%s</div>',
				esc_html__( 'Prompt:', 'learnpress' ),
				$this->getHtmlLoading()
			),
			'output_suggestion'     => '<div id="lp-ai-output-suggestion"></div>',
			'output_section_close'  => '</div>',
			'modal_content_close'   => '</div>',
		];

		return Template::combine_components( $components );
	}

	private function _get_html_course_title_modal(): string {
		$config = $this->_get_config();

		$build_select_options = function ( $options_arr ) {
			$html = '';
			foreach ( $options_arr as $key => $value ) {
				$html .= sprintf( '<option value="%s">%s</option>', esc_attr( $key ), esc_html( $value ) );
			}
			return $html;
		};

		$components = [
			'modal_content_open'    => '<div class="modal-content">',
			'input_section_open'    => '<div class="input-section">',

			'heading_topic'         => sprintf(
				'<h3>%s</h3>',
				esc_html__( 'Describe what your course is about', 'learnpress' )
			),
			'textarea_topic'        => '<textarea id="swal-course-topic" placeholder="e.g. A course to teach how to use LearnPress"></textarea>',

			'heading_goals'         => sprintf(
				'<h3>%s</h3>',
				esc_html__( 'Describe the main goals of your course', 'learnpress' )
			),
			'textarea_goals'        => '<textarea id="swal-course-goals" placeholder="e.g. A course for beginners to learn WordPress"></textarea>',
			'heading_characters'    => sprintf( '<h3>%s</h3>', esc_html__( 'Title Length (characters)', 'learnpress' ) ),
			'input_characters'      => '<input type="number" id="lp-ai-course-title-characters" class="swal2-input" value="60">',
			'heading_audience'      => sprintf( '<h3>%s</h3>', esc_html__( 'Audience', 'learnpress' ) ),
			'select_audience_open'  => '<select id="swal-audience" multiple>',
			'options_audience'      => $build_select_options( $config['audience'] ),
			'select_audience_close' => '</select>',
			'heading_tone'          => sprintf( '<h3>%s</h3>', esc_html__( 'Tone', 'learnpress' ) ),
			'select_tone_open'      => '<select id="swal-tone" multiple>',
			'options_tone'          => $build_select_options( $config['tone'] ),
			'select_tone_close'     => '</select>',
			'heading_language'      => sprintf( '<h3>%s</h3>', esc_html__( 'Output Language', 'learnpress' ) ),
			'select_language_open'  => '<select id="swal-language">',
			'options_language'      => $build_select_options( $config['language'] ),
			'select_language_close' => '</select>',
			'outputs_control'       => sprintf(
				'<div class="outputs-control"><h3>%s</h3><div class="outputs-control-content"><input type="number" id="lp-ai-output-count" class="output-number-selector" value="2">%s</div></div>',
				esc_html__( 'Outputs', 'learnpress' ),
				$this->getHtmlLoading()
			),
			'input_section_close'   => '</div>',
			'output_section_open'   => '<div class="output-section">',
			'output_header'         => '<div class="output-header"><h3>Output</h3><div class="header-icons"><button class="icon-button"><img width="18" height="18" src="https://cdn-icons-png.flaticon.com/512/6808/6808309.png" alt="Maximize"/></button></div></div>',
			'output_prompt'         => sprintf(
				'<div class="output-item" id="lp-ai-output-prompt"><p class="prompt">%s</p><textarea class="prompt-text" rows="6" id="lp-ai-output-prompt-desc" placeholder="e.g. A course to teach how to use LearnPress"></textarea>%s</div>',
				esc_html__( 'Prompt:', 'learnpress' ),
				$this->getHtmlLoading()
			),
			'output_suggestion'     => '<div id="lp-ai-output-suggestion"></div>',
			'output_section_close'  => '</div>',
			'modal_content_close'   => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function getHtmlLoading() {
		return '<div class="fui-loading-spinner-3">
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
</div>';
	}

	private function _get_html_course_feature_image_modal(): string {
		$config     = $this->_get_config();
		$modelImage = LP_Settings::instance()->get_option( 'open_ai_image_model_type', 'dall-e-3' );
		$postId     = $_GET['post'] ?? 0;
		$image_url  = get_the_post_thumbnail_url( $postId, 'full' );

		$build_select_options = function ( $options_arr ) {
			$html = '';
			foreach ( $options_arr as $key => $value ) {
				$html .= sprintf( '<option value="%s">%s</option>', esc_attr( $key ), esc_html( $value ) );
			}
			return $html;
		};

		$dall_e_3_fields = '';
		if ( $modelImage === 'dall-e-3' ) {
			$components_dall_e_3 = [
				'heading_size'    => sprintf( '<h3>%s</h3>', esc_html__( 'Size Image', 'learnpress' ) ),
				'select_size'     => sprintf(
					'<select id="lp-ai-image-size" class="lp-tom-select"><option value="">%s</option>%s</select>',
					esc_html__( 'Select image size', 'learnpress' ),
					$build_select_options( $config['image-dall-e-3-sizes'] ?? [] )
				),
				'heading_quality' => sprintf( '<h3>%s</h3>', esc_html__( 'Quality Image', 'learnpress' ) ),
				'select_quality'  => sprintf(
					'<select id="lp-ai-image-quality" class="lp-tom-select"><option value="">%s</option>%s</select>',
					esc_html__( 'Select Quality Image', 'learnpress' ),
					$build_select_options( $config['image-quality'] ?? [] )
				),
				'outputs_control' => sprintf(
					'<div class="outputs-control"><h3>%s</h3><div class="outputs-control-content"><input type="number" id="lp-ai-output-count" class="output-number-selector" value="1" readonly>%s</div></div>',
					esc_html__( 'Outputs', 'learnpress' ),
					$this->getHtmlLoading()
				),
			];
			$dall_e_3_fields     = Template::combine_components( $components_dall_e_3 );
		}

		$dall_e_2_fields = '';
		if ( $modelImage === 'dall-e-2' ) {
			$mask_logo_html = '';
			if ( ! empty( $image_url ) ) {
				$mask_logo_html = sprintf(
					'<div class="branding-logo-container"><h3>%s</h3><div class="file-upload-wrapper"><input id="lp-ai-mask-logo" name="mask-logo" type="file"/><span id="logo-file-name" class="file-name"></span><label for="lp-ai-mask-logo" class="lp-ai-upload-button">%s</label></div></div>',
					esc_html__( 'Mask Logo', 'learnpress' ),
					esc_html__( 'Upload', 'learnpress' )
				);
			}

			$components_dall_e_2 = [
				'mask_logo'       => $mask_logo_html,
				'heading_size'    => sprintf( '<h3>%s</h3>', esc_html__( 'Size Image', 'learnpress' ) ),
				'select_size'     => sprintf(
					'<select id="lp-ai-image-size" class="lp-tom-select"><option value="">%s</option>%s</select>',
					esc_html__( 'Select image size', 'learnpress' ),
					$build_select_options( $config['image-dall-e-2-sizes'] ?? [] )
				),
				'outputs_control' => sprintf(
					'<div class="outputs-control"><h3>%s</h3><div class="outputs-control-content"><input type="number" id="lp-ai-output-count" class="output-number-selector" value="2">%s</div></div>',
					esc_html__( 'Outputs', 'learnpress' ),
					$this->getHtmlLoading()
				),
			];
			$dall_e_2_fields     = Template::combine_components( $components_dall_e_2 );
		}

		$components = [
			'modal_content_open'   => '<div class="modal-content">',
			'input_section_open'   => '<div class="input-section">',

			'heading_style'        => sprintf( '<h3>%s</h3>', esc_html__( 'Style', 'learnpress' ) ),
			'select_style'         => sprintf(
				'<select id="lp-ai-image-style" class="lp-tom-select" multiple>%s</select>',
				$build_select_options( $config['image-style'] ?? [] )
			),

			'heading_desc'         => sprintf( '<h3>%s</h3>', esc_html__( 'Images or icons should be included', 'learnpress' ) ),
			'textarea_desc'        => '<textarea id="lp-ai-image-desc" placeholder="e.g. a computer"></textarea>',

			'dall_e_3_fields'      => $dall_e_3_fields,
			'dall_e_2_fields'      => $dall_e_2_fields,

			'input_section_close'  => '</div>',
			'output_section_open'  => '<div class="output-section">',

			'output_prompt'        => sprintf(
				'<div class="output-item" id="lp-ai-output-prompt"><p class="prompt">%s</p><textarea class="prompt-text" id="lp-ai-output-prompt-desc" placeholder="e.g. A course to teach how to use LearnPress" rows="6"></textarea>%s</div>',
				esc_html__( 'Prompt:', 'learnpress' ),
				$this->getHtmlLoading()
			),
			'output_suggestion'    => '<div id="lp-ai-output-suggestion"></div>',

			'output_section_close' => '</div>',
			'modal_content_close'  => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function _get_html_ai_course_modal(): string {

		$components = [
			'container_open'     => '<div class="course-builder-container">',
			'stepper_header'     => $this->_get_html_stepper_header(),
			'form_content_open'  => '<div class="form-content">',
			'step_1'             => $this->_get_html_step_1(),
			'step_2'             => $this->_get_html_step_2(),
			'step_3'             => $this->_get_html_step_3(),
			'step_4'             => $this->_get_html_step_4(),
			'form_content_close' => '</div>',
			'container_close'    => '</div>',
		];

		return Template::combine_components( $components );
	}

	private function _get_html_stepper_header(): string {
		$components = [
			'header_open'  => '<div class="stepper-header">',
			'step_1'       => sprintf(
				'<div class="stepper-item active" data-step="1"><span class="step-number">1</span> <span class="step-text">%s</span></div>',
				esc_html__( 'Course Intent', 'learnpress' )
			),
			'step_2'       => sprintf(
				'<div class="stepper-item" data-step="2"><span class="step-number">2</span> <span class="step-text">%s</span></div>',
				esc_html__( 'AI Settings', 'learnpress' )
			),
			'step_3'       => sprintf(
				'<div class="stepper-item" data-step="3"><span class="step-number">3</span> <span class="step-text">%s</span></div>',
				esc_html__( 'Course Structure', 'learnpress' )
			),
			'step_4'       => sprintf(
				'<div class="stepper-item" data-step="4"><span class="step-number">4</span> <span class="step-text">%s</span></div>',
				esc_html__( 'Generate', 'learnpress' )
			),
			'header_close' => '</div>',
		];

		return Template::combine_components( $components );
	}

	private function _get_html_step_1(): string {
		$components = [
			'step_open'       => '<div id="step-1" class="step-content active">',
			'header'          => sprintf(
				'<div class="step-header"><h2>%s</h2><p>%s</p></div>',
				esc_html__( 'Step 1 — Course Intent', 'learnpress' ),
				esc_html__( 'Define the course goal and the authoring role/persona.', 'learnpress' )
			),
			'role_group'      => sprintf(
				'<div class="form-group"><label for="role-persona">%s</label><input type="text" id="role-persona" value="Front-end Trainer + Instructional Designer"></div>',
				esc_html__( 'Role / Persona (critical)', 'learnpress' )
			),
			'audience_group'  => sprintf(
				'<div class="form-group"><label for="target-audience">%s</label><input type="text" id="target-audience" value="Complete beginners"></div>',
				esc_html__( 'Target audience', 'learnpress' )
			),
			'objective_group' => sprintf(
				'<div class="form-group"><label for="course-objective">%s</label><textarea id="course-objective">Act as a Front-end Training Expert + Instructional Designer. Create a Basic HTML course for absolute beginners. Learners should understand HTML5 structure, semantic tags, accessibility basics, and simple SEO on-page.</textarea></div>',
				esc_html__( 'Course objective', 'learnpress' )
			),
			'nav_buttons'     => sprintf(
				'<div class="navigation-buttons"><button class="btn btn-primary next-btn">%s &rarr;</button></div>',
				esc_html__( 'Next', 'learnpress' )
			),
			'step_close'      => '</div>',
		];

		return Template::combine_components( $components );
	}

	private function _get_html_step_2(): string {
		$grid_components = [
			'grid_open'     => '<div class="form-grid">',
			'language'      => sprintf(
				'<div class="form-group"><label for="language">%s</label><input type="text" id="language" value="English"></div>',
				esc_html__( 'Language', 'learnpress' )
			),
			'tone'          => sprintf(
				'<div class="form-group"><label for="tone">%s</label><input type="text" id="tone" value="Friendly"></div>',
				esc_html__( 'Tone', 'learnpress' )
			),
			'lesson_length' => sprintf(
				'<div class="form-group"><label for="lesson-length">%s</label><input type="number" id="lesson-length" value="400"></div>',
				esc_html__( 'Lesson length (words)', 'learnpress' )
			),
			'reading_level' => sprintf(
				'<div class="form-group"><label for="reading-level">%s</label><input type="text" id="reading-level" value="Beginner"></div>',
				esc_html__( 'Reading level', 'learnpress' )
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

	private function _get_html_step_3(): string {
		$grid_components = [
			'grid_open'  => '<div class="form-grid">',
			'sections'   => sprintf(
				'<div class="form-group"><label for="sections">%s</label><input type="number" id="sections" value="1"></div>',
				esc_html__( 'Sections', 'learnpress' )
			),
			'lessons'    => sprintf(
				'<div class="form-group"><label for="lessons-per-section">%s</label><input type="number" id="lessons-per-section" value="1"></div>',
				esc_html__( 'Lessons per Section', 'learnpress' )
			),
			'quizzes'    => sprintf(
				'<div class="form-group"><label for="quizzes-per-section">%s</label><input type="number" id="quizzes-per-section" value="1"></div>',
				esc_html__( 'Quizzes per Section', 'learnpress' )
			),
			'questions'  => sprintf(
				'<div class="form-group"><label for="questions-per-quiz">%s</label><input type="number" id="questions-per-quiz" value="3"></div>',
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
				'<button id="generate-prompt-btn" class="btn btn-primary" style="margin-bottom: 20px;">%s</button>',
				esc_html__( 'Generate Prompt', 'learnpress' )
			),
			'prompt_preview'  => sprintf(
				'<div class="form-group"><label for="prompt-preview">%s</label><textarea id="prompt-preview" readonly>%s</textarea></div>',
				esc_html__( 'Prompt Preview', 'learnpress' ),
				esc_html__( 'The generated prompt will appear here...', 'learnpress' )
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

	private function _get_step_4_header(): string {
		$components = [
			'header_open'  => '<div class="step-header">',
			'title'        => sprintf( '<h2>%s</h2>', esc_html__( 'Step 4 — Generated Course', 'learnpress' ) ),
			'subtitle'     => sprintf(
				'<p>%s</p>',
				esc_html__( 'Review the generated course structure and content below.', 'learnpress' )
			),
			'header_close' => '</div>',
		];
		return Template::combine_components( $components );
	}

	private function _get_step_4_left_panel(): string {
		$course_details_components = [
			'title'          => '<h3>HTML Basics: A Beginner\'s Guide</h3>',
			'cover'          => sprintf(
				'<div class="course-cover-placeholder">%s</div>',
				esc_html__( 'Course Cover', 'learnpress' )
			),
			'info_title'     => sprintf( '<p class="section-title">%s</p>', esc_html__( 'Course Info', 'learnpress' ) ),
			'info_p'         => sprintf(
				'<p>%s</p>',
				esc_html__(
					'This course covers the fundamentals of web development with HTML. This course is designed for complete beginners who want to understand web structure, semantic tags, and basic SEO on-page practices. By the end, you will be able to build and publish a simple one-page portfolio site.',
					'learnpress'
				)
			),
			'outcomes_title' => sprintf(
				'<p class="section-title">%s</p>',
				esc_html__( 'Key Learning Outcomes', 'learnpress' )
			),
			'outcomes_list'  => sprintf(
				'<ul>
                <li>%s</li>
                <li>%s</li>
                <li>%s</li>
                <li>%s</li>
            </ul>',
				esc_html__( 'Understand basic HTML5 structure and semantic tags', 'learnpress' ),
				esc_html__( 'Format text: headings, paragraphs, lists, links, and images', 'learnpress' ),
				esc_html__( 'Build accessible forms with labels and attributes', 'learnpress' ),
				esc_html__( 'Apply multimedia (e.g., audio, video, iframes, etc. text)', 'learnpress' )
			),
		];

		$course_details = Template::combine_components( $course_details_components );

		$components = [
			'panel_open'  => '<div class="step4-left-panel">',
			'details'     => '<div class="course-details">' . $course_details . '</div>',
			'panel_close' => '</div>',
		];

		return Template::combine_components( $components );
	}


	private function _get_step_4_right_panel(): string {
		$summary_list = '
        <ul class="summary-list">
            <li><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg> 3 Sections</li>
            <li><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg> 6 Lessons</li>
            <li><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg> 3 Quizzes</li>
            <li><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg> 1 Final Project</li>
        </ul>';

		$action_buttons = sprintf(
			'<div style="display: flex; flex-direction: column; gap: 10px; margin-top: 24px;">
            <button class="btn btn-primary">%s</button>
            <button class="btn btn-secondary">%s</button>
        </div>',
			esc_html__( 'Approve & Save Course', 'learnpress' ),
			esc_html__( 'Regenerate Course', 'learnpress' )
		);

		$summary_panel_components = [
			'panel_open'  => '<div class="summary-panel">',
			'title'       => sprintf( '<h3>%s</h3>', esc_html__( 'Generated course summary', 'learnpress' ) ),
			'list'        => $summary_list,
			'buttons'     => $action_buttons,
			'panel_close' => '</div>',
		];
		$summary_panel            = Template::combine_components( $summary_panel_components );

		$components = [
			'panel_open'  => '<div class="step4-right-panel">',
			'summary'     => $summary_panel,
			'panel_close' => '</div>',
		];
		return Template::combine_components( $components );
	}

	private function _get_step_4_nav_buttons(): string {
		return sprintf(
			'<div class="navigation-buttons"><button class="btn btn-secondary prev-btn">&larr; %s</button></div>',
			esc_html__( 'Previous', 'learnpress' )
		);
	}


	private function _get_html_step_4(): string {
		$layout_components = [
			'layout_open'  => '<div class="step4-layout">',
			'left_panel'   => $this->_get_step_4_left_panel(),
			'right_panel'  => $this->_get_step_4_right_panel(),
			'layout_close' => '</div>',
		];
		$layout            = Template::combine_components( $layout_components );

		$content_components = [
			'header' => $this->_get_step_4_header(),
			'layout' => $layout,
		];
		$content            = Template::combine_components( $content_components );

		$components = [
			'step_open'   => '<div id="step-4" class="step-content">',
			'content'     => $content,
			'nav_buttons' => $this->_get_step_4_nav_buttons(),
			'step_close'  => '</div>',
		];

		return Template::combine_components( $components );
	}
}
