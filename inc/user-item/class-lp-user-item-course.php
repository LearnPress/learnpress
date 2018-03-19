<?php

/**
 * Class LP_User_Item_Course
 */
class LP_User_Item_Course extends LP_User_Item implements ArrayAccess {
	/**
	 * Course's items
	 *
	 * @var array
	 */
	protected $_items = array();

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

	protected $_item = null;

	protected static $_loaded = 0;

	protected $_items_by_item_ids = array();

	/**
	 * LP_User_Item_Course constructor.
	 *
	 * @param null $item
	 */
	public function __construct( $item ) {
		parent::__construct( $item );
		$this->_item = $item;
		$this->read_items();
		$this->read_items_meta();

		self::$_loaded ++;
		if ( self::$_loaded == 1 ) {
			add_filter( 'debug_data', array( __CLASS__, 'log' ) );
		}
	}

	public static function log( $data ) {
		$data[] = __CLASS__ . '( ' . self::$_loaded . ' )';

		return $data;
	}

	/**
	 * Read items's data of course for the user.
	 */
	public function read_items() {
		$user_curd = new LP_User_CURD();
		$user_curd->read_course( $this->get_user_id(), $this->get_id() );

		$this->_set_data( $this->_item );
		if ( $course = learn_press_get_course( $this->get_id() ) ) {
			$this->_course = $course;
			$course_items  = $course->get_items();
			if ( $course_items ) {
				$default_data = array(
					'user_id'   => $this->get_user_id(),
					'item_id'   => 0,
					'item_type' => '',
					'ref_id'    => $course->get_id(),
					'ref_type'  => get_post_type( $this->get_id() ),
					'parent_id' => $this->get_data( 'parent_id' ),
					'status'    => ''
				);
				foreach ( $course_items as $item_id ) {

					$cache_name = sprintf( 'course-item-%s-%s-%s', $this->get_user_id(), $this->get_id(), $item_id );
					if ( false !== ( $data = wp_cache_get( $cache_name, 'lp-user-course-items' ) ) ) {
						$data = reset( $data );
					} else {
						$data = wp_parse_args(
							array(
								'item_id'   => $item_id,
								'item_type' => get_post_type( $item_id )
							),
							$default_data
						);
					}
					if ( $course_item = apply_filters( 'learn-press/user-course-item', LP_User_Item::get_item_object( $data ), $data, $this ) ) {
						$this->_items[ $item_id ]                                     = $course_item;
						$this->_items_by_item_ids[ $course_item->get_user_item_id() ] = $item_id;
					}
				}
			}
		}
		unset( $this->_data['items'] );

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
					$result->meta_value = maybe_unserialize( $result->meta_value );

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
		return $this->offsetExists( $offset ) ? $this->_items[ $offset ] : false;
	}

