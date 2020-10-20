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
//				'vue'                               => array(
//					'url' => $this->url( 'js/vendor/vue.' . self::$_min_assets . '.js' ),
//					'in_footer' => 0,
//				),
				'select2'                           => array(
					'url'       => $this->url( '../inc/libraries/meta-box/js/select2/select2.min.js' ),
					'in_footer' => 0
				),
				'jsautocomplete'                    => $this->url( '../inc/libraries/meta-box/js/autocomplete.js' ),
				'lp-plugins-all'                    => array(
					'url'       => $this->url( 'js/vendor/admin.plugins.all' . self::$_min_assets . '.js' ),
					'screens'   => array(
						'learnpress'
					),
					'in_footer' => 0,
				),
				'learn-press-global'                => array(
					'url'       => $this->url( 'js/global' . self::$_min_assets . '.js' ),
					'deps'      => array(
						'jquery',
						'underscore',
						'utils',
						'jquery-ui-sortable',
						'select2'
					),
					'screens'   => array( 'learnpress' ),
					'in_footer' => 0,
				),
				'learn-press-utils'                 => array(
					'url'       => $this->url( 'js/admin/utils' . self::$_min_assets . '.js' ),
					'deps'      => array( 'jquery' ),
					'in_footer' => 0,
				),
				'lp-admin'                          => array(
					'url'       => $this->url( 'js/admin/admin' . self::$_min_assets . '.js' ),
					'deps'      => array( 'learn-press-global', 'learn-press-utils', 'wp-color-picker' ),
					'screens'   => array( '*' ),
					'in_footer' => 0,
				),
				'lp-admin-learnpress'               => array(
					'url'       => $this->url( 'js/admin/learnpress' . self::$_min_assets . '.js' ),
					'deps'      => array( 'learn-press-global', 'learn-press-utils', 'wp-color-picker' ),
					'screens'   => array( '*' ),
					'in_footer' => 0,
				),
				'learn-press-admin-course-editor'   => array(
					'url'       => $this->url( 'js/admin/editor/course' . self::$_min_assets . '.js' ),
					'deps'      => array(
						//'lp-vue',
						//'learn-press-modal-search-items',
						//'lp-admin-tabs'
					),
					'screens'   => array( LP_COURSE_CPT ),
					'in_footer' => 0,
				),
				'learn-press-admin-quiz-editor'     => array(
					'url'     => $this->url( 'js/admin/editor/quiz' . self::$_min_assets . '.js' ),
					'deps'    => array(
						//'lp-vue',
						//'learn-press-modal-search-items'
					),
					'screens' => array( LP_QUIZ_CPT ),
				),
				'learn-press-admin-question-editor' => array(
					'url'     => $this->url( 'js/admin/editor/question' . self::$_min_assets . '.js' ),
					'deps'    => array(
						//'lp-vue',
						//'learn-press-modal-search-items'
					),
					'screens' => array( LP_QUESTION_CPT )
				),
				'learn-press-meta-box-order'        => array(
					'url'     => $this->url( 'js/admin/partial/meta-box-order' . self::$_min_assets . '.js' ),
					'deps'    => array(
						//'learn-press-modal-search-items',
						//'learn-press-modal-search-users',
						'learn-press-utils',
						//'lp-vue'
					),
					'screens' => array( LP_ORDER_CPT )
				),
				'learn-press-sync-data'             => array(
					'url' => $this->url( 'js/admin/sync-data.js' ),
					//'deps' => array( 'lp-vue' )
				),
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
		//$this->_register_scripts();

		$screen_id = LP_Admin::instance()->get_screen_id();

		$this->handle_js($screen_id);

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
		/**
		 * @since 3.2.7.8
		 * @author hungkv
		 */
		$v_rand = uniqid();
		if ( LP_DEBUG_STATUS ) {
			wp_register_script( 'learnpress-jspdf', LP_PLUGIN_URL . 'assets/js/admin/jspdf.js', false, $v_rand, true );
		} else {
			wp_register_script( 'learnpress-jspdf', LP_PLUGIN_URL . 'assets/js/admin/jspdf.min.js', false, LEARNPRESS_VERSION, true );
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
		if ( $scripts = $this->_get_scripts() ) {
			foreach ( $scripts as $handle => $data ) {
				$in_footer = 1;

				if ( ! isset( $data['url'] ) ) {
					continue;
				}

				if ( isset( $data['in_footer'] ) && ! $data['in_footer'] ) {
					$in_footer = 0;
				}

				wp_enqueue_script( $handle, $data['url'], $data['deps'], LP_Assets::$_version_assets, $in_footer );
//				if ( ! empty( $data['screens'] ) ) {
//					if ( $screen_id === $data['screens'] || is_array( $data['screens'] ) && in_array( $screen_id, $data['screens'] ) ) {
//						wp_enqueue_script( $handle );
//					} elseif ( ( $data['screens'] === 'learnpress' ) || ( is_array( $data['screens'] ) && in_array( 'learnpress', $data['screens'] ) ) && learn_press_is_admin_page() ) {
//						wp_enqueue_script( $handle );
//					} elseif ( ( $data['screens'] === '*' ) || is_array( $data['screens'] ) && in_array( '*', $data['screens'] ) ) {
//						wp_enqueue_script( $handle );
//					}
//				}

				do_action( 'learn-press/enqueue-script/' . $handle );
			}
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
	return LP_Admin_Assets::instance();
}

/**
 * Load admin asset
 */
if ( is_admin() ) {
	learn_press_admin_assets();
}