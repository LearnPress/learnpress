<?php
/**
 * Build courses content
 */

defined( 'ABSPATH' ) || exit();

/**
 * New functions since 3.x.x
 */

/**
 * Course buttons
 *
 * @see learn_press_course_purchase_button()
 * @see learn_press_course_enroll_button()
 * @see learn_press_course_retake_button()
 * @see learn_press_course_continue_button()
 */
add_action( 'learn-press/course-buttons', 'learn_press_course_purchase_button', 5 );
add_action( 'learn-press/course-buttons', 'learn_press_course_enroll_button', 10 );
add_action( 'learn-press/course-buttons', 'learn_press_course_retake_button', 15 );
add_action( 'learn-press/course-buttons', 'learn_press_course_continue_button', 20 );


/**
 * Course curriculum.
 *
 * @see learn_press_curriculum_section_title()
 * @see learn_press_curriculum_section_content()
 */
add_action( 'learn-press/section-summary', 'learn_press_curriculum_section_title', 5 );
add_action( 'learn-press/section-summary', 'learn_press_curriculum_section_content', 10 );

/**
 * Checkout
 *
 * @see learn_press_checkout_form_login()
 * @see learn_press_checkout_form_register()
 */
add_action( 'learn-press/before-checkout-form', 'learn_press_checkout_form_login', 5 );
add_action( 'learn-press/before-checkout-form', 'learn_press_checkout_form_register', 10 );

/**
 * @see learn_press_order_review()
 */
add_action( 'learn-press/checkout-order-review', 'learn_press_order_review', 5 );

/**
 * @see learn_press_order_comment()
 * @see learn_press_order_payment()
 */
add_action( 'learn-press/after-checkout-order-review', 'learn_press_order_comment', 10 );
add_action( 'learn-press/after-checkout-order-review', 'learn_press_order_payment', 15 );

/**
 * @see learn_press_order_guest_email()
 */
add_action( 'learn-press/payment-form', 'learn_press_order_guest_email', 15 );


/****************************/
/*          Profile         */
/****************************/

/**
 * @see learn_press_user_profile_header()
 */
add_action( 'learn-press/before-user-profile', 'learn_press_user_profile_header', 5 );

/**
 * @see learn_press_user_profile_content()
 * @see learn_press_user_profile_tabs()
 */
add_action( 'learn-press/user-profile', 'learn_press_user_profile_tabs', 5 );
add_action( 'learn-press/user-profile', 'learn_press_user_profile_content', 10 );

/**
 * @see learn_press_user_profile_footer()
 */
add_action( 'learn-press/after-user-profile', 'learn_press_user_profile_footer', 5 );

/**
 * @see learn_press_profile_tab_orders()
 * @see learn_press_profile_recover_order_form()
 */

add_action( 'learn-press/profile/orders', 'learn_press_profile_tab_orders', 10 );
add_action( 'learn-press/profile/orders', 'learn_press_profile_recover_order_form', 15 );

//add_action( 'learn-press/order/after-table-details', 'learn_press_profile_recover_my_order_form', 10 );

/****************************/
/*       Single course      */
/****************************/

/**
 * @see learn_press_single_course_summary()
 */
add_action( 'learn-press/single-course-summary', 'learn_press_single_course_summary', 5 );

/**
 * @see learn_press_course_meta_start_wrapper()
 * @see learn_press_course_price()
 * @see learn_press_course_instructor()
 * @see learn_press_course_students()
 * @see learn_press_course_meta_end_wrapper()
 * @see learn_press_single_course_content_lesson()
 * @see learn_press_single_course_content_item()
 * @see learn_press_course_progress()
 * @see learn_press_course_tabs()
 * @see learn_press_course_curriculum_popup()
 * @see learn_press_course_buttons()
 */
add_action( 'learn-press/content-landing-summary', 'learn_press_course_meta_start_wrapper', 15 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_price', 25 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_instructor', 20 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_students', 30 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_meta_end_wrapper', 35 );
//add_action( 'learn-press/content-landing-summary', 'learn_press_single_course_content_lesson', 40 );
//add_action( 'learn-press/content-landing-summary', 'learn_press_single_course_content_item', 40 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_progress', 40 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_tabs', 50 );
//add_action( 'learn-press/content-landing-summary', 'learn_press_course_curriculum_popup', 65 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_buttons', 70 );

/**
 * Content learning course
 * @see learn_press_course_meta_start_wrapper()
 * @see learn_press_course_status()
 * @see learn_press_course_instructor()
 * @see learn_press_course_students()
 * @see learn_press_course_meta_end_wrapper()
 * @see learn_press_single_course_content_lesson()
 * @see learn_press_single_course_content_item()
 * @see learn_press_course_progress()
 * @see learn_press_course_tabs()
 * @see learn_press_course_curriculum_popup()
 * @see learn_press_course_buttons()
 */
