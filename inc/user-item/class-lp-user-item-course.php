<?php
/**
 * Class LP_User_Item_Course
 */

class LP_User_Item_Course extends LP_User_Item implements ArrayAccess {
	public $_item_type = LP_COURSE_CPT;
	public $_ref_type  = LP_ORDER_CPT;

	/**
	 * Course
	 *
	 * @var LP_Course
	 */
	protected $_course = 0;

	/**
	 * @var LP_User
	 */
	protected $_user = 0;

	/**
	 * @var array
	 */
	protected $_items_by_item_ids = array();

	/**
	 * @var array
	 */
	protected $_items_by_order = array();

	/**
	 * @var bool
	 */
	protected $_loaded = false;

	/**
	 * @var LP_User_CURD
	 */
	protected $_curd = null;

	/**
	 * LP_User_Item_Course constructor.
	 *
	 * @param null $item
	 */
	public function __construct( $item ) {
		if ( is_array( $item ) ) {
			$item['item_type'] = $this->_item_type;
			$item['ref_type']  = $this->_ref_type;
		}
		parent::__construct( $item );

		$this->_curd    = new LP_User_CURD();
		$this->_changes = array();
	}

	public function load() {
		if ( ! $this->_loaded ) {
			$this->read_items();

			$this->_loaded = true;
		}
	}

	/**
	 * Read items' data of course for the user.
	 *
	 * @param bool $refresh .
	 *
	 * @return array|bool
	 */
	public function read_items( $refresh = false ) {
		$this->_course = learn_press_get_course( $this->get_id() );

		$user_course_item_id = $this->get_user_item_id();

		if ( ! $this->_course || ( ! $user_course_item_id ) ) {
			return false;
		}

		// $items = $this->cache_get_items();

		$course_items = $this->_course->get_item_ids();

		if ( ! $course_items ) {
			return false;
		}

		$filter            = new LP_User_Items_Filter();
		$filter->parent_id = $user_course_item_id;
		$filter->user_id   = $this->get_user_id();
		$user_course_items = LP_User_Items_DB::getInstance()->get_user_course_items( $filter );

		if ( $user_course_items ) {
			$tmp = array();
			// Convert keys of array from numeric to keys is item id
			foreach ( $user_course_items as $user_course_item ) {
				$tmp[ $user_course_item->item_id ] = $user_course_item;
			}

			$user_course_items = $tmp;
			unset( $tmp );
		} else {
			$user_course_items = array();
		}

		$items = array();

		foreach ( $course_items as $item_id ) {
			if ( ! empty( $user_course_items[ $item_id ] ) ) {
				$user_course_item = (array) $user_course_items[ $item_id ];
			} else {
				$user_course_item = array(
					'item_id'   => $item_id,
					'ref_id'    => $this->get_id(),
					'parent_id' => $user_course_item_id,
				);
			}

			$course_item = apply_filters(
				'learn-press/user-course-item',
				LP_User_Item::get_item_object( $user_course_item ),
				$user_course_item,
				$this
			);

			if ( $course_item ) {
				// $this->_items_by_item_ids[ $course_item->get_user_item_id() ] = $item_id;
				$items[ $item_id ] = $course_item;
			}
		}

		return $items;
	}

	/**
	 * Get Id of course.
	 *
	 * @return int
	 * @since 3.3.0
	 */
	public function get_course_id() {
		return $this->get_data( 'item_id' );
	}

	public function get_finishing_type() {
		$type = $this->get_meta( 'finishing_type' );

		if ( ! $type ) {
			$type = $this->is_exceeded() <= 0 ? 'exceeded' : 'click';

			learn_press_update_user_item_meta( $this->get_user_item_id(), 'finishing_type', $type );

			$this->set_meta( 'finishing_type', $type );
		}

		return $type;
	}

	public function offsetSet( $offset, $value ) {
		// $this->set_data( $offset, $value );
		// Do not allow to set value directly!
	}

	public function offsetUnset( $offset ) {
		// Do not allow to unset value directly!
	}

	public function offsetGet( $offset ) {
		$items = $this->read_items( true );

		return $items && array_key_exists( $offset, $items ) ? $items[ $offset ] : false;
	}

	public function offsetExists( $offset ) {
		$items = $this->read_items();

		return array_key_exists( $offset, (array) $items );
	}

	/**
	 * @return LP_User_Item|bool
	 */
	public function get_viewing_item() {
		$item = LP_Global::course_item();
		if ( $item ) {
			return $this[ $item->get_id() ];
		}

		return false;
	}

	public function get_course( $return = '' ) {
		$cid = $this->get_data( 'item_id' );
		if ( $return == '' ) {
			return $cid ? learn_press_get_course( $cid ) : false;
		}

		return $cid;
	}

	/**
	 * Get result/processing course
	 *
	 * @param string $prop
	 *
	 * @return mixed
	 */
	public function get_result( $prop = '' ) {
		$results = $this->calculate_course_results();

		// Fix temporary for case call 'grade' - addons called
		if ( 'grade' === $prop ) {
			if ( $results['pass'] ) {
				$results['grade'] = 'passed';
			} else {
				$results['grade'] = 'failed';
			}
		}

		return $prop && $results && array_key_exists( $prop, $results ) ? $results[ $prop ] : $results;
	}

	/**
	 * Get current progress of course for an user.
	 * Firstly, get data from cache if it is already loaded.
	 * If data is not loaded to cache then get from meta data.
	 * If meta data is not updated then calculate and update it
	 *
	 * @updated 3.1.0
	 *
	 * @param string $prop
	 *
	 * @return float|int
	 */
	public function get_results( $prop = 'result' ) {
		$course = $this->get_course();

		if ( ! $course ) {
			return false;
		}

		$results = LP_Object_Cache::get(
			'course-' . $this->get_item_id() . '-' . $this->get_user_id(),
			'course-results'
		);

		if ( $results === false ) {
			// $course_result = $course->get_evaluation_results_method();

			// $results = LP_User_Items_Result_DB::instance()->get_result( $this->get_user_item_id() );

			// if ( is_array( $results ) && array_key_exists( 'result', $results ) ) {
			$results = $this->calculate_course_results();
			// }

			LP_Object_Cache::set(
				'course-' . $this->get_item_id() . '-' . $this->get_user_id(),
				$results,
				'course-results'
			);
		}

		return $prop && $results && array_key_exists( $prop, $results ) ? $results[ $prop ] : $results;
	}

