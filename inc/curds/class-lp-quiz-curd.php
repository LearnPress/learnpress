<?php

/**
 * Class LP_Quiz_CURD
 *
 * Class to manipulating quiz with database.
 */
class LP_Quiz_CURD extends LP_Object_Data_CURD implements LP_Interface_CURD {
	/**
	 * Errors codes and message.
	 *
	 * @var array|bool
	 */
	protected $_error_messages = false;

	/**
	 * LP_Quiz_CURD constructor.
	 */
	public function __construct() {
		$this->_error_messages = array(
			'QUIZ_NOT_EXISTS' => __( 'Quiz does not exists.', 'learnpress' )
		);
	}

	/**
	 * @param LP_Quiz $quiz
	 *
	 * @return LP_Quiz|mixed
	 * @throws Exception
	 */
	public function load( &$quiz ) {

		$the_id = $quiz->get_id();

		if ( ! $the_id || ! in_array( get_post_type( $the_id ), array( LP_QUIZ_CPT, LP_QUESTION_CPT ) ) ) {
			throw new Exception( __( 'Invalid quiz.', 'learnpress' ) );
		}
		$quiz->set_data_via_methods(
			array(
				'retake_count'       => get_post_meta( $quiz->get_id(), '_lp_retake_count', true ),
				'show_result'        => get_post_meta( $quiz->get_id(), '_lp_show_result', true ),
				'passing_grade_type' => get_post_meta( $quiz->get_id(), '_lp_passing_grade_type', true ),
				'passing_grade'      => get_post_meta( $quiz->get_id(), '_lp_passing_grade', true ),
				'show_check_answer'  => get_post_meta( $quiz->get_id(), '_lp_show_check_answer', true ),
				'count_check_answer' => get_post_meta( $quiz->get_id(), '_lp_check_answer_count', true ),
				'show_hint'          => get_post_meta( $quiz->get_id(), '_lp_show_hint', true ),
				'archive_history'    => get_post_meta( $quiz->get_id(), '_lp_archive_history', true ),
				'count_hint'         => get_post_meta( $quiz->get_id(), '_lp_hint_count', true ),
			)
		);
		$this->_load_questions( $quiz );
		$this->_update_meta_cache( $quiz );

		return $quiz;
	}

	public function create( &$quiz ) {
		// TODO: Implement update() method.
	}

	public function update( &$quiz ) {
		// TODO: Implement update() method.
	}

	public function delete( &$quiz ) {
		// TODO: Implement delete() method.
	}

	/**
	 * Duplicate quiz.
	 *
	 * @since 3.0.0
	 *
	 * @param $quiz_id
	 * @param array $args
	 *
	 * @return mixed|WP_Error
	 */
	public function duplicate( &$quiz_id, $args = array() ) {

		if ( ! $quiz_id ) {
			return new WP_Error( __( '<p>Op! ID not found</p>', 'learnpress' ) );
		}

		if ( get_post_type( $quiz_id ) != LP_QUIZ_CPT ) {
			return new WP_Error( __( '<p>Op! The quiz does not exist</p>', 'learnpress' ) );
		}

		// ensure that user can create quiz
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( __( '<p>Sorry! You have not permission to duplicate this quiz</p>', 'learnpress' ) );
		}

		// duplicate quiz
		$new_quiz_id = learn_press_duplicate_post( $quiz_id, $args, true );

