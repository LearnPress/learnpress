<?php
/**
 * Build courses content
 */

defined( 'ABSPATH' ) || exit();

/**
 * New functions since 3.0.0
 */

/**
 * Course buttons
 *
 * @see learn_press_course_purchase_button
 * @see learn_press_course_enroll_button
 * @see learn_press_course_retake_button
 * @see learn_press_course_continue_button
 * @see learn_press_course_finish_button
 * @see learn_press_course_external_button
 */
add_action( 'learn-press/course-buttons', 'learn_press_course_external_button', 5 );
add_action( 'learn-press/course-buttons', 'learn_press_course_purchase_button', 10 );
add_action( 'learn-press/course-buttons', 'learn_press_course_enroll_button', 15 );
add_action( 'learn-press/course-buttons', 'learn_press_course_retake_button', 20 );
add_action( 'learn-press/course-buttons', 'learn_press_course_continue_button', 25 );
add_action( 'learn-press/course-buttons', 'learn_press_course_finish_button', 30 );

/**
 * Course curriculum.
 *
 * @see learn_press_curriculum_section_title
 * @see learn_press_curriculum_section_content
 */
add_action( 'learn-press/section-summary', 'learn_press_curriculum_section_title', 5 );
add_action( 'learn-press/section-summary', 'learn_press_curriculum_section_content', 10 );

/**
 * Checkout
 *
 * @see learn_press_checkout_form_login
 * @see learn_press_checkout_form_register
 */
add_action( 'learn-press/before-checkout-form', 'learn_press_checkout_form_login', 5 );
add_action( 'learn-press/before-checkout-form', 'learn_press_checkout_form_register', 10 );

/**
 * @see learn_press_order_review
 */
add_action( 'learn-press/checkout-order-review', 'learn_press_order_review', 5 );

/**
 * @see learn_press_order_comment
 * @see learn_press_order_payment
 */
add_action( 'learn-press/after-checkout-order-review', 'learn_press_order_comment', 5 );
add_action( 'learn-press/after-checkout-order-review', 'learn_press_order_payment', 10 );

/**
 * @see learn_press_order_guest_email
 */
add_action( 'learn-press/payment-form', 'learn_press_order_guest_email', 15 );

/**
 * @see learn_press_user_profile_header
 */
add_action( 'learn-press/before-user-profile', 'learn_press_user_profile_header', 5 );

/**
 * @see learn_press_user_profile_content
 * @see learn_press_user_profile_tabs
 */
add_action( 'learn-press/user-profile', 'learn_press_user_profile_tabs', 5 );
add_action( 'learn-press/user-profile', 'learn_press_user_profile_content', 10 );

/**
 * @see learn_press_user_profile_footer
 */
add_action( 'learn-press/after-user-profile', 'learn_press_user_profile_footer', 5 );

/**
 * @see learn_press_profile_tab_orders
 * @see learn_press_profile_recover_order_form
 */

add_action( 'learn-press/profile/orders', 'learn_press_profile_tab_orders', 5 );
add_action( 'learn-press/profile/orders', 'learn_press_profile_recover_order_form', 10 );

/**
 * @see learn_press_profile_order_details
 * @see learn_press_profile_order_recover
 * @see learn_press_profile_order_message
 */
add_action( 'learn-press/profile/order-details', 'learn_press_profile_order_details', 5 );
add_action( 'learn-press/profile/order-details', 'learn_press_profile_order_recover', 10 );
add_action( 'learn-press/profile/order-details', 'learn_press_profile_order_message', 15 );

/**
 * @see learn_press_profile_dashboard_logged_in
 * @see learn_press_profile_dashboard_user_bio
 */
add_action( 'learn-press/profile/dashboard-summary', 'learn_press_profile_dashboard_logged_in', 5 );
add_action( 'learn-press/profile/dashboard-summary', 'learn_press_profile_dashboard_user_bio', 10 );

/**
 * @see learn_press_profile_dashboard_not_logged_in
 * @see learn_press_profile_login_form
 * @see learn_press_profile_register_form
 */
add_action( 'learn-press/user-profile', 'learn_press_profile_dashboard_not_logged_in', 5 );
add_action( 'learn-press/user-profile', 'learn_press_profile_login_form', 10 );
add_action( 'learn-press/user-profile', 'learn_press_profile_register_form', 15 );

/**
 * @see learn_press_profile_mobile_menu
 */
add_action( 'learn-press/before-profile-nav', 'learn_press_profile_mobile_menu', 5 );

/**
 * @see learn_press_single_course_summary
 */