	/**
	 * Calculate course result
	 */
	public function calculate_course_results( bool $force_cache = false ) {
		$items   = array();
		$results = array(
			'count_items'     => 0,
			'completed_items' => 0,
			'items'           => array(),
			'evaluate_type'   => '',
			'pass'            => 0,
		);

		try {
			$course = learn_press_get_course( $this->get_course_id() );

			if ( ! $course ) {
				throw new Exception( 'Course invalid!' );
			}

			$key_first_cache = 'calculate_course/' . $this->get_user_id() . '/' . $course->get_id();
			$results_cache   = LP_Cache::cache_load_first( 'get', $key_first_cache );
			if ( false !== $results_cache && ! $force_cache ) {
				return $results_cache;
			}

			$this->_course = $course;

			if ( $this->is_finished() ) {
				// Get result from lp_user_item_results
				// Todo: tungnx - set cache
				return LP_User_Items_Result_DB::instance()->get_result( $this->get_user_item_id() );
			}

			$count_items           = $course->count_items();
			$count_items_completed = $this->count_items_completed();

			$evaluate_type = $course->get_data( 'course_result', 'evaluate_lesson' );
			switch ( $evaluate_type ) {
				case 'evaluate_lesson':
					$results_evaluate = $this->evaluate_course_by_lesson( $count_items_completed, $course->count_items( LP_LESSON_CPT ) );
					break;
				case 'evaluate_final_quiz':
					$results_evaluate = $this->evaluate_course_by_final_quiz();
					break;
				case 'evaluate_quiz':
					$results_evaluate = $this->evaluate_course_by_quizzes_passed( $count_items_completed, $course->count_items( LP_QUIZ_CPT ) );
					break;
				case 'evaluate_questions':
				case 'evaluate_mark':
					$results_evaluate = $this->evaluate_course_by_question( $evaluate_type );
					break;
				default:
					$results_evaluate = apply_filters( 'learn-press/evaluate_passed_conditions', $results, $evaluate_type, $this );
			}

			if ( ! is_array( $results_evaluate ) ) {
				$results_evaluate = array(
					'result' => 0,
					'pass'   => 0,
				);
			}

			$results_evaluate['result'] = round( $results_evaluate['result'], 2 );

			$completed_items = intval( $count_items_completed->count_status ?? 0 );

			$item_types = learn_press_get_course_item_types();
			foreach ( $item_types as $item_type ) {
				$item_type_key = str_replace( 'lp_', '', $item_type );

				$items[ $item_type_key ] = array(
					'completed' => $count_items_completed->{$item_type . '_status_completed'} ?? 0,
					'passed'    => $count_items_completed->{$item_type . '_graduation_passed'} ?? 0,
					'total'     => $course->count_items( $item_type ),
				);
			}

			$results = array_merge(
				$results_evaluate,
				compact( 'count_items', 'completed_items', 'items', 'evaluate_type' )
			);

			/*
			$graduation = '';

			if ( ! $this->is_purchased() ) {
				$graduation = $this->is_finished() ? $this->_is_passed( $results['result'] ) : 'in-progress';
			}*/

			$results = apply_filters(
				'learn-press/update-course-results',
				$results,
				$this->get_item_id(),
				$this->get_user_id(),
				$this
			);

			LP_Cache::cache_load_first( 'set', $key_first_cache, $results );
		} catch ( Throwable $e ) {

		}

		return $results;
	}

	/**
	 * @editor tungnx
	 * @modify 4.1.4.1 - comment - not use
	 */
	/*
	public function count_items() {
		global $wpdb;
		$t               = microtime( true );
		$course          = $this->get_course();
		$item_ids        = $course->get_items();
		$item_ids_format = LP_Helper::db_format_array( $item_ids, '%d' );

		$query = LP_Helper::prepare(
			"
			SELECT MAX(user_item_id) user_item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE user_id = %d
				AND item_id IN (" . $item_ids_format . ')
			GROUP BY item_id
		',
			$this->get_user_id(),
			$item_ids
		);

		if ( $user_item_ids = $wpdb->get_col( $query ) ) {

			$item_types        = learn_press_get_course_item_types();
			$item_types_format = LP_Helper::db_format_array( $item_types, '%s' );

			$query = LP_Helper::prepare(
				"
				SELECT ui.*, p.post_type AS item_type, grade.meta_value as grade, data.meta_value as data, results.meta_value as results, version.meta_value as version
				FROM {$wpdb->learnpress_user_items} ui
				LEFT JOIN {$wpdb->learnpress_user_itemmeta} grade ON ui.user_item_id = grade.learnpress_user_item_id AND grade.meta_key = '%s'
				LEFT JOIN {$wpdb->learnpress_user_itemmeta} data ON ui.user_item_id = data.learnpress_user_item_id AND data.meta_key = '%s'
				LEFT JOIN {$wpdb->learnpress_user_itemmeta} results ON ui.user_item_id = results.learnpress_user_item_id AND results.meta_key = '%s'
				LEFT JOIN {$wpdb->learnpress_user_itemmeta} version ON ui.user_item_id = version.learnpress_user_item_id AND version.meta_key = '%s'
				INNER JOIN {$wpdb->posts} p ON p.ID = ui.item_id
				WHERE user_item_id IN(" . LP_Helper::db_format_array( $user_item_ids ) . ')
					AND  p.post_type IN(' . $item_types_format . ')
			',
				'grade',
				'data',
				'results',
				'version',
				$user_item_ids,
				$item_types
			);

			$user_items          = $wpdb->get_results( $query );
			$user_items_by_types = array();

			if ( $user_items ) {
				foreach ( $user_items as $k => $user_item ) {
					$user_items[ $k ]->data    = maybe_unserialize( $user_item->data );
					$user_items[ $k ]->results = maybe_unserialize( $user_item->results );

					if ( empty( $user_items_by_types[ $user_item->item_type ] ) ) {
						$user_items_by_types[ $user_item->item_type ] = array();
					}
					$user_items_by_types[ $user_item->item_type ][] = $user_item->item_id;
				}
			}

			learn_press_debug( $user_items, $user_items_by_types );

		}

	}*/

