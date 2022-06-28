<?php
/**
 * Class LP_Quiz_CURD.
 *
 * @author  ThimPress
 * @package LearnPress/Classes/CURD
 * @since   3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! function_exists( 'LP_Quiz_CURD' ) ) {

	/**
	 * Class LP_Quiz_CURD
	 *
	 * Class to manipulating quiz with database.
	 */
	class LP_Quiz_CURD extends LP_Object_Data_CURD implements LP_Interface_CURD {

		/**
		 * LP_Quiz_CURD constructor.
		 */
		public function __construct() {
			$this->_error_messages = array(
				'QUIZ_NOT_EXISTS' => __( 'Quiz does not exist.', 'learnpress' ),
			);
		}

		/**
		 * @param LP_Quiz $quiz
		 *
		 * @return LP_Quiz|mixed
		 * @throws Exception
		 */
		public function load( &$quiz ) {
			$id = $quiz->get_id();
			if ( ! $id || learn_press_get_post_type( $id ) !== LP_QUIZ_CPT ) {

				throw new Exception( sprintf( __( 'Invalid quiz with ID "%d".', 'learnpress' ), $id ) );
			}

			// LP_Helper_CURD::update_meta_cache( $quiz->get_id() );

			$quiz->set_data_via_methods(
				array(
					// 'show_result'          => get_post_meta( $quiz->get_id(), '_lp_show_result', true ),
					'passing_grade_type'   => get_post_meta( $quiz->get_id(), '_lp_passing_grade_type', true ),
					'passing_grade'        => get_post_meta( $quiz->get_id(), '_lp_passing_grade', true ),
					// 'show_check_answer'    => get_post_meta( $quiz->get_id(), '_lp_show_check_answer', true ),
					// 'count_check_answer'   => get_post_meta( $quiz->get_id(), '_lp_check_answer_count', true ),
					// 'show_hint'            => get_post_meta( $quiz->get_id(), '_lp_show_hint', true ),
					// 'archive_history'      => get_post_meta( $quiz->get_id(), '_lp_archive_history', true ),
					// 'count_hint'           => get_post_meta( $quiz->get_id(), '_lp_hint_count', true ),
					'pagination'           => get_post_meta( $quiz->get_id(), '_lp_pagination', true ),
					'review_questions'     => get_post_meta( $quiz->get_id(), '_lp_review', true ),
					'show_correct_review'  => get_post_meta( $quiz->get_id(), '_lp_show_correct_review', true ),
					'preview'              => get_post_meta( $quiz->get_id(), '_lp_preview', true ),
					// 'minus_points'         => get_post_meta( $quiz->get_id(), '_lp_minus_points', true ),
					'minus_skip_questions' => get_post_meta( $quiz->get_id(), '_lp_minus_skip_questions', true ),

					'instant_check'        => get_post_meta( $quiz->get_id(), '_lp_instant_check', true ),
					'negative_marking'     => get_post_meta( $quiz->get_id(), '_lp_negative_marking', true ),
					'retake_count'         => get_post_meta( $quiz->get_id(), '_lp_retake_count', true ),
				)
			);

			return $quiz;
		}

		/**
		 * Create quiz, with default meta.
		 *
		 * @param  $args
		 *
		 * @return int|WP_Error
		 */
		public function create( &$args ) {

			$args = wp_parse_args(
				$args,
				array(
					'id'      => '',
					'status'  => 'publish',
					'title'   => __( 'New Quiz', 'learnpress' ),
					'content' => '',
					'author'  => learn_press_get_current_user_id(),
				)
			);

			$quiz_id = wp_insert_post(
				array(
					'ID'           => $args['id'],
					'post_type'    => LP_QUIZ_CPT,
					'post_status'  => $args['status'],
					'post_title'   => $args['title'],
					'post_content' => $args['content'],
					'post_author'  => $args['author'],
				)
			);

			if ( $quiz_id ) {
				// add default meta for new lesson
				$default_meta = LP_Quiz::get_default_meta();

				if ( is_array( $default_meta ) ) {
					foreach ( $default_meta as $key => $value ) {
						update_post_meta( $quiz_id, '_lp_' . $key, $value );
					}
				}
			}

			return $quiz_id;
		}

		public function update( &$quiz ) {
			// TODO: Implement update() method.
		}

		/**
		 * Delete quiz.
		 *
		 * @since 3.0.0
		 *
		 * @param object $quiz_id
		 */
		public function delete( &$quiz_id ) {
			// course curd
			$curd = new LP_Course_CURD();

			// allow hook
			do_action( 'learn-press/before-delete-quiz', $quiz_id );

			// remove quiz from course items
			$curd->remove_item( $quiz_id );
			// remove questions from quiz
			$this->remove_questions( $quiz_id, '', true );
		}

		/**
		 * Duplicate quiz.
		 *
		 * @since 3.0.0
		 *
		 * @param       $quiz_id
		 * @param array   $args
		 *
		 * @return mixed|WP_Error
		 */
		public function duplicate( &$quiz_id, $args = array() ) {

			if ( ! $quiz_id ) {
				return new WP_Error( __( '<p>Op! ID not found</p>', 'learnpress' ) );
			}

			if ( learn_press_get_post_type( $quiz_id ) != LP_QUIZ_CPT ) {
				return new WP_Error( __( '<p>Op! The quiz does not exist</p>', 'learnpress' ) );
			}

			// ensure that user can create quiz
			if ( ! current_user_can( 'edit_posts' ) ) {
				return new WP_Error( __( '<p>Sorry! You have not permission to duplicate this quiz</p>', 'learnpress' ) );
			}

			// duplicate quiz
			$new_quiz_id = learn_press_duplicate_post( $quiz_id, $args, true );

			if ( ! $new_quiz_id || is_wp_error( $new_quiz_id ) ) {
				return new WP_Error( __( '<p>Sorry! Failed to duplicate quiz!</p>', 'learnpress' ) );
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
						$new_question_id = $question_curd->duplicate( $question_id, array( 'post_status' => 'publish' ) );

						// add duplicate question to new quiz
						$this->add_question( $new_quiz_id, $new_question_id );
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
			$questions = LP_Object_Cache::get( 'questions-' . $id, 'learn-press/quizzes' );

			if ( false === $questions || $quiz->get_no_cache() ) {
				$this->load_questions( $quiz->get_id() );
			}
		}

		/**
		 * Read question of a quiz from database
		 *
		 * @param int    $quiz_id
		 * @param string $context
		 *
		 * @return array|bool
		 */
		/*public function read_questions( $quiz_id, $context = 'display' ) {
			$quiz = learn_press_get_quiz( $quiz_id );

			if ( ! $quiz ) {
				return false;
			}

			return $quiz->get_question_ids( $context );
		}*/

		/**
		 * Read all question ids of a quiz.
		 *
		 * @param int    $quiz_id
		 * @param string $context
		 *
		 * @return array
		 * @throws Exception
		 */
		public function read_question_ids( int $quiz_id = 0, string $context = 'display' ) :array {
			$quiz = learn_press_get_quiz( $quiz_id );

			if ( ! $quiz ) {
				return array();
			}

			$lp_question_db = LP_Question_DB::getInstance();

			if ( $context === 'display' ) {
				$statuses = array( 'publish' );
			} else {
				$statuses = array( 'publish', 'draft', 'auto-draft' );
			}

			$filter          = new LP_Question_Filter();
			$filter->quiz_id = $quiz_id;
			$filter->statues = $statuses;

			return $lp_question_db->get_list_question_ids_of_quiz( $filter );
		}

		public function update_question_ids( $quiz_id ) {
			wp_cache_delete( 'quiz-' . $quiz_id, 'quiz-questions' );

			$quiz = learn_press_get_quiz( $quiz_id );

			if ( $quiz ) {
				$question_ids = $quiz->get_question_ids();
			} else {
				$question_ids = array();
			}

			update_post_meta( $quiz_id, '_questions', $question_ids );
			update_post_meta( $quiz_id, '_question_count', sizeof( $question_ids ) );
		}

		/**
		 * Get quiz ids that contains a question.
		 *
		 * @since 3.1.0
		 *
		 * @param int $question_id
		 *
		 * @return array
		 */
		public function get_quiz_by_question( $question_id ) {
			global $wpdb;
			$query = $wpdb->prepare(
				"
				SELECT ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->learnpress_quiz_questions} qq ON qq.quiz_id = p.ID
				WHERE qq.question_id = %d
			",
				$question_id
			);

			return $wpdb->get_col( $query );
		}

		public function load_questions( $quiz_id ) {

			global $wpdb;

			if ( is_array( $quiz_id ) ) {
				foreach ( $quiz_id as $q_id ) {
					$this->load_questions( $q_id );
				}

				return;
			}

			$query = $wpdb->prepare(
				"
				SELECT ID, post_title, post_content, post_status, post_type, post_author, post_date, post_name
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON p.ID = qq.question_id
				WHERE qq.quiz_id = %d
				AND p.post_status = %s
				ORDER BY question_order, quiz_question_id ASC
			",
				$quiz_id,
				'publish'
			);

			$question_ids = array();
			$results      = $wpdb->get_results( $query );

			if ( $results ) {
				foreach ( $results as $k => $v ) {
					settype( $v, 'object' );
					wp_cache_set( $v->ID, $v, 'posts' );

					$question_ids[] = $v->ID;
				}
			}

			LP_Object_Cache::set( 'questions-' . $quiz_id, $question_ids, 'learn-press/quizzes' );

			// LP_Helper_CURD::cache_posts( $question_ids );
			$question_factory = new LP_Question_CURD();
			$question_factory->load_answer_options( $question_ids );
		}

		/**
		 * @param LP_Quiz $quiz
		 * @depecated 4.1.6.4
		 */
		protected function _update_meta_cache( &$quiz ) {
			_deprecated_function( __FUNCTION__, '4.1.6.4' );
			$meta_ids = LP_Object_Cache::get( 'questions-' . $quiz->get_id(), 'learn-press/quizzes' );

			if ( false === $meta_ids ) {
				$meta_ids = array( $quiz->get_id() );
			} else {
				$meta_ids[] = $quiz->get_id();
			}
			// LP_Helper_CURD::update_meta_cache( $meta_ids );
		}

		/**
		 * @depecated 4.1.6.4
		 */
		protected function _load_question_answer_meta( $meta_ids ) {
			global $wpdb;

			$format = array_fill( 0, sizeof( $meta_ids ), '%d' );
			$query  = $wpdb->prepare(
				"
				SELECT *
				FROM {$wpdb->learnpress_question_answermeta}
				WHERE learnpress_question_answer_id IN(" . join( ',', $format ) . ')
			',
				$meta_ids
			);

			$metas = $wpdb->get_results( $query );

			if ( $metas ) {
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
		 * @param mixed               $questions
		 *
		 * @return mixed
		 */
		public function reorder_questions( $the_quiz, $questions = false ) {
			global $wpdb;

			$the_quiz = learn_press_get_quiz( $the_quiz );

			if ( ! $the_quiz ) {
				return false;
			}

			if ( false == $questions ) {
				$query = $wpdb->prepare(
					"
				SELECT quiz_question_id as id
				FROM {$wpdb->prefix}learnpress_quiz_questions
				WHERE quiz_id = %d
				ORDER BY question_order ASC
			",
					$the_quiz->get_id()
				);

				$rows = $wpdb->get_results( $query );

				if ( $rows ) {
					$update = array();
					$ids    = wp_list_pluck( $rows, 'id' );
					$format = array_fill( 0, sizeof( $ids ), '%d' );

					foreach ( $rows as $order => $row ) {
						$update[] = $wpdb->prepare( 'WHEN quiz_question_id = %d THEN %d', $row->id, $order + 1 );
					}

					$query = $wpdb->prepare(
						"
							UPDATE {$wpdb->prefix}learnpress_quiz_questions
							SET question_order = CASE
							" . join( "\n", $update ) . '
							ELSE question_order END
							WHERE quiz_question_id IN(' . join( ',', $format ) . ')
						',
						$ids
					);

					return $wpdb->query( $query );
				}
			} else {
				$query = "
				UPDATE {$wpdb->learnpress_quiz_questions}
				SET question_order = CASE
			";
				for ( $order = 0, $n = sizeof( $questions ); $order < $n; $order ++ ) {
					$query .= $wpdb->prepare( 'WHEN question_id = %d THEN %d', $questions[ $order ], $order + 1 ) . "\n";
				}
				$query .= sprintf( 'ELSE question_order END WHERE quiz_id = %d', $the_quiz->get_id() );

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
			$the_quiz = learn_press_get_quiz( $the_quiz );

			if ( ! $the_quiz ) {
				return $this->get_error( 'QUIZ_NOT_EXISTS' );
			}

			return LP_Object_Cache::get( 'questions-' . $the_quiz->get_id(), 'learn-press/quizzes' );
		}

		/**
		 * Add existing question into quiz.
		 *
		 * @param LP_Quiz|int $the_quiz
		 * @param             $question_id
		 * @param array       $args
		 *
		 * @return mixed false on failed
		 */
		public function add_question( $the_quiz, $question_id, $args = array() ) {
			$the_quiz = learn_press_get_quiz( $the_quiz );

			if ( ! $the_quiz ) {
				return $this->get_error( 'QUIZ_NOT_EXISTS' );
			}

			$question = learn_press_get_question( $question_id );

			if ( ! $question ) {
				return false;
			}

			if ( $this->is_exists_question( $the_quiz->get_id(), $question_id ) ) {
				return false;
			}

			// list exist quiz question
			$list_questions = $this->get_questions( $the_quiz );
			// add new question and set to cache
			$list_questions[ $question_id ] = strval( $question_id );
			LP_Object_Cache::set( 'questions-' . $the_quiz->get_id(), $list_questions, 'learn-press/quizzes' );

			global $wpdb;

			$id   = $the_quiz->get_id();
			$args = wp_parse_args( $args, array( 'order' => - 1 ) );

			if ( $args['order'] >= 0 ) {
				$query = $wpdb->prepare(
					"
						UPDATE {$wpdb->prefix}learnpress_quiz_questions
						SET question_order = question_order + 1
						WHERE quiz_id = %d AND question_order >= %d
					",
					$id,
					$args['order']
				);
				$wpdb->get_results( $query );
			} else {
				$query = $wpdb->prepare(
					"
				SELECT max(question_order) + 1 as ordering
				FROM {$wpdb->prefix}learnpress_quiz_questions
				WHERE quiz_id = %d
			",
					$id
				);

				$order = $wpdb->get_var( $query );

				if ( ! $order ) {
					$order = 1;
				}

				$args['order'] = $order;
			}

			$inserted = $wpdb->insert(
				$wpdb->prefix . 'learnpress_quiz_questions',
				array(
					'quiz_id'        => $id,
					'question_id'    => $question_id,
					'question_order' => $args['order'],
				),
				array( '%d', '%d', '%d' )
			);

			$return = $inserted ? $wpdb->insert_id : $inserted;

			do_action( 'learn-press/quiz-added-question', $inserted, $question_id, $the_quiz->get_id() );

			return $return;
		}

		/**
		 * Check if a question (or batch of questions) is already added to quiz.
		 *
		 * @param int       $the_id
		 * @param int|array $ids
		 *
		 * @return array|bool|null|object
		 */
		public function is_exists_question( $the_id, $ids ) {
			global $wpdb;

			settype( $ids, 'array' );
			$format = array_fill( 0, sizeof( $ids ), '%d' );

			$questions_ids = implode( ', ', $ids );

			$query = $wpdb->prepare(
				"
			SELECT quiz_question_id
			FROM {$wpdb->learnpress_quiz_questions}
			WHERE question_id IN( " . join( ',', $format ) . ' )
				AND quiz_id = %d
		',
				$questions_ids,
				$the_id
			);

			$results = $wpdb->get_results( $query );

			if ( $results ) {
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

			foreach ( $order as $index => $question_id ) {
				$update = $wpdb->update(
					$wpdb->learnpress_quiz_questions,
					array( 'question_order' => $index + 1 ),
					array( 'question_id' => $question_id ),
					array( '%d' ),
					array( '%d' )
				);

				if ( $update === false ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Remove question from quiz.
		 *
		 * @since 3.0.0
		 *
		 * @param      $quiz_id
		 * @param      $question_id
		 * @param bool        $force | remove all questions from quiz
		 *
		 * @return bool|false|int|WP_Error
		 */
		public function remove_questions( $quiz_id, $question_id, $force = false ) {
			$quiz = learn_press_get_quiz( $quiz_id );

			if ( ! $quiz ) {
				return $this->get_error( 'QUIZ_NOT_EXISTS' );
			}

			global $wpdb;

			if ( $force ) {
				// remove all questions from quiz
				$delete = $wpdb->delete(
					$wpdb->prefix . 'learnpress_quiz_questions',
					array( 'quiz_id' => $quiz_id ),
					array( '%d' )
				);
			} else {
				do_action( 'learn-press/delete-quiz-question', $question_id, $quiz_id );

				// remove question from quiz
				$delete = $wpdb->delete(
					$wpdb->prefix . 'learnpress_quiz_questions',
					array(
						'quiz_id'     => $quiz_id,
						'question_id' => $question_id,
					),
					array( '%d', '%d' )
				);

				do_action( 'learn-press/deleted-quiz-question', $question_id, $quiz_id, $delete );

				// reorder questions
				$this->reorder_questions( $quiz );
			}

			// increment quiz questions
			learn_press_reset_auto_increment( 'learnpress_quiz_questions' );

			return $delete;
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
					array( 'order' => $order ),
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
	}
}
