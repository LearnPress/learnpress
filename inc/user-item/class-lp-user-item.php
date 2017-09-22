<?php

/**
 * Class LP_User_Item
 * @since 3.x.x
 */
class LP_User_Item extends LP_Abstract_Object_Data {
	protected static $_loaded = 0;

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
	public function get_start_time( $format = '') {
		$date = new LP_Datetime( $this->get_data( 'start_time' ) );

		if ( $format ) {
			return $date->format( $format );
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
			return $date->format( $format );
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
		if ( $uid = $this->get_data( 'user_id' ) ) {
			if ( $return == '' ) {
				return learn_press_get_user( $uid );
			}
		}

		return $uid;
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

	public function update_meta() {
		if ( $this->_meta_data ) {
			foreach ( $this->_meta_data as $meta_data ) {
				learn_press_update_user_item_meta( $this->get_user_item_id(), $meta_data->meta_key, $meta_data->meta_value );
			}
		}
	}

	public function get_mysql_data() {
		$columns = array();
		foreach ( $this->_data as $k => $v ) {
			switch ( $k ) {
				case 'start_time':
				case 'end_time':
					$v = $v->toSql();
					break;
				case 'start_time_gmt':
					$v = new LP_Datetime( $this->_data['start_time'] );
					$v = $v->toSql( false );
					break;
				case 'end_time_gmt':
					$v = new LP_Datetime( $this->_data['end_time'] );
					$v = $v->toSql( false );
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
		learn_press_update_user_item_field( $data );
	}

	public function get_status_label() {
		$statuses = array(
			'enrolled'  => __( 'In Progress', 'learnpress' ),
			'started'   => __( 'In Progress', 'learnpress' ),
			'completed' => __( 'Completed', 'learnpress' ),
			'finished'  => __( 'Finished', 'learnpress' )
		);

		return ! empty( $statuses[ $this->get_status() ] ) ? $statuses[ $this->get_status() ] : __( 'Unknown', 'learnpress' );
	}
}