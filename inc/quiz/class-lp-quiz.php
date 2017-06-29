<?php

/**
 * Class LP_Quiz
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Quiz extends LP_Abstract_Course_Item {
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
	 * Mark of quiz
	 *
	 * @var int
	 */
	protected $_mark = null;

	public $content = '';

	/**
	 * LP_Quiz_CURD
	 *
	 * @var string
	 */
	protected $_curd = 'LP_Quiz_CURD';

	/**
	 * @var array
	 */
	static protected $_meta = array();

	/**
	 * Constructor gets the post object and sets the ID for the loaded course.
	 *
	 * @param mixed
	 */
	public function __construct( $the_quiz, $args = array() ) {

		parent::__construct( $the_quiz, $args );

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
	}

	/**
	 * Load quiz data
	 */
	public function load() {
		$this->_curd->load( $this );
	}

	/**
	 * Load questions
	 */
	protected function _load_questions() {
		$id        = $this->get_id();
		$questions = wp_cache_get( 'questions-' . $id, 'lp-quizzes' );
		if ( false === $questions || $this->get_no_cache() ) {
			global $wpdb;
			$questions = array();
			$query     = $wpdb->prepare( "
				SELECT p.*, qq.question_order AS `order`
				FROM {$wpdb->posts} p 
				INNER JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON p.ID = qq.question_id
				WHERE qq.quiz_id = %d
				ORDER BY question_order ASC
			", $id );
			if ( $results = $wpdb->get_results( $query, OBJECT_K ) ) {
				foreach ( $results as $k => $v ) {
					wp_cache_set( $v->ID, $v, 'posts' );
					$questions[ $v->ID ] = $v->ID;
				}
			}
			wp_cache_set( 'questions-' . $id, $questions, 'lp-quizzes' );

			//$this->_load_question_answers();
		}
	}

	/**
	 * Update meta cache for post type are quizzes and questions
	 */
	protected function _update_meta_cache() {
		$meta_ids = $this->get_questions();
		if ( false === $meta_ids ) {
			$meta_ids = array( $this->get_id() );
		} else {
			$meta_ids[] = $this->get_id();
		}
		update_meta_cache( 'post', $meta_ids );
	}

	/**
	 * Sort questions by order.
	 * Check in an array of questions if there is a key 'order'.
	 *
	 * @param $questions
	 *
	 * @return mixed
	 */
	protected function _maybe_sort_questions( &$questions ) {
		if ( ! $questions ) {
			return $questions;
		}
		$first = reset( $questions );
		if ( empty( $first['order'] ) ) {
			return $questions;
		}

		uasort( $questions, array( $this, 'callback_sort_questions' ) );

		return $questions;
	}

	public function callback_sort_questions( $a, $b ) {
		return $a['order'] > $b['order'];
	}

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

	/**
	 *
	 */
	protected function _load_question_answers() {
		global $wpdb;

		$questions = $this->get_questions();
		$format    = array_fill( 0, sizeof( $questions ), '%d' );
		$query     = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}learnpress_question_answers
			WHERE question_id IN(" . join( ',', $format ) . ")
			ORDER BY question_id, answer_order ASC
		", $questions );
		if ( $results = $wpdb->get_results( $query, OBJECT_K ) ) {
			$answer_options = array();
			$meta_ids       = array();
			$group          = 0;
			foreach ( $results as $k => $v ) {
				if ( empty( $answer_options[ $v->question_id ] ) ) {
					$answer_options[ $v->question_id ] = array();
				}
				$answer_options[ $v->question_id ][] = (array) $v;
				$kk                                  = sizeof( $answer_options[ $v->question_id ] );

				if ( $answer_data = maybe_unserialize( $v->answer_data ) ) {
					foreach ( $answer_data as $data_key => $data_value ) {
						$answer_options[ $v->question_id ][ $kk ][ $data_key ] = $data_value;
					}
				}
				unset( $answer_options[ $v->question_id ][ $kk ]['answer_data'] );
				if ( empty( $meta_ids[ $group ] ) ) {
					$meta_ids[ $group ] = array();
				}
				$meta_ids[ $group ][] = $v->question_answer_id;
				$group                = ceil( sizeof( $answer_options ) / 5 ) - 1;
			}
			foreach ( $answer_options as $question_id => $options ) {
				wp_cache_set( 'answer-options-' . $question_id, $options, 'lp-questions' );
			}

			foreach ( $meta_ids as $meta_id ) {
				$this->_load_question_answer_meta( $meta_id );
			}

			$this->_load_answer_option_meta( $answer_options );
		}
		//

	}

	protected function _load_question_answer_meta( $meta_ids ) {
		global $wpdb;
		$format = array_fill( 0, sizeof( $meta_ids ), '%d' );
		$query  = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_question_answermeta}
			WHERE learnpress_question_answer_id IN(" . join( ',', $format ) . ")
		", $meta_ids );
		if ( $metas = $wpdb->get_results( $query ) ) {
			foreach ( $metas as $meta ) {
				$key        = $meta->meta_key;
				$option_key = $meta->learnpress_question_answer_id;
				if ( ! empty( $answer_options[ $option_key ] ) ) {
					if ( $key == 'checked' ) {
						$key = 'is_true';
					}
					$answer_options[ $option_key ][ $key ] = $meta->meta_value;
				}
			}
		}
	}

	/**
	 * Reorder question by indexed number
	 */
	public function sanitize_question_orders() {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT quiz_question_id as id
			FROM {$wpdb->prefix}learnpress_quiz_questions
			WHERE quiz_id = %d
			ORDER BY question_order ASC
		", $this->get_id() );
		if ( $rows = $wpdb->get_results( $query ) ) {
			$update = array();
			$ids    = wp_list_pluck( $rows, 'id' );
			$format = array_fill( 0, sizeof( $ids ), '%d' );
			foreach ( $rows as $order => $row ) {
				$update[] = $wpdb->prepare( "WHEN quiz_question_id = %d THEN %d", $row->id, $order + 1 );
			}
			$query = $wpdb->prepare( "
				UPDATE {$wpdb->prefix}learnpress_quiz_questions
				SET question_order = CASE
				" . join( "\n", $update ) . "
				ELSE question_order END
				WHERE quiz_question_id IN(" . join( ',', $format ) . ")
			", $ids );
			$wpdb->query( $query );
		}
	}

	/**
	 * Get all questions in a quiz
	 *
	 * @param bool $force
	 *
	 * @return array|mixed
	 */
	public function get_questions( $force = false ) {
		$no_cache = $this->get_no_cache();
		if ( $force ) {
			$this->set_no_cache( true );
		} else {
			$this->set_no_cache( false );
		}

		$questions = apply_filters( 'learn_press_quiz_questions', wp_cache_get( 'questions-' . $this->get_id(), 'lp-quizzes' ), $this->get_id() );
		$this->set_no_cache( $no_cache );

		return $questions;
	}

	/**
	 * Add existing question into quiz.
	 *
	 * @param       $question_id
	 * @param array $args
	 *
	 * @return int|bool false on failed
	 */
	public function add_question( $question_id, $args = array() ) {
		global $wpdb;
		$id = $this->get_id();

		if ( ! $question = learn_press_get_question( $question_id ) ) {
			return false;
		}

		if ( $this->is_exists_question( $question_id ) ) {
			return false;
		}

		$args = wp_parse_args( $args, array( 'order' => - 1 ) );
		$this->sanitize_question_orders();
		if ( $args['order'] >= 0 ) {
			$query = $wpdb->prepare( "
				UPDATE {$wpdb->prefix}learnpress_quiz_questions
				SET question_order = question_order + 1
				WHERE quiz_id = %d AND question_order >= %d
			", $id, $args['order'] );
			$wpdb->get_results( $query );
		} else {
			$query = $wpdb->prepare( "
				SELECT max(question_order) + 1 as ordering
				FROM {$wpdb->prefix}learnpress_quiz_questions
				WHERE quiz_id = %d
			", $id );
			if ( ! $order = $wpdb->get_var( $query ) ) {
				$order = 1;
			}
			$args['order'] = $order;
		}
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'learnpress_quiz_questions',
			array(
				'quiz_id'        => $id,
				'question_id'    => $question_id,
				'question_order' => $args['order']
			),
			array( '%d', '%d', '%d' )
		);
		print_r( $wpdb );

		return $inserted ? $wpdb->insert_id : $inserted;
	}

	/**
	 * Check if a question (or batch of questions) is already added to quiz.
	 *
	 * @param int|array $ids
	 *
	 * @return array|bool|null|object
	 */
	public function is_exists_question( $ids ) {
		global $wpdb;
		settype( $ids, 'array' );
		$format = array_fill( 0, sizeof( $ids ), '%d' );
		$args   = $ids;
		$args[] = $this->get_id();
		$query  = $wpdb->prepare( "
			SELECT quiz_question_id 
			FROM {$wpdb->learnpress_quiz_questions} 
			WHERE question_id IN( " . join( ',', $format ) . " )
				AND quiz_id = %d
		", $args );
		if ( $results = $wpdb->get_results( $query ) ) {
			return $results;
		}

		return false;
	}

	/**
	 * Remove a question from list of questions
	 *
	 * @param int|array $question_id ID of the question to remove
	 * @param mixed     $args        Extra options
	 *
	 * @return mixed         false on failed
	 */
	public function remove_question( $question_id, $args = array() ) {
		global $wpdb;
		$id       = $this->get_id();
		$args     = wp_parse_args( $args, array( 'delete_permanently' => false ) );
		$is_multi = is_array( $question_id );

		$ids = $question_id;
		settype( $ids, 'array' );
		$results = array();
		foreach ( $ids as $question_id ) {
			do_action( 'learn-press/delete-quiz-question', $question_id, $id );
			$deleted = $wpdb->delete(
				$wpdb->prefix . 'learnpress_quiz_questions',
				array(
					'quiz_id'     => $id,
					'question_id' => $question_id
				),
				array( '%d', '%d' )
			);
			$this->sanitize_question_orders();
			do_action( 'learn-press/deleted-quiz-question', $question_id, $id, $deleted );
			if ( $deleted && $args['delete_permanently'] ) {
				LP_Question_Factory::delete_question( $question_id, $id );
			}
			$results[ $question_id ] = $deleted;
		}

		return $is_multi ? $results : $deleted;
	}

	/**
	 * Update quiz questions.
	 *
	 * @param array $questions An array of questions need to update.
	 *
	 * @return mixed
	 */
	public function update_questions( $questions ) {
		if ( ! $questions ) {
			return false;
		}
		$this->_maybe_sort_questions( $questions );
		$orders = array();
		foreach ( $questions as $question_id => $data ) {
			// If the ID is not a numeric, then add new question
			$is_new = false;
			if ( ! is_numeric( $question_id ) ) {
				if ( $new_question_id = LP_Question_Factory::add_question( $data ) ) {
					$question = learn_press_get_question( $new_question_id );
					$this->add_question( $new_question_id, $data );
				}
				$is_new = true;
			} else {
				$question = learn_press_get_question( $question_id );
			}

			if ( isset( $question ) && $question instanceof LP_Question ) {
				// Do not need to update a new question.
				if ( $is_new ) {
					$orders[ $question_id ] = $question->get_id();
				} else {
					$question->set_data( $data );
					if ( $question_id = $question->store() ) {
						$orders[ $question_id ] = $question_id;
					}
				}
			}
		}

		$this->update_questions_orders( $orders );

		return $orders;
	}

	/**
	 * Reorder questions ordering.
	 *
	 * @param array $ids The array of question ids
	 */
	public function update_questions_orders( $ids ) {
		global $wpdb;
		$query = "
			UPDATE {$wpdb->learnpress_quiz_questions} 
			SET question_order = CASE
		";
		for ( $order = 0, $n = sizeof( $ids ); $order < $n; $order ++ ) {
			$query .= $wpdb->prepare( "WHEN question_id = %d THEN %d", $ids   [ $order ], $order + 1 ) . "\n";
		}
		$query .= sprintf( "ELSE question_order END WHERE quiz_id = %d", $this->get_id() );
		$wpdb->query( $query );
	}

	/******/


	protected function _init() {

		add_action( 'wp_head', array( $this, 'frontend_assets' ) );
	}

	public function get_duration_html() {
		$duration = $this->duration;
		if ( $duration ) {
			$duration = learn_press_seconds_to_time( $duration );
		} else {
			$duration = __( 'Unlimited', 'learnpress' );
		}

		return apply_filters( 'learn_press_quiz_duration_html', $duration, $this );
	}

	public function get_total_questions() {
		$questions = $this->questions;
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
		$key         = sprintf( '%d-%d-%d', $user_id, $course_id, $this->id );
		if ( ! array_key_exists( $key, $quiz_params ) || $force ) {

			if ( $results = $user->get_quiz_results( $this->id, $course_id, $force ) ) {
				$questions = $results->questions;
			} else {
				$questions = learn_press_get_quiz_questions();
				$questions = array_keys( $questions );
			}

			$current_question_id = $user->get_current_quiz_question( $this->id, $course->id );
			$question            = LP_Question_Factory::get_question( $current_question_id );
			$duration            = $this->duration;
			$remaining           = $user->get_quiz_time_remaining( $this->id, $course_id );
			if ( $remaining === false ) {
				$remaining = $this->duration;
			} elseif ( $remaining < 0 ) {
				$remaining = 0;
			}
			//$r_time              = ( $remaining > 0 ) && !in_array( $user->get_quiz_status( $this->id, $course_id, $force ), array( '', 'completed' ) ) ? $remaining : $this->duration;

			$js = array(
				'id'              => $this->id,
				'questions'       => array_values( $this->get_question_params( $questions, $current_question_id ) ),
				//$questions,
				'status'          => $user->get_quiz_status( $this->id, $course_id, $force ),
				'permalink'       => get_the_permalink(),
				'ajaxurl'         => admin_url( 'admin-ajax.php' ),
				'question'        => $question ? array( 'check_answer' => $question->can_check_answer() ) : false,
				'totalTime'       => $this->duration,
				'userTime'        => $duration - $remaining,
				'currentQuestion' => get_post_field( 'post_name', $current_question_id ),
				'usePermalink'    => get_option( 'permalink' ),
				'courseId'        => $course_id
			);
			if ( $js['status'] == 'completed' ) {
				$js['result'] = $user->get_quiz_results( $this->id, $course_id, $force );
			}
			if ( $js['status'] == 'started' ) {
				if ( $history = $user->get_quiz_results( $this->id, $course_id ) ) {
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
				if ( $history = $user->get_quiz_results( $this->id ) ) {
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

		return apply_filters( 'learn_press_quiz_param_questions', $results, $this->id );
	}

	public function frontend_assets() {
		if ( learn_press_is_course() && ( $quiz = LP()->global['course-item'] ) && $quiz->id == $this->id ) {
			$translate = $this->get_localize();
			LP_Assets::add_localize( $translate, false, 'learn-press-single-quiz' );
			LP_Assets::add_param( $this->get_settings(), false, 'learn-press-single-quiz' );
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
			case 'ID':
				$value = $this->id;
				break;
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
				break;
			case 'title':
				$value = $this->post->post_title;
				break;

			default:
				if ( array_key_exists( $key, self::$_meta[ $this->id ] ) ) {
					$value = self::$_meta[ $this->id ][ $key ];
				} else {
					if ( strpos( $key, '_lp_' ) === false ) {
						$key = '_lp_' . $key;
					}
					$value = get_post_meta( $this->id, $key, true );
					if ( $key == '_lp_duration' ) {
						$value = learn_press_human_time_to_seconds( $value );
					} elseif ( $key == '_lp_retake_count' ) {
						$value = absint( $value );
					}
					self::$_meta[ $this->id ][ $key ] = $value;
				}
		}
		if ( ! empty( $value ) ) {
			$this->$key = $value;
		}

		return $value;
	}

	public function get_content() {
		if ( ! did_action( 'learn_press_get_content_' . $this->id ) ) {
			global $post, $wp_query;
			$post = get_post( $this->id );
			//$posts = apply_filters( 'the_posts', array( $post ), $wp_query );
			$posts = apply_filters_ref_array( 'the_posts', array( array( $post ), &$wp_query ) );

			if ( $posts ) {
				$post = $posts[0];
			}
			setup_postdata( $post );
			ob_start();
			the_content();
			$this->content = ob_get_clean();
			wp_reset_postdata();
			do_action( 'learn_press_get_content_' . $this->id );
		}

		return $this->content;
	}

	/**
	 * Get the quiz's post data.
	 *
	 * @return object
	 */
	public function get_quiz_data() {
		return $this->post;
	}

	public function is_require_enrollment() {
		return $this->course && $this->course->is_require_enrollment();
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
				", $this->id );
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

	public function get_user_id() {
		if ( empty( $this->user_id ) ) {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				if ( ! $this->is_require_enrollment() ) {
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
	 * @return LP_Quiz
	 */
	public static function get_quiz( $the_quiz = false, $args = array() ) {
		//$the_quiz = self::get_quiz_object( $the_quiz );
		if ( ! $the_quiz ) {
			return false;
		}

		return new LP_Quiz( $the_quiz, $args );
	}

	public function get_quiz_result( $quiz_id = null ) {
		return false;
	}


	/**
	 * Get all questions of a quiz from database
	 *
	 * @return array|null|object
	 */
	private function _get_questions() {
		global $wpdb;
		$query               = $wpdb->prepare( "
			SELECT p.*, IF(pm.meta_value, pm.meta_value, 1) as mark
			FROM {$wpdb->learnpress_quiz_questions} qq INNER JOIN {$wpdb->posts} p ON p.ID = qq.question_id AND p.post_type = %s
			INNER JOIN {$wpdb->posts} q ON q.ID = qq.quiz_id AND q.post_type = %s
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = qq.question_id AND pm.meta_key = %s
			WHERE qq.quiz_id = %d
			ORDER BY qq.question_order ASC
		", 'lp_question', 'lp_quiz', '_lp_mark', $this->id );
		self::$_meta['mark'] = 0;
		if ( $questions = $wpdb->get_results( $query, OBJECT_K ) ) {
			$question_ids = array_keys( $questions );

			$answers = $this->_get_question_answers( $question_ids );
			foreach ( $questions as $id => $question ) {
				$answer_data = array( 'type' => 'true_or_false' );
				// Fetch answers for questions if exists
				if ( $answers ) {
					$question->answers = array();
					for ( $n = sizeof( $answers ), $i = $n - 1; $i >= 0; $i -- ) {
						if ( $answers[ $i ]->id != $question->ID ) {
							break;
						}
						$answers[ $i ]->answer_data                              = maybe_unserialize( $answers[ $i ]->answer_data );
						$answer_data                                             = array_merge( $answer_data, $answers[ $i ]->answer_data );
						$answer_data['id']                                       = $answers[ $i ]->question_answer_id;
						$answer_data['order']                                    = $answers[ $i ]->answer_order;
						$answer_data['type']                                     = $answers[ $i ]->type;
						$question->answers[ $answers[ $i ]->question_answer_id ] = $answer_data;
						unset( $answers[ $i ] );
					}
				}
				$question->type = $answer_data['type'];
				/**
				 * Add item to 'posts' cache group
				 */
				$item_post = wp_cache_get( $question->ID, 'posts' );
				if ( $item_post ) {
					wp_cache_delete( $question->ID, 'posts' );
				}
				$add = wp_cache_add( $question->ID, $question, 'posts' );

				// update mark of quiz
				$this->_mark += absint( $question->mark );
			}
			// Update meta cache
			update_meta_cache( 'post', $question_ids );
		}

		return $questions;
	}

	private function _get_question_answers( $question_ids ) {
		global $wpdb;
		$format_ids   = array_fill( 0, sizeof( $question_ids ), '%d' );
		$prepare_args = array_merge( array( '_lp_type', 'lp_question' ), $question_ids );
		$query        = $wpdb->prepare( "
			SELECT qa.question_answer_id, ID as id, pm.meta_value as type, qa.answer_data as answer_data, answer_order
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			INNER JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON qq.question_id = p.ID
			RIGHT JOIN {$wpdb->prefix}learnpress_question_answers qa ON qa.question_id = p.ID
			WHERE post_type = %s
			AND ID IN(" . join( ',', $format_ids ) . ")
			ORDER BY qq.question_order DESC, answer_order DESC
		", $prepare_args );

		return $wpdb->get_results( $query );
	}

	public function get_questions_( $force = false ) {
		$questions = LP_Cache::get_quiz_questions( false, array() );
		if ( ! array_key_exists( 'questions', self::$_meta[ $this->id ] ) || $force ) {
			self::$_meta[ $this->id ]['questions'] = array();
			global $wpdb;
			/*$query = $wpdb->prepare( "
					SELECT q.*, qq.params
					FROM {$wpdb->posts} q
					INNER JOIN {$wpdb->learnpress_quiz_questions} qq ON qq.question_id = q.ID
					AND q.post_type = %s
					AND qq.quiz_id = %d
				", LP_QUESTION_CPT, $this->id );*/

			$query = $wpdb->prepare( "
			SELECT qa.question_answer_id, ID as id, pm.meta_value as type, qa.answer_data as answer_data, answer_order
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			INNER JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON qq.question_id = p.ID
			RIGHT JOIN {$wpdb->prefix}learnpress_question_answers qa ON qa.question_id = p.ID
			WHERE ID IN(" . join( ',', $ids ) . ")
			AND post_type = %s
			ORDER BY qq.question_order, answer_order ASC
		", '_lp_type', 'lp_question' );

			if ( $this_questions = $wpdb->get_results( $query, OBJECT_K ) ) {
				$question_ids = array();
				foreach ( $this_questions as $id => $question ) {
					$question->params                              = maybe_unserialize( $question->params );
					self::$_meta[ $this->id ]['questions'][ $id ]  = $question;
					$GLOBALS['learnpress_question_answers'][ $id ] = array();

					/**
					 * Add item to 'posts' cache group
					 */
					$item_post = wp_cache_get( $question->ID, 'posts' );
					if ( ! $item_post ) {
						wp_cache_add( $question->ID, $question, 'posts' );
					}
					$question_ids[] = $question->ID;
				}
				// Update cache
				update_meta_cache( 'post', $question_ids );

				if ( $answers = $wpdb->get_results( "
					SELECT *
					FROM {$wpdb->learnpress_question_answers}
					WHERE question_id IN(" . join( ',', array_keys( $this_questions ) ) . ")
					ORDER BY question_id, answer_order ASC
				" )
				) {

					foreach ( $answers as $answer ) {
						$GLOBALS['learnpress_question_answers'][ $answer->question_id ][ $answer->question_answer_id ]       = (array) maybe_unserialize( $answer->answer_data );
						$GLOBALS['learnpress_question_answers'][ $answer->question_id ][ $answer->question_answer_id ]['id'] = $answer->question_answer_id;
					}
					//print_r($GLOBALS['learnpress_question_answers']);
				}
			}
		} else {
			$this_questions = self::$_meta[ $this->id ]['questions'];
		}

		return apply_filters( 'learn_press_quiz_questions', $this_questions, $this->id, $force );
	}

	public function get_buttons() {

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

	public function has_questions() {
		return $this->get_questions();
	}

	public function has_question( $question_id ) {
		return is_array( $this->questions ) && isset( $this->questions[ $question_id ] );
	}

	/**
	 * Return total mark of quiz
	 *
	 * @param bool $force
	 *
	 * @return mixed|void
	 */
	public function get_mark( $force = false ) {
		if ( $this->_mark === null || $force ) {
			$this->_get_questions();
		}

		return apply_filters( 'learn_press_quiz_mark', $this->_mark, $this->id );
	}

	public function get_question_link( $question_id = null ) {
		$course = LP()->global['course'];

		$permalink     = $course->get_item_link( $this->id );
		$question_name = get_post_field( 'post_name', $question_id );
		if ( '' != get_option( 'permalink_structure' ) && get_post_status( $this->id ) != 'draft' ) {
			$permalink = $permalink . $question_name;
		} else {
			$permalink = add_query_arg( array( 'question', $question_name ), $permalink );
		}

		return apply_filters( 'learn_press_quiz_question_permalink', $permalink, $question_id, $this );
	}

	public function get_question_param( $name, $id ) {
		if ( $this->questions ) {
			if ( ! empty( $this->questions[ $id ] ) ) {
				return ! empty( $this->questions[ $id ]->params[ $name ] ) ? $this->questions[ $id ]->params[ $name ] : null;
			}
		}

		return false;
	}

	public function check_question( $question_id, $user_id ) {

		if ( ! $question = LP_Question_Factory::get_question( $question_id ) ) {
			return false;
		}

		$user = learn_press_get_user( $user_id );

		$history = $user->get_quiz_results( $this->id );
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
		} elseif ( ! ( $the_quiz instanceof WP_Post ) ) {
			$the_quiz = false;
		}

		return apply_filters( 'learn_press_quiz_object', $the_quiz );
	}

	public function get_current_question( $user_id = 0, $course_id = 0 ) {
		$user = learn_press_get_current_user( $user_id );
		$id   = $user->get_current_quiz_question( $this->id, $course_id );

		return LP_Question_Factory::get_question( $id );
	}
}