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

	/**
	 * Get default styles in admin.
	 *
	 * @return mixed
	 */
	protected function _get_styles() {
		return apply_filters(
			'learn-press/frontend-default-styles',
			array(
				'font-awesome'     => self::url( 'css/font-awesome.min.css' ),
				'learn-press'      => self::url( 'css/learnpress.css' ),
				'jquery-scrollbar' => self::url( 'js/vendor/jquery-scrollbar/jquery.scrollbar.css' )
			)
		);
	}

	public function _get_script_data() {
		return array(
			'checkout'     => array(
				'ajaxurl'              => site_url(),
				'user_waiting_payment' => LP()->checkout()->get_user_waiting_payment(),
				'user_checkout'        => LP()->checkout()->get_checkout_email(),
				'i18n_processing'      => __( 'Processing', 'learnpress' ),
				'i18n_redirecting'     => __( 'Redirecting', 'learnpress' ),
				'i18n_invalid_field'   => __( 'Invalid field', 'learnpress' ),
				'i18n_unknown_error'   => __( 'Unknow error', 'learnpress' ),
				'i18n_place_order'     => __( 'Place order', 'learnpress' )
			),
			'profile-user' => array(
				'processing'  => __( 'Processing', 'learnpress' ),
				'redirecting' => __( 'Redirecting', 'learnpress' )
			),
			'course'       => learn_press_single_course_args()
		);
	}

	public function _get_scripts() {
		return apply_filters(
			'learn-press/frontend-default-scripts',
			array(

				'lp-vue'           => array(
					'url' => self::url( 'js/vendor/vue.js' ),
					'ver' => '2.4.0'
				),
				'lp-vuex'          => array(
					'url' => self::url( 'js/vendor/vuex.2.3.1.js' ),
					'ver' => '2.3.1'
				),
				'lp-vue-resource'  => array(
					'url' => self::url( 'js/vendor/vue-resource.1.3.4.js' ),
					'ver' => '1.3.4'
				),
				'global'           => array(
					'url'  => self::url( 'js/global.js' ),
					'deps' => array( 'jquery', 'underscore', 'utils', 'backbone' )
				),
				'jquery-scrollbar' => array(
					'url'  => self::url( 'js/vendor/jquery-scrollbar/jquery.scrollbar.js' ),
					'deps' => array( 'jquery' )
				),
				'learnpress'       => array(
					'url'  => self::url( 'js/frontend/learnpress.js' ),
					'deps' => array( 'global' )
				),
				'checkout'         => array(
					'url'  => self::url( 'js/frontend/checkout.js' ),
					'deps' => array( 'global' )
				),
				'course'           => array(
					'url'  => self::url( 'js/frontend/course.js' ),
					'deps' => array( 'global', 'lp-vue', 'jquery-scrollbar' )
				),
				'profile-user'     => array(
					'url'  => self::url( 'js/frontend/profile.js' ),
					'deps' => array(
						'global',
						'plupload',
						'jquery-ui-slider',
						'jquery-ui-draggable'
					)
				),
				'jquery-scrollto'  => array(
					'url'  => self::url( 'js/vendor/jquery.scrollTo.js' ),
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
				$enqueue = true;
				switch ( $handle ) {
					case 'checkout':
						$enqueue = false;
						if ( learn_press_is_course() || learn_press_is_checkout() ) {
							$enqueue = true;
						}

				}
				if ( $handle == 'font-awesome' || $enqueue ) {
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

	public static function add_param() {

	}

	public static function add_var() {

	}

	public static function add_script_tag() {

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

learn_press_assets();