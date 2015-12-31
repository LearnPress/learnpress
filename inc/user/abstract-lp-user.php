<?php

/**
 * Class LP_Abstract_User
 */
class LP_Abstract_User {
	/**
	 * @var array
	 */
	static protected $_users = array();

	/**
	 * @var int
	 */
	public $id = 0;

	/**
	 * @var WP_User object
	 */
	public $user = false;

	/**
	 * @var LP_Quiz object
	 */
	public $quiz = false;

	static protected $_order_items = array();

	protected $_quiz_history_id = null;

	/**
	 * Constructor
	 *
	 * @param int $the_user
	 *
	 * @throws Exception
	 */
	function __construct( $the_user = 0 ) {
		if ( $user = get_user_by( 'id', $the_user ) ) {
			$this->user = get_user_by( 'id', $the_user );
			$this->id   = $the_user;
		} else {
			throw new Exception( sprintf( __( 'The user with ID = %d is not exists', 'learn_press' ), $the_user ) );
		}
	}

	/**
	 * Magic function to setup user data
	 *
	 * @param $key
	 * @param $value
	 */
	function __set( $key, $value ) {
		$this->user->data->{$key} = $value;
	}

	/**
	 * Magic function to get user data
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	function __get( $key ) {
		$return = false;
		if ( !empty( $this->user->data->{$key} ) ) {
			$return = $this->user->data->{$key};
		} else {
			if ( isset( $this->{$key} ) ) {
				$return = $this->{$key};
			} elseif ( strpos( $key, '_lp_' ) === false ) {
				$key    = '_lp_' . $key;
				$return = get_user_meta( $this->id, $key, true );
				if ( !empty( $value ) ) {
					$this->$key = $return;
				}
			}
		}
		return $return;
	}

	/**
	 * Set current quiz for user
	 *
	 * @param $quiz
	 */
	function set_quiz( $quiz ) {
		$this->quiz = $quiz;
	}

	/**
	 * Get quiz field
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	function get_quiz_field( $field ) {
		if ( !empty( $this->quiz->{$field} ) ) {
			return $this->quiz->{$field};
		}
		return false;
	}

	private function _create_quiz_history( $quiz_id ) {

		if ( empty( $this->_quiz_history_id ) ) {
			global $wpdb;

			$timestamp = current_time( 'timestamp' );
			$wpdb->insert(
				$wpdb->learnpress_user_quizzes,
				array(
					'user_id' => $this->id,
					'quiz_id' => $quiz_id
				),
				array( '%d', '%d' )
			);

			$user_quiz_id = $wpdb->insert_id;

			$quiz           = LP_Quiz::get_quiz( $quiz_id );
			$quiz_questions = $quiz->get_questions();
			$user_quiz_data = apply_filters(
				'learn_press_user_quiz_data',
				array(
					'history_id'       => $user_quiz_id,
					'start'            => $timestamp,
					'end'              => '',
					'status'           => 'started',
					'results'          => '',
					'current_question' => '',
					'question_answers' => '',
					'questions'        => $quiz_questions ? array_keys( $quiz_questions ) : ''
				)
			);

			foreach ( $user_quiz_data as $key => $value ) {
				if ( $key == 'history_id' ) continue;
				learn_press_add_user_quiz_meta( $user_quiz_id, $key, $value );
			}

			do_action( 'learn_press_add_user_quiz_meta', $user_quiz_id, $this );
		} else {
			$user_quiz_data = $this->get_quiz_history( $quiz_id, $this->_quiz_history_id );
		}
		return $user_quiz_data;
	}

	/**
	 * Start quiz for the user
	 *
	 * @param null $quiz_id
	 *
	 * @throws Exception
	 * @return array|void
	 */
	function start_quiz( $quiz_id = null ) {
		if ( !$quiz_id ) $quiz_id = $this->get_quiz_field( 'id' );
		$user_id = $this->id;

		if ( !apply_filters( 'learn_press_before_user_start_quiz', true, $quiz_id, $user_id ) ) {
			return false;
		}

		if ( $this->get_quiz_status( $quiz_id ) != '' ) {
			throw new Exception( __( 'This user already has start quiz', 'learn_press' ) );
		}
		///
		return $this->_create_quiz_history( $quiz_id );
	}

