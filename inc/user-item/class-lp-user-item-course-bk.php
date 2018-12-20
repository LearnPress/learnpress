<?php

/**
 * Class LP_User_Item_Course
 */
class LP_User_Item_CourseY extends LP_User_Item implements ArrayAccess {
	/**
	 * Course's items
	 *
	 * @var array
	 */
	protected $_items = false;

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

		parent::__construct( $item );

		$this->_curd    = new LP_User_CURD();
		$this->_changes = array();
		$this->load();
	}

	public function load() {
		if ( ! $this->_loaded ) {
			$this->read_items();
			//$this->read_items_meta();
			$this->_loaded = true;
		}
	}

	/**
	 * Read items's data of course for the user.
	 */
	public function read_items() {
		$this->_items  = array();
		$this->_course = learn_press_get_course( $this->get_id() );

		if ( ! $this->_course || ( ! $user_course_item_id = $this->get_user_item_id() ) ) {
			return false;
		}
		$course_items = $this->_course->get_item_ids();

		if ( ! $course_items ) {
			return false;
		}

		if ( $user_course_items = $this->_curd->read_course_items_by_user_item_id( $user_course_item_id ) ) {

			// Convert keys of array from numeric to keys is item id
			foreach ( array_keys( $user_course_items ) as $key ) {
				$user_course_item                                = $user_course_items[ $key ];
				$user_course_items[ $user_course_item->item_id ] = $user_course_item;
				unset( $user_course_items[ $key ] );
			}
		} else {
			$user_course_items = array();
		}

		foreach ( $course_items as $item_id ) {

			if ( ! empty( $user_course_items[ $item_id ] ) ) {
				$user_course_item = (array) $user_course_items[ $item_id ];
			} else {
				$user_course_item = array(
					'item_id'   => $item_id,
					'ref_id'    => $this->get_id(),
					'parent_id' => $user_course_item_id
				);
			}

			if ( $course_item = apply_filters( 'learn-press/user-course-item', LP_User_Item::get_item_object( $user_course_item ), $user_course_item, $this ) ) {
				$this->_items[ $item_id ]                                     = $course_item;
				$this->_items_by_item_ids[ $course_item->get_user_item_id() ] = $item_id;
			}
		}

		return true;
	}

	public function is_exceeded() {
		$exceeded = DAY_IN_SECONDS * 360 * 100;

		if ( ! $course = $this->get_course() ) {
			return $exceeded;
		}

		if ( ! $course->get_duration() ) {
			return $exceeded;
		}

		return parent::is_exceeded();
	}

	public function get_finishing_type() {
		if ( ! $type = $this->get_meta( 'finishing_type' ) ) {
			$type = $this->is_exceeded() <= 0 ? 'exceeded' : 'click';
			learn_press_update_user_item_meta( $this->get_user_item_id(), 'finishing_type', $type );
			$this->set_meta( 'finishing_type', $type );
		}

		return $type;
	}

	/**
	 * Read item meta.
	 *
	 * @return mixed|bool
	 */
	public function read_items_meta() {

		$item_ids = array();
		if ( $this->_items ) {
			foreach ( $this->_items as $item ) {
				if ( $item->get_user_item_id() ) {
					$item_ids[ $item->get_user_item_id() ] = $item->get_item_id();
				}
			}
		}

		if ( ! $item_ids ) {
			return false;
		}

		global $wpdb;
		$meta_ids = array_keys( $item_ids );
		$format   = array_fill( 0, sizeof( $meta_ids ), '%d' );
		$sql      = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id IN(" . join( ',', $format ) . ")
		", $meta_ids );

		if ( $results = $wpdb->get_results( $sql ) ) {
			foreach ( $results as $result ) {
				$item_id = $item_ids[ $result->learnpress_user_item_id ];

				if ( $item_id === $this->get_item_id() ) {
					$this->add_meta( $result );
				} else {
					$item               = $this->get_item( $item_id );
					$result->meta_value = LP_Helper::maybe_unserialize( $result->meta_value );

					$item->add_meta( $result );
				}
			}
		}

		return $this->_meta_data;
	}

	public function offsetSet( $offset, $value ) {
		//$this->set_data( $offset, $value );
		// Do not allow to set value directly!
	}

	public function offsetUnset( $offset ) {
		// Do not allow to unset value directly!
	}

	public function offsetGet( $offset ) {
		$this->load();

		return $this->offsetExists( $offset ) ? $this->_items[ $offset ] : false;
	}

	public function offsetExists( $offset ) {
		$this->load();

		return array_key_exists( $offset, $this->_items );
	}

	public function evaluate() {

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
	 * Get result of course.
	 *
	 * @param string $prop
	 *
	 * @return float|int
	 */
	public function get_results( $prop = 'result' ) {

		if ( ! $course = $this->get_course() ) {
			return false;
		}

		$this->load();
		$course_result = $course->get_data( 'course_result' );
		$results       = false;
		switch ( $course_result ) {
			// Completed lessons per total
			case 'evaluate_lesson':
				$results = $this->_evaluate_course_by_lesson();
				break;
			// Results of final quiz
			case 'evaluate_final_quiz':
				$results = $this->_evaluate_course_by_final_quiz();
				break;
			// Points of quizzes per points of all quizzes
			case 'evaluate_quizzes':
				$results = $this->_evaluate_course_by_quizzes();
				break;
			// Points of passed quizzes per points of all quizzes
			case 'evaluate_passed_quizzes':
				$results = $this->_evaluate_course_by_passed_quizzes();
				break;
			// Points of completed (may not passed) quizzes per points of all quizzes
			case 'evaluate_quiz':
				$results = $this->_evaluate_course_by_completed_quizzes();
				break;
			default:
				$results = apply_filters( 'learn-press/evaluate_passed_conditions', $course_result, $this );
				break;
		}

		if ( is_array( $results ) ) {
			$count_items     = $course->count_items( '', true );
			$completed_items = $this->get_completed_items();
			$results         = array_merge(
				$results,
				array(
					'count_items'     => $count_items,
					'completed_items' => $completed_items,
					'skipped_items'   => $count_items - $completed_items,
					'status'          => $this->get_status(),
					'grade'           => ''
				)
			);

			if ( ! in_array( $this->get_status(), array( 'purchased', 'viewed' ) ) ) {
				$results['grade'] = $this->is_finished() ? $this->_is_passed( $results['result'] ) : 'in-progress';
			} else {
			}
		}

		if ( $prop === 'status' ) {
			if ( isset( $results['grade'] ) ) {
				$prop = 'grade';
			}
		}

		///$result = wp_cache_get( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), 'learn-press/user-course-results' );

		return $prop && $results && array_key_exists( $prop, $results ) ? $results[ $prop ] : $results;
	}

	/**
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_grade( $context = '' ) {
		$grade = $this->get_results( 'grade' );

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
		return apply_filters( 'learn-press/user/course-percent-result', sprintf( '%s%%', round( $this->get_results( 'result' ), $decimal ), $this->get_user_id(), $this->get_item_id() ) );
	}

	/**
	 * Evaluate course result by lessons.
	 *
	 * @return array
	 */
	protected function _evaluate_course_by_lesson() {

		$cache_key = 'user-course-' . $this->get_user_id() . '-' . $this->get_id();

		if ( false === ( $cached_data = wp_cache_get( $cache_key, 'learn-press/course-results' ) ) || ! array_key_exists( 'lessons', $cached_data ) ) {
			$completing = $this->get_completed_items( LP_LESSON_CPT, true );
			if ( $completing[1] ) {
				$result = $completing[0] / $completing[1];
			} else {
				$result = 0;
			}
			$result *= 100;
			$data   = array(
				'result' => $result,
				'grade'  => $this->is_finished() ? $this->_is_passed( $result ) : '',
				'status' => $this->get_status()
			);

			settype( $cached_data, 'array' );
			$cached_data['lessons'] = $data;

			wp_cache_set( $cache_key, $cached_data, 'learn-press/course-results' );
		}

		return isset( $cached_data['lessons'] ) ? $cached_data['lessons'] : array();
	}

	/**
	 * Finish course for user
	 *
	 * @return int
	 */
	public function finish() {

		$return = parent::complete( 'finished' );

		return $return;
	}

	/**
	 * Evaluate course result by final quiz.
	 *
	 * @return array
	 */
	protected function _evaluate_course_by_final_quiz() {

		$cache_key = 'user-course-' . $this->get_user_id() . '-' . $this->get_id();

		if ( false === ( $cached_data = wp_cache_get( $cache_key, 'learn-press/course-results' ) ) || ! array_key_exists( 'final-quiz', $cached_data ) ) {
			$course     = $this->get_course();
			$final_quiz = $course->get_final_quiz();
			$result     = false;
			if ( $user_quiz = $this->get_item( $final_quiz ) ) {
				$result = $user_quiz->get_results( false );
			}

			$percent = $result ? $result['result'] : 0;
			$data    = array(
				'result' => $percent,
				'grade'  => $this->is_finished() ? $this->_is_passed( $percent ) : '',
				'status' => $this->get_status()
			);
			settype( $cached_data, 'array' );
			$cached_data['final-quiz'] = $data;

			wp_cache_set( $cache_key, $cached_data, 'learn-press/course-results' );
		}

		return isset( $cached_data['final-quiz'] ) ? $cached_data['final-quiz'] : array();
	}

	/**
	 * Evaluate course result by point of quizzes doing/done per total quizzes.
	 *
	 * @return array
	 */
	protected function _evaluate_course_by_quizzes() {

		$cache_key = 'user-course-' . $this->get_user_id() . '-' . $this->get_id();

		if ( ( false === ( $cached_data = wp_cache_get( $cache_key, 'learn-press/course-results' ) ) ) || ! array_key_exists( 'quizzes', $cached_data ) ) {

			$data            = array( 'result' => 0, 'grade' => '', 'status' => $this->get_status() );
			$result          = 0;
			$result_of_items = 0;

			if ( $items = $this->get_items() ) {
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
				if ( $this->is_finished() ) {
					$data['grade'] = $this->_is_passed( $result );
				}
			}

			settype( $cached_data, 'array' );
			$cached_data['quizzes'] = $data;

			wp_cache_set( $cache_key, $cached_data, 'learn-press/course-results' );
		}

		return isset( $cached_data['quizzes'] ) ? $cached_data['quizzes'] : array();
	}

	/**
	 * Evaluate course result by point of quizzes doing/done per total quizzes.
	 *
	 * @return array
	 */
	protected function _evaluate_course_by_passed_quizzes() {

		$cache_key = 'user-course-' . $this->get_user_id() . '-' . $this->get_id();

		if ( false === ( $cached_data = wp_cache_get( $cache_key, 'learn-press/course-results' ) ) || ! array_key_exists( 'passed-quizzes', $cached_data ) ) {

			$data            = array( 'result' => 0, 'grade' => '', 'status' => $this->get_status() );
			$result          = 0;
			$result_of_items = 0;
			if ( $items = $this->get_items() ) {
				foreach ( $items as $item ) {
					if ( $item->get_type() !== LP_QUIZ_CPT ) {
						continue;
					}
					if ( $item->get_quiz()->get_data( 'passing_grade' ) ) {
						$result += $item->is_passed() ? $item->get_results( 'result' ) : 0;
						$result_of_items ++;
					}
				}
				$result         = $result_of_items ? $result / $result_of_items : 0;
				$data['result'] = $result;

				if ( $this->is_finished() ) {
					$data['grade'] = $this->_is_passed( $result );
				}
			}

			settype( $cached_data, 'array' );
			$cached_data['passed-quizzes'] = $data;

			wp_cache_set( $cache_key, $cached_data, 'learn-press/course-results' );
		}

		return isset( $cached_data['passed-quizzes'] ) ? $cached_data['passed-quizzes'] : array();
	}

	/**
	 * Evaluate course result by number of passed quizzes per total quizzes.
	 *
	 * @return array
	 */
	protected function _evaluate_course_by_completed_quizzes() {
		$cache_key = 'user-course-' . $this->get_user_id() . '-' . $this->get_id();

		if ( false === ( $cached_data = wp_cache_get( $cache_key, 'learn-press/course-results' ) ) || ! array_key_exists( 'completed-quizzes', $cached_data ) ) {
			$course = $this->get_course();

			$data   = array( 'result' => 0, 'grade' => '', 'status' => $this->get_status() );
			$result = 0;

			if ( $items = $this->get_items() ) {
				$result_of_items = 0;
				foreach ( $items as $item ) {
					if ( $item->get_type() !== LP_QUIZ_CPT ) {
						continue;
					}
					if ( $item->get_quiz()->get_data( 'passing_grade' ) ) {
						$result += $item->is_passed() ? 1 : 0;
						$result_of_items ++;
					}
				}
				$result         = $result_of_items ? $result * 100 / $result_of_items : 0;
				$data['result'] = $result;
				if ( $this->is_finished() ) {
					$data['grade'] = $this->_is_passed( $result );
				}
			}

			settype( $cached_data, 'array' );
			$cached_data['completed-quizzes'] = $data;

			wp_cache_set( $cache_key, $cached_data, 'learn-press/course-results' );
		}

		return isset( $cached_data['completed-quizzes'] ) ? $cached_data['completed-quizzes'] : array();
	}

	protected function _is_passed( $result ) {
		$result = round( $result, 2 );

		return $result >= $this->get_passing_condition() ? 'passed' : 'failed';
	}

	/**
	 * Get completed items.
	 *
	 * @param string $type       - Optional. Filter by type (such lp_quiz, lp_lesson) if passed
	 * @param bool   $with_total - Optional. Include total if TRUE
	 * @param int    $section_id - Optional. Get in specific section
	 *
	 * @return array|bool|mixed
	 */
	public function get_completed_items( $type = '', $with_total = false, $section_id = 0 ) {
		$this->load();

		$key = sprintf( '%d-%d-%s', $this->get_user_id(), $this->_course->get_id(), md5( build_query( func_get_args() ) ) );

		if ( false === ( $completed_items = wp_cache_get( $key, 'learn-press/user-completed-items' ) ) ) {
			$completed     = 0;
			$total         = 0;
			$section_items = array();

			if ( $section_id && $section = $this->_course->get_sections( 'object', $section_id ) ) {
				$section_items = $section->get_items();

				if ( $section_items ) {
					$section_items = array_keys( $section_items );
				}
			}

			if ( $items = $this->_items ) {
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
						$completed = apply_filters( 'learn-press/course-item/completed', $completed, $item, $item->get_status() );
						//if ( ! $item->is_preview() ) {
						$total ++;
						//}
					}
				}
			}
			$completed_items = array( $completed, $total );
			wp_cache_set( $key, $completed_items, 'learn-press/user-completed-items' );
		}

		return $with_total ? $completed_items : $completed_items[0];
	}

	/**
	 * Get items completed by percentage.
	 *
	 * @param string $type       - Optional. Filter by type or not
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
	 * @return LP_User_Item[]
	 */
	public function get_items() {
		$this->load();

		return $this->_items;
	}

	/**
	 * Check course is completed or not.
	 *
	 * @return bool
	 */
	public function is_finished() {
		return $this->get_status() === 'finished';
	}

	/**
	 * Check course graduation is passed or not.
	 *
	 * @return bool
	 */
	public function is_graduated() {
		return $this->get_results( 'grade' ) == 'passed';
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

	/**
	 * @param int $item_id
	 *
	 * @return LP_User_Item|LP_User_Item_Quiz|bool
	 */
	public function get_item( $item_id ) {
		$this->load();

		return ! empty( $this->_items[ $item_id ] ) ? $this->_items[ $item_id ] : false;
	}

	/**
	 * @param int $user_item_id
	 *
	 * @return LP_User_Item|LP_User_Item_Quiz|bool
	 */
	public function get_item_by_user_item_id( $user_item_id ) {
		$this->load();

		if ( ! empty( $this->_items_by_item_ids[ $user_item_id ] ) ) {
			$item_id = $this->_items_by_item_ids[ $user_item_id ];

			return $this->get_item( $item_id );
		}

		return false;
	}

	public function set_item( $item ) {


		$this->load();

		if ( $item = LP_User_Item::get_item_object( $item ) ) {
			$this->_items[ $item->get_item_id() ] = $item;
		}
	}

	/**
	 * @param        $item_id
	 * @param string $prop
	 *
	 * @return bool|float|int
	 */
	public function get_item_result( $item_id, $prop = 'result' ) {
		if ( $item = $this->get_item( $item_id ) ) {
			return $item->get_result( $prop );
		}

		return false;
	}

	public function get_result( $prop = '' ) {
		return $this->get_results( $prop );
	}

	/**
	 * @param int $at
	 *
	 * @return LP_User_Item_Course
	 */
	public function get_item_at( $at = 0 ) {
		$this->load();
		$values = array_values( $this->_items );
		$item   = ! empty( $values[ $at ] ) ? $values[ $at ] : false;

		return $item;
	}

	/**
	 * @param $id
	 *
	 * @return LP_User_Item_Quiz|bool
	 */
	public function get_item_quiz( $id ) {

		$this->load();
		$item = ! empty( $this->_items[ $id ] ) ? $this->_items[ $id ] : new LP_User_Item_Quiz( array() );

		return $item;
	}

	/**
	 * Get js settings of course.
	 *
	 * @return array
	 */
	public function get_js_args() {
		$js_args = false;
		if ( $course = $this->get_course() ) {
			$item    = false;
			$js_args = array(
				'root_url'     => trailingslashit( get_home_url() ),
				'id'           => $course->get_id(),
				'url'          => $course->get_permalink(),
				'result'       => $this->get_results(),
				'current_item' => $item ? $item->get_id() : false,
				'items'        => $this->get_items_for_js()
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
	public function get_retaken_count() {
		return absint( learn_press_get_user_item_meta( $this->get_user_item_id(), '_lp_retaken_count', true ) );
	}

	/**
	 * Increase retaken count.
	 *
	 * @return bool|int
	 */
	public function increase_retake_count() {
		$count = $this->get_retaken_count();
		$count ++;

		return learn_press_update_user_item_meta( $this->get_user_item_id(), '_lp_retaken_count', $count );
	}

	/**
	 * Get js settings of course items.
	 *
	 * @return array
	 */
	public function get_items_for_js() {

		/*** TEST CACHE ***/
		return false;
		$args = array();
		if ( $items = $this->get_items() ) {
			$user   = $this->get_user();
			$course = $this->get_course();
			foreach ( $items as $item ) {

				$args[ $item->get_id() ] = $item->get_js_args();// $item_js;
			}
		}

		return apply_filters( 'learn-press/course/items-for-js', $args, $this->get_id(), $this->get_user_id() );
	}

	/**
	 * Add new item
	 *
	 * @param int|array $item_id
	 * @param int       $user_id
	 *
	 * @return bool
	 */
	public function add_item( $item_id, $user_id = 0 ) {
		$this->load();

		if ( empty( $this->_items[ $item_id ] ) ) {
			return false;
		}

		$item_data = is_numeric( $item_id ) ? array( 'item_id' => $item_id ) : (array) $item_id;

		if ( func_num_args() == 2 ) {
			$item_data['user_id'] = $user_id ? $user_id : get_current_user_id();
		}

		$current_time = new LP_Datetime();
		$defaults     = array(
			'start_time'     => $current_time,
			'start_time_gtm' => $current_time->toSql( false ),
			'end_time'       => $current_time,
			'end_time_gmt'   => $current_time->toSql( false ),
			'item_type'      => learn_press_get_post_type( $item_id ),
			'status'         => '',
			'ref_id'         => $this->get_id(),
			'ref_type'       => learn_press_get_post_type( $this->get_id() ),
			'parent_id'      => $this->get_user_item_id()
		);
		$item_data    = wp_parse_args(
			$item_data,
			$defaults
		);

		$this->_items[ $item_id ] = LP_User_Item::get_item_object( $item_data );

		return $this->_items[ $item_id ];
	}

	/**
	 * Update user item
	 */
	public function save() {
		$this->update();

		if ( ! $items = $this->get_items() ) {
			return false;
		}

		foreach ( $items as $item_id => $item ) {

			if ( ! $item->get_status() ) {
				continue;
			}

			$item->update();
		}

//global $wp_object_cache;
//
//		learn_press_debug($wp_object_cache);

		return true;
	}
}