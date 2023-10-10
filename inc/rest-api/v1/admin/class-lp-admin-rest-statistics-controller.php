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
			if ( !$params['filterType'] ) {
				$filter_type = 'date';
				$time        = current_time('Y-m-d');
			}
			$lp_order_db = LP_Order_DB::getInstance();
			$statistics = $lp_order_db->get_order_statics( $filter_type, $time );
			$completed_data = $lp_order_db->get_completed_order_data( $filter_type, $time );
			$data = array(
				'statistics' => $statistics,
				'chart'      => $completed_data,
			);
			$response->data = $data;
			$response->status  = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}
		return rest_ensure_response( $response );
	}
	public function permission_check( $request ) {
		return apply_filters( 'learnpress/admin-statistics/rest-permission', current_user_can( 'administrator' ) );
	}
	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}
LP_REST_Admin_Statistics_Controller::getInstance();