<?php
/**
 * Common functions to process actions about user
 *
 * @author  ThimPress
 * @package LearnPress/Functions/User
 * @version 1.0
 */

add_action( 'learn_press_user_finished_course', 'learn_press_user_finished_course_send_email', 999, 2 );

function learn_press_user_finished_course_send_email( $course_id = null, $user_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) $user_id = get_current_user_id();
	$user = get_user_by( 'id', $user_id );
	if ( empty( $user->ID ) || !$course_id ) return false;
	$mail_to = $user->user_email;

	$assessment = get_post_meta( $course_id, '_lpr_course_final', true );
	if ( 'yes' == $assessment ) {
		$quiz_id       = lpr_get_final_quiz( $course_id );
		$quiz_result   = learn_press_get_quiz_result( $user_id, $quiz_id );
		$course_result = $quiz_result['mark_percent'] * 100;
	} else {
		$course_result = 100;
	}
	$args = apply_filters(
		'learn_press_vars_passed_course',
		array(
			'user_name'     => !empty( $user->display_name ) ? $user->display_name : $user->user_nicename,
			'course_name'   => get_the_title( $course_id ),
			'course_link'   => get_permalink( $course_id ),
			'course_result' => sprintf( __( '%d%% of total', 'learnpress' ), intval( $course_result ) )
		)
	);
	learn_press_send_mail(
		$mail_to,
		'passed_course',
		$args
	);
}

/**
 * @return int
 */
function learn_press_get_current_user_id() {
	$user = learn_press_get_current_user();
	return $user->id;
}

/**
 * Get current user
 * @return LP_User
 */
function learn_press_get_current_user() {
	$user_id      = get_current_user_id();
	$current_user = false;
	if ( $user_id ) {
		$current_user = learn_press_get_user( $user_id );
	} else {
		$current_user = LP_User_Guest::instance();
	}
	return $current_user;
}

/**
 * @param      $user_id
 *
 * @param bool $force
 *
 * @return LP_User_Guest|mixed
 */
function learn_press_get_user( $user_id, $force = false ) {
	if ( $user_id ) {
		return LP_User::get_user( $user_id, $force );
	}
	return LP_User_Guest::instance();
}

/**
 * Get all prerequisite courses that user need to pass before take a course
 *
 * @param bool $user_id
 * @param      $course_id -=90a4
 *
 * @return array|bool
 */
function learn_press_user_prerequisite_courses( $user_id = false, $course_id ) {
	if ( !$user_id ) {
		$user    = learn_press_get_current_user();
		$user_id = $user->ID;
	}
	$prerequisite = (array) get_post_meta( $course_id, '_lpr_course_prerequisite', true );
	$courses      = false;
	if ( $prerequisite ) {

		$course_completed = get_user_meta( $user_id, '_lpr_course_completed', true );
		foreach ( $prerequisite as $course ) {
			if ( $course && $course_completed ) {
				if ( !array_key_exists( $course, $course_completed ) ) {
					if ( !$courses ) $courses = array();
					$courses[] = $course;
				}
			}
		}
	}
	return $courses;
}

function learn_press_before_take_course_prerequisite( $can_take, $user_id, $course_id ) {
	return false == learn_press_user_prerequisite_courses( $user_id, $course_id );
}

//add_filter( 'learn_press_before_take_course', 'learn_press_before_take_course_prerequisite', 5, 3 );


function learn_press_send_user_email_order( $status, $order_id ) {
	return;
	$status = strtolower( $status );
	if ( 'completed' == $status ) {
		$order        = new LP_Order( $order_id );
		$mail_to      = $order->get_user( 'email' );
		$instructors  = array();
		$course_title = '';

		$transaction_object = $order->get_items();
		$items              = $transaction_object->products;
		$item               = array_shift( $items );

		$course = get_post( $item['id'] );

		$course_title = get_the_title( $item['id'] );

		$instructor                   = LP_User::get_user( $course->post_author );
		$instructors[$instructor->ID] = $instructor->data->display_name;

		learn_press_send_mail(
			$mail_to,
			'enrolled_course',
			apply_filters(
				'learn_press_vars_enrolled_course',
				array(
					'user_name'   => $order->get_user( 'display_name' ),
					'course_name' => $course_title,
					'course_link' => get_permalink( $item['id'] )
				),
				$course,
				$instructor
			)
		);
	}
}

add_action( 'learn_press_update_order_status', 'learn_press_send_user_email_order', 5, 2 );

