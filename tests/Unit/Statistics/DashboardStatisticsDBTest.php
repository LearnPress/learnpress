<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Statistics;

use LearnPress\Statistics\DashboardStatisticsDB;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

// Parent class of DashboardStatisticsDB (legacy, not PSR-4 autoloadable).
require_once dirname( __DIR__, 3 ) . '/inc/Databases/class-lp-db.php';

/**
 * Pure-math helpers of DashboardStatisticsDB (query methods are covered by
 * the wp eval runtime verification against the real DB).
 *
 * @covers \LearnPress\Statistics\DashboardStatisticsDB
 */
class DashboardStatisticsDBTest extends BrainMonkeyTestCase {

	private function row( array $data ): object {
		return (object) $data;
	}

	/*
	|--------------------------------------------------------------------------
	| completion_from_rows()
	|--------------------------------------------------------------------------
	*/

	public function test_completion_aggregates_and_rate(): void {
		$rows = [
			$this->row( [ 'enrolled' => 10, 'completed' => 8 ] ), // 80% — above target
			$this->row( [ 'enrolled' => 10, 'completed' => 2 ] ), // 20% — below
		];

		$stats = DashboardStatisticsDB::completion_from_rows( $rows, 70 );

		$this->assertSame( 20, $stats['enrolled'] );
		$this->assertSame( 10, $stats['completed'] );
		$this->assertSame( 50.0, $stats['rate'] );
		$this->assertSame( 1, $stats['courses_below_target'] );
	}

	public function test_completion_empty_rows_gives_null_rate(): void {
		$stats = DashboardStatisticsDB::completion_from_rows( [], 70 );

		$this->assertNull( $stats['rate'] );
		$this->assertSame( 0, $stats['enrolled'] );
		$this->assertSame( 0, $stats['courses_below_target'] );
	}

	public function test_completion_exactly_on_target_not_below(): void {
		$rows = [ $this->row( [ 'enrolled' => 10, 'completed' => 7 ] ) ]; // exactly 70%

		$stats = DashboardStatisticsDB::completion_from_rows( $rows, 70 );

		$this->assertSame( 0, $stats['courses_below_target'] );
	}

	public function test_completion_zero_enrolled_course_not_counted_below(): void {
		$rows = [ $this->row( [ 'enrolled' => 0, 'completed' => 0 ] ) ];

		$stats = DashboardStatisticsDB::completion_from_rows( $rows, 70 );

		$this->assertSame( 0, $stats['courses_below_target'] );
		$this->assertNull( $stats['rate'] );
	}

	public function test_average_completion_excludes_zero_enrolled_courses(): void {
		$rows = [
			$this->row( [ 'enrolled' => 0, 'completed' => 0 ] ),
			$this->row( [ 'enrolled' => 10, 'completed' => 5 ] ),
			$this->row( [ 'enrolled' => 4, 'completed' => 4 ] ),
		];

		$this->assertSame( 75.0, DashboardStatisticsDB::average_completion_rate_from_rows( $rows ) );
	}

	public function test_average_completion_empty_rows_gives_null(): void {
		$this->assertNull( DashboardStatisticsDB::average_completion_rate_from_rows( [] ) );
	}

	/*
	|--------------------------------------------------------------------------
	| merge_course_performance()
	|--------------------------------------------------------------------------
	*/

	public function test_merge_joins_both_sides_by_course_id(): void {
		$revenue = [
			$this->row( [ 'course_id' => 1, 'course_name' => 'A', 'revenue' => '100.00', 'order_count' => 4 ] ),
		];
		$enroll  = [
			$this->row( [ 'course_id' => 1, 'course_name' => 'A', 'enrolled' => 10, 'completed' => 5 ] ),
		];

		$merged = DashboardStatisticsDB::merge_course_performance( $revenue, $enroll, 5 );

		$this->assertCount( 1, $merged );
		$this->assertSame( 100.0, $merged[0]['revenue'] );
		$this->assertSame( 4, $merged[0]['order_count'] );
		$this->assertSame( 10, $merged[0]['enrolled'] );
		$this->assertSame( 50.0, $merged[0]['completion_rate'] );
	}

	public function test_merge_keeps_courses_present_on_one_side_only(): void {
		$revenue = [ $this->row( [ 'course_id' => 1, 'course_name' => 'Paid', 'revenue' => '50.00', 'order_count' => 1 ] ) ];
		$enroll  = [ $this->row( [ 'course_id' => 2, 'course_name' => 'Free', 'enrolled' => 20, 'completed' => 0 ] ) ];

		$merged = DashboardStatisticsDB::merge_course_performance( $revenue, $enroll, 5 );

		$this->assertCount( 2, $merged );
		// Revenue sorts first.
		$this->assertSame( 'Paid', $merged[0]['course_name'] );
		$this->assertSame( 0, $merged[0]['enrolled'] );
		$this->assertSame( 'Free', $merged[1]['course_name'] );
		$this->assertSame( 0.0, $merged[1]['revenue'] );
		$this->assertSame( 0.0, $merged[1]['completion_rate'] );
	}

