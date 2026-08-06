<?php
/**
 * Class InstructorStatisticsProvider
 *
 * @package LearnPress/Classes/Statistics
 * @since 4.4.2
 */

namespace LearnPress\Statistics;

use LP_Statistics_DB;

defined( 'ABSPATH' ) || exit();

/**
 * Assembles the Instructors tab payload.
 *
 * Composes DashboardStatisticsDB (query layer) and derives KPIs + the
 * operations widget in PHP from a single performance query — no per-KPI
 * queries. Pure derivation is split into static helpers so the risk/action
 * and aggregation logic is unit-testable without a database.
 *
 * @since 4.4.2
 */
class InstructorStatisticsProvider {
	/**
	 * Ceiling for the performance query that also feeds KPI/operations math.
	 * Well above any realistic instructor count; a larger site is logged.
	 */
	const AGGREGATE_LIMIT = 500;

	/**
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $performance_limit Rows kept for the visible table.
	 * @return array
	 */
	public static function get_statistics( string $type, string $value, ?StatisticsScope $scope = null, int $performance_limit = 10 ): array {
		$db = DashboardStatisticsDB::getInstance();

		// One query drives the table, KPIs and operations.
		$rows = $db->get_instructor_performance( $type, $value, $scope, self::AGGREGATE_LIMIT );
		if ( count( $rows ) >= self::AGGREGATE_LIMIT ) {
			error_log( 'LP Statistics: instructor count reached the ' . self::AGGREGATE_LIMIT . ' aggregate cap; KPI totals may undercount.' );
		}

		$total_net_sales    = self::get_total_net_sales( $type, $value );
		$active_instructors = $db->get_instructors_active_in_period( $type, $value, $scope );
		$pending_rows       = $db->get_pending_courses_by_instructor( $scope );
		$needs_review       = array_sum( array_column( $pending_rows, 'pending' ) );

		return array(
			'kpis'        => self::build_kpis( $rows, $total_net_sales, $active_instructors, $needs_review ),
			'operations'  => self::build_operations( $rows, $pending_rows, $needs_review ),
			'performance' => self::format_performance( array_slice( $rows, 0, max( 1, $performance_limit ) ) ),
			'watchlist'   => $db->get_course_watchlist( $type, $value, $scope, 10 ),
		);
	}

	/**
	 * Per-instructor drill-down: sold + enrolled courses merged by course_id,
	 * paginated for the report popup.
	 *
	 * The two source queries are merged in PHP, so the page slice happens after
	 * the merge over a bounded candidate set ( learn-press/statistics/report-max-rows ).
	 *
	 * @param int    $instructor_id
	 * @param int    $paged  1-based page.
	 * @param int    $limit  Rows per page.
	 * @param string $search Optional course-title filter.
	 * @return array { instructor_id, rows: [...], total: int, error?: string }
	 */
	public static function get_report( int $instructor_id, int $paged = 1, int $limit = 20, string $search = '' ): array {
		$instructor_id = absint( $instructor_id );
		if ( $instructor_id <= 0 || ! get_user_by( 'id', $instructor_id ) ) {
			return array(
				'instructor_id' => $instructor_id,
				'rows'          => array(),
				'total'         => 0,
				'error'         => 'invalid_instructor',
			);
		}

		$cap         = (int) apply_filters( 'learn-press/statistics/report-max-rows', 2000 );
		$lp_stats_db = LP_Statistics_DB::getInstance();
		$sold        = $lp_stats_db->get_top_sold_courses_by_instructor( $instructor_id, $cap, $search );
		$enrolled    = $lp_stats_db->get_top_enrolled_courses_by_instructor( $instructor_id, $cap, $search );

		$all    = self::merge_report_rows( $sold, $enrolled );
		$total  = count( $all );
		$paged  = max( 1, $paged );
		$limit  = max( 1, $limit );
		$offset = ( $paged - 1 ) * $limit;

		$rows = array_map(
			function ( $row ) {
				$row['revenue_formatted'] = html_entity_decode( learn_press_format_price( $row['revenue'] ) );
				return $row;
			},
			array_slice( $all, $offset, $limit )
		);

		return array(
			'instructor_id' => $instructor_id,
			'rows'          => $rows,
			'total'         => $total,
		);
	}

	/**
	 * KPI payloads derived from the performance rows + totals. Pure.
	 *
	 * @param array $rows               Performance rows (scoped, uncapped-for-math).
	 * @param float $total_net_sales    Range net sales across all courses (contribution denominator).
	 * @param int   $active_instructors Instructors with an enrollment in range.
	 * @param int   $needs_review       Pending-review course count.
	 * @return array
	 */
	public static function build_kpis( array $rows, float $total_net_sales, int $active_instructors, int $needs_review ): array {
		$instructor_revenue = 0.0;
		$courses_managed    = 0;
		$students_reached   = 0;
		$rate_sum           = 0.0;
		$rate_count         = 0;

		foreach ( $rows as $row ) {
			$instructor_revenue += (float) ( $row['revenue'] ?? 0 );
			$courses_managed    += (int) ( $row['course_count'] ?? 0 );
			$students_reached   += (int) ( $row['enrolled'] ?? 0 );

			if ( null !== ( $row['completion_rate'] ?? null ) ) {
				$rate_sum += (float) $row['completion_rate'];
				++$rate_count;
			}
		}

		$contribution_pct = $total_net_sales > 0
			? round( $instructor_revenue / $total_net_sales * 100, 1 )
			: null;
		$avg_completion   = $rate_count > 0 ? round( $rate_sum / $rate_count, 1 ) : null;

		return array(
			'active_instructors' => array(
				'value' => $active_instructors,
				'total' => count( $rows ),
			),
			'instructor_revenue' => array(
				'value'            => round( $instructor_revenue, 2 ),
				'formatted'        => html_entity_decode( learn_press_format_price( $instructor_revenue ) ),
				'contribution_pct' => $contribution_pct,
			),
			'courses_managed'    => array(
				'value' => $courses_managed,
			),
			'students_reached'   => array(
				'value' => $students_reached,
			),
			'avg_completion'     => array(
				'value' => $avg_completion,
			),
			'needs_review'       => array(
				'value' => $needs_review,
			),
		);
	}

