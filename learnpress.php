<?php
/*
Plugin Name: LearnPress
Plugin URI: http://thimpress.com/learnpress
Description: LearnPress is a WordPress complete solution for creating a Learning Management System (LMS). It can help you to create courses, lessons and quizzes.
Author: ThimPress
Version: 2.1.7.2
Author URI: http://thimpress.com
Requires at least: 3.8
Tested up to: 4.8

Text Domain: learnpress
Domain Path: /languages/
*/
/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( !defined( 'LP_PLUGIN_FILE' ) ) {
	define( 'LP_PLUGIN_FILE', __FILE__ );
	require_once dirname( __FILE__ ) . '/inc/lp-constants.php';
}

if ( !class_exists( 'LearnPress' ) ) {

	/**
	 * Class LearnPress
	 *
	 * Version 1.0
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
		 * @var array
		 */
		public $session = null;

		/**
		 * Store singular LP_Course object
		 *
		 * @var null
		 */
		private $_course = null;

		/**
		 * @var null
		 */
		private $_quiz = null;

		/**
		 * @var null
		 */
		public $lesson = null;

		/**
		 * @var LP_Cart object
		 */
		public $cart = false;

		/**
		 * @var null
		 */
		public $schedule = null;

		/**
		 * @var array
		 */
		public $query_vars = array();

		/**
		 * Store global variables
		 *
		 * @var array
		 */
		public $global = array( 'course' => null, 'course-item' => null, 'quiz-question' => null );

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
		 * LearnPress constructor
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

		public function __get( $key ) {
			$return = false;
			switch ( $key ) {
				case 'email':
					$return = LP_Email::instance();
					break;
				case 'checkout':
					$return = LP_Checkout::instance();
					break;
				case 'course':
					if ( empty( $this->_course ) ) {
						if ( learn_press_is_course() ) {
							$this->_course = learn_press_setup_object_data( get_the_ID() );
						}
					}
					$return = $this->_course;
					break;
				case 'quiz':
					if ( empty( $this->_quiz ) ) {
						if ( learn_press_is_quiz() ) {
							$this->_quiz = learn_press_setup_object_data( get_the_ID() );
						}
					}
					$return = $this->_quiz;
					break;
				default:
					if ( strpos( $key, 'tbl_' ) === 0 ) {
						$return = $this->_table_prefixes[$key];
					}
			}
			return $return;
		}

		public function set_object( $name, $object, $global = false ) {
			$this->{$name} = $object;
			if ( $global ) {
				$GLOBALS[$name] = $object;
			}
		}

		/**
		 * Main plugin Instance
		 *
		 * @static
		 * @return object Main instance
		 *
		 * @since  1.0
		 * @author
		 */
		public static function instance() {
			if ( !self::$_instance ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Defines table names
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
				'review_logs'
			);
			foreach ( $tables as $short_name ) {
				$table_name                                  = $wpdb->prefix . LP_TABLE_PREFIX . $short_name;
				$this->_table_prefixes['tbl_' . $short_name] = $table_name;

				$backward_key          = 'learnpress_' . $short_name;
				$wpdb->{$backward_key} = $table_name;
			}
		}

		/**
		 * Include custom post types
		 */
		public function include_post_types() {
			// Register custom-post-type and taxonomies
			require_once 'inc/custom-post-types/abstract.php';
			require_once 'inc/custom-post-types/course.php';
			require_once 'inc/custom-post-types/lesson.php';
			require_once 'inc/custom-post-types/quiz.php';
			require_once 'inc/custom-post-types/question.php';
			require_once 'inc/custom-post-types/order.php';
		}

		/**
		 * Get base name of plugin from file
		 * @return string
		 */
		private function plugin_basename() {
			return learn_press_plugin_basename( __FILE__ );
		}

		/**
		 * Initial common hooks
		 */
		public function init_hooks() {
			$plugin_basename = $this->plugin_basename();

			add_action( 'activate_' . $plugin_basename, array( $this, 'on_activate' ) );
			add_action( 'activate_' . $plugin_basename, array( 'LP_Install', 'install' ) );

			add_action( 'init', array( $this, 'init' ), 15 );
			add_action( 'wp_loaded', array( $this, 'wp_loaded' ), 20 );

			///add_action( 'widgets_init', array( $this, 'widgets_init' ) );

			add_action( 'template_redirect', 'learn_press_handle_purchase_request' );
			add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
			add_action( 'load-post.php', array( $this, 'load_meta_box' ), - 10 );
			add_action( 'load-post-new.php', array( $this, 'load_meta_box' ), - 10 );
			add_action( 'plugins_loaded', array( $this, 'loaded' ), 0 );
		}

		function wp_loaded() {
			learn_press_get_current_user();
			if ( $this->is_request( 'frontend' ) ) {
				$this->gateways = LP_Gateways::instance()->get_available_payment_gateways();
			}
		}

		public function loaded() {
			// let third parties know that we're ready
			do_action( 'learn_press_ready' );
			do_action( 'learn_press_loaded', $this );
		}

		/**
		 * Load metabox library
		 */
		public function load_meta_box() {
			require_once 'inc/libraries/meta-box/meta-box.php';
		}

		/**
		 * Trigger this function while activating LP
		 *
		 * @hook learn_press_activate
		 */
		public function on_activate() {
			do_action( 'learn_press_activate', $this );
		}

		/**
		 * Trigger this function while deactivating LP
		 *
		 * @hook learn_press_deactivate
		 */
		public function on_deactivate() {
			do_action( 'learn_press_deactivate', $this );
		}

		/**
		 * Init LearnPress when WP initialises
		 */
		public function init() {

			if ( !empty( $_REQUEST['view-log'] ) ) {
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
			if ( $this->is_request( 'frontend' ) ) {
				$this->get_session();
				$this->get_cart();
			}

			$this->get_user();
			$this->schedule = require_once( LP_PLUGIN_PATH . "/inc/class-lp-schedules.php" );

			LP_Emails::instance();

			if ( get_option( 'learn_press_install' ) == 'yes' ) {
				flush_rewrite_rules();
				delete_option( 'learn_press_install' );
			}
		}

		/**
		 * Get session object instance
		 *
		 * @return mixed
		 */
		public function get_session() {
			if ( !$this->session ) {
				$session_class = apply_filters( 'learn_press_session_class', 'LP_Session_Handler' );
				if ( class_exists( $session_class ) ) {
					$this->session = is_callable( array( $session_class, 'instance' ) ) ? call_user_func( array( $session_class, 'instance' ) ) : new $session_class();
				}
			}
			return $this->session;
		}

		/**
		 * Get cart object instance for online learning market
		 *
		 * @return mixed
		 */
		public function get_cart() {
			if ( !$this->cart ) {
				$cart_class = apply_filters( 'learn_press_cart_class', 'LP_Cart' );
				if ( is_object( $cart_class ) ) {
					$this->cart = $cart_class;
				} else {
					if ( class_exists( $cart_class ) ) {
						$this->cart = is_callable( array( $cart_class, 'instance' ) ) ? call_user_func( array( $cart_class, 'instance' ) ) : new $cart_class();
					}
				}
			}
			return $this->cart;
		}

		public function get_checkout_cart() {
			return learn_press_get_checkout_cart();
		}

		public function get_user( $user_id = 0 ) {
			static $users = array();
			$user = false;
			if ( !$this->user ) {
				$this->user = learn_press_get_current_user();
			}
			if ( $user_id ) {
				if ( $user_id == $this->user->id ) {
					$user = $this->user;
				} else {
					if ( empty( $users[$user_id] ) ) {
						$users[$user_id] = learn_press_get_user( $user_id );
						$user            = $users[$user_id];
					}
				}
			} else {
				$user = $this->user;
			}
			return $user;
		}

		/**
		 * Check type of request
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
					return ( !is_admin() || defined( 'LP_DOING_AJAX' ) ) && !defined( 'DOING_CRON' );
				default:
					return strtolower( $_SERVER['REQUEST_METHOD'] ) == $type;
			}
		}

		/**
		 * Get the template folder in the theme.
		 *
		 * @param bool
		 *
		 * @access public
		 * @return string
		 */
		public function template_path( $slash = false ) {
			return learn_press_template_path( $slash );
		}

		/**
		 * Includes needed files
		 */
		public function includes() {

			require_once 'inc/lp-deprecated.php';
			require_once 'inc/class-lp-cache.php';

			// include core functions
			require_once 'inc/lp-core-functions.php';
			require_once 'inc/lp-add-on-functions.php';
			// auto include file for class if class doesn't exists
			require_once 'inc/class-lp-autoloader.php';
			require_once 'inc/class-lp-install.php';
			require_once 'inc/lp-webhooks.php';
			require_once 'inc/class-lp-request-handler.php';

			if ( is_admin() ) {

				require_once 'inc/admin/class-lp-admin-notice.php';

				/*if ( !defined( 'RWMB_VER' ) ) {
					require_once 'inc/libraries/meta-box/meta-box.php';
				}*/

				require_once 'inc/admin/class-lp-admin.php';
				//require_once 'inc/admin/class-lp-admin-settings.php';

				//require_once( 'inc/admin/class-lp-admin-assets.php' );
				require_once( 'inc/admin/settings/class-lp-settings-base.php' );
			}
			$this->settings = LP_Settings::instance();
			require_once 'inc/class-lp-assets.php';
			require_once 'inc/question/abstract-lp-question.php';
			require_once 'inc/question/class-lp-question-factory.php';
			$this->include_post_types();

			if ( defined( 'LP_USE_ATTRIBUTES' ) && LP_USE_ATTRIBUTES ) {
				require_once 'inc/attributes/lp-attributes-functions.php';
			}

			// course
			require_once 'inc/course/lp-course-functions.php';
			require_once 'inc/course/abstract-lp-course.php';
			require_once 'inc/course/abstract-lp-course-item.php';
			require_once 'inc/course/class-lp-course.php';

			// quiz
			require_once 'inc/quiz/lp-quiz-functions.php';
			require_once 'inc/quiz/class-lp-quiz-factory.php';
			require_once 'inc/quiz/class-lp-quiz.php';

			// lesson
			require_once 'inc/lesson/lp-lesson-functions.php';
			// question
			//require_once 'inc/question/lp-question.php';

			// order
			require_once 'inc/order/lp-order-functions.php';
			require_once 'inc/order/class-lp-order.php';

			// user API
			require_once 'inc/user/lp-user-functions.php';
			require_once 'inc/user/class-lp-user-factory.php';
			require_once 'inc/user/abstract-lp-user.php';
			require_once 'inc/user/class-lp-user.php';
			require_once 'inc/user/class-lp-profile.php';


			// others
			require_once 'inc/class-lp-session-handler.php';
			require_once 'inc/admin/class-lp-email.php';
			// assets

			if ( is_admin() ) {

				//Include pointers
				require_once 'inc/admin/pointers/pointers.php';
			} else {

				// shortcodes
				require_once 'inc/class-lp-shortcodes.php';
			}

			// include template functions
			require_once( 'inc/lp-template-functions.php' );
			require_once( 'inc/lp-template-hooks.php' );
			// settings
			//require_once 'inc/class-lp-settings.php';
			// simple cart
			require_once 'inc/cart/class-lp-cart.php';
			// payment gateways
			require_once 'inc/gateways/class-lp-gateway-abstract.php';
			require_once 'inc/gateways/class-lp-gateways.php';

			//add ajax-action
			require_once 'inc/admin/class-lp-admin-ajax.php';
			require_once 'inc/class-lp-ajax.php';
			require_once 'inc/class-lp-multi-language.php';
			require_once 'inc/class-lp-page-controller.php';

			// widgets
			LP_Widget::register( array( 'featured-courses', 'popular-courses', 'recent-courses' ) );

			$GLOBALS['lp_query'] = $this->query = new LP_Query();

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
		 * Include a file from plugin path
		 *
		 * @param           $file
		 * @param string    $folder
		 * @param bool|true $include_once
		 *
		 * @return bool
		 */
		public function _include( $file, $folder = 'inc', $include_once = true ) {
			if ( file_exists( $include = $this->plugin_path( "{$folder}/{$file}" ) ) ) {
				if ( $include_once ) {
					include_once $include;
				} else {
					include $include;
				}
				return true;
			}
			return false;
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
		 * Setup courses thumbnail
		 */
		public function setup_theme() {
			if ( !current_theme_supports( 'post-thumbnails' ) ) {
				add_theme_support( 'post-thumbnails' );
			}
			add_post_type_support( 'lp_course', 'thumbnail' );

			// if enabled generate course thumbnail on General Settings add new image sizes
			$enabled_course_thum = LP()->settings->get( 'generate_course_thumbnail', 'yes' );
			if ( $enabled_course_thum !== 'yes' ) {
				return;
			}
			$sizes = apply_filters( 'learn_press_image_sizes', array( 'single_course', 'course_thumbnail' ) );

			foreach ( $sizes as $image_size ) {
				$size           = LP()->settings->get( $image_size . '_image_size', array() );
				$size['width']  = isset( $size['width'] ) ? $size['width'] : '300';
				$size['height'] = isset( $size['height'] ) ? $size['height'] : '300';
				$size['crop']   = isset( $size['crop'] ) ? $size['crop'] : 0;

				add_image_size( $image_size, $size['width'], $size['height'], $size['crop'] );
			}
		}

		/**
		 * Short way to return js file is located in LearnPress directory
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
			if ( !preg_match( '/.js$/', $file ) ) {
				$file .= '.js';
			}
			if ( $min ) {
				$file = preg_replace( '/.js$/', $min . '.js', $file );
			}
			return $this->plugin_url( "assets/js/{$file}" );
		}

		/**
		 * Short way to return css file is located in LearnPress directory
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
			if ( !preg_match( '/.css/', $file ) ) {
				$file .= '.css';
			}
			if ( $min ) {
				$file = preg_replace( '/.css/', $min . '.css', $file );
			}
			return $this->plugin_url( "assets/css/{$file}" );
		}

		/**
		 * Short way to return image file is located in LearnPress directory
		 *
		 * @param string
		 *
		 * @return string
		 */
		public function image( $file ) {

			if ( !preg_match( '/.(jpg|png)$/', $file ) ) {
				$file .= '.jpg';
			}

			return $this->plugin_url( "assets/images/{$file}" );
		}
	} // end class
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
