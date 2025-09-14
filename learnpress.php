<?php
/**
 * Plugin Name: LearnPress
 * Plugin URI: http://thimpress.com/learnpress
 * Description: LearnPress is a WordPress complete solution for creating a Learning Management System (LMS). It can help you to create courses, lessons and quizzes.
 * Author: ThimPress
 * Version: 4.2.9.2
 * Author URI: http://thimpress.com
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: learnpress
 * Domain Path: /languages/
 *
 * @package LearnPress
 */

use LearnPress\Ajax\EditQuestionAjax;
use LearnPress\Ajax\EditQuizAjax;
use LearnPress\Ajax\LessonAjax;
use LearnPress\Ajax\LoadContentViaAjax;
use LearnPress\Background\LPBackgroundTrigger;
use LearnPress\ExternalPlugin\Elementor\LPElementor;
use LearnPress\ExternalPlugin\RankMath\LPRankMath;
use LearnPress\ExternalPlugin\YoastSeo\LPYoastSeo;
use LearnPress\Gutenberg\GutenbergHandleMain;
use LearnPress\Ajax\EditCurriculumAjax;
use LearnPress\Ajax\SendEmailAjax;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\Shortcodes\Course\FilterCourseShortcode;
use LearnPress\Shortcodes\ListInstructorsShortcode;
use LearnPress\Shortcodes\SingleInstructorShortcode;
use LearnPress\Shortcodes\CourseMaterialShortcode;
use LearnPress\TemplateHooks\Admin\AdminEditQizTemplate;
use LearnPress\TemplateHooks\Admin\AdminEditQuestionTemplate;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LearnPress\TemplateHooks\Course\FilterCourseTemplate;
use LearnPress\TemplateHooks\Course\ListCoursesRelatedTemplate;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;
use LearnPress\TemplateHooks\Course\SingleCourseOfflineTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseClassicTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\TemplateHooks\Instructor\ListInstructorsTemplate;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\Profile\ProfileGeneralInfoTemplate;
use LearnPress\TemplateHooks\Profile\ProfileInstructorStatisticsTemplate;
use LearnPress\TemplateHooks\Profile\ProfileQuizzesTemplate;
use LearnPress\TemplateHooks\Profile\ProfileOrdersTemplate;
use LearnPress\TemplateHooks\Profile\ProfileOrderTemplate;
use LearnPress\TemplateHooks\Profile\ProfileStudentStatisticsTemplate;
use LearnPress\TemplateHooks\Course\CourseMaterialTemplate;
use LearnPress\Widgets\LPRegisterWidget;

