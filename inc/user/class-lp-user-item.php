<?php

class LP_User_Item extends LP_Abstract_Object_Data {

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

	public function get_type() {
		return $this->get_data( 'item_type' );
	}

	public function set_start_time( $time ) {
		$this->set_data_date( 'start_time', $time );
	}

	public function get_start_time() {
		$this->get_data( 'start_time' );
	}

	public function set_end_time( $time ) {
		$this->set_data_date( 'end_time', $time );
	}

	public function get_end_time() {
		$this->get_data( 'end_time' );
	}

	public function set_status( $status ) {
		$this->_set_data( 'status', $status );
	}

	public function get_status() {
		return $this->get_data( 'status' );
	}

	public function get_user_id() {
		return $this->get_data( 'user_id' );
	}

	public function get_current_question() {
		return learn_press_get_user_item_meta( $this->get_data( 'user_item_id' ), '_current_question', true );
	}
}