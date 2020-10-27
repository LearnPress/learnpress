<?php

/**
 * Class LP_Admin_Assets
 *
 * Manage admin assets
 */
class LP_Admin_Assets extends LP_Abstract_Assets {
	protected static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	protected function _get_script_data() {
		return array(
			'learn-press-global'         => learn_press_global_script_params(),
			'learn-press-meta-box-order' => apply_filters(
				'learn-press/meta-box-order/script-data',
				array(
					'i18n_error' => __( 'Ooops! Error.', 'learnpress' ),
					'i18n_guest' => __( 'Guest', 'learnpress' )
				)
			),
			'learn-press-update'         => apply_filters(
				'learn-press/upgrade/script-data',
				array(
					'i18n_confirm' => __( 'Before taking this action, we strongly recommend you should backup your site first before proceeding. Should any issues come at hand, do not hesitate to contact our Support team. Are you sure to proceed the update protocol?', 'learnpress' )
				)
			)
		);
	}

//	protected function get_all_plugins_url() {
//		$url = false;
//		if ( get_option( 'learn_press_exclude_admin_libraries' ) ) {
//			$uploadDir = wp_upload_dir();
//			if ( file_exists( $uploadDir['basedir'] . '/learnpress/admin.plugins.all' . self::$_min_assets . '.js' ) ) {
//				$url = $uploadDir['baseurl'] . '/learnpress/admin.plugins.all' . self::$_min_assets . '.js';
//			}
//		}
//
//		return $url;
//	}

