<?php

/**
 * Class LP_Addon
 */
class LP_Addon {
	/**
	 * Current version of addon.
	 *
	 * @var string
	 */
	public $version = null;

	/**
	 * Learnpress require addon version
	 * Case:
	 *
	 * @var array
	 */
	private $lp_require_addon_version = '4.0.0';

	/**
	 * Required version for current version of addon.
	 *
	 * @var string
	 */
	public $require_version = null;

	/**
	 * Path to addon.
	 *
	 * @var string
	 */
	public $plugin_file = null;

	/**
	 * Base addon.
	 *
	 * @var string
	 */
	public $plugin_base = null;

	/**
	 * Addon textdomain name.
	 *
	 * @var string
	 */
	public $text_domain = '';

	/**
	 * @var null
	 */
	protected $_valid = null;

	/**
	 * Singleton instance of the addon.
	 *
	 * @var array
	 */
	public static $instances = array();

	/**
	 * @var array
	 */
	protected static $_admin_notices = array();

	/**
	 * @var string
	 */
	protected $_template_path = '';

	protected static $on_activate_plugins = array();

	/**
	 * LP_Addon constructor.
	 */
	public function __construct() {

		$this->_define_constants();

		//      if ( ! $this->_check_version() ) {
		//          return;
		//      }

		/**
		 * After all addons lp config by key "Require_LP_Version" can remove hook
		 */
		//add_action( 'plugins_loaded', array( $this, 'check_require_version_lp' ), - 9 );

		$this->_includes();

		add_action( 'init', array( $this, 'init' ) );
	}

	public static function admin_errors() {
		if ( ! self::$_admin_notices ) {
			return;
		}

		foreach ( self::$_admin_notices as $notice ) {
			?>
			<div class="error"><p><?php echo $notice; ?></p></div>
			<?php
		}
	}

	public function _plugin_links( $links ) {
		if ( method_exists( $this, 'plugin_links' ) ) {
			$plugin_links = call_user_func( array( $this, 'plugin_links' ) );

			if ( is_callable( array( $this, 'plugin_links' ) ) && $plugin_links ) {
				$links = array_merge( $links, $plugin_links );
			}
		}

		return $links;
	}

	/**
	 * Init
	 */
	public function init() {
		//      if ( ! $this->_check_version() ) {
		//          return;
		//      }

		$this->load_text_domain();

		add_filter(
			"plugin_action_links_{$this->get_plugin_slug()}",
			array(
				$this,
				'_plugin_links',
			)
		);

		$this->_init_hooks();
		$this->_enqueue_assets();
	}

	/**
	 * Define add-on constants.
	 */
	protected function _define_constants() {

	}

	/**
	 * Includes add-on files.
	 */
	protected function _includes() {

	}

	/**
	 * Init add-on hooks.
	 */
	protected function _init_hooks() {

	}

