<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Ajax;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Exception;
use LearnPress\Ajax\UserQuizAjax;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\UserItem\UserQuizTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;

require_once dirname( __DIR__, 3 ) . '/inc/class-lp-duration.php';

/**
 * Unit tests for UserQuizAjax: start, retake, submit, instant check and security checks.
 *
 * Strategy: UserQuizAjax is a partial mock, finders / template / cache are protected seams,
 * so no DB is needed. wp_send_json() is captured.
 */
class UserQuizAjaxTest extends BrainMonkeyTestCase {

	/**
	 * Fixed time(), see patchwork.json.
	 */
	private const NOW = 1000000;

	/**
	 * @var object|null response sent by wp_send_json()
	 */
	private $sent;

	/**
	 * @var array args passed to template methods, key is method name
	 */
	private $template_args = [];

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'LP_Request', false ) ) {
			eval(
				'class LP_Request {
					public static array $params = array();
					public static function get_param( string $key, $default = null, $type = "", $method = "" ) {
						return self::$params[ $key ] ?? $default;
					}
				}'
			);
		}
		if ( ! class_exists( 'LP_Helper', false ) ) {
			eval(
				'class LP_Helper {
					public static function json_decode( string $json, bool $assoc = false ) {
						return json_decode( $json, $assoc );
					}
				}'
			);
		}
		if ( ! class_exists( 'LP_Debug', false ) ) {
			eval(
				'class LP_Debug {
					public static array $logged = array();
					public static function error_log( $error ) {
						self::$logged[] = $error instanceof \\Throwable ? $error->getMessage() : (string) $error;
					}
				}'
			);
		}
		\LP_Request::$params = [];
		\LP_Debug::$logged   = [];

		$this->sent = null;
		Functions\when( 'wp_send_json' )->alias(
			function ( $response ) {
				$this->sent = $response;
			}
		);
		Functions\when( 'get_current_user_id' )->justReturn( 7 );
		Functions\when( 'time' )->justReturn( self::NOW );
		Functions\when( 'post_password_required' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	}

	/**
	 * @param array $data
	 */
	private function set_data( array $data ): void {
		\LP_Request::$params['data'] = json_encode( $data );
	}

	/**
	 * @param QuizPostModel|false|null $quiz null to create quiz mock
	 * @param bool $no_enroll
	 *
	 * @return CourseModel|Mockery\MockInterface
	 */
	private function make_course( $quiz = null, bool $no_enroll = false ) {
		if ( null === $quiz ) {
			$quiz = $this->make_quiz();
		}

		$course = Mockery::mock( CourseModel::class );
		$course->shouldReceive( 'get_id' )->andReturn( 30 );
		$course->shouldReceive( 'get_item_model' )->with( 20, LP_QUIZ_CPT )->andReturn( $quiz );
		$course->shouldReceive( 'has_no_enroll_requirement' )->andReturn( $no_enroll );

		return $course;
	}

	/**
	 * @param bool $instant_check
	 * @param bool $negative_marking
	 * @param string $duration
	 *
	 * @return QuizPostModel|Mockery\MockInterface
	 */
	private function make_quiz( bool $instant_check = true, bool $negative_marking = false, string $duration = '0 minute' ) {
		$quiz = Mockery::mock( QuizPostModel::class );
		$quiz->shouldReceive( 'get_id' )->andReturn( 20 );
		$quiz->shouldReceive( 'get_duration' )->andReturn( $duration );
		$quiz->shouldReceive( 'get_question_ids' )->andReturn( [ 101, 102 ] );
		$quiz->shouldReceive( 'has_instant_check' )->andReturn( $instant_check );
		$quiz->shouldReceive( 'has_negative_marking' )->andReturn( $negative_marking );

		return $quiz;
	}

	/**
	 * Start time of user quiz, $elapsed seconds before now.
	 */
	private function start_time( int $elapsed ): string {
		return date( 'Y-m-d H:i:s', self::NOW - $elapsed );
	}

	private function make_user() {
		$user = Mockery::mock( UserModel::class );
		$user->shouldReceive( 'get_id' )->andReturn( 7 );

		return $user;
	}

	/**
	 * @param array $finders [ course, user, user_course, user_quiz, new_user_quiz, guest_user_quiz, question, html_finish_course ]
	 * @param string $doing_html
	 *
	 * @return UserQuizAjax|Mockery\MockInterface
	 */
	private function make_ajax( array $finders, string $doing_html = '<div class="lp-quiz-doing"></div>' ) {
		$ajax = Mockery::mock( UserQuizAjax::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$ajax->shouldReceive( 'find_course' )->andReturn( $finders['course'] ?? false );
		$ajax->shouldReceive( 'find_user' )->andReturn( $finders['user'] ?? false );
		$ajax->shouldReceive( 'find_user_course' )->andReturn( $finders['user_course'] ?? false );
		$ajax->shouldReceive( 'find_user_quiz' )->andReturn( $finders['user_quiz'] ?? false );
		$ajax->shouldReceive( 'find_question' )->andReturn( $finders['question'] ?? false );
		$ajax->shouldReceive( 'html_btn_finish_course' )->andReturn( $finders['html_finish_course'] ?? '' );
		if ( isset( $finders['new_user_quiz'] ) ) {
			$ajax->shouldReceive( 'create_user_quiz' )->with( 7, 20, 30 )->andReturn( $finders['new_user_quiz'] );
		}
		if ( isset( $finders['guest_user_quiz'] ) ) {
			$ajax->shouldReceive( 'create_user_quiz' )->with( 0, 20, 30 )->andReturn( $finders['guest_user_quiz'] );
		}

		$this->template_args = [];
		$template            = Mockery::mock( UserQuizTemplate::class );
		$template->shouldReceive( 'html_doing_quiz' )->andReturn( $doing_html );
		$template->shouldReceive( 'html_completed' )->andReturnUsing(
			function ( ...$args ) {
				$this->template_args['html_completed'] = $args;

				return '<div class="lp-quiz-completed"></div>';
			}
		);
		$template->shouldReceive( 'html_check_quiz' )->andReturnUsing(
			function ( ...$args ) {
				$this->template_args['html_check_quiz'] = $args;

				return '<div class="question"></div>';
			}
		);
		$ajax->shouldReceive( 'get_template' )->andReturn( $template );

		return $ajax;
	}

	private function valid_data(): array {
		return [
			'quiz_id'   => 20,
			'course_id' => 30,
		];
	}

	// -------------------------------------------------------------------------
	// Security / validation (via user_start_quiz)
	// -------------------------------------------------------------------------

	public function test_error_when_data_not_json(): void {
		\LP_Request::$params['data'] = 'not json';

		$this->make_ajax( [] )->user_start_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'Params invalid!', $this->sent->message );
	}

	public function test_error_when_ids_missing(): void {
		$this->set_data( [ 'quiz_id' => 20 ] );

		$this->make_ajax( [] )->user_start_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'Params invalid!', $this->sent->message );
	}

	public function test_error_when_data_is_array(): void {
		\LP_Request::$params['data'] = [ 'quiz_id' => 20, 'course_id' => 30 ];

		$this->make_ajax( [] )->user_start_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'Params invalid!', $this->sent->message );
	}

	public function test_php_error_is_logged_not_sent_to_user(): void {
		$this->set_data( $this->valid_data() );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		$userQuizModel->shouldReceive( 'handle_start' )->andThrow( new \Error( 'Call to a member function on null in /var/www/secret.php' ) );

		$this->make_ajax(
			[
				'course'        => $this->make_course(),
				'user'          => $this->make_user(),
				'user_course'   => Mockery::mock( UserCourseModel::class ),
				'new_user_quiz' => $userQuizModel,
			]
		)->user_start_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'Something went wrong, please try again!', $this->sent->message );
		$this->assertSame( [ 'Call to a member function on null in /var/www/secret.php' ], \LP_Debug::$logged );
	}

	public function test_error_when_course_invalid(): void {
		$this->set_data( $this->valid_data() );

		$this->make_ajax( [ 'course' => false ] )->user_start_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'The course is invalid', $this->sent->message );
	}

	public function test_error_when_quiz_not_in_course(): void {
		$this->set_data( $this->valid_data() );

		$this->make_ajax( [ 'course' => $this->make_course( false ) ] )->user_start_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'The quiz is invalid', $this->sent->message );
	}

	public function test_error_when_password_protected(): void {
		$this->set_data( $this->valid_data() );
		Functions\when( 'post_password_required' )->alias( static fn( $post_id ) => 20 === $post_id );
		// Even editor can not start quiz password protected, same as old REST.
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->make_ajax( [ 'course' => $this->make_course() ] )->user_start_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'This content is password protected.', $this->sent->message );
	}

	public function test_error_when_logged_in_user_invalid(): void {
		$this->set_data( $this->valid_data() );

		$this->make_ajax( [ 'course' => $this->make_course() ] )->user_start_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'The user is invalid', $this->sent->message );
	}

	public function test_error_when_guest_and_course_required_enroll(): void {
		$this->set_data( $this->valid_data() );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$this->make_ajax( [ 'course' => $this->make_course( null, false ) ] )->user_start_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'You have to log in to start the quiz.', $this->sent->message );
	}

	public function test_error_when_user_not_enrolled(): void {
		$this->set_data( $this->valid_data() );

		$this->make_ajax(
			[
				'course' => $this->make_course(),
				'user'   => $this->make_user(),
			]
		)->user_start_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'User not enrolled course!', $this->sent->message );
	}

	// -------------------------------------------------------------------------
	// user_start_quiz
	// -------------------------------------------------------------------------

	public function test_start_quiz_success_returns_doing_html(): void {
		$this->set_data( $this->valid_data() );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		$userQuizModel->shouldReceive( 'handle_start' )->once();

		$ajax = $this->make_ajax(
			[
				'course'        => $this->make_course(),
				'user'          => $this->make_user(),
				'user_course'   => Mockery::mock( UserCourseModel::class ),
				'new_user_quiz' => $userQuizModel,
			]
		);
		$ajax->shouldReceive( 'clear_quiz_cache' )->once()->with( 20, 7, 30 );
		Actions\expectDone( 'learn-press/user/before/start-quiz' )->once()->with( 20, 30, 7 );

		$ajax->user_start_quiz();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( '<div class="lp-quiz-doing"></div>', $this->sent->data->content );
		$this->assertSame( 'started', $this->sent->data->status );
	}

	public function test_start_quiz_error_when_model_can_not_start(): void {
		$this->set_data( $this->valid_data() );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		$userQuizModel->shouldReceive( 'handle_start' )->once()->andThrow( new Exception( 'You have already started the quiz.' ) );

		$ajax = $this->make_ajax(
			[
				'course'        => $this->make_course(),
				'user'          => $this->make_user(),
				'user_course'   => Mockery::mock( UserCourseModel::class ),
				'new_user_quiz' => $userQuizModel,
			]
		);
		$ajax->shouldReceive( 'clear_quiz_cache' )->never();

		$ajax->user_start_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'You have already started the quiz.', $this->sent->message );
	}

	public function test_start_quiz_guest_not_saved(): void {
		$this->set_data( $this->valid_data() );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$ajax = $this->make_ajax( [ 'course' => $this->make_course( null, true ) ], '<div>guest</div>' );
		$ajax->shouldReceive( 'create_user_quiz' )->never();
		$ajax->shouldReceive( 'clear_quiz_cache' )->never();

		$ajax->user_start_quiz();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( '<div>guest</div>', $this->sent->data->content );
	}

	// -------------------------------------------------------------------------
	// user_retake_quiz
	// -------------------------------------------------------------------------

	public function test_retake_quiz_success(): void {
		$this->set_data( $this->valid_data() );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		$userQuizModel->shouldReceive( 'handle_retake' )->once();

		$ajax = $this->make_ajax(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
				'user_quiz'   => $userQuizModel,
			]
		);
		$ajax->shouldReceive( 'clear_quiz_cache' )->once()->with( 20, 7, 30 );

		$ajax->user_retake_quiz();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( '<div class="lp-quiz-doing"></div>', $this->sent->data->content );
	}

	public function test_retake_quiz_error_when_not_started(): void {
		$this->set_data( $this->valid_data() );

		$ajax = $this->make_ajax(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
			]
		);

		$ajax->user_retake_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'You have not started the quiz.', $this->sent->message );
	}

	public function test_retake_quiz_error_when_model_can_not_retake(): void {
		$this->set_data( $this->valid_data() );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		$userQuizModel->shouldReceive( 'handle_retake' )->once()->andThrow( new Exception( 'You have exceeded the number of retakes.' ) );

		$ajax = $this->make_ajax(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
				'user_quiz'   => $userQuizModel,
			]
		);

		$ajax->user_retake_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'You have exceeded the number of retakes.', $this->sent->message );
	}

	public function test_retake_quiz_guest_not_saved(): void {
		$this->set_data( $this->valid_data() );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$ajax = $this->make_ajax( [ 'course' => $this->make_course( null, true ) ] );
		$ajax->shouldReceive( 'find_user_quiz' )->never();

		$ajax->user_retake_quiz();

		$this->assertSame( 'success', $this->sent->status );
	}

	// -------------------------------------------------------------------------
	// catch_lp_ajax whitelist
	// -------------------------------------------------------------------------

	public function test_catch_lp_ajax_ignores_helper_methods(): void {
		\LP_Request::$params['lp-load-ajax'] = 'get_context';
		$_REQUEST['lp-load-ajax']            = 'get_context';
		Functions\expect( 'wp_verify_nonce' )->never();

		UserQuizAjax::catch_lp_ajax();

		unset( $_REQUEST['lp-load-ajax'] );
		$this->assertNull( $this->sent );
	}

	public function test_catch_lp_ajax_checks_nonce_for_allowed_action(): void {
		\LP_Request::$params['lp-load-ajax'] = 'user_start_quiz';
		\LP_Request::$params['nonce']        = 'bad';
		$_REQUEST['lp-load-ajax']            = 'user_start_quiz';
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'bad', 'wp_rest' )->andReturn( false );
		Functions\when( 'wp_die' )->alias(
			static function () {
				throw new Exception( 'wp_die' );
			}
		);

		try {
			UserQuizAjax::catch_lp_ajax();
			$this->fail( 'wp_die must be called' );
		} catch ( Exception $e ) {
			$this->assertSame( 'wp_die', $e->getMessage() );
		} finally {
			unset( $_REQUEST['lp-load-ajax'] );
		}

		$this->assertNull( $this->sent );
	}

	// -------------------------------------------------------------------------
	// user_submit_quiz
	// -------------------------------------------------------------------------

	private function submit_data(): array {
		return array_merge(
			$this->valid_data(),
			[
				'answered'   => [
					101 => 'a',
					102 => [ 'x', 'y', [ 'nested' ] ],
					999 => 'not in quiz',
				],
				// Browser sends wrong time, logged in user: server calculates.
				'time_spend' => '5',
			]
		);
	}

	private function quiz_result( int $pass = 1 ): array {
		return [
			'questions' => [ 101 => [ 'correct' => true ] ],
			'result'    => 50,
			'pass'      => $pass,
			'user_mark' => 1,
			'mark'      => 2,
		];
	}

	public function test_submit_quiz_success_finish_with_sanitized_answers(): void {
		$this->set_data( $this->submit_data() );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		$userQuizModel->shouldReceive( 'handle_finish' )
			->once()
			->with(
				[
					'answered'   => [
						101 => 'a',
						102 => [ 'x', 'y' ],
					],
					'time_spend' => 65,
				]
			)
			->andReturn( $this->quiz_result() );
		$userQuizModel->shouldReceive( 'get_graduation' )->andReturn( 'passed' );
		$userQuizModel->shouldReceive( 'get_start_time' )->andReturn( $this->start_time( 65 ) );

		$ajax = $this->make_ajax(
			[
				'course'             => $this->make_course(),
				'user'               => $this->make_user(),
				'user_course'        => Mockery::mock( UserCourseModel::class ),
				'user_quiz'          => $userQuizModel,
				'html_finish_course' => '<form class="form-button-finish-course"></form>',
			]
		);
		$ajax->shouldReceive( 'clear_quiz_cache' )->once()->with( 20, 7, 30 );

		$ajax->user_submit_quiz();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( '<div class="lp-quiz-completed"></div>', $this->sent->data->content );
		$this->assertSame( 'completed', $this->sent->data->status );
		$this->assertSame( 'passed', $this->sent->data->graduation );
		// Result of each question is not sent.
		$this->assertArrayNotHasKey( 'questions', $this->sent->data->results );
		$this->assertSame( 50, $this->sent->data->results['result'] );
		$this->assertSame( $userQuizModel, $this->template_args['html_completed'][3] );
		$this->assertSame( '<form class="form-button-finish-course"></form>', $this->sent->data->html_finish_course );
	}

	public function test_submit_quiz_keeps_fib_text_as_typed(): void {
		$data             = $this->valid_data();
		$data['answered'] = [ 101 => [ 'blank_1' => '  Hà Nội <b> ' ] ];
		$this->set_data( $data );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		$userQuizModel->shouldReceive( 'handle_finish' )
			->once()
			->with(
				[
					'answered'   => [ 101 => [ 'blank_1' => '  Hà Nội <b> ' ] ],
					'time_spend' => 0,
				]
			)
			->andReturn( $this->quiz_result() );
		$userQuizModel->shouldReceive( 'get_graduation' )->andReturn( 'passed' );
		$userQuizModel->shouldReceive( 'get_start_time' )->andReturn( $this->start_time( 0 ) );

		$ajax = $this->make_ajax(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
				'user_quiz'   => $userQuizModel,
			]
		);
		$ajax->shouldReceive( 'clear_quiz_cache' );

		$ajax->user_submit_quiz();

		$this->assertSame( 'success', $this->sent->status );
	}

	public function test_submit_quiz_time_spend_not_more_than_duration(): void {
		$this->set_data( $this->submit_data() );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		// Started 10 minutes ago, quiz duration 1 minute: user submitted late.
		$userQuizModel->shouldReceive( 'get_start_time' )->andReturn( $this->start_time( 600 ) );
		$userQuizModel->shouldReceive( 'handle_finish' )
			->once()
			->withArgs( static fn( array $data ) => 60 === $data['time_spend'] )
			->andReturn( $this->quiz_result() );
		$userQuizModel->shouldReceive( 'get_graduation' )->andReturn( 'passed' );

		$ajax = $this->make_ajax(
			[
				'course'      => $this->make_course( $this->make_quiz( true, false, '1 minute' ) ),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
				'user_quiz'   => $userQuizModel,
			]
		);
		$ajax->shouldReceive( 'clear_quiz_cache' );

		$ajax->user_submit_quiz();

		$this->assertSame( 'success', $this->sent->status );
	}

	public function test_submit_quiz_error_when_user_quiz_invalid(): void {
		$this->set_data( $this->submit_data() );

		$this->make_ajax(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
			]
		)->user_submit_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'User quiz is invalid!', $this->sent->message );
	}

	public function test_submit_quiz_error_when_model_can_not_finish(): void {
		$this->set_data( $this->submit_data() );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		$userQuizModel->shouldReceive( 'handle_finish' )->once()->andThrow( new Exception( 'You have finished the quiz.' ) );
		$userQuizModel->shouldReceive( 'get_start_time' )->andReturn( $this->start_time( 10 ) );

		$ajax = $this->make_ajax(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
				'user_quiz'   => $userQuizModel,
			]
		);
		$ajax->shouldReceive( 'clear_quiz_cache' )->never();

		$ajax->user_submit_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'You have finished the quiz.', $this->sent->message );
	}

	public function test_submit_quiz_guest_calculates_result_not_saved(): void {
		// Guest: nothing saved on server, time sent by browser is used.
		$this->set_data( array_merge( $this->submit_data(), [ 'time_spend' => '65' ] ) );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$userQuizModelFake = Mockery::mock( UserQuizModel::class );
		$userQuizModelFake->shouldReceive( 'calculate_quiz_result' )
			->once()
			->with(
				[
					101 => 'a',
					102 => [ 'x', 'y' ],
				]
			)
			->andReturn( $this->quiz_result( 0 ) );
		$userQuizModelFake->shouldNotReceive( 'handle_finish' );

		$ajax = $this->make_ajax(
			[
				'course'          => $this->make_course( null, true ),
				'guest_user_quiz' => $userQuizModelFake,
			]
		);
		$ajax->shouldReceive( 'find_user_quiz' )->never();
		$ajax->shouldReceive( 'clear_quiz_cache' )->never();
		$ajax->shouldReceive( 'html_btn_finish_course' )->never();

		$ajax->user_submit_quiz();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( 'failed', $this->sent->data->graduation );
		$this->assertSame( '', $this->sent->data->html_finish_course );
		$this->assertSame( '00:01:05', $this->template_args['html_completed'][2]['time_spend'] );
		$this->assertFalse( $this->template_args['html_completed'][3] );
	}

	public function test_submit_quiz_guest_time_spend_not_more_than_duration(): void {
		$data               = $this->submit_data();
		$data['time_spend'] = '99999';
		$this->set_data( $data );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$userQuizModelFake = Mockery::mock( UserQuizModel::class );
		$userQuizModelFake->shouldReceive( 'calculate_quiz_result' )->andReturn( $this->quiz_result() );

		$this->make_ajax(
			[
				'course'          => $this->make_course( $this->make_quiz( true, false, '1 minute' ), true ),
				'guest_user_quiz' => $userQuizModelFake,
			]
		)->user_submit_quiz();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( '00:01:00', $this->template_args['html_completed'][2]['time_spend'] );
	}

	public function test_submit_quiz_password_protected_even_editor(): void {
		$this->set_data( $this->submit_data() );
		Functions\when( 'post_password_required' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->make_ajax( [ 'course' => $this->make_course() ] )->user_submit_quiz();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'This content is password protected.', $this->sent->message );
	}

	// -------------------------------------------------------------------------
	// user_check_question
	// -------------------------------------------------------------------------

	private function check_data( int $question_id = 101 ): array {
		return array_merge(
			$this->valid_data(),
			[
				'question_id' => $question_id,
				'answered'    => 'a',
			]
		);
	}

	/**
	 * @return QuestionPostModel|Mockery\MockInterface
	 */
	private function make_question() {
		$question = Mockery::mock( QuestionPostModel::class );
		$question->shouldReceive( 'get_id' )->andReturn( 101 );
		$question->shouldReceive( 'get_mark' )->andReturn( 0 );

		return $question;
	}

	public function test_check_question_error_when_instant_check_off(): void {
		$this->set_data( $this->check_data() );

		$this->make_ajax(
			[
				'course'      => $this->make_course( $this->make_quiz( false ) ),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
			]
		)->user_check_question();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'Instant check is disable', $this->sent->message );
	}

	public function test_check_question_error_when_question_not_in_quiz(): void {
		$this->set_data( $this->check_data( 999 ) );

		$this->make_ajax(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
				'question'    => $this->make_question(),
			]
		)->user_check_question();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'The quiz question is invalid', $this->sent->message );
	}

	public function test_check_question_error_when_question_not_found(): void {
		$this->set_data( $this->check_data() );

		$this->make_ajax(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
			]
		)->user_check_question();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'The question is invalid', $this->sent->message );
	}

	public function test_check_question_success(): void {
		$this->set_data( $this->check_data() );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		$userQuizModel->shouldReceive( 'has_started' )->andReturn( true );
		$userQuizModel->shouldReceive( 'instant_check_question' )
			->once()
			->with( 101, 'a' )
			->andReturn(
				[
					'answered' => 'a',
					'mark'     => 2,
					'correct'  => true,
				]
			);

		$question = $this->make_question();
		$this->make_ajax(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
				'user_quiz'   => $userQuizModel,
				'question'    => $question,
			]
		)->user_check_question();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( '<div class="question"></div>', $this->sent->data->content );
		$this->assertTrue( $this->sent->data->correct );
		$this->assertSame( 2.0, $this->sent->data->mark );
		$this->assertSame( 1.0, $this->sent->data->point );
		$this->assertSame( 0.0, $this->sent->data->minus );
		$this->assertSame( $question, $this->template_args['html_check_quiz'][2] );
	}

	public function test_check_question_wrong_with_negative_marking_sends_minus(): void {
		$this->set_data( $this->check_data() );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$question = $this->make_question();
		$question->shouldReceive( 'check' )->andReturn(
			[
				'correct' => false,
				'mark'    => 0,
			]
		);

		$this->make_ajax(
			[
				'course'   => $this->make_course( $this->make_quiz( true, true ), true ),
				'question' => $question,
			]
		)->user_check_question();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( 1.0, $this->sent->data->minus );
	}

	public function test_check_question_error_when_quiz_not_doing(): void {
		$this->set_data( $this->check_data() );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		$userQuizModel->shouldReceive( 'has_started' )->andReturn( false );
		$userQuizModel->shouldReceive( 'instant_check_question' )->never();

		$this->make_ajax(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
				'user_quiz'   => $userQuizModel,
				'question'    => $this->make_question(),
			]
		)->user_check_question();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'User quiz is invalid!', $this->sent->message );
	}

	public function test_check_question_guest_uses_question_check(): void {
		$this->set_data( $this->check_data() );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$question = $this->make_question();
		$question->shouldReceive( 'check' )->once()->with( 'a' )->andReturn(
			[
				'correct' => false,
				'mark'    => 0,
			]
		);

		$ajax = $this->make_ajax(
			[
				'course'   => $this->make_course( null, true ),
				'question' => $question,
			]
		);
		$ajax->shouldReceive( 'find_user_quiz' )->never();

		$ajax->user_check_question();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertFalse( $this->sent->data->correct );
		$this->assertSame( 'a', $this->template_args['html_check_quiz'][3]['answered'] );
	}

	public function test_check_question_editor_can_pass_password(): void {
		$this->set_data( $this->check_data() );
		Functions\when( 'post_password_required' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );

		$userQuizModel = Mockery::mock( UserQuizModel::class );
		$userQuizModel->shouldReceive( 'has_started' )->andReturn( true );
		$userQuizModel->shouldReceive( 'instant_check_question' )->once()->andReturn(
			[
				'answered' => 'a',
				'mark'     => 0,
				'correct'  => false,
			]
		);

		$this->make_ajax(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => Mockery::mock( UserCourseModel::class ),
				'user_quiz'   => $userQuizModel,
				'question'    => $this->make_question(),
			]
		)->user_check_question();

		$this->assertSame( 'success', $this->sent->status );
	}
}
