<?php
/**
 * Class LP_Debug
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
	 * @var array
	 */
	private static $_log_functions = array();

	protected static $log_times = array();


	/**
	 * Constructor for the logger.
	 */
	protected function __construct() {
		$this->_handles = array();

		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * @var string
	 */
	protected static $_current_name = '';

	/**
	 * @var null
	 */
	protected $_lock = null;

	/**
	 * @var bool
	 */
	protected static $_transaction_started = false;

	/**
	 * Destructor.
	 */
	public function __destruct() {
		if ( ! $this->_handles ) {
			return;
		}
		foreach ( $this->_handles as $handle ) {
			@fclose( $handle );
		}
	}

	public function init() {
		//add_action( 'shutdown', array( $this, 'output' ) );
	}

	public static function is_enable_log() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG && ( ! learn_press_is_ajax() || ( function_exists( 'is_ajax' ) && ! is_ajax() ) );
	}

	public function output() {
		if ( self::is_enable_log() && self::$_log_functions && ! is_admin() ) {
			uasort( self::$_log_functions, array( $this, 'sort_log_functions' ) );
			$total_time = 0;
			$i          = 0;
			echo "<!---\n";
			foreach ( self::$_log_functions as $func => $times ) {
				if ( ! is_array( $times ) ) {
					continue;
				}
				$time = array_sum( $times );
				echo str_pad( ++ $i, 3, '-', STR_PAD_LEFT ) . '.' . str_pad( $func, 50, '-' ) . ' = ' . str_pad( sizeof( $times ), 5, '-' ) . "(" . $time . ')' . "\n";
				$total_time += $time;
			}
			echo '----' . str_pad( 'Total time', 50, '-' ) . ' = ' . $total_time . "\n";
			echo microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'], ',', date( 'Y-m-d H:i:s' ), ',', date( 'Y-m-d H:i:s', $_SERVER['REQUEST_TIME_FLOAT'] );

			echo "---->";
		}
	}

	public function sort_log_functions( $a, $b ) {
		return sizeof( $a ) < sizeof( $b );
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
		if ( isset( $this->_handles[ $handle ] ) ) {
			return true;
		}

		try {
			$path = learn_press_get_log_file_path( $handle );
			$dir  = dirname( $path );

			// Try to create path
			if ( ! is_writeable( $dir ) ) {
				@mkdir( $dir, 0777, true );
			}

			// Create file if does not exists
			if ( ! file_exists( $path ) ) {
				$f = @fopen( $path, 'w' );
				fclose( $f );
			}

			// Open to read/write
			$f = @fopen( $path, 'r+' );

			if ( $f ) {
				// 3M
				if ( filesize( $path ) >= 1024 * 1024 * 3 ) {
					ftruncate( $f, 0 );
				}

				$this->_handles[ $handle ] = $f;

				return true;
			}
		}
		catch ( Exception $ex ) {
			error_log( $ex->getMessage() );
		}

		return false;
	}

	private function close( $handle ) {
		if ( isset( $this->_handles[ $handle ] ) ) {
			@fclose( $this->_handles[ $handle ] );
			unset( $this->_handles[ $handle ] );

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
	 * @param bool   $force
	 */
	public function add( $message, $handle = 'log', $clear = false, $force = false ) {
		if ( ! $handle ) {
			$handle = 'log';
		}

		if ( $this->_lock === null ) {
			$this->_lock = ! ( LP_Settings::instance()->get( 'debug' ) == 'yes' );
		}


		if ( ( ! $force && ! $this->_lock || $force ) && $this->_can_log( $handle ) ) {
			if ( $clear ) {
				$this->clear( $handle );
			}
			$time = date_i18n( 'm-d-Y @ H:i:s' );

			if ( ! is_string( $message ) ) {
				ob_start();
				print_r( $message );
				$message = ob_get_clean();
			}
			$backtrace = debug_backtrace();
			ob_start();
			echo "\n\n";
			foreach ( array( 'file', 'line', 'function', 'class' ) as $prop ) {
				if ( isset( $backtrace[0][ $prop ] ) ) {
					echo "=> " . str_pad( $prop, 10 ) . ':' . $backtrace[0][ $prop ] . "\n";
				}
			}
			$message .= ob_get_clean();
			try {

				$path = learn_press_get_log_file_path( $handle . '-temp' );
				$f    = @fopen( $path, 'a' );
				fwrite( $f, "----------" . $time . "----------\n" . $message . "-----------------------------------------\n\n\n" );
				fseek( $this->_handles[ $handle ], 0 );
				while ( ( $buffer = fgets( $this->_handles[ $handle ], 4096 ) ) !== false ) {
					fwrite( $f, $buffer );
				}

				fclose( $f );
				$this->close( $handle );
				unlink( learn_press_get_log_file_path( $handle ) );
				rename( $path, learn_press_get_log_file_path( $handle ) );

				//fwrite( $this->_handles[ $handle ], "----------" . $time . "----------\n" . $message . "\n--------------------" );
				do_action( 'learn_press_log_add', $handle, $message );
			}
			catch ( Exception $ex ) {
				error_log( 'LearnPress add log failed!' );
			}
		}
	}

	protected function _can_log( $handle ) {
		return $this->open( $handle ) && is_resource( $this->_handles[ $handle ] );
	}

	/**
	 * Clear entries from chosen file.
	 *
	 * @param mixed $handle
	 */
	public function clear( $handle ) {
		if ( $this->open( $handle ) && is_resource( $this->_handles[ $handle ] ) ) {
			@ftruncate( $this->_handles[ $handle ], 0 );
		}

		do_action( 'learn_press_log_clear', $handle );
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

	public static function timeStart( $name = '' ) {
		if ( ! $name ) {
			self::$_current_name = md5( uniqid() );
			$name                = self::$_current_name;
		}
		self::$_current_name  = $name;
		self::$_time[ $name ] = microtime( true );
	}

	public static function timeEnd( $name = '', $echo = true ) {

		if ( ! $name ) {
			$name = self::$_current_name;
		}

		$time = microtime( true ) - self::$_time[ $name ];

		if ( $echo ) {
			echo "{$name} execution time = " . $time . "\n";
		}

		unset( self::$_time[ $name ] );

		return $time;
	}

	public static function logTime( $name ) {
		if ( empty( self::$log_times[ $name ] ) ) {
			self::$log_times[ $name ] = array();
		}

		static $time;
		if ( ! $time ) {
			$time = microtime( true );
		} else {
			self::$log_times[ $name ][] = microtime( true ) - $time;
			$time                     = 0;
		}
	}

	public static function getLogTimes(){
		return self::$log_times;
	}

	/**
	 * Throw an exception.
	 *
	 * @param string    $message
	 * @param int       $code
	 * @param Throwable $prev
	 * @param string    $type A class of an exception, default Exception.
	 *
	 * @throws Exception.
	 */
	public static function throw_exception( $message, $code = null, $prev = null, $type = '' ) {
		if ( learn_press_is_debug() ) {
			$exception = class_exists( $type ) ? $type : 'Exception';
			$exception = new $exception( $message, $code, $prev );
			throw $exception;
		}
	}

	/**
	 * Start a new sql transaction
	 */
	public static function startTransaction() {
		global $wpdb;

		if ( self::$_transaction_started ) {
			return;
		}

		$wpdb->query( "START TRANSACTION;" );

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

		$wpdb->query( "ROLLBACK;" );

		self::$_transaction_started = false;
	}

	/**
	 * Commit a sql transaction
	 */
	public static function commitTransaction() {
		global $wpdb;

		if ( ! self::$_transaction_started ) {
			return;
		}

		$wpdb->query( "COMMIT;" );

		self::$_transaction_started = false;
	}

	public static function log_function( $func ) {

		if ( ! self::is_enable_log() ) {
			return;
		}

		if ( empty( self::$_log_functions[ $func ] ) ) {
			self::$_log_functions[ $func ] = array();
		}

		$last_func = ! empty( self::$_log_functions[ $func . '_func' ] ) ? self::$_log_functions[ $func . '_func' ] : '';

		if ( $last_func == $func ) {
			$time                                    = microtime( true ) - self::$_log_functions[ $func . '_time' ];
			self::$_log_functions[ $func ][]         = $time;
			self::$_log_functions[ $func . '_func' ] = '';

		} else {
			self::$_log_functions[ $func . '_time' ] = microtime( true );
			self::$_log_functions[ $func . '_func' ] = $func;
		}

	}
}

return LP_Debug::instance();