<?php
/*
Plugin Name: LearnPress
Plugin URI: http://thimpress.com/learnpress
Description: LearnPress is a WordPress complete solution for creating a Learning Management System (LMS). It can help you to create courses, lessons and quizzes.
Author: ThimPress
Version: 0.9.6
Author URI: http://thimpress.com
*/

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'LearnPress' ) ) {
    /**
     * Class LearnPress
     */
	class LearnPress {

		/**
         * Current version of the plugin
		 * @var string
		 */
		public $version = '0.9.6';

		/**
         * The single instance of the class
         *
		 * @var object
		 */
		protected static $_instance = null;

		/**
         * Store the url of the plugin
         *
		 * @var string
		 */
		public $plugin_url;

		/**
         * Store the path of the plugin
         *
		 * @var string
		 */
		public $plugin_path;

		/**
		 * Store the session class
		 *
		 * @var      array
		 */
		public $session = null;

        /**
         * Constructor
         */
		public function __construct() {

			// Define the url and path of plugin
			$this->plugin_file = __FILE__;
			$this->plugin_url  = untrailingslashit( plugins_url( '/', __FILE__ ) );
			$this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );

			// defines const
			$this->define_const();

			// hooks
			$this->init_hooks();

			// includes
			$this->includes();

			// load core add ons
			//$this->load_core_add_ons();

			// load payment gateways
			LPR_Gateways::instance()->get_available_payment_gateways();

			// let third parties know that we're ready
			do_action( 'learn_press_loaded' );
			do_action( 'learn_press_register_add_ons' );

            global $learn_press_add_ons;

            $learn_press_add_ons['bundle_activate'] = array(
                'learnpress-course-review',
                'learnpress-import-export',
                'learnpress-prerequisites-courses',
                'learnpress-wishlist'
            );
            $learn_press_add_ons['more'] = array(
                'learnpress-bbpress',
                'learnpress-buddypress',
                'learnpress-course-review',
                'learnpress-import-export',
                'learnpress-prerequisites-courses',
                'learnpress-wishlist'
            );

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
			$this->define( 'LPR_PLUGIN_FILE', __FILE__ );
			$this->define( 'LEARNPRESS_VERSION', $this->version );
			$this->define( 'LPR_PLUGIN_URL', $this->plugin_url() );
			$this->define( 'LPR_PLUGIN_PATH', $this->plugin_path() );
			$this->define( 'LPR_JS_URL', $this->plugin_url( "assets/js/" ) );
			$this->define( 'LPR_CSS_URL', $this->plugin_url( "assets/css/" ) );
			$this->define( 'LPR_ASSIGNMENT_CPT', 'lpr_assignment' );
			$this->define( 'LPR_COURSE_CPT', 'lpr_course' );
			$this->define( 'LPR_LESSON_CPT', 'lpr_lesson' );
			$this->define( 'LPR_QUESTION_CPT', 'lpr_question' );
			$this->define( 'LPR_EVENT_CPT', 'lpr_event' );
			$this->define( 'LPR_QUIZ_CPT', 'lpr_quiz' );
			$this->define( 'LPR_ORDER_CPT', 'lpr_order' );
		}

		/**
		 * Load all add-ons provided by the plugin
		 */
		function load_core_add_ons() {
			// Auto load to include core-addon
			if ( $dirs = array_filter( glob( LPR_PLUGIN_PATH . '/inc/core-addons/*' ), 'is_dir' ) ) {
				foreach ( $dirs as $dir ) {
					if ( file_exists( $addon = $dir . '/init.php' ) ) {
						require_once( $addon );
					}
				}
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

			register_activation_hook( __FILE__, array( 'LPR_Install', 'install' ) );
			// initial some tasks before page load
			add_action( 'init', array( $this, 'init' ) );

			// load enable add-ons
			//add_action( 'init', array( $this, 'include_enable_add_on' ) );

			// user roles
			add_action( 'init', array( $this, 'add_user_roles' ) );

			// admin menu
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );


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
			$session_class = apply_filters( 'learn_press_session_handler', 'LPR_Session' );

			// Class instances
			$this->session = new $session_class();

			// auto include file for admin page
			// example: slug = learn_press_settings -> file = inc/admin/sub-menus/settings.php
			$page = !empty ( $_REQUEST['page'] ) ? $_REQUEST['page'] : null;
			if ( !$page ) return;

			if ( strpos( $page, 'learn_press_' ) === false ) return;
			$file = preg_replace( '!^learn_press_!', '', $page );
			$file = str_replace( '_', '-', $file );
			if ( file_exists( $file = LPR_PLUGIN_PATH . "/inc/admin/sub-menus/{$file}.php" ) ) {
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

			// include core functions
			require_once 'inc/lpr-core-functions.php';
			require_once 'inc/lpr-add-on-functions.php';

			// auto include file for class if class doesn't exists
			require_once 'inc/class.lpr-autoloader.php';

			require_once 'inc/class.lpr-install.php';

			if ( is_admin() ) {
				require_once 'lib/meta-box/meta-box.php';
				//Include admin settings
				require_once 'inc/admin/class.lpr-admin.php';

				require_once 'inc/admin/class.lpr-admin-settings.php';

			} else {

			}

			$this->include_post_types();

			require_once 'inc/class.lpr-session.php';

			require_once 'inc/admin/class.lpr-profile.php';
			require_once 'inc/admin/class.lpr-email.php';

			require_once 'inc/order/class.lpr-order.php';
			require_once 'inc/order/lpr-order-functions.php';

			require_once 'inc/class.lpr-question-type.php';

			// user API
			require_once 'inc/user/class.lpr-user.php';
			require_once 'inc/user/lpr-user-functions.php';

			// course functions
			require_once( 'inc/lpr-course-functions.php' );

			// quiz functions
			require_once 'inc/lpr-quiz-functions.php';

			if ( is_admin() ) {
				require_once( 'inc/admin/class.lpr-admin-assets.php' );

				//Include pointers
				require_once 'inc/admin/pointers/pointers.php';
			} else {
				// assets
				require_once 'inc/class.lpr-assets.php';

				// shortcodes
				require_once 'inc/class.lpr-shortcodes.php';

				// Include short-code file
				require_once 'inc/shortcodes/profile-page.php';


			}
            // include template functions
            require_once( 'inc/lpr-template-functions.php' );
            require_once( 'inc/lpr-template-hooks.php' );
			// settings
			require_once 'inc/class.lpr-settings.php';
			// simple cart
			require_once 'inc/cart/class.lpr-cart.php';
			// payment gateways
			require_once 'inc/gateways/class.lpr-gateway-abstract.php';
			require_once 'inc/gateways/class.lpr-gateways.php';

			//if ( defined( 'DOING_AJAX' ) ) {
			//add ajax-action
			require_once 'inc/admin/class.lpr-admin-ajax.php';
			require_once 'inc/class.lpr-ajax.php';
			require_once 'inc/class.lpr-multi-language.php';
			//}
		}

		/**
		 * Get the plugin url.
		 *
		 * @param string $sub_dir
		 *
		 * @return string
		 */
		public function plugin_url( $sub_dir = '' ) {
			return $this->plugin_url . ( $sub_dir ? "/{$sub_dir}" : '' );
		}

		/**
		 * Get the plugin path.
		 *
		 * @param string $sub_dir
		 *
		 * @return string
		 */
		public function plugin_path( $sub_dir = '' ) {
			return $this->plugin_path . ( $sub_dir ? "/{$sub_dir}" : '' );
		}

		/**
		 * Register for menu
		 *
		 * @access public
		 * @return void
		 */
		public function admin_menu() {
			add_menu_page(
				__( 'Learning Management System', 'learn_press' ),
				__( 'LearnPress', 'learn_press' ),
				'edit_lpr_courses',
				'learn_press',
				'',
				'dashicons-welcome-learn-more',
				'3.14'
			);

			$menu_items = array(
				'statistics' => array(
					'learn_press',
					__( 'Statistics', 'learn_press' ),
					__( 'Statistics', 'learn_press' ),
					'edit_lpr_courses',
					'learn_press_statistics',
					'learn_press_statistic_page'
				),
				'settings'   => array(
					'options-general.php',
					__( 'LearnPress Settings', 'learn_press' ),
					__( 'LearnPress', 'learn_press' ),
					'manage_options',
					'learn_press_settings',
					'learn_press_settings_page'
				),
				'addons'     => array(
					'learn_press',
					__( 'Add-ons', 'learn_press' ),
					__( 'Add-ons', 'learn_press' ),
					'manage_options',
					'learn_press_add_ons',
					'learn_press_add_ons_page'
				)
			);

			// Third-party can be add more items
			$menu_items = apply_filters( 'learn_press_menu_items', $menu_items );

			if ( $menu_items ) foreach ( $menu_items as $item ) {
				call_user_func_array( 'add_submenu_page', $item );
			}
		}

		/**
		 * Add more 2 user roles teacher and student
		 *
		 * @access public
		 * @return void
		 */
		public function add_user_roles() {			

			/* translators: user role */
			_x('Instructor', 'User role');			

			add_role(
				'lpr_teacher',
				'Instructor',
				array()
			);
			// teacher
			$teacher = get_role( 'lpr_teacher' );
			$teacher->add_cap( 'delete_published_lpr_courses' );
			$teacher->add_cap( 'edit_published_lpr_courses' );
			$teacher->add_cap( 'edit_lpr_courses' );
			$teacher->add_cap( 'delete_lpr_courses' );

			$teacher->add_cap( 'delete_published_lpr_lessons' );
			$teacher->add_cap( 'edit_published_lpr_lessons' );
			$teacher->add_cap( 'edit_lpr_lessons' );
			$teacher->add_cap( 'delete_lpr_lessons' );
			$teacher->add_cap( 'publish_lpr_lessons' );
			$teacher->add_cap( 'upload_files' );
			$teacher->add_cap( 'read' );
			$teacher->add_cap( 'edit_posts' );

			// administrator
			$admin = get_role( 'administrator' );
			$admin->add_cap( 'delete_lpr_courses' );
			$admin->add_cap( 'delete_published_lpr_courses' );
			$admin->add_cap( 'edit_lpr_courses' );
			$admin->add_cap( 'edit_published_lpr_courses' );
			$admin->add_cap( 'publish_lpr_courses' );
			$admin->add_cap( 'delete_private_lpr_courses' );
			$admin->add_cap( 'edit_private_lpr_courses' );
			$admin->add_cap( 'delete_others_lpr_courses' );
			$admin->add_cap( 'edit_others_lpr_courses' );

			$admin->add_cap( 'delete_lpr_lessons' );
			$admin->add_cap( 'delete_published_lpr_lessons' );
			$admin->add_cap( 'edit_lpr_lessons' );
			$admin->add_cap( 'edit_published_lpr_lessons' );
			$admin->add_cap( 'publish_lpr_lessons' );
			$admin->add_cap( 'delete_private_lpr_lessons' );
			$admin->add_cap( 'edit_private_lpr_lessons' );
			$admin->add_cap( 'delete_others_lpr_lessons' );
			$admin->add_cap( 'edit_others_lpr_lessons' );

			$admin->add_cap( 'delete_lpr_orders' );
			$admin->add_cap( 'delete_published_lpr_orders' );
			$admin->add_cap( 'edit_lpr_orders' );
			$admin->add_cap( 'edit_published_lpr_orders' );
			$admin->add_cap( 'publish_lpr_orders' );
			$admin->add_cap( 'delete_private_lpr_orders' );
			$admin->add_cap( 'edit_private_lpr_orders' );
			$admin->add_cap( 'delete_others_lpr_orders' );
			$admin->add_cap( 'edit_others_lpr_orders' );
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
			wp_enqueue_style( 'lpr-learnpress-css', LPR_CSS_URL . 'learnpress.css' );
			wp_enqueue_script( 'lpr-learnpress-js', LPR_JS_URL . 'learnpress.js', array( 'jquery' ), '', true );

			wp_enqueue_script( 'lpr-alert-js', LPR_JS_URL . 'jquery.alert.js', array( 'jquery' ) );

			wp_enqueue_style( 'lpr-time-circle-css', LPR_CSS_URL . 'timer.css' );
			wp_enqueue_script( 'lpr-time-circle-js', LPR_JS_URL . 'jquery.timer.js', array( 'jquery' ) );

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
	$GLOBALS['LearnPress']  = LearnPress();

}

// Done! entry point of the plugin
add_action( 'plugins_loaded', 'load_learn_press' );