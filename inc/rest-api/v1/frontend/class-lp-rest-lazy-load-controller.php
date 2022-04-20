<?php

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
	 * Load sections of single course
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @author nhamdv
	 * @since 4.1.5
	 * @version 1.0.1
	 */
	public function course_curriculum( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();
		$params   = $request->get_params();
		$content  = '';

		$course_id  = absint( $params['courseId'] ?? 0 );
		$per_page   = LP()->settings()->get( 'section_per_page', 2 );
		$page       = absint( $params['page'] ?? 1 );
		$order      = wp_unslash( $params['order'] ?? 'ASC' );
		$search     = wp_unslash( $params['search'] ?? '' );
		$include    = wp_unslash( $params['include'] ?? array() );
		$exclude    = wp_unslash( $params['exclude'] ?? array() );
		$section_id = wp_unslash( $params['sectionID'] ?? false );

		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'Course is invalid!', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				throw new Exception( esc_html__( 'Course is invalid!', 'learnpress' ) );
			}

			$filters                    = new LP_Section_Filter();
			$filters->section_course_id = $course_id;
			$filters->limit             = $per_page;
			$filters->page              = $page;
			$filters->order             = $order;
			$filters->search_section    = $search;
			$filters->section_ids       = $include;
			$filters->section_not_ids   = $exclude;

			$sections = LP_Section_DB::getInstance()->get_sections_by_course_id( $filters );

			if ( is_wp_error( $sections ) ) {
				throw new Exception( $sections->get_error_message() );
			}

			if ( ! empty( $params['loadMore'] ) ) {
				foreach ( $sections['results'] as $section ) {
					$content .= learn_press_get_template_content(
						'loop/single-course/loop-section',
						compact( 'sections', 'section', 'course_id', 'filters' )
					);
				}
			} else {
				$content = learn_press_get_template_content(
					'single-course/tabs/curriculum-v2',
					compact( 'sections', 'course_id', 'filters' )
				);
			}

			if ( $section_id ) {
				$response->data->section_ids = wp_list_pluck( $sections['results'], 'section_id' );
			}

			$response->status        = 'success';
			$response->data->pages   = $sections['pages'];
			$response->data->page    = $filters->page;
			$response->data->content = $content;

			// For old value use on theme Eduma <= v4.6.0 - deprecated 4.1.6.1
			if ( defined( 'THIM_THEME_VERSION' ) && version_compare( THIM_THEME_VERSION, '4.6.3', '<=' ) ) {
				$response->pages       = $sections['pages'];
				$response->data        = $content;
				$response->section_ids = wp_list_pluck( $sections['results'], 'section_id' );
			}
		} catch ( \Throwable $th ) {
			$response->message = $th->getMessage();
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
	 * @version 1.0.1
	 * @return WP_REST_Response|WP_Error
	 */
	public function course_curriculum_items( WP_REST_Request $request ) {
		$params = $request->get_params();

		$section_id = absint( $params['sectionId'] ?? 0 );
		$per_page   = LP()->settings()->get( 'course_item_per_page', 5 );
		$page       = absint( $params['page'] ?? 1 );
		$order      = LP_Helper::sanitize_params_submitted( $params['order'] ?? 'ASC' );
		$search     = LP_Helper::sanitize_params_submitted( $params['search'] ?? '' );
		$include    = LP_Helper::sanitize_params_submitted( $params['include'] ?? array() );
		$exclude    = LP_Helper::sanitize_params_submitted( $params['exclude'] ?? array() );

		$response = new LP_REST_Response();

		try {
			if ( empty( $section_id ) ) {
				throw new Exception( esc_html__( 'Section is invalid!', 'learnpress' ) );
			}

			$filters               = new LP_Section_Items_Filter();
			$filters->section_id   = $section_id;
			$filters->limit        = $per_page;
			$filters->page         = $page;
			$filters->order        = $order;
			$filters->search_title = $search;
			$filters->item_ids     = $include;
			$filters->item_not_ids = $exclude;

			$section_items = LP_Section_DB::getInstance()->get_section_items_by_section_id( $filters );

			if ( is_wp_error( $section_items ) ) {
				throw new Exception( $section_items->get_error_message() );
			}

			$content = '';

			foreach ( $section_items['results'] as $key => $section_item ) {
				$course_id = LP_Section_DB::getInstance()->get_course_id_by_section( $section_id );

				if ( $course_id ) {
					$course_item = \LP_Course_Item::get_item( absint( $section_item['ID'] ) );

					if ( method_exists( $course_item, 'set_course' ) ) {
						$course_item->set_course( absint( $course_id ) );
					}

					$can_view_item = new LP_Model_User_Can_View_Course_Item();

					$user = learn_press_get_user( get_current_user_id() );

					if ( $user ) {
						$can_view_content_course = $user->can_view_content_course( absint( $course_id ) );
						$can_view_item           = $user->can_view_item( $section_item['ID'], $can_view_content_course );
					}

					// Ordinal numbers
					$key = absint( ( ( $page - 1 ) * $per_page ) + $key + 1 );

					$content .= learn_press_get_template_content(
						'loop/single-course/loop-section-item',
						compact( 'section_item', 'course_item', 'can_view_item', 'course_id', 'user', 'key' )
					);
				}
			}

			$response->data->pages    = $section_items['pages'];
			$response->data->page     = $filters->page;
			$response->data->content  = $content;
			$response->data->item_ids = wp_list_pluck( $section_items['results'], 'ID' );

			$response->status = 'success';
			// For old value use on theme Eduma <= v4.6.0 - deprecated 4.1.6.1
			if ( defined( 'THIM_THEME_VERSION' ) && version_compare( THIM_THEME_VERSION, '4.6.3', '<=' ) ) {
				$response->pages    = $section_items['pages'];
				$response->page     = $filters->page;
				$response->data     = $content;
				$response->item_ids = wp_list_pluck( $section_items['results'], 'ID' );
			}
		} catch ( \Throwable $th ) {
			$response->message = $th->getMessage();
		}

		return rest_ensure_response( $response );
	}
}
