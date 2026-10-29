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

	public function test_html_quiz_content_editor_still_sees_content_but_no_start_when_password_required(): void {
		Functions\when( 'post_password_required' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );

		$html = UserQuizTemplate::instance()->html_quiz_content( $this->make_course(), $this->make_quiz() );

		$this->assertStringContainsString( 'class="quiz-intro"', $html );
		$this->assertStringNotContainsString( 'lp-button start', $html );
	}

	// -------------------------------------------------------------------------
	// html_btn_start
	// -------------------------------------------------------------------------

	public function test_html_btn_start_has_data_send_for_start_action(): void {
		$html = UserQuizTemplate::instance()->html_btn_start( $this->make_course(), $this->make_quiz() );

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
		$html = UserQuizTemplate::instance()->html_history( $this->make_user_quiz( [] ) );

		$this->assertSame( '', $html );
	}

	public function test_html_history_renders_attempt_rows(): void {
		$html = UserQuizTemplate::instance()->html_history(
			$this->make_user_quiz(
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
			)
		);

		$this->assertStringContainsString( 'class="quiz-attempts"', $html );
		$this->assertStringContainsString( 'Last Attempt', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__questions">3 / 5</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__spend">00:10:05</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__marks">6 / 10</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__grade">80</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__result">60.00% <span>Failed</span></td>', $html );
		// Second row: empty time spend / passing grade fall back, graduation key is used.
		$this->assertStringContainsString( '<td class="quiz-attempts__spend">--</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__grade">-</td>', $html );
		$this->assertStringContainsString( '<td class="quiz-attempts__result">100.00% <span>Passed</span></td>', $html );
	}

	public function test_html_history_escapes_values(): void {
		$html = UserQuizTemplate::instance()->html_history(
			$this->make_user_quiz( [ [ 'time_spend' => '<script>alert(1)</script>' ] ] )
		);

		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_html_intro_includes_history_for_user_quiz(): void {
		$html = UserQuizTemplate::instance()->html_intro(
			$this->make_course(),
			$this->make_quiz(),
			$this->make_user_quiz( [ [ 'question_count' => 5 ] ] )
		);

		$this->assertStringContainsString( 'quiz-attempts', $html );
		$this->assertStringContainsString( 'lp-button start', $html );
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
			$question->shouldReceive( 'get_answer_options' )->andReturnUsing(
				static function ( $args ) {
					$options = [
						[
							'uid'     => 1,
							'title'   => 'A',
							'value'   => 'a',
							'is_true' => 'yes',
						],
						[
							'uid'     => 2,
							'title'   => 'B',
							'value'   => 'b',
							'is_true' => 'no',
						],
					];

					return array_map(
						static fn( $option ) => array_diff_key( $option, array_flip( $args['exclude'] ) ),
						$options
					);
				}
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

		$html = UserQuizTemplate::instance()->html_screen(
			$this->make_course(),
			$this->make_quiz( [ 'question_ids' => [ 101 ] ] ),
			$this->make_user_quiz_started()
		);

		$this->assertStringContainsString( 'class="lp-quiz-doing"', $html );
		$this->assertStringContainsString( 'class="quiz-status"', $html );
		$this->assertStringNotContainsString( 'lp-button start', $html );
		$this->assertStringNotContainsString( 'quiz-intro', $html );
	}

	public function test_html_doing_quiz_paginates_questions(): void {
		$this->add_questions( [ 101, 102, 103 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz(
			$this->make_course(),
			$this->make_quiz(
				[
					'question_ids' => [ 101, 102, 103 ],
					'per_page'     => 2,
				]
			),
			$this->make_user_quiz_started()
		);

		$this->assertStringContainsString( 'data-per-page="2" data-total="3" data-num-pages="2"', $html );
		$this->assertStringContainsString( 'Question <span>1 to 2 of 3</span>', $html );
		$this->assertStringContainsString( 'data-id="101" data-page="1">', $html );
		$this->assertStringContainsString( 'data-id="102" data-page="1">', $html );
		$this->assertStringContainsString( 'data-id="103" data-page="2" style="display: none;">', $html );
		$this->assertStringContainsString( '<span class="question-index">3.</span>', $html );
		$this->assertStringContainsString( '<div class="questions-pagination" data-num-pages="2"></div>', $html );
		// Finish button on status bar always shows, bottom one hidden until last page.
		$this->assertStringContainsString( 'id="button-submit-quiz"', $html );
		$this->assertStringContainsString( 'class="lp-button submit-quiz" hidden', $html );
		$this->assertStringNotContainsString( 'is-last', $html );
	}

	public function test_html_doing_quiz_all_questions_on_one_page_when_pagination_zero(): void {
		$this->add_questions( [ 101, 102, 103 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz(
			$this->make_course(),
			$this->make_quiz(
				[
					'question_ids' => [ 101, 102, 103 ],
					'per_page'     => 0,
				]
			),
			$this->make_user_quiz_started()
		);

		$this->assertStringContainsString( 'data-num-pages="1"', $html );
		$this->assertStringContainsString( 'Question <span>1 to 3 of 3</span>', $html );
		$this->assertStringNotContainsString( 'display: none;"', $html );
		$this->assertStringNotContainsString( 'questions-pagination', $html );
		$this->assertStringContainsString( 'quiz-buttons align-center is-first is-last', $html );
		$this->assertStringContainsString( 'class="lp-button submit-quiz" data-title="Finish Quiz" data-content="Are you sure to submit the quiz?"', $html );
	}

	public function test_html_doing_quiz_timer_counts_down_from_remaining(): void {
		$this->add_questions( [ 101 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz(
			$this->make_course(),
			$this->make_quiz( [ 'question_ids' => [ 101 ] ] ),
			$this->make_user_quiz_started( [ 'remaining' => 1680 ] )
		);

		$this->assertMatchesRegularExpression( '/data-duration="1800" data-remaining="1680" data-time-spend="1[12]\d"/', $html );
		$this->assertStringContainsString( '<span>28:00</span>', $html );
		$this->assertStringContainsString( 'name="lp-quiz-time-spend"', $html );
	}

	public function test_html_doing_quiz_guest_uses_full_duration(): void {
		$this->add_questions( [ 101 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz(
			$this->make_course(),
			$this->make_quiz( [ 'question_ids' => [ 101 ] ] )
		);

		$this->assertStringContainsString( 'data-duration="1800" data-remaining="1800" data-time-spend="0"', $html );
		$this->assertStringContainsString( '<span>30:00</span>', $html );
		$this->assertStringContainsString( 'Earned Point: <span class="current-point__value">0</span>', $html );
	}

	public function test_html_doing_quiz_instant_check_button_only_for_unchecked(): void {
		$this->add_questions( [ 101, 102 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz(
			$this->make_course(),
			$this->make_quiz(
				[
					'question_ids'  => [ 101, 102 ],
					'instant_check' => true,
					'show_correct'  => true,
				]
			),
			$this->make_user_quiz_started(
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
			)
		);

		$this->assertSame( 1, substr_count( $html, 'lp-button instant-check' ) );
		$this->assertStringContainsString( '&quot;question_id&quot;:102', $html );
		$this->assertStringNotContainsString( '&quot;question_id&quot;:101', $html );
		// Checked question shows result, correct answer and explanation.
		$this->assertStringContainsString( 'question question-single_choice question-answered" data-id="101"', $html );
		$this->assertStringContainsString( 'answer-option answer-correct answered-correct', $html );
		$this->assertStringContainsString( 'Explanation 101', $html );
		$this->assertStringNotContainsString( 'Explanation 102', $html );
		$this->assertStringContainsString( 'Earned Point: <span class="current-point__value">1</span>', $html );
	}

	public function test_html_doing_quiz_no_instant_check_button_when_option_off(): void {
		$this->add_questions( [ 101 ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz(
			$this->make_course(),
			$this->make_quiz( [ 'question_ids' => [ 101 ] ] ),
			$this->make_user_quiz_started()
		);

		$this->assertStringNotContainsString( 'instant-check', $html );
		$this->assertStringNotContainsString( 'answer-correct', $html );
	}

	public function test_html_doing_quiz_edit_link_only_for_user_can_edit(): void {
		$this->add_questions( [ 101 ] );
		$quiz = $this->make_quiz( [ 'question_ids' => [ 101 ] ] );

		$html = UserQuizTemplate::instance()->html_doing_quiz( $this->make_course(), $quiz, $this->make_user_quiz_started() );
		$this->assertStringNotContainsString( 'edit-link', $html );

		Functions\when( 'current_user_can' )->justReturn( true );
		$html = UserQuizTemplate::instance()->html_doing_quiz( $this->make_course(), $quiz, $this->make_user_quiz_started() );
		$this->assertStringContainsString( '<span class="edit-link"><a href="https://example.com/wp-admin/post.php?post=101', $html );
	}

	public function test_html_doing_buttons_nav_center_when_setting_no(): void {
		\LP_Settings::$options['navigation_position'] = 'no';

		$html = UserQuizTemplate::instance()->html_doing_buttons( $this->make_course(), $this->make_quiz(), 2 );

		$this->assertStringContainsString( 'class="button-left nav-center"', $html );
	}

	public function test_html_questions_index(): void {
		$template = UserQuizTemplate::instance();

		$this->assertSame( 'Question <span>1 of 10</span>', $template->html_questions_index( 1, 1, 10 ) );
		$this->assertSame( 'Question <span>6 to 10 of 10</span>', $template->html_questions_index( 6, 10, 10 ) );
	}

	public function test_format_timer(): void {
		$template = UserQuizTemplate::instance();

		$this->assertSame( '00:00', $template->format_timer( 0, 1800 ) );
		$this->assertSame( '29:59', $template->format_timer( 1799, 1800 ) );
		$this->assertSame( '01:00:05', $template->format_timer( 3605, 7200 ) );
	}

	// -------------------------------------------------------------------------
	// Check question (instant check)
	// -------------------------------------------------------------------------

	public function test_html_check_quiz_returns_checked_question_visible(): void {
		$this->add_questions( [ 101, 102 ] );

		$html = UserQuizTemplate::instance()->html_check_quiz(
			$this->make_course(),
			$this->make_quiz(
				[
					'question_ids'  => [ 101, 102 ],
					'per_page'      => 1,
					'instant_check' => true,
					'show_correct'  => true,
				]
			),
			$this->questions[102],
			[
				'answered' => 'b',
				'mark'     => 0,
				'correct'  => false,
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

	public function test_html_check_quiz_hides_correct_answer_when_option_off(): void {
		$this->add_questions( [ 101 ] );

		$html = UserQuizTemplate::instance()->html_check_quiz(
			$this->make_course(),
			$this->make_quiz(
				[
					'question_ids'  => [ 101 ],
					'instant_check' => true,
					'show_correct'  => false,
				]
			),
			$this->questions[101],
			[
				'answered' => 'b',
				'mark'     => 0,
				'correct'  => false,
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

		$html = UserQuizTemplate::instance()->html_screen(
			$this->make_course(),
			$this->make_quiz( [ 'question_ids' => [ 101, 102 ] ] ),
			$this->make_user_quiz_completed( [ 'history' => [ [ 'question_count' => 5 ] ] ] )
		);

		$this->assertStringContainsString( '<div class="lp-quiz-completed"><div class="lp-quiz-result-wrap"><div class="quiz-result passed">', $html );
		$this->assertStringContainsString( 'class="quiz-attempts"', $html );
		$this->assertStringContainsString( '<div class="lp-quiz-review" style="display: none;"', $html );
		$this->assertStringContainsString( 'lp-button review-quiz', $html );
		$this->assertStringNotContainsString( 'lp-quiz-doing', $html );
		$this->assertStringNotContainsString( 'quiz-intro', $html );
	}

	public function test_html_result_shows_grade_and_statistic(): void {
		$html = UserQuizTemplate::instance()->html_result(
			$this->make_quiz(),
			$this->result_data(),
			$this->make_user_quiz_completed()
		);

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
		$html = UserQuizTemplate::instance()->html_result( $this->make_quiz(), $this->result_data( [ 'result' => 50 ] ) );

		// r = 95, circumference = 2 * PI * 95 = 596.903, offset = half.
		$this->assertStringContainsString( 'style="stroke-dasharray: 596.903 596.903; stroke-dashoffset: 298.451;"', $html );
		$this->assertStringContainsString( 'r="95" cx="100" cy="100"', $html );
	}

	public function test_html_result_guest_failed_from_pass_and_minus_point_when_negative_marking(): void {
		$html = UserQuizTemplate::instance()->html_result(
			$this->make_quiz( [ 'negative' => true ] ),
			$this->result_data(
				[
					'result'    => 66.666,
					'pass'      => 0,
					'user_mark' => 6.5,
				]
			)
		);

		$this->assertStringStartsWith( '<div class="quiz-result failed">', $html );
		$this->assertStringContainsString( '<span class="result-achieved">66.67%</span>', $html );
		$this->assertStringContainsString( '<p class="result-message">Failed</p>', $html );
		$this->assertStringContainsString( '<p>6.50 / 10</p>', $html );
		$this->assertStringContainsString( '<li class="result-statistic-field result-questions-minus"><span>Minus points</span><p>1</p></li>', $html );
	}

	public function test_html_btn_retake_none_when_retake_zero(): void {
		$html = UserQuizTemplate::instance()->html_btn_retake( $this->make_course(), $this->make_quiz( [ 'retake' => 0 ] ), $this->make_user_quiz_completed() );

		$this->assertSame( '', $html );
	}

	public function test_html_btn_retake_shows_remaining(): void {
		$html = UserQuizTemplate::instance()->html_btn_retake(
			$this->make_course(),
			$this->make_quiz( [ 'retake' => 3 ] ),
			$this->make_user_quiz_completed( [ 'remaining' => 2 ] )
		);

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
		$html = UserQuizTemplate::instance()->html_btn_retake(
			$this->make_course(),
			$this->make_quiz( [ 'retake' => 3 ] ),
			$this->make_user_quiz_completed( [ 'can_retake' => new WP_Error( 'exceed_retaken_count', 'Exceeded' ) ] )
		);

		$this->assertSame( '', $html );
	}

	public function test_html_btn_retake_unlimited_has_no_count(): void {
		$html = UserQuizTemplate::instance()->html_btn_retake(
			$this->make_course(),
			$this->make_quiz( [ 'retake' => -1 ] ),
			$this->make_user_quiz_completed()
		);

		$this->assertStringContainsString( 'data-retake-max="-1"', $html );
		$this->assertStringContainsString( '>Retake</button>', $html );
	}

	public function test_html_btn_retake_guest_has_no_count(): void {
		$html = UserQuizTemplate::instance()->html_btn_retake( $this->make_course(), $this->make_quiz( [ 'retake' => 3 ] ) );

		$this->assertStringContainsString( 'data-retake-max="3"', $html );
		$this->assertStringContainsString( '>Retake</button>', $html );
	}

	public function test_html_btn_retake_none_when_password_required(): void {
		Functions\when( 'post_password_required' )->justReturn( true );

		$html = UserQuizTemplate::instance()->html_btn_retake( $this->make_course(), $this->make_quiz( [ 'retake' => -1 ] ) );

		$this->assertSame( '', $html );
	}

	public function test_html_btn_review_follows_option(): void {
		$template = UserQuizTemplate::instance();

		$this->assertSame( '<button type="button" class="lp-button review-quiz">Review</button>', $template->html_btn_review( $this->make_quiz( [ 'review' => true ] ) ) );
		$this->assertSame( '', $template->html_btn_review( $this->make_quiz( [ 'review' => false ] ) ) );
	}

	public function test_html_review_result_empty_when_review_off(): void {
		$this->add_questions( [ 101 ] );

		$html = UserQuizTemplate::instance()->html_review_result(
			$this->make_course(),
			$this->make_quiz(
				[
					'question_ids' => [ 101 ],
					'review'       => false,
				]
			),
			$this->result_data()
		);

		$this->assertSame( '', $html );
	}

	public function test_html_review_result_read_only_questions_with_correct_answers(): void {
		$this->add_questions( [ 101, 102 ] );

		$html = UserQuizTemplate::instance()->html_review_result(
			$this->make_course(),
			$this->make_quiz(
				[
					'question_ids' => [ 101, 102 ],
					'per_page'     => 1,
					'show_correct' => true,
				]
			),
			$this->result_data()
		);

		$this->assertStringStartsWith( '<div class="lp-quiz-review" style="display: none;" data-per-page="1" data-total="2" data-num-pages="2">', $html );
		$this->assertStringContainsString( 'data-id="102" data-page="2" style="display: none;"', $html );
		$this->assertStringContainsString( 'answer-option answer-correct answered-correct', $html );
		$this->assertStringContainsString( 'answer-option answered-wrong', $html );
		$this->assertStringContainsString( 'Explanation 101', $html );
		$this->assertStringNotContainsString( 'name="learn-press-question-', $html );
		$this->assertStringNotContainsString( 'instant-check', $html );
		$this->assertStringContainsString( '<div class="questions-pagination" data-num-pages="2"></div>', $html );
		$this->assertStringContainsString( '<button type="button" class="lp-button back-quiz">Result</button>', $html );
	}

	public function test_html_review_result_without_show_correct(): void {
		$this->add_questions( [ 101, 102 ] );

		$html = UserQuizTemplate::instance()->html_review_result(
			$this->make_course(),
			$this->make_quiz(
				[
					'question_ids' => [ 101, 102 ],
					'show_correct' => false,
				]
			),
			$this->result_data()
		);

		$this->assertStringNotContainsString( 'answer-correct', $html );
		$this->assertStringContainsString( 'answer-option answered-correct', $html );
		$this->assertStringContainsString( 'answer-option answered-wrong', $html );
	}
}
