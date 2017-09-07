<?php

/**
 * Class LP_User_Item
 * @since 3.x.x
 */
class LP_User_Item extends LP_Abstract_Object_Data {
	/**
	 * LP_User_Item constructor.
	 *
	 * @param array $item. A record fetched from table _learnpress_user_items
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
	 */
	public function get_start_time() {
		$this->get_data( 'start_time' );
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
	 */
	public function get_end_time() {
		$this->get_data( 'end_time' );
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
		return $this->get_data( 'user_id' );
	}

	/**
	 * Get current question ID (quiz).
	 *
	 * @return int
	 */
	public function get_current_question() {
		return learn_press_get_user_item_meta( $this->get_data( 'user_item_id' ), '_current_question', true );
	}

	/**
	 * @return int
	 */
	public function get_user_item_id(){
		return $this->get_data('user_item_id');
	}
}