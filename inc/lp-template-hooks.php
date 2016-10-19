<?php
/**
 * Build courses content
 */

defined( 'ABSPATH' ) || exit();

add_filter( 'learn_press_course_tabs', '_learn_press_default_course_tabs', 5 );

add_filter( 'body_class', 'learn_press_body_class' );
add_filter( 'post_class', 'learn_press_course_class', 15, 3 );

/* wrapper */
add_action( 'learn_press_before_main_content', 'learn_press_wrapper_start' );
add_action( 'learn_press_after_main_content', 'learn_press_wrapper_end' );

/* breadcrumb */
add_action( 'learn_press_before_main_content', 'learn_press_breadcrumb' );

/* archive courses */
add_action( 'learn_press_courses_loop_item_title', 'learn_press_courses_loop_item_thumbnail', 10 );
add_action( 'learn_press_courses_loop_item_title', 'learn_press_courses_loop_item_title', 10 );

add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_begin_meta', 10 );
add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_instructor', 15 );
add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_students', 20 );
add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_price', 25 );
add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_end_meta', 30 );

add_action( 'learn_press_after_courses_loop', 'learn_press_courses_pagination', 5 );

/* single course content */
add_action( 'learn_press_before_single_course', 'learn_press_single_course_args', 5 );
add_action( 'learn_press_single_course_learning_summary', 'learn_press_output_single_course_learning_summary', 5 );
add_action( 'learn_press_single_course_landing_summary', 'learn_press_output_single_course_landing_summary', 5 );

/* actions to display course content for landing page */
add_action( 'learn_press_course_item_content', 'learn_press_course_item_content', 5 );

