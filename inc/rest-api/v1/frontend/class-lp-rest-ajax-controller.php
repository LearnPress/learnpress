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
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'get_content' ),
					'permission_callback' => '__return_true',
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * @param WP_REST_Request $request
	 * Has two params: callback and args.
	 * Data type of callback is [ 'class' => '', 'method' => '' ].
	 * Data type of args is array.
	 *
	 * @return LP_REST_Response
	 * @since 4.2.5.7
	 * @version 1.0.0
	 */
	public function get_content( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$params = $request->get_params();

			if ( empty( $params['callback'] ) ||
				empty( $params['args'] ) ) {
				throw new Exception( 'Error: params invalid!' );
			}

			// @var array $args
			$args     = $params['args'];
			$callBack = $params['callback'];
			if ( $request->get_method() === 'GET' ) {
				$args     = LP_Helper::json_decode( $params['args'], true );
				$callBack = LP_Helper::json_decode( $params['callback'], true );
			}

			if ( empty( $callBack['class'] ) ||
				empty( $callBack['method'] ) ) {
				throw new Exception( 'Error: callback invalid!' );
			}

			$class  = $callBack['class'];
			$method = $callBack['method'];
			$data   = null;
			if ( is_callable( [ $class, $method ] ) ) {
				$data = call_user_func( [ $class, $method ], $args );
			}

			if ( ! $data instanceof stdClass && ! isset( $data->content ) ) {
				throw new Exception( 'Error: data content invalid!' );
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
