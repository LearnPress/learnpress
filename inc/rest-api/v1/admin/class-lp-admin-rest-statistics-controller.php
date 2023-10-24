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
			$filter = $this->get_order_statistics_filter( $params );

			$lp_order_db      = LP_Order_DB::getInstance();
			$statistics       = $lp_order_db->get_order_statics( $filter['filter_type'], $filter['time'] );
			$completed_orders = $lp_order_db->get_completed_order_data( $filter['filter_type'], $filter['time'] );
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
					// TODO
					// $filter = $this->chart_filter_previous_months_group_by( $filter );
				}
			} elseif ( $y < 2 ) {
				// TODO
				// $filter = $this->chart_filter_previous_months_group_by( $filter );
			} elseif ( $y < 5 ) {
				// TODO
			} else {
				// TODO
			}
		}
		foreach ( $data as $row ) {
			$chart_data['labels'][] = $row->order_time;
			$chart_data['data'][]   = (int) $row->count_order;
		}
		$chart_data['line_label'] = __( 'Completed orders', 'learnpress' );

		return $chart_data;
	}

	public function get_order_statistics_filter( $params ) {
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
			$row              = new stdClass();
			$row->order_time  = $i;
			$row->count_order = 0;
			$data[ $i ]       = $row;
		}
		if ( ! empty( $input_data ) ) {
			foreach ( $input_data as $row ) {
				$data[ $row->order_time ] = $row;
			}
		}
		return $data;
	}

	public function process_previous_days_data( int $days, array $input_data, $last_date = false ) {
		$data = array();
		for ( $i = $days; $i >= 0; $i-- ) {
			$date             = date( 'Y-m-d', strtotime( ( $last_date ? $last_date : '' ) . -$i . 'days' ) );
			$row              = new stdClass();
			$row->order_time  = $date;
			$row->count_order = 0;
			$data[ $date ]    = $row;
		}
		foreach ( $input_data as $row ) {
			$data[ $row->order_time ] = $row;
		}
		return $data;
	}

	public function process_month_data( array $filter, array $input_data ) {
		$data    = array();
		$max_day = cal_days_in_month( 0, date( 'm', strtotime( $filter['time'] ) ), date( 'Y', strtotime( $filter['time'] ) ) );
		for ( $i = 1; $i <= $max_day; $i++ ) {
			$row              = new stdClass();
			$row->order_time  = $i;
			$row->count_order = 0;
			$data[ $i ]       = $row;
		}
		if ( ! empty( $input_data ) ) {
			foreach ( $input_data as $row ) {
				$data[ $row->order_time ] = $row;
			}
		}
		return $data;
	}

	public function process_year_data() {
		$data = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$row              = new stdClass();
			$row->order_time  = $i;
			$row->count_order = 0;
			$data[ $i ]       = $row;
		}
		if ( ! empty( $input_data ) ) {
			foreach ( $input_data as $row ) {
				$data[ $row->order_time ] = $row;
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
