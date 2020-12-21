<?php

/**
 * Class LP_Question_CURD
 *
 * @author  ThimPress
 * @package LearnPress/Classes/CURD
 * @since   3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Question_CURD' ) ) {

	/**
	 * Class LP_Question_CURD
	 */
	class LP_Question_CURD extends LP_Object_Data_CURD implements LP_Interface_CURD {

		/**
		 * LP_Question_CURD constructor.
		 */
		public function __construct() {
			$this->_error_messages = array(
				'QUESTION_NOT_EXISTS' => __( 'Question does not exist.', 'learnpress' )
			);
		}

		/**
		 * Create question and can add to quiz.
		 *
		 * @param $args
		 *
		 * @return bool|int|LP_Question|WP_Error
		 */
		public function create( &$args ) {
			$args = wp_parse_args( $args, array(
					'quiz_id'        => 0,
					'order'          => - 1,
					'id'             => '',
					'status'         => 'publish',
					'type'           => 'true_or_false',
					'title'          => __( 'New Question', 'learnpress' ),
					'content'        => '',
					'create_answers' => true // some cases do not need create answers for new question
				)
			);

			// set question author for author of quiz
			if ( ! empty( $args['quiz_id'] ) ) {
				$user_id = get_post_field( 'post_author', $args['quiz_id'] );
			} else {
				$user_id = learn_press_get_current_user_id();
			}

			$question_id = wp_insert_post( array(
				'ID'           => $args['id'],
				'post_author'  => $user_id,
				'post_type'    => LP_QUESTION_CPT,
				'post_status'  => $args['status'],
				'post_title'   => $args['title'],
				'post_content' => $args['content'],
			) );

			if ( $question_id ) {

				// add default meta for new lesson
				$default_meta = LP_Question::get_default_meta();
				if ( is_array( $default_meta ) ) {
					foreach ( $default_meta as $key => $value ) {
						update_post_meta( $question_id, '_lp_' . $key, $value );
					}
				}
				update_post_meta( $question_id, '_lp_type', $args['type'] );
				// update user memory question types
				get_user_meta( $user_id, '_learn_press_memorize_question_types', $args['type'] );

				$question = LP_Question::get_question( $question_id, array( 'type' => $args['type'] ) );
				$question->set_type( $args['type'] );

				if ( $args['create_answers'] ) {
					$answers = $question->get_default_answers();
					// insert answers data in new question
					foreach ( $answers as $index => $answer ) {
						$insert = array(
							'question_id'  => $question_id,
							'answer_data'  => serialize( array(
									'text'    => stripslashes( $answer['text'] ),
									'value'   => isset( $answer['value'] ) ? stripslashes( $answer['value'] ) : '',
									'is_true' => ( $answer['is_true'] == 'yes' ) ? $answer['is_true'] : ''
								)
							),
							'answer_order' => $index + 1
						);
						$this->add_answer( $args['type'], $insert );
					}

					// add question to quiz
					if ( ! empty( $args['quiz_id'] ) ) {
						$quiz_curd = new LP_Quiz_CURD();
						$quiz_curd->add_question( $args['quiz_id'], $question_id, $args['order'] );
					}
				}

				// hook
				do_action( 'learn-press/after-create-question', $question );

				return $question;
			}

			return $question_id;
		}


		public function update( &$question ) {
			return $question;
			// TODO: Implement update() method.
		}

		/**
		 * Delete all question's related data before run wp_delete_post(), hook to before delete question hook.
		 *
		 * @since 3.0.0
		 *
		 * @param object $question_id
		 */
		public function delete( &$question_id ) {
			// remove all answer of question from {$wpdb->prefix}learnpress_question_answers table
			$this->clear( $question_id );

			// quiz curd
			$curd = new LP_Quiz_CURD();

			// allow hook
			do_action( 'learn-press/before-delete-question', $question_id );

			// get the quizzes that a question is assigned to, return WP Post
			$quiz = $this->get_quiz( $question_id );

			// remove question from quiz
			if ( $quiz ) {
				$curd->remove_questions( $quiz->ID, $question_id );
			}
		}

		/**
		 * Duplicate question.
		 *
		 * @since 3.0.0
		 *
		 * @param       $question_id
		 * @param array $args
		 *
		 * @return mixed|WP_Error
		 */
		public function duplicate( &$question_id, $args = array() ) {
			if ( ! $question_id ) {
				return new WP_Error( __( '<p>Op! ID not found</p>', 'learnpress' ) );
			}

			if ( learn_press_get_post_type( $question_id ) != LP_QUESTION_CPT ) {
				return new WP_Error( __( '<p>Op! The question does not exist</p>', 'learnpress' ) );
			}

			// ensure that user can create question
			if ( ! current_user_can( 'edit_posts' ) ) {
				return new WP_Error( __( '<p>Sorry! You don\'t have permission to duplicate this question</p>', 'learnpress' ) );
			}

			// origin question
			$question = LP_Question::get_question( $question_id );

			// duplicate question
			$new_question_id = learn_press_duplicate_post( $question_id, array( 'post_status' => 'publish' ) );

			if ( ! $new_question_id || is_wp_error( $new_question_id ) ) {
				return new WP_Error( __( '<p>Sorry! Failed to duplicate question!</p>', 'learnpress' ) );
			} else {

				// init new question
				$new_question = LP_Question::get_question( $new_question_id );

				// set data
				$new_question->set_type( $question->get_type() );
				$new_question->set_data( 'answer_options', $question->get_data( 'answer_options' ) );

				// trigger change user memorize question types
				$user_id = get_current_user_id();
				update_user_meta( $user_id, '_learn_press_memorize_question_types', $new_question->get_type() );

				// duplicate answer
				$this->duplicate_answer( $question_id, $new_question_id );

				return $new_question_id;
			}
		}

		/**
		 * Duplicate answer question.
		 *
		 * @param $question_id     | origin question
		 * @param $new_question_id | new question
		 */
		public function duplicate_answer( $question_id, $new_question_id ) {

			global $wpdb;

			// duplicate question answer
			$query          = $wpdb->prepare( " SELECT * FROM $wpdb->learnpress_question_answers WHERE question_id = %d", $question_id );
			$answer_options = $wpdb->get_results( $query );

			if ( $answer_options ) {
				foreach ( $answer_options as $option ) {
					$wpdb->insert(
						$wpdb->learnpress_question_answers,
						array(
							'question_id'  => $new_question_id,
							'answer_data'  => $option->answer_data,
							'answer_order' => $option->answer_order
						),
						array( '%d', '%s', '%s' )
					);
				}
			}
		}

		/**
		 * @param LP_Question $question
		 *
		 * @return bool
		 * @throws Exception
		 */
		public function load( &$question ) {
			// question id
			$id = $question->get_id();

			if ( ! $id || ! in_array( learn_press_get_post_type( $id ), array( 'revision', LP_QUESTION_CPT ) ) ) {
				throw new Exception( sprintf( __( 'Invalid question with ID "%d".', 'learnpress' ), $id ) );
			}

			$question->set_data_via_methods(
				array(
					'explanation' => get_post_meta( $id, '_lp_explanation', true ),
					'hint'        => get_post_meta( $id, '_lp_hint', true )
				)
			);
			//$this->_load_answer_options( $question );
			$this->_load_meta( $question );

			return true;
		}

		/**
		 * @param $question | LP_Question
		 */
		protected function _load_meta( &$question ) {
			$type = get_post_meta( $question->get_id(), '_lp_type', true );
			if ( ! learn_press_is_support_question_type( $type ) ) {
				$type = 'true_or_false';
			}
//			$question->set_type( $type );

			$mark = $this->_get_question_mark( $question->get_id() );
			$question->set_data_via_methods(
				array(
					'mark' => $mark
				)
			);
		}

		public function _get_question_mark( $question_id ) {
			$mark = absint( get_post_meta( $question_id, '_lp_mark', true ) );
			if ( ! $mark ) {
				$mark = apply_filters( 'learn-press/question/default-mark', 1, $question_id );
				update_post_meta( $question_id, '_lp_mark', $mark );
			}

			return $mark;
		}

		/**
		 * Get the quizzes that a question is assigned to.
		 *
		 * @since 3.0.0
		 *
		 * @param $question_id
		 *
		 * @return null|object WP_Post
		 */
		public function get_quiz( $question_id ) {
			global $wpdb;

			$query = $wpdb->prepare( "
	  			SELECT post.* FROM {$wpdb->posts} post
				INNER JOIN {$wpdb->prefix}learnpress_quiz_questions quiz ON post.ID = quiz.quiz_id
				WHERE quiz.question_id = %d
			", $question_id );

			// get single row
			return $wpdb->get_row( $query );
		}

		/**
		 * Change question type.
		 *
		 * @param $question LP_Question
		 * @param $new_type
		 *
		 * @return bool|int|LP_Question
		 */
		public function change_question_type( $question, $new_type ) {
			if ( learn_press_get_post_type( $question->get_id() ) != LP_QUESTION_CPT ) {
				return false;
			}

			$question_id = $question->get_id();
			$old_type    = $question->get_type();

			if ( $old_type == $new_type ) {
				return false;
			}

			$answer_options = $question->get_data( 'answer_options' );

			update_post_meta( $question_id, '_lp_type', $new_type );
			$question->set_type( $new_type );

			if ( $new_question = LP_Question::get_question( $question_id, array( 'force' => true ) ) ) {

				$user_id = get_current_user_id();
				update_user_meta( $user_id, '_learn_press_memorize_question_types', $new_type );

				if ( $old_type == 'multi_choice' && $new_type == 'single_choice' ) {
					// for convert to multi choice to single choice
					$func = '_convert_answers_multi_choice_to_single_choice';
				} else if ( $question->is_support( 'answer_options' ) && 'true_or_false' == $new_type ) {
					// for all question supports answer options convert to true or false (except: Fill in blank, so on)
					$func = '_convert_answers_to_true_or_false';
				} else if ( ( $old_type == 'true_or_false' && $new_question->is_support( 'answer_options' ) ) || ( $old_type == 'single_choice' && $new_type == 'multi_choice' ) ) {
					// for case not must to convert answer
					$func = '';
				} else {
					// for rest, clear answer data and create default
					$func = '_convert_default_answers';
				}

				if ( is_callable( array( $this, $func ) ) ) {
					$answer_options = call_user_func_array( array( $this, $func ), array(
						$question,
						$new_question,
						$answer_options
					) );
				}

				LP_Object_Cache::set( 'answer-options-' . $question_id, $answer_options, 'learn-press/questions' );
				$new_question->set_data( 'answer_options', $answer_options );

				return $new_question;
			}

			return false;
		}

		/**
		 * Update answer title
		 *
		 * @param $question_id
		 * @param $answer
		 *
		 * @return bool|false|int
		 */
		public function update_answer_title( $question_id, $answer ) {

			if ( get_post_type( $question_id ) !== LP_QUESTION_CPT ) {
				return false;
			}

			global $wpdb;

			// question data
			$data = array(
				'data'  => apply_filters(
					'learn-press/question/update-answer-data',
					array(
						'answer_data' => serialize( array(
								'text'    => stripslashes( $answer['text'] ),
								'value'   => isset( $answer['value'] ) ? $answer['value'] : '',
								'is_true' => isset( $answer['is_true'] ) ? $answer['is_true'] : ''
							)
						)
					)
				),
				'where' => array(
					'question_answer_id' => $answer['question_answer_id'],
					'question_id'        => $question_id
				)
			);

			$update = $wpdb->update( $wpdb->learnpress_question_answers,
				$data['data'],
				$data['where'],
				array( '%s', '%s', '%s' ),
				array( '%d', '%d' )
			);


			do_action( 'learn-press/question/updated-answer-data', $question_id, $answer['question_answer_id'], $answer );

			return $update;
		}

		/**
		 * Update correct answer.
		 *
		 * @param $question LP_Question
		 * @param $correct
		 *
		 * @return bool | LP_Question
		 */
		public function change_correct_answer( $question, $correct ) {
			if ( learn_press_get_post_type( $question->get_id() ) != LP_QUESTION_CPT ) {
				return false;
			}

			global $wpdb;

			$question_id      = $question->get_id();
			$question_type    = $question->get_type();
			$question_answers = $question->get_data( 'answer_options' );

			$db_args = $answers = array();

			foreach ( $question_answers as $index => $answer ) {

				$answer_data = array(
					'text'    => stripslashes( $answer['text'] ),
					'value'   => isset( $answer['value'] ) ? stripslashes( $answer['value'] ) : '',
					'is_true' => isset( $answer['is_true'] ) ? $answer['is_true'] : ''
				);

				// update correct for select answer
				if ( $answer['question_answer_id'] == $correct['question_answer_id'] ) {
					$answer_data['is_true'] = $correct['is_true'];
				} else {
					// untrue all rest answer with True or false and Single choice question
					if ( in_array( $question_type, array( 'true_or_false', 'single_choice' ) ) ) {
						$answer_data['is_true'] = '';
					}
				}

				// question answers data to set cache
				$answers[ $index ] = array(
					'question_answer_id' => $answer['question_answer_id'],
					'question_id'        => $question_id,
					'answer_order'       => $answer['answer_order'],
					'text'               => $answer_data['text'],
					'value'              => $answer_data['value'],
					'is_true'            => $answer_data['is_true']
				);

				// new answers data
				$db_args[ $index ] = array(
					'data'  => array(
						'answer_data' => serialize( $answer_data )
					),
					'where' => array(
						'question_answer_id' => $answer['question_answer_id'],
						'question_id'        => $question_id,
						'answer_order'       => $answer['answer_order']
					)
				);
			}

			// update db
			foreach ( $db_args as $id => $arg ) {
				$wpdb->update( $wpdb->learnpress_question_answers,
					$arg['data'],
					$arg['where'],
					array( '%s', '%s', '%s' ),
					array( '%d', '%d', '%d' )
				);
			}

			// set question answer data
			$question->set_data( 'answer_options', $answers );

			return $question;
		}

		/**
		 * Sort answers.
		 *
		 * @since 3.0.0
		 *
		 * @param       $question_id
		 * @param array $order
		 *
		 * @return bool|LP_Question
		 */
		public function sort_answers( $question_id, $order = array() ) {

			if ( learn_press_get_post_type( $question_id ) !== LP_QUESTION_CPT ) {
				return false;
			}

			if ( $order ) {

				$question = LP_Question::get_question( $question_id );
				$answers  = $question->get_data( 'answer_options' );

				global $wpdb;
				$new_answers = array();
				foreach ( $order as $index => $answer_id ) {
					$wpdb->update(
						$wpdb->learnpress_question_answers,
						array( 'answer_order' => $index + 1 ),
						array( 'question_answer_id' => $answer_id )
					);

					$new_answers[ $answer_id ] = $answers[ $answer_id ];
				}

				$question->set_data( 'answer_options', $new_answers );

				return $question;

			}

			return false;
		}

		/**
		 * Delete question answer.
		 *
		 * @param $question_id
		 * @param $answer_id
		 * @param $force
		 *
		 * @return bool|false|int
		 */
		public function delete_answer( $question_id, $answer_id, $force = false ) {

			if ( learn_press_get_post_type( $question_id ) !== LP_QUESTION_CPT ) {
				return false;
			}

			$question = LP_Question::get_question( $question_id );

			// exist answer options
			$answers = $question->get_data( 'answer_options' );

			global $wpdb;

			// delete all answer in question
			if ( $force ) {
				$delete = $wpdb->delete(
					$wpdb->learnpress_question_answers,
					array( 'question_id' => $question_id )
				);

				if ( $delete ) {
					$question->set_data( 'answer_options', '' );
				}
			} else {
				$delete = $wpdb->delete(
					$wpdb->learnpress_question_answers,
					array( 'question_answer_id' => $answer_id )
				);
				if ( $delete ) {
					unset( $answers[ $answer_id ] );
					$question->set_data( 'answer_options', $answers );

					$this->sort_answers( $question_id, array_keys( $answers ) );
				}
			}

			return $delete;
		}

		/**
		 * Add new answer.
		 *
		 * @param $question_id
		 * @param $new_answer
		 *
		 * @return bool|false|int
		 */
		public function new_answer( $question_id, $new_answer ) {

			if ( learn_press_get_post_type( $question_id ) !== LP_QUESTION_CPT ) {
				return false;
			}

			$question = LP_Question::get_question( $question_id );

			// exist answer options
			$answers = $question->get_data( 'answer_options' );
			// number answer options
			$number = count( $question->get_data( 'answer_options' ) );

			global $wpdb;

			$insert = $wpdb->insert(
				$wpdb->learnpress_question_answers,
				array(
					'question_id'  => $question_id,
					'answer_data'  => serialize( $new_answer ),
					'answer_order' => $number + 1,
				),
				array( '%d', '%s', '%d' ) );

			if ( $insert ) {
				$new_answer['question_answer_id'] = $wpdb->insert_id;
				$new_answer['question_id']        = $question_id;
				$new_answer['answer_order']       = $number + 1;

				if ( is_array( $answers ) ) {
					$answers = array_merge( $answers, array( $new_answer ) );
				} else {
					$answers = array( $new_answer );
				}
				$question->set_data( 'answer_options', $answers );
			}

			return $wpdb->insert_id;
		}


		/**
		 * Convert answers to true or false question.
		 *
		 * @since 3.0.0
		 *
		 * @param $question
		 * @param $new_question
		 * @param $answers
		 *
		 * @return mixed
		 */
		protected function _convert_answers_to_true_or_false( $question, $new_question, $answers ) {
			if ( is_array( $answers ) ) {
				// array answer ids
				$answer_ids = array_keys( $answers );

				if ( sizeof( $answers ) > 2 ) {
					global $wpdb;

					foreach ( $answers as $key => $answer ) {
						if ( array_search( $key, $answer_ids ) > 1 ) {
							$wpdb->delete(
								$wpdb->learnpress_question_answers,
								array( 'question_answer_id' => $answer['question_answer_id'] )
							);
						}
					}
					$answers = array_slice( $answers, 0, 2 );
				}

				$correct = 0;
				foreach ( $answers as $key => $answer ) {
					if ( $answer['is_true'] == 'yes' ) {
						$correct += 1;
					}
				}

				if ( ! $correct ) {
					// for single choice deletes all correct, set first option is correct
					$answers[ $answer_ids[0] ]['is_true'] = 'yes';
				} else if ( $correct == 2 ) {
					// for multiple choice keeps all correct, remove all correct and keep first option
					$answers[ $answer_ids[1] ]['is_true'] = '';
				}
			}

			return $answers;
		}

		/**
		 *
		 * Convert answers for multi choice to single choice question.
		 *
		 * @since 3.0.0
		 *
		 * @param $question
		 * @param $new_question
		 * @param $answers
		 *
		 * @return array
		 */
		protected function _convert_answers_multi_choice_to_single_choice( $question, $new_question, $answers ) {
			if ( is_array( $answers ) ) {
				// array answer ids
				$answer_ids = array_keys( $answers );

				$correct = 0;
				foreach ( $answers as $key => $answer ) {
					if ( $answer['is_true'] == 'yes' ) {
						$correct += 1;
					}
				}

				if ( $correct > 1 ) {
					// remove all correct and keep first option
					$answers[ $answer_ids[0] ]['is_true'] = '';
				}
			}

			return $answers;
		}

		/**
		 * Convert default answers.
		 *
		 * @param $question     LP_Question
		 * @param $new_question LP_Question
		 * @param $answers
		 *
		 * @return array
		 */
		protected function _convert_default_answers( $question, $new_question, $answers ) {
			$question_id = $question->get_id();
			// clear all exists answer
			$this->clear( $question_id );
			// set default answer
			$answer_options = $new_question->get_default_answers();

			if ( is_array( $answer_options ) ) {
				// insert answers data in new question
				foreach ( $answer_options as $index => $answer ) {
					$insert        = array(
						'question_id'  => $question_id,
						'answer_data'  => serialize( array(
								'text'    => stripslashes( $answer['text'] ),
								'value'   => isset( $answer['value'] ) ? stripslashes( $answer['value'] ) : '',
								'is_true' => ( $answer['is_true'] == 'yes' ) ? $answer['is_true'] : ''
							)
						),
						'answer_order' => $index + 1
					);
					$new_answers[] = $this->add_answer( $new_question->get_type(), $insert );
				};

				return $new_answers;
			}

			return $answers;
		}

		/**
		 * Add question answer.
		 *
		 * @since 3.0.0
		 *
		 * @param string $question_type
		 * @param array  $args
		 *
		 * @return array|bool
		 */
		public function add_answer( $question_type = '', $args = array() ) {

			global $wpdb;

			$question = LP_Question::get_question( $args['question_id'], array( 'type' => $question_type ) );

			$wpdb->insert(
				$wpdb->learnpress_question_answers,
				array(
					'question_id'  => $args['question_id'],
					'answer_data'  => $args['answer_data'],
					'answer_order' => $args['answer_order']
				),
				array( '%d', '%s', '%d' ) );

			$question_answer_id = $wpdb->insert_id;
			if ( $question_answer_id ) {
				// update question answer option data
				$answer_options   = $question->get_data( 'answer_options' ) ? $question->get_data( 'answer_options' ) : array();
				$unserialize_data = unserialize( $args['answer_data'] );

				$new_answer_option_data = array(
					'question_answer_id' => $question_answer_id,
					'question_id'        => $args['question_id'],
					'answer_order'       => $args['answer_order'],
					'text'               => $unserialize_data['text'],
					'value'              => $unserialize_data['value'],
					'is_true'            => $unserialize_data['is_true']
				);

				if ( ! $answer_options ) {
					$question->set_data( 'answer_options', array( $new_answer_option_data ) );
				} else {
					$answer_options[] = $new_answer_option_data;
					$question->set_data( 'answer_options', $answer_options );
				}

				return $new_answer_option_data;
			} else {
				return false;
			}
		}

		/**
		 * Delete question answer.
		 *
		 * @since 3.0.0
		 *
		 * @param $question_id
		 * @param $answer_id
		 *
		 * @return bool|false|int
		 */
		public function delete_question_answer( $question_id, $answer_id ) {
			if ( learn_press_get_post_type( $question_id ) !== LP_QUESTION_CPT || ! $answer_id ) {
				return false;
			}

			global $wpdb;

			$result = $wpdb->delete(
				$wpdb->learnpress_question_answers,
				array( 'question_answer_id' => $answer_id )
			);

			return $result;
		}

		/**
		 * Delete all question answers.
		 *
		 * @param $question_id
		 *
		 * @return bool|WP_Error
		 */
		public function clear( $question_id ) {

			if ( ! learn_press_get_question( $question_id ) ) {
				return $this->get_error( 'QUESTION_NOT_EXISTS' );
			}

			do_action( 'learn-press/before-clear-question', $question_id );

			global $wpdb;
			$wpdb->delete( $wpdb->learnpress_question_answers, array( 'question_id' => $question_id ) );

			return true;
		}

		/**
		 * Load answer options for the question from database.
		 * Load from cache if data is already loaded into cache.
		 * Otherwise, load from database and put to cache.
		 *
		 * @param int $question_id
		 *
		 * @return array
		 */
		protected function _read_answers( $question_id ) {
			global $wpdb;

			$query = $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->prefix}learnpress_question_answers
				WHERE question_id = %d
				ORDER BY answer_order ASC
			", $question_id );

			$answer_options = array();

			if ( $results = $wpdb->get_results( $query ) ) {
				foreach ( $results as $k => $v ) {

					if ( $answer_data = LP_Helper::maybe_unserialize( $v->answer_data ) ) {
						unset($answer_data['question_answer_id']);
						foreach ( $answer_data as $data_key => $data_value ) {
							$v->{$data_key} = $data_value;
						}
					}

					unset( $v->answer_data );

					$answer_options[ $v->question_answer_id ] = (array) $v;
				}
			}

			return $answer_options;
		}

		/**
		 * Load answer options for the question from database.
		 * Load from cache if data is already loaded into cache.
		 * Otherwise, load from database and put to cache.
		 *
		 * @param $question LP_Question
		 */
		protected function _load_answer_options( &$question ) {

			$id             = $question->get_id();
			$answer_options = LP_Object_Cache::get( 'answer-options-' . $id, 'lp-questions' );

			if ( false === $answer_options ) {
				global $wpdb;
				$query = $wpdb->prepare( "
					SELECT *
					FROM {$wpdb->prefix}learnpress_question_answers
					WHERE question_id = %d
					ORDER BY answer_order ASC
				", $id );
				if ( $answer_options = $wpdb->get_results( $query, OBJECT_K ) ) {
					foreach ( $answer_options as $k => $v ) {
						$answer_options[ $k ] = (array) $answer_options[ $k ];
						if ( $answer_data = LP_Helper::maybe_unserialize( $v->answer_data ) ) {
							foreach ( $answer_data as $data_key => $data_value ) {
								$answer_options[ $k ][ $data_key ] = $data_value;
							}
						}
						unset( $answer_options[ $k ]['answer_data'] );
					}
				}

				$answer_options = $this->load_answer_options( $question->get_id() );
			}
			$answer_options = apply_filters( 'learn-press/question/load-answer-options', $answer_options, $id );

			if ( ! empty( $answer_options['question_answer_id'] ) && $answer_options['question_answer_id'] > 0 ) {
				$this->_load_answer_option_meta( $answer_options );
			}
			LP_Object_Cache::set( 'answer-options-' . $id, $answer_options, 'lp-questions' );

			$question->set_data( 'answer_options', $answer_options );
		}

		/**
		 * Load question answers
		 *
		 * @updated 3.1.0
		 *
		 * @param array|int $question_id
		 *
		 * @return array|bool
		 */
		public function load_answer_options( $question_id ) {

			global $wpdb;

			$return_id = 0;

			if ( is_array( $question_id ) ) {

				foreach ( $question_id as $q_id ) {
					$this->load_answer_options( $q_id );
					if ( ! $return_id ) {
						$return_id = $q_id;
					}
				}
				$question_id = $return_id;
			}

			if ( false === ( $answer_options = LP_Object_Cache::get( 'question-' . $question_id, 'question-answers' ) ) ) {

				$answer_options = $this->_read_answers( $question_id );
				LP_Object_Cache::set( 'question-' . $question_id, $answer_options, 'question-answers' );
			}

			return $answer_options;
		}

		/**
		 * Load meta data for answer options.
		 *
		 * @param array $answer_options
		 *
		 * @return mixed;
		 */
		protected function _load_answer_option_meta( &$answer_options ) {

			global $wpdb;
			if ( ! $answer_options ) {
				return false;
			}
			$answer_option_ids = wp_list_pluck( $answer_options, 'question_answer_id' );
			$format            = array_fill( 0, sizeof( $answer_option_ids ), '%d' );
			$query             = $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->prefix}learnpress_question_answermeta
				WHERE learnpress_question_answer_id IN(" . join( ', ', $format ) . ")
			", $answer_option_ids );
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

			return true;
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

		public function update_meta( &$object, $meta ) {
			// TODO: Implement update_meta() method.
		}
	}
}
