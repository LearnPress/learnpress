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

	public function layout_popup() {
		$screen  = get_current_screen();
		$screens = [
			LP_COURSE_CPT,
		];
		if ( ! $screen || in_array( $screen, $screens ) ) {
			return;
		}

		$this->config = Config::instance()->get( 'open-ai-modal', 'settings' );

		$data_dummy = '[{&quot;sections&quot;:[{&quot;section_title&quot;:&quot;Mastering Core Concepts in Advanced PHP 8&quot;,&quot;section_description&quot;:&quot;This section dives deep into the core functionality and logic-enhancing tools that set advanced PHP 8 programming apart. Students will explore complex constructs like closures and anonymous functions, while also learning how to utilize generators for efficient memory management.&quot;,&quot;lessons&quot;:[{&quot;lesson_title&quot;:&quot;Unpacking Closures and Anonymous Functions&quot;,&quot;lesson_description&quot;:&quot;In this lesson, students will explore the difference between closures and anonymous functions, and learn how both enhance code modularity and reusability. Through hands-on examples, students will create and invoke closures, understand variable scope, and learn how to pass closures as parameters to other functions. By the end, students will be able to integrate closures and anonymous functions into real-world PHP applications to improve code organization and performance.&quot;},{&quot;lesson_title&quot;:&quot;Harnessing the Power of Generators&quot;,&quot;lesson_description&quot;:&quot;This lesson introduces students to generators in PHP 8—an efficient way of iterating through large datasets without consuming excessive memory. Students will learn how to use the \u0027yield\u0027 keyword, build simple and recursive generators, and compare generators with traditional iterators. Through contextual examples, this lesson demonstrates how generators can streamline complex data processing tasks while keeping applications fast and efficient.&quot;}],&quot;quizzes&quot;:[{&quot;quiz_title&quot;:&quot;Core PHP 8 Functions Quiz&quot;,&quot;quiz_description&quot;:&quot;Assess your understanding of closures, anonymous functions, and generators.&quot;,&quot;questions&quot;:[{&quot;question_title&quot;:&quot;What is a key advantage of using a closure in PHP?&quot;,&quot;question_description&quot;:&quot;Choose the most accurate benefit from the options below.&quot;,&quot;options&quot;:[&quot;Consumes more memory&quot;,&quot;Must be declared globally&quot;,&quot;Allows access to outer scope variables&quot;,&quot;Cannot be passed to other functions&quot;],&quot;correct_answer&quot;:&quot;Allows access to outer scope variables&quot;},{&quot;question_title&quot;:&quot;What keyword is used to build a generator function in PHP?&quot;,&quot;question_description&quot;:&quot;Select the keyword that enables generator behavior.&quot;,&quot;options&quot;:[&quot;return&quot;,&quot;yield&quot;,&quot;generate&quot;,&quot;function&quot;],&quot;correct_answer&quot;:&quot;yield&quot;}]}]},{&quot;section_title&quot;:&quot;Putting PHP 8 Features Into Practice&quot;,&quot;section_description&quot;:&quot;This section focuses on leveraging the newest and most powerful features of PHP 8 for practical and scalable application development. Students will explore advanced tools such as match expressions and named arguments through real-world examples and scenarios.&quot;,&quot;lessons&quot;:[{&quot;lesson_title&quot;:&quot;Advanced Control Flow with Match Expressions&quot;,&quot;lesson_description&quot;:&quot;Students will learn how PHP 8\u0027s match expression improves upon traditional switch statements by offering strict comparison, returnable values, and more concise syntax. Through interactive code samples, students will practice building robust control flow structures that enhance readability and reduce bugs in logic-heavy applications.&quot;},{&quot;lesson_title&quot;:&quot;Boosting Readability with Named Arguments&quot;,&quot;lesson_description&quot;:&quot;This lesson explores how named arguments simplify function calls by explicitly stating parameter names, increasing clarity especially in functions with multiple optional parameters. Students will practice rewriting traditional function invocations using named arguments and apply this feature to build clearer, more maintainable code structures across different PHP 8 projects.&quot;}],&quot;quizzes&quot;:[{&quot;quiz_title&quot;:&quot;Modern PHP 8 Features Quiz&quot;,&quot;quiz_description&quot;:&quot;Test your knowledge of PHP 8\u0027s match expressions and named arguments.&quot;,&quot;questions&quot;:[{&quot;question_title&quot;:&quot;How do match expressions differ from switch statements?&quot;,&quot;question_description&quot;:&quot;Identify the key improvement match expressions offer over switch statements.&quot;,&quot;options&quot;:[&quot;Match uses loose comparisons&quot;,&quot;Match supports variable variables&quot;,&quot;Match returns values and uses strict comparison&quot;,&quot;Match allows function returns inside case blocks&quot;],&quot;correct_answer&quot;:&quot;Match returns values and uses strict comparison&quot;},{&quot;question_title&quot;:&quot;What is the benefit of using named arguments in function calls?&quot;,&quot;question_description&quot;:&quot;Choose the most significant advantage of using named arguments.&quot;,&quot;options&quot;:[&quot;Shorter function syntax&quot;,&quot;Prevents function overloading&quot;,&quot;Improves code clarity and flexibility&quot;,&quot;Eliminates the need for function parameters&quot;],&quot;correct_answer&quot;:&quot;Improves code clarity and flexibility&quot;}]}]}]}]';

		$components = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-edit-course-curriculum-ai">',
			'wrap'                     => '<div class="lp-generate-data-ai-wrap">',
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
					<button class="lp-button btn-primary lp-btn-apply-curriculum"
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
			'dummy' 				 => sprintf(
				'<input name="lp-openai-generated-data" value="%s">',
				$data_dummy
			),
			'form-end'                 => '</form>',
			'wrap-end'                 => '</div>',
			'wrap-script-template-end' => '</script>',
		];

		echo Template::combine_components( $components );
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
			'step'                  => '<div class="step-content active" data-step="1">',
			'title'                 => sprintf(
				'<div class="step-title">%s</div>',
				esc_html__( 'Step 1 — Curriculum Goal', 'learnpress' ),
			),
			'description'           => sprintf(
				'<p class="step-description">%s</p>',
				esc_html__( 'Config your curriculum you want.', 'learnpress' )
			),
			'form-grid'             => '<div class="form-grid">',
			'post-title'            => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="text" name="post-title" readonly />
				</div>',
				esc_html__( 'Course Title', 'learnpress' )
			),
			'post-content'          => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<textarea type="text" name="post-content" readonly></textarea>
				</div>',
				esc_html__( 'Course Description', 'learnpress' )
			),
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
					<input type="number" name="section_title_length" value="60" min="0">
				</div>',
				esc_html__( 'Each section title length', 'learnpress' )
			),
			'sections-des-length'   => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="section_description_length" value="200" min="0">
				</div>',
				esc_html__( 'Each section description length', 'learnpress' )
			),
			'lesson-number'         => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="lessons_per_section" value="2" min="0">
				</div>',
				esc_html__( 'Lessons per Section', 'learnpress' )
			),
			'lesson-title-length'   => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="lessons_title_length" value="60" min="0">
				</div>',
				esc_html__( 'Each lesson title length', 'learnpress' )
			),
			'lesson-des-length'     => sprintf(
				'<div class="form-group">
					<label>%s</label>
					<input type="number" name="lessons_description_length" value="1000" min="0">
				</div>',
				esc_html__( 'Each lesson description length', 'learnpress' )
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
			'form-grid-end'         => '</div>',
			'step_close'            => '</div>',
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
				</div>',
				esc_html__( 'Generated Prompt', 'learnpress' ),
				'<textarea name="lp-openai-prompt-generated-field"></textarea>',
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
			'results'    => '<div class="lp-ai-generated-results"></div>',
			'step_close' => '</div>',
		];

		return Template::combine_components( $components );
	}
}
