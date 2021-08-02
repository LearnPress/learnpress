<?php
class LP_REST_Orders_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'orders';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'statistic'  => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'statistic' ),
					'permission_callback' => '__return_true',
				),
			),
		);

		parent::register_routes();
	}

	public function statistic( WP_REST_Request $request ) {

		$response         = new LP_REST_Response();
		$response->data   = '';

		$order_statuses    = learn_press_get_order_statuses( true, true );
		$specific_statuses = array( 'lp-completed', 'lp-failed' );

		foreach ( $order_statuses as $status ) {
			if ( ! in_array( $status, $specific_statuses ) ) {
				$specific_statuses[] = $status;
			}
		}

		try {
			$response->data   = learn_press_get_template_content( 'admin/views/dashboard/html-orders', compact( 'specific_statuses' ), '' , LP_PLUGIN_PATH . 'inc/' );
			$response->status = 'success';

		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

}
