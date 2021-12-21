<?php
class LP_REST_Lazy_Load_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'lazy-load';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'course-progress'   => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'user_progress' ),
					'permission_callback' => function() {
						return is_user_logged_in();
					},
				),
			),
			'course-curriculum' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'course_curriculum' ),
					'permission_callback' => '__return_true',
				),
			),
			'items-progress'    => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'items_progress' ),
					'permission_callback' => '__return_true',
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Load items progress in single curriculum items.
	 *
	 * @param [type] $request
	 * @return WP_REST_Response|WP_Error
	 *
	 * @author Nhamdv <daonham95>
	 */
	public function items_progress( $request ) {
		$params         = $request->get_params();
		$course_id      = $params['courseId'] ?? 0;
		$user_id        = get_current_user_id();
		$response       = new LP_REST_Response();
		$response->data = '';

		try {
			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				throw new Exception( esc_html__( 'Course is invalid!', 'learnpress' ) );
			}

			if ( $course->is_no_required_enroll() ) {
				throw new Exception( esc_html__( 'Course is no require enroll!', 'learnpress' ) );
			}

			$user = learn_press_get_user( $user_id );

			$check = $user->can_show_finish_course_btn( $course );

			if ( $check['status'] !== 'success' ) {
				throw new Exception( $check['message'] );
			}

			$response->status = 'success';
			$response->data   = learn_press_get_template_content(
				'single-course/buttons/finish.php',
				array(
					'course' => $course,
					'user'   => $user,
				)
			);
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Load user_progress in single course.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @author Nhamdv <email@email.com>
	 * @editor tungnx
	 * @version 1.0.1
	 * @sicne 4.0.0
	 */
	public function user_progress( WP_REST_Request $request ): LP_REST_Response {
		$params         = $request->get_params();
		$course_id      = $params['courseId'] ?? false;
		$user_id        = $params['userId'] ?? false;
		$response       = new LP_REST_Response();
		$response->data = '';

		if ( $course_id && $user_id ) {
			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				$response->message = __( 'Course is invalid', 'learnpress' );
				return $response;
			}

			$user = learn_press_get_user( $user_id );
			if ( $user->is_guest() ) {
				$response->message = __( 'You are Guest', 'learnpress' );
				return $response;
			}

			$course_data = $user->get_course_data( $course->get_id() );
			if ( ! $course_data ) {
				return $response;
			}

			$course_results = $course_data->calculate_course_results();

			$response->status = 'success';
			$response->data   = learn_press_get_template_content(
				'single-course/sidebar/user-progress',
				compact( 'user', 'course', 'course_data', 'course_results' )
			);
		}

		return $response;
	}

	/**
	 * Load course items in tab Curriculum and sidebar in Single Course Curriculums
	 *
	 * @param [type] $request
	 * @return void
	 */
	public function course_curriculum( $request ) {
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
					'single-course/tabs/curriculum',
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
