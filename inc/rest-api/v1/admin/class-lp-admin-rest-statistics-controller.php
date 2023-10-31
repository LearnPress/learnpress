<?php

use LearnPress\Helpers\Template;

/**
 * Class LP_REST_Admin_Statistics_Controller
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
					// 'permission_callback' => array( $this, 'permission_check' ),
					'permission_callback' => '__return_true',
				),
			),
		);

		parent::register_routes();
	}
	public function get_overviews_statistics( $request ) {
		$response = new LP_REST_Response();
		try {
			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}
		return rest_ensure_response( $response );
	}
	public function get_order_statistics( $request ) {
		$response = new LP_REST_Response();
		try {
			$params = $request->get_params();
			$params = LP_Helper::sanitize_params_submitted( $params );
			$filter = $this->get_statistics_filter( $params );

			$lp_statistic_db  = LP_Statistics_DB::getInstance();
			$statistics       = $lp_statistic_db->get_order_statics( $filter['filter_type'], $filter['time'] );
			$completed_orders = $lp_statistic_db->get_completed_order_data( $filter['filter_type'], $filter['time'] );
			$chart_data       = $this->process_order_chart_data( $filter, $completed_orders );
			$data             = array(
				'statistics' => $statistics,
				'chart_data' => $chart_data,
			);
			$response->data   = $data;
			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}
		return rest_ensure_response( $response );
	}
	public function process_order_chart_data( array $filter, array $input_data ) {
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
				$data                  = $this->process_previous_months_data( $m, $input_data, $dates[1] );
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
			$chart_data['data'][]   = (int) $row->x_data;
		}
		$chart_data['line_label'] = __( 'Completed orders', 'learnpress' );

		return $chart_data;
	}

	public function get_statistics_filter( $params ) {
		$filter = [];
		if ( ! $params['filterType'] || $params['filterType'] == 'today' ) {
			$filter['filter_type'] = 'date';
			$filter['time']        = current_time( 'Y-m-d' );
		} elseif ( $params['filterType'] == 'yesterday' ) {
			$filter['filter_type'] = 'date';
			$filter['time']        = date( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . '-1 days' ) );
		} elseif ( $params['filterType'] == 'last7days' ) {
			$filter['filter_type'] = 'previous_days';
			$filter['time']        = 6;
		} elseif ( $params['filterType'] == 'last30days' ) {
			$filter['filter_type'] = 'previous_days';
			$filter['time']        = 30;
		} elseif ( $params['filterType'] == 'thismonth' ) {
			$filter['filter_type'] = 'month';
			$filter['time']        = current_time( 'Y-m-d' );
		} elseif ( $params['filterType'] == 'thisyear' ) {
			$filter['filter_type'] = 'year';
			$filter['time']        = current_time( 'Y-m-d' );
		} elseif ( $params['filterType'] == 'custom' && ! empty( $params['date'] ) ) {
			$filter['filter_type'] = 'custom';
			$filter['time']        = $params['date'];
		}
		return $filter;
	}

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
	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}
LP_REST_Admin_Statistics_Controller::getInstance();
