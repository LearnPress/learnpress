<?php

/**
 * Class LP_Abstract_Assets
 *
 * Abstract class for managing assets
 */
abstract class LP_Abstract_Assets {

	protected $_cache = '';

	/**
	 * @var array
	 */
	protected $_scripts = array();

	/**
	 * @var array
	 */
	protected $_styles = array();

	/**
	 * @var array
	 */
	protected $_script_data = array();

	public static $_min_assets = '.min';
	public static $_version_assets = LEARNPRESS_VERSION;
	public static $_folder_source = '';

	/**
	 * LP_Abstract_Assets constructor.
	 */
	protected function __construct() {
		$priority = 1000;

		if ( LP_Debug::is_debug() ) {
			self::$_min_assets     = '';
			self::$_version_assets = uniqid();
			self::$_folder_source  = 'src/';
		}

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'localize_printed_admin_scripts' ) );
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), $priority );
			add_action( 'wp_print_scripts', array( $this, 'localize_printed_scripts' ), $priority + 10 );
			add_action( 'wp_print_footer_scripts', array( $this, 'localize_printed_scripts' ), $priority + 10 );
		}
	}

	abstract function load_scripts();

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
	 * Register style
	 *
	 * @param        $handle
	 * @param        $src
	 * @param array $deps
	 * @param bool $ver
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
	 * @param bool $ver
	 * @param bool $in_footer
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
	 * @param array $deps
	 * @param bool $ver
	 * @param string $media
	 */
	public function enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		$this->register_style( $handle, $src, $deps, $ver, $media );
		if ( did_action( 'init' ) || did_action( 'admin_enqueue_scripts' ) || did_action( 'wp_enqueue_scripts' ) || did_action( 'login_enqueue_scripts' ) ) {
			wp_enqueue_style( $handle, $src, $deps, $ver, $media );
		}
	}

	/**
	 * Enqueue script
	 *
	 * @param       $handle
	 * @param       $src
	 * @param array $deps
	 * @param bool $ver
	 * @param bool $in_footer
	 */
	public function enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
		$this->register_script( $handle, $src, $deps, $ver, $in_footer );
		if ( did_action( 'init' ) || did_action( 'admin_enqueue_scripts' ) || did_action( 'wp_enqueue_scripts' ) || did_action( 'login_enqueue_scripts' ) ) {
			wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
		}
	}

	public function add_script_data( $handle, $key_or_array, $value = '' ) {
		if ( empty( $this->_script_data[ $handle ] ) ) {
			$this->_script_data[ $handle ] = array();
		}

		if ( func_num_args() == 2 && is_array( $key_or_array ) ) {
			$this->_script_data[ $handle ] = LP_Helper::array_merge_recursive( $this->_script_data[ $handle ], $key_or_array );
		} else {
			$this->_script_data[ $handle ][ $key_or_array ] = $value;
		}
	}

	protected function _get_wp_styles() {
		global $wp_styles;

		if ( empty( $wp_styles ) ) {
			$wp_styles = new WP_Styles();
		}

		return $wp_styles;
	}

	protected function _get_wp_scripts() {
		global $wp_scripts;

		if ( empty( $wp_scripts ) ) {
			$wp_scripts = new WP_Scripts();
		}

		return $wp_scripts;
	}

	/**
	 * Register scripts and styles for admin.
	 *
	 * @editor tungnx
	 * @reason not use
	 * @deprecated 3.2.8
	 */
	/*protected function _register_scripts() {
		$wp_scripts = $this->_get_wp_scripts();
		$wp_styles  = $this->_get_wp_styles();

		// No use cache if debug mode is turn on
		$no_cache = '';
		if ( learn_press_is_debug() ) {
			$no_cache = microtime( true );
		}

		$default_scripts = $this->_get_scripts();
		if ( $default_scripts ) {

			foreach ( $default_scripts as $handle => $data ) {
				if ( is_string( $data ) ) {
					$data = array( 'url' => $data );
				}

				if ( empty( $data['url'] ) ) {
					continue;
				}

				$data = wp_parse_args(
					$data,
					array(
						'deps'      => null,
						'ver'       => LEARNPRESS_VERSION,
						'in_footer' => false,
					)
				);

				wp_register_script(
					$handle,
					$no_cache ? add_query_arg( 'nocache', $no_cache, $data['url'] ) : $data['url'],
					$data['deps'],
					$data['ver'],
					$data['in_footer']
				);
			}
		}

		$default_styles = $this->_get_styles();
		if ( $default_styles ) {

			foreach ( $default_styles as $handle => $data ) {
				if ( is_string( $data ) ) {
					$data = array( 'url' => $data );
				}

				$data = wp_parse_args(
					$data,
					array(
						'deps' => null,
						'ver'  => LEARNPRESS_VERSION,
					)
				);
				$wp_styles->add( $handle, $no_cache ? add_query_arg( 'nocache', $no_cache, $data['url'] ) : $data['url'], $data['deps'], $data['ver'] );
			}
		}
	}*/

	/**
	 * @param $handle
	 *
	 * @return string
	 */
	public function get_script_var_name( $handle ) {
		$handle = str_replace( array( 'learn-press', 'lp', '_', '-' ), ' ', $handle );
		$handle = ucwords( $handle );

		return 'lp' . str_replace( ' ', '', $handle ) . 'Settings';
	}

	public function _get_admin_script_data() {
		return false;
	}

	public function localize_printed_scripts( $side = '' ) {
		$scripts_data = ( $side == 'admin' ) ? $this->_get_script_data() : $this->_get_script_data();

		if ( is_array( $scripts_data ) && is_array( $this->_script_data ) ) {
			$scripts_data = LP_Helper::array_merge_recursive( $scripts_data, $this->_script_data );
		} elseif ( is_array( $this->_script_data ) ) {
			$scripts_data = $this->_script_data;
		}

		if ( ! $scripts_data ) {
			return;
		}

		global $wp_scripts;

		if ( ! $wp_scripts ) {
			$wp_scripts = new WP_Scripts();
		}

		foreach ( $scripts_data as $handle => $data ) {
			$data = apply_filters( 'learn-press/script-data', $data, $handle );
			wp_localize_script( $handle, $this->get_script_var_name( $handle ), $data );

			// comment by tungnx
			// Edit: Use in certificate - Nhamdv.
			if ( isset( $wp_scripts->registered[ $handle ] ) ) {
				if ( isset( $wp_scripts->registered[ $handle ]->extra['data'] ) ) {
					if ( $wp_scripts->registered[ $handle ]->extra['data'] ) {
						$data = $wp_scripts->registered[ $handle ]->extra['data'];
						$data = preg_replace_callback( '~:"(([0-9]+)([.,]?)([0-9]?)|true|false)"~', array( $this, '_valid_json_number' ), $data );

						$wp_scripts->registered[ $handle ]->extra['data'] = $data;
					}
				}
			}

			if ( is_admin() ) {
				$wp_scripts->print_extra_script( $handle );
			}
		}

	}

	public function localize_printed_admin_scripts() {
		$this->localize_printed_scripts( 'admin' );
	}

	protected function _valid_json_number( $m ) {
		return str_replace( array( ':"', '"' ), array( ':', '' ), $m[0] );
	}

	protected function _get_script_data() {
		return array();
	}

	public function add_localize( $handle, $key_or_array, $value = '' ) {
		if ( empty( $this->_script_data[ $handle ] ) ) {
			$this->_script_data[ $handle ] = array();
		}
		if ( is_array( $key_or_array ) ) {
			$this->_script_data[ $handle ] = array_merge( $this->_script_data[ $handle ], $key_or_array );
		} else {
			$this->_script_data[ $handle ][ $key_or_array ] = $value;
		}
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

	public function get_compressible_assets() {
		return array();
	}

	public static function add_param() {

	}

	public static function add_var() {

	}

	public static function add_script_tag() {

	}
}
