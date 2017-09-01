<?php

class LP_User_Course_Item extends LP_User_Item implements ArrayAccess {
	protected $_items = array();

	protected $_course = 0;

	protected $_user = 0;

	public function __construct( $item ) {
		parent::__construct( $item );

		$this->read_items();
	}

	public function read_items() {
		$course       = learn_press_get_course( $this->get_id() );
		$course_items = $course->get_items();
		//$user_items = $course_item['items'];
		if ( $course_items ) {
			foreach ( $course_items as $item_id ) {

				$data = wp_cache_get( sprintf( 'course-item-%s-%s-%s', $this->get_user_id(), $this->get_id(), $item_id ), 'lp-user-course-items' );

				$this->_items[ $item_id ] = new LP_User_Item( $data ? end( $data ) : array() );
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
}