	function get_quiz_time_remaining( $quiz_id ) {
		$remaining = false;
		if ( $progress = $this->get_quiz_progress( $quiz_id ) ) {
			$quiz      = LP_Quiz::get_quiz( $quiz_id );
			$remaining = $quiz->duration + $progress->start - current_time( 'timestamp' );
			if ( $remaining < 0 ) $remaining = 0;
		}
		return apply_filters( 'learn_press_user_quiz_time_remaining', $remaining, $this, $quiz_id );
	}

	function get_current_question_id( $quiz_id = 0 ) {
		$current               = false;
		$quiz_current_question = $this->quiz_current_question;
		if ( is_array( $quiz_current_question ) && !empty( $quiz_current_question[$quiz_id] ) ) {
			$current = $quiz_current_question[$quiz_id];
		} else {
			$quiz_questions = $this->quiz_questions;
			if ( is_array( $quiz_questions ) && !empty( $quiz_questions[$quiz_id] ) ) {
				$current = $quiz_questions[$quiz_id];
			}
		}
		return $current;
	}

	function get_current_question( $quiz_id, $what = '' ) {
		$current = $this->get_current_question_id( $quiz_id );
		if ( $what == 'id' ) {
			return $current;
		} else {
			$question = LP_Question_Factory::get_question($current );
			switch ( $what ) {
				case 'html':
					if ( $question ) {
						ob_start();
						$question->render();
						$current = ob_get_clean();
					}
			}
		}
		return $current;
	}

	function get_question_answers( $quiz_id, $question_id ) {
		$progress         = $this->get_quiz_progress( $quiz_id );
		$question_answers = null;
		if ( $progress ) {
			if ( !empty( $progress->question_answers[$question_id] ) ) {
				$question_answers = $progress->question_answers[$question_id];
			}
		}
		return $question_answers;
	}

	/**
	 * Get mark of a quiz for an user
	 *
	 * @param $quiz_id
	 *
	 * @return int
	 */
	function get_quiz_mark( $quiz_id ) {
		$quiz_questions = get_post_meta( $quiz_id, '_lpr_quiz_questions', true );
		$mark           = 0;
		if ( !$quiz_questions ) {
			foreach ( $quiz_questions as $question ) {
				$correct_answer = get_post_meta( $question, '_lpr_question_correct_answer', true );
				$question_mark  = get_post_meta( $question, '_lpr_question_mark', true );
				$student_answer = lpr_get_question_answer( $quiz_id, $question );

				if ( array_key_exists( $question, $student_answer ) ) {
					if ( $correct_answer == $student_answer ) {
						$mark += $question_mark;
					}
				}
			}
		}
		return $mark;
	}

	/**
	 * Finish a quiz for the user and save all data needed
	 *
	 * @param $quiz_id
	 */
	function finish_quiz( $quiz_id ) {
		$quiz = LP_Quiz::get_quiz( $quiz_id );
		if ( !$quiz ) {
			return;
		}
		$time = current_time( 'timestamp' );

		$progress = $this->get_quiz_progress( $quiz->id );

		if ( $time - $progress->start > $quiz->duration ) {
			$time = $progress->start - $quiz->duration;
		}

		learn_press_update_user_quiz_meta( $progress->history_id, 'end', $time );
		learn_press_update_user_quiz_meta( $progress->history_id, 'status', 'completed' );

		/*$course_id = learn_press_get_course_by_quiz( $quiz_id );
		if ( !learn_press_user_has_finished_course( $course_id ) ) {
			if ( learn_press_user_has_completed_all_parts( $course_id, $user_id ) ) {
				learn_press_finish_course( $course_id, $user_id );
			}
		}*/

		do_action( 'learn_press_user_finish_quiz', $quiz_id, $this );

	}

