<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
 * Setup courses navigation in Buddy Press
 */
if ( learn_press_buddypress_is_active() ) {
	/*
	 * Add BuddyProfile method
	 */
	add_filter( 'learn_press_profile_methods', 'learn_press_buddy_press_profile' );
	function learn_press_buddy_press_profile( $methods ) {
		$methods['bp_profile'] = __( 'BuddyPress Profile', 'learn_press' );
		return $methods;
	}

	/*
	 * Check BuddyPress profile method
	 */
	function learn_press_has_buddypress_profile_method() {
		$bp_profile = get_option( '_lpr_settings_general', array() );
		if ( isset( $bp_profile['set_page'] ) && $bp_profile['set_page'] == 'bp_profile' ) {
			return true;
		}
		return false;
	}

	if ( learn_press_has_buddypress_profile_method() ) {
		add_action( 'wp_loaded', 'learn_press_setup_courses_nav' );
		add_action( 'bp_setup_admin_bar', 'learn_press_setup_courses_bar' );

		function learn_press_setup_courses_nav() {
			$courses_nav = apply_filters( 'learn_press_bp_courses_nav', array() );

			bp_core_new_nav_item( $courses_nav );

			$sub_navs = apply_filters( 'learn_press_bp_courses_sub_navs', array() );
			foreach ( $sub_navs as $sub_nav ) {
				bp_core_new_subnav_item( $sub_nav );
			}
		}

		function learn_press_setup_courses_bar() {

			// Bail if this is an ajax request
			if ( defined( 'DOING_AJAX' ) ) {
				return;
			}

			// Do not proceed if BP_USE_WP_ADMIN_BAR constant is not set or is false
			if ( !bp_use_wp_admin_bar() || !is_user_logged_in() ) {
				return;
			}
			$course_slug = apply_filters( 'learn_press_bp_courses_slug', '' );
			// Filter the passed admin nav
			$wp_admin_nav = apply_filters( 'learn_press_bp_courses_bar', array() );

			// Do we have Toolbar menus to add?
			if ( !empty( $wp_admin_nav ) ) {


				// Define the WordPress global
				global $wp_admin_bar;

				// Add each admin menu
				foreach ( $wp_admin_nav as $admin_menu ) {
					$wp_admin_bar->add_menu( $admin_menu );
				}
			}

			// Call action
			do_action( 'bp_' . $course_slug . '_setup_admin_bar' );
		}


		/*
		 * Setup main navigation
		 */
		add_filter( 'learn_press_bp_courses_nav', 'learn_press_bp_courses_nav' );
		function learn_press_bp_courses_nav() {
			return array(
				'name'                    => apply_filters( 'learn_press_bp_courses_name', '' ),
				'slug'                    => apply_filters( 'learn_press_bp_courses_slug', '' ),
				'show_for_displayed_user' => true,
				'position'                => 40,
				'screen_function'         => 'learn_press_bp_courses_all',
				'default_subnav_slug'     => 'all',
			);
		}

		/*
		 * Setup name of main navigation
		 */
		add_filter( 'learn_press_bp_courses_name', 'learn_press_bp_courses_name' );
		function learn_press_bp_courses_name() {
			return __( 'Courses', 'learn_press' );

		}

		/*
		 * Setup slug of main navigation
		 */
		add_filter( 'learn_press_bp_courses_slug', 'learn_press_bp_courses_slug' );
		function learn_press_bp_courses_slug() {
			return 'courses';
		}

		/*
		 * Setup sub navigation
		 */
		add_filter( 'learn_press_bp_courses_sub_navs', 'learn_press_bp_courses_nav_all' );
		function learn_press_bp_courses_nav_all( $sub_navs ) {
			$nav_all = array(
				'name'                    => __( 'Course', 'learn_press' ),
				'slug'                    => 'all',
				'show_for_displayed_user' => true,
				'position'                => 10,
				'screen_function'         => 'learn_press_bp_courses_all',
				'parent_url'              => learn_press_get_current_bp_link(),
				'parent_slug'             => apply_filters( 'learn_press_bp_courses_slug', '' ),
			);
			array_push( $sub_navs, $nav_all );
			return $sub_navs;
		}

		/*
		 * Setup sub navigation
		 */
		if ( bp_is_my_profile() || current_user_can( 'manage_options' ) ) {
			add_filter( 'learn_press_bp_courses_sub_navs', 'learn_press_bp_courses_nav_quiz_results' );
		}
		function learn_press_bp_courses_nav_quiz_results( $sub_navs ) {
			$nav_all = array(
				'name'                    => __( 'Quiz results', 'learn_press' ),
				'slug'                    => 'quiz_results',
				'show_for_displayed_user' => false,
				'position'                => 15,
				'screen_function'         => 'learn_press_bp_courses_quiz_results',
				'parent_url'              => learn_press_get_current_bp_link(),
				'parent_slug'             => apply_filters( 'learn_press_bp_courses_slug', '' ),
			);
			array_push( $sub_navs, $nav_all );
			return $sub_navs;
		}

		/*
		 * Setup course bar
		 */
		add_filter( 'learn_press_bp_courses_bar', 'learn_press_bp_courses_bar' );
		function learn_press_bp_courses_bar( $wp_admin_nav ) {
			// Add main Courses menu
			global $bp;
			$courses_slug   = apply_filters( 'learn_press_bp_courses_slug', '' );
			$courses_name   = apply_filters( 'learn_press_bp_courses_name', '' );
			$courses_link   = learn_press_get_current_bp_link();
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $courses_slug,
				'title'  => $courses_name,
				'href'   => trailingslashit( $courses_link )
			);

			// All courses
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $courses_slug,
				'id'     => 'my-account-' . $courses_slug . '-all',
				'title'  => __( 'All courses', 'learn_press' ),
				'href'   => trailingslashit( $courses_link . 'all' )
			);
			// Quiz results
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $courses_slug,
				'id'     => 'my-account-' . $courses_slug . '-quiz_results',
				'title'  => __( 'Quiz Results', 'learn_press' ),
				'href'   => trailingslashit( $courses_link . 'quiz_results' )
			);
			return $wp_admin_nav;
		}

		function learn_press_bp_courses_all() {
			add_action( 'bp_template_title', 'learn_press_bp_courses_all_title' );
			add_action( 'bp_template_content', 'learn_press_bp_courses_all_content' );
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
		}

		function learn_press_bp_courses_quiz_results() {
			add_action( 'bp_template_title', 'learn_press_bp_courses_quiz_results_title' );
			add_action( 'bp_template_content', 'learn_press_bp_courses_quiz_results_content' );
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
		}

		/*
		 * Setup title of navigation all
		 */
		function learn_press_bp_courses_all_title() {
			echo __( 'All courses', 'learn_press' );
		}

		/*
		 * Setup content of navigation all
		 */
		function learn_press_bp_courses_all_content() {
			global $bp;
			echo apply_filters( 'learn_press_user_courses_tab_content', '', get_user_by( 'id', $bp->displayed_user->id ) );
		}

		/*
		 * Setup title of navigation quiz results
		 */
		function learn_press_bp_courses_quiz_results_title() {
			echo __( 'Quiz Results', 'learn_press' );
		}

		/*
		 * Setup content of navigation quiz results
		 */
		function learn_press_bp_courses_quiz_results_content() {
			global $bp;
			echo apply_filters( 'learn_press_user_quizzes_tab_content', '', get_user_by( 'id', $bp->displayed_user->id ) );
		}

		/*
		 * Set up courses navigation link
		 */
		function learn_press_get_current_bp_link() {

			// Determine user to use
			if ( bp_displayed_user_domain() ) {
				$user_domain = bp_displayed_user_domain();
			} elseif ( bp_loggedin_user_domain() ) {
				$user_domain = bp_loggedin_user_domain();
			} else {
				return;
			}

			// Link to user courses
			return trailingslashit( $user_domain . apply_filters( 'learn_press_bp_courses_slug', '' ) );
		}

		add_filter( 'learn_press_instructor_profile_link', 'learn_press_get_bp_link', 20, 2 );
		function learn_press_get_bp_link( $link, $course_id ) {
			// Determine user to use
			$course  = get_post( $course_id );
			$user_id = $course->post_author;
			$link    = bp_core_get_user_domain( $user_id );
			return trailingslashit( $link . apply_filters( 'learn_press_bp_courses_slug', '' ) );
		}
	}

}