	/**
	 * Evaluate course results by count quizzes passed/all quizzes.
	 *
	 * @param bool $hard - Optional. TRUE will re-calculate results instead of get from cache
	 *
	 * @return array|mixed
	 * @since 4.0.0
	 * @author Nhamdv <email@email.com>
	 */
	protected function _evaluate_results_by_passed_per_all_quizzes( $hard = false ) {
		$cache_key     = 'user-course-' . $this->get_user_id() . '-' . $this->get_id();
		$cache_sub_key = 'passed-per-all-quizzes';
		$cached_data   = LP_Object_Cache::get( $cache_key, 'learn-press/course-results' );

		if ( $hard || false === $cached_data || ! array_key_exists( $cache_sub_key, $cached_data ) ) {
			$data = array(
				'items_completed' => 0,
				'items_count'     => 0,
				'result'          => 0,
				'status'          => $this->get_status(),
			);

			$items = $this->get_items( true );
			if ( $items ) {
				foreach ( $items as $item ) {
					if ( $item->get_type() !== LP_QUIZ_CPT ) {
						continue;
					}

					$data['items_completed'] += $item->get_status( 'graduation' ) == 'passed' ? 1 : 0;
					$data['items_count'] ++;
				}

				$data['result'] = $data['items_count'] ? ( $data['items_completed'] / $data['items_count'] ) * 100 : 0;
			}

			if ( $cached_data ) {
				$cached_data[ $cache_sub_key ] = $data;
			} else {
				$cached_data = array( $cache_sub_key => $data );
			}

			LP_Object_Cache::set( $cache_key, $cached_data, 'learn-press/course-results' );
		}

		return isset( $cached_data[ $cache_sub_key ] ) ? $cached_data[ $cache_sub_key ] : array();
	}

	protected function _evaluate_course_by_question( $hard = false ) {
		$cache_key   = 'user-course-' . $this->get_user_id() . '-' . $this->get_id();
		$cached_data = LP_Object_Cache::get( $cache_key, 'learn-press/course-results' );

		if ( $hard || false === $cached_data || ! array_key_exists( 'questions', $cached_data ) ) {
			$data = array(
				'result' => 0,
				'status' => $this->get_status(),
			);

			$result          = 0;
			$result_of_items = 0;

			$items = $this->get_items();

			if ( $items ) {
				foreach ( $items as $item ) {
					if ( $item->get_type() !== LP_QUIZ_CPT ) {
						continue;
					}

					$quiz_result = $item->get_results( '' );

					if ( $quiz_result ) {
						if ( $quiz_result['question_correct'] ) {
							$result += absint( $quiz_result['question_correct'] );
						}
					}

					$result_of_items += ! empty( $item->get_questions() ) ? count( $item->get_questions() ) : 0;
				}

				$result         = $result_of_items ? ( $result * 100 ) / $result_of_items : 0;
				$data['result'] = $result;
			}

			settype( $cached_data, 'array' );
			$cached_data['questions'] = $data;

			LP_Object_Cache::set( $cache_key, $cached_data, 'learn-press/course-results' );
		}

		return isset( $cached_data['questions'] ) ? $cached_data['questions'] : array();
	}

	protected function _evaluate_course_by_mark() {
		$cache_key   = 'user-course-' . $this->get_user_id() . '-' . $this->get_id();
		$cached_data = LP_Object_Cache::get( $cache_key, 'learn-press/course-results' );

		if ( false === $cached_data || ! array_key_exists( 'marks', $cached_data ) ) {
			$data = array(
				'result' => 0,
				'status' => $this->get_status(),
			);

			$result          = 0;
			$result_of_items = 0;

			$items = $this->get_items();

			if ( $items ) {
				foreach ( $items as $item ) {
					if ( $item->get_type() !== LP_QUIZ_CPT ) {
						continue;
					}

					$questions   = $item->get_questions();
					$quiz_result = $item->get_results( '' );

					if ( $questions ) {
						foreach ( $questions as $question_id ) {
							$question = LP_Question::get_question( $question_id );

							if ( $question ) {
								$result_of_items += absint( $question->get_mark() );
							}
						}
					}

					if ( $quiz_result ) {
						if ( $quiz_result['user_mark'] ) {
							$result += $quiz_result['user_mark'];
						}
					}
				}

				$result         = $result_of_items ? ( $result * 100 ) / $result_of_items : 0;
				$data['result'] = $result;
			}

			settype( $cached_data, 'array' );
			$cached_data['marks'] = $data;

			LP_Object_Cache::set( $cache_key, $cached_data, 'learn-press/course-results' );
		}

		return isset( $cached_data['marks'] ) ? $cached_data['marks'] : array();
	}

	/**
	 * Get graduation
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_grade( string $context = 'display' ): string {
		$grade = $this->get_graduation() ?? '';

		return $context == 'display' ? learn_press_course_grade_html( $grade, false ) : $grade;
	}

	/**
	 * @return bool
	 */
	public function is_passed() {
		return $this->get_grade() == 'passed';
	}

