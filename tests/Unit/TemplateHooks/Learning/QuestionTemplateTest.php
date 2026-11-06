<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Learning;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LearnPress\Models\Question\QuestionPostFIBModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LearnPress\TemplateHooks\Learning\QuestionTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;

/**
 * Unit tests for QuestionTemplate answer renderers.
 *
 * Strategy: QuestionPostModel is a partial mock, get_answers_for_legacy_type() returns the answers config,
 * answer of user and result come from args['result_questions'], quiz settings from args['quizPostModel'].
 */
class QuestionTemplateTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_kses_post' )->returnArg( 1 );
		Functions\when( 'do_shortcode' )->returnArg( 1 );
		Functions\when( 'wp_kses_allowed_html' )->justReturn( [] );
		Functions\when( 'wp_kses' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_html' )->alias(
			static function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES );
			}
		);
		if ( ! class_exists( 'LP_Debug', false ) ) {
			eval( 'class LP_Debug { public static function error_log( $e ) {} }' );
		}
	}

	/**
	 * @param string $type
	 * @param array $options answers config
	 * @param float $mark
	 * @param string $class
	 *
	 * @return QuestionPostModel|Mockery\MockInterface
	 */
	private function make_question( string $type, array $options, float $mark = 2, string $class = QuestionPostModel::class ) {
		$question = Mockery::mock( $class )->makePartial();
		$question->shouldReceive( 'get_id' )->andReturn( 5 );
		$question->shouldReceive( 'get_type' )->andReturn( $type );
		$question->shouldReceive( 'get_mark' )->andReturn( $mark );
		$question->shouldReceive( 'get_answers_for_legacy_type' )->andReturn( $options );

		return $question;
	}

	/**
	 * @return QuizPostModel|Mockery\MockInterface
	 */
	private function make_quiz() {
		$quiz = Mockery::mock( QuizPostModel::class );
		$quiz->shouldReceive( 'has_review' )->andReturn( true );
		$quiz->shouldReceive( 'has_show_correct_review' )->andReturn( true );

		return $quiz;
	}

	/**
	 * User quiz started: answers are editable (input has name, value and no disabled).
	 *
	 * @return UserQuizModel|Mockery\MockInterface
	 */
	private function make_user_quiz( bool $started = true ) {
		$userQuizModel = Mockery::mock( UserQuizModel::class )->makePartial();
		$userQuizModel->shouldReceive( 'has_started' )->andReturn( $started );

		return $userQuizModel;
	}

	/**
	 * Args for html_answers().
	 *
	 * @param mixed $answered answer of user, null when not answered
	 * @param array $args
	 */
	private function make_args( $answered = null, array $args = [] ): array {
		$result = [];
		if ( null !== $answered ) {
			$result = [ 5 => [ 'answered' => $answered ] ];
		}

		return array_merge(
			[
				'quizPostModel'    => $this->make_quiz(),
				'userQuizModel'    => $this->make_user_quiz(),
				'result_questions' => $result,
			],
			$args
		);
	}

	private function choice_options(): array {
		return [
			[
				'title'   => 'Option A',
				'value'   => 'a',
				'is_true' => 'yes',
			],
			[
				'title'   => 'Option B',
				'value'   => 'b',
				'is_true' => 'no',
			],
		];
	}

	/**
	 * FIB question: blanks are read by the model helpers, mock them.
	 *
	 * @param array $blank_answers result of each blank, key is blank id.
	 *
	 * @return QuestionPostFIBModel|Mockery\MockInterface
	 */
	private function make_fib_question( array $blank_answers = [] ) {
		$question = $this->make_question(
			'fill_in_blanks',
			[
				[
					'uid'   => 21,
					'title' => 'Hello {{FIB_x1}} and {{FIB_x2}}',
				],
			],
			2,
			QuestionPostFIBModel::class
		);
		$question->shouldReceive( 'get_blanks_of_answer' )->andReturn( [] );
		$question->shouldReceive( 'fib_get_ids' )->andReturn( [ 'x1', 'x2' ] );
		$question->shouldReceive( 'match_shortcode_api' )->andReturnUsing(
			static function ( string $content ) {
				return $content;
			}
		);
		$question->shouldReceive( 'get_answer_data' )->andReturn( $blank_answers );

		return $question;
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
			$this->make_args()
		);

		$this->assertStringContainsString( '<ul id="answer-options-5" class="answer-options">', $html );
		$this->assertStringContainsString( 'id="learn-press-answer-option-5-0"', $html );
		$this->assertStringContainsString( 'name="learn-press-question-5"', $html );
		$this->assertStringContainsString( 'value="a"', $html );
		$this->assertStringContainsString( '<label for="learn-press-answer-option-5-0" class="option-title">Option A</label>', $html );
		$this->assertStringNotContainsString( 'answer-correct', $html );
		$this->assertStringNotContainsString( 'answered-', $html );
		$this->assertStringNotContainsString( 'question-response', $html );
		$this->assertStringNotContainsString( 'disabled', $html );
	}

	public function test_choice_doing_restores_answered(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'single_choice', $this->choice_options() ),
			$this->make_args( 'b' )
		);

		$this->assertStringContainsString( 'value="b" checked', $html );
		$this->assertStringNotContainsString( 'value="a" checked', $html );
	}

	public function test_multi_choice_uses_checkbox(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'multi_choice', $this->choice_options() ),
			$this->make_args( [ 'a', 'b' ] )
		);

		$this->assertSame( 2, substr_count( $html, 'type="checkbox"' ) );
		$this->assertSame( 2, substr_count( $html, ' checked' ) );
	}

	public function test_choice_without_quiz_renders_nothing(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'single_choice', $this->choice_options() ),
			[]
		);

		$this->assertSame( '', $html );
	}

	// -------------------------------------------------------------------------
	// Choice: checked / review
	// -------------------------------------------------------------------------

	public function test_choice_checked_with_show_correct_marks_answers(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'single_choice', $this->choice_options() ),
			$this->make_args(
				'b',
				[
					'show_result'  => true,
					'show_correct' => true,
					'correct'      => false,
				]
			)
		);

		$this->assertStringContainsString( '<li class="answer-option answer-correct">', $html );
		$this->assertStringContainsString( '<li class="answer-option answered-wrong">', $html );
		$this->assertSame( 2, substr_count( $html, ' disabled' ) );
		$this->assertStringContainsString( 'question-response incorrect', $html );
		$this->assertStringContainsString( '0/2 point', $html );
	}

	public function test_choice_checked_without_show_correct_uses_server_result(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'true_or_false', $this->choice_options() ),
			$this->make_args(
				'b',
				[
					'show_result'  => true,
					'show_correct' => false,
					'correct'      => true,
				]
			)
		);

		// is_true is not read when not show correct, the result of server decides.
		$this->assertStringNotContainsString( 'answer-correct', $html );
		$this->assertStringContainsString( '<li class="answer-option answered-correct">', $html );
		$this->assertStringContainsString( 'question-response correct', $html );
		$this->assertStringContainsString( '2/2 point', $html );
	}

	public function test_choice_review_has_no_input_name_and_value(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'single_choice', $this->choice_options() ),
			$this->make_args(
				'a',
				[
					'userQuizModel' => $this->make_user_quiz( false ),
					'show_result'   => true,
					'correct'       => true,
				]
			)
		);

		$this->assertStringContainsString( 'name=""', $html );
		$this->assertMatchesRegularExpression( '/id="learn-press-answer-option-5-0"\s+value="" checked disabled/', $html );
	}

	public function test_choice_reads_answers_from_question_snapshot(): void {
		$snapshot = [
			'answers_config' => [
				[
					'title'   => 'From snapshot',
					'value'   => 's1',
					'is_true' => 'yes',
				],
			],
		];
		$question = Mockery::mock( QuestionPostModel::class )->makePartial();
		$question->shouldReceive( 'get_id' )->andReturn( 5 );
		$question->shouldReceive( 'get_type' )->andReturn( 'single_choice' );
		$question->shouldReceive( 'get_answers_for_legacy_type' )->never();

		$args                                              = $this->make_args( 's1' );
		$args['result_questions'][5]['questionSnapshot']   = $snapshot;
		$html = QuestionTemplate::instance()->html_answers( $question, $args );

		$this->assertStringContainsString( 'From snapshot', $html );
		$this->assertStringContainsString( 'value="s1" checked', $html );
	}

	// -------------------------------------------------------------------------
	// Fill in blanks
	// -------------------------------------------------------------------------

	public function test_fib_doing_renders_inputs_and_restores_answered(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_fib_question(),
			$this->make_args( [ 'x1' => 'world' ] )
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
			$this->make_fib_question(
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
			$this->make_args(
				[
					'x1' => 'world',
					'x2' => '<b>bad</b>',
				],
				[
					'show_result'  => true,
					'show_correct' => true,
					'mark'         => 1.5,
				]
			)
		);

		$this->assertStringContainsString( '<span class="lp-fib-answered correct"><span class="lp-fib-answered__fill">world</span></span>', $html );
		$this->assertStringContainsString( '<span class="lp-fib-answered fail"><span class="lp-fib-answered__answer">&lt;b&gt;bad&lt;/b&gt;</span> &rarr; <span class="lp-fib-answered__fill">good</span></span>', $html );
		$this->assertStringContainsString( '1.50/2 point', $html );
		$this->assertStringContainsString( 'lp-fib-note', $html );
	}

	public function test_fib_review_without_show_correct_hides_fill(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_fib_question(
				[
					'x1' => [
						'is_correct' => false,
						'answer'     => 'wrong',
					],
				]
			),
			$this->make_args(
				[ 'x1' => 'wrong' ],
				[
					'show_result'  => true,
					'show_correct' => false,
				]
			)
		);

		$this->assertStringContainsString( '<span class="lp-fib-answered fail"><span class="lp-fib-answered__answer">wrong</span></span>', $html );
		$this->assertStringNotContainsString( 'lp-fib-answered__fill', $html );
	}

	// -------------------------------------------------------------------------
	// Sorting choice
	// -------------------------------------------------------------------------

	private function sorting_options(): array {
		// Config order is the correct order.
		return [
			[
				'title' => 'First',
				'value' => 'v1',
			],
			[
				'title' => 'Second',
				'value' => 'v2',
			],
		];
	}

	public function test_sorting_doing_uses_answered_order(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'sorting_choice', $this->sorting_options() ),
			$this->make_args( [ 'v2', 'v1' ] )
		);

		$this->assertStringContainsString( 'class="answer-options lp-sorting-choice" data-sorted="1"', $html );
		$this->assertLessThan( strpos( $html, 'value="v1"' ), strpos( $html, 'value="v2"' ) );
		$this->assertStringContainsString( 'name="learn-press-question-5[]"', $html );
		// Editable: no readonly class (state is not attribute draggable, Sortable changes it).
		$this->assertStringNotContainsString( 'lp-sorting-choice--readonly', $html );
		$this->assertStringNotContainsString( 'draggable', $html );
		$this->assertStringNotContainsString( 'lp-sorting-choice__position', $html );
	}

	public function test_sorting_not_answered_has_no_sorted_flag(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'sorting_choice', $this->sorting_options() ),
			$this->make_args()
		);

		// Options are shuffled by server, untouched question must not be counted as answered by JS.
		$this->assertStringContainsString( 'class="answer-options lp-sorting-choice">', $html );
		$this->assertStringNotContainsString( 'data-sorted', $html );
	}

	public function test_sorting_review_with_show_correct_marks_positions(): void {
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'sorting_choice', $this->sorting_options() ),
			$this->make_args(
				[ 'v2', 'v1' ],
				[
					'userQuizModel' => $this->make_user_quiz( false ),
					'show_result'   => true,
					'show_correct'  => true,
					'correct'       => false,
				]
			)
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
		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'unknown_type', [] ),
			$this->make_args()
		);

		$this->assertSame( '<div class="question-types">Question <span>unknown_type</span> invalid!</div>', $html );
	}

	public function test_addon_type_rendered_by_filter(): void {
		Filters\expectApplied( 'learn-press/question/html-answers' )
			->andReturn( '<div class="addon-type"></div>' );

		$html = QuestionTemplate::instance()->html_answers(
			$this->make_question( 'addon_type', [] ),
			$this->make_args()
		);

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
}
