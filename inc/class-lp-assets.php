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
				'ajaxurl'              => site_url(),
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

	public function _get_scripts() {

		return apply_filters(
			'learn-press/frontend-default-scripts',
			array(
				'watchjs'          => self::url( 'js/vendor/watch.js' ),
				'jalerts'          => self::url( 'js/vendor/jquery.alert.js' ),
				'circle-bar'       => self::url( 'js/vendor/circle-bar.js' ),
				'lp-vue'           => array(
					'url'     => self::url( 'js/vendor/vue.js' ),
					'ver'     => '2.4.0',
					'enqueue' => false
				),
				'lp-vuex'          => array(
					'url'     => self::url( 'js/vendor/vuex.2.3.1.js' ),
					'ver'     => '2.3.1',
					'enqueue' => false
				),
				'lp-vue-resource'  => array(
					'url'     => self::url( 'js/vendor/vue-resource.1.3.4.js' ),
					'ver'     => '1.3.4',
					'enqueue' => false
				),
				'global'           => array(
					'url'  => self::url( 'js/global.js' ),
					'deps' => array( 'jquery', 'underscore', 'utils' )
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
					'url'     => self::url( 'js/frontend/checkout.js' ),
					'deps'    => array( 'global' ),
					'enqueue' => learn_press_is_checkout() || learn_press_is_course() && ! learn_press_is_learning_course()

				),
				'course'           => array(
					'url'  => self::url( 'js/frontend/course.js' ),
					'deps' => array( 'global', 'jquery-scrollbar', 'watchjs', 'jalerts' )
				),
				'quiz'             => array(
					'url'     => self::url( 'js/frontend/quiz.js' ),
					'deps'    => array( 'global', 'jquery-scrollbar', 'watchjs' ),
					'enqueue' => LP_Global::course_item_quiz() ? true : false
				),
				'profile-user'     => array(
					'url'     => self::url( 'js/frontend/profile.js' ),
					'deps'    => array(
						'global',
						'plupload',
						'backbone',
						'jquery-ui-slider',
						'jquery-ui-draggable'
					),
					'enqueue' => learn_press_is_profile()
				),
				'jquery-scrollto'  => array(
					'url'  => self::url( 'js/vendor/jquery.scrollTo.js' ),
					'deps' => array(
						'jquery'
					)
				),
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