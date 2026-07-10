<?php

use LearnPress\Helpers\Template;
use LearnPress\Statistics\DashboardStatisticsDB;
use LearnPress\Statistics\FilterOptionsProvider;
use LearnPress\Statistics\HealthCheckProvider;
use LearnPress\Statistics\InstructorStatisticsProvider;
use LearnPress\Statistics\OrderExceptionsProvider;
use LearnPress\Statistics\PeriodHelper;
use LearnPress\Statistics\StatisticsScope;

/**
 * Class LP_REST_Admin_Statistics_Controller
 *
 * @since 4.2.5.5
 */
class LP_REST_Admin_Statistics_Controller extends LP_Abstract_REST_Controller {
	protected static $_instance = null;
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'statistics';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'overviews-statistics'  => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_overviews_statistics' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			),
			'order-statistics'      => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_order_statistics' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			),
			'course-statistics'     => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_courses_statistics' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			),
			'user-statistics'       => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_users_statistics' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			),
			'filter-options'        => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_filter_options' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			),
			'instructor-statistics' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_instructor_statistics' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			),
			'instructor-report'     => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_instructor_report' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Gets the overviews statistics.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response.
	 */
	public function get_overviews_statistics( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$params = $request->get_params();
			$params = LP_Helper::sanitize_params_submitted( $params );
			$filter = $this->get_statistics_filter( $params );

			// Report drill-down: only the requested table, capped — for the popup. @since 4.4.2
			if ( ! empty( $params['report'] ) ) {
				$response->data   = $this->get_dashboard_report( $filter, $params );
				$response->status = 'success';
				return $response;
			}

			$lp_statistic_db                    = LP_Statistics_DB::getInstance();
			$net_sales                          = $lp_statistic_db->get_net_sales_data( $filter['filter_type'], $filter['time'] );
			$total_courses                      = $lp_statistic_db->get_total_course_created( $filter['filter_type'], $filter['time'] );
			$total_orders                       = $lp_statistic_db->get_total_order_created( $filter['filter_type'], $filter['time'] );
			$total_instructors                  = $lp_statistic_db->get_total_instructor_created( $filter['filter_type'], $filter['time'] );
			$total_students                     = $lp_statistic_db->get_total_student_created( $filter['filter_type'], $filter['time'] );
			$chart_data                         = $this->process_chart_data( $filter, $net_sales );
			$top_courses                        = $lp_statistic_db->get_top_sold_courses( $filter['filter_type'], $filter['time'] );
			$top_categories                     = $lp_statistic_db->get_top_sold_categories( $filter['filter_type'], $filter['time'] );
			$chart_data['line_label']           = __( 'Net sales', 'learnpress' );
			$total_sales                        = html_entity_decode( learn_press_format_price( array_sum( $chart_data['data'] ) ) );

			$data = array(
				'total_sales'       => $total_sales,
				'total_orders'      => $total_orders,
				'total_instructors' => $total_instructors,
				'total_courses'     => $total_courses,
				'total_students'    => $total_students,
				'chart_data'        => $chart_data,
				'top_courses'       => $top_courses,
				'top_categories'    => $top_categories,
			);
			// New dashboard payload (scoped); keys above stay byte-identical. @since 4.4.2
			$data['dashboard'] = $this->get_dashboard_data( $filter, $params );
			$response->data    = $data;
			$response->status  = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}