	/**
	 * Get default scripts in admin.
	 *
	 * @return mixed
	 */
	protected function _get_scripts() {
		return apply_filters(
			'learn-press/admin-default-scripts',
			array(
				'vue'                               => new LP_Asset_Key( $this->url( 'src/js/vendor/vue/vue' . self::$_min_assets . '.js' ) ),
				'vuex'                              => new LP_Asset_Key( $this->url( 'src/js/vendor/vue/vuex' . self::$_min_assets . '.js' ) ),
				'vue-resource'                      => new LP_Asset_Key( $this->url( 'src/js/vendor/vue/vue-resource' . self::$_min_assets . '.js' ) ),
				'select2'                           => new LP_Asset_Key( $this->url( 'src/js/vendor/select2.full.min.js' ) ),
				'jquery-tipsy'                      => new LP_Asset_Key( $this->url( 'src/js/vendor/jquery/jquery-tipsy.js' ) ),
				'jspdf'                             => new LP_Asset_Key( $this->url( 'js/vendor/jspdf' . self::$_min_assets . '.js' ), array(), array(), 1, 1 ),
				'chart'                             => new LP_Asset_Key( $this->url( 'src/js/vendor/chart.min.js' ), array(), array(), 1, 1 ),
				'dropdown-pages'                    => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/share/dropdown-pages' . self::$_min_assets . '.js' ) ),
				'search-lp-addons-themes'           => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/pages/search-lp-addons-themes' . self::$_min_assets . '.js' ),
					array( 'jquery' ), array( 'learnpress_page_learn-press-addons' ), 0, 1
				),
				'advanced-list'                     => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/share/advanced-list' . self::$_min_assets . '.js' ) ),

//				'jsautocomplete'                    => array(
//					'url'       => $this->url( '../inc/libraries/meta-box/js/autocomplete.js' ),
//					'in_footer' => 0
//				),
//				'lp-plugins-all'                    => new LP_Asset_Key( $this->url( 'js/vendor/admin.plugins.all' . self::$_min_assets . '.js' ),
//					array(), array( 'learnpress' ), 1 ),
				'learn-press-global'                => new LP_Asset_Key( $this->url( 'js/global' . self::$_min_assets . '.js' ),
					array( 'jquery', 'underscore', 'utils', 'jquery-ui-sortable', 'select2' ),
					array( 'learnpress' ), 1 ),
				'learn-press-utils'                 => new LP_Asset_Key( $this->url( 'js/admin/utils' . self::$_min_assets . '.js' ), array(), array(), 1 ),
				'lp-admin'                          => new LP_Asset_Key( $this->url( 'js/admin/admin' . self::$_min_assets . '.js' ),
					array( 'learn-press-global', 'learn-press-utils', 'wp-color-picker', 'jspdf' ),
					array(), 0, 1
				),
				'lp-admin-learnpress'               => new LP_Asset_Key( $this->url( 'js/admin/learnpress' . self::$_min_assets . '.js' ),
					array(
						'learn-press-global',
						'learn-press-utils',
						'wp-color-picker',
						'jquery-tipsy',
						'dropdown-pages'
					),
					array( LP_COURSE_CPT, 'learnpress_page_learn-press-settings' ), 0, 1
				),
				'learn-press-admin-course-editor'   => new LP_Asset_Key( $this->url( 'js/dist/admin/editor/course' . self::$_min_assets . '.js' ),
					array(
						'vue',
						'vuex',
						'vue-resource',
//						'lp-plugins-all',
					),
					array( LP_COURSE_CPT ), 0, 0
				),
				'learn-press-admin-quiz-editor'     => new LP_Asset_Key( $this->url( 'js/dist/admin/editor/quiz' . self::$_min_assets . '.js' ),
					array(
						'vue',
						'vuex',
						'vue-resource',
//						'lp-plugins-all'
						//'learn-press-modal-search-items'
					),
					array( LP_QUIZ_CPT ), 0, 0
				),
				'learn-press-admin-question-editor' => new LP_Asset_Key( $this->url( 'js/dist/admin/editor/question' . self::$_min_assets . '.js' ),
					array(
						'vue',
						'vuex',
						'vue-resource',
//						'lp-plugins-all'
						//'learn-press-modal-search-items'
					),
					array( LP_QUESTION_CPT ), 0, 0
				),
				'learn-press-meta-box-order'        => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/partial/meta-box-order' . self::$_min_assets . '.js' ),
					array(
						//'learn-press-modal-search-items',
						//'learn-press-modal-search-users',
						'vue',
						'vuex',
						'vue-resource',
						'advanced-list'
						//'lp-vue'
					),
					array( LP_ORDER_CPT ), 0, 1
				),
				'learn-press-sync-data'             => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/pages/sync-data' . self::$_min_assets . '.js' ),
					array(),
					array( 'learnpress_page_learn-press-tools' ),
					0, 1
				),
				'lp-setup'                          => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/pages/setup' . self::$_min_assets . '.js' ),
					array( 'jquery', 'learn-press-utils', 'dropdown-pages' ),
					array( 'lp-page-setup' ),
					0, 1
				),
				'learn-press-statistic'             => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/pages/statistic' . self::$_min_assets . '.js' ),
					array( 'jquery', 'jquery-ui-datepicker', 'chart' ),
					array( 'learnpress_page_learn-press-statistics' ),
					0, 1
				),
				'lp-advertisement'                  => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/share/advertisement' . self::$_min_assets . '.js' ),
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
					0, 1
				),
				'lp-modal-search-courses'           => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/share/modal-search-courses' . self::$_min_assets . '.js' ),
					array(
						'vue',
						'vuex',
						'vue-resource',
						'jquery'
					), array( LP_ORDER_CPT ), 0, 1
				),
				'lp-admin-tabs'                     => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/share/admin-tabs' . self::$_min_assets . '.js' ),
					array( 'jquery' ), array( LP_COURSE_CPT ), 0, 1
				),
				'lp-admin-notice'                   => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/share/admin-notice' . self::$_min_assets . '.js' ),
					array( 'jquery' ), array(), 0, 1
				),
				'lp-modal-search-users'             => new LP_Asset_Key( $this->url( self::$_folder_source . 'js/admin/share/modal-search-users' . self::$_min_assets . '.js' ),
					array( 'jquery' ), array(), 0, 1
				),
			)
		);
	}

	/**
	 * Get default styles in admin.
	 *
	 * @return mixed
	 */
	protected function _get_styles() {
		return apply_filters(
			'learn-press/admin-default-styles',
			array(
				'select2'               => array(
					'url' => LP()->plugin_url( 'inc/libraries/meta-box/css/select2/select2.css' )
				),
				//'font-awesome'      => $this->url( 'css/font-awesome.min.css' ),
				'learn-press-bundle'    => array(
					'url' => $this->url( 'css/bundle.min.css' )
				),
				'learn-press-admin'     => array(
					'url'  => $this->url( 'css/admin/admin.css' ),
					'deps' => array( 'wp-color-picker' )
				),
				'learn-press-statistic' => array(
					'url'     => LP_CSS_URL . 'admin/statistic.css',
					'screens' => 'learnpress_page_learn-press-statistics'
				)
			)
		);
	}

	/**
	 * Register and enqueue needed js and styles
	 */
	public function load_scripts() {
		// Register
		//$this->_register_scripts();

		$screen_id = LP_Admin::instance()->get_screen_id();

		$this->handle_js( $screen_id );

		/**
		 * Enqueue styles
		 *
		 * TODO: check to show only styles needed in specific pages
		 */
		if ( $styles = $this->_get_styles() ) {
			foreach ( $styles as $handle => $data ) {
				wp_enqueue_style( $handle, $data['url'] );
			}
		}

		do_action( 'learn-press/admin/after-enqueue-scripts' );
	}

	/**
	 * Register and enqueue a custom stylesheet in the WordPress admin.
	 * @author
	 */
	public function wpdocs_enqueue_custom_admin_style() {
		wp_register_style( 'custom_wp_admin_css', get_template_directory_uri() . '/admin-style.css', false, '1.0.0' );
		wp_enqueue_style( 'custom_wp_admin_css' );
	}

	/**
	 * Register, enqueue js
	 *
	 * @param string $screen_id
	 */
	protected function handle_js( $screen_id = '' ) {
		$scripts = $this->_get_scripts();
		/**
		 * @var LP_Asset_Key[] $scripts
		 */
		foreach ( $scripts as $handle => $script ) {
			if ( ! $script instanceof LP_Asset_Key ) {
				continue;
			}

			wp_register_script( $handle, $script->_url, $script->_deps, self::$_version_assets, $script->_in_footer );

			if ( ! $script->_only_register ) {
				$can_load_js = false;

				if ( ! empty( $script->_screens ) ) {
					$can_load_js = apply_filters( 'learnpress/can-load-js/' . $handle, in_array( $screen_id, $script->_screens ), $screen_id, $script->_screens );
				} else {
					$can_load_js = true;
				}

				if ( $can_load_js ) {
					wp_enqueue_script( $handle );
				}
			}

			do_action( 'learn-press/enqueue-script/' . $handle, $script, $screen_id );
		}
	}

	protected function handle_style() {

	}

	public static function instance() {
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
	if ( ! is_admin() ) {
		return null;
	}

	return LP_Admin_Assets::instance();
}

learn_press_admin_assets();
