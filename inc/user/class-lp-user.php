<?php

/**
 * Class LP_User
 *
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

		// Set view->flag is true if course is no required enroll
		if ( $course->is_no_required_enroll() ) {
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

		// Todo: set cache - tungnx

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
	public function can_retry_course( int $course_id = 0 ): int {
		$flag = 0;

		try {
			$course = learn_press_get_course( $course_id );

			if ( ! $course ) {
				throw new Exception( 'Course is not available' );
			}

			$retake_option = (int) $course->get_data( 'retake_count' );

			if ( $retake_option > 0 ) {
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

	/**
	 * Check course is enrolled
	 *
	 * @param integer $course_id Course ID
	 * @param boolean $return_bool
	 * @return any
	 *
	 * @author Nhamdv
	 */
	public function is_course_enrolled( int $course_id, bool $return_bool = true ) {
		static $output;

		if ( ! isset( $output ) || ! is_object( $output ) ) {
			$output          = new stdClass();
			$output->check   = true;
			$output->message = '';

			try {
				$order = $this->get_course_order( $course_id, 'id', true );

				if ( empty( $order ) ) {
					throw new Exception( esc_html__( 'Order is not completed', 'learnpress' ) );
				}

				global $wpdb;

				$query  = $wpdb->prepare( "SELECT status FROM $wpdb->learnpress_user_items WHERE item_id=%d AND user_id=%d AND item_type=%s ORDER BY user_item_id DESC LIMIT 1", $course_id, $this->get_id(), LP_COURSE_CPT );
				$status = $wpdb->get_var( $query );

				if ( $status !== 'enrolled' ) {
					throw new Exception( esc_html__( 'Course is not enrolled', 'learnpress' ) );
				}
			} catch ( Throwable $th ) {
				$output->check   = false;
				$output->message = $th->getMessage();
			}
		}

		if ( $return_bool ) {
			$output = $output->check;
		}

		return apply_filters( 'learn-press/user/is-course-enrolled', $output, $course_id, $return_bool );
	}

	/**
	 * Check user can enroll course
	 *
	 * @param int  $course_id
	 * @param bool $return_bool
	 * @return mixed|object|bool
	 */
	public function can_enroll_course( int $course_id, bool $return_bool = true ) {
		$course          = learn_press_get_course( $course_id );
		$output          = new stdClass();
		$output->check   = true;
		$output->message = '';

		try {
			if ( ! $course ) {
				throw new Exception( esc_html__( 'No Course or User available', 'learnpress' ) );
			}

			if ( ! $course->is_publish() ) {
				throw new Exception( esc_html__( 'Course is not public', 'learnpress' ) );
			}

			if ( $course->get_external_link() ) {
				throw new Exception( esc_html__( 'Course is External', 'learnpress' ) );
			}

			if ( ! $course->is_in_stock() ) {
				throw new Exception( esc_html__( 'Course is full students', 'learnpress' ) );
			}

			if ( $course->is_no_required_enroll() ) {
				throw new Exception( esc_html__( 'Course is not require enrolling.', 'learnpress' ) );
			}

			if ( ! $course->is_free() && ! $this->has_purchased_course( $course_id ) ) {
				throw new Exception( esc_html__( 'Course is not purchased.', 'learnpress' ) );
			}

			if ( $this->is_course_enrolled( $course_id ) ) {
				throw new Exception( esc_html__( 'This course is already enrolled.', 'learnpress' ) );
			}
		} catch ( \Throwable $th ) {
			$output->check   = false;
			$output->message = $th->getMessage();
		}

		if ( $return_bool ) {
			$output = $output->check;
		}

		return apply_filters( 'learn-press/user/can-enroll-course', $output, $course, $return_bool );
	}

	/**
	 * Check can show purchase course button
	 *
	 * @param int $course_id
	 * @return bool
	 * @throws Exception
	 * @author nhamdv
	 */
	public function can_purchase_course( int $course_id ): bool {
		$can_purchase = false;
		$course       = learn_press_get_course( $course_id );

		try {
			if ( ! $course ) {
				throw new Exception( 'Course is unavailable' );
			}

			if ( ! $course->is_publish() ) {
				throw new Exception( 'Course is not publish' );
			}

			if ( $course->is_free() ) {
				throw new Exception( 'Course is free' );
			}

			if ( $this->can_retry_course( $course_id ) ) {
				throw new Exception( 'Course is has retake' );
			}

			// If course is reached limitation.
			if ( ! $course->is_in_stock() ) {
				$message = apply_filters(
					'learn-press/maximum-students-reach',
					esc_html__( 'This course is out of stock', 'learnpress' )
				);

				if ( $message ) {
					learn_press_display_message( $message );
				}

				throw new Exception( $message );
			}

			// If the order contains course is processing
			$order = $this->get_course_order( $course_id );
			if ( $order && $order->get_status() === 'processing' ) {
				$message = apply_filters(
					'learn-press/order-processing-message',
					__( 'Your order is waiting for processing', 'learnpress' )
				);

				if ( $message ) {
					learn_press_display_message( $message );
				}

				throw new Exception( $message );
			}

			// Allow Repurchase when course finished or block duration.
			if ( $course->allow_repurchase() && ( $this->has_finished_course( $course_id ) || 0 === $course->timestamp_remaining_duration() ) ) {
				$can_purchase = true;
			} else {
				if ( $this->has_enrolled_course( $course_id ) ) {
					throw new Exception( 'Course is has enrolled' );
				}

				// User can not purchase course
				/*
				if ( ! parent::can_purchase_course( $course_id ) ) {
					return false;
				}*/

				// If user has already purchased course but has not finished yet.
				if ( $this->has_purchased_course( $course_id ) && 'finished' !== $this->get_course_status( $course_id ) ) {
					throw new Exception( 'Course is has purchased but not finished' );
				}
			}

			$can_purchase = true;
		} catch ( Exception $e ) {

		}

		return apply_filters( 'learn-press/user/can-purchase-course', $can_purchase, $this->get_id(), $course_id );
	}

	/**
	 * Check condition show finish course button
	 *
	 * @param $course
	 * @return array
	 * @author nhamdv
	 * @editor tungnx
	 * @version 1.0.1
	 */
	public function can_show_finish_course_btn( $course ): array {
		$return = array(
			'status'  => 'fail',
			'message' => '',
		);

		try {
			if ( ! $course ) {
				throw new Exception( esc_html__( 'Error: No Course or User available.', 'learnpress' ) );
			}

			$course_id = $course->get_id();

			/**
			 * Re-calculate result course of user
			 */
			$course_data    = $this->get_course_data( $course_id );
			$course_results = $course_data->calculate_course_results();
			// End

			// Get result to check
			$is_all_completed = $this->is_completed_all_items( $course_id );

			if ( ! $this->is_course_in_progress( $course_id ) ) {
				throw new Exception( esc_html__( 'Error: Course is not in-progress.', 'learnpress' ) );
			}

			$has_finish = get_post_meta( $course_id, '_lp_has_finish', true ) ? get_post_meta( $course_id, '_lp_has_finish', true ) : 'yes';
			$is_passed  = $this->has_reached_passing_condition( $course_id );

			if ( ! $is_passed && $has_finish === 'no' ) {
				throw new Exception( esc_html__( 'Error: Course is not has finish.', 'learnpress' ) );
			}

			if ( ! $is_all_completed && $has_finish === 'yes' && ! $is_passed ) {
				throw new Exception( esc_html__( 'Error: Cannot finish course.', 'learnpress' ) );
			}

			if ( ! apply_filters( 'lp_can_finish_course', true ) ) {
				throw new Exception( esc_html__( 'Error: Filter disable finish course.', 'learnpress' ) );
			}

			$return['status'] = 'success';
		} catch ( Exception $e ) {
			$return['message'] = $e->getMessage();
		}

		return $return;
	}
}