	/**
	 * Retake a quiz for the user
	 *
	 * @param $quiz_id
	 *
	 * @return array
	 * @throws Exception
	 */
	function retake_quiz( $quiz_id ) {
		$response = array();
		if ( !$this->can( 'retake-quiz', $quiz_id ) ) {
			$response['message'] = __( 'Sorry! You can not retake this quiz', 'learn_press' );
			$response['result']  = 'fail';
		} else {
			//lpr_reset_quiz_answer($quiz_id);
			//learn_press_reset_user_quiz( $user_id, $quiz_id );
			//add_user_meta( $user_id, '_lpr_quiz_taken', $quiz_id );
			$data = $this->_create_quiz_history( $quiz_id );

			$response['result']   = 'success';
			$response['redirect'] = get_the_permalink( $quiz_id );
			$response['data']     = $data;
		}
		do_action( 'learn_press_user_retake_quiz', $quiz_id, $response, $this );
		return $response;
	}

	/**
	 * Get quiz status for the user
	 *
	 * @param null $quiz_id
	 *
	 * @return mixed
	 */
	function get_quiz_status( $quiz_id = null ) {
		$progress = $this->get_quiz_progress( $quiz_id );
		if ( $progress ) {
			$quiz_status = $progress->status;
		} else {
			$quiz_status = '';
		}
		return apply_filters( 'learn_press_user_quiz_status', $quiz_status, $this, $quiz_id );
	}

