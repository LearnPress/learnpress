<?php
/**
 * class AjaxBase
 *
 * @since 4.2.7.6
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserItems\UserLessonModel;
use LP_Helper;
use LP_Request;
use LP_REST_Response;
use stdClass;
use Throwable;
use WP_Error;

class LessonAjax extends AbstractAjax {
	/**
	 * User complete lesson ajax handler.
	 *
	 * @since 4.2.7.6
	 * @version 1.0.1
	 * @return void
	 */
	public function user_complete_lesson() {
		$link_continue = '';
		$message_data  = [
			'status'  => 'error',
			'content' => '',
		];

		try {
			$lesson_id = LP_Request::get_param( 'lesson_id', 0, 'int', 'post' );
			$course_id = LP_Request::get_param( 'course_id', 0, 'int', 'post' );

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course is invalid!', 'learnpress' ) );
			}

			$lessonModel = LessonPostModel::find( $lesson_id, true );
			if ( ! $lessonModel ) {
				throw new Exception( __( 'Lesson is invalid!', 'learnpress' ) );
			}

			$userLessonModel = UserLessonModel::find_user_item(
				get_current_user_id(),
				$lesson_id,
				$lessonModel->post_type,
				$course_id,
				LP_COURSE_CPT,
				true
			);
			if ( ! $userLessonModel instanceof UserLessonModel ) {
				throw new Exception( __( 'You have not started lesson', 'learnpress' ) );
			}

			$userLessonModel->set_complete();
			$userCourseModel = $userLessonModel->get_user_course_model();
			$item_model_next = $userCourseModel->get_item_next_when_complete_lesson( $lesson_id );
			if ( $item_model_next ) {
				$link_continue = $courseModel->get_item_link( $item_model_next->ID, $item_model_next->post_type );
			} else {
				$link_continue = $courseModel->get_permalink();
			}

			$message_data['status']  = 'success';
			$message_data['content'] = sprintf(
				__( 'Congrats! You have completed "%s".', 'learnpress' ),
				$lessonModel->get_the_title()
			);
		} catch ( Throwable $e ) {
			$message_data['content'] = $e->getMessage();
			if ( isset( $courseModel ) && isset( $lessonModel ) ) {
				$link_continue = $courseModel->get_item_link( $lessonModel->ID, $lessonModel->post_type );
			}
		}

		learn_press_set_message( $message_data );

		wp_safe_redirect( $link_continue );
		die();
	}
}
