<?php

/**
 * Class LP_Abstract_User
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Abstract_User' ) ) {

	/**
	 * Class LP_Abstract_User
	 */
	class LP_Abstract_User extends LP_Abstract_Object_Data {

		/**
		 * @var WP_User object
		 */
		public $user = false;

		/**
		 * @var null
		 */
		public $profile_picture_src = null;

		/**
		 * @var null
		 */
		public $profile_picture_type = null;

		/**
		 * @var array
		 */
		protected $_data = array(
			'email'         => '',
			'user_login'    => '',
			'description'   => '',
			'first_name'    => '',
			'last_name'     => '',
			'nickname'      => '',
			'display_name'  => '',
			'date_created'  => '',
			'date_modified' => '',
			'role'          => '',
			'roles'         => array(),
		);

		/**
		 * @var LP_User_CURD
		 */
		protected $_curd = null;

		/**
		 * LP_Abstract_User constructor.
		 *
		 * @param int   $the_user
		 * @param array $args
		 */
		public function __construct( $the_user = 0, $args = array() ) {

			parent::__construct( $the_user, $args );

			$this->_curd = new LP_User_CURD();

			if ( is_numeric( $the_user ) && $the_user > 0 ) {
				$this->set_id( $the_user );
			} elseif ( $the_user instanceof self ) {
				$this->set_id( absint( $the_user->get_id() ) );
			} elseif ( ! empty( $the_user->ID ) ) {
				$this->set_id( absint( $the_user->ID ) );
			}

			if ( $this->get_id() > 0 ) {
				$this->load();
			}

		}

		/**
		 * Load user data from curd
		 */
		public function load() {
			$this->_curd->load( $this );
		}

		/**
		 * Get data for a course user has enrolled.
		 *
		 * @updated 3.1.0
		 *
		 * @param int|LP_Abstract_Course $course_id .
		 * @param bool                   $check_exists .
		 *
		 * @return LP_User_Item_Course|LP_User_Item_Quiz|bool|mixed
		 */
		public function get_course_data( $course_id, $check_exists = false ) {
			if ( is_a( $course_id, 'LP_Abstract_Course' ) ) {
				$course_id = $course_id->get_id();
			}

			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			$object_course_data = LP_Object_Cache::get(
				'course-' . $this->get_id() . '-' . $course_id,
				'learn-press/user-item-object-courses'
			);

			if ( false === $object_course_data ) {
				$result = $this->_curd->read_course( $this->get_id(), $course_id );

				if ( $result ) {
					$object_course_data = new LP_User_Item_Course( $result );
				} else {
					$object_course_data = new LP_User_Item_Course( $course_id );
				}

				LP_Object_Cache::set(
					'course-' . $this->get_id() . '-' . $course_id,
					$object_course_data,
					'learn-press/user-item-object-courses'
				);
			}

			if ( $object_course_data ) {
				if ( ! $object_course_data->get_item_id() && $check_exists ) {
					return false;
				}
			}

			return $object_course_data;
		}

		/**
		 * Get user course item.
		 *
		 * @param int    $item_id
		 * @param int    $course_id
		 * @param string $field - Optional. Value of field to return.
		 *
		 * @return LP_User_Item_Quiz|LP_User_Item|bool
		 */
		public function get_item_data( $item_id, $course_id, $field = '' ) {
			$user_item = $this->get_user_item( $item_id, $course_id );

			switch ( $field ) {
				case 'end_time':
					return $user_item->get_end_time();
			}

			return $user_item;
		}

		/**
		 * Get data for a item user started in table user-items
		 *
		 * @param int $item_id
		 * @param int $course_id
		 *
		 * @return LP_User_Item_Quiz|LP_User_Item|bool
		 */
		public function get_user_item( $item_id, $course_id ) {
			$data = false;

			$course_data = $this->get_course_data( $course_id );

			if ( $course_data ) {
				$data = $course_data->get_item( $item_id );
			}

			return $data;
		}

		/**
		 * Return TRUE if user has used function for checking the question.
		 *
		 * @param int $question_id
		 * @param int $quiz_id
		 * @param int $course_id
		 *
		 * @return mixed
		 * @since 3.0.0
		 */
		public function has_checked_question( $question_id, $quiz_id, $course_id = 0 ) {
			$checked = false;

			if ( $this->get_quiz_data( $quiz_id, $course_id ) ) {
				$data    = $this->get_quiz_data( $quiz_id, $course_id );
				$checked = $data->has_checked_question( $question_id );
			}

			return apply_filters(
				'learn-press/user/checked-question',
				$checked,
				$question_id,
				$quiz_id,
				$course_id,
				$this->get_id()
			);
		}

		/**
		 * Magic function to get user data
		 *
		 * @param $key
		 *
		 * @return bool
		 */
		public function __get( $key ) {
			$return = false;

			if ( strtolower( $key ) !== 'id' ) {
				_deprecated_argument( __CLASS__ . '::' . $key, '3.0.0' );
			}

			if ( ! empty( $this->user->data->{$key} ) ) {
				$return = $this->user->data->{$key};
			} else {
				if ( isset( $this->{$key} ) ) {
					$return = $this->{$key};
				} elseif ( strpos( $key, '_lp_' ) === false ) {
					$key    = '_lp_' . $key;
					$return = get_user_meta( $this->get_id(), $key, true );
					if ( ! empty( $value ) ) {
						$this->$key = $return;
					}
				}
			}

			return $return;
		}

		/**
		 * Check if a course is exists then return it's ID.
		 * Try to get it from global.
		 *
		 * @param int    $course_id
		 * @param string $return
		 *
		 * @return bool|false|int|LP_Course
		 */
		protected function _get_course( $course_id, $return = 'id' ) {

			// if $course_id is not passed then try to get it from global
			if ( ! $course_id && learn_press_is_course() ) {
				$course_id = get_the_ID();
			}

			// Validate course
			$course = learn_press_get_course( $course_id );

			if ( $course ) {
				switch ( $return ) {
					case 'id':
						return $course_id;
					case 'object':
						return $course;
				}
			}

			return false;
		}

		/**
		 *
		 * @param int $item_id
		 * @param int $course_id
		 *
		 * @return bool
		 *
		 * @since 3.0.0
		 */
		protected function _verify_course_item( $item_id, $course_id = 0 ) {
			$course = $this->_get_course( $course_id, 'object' );

			if ( false !== $course ) {
				return $course->has_item( $item_id ) ? $course_id : false;
			}

			return false;
		}

		/**
		 * Return TRUE if an item has a status.
		 *
		 * @param array $statuses
		 * @param int   $item_id
		 * @param int   $course_id
		 *
		 * @return mixed
		 *
		 * @since 3.0.0
		 */
		public function has_item_status( $statuses, $item_id, $course_id ) {
			settype( $statuses, 'array' );
			$status = $this->get_item_status( $item_id, $course_id );

			//Todo: tungnx
			//$status = $this->getItemStatus( $item_id, $course_id );

			return apply_filters(
				'learn-press/user-has-item-status',
				in_array( $status, $statuses ),
				$statuses,
				$item_id,
				$course_id,
				$this->get_id()
			);
		}

		/**
		 * Get all records of an item.
		 *
		 * @param int  $item_id
		 * @param int  $course_id
		 * @param bool $return_last
		 *
		 * @return bool|mixed
		 */
		public function get_item_archive( $item_id, $course_id = 0, $return_last = false ) {
			$records = LP_Object_Cache::get(
				'course-item-' . $this->get_id() . '-' . $course_id . '-' . $item_id,
				'lp-user-course-items'
			);

			if ( $records ) {
				// $records = array_filter( $records );
			}

			if ( $return_last && is_array( $records ) ) {
				$records = reset( $records );
			}

			return $records;
		}

		/**
		 * Count number of rows for an item in user-items
		 *
		 * @param int $item_id
		 * @param int $course_id
		 *
		 * @return int
		 */
		public function count_item_archive( $item_id, $course_id = 0 ) {
			$count = 0;

			if ( $items = $this->get_item_archive( $item_id, $course_id ) ) {
				$count = sizeof( $items );
			}

			return $count;
		}

		/**
		 * Start quiz for the user.
		 *
		 * @param int  $quiz_id
		 * @param int  $course_id
		 * @param bool $wp_error Optional. Whether to return a WP_Error on failure. Default false.
		 *
		 * @return LP_User_Item_Quiz|bool|WP_Error
		 * @throws Exception
		 */
		public function start_quiz( $quiz_id, $course_id = 0, $wp_error = false ) {
			try {
				$item_id = learn_press_get_request( 'lp-preview' );

				if ( $item_id ) {
					learn_press_add_message( __( 'You cannot start a quiz in preview mode.', 'learnpress' ), 'error' );
					wp_safe_redirect( learn_press_get_preview_url( $item_id ) );
					exit();
				}

				// Validate course and quiz
				$course_id = $this->_verify_course_item( $quiz_id, $course_id );
				if ( ! $course_id ) {
					throw new Exception(
						__( 'Course does not exist or does not contain the quiz', 'learnpress' ),
						LP_INVALID_QUIZ_OR_COURSE
					);
				}

				$course       = learn_press_get_course( $course_id );
				$access_level = $this->get_course_access_level( $course_id );

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
							__( 'Please enroll course before starting quiz.', 'learnpress' ),
							LP_COURSE_IS_FINISHED
						);
					}
				}

				// Check if user has already started or completed quiz
				if ( $this->has_item_status( array( 'started', 'completed' ), $quiz_id, $course_id ) ) {
					throw new Exception(
						__( 'User has started or completed quiz', 'learnpress' ),
						LP_QUIZ_HAS_STARTED_OR_COMPLETED
					);
				}
				$user = LP_Global::user();

				if ( $user->is_guest() ) {
					// if course required enroll => print message "You have to login for starting quiz"
					if ( ! $course->is_no_required_enroll() ) {
						throw new Exception( __( 'You have to login for starting quiz.', 'learnpress' ), LP_REQUIRE_LOGIN );
					}
				}

				/**
				 * Hook can start quiz
				 *
				 * @see learn_press_hk_before_start_quiz
				 */
				$can_start_quiz = apply_filters(
					'learn-press/before-start-quiz',
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
			} catch ( Exception $ex ) {
				$return = $wp_error ? new WP_Error( $ex->getCode(), $ex->getMessage() ) : false;
			}

			return $return;
		}

		/**
		 * Finish a quiz for the user and save all data needed
		 *
		 * @param int  $quiz_id
		 * @param int  $course_id
		 * @param bool $wp_error
		 *
		 * @return LP_User_Item_Quiz|bool|WP_Error
		 */
		public function finish_quiz( $quiz_id, $course_id, $wp_error = false ) {
			if ( ! apply_filters(
				'learn_press_before_user_finish_quiz',
				true,
				$quiz_id,
				$course_id,
				$this->get_id()
			) ) {
				return false;
			}
			$return = false;

			try {
				// Validate course and quiz
				if ( false === ( $course_id = $this->_verify_course_item( $quiz_id, $course_id ) ) ) {
					throw new Exception(
						__( 'Course is not exists or does not contain the quiz', 'learnpress' ),
						LP_INVALID_QUIZ_OR_COURSE
					);
				}

				// If user has already finished the course
				if ( $this->has_finished_course( $course_id ) ) {
					throw new Exception(
						__( 'User has already finished course of this quiz', 'learnpress' ),
						LP_COURSE_IS_FINISHED
					);

				}

				// Check if user has already started or completed quiz
				if ( $this->has_item_status( array( 'completed' ), $quiz_id, $course_id ) ) {
					throw new Exception(
						__( 'User has completed quiz', 'learnpress' ),
						LP_QUIZ_HAS_STARTED_OR_COMPLETED
					);
				}

				$user_quiz = $this->get_item_data( $quiz_id, $course_id );

				$user_quiz->finish();

				do_action( 'learn-press/user/quiz-finished', $quiz_id, $course_id, $this->get_id() );
			} catch ( Exception $ex ) {
				$return = $wp_error ? new WP_Error( $ex->getCode(), $ex->getMessage() ) : false;
			}

			return $return;
		}

		/**
		 * Retake a quiz for the user
		 *
		 * @param int  $quiz_id
		 * @param int  $course_id
		 * @param bool $wp_error
		 *
		 * @return bool|WP_Error|LP_User_Item_Quiz
		 *
		 * @throws Exception
		 */
		public function retake_quiz( $quiz_id, $course_id, $wp_error = false ) {
			if ( ! apply_filters(
				'learn-press/user/before-retake-quiz',
				true,
				$quiz_id,
				$course_id,
				$this->get_id()
			) ) {
				return false;
			}
			$return = false;
			try {
				$course_id = $this->_verify_course_item( $quiz_id, $course_id );

				if ( false === $course_id ) {
					throw new Exception(
						sprintf(
							__(
								'Course does not exist or does not contain the quiz.',
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
							__( '%1$s::%2$s - User has not completed quiz.', 'learnpress' ),
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
							__( '%1$s::%2$s - Your Quiz can\'t retake.', 'learnpress' ),
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
		 * Check quiz can retake?
		 *
		 * @param [type] $quiz_id
		 * @param [type] $course_id
		 *
		 * @return boolean
		 */
		public function has_retake_quiz( $quiz_id, $course_id ) {
			$user_quiz = $this->get_item_data( $quiz_id, $course_id );

			if ( $user_quiz ) {
				$retaken = $user_quiz->get_retaken_count();
				$count   = get_post_meta( $quiz_id, '_lp_retake_count', true );

				// Retake is disable.
				if ( ! $count ) {
					return false;
				}

				return absint( $retaken ) < absint( $count ) ? true : false;
			}

			return false;
		}

		/**
		 * Get quiz status for the user
		 *
		 * @param int $quiz_id
		 * @param int $course_id
		 *
		 * @return mixed
		 */
		public function get_quiz_status( $quiz_id, $course_id = 0 ) {
			return $this->get_item_status( $quiz_id, $course_id );
		}

		/**
		 * Get quiz status for the user
		 *
		 * @param int $lesson_id
		 * @param int $course_id
		 *
		 * @return mixed
		 */
		public function get_lesson_status( $lesson_id, $course_id = 0 ) {
			return $this->get_item_status( $lesson_id, $course_id );
		}

		/**
		 * @param int  $item_id
		 * @param int  $course_id
		 * @param bool $last
		 *
		 * @return mixed
		 * @since 3.0.0
		 */
		public function get_item( $item_id, $course_id = 0, $last = false ) {
			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			if ( $course_data = $this->get_course_data( $course_id ) ) {
				return $course_data->get_item( $item_id );
			}

			return false;
		}

		/**
		 * @param int $item_id
		 * @param int $course_id
		 *
		 * @return mixed
		 * @since 3.0.0
		 */
		public function get_item_grade( $item_id, $course_id = 0 ) {
			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			$grade = false;

			$course_data = $this->get_course_data( $course_id );
			$item_result = $course_data->get_item_result( $item_id, false );

			if ( $course_data && $item_result ) {
				$grade = isset( $item_result['graduation'] ) ? $item_result['graduation'] : false;
			}

			return apply_filters( 'learn-press/user-item-grade', $grade, $item_id, $this->get_id(), $course_id );
		}

		/**
		 * Get current status of an item for user.
		 *
		 * @param int $item_id
		 * @param int $course_id
		 *
		 * @return bool|mixed
		 */
		public function get_item_status( $item_id, $course_id = 0 ) {
			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			$status = false;
			$item   = $this->get_item( $item_id, $course_id, true );

			if ( false !== $item ) {
				$status = $item['status'];
			}

			return apply_filters( 'learn-press/user-item-status', $status, $item_id, $this->get_id(), $course_id );
		}

		/**
		 * To rewrite get_item_status on abstract-lp-user.
		 *
		 * @throws Exception
		 * @author tungnx
		 */
		public function getItemStatus( $item_id, $course_id ) {
			$status = LP_User_Items_DB::getInstance()->get_item_status( $item_id, $course_id );

			return $status;
		}

		/**
		 * Update viewing item data into database.
		 *
		 * @param int $item_id
		 * @param int $course_id
		 *
		 * @return bool
		 * @since 3.0.0
		 */
		public function maybe_update_item( $item_id, $course_id ) {
			$return      = false;
			$course_data = $this->get_course_data( $course_id );

			if ( $course_data && $course_data->get_user_item_id() ) {
				$item = $course_data->get_item( $item_id );

				if ( ! $item || ! $item->get_user_item_id() ) {
					$item = LP_User_Item::get_item_object( $item_id );

					if ( ! $item ) {
						return $return;
					}

					$item->set_ref_id( $course_id );
					$item->set_parent_id( $course_data->get_user_item_id() );

					$return = $item->update();

					if ( $return ) {
						$course_data->set_item( $item );
					}
				}
			}

			return $return;
		}

		/**
		 * Get item user has accessed in last time.
		 *
		 * @param int  $course_id
		 * @param bool $permalink - Optional. TRUE will return permalink instead of ID.
		 *
		 * @return mixed
		 */
		public function get_current_item( $course_id, $permalink = false ) {
			if ( ! $course_data = $this->get_course_data( $course_id ) ) {
				return false;
			}

			$course = learn_press_get_course( $course_id );

			if ( false == ( $id = learn_press_get_user_item_meta(
				$course_data->get_user_item_id(),
				'_current_item',
				true
			) ) || $this->has_completed_item( $id, $course_id ) ) {

				if ( $items = $course->get_items( '', false ) ) {
					foreach ( $items as $item_id ) {
						if ( ! $this->has_completed_item( $item_id, $course_id ) ) {
							$id = $item_id;
							break;
						}
					}

					if ( ! $id ) {
						$id = reset( $items );
					}
				}

				if ( $id ) {
					learn_press_update_user_item_meta( $course_data->get_user_item_id(), '_current_item', $id );
				}
			}

			if ( $permalink && $id ) {
				return apply_filters(
					'learn-press/current-course-item-permalink',
					$course->get_item_link( $id ),
					$course_id,
					$this->get_id()
				);
			} else {
				return apply_filters( 'learn-press/current-course-item', $id, $course_id, $this->get_id() );
			}
		}

		/**
		 * Get current question's ID/Permalink inside quiz.
		 *
		 * @param int  $quiz_id
		 * @param int  $course_id
		 * @param bool $permalink
		 *
		 * @return bool|int|string
		 */
		public function get_current_question( $quiz_id, $course_id, $permalink = false ) {
			_deprecated_function( sprintf( '%s::%s', __CLASS__, __FUNCTION__ ), '4.0.0' );

			/**
			 * @var LP_User_Item_Quiz $quiz_item
			 */
			$data = $this->get_course_data( $course_id );

			if ( empty( $data[ $quiz_id ] ) ) {
				return false;
			}

			$quiz        = learn_press_get_quiz( $quiz_id );
			$quiz_item   = $data[ $quiz_id ];
			$question_id = $quiz_item->get_current_question();
			if ( $question_id && $permalink ) {
				return apply_filters(
					'learn-press/current-user-question-permalink',
					$quiz->get_question_link( $question_id ),
					$quiz_id,
					$course_id,
					$this->get_id()
				);
			}

			return apply_filters( 'learn-press/current-user-question', $question_id );
		}

		/**
		 * Get previous Question
		 *
		 * @param null  $quiz_id
		 * @param int   $course_id
		 * @param false $permalink
		 */
		public function get_prev_question( $quiz_id = null, $course_id = 0, $permalink = false ) {
			_deprecated_function( sprintf( '%s::%s', __CLASS__, __FUNCTION__ ), '4.0.0' );
		}

		/**
		 * Get next Question
		 *
		 * @param null  $quiz_id
		 * @param int   $course_id
		 * @param false $permalink
		 */
		public function get_next_question( $quiz_id = null, $course_id = 0, $permalink = false ) {
			_deprecated_function( sprintf( '%s::%s', __CLASS__, __FUNCTION__ ), '4.0.0' );
		}

		/**
		 * Checks if has status of a quiz for user
		 *
		 * @param string|array $statuses
		 * @param int          $quiz_id
		 * @param int          $course_id
		 * @param boolean      $force
		 *
		 * @return bool
		 */
		public function has_quiz_status( $statuses, $quiz_id, $course_id = 0, $force = false ) {
			$status = $this->get_quiz_status( $quiz_id, $course_id );

			settype( $statuses, 'array' );

			return apply_filters(
				'learn_press_user_has_quiz_status',
				in_array( $status, $statuses ),
				$statuses,
				$status,
				$quiz_id,
				$course_id,
				$this->get_id()
			);
		}

		/**
		 * Get current results of a quiz
		 *
		 * @param int    $quiz_id
		 * @param int    $course_id
		 * @param string $prop
		 *
		 * @return mixed
		 */
		public function get_quiz_results( $quiz_id, $course_id = 0, $prop = 'result' ) {
			$user_quiz = $this->get_item_data( $quiz_id, $course_id );

			return $user_quiz ? $user_quiz->get_results( $prop ) : false;
		}

		/**
		 * Get current progress of user's quiz.
		 *
		 * @param int $quiz_id
		 * @param int $course_id
		 *
		 * @return LP_User_Item_Quiz
		 */
		public function get_quiz_data( $quiz_id, $course_id = 0 ) {
			$result = false;
			if ( $course_result = $this->get_course_data( $course_id ) ) {

				$result = $course_result->get_item( $quiz_id );
			}

			return $result;
		}

		/**
		 * Mark question that user has checked.
		 *
		 * @param int $question_id
		 * @param int $quiz_id
		 * @param int $course_id
		 *
		 * @return WP_Error|mixed
		 * @since 3.0.0
		 */
		public function check_question( $question_id, $quiz_id, $course_id ) {
			if ( ! $course = learn_press_get_course( $course_id ) ) {
				return false;
			}

			if ( ! $course->has_item( $quiz_id ) ) {
				return false;
			}

			$quiz = $course->get_item( $quiz_id );

			if ( ! $quiz->has_question( $question_id ) ) {
				return false;
			}

			$quiz_data = $this->get_item_data( $quiz_id, $course_id );

			return $quiz_data->check_question( $question_id );
		}

		/**
		 * Mark question that user has checked.
		 *
		 * @param int $question_id
		 * @param int $quiz_id
		 * @param int $course_id
		 *
		 * @return WP_Error|mixed
		 * @since 3.0.0
		 */
		public function hint( $question_id, $quiz_id, $course_id ) {

			if ( ! $course = learn_press_get_course( $course_id ) ) {
				return false;
			}

			if ( ! $course->has_item( $quiz_id ) ) {
				return false;
			}

			$quiz = $course->get_item( $quiz_id );

			if ( ! $quiz->has_question( $question_id ) ) {
				return false;
			}

			$quiz_data = $this->get_item_data( $quiz_id, $course_id );

			if ( false === ( $remain = $quiz_data->hint( $question_id ) ) ) {
				return new WP_Error( 1001, __( 'You can not hint question.', 'learnpress' ) );
			}

			return $remain;
		}

		/**
		 * Return true if check answer is enabled.
		 *
		 * @param int $quiz_id
		 * @param int $course_id
		 *
		 * @return bool
		 */
		public function can_check_answer( $quiz_id, $course_id = 0 ) {

			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			if ( $quiz_data = $this->get_item_data( $quiz_id, $course_id ) ) {
				return $quiz_data->can_check_answer();
			}

			return false;
		}

		/**
		 * Return true if check answer is enabled.
		 *
		 * @param int $quiz_id
		 * @param int $course_id
		 *
		 * @return bool
		 */
		public function can_hint_answer( $quiz_id, $course_id = 0 ) {

			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			if ( $quiz_data = $this->get_item_data( $quiz_id, $course_id ) ) {
				return $quiz_data->can_hint_answer();
			}

			return false;
		}


		public function get_quiz_last_results( $quiz_id ) {
			$results = $this->get_course_info( $quiz_id );
			if ( $results ) {
				$results = reset( $results );
			}

			return apply_filters( 'learn_press_user_quiz_last_results', $results, $quiz_id, $this );
		}

		/**
		 * Get history of a quiz for an user
		 *
		 * @param int  $quiz_id
		 * @param int  $course_id
		 * @param int  $history_id
		 * @param bool $force
		 *
		 * @return mixed|null|void
		 */
		public function get_quiz_history( $quiz_id, $course_id = 0, $history_id = null, $force = false ) {
			$course_id = $this->_get_course( $course_id );

			$course = learn_press_get_course( $course_id );
			if ( $course ) {
				$quizzes = $course->get_quizzes( 'ID' );
			} else {
				$quizzes = array();
			}
			$key = $this->get_id() . '-' . $course_id . '-' . $quiz_id;

			$cached = LP_Cache::get_quiz_history( false, array() );

			if ( ( ! array_key_exists( $key, $cached ) || $force ) && $quizzes && in_array( $quiz_id, $quizzes ) ) {
				global $wpdb;
				$t1             = $wpdb->learnpress_user_items;
				$t2             = $wpdb->learnpress_user_itemmeta;
				$in             = array_fill( 0, sizeof( $quizzes ), '%d' );
				$prepare_params = array_merge(
					array( 'lp_quiz', $this->get_id(), $course_id ),
					$quizzes
				);
				$query          = $wpdb->prepare(
					"
				SELECT *
				FROM $t1 uq
				WHERE uq.item_type = %s
					AND uq.user_id = %d
					AND uq.ref_id = %d
					AND uq.item_id IN(" . join( ',', $in ) . ')
				ORDER BY uq.user_item_id DESC
			',
					$prepare_params
				);

				$history = array();
				foreach ( $quizzes as $_quiz_id ) {
					$history[ $this->get_id() . '-' . $course_id . '-' . $_quiz_id ] = array();
				}
				if ( $results = $wpdb->get_results( $query ) ) {
					$item_ids = array();
					foreach ( $results as $result ) {
						$item_ids[] = $result->user_item_id;
						$cache_key  = $this->get_id() . '-' . $course_id . '-' . $result->item_id;
						if ( empty( $history[ $cache_key ] ) ) {
							$history[ $cache_key ] = array();
						}
						// limit newest 10 items
						if ( sizeof( $history[ $cache_key ] ) >= 10 ) {
							// break;
						}

						$history[ $cache_key ][ $result->user_item_id ] = (object) array(
							'history_id'       => $result->user_item_id,
							'start'            => $result->start_time,
							'end'              => $result->end_time,
							'status'           => $result->status,
							'question'         => '',
							'questions'        => array(),
							'question_answers' => array(),
						);
					}
					if ( $item_ids && $meta = $this->_get_quiz_meta( $item_ids ) ) {
						$maps = array(
							'questions'        => 'questions',
							'current_question' => 'question',
							'question_answers' => 'question_answers',
							'question_checked' => 'question_checked',
						);
						foreach ( $meta as $k => $v ) {
							$_key = $this->get_id() . '-' . $course_id . '-' . $v->item_id;
							if ( empty( $history[ $_key ] ) ) {
								continue;
							}
							if ( empty( $history[ $_key ][ $v->user_item_id ] ) ) {
								continue;
							}
							$obj_key = ! empty( $maps[ $v->meta_key ] ) ? $maps[ $v->meta_key ] : $v->meta_key;
							if ( ! $obj_key ) {
								continue;
							}
							$history[ $_key ][ $v->user_item_id ]->{$obj_key} = LP_Helper::maybe_unserialize( $v->meta_value );
						}
					}
				}

				if ( $history ) {
					foreach ( $history as $k1 => $v1 ) {
						if ( empty( $cached[ $k1 ] ) ) {
							$cached[ $k1 ] = $v1;
							continue;
						}
						foreach ( $v1 as $k2 => $v2 ) {
							$cached[ $k1 ][ $k2 ] = $v2;
						}
					}
				}
				LP_Cache::set_quiz_history( $cached );
			}

			return apply_filters(
				'learn_press_user_quiz_history',
				isset( $cached[ $key ] ) ? $cached[ $key ] : array(),
				$this,
				$quiz_id
			);
		}

		private function _get_quiz_meta( $user_item_id ) {
			global $wpdb;
			settype( $user_item_id, 'array' );
			$in = array_fill( 0, sizeof( $user_item_id ), '%d' );

			$query = $wpdb->prepare(
				"
			SELECT learnpress_user_item_id as user_item_id, meta_key, meta_value, item_id
			FROM {$wpdb->prefix}learnpress_user_itemmeta im
			INNER JOIN {$wpdb->prefix}learnpress_user_items i ON i.user_item_id = im.learnpress_user_item_id
			WHERE learnpress_user_item_id IN(" . join( ',', $in ) . ')
			',
				$user_item_id
			);

			return $wpdb->get_results( $query );
		}

		public function get_current_results( $quiz_id, $course_id = 0 ) {
			$course_id = $this->_get_course( $course_id );

			$history = $this->get_quiz_history( $quiz_id, $course_id );
			$current = false;
			if ( $history ) {
				$current = reset( $history );
			}

			return $current;
		}

		/**
		 * Check if user has at least one role.
		 *
		 * @param array|string $roles
		 *
		 * @return array
		 */
		public function has_role( $roles ) {
			settype( $roles, 'array' );

			return array_intersect( $roles, $this->get_roles() );
		}

		/**
		 * Detect the type of user
		 *
		 * @param string|int $type
		 *
		 * @return bool
		 */
		public function is( $type ) {
			$is = false;
			if ( $type === 'current' ) {
				$is = $this->is( get_current_user_id() );
			} elseif ( is_string( $type ) ) {
				$name = preg_replace( '!LP_User(_?)!', '', get_class( $this ) );
				$is   = strtolower( $name ) == strtolower( $type );
			} elseif ( is_numeric( $type ) ) {
				$is = $this->get_id() && ( $this->get_id() == $type );
			}

			return $is;
		}

		/**
		 *
		 * Check what the user can do
		 *
		 * @param $role
		 *
		 * @return mixed
		 * @throws Exception
		 */
		public function can( $role ) {
			_deprecated_function( __FUNCTION__, '3.0.8' );
			$args = func_get_args();
			unset( $args[0] );
			$method   = 'can_' . preg_replace( '!-!', '_', $role );
			$callback = array( $this, $method );
			if ( is_callable( $callback ) ) {
				return call_user_func_array( $callback, $args );
			} else {
				throw new Exception( sprintf( __( 'The role %s for user doesn\'t exist', 'learnpress' ), $role ) );
			}
		}

		/**
		 * Return true if user can purchase a course
		 *
		 * @param int $course_id
		 *
		 * @return bool
		 */
		public function can_purchase_course( int $course_id ) : bool {
			return false;
			/*$course      = learn_press_get_course( $course_id );
			$purchasable = $course->is_purchasable();

			// @deprecated
			$purchasable = apply_filters( 'learn_press_user_can_purchase_course', $purchasable, $this, $course_id );

			// since 3.0.0
			return apply_filters( 'learn-press/user/can-purchase-course', $purchasable, $this->get_id(), $course_id );*/
		}

		/**
		 * Check if the user can access to an item inside course.
		 *
		 * @updated 3.1.0
		 *
		 * @param int $item_id
		 * @param int $course_id
		 *
		 * @editor tungnx
		 * @reason rewrite this function
		 *
		 * @return mixed
		 */
		/*
		public function can_view_item( $item_id, $course_id = 0 ) {

			$view   = false;
			$course = learn_press_get_course( $course_id );
			if ( ! $course_id ) {
				$course_id = $course->get_id();
			}
			// Disable preview course when course status is pending
			if ( $course && $course->is_publish() && ( $item = $course->get_item( $item_id ) ) ) {
				if ( $this->has_course_access_level(
					array(
						LP_COURSE_ACCESS_LEVEL_60,
						LP_COURSE_ACCESS_LEVEL_70,
					),
					$course_id,
					'any'
				)
				) {
					$view = 'enrolled';
				} elseif ( ! $course->is_required_enroll() ) {
					$view = 'no-required-enroll';
				} elseif ( $item->is_preview() ) {
					$view = 'preview';
				} elseif ( $this->is_admin() ) {
					$view = 'admin';
				} elseif ( $this->is_author_of( $item_id ) ) {
					$view = 'author';
				}
			}

			return apply_filters( 'learn-press/can-view-item', $view, $item_id, $this->get_id(), $course_id );

			// $return    = false;
			// $course_id = $this->_get_course( $course_id );
			//
			// $course_author = learn_press_get_course_user( $course_id );
			// if ( $course_author ) {
			// $author_id = $course_author->get_id();
			// if ( $author_id == $this->get_id() ) {
			// return true;
			// }
			// }
			//
			// switch ( learn_press_get_post_type( $item_id ) ) {
			// case LP_QUIZ_CPT:
			// $return = $this->can_view_quiz( $item_id, $course_id );
			// break;
			// case LP_LESSON_CPT:
			// $return = $this->can_view_lesson( $item_id, $course_id );
			// break;
			// }
			//
			// @deprecated
			// $return = apply_filters( 'learn_press_user_can_view_item', $return, $item_id, $course_id, $this->get_id() );
			//
			// return apply_filters( 'learn-press/can-view-item', $return, $item_id, $course_id, $this->get_id() );
		}*/

		public function can_edit_item( $item_id, $course_id = 0 ) {
			$return = $this->is_admin();

			if ( ! $return ) {
				$course_id = $this->_get_course( $course_id );

				$course_author = learn_press_get_course_user( $course_id );
				if ( $course_author && $course_author->get_id() == $this->get_id() ) {
					$return = true;
				}
			}

			return apply_filters( 'learn_press_user_can_edit_item', $return, $item_id, $course_id, $this->get_id() );
		}

		/**
		 * Check if user can finished course by getting current progress
		 * and compares with course passing condition.
		 *
		 * @param int $course_id
		 *
		 * @return bool
		 */
		public function can_finish_course( $course_id ) {
			$return = false;
			$course = learn_press_get_course( $course_id );

			if ( $course ) {
				$access_level = $this->get_course_access_level( $course_id );

				if ( $access_level === LP_COURSE_ACCESS_LEVEL_70 ) {
					$result = $this->evaluate_course_results( $course_id );
					$return = $result >= $course->get_passing_condition();
				}
			}

			return apply_filters( 'learn-press/can-finished-course', $return, $course, $this->get_id() );
		}

		/**
		 * Check if course has any passed status for an user.
		 * Statuses: depending on value of column `status` in user_items.
		 *      - purchased: bought and order is completed, `start_date` and `end_date` is null
		 *      - enrolled: value of column `status` in user_items is enrolled
		 *      - started: value of column `status` in user_items is started
		 *      - enrolled: value of column `status` in user_items is enrolled
		 *
		 * @param int          $course_id
		 * @param string|array $statuses
		 *
		 * @return bool
		 * @since 2.0
		 */
		public function has_course_status( $course_id, $statuses ) {
			$status = $this->get_course_status( $course_id );

			if ( is_array( $statuses ) ) {
				return in_array( $status, $statuses );
			} elseif ( is_string( $statuses ) ) {
				return $statuses == $status;
			}

			return false;
		}

		public function get_completed_items( $course_id ) {
			$this->_curd->get_user_items( $this->get_id(), $course_id );

			return $this->_curd->get_user_completed_items( $this->get_id(), $course_id );
		}

		/**
		 * Finish course
		 *
		 * @param int $course_id
		 *
		 * @return int|bool
		 */
		public function finish_course( $course_id ) {
			$return = false;
			$course = learn_press_get_course( $course_id );

			if ( $course ) {
				$user_course = $this->get_course_data( $course_id );

				$return = $user_course->finish();

				$user_course->calculate_course_results();

				if ( $return ) {
					do_action( 'learn-press/user-course-finished', $course_id, $this->get_id(), $return );
				}

				wp_cache_flush();
			}

			return apply_filters( 'learn-press/user-course-finished-data', $return, $course_id, $this->get_id() );
		}

		/**
		 * Check user instructor.
		 *
		 * @return bool
		 */
		public function is_instructor() {

			$roles = $this->get_data( 'roles' ) ? $this->get_data( 'roles' ) : array();

			return in_array( LP_TEACHER_ROLE, $roles );
		}

		/**
		 * Check user admin.
		 *
		 * @return bool
		 */
		public function is_admin() {
			$roles = $this->get_data( 'roles' ) ? $this->get_data( 'roles' ) : array();

			return in_array( 'administrator', $roles );
		}


		public function can_create_course() {
			return $this->is_instructor() || $this->is_admin();
		}

		/**
		 * Wrap function to check this user is author of a post.
		 *
		 * @param int $post_id
		 *
		 * @return bool
		 * @since 3.1.0
		 */
		public function is_author_of( $post_id ) {
			return absint( get_post_field( 'post_author', $post_id ) ) === $this->get_id();
		}

		public function has( $role ) {
			_deprecated_function( __FUNCTION__, '3.0.8' );

			$args = func_get_args();
			unset( $args[0] );
			$method   = 'has_' . preg_replace( '!-!', '_', $role );
			$callback = array( $this, $method );
			if ( is_callable( $callback ) ) {
				return call_user_func_array( $callback, $args );
			} else {
				throw new Exception( sprintf( __( 'The role %s for user doesn\'t exist', 'learnpress' ), $role ) );
			}
		}

		public function get( $role ) {
			$args = func_get_args();
			unset( $args[0] );
			$method   = 'get_' . preg_replace( '!-!', '_', $role );
			$callback = array( $this, $method );
			if ( is_callable( $callback ) ) {
				return call_user_func_array( $callback, $args );
			} else {
				throw new Exception( sprintf( __( 'The role %s for user doesn\'t exist', 'learnpress' ), $role ) );
			}
		}

		/**
		 * Get last order of an user of all courses
		 *
		 * @param bool $last_order
		 *
		 * @return array
		 */
		public function get_orders( $last_order = true ) {

			if ( $last_order ) {
				if ( false !== ( $cached_last_order = LP_Object_Cache::get(
					'user-' . $this->get_id(),
					'learn-press/user-last-order'
				) ) ) {
					return $cached_last_order;
				}
			}

			$my_orders = $this->_curd->get_orders( $this->get_id() );

			if ( $last_order && $my_orders ) {
				$last_orders = array();
				foreach ( $my_orders as $course_id => $orders ) {
					$last_orders[ $course_id ] = reset( $orders );
				}
				LP_Object_Cache::set( 'user-' . $this->get_id(), $last_orders, 'learn-press/user-last-order' );
			} else {
				$last_orders = $my_orders;
			}

			return $last_orders;
		}


		/**
		 * Return true if user has already enrolled course
		 *
		 * @param int $course_id .
		 *
		 * @return bool
		 */
		public function has_enrolled_course( int $course_id ) : bool {
			$course_item = $this->get_course_data( $course_id );

			$enrolled = $course_item && $course_item->get_user_item_id() > 0;

			return apply_filters( 'learn-press/has-enrolled-course', $enrolled, $this->get_id(), $course_id );
		}

		/**
		 * Return true if you has finished a course
		 *
		 * @param int $course_id .
		 *
		 * @return bool
		 */
		public function has_finished_course( int $course_id ) : bool {
			$course_item = $this->get_course_data( $course_id );
			$finished    = $course_item && $course_item->has_finished();

			return apply_filters( 'learn-press/user-has-finished-course', $finished, $this->get_id(), $course_id );
		}

		/**
		 * Check user has passed course.
		 *
		 * @param $course_id
		 *
		 * @return mixed
		 */
		public function has_passed_course( $course_id ) {
			$course = learn_press_get_course( $course_id );

			if ( $course ) {
				$results = $this->evaluate_course_results( $course_id );
			} else {
				$results = 0;
			}

			return apply_filters(
				'learn_press_user_has_passed_course',
				$results >= $course->get_passing_condition() ? $results : false,
				$course_id,
				$this
			);
		}

		/**
		 * Checks if user has started a quiz
		 * - FALSE if user has not started quiz
		 * - String if user has started quiz (status of quiz)
		 *
		 * @param int $quiz_id
		 * @param int $course_id
		 *
		 * @return mixed
		 */
		public function has_started_quiz( $quiz_id, $course_id = 0 ) {
			$course_id = $this->_get_course( $course_id );
			$started   = false;
			$started   = $this->has_quiz_status( array( 'started', 'completed' ), $quiz_id, $course_id );

			return apply_filters( 'learn_press_user_started_quiz', $started, $quiz_id, $course_id, $this->get_id() );
		}

		/**
		 * Return true if user has completed a quiz
		 *
		 * @param int $quiz_id
		 * @param int $course_id
		 *
		 * @return mixed
		 */
		public function has_completed_quiz( $quiz_id, $course_id = 0 ) {
			$completed = $this->get_item_status( $quiz_id, $course_id ) == 'completed';

			// @deprecated since 3.0.0
			$completed = apply_filters( 'learn_press_user_has_completed_quiz', $completed, $quiz_id, $this );

			return apply_filters(
				'learn-press/user-completed-quiz',
				$completed,
				$quiz_id,
				$course_id,
				$this->get_id()
			);
		}


		/**
		 * Count number of time user has retaken a quiz
		 *
		 * @param int  $quiz_id
		 * @param int  $course_id
		 * @param bool $force
		 *
		 * @return int
		 */
		public function count_retaken_quiz( $quiz_id, $course_id = 0, $force = false ) {
			$count     = false;
			$course_id = $this->_get_course( $course_id );

			if ( ! $course_id || ! $quiz_id ) {
				return $count;
			}

			$count = 0;

			if ( $course_data = $this->get_course_data( $course_id ) ) {

				if ( false === ( $count = $course_data->get_item_retaken_count( $quiz_id ) ) ) {
					$user_item = $this->get_item_data( $quiz_id, $course_id );

					if ( $user_item ) {
						$new_count = $user_item->count_history() - 1;
						$count     = $course_data->update_item_retaken_count( $quiz_id, $new_count );
					}
				}
			}

			return apply_filters(
				'learn_press_user_count_retaken_quiz',
				$count,
				$quiz_id,
				$course_id,
				$this->get_id()
			);
		}

		/**
		 * Count number of time user has retaken a quiz
		 *
		 * @param int  $course_id
		 * @param bool $force
		 *
		 * @return int
		 */
		public function count_retaken_course( $course_id = 0, $force = false ) {
			$count     = false;
			$course_id = $this->_get_course( $course_id );

			if ( ! $course_id ) {
				return $count;
			}

			if ( $user_course = $this->get_course_data( $course_id ) ) {
				$count = $user_course->get_retaken_count();
			}

			return $count;
		}

		/**
		 * @param $course_id
		 *
		 * @return bool|WP_Error
		 * @deprecated
		 */
		/*
		public function retake_course( $course_id ) {
			 return $this->retry_course( $course_id );
		}*/

		/**
		 * Retry course action.
		 *
		 * @param int  $course_id
		 * @param bool $wp_error - Return WP_Error object when getting any error.
		 *
		 * @return bool|WP_Error
		 * @since 4.0.0
		 */
		// public function retry_course( $course_id, $wp_error = false ) {
		// global $wpdb;
		//
		// $result = false;
		// $check  = apply_filters(
		// 'learn-press/before-retake-course',
		// $this->can_retry_course( $course_id ),
		// $course_id,
		// $this->get_id()
		// );
		//
		// if ( ! $check ) {
		// return false;
		// }
		//
		// try {
		// if ( ! $this->has_finished_course( $course_id ) ) {
		// throw new Exception( __( 'Your are learning course.', 'learnpress' ) );
		// }
		//
		// $user_item_api = new LP_User_Item_CURD();
		// $find_query    = array(
		// 'item_id' => $course_id,
		// 'user_id' => $this->get_id(),
		// );
		//
		// $course       = learn_press_get_course( $course_id );
		// $course_items = $user_item_api->get_items_by( $find_query );
		// $ref_id       = 0;
		// if ( $course_items ) {
		// $ref_id = $course_items[0]->ref_id;
		// }
		//
		// $result = $this->enroll_course( $course_id, $ref_id, false, $wp_error );
		//
		// if ( is_wp_error( $result ) ) {
		// throw new Exception( __( 'Retry course error!', 'learnpress' ) );
		// }
		// } catch ( Exception $ex ) {
		// return $wp_error ? new WP_Error( 'retry-course-error', $ex->getMessage() ) : false;
		// }
		//
		// **
		// * LP hook
		// *
		// * @since 4.0.0
		// */
		// do_action( 'learn-press/user-retried-course', $result, $course_id, $this->get_id() );
		//
		// return $result;
		// }

		/**
		 * Mark a lesson is completed for user
		 *
		 * @param int  $lesson_id
		 * @param int  $course_id
		 * @param bool $return_wp_error
		 *
		 * @return bool|WP_Error
		 */
		public function complete_lesson( $lesson_id, $course_id = 0, $return_wp_error = true ) {
			global $wpdb;

			try {
				do_action( 'learn-press/before-complete-lesson', $lesson_id, $course_id, $this->get_id() );
				$course_id = $this->_get_course( $course_id );

				$course_data = $this->get_course_data( $course_id );

				$result = false;

				/**
				 * If user has stared a lesson, get user lesson information
				 */
				$item = $course_data->get_item( $lesson_id );
				if ( $item ) {

					if ( $item->is_completed() ) {
						throw new Exception(
							__( 'You have already completed this lesson.', 'learnpress' ),
							LP_COMPLETE_ITEM_FAIL
						);
					}
					// TODO: conflict???
					$time = new LP_Datetime();
					$item->set_end_time( $time->toSql( false ) );
					$item->set_status( 'completed' );
					$item->set_graduation( apply_filters( 'learn-press/complete-lesson-graduation', 'passed' ) );

					$updated = $item->update( true, true );

					if ( is_wp_error( $updated ) ) {
						return $return_wp_error ? $updated : false;
					}

					$result = $this->evaluate_course_results( $this->get_id() );
				}

				do_action( 'learn-press/user-completed-lesson', $lesson_id, $course_id, $this->get_id() );
			} catch ( Exception $ex ) {
				$result = $return_wp_error ? new WP_Error( $ex->getCode(), $ex->getMessage() ) : false;
			}

			return $result;
		}

		/**
		 * Returns TRUE if user has already completed a lesson
		 *
		 * @param int  $lesson_id Lesson id.
		 * @param null $course_id Course id.
		 * @param bool $force
		 *
		 * @return mixed|null
		 */
		public function has_completed_lesson( $lesson_id = 0, $course_id = null, $force = false ) {
			$completed = 'completed' === $this->get_item_status( $lesson_id, $course_id );

			return apply_filters(
				'learn-press/user-has-completed-lesson',
				$completed,
				$lesson_id,
				$course_id,
				$this->get_id()
			);
		}

		/**
		 * Return current status of course for user
		 *
		 * @param int    $course_id
		 * @param string $field
		 * @param bool   $force
		 *
		 * @return mixed
		 */
		public function get_course_info( $course_id, $field = null, $force = false ) {

			if ( $data = $this->get_course_data( $course_id ) ) {
				return $data->get_results( $field );
			}

			return false;
		}

		/**
		 * @param $course_id
		 *
		 * @return mixed
		 * @deprecated
		 */
		public function get_course_info2( $course_id ) {
			_deprecated_function( __FUNCTION__, '3.0.0' );

			return $this->get_course_info( $course_id );
		}

		/**
		 * @param $course_id
		 *
		 * @return int
		 */
		public function get_course_history_id( $course_id ) {
			$history = $this->get_course_info( $course_id );

			return ! empty( $history['history_id'] ) ? $history['history_id'] : 0;
		}

		/**
		 * Get current status of a course for user.
		 *
		 * @param int $course_id
		 *
		 * @return mixed
		 */
		public function get_course_status( $course_id ) {

			$status = false;

			if ( $data = $this->get_course_data( $course_id ) ) {
				$status = $data->get_status();
			}

			return apply_filters( 'learn-press/user-course-status', $status, $course_id, $this->get_id() );
		}

		/**
		 * Controls what this user can do with a course.
		 *
		 * 0    => No accessible
		 * 10   => Normal users (like not logged in)
		 * 20   => Author of course
		 * 30   => Admin site
		 * 35   => No require enrollment
		 * 40   => Ordered but not completed
		 * 50   => Order is completed but not enrolled
		 * 60   => User has already enrolled course
		 * 70   => User has already finished course
		 *
		 * @param int $course_id
		 *
		 * @return int
		 * @since 3.1.0
		 */
		public function get_course_access_level( $course_id ) {
			$access_level = LP_Object_Cache::get(
				'course-' . $course_id . '-' . $this->get_id(),
				'learn-press/course-access-levels'
			);

			if ( false === $access_level ) {
				$course = learn_press_get_course( $course_id );

				if ( ! $course ) {
					$access_level = LP_COURSE_ACCESS_LEVEL_0;
				} elseif ( $this->is_admin() ) {
					$access_level = LP_COURSE_ACCESS_LEVEL_30;
				} elseif ( $this->is_author_of( $course_id ) ) {
					$access_level = LP_COURSE_ACCESS_LEVEL_20;
				} else {
					$access_level = LP_COURSE_ACCESS_LEVEL_10;
				}

				// Default level
				$access_level = apply_filters(
					'learn-press/course-access-level-default',
					$access_level,
					$course_id,
					$this->get_id()
				);
				$course_data  = $this->get_course_data( $course_id );

				if ( $course_data && $course_data->get_user_item_id() ) {
					//                  if ( $course_data->get_access_level() >= 50 ) {
					switch ( $course_data->get_status() ) {
						case 'completed':
						case 'failed':
							$access_level = LP_COURSE_ACCESS_LEVEL_60;
							break;
						case 'in-progress':
						case 'enrolled':
							$access_level = LP_COURSE_ACCESS_LEVEL_70;
							break;
					}
					//                  }
				} else {
					$order = $this->get_course_order( $course_id, 'object', true );

					if ( $order ) {
						switch ( $order->get_status() ) {
							case 'completed':
								$access_level = LP_COURSE_ACCESS_LEVEL_50;
								break;
							default:
								$access_level = LP_COURSE_ACCESS_LEVEL_40;
						}
					}
				}

				LP_Object_Cache::set(
					'course-' . $course_id . '-' . $this->get_id(),
					$access_level,
					'learn-press/course-access-levels'
				);
			}

			return apply_filters( 'learn-press/course-access-level', $access_level, $course_id, $this->get_id() );
		}

		public function get_item_access_level( $item_id, $course_id ) {
			$access_level = 0;

			if ( $course = learn_press_get_course( $course_id ) ) {
				if ( $course->has_item( $item_id ) ) {
					if ( 10 < $this->get_course_access_level( $course_id ) ) {
						$access_level = 10;
					} else {
						$item = $course->get_item( $item_id );
						if ( $item->is_preview() ) {
							$access_level = 10;
						}
					}
				}
			}

			return apply_filters(
				'learn-press/course-item-access-level',
				$access_level,
				$item_id,
				$course_id,
				$this->get_id()
			);
		}

		/**
		 * Set new access-level of an user with a course.
		 *
		 * @param int $access_level
		 * @param int $course_id
		 *
		 * @return mixed
		 * @since 3.1.0
		 */
		public function set_course_access_level( $access_level, $course_id ) {
			if ( $access_level !== $this->get_course_access_level( $course_id ) ) {
				LP_Object_Cache::set(
					'course-' . $course_id . '-' . $this->get_id(),
					$access_level,
					'learn-press/course-access-levels'
				);
			}

			return $access_level;
		}

		/**
		 * Check if user have an access-level.
		 * Consider the passed access-level is max level user have.
		 *
		 * @param int[]  $access_level
		 * @param int    $course_id
		 * @param string $compare
		 *
		 * @return bool
		 * @since 3.1.0
		 */
		public function has_course_access_level( $access_level, $course_id, $compare = '<=' ) {
			$user_access_level = $this->get_course_access_level( $course_id );

			switch ( $compare ) {
				case 'any':
					settype( $access_level, 'array' );
					$has = in_array( $user_access_level, $access_level );
					break;
				default:
					$has = version_compare( $user_access_level, $access_level );
			}

			return $has;
		}

		/**
		 * Check if user has an access-level with a course.
		 *
		 * @param int $access_level
		 * @param int $course_id
		 *
		 * @return bool
		 * @since 3.1.0
		 */
		public function is_access_level( $access_level, $course_id ) {
			$user_access_level = $this->get_course_access_level( $course_id );

			return $user_access_level === $access_level;
		}

		/**
		 * Return true if user has already purchased course
		 * and the order is completed.
		 *
		 * @param int $course_id
		 *
		 * @return bool
		 */
		public function has_purchased_course( $course_id ) {

			$order     = $this->get_course_order( $course_id, 'id', true );
			$purchased = ! empty( $order );
			$purchased = apply_filters( 'learn-press/user-purchased-course', $purchased, $course_id, $this->get_id() );
			// @deprecated
			$purchased = apply_filters(
				'learn_press_user_has_purchased_course',
				$purchased,
				$course_id,
				$this->get_id()
			);

			return $purchased;
		}

		/**
		 * Check if user is already ordered a course.
		 *
		 * @param int $course_id
		 *
		 * @return mixed|LP_Order
		 */
		public function has_ordered_course( $course_id ) {
			$return = apply_filters(
				'learn-press/user-has-ordered-course',
				$this->get_course_order( $course_id ),
				$course_id,
				$this->get_id()
			);

			// Deprecated since 3.0.0
			$return = apply_filters( 'learn_press_user_has_ordered_course', $return, $course_id, $this->get_id() );

			return $return;
		}

		/**
		 * Get order status of a course.
		 *
		 * @param int $course_id
		 *
		 * @return mixed
		 */
		public function get_order_status( $course_id ) {
			$order_id = $this->get_course_order( $course_id, false );
			$return   = apply_filters(
				'learn-press/course-order-status',
				$order_id ? get_post_status( $order_id ) : false,
				$course_id,
				$this->get_id()
			);

			// Deprecated since 3.0.0
			$return = apply_filters( 'learn_press_user_has_ordered_course', $return, $course_id, $this->get_id() );

			return $return;
		}

		/**
		 * @param      $item
		 * @param int  $course_id
		 * @param bool $force
		 *
		 * @return mixed|void
		 */
		public function has_completed_item( $item, $course_id = 0, $force = false ) {

			$course_id = $this->_get_course( $course_id );

			$return  = false;
			$item_id = 0;
			if ( is_numeric( $item ) ) {
				$item_id = absint( $item );
			} else {
				settype( $item, 'array' );
				if ( ! empty( $item['ID'] ) ) {
					$item_id = absint( $item['ID'] );
				}
			}
			if ( $item_id ) {
				if ( empty( $item['item_type'] ) ) {
					$type = learn_press_get_post_type( $item_id );
				} else {
					$type = $item['item_type'];
				}
				if ( ! $type ) {
					$type = learn_press_get_post_type( $item_id );
				}
				if ( $type == 'lp_lesson' ) {
					$return = $this->has_completed_lesson( $item_id, $course_id, $force );
				} elseif ( $type == 'lp_quiz' ) {
					$return = $this->has_completed_quiz( $item_id, $course_id, $force );
				}
			}

			return apply_filters( 'learn_press_user_has_completed_item', $return, $item );
		}

		/**
		 * Get the remaining time of a course for the user.
		 *
		 * @param int $course_id
		 *
		 * @return bool|int|string
		 */
		public function get_course_remaining_time( $course_id ) {
			$course = learn_press_get_course( $course_id );
			$remain = false;

			if ( $course && $course->get_id() ) {
				if ( $course_data = $this->get_course_data( $course_id, true ) ) {
					$remain = $course_data->is_exceeded();
				}
			}

			return $remain > 0 ? learn_press_seconds_to_weeks( $remain ) : false;
		}

		/**
		 * Get the order that contains the course.
		 *
		 * @param int    $course_id
		 * @param string $return type of order to return LP_Order|ID
		 *
		 * @return int|LP_Order|mixed
		 */

		public function get_course_order( $course_id, $return = 'object', $completed = false ) {
			$orders   = $this->get_orders( ! $completed );
			$order_id = false;
			if ( isset( $orders[ $course_id ] ) ) {
				if ( $completed ) {
					$order_ids = $orders[ $course_id ];
					foreach ( $order_ids as $oid ) {
						if ( 'lp-completed' == get_post_status( $oid ) ) {
							$order_id = $oid;
							break;
						}
					}
				} else {
					$order_id = ! empty( $orders[ $course_id ] ) ? $orders[ $course_id ] : false;
				}
			}

			return $order_id ? ( $return === 'object' ? learn_press_get_order( $order_id ) : $order_id ) : false;
		}

		/**
		 * Enroll this user to a course.
		 *
		 * @param int         $course_id
		 * @param int         $order_id - Optional. An user can be enrolled to a course
		 *                                                without an order.
		 * @param bool|string $overwrite - Optional. FALSE will ignore inserting new item
		 *                                if there is a row with the same course and user
		 *                                and/or order. TRUE will overwrite if exists.
		 *                                If $overwrite = 'append' then new row will be
		 *                                inserted if exists.
		 * @param bool        $wp_error - Optional. TRUE will return WP_Error object if
		 *                                              error.
		 *
		 * @return bool|WP_Error
		 * @throws Exception .
		 * @since 3.3.0
		 */
		public function enroll_course( $course_id = 0, $order_id = 0, $overwrite = false, $wp_error = false ) {

			try {
				$user_item_api = new LP_User_Item_CURD();
				$find_query    = array(
					'item_id' => $course_id,
					'user_id' => $this->get_id(),
				);

				if ( $order_id ) {
					$find_query['ref_id'] = $order_id;
				}

				$course       = learn_press_get_course( $course_id );
				$course_items = $user_item_api->get_items_by( $find_query );
				$course_item  = false;

				if ( $course_items ) {
					// User is learning course or course result is under evaluation
					if ( in_array( $course_items[0]->status, array( 'in-progress', 'under-evaluation' ) ) ) {
						return $course_items[0]->user_item_id;
					}

					// User has finished course (passed or failed)
					if ( ! in_array( $course_items[0]->status, array( 'passed', 'failed', 'enrolled' ) ) ) {
						$course_item = $course_items[0];
					}
				}

				if ( ! $course_item ) {
					$course_item = LP_User_Item::get_empty_item();
				}

				$user_id = $this->get_id();

				$course_item['user_id']      = $user_id;
				$course_item['item_id']      = $course_id;
				$course_item['item_type']    = learn_press_get_post_type( $course_id );
				$course_item['ref_id']       = $order_id;
				$course_item['ref_type']     = ( $order_id != 0 ) ? learn_press_get_post_type( $order_id ) : '';
				$course_item['start_time']   = current_time( 'mysql', true );
				$course_item['access_level'] = 50;

				/**
				 * @editor tungnx
				 * @fixed: case no auto enroll
				 */
				if ( 'yes' == LP_Settings::get_option( 'auto_enroll' ) ) {
					$course_item['graduation'] = 'in-progress';
				}

				$user_course = new LP_User_Item_Course( $course_item );
				$user_course->set_status( LP_COURSE_PURCHASED );

				if ( ! $user_course->update() ) {
					throw new Exception( __( 'Update user item error.', 'learnpress' ) );
				}

				$user_id = is_user_logged_in() ? $this->get_id() : 0;

				global $wpdb;
				$query = $wpdb->prepare(
					"
					UPDATE {$wpdb->learnpress_user_items}
					SET access_level = %d
					WHERE user_id = %d
						AND item_id = %d
						AND user_item_id NOT IN(%d)
				",
					0,
					$user_id,
					$course_id,
					$user_course->get_user_item_id()
				);
				$wpdb->query( $query );

				$return = $user_course->get_user_item_id();
			} catch ( Exception $ex ) {
				if ( $wp_error ) {
					return new WP_Error( 'enroll_course_error', $ex->getMessage() );
				}

				return false;
			}

			return $return;
		}

		/**
		 * Send mail when user enrolled course
		 * @param $user_id
		 * @param $course_id
		 */
		public function enrolled_sendmail( $user_id, $course_id ) {
			$user_course = $this->get_course_data( $this->get_id() );
			if ( $this->has_enrolled_course( $course_id ) ) {
				// Trigger action
				do_action( 'learn-press/user-enrolled-course', $course_id, $user_id, $user_course );
			}
		}

		/**
		 * Enroll this user to a course.
		 *
		 * @param int $course_id
		 * @param int $order_id
		 *
		 * @return mixed|WP_Error
		 * @throws Exception
		 */
		/**
		 * Enroll this user to a course.
		 *
		 * @param int  $course_id
		 * @param int  $order_id
		 * @param bool $force - Optional. Force create db record for preview quiz case
		 * @param bool $wp_error - Optional. TRUE will return WP_Error object if there is an error.
		 * @editor tungnx - comment - not use
		 * @return bool|mixed|WP_Error
		 */
		public function enroll( $course_id, $order_id, $force = false, $wp_error = false ) {
			global $wpdb;

			_deprecated_function( __FUNCTION__, '4.1.0' );

			try {
				$course  = learn_press_get_course( $course_id );
				$user_id = $this->get_id();

				if ( $course->is_required_enroll() && ! $force ) {
					$order = learn_press_get_order( $order_id );

					if ( ! $order ) {
						throw new Exception( __( 'Failed to enroll course.', 'learnpress' ), 10000 );
					}

					if ( ! $this->can_enroll_course( $course_id ) ) {
						throw new Exception( __( 'Failed to enroll course.', 'learnpress' ), 10001 );
					}

					if ( ! $this->get_id() ) {
						throw new Exception( __( 'Please login to enroll course.', 'learnpress' ), 10002 );
					}
				}

				$return = $this->enroll_course( $course_id, $order_id, false, $wp_error );

				return $return;

			} catch ( Exception $ex ) {
				return new WP_Error( $ex->getCode(), $ex->getMessage() );
			}
		}

		/**
		 * @param $question_id
		 *
		 * @return null|string
		 */
		public function get_quiz_by_question( $question_id ) {
			global $wpdb;
			$query = $wpdb->prepare(
				"
			SELECT quiz_id
			FROM {$wpdb->prefix}learnpress_user_items uq
			INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta uqm ON uqm.learnpress_user_item_id = uq.user_item_id AND uqm.meta_key = %s AND uqm.meta_value LIKE %s
		",
				'questions',
				'%i:' . $wpdb->esc_like( $question_id . '' ) . ';%'
			);

			return $wpdb->get_var( $query );
		}

		/**
		 * @param      $question_id
		 * @param null $quiz_id
		 *
		 * @return bool
		 */
		public function get_answer_results( $question_id, $quiz_id = null ) {

			_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '3.0.0' );

			$data = false;
			if ( ! $quiz_id ) {
				$quiz_id = $this->get_quiz_by_question( $question_id );
			}
			if ( $quiz_id ) {
				$question = LP_Question::get_question( $question_id );

				if ( $question ) {
					$quiz_results = $this->get_quiz_results( $quiz_id );
					if ( ! empty( $quiz_results->question_answers ) ) {
						$question_answer = array_key_exists(
							$question_id,
							$quiz_results->question_answers
						) ? $quiz_results->question_answers[ $question_id ] : null;
						$data            = $question->check( $question_answer );
					}
				}
			}

			return $data;
		}

		/**
		 * @param      $question_id
		 * @param null $quiz_id
		 *
		 * @return bool
		 */
		public function is_answered_question( $question_id, $quiz_id = null ) {
			if ( empty( $this->answered_questions ) ) {
				$this->answered_questions = array();
			}
			if ( ! $quiz_id ) {
				$quiz_id = $this->get_quiz_by_question( $question_id );
			}

			$results  = $this->get_quiz_results( $quiz_id );
			$answered = ! empty( $results->question_answers ) ? $results->question_answers : array();

			return $answered ? array_key_exists( $question_id, $answered ) : false;
		}

		/**
		 * @param array $args
		 *
		 * @return LP_Query_List_Table
		 */
		public function get_purchased_courses( array $args = array() ): LP_Query_List_Table {
			return $this->_curd->query_purchased_courses( $this->get_id(), $args );
		}

		/**
		 * @return array
		 */
		public function get_roles() {
			return (array) $this->get_data( 'roles' );
		}

		/**
		 * @param     $question_id
		 * @param     $quiz_id
		 * @param int $course_id
		 *
		 * @return bool
		 */
		public function has_checked_answer( $question_id, $quiz_id, $course_id = 0 ) {
			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			$quiz_data = $this->get_item_data( $quiz_id, $course_id );

			return $quiz_data ? $quiz_data->has_checked_question( $question_id ) : false;
		}

		/**
		 * @param     $question_id
		 * @param     $quiz_id
		 * @param int $course_id
		 *
		 * @return bool
		 */
		public function has_hinted_answer( $question_id, $quiz_id, $course_id = 0 ) {
			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			$quiz_data = $this->get_item_data( $quiz_id, $course_id );

			return $quiz_data ? $quiz_data->has_hinted_question( $question_id ) : false;
		}

		/**
		 * Return TRUE if user is already exists.
		 *
		 * @return bool
		 */
		public function is_exists() {
			return ! ! get_user_by( 'id', $this->get_id() );
		}

		/**
		 * Check if the user is logged in.
		 *
		 * @return bool
		 */
		public function is_logged_in() {
			return $this->get_id() == get_current_user_id();
		}

		/**
		 * Get upload profile src
		 * Option: null: get origin picture, "thumbnail": get thumbnail picture
		 *
		 * @param mixed $size
		 *
		 * @return string
		 */
		public function get_upload_profile_src( $size = '' ) {
			return LP_Profile::instance( $this->get_id() )->get_upload_profile_src( $size );
		}

		/**
		 * @param string $type
		 * @param int    $size
		 * @param bool   $src_only
		 *
		 * @return false|string
		 */
		public function get_profile_picture( $type = '', $size = 96, $src_only = false ) {
			return LP_Profile::instance( $this->get_id() )->get_profile_picture( $type, $size );
		}

		/**
		 * Get links socials of use on Profile page
		 *
		 * @param int $user_id
		 * @return array
		 */
		public function get_profile_socials( int $user_id = 0 ): array {
			$socials    = array();
			$extra_info = learn_press_get_user_extra_profile_info( $user_id );

			if ( $extra_info ) {
				foreach ( $extra_info as $k => $v ) {
					if ( empty( $v ) ) {
						continue;
					}

					switch ( $k ) {
						case 'facebook':
							$i = '<i class="fab fa-facebook-f"></i>';
							break;
						case 'twitter':
							$i = '<i class="fab fa-twitter"></i>';
							break;
						case 'googleplus':
							$i = '<i class="fab fa-google-plus-g"></i>';
							break;
						case 'youtube':
							$i = '<i class="fab fa-youtube"></i>';
							break;
						default:
							$i = sprintf( '<i class="fab fa-%s"></i>', $k );
					}

					$icon          = apply_filters(
						'learn-press/user-profile-social-icon',
						$i,
						$k,
						$this->get_id(),
						$this
					);
					$socials[ $k ] = sprintf( '<a href="%s">%s</a>', esc_url( $v ), $icon );
				}
			}

			return apply_filters( 'learn-press/user-profile-socials', $socials, $this->get_id(), $this );
		}

		/**
		 * Check if user can access to a course.
		 *
		 * @param int $course_id
		 *
		 * @return mixed
		 */
		public function can_access_course( $course_id ) {

			$accessible = $this->has_course_access_level(
				array(
					LP_COURSE_ACCESS_LEVEL_60,
					LP_COURSE_ACCESS_LEVEL_70,
				),
				$course_id,
				'any'
			);

			$accessible = apply_filters(
				'learn-press/user-can-access-course',
				$accessible,
				$course_id,
				$this->get_id()
			);

			return $accessible;
		}

		public function is_course_in_progress( $course_id ) {
			$course_data = $this->get_course_data( $course_id );

			if ( ! $course_data ) {
				return false;
			}

			return $course_data->get_status( 'graduation' ) === 'in-progress';
		}

		/**
		 * Return TRUE if user can do a quiz
		 *
		 * @param     $quiz_id
		 * @param int $course_id
		 *
		 * @return bool
		 * @throws Exception
		 */
		public function can_do_quiz( $quiz_id, $course_id = 0 ) {
			$course = learn_press_get_course( $course_id );

			if ( $course->is_required_enroll() ) {
				$can = $this->has_course_status(
					$course_id,
					array( 'enrolled' )
				) && ! $this->has_started_quiz( $quiz_id, $course_id );
			} else {
				$can = ! $this->has_started_quiz( $quiz_id, $course_id );
			}

			return apply_filters( 'learn_press_user_can_do_quiz', $can, $quiz_id, $this->get_id(), $course_id );
		}

		public function evaluate_course_results( $course_id ) {
			$user_course = $this->get_course_data( $course_id );

			return isset( $user_course ) ? $user_course->get_results( 'result' ) : 0;
		}

		public function has_reached_passing_condition( $course_id ) {
			$course = learn_press_get_course( $course_id );
			$result = $this->evaluate_course_results( $course_id );

			return $return = $result >= $course->get_passing_condition();
		}

		/**
		 * Check if all items in course completed.
		 *
		 * @return boolean
		 * @author Nhamdv <email@email.com>
		 */
		public function is_completed_all_items( $course_id ) {
			$course = learn_press_get_course( $course_id );

			$course_data = $this->get_course_data( $course_id );

			$course_results = $course_data->get_results( false );

			return ( $course_results['completed_items'] >= $course->count_items( '', true ) ) ? true : false;
		}

		public function get_role() {
			return $this->is_admin() ? 'admin' : ( $this->is_instructor() ? 'instructor' : 'user' );
		}

		/**
		 * Get user course's grade.
		 * Possible values:
		 *        + passed        User has finished and passed course.
		 *        + failed        User has finished but failed.
		 *        + in-progress    User still is learning course.
		 *
		 * @param $course_id
		 *
		 * @return string|bool
		 */
		public function get_course_grade( $course_id ) {
			$grade       = false;
			$course_data = $this->get_course_data( $course_id );

			if ( $course_data ) {
				$grade = $course_data->get_status( 'graduation' );
			}

			return apply_filters( 'learn-press/user-course-grade', $grade, $this->get_id(), $course_id );
		}

		/**
		 * Check if user is a GUEST by checking the meta _lp_temp_user is exists.
		 *
		 * @return bool
		 */
		public function is_guest() {
			return ! $this->get_id() || ! get_user_by( 'id', $this->get_id() );
		}

		/**
		 * Load course data for the user.
		 *
		 * @param mixed $the_course
		 */
		public function read_course( $the_course ) {
			$this->_curd->read_course( $this->get_id(), $the_course );
		}

		/**
		 * Check if user can edit a post.
		 *
		 * @param int $post_id
		 *
		 * @return bool
		 */
		public function can_edit( $post_id ) {
			if ( $this->get_id() !== get_current_user_id() ) {
				return false;
			}

			return current_user_can( 'edit_post', $post_id );
		}

		/**
		 * @return array|mixed
		 */
		public function get_email() {
			return $this->get_data( 'email' );
		}

		/**
		 * Return user_login of the user.
		 *
		 * @return string
		 */
		public function get_username() {
			return $this->get_data( 'user_login' );
		}

		/**
		 * Return user bio information.
		 *
		 * @return string
		 */
		public function get_description() {
			return $this->get_data( 'description' );
		}

		/**
		 * Return user first name.
		 *
		 * @return string
		 */
		public function get_first_name() {
			return $this->get_data( 'first_name' );
		}

		/**
		 * Return user last name.
		 *
		 * @return string
		 */
		public function get_last_name() {
			return $this->get_data( 'last_name' );
		}

		/**
		 * Return user nickname.
		 *
		 * @return string
		 */
		public function get_nickname() {
			return $this->get_data( 'nickname' );
		}

		/**
		 * Return user display name.
		 *
		 * @return string
		 */
		public function get_display_name() {
			return $this->get_data( 'display_name' );
		}
	}
}