	/**
	 * @param int $decimal
	 *
	 * @return int|string
	 */
	public function get_percent_result( $decimal = 1 ) {
		return apply_filters(
			'learn-press/user/course-percent-result',
			sprintf(
				'%s%%',
				round( $this->get_results( 'result' ), $decimal ),
				$this->get_user_id(),
				$this->get_item_id()
			)
		);
	}

	/**
	 * Evaluate course result by lessons.
	 *
	 * @param bool $hard
	 *
	 * @return array
	 */
	protected function _evaluate_course_by_lesson( $hard = false ) {
		$cache_key   = 'user-course-' . $this->get_user_id() . '-' . $this->get_id();
		$cached_data = LP_Object_Cache::get( $cache_key, 'learn-press/course-results' );

		if ( $hard || false === $cached_data || ! array_key_exists( 'lessons', $cached_data ) ) {
			$completing = $this->get_completed_items( LP_LESSON_CPT, true );

			if ( $completing[1] ) {
				$result = $completing[0] / $completing[1];
			} else {
				$result = 0;
			}

			$result *= 100;
			$data    = array(
				'result' => $result,
				'status' => $this->get_status(),
			);

			if ( $cached_data ) {
				$cached_data['lessons'] = $data;
			} else {
				$cached_data = array( 'lessons' => $data );
			}

			LP_Object_Cache::set( $cache_key, $cached_data, 'learn-press/course-results' );
		}

		return isset( $cached_data['lessons'] ) ? $cached_data['lessons'] : array();
	}

	/**
	 * Evaluate course result by lessons.
	 *
	 * @param $count_items_completed
	 * @param int $total_item_lesson
	 *
	 * @return array
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.0
	 */
	protected function evaluate_course_by_lesson( $count_items_completed, int $total_item_lesson = 0 ): array {
		$evaluate = array(
			'result' => 0,
			'pass'   => 0,
		);

		$count_items_completed = intval( $count_items_completed->{LP_LESSON_CPT . '_status_completed'} ?? 0 );

		if ( $total_item_lesson && $count_items_completed ) {
			$evaluate['result'] = $count_items_completed * 100 / $total_item_lesson;
		}

		$passing_condition = $this->_course->get_passing_condition();
		if ( $evaluate['result'] >= $passing_condition ) {
			$evaluate['pass'] = 1;
		}

		return $evaluate;
	}

	/**
	 * Evaluate course result by final quiz.
	 *
	 * @return array
	 */
	protected function evaluate_course_by_final_quiz(): array {
		$lp_user_items_db       = LP_User_Items_DB::getInstance();
		$lp_user_item_result_db = LP_User_Items_Result_DB::instance();
		$evaluate               = array(
			'result' => 0,
			'pass'   => 0,
		);

		try {
			$quiz_final_id = get_post_meta( $this->get_course_id(), '_lp_final_quiz', true );

			if ( ! $quiz_final_id ) {
				throw new Exception( '' );
			}

			$quiz_final = learn_press_get_quiz( $quiz_final_id );

			if ( ! $quiz_final ) {
				throw new Exception( 'Quiz final invalid' );
			}

			$user_course = $this->get_last_user_course();

			if ( ! $user_course ) {
				throw new Exception( 'User course not exists' );
			}

			$filter             = new LP_User_Items_Filter();
			$filter->query_type = 'get_row';
			$filter->parent_id  = $user_course->user_item_id;
			$filter->item_type  = LP_QUIZ_CPT;
			$filter->item_id    = $quiz_final_id;
			$user_quiz          = $lp_user_items_db->get_user_course_items_by_item_type( $filter );

			if ( ! $user_quiz ) {
				throw new Exception();
			}

			// Get result did quiz
			$quiz_result = $lp_user_item_result_db->get_result( $user_quiz->user_item_id );

			if ( $quiz_result ) {
				if ( ! isset( $quiz_result['result'] ) ) {
					$evaluate['result'] = $quiz_result['user_mark'] * 100 / $quiz_result['mark'];
				} else {
					$evaluate['result'] = $quiz_result['result'];
				}

				$passing_condition = floatval( $quiz_final->get_data( 'passing_grade', 0 ) );
				if ( $evaluate['result'] >= $passing_condition ) {
					$evaluate['pass'] = 1;
				}
			}
		} catch ( Throwable $e ) {

		}

		return $evaluate;
	}

	/**
	 * Evaluate course results by count quizzes passed/all quizzes.
	 *
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.0
	 */
	protected function evaluate_course_by_quizzes_passed( $count_items_completed, $total_item_quizzes ): array {
		$evaluate = array(
			'result' => 0,
			'pass'   => 0,
		);

		$count_items_completed = intval( $count_items_completed->{LP_QUIZ_CPT . '_graduation_passed'} ?? 0 );

		if ( $total_item_quizzes && $count_items_completed ) {
			$evaluate['result'] = $count_items_completed * 100 / $total_item_quizzes;

			$passing_condition = $this->_course->get_passing_condition();
			if ( $evaluate['result'] >= $passing_condition ) {
				$evaluate['pass'] = 1;
			}
		}

		return $evaluate;
	}

	/**
	 * Evaluate course results by count questions true/all questions.
	 *
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.0
	 */
	private function evaluate_course_by_questions( &$evaluate, $lp_quizzes, $total_questions ) {
		$lp_user_item_results_db = LP_User_Items_Result_DB::instance();
		$count_questions_correct = 0;

		// get questions correct
		foreach ( $lp_quizzes as $lp_quiz ) {
			$lp_quiz_result = $lp_user_item_results_db->get_result( $lp_quiz->user_item_id );
			if ( $lp_quiz_result ) {
				$count_questions_correct += $lp_quiz_result['question_correct'];
			}
		}

		if ( $total_questions && $count_questions_correct ) {
			$evaluate['result'] = $count_questions_correct * 100 / $total_questions;

			$passing_condition = $this->_course->get_passing_condition();
			if ( $evaluate['result'] >= $passing_condition ) {
				$evaluate['pass'] = 1;
			}
		}
	}

