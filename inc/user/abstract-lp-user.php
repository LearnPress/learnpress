<?php

/**
 * Class LP_Abstract_User
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

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

	/**
	 * @var array
	 */
	static protected $_order_items = array();

	/**
	 * @var array
	 */
	static protected $_lessons = array();

	/**
	 * @var null
	 */
	protected $_quiz_history_id = null;

	/**
	 * @var int
	 */
	protected $_FOUND_ROWS = 0;

	/**
	 * Constructor
	 *
	 * @param int $the_user
	 *
	 * @throws Exception
	 */
	public function __construct( $the_user = 0 ) {
		if ( $user = get_user_by( 'id', $the_user ) ) {
			$this->user = $user;
			$this->id   = $the_user;
		} else {
			//throw new Exception( sprintf( __( 'The user with ID = %d is not exists', 'learnpress' ), $the_user ) );
		}
		if ( empty( self::$_lessons[$this->id] ) ) {
			self::$_lessons[$this->id] = array();
		}
	}

	/**
	 * Magic function to setup user data
	 *
	 * @param $key
	 * @param $value
	 */
	/*function __set( $key, $value ) {
		//$this->user->data->{$key} = $value;
	}*/

	/**
	 * Magic function to get user data
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function __get( $key ) {
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
	public function set_quiz( $quiz ) {
		$this->quiz = $quiz;
	}

	/**
	 * Get quiz field
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public function get_quiz_field( $field ) {
		if ( !empty( $this->quiz->{$field} ) ) {
			return $this->quiz->{$field};
		}
		return false;
	}

	private function _create_quiz_history( $quiz_id, $course_id = 0/* added 1.0.4 */ ) {

		if ( empty( $this->_quiz_history_id ) ) {
			global $wpdb;

			$timestamp = current_time( 'timestamp' );
			$wpdb->insert(
				$wpdb->learnpress_user_quizzes,
				apply_filters( 'learn_press_user_quiz_history_data', array(
					'user_id'   => $this->id,
					'quiz_id'   => $quiz_id,
					'course_id' => $course_id
				), $this->id, $quiz_id, $course_id ),
				array( '%d', '%d' )
			);
			$user_quiz_id = $wpdb->insert_id;

			/**
			 * Added 1.0.4
			 */
			@$wpdb->update(
				$wpdb->learnpress_user_quizzes,
				array(
					'course_id' => $course_id
				),
				array( 'user_quiz_id' => $user_quiz_id ),
				array( '%d' ),
				array( '%d' )
			);
			//

			$quiz              = new LP_Quiz( $quiz_id );
			$quiz_questions    = $quiz->get_questions();
			$quiz_question_ids = $quiz_questions ? array_keys( $quiz_questions ) : array();
			$quiz_question_ids = array_filter( $quiz_question_ids );
			$user_quiz_data    = apply_filters(
				'learn_press_user_quiz_data',
				array(
					'history_id'       => $user_quiz_id,
					'start'            => $timestamp,
					'end'              => '',
					'status'           => 'started',
					'results'          => '',
					'current_question' => !empty( $quiz_question_ids[0] ) ? $quiz_question_ids[0] : null,
					'question_answers' => '',
					'questions'        => $quiz_question_ids
				),
				$quiz_id, $this->id
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
	public function start_quiz( $quiz_id = null ) {
		if ( !$quiz_id ) $quiz_id = $this->get_quiz_field( 'id' );
		$user_id = $this->id;
		$return  = false;
		if ( !apply_filters( 'learn_press_before_user_start_quiz', true, $quiz_id, $user_id ) ) {
			return false;
		}

		if ( $this->get_quiz_status( $quiz_id ) != '' ) {
			//throw new Exception( __( 'This user already has start quiz', 'learnpress' ) );
		} else {
			///
			$return = $this->_create_quiz_history( $quiz_id );
		}
		do_action( 'learn_press_user_start_quiz', $quiz_id, $user_id );
		return $return;
	}

	public function get_quiz_time_remaining( $quiz_id ) {
		$remaining = false;
		if ( $progress = $this->get_quiz_progress( $quiz_id ) ) {
			$quiz      = LP_Quiz::get_quiz( $quiz_id );
			$remaining = $quiz->duration + $progress->start - current_time( 'timestamp' );
			if ( $remaining < 0 ) $remaining = 0;
		}
		return apply_filters( 'learn_press_user_quiz_time_remaining', $remaining, $this, $quiz_id );
	}

	public function get_current_question_id( $quiz_id = 0 ) {
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
		if ( !$current ) {
			$history = $this->get_quiz_history( $quiz_id );
			if ( $history ) {
				$keys    = array_keys( $history );
				$current = learn_press_get_user_quiz_meta( $keys[0], 'current_question' );
			}
		}
		return $current;
	}

	public function get_current_question( $quiz_id, $what = '' ) {
		$current = $this->get_current_question_id( $quiz_id );
		if ( $what == 'id' ) {
			return $current;
		} else {
			$question = LP_Question_Factory::get_question( $current );
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

	public function get_question_answers( $quiz_id, $question_id ) {
		$progress = $this->get_quiz_progress( $quiz_id );

		$question_answers = null;
		if ( $progress ) {
			$answers = (array) $progress->question_answers;
			if ( array_key_exists( $question_id, $answers ) ) {
				$question_answers = $answers[$question_id];
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
	public function get_quiz_mark( $quiz_id ) {
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
	public function finish_quiz( $quiz_id ) {
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

		do_action( 'learn_press_user_finish_quiz', $quiz_id, $this->id );

	}

	/**
	 * Retake a quiz for the user
	 *
	 * @param $quiz_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function retake_quiz( $quiz_id ) {
		$response = array();
		if ( !$this->can( 'retake-quiz', $quiz_id ) ) {
			$response['message'] = __( 'Sorry! You can not retake this quiz', 'learnpress' );
			$response['result']  = 'fail';
		} else {
			$data = $this->_create_quiz_history( $quiz_id );

			$response['result']   = 'success';
			$response['redirect'] = get_the_permalink( $quiz_id );
			$response['data']     = $data;
		}
		do_action( 'learn_press_user_retake_quiz', $response, $quiz_id, $this->id );
		return $response;
	}

	/**
	 * Get quiz status for the user
	 *
	 * @param null $quiz_id
	 *
	 * @return mixed
	 */
	public function get_quiz_status( $quiz_id = null ) {
		$progress = $this->get_quiz_progress( $quiz_id );
		if ( $progress ) {
			$quiz_status = $progress->status;
		} else {
			$quiz_status = '';
		}
		return apply_filters( 'learn_press_user_quiz_status', $quiz_status, $this, $quiz_id );
	}

	public function has_quiz_status( $quiz_id, $statuses ) {
		$status = $this->get_quiz_status( $quiz_id );
		settype( $statuses, 'array' );
		return in_array( $status, $statuses );
	}

	public function get_quiz_last_results( $quiz_id ) {
		$results = $this->get_course_info( $quiz_id );
		if ( $results ) {
			$results = reset( $results );
		}
		return apply_filters( 'learn_press_user_quiz_last_results', $results, $quiz_id, $this );
	}

	public function get_quiz_info( $quiz_id, $field = null ) {
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

	public function get_quiz_history( $quiz_id, $history_id = null ) {
		static $history = array();
		$key = $this->id . '_' . $quiz_id;
		if ( $quiz_id && !array_key_exists( $key, $history ) ) {
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
				$history[$key] = array();
				foreach ( $results as $result ) {
					$id = $result->user_quiz_id;
					if ( empty( $history[$key][$id] ) ) {
						$history[$key][$id] = (object) array(
							'history_id' => $id
						);
					}
					$history[$key][$id]->{$result->meta_key} = maybe_unserialize( $result->meta_value );
				}
				foreach ( $history[$key] as $id => $progress ) {
					$history[$key][$id]->results = $this->evaluate_quiz_results( $quiz_id, $progress );
				}
			}
		}
		if ( $history_id ) {
			return apply_filters( 'learn_press_user_quiz_history', isset( $history[$key][$history_id] ) ? $history[$key][$history_id] : false, $this, $quiz_id );
		}
		return apply_filters( 'learn_press_user_quiz_history', isset( $history[$key] ) ? $history[$key] : array(), $this, $quiz_id );
	}

	public function get_current_results( $quiz_id ) {
		$history = $this->get_quiz_history( $quiz_id );
		$current = false;
		if ( $history ) {
			$current = reset( $history );
		}
		return $current;
	}

	public function get_quiz_progress( $quiz_id ) {
		$history  = $this->get_quiz_history( $quiz_id );
		$progress = false;
		if ( $history ) {
			$progress = reset( $history );
		}
		return apply_filters( 'learn_press_user_quiz_progress', $progress, $this, $quiz_id );
	}

	public function get_current_quiz_question( $quiz_id ) {
		if ( $progress = $this->get_quiz_progress( $quiz_id ) ) {
			$question_id = !empty( $progress->current_question ) ? $progress->current_question : false;
		} else {
			$question_id = false;
		}
		return apply_filters( 'learn_press_user_current_quiz_question', $question_id, $quiz_id, $this );
	}

	public function get_finished_courses() {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT p.*, uc.start_time, uc.end_time, uc.order_id
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->prefix}learnpress_user_courses uc ON p.ID = uc.course_id
			WHERE uc.user_id = %d
			AND uc.status = %s
		", $this->id, 'finished' );
		return apply_filters( 'learn_press_user_finished_courses', $wpdb->get_results( $query ) );
	}

	public function save_quiz_question( $question_id, $answer ) {

	}

	/**
	 * Detect the type of user
	 *
	 * @param $type
	 *
	 * @return bool
	 */
	public function is( $type ) {
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
	public function get_posts( $args = array() ) {
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
	public function get_quizzes( &$args = array(), $force = false ) {
		static $quizzes = array();
		settype( $args, 'array' );

		$args              = wp_parse_args(
			$args,
			array(
				'limit'  => - 1,
				'paged'  => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
				'status' => array( 'started', 'finished' ),
				'total'  => 5
			)
		);
		$args['post_type'] = LP()->quiz_post_type;
		$key               = md5( serialize( $args ) );
		if ( empty( $quizzes[$key] ) || $force ) {
			global $wpdb;
			//$where = $wpdb->prepare( "\nWHERE uq.user_id = %d", $this->id );
			$limit = "\n";
			if ( $args['limit'] > 0 ) {
				if ( !$args['paged'] ) {
					$args['paged'] = 1;
				}
				$start = ( $args['paged'] - 1 ) * $args['limit'];
				$limit .= "LIMIT " . $start . ',' . $args['limit'];
			}
			$query = $wpdb->prepare( "
				SELECT SQL_CALC_FOUND_ROWS DISTINCT q.*
				FROM {$wpdb->posts} q
				INNER JOIN {$wpdb->prefix}learnpress_user_quizzes uq ON uq.quiz_id = q.ID
				WHERE uq.user_id = %d
			", $this->id );

			$query         = $query . $limit;
			$rows          = $wpdb->get_results( $query );
			$total         = $wpdb->get_var( "SELECT FOUND_ROWS()" );
			$quizzes[$key] = array(
				'rows'  => $rows,
				'total' => $total
			);
			$args['total'] = $total;
		} else {
			$args['total'] = $quizzes[$key]['total'];
		}

		return apply_filters( 'learn_press_get_user_quizzes', $quizzes[$key]['rows'], $args );
	}

	/**
	 * Get all quizzes of the user
	 *
	 * @param array $args - actually, it as the same with WP_Query args
	 * @param       bool
	 *
	 * @return array
	 */
	public function get_lessons( $args = array(), $force = false ) {
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
	public function can( $role ) {
		$args = func_get_args();
		unset( $args[0] );
		$method   = 'can_' . preg_replace( '!-!', '_', $role );
		$callback = array( $this, $method );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		} else {
			throw new Exception( sprintf( __( 'The role %s for user doesn\'t exists', 'learnpress' ), $role ) );
		}
	}

	/**
	 * Return true if user can purchase a course
	 *
	 * @param $course_id
	 *
	 * @return bool
	 */
	public function can_purchase_course( $course_id ) {
		$course      = learn_press_get_course( $course_id );
		$purchasable = $course->is_purchasable() && !$this->has_purchased_course( $course_id );
		return apply_filters( 'learn_press_user_can_purchase_course', $purchasable, $this, $course_id );
	}

	/**
	 * Return true if user can enroll a course
	 *
	 * @param int
	 *
	 * @return bool
	 */
	public function can_enroll_course( $course_id ) {
		$course = LP_Course::get_course( $course_id );
		// check if course is purchasable
		$enrollable = $course && $course->is_purchasable();/* $course->payment == 'no' &&*/
		//$course->required_enroll == 'yes';

		// if user can enroll, check order to ensure that user hasn't bought course
		if ( $enrollable && ( $order_id = $this->has_purchased_course( $course_id ) ) ) {
			$order      = LP_Order::instance( $order_id, true );
			$enrollable = !$this->has_enrolled_course( $course_id ) && ( $order && $order->has_status( 'completed' ) );
		}
		return apply_filters( 'learn_press_user_can_enroll_course', $enrollable, $this, $course_id );
	}

	public function can_view_item( $item_id ) {
		$return = false;
		switch ( get_post_type( $item_id ) ) {
			case LP()->quiz_post_type:
				$return = $this->can( 'view-quiz', $item_id );
				break;
			case LP()->lesson_post_type:
				$return = $this->can( 'view-lesson', $item_id );
				break;
		}
		return apply_filters( 'learn_press_user_can_view_item', $return, $item_id, $this->id );
	}

	/**
	 * Return true if user can view a lesson
	 *
	 * @param int $lesson_id
	 * @param int $course_id
	 *
	 * @return bool
	 */
	public function can_view_lesson( $lesson_id, $course_id = null ) {
		$lesson = LP_Lesson::get_lesson( $lesson_id );
		$view   = false;
		// first, check if the lesson is previewable
		if ( $lesson->is( 'previewable' ) ) {
			$view = 1;
		} else {
			// else, find the course of this lesson
			if ( !$course_id ) {
				$course_id = LP_Course::get_course_by_item( $lesson_id );
			}
			if ( $course = LP_Course::get_course( $course_id ) ) {
				// if course is not required enroll so the lesson is previewable
				if ( !$course->is( 'required_enroll' ) ) {
					$view = 2;
				} elseif ( $this->has( 'enrolled-course', $course_id ) || $this->has( 'finished-course', $course_id ) ) {
					// or user has enrolled course
					$view = 3;
				}
			}
		}

		return apply_filters( 'learn_press_user_view_lesson', $view, $lesson_id, $this->id, $course_id );
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
		$course = false;
		$view   = false;
		if ( !$course_id ) {
			$course_id = LP_Course::get_course_by_item( $quiz_id );
		}
		if ( $course_id ) {
			$course = LP_Course::get_course( $course_id );
		}

		if ( $quiz = LP_Quiz::get_quiz( $quiz_id ) ) {
			if ( !$course ) {
				$course = $quiz->get_course();
			}
		}

		if ( $course ) {
			$this->get_course_order( $course->id );
			if ( !$course->is( 'required_enroll' ) ) {
				$view = 1;
			} else {
				if ( $this->has( 'enrolled-course', $course->id ) || $this->has( 'finished-course', $course_id ) ) {
					$view = 2;
				}
			}
		}

		return apply_filters( 'learn_press_user_view_quiz', $view, $quiz_id, $this->id, $course_id );
	}

	public function can_retake_quiz( $quiz_id ) {
		$can = false;
		if ( $quiz = LP_Quiz::get_quiz( $quiz_id ) ) {
			$history = $this->get_quiz_history( $quiz_id );
			$taken   = $history ? sizeof( $history ) : 0;
			$can     = $taken ? ( $quiz->retake_count + 1 ) - $taken : $quiz->retake_count;
		}
		return apply_filters( 'learn_press_user_can_retake_quiz', $can, $quiz_id, $this );
	}

	public function can_finish_course( $course_id ) {
		$return = false;
		if ( $course = LP_Course::get_course( $course_id ) ) {
			$result = $course->evaluate_course_results() * 100;
			$return = ( $result >= $course->passing_condition ) && $this->has_course_status( $course_id, array( 'enrolled', 'started' ) );
		}
		return apply_filters( 'learn_press_user_can_finish_course', $return, $course_id, $this->id );
	}

	public function has_course_status( $course_id, $statuses ) {
		$status = $this->get_course_status( $course_id );
		if ( is_array( $statuses ) ) {
			return in_array( $status, $statuses );
		} elseif ( is_string( $statuses ) ) {
			return $statuses == $status;
		}
		return false;
	}

	public function finish_course( $course_id ) {
		global $wpdb;
		$result = array(
			'result'    => 'fail',
			'course_id' => $course_id
		);
		if ( $course = LP_Course::get_course( $course_id ) ) {
			if ( !$this->can( 'finish-course', $course_id ) ) {
				$result['message'] = __( 'Sorry, you can not finish this course. Please contact administrator or your instructor.', 'learnpress' );
			} else {
				$updated   = $wpdb->update(
					$wpdb->prefix . 'learnpress_user_courses',
					array(
						'end_time' => current_time( 'mysql' ),
						'status'   => 'finished'
					),
					array( 'user_id' => $this->id, 'course_id' => $course_id ),
					array( '%s', '%s' )
				);
				$null_time = '0000-00-00 00:00';
				$rec_id    = $wpdb->get_var(
					$wpdb->prepare( "
							SELECT user_course_id
							FROM {$wpdb->prefix}learnpress_user_courses
							WHERE user_id = %d
								AND course_id = %d
								AND start_time <> %s AND end_time <> %s
						", $this->id, $course_id, $null_time, $null_time )
				);
				if ( $rec_id ) {
					$result['rec_id'] = $rec_id;
					$result['result'] = 'success';
					do_action( 'learn_press_user_finish_course', $course_id, $this->id, $result );
				} else {

				}
			}
		}
		return apply_filters( 'learn_press_user_finish_course_data', $result, $course_id, $this->id );
	}

	public function is_instructor() {
		$roles = !empty( $this->user->roles ) ? $this->user->roles : array();
		return in_array( LP()->teacher_role, $roles );
	}

	public function is_admin() {
		$roles = !empty( $this->user->roles ) ? $this->user->roles : array();
		return in_array( 'administrator', $roles );
	}

	public function has( $role ) {
		$args = func_get_args();
		unset( $args[0] );
		$method   = 'has_' . preg_replace( '!-!', '_', $role );
		$callback = array( $this, $method );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		} else {
			throw new Exception( sprintf( __( 'The role %s for user doesn\'t exists', 'learnpress' ), $role ) );
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
			throw new Exception( sprintf( __( 'The role %s for user doesn\'t exists', 'learnpress' ), $role ) );
		}
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

		static $enrolled_courses = array();

		$key = sprintf( '%d-%d', $this->id, $course_id );

		if ( empty( $enrolled_courses[$key] ) || $force ) {
			//$info = $this->get_course_info( $course_id );
			global $wpdb;
			$query                  = $wpdb->prepare( "
				SELECT status
				FROM {$wpdb->prefix}learnpress_user_courses
				WHERE user_id = %d
				AND course_id = %d
				AND status <> %s
				LIMIT 0, 1
			", $this->id, $course_id, '' );
			$enrolled_courses[$key] = $wpdb->get_var( $query ) ? true : false;
		}
		return apply_filters( 'learn_press_user_has_enrolled_course', $enrolled_courses[$key], $this, $course_id );
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
		static $courses = array();
		if ( empty( $courses[$course_id] ) || $force ) {
			global $wpdb;
			$query               = $wpdb->prepare( "
				SELECT count(user_id)
				FROM {$wpdb->prefix}learnpress_user_courses uc
				INNER JOIN {$wpdb->posts} c ON c.ID = uc.course_id
				INNER JOIN {$wpdb->posts} o ON o.ID = uc.order_id
				INNER JOIN {$wpdb->postmeta} om ON om.post_id = o.ID AND om.meta_key = %s AND om.meta_value = %d
				WHERE uc.user_id = %d
				AND uc.course_id = %d
				AND uc.status = %s
				AND o.post_status = %s
			", '_user_id', $this->id, $this->id, $course_id, 'finished', 'lp-completed' );
			$courses[$course_id] = $wpdb->get_var( $query ) > 0 ? 'yes' : 'no';
		}
		return apply_filters( 'learn_press_user_has_finished_course', $courses[$course_id] == 'yes', $this, $course_id );
	}

	public function has_passed_course( $course_id ) {
		$course = LP_Course::get_course( $course_id );
		if ( $course ) {
			$results = $course->evaluate_course_results( $this->id );
		} else {
			$results = 0;
		}
		return apply_filters( 'learn_press_user_has_passed_course', $results * 100 >= $course->passing_condition ? $results : false, $course_id, $this );
	}

	/**
	 * Return tru if user has started a quiz
	 *
	 * @param $quiz_id
	 *
	 * @return mixed
	 */
	public function has_started_quiz( $quiz_id ) {
		$quiz_info = $this->get_quiz_results( $quiz_id );
		return apply_filters( 'learn_press_user_started_quiz', !empty( $quiz_info ) && ( $quiz_info->status == 'started' ), $this );
	}

	/**
	 * Return true if user has completed a quiz
	 *
	 * @param $quiz_id
	 *
	 * @return mixed
	 */
	public function has_completed_quiz( $quiz_id ) {
		$completed = false;
		if ( $progress = $this->get_quiz_progress( $quiz_id ) ) {
			$completed = $progress->status == 'completed';
		}
		return apply_filters( 'learn_press_user_has_completed_quiz', $completed, $quiz_id, $this );
	}

	public function current_quiz_status( $quiz_id, $force = false ) {
		global $wpdb;
		static $quizzes = array();
		if ( $quiz_id && ( !array_key_exists( $quiz_id, $quizzes ) || $force ) ) {
			$query             = $wpdb->prepare( "
				SELECT uqm.meta_value as `status`
				FROM {$wpdb->prefix}learnpress_user_quizmeta uqm
				INNER JOIN {$wpdb->prefix}learnpress_user_quizzes uq ON uq.user_quiz_id = uqm.learnpress_user_quiz_id AND uqm.meta_key = %s
				WHERE uq.user_id = %d
				AND uq.quiz_id = %d
				ORDER BY user_quiz_id DESC
				LIMIT 1
			", 'status', $this->id, $quiz_id );
			$quizzes[$quiz_id] = $wpdb->get_var( $query );
		}
		return $quizzes[$quiz_id];
	}

	public function is_exists_lesson( $lesson_id, $course_id ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT user_lesson_id
			FROM {$wpdb->prefix}learnpress_user_lessons
			WHERE user_id = %d
			AND course_id = %d
			AND lesson_id = %d
		", $this->id, $course_id, $lesson_id );
		return $wpdb->get_var( $query );
	}

	public function complete_lesson( $lesson_id, $course_id = 0 ) {
		global $wpdb;
		do_action( 'learn_press_before_user_complete_lesson', $lesson_id, $this );
		if ( !$course_id ) {
			$course_id = LP_Course::get_course_by_item( $lesson_id );
		}
		if ( $this->is_exists_lesson( $lesson_id, $course_id ) ) {
			$updated = $wpdb->update(
				$wpdb->prefix . 'learnpress_user_lessons',
				array(
					'end_time' => current_time( 'mysql' ),
					'status'   => 'completed'
				),
				array(
					'user_id'   => $this->id,
					'lesson_id' => $lesson_id,
					'course_id' => $course_id
				),
				array( '%s', '%s' ),
				array( '%d', '%d', '%d' )
			);
		} else {
			$updated = $wpdb->insert(
				$wpdb->prefix . 'learnpress_user_lessons',
				array(
					'user_id'    => $this->id,
					'lesson_id'  => $lesson_id,
					'course_id'  => $course_id,
					'start_time' => current_time( 'mysql' ),
					'end_time'   => current_time( 'mysql' ),
					'status'     => 'completed'
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s' )
			);
		}
		$result = false;
		if ( $updated ) {
			if ( $course = LP_Course::get_course( $course_id ) ) {
				$result = $course->evaluate_course_results( $this->id );
			}
		} else {
			$result = new WP_Error( null, $wpdb->last_error );
		}

		do_action( 'learn_press_user_complete_lesson', $lesson_id, $result, $this->id );
		return $result;
	}

	public function has_completed_lesson( $lesson_id, $course_id = null ) {
		if ( !$course_id ) {
			$course    = LP()->course;
			$course_id = $course ? $course->id : 0;
		}
		$lessons = !empty( self::$_lessons[$this->id] ) ? self::$_lessons[$this->id] : array();
		if ( empty( $lessons ) || ( $lessons && !array_key_exists( $lesson_id, $lessons ) ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "
				SELECT lesson_id, status FROM {$wpdb->prefix}learnpress_user_lessons
				WHERE user_id = %d
			", $this->id );
			$query .= $course_id ? $wpdb->prepare( " AND course_id = %d", $course_id ) : '';
			if ( $rows = $wpdb->get_results( $query ) ) {
				foreach ( $rows as $r ) {
					$lessons[$r->lesson_id] = $r->status;
				}
			}
			self::$_lessons[$this->id] = $lessons;
		}
		$completed = !empty( $lessons[$lesson_id] ) && $lessons[$lesson_id] == 'completed';
		return apply_filters( 'learn_press_user_has_completed_lesson', $completed, $lesson_id, $this );
	}

	/**
	 * Return current status of course for user
	 *
	 * @param $course_id
	 * @param $field
	 *
	 * @return mixed
	 */
	public function get_course_info( $course_id, $field = null ) {
		if ( !$course_id ) {
			return false;
		}
		global $wpdb;
		static $user_course_info = array();
		if ( empty( $user_course_info[$this->id] ) ) {
			$user_course_info[$this->id] = array();
		}
		if ( $course_id && empty( $user_course_info[$this->id][$course_id] ) ) {
			$query = $wpdb->prepare( "
				SELECT uc.*
				FROM {$wpdb->learnpress_user_courses} uc
				INNER JOIN {$wpdb->posts} o ON o.ID = uc.order_id
				WHERE uc.course_id = %d
				AND uc.user_id = %d AND o.post_status = %s
				ORDER BY user_course_id DESC
			", $course_id, $this->id, 'lp-completed' );

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
			$user_course_info[$this->id][$course_id] = $info;
		}
		if ( $field && array_key_exists( $field, $user_course_info[$this->id][$course_id] ) ) {
			$info = $user_course_info[$this->id][$course_id][$field];
		} else {
			$info = $user_course_info[$this->id][$course_id];
		}

		$this->_parse_item_order_of_course( $course_id );
		return apply_filters( 'learn_press_user_course_info', $info, $this, $course_id );
	}

	public function get_course_status( $course_id ) {
		return apply_filters( 'learn_press_user_course_status', $this->get_course_info( $course_id, 'status' ), $this->id );
	}

	public function get_quiz_results( $quiz_id, $force = false ) {
		static $quiz_results = array();
		$key = $this->id . '_' . $quiz_id;
		if ( empty( $quiz_results[$key] ) || $force ) {
			if ( $progress = $this->get_quiz_progress( $quiz_id ) ) {
				$quiz_results[$key] = $progress;
				$progress->results  = $this->evaluate_quiz_results( $quiz_id, $progress );

			} else {
				$quiz_results[$key] = false;
			}
		}
		return $quiz_results[$key];
	}

	public function evaluate_quiz_results( $quiz_id, $progress ) {
		$quiz      = LP_Quiz::get_quiz( $quiz_id );
		$results   = array(
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
			'questions'       => array()
		);
		$questions = $quiz->questions;
		if ( $questions ) {
			$questions = array_keys( $questions );
		}
		if ( !empty( $questions ) ) {
			$question_answers = !empty( $progress->question_answers ) ? $progress->question_answers : array();
			foreach ( $questions as $question_id ) {
				if ( is_array( $question_answers ) && array_key_exists( $question_id, $question_answers ) && !is_null( $question_answers[$question_id] ) ) {
					$question = LP_Question_Factory::get_question( $question_id );
					$check    = $question->check( $question_answers[$question_id] );

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
		if ( $total_questions = sizeof( $questions ) ) {
			$results['correct_percent']       = $results['correct'] / $total_questions * 100;
			$results['wrong_percent']         = $results['wrong'] / $total_questions * 100;
			$results['empty_percent_percent'] = $results['empty'] / $total_questions * 100;
		}
		$results['user_time'] = $this->_calculate_quiz_time( $quiz, $progress );
		if ( $results['quiz_mark'] ) {
			$results['mark_percent'] = $results['mark'] / $results['quiz_mark'] * 100;
		}
		return apply_filters( 'learn_press_evaluate_quiz_results', $results, $quiz_id, $this->id );
	}

	private function _calculate_quiz_time( $quiz, $progress ) {
		$start = $progress->start;
		$end   = $progress->end;
		if ( $end < $start ) {
			$end = $start + $quiz->duration;
			learn_press_update_user_quiz_meta( $progress->history_id, 'end', $end );
		}
		return $end - $start;
	}

	/**
	 * Return true if user has already purchased course
	 *
	 * @param $course_id
	 *
	 * @return bool
	 */
	public function has_purchased_course( $course_id ) {
		$purchased = false;
		$order     = $this->get_course_order( $course_id, 'object' );
		if ( $order && $order->has_status( array( 'processing', 'completed' ) ) ) {
			$purchased = $order->id;
		}
		return apply_filters( 'learn_press_user_has_purchased_course', $purchased, $course_id, $this->id, $order ? $order->id : 0 );
	}

	public function has_completed_item( $item ) {
		$return  = false;
		$item_id = 0;
		if ( is_numeric( $item ) ) {
			$item_id = absint( $item );
		} else {
			settype( $item, 'array' );
			if ( !empty( $item['ID'] ) ) {
				$item_id = absint( $item['ID'] );
			}
		}
		if ( $item_id ) {
			if ( empty( $item['item_type'] ) ) {
				$type = get_post_type( $item_id );
			} else {
				$type = $item['item_type'];
			}
			if ( $type == 'lp_lesson' ) {
				$return = $this->has_completed_lesson( $item_id );
			} elseif ( $type == 'lp_quiz' ) {
				$return = $this->has_completed_quiz( $item_id );
			}
		}
		return apply_filters( 'learn_press_user_has_completed_item', $return, $item );
	}

	public function get_course_remaining_time( $course_id ) {
		$course = learn_press_get_course( $course_id );
		$remain = false;
		if ( $course->id ) {
			$course_duration = $course->duration * 7 * 24 * 3600;
			$course_info     = $this->get_course_info( $course_id );
			if ( $course_info ) {
				$now        = time();
				$start_time = intval( strtotime( $course_info['start'] ) );

				if ( $start_time + $course_duration > $now ) {
					$remain = $start_time + $course_duration - $now;
					$remain = learn_press_seconds_to_weeks( $remain );
				}
			}
		}
		return $remain;
	}

	/**
	 * Get the order that contains the course
	 *
	 * @param int
	 * @param string type of order to return LP_Order|ID
	 *
	 * @return int
	 */
	public function get_course_order( $course_id, $return = '' ) {
		global $wpdb;
		static $orders = array();

		$key = sprintf( '%d_%d', $this->id, $course_id );

		if ( empty( $orders[$key] ) ) {
			$query = $wpdb->prepare( "
				SELECT order_id
				FROM {$wpdb->posts} o
				INNER JOIN {$wpdb->postmeta} om ON om.post_id = o.ID AND om.meta_key = %s AND om.meta_value = %d
				INNER JOIN {$wpdb->learnpress_order_items} oi ON o.ID = oi.order_ID
				INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.learnpress_order_item_id= oi.order_item_id AND oim.meta_key = %s AND oim.meta_value = %d
				WHERE o.post_status IN ('lp-processing', 'lp-pending', 'lp-completed')
				ORDER BY order_id DESC
			", '_user_id', $this->id, '_course_id', $course_id );

			$order_id = $wpdb->get_var( $query );
			$this->_parse_item_order_of_course( $course_id );
			$orders[$key] = $order_id;
		} else {
			$order_id = $orders[$key];
		}
		if ( $order_id && $return == 'object' ) {
			$order = LP_Order::instance( $order_id );
		} else {
			$order = $order_id;
		}
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
	public function get_item_order( $item_id ) {
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
		if ( !empty( $courses_parsed[$this->id . '-' . $course_id] ) ) {
			return true;
		}
		global $wpdb;
		$course = LP_Course::get_course( $course_id );
		$items  = null;
		if ( $course ) {
			$items = $course->get_curriculum_items( array( 'field' => 'ID' ) );
		}
		if ( $items ) {
			// How to make this simpler, LOL?
			$query = $wpdb->prepare( "
				SELECT order_id, si.item_id
				FROM {$wpdb->posts} o
				INNER JOIN {$wpdb->postmeta} om ON om.post_id = o.ID AND om.meta_key = %s AND om.meta_value = %d
				INNER JOIN {$wpdb->learnpress_order_items} oi ON o.ID = oi.order_ID
				INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.learnpress_order_item_id= oi.order_item_id AND oim.meta_key = %s
				INNER JOIN {$wpdb->posts} c ON c.ID = oim.meta_value
				INNER JOIN {$wpdb->learnpress_sections} s ON s.section_course_id = c.ID
				INNER JOIN {$wpdb->learnpress_section_items} si ON si.section_id = s.section_id WHERE si.item_id IN (" . join( ',', $items ) . ")
				AND o.post_status = %s
			", '_user_id', $this->id, '_course_id', 'lp-completed' );

			if ( $results = $wpdb->get_results( $query ) ) {
				foreach ( $results as $row ) {
					self::$_order_items[$row->item_id] = $row->order_id;
				}
			}
		}
		$courses_parsed[$this->id . '-' . $course_id] = true;
	}

	/**
	 * Enroll this user to a course
	 *
	 * @param $course_id
	 *
	 * @return int|void
	 * @throws Exception
	 */
	public function enroll( $course_id ) {
		if ( !$this->can( 'enroll-course', $course_id ) ) {
			learn_press_add_notice( __( 'Sorry! You can not enroll this course. Please try again or contact site admin' ), 'error' );
			return;
		}
		global $wpdb;
		$inserted = 0;

		$check = apply_filters( 'learn_press_before_enroll_course', true, $this, $course_id );
		if ( !$check ) {
			return false;
		}
		if ( $wpdb->insert(
			$wpdb->learnpress_user_courses,
			array(
				'user_id'    => $this->id,
				'course_id'  => $course_id,
				'start_time' => current_time( 'mysql' ),
				'status'     => 'enrolled',
				'end_time'   => '0000-00-00 00:00:00',
				'order_id'   => $this->get_course_order( $course_id )
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		)
		) {
			$inserted = $wpdb->insert_id;

			do_action( 'learn_press_user_enrolled_course', $this, $course_id, $inserted );

		} else {
			learn_press_debug( $wpdb );
			do_action( 'learn_press_user_enroll_course_failed', $this, $course_id, $inserted );
		}
		return $inserted;
	}

	public function get_questions( $args = array() ) {
		static $questions = array();
		if ( !is_array( $args ) ) {
			$args = array(
				'post_per_page' => 9999
			);
		}

		$args['post_type'] = LP()->question_post_type;
		$args['author']    = $this->id;

		$key = md5( serialize( $args ) );
		if ( empty( $questions[$key] ) || ( !empty( $args['force'] ) && $args['force'] == true ) ) {
			$questions[$key] = get_posts( $args );
		}

		return apply_filters( 'learn_press_user_questions', $questions[$key], $this );
	}

	public function get_courses( $args = array() ) {
		global $wpdb;
		static $courses = array();
		$args = wp_parse_args(
			$args,
			array(
				'status'  => '',
				'limit'   => - 1,
				'paged'   => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
				'orderby' => 'post_title',
				'order'   => 'ASC',
				'user_id' => $this->id
			)
		);
		ksort( $args );
		$key = md5( serialize( $args ) );
		if ( empty( $courses[$key] ) ) {
			$where = $args['status'] ? $wpdb->prepare( "AND uc.status = %s", $args['status'] ) : '';
			$limit = "\n";
			if ( $args['limit'] > 0 ) {
				if ( !$args['paged'] ) {
					$args['paged'] = 1;
				}
				$start = ( $args['paged'] - 1 ) * $args['limit'];
				$limit .= "LIMIT " . $start . ',' . $args['limit'];
			}
			$order = "\nORDER BY " . ( $args['orderby'] ? $args['orderby'] : 'post_title' ) . ' ' . $args['order'];
			$query = $wpdb->prepare( "
				SELECT SQL_CALC_FOUND_ROWS * FROM(
					SELECT c.*, uc.status as course_status
					FROM {$wpdb->posts} c
					LEFT JOIN {$wpdb->prefix}learnpress_user_courses uc ON c.ID = uc.course_id AND uc.user_id = %d
					WHERE post_type = %s
					AND post_author = %d
					UNION
					SELECT c.*, uc.status as course_status
					FROM {$wpdb->posts} c
					INNER JOIN {$wpdb->learnpress_user_courses} uc ON c.ID = uc.course_id
					WHERE uc.user_id = %d
						AND c.post_type = %s
						AND c.post_status = %s
				) a GROUP BY a.ID
			", $args['user_id'],
				LP()->course_post_type, $this->id,
				$args['user_id'], LP()->course_post_type, 'publish'
			);
			$query .= $where . $order . $limit;

			$data          = array(
				'rows' => $wpdb->get_results( $query )
			);
			$data['count'] = $wpdb->get_var( "SELECT FOUND_ROWS();" );

			$courses[$key] = $data;
		}
		$this->_FOUND_ROWS = $courses[$key]['count'];
		return $courses[$key]['rows'];
	}

	public function get_orders() {
		global $wpdb;
		$query  = $wpdb->prepare( "
			SELECT o.*
			FROM {$wpdb->posts} o
			INNER JOIN {$wpdb->postmeta} om ON om.post_id = o.ID AND om.meta_key = %s
			INNER JOIN {$wpdb->users} u ON u.ID = om.meta_value
			WHERE o.post_type = %s
			AND u.ID = %d
			AND o.post_status <> %s
			ORDER BY `post_date` DESC
		", '_user_id', 'lp_order', $this->id, 'trash' );
		$orders = $wpdb->get_results( $query );
		return apply_filters( 'learn_press_user_orders', $orders, $this->id );
	}

	public function get_quiz_by_question( $question_id ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT quiz_id
			FROM {$wpdb->prefix}learnpress_user_quizzes uq
			INNER JOIN {$wpdb->prefix}learnpress_user_quizmeta uqm ON uqm.learnpress_user_quiz_id = uq.user_quiz_id AND uqm.meta_key = %s AND uqm.meta_value LIKE %s
		", 'questions', '%i:' . $wpdb->esc_like( $question_id . '' ) . ';%' );
		return $wpdb->get_var( $query );
	}

	public function get_answer_results( $question_id, $quiz_id = null ) {
		$data = false;
		if ( !$quiz_id ) {
			$quiz_id = $this->get_quiz_by_question( $question_id );
		}
		if ( $quiz_id ) {
			if ( $question = LP_Question_Factory::get_question( $question_id ) ) {
				$quiz_results = $this->get_quiz_results( $quiz_id );
				if ( !empty( $quiz_results->question_answers ) ) {
					$question_answer = array_key_exists( $question_id, $quiz_results->question_answers ) ? $quiz_results->question_answers[$question_id] : null;
					$data            = $question->check( $question_answer );
				}
			}
		}
		return $data;
	}

	public function is_answered_question( $question_id, $quiz_id = null ) {
		if ( empty( $this->answered_questions ) ) {
			$this->answered_questions = array();
		}
		if ( !$quiz_id ) {
			$quiz_id = $this->get_quiz_by_question( $question_id );
		}

		$results  = $this->get_quiz_results( $quiz_id );
		$answered = !empty( $results->question_answers ) ? $results->question_answers : array();
		return $answered ? array_key_exists( $question_id, $answered ) : false;
	}

	public function get_enrolled_courses( $args = array() ) {
		global $wpdb;
		static $courses = array();
		$args = wp_parse_args(
			$args,
			array(
				'status'  => '',
				'limit'   => - 1,
				'paged'   => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
				'user_id' => $this->id
			)
		);
		ksort( $args );
		$key = md5( serialize( $args ) );
		if ( empty( $courses[$key] ) ) {
			$where = $args['status'] ? $wpdb->prepare( "AND uc.status = %s", $args['status'] ) : '';
			$limit = "\n";
			if ( $args['limit'] > 0 ) {
				if ( !$args['paged'] ) {
					$args['paged'] = 1;
				}
				$start = ( $args['paged'] - 1 ) * $args['limit'];
				$limit .= "LIMIT " . $start . ',' . $args['limit'];
			}
			$query = $wpdb->prepare( "
				SELECT SQL_CALC_FOUND_ROWS c.*, uc.status as course_status
				FROM {$wpdb->posts} c
				INNER JOIN {$wpdb->learnpress_user_courses} uc ON c.ID = uc.course_id
				WHERE uc.user_id = %d
					AND c.post_type = %s
					AND c.post_status = %s
			", $args['user_id'], 'lp_course', 'publish' );
			$query .= $where . $limit;
			$data          = array(
				'rows' => $wpdb->get_results( $query, OBJECT_K )
			);
			$data['count'] = $wpdb->get_var( "SELECT FOUND_ROWS();" );

			$courses[$key] = $data;
		}
		$this->_FOUND_ROWS = $courses[$key]['count'];
		return $courses[$key]['rows'];
	}

	public function get_purchased_courses( $args = array() ) {
		global $wpdb;
		static $courses = array();
		$args = wp_parse_args(
			$args,
			array(
				'status'  => '',
				'limit'   => - 1,
				'paged'   => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
				'user_id' => $this->id,
				'fields'  => ''
			)
		);
		ksort( $args );
		$key = md5( serialize( $args ) );
		if ( empty( $courses[$key] ) ) {
			$limit = "\n";
			if ( $args['limit'] > 0 ) {
				if ( !$args['paged'] ) {
					$args['paged'] = 1;
				}
				$start = ( $args['paged'] - 1 ) * $args['limit'];
				$limit .= "LIMIT " . $start . ',' . $args['limit'];
			}
			$query = $wpdb->prepare( "
				SELECT SQL_CALC_FOUND_ROWS c.*
				FROM {$wpdb->posts} o
				INNER JOIN {$wpdb->prefix}learnpress_order_items oi ON oi.order_id = o.ID
				INNER JOIN {$wpdb->prefix}learnpress_order_itemmeta oim ON oim.learnpress_order_item_id = oi.order_item_id AND oim.meta_key = %s
				INNER JOIN {$wpdb->posts} c ON c.ID = oim.meta_value
				INNER JOIN {$wpdb->postmeta} om ON om.post_id = o.ID AND om.meta_key = %s
				WHERE o.post_status IN( %s, %s, %s )
				AND c.post_type = %s
				AND c.post_status = %s
				AND om.meta_value = %d
			", '_course_id', '_user_id', 'lp-completed', 'lp-processing', 'lp-on-hold', 'lp_course', 'publish', $args['user_id'] );
			$query .= $limit;

			$data          = array(
				'rows' => $wpdb->get_results( $query, OBJECT_K )
			);
			$data['count'] = $wpdb->get_var( "SELECT FOUND_ROWS();" );

			$courses[$key] = $data;
		}
		$this->_FOUND_ROWS = $courses[$key]['count'];
		return $courses[$key]['rows'];
	}

	public function get_own_courses( $args = array() ) {
		global $wpdb;
		static $courses = array();
		$args = wp_parse_args(
			$args,
			array(
				'limit'   => - 1,
				'paged'   => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
				'user_id' => $this->id
			)
		);
		ksort( $args );
		$key = md5( serialize( $args ) );
		if ( empty( $courses[$key] ) ) {
			$limit = "\n";
			if ( $args['limit'] > 0 ) {
				if ( !$args['paged'] ) {
					$args['paged'] = 1;
				}
				$start = ( $args['paged'] - 1 ) * $args['limit'];
				$limit .= "LIMIT " . $start . ',' . $args['limit'];
			}
			$query = $wpdb->prepare( "
				SELECT SQL_CALC_FOUND_ROWS c.*
				FROM {$wpdb->posts} c
				WHERE c.post_status = %s
				AND c.post_type = %s
				AND c.post_author = %d
			", 'publish', 'lp_course', $args['user_id'] );
			$query .= $limit;
			$data          = array(
				'rows' => $wpdb->get_results( $query, OBJECT_K )
			);
			$data['count'] = $wpdb->get_var( "SELECT FOUND_ROWS();" );

			$courses[$key] = $data;

		}
		$this->_FOUND_ROWS = $courses[$key]['count'];
		return $courses[$key]['rows'];
	}

	public function _get_found_rows() {
		return $this->_FOUND_ROWS;
	}

	public function has_checked_answer( $question_id, $quiz_id ) {
		$history = $this->get_quiz_results( $quiz_id );
		if ( !$history ) {
			return;
		}
		$checked = (array) learn_press_get_user_quiz_meta( $history->history_id, 'checked' );
		$checked = array_filter( $checked );
		return in_array( $question_id, $checked );
	}
}