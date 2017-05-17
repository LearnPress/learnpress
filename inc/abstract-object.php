<?php

/**
 * Class LP_Abstract_Object
 */
class LP_Abstract_Object {

	/**
	 * @var int
	 */
	protected $_id = 0;

	/**
	 * @var array
	 */
	protected $_data = array();

	/**
	 *
	 * @var bool
	 */
	protected $_no_cache = false;

	/**
	 * LP_Abstract_Object constructor.
	 *
	 * @param null $data
	 */
	public function __construct( $data = null ) {
		$this->_data = (array) $data;
		if ( array_key_exists( 'id', $this->_data ) ) {
			$this->set_id( absint( $this->_data['id'] ) );
			unset( $this->_data['id'] );
		}
	}

	public function set_id( $id ) {
		$this->_id = $id;
	}

	public function get_id() {
		return $this->_id;
	}

	public function get_data( $name = false ) {
		return false !== $name && array_key_exists( $name, $this->_data ) ? $this->_data[ $name ] : false;
	}

	public function set_data( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$this->set_data( $key, $value );
			}
		} else {
			$key                 = func_get_arg( 0 );
			$value               = func_get_arg( 1 );
			$this->_data[ $key ] = $value;
		}
	}

	public function set_no_cache( $value ) {
		$this->_no_cache = $value;
	}

	public function get_no_cache(){
		return $this->_no_cache;
	}
}