/**
 * Add more 2 user roles teacher and student
 *
 * @access public
 * @return void
 */
function learn_press_add_user_roles() {
	$settings = LP()->settings;
	/* translators: user role */
	_x( 'Instructor', 'User role' );
	add_role(
		LP()->teacher_role,
		'Instructor',
		array()
	);
	$course_cap = LP()->course_post_type . 's';
	$lesson_cap = LP()->lesson_post_type . 's';
	$order_cap  = LP()->order_post_type . 's';
	// teacher
	$teacher = get_role( LP()->teacher_role );
	$teacher->add_cap( 'delete_published_' . $course_cap );
	$teacher->add_cap( 'edit_published_' . $course_cap );
	$teacher->add_cap( 'edit_' . $course_cap );
	$teacher->add_cap( 'delete_' . $course_cap );

	$settings->get( 'required_review' );

	if ( $settings->get( 'required_review' ) == 'yes' ) {
		$teacher->remove_cap( 'publish_' . $course_cap );
	} else {
		$teacher->add_cap( 'publish_' . $course_cap );
	}
	//


	$teacher->add_cap( 'delete_published_' . $lesson_cap );
	$teacher->add_cap( 'edit_published_' . $lesson_cap );
	$teacher->add_cap( 'edit_' . $lesson_cap );
	$teacher->add_cap( 'delete_' . $lesson_cap );
	$teacher->add_cap( 'publish_' . $lesson_cap );
	$teacher->add_cap( 'upload_files' );
	$teacher->add_cap( 'read' );
	$teacher->add_cap( 'edit_posts' );

	// administrator
	$admin = get_role( 'administrator' );
	$admin->add_cap( 'delete_' . $course_cap );
	$admin->add_cap( 'delete_published_' . $course_cap );
	$admin->add_cap( 'edit_' . $course_cap );
	$admin->add_cap( 'edit_published_' . $course_cap );
	$admin->add_cap( 'publish_' . $course_cap );
	$admin->add_cap( 'delete_private_' . $course_cap );
	$admin->add_cap( 'edit_private_' . $course_cap );
	$admin->add_cap( 'delete_others_' . $course_cap );
	$admin->add_cap( 'edit_others_' . $course_cap );

	$admin->add_cap( 'delete_' . $lesson_cap );
	$admin->add_cap( 'delete_published_' . $lesson_cap );
	$admin->add_cap( 'edit_' . $lesson_cap );
	$admin->add_cap( 'edit_published_' . $lesson_cap );
	$admin->add_cap( 'publish_' . $lesson_cap );
	$admin->add_cap( 'delete_private_' . $lesson_cap );
	$admin->add_cap( 'edit_private_' . $lesson_cap );
	$admin->add_cap( 'delete_others_' . $lesson_cap );
	$admin->add_cap( 'edit_others_' . $lesson_cap );

	$admin->add_cap( 'delete_' . $order_cap );
	$admin->add_cap( 'delete_published_' . $order_cap );
	$admin->add_cap( 'edit_' . $order_cap );
	$admin->add_cap( 'edit_published_' . $order_cap );
	$admin->add_cap( 'publish_' . $order_cap );
	$admin->add_cap( 'delete_private_' . $order_cap );
	$admin->add_cap( 'edit_private_' . $order_cap );
	$admin->add_cap( 'delete_others_' . $order_cap );
	$admin->add_cap( 'edit_others_' . $order_cap );
}

add_action( 'init', 'learn_press_add_user_roles' );

function learn_press_get_user_questions( $user_id = null, $args = array() ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	return learn_press_get_user( $user_id )->get_questions( $args );
}

/**
 * Get the type of current user
 *
 * @param null $check_type
 *
 * @return bool|string
 */
function learn_press_current_user_is( $check_type = null ) {
	global $current_user;
	$user_roles = $current_user->roles;
	$user_type  = '';

	// backward compatible
	if ( in_array( 'lpr_teacher', $user_roles ) ) {
		$user_type = 'instructor';
	} elseif ( in_array( 'lp_teacher', $user_roles ) ) {
		$user_type = 'instructor';
	} elseif ( in_array( 'administrator', $user_roles ) ) {
		$user_type = 'administrator';
	}
	return $check_type ? $check_type == $user_type : $user_type;
}

