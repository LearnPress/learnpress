<?php

/**
 * Class LP_User_Item
 *
 * @since 3.0.0
 */
class LP_User_Item extends LP_Abstract_Object_Data implements ArrayAccess {
	/**
	 * @var bool
	 */
	protected $_is_available = null;

	/**
	 * @var string
	 */
	protected $_data_key = '';

	/**
	 * LP_User_Item constructor.
	 *
	 * @param array $item . A record fetched from table _learnpress_user_items
	 */
	public function __construct( $item ) {
		if ( is_numeric( $item ) ) {
			$item = array( 'item_id' => $item );
		} else {
			$item = (array) $item;
		}

		// $this->_data = self::get_empty_item();

		parent::__construct( $item );
		$this->set_default_data( $item );

	}

	/**
	 * Set data from passed args
	 *
	 * @param array $item
	 */
	protected function set_default_data( $item ) {
		$this->_changes = array();
		$item_id        = 0;

		if ( ! empty( $item['user_item_id'] ) ) {
			$this->set_data( 'user_item_id', absint( $item['user_item_id'] ) );
		}

		if ( ! empty( $item['item_id'] ) ) {
			$item['item_id'] = absint( $item['item_id'] );

			$this->set_id( $item['item_id'] );
			$this->set_data( 'item_id', $item['item_id'] );
			$this->set_data( 'item_type', learn_press_get_post_type( $item['item_id'] ) );
			$item_id = $item['item_id'];
		}

		if ( ! empty( $item['start_time'] ) ) {
			$this->set_start_time( $item['start_time'] );
		} else {
			$this->set_start_time( learn_press_mysql_time( true ) );
		}

		if ( ! empty( $item['end_time'] ) ) {
			$this->set_end_time( $item['end_time'] );
		} else {
			$this->set_end_time( null );
		}

		if ( ! empty( $item['user_id'] ) ) {
			$item['user_id'] = absint( $item['user_id'] );

			$this->set_user_id( $item['user_id'] );
		} else {
			$this->set_user_id( get_current_user_id() );
		}

		if ( ! empty( $item['status'] ) ) {
			$this->set_status( $item['status'] );
		} else {
			$status = $this->get_user_item_id() ? learn_press_default_user_item_status( $item_id ) : '';
			$this->set_status( $status );
		}

		if ( ! empty( $item['ref_id'] ) ) {
			$item['ref_id'] = absint( $item['ref_id'] );

			$this->set_ref_id( $item['ref_id'] );
			if ( empty( $item['ref_type'] ) ) {
				$this->set_data( 'ref_type', learn_press_get_post_type( $item['ref_id'] ) );
			}
		}

		if ( ! empty( $item['ref_type'] ) ) {
			$this->set_data( 'ref_type', $item['ref_type'] );
		}

		if ( ! empty( $item['parent_id'] ) ) {
			$item['parent_id'] = absint( $item['parent_id'] );

			$this->set_parent_id( $item['parent_id'] );
		}

		if ( ! empty( $item['access_level'] ) ) {
			$this->set_data( 'access_level', $item['access_level'] );
		}

		if ( ! empty( $item['graduation'] ) ) {
			$this->set_data( 'graduation', $item['graduation'] );
		}

		$new_data = $this->get_mysql_data();
		ksort( $new_data );
		$this->_data_key = md5( serialize( $new_data ) );
	}

	public function set_user_id( $user_id ) {
		$this->set_data( 'user_id', $user_id );
	}

	public function get_user_id() {
		return $this->get_data( 'user_id' );
	}

	public function set_ref_id( $ref_id ) {
		$this->set_data( 'ref_id', $ref_id );
		$this->set_data( 'ref_type', learn_press_get_post_type( $ref_id ) );
	}

	public function get_parent_id() {
		return absint( $this->get_data( 'parent_id' ) );
	}

	public function set_parent_id( $parent_id ) {
		$this->set_data( 'parent_id', $parent_id );
	}

