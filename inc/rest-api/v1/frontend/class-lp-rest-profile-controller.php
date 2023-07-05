<?php

use LearnPress\Helpers\Template;

class LP_REST_Profile_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'profile';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'student/statistic'    => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'student_statistics' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'instructor/statistic' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'instructor_statistics' ),
					'permission_callback' => '__return_true',
				),
			),
			'course-tab'           => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'course_tab' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'course-attend'        => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'course_attend' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
			'get-avatar'           => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_avatar' ),
					'permission_callback' => function() {
						return get_current_user_id() ? true : false;
					},
				),
			),
			'upload-avatar'        => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upload_avatar' ),
					'permission_callback' => function() {
						return get_current_user_id() ? true : false;
					},
				),
			),
			'remove-avatar'        => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'remove_avatar' ),
					'permission_callback' => function() {
						return get_current_user_id() ? true : false;
					},
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Check permission
	 *
	 * @param $request
	 *
	 * @return bool
	 */
	public function check_permission( $request ): bool {
		$user_id = $request->get_param( 'userID' );

		if ( empty( $user_id ) ) {
			return false;
		}

		/*$profile = learn_press_get_profile( $user_id );

		if ( ! $profile->current_user_can( 'view-tab-my-courses' ) ) {
			return false;
		}*/

		return true;
	}

	public function get_avatar( WP_REST_Request $request ) {
		$response = new LP_REST_Response();

		$thumb_size = learn_press_get_avatar_thumb_size();

		$profile    = learn_press_get_profile( get_current_user_id() );
		$avatar_url = $profile->get_upload_profile_src();

		$response->data->width  = $thumb_size['width'];
		$response->data->height = $thumb_size['height'];
		$response->data->url    = $avatar_url ? $avatar_url : '';

		return rest_ensure_response( $response );
	}

	public function upload_avatar( WP_REST_Request $request ) {
		$file_base64 = $request->get_param( 'file' );
		$response    = new LP_REST_Response();

		try {
			$user_id = get_current_user_id();

			if ( ! $user_id ) {
				throw new Exception( __( 'User not found', 'learnpress' ) );
			}

			if ( empty( $file_base64 ) ) {
				throw new Exception( __( 'File not found', 'learnpress' ) );
			}

			$upload_dir = learn_press_user_profile_picture_upload_dir( true );

			$target_dir = LP_WP_Filesystem::instance()->is_dir( $upload_dir['path'] );

			if ( ! $target_dir ) {
				wp_mkdir_p( $upload_dir['path'] );
			}

			if ( ! LP_WP_Filesystem::instance()->is_writable( $upload_dir['path'] ) ) {
				throw new Exception( __( 'The upload directory is not writable', 'learnpress' ) );
			}

			// Delete old image if exists
			$path_img = get_user_meta( $user_id, '_lp_profile_picture', true );

			if ( $path_img ) {
				$path = $upload_dir['basedir'] . '/' . $path_img;

				if ( file_exists( $path ) ) {
					LP_WP_Filesystem::instance()->unlink( $path );
				}
			}

			$file_name = md5( $user_id . microtime( true ) ) . '.jpeg';

			$file_base64 = str_replace( 'data:image/jpeg;base64,', '', $file_base64 );
			$file_base64 = base64_decode( $file_base64 );

			$put_content = LP_WP_Filesystem::instance()->put_contents( $upload_dir['path'] . '/' . $file_name, $file_base64, FS_CHMOD_FILE );

			if ( ! $put_content ) {
				throw new Exception( __( 'Cannot write the file', 'learnpress' ) );
			}

			update_user_meta( $user_id, '_lp_profile_picture', $upload_dir['subdir'] . '/' . $file_name );

			$response->status  = 'success';
			$response->message = __( 'Avatar updated', 'learnpress' );
		} catch ( \Throwable $th ) {
			$response->message = $th->getMessage();
		}

		return rest_ensure_response( $response );
	}

	public function remove_avatar( WP_REST_Request $request ) {
		$response = new LP_REST_Response();

		try {
			$user_id = get_current_user_id();

			if ( ! $user_id ) {
				throw new Exception( esc_html__( 'The user is invalid', 'learnpress' ) );
			}

			$upload_dir = learn_press_user_profile_picture_upload_dir( true );

			$target_dir = LP_WP_Filesystem::instance()->is_dir( $upload_dir['path'] );

			if ( ! $target_dir ) {
				wp_mkdir_p( $upload_dir['path'] );
			}

			if ( ! LP_WP_Filesystem::instance()->is_writable( $upload_dir['path'] ) ) {
				throw new Exception( __( 'The upload directory is not writable', 'learnpress' ) );
			}

			$path_img = get_user_meta( $user_id, '_lp_profile_picture', true );

			if ( $path_img ) {
				$path = $upload_dir['basedir'] . '/' . $path_img;

				if ( file_exists( $path ) ) {
					LP_WP_Filesystem::instance()->unlink( $path );

					$response->status  = 'success';
					$response->message = esc_html__( 'The profile picture has been removed successfully', 'learnpress' );
				}
			}
		} catch ( \Throwable $th ) {
			$response->message = $th->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Statistics of a student.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function student_statistics( WP_REST_Request $request ) {
		$user_id        = $request->get_param( 'userID' );
		$response       = new LP_REST_Response();
		$response->data = '';

		try {
			if ( empty( $user_id ) ) {
				throw new Exception( esc_html__( 'No user ID found!', 'learnpress' ) );
			}

			$user = learn_press_get_user( $user_id );
			if ( ! $user ) {
				throw new Exception( esc_html__( 'The user does not exist!', 'learnpress' ) );
			}

			$statistic = $user->get_student_statistic();
			$data      = apply_filters(
				'learn-press/profile/student-statistics/info',
				[
					'enrolled_courses'   => [
						'title' => __( 'Total enrolled courses', 'learnpress' ),
						'label' => __( 'Enrolled Course', 'learnpress' ),
						'count' => $statistic['enrolled_courses'] ?? 0,
					],
					'in_progress_course' => [
						'title' => __( 'Total course is in progress', 'learnpress' ),
						'label' => __( 'Inprogress Course', 'learnpress' ),
						'count' => $statistic['in_progress_course'] ?? 0,
					],
					'finished_courses'   => [
						'title' => __( 'Total courses finished', 'learnpress' ),
						'label' => __( 'Finished Course', 'learnpress' ),
						'count' => $statistic['finished_courses'] ?? 0,
					],
					'passed_courses'     => [
						'title' => __( 'Total courses passed', 'learnpress' ),
						'label' => __( 'Passed Course', 'learnpress' ),
						'count' => $statistic['passed_courses'] ?? 0,
					],
					'failed_courses'     => [
						'title' => __( 'Total courses failed', 'learnpress' ),
						'label' => __( 'Failed Course', 'learnpress' ),
						'count' => $statistic['failed_courses'] ?? 0,
					],
				]
			);

			ob_start();
			Template::instance()->get_frontend_template(
				'profile/tabs/statistics/student-statistics.php',
				compact( 'data' )
			);
			$response->data   = ob_get_clean();
			$response->status = 'success';
		} catch ( Exception $e ) {
			ob_end_clean();
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Statistics of an instructor.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function instructor_statistics( WP_REST_Request $request ) {
		$user_id        = $request->get_param( 'userID' );
		$response       = new LP_REST_Response();
		$response->data = '';

		try {
			if ( empty( $user_id ) ) {
				throw new Exception( esc_html__( 'No user ID found!', 'learnpress' ) );
			}

			$user = learn_press_get_user( $user_id );
			if ( ! $user ) {
				throw new Exception( esc_html__( 'The user does not exist!', 'learnpress' ) );
			}

			$profile = learn_press_get_profile( $user_id );
			if ( $profile instanceof WP_Error ) {
				throw new Exception( $profile->get_error_message() );
			}

			$statistic = $user->get_instructor_statistic();

			$data = apply_filters(
				'learn-press/profile/instructor-statistics/info',
				[
					'total_course'        => [
						'title' => __( 'Total Course', 'learnpress' ),
						'label' => __( 'Total Course', 'learnpress' ),
						'count' => $statistic['total_course'] ?? 0,
					],
					'published_course'    => [
						'title' => __( 'Published Course', 'learnpress' ),
						'label' => __( 'Published Course', 'learnpress' ),
						'count' => $statistic['published_course'] ?? 0,
					],
					'pending_course'      => [
						'title' => __( 'Pending Course', 'learnpress' ),
						'label' => __( 'Pending Course', 'learnpress' ),
						'count' => $statistic['pending_course'] ?? 0,
					],
					'total_student'       => [
						'title' => __( 'Total Student', 'learnpress' ),
						'label' => __( 'Total Student', 'learnpress' ),
						'count' => $statistic['total_student'] ?? 0,
					],
					'student_completed'   => [
						'title' => __( 'Student Completed', 'learnpress' ),
						'label' => __( 'Student Completed', 'learnpress' ),
						'count' => $statistic['student_completed'] ?? 0,
					],
					'student_in_progress' => [
						'title' => __( 'Student In-progress', 'learnpress' ),
						'label' => __( 'Student In-progress', 'learnpress' ),
						'count' => $statistic['student_in_progress'] ?? 0,
					],
				]
			);

			ob_start();
			Template::instance()->get_frontend_template(
				'profile/tabs/statistics/instructor-statistics.php',
				compact( 'data' )
			);
			$response->data   = ob_get_clean();
			$response->status = 'success';
		} catch ( Exception $e ) {
			ob_end_clean();
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	public function course_tab( $request ) {
		$params     = $request->get_params();
		$user_id    = $params['userID'] ?? get_current_user_id();
		$status     = $params['status'] ?? '';
		$paged      = $params['paged'] ?? 1;
		$query_type = $params['query'] ?? 'purchased';
		$layout     = $params['layout'] ?? 'grid';
		$response   = new LP_REST_Response();

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
					),
					$request
				)
			);

			// LP_User_Item_Course.
			$course_item_objects = ! empty( $query->get_items() ) ? $query->get_items() : false;

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

			do_action( 'learnpress/rest/frontend/profile/course_tab', $params );

			$num_pages    = $query->get_pages();
			$current_page = $query->get_paged();

			$template = $layout === 'grid' ? 'profile/tabs/courses/course-grid' : 'profile/tabs/courses/course-list';

			$response->data   = learn_press_get_template_content(
				$template,
				array(
					'user'         => $user,
					'course_ids'   => $course_ids,
					'num_pages'    => max( absint( $num_pages ), 1 ),
					'current_page' => absint( $current_page ),
				)
			);
			$response->status = 'success';

		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Get course's user attend
	 *
	 * @param WP_REST_Request $request
	 *
	 * @author tungnx
	 * @since 4.1.5
	 * @version 1.0.0
	 * @return LP_REST_Response
	 */
	public function course_attend( WP_REST_Request $request ): LP_REST_Response {
		$params   = $request->get_params();
		$user_id  = get_current_user_id();
		$status   = $params['status'] ?? '';
		$paged    = $params['paged'] ?? 1;
		$layout   = $params['layout'] ?? 'grid';
		$response = new LP_REST_Response();

		try {
			if ( ! $user_id ) {
				throw new Exception( __( 'The user is invalid', 'learnpress' ) );
			}

			$filter                      = new LP_User_Items_Filter();
			$filter->limit               = LP_Settings::get_option( 'archive_course_limit', 6 );
			$filter->user_id             = $user_id;
			$total_rows                  = 0;
			$courses                     = LP_User_Item_Course::get_user_courses( $filter, $total_rows );
			$response->data->courses     = $courses;
			$response->data->total_pages = LP_Database::get_total_pages( $filter->limit, $total_rows );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
