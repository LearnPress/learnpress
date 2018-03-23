<?php

/**
 * Class LP_User_Item
 * @since 3.0.0
 */
class LP_User_Item extends LP_Abstract_Object_Data {
	/**
	 * @var int
	 */
	protected static $_loaded = 0;

	/**
	 * @var bool
	 */
	protected $_is_available = null;

	/**
	 * LP_User_Item constructor.
	 *
	 * @param array $item . A record fetched from table _learnpress_user_items
	 */
	public function __construct( $item ) {
		settype( $item, 'array' );
		parent::__construct( $item );
		if ( ! empty( $item['item_id'] ) ) {
			$this->set_id( $item['item_id'] );
		}

		if ( ! empty( $item['start_time'] ) ) {
			$this->set_start_time( $item['start_time'] );
		}

		if ( ! empty( $item['end_time'] ) ) {
			$this->set_end_time( $item['end_time'] );
		}
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
	 * Get type of item. Consider is post-type.
	 *
	 * @return array|mixed
	 */
	public function get_type() {
		return $this->get_data( 'item_type' );
	}

	/**
	 * Set start-time.
	 *
	 * @param mixed $time
	 */
	public function set_start_time( $time ) {
		$this->set_data_date( 'start_time', $time );
	}

	/**
	 * Get start-time.
	 *
	 * @param string $format
	 *
	 * @return string|LP_Datetime
	 */
	public function get_start_time( $format = '' ) {
		$time = $this->get_data( 'start_time' );

		$date = new LP_Datetime( $time );

		if ( $format ) {
			return $format = 'i18n' ? learn_press_date_i18n( $date->getTimestamp() ) : $date->format( $format );
		}

		return $date;
	}

	public function set_start_time_gmt( $time ) {
		$this->set_data_date( 'start_time_gmt', $time );
	}

	public function get_start_time_gmt( $format = '' ) {
		$date = new LP_Datetime( $this->get_data( 'start_time_gmt' ) );
		if ( $format ) {
			return $format = 'i18n' ? learn_press_date_i18n( $date->getTimestamp() ) : $date->format( $format );
		}

		return $date;
	}

	/**
	 * Get end-time.
	 *
	 * @param mixed $time
	 */
	public function set_end_time( $time ) {
		$this->set_data_date( 'end_time', $time );
	}

	/**
	 * Get end-time.
	 *
	 * @param string $format
	 *
	 * @return string|LP_Datetime
	 */
	public function get_end_time( $format = '' ) {
		$date = new LP_Datetime( $this->get_data( 'end_time' ) );
		if ( $format ) {
			return $format = 'i18n' ? learn_press_date_i18n( $date->getTimestamp() ) : $date->format( $format );
		}

		return $date;
	}

	/**
	 * Get end-time.
	 *
	 * @param mixed $time
	 */
	public function set_end_time_gmt( $time ) {
		$this->set_data_date( 'end_time_gmt', $time );
	}

	/**
	 * Get end-time.
	 *
	 * @param string $format
	 *
	 * @return string|LP_Datetime
	 */
	public function get_end_time_gmt( $format = '' ) {
		$date = new LP_Datetime( $this->get_data( 'end_time_gmt' ) );
		if ( $format ) {
			return $format = 'i18n' ? learn_press_date_i18n( $date->getTimestamp() ) : $date->format( $format );
		}

		return $date;
	}

	/**
	 * Set item-status.
	 *
	 * @param string $status
	 */
	public function set_status( $status ) {
		$this->_set_data( 'status', $status );
	}

	/**
	 * Get item-status.
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->get_data( 'status' );
	}

	public function is_available() {
		if ( null === $this->_is_available ) {
			$user                = $this->get_user();
			$order               = $user->get_course_order( $this->get_item_id() );
			$this->_is_available = $order && $order->get_status() === 'completed';
		}

		return $this->_is_available;
	}

	/**
	 * Get user-id.
	 *
	 * @return int
	 */
	public function get_user_id() {
		return $this->get_user( 'id' );
	}

	/**
	 * @param string $return
	 *
	 * @return LP_User|int
	 */
	public function get_user( $return = '' ) {
		$uid = $this->get_data( 'user_id' );
		if ( $return == '' ) {
			return $uid ? learn_press_get_user( $uid ) : new LP_User();
		}

		return $uid;
	}

	public function get_course( $return = '' ) {
		$cid = $this->get_data( 'ref_id' );
		if ( $return == '' ) {
			return $cid ? learn_press_get_course( $cid ) : false;
		}

		return $cid;
	}

	/**
	 * @return int
	 */
	public function get_user_item_id() {
		return $this->get_data( 'user_item_id' );
	}

	public function get_item_id() {
		return $this->get_data( 'item_id' );
	}

	public function get_parent() {
		return intval( $this->get_data( 'parent_id' ) );
	}

	public function get_result( $prop = 'result' ) {
		$result = array(
			'result' => $this->is_completed() ? 100 : 0,
			'grade'  => $this->is_completed() ? 'passed' : 'failed'
		);

		return $prop && array_key_exists( $prop, $result ) ? $result[ $prop ] : $result;
	}

	public function read_meta() {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id = %d
		", $this->get_user_item_id() );

		if ( $results = $wpdb->get_results( $query ) ) {
			foreach ( $results as $result ) {
				$result->meta_value = maybe_unserialize( $result->meta_value );
				$this->_meta_data[] = $result;
			}
		}

	}

	public function get_meta( $key, $single = true ) {
		return learn_press_get_user_item_meta( $this->get_user_item_id(), $key, $single );
	}

//	public function set_meta_data( $key, $value ) {
//		if ( empty( $this->_meta_data ) ) {
//			$this->_meta_data = array();
//		}
//		$set = false;
//		if ( $this->_meta_data ) {
//			foreach ( $this->_meta_data as $k => $v ) {
//				if ( $k === $v->meta_key ) {
//					$this->_meta_data[ $k ]->meta_value = $value;
//					$set                                = true;
//					break;
//				}
//			}
//		}
//
//		if ( ! $set ) {
//			$this->_meta_data[] = (object) array(
//				'meta_key'   => $key,
//				'meta_value' => $value
//			);
//		}
//
//	}

	public function update_meta() {
		if ( $this->_meta_data ) {
			foreach ( $this->_meta_data as $meta_data ) {
				if ( $meta_data->meta_value ) {
					learn_press_update_user_item_meta( $this->get_user_item_id(), $meta_data->meta_key, $meta_data->meta_value );
				} else {
					learn_press_delete_user_item_meta( $this->get_user_item_id(), $meta_data->meta_key );
				}
			}
		}
	}

	public function get_mysql_data() {
		$columns = array();
		foreach ( $this->_data as $k => $v ) {
			switch ( $k ) {
				case 'start_time':
				case 'end_time':
					$v = is_a( $v, 'LP_Datetime' ) ? $v->toSql() : $v;
					break;
				case 'start_time_gmt':
					if ( ! $this->_data['start_time_gmt'] ) {
						$v = new LP_Datetime( $this->_data['start_time'] );
					}

					$v = is_a( $v, 'LP_Datetime' ) ? $v->toSql() : $v;
					break;
				case 'end_time_gmt':
					if ( ! $this->_data['end_time_gmt'] ) {
						$v = new LP_Datetime( $this->_data['end_time'] );
					}

					$v = is_a( $v, 'LP_Datetime' ) ? $v->toSql() : $v;
					break;
			}
			$columns[ $k ] = $v;
		}

		return $columns;
	}

	/**
	 * @param $data
	 *
	 * @return LP_User_Item|bool
	 */
	public static function get_item_object( $data ) {
		if ( isset( $data['item_id'] ) ) {
			$item_id = $data['item_id'];
		} elseif ( isset( $data->item_id ) ) {
			$item_id = $data->item_id;
		} else {
			return false;
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

		return apply_filters( 'learn-press/user-item-object', $item, $data );
	}

	public function update() {
		$data = $this->get_mysql_data();

		return learn_press_update_user_item_field( $data );
	}

	public function get_status_label( $status = '' ) {
		$statuses = array(
			'enrolled'    => __( 'In Progress', 'learnpress' ),
			'started'     => __( 'In Progress', 'learnpress' ),
			'in-progress' => __( 'In Progress', 'learnpress' ),
			'completed'   => __( 'Completed', 'learnpress' ),
			'finished'    => __( 'Finished', 'learnpress' ),
			'passed'      => __( 'Passed', 'learnpress' ),
			'failed'      => __( 'Failed', 'learnpress' )
		);

		if ( ! $status ) {
			$status = $this->get_status();
		}

		return ! empty( $statuses[ $status ] ) ? $statuses[ $status ] : __( 'Not enrolled', 'learnpress' );
	}

	/**
	 * Get time from user started to ended.
	 *
	 * @param string $context
	 *
	 * @return bool|float|int
	 */
	public function get_time_interval( $context = '' ) {
		$start = $this->get_start_time();
		$end   = $this->get_end_time();
		if ( $start->is_null() || $end->is_null() ) {
			return false;
		}
		$interval = $end->getTimestamp() - $start->getTimestamp();

		return $interval;
	}

	public function current_user_can_view() {
		$user = $this->get_user();
		if ( false !== ( $view = $user->can_view_item( $this->get_id(), $this->get_course( 'id' ) ) ) ) {
			return $view;
		}

		return false;
	}

	public function get_history() {
		return wp_cache_get( sprintf( 'course-item-%s-%s-%s', $this->get_user_id(), $this->get_course( 'id' ), $this->get_id() ), 'lp-user-course-items' );
	}

	public function count_history() {
		if ( $items = $this->get_history() ) {
			return sizeof( $items );
		}

		return 0;
	}

	/**
	 * Return number of seconds has exceeded.
	 * If less than or equals to 0 that means the time is exceeded.
	 * Otherwise, the time is not exceeded
	 *
	 * @return float|int
	 */
	public function is_exceeded() {
		$time     = new LP_Datetime();
		$current  = $time->getTimestamp();
		$exceeded = $this->get_exceeded_time();

		return false !== $exceeded ? $exceeded - $current : false;
	}

	/**
	 * @param string $format
	 *
	 * @return int|mixed
	 */
	public function get_exceeded_time( $format = '' ) {
		$start_time = $this->get_start_time()->getTimestamp();

		if ( $course = $this->get_course() ) {
			$duration = $course->get_duration();
		} else {
			$duration = 100 * DAY_IN_SECONDS * 360;
		}

		return $duration !== false ? $format ? date( $format, $start_time + $duration ) : $start_time + $duration : false;
	}

	/**
	 * Return true of item is completed/finished
	 *
	 * @param string $status
	 *
	 * @return bool
	 */
	public function is_completed( $status = 'completed' ) {
		return $this->get_status() === $status;
	}

	public function complete( $status = 'completed' ) {

		global $wpdb;

		$end_time  = new LP_Datetime();
		$null_time = '0000-00-00 00:00';

		$this->set_end_time( $end_time->toSql() );
		$this->set_end_time_gmt( $end_time->toSql( false ) );
		$this->set_status( $status );
		$this->update();

		$query = $wpdb->prepare( "
				SELECT user_item_id
				FROM {$wpdb->prefix}learnpress_user_items
				WHERE user_id = %d
					AND item_id = %d
					AND start_time <> %s AND end_time <> %s
					AND status = %s
			", $this->get_user_id(), $this->get_item_id(), $null_time, $null_time, $status );

		if ( $return = $wpdb->get_var( $query ) ) {
			$this->maybe_update_item_grade();
		}

		return $return;
	}

	public function maybe_update_item_grade() {
		$result = $this->get_result();
		learn_press_update_user_item_meta( $this->get_user_item_id(), 'grade', $result['grade'] );
	}

	public function delete_meta_data( $include = '', $exclude = '' ) {
		global $wpdb;

		$where = '';
		if ( $include ) {
			settype( $include, 'array' );
			$format = array_fill( 0, sizeof( $include ), '%s' );
			$where  .= $wpdb->prepare( " AND meta_key IN(" . join( ',', $format ) . ")", $include );
		}

		if ( $exclude ) {
			settype( $exclude, 'array' );
			$format = array_fill( 0, sizeof( $exclude ), '%s' );
			$where  .= $wpdb->prepare( " AND meta_key IN(" . join( ',', $format ) . ")", $exclude );
		}

		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id = %d
			{$where}
		", $this->get_user_item_id() );

		$wpdb->query( $query );

		$this->_meta_data = array();
		update_meta_cache( 'learnpress_user_item', $this->get_user_item_id() );
	}

	/**
	 * Get post type of item.
	 *
	 * @return string
	 */
	public function get_post_type() {
		return get_post_type( $this->get_item_id() );
	}

	public function is_passed() {
		return $this->get_result( 'grade' ) === 'passed';
	}

	public function get_percent_result( $decimal = 1 ) {
		return apply_filters( 'learn-press/user/item-percent-result', sprintf( '%s%%', round( $this->get_result( 'result' ), $decimal ), $this->get_user_id(), $this->get_item_id() ) );
	}

	public function get_js_args() {
		$course = $this->get_course();
//		$args = array(
//			'url'=>$course->get_item_link( $item->get_id() )
//		)
		$item_js = array(
			'status'   => '',
			'url'      => $course->get_item_link( $this->get_id() ),
			'viewable' => ''
		);

		if ( ( $view = $this->current_user_can_view() ) !== false ) {
			$item_js['status']   = $this->get_status();
			$item_js['viewable'] = $view;
		}

		return $item_js;
	}

	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		if ( ! method_exists( $this, $name ) ) {
			if ( $course = $this->get_course() ) {
				$item = $course->get_item( $this->get_item_id() );
				if ( is_callable( array( $item, $name ) ) ) {
					return call_user_func_array( array( $item, $name ), $arguments );
				}
			}
		}

		return false;
	}
}