	/**
	 * Get access level to a course.
	 *
	 * @return int
	 * @since 3.x.x
	 */
	public function get_access_level() {
		return absint( $this->get_data( 'access_level' ) );
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
	 * @param mixed $time .
	 *
	 * @return $this
	 */
	public function set_start_time( $time ) {
		$this->_set_data_date( 'start_time', $time );

		return $this;
	}

	public function get_time( $field, $format = '', $human_diff_time = false ) {
		if ( ! $format ) {
			$format = get_option( 'date_format' );
		}

		$m_time    = call_user_func( array( $this, 'get_' . $field ) );
		$time      = mysql2date( 'G', call_user_func( array( $this, 'get_' . $field . '_gmt' ) ) );
		$time_diff = time() - $time;

		if ( $human_diff_time && $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			$h_time = sprintf( __( '%s ago', 'learnpress' ), human_time_diff( $time ) );
		} else {
			$h_time = mysql2date( $format, $m_time );
		}

		return $h_time;
	}

	/**
	 * Get start-time.
	 *
	 * @param string $format
	 * @param bool   $local
	 *
	 * @return string|LP_Datetime
	 */
	public function get_start_time( $format = '', $local = false ) {
		$date = $this->get_data( 'start_time' );

		return $this->format_time( $date, $format, $local );
	}

	/**
	 * @param $time
	 *
	 * @deprecated 4.0.0
	 */
	public function set_start_time_gmt( $time ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '4.0.0' );
	}

	/**
	 * @param string $format
	 *
	 * @return array|bool|LP_Datetime|mixed|string
	 * @deprecated
	 */
	public function get_start_time_gmt( $format = '' ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '4.0.0' );

