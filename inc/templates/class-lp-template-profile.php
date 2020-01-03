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

	public function sidebar() {
		learn_press_get_template( 'profile/sidebar.php' );
	}

	public function content( $user ) {
		$profile = LP_Global::profile();

		if ( $profile->get_user()->is_guest() ) {
			return;
		}

		learn_press_get_template( 'profile/content.php', array( 'user' => $user ) );
	}

	public function avatar() {
		learn_press_get_template( 'profile/avatar.php' );
	}

	public function socials() {
		learn_press_get_template( 'profile/socials.php' );
	}

	public function tabs( $user = null ) {
		$profile = LP_Global::profile();

		if ( $profile->get_user()->is_guest() ) {
			return;
		}

		learn_press_get_template( 'profile/tabs.php', array( 'user' => $user ) );
	}

	public function dashboard_statistic() {
		$user      = $this->get_user();
		$query     = LP_Profile::instance()->query_courses( 'purchased' );
		$counts    = $query['counts'];
		$statistic = array(
			'enrolled_courses'  => isset( $counts['all'] ) ? $counts['all'] : 0,
			'active_courses'    => isset( $counts['in-progress'] ) ? $counts['in-progress'] : 0,
			'completed_courses' => isset( $counts['finished'] ) ? $counts['finished'] : 0,
			'total_courses'     => count_user_posts( $user->get_id(), LP_COURSE_CPT ),
			'total_users'       => learn_press_count_instructor_users( $user->get_id() ),
		);

		learn_press_get_template( 'profile/dashboard/general-statistic', compact( 'statistic' ) );
	}

	public function dashboard_featured_courses() {

		$user  = $this->get_user();
		$query = new LP_Course_Query(
			array(
				'paginate' => true,
				'featured' => 'yes',
				'return'   => 'ids',
				'author'   => $user->get_id()
			)
		);

		$data = $query->get_courses();

		learn_press_get_template( 'profile/dashboard/featured-courses', (array) $data );
	}

	public function dashboard_latest_courses() {
		$user  = $this->get_user();
		$query = new LP_Course_Query(
			array(
				'paginate' => true,
				'return'   => 'ids',
				'author'   => $user->get_id()
			)
		);

		learn_press_get_template( 'profile/dashboard/latest-courses', (array) $query->get_courses() );
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

	/**
	 * @return bool|LP_User|mixed
	 */
	protected function get_user() {
		return LP_Profile::instance()->get_user();
	}
}

return new LP_Template_Profile();