		if ( ! $new_quiz_id || is_wp_error( $new_quiz_id ) ) {
			return new WP_Error( __( '<p>Sorry! Duplicate quiz failed!</p>', 'learnpress' ) );
		} else {

			$quiz      = LP_Quiz::get_quiz( $quiz_id );
			$questions = $quiz->get_questions();

			// question curd
			$question_curd = new LP_Question_CURD();

			// duplicate questions in quiz
			if ( $questions ) {

				$questions = array_keys( $questions );

				foreach ( $questions as $question_id ) {

					// duplicate question
					$new_question_id = $question_curd->duplicate($question_id, array( 'post_status' => 'publish' ) );

					// add duplicate question to new quiz
					$this->add_question($new_quiz_id, $new_question_id );
				}
			}

			return $new_quiz_id;
		}

	}

	/**
	 * @param LP_Quiz $quiz
	 */
	protected function _load_questions( &$quiz ) {
		$id        = $quiz->get_id();
		$questions = wp_cache_get( 'questions-' . $id, 'lp-quizzes' );
		if ( false === $questions || $quiz->get_no_cache() ) {
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

			$this->_load_question_answers( $quiz );
		}
	}

	/**
	 * @param LP_Quiz $quiz
	 */
	protected function _update_meta_cache( &$quiz ) {
		$meta_ids = wp_cache_get( 'questions-' . $quiz->get_id(), 'lp-quizzes' );

		if ( false === $meta_ids ) {
			$meta_ids = array( $quiz->get_id() );
		} else {
			$meta_ids[] = $quiz->get_id();
		}
		update_meta_cache( 'post', $meta_ids );
	}

	/**
	 * Load answer quiz's questions.
	 */
	protected function _load_question_answers( &$quiz ) {
		global $wpdb;

		if ( ! $questions = $this->get_questions( $quiz ) ) {
			return;
		}

		$format = array_fill( 0, sizeof( $questions ), '%d' );
		$query  = $wpdb->prepare( "
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
				$v = (array) $v;
				if ( $answer_data = maybe_unserialize( $v['answer_data'] ) ) {
					foreach ( $answer_data as $kk => $vv ) {
						$v[ $kk ] = $vv;
					}
				}
				unset( $v['answer_data'] );


				$answer_options[ $v['question_id'] ][] = $v;
				/*$kk                                  = sizeof( $answer_options[ $v->question_id ] );

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
				$group                = ceil( sizeof( $answer_options ) / 5 ) - 1;*/


			}
//			learn_press_debug( $results );
//			learn_press_debug( $answer_options );
//			die();
			foreach ( $answer_options as $question_id => $options ) {
				wp_cache_set( 'answer-options-' . $question_id, $options, 'lp-questions' );
			}

			foreach ( $meta_ids as $meta_id ) {
				//$this->_load_question_answer_meta( $meta_id );
			}

			$fetched    = array_keys( $answer_options );
			$un_fetched = array_diff( $questions, $fetched );
			//$this->_load_question_answer_meta( $answer_options );
		} else {
			$un_fetched = $questions;
		}
		if ( $un_fetched ) {
			foreach ( $un_fetched as $question_id ) {
				wp_cache_set( 'answer-options-' . $question_id, array(), 'lp-questions' );
			}
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

		uasort( $questions, array( $this, '_callback_sort_questions' ) );

		return $questions;
	}

	public function _callback_sort_questions( $a, $b ) {
		return $a['order'] > $b['order'];
	}

	/**
	 * Reorder question by indexed number.
	 *
	 * @param LP_Quiz|WP_Post|int $the_quiz
	 * @param mixed $questions
	 *
	 * @return mixed
	 */
	public function reorder_questions( $the_quiz, $questions = false ) {
		global $wpdb;
		if ( ! $the_quiz = learn_press_get_quiz( $the_quiz ) ) {
			return false;
		}
		if ( false == $questions ) {
			$query = $wpdb->prepare( "
				SELECT quiz_question_id as id
				FROM {$wpdb->prefix}learnpress_quiz_questions
				WHERE quiz_id = %d
				ORDER BY question_order ASC
			", $the_quiz->get_id() );
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

				return $wpdb->query( $query );
			}
		} else {
			$query = "
				UPDATE {$wpdb->learnpress_quiz_questions} 
				SET question_order = CASE
			";
			for ( $order = 0, $n = sizeof( $questions ); $order < $n; $order ++ ) {
				$query .= $wpdb->prepare( "WHEN question_id = %d THEN %d", $questions[ $order ], $order + 1 ) . "\n";
			}
			$query .= sprintf( "ELSE question_order END WHERE quiz_id = %d", $the_quiz->get_id() );

			return $wpdb->query( $query );
		}

		return false;
	}


	/**
	 * Get all questions in a quiz
	 *
	 * @param LP_Quiz $the_quiz
	 *
	 * @return array|mixed
	 */
	public function get_questions( $the_quiz ) {
		if ( ! $the_quiz = learn_press_get_quiz( $the_quiz ) ) {
			return $this->get_error( 'QUESTION_NOT_EXISTS' );
		}

		return wp_cache_get( 'questions-' . $the_quiz->get_id(), 'lp-quizzes' );
	}

	/**
	 * Add existing question into quiz.
	 *
	 * @param LP_Quiz|int $the_quiz
	 * @param             $question_id
	 * @param array $args
	 *
	 * @return mixed false on failed
	 */
	public function add_question( $the_quiz, $question_id, $args = array() ) {
		if ( ! $the_quiz = learn_press_get_quiz( $the_quiz ) ) {
			return $this->get_error( 'QUIZ_NOT_EXISTS' );
		}
		if ( ! $question = learn_press_get_question( $question_id ) ) {
			return false;
		}

		if ( $this->is_exists_question( $the_quiz->get_id(), $question_id ) ) {
			return false;
		}

		global $wpdb;
		$id   = $the_quiz->get_id();
		$args = wp_parse_args( $args, array( 'order' => - 1 ) );
		$this->reorder_questions( $the_quiz );
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

		return $inserted ? $wpdb->insert_id : $inserted;
	}

	/**
	 * Check if a question (or batch of questions) is already added to quiz.
	 *
	 * @param int $the_id
	 * @param int|array $ids
	 *
	 * @return array|bool|null|object
	 */
	public function is_exists_question( $the_id, $ids ) {
		global $wpdb;

		settype( $ids, 'array' );
		$format = array_fill( 0, sizeof( $ids ), '%d' );

		$questions_ids = implode( ', ', $ids );

		$query = $wpdb->prepare( "
			SELECT quiz_question_id 
			FROM {$wpdb->learnpress_quiz_questions} 
			WHERE question_id IN( " . join( ',', $format ) . " )
				AND quiz_id = %d
		", $questions_ids, $the_id );
		if ( $results = $wpdb->get_results( $query ) ) {
			return $results;
		}

		return false;
	}

	/**
	 * Update order question in quiz.
	 *
	 * @param $order
	 *
	 * @return false|int
	 */
	public function sort_questions( $order ) {
		global $wpdb;

		// number db row affected
		$rows_affected = 0;

		foreach ( $order as $index => $question_id ) {

			$update = $wpdb->update(
				$wpdb->learnpress_quiz_questions,
				array( 'question_order' => $index + 1 ),
				array( 'question_id' => $question_id )
			);

			if ( $update === false ) {
				return $update;
			} else {
				$rows_affected += $update;
			};

			// return 1 for successful, 0 for database error
			return $rows_affected ? 1 : 0;
		}

		// return -1 for don't update
		return - 1;

	}

	/**
	 * Remove question from quiz.
	 *
	 * @param $quiz
	 * @param $question_id
	 *
	 * @return bool|false|int|WP_Error
	 */
	public function remove_question( $quiz, $question_id ) {

		if ( ! $quiz = learn_press_get_quiz( $quiz ) ) {
			return $this->get_error( 'QUIZ_NOT_EXISTS' );
		}

		global $wpdb;

		// remove question from quiz
		$delete = $wpdb->delete(
			$wpdb->prefix . 'learnpress_quiz_questions',
			array(
				'quiz_id'     => $quiz->get_id(),
				'question_id' => $question_id
			),
			array( '%d', '%d' )
		);

		// reorder questions
		$this->reorder_questions( $quiz );
		// increment quiz questions
		learn_press_reset_auto_increment( 'learnpress_quiz_questions' );

		return $delete;
	}

	/**
	 * Delete permanently question from quiz.
	 *
	 * @param $quiz
	 * @param $question_id
	 *
	 * @return array|bool|false|WP_Post
	 */
	public function delete_question( $quiz, $question_id ) {

		$question_curd = new LP_Question_CURD();

		// remove question from quiz
		$this->remove_question( $quiz, $question_id );

		// delete permanently question
		$delete = $question_curd->delete_permanently_question( $question_id );

		return $delete;

	}

	/**
	 * Remove a question from list of questions
	 *
	 * @param LP_Quiz|int $the_quiz
	 * @param int|array $question_id ID of the question to remove
	 * @param mixed $args Extra options
	 *
	 * @return mixed         false on failed
	 */
	public function _remove_question( $the_quiz, $question_id, $args = array() ) {
		if ( ! $the_quiz = learn_press_get_quiz( $the_quiz ) ) {
			return $this->get_error( 'QUIZ_NOT_EXISTS' );
		}

		global $wpdb;
		$id         = $the_quiz->get_id();
		$args       = wp_parse_args( $args, array( 'delete_permanently' => false ) );
		$is_multi   = is_array( $question_id );
		$remove_all = $question_id === true ? true : false;
		$results    = array();

		if ( ! $remove_all ) {
			$ids = $question_id;
			settype( $ids, 'array' );
			$deleted = '';

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
				$this->reorder_questions( $the_quiz );
				do_action( 'learn-press/deleted-quiz-question', $question_id, $id, $deleted );
				if ( $deleted && $args['delete_permanently'] ) {
					LP_Question_Factory::delete_question( $question_id, $id );
				}
				$results[ $question_id ] = $deleted;
			}
		} else {
			$deleted = $wpdb->delete(
				$wpdb->prefix . 'learnpress_quiz_questions',
				array( 'quiz_id' => $id ),
				array( '%d' )
			);
		}

		learn_press_reset_auto_increment( 'learnpress_quiz_questions' );

		return $remove_all ? $deleted : ( $is_multi ? $results : $deleted );
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

		$this->reorder_questions( $orders );

		return $orders;
	}

	/**
	 * Update order quiz question answers.
	 *
	 * @since 3.0.0
	 *
	 * @param $answers
	 *
	 * @return array
	 */
	public function sort_question_answers( $answers ) {
		global $wpdb;

		$orders = array();

		foreach ( $answers as $index => $answer_id ) {
			$order = $index + 1;

			$orders[ $answer_id ] = $order;

			$wpdb->update(
				$wpdb->learnpress_question_answers,
				array( 'answer_order' => $order ),
				array( 'question_answer_id' => $answer_id )
			);
		}

		return $orders;
	}

	public function add_meta( &$object, $meta ) {
		// TODO: Implement add_meta() method.
	}

	public function delete_meta( &$object, $meta ) {
		// TODO: Implement delete_meta() method.
	}

	public function read_meta( &$object ) {
		// TODO: Implement read_meta() method.
	}

	/**
	 * @param $object
	 * @param $meta
	 *
	 * @return mixed
	 */
	public function update_meta( &$object, $meta ) {
		return learn_press_update_user_item_meta( $object->get_user_item_id(), $meta->meta_key, $meta->meta_value );
	}

	/**
	 * Get WP_Object.
	 *
	 * @param $code
	 *
	 * @return bool|WP_Error
	 */
	protected function get_error( $code ) {
		if ( isset( $this->_error_messages[ $code ] ) ) {
			return new WP_Error( $code, $this->_error_messages[ $code ] );
		}

		return false;
	}
}