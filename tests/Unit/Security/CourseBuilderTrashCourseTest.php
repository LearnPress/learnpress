<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Security;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Regression test: Course Builder AJAX must not let a guest (user 0, public wp_rest nonce)
 * trash/draft/delete arbitrary posts, nor duplicate non-existing items.
 */
class CourseBuilderTrashCourseTest extends BrainMonkeyTestCase {

	public static array $sent = [];
	public static array $parent_calls = [];

	private function load_ajax_with_stubs(): void {
		self::$sent         = [];
		self::$parent_calls = [];

		if ( ! defined( 'LP_COURSE_CPT' ) ) {
			define( 'LP_COURSE_CPT', 'lp_course' );
		}

		eval(
			'namespace LearnPress\\Ajax {
				abstract class AbstractAjax {
					public static function catch_lp_ajax() {
						\\LearnPress\\Tests\\Unit\\Security\\CourseBuilderTrashCourseTest::$parent_calls[] = "parent";
					}
				}
			}
			namespace LearnPress\\Models {
				class CourseModel {
					public static $found = null;
					public $post_author = 0;
					public $post_name = "";
					public static function find( $id, $cache = false ) {
						return self::$found;
					}
				}
			}
			namespace {
				class LP_Request {
					public static function get_param( $key ) {
						return $_REQUEST[ $key ] ?? null;
					}
				}
				class LP_Helper {
					public static function json_decode( $json, $assoc = false ) {
						return json_decode( $json, $assoc );
					}
				}
				class LP_REST_Response {
					public $status = "error";
					public $message = "";
					public $data = null;
				}
			}'
		);

		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'absint' )->alias( static fn( $v ) => abs( (int) $v ) );
		Functions\when( 'sanitize_key' )->alias( static fn( $v ) => strtolower( (string) $v ) );
		Functions\when( 'wp_send_json' )->alias(
			static function ( $response ) {
				CourseBuilderTrashCourseTest::$sent[] = $response;
			}
		);

		require_once dirname( __DIR__, 3 ) . '/inc/Ajax/CourseBuilder/CourseBuilderAjax.php';
	}

	private function request( array $data ): void {
		$_REQUEST['data'] = json_encode( $data );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_guest_cannot_trash_non_course_post(): void {
		$this->load_ajax_with_stubs();

		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'wp_trash_post' )->never();
		Functions\expect( 'wp_update_post' )->never();
		Functions\expect( 'wp_delete_post' )->never();

		// Target is a page: CourseModel::find() returns nothing.
		$this->request( [ 'course_id' => 123, 'status' => 'trash' ] );
		( new \LearnPress\Ajax\CourseBuilder\CourseBuilderAjax() )->move_trash_course();

		$this->assertCount( 1, self::$sent );
		$this->assertSame( 'error', self::$sent[0]->status );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_logged_in_user_cannot_draft_or_trash_non_course_post(): void {
		$this->load_ajax_with_stubs();

		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 5 );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\expect( 'wp_trash_post' )->never();
		Functions\expect( 'wp_update_post' )->never();

		foreach ( [ 'draft', 'trash', 'delete' ] as $status ) {
			self::$sent = [];
			$this->request( [ 'course_id' => 123, 'status' => $status ] );
			( new \LearnPress\Ajax\CourseBuilder\CourseBuilderAjax() )->move_trash_course();
			$this->assertSame( 'error', self::$sent[0]->status, $status );
		}
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_user_without_edit_capability_cannot_trash_course(): void {
		$this->load_ajax_with_stubs();

		$course              = new \LearnPress\Models\CourseModel();
		$course->post_author = 5;
		\LearnPress\Models\CourseModel::$found = $course;

		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 5 );
		Functions\when( 'get_post_type' )->justReturn( 'lp_course' );
		Functions\when( 'get_post_meta' )->justReturn( [] );
		Functions\when( 'current_user_can' )->alias(
			static fn( $cap ) => 'edit_post' !== $cap && 'manage_options' !== $cap
		);
		Functions\expect( 'wp_trash_post' )->never();

		$this->request( [ 'course_id' => 10, 'status' => 'trash' ] );
		( new \LearnPress\Ajax\CourseBuilder\CourseBuilderAjax() )->move_trash_course();

		$this->assertSame( 'error', self::$sent[0]->status );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_author_can_trash_own_course(): void {
		$this->load_ajax_with_stubs();

		$course              = new \LearnPress\Models\CourseModel();
		$course->post_author = 5;
		$course->post_name   = 'my-course';
		\LearnPress\Models\CourseModel::$found = $course;

		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 5 );
		Functions\when( 'get_post_type' )->justReturn( 'lp_course' );
		Functions\when( 'get_post_meta' )->justReturn( [] );
		Functions\when( 'current_user_can' )->alias( static fn( $cap ) => 'edit_post' === $cap );
		Functions\expect( 'wp_trash_post' )->once()->with( 10 )->andReturn( true );
		Functions\when( 'wp_update_post' )->justReturn( 10 );

		$this->request( [ 'course_id' => 10, 'status' => 'trash' ] );
		( new \LearnPress\Ajax\CourseBuilder\CourseBuilderAjax() )->move_trash_course();

		$this->assertSame( 'success', self::$sent[0]->status );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_duplicate_course_rejects_missing_course(): void {
		$this->load_ajax_with_stubs();

		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'current_user_can' )->justReturn( false );

		$this->request( [ 'course_id' => 123 ] );
		( new \LearnPress\Ajax\CourseBuilder\CourseBuilderAjax() )->duplicate_course();

		$this->assertSame( 'error', self::$sent[0]->status );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_catch_lp_ajax_blocks_guest_and_allows_logged_in_user(): void {
		$this->load_ajax_with_stubs();

		$_REQUEST['lp-load-ajax'] = 'move_trash_course';

		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\expect( 'wp_die' )->once()->with( 'Invalid request!', 403 );
		\LearnPress\Ajax\CourseBuilder\CourseBuilderAjax::catch_lp_ajax();
		$this->assertSame( [], self::$parent_calls );

		Functions\when( 'is_user_logged_in' )->justReturn( true );
		\LearnPress\Ajax\CourseBuilder\CourseBuilderAjax::catch_lp_ajax();
		$this->assertSame( [ 'parent' ], self::$parent_calls );
	}
}