		// $date = $this->get_data_date( 'start_time_gmt' );
		// if ( $format ) {
		// return $date->is_null() ? false : ( $format = 'i18n' ? learn_press_date_i18n( $date->getTimestamp() ) : $date->format( $format ) );
		// }
		//
		// return $date;
	}

	/**
	 * Set end-time for item.
	 *
	 * @param bool  $bound_to_gmt - Optional. Calculate gmt of end-time and update
	 * @param mixed $time
	 *
	 * @return $this
	 */
	public function set_end_time( $time ) {
		if ( $time && '0000-00-00 00:00:00' !== $time ) {
			$this->_set_data_date( 'end_time', $time );
		} else {
			$this->_set_data( 'end_time', '' );
		}

		return $this;
	}

	/**
	 * Get end-time.
	 *
	 * @param string $format
	 *
	 * @return string|LP_Datetime
	 */
	public function get_end_time( $format = '' ) {
		$date = $this->get_data( 'end_time' );

		return $this->format_time( $date, $format );
	}

	public function get_start_time_local() {

	}

	/**
	 * @param string|int|LP_Datetime $date
	 * @param string                 $format
	 * @param bool                   $local
	 *
	 * @return bool|float|int|LP_Datetime|string
	 */
	public function format_time( $date, $format = '', $local = false ) {
		if ( ! $date ) {
			return false;
		}

		if ( ! $date instanceof LP_Datetime ) {
			$date = new LP_Datetime( $date );
		}

		return $format ? $date->format( $format, $local ) : $date;
	}

	/**
	 * Get end-time.
	 *
	 * @param mixed $time
	 *
	 * @deprecated 4.0.0
	 */
	public function set_end_time_gmt( $time ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '4.0.0' );
	}

	/**
	 * Get end-time.
	 *
	 * @param string $format
	 *
	 * @return string|LP_Datetime
	 * @deprecated
	 */
	public function get_end_time_gmt( $format = '' ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '4.0.0' );
	}

	/**
	 * Get expiration time.
	 *
	 * @param string $format
	 *
	 * @return string|LP_Datetime $time
	 * @since 3.3.0
	 */
	public function get_expiration_time( $format = '' ) {
		$duration   = get_post_meta( $this->get_item_id(), '_lp_duration', true );
		$start_time = $this->get_start_time( '', false );

		if ( ! absint( $duration ) || ! $start_time ) {
			return null;
		}

		$date = new LP_Datetime( $start_time->getPeriod( $duration, true ) );

		return $this->format_time( $date, $format );
	}

	/**
	 * Set item-status.
	 *
	 * @param string $status .
	 *
	 * @return $this
	 */
	public function set_status( string $status ): LP_User_Item {
		$this->_set_data( 'status', $status );

		return $this;
	}

	/**
	 * Get item-status.
	 *
	 * @return string
	 */
	public function get_status( $field = 'status' ) {
		$got_status = $this->get_data( $field, false );
		$user_id    = $this->get_extra_data( 'user_id' );

		if ( false === $got_status && false !== $user_id ) {
			$user_item = learn_press_get_user_item(
				array(
					'user_id'   => $user_id,
					'item_id'   => $this->get_item_id(),
					'parent_id' => $this->get_parent_id(),
					'ref_id'    => $this->get_data( 'ref_id' ),
				)
			);

			if ( ! empty( $user_item ) ) {
				$got_status = $user_item->$field;
			} else {
				$got_status = '';
			}

			$this->set_data( $field, $got_status );
		}

		return $got_status;
	}

	/**
	 * Check course is finished.
	 *
	 * @return bool.
	 */
	public function has_finished() : bool {
		return $this->get_status() === LP_COURSE_FINISHED;
	}

	public function is_exists() {
		return ! ! $this->get_user_item_id();
	}

	public function is_available() {
		if ( null === $this->_is_available ) {
			$user                = $this->get_user();
			$order               = $user->get_course_order( $this->get_item_id() );
			$this->_is_available = $order && ( $order->get_status() === 'completed' ) && $this->is_exists();
		}

		return $this->_is_available;
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

	/**
	 * Change the primary key user_item_id of user-items.
	 * Only use zero value to force creating new item.
	 *
	 * @param int $user_item_id
	 */
	public function set_user_item_id( $user_item_id ) {
		$this->_set_data( 'user_item_id', absint( $user_item_id ) );
	}

	public function get_item_id() {
		return $this->get_data( 'item_id' );
	}

	public function get_parent() {
		$user   = learn_press_get_user( $this->get_user_id() );
		$ref_id = $this->get_data( 'ref_id' );

		if ( get_post_type( $ref_id ) === LP_COURSE_CPT ) {
			return $user->get_course_data( $ref_id );
		}

		return false;
	}

	public function get_result( $prop = 'result' ) {
		$result = array(
			'result' => $this->is_completed() ? 100 : 0,
			'grade'  => $this->is_completed() ? 'passed' : 'failed',
		);

		return $prop && array_key_exists( $prop, $result ) ? $result[ $prop ] : $result;
	}

	public function read_meta() {
		global $wpdb;
		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id = %d
		",
			$this->get_user_item_id()
		);

		if ( $results = $wpdb->get_results( $query ) ) {
			foreach ( $results as $result ) {
				$result->meta_value = LP_Helper::maybe_unserialize( $result->meta_value );
				$this->_meta_data[] = $result;
			}
		}

	}

	/**
	 * Get structure of an user item.
	 *
	 * @return array
	 * @since 3.1.0
	 */
	public static function get_empty_item() {
		return array(
			'user_item_id' => 0,
			'user_id'      => 0,
			'item_id'      => 0,
			'start_time'   => '',
			'end_time'     => '',
			'item_type'    => '',
			'status'       => '',
			'graduation'   => '',
			'access_level' => 50,
			'ref_id'       => '',
			'ref_type'     => '',
			'parent_id'    => 0,
		);
	}

	/**
	 * Get user-item meta data.
	 * Check if meta data does not exist then return FALSE.
	 *
	 * @updated 3.1.0
	 *
	 * @param string $key
	 * @param bool   $single
	 *
	 * @return bool|mixed
	 */
	public function get_meta( $key, $single = true ) {
		if ( ! metadata_exists( 'learnpress_user_item', $this->get_user_item_id(), $key ) ) {
			return false;
		}

		return learn_press_get_user_item_meta( $this->get_user_item_id(), $key, $single );
	}

	/**
	 * Update meta data
	 *
	 * @updated 3.1.0
	 *
	 * @param string $key
	 * @param string $value
	 * @param string $prev_value
	 *
	 * @editor tungnx add bk method
	 */
	public function update_meta_bk( $key = '', $value = '', $prev_value = '' ) {
		if ( func_num_args() === 0 ) {
			if ( $this->_meta_data ) {
				foreach ( $this->_meta_data as $meta_data ) {
					if ( $meta_data->meta_value ) {
						learn_press_update_user_item_meta(
							$this->get_user_item_id(),
							$meta_data->meta_key,
							$meta_data->meta_value
						);
					} else {
						learn_press_delete_user_item_meta( $this->get_user_item_id(), $meta_data->meta_key );
					}
				}
			}
		} else {
			if ( is_array( $key ) ) {
				foreach ( $key as $k => $v ) {
					if ( $v === false ) {
						learn_press_delete_user_item_meta( $this->get_user_item_id(), $k );
					} else {
						learn_press_update_user_item_meta( $this->get_user_item_id(), $k, $v );
					}
				}
			} else {
				if ( $value === false ) {
					learn_press_delete_user_item_meta( $this->get_user_item_id(), $key );
				} else {
					learn_press_update_user_item_meta( $this->get_user_item_id(), $key, $value, $prev_value );
				}
			}
		}
	}

	/**
	 * Update meta data
	 *
	 * @param string $key .
	 * @param string $value .
	 * @param string $prev_value .
	 *
	 * @return int|bool
	 * @editor tungnx
	 */
	public function update_meta( $key = '', $value = '', $prev_value = '' ) {
		$result = false;

		if ( is_array( $key ) ) {
			$result = $this->update_meta_multiple_keys( $key );
		} else {
			if ( false === $value ) {
				$result = learn_press_delete_user_item_meta( $this->get_user_item_id(), $key );
			} else {
				$result = learn_press_update_user_item_meta( $this->get_user_item_id(), $key, $value, $prev_value );
			}
		}

		return $result;
	}

	/**
	 * Update metadata with array keys values
	 *
	 * @param array $meta_datas .
	 *
	 * @return bool|int
	 */
	public function update_meta_multiple_keys( $meta_datas = array() ) {
		$result = false;

		foreach ( $meta_datas as $k => $v ) {
			if ( false === $v ) {
				$result = learn_press_delete_user_item_meta( $this->get_user_item_id(), $k );
			} else {
				$result = learn_press_update_user_item_meta( $this->get_user_item_id(), $k, $v );
			}
		}

		return $result;
	}

	public function get_mysql_data() {
		/**
		 * @var LP_Datetime $v
		 */
		$columns = array();

		foreach ( $this->get_data() as $k => $v ) {
			switch ( $k ) {
				case 'start_time':
				case 'end_time':
					$v = is_a( $v, 'LP_Datetime' ) ? $v->toSql( false ) : $v;
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
		$item_id = 0;

		if ( is_array( $data ) && isset( $data['item_id'] ) ) {
			$item_id = $data['item_id'];
		} elseif ( is_object( $data ) && isset( $data->item_id ) ) {
			$item_id = $data->item_id;
		} elseif ( is_numeric( $data ) ) {
			$item_id = absint( $data );
		} elseif ( $data instanceof LP_User_Item ) {
			$item_id = $data->get_id();
		}

		$item      = false;
		$item_type = learn_press_get_post_type( $item_id );

		switch ( $item_type ) {
			case LP_LESSON_CPT:
				$item = new LP_User_Item( $data );
				break;
			case LP_QUIZ_CPT:
				$item = new LP_User_Item_Quiz( $data );
				break;
		}

		return apply_filters( 'learn-press/user-item-object', $item, $data, $item_type );
	}

	/**
	 * Set graduation
	 *
	 * @param string $graduation .
	 *
	 * @return $this
	 */
	public function set_graduation( string $graduation ): LP_User_Item {
		$this->_set_data( 'graduation', $graduation );

		return $this;
	}

	/**
	 * Get graduation
	 *
	 * @param string $graduation .
	 *
	 * @return $this
	 */
	public function get_graduation() {
		return apply_filters(
			'learnpress/user-item/get-graduation',
			$this->get_data( 'graduation' ),
			$this->get_item_id(),
			$this->get_user()
		);
	}

	/**
	 * Update data from memory to database.
	 *
	 * @updated 3.1.0
	 *
	 * @param bool $force - Optional. Added from 3.1.0 to force update if even the data is not changed.
	 * @param bool $wp_error - Optional. Added from 3.3.0 to return WP_Error
	 *
	 * @return bool|mixed
	 */
	public function update( $force = false, $wp_error = false ) {

		if ( ! $force && ! $this->is_change() ) {
			if ( $wp_error ) {
				return new WP_Error( 'item_not_changed', __( 'Item not changed.', 'learnpress' ) );
			}

			return false;
		}

		$data = $this->get_mysql_data();

		/**
		 * @since 3.3.0
		 * Allow filter to modify data
		 */
		$data  = apply_filters( 'learn-press/update-user-item-data', $data, $this->get_user_item_id() );
		$where = array();

		if ( $this->get_user_item_id() ) {
			$where = array( 'user_item_id' => $this->get_user_item_id() );
		}

		$return = learn_press_update_user_item_field( $data, $where );

		if ( $return ) {
			foreach ( (array) $return as $k => $v ) {
				$this->_set_data( $k, $v );
			}
			$this->_changes = array();
		}

		if ( $data_course = $this->get_parent() ) {
			$data_course->calculate_course_results();
		}

		return $return;
	}

	public function is_course_item() {
		return learn_press_is_support_course_item_type( $this->get_data( 'item_type' ) );
	}

	public function get_status_label( $status = '' ) {
		$statuses = array(
			'enrolled'    => esc_html__( 'In Progress', 'learnpress' ),
			'started'     => esc_html__( 'In Progress', 'learnpress' ),
			'in-progress' => esc_html__( 'In Progress', 'learnpress' ),
			'purchased'   => esc_html__( 'Not Enrolled', 'learnpress' ),
			'completed'   => esc_html__( 'Completed', 'learnpress' ),
			'finished'    => esc_html__( 'Finished', 'learnpress' ),
			'passed'      => esc_html__( 'Passed', 'learnpress' ),
			'failed'      => esc_html__( 'Failed', 'learnpress' ),
		);

		if ( ! $status ) {
			$status = $this->get_status();
		}

		return ! empty( $statuses[ $status ] ) ? $statuses[ $status ] : esc_html__( 'Not Enrolled', 'learnpress' );
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

		if ( ! $start instanceof LP_Datetime || ! $end instanceof LP_Datetime ) {
			return false;
		}

		if ( $start->is_null() || $end->is_null() ) {
			return false;
		}
		$interval = $end->getTimestamp() - $start->getTimestamp();

		return $interval;
	}

	public function get_history() {
		return LP_Object_Cache::get(
			sprintf(
				'course-item-%s-%s-%s',
				$this->get_user_id(),
				$this->get_course( 'id' ),
				$this->get_id()
			),
			'learn-press/user-course-items'
		);
	}

	public function count_history() {
		if ( $items = $this->get_history() ) {
			return sizeof( $items );
		}

		return 0;
	}

	public function remove_user_items_history( $keep = 10 ) {
		learn_press_remove_user_items_history(
			$this->get_item_id(),
			$this->get_course( 'id' ),
			$this->get_user_id(),
			$keep
		);
	}

	/**
	 * Return number of seconds has exceeded from the expiration time to now.
	 * If less than or equals to 0 that means the time is exceeded.
	 * Otherwise, the time is not exceeded.
	 *
	 * @return float|int
	 * @since 3.3.0
	 */
	public function get_exceeded() {
		$time     = new LP_Datetime();
		$current  = $time->getTimestamp( false );
		$exceeded = $this->get_expiration_time();

		return false !== $exceeded ? $exceeded->getTimestamp() - $current : false;
	}

	/**
	 * Check if user was finished before the expiration time is exceeded.
	 * If the expiration-time is NULL that mean the course is not set duration.
	 *
	 * @return bool|float|int
	 * @since 3.3.0
	 * Todo: check remove function
	 */
	public function is_exceeded() {
		$expiration = $this->get_expiration_time();
		$end        = $this->get_end_time();

		if ( ! $expiration ) {
			return false;
		}

		// If course is not finished then consider end time is current time
		if ( ! $end || 0 >= $end->getTimestamp() ) {
			$end = new LP_Datetime();
			$end = $end->getTimestamp( false );
		} else {
			$end = $end->getTimestamp();
		}

		return $expiration->getTimestamp() - $end;
	}

	/**
	 * Get time remaining for user item.
	 *
	 * @param string $return - Optional. What kind of data to return.
	 *
	 * @return LP_Duration
	 * @since 3.3.0
	 */
	public function get_time_remaining( $return = 'object' ) {
		$is_exceeded = $this->is_exceeded();
		$time        = false;

		if ( false !== $is_exceeded ) {
			$time = 0 < $is_exceeded ? absint( $is_exceeded ) : 0;
		}

		// return apply_filters( 'learn-press/quiz/time-remaining', $remaining, $this->get_item_id(), $this->get_course_id() );
		return apply_filters(
			'learn-press/user-item-time-remaining',
			$return === 'object' ? new LP_Duration( $time ) : $time,
			$this->get_item_id(),
			$this->get_parent_id(),
			$this->get_user_id()
		);
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

		$this->set_end_time( $end_time->toSql( false ) );
		$this->set_status( $status );
		$this->update();

		$query = $wpdb->prepare(
			"
				SELECT user_item_id
				FROM {$wpdb->prefix}learnpress_user_items
				WHERE user_id = %d
					AND item_id = %d
					AND start_time <> %s AND end_time <> %s
					AND status = %s
			",
			$this->get_user_id(),
			$this->get_item_id(),
			$null_time,
			$null_time,
			$status
		);

		return $wpdb->get_var( $query );
	}

	public function delete_meta_data( $include = '', $exclude = '' ) {
		global $wpdb;

		$where = '';
		if ( $include ) {
			settype( $include, 'array' );
			$format = array_fill( 0, sizeof( $include ), '%s' );
			$where .= $wpdb->prepare( ' AND meta_key IN(' . join( ',', $format ) . ')', $include );
		}

		if ( $exclude ) {
			settype( $exclude, 'array' );
			$format = array_fill( 0, sizeof( $exclude ), '%s' );
			$where .= $wpdb->prepare( ' AND meta_key IN(' . join( ',', $format ) . ')', $exclude );
		}

		$query = $wpdb->prepare(
			"
			DELETE FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id = %d
			{$where}
		",
			$this->get_user_item_id()
		);

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
		return learn_press_get_post_type( $this->get_item_id() );
	}

	public function is_passed() {
		return $this->get_graduation() === 'passed';
	}

	public function get_percent_result( $decimal = 1 ) {
		return apply_filters(
			'learn-press/user/item-percent-result',
			sprintf(
				'%s%%',
				round( $this->get_result( 'result' ), $decimal ),
				$this->get_user_id(),
				$this->get_item_id()
			)
		);
	}

	/**
	 * Calculate expiration time from the start time and duration.
	 *
	 * @param int|string|LP_Datetime $duration
	 *
	 * @return LP_Datetime
	 * @since 3.3.0
	 */
	public function set_duration( $duration ) {
		if ( $duration instanceof LP_Datetime ) {
			$period = $duration->toSql();
		} else {
			$period = $duration;
		}

		return $this->get_expiration_time();
	}

	public function is_change() {

		$new_data = $this->get_mysql_data();
		ksort( $new_data );

		return $this->_data_key !== md5( serialize( $new_data ) );
	}

	protected function _set_data_date( $key, $value, $extra = false ) {
		if ( $value instanceof LP_Datetime ) {
			$value = $value->getTimestamp();
		} else {
			$value = is_numeric( $value ) ? $value : strtotime( $value );
		}
		$offset = (int) ( get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );

		parent::_set_data_date( $key, $value + $offset, $extra );
	}

	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		if ( ! method_exists( $this, $name ) ) {
			$course = $this->get_course();

			if ( $course ) {
				$item = $course->get_item( $this->get_item_id() );

				if ( is_callable( array( $item, $name ) ) ) {
					return call_user_func_array( array( $item, $name ), $arguments );
				}
			}
		}

		return false;
	}

	public function offsetSet( $offset, $value ) {
		// TODO: Implement offsetSet() method.
	}

	public function offsetGet( $offset ) {
		if ( is_callable( array( $this, 'get_' . $offset ) ) ) {
			return call_user_func( array( $this, 'get_' . $offset ) );
		}

		return false;
	}

	public function offsetUnset( $offset ) {
		// TODO: Implement offsetUnset() method.
	}

	public function offsetExists( $offset ) {
		// TODO: Implement offsetExists() method.
	}
}

