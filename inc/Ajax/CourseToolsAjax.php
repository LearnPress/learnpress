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
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
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

	/**
	 * Reset progress for selected course items (lesson, quiz, etc.).
	 *
	 * @return void
	 * @since 4.4.6
	 * @version 1.0.0
	 */
	public function reset_progress_items_course() {
		$response = new Response();

		ini_set( 'max_execution_time', 0 );
		try {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception(
					esc_html__( 'You do not have permission to reset item progress.', 'learnpress' )
				);
			}

			$params = LP_Helper::json_decode( LP_Request::get_param( 'data' ), true );
			if ( ! is_array( $params ) ) {
				throw new Exception( esc_html__( 'Data invalid.', 'learnpress' ) );
			}

			$user_item_ids = array_map( 'absint', (array) ( $params['user_item_ids'] ?? [] ) );
			$reset_all     = $params['reset_all'] ?? false;
			$search_item   = trim( $params['lp-search-item'] ?? '' );
			$search_course = trim( $params['lp-search-course'] ?? '' );
			$search_user   = trim( $params['lp-search-user'] ?? '' );
			$item_type     = trim( $params['lp-item-type'] ?? '' );

			// Query
			$db            = UserItemsDB::getInstance();
			$filter        = new UserItemsFilter();
			$filter->limit = -1;

			// Filter by ref_type = course (items belong to a course)
			$filter->ref_type = LP_COURSE_CPT;

			// Get only user_id > 0
			$filter->where[] = 'AND ui.user_id > 0';

			// Filter by item type
			if ( ! empty( $item_type ) ) {
				$filter->item_type = $item_type;
			} else {
				$item_types   = CourseModel::item_types_support();
				$types_format = LP_Helper::db_format_array( $item_types, '%s' );
				// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$filter->where[] = $db->wpdb->prepare(
					'AND ui.item_type IN (' . $types_format . ')',
					...$item_types
				);
				// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			}

			if ( $reset_all ) {
				// Search item by title
				if ( ! empty( $search_item ) ) {
					$filter->join[]  = "INNER JOIN {$db->tb_posts} AS p ON ui.item_id = p.ID";
					$filter->where[] = $db->wpdb->prepare(
						'AND p.post_title LIKE %s',
						'%' . $db->wpdb->esc_like( $search_item ) . '%'
					);
				}

				// Search course by title
				if ( ! empty( $search_course ) ) {
					$filter->join[]  = "INNER JOIN {$db->tb_posts} AS pc ON ui.ref_id = pc.ID";
					$filter->where[] = $db->wpdb->prepare(
						'AND pc.post_title LIKE %s',
						'%' . $db->wpdb->esc_like( $search_course ) . '%'
					);
				}

				// Search user
				if ( ! empty( $search_user ) ) {
					$filter->join[]  = "INNER JOIN {$db->tb_users} AS u ON ui.user_id = u.ID";
					$esc_search_user = '%' . $db->wpdb->esc_like( $search_user ) . '%';
					$filter->where[] = $db->wpdb->prepare(
						'AND (u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s)',
						$esc_search_user,
						$esc_search_user,
						$esc_search_user
					);
				}
			} else {
				if ( empty( $user_item_ids ) ) {
					throw new Exception( esc_html__( 'No data reset.', 'learnpress' ) );
				}

				$filter->user_item_ids = $user_item_ids;
			}
			// End Query

			$total_rows = 0;
			$userItems  = $db->get_user_items( $filter, $total_rows );
			if ( empty( $userItems ) ) {
				throw new Exception( esc_html__( 'No data reset.', 'learnpress' ) );
			}

			foreach ( $userItems as $userItemObj ) {
				$userItemModel = new UserItemModel( $userItemObj );
				$userItemModel->delete();
			}

			$response->status  = Response::STATUS_SUCCESS;
			$response->message = sprintf(
				esc_html__(
					'Progress of %1$d %2$s has been reset.',
					'learnpress'
				),
				$total_rows,
				__( 'item(s)', 'learnpress' )
			);
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		ini_set( 'max_execution_time', LearnPress::$time_limit_default_of_sever );

		wp_send_json( $response );
	}
}
