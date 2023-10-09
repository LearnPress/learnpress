<?php
/**
 * Class LP_Order_DB
 *
 * @author tungnx
 * @since 4.1.4
 */

defined( 'ABSPATH' ) || exit();

class LP_Order_DB extends LP_Database {
	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get the latest LP Order id by user_id and course_id
	 *
	 * @param int|string $user_id LP_User is int, LP_User_Guest is string
	 * @param int $course_id
	 *
	 * @return null|string
	 * @since 4.1.4
	 * @author tungnx
	 * @version 1.0.1
	 */
	public function get_last_lp_order_id_of_user_course( $user_id, int $course_id ) {
		$key_cache = "lp/order/id/last/$user_id/$course_id";
		$order_id  = LP_Cache::cache_load_first( 'get', $key_cache );
		if ( false !== $order_id ) {
			return $order_id;
		}

		if ( ! $user_id || ! $course_id ) {
			return null;
		}

		$user_id_str = $this->wpdb->prepare( '%"%d"%', $user_id );

		$query = $this->wpdb->prepare(
			"SELECT p.ID FROM {$this->tb_posts} as p
			INNER join {$this->tb_postmeta} pm on p.ID = pm.post_id
			INNER join {$this->tb_lp_order_items} as oi on p.ID = oi.order_id
			INNER join {$this->tb_lp_order_itemmeta} as oim on oim.learnpress_order_item_id = oi.order_item_id
			WHERE post_type = %s
			AND pm.meta_key = %s
			AND (pm.meta_value = %s OR pm.meta_value LIKE '%s')
			AND oim.meta_key = %s
			AND oim.meta_value = %d
			ORDER BY p.ID DESC
			LIMIT 1
			",
			LP_ORDER_CPT,
			'_user_id',
			$user_id,
			$user_id_str,
			'_course_id',
			$course_id
		);

		$order_id = $this->wpdb->get_var( $query );

		LP_Cache::cache_load_first( 'set', $key_cache, $order_id );

		return $order_id;
	}

	/**
	 * Get order_item_ids by order_id
	 *
	 * @param int $order_id
	 * @author tungnx
	 * @since 4.1.4
	 * @version 1.0.0
	 * @return array
	 */
	public function get_order_item_ids( int $order_id ): array {
		$query = $this->wpdb->prepare(
			"SELECT order_item_id FROM $this->tb_lp_order_items
			WHERE order_id = %d
			",
			$order_id
		);

		return $this->wpdb->get_col( $query );
	}

	/**
	 * Delete row IN order_item_ids
	 *
	 * @param LP_Order_Filter $filter
	 * @author tungnx
	 * @since 4.1.4
	 * @version 1.0.0
	 * @return bool|int
	 * @throws Exception
	 */
	public function delete_order_item( LP_Order_Filter $filter ) {
		// Check valid user.
		if ( ! current_user_can( 'administrator' ) ) {
			throw new Exception( __( 'Invalid user!', 'learnpress' ) );
		}

		if ( empty( $filter->order_item_ids ) ) {
			return 1;
		}

		$where = 'WHERE 1=1 ';

		$where .= $this->wpdb->prepare(
			'AND order_item_id IN(' . LP_Helper::db_format_array( $filter->order_item_ids, '%d' ) . ')',
			$filter->order_item_ids
		);

		return $this->wpdb->query(
			"DELETE FROM {$this->tb_lp_order_items}
			{$where}
			"
		);
	}

	/**
	 * Delete row IN order_item_ids
	 *
	 * @param LP_Order_Filter $filter
	 * @author tungnx
	 * @since 4.1.4
	 * @version 1.0.0
	 * @return bool|int
	 * @throws Exception
	 */
	public function delete_order_itemmeta( LP_Order_Filter $filter ) {
		// Check valid user.
		if ( ! current_user_can( 'administrator' ) ) {
			throw new Exception( __( 'Invalid user!', 'learnpress' ) );
		}

		if ( empty( $filter->order_item_ids ) ) {
			return 1;
		}

		$where = 'WHERE 1=1 ';

		$where .= $this->wpdb->prepare(
			'AND learnpress_order_item_id IN(' . LP_Helper::db_format_array( $filter->order_item_ids, '%d' ) . ')',
			$filter->order_item_ids
		);

		return $this->wpdb->query(
			"DELETE FROM {$this->tb_lp_order_itemmeta}
			{$where}
			"
		);
	}

	public function chart_filter_date_group_by( LP_Order_Filter $filter ) {
		$filter->only_fields[] = 'HOUR(p.post_date) as order_time';
		$filter->group_by      = 'order_time';
		return $filter;
	}

	public function chart_filter_previous_days_group_by( LP_Order_Filter $filter ) {
		$filter->only_fields[] = 'CAST(p.post_date AS DATE) as order_time';
		$filter->group_by      = 'order_time';
		return $filter;
	}

	public function chart_filter_month_group_by( LP_Order_Filter $filter ) {
		$filter->only_fields[] = 'DAY(p.post_date) as order_time';
		$filter->group_by      = 'order_time';
		return $filter;
	}

	public function chart_filter_previous_months_group_by( LP_Order_Filter $filter ) {
		$filter->only_fields[] = 'DATE_FORMAT( p.post_date , "%b-%Y") as order_time';
		$filter->group_by      = 'order_time';
		return $filter;
	}

	public function chart_filter_year_group_by( LP_Order_Filter $filter ) {
		$filter->only_fields[] = 'MONTH(p.post_date) as order_time';
		$filter->group_by      = 'order_time';
		return $filter;
	}

	public function date_filter( LP_Order_Filter $filter, string $date ) {
		$filter->where[] = $this->wpdb->prepare( 'AND cast( p.post_date as DATE)= cast(%s as DATE)', $date );
		return $filter;
	}

