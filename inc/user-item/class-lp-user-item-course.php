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
				$this->_items[ $item_id ] = apply_filters( 'learn-press/user-course-item', LP_User_Item::get_item_object( $data ), $data, $this );
			}
		}

		unset( $this->_data['items'] );

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

		$result = wp_cache_get( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), 'lp-user-course-results' );

		return $prop && array_key_exists( $prop, $result ) ? $result[ $prop ] : $result;
	}

	/**
	 * Evaluate course result by lessons.
	 *
	 * @return float|int
	 */
	protected function _evaluate_course_by_lesson() {
		if ( false === ( $data = wp_cache_get( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), 'lp-user-course-results' ) ) ) {
			$completing = $this->get_completed_items( LP_LESSON_CPT, true );
			if ( $completing[1] ) {
				$result = $completing[0] / $completing[1];
			} else {
				$result = 0;
			}
			$result *=100;
			$data = array(
				'result' => $result,
				'grade'  => $this->is_finished() ? ( $result >= $this->get_passing_condition() ? 'passed' : 'failed' ) : '',
				'status' => $this->get_status()
			);

			wp_cache_set( 'user-course-' . $this->get_user_id() . '-' . $this->get_id(), $data, 'lp-user-course-results' );
		}

		return $data['result'];
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
						$total ++;
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
		return $this->_course->get_data( 'passing_condition' );
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
	 * @return LP_User_Item_Course|bool
	 */
	public function get_item( $item_id ) {
		return ! empty( $this->_items[ $item_id ] ) ? $this->_items[ $item_id ] : false;
	}

	public function set_item( $item ) {
		if ( $item = LP_User_Item::get_item_object( $item ) ) {
			$this->_items[ $item->get_item_id() ] = $item;
		}
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
		return ! empty( $this->_items[ $id ] ) ? $this->_items[ $id ] : false;
	}

	public function get_course() {
		return learn_press_get_course( $this->get_id() );
	}

	public function get_js_args() {
		$js_args = false;
		if ( $course = $this->get_course() ) {
			$item    = false;
			$js_args = array(
				'root_url'     => trailingslashit( get_site_url() ),
				'id'           => $course->get_id(),
				'url'          => $course->get_permalink(),
				'result'       => $this->get_results(),
				'current_item' => $item ? $item->get_id() : false,
				'items'        => $this->get_items_for_js()
			);
		}

		return apply_filters( 'learn-press/course/single-params', $js_args, $this->get_course()->get_id() );
	}

	public function get_items_for_js() {
		$args = array();
		if ( $items = $this->get_items() ) {
			$user   = $this->get_user();
			$course = $this->get_course();
			foreach ( $items as $item ) {
				if ( ( $view = $user->can( 'view-item', $item->get_id(), $this->get_id() ) ) !== false ) {
					$item_js = array(
						'status' => $item->get_status(),
						'url'    => $course->get_item_link( $item->get_id() )
					);
				} else {
					$item_js = array(
						'status' => '',
						'url'    => ''
					);
				}

				$args[ $item->get_id() ] = $item_js;
			}
		}

		return apply_filters( 'learn-press/course/items-for-js', $args, $this->get_id(), $this->get_user_id() );
	}
}