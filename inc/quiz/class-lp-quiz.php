<?php

/**
 * Class LP_Quiz
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Quiz extends LP_Course_Item implements ArrayAccess {

	/**
	 * LP_Quiz_CURD
	 *
	 * @var LP_Quiz_CURD
	 */
	protected $_curd = false;

	/**
	 * @var array
	 *
	 * @deprecated
	 */
	static protected $_meta = array();

	/**
	 * @var string
	 */
	protected $_item_type = 'lp_quiz';

	protected $_questions = array();

	protected $_data = array(
		'retake_count'       => 0,
		'show_result'        => 'no',
		'passing_grade_type' => '',
		'passing_grade'      => 0
	);

	protected static $_loaded = 0;

	/**
	 * Constructor gets the post object and sets the ID for the loaded course.
	 *
	 * @param mixed $the_quiz
	 * @param mixed $args
	 */
	public function __construct( $the_quiz, $args = array() ) {

		//parent::__construct( $the_quiz, $args );

		$this->_curd = new LP_Quiz_CURD();

		if ( is_numeric( $the_quiz ) && $the_quiz > 0 ) {
			$this->set_id( $the_quiz );
		} elseif ( $the_quiz instanceof self ) {
			$this->set_id( absint( $the_quiz->get_id() ) );
		} elseif ( ! empty( $the_quiz->ID ) ) {
			$this->set_id( absint( $the_quiz->ID ) );
		}
		if ( $this->get_id() > 0 ) {
			$this->load();
		}

		self::$_loaded ++;
		if ( self::$_loaded == 1 ) {
			add_filter( 'debug_data', array( __CLASS__, 'log' ) );
		}
	}

	public static function log( $data ) {
		$data[] = __CLASS__ . '( ' . self::$_loaded . ' )';

		return $data;
	}

	public function get_heading_title() {
		global $lp_quiz_question;
		$title = $this->get_title();
		if ( $lp_quiz_question instanceof LP_Question ) {
			$titles = apply_filters( 'learn-press/quiz/title-parts', array(
				$title,
				sprintf( '<small>%s</small>', $lp_quiz_question->get_title() )
			) );
			$title  = apply_filters( 'learn-press/quiz/heading-title', join( ' ', $titles ) );
		}

		return $title;
	}

	/**
	 * Load quiz data
	 */
	public function load() {
		$this->_curd->load( $this );
	}

	public function set_retake_count( $count ) {
		$this->_set_data( 'retake_count', $count );
	}

	public function get_retake_count() {
		return $this->get_data( 'retake_count' );
	}

	public function set_show_result( $show_result ) {
		$this->_set_data( 'show_result', $show_result );
	}

	public function get_show_result() {
		return $this->get_data( 'show_result' );
	}

	public function set_passing_grade_type( $type ) {
		$this->_set_data( 'passing_grade_type', $type );
	}

	public function get_passing_grade_type() {
		return $this->get_data( 'passing_grade_type' );
	}

	public function set_passing_grade( $value ) {
		$this->_set_data( 'passing_grade', $value );
	}

	public function get_passing_grade() {
		return $this->get_data( 'passing_grade' );
	}

	/**
	 * Return total mark of quiz by calculating total mark of all questions.
	 *
	 * @return int
	 */
	public function get_mark() {
		$mark = $this->get_data( 'mark' );
		if ( false === $mark || '' === $mark ) {
			$questions = $this->get_questions();
			$mark      = 0;
			foreach ( $questions as $question_id ) {
				$question = LP_Question::get_question( $question_id );
				$mark     += $question->get_mark();
			}
			$this->_set_data( 'mark', $mark );
		}

		return apply_filters( 'learn-press/quiz-mark', $mark, $this->get_id() );
	}

	/**
	 * Return total mark of quiz by calculating total mark of all questions.
	 *
	 * @return LP_Duration
	 */
	public function get_duration() {
		$duration = $this->get_data( 'duration' );
		if ( false === $duration || '' === $duration ) {
			if ( $duration = get_post_meta( $this->get_id(), '_lp_duration', true ) ) {
				$duration = new LP_Duration( $duration );
			}

			$this->_set_data( 'duration', $duration );
		}

		return apply_filters( 'learn-press/quiz-duration', $duration, $this->get_id() );
	}

	/**
	 * Get quiz questions.
	 *
	 * @return mixed
	 */
	public function get_questions() {
		$questions = $this->_curd->get_questions( $this );

		return apply_filters( 'learn-press/quiz/questions', $questions, $this->get_id() );
	}

	/************/
	/**
	 * Get admin configuration.
	 *
	 * @return array
	 */
	public function get_admin_config() {
		$id     = $this->get_id();
		$config = array(
			'id'        => $id,
			'questions' => $this->get_questions(),
			'closed'    => learn_press_is_hidden_post_box( $id )
		);

		return apply_filters( 'learn-press/quiz/admin-config', $config, $id );
	}


	/******/


	protected function _init() {

		add_action( 'wp_head', array( $this, 'frontend_assets' ) );
	}

	public function get_duration_html() {
		$duration = $this->get_duration();
		if ( $duration ) {
			$duration = learn_press_seconds_to_time( $duration->get_seconds() );
		} else {
			$duration = __( 'Unlimited', 'learnpress' );
		}

		return apply_filters( 'learn_press_quiz_duration_html', $duration, $this );
	}

	public function get_total_questions() {
		$questions = $this->get_questions();
		$count     = 0;
		if ( $questions ) {
			$count = count( $questions );
		}

		return $count;
	}

	public function get_localize() {
		$localize = array(
			'confirm_finish_quiz' => array(
				'title'   => __( 'Finish quiz', 'learnpress' ),
				'message' => __( 'Are you sure you want to finish this quiz?', 'learnpress' )
			),
			'confirm_retake_quiz' => array(
				'title'   => __( 'Retake quiz', 'learnpress' ),
				'message' => __( 'Are you sure you want to retake this quiz?', 'learnpress' )
			),
			'quiz_time_is_over'   => array(
				'title'   => __( 'Time out!', 'learnpress' ),
				'message' => __( 'The time is over! Your quiz will automate come to finish', 'learnpress' )
			),
			'finished_quiz'       => __( 'Congrats! You have finished this quiz', 'learnpress' ),
			'retaken_quiz'        => __( 'Congrats! You have re-taken this quiz. Please wait a moment and the page will reload', 'learnpress' )
		);

		return apply_filters( 'learn_press_single_quiz_localize', $localize, $this );
	}

	/**
	 * Get quiz's settings for json
	 *
	 * @param int  $user_id
	 * @param int  $course_id
	 * @param bool $force
	 *
	 * @return mixed|void
	 */
	public function get_settings( $user_id = 0, $course_id = 0, $force = false ) {
		if ( ! $course_id ) {
			$course_id = get_the_ID();
		}
		$user        = learn_press_get_current_user( $user_id );
		$course      = learn_press_get_course( $course_id );
		$quiz_params = LP_Cache::get_quiz_params( false, array() );
		$key         = sprintf( '%d-%d-%d', $user_id, $course_id, $this->get_id() );
		if ( ! array_key_exists( $key, $quiz_params ) || $force ) {

			if ( $results = $user->get_quiz_results( $this->get_id(), $course_id, $force ) ) {
				$questions = $results->questions;
			} else {
				$questions = learn_press_get_quiz_questions();
				$questions = array_keys( $questions );
			}

			$current_question_id = $user->get_current_quiz_question( $this->get_id(), $course->get_id() );
			$question            = LP_Question::get_question( $current_question_id );
			$duration            = $this->get_duration();
			$remaining           = $user->get_quiz_time_remaining( $this->get_id(), $course_id );
			if ( $remaining === false ) {
				$remaining = $this->get_duration();
			} elseif ( $remaining < 0 ) {
				$remaining = 0;
			}
			//$r_time              = ( $remaining > 0 ) && !in_array( $user->get_quiz_status( $this->get_id(), $course_id, $force ), array( '', 'completed' ) ) ? $remaining : $this->duration;

			$js = array(
				'id'              => $this->get_id(),
				'questions'       => array_values( $this->get_question_params( $questions, $current_question_id ) ),
				//$questions,
				'status'          => $user->get_quiz_status( $this->get_id(), $course_id, $force ),
				'permalink'       => get_the_permalink(),
				'ajaxurl'         => admin_url( 'admin-ajax.php' ),
				'question'        => $question ? array( 'check_answer' => $question->can_check_answer() ) : false,
				'totalTime'       => $this->get_duration(),
				'userTime'        => $duration - $remaining,
				'currentQuestion' => get_post_field( 'post_name', $current_question_id ),
				'usePermalink'    => get_option( 'permalink' ),
				'courseId'        => $course_id
			);
			if ( $js['status'] == 'completed' ) {
				$js['result'] = $user->get_quiz_results( $this->get_id(), $course_id, $force );
			}
			if ( $js['status'] == 'started' ) {
				if ( $history = $user->get_quiz_results( $this->get_id(), $course_id ) ) {
					$js['startTime']  = strtotime( $history->start, current_time( 'timestamp' ) );
					$js['serverTime'] = date( 'Z' ) / 3600;//date_timezone_get( date_default_timezone_get() );// get_option('gmt_offset');
				}
			}

			$quiz_params[ $key ] = $js;
			LP_Cache::set_quiz_params( $quiz_params );
		}

		return apply_filters( 'learn_press_single_quiz_params', $quiz_params[ $key ], $this );
	}

	public function get_question_params( $ids, $current = 0 ) {
		global $wpdb;
		if ( ! $ids ) {
			$ids = array( 0 );
		}

		$results = array();
		if ( $questions = $this->get_questions() ) {
			$user              = learn_press_get_current_user();
			$show_check_answer = $this->show_check_answer;
			$show_hint         = $this->show_hint;
			$checked_answers   = array();
			//$show_explanation  = $this->show_explanation;
			if ( $show_check_answer == 'yes' ) {
				if ( $history = $user->get_quiz_results( $this->get_id() ) ) {
					$checked_answers = ! empty( $history->checked ) ? (array) $history->checked : array();
				}
			}
			foreach ( $questions as $question_id => $question ) {
				$_question = (object) array(
					'id'    => absint( $question->ID ),
					'type'  => $question->type,
					'title' => get_the_title( $question->ID ),
					'name'  => get_post_field( 'post_name', $question->ID ),
					'url'   => trailingslashit( $this->get_question_link( $question->ID ) )
				);
				if ( $show_check_answer == 'yes' ) {
					//$_question->check_answer = learn_press_question_type_support( $question->type, 'check-answer' );
					$_question->hasCheckAnswer = learn_press_question_type_support( $question->type, 'check-answer' ) ? 'yes' : 'no';
					$_question->checked        = array();
				}
				if ( $show_hint == 'yes' && empty( $question->hasHint ) ) {
					$_question->hasHint = get_post_meta( $question->ID, '_lp_hint', true ) ? 'yes' : 'no';
				}
				/*if ( $show_explanation == 'yes' && empty( $question->hasExplanation ) ) {
					$_question->hasExplanation = get_post_meta( $question->ID, '_lp_explanation', true ) ? 'yes' : 'no';
				}*/
				/*if ( empty( $results[$row->id] ) ) {
					$results[$row->id] = (object) array(
						'id'   => absint( $row->id ),
						'type' => $row->type
					);
					if ( $show_check_answer == 'yes' ) {
						$results[$row->id]->check_answer = learn_press_question_type_support( $row->type, 'check-answer' );
						$results[$row->id]->checked      = array();
					}

					if ( $show_hint == 'yes' && empty( $results[$row->id]->hint ) ) {
						$results[$row->id]->hint = get_post_meta( $row->id, '_lp_hint', true ) ? true : false;
					}
					if ( $show_explanation == 'yes' && empty( $results[$row->id]->explanation ) ) {
						$results[$row->id]->explanation = get_post_meta( $row->id, '_lp_explanation', true ) ? true : false;
					}
				//}
				*/
				if ( $show_check_answer == 'yes' ) {
					if ( in_array( $question->ID, $checked_answers ) ) {
						if ( ! empty( $question->answers ) ) {
							foreach ( $question->answers as $answer ) {
								$checked = maybe_unserialize( $answer );
								unset( $checked['text'] );
								$_question->checked[ $answer['id'] ] = $checked;
							}
						}
					} else {
						$_question->checked = false;
					}
				}

				if ( $current == $question->ID ) {
					$_question->current = 'yes';
				}
				$results[ $question->ID ] = $_question;
			}
		}

		return apply_filters( 'learn_press_quiz_param_questions', $results, $this->get_id() );
	}

	public function frontend_assets() {
		if ( learn_press_is_course() && ( $quiz = LP()->global['course-item'] ) && $quiz->id == $this->get_id() ) {
			$translate = $this->get_localize();
			//LP_Assets::add_localize( $translate, false, 'learn-press-single-quiz' );
			//LP_Assets::add_param( $this->get_settings(), false, 'learn-press-single-quiz' );
		}
	}

	/**
	 * __isset function.
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		return metadata_exists( 'post', $this->get_id(), '_' . $key );
	}

	/**
	 * __get function.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		echo '@deprecated[' . $key . ']';
		learn_press_debug( debug_backtrace() );

		return false;
	}

	/**
	 * This function is no longer support. Check directly from course.
	 *
	 * @deprecated
	 *
	 * @param int $the_course
	 *
	 * @return bool
	 */
	public function is_require_enrollment( $the_course = 0 ) {
		if ( ! $the_course ) {
			$the_course = get_the_ID();
		}

		$return = false;
		if ( $course = learn_press_get_course( $the_course ) ) {
			$return = $course->is_require_enrollment();
		}

		return $return;
	}

	/**
	 * Get the course that contains this quiz
	 *
	 * @param string
	 *
	 * @return bool|null
	 */
	public function get_course( $args = null ) {
		if ( empty( $this->course ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "
				SELECT c.*
				FROM {$wpdb->posts} c
				INNER JOIN {$wpdb->learnpress_sections} s on c.ID = s.section_course_id
				INNER JOIN {$wpdb->learnpress_section_items} si on si.section_id = s.section_id AND si.item_id = %d
				", $this->get_id() );
			if ( $course_id = $wpdb->get_var( $query ) ) {
				$this->course = LP_Course::get_course( $course_id );
			}
		}
		$return = $this->course;
		if ( $this->course && $args ) {
			$args = wp_parse_args( $args, array( 'field' => null ) );
			if ( $args['field'] ) {
				$return = $this->course->{$args['field']};
			}
		}

		return $return;
	}

	public function has( $feature ) {
		$args = func_get_args();
		unset( $args[0] );
		$method   = 'has_' . preg_replace( '!-!', '_', $feature );
		$callback = array( $this, $method );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		} else {
			throw new Exception( sprintf( __( 'The function %s doesn\'t exists', 'learnpress' ), $feature ) );
		}
	}

	/**
	 * This quiz has any question?
	 *
	 * @return bool
	 */
	public function has_questions() {
		return $this->count_questions() > 0;
	}

	/**
	 * Return TRUE if quiz contain a question.
	 *
	 * @param int $question_id
	 *
	 * @return bool
	 */
	public function has_question( $question_id ) {
		$questions = $this->get_questions();

		return apply_filters( 'learn-press/quiz/has-question', is_array( $questions ) && ( false !== array_search( $question_id, $questions ) ), $question_id, $this->get_id() );
	}

	/**
	 * Get question permalink from it's ID.
	 * If permalink option is turn on, add name of question
	 * into quiz permalink. Otherwise, add it's ID into
	 * query var.
	 *
	 * @param int $question_id
	 *
	 * @return string
	 */
	public function get_question_link( $question_id = null ) {
		$course = LP_Global::course();

		$permalink = $course->get_item_link( $this->get_id() );
		if ( '' != get_option( 'permalink_structure' ) && get_post_status( $this->get_id() ) != 'draft' ) {
			$question_name = get_post_field( 'post_name', $question_id );
			$permalink     = $permalink . $question_name;
		} else {
			$permalink = add_query_arg( array( 'question', $question_id ), $permalink );
		}

		// @deprecated
		$permalink = apply_filters( 'learn_press_quiz_question_permalink', $permalink, $question_id, $this );

		return apply_filters( 'learn-press/quiz/question-permalink', $permalink, $question_id, $this->get_id() );
	}

	/**
	 * Get prev question from a question.
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function get_prev_question( $id ) {
		$prev = false;
		if ( ( $questions = $this->get_questions() ) ) {
			$questions = array_values( $questions );
			if ( 0 < ( $at = array_search( $id, $questions ) ) ) {
				$prev = $questions[ $at - 1 ];
			}
		}

		return apply_filters( 'learn-press/quiz/prev-question-id', $prev, $this->get_id() );
	}

	/**
	 * Get next question from a question.
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function get_next_question( $id ) {
		$next = false;
		if ( ( $questions = $this->get_questions() ) ) {
			$questions = array_values( $questions );
			if ( sizeof( $questions ) - 1 > ( $at = array_search( $id, $questions ) ) ) {
				$next = $questions[ $at + 1 ];
			}
		}

		return apply_filters( 'learn-press/quiz/next-question-id', $next, $this->get_id() );
	}

	/**
	 * Get index number of a question.
	 *
	 * @param int $id
	 * @param int $start
	 *
	 * @return bool|mixed
	 */
	public function get_question_index( $id, $start = 0 ) {
		$index = false;
		if ( ( $questions = $this->get_questions() ) ) {
			$questions = array_values( $questions );
			$index     = array_search( $id, $questions );
		}

		return apply_filters( 'learn-press/quiz/question-index', intval( $start ) + $index, $this->get_id() );
	}

	/**
	 * Count number questions in quiz.
	 *
	 * @return int
	 */
	public function count_questions() {
		$size = 0;
		if ( ( $questions = $this->get_questions() ) ) {
			$size = sizeof( $questions );
		}

		return apply_filters( 'learn-press/quiz/count-questions', $size, $this->get_id() );
	}


	public function get_question_param( $name, $id ) {
		if ( $this->get_questions() ) {
			if ( ! empty( $this->questions[ $id ] ) ) {
				return ! empty( $this->questions[ $id ]->params[ $name ] ) ? $this->questions[ $id ]->params[ $name ] : null;
			}
		}

		return false;
	}

	public function check_question( $question_id, $user_id ) {

		if ( ! $question = LP_Question::get_question( $question_id ) ) {
			return false;
		}

		$user = learn_press_get_user( $user_id );

		$history = $user->get_quiz_results( $this->get_id() );
		if ( ! $history ) {
			return false;
		}
		$checked = (array) learn_press_get_user_quiz_meta( $history->history_id, 'checked' );
		$checked = array_filter( $checked );
		if ( ! in_array( $question_id, $checked ) ) {
			$checked[] = $question_id;
		}

		learn_press_update_user_quiz_meta( $history->history_id, 'checked', $checked );

	}

	public function get_question_position( $question, $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = learn_press_get_current_user_id();
		}
		$user = learn_press_get_user( $user_id );
		if ( $user && $results = $user->get_quiz_results( $this->get_id() ) ) {
			$questions = (array) $results->questions;
		} else {
			$questions = (array) $this->get_questions();
			$questions = array_keys( $questions );
		}
		$position = array_search( $question, $questions );

		return $position;
	}

	public function get_current_question( $user_id = 0, $course_id = 0 ) {
		$user = learn_press_get_current_user( $user_id );
		$id   = $user->get_current_quiz_question( $this->get_id(), $course_id );

		return LP_Question::get_question( $id );
	}

	/**
	 * @return LP_Question
	 */
	public function get_viewing_question() {
		global $lp_quiz_question;

		return $lp_quiz_question;
	}

	public function offsetSet( $offset, $value ) {
		//$this->set_data( $offset, $value );
		// Do not allow to set value directly!
	}

	public function offsetUnset( $offset ) {
		// Do not allow to unset value directly!
	}

	public function offsetGet( $offset ) {
		return $this->offsetExists( $offset ) ? $this->_questions[ $offset ] : false;
	}

	public function offsetExists( $offset ) {
		return array_key_exists( $offset, $this->_questions );
	}

	/**
	 * @param bool  $the_quiz
	 * @param array $args
	 *
	 * @return LP_Course|bool
	 */
	public static function get_quiz( $the_quiz = false, $args = array() ) {
		$the_quiz = self::get_quiz_object( $the_quiz );
		if ( ! $the_quiz ) {
			return false;
		}

		if ( ! empty( $args['force'] ) ) {
			$force = ! ! $args['force'];
			unset( $args['force'] );
		} else {
			$force = false;
		}

		$key_args = wp_parse_args( $args, array( 'id' => $the_quiz->ID, 'type' => $the_quiz->post_type ) );

		$key = LP_Helper::array_to_md5( $key_args );

		if ( $force ) {
			LP_Global::$quizzes[ $key ] = false;
		}

		if ( empty( LP_Global::$quizzes[ $key ] ) ) {
			$class_name = self::get_quiz_class( $the_quiz, $args );
			if ( is_string( $class_name ) && class_exists( $class_name ) ) {
				$lesson = new $class_name( $the_quiz->ID, $args );
			} elseif ( $class_name instanceof LP_Course_Item ) {
				$lesson = $class_name;
			} else {
				$lesson = new self( $the_quiz->ID, $args );
			}
			LP_Global::$quizzes[ $key ] = $lesson;
		}

		return LP_Global::$quizzes[ $key ];
	}

	/**
	 * @param  string $quiz_type
	 *
	 * @return string|false
	 */
	private static function get_class_name_from_quiz_type( $quiz_type ) {
		return LP_QUIZ_CPT === $quiz_type ? __CLASS__ : 'LP_Quiz_' . implode( '_', array_map( 'ucfirst', explode( '-', $quiz_type ) ) );
	}

	/**
	 * Get the lesson class name
	 *
	 * @param  WP_Post $the_quiz
	 * @param  array   $args (default: array())
	 *
	 * @return string
	 */
	private static function get_quiz_class( $the_quiz, $args = array() ) {
		$lesson_id = absint( $the_quiz->ID );
		$type      = $the_quiz->post_type;

		$class_name = self::get_class_name_from_quiz_type( $type );

		// Filter class name so that the class can be overridden if extended.
		return apply_filters( 'learn-press/quiz/object-class', $class_name, $type, $lesson_id );
	}

	/**
	 * Get the lesson object
	 *
	 * @param  mixed $the_quiz
	 *
	 * @uses   WP_Post
	 * @return WP_Post|bool false on failure
	 */
	private static function get_quiz_object( $the_quiz ) {
		if ( false === $the_quiz ) {
			$the_quiz = get_post_type() === LP_LESSON_CPT ? $GLOBALS['post'] : false;
		} elseif ( is_numeric( $the_quiz ) ) {
			$the_quiz = get_post( $the_quiz );
		} elseif ( $the_quiz instanceof LP_Course_Item ) {
			$the_quiz = get_post( $the_quiz->get_id() );
		} elseif ( ! ( $the_quiz instanceof WP_Post ) ) {
			$the_quiz = false;
		}

		return apply_filters( 'learn-press/quiz/post-object', $the_quiz );
	}
}