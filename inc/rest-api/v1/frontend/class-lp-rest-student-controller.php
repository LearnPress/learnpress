<?php
/**
 * REST API LP Student.
 *
 * @class LP_REST_Student_Controller
 * @author thimpress
 * @version 1.0.0
 */

class LP_REST_Student_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'students';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'list_students' ),
				'permission_callback' => '__return_true',
			),
		);

		parent::register_routes();
	}

	/**
	 * Get list student attend
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 */
	public function list_students( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$params    = $request->get_params();
			$course_id = absint( $params['courseId'] ?? 0 );
			$status    = $params['status'] ?? '';
			$paged     = absint( $params['paged'] ?? 1 );
			$limit     = LP_Settings::get_option( 'archive_course_limit', 10 );

			$filter   = new LP_User_Items_Filter();
			$students = LP_User_Items_DB::getInstance()->get_students( $filter );

			$response->status        = 'success';
			$response->data->content = $students;
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
