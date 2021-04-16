<?php

/**
 * Class LP_User
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_User extends LP_Abstract_User {
	/**
	 * Check the user can view content items' course
	 *
	 * @param int $course_id
	 *
	 * @return LP_Model_User_Can_View_Course_Item
	 */
	public function can_view_content_course( int $course_id = 0 ): LP_Model_User_Can_View_Course_Item {
		$view          = new LP_Model_User_Can_View_Course_Item();
		$view->message = esc_html__(
			'This content is protected, please enroll course to view this content!',
			'learnpress'
		);

		$course = learn_press_get_course( $course_id );

		if ( ! $course ) {
			return $view;
		}

		if ( $this->is_admin() || $this->is_author_of( $course_id ) ) {
			$view->flag = true;

			return $view;
		}

		if ( $course->is_publish() ) {
			$is_enrolled = $this->has_enrolled_course( $course_id );

			if ( $is_enrolled ) {
				$is_finished_course            = $this->has_finished_course( $course_id );
				$enable_block_item_when_finish = $course->enable_block_item_when_finish();

				if ( $is_finished_course && $enable_block_item_when_finish ) {
					$view->key     = LP_BLOCK_COURSE_FINISHED;
					$view->message = __(
						'You finished this course. This content is protected, please enroll course to view this content!',
						'learnpress'
					);
				} elseif ( 0 === $course->timestamp_remaining_duration() ) {
					$view->key     = LP_BLOCK_COURSE_DURATION_EXPIRE;
					$view->message = __(
						'Content of this item has blocked because the course has exceeded duration.',
						'learnpress'
					);
				} elseif ( $this->get_course_status( $course_id ) === LP_COURSE_PURCHASED ) {
					$view->key     = LP_BLOCK_COURSE_PURCHASE;
					$view->message = __(
						'This content is protected, please enroll course to view this content!',
						'learnpress'
					);
				} else {
					$view->key     = 'can_view_course';
					$view->flag    = true;
					$view->message = '';
				}
			}
		}

		//Todo: set cache - tungnx

		return apply_filters( 'learnpress/course/can-view-content', $view, $this->get_id(), $course );
	}

	/**
	 * Check the user can access to an item inside course.
	 *
	 * @param int                                $item_id Course's item Id.
	 * @param LP_Model_User_Can_View_Course_Item $view Course Id.
	 *
	 * @author  tungnx
	 * @return LP_Model_User_Can_View_Course_Item
	 * @since 4.0.0
	 */
	public function can_view_item( $item_id = 0, $view = null ): LP_Model_User_Can_View_Course_Item {
		$view_new = null;

		if ( ! $view instanceof LP_Model_User_Can_View_Course_Item ) {
			return new LP_Model_User_Can_View_Course_Item();
		}

		$item = LP_Course_Item::get_item( $item_id );

		if ( ! $item ) {
			return $view;
		}

		if ( $item instanceof LP_Course_Item && $item->is_preview() ) {
			$view_new          = clone $view; // or create new LP_Model_User_Can_View_Course_Item()
			$view_new->flag    = true;
			$view_new->key     = 'lesson_preview';
			$view_new->message = '';
		}

		if ( $view_new ) {
			$view = $view_new;
		}

		return apply_filters( 'learnpress/course/item/can-view', $view, $item );
	}

	/**
	 * Check if user can retry course.
	 *
	 * @param int $course_id .
	 *
	 * @return int
	 * @throws Exception .
	 * @since 4.0.0
	 */
	public function can_retry_course( $course_id = 0 ): int {
		$flag = 0;

		try {
			$course        = learn_press_get_course( $course_id );
			$retake_option = (int) $course->get_data( 'retake_count' );

			if ( $course && $retake_option > 0 ) {
				/**
				 * Check course is finished
				 * Check duration is blocked
				 */
				if ( ! $this->has_finished_course( $course->get_id() ) ) {
					if ( 0 !== $course->timestamp_remaining_duration() ) {
						throw new Exception();
					}
				}

				$user_course_data = $this->get_course_data( $course_id );

				if ( $user_course_data instanceof LP_User_Item_Course ) {
					$retaken          = $user_course_data->get_retaken_count();
					$can_retake_times = $retake_option - $retaken;

					if ( $can_retake_times > 0 ) {
						$flag = $can_retake_times;
					}
				}
			}
		} catch ( Exception $e ) {

		}

		return apply_filters( 'learn-press/user/course/can-retry', $flag, $this->get_id(), $course_id );
	}
}