add_action( 'learn-press/single-course-summary', 'learn_press_single_course_summary', 5 );

/**
 * @see learn_press_course_meta_start_wrapper
 * @see learn_press_course_price
 * @see learn_press_course_instructor
 * @see learn_press_course_students
 * @see learn_press_course_meta_end_wrapper
 * @see learn_press_single_course_content_lesson
 * @see learn_press_single_course_content_item
 * @see learn_press_course_tabs
 * @see learn_press_course_buttons
 */
add_action( 'learn-press/content-landing-summary', 'learn_press_course_meta_start_wrapper', 5 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_students', 10 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_meta_end_wrapper', 15 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_tabs', 20 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_price', 25 );
add_action( 'learn-press/content-landing-summary', 'learn_press_course_buttons', 30 );
//add_action( 'learn-press/content-landing-summary', 'learn_press_course_instructor', 35 );

/**
 * @see learn_press_course_meta_start_wrapper
 * @see learn_press_course_instructor
 * @see learn_press_course_students
 * @see learn_press_course_meta_end_wrapper
 * @see learn_press_single_course_content_lesson
 * @see learn_press_single_course_content_item
 * @see learn_press_course_progress
 * @see learn_press_course_tabs
 * @see learn_press_course_buttons
 * @see learn_press_course_remaining_time
 */
