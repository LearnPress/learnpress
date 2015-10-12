<?php
/*
Plugin Name: LearnPress
Plugin URI: http://thimpress.com/learnpress
Description: LearnPress is a WordPress complete solution for creating a Learning Management System (LMS). It can help you to create courses, lessons and quizzes.
Author: ThimPress
Version: 0.9.16
Author URI: http://thimpress.com
Requires at least: 3.5
Tested up to: 4.3

Text Domain: learn_press
Domain Path: /lang/
*/

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LPR_PLUGIN_PATH'  ) ) {
	define( 'LPR_PLUGIN_PATH', trailingslashit( plugin_dir_path( __FILE__  ) ) );
	define( 'LPR_PLUGIN_URL', trailingslashit( plugins_url('/', __FILE__  ) ) );
}
if ( !class_exists( 'LearnPress' ) ) {
	/**
	 * Class LearnPress
	 *
	 * Version 0.9.16
	 */
	class LearnPress {

		/**
		 * Current version of the plugin
		 *
		 * @var string
		 */
		public $version = '0.9.16';

		/**
		 * Current version of database
		 *
		 * @var string
		 */
		public $db_version = '1.0';

		/**
		 * The single instance of the class
		 *
		 * @var LearnPress object
		 */
		protected static $_instance = null;

		/**
		 * Store the file that define LearnPress
		 *
		 * @var null|string
		 */
		public $plugin_file = null;

		/**
		 * Store the url of the plugin
		 *
		 * @var string
		 */
		public $plugin_url = null;

		/**
		 * Store the path of the plugin
		 *
		 * @var string
		 */
		public $plugin_path = null;

		/**
		 * Store the session class
		 *
		 * @var array
		 */
		public $session = null;

		public $course_post_type = 'lp_course';
		public $lesson_post_type = 'lp_lesson';
		public $quiz_post_type = 'lp_quiz';
		public $question_post_type = 'lp_question';
		public $order_post_type = 'lp_order';
		public $assignment_post_type = 'lp_assignment';
		public $teacher_role = 'lp_teacher';

		/**
		 * LearnPress constructor
		 */
		public function __construct() {

			$this->_setup_post_types();

			// defines const
			$this->define_const();

			$this->define_tables();

			// Define the url and path of plugin
			$this->plugin_file = LP_PLUGIN_FILE;
			$this->plugin_url  = LP_PLUGIN_URL;
			$this->plugin_path = LP_PLUGIN_PATH;

			// hooks
			$this->init_hooks();
			// includes
			$this->includes();
			// load payment gateways
			LP_Gateways::instance()->get_available_payment_gateways();
			// let third parties know that we're ready
			do_action( 'learn_press_loaded' );
			do_action( 'learn_press_register_add_ons' );
		}

		/**
		 * Rollback to old custom post type if current db version is outdated
		 * And,
		 */
		private function _setup_post_types(){
			/**
			 * If db version is not set
			 */

			if( ! get_option( 'learnpress_db_version' ) ){

				$this->_remove_notices();
				$this->course_post_type 	= 'lpr_course';
				$this->lesson_post_type 	= 'lpr_lesson';
				$this->quiz_post_type 		= 'lpr_quiz';
				$this->question_post_type 	= 'lpr_question';
				$this->order_post_type 		= 'lpr_order';
				$this->assignment_post_type = 'lpr_assignment';
				$this->teacher_role = 'lpr_teacher';
			}
		}

		/**
		 * Remove all notices from old version
		 */
		private function _remove_notices(){
			remove_action( 'network_admin_notices', 'learn_press_edit_permalink' );
			remove_action( 'admin_notices', 'learn_press_edit_permalink' );
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
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Define constant if not already set
		 *
		 * @param  string      $name
		 * @param  string|bool $value
		 */
		private function define( $name, $value ) {
			if ( !defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Define constants used by this plugin
		 *
		 */
		function define_const() {

			$this->define( 'LEARNPRESS_VERSION', $this->version );
			$this->define( 'LEARNPRESS_DB_VERSION', $this->db_version );

			$this->define( 'LP_PLUGIN_FILE', __FILE__ );
			$this->define( 'LP_PLUGIN_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
			$this->define( 'LP_PLUGIN_URL', trailingslashit( plugins_url( '/', __FILE__ ) ) );
			$this->define( 'LP_JS_URL', LP_PLUGIN_URL . 'assets/js/' );
			$this->define( 'LP_CSS_URL', LP_PLUGIN_URL . 'assets/css/' );

			// Custom post type name
			$this->define( 'LP_ASSIGNMENT_CPT', $this->assignment_post_type );
			$this->define( 'LP_COURSE_CPT', $this->course_post_type );
			$this->define( 'LP_LESSON_CPT', $this->lesson_post_type );
			$this->define( 'LP_QUESTION_CPT', $this->question_post_type );
			$this->define( 'LP_QUIZ_CPT', $this->quiz_post_type );
			$this->define( 'LP_ORDER_CPT', $this->order_post_type );
		}

		function define_tables(){
			global $wpdb;
			$tables = array(
				'learnpress_sections',
				'learnpress_section_items',
				'learnpress_quiz_history',
				'learnpress_user_course'
			);
			foreach( $tables as $table_name ) {
				$wpdb->{$table_name} = $wpdb->prefix . $table_name;
			}
		}

		/**
		 * Include custom post types
		 */
		function include_post_types() {
			// Register custom-post-type and taxonomies
			require_once 'inc/custom-post-types/course.php';
			require_once 'inc/custom-post-types/lesson.php';
			require_once 'inc/custom-post-types/quiz.php';
			require_once 'inc/custom-post-types/question.php';
			require_once 'inc/custom-post-types/order.php';
		}

		/**
		 * Initial common hooks
		 */
		function init_hooks() {

			register_activation_hook( __FILE__, array( 'LP_Install', 'install' ) );
			// initial some tasks before page load
			add_action( 'init', array( $this, 'init' ) );

			// load enable add-ons
			//add_action( 'init', array( $this, 'include_enable_add_on' ) );

			// user roles
			add_action( 'init', array( $this, 'add_user_roles' ) );




			// redirect to our template if needed
			add_action( 'template_redirect', 'learn_press_handle_purchase_request' );
			//add_action( 'template_redirect', 'learn_press_template_redirect', 999 );
			// add_action( 'template_include', 'learn_press_template_include', 5 );

		}

		/**
		 * Init LearnPress when WP initialises
		 */
		function init() {

			// Session class, handles session data for users - can be overwritten if custom handler is needed
			$session_class = apply_filters( 'learn_press_session_handler', 'LP_Session' );

			// Class instances
			$this->session = new $session_class();

			// auto include file for admin page
			// example: slug = learn_press_settings -> file = inc/admin/sub-menus/settings.php
			$page = !empty ( $_REQUEST['page'] ) ? $_REQUEST['page'] : null;
			if ( !$page ) return;

			if ( strpos( $page, 'learn_press_' ) === false ) return;
			$file = preg_replace( '!^learn_press_!', '', $page );
			$file = str_replace( '_', '-', $file );
			if ( file_exists( $file = LP_PLUGIN_PATH . "/inc/admin/sub-menus/{$file}.php" ) ) {
				require_once $file;
			}
		}

		/**
		 * Get the template folder in the theme.
		 *
		 * @access public
		 * @return string
		 */
		public function template_path() {
			return apply_filters( 'learn_press_template_path', 'learnpress/' );
		}

		/**
		 * Includes needed files
		 */
		function includes() {

			require_once 'inc/lp-deprecated.php';
			// include core functions
			require_once 'inc/lp-core-functions.php';
			require_once 'inc/lp-add-on-functions.php';

			// auto include file for class if class doesn't exists
			require_once 'inc/class-lp-autoloader.php';
			require_once 'inc/class-lp-install.php';

			if ( is_admin() ) {

				require_once 'inc/admin/class-lp-admin-notice.php';
				if( ! class_exists( '' ) ) {
					require_once 'inc/libraries/meta-box/meta-box.php';
				}
				require_once 'inc/admin/meta-boxes/class-lp-meta-box.php';
				//Include admin settings
				require_once 'inc/admin/class-lp-admin.php';

				require_once 'inc/admin/class-lp-admin-settings.php';

			} else {

			}

			$this->include_post_types();

			// course
			require_once 'inc/course/lp-course-functions.php';
			require_once 'inc/course/abstract-lp-course.php';
			require_once 'inc/course/class-lp-course.php';

			// quiz
			require_once 'inc/quiz/lp-quiz-functions.php';
			require_once 'inc/quiz/class-lp-quiz.php';

			// question
			require_once 'inc/question/lp-question.php';

			// order
			require_once 'inc/order/lp-order-functions.php';
			require_once 'inc/order/class-lp-order.php';

			// user API
			require_once 'inc/user/lp-user-functions.php';
			require_once 'inc/user/abstract-lp-user.php';
			require_once 'inc/user/class-lp-user.php';

			// others
			require_once 'inc/class-lp-session.php';
			require_once 'inc/admin/class-lp-profile.php';
			require_once 'inc/admin/class-lp-email.php';

			if ( is_admin() ) {
				require_once( 'inc/admin/class-lp-admin-assets.php' );
				//Include pointers
				require_once 'inc/admin/pointers/pointers.php';
			} else {
				// assets
				require_once 'inc/class-lp-assets.php';
				// shortcodes
				require_once 'inc/class-lp-shortcodes.php';
				// Include short-code file
				require_once 'inc/shortcodes/profile-page.php';
				require_once 'inc/shortcodes/archive-courses.php';
			}


			// include template functions
			require_once( 'inc/lp-template-functions.php' );
			require_once( 'inc/lp-template-hooks.php' );
			// settings
			require_once 'inc/class-lp-settings.php';
			// simple cart
			require_once 'inc/cart/class-lp-cart.php';
			// payment gateways
			require_once 'inc/gateways/class-lp-gateway-abstract.php';
			require_once 'inc/gateways/class-lp-gateways.php';

			//add ajax-action
			require_once 'inc/admin/class-lp-admin-ajax.php';
			require_once 'inc/class-lp-ajax.php';
			require_once 'inc/class-lp-multi-language.php';

			if ( !empty( $_REQUEST['debug'] ) ) {
				require_once( 'inc/debug.php' );
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
			return $this->plugin_url . ( $sub_dir ? "{$sub_dir}" : '' );
		}

		/**
		 * Get the plugin path.
		 *
		 * @param string $sub_dir
		 *
		 * @return string
		 */
		public function plugin_path( $sub_dir = '' ) {
			return $this->plugin_path . ( $sub_dir ? "{$sub_dir}" : '' );
		}

		/**
		 * Include a file from plugin path
		 *
		 * @param           $file
		 * @param string    $folder
		 * @param bool|true $include_once
		 * @return bool
		 */
		public function _include( $file, $folder = 'inc', $include_once = true ){
			if( file_exists( $include = $this->plugin_path( "{$folder}/{$file}" ) ) ){
				if( $include_once ){
					include_once $include;
				}else{
					include $include;
				}
				return true;
			}
			return false;
		}
		/**
		 * Add more 2 user roles teacher and student
		 *
		 * @access public
		 * @return void
		 */
		public function add_user_roles() {

			/* translators: user role */
			_x( 'Instructor', 'User role' );

			add_role(
				$this->teacher_role,
				'Instructor',
				array()
			);
			$course_cap = $this->course_post_type . 's';
			$lesson_cap = $this->lesson_post_type . 's';
			$order_cap	= $this->order_post_type . 's';
			// teacher
			$teacher = get_role( $this->teacher_role );
			$teacher->add_cap( 'delete_published_' . $course_cap );
			$teacher->add_cap( 'edit_published_' . $course_cap );
			$teacher->add_cap( 'edit_' . $course_cap );
			$teacher->add_cap( 'delete_' . $course_cap );

			$teacher->add_cap( 'delete_published_' . $lesson_cap );
			$teacher->add_cap( 'edit_published_' . $lesson_cap );
			$teacher->add_cap( 'edit_' . $lesson_cap );
			$teacher->add_cap( 'delete_' . $lesson_cap );
			$teacher->add_cap( 'publish_' . $lesson_cap );
			$teacher->add_cap( 'upload_files' );
			$teacher->add_cap( 'read' );
			$teacher->add_cap( 'edit_posts' );

			// administrator
			$admin = get_role( 'administrator' );
			$admin->add_cap( 'delete_' . $course_cap );
			$admin->add_cap( 'delete_published_' . $course_cap );
			$admin->add_cap( 'edit_' . $course_cap );
			$admin->add_cap( 'edit_published_' . $course_cap );
			$admin->add_cap( 'publish_' . $course_cap );
			$admin->add_cap( 'delete_private_' . $course_cap );
			$admin->add_cap( 'edit_private_' . $course_cap );
			$admin->add_cap( 'delete_others_' . $course_cap );
			$admin->add_cap( 'edit_others_' . $course_cap );

			$admin->add_cap( 'delete_' . $lesson_cap );
			$admin->add_cap( 'delete_published_' . $lesson_cap );
			$admin->add_cap( 'edit_' . $lesson_cap );
			$admin->add_cap( 'edit_published_' . $lesson_cap );
			$admin->add_cap( 'publish_' . $lesson_cap );
			$admin->add_cap( 'delete_private_' . $lesson_cap );
			$admin->add_cap( 'edit_private_' . $lesson_cap );
			$admin->add_cap( 'delete_others_' . $lesson_cap );
			$admin->add_cap( 'edit_others_' . $lesson_cap );

			$admin->add_cap( 'delete_' . $order_cap );
			$admin->add_cap( 'delete_published_' . $order_cap );
			$admin->add_cap( 'edit_' . $order_cap );
			$admin->add_cap( 'edit_published_' . $order_cap );
			$admin->add_cap( 'publish_' . $order_cap );
			$admin->add_cap( 'delete_private_' . $order_cap );
			$admin->add_cap( 'edit_private_' . $order_cap );
			$admin->add_cap( 'delete_others_' . $order_cap );
			$admin->add_cap( 'edit_others_' . $order_cap );
		}

		/**
		 * Include files of enabled add ons
		 */
		public function include_enable_add_on() {
			$enabled_addons = learn_press_get_enabled_add_ons();
			$add_ons        = learn_press_get_add_ons();

			// Init all enabled addons
			foreach ( (array) $add_ons as $slug => $params ) {
				if ( isset( $enabled_addons[$slug] ) ) {
					if ( !empty( $params['file'] ) && is_file( $params['file'] ) ) {
						include_once( $params['file'] );
					}
				}
			}
		}

		/**
		 * Function include all files in folder
		 *
		 * @param $path   Directory address
		 * @param $ext    array file extension what will include
		 * @param $prefix string Class prefix
		 */
		function include_folder( $path, $ext = array( 'php' ), $prefix = '' ) {
			/*Include all files in payment folder*/
			$sfiles = scandir( $path );
			foreach ( $sfiles as $sfile ) {
				if ( $sfile != '.' && $sfile != '..' ) {
					if ( is_file( $path . "/" . $sfile ) ) {
						$ext_file  = pathinfo( $path . "/" . $sfile );
						$file_name = $ext_file['filename'];

						if ( in_array( $ext_file['extension'], $ext ) ) {
							$class = preg_replace( '/\W/i', '_', $prefix . $file_name );
							if ( !class_exists( $class ) ) {
								require_once $path . "/" . $sfile;
								new $class;
							}
						}
					}
				}
			}
		}

		/**
		 * Enqueue script
		 *
		 * @access public
		 * @return void
		 */
		public function lpr_scripts() {
			wp_enqueue_style( 'lpr-learnpress-css', LP_CSS_URL . 'learnpress.css' );
			wp_enqueue_style( 'lpr-time-circle-css', LP_CSS_URL . 'timer.css' );

			wp_enqueue_script( 'lpr-global', LP_JS_URL . 'global.js' );
			wp_enqueue_script( 'lpr-alert-js', LP_JS_URL . 'jquery.alert.js', array( 'jquery' ) );
			wp_enqueue_script( 'lpr-time-circle-js', LP_JS_URL . 'jquery.timer.js', array( 'jquery', 'lpr-global', 'lpr-alert-js' ) );

			wp_enqueue_script( 'lpr-learnpress-js', LP_JS_URL . 'learnpress.js', array( 'jquery' ), '', true );
		}

	} // end class
}

/**
 * Main instance of plugin
 *
 * @return LearnPress
 * @since  1.0
 * @author thimpress
 */
function LearnPress() {
	_deprecated_function( __FUNCTION__ . '()', '1.0', 'LP()' );
	return LearnPress::instance();
}

function LP(){
	return LearnPress::instance();
}

/**
 * Load the main instance of plugin after all plugins have been loaded
 *
 * @author      TuNguyen
 * @date        04 Mar 2015
 * @since       1.0
 */
function load_learn_press() {
	$GLOBALS['learn_press'] = array();
	$GLOBALS['LearnPress']  = LP();


}

// Done! entry point of the plugin
add_action( 'plugins_loaded', 'load_learn_press' );

