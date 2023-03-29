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

use LearnPress\Helpers\Template;

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
		 * @return bool|LP_User|LP_User_Guest|WP_Error
		 */
		public function can_view_profile() {
			global $wp;

			$current_user = learn_press_get_current_user();
			$viewing_user = true;

			if ( ! current_user_can( ADMIN_ROLE ) ) {
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
			}

			if ( ! $viewing_user ) {
				return new WP_Error( 'cannot-view-profile', esc_html__( 'You can\'t view the user profile', 'learnpress' ) );
			}

			return $viewing_user;
		}

		/**
		 * Shortcode content.
		 *
		 * @return string
		 */
		public function output() {
			$profile = LP_Global::profile();
			$output  = '';
			wp_enqueue_style( 'learnpress' );
			wp_enqueue_script( 'lp-profile' );

			try {
				ob_start();
				if ( is_wp_error( $this->can_view_profile() ) ) {
					$messages = [
						'status'  => 'error',
						'content' => $this->can_view_profile()->get_error_message(),
					];

					learn_press_set_message( $messages );
					learn_press_show_message();
				} else {
					//learn_press_print_messages();
					learn_press_show_message();
					//learn_press_get_template( 'pages/profile.php', array( 'profile' => $profile ) );
					Template::instance()->get_frontend_template( 'pages/profile.php', compact( 'profile' ) );
				}

				$output = ob_get_clean();
			} catch ( Throwable $e ) {
				ob_end_clean();
				error_log( $e->getMessage() );
			}

			return $output;
		}
	}
}
