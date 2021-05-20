<?php

/**
 * Class LP_REST_Users_Controller
 * in LearnPres > Tool
 *
 * @since 4.0.4
 * @author Nhamdv <email@email.com>
 */
class LP_REST_Admin_Reset_Data_Controller extends LP_Abstract_REST_Controller {

	public function __construct() {
		$this->namespace = 'lp/v1/admin/tools';
		$this->rest_base = 'reset-data';

		parent::__construct();
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			'search-courses' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_courses' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'reset-courses'  => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'reset_courses' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
		);

		parent::register_routes();
	}

	public function check_admin_permission() {
		return LP_REST_Authentication::check_admin_permission();
	}

	/**
	 * Search Course for Reset Course
	 *
	 * @param WP_REST_Request $request
	 * @return void
	 */
	public function search_courses( $request ) {
		$params = $request->get_params();
		$s      = ! empty( $params['s'] ) ? $params['s'] : '';

		// Response.
		$response       = new LP_REST_Response();
		$response->data = '';

		try {
			global $wpdb;

			$query = $wpdb->prepare(
				"SELECT ID as id, post_title AS title, 'students', '' AS status
				FROM {$wpdb->posts}
				WHERE post_type = %s AND post_title LIKE %s",
				LP_COURSE_CPT,
				'%' . $wpdb->esc_like( $s ) . '%'
			);

			$courses = array();
			$rows    = $wpdb->get_results( $query );

			if ( $rows ) {
				$course_ids = wp_list_pluck( $rows, 'id' );
				$format     = array_fill( 0, count( $course_ids ), '%d' );
				$args       = $course_ids;
				$args[]     = LP_COURSE_CPT;
				$query      = $wpdb->prepare( "SELECT item_id FROM {$wpdb->learnpress_user_items} WHERE item_id IN(" . implode( ',', $format ) . ') AND item_type = %s', $args );

				$item_ids = $wpdb->get_col( $query );

				if ( $item_ids ) {
					for ( $n = count( $rows ), $i = $n - 1; $i >= 0; $i -- ) {
						if ( ! in_array( $rows[ $i ]->id, $item_ids ) ) {
							unset( $rows[ $i ] );
						}
					}
				} else {
					throw new Exception( esc_html__( 'No items available!', 'learnpress' ) );
				}

				if ( $rows ) {
					foreach ( $rows as $k => $row ) {
						$course         = learn_press_get_course( $row->id );
						$count_in_order = $course->count_in_order();
						$row->students  = $count_in_order;

						if ( $row->students ) {
							$courses[] = $row;
						}
					}
				}
			} else {
				throw new Exception( esc_html__( 'No courses available!', 'learnpress' ) );
			}

			$response->status = 'success';
			$response->data   = $courses;
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Get Final Quiz in Course Settings.
	 *
	 * @return void
	 */
	public function reset_courses( WP_REST_Request $request ) {
		$params    = $request->get_params();
		$course_id = isset( $params['courseId'] ) ? $params['courseId'] : false;

		// Responsive.
		$response       = new LP_REST_Response();
		$response->data = '';

		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No course ID available', 'learnpress' ) );
			}

			global $wpdb;

			// GET list user_item_id child of parent course ids.
			$child_user_item_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT user_item_id FROM $wpdb->learnpress_user_items
					WHERE ref_id=%d
					",
					$course_id
				)
			);

			// DELETE course_item ( lesson, quiz ) by course id in table user_items.
			$wpdb->delete(
				$wpdb->learnpress_user_items,
				array(
					'ref_id' => $course_id,
				),
				array( '%d' )
			);

			// DELETE user_itemmeta for ( lesson, quiz... )
			if ( ! empty( $child_user_item_ids ) ) {
				foreach ( $child_user_item_ids as $child_user_item_id ) {
					$wpdb->delete(
						$wpdb->learnpress_user_itemmeta,
						array(
							'learnpress_user_item_id' => absint( $child_user_item_id ),
						),
						array( '%d' )
					);

					LP_User_Items_Result_DB::instance()->delete( absint( $child_user_item_id ) );
				}
			}

			// Change status, graduation... by course id
			$wpdb->update(
				$wpdb->learnpress_user_items,
				array(
					'status'     => LP_COURSE_ENROLLED,
					'graduation' => 'in-progress',
					'start_time' => current_time( 'mysql', true ),
					'end_time'   => null,
				),
				array(
					'item_id' => $course_id,
				),
				array( '%s', '%s', '%s', null ),
				array( '%d' )
			);

			// GET all user_item_ids by course ids
			$user_item_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT user_item_id FROM $wpdb->learnpress_user_items
					WHERE item_id=%d
					",
					$course_id
				)
			);

			// DELETE user_itemmeta course by course id.
			if ( ! empty( $user_item_ids ) ) {
				foreach ( $user_item_ids as $user_item_id ) {
					$wpdb->delete(
						$wpdb->learnpress_user_itemmeta,
						array(
							'learnpress_user_item_id' => absint( $user_item_id ),
						),
						array( '%d' )
					);

					LP_User_Items_Result_DB::instance()->delete( absint( $user_item_id ) );
				}
			}

			$response->status = 'success';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}
}
