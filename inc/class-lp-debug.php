<?php
/**
 * Class LP_Debug
 */

if ( !defined( 'ABSPATH' ) ) {
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
	public function __construct() {
		$this->_handles = array();
	}


	/**
	 * Destructor.
	 */
	public function __destruct() {
		foreach ( $this->_handles as $handle ) {
			@fclose( $handle );
		}
	}


	/**
	 * Open log file for writing.
	 *
	 * @access private
	 *
	 * @param mixed $handle
	 *
	 * @return bool success
	 */
	private function open( $handle ) {
		if ( isset( $this->_handles[$handle] ) ) {
			return true;
		}

		$path = learn_press_get_log_file_path( $handle );
		$f    = @fopen( $path, 'a' );

		// if path is not exists, creates path and try again!
		if ( !$f ) {
			LP_Install::create_files();
			$f = @fopen( $path, 'a' );
		}

		if ( $f ) {
			if ( filesize( $path ) >= 1024 * 1024 * 1 ) {
				ftruncate( $f, 0 );
			}
			$this->_handles[$handle] = $f;
			return true;
		}
		return false;
	}


	/**
	 * Add a log entry to chosen file.
	 *
	 * @param string $handle
	 * @param string $message
	 * @param bool   $clear
	 */
	public function add( $message, $handle = 'log', $clear = false ) {
		if ( !$handle ) {
			$handle = 'log';
		}
		if ( LP()->settings->get( 'debug' ) == 'yes' && $this->open( $handle ) && is_resource( $this->_handles[$handle] ) ) {
			if ( $clear ) {
				$this->clear( $handle );
			}
			$time = date_i18n( 'm-d-Y @ H:i:s -' );
			if ( !is_string( $message ) ) {
				ob_start();
				print_r( $message );
				$message = ob_get_clean();
			}
			fwrite( $this->_handles[$handle], "-----" . $time . "-----\n" . $message . "\n" );
		}
		do_action( 'learn_press_log_add', $handle, $message );
	}


	/**
	 * Clear entries from chosen file.
	 *
	 * @param mixed $handle
	 */
	public function clear( $handle ) {
		if ( $this->open( $handle ) && is_resource( $this->_handles[$handle] ) ) {
			@ftruncate( $this->_handles[$handle], 0 );
		}

		do_action( 'learn_press_log_clear', $handle );
	}

	/**
	 * @return LP_Debug|null
	 */
	public static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public static function debug() {
		if ( LP_Settings::instance()->get( 'debug' ) != 'yes' ) {
			return;
		}
		if ( $args = func_get_args() ) {
			foreach ( $args as $arg ) {
				learn_press_debug( $arg );
			}
		}
	}

	public static function exception( $message ) {
		if ( LP_Settings::instance()->get( 'debug' ) != 'yes' ) {
			return;
		}
		throw new Exception( $message );
	}

	public static function timeStart( $name ) {
		self::$_time[$name] = microtime();
	}

	public static function timeEnd( $name ) {
		$time = microtime() - self::$_time[$name];
		echo "{$name} execution time = " . $time;
		unset( self::$_time[$name] );
	}
}