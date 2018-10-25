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
		$default_styles = array();
		$load_fa        = LP()->settings()->get( 'load_fa' );
		$load_css       = LP()->settings()->get( 'load_css' );

		if ( $load_fa === 'yes' || ! $load_fa ) {
			$default_styles['font-awesome'] = self::url( 'css/font-awesome.min.css' );
		}

		if ( $load_css === 'yes' || ! $load_css ) {
			$default_styles['learn-press']      = self::url( 'css/learnpress.css' );
			$default_styles['jquery-scrollbar'] = self::url( 'js/vendor/jquery-scrollbar/jquery.scrollbar.css' );
		}

		return apply_filters(
			'learn-press/frontend-default-styles',
			$default_styles
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

	public function _get_scripts() {
		$suffix          = defined( 'LP_DEV' ) && LP_DEV ? '' : '';
		$default_scripts = learn_press_is_compress_assets()
			? array(
				'global'           => array(
					'url'  => self::url( 'js/global.js' ),
					'deps' => array(
						'jquery'
					)
				),
				'learnpress'       => self::url( 'js/frontend/learnpress-frontend.min.js' ),
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
				'become-a-teacher' => array(
					'url'  => self::url( 'js/frontend/become-teacher.js' ),
					'deps' => array(
						'jquery'
					)
				)
			)
			: array(
				'watchjs'          => self::url( 'js/vendor/watch.js' ),
				'jalerts'          => self::url( 'js/vendor/jquery.alert.js' ),
				//'circle-bar'       => self::url( 'js/vendor/circle-bar.js' ),
				'lp-vue'           => array(
					'url'     => self::url( 'js/vendor/vue' . $suffix . '.js' ),
					'ver'     => '2.5.16',
					'enqueue' => false
				),
				'lp-vuex'          => array(
					'url'     => self::url( 'js/vendor/vuex.2.3.1.js' ),
					'ver'     => '2.3.1',
					'enqueue' => false,
					'deps'    => array( 'lp-vue' )
				),
				'lp-vue-resource'  => array(
					'url'     => self::url( 'js/vendor/vue-resource.1.3.4.js' ),
					'ver'     => '1.3.4',
					'enqueue' => false,
					'deps'    => array( 'lp-vue', 'lp-vuex' )
				),
				'global'           => array(
					'url'  => self::url( 'js/global.js' ),
					'deps' => array(
						'jquery', /*'underscore',*/
						'utils'
					)
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
				),
				'notifications'    => array(
					'url'  => self::url( 'js/frontend/notifications.js' ),
					'deps' => array(
						'jquery'
					)
				)
			);


		return apply_filters( 'learn-press/frontend-default-scripts', $default_scripts );
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
				$enqueue = apply_filters( 'learn-press/enqueue-script', $enqueue, $handle );
				if ( $handle == 'font-awesome' || $enqueue ) {
					wp_enqueue_script( $handle );
				} else {
					$args = wp_parse_args( $data, array(
						'url'       => '',
						'deps'      => array(),
						'ver'       => '',
						'in_footer' => false
					) );
					list( $url, $deps, $ver, $in_footer ) = array_values( $args );

					//wp_register_script($handle);

					wp_register_script( $handle, $url, $deps, $ver, $in_footer );
				}
			}
		}

		do_action( 'learn-press/frontend-enqueue-scripts' );

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

		do_action( 'learn-press/frontend-enqueue-styles' );
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
 * Compress js/css or not?
 *
 * @return bool
 */
function learn_press_is_compress_assets() {
	return apply_filters( 'learn-press/compress-assets', ! defined( 'LP_COMPRESS_ASSETS' ) || defined( 'LP_COMPRESS_ASSETS' ) && LP_COMPRESS_ASSETS );
}

/**
 * Load frontend asset
 */
if ( ! is_admin() ) {
	learn_press_assets();
}