<?php

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Statistics\PeriodResolver;

/**
 * Class LP_Admin_Assets
 *
 * Manage admin assets
 */
class LP_Admin_Assets extends LP_Abstract_Assets {
	protected static $_instance;

	/**
	 * LP_Admin_Assets constructor.
	 */
	protected function __construct() {
		add_action( 'admin_footer', array( $this, 'add_elements_global' ) );
		add_action( 'admin_print_scripts', array( $this, 'load_scripts_styles_on_head' ), - 1 );
		parent::__construct();
	}

	/**
	 * Add javascript to head
	 * Add style to head
	 *
	 * @return void
	 * @version 1.0.0
	 * @since 4.2.5.6
	 */
	public function load_scripts_styles_on_head() {
		LP_Helper::print_inline_script_tag( 'lpDataAdmin', $this->localize_data_global(), array( 'id' => 'lpDataAdmin' ) );
		LP_Helper::print_inline_script_tag( 'lpData', $this->localize_data_global(), array( 'id' => 'lpData' ) );
	}

	/**
	 * Localize data for all page backend.
	 *
	 * @return array
	 * @since 4.2.5.6
	 * @version 1.0.2
	 */
	public function localize_data_global(): array {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		ob_start();
		Template::instance()->get_admin_template( 'search-author-field.php' );
		$html_search_author_field = ob_get_clean();

		return apply_filters(
			'learn-press/admin/localize-data-global',
			array(
				'site_url'                 => site_url(),
				'user_id'                  => get_current_user_id(),
				'is_admin'                 => current_user_can( ADMIN_ROLE ),
				'theme'                    => get_stylesheet(),
				'lp_version'               => LP()->version,
				'lp_rest_url'              => get_rest_url(),
				'lp_rest_load_ajax'        => get_rest_url( null, 'lp/v1/load_content_via_ajax/' ), // @deprecated 4.3.0
				'lpAjaxUrl'                => LP_Settings::url_handle_lp_ajax(),
				'nonce'                    => wp_create_nonce( 'wp_rest' ),
				'courses_url'              => learn_press_get_page_link( 'courses' ),
				'urlParams'                => lp_archive_skeleton_get_args(),
				'i18n'                     => array(
					'select_page'      => esc_html__( 'Select page', 'learnpress' ),
					'yes'              => esc_html__( 'Yes' ),
					'cancel'           => esc_html__( 'Cancel' ),
					'generate_with_ai' => esc_html__( 'Generate with AI', 'learnpress' ),
					'confirm_close_ai' => esc_html__( 'Are you sure you want to close? Generate data will stop.', 'learnpress' ),
				),
				'current_screen'           => $screen ? $screen->id : '',
				'show_search_author_field' => empty( $html_search_author_field ) ? 0 : $html_search_author_field,
				'toast'                    => array(
					'gravity'     => 'bottom',
					'position'    => 'center',
					'duration'    => 3000,
					'close'       => 1,
					'stopOnFocus' => 1,
					'classPrefix' => 'lp-toast',
				),
				'single_instructor_id'     => learn_press_get_page_id( 'single_instructor' ),
				'lpAi'                     => array(
					'config'     => Config::instance()->get( 'open-ai-modal', 'settings' ),
					'modelImage' => LP_Settings::get_option( 'open_ai_image_model_type', 'gpt-image-1' ),
				),
				'enable_open_ai'           => LP_Settings::get_option( 'enable_open_ai', 'no' ) === 'yes'
					&& ! empty( LP_Settings::get_option( 'open_ai_secret_key', '' ) ),
			)
		);
	}