	/**
	 * Evaluate course results by total mark of questions.
	 *
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.0
	 */
	private function evaluate_course_by_mark( &$evaluate, $lp_quizzes, $total_mark_questions ) {
		$lp_user_item_results_db       = LP_User_Items_Result_DB::instance();
		$count_mark_questions_receiver = 0;

		foreach ( $lp_quizzes as $lp_quiz ) {
			$lp_quiz_result = $lp_user_item_results_db->get_result( $lp_quiz->user_item_id );
			if ( $lp_quiz_result ) {
				$count_mark_questions_receiver += $lp_quiz_result['user_mark'];
			}
		}

		if ( $count_mark_questions_receiver && $total_mark_questions ) {
			$evaluate['result'] = $count_mark_questions_receiver * 100 / $total_mark_questions;

			$passing_condition = floatval( $this->_course->get_passing_condition() );
			if ( $evaluate['result'] >= $passing_condition ) {
				$evaluate['pass'] = 1;
			}
		}
	}

	/**
	 * Evaluate course results by total mark of questions.
	 *
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.0
	 */
	protected function evaluate_course_by_question( string $evaluate_type ): array {
		$lp_user_items_db = LP_User_Items_DB::getInstance();
		$evaluate         = array(
			'result' => 0,
			'pass'   => 0,
		);

		try {
			$user_course = $this->get_last_user_course();

			if ( ! $user_course ) {
				throw new Exception( 'User course not exists!' );
			}

			// get quiz_ids
			$filter_get_quiz_ids            = new LP_User_Items_Filter();
			$filter_get_quiz_ids->parent_id = $user_course->user_item_id;
			$filter_get_quiz_ids->item_type = LP_QUIZ_CPT;
			$lp_quizzes                     = $lp_user_items_db->get_user_course_items_by_item_type( $filter_get_quiz_ids );

			// Get total questions, mark
			// Todo: Tungnx - save (questions, mark) total when save quiz, course, if not query again
			$course = $this->_course;
			if ( is_int( $course ) ) {
				$course = learn_press_get_course( $course );
			}

			$total_questions     = 0;
			$total_mark_question = 0;

			// get all item by course
			$items = $course->get_item_ids();

			foreach ( $items as $item_id ) {
				$item = $course->get_item( $item_id );
				if ( $item->get_item_type() == LP_QUIZ_CPT ) {
					$total_questions     += count( $item->get_questions() );
					$total_mark_question += $item->get_mark();
				}
			}
			// End get total questions, mark

			switch ( $evaluate_type ) {
				case 'evaluate_questions':
					$this->evaluate_course_by_questions( $evaluate, $lp_quizzes, $total_questions );
					break;
				case 'evaluate_mark':
					$this->evaluate_course_by_mark( $evaluate, $lp_quizzes, $total_mark_question );
					break;
				default:
					break;
			}

			// Get results of each quiz - has questions
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}

		return $evaluate;
	}

	/**
	 * Finish course for user
	 *
	 * @return int
	 */
	public function finish() {
		$status = apply_filters(
			'learn-press/finish-course-status',
			'finished',
			$this->get_course_id(),
			$this->get_user(),
			$this
		);

		// $results = $this->calculate_course_results();

		return parent::complete( $status );
	}

	/**
	 * Check course of use has enrolled
	 *
	 * @throws Exception
	 */
	public function is_enrolled(): bool {
		return $this->get_status() == LP_COURSE_ENROLLED;
	}

	/**
	 * Check course of use has purchased
	 *
	 * @throws Exception
	 * @author tungnx
	 * @since 4.1.3
	 * @version 1.0.0
	 */
	public function is_purchased(): bool {
		return $this->get_status() == LP_COURSE_PURCHASED;
	}

	public function get_level() {
		if ( ! $this->is_exists() ) {
			return 0;
		}

		$level = 10;

		switch ( $this->get_status() ) {
			case 'enrolled':
				$level = 20;
				break;
			case 'finished':
				$level = 30;
				break;

		}

		return $level;
	}

	/**
	 * Evaluate course result by final quiz.
	 *
	 * @return array
	 */
	protected function _evaluate_course_by_final_quiz() {
		$cache_key   = 'user-course-' . $this->get_user_id() . '-' . $this->get_id();
		$cached_data = LP_Object_Cache::get( $cache_key, 'learn-press/course-results' );

		if ( false === $cached_data || ! array_key_exists( 'final-quiz', $cached_data ) ) {
			$course     = $this->get_course();
			$final_quiz = $course->get_final_quiz();
			$user_quiz  = $this->get_item( $final_quiz );
			$result     = false;

			if ( $user_quiz ) {
				$result = $user_quiz->get_results( false );
			}

			$percent = $result ? $result['result'] : 0;
			$data    = array(
				'result' => $percent,
				'status' => $this->get_status(),
			);

			settype( $cached_data, 'array' );
			$cached_data['final-quiz'] = $data;

			LP_Object_Cache::set( $cache_key, $cached_data, 'learn-press/course-results' );
		}

		return isset( $cached_data['final-quiz'] ) ? $cached_data['final-quiz'] : array();
	}

