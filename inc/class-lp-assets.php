<?php

/**
 * Class LP_Assets
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Assets extends LP_Abstract_Assets {
	protected static $_instance;

	/**
	 * Constructor
	 */
	protected function __construct() {
		parent::__construct();

		add_action( 'wp_print_footer_scripts', array( $this, 'show_overlay' ) );

		// Perform deregister script
		$this->lp_deregister_script();
	}

	/**
	 * Get default styles in admin.
	 *
	 * @return mixed
	 */
	protected function _get_styles() {
		$styles = array(
			'learn-press-bundle' => self::url( 'css/bundle.min.css' ),
			'learn-press'        => self::url( self::$_folder_source . 'css/frontend/learnpress' . self::$_min_assets . '.css' ),
			'lp-overlay'         => self::url( self::$_folder_source . 'css/frontend/lp-overlay' . self::$_min_assets . '.css' ),
		);

		return apply_filters( 'learn-press/frontend-default-styles', $styles );
	}

	public function _get_script_data() {
		return array(
			'lp-global'    => array(
				'url'                       => learn_press_get_current_url(),
				'siteurl'                   => site_url(),
				'ajax'                      => admin_url( 'admin-ajax.php' ),
				'theme'                     => get_stylesheet(),
				'localize'                  => array(
					'button_ok'     => __( 'OK', 'learnpress' ),
					'button_cancel' => __( 'Cancel', 'learnpress' ),
					'button_yes'    => __( 'Yes', 'learnpress' ),
					'button_no'     => __( 'No', 'learnpress' )
				),
				'show_popup_confirm_finish' => LP()->settings()->get( 'enable_popup_confirm_finish', 'yes' ),
			),
			'lp-checkout'  => array(
				'ajaxurl'              => home_url(),
				'user_waiting_payment' => LP()->checkout()->get_user_waiting_payment(),
				'user_checkout'        => LP()->checkout()->get_checkout_email(),
				'i18n_processing'      => __( 'Processing', 'learnpress' ),
				'i18n_redirecting'     => __( 'Redirecting', 'learnpress' ),
				'i18n_invalid_field'   => __( 'Invalid field', 'learnpress' ),
				'i18n_unknown_error'   => __( 'Unknown error', 'learnpress' ),
				'i18n_place_order'     => __( 'Place order', 'learnpress' )
			),
			'profile-user' => array(
				'processing'  => __( 'Processing', 'learnpress' ),
				'redirecting' => __( 'Redirecting', 'learnpress' ),
				'avatar_size' => learn_press_get_avatar_thumb_size()
			),
			//'course'       => learn_press_single_course_args(), # lpCourseSettings => didn't see use
			'lp-quiz'      => learn_press_single_quiz_args()
			# lpQuizSettings is param object_name of wp_localize_script( $handle, $this->get_script_var_name( $handle ), $data );
		);

	}

	public function _get_scripts() {
		return apply_filters(
			'learn-press/frontend-default-scripts',
			array(
				'watch'            => new LP_Asset_Key( self::url( 'src/js/vendor/watch' . self::$_min_assets . '.js' ) ),
				//'lp-plugins-all'   => new LP_Asset_Key( self::url( 'src/js/plugins.all.min.js' ) ),
				'vue-libs'         => new LP_Asset_Key( self::url( 'src/js/vendor/vue/vue_libs_special.min.js' ) ),
				'lp-plugins-all'   => new LP_Asset_Key( self::url( 'js/vendor/plugins.all.min.js' ) ),
				'lp-global'        => new LP_Asset_Key( self::url( self::$_folder_source . 'js/global' . self::$_min_assets . '.js' ),
					array( 'jquery', 'underscore', 'utils' )
				),
				'lp-utils'         => new LP_Asset_Key( self::url( 'js/dist/utils' . self::$_min_assets . '.js' ),
					array( 'jquery' ), array(), 1, 0
				),
//				'learnpress'       => new LP_Asset_Key(self::url( 'src/js/frontend/learnpress' . self::$_min_assets . '.js' ),
//					array( 'lp-global' ), array(), 0, 1
//				),
				'lp-checkout'      => new LP_Asset_Key( self::url( self::$_folder_source . 'js/frontend/checkout' . self::$_min_assets . '.js' ),
					array( 'lp-global' ), array( LP_PAGE_CHECKOUT ), 0, 1
				),
				'course'           => new LP_Asset_Key( self::url( self::$_folder_source . 'js/frontend/course' . self::$_min_assets . '.js' ),
					array( 'lp-global', 'lp-utils', 'watch', 'lp-plugins-all' ),
					array(), 0, 1
				),
				'lp-quiz'          => new LP_Asset_Key( self::url( self::$_folder_source . 'js/frontend/quiz' . self::$_min_assets . '.js' ),
					array( 'lp-global', 'lp-utils', 'watch' ),//, 'jquery-scrollbar', 'watchjs' ),
					array( LP_PAGE_QIZ ), 0, 1
				),
				'profile-user'     => new LP_Asset_Key( self::url( self::$_folder_source . 'js/frontend/profile' . self::$_min_assets . '.js' ),
					array(
						'lp-global',
						'plupload',
						'backbone',
						'jquery-ui-slider',
						'jquery-ui-draggable',
						'jquery-touch-punch',
					),
					array( LP_PAGE_PROFILE ), 0, 1
				),
				'become-a-teacher' => new LP_Asset_Key( self::url( self::$_folder_source . 'js/frontend/become-teacher' . self::$_min_assets . '.js' ),
					array( 'jquery', 'lp-utils' ),
					array( LP_PAGE_BECOME_A_TEACHER ), 0, 1
				)
			)
		);
	}

	/**
	 * Load assets
	 */
	public function load_scripts() {
		// Register
//		$this->_register_scripts();
//
//		/**
//		 * Enqueue scripts
//		 *
//		 * TODO: check to show only scripts needed in specific pages
//		 */
//		if ( $scripts = $this->_get_scripts() ) {
//			foreach ( $scripts as $handle => $data ) {
//				$enqueue = is_array( $data ) && array_key_exists( 'enqueue', $data ) ? $data['enqueue'] : true;
//				/*switch ( $handle ) {
//					case 'checkout':
//						$enqueue = false;
//						if ( learn_press_is_course() || learn_press_is_checkout() ) {
//							$enqueue = true;
//						}
//
//				}*/
//				$enqueue = apply_filters( 'learn-press/enqueue-script', $enqueue, $handle );
//				if ( $handle == 'font-awesome' || $enqueue ) {
//					wp_enqueue_script( 'jquery' );
//					wp_enqueue_script( $handle );
//				}
//			}
//		}

		$page_current = lp_page_controller()::page_current();

		$this->handle_js( $page_current );

		/**
		 * Enqueue scripts
		 *
		 * TODO: check to show only styles needed in specific pages
		 */
		if ( $styles = $this->_get_styles() ) {
			foreach ( $styles as $handle => $data ) {
				wp_enqueue_style( $handle, $data, array(),  self::$_version_assets );
			}
		}
	}

	protected function handle_js( $page_current ) {
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
				$can_load_js = true;

				if ( ! empty( $script->_screens ) ) {
					$can_load_js = apply_filters( 'learnpress/frontend/can-load-js/' . $handle,
						in_array( $page_current, $script->_screens ), $page_current, $script->_screens );
				}

				if ( $can_load_js ) {
					wp_enqueue_script( $handle );
				}
			}
		}
	}

	public static function instance() {
		if ( is_admin() ) {
			return null;
		}

		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function show_overlay() {
		$page_current = LP_Page_Controller::page_current();
		if ( ! in_array( $page_current, array( LP_PAGE_COURSE, LP_PAGE_QIZ ) ) ) {
			return;
		}

		echo '<div class="lp-overlay">';
		apply_filters( 'learnpress/modal-dialog', learn_press_get_template( 'global/lp-modal-overlay' ) );
		echo '</div>';
	}

	/**
	 * Check and remove script conflict by default theme
	 *
	 * @author hungkv
	 * @since 3.2.8.2
	 */
	protected function lp_deregister_script() {
		$theme = wp_get_theme(); // gets the current theme

		// deregister global js if theme active is twenty seventeen
		if ( 'Twenty Seventeen' == $theme->name || 'Twenty Seventeen' == $theme->parent_theme ) {
			wp_deregister_script( 'twentyseventeen-global' );
		}
	}
}

/**
 * Shortcut function to get instance of LP_Assets
 *
 * @return LP_Assets|null
 */
function learn_press_assets() {
	return LP_Assets::instance();
}

learn_press_assets();