add_action( 'learn-press/content-learning-summary', 'learn_press_course_meta_start_wrapper', 10 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_students', 15 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_meta_end_wrapper', 20 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_progress', 25 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_remaining_time', 30 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_tabs', 35 );
add_action( 'learn-press/content-learning-summary', 'learn_press_course_buttons', 40 );
//add_action( 'learn-press/content-learning-summary', 'learn_press_course_instructor', 45 );

/**
 * Course item content
 */

/**
 * @see learn_press_content_single_item
 * @see learn_press_content_single_course
 */
add_action( 'learn-press/content-single', 'learn_press_content_single_item', 10 );
add_action( 'learn-press/content-single', 'learn_press_content_single_course', 10 );

/**
 * @see learn_press_course_curriculum_tab
 * @see learn_press_single_course_content_item
 */
add_action( 'learn-press/single-item-summary', 'learn_press_course_curriculum_tab', 5 );
add_action( 'learn-press/single-item-summary', 'learn_press_single_course_content_item', 10 );

/**
 * @see learn_press_course_item_content
 * @see learn_press_content_item_comments
 */
add_action( 'learn-press/course-item-content', 'learn_press_course_item_content', 5 );
//add_action( 'learn-press/course-item-content', 'learn_press_content_item_comments', 10 );

/**
 * @see learn_press_content_item_nav
 * @see learn_press_disable_course_comment_form
 */
add_action( 'learn-press/after-course-item-content', 'learn_press_content_item_nav', 5 );
add_action( 'learn-press/after-course-item-content', 'learn_press_lesson_comment_form', 10 );
// add_action( 'learn-press/after-course-item-content', 'learn_press_disable_course_comment_form', 1000 );

/**
 * @see learn_press_content_item_lesson_title
 * @see learn_press_content_item_lesson_content
 * @see learn_press_content_item_lesson_content_blocked
 * @see learn_press_content_item_lesson_complete_button
 */
add_action( 'learn-press/before-content-item-summary/lp_lesson', 'learn_press_content_item_lesson_title', 10 );
add_action( 'learn-press/content-item-summary/lp_lesson', 'learn_press_content_item_lesson_content', 10 );
add_action( 'learn-press/content-item-summary/lp_lesson', 'learn_press_content_item_lesson_content_blocked', 15 );
add_action( 'learn-press/after-content-item-summary/lp_lesson', 'learn_press_content_item_lesson_complete_button', 10 );
add_action( 'learn-press/after-content-item-summary/lp_lesson', 'learn_press_course_finish_button', 15 );

add_action( 'learn-press/content-item-summary-class', 'learn_press_content_item_summary_classes', 15 );

/**
 * @see learn_press_content_item_header
 * @see learn_press_content_item_footer
 * @see learn_press_section_item_meta
 */
add_action( 'learn-press/course-item-content-header', 'learn_press_content_item_header', 10 );
add_action( 'learn-press/course-item-content-footer', 'learn_press_content_item_footer', 10 );
add_action( 'learn-press/after-section-loop-item', 'learn_press_section_item_meta', 10, 2 );

/**
 * @see learn_press_quiz_meta_questions
 * @see learn_press_item_meta_duration
 * @see learn_press_quiz_meta_final
 */
add_action( 'learn-press/course-section-item/before-lp_quiz-meta', 'learn_press_quiz_meta_questions', 5 );
add_action( 'learn-press/course-section-item/before-lp_quiz-meta', 'learn_press_item_meta_duration', 10 );
add_action( 'learn-press/course-section-item/before-lp_quiz-meta', 'learn_press_quiz_meta_final', 15 );

/**
 * @see learn_press_item_meta_duration
 */
add_action( 'learn-press/course-section-item/before-lp_lesson-meta', 'learn_press_item_meta_duration', 5 );

/**
 * @see learn_press_content_item_summary_title
 * @see learn_press_content_item_summary_content
 */
add_action( 'learn-press/before-content-item-summary/lp_quiz', 'learn_press_content_item_quiz_title', 5 );
add_action( 'learn-press/before-content-item-summary/lp_quiz', 'learn_press_content_item_quiz_intro', 10 );

/**
 * @see learn_press_content_item_summary_quiz_content
 * @see learn_press_content_item_summary_quiz_progress
 * @see learn_press_content_item_summary_quiz_result
 * @see learn_press_content_item_summary_quiz_countdown
 * @see learn_press_content_item_summary_quiz_question
 *
 */
add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_progress', 5 );
add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_result', 10 );
add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_content', 15 );
add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_countdown', 20 );
add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_question', 25 );

/**
 * @see learn_press_content_item_summary_quiz_buttons
 * @see learn_press_content_item_summary_question_numbers
 * @see learn_press_content_item_summary_questions
 */
add_action( 'learn-press/after-content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_buttons', 5 );
add_action( 'learn-press/after-content-item-summary/lp_quiz', 'learn_press_content_item_summary_question_numbers', 10 );
add_action( 'learn-press/after-content-item-summary/lp_quiz', 'learn_press_content_item_summary_questions', 15 );

/**
 * @see learn_press_content_item_review_quiz_title
 * @see learn_press_content_item_summary_question_title
 * @see learn_press_content_item_summary_question_content
 * @see learn_press_content_item_summary_question
 * @see learn_press_content_item_summary_question_explanation
 * @see learn_press_content_item_summary_question_hint
 */
add_action( 'learn-press/question-content-summary', 'learn_press_content_item_review_quiz_title', 5 );
add_action( 'learn-press/question-content-summary', 'learn_press_content_item_summary_question_title', 10 );
add_action( 'learn-press/question-content-summary', 'learn_press_content_item_summary_question_content', 15 );
add_action( 'learn-press/question-content-summary', 'learn_press_content_item_summary_question', 20 );
add_action( 'learn-press/question-content-summary', 'learn_press_content_item_summary_question_explanation', 25 );
add_action( 'learn-press/question-content-summary', 'learn_press_content_item_summary_question_hint', 30 );

/**
 * @see learn_press_quiz_nav_buttons
 * @see learn_press_quiz_start_button
 * @see learn_press_quiz_check_button
 * @see learn_press_quiz_hint_button
 * @see learn_press_quiz_continue_button
 * @see learn_press_quiz_complete_button
 * @see learn_press_quiz_result_button
 * @see learn_press_quiz_summary_button
 * @see learn_press_quiz_redo_button
 */
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_nav_buttons', 5 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_start_button', 10 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_check_button', 15 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_hint_button', 20 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_continue_button', 25 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_complete_button', 30 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_result_button', 35 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_summary_button', 40 );
add_action( 'learn-press/quiz-buttons', 'learn_press_quiz_redo_button', 45 );
add_action( 'learn-press/quiz-buttons', 'learn_press_course_finish_button', 50 );

/**
 * @see learn_press_control_displaying_course_item
 */
add_action( 'learn-press/parse-course-item', 'learn_press_control_displaying_course_item', 5 );

/**
 * Single course param.
 *
 * @see learn_press_single_course_args()
 */
add_action( 'learn-press/after-single-course', 'learn_press_single_course_args', 5 );

/**
 * @see learn_press_single_document_title_parts()
 */
add_filter( 'document_title_parts', 'learn_press_single_document_title_parts', 5 );

/***********************************/
/*         BECOME A TEACHER        */
/***********************************/

/**
 * @see learn_press_become_teacher_messages
 * @see learn_press_become_teacher_heading
 */
add_action( 'learn-press/before-become-teacher-form', 'learn_press_become_teacher_messages', 5 );
add_action( 'learn-press/before-become-teacher-form', 'learn_press_become_teacher_heading', 10 );

/**
 * @see learn_press_become_teacher_form_fields
 * @see learn_press_become_teacher_button
 */