		return $response;
	}

	/**
	 * @param WP_REST_Request $request
	 * @return LP_REST_Response
	 */
	public function get_order_statistics( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$params = $request->get_params();
			$params = LP_Helper::sanitize_params_submitted( $params );
			$filter = $this->get_statistics_filter( $params );

			if ( ! empty( $params['report'] ) ) {
				$response->data   = $this->get_order_dashboard_report( $filter, $params );
				$response->status = 'success';
				return $response;
			}

			$lp_statistic_db          = LP_Statistics_DB::getInstance();
			$statistics               = $lp_statistic_db->get_order_statics( $filter['filter_type'], $filter['time'] );
			$completed_orders         = $lp_statistic_db->get_completed_order_data( $filter['filter_type'], $filter['time'] );
			$chart_data               = $this->process_chart_data( $filter, $completed_orders );
			$chart_data['line_label'] = __( 'Completed orders', 'learnpress' );
			$data                     = array(
				'statistics' => $statistics,
				'chart_data' => $chart_data,
				'dashboard'  => $this->get_order_dashboard_data( $filter, $params ),
			);
			$response->data           = $data;
			$response->status         = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}

		return $response;
	}
	public function get_courses_statistics( $request ) {
		$response = new LP_REST_Response();
		try {
			$params = $request->get_params();
			$params = LP_Helper::sanitize_params_submitted( $params );
			$filter = $this->get_statistics_filter( $params );

			if ( ! empty( $params['report'] ) ) {
				$response->data   = $this->get_courses_dashboard_report( $filter, $params );
				$response->status = 'success';
				return $response;
			}

			$lp_statistic_db          = LP_Statistics_DB::getInstance();
			$published_course         = $lp_statistic_db->get_published_course_data( $filter['filter_type'], $filter['time'] );
			$courses                  = $lp_statistic_db->get_course_count_by_statuses( $filter['filter_type'], $filter['time'] );
			$items                    = $lp_statistic_db->get_course_items_count( $filter['filter_type'], $filter['time'] );
			$chart_data               = $this->process_chart_data( $filter, $published_course );
			$chart_data['line_label'] = __( 'Published Courses', 'learnpress' );
			$data                     = array(
				'courses'    => $courses,
				'items'      => $items,
				'chart_data' => $chart_data,
				'dashboard'  => $this->get_courses_dashboard_data( $filter, $params ),
			);
			$response->data           = $data;
			$response->status         = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}

		return $response;
	}

	/**
	 * @param $request
	 * @return LP_REST_Response
	 */
	public function get_users_statistics( $request ): LP_REST_Response {
		$response = new LP_REST_Response();
		try {
			$params = $request->get_params();
			$params = LP_Helper::sanitize_params_submitted( $params );
			$filter = $this->get_statistics_filter( $params );

			if ( ! empty( $params['report'] ) ) {
				$response->data   = $this->get_users_dashboard_report( $filter, $params );
				$response->status = 'success';
				return $response;
			}

			$lp_statistic_db         = LP_Statistics_DB::getInstance();
			$user_registers          = $lp_statistic_db->get_user_registered_data( $filter['filter_type'], $filter['time'] );
			$user_course_statused    = $lp_statistic_db->get_users_by_user_item_graduation_statuses( $filter['filter_type'], $filter['time'] );
			$user_not_start_course   = $lp_statistic_db->get_users_not_started_any_course( $filter['filter_type'], $filter['time'] );
			$top_enrolled_courses    = $lp_statistic_db->get_top_enrolled_courses( $filter['filter_type'], $filter['time'] );
			$total_instructors       = $lp_statistic_db->get_total_instructor_created( $filter['filter_type'], $filter['time'] );
			$total_students          = $lp_statistic_db->get_total_student_created( $filter['filter_type'], $filter['time'] );
			$chart_data              = $this->process_chart_data( $filter, $user_registers );
			$top_enrolled_instructor = array();
			if ( ! empty( $top_enrolled_courses ) ) {
				foreach ( $top_enrolled_courses as $key => $course ) {
					if ( ! array_key_exists( $course->instructor_id, $top_enrolled_instructor ) ) {
						$top_enrolled_instructor[ $course->instructor_id ] = array(
							'name'     => $course->instructor_name,
							'students' => (int) $course->enrolled_user,
						);
					} else {
						$top_enrolled_instructor[ $course->instructor_id ]['students'] += (int) $course->enrolled_user;
					}
				}
			}
			$chart_data['line_label'] = __( 'User registed', 'learnpress' );
			$data                     = array(
				'chart_data'              => $chart_data,
				'user_course_statused'    => $user_course_statused,
				'user_not_start_course'   => $user_not_start_course,
				'top_enrolled_courses'    => $top_enrolled_courses,
				'top_enrolled_instructor' => $top_enrolled_instructor,
				'total_instructors'       => $total_instructors,
				'total_students'          => $total_students,
				'dashboard'               => $this->get_users_dashboard_data( $filter, $params, (int) $user_not_start_course ),
			);
			$response->data           = $data;
			$response->status         = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}

		return $response;
	}
	/**
	 * Process data use for chart js
	 *
	 * @param      array  $filter      The filter in get_statistics_filter
	 * @param      array  $input_data  The input data ( data from DB )
	 *
	 * @return     array  $chart_data  Data use for chart js
	 */
	public function process_chart_data( array $filter, array $input_data ) {
		$chart_data = array();
		$data       = array();
		if ( $filter['filter_type'] == 'date' ) {
			$data                  = $this->process_date_data( $input_data );
			$chart_data['x_label'] = __( 'Hour', 'learnpress' );
		} elseif ( $filter['filter_type'] == 'previous_days' ) {
			$data                  = $this->process_previous_days_data( $filter['time'], $input_data );
			$chart_data['x_label'] = __( 'Dates', 'learnpress' );
		} elseif ( $filter['filter_type'] == 'month' ) {
			$data                  = $this->process_month_data( $filter, $input_data );
			$chart_data['x_label'] = __( 'Dates', 'learnpress' );
		} elseif ( $filter['filter_type'] == 'previous_months' ) {
			$data                  = $this->process_previous_months_data( $filter['time'], $input_data );
			$chart_data['x_label'] = __( 'Months', 'learnpress' );
		} elseif ( $filter['filter_type'] == 'year' ) {
			$data                  = $this->process_year_data( $input_data );
			$chart_data['x_label'] = __( 'Months', 'learnpress' );
		} elseif ( $filter['filter_type'] == 'custom' ) {
			$dates = $filter['time'];
			$dates = explode( '+', $dates );
			sort( $dates );
			$diff = date_diff( date_create( $dates[0] ), date_create( $dates[1] ), true );
			$y    = $diff->y;
			$m    = $diff->m;
			$d    = $diff->d;
			if ( $y < 1 ) {
				if ( $m <= 1 ) {
					if ( $d < 1 ) {
						$data                  = $this->process_date_data( $input_data );
						$chart_data['x_label'] = __( 'Hour', 'learnpress' );
					} else {
						$data                  = $this->process_previous_days_data( $d, $input_data, $dates[1] );
						$chart_data['x_label'] = __( 'Dates', 'learnpress' );
					}
				} else {
					$data                  = $this->process_previous_months_data( $m, $input_data, $dates[1] );
					$chart_data['x_label'] = __( 'Months', 'learnpress' );
					// $filter = $this->chart_filter_previous_months_group_by( $filter );
				}
			} elseif ( $y < 2 ) {
				$months                = $y * 12 + $m;
				$data                  = $this->process_previous_months_data( $months, $input_data, $dates[1] );
				$chart_data['x_label'] = __( 'Months', 'learnpress' );
			} elseif ( $y < 5 ) {
				// TODO
				$data                  = $this->process_quarters_data( $dates, $input_data );
				$chart_data['x_label'] = __( 'Quarters', 'learnpress' );
			} else {
				$data                  = $this->process_years_data( $y, $input_data, $dates[1] );
				$chart_data['x_label'] = __( 'Years', 'learnpress' );
			}
		}
		foreach ( $data as $row ) {
			$chart_data['labels'][] = $row->x_data_label;
			$chart_data['data'][]   = (float) $row->x_data;
		}
		// $chart_data['line_label'] = __( 'Completed orders', 'learnpress' );

		return $chart_data;
	}
	/**
	 * Gets the statistics filter.
	 *
	 * @param      http request  $params  The parameters
	 *
	 * @return     array   The statistics filter. use for process data
	 */
	public function get_statistics_filter( $params ) {
		$filter     = [];
		$filtertype = $params['filtertype'] ?? 'today';
		switch ( $filtertype ) {
			case 'today':
				$filter['filter_type'] = 'date';
				$filter['time']        = current_time( 'Y-m-d' );
				break;
			case 'yesterday':
				$filter['filter_type'] = 'date';
				$filter['time']        = date( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . '-1 days' ) );
				break;
			case 'last7days':
				$filter['filter_type'] = 'previous_days';
				$filter['time']        = 6;
				break;
			case 'last30days':
				$filter['filter_type'] = 'previous_days';
				$filter['time']        = 30;
				break;
			case 'thismonth':
				$filter['filter_type'] = 'month';
				$filter['time']        = current_time( 'Y-m-d' );
				break;
			case 'last12months':
				$filter['filter_type'] = 'previous_months';
				$filter['time']        = 11;
				break;
			case 'thisyear':
				$filter['filter_type'] = 'year';
				$filter['time']        = current_time( 'Y-m-d' );
				break;
			case 'custom':
				$filter['filter_type'] = 'custom';
				$filter['time']        = $params['date'];
				break;
			default:
				break;
		}
		return $filter;
	}
	/**
	 * process data of a date ( in 24h )
	 *
	 * @param      array  $input_data  The input data
	 *
	 * @return     array  ( description_of_the_return_value )
	 */
	public function process_date_data( array $input_data ) {
		$data = array();
		for ( $i = 0; $i < 24;$i++ ) {
			$row               = new stdClass();
			$row->x_data_label = $i;
			$row->x_data       = 0;
			$data[ $i ]        = $row;
		}
		if ( ! empty( $input_data ) ) {
			foreach ( $input_data as $row ) {
				$data[ $row->x_data_label ] = $row;
			}
		}
		return $data;
	}
	/**
	 * process data of days since the last date, if dont have last date, last date is current date
	 *
	 * @param      int    $days        The days
	 * @param      array  $input_data  The input data
	 * @param      bool   $last_date   The last date
	 *
	 * @return     array  ( description_of_the_return_value )
	 */
	public function process_previous_days_data( int $days, array $input_data, $last_date = false ) {
		$data = array();
		for ( $i = $days; $i >= 0; $i-- ) {
			$date              = date( 'Y-m-d', strtotime( ( $last_date ? $last_date : '' ) . -$i . 'days' ) );
			$row               = new stdClass();
			$row->x_data_label = $date;
			$row->x_data       = 0;
			$data[ $date ]     = $row;
		}
		if ( ! empty( $input_data ) ) {
			foreach ( $input_data as $row ) {
				$data[ $row->x_data_label ] = $row;
			}
		}
		return $data;
	}
	/**
	 * process data of a month
	 *
	 * @param      array  $filter      The filter
	 * @param      array  $input_data  The input data
	 *
	 * @return     array  ( description_of_the_return_value )
	 */
	public function process_month_data( array $filter, array $input_data ) {
		$data    = array();
		$max_day = cal_days_in_month( 0, date( 'm', strtotime( $filter['time'] ) ), date( 'Y', strtotime( $filter['time'] ) ) );
		for ( $i = 1; $i <= $max_day; $i++ ) {
			$row               = new stdClass();
			$row->x_data_label = $i;
			$row->x_data       = 0;
			$data[ $i ]        = $row;
		}
		if ( ! empty( $input_data ) ) {
			foreach ( $input_data as $row ) {
				$data[ $row->x_data_label ] = $row;
			}
		}
		return $data;
	}
	/**
	 * process data of months since the last date, if dont have last date, last date is current date
	 *
	 * @param      int    $months      The months
	 * @param      array  $input_data  The input data
	 * @param      bool   $last_date   The last date
	 *
	 * @return     array  ( description_of_the_return_value )
	 */
	public function process_previous_months_data( int $months, array $input_data, $last_date = false ) {
		$data = array();
		for ( $i = $months; $i >= 0; $i-- ) {
			$date              = date( 'm-Y', strtotime( ( $last_date ? $last_date : '' ) . -$i . 'months' ) );
			$row               = new stdClass();
			$row->x_data_label = $date;
			$row->x_data       = 0;
			$data[ $date ]     = $row;
		}
		if ( ! empty( $input_data ) ) {
			foreach ( $input_data as $row ) {
				$data[ $row->x_data_label ] = $row;
			}
		}
		return $data;
	}
	/**
	 *
	 * @param      array  $dates       The dates
	 * @param      array  $input_data  The input data
	 *
	 * @return     array  process data for date range 2-5 years
	 */
	public function process_quarters_data( array $dates, array $input_data ) {
		$data       = array();
		$start_time = strtotime( $dates[0] );
		$end_time   = strtotime( $dates[1] );
		for ( $i = date( 'Y', $start_time ); $i <= date( 'Y', $end_time ); $i++ ) {
			if ( $i == date( 'Y', $start_time ) ) {
				$quarter = ceil( date( 'm', $start_time ) / 3 );
				for ( $j = $quarter;$j <= 4;$j++ ) {
					$row               = new stdClass();
					$row->x_data_label = 'q' . $j . '-' . $i;
					$row->x_data       = 0;
					$data[]            = $row;
				}
			} elseif ( $i == date( 'Y', $start_time ) ) {
				$quarter = ceil( date( 'm', $end_time ) / 3 );
				for ( $j = 1;$j <= $quarter;$j++ ) {
					$row               = new stdClass();
					$row->x_data_label = 'q' . $j . '-' . $i;
					$row->x_data       = 0;
					$data[]            = $row;
				}
			} else {
				for ( $j = 1; $j <= 4;$j++ ) {
					$row               = new stdClass();
					$row->x_data_label = 'q' . $j . '-' . $i;
					$row->x_data       = 0;
					$data[]            = $row;
				}
			}
		}
		if ( ! empty( $input_data ) ) {
			foreach ( $input_data as $row ) {
				$data[ $row->x_data_label ] = $row;
			}
		}
		return $data;
	}
	/**
	 * process data of a year
	 *
	 * @param      array  $input_data  data from DB
	 *
	 * @return     array  chart data
	 */
	public function process_year_data( array $input_data ) {
		$data = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$row               = new stdClass();
			$row->x_data_label = $i;
			$row->x_data       = 0;
			$data[ $i ]        = $row;
		}
		if ( ! empty( $input_data ) ) {
			foreach ( $input_data as $row ) {
				$data[ $row->x_data_label ] = $row;
			}
		}
		return $data;
	}

	/**
	 * process data of years( when date range > 5 years )
	 *
	 * @param      int    $years       The years
	 * @param      array  $input_data  The input data
	 * @param      bool   $last_date   The last date
	 *
	 * @return     array  ( description_of_the_return_value )
	 */
	public function process_years_data( int $years, array $input_data, $last_date = false ) {
		$data = array();
		for ( $i = $years; $i >= 0; $i-- ) {
			$year              = date( 'Y', strtotime( ( $last_date ? $last_date : '' ) . -$i . 'years' ) );
			$row               = new stdClass();
			$row->x_data_label = $year;
			$row->x_data       = 0;
			$data[ $year ]     = $row;
		}
		if ( ! empty( $input_data ) ) {
			foreach ( $input_data as $row ) {
				$data[ $row->x_data_label ] = $row;
			}
		}
		return $data;
	}

	/**
	 * Bucket order-count rows ( from get_order_statics ) by status.
	 *
	 * @param mixed $rows Rows of { count_order, order_status }.
	 * @return array Known statuses => int counts.
	 * @since 4.4.2
	 */
	private function get_order_status_buckets( $rows ): array {
		$buckets = array(
			'completed'  => 0,
			'processing' => 0,
			'pending'    => 0,
			'cancelled'  => 0,
			'failed'     => 0,
		);

		foreach ( (array) $rows as $row ) {
			$status = $row->order_status ?? '';
			if ( isset( $buckets[ $status ] ) ) {
				$buckets[ $status ] = (int) $row->count_order;
			}
		}

		return $buckets;
	}

	/**
	 * Bucket course-count rows by status.
	 *
	 * @param mixed $rows Rows of { course_count, course_status }.
	 * @return array Known statuses => int counts.
	 * @since 4.4.2
	 */
	private function get_course_status_buckets( $rows ): array {
		$buckets = array(
			'publish' => 0,
			'pending' => 0,
			'future'  => 0,
			'draft'   => 0,
		);

		foreach ( (array) $rows as $row ) {
			$status = $row->course_status ?? '';
			if ( isset( $buckets[ $status ] ) ) {
				$buckets[ $status ] = (int) $row->course_count;
			}
		}

		return $buckets;
	}

	/**
	 * Sum x_data values from a chart-query result set.
	 *
	 * @param mixed $rows
	 * @return float
	 * @since 4.4.2
	 */
	private function sum_chart_rows( $rows ): float {
		return round(
			array_sum(
				array_map(
					function ( $row ) {
						return (float) ( $row->x_data ?? 0 );
					},
					(array) $rows
				)
			),
			2
		);
	}

	/**
	 * Assemble the scoped dashboard payload for the Orders tab.
	 *
	 * @param array $filter [ 'filter_type', 'time' ] from get_statistics_filter().
	 * @param array $params Sanitized request params.
	 * @return array
	 * @since 4.4.2
	 */
	private function get_order_dashboard_data( array $filter, array $params ): array {
		$scope       = StatisticsScope::from_params( $params );
		$prev_filter = PeriodHelper::get_previous_filter( $filter );
		$db          = DashboardStatisticsDB::getInstance();
		$lp_stats_db = LP_Statistics_DB::getInstance();
		$type        = $filter['filter_type'];
		$time        = (string) $filter['time'];
		$prev_type   = $prev_filter['filter_type'] ?? '';
		$prev_time   = isset( $prev_filter['time'] ) ? (string) $prev_filter['time'] : '';

		$order_buckets  = $this->get_order_status_buckets( $lp_stats_db->get_order_statics( $type, $time, $scope ) );
		$prev_buckets   = $prev_filter
			? $this->get_order_status_buckets( $lp_stats_db->get_order_statics( $prev_type, $prev_time, $scope ) )
			: null;
		$total_orders   = array_sum( $order_buckets );
		$prev_total     = $prev_buckets ? array_sum( $prev_buckets ) : null;
		$cancelled_fail = $order_buckets['cancelled'] + $order_buckets['failed'];
		$prev_cf        = $prev_buckets ? $prev_buckets['cancelled'] + $prev_buckets['failed'] : null;

		$net_sales  = $this->sum_chart_rows( $lp_stats_db->get_net_sales_data( $type, $time, $scope ) );
		$prev_sales = $prev_filter ? $this->sum_chart_rows( $lp_stats_db->get_net_sales_data( $prev_type, $prev_time, $scope ) ) : null;

		$completed_orders = $order_buckets['completed'];
		$aov              = $completed_orders > 0 ? round( $net_sales / $completed_orders, 2 ) : null;
		$cancel_fail_rate = $total_orders > 0 ? round( $cancelled_fail / $total_orders * 100, 1 ) : null;

		$paid_courses = $db->get_paid_courses_sold( $type, $time, $scope );
		$prev_paid    = $prev_filter ? $db->get_paid_courses_sold( $prev_type, $prev_time, $scope ) : null;

		$top_sold_courses = array_map(
			function ( $row ) {
				$row['revenue_formatted'] = html_entity_decode( learn_press_format_price( $row['revenue'] ) );
				$row['aov_formatted']     = null !== $row['aov'] ? html_entity_decode( learn_press_format_price( $row['aov'] ) ) : null;
				return $row;
			},
			$db->get_top_sold_courses_detailed( $type, $time, $scope, 20 )
		);

		return array(
			'kpis'             => array(
				'net_sales'         => PeriodHelper::kpi_payload( $net_sales, $prev_sales ) + array(
					'formatted' => html_entity_decode( learn_press_format_price( $net_sales ) ),
				),
				'completed_orders'  => PeriodHelper::kpi_payload( $completed_orders, $prev_buckets['completed'] ?? null ) + array(
					'aov'           => $aov,
					'aov_formatted' => null !== $aov ? html_entity_decode( learn_press_format_price( $aov ) ) : null,
				),
				'processing'        => PeriodHelper::kpi_payload( $order_buckets['processing'], $prev_buckets['processing'] ?? null ),
				'pending'           => PeriodHelper::kpi_payload( $order_buckets['pending'], $prev_buckets['pending'] ?? null ),
				'cancelled_failed'  => PeriodHelper::kpi_payload( $cancelled_fail, $prev_cf ) + array(
					'rate_pct'      => $cancel_fail_rate,
					'prev_rate_pct' => $prev_total > 0 && null !== $prev_cf ? round( $prev_cf / $prev_total * 100, 1 ) : null,
				),
				'paid_courses_sold' => PeriodHelper::kpi_payload( $paid_courses, $prev_paid ),
			),
			'order_health'       => $order_buckets,
			'top_sold_courses' => $top_sold_courses,
			'exceptions'       => OrderExceptionsProvider::getInstance()->get_exceptions( $type, $time, $scope, 20 ),
		);
	}

	/**
	 * Orders dashboard drill-down for report popups.
	 *
	 * @param array $filter [ 'filter_type', 'time' ].
	 * @param array $params Sanitized request params.
	 * @return array
	 * @since 4.4.2
	 */
	private function get_order_dashboard_report( array $filter, array $params ): array {
		$report = sanitize_key( $params['report'] );
		$limit  = min( 500, max( 1, absint( $params['limit'] ?? 500 ) ) );
		$scope  = StatisticsScope::from_params( $params );
		$type   = $filter['filter_type'];
		$time   = (string) $filter['time'];

		switch ( $report ) {
			case 'top_sold_courses':
				$rows = array_map(
					function ( $row ) {
						$row['revenue_formatted'] = html_entity_decode( learn_press_format_price( $row['revenue'] ) );
						$row['aov_formatted']     = null !== $row['aov'] ? html_entity_decode( learn_press_format_price( $row['aov'] ) ) : null;
						return $row;
					},
					DashboardStatisticsDB::getInstance()->get_top_sold_courses_detailed( $type, $time, $scope, $limit )
				);
				break;
			case 'exceptions':
				$rows = OrderExceptionsProvider::getInstance()->get_exceptions( $type, $time, $scope, $limit );
				break;
			default:
				$rows = array();
		}

		return array(
			'report' => $report,
			'rows'   => $rows,
			'limit'  => $limit,
		);
	}

	/**
	 * Assemble the scoped dashboard payload for the Courses tab.
	 *
	 * Legacy response keys are built unscoped in get_courses_statistics(); this
	 * payload honors instructor/category scope for the upgraded dashboard UI.
	 *
	 * @param array $filter [ 'filter_type', 'time' ] from get_statistics_filter().
	 * @param array $params Sanitized request params.
	 * @return array
	 * @since 4.4.2
	 */
	private function get_courses_dashboard_data( array $filter, array $params ): array {
		$scope       = StatisticsScope::from_params( $params );
		$db          = DashboardStatisticsDB::getInstance();
		$lp_stats_db = LP_Statistics_DB::getInstance();
		$type        = $filter['filter_type'];
		$time        = (string) $filter['time'];
		$target      = (int) apply_filters( 'learn-press/statistics/completion-target', 70 );

		$inventory       = $db->get_content_inventory( $scope );
		$status_buckets  = $this->get_course_status_buckets( $lp_stats_db->get_course_count_by_statuses( $type, $time, $scope ) );
		$completion_rows = $db->get_completion_rows( $type, $time, $scope );
		$completion      = DashboardStatisticsDB::completion_from_rows( $completion_rows, $target );
		$health_raw      = HealthCheckProvider::getInstance()->get_checks( $scope );

		return array(
			'kpis'          => array(
				'published'                  => array(
					'value'           => (int) ( $inventory['courses']['publish'] ?? 0 ),
					'added_in_period' => $status_buckets['publish'],
				),
				'pending_review'             => array(
					'value'           => (int) ( $inventory['courses']['pending'] ?? 0 ),
					'added_in_period' => $status_buckets['pending'],
				),
				'future'                     => array(
					'value'           => (int) ( $inventory['courses']['future'] ?? 0 ),
					'added_in_period' => $status_buckets['future'],
				),
				'enrollments'                => array(
					'value' => $db->get_enrollments_count( $type, $time, $scope ),
				),
				'avg_completion'             => array(
					'value'  => DashboardStatisticsDB::average_completion_rate_from_rows( $completion_rows ),
					'target' => $target,
				),
				'courses_without_enrollment' => array(
					'value' => (int) ( $health_raw['no_enrollment'] ?? 0 ),
				),
			),
			'performance'   => $this->format_course_performance_rows( $db->get_top_courses_performance( $type, $time, $scope, 10 ) ),
			'health_checks' => array(
				'no_curriculum'  => (int) ( $health_raw['no_content'] ?? 0 ),
				'no_students'    => (int) ( $health_raw['no_enrollment'] ?? 0 ),
				'low_completion' => (int) $completion['courses_below_target'],
				'low_quiz_pass'  => (int) ( $health_raw['quiz_low_pass'] ?? 0 ),
				'pending_review' => (int) ( $health_raw['pending_review'] ?? 0 ),
			),
			'inventory'     => $inventory,
		);
	}

	/**
	 * Courses dashboard drill-down for report popups.
	 *
	 * @param array $filter [ 'filter_type', 'time' ].
	 * @param array $params Sanitized request params.
	 * @return array
	 * @since 4.4.2
	 */
	private function get_courses_dashboard_report( array $filter, array $params ): array {
		$report = sanitize_key( $params['report'] );
		$limit  = min( 500, max( 1, absint( $params['limit'] ?? 500 ) ) );
		$scope  = StatisticsScope::from_params( $params );
		$rows   = array();

		if ( 'performance' === $report ) {
			$rows = $this->format_course_performance_rows(
				DashboardStatisticsDB::getInstance()->get_top_courses_performance(
					$filter['filter_type'],
					(string) $filter['time'],
					$scope,
					$limit
				)
			);
		}

		return array(
			'report' => $report,
			'rows'   => $rows,
			'limit'  => $limit,
		);
	}

	/**
	 * Format course performance rows for the Courses tab contract.
	 *
	 * @param array $rows Rows from DashboardStatisticsDB::get_top_courses_performance().
	 * @return array
	 * @since 4.4.2
	 */
	private function format_course_performance_rows( array $rows ): array {
		$course_ids  = array_map(
			function ( $row ) {
				return absint( $row['course_id'] ?? 0 );
			},
			$rows
		);
		$instructors = $this->get_course_instructor_map( $course_ids );

		return array_map(
			function ( $row ) use ( $instructors ) {
				$course_id = absint( $row['course_id'] ?? 0 );
				$revenue   = (float) ( $row['revenue'] ?? 0 );

				return array(
					'course_id'         => $course_id,
					'name'              => (string) ( $row['course_name'] ?? '' ),
					'instructor'        => $instructors[ $course_id ] ?? '',
					'revenue'           => $revenue,
					'revenue_formatted' => html_entity_decode( learn_press_format_price( $revenue ) ),
					'orders'            => (int) ( $row['order_count'] ?? 0 ),
					'enrollments'       => (int) ( $row['enrolled'] ?? 0 ),
					'completed'         => (int) ( $row['completed'] ?? 0 ),
					'completion_rate'   => $row['completion_rate'] ?? null,
					'edit_link'         => $course_id > 0 ? (string) get_edit_post_link( $course_id, 'raw' ) : '',
				);
			},
			$rows
		);
	}

	/**
	 * Batch-map course IDs to instructor display names.
	 *
	 * @param array $course_ids
	 * @return array course_id => display_name
	 * @since 4.4.2
	 */
	private function get_course_instructor_map( array $course_ids ): array {
		global $wpdb;

		$course_ids = array_values( array_filter( array_unique( array_map( 'absint', $course_ids ) ) ) );
		if ( empty( $course_ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic %d list is built from absint-normalized IDs.
		$sql = $wpdb->prepare(
			"SELECT p.ID AS course_id, u.display_name AS instructor
			FROM {$wpdb->posts} AS p
			LEFT JOIN {$wpdb->users} AS u ON u.ID = p.post_author
			WHERE p.ID IN ( {$placeholders} )",
			...$course_ids
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$rows = $wpdb->get_results( $sql );
		$map  = array();

		foreach ( (array) $rows as $row ) {
			$map[ (int) $row->course_id ] = (string) $row->instructor;
		}

		return $map;
	}

	/**
	 * Assemble the scoped dashboard payload for the Overview tab.
	 *
	 * Legacy response keys are built unscoped elsewhere and stay byte-identical;
	 * everything here honors instructor_id/category_id and carries
	 * previous-period deltas via PeriodHelper.
	 *
	 * @param array $filter [ 'filter_type', 'time' ] from get_statistics_filter().
	 * @param array $params Sanitized request params.
	 * @return array
	 * @since 4.4.2
	 */
	private function get_dashboard_data( array $filter, array $params ): array {
		$scope       = StatisticsScope::from_params( $params );
		$prev_filter = PeriodHelper::get_previous_filter( $filter );
		$db          = DashboardStatisticsDB::getInstance();
		$lp_stats_db = LP_Statistics_DB::getInstance();
		$type        = $filter['filter_type'];
		$time        = (string) $filter['time'];
		$prev_type   = $prev_filter['filter_type'] ?? '';
		$prev_time   = isset( $prev_filter['time'] ) ? (string) $prev_filter['time'] : '';

		// Orders: current + previous buckets (one query each).
		$order_buckets = $this->get_order_status_buckets( $lp_stats_db->get_order_statics( $type, $time, $scope ) );
		$prev_buckets  = $prev_filter
			? $this->get_order_status_buckets( $lp_stats_db->get_order_statics( $prev_type, $prev_time, $scope ) )
			: null;
		$total_orders  = array_sum( $order_buckets );

		// Revenue: chart series + period sums.
		$revenue_chart = $this->process_chart_data( $filter, $lp_stats_db->get_net_sales_data( $type, $time, $scope ) );
		$net_sales     = round( array_sum( $revenue_chart['data'] ), 2 );
		$prev_sales    = null;
		if ( $prev_filter ) {
			$prev_rows  = $lp_stats_db->get_net_sales_data( $prev_type, $prev_time, $scope );
			$prev_sales = round( array_sum( array_map( fn( $row ) => (float) $row->x_data, (array) $prev_rows ) ), 2 );
		}

		// Enrollments chart series (same label processing as revenue).
		$enroll_chart = $this->process_chart_data(
			$filter,
			$lp_stats_db->get_enrollment_chart_data( $type, $time, 0, $scope )
		);

		$enrollments      = $db->get_enrollments_count( $type, $time, $scope );
		$prev_enrollments = $prev_filter ? $db->get_enrollments_count( $prev_type, $prev_time, $scope ) : null;

		$completion      = $db->get_completion_stats( $type, $time, $scope );
		$prev_completion = $prev_filter ? $db->get_completion_stats( $prev_type, $prev_time, $scope ) : null;

		$active_learners = $db->get_active_learners_count( $type, $time, $scope );
		$prev_active     = $prev_filter ? $db->get_active_learners_count( $prev_type, $prev_time, $scope ) : null;

		$completed_orders = $order_buckets['completed'];
		$aov              = $completed_orders > 0 ? round( $net_sales / $completed_orders, 2 ) : null;
		$failed_orders    = $order_buckets['failed'];
		$fail_rate        = $total_orders > 0 ? round( $failed_orders / $total_orders * 100, 1 ) : null;

		$kpis = array(
			'net_sales'        => PeriodHelper::kpi_payload( $net_sales, $prev_sales ) + array(
				'formatted' => html_entity_decode( learn_press_format_price( $net_sales ) ),
			),
			'completed_orders' => PeriodHelper::kpi_payload( $completed_orders, $prev_buckets['completed'] ?? null ) + array(
				'aov'           => $aov,
				'aov_formatted' => null !== $aov ? html_entity_decode( learn_press_format_price( $aov ) ) : null,
			),
			'enrollments'      => PeriodHelper::kpi_payload( $enrollments, $prev_enrollments ),
			'completion_rate'  => PeriodHelper::kpi_payload( $completion['rate'], $prev_completion['rate'] ?? null ) + array(
				'courses_below_target' => $completion['courses_below_target'],
			),
			'active_learners'  => PeriodHelper::kpi_payload( $active_learners, $prev_active ),
			'failed_orders'    => PeriodHelper::kpi_payload( $failed_orders, $prev_buckets['failed'] ?? null ) + array(
				'fail_rate_pct' => $fail_rate,
			),
		);

		$top_courses = array_map(
			function ( $row ) {
				$row['revenue_formatted'] = html_entity_decode( learn_press_format_price( $row['revenue'] ) );
				return $row;
			},
			$db->get_top_courses_performance( $type, $time, $scope )
		);

		$instructor_summary = array_map(
			function ( $row ) {
				$row['revenue_formatted'] = html_entity_decode( learn_press_format_price( $row['revenue'] ) );
				return $row;
			},
			$db->get_instructor_performance( $type, $time, $scope )
		);

		$health_checks                   = HealthCheckProvider::getInstance()->get_checks( $scope );
		$health_checks['low_completion'] = $completion['courses_below_target'];

		return array(
			'kpis'               => $kpis,
			'chart'              => array(
				'labels'      => $revenue_chart['labels'],
				'revenue'     => $revenue_chart['data'],
				'enrollments' => $enroll_chart['data'],
				'x_label'     => $revenue_chart['x_label'],
			),
			'funnel'             => $db->get_learner_funnel( $type, $time, $scope ),
			'top_courses'        => $top_courses,
			'instructor_summary' => $instructor_summary,
			'order_health'       => $order_buckets + array(
				'total'            => $total_orders,
				'cancelled_failed' => $order_buckets['cancelled'] + $failed_orders,
			),
			'health_checks'      => $health_checks,
		);
	}

	/**
	 * Single-table drill-down for the report popup.
	 *
	 * @param array $filter [ 'filter_type', 'time' ].
	 * @param array $params Sanitized request params ( report, limit, scope ).
	 * @return array [ 'report' => string, 'rows' => array, 'limit' => int ]
	 * @since 4.4.2
	 */
	private function get_dashboard_report( array $filter, array $params ): array {
		$report = sanitize_key( $params['report'] );
		$limit  = min( 500, max( 1, absint( $params['limit'] ?? 500 ) ) );
		$scope  = StatisticsScope::from_params( $params );

		switch ( $report ) {
			case 'top_courses':
				$rows = array_map(
					function ( $row ) {
						$row['revenue_formatted'] = html_entity_decode( learn_press_format_price( $row['revenue'] ) );
						return $row;
					},
					DashboardStatisticsDB::getInstance()->get_top_courses_performance(
						$filter['filter_type'],
						(string) $filter['time'],
						$scope,
						$limit
					)
				);
				break;
			default:
				$rows = array();
		}

		return array(
			'report' => $report,
			'rows'   => $rows,
			'limit'  => $limit,
		);
	}

	/**
	 * Assemble the scoped dashboard payload for the Users tab.
	 *
	 * users_activated/students/instructors totals are role-based user counts
	 * (no course dimension) and stay unscoped like their legacy siblings;
	 * everything course-linked honors instructor_id/category_id.
	 *
	 * @param array $filter [ 'filter_type', 'time' ] from get_statistics_filter().
	 * @param array $params Sanitized request params.
	 * @param int   $not_started Reused from the legacy assembly — get_users_not_started_any_course() is expensive.
	 * @return array
	 * @since 4.4.2
	 */
	private function get_users_dashboard_data( array $filter, array $params, int $not_started = 0 ): array {
		$scope       = StatisticsScope::from_params( $params );
		$db          = DashboardStatisticsDB::getInstance();
		$lp_stats_db = LP_Statistics_DB::getInstance();
		$type        = $filter['filter_type'];
		$time        = (string) $filter['time'];

		$total_instructors = (int) $lp_stats_db->get_total_instructor_created( $type, $time );
		$total_students    = (int) $lp_stats_db->get_total_student_created( $type, $time );
		$funnel            = $db->get_learner_funnel( $type, $time, $scope, true );
		$completion        = $db->get_completion_stats( $type, $time, $scope );

		return array(
			'kpis'                    => array(
				'users_activated' => array(
					'value'         => $total_instructors + $total_students,
					'new_in_period' => $funnel['registered'],
				),
				'students'        => array(
					'value'     => $total_students,
					// '6' matches the last7days button window ( CURDATE() - 6 → today ).
					'active_7d' => $db->get_active_learners_count( 'previous_days', '6', $scope ),
				),
				'instructors'     => array(
					'value'            => $total_instructors,
					'active_in_period' => $db->get_instructors_active_in_period( $type, $time, $scope ),
				),
				'not_started'     => array(
					'value' => $not_started,
				),
				'in_progress'     => array(
					'value' => $db->get_users_in_progress_count( $type, $time, $scope ),
				),
				'finished'        => array(
					'value'           => $funnel['completed'],
					'completion_rate' => $completion['rate'],
				),
			),
			'funnel'                  => $funnel,
			'top_students'            => $db->get_top_students( $type, $time, $scope, 10 ),
			'top_courses_by_students' => $db->get_courses_by_students( $type, $time, $scope, 10 ),
		);
	}

	/**
	 * Users dashboard drill-down for report popups.
	 *
	 * @param array $filter [ 'filter_type', 'time' ].
	 * @param array $params Sanitized request params.
	 * @return array
	 * @since 4.4.2
	 */
	private function get_users_dashboard_report( array $filter, array $params ): array {
		$report = sanitize_key( $params['report'] );
		$limit  = min( 500, max( 1, absint( $params['limit'] ?? 500 ) ) );
		$scope  = StatisticsScope::from_params( $params );
		$db     = DashboardStatisticsDB::getInstance();
		$rows   = array();

		if ( 'top_students' === $report ) {
			$rows = $db->get_top_students( $filter['filter_type'], (string) $filter['time'], $scope, $limit );
		} elseif ( 'courses_by_students' === $report ) {
			$rows = $db->get_courses_by_students( $filter['filter_type'], (string) $filter['time'], $scope, $limit );
		}

		return array(
			'report' => $report,
			'rows'   => $rows,
			'limit'  => $limit,
		);
	}

	/**
	 * Instructors tab payload: KPIs, operations widget, performance + watchlist tables.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @since 4.4.2
	 */
	public function get_instructor_statistics( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$params = $request->get_params();
			$params = LP_Helper::sanitize_params_submitted( $params );
			$filter = $this->get_statistics_filter( $params );
			$scope  = StatisticsScope::from_params( $params );

			// Performance drill-down: the full (500-capped) table for the popup. @since 4.4.2
			if ( ! empty( $params['report'] ) ) {
				$limit = min( 500, max( 1, absint( $params['limit'] ?? 500 ) ) );
				$rows  = 'performance' === sanitize_key( $params['report'] )
					? InstructorStatisticsProvider::format_performance(
						DashboardStatisticsDB::getInstance()->get_instructor_performance(
							$filter['filter_type'],
							(string) $filter['time'],
							$scope,
							$limit
						)
					)
					: array();

				$response->data   = array(
					'report' => sanitize_key( $params['report'] ),
					'rows'   => $rows,
					'limit'  => $limit,
				);
				$response->status = 'success';
				return $response;
			}

			$type        = $filter['filter_type'];
			$time        = (string) $filter['time'];
			$lp_stats_db = LP_Statistics_DB::getInstance();

			$revenue_chart = $this->process_chart_data( $filter, $lp_stats_db->get_net_sales_data( $type, $time, $scope ) );
			$enroll_chart  = $this->process_chart_data(
				$filter,
				$lp_stats_db->get_enrollment_chart_data( $type, $time, 0, $scope )
			);

			$response->data   = array(
				'dashboard'  => InstructorStatisticsProvider::get_statistics( $type, $time, $scope ),
				'chart_data' => array(
					'labels'      => $revenue_chart['labels'] ?? array(),
					'revenue'     => $revenue_chart['data'] ?? array(),
					'enrollments' => $enroll_chart['data'] ?? array(),
					'x_label'     => $revenue_chart['x_label'] ?? '',
				),
			);
			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}

		return $response;
	}

	/**
	 * Per-instructor drill-down for the performance-row popup.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @since 4.4.2
	 */
	public function get_instructor_report( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$params        = $request->get_params();
			$params        = LP_Helper::sanitize_params_submitted( $params );
			$instructor_id = absint( $params['instructor_id'] ?? 0 );
			$limit         = min( 500, max( 1, absint( $params['limit'] ?? 500 ) ) );

			$report = InstructorStatisticsProvider::get_report( $instructor_id, $limit );

			if ( ! empty( $report['error'] ) ) {
				$response->message = __( 'Instructor not found or has no courses.', 'learnpress' );
				$response->status  = 'error';
				return $response;
			}

			$response->data   = $report;
			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}

		return $response;
	}

	/**
	 * Options for the global statistics filters (instructor/category dropdowns).
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @since 4.4.2
	 */
	public function get_filter_options( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$response->data   = FilterOptionsProvider::get_options();
			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}

		return $response;
	}

	public function permission_check( $request ) {
		return apply_filters( 'learnpress/admin-statistics/permission', current_user_can( 'administrator' ) );
	}
}
