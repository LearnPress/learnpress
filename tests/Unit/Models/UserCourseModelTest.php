<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;
use stdClass;
use WP_Error;

class UserCourseModelTest extends BrainMonkeyTestCase {

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a UserCourseModel with properties pre-populated (no DB).
	 */
	private function make_user_course( array $props = [], array $meta = [] ): UserCourseModel {
		$defaults = [
			'user_item_id' => 1,
			'user_id'      => 10,
			'item_id'      => 100,
			'item_type'    => LP_COURSE_CPT,
			'ref_type'     => LP_ORDER_CPT,
			'status'       => UserItemModel::STATUS_ENROLLED,
			'graduation'   => UserItemModel::GRADUATION_IN_PROGRESS,
			'start_time'   => '2025-01-01 00:00:00',
			'end_time'     => null,
			'parent_id'    => 0,
			'ref_id'       => 50,
		];

		$data = array_merge( $defaults, $props );

		// Prevent constructor from calling get_course_model and get_user_model.
		$model = Mockery::mock( UserCourseModel::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$model->shouldReceive( 'get_course_model' )->andReturn( false )->byDefault();
		$model->shouldReceive( 'get_user_model' )->andReturn( false )->byDefault();

		// Map data to object properties.
		foreach ( $data as $key => $value ) {
			if ( property_exists( $model, $key ) ) {
				$model->{$key} = $value;
			}
		}

		$model->meta_data = (object) $meta;

		return $model;
	}

	/**
	 * Create a mock CourseModel with configurable methods.
	 */
	private function make_course_mock( array $methods = [] ): CourseModel {
		$course = Mockery::mock( CourseModel::class )->makePartial();

		foreach ( $methods as $method => $return ) {
			$course->shouldReceive( $method )->andReturn( $return )->byDefault();
		}

		return $course;
	}

	// -------------------------------------------------------------------------
	// 1. Constructor & defaults
	// -------------------------------------------------------------------------

	public function test_item_type_defaults_to_course_cpt(): void {
		$model = new UserCourseModel();

		$this->assertSame( LP_COURSE_CPT, $model->item_type );
	}

	public function test_ref_type_defaults_to_order_cpt(): void {
		$model = new UserCourseModel();

		$this->assertSame( LP_ORDER_CPT, $model->ref_type );
	}

	// -------------------------------------------------------------------------
	// 2. Constants
	// -------------------------------------------------------------------------

	public function test_status_purchased_constant(): void {
		$this->assertSame( 'purchased', UserCourseModel::STATUS_PURCHASED );
	}

	public function test_meta_key_retaken_count_constant(): void {
		$this->assertSame( '_lp_retaken_count', UserCourseModel::META_KEY_RETAKEN_COUNT );
	}

	// -------------------------------------------------------------------------
	// 3. Status check methods
	// -------------------------------------------------------------------------

	public function test_has_enrolled_returns_true_when_status_is_enrolled(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_ENROLLED ] );

