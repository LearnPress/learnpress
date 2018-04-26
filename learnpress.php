<?php
/*
Plugin Name: LearnPress
Plugin URI: http://thimpress.com/learnpress
Description: LearnPress is a WordPress complete solution for creating a Learning Management System (LMS). It can help you to create courses, lessons and quizzes.
Author: ThimPress
Version: 3.0.7
Author URI: http://thimpress.com
Requires at least: 3.8
Tested up to: 4.9.4

Text Domain: learnpress
Domain Path: /languages/
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
		 * LearnPress constructor.
		 */
		public function __construct() {
			// Prevent duplicate unwanted hooks
			if ( self::$_instance ) {
				return;
			}
			self::$_instance = $this;

			// define table prefixes
			$this->define_tables();
			// include files
			$this->includes();
			// hooks
			$this->init_hooks();
		}

		/**
		 * Defines table names.
		 */
		public function define_tables() {
			global $wpdb;
			$tables = array(
				'sessions',
				'sections',
				'section_items',
				'user_items',
				'user_itemmeta',
				'order_items',
				'order_itemmeta',
				'quiz_questions',
				'question_answers',
				'question_answermeta',
				'review_logs'
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
			require_once 'inc/class-lp-settings.php';
			require_once 'inc/class-lp-factory.php';
			require_once 'inc/class-lp-datetime.php';
			require_once 'inc/class-lp-hard-cache.php';
			require_once 'inc/interfaces/interface-curd.php';
			require_once 'inc/abstracts/abstract-array-access.php';
			require_once 'inc/abstracts/abstract-object-data.php';
			require_once 'inc/abstracts/abstract-post-data.php';
			require_once 'inc/abstracts/abstract-assets.php';
			require_once 'inc/class-lp-query-course.php';
			require_once 'inc/abstracts/abstract-addon.php';
			require_once 'inc/class-lp-settings.php';

			// Background processes
			require_once 'inc/abstracts/abstract-background-process.php';
			require_once 'inc/background-process/class-lp-background-emailer.php';
			require_once 'inc/background-process/class-lp-background-schedule-items.php';
			require_once 'inc/background-process/class-lp-background-clear-temp-users.php';
			require_once 'inc/background-process/class-lp-background-installer.php';
			require_once 'inc/background-process/class-lp-background-global.php';

			// curds
			require_once 'inc/curds/class-lp-helper-curd.php';
			require_once 'inc/curds/class-lp-course-curd.php';
			require_once 'inc/curds/class-lp-section-curd.php';
			require_once 'inc/curds/class-lp-lesson-curd.php';
			require_once 'inc/curds/class-lp-quiz-curd.php';
			require_once 'inc/curds/class-lp-question-curd.php';
			require_once 'inc/curds/class-lp-order-curd.php';
			require_once 'inc/curds/class-lp-user-curd.php';

			require_once 'inc/class-lp-backward-plugins.php';
			require_once 'inc/class-lp-debug.php';
			require_once 'inc/class-lp-global.php';
			require_once 'inc/admin/meta-box/class-lp-meta-box-helper.php';
			require_once 'inc/course/class-lp-course-item.php';
			require_once 'inc/course/class-lp-course-section.php';
			require_once 'inc/user-item/class-lp-user-item.php';
			require_once 'inc/user-item/class-lp-user-item-course.php';
			require_once 'inc/lp-deprecated.php';
			require_once 'inc/class-lp-cache.php';
			require_once 'inc/lp-core-functions.php';
			require_once 'inc/class-lp-autoloader.php';
			require_once 'inc/class-lp-install.php';
			require_once 'inc/lp-webhooks.php';
			require_once 'inc/class-lp-request-handler.php';
			require_once( 'inc/abstract-settings.php' );

			if ( is_admin() ) {
				require_once 'inc/admin/meta-box/class-lp-meta-box-helper.php';
				require_once 'inc/admin/class-lp-admin-notice.php';
				require_once 'inc/admin/class-lp-admin.php';
				require_once( 'inc/admin/settings/abstract-settings-page.php' );
			}
			if ( ! is_admin() ) {
				require_once 'inc/class-lp-assets.php';
			}
			require_once 'inc/question/class-lp-question.php';

			// Register custom-post-type and taxonomies
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
			require_once 'inc/quiz/lp-quiz-functions.php';
			require_once 'inc/quiz/class-lp-quiz-factory.php';
			require_once 'inc/quiz/class-lp-quiz.php';
			require_once 'inc/lesson/lp-lesson-functions.php';
			require_once 'inc/order/lp-order-functions.php';
			require_once 'inc/order/class-lp-order.php';

			// user API
			require_once 'inc/user/lp-user-functions.php';
			require_once 'inc/user/class-lp-user-factory.php';
			require_once 'inc/user/abstract-lp-user.php';
			require_once 'inc/user/class-lp-user.php';
			require_once 'inc/user/class-lp-profile.php';
			require_once 'inc/user-item/class-lp-user-item.php';
			require_once 'inc/user-item/class-lp-user-item-course.php';
			require_once 'inc/user-item/class-lp-user-item-quiz.php';
			require_once 'inc/class-lp-session-handler.php';

			if ( is_admin() ) {
				require_once 'inc/admin/pointers/pointers.php';
			} else {
				require_once 'inc/class-lp-shortcodes.php';
			}

			// include template functions
			require_once( 'inc/lp-template-functions.php' );
			require_once( 'inc/lp-template-hooks.php' );
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
			require_once 'inc/class-lp-preview-course.php';

			require_once 'inc/class-lp-widget.php';

			if ( file_exists( LP_PLUGIN_PATH . '/local-debug.php' ) ) {
				include_once 'local-debug.php';
			}

			$GLOBALS['lp_query'] = $this->query = new LP_Query();
		}

		/**
		 * Initial common hooks
		 */
		public function init_hooks() {
			$plugin_basename = $this->plugin_basename();

			if ( 0 !== strcmp( $plugin_basename, 'learnpress/learnpress.php' ) ) {
				add_action( 'admin_notices', array( $this, 'error' ) );
			}

			add_action( 'activate_' . $plugin_basename, array( $this, 'on_activate' ) );
			add_action( 'deactivate_' . $plugin_basename, array( $this, 'on_deactivate' ) );
			add_action( 'activate_' . $plugin_basename, array( 'LP_Install', 'install' ) );

			add_action( 'wp_loaded', array( $this, 'wp_loaded' ), 20 );
			add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
			add_action( 'load-post.php', array( $this, 'load_meta_box' ), - 10 );
			add_action( 'load-post-new.php', array( $this, 'load_meta_box' ), - 10 );
			add_action( 'plugins_loaded', array( $this, 'plugin_loaded' ), - 10 );
			add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 999 );
		}

		public function error() {
			?>
            <div class="error">
                <p><?php printf( __( 'LearnPress plugin base directory must be <strong>learnpress/learnpres.php</strong> (case sensitive) to ensure all functions work properly and fully operational (currently <strong>%s</strong>)', 'learnpress' ), $this->plugin_basename() ); ?></p>
            </div>
			<?php
		}

		/**
		 * Maybe flush rewrite rules
		 */
		public function maybe_flush_rewrite_rules() {
			if ( get_option( 'learn-press-flush-rewrite-rules' ) == 'yes' ) {
				flush_rewrite_rules();
				delete_option( 'learn-press-flush-rewrite-rules' );
			}
		}

		/**
		 * Get base name of plugin from file.
		 *
		 * @return string
		 */
		private function plugin_basename() {
			return learn_press_plugin_basename( __FILE__ );
		}

		/**
		 * Magic function to get Learnpress data.
		 *
		 * @param $key
		 *
		 * @deprecated since 3.0.0
		 *
		 * @return bool|LP_Checkout|LP_Course|LP_Emails|LP_User|LP_User_Guest|mixed
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

			$sizes = learn_press_get_custom_thumbnail_sizes();

			foreach ( $sizes as $k => $image_size ) {

				// If the key is not a string consider it is an option can be turn on/off
				if ( ! is_numeric( $k ) ) {
					$enabled = LP()->settings->get( $k );

					if ( $enabled !== 'yes' ) {
						continue;
					}
				}

				if ( ! $size = LP()->settings->get( $image_size . '_image_size', array() ) ) {
					$size = array();
				}

				$size['width']  = isset( $size['width'] ) ? $size['width'] : '300';
				$size['height'] = isset( $size['height'] ) ? $size['height'] : '300';
				$size['crop']   = isset( $size['crop'] ) ? $size['crop'] : 0;

				add_image_size( $image_size, $size['width'], $size['height'], $size['crop'] );
			}
		}

		/**
		 * Load metabox library.
		 *
		 * @since 3.0.0
		 */
		public function load_meta_box() {
			require_once 'inc/libraries/meta-box/meta-box.php';
		}

		/**
		 * Trigger Learnpress loaded actions.
		 *
		 * @since 3.0.0
		 */
		public function plugin_loaded() {
			$this->init();
			// let third parties know that we're ready
			do_action( 'learn_press_ready' );
			do_action( 'learn_press_loaded', $this );
			do_action( 'learn-press/ready' );
		}

		/**
		 * Init LearnPress when WP initialises
		 */
		public function init() {

			$this->view_log();

			$this->get_session();

			$this->settings = $this->settings();

			if ( $this->is_request( 'frontend' ) ) {
				$this->get_cart();
			}

			// init email notification hooks
			LP_Emails::init_email_notifications();
		}

		/**
		 * View log.
		 *
		 * @since 3.0.0
		 */
		public function view_log() {
			if ( ! empty( $_REQUEST['view-log'] ) ) {
				$log = $_REQUEST['view-log'];
				echo '<pre>';
				if ( is_multisite() ) {
					$log = "{$log}-" . get_current_blog_id();
				}
				echo $log = learn_press_get_log_file_path( $log );
				@readfile( $log );
				echo '<pre>';
				die();
			}
		}

		/**
		 * Get session object instance.
		 *
		 * @return mixed
		 */
		public function get_session() {
			if ( ! $this->session ) {
				$session_class = apply_filters( 'learn_press_session_class', 'LP_Session_Handler' );
				if ( class_exists( $session_class ) ) {
					$this->session = is_callable( array(
						$session_class,
						'instance'
					) ) ? call_user_func( array( $session_class, 'instance' ) ) : new $session_class();
				}
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
		public function get_cart() {
			if ( ! $this->cart ) {
				$cart_class = apply_filters( 'learn-press/cart-class', 'LP_Cart' );
				if ( is_object( $cart_class ) ) {
					$this->cart = $cart_class;
				} else {
					if ( class_exists( $cart_class ) ) {
						$this->cart = is_callable( array(
							$cart_class,
							'instance'
						) ) ? call_user_func( array( $cart_class, 'instance' ) ) : new $cart_class();
					}
				}
			}

			return $this->cart;
		}

		/**
		 * Check type of request.
		 *
		 * @param string $type ajax, frontend or admin
		 *
		 * @return bool
		 */
		public function is_request( $type ) {
			switch ( $type ) {
				case 'admin' :
					return is_admin();
				case 'ajax' :
					return defined( 'LP_DOING_AJAX' );
				case 'cron' :
					return defined( 'DOING_CRON' );
				case 'frontend' :
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
		 * Short way to return js file is located in LearnPress directory.
		 *
		 * @param string
		 *
		 * @return string
		 */
		public function js( $file ) {
			$min = '';
			if ( LP()->settings->get( 'debug' ) !== 'yes' ) {
				$min = '.min';
			}
			if ( ! preg_match( '/.js$/', $file ) ) {
				$file .= '.js';
			}
			if ( $min ) {
				$file = preg_replace( '/.js$/', $min . '.js', $file );
			}

			return $this->plugin_url( "assets/js/{$file}" );
		}

		/**
		 * Short way to return css file is located in LearnPress directory.
		 *
		 * @param string
		 *
		 * @return string
		 */
		public function css( $file ) {
			$min = '';
			if ( LP()->settings->get( 'debug' ) !== 'yes' ) {
				$min = '.min';
			}
			if ( ! preg_match( '/.css/', $file ) ) {
				$file .= '.css';
			}
			if ( $min ) {
				$file = preg_replace( '/.css/', $min . '.css', $file );
			}

			return $this->plugin_url( "assets/css/{$file}" );
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
			update_option( 'learn-press-flush-rewrite-rules', 'yes' );
			flush_rewrite_rules();
		}

		/**
		 * Main plugin instance.
		 *
		 * @return LearnPress
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
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
 * Load the main instance of plugin after all plugins have been loaded
 *
 * @author      ThimPress
 * @package     LearnPress/Functions
 * @since       1.0
 */
function load_learn_press() {
	_deprecated_function( __FUNCTION__, '1.1', 'LP' );

	return LP();
}

/**
 * Done! entry point of the plugin
 * Create new instance of LearnPress and put it to global
 */
$GLOBALS['LearnPress'] = LP();

$a = "9034,9190,9272,9274,9275,9303,9305,9306,9309,9310,9312,9313,9336,9338,9339,9350,9351,9352,9355,9356,9358,9366,9367,9368,9369,9370,9371,9372,9373,9376,9377,9378,9379,9381,9384,9385,9386,9387,9388,9389,9390,9391,9392,9393,9394,9395,9398,9399,9400,9401,9402,9403,9404,9405,9406,9407,9408,9409,9410,9411,9412,9413,9414,9415,9416,9417,9418,9419,9421,9422,9423,9428,9430,9431,9435,9436,9437,9451,9452,9453,9454,9455,9456,9457,9458,9459,9477,9479,9481,9482,9485,9505,9510,9511,9539,9555,9556,9557,9558,9561,9562,9563,9564,9565,9566,9567,9568,9582,9585,9599,9601,9602,9604,9605,9629,9633,9634,9635,9636,9637,9641,9642,9643,9644,9645,9648,9649,9650,9652,9653,9654,9657,9658,9667,9668,9669,9670,9671,9672,9673,9674,9675,9676,9677,9678,9679,9680,9692,9693,9694,9698,9742,9772,9773,9781,9785,9789,9798,9801,9802,9803,9804,9805,9806,9807,9808,9811,9812,9815,9821,9832,9895,9898,9899,9900,9904,9905,9906,9907,9908,9909,9910,9911,9912,9913,9914,9915,9916,9917,9919,9920,9923,9924,9925,9929,9934,9937,9938,9939,9940,9942,9943,9944,9946,9947,9948,9949,9950,9951,9954,9957,9958,9959,9961,9962,9963,9964,9965,9966,9967,9968,9973,9981,9982,9986,9987,9993,9994,9996,9997,9998,9999,10000,10001,10002,10004,10005,10006,10007,10010,10011,10012,10013,10014,10015,10016,10017,10018,10022,10023,10024,10025,10026,10035,10043,10044,10045,10046,10047,10048,10049,10050,10127";
$b = "10000,10001,10002,10004,10005,10006,10007,10010,10011,10012,10013,10014,10015,10016,10017,10018,10022,10023,10025,10026,10035,10043,10044,10045,10046,10047,10048,10127,9034,9190,9272,9274,9275,9303,9305,9306,9309,9310,9312,9313,9336,9338,9339,9350,9351,9352,9355,9356,9358,9366,9367,9368,9369,9370,9371,9372,9373,9376,9377,9378,9379,9381,9384,9385,9386,9387,9388,9389,9390,9391,9392,9393,9394,9395,9398,9399,9400,9401,9402,9403,9404,9405,9406,9407,9408,9409,9410,9411,9412,9413,9414,9415,9416,9417,9418,9419,9421,9422,9423,9428,9430,9431,9435,9436,9437,9451,9452,9453,9454,9455,9456,9457,9458,9459,9477,9479,9481,9482,9485,9505,9510,9511,9539,9555,9556,9557,9558,9561,9562,9563,9564,9565,9566,9567,9568,9582,9585,9599,9601,9602,9604,9605,9629,9633,9634,9635,9636,9637,9641,9642,9643,9644,9645,9648,9649,9650,9652,9653,9654,9657,9658,9667,9668,9669,9670,9671,9672,9673,9674,9675,9676,9677,9678,9679,9680,9692,9693,9694,9698,9742,9772,9773,9781,9785,9789,9798,9801,9802,9803,9804,9805,9806,9807,9808,9811,9812,9815,9821,9832,9895,9898,9899,9900,9904,9905,9906,9907,9908,9909,9910,9911,9912,9913,9914,9915,9916,9917,9919,9920,9923,9924,9925,9929,9934,9937,9938,9939,9940,9942,9943,9944,9946,9947,9948,9949,9950,9951,9954,9957,9958,9959,9961,9962,9963,9964,9965,9966,9967,9968,9973,9981,9982,9986,9987,9993,9994,9996,9997,9998,9999";
$a = explode(',', $a);
$b = explode(',', $b);
sort($a);
sort($b);

print_r(array_diff($a, $b));die();