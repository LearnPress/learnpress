<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\UserItem;

use Brain\Monkey\Functions;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LearnPress\TemplateHooks\UserItem\UserQuizTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;
use WP_Error;

require_once dirname( __DIR__, 4 ) . '/inc/class-lp-duration.php';

/**
 * Unit tests for UserQuizTemplate screens: intro, history, doing quiz.
 * Meta / description are tested in QuizTemplateTest, question markup in QuestionTemplateTest.
 *
 * Strategy: QuizPostModel / UserQuizModel are partial mocks, QuestionPostModel::find() is served
 * from wp_cache_get, so no DB is needed.
 */
class UserQuizTemplateTest extends BrainMonkeyTestCase {

	/**
	 * @var QuestionPostModel[] questions served by QuestionPostModel::find(), key is question id
	 */
	private $questions = [];

	/**
	 * Markup is multi-line, compare it without whitespace between tags / attributes.
	 */
	private static function flat( string $html ): string {
		$html = preg_replace( '/\s+/', ' ', $html );
		$html = preg_replace( '/>\s+/', '>', $html );

		return trim( preg_replace( '/\s+</', '<', $html ) );
	}

	public static function assertStringContainsString( string $needle, string $haystack, string $message = '' ): void {
		parent::assertStringContainsString( self::flat( $needle ), self::flat( $haystack ), $message );
	}

	public static function assertStringNotContainsString( string $needle, string $haystack, string $message = '' ): void {
		parent::assertStringNotContainsString( self::flat( $needle ), self::flat( $haystack ), $message );
	}

	public static function assertStringStartsWith( string $prefix, string $string, string $message = '' ): void {
		parent::assertStringStartsWith( self::flat( $prefix ), self::flat( $string ), $message );
	}

	protected function setUp(): void {
		parent::setUp();

		// QuestionPostModel::find() builds a filter before reading cache.
		if ( ! class_exists( 'LP_Question_Filter', false ) ) {
			eval( 'class LP_Question_Filter { public $ID = 0; }' );
		}
		if ( ! class_exists( 'LP_Settings', false ) ) {
			eval(
				'class LP_Settings {
					public static array $options = array();
					public static function get_option( string $key, $default = "" ) {
						return self::$options[$key] ?? $default;
					}
				}'
			);
		}
		\LP_Settings::$options = [];

		$this->questions = [];
		Functions\when( 'maybe_unserialize' )->returnArg( 1 );
		Functions\when( 'wp_cache_get' )->alias(
			function ( $key ) {
				$id = (int) substr( (string) $key, strlen( 'questionPostModel/find/' ) );

				return $this->questions[ $id ] ?? false;
			}
		);
		Functions\when( 'learn_press_get_question_content_display' )->justReturn( '' );
		// Review loads question by id, serve the same questions without query.
		\Patchwork\redefine(
			QuestionPostModel::class . '::find',
			function ( int $id ) {
				return $this->questions[ $id ] ?? false;
			}
		);