add_action( 'learn-press/content-learning-summary', 'learn_press_course_meta_start_wrapper', 10 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_status', 15 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_instructor', 20 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_students', 25 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_meta_end_wrapper', 30 );
//add_action( 'learn_press_content_learning_summary', 'learn_press_single_course_content_lesson', 35 );
//add_action( 'learn_press_content_learning_summary', 'learn_press_single_course_content_item', 40 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_progress', 45 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_tabs', 50 );
//add_action( 'learn_press_content_learning_summary', 'learn_press_course_remaining_time', 55 );
//add_action( 'learn_press_content_learning_summary', 'learn_press_course_curriculum_popup', 60 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_buttons', 65 );

///add_action( 'learn-press/content-learning-summary', 'learn_press_course_content_item', 50 );


/**
 * Course item content
 */

/**
 * @see learn_press_course_item_content()
 */
add_action( 'learn-press/course-item-content', 'learn_press_course_item_content', 5 );

add_action( 'learn-press/before-content-item-summary/lp_lesson', function () {
	$item = LP_Global::course_item();
	//if($item->is_show_complete
	learn_press_get_template( 'content-lesson/title.php' );
} );

add_action( 'learn-press/content-item-summary/lp_lesson', function () {
	$item = LP_Global::course_item();
	//if($item->is_show_complete
	if ( ( 'standard' !== ( $format = $item->get_format() ) ) && file_exists( $format_template = learn_press_locate_template( "content-lesson/type/{$format}.php" ) ) ) {
		include_once $format_template;

		return;
	}
	learn_press_get_template( 'content-lesson/description.php' );
} );

add_action( 'learn-press/after-content-item-summary/lp_lesson', function () {
	$item = LP_Global::course_item();
	//if($item->is_show_complete
	learn_press_get_template( 'content-lesson/button-complete.php' );
} );


add_action( 'learn-press/after-section-loop-item', 'learn_press_section_item_meta', 10, 2 );

/**
 * @param LP_Quiz $item
 */
function learn_press_quiz_meta_questions( $item ) {
	echo '<span class="item-meta count-questions">' . $item->count_questions() . '</span>';
}

add_action( 'learn-press/course-section-item/before-lp_quiz-meta', 'learn_press_quiz_meta_questions' );

/**
 * @param LP_Quiz $item
 */
function learn_press_quiz_meta_final( $item ) {
	$course = LP_Global::course();
	if ( ! $course->is_final_quiz( $item->get_id() ) ) {
		return;
	}
	echo '<span class="item-meta final-quiz">' . __( 'Final', 'learnpress' ) . '</span>';
}

add_action( 'learn-press/course-section-item/before-lp_quiz-meta', 'learn_press_quiz_meta_final' );
/**
 * @see learn_press_content_item_summary_title()
 * @see learn_press_content_item_summary_content()
 */
add_action( 'learn-press/before-content-item-summary/lp_quiz', 'learn_press_content_item_summary_title', 10 );
add_action( 'learn-press/before-content-item-summary/lp_quiz', 'learn_press_content_item_summary_intro', 15 );
add_action( 'learn-press/before-content-item-summary/lp_quiz', 'learn_press_content_item_summary_content', 20 );

/**
 * @see learn_press_content_item_summary_quiz_progress()
 * @see learn_press_content_item_summary_quiz_countdown()
 * @see learn_press_content_item_summary_quiz_question()
 * @see learn_press_content_item_summary_quiz_result()
 *
 */

add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_progress', 10 );
add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_result', 10 );
add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_countdown', 10 );
add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_question', 10 );

/**
 * @see learn_press_content_item_summary_question_numbers()
 * @see learn_press_content_item_summary_quiz_buttons()
 */
add_action( 'learn-press/after-content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_buttons', 10 );
add_action( 'learn-press/after-content-item-summary/lp_quiz', 'learn_press_content_item_summary_question_numbers', 15 );
add_action( 'learn-press/after-content-item-summary/lp_quiz', 'learn_press_content_item_summary_questions', 25 );

/**
 * @see learn_press_content_item_summary_question_title()
 * @see learn_press_content_item_summary_question_content()
 * @see learn_press_content_item_summary_question()
 */
add_action( 'learn-press/question-content-summary', 'learn_press_content_item_summary_question_title', 15 );
add_action( 'learn-press/question-content-summary', 'learn_press_content_item_summary_question_content', 20 );
add_action( 'learn-press/question-content-summary', 'learn_press_content_item_summary_question', 25 );
add_action( 'learn-press/question-content-summary', 'learn_press_content_item_summary_question_explanation', 25 );
add_action( 'learn-press/question-content-summary', 'learn_press_content_item_summary_question_hint', 25 );


