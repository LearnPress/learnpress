<?php
/**
 * Class LP_Statistics_DB
 *
 * @author thimpress
 * @since 4.2.6
 */

defined( 'ABSPATH' ) || exit();

class LP_Statistics_DB extends LP_Database {
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
	 * filter to get data for chart of a day.
	 * @param  LP_Filter $filter
	 * @param  string    $time_field the column use to filter time
	 * @return LP_Filter
	 */
	public function chart_filter_date_group_by( LP_Filter $filter, string $time_field ) {
		$filter->only_fields[] = "HOUR($time_field) as x_data_label";
		$filter->group_by      = 'x_data_label';
		return $filter;
	}
	/**
	 * filter to get data for chart of last some days. ex: last 7 days, last 30 days,...
	 * @param  LP_Filter $filter
	 * @param  string    $time_field the column use to filter time
	 * @return LP_Filter
	 */
	public function chart_filter_previous_days_group_by( LP_Filter $filter, string $time_field ) {
		$filter->only_fields[] = "CAST($time_field AS DATE) as x_data_label";
		$filter->group_by      = 'x_data_label';
		return $filter;
	}
	/**
	 * filter to get data for chart of a month
	 * @param  LP_Filter $filter
	 * @param  string    $time_field the column use to filter time
	 * @return LP_Filter
	 */
	public function chart_filter_month_group_by( LP_Filter $filter, string $time_field ) {
		$filter->only_fields[] = "DAY($time_field) as x_data_label";
		$filter->group_by      = 'x_data_label';
		return $filter;
	}
	/**
	 * filter to get data for chart of months. ex: last 3 months, 6 months, 9 months,...
	 * @param  LP_Filter $filter
	 * @param  string    $time_field the column use to filter time
	 * @return LP_Filter
	 */
	public function chart_filter_previous_months_group_by( LP_Filter $filter, string $time_field ) {
		$filter->only_fields[] = "DATE_FORMAT( $time_field , '%m-%Y') as x_data_label";
		$filter->group_by      = 'x_data_label';
		return $filter;
	}
	/**
	 * filter to get data for chart of a year
	 * @param  LP_Filter $filter
	 * @param  string    $time_field the column use to filter time. ex: post_date with posts table, user_registered on users table
	 * @return LP_Filter
	 */
	public function chart_filter_year_group_by( LP_Filter $filter, string $time_field ) {
		$filter->only_fields[] = "MONTH($time_field) as x_data_label";
		$filter->group_by      = 'x_data_label';
		return $filter;
	}
	/**
	 * filter to get data for chart of a custom date ranges
	 * @param  LP_Filter $filter
	 * @param  array     $dates array of date range use to filer
	 * @param  string    $time_field the column use to filter time. ex: post_date with posts table, user_registered on users table
	 * @return LP_Filter
	 */
	public function chart_filter_custom_group_by( LP_Filter $filter, array $dates, string $time_field ) {
		$diff1 = date_create( $dates[0] );
		$diff2 = date_create( $dates[1] );
		if ( ! $diff1 || ! $diff2 ) {
			throw new Exception( 'Custom filter date is invalid.', 'learnpress' );
		}
		$diff = date_diff( $diff1, $diff2, true );
		$y    = $diff->y;
		$m    = $diff->m;
		$d    = $diff->d;
		if ( $y < 1 ) {
			if ( $m <= 1 ) {
				if ( $d < 1 ) {
					$filter = $this->chart_filter_date_group_by( $filter, $time_field );
				} else {
					// more thans 2 days return data of days
					$filter = $this->chart_filter_previous_days_group_by( $filter, $time_field );
				}
			} else {
				// more thans 2 months return data of months
				$filter = $this->chart_filter_previous_months_group_by( $filter, $time_field );
			}
		} elseif ( $y < 2 ) {
			// less thans 2 years return data of year months
			$filter = $this->chart_filter_previous_months_group_by( $filter, $time_field );
		} elseif ( $y < 5 ) {
			// from 2-5years return data of year quarters
			$filter->only_fields[] = $this->wpdb->prepare( "CONCAT( %s, QUARTER($time_field) ,%s, Year($time_field)) as x_data_label", [ 'q', '-' ] );
			$filter->group_by      = 'x_data_label';
		} else {
			// more than 5 years, return data of years
			$filter->only_fields[] = "YEAR($time_field) as x_data_label";
			$filter->group_by      = 'x_data_label';
		}
		return $filter;
	}
	/**
	 * @param  LP_Filter $filter
	 * @param  string    $date       choose a date to query, format Y-m-d
	 * @param  string    $time_field $time_field the column use to filter time. ex: post_date with posts table, user_registered on
	 * @return LP_Filter
	 */
	public function date_filter( LP_Filter $filter, string $date, string $time_field, $is_until = false ) {
		if ( $is_until ) {
			$filter->where[] = $this->wpdb->prepare( "AND cast( $time_field as DATE)<= cast(%s as DATE)", $date );
		} else {
			$filter->where[] = $this->wpdb->prepare( "AND cast( $time_field as DATE)= cast(%s as DATE)", $date );
		}
		return $filter;
	}
	/**
	 * @param  LP_Filter $filter
	 * @param  int       $value      ex: 7 - last 7 days, 10 - last 10 days, ...
	 * @param  string    $time_field $time_field the column use to filter time. ex: post_date with posts table, user_registered on
	 * @return LP_Filter
	 */
	public function previous_days_filter( LP_Filter $filter, int $value, string $time_field, $is_until = false ) {
		if ( $value < 2 ) {
			throw new Exception( 'Day must be greater than 2 days.', 'learnpress' );
		}
		if ( $is_until ) {
			$filter->where[] = $this->wpdb->prepare( "AND $time_field <= CURDATE()" );
		} else {
			$filter->where[] = $this->wpdb->prepare( "AND $time_field >= DATE_ADD(CURDATE(), INTERVAL -%d DAY)", $value );
		}

		return $filter;
	}
	/**
	 * @param  LP_Filter $filter
	 * @param  string    $date       choose a date to query, format Y-m-d
	 * @param  string    $time_field $time_field the column use to filter time. ex: post_date with posts table, user_registered on
	 * @return LP_Filter
	 */
	public function month_filter( LP_Filter $filter, string $date, string $time_field, $is_until = false ) {
		if ( $is_until ) {
			$filter->where[] = $this->wpdb->prepare( "AND cast( $time_field as DATE)<= cast(%s as DATE)", $date );
		} else {
			$filter->where[] = $this->wpdb->prepare( "AND EXTRACT(YEAR_MONTH FROM $time_field)= EXTRACT(YEAR_MONTH FROM %s)", $date );
		}
		return $filter;
	}
	/**
	 * @param  LP_Filter $filter
	 * @param  int       $value      ex: 3 - last 3 months, 10 - last 10 months, ...
	 * @param  string    $time_field $time_field the column use to filter time. ex: post_date with posts table, user_registered on
	 * @return LP_Filter
	 */
	public function previous_months_filter( LP_Filter $filter, int $value, string $time_field, $is_until = false ) {
		if ( $value < 2 ) {
			throw new Exception( 'Values must be greater than 2 months.', 'learnpress' );
		}
		if ( $is_until ) {
			$filter->where[] = $this->wpdb->prepare( "AND $time_field <= CURDATE()" );
		} else {
			$filter->where[] = $this->wpdb->prepare( "AND EXTRACT(YEAR_MONTH FROM $time_field) >= EXTRACT(YEAR_MONTH FROM DATE_ADD(CURDATE(), INTERVAL -%d MONTH))", $value );
		}
		return $filter;
	}
	/**
	 * get data for each month in year
	 * @param  LP_Filter $filter
	 * @param  string    $date       choose a date to query, format Y-m-d
	 * @param  string    $time_field $time_field the column use to filter time. ex: post_date with posts table, user_registered on
	 * @return LP_Filter
	 */
	public function year_filter( LP_Filter $filter, string $date, string $time_field, $is_until = false ) {
		if ( $is_until ) {
			$filter->where[] = $this->wpdb->prepare( "AND cast( $time_field as DATE) <= cast(%s as DATE)", $date );
		} else {
			$filter->where[] = $this->wpdb->prepare( "AND YEAR($time_field)= YEAR(%s)", $date );
		}
		return $filter;
	}
	/**
	 * custom query with data range
	 * @param  LP_Filter $filter
	 * @param  array     $dates      date ranges, array of 2 dates.
	 * @param  string    $time_field $time_field the column use to filter time. ex: post_date with posts table, user_registered on
	 * @return LP_Filter
	 */
	public function custom_time_filter( LP_Filter $filter, array $dates, string $time_field, $is_until = false ) {
		if ( empty( $dates ) ) {
			throw new Exception( 'Select date', 'learnpress' );
		}
		sort( $dates );
		if ( $is_until ) {
			$filter->where[] = $this->wpdb->prepare( "AND cast( $time_field as DATE) <= cast(%s as DATE)", $dates[1] );
		} else {
			$filter->where[] = $this->wpdb->prepare(
				"AND (DATE($time_field) BETWEEN %s AND %s)",
				date( 'Y-m-d', strtotime( $dates[0] ) ),
				date( 'Y-m-d', strtotime( $dates[1] ) )
			);
		}

		return $filter;
	}

