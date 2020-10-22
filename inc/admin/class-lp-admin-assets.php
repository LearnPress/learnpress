<?php
/**
 * Class LP_Admin_Assets
 *
 * Manage admin assets
 *
 * @author ThimPress <nhamdv>
 * @version 4.0.0
 */
class LP_Admin_Assets extends LP_Abstract_Assets {

	public function __construct() {
		parent::__construct();
	}

	protected function _get_script_data() {
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
					'i18n_confirm' => esc_html__( 'Before taking this action, we strongly recommend you should backup your site first before proceeding. Should any issues come at hand, do not hesitate to contact our Support team. Are you sure to proceed the update protocol?', 'learnpress' ),
				)
			),
			'lp-admin'                   => apply_filters(
				'learn-press/admin/script-data',
				array(
					'ajax'                 => admin_url( 'admin-ajax.php' ),
					'questionTypes'        => learn_press_question_types(),
					'supportAnswerOptions' => learn_press_get_question_support_answer_options(),
				)
			),
		);
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
				'select2'                           => self::url( 'js/vendor/jquery/select2.full.min.js' ),
				'lp-plugins-all'                    => array(
					'url'     => self::url( 'js/vendor/admin.plugins.all' . $min . '.js' ),
					'screens' => array(
						'learnpress',
					),
				),
				'jquery-ui-timepicker-addon'        => array(
					'url'     => $this->url( 'js/vendor/jquery/jquery-ui-timepicker-addon.js' ),
					'deps'    => array( 'jquery-ui-datepicker' ),
					'screens' => array( LP_COURSE_CPT ),
				),
				'learn-press-global'                => array(
					'url'     => $this->url( 'js/global' . $min . '.js' ),
					'deps'    => array(
						'jquery',
						'underscore',
						'utils',
						'jquery-ui-sortable',
						'select2',
					),
					'screens' => array( 'learnpress' ),
				),
				'learn-press-utils'                 => array(
					'url'  => $this->url( 'js/admin/utils' . $min . '.js' ),
					'deps' => array( 'jquery' ),
				),
				'lp-admin'                          => array(
					'url'     => $this->url( 'js/admin/admin' . $min . '.js' ),
					'deps'    => array( 'learn-press-global', 'learn-press-utils', 'wp-color-picker' ),
					'screens' => array( '*' ),
				),
				'lp-admin-learnpress'               => array(
					'url'     => $this->url( 'js/admin/learnpress' . $min . '.js' ),
					'deps'    => array( 'learn-press-global', 'learn-press-utils', 'wp-color-picker' ),
					'screens' => array( '*' ),
				),
				'learn-press-admin-course-editor'   => array(
					'url'     => $this->url( 'js/admin/editor/course' . $min . '.js' ),
					'deps'    => array(),
					'screens' => array( LP_COURSE_CPT ),
				),
				'learn-press-admin-quiz-editor'     => array(
					'url'     => $this->url( 'js/admin/editor/quiz' . $min . '.js' ),
					'deps'    => array(),
					'screens' => array( LP_QUIZ_CPT ),
				),
				'learn-press-admin-question-editor' => array(
					'url'     => $this->url( 'js/admin/editor/question' . $min . '.js' ),
					'deps'    => array(),
					'screens' => array( LP_QUESTION_CPT ),
				),
				'learn-press-meta-box-order'        => array(
					'url'     => $this->url( 'js/admin/partial/meta-box-order' . $min . '.js' ),
					'deps'    => array(
						'learn-press-utils',
					),
					'screens' => array( LP_ORDER_CPT ),
				),
				'learn-press-update'                => array(
					'url' => $this->url( 'js/admin/update.js' ),
				),
				'learn-press-sync-data'             => array(
					'url' => $this->url( 'js/admin/sync-data.js' ),
				),
				'learn-press-data-controls'         => array(
					'url'       => $this->url( 'js/frontend/data-controls.js' ),
					'screens'   => array( LP_QUESTION_CPT ),
					'in_footer' => true,
					'deps'      => array(
						'wp-element',
						'wp-compose',
						'wp-data',
						'wp-hooks',
						'wp-api-fetch',
						'lodash',
					),
				),
				'learn-press-question-editor'       => array(
					'url'       => $this->url( 'js/admin/question-editor.js' ),
					'screens'   => array( LP_QUESTION_CPT ),
					'in_footer' => true,
					'deps'      => array(
						'wp-element',
						'wp-compose',
						'wp-data',
						'wp-hooks',
						'wp-api-fetch',
						'lodash',
					),
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
				'select2'              => $this->url( 'css/vendor/select2.min.css' ),
				'font-awesome'         => $this->url( 'css/vendor/font-awesome-5.min.css' ),
				'jquery-ui'            => $this->url( 'css/vendor/jquery-ui/jquery-ui.min.css' ),
				'jquery-ui-timepicker' => $this->url( 'css/vendor/jquery-ui-timepicker-addon.css' ),
				'learn-press-bundle'   => $this->url( 'css/bundle.min.css' ),
				'learn-press-admin'    => array(
					'url'  => $this->url( 'css/admin/admin.css' ),
					'deps' => array( 'wp-color-picker' ),
				),
			)
		);
	}

	/**
	 * Register and enqueue needed scripts and styles
	 */
	public function load_scripts() {
		$this->_register_scripts();

		$screen_id = learn_press_get_screen_id();

		/**
		 * Enqueue scripts
		 *
		 * TODO: check to show only scripts needed in specific pages
		 */
		$scripts = $this->_get_scripts();

		if ( $scripts ) {
			foreach ( $scripts as $handle => $data ) {
				do_action( 'learn-press/enqueue-script/' . $handle );
				wp_enqueue_media();

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
		$styles = $this->_get_styles();
		if ( $styles ) {
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
