<?php

/**
 * Class LP_Abstract_REST_Controller
 */
class LP_Abstract_REST_Controller extends WP_REST_Controller {

	/**
	 * @var string
	 */
	public $namespace = 'lp/v1';

	/**
	 * @var string
	 */
	public $rest_base = '';

	/**
	 * @var array
	 */
	public $routes = array();

	public function __construct() {

	}

	/**
	 * Register routes for controller.
	 */
	public function register_routes() {

		if ( ! $this->routes ) {
			return;
		}

		foreach ( $this->routes as $key => $args ) {
			$rest_base = $this->rest_base;
			$override  = false;

			if ( is_bool( end( $args ) ) ) {
				$override = array_pop( $args );
			}

			if ( ! is_numeric( $key ) ) {
				$rest_base = "{$rest_base}/{$key}";
			}

			register_rest_route( $this->namespace, '/' . $rest_base, $args, $override );
		}
	}

	public function ensure_response( $data ) {
		add_filter( 'rest_pre_serve_request', array( $this, 'print_response' ), 10, 4 );

		return rest_ensure_response( $data );
	}

	/**
	 * @param boolean          $false
	 * @param WP_REST_Response $result
	 * @param WP_REST_Request  $request
	 * @param WP_REST_Server   $server
	 */
	public function print_response( $false, $result, $request, $server ) {
		learn_press_send_json( $result->get_data() );
	}
}