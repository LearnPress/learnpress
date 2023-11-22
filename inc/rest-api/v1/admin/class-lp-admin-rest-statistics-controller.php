<?php

use LearnPress\Helpers\Template;

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
			'overviews-statistics' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_overviews_statistics' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			),
			'order-statistics'     => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_order_statistics' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			),
			'course-statistics'    => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_courses_statistics' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			),
			'user-statistics'      => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_users_statistics' ),
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

			$data             = array(
				'total_sales'       => $total_sales,
				'total_orders'      => $total_orders,
				'total_instructors' => $total_instructors,
				'total_courses'     => $total_courses,
				'total_students'    => $total_students,
				'chart_data'        => $chart_data,
				'top_courses'       => $top_courses,
				'top_categories'    => $top_categories,
			);
			$response->data   = $data;
			$response->status = 'success';
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
			$params                   = $request->get_params();
			$params                   = LP_Helper::sanitize_params_submitted( $params );
			$filter                   = $this->get_statistics_filter( $params );
			$lp_statistic_db          = LP_Statistics_DB::getInstance();
			$statistics               = $lp_statistic_db->get_order_statics( $filter['filter_type'], $filter['time'] );
			$completed_orders         = $lp_statistic_db->get_completed_order_data( $filter['filter_type'], $filter['time'] );
			$chart_data               = $this->process_chart_data( $filter, $completed_orders );
			$chart_data['line_label'] = __( 'Completed orders', 'learnpress' );
			$data                     = array(
				'statistics' => $statistics,
				'chart_data' => $chart_data,
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
			$params                   = $request->get_params();
			$params                   = LP_Helper::sanitize_params_submitted( $params );
			$filter                   = $this->get_statistics_filter( $params );
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
			$params                  = $request->get_params();
			$params                  = LP_Helper::sanitize_params_submitted( $params );
			$filter                  = $this->get_statistics_filter( $params );
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
			$chart_data['data'][]   = (float) number_format( $row->x_data, 2 );
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

	public function permission_check( $request ) {
		return apply_filters( 'learnpress/admin-statistics/permission', current_user_can( 'administrator' ) );
	}
}