function learn_press_user_has_roles( $roles, $user_id = null ) {
	$has_role = false;
	if ( !$user_id ) {
		$user = wp_get_current_user();
	} else {
		$user = get_user_by( 'id', $user_id );
	}
	$available_roles = (array) $user->roles;
	if ( is_array( $roles ) ) {
		foreach ( $roles as $role ) {
			if ( in_array( $role, $available_roles ) ) {
				$has_role = true;
				break; // only need one of roles is in available
			}
		}
	} else {
		if ( in_array( $roles, $available_roles ) ) {
			$has_role = true;
		}
	}
	return $has_role;
}

function learn_press_edit_admin_bar() {
	global $wp_admin_bar;
	if ( ( $profile = learn_press_get_page_id( 'profile' ) ) && get_post_type( $profile ) == 'page' && ( LP()->settings->get( 'admin_bar_link' ) == 'yes' ) ) {
		$text                             = LP()->settings->get( 'admin_bar_link_text' );
		$course_profile                   = array();
		$course_profile['id']             = 'course_profile';
		$course_profile['parent']         = 'user-actions';
		$course_profile['title']          = $text ? $text : get_the_title( $profile );
		$course_profile['href']           = learn_press_get_page_link( 'profile' );
		$course_profile['meta']['target'] = LP()->settings->get( 'admin_bar_link_target' );
		$wp_admin_bar->add_menu( $course_profile );
	}
	$current_user = wp_get_current_user();
	// add `be teacher` link
	if ( in_array( LP()->teacher_role, $current_user->roles ) || in_array( 'administrator', $current_user->roles ) ) {
		return;
	}
	//if ( !class_exists( 'LP_Admin_Settings' ) ) return;
	/**
	 * $settings = LP_Admin_Settings::instance( 'general' );
	 * if ( $settings->get( 'instructor_registration' ) ) {
	 * $be_teacher           = array();
	 * $be_teacher['id']     = 'be_teacher';
	 * $be_teacher['parent'] = 'user-actions';
	 * $be_teacher['title']  = __( 'Become An Instructor', 'learnpress' );
	 * $be_teacher['href']   = '#';
	 * $wp_admin_bar->add_menu( $be_teacher );
	 * }*/
}

add_action( 'admin_bar_menu', 'learn_press_edit_admin_bar' );


function learn_press_current_user_can_view_profile_section( $section, $user ) {
	$current_user = wp_get_current_user();
	$view         = true;
	if ( $user->user_login != $current_user->user_login && $section == LP()->settings->get( 'profile_endpoints.profile-orders', 'profile-orders' ) ) {
		$view = false;
	}
	return apply_filters( 'learn_press_current_user_can_view_profile_section', $view, $section, $user );
}

function learn_press_profile_tab_courses_content( $current, $tab, $user ) {
	learn_press_get_template( 'profile/tabs/courses.php', array( 'user' => $user, 'current' => $current, 'tab' => $tab ) );
}

function learn_press_profile_tab_quizzes_content( $current, $tab, $user ) {
	learn_press_get_template( 'profile/tabs/quizzes.php', array( 'user' => $user, 'current' => $current, 'tab' => $tab ) );
}

function learn_press_profile_tab_orders_content( $current, $tab, $user ) {
	learn_press_get_template( 'profile/tabs/orders.php', array( 'user' => $user, 'current' => $current, 'tab' => $tab ) );
}

function learn_press_after_logged_in() {

}

add_action( 'wp_login', 'learn_press_after_logged_in' );

function head_head_head() {

}

add_action( 'admin_head', 'head_head_head' );

function learn_press_update_user_lesson_start_time() {
	global $course, $wpdb;

	if ( !$course->id || !( $lesson = $course->current_lesson ) ) {
		return;
	}
	$query = $wpdb->prepare( "
		SELECT user_lesson_id FROM {$wpdb->prefix}learnpress_user_lessons WHERE user_id = %d AND lesson_id = %d AND course_id = %d
	", get_current_user_id(), $lesson->id, $course->id );
	if ( $wpdb->get_row( $query ) ) {
		return;
	}
	$wpdb->insert(
		$wpdb->prefix . 'learnpress_user_lessons',
		array(
			'user_id'    => get_current_user_id(),
			'lesson_id'  => $lesson->id,
			'start_time' => current_time( 'mysql' ),
			'status'     => 'stared',
			'course_id'  => $course->id
		),
		array( '%d', '%d', '%s', '%s', '%d' )
	);
}

add_action( 'learn_press_course_content_lesson', 'learn_press_update_user_lesson_start_time' );

function learn_press_get_profile_user() {
	global $wp;
	return !empty( $wp->query_vars['user'] ) ? get_user_by( 'login', $wp->query_vars['user'] ) : false;
}