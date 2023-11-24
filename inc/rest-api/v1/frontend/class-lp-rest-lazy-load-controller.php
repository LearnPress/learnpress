<?php

use LearnPress\Helpers\Template;

/**
 * Class LP_REST_Lazy_Load_Controller
 */
class LP_REST_Lazy_Load_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'lazy-load';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'course-progress'         => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'user_progress' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
				),
			),
			'course-curriculum'       => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'course_curriculum' ),
					'permission_callback' => '__return_true',
				),
			),
			'course-curriculum-items' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'course_curriculum_items' ),
					'permission_callback' => '__return_true',
				),
			),
			'items-progress'          => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'items_progress' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Load items progress in single curriculum items.
	 *
	 * @param [type] $request
	 *
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
				throw new Exception( esc_html__( 'The course is invalid!', 'learnpress' ) );
			}

			if ( $course->is_no_required_enroll() ) {
				throw new Exception( esc_html__( 'The course is not required to enroll!', 'learnpress' ) );
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

		try {
			if ( ! $course_id || ! $user_id ) {
				throw new Exception( __( 'Params is invalid!', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				throw new Exception( __( 'The course is invalid!', 'learnpress' ) );
			}

			$user = learn_press_get_user( $user_id );
			if ( ! $user || $user->is_guest() ) {
				throw new Exception( __( 'You are a Guest', 'learnpress' ) );
			}

			if ( ! $user->can_create_course() && get_current_user_id() !== $user_id ) {
				throw new Exception( __( 'You are a not permission!', 'learnpress' ) );
			}

			$course_data = $user->get_course_data( $course->get_id() );
			if ( ! $course_data ) {
				throw new Exception( __( 'You are a not enroll course', 'learnpress' ) );
			}

			$course_results = $course_data->calculate_course_results();

			$response->status = 'success';
			$response->data   = learn_press_get_template_content(
				'single-course/sidebar/user-progress',
				compact( 'user', 'course', 'course_data', 'course_results' )
			);
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Load sections of single course
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @author nhamdv
	 * @since 4.1.5
	 * @version 1.0.2
	 */
	public function course_curriculum( WP_REST_Request $request ): LP_REST_Response {
		$response   = new LP_REST_Response();
		$params     = $request->get_params();
		$total_rows = 0;
		$course_id  = absint( $params['courseId'] ?? 0 );
		$per_page   = LP_Settings::get_option( 'section_per_page', -1 );
		$page       = absint( $params['page'] ?? 1 );
		$section_id = wp_unslash( $params['sectionID'] ?? false );

		try {
			ob_start();
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'The course is invalid!', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				throw new Exception( esc_html__( 'The course is invalid!', 'learnpress' ) );
			}

			$filters                    = new LP_Section_Filter();
			$filters->section_course_id = $course_id;
			$filters->limit             = $per_page;
			$filters->page              = $page;
			$sections_result            = LP_Section_DB::getInstance()->get_sections( $filters, $total_rows );
			$sections_tmp               = [];
			foreach ( $sections_result as $section ) {
				$sections_tmp[] = (array) $section;
			}

			$total_page = 1;
			if ( $filters->limit > 0 ) {
				$total_page = LP_Database::get_total_pages( $filters->limit, $total_rows );
			}
			$sections = array(
				'results' => $sections_tmp,
				'total'   => $total_rows,
				'pages'   => $total_page,
			);

			if ( ! empty( $params['loadMore'] ) ) {
				foreach ( $sections_tmp as $section ) {
					Template::instance()->get_frontend_template(
						'loop/single-course/loop-section.php',
						compact( 'sections', 'section', 'course_id', 'filters' )
					);
				}
			} else {
				Template::instance()->get_frontend_template(
					'single-course/tabs/curriculum-v2.php',
					compact( 'sections', 'course_id', 'filters' )
				);
			}

			if ( $section_id ) {
				$response->data->section_ids = LP_Database::get_values_by_key( $sections_result, 'section_id' );
			}

			$response->status        = 'success';
			$response->data->pages   = $total_page;
			$response->data->page    = $filters->page;
			$response->data->content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Load items' section on the single course
	 *
	 * @param WP_REST_Request $request
	 *
	 * @author nhamdv
	 * @since 4.1.5
	 * @version 1.0.2
	 * @return WP_REST_Response|WP_Error
	 */
	public function course_curriculum_items( WP_REST_Request $request ) {
		$params = $request->get_params();

		$section_id = absint( $params['sectionId'] ?? 0 );
		$course_id  = absint( $params['courseId'] ?? 0 );
		$per_page   = LP_Settings::get_option( 'course_item_per_page', -1 );
		$page       = absint( $params['page'] ?? 1 );

		$response                = new LP_REST_Response();
		$response->data->content = '';

		try {
			ob_start();
			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'The section is invalid!', 'learnpress' ) );
			}

			$filters              = new LP_Section_Items_Filter();
			$filters->only_fields = [ 'ID', 'post_title' ];
			$filters->section_id  = $section_id;
			$filters->limit       = $per_page;
			$filters->page        = $page;
			$total_rows           = 0;
			$section_items_result = LP_Section_DB::getInstance()->get_items( $filters, $total_rows );
			$total_pages          = 1;
			if ( $filters->limit > 0 ) {
				$total_pages = LP_Database::get_total_pages( $filters->limit, $total_rows );
			}

			foreach ( $section_items_result as $key => $section_item ) {
				$section_item = (array) $section_item;
				if ( ! $course_id ) {
					$course_id = LP_Section_DB::getInstance()->get_course_id_by_section( $section_id );
				}
				if ( ! $course_id ) {
					throw new Exception( esc_html__( 'Item not assign to course', 'learnpress' ) );
				}

				$course = learn_press_get_course( $course_id );
				if ( ! $course ) {
					throw new Exception( 'Course is not exists!' );
				}

				$course_item   = $course->get_item( absint( $section_item['ID'] ?? 0 ) );
				$can_view_item = new LP_Model_User_Can_View_Course_Item();
				$user          = learn_press_get_user( get_current_user_id() );
				if ( $user ) {
					$can_view_content_course = $user->can_view_content_course( absint( $course_id ) );
					$can_view_item           = $user->can_view_item( $section_item['ID'], $can_view_content_course );
				}

				// Ordinal numbers
				$key = absint( ( ( $page - 1 ) * $per_page ) + $key + 1 );

				Template::instance()->get_frontend_template(
					'loop/single-course/loop-section-item.php',
					compact( 'section_item', 'course_item', 'can_view_item', 'course_id', 'user', 'key' )
				);
			}

			$response->data->pages    = $total_pages;
			$response->data->page     = $filters->page;
			$response->data->content  = ob_get_clean();
			$response->data->item_ids = LP_Database::get_values_by_key( $section_items_result );
			$response->status         = 'success';
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}
}
