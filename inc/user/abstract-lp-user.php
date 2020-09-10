<?php

/**
 * Class LP_Abstract_User
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.1
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
			'roles'         => array()
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
		 * @param int|LP_Abstract_Course $course_id
		 * @param bool                   $check_exists
		 *
		 * @return LP_User_Item_Course|LP_User_Item_Quiz|bool
		 */
		public function get_course_data( $course_id, $check_exists = false ) {

			if ( is_a( $course_id, 'LP_Abstract_Course' ) ) {
				$course_id = $course_id->get_id();
			}

			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			if ( false === ( $object_course_data = LP_Object_Cache::get( 'course-' . $this->get_id() . '-' . $course_id, 'learn-press/user-item-object-courses' ) ) ) {
				$result = $this->_curd->read_course( $this->get_id(), $course_id );

				if ( $result ) {
					$object_course_data = new LP_User_Item_Course( $result );
				} else {
					$object_course_data = new LP_User_Item_Course( $course_id );
				}

				LP_Object_Cache::set( 'course-' . $this->get_id() . '-' . $course_id, $object_course_data, 'learn-press/user-item-object-courses' );
			}

			if ( $object_course_data ) {
				if ( ! $object_course_data->get_item_id() && $check_exists ) {
					return false;
				}
			}

			return $object_course_data;
		}

		/**
		 * @param int $item_id
		 * @param int $course_id
		 *
		 * @return LP_User_Item_Quiz|LP_User_Item|bool
		 */
		public function get_item_data( $item_id, $course_id ) {
			return $this->get_user_item( $item_id, $course_id );
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

			if ( $course_data = $this->get_course_data( $course_id ) ) {
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
			if ( $data = $this->get_quiz_data( $quiz_id, $course_id ) ) {
				$checked = $data->has_checked_question( $question_id );
			}

			return apply_filters( 'learn-press/user/checked-question', $checked, $question_id, $quiz_id, $course_id, $this->get_id() );
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
			if ( $course = learn_press_get_course( $course_id ) ) {
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
			if ( false !== ( $course = $this->_get_course( $course_id, 'object' ) ) ) {
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

			return apply_filters( 'learn-press/user-has-item-status', in_array( $status, $statuses ), $statuses, $item_id, $course_id, $this->get_id() );
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
			$records = LP_Object_Cache::get( 'course-item-' . $this->get_id() . '-' . $course_id . '-' . $item_id, 'lp-user-course-items' );

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
		 * @return mixed|WP_Error
		 * @throws Exception
		 */
		public function start_quiz( $quiz_id, $course_id = 0, $wp_error = false ) {
			try {
				if ( $item_id = learn_press_get_request( 'lp-preview' ) ) {
					learn_press_add_message( __( 'You cannot start a quiz in preview mode.', 'learnpress' ), 'error' );
					wp_redirect( learn_press_get_preview_url( $item_id ) );
					exit();
				}

				// Validate course and quiz
				if ( false === ( $course_id = $this->_verify_course_item( $quiz_id, $course_id ) ) ) {
					throw new Exception( __( 'Course does not exist or does not contain the quiz', 'learnpress' ), LP_INVALID_QUIZ_OR_COURSE );
				}
				$course       = learn_press_get_course( $course_id );
				$access_level = $this->get_course_access_level( $course_id );

				// If user has already finished the course
				if ( $access_level === LP_COURSE_ACCESS_LEVEL_70 ) {
					throw new Exception( __( 'You have already finished the course of this quiz', 'learnpress' ), LP_COURSE_IS_FINISHED );
				}

				if ( $course->is_required_enroll() && $access_level < LP_COURSE_ACCESS_LEVEL_60 ) {
					throw new Exception( __( 'Please enroll course before starting quiz.', 'learnpress' ), LP_COURSE_IS_FINISHED );
				}

				// Check if user has already started or completed quiz
				if ( $this->has_item_status( array( 'started', 'completed' ), $quiz_id, $course_id ) ) {
					throw new Exception( __( 'User has started or completed quiz', 'learnpress' ), LP_QUIZ_HAS_STARTED_OR_COMPLETED );
				}
				$user = LP_Global::user();

				if ( $course->is_required_enroll() && $user->is_guest()/* && ! $quiz->get_preview() */ ) {
					throw new Exception( __( 'You have to login for starting quiz.', 'learnpress' ), LP_REQUIRE_LOGIN );
				}

				/**
				 * @see learn_press_hk_before_start_quiz
				 */
				$do_start = apply_filters( 'learn-press/before-start-quiz', true, $quiz_id, $course_id, $this->get_id() );

				if ( ! $do_start ) {
					return false;
				}

				$course_data = $this->get_course_data( $course_id );
				$quiz        = learn_press_get_quiz( $quiz_id );
				$quiz_data   = $course_data->get_item( $quiz_id );
				if ( ! $quiz_data ) {
					$user_item_api = new LP_User_Item_CURD();
					$course_item   = $user_item_api->get_item_by(
						array(
							'item_id' => $course_id,
							'user_id' => $user->get_id(),
						)
					);

					$quiz_item              = LP_User_Item::get_empty_item();
					$quiz_item['user_id']   = $user->get_id();
					$quiz_item['item_id']   = $quiz_id;
					$quiz_item['item_type'] = learn_press_get_post_type( $quiz_id );
					$quiz_item['ref_id']    = $course_id;
					$quiz_item['ref_type']  = learn_press_get_post_type( $course_id );
					$quiz_item['parent_id'] = $course_item->user_item_id;

					$quiz_data = new LP_User_Item_Quiz( $quiz_item );
				}

				if ( ! $enable_history = $quiz->enable_archive_history() ) {
					if ( $quiz_data->get_user_item_id() ) {
						global $wpdb;
						$query = $wpdb->prepare( "
						DELETE FROM {$wpdb->learnpress_user_items}
						WHERE user_id = %d AND item_id = %d AND user_item_id <> %d
					", $this->get_id(), $quiz_id, $quiz_data->get_user_item_id() );

						$wpdb->query( $query );
					} else {
						$course_data->update_item_retaken_count( $quiz_id, 0 );
					}
				} else {
					$count_history = $course_data->count_history_items( $quiz_id );
				}

				$course_data->update_item_retaken_count( $quiz_id, '+1' );
				$quiz_data->set_status( 'started' );
				$quiz_data->set_user_id( $user->get_id() );
				$date = new LP_Datetime();
				$quiz_data->set_start_time( $date->toSql() );
				$quiz_data->set_end_time_gmt( $date->toSql( false ) );

				if ( $quiz_data->update() ) {
					$course_data->set_item( $quiz_data );
				}

				if ( $questions = $quiz->get_questions() ) {
					$question_id = reset( $questions );
					learn_press_update_user_item_meta( $quiz_data->get_user_item_id(), '_current_question', $question_id );
				}

				// @deprecated
				do_action( 'learn_press_user_start_quiz', $quiz_data, $quiz_id, $course_id, $this->get_id() );

				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/user/quiz-started', $quiz_id, $course_id, $this->get_id() );

				$return = $quiz_data->get_mysql_data();
			}
			catch ( Exception $ex ) {
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
		 * @return mixed
		 */
		public function finish_quiz( $quiz_id, $course_id, $wp_error = false ) {

			if ( ! apply_filters( 'learn_press_before_user_finish_quiz', true, $quiz_id, $course_id, $this->get_id() ) ) {
				return false;
			}
			$return = false;

			try {
				// Validate course and quiz
				if ( false === ( $course_id = $this->_verify_course_item( $quiz_id, $course_id ) ) ) {
					throw new Exception( __( 'Course is not exists or does not contain the quiz', 'learnpress' ), LP_INVALID_QUIZ_OR_COURSE );
				}

				// If user has already finished the course
				if ( $this->has_finished_course( $course_id ) ) {
					throw new Exception( __( 'User has already finished course of this quiz', 'learnpress' ), LP_COURSE_IS_FINISHED );

				}

				// Check if user has already started or completed quiz
				if ( $this->has_item_status( array( 'completed' ), $quiz_id, $course_id ) ) {
					throw new Exception( __( 'User has completed quiz', 'learnpress' ), LP_QUIZ_HAS_STARTED_OR_COMPLETED );
				}

				$user_quiz = $this->get_item_data( $quiz_id, $course_id );

				$user_quiz->finish();

				/**
				 * @deprecated
				 */
				do_action( 'learn_press_user_finish_quiz', $quiz_id, $this->get_id() );

				do_action( 'learn-press/user/quiz-finished', $quiz_id, $course_id, $this->get_id() );
			}
			catch ( Exception $ex ) {
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
		 * @return bool|LP_User_Item_Quiz|WP_Error
		 *
		 * @throws Exception
		 */
		public function retake_quiz( $quiz_id, $course_id, $wp_error = false ) {
			$return = false;

			try {
				// Validate course and quiz
				if ( false === ( $course_id = $this->_verify_course_item( $quiz_id, $course_id ) ) ) {
					throw new Exception( sprintf( __( 'Course does not exist or does not contain the quiz.', 'learnpress' ), __CLASS__, __FUNCTION__ ), LP_INVALID_QUIZ_OR_COURSE );
				}

				// If user has already finished the course
				if ( $this->has_finished_course( $course_id ) ) {
					throw new Exception( sprintf( __( 'You can not redo a quiz in a finished course.', 'learnpress' ), __CLASS__, __FUNCTION__ ), LP_COURSE_IS_FINISHED );

				}

				// Check if user has already started or completed quiz
				if ( ! $this->has_item_status( array( 'completed' ), $quiz_id, $course_id ) ) {
					throw new Exception( sprintf( __( '%1$s::%2$s - User has not completed quiz.', 'learnpress' ), __CLASS__, __FUNCTION__ ), LP_QUIZ_HAS_STARTED_OR_COMPLETED );
				}

				$course_data = $this->get_course_data( $course_id );
				$quiz        = learn_press_get_quiz( $quiz_id );
				$quiz_data   = $course_data->get_item( $quiz_id );

				if ( ! $enable_history = $quiz->enable_archive_history() ) {
					if ( $user_item_id = $quiz_data->get_user_item_id() ) {
						global $wpdb;
						$query_meta = $wpdb->prepare( "
							DELETE FROM {$wpdb->learnpress_user_itemmeta}
							WHERE learnpress_user_item_id = %d
						", $user_item_id );
						$wpdb->query( $query_meta );

						$query = $wpdb->prepare( "
							DELETE FROM {$wpdb->learnpress_user_items}
							WHERE user_id = %d AND item_id = %d AND user_item_id <> %d
						", $this->get_id(), $quiz_id, $quiz_data->get_user_item_id() );
						$wpdb->query( $query );
					} else {
						$course_data->update_item_retaken_count( $quiz_id, 0 );
					}
				} else {
					$count_history = $course_data->count_history_items( $quiz_id );
				}

				$course_data->update_item_retaken_count( $quiz_id, '+1' );
				$quiz_data->set_status( 'started' );
				$quiz_data->set_start_time( current_time( 'mysql' ), true );
				$quiz_data->set_end_time( '0000-00-00 00:00:00' );
				$quiz_data->set_end_time_gmt( '0000-00-00 00:00:00' );
				$quiz_data->set_status( 'started' );

				if ( $quiz_data->update() ) {
					$quiz_data->update_meta(
						array(
							'_question_answers' => false,
							'_grade'            => false,
							'results'           => false
						)
					);

					$course_data->set_item( $quiz_data );
				}

				if ( $questions = $quiz->get_questions() ) {
					$question_id = reset( $questions );
					learn_press_update_user_item_meta( $quiz_data->get_user_item_id(), '_current_question', $question_id );
				}

				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/user/quiz-redone', $quiz_id, $course_id, $this->get_id() );

				$return = $quiz_data;
			}
			catch ( Exception $ex ) {
				$return = $wp_error ? new WP_Error( $ex->getCode(), $ex->getMessage() ) : false;
				do_action( 'learn-press/user/retake-quiz-failure', $quiz_id, $course_id, $this->get_id() );
			}

			return $return;
		}

		protected function _insert_quiz_item( $quiz_id, $course_id ) {
			_deprecated_function( __FUNCTION__, '3.1.0' );
			$course_data   = $this->get_course_data( $course_id );
			$quiz          = learn_press_get_quiz( $quiz_id );
			$quiz_data     = $course_data->get_item( $quiz_id );
			$count_history = $course_data->count_history_items( $quiz_id );
			if ( ! $quiz->enable_archive_history() ) {
				if ( $quiz_data->get_user_item_id() ) {
					global $wpdb;
					$query = $wpdb->prepare(
						"
						DELETE FROM {$wpdb->learnpress_user_items}
						WHERE user_id = %d AND item_id = %d AND user_item_id <> %d
					",
						$this->get_id(),
						$quiz_id,
						$quiz_data->get_user_item_id()
					);

					$wpdb->query( $query );
				} else {
					$course_data->update_item_retaken_count( $quiz_id, 0 );
				}
			}

			if ( $quiz_data->get_status() === 'completed' ) {
				$course_data->update_item_retaken_count( $quiz_id, '+1' );
			}

			$start_time = new LP_Datetime( current_time( 'mysql' ) );
			$item_data  = array(
				'user_id'        => $this->get_id(),
				'item_id'        => $quiz_id,
				'start_time'     => $start_time->toSql(),
				'start_time_gmt' => $start_time->toSql( false ),
				'end_time'       => '0000-00-00 00:00:00',
				'end_time_gmt'   => '0000-00-00 00:00:00',
				'item_type'      => LP_QUIZ_CPT,
				'status'         => 'started',
				'ref_id'         => $course_id,
				'ref_type'       => LP_COURSE_CPT,
				'parent_id'      => $course_data->get_user_item_id(),
				'user_item_id'   => 0, // insert
			);

			$last_results         = $this->get_item_archive( $quiz_id, $course_id, true );
			$set_current_question = false;

			// If there is no a record
			if ( ! $last_results ) {
				$item_data            = apply_filters( 'learn-press/insert-user-item-data', $item_data, $quiz_id, $course_id, $this->get_id() );
				$set_current_question = true;
				// learn_press_update_user_item_field( $item_data );
			} else {

				// If there is one record but it's status is not valid then
				// update it as started
				if ( in_array( $last_results['status'], array( '', 'viewed' ) ) ) {
					$last_results['status']         = 'started';
					$last_results['start_time']     = $start_time->toSql();
					$last_results['start_time_gmt'] = $start_time->toSql( false );

					$item_data            = apply_filters( 'learn-press/update-user-item-data', $last_results, $quiz_id, $course_id, $this->get_id() );
					$set_current_question = true;
				}
			}

			$this->_curd->update_user_item( $this->get_id(), $quiz_id, $item_data, $course_id );
			$return = $this->get_item_archive( $quiz_id, $course_id, true );

			if ( $return && $set_current_question ) {
				$quiz = learn_press_get_quiz( $quiz_id );
				if ( $first_question = $quiz->get_question_at( 0 ) ) {
					learn_press_update_user_item_meta( $return['user_item_id'], '_current_question', $first_question );
				}
			}

			return $return;
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
			$item = false;
			if ( false !== ( $items = LP_Object_Cache::get( 'course-item-' . $this->get_id() . '-' . $course_id . '-' . $item_id, 'learn-press/user-course-items' ) ) ) {
				// Only get status of a newest record.
				if ( $last ) {
					$item = reset( $items );
				} else {
					$item = $items;
				}
			}

			return $item;
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

			if ( $course_data && $item_result = $course_data->get_item_result( $item_id, false ) ) {
				$grade = isset( $item_result['grade'] ) ? $item_result['grade'] : false;
			}

			return apply_filters( 'learn-press/user-item-grade', $grade, $item_id, $this->get_id(), $course_id );
		}

		/**
		 * Get current status of an item for user.
		 *
		 * @param int  $item_id
		 * @param int  $course_id
		 * @param bool $force
		 *
		 * @return bool|mixed
		 */
		public function get_item_status( $item_id, $course_id = 0, $force = false ) {

			// Deprecated third argument
			if ( func_num_args() >= 3 ) {
				_deprecated_argument( __FUNCTION__ . ' {$force}', '3.0.0' );
			}

			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			$status = false;

			if ( false !== ( $item = $this->get_item( $item_id, $course_id, true ) ) ) {
				$status = $item['status'];
			}

			$status = apply_filters( 'learn-press/user-item-status', $status, $item_id, $this->get_id(), $course_id );

			/**
			 * @deprecated
			 */
			$status = apply_filters( 'learn_press_user_course_item_status', $status, $item_id, $course_id, $this->get_id() );

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
			$return = false;

			if ( ( $course_data = $this->get_course_data( $course_id ) ) && $course_data->get_user_item_id() ) {
				// learn_press_show_log($course_data, $course_data->get_item( $item_id ));

				if ( $item = $course_data->get_item( $item_id ) ) {
					learn_press_show_log( 'HERE' );
				} else {
					$item = LP_User_Item::get_item_object( $item_id );
					$item->set_ref_id( $course_id );
					$item->set_parent_id( $course_data->get_user_item_id() );
				}

				if ( $return = $item->update() ) {
					$course_data->set_item( $item );
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

			if ( false == ( $id = learn_press_get_user_item_meta( $course_data->get_user_item_id(), '_current_item', true ) ) || $this->has_completed_item( $id, $course_id ) ) {

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
				return apply_filters( 'learn-press/current-course-item-permalink', $course->get_item_link( $id ), $course_id, $this->get_id() );
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
				return apply_filters( 'learn-press/current-user-question-permalink', $quiz->get_question_link( $question_id ), $quiz_id, $course_id, $this->get_id() );
			}

			return apply_filters( 'learn-press/current-user-question', $question_id );
		}

		public function get_prev_question( $quiz_id = null, $course_id, $permalink = false ) {
			if ( ! $quiz_id ) {
				$quiz_id = $this->get_current_item( $course_id );
			}

			if ( ! $quiz_id ) {
				return false;
			}
			$current = $this->get_current_question( $quiz_id, $course_id );
			$quiz    = learn_press_get_quiz( $quiz_id );

			return $quiz->get_prev_question( $current );
		}

		public function get_next_question( $quiz_id = null, $course_id, $permalink = false ) {
			if ( ! $quiz_id ) {
				$quiz_id = $this->get_current_item( $course_id );
			}

			if ( ! $quiz_id ) {
				return false;
			}

			$current = $this->get_current_question( $quiz_id, $course_id );
			$quiz    = learn_press_get_quiz( $quiz_id );

			return $quiz->get_next_question( $current );
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

			$status = $this->get_quiz_status( $quiz_id, $course_id, $force );

			settype( $statuses, 'array' );

			return apply_filters( 'learn_press_user_has_quiz_status', in_array( $status, $statuses ), $statuses, $status, $quiz_id, $course_id, $this->get_id() );
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

			//$cached = LP_Cache::get_quiz_history( false, array() );// LP_Object_Cache::get( 'user-quiz-history', 'learnpress' );
			$cached = array();

			if ( ( ! array_key_exists( $key, $cached ) || $force ) && $quizzes && in_array( $quiz_id, $quizzes ) ) {
				global $wpdb;
				$t1             = $wpdb->learnpress_user_items; // {$wpdb->learnpress_user_quizzes}
				$t2             = $wpdb->learnpress_user_itemmeta; // {$wpdb->learnpress_user_quizzes}
				$in             = array_fill( 0, sizeof( $quizzes ), '%d' );
				$prepare_params = array_merge(
					array( 'lp_quiz', $this->get_id(), $course_id ),
					$quizzes
				);
				$query          = $wpdb->prepare( "
					SELECT * FROM $t1 uq
					WHERE uq.item_type = %s
						AND uq.user_id = %d
						AND uq.ref_id = %d
						AND uq.item_id IN(" . join( ',', $in ) . ")"
					, $prepare_params );

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
				//P_Cache::set_quiz_history( $cached );
			}

			return apply_filters( 'learn_press_user_quiz_history', isset( $cached[ $key ] ) ? $cached[ $key ] : array(), $this, $quiz_id );
		}

		private function _get_quiz_meta( $user_item_id ) {
			global $wpdb;
			settype( $user_item_id, 'array' );
			$in = array_fill( 0, sizeof( $user_item_id ), '%d' );

			$query = $wpdb->prepare( "
				SELECT learnpress_user_item_id as user_item_id, meta_key, meta_value, item_id
				FROM {$wpdb->prefix}learnpress_user_itemmeta im
				INNER JOIN {$wpdb->prefix}learnpress_user_items i ON i.user_item_id = im.learnpress_user_item_id
				WHERE learnpress_user_item_id IN(" . join( ',', $in ) . ")	"
				, $user_item_id );

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
		public function can_purchase_course( $course_id ) {
			$course      = learn_press_get_course( $course_id );
			$purchasable = $course->is_purchasable();

			// @deprecated
			$purchasable = apply_filters( 'learn_press_user_can_purchase_course', $purchasable, $this, $course_id );

			// since 3.0.0
			return apply_filters( 'learn-press/user/can-purchase-course', $purchasable, $this->get_id(), $course_id );
		}

		/**
		 * Return true if user can enroll a course.
		 *
		 * @param int $course_id
		 *
		 * @return bool|string
		 */
		public function can_enroll_course( $course_id ) {
			$course = learn_press_get_course( $course_id );

			// Course is published and not reached limitation
			$can_enroll = ! ! $course && $course->is_publish();// && $course->is_in_stock();

			if ( $can_enroll && $course->is_free() && ! $course->is_required_enroll() && ! $course->is_in_stock() ) {
				$can_enroll = false;
			}

			if ( $can_enroll && ! $course->is_free() && ! $this->has_purchased_course( $course_id ) ) {
				$can_enroll = false;

			}

			return apply_filters( 'learn-press/can-enroll-course', $can_enroll, $course_id, $this->get_id() );
		}

		/**
		 * Check if the user can access to an item inside course.
		 *
		 * @param int $item_id
		 * @param int $course_id
		 *
		 * @return bool|string
		 * @since  3.1.0
		 */
		public function can_view_item( $item_id = 0, $course_id = 0 ) {
			$view   = false;
			$course = learn_press_get_course( $course_id );

			if ( ! $course ) {
				return $view;
			}

			if ( ! $course_id ) {
				$course_id = $course->get_id();
			}

			// get item (quiz, assignment, v.v...)
			$item = $course->get_item( $item_id );

			if ( $course->is_publish() && $item ) {
				$is_enrolled = $this->has_course_access_level( array( LP_COURSE_ACCESS_LEVEL_60, LP_COURSE_ACCESS_LEVEL_70 ), $course_id, 'any' );

				if ( $is_enrolled ) {
					$view = 'enrolled';

					if ( $item->is_blocked() ) {
						$view = 'is_blocked';
					}
				} elseif ( ! $course->is_required_enroll() ) {
					$view = 'no-required-enroll';
				} elseif ( $item->is_preview() ) {
					$view = 'preview';
				} elseif ( $this->is_admin() ) {
					$view = 'admin';
				} elseif ( $this->is_author_of( $item_id ) ) {
					$view = 'author';
				}
			} elseif ( $this->is_admin() ) {
				$view = 'admin';
			} elseif ( $this->is_author_of( $item_id ) ) {
				$view = 'author';
			}

			return apply_filters( 'learn-press/can-view-item', $view, $item_id, $this->get_id(), $course_id );
		}

		/**
		 * @param int $item_id
		 * @param int $course_id
		 *
		 * @return string
		 */
		public function get_item_url( $item_id = 0, $course_id = 0 ) {
			$course = learn_press_get_course( $course_id );
			$url    = $course->get_item_link( $item_id );

			return $url;
		}

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
		 * Return true if user can view a lesson
		 *
		 * @param int $lesson_id
		 * @param int $course_id
		 *
		 * @return bool
		 */
		public function can_view_lesson( $lesson_id, $course_id = 0 ) {
			_deprecated_function( __CLASS__ . '->' . __FUNCTION__, '3.1.0', __CLASS__ . '->can_view_item' );

			return $this->can_view_item( $lesson_id, $course_id );
			$view = false;
			// else, find the course of this lesson
			$course_id = $this->_get_course( $course_id );

			// Disable preview lesson when course status is pending
			if ( get_post_status( $course_id ) == 'pending' ) {
				$view = false;
			} else {
				$lesson = LP_Lesson::get_lesson( $lesson_id );

				if ( $course = learn_press_get_course( $course_id ) ) {
					if ( $this->get_course_access_level( $course_id ) >= 60 ) {
						// or user has enrolled course
						$view = 'enrolled';
					} elseif ( $lesson->is_preview() || $this->is_admin() || ( $this->is_instructor() && $course->get_instructor( 'id' ) == $this->get_id() ) ) {
						$view = 'preview';
					} elseif ( ! $course->is_required_enroll() ) {
						// if course is not required enroll so the lesson is previewable
						$view = 'no-required-enroll';
					}
				}
			}

			// @deprecated
			$view = apply_filters( 'learn_press_user_view_lesson', $view, $lesson_id, $this->get_id(), $course_id );

			return apply_filters( 'learn-press/can-view-lesson', $view, $lesson_id, $this->get_id(), $course_id );
		}

		/**
		 * Return true if user can view a quiz
		 *
		 * @param int $quiz_id
		 * @param int $course_id - optional The course contains quiz
		 *
		 * @return bool
		 */
		public function can_view_quiz( $quiz_id, $course_id = 0 ) {
			_deprecated_function( __CLASS__ . '->' . __FUNCTION__, '3.1.0', __CLASS__ . '->can_view_item' );

			return $this->can_view_item( $quiz_id, $course_id );
			$course    = false;
			$view      = false;
			$course_id = $this->_get_course( $course_id );

			// Disable preview course when course status is pending
			if ( get_post_status( $course_id ) == 'pending' ) {
				$view = false;
			} else {
				if ( $course_id ) {
					$course = learn_press_get_course( $course_id );
				}

				if ( $course ) {

					$quiz = LP_Quiz::get_quiz( $quiz_id );

					if ( $this->has_enrolled_course( $course_id ) || $this->has_finished_course( $course_id ) ) {
						$view = 'enrolled';
					} elseif ( $quiz->is_preview() || $this->is_admin() || ( $this->is_instructor() && $course->get_instructor( 'id' ) == $this->get_id() ) ) {
						$view = 'preview';
					} elseif ( ! $course->is_required_enroll() ) {
						$view = 'no-required-enroll';
					}
				}
			}

			// @deprecated
			$view = apply_filters( 'learn_press_user_view_quiz', $view, $quiz_id, $this->get_id(), $course_id );

			return apply_filters( 'learn-press/can-view-quiz', $view, $quiz_id, $this->get_id(), $course_id );
		}

		/**
		 * Check to see if user can retake a quiz
		 * - FALSE if user CAN NOT retake quiz
		 * - INT (number of remain) if user CAN retake quiz
		 *
		 * @param int $quiz_id
		 * @param int $course_id
		 *
		 * @return bool|int
		 */
		public function can_retake_quiz( $quiz_id, $course_id = 0 ) {
			$can    = false;
			$course = learn_press_get_course( $course_id );

			if ( $course && $course->has_item( $quiz_id ) ) {

				// Check if quiz is already exists
				if ( $quiz = learn_press_get_quiz( $quiz_id ) ) {
					$count = $quiz->get_retake_count();
					if ( $count > 0 ) {
						$count ++;
						// Number of taken
						$taken = $this->count_retaken_quiz( $quiz_id, $course_id );
						if ( $taken ) {
							$can = $count - $taken;
						} else {
							$can = $count;
						}

						$can = max( $can, 0 );

						$can = absint( $can );
					}
				}
			}

			return apply_filters( 'learn_press_user_can_retake_quiz', $can, $quiz_id, $this->get_id(), $course_id );
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

				if ( $access_level === LP_COURSE_ACCESS_LEVEL_60 ) {
					if ( $this->can_finish_course_passed( $course ) || $this->can_finish_course_not_passed( $course ) ) {
						$return = true;
					}
				}
			}

			return apply_filters( 'learn-press/can-finished-course', $return, $course, $this->get_id() );
		}

		public function can_finish_course_passed( $course ) {
			$return = false;

			if ( $course ) {
				$result = $this->evaluate_course_results( $course->get_id() );

				if ( $result >= $course->get_passing_condition() ) {
					$return = true;
				}
			}

			return $return;
		}

		public function can_finish_course_not_passed( $course ) {
			$return = false;

			if ( $course ) {
				$user_course = $this->get_course_data( $course->get_id() );
				$percent     = absint( $user_course->get_percent_completed_items() );
				$is_finish   = $percent === 100 ? true : false;

				if ( $is_finish ) {
					$return = true;
				}
			}

			return apply_filters( 'learn_press_finish_course_not_passed', $return );
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
		 * Check to see if user can retake a course, if yes return number of times
		 *
		 * @param      $course_id
		 * @param bool $force
		 *
		 * @return mixed
		 */
		public function can_retake_course( $course_id, $force = false ) {
			$can = false;
			if ( $course = learn_press_get_course( $course_id ) ) {
				$count = $course->get_retake_count();
				if ( $count > 0 ) {
					// Number of taken
					$taken = $this->count_retaken_course( $course_id, $force );
					if ( $taken ) {
						$can = $count - $taken;
					} else {
						$can = $count;
					}
				}
			}

			return apply_filters( 'learn_press_user_can_retake_course', $can, $course->get_id(), $this->get_id() );
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
			if ( $course = learn_press_get_course( $course_id ) ) {
				if ( ! $this->can_finish_course( $course_id ) ) {
					return false;
				} else {
					$user_course = $this->get_course_data( $course_id );
					$return      = $user_course->finish();
					if ( $return ) {
						do_action( 'learn-press/user-course-finished', $course_id, $this->get_id(), $return );
					}
					wp_cache_flush();
				}
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
				if ( false !== ( $cached_last_order = LP_Object_Cache::get( 'user-' . $this->get_id(), 'learn-press/user-last-order' ) ) ) {
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
		 * @param int  $course_id
		 * @param bool $force
		 *
		 * @return bool
		 */
		public function has_enrolled_course( $course_id, $force = false ) {
			$enrolled = $this->get_course_access_level( $course_id ) >= LP_COURSE_ACCESS_LEVEL_60;

			/**
			 * @since 3.0.0
			 */
			return apply_filters( 'learn-press/has-enrolled-course', $enrolled, $this->get_id(), $course_id );
		}

		/**
		 * Return true if you has finished a course
		 *
		 * @param int
		 * @param bool
		 *
		 * @return bool
		 */
		public function has_finished_course( $course_id, $force = false ) {
			if ( func_num_args() > 1 ) {
				_deprecated_argument( '$force', '3.0.0' );
			}

			$finished = $this->get_course_access_level( $course_id ) === LP_COURSE_ACCESS_LEVEL_70;

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

			return apply_filters( 'learn_press_user_has_passed_course', $results >= $course->get_passing_condition() ? $results : false, $course_id, $this );
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

			return apply_filters( 'learn-press/user-completed-quiz', $completed, $quiz_id, $course_id, $this->get_id() );
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

			return apply_filters( 'learn_press_user_count_retaken_quiz', $count, $quiz_id, $course_id, $this->get_id() );
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

		public function retake_course( $course_id ) {
			if ( ! $this->can_retake_course( $course_id ) ) {
				return false;
			}

			global $wpdb;
			$result = false;

			$check = apply_filters( 'learn-press/before-retake-course', true, $course_id, $this->get_id() );
			if ( ! $check ) {
				return false;
			}

			if ( $course_data = $this->get_course_data( $course_id ) ) {
				$course_data->delete_meta_data( array( 'grade', 'via', 'exceeded' ) );

				$course_data->set_status( 'enrolled' );
				$start_time = new LP_Datetime( current_time( 'mysql' ) );
				$course_data->set_start_time( $start_time->toSql() );
				$course_data->set_start_time_gmt( $start_time->toSql( false ) );
				$course_data->set_end_time( LP_Datetime::getSqlNullDate() );
				$course_data->set_end_time_gmt( LP_Datetime::getSqlNullDate() );

				if ( $result = $course_data->update() ) {
					$course_data->increase_retake_count();

					/*
					 * Should be deleted all user items when user retake course?
					 */
					$wpdb->query(
						$wpdb->prepare( "
						DELETE FROM {$wpdb->prefix}learnpress_user_items
						WHERE parent_id = %d
					", $result->user_item_id )
					);

					$course_data->calculate_course_results();
					do_action( 'learn-press/user/retaken-course', $result, $course_id, $this->get_id() );
				}
			}

			return $result;
		}

		/**
		 * Mark a lesson is completed for user
		 *
		 * @param     $lesson_id
		 * @param int $course_id
		 *
		 * @return bool|WP_Error
		 */
		public function complete_lesson( $lesson_id, $course_id = 0, $return_wp_error = true ) {
			global $wpdb;

			try {
				// @deprecated
				do_action( 'learn_press_before_user_complete_lesson', $lesson_id, $this );

				do_action( 'learn-press/before-complete-lesson', $lesson_id, $course_id, $this->get_id() );
				$course_id = $this->_get_course( $course_id );

				/*if ( $this->can_view_item( $lesson_id, $course_id ) == 'preview' ) {
					throw new Exception( __( 'You can not complete a preview lesson.', 'learnpress' ), LP_COMPLETE_ITEM_FAIL );
				}*/

				$course_data = $this->get_course_data( $course_id );

				if ( ! $course_data || ! $course_data->get_user_item_id() ) {
					throw new Exception( __( 'You have to enroll course to complete lesson.', 'learnpress', LP_COMPLETE_ITEM_FAIL ) );
				}

				$result = false;

				/**
				 * If user has stared a lesson, get user lesson information
				 */
				if ( $item = $course_data->get_item( $lesson_id ) ) {

					if ( $item->is_completed() ) {
						throw new Exception( __( 'You have already completed this lesson.', 'learnpress' ), LP_COMPLETE_ITEM_FAIL );
					}
					$time = new LP_Datetime();
					$item->set_end_time( $time->toSql(), true );
					$item->set_status( 'completed' );

					$course_data->save();

					$result = $this->evaluate_course_results( $this->get_id() );
				}

				// @deprecated
				do_action( 'learn_press_user_complete_lesson', $lesson_id, $result, $this->get_id() );

				do_action( 'learn-press/user-completed-lesson', $lesson_id, $course_id, $this->get_id() );
			}
			catch ( Exception $ex ) {
				$result = $return_wp_error ? new WP_Error( $ex->getCode(), $ex->getMessage() ) : false;
			}

			return $result;
		}

		/**
		 * Returns TRUE if user has already completed a lesson
		 *
		 * @param      $lesson_id
		 * @param null $course_id
		 * @param bool $force
		 *
		 * @return mixed|null
		 */
		public function has_completed_lesson( $lesson_id, $course_id = null, $force = false ) {
			$completed = $this->get_item_status( $lesson_id, $course_id ) == 'completed';

			return apply_filters( 'learn-press/user-has-completed-lesson', $completed, $lesson_id, $course_id, $this->get_id() );
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
		 * @since  3.2.6.8
		 * @author tungnx
		 */
		public function get_course_access_level( $course_id ) {
			$access_level = LP_Object_Cache::get( 'course-' . $course_id . '-' . $this->get_id(), 'learn-press/course-access-levels' );

			if ( false === $access_level ) {

				$course = learn_press_get_course( $course_id );

				if ( ! $course ) {
					$access_level = LP_COURSE_ACCESS_LEVEL_0;
				} elseif ( $course->is_required_enroll() ) {
					$access_level = LP_COURSE_ACCESS_LEVEL_35;
				} elseif ( $this->is_admin() ) {
					$access_level = LP_COURSE_ACCESS_LEVEL_30;
				} elseif ( $this->is_author_of( $course_id ) ) {
					$access_level = LP_COURSE_ACCESS_LEVEL_20;
				} else {
					$access_level = LP_COURSE_ACCESS_LEVEL_10;
				}

				// Default level
				$access_level       = apply_filters( 'learn-press/course-access-level-default', $access_level, $course_id, $this->get_id() );
				$status_course_user = $this->get_status_course_of_user( $course_id );

				if ( ( $status_course_user && isset( $status_course_user->course_status ) && isset( $status_course_user->order_status ) ) ) {
					switch ( $status_course_user->order_status ) {
						case 'lp-completed':
							$access_level = LP_COURSE_ACCESS_LEVEL_50;
							break;
						default:
							$access_level = LP_COURSE_ACCESS_LEVEL_40;
					}

					if ( $access_level === LP_COURSE_ACCESS_LEVEL_50 ) {
						switch ( $status_course_user->course_status ) {
							case 'enrolled':
								$access_level = LP_COURSE_ACCESS_LEVEL_60;
								break;
							case 'finished':
								$access_level = LP_COURSE_ACCESS_LEVEL_70;
								break;
						}
					}
				}

				LP_Object_Cache::set( 'course-' . $course_id . '-' . $this->get_id(), $access_level, 'learn-press/course-access-levels' );
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

			return apply_filters( 'learn-press/course-item-access-level', $access_level, $item_id, $course_id, $this->get_id() );
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
		/*
		public function set_course_access_level( $access_level, $course_id ) {
			if ( $access_level !== $this->get_course_access_level( $course_id ) ) {
				LP_Object_Cache::set( 'course-' . $course_id . '-' . $this->get_id(), $access_level, 'learn-press/course-access-levels' );
			}

			return $access_level;
		}*/

		/**
		 * Check if user have an access-level.
		 * Consider the passed access-level is max level user have.
		 *
		 * @param array  $access_level
		 * @param int    $course_id
		 * @param string $compare
		 *
		 * @return bool
		 * @since 3.1.0
		 */
		public function has_course_access_level( $access_level = array(), $course_id, $compare = '<=' ) {
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
		 * Evaluate results of a quiz for this user
		 *
		 * @param $quiz_id
		 * @param $progress
		 *
		 * @return mixed
		 * @deprecated
		 */
		public function evaluate_quiz_results( $quiz_id, $progress ) {
			_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '3.0.0' );

			return array();
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
			$purchased = apply_filters( 'learn_press_user_has_purchased_course', $purchased, $course_id, $this->get_id() );

			return $purchased;
		}

		public function is_locked_course( $course_id ) {
			$locked = false;// apply_filters( 'learn-press/course-is-locked-for-guest', ! is_user_logged_in() );

			if ( ! $locked && $course_item = $this->get_course_data( $course_id ) ) {
				$locked = 'locked' === learn_press_get_user_item_meta( $course_item->get_user_item_id(), '_status', true );
			}

			return apply_filters( 'learn-press/course-is-locked', $locked, $course_id, $this->get_id() );
		}

		/**
		 * Check if user is already ordered a course.
		 *
		 * @param int $course_id
		 *
		 * @return mixed|LP_Order
		 */
		public function has_ordered_course( $course_id ) {
			$return = apply_filters( 'learn-press/user-has-ordered-course', $this->get_course_order( $course_id ), $course_id, $this->get_id() );

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
			$return   = apply_filters( 'learn-press/course-order-status', $order_id ? get_post_status( $order_id ) : false, $course_id, $this->get_id() );

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
		 * @param int $course_id
		 * @param int $order_id
		 *
		 * @return mixed|WP_Error
		 * @throws Exception
		 */
		/**
		 * Enroll this user to a course.
		 *
		 * @param      $course_id
		 * @param      $order_id
		 * @param bool $force | Force create db record for preview quiz case
		 *
		 * @return bool|mixed|WP_Error
		 */
		public function enroll( $course_id, $order_id, $force = false ) {
			$return = false;
			try {
				global $wpdb;

				$course  = learn_press_get_course( $course_id );
				$user_id = $this->get_id();

				if ( $course->is_required_enroll() && ! $force ) {

					if ( ! $order = learn_press_get_order( $order_id ) ) {
						throw new Exception( __( 'Failed to enroll course.', 'learnpress' ), 10000 );
					}

					if ( ! $this->can_enroll_course( $course_id ) ) {
						throw new Exception( __( 'Failed to enroll course.', 'learnpress' ), 10001 );
					}

					if ( ! $this->get_id() ) {
						throw new Exception( __( 'Please login to enroll course.', 'learnpress' ), 10002 );
					}
				}

				$user_item_api = new LP_User_Item_CURD();
				$course_item   = $user_item_api->get_item_by(
					array(
						'item_id' => $course_id,
						'ref_id'  => $order_id,
						'user_id' => $user_id,
					)
				);

				if ( ! $course_item ) {
					$course_item = LP_User_Item::get_empty_item();
				} else {
					settype( $course_item, 'array' );
				}

				$date                          = new LP_Datetime();
				$course_item['user_id']        = $user_id;
				$course_item['item_id']        = $course_id;
				$course_item['item_type']      = learn_press_get_post_type( $course_id );
				$course_item['ref_id']         = $order_id;
				$course_item['ref_type']       = ( $order_id != 0 ) ? learn_press_get_post_type( $order_id ) : LP_ORDER_CPT;
				$course_item['start_time']     = $date->toSql();
				$course_item['start_time_gmt'] = $date->toSql( false );

				$user_course = new LP_User_Item_Course( $course_item );
				$user_course->set_status( 'enrolled' );
				$user_course->set_end_time_gmt( '0000-00-00 00:00:00' );
				if ( $user_course->update() ) {
					$return = true;
				}

				learn_press_remove_message( '', 'error' );
				$user_id = is_user_logged_in() ? $this->get_id() : 0;

				do_action( 'learn-press/user-enrolled-course', $course_id, $user_id, $user_course );

				// @deprecated
				do_action( 'learn_press_user_enrolled_course', $course_id, $user_id, $user_course );

				return $return;
			}
			catch ( Exception $ex ) {
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
			$query = $wpdb->prepare( "
			SELECT quiz_id
			FROM {$wpdb->prefix}learnpress_user_items uq
			INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta uqm ON uqm.learnpress_user_item_id = uq.user_item_id AND uqm.meta_key = %s AND uqm.meta_value LIKE %s",
				'questions', '%i:' . $wpdb->esc_like( $question_id . '' ) . ';%' );

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
				if ( $question = LP_Question::get_question( $question_id ) ) {
					$quiz_results = $this->get_quiz_results( $quiz_id );
					if ( ! empty( $quiz_results->question_answers ) ) {
						$question_answer = array_key_exists( $question_id, $quiz_results->question_answers ) ? $quiz_results->question_answers[ $question_id ] : null;
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
		public function get_purchased_courses( $args = array() ) {
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
		 *
		 * @return false|string
		 */
		public function get_profile_picture( $type = '', $size = 96 ) {
			return LP_Profile::instance( $this->get_id() )->get_profile_picture( $type, $size );
		}

		/**
		 * Check if user can access to a course.
		 *
		 * @param int $course_id
		 *
		 * @return mixed
		 */
		public function can_access_course( $course_id ) {
			$return = apply_filters( 'learn-press/user-can-access-course', $this->get_order_status( $course_id ) == 'lp-completed', $course_id, $this->get_id() );

			// Deprecated since 3.0.0
			$return = apply_filters( 'learn_press_user_can_access_course', $return, $course_id, $this->get_id() );

			return $return;
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
				$can = $this->has_course_status( $course_id, array( 'enrolled' ) ) && ! $this->has_started_quiz( $quiz_id, $course_id );
			} else {
				$can = ! $this->has_started_quiz( $quiz_id, $course_id );
			}

			return apply_filters( 'learn_press_user_can_do_quiz', $can, $quiz_id, $this->get_id(), $course_id );
		}

		public function evaluate_course_results( $course_id ) {
			LP_Debug::logTime( __FUNCTION__ );

			$user_course = $this->get_course_data( $course_id );

			$result = isset( $user_course ) ? $user_course->get_results( 'result' ) : 0;
			LP_Debug::logTime( __FUNCTION__ );

			return $result;
		}

		public function get_role() {
			return $this->is_admin() ? 'admin' : ( $this->is_instructor() ? 'instructor' : 'user' );
		}

		/**
		 * Get user course's grade.
		 * Possible values:
		 *        + passed        User has finished and passed course
		 *        + failed        User has finished but failed
		 *        + in-progress    User still is learning course
		 *        + false            All other cases, e.g: not enrolled
		 *
		 * @param $course_id
		 *
		 * @return string|bool
		 */
		public function get_course_grade( $course_id ) {
			$grade = false;

			if ( $course_data = $this->get_course_data( $course_id ) ) {
				$grade = $course_data->get_grade();
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

		/**
		 * Get status course's user
		 *
		 * @param int $course_id
		 *
		 * @return array|object|void|null
		 * @since  3.2.6.8
		 * @author tungnx
		 */
		public function get_status_course_of_user( $course_id = 0 ) {
			// Todo: add cache get_status_course_of_user
			global $wpdb;

			$user_id = get_current_user_id();

			if ( $user_id > 0 ) {
				$table_learnpress_user_items = $wpdb->prefix . 'learnpress_user_items';
				$table_post                  = $wpdb->posts;

				$query = $wpdb->prepare(
					"SELECT u_it.status as course_status, p.post_status as order_status
						FROM $table_learnpress_user_items AS u_it
						INNER JOIN $table_post AS p
						ON u_it.ref_id = p.ID
						WHERE u_it.user_id = %d
						AND u_it.item_id = %d
						AND u_it.item_type = %s
						AND u_it.ref_type = %s
						ORDER BY p.ID DESC
						LIMIT 1
						"
					, $user_id, $course_id, LP_COURSE_CPT, LP_ORDER_CPT );

				$result = $wpdb->get_row( $query );

				return $result;
			}

			return null;
		}

		/**
		 * Get the remaining time of a course for the user (timestamp).
		 *
		 * @param int $course_id
		 *
		 * @return bool|int|string
		 * @since  3.2.7.7
		 * @author hungkv
		 */
		public function get_course_remaining_time_timestamp( $course_id = 0 ) {
			$course = learn_press_get_course( $course_id );
			$remain = false;

			if ( $course && $course->get_id() ) {
				if ( $course_data = $this->get_course_data( $course_id, true ) ) {
					$remain = $course_data->is_exceeded();
				}
			}

			return $remain > 0 ? $remain : false;
		}
	}
}
