<?php
/**
 * Plugin Name: LearnPress
 * Plugin URI: http://thimpress.com/learnpress
 * Description: LearnPress is a WordPress complete solution for creating a Learning Management System (LMS). It can help you to create courses, lessons and quizzes.
 * Author: ThimPress
 * Version: 4.2.5.3
 * Author URI: http://thimpress.com
 * Requires at least: 6.2
 * Requires PHP: 7.0
 * Text Domain: learnpress
 * Domain Path: /languages/
 *
 * @package LearnPress
 */

/**
 * Prevent loading this file directly
 */

use LearnPress\ExternalPlugin\Elementor\LPElementor;
use LearnPress\Shortcodes\Course\FilterCourseShortcode;
use LearnPress\Shortcodes\ListInstructorsShortcode;
use LearnPress\Shortcodes\SingleInstructorShortcode;
use LearnPress\Shortcodes\CourseMaterialShortcode;
use LearnPress\TemplateHooks\Course\FilterCourseTemplate;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\Instructor\ListInstructorsTemplate;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\Profile\ProfileInstructorStatisticsTemplate;
use LearnPress\TemplateHooks\Profile\ProfileStudentStatisticsTemplate;
use LearnPress\TemplateHooks\Course\CourseMaterialTemplate;
use LearnPress\Widgets\LPRegisterWidget;

defined( 'ABSPATH' ) || exit();

if ( ! defined( 'LP_PLUGIN_FILE' ) ) {
	define( 'LP_PLUGIN_FILE', __FILE__ );
	include_once 'inc/lp-constants.php';
}

