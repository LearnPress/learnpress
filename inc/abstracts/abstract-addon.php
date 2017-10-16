<?php

/**
 * Class LP_Addon
 */
class LP_Addon {
	/**
	 * @var null
	 */
	public $version = null;

	/**
	 * @var null
	 */
	public $require_version = null;

	/**
	 * @var null
	 */
	public $plugin_file = null;

	/**
	 * @var string
	 */
	public $text_domain = '';

	/**
	 * @var null
	 */
	protected $_valid = null;

	/**
	 * @var array
	 */
	public static $instances = array();

	/**
	 * LP_Addon constructor.
	 */
	public function __construct() {
		//add_action( 'plugins_loaded', array( $this, 'check_version' ) );
		add_action( 'init', array( $this, 'init' ) );


	}

	public function plugin_links( $links ) {
		return $links;
	}

	/**
	 * Init
	 */
	public function init() {
		if ( $this->_check_version() ) {
			$this->load_text_domain();
		}

		add_filter( "plugin_action_links_{$this->get_plugin_slug()}", array(
			$this,
			'plugin_links'
		) );
	}

	/**
     * Get plugin slug from plugin file.
     *
	 * @return bool|string
	 */
	public function get_plugin_slug() {
		if ( empty( $this->plugin_file ) ) {
			return false;
		}

		$dir      = dirname( $this->plugin_file );
		$basename = basename( $dir );

		return $basename . '/' . basename( $this->plugin_file );
	}

	/**
	 * Check required version of LP.
	 *
	 * @return bool|null
	 */
	protected function _check_version() {
		if ( $this->_valid === null ) {
			$this->_valid = true;
			if ( $this->require_version ) {
				if ( version_compare( $this->require_version, LEARNPRESS_VERSION, '>' ) ) {
					add_action( 'admin_notices', array( $this, 'admin_notices' ) );

					$this->_valid = false;
				}
			}
		}

		return $this->_valid;
	}

	/**
	 * Admin notices
	 */
	public function admin_notices() {
		?>
        <div class="error">
            <p><?php printf( __( '<strong>%s</strong> addon version %s requires <strong>LearnPress</strong> version %s or higher', 'learnpress' ), $this->get_name(), $this->version, $this->require_version ); ?></p>
        </div>
		<?php
	}

	/**
	 * @return mixed
	 */
	public function get_name() {
		return str_replace( '_', ' ', str_replace( 'LP_Addon_', '', get_class( $this ) ) );
	}

	/**
	 * Load text domain
	 */
	public function load_text_domain() {
		$plugin_path   = dirname( $this->plugin_file );
		$plugin_folder = basename( $plugin_path );
		$text_domain   = empty( $this->text_domain ) ? $plugin_folder : $this->text_domain;
		$locale        = apply_filters( 'plugin_locale', get_locale(), $plugin_folder );

		if ( is_admin() ) {
			load_textdomain( $text_domain, WP_LANG_DIR . "/{$plugin_folder}/{$plugin_folder}-admin-{$locale}.mo" );
			load_textdomain( $text_domain, WP_LANG_DIR . "/plugins/{$plugin_folder}-admin-{$locale}.mo" );
		}

		load_textdomain( $text_domain, WP_LANG_DIR . "/{$plugin_folder}/{$plugin_folder}-{$locale}.mo" );

		$mo = WP_CONTENT_DIR . "/plugins/{$plugin_folder}/languages/{$plugin_folder}-{$locale}.mo";
		load_textdomain( $text_domain, $mo );
		load_plugin_textdomain( $text_domain, false, plugin_basename( $plugin_path ) . "/languages" );
	}

	/**
	 * @param        $instance
	 * @param        $path
	 * @param string $plugin_file
	 */
	public static function load( $instance, $path, $plugin_file = '' ) {
		$plugin_folder = '';
		if ( $plugin_file ) {
			$plugin_folder = dirname( $plugin_file );
		}
		if ( $plugin_folder ) {
			$path = "{$plugin_folder}/$path";
		}

		if ( ! file_exists( $path ) ) {
			return;
		}

		include_once $path;
		$addon_instance = null;

		if ( class_exists( $instance ) ) {
			$addon_instance = null;
			if ( is_callable( array( $instance, 'instance' ) ) ) {
				$addon_instance = call_user_func( array( $instance, 'instance' ) );
			} else {
				$addon_instance = new $instance();
			}
		}

		if ( ! $addon_instance ) {
			return;
		}

		$addon_instance->plugin_file = $plugin_file;

		self::$instances[ $instance ] = $addon_instance;

		LP_Multi_Language::load_plugin_text_domain( $plugin_file );
	}

	/**
	 * @return mixed
	 */
	public static function instance() {

		if ( false === ( $name = self::_get_called_class() ) ) {
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
