<?php

/**
 * Class LP_Profile_Template
 *
 * Group templates related user profile.
 *
 * @since 3.x.x
 */
class LP_Template_Profile extends LP_Abstract_Template {
	public function header( $user ) {
		$profile = LP_Global::profile();

		if ( $profile->get_user()->is_guest() ) {
			return;
		}

		learn_press_get_template( 'profile/header.php', array( 'user' => $user ) );
	}

	public function sidebar(){
		learn_press_get_template( 'profile/sidebar.php' );
	}

	public function content( $user ) {
		$profile = LP_Global::profile();

		if ( $profile->get_user()->is_guest() ) {
			return;
		}

		learn_press_get_template( 'profile/content.php', array( 'user' => $user ) );
	}

	public function avatar(){
		learn_press_get_template( 'profile/avatar.php');
	}

	public function socials(){
		learn_press_get_template( 'profile/socials.php');
	}

	public function tabs( $user = null ) {
		$profile = LP_Global::profile();

		if ( $profile->get_user()->is_guest() ) {
			return;
		}

		learn_press_get_template( 'profile/tabs.php', array( 'user' => $user ) );
	}

	public function dashboard_statistic(){
		learn_press_get_template( 'profile/dashboard/general-statistic');
	}

	public function dashboard_featured_courses(){
		learn_press_get_template( 'profile/dashboard/featured-courses');
	}

	////////////


	public function order_details() {
		$profile = LP_Profile::instance();

		if ( false === ( $order = $profile->get_view_order() ) ) {
			return;
		}

		learn_press_get_template( 'order/order-details.php', array( 'order' => $order ) );
	}

	public function order_recover() {
		$profile = LP_Profile::instance();

		if ( false === ( $order = $profile->get_view_order() ) ) {
			return;
		}
		learn_press_get_template( 'profile/tabs/orders/recover-my-order.php', array( 'order' => $order ) );
	}

	public function order_message() {
		$profile = LP_Profile::instance();

		if ( false === ( $order = $profile->get_view_order() ) ) {
			return;
		}
		learn_press_get_template( 'profile/tabs/orders/order-message.php', array( 'order' => $order ) );
	}

	public function dashboard_logged_in() {
		learn_press_get_template( 'profile/dashboard-logged-in.php' );
	}

	public function dashboard_user_bio() {
		$profile = LP_Profile::instance();

		if ( ! $user = $profile->get_user() ) {
			return;
		}

		learn_press_get_template( 'profile/user-bio.php' );
	}

	public function dashboard_not_logged_in() {
		$profile = LP_Global::profile();

		if ( ! $profile->get_user()->is_guest() ) {
			return;
		}

		if ( 'yes' === LP()->settings()->get( 'enable_register_profile' ) || 'yes' === LP()->settings()->get( 'enable_login_profile' ) ) {
			return;
		}

		learn_press_get_template( 'profile/not-logged-in.php' );
	}

	public function login_form() {
		$profile = LP_Global::profile();

		print_r( metadata_exists( 'user', $profile->get_user()->get_id(), '_lp_temp_user' ) );
		if ( ! $profile->get_user()->is_guest() ) {
			return;
		}

		if ( ! $fields = $profile->get_login_fields() ) {
			return;
		}

		if ( 'yes' !== LP()->settings()->get( 'enable_login_profile' ) ) {
			return;
		}

		learn_press_get_template( 'global/form-login.php', array( 'fields' => $fields ) );
	}

	public function register_form() {
		$profile = LP_Global::profile();

		if ( ! $profile->get_user()->is_guest() ) {
			return;
		}

		if ( ! $fields = $profile->get_register_fields() ) {
			return;
		}

		if ( 'yes' !== LP()->settings()->get( 'enable_register_profile' ) ) {
			return;
		}

		learn_press_get_template( 'global/form-register.php', array( 'fields' => $fields ) );
	}
}

return new LP_Template_Profile();