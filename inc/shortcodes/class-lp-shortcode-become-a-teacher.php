<?php
/**
 * Become A Teacher Shortcode.
 *
 * @author  ThimPress
 * @category Shortcodes
 * @package  Learnpress/Shortcodes
 * @version  4.0.0
 * @extends  LP_Abstract_Shortcode
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Shortcode_Become_A_Teacher' ) ) {
	class LP_Shortcode_Become_A_Teacher extends LP_Abstract_Shortcode {

		/**
		 * @var array
		 */
		protected static $messages = array();

		/**
		 * LP_Checkout_Shortcode constructor.
		 *
		 * @param mixed $atts
		 */
		public function __construct( $atts = '' ) {
			parent::__construct( $atts );
		}

		/**
		 * Add new message into queue.
		 *
		 * @param        $message
		 * @param string  $code
		 */
		public static function add_message( $message, $code = '' ) {
			self::$messages[ $code ] = $message;
		}

		/**
		 * Get added message by code from queue.
		 *
		 * @param string $code
		 *
		 * @return bool|mixed
		 */
		public static function get_message( $code = '' ) {
			return isset( self::$messages[ $code ] ) ? self::$messages[ $code ] : false;
		}

		/**
		 * Get all messages added into queue.
		 *
		 * @return array
		 */
		public static function get_messages() {
			return self::$messages;
		}

		/**
		 * Output form.
		 *
		 * @return string
		 */
		public function output() {
			ob_start();

			$message = '';
			$atts    = $this->get_atts();
			$user    = learn_press_get_current_user( false );

			if ( ! $user || $user instanceof LP_User_Guest ) {
				$message = sprintf( esc_html__( 'Please %s to send your request!', 'learnpress' ), sprintf( '<strong><a href="%s">%s</a></strong>', learn_press_get_login_url(), _x( 'login', 'become-teacher-form', 'learnpress' ) ) );
			} else {
				if ( learn_press_become_teacher_sent() ) {
					$message = esc_html__( 'Your have already sent the request. Please wait for approvement.', 'learnpress' );
				} elseif ( learn_press_user_maybe_is_a_teacher() ) {
					$message = esc_html__( 'You are a teacher!', 'learnpress' );
				}
			}

			if ( apply_filters( 'learn_press_become_a_teacher_display_form', true, $message ) ) {
				$atts = shortcode_atts(
					array(
						'title'                      => esc_html__( 'Become a Teacher', 'learnpress' ),
						'description'                => esc_html__( 'Fill in your information and send us to become a teacher.', 'learnpress' ),
						'submit_button_text'         => esc_html__( 'Submit', 'learnpress' ),
						'submit_button_process_text' => esc_html__( 'Processing', 'learnpress' ),
					),
					$atts
				);

				wp_enqueue_style( 'learnpress' );
				wp_enqueue_script( 'lp-become-a-teacher' );

				if ( empty( $message ) || ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) ) {
					learn_press_get_template( 'global/become-teacher-form.php', $atts );
				} else {
					learn_press_display_message( $message );
				}
			}

			return ob_get_clean();
		}
	}
}
