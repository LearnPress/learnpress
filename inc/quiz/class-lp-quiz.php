<?php
/**
 * Class LP_Quiz.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Quiz' ) ) {

	/**
	 * Class LP_Quiz
	 */
	class LP_Quiz extends LP_Course_Item implements ArrayAccess {


		/**
		 * @var array
		 *
		 * @deprecated
		 */
		static protected $_meta = array();

		/**
		 * @var string
		 */
		protected $_item_type = LP_QUIZ_CPT;

		/**
		 * @var array
		 */
		protected $_questions = array();

		/**
		 * @var array
		 */
		protected $_data = array(
			'retake_count'         => 0,
			'show_result'          => 'no',
			'passing_grade_type'   => '',
			'passing_grade'        => 0,
			'show_check_answer'    => 'no',
			'count_check_answer'   => 0,
			'show_hint'            => 'no',
			'count_hint'           => 0,
			'archive_history'      => 'no',
			'show_hide_question'   => 'yes',
			'preview'              => 'no',
			'minus_points'         => 0,
			'minus_skip_questions' => 'no'
		);

		/**
		 * @var int
		 */
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

		/**
		 * Log debug data.
		 *
		 * @param $data
		 *
		 * @return array
		 * @since 3.0.0
		 *
		 */
		public static function log( $data ) {
			$data[] = __CLASS__ . '( ' . self::$_loaded . ' )';

			return $data;
		}

		/**
		 * @param string $context
		 *
		 * @return string
		 */
		public function get_heading_title( $context = '' ) {
			return $this->get_title( $context );
		}

		/**
		 * Load quiz data
		 */
		public function load() {
			$this->_curd->load( $this );
		}

		/**
		 * Get default quiz meta.
		 *
		 * @return mixed
		 * @since 3.0.0
		 *
		 */
		public static function get_default_meta() {
			$meta = array(
				'preview'              => 'no',
				'minus_points'         => 0,
				'minus_skip_questions' => 'no',
				'show_hide_question'   => 'yes',
				'review_questions'     => 'no',
				'show_result'          => 'no',
				'duration'             => '10 minute',
				'passing_grade'        => 80,
				'retake_count'         => 0,
				'archive_history'      => 'no',
				'show_check_answer'    => 0,
				'show_hint'            => 0
			);

			return apply_filters( 'learn-press/quiz/default-meta', $meta );
		}

		/**
		 * Save quiz data.
		 *
		 * @return mixed
		 *
		 * @throws Exception
		 */
		public function save() {
			if ( $this->get_id() ) {
				$return = $this->_curd->update( $this );
			} else {
				$return = $this->_curd->create( $this );
			}

			return $return;
		}

		/**
		 * Set quiz retake count.
		 *
		 * @param $count
		 *
		 * @since 3.0.0
		 *
		 */
		public function set_retake_count( $count ) {
			$this->_set_data( 'retake_count', $count );
		}

		/**
		 * @return array|mixed
		 */
		public function get_retake_count() {
			return $this->get_data( 'retake_count' );
		}

		/**
		 * @param $show_result
		 */
		public function set_show_result( $show_result ) {
			$this->_set_data( 'show_result', $show_result );
		}

		/**
		 * @return array|mixed
		 */
		public function get_show_result() {
			return $this->get_data( 'show_result' ) === 'yes';
		}

		/**
		 * @param $review_questions
		 */
		public function set_review_questions( $review_questions ) {
			$this->_set_data( 'review_questions', $review_questions );
		}

		/**
		 * @return array|mixed
		 */
		public function get_review_questions() {
			return $this->get_data( 'review_questions' ) === 'yes';
		}

		/**
		 * @param $type
		 */
		public function set_passing_grade_type( $type ) {
			$this->_set_data( 'passing_grade_type', $type );
		}

		/**
		 * @return array|mixed
		 */
		public function get_passing_grade_type() {
			return $this->get_data( 'passing_grade_type' );
		}

		/**
		 * @param $value
		 */
		public function set_passing_grade( $value ) {
			$this->_set_data( 'passing_grade', $value );
		}

		/**
		 * @return array|mixed
		 */
		public function get_passing_grade() {
			$type  = $this->get_passing_grade_type();
			$value = $this->get_data( 'passing_grade' );

			switch ( $type ) {
				case 'point':
					break;
				case 'percentage':
				default:
					$value = "{$value}%";
					break;
			}

			return $value;
		}

		/**
		 * @param $value
		 */
		public function set_show_check_answer( $value ) {
			$this->_set_data( 'show_check_answer', $value );
		}

		/**
		 * @return array|mixed
		 */
		public function get_show_check_answer() {
			return $this->get_data( 'show_check_answer' );
		}

		/**
		 * @param $count
		 */
		public function set_count_check_answer( $count ) {
			$this->_set_data( 'count_check_answer', $count );
		}

		/**
		 * @return int
		 */
		public function get_check_answer_count() {
			return intval( $this->get_data( 'check_answer_count' ) );
		}

		/**
		 * @param $value
		 */
		public function set_show_hint( $value ) {
			$this->_set_data( 'show_hint', $value );
		}

		/**
		 * @return int
		 */
		public function get_show_hint() {
			return intval( $this->get_data( 'show_hint' ) );
		}

		/**
		 * @param $count
		 */
		public function set_count_hint( $count ) {
			$this->_set_data( 'count_hint', $count );
		}

		/**
		 * Return true if hint answer is enabled.
		 *
		 * @return bool
		 */
		public function enable_show_hint() {
			return apply_filters( 'learn-press/quiz/enable-show-hint', $this->get_data( 'show_hint' ) == 'yes', $this->get_id() );
		}

		/**
		 * @param $value
		 */
		public function set_archive_history( $value ) {
			$this->_set_data( 'archive_history', $value );
		}

		/**
		 * Return true if archive history is enabled.
		 *
		 * @return bool
		 */
		public function enable_archive_history() {
			return apply_filters( 'learn-press/quiz/enable-archive-history', $this->get_data( 'archive_history' ) == 'yes', $this->get_id() );
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
					if ( $question = LP_Question::get_question( $question_id ) ) {
						$mark += $question->get_mark();
					}
				}
				$this->_set_data( 'mark', $mark );
			}

			return apply_filters( 'learn-press/quiz-mark', $mark, $this->get_id() );
		}

		/**
		 * Get duration of quiz
		 *
		 * @return LP_Duration
		 */
		public function get_duration() {
			$duration = parent::get_duration();

			return apply_filters( 'learn-press/quiz-duration', $duration, $this->get_id() );
		}

		/**
		 * Get quiz questions.
		 *
		 * @param string $context
		 *
		 * @return mixed
		 */
		public function get_questions( $context = 'display' ) {

			if ( false === ( $questions = LP_Object_Cache::get( $this->get_id(), 'quiz-questions-' . $context ) ) ) {
				$questions = array();

				if ( $ids = $this->_curd->read_questions( $this->get_id(), $context ) ) {
					foreach ( $ids as $id ) {
						$questions[ $id ] = $id;
					}
				}

				LP_Object_Cache::set( $this->get_id(), $questions, 'quiz-questions-' . $context );
			}

			return apply_filters( 'learn-press/quiz/questions', $questions, $this->get_id(), $context );
		}

		/**
		 * Quiz editor get questions.
		 *
		 * @return mixed
		 */
		public function quiz_editor_get_questions() {
			// list questions
			$questions = $this->get_questions( 'edit' );
			// order questions in quiz
			$question_order = learn_press_quiz_get_questions_order( $questions );

			$result = array();
			if ( is_array( $questions ) ) {
				foreach ( $questions as $index => $id ) {

					$question = LP_Question::get_question( $id );

					$answers = array();
					// handle question answer
					if ( is_array( $question->get_data( 'answer_options' ) ) ) {
						foreach ( $question->get_data( 'answer_options' ) as $answer ) {
							$answers[] = $answer;
						}
					}

					$post     = get_post( $id );
					$result[] = apply_filters( 'learn-press/quiz-editor/question-data', array(
						'id'       => $id,
						'open'     => false,
						'title'    => $post->post_title,
						'type'     => array(
							'key'   => $question->get_type(),
							'label' => $question->get_type_label()
						),
						'answers'  => apply_filters( 'learn-press/quiz-editor/question-answers-data', $answers, $id, $this->get_id() ),
						'settings' => array(
							'content'     => $post->post_content,
							'mark'        => get_post_meta( $id, '_lp_mark', true ),
							'explanation' => get_post_meta( $id, '_lp_explanation', true ),
							'hint'        => get_post_meta( $id, '_lp_hint', true )
						),
						'order'    => $question_order[ $index ]
					), $id, $this->get_id() );
				}
			}

			return apply_filters( 'learn-press/quiz/quiz_editor_questions', $result, $this->get_id() );
		}

		/**
		 * Get quiz duration html.
		 *
		 * @return mixed
		 */
		public function get_duration_html() {
			$duration = $this->get_duration();
			if ( $duration ) {
				$duration = learn_press_seconds_to_time( $duration->get_seconds() );
			} else {
				$duration = __( 'Unlimited', 'learnpress' );
			}

			return apply_filters( 'learn_press_quiz_duration_html', $duration, $this );
		}

		/**
		 * Get number questions in quiz.
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

		/**
		 * Get all question's ids of the quiz.
		 *
		 * @param string $context
		 *
		 * @return int[]
		 * @since 3.2.0
		 *
		 */
		public function get_question_ids( $context = 'display' ) {

			if ( false === ( $ids = LP_Object_Cache::get( 'quiz-' . $this->get_id(), 'quiz-question-ids-' . $context ) ) ) {
				$ids = $this->_curd->read_question_ids( $this->get_id(), $context );
				LP_Object_Cache::set( 'quiz-' . $this->get_id(), $ids, 'quiz-question-ids-' . $context );
			}

			$ids = apply_filters( 'learn-press/quiz-question-ids', $ids, $this->get_id(), $this->get_course_id(), $context );

			return apply_filters( 'learn-press/quiz/get-question-ids', $ids, $this->get_id(), $this->get_course_id(), $context );
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
		 * Get js localize script in frontend. [NOT USED]
		 *
		 * @return mixed
		 */
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
					'title'   => __( 'Time\'s up!', 'learnpress' ),
					'message' => __( 'The time is up! Your quiz will automate come to finish', 'learnpress' )
				),
				'finished_quiz'       => __( 'Congrats! You have finished this quiz', 'learnpress' ),
				'retaken_quiz'        => __( 'Congrats! You have re-taken this quiz. Please wait a moment and the page will reload', 'learnpress' )
			);

			return apply_filters( 'learn_press_single_quiz_localize', $localize, $this );
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
		 * @param $feature
		 *
		 * @return mixed
		 * @throws Exception
		 */
		public function has( $feature ) {
			_deprecated_function( __FUNCTION__, '3.0.8' );

			$args = func_get_args();
			unset( $args[0] );
			$method   = 'has_' . preg_replace( '!-!', '_', $feature );
			$callback = array( $this, $method );
			if ( is_callable( $callback ) ) {
				return call_user_func_array( $callback, $args );
			} else {
				throw new Exception( sprintf( __( 'The function %s doesn\'t exist', 'learnpress' ), $feature ) );
			}
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
			$course    = LP_Global::course();
			$permalink = $course->get_item_link( $this->get_id() );
			if ( '' != get_option( 'permalink_structure' ) && get_post_status( $this->get_id() ) != 'draft' ) {
				if ( get_post_type( $question_id ) === LP_QUESTION_CPT ) {
					$question_name = get_post_field( 'post_name', $question_id );
					preg_match( '/\?/i', $permalink, $result );
					if ( empty( $result ) ) {
						$permalink = $permalink . $question_name;
					} else {
						$permalink = preg_replace( '/\?/i', '/' . $question_name . '/?', $permalink );
					}
				}
			} else {
				$permalink = add_query_arg( array( 'question', $question_id ), $permalink );
			}

			// @deprecated
			$permalink = apply_filters( 'learn_press_quiz_question_permalink', $permalink, $question_id, $this );

			return apply_filters( 'learn-press/quiz/question-permalink', $permalink, $question_id, $this->get_id() );
		}

		/**
		 * @param int $at
		 *
		 * @return bool
		 */
		public function get_question_at( $at = 0 ) {
			if ( $questions = $this->get_questions() ) {
				$questions = array_values( $questions );

				return @$questions[ $at ];
			}

			return false;
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
			$index = 0;

			if ( ( $questions = $this->get_questions() ) ) {
				$questions = array_values( $questions );
				$index     = array_search( $id, $questions );
			}

			return apply_filters( 'learn-press/quiz/question-index', intval( $start ) + $index, $this->get_id() );
		}

		/**
		 * Check question.
		 *
		 * @param $question_id
		 * @param $user_id
		 *
		 * @return bool
		 */
		public function check_question( $question_id, $user_id ) {

			if ( ! $question = LP_Question::get_question( $question_id ) ) {
				return false;
			}

			$user = learn_press_get_user( $user_id );

			$history = $user->get_quiz_results( $this->get_id() );
			if ( ! $history ) {
				return false;
			}
			$checked = array();// learn_press_get_user_quiz_meta( $history->history_id, 'checked' );
			$checked = array_filter( $checked );
			if ( ! in_array( $question_id, $checked ) ) {
				$checked[] = $question_id;
			}

			//learn_press_update_user_quiz_meta( $history->history_id, 'checked', $checked );

			return true;

		}

		/**
		 * Get question position in quiz.
		 *
		 * @param     $question
		 * @param int $user_id
		 *
		 * @return false|int|string
		 */
		public function get_question_position( $question, $user_id = 0 ) {
			if ( ! $user_id ) {
				$user_id = learn_press_get_current_user_id();
			}
			$user = learn_press_get_user( $user_id );
			if ( $user && ( $results = $user->get_quiz_results( $this->get_id(), '', '' ) ) ) {
				$questions = (array) $results->questions;

			} else {
				$questions = (array) $this->get_questions();
				$questions = array_keys( $questions );
			}
			$position = array_search( $question, $questions );

			return $position;
		}

		/**
		 * @param int $user_id
		 * @param int $course_id
		 *
		 * @return bool|LP_Question
		 */
		public function get_current_question( $user_id = 0, $course_id = 0 ) {
			$user = learn_press_get_user( $user_id );
			$id   = $user->get_current_quiz_question( $this->get_id(), $course_id );

			return LP_Question::get_question( $id );
		}

		/**
		 * @param string $return
		 *
		 * @return LP_Question|int
		 */
		public function get_viewing_question( $return = '' ) {
			global $lp_quiz_question;

			return $return !== 'id' ? $lp_quiz_question : ( $lp_quiz_question ? $lp_quiz_question->get_id() : 0 );
		}

		/**
		 * @param mixed $the_quiz
		 * @param array $args
		 *
		 * @return LP_Quiz|bool
		 */
		public static function get_quiz( $the_quiz = false, $args = array() ) {

			if ( is_numeric( $the_quiz ) && isset( LP_Global::$quizzes[ $the_quiz ] ) ) {
				return LP_Global::$quizzes[ $the_quiz ];
			}

			$the_quiz = self::get_quiz_object( $the_quiz );

			if ( ! $the_quiz ) {
				return false;
			}

			if ( isset( LP_Global::$quizzes[ $the_quiz->ID ] ) ) {
				return LP_Global::$quizzes[ $the_quiz->ID ];
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
				LP_Global::$quizzes[ $key ]          = false;
				LP_Global::$quizzes[ $the_quiz->ID ] = false;
			}

			if ( empty( LP_Global::$quizzes[ $key ] ) ) {
				$class_name = self::get_quiz_class( $the_quiz, $args );
				if ( is_string( $class_name ) && class_exists( $class_name ) ) {
					$quiz = new $class_name( $the_quiz->ID, $args );
				} elseif ( $class_name instanceof LP_Course_Item ) {
					$quiz = $class_name;
				} else {
					$quiz = new self( $the_quiz->ID, $args );
				}
				LP_Global::$quizzes[ $key ]          = $quiz;
				LP_Global::$quizzes[ $the_quiz->ID ] = $quiz;
			}

			return LP_Global::$quizzes[ $key ];
		}

		/**
		 * @param string $quiz_type
		 *
		 * @return string|false
		 */
		private static function get_class_name_from_quiz_type( $quiz_type ) {
			return LP_QUIZ_CPT === $quiz_type ? __CLASS__ : 'LP_Quiz_' . implode( '_', array_map( 'ucfirst', explode( '-', $quiz_type ) ) );
		}

		/**
		 * Get the lesson class name
		 *
		 * @param WP_Post $the_quiz
		 * @param array   $args (default: array())
		 *
		 * @return string
		 */
		private static function get_quiz_class( $the_quiz, $args = array() ) {
			$quiz_id = absint( $the_quiz->ID );
			$type    = $the_quiz->post_type;

			$class_name = self::get_class_name_from_quiz_type( $type );

			// Filter class name so that the class can be overridden if extended.
			return apply_filters( 'learn-press/quiz/object-class', $class_name, $type, $quiz_id );
		}

		/**
		 * Get the quiz object
		 *
		 * @param mixed $the_quiz
		 *
		 * @return WP_Post|bool false on failure
		 * @uses   WP_Post
		 */
		private static function get_quiz_object( $the_quiz ) {
			if ( false === $the_quiz ) {
				$the_quiz = get_post_type() === LP_QUIZ_CPT ? $GLOBALS['post'] : false;
			} elseif ( is_numeric( $the_quiz ) ) {
				$the_quiz = get_post( $the_quiz );
			} elseif ( $the_quiz instanceof LP_Course_Item ) {
				$the_quiz = get_post( $the_quiz->get_id() );
			} elseif ( ! ( $the_quiz instanceof WP_Post ) ) {
				$the_quiz = false;
			}

			return apply_filters( 'learn-press/quiz/post-object', $the_quiz );
		}

		public function set_show_hide_question( $show_or_hide ) {
			$this->_set_data( 'show_hide_question', $show_or_hide );
		}

		public function set_preview( $preview ) {
			$this->_set_data( 'preview', $preview );
		}

		public function set_minus_points( $points ) {
			$this->_set_data( 'minus_points', $points );
		}

		public function set_minus_skip_questions( $skip ) {
			$this->_set_data( 'minus_skip_questions', $skip );
		}

		public function get_preview() {
			return 'yes' === $this->get_data( 'preview' );
		}

		public function get_show_hide_question() {
			return 'yes' === $this->get_data( 'show_hide_question' );
		}

		public function get_minus_points() {
			return $this->get_data( 'minus_points' );
		}

		public function get_minus_skip_questions() {
			return 'yes' === $this->get_data( 'minus_skip_questions' );
		}

		/**
		 * Get css classes of question displays in a list.
		 *
		 * @param int  $question_id
		 * @param null $position
		 *
		 * @return array
		 */
		public function get_question_number_class( $question_id, $position = null ) {

			if ( null === $position ) {
				$position = $this->get_question_index( $question_id );
			}

			$class = array( "question-" . $position );

			if ( $this->is_viewing_question( $question_id ) ) {
				$class[] = 'current';
			}

			$user = learn_press_get_current_user();

			if ( $quiz_data = $user->get_quiz_data( $this->get_id() ) ) {
				if ( $quiz_data->is_skipped( $question_id ) ) {
					$class[] = 'skipped';
				}
			}

			return $class;
		}

		/**
		 * Implement ArrayAccess functions.
		 *
		 * @param mixed $offset
		 * @param mixed $value
		 */
		public function offsetSet( $offset, $value ) {
			// Do not allow to set value directly!
		}

		/**
		 * @param mixed $offset
		 */
		public function offsetUnset( $offset ) {
			// Do not allow to unset value directly!
		}

		/**
		 * @param mixed $offset
		 *
		 * @return bool|mixed
		 */
		public function offsetGet( $offset ) {
			return $this->offsetExists( $offset ) ? $this->_questions[ $offset ] : false;
		}

		/**
		 * @param mixed $offset
		 *
		 * @return bool
		 */
		public function offsetExists( $offset ) {
			return array_key_exists( $offset, $this->_questions );
		}
	}
}