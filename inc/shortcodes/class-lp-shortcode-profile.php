<?php

class LP_Shortcode_Profile extends LP_Abstract_Shortcode {
	/**
	 * LP_Checkout_Shortcode constructor.
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
		global $wp_query, $wp;
		if ( isset( $wp_query->query['user'] ) ) {
			$user = get_user_by( apply_filters( 'learn_press_get_user_requested_by', 'login' ), urldecode( $wp_query->query['user'] ) );
		} else {
			$user = get_user_by( 'id', get_current_user_id() );
		}
		ob_start();
		if ( ! $user ) {
			if ( empty( $wp_query->query['user'] ) ) {

			} else {
				learn_press_display_message( sprintf( __( 'The user %s is not available!', 'learnpress' ), $wp_query->query['user'] ), 'error' );
			}

		} else {
			$user = LP_User_Factory::get_user( $user->ID );
			$tabs = learn_press_get_user_profile_tabs( $user );
			if ( ! empty( $wp->query_vars['view'] ) ) {
				$current = $wp->query_vars['view'];
			} else {
				$current = '';
			}
			if ( empty( $tabs[ $current ] ) && empty( $wp->query_vars['view'] ) ) {
				$tab_keys = array_keys( $tabs );
				$current  = reset( $tab_keys );
			}
			$_REQUEST['tab'] = $current;
			$_POST['tab']    = $current;
			$_GET['tab']     = $current;
			if ( ! learn_press_current_user_can_view_profile_section( $current, $user ) ) {
				learn_press_get_template( 'profile/private-area.php' );
			} else {
				if ( ! empty( $tabs ) && ! empty( $tabs[ $current ] ) ) :
					learn_press_get_template( 'profile/profile.php',
						array(
							'user'    => $user,
							'tabs'    => $tabs,
							'current' => $current
						)
					);
				else:
					if ( $wp->query_vars['view'] == LP()->settings->get( 'profile_endpoints.profile-order-details' ) ) {
						$order_id = 0;
						if ( ! empty( $wp->query_vars['id'] ) ) {
							$order_id = $wp->query_vars['id'];
						}
						$order = learn_press_get_order( $order_id );
						if ( ! $order ) {
							learn_press_display_message( __( 'Invalid order!', 'learnpress' ), 'error' );
						} else {
							learn_press_get_template( 'profile/order-details.php',
								array(
									'user'  => $user,
									'order' => $order
								)
							);
						}
					}
				endif;
			}
		}
		$output = ob_get_clean();

		return $output;
	}
}