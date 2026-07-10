<?php
/**
 * Class DashboardStatisticsDB
 *
 * @package LearnPress/Classes/Statistics
 * @since 4.4.2
 */

namespace LearnPress\Statistics;

use LP_Database;
use LP_Filter;
use LP_Statistics_DB;

defined( 'ABSPATH' ) || exit();

/**
 * Aggregate queries for the statistics dashboard (Overview tab and beyond).
 *
 * All time-ranged methods share the signature
 * ( string $type, string $value, ?StatisticsScope $scope = null ) and return
 * typed empties on empty input — the REST controller try/catch is the only
 * error boundary.
 *
 * @since 4.4.2
 */
class DashboardStatisticsDB extends LP_Database {
	/**
	 * @var DashboardStatisticsDB
	 */
	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	/**
	 * @return DashboardStatisticsDB
	 */
	public static function getInstance(): DashboardStatisticsDB {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Prepared "AND ..." time-range condition, reusing the tested
	 * LP_Statistics_DB::filter_time() mapping.
	 *
	 * @param string $type       date|month|year|previous_days|previous_months|custom.
	 * @param string $value      Time value.
	 * @param string $time_field Hardcoded column, e.g. 'ui.start_time'.
	 * @return string
	 */
	private function time_condition( string $type, string $value, string $time_field ): string {
		$filter = new LP_Filter();
		$filter = LP_Statistics_DB::getInstance()->filter_time( $filter, $type, $time_field, $value );

		return $filter->where[0] ?? '';
	}

	/**
	 * @param StatisticsScope|null $scope
	 * @param string               $course_id_field
	 * @return string
	 */
	private function scope_condition( ?StatisticsScope $scope, string $course_id_field ): string {
		if ( ! $scope || $scope->is_empty() ) {
			return '';
		}

		return $scope->sql_conditions( $course_id_field );
	}

	/**
	 * Course enrollments started in the range.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @return int
	 */
	public function get_enrollments_count( string $type, string $value, ?StatisticsScope $scope = null ): int {
		if ( ! $type || ! $value ) {
			return 0;
		}

		$time  = $this->time_condition( $type, $value, 'ui.start_time' );
		$where = $this->scope_condition( $scope, 'ui.item_id' );

		$sql = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tb_lp_user_items} AS ui
			WHERE ui.item_type = %s {$time} {$where}",
			LP_COURSE_CPT
		);

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Per-course enrolled/completed rows for the range (one GROUP BY query).
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @return array Rows of { course_id, enrolled, completed }.
	 */
	public function get_completion_rows( string $type, string $value, ?StatisticsScope $scope = null ): array {
		if ( ! $type || ! $value ) {
			return [];
		}

		$time  = $this->time_condition( $type, $value, 'ui.start_time' );
		$where = $this->scope_condition( $scope, 'ui.item_id' );

		$sql = $this->wpdb->prepare(
			"SELECT ui.item_id AS course_id,
				COUNT(*) AS enrolled,
				SUM( ui.status = %s ) AS completed
			FROM {$this->tb_lp_user_items} AS ui
			WHERE ui.item_type = %s {$time} {$where}
			GROUP BY ui.item_id",
			'finished',
			LP_COURSE_CPT
		);

		$rows = $this->wpdb->get_results( $sql );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Completion aggregate + per-course below-target count. Pure math on the
	 * grouped rows — kept static so unit tests need no DB.
	 *
	 * @param array $rows   From get_completion_rows().
	 * @param int   $target Completion target percent.
	 * @return array [ 'rate' => float|null, 'enrolled' => int, 'completed' => int, 'courses_below_target' => int ]
	 */
	public static function completion_from_rows( array $rows, int $target ): array {
		$enrolled  = 0;
		$completed = 0;
		$below     = 0;

		foreach ( $rows as $row ) {
			$course_enrolled  = (int) ( $row->enrolled ?? 0 );
			$course_completed = (int) ( $row->completed ?? 0 );
			$enrolled        += $course_enrolled;
			$completed       += $course_completed;

			if ( $course_enrolled > 0 && ( $course_completed / $course_enrolled ) * 100 < $target ) {
				++$below;
			}
		}

		return [
			'rate'                 => $enrolled > 0 ? round( $completed / $enrolled * 100, 1 ) : null,
			'enrolled'             => $enrolled,
			'completed'            => $completed,
			'courses_below_target' => $below,
		];
	}

	/**
	 * Average per-course completion rate for courses with at least one enrollment.
	 *
	 * Zero-enrollment courses are excluded because they have no denominator and
	 * would turn "no data yet" into a false 0% completion signal.
	 *
	 * @param array $rows From get_completion_rows().
	 * @return float|null
	 */
	public static function average_completion_rate_from_rows( array $rows ): ?float {
		$total_rate = 0.0;
		$count      = 0;

		foreach ( $rows as $row ) {
			$enrolled = (int) ( $row->enrolled ?? 0 );
			if ( $enrolled <= 0 ) {
				continue;
			}

			$total_rate += ( (int) ( $row->completed ?? 0 ) / $enrolled ) * 100;
			++$count;
		}

		return $count > 0 ? round( $total_rate / $count, 1 ) : null;
	}

	/**
	 * Completion stats for the range (target filterable).
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @return array See completion_from_rows().
	 */
	public function get_completion_stats( string $type, string $value, ?StatisticsScope $scope = null ): array {
		$target = (int) apply_filters( 'learn-press/statistics/completion-target', 70 );

		return self::completion_from_rows( $this->get_completion_rows( $type, $value, $scope ), $target );
	}

	/**
	 * Distinct users who started a lesson or quiz in the range.
	 *
	 * Child rows carry their own start_time (verified on 4.4.x data);
	 * scope goes through the parent course user_item row (ui2).
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @return int
	 */
	public function get_active_learners_count( string $type, string $value, ?StatisticsScope $scope = null ): int {
		if ( ! $type || ! $value ) {
			return 0;
		}

		$time  = $this->time_condition( $type, $value, 'ui.start_time' );
		$where = '';

		if ( $scope && ! $scope->is_empty() ) {
			$parent_conditions = $scope->sql_conditions( 'ui2.item_id' );
			$where             = " AND EXISTS ( SELECT 1 FROM {$this->tb_lp_user_items} AS ui2
				WHERE ui2.user_item_id = ui.parent_id {$parent_conditions} )";
		}

		$sql = $this->wpdb->prepare(
			"SELECT COUNT( DISTINCT ui.user_id ) FROM {$this->tb_lp_user_items} AS ui
			WHERE ui.item_type IN ( %s, %s ) {$time} {$where}",
			LP_LESSON_CPT,
			LP_QUIZ_CPT
		);

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Learner funnel counts for the range.
	 *
	 * 'registered' intentionally ignores scope: a user registration has no
	 * course dimension, so instructor/category cannot apply to it.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param bool                 $with_failed Add a 'failed' step (Users tab) — distinct users with a failed course graduation in range.
	 * @return array [ 'registered' => int, 'enrolled' => int, 'started' => int, 'completed' => int, 'failed'? => int ]
	 */
	public function get_learner_funnel( string $type, string $value, ?StatisticsScope $scope = null, bool $with_failed = false ): array {
		if ( ! $type || ! $value ) {
			$empty = [
				'registered' => 0,
				'enrolled'   => 0,
				'started'    => 0,
				'completed'  => 0,
			];
			if ( $with_failed ) {
				$empty['failed'] = 0;
			}

			return $empty;
		}

		$course_scope = $this->scope_condition( $scope, 'ui.item_id' );

		$time_users = $this->time_condition( $type, $value, 'u.user_registered' );
		$registered = (int) $this->wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->tb_users} AS u WHERE 1=1 {$time_users}"
		);

