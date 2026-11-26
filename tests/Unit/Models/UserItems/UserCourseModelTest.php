<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models\UserItems;

use Brain\Monkey\Functions;
use LearnPress\Databases\UserItemResultsDB;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItemResults\UserItemResultModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;
use ReflectionProperty;
use stdClass;

/**
 * Unit tests for UserCourseModel::calculate_course_results().
 *
 * Strategy: the result row of learnpress_user_item_results is served from wp_cache_get
 * (or a mocked UserItemResultsDB when missing), course and progress are partial mocks.
 */
class UserCourseModelTest extends BrainMonkeyTestCase {

	/**
	 * Unique user id per test, avoid static cache_load_first collisions.
	 *
	 * @var int
	 */
	private static $user_id = 1000;

	protected function setUp(): void {
		parent::setUp();

		defined( 'LP_COURSE_ENROLLED' ) || define( 'LP_COURSE_ENROLLED', 'enrolled' );
		defined( 'LP_COURSE_FINISHED' ) || define( 'LP_COURSE_FINISHED', 'finished' );

		// UserItemResultModel::get_result() decodes via LP_Helper::json_decode().
		if ( ! class_exists( 'LP_Helper' ) ) {
			require_once LP_ABSPATH . 'inc/class-lp-helper.php';
		}

		Functions\when( 'wp_cache_set' )->justReturn( true );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
	}

	protected function tearDown(): void {
		( new ReflectionProperty( UserItemResultsDB::class, '_instance' ) )->setValue( null, null );

		parent::tearDown();
	}

	/**
	 * Serve the result row from cache, or null when the row does not exist on DB.
	 *
	 * @param UserItemResultModel|null $userItemResult
	 */
	private function stub_result_row( $userItemResult ): void {
		Functions\when( 'wp_cache_get' )->alias(
			function ( $key ) use ( $userItemResult ) {
				if ( 0 === strpos( $key, 'userModel/' ) ) {
					return Mockery::mock( UserModel::class );
				}

				if ( 0 === strpos( $key, 'userItemResultModel/' ) && $userItemResult ) {
					return $userItemResult;
				}

				return false;
			}
		);

		if ( ! $userItemResult ) {
			$wpdb = Mockery::mock();
			$wpdb->shouldReceive( 'get_row' )->andReturn( null );

			$db = Mockery::mock( UserItemResultsDB::class );
			$db->shouldReceive( 'get_query_single_row' );
			$db->shouldReceive( 'get_user_item_results' )->andReturn( 'SQL' );
			$db->wpdb = $wpdb;
			( new ReflectionProperty( UserItemResultsDB::class, '_instance' ) )->setValue( null, $db );
		}
	}

	/**
	 * Partial mock UserCourseModel: course has 2 lessons, user completed $lessons_completed.
	 *
	 * @return UserCourseModel|Mockery\MockInterface
	 */
	private function make_user_course( string $status, int $lessons_completed = 1 ) {
		$courseModel = Mockery::mock( CourseModel::class );
		$courseModel->shouldReceive( 'get_id' )->andReturn( 50 );
		$courseModel->shouldReceive( 'get_meta_value_by_key' )->andReturn( 'evaluate_lesson' );
		$courseModel->shouldReceive( 'get_passing_condition' )->andReturn( 80.0 );
		$courseModel->shouldReceive( 'count_items' )->andReturnUsing(
			function ( $type = '' ) {
				return LP_LESSON_CPT === $type || '' === $type ? 2 : 0;
			}
		);

		$count_completed                                        = new stdClass();
		$count_completed->count_status                          = $lessons_completed;
		$count_completed->{LP_LESSON_CPT . '_status_completed'} = $lessons_completed;

		$userCourseModel = Mockery::mock( UserCourseModel::class )->makePartial();
		$userCourseModel->shouldReceive( 'get_course_model' )->andReturn( $courseModel );
		$userCourseModel->shouldReceive( 'count_items_completed' )->andReturn( $count_completed );
		$userCourseModel->user_id = ++self::$user_id;
		$userCourseModel->item_id = 50;
		$userCourseModel->status  = $status;
		( new ReflectionProperty( UserCourseModel::class, 'user_item_id' ) )->setValue( $userCourseModel, 325 );

		return $userCourseModel;
	}

