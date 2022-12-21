<?php

use LearnPress\Helpers\Template;

class LP_REST_Orders_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'orders';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'statistic' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'statistic' ),
					'permission_callback' => '__return_true',
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Get statistic of orders.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @since 4.0.0
	 * @version 1.0.1
	 */
	public function statistic( WP_REST_Request $request ): LP_REST_Response {
		$response       = new LP_REST_Response();
		$response->data = '';

		try {
			//$order_statuses    = learn_press_get_order_statuses( true, true );
			$order_statuses = LP_Order::get_order_statuses();
			$lp_order_icons = LP_Order::get_icons_status();

			ob_start();
			$data = compact( 'order_statuses', 'lp_order_icons' );
			Template::instance()->get_admin_template( 'dashboard/html-orders', $data );
			$response->data   = ob_get_clean();
			$response->status = 'success';
		} catch ( Throwable $e ) {
			ob_end_clean();
			$response->message = $e->getMessage();
		}

		return $response;
	}

}
