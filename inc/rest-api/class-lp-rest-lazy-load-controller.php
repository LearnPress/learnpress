<?php
class LP_REST_Lazy_Load_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'lazy-load';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'course-progress' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'user_progress' ),
					'permission_callback' => function() {
						return is_user_logged_in();
					},
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Load user_progress in single course.
	 *
	 * @author Nhamdv <email@email.com>
	 *
	 * @return void
	 */
	public function user_progress( $request ) {
		$params         = $request->get_params();
		$course_id      = isset( $params['courseId'] ) ? $params['courseId'] : false;
		$user_id        = isset( $params['userId'] ) ? $params['userId'] : false;
		$response       = new LP_REST_Response();
		$response->data = '';

		if ( $course_id && $user_id ) {
			$course = learn_press_get_course( $course_id );
			$user   = learn_press_get_user( $user_id );

			if ( $course && $user ) {
				$response->status = 'success';
				$response->data   = learn_press_get_template_content(
					'single-course/sidebar/user-progress',
					array(
						'course' => $course,
						'user'   => $user,
					)
				);
			}
		}

		return rest_ensure_response( $response );
	}
}
