<?php

use LearnPress\Helpers\Template;

/**
 * Class LP_REST_Lazy_Load_Controller
 */
class LP_REST_Lazy_Load_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'statistics';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'overviews-chart-net-sales'         => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_overviews_net_sales' ),
					'permission_callback' => function () {
						return current_user_can('administrator');
					},
				),
			),
		);

		parent::register_routes();
	}
	public function get_overviews_net_sales( $request ) {
		$response = new LP_REST_Response();
		try {
			$response->status  = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
			$response->status  = 'error';
		}
		return rest_ensure_response( $response );
	}
}
