<?php
/**
* Build courses content
*/

// Archive courses
add_action( 'learn_press_entry_footer_archive', 'learn_press_course_price', 10 );
add_action( 'learn_press_entry_footer_archive', 'learn_press_course_students', 20 );
add_action( 'learn_press_entry_footer_archive', 'learn_press_course_instructor', 30 );
add_action( 'learn_press_entry_footer_archive', 'learn_press_course_categories', 40 );
add_action( 'learn_press_entry_footer_archive', 'learn_press_course_tags', 50 );


// Single landing course
// add_action( 'learn_press_course_landing_content', 'the_excerpt', 10 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_price', 30 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_students', 40 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_payment_form', 40 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_enroll_button', 50 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_status_message', 50 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_content', 60 );
add_action( 'learn_press_course_landing_content', 'learn_press_course_curriculum', 70 );

// Single learning course
add_action( 'learn_press_before_main_content', 'learn_press_wrapper_start' );
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

add_action( 'wp_enqueue_scripts', 'learn_press_frontend_single_quiz_scripts' );

// Single Quiz
add_action( 'learn_press_before_content_in_single_quiz', 'learn_press_single_quiz_content_start_wrap' );
add_action( 'learn_press_before_content_in_single_quiz', 'learn_press_single_quiz_content_before_content' );

add_action( 'learn_press_after_content_in_single_quiz', 'learn_press_single_quiz_content_page' );
add_action( 'learn_press_after_content_in_single_quiz', 'learn_press_single_quiz_content_after_content' );
add_action( 'learn_press_after_content_in_single_quiz', 'learn_press_single_quiz_sidebar' );
add_action( 'learn_press_after_content_in_single_quiz', 'learn_press_single_quiz_content_end_wrap' );

add_action( 'learn_press_content_quiz_sidebar', 'learn_press_single_quiz_time_counter' );
add_action( 'learn_press_content_quiz_sidebar', 'learn_press_single_quiz_buttons' );


// single quiz page js
add_action( 'learn_press_after_single_quiz', 'learn_press_print_quiz_question_content_script' );

// title and content of the quiz
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_title' );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_description' );
add_action( 'learn_press_single_quiz_summary', 'learn_press_single_quiz_no_question' );

// load question content or result of quiz depending on status of it
add_action( 'learn_press_after_single_quiz_summary', 'learn_press_single_quiz_load_question' );
add_action( 'learn_press_after_single_quiz_summary', 'learn_press_single_quiz_result' );
add_action( 'learn_press_after_single_quiz_summary', 'learn_press_single_quiz_percentage' );
add_action( 'learn_press_after_single_quiz_summary', 'learn_press_single_quiz_sidebar' );

add_action( 'learn_press_quiz_question_nav', 'learn_press_quiz_question_nav');
add_action( 'learn_press_quiz_question_nav', 'learn_press_check_question_answer');
add_action( 'learn_press_quiz_question_nav', 'learn_press_quiz_question_nav_buttons');

add_action( 'learn_press_before_main_quiz_content', 'learn_press_before_main_quiz_content');
add_action( 'learn_press_after_main_quiz_content', 'learn_press_after_main_quiz_content', 1000);

add_action( 'learn_press_after_single_quiz_summary', 'learn_press_single_quiz_questions' );
add_action( 'learn_press_after_single_quiz_summary', 'learn_press_display_course_link' );

add_action( 'learn_press_quiz_questions_after_question_title_element', 'learn_press_quiz_hint' );

add_filter( 'learn_press_get_template_part', 'learn_press_permission_to_view_page', 1000, 3 );
add_filter( 'template_include', 'learn_press_permission_to_view_page' );
// Confirm order
add_action( 'learn_press_confirm_order', 'learn_press_order_details_table' );

//add_filter( 'learn_press_get_current_question', 'learn_press_get_current_question_filter', 10, 3 );

add_action( 'template_redirect', 'learn_press_redirect_to_question' );

