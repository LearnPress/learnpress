<?php

namespace LearnPress\TemplateHooks\Admin\AI;

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LP_Debug;
use Throwable;

/**
 * Class AdminCreateCourseAITemplate
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
		try {
			if ( ! function_exists( 'get_current_screen' ) ) {
				return;
			}

			$screen = get_current_screen();
			if ( ! $screen || $screen->id != 'edit-' . LP_COURSE_CPT ) {
				return;
			}

			$this->config = Config::instance()->get( 'open-ai-modal', 'settings' );
			echo $this->html_create_course_via_ai();
			echo $this->html_creating_course();
			echo $this->html_warning_enable_ai();
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	public function html_create_course_via_ai(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-create-course-ai">',
			'wrap'                     => '<div class="lp-generate-data-ai-wrap">',
			'btn-close'                =>
				'<button type="button" class="lp-btn-close-ai-popup">
					<i class="lp-icon-remove"></i>
				</button>',
			'h2'                       => sprintf(
				'<div class="content-title">%s</div>',
				esc_html__( 'AI Course Builder for LearnPress', 'learnpress' )
			),
			'header'                   => $this->html_step_header(),
			'form'                     => '<form class="lp-form-generate-data-ai">',
			'step_1'                   => $this->html_step_1(),
			'step_2'                   => $this->html_step_2(),
			'step_3'                   => $this->html_step_3(),
			'step_4'                   => $this->html_step_4(),
			'step_5'                   => $this->html_step_5(),
			'buttons'                  => sprintf(
				'<div class="button-actions" data-step="1" data-step-max="4">
					<button class="btn btn-secondary lp-btn-step lp-hidden"
						data-step-show="2,3,4,5"
					 	data-action="prev" type="button">&larr; %s
					 </button>
					<button class="btn btn-primary lp-btn-step"
						data-step-show="1,2"
					 	data-action="next" type="button">%s &rarr;
					 </button>
					<button class="lp-button btn-primary lp-btn-generate-prompt lp-hidden"
						data-step-show="3"
						data-send="%s" type="button">%s
					</button>
					<button class="lp-button btn-primary lp-btn-call-open-ai lp-hidden"
						data-step-show="4"
						data-send="%s" type="button">%s
					</button>
					<button class="lp-button btn-primary lp-btn-create-course lp-hidden"
						data-step-show="5"
					 	data-send="%s"
						type="button">%s
					</button>
				</div>',
				esc_html__( 'Previous', 'learnpress' ),
				esc_html__( 'Next', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action' => 'openai_generate_prompt_course',
						'id_url' => 'generate_prompt_openai',
					]
				),
				esc_html__( 'Generate Prompt', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action' => 'openai_generate_data_course',
						'id_url' => 'submit_to_openai',
					]
				),
				esc_html__( 'Generate Data Course', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action' => 'openai_create_course',
						'id_url' => 'openai_create_course',
					]
				),
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
				esc_html__( 'Course Goal', 'learnpress' )
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
				esc_html__( 'Prompt', 'learnpress' )
			),
			'step_5'   => sprintf(
				'<div class="step-item" data-step="5">
					<span class="step-number">5</span>
					<span class="step-text">%s</span>
				</div>',
				esc_html__( 'Create course', 'learnpress' )
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
				esc_html__( 'Step 1 — Course Goal', 'learnpress' ),
			),
			'description' => sprintf(
				'<p class="step-description">%s</p>',
				''
			),
			'role'        => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="text" name="role_persona" placeholder="">
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Role / Persona', 'learnpress' ),
				esc_html__( 'Defines who is creating the course so AI can tailor tone, expertise, and perspective.', 'learnpress' )
			),
			'audience'    => sprintf(
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
				esc_html__( 'Identifies who will take the course so the content matches their background and skill level.', 'learnpress' )
			),
			'objective'   => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<textarea name="course_objective" placeholder="%s"></textarea>
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Course objective', 'learnpress' ),
				esc_html__( 'Enter description about course you want AI generate', 'learnpress' ),
				esc_html__( 'Specifies what learners should achieve after completing the course, guiding AI to generate outcome-aligned content.', 'learnpress' ),
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
			'reading_level' => sprintf(
				'<div class="form-group">
					<label for="swal-levels">%s</label>
					%s
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Reading level', 'learnpress' ),
				AdminTemplate::html_tom_select(
					[
						'name'    => 'reading_level',
						'options' => $options['reading_level'] ?? [],
					]
				),
				esc_html__( 'Determines the complexity of language (e.g., foundational, intermediate, advanced) so the AI can generate content appropriate for the learners’ comprehension level.', 'learnpress' )
			),
			'seo_emphasis'  => sprintf(
				'<div class="form-group">
					<label for="seo-emphasis">%s</label>
					<input type="text" name="seo_emphasis" value="">
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'SEO emphasis', 'learnpress' ),
				esc_html__( 'Determines how strongly AI should optimize content for search engines.', 'learnpress' ),
			),
			'keywords'      => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="text" name="target_keywords" value="">
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Target keywords (comma-separated)', 'learnpress' ),
				esc_html__( 'Lists the keywords AI should integrate to improve SEO performance across titles and descriptions.', 'learnpress' )
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
			'grid'            => '<div class="form-grid">',
			'sections-number' => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="section_number" value="2" min="0">
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Sections number', 'learnpress' ),
				esc_html__( 'Defines how many main sections/modules the course will include.', 'learnpress' )
			),
			/*'sections-title-length' => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="section_title_length" value="60" min="0">
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Each section title length', 'learnpress' ),
				esc_html__( 'Specifies the word limit for section titles to maintain consistency and readability.', 'learnpress' )
			),
			'sections-des-length'   => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="section_description_length" value="200" min="0">
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Each section description length', 'learnpress' ),
				esc_html__( 'Sets the word limit for section introductions to control depth and clarity.', 'learnpress' )
			),*/
			'lesson-number'   => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="lessons_per_section" value="2" min="0">
				</div>',
				esc_html__( 'Lessons per Section', 'learnpress' )
			),
			/*'lesson-title-length'   => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="lessons_title_length" value="60" min="0">
				</div>',
				esc_html__( 'Each lesson title length (words)', 'learnpress' )
			),
			'lesson-des-length'     => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="lessons_description_length" value="1000" min="0">
				</div>',
				esc_html__( 'Each lesson description length', 'learnpress' )
			),*/
			'quizzes'         => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="quizzes_per_section" value="2" min="0">
				</div>',
				esc_html__( 'Quizzes per Section', 'learnpress' )
			),
			'questions'       => sprintf(
				'<div class="form-group">
					<label for="questions-per-quiz">%s</label>
				<input type="number" name="questions_per_quiz" value="2" min="0"></div>',
				esc_html__( 'Questions per Quiz', 'learnpress' )
			),
			'grid-end'        => '</div>',
		];

		$components = [
			'step'        => '<div class="step-content" data-step="3">',
			'title'       => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 3 — Course Structure', 'learnpress' ),
			),
			'description' => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Define the LearnPress Curriculum structure. The Prompt will be generated based on these controls.', 'learnpress' )
			),
			'form_grid'   => Template::combine_components( $grid_components ),
			'step-end'    => '</div>',
		];

		return Template::combine_components( $components );
	}

	/**
	 * Generated prompt to submit OpenAI
	 *
	 * @return string
	 */
	public function html_step_4(): string {
		$components = [
			'step'     => '<div class="step-content" data-step="4">',
			'title'    => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 4 — Prompt Generated', 'learnpress' ),
			),
			'prompt'   => sprintf(
				'<div class="form-group">
					<textarea name="lp-openai-prompt-generated-field" rows="20"></textarea>
					<i>%s</i>
				</div>',
				__( 'Shows the auto-generated AI prompt, allowing further adjustments before submission.', 'learnpress' )
			),
			'step-end' => '</div>',
		];

		return Template::combine_components( $components );
	}

	/**
	 * Preview Course and create
	 *
	 * @return string
	 */
	public function html_step_5(): string {
		$components = [
			'step'        => '<div class="step-content" data-step="5">',
			'title'       => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 5 — Create Course', 'learnpress' ),
			),
			'description' => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Data preview before create course.', 'learnpress' )
			),
			'preview'     => '<div class="lp-ai-generated-results lp-ai-course-data-preview-wrap"></div>',
			'step-end'    => '</div>',
		];

		return Template::combine_components( $components );
	}

	/**
	 * HTML preview with data received from OpenAI
	 *
	 * @param array $data_received
	 *
	 * @return string
	 */
	public static function html_preview_with_data( array $data_received ): string {
		$course_title       = $data_received['course_title'] ?? '';
		$course_description = $data_received['course_description'] ?? '';
		$sections           = $data_received['sections'] ?? [];

		$html_section = '';
		foreach ( $sections as $section ) {
			$section_title = $section['section_title'] ?? '';
			$section_des   = $section['section_description'] ?? '';
			$lessons       = $section['lessons'] ?? [];
			$quizzes       = $section['quizzes'] ?? [];

			$html_lessons = '';
			foreach ( $lessons as $lesson ) {
				$lesson_title = $lesson['lesson_title'] ?? '';
				$lesson_des   = $lesson['lesson_description'] ?? '';

				$arr_lesson_components = [
					'wrap'     => '<li class="course-lesson-item">',
					'title'    => sprintf(
						'<div class="lesson-title">%s: %s</div>',
						__( 'Lesson', 'learnpress' ),
						esc_html( $lesson_title )
					),
					'des'      => sprintf( '<div class="lesson-description">%s</div>', esc_html( $lesson_des ) ),
					'wrap-end' => '</li>',
				];

				$html_lessons .= Template::combine_components( $arr_lesson_components );
			}

			// Quizzes
			$html_quizzes = '';
			foreach ( $quizzes as $quiz ) {
				$quiz_title = $quiz['quiz_title'] ?? '';
				$quiz_des   = $quiz['quiz_description'] ?? '';
				$questions  = $quiz['questions'] ?? [];

				$html_questions = '';
				foreach ( $questions as $question ) {
					$question_title = $question['question_title'] ?? '';
					$question_des   = $question['question_description'] ?? '';
					$options        = $question['options'] ?? [];

					$html_options = '';
					foreach ( $options as $option ) {
						$html_options .= sprintf( '<li class="question-option">%s</li>', esc_html( $option ) );
					}

					$arr_question_components = [
						'wrap'     => '<li class="quiz-question-item">',
						'title'    => sprintf(
							'<div class="question-title">%s: %s</div>',
							__( 'Question', 'learnpress' ),
							esc_html( $question_title )
						),
						'desc'     => sprintf( '<div class="question-desc">%s</div>', esc_html( $question_des ) ),
						'options'  => sprintf( '<ul class="course-question-options">%s</ul>', $html_options ),
						'wrap-end' => '</li>',
					];

					$html_questions .= Template::combine_components( $arr_question_components );
				}

				$arr_quiz_components = [
					'wrap'      => '<li class="course-quiz-item">',
					'title'     => sprintf(
						'<div class="quiz-title">%s: %s</div>',
						__( 'Quiz', 'learnpress' ),
						esc_html( $quiz_title )
					),
					'des'       => sprintf( '<div class="quiz-description">%s</div>', esc_html( $quiz_des ) ),
					'questions' => sprintf( '<ul class="course-questions">%s</ul>', $html_questions ),
					'wrap-end'  => '</li>',
				];

				$html_quizzes .= Template::combine_components( $arr_quiz_components );
			}

			$arr_section_components = [
				'wrap'         => '<li class="course-section-item">',
				'title'        => sprintf(
					'<div class="section-title">%s: %s</div>',
					__( 'Curriculum', 'learnpress' ),
					esc_html( $section_title )
				),
				'des'          => sprintf( '<div class="section-description">%s</div>', esc_html( $section_des ) ),
				'ul-items'     => '<ul class="course-section-items">',
				'items'        => $html_lessons . $html_quizzes,
				'ul-items-end' => '</ul>',
				'wrap-end'     => '</li>',
			];

			$html_section .= Template::combine_components( $arr_section_components );
		}

		$section = [
			'wrap'     => '<div class="lp-ai-course-data-preview">',
			'title'    => sprintf( '<div class="course-title">%s</div>', esc_html( $course_title ) ),
			'des'      => sprintf( '<div class="course-description">%s</div>', esc_html( $course_description ) ),
			'sections' => sprintf( '<ul class="course-sections">%s</ul>', $html_section ),
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML for Popup Creating course template
	 *
	 * @return string
	 */
	public function html_creating_course(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-creating-course-ai">',
			'wrap'                     => '<div class="lp-creating-course-ai-wrap">',
			'head'                     => sprintf(
				'<h2><strong>%s</strong></h2>',
				esc_html__( 'Creating your LearnPress course...', 'learnpress' )
			),
			'desc'                     => sprintf(
				'<div class="desc">%s</div>
				<div>%s</div>',
				esc_html__( 'Please wait while we prepare sections, lessons...', 'learnpress' ),
				esc_html__( 'Don\'t reload page when creating', 'learnpress' ),
			),
			'loader'                   => '<div class="loading-wrap">
												<span class="lp-loading-circle"></span>
											</div>',
			'struct'                   => sprintf(
				'<ul>
					<li>%s</li>
					<li>%s</li>
					<li>%s</li>
				</ul>',
				esc_html__( 'Creating sections...', 'learnpress' ),
				esc_html__( 'Creating lessons...', 'learnpress' ),
				esc_html__( 'Creating quizzes...', 'learnpress' ),
			),
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}

	/**
	 * HTML for Popup warning enable AI
	 *
	 * @return string
	 */
	public function html_warning_enable_ai(): string {
		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-must-enable-ai">',
			'wrap'                     => '<div class="lp-must-enable-ai-wrap">',
			'head'                     => sprintf(
				'<h2><i class="lp-icon-warning"></i><strong>%s</strong></h2>',
				esc_html__( 'OpenAI API is not connected', 'learnpress' )
			),
			'desc'                     => sprintf(
				'<div class="desc">%s</div>',
				esc_html__( 'Connect the OpenAI API to unlock LearnPress AI features.', 'learnpress' ),
			),
			'paragraph'                => sprintf(
				'<p>%s</p>
				<p class="p2">%s</p>',
				sprintf(
					'%s <a href="%s" target="_blank">%s</a>',
					__( 'Please enter your <strong>OpenAI Secret Key</strong> and enable the option <strong>Enable OpenAI</strong> option', 'learnpress' ),
					esc_url( admin_url( 'admin.php?page=learn-press-settings&tab=open-ai' ) ),
					esc_html__( 'here.', 'learnpress' ),
				),
				esc_html__(
					'LearnPress AI helps you create courses, lessons, quizzes, and
					learning content faster with intelligent AI assistance.',
					'learnpress'
				),
			),
			'help-link'                => sprintf(
				'<div class="help-link">%s<br/>%s</div>',
				sprintf(
					'%s %s',
					'<i class="lp-icon-book"></i>',
					esc_html__( 'Need help using LearnPress AI?', 'learnpress' ),
				),
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( 'https://learnpresslms.com/docs/learnpress/guide-to-using-ai-to-create-courses-in-learpress/' ),
					esc_html__( 'View LearnPress AI documentation.', 'learnpress' ),
				),
			),
			'buttons'                  => sprintf(
				'<div class="button-actions">
					<button class="button lp-btn-close-ai-popup" type="button">%s</button>
					<a class="button button-primary" href="%s" target="_blank">%s</a>
				</div>',
				esc_html__( 'Close', 'learnpress' ),
				esc_url( admin_url( 'admin.php?page=learn-press-settings&tab=open-ai' ) ),
				esc_html__( 'Go to Settings', 'learnpress' ),
			),
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		return Template::combine_components( $components );
	}
}
