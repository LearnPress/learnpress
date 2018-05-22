<?php

/**
 * Class LP_Array_Access
 */
class LP_Array_Access implements ArrayAccess, Iterator, Countable {

	/**
	 * @var array
	 */
	protected $_data = array();

	/**
	 * @var int
	 */
	protected $_position = 0;

	/**
	 * LP_Array_Access constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data ) {
		$this->_data = is_array( $data ) ? $data : (array) $data;
	}

	public function offsetExists( $offset ) {
		if ( $offset ) {
			return array_key_exists( $offset, $this->_data );
		}

		return false;
	}

	public function offsetSet( $offset, $value ) {
		if ( $offset ) {
			$this->_data[ $offset ] = $value;
		}
	}

	public function offsetGet( $offset ) {
		return $this->offsetExists( $offset ) ? $this->_data[ $offset ] : false;
	}

	public function offsetUnset( $offset ) {
		if ( $this->offsetExists( $offset ) ) {
			unset( $this->_data[ $offset ] );

			return true;
		}

		return false;
	}

	/**
	 * Reset current position of answer options.
	 */
	public function rewind() {
		$this->_position = 0;
	}

	/**
	 * @return mixed
	 */
	public function current() {
		$values = array_values( $this->_data );

		return $values[ $this->_position ];
	}

	/**
	 * @return mixed
	 */
	public function key() {
		$keys = array_keys( $this->_data );

		return $keys[ $this->_position ];
	}

	/**
	 * Nex question.
	 */
	public function next() {
		++ $this->_position;
	}

	/**
	 * @return bool
	 */
	public function valid() {
		$values = array_values( $this->_data );

		return isset( $values[ $this->_position ] );
	}

	public function count() {
		return sizeof($this->_data);
	}
}