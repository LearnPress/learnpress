<?php
/**
 * REST API LP, load all content want via REST.
 *
 * @since 4.2.5.7
 * @version 1.0.0
 */

class LP_REST_AJAX_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'load_content_via_ajax';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'/' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_content' ),
					'permission_callback' => '__return_true',
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 */
	public function get_content( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$params = $request->get_params();

			if ( empty( $params['class'] ) ||
			     empty( $params['method'] ) ||
			     empty( $params['target'] ) ) {
				throw new Exception( 'Error: params invalid!' );
			}

			$class  = $params['class'];
			$method = $params['method'];
			$data   = new stdClass();
			if ( is_callable( [ $class, $method ] ) ) {
				$data = call_user_func( [ $class, $method ], $params );
			}

			$response->status  = 'success';
			$response->message = 'Success!';
			$response->data    = $data;
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
