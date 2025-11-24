<?php

use LearnPress\Helpers\Template;

/**
 * Class LP_Addon
 */
class LP_Addon {
	/**
	 * Current version of addon.
	 *
	 * @var string
	 */
	public $version = 0;
	/**
	 * LearnPress require addon version
	 *
	 * @var array
	 */
	private $lp_require_addon_version = '4.0.0';
	/**
	 * Required version for current version of addon.
	 *
	 * @var string
	 */
	public $require_version = 0;
	/**
	 * Path to root file of addon.
	 *
	 * @var string
	 */
	public $plugin_file = '';
	/**
	 * @var string folder path root of addon.
	 */
	public $plugin_folder_path = '';
	/**
	 * Root folder name addon.
	 *
	 * @var string
	 */
	public $plugin_folder_name = '';
	/**
	 * Plugin base addon.
	 *
	 * @var string contain root folder path and root file of addon
	 */
	public $plugin_base = '';
	/**
	 * Base name addon.
	 *
	 * @var string root file name php of addon
	 */
	public $plugin_base_name = '';
	/**
	 * Addon text-domain name.
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
		$this->plugin_base        = plugin_basename( $this->plugin_file );
		$this->plugin_base_name   = basename( $this->plugin_file );
		$this->plugin_folder_path = dirname( $this->plugin_file );
		$this->plugin_folder_name = str_replace( array( '/', $this->plugin_base_name ), '', $this->plugin_base );
		$this->_define_constants();
		$this->_includes();
		$this->load_text_domain();
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
			<p>
				<?php
				printf(
					wp_kses_post( __( '<strong>LearnPress version %1$s require %2$s</strong> version %3$s or higher', 'learnpress' ) ),
					esc_html( LEARNPRESS_VERSION ),
					esc_html( $this->get_name() ),
					esc_html( $this->lp_require_addon_version )
				);
				?>
			</p>
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
					wp_kses_post(
						__(
							'<strong>%1$s</strong> add-on version %2$s requires <strong>LearnPress</strong> version %3$s or higher %4$s',
							'learnpress'
						)
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
		if ( empty( $this->plugin_file ) ) {
			return;
		}

		$text_domain = empty( $this->text_domain ) ? $this->plugin_folder_name : $this->text_domain;
		if ( $text_domain ) {
			load_plugin_textdomain( $text_domain, false, $this->plugin_folder_name . '/languages' );
		}
	}

	/**
	 * Load Addon
	 *
	 * @param string $instance
	 * @param string $path
	 * @param string $plugin_file
	 *
	 * @return void|mixed
	 */
	public static function load( string $instance = '', string $path = '', string $plugin_file = '' ) {
		$plugin_folder = '';

		if ( $plugin_file ) {
			$plugin_folder = dirname( $plugin_file );
		}

		if ( $plugin_folder ) {
			$path = "$plugin_folder/$path";
		}

		if ( ! file_exists( $path ) ) {
			error_log(
				sprintf(
					__( '%s plugin file does not exist.', 'learnpress' ),
					$path
				)
			);

			return;
		}

		include_once $path;
		$addon_instance = null;

		if ( ! array_key_exists( $instance, self::$instances ) ) {
			if ( class_exists( $instance ) ) {
				$addon_instance = new $instance();
			}

			if ( ! $addon_instance ) {
				error_log(
					sprintf(
						__( '%s plugin class does not exist.', 'learnpress' ),
						$instance
					)
				);

				return;
			}

			$addon_instance->plugin_file = $plugin_file;

			self::$instances[ $instance ] = $addon_instance;
		}

		return self::$instances[ $instance ];
	}

	public function get_plugin_url( $sub = '/' ) {
		return plugins_url( $sub, $this->plugin_file );
	}

	/**
	 * Get content template of addon.
	 *
	 * @param string $template_name
	 * @param array $args
	 * @param bool $include
	 *
	 * @since 3.0.0
	 * @version 1.0.1
	 */
	public function get_template( string $template_name = '', array $args = [], bool $include = true ) {
		// Check path file not extension php, will add extension .php
		if ( ! preg_match( '/\.php$/', $template_name ) ) {
			$template_name .= '.php';
		}
		$default_path          = $this->plugin_folder_path . "/templates/$template_name";
		$folder_name_rewrite   = learn_press_template_path();
		$from_child_theme_path = sprintf(
			'%s/%s/%s/%s/%s',
			get_stylesheet_directory(),
			$folder_name_rewrite,
			'addons',
			str_replace( 'learnpress-', '', $this->plugin_folder_name ),
			$template_name
		);
		$from_theme_path       = sprintf(
			'%s/%s/%s/%s/%s',
			get_template_directory(),
			$folder_name_rewrite,
			'addons',
			str_replace( 'learnpress-', '', $this->plugin_folder_name ),
			$template_name
		);

		$path_load = $default_path;
		if ( file_exists( $from_child_theme_path ) ) {
			$path_load = $from_child_theme_path;
		} elseif ( file_exists( $from_theme_path ) ) {
			$path_load = $from_theme_path;
		}
		Template::instance( $include )->get_template( $path_load, $args );
	}

	/**
	 * Get content template of addon.
	 *
	 * @param string $template_name
	 * @param mixed $args
	 *
	 * @since 4.2.1
	 * @version 1.0.1
	 */
	public function get_admin_template( string $template_name = '', $args = [] ) {
		if ( ! preg_match( '/\.php$/', $template_name ) ) {
			$template_name .= '.php';
		}
		$template_path = "{$this->plugin_folder_path}/inc/admin/views/$template_name";
		Template::instance()->get_template( $template_path, $args );
	}

	/**
	 * Output content of admin view file.
	 *
	 * @param string $view
	 * @param array $args
	 *
	 * @since x.x.x
	 */
	public function admin_view( $view, $args = array() ) {
		$args['plugin_file'] = $this->plugin_file;
		learn_press_admin_view( $view, $args );
	}

	/**
	 * @return mixed
	 * @deprecated 4.2.0
	 * using on the addons: co-instructor(4.0.1), wishlist(4.0.4), announcements(4.0.3), course-review, coming-soon ...
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
	 * @deprecated 4.2.0
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