	/**
	 * Get localize script
	 *
	 * @return array
	 */
	protected function _get_script_data(): array {
		$current_screen = get_current_screen();

		return array(
			'learn-press-global' => learn_press_global_script_params(),
			/*
			'learn-press-meta-box-order'      => apply_filters(
				'learn-press/meta-box-order/script-data',
				array(
					'i18n_error' => esc_html__( 'Oops! Error.', 'learnpress' ),
					'i18n_guest' => esc_html__( 'Guest', 'learnpress' ),
				)
			),*/
			/*
			'learn-press-update' => apply_filters(
				'learn-press/upgrade/script-data',
				array(
					'i18n_confirm' => esc_html__(
						'Before taking this action, we strongly recommend you backup your site first before proceeding. If you encounter any problems, please do not hesitate to contact our support team. Are you sure to proceed with the update protocol?',
						'learnpress'
					),
				)
			),*/
			'lp-admin'           => apply_filters(
				'learn-press/admin/script-data',
				array(
					'ajax'                 => admin_url( 'admin-ajax.php' ),
					'questionTypes'        => learn_press_question_types(),
					'supportAnswerOptions' => learn_press_get_question_support_answer_options(),
					'screen'               => $current_screen,
				)
			),
			// Statistics dashboard config → JS global lpAdminStatisticSettings.
			// REST root + nonce come from lpDataAdmin (already on every admin page).
			'lp-admin-statistic' => apply_filters(
				'learn-press/admin/statistics/script-data',
				array(
					'restNamespace'    => 'lp/v1/statistics',
					'adminUrl'         => admin_url(),
					// decode: JS renders via textContent, entities would show literally.
					'currencySymbol'   => html_entity_decode( learn_press_get_currency_symbol() ),
					'completionTarget' => (int) apply_filters( 'learn-press/statistics/completion-target', 70 ),
					// Completion badge bands: >= green is green, >= yellow is yellow, below is red.
					'completionBadge'  => apply_filters(
						'learn-press/statistics/completion-badge-thresholds',
						array(
							'green'  => 60,
							'yellow' => 40,
						)
					),
					// Date-range dropdown: per-preset resolved labels ( the server is the
					// single source of calendar logic — JS never re-derives windows ).
					'dateRange'        => array(
						'presets'     => self::statistics_date_range_presets(),
						'startOfWeek' => (int) get_option( 'start_of_week', 1 ),
						'dateFormat'  => (string) get_option( 'date_format', 'F j, Y' ),
					),
					'i18n'             => array(
						'loadError'              => esc_html__( 'Failed to load statistics data.', 'learnpress' ),
						'noData'                 => esc_html__( 'No data for this period.', 'learnpress' ),
						// Date-range dropdown.
						'presets'                => esc_html__( 'Presets', 'learnpress' ),
						'custom'                 => esc_html__( 'Custom', 'learnpress' ),
						'compareTo'              => esc_html__( 'Compare to', 'learnpress' ),
						'previousPeriod'         => esc_html__( 'Previous period', 'learnpress' ),
						'previousYear'           => esc_html__( 'Previous year', 'learnpress' ),
						'update'                 => esc_html__( 'Update', 'learnpress' ),
						'from'                   => esc_html__( 'From', 'learnpress' ),
						'to'                     => esc_html__( 'To', 'learnpress' ),
						'selectDateRange'        => esc_html__( 'Select a date range', 'learnpress' ),
						'itemsCount'             => esc_html__( '%d items', 'learnpress' ),
						'cappedNotice'           => esc_html__( 'Showing the first %d rows. Narrow the period or filters to see the rest.', 'learnpress' ),
						'vsPrevPeriod'           => esc_html__( 'vs previous period', 'learnpress' ),
						'revenue'                => esc_html__( 'Revenue', 'learnpress' ),
						'enrollments'            => esc_html__( 'Enrollments', 'learnpress' ),
						'aov'                    => esc_html__( 'Avg. order value: %s', 'learnpress' ),
						'belowTarget'            => esc_html__( '%d below completion target', 'learnpress' ),
						'failRate'               => esc_html__( '%s%% of all orders', 'learnpress' ),
						'course'                 => esc_html__( 'Course', 'learnpress' ),
						'orders'                 => esc_html__( 'Orders', 'learnpress' ),
						'aovShort'               => esc_html__( 'AOV', 'learnpress' ),
						'status'                 => esc_html__( 'Status', 'learnpress' ),
						'enrolled'               => esc_html__( 'Enrolled', 'learnpress' ),
						'completion'             => esc_html__( 'Completion', 'learnpress' ),
						'instructor'             => esc_html__( 'Instructor', 'learnpress' ),
						'courses'                => esc_html__( 'Courses', 'learnpress' ),
						'topCourses'             => esc_html__( 'Top courses', 'learnpress' ),
						'topSoldCourses'         => esc_html__( 'Top sold courses', 'learnpress' ),
						'healthy'                => esc_html__( 'Healthy', 'learnpress' ),
						'watchCompletion'        => esc_html__( 'Watch completion', 'learnpress' ),
						'highFailedQuizzes'      => esc_html__( 'High failed quizzes', 'learnpress' ),
						'needsFulfillmentReview' => esc_html__( 'Needs fulfillment review', 'learnpress' ),
						'awaitingPayment'        => esc_html__( 'Awaiting payment', 'learnpress' ),
						'exceptionRate'          => esc_html__( '%s%% of all orders', 'learnpress' ),
						'orderId'                => esc_html__( 'Order ID', 'learnpress' ),
						'student'                => esc_html__( 'Student', 'learnpress' ),
						'issue'                  => esc_html__( 'Issue', 'learnpress' ),
						'date'                   => esc_html__( 'Date', 'learnpress' ),
						'severity'               => esc_html__( 'Severity', 'learnpress' ),
						'high'                   => esc_html__( 'High', 'learnpress' ),
						'medium'                 => esc_html__( 'Medium', 'learnpress' ),
						'low'                    => esc_html__( 'Low', 'learnpress' ),
						'orderExceptions'        => esc_html__( 'Recent order exceptions', 'learnpress' ),
						'noOrderExceptions'      => esc_html__( 'No failed or cancelled orders in this period.', 'learnpress' ),
						'addedThisPeriod'        => esc_html__( '%d added this period', 'learnpress' ),
						'needsInstructorAction'  => esc_html__( 'Needs instructor action', 'learnpress' ),
						'scheduledReleases'      => esc_html__( 'Scheduled releases', 'learnpress' ),
						'targetPercent'          => esc_html__( 'Target: %s%%', 'learnpress' ),
						'publishedCourses'       => esc_html__( 'Published courses', 'learnpress' ),
						'noCoursePerformance'    => esc_html__( 'No course performance data in this period.', 'learnpress' ),
						'coursePerformance'      => esc_html__( 'Course performance', 'learnpress' ),
						'lessons'                => esc_html__( 'Lessons', 'learnpress' ),
						'quizzes'                => esc_html__( 'Quizzes', 'learnpress' ),
						'assignments'            => esc_html__( 'Assignments', 'learnpress' ),
						'published'              => esc_html__( 'Published', 'learnpress' ),
						'pending'                => esc_html__( 'Pending', 'learnpress' ),
						'future'                 => esc_html__( 'Future', 'learnpress' ),
						'drafts'                 => esc_html__( 'Drafts', 'learnpress' ),
						'total'                  => esc_html__( 'Total', 'learnpress' ),
						'content'                => esc_html__( 'Content', 'learnpress' ),
						'newThisPeriod'          => esc_html__( '+%d this period', 'learnpress' ),
						'activeInPeriod'         => esc_html__( '%d active in this period', 'learnpress' ),
						'activeThisPeriod'       => esc_html__( 'active this period', 'learnpress' ),
						'afterEnrollment'        => esc_html__( 'After enrollment', 'learnpress' ),
						'currentLearners'        => esc_html__( 'Current learners', 'learnpress' ),
						'completionRateSub'      => esc_html__( '%s%% completion rate', 'learnpress' ),
						'registeredUsers'        => esc_html__( 'Registered users', 'learnpress' ),
						'topStudents'            => esc_html__( 'Top students', 'learnpress' ),
						'topCoursesByStudents'   => esc_html__( 'Top courses by students', 'learnpress' ),
						'avgScore'               => esc_html__( 'Quiz pass rate', 'learnpress' ),
						'lastActive'             => esc_html__( 'Last active', 'learnpress' ),
						'startedLabel'           => esc_html__( 'Started', 'learnpress' ),
						'completedLabel'         => esc_html__( 'Completed', 'learnpress' ),
						'activeLast7dShort'      => esc_html__( 'Active 7d', 'learnpress' ),
						'statusActive'           => esc_html__( 'Active', 'learnpress' ),
						'statusAtRisk'           => esc_html__( 'At risk', 'learnpress' ),
						'statusIdle'             => esc_html__( 'Idle', 'learnpress' ),
						// Instructors tab.
						'ofNetSales'             => esc_html__( '%s%% of net sales', 'learnpress' ),
						'ofTotalInstructors'     => esc_html__( '%d total', 'learnpress' ),
						'students'               => esc_html__( 'Students', 'learnpress' ),
						'sold'                   => esc_html__( 'Sold', 'learnpress' ),
						'risk'                   => esc_html__( 'Risk', 'learnpress' ),
						'actionRequired'         => esc_html__( 'Action required', 'learnpress' ),
						'riskHigh'               => esc_html__( 'High', 'learnpress' ),
						'riskMedium'             => esc_html__( 'Medium', 'learnpress' ),
						'riskHealthy'            => esc_html__( 'Healthy', 'learnpress' ),
						'instructorPerformance'  => esc_html__( 'Instructor performance', 'learnpress' ),
						'instructorReport'       => esc_html__( 'Instructor report', 'learnpress' ),
						'noInstructorData'       => esc_html__( 'No instructor data in this period.', 'learnpress' ),
						'instructorReportEmpty'  => esc_html__( 'This instructor has no courses yet.', 'learnpress' ),
						'watchlistActions'       => array(
							'review_quiz_difficulty' => esc_html__( 'Review quiz difficulty', 'learnpress' ),
							'build_curriculum'       => esc_html__( 'Build the curriculum', 'learnpress' ),
							'add_practice_content'   => esc_html__( 'Add practice content', 'learnpress' ),
							'monitor'                => esc_html__( 'Monitor', 'learnpress' ),
						),
					),
				)
			),
			// @deprecated tag 'learn-press-admin-course-editor' 4.4.2 - no any enqueue js for that.
			// 'learn-press-admin-course-editor' => $this->get_course_data_for_editor_vue(),
		);
	}

