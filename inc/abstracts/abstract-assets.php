<?php

/**
 * Class LP_Abstract_Assets
 *
 * Abstract class for managing assets
 */
abstract class LP_Abstract_Assets {

	protected $_cache = '';

	protected $_scripts = array();

	protected $_styles = array();

	protected $_enqueue_scripts = array();

	protected $_enqueue_styles = array();

	/**
	 * LP_Abstract_Assets constructor.
	 */
	public function __construct() {
		$this->_cache = learn_press_is_debug() ? microtime( true ) : '';

		$priory = 1000;
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'do_register' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ), $priory );
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'do_register' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), $priory );
			add_action( 'wp_print_scripts', array( $this, 'localize_printed_scripts' ), $priory + 10 );
			add_action( 'wp_print_footer_scripts', array( $this, 'localize_printed_scripts' ), $priory + 10 );
		}
	}

	abstract function load_scripts();

	public function do_register() {

	}

	/**
	 * Default scripts
	 *
	 * @return array
	 */
	protected function _get_scripts() {
		return array();
	}

	/**
	 * Default styles
	 *
	 * @return array
	 */
	protected function _get_styles() {
		return array();
	}

	/**
	 * Register and/or enqueue scripts registered.
	 */
	protected function _do_enqueue_scripts() {
		if ( ! $this->_scripts ) {
			return;
		}

		foreach ( $this->_scripts as $handle => $data ) {
			// Enqueue script if handle is in the queue
			if ( in_array( $handle, $this->_enqueue_scripts ) ) {
				call_user_func_array( 'wp_enqueue_script', $data );
			} else {
				call_user_func_array( 'wp_register_script', $data );
			}
		}
	}

	/**
	 * Register and/or enqueue styles registered.
	 */
	protected function _do_enqueue_styles() {
		if ( ! $this->_styles ) {
			return;
		}

		foreach ( $this->_styles as $handle => $data ) {
			// Enqueue style if handle is in the queue
			if ( in_array( $handle, $this->_enqueue_styles ) ) {
				call_user_func_array( 'wp_enqueue_style', $data );
			} else {
				call_user_func_array( 'wp_register_style', $data );
			}
		}
	}

	/**
	 * Register or enqueue styles+scripts registered.
	 */
	public function do_enqueue() {
		$this->_do_enqueue_scripts();
		$this->_do_enqueue_styles();
	}

	/**
	 * Register style
	 *
	 * @param        $handle
	 * @param        $src
	 * @param array  $deps
	 * @param bool   $ver
	 * @param string $media
	 */
	public function register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
		if ( ! isset( $this->_styles[ $handle ] ) ) {
			$this->_styles[ $handle ] = array( $handle, $src, $deps, $ver, $media );
		}
	}

	/**
	 * Register script
	 *
	 * @param       $handle
	 * @param       $src
	 * @param array $deps
	 * @param bool  $ver
	 * @param bool  $in_footer
	 */
	public function register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
		if ( ! isset( $this->_scripts[ $handle ] ) ) {
			$this->_scripts[ $handle ] = array( $handle, $src, $deps, $ver, $in_footer );
		}
	}

	/**
	 * Enqueue style
	 *
	 * @param        $handle
	 * @param string $src
	 * @param array  $deps
	 * @param bool   $ver
	 * @param string $media
	 */
	public function enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		$this->register_script( $handle, $src, $deps, $ver, $media );
		if ( did_action( 'init' ) || did_action( 'admin_enqueue_scripts' ) || did_action( 'wp_enqueue_scripts' ) || did_action( 'login_enqueue_scripts' ) ) {
			call_user_func_array( 'wp_enqueue_style', $this->_styles[ $handle ] );
		} else {
			$this->_enqueue_styles[] = $handle;
		}
	}

	/**
	 * Enqueue script
	 *
	 * @param       $handle
	 * @param       $src
	 * @param array $deps
	 * @param bool  $ver
	 * @param bool  $in_footer
	 */
	public function enqueue_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
		$this->register_script( $handle, $src, $deps, $ver, $in_footer );
		if ( did_action( 'init' ) || did_action( 'admin_enqueue_scripts' ) || did_action( 'wp_enqueue_scripts' ) || did_action( 'login_enqueue_scripts' ) ) {
			call_user_func_array( 'wp_enqueue_script', $this->_scripts[ $handle ] );
		} else {
			$this->_enqueue_scripts[] = $handle;
		}
	}

	/**
	 * Register scripts and styles for admin.
	 */
	protected function _register_scripts() {
		global $wp_scripts, $wp_styles;

		// No use cache if debug mode is turn on
		$no_cache = '';
		if ( learn_press_is_debug() ) {
			$no_cache = microtime( true );
		}

		if ( $default_scripts = $this->_get_scripts() ) {

			foreach ( $default_scripts as $handle => $data ) {
				if ( is_string( $data ) ) {
					$data = array( 'url' => $data );
				}

				$data = wp_parse_args(
					$data,
					array(
						'deps' => null,
						'ver'  => LEARNPRESS_VERSION
					)
				);
				$wp_scripts->add( $handle, add_query_arg( 'nocache', $no_cache, $data['url'] ), $data['deps'], $data['ver'] );
			}

		}

		if ( $default_styles = $this->_get_styles() ) {

			foreach ( $default_styles as $handle => $data ) {
				if ( is_string( $data ) ) {
					$data = array( 'url' => $data );
				}

				$data = wp_parse_args(
					$data,
					array(
						'deps' => null,
						'ver'  => LEARNPRESS_VERSION
					)
				);
				$wp_styles->add( $handle, add_query_arg( 'nocache', $no_cache, $data['url'] ), $data['deps'], $data['ver'] );
			}

		}
		// admin

		//$scripts->add( 'learn-press-admin', $default_path . 'js/admin/admin' . $suffix . '.js', $deps, $ver, 1 );
		//$scripts->add( 'learn-press-utils', $default_path . 'js/admin/utils' . $suffix . '.js', $deps, $ver, 1 );

		/*
		$scripts->add( 'learn-press-admin-settings', $default_path . 'js/admin/settings' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-mb-question', $default_path . 'js/admin/meta-box-question' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-mb-course', $default_path . 'js/admin/meta-box-course' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-mb-quiz', $default_path . 'js/admin/meta-box-quiz' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-mb-order', $default_path . 'js/admin/meta-box-order' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-modal-search-items', $default_path . 'js/admin/modal-search-items' . $suffix . '.js', array( 'learn-press-global' ), $ver, 1 );
		$scripts->add( 'learn-press-order', $default_path . 'js/admin/meta-box-order' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-admin-tabs', $default_path . 'js/admin/admin-tabs' . $suffix . '.js', $deps, $ver, 1 );*/

		//$scripts->add( 'learn-press-select2', '/' . LP_WP_CONTENT . '/plugins/learnpress/inc/libraries/meta-box/js/select2/select2.min.js', $deps, $ver, 1 );
		//$scripts->add( 'learn-press-tipsy', $default_path . 'js/vendor/jquery-tipsy/jquery.tipsy.js' );
	}

	public function get_script_var_name( $handle ) {
		$handle = str_replace( array( '_', '-' ), ' ', $handle );
		$handle = ucwords( $handle );

		return 'lp' . str_replace( ' ', '', $handle ) . 'Settings';
	}

	public function localize_printed_scripts() {
		if ( ! ( $scripts_data = $this->_get_script_data() ) ) {
			return;
		}
		foreach ( $scripts_data as $handle => $data ) {
			wp_localize_script( $handle, $this->get_script_var_name( $handle ), $data );
		}
	}

	protected function _get_script_data() {
		return array();
	}

	/**
	 * Shortcut to plugin file url.
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function url( $file = '' ) {
		return LP_PLUGIN_URL . "assets/{$file}";
	}

	/**
	 * Get url with param nocache if debug mode is turn on.
	 *
	 * @param $url
	 *
	 * @return string
	 */
	protected function _get_url( $url ) {
		if ( $this->_cache ) {
			$url = add_query_arg( 'nocache', $this->_cache, $url );
		}

		return $url;
	}
}
