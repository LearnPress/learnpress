<?php
/**
 * Build courses content
 */

defined( 'ABSPATH' ) || exit();

add_filter( 'body_class', 'learn_press_body_class' );
add_filter( 'post_class', 'learn_press_course_class' );

/* wrapper */
add_action( 'learn_press_before_main_content', 'learn_press_wrapper_start' );
add_action( 'learn_press_after_main_content', 'learn_press_wrapper_end' );

/* breadcrumb */
add_action( 'learn_press_before_main_content', 'learn_press_breadcrumb' );

/* archive courses */

add_action( 'learn_press_courses_loop_item_title', 'learn_press_courses_loop_item_title', 10 );
add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_thumbnail', 10 );
add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_price', 15 );
add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_students', 20 );
add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_instructor', 25 );
add_action( 'learn_press_after_courses_loop_item', 'learn_press_courses_loop_item_introduce', 30 );
add_action( 'learn_press_after_courses_loop', 'learn_press_courses_pagination', 5 );


/* single course content */
add_action( 'learn_press_single_course_learning_summary', 'learn_press_output_single_course_learning_summary', 5 );
add_action( 'learn_press_single_course_landing_summary', 'learn_press_output_single_course_landing_summary', 5 );

/* actions to display course content for landing page */
add_action( 'learn_press_content_landing_summary', 'learn_press_course_thumbnail', 5 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_title', 10 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_meta_start_wrapper', 15 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_price', 25 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_students', 30 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_meta_end_wrapper', 35 );
add_action( 'learn_press_content_landing_summary', 'learn_press_single_course_content_lesson', 40 );
//add_action( 'learn_press_content_landing_summary', 'learn_press_course_payment_form', 20 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_enroll_button', 45 );
//add_action( 'learn_press_content_landing_summary', 'learn_press_course_status_message', 50 );
add_action( 'learn_press_content_landing_summary', 'learn_press_single_course_description', 55 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_progress', 60 );
add_action( 'learn_press_content_landing_summary', 'learn_press_course_curriculum', 65 );

/* actions to display course content for learning page */
add_action( 'learn_press_content_learning_summary', 'learn_press_course_thumbnail', 5 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_meta_start_wrapper', 10 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_status', 15 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_instructor', 20 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_students', 25 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_meta_end_wrapper', 30 );

add_action( 'learn_press_content_learning_summary', 'learn_press_single_course_description', 35 );
add_action( 'learn_press_content_learning_summary', 'learn_press_single_course_content_lesson', 40 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_progress', 45 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_finish_button', 50 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_curriculum', 55 );

add_action( 'learn_press_course_content_lesson', 'learn_press_course_content_lesson', 5 );
add_action( 'learn_press_course_lesson_summary', 'learn_press_course_lesson_data', 5 );
add_action( 'learn_press_course_lesson_summary', 'learn_press_course_lesson_description', 5 );
add_action( 'learn_press_course_lesson_summary', 'learn_press_course_quiz_description', 10 );
add_action( 'learn_press_course_lesson_summary', 'learn_press_course_lesson_complete_button', 15 );
add_action( 'learn_press_course_lesson_summary', 'learn_press_course_lesson_navigation', 20 );

/**************************************/
add_action( 'learn_press_after_enroll_button', 'learn_press_enroll_script' );

/**
 * curriculum
 */
add_action( 'learn_press_curriculum_section_summary', 'learn_press_curriculum_section_title', 5 );
add_action( 'learn_press_curriculum_section_summary', 'learn_press_curriculum_section_content', 10 );

/*
add_action( 'learn_press_content_learning_summary', 'learn_press_course_finished_message', 30 );
//add_action( 'learn_press_course_learning_content', 'learn_press_course_percentage', 30 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_remaining_time', 30 );
add_action( 'learn_press_content_learning_summary', 'learn_press_passed_conditional', 30 );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_retake_button', 40 );
add_action( 'learn_press_content_learning_summary', 'learn_press_finish_course_button', 40 );

add_action( 'learn_press_content_learning_summary', 'learn_press_course_content_summary' );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_content_course_title' );

add_action( 'learn_press_content_learning_summary', 'learn_press_course_content_course_description' );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_content_lesson_title' );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_content_lesson_description' );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_content_lesson_action' );
add_action( 'learn_press_content_learning_summary', 'learn_press_course_content_next_prev_lesson' );*/

add_action( 'learn_press_before_course_content_lesson_nav', 'learn_press_before_course_content_lesson_nav' );
add_action( 'learn_press_after_the_title', 'learn_press_course_thumbnail', 10 );

//add_action( 'wp_enqueue_scripts', 'learn_press_frontend_single_quiz_scripts' );
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



// Single Quiz
//add_action( 'learn_press_before_content_in_single_quiz', 'learn_press_single_quiz_content_start_wrap' );
//add_action( 'learn_press_before_content_in_single_quiz', 'learn_press_single_quiz_content_before_content' );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_preview_mode', 0 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_title', 5 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_description', 10 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_left_start_wrap', 15 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_question', 20 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_result', 20 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_questions_nav', 25 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_questions', 30 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_history', 35 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_left_end_wrap', 40 );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_sidebar', 45 );

add_action( 'learn_press_single_quiz_sidebar', 'learn_press_single_quiz_information', 5 );
add_action( 'learn_press_single_quiz_sidebar', 'learn_press_single_quiz_timer', 10 );
add_action( 'learn_press_single_quiz_sidebar', 'learn_press_single_quiz_buttons', 15 );
/*
add_action( 'learn_press_single_quiz_sidebar', 'learn_press_single_quiz_buttons', 10 );
*/
add_action( 'learn_press_after_quiz_question_title', 'learn_press_single_quiz_question_answer', 5, 2 );
//add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_no_question' );

/*
add_action( 'learn_press_after_content_in_single_quiz', 'learn_press_single_quiz_content_page' );
add_action( 'learn_press_after_content_in_single_quiz', 'learn_press_single_quiz_content_after_content' );
add_action( 'learn_press_after_content_in_single_quiz', 'learn_press_single_quiz_sidebar' );
add_action( 'learn_press_after_content_in_single_quiz', 'learn_press_single_quiz_content_end_wrap' );

add_action( 'learn_press_content_quiz_sidebar', 'learn_press_single_quiz_time_counter' );
add_action( 'learn_press_content_quiz_sidebar', 'learn_press_single_quiz_buttons' );

add_action( 'learn_press_after_single_quiz', 'learn_press_print_quiz_question_content_script' );
*/


add_action( 'learn_press_order_received', 'learn_press_order_details_table', 5 );

add_action( 'learn_press_before_template_part', 'learn_press_generate_template_information', 999, 4 );

return;

/*****************************************************/
// Archive courses
add_action( 'learn_press_entry_footer_archive', 'learn_press_course_price', 10 );
add_action( 'learn_press_entry_footer_archive', 'learn_press_course_students', 20 );
add_action( 'learn_press_entry_footer_archive', 'learn_press_course_instructor', 30 );
add_action( 'learn_press_entry_footer_archive', 'learn_press_course_categories', 40 );
add_action( 'learn_press_entry_footer_archive', 'learn_press_course_tags', 50 );


// Single landing course
/*add_action( 'learn_press_course_landing_content', 'learn_press_course_price', 30 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_students', 40 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_payment_form', 40 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_enroll_button', 50 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_status_message', 50 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_content', 60 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_curriculum', 70 );
*/
// Single learning course
/*add_action( 'learn_press_before_main_content', 'learn_press_wrapper_start' );
add_action( 'learn_press_after_main_content', 'learn_press_wrapper_end' );


add_action( 'learn_press_course_learning_content', 'learn_press_course_instructor', 10 );
add_action( 'learn_press_course_learning_content', 'learn_press_course_content', 20 );
add_action( 'learn_press_course_learning_content', 'learn_press_course_students' );
add_action( 'learn_press_course_learning_content', 'learn_press_course_curriculum', 20 );

add_action( 'learn_press_course_learning_content', 'learn_press_course_finished_message', 30 );
//add_action( 'learn_press_course_learning_content', 'learn_press_course_percentage', 30 );
add_action( 'learn_press_course_learning_content', 'learn_press_course_remaining_time', 30 );
add_action( 'learn_press_course_learning_content', 'learn_press_passed_conditional', 30 );
add_action( 'learn_press_course_learning_content', 'learn_press_course_retake_button', 40 );
add_action( 'learn_press_course_learning_content', 'learn_press_finish_course_button', 40 );

add_action( 'learn_press_course_content_summary', 'learn_press_course_content_summary' );
add_action( 'learn_press_course_content_course', 'learn_press_course_content_course_title' );
add_action( 'learn_press_course_content_course', 'learn_press_course_content_course_description' );
add_action( 'learn_press_course_content_lesson', 'learn_press_course_content_lesson_title' );
add_action( 'learn_press_course_content_lesson', 'learn_press_course_content_lesson_description' );
add_action( 'learn_press_course_content_lesson', 'learn_press_course_content_lesson_action' );
add_action( 'learn_press_course_content_lesson', 'learn_press_course_content_next_prev_lesson' );
add_action( 'learn_press_before_course_content_lesson_nav', 'learn_press_before_course_content_lesson_nav' );
add_action( 'learn_press_after_the_title', 'learn_press_course_thumbnail', 10 );

add_action( 'wp_enqueue_scripts', 'learn_press_frontend_single_quiz_scripts' );*/


// load question content or result of quiz depending on status of it

return;
add_action( 'learn_press_after_single_quiz_summary', 'learn_press_single_quiz_load_question' );
add_action( 'learn_press_after_single_quiz_summary', 'learn_press_single_quiz_result' );
add_action( 'learn_press_after_single_quiz_summary', 'learn_press_single_quiz_percentage' );
add_action( 'learn_press_after_single_quiz_summary', 'learn_press_single_quiz_sidebar' );

add_action( 'learn_press_quiz_question_nav', 'learn_press_quiz_question_nav' );
add_action( 'learn_press_quiz_question_nav', 'learn_press_check_question_answer' );
add_action( 'learn_press_quiz_question_nav', 'learn_press_quiz_question_nav_buttons' );

add_action( 'learn_press_before_main_quiz_content', 'learn_press_before_main_quiz_content' );
add_action( 'learn_press_after_main_quiz_content', 'learn_press_after_main_quiz_content', 1000 );

add_action( 'learn_press_after_single_quiz_summary', 'learn_press_single_quiz_questions' );
add_action( 'learn_press_after_single_quiz_summary', 'learn_press_display_course_link' );

add_action( 'learn_press_quiz_questions_after_question_title_element', 'learn_press_quiz_hint' );

add_filter( 'learn_press_get_template_part', 'learn_press_permission_to_view_page', 1000, 3 );
// Confirm order
add_action( 'learn_press_confirm_order', 'learn_press_order_details_table' );

//add_filter( 'learn_press_get_current_question', 'learn_press_get_current_question_filter', 10, 3 );


