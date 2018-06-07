<?php
/**
 * Profile Page Shortcode.
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

if ( ! class_exists( 'LP_Shortcode_Profile' ) ) {

	/**
	 * Class LP_Shortcode_Profile
	 */
	class LP_Shortcode_Profile extends LP_Abstract_Shortcode {
		/**
		 * LP_Shortcode_Profile constructor.
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

			ob_start();
			learn_press_print_messages();
			learn_press_get_template( 'profile/profile.php' );
			$output = ob_get_clean();

			return $output;
		}
	}
}