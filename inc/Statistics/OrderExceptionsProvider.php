<?php
/**
 * Class OrderExceptionsProvider
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
 * Recent failed/cancelled order rows for the Orders statistics dashboard.
 *
 * LearnPress does not store structured gateway failure reasons, so v1 reports
 * payment method + order status and exposes a filter for gateway/add-on
 * enrichment.
 *
 * @since 4.4.2
 */
class OrderExceptionsProvider extends LP_Database {
	/**
	 * @var OrderExceptionsProvider
	 */
	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	/**
	 * @return OrderExceptionsProvider
	 */
	public static function getInstance(): OrderExceptionsProvider {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * @param string $type
	 * @param string $value
	 * @param string $time_field
	 * @return string
	 */
	private function time_condition( string $type, string $value, string $time_field ): string {
		$filter = new LP_Filter();
		$filter = LP_Statistics_DB::getInstance()->filter_time( $filter, $type, $time_field, $value );

		return $filter->where[0] ?? '';
	}

	/**
	 * @param StatisticsScope|null $scope
	 * @return string
	 */
	private function scope_condition( ?StatisticsScope $scope ): string {
		if ( ! $scope || $scope->is_empty() ) {
			return '';
		}

		$filter = new LP_Filter();
		$scope->apply_to_orders( $filter, 'p.ID' );

		return implode( ' ', $filter->where );
	}

	/**
	 * WHERE fragment pinning the exception status. Empty/other → both
	 * failed+cancelled (the tab's default); 'failed'/'cancelled' → just that one
	 * (Orders tab deep-link). No '%' in values, safe to interpolate.
	 *
	 * @param string $status
	 * @return string
	 * @since 4.4.2
	 */
	private function status_condition( string $status ): string {
		if ( 'failed' === $status ) {
			return $this->wpdb->prepare( 'p.post_status = %s', LP_ORDER_FAILED_DB );
		}

		if ( 'cancelled' === $status ) {
			return $this->wpdb->prepare( 'p.post_status = %s', LP_ORDER_CANCELLED_DB );
		}

		return $this->wpdb->prepare( 'p.post_status IN ( %s, %s )', LP_ORDER_FAILED_DB, LP_ORDER_CANCELLED_DB );
	}

	/**
	 * HAVING fragment matching the popup search across course/payment/status.
	 * Empty → no condition. '%' are doubled to survive interpolation into the
	 * outer wpdb::prepare() (see DashboardStatisticsDB::search_condition()).
	 *
	 * @param string $search
	 * @return string
	 * @since 4.4.2
	 */
	private function search_having( string $search ): string {
		$search = trim( $search );
		if ( '' === $search ) {
			return '';
		}

		$like     = '%' . $this->wpdb->esc_like( $search ) . '%';
		$fragment = $this->wpdb->prepare(
			' HAVING ( course LIKE %s OR payment_method_title LIKE %s OR status LIKE %s )',
			$like,
			$like,
			$like
		);

		return str_replace( '%', '%%', $fragment );
	}

	/**
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param int                  $limit
	 * @param int                  $offset Row offset for report-popup pagination.
	 * @param string               $search Optional course/payment/status filter.
	 * @param string               $status Restrict to a single status ( failed|cancelled ).
	 * @return array
	 */
	public function get_exceptions( string $type, string $value, ?StatisticsScope $scope = null, int $limit = 20, int $offset = 0, string $search = '', string $status = '' ): array {
		if ( ! $type || ! $value ) {
			return [];
		}

		$limit      = min( 500, max( 1, $limit ) );
		$offset     = max( 0, $offset );
		$time       = $this->time_condition( $type, $value, 'p.post_date' );
		$scope_sql  = $this->scope_condition( $scope );
		$status_sql = $this->status_condition( $status );
		$having     = $this->search_having( $search );

		$sql = $this->wpdb->prepare(
			"SELECT p.ID AS order_id,
				p.post_date AS date,
				REPLACE( p.post_status, 'lp-', '' ) AS status,
				payment_meta.meta_value AS payment_method_title,
				total_meta.meta_value AS total,
				user_meta.meta_value AS user_value,
				GROUP_CONCAT( DISTINCT course_posts.post_title ORDER BY course_posts.post_title SEPARATOR ', ' ) AS course
			FROM {$this->tb_posts} AS p
			LEFT JOIN {$this->tb_postmeta} AS payment_meta ON payment_meta.post_id = p.ID AND payment_meta.meta_key = %s
			LEFT JOIN {$this->tb_postmeta} AS total_meta ON total_meta.post_id = p.ID AND total_meta.meta_key = %s
			LEFT JOIN {$this->tb_postmeta} AS user_meta ON user_meta.post_id = p.ID AND user_meta.meta_key = %s
			LEFT JOIN {$this->tb_lp_order_items} AS oi ON oi.order_id = p.ID AND oi.item_type = %s
			LEFT JOIN {$this->tb_posts} AS course_posts ON course_posts.ID = oi.item_id
			WHERE p.post_type = %s AND {$status_sql} {$time} {$scope_sql}
			GROUP BY p.ID, p.post_date, p.post_status, payment_meta.meta_value, total_meta.meta_value, user_meta.meta_value
			{$having}
			ORDER BY p.post_date DESC, p.ID DESC
			LIMIT %d OFFSET %d",
			'_payment_method_title',
			'_order_total',
			'_user_id',
			LP_COURSE_CPT,
			LP_ORDER_CPT,
			$limit,
			$offset
		);

		$rows = $this->wpdb->get_results( $sql );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		return array_map( [ $this, 'format_exception_row' ], $rows );
	}

	/**
	 * Total exception rows matching get_exceptions()'s filters — the row total
	 * for report-popup pagination.
	 *
	 * @param string               $type
	 * @param string               $value
	 * @param StatisticsScope|null $scope
	 * @param string               $search
	 * @param string               $status
	 * @return int
	 * @since 4.4.2
	 */
	public function count_exceptions( string $type, string $value, ?StatisticsScope $scope = null, string $search = '', string $status = '' ): int {
		if ( ! $type || ! $value ) {
			return 0;
		}

		$time       = $this->time_condition( $type, $value, 'p.post_date' );
		$scope_sql  = $this->scope_condition( $scope );
		$status_sql = $this->status_condition( $status );
		$having     = $this->search_having( $search );

		$sql = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM (
				SELECT p.ID,
					REPLACE( p.post_status, 'lp-', '' ) AS status,
					payment_meta.meta_value AS payment_method_title,
					GROUP_CONCAT( DISTINCT course_posts.post_title SEPARATOR ', ' ) AS course
				FROM {$this->tb_posts} AS p
				LEFT JOIN {$this->tb_postmeta} AS payment_meta ON payment_meta.post_id = p.ID AND payment_meta.meta_key = %s
				LEFT JOIN {$this->tb_lp_order_items} AS oi ON oi.order_id = p.ID AND oi.item_type = %s
				LEFT JOIN {$this->tb_posts} AS course_posts ON course_posts.ID = oi.item_id
				WHERE p.post_type = %s AND {$status_sql} {$time} {$scope_sql}
				GROUP BY p.ID, p.post_status, payment_meta.meta_value
				{$having}
			) AS t",
			'_payment_method_title',
			LP_COURSE_CPT,
			LP_ORDER_CPT
		);

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Pure issue text builder.
	 *
	 * @param string $payment_method
	 * @param string $status
	 * @return string
	 */
	public static function issue_text( string $payment_method, string $status ): string {
		$payment_method = '' !== $payment_method ? $payment_method : __( 'Unknown payment method', 'learnpress' );

		return sprintf( '%1$s - %2$s', $payment_method, $status );
	}

	/**
	 * Pure severity bucket mapper.
	 *
	 * @param string $status failed|cancelled.
	 * @param float  $total
	 * @return string high|medium|low
	 */
	public static function severity( string $status, float $total ): string {
		if ( 'failed' === $status && $total > 0 ) {
			return 'high';
		}

		if ( 'cancelled' === $status && $total > 0 ) {
			return 'medium';
		}

		return 'low';
	}

	/**
	 * @param object $row
	 * @return array
	 */
	private function format_exception_row( object $row ): array {
		$order_id       = (int) $row->order_id;
		$status         = sanitize_key( (string) $row->status );
		$total          = (float) ( $row->total ?? 0 );
		$payment_method = sanitize_text_field( (string) ( $row->payment_method_title ?? '' ) );

		$defaults = [
			'order_id'  => $order_id,
			'edit_link' => get_edit_post_link( $order_id, 'raw' ),
			'student'   => $this->get_student_name( $row->user_value ?? '' ),
			'course'    => (string) ( $row->course ?? '' ),
			'status'    => $status,
			'issue'     => self::issue_text( $payment_method, $status ),
			'date'      => mysql2date( 'Y-m-d H:i:s', (string) $row->date ),
			'severity'  => self::severity( $status, $total ),
		];

		$filtered = apply_filters( 'learn-press/statistics/order-exception-data', $defaults, $order_id );
		$filtered = wp_parse_args( (array) $filtered, $defaults );

		return [
			'order_id'  => (int) $filtered['order_id'],
			'edit_link' => esc_url_raw( (string) $filtered['edit_link'] ),
			'student'   => sanitize_text_field( (string) $filtered['student'] ),
			'course'    => sanitize_text_field( (string) $filtered['course'] ),
			'status'    => sanitize_key( (string) $filtered['status'] ),
			'issue'     => sanitize_text_field( (string) $filtered['issue'] ),
			'date'      => sanitize_text_field( (string) $filtered['date'] ),
			'severity'  => sanitize_key( (string) $filtered['severity'] ),
		];
	}

	/**
	 * @param mixed $raw_user_value
	 * @return string
	 */
	private function get_student_name( $raw_user_value ): string {
		$user_ids = maybe_unserialize( $raw_user_value );
		$user_ids = is_array( $user_ids ) ? $user_ids : [ $user_ids ];
		$user_id  = absint( reset( $user_ids ) );

		if ( ! $user_id ) {
			return '';
		}

		$user = get_userdata( $user_id );

		return $user ? (string) $user->display_name : '';
	}
}
