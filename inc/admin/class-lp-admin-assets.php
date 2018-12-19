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
		add_action( 'learn-press/enqueue-script/learn-press-modal-search-items', array(
			'LP_Modal_Search_Items',
			'instance'
		) );
		add_action( 'learn-press/enqueue-script/learn-press-modal-search-users', array(
			'LP_Modal_Search_Users',
			'instance'
		) );
	}


	protected function _get_script_data() {
		return array(
			'learn-press-global'         => array(
				'i18n'    => array(
					'test_message' => 'This is global script for both admin and site'
				),
				'ajax'    => admin_url( 'admin-ajax.php' ),
				'siteurl' => site_url()
			),
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

	/**
	 * Get default scripts in admin.
	 *
	 * @return mixed
	 */
	protected function _get_scripts() {
		$min = defined( 'LP_DEBUG_DEV' ) && LP_DEBUG_DEV ? '' : '.min';

		return apply_filters(
			'learn-press/admin-default-scripts',
			array(
				'select2'                           => LP_Admin_Assets::url( '../inc/libraries/meta-box/js/select2/select2.min.js' ),
				'lp-vue'                            => array(
					'url' => self::url( 'js/vendor/vue' . $min . '.js' ),
					'ver' => '2.5.16'
				),
				'lp-vuex'                           => array(
					'url' => self::url( 'js/vendor/vuex.2.3.1.js' ),
					'ver' => '2.3.1'
				),
				'lp-vue-resource'                   => array(
					'url' => self::url( 'js/vendor/vue-resource.1.3.4.js' ),
					'ver' => '1.3.4'
				),
				'lp-sortable'                       => array(
					'url' => self::url( 'js/vendor/sortable.1.6.0.js' ),
					'ver' => '1.6.0'
				),
				'lp-vuedraggable'                   => array(
					'url'  => self::url( 'js/vendor/vuedraggable.2.14.1.js' ),
					'ver'  => '2.14.1',
					'deps' => array( 'lp-sortable' )
				),
				'learn-press-global'                => array(
					'url'  => $this->url( 'js/global.js' ),
					'deps' => array( 'jquery', 'underscore', 'utils', 'jquery-ui-sortable', 'select2' )
				),
				'learn-press-utils'                 => array(
					'url'  => $this->url( 'js/admin/utils.js' ),
					'deps' => array( 'jquery' )
				),
				'admin'                             => array(
					'url'  => $this->url( 'js/admin/admin.js' ),
					'deps' => array( 'learn-press-global', 'learn-press-utils', 'wp-color-picker' )
				),
				'admin-tabs'                        => array(
					'url'  => $this->url( 'js/admin/admin-tabs.js' ),
					'deps' => array( 'jquery' )
				),
				'tipsy'                             => array(
					'url'  => $this->url( 'js/vendor/jquery-tipsy/jquery.tipsy.js' ),
					'deps' => array( 'jquery' )
				),
				'learn-press-admin-course-editor'   => array(
					'url'     => $this->url( 'js/admin/course-editor.js' ),
					'deps'    => array(
						'lp-vue',
						'lp-vuex',
						'lp-vue-resource',
						'lp-vuedraggable',
					),
					'screens' => array( LP_COURSE_CPT )
				),
				'learn-press-admin-quiz-editor'     => array(
					'url'     => $this->url( 'js/admin/quiz-editor.js' ),
					'deps'    => array(
						'lp-vue',
						'lp-vuex',
						'lp-vue-resource',
						'lp-vuedraggable',
					),
					'screens' => array( LP_QUIZ_CPT )
				),
				'learn-press-admin-question-editor' => array(
					'url'     => $this->url( 'js/admin/question-editor.js' ),
					'deps'    => array(
						'lp-vue',
						'lp-vuex',
						'lp-vue-resource',
						'lp-vuedraggable',
					),
					'screens' => array( LP_QUESTION_CPT )
				),
				'learn-press-modal-search-items'    => array(
					'url' => $this->url( 'js/admin/modal-search-items.js' )
				),
				'learn-press-modal-search-users'    => array(
					'url' => $this->url( 'js/admin/modal-search-users.js' )
				),
				'learn-press-meta-box-order'        => array(
					'url'     => $this->url( 'js/admin/meta-box-order.js' ),
					'deps'    => array(
						'learn-press-global',
						'learn-press-modal-search-items',
						'learn-press-modal-search-users'
					),
					'screens' => array( LP_ORDER_CPT )
				),
				'learn-press-update'                => array(
					'url' => $this->url( 'js/admin/update.js' )
				),
				'learn-press-sync-data'             => array(
					'url' => $this->url( 'js/admin/sync-data.js' )
				)
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
				'select2'           => LP()->plugin_url( 'inc/libraries/meta-box/css/select2/select2.css' ),
				'font-awesome'      => $this->url( 'css/font-awesome.min.css' ),
				'learn-press-admin' => array(
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

		global $current_screen;
		$screen_id = $current_screen ? $current_screen->id : false;

		/**
		 * Enqueue scripts
		 *
		 * TODO: check to show only scripts needed in specific pages
		 */
		if ( $scripts = $this->_get_scripts() ) {
			foreach ( $scripts as $handle => $data ) {
				do_action( 'learn-press/enqueue-script/' . $handle );
				if ( empty( $data['screens'] ) || ! empty( $data['screens'] ) && in_array( $screen_id, $data['screens'] ) ) {
					wp_enqueue_script( $handle );
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