	/**
	 * Enqueue scripts.
	 */
	protected function _enqueue_assets() {

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
	 * @deprecated 4.0.0
	 * @todo clean, remove code
	 */
	/*protected function _check_version() {
		if ( null === $this->_valid ) {
			$this->_valid = true;

			if ( $this->require_version ) {
				if ( version_compare( $this->require_version, LEARNPRESS_VERSION, '>' ) ) {
					add_action( 'admin_notices', array( $this, '_admin_notices' ) );

					// Deactivate plugin .
					deactivate_plugins( plugin_basename( $this->plugin_file ) );

					$this->_valid = false;
				}
			}
		}

		return $this->_valid;
	}*/

	/**
	 * Check required version Addons.
	 *
	 * @return bool
	 */
	public function check_require_version_addon(): bool {
		$flag = true;

		if ( version_compare( $this->lp_require_addon_version, $this->version, '>' ) ) {
			$flag = false;
		}

		if ( ! $flag ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_require_addon_version' ) );

			// Deactivate plugin .
			if ( ! empty( $this->plugin_base ) ) {
				deactivate_plugins( $this->plugin_base );
			}
		}

		return $flag;
	}

	/**
	 * Check required version LP on Addon.
	 * Should define require on each Addons by key "Require_LP_Version".
	 * After long time, when ready standard, need check not key "Require_LP_Version" will deactivate addon.
	 *
	 * @return bool
	 */
	public function check_require_version_lp(): bool {

		$flag = true;

		// If addon not set, return false.
		if ( empty( $this->require_version ) ) {
			$flag = false;
		}

		if ( version_compare( $this->require_version, LEARNPRESS_VERSION, '>' ) ) {
			$flag = false;
		}

		if ( ! $flag ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_require_lp_version' ) );

			// Deactivate plugin .
			if ( ! empty( $this->plugin_base ) ) {
				deactivate_plugins( plugin_basename( $this->plugin_base ) );
			}
		}

		return $flag;
	}

	public function admin_notice_require_addon_version() {
		?>
		<div class="notice notice-error">
			<p><?php echo( '<strong>LearnPress version ' . LEARNPRESS_VERSION . ' require ' . $this->get_name() . '</strong> version ' . $this->lp_require_addon_version . ' or higher' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Admin notices
	 */
	public function admin_notice_require_lp_version() {
		?>
		<div class="error">
			<p>
				<?php
				printf(
					__(
						'<strong>%1$s</strong> add-on version %2$s requires <strong>LearnPress</strong> version %3$s or higher %4$s',
						'learnpress'
					),
					esc_html( $this->get_name() ),
					esc_html( $this->version ),
					esc_html( $this->require_version ),
					'| Can addon invalid key Require_LP_Version'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * @return mixed
	 */
	public function get_name() {
		preg_match( '/([A-z].*)\/([A-z].*)/', $this->plugin_base, $match );

		$name_addon = '';
		if ( isset( $match[1] ) ) {
			$name_addon = $match[1];
		}

		return $name_addon;
	}

	/**
	 * Load text domain
	 */
	public function load_text_domain() {
		$plugin_path   = dirname( $this->plugin_file );
		$plugin_folder = basename( $plugin_path );
		$text_domain   = empty( $this->text_domain ) ? $plugin_folder : $this->text_domain;
		$locale        = apply_filters( 'plugin_locale', get_locale(), $plugin_folder );
		$domain_files  = array();

		if ( is_admin() ) {
			$domain_files[] = WP_LANG_DIR . "/{$plugin_folder}/{$plugin_folder}-admin-{$locale}.mo";
			$domain_files[] = WP_LANG_DIR . "/plugins/{$plugin_folder}-admin-{$locale}.mo";
		}

		$domain_files[] = WP_CONTENT_DIR . "/plugins/{$plugin_folder}/languages/{$plugin_folder}-{$locale}.mo";
		$domain_files[] = WP_LANG_DIR . "/{$plugin_folder}/{$plugin_folder}-{$locale}.mo";

		foreach ( $domain_files as $file ) {
			if ( ! file_exists( $file ) ) {
				continue;
			}

			load_textdomain( $text_domain, $file );
		}

		if ( $text_domain ) {
			load_plugin_textdomain( $text_domain, false, plugin_basename( $plugin_path ) . '/languages' );
		}
	}

	/**
	 * Load Addon
	 *
	 * @param        $instance
	 * @param        $path
	 * @param string $plugin_file
	 *
	 * @return void|mixed
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
			self::$_admin_notices['add-on-file-no-exists'] = sprintf(
				__( '%s plugin file does not exist.', 'learnpress' ),
				$path
			);

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
			self::$_admin_notices['add-on-class-no-exists'] = sprintf(
				__( '%s plugin class does not exist.', 'learnpress' ),
				$instance
			);

			return;
		}

		$addon_instance->plugin_file = $plugin_file;

		self::$instances[ $instance ] = $addon_instance;

		return $addon_instance;
	}

	public function get_plugin_url( $sub = '/' ) {
		return plugins_url( $sub, $this->plugin_file );
	}

	/**
	 * Get template path.
	 *
	 * @return string
	 */
	public function get_template_path() {
		if ( empty( $this->_template_path ) ) {
			$this->_template_path = learn_press_template_path() . '/addons/' . preg_replace(
				'!^learnpress-!',
				'',
				dirname( $this->get_plugin_slug() )
			);
		}

		return $this->_template_path;
	}

	/**
	 * Get content template of addon in theme or inside itself.
	 *
	 * @param string $template_name
	 * @param array  $args
	 */
	public function get_template( $template_name, $args = array() ) {
		learn_press_get_template(
			$template_name,
			$args,
			$this->get_template_path(),
			dirname( $this->plugin_file ) . '/templates/'
		);
	}

	/**
	 * Locate template of addon in theme or inside itself.
	 *
	 * @param string $template_name
	 *
	 * @return string
	 */
	public function locate_template( $template_name ) {
		return learn_press_locate_template(
			$template_name,
			$this->get_template_path(),
			dirname( $this->plugin_file ) . '/templates/'
		);
	}

	/**
	 * Output content of admin view file.
	 *
	 * @param string $view
	 * @param array  $args
	 *
	 * @since x.x.x
	 */
	public function admin_view( $view, $args = array() ) {
		$args['plugin_file'] = $this->plugin_file;
		learn_press_admin_view( $view, $args );
	}

	/**
	 * Get content of admin view file.
	 *
	 * @param string $view
	 * @param array  $args
	 *
	 * @return string
	 * @since x.x.x
	 */
	public function admin_view_content( $view, $args = array() ) {
		ob_start();
		$this->admin_view( $view, $args );

		return ob_get_clean();
	}

	/**
	 * @return mixed
	 */
	public static function instance() {
		$name = self::_get_called_class();
		if ( false === $name ) {
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

add_action( 'admin_notices', array( 'LP_Addon', 'admin_errors' ) );