		$time_items = $this->time_condition( $type, $value, 'ui.start_time' );

		$enrolled = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT( DISTINCT ui.user_id ) FROM {$this->tb_lp_user_items} AS ui
				WHERE ui.item_type = %s {$time_items} {$course_scope}",
				LP_COURSE_CPT
			)
		);

		$started = $this->get_active_learners_count( $type, $value, $scope );

		$completed = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT( DISTINCT ui.user_id ) FROM {$this->tb_lp_user_items} AS ui
				WHERE ui.item_type = %s AND ui.status = %s {$time_items} {$course_scope}",
				LP_COURSE_CPT,
				'finished'
			)
		);

		$funnel = [
			'registered' => $registered,
			'enrolled'   => $enrolled,
			'started'    => $started,
			'completed'  => $completed,
		];

		if ( $with_failed ) {
			$funnel['failed'] = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT( DISTINCT ui.user_id ) FROM {$this->tb_lp_user_items} AS ui
					WHERE ui.item_type = %s AND ui.graduation = %s {$time_items} {$course_scope}",
					LP_COURSE_CPT,
					'failed'
				)
			);
		}

		return $funnel;
	}

	/**
	 * Revenue side of top-course performance (orders in range, grouped by course).
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit Row cap (popup drill-down needs more than the widget's 50).
	 * @return array Rows of { course_id, course_name, revenue, order_count }.
	 */
	public function get_course_revenue_rows( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 50 ): array {
		if ( ! $type || ! $value ) {
			return [];
		}

		$time  = $this->time_condition( $type, $value, 'p.post_date' );
		$where = $this->scope_condition( $scope, 'oi.item_id' );

		$sql = $this->wpdb->prepare(
			"SELECT oi.item_id AS course_id,
				p2.post_title AS course_name,
				SUM( CAST( oim.meta_value AS DECIMAL(10,2) ) ) AS revenue,
				COUNT( DISTINCT p.ID ) AS order_count
			FROM {$this->tb_posts} AS p
			INNER JOIN {$this->tb_lp_order_items} AS oi ON oi.order_id = p.ID
			INNER JOIN {$this->tb_posts} AS p2 ON p2.ID = oi.item_id
			INNER JOIN {$this->tb_lp_order_itemmeta} AS oim ON oim.learnpress_order_item_id = oi.order_item_id AND oim.meta_key = %s
			WHERE p.post_type = %s AND p.post_status = %s AND oi.item_type = %s {$time} {$where}
			GROUP BY oi.item_id, p2.post_title
			ORDER BY revenue DESC
			LIMIT %d",
			'_total',
			LP_ORDER_CPT,
			LP_ORDER_COMPLETED_DB,
			LP_COURSE_CPT,
			max( 1, $limit )
		);

		$rows = $this->wpdb->get_results( $sql );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Enrollment side of top-course performance (user_items in range, grouped by course).
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit Row cap (popup drill-down needs more than the widget's 50).
	 * @return array Rows of { course_id, course_name, enrolled, completed }.
	 */
	public function get_course_enrollment_rows( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 50 ): array {
		if ( ! $type || ! $value ) {
			return [];
		}

		$time  = $this->time_condition( $type, $value, 'ui.start_time' );
		$where = $this->scope_condition( $scope, 'ui.item_id' );

		$sql = $this->wpdb->prepare(
			"SELECT ui.item_id AS course_id,
				p2.post_title AS course_name,
				COUNT(*) AS enrolled,
				SUM( ui.status = %s ) AS completed
			FROM {$this->tb_lp_user_items} AS ui
			INNER JOIN {$this->tb_posts} AS p2 ON p2.ID = ui.item_id
			WHERE ui.item_type = %s {$time} {$where}
			GROUP BY ui.item_id, p2.post_title
			ORDER BY enrolled DESC
			LIMIT %d",
			'finished',
			LP_COURSE_CPT,
			max( 1, $limit )
		);

		$rows = $this->wpdb->get_results( $sql );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Merge the two GROUP BY result sets by course_id (no mega-join — perf).
	 * Static pure math, unit-testable without DB.
	 *
	 * @param array $revenue_rows From get_course_revenue_rows().
	 * @param array $enroll_rows  From get_course_enrollment_rows().
	 * @param int   $limit        Rows to keep after sorting by revenue, then enrollments.
	 * @return array Rows of { course_id, course_name, revenue, order_count, enrolled, completed, completion_rate }.
	 */
	public static function merge_course_performance( array $revenue_rows, array $enroll_rows, int $limit ): array {
		$merged = [];

		foreach ( $revenue_rows as $row ) {
			$course_id            = (int) $row->course_id;
			$merged[ $course_id ] = [
				'course_id'       => $course_id,
				'course_name'     => (string) $row->course_name,
				'revenue'         => (float) $row->revenue,
				'order_count'     => (int) $row->order_count,
				'enrolled'        => 0,
				'completed'       => 0,
				'completion_rate' => null,
			];
		}

		foreach ( $enroll_rows as $row ) {
			$course_id = (int) $row->course_id;

			if ( ! isset( $merged[ $course_id ] ) ) {
				$merged[ $course_id ] = [
					'course_id'       => $course_id,
					'course_name'     => (string) $row->course_name,
					'revenue'         => 0.0,
					'order_count'     => 0,
					'enrolled'        => 0,
					'completed'       => 0,
					'completion_rate' => null,
				];
			}

			$enrolled  = (int) $row->enrolled;
			$completed = (int) $row->completed;

			$merged[ $course_id ]['enrolled']        = $enrolled;
			$merged[ $course_id ]['completed']       = $completed;
			$merged[ $course_id ]['completion_rate'] = $enrolled > 0 ? round( $completed / $enrolled * 100, 1 ) : null;
		}

		usort(
			$merged,
			function ( $a, $b ) {
				return [ $b['revenue'], $b['enrolled'] ] <=> [ $a['revenue'], $a['enrolled'] ];
			}
		);

		return array_slice( $merged, 0, max( 1, $limit ) );
	}

	/**
	 * Top courses by revenue + enrollments/completion for the range.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit
	 * @return array See merge_course_performance().
	 */
	public function get_top_courses_performance( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 5 ): array {
		$fetch_limit = max( 50, $limit );

		return self::merge_course_performance(
			$this->get_course_revenue_rows( $type, $value, $scope, $fetch_limit ),
			$this->get_course_enrollment_rows( $type, $value, $scope, $fetch_limit ),
			$limit
		);
	}

	/**
	 * Current content inventory, not time-filtered.
	 *
	 * Course scope reaches courses directly through p.ID and curriculum items
	 * through section lineage (item -> section -> course).
	 *
	 * @param StatisticsScope|null $scope
	 * @return array
	 */
	public function get_content_inventory( ?StatisticsScope $scope = null ): array {
		$include_assignments = class_exists( 'LP_Assignment' );
		$post_type_where     = $include_assignments
			? $this->wpdb->prepare( 'p.post_type IN ( %s, %s, %s, %s )', LP_COURSE_CPT, LP_LESSON_CPT, LP_QUIZ_CPT, LP_ASSIGNMENT_CPT )
			: $this->wpdb->prepare( 'p.post_type IN ( %s, %s, %s )', LP_COURSE_CPT, LP_LESSON_CPT, LP_QUIZ_CPT );
		$item_type_where     = $include_assignments
			? $this->wpdb->prepare( 'p.post_type IN ( %s, %s, %s )', LP_LESSON_CPT, LP_QUIZ_CPT, LP_ASSIGNMENT_CPT )
			: $this->wpdb->prepare( 'p.post_type IN ( %s, %s )', LP_LESSON_CPT, LP_QUIZ_CPT );
		$status_where        = $this->wpdb->prepare( 'p.post_status IN ( %s, %s, %s, %s )', 'publish', 'pending', 'future', 'draft' );
		$join                = '';
		$scope_where         = '';

		if ( $scope && ! $scope->is_empty() ) {
			$join              = "LEFT JOIN {$this->tb_lp_section_items} AS si ON si.item_id = p.ID
				LEFT JOIN {$this->tb_lp_sections} AS s ON s.section_id = si.section_id";
			$course_type_where = $this->wpdb->prepare( 'p.post_type = %s', LP_COURSE_CPT );
			$course_scope      = $scope->sql_conditions( 'p.ID' );
			$item_scope        = $scope->sql_conditions( 's.section_course_id' );
			$scope_where       = "AND ( ( {$course_type_where} {$course_scope} ) OR ( {$item_type_where} {$item_scope} ) )";
		}

		$sql = "SELECT p.post_type, p.post_status, COUNT( DISTINCT p.ID ) AS item_count
			FROM {$this->tb_posts} AS p
			{$join}
			WHERE {$post_type_where} AND {$status_where} {$scope_where}
			GROUP BY p.post_type, p.post_status";

		$rows = $this->wpdb->get_results( $sql );

		return self::content_inventory_from_rows( is_array( $rows ) ? $rows : [], $include_assignments );
	}

	/**
	 * Fold GROUP BY post_type/status rows into the dashboard inventory shape.
	 *
	 * @param array $rows
	 * @param bool  $include_assignments
	 * @return array
	 */
	public static function content_inventory_from_rows( array $rows, bool $include_assignments ): array {
		$statuses             = [ 'publish', 'pending', 'future', 'draft' ];
		$assignment_post_type = defined( 'LP_ASSIGNMENT_CPT' ) ? LP_ASSIGNMENT_CPT : 'lp_assignment';
		$bucket_map           = [
			LP_COURSE_CPT => 'courses',
			LP_LESSON_CPT => 'lessons',
			LP_QUIZ_CPT   => 'quizzes',
		];

		if ( $include_assignments ) {
			$bucket_map[ $assignment_post_type ] = 'assignments';
		}

		$inventory = [];
		foreach ( $bucket_map as $bucket ) {
			$inventory[ $bucket ]          = array_fill_keys( $statuses, 0 );
			$inventory[ $bucket ]['total'] = 0;
		}

		foreach ( $rows as $row ) {
			$post_type = (string) ( $row->post_type ?? '' );
			$status    = (string) ( $row->post_status ?? '' );

			if ( ! isset( $bucket_map[ $post_type ] ) || ! in_array( $status, $statuses, true ) ) {
				continue;
			}

			$count  = (int) ( $row->item_count ?? 0 );
			$bucket = $bucket_map[ $post_type ];

			$inventory[ $bucket ][ $status ] = $count;
			$inventory[ $bucket ]['total']  += $count;
		}

		return $inventory;
	}

	/**
	 * Paid course quantities sold in completed orders.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @return int
	 */
	public function get_paid_courses_sold( string $type, string $value, ?StatisticsScope $scope = null ): int {
		if ( ! $type || ! $value ) {
			return 0;
		}

		$time  = $this->time_condition( $type, $value, 'p.post_date' );
		$where = $this->scope_condition( $scope, 'oi.item_id' );

		$sql = $this->wpdb->prepare(
			"SELECT SUM( CAST( oim_qty.meta_value AS UNSIGNED ) )
			FROM {$this->tb_posts} AS p
			INNER JOIN {$this->tb_lp_order_items} AS oi ON oi.order_id = p.ID
			INNER JOIN {$this->tb_lp_order_itemmeta} AS oim_qty ON oim_qty.learnpress_order_item_id = oi.order_item_id AND oim_qty.meta_key = %s
			INNER JOIN {$this->tb_lp_order_itemmeta} AS oim_total ON oim_total.learnpress_order_item_id = oi.order_item_id AND oim_total.meta_key = %s AND CAST( oim_total.meta_value AS DECIMAL(10,2) ) > 0
			WHERE p.post_type = %s AND p.post_status = %s AND oi.item_type = %s {$time} {$where}",
			'_quantity',
			'_total',
			LP_ORDER_CPT,
			LP_ORDER_COMPLETED_DB,
			LP_COURSE_CPT
		);

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Detailed paid top-sold courses for the Orders dashboard.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit
	 * @return array Rows of { course_id, name, revenue, orders, aov, status_label }.
	 */
	public function get_top_sold_courses_detailed( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 10 ): array {
		if ( ! $type || ! $value ) {
			return [];
		}

		$limit = max( 1, $limit );
		$time  = $this->time_condition( $type, $value, 'p.post_date' );
		$where = $this->scope_condition( $scope, 'oi.item_id' );

		$sql = $this->wpdb->prepare(
			"SELECT oi.item_id AS course_id,
				p2.post_title AS name,
				SUM( CAST( oim_total.meta_value AS DECIMAL(10,2) ) ) AS revenue,
				COUNT( DISTINCT p.ID ) AS orders
			FROM {$this->tb_posts} AS p
			INNER JOIN {$this->tb_lp_order_items} AS oi ON oi.order_id = p.ID
			INNER JOIN {$this->tb_posts} AS p2 ON p2.ID = oi.item_id
			INNER JOIN {$this->tb_lp_order_itemmeta} AS oim_total ON oim_total.learnpress_order_item_id = oi.order_item_id AND oim_total.meta_key = %s AND CAST( oim_total.meta_value AS DECIMAL(10,2) ) > 0
			WHERE p.post_type = %s AND p.post_status = %s AND oi.item_type = %s {$time} {$where}
			GROUP BY oi.item_id, p2.post_title
			ORDER BY revenue DESC, orders DESC
			LIMIT %d",
			'_total',
			LP_ORDER_CPT,
			LP_ORDER_COMPLETED_DB,
			LP_COURSE_CPT,
			$limit
		);

		$rows = $this->wpdb->get_results( $sql );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$course_ids      = array_map( 'absint', wp_list_pluck( $rows, 'course_id' ) );
		$completion_map  = $this->get_course_completion_rate_map( $type, $value, $course_ids );
		$low_quiz_map    = $this->get_low_quiz_course_map( $course_ids );
		$completion_goal = (int) apply_filters( 'learn-press/statistics/completion-target', 70 );

		return array_map(
			function ( $row ) use ( $completion_map, $low_quiz_map, $completion_goal ) {
				$course_id       = (int) $row->course_id;
				$revenue         = (float) $row->revenue;
				$orders          = (int) $row->orders;
				$completion_rate = $completion_map[ $course_id ] ?? null;

				return [
					'course_id'    => $course_id,
					'name'         => (string) $row->name,
					'revenue'      => $revenue,
					'orders'       => $orders,
					'aov'          => $orders > 0 ? round( $revenue / $orders, 2 ) : null,
					'status_label' => self::course_status_label(
						$completion_rate,
						! empty( $low_quiz_map[ $course_id ] ),
						$completion_goal
					),
				];
			},
			$rows
		);
	}

	/**
	 * Pure status-label mapping for Orders top-sold rows.
	 *
	 * @param float|null $completion_rate
	 * @param bool       $has_low_quiz
	 * @param int        $completion_goal
	 * @return string healthy|watch_completion|high_failed_quizzes.
	 */
	public static function course_status_label( ?float $completion_rate, bool $has_low_quiz, int $completion_goal ): string {
		if ( $has_low_quiz ) {
			return 'high_failed_quizzes';
		}

		if ( null !== $completion_rate && $completion_rate < $completion_goal ) {
			return 'watch_completion';
		}

		return 'healthy';
	}

	/**
	 * @param string $type
	 * @param string $value
	 * @param array  $course_ids
	 * @return array course_id => completion_rate|null
	 */
	private function get_course_completion_rate_map( string $type, string $value, array $course_ids ): array {
		$course_ids = array_values( array_filter( array_unique( array_map( 'absint', $course_ids ) ) ) );
		if ( empty( $course_ids ) ) {
			return [];
		}

		$course_ids_sql = implode( ', ', $course_ids );
		$time           = $this->time_condition( $type, $value, 'ui.start_time' );

		$sql = $this->wpdb->prepare(
			"SELECT ui.item_id AS course_id,
				COUNT(*) AS enrolled,
				SUM( ui.status = %s ) AS completed
			FROM {$this->tb_lp_user_items} AS ui
			WHERE ui.item_type = %s AND ui.item_id IN ( {$course_ids_sql} ) {$time}
			GROUP BY ui.item_id",
			'finished',
			LP_COURSE_CPT
		);

		$rows = $this->wpdb->get_results( $sql );
		$map  = [];

		foreach ( (array) $rows as $row ) {
			$enrolled                     = (int) $row->enrolled;
			$map[ (int) $row->course_id ] = $enrolled > 0 ? round( (int) $row->completed / $enrolled * 100, 1 ) : null;
		}

		return $map;
	}

	/**
	 * @param array $course_ids
	 * @return array course_id => true
	 */
	private function get_low_quiz_course_map( array $course_ids ): array {
		$course_ids = array_values( array_filter( array_unique( array_map( 'absint', $course_ids ) ) ) );
		if ( empty( $course_ids ) ) {
			return [];
		}

		$threshold      = (float) apply_filters( 'learn-press/statistics/quiz-pass-alert', 50 );
		$min_attempts   = (int) apply_filters( 'learn-press/statistics/quiz-pass-alert-min-attempts', 5 );
		$course_ids_sql = implode( ', ', $course_ids );

		$sql = $this->wpdb->prepare(
			"SELECT low_quizzes.course_id
			FROM (
				SELECT s.section_course_id AS course_id, ui.item_id
				FROM {$this->tb_lp_user_items} AS ui
				INNER JOIN {$this->tb_lp_section_items} AS si ON si.item_id = ui.item_id
				INNER JOIN {$this->tb_lp_sections} AS s ON s.section_id = si.section_id
				WHERE ui.item_type = %s AND ui.graduation IN ( %s, %s )
				GROUP BY s.section_course_id, ui.item_id
				HAVING COUNT(*) >= %d AND ( SUM( ui.graduation = %s ) / COUNT(*) ) * 100 < %f
			) AS low_quizzes
			WHERE low_quizzes.course_id IN ( {$course_ids_sql} )
			GROUP BY low_quizzes.course_id",
			LP_QUIZ_CPT,
			'passed',
			'failed',
			$min_attempts,
			'passed',
			$threshold
		);

		$rows = $this->wpdb->get_col( $sql );
		$map  = [];

		foreach ( (array) $rows as $course_id ) {
			$map[ (int) $course_id ] = true;
		}

		return $map;
	}

	/**
	 * Instructor summary: course count + range-bound revenue/enrollments/completion.
	 *
	 * Scope semantics (documented in the task file): the scope filters WHICH
	 * instructors appear (instructor_id → that instructor; category_id →
	 * instructors with a published course in the category). The per-row
	 * metrics always cover the instructor's whole portfolio — an instructor
	 * summary sliced to a category would misstate their performance.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit
	 * @return array Rows of { instructor_id, instructor_name, course_count, revenue, enrolled, completed, completion_rate }.
	 */
	public function get_instructor_performance( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 5 ): array {
		if ( ! $type || ! $value ) {
			return [];
		}

		$time_orders = $this->time_condition( $type, $value, 'o.post_date' );
		$time_items  = $this->time_condition( $type, $value, 'ui.start_time' );
		$list_where  = $this->scope_condition( $scope, 'p.ID' );

		if ( $scope && $scope->instructor_id > 0 ) {
			$list_where .= $this->wpdb->prepare( ' AND u.ID = %d', $scope->instructor_id );
		}

		$sql = $this->wpdb->prepare(
			"SELECT u.ID AS instructor_id,
				u.display_name AS instructor_name,
				COUNT( DISTINCT p.ID ) AS course_count,
				( SELECT SUM( CAST( oim.meta_value AS DECIMAL(10,2) ) )
					FROM {$this->tb_lp_order_items} AS oi
					INNER JOIN {$this->tb_posts} AS o ON o.ID = oi.order_id
					INNER JOIN {$this->tb_posts} AS pc ON pc.ID = oi.item_id
					INNER JOIN {$this->tb_lp_order_itemmeta} AS oim ON oim.learnpress_order_item_id = oi.order_item_id AND oim.meta_key = '_total'
					WHERE pc.post_author = u.ID AND o.post_type = %s AND o.post_status = %s AND oi.item_type = %s {$time_orders}
				) AS revenue,
				( SELECT COUNT(*)
					FROM {$this->tb_lp_user_items} AS ui
					INNER JOIN {$this->tb_posts} AS pc2 ON pc2.ID = ui.item_id
					WHERE pc2.post_author = u.ID AND ui.item_type = %s {$time_items}
				) AS enrolled,
				( SELECT COUNT(*)
					FROM {$this->tb_lp_user_items} AS ui
					INNER JOIN {$this->tb_posts} AS pc3 ON pc3.ID = ui.item_id
					WHERE pc3.post_author = u.ID AND ui.item_type = %s AND ui.status = 'finished' {$time_items}
				) AS completed
			FROM {$this->tb_users} AS u
			INNER JOIN {$this->tb_posts} AS p ON p.post_author = u.ID AND p.post_type = %s AND p.post_status = 'publish'
			WHERE 1=1 {$list_where}
			GROUP BY u.ID, u.display_name
			ORDER BY revenue DESC
			LIMIT %d",
			LP_ORDER_CPT,
			LP_ORDER_COMPLETED_DB,
			LP_COURSE_CPT,
			LP_COURSE_CPT,
			LP_COURSE_CPT,
			LP_COURSE_CPT,
			max( 1, $limit )
		);

		$rows = $this->wpdb->get_results( $sql );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		return array_map(
			function ( $row ) {
				$enrolled  = (int) $row->enrolled;
				$completed = (int) $row->completed;

				return [
					'instructor_id'   => (int) $row->instructor_id,
					'instructor_name' => (string) $row->instructor_name,
					'course_count'    => (int) $row->course_count,
					'revenue'         => (float) $row->revenue,
					'enrolled'        => $enrolled,
					'completed'       => $completed,
					'completion_rate' => $enrolled > 0 ? round( $completed / $enrolled * 100, 1 ) : null,
				];
			},
			$rows
		);
	}

	/**
	 * Instructors whose courses received at least one enrollment in the range.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @return int
	 */
	public function get_instructors_active_in_period( string $type, string $value, ?StatisticsScope $scope = null ): int {
		if ( ! $type || ! $value ) {
			return 0;
		}

		$time  = $this->time_condition( $type, $value, 'ui.start_time' );
		$where = $this->scope_condition( $scope, 'ui.item_id' );

		$sql = $this->wpdb->prepare(
			"SELECT COUNT( DISTINCT p.post_author ) FROM {$this->tb_lp_user_items} AS ui
			INNER JOIN {$this->tb_posts} AS p ON p.ID = ui.item_id
			WHERE ui.item_type = %s {$time} {$where}",
			LP_COURSE_CPT
		);

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Distinct users with an in-progress course graduation in the range.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @return int
	 */
	public function get_users_in_progress_count( string $type, string $value, ?StatisticsScope $scope = null ): int {
		if ( ! $type || ! $value ) {
			return 0;
		}

		$time  = $this->time_condition( $type, $value, 'ui.start_time' );
		$where = $this->scope_condition( $scope, 'ui.item_id' );

		$sql = $this->wpdb->prepare(
			"SELECT COUNT( DISTINCT ui.user_id ) FROM {$this->tb_lp_user_items} AS ui
			WHERE ui.item_type = %s AND ui.graduation = %s {$time} {$where}",
			LP_COURSE_CPT,
			'in-progress'
		);

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Student status slug from activity + progress. Pure math, unit-testable.
	 *
	 * active  — child-item activity within active_days;
	 * at_risk — no recent activity and enrolled > ratio × completed;
	 * idle    — everything else.
	 *
	 * @param int         $enrolled    Courses enrolled in range.
	 * @param int         $completed   Courses finished in range.
	 * @param string|null $last_active MySQL datetime of last child activity, null = never started.
	 * @param array       $rules       [ 'active_days' => int, 'at_risk_ratio' => float ].
	 * @param string      $now         MySQL datetime anchor (injectable for tests).
	 * @return string active|at_risk|idle
	 */
	public static function student_status( int $enrolled, int $completed, ?string $last_active, array $rules, string $now ): string {
		$active_days = (int) ( $rules['active_days'] ?? 7 );
		$ratio       = (float) ( $rules['at_risk_ratio'] ?? 2 );

		if ( $last_active && strtotime( $last_active ) >= strtotime( $now ) - $active_days * DAY_IN_SECONDS ) {
			return 'active';
		}

		if ( $enrolled > $ratio * $completed ) {
			return 'at_risk';
		}

		return 'idle';
	}

	/**
	 * Top students by enrollments in the range.
	 *
	 * Two-phase for perf: one GROUP BY user_id over course rows picks the top
	 * users, then last-activity and quiz pass-ratio are fetched in two batched
	 * IN() queries for those users only — never per row, never JSON parsing
	 * of user_item_results in SQL.
	 *
	 * Privacy: display_name only — this table is CSV-exported.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit
	 * @return array Rows of { user_id, name, enrolled, completed, avg_score, last_active, status }.
	 */
	public function get_top_students( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 10 ): array {
		if ( ! $type || ! $value ) {
			return [];
		}

		$time  = $this->time_condition( $type, $value, 'ui.start_time' );
		$where = $this->scope_condition( $scope, 'ui.item_id' );

		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT ui.user_id,
					u.display_name AS name,
					COUNT(*) AS enrolled,
					SUM( ui.status = %s ) AS completed
				FROM {$this->tb_lp_user_items} AS ui
				INNER JOIN {$this->tb_users} AS u ON u.ID = ui.user_id
				WHERE ui.item_type = %s {$time} {$where}
				GROUP BY ui.user_id, u.display_name
				ORDER BY enrolled DESC, completed DESC
				LIMIT %d",
				'finished',
				LP_COURSE_CPT,
				max( 1, $limit )
			)
		);

		if ( ! is_array( $rows ) || ! $rows ) {
			return [];
		}

		$user_ids     = array_map(
			function ( $row ) {
				return (int) $row->user_id;
			},
			$rows
		);
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );

		$activity_rows = $this->wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- dynamic IN() placeholders, count matches at runtime.
			$this->wpdb->prepare(
				"SELECT c.user_id, MAX( c.start_time ) AS last_active
				FROM {$this->tb_lp_user_items} AS c
				WHERE c.item_type IN ( %s, %s ) AND c.user_id IN ( {$placeholders} )
				GROUP BY c.user_id",
				...array_merge( [ LP_LESSON_CPT, LP_QUIZ_CPT ], $user_ids )
			)
		);
		$last_active = array_column( (array) $activity_rows, 'last_active', 'user_id' );

		$score_rows = $this->wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- dynamic IN() placeholders, count matches at runtime.
			$this->wpdb->prepare(
				"SELECT q.user_id, ( SUM( q.graduation = %s ) / COUNT(*) ) * 100 AS avg_score
				FROM {$this->tb_lp_user_items} AS q
				WHERE q.item_type = %s AND q.graduation IN ( %s, %s ) AND q.user_id IN ( {$placeholders} )
				GROUP BY q.user_id",
				...array_merge( [ 'passed', LP_QUIZ_CPT, 'passed', 'failed' ], $user_ids )
			)
		);
		$avg_score = array_column( (array) $score_rows, 'avg_score', 'user_id' );

		$rules = apply_filters(
			'learn-press/statistics/student-status-rules',
			[
				'active_days'   => 7,
				'at_risk_ratio' => 2,
			]
		);
		$now   = current_time( 'mysql' );

		return array_map(
			function ( $row ) use ( $last_active, $avg_score, $rules, $now ) {
				$user_id = (int) $row->user_id;
				$active  = $last_active[ $user_id ] ?? null;

				return [
					'user_id'     => $user_id,
					'name'        => (string) $row->name,
					'enrolled'    => (int) $row->enrolled,
					'completed'   => (int) $row->completed,
					'avg_score'   => isset( $avg_score[ $user_id ] ) ? round( (float) $avg_score[ $user_id ], 1 ) : null,
					'last_active' => $active,
					'status'      => self::student_status( (int) $row->enrolled, (int) $row->completed, $active, $rules, $now ),
				];
			},
			$rows
		);
	}

	/**
	 * Courses ranked by students in the range, with started/active-7d
	 * conditional sums — one GROUP BY, no per-row queries.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit
	 * @return array Rows of { course_id, name, enrolled, started, completed, completion_rate, active_7d }.
	 */
	public function get_courses_by_students( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 10 ): array {
		if ( ! $type || ! $value ) {
			return [];
		}

		$time  = $this->time_condition( $type, $value, 'ui.start_time' );
		$where = $this->scope_condition( $scope, 'ui.item_id' );

		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT ui.item_id AS course_id,
					p.post_title AS name,
					COUNT(*) AS enrolled,
					SUM( ui.status = %s ) AS completed,
					SUM( EXISTS (
						SELECT 1 FROM {$this->tb_lp_user_items} AS c
						WHERE c.parent_id = ui.user_item_id AND c.item_type IN ( %s, %s )
					) ) AS started,
					SUM( EXISTS (
						SELECT 1 FROM {$this->tb_lp_user_items} AS c7
						WHERE c7.parent_id = ui.user_item_id AND c7.item_type IN ( %s, %s )
						AND c7.start_time >= DATE_ADD( NOW(), INTERVAL -7 DAY )
					) ) AS active_7d
				FROM {$this->tb_lp_user_items} AS ui
				INNER JOIN {$this->tb_posts} AS p ON p.ID = ui.item_id
				WHERE ui.item_type = %s {$time} {$where}
				GROUP BY ui.item_id, p.post_title
				ORDER BY enrolled DESC
				LIMIT %d",
				'finished',
				LP_LESSON_CPT,
				LP_QUIZ_CPT,
				LP_LESSON_CPT,
				LP_QUIZ_CPT,
				LP_COURSE_CPT,
				max( 1, $limit )
			)
		);

		if ( ! is_array( $rows ) ) {
			return [];
		}

		return array_map(
			function ( $row ) {
				$enrolled  = (int) $row->enrolled;
				$completed = (int) $row->completed;

				return [
					'course_id'       => (int) $row->course_id,
					'name'            => (string) $row->name,
					'enrolled'        => $enrolled,
					'started'         => (int) $row->started,
					'completed'       => $completed,
					'completion_rate' => $enrolled > 0 ? round( $completed / $enrolled * 100, 1 ) : null,
					'active_7d'       => (int) $row->active_7d,
				];
			},
			$rows
		);
	}

	/**
	 * Map a course completion rate to a risk slug.
	 *
	 * Bands filterable via 'learn-press/statistics/risk-bands' — [ high_below, medium_ceiling ].
	 * high: rate < high_below · medium: high_below ≤ rate ≤ medium_ceiling · healthy: above.
	 * A null rate (no enrollments to assess) is treated as healthy — never a false alarm.
	 *
	 * @param float|null $completion_rate
	 * @param array      $bands [ 40, 55 ] by default.
	 * @return string high|medium|healthy
	 */
	public static function watchlist_risk( ?float $completion_rate, array $bands ): string {
		if ( null === $completion_rate ) {
			return 'healthy';
		}

		$high   = (float) ( $bands[0] ?? 40 );
		$medium = (float) ( $bands[1] ?? 55 );

		if ( $completion_rate < $high ) {
			return 'high';
		}

		if ( $completion_rate <= $medium ) {
			return 'medium';
		}

		return 'healthy';
	}

	/**
	 * Recommended action slug for a watchlist row. Pure precedence, unit-testable.
	 *
	 * quiz failing → review_quiz_difficulty (most specific signal wins);
	 * empty curriculum → build_curriculum (nothing else is actionable);
	 * at-risk with curriculum → add_practice_content;
	 * otherwise → monitor. Client maps slug → localized sentence.
	 *
	 * @param string $risk           From watchlist_risk().
	 * @param bool   $has_curriculum
	 * @param bool   $has_low_quiz
	 * @return string
	 */
	public static function watchlist_action( string $risk, bool $has_curriculum, bool $has_low_quiz ): string {
		if ( $has_low_quiz ) {
			return 'review_quiz_difficulty';
		}

		if ( ! $has_curriculum ) {
			return 'build_curriculum';
		}

		if ( 'healthy' !== $risk ) {
			return 'add_practice_content';
		}

		return 'monitor';
	}

	/**
	 * Per-course watchlist for the range: worst completion first, with risk +
	 * recommended action. Only courses with at least one enrollment appear.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit
	 * @return array Rows of { course_id, name, instructor, completion_rate, risk, action }.
	 */
	public function get_course_watchlist( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 10 ): array {
		if ( ! $type || ! $value ) {
			return [];
		}

		$time  = $this->time_condition( $type, $value, 'ui.start_time' );
		$where = $this->scope_condition( $scope, 'ui.item_id' );

		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT ui.item_id AS course_id,
					p.post_title AS name,
					u.display_name AS instructor,
					COUNT(*) AS enrolled,
					SUM( ui.status = %s ) AS completed,
					EXISTS (
						SELECT 1 FROM {$this->tb_lp_sections} AS s
						INNER JOIN {$this->tb_lp_section_items} AS si ON si.section_id = s.section_id
						WHERE s.section_course_id = ui.item_id
					) AS has_curriculum
				FROM {$this->tb_lp_user_items} AS ui
				INNER JOIN {$this->tb_posts} AS p ON p.ID = ui.item_id
				INNER JOIN {$this->tb_users} AS u ON u.ID = p.post_author
				WHERE ui.item_type = %s {$time} {$where}
				GROUP BY ui.item_id, p.post_title, u.display_name
				ORDER BY ( SUM( ui.status = %s ) / COUNT(*) ) ASC, enrolled DESC
				LIMIT %d",
				'finished',
				LP_COURSE_CPT,
				'finished',
				max( 1, $limit )
			)
		);

		if ( ! is_array( $rows ) || ! $rows ) {
			return [];
		}

		$course_ids   = array_map(
			function ( $row ) {
				return (int) $row->course_id;
			},
			$rows
		);
		$low_quiz_map = $this->get_low_quiz_course_map( $course_ids );
		$bands        = (array) apply_filters( 'learn-press/statistics/risk-bands', [ 40, 55 ] );

		return array_map(
			function ( $row ) use ( $low_quiz_map, $bands ) {
				$enrolled        = (int) $row->enrolled;
				$completion_rate = $enrolled > 0 ? round( (int) $row->completed / $enrolled * 100, 1 ) : null;
				$has_curriculum  = (bool) $row->has_curriculum;
				$has_low_quiz    = ! empty( $low_quiz_map[ (int) $row->course_id ] );
				$risk            = self::watchlist_risk( $completion_rate, $bands );
				$action          = self::watchlist_action( $risk, $has_curriculum, $has_low_quiz );

				return [
					'course_id'       => (int) $row->course_id,
					'name'            => (string) $row->name,
					'instructor'      => (string) $row->instructor,
					'completion_rate' => $completion_rate,
					'risk'            => $risk,
					// Per-row override point for gateway/add-on rules.
					'action'          => (string) apply_filters( 'learn-press/statistics/watchlist-actions', $action, $row, $risk ),
				];
			},
			$rows
		);
	}

	/**
	 * Pending-review course counts per instructor (all-time — a pending course
	 * is a backlog regardless of the selected range). Scope-filtered.
	 *
	 * @param StatisticsScope|null $scope
	 * @return array Rows of { instructor_id, name, pending }, most pending first.
	 */
	public function get_pending_courses_by_instructor( ?StatisticsScope $scope = null ): array {
		$where = $this->scope_condition( $scope, 'p.ID' );

		if ( $scope && $scope->instructor_id > 0 ) {
			$where .= $this->wpdb->prepare( ' AND u.ID = %d', $scope->instructor_id );
		}

		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT p.post_author AS instructor_id,
					u.display_name AS name,
					COUNT(*) AS pending
				FROM {$this->tb_posts} AS p
				INNER JOIN {$this->tb_users} AS u ON u.ID = p.post_author
				WHERE p.post_type = %s AND p.post_status = %s {$where}
				GROUP BY p.post_author, u.display_name
				ORDER BY pending DESC",
				LP_COURSE_CPT,
				'pending'
			)
		);

		if ( ! is_array( $rows ) ) {
			return [];
		}

		return array_map(
			function ( $row ) {
				return [
					'instructor_id' => (int) $row->instructor_id,
					'name'          => (string) $row->name,
					'pending'       => (int) $row->pending,
				];
			},
			$rows
		);
	}
}