	public function test_calculate_course_results_finished_without_result_row_calculates_progress(): void {
		$this->stub_result_row( null );

		$results = $this->make_user_course( LP_COURSE_FINISHED )->calculate_course_results( true );

		$this->assertEquals( 50, $results['result'] );
		$this->assertSame( 0, $results['pass'] );
		$this->assertSame( 2, $results['count_items'] );
		$this->assertSame( 1, $results['completed_items'] );
	}

	public function test_calculate_course_results_finished_with_empty_result_calculates_progress(): void {
		$userItemResult = new UserItemResultModel();
		$this->stub_result_row( $userItemResult );

		$results = $this->make_user_course( LP_COURSE_FINISHED, 2 )->calculate_course_results( true );

		$this->assertEquals( 100, $results['result'] );
		$this->assertSame( 1, $results['pass'] );
	}

	public function test_calculate_course_results_finished_returns_saved_snapshot(): void {
		$userItemResult = new UserItemResultModel();
		$userItemResult->set_result(
			[
				'result' => 75,
				'pass'   => 1,
			]
		);
		$this->stub_result_row( $userItemResult );

		$userCourseModel = $this->make_user_course( LP_COURSE_FINISHED );
		$userCourseModel->shouldNotReceive( 'count_items_completed' );

		$results = $userCourseModel->calculate_course_results( true );

		$this->assertSame( 75, $results['result'] );
		$this->assertSame( 1, $results['pass'] );
	}

	public function test_calculate_course_results_not_enrolled_returns_empty_result(): void {
		$this->stub_result_row( null );

		$results = $this->make_user_course( 'purchased' )->calculate_course_results( true );

		$this->assertSame( 0, $results['result'] );
		$this->assertSame( 0, $results['count_items'] );
	}

	public function test_get_retaken_count_reads_extra_data_first(): void {
		$userCourseModel = Mockery::mock( UserCourseModel::class )->makePartial();
		$userCourseModel->set_extra_data( [ UserCourseModel::META_KEY_RETAKEN_COUNT => 3 ] );
		$userCourseModel->shouldNotReceive( 'get_meta_value_from_key' );

		$this->assertSame( 3, $userCourseModel->get_retaken_count() );
	}

	public function test_get_retaken_count_extra_data_zero_wins_over_meta(): void {
		$userCourseModel = Mockery::mock( UserCourseModel::class )->makePartial();
		$userCourseModel->set_extra_data( [ UserCourseModel::META_KEY_RETAKEN_COUNT => 0 ] );
		$userCourseModel->shouldNotReceive( 'get_meta_value_from_key' );

		$this->assertSame( 0, $userCourseModel->get_retaken_count() );
	}

	public function test_get_retaken_count_falls_back_to_meta_when_key_missing_in_extra_data(): void {
		$userCourseModel = Mockery::mock( UserCourseModel::class )->makePartial();
		$userCourseModel->set_extra_data( [ 'other_key' => 'x' ] );
		$userCourseModel->shouldReceive( 'get_meta_value_from_key' )
			->with( UserCourseModel::META_KEY_RETAKEN_COUNT, 0 )
			->andReturn( '2' );

		$this->assertSame( 2, $userCourseModel->get_retaken_count() );
	}

	public function test_get_retaken_count_falls_back_to_meta_when_extra_data_null(): void {
		$userCourseModel             = Mockery::mock( UserCourseModel::class )->makePartial();
		$userCourseModel->extra_data = null;
		$userCourseModel->shouldReceive( 'get_meta_value_from_key' )
			->with( UserCourseModel::META_KEY_RETAKEN_COUNT, 0 )
			->andReturn( 0 );

		$this->assertSame( 0, $userCourseModel->get_retaken_count() );
	}

	public function test_set_retaken_count_stores_meta_and_extra_data_keeping_other_keys(): void {
		$userCourseModel = Mockery::mock( UserCourseModel::class )->makePartial();
		$userCourseModel->set_extra_data( [ 'other_key' => 'x' ] );
		$userCourseModel->shouldReceive( 'set_meta_value_for_key' )
			->once()
			->with( UserCourseModel::META_KEY_RETAKEN_COUNT, 4 );

		$userCourseModel->set_retaken_count( 4 );

		$this->assertSame(
			[
				'other_key'                            => 'x',
				UserCourseModel::META_KEY_RETAKEN_COUNT => 4,
			],
			$userCourseModel->get_extra_data()
		);
	}
}