	/**
	 * Preset entries for the statistics date-range dropdown.
	 *
	 * UI presets in display order. Labels are resolved server-side so JS never
	 * re-implements calendar logic.
	 *
	 * @return array[] [ { value, name, rangeLabel } ]
	 * @since 4.4.2
	 */
	private static function statistics_date_range_presets(): array {
		$presets = array();

		foreach ( PeriodResolver::UI_PRESETS as $preset ) {
			$presets[] = array(
				'value'      => $preset,
				'name'       => PeriodResolver::preset_name( $preset ),
				'rangeLabel' => PeriodResolver::range_label_for( PeriodResolver::resolve( $preset ) ),
			);
		}

		return $presets;
	}

	/**
	 * Get default scripts in admin.
	 *
	 * @return mixed
	 */
	protected function _get_scripts(): array {
		$lp_admin_js = new LP_Asset_Key(
			$this->url( 'js/dist/admin/admin' . self::$_min_assets . '.js' ),
			array( 'wp-i18n', 'lp-utils' ),
			array(),
			0,
			0,
			'',
			array( 'strategy' => 'async' )
		);
		$lp_admin_js->exclude_screen(
			array(
				'plugin-install',
				'learnpress_page_learn-press-statistics',
				'learnpress_page_learn-press-addons',
			)
		);

		$scripts = apply_filters(
			'learn-press/admin-default-scripts',
			array(
				'lp-load-ajax'              => new LP_Asset_Key(
					self::url( 'js/dist/loadAJAX' . self::$_min_assets . '.js' ),
					array(),
					array(),
					0,
					0,
					'',
					array( 'strategy' => 'async' )
				),
				// need build if change source vue
				//'vue-libs'                  => new LP_Asset_Key( $this->url( 'js/vendor/vue/vue_libs.js' ) ),
				//'select2'                   => new LP_Asset_Key( $this->url( 'src/js/vendor/select2.full.min.js' ) ),
				'jquery-tipsy'              => new LP_Asset_Key( $this->url( 'src/js/vendor/jquery/jquery-tipsy.js' ) ),
				'html2pdf'                  => new LP_Asset_Key( $this->url( 'src/js/vendor/html2pdf.bundle.min.js' ) ),
				'lp-utils'                  => new LP_Asset_Key(
					$this->url( 'js/dist/utils' . self::$_min_assets . '.js' ),
					array(),
					array(),
					1
				),
				/*
				'jquery-ui-timepicker-addon'        => new LP_Asset_Key(
					$this->url( 'src/js/vendor/jquery/jquery-ui-timepicker-addon.js' ),
					array( 'jquery-ui-datepicker' )
				),*/
				'lp-addons'                 => new LP_Asset_Key(
					$this->url( 'js/dist/admin/addons' . self::$_min_assets . '.js' ),
					array(),
					array( 'learnpress_page_learn-press-addons' ),
					0,
					0,
					'',
					array( 'strategy' => 'async' )
				),
				// 'advanced-list'                     => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/share/advanced-list' . self::$_min_assets . '.js' ) ),
				'learn-press-global'        => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/global' . self::$_min_assets . '.js' ),
					array( 'jquery', 'underscore', 'utils', 'jquery-ui-sortable' )
				),
				'lp-admin'            => $lp_admin_js,
				'lp-admin-mcp-api-keys' => new LP_Asset_Key(
					$this->url( 'js/dist/admin/mcp-api-keys.js' ),
					[ 'lp-load-ajax' ],
					[ 'learnpress_page_learn-press-settings' ],
					0,
					1,
					'',
					array( 'strategy' => 'defer' )
				),
				'lp-admin-webhooks'     => new LP_Asset_Key(
					$this->url( 'js/dist/admin/webhooks.js' ),
					[ 'lp-load-ajax', 'lp-admin' ],
					[ 'learnpress_page_learn-press-settings' ],
					0,
					1,
					'',
					[ 'strategy' => 'defer' ]
				),
				'lp-admin-learnpress' => new LP_Asset_Key(
					$this->url( 'js/dist/admin/learnpress' . self::$_min_assets . '.js' ),
					array(
						'learn-press-global',
						'wp-color-picker',
						'jquery-tipsy',
						'wp-api-fetch',
						// 'jquery-ui-timepicker-addon',
						// 'select2'
					),
					array(
						LP_LESSON_CPT,
						LP_QUIZ_CPT,
						LP_COURSE_CPT,
						// LP_ORDER_CPT,
						'learnpress_page_learn-press-settings',
					),
					0,
					1,
					'',
					array( 'strategy' => 'defer' )
				),
				'lp-duplicate-post'         => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/admin/lp-duplicate-post' . self::$_min_assets . '.js' ),
					array( 'jquery' ),
					array(
						'edit-' . LP_COURSE_CPT,
						'edit-' . LP_LESSON_CPT,
						'edit-' . LP_QUESTION_CPT,
						'edit-' . LP_QUIZ_CPT,
					),
					0,
					1
				),
				/*
				'learn-press-admin-course-editor'   => new LP_Asset_Key(
					$this->url( 'js/dist/admin/editor/course' . self::$_min_assets . '.js' ),
					array( 'vue-libs', 'lp-utils' ),
					array( LP_COURSE_CPT ),
					0,
					0
				),*/
				'lp-admin-courses'          => new LP_Asset_Key(
					$this->url( 'dist/js/admin/admin-courses' . self::$_min_assets . '.js' ),
					array( 'lp-load-ajax', 'wp-i18n' ),
					array( 'edit-' . LP_COURSE_CPT ),
					0,
					0,
					'',
					array( 'strategy' => 'async' )
				),
				'lp-edit-course'            => new LP_Asset_Key(
					$this->url( 'dist/js/admin/edit-course' . self::$_min_assets . '.js' ),
					array( 'lp-load-ajax' ),
					array(),
					1,
					0,
					'',
					array( 'strategy' => 'async' )
				),
				'lp-edit-quiz'              => new LP_Asset_Key(
					$this->url( 'dist/js/admin/edit-quiz' . self::$_min_assets . '.js' ),
					array( 'lp-load-ajax' ),
					array(),
					1,
					0,
					'',
					array( 'strategy' => 'async' )
				),
				'lp-edit-question'          => new LP_Asset_Key(
					$this->url( 'dist/js/admin/edit-question' . self::$_min_assets . '.js' ),
					array( 'lp-load-ajax' ),
					array(),
					1,
					0,
					'',
					array( 'strategy' => 'async' )
				),
				/*
				'learn-press-admin-quiz-editor'     => new LP_Asset_Key(
					$this->url( 'js/dist/admin/editor/quiz' . self::$_min_assets . '.js' ),
					array( 'vue-libs', 'lp-utils' ),
					array( LP_QUIZ_CPT ),
					0,
					0
				),*/
				/*
				'learn-press-admin-question-editor' => new LP_Asset_Key(
					$this->url( 'js/dist/admin/editor/question' . self::$_min_assets . '.js' ),
					array( 'vue-libs', 'lodash', 'lp-utils' ),
					array( LP_QUESTION_CPT ),
					0,
					0
				),*/
				/*
				'learn-press-meta-box-order'        => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/admin/partial/meta-box-order' . self::$_min_assets . '.js' ),
					array(
						'vue-libs',
						'advanced-list',
						//'lp-modal-search-courses',
						//'lp-modal-search-users',
					),
					array( LP_ORDER_CPT ),
					0,
					1
				),*/
				'lp-admin-order'            => new LP_Asset_Key(
					$this->url( 'js/dist/admin/admin-order' . self::$_min_assets . '.js' ),
					array( 'html2pdf', 'lp-load-ajax' ),
					array( LP_ORDER_CPT ),
					0,
					0,
					'',
					array( 'strategy' => 'defer' )
				),
				'lp-admin-orders'           => new LP_Asset_Key(
					$this->url( 'js/dist/admin/admin-orders' . self::$_min_assets . '.js' ),
					array(),
					array( 'edit-' . LP_ORDER_CPT ),
					0,
					0,
					'',
					array( 'strategy' => 'async' )
				),

				/*
				'learn-press-sync-data'             => new LP_Asset_Key(
					$this->url( 'js/dist/admin/pages/sync-data' . self::$_min_assets . '.js' ),
					array(),
					array( 'learnpress_page_learn-press-tools' ),
					0,
					1
				),*/
				/*
				'lp-setup'                          => new LP_Asset_Key(
					$this->url( 'js/dist/admin/pages/setup' . self::$_min_assets . '.js' ),
					array( 'jquery', 'dropdown-pages' ),
					array( 'lp-page-setup' ),
					0,
					1
				),*/
				/*
				'learn-press-statistic'             => new LP_Asset_Key(
					$this->url( 'js/dist/admin/pages/statistic' . self::$_min_assets . '.js' ),
					array( 'jquery', 'jquery-ui-datepicker', 'chart' ),
					array( 'learnpress_page_learn-press-statistics' ),
					0,
					1
				),*/
				/*
				'lp-modal-search-courses'           => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/admin/share/modal-search-courses' . self::$_min_assets . '.js' ),
					array(
						'vue-libs',
						'jquery',
					),
					array( LP_ORDER_CPT ),
					1,
					1
				),*/
				/*
				'lp-modal-search-users'             => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/admin/share/modal-search-users' . self::$_min_assets . '.js' ),
					array( 'jquery' ),
					array( LP_ORDER_CPT ),
					1,
					1
				),*/
				'lp-tools-course-tab'       => new LP_Asset_Key(
					$this->url( 'js/dist/admin/pages/tools' . self::$_min_assets . '.js' ),
					[],
					array( 'learnpress_page_learn-press-tools' ),
					0,
					1,
					'',
					array( 'strategy' => 'defer' )
				),
				/*
				'lp-dashboard'        => new LP_Asset_Key(
					self::url( 'js/dist/admin/pages/dashboard' . self::$_min_assets . '.js' ),
					[],
					array( 'dashboard' ),
					0,
					1
				),*/
				'lp-widgets-admin'          => new LP_Asset_Key(
					self::url( 'js/dist/admin/pages/widgets' . self::$_min_assets . '.js' ),
					array(
						'wp-url',
						'wp-api-fetch',
						'lodash',
						//'select2',
					),
					array( 'widgets', 'elementor' ),
					0,
					1
				),
				'lp-admin-notices'          => new LP_Asset_Key(
					self::url( 'js/dist/admin/admin-notices' . self::$_min_assets . '.js' ),
					array(),
					array(),
					1,
					0,
					'',
					array( 'strategy' => 'async' )
				),
				'lp-material'               => new LP_Asset_Key(
					$this->url( 'js/dist/admin/course-material' . self::$_min_assets . '.js' ),
					array(),
					array(
						LP_COURSE_CPT,
						LP_LESSON_CPT,
					),
					0,
					1
				),
				'lp-list-students-enrolled' => new LP_Asset_Key(
					$this->url( 'dist/js/admin/list-students-enrolled' . self::$_min_assets . '.js' ),
					array( 'lp-load-ajax' ),
					array( 'learnpress_page_learn-press-students-enrolled' ),
					0,
					0,
					'',
					array( 'strategy' => 'async' )
				),
				'lp-admin-tools'            => new LP_Asset_Key(
					$this->url( 'js/dist/admin/admin-tools' . self::$_min_assets . '.js' ),
					array(),
					array( 'learnpress_page_learn-press-tools' ),
					0,
					1,
					'',
					array( 'strategy' => 'defer' )
				),
				'lp-admin-statistic'        => new LP_Asset_Key(
					$this->url( 'js/dist/admin/admin-statistic' . self::$_min_assets . '.js' ),
					array( 'lp-load-ajax' ),
					array( 'learnpress_page_learn-press-statistics' ),
					0,
					0,
					'',
					array( 'strategy' => 'defer' )
				),
			)
		);

		/*
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && $screen->id === 'site-editor' ) {
			$scripts['editor-check'] = new LP_Asset_Key(
				self::url( 'js/dist/gutenberg/editor-check' . self::$_min_assets . '.js' ),
				array(
					'wp-data',
					'wp-edit-post',
					'wp-editor',
				),
				[],
				0,
				0,
				'1.0.0',
				[ 'strategy' => 'defer' ]
			);
		}*/

		return $scripts;
	}

	/**
	 * Get default styles in admin.
	 *
	 * @return mixed
	 */
	protected function _get_styles(): array {
		$is_rtl = is_rtl() ? '-rtl' : '';

		return apply_filters(
			'learn-press/admin-default-styles',
			array(
				/*'select2'               => new LP_Asset_Key(
					$this->url( 'src/css/vendor/select2.min.css' )
				),*/
				/*
				'font-awesome'          => new LP_Asset_Key(
					$this->url( 'src/css/vendor/font-awesome-5.min.css' )
				),*/
				/*
				'jquery-ui'             => new LP_Asset_Key(
					$this->url( 'src/css/vendor/jquery-ui/jquery-ui.min.css' )
				),
				'jquery-ui-timepicker'  => new LP_Asset_Key(
					$this->url( 'src/css/vendor/jquery-ui-timepicker-addon.css' )
				),*/
				'jquery-tipsy'          => new LP_Asset_Key(
					$this->url( 'src/css/vendor/jquery.tipsy.css' )
				),
				'learn-press-admin'     => new LP_Asset_Key(
					$this->url( 'css/admin/admin' . $is_rtl . self::$_min_assets . '.css' ),
					array(
						'wp-color-picker',
						'wp-components',
						// 'select2',
						// 'jquery-ui',
						// 'jquery-ui-timepicker',
						// 'font-awesome',
						'jquery-tipsy',
					),
					array(),
					0
				),
				'lp-help-center'        => new LP_Asset_Key(
					$this->url( 'css/admin/help-center' . $is_rtl . self::$_min_assets . '.css' ),
					array(),
					array( 'learnpress_page_learn-press-help-center' ),
					0
				),
				'lp-edit-curriculum'    => new LP_Asset_Key(
					$this->url( 'css/edit-curriculum' . $is_rtl . self::$_min_assets . '.css' ),
					array(),
					array(),
					1
				),
				'lp-edit-quiz'          => new LP_Asset_Key(
					$this->url( 'css/edit-quiz' . $is_rtl . self::$_min_assets . '.css' ),
					array(),
					array(),
					1
				),
				'lp-edit-question'      => new LP_Asset_Key(
					$this->url( 'css/edit-question' . $is_rtl . self::$_min_assets . '.css' ),
					array(),
					array(),
					1
				),
				'learn-press-statistic' => new LP_Asset_Key(
					LP_CSS_URL . 'admin/statistic' . $is_rtl . self::$_min_assets . '.css',
					array(),
					array( 'learners_page_learn-press-statistics' ),
					0
				),
				'lp-tom-select'         => new LP_Asset_Key(
					$this->url( 'src/css/vendor/tom-select.min.css' ),
					array(),
					array(),
					0
				),
			)
		);
	}

	/**
	 * Register and enqueue needed js and styles
	 */
	public function load_scripts() {
		$screen_id = LP_Admin::instance()->get_screen_id();

		if ( empty( $screen_id ) ) {
			return;
		}

		// wp_enqueue_media(); //Todo: tungnx need check why call for that using.
		$this->handle_js( $screen_id );
		$this->handle_style( $screen_id );

		do_action( 'learn-press/admin/after-enqueue-scripts' );
	}

	/**
	 * Show overlay
	 */
	public function add_elements_global() {
		echo '<div class="lp-overlay">';
		apply_filters( 'learnpress/admin/modal-dialog', learn_press_get_template( 'global/lp-modal-overlay' ) );
		echo '</div>';

		apply_filters( 'learnpress/admin/steps', learn_press_get_template( 'global/lp-group-step' ) );

		// Added notify message when action done.
		Template::instance()->get_admin_template( 'global/notify-action.php' );
	}

	/**
	 * Get course data for Vue Editor Course use.
	 *
	 * @return array|mixed|null
	 * @deprecated 4.4.2 - no any enqueue js for that.
	 */
	/*
	public function get_course_data_for_editor_vue() {
		global $post, $pagenow;

		if ( empty( $post ) || ( get_post_type() !== LP_COURSE_CPT ) || ! in_array(
			$pagenow,
			array(
				'post.php',
				'post-new.php',
			)
		) ) {
			return array();
		}

		$course = CourseModel::find( $post->ID, true );
		if ( $course ) {
			$course_section_items = $course->get_section_items();
		} else { // Code old if not found course on the table learnpress_courses.
			$course               = learn_press_get_course( $post->ID );
			$course_section_items = $course->get_curriculum_raw();
		}
		$hidden_sections = get_post_meta( $post->ID, '_admin_hidden_sections', true );

		return apply_filters(
			'learn-press/admin-localize-course-editor',
			array(
				'root'        => array(
					'course_id'          => $post->ID,
					'auto_draft'         => get_post_status( $post->ID ) == 'auto-draft',
					'ajax'               => admin_url( 'index.php' ),
					'disable_curriculum' => false,
					'action'             => 'admin_course_editor',
					'nonce'              => wp_create_nonce( 'learnpress_update_curriculum' ),
				),
				'chooseItems' => array(
					'types'      => learn_press_course_get_support_item_types(),
					'open'       => false,
					'addedItems' => array(),
					'items'      => array(),
				),
				'i18n'        => array(
					'item'                   => __( 'item', 'learnpress' ),
					'new_section_item'       => __( 'Create a new', 'learnpress' ),
					'back'                   => __( 'Back', 'learnpress' ),
					'selected_items'         => __( 'Selected items', 'learnpress' ),
					'confirm_remove_item'    => __( 'Do you want to remove the "{{ITEM_NAME}}" item from the course?', 'learnpress' ),
					'confirm_trash_item'     => __( 'Do you want to move the "{{ITEM_NAME}}" item to the trash?', 'learnpress' ),
					'item_labels'            => array(
						'singular' => __( 'Item', 'learnpress' ),
						'plural'   => __( 'Items', 'learnpress' ),
					),
					'notice_sale_price'      => __( 'The course sale price must be less than the regular price', 'learnpress' ),
					'notice_price'           => __( 'The course price must be greater than the sale price', 'learnpress' ),
					'notice_sale_start_date' => __( 'The sale start date must be before the sale end date', 'learnpress' ),
					'notice_sale_end_date'   => __( 'The sale end date must be after the sale start date', 'learnpress' ),
					'notice_invalid_date'    => __( 'Invalid date', 'learnpress' ),
				),
				'sections'    => array(
					'sections'        => $course_section_items,
					'hidden_sections' => ! empty( $hidden_sections ) ? $hidden_sections : array(),
					'urlEdit'         => admin_url( 'post.php?action=edit&post=' ),
				),
			)
		);
	}*/

	public static function instance() {
		if ( ! is_admin() ) {
			return null;
		}

		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

/**
 * Shortcut function to get instance of LP_Admin_Assets
 *
 * @return LP_Admin_Assets|null
 * Addon Certificate, Import/Export is using.
 */
function learn_press_admin_assets() {
	return LP_Admin_Assets::instance();
}