	/**
	 * choose filter type foreach filter time
	 * @param  LP_Filter $filter
	 * @param  string    $type       date|month|year|previous_days|custom
	 * @param  string    $time_field datetime colummn
	 * @param  boolean   $value      value to query datetimes
	 * @param  boolean   $is_until   filter time by the last date
	 * @return LP_Filter
	 */
	public function filter_time( LP_Filter $filter, string $type, string $time_field, $value = false, $is_until = false ) {
		if ( ! $value ) {
			throw new Exception( 'Empty statistic time', 'learnpress' );
		}
		switch ( $type ) {
			case 'date':
				$filter = $this->date_filter( $filter, $value, $time_field, $is_until );
				break;
			case 'month':
				$filter = $this->month_filter( $filter, $value, $time_field, $is_until );
				break;
			case 'year':
				$filter = $this->year_filter( $filter, $value, $time_field, $is_until );
				break;
			case 'previous_days':
				$filter = $this->previous_days_filter( $filter, (int) $value, $time_field, $is_until );
				break;
			case 'previous_months':
				$filter = $this->previous_months_filter( $filter, (int) $value, $time_field, $is_until );
				break;
			case 'custom':
				$value = explode( '+', $value );
				if ( count( $value ) !== 2 ) {
					throw new Exception( 'Invalid custom time', 'learnpress' );
				}
				$filter = $this->custom_time_filter( $filter, $value, $time_field, $is_until );
			default:
				// code...
				break;
		}
		return $filter;
	}
	/**
	 * format return data foreach type of filter
	 * @param  LP_Filter $filter
	 * @param  string    $type       date|month|year|previous_days|custom
	 * @param  string    $time_field datetime colummn
	 * @param  boolean   $value      value to query datetimes
	 * @return LP_Filter
	 */
	public function chart_filter_group_by( LP_Filter $filter, string $type, string $time_field, $value = false ) {
		switch ( $type ) {
			case 'date':
				$filter = $this->chart_filter_date_group_by( $filter, $time_field );
				break;
			case 'month':
				$filter = $this->chart_filter_month_group_by( $filter, $time_field );
				break;
			case 'year':
				$filter = $this->chart_filter_year_group_by( $filter, $time_field );
				break;
			case 'previous_days':
				$filter = $this->chart_filter_previous_days_group_by( $filter, $time_field );
				break;
			case 'previous_months':
				$filter = $this->chart_filter_previous_months_group_by( $filter, $time_field );
				break;
			case 'custom':
				if ( empty( $value ) ) {
					throw new Exception( 'Empty statistic time', 'learnpress' );
				}
				$value = explode( '+', $value );
				if ( count( $value ) !== 2 ) {
					throw new Exception( 'Invalid custom time', 'learnpress' );
				}
				$filter = $this->chart_filter_custom_group_by( $filter, $value, $time_field );
			default:
				// code...
				break;
		}
		return $filter;
	}

