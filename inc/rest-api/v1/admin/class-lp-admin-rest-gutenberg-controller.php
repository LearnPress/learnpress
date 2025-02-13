<?php

/**
 * Class LP_REST_Users_Controller
 *
 * @since 4.2.7.6
 */
class LP_REST_Admin_Gutenberg_Controller extends LP_Abstract_REST_Controller {

	public function __construct() {
		$this->namespace = 'lp/v1/admin';
		$this->rest_base = 'gutenberg';

		parent::__construct();
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			'get-categories' => array(
				array(
					'methods'  => WP_REST_Server::ALLMETHODS,
					'callback' => array( $this, 'get_categories' ),
					// 'permission_callback' => array( $this, 'permission_check' ),
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Get Final Quiz in Course Settings.
	 *
	 * @return void
	 */
	public function get_categories( WP_REST_Request $request ) {
		$params         = $request->get_params();
		$search         = ! empty( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
		$response       = new LP_REST_Response();
		$response->data = '';
		$categories     = '';
		$response       = new LP_REST_Response();
		try {
			$categories                 = get_terms(
				array(
					'taxonomy'   => 'course_category',
					'hide_empty' => false,
					'search'     => $search,
				)
			);
			$formatted_categories       = array_map(
				function ( $category ) {
					return array(
						'id'   => $category->term_id,
						'name' => $category->name,
					);
				},
				$categories
			);
			$response->status           = 'success';
			$response->data->categories = $formatted_categories;
		} catch ( Exception $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function check_admin_permission() {
		return LP_Abstract_API::check_admin_permission();
	}
}
