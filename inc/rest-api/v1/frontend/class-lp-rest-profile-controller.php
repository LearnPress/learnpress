<?php
class LP_REST_Profile_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'profile';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'statistic'  => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'statistic' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'course-tab' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'course_tab' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
		);

		parent::register_routes();
	}

	public function check_admin_permission( $request ) {
		$user_id = $request->get_param( 'userID' );

		if ( empty( $user_id ) ) {
			return false;
		}

		$profile = learn_press_get_profile( $user_id );

		if ( ! $profile->current_user_can( 'view-tab-courses' ) ) {
			return false;
		}

		return true;
	}

	public function statistic( WP_REST_Request $request ) {
		$user_id          = $request->get_param( 'userID' );
		$response         = new LP_REST_Response();
		$response->data   = '';
		$lp_user_items_db = LP_User_Items_DB::getInstance();

		try {
			if ( empty( $user_id ) ) {
				throw new Exception( esc_html__( 'No user ID found!', 'learnpress' ) );
			}

			$profile = learn_press_get_profile( $user_id );

			if ( $profile instanceof WP_Error ) {
				throw new Exception( $profile->get_error_message() );
			}

			$query = $profile->query_courses( 'purchased' );

			$counts = $query['counts'];

			// Count total courses has status 'in-progress'
			$total_courses_has_status = $lp_user_items_db->get_total_courses_has_status( $user_id, 'in-progress' );

			$statistic = array(
				'enrolled_courses'  => $counts['all'] ?? 0,
				'active_courses'    => $total_courses_has_status,
				'completed_courses' => $counts['finished'] ?? 0,
				'total_courses'     => count_user_posts( $user_id, LP_COURSE_CPT ),
				'total_users'       => learn_press_count_instructor_users( $user_id ),
			);

			do_action( 'learnpress/rest/frontend/profile/statistic', $request );

			$response->data   = learn_press_get_template_content( 'profile/tabs/courses/general-statistic', compact( 'statistic' ) );
			$response->status = 'success';

		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	public function course_tab( $request ) {
		$request        = $request->get_params();
		$user_id        = $request['userID'];
		$status         = $request['status'] ?? '';
		$paged          = $request['paged'] ?? 1;
		$query_type     = $request['query'] ?? 'purchased';
		$layout         = $request['layout'] ?? 'grid';
		$response       = new LP_REST_Response();
		$response->data = '';

		try {
			if ( empty( $user_id ) ) {
				throw new Exception( esc_html__( 'No user ID found!', 'learnpress' ) );
			}

			$profile = learn_press_get_profile( $user_id );

			$query = $profile->query_courses(
				$query_type,
				apply_filters(
					'learnpress/rest/frontend/profile/course_tab/query',
					array(
						'status' => $status,
						'limit'  => LP_Settings::get_option( 'archive_course_limit', 6 ),
						'paged'  => $paged,
					)
				)
			);

			// LP_User_Item_Course.
			$course_item_objects = ! empty( $query['items'] ) ? $query['items'] : false;

			if ( empty( $course_item_objects ) ) {
				throw new Exception( esc_html__( 'No Course available!', 'learnpress' ) );
			}

			$course_ids = array_map(
				function( $course_object ) {
					return ! is_object( $course_object ) ? absint( $course_object ) : $course_object->get_id();
				},
				$course_item_objects
			);

			if ( empty( $course_ids ) ) {
				throw new Exception( esc_html__( 'No Course IDs available!', 'learnpress' ) );
			}

			$user = learn_press_get_user( $user_id );

			if ( empty( $user ) ) {
				throw new Exception( esc_html__( 'No User available!', 'learnpress' ) );
			}

			do_action( 'learnpress/rest/frontend/profile/course_tab', $request );

			$num_pages    = $query->get_pages();
			$current_page = $query->get_paged();

			$content = $layout === 'grid' ? 'profile/tabs/courses/course-grid' : 'profile/tabs/courses/course-list';

			$response->data   = learn_press_get_template_content(
				$content,
				array(
					'user'         => $user,
					'course_ids'   => $course_ids,
					'num_pages'    => absint( $num_pages ) > 1 ? absint( $num_pages ) : 1,
					'current_page' => absint( $current_page ),
				)
			);
			$response->status = 'success';

		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

}