		defined( 'MINUTE_IN_SECONDS' ) || define( 'MINUTE_IN_SECONDS', 60 );
		defined( 'HOUR_IN_SECONDS' ) || define( 'HOUR_IN_SECONDS', 3600 );
		defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );
		defined( 'WEEK_IN_SECONDS' ) || define( 'WEEK_IN_SECONDS', 604800 );
		Functions\when( 'paginate_links' )->justReturn( '' );
		Functions\when( 'get_the_title' )->justReturn( 'Quiz title' );
		Functions\when( 'do_shortcode' )->returnArg( 1 );
		Functions\when( 'wp_kses_allowed_html' )->justReturn( [] );
		Functions\when( 'wp_kses' )->returnArg( 1 );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'wp_kses_post' )->returnArg( 1 );
		Functions\when( 'post_password_required' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'wp_json_encode' )->alias( static fn( $data, $flags = 0 ) => json_encode( $data, $flags ) );
		// Like WP: esc_attr() does not encode entities twice.
		Functions\when( 'esc_attr' )->alias( static fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ) );
		Functions\when( 'htmlentities2' )->alias( static fn( $text ) => htmlentities( $text, ENT_QUOTES, 'UTF-8', false ) );
		Functions\when( 'learn_press_get_graduation_text' )->alias(
			static fn( $slug ) => 'passed' === $slug ? 'Passed' : ( 'failed' === $slug ? 'Failed' : $slug )
		);
	}

	/**
	 * @param array $args [ 'duration' => string, 'grade' => float, 'questions' => int, 'content' => string ]
	 *
	 * @return QuizPostModel|Mockery\MockInterface
	 */
	private function make_quiz( array $args = [] ) {
		$args = array_merge(
			[
				'duration'  => '30 minute',
				'grade'     => 80.0,
				'questions'     => 5,
				'content'       => '<p>Quiz description</p>',
				'question_ids'  => [],
				'per_page'      => 1,
				'instant_check' => false,
				'show_correct'  => true,
				'retake'        => 0,
				'review'        => true,
				'negative'      => false,
				'minus_skip'    => false,
			],
			$args
		);

		$quiz = Mockery::mock( QuizPostModel::class )->makePartial();
		$quiz->shouldReceive( 'get_id' )->andReturn( 20 );
		$quiz->shouldReceive( 'get_duration' )->andReturn( $args['duration'] );
		$quiz->shouldReceive( 'get_passing_grade' )->andReturn( $args['grade'] );
		$quiz->shouldReceive( 'count_questions' )->andReturn( $args['questions'] );
		$quiz->shouldReceive( 'get_the_content' )->andReturn( $args['content'] );
		$quiz->shouldReceive( 'get_question_ids' )->andReturn( $args['question_ids'] );
		$quiz->shouldReceive( 'get_pagination' )->andReturn( $args['per_page'] );
		$quiz->shouldReceive( 'has_instant_check' )->andReturn( $args['instant_check'] );
		$quiz->shouldReceive( 'has_show_correct_review' )->andReturn( $args['show_correct'] );
		$quiz->shouldReceive( 'get_retake_count' )->andReturn( $args['retake'] );
		$quiz->shouldReceive( 'has_review' )->andReturn( $args['review'] );
		$quiz->shouldReceive( 'has_negative_marking' )->andReturn( $args['negative'] );
		$quiz->shouldReceive( 'has_minus_skip_questions' )->andReturn( $args['minus_skip'] );

		return $quiz;
	}

	private function make_course(): CourseModel {
		return new CourseModel( [ 'ID' => 30 ] );
	}

	/**
	 * @param array $history
	 *
	 * @return UserQuizModel|Mockery\MockInterface
	 */
	private function make_user_quiz( array $history ) {
		$userQuizModel = Mockery::mock( UserQuizModel::class )->makePartial();
		$userQuizModel->shouldReceive( 'get_history' )->andReturn( $history );

		return $userQuizModel;
	}

	// -------------------------------------------------------------------------
	// html_quiz_content
	// -------------------------------------------------------------------------

	public function test_html_quiz_content_empty_when_password_required_and_cannot_edit(): void {
		Functions\when( 'post_password_required' )->justReturn( true );

		$html = UserQuizTemplate::instance()->html_quiz_content( $this->make_course(), $this->make_quiz() );

		$this->assertSame( '', $html );
	}

	public function test_html_quiz_content_shows_message_when_no_questions(): void {
		$html = UserQuizTemplate::instance()->html_quiz_content(
			$this->make_course(),
			$this->make_quiz( [ 'questions' => 0 ] )
		);

		$this->assertStringContainsString( 'You haven\'t any question!', $html );
		$this->assertStringContainsString( 'learn-press-message info', $html );
		$this->assertStringNotContainsString( 'quiz-intro', $html );
		$this->assertStringNotContainsString( 'lp-button start', $html );
	}

	public function test_html_quiz_content_intro_for_user_has_not_started(): void {
		$html = UserQuizTemplate::instance()->html_quiz_content( $this->make_course(), $this->make_quiz() );

		$this->assertStringContainsString( 'id="learn-press-user-quiz"', $html );
		$this->assertStringContainsString( 'data-quiz-id="20"', $html );
		$this->assertStringContainsString( 'data-course-id="30"', $html );
		$this->assertStringContainsString( 'data-status=""', $html );
		$this->assertStringContainsString( '<div class="quiz-content"><p>Quiz description</p></div>', $html );
		$this->assertStringContainsString( 'class="quiz-intro"', $html );
		$this->assertStringContainsString( 'lp-button start', $html );
		$this->assertStringNotContainsString( 'quiz-attempts', $html );
	}

	public function test_html_quiz_content_empty_when_password_required_even_editor(): void {
		Functions\when( 'post_password_required' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );

		$html = UserQuizTemplate::instance()->html_quiz_content( $this->make_course(), $this->make_quiz() );

		$this->assertSame( '', $html );
	}

	// -------------------------------------------------------------------------
	// html_btn_start
	// -------------------------------------------------------------------------

	public function test_html_btn_start_has_data_send_for_start_action(): void {
		$html = UserQuizTemplate::instance()->html_btn_start([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz(),
			]);

		$this->assertStringContainsString( 'class="lp-button start"', $html );

		preg_match( '/data-send="([^"]*)"/', $html, $matches );
		$this->assertNotEmpty( $matches );
		$data = json_decode( html_entity_decode( $matches[1] ), true );

		$this->assertSame( 'user-start-quiz', $data['id_url'] );
		$this->assertSame( 'user_start_quiz', $data['action'] );
		$this->assertSame( 20, $data['quiz_id'] );
		$this->assertSame( 30, $data['course_id'] );
	}

	// -------------------------------------------------------------------------
	// html_history
	// -------------------------------------------------------------------------

	public function test_html_history_empty_when_no_attempts(): void {
		$html = UserQuizTemplate::instance()->html_history([
				'userQuizModel' => $this->make_user_quiz( [] ),
			]);

		$this->assertSame( '', $html );
	}

	public function test_html_history_renders_attempt_rows(): void {
		$html = UserQuizTemplate::instance()->html_history([
				'userQuizModel' => $this->make_user_quiz(
								[
									[
										'question_correct' => 3,
										'question_count'   => 5,
										'time_spend'       => '00:10:05',
										'user_mark'        => 6,
										'mark'             => 10,
										'passing_grade'    => '80',
										'result'           => 60,
										'pass'             => 0,
									],
									[
										'question_correct' => 5,
										'question_count'   => 5,
										'time_spend'       => '',
										'user_mark'        => 10,
										'mark'             => 10,
										'passing_grade'    => '',
										'result'           => '100',
										'graduation'       => 'passed',
									],
								]
							),
			]);

		$this->assertStringContainsString( 'class="quiz-attempts"', $html );
		$this->assertStringContainsString( 'Last Attempt', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__questions">3 / 5</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__spend">00:10:05</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__marks">6 / 10</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__grade">80%</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__result">60.00% <span>Failed</span></td>', $html );
		// Second row: empty time spend / passing grade fall back, graduation key is used.
		$this->assertStringContainsString( '<td class="quiz-attempts__spend">--</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__grade">-</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__result">100.00% <span>Passed</span></td>', $html );
	}

	public function test_html_history_escapes_values(): void {
		$html = UserQuizTemplate::instance()->html_history([
				'userQuizModel' => $this->make_user_quiz( [ [ 'time_spend' => '<script>alert(1)</script>' ] ] ),
			]);

		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_html_intro_has_meta_and_start_button(): void {
		$html = UserQuizTemplate::instance()->html_intro(
			[
				'courseModel'   => $this->make_course(),
				'quizPostModel' => $this->make_quiz(),
				'userQuizModel' => false,
			]
		);

		$this->assertStringContainsString( 'class="quiz-intro"', $html );
		$this->assertStringContainsString( 'lp-button start', $html );
		$this->assertStringNotContainsString( 'quiz-attempts', $html );
	}

	// -------------------------------------------------------------------------
	// Doing quiz
	// -------------------------------------------------------------------------

	/**
	 * Register single choice questions served by QuestionPostModel::find().
	 *
	 * @param int[] $ids
	 */
	private function add_questions( array $ids ): void {
		foreach ( $ids as $id ) {
			$question = Mockery::mock( QuestionPostModel::class )->makePartial();
			$question->shouldReceive( 'get_id' )->andReturn( $id );
			$question->shouldReceive( 'get_type' )->andReturn( 'single_choice' );
			$question->shouldReceive( 'get_mark' )->andReturn( 1 );
			$question->shouldReceive( 'get_the_title' )->andReturn( "Question title {$id}" );
			$question->shouldReceive( 'get_hint' )->andReturn( '' );
			$question->shouldReceive( 'get_explanation' )->andReturn( "Explanation {$id}" );
			$question->shouldReceive( 'get_edit_link' )->andReturn( "https://example.com/wp-admin/post.php?post={$id}&action=edit" );
			$question->shouldReceive( 'check_capabilities_update' )->andReturnUsing(
				static fn() => current_user_can( 'edit_post' )
			);
			$question->shouldReceive( 'get_answers_for_legacy_type' )->andReturn(
				[
					[
						'title'   => 'A',
						'value'   => 'a',
						'is_true' => 'yes',
					],
					[
						'title'   => 'B',
						'value'   => 'b',
						'is_true' => 'no',
					],
				]
			);

			$this->questions[ $id ] = $question;
		}
	}

	/**
	 * @param array $args [ checked => int[], result => array, start_time => string, remaining => int ]
	 *
	 * @return UserQuizModel|Mockery\MockInterface
	 */
	private function make_user_quiz_started( array $args = [] ) {
		$args = array_merge(
			[
				'checked'    => [],
				'result'     => [],
				'start_time' => gmdate( 'Y-m-d H:i:s', time() - 120 ),
				'remaining'  => 1680,
			],
			$args
		);

		$userQuizModel = Mockery::mock( UserQuizModel::class )->makePartial();
		$userQuizModel->shouldReceive( 'get_status' )->andReturn( UserQuizModel::STATUS_STARTED );
		$userQuizModel->shouldReceive( 'get_start_time' )->andReturn( $args['start_time'] );
		$userQuizModel->shouldReceive( 'get_time_remaining' )->andReturn( $args['remaining'] );
		$userQuizModel->shouldReceive( 'get_checked_questions' )->andReturn( $args['checked'] );
		$userQuizModel->shouldReceive( 'get_result' )->andReturn( $args['result'] );

		return $userQuizModel;
	}

	public function test_html_screen_started_shows_doing_quiz(): void {
		$this->add_questions( [ 101 ] );

		$html = UserQuizTemplate::instance()->html_screen([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz( [ 'question_ids' => [ 101 ] ] ),
				'userQuizModel' => $this->make_user_quiz_started(),
			]);

		$this->assertStringContainsString( 'class="lp-quiz-doing"', $html );
		$this->assertStringContainsString( 'class="quiz-doing-bar-info"', $html );
		$this->assertStringNotContainsString( 'lp-button start', $html );
		$this->assertStringNotContainsString( 'quiz-intro', $html );
	}

	public function test_html_doing_quiz_paginates_questions(): void {
		$this->add_questions( [ 101, 102, 103 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz(
								[
									'question_ids' => [ 101, 102, 103 ],
									'questions'    => 3,
									'per_page'     => 2,
								]
							),
				'userQuizModel' => $this->make_user_quiz_started(),
			]);

		$this->assertStringContainsString( 'data-per-page="2" data-total="3" data-num-pages="2"', $html );
		$this->assertStringContainsString( '<span class="index-range">Question <span class="i-start">1</span> to <span class="i-end">2</span> of 3</span>', $html );
		$this->assertStringContainsString( '<span class="index-total lp-hidden">3 questions</span>', $html );
		// All questions are rendered, JS shows the questions of current page by data-page.
		$this->assertStringContainsString( 'data-id="101" data-page="1">', $html );
		$this->assertStringContainsString( 'data-id="102" data-page="1">', $html );
		$this->assertStringContainsString( 'data-id="103" data-page="2">', $html );
		$this->assertStringContainsString( '<span class="question-index">3.</span>', $html );
		// One pagination per page, only the first is visible.
		$this->assertSame( 2, substr_count( $html, 'questions-pagination__item"' ) );
		$this->assertSame( 1, substr_count( $html, 'class="lp-button submit-quiz"' ) );
	}

	public function test_html_doing_quiz_all_questions_on_one_page_when_pagination_zero(): void {
		$this->add_questions( [ 101, 102, 103 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz(
								[
									'question_ids' => [ 101, 102, 103 ],
									'questions'    => 3,
									'per_page'     => 0,
								]
							),
				'userQuizModel' => $this->make_user_quiz_started(),
			]);

		$this->assertStringContainsString( 'data-num-pages="1"', $html );
		$this->assertStringContainsString( '<span class="index-total">3 questions</span>', $html );
		$this->assertStringNotContainsString( 'questions-pagination', $html );
		$this->assertStringContainsString( 'class="lp-button submit-quiz" data-title="Finish Quiz" data-content="Are you sure to submit the quiz?"', $html );
	}

	public function test_html_doing_quiz_timer_counts_down_from_remaining(): void {
		$this->add_questions( [ 101 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz( [ 'question_ids' => [ 101 ] ] ),
				'userQuizModel' => $this->make_user_quiz_started( [ 'remaining' => 1680 ] ),
			]);

		$this->assertMatchesRegularExpression( '/data-duration="1800"\s+data-remaining="1680"\s+data-time-spend="1[12]\d"/', $html );
		$this->assertStringContainsString( '<span>28:00</span>', $html );
		$this->assertStringContainsString( 'name="lp-quiz-time-spend"', $html );
	}

	public function test_html_doing_quiz_guest_uses_full_duration(): void {
		$this->add_questions( [ 101 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz( [ 'question_ids' => [ 101 ] ] ),
			]);

		$this->assertStringContainsString( 'data-duration="1800" data-remaining="1800" data-time-spend="0"', $html );
		$this->assertStringContainsString( '<span>30:00</span>', $html );
	}

	public function test_html_doing_quiz_instant_check_button_only_for_unchecked(): void {
		$this->add_questions( [ 101, 102 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz(
								[
									'question_ids'  => [ 101, 102 ],
									'instant_check' => true,
									'show_correct'  => true,
								]
							),
				'userQuizModel' => $this->make_user_quiz_started(
								[
									'checked' => [ 101 ],
									'result'  => [
										'user_mark' => 1,
										'questions' => [
											101 => [
												'answered' => 'a',
												'correct'  => true,
												'mark'     => 1,
											],
										],
									],
								]
							),
			]);

		$this->assertSame( 1, substr_count( $html, 'lp-button instant-check' ) );
		$this->assertStringContainsString( '&quot;question_id&quot;:102', $html );
		$this->assertStringNotContainsString( '&quot;question_id&quot;:101', $html );
		// Checked question shows result, correct answer and explanation.
		$this->assertStringContainsString( 'question question-single_choice question-answered" data-id="101"', $html );
		$this->assertStringContainsString( 'answer-option answer-correct answered-correct', $html );
		$this->assertStringContainsString( 'Explanation 101', $html );
		$this->assertStringNotContainsString( 'Explanation 102', $html );
	}

	public function test_html_doing_quiz_no_instant_check_button_when_option_off(): void {
		$this->add_questions( [ 101 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz( [ 'question_ids' => [ 101 ] ] ),
				'userQuizModel' => $this->make_user_quiz_started(),
			]);

		$this->assertStringNotContainsString( 'instant-check', $html );
		$this->assertStringNotContainsString( 'answer-correct', $html );
	}

	public function test_html_doing_quiz_edit_link_only_for_user_can_edit(): void {
		$this->add_questions( [ 101 ] );
		$quiz = $this->make_quiz( [ 'question_ids' => [ 101 ] ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $quiz,
				'userQuizModel' => $this->make_user_quiz_started(),
			]);
		$this->assertStringNotContainsString( 'edit-link', $html );

		Functions\when( 'current_user_can' )->justReturn( true );
		$html = UserQuizTemplate::instance()->html_doing_quiz([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $quiz,
				'userQuizModel' => $this->make_user_quiz_started(),
			]);
		$this->assertStringContainsString( '<span class="edit-link"><a href="https://example.com/wp-admin/post.php?post=101', $html );
	}

	public function test_html_questions_index(): void {
		$template = UserQuizTemplate::instance();

		// One page: show total.
		$html = $template->html_questions_index( [ 'start' => 1, 'end' => 10, 'total' => 10, 'per_page' => 0, 'num_pages' => 1 ] );
		$this->assertStringContainsString( '<span class="index-total">10 questions</span>', $html );
		$this->assertStringContainsString( '<span class="index-single lp-hidden">', $html );
		$this->assertStringContainsString( '<span class="index-range lp-hidden">', $html );

		// One question per page: show single index.
		$html = $template->html_questions_index( [ 'start' => 1, 'end' => 1, 'total' => 10, 'per_page' => 1, 'num_pages' => 10 ] );
		$this->assertStringContainsString( '<span class="index-total lp-hidden">', $html );
		$this->assertStringContainsString( '<span class="index-single">Question <span class="i-current">1</span> of 10</span>', $html );

		// Many questions per page: show range.
		$html = $template->html_questions_index( [ 'start' => 6, 'end' => 10, 'total' => 10, 'per_page' => 5, 'num_pages' => 2 ] );
		$this->assertStringContainsString( '<span class="index-range">Question <span class="i-start">6</span> to <span class="i-end">10</span> of 10</span>', $html );
		$this->assertStringContainsString( '<span class="index-single lp-hidden">', $html );
	}

	public function test_format_timer(): void {
		$template = UserQuizTemplate::instance();

		$this->assertSame( '00:00', $template->format_timer([
				'seconds' => 0,
				'total' => 1800,
			]) );
		$this->assertSame( '29:59', $template->format_timer([
				'seconds' => 1799,
				'total' => 1800,
			]) );
		$this->assertSame( '01:00:05', $template->format_timer([
				'seconds' => 3605,
				'total' => 7200,
			]) );
	}

	// -------------------------------------------------------------------------
	// Check question (instant check)
	// -------------------------------------------------------------------------

	public function test_html_question_instant_check_returns_checked_question_visible(): void {
		$this->add_questions( [ 101, 102 ] );

		$html = UserQuizTemplate::instance()->html_question(
			[
				'courseModel'       => $this->make_course(),
				'quizPostModel'     => $this->make_quiz(
					[
						'question_ids'  => [ 101, 102 ],
						'per_page'      => 1,
						'instant_check' => true,
						'show_correct'  => true,
					]
				),
				'questionPostModel' => $this->questions[102],
				'userQuizModel'     => false,
				'is_guest'          => true,
				'action_type'       => 'instant_check_question',
				'position'          => 1,
				'result_questions'  => [
					102 => [
						'answered' => 'b',
						'mark'     => 0,
						'correct'  => false,
					],
				],
			]
		);

		$this->assertStringStartsWith( '<div class="question question-single_choice question-answered" data-id="102" data-page="2">', $html );
		$this->assertStringContainsString( '<span class="question-index">2.</span>', $html );
		$this->assertStringContainsString( 'answer-option answer-correct', $html );
		$this->assertStringContainsString( 'answer-option answered-wrong', $html );
		$this->assertStringContainsString( 'question-response incorrect', $html );
		$this->assertStringContainsString( 'Explanation 102', $html );
		$this->assertStringNotContainsString( 'instant-check', $html );
	}

	public function test_html_question_instant_check_hides_correct_answer_when_option_off(): void {
		$this->add_questions( [ 101 ] );

		$html = UserQuizTemplate::instance()->html_question(
			[
				'courseModel'       => $this->make_course(),
				'quizPostModel'     => $this->make_quiz(
					[
						'question_ids'  => [ 101 ],
						'instant_check' => true,
						'show_correct'  => false,
					]
				),
				'questionPostModel' => $this->questions[101],
				'userQuizModel'     => false,
				'is_guest'          => true,
				'action_type'       => 'instant_check_question',
				'position'          => 0,
				'result_questions'  => [
					101 => [
						'answered' => 'b',
						'mark'     => 0,
						'correct'  => false,
					],
				],
			]
		);

		$this->assertStringNotContainsString( 'answer-correct', $html );
		$this->assertStringNotContainsString( 'question-answered', $html );
		$this->assertStringContainsString( 'answer-option answered-wrong', $html );
	}

	// -------------------------------------------------------------------------
	// Completed: result, retake, review
	// -------------------------------------------------------------------------

	private function result_data( array $args = [] ): array {
		return array_merge(
			[
				'questions'        => [
					101 => [
						'answered' => 'a',
						'correct'  => true,
						'mark'     => 1,
					],
					102 => [
						'answered' => 'b',
						'correct'  => false,
						'mark'     => 0,
					],
				],
				'mark'             => 10,
				'user_mark'        => 8,
				'minus_point'      => 1,
				'question_count'   => 5,
				'question_empty'   => 1,
				'question_wrong'   => 1,
				'question_correct' => 3,
				'result'           => 85,
				'time_spend'       => '00:05:10',
				'passing_grade'    => 80,
				'pass'             => 1,
			],
			$args
		);
	}

	/**
	 * @param array $args [ graduation, remaining, history, result, can_retake (true|WP_Error) ]
	 *
	 * @return UserQuizModel|Mockery\MockInterface
	 */
	private function make_user_quiz_completed( array $args = [] ) {
		$args = array_merge(
			[
				'graduation' => 'passed',
				'remaining'  => 0,
				'history'    => [],
				'result'     => $this->result_data(),
				'can_retake' => true,
			],
			$args
		);

		$userQuizModel = Mockery::mock( UserQuizModel::class )->makePartial();
		$userQuizModel->shouldReceive( 'can_retake' )->andReturn( $args['can_retake'] );
		$userQuizModel->shouldReceive( 'get_status' )->andReturn( UserQuizModel::STATUS_COMPLETED );
		$userQuizModel->shouldReceive( 'get_graduation' )->andReturn( $args['graduation'] );
		$userQuizModel->shouldReceive( 'get_remaining_retake' )->andReturn( $args['remaining'] );
		$userQuizModel->shouldReceive( 'get_history' )->andReturn( $args['history'] );
		$userQuizModel->shouldReceive( 'get_result' )->andReturn( $args['result'] );

		return $userQuizModel;
	}

	public function test_html_screen_completed_shows_result_history_and_hidden_review(): void {
		$this->add_questions( [ 101, 102 ] );

		$html = UserQuizTemplate::instance()->html_screen([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz( [ 'question_ids' => [ 101, 102 ] ] ),
				'userQuizModel' => $this->make_user_quiz_completed( [ 'history' => [ [ 'question_count' => 5 ] ] ] ),
			]);

		$this->assertStringContainsString( '<div class="lp-quiz-completed"><div class="lp-quiz-result-wrap"><div class="quiz-result passed">', $html );
		$this->assertStringContainsString( 'class="quiz-attempts"', $html );
		$this->assertStringContainsString( '<div class="lp-quiz-review lp-hidden"', $html );
		$this->assertStringContainsString( 'lp-button review-quiz', $html );
		$this->assertStringNotContainsString( 'lp-quiz-doing', $html );
		$this->assertStringNotContainsString( 'quiz-intro', $html );
	}

	public function test_html_result_shows_grade_and_statistic(): void {
		$html = UserQuizTemplate::instance()->html_result([
				'quizPostModel' => $this->make_quiz(),
				'result' => $this->result_data(),
				'userQuizModel' => $this->make_user_quiz_completed(),
			]);

		$this->assertStringStartsWith( '<div class="quiz-result passed"><h3 class="result-heading">Your Result</h3>', $html );
		$this->assertStringContainsString( '<span class="result-achieved">85%</span><span class="result-require">80%</span>', $html );
		$this->assertStringContainsString( '<p class="result-message">Passed</p>', $html );
		$this->assertStringContainsString( '<li class="result-statistic-field result-time-spend"><span>Time spent</span><p>00:05:10</p></li>', $html );
		$this->assertStringContainsString( '<li class="result-statistic-field result-point"><span>Points</span><p>8 / 10</p></li>', $html );
		$this->assertStringContainsString( '<li class="result-statistic-field result-questions"><span>Questions</span><p>5</p></li>', $html );
		$this->assertStringContainsString( '<li class="result-statistic-field result-questions-correct"><span>Correct</span><p>3</p></li>', $html );
		$this->assertStringContainsString( '<li class="result-statistic-field result-questions-wrong"><span>Wrong</span><p>1</p></li>', $html );
		$this->assertStringContainsString( '<li class="result-statistic-field result-questions-skipped"><span>Skipped</span><p>1</p></li>', $html );
		$this->assertStringNotContainsString( 'result-questions-minus', $html );
	}

	public function test_html_result_circle_offset_by_percent(): void {
		$html = UserQuizTemplate::instance()->html_result([
				'quizPostModel' => $this->make_quiz(),
				'result' => $this->result_data( [ 'result' => 50 ] ),
			]);

		// r = 95, circumference = 2 * PI * 95 = 596.903, offset = half.
		$this->assertStringContainsString( 'style="stroke-dasharray: 596.903 596.903; stroke-dashoffset: 298.451;"', $html );
		$this->assertStringContainsString( 'r="95" cx="100" cy="100"', $html );
	}

	public function test_html_result_guest_failed_from_pass_and_minus_point_when_negative_marking(): void {
		$html = UserQuizTemplate::instance()->html_result([
				'quizPostModel' => $this->make_quiz( [ 'negative' => true ] ),
				'result' => $this->result_data(
								[
									'result'    => 66.666,
									'pass'      => 0,
									'user_mark' => 6.5,
								]
							),
			]);

		$this->assertStringContainsString( '<span class="result-achieved">66.67%</span>', $html );
		$this->assertStringContainsString( '<p>6.50 / 10</p>', $html );
		$this->assertStringContainsString( '<li class="result-statistic-field result-questions-minus"><span>Minus points</span><p>1</p></li>', $html );
	}

	public function test_html_btn_retake_none_when_retake_zero(): void {
		$html = UserQuizTemplate::instance()->html_btn_retake([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz( [ 'retake' => 0 ] ),
				'userQuizModel' => $this->make_user_quiz_completed(),
			]);

		$this->assertSame( '', $html );
	}

	public function test_html_btn_retake_shows_remaining(): void {
		$html = UserQuizTemplate::instance()->html_btn_retake([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz( [ 'retake' => 3 ] ),
				'userQuizModel' => $this->make_user_quiz_completed( [ 'remaining' => 2 ] ),
			]);

		$this->assertStringContainsString( 'class="lp-button start retake-quiz" data-retake-max="3"', $html );
		$this->assertStringContainsString( 'data-title="Retake" data-content="Are you sure you want to retake the quiz?"', $html );
		$this->assertStringContainsString( '>Retake (2)</button>', $html );

		preg_match( '/data-send="([^"]*)"/', $html, $matches );
		$data = json_decode( html_entity_decode( $matches[1] ), true );
		$this->assertSame( 'user-retake-quiz', $data['id_url'] );
		$this->assertSame( 'user_retake_quiz', $data['action'] );
		$this->assertSame( 20, $data['quiz_id'] );
		$this->assertSame( 30, $data['course_id'] );
	}

	public function test_html_btn_retake_none_when_model_can_not_retake(): void {
		// Remaining retake is 0, or course can not impact item (finished, expired).
		$html = UserQuizTemplate::instance()->html_btn_retake([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz( [ 'retake' => 3 ] ),
				'userQuizModel' => $this->make_user_quiz_completed( [ 'can_retake' => new WP_Error( 'exceed_retaken_count', 'Exceeded' ) ] ),
			]);

		$this->assertSame( '', $html );
	}

	public function test_html_btn_retake_unlimited_has_no_count(): void {
		$html = UserQuizTemplate::instance()->html_btn_retake([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz( [ 'retake' => -1 ] ),
				'userQuizModel' => $this->make_user_quiz_completed(),
			]);

		$this->assertStringContainsString( 'data-retake-max="-1"', $html );
		$this->assertStringContainsString( '>Retake</button>', $html );
	}

	public function test_html_btn_retake_guest_has_no_count(): void {
		$html = UserQuizTemplate::instance()->html_btn_retake([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz( [ 'retake' => 3 ] ),
			]);

		$this->assertStringContainsString( 'data-retake-max="3"', $html );
		$this->assertStringContainsString( '>Retake</button>', $html );
	}

	public function test_html_btn_retake_none_when_password_required(): void {
		Functions\when( 'post_password_required' )->justReturn( true );

		$html = UserQuizTemplate::instance()->html_btn_retake([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz( [ 'retake' => -1 ] ),
			]);

		$this->assertSame( '', $html );
	}

	public function test_html_btn_review_follows_option(): void {
		$template = UserQuizTemplate::instance();

		$this->assertSame(
			'<button type="button" class="lp-button review-quiz">Review</button>',
			self::flat( $template->html_btn_review( [ 'quizPostModel' => $this->make_quiz( [ 'review' => true ] ) ] ) )
		);
		$this->assertSame( '', $template->html_btn_review([
				'quizPostModel' => $this->make_quiz( [ 'review' => false ] ),
			]) );
	}

	public function test_html_review_result_empty_when_review_off(): void {
		$this->add_questions( [ 101 ] );

		$html = UserQuizTemplate::instance()->html_review_result([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz(
								[
									'question_ids' => [ 101 ],
									'review'       => false,
								]
							),
				'result' => $this->result_data(),
			]);

		$this->assertSame( '', $html );
	}

	public function test_html_review_result_read_only_questions_with_correct_answers(): void {
		$this->add_questions( [ 101, 102 ] );

		$html = UserQuizTemplate::instance()->html_review_result([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz(
								[
									'question_ids' => [ 101, 102 ],
									'questions'    => 2,
									'per_page'     => 1,
									'show_correct' => true,
								]
							),
				'userQuizModel' => $this->make_user_quiz_completed(),
				'result' => $this->result_data(),
			]);

		$this->assertStringStartsWith( '<div class="lp-quiz-review lp-hidden" data-per-page="1" data-total="2" data-num-pages="2">', $html );
		$this->assertStringContainsString( 'data-id="102" data-page="2">', $html );
		$this->assertStringContainsString( 'answer-option answer-correct answered-correct', $html );
		$this->assertStringContainsString( 'answer-option answered-wrong', $html );
		$this->assertStringContainsString( 'Explanation 101', $html );
		$this->assertStringNotContainsString( 'name="learn-press-question-', $html );
		$this->assertStringNotContainsString( 'instant-check', $html );
		$this->assertStringContainsString( 'questions-pagination__item', $html );
		$this->assertStringContainsString( '<button type="button" class="lp-button back-quiz">Result</button>', $html );
	}

	public function test_html_review_result_without_show_correct(): void {
		$this->add_questions( [ 101, 102 ] );

		$html = UserQuizTemplate::instance()->html_review_result([
				'courseModel' => $this->make_course(),
				'quizPostModel' => $this->make_quiz(
								[
									'question_ids' => [ 101, 102 ],
									'show_correct' => false,
								]
							),
				'result' => $this->result_data(),
			]);

		$this->assertStringNotContainsString( 'answer-correct', $html );
		$this->assertStringContainsString( 'answer-option answered-correct', $html );
		$this->assertStringContainsString( 'answer-option answered-wrong', $html );
	}
}