	/**
	 * Evaluate course result by point of quizzes doing/done per total quizzes.
	 *
	 * @return array
	 * @editor tungnx
	 * @modify 4.1.3 - comment - not use
	 */
	/*
	protected function _evaluate_course_by_quizzes() {
		$cache_key   = 'user-course-' . $this->get_user_id() . '-' . $this->get_id();
		$cached_data = LP_Object_Cache::get( $cache_key, 'learn-press/course-results' );

		if ( ( false === $cached_data ) || ! array_key_exists( 'quizzes', $cached_data ) ) {
			$data = array(
				'result' => 0,
				'status' => $this->get_status(),
			);

			$result          = 0;
			$result_of_items = 0;

			$items = $this->get_items();

			if ( $items ) {
				foreach ( $items as $item ) {
					if ( $item->get_type() !== LP_QUIZ_CPT ) {
						continue;
					}

					if ( $item->get_quiz()->get_data( 'passing_grade' ) ) {
						$result += $item->get_results( 'result' );
						$result_of_items ++;
					}
				}

				$result         = $result_of_items ? $result / $result_of_items : 0;
				$data['result'] = $result;
			}

			settype( $cached_data, 'array' );
			$cached_data['quizzes'] = $data;

			LP_Object_Cache::set( $cache_key, $cached_data, 'learn-press/course-results' );
		}

		return isset( $cached_data['quizzes'] ) ? $cached_data['quizzes'] : array();
	}*/

	protected function _is_passed( $result ) {
		$is_passed = LP_COURSE_GRADUATION_FAILED;
		$result    = round( $result, 2 );

		if ( $result >= $this->get_passing_condition() ) {
			$is_passed = LP_COURSE_GRADUATION_PASSED;
		}

		return apply_filters( 'learnpress/user/course/is-passed', $is_passed, $result );
	}

	/**
	 * Get completed items.
	 *
	 * @param string $type - Optional. Filter by type (such lp_quiz, lp_lesson) if passed
	 * @param bool   $with_total - Optional. Include total if TRUE
	 * @param int    $section_id - Optional. Get in specific section
	 *
	 * @return array|bool|mixed
	 * @editor tungnx
	 */
	public function get_completed_items( $type = '', $with_total = false, $section_id = 0 ) {

		$this->read_items();

		// $completed_items = array(0,100);
		// return $with_total ? $completed_items : $completed_items[0];

		if ( ! $this->_course ) {
			return;
		}

		$key = sprintf(
			'%d-%d-%s',
			$this->get_user_id(),
			$this->_course->get_id(),
			md5( build_query( func_get_args() ) )
		);

		$completed_items = LP_Object_Cache::get( $key, 'learn-press/user-completed-items' );

		if ( false === $completed_items ) {
			$completed     = 0;
			$total         = 0;
			$section_items = array();

			if ( $section_id ) {
				$section = $this->_course->get_sections( 'object', $section_id );

				if ( $section ) {
					$section_items = $section->get_items();

					if ( $section_items ) {
						$section_items = array_keys( $section_items );
					}
				}
			}

			$items = $this->get_items();
			if ( $items ) {
				foreach ( $items as $item ) {

					if ( $section_id && ! in_array( $item->get_id(), $section_items ) ) {
						continue;
					}

					if ( $type ) {
						$item_type = $item->get_data( 'item_type' );
					} else {
						$item_type = '';
					}

					if ( $type === $item_type ) {
						if ( $item->get_status() == 'completed' ) {
							$completed ++;
						}
						$completed = apply_filters(
							'learn-press/course-item/completed',
							$completed,
							$item,
							$item->get_status()
						);
						// if ( ! $item->is_preview() ) {
						$total ++;
						// }
					}
				}
			}
			$completed_items = array( $completed, $total );
			LP_Object_Cache::set( $key, $completed_items, 'learn-press/user-completed-items' );
		}

		return $with_total ? $completed_items : $completed_items[0];
	}

	/**
	 * Get completed items.
	 *
	 * @return object
	 * @editor tungnx
	 * @modify 4.1.4.1
	 * @since 4.0.0
	 * @version 4.0.1
	 */
	public function count_items_completed() {
		$lp_user_items_db      = LP_User_Items_DB::getInstance();
		$count_items_completed = new stdClass();

		try {
			$course = learn_press_get_course( $this->get_course_id() );

			if ( ! $course ) {
				throw new Exception( __FUNCTION__ . ': Course is invalid!' );
			}

			$user_course = $this->get_last_user_course();

			if ( ! $user_course ) {
				throw new Exception();
			}

			$filter_count             = new LP_User_Items_Filter();
			$filter_count->parent_id  = $user_course->user_item_id;
			$filter_count->item_id    = $this->get_course_id();
			$filter_count->user_id    = $this->get_user_id();
			$filter_count->status     = 'completed';
			$filter_count->graduation = LP_COURSE_GRADUATION_PASSED;
			$count_items_completed    = $lp_user_items_db->count_items_of_course_with_status( $filter_count );
		} catch ( Throwable $e ) {

		}

		return $count_items_completed;
	}

	/**
	 * Get items completed by percentage.
	 *
	 * @param string $type - Optional. Filter by type or not
	 * @param int    $section_id - Optional. Get in specific section
	 *
	 * @return float|int
	 */
	public function get_percent_completed_items( $type = '', $section_id = 0 ) {
		$values = $this->get_completed_items( $type, true, $section_id );
		if ( $values[1] ) {
			return $values[0] / $values[1] * 100;
		}

		return 0;
	}

	/**
	 * Get passing condition criteria.
	 *
	 * @return string
	 */
	public function get_passing_condition() {
		return $this->_course->get_passing_condition();
	}

	/**
	 * Get all items in course.
	 *
	 * @param bool $refresh
	 *
	 * @return LP_User_Item[]
	 */
	public function get_items( $refresh = false ) {
		return $this->read_items( $refresh );

		/*
		return LP_Object_Cache::get(
			$this->get_user_id() . '-' . $this->get_id(),
			'learn-press/user-course-item-objects'
		);*/
	}

	/**
	 * Check course is completed or not.
	 *
	 * @return bool
	 * @throws Exception
	 * @editor tungnx
	 * @modify 4.1.3
	 */
	public function is_finished(): bool {
		return $this->get_status() == LP_COURSE_FINISHED;
	}

	/**
	 * Check course graduation is passed or not.
	 *
	 * @return bool
	 */
	public function is_graduated() {
		return $this->get_graduation();
	}