	public function offsetExists( $offset ) {
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
					'status'          => $this->get_status()
				)
			);

			$results['grade'] = $this->is_finished() ? $this->_is_passed( $results['result'] ) : 'in-progress';
		}

		if ( $prop === 'status' ) {
			if ( $results['grade'] ) {
				$prop = 'grade';
			}
		}

		///$result = wp_cache_get( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), 'lp-user-course-results' );

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
		if ( false === ( $data = wp_cache_get( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), 'lp-user-course-results/evaluate-by-lesson' ) ) ) {
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

			wp_cache_set( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), $data, 'lp-user-course-results/evaluate-by-lesson' );
		}

		return $data;
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

		if ( false === ( $data = wp_cache_get( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), 'lp-user-course-results/evaluate-by-final-quiz' ) ) ) {
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

			wp_cache_set( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), $data, 'lp-user-course-results/evaluate-by-final-quiz' );
		}

		return $data;
	}

	/**
	 * Evaluate course result by point of quizzes doing/done per total quizzes.
	 *
	 * @return array
	 */
	protected function _evaluate_course_by_quizzes() {

		if ( false === ( $data = wp_cache_get( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), 'lp-user-course-results/evaluate-by-quizzes' ) ) ) {

			$data   = array( 'result' => 0, 'grade' => '', 'status' => $this->get_status() );
			$result = 0;

			if ( $items = $this->get_items() ) {
				foreach ( $items as $item ) {
					if ( $item->get_type() !== LP_QUIZ_CPT ) {
						continue;
					}

					$result += $item->get_results( 'result' );
				}
				$data['result'] = $result;

				if ( $this->is_finished() ) {
					$data['grade'] = $this->_is_passed( $result );
				}
			}

			wp_cache_set( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), $data, 'lp-user-course-results/lp-user-course-results/evaluate-by-quizzes' );
		}

		return $data;
	}

	/**
	 * Evaluate course result by point of quizzes doing/done per total quizzes.
	 *
	 * @return array
	 */
	protected function _evaluate_course_by_passed_quizzes() {

		if ( false === ( $data = wp_cache_get( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), 'lp-user-course-results/evaluate-by-passed-quizzes' ) ) ) {

			$data   = array( 'result' => 0, 'grade' => '', 'status' => $this->get_status() );
			$result = 0;

			if ( $items = $this->get_items() ) {
				foreach ( $items as $item ) {
					if ( $item->get_type() !== LP_QUIZ_CPT ) {
						continue;
					}

					$result += $item->is_passed() ? $item->get_results( 'result' ) : 0;
				}
				$data['result'] = $result;

				if ( $this->is_finished() ) {
					$data['grade'] = $this->_is_passed( $result );
				}
			}

			wp_cache_set( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), $data, 'lp-user-course-results/evaluate-by-passed-quizzes' );
		}

		return $data;
	}

	/**
	 * Evaluate course result by point of quizzes doing/done per total quizzes.
	 *
	 * @return array
	 */
	protected function _evaluate_course_by_completed_quizzes() {

		if ( false === ( $data = wp_cache_get( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), 'lp-user-course-results/evaluate-by-completed-quizzes' ) ) ) {
			$course = $this->get_course();

			$data   = array( 'result' => 0, 'grade' => '', 'status' => $this->get_status() );
			$result = 0;

			if ( $items = $this->get_items() ) {
				foreach ( $items as $item ) {
					if ( $item->get_type() !== LP_QUIZ_CPT ) {
						continue;
					}

					$result += $item->is_completed() ? $item->get_results( 'result' ) : 0;
				}
				$data['result'] = $result;

				if ( $this->is_finished() ) {
					$data['grade'] = $this->_is_passed( $result );
				}
			}

			wp_cache_set( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), $data, 'lp-user-course-results/evaluate-by-completed-quizzes' );
		}

		return $data;
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
		$key = sprintf( '%d-%d-%s', $this->get_user_id(), $this->_course->get_id(), md5( build_query( func_get_args() ) ) );

		if ( false === ( $completed_items = wp_cache_get( $key, 'lp-user-completed-items' ) ) ) {
			$completed     = 0;
			$total         = 0;
			$section_items = array();

			if ( $section_id && $section = $this->_course->get_curriculum( $section_id ) ) {
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

						//if ( ! $item->is_preview() ) {
						$total ++;
						//}
					}
				}
			}
			$completed_items = array( $completed, $total );
			wp_cache_set( $key, $completed_items, 'lp-user-completed-items' );
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
	 * @return array
	 */
	public function get_items() {
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
		return ! empty( $this->_items[ $item_id ] ) ? $this->_items[ $item_id ] : false;
	}

	/**
	 * @param int $user_item_id
	 *
	 * @return LP_User_Item|LP_User_Item_Quiz|bool
	 */
	public function get_item_by_user_item_id( $user_item_id ) {
		if ( ! empty( $this->_items_by_item_ids[ $user_item_id ] ) ) {
			$item_id = $this->_items_by_item_ids[ $user_item_id ];

			return $this->get_item( $item_id );
		}

		return false;
	}

	public function set_item( $item ) {
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
}