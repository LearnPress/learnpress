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

	/**
	 * LP_Update_Base constructor.
	 */
	public function __construct() {
		add_filter( 'query', array( $this, 'log_query' ) );
		$this->_get_version();
		$this->update();
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
			LP_Debug::instance()->add( "===== " . $this->version . " ===== \n" . $query, 'query-updater', false, true );
		}

		return $query;
	}

	/**
	 * Entry point
	 */
	public function update() {

		$db_version = get_option( 'learnpress_db_version' );
		if ( $db_version && version_compare( $db_version, $this->version, '>' ) ) {
			return false;
		}

		$step = get_option( 'learnpress_updater_step' );
		try {

			if ( ! $step ) {
				$step = reset( $this->steps );
				update_option( 'learnpress_updater_step', $step );
			}

			$running_step = get_option( 'learnpress_updater_running_step' );

			foreach ( $this->steps as $callback ) {
				if ( $callback == $step ) {
					if ( is_callable( array( $this, $callback ) ) ) {

//						if ( $running_step === $step ) {
//							break;
//						}

						echo "Running " . get_class( $this ) . '::' . $callback, "\n";
						update_option( 'learnpress_updater_running_step', $step );
						call_user_func( array( $this, $callback ) );
						delete_option( 'learnpress_updater_running_step' );
					}
					break;
				}
			}

		}
		catch ( Exception $exception ) {
			LP_Debug::rollbackTransaction();
		}

		return false;
	}

	/**
	 * Move to next step
	 */
	protected function _next_step() {
		$step = get_option( 'learnpress_updater_step' );
		if ( false !== ( $pos = array_search( $step, $this->steps ) ) ) {
			$pos ++;
			$next_step = ! empty( $this->steps[ $pos ] ) ? $this->steps[ $pos ] : '';

			if ( $next_step ) {
				update_option( 'learnpress_updater_step', $next_step );
			} else {
				delete_option( 'learnpress_updater_step' );
				delete_option( 'learnpress_updater' );
				LP_Install::update_db_version( $this->version );
			}
		}

		return true;
	}
}