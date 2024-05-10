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

use LearnPress\Models\UserItems\UserCourseModel;

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
		 * @param int $course_id .
		 *
		 * @return LP_User_Item_Course|bool
		 * @version  3.1.3
		 * @editor tungnx
		 * @modify 4.1.3
		 */
		public function get_course_data( int $course_id = 0 ) {
			$lp_user_items_db   = LP_User_Items_DB::getInstance();
			$object_course_data = false;

			try {
				if ( ! $course_id ) {
					$course_id = get_the_ID();
				}

				/*if ( $this instanceof LP_User_Guest ) {
					throw new Exception( 'User is Guest' );
				}*/

				$filter            = new LP_User_Items_Filter();
				$filter->item_id   = $course_id;
				$filter->item_type = LP_COURSE_CPT;
				$filter->user_id   = $this->get_id();
				$last_user_course  = $lp_user_items_db->get_last_user_course( $filter );

				if ( $last_user_course ) {
					$object_course_data = new LP_User_Item_Course( $last_user_course );
				} else {
					$object_course_data = false;
					/**
					 * Todo: some themes still not check false, so still use below code.\
					 * @editor tungnx 4.1.6.9
					 */
					$object_course_data = new LP_User_Item_Course( $course_id );
				}
			} catch ( Throwable $e ) {
				$object_course_data = false;
			}

			return $object_course_data;
		}

		/**
		 * Get course attend of user not Guest.
		 * Replace get_course_data method.
		 *
		 * @param int $course_id
		 * @return false|UserCourseModel
		 * @since 4.2.5
		 * @version 1.0.1
		 */
		public function get_course_attend( int $course_id = 0 ) {
			if ( $this instanceof LP_User_Guest ) {
				return false;
			}

			$filter          = new LP_User_Items_Filter();
			$filter->item_id = $course_id;
			$filter->user_id = $this->get_id();
			return UserCourseModel::get_user_item_model_from_db( $filter );
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
		 * Check quiz can retake?
		 *
		 * @param [type] $quiz_id
		 * @param [type] $course_id
		 *
		 * @return boolean
		 */
		public function has_retake_quiz( $quiz_id, $course_id ): bool {
			$user_quiz = $this->get_item_data( $quiz_id, $course_id );
			$flag      = false;

			if ( $user_quiz ) {
				$retaken       = $user_quiz->get_retaken_count();
				$retake_config = get_post_meta( $quiz_id, '_lp_retake_count', true );

				if ( $retake_config == '-1' ) { // For no limit
					$flag = true;
				} elseif ( absint( $retaken ) < absint( $retake_config ) ) {
					$flag = true;
				}
			}

			return apply_filters( 'lp/quiz/can-retake', $flag );
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

			if ( ! $course_id ) {
				return false;
			}

			$course_data = $this->get_course_data( $course_id );
			if ( $course_data ) {
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
		 * @version 1.0.1
		 * @editor tungnx
		 * @modify 4.1.4.1
		 */
		public function get_item_grade( $item_id, $course_id = 0 ) {
			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			$grade = false;

			$course_data = $this->get_course_data( $course_id );

			if ( $course_data ) {
				$grade = $course_data->get_item_result( $item_id, 'grade' );
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
			$status = '';

			if ( ! $course_id ) {
				$course_id = get_the_ID();
				if ( ! $course_id ) {
					return $status;
				}
			}

			$item = $this->get_item( $item_id, $course_id, true );
			if ( $item instanceof LP_User_Item ) {
				$status = $item->get_status();
			}

			return apply_filters( 'learn-press/user-item-status', $status, $item_id, $this->get_id(), $course_id );
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
		 * @return LP_User_Item_Quiz|false
		 */
		public function get_quiz_data( $quiz_id, $course_id = 0 ) {
			$result        = false;
			$course_result = $this->get_course_data( $course_id );
			if ( $course_result ) {
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
		 * @deprecated 4.2.5
		 */
		public function hint( $question_id, $quiz_id, $course_id ) {
			_deprecated_function( __FUNCTION__, '4.2.5' );
			return false;
			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				return false;
			}

			if ( ! $course->has_item( $quiz_id ) ) {
				return false;
			}

			/**
			 * @var $quiz LP_Quiz
			 */
			$quiz = $course->get_item( $quiz_id );
			if ( ! $quiz instanceof LP_Course_Item_Quiz ) {
				return false;
			}

			if ( ! $quiz->has_question( $question_id ) ) {
				return false;
			}

			$quiz_data = $this->get_item_data( $quiz_id, $course_id );
			$remain    = $quiz_data->hint( $question_id );
			if ( false === $remain ) {
				return new WP_Error( 1001, __( 'You can not hint at the question.', 'learnpress' ) );
			}

			return $remain;
		}

		public function get_quiz_last_results( $quiz_id ) {
			$results = $this->get_course_info( $quiz_id );
			if ( $results ) {
				$results = reset( $results );
			}

			return apply_filters( 'learn_press_user_quiz_last_results', $results, $quiz_id, $this );
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
				throw new Exception( sprintf( __( 'The role %s for the user doesn\'t exist', 'learnpress' ), $role ) );
			}
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
		 * Check if user can finish course by getting current progress
		 * and compares with course passing condition.
		 *
		 * @param int $course_id
		 *
		 * @return bool
		 * @editor tungnx
		 * @modify 4.1.3
		 */
		public function can_finish_course( int $course_id ) {
			_deprecated_function( __FUNCTION__, '4.1.3', 'is_course_finished' );

			return true;
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

		/**
		 * Finish course
		 *
		 * @param int $course_id
		 *
		 * @return int|bool
		 */
		public function finish_course( int $course_id ) {
			$return = false;
			$course = learn_press_get_course( $course_id );

			if ( $course ) {
				$user_course = $this->get_course_data( $course_id );
				if ( ! $user_course ) {
					return $return;
				}

				$result = $user_course->calculate_course_results();

				// Save result for course
				LP_User_Items_Result_DB::instance()->update( $user_course->get_user_item_id(), wp_json_encode( $result ) );

				if ( $result['pass'] ) {
					$graduation = LP_COURSE_GRADUATION_PASSED;
				} else {
					$graduation = LP_COURSE_GRADUATION_FAILED;
				}

				$user_course->set_graduation( $graduation );
				//$user_course->save();
				$return = $user_course->complete( LP_COURSE_FINISHED );

				if ( $return ) {
					do_action( 'learn-press/user-course-finished', $course_id, $this->get_id(), $return );
				}
			}

			return apply_filters( 'learn-press/user-course-finished-data', $return, $course_id, $this->get_id() );
		}

		/**
		 * Check user instructor.
		 *
		 * @return bool
		 */
		public function is_instructor(): bool {

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
				throw new Exception( sprintf( __( 'The role %s for the user doesn\'t exist', 'learnpress' ), $role ) );
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
				throw new Exception( sprintf( __( 'The role %s for the user doesn\'t exist', 'learnpress' ), $role ) );
			}
		}

		/**
		 * Check user has passed course.
		 *
		 * @param $course_id
		 *
		 * @return mixed
		 */
		public function has_passed_course( $course_id ) {
			$user_course = $this->get_course_data( $course_id );

			return $user_course && $user_course->is_passed();
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
		 * @return bool
		 * @deprecated 4.1.6.9
		 */
		/*public function has_completed_quiz( $quiz_id, $course_id = 0 ): bool {
			return $this->get_item_status( $quiz_id, $course_id ) == 'completed';
		}*/

		/**
		 * Mark a lesson is completed for user
		 *
		 * @param int  $lesson_id
		 * @param int  $course_id
		 *
		 * @return bool|WP_Error
		 */
		public function complete_lesson( $lesson_id = 0, $course_id = 0 ) {
			$result = true;

			try {
				$course = learn_press_get_course( $course_id );
				if ( ! $course ) {
					throw new Exception( __( 'Invalid course', 'learnpress' ) );
				}

				$course_data = $this->get_course_data( $course_id );
				if ( ! $course_data ) {
					throw new Exception( __( 'You must enroll course!', 'learnpress' ) );
				}

				/**
				 * If user has stared a lesson, get user lesson information
				 */
				$item = $course_data->get_item( $lesson_id );
				if ( ! $item ) {
					throw new Exception( __( 'Invalid lesson', 'learnpress' ) );
				}

				if ( $item->is_completed() ) {
					throw new Exception( __( 'You have already completed this lesson.', 'learnpress' ) );
				}

				$item->set_graduation( 'passed' );
				$updated = $item->complete();

				do_action( 'learn-press/user-completed-lesson', $lesson_id, $course_id, $this->get_id() );
			} catch ( Throwable $e ) {
				$result = new WP_Error( 'error_lesson_complete', $e->getMessage() );
			}

			return $result;
		}

		/**
		 * Returns TRUE if user has already completed a lesson
		 *
		 * @param int  $lesson_id Lesson id.
		 * @param null $course_id Course id.
		 *
		 * @return bool
		 * @deprecated 4.1.6.9
		 */
		public function has_completed_lesson( $lesson_id = 0, $course_id = null ): bool {
			return 'completed' === $this->get_item_status( $lesson_id, $course_id );
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
		public function get_course_info( int $course_id, $field = null, $force = false ) {
			$user_data = $this->get_course_data( $course_id );
			if ( $user_data ) {
				return $user_data->get_results( $field );
			}

			return false;
		}

		/**
		 * @param $course_id
		 *
		 * @return int
		 * @deprecated 4.1.6.9
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
		 * @editor tungnx
		 * @modify 4.1.3
		 * @version 1.0.1
		 */
		public function get_course_status( int $course_id ): string {
			$status = '';

			try {
				$user_data = $this->get_course_data( $course_id );

				if ( $user_data ) {
					$status = $user_data->get_status();
				}
			} catch ( Throwable $e ) {
				if ( LP_Debug::is_debug() ) {
					error_log( $e->getMessage() );
				}
			}

			return apply_filters( 'learn-press/user-course-status', $status, $course_id, $this->get_id() );
		}

		/**
		 * Get order status of a course.
		 *
		 * @param int $course_id
		 *
		 * @return mixed
		 */
		public function get_order_status( $course_id ) {
			try {
				$order = $this->get_course_order( $course_id );

				if ( ! $order ) {
					throw new Exception( 'Order not exists' );
				}

				$order_status = apply_filters(
					'learn-press/course-order-status',
					get_post_status( $order->get_id() ),
					$course_id,
					$this->get_id()
				);
			} catch ( Throwable $e ) {
				$order_status = false;
			}

			return $order_status;
		}

		/**
		 * Check item completed.
		 *
		 * @param      $item
		 * @param int  $course_id
		 * @param bool $force
		 *
		 * @return mixed|void
		 * @version 3.0.1
		 * @since 3.0.0
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
				$return = 'completed' === $this->get_item_status( $item_id, $course_id );
			}

			return apply_filters( 'learn_press_user_has_completed_item', $return, $item );
		}

		/**
		 * Get the remaining time of a course for the user.
		 *
		 * @param int $course_id
		 *
		 * @return bool|int|string
		 * @deprecated 4.1.7.3
		 */
		public function get_course_remaining_time( $course_id ) {
			_deprecated_function( __FUNCTION__, '4.1.7.3' );
			/*$course = learn_press_get_course( $course_id );
			$remain = false;

			if ( $course && $course->get_id() ) {
				$course_data = $this->get_course_data( $course_id, true );
				if ( $course_data ) {
					$remain = $course_data->is_exceeded();
				}
			}

			return $remain > 0 ? learn_press_seconds_to_weeks( $remain ) : false;*/
		}

		/**
		 * Get the order that contains the course.
		 *
		 * @param int $course_id
		 *
		 * @return bool|LP_Order
		 * @editor tungnx
		 * @throws Exception
		 * @version 1.0.2
		 * @since 4.1.1
		 */
		public function get_course_order( int $course_id ) {
			$lp_order    = false;
			$lp_order_db = LP_Order_DB::getInstance();
			$lp_order_id = $lp_order_db->get_last_lp_order_id_of_user_course( $this->get_id(), $course_id );

			if ( $lp_order_id ) {
				$lp_order = new LP_Order( $lp_order_id );
			}

			return $lp_order;
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
		 * Icon is font awesome
		 *
		 * @param int $user_id
		 * @return array
		 * @deprecated 4.2.3
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
					$socials[ $k ] = sprintf( '<a href="%s">%s</a>', esc_url_raw( $v ), $icon );
				}
			}

			return apply_filters( 'learn-press/user-profile-socials', $socials, $this->get_id(), $this );
		}

		/**
		 * Get links socials of use on Profile page
		 * Icon is svg
		 *
		 * @param int $user_id
		 * @return array
		 * @since 4.2.3
		 * @version 1.0.0
		 */
		public function get_profile_social( int $user_id = 0 ): array {
			$socials    = array();
			$extra_info = learn_press_get_user_extra_profile_info( $user_id );

			if ( $extra_info ) {
				foreach ( $extra_info as $k => $v ) {
					if ( empty( $v ) ) {
						continue;
					}

					switch ( $k ) {
						case 'facebook':
//							$i = sprintf(
//								'<i class="lp-user-ico">%s</i>',
//								wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-facebook.svg' )
//							);
							$i ='<i class="lp-user-ico lp-icon-facebook"></i>';
							break;
						case 'twitter':
//							$i = sprintf(
//								'<i class="lp-user-ico">%s</i>',
//								wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-twitter.svg' )
//							);
							$i ='<i class="lp-user-ico lp-icon-twitter"></i>';
							break;
						case 'linkedin':
//							$i = sprintf(
//								'<i class="lp-user-ico">%s</i>',
//								wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-linkedin.svg' )
//							);
							$i ='<i class="lp-user-ico lp-icon-linkedin"></i>';
							break;
						case 'youtube':
//							$i = sprintf(
//								'<i class="lp-user-ico">%s</i>',
//								wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-youtube.svg' )
//							);
							$i ='<i class="lp-user-ico lp-icon-youtube-play"></i>';
							break;
						default:
							$i = sprintf( '<i class="lp-user-ico lp-icon-%s"></i>', $k );
					}

					$icon          = apply_filters(
						'learn-press/user-profile-social-icon',
						$i,
						$k,
						$this->get_id(),
						$this
					);
					$socials[ $k ] = sprintf( '<a href="%s">%s</a>', esc_url_raw( $v ), $icon );
				}
			}

			return apply_filters( 'learn-press/user-profile-socials', $socials, $this->get_id(), $this );
		}

		/**
		 * Check course of user has graduation is in-progress
		 *
		 * @param $course_id
		 * @return bool
		 * @throws Exception
		 */
		public function is_course_in_progress( $course_id ): bool {
			$user        = learn_press_get_user( $this->get_id() );
			$user_course = $user->get_course_data( $course_id );

			return  $user_course && LP_COURSE_GRADUATION_IN_PROGRESS === $user_course->get_graduation();
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

			if ( ! $course->is_no_required_enroll() ) {
				$can = $this->has_course_status( $course_id, array( 'enrolled' ) ) && ! $this->has_started_quiz( $quiz_id, $course_id );
			} else {
				$can = ! $this->has_started_quiz( $quiz_id, $course_id );
			}

			return apply_filters( 'learn_press_user_can_do_quiz', $can, $quiz_id, $this->get_id(), $course_id );
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

			$course_results = $course_data->get_result();

			if ( ! isset( $course_results['completed_items'] ) ) {
				return false;
			}

			return $course_results['completed_items'] >= $course->count_items();
		}

		/**
		 * Get role of user.
		 *
		 * @return string
		 * @since 3.0.0
		 * @version 1.0.1
		 */
		public function get_role() {
			return $this->get_data( 'role' );
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
		public function is_guest(): bool {
			return $this instanceof LP_User_Guest;
		}

		/**
		 * Check if user can edit a post.
		 *
		 * @param int $post_id
		 *
		 * @return bool
		 */
		public function can_edit( int $post_id ): bool {
			if ( $this->get_id() !== get_current_user_id() ) {
				return false;
			}

			return current_user_can( 'edit_post', $post_id );
		}

		/**
		 * Get email of user
		 *
		 * @return string
		 */
		public function get_email(): string {
			return $this->get_data( 'email', '' );
		}

		/**
		 * Return user_login of the user.
		 *
		 * @return string
		 */
		public function get_username(): string {
			return $this->get_data( 'user_login', '' );
		}

		/**
		 * Return user bio information.
		 *
		 * @return string
		 */
		public function get_description(): string {
			return wpautop( $this->get_data( 'description', '' ) );
		}

		/**
		 * Return user first name.
		 *
		 * @return string
		 */
		public function get_first_name(): string {
			return $this->get_data( 'first_name', '' );
		}

		/**
		 * Return user last name.
		 *
		 * @return string
		 */
		public function get_last_name(): string {
			return $this->get_data( 'last_name', '' );
		}

		/**
		 * Return user nickname.
		 *
		 * @return string
		 */
		public function get_nickname(): string {
			return $this->get_data( 'nickname', '' );
		}

		/**
		 * Return user display name.
		 *
		 * @return string
		 */
		public function get_display_name(): string {
			return $this->get_data( 'display_name', '' );
		}
	}
}