add_action( 'learn-press/become-teacher-form', 'learn_press_become_teacher_form_fields', 5 );
add_action( 'learn-press/after-become-teacher-form', 'learn_press_become_teacher_button', 10 );

/**
 * @see learn_press_body_classes
 * @see learn_press_course_class
 */
add_filter( 'body_class', 'learn_press_body_classes', 10 );
add_filter( 'post_class', 'learn_press_course_class', 15, 3 );

/**
 * @see learn_press_wrapper_start
 * @see learn_press_breadcrumb
 * @see learn_press_search_form
 */
add_action( 'learn-press/before-main-content', 'learn_press_wrapper_start', 5 );
add_action( 'learn-press/before-main-content', 'learn_press_breadcrumb', 10 );
add_action( 'learn-press/before-main-content', 'learn_press_search_form', 15 );

/**
 * @see learn_press_wrapper_end
 */
add_action( 'learn-press/after-main-content', 'learn_press_wrapper_end', 5 );

/**
 * @see learn_press_courses_loop_item_thumbnail
 * @see learn_press_courses_loop_item_title
 */
add_action( 'learn-press/courses-loop-item-title', 'learn_press_courses_loop_item_thumbnail', 10 );
add_action( 'learn-press/courses-loop-item-title', 'learn_press_courses_loop_item_title', 15 );

/**
 * @see learn_press_courses_loop_item_begin_meta
 * @see learn_press_courses_loop_item_price
 * @see learn_press_courses_loop_item_instructor
 * @see learn_press_courses_loop_item_end_meta
 * @see learn_press_course_loop_item_buttons
 * @see learn_press_course_loop_item_user_progress
 */
add_action( 'learn-press/after-courses-loop-item', 'learn_press_courses_loop_item_begin_meta', 10 );
add_action( 'learn-press/after-courses-loop-item', 'learn_press_courses_loop_item_price', 20 );
add_action( 'learn-press/after-courses-loop-item', 'learn_press_courses_loop_item_instructor', 25 );
add_action( 'learn-press/after-courses-loop-item', 'learn_press_courses_loop_item_end_meta', 30 );
add_action( 'learn-press/after-courses-loop-item', 'learn_press_course_loop_item_buttons', 35 );
add_action( 'learn-press/after-courses-loop-item', 'learn_press_course_loop_item_user_progress', 40 );

/**
 * @see learn_press_courses_pagination
 */
add_action( 'learn-press/after-courses-loop', 'learn_press_courses_pagination', 5 );

/**
 * @see learn_press_single_course_args
 */
add_action( 'wp_head', 'learn_press_single_course_args', 5 );

/**
 * @see learn_press_checkout_user_form
 * @see learn_press_checkout_user_logged_in
 */
add_action( 'learn-press/before-checkout-order-review', 'learn_press_checkout_user_form', 5 );
add_action( 'learn-press/before-checkout-order-review', 'learn_press_checkout_user_logged_in', 10 );

add_filter( 'comments_template_query_args', 'learn_press_comments_template_query_args' );
add_filter( 'get_comments_number', 'learn_press_filter_get_comments_number' );

/**
 * @see learn_press_back_to_class_button
 */
add_action( 'learn-press/after-checkout-form', 'learn_press_back_to_class_button' );
add_action( 'learn-press/after-empty-cart-message', 'learn_press_back_to_class_button' );

/**
 * add_action( 'learn_press_checkout_user_form', 'learn_press_checkout_user_form_login', 5 );
 * add_action( 'learn_press_checkout_user_form', 'learn_press_checkout_user_form_register', 10 );
 * add_action( 'learn_press_checkout_order_review', 'learn_press_order_review', 5 );
 * add_action( 'learn_press_checkout_order_review', 'learn_press_order_comment', 10 );
 * add_action( 'learn_press_checkout_order_review', 'learn_press_order_payment', 15 );
 * add_action( 'learn_press_after_quiz_question_title', 'learn_press_single_quiz_question_answer', 5, 2 );
 * add_action( 'learn_press_order_received', 'learn_press_order_details_table', 5 );
 * add_action( 'learn_press_before_template_part', 'learn_press_generate_template_information', 999, 4 );
 * add_action( 'learn_press/after_course_item_content', 'learn_press_course_item_edit_link', 10, 2 );
 * add_action( 'learn_press/after_course_item_content', 'learn_press_course_nav_items', 10, 2 );
 * add_action( 'learn_press/after_course_item_content', 'learn_press_lesson_comment_form', 10, 2 );
 */

/**
 * @see learn_press_reset_single_item_summary_content
 */
add_action( 'wp_head', 'learn_press_reset_single_item_summary_content' );