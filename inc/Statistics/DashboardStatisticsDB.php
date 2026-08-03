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
	 * Prepared "AND {$field} LIKE '%...%'" fragment for the report popup search
	 * box. Empty search → no condition. LIKE wildcards in the term are escaped.
	 *
	 * The returned fragment is meant to be interpolated into ANOTHER
	 * wpdb::prepare() string (the report queries build their SQL that way). To
	 * survive that second prepare pass its literal '%' are doubled — the outer
	 * prepare() collapses each '%%' back to '%', reproducing the fragment
	 * verbatim instead of mistaking '%term%' for placeholders.
	 *
	 * @param string $search
	 * @param string $field Hardcoded, already-qualified column (e.g. 'p2.post_title').
	 * @return string
	 * @since 4.4.2
	 */
	private function search_condition( string $search, string $field ): string {
		$search = trim( $search );
		if ( '' === $search ) {
			return '';
		}

		$fragment = $this->wpdb->prepare( " AND {$field} LIKE %s", '%' . $this->wpdb->esc_like( $search ) . '%' );

		return str_replace( '%', '%%', $fragment );
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
			return array();
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

		return is_array( $rows ) ? $rows : array();
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

		return array(
			'rate'                 => $enrolled > 0 ? round( $completed / $enrolled * 100, 1 ) : null,
			'enrolled'             => $enrolled,
			'completed'            => $completed,
			'courses_below_target' => $below,
		);
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
		return $this->compute_learner_funnel( $type, $value, $scope, $with_failed );
	}

	/**
	 * Funnel computation. See get_learner_funnel().
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param bool                 $with_failed
	 * @return array
	 */
	private function compute_learner_funnel( string $type, string $value, ?StatisticsScope $scope, bool $with_failed ): array {
		if ( ! $type || ! $value ) {
			$empty = array(
				'registered' => 0,
				'enrolled'   => 0,
				'started'    => 0,
				'completed'  => 0,
			);
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

		$funnel = array(
			'registered' => $registered,
			'enrolled'   => $enrolled,
			'started'    => $started,
			'completed'  => $completed,
		);

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
	 * @param string               $search Optional course-title filter (popup search box).
	 * @return array Rows of { course_id, course_name, revenue, order_count }.
	 */
	public function get_course_revenue_rows( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 50, string $search = '' ): array {
		if ( ! $type || ! $value ) {
			return array();
		}

		$time   = $this->time_condition( $type, $value, 'p.post_date' );
		$where  = $this->scope_condition( $scope, 'oi.item_id' );
		$search = $this->search_condition( $search, 'p2.post_title' );

		$sql = $this->wpdb->prepare(
			"SELECT oi.item_id AS course_id,
				p2.post_title AS course_name,
				SUM( CAST( oim.meta_value AS DECIMAL(10,2) ) ) AS revenue,
				COUNT( DISTINCT p.ID ) AS order_count
			FROM {$this->tb_posts} AS p
			INNER JOIN {$this->tb_lp_order_items} AS oi ON oi.order_id = p.ID
			INNER JOIN {$this->tb_posts} AS p2 ON p2.ID = oi.item_id
			INNER JOIN {$this->tb_lp_order_itemmeta} AS oim ON oim.learnpress_order_item_id = oi.order_item_id AND oim.meta_key = %s
			WHERE p.post_type = %s AND p.post_status = %s AND oi.item_type = %s {$time} {$where} {$search}
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

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Enrollment side of top-course performance (user_items in range, grouped by course).
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit Row cap (popup drill-down needs more than the widget's 50).
	 * @param string               $search Optional course-title filter (report popup search box).
	 * @return array Rows of { course_id, course_name, enrolled, completed }.
	 */
	public function get_course_enrollment_rows( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 50, string $search = '' ): array {
		if ( ! $type || ! $value ) {
			return array();
		}

		$time   = $this->time_condition( $type, $value, 'ui.start_time' );
		$where  = $this->scope_condition( $scope, 'ui.item_id' );
		$search = $this->search_condition( $search, 'p2.post_title' );

		$sql = $this->wpdb->prepare(
			"SELECT ui.item_id AS course_id,
				p2.post_title AS course_name,
				COUNT(*) AS enrolled,
				SUM( ui.status = %s ) AS completed
			FROM {$this->tb_lp_user_items} AS ui
			INNER JOIN {$this->tb_posts} AS p2 ON p2.ID = ui.item_id
			WHERE ui.item_type = %s {$time} {$where} {$search}
			GROUP BY ui.item_id, p2.post_title
			ORDER BY enrolled DESC
			LIMIT %d",
			'finished',
			LP_COURSE_CPT,
			max( 1, $limit )
		);

		$rows = $this->wpdb->get_results( $sql );

		return is_array( $rows ) ? $rows : array();
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
		$merged = array();

		foreach ( $revenue_rows as $row ) {
			$course_id            = (int) $row->course_id;
			$merged[ $course_id ] = array(
				'course_id'       => $course_id,
				'course_name'     => (string) $row->course_name,
				'revenue'         => (float) $row->revenue,
				'order_count'     => (int) $row->order_count,
				'enrolled'        => 0,
				'completed'       => 0,
				'completion_rate' => null,
			);
		}

		foreach ( $enroll_rows as $row ) {
			$course_id = (int) $row->course_id;

			if ( ! isset( $merged[ $course_id ] ) ) {
				$merged[ $course_id ] = array(
					'course_id'       => $course_id,
					'course_name'     => (string) $row->course_name,
					'revenue'         => 0.0,
					'order_count'     => 0,
					'enrolled'        => 0,
					'completed'       => 0,
					'completion_rate' => null,
				);
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
				return array( $b['revenue'], $b['enrolled'] ) <=> array( $a['revenue'], $a['enrolled'] );
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
	 * @param string               $search Optional course-title filter (report popup search box).
	 * @return array See merge_course_performance().
	 */
	public function get_top_courses_performance( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 5, string $search = '' ): array {
		$fetch_limit = max( 50, $limit );

		$merged = self::merge_course_performance(
			$this->get_course_revenue_rows( $type, $value, $scope, $fetch_limit, $search ),
			$this->get_course_enrollment_rows( $type, $value, $scope, $fetch_limit, $search ),
			$limit
		);

		if ( empty( $merged ) ) {
			return $merged;
		}

		// The revenue and enrollment lists are each capped independently, so a
		// displayed course can arrive missing the OTHER dimension ( shown as 0 /
		// null ). Re-query both metrics for exactly the displayed course ids so
		// every row's revenue and enrollment/completion are exact.
		$course_ids  = array_map(
			function ( $row ) {
				return (int) $row['course_id'];
			},
			$merged
		);
		$revenue_map = $this->get_course_revenue_totals( $type, $value, $scope, $course_ids );
		$enroll_map  = $this->get_course_enrollment_totals( $type, $value, $scope, $course_ids );

		foreach ( $merged as &$row ) {
			$course_id = (int) $row['course_id'];

			if ( isset( $revenue_map[ $course_id ] ) ) {
				$row['revenue'] = $revenue_map[ $course_id ];
			}

			if ( isset( $enroll_map[ $course_id ] ) ) {
				$enrolled               = (int) $enroll_map[ $course_id ]['enrolled'];
				$completed              = (int) $enroll_map[ $course_id ]['completed'];
				$row['enrolled']        = $enrolled;
				$row['completed']       = $completed;
				$row['completion_rate'] = $enrolled > 0 ? round( $completed / $enrolled * 100, 1 ) : null;
			}
		}
		unset( $row );

		return $merged;
	}

	/**
	 * Enrolled/completed course-row totals for a set of course IDs in the range.
	 * Companion to get_course_revenue_totals() — used to backfill the enrollment
	 * side of top-course performance for the displayed courses.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param array                $course_ids
	 * @return array course_id => [ 'enrolled' => int, 'completed' => int ]
	 * @since 4.4.2
	 */
	public function get_course_enrollment_totals( string $type, string $value, ?StatisticsScope $scope, array $course_ids ): array {
		$course_ids = array_values( array_filter( array_unique( array_map( 'absint', $course_ids ) ) ) );
		if ( ! $type || ! $value || empty( $course_ids ) ) {
			return array();
		}

		$time         = $this->time_condition( $type, $value, 'ui.start_time' );
		$where        = $this->scope_condition( $scope, 'ui.item_id' );
		$placeholders = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic %d list is built from absint-normalized IDs.
		$sql = $this->wpdb->prepare(
			"SELECT ui.item_id AS course_id,
				COUNT(*) AS enrolled,
				SUM( ui.status = %s ) AS completed
			FROM {$this->tb_lp_user_items} AS ui
			WHERE ui.item_type = %s AND ui.item_id IN ( {$placeholders} ) {$time} {$where}
			GROUP BY ui.item_id",
			'finished',
			LP_COURSE_CPT,
			...$course_ids
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$rows = $this->wpdb->get_results( $sql );
		$map  = array();

		foreach ( (array) $rows as $row ) {
			$map[ (int) $row->course_id ] = array(
				'enrolled'  => (int) $row->enrolled,
				'completed' => (int) $row->completed,
			);
		}

		return $map;
	}

	/**
	 * Batch-map course IDs to their author's display name.
	 *
	 * @param array $course_ids
	 * @return array course_id => display_name
	 * @since 4.4.2
	 */
	public function get_course_instructor_names( array $course_ids ): array {
		$course_ids = array_values( array_filter( array_unique( array_map( 'absint', $course_ids ) ) ) );
		if ( empty( $course_ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic %d list is built from absint-normalized IDs.
		$sql = $this->wpdb->prepare(
			"SELECT p.ID AS course_id, u.display_name AS instructor
			FROM {$this->tb_posts} AS p
			LEFT JOIN {$this->tb_users} AS u ON u.ID = p.post_author
			WHERE p.ID IN ( {$placeholders} )",
			...$course_ids
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$rows = $this->wpdb->get_results( $sql );
		$map  = array();

		foreach ( (array) $rows as $row ) {
			$map[ (int) $row->course_id ] = (string) $row->instructor;
		}

		return $map;
	}

	/**
	 * Batch-map course IDs to their course-category names.
	 *
	 * Returns a list per course ( sorted by name ) so callers can show the
	 * primary category on screen while exporting the full set to CSV.
	 *
	 * @param array $course_ids
	 * @return array course_id => string[] category names
	 * @since 4.4.2
	 */
	public function get_course_category_names( array $course_ids ): array {
		$course_ids = array_values( array_filter( array_unique( array_map( 'absint', $course_ids ) ) ) );
		if ( empty( $course_ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );
		// Multi-char separator that will not occur inside a term name, so the
		// GROUP_CONCAT can be split back into a clean list in PHP.
		$sep = '|~|';
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic %d list is built from absint-normalized IDs.
		$sql = $this->wpdb->prepare(
			"SELECT tr.object_id AS course_id,
				GROUP_CONCAT( DISTINCT t.name ORDER BY t.name SEPARATOR '{$sep}' ) AS category
			FROM {$this->wpdb->term_relationships} AS tr
			INNER JOIN {$this->wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
			INNER JOIN {$this->wpdb->terms} AS t ON t.term_id = tt.term_id
			WHERE tr.object_id IN ( {$placeholders} )
			GROUP BY tr.object_id",
			LP_COURSE_CATEGORY_TAX,
			...$course_ids
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$rows = $this->wpdb->get_results( $sql );
		$map  = array();

		foreach ( (array) $rows as $row ) {
			// Term names are entity-encoded in the DB; the cell re-escapes, so decode here.
			$names = explode( $sep, wp_specialchars_decode( (string) $row->category ) );
			$names = array_values( array_filter( array_map( 'trim', $names ), 'strlen' ) );

			$map[ (int) $row->course_id ] = $names;
		}

		return $map;
	}

	/**
	 * Completed-order revenue per course for a set of course IDs in the range.
	 * Used to compute the report "Trend" ( current vs previous period ).
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param array                $course_ids
	 * @return array course_id => float revenue
	 * @since 4.4.2
	 */
	public function get_course_revenue_totals( string $type, string $value, ?StatisticsScope $scope, array $course_ids ): array {
		$course_ids = array_values( array_filter( array_unique( array_map( 'absint', $course_ids ) ) ) );
		if ( ! $type || ! $value || empty( $course_ids ) ) {
			return array();
		}

		$time         = $this->time_condition( $type, $value, 'p.post_date' );
		$where        = $this->scope_condition( $scope, 'oi.item_id' );
		$placeholders = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic %d list is built from absint-normalized IDs.
		$sql = $this->wpdb->prepare(
			"SELECT oi.item_id AS course_id,
				SUM( CAST( oim.meta_value AS DECIMAL(10,2) ) ) AS revenue
			FROM {$this->tb_posts} AS p
			INNER JOIN {$this->tb_lp_order_items} AS oi ON oi.order_id = p.ID
			INNER JOIN {$this->tb_lp_order_itemmeta} AS oim ON oim.learnpress_order_item_id = oi.order_item_id AND oim.meta_key = %s
			WHERE p.post_type = %s AND p.post_status = %s AND oi.item_type = %s AND oi.item_id IN ( {$placeholders} ) {$time} {$where}
			GROUP BY oi.item_id",
			'_total',
			LP_ORDER_CPT,
			LP_ORDER_COMPLETED_DB,
			LP_COURSE_CPT,
			...$course_ids
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$rows = $this->wpdb->get_results( $sql );
		$map  = array();

		foreach ( (array) $rows as $row ) {
			$map[ (int) $row->course_id ] = (float) $row->revenue;
		}

		return $map;
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

		return self::content_inventory_from_rows( is_array( $rows ) ? $rows : array(), $include_assignments );
	}

	/**
	 * Fold GROUP BY post_type/status rows into the dashboard inventory shape.
	 *
	 * @param array $rows
	 * @param bool  $include_assignments
	 * @return array
	 */
	public static function content_inventory_from_rows( array $rows, bool $include_assignments ): array {
		$statuses             = array( 'publish', 'pending', 'future', 'draft' );
		$assignment_post_type = defined( 'LP_ASSIGNMENT_CPT' ) ? LP_ASSIGNMENT_CPT : 'lp_assignment';
		$bucket_map           = array(
			LP_COURSE_CPT => 'courses',
			LP_LESSON_CPT => 'lessons',
			LP_QUIZ_CPT   => 'quizzes',
		);

		if ( $include_assignments ) {
			$bucket_map[ $assignment_post_type ] = 'assignments';
		}

		$inventory = array();
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
	 * @param int                  $offset Row offset for report-popup pagination.
	 * @param string               $search Optional course-title filter.
	 * @return array Rows of { course_id, name, revenue, orders, aov, status_label }.
	 */
	public function get_top_sold_courses_detailed( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 10, int $offset = 0, string $search = '' ): array {
		if ( ! $type || ! $value ) {
			return array();
		}

		$limit  = max( 1, $limit );
		$offset = max( 0, $offset );
		$time   = $this->time_condition( $type, $value, 'p.post_date' );
		$where  = $this->scope_condition( $scope, 'oi.item_id' );
		$search = $this->search_condition( $search, 'p2.post_title' );

		$sql = $this->wpdb->prepare(
			"SELECT oi.item_id AS course_id,
				p2.post_title AS name,
				SUM( CAST( oim_total.meta_value AS DECIMAL(10,2) ) ) AS revenue,
				COUNT( DISTINCT p.ID ) AS orders
			FROM {$this->tb_posts} AS p
			INNER JOIN {$this->tb_lp_order_items} AS oi ON oi.order_id = p.ID
			INNER JOIN {$this->tb_posts} AS p2 ON p2.ID = oi.item_id
			INNER JOIN {$this->tb_lp_order_itemmeta} AS oim_total ON oim_total.learnpress_order_item_id = oi.order_item_id AND oim_total.meta_key = %s AND CAST( oim_total.meta_value AS DECIMAL(10,2) ) > 0
			WHERE p.post_type = %s AND p.post_status = %s AND oi.item_type = %s {$time} {$where} {$search}
			GROUP BY oi.item_id, p2.post_title
			ORDER BY revenue DESC, orders DESC
			LIMIT %d OFFSET %d",
			'_total',
			LP_ORDER_CPT,
			LP_ORDER_COMPLETED_DB,
			LP_COURSE_CPT,
			$limit,
			$offset
		);

		$rows = $this->wpdb->get_results( $sql );
		if ( ! is_array( $rows ) ) {
			return array();
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

				return array(
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
				);
			},
			$rows
		);
	}

	/**
	 * Total distinct paid courses matching get_top_sold_courses_detailed()'s
	 * filters — the row total for report-popup pagination.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param string               $search
	 * @return int
	 * @since 4.4.2
	 */
	public function count_top_sold_courses( string $type, string $value, ?StatisticsScope $scope = null, string $search = '' ): int {
		if ( ! $type || ! $value ) {
			return 0;
		}

		$time   = $this->time_condition( $type, $value, 'p.post_date' );
		$where  = $this->scope_condition( $scope, 'oi.item_id' );
		$search = $this->search_condition( $search, 'p2.post_title' );

		$sql = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM (
				SELECT oi.item_id
				FROM {$this->tb_posts} AS p
				INNER JOIN {$this->tb_lp_order_items} AS oi ON oi.order_id = p.ID
				INNER JOIN {$this->tb_posts} AS p2 ON p2.ID = oi.item_id
				INNER JOIN {$this->tb_lp_order_itemmeta} AS oim_total ON oim_total.learnpress_order_item_id = oi.order_item_id AND oim_total.meta_key = %s AND CAST( oim_total.meta_value AS DECIMAL(10,2) ) > 0
				WHERE p.post_type = %s AND p.post_status = %s AND oi.item_type = %s {$time} {$where} {$search}
				GROUP BY oi.item_id, p2.post_title
			) AS t",
			'_total',
			LP_ORDER_CPT,
			LP_ORDER_COMPLETED_DB,
			LP_COURSE_CPT
		);

		return (int) $this->wpdb->get_var( $sql );
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
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );
		$time         = $this->time_condition( $type, $value, 'ui.start_time' );

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic %d list is built from absint-normalized IDs.
		$sql = $this->wpdb->prepare(
			"SELECT ui.item_id AS course_id,
				COUNT(*) AS enrolled,
				SUM( ui.status = %s ) AS completed
			FROM {$this->tb_lp_user_items} AS ui
			WHERE ui.item_type = %s AND ui.item_id IN ( {$placeholders} ) {$time}
			GROUP BY ui.item_id",
			'finished',
			LP_COURSE_CPT,
			...$course_ids
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$rows = $this->wpdb->get_results( $sql );
		$map  = array();

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
			return array();
		}

		list( $threshold, $min_attempts ) = self::quiz_alert_config();
		$placeholders                     = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic %d list is built from absint-normalized IDs.
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
			WHERE low_quizzes.course_id IN ( {$placeholders} )
			GROUP BY low_quizzes.course_id",
			LP_QUIZ_CPT,
			'passed',
			'failed',
			$min_attempts,
			'passed',
			$threshold,
			...$course_ids
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$rows = $this->wpdb->get_col( $sql );
		$map  = array();

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
	 * @param int                  $offset Row offset for report-popup pagination.
	 * @param string               $search Optional instructor-name filter.
	 * @return array Rows of { instructor_id, instructor_name, course_count, revenue, enrolled, completed, completion_rate }.
	 */
	public function get_instructor_performance( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 5, int $offset = 0, string $search = '' ): array {
		return $this->compute_instructor_performance( $type, $value, $scope, $limit, $offset, $search );
	}

	/**
	 * Instructor performance query. See get_instructor_performance().
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit
	 * @param int                  $offset
	 * @param string               $search
	 * @return array
	 */
	private function compute_instructor_performance( string $type, string $value, ?StatisticsScope $scope, int $limit, int $offset, string $search ): array {
		if ( ! $type || ! $value ) {
			return array();
		}

		$offset      = max( 0, $offset );
		$time_orders = $this->time_condition( $type, $value, 'o.post_date' );
		$time_items  = $this->time_condition( $type, $value, 'ui.start_time' );
		$list_where  = $this->instructor_list_where( $scope, $search );

		// Revenue and enrollment are pre-aggregated per author in derived tables
		// ( one grouped pass each ) and LEFT JOINed, instead of three correlated
		// subqueries evaluated per instructor row. rev/enr hold at most one row per
		// author, so MAX() over the publish-course group returns that author's
		// value; COALESCE mirrors the old NULL→0 cast. Result is identical.
		$sql = $this->wpdb->prepare(
			"SELECT u.ID AS instructor_id,
				u.display_name AS instructor_name,
				COUNT( DISTINCT p.ID ) AS course_count,
				COALESCE( MAX( rev.revenue ), 0 ) AS revenue,
				COALESCE( MAX( enr.enrolled ), 0 ) AS enrolled,
				COALESCE( MAX( enr.completed ), 0 ) AS completed
			FROM {$this->tb_users} AS u
			INNER JOIN {$this->tb_posts} AS p ON p.post_author = u.ID AND p.post_type = %s AND p.post_status = 'publish'
			LEFT JOIN (
				SELECT pc.post_author AS author_id,
					SUM( CAST( oim.meta_value AS DECIMAL(10,2) ) ) AS revenue
				FROM {$this->tb_lp_order_items} AS oi
				INNER JOIN {$this->tb_posts} AS o ON o.ID = oi.order_id
				INNER JOIN {$this->tb_posts} AS pc ON pc.ID = oi.item_id
				INNER JOIN {$this->tb_lp_order_itemmeta} AS oim ON oim.learnpress_order_item_id = oi.order_item_id AND oim.meta_key = '_total'
				WHERE o.post_type = %s AND o.post_status = %s AND oi.item_type = %s {$time_orders}
				GROUP BY pc.post_author
			) AS rev ON rev.author_id = u.ID
			LEFT JOIN (
				SELECT pc2.post_author AS author_id,
					COUNT(*) AS enrolled,
					SUM( ui.status = 'finished' ) AS completed
				FROM {$this->tb_lp_user_items} AS ui
				INNER JOIN {$this->tb_posts} AS pc2 ON pc2.ID = ui.item_id
				WHERE ui.item_type = %s {$time_items}
				GROUP BY pc2.post_author
			) AS enr ON enr.author_id = u.ID
			WHERE 1=1 {$list_where}
			GROUP BY u.ID, u.display_name
			ORDER BY revenue DESC
			LIMIT %d OFFSET %d",
			LP_COURSE_CPT,
			LP_ORDER_CPT,
			LP_ORDER_COMPLETED_DB,
			LP_COURSE_CPT,
			LP_COURSE_CPT,
			max( 1, $limit ),
			$offset
		);

		$rows = $this->wpdb->get_results( $sql );
		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			function ( $row ) {
				$enrolled  = (int) $row->enrolled;
				$completed = (int) $row->completed;

				return array(
					'instructor_id'   => (int) $row->instructor_id,
					'instructor_name' => (string) $row->instructor_name,
					'course_count'    => (int) $row->course_count,
					'revenue'         => (float) $row->revenue,
					'enrolled'        => $enrolled,
					'completed'       => $completed,
					'completion_rate' => $enrolled > 0 ? round( $completed / $enrolled * 100, 1 ) : null,
				);
			},
			$rows
		);
	}

	/**
	 * Shared WHERE fragment for the instructor list (scope + explicit instructor
	 * + name search) so get_instructor_performance() and its count stay in sync.
	 *
	 * @param StatisticsScope|null $scope
	 * @param string               $search
	 * @return string
	 * @since 4.4.2
	 */
	private function instructor_list_where( ?StatisticsScope $scope, string $search = '' ): string {
		$list_where = $this->scope_condition( $scope, 'p.ID' );

		if ( $scope && $scope->instructor_id > 0 ) {
			$list_where .= $this->wpdb->prepare( ' AND u.ID = %d', $scope->instructor_id );
		}

		$list_where .= $this->search_condition( $search, 'u.display_name' );

		return $list_where;
	}

	/**
	 * Total instructors matching get_instructor_performance()'s filters — the
	 * row total for report-popup pagination.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param string               $search
	 * @return int
	 * @since 4.4.2
	 */
	public function count_instructor_performance( string $type, string $value, ?StatisticsScope $scope = null, string $search = '' ): int {
		if ( ! $type || ! $value ) {
			return 0;
		}

		$list_where = $this->instructor_list_where( $scope, $search );

		$sql = $this->wpdb->prepare(
			"SELECT COUNT( DISTINCT u.ID )
			FROM {$this->tb_users} AS u
			INNER JOIN {$this->tb_posts} AS p ON p.post_author = u.ID AND p.post_type = %s AND p.post_status = 'publish'
			WHERE 1=1 {$list_where}",
			LP_COURSE_CPT
		);

		return (int) $this->wpdb->get_var( $sql );
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
	 * @param int                  $offset Row offset for report-popup pagination.
	 * @param string               $search Optional student-name filter.
	 * @return array Rows of { user_id, name, enrolled, completed, avg_score, last_active, status }.
	 */
	public function get_top_students( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 10, int $offset = 0, string $search = '' ): array {
		if ( ! $type || ! $value ) {
			return array();
		}

		$offset = max( 0, $offset );
		$time   = $this->time_condition( $type, $value, 'ui.start_time' );
		$where  = $this->scope_condition( $scope, 'ui.item_id' );
		$search = $this->search_condition( $search, 'u.display_name' );

		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT ui.user_id,
					u.display_name AS name,
					COUNT(*) AS enrolled,
					SUM( ui.status = %s ) AS completed
				FROM {$this->tb_lp_user_items} AS ui
				INNER JOIN {$this->tb_users} AS u ON u.ID = ui.user_id
				WHERE ui.item_type = %s {$time} {$where} {$search}
				GROUP BY ui.user_id, u.display_name
				ORDER BY enrolled DESC, completed DESC
				LIMIT %d OFFSET %d",
				'finished',
				LP_COURSE_CPT,
				max( 1, $limit ),
				$offset
			)
		);

		if ( ! is_array( $rows ) || ! $rows ) {
			return array();
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
				...array_merge( array( LP_LESSON_CPT, LP_QUIZ_CPT ), $user_ids )
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
				...array_merge( array( 'passed', LP_QUIZ_CPT, 'passed', 'failed' ), $user_ids )
			)
		);
		$avg_score = array_column( (array) $score_rows, 'avg_score', 'user_id' );

		$rules = apply_filters(
			'learn-press/statistics/student-status-rules',
			array(
				'active_days'   => 7,
				'at_risk_ratio' => 2,
			)
		);
		$now   = current_time( 'mysql' );

		return array_map(
			function ( $row ) use ( $last_active, $avg_score, $rules, $now ) {
				$user_id = (int) $row->user_id;
				$active  = $last_active[ $user_id ] ?? null;

				return array(
					'user_id'     => $user_id,
					'name'        => (string) $row->name,
					'enrolled'    => (int) $row->enrolled,
					'completed'   => (int) $row->completed,
					'avg_score'   => isset( $avg_score[ $user_id ] ) ? round( (float) $avg_score[ $user_id ], 1 ) : null,
					'last_active' => $active,
					'status'      => self::student_status( (int) $row->enrolled, (int) $row->completed, $active, $rules, $now ),
				);
			},
			$rows
		);
	}

	/**
	 * Total distinct students matching get_top_students()'s filters — the row
	 * total for report-popup pagination.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param string               $search
	 * @return int
	 * @since 4.4.2
	 */
	public function count_top_students( string $type, string $value, ?StatisticsScope $scope = null, string $search = '' ): int {
		if ( ! $type || ! $value ) {
			return 0;
		}

		$time   = $this->time_condition( $type, $value, 'ui.start_time' );
		$where  = $this->scope_condition( $scope, 'ui.item_id' );
		$search = $this->search_condition( $search, 'u.display_name' );
		$join   = '' !== $search ? "INNER JOIN {$this->tb_users} AS u ON u.ID = ui.user_id" : '';

		$sql = $this->wpdb->prepare(
			"SELECT COUNT( DISTINCT ui.user_id )
			FROM {$this->tb_lp_user_items} AS ui
			{$join}
			WHERE ui.item_type = %s {$time} {$where} {$search}",
			LP_COURSE_CPT
		);

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Courses ranked by students in the range, with started/active-7d
	 * conditional sums — one GROUP BY, no per-row queries.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit
	 * @param int                  $offset Row offset for report-popup pagination.
	 * @param string               $search Optional course-title filter.
	 * @return array Rows of { course_id, name, enrolled, started, completed, completion_rate, active_7d }.
	 */
	public function get_courses_by_students( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 10, int $offset = 0, string $search = '' ): array {
		if ( ! $type || ! $value ) {
			return array();
		}

		$offset = max( 0, $offset );
		$time   = $this->time_condition( $type, $value, 'ui.start_time' );
		$where  = $this->scope_condition( $scope, 'ui.item_id' );
		$search = $this->search_condition( $search, 'p.post_title' );
		// active_in_period counts enrollments whose child activity ( lesson/quiz
		// start_time ) falls in the SELECTED period, not a fixed last-7-days window.
		$time_child = $this->time_condition( $type, $value, 'c.start_time' );

		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT ui.item_id AS course_id,
					p.post_title AS name,
					COUNT(*) AS enrolled,
					SUM( ui.status = %s ) AS completed,
					SUM( ch.pid IS NOT NULL ) AS started,
					SUM( ch.in_period = 1 ) AS active_in_period
				FROM {$this->tb_lp_user_items} AS ui
				INNER JOIN {$this->tb_posts} AS p ON p.ID = ui.item_id
				LEFT JOIN (
					SELECT c.parent_id AS pid,
						MAX( ( 0 = 0 {$time_child} ) ) AS in_period
					FROM {$this->tb_lp_user_items} AS c
					WHERE c.item_type IN ( %s, %s )
					GROUP BY c.parent_id
				) AS ch ON ch.pid = ui.user_item_id
				WHERE ui.item_type = %s {$time} {$where} {$search}
				GROUP BY ui.item_id, p.post_title
				ORDER BY enrolled DESC
				LIMIT %d OFFSET %d",
				'finished',
				LP_LESSON_CPT,
				LP_QUIZ_CPT,
				LP_COURSE_CPT,
				max( 1, $limit ),
				$offset
			)
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			function ( $row ) {
				$enrolled  = (int) $row->enrolled;
				$completed = (int) $row->completed;

				return array(
					'course_id'        => (int) $row->course_id,
					'name'             => (string) $row->name,
					'enrolled'         => $enrolled,
					'started'          => (int) $row->started,
					'completed'        => $completed,
					'completion_rate'  => $enrolled > 0 ? round( $completed / $enrolled * 100, 1 ) : null,
					'active_in_period' => (int) $row->active_in_period,
				);
			},
			$rows
		);
	}

	/**
	 * Total distinct courses matching get_courses_by_students()'s filters — the
	 * row total for report-popup pagination.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param string               $search
	 * @return int
	 * @since 4.4.2
	 */
	public function count_courses_by_students( string $type, string $value, ?StatisticsScope $scope = null, string $search = '' ): int {
		if ( ! $type || ! $value ) {
			return 0;
		}

		$time   = $this->time_condition( $type, $value, 'ui.start_time' );
		$where  = $this->scope_condition( $scope, 'ui.item_id' );
		$search = $this->search_condition( $search, 'p.post_title' );
		$join   = '' !== $search ? "INNER JOIN {$this->tb_posts} AS p ON p.ID = ui.item_id" : '';

		$sql = $this->wpdb->prepare(
			"SELECT COUNT( DISTINCT ui.item_id )
			FROM {$this->tb_lp_user_items} AS ui
			{$join}
			WHERE ui.item_type = %s {$time} {$where} {$search}",
			LP_COURSE_CPT
		);

		return (int) $this->wpdb->get_var( $sql );
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
		return $this->compute_course_watchlist( $type, $value, $scope, $limit );
	}

	/**
	 * Quiz low-pass alert config ( threshold %, minimum attempts ) — read in one
	 * place so query methods stay in sync.
	 *
	 * @return array [ float threshold, int min_attempts ]
	 * @since 4.4.2
	 */
	private static function quiz_alert_config(): array {
		return array(
			(float) apply_filters( 'learn-press/statistics/quiz-pass-alert', 50 ),
			(int) apply_filters( 'learn-press/statistics/quiz-pass-alert-min-attempts', 5 ),
		);
	}

	/**
	 * Watchlist query. See get_course_watchlist().
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit
	 * @return array
	 */
	private function compute_course_watchlist( string $type, string $value, ?StatisticsScope $scope, int $limit ): array {
		if ( ! $type || ! $value ) {
			return array();
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
			return array();
		}

		$course_ids   = array_map(
			function ( $row ) {
				return (int) $row->course_id;
			},
			$rows
		);
		$low_quiz_map = $this->get_low_quiz_course_map( $course_ids );
		$bands        = (array) apply_filters( 'learn-press/statistics/risk-bands', array( 40, 55 ) );

		return array_map(
			function ( $row ) use ( $low_quiz_map, $bands ) {
				$enrolled        = (int) $row->enrolled;
				$completion_rate = $enrolled > 0 ? round( (int) $row->completed / $enrolled * 100, 1 ) : null;
				$has_curriculum  = (bool) $row->has_curriculum;
				$has_low_quiz    = ! empty( $low_quiz_map[ (int) $row->course_id ] );
				$risk            = self::watchlist_risk( $completion_rate, $bands );
				$action          = self::watchlist_action( $risk, $has_curriculum, $has_low_quiz );

				return array(
					'course_id'       => (int) $row->course_id,
					'name'            => (string) $row->name,
					'instructor'      => (string) $row->instructor,
					'completion_rate' => $completion_rate,
					'risk'            => $risk,
					// Per-row override point for gateway/add-on rules.
					'action'          => (string) apply_filters( 'learn-press/statistics/watchlist-actions', $action, $row, $risk ),
				);
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
			return array();
		}

		return array_map(
			function ( $row ) {
				return array(
					'instructor_id' => (int) $row->instructor_id,
					'name'          => (string) $row->name,
					'pending'       => (int) $row->pending,
				);
			},
			$rows
		);
	}
}
