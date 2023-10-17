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
			'overviews-statistics'         => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_overviews_statistics' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			),
			'order-statistics'         => array(
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
			$response->status  = 'success';
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
			
			$lp_order_db = LP_Order_DB::getInstance();
			$statistics = $lp_order_db->get_order_statics( $filter['filter_type'], $filter['time'] );
			$completed_orders = $lp_order_db->get_completed_order_data( $filter['filter_type'], $filter['time'] );
			$chart_data = $this->process_order_complete_data( $filter, $completed_orders );
			$data = array(
				'statistics' => $statistics,
				'chart_data' => $chart_data,
			);
			$response->data = $data;
			$response->status  = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}
		return rest_ensure_response( $response );
	}
	public function process_order_complete_data( array $filter, array $input_data ) {
		$chart_data = array();
		$data       = array();
		if ( $filter['filter_type'] == 'date' ) {
			for( $i=0; $i<24;$i++ ){
			    $row = new stdClass();
			    $row->order_time = $i; 
			    $row->count_order = 0;
			    $data[ $i ] = $row;
			}
			if( ! empty( $input_data ) ) {
				foreach ( $input_data as $row ) {
				    $data[ $row->order_time ] = $row;
				}
			}
			$chart_data['x_label'] = __( 'Hour', 'learnpress' );
		} elseif ( $filter['filter_type'] == 'previous_days' ) {
			for( $i=$filter['time']; $i>=0; $i-- ){
			    $date = date('Y-m-d', strtotime( -$i.'days'));
			    $row = new stdClass();
			    $row->order_time = $date;
			    $row->count_order = 0;
			    $data[ $date ] = $row;
			}
			foreach ( $input_data as $row ) {
			    $data[ $row->order_time ] = $row;
			}
			$chart_data['x_label'] = __( 'Dates', 'learnpress' );
		}
		foreach ( $data as $row ) {
			$chart_data['labels'][] = $row->order_time;
			$chart_data['data'][]   = (int)$row->count_order;
		}
		$chart_data['line_label'] = __( 'Completed orders', 'learnpress' );

		return $chart_data;
	}

	public function get_order_statistics_filter( $params ) {
		$filter = [];
		if ( !$params['filterType'] || $params['filterType'] == 'today' ) {
			$filter['filter_type'] = 'date';
			$filter['time']        = current_time('Y-m-d');
		} elseif ( $params['filterType'] == 'yesterday' ) {
			$filter['filter_type'] = 'date';
			$filter['time']        =  date('Y-m-d',strtotime( current_time('Y-m-d') . '-1 days' ));
		} elseif ( $params['filterType'] == 'last7days' ) {
			$filter['filter_type'] = 'previous_days';
			$filter['time']        =  6;
		} elseif ( $params['filterType'] == 'last30days' ) {
			$filter['filter_type'] = 'previous_days';
			$filter['time']        =  30;
		}
		return $filter;
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