if ( ! class_exists( 'LearnPress' ) ) {
	/**
	 * Class LearnPress
	 *
	 * Version 3.0.1
	 */
	class LearnPress {
		/**
		 * Current version of the plugin
		 *
		 * @var string
		 */
		public $version = LEARNPRESS_VERSION;
		/**
		 * Version database require, use for this LP source
		 *
		 * @var int
		 */
		public $db_version = 4;

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
		 * @var LP_Template
		 */
		public $template = null;

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

		public $gateways = null;

		/**
		 * LearnPress constructor.
		 */
		protected function __construct() {
			/*if ( isset( $_POST['action'] ) && 'heartbeat' === $_POST['action'] ) {
				return;
			}*/

			try {
				if ( self::$_instance ) {
					return;
				}
				self::$_instance = $this;

				// Update for case compare version of LP if LEARNPRESS_VERSION undefined
				if ( is_admin() ) {
					update_option( 'learnpress_version', $this->version );
				}

				// Define constant .
				$this->plugin_defines();

				// define table prefixes .
				$this->define_tables();

				// Include files .
				$this->includes();

				// hooks .
				$this->init_hooks();
			} catch ( Throwable $e ) {
				error_log( __METHOD__ . ': ' . $e->getMessage() );
			}
		}

		/**
		 * Define constant.
		 */
		protected function plugin_defines() {
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
			include_once LP_PLUGIN_PATH . 'vendor/autoload.php';

			// Include required files load anywhere, both frontend and backend.
			$this->include_files_global();

			// include files when LP ready run - after setup success .
			if ( ! LP_Install::instance()->tables_install_done() ) {
				return;
			}

			// Include required files Backend.
			$this->include_files_admin();

			// Include required files Frontend.
			$this->include_files_frontend();

			new LP_Query();
		}

		/**
		 * load files anywhere, both frontend and backend
		 *
		 * @return void
		 */
		private function include_files_global() {
			include_once 'inc/class-lp-multi-language.php';

			// Filter query .
			include_once 'inc/Filters/class-lp-filter.php';
			include_once 'inc/Filters/class-lp-post-type-filter.php';
			include_once 'inc/Filters/class-lp-course-filter.php';
			include_once 'inc/Filters/class-lp-order-filter.php';
			include_once 'inc/Filters/class-lp-session-filter.php';
			include_once 'inc/Filters/class-lp-section-filter.php';
			include_once 'inc/Filters/class-lp-section-items-filter.php';
			include_once 'inc/Filters/class-lp-question-filter.php';
			include_once 'inc/Filters/class-lp-user-items-filter.php';
			include_once 'inc/Filters/class-lp-user-item-meta-filter.php';
			include_once 'inc/Filters/class-lp-quiz-questions-filter.php';
			include_once 'inc/Filters/class-lp-question-answers-filter.php';
			include_once 'inc/Filters/class-lp-question-answermeta-filter.php';

			// Query Database .
			include_once 'inc/Databases/class-lp-db.php';
			include_once 'inc/Databases/class-lp-order-db.php';
			include_once 'inc/Databases/class-lp-course-db.php';
			include_once 'inc/Databases/class-lp-lesson-db.php';
			include_once 'inc/Databases/class-lp-section-db.php';
			include_once 'inc/Databases/class-lp-section-items-db.php';
			include_once 'inc/Databases/class-lp-quiz-db.php';
			include_once 'inc/Databases/class-lp-quiz-questions-db.php';
			include_once 'inc/Databases/class-lp-question-answers-db.php';
			include_once 'inc/Databases/class-lp-sessions-db.php';
			include_once 'inc/Databases/class-lp-question-db.php';
			include_once 'inc/Databases/class-lp-user-items-db.php';
			include_once 'inc/Databases/class-lp-user-item-meta-db.php';
			include_once 'inc/Databases/class-lp-user-item-results-db.php';
			include_once 'inc/Databases/class-thim-cace-db.php';
			include_once 'inc/Databases/class-lp-material-db.php';
			include_once 'inc/Databases/class-lp-wpdb-multiple.php';

			// File system .
			include_once 'inc/class-lp-file-system.php';

			// File helper
			include_once 'inc/class-lp-helper.php';

			// Template Hooks.
			ListCoursesTemplate::instance();
			ListInstructorsTemplate::instance();
			SingleInstructorTemplate::instance();
			ProfileInstructorStatisticsTemplate::instance();
			ProfileStudentStatisticsTemplate::instance();
			FilterCourseTemplate::instance();

			CourseMaterialTemplate::instance();

			// Models
			include_once 'inc/Models/class-lp-rest-response.php';
			include_once 'inc/Models/steps/class-lp-group-step.php';
			include_once 'inc/Models/steps/class-lp-step.php';
			include_once 'inc/Models/class-lp-course-extra-info-fast-query-model.php';

			// Handle steps.
			include_once 'inc/handle-steps/class-lp-handle-steps.php';
			include_once 'inc/handle-steps/class-lp-handle-upgrade-db-steps.php';

			// LP Cache
			include_once 'inc/cache/class-lp-cache.php';
			include_once 'inc/cache/class-lp-courses-cache.php';
			include_once 'inc/cache/class-lp-course-cache.php';
			include_once 'inc/cache/class-lp-quiz-cache.php';
			include_once 'inc/cache/class-lp-question-cache.php';
			include_once 'inc/cache/class-lp-session-cache.php';
			include_once 'inc/cache/class-lp-settings-cache.php';
			include_once 'inc/cache/class-lp-user-items-cache.php';

			// Background processes.
			include_once 'inc/libraries/wp-background-process/wp-background-processing.php';
			include_once 'inc/background-process/abstract-lp-async-request.php';
			//include_once 'inc/background-process/abstract-lp-async-task.php';
			include_once 'inc/background-process/class-lp-background-single-course.php';
			include_once 'inc/background-process/class-lp-background-single-email.php';
			include_once 'inc/background-process/class-lp-background-thim-cache.php';

			// Assets object
			include_once 'inc/class-lp-asset-key.php';
			include_once 'inc/abstracts/abstract-assets.php';

			// Debug class
			include_once 'inc/class-lp-debug.php';

			include_once 'inc/class-lp-settings.php';
			include_once 'inc/abstract-settings.php';
			include_once 'inc/settings/abstract-settings-page.php';
			include_once 'inc/settings/class-lp-settings-courses.php';
			include_once 'inc/class-lp-global.php';
			include_once 'inc/class-lp-datetime.php';

			// Register custom-post-type and taxonomies .
			include_once 'inc/custom-post-types/abstract.php';
			include_once 'inc/custom-post-types/course.php';
			include_once 'inc/custom-post-types/lesson.php';
			include_once 'inc/custom-post-types/quiz.php';
			include_once 'inc/custom-post-types/question.php';
			include_once 'inc/custom-post-types/order.php';

			include_once 'inc/interfaces/interface-curd.php';
			include_once 'inc/abstracts/abstract-array-access.php';
			include_once 'inc/abstracts/abstract-object-data.php';
			include_once 'inc/abstracts/abstract-post-data.php';

			include_once 'inc/curds/class-lp-course-curd.php';
			include_once 'inc/curds/class-lp-section-curd.php';
			include_once 'inc/curds/class-lp-lesson-curd.php';
			include_once 'inc/curds/class-lp-quiz-curd.php';
			include_once 'inc/curds/class-lp-question-curd.php';
			include_once 'inc/curds/class-lp-order-curd.php';
			include_once 'inc/curds/class-lp-user-curd.php';
			include_once 'inc/curds/class-lp-user-item-curd.php';

			include_once 'inc/course/class-lp-course-item.php';
			include_once 'inc/question/class-lp-question.php';
			include_once 'inc/course/class-lp-course-section.php';
			include_once 'inc/course/class-lp-course-no-required-enroll.php';
			include_once 'inc/user-item/class-lp-user-item.php';
			include_once 'inc/user-item/class-lp-user-item-course.php';

			include_once 'inc/lp-deprecated.php'; // Will remove if Eduma and guest update all 4.0.0
			include_once 'inc/lp-core-functions.php';
			include_once 'inc/class-lp-autoloader.php';

			include_once 'inc/lp-webhooks.php';
			include_once 'inc/class-lp-request-handler.php';

			include_once 'inc/admin/helpers/class-lp-plugins-helper.php';

			// Todo: tungnx check those files.
			include_once 'inc/abstracts/abstract-object-query.php';
			include_once 'inc/class-lp-course-query.php';
			include_once 'inc/abstracts/abstract-addon.php';
			include_once 'inc/class-lp-thumbnail-helper.php';
			include_once 'inc/cache.php';

			// Class handle check db of LP need to upgrade?
			include_once 'inc/admin/class-lp-updater.php';

			include_once 'inc/course/lp-course-functions.php';
			include_once 'inc/course/abstract-course.php';
			include_once 'inc/course/class-lp-course.php';
			include_once 'inc/quiz/lp-quiz-functions.php';
			include_once 'inc/quiz/class-lp-quiz.php';
			include_once 'inc/lesson/lp-lesson-functions.php';
			include_once 'inc/order/lp-order-functions.php';
			include_once 'inc/order/class-lp-order.php';

			include_once 'inc/user/lp-user-functions.php';
			include_once 'inc/user/class-lp-user-factory.php';
			include_once 'inc/user/abstract-lp-user.php';
			include_once 'inc/user/class-lp-user.php';
			include_once 'inc/user/class-lp-profile.php';
			include_once 'inc/user-item/class-lp-user-item.php';
			include_once 'inc/user-item/class-lp-user-item-course.php';
			include_once 'inc/user-item/class-lp-user-item-quiz.php';
			include_once 'inc/user-item/class-lp-quiz-results.php';

			// Shortcodes.
			SingleInstructorShortcode::instance();
			ListInstructorsShortcode::instance();
			CourseMaterialShortcode::instance();
			FilterCourseShortcode::instance();
			include_once 'inc/class-lp-shortcodes.php';

			// include template functions .
			include_once 'inc/lp-template-functions.php';
			include_once 'inc/templates/abstract-template.php';
			//include_once 'inc/class-lp-template.php';

			// Cart
			include_once 'inc/cart/class-lp-cart.php';
			include_once 'inc/cart/lp-cart-functions.php';

			// Block Templates
			include_once 'inc/block-template/class-abstract-block-template.php';
			include_once 'inc/block-template/class-block-template-handle.php';

			// API
			include_once 'inc/abstracts/abstract-rest-api.php';
			include_once 'inc/abstracts/abstract-rest-controller.php';
			include_once 'inc/rest-api/class-lp-core-api.php';
			include_once 'inc/rest-api/class-lp-admin-core-api.php';

			/** Jwt */
			include_once 'inc/jwt/class-jwt-auth.php';

			LPRegisterWidget::instance();
			include_once 'inc/class-lp-widget.php';
			include_once 'inc/lp-widget-functions.php';

			// TODO: update frontend editor before move to function include_files_admin.
			include_once 'inc/admin/views/meta-boxes/class-lp-meta-box.php';

			include_once 'inc/class-lp-page-controller.php';

			include_once 'inc/gateways/class-lp-gateway-abstract.php';
			include_once 'inc/gateways/class-lp-gateways.php';
			new LP_Gateways();
		}

		/**
		 * Include file run on backend
		 */
		private function include_files_admin() {
			if ( ! is_admin() ) {
				return;
			}

			include_once 'inc/admin/class-lp-admin-ajax.php';

			include_once 'inc/admin/class-lp-admin-notice.php';

			// File handle install LP
			include_once 'inc/class-lp-install.php';

			// Meta box helper
			include_once 'inc/admin/meta-box/class-lp-meta-box-helper.php';

			include_once 'inc/admin/class-lp-admin.php';
			// include_once 'inc/admin/settings/abstract-settings-page.php';
		}

		/**
		 * Include file run on frontend
		 */
		private function include_files_frontend() {
			if ( is_admin() ) {
				return;
			}

			include_once 'inc/class-lp-assets.php';

			include_once 'inc/course/class-model-user-can-view-course-item.php';

			include_once 'inc/class-lp-ajax.php';

			include_once 'inc/class-lp-session-handler.php';
		}

		/**
		 * Initial common hooks
		 */
		public function init_hooks() {
			// Add links setting|document|addon on plugins page.
			add_filter( 'plugin_action_links_' . LP_PLUGIN_BASENAME, array( $this, 'plugin_links' ) );

			register_activation_hook( LP_PLUGIN_FILE, array( $this, 'on_activate' ) );
			register_deactivation_hook( LP_PLUGIN_FILE, array( $this, 'on_deactivate' ) );
			// add_action( 'deactivate_' . LP_PLUGIN_BASENAME, array( $this, 'on_deactivate' ) );

			if ( ! LP_Install::instance()->tables_install_done() ) {
				return;
			}

			//add_action( 'wp_loaded', array( $this, 'wp_loaded' ), 20 );
			//add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), - 10 );
			add_action(
				'plugin_loaded',
				function ( $plugin ) {
					// For check wp_remote call normally of WP
					if ( ! empty( LP_Request::get_param( 'lp_test_wp_remote' ) ) ) {
						echo '[TEST_REMOTE]';
						die;
					}
				}
			);

			// Check require version thim-core on Backend.
			if ( is_admin() ) {
				add_action( 'before_thim_core_init', array( $this, 'check_thim_core_version_require' ) );
			}

			// Save key purchase addon when install via file download from Thimpress.
			add_action(
				'upgrader_process_complete',
				function ( $plugin_upgrader ) {
					if ( ! empty( $plugin_upgrader->result ) ) {
						$res         = $plugin_upgrader->result;
						$path_source = $res['destination'] ?? '';
						if ( empty( $path_source ) ) {
							return;
						}

						$key_purchase_path = realpath( $path_source . '/purchase-code.txt' );
						if ( file_exists( $key_purchase_path ) ) {
							$purchase_code_content = file_get_contents( $key_purchase_path );
							if ( empty( $purchase_code_content ) ) {
								return;
							}

							$addon_slug = $res['destination_name'] ?? '';
							if ( empty( $addon_slug ) ) {
								return;
							}

							// Call active purchase code for site.
							LP_Manager_Addons::instance()->active_site( $addon_slug, $purchase_code_content );
						}
					}
				}
			);
		}

		/**
		 * Add links to Documentation and Extensions in plugin's list of action links
		 *
		 * @param array $links Array of action links
		 *
		 * @return array
		 * @since 4.3.11
		 *
		 */
		public function plugin_links( array $links ): array {
			$links[] = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=learn-press-settings' ), __( 'Settings', 'learnpress' ) );
			$links[] = sprintf( '<a href="%s" target="_blank">%s</a>', 'https://docspress.thimpress.com/learnpress-4-0/', __( 'Documentation', 'learnpress' ) );
			$links[] = sprintf( '<a href="%s" target="_blank">%s</a>', get_admin_url() . '/admin.php?page=learn-press-addons', __( 'Add-ons', 'learnpress' ) );

			return $links;
		}

		/**
		 * Trigger this function while activating Learnpress.
		 *
		 * @since 3.0.0
		 * @version 4.1.4.1
		 */
		public function on_activate() {
			LP_Install::instance()->on_activate();
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
		 * @deprecated 4.2.2
		 */
		public function wp_loaded() {
			_deprecated_function( __METHOD__, '4.2.2' );
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
		 * @version 1.0.3
		 */
		public function plugins_loaded() {
			try {
				do_action( 'learnpress/hook/before-addons-call-hook-learnpress-ready' );

				// Polylang
				if ( defined( 'POLYLANG_VERSION' ) ) {
					include_once 'inc/ExternalPlugin/Polylang/class-lp-polylang.php';
					LP_Polylang::instance();
				}

				// For plugin Elementor
				if ( defined( 'ELEMENTOR_VERSION' ) ) {
					LPElementor::instance();
				}

				$this->init();

				include_once 'inc/lp-template-hooks.php';

				/**
				 * Check version addons valid version require.
				 * If not valid will be to deactivate.
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

					// $lp_addon_info    = get_plugin_data( WP_PLUGIN_DIR . '/' . $lp_addon );
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
			} catch ( Throwable $e ) {
				error_log( __METHOD__ . ': ' . $e->getMessage() );
			}
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
			$this->template = LP_Template::instance();
			$templates      = (array) $this->template->get_templates();

			return $templates[ $type ] ?? $this->template;
		}

		/**
		 * Init LearnPress when WP initialises
		 */
		public function init() {
			$this->api       = new LP_Core_API();
			$this->admin_api = new LP_Admin_Core_API();

			$this->get_session();

			$this->settings = $this->settings();

			if ( $this->is_request( 'frontend' ) ) {
				$this->get_cart();
			}

			// Email hook notify
			include_once 'inc/emails/class-lp-email-hooks.php';
			// Init emails
			LP_Emails::instance();
		}

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
		public function get_cart(): LP_Cart {
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
					'<a href="https://docspress.thimpress.com/upgrade-database-how-to-fix-some-issue/">here</a>'
				);
				?>
				<div class="notice notice-error">
					<p><?php echo wp_kses_post( $message ); ?></p>
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
			if ( is_null( self::$_instance ) ) {
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
 * Done! entry point of the plugin
 * Create new instance of LearnPress and put it to global
 */
$GLOBALS['LearnPress'] = LearnPress::instance();
