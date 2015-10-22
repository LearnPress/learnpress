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
			} elseif ( strpos( $key, '_lpr_' ) === false ) {
				$key    = '_lpr_' . $key;
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

	/**
	 * Start quiz for the user
	 *
	 * @param null $quiz_id
	 *
	 * @return array|void
	 */
	function start_quiz( $quiz_id = null ) {
		if ( !$quiz_id ) $quiz_id = $this->get_quiz_field( 'id' );
		$user_id       = $this->id;
		$location_time = current_time( 'timestamp' );

		// @since 0.9.5
		if ( !apply_filters( 'learn_press_before_user_start_quiz', true, $quiz_id, $user_id ) ) {
			return;
		}

		// update start time, this is the time user begin the quiz
		$meta = get_user_meta( $user_id, '_lpr_quiz_start_time', true );
		if ( !is_array( $meta ) ) $meta = array( $quiz_id => $location_time );
		else $meta[$quiz_id] = $location_time;
		update_user_meta( $user_id, '_lpr_quiz_start_time', $meta );

		// update questions
		if ( $questions = learn_press_get_quiz_questions( $quiz_id ) ) {

			// stores the questions
			$question_ids = array_keys( $questions );
			$meta         = get_user_meta( $user_id, '_lpr_quiz_questions', true );
			if ( !is_array( $meta ) ) $meta = array( $quiz_id => $question_ids );
			else $meta[$quiz_id] = $question_ids;
			update_user_meta( $user_id, '_lpr_quiz_questions', $meta );

			// stores current question
			$meta = get_user_meta( $user_id, '_lpr_quiz_current_question', true );
			if ( !is_array( $meta ) ) $meta = array( $quiz_id => $question_ids[0] );
			else $meta[$quiz_id] = $question_ids[0];
			update_user_meta( $user_id, '_lpr_quiz_current_question', $meta );

		}
		$course_id   = learn_press_get_course_by_quiz( $quiz_id );
		$course_time = get_user_meta( $user_id, '_lpr_course_time', true );
		if ( empty( $course_time[$course_id] ) ) {
			$course_time[$course_id] = array(
				'start' => $location_time,
				'end'   => null
			);
			update_user_meta( $user_id, '_lpr_course_time', $course_time );
		}


		// update answers
		$quizzes = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );
		if ( !is_array( $quizzes ) ) $quizzes = array();
		$quizzes[$quiz_id] = array();
		update_user_meta( $user_id, '_lpr_quiz_question_answer', $quizzes );

		// @since 0.9.5
		do_action( 'learn_press_user_start_quiz', $quiz_id, $user_id );

		return array(
			'start' => $location_time,
			'end'   => null
		);
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
		echo $what;
		if ( $what == 'id' ) {
			return $current;
		} else {
			$question = LP_Question::instance( $current );
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

	function finish_quiz( $quiz_id ) {
		$quiz = LP_Quiz::get_quiz( $quiz_id );
		if ( !$quiz ) {
			return;
		}
		$user_id        = $this->id;
		$time           = current_time( 'timestamp' );
		$quiz_start     = get_user_meta( $user_id, '_lpr_quiz_start_time', true );
		$quiz_completed = get_user_meta( $user_id, '_lpr_quiz_completed', true );
		$quiz_duration  = absint( get_post_meta( $quiz_id, '_lpr_duration', true ) ) * 60;

		if ( $time - $quiz_start[$quiz_id] > $quiz_duration ) {
			$time = $quiz_start[$quiz_id] - $quiz_duration;
		}

		$quiz_completed[$quiz_id] = $time;

		update_user_meta( $user_id, '_lpr_quiz_completed', $quiz_completed );
		$course_id = learn_press_get_course_by_quiz( $quiz_id );
		if ( !learn_press_user_has_finished_course( $course_id ) ) {
			if ( learn_press_user_has_completed_all_parts( $course_id, $user_id ) ) {
				learn_press_finish_course( $course_id, $user_id );
			}
		}

		do_action( 'learn_press_finish_quiz', $quiz_id, $user_id );

	}

	function retake_quiz() {

	}

	/**
	 * Get quiz status for the user
	 *
	 * @param null $quiz_id
	 *
	 * @return mixed
	 */
	function get_quiz_status( $quiz_id = null ) {
		if ( !$quiz_id ) $quiz_id = $this->get_quiz_field( 'id' );

		$status = '';
		if ( learn_press_user_has_started_quiz( $this->id, $quiz_id ) ) {
			$status = 'started';
		}

		if ( learn_press_user_has_completed_quiz( $this->id, $quiz_id ) ) {
			$status = 'completed';
		}
		return apply_filters( 'learn_press_user_quiz_status', $status, $this, $quiz_id );
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
	 *
	 * @return array
	 */
	function get_quizzes( $args = array() ) {
		settype( $args, 'array' );
		$args['post_type'] = LP()->quiz_post_type;
		return $this->get_posts( $args );
	}

	/**
	 * Get all quizzes of the user
	 *
	 * @param array $args - actually, it as the same with WP_Query args
	 *
	 * @return array
	 */
	function get_lessons( $args = array() ) {
		settype( $args, 'array' );
		$args['post_type'] = LP()->lesson_post_type;
		return $this->get_posts( $args );
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
	 * @return bool
	 */
	function can_enroll_course( $course_id ) {
		$enrollable = !$this->has_enrolled_course( $course_id ) && $this->has_purchased_course( $course_id );
		return apply_filters( 'learn_press_user_can_enroll_course', $enrollable, $this, $course_id );
	}

	/**
	 * Return true if user can view a lesson
	 *
	 * @param $lesson_id
	 * @return bool
	 */
	function can_view_lesson( $lesson_id ){
		$lesson = LP_Lesson::get_lesson( $lesson_id );
		return $lesson->is( 'previewable' );
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
		return apply_filters( 'learn_press_user_course_status', $info, $this, $course_id );
	}

	function get_course_status( $course_id ) {
		return $this->get_course_info( $course_id, 'status' );
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
		$query    = $wpdb->prepare( "
			SELECT order_id
			FROM {$wpdb->posts} o
			INNER JOIN {$wpdb->learnpress_order_items} oi ON o.ID = oi.order_ID
			INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.learnpress_order_item_id= oi.order_item_id AND oim.meta_key = %s AND oim.meta_value = %d
		", '_course_id', $course_id );
		$order_id = $wpdb->get_var( $query );
		if ( $order_id && $return == 'object' ) {
			$order = LP_Order::instance( $order_id );
		} else {
			$order = $order_id;
		}
		return $order;
	}

	function enroll( $course_id ) {
		if ( !$this->can( 'enroll-course', $course_id ) ) {
			learn_press_add_notice( __( 'Sorry! You can not enroll this course. Please try again or contact site admin' ), 'error' );
			return;
		}
		global $wpdb;

		do_action( 'learn_press_before_enroll_course', $this, $course_id );

		$inserted = 0;

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
}