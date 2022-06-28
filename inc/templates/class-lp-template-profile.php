<?php
/**
 * Class LP_Profile_Template
 *
 * Group templates related user profile.
 *
 * @since 4.0.0
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
		$profile = LP_Global::profile();

		if ( $profile->get_user()->is_guest() ) {
			return;
		}

		learn_press_get_template( 'profile/sidebar.php' );
	}

	/**
	 * @param LP_Profile $user
	 */
	public function content( LP_Profile $user ) {
		$profile = LP_Global::profile();
		$user_id = get_current_user_id();

		if ( $profile->get_user()->is_guest() ) {
			return;
		}

		$current_tab = $profile->get_current_tab();

		if ( 'settings' === $current_tab && ( ! $user_id || $user_id != $profile->get_user()->get_id() ) ) {
			return;
		}

		$privacy = get_user_meta( $user->get_user()->get_id(), '_lp_profile_privacy', true );

		if ( ! current_user_can( ADMIN_ROLE ) && ( $user->get_user()->get_id() != $user_id && empty( $privacy ) ) ) {
			return;
		}

		$profile = learn_press_get_profile();
		/**
		 * LP_Profile_Tabs
		 */
		$tabs        = $profile->get_tabs();
		$tab_key     = $profile->get_current_tab();
		$profile_tab = $tabs->get( $tab_key );

		learn_press_get_template( 'profile/content.php', compact( 'user', 'profile_tab', 'tab_key', 'profile' ) );
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

		learn_press_get_template( 'profile/tabs.php', compact( 'user', 'profile' ) );
	}

	/**
	 * Get template tab course
	 *
	 * @author tungnx
	 * @since 4.1.5
	 * @version 1.0.0
	 * @return void
	 */
	public static function tab_courses() {
		if ( ! LP_Profile::instance()->current_user_can( 'view-tab-courses' ) ) {
			return;
		}

		$user = LP_Profile::instance()->get_user();

		$courses_enrolled_tab = apply_filters(
			'lp/profile/user_courses_attend/subtask',
			array(
				''            => esc_html__( 'All', 'learnpress' ),
				'in-progress' => esc_html__( 'In Progress', 'learnpress' ),
				'finished'    => esc_html__( 'Finished', 'learnpress' ),
				'passed'      => esc_html__( 'Passed', 'learnpress' ),
				'failed'      => esc_html__( 'Failed', 'learnpress' ),
			)
		);

		$courses_created_tab = apply_filters(
			'lp/profile/user_courses_created/subtask',
			array(
				''        => esc_html__( 'All', 'learnpress' ),
				'publish' => esc_html__( 'Publish', 'learnpress' ),
				'pending' => esc_html__( 'Pending', 'learnpress' ),
			)
		);

		$courses_enrolled_tab_active = apply_filters( 'learnpress/profile/tab/enrolled/subtab-active', ! learn_press_user_maybe_is_a_teacher() ? 'in-progress' : '' );
		$tab_active                  = LP_Helper::sanitize_params_submitted( $_GET['tab'] ?? '' );
		if ( ! $tab_active ) {
			$tab_active = ! learn_press_user_maybe_is_a_teacher() ? 'enrolled' : 'created';
		}
		$tab_active = apply_filters( 'learnpress/profile/tab-active', $tab_active );

		$args_query_user_courses_created   = apply_filters(
			'lp/profile/args/user_courses_created',
			array(
				'userID' => $user->get_id(),
				'query'  => 'own',
			)
		);
		$args_query_user_courses_attend    = apply_filters(
			'lp/profile/args/user_courses_attend',
			array(
				'userID' => $user->get_id(),
				'query'  => 'purchased',
				'layout' => 'list',
			)
		);
		$args_query_user_courses_statistic = apply_filters(
			'lp/profile/args/user_courses_statistic',
			array(
				'userID' => $user->get_id(),
			)
		);

		learn_press_get_template(
			'profile/tabs/courses',
			compact(
				'user',
				'courses_created_tab',
				'courses_enrolled_tab',
				'tab_active',
				'courses_enrolled_tab_active',
				'args_query_user_courses_attend',
				'args_query_user_courses_created',
				'args_query_user_courses_statistic'
			)
		);
	}

	/**
	 * @author tungnx
	 * @deprecated 4.1.6
	 */
	/*public function dashboard_statistic() {
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

		learn_press_get_template( 'profile/tabs/courses/general-statistic', compact( 'statistic' ) );
	}*/

	public function dashboard_featured_courses() {
		$profile_privacy = $this->get_user()->get_extra_data(
			'profile_privacy',
			array(
				'courses' => 'no',
				'quizzes' => 'no',
			)
		);

		if ( $this->get_user()->get_id() !== get_current_user_id() && 'yes' !== $profile_privacy['courses'] ) {
			return;
		}

		$user  = $this->get_user();
		$query = new LP_Course_Query(
			array(
				'paginate' => true,
				'featured' => 'yes',
				'return'   => 'ids',
				'author'   => $user->get_id(),
			)
		);

		$data = $query->get_courses();

		learn_press_get_template( 'profile/dashboard/featured-courses', (array) $data );
	}

	public function dashboard_latest_courses() {
		$profile_privacy = $this->get_user()->get_extra_data(
			'profile_privacy',
			array(
				'courses' => 'no',
				'quizzes' => 'no',
			)
		);

		if ( $this->get_user()->get_id() !== get_current_user_id() && 'yes' !== $profile_privacy['courses'] ) {
			return;
		}

		$user  = $this->get_user();
		$query = new LP_Course_Query(
			array(
				'paginate' => true,
				'return'   => 'ids',
				'author'   => $user->get_id(),
			)
		);

		learn_press_get_template( 'profile/dashboard/latest-courses', (array) $query->get_courses() );
	}

	public function order_details() {
		$profile = LP_Profile::instance();

		$order = $profile->get_view_order();

		if ( false === $order ) {
			return;
		}

		learn_press_get_template( 'order/order-details.php', array( 'order' => $order ) );
	}

	public function order_recover() {
		$profile = LP_Profile::instance();
		$order   = $profile->get_view_order();

		if ( false === $order ) {
			return;
		}

		//learn_press_get_template( 'profile/tabs/orders/recover-my-order.php', array( 'order' => $order ) );
	}

	public function order_message() {
		$profile = LP_Profile::instance();
		$order   = $profile->get_view_order();

		if ( false === $order ) {
			return;
		}

		learn_press_get_template( 'profile/tabs/orders/order-message.php', array( 'order' => $order ) );
	}

	public function dashboard_not_logged_in() {
		$profile = LP_Global::profile();

		if ( ! $profile->get_user()->is_guest() ) {
			return;
		}

		if ( 'yes' === LP_Settings::instance()->get( 'enable_login_profile' ) || 'yes' === LP_Settings::instance()->get( 'enable_register_profile' ) ) {
			return;
		}

		learn_press_get_template( 'profile/not-logged-in.php' );
	}

	public function login_form() {
		$profile = LP_Global::profile();

		if ( ! $profile->get_user()->is_guest() ) {
			return;
		}

		if ( 'yes' !== LP_Settings::instance()->get( 'enable_login_profile' ) ) {
			return;
		}

		learn_press_get_template( 'global/form-login.php' );
	}

	public function register_form() {
		$profile = LP_Global::profile();

		if ( ! $profile->get_user()->is_guest() ) {
			return;
		}

		if ( 'yes' !== LP_Settings::instance()->get( 'enable_register_profile' ) || ! get_option( 'users_can_register' ) ) {
			return;
		}

		learn_press_get_template( 'global/form-register.php' );
	}

	/**
	 * @return bool|LP_User|mixed
	 */
	protected function get_user() {
		return LP_Profile::instance()->get_user();
	}
}

return new LP_Template_Profile();