defined( 'ABSPATH' ) || exit();

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
		public $version = '';
		/**
		 * Version database require, use for this LP source
		 *
		 * @var int
		 */
		public $db_version = 5;

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
		 * @var LP_Cart object
		 */
		public $cart = false;

		/**
		 * @var LP_Settings
		 */
		public $settings = null;

		/**
		 * @var array
		 */
		public $query_vars = array();

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

		public static $time_limit_default_of_sever = 0;

		/**
		 * LearnPress constructor.
		 */
		private function __construct() {
			/*if ( isset( $_POST['action'] ) && 'heartbeat' === $_POST['action'] ) {
				return;
			}*/

			try {
				$this->prepare_before_handle();

				if ( ! LP_Install::instance()->tables_install_done() ) {
					return;
				}

				// Must handle in hook init of WordPress, when loaded plugins, theme, user.
				add_action( 'init', [ $this, 'lp_main_handle' ], - 1000 );

				// hooks .
				$this->hooks();
			} catch ( Throwable $e ) {
				error_log( __METHOD__ . ': ' . $e->getMessage() );
			}
		}

		/**
		 * Prepare before handle.
		 * 1.Load constants and includes files.
		 * 2.Get default time limit of server.
		 * 3.Update version of LP undefined.
		 *
		 * @return void
		 * @since 4.2.7.6
		 * @version 1.0.0
		 */
		public function prepare_before_handle() {
			// Define constant .
			$this->plugin_defines();

			self::$time_limit_default_of_sever = ini_get( 'max_execution_time' );

			// Update for case compare version of LP if LEARNPRESS_VERSION undefined
			$this->version = LEARNPRESS_VERSION;
			if ( is_admin() ) {
				$learn_press_version = get_option( 'learnpress_version', '' );
				if ( $learn_press_version !== $this->version ) {
					if ( empty( $learn_press_version ) ) { // Case user install new
						// Set using modern layout for new installation.
						update_option( 'learn_press_layout_single_course', 'modern' );
					}

					update_option( 'learnpress_version', $this->version );
				}
			}

			// define table prefixes .
			$this->define_tables();

			// Include files .
			$this->includes();
		}

		/**
		 * Define constant.
		 */
		protected function plugin_defines() {
			if ( ! defined( 'LP_PLUGIN_FILE' ) ) {
				define( 'LP_PLUGIN_FILE', __FILE__ );
				include_once 'inc/lp-constants.php';
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
			);

			foreach ( $tables as $short_name ) {
				$table_name            = $wpdb->prefix . LP_TABLE_PREFIX . $short_name;
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

			// Filter query .
			include_once 'inc/Filters/class-lp-filter.php';
			include_once 'inc/Filters/class-lp-post-type-filter.php';
			include_once 'inc/Filters/class-lp-post-meta-filter.php';
			include_once 'inc/Filters/class-lp-user-filter.php';
			include_once 'inc/Filters/class-lp-course-filter.php';
			include_once 'inc/Filters/class-lp-course-json-filter.php';
			include_once 'inc/Filters/class-lp-order-filter.php';
			include_once 'inc/Filters/class-lp-session-filter.php';
			include_once 'inc/Filters/class-lp-section-filter.php';
			include_once 'inc/Filters/class-lp-section-items-filter.php';
			include_once 'inc/Filters/class-lp-question-filter.php';
			include_once 'inc/Filters/class-lp-quiz-filter.php';
			include_once 'inc/Filters/class-lp-user-items-filter.php';
			include_once 'inc/Filters/class-lp-user-item-meta-filter.php';
			include_once 'inc/Filters/class-lp-quiz-filter.php';
			include_once 'inc/Filters/class-lp-quiz-questions-filter.php';
			include_once 'inc/Filters/class-lp-question-answers-filter.php';
			include_once 'inc/Filters/class-lp-question-answermeta-filter.php';

			// Query Database .
			include_once 'inc/Databases/class-lp-db.php';
			include_once 'inc/Databases/class-lp-course-json-db.php';
			include_once 'inc/Databases/class-lp-order-db.php';
			include_once 'inc/Databases/class-lp-post-db.php';
			include_once 'inc/Databases/class-lp-post-meta-db.php';
			include_once 'inc/Databases/class-lp-user-db.php';
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
			include_once 'inc/Databases/class-thim-cache-db.php';
			include_once 'inc/Databases/class-lp-material-db.php';
			include_once 'inc/Databases/class-lp-statistics-db.php';

			// File system .
			include_once 'inc/class-lp-file-system.php';

			// File helper
			include_once 'inc/class-lp-helper.php';

			// Template Hooks.
			ListCoursesTemplate::instance();
			ListCoursesRelatedTemplate::instance();
			ListInstructorsTemplate::instance();
			SingleCourseTemplate::instance();
			SingleCourseOfflineTemplate::instance();
			SingleCourseModernLayout::instance();
			SingleCourseClassicTemplate::instance();
			SingleInstructorTemplate::instance();
			ProfileInstructorStatisticsTemplate::instance();
			ProfileStudentStatisticsTemplate::instance();
			ProfileOrdersTemplate::instance();
			ProfileOrderTemplate::instance();
			ProfileGeneralInfoTemplate::instance();
			FilterCourseTemplate::instance();
			ProfileQuizzesTemplate::instance();

			// Admin template hooks.
			AdminEditCurriculumTemplate::instance();
			AdminEditQizTemplate::instance();
			AdminEditQuestionTemplate::instance();
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
			LPBackgroundTrigger::instance();
			include_once 'inc/libraries/wp-background-process/wp-background-processing.php';
			include_once 'inc/background-process/abstract-lp-async-request.php';
			//include_once 'inc/background-process/abstract-lp-async-task.php';
			include_once 'inc/background-process/class-lp-background-single-course.php';
			include_once 'inc/background-process/class-lp-background-single-email.php';

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

			include_once 'inc/lp-webhooks.php'; // Addon learnpress-2checkout-payment v4.0.1 is using, when update v4.0.2 don't need load it.
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
			//include_once 'inc/lesson/lp-lesson-functions.php';
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
			//ListCourseRecentShortcode::instance();
			include_once 'inc/class-lp-shortcodes.php';

			// include template functions .
			include_once 'inc/lp-template-functions.php';
			include_once 'inc/templates/abstract-template.php';
			//include_once 'inc/class-lp-template.php';

			// Cart
			include_once 'inc/cart/class-lp-cart.php';
			include_once 'inc/cart/lp-cart-functions.php';

			// Block Templates
			//include_once 'inc/block-template/class-abstract-block-template.php';
			//include_once 'inc/block-template/class-block-template-handle.php';
			GutenbergHandleMain::instance();

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
			LP_Page_Controller::instance();

			include_once 'inc/gateways/class-lp-gateway-abstract.php';
			include_once 'inc/gateways/class-lp-gateways.php';
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
		 * Main instance of LearnPress.
		 * Must load on "init" hook of WordPress.
		 * 1. Load text domain.
		 * 2. Handle lp ajax.
		 *
		 * @return void
		 * @version 4.2.7.6
		 * @version 1.0.1
		 */
		public function lp_main_handle() {
			try {
				// Load text domain.
				$this->load_plugin_text_domain();

				// Polylang
				if ( defined( 'POLYLANG_VERSION' ) ) {
					include_once 'inc/ExternalPlugin/Polylang/class-lp-polylang.php';
					LP_Polylang::instance();
				}

				// For plugin Elementor
				if ( defined( 'ELEMENTOR_VERSION' ) ) {
					LPElementor::instance();
				}

				// For plugin WPSEO
				if ( defined( 'WPSEO_FILE' ) ) {
					LPYoastSeo::instance();
				}

				// For plugin RankMath
				if ( defined( 'RANK_MATH_VERSION' ) ) {
					LPRankMath::instance();
				}

				$this->api       = new LP_Core_API();
				$this->admin_api = new LP_Admin_Core_API();
				$this->get_session();
				$this->settings = $this->settings();
				if ( $this->is_request( 'frontend' ) ) {
					$this->get_cart();
				}

				// Init emails
				LP_Emails::instance();
				// Email hook notify
				include_once 'inc/emails/class-lp-email-hooks.php';

				if ( is_admin() ) {
					$this->check_addons_version_valid();
				}

				// let third parties know that we're ready .
				do_action( 'learn-press/ready' );

				// For addon sorting choice old <= v4.0.1
				if ( class_exists( 'LP_Addon_Sorting_Choice_Preload' ) ) {
					if ( version_compare( LP_ADDON_SORTING_CHOICE_VER, '4.0.1', '<=' ) ) {
						$lp_addon_sorting_choice = new LP_Addon_Sorting_Choice();
						$lp_addon_sorting_choice->init();
					}
				}

				/**
				 * Init gateways, to load all payment gateways, catch callback.
				 * Must be call after learn-press/ready to register hook of addon.
				 */
				LP_Gateways::instance();

				/**
				 * Fixed temporary for emails of Announcement v4.0.6, Assignment v4.1.1 addons.
				 * @since 4.2.7.4
				 * When 2 addons update to new version, will remove this code.
				 */
				if ( class_exists( 'LP_Addon_Announcements_Preload' ) ) {
					if ( version_compare( LP_ADDON_ANNOUNCEMENTS_VER, '4.0.6', '<=' ) ) {
						$addon_announcement = LP_Addon_Announcements_Preload::$addon;
						$addon_announcement->emails_setting();
					}
				}
				if ( class_exists( 'LP_Addon_Assignment_Preload' ) ) {
					if ( version_compare( LP_ADDON_ASSIGNMENT_VER, '4.1.1', '<=' ) ) {
						$addon_assignment = LP_Addon_Assignment_Preload::$addon;
						$addon_assignment->emails_setting();
					}
				}
			} catch ( Throwable $e ) {
				LP_Debug::error_log( $e );
			}
		}

		/**
		 * Check version addons valid version require.
		 * If not valid will be to deactivate.
		 * Reload page, so not affect to hook "learn-press/ready"
		 */
		public function check_addons_version_valid() {
			$addons_valid = true;
			$plugins      = get_option( 'active_plugins' );

			$list_lp_addon_activated = preg_grep( '/^learnpress-.*/i', $plugins );
			foreach ( $list_lp_addon_activated as $lp_addon ) {
				$lp_addon_info = get_file_data(
					WP_PLUGIN_DIR . '/' . $lp_addon,
					array(
						'Require_LP_Version' => 'Require_LP_Version',
						'Version'            => 'Version',
					)
				);

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
		}

		/**
		 * Initial common hooks
		 */
		public function hooks() {
			/**
			 * Handle lp ajax.
			 * Set priority after register_post_type to register capabilities for post type of LP.
			 */
			add_action(
				'init',
				function () {
					LoadContentViaAjax::catch_lp_ajax();
					LessonAjax::catch_lp_ajax();
					EditCurriculumAjax::catch_lp_ajax();
					EditQuizAjax::catch_lp_ajax();
					EditQuestionAjax::catch_lp_ajax();
					SendEmailAjax::catch_lp_ajax();
				},
				11
			);

			// Add links setting|document|addon on plugins page.
			add_filter( 'plugin_action_links_' . LP_PLUGIN_BASENAME, array( $this, 'plugin_links' ) );

			register_activation_hook( LP_PLUGIN_FILE, array( $this, 'on_activate' ) );
			register_deactivation_hook( LP_PLUGIN_FILE, array( $this, 'on_deactivate' ) );

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

			// Clear cache UserModel when save user.
			add_action(
				'wp_update_user',
				function ( $user_id ) {
					$user = UserModel::find( $user_id, true );
					$user->clean_caches();
				}
			);

			// For temporary fix issue security of wp comments. Is it error of WP, not LP, LP only call to comments_template function.
			add_filter(
				'comments_array',
				function ( $comments_flat, $post_id ) {
					// Check if post type is course or item's course (lesson, quiz...)
					$post_type           = get_post_type( $post_id );
					$course_item_types   = CourseModel::item_types_support();
					$course_item_types[] = LP_COURSE_CPT;
					if ( ! in_array( $post_type, $course_item_types ) ) {
						return $comments_flat;
					}

					foreach ( $comments_flat as $key => $comment ) {
						$comment->comment_content = wp_kses_post( $comment->comment_content );
						$comments_flat[ $key ]    = $comment;
					}

					return $comments_flat;
				},
				10,
				2
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
			$links[] = sprintf( '<a href="%s" target="_blank">%s</a>', 'https://docs.thimpress.com/learnpress/', __( 'Documentation', 'learnpress' ) );
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
		 * Handle load text domain for LearnPress.
		 *
		 * @since 4.2.7.4
		 * @version 1.0.1
		 */
		public function load_plugin_text_domain() {
			/*$locale = determine_locale();
			$locale = apply_filters( 'plugin_locale', $locale, 'learnpress' );

			$plugin_translation_path = WP_LANG_DIR . '/plugins/learnpress-' . $locale . '.mo';
			$custom_translation_path = WP_LANG_DIR . '/learnpress/learnpress-' . $locale . '.mo';
			if ( is_readable( $custom_translation_path ) ) {
				unload_textdomain( LP_TEXT_DOMAIN );
				load_textdomain( LP_TEXT_DOMAIN, $custom_translation_path );
				load_textdomain( LP_TEXT_DOMAIN, $plugin_translation_path );
			}*/

			load_plugin_textdomain( LP_TEXT_DOMAIN, false, LP_PLUGIN_FOLDER_NAME . '/languages' );
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

// Load template hooks here, before theme add hooks remove.
// Load here because this file call LearnPress::instance(), loop call.
require_once 'inc/lp-template-hooks.php';
