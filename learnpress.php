<?php
/**
 * Plugin Name: LearnPress
 * Plugin URI: http://thimpress.com/learnpress
 * Description: LearnPress is a WordPress complete solution for creating a Learning Management System (LMS). It can help you to create courses, lessons and quizzes.
 * Author: ThimPress
 * Version: 4.1.2
 * Author URI: http://thimpress.com
 * Requires at least: 5.6
 * Tested up to: 5.7
 * Requires PHP: 7.0
 * Text Domain: learnpress
 * Domain Path: /languages/
 *
 * @package LearnPress
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! defined( 'LP_PLUGIN_FILE' ) ) {
	define( 'LP_PLUGIN_FILE', __FILE__ );
	require_once dirname( __FILE__ ) . '/inc/lp-constants.php';
}

if ( ! class_exists( 'LearnPress' ) ) {

	/**
	 * Class LearnPress
	 *
	 * Version 3.0.0
	 */
	class LearnPress {

		/**
		 * Current version of the plugin
		 *
		 * @var string
		 */
		public $version = LEARNPRESS_VERSION;

		/**
		 * The single instance of the class
		 *
		 * @var LearnPress object
		 */
		private static $_instance = null;

		/**
		 * Store the session class
		 *
		 * @var LP_Session_Handler
		 */
		public $session = null;

		/**
		 * @var LP_Profile
		 */
		public $profile = null;

		/**
		 * @var LP_Cart object
		 */
		public $cart = false;

		/**
		 * @var LP_Settings
		 */
		public $settings = null;

		/**
		 * @var null
		 */
		public $schedule = null;

		/**
		 * @var array
		 */
		public $query_vars = array();

		/**
		 * Table prefixes
		 *
		 * @var array
		 */
		protected $_table_prefixes = array();

		/**
		 * @var null
		 */
		public $query = null;

		/**
		 * @var array
		 */
		public $global = array();

		/**
		 * Manage all processes run in background.
		 *
		 * @var LP_Abstract_Background_Process[]
		 */
		public $backgrounds = array();

		/**
		 * @var LP_Admin_Notice
		 *
		 * @since 3.2.6
		 */
		public $admin_notices = null;

		/**
		 * @var LP_Template
		 */
		public $template = null;

		/**
		 * @var LP_Utils
		 */
		public $utils = null;

		/**
		 * @var LP_Core_API
		 */
		public $api = null;

		/**
		 * @var LP_Admin_Core_API
		 */
		public $admin_api = null;

		/**
		 * @var string
		 */
		public $thim_core_version_require = '2.0.0';

		/**
		 *
		 */
		public $theme_support = null;

		/**
		 * LearnPress constructor.
		 */
		protected function __construct() {
			if ( self::$_instance ) {
				return;
			}
			self::$_instance = $this;

			// Define constant .
			$this->plugin_defines();

			// define table prefixes .
			$this->define_tables();

			// include files .
			$this->includes();

			// hooks .
			$this->init_hooks();
		}

		/**
		 * Define constant.
		 */
		protected function plugin_defines() {
			if ( ! defined( 'LP_PLUGIN_BASENAME' ) ) {
				define( 'LP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			}
		}

		/**
		 * Defines database table names.
		 */
		public function define_tables() {
			global $wpdb;

			$tables = array(
				'sessions',
				'sections',
				'section_items',
				'user_items',
				'user_itemmeta',
				'user_item_results',
				'order_items',
				'order_itemmeta',
				'quiz_questions',
				'question_answers',
				'question_answermeta',
				'review_logs',
			);

			foreach ( $tables as $short_name ) {
				$table_name                                    = $wpdb->prefix . LP_TABLE_PREFIX . $short_name;
				$this->_table_prefixes[ 'tbl_' . $short_name ] = $table_name;

				$backward_key          = 'learnpress_' . $short_name;
				$wpdb->{$backward_key} = $table_name;
			}
		}

		/**
		 * Includes needed files.
		 */
		public function includes() {
			require_once 'inc/class-lp-file-system.php';
			require_once 'inc/class-lp-helper.php';
			require_once 'inc/class-lp-settings.php';
			require_once 'inc/class-lp-factory.php';
			require_once 'inc/class-lp-datetime.php';
			require_once 'inc/class-lp-hard-cache.php';
			require_once 'inc/interfaces/interface-curd.php';
			require_once 'inc/abstracts/abstract-array-access.php';
			require_once 'inc/abstracts/abstract-object-data.php';
			require_once 'inc/abstracts/abstract-post-data.php';
			require_once 'inc/abstracts/abstract-assets.php';
			require_once 'inc/abstracts/abstract-object-query.php';
			require_once 'inc/class-lp-course-query.php';
			require_once 'inc/class-lp-utils.php';
			require_once 'inc/abstracts/abstract-addon.php';
			require_once 'inc/class-lp-thumbnail-helper.php';
			require_once 'inc/cache.php';
			require_once 'inc/class-lp-asset-key.php';

			// LP Cache
			require_once 'inc/cache/class-lp-cache.php';

			// Abstract Metabox.
			include_once 'inc/admin/views/meta-boxes/class-lp-meta-box.php';

			// Background processes .
			require_once 'inc/abstracts/abstract-background-process.php';

			// Filter query .
			require_once 'inc/filters/class-lp-filter.php';
			require_once 'inc/filters/class-lp-post-type-filter.php';
			require_once 'inc/filters/class-lp-course-filter.php';
			require_once 'inc/filters/class-lp-question-filter.php';
			require_once 'inc/filters/class-lp-user-items-filter.php';

			// Query Database .
			require_once 'inc/databases/class-lp-db.php';
			require_once 'inc/databases/class-lp-course-db.php';
			require_once 'inc/databases/class-lp-lesson-db.php';
			require_once 'inc/databases/class-lp-section-db.php';
			require_once 'inc/databases/class-lp-quiz-db.php';
			require_once 'inc/databases/class-lp-sessions-db.php';
			require_once 'inc/databases/class-lp-question-db.php';
			require_once 'inc/databases/class-lp-user-items-db.php';
			require_once 'inc/databases/class-lp-user-item-results-db.php';

			// curds .
			require_once 'inc/curds/class-lp-helper-curd.php';
			require_once 'inc/curds/class-lp-course-curd.php';
			require_once 'inc/curds/class-lp-section-curd.php';
			require_once 'inc/curds/class-lp-lesson-curd.php';
			require_once 'inc/curds/class-lp-quiz-curd.php';
			require_once 'inc/curds/class-lp-question-curd.php';
			require_once 'inc/curds/class-lp-order-curd.php';
			require_once 'inc/curds/class-lp-user-curd.php';
			require_once 'inc/curds/class-lp-user-item-curd.php';

			require_once 'inc/class-lp-backward-plugins.php';
			require_once 'inc/class-lp-debug.php';
			require_once 'inc/class-lp-global.php';
			require_once 'inc/admin/meta-box/class-lp-meta-box-helper.php';
			require_once 'inc/course/class-lp-course-item.php';
			require_once 'inc/course/class-lp-course-section.php';
			require_once 'inc/course/class-lp-course-no-required-enroll.php';
			require_once 'inc/user-item/class-lp-user-item.php';
			require_once 'inc/user-item/class-lp-user-item-course.php';

			require_once 'inc/lp-deprecated.php'; // Will remove if Eduma and guest update all 4.0.0
			require_once 'inc/lp-core-functions.php';
			require_once 'inc/class-lp-autoloader.php';

			if ( get_option( 'learn_press_status' ) !== 'installed' ) {
				require_once 'inc/class-lp-install.php';
			}

			require_once 'inc/lp-webhooks.php';
			require_once 'inc/class-lp-request-handler.php';
			require_once 'inc/abstract-settings.php';
			require_once 'inc/admin/helpers/class-lp-plugins-helper.php';
			require_once 'inc/class-lp-rest-response.php';

			include_once 'inc/admin/class-lp-updater.php';

			if ( is_admin() ) {
				require_once 'inc/admin/meta-box/class-lp-meta-box-helper.php';
				require_once 'inc/admin/class-lp-admin-notice.php';
				require_once 'inc/admin/class-lp-admin.php';
				require_once 'inc/admin/settings/abstract-settings-page.php';
			}

			if ( ! is_admin() ) {
				require_once 'inc/class-lp-assets.php';
				require_once 'inc/course/class-model-user-can-view-course-item.php';
			}

			include_once 'inc/models/steps/class-lp-group-step.php';
			include_once 'inc/models/steps/class-lp-step.php';

			require_once 'inc/class-lp-repair-database.php';
			require_once 'inc/question/class-lp-question.php';

			// Register custom-post-type and taxonomies .
			require_once 'inc/custom-post-types/abstract.php';
			require_once 'inc/custom-post-types/course.php';
			require_once 'inc/custom-post-types/lesson.php';
			require_once 'inc/custom-post-types/quiz.php';
			require_once 'inc/custom-post-types/question.php';
			require_once 'inc/custom-post-types/order.php';

			if ( defined( 'LP_USE_ATTRIBUTES' ) && LP_USE_ATTRIBUTES ) {
				require_once 'inc/attributes/lp-attributes-functions.php';
			}

			require_once 'inc/course/lp-course-functions.php';
			require_once 'inc/course/abstract-course.php';
			require_once 'inc/course/class-lp-course.php';
			require_once 'inc/course/class-lp-course-utils.php';
			require_once 'inc/quiz/lp-quiz-functions.php';
			require_once 'inc/quiz/class-lp-quiz.php';
			require_once 'inc/lesson/lp-lesson-functions.php';
			require_once 'inc/order/lp-order-functions.php';
			require_once 'inc/order/class-lp-order.php';
			require_once 'inc/class-lp-gdpr.php';

			// user API
			require_once 'inc/user/lp-user-functions.php';
			require_once 'inc/user/class-lp-user-factory.php';
			require_once 'inc/user/abstract-lp-user.php';
			require_once 'inc/user/class-lp-user.php';
			require_once 'inc/user/class-lp-profile.php';
			require_once 'inc/user-item/class-lp-user-item.php';
			require_once 'inc/user-item/class-lp-user-item-course.php';
			require_once 'inc/user-item/class-lp-user-item-quiz.php';
			require_once 'inc/user-item/class-lp-quiz-results.php';
			require_once 'inc/class-lp-session-handler.php';

			if ( ! is_admin() ) {
				require_once 'inc/class-lp-shortcodes.php';
			}

			// include template functions .
			require_once 'inc/lp-template-functions.php';
			require_once 'inc/templates/abstract-template.php';
			require_once 'inc/class-lp-template.php';

			require_once 'inc/cart/class-lp-cart.php';
			require_once 'inc/cart/lp-cart-functions.php';
			require_once 'inc/gateways/class-lp-gateway-abstract.php';
			require_once 'inc/gateways/class-lp-gateways.php';
			require_once 'inc/admin/class-lp-admin-ajax.php';

			if ( ! is_admin() ) {
				require_once 'inc/class-lp-ajax.php';
			}

			require_once 'inc/class-lp-multi-language.php';
			require_once 'inc/class-lp-page-controller.php';
			require_once 'inc/class-lp-schedules.php';
			//require_once 'inc/class-lp-preview-course.php';

			require_once 'inc/class-lp-widget.php';
			require_once 'inc/lp-widget-functions.php';

			// Handle steps.
			require_once 'inc/handle-steps/class-lp-handle-steps.php';
			require_once 'inc/handle-steps/class-lp-handle-upgrade-db-steps.php';

			/**
			 * REST APIs
			 *
			 * @since 3.2.6
			 */
			require_once 'inc/abstracts/abstract-rest-api.php';
			require_once 'inc/abstracts/abstract-rest-controller.php';
			require_once 'inc/rest-api/class-lp-core-api.php';
			require_once 'inc/rest-api/class-lp-admin-core-api.php';

			include_once 'inc/theme-support/class-theme-support-base.php';
			include_once 'inc/class-lp-theme-support.php';

			/** Jwt */
			include_once 'inc/jwt/class-jwt-auth.php';

			if ( file_exists( LP_PLUGIN_PATH . '/local-debug.php' ) ) {
				include_once 'local-debug.php';
			}

			$GLOBALS['lp_query'] = $this->query = new LP_Query();
		}

		/**
		 * Initial common hooks
		 */
		public function init_hooks() {
			if ( 0 !== strcmp( LP_PLUGIN_BASENAME, 'learnpress/learnpress.php' ) ) {
				add_action( 'admin_notices', array( $this, 'error' ) );
			}

			add_action( 'activate_' . LP_PLUGIN_BASENAME, array( $this, 'on_activate' ) );
			add_action( 'deactivate_' . LP_PLUGIN_BASENAME, array( $this, 'on_deactivate' ) );

			add_action( 'wp_loaded', array( $this, 'wp_loaded' ), 20 );
			add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
			add_action( 'plugins_loaded', array( $this, 'plugin_loaded' ), - 10 );
			add_action( 'init', array( $this, 'wp_init' ), 10 );

			// Check require version thim-core.
			add_action( 'before_thim_core_init', array( $this, 'check_thim_core_version_require' ) );
		}

		public function error() {
			?>
			<div class="error">
				<p>
					<?php
					printf(
						__(
							'LearnPress plugin base directory must be <strong>learnpress/learnpres.php</strong> (case sensitive) to ensure all functions work properly and fully operational (currently <strong>%s</strong>)',
							'learnpress'
						),
						LP_PLUGIN_BASENAME
					);
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Maybe flush rewrite rules
		 */
		public function wp_init() {
			if ( LP()->session->flush_rewrite_rules ) {
				flush_rewrite_rules();
				unset( LP()->session->flush_rewrite_rules );
			}
		}

		/**
		 * Get base name of plugin from file.
		 *
		 * @return string
		 */
		//      private function plugin_basename() {
		//          return learn_press_plugin_basename( __FILE__ );
		//      }

		/**
		 * Magic function to get Learnpress data.
		 *
		 * @param $key
		 *
		 * @return bool|LP_Checkout|LP_Course|LP_Emails|LP_User|LP_User_Guest|mixed
		 * @deprecated since 3.0.0
		 */
		public function __get( $key ) {
			return false;
		}

		/**
		 * Trigger this function while activating Learnpress.
		 *
		 * @since 3.0.0
		 *
		 * @hook  learn_press_activate
		 */
		public function on_activate() {
			do_action( 'learn-press/activate', $this );
		}

		/**
		 * Trigger this function while deactivating Learnpress.
		 *
		 * $since 3.0.0
		 *
		 * @hook learn_press_deactivate
		 */
		public function on_deactivate() {
			do_action( 'learn-press/deactivate', $this );
		}

		/**
		 * Trigger WP loaded actions.
		 *
		 * @since 3.0.0
		 */
		public function wp_loaded() {
			if ( $this->is_request( 'frontend' ) ) {
				$this->gateways = LP_Gateways::instance()->get_available_payment_gateways();
			}
		}

		/**
		 * Setup courses thumbnail.
		 *
		 * @since 3.0.0
		 */
		public function setup_theme() {
			if ( ! current_theme_supports( 'post-thumbnails' ) ) {
				add_theme_support( 'post-thumbnails' );
			}
			add_post_type_support( LP_COURSE_CPT, 'thumbnail' );

			$size = LP_Settings::get_option(
				'course_thumbnail_dimensions',
				array(
					500,
					300,
				)
			);

			$size = array_values( (array) $size );

			add_image_size( 'course_thumbnail', $size[0], $size[1], true );
		}

		/**
		 * Trigger Learnpress loaded actions.
		 *
		 * @since 3.0.0
		 * @version 1.0.1
		 * @editor tungnx
		 */
		public function plugin_loaded() {
			$this->init();

			//Todo: tungnx - remove this code after handle ajax on page learn-press-addons
			require_once 'inc/background-process/class-lp-background-query-items.php';
			require_once 'inc/background-process/class-lp-background-installer.php';

			require_once 'inc/lp-template-hooks.php';

			/**
			 * Check version addons valid version require.
			 * If not valid will be deactivate.
			 * Reload page, so not affect to hook "learn-press/ready"
			 */
			$addons_valid = true;
			$plugins      = get_option( 'active_plugins' );

			$list_lp_addon_activated = preg_grep( '/^learnpress-.*/i', $plugins );

			// Remove hook deactivate addon assignments v3.
			add_action(
				'deactivate_learnpress-assignments/learnpress-assignments.php',
				array( $this, 'lp_assignment_install' ),
				- 10
			);

			foreach ( $list_lp_addon_activated as $lp_addon ) {
				$lp_addon_info = get_file_data(
					WP_PLUGIN_DIR . '/' . $lp_addon,
					array(
						'Require_LP_Version' => 'Require_LP_Version',
						'Version'            => 'Version',
					)
				);

				//$lp_addon_info    = get_plugin_data( WP_PLUGIN_DIR . '/' . $lp_addon );
				$lp_addon_version = $lp_addon_info['Version'];

				$addon                  = new Lp_Addon();
				$addon->version         = $lp_addon_version;
				$addon->plugin_base     = $lp_addon;
				$addon->require_version = $lp_addon_info['Require_LP_Version'];
				$addon_valid            = $addon->check_require_version_addon();

				if ( $addons_valid ) {
					$addon_valid = $addon->check_require_version_lp();
				}

				if ( ! $addon_valid ) {
					$addons_valid = false;
				}
			}
			// End check addons valid.

			if ( ! $addons_valid ) {
				return;
			}

			// let third parties know that we're ready .
			do_action( 'learn-press/ready' );
		}

		/**
		 * Remove hook deactivate addon assignments v3.
		 */
		public function lp_assignment_install() {
			remove_action( 'deactivate_learnpress-assignments/learnpress-assignments.php', 'lp_assignment_remove' );
		}

		/**
		 * Get instance of class LP_Template.
		 *
		 * @param string $type
		 *
		 * @return LP_Template_Course|LP_Template_Profile|LP_Template_General|LP_Abstract_Template|LP_Template
		 *
		 * @throws Exception
		 * @since 3.3.0
		 */
		public function template( $type = '' ) {
			if ( ! $this->template ) {
				$this->template = LP_Template::instance();
			}

			return isset( $this->template[ $type ] ) ? $this->template[ $type ] : $this->template;
		}

		/**
		 * Init LearnPress when WP initialises
		 */
		public function init() {
			$this->api           = new LP_Core_API();
			$this->admin_api     = new LP_Admin_Core_API();
			$this->theme_support = LP_Theme_Support::instance();

			//$this->view_log();

			$this->get_session();

			$this->settings = $this->settings();
			$this->utils    = LP_Utils::instance();

			if ( $this->is_request( 'frontend' ) ) {
				$this->get_cart();
			} else {
				$this->admin_notices = LP_Admin_Notice::instance();
			}

			// init email notification hooks
			LP_Emails::init_email_notifications();
		}

		/**
		 * View log.
		 *
		 * @since 3.0.0
		 * @deprecated 3.2.8
		 * @editor tungnx
		 */
		/*public function view_log() {
			if ( ! empty( $_REQUEST['view-log'] ) ) {
				$log = LP_Helper::sanitize_params_submitted( $_REQUEST['view-log'] );
				echo '<pre>';
				if ( is_multisite() ) {
					$log = "{$log}-" . get_current_blog_id();
				}
				echo $log = learn_press_get_log_file_path( $log );
				@readfile( $log );
				echo '<pre>';
				die();
			}
		}*/

		/**
		 * Get session object instance.
		 *
		 * @return mixed
		 */
		public function get_session() {
			if ( ! $this->session ) {
				$this->session = LP_Session_Handler::instance();
			}

			return $this->session;
		}

		/**
		 * Get settings object instance.
		 *
		 * @return bool|LP_Settings
		 */
		public function settings() {
			return LP_Settings::instance();
		}

		/**
		 * Get cart object instance for online learning market.
		 *
		 * @return LP_Cart
		 */
		public function get_cart() : LP_Cart {
			if ( ! $this->cart ) {
				$this->cart = LP_Cart::instance();
			}

			return $this->cart;
		}

		/**
		 * Check type of request.
		 *
		 * @param string $type ajax, frontend or admin.
		 *
		 * @return bool
		 */
		public function is_request( $type ) {
			switch ( $type ) {
				case 'admin':
					return is_admin();
				case 'ajax':
					return defined( 'LP_DOING_AJAX' );
				case 'cron':
					return defined( 'DOING_CRON' );
				case 'frontend':
					return ( ! is_admin() || defined( 'LP_DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
				default:
					return strtolower( $_SERVER['REQUEST_METHOD'] ) == $type;
			}
		}

		/**
		 * Get the plugin url.
		 *
		 * @param string $sub_dir
		 *
		 * @return string
		 */
		public function plugin_url( $sub_dir = '' ) {
			return LP_PLUGIN_URL . ( $sub_dir ? "{$sub_dir}" : '' );
		}

		/**
		 * Get the plugin path.
		 *
		 * @param string $sub_dir
		 *
		 * @return string
		 */
		public function plugin_path( $sub_dir = '' ) {
			return LP_PLUGIN_PATH . ( $sub_dir ? "{$sub_dir}" : '' );
		}

		/**
		 * Get checkout object instance
		 *
		 * @return LP_Checkout
		 */
		public function checkout() {
			return LP_Checkout::instance();
		}

		/**
		 * Short way to return image file is located in LearnPress directory.
		 *
		 * @param string
		 *
		 * @return string
		 */
		public function image( $file ) {
			if ( ! preg_match( '/.(jpg|png)$/', $file ) ) {
				$file .= '.jpg';
			}

			return $this->plugin_url( "assets/images/{$file}" );
		}

		public function flush_rewrite_rules() {
			LP()->session->flush_rewrite_rules = true;
			flush_rewrite_rules();
		}

		/**
		 * Check require version thim-core
		 */
		public function check_thim_core_version_require() {
			// Get thim-core info for LP check .
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			$thim_core_info = get_file_data(
				WP_PLUGIN_DIR . '/thim-core/thim-core.php',
				array(
					'Name'               => 'Plugin Name',
					'Require_LP_Version' => 'Require_LP_Version',
					'Version'            => 'Version',
				)
			);

			if ( version_compare( $this->thim_core_version_require, $thim_core_info['Version'], '>' ) ) {
				deactivate_plugins( 'thim-core/thim-core.php' );

				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}

				$message = sprintf(
					'%s %s You can download %s. Read guide on %s',
					'LP4 require version Thim-core:',
					$this->thim_core_version_require,
					'<a href="https://thimpresswp.github.io/thim-core/thim-core.zip">latest version</a>',
					'<a href="https://bit.ly/2SrsxUf">here</a>'
				);
				?>
				<div class="notice notice-error">
					<p><?php echo $message; ?></p>
				</div>
				<?php
				die;
			}
		}

		/**
		 * Main plugin instance.
		 *
		 * @return LearnPress
		 */
		public static function instance() {
			update_option( 'learnpress_version', LEARNPRESS_VERSION );

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public function admin_notice_require_addon_version() {
			?>
			<div class="notice notice-error">
				<p><?php echo( '<strong>LearnPress version ' . LEARNPRESS_VERSION . ' require Addon</strong> version 4.0.0 or higher' ); ?></p>
			</div>
			<?php
		}
	}
}

/**
 * Short way to load main instance of plugin
 *
 * @return LearnPress
 * @since  1.0
 * @author thimpress
 */
function LP() {
	return LearnPress::instance();
}

/**
 * Done! entry point of the plugin
 * Create new instance of LearnPress and put it to global
 */
$GLOBALS['LearnPress'] = LP();
