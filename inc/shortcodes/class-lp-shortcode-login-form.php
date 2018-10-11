<?php
/**
 * Login Form Shortcode.
 *
 * @author   ThimPress
 * @category Shortcodes
 * @package  Learnpress/Shortcodes
 * @version  3.0.0
 * @extends  LP_Abstract_Shortcode
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Shortcode_Login_Form' ) ) {
	/**
	 * Class LP_Shortcode_Login_Form
	 */
	class LP_Shortcode_Login_Form extends LP_Abstract_Shortcode {
		/**
		 * LP_Shortcode_Login_Form constructor.
		 *
		 * @param mixed $atts
		 */
		public function __construct( $atts = '' ) {
			parent::__construct( $atts );
		}

		/**
		 * Shortcode content.
		 *
		 * @return string
		 */
		public function output() {
			if ( is_user_logged_in() ) {
				$user   = learn_press_get_current_user();
				$output = sprintf( __( 'Your are logged in as %s. <a href="%s">Log out</a>?', 'learnpress' ), $user->get_display_name(), wp_logout_url() );
			} else {
				if ( ! class_exists( 'LP_Meta_Box_Helper' ) ) {
					include_once LP_PLUGIN_PATH . 'inc/admin/meta-box/class-lp-meta-box-helper.php';
				}
				ob_start();
				learn_press_print_messages();
				learn_press_get_template( 'global/form-login.php', array( 'fields' => self::get_login_fields() ) );
				$output = ob_get_clean();
			}

			return $output;
		}

		/**
		 * Get fields for login form.
		 *
		 * @return array
		 */
		public static function get_login_fields() {
			$fields = array(
				'username' => array(
					'title'       => __( 'Username or email', 'learnpress' ),
					'type'        => 'text',
					'placeholder' => __( 'Username or email', 'learnpress' ),
					'saved'       => LP_Request::get_string( 'username' ),
					'id'          => 'username',
					'required'    => true
				),
				'password' => array(
					'title'       => __( 'Password', 'learnpress' ),
					'type'        => 'password',
					'placeholder' => __( 'Password', 'learnpress' ),
					'saved'       => LP_Request::get_string( 'password' ),
					'id'          => 'password',
					'required'    => true
				)
			);
			$fields = apply_filters( 'learn-press/login-fields', $fields );

			return $fields;
		}

	}
}