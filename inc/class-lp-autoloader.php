<?php

/**
 * LP_Autoloader
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class LP_Autoloader {

	/**
	 * Path to the includes directory
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor
	 */
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( LP_PLUGIN_PATH ) . '/inc/';
	}

	/**
	 * Take a class name and turn it into a file name
	 *
	 * @param  string $class
	 *
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
	}

	/**
	 * Include a class file
	 *
	 * @param  string $path
	 *
	 * @return bool successful or not
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once( $path );
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param string $class
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );
		$file  = $this->get_file_name_from_class( $class );
		$path  = '';

		// payment gateways
		if ( strpos( $class, 'lp_gateway_' ) === 0 ) {
			$path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class ), 11 ) . '/';
		} elseif ( preg_match( '!lp_meta_box_|rwmb_!', $class, $matches ) ) { // meta box fields

			$file = 'class-' . substr( str_replace( '_', '-', $class ), 5 ) . '.php';
			$path = $this->include_path . 'admin/meta-boxes/';

		} elseif ( strpos( $class, 'lp_statistic_' ) === 0 ) {
			$path = $this->include_path . 'admin/dashboard-statistics/';
		} else {
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';
			$path = dirname( __FILE__ ) . '/';
			if ( strpos( $class, 'lp_user' ) !== false || $class == 'lp_abstract_user' ) {
				$path .= 'user/';
			}

			if ( !file_exists( $path . $file ) ) {
				$segs = explode( '_', $class );
				if ( !empty( $segs[1] ) ) {
					$path .= $segs[1] . '/';
				}
			}
		}
		$this->load_file( $path . $file );
	}
}

new LP_Autoloader();
