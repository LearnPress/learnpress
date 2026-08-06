<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Statistics;

use Brain\Monkey\Functions;
use LearnPress\Statistics\DashboardStatisticsDB;
use LearnPress\Statistics\InstructorStatisticsProvider;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

// Parent class of DashboardStatisticsDB (legacy, not PSR-4 autoloadable).
require_once dirname( __DIR__, 3 ) . '/inc/Databases/class-lp-db.php';

/**
 * Pure derivation helpers for the Instructors tab.
 *
 * @covers \LearnPress\Statistics\InstructorStatisticsProvider
 * @covers \LearnPress\Statistics\DashboardStatisticsDB::watchlist_risk
 * @covers \LearnPress\Statistics\DashboardStatisticsDB::watchlist_action
 */
class InstructorStatisticsTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		// Money formatter isn't stubbed by the base case — echo the raw amount back.
		Functions\when( 'learn_press_format_price' )->returnArg();
	}

	private function perfRow( array $data ): array {
		return array_merge(
			array(
				'instructor_id'   => 0,
				'instructor_name' => '',
				'course_count'    => 0,
				'revenue'         => 0.0,
				'enrolled'        => 0,
				'completed'       => 0,
				'completion_rate' => null,
			),
			$data
		);
	}

	/*
	|--------------------------------------------------------------------------
	| watchlist_risk() — band edges
	|--------------------------------------------------------------------------
	*/

	public function test_risk_below_high_band_is_high(): void {
		$this->assertSame( 'high', DashboardStatisticsDB::watchlist_risk( 39.9, [ 40, 55 ] ) );
	}

	public function test_risk_on_high_boundary_is_medium(): void {
		$this->assertSame( 'medium', DashboardStatisticsDB::watchlist_risk( 40.0, [ 40, 55 ] ) );
	}

	public function test_risk_on_medium_ceiling_is_medium(): void {
		$this->assertSame( 'medium', DashboardStatisticsDB::watchlist_risk( 55.0, [ 40, 55 ] ) );
	}

	public function test_risk_above_medium_ceiling_is_healthy(): void {
		$this->assertSame( 'healthy', DashboardStatisticsDB::watchlist_risk( 55.1, [ 40, 55 ] ) );
	}

	public function test_risk_null_rate_is_healthy(): void {
		$this->assertSame( 'healthy', DashboardStatisticsDB::watchlist_risk( null, [ 40, 55 ] ) );
	}

	public function test_risk_respects_custom_bands(): void {
		$this->assertSame( 'high', DashboardStatisticsDB::watchlist_risk( 60.0, [ 70, 90 ] ) );
	}

	/*
	|--------------------------------------------------------------------------
	| watchlist_action() — precedence
	|--------------------------------------------------------------------------
	*/

	public function test_action_low_quiz_wins_over_everything(): void {
		// No curriculum AND high risk, but a failing quiz takes precedence.
		$this->assertSame(
			'review_quiz_difficulty',
			DashboardStatisticsDB::watchlist_action( 'high', false, true )
		);
	}

	public function test_action_no_curriculum_before_completion(): void {
		$this->assertSame(
			'build_curriculum',
			DashboardStatisticsDB::watchlist_action( 'high', false, false )
		);
	}

	public function test_action_at_risk_with_curriculum_adds_practice(): void {
		$this->assertSame(
			'add_practice_content',
			DashboardStatisticsDB::watchlist_action( 'medium', true, false )
		);
	}

	public function test_action_healthy_with_curriculum_monitors(): void {
		$this->assertSame(
			'monitor',
			DashboardStatisticsDB::watchlist_action( 'healthy', true, false )
		);
	}

	/*
	|--------------------------------------------------------------------------
	| build_kpis()
	|--------------------------------------------------------------------------
	*/

	public function test_kpis_aggregate_rows(): void {
		$rows = array(
			$this->perfRow( array( 'revenue' => 100.0, 'course_count' => 2, 'enrolled' => 10, 'completion_rate' => 80.0 ) ),
			$this->perfRow( array( 'revenue' => 50.0, 'course_count' => 1, 'enrolled' => 5, 'completion_rate' => 40.0 ) ),
		);

		$kpis = InstructorStatisticsProvider::build_kpis( $rows, 300.0, 2, 3 );

		$this->assertSame( 150.0, $kpis['instructor_revenue']['value'] );
		$this->assertSame( 50.0, $kpis['instructor_revenue']['contribution_pct'] ); // 150 / 300
		$this->assertSame( 3, $kpis['courses_managed']['value'] );
		$this->assertSame( 15, $kpis['students_reached']['value'] );
		$this->assertSame( 60.0, $kpis['avg_completion']['value'] ); // ( 80 + 40 ) / 2
		$this->assertSame( 2, $kpis['active_instructors']['value'] );
		$this->assertSame( 2, $kpis['active_instructors']['total'] );
		$this->assertSame( 3, $kpis['needs_review']['value'] );
	}

	public function test_kpis_zero_total_sales_gives_null_contribution(): void {
		$rows = array( $this->perfRow( array( 'revenue' => 0.0 ) ) );

		$kpis = InstructorStatisticsProvider::build_kpis( $rows, 0.0, 0, 0 );

		$this->assertNull( $kpis['instructor_revenue']['contribution_pct'] );
	}

	public function test_kpis_avg_completion_ignores_null_rates(): void {
		$rows = array(
			$this->perfRow( array( 'completion_rate' => 90.0 ) ),
			$this->perfRow( array( 'completion_rate' => null ) ),
		);

		$kpis = InstructorStatisticsProvider::build_kpis( $rows, 100.0, 0, 0 );

		$this->assertSame( 90.0, $kpis['avg_completion']['value'] );
	}

	public function test_kpis_all_null_completion_is_null(): void {
		$rows = array( $this->perfRow( array( 'completion_rate' => null ) ) );

		$kpis = InstructorStatisticsProvider::build_kpis( $rows, 100.0, 0, 0 );

		$this->assertNull( $kpis['avg_completion']['value'] );
	}

	/*
	|--------------------------------------------------------------------------
	| build_operations()
	|--------------------------------------------------------------------------
	*/

	public function test_operations_pick_maxima(): void {
		$rows = array(
			$this->perfRow( array( 'instructor_id' => 1, 'instructor_name' => 'Alice', 'revenue' => 100.0, 'enrolled' => 10, 'completion_rate' => 50.0 ) ),
			$this->perfRow( array( 'instructor_id' => 2, 'instructor_name' => 'Bob', 'revenue' => 200.0, 'enrolled' => 0, 'completion_rate' => 90.0 ) ),
		);
		$pending = array(
			array( 'instructor_id' => 3, 'name' => 'Carol', 'pending' => 5 ),
			array( 'instructor_id' => 1, 'name' => 'Alice', 'pending' => 2 ),
		);

		$ops = InstructorStatisticsProvider::build_operations( $rows, $pending, 7 );

		$this->assertSame( 2, $ops['top_revenue']['id'] ); // Bob, 200
		$this->assertSame( 'Bob', $ops['top_completion']['name'] ); // 90
		$this->assertSame( 3, $ops['most_pending']['id'] ); // Carol, 5
		$this->assertSame( 5, $ops['most_pending']['value'] );
		$this->assertSame( 1, $ops['no_new_enrollments'] ); // Bob has 0
		$this->assertSame( 7, $ops['review_queue_count'] );
	}

	public function test_operations_no_pending_rows(): void {
		$rows = array( $this->perfRow( array( 'instructor_id' => 1, 'revenue' => 10.0, 'enrolled' => 5, 'completion_rate' => 60.0 ) ) );

		$ops = InstructorStatisticsProvider::build_operations( $rows, array(), 0 );

		$this->assertNull( $ops['most_pending'] );
		$this->assertSame( 0, $ops['no_new_enrollments'] );
	}

	public function test_operations_top_completion_ignores_null(): void {
		$rows = array(
			$this->perfRow( array( 'instructor_id' => 1, 'instructor_name' => 'A', 'completion_rate' => null ) ),
			$this->perfRow( array( 'instructor_id' => 2, 'instructor_name' => 'B', 'completion_rate' => 30.0 ) ),
		);

		$ops = InstructorStatisticsProvider::build_operations( $rows, array(), 0 );

		$this->assertSame( 2, $ops['top_completion']['id'] );
	}

	/*
	|--------------------------------------------------------------------------
	| merge_report_rows()
	|--------------------------------------------------------------------------
	*/

	public function test_merge_report_combines_by_course(): void {
		$sold     = array( (object) array( 'course_id' => 1, 'course_name' => 'PHP', 'course_count' => 4, 'total_revenue' => '120.00' ) );
		$enrolled = array( (object) array( 'course_id' => 1, 'course_name' => 'PHP', 'enrollment_count' => 20 ) );

		$merged = InstructorStatisticsProvider::merge_report_rows( $sold, $enrolled );

		$this->assertCount( 1, $merged );
		$this->assertSame( 4, $merged[0]['sold'] );
		$this->assertSame( 120.0, $merged[0]['revenue'] );
		$this->assertSame( 20, $merged[0]['enrolled'] );
	}

	public function test_merge_report_keeps_one_sided_rows(): void {
		$sold     = array( (object) array( 'course_id' => 1, 'course_name' => 'Paid', 'course_count' => 1, 'total_revenue' => '50.00' ) );
		$enrolled = array( (object) array( 'course_id' => 2, 'course_name' => 'Free', 'enrollment_count' => 30 ) );

		$merged = InstructorStatisticsProvider::merge_report_rows( $sold, $enrolled );

		$this->assertCount( 2, $merged );
		// Revenue sorts first.
		$this->assertSame( 'Paid', $merged[0]['name'] );
		$this->assertSame( 0, $merged[0]['enrolled'] );
		$this->assertSame( 'Free', $merged[1]['name'] );
		$this->assertSame( 0.0, $merged[1]['revenue'] );
		$this->assertSame( 30, $merged[1]['enrolled'] );
	}
}
