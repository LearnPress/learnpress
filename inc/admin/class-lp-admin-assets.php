<?php

/**
 * Class LP_Admin_Assets
 *
 * Manage admin assets
 */
class LP_Admin_Assets extends LP_Abstract_Assets {

	/**
	 * Init Asset
	 */
	public function __construct() {
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

	protected function get_all_plugins_url( $min = '' ) {
		$url = false;
		if ( get_option( 'learn_press_exclude_admin_libraries' ) ) {
			$uploadDir = wp_upload_dir();
			if ( file_exists( $uploadDir['basedir'] . '/learnpress/admin.plugins.all' . $min . '.js' ) ) {
				$url = $uploadDir['baseurl'] . '/learnpress/admin.plugins.all' . $min . '.js';
			}
		}

		return $url;
	}

	/**
	 * Get default scripts in admin.
	 *
	 * @return mixed
	 */
	protected function _get_scripts() {
		$min = learn_press_is_debug() ? '' : '.min';

		return apply_filters(
			'learn-press/admin-default-scripts',
			array(
				'wp-color-picker'    => array(
					'screens' => 'learnpress_page_learn-press-settings'
				),
				'select2'            => LP_Admin_Assets::url( '../inc/libraries/meta-box/js/select2/select2.min.js' ),
//				'lp-vue'             => array(
//					'url'     => self::url( 'js/vendor/vue.min.js' ),
//					'ver'     => '2.5.16',
//					'screens' => array(
//						'learnpress'
//					)
//				),
				'lp-plugins-all'     => array(
					'url'     => ( $url = $this->get_all_plugins_url( $min ) ) ? $url : self::url( 'js/vendor/admin.plugins.all' . $min . '.js' ),
					'screens' => array(
						'learnpress'
					)
				),
//				'jquery-plugins'     => array(
//					'url'     => self::url( 'js/vendor/jquery-plugins' . $min . '.js' ),
//					'ver'     => '2.5.16',
//					'screens' => array(
//						'learnpress'
//					)
//				),
//				'lp-vuex'            => array(
//					'url'     => self::url( 'js/vendor/vuex.js' ),
//					'ver'     => '3.1.0',
//					'deps'    => array( 'lp-vue' ),
//					'screens' => array(
//						'learnpress'
//					)
//				),
//				'lp-vue-resource'    => array(
//					'url'     => self::url( 'js/vendor/vue-resource.js' ),
//					'ver'     => '1.3.4',
//					'deps'    => array( 'lp-vue' ),
//					'screens' => array(
//						'learnpress'
//					)
//				),
//				'lp-sortable'        => array(
//					'url'     => self::url( 'js/vendor/sortable.js' ),
//					'ver'     => '1.6.0',
//					'deps'    => array( 'lp-vue' ),
//					'screens' => array(
//						'learnpress'
//					)
//				),
//				'lp-vuedraggable'    => array(
//					'url'     => self::url( 'js/vendor/vuedraggable.js' ),
//					'ver'     => '2.14.1',
//					'deps'    => array( 'lp-vue', 'lp-sortable' ),
//					'screens' => array(
//						'learnpress'
//					)
//				),
				'learn-press-global' => array(
					'url'     => $this->url( 'js/global' . $min . '.js' ),
					'deps'    => array(
						'jquery',
						'underscore',
						'utils',
						'jquery-ui-sortable',
						'select2'
					),
					'screens' => array( 'learnpress' )
				),
				'learn-press-utils'  => array(
					'url'  => $this->url( 'js/admin/utils' . $min . '.js' ),
					'deps' => array( 'jquery' )
				),
//				'admin'                             => array(
//					'url'     => $this->url( 'js/admin/admin.js' ),
//					'deps'    => array( 'learn-press-global', 'learn-press-utils', 'wp-color-picker' ),
//					'screens' => array( 'learnpress' )
//				),
//				'admin-tabs'         => array(
//					'url'  => $this->url( 'js/admin/admin-tabs.js' ),
//					'deps' => array( 'jquery' )
//				),
//
//				'lp-admin-tabs' => array(
//					'url'  => $this->url( 'js/admin/admin-tabs.js' ),
//					'deps' => array( 'jquery' )
//				),
//				'tipsy'              => array(
//					'url'  => $this->url( 'js/vendor/jquery-tipsy'.$min.'.js' ),
//					'deps' => array( 'jquery' )
//				),

				'lp-admin' => array(
					//'url'     => $this->url( 'js/admin/admin.js' ),
					'url'     => $this->url( 'js/admin/admin' . $min . '.js' ),
					'deps'    => array( 'learn-press-global', 'learn-press-utils', 'wp-color-picker' ),
					'screens' => array( '*' )
				),

				'lp-admin-learnpress' => array(
					//'url'     => $this->url( 'js/admin/admin.js' ),
					'url'     => $this->url( 'js/admin/learnpress' . $min . '.js' ),
					'deps'    => array( 'learn-press-global', 'learn-press-utils', 'wp-color-picker' ),
					'screens' => array( '*' )
				),

				'learn-press-admin-course-editor'   => array(
					'url'     => $this->url( 'js/admin/editor/course' . $min . '.js' ),
					'deps'    => array(
						//'lp-vue',
						//'learn-press-modal-search-items',
						//'lp-admin-tabs'
					),
					'screens' => array( LP_COURSE_CPT )
				),
				'learn-press-admin-quiz-editor'     => array(
					'url'     => $this->url( 'js/admin/editor/quiz' . $min . '.js' ),
					'deps'    => array(
						//'lp-vue',
						//'learn-press-modal-search-items'
					),
					'screens' => array( LP_QUIZ_CPT )
				),
				'learn-press-admin-question-editor' => array(
					'url'     => $this->url( 'js/admin/editor/question' . $min . '.js' ),
					'deps'    => array(
						//'lp-vue',
						//'learn-press-modal-search-items'
					),
					'screens' => array( LP_QUESTION_CPT )
				),
//				'learn-press-modal-search-items'    => array(
//					'url'  => $this->url( 'js/admin/modal-search-items.js' ),
//					'deps' => array( 'learn-press-global', 'lp-vue' )
//				),
//				'learn-press-modal-search-users'    => array(
//					'url'  => $this->url( 'js/admin/modal-search-users.js' ),
//					'deps' => array( 'learn-press-global', 'lp-vue' )
//				),
				'learn-press-meta-box-order'        => array(
					'url'     => $this->url( 'js/admin/partial/meta-box-order' . $min . '.js' ),
					'deps'    => array(
						//'learn-press-modal-search-items',
						//'learn-press-modal-search-users',
						'learn-press-utils',
						//'lp-vue'
					),
					'screens' => array( LP_ORDER_CPT )
				),
				'learn-press-update'                => array(
					'url'  => $this->url( 'js/admin/update.js' ),
					//'deps' => array( 'lp-vue' )
				),
				'learn-press-sync-data'             => array(
					'url'  => $this->url( 'js/admin/sync-data.js' ),
					//'deps' => array( 'lp-vue' )
				),
//				'learn-press-chartjs'               => array(
//					'url'     => $this->url( 'js/vendor/chart.min.js' ),
//					'screens' => 'dashboard'
//				)
			)
		);
	}

	protected function get_bundle_css_url() {
		$url = false;
		if ( get_option( 'learn_press_exclude_admin_libraries' ) ) {
			$uploadDir = wp_upload_dir();
			if ( file_exists( $uploadDir['basedir'] . '/learnpress/admin.bundle.min.css' ) ) {
				$url = $uploadDir['baseurl'] . '/learnpress/admin.bundle.min.css';
			}
		}

		return $url;
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
				'select2'            => LP()->plugin_url( 'inc/libraries/meta-box/css/select2/select2.css' ),
				//'font-awesome'      => $this->url( 'css/font-awesome.min.css' ),
				'learn-press-bundle' => ( $url = $this->get_bundle_css_url() ) ? $url : $this->url( 'css/bundle.min.css' ),
				'learn-press-admin'  => array(
					'url'  => $this->url( 'css/admin/admin.css' ),
					'deps' => array( 'wp-color-picker' )
				)
			)
		);
	}

	/**
	 * Register and enqueue needed scripts and styles
	 */
	public function load_scripts() {
		// Register
		$this->_register_scripts();

		$screen_id = learn_press_get_screen_id();

		/**
		 * Enqueue scripts
		 *
		 * TODO: check to show only scripts needed in specific pages
		 */
		if ( $scripts = $this->_get_scripts() ) {
			foreach ( $scripts as $handle => $data ) {
				do_action( 'learn-press/enqueue-script/' . $handle );
				if ( ! empty( $data['screens'] ) ) {
					if ( $screen_id === $data['screens'] || is_array( $data['screens'] ) && in_array( $screen_id, $data['screens'] ) ) {
						wp_enqueue_script( $handle );
					} elseif ( ( $data['screens'] === 'learnpress' ) || ( is_array( $data['screens'] ) && in_array( 'learnpress', $data['screens'] ) ) && learn_press_is_admin_page() ) {
						wp_enqueue_script( $handle );
					} elseif ( ( $data['screens'] === '*' ) || is_array( $data['screens'] ) && in_array( '*', $data['screens'] ) ) {
						wp_enqueue_script( $handle );
					}
				}
			}
		}

		/**
		 * Enqueue styles
		 *
		 * TODO: check to show only styles needed in specific pages
		 */
		if ( $styles = $this->_get_styles() ) {
			foreach ( $styles as $handle => $data ) {
				wp_enqueue_style( $handle );
			}
		}

		do_action( 'learn-press/admin/after-enqueue-scripts' );
	}
}

/**
 * Shortcut function to get instance of LP_Admin_Assets
 *
 * @return LP_Admin_Assets|null
 */
function learn_press_admin_assets() {
	static $assets = null;
	if ( ! $assets ) {
		$assets = new LP_Admin_Assets();
	}

	return $assets;
}

/**
 * Load admin asset
 */
if ( is_admin() ) {
	learn_press_admin_assets();
}