		$this->assertTrue( $model->has_enrolled() );
	}

	public function test_has_enrolled_returns_false_when_status_is_not_enrolled(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_FINISHED ] );

		$this->assertFalse( $model->has_enrolled() );
	}

	public function test_has_purchased_returns_true_when_status_is_purchased(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_PURCHASED ] );

		$this->assertTrue( $model->has_purchased() );
	}

	public function test_has_purchased_returns_false_when_status_is_enrolled(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_ENROLLED ] );

		$this->assertFalse( $model->has_purchased() );
	}

	public function test_has_finished_returns_true_when_status_is_finished(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_FINISHED ] );

		$this->assertTrue( $model->has_finished() );
	}

	public function test_has_finished_returns_false_when_status_is_enrolled(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_ENROLLED ] );

		$this->assertFalse( $model->has_finished() );
	}

	public function test_is_finished_is_alias_of_has_finished(): void {
		$finished = $this->make_user_course( [ 'status' => UserItemModel::STATUS_FINISHED ] );
		$enrolled = $this->make_user_course( [ 'status' => UserItemModel::STATUS_ENROLLED ] );

		$this->assertTrue( $finished->is_finished() );
		$this->assertFalse( $enrolled->is_finished() );
	}

	public function test_has_canceled_returns_true_when_status_is_cancel(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_CANCEL ] );

		$this->assertTrue( $model->has_canceled() );
	}

	public function test_has_canceled_returns_false_when_status_is_enrolled(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_ENROLLED ] );

		$this->assertFalse( $model->has_canceled() );
	}

	// -------------------------------------------------------------------------
	// 4. has_enrolled_or_finished
	// -------------------------------------------------------------------------

	public function test_has_enrolled_or_finished_true_when_enrolled(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_ENROLLED ] );

		$this->assertTrue( $model->has_enrolled_or_finished() );
	}

	public function test_has_enrolled_or_finished_true_when_finished(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_FINISHED ] );

		$this->assertTrue( $model->has_enrolled_or_finished() );
	}

	public function test_has_enrolled_or_finished_false_when_purchased(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_PURCHASED ] );

		$this->assertFalse( $model->has_enrolled_or_finished() );
	}

	public function test_has_enrolled_or_finished_false_when_canceled(): void {
		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_CANCEL ] );

		$this->assertFalse( $model->has_enrolled_or_finished() );
	}

	// -------------------------------------------------------------------------
	// 5. Graduation checks
	// -------------------------------------------------------------------------

	public function test_get_graduation_returns_graduation_value(): void {
		$model = $this->make_user_course( [ 'graduation' => UserItemModel::GRADUATION_PASSED ] );

		$this->assertSame( 'passed', $model->get_graduation() );
	}

	public function test_is_passed_returns_true_when_graduation_is_passed(): void {
		$model = $this->make_user_course( [ 'graduation' => UserItemModel::GRADUATION_PASSED ] );

		$this->assertTrue( $model->is_passed() );
	}

	public function test_is_passed_returns_false_when_graduation_is_failed(): void {
		$model = $this->make_user_course( [ 'graduation' => UserItemModel::GRADUATION_FAILED ] );

		$this->assertFalse( $model->is_passed() );
	}

	public function test_is_passed_returns_false_when_graduation_is_in_progress(): void {
		$model = $this->make_user_course( [ 'graduation' => UserItemModel::GRADUATION_IN_PROGRESS ] );

		$this->assertFalse( $model->is_passed() );
	}

	// -------------------------------------------------------------------------
	// 6. get_retaken_count
	// -------------------------------------------------------------------------

	public function test_get_retaken_count_returns_value_from_meta(): void {
		$model = $this->make_user_course();
		$model->shouldReceive( 'get_meta_value_from_key' )
			->with( UserCourseModel::META_KEY_RETAKEN_COUNT, 0 )
			->andReturn( 3 );

		$this->assertSame( 3, $model->get_retaken_count() );
	}

	public function test_get_retaken_count_returns_zero_when_no_meta(): void {
		$model = $this->make_user_course();
		$model->shouldReceive( 'get_meta_value_from_key' )
			->with( UserCourseModel::META_KEY_RETAKEN_COUNT, 0 )
			->andReturn( 0 );

		$this->assertSame( 0, $model->get_retaken_count() );
	}

	// -------------------------------------------------------------------------
	// 7. can_retake
	// -------------------------------------------------------------------------

	public function test_can_retake_returns_zero_when_course_not_found(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$model = $this->make_user_course();
		$model->shouldReceive( 'get_course_model' )->andReturn( false );

		$this->assertSame( 0, $model->can_retake() );
	}

	public function test_can_retake_returns_zero_when_retake_option_is_zero(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$course = $this->make_course_mock( [
			'get_meta_value_by_key' => 0,
		] );

		$model = $this->make_user_course();
		$model->shouldReceive( 'get_course_model' )->andReturn( $course );

		$this->assertSame( 0, $model->can_retake() );
	}

	public function test_can_retake_returns_remaining_times_when_finished(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$course = $this->make_course_mock();
		$course->shouldReceive( 'get_meta_value_by_key' )
			->with( CoursePostModel::META_KEY_RETAKE_COUNT, 0 )
			->andReturn( 3 );

		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_FINISHED ] );
		$model->shouldReceive( 'get_course_model' )->andReturn( $course );
		$model->shouldReceive( 'get_retaken_count' )->andReturn( 1 );

		$this->assertSame( 2, $model->can_retake() );
	}

	public function test_can_retake_returns_zero_when_all_retakes_used(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$course = $this->make_course_mock();
		$course->shouldReceive( 'get_meta_value_by_key' )
			->with( CoursePostModel::META_KEY_RETAKE_COUNT, 0 )
			->andReturn( 2 );

		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_FINISHED ] );
		$model->shouldReceive( 'get_course_model' )->andReturn( $course );
		$model->shouldReceive( 'get_retaken_count' )->andReturn( 2 );

		$this->assertSame( 0, $model->can_retake() );
	}

	// -------------------------------------------------------------------------
	// 8. evaluate_course_by_lesson (protected, test via calculate_course_results)
	// -------------------------------------------------------------------------

	public function test_evaluate_course_by_lesson_zero_when_no_completed(): void {
		$model = $this->make_user_course();

		$course = $this->make_course_mock( [
			'get_passing_condition' => 80,
		] );
		$model->shouldReceive( 'get_course_model' )->andReturn( $course );

		$count_completed = new stdClass();
		$count_completed->{LP_LESSON_CPT . '_status_completed'} = 0;

		$result = $this->callProtected( $model, 'evaluate_course_by_lesson', [ $count_completed, 10 ] );

		$this->assertSame( 0, $result['result'] );
		$this->assertSame( 0, $result['pass'] );
	}

	public function test_evaluate_course_by_lesson_calculates_percentage(): void {
		$model = $this->make_user_course();

		$course = $this->make_course_mock( [
			'get_passing_condition' => 80,
		] );
		$model->shouldReceive( 'get_course_model' )->andReturn( $course );

		$count_completed = new stdClass();
		$count_completed->{LP_LESSON_CPT . '_status_completed'} = 9;

		$result = $this->callProtected( $model, 'evaluate_course_by_lesson', [ $count_completed, 10 ] );

		$this->assertEquals( 90, $result['result'] );
		$this->assertSame( 1, $result['pass'] );
	}

	public function test_evaluate_course_by_lesson_fails_when_below_passing(): void {
		$model = $this->make_user_course();

		$course = $this->make_course_mock( [
			'get_passing_condition' => 80,
		] );
		$model->shouldReceive( 'get_course_model' )->andReturn( $course );

		$count_completed = new stdClass();
		$count_completed->{LP_LESSON_CPT . '_status_completed'} = 5;

		$result = $this->callProtected( $model, 'evaluate_course_by_lesson', [ $count_completed, 10 ] );

		$this->assertEquals( 50, $result['result'] );
		$this->assertSame( 0, $result['pass'] );
	}

	// -------------------------------------------------------------------------
	// 9. evaluate_course_by_quizzes_passed (protected)
	// -------------------------------------------------------------------------

	public function test_evaluate_course_by_quizzes_passed_zero_when_no_quizzes(): void {
		$model = $this->make_user_course();

		$count_completed = new stdClass();
		$count_completed->{LP_QUIZ_CPT . '_graduation_passed'} = 0;

		$result = $this->callProtected( $model, 'evaluate_course_by_quizzes_passed', [ $count_completed, 0 ] );

		$this->assertSame( 0, $result['result'] );
		$this->assertSame( 0, $result['pass'] );
	}

	public function test_evaluate_course_by_quizzes_passed_calculates_percentage(): void {
		$model = $this->make_user_course();

		$course = $this->make_course_mock( [
			'get_passing_condition' => 50,
		] );
		$model->shouldReceive( 'get_course_model' )->andReturn( $course );

		$count_completed = new stdClass();
		$count_completed->{LP_QUIZ_CPT . '_graduation_passed'} = 3;

		$result = $this->callProtected( $model, 'evaluate_course_by_quizzes_passed', [ $count_completed, 4 ] );

		$this->assertEquals( 75, $result['result'] );
		$this->assertSame( 1, $result['pass'] );
	}

	// -------------------------------------------------------------------------
	// 10. can_impact_item
	// -------------------------------------------------------------------------

	public function test_can_impact_item_returns_error_when_no_user(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$model = $this->make_user_course();
		$model->shouldReceive( 'get_user_model' )->andReturn( false );
		$model->shouldReceive( 'get_time_remaining' )->andReturn( -1 );

		$result = $model->can_impact_item();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'user_not_exists', $result->code );
	}

	public function test_can_impact_item_returns_error_when_canceled(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$user = Mockery::mock( \LearnPress\Models\UserModel::class );

		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_CANCEL ] );
		$model->shouldReceive( 'get_user_model' )->andReturn( $user );
		$model->shouldReceive( 'get_time_remaining' )->andReturn( -1 );

		$result = $model->can_impact_item();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'user_not_enroll_course', $result->code );
	}

	public function test_can_impact_item_returns_error_when_finished(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$user = Mockery::mock( \LearnPress\Models\UserModel::class );

		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_FINISHED ] );
		$model->shouldReceive( 'get_user_model' )->andReturn( $user );
		$model->shouldReceive( 'get_time_remaining' )->andReturn( -1 );

		$result = $model->can_impact_item();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'user_finished_course', $result->code );
	}

	public function test_can_impact_item_returns_error_when_expired(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$user = Mockery::mock( \LearnPress\Models\UserModel::class );

		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_ENROLLED ] );
		$model->shouldReceive( 'get_user_model' )->andReturn( $user );
		$model->shouldReceive( 'get_time_remaining' )->andReturn( 0 );

		$result = $model->can_impact_item();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'course_is_blocked', $result->code );
	}

	public function test_can_impact_item_returns_true_when_enrolled_and_valid(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$user = Mockery::mock( \LearnPress\Models\UserModel::class );

		$model = $this->make_user_course( [ 'status' => UserItemModel::STATUS_ENROLLED ] );
		$model->shouldReceive( 'get_user_model' )->andReturn( $user );
		$model->shouldReceive( 'get_time_remaining' )->andReturn( -1 );

		$result = $model->can_impact_item();

		$this->assertTrue( $result );
	}

	// -------------------------------------------------------------------------
	// 11. can_finish
	// -------------------------------------------------------------------------

	public function test_can_finish_returns_error_when_course_not_found(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$model = $this->make_user_course();
		$model->shouldReceive( 'get_course_model' )->andReturn( false );
		$model->shouldReceive( 'can_impact_item' )->andReturn( true );

		$result = $model->can_finish();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'lp_user_course_can_finish_err', $result->code );
	}

	public function test_can_finish_returns_error_when_can_impact_item_fails(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$course = $this->make_course_mock();

		$model = $this->make_user_course();
		$model->shouldReceive( 'get_course_model' )->andReturn( $course );
		$model->shouldReceive( 'can_impact_item' )->andReturn(
			new WP_Error( 'user_not_enroll_course', 'Not enrolled' )
		);

		Functions\when( 'is_wp_error' )->alias( function ( $thing ) {
			return $thing instanceof WP_Error;
		} );

		$result = $model->can_finish();

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_can_finish_returns_true_when_passed(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$course = $this->make_course_mock();

		$model = $this->make_user_course();
		$model->shouldReceive( 'get_course_model' )->andReturn( $course );
		$model->shouldReceive( 'can_impact_item' )->andReturn( true );
		$model->shouldReceive( 'calculate_course_results' )->andReturn( [
			'pass'            => 1,
			'result'          => 90,
			'count_items'     => 10,
			'completed_items' => 10,
		] );

		$result = $model->can_finish();

		$this->assertTrue( $result );
	}

	// -------------------------------------------------------------------------
	// 12. get_time_remaining
	// -------------------------------------------------------------------------

	public function test_get_time_remaining_returns_minus_one_when_no_user(): void {
		$model = $this->make_user_course();
		$model->shouldReceive( 'get_user_model' )->andReturn( false );
		$model->shouldReceive( 'get_course_model' )->andReturn( $this->make_course_mock() );

		// Call the real method
		$uc     = new UserCourseModel();
		$result = $this->callGetTimeRemaining( $model, false, true );

		$this->assertSame( -1, $result );
	}

	public function test_get_time_remaining_returns_minus_one_when_no_course(): void {
		$user  = Mockery::mock( \LearnPress\Models\UserModel::class );
		$model = $this->make_user_course();
		$model->shouldReceive( 'get_user_model' )->andReturn( $user );
		$model->shouldReceive( 'get_course_model' )->andReturn( false );

		$result = $this->callGetTimeRemaining( $model, true, false );

		$this->assertSame( -1, $result );
	}

	public function test_get_time_remaining_returns_minus_one_when_duration_is_zero(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$user   = Mockery::mock( \LearnPress\Models\UserModel::class );
		$author = Mockery::mock( \LearnPress\Models\UserModel::class );

		$course = $this->make_course_mock( [
			'get_duration'             => '0',
			'get_author_model'         => $author,
			'enable_block_when_expire' => true,
		] );

		$model = $this->make_user_course();
		$model->shouldReceive( 'get_user_model' )->andReturn( $user );
		$model->shouldReceive( 'get_course_model' )->andReturn( $course );

		$result = $model->get_time_remaining();

		$this->assertSame( -1, $result );
	}

	public function test_get_time_remaining_returns_minus_one_when_block_expire_disabled(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$user   = Mockery::mock( \LearnPress\Models\UserModel::class );
		$author = Mockery::mock( \LearnPress\Models\UserModel::class );

		$course = $this->make_course_mock( [
			'get_duration'             => '30 day',
			'get_author_model'         => $author,
			'enable_block_when_expire' => false,
		] );

		$model = $this->make_user_course();
		$model->shouldReceive( 'get_user_model' )->andReturn( $user );
		$model->shouldReceive( 'get_course_model' )->andReturn( $course );

		$result = $model->get_time_remaining();

		$this->assertSame( -1, $result );
	}

	// -------------------------------------------------------------------------
	// Utility
	// -------------------------------------------------------------------------

	/**
	 * Call a protected/private method via reflection.
	 */
	private function callProtected( $object, string $method, array $args = [] ) {
		$ref = new \ReflectionMethod( get_class( $object ), $method );
		$ref->setAccessible( true );

		return $ref->invoke( $object, ...$args );
	}

	/**
	 * Helper to test get_time_remaining with controlled user/course availability.
	 */
	private function callGetTimeRemaining( $model, bool $hasUser, bool $hasCourse ): int {
		return $model->get_time_remaining();
	}
}
