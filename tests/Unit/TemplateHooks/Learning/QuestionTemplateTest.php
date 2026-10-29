<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Learning;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\TemplateHooks\Learning\QuestionTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;

/**
 * Unit tests for QuestionTemplate answer renderers.
 *
 * Strategy: QuestionPostModel is a partial mock, get_answer_options() returns options the same way
 * the model does: is_true only when not excluded, so tests also check what the template asks for.
 */
class QuestionTemplateTest extends BrainMonkeyTestCase {

	/**
	 * @var array last args passed to get_answer_options()
	 */
	private $options_args = [];

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_kses_post' )->returnArg( 1 );
		$this->options_args = [];
	}

	/**
	 * @param string $type
	 * @param array $options options with is_true, removed when template excludes it.
	 * @param float $mark
	 *
	 * @return QuestionPostModel|Mockery\MockInterface
	 */
	private function make_question( string $type, array $options, float $mark = 2 ) {
		$question = Mockery::mock( QuestionPostModel::class )->makePartial();
		$question->shouldReceive( 'get_id' )->andReturn( 5 );
		$question->shouldReceive( 'get_type' )->andReturn( $type );
		$question->shouldReceive( 'get_mark' )->andReturn( $mark );
		$question->shouldReceive( 'get_answer_options' )->andReturnUsing(
			function ( $args ) use ( $options ) {
				$this->options_args = $args;
				$exclude            = array_flip( $args['exclude'] );

				return array_map(
					static function ( $option ) use ( $exclude ) {
						return array_diff_key( $option, $exclude );
					},
					$options
				);
			}
		);

		return $question;
	}

	private function choice_options(): array {
		return [
			[
				'uid'     => 11,
				'title'   => 'Option A',
				'value'   => 'a',
				'is_true' => 'yes',
			],
			[
				'uid'     => 12,
				'title'   => 'Option B',
				'value'   => 'b',
				'is_true' => 'no',
			],
		];
	}

	private function fib_options( array $answers = [] ): array {
		return [
			[
				'uid'     => 21,
				'title'   => 'Hello {{FIB_x1}} and {{FIB_x2}}',
				'ids'     => [ 'x1', 'x2' ],
				'answers' => $answers,
			],
		];
	}

	// -------------------------------------------------------------------------
	// parse_answer_args
	// -------------------------------------------------------------------------

	public function test_parse_answer_args_show_correct_needs_show_result(): void {
		$args = QuestionTemplate::instance()->parse_answer_args( [ 'show_correct' => true ] );

		$this->assertFalse( $args['show_result'] );
		$this->assertFalse( $args['show_correct'] );
	}

	// -------------------------------------------------------------------------
	// Choice: doing
	// -------------------------------------------------------------------------

	public function test_choice_doing_has_no_correct_markers(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'single_choice', $this->choice_options() ),
			[ 'status' => 'started' ]
		);

		$this->assertContains( 'is_true', $this->options_args['exclude'] );
		$this->assertStringContainsString( '<ul id="answer-options-5" class="answer-options">', $html );
		$this->assertStringContainsString( 'type="radio" class="option-check" name="learn-press-question-5" id="learn-press-answer-option-11" value="a"', $html );
		$this->assertStringContainsString( '<label for="learn-press-answer-option-11" class="option-title">Option A</label>', $html );
		$this->assertStringNotContainsString( 'answer-correct', $html );
		$this->assertStringNotContainsString( 'answered-', $html );
		$this->assertStringNotContainsString( 'question-response', $html );
		$this->assertStringNotContainsString( 'disabled', $html );
	}

	public function test_choice_doing_restores_answered(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'single_choice', $this->choice_options() ),
			[
				'status'   => 'started',
				'answered' => 'b',
			]
		);

		$this->assertStringContainsString( 'value="b" checked', $html );
		$this->assertStringNotContainsString( 'value="a" checked', $html );
	}

	public function test_multi_choice_uses_checkbox(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'multi_choice', $this->choice_options() ),
			[
				'status'   => 'started',
				'answered' => [ 'a', 'b' ],
			]
		);

		$this->assertSame( 2, substr_count( $html, 'type="checkbox"' ) );
		$this->assertSame( 2, substr_count( $html, ' checked' ) );
	}

	// -------------------------------------------------------------------------
	// Choice: checked / review
	// -------------------------------------------------------------------------

	public function test_choice_checked_with_show_correct_marks_answers(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'single_choice', $this->choice_options() ),
			[
				'status'       => 'started',
				'answered'     => 'b',
				'show_result'  => true,
				'show_correct' => true,
				'correct'      => false,
			]
		);

		$this->assertNotContains( 'is_true', $this->options_args['exclude'] );
		$this->assertStringContainsString( '<li class="answer-option answer-correct">', $html );
		$this->assertStringContainsString( '<li class="answer-option answered-wrong">', $html );
		$this->assertSame( 2, substr_count( $html, ' disabled' ) );
		$this->assertStringContainsString( '<div class="question-response incorrect"><span class="label">Incorrect</span><span class="point">0/2 point</span></div>', $html );
	}

	public function test_choice_checked_without_show_correct_uses_server_result(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'true_or_false', $this->choice_options() ),
			[
				'status'       => 'started',
				'answered'     => 'a',
				'show_result'  => true,
				'show_correct' => false,
				'correct'      => true,
			]
		);

		$this->assertContains( 'is_true', $this->options_args['exclude'] );
		$this->assertStringNotContainsString( 'answer-correct', $html );
		$this->assertStringContainsString( '<li class="answer-option answered-correct">', $html );
		$this->assertStringContainsString( '<span class="label">Correct</span><span class="point">2/2 point</span>', $html );
	}

	public function test_choice_review_has_no_input_name_and_value(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'single_choice', $this->choice_options() ),
			[
				'status'      => 'completed',
				'answered'    => 'a',
				'show_result' => true,
				'correct'     => true,
			]
		);

		$this->assertStringContainsString( 'name="" id="learn-press-answer-option-11" value="" checked disabled', $html );
	}

	public function test_choice_uid_fallback_when_missing(): void {
		$options = $this->choice_options();
		unset( $options[0]['uid'] );

		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'single_choice', $options ),
			[ 'status' => 'started' ]
		);

		$this->assertStringContainsString( 'id="learn-press-answer-option-5-0"', $html );
	}

	// -------------------------------------------------------------------------
	// Fill in blanks
	// -------------------------------------------------------------------------

	public function test_fib_doing_renders_inputs_and_restores_answered(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'fill_in_blanks', $this->fib_options() ),
			[
				'status'   => 'started',
				'answered' => [ 'x1' => 'world' ],
			]
		);

		$this->assertStringContainsString( '<div class="lp-fib-content"><div>Hello ', $html );
		$this->assertStringContainsString( '<input type="text" data-id="x1" value="world"/>', $html );
		$this->assertStringContainsString( '<input type="text" data-id="x2" value=""/>', $html );
		$this->assertStringNotContainsString( 'lp-fib-answered', $html );
		$this->assertStringNotContainsString( '{{FIB_', $html );
		$this->assertStringNotContainsString( 'question-response', $html );
	}

	public function test_fib_review_with_show_correct_shows_fill(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question(
				'fill_in_blanks',
				$this->fib_options(
					[
						'x1' => [
							'is_correct' => true,
							'answer'     => 'world',
							'correct'    => 'world',
						],
						'x2' => [
							'is_correct' => false,
							'answer'     => '<b>bad</b>',
							'correct'    => 'good',
						],
					]
				),
				2
			),
			[
				'status'       => 'completed',
				'show_result'  => true,
				'show_correct' => true,
				'mark'         => 1.5,
			]
		);

		$this->assertStringContainsString( '<span class="lp-fib-answered correct"><span class="lp-fib-answered__fill">world</span></span>', $html );
		$this->assertStringContainsString( '<span class="lp-fib-answered fail"><span class="lp-fib-answered__answer">&lt;b&gt;bad&lt;/b&gt;</span> &rarr; <span class="lp-fib-answered__fill">good</span></span>', $html );
		$this->assertStringContainsString( '<span class="point">1.50/2 point</span>', $html );
		$this->assertStringContainsString( 'lp-fib-note', $html );
	}

	public function test_fib_review_without_show_correct_hides_fill(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question(
				'fill_in_blanks',
				$this->fib_options(
					[
						'x1' => [
							'is_correct' => false,
							'answer'     => 'wrong',
							// correct (fill) is removed by model when not show correct, test template does not rely on it.
							'correct'    => 'world',
						],
					]
				)
			),
			[
				'status'       => 'completed',
				'show_result'  => true,
				'show_correct' => false,
			]
		);

		$this->assertStringContainsString( '<span class="lp-fib-answered fail"><span class="lp-fib-answered__answer">wrong</span></span>', $html );
		$this->assertStringNotContainsString( 'lp-fib-answered__fill', $html );
		$this->assertStringNotContainsString( 'world', $html );
	}

	// -------------------------------------------------------------------------
	// Sorting choice
	// -------------------------------------------------------------------------

	private function sorting_options(): array {
		// Model returns random order, key sorting = correct position when not exclude is_true.
		return [
			[
				'title'   => 'Second',
				'value'   => 'v2',
				'is_true' => 'yes',
				'sorting' => 1,
			],
			[
				'title'   => 'First',
				'value'   => 'v1',
				'is_true' => 'yes',
				'sorting' => 0,
			],
		];
	}

	public function test_sorting_doing_uses_answered_order(): void {
		$options = array_map(
			static function ( $option ) {
				unset( $option['sorting'] );

				return $option;
			},
			$this->sorting_options()
		);

		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'sorting_choice', $options ),
			[
				'status'   => 'started',
				'answered' => [ 'v1', 'v2' ],
			]
		);

		$this->assertStringContainsString( 'class="answer-options lp-sorting-choice" data-sorted="1"', $html );
		$this->assertLessThan( strpos( $html, 'value="v2"' ), strpos( $html, 'value="v1"' ) );
		$this->assertStringContainsString( 'name="learn-press-question-5[]"', $html );
		// Editable: no readonly class (state is not attribute draggable, Sortable changes it).
		$this->assertStringNotContainsString( 'lp-sorting-choice--readonly', $html );
		$this->assertStringNotContainsString( 'draggable', $html );
		$this->assertStringNotContainsString( 'lp-sorting-choice__position', $html );
	}

	public function test_sorting_not_answered_has_no_sorted_flag(): void {
		$options = array_map(
			static function ( $option ) {
				unset( $option['sorting'] );

				return $option;
			},
			$this->sorting_options()
		);

		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'sorting_choice', $options ),
			[ 'status' => 'started' ]
		);

		// Options are shuffled by server, untouched question must not be counted as answered by JS.
		$this->assertStringContainsString( 'class="answer-options lp-sorting-choice">', $html );
		$this->assertStringNotContainsString( 'data-sorted', $html );
	}

	public function test_sorting_review_with_show_correct_marks_positions(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'sorting_choice', $this->sorting_options() ),
			[
				'status'       => 'completed',
				'answered'     => [ 'v2', 'v1' ],
				'show_result'  => true,
				'show_correct' => true,
				'correct'      => false,
			]
		);

		$this->assertSame( 2, substr_count( $html, 'answered-wrong' ) );
		$this->assertStringContainsString( '<span class="lp-sorting-choice__position">2</span>', $html );
		$this->assertStringContainsString( 'class="answer-options lp-sorting-choice lp-sorting-choice--readonly"', $html );
		$this->assertStringContainsString( 'question-response incorrect', $html );
	}

	// -------------------------------------------------------------------------
	// Invalid type / add-on filter
	// -------------------------------------------------------------------------

	public function test_invalid_type_shows_message(): void {
		$html = QuestionTemplate::instance()->html_answers( $this->make_question( 'unknown_type', [] ) );

		$this->assertSame( '<div class="question-types">Question <code>unknown_type</code> invalid!</div>', $html );
	}

	public function test_addon_type_rendered_by_filter(): void {
		Filters\expectApplied( 'learn-press/question/html-answers' )
			->once()
			->andReturn( '<div class="addon-type"></div>' );

		$html = QuestionTemplate::instance()->html_answers( $this->make_question( 'addon_type', [] ) );

		$this->assertSame( '<div class="addon-type"></div>', $html );
	}

	// -------------------------------------------------------------------------
	// format_point
	// -------------------------------------------------------------------------

	public function test_format_point(): void {
		$template = QuestionTemplate::instance();

		$this->assertSame( '2', $template->format_point( 2.0 ) );
		$this->assertSame( '1.50', $template->format_point( 1.5 ) );
		$this->assertSame( '0', $template->format_point( 0.0 ) );
	}

	// -------------------------------------------------------------------------
	// html_question
	// -------------------------------------------------------------------------

	/**
	 * @param string $hint
	 * @param string $explanation
	 *
	 * @return QuestionPostModel|Mockery\MockInterface
	 */
	private function make_full_question( string $hint = 'The hint', string $explanation = 'The explanation' ) {
		$question = $this->make_question( 'single_choice', $this->choice_options() );
		$question->shouldReceive( 'get_the_title' )->andReturn( 'What is it?' );
		$question->shouldReceive( 'get_hint' )->andReturn( $hint );
		$question->shouldReceive( 'get_explanation' )->andReturn( $explanation );

		return $question;
	}

	public function test_html_question_doing(): void {
		Functions\when( 'learn_press_get_question_content_display' )->justReturn( '<p>Content</p>' );

		$html = QuestionTemplate::instance()->html_question(
			$this->make_full_question(),
			[
				'status' => 'started',
				'index'  => 3,
				'footer' => '<button class="footer-btn"></button>',
			]
		);

		$this->assertStringStartsWith( '<div class="question question-single_choice" data-id="5" data-page="1">', $html );
		$this->assertStringContainsString( '<h4 class="question-title"><span class="question-index">3.</span><span>What is it?</span><button type="button" class="btn-show-hint"><span>Hint</span></button></h4>', $html );
		$this->assertStringContainsString( '<div class="question-content"><p>Content</p></div>', $html );
		$this->assertStringContainsString( 'class="question-answers"', $html );
		$this->assertStringContainsString( '<div class="question-hint-content" style="display: none;">', $html );
		$this->assertStringNotContainsString( 'question-explanation-content', $html );
		$this->assertStringNotContainsString( 'The explanation', $html );
		$this->assertStringContainsString( '<button class="footer-btn"></button></div>', $html );
	}

	public function test_html_question_hidden_on_other_page(): void {
		Functions\when( 'learn_press_get_question_content_display' )->justReturn( '' );

		$html = QuestionTemplate::instance()->html_question(
			$this->make_full_question(),
			[
				'page'   => 2,
				'hidden' => true,
			]
		);

		$this->assertStringStartsWith( '<div class="question question-single_choice" data-id="5" data-page="2" style="display: none;">', $html );
		$this->assertStringNotContainsString( 'question-content', $html );
	}

	public function test_html_question_checked_shows_explanation_not_hint(): void {
		Functions\when( 'learn_press_get_question_content_display' )->justReturn( '' );

		$html = QuestionTemplate::instance()->html_question(
			$this->make_full_question(),
			[
				'status'           => 'started',
				'answered'         => 'a',
				'show_result'      => true,
				'show_correct'     => true,
				'correct'          => true,
				'show_explanation' => true,
			]
		);

		$this->assertStringStartsWith( '<div class="question question-single_choice question-answered"', $html );
		$this->assertStringContainsString( '<div class="question-explanation-content"><strong class="explanation-title">Explanation:</strong><div>The explanation</div></div>', $html );
		$this->assertStringNotContainsString( 'question-hint-content', $html );
		// Button hint goes with hint content.
		$this->assertStringNotContainsString( 'btn-show-hint', $html );
	}

	public function test_html_question_no_hint_button_when_no_hint_and_edit_link(): void {
		Functions\when( 'learn_press_get_question_content_display' )->justReturn( '' );

		$html = QuestionTemplate::instance()->html_question(
			$this->make_full_question( '', '' ),
			[ 'edit_link' => 'https://example.com/edit' ]
		);

		$this->assertStringNotContainsString( 'btn-show-hint', $html );
		$this->assertStringNotContainsString( 'question-hint-content', $html );
		$this->assertStringNotContainsString( 'question-index', $html );
		$this->assertStringContainsString( '<span class="edit-link"><a href="https://example.com/edit">Edit</a></span>', $html );
	}
}