	/**
	 * @return bool
	 */
	public function can_graduated() {
		return $this->get_results( 'result' ) >= $this->get_passing_condition();
	}

	function __destruct() {
		// TODO: Implement __unset() method.
	}

	public function count_history_items( $item_id ) {

		if ( false === ( $history = LP_Object_Cache::get(
			'course-' . $this->get_item_id() . '-' . $this->get_user_id(),
			'learn-press/items-history'
		) ) ) {
			global $wpdb;
			$query = $wpdb->prepare(
				"
				SELECT item_id, COUNT(user_item_id) `count`
				FROM {$wpdb->learnpress_user_items}
				WHERE user_id = %d
					AND parent_id = %d
				GROUP BY item_id
			",
				$this->get_user_id(),
				$this->get_user_item_id()
			);

			$history = array();
			if ( $results = $wpdb->get_results( $query ) ) {
				foreach ( $results as $result ) {
					$history[ $result->item_id ] = $result->count;
				}
			}

			LP_Object_Cache::set(
				'course-' . $this->get_item_id() . '-' . $this->get_user_id(),
				$history,
				'learn-press/items-history'
			);
		}

		return isset( $history[ $item_id ] ) ? $history[ $item_id ] : 0;
	}

	/**
	 * @param int $item_id
	 *
	 * @return LP_User_Item|LP_User_Item_Quiz|bool
	 */
	public function get_item( $item_id ) {
		return $this->offsetGet( $item_id );
	}

	/**
	 * Write again get_item
	 *
	 * @param int $item_id
	 *
	 * @return LP_User_Item|LP_User_Item_Quiz|bool
	 * @author tungnx
	 */
	public function getItem( $item_id ) {

	}

	/**
	 * @param int $user_item_id
	 *
	 * @return LP_User_Item|LP_User_Item_Quiz|bool
	 */
	/*
	public function get_item_by_user_item_id( $user_item_id ) {
		$this->read_items();

		if ( ! empty( $this->_items_by_item_ids[ $user_item_id ] ) ) {
			$item_id = $this->_items_by_item_ids[ $user_item_id ];

			return $this->get_item( $item_id );
		}

		return false;
	}*/

	/**
	 * @param $item
	 *
	 * @return bool|LP_User_Item
	 */
	public function set_item( $item ) {
		if ( $item = LP_User_Item::get_item_object( $item ) ) {
			$this->cache_set_item( $item );
		}

		return $item;
	}

	/**
	 * @param LP_User_Item $item
	 */
	public function cache_set_item( $item ) {
		if ( ! $items = $this->read_items() ) {
			$items = array();
		}
		$items[ $item->get_item_id() ] = $item;
		LP_Object_Cache::set(
			$this->get_user_id() . '-' . $this->get_id(),
			$items,
			'learn-press/user-course-item-objects'
		);
	}

	public function cache_get_items() {
		return LP_Object_Cache::get(
			$this->get_user_id() . '-' . $this->get_id(),
			'learn-press/user-course-item-objects'
		);
	}

	/**
	 * @param        $item_id
	 * @param string $prop
	 *
	 * @return bool|float|int
	 * @throws Exception
	 */
	public function get_item_result( $item_id, $prop = 'result' ) {
		$item = $this->get_item( $item_id );

		if ( $item instanceof LP_User_Item_Quiz ) {
			/**
			 * @var LP_User_Item_Quiz $item
			 */
			return $item->get_graduation();
		} elseif ( $item ) {
			return $item->get_result( $prop );
		}

		return false;
	}

	/**
	 * @param int $at
	 *
	 * @return LP_User_Item_Course
	 * @editor tungnx
	 * @modify 4.1.3 - comment - not use
	 */
	/*
	public function get_item_at( $at = 0 ) {
		$items   = $this->read_items();
		$item_id = ! empty( $this->_items_by_order[ $at ] ) ? $this->_items_by_order[ $at ] : 0;
		if ( ! $item_id && $items ) {
			$items   = array_values( $items );
			$item_id = $items[ $at ]->get_id();
		}

		return $this->offsetGet( $item_id );
	}*/

	/**
	 * @param $id
	 *
	 * @return LP_User_Item_Quiz|bool
	 */
	public function get_item_quiz( $id ) {

		return $this->get_item( $id );
	}

	/**
	 * Get js settings of course.
	 *
	 * @return array
	 */
	public function get_js_args() {
		$js_args = false;
		$course  = $this->get_course();

		if ( $course ) {
			$item    = false;
			$js_args = array(
				'root_url'     => trailingslashit( get_home_url() ),
				'id'           => $course->get_id(),
				'url'          => $course->get_permalink(),
				'result'       => $this->get_results(),
				'current_item' => $item ? $item->get_id() : false,
				//'items'        => $this->get_items_for_js(),
			);
		}

		return apply_filters( 'learn-press/course/single-params', $js_args, $this->get_id() );
	}

	public function update_item_retaken_count( $item_id, $count = 0 ) {
		$items = $this->get_meta( '_retaken_items' );
		if ( is_string( $count ) && preg_match( '#^(\+|\-)([0-9]+)#', $count, $m ) ) {
			$last_count = ! empty( $items[ $item_id ] ) ? $items[ $item_id ] : 0;
			$count      = $m[1] == '+' ? ( $last_count + $m[2] ) : ( $last_count - $m[2] );
		}

		$items[ $item_id ] = $count;

		learn_press_update_user_item_meta( $this->get_user_item_id(), '_retaken_items', $items );

		return $count;
	}

	public function get_item_retaken_count( $item_id ) {
		$items = $this->get_meta( '_retaken_items' );
		$count = false;

		if ( is_array( $items ) && array_key_exists( $item_id, $items ) ) {
			$count = absint( $items[ $item_id ] );
		}

		return $count;
	}

