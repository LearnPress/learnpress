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
 * Class AdminEditCourseCurriculumWithAITemplate
 *
 * @since 4.2.9
 * @version 1.1.0
 */
class AdminEditCourseCurriculumWithAITemplate {
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

	/**
	 * Layout popup to generate course curriculum with AI
	 */
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

			$components = [
				'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-edit-course-curriculum-ai">',
				'wrap'                     => '<div class="lp-generate-data-ai-wrap">',
				'btn-close'                =>
					'<button type="button" class="lp-btn-close-ai-popup">
					<i class="lp-icon-remove"></i>
				</button>',
				'h2'                       => sprintf(
					'<div class="content-title">%s</div>',
					esc_html__( 'Generate Course Sections Curriculum', 'learnpress' )
				),
				'header'                   => $this->html_step_header(),
				'form'                     => '<form class="lp-form-generate-data-ai">',
				'wrap-fields'              => '<div class="lp-form-fields">',
				'step_1'                   => $this->html_step_1(),
				'step_2'                   => $this->html_step_2(),
				'step_3'                   => $this->html_step_3(),
				'step_4'                   => $this->html_step_4(),
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
					<button class="lp-button btn-primary lp-btn-apply-curriculum lp-hidden"
						data-step-show="4"
						data-send="%s" type="button">%s
					</button>
				</div>',
					esc_html__( 'Previous', 'learnpress' ),
					esc_html__( 'Next', 'learnpress' ),
					Template::convert_data_to_json(
						[
							'action'         => 'openai_generate_prompt',
							'lp-prompt-type' => 'course-curriculum', // define type prompt to generate title.
							'id_url'         => 'generate_prompt_openai',
						]
					),
					esc_html__( 'Generate Prompt', 'learnpress' ),
					Template::convert_data_to_json(
						[
							'action'         => 'openai_generate_data',
							'lp-prompt-type' => 'course-curriculum', // define type prompt to generate title.
							'id_url'         => 'submit_to_openai',
						]
					),
					esc_html__( 'Generate Sections Course', 'learnpress' ),
					Template::convert_data_to_json(
						[
							'action' => 'openai_create_course_sections',
							'id_url' => 'openai_create_course_sections',
						]
					),
					esc_html__( 'Apply Sections Data To Curriculum', 'learnpress' ),
				),
				'form-end'                 => '</form>',
				'wrap-end'                 => '</div>',
				'wrap-script-template-end' => '</script>',
			];

			echo Template::combine_components( $components );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	public function html_step_header(): string {
		$components = [
			'wrap'     => '<div class="step-header">',
			'step_1'   => sprintf(
				'<div class="step-item active" data-step="1"><span class="step-number">1</span><span class="step-text">%s</span></div>',
				esc_html__( 'Curriculum Goal', 'learnpress' )
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

	public function html_step_1(): string {
		$options = $this->config;

		$components = [
			'step'            => '<div class="step-content active" data-step="1">',
			'title'           => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 1 — Curriculum Goal', 'learnpress' ),
			),
			'description'     => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Provide the main goal for the curriculum so the system can generate aligned sections and lessons.', 'learnpress' )
			),
			'post-title'      => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input class="title-refer" type="text" name="post-title" readonly />
					<p class="lp-ai-warning-refer lp-hidden"><i class="lp-icon-warning"></i>%s</p>
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Title refer', 'learnpress' ),
				esc_html__( 'You can edit the course title to better suit the curriculum generation.', 'learnpress' ),
				esc_html__( 'The course title is automatically imported from the previous step. It will guide the AI to build a structured curriculum.', 'learnpress' ),
			),
			'post-content'    => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<textarea class="description-refer" type="text" name="post-content" readonly></textarea>
					<p class="lp-ai-warning-refer lp-hidden"><i class="lp-icon-warning"></i>%s</p>
					<p class="field-description">%s</p>
				</div>',
				esc_html__( 'Description refer', 'learnpress' ),
				esc_html__( 'You can edit the course description to better suit the curriculum generation.', 'learnpress' ),
				esc_html__( 'The course description is automatically imported from the previous step. It will guide the AI to build a structured curriculum.', 'learnpress' )
			),
			'goal'            => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<textarea type="text" name="goal" placeholder="%s"></textarea>
				</div>',
				esc_html__( 'Goal', 'learnpress' ),
				esc_html__( 'Defines the main objective of your curriculum. This helps the AI generate course sections and lessons that align with the intended learning outcomes.', 'learnpress' )
			),
			'form-grid'       => '<div class="form-grid">',
			'sections-number' => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="section_number" value="2" min="0">
				</div>',
				esc_html__( 'Sections number', 'learnpress' )
			),
			/*'sections-title-length' => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="section_title_length" value="60" min="0">
				</div>',
				esc_html__( 'Each section title length', 'learnpress' )
			),
			'sections-des-length'   => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="section_description_length" value="50" min="0">
				</div>',
				esc_html__( 'Each section description length', 'learnpress' )
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
				esc_html__( 'Each lesson title length', 'learnpress' )
			),*/
			'quizzes'         => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="quizzes_per_section" value="2" min="0">
				</div>',
				esc_html__( 'Quizzes per Section', 'learnpress' )
			),
			/*'quiz-title-length'     => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="quiz_title_length" value="60" min="0">
				</div>',
				esc_html__( 'Each quiz title length', 'learnpress' )
			),*/
			'form-grid-end'   => '</div>',
			'step_close'      => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_step_2(): string {
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
				esc_html__( 'Identifies who will take the course so the content matches their background and skill level.', 'learnpress' )
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
			'form-grid-end' => '</div>',
			'step_close'    => '</div>',
		];

		return Template::combine_components( $components );
	}

	public function html_step_3(): string {
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

	public function html_step_4(): string {
		$options = $this->config;

		$components = [
			'step'       => '<div class="step-content" data-step="4">',
			'title'      => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 4 — Result', 'learnpress' ),
			),
			'results'    => '<div class="lp-ai-generated-results lp-ai-course-data-preview-wrap"></div>',
			'step_close' => '</div>',
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
		$sections = $data_received['sections'] ?? [];

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
					'title'    => sprintf( '<div class="lesson-title">%s</div>', esc_html( $lesson_title ) ),
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
						'title'    => sprintf( '<div class="question-title">%s</div>', esc_html( $question_title ) ),
						'desc'     => sprintf( '<div class="question-desc">%s</div>', esc_html( $question_des ) ),
						'options'  => sprintf( '<ul class="course-question-options">%s</ul>', $html_options ),
						'wrap-end' => '</li>',
					];

					$html_questions .= Template::combine_components( $arr_question_components );
				}

				$arr_quiz_components = [
					'wrap'      => '<div class="course-quiz-item">',
					'title'     => sprintf( '<div class="quiz-title">%s</div>', esc_html( $quiz_title ) ),
					'des'       => sprintf( '<div class="quiz-description">%s</div>', esc_html( $quiz_des ) ),
					'questions' => sprintf( '<ul class="course-questions">%s</ul>', $html_questions ),
					'wrap-end'  => '</div>',
				];

				$html_quizzes .= Template::combine_components( $arr_quiz_components );
			}

			$arr_section_components = [
				'wrap'     => '<li class="course-section-item">',
				'title'    => sprintf( '<div class="section-title">%s</div>', esc_html( $section_title ) ),
				'des'      => sprintf( '<div class="section-description">%s</div>', esc_html( $section_des ) ),
				'lessons'  => sprintf( '<ul class="course-section-items">%s</ul>', $html_lessons ),
				'quizzes'  => sprintf( '<ul class="course-section-items">%s</ul>', $html_quizzes ),
				'wrap-end' => '</li>',
			];

			$html_section .= Template::combine_components( $arr_section_components );
		}

		$section = [
			'wrap'     => '<div class="lp-ai-course-data-preview">',
			'sections' => sprintf( '<ul class="course-sections">%s</ul>', $html_section ),
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $section );
	}
}