//add_action( 'learn_press_content_landing_summary', 'learn_press_course_thumbnail', 5 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_meta_start_wrapper', 15 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_price', 25 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_students', 30 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_meta_end_wrapper', 35 );
add_action( 'learn_press_content_landing_summary', 'learn_press_single_course_content_lesson', 40 );
add_action( 'learn_press_content_landing_summary', 'learn_press_single_course_content_item', 40 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_progress', 60 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_tabs', 50 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_curriculum_popup', 65 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_buttons', 70 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_students_list', 75 );

/* actions to display course content for learning page */
add_action( 'learn_press_course_item_content', 'learn_press_course_item_content', 5 );

//add_action( 'learn_press_content_learning_summary', 'learn_press_course_thumbnail', 5 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_meta_start_wrapper', 10 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_status', 15 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_instructor', 20 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_students', 25 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_meta_end_wrapper', 30 );
add_action( 'learn_press_content_learning_summary', 'learn_press_single_course_content_lesson', 35 );
add_action( 'learn_press_content_learning_summary', 'learn_press_single_course_content_item', 40 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_progress', 45 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_tabs', 50 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_remaining_time', 55 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_curriculum_popup', 60 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_buttons', 65 );

add_action( 'learn_press_after_content_learning', 'learn_press_course_students_list', 5 );
//add_action( 'learn_press_course_content_lesson', 'learn_press_course_content_lesson', 5 );

/*
add_action( 'learn_press_course_lesson_summary', 'learn_press_course_lesson_data', 5 );
add_action( 'learn_press_course_lesson_summary', 'learn_press_course_lesson_description', 10 );
add_action( 'learn_press_course_lesson_summary', 'learn_press_course_quiz_description', 15 );
add_action( 'learn_press_course_lesson_summary', 'learn_press_course_lesson_complete_button', 20 );
add_action( 'learn_press_course_lesson_summary', 'learn_press_course_lesson_navigation', 25 );
*/

add_action( 'learn_press_after_enroll_button', 'learn_press_enroll_script' );

/**
 * curriculum
 */
add_action( 'learn_press_curriculum_section_summary', 'learn_press_curriculum_section_title', 5 );
add_action( 'learn_press_curriculum_section_summary', 'learn_press_curriculum_section_content', 10 );

//add_action( 'learn_press_before_course_content_lesson_nav', 'learn_press_before_course_content_lesson_nav', 5 );
add_action( 'learn_press_after_the_title', 'learn_press_course_thumbnail', 10 );

add_action( 'learn_press_after_section_item_title', 'learn_press_section_item_meta', 5, 3 );

/* order */
add_action( 'learn_press_checkout_before_order_review', 'learn_press_checkout_user_form', 5 );
add_action( 'learn_press_checkout_before_order_review', 'learn_press_checkout_user_logged_in', 10 );

add_action( 'learn_press_checkout_user_form', 'learn_press_checkout_user_form_login', 5 );
add_action( 'learn_press_checkout_user_form', 'learn_press_checkout_user_form_register', 10 );

add_action( 'learn_press_checkout_order_review', 'learn_press_order_review', 5 );
add_action( 'learn_press_checkout_order_review', 'learn_press_order_comment', 10 );
add_action( 'learn_press_checkout_order_review', 'learn_press_order_payment', 15 );

/* Profile */
add_action( 'learn_press_user_profile_summary', 'learn_press_output_user_profile_info', 5, 3 );
add_action( 'learn_press_user_profile_summary', 'learn_press_output_user_profile_tabs', 10, 3 );
add_action( 'learn_press_user_profile_summary', 'learn_press_output_user_profile_order', 15, 3 );
add_action( 'learn_press_profile_tab_courses_all', 'learn_press_profile_tab_courses_all', 5, 2 );
add_action( 'learn_press_profile_tab_courses_learning', 'learn_press_profile_tab_courses_learning', 5, 2 );
add_action( 'learn_press_profile_tab_courses_purchased', 'learn_press_profile_tab_courses_purchased', 5, 2 );
add_action( 'learn_press_profile_tab_courses_finished', 'learn_press_profile_tab_courses_finished', 5, 2 );
add_action( 'learn_press_profile_tab_courses_own', 'learn_press_profile_tab_courses_own', 5, 2 );
add_action( 'learn_press_after_profile_tab_all_loop_course', 'learn_press_after_profile_tab_loop_course', 5 );
add_action( 'learn_press_after_profile_tab_own_loop_course', 'learn_press_after_profile_tab_loop_course', 5 );

add_action( 'learn_press_after_quiz_question_title', 'learn_press_single_quiz_question_answer', 5, 2 );
add_action( 'learn_press_order_received', 'learn_press_order_details_table', 5 );
add_action( 'learn_press_before_template_part', 'learn_press_generate_template_information', 999, 4 );

add_filter( 'learn_press_profile_tab_endpoints', function ( $endpoints ) {
	$endpoints['edit'] = 'edit';
	return $endpoints;
} );

add_filter('the_content', function($content) {
	global $wp, $wpdb;
	if( !empty($_POST) && isset($_POST['from']) && isset($_POST['action']) && $_POST['from']=='profile' && $_POST['action']=='update') {
		$user			= learn_press_get_current_user();
		$user_id		= learn_press_get_current_user_id();
		$user_info = get_userdata($user->id);

		// check old pass
		$old_pass	= filter_input(INPUT_POST, 'pass0');
		$check_old_pass = false;
		if( !$old_pass ) {
			$check_old_pass = false;
		} else {
			$cuser = wp_get_current_user();
			require_once( ABSPATH . 'wp-includes/class-phpass.php');
			$wp_hasher = new PasswordHash(8, TRUE);
			if( $wp_hasher->CheckPassword( $old_pass, $cuser->data->user_pass ) ) {
				$check_old_pass = true;
			}
		}
		
		if( !$check_old_pass ) {
			_e('old password incorect!','learnpress');
		}

		// check new pass
		$new_pass = filter_input(INPUT_POST, 'pass1');
		$new_pass2 = filter_input(INPUT_POST, 'pass2');
		$hash_pass = '';
		if( $new_pass != $new_pass2 ) {
			_e('retype new password incorect!','learnpress');
		} else {
			$hash_pass = wp_hash_password( $new_pass );
		}

		$update_data	= array(
			'ID' => $user_id,
			'user_url'			=> filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL),
			'user_email'		=> filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
			'first_name'	=> filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING),
			'last_name'	=> filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING),
			'description'	=> filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
		);
		
		if($hash_pass){
			$update_data['user_pass'] = $hash_pass;
		}

		$user_id = wp_update_user( $update_data );
		if( $user_id ){
			_e('Your change is saved','learnpress');
		}

	}
	$query_vars = $wp->query_vars;
	$user = learn_press_get_current_user();
	if( !$user ) {
		$content = learn_press_get_template('profile/private-area.php');
	} elseif (!empty($query_vars['user']) && !empty($query_vars['view']) && $query_vars['view'] == 'edit' && $user && isset($user->user->data->user_login) && $query_vars['user'] == $user->user->data->user_login ) {
		$content = learn_press_get_template('profile/edit.php');
	}
	return $content;
});

/*
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_preview_mode', 5 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_left_start_wrap', 10 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_question', 15 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_result', 20 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_questions_nav', 25 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_questions', 30 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_history', 35 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_left_end_wrap', 40 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_sidebar', 45 );*/
/*
add_action( 'learn_press_single_quiz_sidebar', 'learn_press_single_quiz_information', 5 );
add_action( 'learn_press_single_quiz_sidebar', 'learn_press_single_quiz_timer', 10 );
add_action( 'learn_press_single_quiz_sidebar', 'learn_press_single_quiz_buttons', 15 );*/
