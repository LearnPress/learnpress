<?php

use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\Profile\ProfileTemplate;
use LearnPress\TemplateHooks\UserTemplate;

/**
 * Class LP_Profile_Template
 *
 * Group templates related user profile.
 *
 * @since 4.0.0
 */
class LP_Template_Profile extends LP_Abstract_Template {
	public function header( $user ) {
		learn_press_get_template( 'profile/header.php', array( 'user' => $user ) );
	}

	public function sidebar() {
		$profile = LP_Profile::instance();
		if ( $profile->get_user()->is_guest() ) {
			return;
		}

		if ( $profile->get_user_current()->is_guest()
			&& 'yes' !== LP_Profile::get_option_publish_profile() ) {
			return;
		}

		$user      = $profile->get_user();
		$userModel = UserModel::find( $user->get_id(), true );
		// Display cover image
		echo ProfileTemplate::instance()->html_cover_image( $userModel );
		// Display Sidebar
		learn_press_get_template( 'profile/sidebar.php' );
	}

	/**
	 * @param LP_Profile $profile
	 *
	 * @since 3.0.0
	 * @version 1.0.1
	 */
	public function content( LP_Profile $profile ) {
		$user          = $profile->get_user();
		$current_tab   = $profile->get_current_tab();
		$user_can_view = $profile->current_user_can( 'view-tab-' . $current_tab );
		if ( ! $user_can_view ) {
			return;
		}

		if ( $profile->get_user_current()->is_guest() ) {
			return;
		}

		$tabs        = $profile->get_tabs();
		$tab_key     = $profile->get_current_tab();
		$profile_tab = $tabs->get( $tab_key );

		learn_press_get_template( 'profile/content.php', compact( 'user', 'profile_tab', 'tab_key', 'profile' ) );
	}

	/**
	 * Display avatar
	 *
	 * @return void
	 * @version 1.0.1
	 * @since 3.x.x
	 */
	public function avatar() {
		$lp_profile = LP_Profile::instance();
		$user       = $lp_profile->get_user();
		$userModel  = UserModel::find( $user->get_id(), true );
		if ( ! $userModel instanceof UserModel ) {
			return;
		}

		$userTemplate = new UserTemplate();
		echo $userTemplate->html_avatar_edit( $userModel );
		//learn_press_get_template( 'profile/avatar.php' );
	}

	public function socials() {
		learn_press_get_template( 'profile/socials.php' );
	}

	public function tabs( $user = null ) {
		$profile = LP_Profile::instance();
		if ( $profile->get_user_current()->is_guest() ) {
			return;
		}

		learn_press_get_template( 'profile/tabs.php', compact( 'user', 'profile' ) );
	}

	/**
	 * Get template tab course
	 *
	 * @return void
	 * @since 4.1.5
	 * @version 1.0.0
	 * @author tungnx
	 */
	public static function tab_courses() {
		if ( ! LP_Profile::instance()->current_user_can( 'view-tab-courses' ) ) {
			return;
		}

		$user = LP_Profile::instance()->get_user();

		$courses_created_tab = apply_filters(
			'lp/profile/user_courses_created/subtask',
			array(
				''        => esc_html__( 'All', 'learnpress' ),
				'publish' => esc_html__( 'Publish', 'learnpress' ),
				'pending' => esc_html__( 'Pending', 'learnpress' ),
			)
		);

		$args_query_user_courses_created = apply_filters(
			'lp/profile/args/user_courses_created',
			array(
				'userID' => $user->get_id(),
				'query'  => 'own',
			)
		);

		$args_query_user_courses_statistic = apply_filters(
			'lp/profile/args/user_courses_statistic',
			array(
				'userID' => $user->get_id(),
			)
		);

		do_action(
			'learn-press/profile/layout/courses',
			compact(
				'user',
				'courses_created_tab',
				'args_query_user_courses_created',
				'args_query_user_courses_statistic'
			)
		);
	}

	public static function tab_my_courses() {
		if ( ! LP_Profile::instance()->current_user_can( 'view-tab-my-courses' ) ) {
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

		$courses_enrolled_tab_active = apply_filters( 'learnpress/profile/tab/enrolled/subtab-active', ! learn_press_user_maybe_is_a_teacher() ? 'in-progress' : '' );

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
			'profile/tabs/my_courses',
			compact(
				'user',
				'courses_enrolled_tab',
				'courses_enrolled_tab_active',
				'args_query_user_courses_attend',
				'args_query_user_courses_statistic'
			)
		);
	}

	/**
	 * Display tab avatar image
	 *
	 * @return void
	 * @since 4.2.8.2
	 * @version 1.0.0
	 */
	public static function tab_avatar() {
		if ( ! LP_Profile::instance()->current_user_can( 'view-tab-avatar' ) ) {
			return;
		}

		$user      = LP_Profile::instance()->get_user();
		$userModel = UserModel::find( $user->get_id(), true );
		if ( ! $userModel ) {
			return;
		}

		echo ProfileTemplate::instance()->html_upload_avatar( $userModel );
	}

	/**
	 * Display tab cover image
	 *
	 * @return void
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public static function tab_cover_image() {
		if ( ! LP_Profile::instance()->current_user_can( 'view-tab-cover-image' ) ) {
			return;
		}

		$user      = LP_Profile::instance()->get_user();
		$userModel = UserModel::find( $user->get_id(), true );
		if ( ! $userModel ) {
			return;
		}

		echo ProfileTemplate::instance()->html_upload_cover_image( $userModel );
	}

	public function order_details() {
		$profile = LP_Profile::instance();
		$order   = $profile->get_view_order();
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
		if ( is_user_logged_in() ) {
			return;
		}

		if ( ! LP_Profile::instance()->get_user()->is_guest() ) {
			return;
		}

		if ( 'yes' === LP_Settings::get_option( 'enable_login_profile' ) ) {
			return;
		}

		learn_press_get_template( 'profile/not-logged-in.php' );
	}

	public function login_form() {
		if ( is_user_logged_in() && ! LP_Page_Controller::is_page_profile() ) {
			Template::print_message(
				esc_html__( 'You are already logged in.', 'learnpress' )
			);
			return;
		}

		if ( ! LP_Profile::instance()->get_user()->is_guest() ) {
			return;
		}

		if ( 'yes' !== LP_Settings::get_option( 'enable_login_profile', 'no' ) ) {
			return;
		}

		learn_press_get_template( 'global/form-login.php' );
	}

	public function register_form() {
		if ( is_user_logged_in() ) {
			return;
		}

		if ( ! LP_Profile::instance()->get_user()->is_guest() ) {
			return;
		}

		if ( 'yes' !== LP_Settings::get_option( 'enable_register_profile' ) || ! get_option( 'users_can_register' ) ) {
			return;
		}

		learn_press_get_template( 'global/form-register.php' );
	}
}

return new LP_Template_Profile();