	/**
	 * get_completed_order_data use this for complete order report chart
	 * @param  string $type  time type filter: date|month|year|previous_days|custom
	 * @param  string $value time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom
	 * @return array  completed order data
	 */
	public function get_completed_order_data( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return [];
		}
		$filter                   = new LP_Order_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';
		$time_field               = 'p.post_date';

		// count completed orders
		$filter->only_fields[] = 'count( p.ID) as x_data';
		$filter                = $this->filter_time( $filter, $type, $time_field, $value );
		$filter                = $this->chart_filter_group_by( $filter, $type, $time_field, $value );
		$filter->where[]       = $this->wpdb->prepare( 'AND p.post_status=%s', LP_ORDER_COMPLETED_DB );
		$filter->where[]       = $this->wpdb->prepare( 'AND p.post_type=%s', $filter->post_type );
		$filter->limit         = -1;
		$filter->order_by      = $time_field;
		$filter->order         = 'asc';

		$filter->run_query_count = false;
		$result                  = $this->execute( $filter );

		return $result;
	}

	/**
	 * query to count LP Orders with all statuses
	 * @param  LP_Order_Filter $filter
	 * @return LP_Order_Filter
	 */
	public function filter_order_count_statics( LP_Order_Filter $filter ) {
		// $filter->query_count = true;
		$filter->only_fields[]   = 'count( p.ID) as count_order';
		$filter->only_fields[]   = 'REPLACE(p.post_status,"lp-","") as order_status';
		$filter->group_by        = 'p.post_status';
		$filter->where[]         = $this->wpdb->prepare( 'AND p.post_status LIKE CONCAT(%s,"%")', 'lp-' );
		$filter->where[]         = $this->wpdb->prepare( 'AND p.post_type=%s', $filter->post_type );
		$filter->run_query_count = false;

		return $filter;
	}
	/**
	 * get LP Order count of a filter time
	 * @param  string $type  date|month|year|previous_days|custom
	 * @param  string $value time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom
	 * @return array  result of LP Order count foreach status
	 */
	public function get_order_statics( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return;
		}
		$filter                   = new LP_Order_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';
		$time_field               = 'p.post_date';
		$filter                   = $this->filter_time( $filter, $type, $time_field, $value );
		$filter                   = $this->filter_order_count_statics( $filter );
		$filter->limit            = -1;
		$result                   = $this->execute( $filter );

		return $result;
	}
	/*Overviews statistics*/
	/**
	 * get sales amount of complete order
	 * @param  string $type  [time type filter: date|month|year|previous_days|custom]
	 * @param  string $value [time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom  ]
	 * @return array  completed order data
	 */
	public function get_net_sales_data( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return [];
		}
		$filter                   = new LP_Order_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';
		$oi_table                 = $this->tb_lp_order_items;
		$oim_table                = $this->tb_lp_order_itemmeta;
		// net sales summary
		$filter->only_fields[]   = 'SUM(oim.meta_value) as x_data';
		$time_field              = 'p.post_date';
		$filter->join            = [
			"INNER JOIN $oi_table AS oi ON p.ID = oi.order_id",
			"INNER JOIN $oim_table AS oim ON oi.order_item_id = oim.learnpress_order_item_id",
		];
		$filter->limit           = -1;
		$filter->where           = [
			$this->wpdb->prepare( 'AND p.post_type=%s', $filter->post_type ),
			$this->wpdb->prepare( 'AND p.post_status=%s', LP_ORDER_COMPLETED_DB ),
			$this->wpdb->prepare( 'AND oim.meta_key=%s', '_total' ),
		];
		$filter                  = $this->filter_time( $filter, $type, $time_field, $value );
		$filter                  = $this->chart_filter_group_by( $filter, $type, $time_field, $value );
		$filter->order_by        = $time_field;
		$filter->order           = 'asc';
		$filter->run_query_count = false;
		$result                  = $this->execute( $filter );
		// error_log( $this->check_execute_has_error() );
		return $result;
	}

	/**
	 * get top categories of sold course
	 * @param  string  $type                date|month|year|previous_days|custom
	 * @param  string  $value               time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom
	 * @param  integer $limit               limit of query, default is 10
	 * @param  boolean $exclude_free_course exclude free course
	 * @return array   return term_id and term_count
	 */
	public function get_top_sold_categories( string $type, string $value, $limit = 0, $exclude_free_course = false ) {
		if ( ! $type || ! $value ) {
			return;
		}
		$filter                   = new LP_Order_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';
		$filter->only_fields[]    = 'r_term.term_taxonomy_id as term_id';
		$filter->only_fields[]    = 'COUNT(r_term.term_taxonomy_id) as term_count';
		$filter->only_fields[]    = 'terms.name as term_name';
		$filter->limit            = $limit > 0 ? $limit : 10;
		$time_field               = 'p.post_date';
		$tb_term_relationships    = $this->tb_term_relationships;
		$tb_term_taxonomy         = $this->tb_term_taxonomy;
		$tb_terms                 = $this->tb_terms;
		$oi_table                 = $this->tb_lp_order_items;
		$oim_table                = $this->tb_lp_order_itemmeta;

		$filter->join = [
			"INNER JOIN $oi_table AS oi ON p.ID = oi.order_id",
			"INNER JOIN $oim_table AS oim ON oi.order_item_id = oim.learnpress_order_item_id",
			"INNER JOIN $tb_term_relationships AS r_term ON oi.item_id = r_term.object_id",
			"INNER JOIN $tb_term_taxonomy AS tax_term ON tax_term.term_taxonomy_id = r_term.term_taxonomy_id",
			"INNER JOIN $tb_terms AS terms ON terms.term_id = r_term.term_taxonomy_id",
		];

		$filter->where = array(
			$this->wpdb->prepare( 'AND p.post_type=%s', $filter->post_type ),
			$this->wpdb->prepare( 'AND p.post_status=%s', LP_ORDER_COMPLETED_DB ),
			$this->wpdb->prepare( 'AND oi.item_type=%s', LP_COURSE_CPT ),
			$this->wpdb->prepare( 'AND tax_term.taxonomy=%s', LP_COURSE_CATEGORY_TAX ),
		);
		$filter        = $this->filter_time( $filter, $type, $time_field, $value );
		if ( $exclude_free_course ) {
			$filter->where[] = $this->wpdb->prepare( 'AND oim.meta_key=%s', '_total' );
			$filter->where[] = $this->wpdb->prepare( 'AND oim.meta_value > 0' );
		}
		$filter->group_by        = 'term_id';
		$filter->order_by        = 'term_count';
		$filter->order           = 'DESC';
		$filter->run_query_count = false;
		$result                  = $this->execute( $filter );

		return $result;
	}

	/**
	 * get top courses was sold in the filter
	 * @param  string  $type                date|month|year|previous_days|custom
	 * @param  string  $value               time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom
	 * @param  integer $limit               limit of query, default 10
	 * @param  boolean $exclude_free_course exclude free course, get result only purchase course
	 * @return array   $result
	 */
	public function get_top_sold_courses( string $type, string $value, $limit = 0, $exclude_free_course = false ) {
		if ( ! $type || ! $value ) {
			return;
		}
		$filter                   = new LP_Order_Filter();
		$tb_posts                 = $this->tb_posts;
		$filter->collection       = $tb_posts;
		$filter->collection_alias = 'p';
		$filter->only_fields[]    = 'oi.item_id as course_id';
		$filter->only_fields[]    = 'COUNT(oi.item_id) as course_count';
		$filter->only_fields[]    = 'p2.post_title as course_name';
		$filter->limit            = $limit > 0 ? $limit : 10;
		$time_field               = 'p.post_date';
		$oi_table                 = $this->tb_lp_order_items;
		$oim_table                = $this->tb_lp_order_itemmeta;

		$filter->join  = [
			"INNER JOIN $oi_table AS oi ON p.ID = oi.order_id",
			"INNER JOIN $oim_table AS oim ON oi.order_item_id = oim.learnpress_order_item_id",
			"INNER JOIN $tb_posts AS p2 ON p2.ID = oi.item_id",
		];
		$filter->where = array(
			$this->wpdb->prepare( 'AND p.post_type=%s', $filter->post_type ),
			$this->wpdb->prepare( 'AND p.post_status=%s', LP_ORDER_COMPLETED_DB ),
			$this->wpdb->prepare( 'AND oi.item_type=%s', LP_COURSE_CPT ),
		);
		$filter        = $this->filter_time( $filter, $type, $time_field, $value );
		if ( $exclude_free_course ) {
			$filter->where[] = $this->wpdb->prepare( 'AND oim.meta_key=%s', '_total' );
			$filter->where[] = $this->wpdb->prepare( 'AND oim.meta_value > 0' );
		}
		$filter->group_by        = 'course_id';
		$filter->order_by        = 'course_count';
		$filter->order           = 'DESC';
		$filter->run_query_count = false;
		$result                  = $this->execute( $filter );

		return $result;
	}
	/**
	 * Overviews get total courses was created ( all statuses )
	 * @param  string  $type   date|month|year|previous_days|custom
	 * @param  string  $value  time value string "Y-m-d" for date|month|year, int for previous_days, string
	 * @return int     $result course count
	 */
	public function get_total_course_created( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return;
		}
		$filter                   = new LP_Course_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';
		$filter->only_fields[]    = 'p.ID';
		$time_field               = 'p.post_date';

		$filter->where[]     = $this->wpdb->prepare( 'AND p.post_type=%s', LP_COURSE_CPT );
		$filter              = $this->filter_time( $filter, $type, $time_field, $value );
		$filter->query_count = true;
		$result              = $this->execute( $filter );
		return $result;
	}
	/**
	 * Overviews get total orders was created ( all statuses )
	 * @param  string  $type   date|month|year|previous_days|custom
	 * @param  string  $value  time value string "Y-m-d" for date|month|year, int for previous_days, string
	 * @return int     $result order count
	 */
	public function get_total_order_created( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return;
		}
		$filter                   = new LP_Course_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';
		$filter->only_fields[]    = 'p.ID';
		$time_field               = 'p.post_date';

		$filter->where[]     = $this->wpdb->prepare( 'AND p.post_type = %s', LP_ORDER_CPT );
		$filter->where[]     = $this->wpdb->prepare( 'AND p.post_status != %s', 'auto-draft' );
		$filter              = $this->filter_time( $filter, $type, $time_field, $value );
		$filter->query_count = true;
		$result              = $this->execute( $filter );
		return $result;
	}
	/**
	 * Overviews get total instructors was created ( administrator and lp_teacher )
	 * @param  string  $type   date|month|year|previous_days|custom
	 * @param  string  $value  time value string "Y-m-d" for date|month|year, int for previous_days, string
	 * @return int     $result user count
	 */
	public function get_total_instructor_created( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return;
		}
		$filter                   = new LP_Filter();
		$filter->collection       = $this->wpdb->users;
		$filter->collection_alias = 'u';
		$filter->only_fields[]    = 'u.ID';
		$usermeta_table           = $this->wpdb->usermeta;
		$filter->join[]           = "INNER JOIN $usermeta_table AS um ON um.user_id = u.ID";
		$time_field               = 'u.user_registered';
		$filter->where[]          = $this->wpdb->prepare( 'AND um.meta_key=%s', 'wp_capabilities' );
		$filter->where[]          = $this->wpdb->prepare( "AND um.meta_value LIKE CONCAT('%',%s,'%')", ADMIN_ROLE );
		$filter->where[]          = $this->wpdb->prepare( "OR um.meta_value LIKE CONCAT('%',%s,'%')", LP_TEACHER_ROLE );
		$filter                   = $this->filter_time( $filter, $type, $time_field, $value, true );
		$filter->query_count      = true;
		$result                   = $this->execute( $filter );
		return $result;
	}
	/**
	 * Overviews get total student was created ( subscriber )
	 * @param  string  $type   date|month|year|previous_days|custom
	 * @param  string  $value  time value string "Y-m-d" for date|month|year, int for previous_days, string
	 * @return int     $result user count
	 */
	public function get_total_student_created( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return;
		}
		$filter                   = new LP_Filter();
		$filter->collection       = $this->wpdb->users;
		$filter->collection_alias = 'u';
		$filter->only_fields[]    = 'u.ID';
		$usermeta_table           = $this->wpdb->usermeta;
		$filter->join[]           = "INNER JOIN $usermeta_table AS um ON um.user_id = u.ID";
		$time_field               = 'u.user_registered';
		$filter->where[]          = $this->wpdb->prepare( 'AND um.meta_key=%s', 'wp_capabilities' );
		$filter->where[]          = $this->wpdb->prepare( 'AND um.meta_value LIKE CONCAT("%",%s,"%")', 'subscriber' );
		$filter                   = $this->filter_time( $filter, $type, $time_field, $value, true );
		$filter->query_count      = true;
		$result                   = $this->execute( $filter );
		return $result;
	}
	/*Course statistics*/
	/**
	 * Gets the published course data.
	 *
	 * @param  string  $type    date|month|year|previous_days|custom
	 * @param  string  $value   time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom
	 *
	 * @return array   The published course data.
	 */
	public function get_published_course_data( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return [];
		}
		$filter                   = new LP_Course_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';
		$time_field               = 'p.post_date';
		// count published course
		$filter->only_fields[] = 'count( p.ID) as x_data';
		$filter                = $this->filter_time( $filter, $type, $time_field, $value );
		$filter                = $this->chart_filter_group_by( $filter, $type, $time_field, $value );
		$filter->where[]       = $this->wpdb->prepare( 'AND p.post_status=%s', 'publish' );
		$filter->where[]       = $this->wpdb->prepare( 'AND p.post_type=%s', $filter->post_type );
		$filter->limit         = -1;
		$filter->order_by      = $time_field;
		$filter->order         = 'asc';

		$filter->run_query_count = false;
		$result                  = $this->execute( $filter );

		return $result;
	}
	/**
	 * Gets the course count by statuses.
	 *
	 * @param  string  $type    date|month|year|previous_days|custom
	 * @param  string  $value   time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom
	 *
	 * @return array   $result  The course count by statuses.
	 */
	public function get_course_count_by_statuses( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return [];
		}
		$filter                   = new LP_Course_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';
		$filter->only_fields[]    = 'COUNT(p.ID) as course_count';
		$filter->only_fields[]    = 'p.post_status as course_status';
		$time_field               = 'p.post_date';
		$filter                   = $this->filter_time( $filter, $type, $time_field, $value );
		$filter->where[]          = $this->wpdb->prepare( 'AND p.post_type=%s', $filter->post_type );
		$filter->where[]          = $this->wpdb->prepare( 'AND p.post_status IN (%s, %s, %s)', 'publish', 'pending', 'future' );
		$filter->limit            = -1;
		$filter->group_by         = 'p.post_status';
		$filter->run_query_count  = false;
		$result                   = $this->execute( $filter );
		return $result;
	}
	/**
	 * Gets the course items count.
	 *
	 * @param  string  $type    date|month|year|previous_days|custom
	 * @param  string  $value   time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom
	 *
	 * @return int     $result  The course items count.
	 */
	public function get_course_items_count( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return;
		}
		$filter                   = new LP_Filter();
		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'p';
		$filter->only_fields[]    = 'COUNT(p.ID) as item_count';
		$filter->only_fields[]    = 'p.post_type as item_type';
		$time_field               = 'p.post_date';
		$filter                   = $this->filter_time( $filter, $type, $time_field, $value );
		if ( class_exists( 'LP_Assignment' ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND p.post_type IN (%s, %s, %s)', LP_LESSON_CPT, LP_QUIZ_CPT, LP_ASSIGNMENT_CPT );
		} else {
			$filter->where[] = $this->wpdb->prepare( 'AND p.post_type IN (%s, %s)', LP_LESSON_CPT, LP_QUIZ_CPT );
		}
		$filter->where[]         = $this->wpdb->prepare( 'AND p.post_status IN(%s,%s,%s)', 'publish', 'pending', 'future' );
		$filter->group_by        = 'p.post_type';
		$filter->limit           = -1;
		$filter->run_query_count = false;
		$result                  = $this->execute( $filter );
		return $result;
	}
	/*User Statistics*/
	/**
	 * Gets the user registered data.
	 *
	 * @param  string  $type    date|month|year|previous_days|custom
	 * @param  string  $value   time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom
	 *
	 * @return array   $result  The user registered data.
	 */
	public function get_user_registered_data( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return [];
		}
		$filter                   = new LP_Filter();
		$filter->collection       = $this->tb_users;
		$filter->collection_alias = 'u';
		$time_field               = 'u.user_registered';
		// count user_registered
		$filter->only_fields[] = 'count( u.ID) as x_data';
		$filter                = $this->filter_time( $filter, $type, $time_field, $value );
		$filter                = $this->chart_filter_group_by( $filter, $type, $time_field, $value );
		$filter->limit         = -1;
		$filter->order_by      = $time_field;
		$filter->order         = 'asc';

		$filter->run_query_count = false;
		$result                  = $this->execute( $filter );
		return $result;
	}

	/**
	 * Gets the users by user item graduation statuses.
	 * @param  string  $type    date|month|year|previous_days|custom
	 * @param  string  $value   time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom
	 * @return int     $result  count users by graduation statuses.
	 */
	public function get_users_by_user_item_graduation_statuses( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return;
		}
		$filter                   = new LP_Filter();
		$filter->collection       = $this->tb_lp_user_items;
		$filter->collection_alias = 'ui';
		$filter->only_fields[]    = 'ui.graduation as graduation_status';
		$filter->only_fields[]    = 'COUNT(distinct(ui.user_id)) as user_count';
		$time_field               = 'ui.start_time';
		$filter->limit            = -1;
		$filter->where[]          = $this->wpdb->prepare( 'AND ui.item_type=%s', LP_COURSE_CPT );
		$filter                   = $this->filter_time( $filter, $type, $time_field, $value );
		$filter->group_by         = 'graduation_status';
		$filter->run_query_count  = false;
		$result                   = $this->execute( $filter );
		return $result;
	}
	/**
	 * filter user dont study any course in the filter time
	 * @param  string  $type    date|month|year|previous_days|custom
	 * @param  string  $value   time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom
	 * @return int     $result  count users
	 */
	public function get_users_not_started_any_course( string $type, string $value ) {
		if ( ! $type || ! $value ) {
			return;
		}
		$filter          = new LP_Filter();
		$table_useritems = $this->tb_lp_user_items;
		$table_user      = $this->tb_users;
		$time_filter     = $this->filter_time( $filter, $type, 'ui.start_time', $value );
		// get time_filter condition SQL
		$time_condition = $time_filter->where[0];
		// reset where
		$filter->where            = array();
		$filter->collection       = $table_user;
		$filter->collection_alias = 'u';
		$filter->only_fields[]    = 'u.ID';
		$filter->where[]          = $this->wpdb->prepare( "AND NOT EXISTS (SELECT * FROM $table_useritems as ui WHERE ui.user_id = u.ID $time_condition)" );
		$filter->limit            = -1;
		$filter->query_count      = true;
		// use this to see the sql query
		// $filter->return_string_query= true;
		$result = $this->execute( $filter );
		return $result;
	}
	/**
	 * get top courses was enrolled by users
	 * @param  string  $type                date|month|year|previous_days|custom
	 * @param  string  $value               time value string "Y-m-d" for date|month|year, int for previous_days, string "Y-m-d+Y-m-d" for custom
	 * @param  integer $limit               limit of query, default 10
	 * @param  boolean $exclude_free_course exclude free course, get result only purchase course
	 * @return array   $result
	 */
	public function get_top_enrolled_courses( string $type, string $value, $limit = 0, $exclude_free_course = false ) {
		if ( ! $type || ! $value ) {
			return;
		}
		$filter                   = new LP_Filter();
		$filter->collection       = $this->tb_lp_user_items;
		$filter->collection_alias = 'ui';
		$filter->only_fields[]    = 'ui.item_id as course_id';
		$filter->only_fields[]    = 'COUNT(ui.user_item_id) as enrolled_user';
		$filter->only_fields[]    = 'p.post_author as instructor_id';
		$filter->only_fields[]    = 'p.post_title as course_name';
		$filter->only_fields[]    = 'u.display_name as instructor_name';
		$filter->limit            = ! $limit ? 10 : $limit;
		$time_field               = 'ui.start_time';
		$filter->join[]           = "INNER JOIN $this->tb_posts AS p ON p.ID = ui.item_id";
		$filter->join[]           = "INNER JOIN $this->tb_users AS u ON u.ID = p.post_author";
		$filter->where[]          = $this->wpdb->prepare( 'AND ui.item_type=%s', LP_COURSE_CPT );
		$filter                   = $this->filter_time( $filter, $type, $time_field, $value );
		$filter->group_by         = 'course_id';
		$filter->order_by         = 'enrolled_user';
		$filter->order            = 'DESC';
		$filter->run_query_count  = false;
		$result                   = $this->execute( $filter );
		return $result;
	}
}
