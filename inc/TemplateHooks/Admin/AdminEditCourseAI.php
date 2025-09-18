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
		add_action( 'admin_footer-post.php', [ $this, 'add_modal_templates_to_footer' ] );
		add_action( 'admin_footer-post-new.php', [ $this, 'add_modal_templates_to_footer' ] );
	}

	public function add_modal_templates_to_footer() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, [ 'lp_course', 'edit-lp_course' ] ) ) {
			return;
		}

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

	/**
	 * Provides data to the frontend JavaScript using wp_localize_script.
	 *
	 * @param string $hook
	 */
	public function localize_script_data( string $hook ) {
		// Only load on the course edit page.
		$screen = get_current_screen();
		wp_enqueue_script( 'lp-edit-course', plugins_url( '' ), [ 'jquery' ], '1.0.0', true );

		if ( ! $screen || $screen->id !== 'lp_course' ) {
			return;
		}

		$config     = $this->_get_config();
		$modelImage = LP_Settings::instance()->get_option( 'open_ai_image_model_type', 'dall-e-3' );

		$data = [
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'ajaxUrl'    => admin_url( 'index.php' ),
			'options'    => [
				'audience'     => $config['audience-options'] ?? [],
				'tone'         => $config['tone-options'] ?? [],
				'language'     => $config['language-options'] ?? [],
				'levels'       => $config['levels'] ?? [],
				'imageStyle'   => $config['image-style-options'] ?? [],
				'imageSize'    => ( $modelImage === 'dall-e-3' )
					? ( $config['image-dall-e-3-sizes-options'] ?? [] )
					: ( $config['image-dall-e-2-sizes-options'] ?? [] ),
				'imageQuality' => $config['image-quality-options'] ?? [],
			],
			'modelImage' => $modelImage,
			'i18n'       => [
				'createCourseTitle'       => esc_html__( 'Create Course Title', 'learnpress' ),
				'describeCourseAbout'     => esc_html__(
					'Describe what your course is about',
					'learnpress'
				),
				'describeCourseGoals'     => esc_html__(
					'Describe the main goals of your course',
					'learnpress'
				),
				'createCourseDescription' => esc_html__( 'Create Course Description', 'learnpress' ),
				'describeCourseStandOut'  => esc_html__(
					'Describe what makes this course stand out?',
					'learnpress'
				),
				'createFeaturedImage'     => esc_html__( 'Create Featured Image', 'learnpress' ),
				'style'                   => esc_html__( 'Style', 'learnpress' ),
				'imagesOrIcons'           => esc_html__(
					'Images or icons should be included',
					'learnpress'
				),
				'sizeImage'               => esc_html__( 'Size Image', 'learnpress' ),
				'qualityImage'            => esc_html__( 'Quality Image', 'learnpress' ),
				'createCourseCurriculum'  => esc_html__( 'Create Course Curriculum', 'learnpress' ),
				'sections'                => esc_html__( 'Sections', 'learnpress' ),
				'lessonsPerSection'       => esc_html__( 'Lessons per section', 'learnpress' ),
				'levels'                  => esc_html__( 'Levels', 'learnpress' ),
				'specificKeyTopics'       => esc_html__( 'Specific key topics', 'learnpress' ),
				'audience'                => esc_html__( 'Audience', 'learnpress' ),
				'tone'                    => esc_html__( 'Tone', 'learnpress' ),
				'outputLanguage'          => esc_html__( 'Output Language', 'learnpress' ),
				'outputs'                 => esc_html__( 'Outputs', 'learnpress' ),
				'generate'                => esc_html__( 'Generate', 'learnpress' ),
				'generating'              => esc_html__( 'Generating...', 'learnpress' ),
				'pleaseWait'              => esc_html__(
					'Please wait while we create the content.',
					'learnpress'
				),
				'results'                 => esc_html__( 'Generated Results', 'learnpress' ),
				'errorOccurred'           => esc_html__( 'An error occurred', 'learnpress' ),
				'applied'                 => esc_html__( 'Applied!', 'learnpress' ),
				'copied'                  => esc_html__( 'Copied!', 'learnpress' ),
				'copy'                    => esc_html__( 'Copy', 'learnpress' ),
				'apply'                   => esc_html__( 'Apply', 'learnpress' ),
			],
		];

		wp_localize_script( 'lp-edit-course', 'lpCourseAiModalData', $data );
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
				'<div class="outputs-control"><h3>%s</h3><div class="outputs-control-content"><input type="number" id="lp-ai-output-count" class="output-number-selector" value="2"></div></div>',
				esc_html__( 'Outputs', 'learnpress' )
			),
			'form_wrap_close'      => '</div>',
			'input_section_close'  => '</div>',
			'output_section_open'  => '<div class="output-section">',
			'output_header'        => '<div class="output-header"><h3>Output</h3><div class="header-icons"><button class="icon-button"><img width="18" height="18" src="https://cdn-icons-png.flaticon.com/512/6808/6808309.png" alt="Maximize"/></button></div></div>',
			'output_prompt'        => sprintf(
				'<div class="output-item" id="lp-ai-output-prompt"><p class="prompt">%s</p><textarea class="prompt-text" rows="6" id="lp-ai-output-prompt-desc" placeholder=""></textarea></div>',
				esc_html__( 'Prompt:', 'learnpress' )
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
				'<div class="outputs-control"><h3>%s</h3><div class="outputs-control-content"><input type="number" id="lp-ai-output-count" class="output-number-selector" value="2"></div></div>',
				esc_html__( 'Outputs', 'learnpress' )
			),
			'input_section_close'   => '</div>',
			'output_section_open'   => '<div class="output-section">',
			'output_header'         => sprintf(
				'<div class="output-header"><h3>Output</h3><div class="header-icons"><button class="icon-button"><img width="18" height="18" src="https://cdn-icons-png.flaticon.com/512/6808/6808309.png" alt="Maximize"/></button></div></div>'
			),
			'output_prompt'         => sprintf(
				'<div class="output-item" id="lp-ai-output-prompt"><p class="prompt">%s</p><textarea class="prompt-text" rows="6" id="lp-ai-output-prompt-desc" placeholder="e.g. A course to teach how to use LearnPress"></textarea></div>',
				esc_html__( 'Prompt:', 'learnpress' )
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

			// Course About
			'heading_topic'         => sprintf(
				'<h3>%s</h3>',
				esc_html__( 'Describe what your course is about', 'learnpress' )
			),
			'textarea_topic'        => '<textarea id="swal-course-topic" placeholder="e.g. A course to teach how to use LearnPress"></textarea>',

			// Course Goals
			'heading_goals'         => sprintf(
				'<h3>%s</h3>',
				esc_html__( 'Describe the main goals of your course', 'learnpress' )
			),
			'textarea_goals'        => '<textarea id="swal-course-goals" placeholder="e.g. A course for beginners to learn WordPress"></textarea>',

			// Audience
			'heading_audience'      => sprintf( '<h3>%s</h3>', esc_html__( 'Audience', 'learnpress' ) ),
			'select_audience_open'  => '<select id="swal-audience" multiple>',
			'options_audience'      => $build_select_options( $config['audience'] ),
			'select_audience_close' => '</select>',

			// Tone
			'heading_tone'          => sprintf( '<h3>%s</h3>', esc_html__( 'Tone', 'learnpress' ) ),
			'select_tone_open'      => '<select id="swal-tone" multiple>',
			'options_tone'          => $build_select_options( $config['tone'] ),
			'select_tone_close'     => '</select>',

			// Language
			'heading_language'      => sprintf( '<h3>%s</h3>', esc_html__( 'Output Language', 'learnpress' ) ),
			'select_language_open'  => '<select id="swal-language">',
			'options_language'      => $build_select_options( $config['language'] ),
			'select_language_close' => '</select>',

			// Outputs control
			'outputs_control'       => sprintf(
				'<div class="outputs-control"><h3>%s</h3><div class="outputs-control-content"><input type="number" id="lp-ai-output-count" class="output-number-selector" value="2"></div></div>',
				esc_html__( 'Outputs', 'learnpress' )
			),
			'input_section_close'   => '</div>',

			'output_section_open'   => '<div class="output-section">',
			'output_header'         => '<div class="output-header"><h3>Output</h3><div class="header-icons"><button class="icon-button"><img width="18" height="18" src="https://cdn-icons-png.flaticon.com/512/6808/6808309.png" alt="Maximize"/></button></div></div>',
			'output_prompt'         => sprintf(
				'<div class="output-item" id="lp-ai-output-prompt"><p class="prompt">%s</p><textarea class="prompt-text" rows="6" id="lp-ai-output-prompt-desc" placeholder="e.g. A course to teach how to use LearnPress"></textarea></div>',
				esc_html__( 'Prompt:', 'learnpress' )
			),
			'output_suggestion'     => '<div id="lp-ai-output-suggestion"></div>',
			'output_section_close'  => '</div>',
			'modal_content_close'   => '</div>',
		];

		return Template::combine_components( $components );
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
					'<div class="outputs-control"><h3>%s</h3><div class="outputs-control-content"><input type="number" id="lp-ai-output-count" class="output-number-selector" value="1" readonly></div></div>',
					esc_html__( 'Outputs', 'learnpress' )
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
					'<div class="outputs-control"><h3>%s</h3><div class="outputs-control-content"><input type="number" id="lp-ai-output-count" class="output-number-selector" value="2"></div></div>',
					esc_html__( 'Outputs', 'learnpress' )
				),
			];
			$dall_e_2_fields     = Template::combine_components( $components_dall_e_2 );
		}

		// Mảng component chính, chứa cả phần tĩnh và phần có điều kiện
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
				'<div class="output-item" id="lp-ai-output-prompt"><p class="prompt">%s</p><textarea class="prompt-text" id="lp-ai-output-prompt-desc" placeholder="e.g. A course to teach how to use LearnPress" rows="6"></textarea></div>',
				esc_html__( 'Prompt:', 'learnpress' )
			),
			'output_suggestion'    => '<div id="lp-ai-output-suggestion"></div>',

			'output_section_close' => '</div>',
			'modal_content_close'  => '</div>',
		];

		return Template::combine_components( $components );
	}
}
