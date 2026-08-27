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
			if ( ! is_array( $params ) || empty( $params['course_ids'] ) ) {
				throw new Exception( esc_html__( 'No courses selected.', 'learnpress' ) );
			}

			$course_ids = array_map( 'absint', (array) $params['course_ids'] );
			$course_ids = array_filter( $course_ids );

			if ( empty( $course_ids ) ) {
				throw new Exception( esc_html__( 'No courses selected.', 'learnpress' ) );
			}

			foreach ( $course_ids as $course_id ) {
				$filter            = new UserItemsFilter();
				$filter->item_id   = $course_id;
				$filter->item_type = LP_COURSE_CPT;
				$filter->limit     = - 1;

				$user_courses = UserItemsDB::getInstance()->get_user_items( $filter );
				if ( empty( $user_courses ) || ! is_array( $user_courses ) ) {
					continue;
				}

				foreach ( $user_courses as $user_course_data ) {
					$userCourseModel = new UserCourseModel( $user_course_data );
					$userCourseModel->reset_progress();
				}
			}

			$response->status  = Response::STATUS_SUCCESS;
			$response->message = sprintf(
				esc_html__(
					'Progress of %1$d %2$s has been reset.',
					'learnpress'
				),
				count( $course_ids ),
				_n( 'course', 'courses', count( $course_ids ), 'learnpress' )
			);
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		ini_set( 'max_execution_time', LearnPress::$time_limit_default_of_sever );

		wp_send_json( $response );
	}
}
