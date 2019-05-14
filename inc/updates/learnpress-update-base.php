<?php

/**
 * Class LP_Update_Base
 *
 * Helper class for updating database
 */
class LP_Update_Base {

	/**
	 * @var array
	 */
	protected $steps = array();

	/**
	 * @var string
	 */
	protected $version = '';

	protected $percent = 0;

	/**
	 * LP_Update_Base constructor.
	 */
	public function __construct() {
		add_filter( 'query', array( $this, 'log_query' ) );
		$this->_get_version();
	}

	/**
	 * Get version number from file name in case it is not defined.
	 *
	 * @return string
	 */
	protected function _get_version() {
		if ( empty( $this->version ) ) {
			if ( preg_match( '~-([0-9.]+)$~', basename( __FILE__, '.php' ), $m ) ) {
				$this->version = $m[1];
			}
		}

		return $this->version;
	}

	public function log_query( $query ) {
		global $wpdb;
		if ( preg_match_all( '#' . $wpdb->prefix . 'learnpress#im', $query )
		     || preg_match_all( '#' . $wpdb->prefix . 'posts#im', $query )
		     || preg_match_all( '#' . $wpdb->prefix . 'postmeta#im', $query )
		) {
			//LP_Debug::instance()->add( "===== " . $this->version . " ===== \n" . $query, 'query-updater', false, true );
		}

		return $query;
	}

	/**
	 * Entry point
	 *
	 * @param bool $force
	 *
	 * @return mixed
	 */
	public function update( $force = false ) {
		$return = true;

		$db_version = get_option( 'learnpress_db_version' );
		if ( ! $force && $db_version && version_compare( $db_version, $this->version, '>' ) ) {
			return $return;
		}

		$step = get_option( 'learnpress_updater_step' );
		try {

			if ( ! $step ) {
				$step = reset( $this->steps );
				update_option( 'learnpress_updater_step', $step );
			}

			$called = false;

			$running_step = get_option( 'learnpress_updater_running_step' );

			foreach ( $this->steps as $callback ) {
				if ( $callback == $step ) {
					if ( is_callable( array( $this, $callback ) ) ) {

						$this->output( "Running " . get_class( $this ) . '::' . $callback . "\n" );
						update_option( 'learnpress_updater_running_step', $step );
						if ( $return = call_user_func( array( $this, $callback ) ) ) {
							$this->_next_step();
						}

					} else {
						$this->output( "$callback failed" );
						$this->_next_step();
					}

					$called = true;

					break;
				}
			}

			if ( ! $called ) {
				$this->output( "Step {$step} not found" );
				$this->_next_step();
			}

		}
		catch ( Exception $exception ) {
			$this->output( $exception->getMessage() );
			LP_Debug::rollbackTransaction();
		}

		$this->percent = array_search( $step, $this->steps );
		$return        = $this->is_last_step( $step ) ? $return : false;

		if ( $return == true ) {
			delete_option( 'learnpress_updater_running_step' );
			delete_option( 'learnpress_updater_step' );
			update_option( 'learnpress_db_version', $this->version );
			do_action( 'learn-press/update-completed', $this->version );
		}

		return $return;
	}

	public function get_percent() {
		return $this->percent / sizeof( $this->steps ) * 100;
	}

	public function is_last_step( $step = '' ) {
		if ( ! $step ) {
			$step = get_option( 'learnpress_updater_step' );
		}

		$end_step = end( $this->steps );

		return $step === $end_step;
	}

	protected function output( $content ) {
		if ( ! learn_press_is_ajax() ) {
			return;
		}

		print_r( $content );
	}

	/**
	 * Move to next step
	 */
	protected function _next_step() {
		$step = get_option( 'learnpress_updater_step' );
		if ( false !== ( $pos = array_search( $step, $this->steps ) ) ) {
			$pos ++;
			$next_step = ! empty( $this->steps[ $pos ] ) ? $this->steps[ $pos ] : '';
		} else {
			$next_step = end( $this->steps );
		}
		if ( $next_step ) {
			update_option( 'learnpress_updater_step', $next_step );
		} else {
			delete_option( 'learnpress_updater_step' );
			delete_option( 'learnpress_updater' );
			LP_Install::update_db_version( $this->version );
		}

		return true;
	}
}