	/**
	 * Get number of retaken times for user course.
	 *
	 * @return int
	 */
	public function get_retaken_count(): int {
		return (int) ( learn_press_get_user_item_meta( $this->get_user_item_id(), '_lp_retaken_count' ) );
	}

	/**
	 * Increase retaken count.
	 *
	 * @return bool|int
	 */
	public function increase_retake_count() {
		$count = $this->get_retaken_count();
		$count ++;

		return $this->update_meta( '_lp_retaken_count', $count );
	}

	/**
	 * Get js settings of course items.
	 *
	 * @return array
	 * @editor tungnx
	 * @modify 4.1.4.1 - comment - not use
	 */
	// public function get_items_for_js() {
	//
	// *** TEST CACHE */
	// return false;
	// $args = array();
	// if ( $items = $this->get_items() ) {
	// $user   = $this->get_user();
	// $course = $this->get_course();
	// foreach ( $items as $item ) {
	//
	// $args[ $item->get_id() ] = $item->get_js_args();// $item_js;
	// }
	// }
	//
	// return apply_filters( 'learn-press/course/items-for-js', $args, $this->get_id(), $this->get_user_id() );
	// }

	/**
	 * Update course item and it's child.
	 *
	 * @TODO: tungnx - review to modify
	 */
	public function save() {
		/**
		 * @var LP_User_Item $item
		 */
		$this->update();
		$items = $this->get_items();
		if ( ! $items ) {
			return false;
		}

		foreach ( $items as $item_id => $item ) {

			if ( ! $item->get_status() ) {
				continue;
			}

			/**
			 * Auto fill the end-time if it isn't already set
			 */
			if ( in_array( $item->get_status(), array( 'completed', 'finished' ) ) ) {

				if ( ! $item->get_end_time() || $item->get_end_time()->is_null() ) {
					$item->set_end_time( current_time( 'mysql', 1 ) );
				}
			}

			$item->update();
		}

		return true;
	}

	/**
	 * Get passed items.
	 *
	 * @param string $type - Optional. Filter by type (such lp_quiz, lp_lesson) if passed
	 * @param bool   $with_total - Optional. Include total if TRUE
	 * @param int    $section_id - Optional. Get in specific section
	 *
	 * @return array|bool|mixed
	 */
	public function get_passed_items( $type = '', $with_total = false, $section_id = 0 ) {
		$this->read_items();

		$key          = sprintf(
			'%d-%d-%s',
			$this->get_user_id(),
			$this->_course->get_id(),
			md5( build_query( func_get_args() ) )
		);
		$passed_items = LP_Object_Cache::get( $key, 'learn-press/user-passed-items' );

		if ( false === $passed_items ) {
			$passed        = 0;
			$total         = 0;
			$section_items = array();

			$section = $this->_course->get_sections( 'object', $section_id );

			if ( $section_id && $section ) {
				$section_items = $section->get_items();

				if ( $section_items ) {
					$section_items = array_keys( $section_items );
				}
			}

			$items = $this->get_items();

			if ( $items ) {
				foreach ( $items as $item ) {

					if ( $section_id && ! in_array( $item->get_id(), $section_items ) ) {
						continue;
					}

					if ( $type ) {
						$item_type = $item->get_data( 'item_type' );
					} else {
						$item_type = '';
					}

					if ( $type === $item_type ) {
						if ( $item->get_status( 'graduation' ) == 'passed' ) {
							$passed ++;
						}
						$passed = apply_filters(
							'learn-press/course-item/passed',
							$passed,
							$item,
							$item->get_status()
						);
						// if ( ! $item->is_preview() ) {
						$total ++;
						// }
					}
				}
			}
			$passed_items = array( $passed, $total );
			LP_Object_Cache::set( $key, $passed_items, 'learn-press/user-passed-items' );
		}

		return $with_total ? $passed_items : $passed_items[0];
	}

	/**
	 * Get Order ID
	 *
	 * @return array|mixed
	 * @since 4.1.3
	 * @version 1.0.0
	 * @author tungnx
	 */
	public function get_order_id() {
		return $this->get_data( 'ref_id', 0 );
	}

	/**
	 * Get Order
	 *
	 * @throws Exception
	 * @since 4.1.3
	 * @version 1.0.0
	 * @author tungnx
	 */
	public function get_order() {
		$order = false;

		if ( $this->get_order_id() ) {
			$order = new LP_Order( $this->get_order_id() );
		}

		return $order;
	}

	/**
	 * Get child item ids by type item
	 *
	 * @return object|null
	 */
	public function get_last_user_course() {
		$lp_user_items_db = LP_User_Items_DB::getInstance();
		$user_course      = null;

		try {
			$filter_user_course          = new LP_User_Items_Filter();
			$filter_user_course->item_id = $this->get_course_id();
			$filter_user_course->user_id = $this->get_user_id();
			$user_course                 = $lp_user_items_db->get_last_user_course( $filter_user_course );
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ':' . $e->getMessage() );
		}

		return $user_course;
	}

	/**
	 * Get courses only by course's user are learning
	 *
	 * @param LP_User_Items_Filter $filter
	 * @param int $total_rows return total row query
	 *
	 * @return array|null|int|string
	 */
	public static function get_user_courses( LP_User_Items_Filter $filter, int &$total_rows = 0 ) {
		try {
			/*$key_cache     = md5( json_encode( $filter ) );
			$courses_cache = LP_Cache::instance()->get_cache( $key_cache );

			if ( false !== $courses_cache ) {
				return $courses_cache;
			}*/

			$courses = LP_User_Items_DB::getInstance()->get_user_courses( $filter, $total_rows );
			//LP_Cache::instance()->set_cache( $key_cache, $courses );
		} catch ( Throwable $e ) {
			$courses = null;
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}

		return $courses;
	}

	public function get_total_users_learn_course( LP_User_Items_Filter $filter ) {

	}
}
