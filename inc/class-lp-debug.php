<?php
/**
 * Class LP_Debug
 *
 * @editor tungnx
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LP_Debug {

	/**
	 * @var array Stores open file _handles.
	 * @access private
	 */
	private $_handles;

	/**
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * @var array
	 */
	private static $_time = array();

	/**
	 * Constructor for the logger.
	 */
	protected function __construct() {

	}

	/**
	 * @var bool
	 */
	protected static $_transaction_started = false;

	public function output() {

	}

	/**
	 * Set time start
	 *
	 * @param string $name
	 */
	public static function time_start( string $name = '' ) {
		if ( ! self::is_debug() ) {
			return;
		}

		if ( empty( $name ) ) {
			echo 'Please set unique name';
		}

		self::$_time[ $name ] = microtime( true );
	}

	/**
	 * Get total time execute
	 *
	 * @param string $name
	 */
	public static function time_end( string $name = '' ) {
		if ( ! self::is_debug() ) {
			return;
		}
		if ( empty( $name ) ) {
			echo 'Please set unique name';
		}

		$time = microtime( true ) - self::$_time[ $name ];
		echo "{$name} execution time = " . $time . "\n";
		unset( self::$_time[ $name ] );
	}

	/**
	 * Start a new sql transaction
	 *
	 * Remove this function on add-on Frontend Editor and update
	 */
	public static function startTransaction() {
		global $wpdb;

		if ( self::$_transaction_started ) {
			return;
		}

		$wpdb->query( 'START TRANSACTION;' );

		self::$_transaction_started = true;
	}

	/**
	 * Rollback a sql transaction
	 */
	public static function rollbackTransaction() {
		global $wpdb;

		if ( ! self::$_transaction_started ) {
			return;
		}

		$wpdb->query( 'ROLLBACK;' );

		self::$_transaction_started = false;
	}

	/**
	 * Show value of variable
	 *
	 * @param $variable
	 * @param $file_path
	 * @param $line
	 */
	public static function var_dump( $variable, $file_path, $line ) {
		echo '<pre>' . print_r( $variable, true ) . '</pre>';
		echo 'FILE:' . $file_path . '<br> LINE:' . $line;
	}

	/**
	 * Check enable debug mode
	 *
	 * @return bool
	 * @since 3.2.8
	 * @editor tungnx
	 */
	public static function is_debug(): bool {
		return LP_Settings::get_option( 'debug', 'no' ) == 'yes';
	}

	/**
	 * @return LP_Debug|null
	 */
	public static function instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

return LP_Debug::instance();
