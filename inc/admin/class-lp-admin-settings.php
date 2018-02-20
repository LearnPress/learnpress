<?php
wp_die( __FILE__ );
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Admin_Settings
 */
class LP_Admin_Settings {
	/**
	 * The option key stored in database
	 *
	 * @var string
	 * @access protected
	 */
	protected $_key = '';

	/**
	 * Store value of the options stored in database
	 *
	 * @var array|bool
	 */
	public $_options = false;

	/**
	 * Constructor
	 *
	 * @param $key
	 */
	public function __construct( $key ) {
		if ( !$key ) {
			wp_die( __FILE__ . '::' . __FUNCTION__ );
		}
		$this->_key     = $key;
		$this->_options = (array) get_option( $this->_key );
	}

	/**
	 * Set new value for a key of $_options
	 *
	 * @param $name
	 * @param $value
	 */
	public function set( $name, $value ) {
		$this->_set_option( $this->_options, $name, $value, true );
	}

	/**
	 * Set value for an object|array by key
	 *
	 * @param      $obj
	 * @param      $var
	 * @param      $value
	 * @param bool $recurse
	 */
	private function _set_option( &$obj, $var, $value, $recurse = false ) {
		$var         = (array) explode( '.', $var );
		$current_var = array_shift( $var );
		if ( is_object( $obj ) ) {
			$obj_vars = get_object_vars( $obj );
			if ( array_key_exists( $current_var, $obj_vars ) ) { // isset( $obj->{$current_var} ) ){
				if ( count( $var ) ) {
					if ( is_object( $obj->$current_var ) ) {
						$obj->$current_var = new stdClass();
					} else {
						$obj->$current_var = array();
					}
					$this->_set_option( $obj->$current_var, join( '.', $var ), $value, $recurse );
				} else {
					$obj->$current_var = $value;
				}
			} else {
				if ( $recurse ) {
					if ( count( $var ) ) {
						$next_var = reset( $var );
						if ( is_object( $obj->$current_var ) ) {
							$obj->$current_var = new stdClass();
						} else {
							$obj->$current_var = array();
						}
						$this->_set_option( $obj->$current_var, join( '.', $var ), $value, $recurse );
					} else {
						$obj->$current_var = $value;
					}
				} else {
					$obj->$current_var = $value;
				}
			}
		} else if ( is_array( $obj ) ) {
			if ( array_key_exists( $current_var, $obj ) ) {
				if ( count( $var ) ) {
					$obj[$current_var] = array();
					$this->_set_option( $obj[$current_var], join( '.', $var ), $value, $recurse );
				} else {
					$obj[$current_var] = $value;
				}
			} else {
				if ( $recurse ) {
					if ( count( $var ) ) {
						$next_var          = reset( $var );
						$obj[$current_var] = array();
						$this->_set_option( $obj[$current_var], join( '.', $var ), $value, $recurse );
					} else {
						$obj[$current_var] = $value;
					}
				} else {
					$obj[$current_var] = $value;
				}
			}
		}
	}

	/**
	 * Get value from a key of $_options
	 *
	 * @param      $var
	 * @param null $default
	 *
	 * @return null
	 */
	public function get( $var, $default = null ) {
		return $this->_get_option( $this->_options, $var, $default );
	}

	/**
	 * Get value from a key of an object|array
	 *
	 * @param      $obj
	 * @param      $var
	 * @param null $default
	 *
	 * @return null
	 */
	public function _get_option( $obj, $var, $default = null ) {
		$var         = (array) explode( '.', $var );
		$current_var = array_shift( $var );
		if ( is_object( $obj ) ) {
			if ( isset( $obj->{$current_var} ) ) {
				if ( count( $var ) ) {
					return $this->_get_option( $obj->{$current_var}, join( '.', $var ), $default );
				} else {
					return $obj->{$current_var};
				}
			} else {
				return $default;
			}
		} else {
			if ( isset( $obj[$current_var] ) ) {
				if ( count( $var ) ) {
					return $this->_get_option( $obj[$current_var], join( '.', $var ), $default );
				} else {
					return $obj[$current_var];
				}
			} else {
				return $default;
			}
		}
		return $default;
	}

	/**
	 * Combine an array|object to current options
	 *
	 * @param $new
	 */
	public function bind( $new ) {
		if ( is_object( $new ) ) $new = (array) $new;
		if ( is_array( $new ) ) {
			foreach ( $new as $k => $v ) {
				$this->set( $k, $v );
			}
		}
	}

	/**
	 * Store options into database
	 */
	public function update() {
		update_option( $this->_key, $this->_options );
	}

	/*** XXXXXXXXXXXXXXXXXXXX
	public static function instance( $key ) {
		static $instances = array();
		$key = '_lpr_settings_' . $key;
		if ( empty( $instances[$key] ) ) {
			$instances[$key] = new LP_Admin_Settings( $key );
		}
		return $instances[$key];
	}*/
}