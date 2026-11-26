<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Ajax\UserItem;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Ajax\UserItem\UserCourseAjax;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPress\Services\UserCourseService;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;

/**
 * Unit tests for UserCourseAjax::user_retake_course.
 *
 * Strategy: finders are redefined (Patchwork), no DB is needed. wp_send_json() is captured.
 */
class UserCourseAjaxTest extends BrainMonkeyTestCase {

	/**
	 * @var object|null response sent by wp_send_json()
	 */
	private $sent;

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
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'sanitize_text_field' )->alias( 'strip_tags' );
		Functions\when( 'sanitize_key' )->alias(
			function ( $key ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
			}
		);
		Functions\when( 'map_deep' )->alias(
			function ( $value, $callback ) {
				return is_array( $value ) ? array_map( fn( $v ) => is_array( $v ) ? array_map( $callback, $v ) : $callback( $v ), $value ) : $callback( $value );
			}
		);
	}

	/**
	 * Ajax with service replaced.
	 *
	 * @param UserCourseService|Mockery\MockInterface $service
	 *
	 * @return UserCourseAjax|Mockery\MockInterface
	 */
	private function make_ajax_with_service( $service ) {
		$ajax = Mockery::mock( UserCourseAjax::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$ajax->shouldReceive( 'get_user_course_service' )->andReturn( $service );

		return $ajax;
	}

	/**
	 * @param array|string $data
	 */
	private function set_data( $data ): void {
		\LP_Request::$params['data'] = is_array( $data ) ? json_encode( $data ) : $data;
	}

	/**
	 * @param string $method Class::method
	 * @param mixed $result
	 */
	private function redefine_finder( string $method, $result ): void {
		\Patchwork\redefine(
			$method,
			function () use ( $result ) {
				return $result;
			}
		);
	}

	/**
	 * @param array $finders [ course, user, user_course ]
	 */
	private function set_finders( array $finders ): void {
		$this->redefine_finder( CourseModel::class . '::find', $finders['course'] ?? false );
		$this->redefine_finder( UserModel::class . '::find', $finders['user'] ?? false );
		$this->redefine_finder( UserCourseModel::class . '::find', $finders['user_course'] ?? false );
	}

	private function make_course() {
		$course = Mockery::mock( CourseModel::class );
		$course->shouldReceive( 'get_id' )->andReturn( 30 );
		$course->shouldReceive( 'get_item_link' )->with( 55 )->andReturn( 'https://site.test/course/c/lessons/l' );
		$course->shouldReceive( 'get_permalink' )->andReturn( 'https://site.test/course/c' );

		return $course;
	}

	private function make_user() {
		$user = Mockery::mock( UserModel::class );
		$user->shouldReceive( 'get_id' )->andReturn( 7 );

		return $user;
	}

	public function test_user_retake_course_error_when_data_not_json(): void {
		$this->set_data( 'not json' );

		( new UserCourseAjax() )->user_retake_course();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'Params invalid!', $this->sent->message );
	}

	public function test_user_retake_course_error_when_course_id_missing(): void {
		$this->set_data( [ 'action' => 'user_retake_course' ] );

		( new UserCourseAjax() )->user_retake_course();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'Params invalid!', $this->sent->message );
	}

	public function test_user_retake_course_error_when_course_invalid(): void {
		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders( [] );

		( new UserCourseAjax() )->user_retake_course();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'The course is invalid', $this->sent->message );
	}

	public function test_user_retake_course_error_when_user_not_logged_in(): void {
		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders( [ 'course' => $this->make_course() ] );

		( new UserCourseAjax() )->user_retake_course();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'You have to log in to retake the course.', $this->sent->message );
	}

	public function test_user_retake_course_error_when_user_not_enrolled(): void {
		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders(
			[
				'course' => $this->make_course(),
				'user'   => $this->make_user(),
			]
		);

		( new UserCourseAjax() )->user_retake_course();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'User not enrolled course!', $this->sent->message );
	}

	public function test_user_retake_course_error_message_from_handle_retake(): void {
		$userCourse = Mockery::mock( UserCourseModel::class );
		$userCourse->shouldReceive( 'handle_retake' )->once()->andThrow( new Exception( 'You can not retake this course!' ) );
		$userCourse->shouldNotReceive( 'get_item_continue' );

		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => $userCourse,
			]
		);

		( new UserCourseAjax() )->user_retake_course();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'You can not retake this course!', $this->sent->message );
	}

	public function test_user_retake_course_php_error_is_logged_and_hidden(): void {
		$userCourse = Mockery::mock( UserCourseModel::class );
		$userCourse->shouldReceive( 'handle_retake' )->andThrow( new \Error( 'Fatal in /path/file.php' ) );

		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => $userCourse,
			]
		);

		( new UserCourseAjax() )->user_retake_course();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'Something went wrong, please try again!', $this->sent->message );
		$this->assertSame( [ 'Fatal in /path/file.php' ], \LP_Debug::$logged );
	}

	public function test_user_retake_course_retake_success_redirect_to_item_continue(): void {
		$userCourse = Mockery::mock( UserCourseModel::class );
		$userCourse->shouldReceive( 'handle_retake' )->once();
		$userCourse->shouldReceive( 'get_item_continue' )->andReturn( (object) [ 'ID' => 55 ] );

		$this->set_data( [ 'course_id' => '30' ] );
		$this->set_finders(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => $userCourse,
			]
		);

		( new UserCourseAjax() )->user_retake_course();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( 'Now you can begin this course', $this->sent->message );
		$this->assertSame( 'https://site.test/course/c/lessons/l', $this->sent->data->url_redirect );
	}

	public function test_user_retake_course_retake_success_redirect_to_course_when_no_item(): void {
		$userCourse = Mockery::mock( UserCourseModel::class );
		$userCourse->shouldReceive( 'handle_retake' )->once();
		$userCourse->shouldReceive( 'get_item_continue' )->andReturn( null );

		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders(
			[
				'course'      => $this->make_course(),
				'user'        => $this->make_user(),
				'user_course' => $userCourse,
			]
		);

		( new UserCourseAjax() )->user_retake_course();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( 'https://site.test/course/c', $this->sent->data->url_redirect );
	}

	public function test_create_action_nonce_uses_class_nonce_action(): void {
		Functions\expect( 'wp_create_nonce' )->once()->with( 'lp_user_course_nonce' )->andReturn( 'abc123' );

		$this->assertSame( 'abc123', UserCourseAjax::create_action_nonce() );
	}

	public function test_verify_action_nonce_fails_when_nonce_missing(): void {
		$this->set_data( [ 'course_id' => 30 ] );

		$this->assertFalse( UserCourseAjax::verify_action_nonce() );
	}

	public function test_verify_action_nonce_skipped_for_guest(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\expect( 'wp_verify_nonce' )->never();
		$this->set_data( [ 'course_id' => 30 ] );

		$this->assertTrue( UserCourseAjax::verify_action_nonce() );
	}

	public function test_verify_action_nonce_checks_nonce_sent_in_data(): void {
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'abc123', 'lp_user_course_nonce' )->andReturn( 1 );
		$this->set_data(
			[
				'course_id'       => 30,
				'lp_action_nonce' => 'abc123',
			]
		);

		$this->assertTrue( UserCourseAjax::verify_action_nonce() );
	}

	public function test_is_action_allowed_only_declared_actions(): void {
		$this->assertTrue( UserCourseAjax::is_action_allowed( 'user_enroll_course' ) );
		$this->assertTrue( UserCourseAjax::is_action_allowed( 'user_purchase_course' ) );
		$this->assertTrue( UserCourseAjax::is_action_allowed( 'user_retake_course' ) );
		$this->assertFalse( UserCourseAjax::is_action_allowed( 'get_course_model' ) );
		$this->assertFalse( UserCourseAjax::is_action_allowed( 'get_user_course_service' ) );
		$this->assertFalse( UserCourseAjax::is_action_allowed( 'get_params' ) );
		$this->assertFalse( UserCourseAjax::is_action_allowed( 'catch_lp_ajax' ) );
	}

	// -------------------------------------------------------------------------
	// user_enroll_course
	// -------------------------------------------------------------------------

	public function test_user_enroll_course_error_when_course_invalid(): void {
		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders( [] );
		$service = Mockery::mock( UserCourseService::class );
		$service->shouldNotReceive( 'enroll' );

		$this->make_ajax_with_service( $service )->user_enroll_course();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'The course is invalid', $this->sent->message );
	}

	public function test_user_enroll_course_success(): void {
		$course = $this->make_course();
		$user   = $this->make_user();
		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders(
			[
				'course' => $course,
				'user'   => $user,
			]
		);
		$service = Mockery::mock( UserCourseService::class );
		$service->shouldReceive( 'enroll' )->once()->with( $course, $user )->andReturn(
			[
				'message'  => 'Congrats!',
				'redirect' => 'https://site.test/course/c/lessons/l',
			]
		);

		$this->make_ajax_with_service( $service )->user_enroll_course();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( 'Congrats!', $this->sent->message );
		$this->assertSame( 'https://site.test/course/c/lessons/l', $this->sent->data->redirect );
	}

	public function test_user_enroll_course_guest_passes_false_user(): void {
		$course = $this->make_course();
		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders( [ 'course' => $course ] );
		$service = Mockery::mock( UserCourseService::class );
		$service->shouldReceive( 'enroll' )->once()->with( $course, false )->andReturn(
			[
				'message'  => 'Redirecting...',
				'redirect' => 'https://site.test/checkout/',
			]
		);

		$this->make_ajax_with_service( $service )->user_enroll_course();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( 'https://site.test/checkout/', $this->sent->data->redirect );
	}

	public function test_user_enroll_course_error_message_from_service(): void {
		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders( [ 'course' => $this->make_course() ] );
		$service = Mockery::mock( UserCourseService::class );
		$service->shouldReceive( 'enroll' )->andThrow( new Exception( 'Course is out of stock' ) );

		$this->make_ajax_with_service( $service )->user_enroll_course();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'Course is out of stock', $this->sent->message );
	}

	// -------------------------------------------------------------------------
	// user_purchase_course
	// -------------------------------------------------------------------------

	public function test_user_purchase_course_sends_sanitized_form_data(): void {
		$course = $this->make_course();
		$user   = $this->make_user();
		$this->set_data(
			[
				'course_id'       => 30,
				'repurchase_type' => 'Keep',
				'form_data'       => [
					'purchase-course' => '30',
					'quantity'        => '2',
					'note'            => '<b>hi</b>',
					'lp_action_nonce' => 'abc',
				],
			]
		);
		$this->set_finders(
			[
				'course' => $course,
				'user'   => $user,
			]
		);
		$service = Mockery::mock( UserCourseService::class );
		$service->shouldReceive( 'purchase' )->once()->with(
			$course,
			$user,
			[
				'purchase-course' => '30',
				'quantity'        => '2',
				'note'            => 'hi',
			],
			'keep'
		)->andReturn(
			[
				'message'  => 'Added to cart',
				'redirect' => 'https://site.test/checkout/',
			]
		);

		$this->make_ajax_with_service( $service )->user_purchase_course();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( 'Added to cart', $this->sent->message );
		$this->assertSame( 'https://site.test/checkout/', $this->sent->data->redirect );
	}

	public function test_user_purchase_course_form_data_not_array_is_empty(): void {
		$course = $this->make_course();
		$this->set_data(
			[
				'course_id'       => 30,
				'form_data'       => 'string',
				'repurchase_type' => [ 'x' ],
			]
		);
		$this->set_finders( [ 'course' => $course ] );
		$service = Mockery::mock( UserCourseService::class );
		$service->shouldReceive( 'purchase' )->once()->with( $course, false, [], '' )->andReturn(
			[
				'message'  => 'ok',
				'redirect' => 'https://site.test/checkout/',
			]
		);

		$this->make_ajax_with_service( $service )->user_purchase_course();

		$this->assertSame( 'success', $this->sent->status );
	}

	public function test_user_purchase_course_returns_repurchase_options(): void {
		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders(
			[
				'course' => $this->make_course(),
				'user'   => $this->make_user(),
			]
		);
		$service = Mockery::mock( UserCourseService::class );
		$service->shouldReceive( 'purchase' )->andReturn(
			[
				'type'  => 'allow_repurchase',
				'html'  => '<div class="lp_allow_repurchase_select"></div>',
				'title' => 'Repurchase Options',
			]
		);

		$this->make_ajax_with_service( $service )->user_purchase_course();

		$this->assertSame( 'success', $this->sent->status );
		$this->assertSame( 'allow_repurchase', $this->sent->data->type );
		$this->assertSame( '<div class="lp_allow_repurchase_select"></div>', $this->sent->data->html );
		$this->assertSame( 'Repurchase Options', $this->sent->data->title_popup );
		$this->assertFalse( isset( $this->sent->data->redirect ) );
	}

	public function test_user_purchase_course_error_message_from_service(): void {
		$this->set_data( [ 'course_id' => 30 ] );
		$this->set_finders( [ 'course' => $this->make_course() ] );
		$service = Mockery::mock( UserCourseService::class );
		$service->shouldReceive( 'purchase' )->andThrow( new Exception( 'Error: Invalid repurchase option.' ) );

		$this->make_ajax_with_service( $service )->user_purchase_course();

		$this->assertSame( 'error', $this->sent->status );
		$this->assertSame( 'Error: Invalid repurchase option.', $this->sent->message );
	}
}
