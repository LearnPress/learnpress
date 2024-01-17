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
				$output = sprintf( __( 'Your are logged in as %1$s. <a href="%2$s">Log out</a>?', 'learnpress' ), $user->get_display_name(), wp_logout_url() );
			} else {
				ob_start();
				learn_press_show_message();
				learn_press_get_template( 'global/form-login.php' );
				$output = ob_get_clean();
			}

			return $output;
		}

	}
}