	public function previous_days_filter( LP_Order_Filter $filter, int $value ) {
		if ( $value < 2 ) {
			throw new Exception( 'Day must be greater than 2 days.', 'learnpress' );
		}
		$filter->where[] = $this->wpdb->prepare( 'AND p.post_date >= DATE_ADD(CURDATE(), INTERVAL -%d DAY)', $value );
		return $filter;
	}

	public function month_filter( LP_Order_Filter $filter, string $date ) {
		$filter->where[] = $this->wpdb->prepare( 'AND EXTRACT(YEAR_MONTH FROM p.post_date)= EXTRACT(YEAR_MONTH FROM %s)', $date );
		return $filter;
	}

	public function previous_months_filter( LP_Order_Filter $filter, int $value ) {
		if ( $value < 2 ) {
			throw new Exception( 'Values must be greater than 2 months.', 'learnpress' );
		}
		$filter->where[] = $this->wpdb->prepare( 'AND EXTRACT(YEAR_MONTH FROM p.post_date) >= EXTRACT(YEAR_MONTH FROM DATE_ADD(CURDATE(), INTERVAL -%d MONTH))', $value );
		return $filter;
	}

	public function year_filter( LP_Order_Filter $filter, string $date ) {
		$filter->where[] = $this->wpdb->prepare( 'AND YEAR(p.post_date)= YEAR(%s)', $date );
		return $filter;
	}

	public function get_net_sales_data( string $type, string $value ) {
		$filter                   = new LP_Order_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';
		$oi_table                 = $this->tb_lp_order_items;
		$oim_table                = $this->tb_lp_order_itemmeta;
		$filter->only_fields[]    = 'SUM(oim.meta_value) as net_sales';
		$filter->join             = [
			"INNER JOIN $oi_table AS oi ON p.ID = oi.order_id",
			"INNER JOIN $oim_table AS oim ON oi.order_item_id = oim.learnpress_order_item_id",
		];
		$filter->limit            = -1;
		$filter->where            = [
			$this->wpdb->prepare( 'AND p.post_type=%s', $filter->post_type ),
			$this->wpdb->prepare( 'AND p.post_status=%s', LP_ORDER_COMPLETED_DB ),
			$this->wpdb->prepare( 'AND oim.meta_key=%s', '_total' ),
		];
		$filter                   = $this->filter_time( $filter, $type, $value );
		$filter                   = $this->chart_filter_group_by( $filter, $type );
		$filter->order_by         = 'p.post_date';
		$filter->order            = 'asc';
		$filter->run_query_count  = false;
		$result                   = $this->execute( $filter );
		// error_log( $this->check_execute_has_error() );
		return $result;
	}

	/**
	 * [get_completed_order_data use this for complete order report chart]
	 * @param  string $type  [time type filter: date|month|year|previous_days|custom]
	 * @param  string $value [time value ]
	 * @return [array]        []
	 */
	public function get_completed_order_data( string $type, string $value ) {
		// $date                     = date( 'Y-m-d', strtotime( $date ) );
		$filter                   = new LP_Order_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';

		$filter->only_fields[] = 'count( p.ID) as count_order';
		$filter                = $this->filter_time( $filter, $type, $value );
		$filter                = $this->chart_filter_group_by( $filter, $type );

		$filter->where[] = $this->wpdb->prepare( 'AND p.post_status=%s', LP_ORDER_COMPLETED_DB );
		$filter->limit   = -1;

		$filter->order_by = 'p.post_date';
		$filter->order    = 'asc';

		$filter->run_query_count = false;

		$result = $this->execute( $filter );
		return $result;
	}
	public function filter_time( LP_Order_Filter $filter, string $type, string $value ) {
		switch ( $type ) {
			case 'date':
				$filter = $this->date_filter( $filter, $value );
				break;
			case 'month':
				$filter = $this->month_filter( $filter, $value );
				break;
			case 'year':
				$filter = $this->year_filter( $filter, $value );
				break;
			case 'previous_days':
				$filter = $this->previous_days_filter( $filter, (int) $value );
				break;
			case 'previous_months':
				$filter = $this->previous_months_filter( $filter, (int) $value );
				break;
			default:
				// code...
				break;
		}
		return $filter;
	}
	public function chart_filter_group_by( LP_Order_Filter $filter, string $type ) {
		switch ( $type ) {
			case 'date':
				$filter = $this->chart_filter_date_group_by( $filter );
				break;
			case 'month':
				$filter = $this->chart_filter_month_group_by( $filter );
				break;
			case 'year':
				$filter = $this->chart_filter_year_group_by( $filter );
				break;
			case 'previous_days':
				$filter = $this->chart_filter_previous_days_group_by( $filter );
				break;
			case 'previous_months':
				$filter = $this->chart_filter_previous_months_group_by( $filter );
				break;
			default:
				// code...
				break;
		}
		return $filter;
	}


	public function filter_order_count_statics( LP_Order_Filter $filter ) {
		// $filter->query_count = true;
		$filter->only_fields[]   = 'count( p.ID) as count_order';
		$filter->only_fields[]   = 'p.post_status';
		$filter->group_by        = 'p.post_status';
		$filter->where[]         = $this->wpdb->prepare( 'AND p.post_status LIKE CONCAT(%s,"%")', 'lp-' );
		$filter->run_query_count = false;

		return $filter;
	}

	public function get_order_statics( string $type, $value ) {
		$filter                   = new LP_Order_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';
		$filter                   = $this->filter_time( $filter, $type, $value );
		$filter                   = $this->filter_order_count_statics( $filter );
		$filter->limit            = -1;
		$result                   = $this->execute( $filter );
		return $result;
	}
}
