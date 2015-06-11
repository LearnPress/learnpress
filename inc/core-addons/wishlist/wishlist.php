<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
 * Show wishlist button
 */
add_action( 'learn_press_entry_footer_archive', 'learn_press_course_wishlist_button', 10 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_wishlist_button', 10 );
function learn_press_course_wishlist_button() {
	$user_id   = get_current_user_id();
	$course_id = get_the_ID();
//	 If user or course are invalid then return.
	if ( !$user_id || !$course_id ) {
		return;
	}

	$course_taken = get_user_meta( $user_id, '_lpr_user_course', true );
	// If user enrolled course then return
	if ( !$course_taken ) {
		$course_taken = array();
	}
	if ( in_array( $course_id, $course_taken ) ) {
		return;
	}
	learn_press_get_template( 'addons/course-wishlist/button.php' );

}

/*
 * Wishlist scripts processing
 */
function learn_press_wishlist_scripts() {
	wp_enqueue_style( 'course-wishlist-style', LPR_PLUGIN_URL . '/inc/core-addons/wishlist/source/wishlist.css' );
	wp_enqueue_script( 'course-wishlist-script', LPR_PLUGIN_URL . '/inc/core-addons/wishlist/source/wishlist.js', array( 'jquery' ), '1.0.0', true );
}

add_action( 'wp_enqueue_scripts', 'learn_press_wishlist_scripts' );

/**
 * Add course to wishlist
 */
add_action( 'wp_ajax_add_wish_list', 'learn_press_add_wish_list' );
add_action( 'wp_ajax_nopriv_add_wish_list', 'learn_press_add_wish_list' );
function learn_press_add_wish_list() {

	$course_id = $_POST['course_id'];
	$user_id   = get_current_user_id();
	$wish_list = get_user_meta( $user_id, '_lpr_wish_list', true );

	if ( !$wish_list ) {
		$wish_list = array();
	}

	if ( !in_array( $course_id, $wish_list ) ) {
		array_push( $wish_list, $course_id );
	}
	update_user_meta( $user_id, '_lpr_wish_list', $wish_list );
	die;
}

/**
 * Remove course from wishlist
 */
add_action( 'wp_ajax_remove_wish_list', 'learn_press_remove_wish_list' );
add_action( 'wp_ajax_nopriv_remove_wish_list', 'learn_press_remove_wish_list' );

function learn_press_remove_wish_list() {

	$course_id = $_POST['course_id'];
	$user_id   = get_current_user_id();
	$wish_list = get_user_meta( $user_id, '_lpr_wish_list', true );

	if ( !$wish_list ) {
		$wish_list = array();
	}
	$key = array_search( $course_id, $wish_list );
	if ( $key !== false ) {
		unset( $wish_list[$key] );
	}
	update_user_meta( $user_id, '_lpr_wish_list', $wish_list );
	die;
}


/**
 * Update user's wish list
 *
 * @param $user_id
 * @param $course_id
 */

add_action( 'learn_press_after_take_course', 'learn_press_update_wish_list', 10, 2 );
function learn_press_update_wish_list( $user_id, $course_id ) {
	if ( !$user_id || !$course_id ) {
		return;
	}
	$wish_list = get_user_meta( $user_id, '_lpr_wish_list', true );
	if ( !$wish_list ) {
		$wish_list = array();
	}
	$key = array_search( $course_id, $wish_list );
	if ( $key !== false ) {
		unset( $wish_list[$key] );
	}
	update_user_meta( $user_id, '_lpr_wish_list', $wish_list );
}

/*
 * Add wishlist tab into profile page
 */
add_filter( 'learn_press_profile_tabs', 'learn_press_wishlist_tab', 10, 2 );
function learn_press_wishlist_tab( $tabs, $user ) {
	$content = '';

	$tabs[35] = array(
		'tab_id'      => 'user_wishlist',
		'tab_name'    => __( 'Wishlist', 'learn_press' ),
		'tab_content' => apply_filters( 'learn_press_user_wishlist_tab_content', $content, $user )
	);
	// Private customize
	if ( $user->ID != get_current_user_id() ) {
		unset ( $tabs[35] );
	}
	return $tabs;
}

/*
 * Setup wishlist tab content
 */
add_filter( 'learn_press_user_wishlist_tab_content', 'learn_press_user_wishlist_tab_content', 10, 2 );
function learn_press_user_wishlist_tab_content( $content, $user ) {
	ob_start();
	learn_press_get_template( 'addons/course-wishlist/user-wishlist.php', array( 'user' => $user ) );
	$content .= ob_get_clean();
	return $content;
}


if ( learn_press_buddypress_is_active() ) {

	/*
	 * Set up sub admin bar wishlist
	 */
	add_filter( 'learn_press_bp_courses_bar', 'learn_press_bp_courses_bar_wishlist', 20 );
	function learn_press_bp_courses_bar_wishlist( $wp_admin_nav ) {

		$courses_slug = apply_filters( 'learn_press_bp_courses_slug', '' );
		$courses_link = learn_press_get_current_bp_link();

		$wp_admin_nav[] = array(
			'parent' => 'my-account-' . $courses_slug,
			'id'     => 'my-account-' . $courses_slug . '-wishlist',
			'title'  => __( 'Wishlist', 'learn_press' ),
			'href'   => trailingslashit( $courses_link . 'wishlist' )
		);
		return $wp_admin_nav;
	}

	/*
	 * Setup sub navigation wishlist
	 */
	if ( bp_is_my_profile() || current_user_can( 'manage_options' ) ) {
		add_filter( 'learn_press_bp_courses_sub_navs', 'learn_press_bp_courses_nav_wishlist' );

		function learn_press_bp_courses_nav_wishlist( $sub_navs ) {
			$nav_wishlist = array(
				'name'                    => __( 'Wishlist', 'learn_press' ),
				'slug'                    => 'wishlist',
				'show_for_displayed_user' => false,
				'position'                => 10,
				'screen_function'         => 'learn_press_bp_courses_wishlist',
				'parent_url'              => learn_press_get_current_bp_link(),
				'parent_slug'             => apply_filters( 'learn_press_bp_courses_slug', '' ),
			);
			array_push( $sub_navs, $nav_wishlist );
			return $sub_navs;
		}

		function learn_press_bp_courses_wishlist() {
			add_action( 'bp_template_title', 'learn_press_bp_courses_wishlist_title' );
			add_action( 'bp_template_content', 'learn_press_bp_courses_wishlist_content' );
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
		}

		/*
		 * Setup title of navigation all
		 */
		function learn_press_bp_courses_wishlist_title() {
			echo __( 'Your wishlist', 'learn_press' );
		}

		/*
		 * Setup content of navigation all
		 */
		function learn_press_bp_courses_wishlist_content() {
			global $bp;
			echo apply_filters( 'learn_press_user_wishlist_tab_content', '', get_user_by( 'id', $bp->displayed_user->id ) );
		}
	}
}

function learn_press_get_wishlist_courses( $user_id ) {
	$pid = get_user_meta( $user_id, '_lpr_wish_list', true );
	if ( !$pid ) {
		$pid = array( 0 );
	}
	$arr_query = array(
		'post_type'           => 'lpr_course',
		'post__in'            => $pid,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'posts_per_page'      => - 1
	);
	$my_query  = new WP_Query( $arr_query );
	return $my_query;
}

add_action( 'learn_press_after_wishlist_course_title', 'learn_press_course_wishlist_button', 10 );