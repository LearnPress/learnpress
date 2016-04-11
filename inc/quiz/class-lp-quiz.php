<?php

/**
 * Class LP_Quiz
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Quiz {
	/**
	 * The quiz (post) ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * $post Stores post data
	 *
	 * @var $post WP_Post
	 */
	public $post = null;

	/**
	 * WP_Course object
	 *
	 * @var null
	 */
	public $course = null;

	/**
	 * @var array
	 */
	static protected $_meta = array();

	/**
	 * Constructor gets the post object and sets the ID for the loaded course.
	 *
	 * @param int|LP_Quiz|object $quiz Quiz ID, post object, or course object
	 */
	public function __construct( $quiz ) {
		if ( empty( $GLOBALS['learnpress_question_answers'] ) ) {
			$GLOBALS['learnpress_question_answers'] = array();
		}
		if ( is_numeric( $quiz ) ) {
			$this->id   = absint( $quiz );
			$this->post = get_post( $this->id );
		} elseif ( $quiz instanceof LP_Quiz ) {
			$this->id   = absint( $quiz->id );
			$this->post = $quiz->post;
		} elseif ( isset( $quiz->ID ) ) {
			$this->id   = absint( $quiz->ID );
			$this->post = $quiz;
		}
		if ( empty( self::$_meta[$this->id] ) ) {
			self::$_meta[$this->id] = array();
		}
		$this->course = LP_Course::get_course( $this->_lpr_course );
		$this->_init();
	}

	protected function _init() {

		add_action( 'wp_head', array( $this, 'frontend_assets' ) );
	}

	function get_duration_html( $format = 'h:i:s' ) {
		$duration = $this->duration;
		if ( $duration ) {
			$duration = date( $format, $duration );
		} else {
			$duration = __( 'Unlimited', 'learnpress' );
		}
		return apply_filters( 'learn_press_quiz_duration', $duration, $this );
	}

	function get_total_questions() {
		$questions = $this->questions;
		$count     = 0;
		if ( $questions ) {
			$count = count( $questions );
		}
		return $count;
	}

	function get_localize() {
		$localize = array(
			'confirm_finish_quiz' => array(
				'title'   => __( 'Finish quiz', 'learnpress' ),
				'message' => __( 'Are you sure you want to completely finish this quiz?', 'learnpress' )
			),
			'confirm_retake_quiz' => array(
				'title'   => __( 'Retake quiz', 'learnpress' ),
				'message' => __( 'Are you sure you want to retake this quiz?', 'learnpress' )
			),
			'quiz_time_is_over'   => array(
				'title'   => __( 'Time over' ),
				'message' => __( 'The time is over! Your quiz will automate come to finish', 'learnpress' )
			),
			'finished_quiz'       => __( 'Congrats! You have finished this quiz', 'learnpress' ),
			'retaken_quiz'        => __( 'Congrats! You have re-taken this quiz. Please wait a moment and the page will reload', 'learnpress' )
		);
		return apply_filters( 'learn_press_single_quiz_localize', $localize, $this );
	}

	function get_settings() {
		if ( empty( self::$_meta[$this->id]['single_quiz_params'] ) ) {
			$user = learn_press_get_current_user();

			if ( $results = $user->get_quiz_results( $this->id ) ) {
				$questions = $results->questions;
			} else {
				$questions = learn_press_get_quiz_questions();
				$questions = array_keys( $questions );
			}
			$current_question_id = !empty( $_REQUEST['question'] ) ? intval( $_REQUEST['question'] ) : learn_press_get_current_question();// !empty( $_REQUEST['question_id'] ) ? intval( $_REQUEST['question_id'] ) : 0;

			/*if ( $questions ) {
				$question_ids = array_keys( $questions );
			} else {
				$question_ids = array();
			}*/
			if ( !$current_question_id || !in_array( $current_question_id, $questions ) ) {
				$current_question_id = reset( $questions );
			}

			$current_question_id = absint( $current_question_id );
			$question            = LP_Question_Factory::get_question( $current_question_id );

			$js                                           = array(
				'time_format'    => $this->duration >= 300 ? 'h%:m%:s%' : 'm%:s%',
				'total_time'     => $this->duration,
				'id'             => $this->id,
				'questions'      => array_values( $this->get_question_params( $questions ) ),//$questions,
				'question_id'    => $current_question_id,
				'status'         => $user->get_quiz_status( $this->id ),
				'time_remaining' => ( $time_remaining = $user->get_quiz_time_remaining( $this->id ) ) !== false && !in_array( $user->get_quiz_status( $this->id ), array( '', 'completed' ) ) ? $time_remaining : $this->duration,
				'permalink'      => get_the_permalink(),
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'user_id'        => $user->id,
				'nonce'          => wp_create_nonce( 'learn-press-quiz-action-' . $this->id . '-' . $user->id ),
				'question'       => $question ? array( 'check_answer' => $question->can_check_answer() ) : false
			);
			self::$_meta[$this->id]['single_quiz_params'] = $js;
		}
		return apply_filters( 'learn_press_single_quiz_params', self::$_meta[$this->id]['single_quiz_params'], $this );
	}

	function get_question_params( $ids ) {
		global $wpdb;
		$query   = $wpdb->prepare( "
			SELECT qa.question_answer_id, ID as id, pm.meta_value as type, qa.answer_data as answer_data
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			INNER JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON qq.question_id = p.ID
			RIGHT JOIN {$wpdb->prefix}learnpress_question_answers qa ON qa.question_id = p.ID
			WHERE ID IN(" . join( ',', $ids ) . ")
			AND post_type = %s
			ORDER BY qq.question_order, answer_order ASC
		", '_lp_type', 'lp_question' );
		$results = array();
		if ( $_results = $wpdb->get_results( $query, OBJECT_K ) ) {
			$user              = learn_press_get_current_user();
			$show_check_answer = $this->show_check_answer;
			$show_hint         = $this->show_hint;
			$show_explanation  = $this->show_explanation;
			if ( $show_check_answer == 'yes' ) {
				if ( $history = $user->get_quiz_results( $this->id ) ) {
					$checked_answers = !empty( $history->checked ) ? (array) $history->checked : array();
				} else {
					$checked_answers = array();
				}
			}
			foreach ( $_results as $k => $row ) {
				if ( empty( $results[$row->id] ) ) {
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
				}

				if ( $show_check_answer != 'yes' ) {
					continue;
				}
				if ( in_array( $row->id, $checked_answers ) ) {
					$checked = maybe_unserialize( $row->answer_data );
					unset( $checked['text'] );
					$results[$row->id]->checked[$row->question_answer_id] = $checked;
				} else {
					$results[$row->id]->checked = false;
				}
			}
		}
		return apply_filters( 'learn_press_quiz_param_questions', $results, $this->id );
	}

	function frontend_assets() {
		if ( learn_press_is_quiz() && get_the_ID() == $this->id ) {
			$translate = $this->get_localize();
			LP_Assets::add_localize( $translate, false, 'single-quiz' );
			LP_Assets::add_param( $this->get_settings(), false, 'single-quiz' );
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
		return metadata_exists( 'post', $this->id, '_' . $key );
	}

	/**
	 * __get function.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( isset( $this->{$key} ) ) {
			return $this->{$key};
		}
		$value = null;
		switch ( $key ) {
			case 'current_question':

				if ( ( $question = learn_press_get_request( 'question' ) ) && learn_press_is_quiz() ) {
					$value = LP_Question_Factory::get_question( $question );
				}
				break;
			case 'questions':
				$value = $this->get_questions();
				break;
			case 'permalink':
				$value = get_the_permalink( $this->id );
				break;
			default:
				if ( array_key_exists( $key, self::$_meta[$this->id] ) ) {
					$value = self::$_meta[$this->id][$key];
				} else {
					if ( strpos( $key, '_lp_' ) === false ) {
						$key = '_lp_' . $key;
					}
					$value = get_post_meta( $this->id, $key, true );
					if ( $key == '_lp_duration' ) {
						$value = absint( $value ) * 60;
					}
					self::$_meta[$this->id][$key] = $value;
				}
		}
		if ( !empty( $value ) ) {
			$this->$key = $value;
		}
		return $value;
	}

	/**
	 * Get the quiz's post data.
	 *
	 * @return object
	 */
	public function get_quiz_data() {
		return $this->post;
	}

	function is_require_enrollment() {
		return $this->course && $this->course->is_require_enrollment();
	}

	function get_course() {
		if ( empty( $this->course ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "
				SELECT c.*
				FROM {$wpdb->posts} c
				INNER JOIN {$wpdb->learnpress_sections} s on c.ID = s.section_course_id
				INNER JOIN {$wpdb->learnpress_section_items} si on si.section_id = s.section_id AND si.item_id = %d
				", $this->id );
			if ( $course_id = $wpdb->get_var( $query ) ) {
				$this->course = LP_Course::get_course( $course_id );
			}
		}
		return $this->course;
	}

	function get_user_id() {
		if ( empty( $this->user_id ) ) {
			$user_id = get_current_user_id();
			if ( !$user_id ) {
				if ( !$this->is_require_enrollment() ) {
					$user_id = $_SESSION['learn_press_temp_user_id'];
				}
			}
			$this->user_id = $user_id;
		}
		return $this->user_id;
	}

	/**
	 * @param bool  $the_quiz
	 * @param array $args
	 *
	 * @return bool
	 */
	public static function get_quiz( $the_quiz = false, $args = array() ) {
		//$the_quiz = self::get_quiz_object( $the_quiz );
		if ( !$the_quiz ) {
			return false;
		}

		return new LP_Quiz( $the_quiz, $args );
	}

	function get_quiz_result( $quiz_id = null ) {
		return false;
	}

	function get_questions( $force = false ) {
		if ( !array_key_exists( 'questions', self::$_meta[$this->id] ) || $force ) {
			self::$_meta[$this->id]['questions'] = array();
			global $wpdb;
			$query = $wpdb->prepare( "
					SELECT q.*, qq.params
					FROM {$wpdb->posts} q
					INNER JOIN {$wpdb->learnpress_quiz_questions} qq ON qq.question_id = q.ID
					AND q.post_type = %s
					AND qq.quiz_id = %d
				", LP()->question_post_type, $this->id );

			if ( $this_questions = $wpdb->get_results( $query, OBJECT_K ) ) {
				foreach ( $this_questions as $id => $question ) {
					$question->params                            = maybe_unserialize( $question->params );
					self::$_meta[$this->id]['questions'][$id]    = $question;
					$GLOBALS['learnpress_question_answers'][$id] = array();
				}
				if ( $answers = $wpdb->get_results( "
					SELECT *
					FROM {$wpdb->learnpress_question_answers}
					WHERE question_id IN(" . join( ',', array_keys( $this_questions ) ) . ")
					ORDER BY question_id, answer_order ASC
				" )
				) {

					foreach ( $answers as $answer ) {
						$GLOBALS['learnpress_question_answers'][$answer->question_id][$answer->question_answer_id]       = (array) maybe_unserialize( $answer->answer_data );
						$GLOBALS['learnpress_question_answers'][$answer->question_id][$answer->question_answer_id]['id'] = $answer->question_answer_id;
					}
					//print_r($GLOBALS['learnpress_question_answers']);
				}
			}
		} else {
			$this_questions = self::$_meta[$this->id]['questions'];
		}
		return apply_filters( 'learn_press_quiz_questions', $this_questions, $this->id, $force );
	}

	function get_buttons() {

	}

	function has( $feature ) {
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

	function has_questions() {
		return $this->get_questions();
	}

	function has_question( $question_id ) {
		return is_array( $this->questions ) && isset( $this->questions[$question_id] );
	}

	function get_mark( $force = false ) {
		if ( !self::$_meta[$this->id] ) {
			self::$_meta[$this->id] = array();
		}
		if ( empty( self::$_meta[$this->id]['mark'] ) || $force ) {
			global $wpdb;
			$query                          = $wpdb->prepare( "
				SELECT SUM(pm.meta_value) as mark
				FROM {$wpdb->learnpress_quiz_questions} qq
				INNER JOIN {$wpdb->posts} q ON q.ID = qq.quiz_id
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = qq.question_id AND pm.meta_key = %s
				WHERE q.ID = %d
			", '_lp_mark', $this->id );
			self::$_meta[$this->id]['mark'] = $wpdb->get_var( $query );
		}
		return apply_filters( 'learn_press_quiz_mark', self::$_meta[$this->id]['mark'], $this );
	}

	function get_question_link( $question_id = null ) {
		return apply_filters( 'learn_press_quiz_question_permalink', get_the_permalink( $this->id ) . get_post_field( 'post_name', $question_id ), $question_id, $this );
	}

	function get_question_param( $name, $id ) {
		if ( $this->questions ) {
			if ( !empty( $this->questions[$id] ) ) {
				return !empty( $this->questions[$id]->params[$name] ) ? $this->questions[$id]->params[$name] : null;
			}
		}
		return false;
	}

	function check_question( $question_id, $user_id ) {

		if ( !$question = LP_Question_Factory::get_question( $question_id ) ) {
			return false;
		}

		$user = learn_press_get_user( $user_id );

		$history = $user->get_quiz_results( $this->id );
		if ( !$history ) {
			return false;
		}
		$checked = (array) learn_press_get_user_quiz_meta( $history->history_id, 'checked' );
		$checked = array_filter( $checked );
		if ( !in_array( $question_id, $checked ) ) {
			$checked[] = $question_id;
		}

		learn_press_update_user_quiz_meta( $history->history_id, 'checked', $checked );

	}

	function get_question_position( $question, $user_id = 0 ) {
		if ( !$user_id ) {
			$user_id = learn_press_get_current_user_id();
		}
		$user = learn_press_get_user( $user_id );
		if ( $user && $results = $user->get_quiz_results( $this->id ) ) {
			$questions = (array) $results->questions;
		} else {
			$questions = (array) $this->get_questions();
			$questions = array_keys( $questions );
		}
		$position = array_search( $question, $questions );
		return $position;
	}

	/**
	 * Get the quiz object
	 *
	 * @param  mixed $the_quiz
	 *
	 * @uses   WP_Post
	 * @return WP_Post|bool false on failure
	 */
	private static function get_quiz_object( $the_quiz ) {
		if ( false === $the_quiz ) {
			$the_quiz = $GLOBALS['post'];
		} elseif ( is_numeric( $the_quiz ) ) {
			$the_quiz = get_post( $the_quiz );
		} elseif ( $the_quiz instanceof LP_Quiz ) {
			$the_quiz = get_post( $the_quiz->id );
		} elseif ( !( $the_quiz instanceof WP_Post ) ) {
			$the_quiz = false;
		}

		return apply_filters( 'learn_press_quiz_object', $the_quiz );
	}
}