	function get_quiz_info( $quiz_id, $field = null ) {
		static $quizzes = array();
		if ( empty( $quizzes[$quiz_id] ) ) {
			global $wpdb;
			$query     = $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->learnpress_user_quizzes}
				WHERE user_id = %d
				AND quiz_id = %d
			", $this->id, $quiz_id, '' );
			$user_quiz = (array) $wpdb->get_row( $query );
			if ( !empty( $user_quiz['user_quiz_id'] ) ) {
				$user_quiz['history'] = $this->get_quiz_history( $quiz_id );
			}
			$quizzes[$quiz_id] = $user_quiz;
		}
		if ( $field ) {
			if ( array_key_exists( $field, $quizzes[$quiz_id] ) ) {
				$info = $quizzes[$quiz_id][$field];
			} else {
				$info = '';
			}
			return apply_filters( 'learn_press_user_quiz_' . $field, $info, $this );
		} else {
			$info = $quizzes[$quiz_id];
			return apply_filters( 'learn_press_user_quiz_info', $info, $this );
		}

	}

	function get_quiz_history( $quiz_id, $history_id = null ) {
		static $history = array();
		if ( !array_key_exists( $quiz_id, $history ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->learnpress_user_quizzes} uq
				INNER JOIN {$wpdb->learnpress_user_quizmeta} uqm ON uqm.learnpress_user_quiz_id = uq.user_quiz_id
				WHERE uq.quiz_id = %d
					AND uq.user_id = %d
				ORDER BY uq.user_quiz_id DESC
			", $quiz_id, $this->id );
			if ( $results = $wpdb->get_results( $query ) ) {
				$history[$quiz_id] = array();
				foreach ( $results as $result ) {
					$id = $result->user_quiz_id;
					if ( empty( $history[$quiz_id][$id] ) ) {
						$history[$quiz_id][$id] = (object) array(
							'history_id' => $id
						);
					}
					$history[$quiz_id][$id]->{$result->meta_key} = maybe_unserialize( $result->meta_value );
				}
				foreach( $history[$quiz_id] as $id => $progress){
					$history[$quiz_id][$id]->results = $this->evaluate_quiz_results( $quiz_id, $progress );
				}
			}
		}
		if ( $history_id ) {
			return apply_filters( 'learn_press_user_quiz_history', isset( $history[$quiz_id][$history_id] ) ? $history[$quiz_id][$history_id] : false, $this, $quiz_id );
		}
		return apply_filters( 'learn_press_user_quiz_history', $history[$quiz_id], $this, $quiz_id );
	}

	function get_quiz_progress( $quiz_id ) {
		$history  = $this->get_quiz_history( $quiz_id );
		$progress = false;
		if ( $history ) {
			$progress = reset( $history );
		}
		return apply_filters( 'learn_press_user_quiz_progress', $progress, $this, $quiz_id );
	}

	function get_current_quiz_question( $quiz_id ) {
		if ( $progress = $this->get_quiz_progress( $quiz_id ) ) {
			$question_id = !empty( $progress->current_question ) ? $progress->current_question : false;
		} else {
			$question_id = false;
		}
		return apply_filters( 'learn_press_user_current_quiz_question', $question_id, $quiz_id, $this );
	}

	function save_quiz_question( $question_id, $answer ) {

	}

	/**
	 * Detect the type of user
	 *
	 * @param $type
	 *
	 * @return bool
	 */
	function is( $type ) {
		$name = preg_replace( '!LP_User(_?)!', '', get_class( $this ) );
		return strtolower( $name ) == strtolower( $type );
	}

	/**
	 * Get all a type of post for the user
	 *
	 * @param array $args - actually, it as the same with WP_Query args
	 *
	 * @return array
	 */
	function get_posts( $args = array() ) {
		settype( $args, 'array' );
		$args['author'] = $this->id;

		$args  = apply_filters( 'learn_press_get_user_posts', $args, $this );
		$query = new WP_Query( $args );
		$posts = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$p       = $query->next_post();
				$posts[] = $p;
			}
		}
		return $posts;
	}

	/**
	 * Get all quizzes of the user
	 *
	 * @param array $args - actually, it as the same with WP_Query args
	 * @param       bool
	 *
	 * @return array
	 */
	function get_quizzes( $args = array(), $force = false ) {
		static $quizzes = array();
		if ( !$quizzes || $force ) {
			settype( $args, 'array' );
			$args['post_type'] = LP()->quiz_post_type;
			$quizzes           = $this->get_posts( $args );
		}

		return apply_filters( 'learn_press_get_user_quizzes', $quizzes );
	}

	/**
	 * Get all quizzes of the user
	 *
	 * @param array $args - actually, it as the same with WP_Query args
	 * @param       bool
	 *
	 * @return array
	 */
	function get_lessons( $args = array(), $force = false ) {
		static $lessons = array();
		if ( !$lessons || $force ) {
			settype( $args, 'array' );
			$args['post_type'] = LP()->lesson_post_type;
			$lessons           = $this->get_posts( $args );
		}
		return apply_filters( 'learn_press_get_user_lessons', $lessons );
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
	function can( $role ) {
		$args = func_get_args();
		unset( $args[0] );
		$method   = 'can_' . preg_replace( '!-!', '_', $role );
		$callback = array( $this, $method );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		} else {
			throw new Exception( sprintf( __( 'The role %s for user doesn\'t exists', 'learn_press' ), $role ) );
		}
	}

	/**
	 * Return true if user can purchase a course
	 *
	 * @param $course_id
	 *
	 * @return bool
	 */
	function can_purchase_course( $course_id ) {
		$course       = learn_press_get_course( $course_id );
		$purchaseable = $course->is_purchaseable() && !$this->has_purchased_course( $course_id );
		return apply_filters( 'learn_press_user_can_purchase_course', $purchaseable, $this, $course_id );
	}

	/**
	 * Return true if user can enroll a course
	 *
	 * @param int
	 *
	 * @return bool
	 */
	function can_enroll_course( $course_id ) {
		$enrollable = false;
		if( $order_id = $this->has_purchased_course( $course_id ) ) {
			$order = LP_Order::instance( $order_id );
			$enrollable = !$this->has_enrolled_course( $course_id ) && $order && $order->has_status( 'completed' );
		}
		return apply_filters( 'learn_press_user_can_enroll_course', $enrollable, $this, $course_id );
	}

	/**
	 * Return true if user can view a lesson
	 *
	 * @param int $lesson_id
	 * @param int $course_id
	 *
	 * @return bool
	 */
	function can_view_lesson( $lesson_id, $course_id = null ) {
		$lesson = LP_Lesson::get_lesson( $lesson_id );
		$view = $lesson->is( 'previewable' ) || $this->get_item_order( $lesson_id );
		if( !$view && $course_id && ( $course = LP_Course::get_course( $course_id ) ) && !$course->is( 'required_enroll' ) ){
			$view = true;
		}
		return apply_filters( 'learn_press_user_view_lesson', $view, $lesson_id, $this, $course_id );
	}

	/**
	 * Return true if user can view a quiz
	 *
	 * @param int $quiz_id
	 * @param int $course_id - optional The course contains quiz
	 *
	 * @return bool
	 */
	function can_view_quiz( $quiz_id, $course_id = 0 ) {
		$course = false;
		if( $course_id ){
			$course = LP_Course::get_course( $course_id );
		}

		if ( $quiz = LP_Quiz::get_quiz( $quiz_id ) ) {
			if ( ! $course ) {
				$course = $quiz->get_course();
			}
		}
		if( $course ) {
			$this->get_course_order( $course->id );
		}
		$view = $this->get_item_order( $quiz_id );
		if( !$view && $course && !$course->is( 'required_enroll' ) ){
			$view = true;
		}
		return apply_filters( 'learn_press_user_view_quiz', $view, $quiz_id, $this, $course_id );
	}

	function can_retake_quiz( $quiz_id ) {
		$can = false;
		if ( $quiz = LP_Quiz::get_quiz( $quiz_id ) ) {
			$history = $this->get_quiz_history( $quiz_id );
			$taken   = $history ? sizeof( $history ) : 0;
			$can     = $taken ? ( $quiz->retake_count + 1 ) - $taken : $quiz->retake_count;
		}
		return apply_filters( 'learn_press_user_can_retake_quiz', $can, $quiz_id, $this );
	}

	function is_instructor(){
		$roles = !empty( $this->user->roles ) ? $this->user->roles : array();
		return in_array( LP()->teacher_role, $roles );
	}

	function is_admin(){
		$roles = !empty( $this->user->roles ) ? $this->user->roles : array();
		return in_array( 'administrator', $roles );
	}

	function has( $role ) {
		$args = func_get_args();
		unset( $args[0] );
		$method   = 'has_' . preg_replace( '!-!', '_', $role );
		$callback = array( $this, $method );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		} else {
			throw new Exception( sprintf( __( 'The role %s for user doesn\'t exists', 'learn_press' ), $role ) );
		}
	}

	/**
	 * Return true if user has already enrolled course
	 *
	 * @param $course_id
	 *
	 * @return bool
	 */
	function has_enrolled_course( $course_id ) {

		$info = $this->get_course_info( $course_id );

		return apply_filters( 'learn_press_user_has_enrolled_course', $info['status'] == 'enrolled', $this, $course_id );
	}

	/**
	 * Return true if you has finished a course
	 *
	 * @param int
	 *
	 * @return bool
	 */
	function has_finished_course( $course_id ) {
		return false;
	}

	/**
	 * Return tru if user has started a quiz
	 *
	 * @param $quiz_id
	 *
	 * @return mixed
	 */
	function has_started_quiz( $quiz_id ) {
		$quiz_info = $this->get_quiz_info( $quiz_id );
		return apply_filters( 'learn_press_user_started_quiz', $quiz_info && $quiz_info['status'] == 'started', $this );
	}

	/**
	 * Return true if user has completed a quiz
	 *
	 * @param $quiz_id
	 *
	 * @return mixed
	 */
	function has_completed_quiz( $quiz_id ) {
		$completed = false;
		if ( $progress = $this->get_quiz_progress( $quiz_id ) ) {
			$completed = $progress->status == 'completed';
		}
		return apply_filters( 'learn_press_user_has_completed_quiz', $completed, $quiz_id, $this );
	}


	/**
	 * Return current status of course for user
	 *
	 * @param $course_id
	 * @param $field
	 *
	 * @return mixed
	 */
	function get_course_info( $course_id, $field = null ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT uc.*
			FROM {$wpdb->learnpress_user_courses} uc
			WHERE uc.course_id = %d
			AND uc.user_id = %d
			ORDER BY user_course_id DESC
		", $course_id, $this->id );

		$info = array(
			'start'  => null,
			'end'    => null,
			'status' => null
		);
		if ( $result = $wpdb->get_row( $query ) ) {
			$info['start']  = $result->start_time;
			$info['end']    = $result->end_time;
			$info['status'] = $result->status;
		}
		if ( $field && array_key_exists( $field, $info ) ) {
			$info = $info[$field];
		}

		$this->_parse_item_order_of_course( $course_id );
		return apply_filters( 'learn_press_user_course_status', $info, $this, $course_id );
	}

	function get_course_status( $course_id ) {
		return $this->get_course_info( $course_id, 'status' );
	}

	function get_quiz_results( $quiz_id, $force = false ) {
		static $quiz_results = array();
		if ( empty( $quiz_results[$quiz_id] ) || $force ) {
			if ( $progress = $this->get_quiz_progress( $quiz_id ) ) {
				$quiz_results[$quiz_id] = $progress;
				$progress->results      = $this->evaluate_quiz_results( $quiz_id, $progress );

			} else {
				$quiz_results[$quiz_id] = false;
			}
		}
		return $quiz_results[$quiz_id];
	}

	function evaluate_quiz_results( $quiz_id, $progress ) {
		$quiz = LP_Quiz::get_quiz( $quiz_id );
		$results = array(
			'mark'            => 0,
			'correct'         => 0,
			'wrong'           => 0,
			'empty'           => 0,
			'mark_percent'    => 0,
			'correct_percent' => 0,
			'wrong_percent'   => 0,
			'empty_percent'   => 0,
			'quiz_time'       => $quiz->duration,
			'quiz_mark'       => $quiz->get_mark(),
			'user_time'       => 0,
			'questions'		=> array()
		);
		$questions = $quiz->questions;
		if( $questions ){
			$questions = array_keys( $questions );
		}
		if ( !empty( $questions ) ) {
			foreach ( $questions as $question_id ) {
				if ( !empty( $progress->question_answers[$question_id] ) ) {
					$question = LP_Question_Factory::get_question($question_id );
					$check    = $question->check( $progress->question_answers[$question_id] );

					if ( $check['correct'] ) {
						$results['correct'] ++;
					} else {
						$results['wrong'] ++;
					}
					$results['mark'] += $check['mark'];
				} else {
					$check = false;
					$results['empty'] ++;
				}
				$results['questions'][$question_id] = $check;
			}
		}
		$results['user_time'] = $progress->end - $progress->start;
		return $results;
	}

	/**
	 * Return true if user has already purchased course
	 *
	 * @param $course_id
	 *
	 * @return bool
	 */
	function has_purchased_course( $course_id ) {
		$order = $this->get_course_order( $course_id );
		return $order;
	}

	/**
	 * Get the order that contains the course
	 *
	 * @param int
	 * @param string type of order to return LP_Order|ID
	 *
	 * @return int
	 */
	function get_course_order( $course_id, $return = '' ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT order_id
			FROM {$wpdb->posts} o
			INNER JOIN {$wpdb->postmeta} om ON om.post_id = o.ID AND om.meta_key = %s AND om.meta_value = %d
			INNER JOIN {$wpdb->learnpress_order_items} oi ON o.ID = oi.order_ID
			INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.learnpress_order_item_id= oi.order_item_id AND oim.meta_key = %s AND oim.meta_value = %d
			ORDER BY order_id DESC
		", '_user_id', $this->id, '_course_id', $course_id );

		$order_id = $wpdb->get_var( $query );
		if ( $order_id && $return == 'object' ) {
			$order = LP_Order::instance( $order_id );
		} else {
			$order = $order_id;
		}

		$this->_parse_item_order_of_course( $course_id );

		return $order;
	}

	/**
	 * Get the order of an item in a course
	 * Uses this function to verify permission for this user
	 * with an item such as when user view a lesson or quiz
	 *
	 * @param int
	 * @param string type of order to return LP_Order|ID
	 *
	 * @return int
	 */
	function get_item_order( $item_id ) {
		if ( !empty( self::$_order_items[$item_id] ) ) {
			return self::$_order_items[$item_id];
		}
		return false;
	}

	/**
	 * Get the owns order of all items in a course to check permission for view
	 * This function is called when a function related with a course also is called
	 * Make sure parse the order of any items before check permission of it
	 *
	 * @param $course_id
	 *
	 * @return bool
	 */
	private function _parse_item_order_of_course( $course_id ) {
		static $courses_parsed = array();
		if ( !empty( $courses_parsed[$course_id] ) ) {
			return true;
		}
		global $wpdb;
		$items = LP_Course::get_course( $course_id )->get_curriculum_items( array( 'field' => 'ID' ) );

		if( $items ) {
			// How to make this simpler, LOL?
			$query = $wpdb->prepare( "
			SELECT order_id, si.item_id
			FROM {$wpdb->posts} o
			INNER JOIN {$wpdb->postmeta} om ON om.post_id = o.ID AND om.meta_key = %s AND om.meta_value = %d
			INNER JOIN {$wpdb->learnpress_order_items} oi ON o.ID = oi.order_ID
			INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.learnpress_order_item_id= oi.order_item_id AND oim.meta_key = %s
			INNER JOIN {$wpdb->posts} c ON c.ID = oim.meta_value
			INNER JOIN {$wpdb->learnpress_sections} s ON s.course_id = c.ID
			INNER JOIN {$wpdb->learnpress_section_items} si ON si.section_id = s.id WHERE si.item_id IN (" . join( ',', $items ) . ")
		", '_user_id', $this->id, '_course_id' );

			if ( $results = $wpdb->get_results( $query ) ) {
				foreach ( $results as $row ) {
					self::$_order_items[$row->item_id] = $row->order_id;
				}
			}
		}
		$courses_parsed[$course_id] = true;
	}

	/**
	 * Enroll this user to a course
	 *
	 * @param $course_id
	 *
	 * @return int|void
	 * @throws Exception
	 */
	function enroll( $course_id ) {
		if ( !$this->can( 'enroll-course', $course_id ) ) {
			learn_press_add_notice( __( 'Sorry! You can not enroll this course. Please try again or contact site admin' ), 'error' );
			return;
		}
		global $wpdb;
		$inserted = 0;

		do_action( 'learn_press_before_enroll_course', $this, $course_id );

		if ( $wpdb->insert(
			$wpdb->learnpress_user_courses,
			array(
				'user_id'    => $this->id,
				'course_id'  => $course_id,
				'start_time' => current_time( 'mysql' ),
				'status'     => 'enrolled',
				'end_time'   => null,
				'order_id'   => $this->get_course_order( $course_id )
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		)
		) {
			$inserted = $wpdb->insert_id;

			do_action( 'learn_press_user_enrolled_course', $this, $course_id, $inserted );

		} else {
			do_action( 'learn_press_user_enroll_course_failed', $this, $course_id, $inserted );
		}
		return $inserted;
	}

	function get_questions( $args = array() ){
		static $questions = array();
		if( !is_array( $args ) ){
			$args = array(
				'post_per_page' => 9999
			);
		}

		$args['post_type'] = LP()->question_post_type;
		$args['author']	= $this->id;

		$key = md5( serialize( $args ) );
		if( empty( $questions[$key] ) || ( !empty( $args['force'] ) && $args['force'] == true ) ){
			$questions[ $key ] = get_posts( $args );
		}

		return apply_filters( 'learn_press_user_questions', $questions[$key], $this );
	}

	function get_courses(){
		global $wpdb;
		echo $query = $wpdb->prepare("
			SELECT *
			FROM {$wpdb->posts}
			WHERE post_type = %s
			AND post_author = %d
		", LP()->course_post_type, $this->id);
		return $wpdb->get_results( $query );
	}

	function tab_courses_content() {
		learn_press_get_template( 'profile/tabs/courses.php', array( 'user' => $this ) );
	}

	function tab_quizzes_content() {
		learn_press_get_template( 'profile/tabs/quizzes.php', array( 'user' => $this ) );
	}
}