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
	 * @var array
	 */
	protected $_courses = array();

	/**
	 * @var array
	 */
	protected $_course_items = array();

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
		add_action( 'pre_get_posts', array( $this, 'parse_query' ), 50 );
	}

	public function _course_items_callback_filter( $a ) {
		return $a == 'lp_lesson';
	}

	public function parse_query( $q ) {
		global $post;
		if ( is_single() && get_post_type() == 'lp_course' ) {
			$this->set_course( $post->ID );
		}
	}

	public function get_course_items( $type = '', $field = null, $course_id = 0 ) {
		if ( $course_id ) {
			$this->set_course( $course_id );
		}
		if ( !$type ) {
			$items = $this->_course_items;
		} else {
			$items = array_filter( $this->_course_items, create_function( '$a', 'return $a == \'' . $type . '\';' ) );
		}
		if ( $field == 'key' ) {
			$items = array_keys( $items );
		} elseif ( $field == 'value' ) {
			$items = array_values( $items );
		}
		return $items;
	}

	public function set_course( $the_course = 0 ) {
		if ( !$the_course && get_post_type() == 'lp_course' ) {
			$the_course = get_the_ID();
		}

		$course = learn_press_get_course( $the_course );
		if ( !$course || !$course->id ) {
			return;
		}
		if ( !empty( $this->_courses[$course->id] ) ) {
			return $this->_courses[$course->id];
		}
		$this->_courses[$course->id] = $course;

		if ( $course_items = $course->get_curriculum_items() ) {
			foreach ( $course_items as $item ) {
				$this->_course_items[$item->ID] = $item->post_type;
			}
		} else {
			return;
		}
		return;
		if ( !$this->_course_items ) {
			return;
		}

		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT ui.*
			FROM {$wpdb->prefix}learnpress_user_items ui
			LEFT JOIN {$wpdb->posts} p ON p.ID = ui.item_id
			WHERE p.ID IN(" . join( ',', $course_items ) . ")
			AND ui.user_id = %d
			AND ui.ref_id = %d
		", $this->id, $course->id );
		if ( $items = $wpdb->get_results( $query, OBJECT_K ) ) {
			foreach ( $items as $item ) {

			}
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

	private function _get_course_id( $course_id ) {
		if ( !$course_id && learn_press_is_course() ) {
			$course_id = get_the_ID();
		}
		return $course_id;
	}

	private function _create_quiz_history( $quiz_id, $course_id = 0/* added 1.0.4 */ ) {
		if ( !$course_id ) {
			$quiz = LP_Quiz::get_quiz( $quiz_id );
			if ( $quiz && $quiz->id ) {
				$course_id = $quiz->get_course( array( 'field' => 'id' ) );
			}
		}
		if ( !$course_id ) {
			throw new Exception( __( 'Create quiz history need pass course ID', 'learnpress' ) );
		}
		if ( empty( $this->_quiz_history_id ) ) {
			global $wpdb;

			$timestamp = current_time( 'timestamp' );

			$wpdb->insert(
				$wpdb->prefix . 'learnpress_user_items',
				apply_filters( 'learn_press_user_quiz_history_data', array(
					'user_id'    => $this->id,
					'item_id'    => $quiz_id,
					'item_type'  => 'lp_quiz',
					'ref_id'     => $course_id,
					'ref_type'   => get_post_type( $course_id ),
					'start_time' => current_time( 'mysql' ),
					'status'     => ''
				), $this->id, $quiz_id, $course_id ),
				array( '%d', '%d', '%s', '%d', '%s', '%s', '%s' )
			);

			$user_quiz_id = $wpdb->insert_id;

			/**
			 * Added 1.0.4
			 */
			@$wpdb->update(
				$wpdb->prefix . 'learnpress_user_items', //quizzes,
				array(
					'ref_id' => $course_id
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
			//$user_quiz_data = $this->get_quiz_history( $quiz_id, $this->_quiz_history_id );
		}
		return $user_quiz_data;
	}

	/**
	 * Start quiz for the user
	 *
	 * @param int $quiz_id
	 * @param int $course_id
	 *
	 * @throws Exception
	 * @return array|void
	 */
	public function start_quiz( $quiz_id, $course_id = 0 ) {
		$course_id = $this->_get_course_id( $course_id );
		if ( !apply_filters( 'learn_press_before_user_start_quiz', true, $quiz_id, $course_id, $this->id ) ) {
			return false;
		}
		$history = $this->get_quiz_history( $quiz_id, $course_id, null, true );
		if ( !$history ) {
			$return = learn_press_update_user_item_field(
				array(
					'status'     => 'started',
					'start_time' => current_time( 'mysql' ),
					'item_id'    => $quiz_id,
					'ref_id'     => $course_id,
					'user_id'    => $this->id,
					'item_type'  => LP_QUIZ_CPT,
					'ref_type'   => LP_COURSE_CPT,
					'parent_id'  => $this->get_course_history_id( $course_id )
				)
			);
		} else {
			$return = learn_press_update_user_item_field(
				array(
					'status'     => 'started',
					'start_time' => current_time( 'mysql' )
				),
				array( 'item_id' => $quiz_id, 'ref_id' => $course_id )
			);
		}
		if ( $return ) {
			$item = learn_press_get_user_item(
				array( 'item_id' => $quiz_id, 'ref_id' => $course_id, 'user_id' => $this->id )
			);
		} else {
			$item = false;
		}

		do_action( 'learn_press_user_start_quiz', $item, $quiz_id, $course_id, $this->id );
		return $item;
	}

	public function get_quiz_time_remaining( $quiz_id, $course_id = 0 ) {
		$course_id = $this->_get_course_id( $course_id );
		$remaining = false;
		if ( $progress = $this->get_quiz_progress( $quiz_id, $course_id ) ) {
			if ( $progress->status != 'completed' ) {
				$quiz      = LP_Quiz::get_quiz( $quiz_id );
				$remaining = $quiz->duration + strtotime( $progress->start ) - current_time( 'timestamp' );
			}
			//if ( $remaining < 0 ) $remaining = 0;
		}
		return apply_filters( 'learn_press_user_quiz_time_remaining', $remaining, $quiz_id, $course_id, $this->id );
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

	public function get_question_answers( $question_id, $quiz_id, $course_id = 0 ) {
		$course_id = $this->_get_course_id( $course_id );

		$progress = $this->get_quiz_progress( $quiz_id, $course_id );

		$question_answers = null;
		if ( $progress ) {
			$answers = (array) $progress->quiz_question_answers;
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
	 * @param int $quiz_id
	 * @param int $course_id
	 * @param     mixed Extra custom fields
	 *
	 * @return mixed
	 */
	public function finish_quiz( $quiz_id, $course_id, $args = '' ) {
		$course_id = $this->_get_course_id( $course_id );

		$quiz = LP_Quiz::get_quiz( $quiz_id );
		if ( !$quiz ) {
			return;
		}
		$return   = false;
		$progress = $this->get_quiz_results( $quiz->id, $course_id );
		if ( $progress ) {
			$start_time = strtotime( $progress->start );
			// Maximum time spend to do quiz if it finish automatically
			if ( is_array( $args ) && !empty( $args['auto_finish'] ) ) {
				$time = $start_time + $quiz->duration;
			} else {
				$time = current_time( 'timestamp' );
				if ( $time > $start_time + $quiz->duration ) {
					$time = $start_time + $quiz->duration;
				}
			}
			$updated = learn_press_update_user_item_field(
				array(
					'status'   => 'completed',
					'end_time' => date( 'Y-m-d H:i:s', $time )
				),
				array(
					'user_item_id' => $progress->history_id
				)
			);
			if ( $updated ) {
				if ( is_array( $args ) ) {
					foreach ( $args as $k => $v ) {
						learn_press_update_user_item_meta( $progress->history_id, $k, $v );
					}
				}
				$return = $this->get_quiz_results( $quiz_id, $course_id, true );
			}
		}

		do_action( 'learn_press_user_finish_quiz', $quiz_id, $this->id );
		return $return;
	}

	/**
	 * Retake a quiz for the user
	 *
	 * @param int $quiz_id
	 * @param int $course_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function retake_quiz( $quiz_id, $course_id ) {
		$course_id = $this->_get_course_id( $course_id );

		$response = false;
		$return   = learn_press_update_user_item_field(
			array(
				'user_id'    => learn_press_get_current_user_id(),
				'item_id'    => $quiz_id,
				'ref_id'     => $course_id,
				'start_time' => current_time( 'mysql' ),
				'item_type'  => 'lp_quiz',
				'status'     => 'started',
				'ref_type'   => 'lp_course',
				'parent_id'  => $this->get_course_history_id( $course_id )
			)
		);
		if ( $return ) {
			$quiz      = LP_Quiz::get_quiz( $quiz_id );
			$questions = $quiz->questions;
			if ( is_array( $questions ) ) {
				$questions = array_keys( $questions );
				$question  = reset( $questions );
			} else {
				$question = 0;
			}
			// Update user quiz meta data
			learn_press_update_user_item_meta( $return, 'questions', $questions );
			learn_press_update_user_item_meta( $return, 'current_question', $question );
			learn_press_update_user_item_meta( $return, 'question_answers', array() );


			$response = $this->get_quiz_results( $quiz_id, $course_id, true );
		}
		do_action( 'learn_press_user_retake_quiz', $response, $quiz_id, $this->id );
		return $response;
	}

	/**
	 * Get quiz status for the user
	 *
	 * @param int     $quiz_id
	 * @param int     $course_id
	 * @param boolean $force
	 *
	 * @return mixed
	 */
	public function get_quiz_status( $quiz_id, $course_id = 0, $force = false ) {

		return $this->get_item_status( $quiz_id, $course_id, $force );
		global $wpdb;
		if ( !$course_id ) {
			$course_id = get_the_ID();
		}
		$cached = LP_Cache::get_quiz_status( false, array() );// (array) wp_cache_get( 'user-quiz-statuses', 'learnpress' );
		if ( ( !array_key_exists( $this->id . '-' . $course_id . '-' . $quiz_id, $cached ) || $force ) && $quiz_ids = $this->get_course_items( 'lp_quiz', 'key' ) ) {

			$in    = array_fill( 0, sizeof( $quiz_ids ), '%d' );
			$args  = array_merge(
				array( $this->id, $course_id ),
				$quiz_ids
			);
			$query = $wpdb->prepare( "
				SELECT item_id as id, `status`
				FROM {$wpdb->prefix}learnpress_user_items
				WHERE user_id = %d
				AND ref_id = %d
				AND item_id IN(" . join( ',', $in ) . ")
				ORDER BY user_item_id ASC
			", $args );
			if ( $items = $wpdb->get_results( $query ) ) {
				$started = array();
				foreach ( $items as $item ) {
					$cached[$this->id . '-' . $course_id . '-' . $item->id] = $item->status;
					$started[]                                              = $item->id;
				}
				$quiz_ids = array_diff( $quiz_ids, $started );
			}
			if ( $quiz_ids ) foreach ( $quiz_ids as $id ) {
				$cached[$this->id . '-' . $course_id . '-' . $id] = '';
			}
			//wp_cache_set( 'user-quiz-statuses', $cached, 'learnpress' );
			LP_Cache::set_quiz_status( $this->id . '-' . $course_id . '-' . $quiz_id, $cached[$this->id . '-' . $course_id . '-' . $quiz_id] );
		}

		//learn_press_debug( __FILE__, current_filter(), get_the_ID(), $cached, $quiz_ids );
		$status = !empty( $cached[$this->id . '-' . $course_id . '-' . $quiz_id] ) ? $cached[$this->id . '-' . $course_id . '-' . $quiz_id] : false;
		return apply_filters( 'learn_press_user_quiz_status', $status, $quiz_id, $this->id, $course_id );
	}

	/**
	 * Get quiz status for the user
	 *
	 * @param int     $lesson_id
	 * @param int     $course_id
	 * @param boolean $force
	 *
	 * @return mixed
	 */
	public function get_lesson_status( $lesson_id, $course_id = 0, $force = false ) {
		return $this->get_item_status( $lesson_id, $course_id, $force );
		global $wpdb;
		if ( !$course_id ) {
			$course_id = get_the_ID();
		}
		$cached = (array) wp_cache_get( 'user-lesson-statuses', 'learnpress' );
		if ( ( !array_key_exists( $this->id . '-' . $course_id . '-' . $lesson_id, $cached ) || $force ) && $lesson_ids = $this->get_course_items( 'lp_lesson', 'key' ) ) {

			$in    = array_fill( 0, sizeof( $lesson_ids ), '%d' );
			$args  = array_merge(
				array( $this->id, $course_id ),
				$lesson_ids
			);
			$query = $wpdb->prepare( "
				SELECT item_id as id, `status`
				FROM {$wpdb->prefix}learnpress_user_items
				WHERE user_id = %d
				AND ref_id = %d
				AND item_id IN(" . join( ',', $in ) . ")
				ORDER BY user_item_id ASC
			", $args );
			if ( $items = $wpdb->get_results( $query ) ) {
				$started = array();
				foreach ( $items as $item ) {
					$cached[$this->id . '-' . $course_id . '-' . $item->id] = $item->status;
					$started[]                                              = $item->id;
				}
				$lesson_ids = array_diff( $lesson_ids, $started );
			}
			if ( $lesson_ids ) foreach ( $lesson_ids as $id ) {
				$cached[$this->id . '-' . $course_id . '-' . $id] = '';
			}
			wp_cache_set( 'user-lesson-statuses', $cached, 'learnpress' );
			//learn_press_debug( __FILE__, $query, $cached );
		}

		return apply_filters( 'learn_press_user_lesson_status', $cached[$this->id . '-' . $course_id . '-' . $lesson_id], $lesson_id, $this->id, $course_id );
	}

	public function get_item_status( $item_id, $course_id = 0, $force = false ) {
		$course_id = $this->_get_course_id( $course_id );

		_learn_press_parse_user_item_statuses( $this->id, $course_id );

		// Force to update new course data
		if ( $force ) {
			$this->set_course( $course_id );
		}

		$item_statuses = LP_Cache::get_item_statuses( false, array() );
		$key           = sprintf( '%d-%d-%d', $this->id, $course_id, $item_id );
		if ( ( !array_key_exists( $key, $item_statuses ) || $force ) ) {
			if ( $item_ids = $this->get_course_items( '', 'key', $course_id ) ) {
				global $wpdb;
				$in   = array_fill( 0, sizeof( $item_ids ), '%d' );
				$args = array_merge(
					array( $this->id, $course_id ),
					$item_ids
				);
				/*echo $query               = $wpdb->prepare( "
					SELECT * FROM(SELECT item_id as id, `status`
					FROM {$wpdb->learnpress_user_items} WHERE user_id = %d AND ref_id = %d AND item_id IN(" . join(', ', $in) . ") ORDER BY user_item_id DESC
					) AS X GROUP BY id
				", $args );*/
				$query = $wpdb->prepare( "
                    SELECT o.item_id, o.status
                    FROM {$wpdb->prefix}learnpress_user_items o
                    WHERE user_item_id = (SELECT MAX(user_item_id) FROM {$wpdb->prefix}learnpress_user_items s2 WHERE s2.item_id = o.item_id)
                    AND user_id = %d AND ref_id = %d
                    AND item_id IN(" . join( ', ', $in ) . ")
                ", $args );

				$item_statuses[$key] = false;
				if ( $rows = $wpdb->get_results( $query ) ) {
					foreach ( $rows as $row ) {
						$item_statuses[$this->id . '-' . $course_id . '-' . $row->item_id] = $row->status;
					}
				}
			}

			if ( $item_ids ) {
				foreach ( $item_ids as $id ) {
					if ( !array_key_exists( $this->id . '-' . $course_id . '-' . $id, $item_statuses ) ) {
						$item_statuses[$this->id . '-' . $course_id . '-' . $id] = false;
					}
				}
			}
			LP_Cache::set_item_statuses( $item_statuses );
		}

		return apply_filters( 'learn_press_user_course_item_status', $item_statuses[$key], $item_id, $course_id, $this->id );
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
		return apply_filters( 'learn_press_user_has_quiz_status', in_array( $status, $statuses ), $statuses, $status, $quiz_id, $course_id, $this->id );
	}

	public function get_quiz_last_results( $quiz_id ) {
		$results = $this->get_course_info( $quiz_id );
		if ( $results ) {
			$results = reset( $results );
		}
		return apply_filters( 'learn_press_user_quiz_last_results', $results, $quiz_id, $this );
	}

	public function get_quiz_info( $quiz_id, $course_id = 0, $field = null ) {
		$course_id = $this->_get_course_id( $course_id );

		static $quizzes = array();
		if ( empty( $quizzes[$quiz_id] ) ) {
			global $wpdb;
			$table     = $wpdb->prefix . 'learnpress_user_items';//{$wpdb->learnpress_user_quizzes}
			$query     = $wpdb->prepare( "
				SELECT *
				FROM $table
				WHERE user_id = %d
				AND item_id = %d
			", $this->id, $quiz_id, '' );
			$user_quiz = (array) $wpdb->get_row( $query );
			if ( !empty( $user_quiz['user_quiz_id'] ) ) {
				$user_quiz['history'] = $this->get_quiz_history( $quiz_id, $course_id );
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
		$course_id = $this->_get_course_id( $course_id );

		$course = learn_press_get_course( $course_id );
		if ( $course ) {
			$quizzes = $course->get_quizzes( 'ID' );
		} else {
			$quizzes = array();
		}
		$key = $this->id . '-' . $course_id . '-' . $quiz_id;


		$cached = LP_Cache::get_quiz_history( false, array() );// wp_cache_get( 'user-quiz-history', 'learnpress' );

		if ( ( !array_key_exists( $key, $cached ) || $force ) && $quizzes && in_array( $quiz_id, $quizzes ) ) {
			global $wpdb;
			$t1             = $wpdb->prefix . 'learnpress_user_items'; //{$wpdb->learnpress_user_quizzes}
			$t2             = $wpdb->prefix . 'learnpress_user_itemmeta'; //{$wpdb->learnpress_user_quizzes}
			$in             = array_fill( 0, sizeof( $quizzes ), '%d' );
			$prepare_params = array_merge(
				array( 'lp_quiz', $this->id, $course_id ),
				$quizzes
			);
			$query          = $wpdb->prepare( "
				SELECT *
				FROM $t1 uq
				WHERE uq.item_type = %s
					AND uq.user_id = %d
					AND uq.ref_id = %d
					AND uq.item_id IN(" . join( ',', $in ) . ")
				ORDER BY uq.user_item_id DESC
			", $prepare_params );


			$history = array();
			foreach ( $quizzes as $_quiz_id ) {
				$history[$this->id . '-' . $course_id . '-' . $_quiz_id] = array();
			}
			if ( $results = $wpdb->get_results( $query ) ) {
				$item_ids = array();
				foreach ( $results as $result ) {
					$item_ids[] = $result->user_item_id;
					$cache_key  = $this->id . '-' . $course_id . '-' . $result->item_id;
					if ( empty( $history[$cache_key] ) ) {
						$history[$cache_key] = array();
					}
					// limit newest 10 items
					if ( sizeof( $history[$cache_key] ) >= 10 ) {
						//break;
					}

					$history[$cache_key][$result->user_item_id] = (object) array(
						'history_id'       => $result->user_item_id,
						'start'            => $result->start_time,
						'end'              => $result->end_time,
						'status'           => $result->status,
						'question'         => '',
						'questions'        => array(),
						'question_answers' => array()
					);
				}
				if ( $item_ids && $meta = $this->_get_quiz_meta( $item_ids ) ) {
					$maps = array(
						'questions'        => 'questions',
						'current_question' => 'question',
						'question_answers' => 'question_answers',
						'question_checked' => 'question_checked'
					);
					foreach ( $meta as $k => $v ) {
						$_key = $this->id . '-' . $course_id . '-' . $v->item_id;
						if ( empty( $history[$_key] ) ) {
							continue;
						}
						if ( empty( $history[$_key][$v->user_item_id] ) ) {
							continue;
						}
						$obj_key = !empty( $maps[$v->meta_key] ) ? $maps[$v->meta_key] : $v->meta_key;
						if ( !$obj_key ) {
							continue;
						}
						$history[$_key][$v->user_item_id]->{$obj_key} = maybe_unserialize( $v->meta_value );
					}
				}
			}

			if ( $history ) {
				foreach ( $history as $k1 => $v1 ) {
					if ( empty( $cached[$k1] ) ) {
						$cached[$k1] = $v1;
						continue;
					}
					foreach ( $v1 as $k2 => $v2 ) {
						$cached[$k1][$k2] = $v2;
					}
				}
			}
			LP_Cache::set_quiz_history( $cached );
		}

		return apply_filters( 'learn_press_user_quiz_history', isset( $cached[$key] ) ? $cached[$key] : array(), $this, $quiz_id );
	}

	private function _get_quiz_meta( $user_item_id ) {
		global $wpdb;
		settype( $user_item_id, 'array' );
		$in = array_fill( 0, sizeof( $user_item_id ), '%d' );

		$query = $wpdb->prepare( "
			SELECT learnpress_user_item_id as user_item_id, meta_key, meta_value, item_id
			FROM {$wpdb->prefix}learnpress_user_itemmeta im
			INNER JOIN {$wpdb->prefix}learnpress_user_items i ON i.user_item_id = im.learnpress_user_item_id
			WHERE learnpress_user_item_id IN(" . join( ',', $in ) . ")
		", $user_item_id );
		return $wpdb->get_results( $query );
	}

	public function get_current_results( $quiz_id, $course_id = 0 ) {
		$course_id = $this->_get_course_id( $course_id );

		$history = $this->get_quiz_history( $quiz_id, $course_id );
		$current = false;
		if ( $history ) {
			$current = reset( $history );
		}
		return $current;
	}

	/**
	 * Get current progress for a quiz
	 *
	 * @param     $quiz_id
	 * @param int $course_id
	 *
	 * @return mixed|void
	 */
	public function get_quiz_progress( $quiz_id, $course_id = 0 ) {
		return $this->get_quiz_results( $quiz_id, $course_id );
		if ( !$course_id ) {
			$course_id = get_the_ID();
		}
		$history  = $this->get_quiz_history( $quiz_id, $course_id );
		$progress = false;
		if ( $history ) {
			$progress = reset( $history );
		}

		return apply_filters( 'learn_press_user_quiz_progress', $progress, $quiz_id, $course_id, $this->id );
	}

	/**
	 * Get current question user doing for a quiz
	 *
	 * @param int $quiz_id
	 * @param int $course_id
	 *
	 * @return mixed|void
	 */
	public function get_current_quiz_question( $quiz_id, $course_id = 0 ) {
		$course_id = $this->_get_course_id( $course_id );

		$question_id = 0;
		if ( $progress = $this->get_quiz_results( $quiz_id, $course_id ) ) {
			if ( !empty( $progress->question ) ) {
				$question_id = $progress->question;
			} elseif ( !empty( $progress->questions ) && is_array( $progress->questions ) ) {
				$question_id = reset( $progress->questions );
			}
		}
		if ( !$question_id ) {
			$quiz = LP_Quiz::get_quiz( $quiz_id );
			if ( $quiz ) {
				$questions = $quiz->get_questions();
				if ( $questions ) {
					$question    = reset( $questions );
					$question_id = $question->ID;
				}
			}

		}
		return apply_filters( 'learn_press_user_current_quiz_question', absint( $question_id ), $quiz_id, $course_id, $this->id );
	}

	public function get_finished_courses() {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT p.*, uc.start_time, uc.end_time, uc.ref_id
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->prefix}learnpress_user_items uc ON p.ID = uc.item_id
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
		$args['post_type'] = LP_QUIZ_CPT;
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
				INNER JOIN {$wpdb->prefix}learnpress_user_items uq ON uq.item_id = q.ID
				WHERE uq.user_id = %d AND uq.item_type = %s
			", $this->id, 'lp_quiz' );

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
			$args['post_type'] = LP_LESSON_CPT;
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
		$purchasable = $course->is_purchasable() && !$this->has_ordered_course( $course_id );
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
		# condition
		$course = LP_Course::get_course( $course_id );
		// check if course is purchasable
		$enrollable = false;
		if ( !$course ) {
			$enrollable = false;
		} elseif ( !$course->is_required_enroll() ) {
			$enrollable = false;
		} elseif ( $course->is_free() && $this->is_exists() ) {
			$enrollable = true;
		} elseif ( $course->is_purchasable() && ( $this->has_purchased_course( $course_id ) ) ) {
			$order      = LP_Order::instance( $this->get_course_order( $course_id ), true );
			$enrollable = !$this->has_enrolled_course( $course_id ) && ( $order && $order->has_status( 'completed' ) );
		}
		$enrollable = apply_filters( 'learn_press_user_can_enroll_course', $enrollable, $this, $course_id );

		return $enrollable;
	}

	public function can_view_item( $item_id, $course_id = 0 ) {
		$return    = false;
		$course_id = $this->_get_course_id( $course_id );

		switch ( get_post_type( $item_id ) ) {
			case LP_QUIZ_CPT:
				$return = $this->can( 'view-quiz', $item_id, $course_id );
				break;
			case LP_LESSON_CPT:
				$return = $this->can( 'view-lesson', $item_id, $course_id );
				break;
		}
		return apply_filters( 'learn_press_user_can_view_item', $return, $item_id, $course_id, $this->id );
	}

	public function can_edit_item( $item_id, $course_id = 0 ) {
		$return = $this->is_admin();

		if ( !$return ) {
			$course_id = $this->_get_course_id( $course_id );

			$course_author = learn_press_get_course_user( $course_id );
			if ( $course_author && $course_author->id == $this->id ) {
				$return = true;
			}
		}
		return apply_filters( 'learn_press_user_can_edit_item', $return, $item_id, $course_id, $this->id );
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
		$view = false;
		// else, find the course of this lesson
		$course_id = $this->_get_course_id( $course_id );

		$lesson = LP_Lesson::get_lesson( $lesson_id );
		if ( $course = LP_Course::get_course( $course_id ) ) {
			if ( $this->has( 'enrolled-course', $course_id ) || $this->has( 'finished-course', $course_id ) ) {
				// or user has enrolled course
				$view = 'enrolled';
			} elseif ( $lesson->is( 'previewable' ) || $this->is_admin() || ( $this->is_instructor() && $course->post->post_author == $this->user->ID ) ) {
				$view = 'preview';
			} elseif ( !$course->is( 'required_enroll' ) ) {
				// if course is not required enroll so the lesson is previewable
				$view = 'no-required-enroll';
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
		$course    = false;
		$view      = false;
		$course_id = $this->_get_course_id( $course_id );

		if ( $course_id ) {
			$course = LP_Course::get_course( $course_id );
		}

		if ( $course ) {
			if ( $this->has( 'enrolled-course', $course_id ) || $this->has( 'finished-course', $course_id ) ) {
				$view = 'enrolled';
			} elseif ( $this->is_admin() || ( $this->is_instructor() && $course->post->post_author == $this->user->ID ) ) {
				$view = 'preview';
			} elseif ( !$course->is( 'required_enroll' ) ) {
				$view = 'no-required-enroll';
			}
		}
		return apply_filters( 'learn_press_user_view_quiz', $view, $quiz_id, $this->id, $course_id );
	}

	/**
	 * Check to see if user can retake a quiz
	 * - FALSE if user CAN NOT retake quiz
	 * - INT (number of remain) if user CAN retake quiz
	 *
	 * @param         $quiz_id
	 * @param int     $course_id
	 * @param boolean $force
	 *
	 * @return mixed|null|void
	 */
	public function can_retake_quiz( $quiz_id, $course_id = 0, $force = false ) {
		$can       = false;
		$course_id = $this->_get_course_id( $course_id );

		// Check if quiz is already exists
		if ( $quiz = LP_Quiz::get_quiz( $quiz_id ) ) {
			$count = $quiz->retake_count;
			if ( $count > 0 ) {
				// Number of taken
				$taken = $this->count_retaken_quiz( $quiz_id, $course_id, $force );
				if ( $taken ) {
					$can = $count - $taken;
				} else {
					$can = $count;
				}
			}
		}

		return apply_filters( 'learn_press_user_can_retake_quiz', $can, $quiz_id, $this->id, $course_id );
	}

	public function _has_quiz_status( $status, $quiz_id, $course_id = 0 ) {

	}

	public function can_finish_course( $course_id ) {
		$return = false;
		if ( $course = LP_Course::get_course( $course_id ) ) {
			$result = $course->evaluate_course_results();
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
			$count = $course->retake_count;
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

		return apply_filters( 'learn_press_user_can_retake_course', $can, $course->id, $this->id );
	}

	public function get_incomplete_items( $course_id ) {
		global $wpdb;
		$query    = $wpdb->prepare( "
			SELECT user_item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE user_id = %d
			AND (item_id = %d OR ref_id = %d)
			AND `status` NOT IN(%s, %s)
		", $this->id, $course_id, $course_id, 'completed', 'finished' );
		$item_ids = $wpdb->get_col( $query );
		return apply_filters( 'learn_press_user_incomplete_items', $item_ids, $course_id, $this->id );
	}

	public function finish_course( $course_id ) {
		global $wpdb;
		$return = 0;
		if ( $course = LP_Course::get_course( $course_id ) ) {
			if ( !$this->can( 'finish-course', $course_id ) && 1 == 0 ) {
				return false;
			} else {
				$time      = current_time( 'timestamp' );
				$expired   = $course->get_user_expired_time( $this->id );
				$updated   = $wpdb->update(
					$wpdb->prefix . 'learnpress_user_items',
					array(
						'end_time' => date( 'Y-m-d H:i:s', $expired - $time < 0 ? $expired : $time ),
						'status'   => 'finished'
					),
					array( 'user_id' => $this->id, 'item_id' => $course_id ),
					array( '%s', '%s' )
				);
				$null_time = '0000-00-00 00:00';

				/*$incomplete_items = $this->get_incomplete_items( $course_id );
				if ( $incomplete_items ) {
					$update    = $wpdb->prepare( "
						UPDATE {$wpdb->prefix}learnpress_user_items
						SET end_time = %s, status = CASE
							WHEN item_type = %s THEN %s
							ELSE %s
							END
						WHERE
							user_id = %d
							AND (( item_id = %d AND item_type = %s) OR (ref_id = %d and ref_type = %s))
							AND `status` NOT IN(%s, %s)
					", $null_time, 'lp_course', 'finished', 'completed', $this->id, $course_id, 'lp_course', $course_id, 'lp_order', 'finished', 'completed' );
					$wpdb->query( $update );

				}*/
				$return = $wpdb->get_var(
					$wpdb->prepare( "
							SELECT user_item_id
							FROM {$wpdb->prefix}learnpress_user_items
							WHERE user_id = %d
								AND item_id = %d
								AND start_time <> %s AND end_time <> %s
						", $this->id, $course_id, $null_time, $null_time )
				);
				if ( $return ) {
					do_action( 'learn_press_user_finish_course', $course_id, $this->id, $return );
				}
			}
		}
		return apply_filters( 'learn_press_user_finish_course_data', $return, $course_id, $this->id );
	}

	public function is_instructor() {
		$roles = !empty( $this->user->roles ) ? $this->user->roles : array();
		return in_array( LP_TEACHER_ROLE, $roles );
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

	public function get_orders() {
		_learn_press_get_user_course_orders( $this->id );
		$orders = LP_Cache::get_user_course_order( false, array() );
		return !empty( $orders[$this->id] ) ? $orders[$this->id] : false;
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

		if ( $enrolled = $this->has_purchased_course( $course_id ) ) {
			$item_statuses = LP_Cache::get_item_statuses( false, array() );
			$key           = sprintf( '%d-%d-%d', $this->id, $course_id, $course_id );
			$enrolled      = false;
			if ( !array_key_exists( $key, $item_statuses ) ) {
				$enrolled = $item_statuses[$key] = $this->_has_enrolled_course( $course_id );
			} elseif ( !empty( $item_statuses[$key] ) && $item_statuses[$key] != '' ) {
				$enrolled = true;
			}
		}
		return apply_filters( 'learn_press_user_has_enrolled_course', $enrolled, $this, $course_id );
	}

	private function _has_enrolled_course( $course_id ) {
		global $wpdb;
		$query = $wpdb->prepare( "
				SELECT status
				FROM {$wpdb->prefix}learnpress_user_items
				WHERE user_id = %d
				AND item_id = %d
				AND status <> %s
				LIMIT 0, 1
			", $this->id, $course_id, '' );
		return $wpdb->get_var( $query ) ? true : false;
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
		$item_statuses = LP_Cache::get_item_statuses( false, array() );
		$key           = sprintf( '%d-%d-%d', $this->id, $course_id, $course_id );
		$enrolled      = false;
		if ( !empty( $item_statuses[$key] ) ) {
			$enrolled = $item_statuses[$key];
		}
		return apply_filters( 'learn_press_user_has_finished_course', $enrolled == 'finished', $this, $course_id );


		//static $courses = array();
		$finished_courses = LP_Cache::get_finished_courses( false, array() );
		if ( empty( $finished_courses[$course_id] ) || $force ) {
			global $wpdb;
			$query                        = $wpdb->prepare( "
				SELECT status
				FROM {$wpdb->prefix}learnpress_user_items uc
				INNER JOIN {$wpdb->posts} c ON c.ID = uc.item_id
				INNER JOIN {$wpdb->posts} o ON o.ID = uc.ref_id
				INNER JOIN {$wpdb->postmeta} om ON om.post_id = o.ID AND om.meta_key = %s AND om.meta_value = %d
				WHERE uc.user_id = %d
				AND uc.item_id = %d
				AND o.post_status = %s
				ORDER BY user_item_id DESC LIMIT 0,1
			", '_user_id', $this->id, $this->id, $course_id, 'lp-completed' );
			$finished_courses[$course_id] = $wpdb->get_var( $query ) == 'finished' ? 'yes' : 'no';
			LP_Cache::set_finished_courses( $finished_courses );
		}
		return apply_filters( 'learn_press_user_has_finished_course', $finished_courses[$course_id] == 'yes', $course_id, $this->id );
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
		$course_id = $this->_get_course_id( $course_id );

		$started   = false;
		$quiz_info = $this->get_quiz_results( $quiz_id, $course_id );
		if ( $quiz_info ) {
			$started = in_array( $quiz_info->status, array( 'started', 'completed' ) ) ? $quiz_info->status : false;
		}
		$started = $this->has_quiz_status( array( 'started', 'completed' ), $quiz_id, $course_id );
		return apply_filters( 'learn_press_user_started_quiz', $started, $quiz_id, $course_id, $this->id );
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
		$course_id = $this->_get_course_id( $course_id );

		$completed = $this->get_quiz_status( $quiz_id, $course_id ) == 'completed';

		return apply_filters( 'learn_press_user_has_completed_quiz', $completed, $quiz_id, $this );
	}

	/**
	 * @param int  $quiz_id
	 * @param int  $course_id
	 * @param bool $force
	 *
	 * @return mixed
	 */
	public function current_quiz_status( $quiz_id, $course_id = 0, $force = false ) {
		$course_id = $this->_get_course_id( $course_id );

		global $wpdb;
		//$key_format = '%d-%d';
		$cached = (array) wp_cache_get( 'user-quiz-statuses', 'learnpress' );
		if ( !array_key_exists( $this->id . '-' . $course_id . '-' . $quiz_id, $cached ) || $force ) {
			$query                              = $wpdb->prepare( "
				SELECT uq.item_id as id, uqm.meta_value as `status`
				FROM {$wpdb->prefix}learnpress_user_itemmeta uqm
				INNER JOIN {$wpdb->prefix}learnpress_user_items uq ON uq.user_item_id = uqm.learnpress_user_item_id AND uqm.meta_key = %s
				WHERE uq.user_id = %d
				ORDER BY user_item_id DESC
			", 'status', $this->id, $quiz_id );
			$cached[$this->id . '-' . $quiz_id] = '';
			if ( $items = $wpdb->get_results( $query ) ) {
				foreach ( $items as $item ) {
					$cached[$this->id . '-' . $item->ID] = $item->status;
				}
			}
		}
		return $cached[$this->id . '-' . $quiz_id];
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
		$count = false;

		$course_id = $this->_get_course_id( $course_id );

		if ( !$course_id || !$quiz_id ) {
			return $count;
		}

		global $wpdb;

		$count   = 0;
		$history = $this->get_quiz_history( $quiz_id, $course_id );
		if ( $history ) {
			$count = sizeof( $history ) - 1;
		}
		return apply_filters( 'learn_press_user_count_retaken_quiz', $count, $quiz_id, $course_id, $this->id );

		// Get data from cache
		$cached = (array) wp_cache_get( 'user-count-retaken-quiz', 'learnpress' );

		// If no data exists in cache or force to get it from database
		if ( !array_key_exists( $this->id . '-' . $course_id . '-' . $quiz_id, $cached ) || $force ) {
			$query = $wpdb->prepare( "
				SELECT count(user_item_id) - 1 FROM {$wpdb->prefix}learnpress_user_items
				WHERE user_id = %d
				AND item_id = %d
				AND ref_id = %d
				AND item_type = %s
			", $this->id, $quiz_id, $course_id, 'lp_quiz' );

			$count = $wpdb->get_var( $query );
			if ( $count < 0 ) {
				$count = 0;
			}
			// Store into cache
			$cached[$this->id . '-' . $course_id . '-' . $quiz_id] = $count;
			wp_cache_set( 'user-count-retaken-quiz', $cached, 'learnpress' );
		} else {
			$count = $cached[$this->id . '-' . $course_id . '-' . $quiz_id];
		}

		return apply_filters( 'learn_press_user_count_retaken_quiz', $count, $quiz_id, $course_id, $this->id );
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
		$course_id = $this->_get_course_id( $course_id );

		if ( !$course_id ) {
			return $count;
		}

		global $wpdb;

		// Get data from cache
		$cached = (array) wp_cache_get( 'user-count-retaken-course', 'learnpress' );

		// If no data exists in cache or force to get it from database
		if ( !array_key_exists( $this->id . '-' . $course_id, $cached ) || $force ) {
			$query = $wpdb->prepare( "
				SELECT count(user_item_id) - 1 FROM {$wpdb->prefix}learnpress_user_items
				WHERE user_id = %d
				AND item_id = %d
				AND item_type = %s
			", $this->id, $course_id, 'lp_course' );

			$count = $wpdb->get_var( $query );
			if ( $count < 0 ) {
				$count = 0;
			}
			// Store into cache
			$cached[$this->id . '-' . $course_id] = $count;
			wp_cache_set( 'user-count-retaken-course', $cached, 'learnpress' );
		} else {
			$count = $cached[$this->id . '-' . $course_id];
		}

		return apply_filters( 'learn_press_user_count_retaken_course', $count, $course_id, $this->id );
	}

	public function retake_course( $course_id ) {
		if ( !$this->can( 'retake-course', $course_id ) ) {
			return false;
		}

		global $wpdb;
		$inserted = 0;

		$check = apply_filters( 'learn_press_before_retake_course', true, $course_id, $this->id );
		if ( !$check ) {
			return false;
		}
		if ( $wpdb->insert(
			$wpdb->prefix . 'learnpress_user_items',
			array(
				'user_id'    => $this->id,
				'item_id'    => $course_id,
				'start_time' => current_time( 'mysql' ),
				'status'     => 'enrolled',
				'end_time'   => '0000-00-00 00:00:00',
				'ref_id'     => $this->get_course_order( $course_id ),
				'item_type'  => 'lp_course',
				'ref_type'   => 'lp_order'
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		)
		) {
			$inserted = $wpdb->insert_id;

			/*
			 * Should be deleted all user items when user retake course?
			 */
			$wpdb->query(
				$wpdb->prepare( "
					DELETE FROM {$wpdb->prefix}learnpress_user_items
					WHERE user_id = %d
					AND ref_id = %d
					AND ref_type = %s
				", $this->id, $course_id, 'lp_course' )
			);
			do_action( 'learn_press_user_retaken_course', $course_id, $this->id, $inserted );

		} else {
			learn_press_debug( $wpdb );
			do_action( 'learn_press_user_retake_course_failed', $course_id, $this->id, $inserted );
		}
		return $inserted;
	}

	/**
	 * Checks if user has started a lesson
	 *
	 * @param $lesson_id
	 * @param $course_id
	 *
	 * @return null|string
	 */
	public function is_exists_lesson( $lesson_id, $course_id ) {
		global $wpdb;
		echo $query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE user_id = %d
				AND ref_id = %d
				AND item_id = %d
			ORDER BY user_item_id DESC
		", $this->id, $course_id, $lesson_id );
		$results = $wpdb->get_row( $query );
		if ( $results ) {
			return $results;
		}
		return false;
	}

	/**
	 * Mark a lesson is completed for user
	 *
	 * @param     $lesson_id
	 * @param int $course_id
	 *
	 * @return bool|WP_Error
	 */
	public function complete_lesson( $lesson_id, $course_id = 0 ) {
		global $wpdb;
		do_action( 'learn_press_before_user_complete_lesson', $lesson_id, $this );
		$course_id = $this->_get_course_id( $course_id );

		if ( $this->can_view_lesson( $lesson_id, $course_id ) == 'preview' ) {
			return false;
		}
		$result = false;

		/**
		 * If user has stared a lesson, get user lesson information
		 */
		if ( $item = $this->is_exists_lesson( $lesson_id, $course_id ) ) {

			// Update lesson status if it's not 'completed'
			if ( $item->status !== 'completed' ) {
				$updated = $wpdb->update(
					$wpdb->prefix . 'learnpress_user_items',
					array(
						'end_time' => current_time( 'mysql' ),
						'status'   => 'completed'
					),
					array(
						'user_item_id' => $item->user_item_id
					),
					array( '%s', '%s' ),
					array( '%d' )
				);
				if ( !$updated ) {
					$result = new WP_Error( 'lesson-completed', $wpdb->last_error );
				}
			} else {
				$result = new WP_Error( 'lesson-completed', __( 'You has already completed this lesson', 'learnpress' ) );
			}
		} else {
			$wpdb->insert(
				$wpdb->prefix . 'learnpress_user_items',
				array(
					'user_id'    => $this->id,
					'item_id'    => $lesson_id,
					'item_type'  => LP_LESSON_CPT,
					'ref_id'     => $course_id,
					'ref_type'   => LP_COURSE_CPT,
					'start_time' => current_time( 'mysql' ),
					'end_time'   => current_time( 'mysql' ),
					'status'     => 'completed',
					'parent_id'  => learn_press_get_user_item_id( $this->id, $course_id )
				),
				array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d' )
			);
			$updated = $wpdb->insert_id;
			if ( !$updated ) {
				$result = new WP_Error( 'lesson-completed', $wpdb->last_error );
			}
		}
		if ( !empty( $updated ) ) {
			if ( $course = LP_Course::get_course( $course_id ) ) {
				$result = $course->evaluate_course_results( $this->id );
			}
		}

		do_action( 'learn_press_user_complete_lesson', $lesson_id, $result, $this->id );

		return $result;
	}

	/**
	 * Returns TRUE if user has already completed a lesson
	 *
	 * @param      $lesson_id
	 * @param null $course_id
	 * @param bool $force
	 *
	 * @return mixed|null|void
	 */
	public function has_completed_lesson( $lesson_id, $course_id = null, $force = false ) {

		return $this->get_item_status( $lesson_id, $course_id, $force ) == 'completed';

		if ( !$course_id ) {
			$course    = LP()->course;
			$course_id = $course ? $course->id : 0;
		}
		$cached  = array_key_exists( $this->id, self::$_lessons );
		$lessons = $cached ? (array) self::$_lessons[$this->id] : array();
		if ( !$cached/* && !array_key_exists( $lesson_id, $lessons )*/ || $force ) {
			global $wpdb;
			$query = $wpdb->prepare( "
				SELECT item_id, status FROM {$wpdb->prefix}learnpress_user_items
				WHERE user_id = %d
					AND item_type = %s
			", $this->id, 'lp_lesson' );
			$query .= $course_id ? $wpdb->prepare( " AND ref_id = %d AND ref_type = %s", $course_id, 'lp_course' ) : '';
			if ( $rows = $wpdb->get_results( $query ) ) {
				foreach ( $rows as $r ) {
					$lessons[$r->item_id] = $r->status;
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
		//static $user_course_info = array();
		$user_course_info = LP_Cache::get_course_info( false, array() );
		if ( empty( $user_course_info[$this->id] ) ) {
			$user_course_info[$this->id] = array();
		}
		if ( $course_id && empty( $user_course_info[$this->id][$course_id] ) ) {
			$query = $wpdb->prepare( "
				SELECT uc.*
				FROM {$wpdb->prefix}learnpress_user_items uc
				INNER JOIN {$wpdb->posts} o ON o.ID = uc.item_id
				WHERE uc.user_id = %d AND uc.status IS NOT NULL
				AND uc.item_id = %d AND uc.item_type = %s
				ORDER BY user_item_id DESC
			", $this->id, $course_id, 'lp_course' );

			$info = array(
				'history_id' => 0,
				'start'      => null,
				'end'        => null,
				'status'     => null
			);
			if ( $result = $wpdb->get_row( $query ) ) {
				$course             = learn_press_get_course( $course_id );
				$info['history_id'] = $result->user_item_id;
				$info['start']      = $result->start_time;
				$info['end']        = $result->end_time;
				$info['status']     = $result->status;
				$info['results']    = $course->evaluate_course_results( $this->id );
				$info['items']      = $course->get_items_params( $this->id );
			}
			$user_course_info[$this->id][$course_id] = $info;
			LP_Cache::set_course_info( $user_course_info );

		}
		if ( $field && array_key_exists( $field, $user_course_info[$this->id][$course_id] ) ) {
			$info = $user_course_info[$this->id][$course_id][$field];
		} else {
			$info = $user_course_info[$this->id][$course_id];
		}

		$this->_parse_item_order_of_course( $course_id );
		return apply_filters( 'learn_press_user_course_info', $info, $this, $course_id );
	}

	public function get_course_history_id( $course_id ) {
		$history = $this->get_course_info( $course_id );
		return !empty( $history['history_id'] ) ? $history['history_id'] : 0;
	}

	public function get_course_status( $course_id ) {
		return apply_filters( 'learn_press_user_course_status', $this->get_course_info( $course_id, 'status' ), $this->id );
	}

	/**
	 * Get current results of a quiz
	 *
	 * @param int  $quiz_id
	 * @param int  $course_id
	 * @param bool $force
	 *
	 * @return mixed
	 */
	public function get_quiz_results( $quiz_id, $course_id = 0, $force = false ) {
		$course_id = $this->_get_course_id( $course_id );

		$quiz_results = LP_Cache::get_quiz_results( false, array() );

		$key = $this->id . '-' . $course_id . '-' . $quiz_id;
		if ( get_class( $this ) == 'LP_User_Guest' ) {
//			print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 10));
		}
		if ( !array_key_exists( $key, $quiz_results ) || $force ) {
			if ( $history = $this->get_quiz_history( $quiz_id, $course_id, false, $force ) ) {
				$quiz_results[$key] = reset( $history );
				if ( $user_results = $this->evaluate_quiz_results( $quiz_id, $quiz_results[$key] ) ) {
					foreach ( $user_results as $k => $v ) {
						$quiz_results[$key]->{$k} = $v;
					}
				}
			} else {
				$quiz_results[$key] = false;
			}
			LP_Cache::set_quiz_results( $quiz_results );
		}
		return $quiz_results[$key];
	}

	/**
	 * Evaluate results of a quiz for this user
	 *
	 * @param $quiz_id
	 * @param $progress
	 *
	 * @return mixed|void
	 */
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
			//'quiz_time'       => $quiz->duration,
			'quiz_mark'       => $quiz->get_mark(),
			'time'            => 0,
			'questions'       => array()
		);
		$questions = $quiz->questions;
		if ( $questions ) {
			$questions            = array_keys( $questions );
			$results['questions'] = $questions;
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
					$results['mark'] += !empty( $check['mark'] ) ? $check['mark'] : 0;
				} else {
					$check = false;
					$results['empty'] ++;
				}
				$results['answer_results'][$question_id] = $check;
			}
		}
		if ( $total_questions = sizeof( $questions ) ) {
			$results['correct_percent'] = round( $results['correct'] / $total_questions * 100 );
			$results['wrong_percent']   = round( $results['wrong'] / $total_questions * 100 );
			$results['empty_percent']   = round( $results['empty'] / $total_questions * 100 );
		}
		$results['time'] = $this->_calculate_quiz_time( $quiz, $progress );
		if ( $results['quiz_mark'] ) {
			$results['mark_percent'] = round( $results['mark'] / $results['quiz_mark'] * 100 );
		}
		return apply_filters( 'learn_press_evaluate_quiz_results', $results, $quiz_id, $this->id );
	}

	private function _calculate_quiz_time( $quiz, $progress ) {
		$start = strtotime( $progress->start );
		$end   = strtotime( $progress->end );
		if ( $end < $start ) {
			$end = $start + $quiz->duration;
			//learn_press_update_user_quiz_meta( $progress->history_id, 'end', $end );
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
		/*$purchased = false;
		$orders    = $this->get_orders();
		$order     = !empty( $orders[$course_id] ) ? $orders[$course_id] : false;
		if ( $order ) {
			$purchased = $order->post_status = 'lp-completed';
		}*/
		return apply_filters( 'learn_press_user_has_purchased_course', $this->get_order_status( $course_id ) == 'lp-completed', $course_id, $this->id );

		$purchased = false;
		$order     = $this->get_course_order( $course_id, 'object' );
		if ( $order && $order->has_status( array( 'processing', 'completed' ) ) ) {
			$purchased = $order->id;
		}
		return apply_filters( 'learn_press_user_has_purchased_course', $purchased, $course_id, $this->id, $order ? $order->id : 0 );
	}

	public function has_ordered_course( $course_id ) {
		return apply_filters( 'learn_press_user_has_ordered_course', $this->get_course_order( $course_id ), $course_id, $this->id );
	}

	public function get_order_status( $course_id ) {
		$orders = $this->get_orders();
		$order  = !empty( $orders[$course_id] ) ? $orders[$course_id] : false;
		return apply_filters( 'learn_press_user_has_ordered_course', $order ? $order->post_status : false, $course_id, $this->id );
	}


	public function has_completed_item( $item, $course_id = 0, $force = false ) {
		$course_id = $this->_get_course_id( $course_id );

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
			if ( !$type ) {
				$type = get_post_type( $item_id );
			}
			if ( $type == 'lp_lesson' ) {
				$return = $this->has_completed_lesson( $item_id, $course_id, $force );
			} elseif ( $type == 'lp_quiz' ) {
				$return = $this->has_completed_quiz( $item_id, $course_id, $force );
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
		$orders = $this->get_orders();
		$order  = !empty( $orders[$course_id] ) ? $orders[$course_id] : false;

		return $order ? ( $return == 'object' ? LP_Order::instance( $order->ID ) : $order->ID ) : false;
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
			//$this->_parse_item_order_of_course( $course_id );
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
	public function enroll( $course_id, $ref_id = 0 ) {
		if ( !$this->can( 'enroll-course', $course_id ) ) {
			return false;
		}
		global $wpdb;
		$inserted = 0;

		$check = apply_filters( 'learn_press_before_enroll_course', true, $this, $course_id );
		if ( !$check ) {
			return false;
		}

		$course = learn_press_get_course( $course_id );
		$ref_id = 0;
		if ( $course->is_free() ) {
			# 1 create order
			$order_data = array(
				'status'      => apply_filters( 'learn_press_default_enroll_order_status', 'completed' ),
				'user_id'     => get_current_user_id(),
				'user_note'   => '',
				'created_via' => 'enroll'
			);
			$order      = learn_press_create_order( $order_data );

			# 2 add order item
			$item       = array(
				'order_item_name' => $course->get_title(),
				'course_id'       => $course->id,
				'name'            => $course->get_title(),
				'quantity'        => 1,
				'subtotal'        => $course->get_price(),
				'total'           => $course->get_price()
			);
			$order_item = learn_press_add_order_item( $order->id, $item );
			$ref_id     = $order->id;

			# 3 add order itemmeta
			learn_press_add_order_item_meta( $order_item, '_course_id', $course->id );
			learn_press_add_order_item_meta( $order_item, '_quantity', 1 );
			learn_press_add_order_item_meta( $order_item, '_subtotal', 0 );
			learn_press_add_order_item_meta( $order_item, '_total', 0 );

		} else {
			$ref_id = $this->get_course_order( $course_id );
		}

		/**
		 * // backup from server
		 * if ( $wpdb->insert(
		 * $wpdb->prefix . 'learnpress_user_items',
		 * array(
		 * 'user_id'    => $this->id,
		 * 'item_id'    => $course_id,
		 * 'start_time' => current_time( 'mysql' ),
		 * 'status'     => 'enrolled',
		 * 'end_time'   => '0000-00-00 00:00:00',
		 * 'ref_id'     => $ref_id,
		 * 'item_type'  => 'lp_course',
		 * 'ref_type'   => $ref_type
		 * ),
		 * array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		 * )
		 * ) {
		 */
		# 4 enroll course
		if ( $wpdb->insert(
			$wpdb->prefix . 'learnpress_user_items',
			array(
				'user_id'    => $this->id,
				'item_id'    => $course_id,
				'start_time' => current_time( 'mysql' ),
				'status'     => 'enrolled',
				'end_time'   => '0000-00-00 00:00:00',
				'ref_id'     => $ref_id,
				'item_type'  => 'lp_course',
				'ref_type'   => 'lp_order'
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		)
		) {
			$inserted = $wpdb->insert_id;

			do_action( 'learn_press_user_enrolled_course', $course_id, $this->id, $inserted );

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

		$args['post_type'] = LP_QUESTION_CPT;
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
					LEFT JOIN {$wpdb->prefix}learnpress_user_items uc ON c.ID = uc.item_id AND uc.user_id = %d
					WHERE post_type = %s
						AND ( c.post_status = %s OR c.post_status = %s)
						AND post_author = %d
					UNION
					SELECT c.*, uc.status as course_status
					FROM {$wpdb->posts} c
					INNER JOIN {$wpdb->prefix}learnpress_user_items uc ON c.ID = uc.item_id
					WHERE uc.user_id = %d
						AND c.post_type = %s
						AND c.post_status = %s
				) a GROUP BY a.ID
			", $args['user_id'],
				LP_COURSE_CPT, 'publish', 'draft', $this->id,
				$args['user_id'], LP_COURSE_CPT, 'publish'
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

	public function _get_orders() {
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
			FROM {$wpdb->prefix}learnpress_user_items uq
			INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta uqm ON uqm.learnpress_user_item_id = uq.user_item_id AND uqm.meta_key = %s AND uqm.meta_value LIKE %s
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

	public function get_course_info2( $course_id ) {
		$course_info = $this->get_course_info( $course_id );
		if ( !empty( $course_info['items'] ) ) {
			foreach ( $course_info['items'] as $k => $item ) {
				if ( $item['type'] == 'lp_quiz' ) {
					$result                   = $this->get_quiz_results( $item['id'], $course_id );
					$course_info['items'][$k] = array_merge(
						$course_info['items'][$k],
						array(
							'results' => $result ? $result->mark_percent : 0
						)
					);
				}
			}
		}
		return $course_info;
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
				INNER JOIN {$wpdb->prefix}learnpress_user_items uc ON c.ID = uc.item_id
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

	function get_completed_items_in_section( $course_id, $section_id, $force = false ) {
		$course     = learn_press_get_course( $course_id );
		$curriculum = $course->get_curriculum( $section_id );
		$completed  = 0;
		if ( $curriculum && $curriculum->items ) {
			foreach ( $curriculum->items as $item ) {
				if ( $this->has_completed_item( $item->ID, $course_id, $force ) ) {
					$completed ++;
				}
			}
		}
		return $completed;
	}

	public function _get_found_rows() {
		return $this->_FOUND_ROWS;
	}

	public function has_checked_answer( $question_id, $quiz_id, $course_id = 0 ) {
		$course_id = $this->_get_course_id( $course_id );

		$history = $this->get_quiz_results( $quiz_id, $course_id );
		if ( !$history ) {
			return false;
		}
		$checked = (array) learn_press_get_user_item_meta( $history->history_id, 'question_checked', true );
		$checked = array_filter( $checked );
		return in_array( $question_id, $checked );
	}

	/**
	 * Get user's quiz's graduation
	 *
	 * @param      $quiz_id
	 * @param int  $course_id
	 * @param bool $check_completed
	 *
	 * @return mixed|void
	 */
	public function get_quiz_graduation( $quiz_id, $course_id = 0, $check_completed = true ) {
		$course_id = $this->_get_course_id( $course_id );

		$result = $this->get_quiz_results( $quiz_id, $course_id );
		$grade  = '';
		if ( $result && ( ( $check_completed == false ) || $check_completed && $result->status == 'completed' ) ) {
			$quiz          = LP_Quiz::get_quiz( $quiz_id );
			$grade_type    = $quiz->passing_grade_type;
			$passing_grade = $quiz->passing_grade;
			if ( $grade_type == 'point' ) {
				$grade = $passing_grade <= $result->mark;
			} elseif ( $grade_type == 'percentage' ) {
				$grade = $passing_grade <= $result->mark_percent;
			} else {
				$grade = true;
			}
			$grade = $grade ? 'passed' : 'failed';
		}
		return apply_filters( 'learn_press_user_quiz_graduation', $grade, $quiz_id, $course_id );
	}

	public function get_current_quiz_questionX() {

	}

	/**
	 * Return TRUE if user is already exists
	 *
	 * @return bool
	 */
	public function is_exists() {
		return $this->ID > 0;
	}

	public function get_upload_profile_src( $size = 'thumbnail' ) {
		if ( empty( $this->uploaded_profile_src ) ) {
			$profile_picture = $this->profile_picture;
			$upload          = wp_get_upload_dir();
			$user_id         = $this->id;
			if ( file_exists( $upload['basedir'] . '\learn-press-profile\\' . $user_id . '\\' . $profile_picture ) ) {
				$this->uploaded_profile_src = $upload['baseurl'] . '/learn-press-profile/' . $user_id . '/' . $profile_picture;
			} else {
				$this->uploaded_profile_src = false;
			}
		}
		return $this->uploaded_profile_src;
	}

	public function get_profile_picture( $type = '', $size = 96 ) {
		if ( empty( $type ) ) {
			$type = $this->profile_picture_type;
		}
		if ( $type == 'picture' ) {
			if ( $profile_picture_src = $this->get_upload_profile_src( $size ) ) {
				$this->profile_picture_src = $profile_picture_src;
				add_filter( 'get_avatar_url', array( $this, 'get_avatar_url' ), 10, 3 );
			}
		}
		$avatar = get_avatar( $this->id, $size );
		remove_filter( 'get_avatar_url', array( $this, 'get_avatar_url' ), 10 );
		return $avatar;
	}

	public function get_profile_picture_src() {
		//if ( empty( $this->profile_picture_src ) ) {
		$profile_picture_type = $this->profile_picture_type;
		if ( $profile_picture_type == 'picture' ) {
			if ( $profile_picture_src = $this->get_upload_profile_src() ) {
				$this->profile_picture_src = $profile_picture_src;
				add_filter( 'get_avatar_url', array( $this, 'get_avatar_url' ), 10, 3 );
			}
		} else {
			$avatar_data               = get_avatar_data( $this->id );
			$this->profile_picture_src = $avatar_data['url'];
		}
		///}
		return $this->profile_picture_src;
	}

	public function get_avatar_url( $url, $id_or_email, $args ) {
		if ( is_numeric( $id_or_email ) && $id_or_email == $this->id ) {
			$url = $this->profile_picture_src;
		}
		if ( $id_or_email == $this->user_login ) {
			$url = $this->profile_picture_src;
		}
		///
		return $url;
	}
}
