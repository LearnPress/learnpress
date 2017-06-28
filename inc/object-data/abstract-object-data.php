<?php

/**
 * Class LP_Abstract_Object_Data
 */
class LP_Abstract_Object_Data {

	/**
	 * @var int
	 */
	protected $_id = 0;

	/**
	 * @var array
	 */
	protected $_data = array();

	/**
	 * @var array
	 */
	protected $_extra_data = array();

	/**
	 *
	 * @var bool
	 */
	protected $_no_cache = false;

	/**
	 * @var array
	 */
	protected $_supports = array();

	/**
	 * LP_Abstract_Object_Data constructor.
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

	/**
	 * Set id of object in database
	 *
	 * @param $id
	 */
	public function set_id( $id ) {
		$this->_id = $id;
	}

	/**
	 * Get id of object in database
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->_id;
	}

	/**
	 * Get object data
	 *
	 * @param bool $name
	 *
	 * @return array|mixed
	 */
	public function get_data( $name = false ) {
		return false !== $name && array_key_exists( $name, $this->_data ) ? $this->_data[ $name ] : $this->_data;
	}

	/**
	 * Set object data
	 *
	 * @param $data
	 */
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

	/**
	 * Check if question is support feature.
	 *
	 * @param string $feature
	 * @param string $type
	 *
	 * @return bool
	 */
	public function is_support( $feature, $type = '' ) {
		$feature    = $this->_sanitize_feature_key( $feature );
		$is_support = array_key_exists( $feature, $this->_supports ) ? true : false;
		if ( $type && $is_support ) {
			return $this->_supports[ $feature ] === $type;
		}

		return $is_support;
	}

	/**
	 * Add a feature that question is supported
	 *
	 * @param        $feature
	 * @param string $type
	 */
	public function add_support( $feature, $type = 'yes' ) {
		$feature                     = $this->_sanitize_feature_key( $feature );
		$this->_supports[ $feature ] = $type === null ? 'yes' : $type;
	}

	/**
	 * @param $feature
	 *
	 * @return mixed
	 */
	protected function _sanitize_feature_key( $feature ) {
		return preg_replace( '~[_]+~', '-', $feature );
	}

	/**
	 * Get all features are supported by question.
	 *
	 * @return array
	 */
	public function get_supports() {
		return $this->_supports;
	}

	/**
	 * @param $value
	 */
	public function set_no_cache( $value ) {
		$this->_no_cache = $value;
	}

	/**
	 * @return bool
	 */
	public function get_no_cache() {
		return $this->_no_cache;
	}
}