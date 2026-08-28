<?php
/**
 * Class CourseToolsAjax
 *
 * Handle admin reset course progress via LearnPress AJAX dispatcher.
 *
 * @since 4.4.6
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use Exception;
use LearnPress;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Helpers\Response;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LP_Helper;
use LP_Request;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class CourseToolsAjax
 */
class CourseToolsAjax extends AbstractAjax {
	/**
	 * Register AJAX callbacks with the abstract dispatcher.
	 *
	 * @return void
	 */
	public static function catch_lp_ajax() {
		parent::catch_lp_ajax();
	}

	/**
	 * Reset progress for all users enrolled in the selected courses.
	 *
	 * @return void
	 */
	public function reset_progress_courses() {
		$response = new Response();

		ini_set( 'max_execution_time', 0 );
		try {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception(
					esc_html__( 'You do not have permission to reset course progress.', 'learnpress' )
				);
			}

			$params = LP_Helper::json_decode( LP_Request::get_param( 'data' ), true );
			if ( ! is_array( $params ) ) {
				throw new Exception( esc_html__( 'Data invalid.', 'learnpress' ) );
			}

			$user_item_ids = array_map( 'absint', (array) ( $params['user_item_ids'] ?? [] ) );
			$reset_all     = $params['reset_all'] ?? false;
			$search_user   = trim( $params['lp-search-user'] ?? '' );
			$search_course = trim( $params['lp-search-course'] ?? '' );

			// Query
			$db            = UserItemsDB::getInstance();
			$filter        = new UserItemsFilter();
			$filter->limit = -1;

			// Get only courses has items attendance
			$filter_course_attendance                      = new UserItemsFilter();
			$filter_course_attendance->only_fields         = [ 'parent_id' ];
			$filter_course_attendance->ref_type            = LP_COURSE_CPT;
			$filter_course_attendance->return_string_query = 1;
			$filter_course_attendance->limit               = -1;
			$query_course_attendance                       = $db->get_user_items( $filter_course_attendance );
			$filter->where[]                               = "AND ui.user_item_id IN ({$query_course_attendance})";
			// End get only courses has items attendance

			// Search user
			if ( ! empty( $search_user ) && $reset_all ) {
				$filter->join[]  = "INNER JOIN {$db->tb_users} AS u ON ui.user_id = u.ID";
				$esc_search_user = '%' . $db->wpdb->esc_like( $search_user ) . '%';
				$filter->where[] = $db->wpdb->prepare(
					'AND (u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s)',
					$esc_search_user,
					$esc_search_user,
					$esc_search_user
				);
			}

			// Search course
			if ( ! empty( $search_course ) && $reset_all ) {
				$filter->only_fields[] = 'post_title';
				$filter->join[]        = "INNER JOIN {$db->tb_lp_courses} AS c ON ui.item_id = c.ID";
				$filter->where[]       = $db->wpdb->prepare(
					'AND c.post_title LIKE %s',
					'%' . $db->wpdb->esc_like( $search_course ) . '%'
				);
			}

			if ( ! $reset_all ) {
				$filter->user_item_ids = $user_item_ids;
			}
			// End Query

			$total_rows  = 0;
			$userCourses = $db->get_user_items( $filter, $total_rows );
			if ( empty( $userCourses ) ) {
				throw new Exception( esc_html__( 'No data reset.', 'learnpress' ) );
			}

			foreach ( $userCourses as $userCourseObj ) {
				$userCourseModel = new UserCourseModel( $userCourseObj );

				$userCourseModel->reset_progress();
			}

			$response->status  = Response::STATUS_SUCCESS;
			$response->message = sprintf(
				esc_html__(
					'Progress of %1$d %2$s has been reset.',
					'learnpress'
				),
				$total_rows,
				__( 'data', 'learnpress' )
			);
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		ini_set( 'max_execution_time', LearnPress::$time_limit_default_of_sever );

		wp_send_json( $response );
	}
}
