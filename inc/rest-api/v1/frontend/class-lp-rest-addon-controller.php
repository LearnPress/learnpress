<?php

use LearnPress\Helpers\Template;

/**
 * REST API LP Widget.
 *
 * @author Nhamdv <daonham95@gmail.com>
 */
class LP_REST_Addon_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'addon';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'all' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_addons' ),
					'permission_callback' => '__return_true',
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Get list addons
	 *
	 * @param WP_REST_Request $request
	 *
	 * @since 4.2.1
	 * @version 1.0.0
	 * @return LP_REST_Response
	 */
	public function list_addons( WP_REST_Request $request ): LP_REST_Response {
		$response       = new LP_REST_Response();
		$response->data = '';

		$url = 'https://learnpress.github.io/learnpress/version-addons.json';

		try {
			$res = wp_remote_get( $url );
			if ( is_wp_error( $res ) ) {
				throw new Exception( $res->get_error_message() );
			}

			$addons = json_decode( wp_remote_retrieve_body( $res ) );
			if ( json_last_error() ) {
				throw new Exception( json_last_error_msg() );
			}

			ob_start();
			Template::instance()->get_admin_template( 'addons.php', compact( 'addons' ) );
			$response->data = ob_get_clean();

			$response->status  = 'success';
			$response->message = __( 'Get addons successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( $e->getMessage() );
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