/**
 * @see learn_press_quiz_nav_buttons()
 * @see learn_press_quiz_start_button()
 * @see learn_press_quiz_continue_button()
 * @see learn_press_quiz_complete_button()
 * @see learn_press_quiz_redo_button()
 * @see learn_press_quiz_check_button()
 * @see learn_press_quiz_hint_button()
 * @see learn_press_quiz_result_button()
 */
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_nav_buttons', 10 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_start_button', 15 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_check_button', 20 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_hint_button', 25 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_continue_button', 30 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_complete_button', 35 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_result_button', 40 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_redo_button', 45 );

add_action( 'learn-press/parse-course-item', 'learn_press_control_displaying_course_item' );

/**
 * Single course param.
 *
 * @see learn_press_single_course_args()
 */
add_action( 'learn-press/after-single-course', 'learn_press_single_course_args' );

/**
 * @see learn_press_single_document_title_parts()
 */
add_filter( 'document_title_parts', 'learn_press_single_document_title_parts' );
/*********************************************************************************************************/
/* @see _learn_press_default_course_tabs() */
//add_filter( 'learn_press_course_tabs', '_learn_press_default_course_tabs', 5 );

add_filter( 'body_class', 'learn_press_body_classes', 10 );
add_filter( 'post_class', 'learn_press_course_class', 15, 3 );

/* wrapper */
add_action( 'learn_press_before_main_content', 'learn_press_wrapper_start' );
add_action( 'learn_press_after_main_content', 'learn_press_wrapper_end' );

/* breadcrumb */
add_action( 'learn_press_before_main_content', 'learn_press_breadcrumb' );

add_action( 'learn_press_before_main_content', 'learn_press_search_form' );

/* archive courses */
add_action( 'learn_press_courses_loop_item_title', 'learn_press_courses_loop_item_thumbnail', 10 );
add_action( 'learn_press_courses_loop_item_title', 'learn_press_courses_loop_item_title', 10 );

add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_begin_meta', 10 );
//add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_instructor', 15 );
//add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_students', 20 );
add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_price', 25 );
add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_end_meta', 30 );

add_action( 'learn_press_after_courses_loop', 'learn_press_courses_pagination', 5 );

/* single course content */
add_action( 'wp_head', 'learn_press_single_course_args', 5 );
add_action( 'learn_press_single_course_learning_summary', 'learn_press_output_single_course_learning_summary', 5 );
add_action( 'learn_press_single_course_landing_summary', 'learn_press_output_single_course_landing_summary', 5 );

/* actions to display course content for landing page */

//add_action( 'learn_press_content_landing_summary', 'learn_press_course_thumbnail', 5 );

//add_action( 'learn_press_content_landing_summary', 'learn_press_course_students_list', 75 );

/* actions to display course content for learning page */
//add_action( 'learn_press_course_item_content', 'learn_press_course_item_content', 5 );

//add_action( 'learn_press_content_learning_summary', 'learn_press_course_thumbnail', 5 );

//add_action( 'learn_press_content_learning_summary', 'learn_press_course_students_list', 75 );
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
//add_action( 'learn_press_curriculum_section_summary', 'learn_press_curriculum_section_title', 5 );
//add_action( 'learn_press_curriculum_section_summary', 'learn_press_curriculum_section_content', 10 );

//add_action( 'learn_press_before_course_content_lesson_nav', 'learn_press_before_course_content_lesson_nav', 5 );
//add_action( 'learn_press_after_the_title', 'learn_press_course_thumbnail', 10 );

add_action( 'learn_press_after_section_item_title', 'learn_press_section_item_meta', 5, 3 );

/* order */
add_action( 'learn_press_checkout_before_order_review', 'learn_press_checkout_user_form', 5 );
add_action( 'learn_press_checkout_before_order_review', 'learn_press_checkout_user_logged_in', 10 );
/**
 * add_action( 'learn_press_checkout_user_form', 'learn_press_checkout_user_form_login', 5 );
 * add_action( 'learn_press_checkout_user_form', 'learn_press_checkout_user_form_register', 10 );
 */

/**
 * add_action( 'learn_press_checkout_order_review', 'learn_press_order_review', 5 );
 * add_action( 'learn_press_checkout_order_review', 'learn_press_order_comment', 10 );
 * add_action( 'learn_press_checkout_order_review', 'learn_press_order_payment', 15 );
 */
