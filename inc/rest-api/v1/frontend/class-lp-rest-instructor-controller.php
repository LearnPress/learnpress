<?php

use LearnPress\Helpers\Template;

/**
 * REST API LP Instructor.
 *
 * @class LP_REST_Instructor_Controller
 * @author thimpress
 * @version 1.0.0
 */
class LP_REST_Instructor_Controller extends LP_Abstract_REST_Controller {
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'instructors';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_instructors' ),
				'args'                => array(
					'posts_per_page' => array(
						'required'    => false,
						'type'        => 'integer',
						'description' => 'The posts per page must be an integer',
					),
					'page'           => array(
						'required'    => false,
						'type'        => 'integer',
						'description' => 'The page must be an integer',
					),
				),
				'permission_callback' => '__return_true',
			),
		);

		parent::register_routes();
	}

	/**
	 * Get list instructor attend
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 */
	public function list_instructors( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$params = $request->get_params();
			$args   = apply_filters(
				'learnpress/instructor-list/args',
				array(
					'number'   => $params['number'] ?? 4,
					'paged'    => $params['paged'] ?? 1,
					'orderby'  => $params['orderby'] ?? 'display_name',
					'order'    => $params['order'] ?? 'asc',
					'role__in' => [ 'lp_teacher', 'administrator' ],
				)
			);

			$query = new WP_User_Query( $args );

			$instructors = $query->get_results();
			$template    = Template::instance();
			//Content
			ob_start();
			if ( empty( $instructors ) ) {
				$template->get_frontend_template(
					'instructor-list/no-instructors-found.php'
				);
			} else {
				foreach ( $instructors as $instructor ) {
					$instructor_id = $instructor->ID;
					$display_name  = $instructor->data->display_name;
					$profile       = learn_press_get_profile( $instructor_id );
					$avatar_url    = $profile->get_upload_profile_src();
					if ( empty( $avatar_url ) ) {
						$avatar_url = LearnPress::instance()->image( 'no-image.png' );
					}
					// Total course
					$lp_course_db  = LP_Course_DB::getInstance();
					$filter_course = $lp_course_db->count_courses_of_author( $instructor_id, [ 'publish' ] );
					$course_total  = $lp_course_db->get_courses( $filter_course );

					// Total student
					$lp_user_items_db = LP_User_Items_DB::getInstance();
					$filter_users     = $lp_user_items_db->count_user_attend_courses_of_author( $instructor_id );
					$student_total    = $lp_user_items_db->get_user_courses( $filter_users );
					$data             = apply_filters(
						'learnpress/instructor-list/data',
						array(
							'instructor_id' => $instructor_id,
							'display_name'  => $display_name,
							'avatar_url'    => $avatar_url,
							'course_total'  => $course_total,
							'student_total' => $student_total,
							'profile_url'   => learn_press_user_profile_link( $instructor_id ),
						)
					);

					$template->get_frontend_template(
						apply_filters(
							'learnpress/instructor-list/instructor-item',
							'instructor-list/instructor-item.php'
						),
						compact( 'data' )
					);
				}
			}
			$response->data->content = ob_get_clean();
			//Paginate
			$instructor_total = $query->get_total();

			$response->data->pagination = learn_press_get_template_content(
				'shared/pagination.php',
				array(
					'total' => intval( ceil( $instructor_total / $args['number'] ) ),
					'paged' => $args['paged'],
				)
			);

			$response->status = 'success';
		} catch ( Throwable $e ) {
			ob_end_clean();
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
