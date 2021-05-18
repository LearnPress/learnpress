<?php
/**
 * Profile Page Shortcode.
 *
 * @author   ThimPress
 * @category Shortcodes
 * @package  Learnpress/Shortcodes
 * @version  4.0.0
 * @extends  LP_Abstract_Shortcode
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


		public function can_view_profile() {
			global $wp;

			$current_user = learn_press_get_current_user();
			$viewing_user = true;

			if ( empty( $wp->query_vars['user'] ) ) {
				$viewing_user = $current_user;
			} else {
				$wp_user = get_user_by( 'login', urldecode( $wp->query_vars['user'] ) );

				if ( $wp_user ) {
					$viewing_user = learn_press_get_user( $wp_user->ID );

					if ( $viewing_user->is_guest() ) {
						$viewing_user = false;
					}
				}
			}

			if ( ! $viewing_user ) {
				return new WP_Error( 'cannot-view-profile', esc_html__( 'You can\'t viewing user profile', 'learnpress' ) );
			}
		}

		/**
		 * Shortcode content.
		 *
		 * @return string
		 */
		public function output() {
			$profile = LP_Global::profile();

			wp_enqueue_style( 'learnpress' );
			wp_enqueue_style( 'lp-font-awesome-5' );

			ob_start();

			if ( is_wp_error( $this->can_view_profile() ) ) {
				$messages = array(
					'error' => array(
						'content' => ! empty( $this->can_view_profile()->get_error_message() ) ? $this->can_view_profile()->get_error_message() : 'LearnPress Profile: Error',
					),
				);

				echo '<div class="lp-content-area">';
				learn_press_get_template( 'global/message.php', array( 'messages' => $messages ) );
				echo '</div>';
			} else {
				learn_press_print_messages();
				learn_press_get_template( 'pages/profile.php', array( 'profile' => $profile ) );
			}

			$output = ob_get_clean();

			return $output;
		}
	}
}
