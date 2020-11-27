<?php
/**
 * Class LP_Abstract_Singleton
 */
abstract class LP_Abstract_Singleton {

	/**
	 * Array of singleton classes.
	 *
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * LP_Abstract_Singleton constructor.
	 */
	protected function __construct() {
	}

	/**
	 * @return mixed
	 */
	public static function instance() {
		$name = self::_get_called_class();

		if ( false === $name ) {
			return false;
		}

		if ( empty( self::$instances[ $name ] ) ) {
			self::$instances[ $name ] = new $name();
		}

		return self::$instances[ $name ];
	}

	/**
	 * @return bool|string
	 */
	protected static function _get_called_class() {
		if ( function_exists( 'get_called_class' ) ) {
			return get_called_class();
		}

		$backtrace = debug_backtrace();

		if ( empty( $backtrace[2] ) ) {
			return false;
		}

		if ( empty( $backtrace[2]['args'][0] ) ) {
			return false;
		}

		return $backtrace[2]['args'][0];
	}
}
