<?php

/**
 * Class LP_User
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0.1
 */
class LP_User extends LP_Abstract_User {
	/**
	 * Check the user can view content items' course
	 *
	 * @param int $course_id
	 *
	 * @return LP_Model_User_Can_View_Course_Item
	 * @throws Exception
	 */
	public function can_view_content_course( int $course_id = 0 ): LP_Model_User_Can_View_Course_Item {
		$view          = new LP_Model_User_Can_View_Course_Item();
		$view->message = esc_html__(
			'This content is protected. Please enroll in the course to view this content!',
			'learnpress'
		);

		$course = learn_press_get_course( $course_id );
		if ( ! $course ) {
			return $view;
		}

		if ( $this->is_admin() || $this->is_author_of( $course_id ) ) {
			$view->flag    = true;
			$view->message = 'User can view because is Admin or author of course';

			return $view;
		}

		// Set view->flag is true if course is no required enroll
		if ( $course->is_no_required_enroll() ) {
			$view->flag = true;

			return $view;
		} elseif ( ! is_user_logged_in() ) {
			$view->message = __(
				'This content is protected. Please log in or enroll in the course to view this content!',
				'learnpress'
			);

			return $view;
		}

		if ( $course->is_publish() ) {
			$is_enrolled_or_finished = $this->has_enrolled_or_finished( $course_id );

			if ( $is_enrolled_or_finished ) {
				$is_finished_course            = $this->has_finished_course( $course_id );
				$enable_block_item_when_finish = $course->enable_block_item_when_finish();

				if ( $is_finished_course && $enable_block_item_when_finish ) {
					$view->key     = LP_BLOCK_COURSE_FINISHED;
					$view->message = __(
						'You finished this course. This content is protected. Please enroll in the course to view this content!',
						'learnpress'
					);
				} elseif ( 0 === $course->timestamp_remaining_duration() ) {
					$view->key     = LP_BLOCK_COURSE_DURATION_EXPIRE;
					$view->message = __(
						'The content of this item has been blocked because the course has exceeded its duration.',
						'learnpress'
					);
				} elseif ( $this->get_course_status( $course_id ) === LP_COURSE_PURCHASED ) {
					$view->key     = LP_BLOCK_COURSE_PURCHASE;
					$view->message = __(
						'This content is protected. Please enroll in the course to view this content!',
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
	 * @param int $item_id Course's item Id.
	 * @param LP_Model_User_Can_View_Course_Item $view Course Id.
	 *
	 * @author  tungnx
	 * @return LP_Model_User_Can_View_Course_Item
	 * @since 4.0.0
	 */
	public function can_view_item( int $item_id = 0, $view = null ): LP_Model_User_Can_View_Course_Item {
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

		return apply_filters( 'learnpress/course/item/can-view', $view, $item, $this );
	}

	/**
	 * Check if user can retry course.
	 *
	 * @param int $course_id .
	 *
	 * @return int
	 * @since 4.0.0
	 * @author tungnx
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
	 * Return true if user has already purchased course
	 *
	 * @param int $course_id
	 *
	 * @return bool
	 * @editor tungnx
	 * @modify 4.1.3
	 * @throws Exception
	 */
	public function has_purchased_course( int $course_id ): bool {
		$user_course = $this->get_course_data( $course_id );

		return apply_filters( 'learn-press/user-purchased-course', $user_course && $user_course->is_purchased(), $course_id, $this->get_id() );
	}

	/**
	 * Check course is enrolled
	 *
	 * @param integer $course_id Course ID
	 * @param boolean $return_bool
	 *
	 * @return bool|object
	 * @editor tungnx
	 * @since 4.1.2
	 * @version 1.0.2
	 *
	 * @author Nhamdv
	 */
	public function has_enrolled_course( int $course_id, bool $return_bool = true ) {
		$result_check          = new stdClass();
		$result_check->check   = true;
		$result_check->message = '';

		try {
			/*$order = $this->get_course_order( $course_id );

			if ( ! $order || ! $order->is_completed() ) {
				throw new Exception( esc_html__( 'Order is not completed', 'learnpress' ) );
			}*/

			$user_course = $this->get_course_data( $course_id );
			if ( ! $user_course || ! $user_course->is_enrolled() ) {
				throw new Exception( esc_html__( 'The course is not enrolled.', 'learnpress' ) );
			}
		} catch ( Throwable $th ) {
			$result_check->check   = false;
			$result_check->message = $th->getMessage();
		}

		return apply_filters( 'learn-press/user/is-course-enrolled', $return_bool ? $result_check->check : $result_check, $course_id, $return_bool );
	}

	/**
	 * Return true if user has finished a course
	 *
	 * @param int $course_id .
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function has_finished_course( int $course_id ): bool {
		$user_course = $this->get_course_data( $course_id );

		return apply_filters( 'learn-press/user-has-finished-course', $user_course && $user_course->is_finished(), $this->get_id(), $course_id );
	}

	/**
	 * Check course of user is enrolled or finished
	 *
	 * @param int $course_id
	 *
	 * @return bool
	 */
	public function has_enrolled_or_finished( int $course_id ): bool {
		$status = true;

		try {
			$user_course = $this->get_course_data( $course_id );

			if ( ! $user_course ) {
				$status = false;
			} elseif ( ! $user_course->is_enrolled() && ! $user_course->is_finished() ) {
				$status = false;
			}
		} catch ( Throwable $e ) {
			$status = false;
		}

		return apply_filters( 'learn-press/user-has-finished-course', $status, $this->get_id(), $course_id );
	}

	/**
	 * Check user can enroll course
	 *
	 * @param int $course_id
	 * @param bool $return_bool
	 *
	 * @return mixed|object|bool
	 */
	public function can_enroll_course( int $course_id, bool $return_bool = true ) {
		$course          = learn_press_get_course( $course_id );
		$output          = new stdClass();
		$output->check   = true;
		$output->message = '';

		try {
			$is_no_required_enroll = $course->get_data( 'no_required_enroll', 'no' ) === 'yes';
			if ( ! $course ) {
				$output->code = 'course_unavailable';
				throw new Exception( esc_html__( 'No Course or User available', 'learnpress' ) );
			}

			if ( ! $course->is_publish() ) {
				$output->code = 'course_not_publish';
				throw new Exception( esc_html__( 'The course is not public', 'learnpress' ) );
			}

			if ( $this->has_enrolled_course( $course_id ) ) {
				$output->code = 'course_is_enrolled';
				throw new Exception( esc_html__( 'This course is already enrolled.', 'learnpress' ) );
			}

			if ( ! $course->is_in_stock_enroll() && ! $this->has_purchased_course( $course_id )
				&& ! $this->has_enrolled_or_finished( $course_id ) && ! $is_no_required_enroll ) {
				$output->code = 'course_out_of_stock';
				throw new Exception( esc_html__( 'The course is full of students.', 'learnpress' ) );
			}

			if ( $course->get_external_link()
				&& ! $this->has_purchased_course( $course_id )
				&& ! $course->is_offline() ) {
				$output->code = 'course_is_external';
				throw new Exception( esc_html__( 'The course is external', 'learnpress' ) );
			}

			if ( $this->can_retry_course( $course_id ) ) {
				$output->code = 'course_can_retry';
				throw new Exception( esc_html__( 'Course can retake.', 'learnpress' ) );
			}

			if ( $is_no_required_enroll && ! is_user_logged_in() ) {
				$output->code = 'course_is_no_required_enroll_not_login';
				throw new Exception(
					esc_html__( 'Enrollment in the course is not mandatory. You can access materials for learning or to take quizzes now.', 'learnpress' )
				);
			}

			if ( ! $course->is_free() && ! $is_no_required_enroll && ! $this->has_purchased_course( $course_id ) ) {
				$output->code = 'course_is_not_purchased';
				throw new Exception( esc_html__( 'The course is not purchased.', 'learnpress' ) );
			}
		} catch ( \Throwable $th ) {
			$output->check   = false;
			$output->message = $th->getMessage();
		}

		if ( $return_bool ) {
			$output = $output->check;
		}

		return apply_filters( 'learn-press/user/can-enroll-course', $output, $course, $return_bool, $this );
	}

	/**
	 * Check can show purchase course button
	 *
	 * @param int $course_id
	 *
	 * @return bool|WP_Error
	 * @author nhamdv
	 * @editor tungnx
	 * @since 4.0.8
	 * @version 1.0.5
	 */
	public function can_purchase_course( int $course_id = 0 ) {
		$can_purchase = true;
		$course       = learn_press_get_course( $course_id );
		$code_err     = '';

		try {
			$can_enroll_course = $this->can_enroll_course( $course_id, false );
			if ( ! $can_enroll_course->check &&
				! in_array( $can_enroll_course->code, [ 'course_is_not_purchased', 'course_is_enrolled' ] ) ) {
				$code_err = $can_enroll_course->code;
				throw new Exception( $can_enroll_course->message );
			}

			if ( $course->is_free() ) {
				$code_err = 'course_is_free';
				throw new Exception( __( 'Course is free, so you can not purchase', 'learnpress' ) );
			}

			// Course is not require enrolling.
			// Not use $course->is_no_required_enroll() because it is not correct, it check with user logged.
			if ( $course->get_data( 'no_required_enroll', 'no' ) === 'yes' ) {
				$code_err = 'no_required_enroll';
				throw new Exception(
					__(
						'Enrollment in the course is not mandatory. You can access materials for learning or to take quizzes now.',
						'learnpress'
					)
				);
			}

			// If the order contains course is processing
			$order = $this->get_course_order( $course_id );
			if ( $order && $order->get_status() === LP_ORDER_PROCESSING ) {
				$code_err = 'order_processing';
				throw new Exception( __( 'Your order is waiting for processing', 'learnpress' ) );
			}

			if ( $this->has_purchased_course( $course_id ) ) {
				$code_err = 'course_purchased';
				throw new Exception( __( 'Course is purchased', 'learnpress' ) );
			}

			$is_blocked_course  = 0 === $course->timestamp_remaining_duration();
			$is_enrolled_course = $this->has_enrolled_course( $course_id );
			if ( $course->allow_repurchase() ) {
				if ( $is_enrolled_course && ! $is_blocked_course ) {
					$code_err = 'course_is_enrolled';
					throw new Exception( 'Course is enrolled' );
				}
			} else {
				if ( $this->has_enrolled_or_finished( $course_id ) ) {
					$code_err = 'course_is_enrolled_or_finished';
					throw new Exception( __( 'Course is enrolled or finished', 'learnpress' ) );
				}
			}
		} catch ( Throwable $e ) {
			$can_purchase = new WP_Error( $code_err, $e->getMessage() );
		}

		return apply_filters( 'learn-press/user/can-purchase-course', $can_purchase, $this->get_id(), $course_id );
	}

	/**
	 * Check condition show finish course button
	 *
	 * @param $course
	 *
	 * @return array
	 * @author nhamdv
	 * @editor tungnx
	 * @version 1.0.2
	 */
	public function can_show_finish_course_btn( $course ): array {
		$return = array(
			'status'  => 'success',
			'message' => '',
		);

		try {
			if ( ! $course ) {
				throw new Exception( esc_html__( 'Error: No Course or User available.', 'learnpress' ) );
			}

			$course_id = $course->get_id();

			if ( $this->has_finished_course( $course_id ) ) {
				throw new Exception( esc_html__( 'The course has finished.', 'learnpress' ) );
			}

			if ( ! $this->has_enrolled_course( $course_id ) ) {
				throw new Exception( esc_html__( 'The course is not enrolled.', 'learnpress' ) );
			}

			if ( ! $this->is_course_in_progress( $course_id ) ) {
				throw new Exception( esc_html__( 'Error: The course is not in progress.', 'learnpress' ) );
			}

			$course_data = $this->get_course_data( $course_id );

			if ( $course_data ) {
				$course_result = $course_data->get_result();

				if ( ! $course_result['pass'] ) {
					// Get option Can finish course if completed all item without pass
					$can_finish = get_post_meta( $course_id, '_lp_has_finish', true ) ?? 'yes';

					if ( $can_finish == 'yes' ) {
						// Check completed all items
						$is_all_completed = $this->is_completed_all_items( $course_id );
						if ( ! $is_all_completed ) {
							throw new Exception( 'All items not completed and Course not pass' );
						}
					} else {
						throw new Exception( 'Course not pass' );
					}
				}
			}

			if ( ! apply_filters( 'lp_can_finish_course', true ) ) {
				throw new Exception( esc_html__( 'Error: Filter the disabled finished courses.', 'learnpress' ) );
			}
		} catch ( Exception $e ) {
			$return['status']  = 'false';
			$return['message'] = $e->getMessage();
		}

		return $return;
	}

	/**
	 * Start quiz for the user.
	 *
	 * @param int $quiz_id
	 * @param int $course_id
	 * @param bool $wp_error Optional. Whether to return a WP_Error on failure. Default false.
	 *
	 * @return LP_User_Item_Quiz|bool|WP_Error
	 * @throws Exception
	 */
	public function start_quiz( int $quiz_id, int $course_id = 0, bool $wp_error = false ) {
		try {
			$item_is_preview = learn_press_get_request( 'lp-preview' );
			if ( $item_is_preview ) {
				throw new Exception( __( 'You cannot start a quiz in preview mode.', 'learnpress' ) );
			}

			// Validate course and quiz
			$course_id = $this->_verify_course_item( $quiz_id, $course_id );
			if ( ! $course_id ) {
				throw new Exception(
					__( 'The course does not exist or does not contain a quiz.', 'learnpress' ),
					LP_INVALID_QUIZ_OR_COURSE
				);
			}

			$course = learn_press_get_course( $course_id );
			// If user has already finished the course
			if ( $this->has_finished_course( $course_id ) ) {
				throw new Exception(
					__( 'You have already finished the course of this quiz', 'learnpress' ),
					LP_COURSE_IS_FINISHED
				);
			}

			if ( ! $this->has_enrolled_course( $course_id ) || ! $this->is_course_in_progress( $course_id ) ) {
				if ( ! $course->is_no_required_enroll() ) {
					throw new Exception(
						__( 'Please enroll in the course before starting the quiz.', 'learnpress' ),
						LP_COURSE_IS_FINISHED
					);
				}
			}

			// Check if user has already started or completed quiz
			if ( $this->has_item_status( array( 'started', 'completed' ), $quiz_id, $course_id ) ) {
				throw new Exception(
					__( 'The user has started or completed the quiz.', 'learnpress' ),
					LP_QUIZ_HAS_STARTED_OR_COMPLETED
				);
			}

			$user_current = learn_press_get_current_user();
			if ( $user_current->is_guest() ) {
				// if course required enroll => print message "You have to login for starting quiz"
				if ( ! $course->is_no_required_enroll() ) {
					throw new Exception( __( 'You have to log in to start the quiz.', 'learnpress' ), LP_REQUIRE_LOGIN );
				}
			}

			/**
			 * Hook can start quiz
			 *
			 * @see learn_press_hk_before_start_quiz
			 */
			$can_start_quiz = apply_filters(
				'learn-press/can-start-quiz',
				true,
				$quiz_id,
				$course_id,
				$this->get_id()
			);

			if ( ! $can_start_quiz ) {
				return false;
			}

			$user_quiz = learn_press_user_start_quiz( $quiz_id, false, $course_id, $wp_error );

			/**
			 * Hook quiz started
			 *
			 * @since 3.0.0
			 */
			do_action( 'learn-press/user/quiz-started', $quiz_id, $course_id, $this->get_id() );

			// $return = $user_quiz->get_mysql_data();
			$return = $user_quiz;
		} catch ( Throwable $ex ) {
			$return = $wp_error ? new WP_Error( $ex->getCode(), $ex->getMessage() ) : false;
		}

		return $return;
	}

	/**
	 * Finish a quiz for the user and save all data needed
	 *
	 * @param int $quiz_id
	 * @param int $course_id
	 * @param bool $wp_error
	 *
	 * @return LP_User_Item_Quiz|bool|WP_Error
	 */
	public function finish_quiz( int $quiz_id, int $course_id, bool $wp_error = false ) {
		$return = false;

		try {
			// If user has already finished the course
			if ( $this->has_finished_course( $course_id ) ) {
				throw new Exception(
					__( 'The user has already finished the course of this quiz.', 'learnpress' ),
					LP_COURSE_IS_FINISHED
				);

			}

			// Check if user has already started or completed quiz
			if ( $this->has_item_status( array( 'completed' ), $quiz_id, $course_id ) ) {
				throw new Exception(
					__( 'The user has completed the quiz', 'learnpress' ),
					LP_QUIZ_HAS_STARTED_OR_COMPLETED
				);
			}

			/**
			 * @var LP_User_Item_Quiz $user_quiz
			 */
			$user_quiz = $this->get_item_data( $quiz_id, $course_id );
			$user_quiz->complete();

			do_action( 'learn-press/user/quiz-finished', $quiz_id, $course_id, $this->get_id() );
		} catch ( Exception $ex ) {
			$return = $wp_error ? new WP_Error( $ex->getCode(), $ex->getMessage() ) : true;
		}

		return $return;
	}

	/**
	 * Retake a quiz for the user
	 *
	 * @param int $quiz_id
	 * @param int $course_id
	 * @param bool $wp_error
	 *
	 * @return bool|WP_Error|LP_User_Item_Quiz
	 *
	 * @throws Exception
	 */
	public function retake_quiz( int $quiz_id, int $course_id, bool $wp_error = false ) {
		$return = false;

		try {
			$course_id = $this->_verify_course_item( $quiz_id, $course_id );

			if ( false === $course_id ) {
				throw new Exception(
					sprintf(
						__(
							'The course does not exist or does not contain a quiz.',
							'learnpress'
						),
						__CLASS__,
						__FUNCTION__
					),
					LP_INVALID_QUIZ_OR_COURSE
				);
			}

			// If user has already finished the course.
			if ( $this->has_finished_course( $course_id ) ) {
				throw new Exception(
					sprintf(
						__( 'You can not redo a quiz in a finished course.', 'learnpress' ),
						__CLASS__,
						__FUNCTION__
					),
					LP_COURSE_IS_FINISHED
				);

			}

			// Check if user has already started or completed quiz
			if ( ! $this->has_item_status( array( 'completed' ), $quiz_id, $course_id ) ) {
				throw new Exception(
					sprintf(
						__( '%1$s::%2$s - The user has not completed the quiz.', 'learnpress' ),
						__CLASS__,
						__FUNCTION__
					),
					LP_QUIZ_HAS_STARTED_OR_COMPLETED
				);
			}

			$allow_attempts = learn_press_get_quiz_max_retrying( $quiz_id, $course_id );

			if ( ! $this->has_retake_quiz( $quiz_id, $course_id ) ) {
				throw new Exception(
					sprintf(
						__( '%1$s::%2$s - Your quiz can\'t be retaken.', 'learnpress' ),
						__CLASS__,
						__FUNCTION__
					),
					LP_QUIZ_HAS_STARTED_OR_COMPLETED
				);
			}

			$return = learn_press_user_retake_quiz( $quiz_id, false, $course_id, $wp_error );

			do_action( 'learn-press/user/quiz-retried', $quiz_id, $course_id, $this->get_id() );
		} catch ( Exception $ex ) {
			$return = $wp_error ? new WP_Error( $ex->getCode(), $ex->getMessage() ) : false;
			do_action( 'learn-press/user/retake-quiz-failure', $quiz_id, $course_id, $this->get_id() );
		}

		return $return;
	}

	/**
	 * Get quiz's user learning or completed
	 *
	 * @param LP_User_Items_Filter $filter
	 *
	 * @return LP_Query_List_Table
	 */
	public function get_user_quizzes( LP_User_Items_Filter $filter ): LP_Query_List_Table {
		$quizzes = [
			'total' => 0,
			'items' => [],
			'pages' => 0,
		];

		try {
			$user_quizzes = LP_User_Items_DB::getInstance()->get_user_quizzes( $filter );

			if ( $user_quizzes ) {
				$count = LP_User_Items_DB::getInstance()->wpdb->get_var( 'SELECT FOUND_ROWS()' );

				$quizzes['total'] = $count;
				$quizzes['pages'] = ceil( $count / $filter->limit );

				foreach ( $user_quizzes as $item ) {
					$quizzes['items'][] = new LP_User_Item_Quiz( $item );
				}
			}

			$quizzes['single'] = __( 'quiz', 'learnpress' );
			$quizzes['plural'] = __( 'quizzes', 'learnpress' );
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}

		return new LP_Query_List_Table( $quizzes );
	}

	/**
	 * Set item is viewing in single course.
	 *
	 * @param LP_Course_Item $item
	 *
	 * @return int|LP_Course_Item
	 * @since 4.1.6.9 - move from LP_Course
	 * @editor tungnx
	 * @version 1.0.0
	 */
	public function set_viewing_item( LP_Course_Item $item ) {
		$flag = false;

		try {
			$user = learn_press_get_current_user();
			if ( $user instanceof LP_User_Guest ) {
				return $flag;
			}

			$course_id   = $item->get_course_id();
			$item_id     = $item->get_id();
			$course_data = $this->get_course_data( $course_id );

			if ( $course_data && $course_data->is_enrolled() ) {
				$item = $course_data->get_item( $item_id );

				if ( ! $item ) {
					$item = LP_User_Item::get_item_object( $item_id );

					if ( ! $item ) {
						return $flag;
					}

					if ( $item instanceof LP_User_Item_Quiz ) {
						return $flag;
					}

					$item->set_ref_id( $course_id );
					$item->set_parent_id( $course_data->get_user_item_id() );

					$flag = $item->update();
				}
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $flag;
	}

	/**
	 * Get statistic info of student user
	 *
	 * @return array
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function get_student_statistic(): array {
		$user      = $this;
		$statistic = array(
			'enrolled_courses'   => 0,
			'in_progress_course' => 0,
			'finished_courses'   => 0,
			'passed_courses'     => 0,
			'failed_courses'     => 0,
		);

		try {
			if ( ! $user ) {
				throw new Exception( 'The user is invalid' );
			}

			$user_id          = $user->get_id();
			$lp_user_items_db = LP_User_Items_DB::getInstance();

			// Count status
			$filter                 = new LP_User_Items_Filter();
			$filter->user_id        = $user_id;
			$count_status           = $lp_user_items_db->count_status_by_items( $filter );
			$total_courses_enrolled = intval( $count_status->{LP_COURSE_PURCHASED} ?? 0 )
				+ intval( $count_status->{LP_COURSE_ENROLLED} ?? 0 )
				+ intval( $count_status->{LP_COURSE_FINISHED} ?? 0 );

			$statistic['enrolled_courses']   = $total_courses_enrolled;
			$statistic['in_progress_course'] = $count_status->{LP_COURSE_GRADUATION_IN_PROGRESS} ?? 0;
			$statistic['finished_courses']   = $count_status->{LP_COURSE_FINISHED} ?? 0;
			$statistic['passed_courses']     = $count_status->{LP_COURSE_GRADUATION_PASSED} ?? 0;
			$statistic['failed_courses']     = $count_status->{LP_COURSE_GRADUATION_FAILED} ?? 0;
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}

		return apply_filters( 'lp/profile/student/statistic', $statistic, $this );
	}

	/**
	 * Get statistic info of instructor user
	 *
	 * @param array $params
	 *
	 * @return array
	 * @since 4.1.6
	 * @version 1.0.1
	 */
	public function get_instructor_statistic( array $params = [] ): array {
		$statistic = array(
			'total_course'        => 0,
			'published_course'    => 0,
			'pending_course'      => 0,
			'total_student'       => 0,
			'student_completed'   => 0,
			'student_in_progress' => 0,
		);

		try {
			$user_id          = $this->get_id();
			$lp_user_items_db = LP_User_Items_DB::getInstance();
			$lp_course_db     = LP_Course_DB::getInstance();

			if ( ! $this->can_create_course() ) {
				throw new Exception( 'The user is not Instructor' );
			}

			// Count total user completed course of author
			$filter_course                      = new LP_Course_Filter();
			$filter_course->only_fields         = array( 'ID' );
			$filter_course->post_author         = $user_id;
			$filter_course->post_status         = 'publish';
			$filter_course->return_string_query = true;
			$query_courses_str                  = LP_Course_DB::getInstance()->get_courses( $filter_course );

			$filter_count_users            = new LP_User_Items_Filter();
			$filter_count_users->item_type = LP_COURSE_CPT;
			$filter_count_users->where[]   = "AND item_id IN ({$query_courses_str})";
			$count_student_has_status      = $lp_user_items_db->count_status_by_items( $filter_count_users );
			// Count total user in progress course of author

			// Get total users attend course of author
			$filter_count_users                   = $lp_user_items_db->count_user_attend_courses_of_author( $user_id );
			$count_users_attend_courses_of_author = $lp_user_items_db->get_user_courses( $filter_count_users );

			// Get total courses publish of author
			$filter_count_courses            = $lp_course_db->count_courses_of_author( $user_id, [ 'publish' ] );
			$total_courses_publish_of_author = $lp_course_db->get_courses( $filter_count_courses );

			// Get total courses of author
			$filter_count_courses    = $lp_course_db->count_courses_of_author( $user_id );
			$total_courses_of_author = $lp_course_db->get_courses( $filter_count_courses );

			// Get total courses pending of author
			$filter_count_courses            = $lp_course_db->count_courses_of_author( $user_id, [ 'pending' ] );
			$total_courses_pending_of_author = $lp_course_db->get_courses( $filter_count_courses );

			$statistic['total_course']        = $total_courses_of_author;
			$statistic['published_course']    = $total_courses_publish_of_author;
			$statistic['pending_course']      = $total_courses_pending_of_author;
			$statistic['total_student']       = $count_users_attend_courses_of_author;
			$statistic['student_completed']   = $count_student_has_status->{LP_COURSE_FINISHED} ?? 0;
			$statistic['student_in_progress'] = $count_student_has_status->{LP_COURSE_GRADUATION_IN_PROGRESS} ?? 0;
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}

		return apply_filters( 'lp/profile/instructor/statistic', $statistic, $this );
	}

	/**
	 * Get url instructor.
	 *
	 * @return string
	 * @version 1.0.0
	 * @since 4.2.3
	 */
	public function get_url_instructor(): string {
		$single_instructor_link = '';

		try {
			$author_id = $this->get_id();
			$author    = get_userdata( $author_id );
			if ( ! $author ) {
				return '';
			}

			$single_instructor_page_id = learn_press_get_page_id( 'single_instructor' );
			$single_instructor_link    = trailingslashit( trailingslashit( get_page_link( $single_instructor_page_id ) ) . $author->user_nicename );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $single_instructor_link;
	}
}
