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

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}

	protected function get_bundle_css_url() {
		$url = false;
		if ( get_option( 'learn_press_exclude_frontend_libraries' ) ) {
			$uploadDir = wp_upload_dir();
			if ( file_exists( $uploadDir['basedir'] . '/learnpress/bundle.min.css' ) ) {
				$url = $uploadDir['baseurl'] . '/learnpress/bundle.min.css';
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
			'learn-press/frontend-default-styles',
			array(
				//'font-awesome'     => self::url( 'css/font-awesome.min.css' ),
				'learn-press-bundle' => ( $url = $this->get_bundle_css_url() ) ? $url : self::url( 'css/bundle.min.css' ),
				'learn-press'        => self::url( 'css/learnpress.css' ),
				//'jquery-scrollbar' => self::url( 'js/vendor/jquery-scrollbar/jquery.scrollbar.css' )
			)
		);
	}

	public function _get_script_data() {
		return array(
			'global'       => array(
				'url'      => learn_press_get_current_url(),
				'siteurl'  => site_url(),
				'ajax'     => admin_url( 'admin-ajax.php' ),
				'theme'    => get_stylesheet(),
				'localize' => array(
					'button_ok'     => __( 'OK', 'learnpress' ),
					'button_cancel' => __( 'Cancel', 'learnpress' ),
					'button_yes'    => __( 'Yes', 'learnpress' ),
					'button_no'     => __( 'No', 'learnpress' )
				)
			),
			'checkout'     => array(
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
			'course'       => learn_press_single_course_args(),
			'quiz'         => learn_press_single_quiz_args()
		);

	}

	protected function get_all_plugins_url( $min = '' ) {
		$url = false;
		if ( get_option( 'learn_press_exclude_frontend_libraries' ) ) {
			$uploadDir = wp_upload_dir();
			if ( file_exists( $uploadDir['basedir'] . '/learnpress/plugins.all' . $min . '.js' ) ) {
				$url = $uploadDir['baseurl'] . '/learnpress/plugins.all' . $min . '.js';
			}
		}

		return $url;
	}

	public function _get_scripts() {
		$min = learn_press_is_debug() ? '' : '.min';

		return apply_filters(
			'learn-press/frontend-default-scripts',
			array(
//				'watchjs'          => self::url( 'js/vendor/watch.js' ),
//				'jalerts'          => self::url( 'js/vendor/jquery.alert.js' ),
//				'circle-bar'       => self::url( 'js/vendor/circle-bar.js' ),
//				'lp-vue'           => array(
//					'url' => self::url( 'js/vendor/vue.min.js' ),
//					'ver' => '2.5.16'
//				),
				'lp-plugins-all'   => array(
					'url' => ( $url = $this->get_all_plugins_url( $min ) ) ? $url : self::url( 'js/vendor/plugins.all' . $min . '.js' ),
				),
//				'lp-vue-plugins'    => array(
//					'url'  => self::url( 'js/vendor/vue-plugins' . $min . '.js' ),
//					'ver'  => '3.1.0',
//					'deps' => array( 'lp-vue' )
//				),
//				'lp-jquery-plugins' => array(
//					'url'  => self::url( 'js/vendor/jquery-plugins' . $min . '.js' ),
//					'ver'  => '3.1.0',
//					'deps' => array( 'jquery' )
//				),
//				'lp-vue-resource'  => array(
//					'url'     => self::url( 'js/vendor/vue-resource.js' ),
//					'ver'     => '1.3.4',
//					'enqueue' => false
//				),
				'global'           => array(
					'url'  => self::url( 'js/global' . $min . '.js' ),
					'deps' => array( 'jquery', 'underscore', 'utils' )
				),
				'wp-utils'         => array(
					'url'     => self::url( 'js/utils' . $min . '.js' ),
					'deps'    => array( 'jquery' ),
					'screens' => '*'
				),
//				'jquery-scrollbar' => array(
//					'url'  => self::url( 'js/vendor/jquery-scrollbar/jquery.scrollbar.js' ),
//					'deps' => array( 'jquery' )
//				),
				'learnpress'       => array(
					'url'  => self::url( 'js/frontend/learnpress' . $min . '.js' ),
					'deps' => array( 'global' )
				),
				'checkout'         => array(
					'url'     => self::url( 'js/frontend/checkout.js' ),
					'deps'    => array( 'global' ),
					'enqueue' => learn_press_is_checkout() || learn_press_is_course() && ! learn_press_is_learning_course()

				),
				'course'           => array(
					'url'  => self::url( 'js/frontend/course.js' ),
					'deps' => array( 'global' )//, 'jquery-scrollbar', 'watchjs', 'jalerts' )
				),
				'quiz'             => array(
					'url'     => self::url( 'js/frontend/quiz.js' ),
					'deps'    => array( 'global'),//, 'jquery-scrollbar', 'watchjs' ),
					'enqueue' => LP_Global::course_item_quiz() ? true : false
				),
				'profile-user'     => array(
					'url'     => self::url( 'js/frontend/profile.js' ),
					'deps'    => array(
						'global',
						'plupload',
						'backbone',
						'jquery-ui-slider',
						'jquery-ui-draggable',
						'jquery-touch-punch',
					),
					'enqueue' => learn_press_is_profile()
				),
//				'jquery-scrollto'   => array(
//					'url'  => self::url( 'js/vendor/jquery.scrollTo.js' ),
//					'deps' => array(
//						'jquery'
//					)
//				),
				'become-a-teacher' => array(
					'url'  => self::url( 'js/frontend/become-teacher.js' ),
					'deps' => array(
						'jquery'
					)
				)
			)
		);

	}

	/**
	 * Load assets
	 */
	public function load_scripts() {
		// Register
		$this->_register_scripts();

		/**
		 * Enqueue scripts
		 *
		 * TODO: check to show only scripts needed in specific pages
		 */
		if ( $scripts = $this->_get_scripts() ) {
			foreach ( $scripts as $handle => $data ) {
				$enqueue = is_array( $data ) && array_key_exists( 'enqueue', $data ) ? $data['enqueue'] : true;
				/*switch ( $handle ) {
					case 'checkout':
						$enqueue = false;
						if ( learn_press_is_course() || learn_press_is_checkout() ) {
							$enqueue = true;
						}

				}*/
				$enqueue = apply_filters( 'learn-press/enqueue-script', $enqueue, $handle );
				if ( $handle == 'font-awesome' || $enqueue ) {
					wp_enqueue_script( 'jquery' );
					wp_enqueue_script( $handle );
				}
			}
		}

		/**
		 * Enqueue scripts
		 *
		 * TODO: check to show only styles needed in specific pages
		 */
		if ( $styles = $this->_get_styles() ) {
			foreach ( $styles as $handle => $data ) {
				wp_enqueue_style( $handle );
			}
		}
	}


}

/**
 * Shortcut function to get instance of LP_Assets
 *
 * @return LP_Assets|null
 */
function learn_press_assets() {
	static $assets = null;
	if ( ! $assets ) {
		$assets = new LP_Assets();
	}

	return $assets;
}

/**
 * Load frontend asset
 */
if ( ! is_admin() ) {
	learn_press_assets();
}