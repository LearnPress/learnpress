<?php
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
		add_action( 'admin_footer', array( $this, 'show_overlay' ) );
		parent::__construct();
	}

	/**
	 * Get localize script
	 *
	 * @return array
	 */
	protected function _get_script_data(): array {
		$current_screen = get_current_screen();

		return array(
			'learn-press-global'         => learn_press_global_script_params(),
			'learn-press-meta-box-order' => apply_filters(
				'learn-press/meta-box-order/script-data',
				array(
					'i18n_error' => esc_html__( 'Ooops! Error.', 'learnpress' ),
					'i18n_guest' => esc_html__( 'Guest', 'learnpress' ),
				)
			),
			'learn-press-update'         => apply_filters(
				'learn-press/upgrade/script-data',
				array(
					'i18n_confirm' => esc_html__(
						'Before taking this action, we strongly recommend you should backup your site first before proceeding. Should any issues come at hand, do not hesitate to contact our Support team. Are you sure to proceed the update protocol?',
						'learnpress'
					),
				)
			),
			'lp-admin'                   => apply_filters(
				'learn-press/admin/script-data',
				array(
					'ajax'                 => admin_url( 'admin-ajax.php' ),
					'questionTypes'        => learn_press_question_types(),
					'supportAnswerOptions' => learn_press_get_question_support_answer_options(),
					'screen'               => $current_screen,
				)
			),
		);
	}

	/**
	 * Get default scripts in admin.
	 *
	 * @return mixed
	 */
	protected function _get_scripts(): array {
		$lp_admin_js = new LP_Asset_Key(
			$this->url( self::$_folder_source . 'js/admin/admin' . self::$_min_assets . '.js' ),
			array( 'learn-press-global', 'lp-utils', 'wp-color-picker', 'jspdf' ),
			array(),
			0,
			1
		);
		$lp_admin_js->exclude_screen( array( 'plugin-install' ) );

		return apply_filters(
			'learn-press/admin-default-scripts',
			array(
				// need build if change source vue
				'vue-libs'                          => new LP_Asset_Key( $this->url( 'js/vendor/vue/vue_libs.js' ) ),
				'select2'                           => new LP_Asset_Key( $this->url( 'src/js/vendor/select2.full.min.js' ) ),
				'jquery-tipsy'                      => new LP_Asset_Key( $this->url( 'src/js/vendor/jquery/jquery-tipsy.js' ) ),
				'jspdf'                             => new LP_Asset_Key( $this->url( 'src/js/vendor/jspdf.min.js' ) ),
				'chart'                             => new LP_Asset_Key( $this->url( 'src/js/vendor/chart.min.js' ) ),
				'dropdown-pages'                    => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/share/dropdown-pages' . self::$_min_assets . '.js' ) ),
				'jquery-ui-timepicker-addon'        => new LP_Asset_Key(
					$this->url( 'src/js/vendor/jquery/jquery-ui-timepicker-addon.js' ),
					array( 'jquery-ui-datepicker' )
				),
				'themes-addons'                     => new LP_Asset_Key(
					$this->url( 'js/dist/admin/pages/themes-addons' . self::$_min_assets . '.js' ),
					array( 'jquery' ),
					array( 'learnpress_page_learn-press-addons' ),
					0,
					1
				),
				'advanced-list'                     => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/share/advanced-list' . self::$_min_assets . '.js' ) ),
				'learn-press-global'                => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/global' . self::$_min_assets . '.js' ),
					array( 'jquery', 'underscore', 'utils', 'jquery-ui-sortable', 'select2' ),
					array( 'learnpress' )
				),
				'lp-utils'                          => new LP_Asset_Key(
					$this->url( 'js/dist/utils' . self::$_min_assets . '.js' ),
					array(),
					array(),
					1
				),
				'lp-admin'                          => $lp_admin_js,
				'lp-admin-learnpress'               => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/admin/learnpress' . self::$_min_assets . '.js' ),
					array(
						'learn-press-global',
						'lp-utils',
						'wp-color-picker',
						'jquery-tipsy',
						'dropdown-pages',
						'wp-api-fetch',
						'jquery-ui-timepicker-addon',
					),
					array( LP_LESSON_CPT, LP_QUIZ_CPT, LP_COURSE_CPT, LP_ORDER_CPT, 'learnpress_page_learn-press-settings' ),
					0,
					1
				),
				'lp-duplicate-post'                 => new LP_Asset_Key(
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
				'learn-press-admin-course-editor'   => new LP_Asset_Key(
					$this->url( 'js/dist/admin/editor/course' . self::$_min_assets . '.js' ),
					array( 'vue-libs' ),
					array( LP_COURSE_CPT ),
					0,
					0
				),
				'learn-press-admin-quiz-editor'     => new LP_Asset_Key(
					$this->url( 'js/dist/admin/editor/quiz' . self::$_min_assets . '.js' ),
					array( 'vue-libs' ),
					array( LP_QUIZ_CPT ),
					0,
					0
				),
				'learn-press-admin-question-editor' => new LP_Asset_Key(
					$this->url( 'js/dist/admin/editor/question' . self::$_min_assets . '.js' ),
					array( 'vue-libs', 'lodash' ),
					array( LP_QUESTION_CPT ),
					0,
					0
				),
				'learn-press-meta-box-order'        => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/admin/partial/meta-box-order' . self::$_min_assets . '.js' ),
					array(
						'vue-libs',
						'advanced-list',
						'lp-modal-search-courses',
						'lp-modal-search-users',
					),
					array( LP_ORDER_CPT ),
					0,
					1
				),
				'learn-press-sync-data'             => new LP_Asset_Key(
					$this->url( 'js/dist/admin/pages/sync-data' . self::$_min_assets . '.js' ),
					array(),
					array( 'learnpress_page_learn-press-tools' ),
					0,
					1
				),
				'lp-setup'                          => new LP_Asset_Key(
					$this->url( 'js/dist/admin/pages/setup' . self::$_min_assets . '.js' ),
					array( 'jquery', 'lp-utils', 'dropdown-pages' ),
					array( 'lp-page-setup' ),
					0,
					1
				),
				'learn-press-statistic'             => new LP_Asset_Key(
					$this->url( 'js/dist/admin/pages/statistic' . self::$_min_assets . '.js' ),
					array( 'jquery', 'jquery-ui-datepicker', 'chart' ),
					array( 'learnpress_page_learn-press-statistics' ),
					0,
					1
				),
				'lp-advertisement'                  => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/admin/share/advertisement' . self::$_min_assets . '.js' ),
					array(),
					array(
						'edit-' . LP_COURSE_CPT,
						'edit-' . LP_QUESTION_CPT,
						'edit-' . LP_LESSON_CPT,
						'edit-' . LP_ORDER_CPT,
						'edit-' . LP_QUIZ_CPT,
						'learnpress_page_learn-press-settings',
						'learnpress_page_learn-press-tools',
						'learnpress_page_learn-press-statistics',
					),
					0,
					1
				),
				'lp-modal-search-courses'           => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/admin/share/modal-search-courses' . self::$_min_assets . '.js' ),
					array(
						'vue-libs',
						'jquery',
					),
					array( LP_ORDER_CPT ),
					1,
					1
				),
				'lp-admin-tabs'                     => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/admin/share/admin-tabs' . self::$_min_assets . '.js' ),
					array( 'jquery' ),
					array( LP_COURSE_CPT ),
					0,
					1
				),
				'lp-admin-notice'                   => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/admin/share/admin-notice' . self::$_min_assets . '.js' ),
					array( 'jquery' ),
					array(),
					0,
					1
				),
				'lp-modal-search-users'             => new LP_Asset_Key(
					$this->url( self::$_folder_source . 'js/admin/share/modal-search-users' . self::$_min_assets . '.js' ),
					array( 'jquery' ),
					array( LP_ORDER_CPT ),
					1,
					1
				),
				'lp-tools-course-tab'               => new LP_Asset_Key(
					$this->url( 'js/dist/admin/pages/tools' . self::$_min_assets . '.js' ),
					array(
						'jquery',
						'wp-element',
						'wp-compose',
						'wp-components',
						'wp-data',
						'wp-hooks',
						'wp-api-fetch',
						'lodash',
						'vue-libs',

					),
					array( 'learnpress_page_learn-press-tools' ),
					0,
					1
				),
				'lp-dashboard'                      => new LP_Asset_Key(
					self::url( 'js/dist/admin/pages/dashboard' . self::$_min_assets . '.js' ),
					array(
						'jquery',
						'wp-element',
						'wp-compose',
						'wp-data',
						'wp-hooks',
						'wp-api-fetch',
						'lodash',
					),
					array( 'dashboard' ),
					0,
					1
				),
				'lp-widgets-admin'                  => new LP_Asset_Key(
					self::url( 'js/dist/admin/pages/widgets' . self::$_min_assets . '.js' ),
					array(
						'wp-url',
						'wp-api-fetch',
						'lodash',
					),
					array( 'widgets', 'elementor' ),
					0,
					1
				),
			)
		);
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
				'select2'               => new LP_Asset_Key(
					$this->url( 'src/css/vendor/select2.min.css' )
				),
				'font-awesome'          => new LP_Asset_Key(
					$this->url( 'src/css/vendor/font-awesome-5.min.css' )
				),
				'jquery-ui'             => new LP_Asset_Key(
					$this->url( 'src/css/vendor/jquery-ui/jquery-ui.min.css' )
				),
				'jquery-ui-timepicker'  => new LP_Asset_Key(
					$this->url( 'src/css/vendor/jquery-ui-timepicker-addon.css' )
				),
				'jquery-tipsy'          => new LP_Asset_Key(
					$this->url( 'src/css/vendor/jquery.tipsy.css' )
				),
				'learn-press-admin'     => new LP_Asset_Key(
					$this->url( 'css/admin/admin' . $is_rtl . self::$_min_assets . '.css' ),
					array( 'wp-color-picker', 'wp-components', 'select2', 'jquery-ui', 'jquery-ui-timepicker', 'font-awesome', 'jquery-tipsy' ),
					array(),
					0
				),
				'learn-press-statistic' => new LP_Asset_Key(
					LP_CSS_URL . 'admin/statistic' . $is_rtl . self::$_min_assets . '.css',
					array(),
					array( 'learners_page_learn-press-statistics' ),
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
		wp_enqueue_media();

		$this->handle_js( $screen_id );

		$this->handle_style( $screen_id );

		do_action( 'learn-press/admin/after-enqueue-scripts' );
	}

	/**
	 * Register and enqueue a custom stylesheet in the WordPress admin.
	 *
	 * @author
	 */
	public function wpdocs_enqueue_custom_admin_style() {
		wp_register_style( 'custom_wp_admin_css', get_template_directory_uri() . '/admin-style.css', false, '1.0.0' );
		wp_enqueue_style( 'custom_wp_admin_css' );
	}

	/**
	 * Show overlay
	 */
	public function show_overlay() {
		echo '<div class="lp-overlay">';
		apply_filters( 'learnpress/admin/modal-dialog', learn_press_get_template( 'global/lp-modal-overlay' ) );
		echo '</div>';

		apply_filters( 'learnpress/admin/steps', learn_press_get_template( 'global/lp-group-step' ) );
	}

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
 */
function learn_press_admin_assets() {
	return LP_Admin_Assets::instance();
}

learn_press_admin_assets();