/* Profile */
add_action( 'learn_press_user_profile_summary', 'learn_press_output_user_profile_info', 5, 3 );
add_action( 'learn_press_user_profile_summary', 'learn_press_output_user_profile_tabs', 10, 3 );
add_action( 'learn_press_user_profile_summary', 'learn_press_output_user_profile_order', 15, 3 );
add_action( 'learn_press_profile_tab_courses_all', 'learn_press_profile_tab_courses_all', 5, 2 );
add_action( 'learn_press_profile_tab_courses_learning', 'learn_press_profile_tab_courses_learning', 5, 2 );
add_action( 'learn_press_profile_tab_courses_purchased', 'learn_press_profile_tab_courses_purchased', 5, 2 );
add_action( 'learn_press_profile_tab_courses_finished', 'learn_press_profile_tab_courses_finished', 5, 2 );
add_action( 'learn_press_profile_tab_courses_own', 'learn_press_profile_tab_courses_own', 5, 2 );
//add_action( 'learn_press_after_profile_tab_all_loop_course', 'learn_press_after_profile_tab_loop_course', 5, 2 );
//add_action( 'learn_press_after_profile_tab_own_loop_course', 'learn_press_after_profile_tab_loop_course', 5, 2 );
add_action( 'learn_press_after_profile_loop_course', 'learn_press_after_profile_tab_loop_course', 5, 2 );

add_action( 'learn_press_after_quiz_question_title', 'learn_press_single_quiz_question_answer', 5, 2 );
add_action( 'learn_press_order_received', 'learn_press_order_details_table', 5 );
add_action( 'learn_press_before_template_part', 'learn_press_generate_template_information', 999, 4 );

add_action( 'learn_press/after_course_item_content', 'learn_press_course_item_edit_link', 10, 2 );
function learn_press_course_item_edit_link( $item_id, $course_id ) {
	$user = learn_press_get_current_user();
	if ( $user->can_edit_item( $item_id, $course_id ) ): ?>
        <p class="edit-course-item-link">
            <a href="<?php echo get_edit_post_link( $item_id ); ?>"><?php _e( 'Edit this item', 'learnpress' ); ?></a>
        </p>
	<?php endif;
}

add_action( 'learn_press/after_course_item_content', 'learn_press_course_nav_items', 10, 2 );
add_action( 'learn_press/after_course_item_content', 'learn_press_lesson_comment_form', 10, 2 );
//add_action('learn_press_after_content_item', 'learn_press_edit_item_link', 10, 3);
//add_action('learn_press_after_content_item', 'learn_press_course_nav_items', 10, 3);
//add_action('learn_press_after_content_item', 'learn_press_lesson_comment_form', 10, 3);

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

/**
 * Redirect profile page if 'view' = 'courses'
 * and 'courses' not exists in URL
 */
/*add_action( 'template_redirect', 'learn_press_redirect_profile', 10 );
if ( !function_exists( 'learn_press_redirect_profile' ) ) {

	function learn_press_redirect_profile( $template ) {
		global $wp_query, $wp;

		if ( !empty( $wp_query->query['page_id'] ) && learn_press_get_page_id( 'profile' ) == $wp_query->query['page_id'] ) {
			parse_str( $wp->matched_query, $query );
			if ( empty( $query['view'] ) && !empty( $wp->query_vars['view'] ) ) {
				$user = learn_press_get_current_user();
				$url  = learn_press_user_profile_link( $user->id, $wp->query_vars['view'] );
				if ( !$url ) {
					$redirect_url = get_permalink() . $wp_query->query['user'];
					$url          = wp_login_url( $redirect_url );
				}
				die('ddddddddddddd');
				wp_redirect( $url );
				exit();
			}
		}
		return $template;
	}

}*/

function learn_press_comments_template_query_args( $comment_args ) {
	$post_type = get_post_type( $comment_args['post_id'] );
	if ( $post_type == 'lp_course' ) {
		$comment_args['type__not_in'] = 'review';
	}

	return $comment_args;
}

add_filter( 'comments_template_query_args', 'learn_press_comments_template_query_args' );

if ( ! function_exists( 'learn_press_filter_get_comments_number' ) ) {
	function learn_press_filter_get_comments_number( $count, $post_id = 0 ) {
		global $wpdb;
		if ( ! $post_id ) {
			$post_id = learn_press_get_course_id();
		}
		if ( ! $post_id ) {
			return $count;
		}
		if ( get_post_type( $post_id ) == 'lp_course' ) {
			$sql   = " SELECT count(*) "
			         . " FROM {$wpdb->comments} "
			         . " WHERE comment_post_ID=%d "
			         . " and comment_approved=1 "
			         . " and comment_type != 'review' ";
			$count = $wpdb->get_var( $wpdb->prepare( $sql, $post_id ) );

			return apply_filters( 'learn_press_get_comments_number', $count, $post_id );
		}

		return $count;
	}
}

add_filter( 'get_comments_number', 'learn_press_filter_get_comments_number' );