	/**
	 * Operations widget highlights from the same rows. Pure.
	 *
	 * @param array $rows         Performance rows.
	 * @param array $pending_rows From get_pending_courses_by_instructor(), most pending first.
	 * @param int   $needs_review Total pending count.
	 * @return array
	 */
	public static function build_operations( array $rows, array $pending_rows, int $needs_review ): array {
		$top_revenue        = null;
		$top_completion     = null;
		$no_new_enrollments = 0;

		foreach ( $rows as $row ) {
			$revenue = (float) ( $row['revenue'] ?? 0 );
			if ( null === $top_revenue || $revenue > $top_revenue['value'] ) {
				$top_revenue = array(
					'id'              => (int) ( $row['instructor_id'] ?? 0 ),
					'name'            => (string) ( $row['instructor_name'] ?? '' ),
					'value'           => $revenue,
					'value_formatted' => html_entity_decode( learn_press_format_price( $revenue ) ),
				);
			}

			$rate = $row['completion_rate'] ?? null;
			if ( null !== $rate && ( null === $top_completion || $rate > $top_completion['value'] ) ) {
				$top_completion = array(
					'id'    => (int) ( $row['instructor_id'] ?? 0 ),
					'name'  => (string) ( $row['instructor_name'] ?? '' ),
					'value' => (float) $rate,
				);
			}

			if ( 0 === (int) ( $row['enrolled'] ?? 0 ) ) {
				++$no_new_enrollments;
			}
		}

		$most_pending = null;
		if ( ! empty( $pending_rows ) ) {
			$most_pending = array(
				'id'    => (int) $pending_rows[0]['instructor_id'],
				'name'  => (string) $pending_rows[0]['name'],
				'value' => (int) $pending_rows[0]['pending'],
			);
		}

		return array(
			'top_revenue'        => $top_revenue,
			'top_completion'     => $top_completion,
			'most_pending'       => $most_pending,
			'no_new_enrollments' => $no_new_enrollments,
			'review_queue_count' => $needs_review,
		);
	}

	/**
	 * Shape performance rows for the table (add formatted revenue). Pure.
	 *
	 * @param array $rows
	 * @return array
	 */
	public static function format_performance( array $rows ): array {
		return array_map(
			function ( $row ) {
				$revenue = (float) ( $row['revenue'] ?? 0 );

				return array(
					'instructor_id'     => (int) ( $row['instructor_id'] ?? 0 ),
					'name'              => (string) ( $row['instructor_name'] ?? '' ),
					'courses'           => (int) ( $row['course_count'] ?? 0 ),
					'students'          => (int) ( $row['enrolled'] ?? 0 ),
					'revenue'           => $revenue,
					'revenue_formatted' => html_entity_decode( learn_press_format_price( $revenue ) ),
					'avg_completion'    => $row['completion_rate'] ?? null,
				);
			},
			$rows
		);
	}

	/**
	 * Merge sold + enrolled per-course result sets by course_id. Pure.
	 *
	 * @param array $sold     From get_top_sold_courses_by_instructor().
	 * @param array $enrolled From get_top_enrolled_courses_by_instructor().
	 * @return array Rows of { course_id, name, sold, revenue, enrolled }.
	 */
	public static function merge_report_rows( array $sold, array $enrolled ): array {
		$merged = array();

		foreach ( $sold as $row ) {
			$course_id            = (int) $row->course_id;
			$merged[ $course_id ] = array(
				'course_id' => $course_id,
				'name'      => (string) $row->course_name,
				'sold'      => (int) $row->course_count,
				'revenue'   => (float) $row->total_revenue,
				'enrolled'  => 0,
			);
		}

		foreach ( $enrolled as $row ) {
			$course_id = (int) $row->course_id;

			if ( ! isset( $merged[ $course_id ] ) ) {
				$merged[ $course_id ] = array(
					'course_id' => $course_id,
					'name'      => (string) $row->course_name,
					'sold'      => 0,
					'revenue'   => 0.0,
					'enrolled'  => 0,
				);
			}

			$merged[ $course_id ]['enrolled'] = (int) $row->enrollment_count;
		}

		usort(
			$merged,
			function ( $a, $b ) {
				return [ $b['revenue'], $b['enrolled'] ] <=> [ $a['revenue'], $a['enrolled'] ];
			}
		);

		return array_values( $merged );
	}

	/**
	 * Range net sales across all completed orders (contribution denominator).
	 *
	 * @param string $type
	 * @param string $value
	 * @return float
	 */
	private static function get_total_net_sales( string $type, string $value ): float {
		$rows = LP_Statistics_DB::getInstance()->get_net_sales_data( $type, $value );

		$total = 0.0;
		foreach ( (array) $rows as $row ) {
			$total += (float) $row->x_data;
		}

		return $total;
	}
}
