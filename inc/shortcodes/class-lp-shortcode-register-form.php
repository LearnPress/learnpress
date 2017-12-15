<?php
/**
 * Register Form Shortcode.
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

if ( ! class_exists( 'LP_Shortcode_Register_Form' ) ) {

	/**
	 * Class LP_Shortcode_Register_Form
	 */
	class LP_Shortcode_Register_Form extends LP_Abstract_Shortcode {
		/**
		 * LP_Shortcode_Register_Form constructor.
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
				learn_press_get_template( 'global/form-register.php', array( 'fields' => self::get_register_fields() ) );
				$output = ob_get_clean();
			}

			return $output;
		}

		/**
		 * Get fields for register form.
		 *
		 * @return array
		 */
		public static function get_register_fields() {
			$fields = array(
				'reg_username' => array(
					'title'       => __( 'Username', 'learnpress' ),
					'type'        => 'text',
					'placeholder' => __( 'Username', 'learnpress' ),
					'saved'       => LP_Request::get_string( 'reg_username' ),
					'id'          => 'reg_username',
					'required'    => true
				),
				'reg_email'    => array(
					'title'       => __( 'Email', 'learnpress' ),
					'type'        => 'email',
					'placeholder' => __( 'Email', 'learnpress' ),
					'saved'       => LP_Request::get_string( 'reg_email' ),
					'id'          => 'reg_email',
					'required'    => true
				),
				'reg_password' => array(
					'title'       => __( 'Password', 'learnpress' ),
					'type'        => 'password',
					'placeholder' => __( 'Password', 'learnpress' ),
					'saved'       => '',
					'id'          => 'reg_password',
					'required'    => true,
					'desc'        => __( 'The password should be at least twelve characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ & )', 'learnpress' )
				)
			);
			$fields = apply_filters( 'learn-press/register-fields', $fields );

			return $fields;
		}
	}
}