	public function test_merge_sorts_by_revenue_then_enrollments_and_limits(): void {
		$revenue = [
			$this->row( [ 'course_id' => 1, 'course_name' => 'Low', 'revenue' => '10.00', 'order_count' => 1 ] ),
			$this->row( [ 'course_id' => 2, 'course_name' => 'High', 'revenue' => '99.00', 'order_count' => 1 ] ),
		];
		$enroll  = [
			$this->row( [ 'course_id' => 3, 'course_name' => 'Free big', 'enrolled' => 50, 'completed' => 10 ] ),
			$this->row( [ 'course_id' => 4, 'course_name' => 'Free small', 'enrolled' => 5, 'completed' => 1 ] ),
		];

		$merged = DashboardStatisticsDB::merge_course_performance( $revenue, $enroll, 3 );

		$this->assertCount( 3, $merged );
		$this->assertSame( [ 'High', 'Low', 'Free big' ], array_column( $merged, 'course_name' ) );
	}

	public function test_merge_zero_enrolled_rate_is_null(): void {
		$enroll = [ $this->row( [ 'course_id' => 9, 'course_name' => 'X', 'enrolled' => 0, 'completed' => 0 ] ) ];

		$merged = DashboardStatisticsDB::merge_course_performance( [], $enroll, 5 );

		$this->assertNull( $merged[0]['completion_rate'] );
	}

	/*
	|--------------------------------------------------------------------------
	| content_inventory_from_rows()
	|--------------------------------------------------------------------------
	*/

	public function test_content_inventory_folds_rows_and_totals(): void {
		$rows = [
			$this->row( [ 'post_type' => LP_COURSE_CPT, 'post_status' => 'publish', 'item_count' => 2 ] ),
			$this->row( [ 'post_type' => LP_COURSE_CPT, 'post_status' => 'draft', 'item_count' => 1 ] ),
			$this->row( [ 'post_type' => LP_LESSON_CPT, 'post_status' => 'pending', 'item_count' => 3 ] ),
			$this->row( [ 'post_type' => LP_QUIZ_CPT, 'post_status' => 'future', 'item_count' => 4 ] ),
		];

		$inventory = DashboardStatisticsDB::content_inventory_from_rows( $rows, false );

		$this->assertSame( 2, $inventory['courses']['publish'] );
		$this->assertSame( 3, $inventory['courses']['total'] );
		$this->assertSame( 3, $inventory['lessons']['pending'] );
		$this->assertSame( 4, $inventory['quizzes']['future'] );
		$this->assertArrayNotHasKey( 'assignments', $inventory );
	}

	public function test_content_inventory_includes_assignments_when_enabled(): void {
		$rows = [
			$this->row( [ 'post_type' => 'lp_assignment', 'post_status' => 'publish', 'item_count' => 5 ] ),
		];

		$inventory = DashboardStatisticsDB::content_inventory_from_rows( $rows, true );

		$this->assertSame( 5, $inventory['assignments']['publish'] );
		$this->assertSame( 5, $inventory['assignments']['total'] );
	}

	/*
	|--------------------------------------------------------------------------
	| course_status_label()
	|--------------------------------------------------------------------------
	*/

	public function test_course_status_label_prioritizes_low_quizzes(): void {
		$this->assertSame(
			'high_failed_quizzes',
			DashboardStatisticsDB::course_status_label( 20.0, true, 70 )
		);
	}

	public function test_course_status_label_warns_on_low_completion(): void {
		$this->assertSame(
			'watch_completion',
			DashboardStatisticsDB::course_status_label( 69.9, false, 70 )
		);
	}

	public function test_course_status_label_healthy_when_no_signal(): void {
		$this->assertSame(
			'healthy',
			DashboardStatisticsDB::course_status_label( null, false, 70 )
		);
	}

	/*
	|--------------------------------------------------------------------------
	| student_status()
	|--------------------------------------------------------------------------
	*/

	private const STATUS_RULES = [
		'active_days'   => 7,
		'at_risk_ratio' => 2,
	];
	private const STATUS_NOW   = '2026-07-10 12:00:00';

	public function test_student_status_recent_activity_is_active(): void {
		$this->assertSame(
			'active',
			DashboardStatisticsDB::student_status( 5, 0, '2026-07-08 09:00:00', self::STATUS_RULES, self::STATUS_NOW )
		);
	}

	public function test_student_status_activity_on_boundary_is_active(): void {
		$this->assertSame(
			'active',
			DashboardStatisticsDB::student_status( 1, 0, '2026-07-03 12:00:00', self::STATUS_RULES, self::STATUS_NOW )
		);
	}

	public function test_student_status_stale_and_behind_is_at_risk(): void {
		// 5 enrolled > 2 × 1 completed, last activity 30 days back.
		$this->assertSame(
			'at_risk',
			DashboardStatisticsDB::student_status( 5, 1, '2026-06-10 12:00:00', self::STATUS_RULES, self::STATUS_NOW )
		);
	}

	public function test_student_status_never_started_but_enrolled_is_at_risk(): void {
		$this->assertSame(
			'at_risk',
			DashboardStatisticsDB::student_status( 1, 0, null, self::STATUS_RULES, self::STATUS_NOW )
		);
	}

	public function test_student_status_stale_but_on_track_is_idle(): void {
		// 2 enrolled is NOT > 2 × 1 completed.
		$this->assertSame(
			'idle',
			DashboardStatisticsDB::student_status( 2, 1, '2026-01-01 12:00:00', self::STATUS_RULES, self::STATUS_NOW )
		);
	}

	public function test_student_status_respects_filtered_rules(): void {
		$loose = [
			'active_days'   => 60,
			'at_risk_ratio' => 10,
		];

		$this->assertSame(
			'active',
			DashboardStatisticsDB::student_status( 5, 0, '2026-06-10 12:00:00', $loose, self::STATUS_NOW )
		);
	}
}
