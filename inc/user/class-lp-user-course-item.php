<?php

/**
 * Class LP_User_Course_Item
 */
class LP_User_Course_Item extends LP_User_Item implements ArrayAccess {
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

	/**
	 * LP_User_Course_Item constructor.
	 *
	 * @param null $item
	 */
	public function __construct( $item ) {
		parent::__construct( $item );
		$this->_item = $item;
		$this->read_items();
	}

	/**
	 * Read items's data of course for the user.
	 */
	public function read_items() {
		$this->_course = $course = learn_press_get_course( $this->get_id() );
		$this->_set_data( $this->_item );
		$course_items = $course->get_items();
		if ( $course_items ) {
			$default_data = array(
				'user_id'   => $this->get_user_id(),
				'item_id'   => 0,
				'item_type' => '',
				'ref_id'    => $this->get_id(),
				'ref_type'  => get_post_type( $this->get_id() ),
				'parent_id' => $this->get_data( 'parent_id' ),
				'status'    => ''
			);
			foreach ( $course_items as $item_id ) {
				$cache_name = sprintf( 'course-item-%s-%s-%s', $this->get_user_id(), $this->get_id(), $item_id );
				if ( false !== ( $data = learn_press_cache_get( $cache_name, 'lp-user-course-items' ) ) ) {
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
				$item = false;
				switch ( get_post_type( $item_id ) ) {
					case LP_LESSON_CPT:
						$item = new LP_User_Item( $data );
						break;
					case LP_QUIZ_CPT:
						$item = new LP_User_Item_Quiz( $data );
						break;
				}
				$this->_items[ $item_id ] = apply_filters( 'learn-press/user-course-item', $item, $data, $this );
			}
		}

		unset( $this->_data['items'] );

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

	/**
	 * Get result of course.
	 *
	 * @param string $prop
	 *
	 * @return float|int
	 */
	public function get_results( $prop = '' ) {
		$course_result = 'evaluate_lesson';//$this->get_data( 'course_result' );
		switch ( $course_result ) {
			// Completed lessons per total
			case 'evaluate_lesson':
				$this->_evaluate_course_by_lesson();
				break;
			// Results of final quiz
			case 'evaluate_final_quiz':
				break;
			// Points of quizzes per points of all quizzes
			case 'evaluate_quizzes':
				break;
			// Points of passed quizzes per points of all quizzes
			case 'evaluate_passed_quizzes':
				break;
			// Points of completed (may not passed) quizzes per points of all quizzes
			case 'evaluate_quiz':
		}

		$result = learn_press_cache_get( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), 'lp-user-course-results' );

		return $prop && array_key_exists( $prop, $result ) ? $result[ $prop ] : $result;
	}

	/**
	 * Evaluate course result by lessons.
	 *
	 * @return float|int
	 */
	protected function _evaluate_course_by_lesson() {
		if ( false === ( $data = learn_press_cache_get( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), 'lp-user-course-results' ) ) ) {
			$completing = $this->get_completed_items( LP_LESSON_CPT, true );
			if ( $completing[1] ) {
				$result = $completing[0] / $completing[1];
			} else {
				$result = 0;
			}
			$data = array(
				'result' => $result * 100,
				'grade'  => $this->is_finished() ? ( $this->can_graduated() ? 'passed' : 'failed' ) : '',
				'status' => $this->get_status()
			);

			learn_press_cache_set( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), $data, 'lp-user-course-results' );
		}

		return $data['result'];
	}

	public function get_completed_items( $type = '', $with_total = false, $section_id = 0 ) {
		$key = sprintf( '%d-%d-%s', $this->get_user_id(), $this->_course->get_id(), md5( build_query( func_get_args() ) ) );

		if ( false === ( $completed_items = learn_press_cache_get( $key, 'lp-user-completed-items' ) ) ) {
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
						$total ++;
					}
				}
			}
			$completed_items = array( $completed, $total );
			learn_press_cache_set( $key, $completed_items, 'lp-user-completed-items' );
		}

		return $with_total ? $completed_items : $completed_items[0];
	}

	public function get_percent_completed_items( $type = '', $section_id = 0 ) {
		$values = $this->get_completed_items( $type, true, $section_id );
		if ( $values[1] ) {
			return $values[0] / $values[1] * 100;
		}

		return 0;
	}

	public function get_passing_condition() {
		return $this->_course->get_data( 'passing_condition' );
	}

	public function get_items() {
		return $this->_items;
	}

	public function is_finished() {
		return $this->get_status() === 'finished';
	}

	public function is_graduated() {
		return $this->get_results( 'grade' ) == 'passed';
	}

	public function can_graduated() {
		return $this->get_results( 'result' ) >= $this->get_passing_condition();
	}

	function __destruct() {
		// TODO: Implement __unset() method.
	}

	/**
	 * @param int $item_id
	 *
	 * @return LP_User_Course_Item
	 */
	public function get_item( $item_id ) {
		return ! empty( $this->_items[ $item_id ] ) ? $this->_items[ $item_id ] : false;
	}

	/**
	 * @param int $at
	 *
	 * @return LP_User_Course_Item
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
	public function get_item_quiz($id){
		return ! empty( $this->_items[ $id ] ) ? $this->_items[ $id ] : false;
	}
}