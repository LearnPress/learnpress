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
	/**
	 * Path file min
	 *
	 * @var string
	 */
	public static $_min_assets = '.min';
	/**
	 * Version file asset
	 *
	 * @var mixed|string
	 */
	public static $_version_assets = LEARNPRESS_VERSION;
	/**
	 * Path file
	 *
	 * @var string
	 */
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
			add_action( 'admin_print_scripts', array( $this, 'localize_printed_admin_scripts' ) );
			// Use for modal search items, modal search users when edit manual LP Order ue Vue js.
			add_action( 'admin_print_footer_scripts', array( $this, 'localize_printed_admin_scripts' ) );
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), $priority );
			add_action( 'wp_print_scripts', array( $this, 'localize_printed_scripts' ), $priority + 10 );
			//add_action( 'wp_print_footer_scripts', array( $this, 'localize_printed_scripts' ), $priority + 10 );
		}
	}

	abstract function load_scripts();

	/**
	 * Default scripts
	 *
	 * @return array
	 */
	protected function _get_scripts(): array {
		return array();
	}

	/**
	 * Default styles
	 *
	 * @return array
	 */
	protected function _get_styles(): array {
		return array();
	}

	/**
	 * Register/Enqueue script
	 *
	 * @param string $page_current
	 * @author tungnx
	 * @since 4.0.0
	 * @version 1.0.1
	 */
	protected function handle_js( string $page_current = '' ) {
		$scripts = $this->_get_scripts();
		/**
		 * @var LP_Asset_Key[] $scripts
		 */
		foreach ( $scripts as $handle => $script ) {
			if ( ! $script instanceof LP_Asset_Key ) {
				continue;
			}

			// For version addon.
			if ( ! LP_Debug::is_debug() && ! empty( $script->_version ) ) {
				self::$_version_assets = $script->_version;
			}
			// End

			wp_register_script( $handle, $script->_url, $script->_deps, self::$_version_assets, $script->_in_footer );
			// Add strategy for script defer/async. @since 4.2.5.5
			foreach ( $script->_strategy as $key => $value ) {
				wp_script_add_data( $handle, $key, $value );
			}

			if ( ! $script->_only_register ) {
				$can_load_js = $this->check_can_load_asset( $handle, $page_current, $script->_screens, $script->_exclude_screens );

				if ( $can_load_js ) {
					wp_enqueue_script( $handle );
				}
			}
		}

		/**
		 * Set translate on file js of folder js/dist
		 * Path translate of a string on file ".pot" if have must map to js/dist
		 */
		wp_set_script_translations( 'lp-quiz', 'learnpress' );
		wp_set_script_translations( 'lp-admin', 'learnpress' );
	}

	/**
	 * Register/Enqueue style
	 *
	 * @param string $page_current
	 * @author tungnx
	 * @since 4.1.3
	 * @version 1.0.0
	 */
	protected function handle_style( string $page_current = '' ) {
		$styles = $this->_get_styles();
		if ( $styles ) {
			/**
			 * @var LP_Asset_Key[] $style
			 */
			foreach ( $styles as $handle => $style ) {
				if ( ! $style instanceof LP_Asset_Key ) {
					continue;
				}

				// For version addon.
				if ( ! LP_Debug::is_debug() && ! empty( $style->_version ) ) {
					self::$_version_assets = $style->_version;
				}
				// End

				wp_register_style( $handle, $style->_url, $style->_deps, self::$_version_assets );

				if ( ! $style->_only_register ) {
					$can_load_style = $this->check_can_load_asset( $handle, $page_current, $style->_screens, $style->_exclude_screens );

					if ( $can_load_style ) {
						wp_enqueue_style( $handle );
					}
				}
			}
		}
	}

	/**
	 * Check file assets can load on pages
	 *
	 * @param string $handle
	 * @param string $page_current
	 * @param array $include_screens
	 * @param array $exclude_screens
	 * @author tungnx
	 * @since 4.1.3
	 * @version 1.0.0
	 *
	 * @return bool
	 */
	protected function check_can_load_asset( string $handle, string $page_current, array $include_screens, array $exclude_screens ): bool {
		$can_load = false;

		if ( ! empty( $include_screens ) ) {
			if ( in_array( $page_current, $include_screens ) ) {
				$can_load = true;
			}
		} elseif ( ! empty( $exclude_screens ) ) {
			if ( ! in_array( $page_current, $exclude_screens ) ) {
				$can_load = true;
			}
		} else {
			$can_load = true;
		}

		$is_on = 'admin';
		if ( ! is_admin() ) {
			$is_on = 'frontend';
		}

		return apply_filters(
			'learnpress/' . $is_on . '/can-load-assets/' . $handle,
			$can_load,
			$page_current,
			$include_screens
		);
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
	 * @param $handle
	 *
	 * @return string
	 */
	public function get_script_var_name( $handle ) {
		$handle = str_replace( array( 'learn-press', 'lp', '_', '-' ), ' ', $handle );
		$handle = ucwords( $handle );

		return 'lp' . str_replace( ' ', '', $handle ) . 'Settings';
	}

	public function localize_printed_scripts( $side = '' ) {
		$scripts_data = $this->_get_script_data();

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
	 * Shortcut to Addon file url.
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function url( string $file = '' ): string {
		return LP_PLUGIN_URL . "assets/{$file}";
	}

	/*public static function add_param() {

	}*/
}
