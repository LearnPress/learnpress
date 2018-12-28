<?php

/**
 * Class LP_User_Item_CURD
 *
 * Class to manipulating user item with database.
 */
class LP_User_Item_CURD implements LP_Interface_CURD {
	/**
	 * Errors codes and message.
	 *
	 * @var array|bool
	 */
	protected $_error_messages = false;

	/**
	 * LP_User_Item_CURD constructor.
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
		if ( ! $the_id || LP_QUIZ_CPT !== learn_press_get_post_type( $the_id ) ) {
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
				'review_questions'   => get_post_meta( $quiz->get_id(), '_lp_review_questions', true ),
			)
		);
		$this->_load_questions( $quiz );
		$this->_update_meta_cache( $quiz );

		return $quiz;
	}

	public function create( &$quiz ) {
		// TODO: Implement create() method.
	}

	public function update( &$quiz ) {
		// TODO: Implement update() method.
	}

	public function delete( &$quiz ) {
		// TODO: Implement delete() method.
	}

	public function duplicate( &$quiz, $args = array() ) {
		// TODO: Implement duplicate() method.
	}

	/**
	 * @param LP_Quiz $quiz
	 */
	protected function _load_questions( &$quiz ) {
		LP_Debug::log_function( __CLASS__ . '::' . __FUNCTION__ );

		$id        = $quiz->get_id();
		$questions = LP_Object_Cache::get( 'questions-' . $id, 'learn-press/quizzes' );
		if ( false === $questions || $quiz->get_no_cache() ) {
			global $wpdb;
			$questions = array();
			$query     = $wpdb->prepare( "
				SELECT p.*, qq.question_order AS `order`
				FROM {$wpdb->posts} p 
				INNER JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON p.ID = qq.question_id
				WHERE qq.quiz_id = %d
				AND p.post_status = %s
				ORDER BY question_order ASC
			", $id, 'publish' );
			if ( $results = $wpdb->get_results( $query, OBJECT_K ) ) {
				foreach ( $results as $k => $v ) {
					wp_cache_set( $v->ID, $v, 'posts' );
					$questions[ $v->ID ] = $v->ID;
				}
			}
			LP_Object_Cache::set( 'questions-' . $id, $questions, 'learn-press/quizzes' );

			$this->_load_question_answers( $quiz );
		}
		unset( $questions );
		LP_Debug::log_function( __CLASS__ . '::' . __FUNCTION__ );

	}

	/**
	 * @param LP_Quiz $quiz
	 */
	protected function _update_meta_cache( &$quiz ) {
		$meta_ids = LP_Object_Cache::get( 'questions-' . $quiz->get_id(), 'learn-press/quizzes' );

		if ( false === $meta_ids ) {
			$meta_ids = array( $quiz->get_id() );
		} else {
			$meta_ids[] = $quiz->get_id();
		}
		LP_Helper_CURD::update_meta_cache( $meta_ids );
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
				if ( $answer_data = LP_Helper::maybe_unserialize( $v['answer_data'] ) ) {
					foreach ( $answer_data as $kk => $vv ) {
						$v[ $kk ] = $vv;
					}
				}
				unset( $v['answer_data'] );


				$answer_options[ $v['question_id'] ][] = $v;
				/*$kk                                  = sizeof( $answer_options[ $v->question_id ] );

				if ( $answer_data = LP_Helper::maybe_unserialize( $v->answer_data ) ) {
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

			foreach ( $answer_options as $question_id => $options ) {
				LP_Object_Cache::set( 'answer-options-' . $question_id, $options, 'learn-press/questions' );
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
				LP_Object_Cache::set( 'answer-options-' . $question_id, array(), 'learn-press/questions' );
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
	 * @param mixed               $questions
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
		if ( ! $the_quiz = learn_press_get_quiz( $the_quiz ) ) {
			return $this->get_error( 'QUESTION_NOT_EXISTS' );
		}
		if ( ! $question = learn_press_get_question( $question_id ) ) {
			return false;
		}

		if ( $this->is_exists_question( $question_id ) ) {
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
	 * @param int       $the_id
	 * @param int|array $ids
	 *
	 * @return array|bool|null|object
	 */
	public function is_exists_question( $the_id, $ids = array() ) {
		global $wpdb;
		settype( $ids, 'array' );
		$format = array_fill( 0, sizeof( $ids ), '%d' );
		$args   = $ids;
		$args[] = $the_id;
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
	 * Get single user item by values of fields.
	 *
	 * @since 3.1.0
	 *
	 * @param string|array $field
	 * @param string       $value
	 *
	 * @return mixed
	 */
	public function get_item_by( $field, $value = '' ) {
		if ( $rows = $this->get_items_by( $field, $value ) ) {
			return $rows[0];
		}

		return false;
	}

	/**
	 * Get multiple rows of user item by values of fields.
	 *
	 * @since 3.1.0
	 *
	 * @param string|array $field
	 * @param string       $value
	 *
	 * @return array
	 */
	public function get_items_by( $field, $value = '' ) {
		global $wpdb;
		$where = "";

		if ( is_array( $field ) ) {
			foreach ( $field as $k => $v ) {
				if ( is_string( $v ) ) {
					$where .= $wpdb->prepare( " AND {$k} = %s", $v );
				} else {
					$where .= $wpdb->prepare( " AND {$k} = %d", $v );
				}
			}
		} else {
			if ( is_string( $value ) ) {
				$where .= $wpdb->prepare( " AND {$field} = %s", $value );
			} else {
				$where .= $wpdb->prepare( " AND {$field} = %s", $value );
			}
		}

		$query = "SELECT * FROM {$wpdb->learnpress_user_items} WHERE 1 {$where}";

		return $wpdb->get_results( $query );
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

	/**
	 * Parse attribute 'preview' for all items in a course
	 *
	 * @since 3.2.0
	 *
	 * @param int $course_id
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function parse_items_preview( $course_id, $user_id = 0 ) {

		$items = array();

		if ( ! $course = learn_press_get_course( $course_id ) ) {
			return $items;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$user         = learn_press_get_user( $user_id, false );
		$current_item = LP_Global::course_item();
		$get_item_ids = $course->get_item_ids();
		$enrolled     = $user ? $user->has_enrolled_course( $course_id ) : false;

		if ( $get_item_ids ) {
			foreach ( $get_item_ids as $item_id ) {
				$is_preview = get_post_meta( $item_id, '_lp_preview', true );#// == 'yes';
				if ( $enrolled ) {
					$is_preview = 'no';
				}
				if ( false === ( $cached = LP_Object_Cache::get( 'item-' . $user_id . '-' . $course_id . '-' . $item_id, 'learn-press/preview-items' ) ) ) {
					LP_Object_Cache::set( 'item-' . $user_id . '-' . $course->get_id() . '-' . $item_id, $is_preview, 'learn-press/preview-items' );
				} else {
					///$is_preview = $cached === 'yes' ? true : false;
				}

				$items[ $item_id ] = $is_preview;
			}
		}

		return $items;
	}

	/**
	 * Parse classes for all items in a course.
	 *
	 * @since 3.2.0
	 *
	 * @param int          $course_id
	 * @param int          $user_id
	 * @param array|string $more
	 *
	 * @return array
	 */
	public function parse_items_classes( $course_id, $user_id = 0, $more = array() ) {
		$items = array();

		if ( ! $course = learn_press_get_course( $course_id ) ) {
			return $items;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		//$user            = learn_press_get_user( $user_id, false ); tested on client site and this function not work. Use the below line instead of!
		$user            = learn_press_get_current_user();
		$current_item    = LP_Global::course_item();
		$get_item_ids    = $course->get_item_ids();
		$enrolled        = $user ? $user->has_enrolled_course( $course_id ) : false;
		$is_free         = $course->is_free();
		$required_enroll = $course->is_required_enroll();

		if ( $get_item_ids ) {
			foreach ( $get_item_ids as $item_id ) {
				$item = $course->get_item( $item_id );

				if ( ! $item ) {
					continue;
				}

				$defaults = array_merge(
					array(
						'course-item',
						'course-item-' . $item->get_item_type(),
						'course-item-' . $item_id
					), (array) $more
				);

				if ( ( 'standard' !== ( $post_format = $item->get_format() ) ) && $post_format ) {
					$defaults[] = 'course-item-type-' . $post_format;
				}

				if ( $current_item && $current_item->get_id() == $item->get_id() ) {
					$defaults[] = 'current';
				}

				if ( $item->is_preview() ) {
					$defaults[] = 'item-preview';
					$defaults[] = 'has-status';
				} elseif ( $item->is_blocked() ) {
					$defaults[] = 'item-locked';
				} else {
					if ( $course ) {
						if ( $is_free && ! $required_enroll ) {
							$defaults[] = 'item-free';
						} else {
							if ( $user ) {
								if ( $enrolled ) {
									$item_status = $user->get_item_status( $item_id, $course_id );
									$item_grade  = $user->get_item_grade( $item_id, $course_id );

									if ( $item_status ) {
										$defaults[] = 'has-status';
										$defaults[] = 'status-' . $item_status;
									}
									switch ( $item_status ) {
										case 'started':
											break;
										case 'completed':
											$defaults[] = $item_grade;
											break;
										default:
											if ( $item_class = apply_filters( 'learn-press/course-item-status-class', $item_status, $item_grade, $item->get_item_type(), $item_id, $course_id ) ) {
												$defaults[] = $item_class;
											}
									}
								}
							}

							if ( ! $enrolled ) {
								$defaults[] = 'item-locked';
							}
						}
					} else {
						$defaults[] = 'item-locked';
					}
				}
				$classes = apply_filters( 'learn-press/course-item-class', $defaults, $item->get_item_type(), $item_id, $course_id );

				// Filter unwanted values
				$classes = is_array( $classes ) ? $classes : explode( ' ', $classes );
				$classes = array_filter( $classes );
				$classes = array_unique( $classes );

				LP_Object_Cache::set( 'item-' . $user_id . '-' . $item_id, $classes, 'learn-press/post-classes' );
				$items[ $item_id ] = $classes;
			}
		}

		return $items;
	}
}