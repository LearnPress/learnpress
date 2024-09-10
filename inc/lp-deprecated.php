<?php
if ( ! function_exists( 'learn_press_course_content_lesson' ) ) {
	/**
	 * Display course description
	 */
	function learn_press_course_content_lesson() {
		learn_press_get_template( 'content-lesson/summary.php' );
	}
}

if ( ! function_exists( 'learn_press_course_lesson_description' ) ) {
	/**
	 * Display course lesson description
	 */
	function learn_press_course_lesson_description() {
		learn_press_get_template( 'content-lesson/description.php' );
	}
}

if ( ! function_exists( 'learn_press_course_quiz_description' ) ) {
	/**
	 * Display lesson content
	 */
	function learn_press_course_quiz_description() {
		learn_press_get_template( 'single-course/content-quiz.php' );
	}
}


if ( ! function_exists( 'learn_press_course_lesson_complete_button' ) ) {
	/**
	 * Display lesson complete button
	 */
	function learn_press_course_lesson_complete_button() {
		learn_press_get_template( 'content-lesson/complete-button.php' );
	}
}


if ( ! function_exists( 'learn_press_course_lesson_navigation' ) ) {
	/**
	 * Display lesson navigation
	 */
	function learn_press_course_lesson_navigation() {
		learn_press_get_template( 'content-lesson/navigation.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_preview_mode' ) ) {
	/**
	 * Output the title of the quiz
	 */
	function learn_press_single_quiz_preview_mode() {
		learn_press_get_template( 'content-quiz/preview-mode.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_left_start_wrap' ) ) {
	function learn_press_single_quiz_left_start_wrap() {
		learn_press_get_template( 'content-quiz/left-start-wrap.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_question' ) ) {
	/**
	 * Output the single question for quiz
	 */
	function learn_press_single_quiz_question() {
		learn_press_get_template( 'content-quiz/content-question.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_result' ) ) {
	/**
	 * Output the result for the quiz
	 */
	function learn_press_single_quiz_result() {
		learn_press_get_template( 'content-quiz/result.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_questions_nav' ) ) {
	/**
	 * Output the navigation to next and previous questions
	 */
	function learn_press_single_quiz_questions_nav() {
		learn_press_get_template( 'content-quiz/nav.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_questions' ) ) {
	/**
	 * Output the list of questions for quiz
	 */
	function learn_press_single_quiz_questions() {
		learn_press_get_template( 'content-quiz/questions.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_history' ) ) {
	/**
	 * Output the history of a quiz
	 */
	function learn_press_single_quiz_history() {
		learn_press_get_template( 'content-quiz/history.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_left_end_wrap' ) ) {
	function learn_press_single_quiz_left_end_wrap() {
		learn_press_get_template( 'content-quiz/left-end-wrap.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_sidebar' ) ) {
	/**
	 * Output the sidebar for a quiz
	 */
	function learn_press_single_quiz_sidebar() {
		learn_press_get_template( 'content-quiz/sidebar.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_information' ) ) {
	/**
	 *
	 */
	function learn_press_single_quiz_information() {
		learn_press_get_template( 'content-quiz/intro.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_timer' ) ) {
	/**
	 * Output the quiz countdown timer
	 */
	function learn_press_single_quiz_timer() {
		learn_press_get_template( 'content-quiz/timer.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_buttons' ) ) {
	/**
	 * Output the buttons for quiz actions
	 */
	function learn_press_single_quiz_buttons() {
		learn_press_get_template( 'content-quiz/buttons.php' );
	}
}

if ( ! function_exists( 'learn_press_single_quiz_description' ) ) {
	/**
	 * Output the content of the quiz
	 */
	function learn_press_single_quiz_description() {
		learn_press_get_template( 'content-quiz/description.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_information' ) ) {
	/**
	 *
	 */
	function learn_press_single_quiz_information() {
		learn_press_get_template( 'content-quiz/intro.php' );
	}
}


if ( ! function_exists( 'learn_press_single_quiz_sidebar_buttons' ) ) {
	/**
	 *
	 */
	function learn_press_single_quiz_sidebar_buttons() {
		learn_press_get_template( 'content-quiz/sidebar-buttons.php' );
	}
}

//if ( ! function_exists( '_learn_press_default_course_tabs' ) ) {
//
//	/**
//	 * Add default tabs to course
//	 *
//	 * @param array $tabs
//	 *
//	 * @return array
//	 */
//	function _learn_press_default_course_tabs( $tabs = array() ) {
//		_deprecated_function( __FUNCTION__, '3.0.0', 'learn_press_get_course_tabs' );
//
//		return learn_press_get_course_tabs();
//	}
//}

// Show filters for students list
// Wait addon student list v4.0.3 update will remove it
function learn_press_get_students_list_filter() {
	$filter = array(
		'all'         => esc_html__( 'All', 'learnpress' ),
		'in-progress' => esc_html__( 'In Progress', 'learnpress' ),
		'finished'    => esc_html__( 'Finished', 'learnpress' ),
	);

	return apply_filters( 'learn_press_get_students_list_filter', $filter );
}


function learn_press_output_question_nonce( $question ) {
	printf( '<input type="hidden" name="update-question-nonce" value="%s" />', wp_create_nonce( 'current-question-nonce-' . $question->id ) );
}

add_action( 'learn_press_after_question_wrap', 'learn_press_output_question_nonce' );


//if ( ! function_exists( 'learn_press_course_nav_items' ) ) {
//	/**
//	 * Displaying course items navigation
//	 *
//	 * @param null $item_id
//	 * @param null $course_id
//	 */
//	function learn_press_course_nav_items( $item_id = null, $course_id = null ) {
//		learn_press_get_template(
//			'single-course/nav-items.php',
//			array(
//				'course_id'    => $course_id,
//				'item_id'      => $item_id,
//				'content_only' => learn_press_is_content_item_only(),
//			)
//		);
//	}
//}

/**
 * Version 3.3.0
 */

/**
 * New functions since 3.0.0
 */
if ( ! function_exists( 'learn_press_course_purchase_button' ) ) {
	/**
	 * Purchase course button.
	 */
	function learn_press_course_purchase_button() {
		_deprecated_function( __FUNCTION__, '3.3.0' );
		return '';
		LearnPress::instance()->template( 'course' )->course_purchase_button();
	}
}

if ( ! function_exists( 'learn_press_course_enroll_button' ) ) {
	/**
	 * Enroll course button.
	 */
	function learn_press_course_enroll_button() {
		_deprecated_function( __FUNCTION__, '3.3.0' );
		return '';
		LearnPress::instance()->template( 'course' )->course_enroll_button();
	}
}

if ( ! function_exists( 'learn_press_course_external_button' ) ) {

	/**
	 * Retake course button
	 * @deprecated 4.2.5.3
	 */
	function learn_press_course_external_button() {
		_deprecated_function( __FUNCTION__, '4.2.5.3' );
		return;
		LearnPress::instance()->template( 'course' )->func( 'course_external_button' );
	}
}

if ( ! function_exists( 'learn_press_course_students' ) ) {
	/**
	 * Display course students
	 */
	function learn_press_course_students() {
		learn_press_get_template( 'single-course/students.php' );
	}
}

//if ( ! function_exists( 'learn_press_course_status' ) ) {
//	/**
//	 * Display the title for single course
//	 */
//	function learn_press_course_status() {
//		learn_press_get_template( 'single-course/status.php' );
//	}
//}

if ( ! function_exists( 'learn_press_courses_loop_item_instructor' ) ) {
	/**
	 * Output the instructor of the course within loop
	 * @using in many themes.
	 */
	function learn_press_courses_loop_item_instructor() {
		learn_press_get_template( 'loop/course/instructor.php' );
	}
}

if ( ! function_exists( 'learn_press_course_tabs' ) ) {
	/*
	 * Output course tabs
	 * @using in theme starkid
	 */
	function learn_press_course_tabs() {
		learn_press_get_template( 'single-course/tabs/tabs.php' );
	}
}

/*if ( ! function_exists( 'learn_press_content_item_quiz_title' ) ) {
	function learn_press_content_item_quiz_title() {
		learn_press_get_template( 'content-quiz/title.php' );
	}
}*/

/*if ( ! function_exists( 'learn_press_content_item_quiz_intro' ) ) {
	function learn_press_content_item_quiz_intro() {
		$course = learn_press_get_course();
		$user   = learn_press_get_current_user();
		$quiz   = LP_Global::course_item_quiz();

		if ( $user->has_quiz_status( array( 'started', 'completed' ), $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( ! $user->has_quiz_status( 'started', $quiz->get_id(), $course->get_id() ) ) {
			// return;
		}

		if ( $quiz->get_viewing_question() ) {
			return;
		}

		learn_press_get_template( 'content-quiz/intro.php' );
	}
}*/

/*if ( ! function_exists( 'learn_press_content_item_summary_quiz_content' ) ) {

	function learn_press_content_item_summary_quiz_content() {
		$item = LP_Global::course_item();

		if ( ! $item->get_viewing_question() ) {
			learn_press_get_template( 'content-quiz/description.php' );
		}
	}
}*/

/*if ( ! function_exists( 'learn_press_content_item_summary_question_title' ) ) {

	function learn_press_content_item_summary_question_title() {
		$quiz = LP_Global::course_item_quiz();

		if ( $question = $quiz->get_viewing_question() ) {
			learn_press_get_template( 'content-question/title.php' );
		}
	}
}*/

/*if ( ! function_exists( 'learn_press_content_item_summary_quiz_progress' ) ) {

	function learn_press_content_item_summary_quiz_progress() {
		$course = learn_press_get_course();
		$quiz   = LP_Global::course_item_quiz();
		$user   = learn_press_get_current_user();

		if ( ! $user ) {
			return;
		}

		if ( $user->has_quiz_status( array( 'viewed', '' ), $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( $question = $quiz->get_viewing_question() ) {
			learn_press_get_template( 'content-quiz/progress.php' );
		}
	}
}*/

/*if ( ! function_exists( 'learn_press_content_item_summary_quiz_countdown' ) ) {

	function learn_press_content_item_summary_quiz_countdown() {
		$quiz = LP_Global::course_item_quiz();

		if ( $question = $quiz->get_viewing_question() ) {
			learn_press_get_template( 'content-quiz/countdown.php' );
		}
	}
}*/

/*if ( ! function_exists( 'learn_press_content_item_summary_question_content' ) ) {

	function learn_press_content_item_summary_question_content() {
		$quiz = LP_Global::course_item_quiz();

		if ( $question = $quiz->get_viewing_question() ) {
			learn_press_get_template( 'content-question/description.php' );
		}
	}
}*/

/*if ( ! function_exists( 'learn_press_content_item_summary_quiz_buttons' ) ) {

	function learn_press_content_item_summary_quiz_buttons() {
		_deprecated_function( __FUNCTION__, '3.3.0' );
		learn_press_get_template( 'content-quiz/buttons.php' );
	}
}*/

/*if ( ! function_exists( 'learn_press_profile_recover_order_form' ) ) {
	function learn_press_profile_recover_order_form( $order ) {
		learn_press_get_template( 'profile/tabs/orders/recover-order.php', array( 'order' => $order ) );
	}
}*/


//if ( ! function_exists( 'learn_press_wrapper_start' ) ) {
//	/**
//	 * Wrapper Start
//	 */
//	function learn_press_wrapper_start() {
//		learn_press_get_template( 'global/before-main-content.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_wrapper_end' ) ) {
//	/**
//	 * wrapper end
//	 */
//	function learn_press_wrapper_end() {
//		learn_press_get_template( 'global/after-main-content.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_courses_loop_item_thumbnail' ) ) {
//	/**
//	 * Output the thumbnail of the course within loop
//	 */
//	function learn_press_courses_loop_item_thumbnail() {
//		learn_press_get_template( 'loop/course/thumbnail.php' );
//	}
//}



//if ( ! function_exists( 'learn_press_courses_loop_item_title' ) ) {
//	/**
//	 * Output the title of the course within loop
//	 */
//	function learn_press_courses_loop_item_title() {
//		learn_press_get_template( 'loop/course/title.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_courses_loop_item_begin_meta' ) ) {
//	/**
//	 * Output the excerpt of the course within loop
//	 */
//	function learn_press_courses_loop_item_begin_meta() {
//		learn_press_get_template( 'loop/course/meta-begin.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_courses_loop_item_end_meta' ) ) {
//	/**
//	 * Output the excerpt of the course within loop
//	 */
//	function learn_press_courses_loop_item_end_meta() {
//		learn_press_get_template( 'loop/course/meta-end.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_courses_loop_item_introduce' ) ) {
//	/**
//	 * Output the excerpt of the course within loop
//	 */
//	function learn_press_courses_loop_item_introduce() {
//		learn_press_get_template( 'loop/course/introduce.php' );
//	}
//}

if ( ! function_exists( 'learn_press_courses_loop_item_price' ) ) {
	/**
	 * Output the price of the course within loop
	 * @using in many themes.
	 */
	function learn_press_courses_loop_item_price() {
		learn_press_get_template( 'loop/course/price.php' );
	}
}

if ( ! function_exists( 'learn_press_begin_courses_loop' ) ) {
	/**
	 * Output the price of the course within loop
	 * @using in many themes.
	 */
	function learn_press_begin_courses_loop() {
		learn_press_get_template( 'loop/course/loop-begin.php' );
	}
}

if ( ! function_exists( 'learn_press_end_courses_loop' ) ) {
	/**
	 * Output the price of the course within loop
	 * @using in many themes.
	 */
	function learn_press_end_courses_loop() {
		learn_press_get_template( 'loop/course/loop-end.php' );
	}
}

//if ( ! function_exists( 'learn_press_courses_loop_item_students' ) ) {
//	/**
//	 * Output the students of the course within loop
//	 * @deprecated 4.0.0
//	 */
//	function learn_press_courses_loop_item_students() {
//		_deprecated_function( __FUNCTION__, '4.0.0' );
//		echo '<div class="clearfix"></div>';
//		learn_press_get_template( 'loop/course/students.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_courses_pagination' ) ) {
//	/**
//	 * Output the pagination of archive courses
//	 */
//	function learn_press_courses_pagination() {
//		learn_press_get_template( 'loop/course/pagination.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_output_single_course_learning_summary' ) ) {
//	/**
//	 * Output the content of learning course content
//	 */
//	function learn_press_output_single_course_learning_summary() {
//		learn_press_get_template( 'single-course/content-learning.php' );
//	}
//}


//if ( ! function_exists( 'learn_press_output_single_course_landing_summary' ) ) {
//	/**
//	 * Output the content of landing course content
//	 */
//	function learn_press_output_single_course_landing_summary() {
//		learn_press_get_template( 'single-course/content-landing.php' );
//	}
//}



//if ( ! function_exists( 'learn_press_course_title' ) ) {
//	/**
//	 * Display the title for single course
//	 */
//	function learn_press_course_title() {
//		learn_press_get_template( 'single-course/title.php' );
//	}
//}

if ( ! function_exists( 'learn_press_course_progress' ) ) {
	/**
	 * Display course curriculum
	 * @using ivy-school
	 */
	function learn_press_course_progress() {
		learn_press_get_template( 'single-course/progress.php' );
	}
}

//if ( ! function_exists( 'learn_press_course_curriculum' ) ) {
//	/**
//	 * Display course curriculum
//	 */
//	function learn_press_course_curriculum() {
//		// learn_press_get_template( 'single-course/curriculum.php' );
//	}
//}

if ( ! function_exists( 'learn_press_course_categories' ) ) {
	/**
	 * Display course categories
	 * @using eduma child theme v5.5.5
	 */
	function learn_press_course_categories() {
		// learn_press_get_template( 'single-course/categories.php' );
	}
}

//if ( ! function_exists( 'learn_press_course_tags' ) ) {
//	/**
//	 * Display course tags
//	 */
//	function learn_press_course_tags() {
//		learn_press_get_template( 'single-course/tags.php' );
//	}
//}

if ( ! function_exists( 'learn_press_course_instructor' ) ) {
	/**
	 * Display course instructor
	 * @using in many themes.
	 */
	function learn_press_course_instructor() {
		learn_press_get_template( 'single-course/instructor.php' );
	}
}

if ( ! function_exists( 'learn_press_course_thumbnail' ) ) {
	/**
	 * Display Course Thumbnail
	 * @using in Eduma, Education Pack themes.
	 */
	function learn_press_course_thumbnail() {
		learn_press_get_template( 'single-course/thumbnail.php' );
	}
}

//if ( ! function_exists( 'learn_press_single_course_description' ) ) {
//	/**
//	 * Display course description
//	 */
//	function learn_press_single_course_description() {
//		learn_press_get_template( 'single-course/description.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_single_course_content_item' ) ) {
//	/**
//	 * Display lesson content
//	 */
//	function learn_press_single_course_content_item() {
//		learn_press_get_template( 'single-course/content-item.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_checkout_user_form_login' ) ) {
//	/**
//	 * Output login form before order review if user is not logged in
//	 */
//	function learn_press_checkout_user_form_login() {
//		learn_press_get_template( 'checkout/form-login.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_checkout_user_form_register' ) ) {
//	/**
//	 * Output register form before order review if user is not logged in
//	 */
//	function learn_press_checkout_user_form_register() {
//		learn_press_get_template( 'checkout/form-register.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_checkout_user_logged_in' ) ) {
//	/**
//	 * Output message before order review if user is logged in
//	 */
//	function learn_press_checkout_user_logged_in() {
//		learn_press_get_template( 'checkout/form-logged-in.php' );
//	}
//}


//if ( ! function_exists( 'learn_press_after_profile_tab_loop_course' ) ) {
//	/**
//	 * Display user profile tabs
//	 *
//	 * @param LP_User
//	 */
//	function learn_press_after_profile_tab_loop_course( $user, $course_id ) {
//
//		$args = array(
//			'user'      => $user,
//			'course_id' => $course_id,
//		);
//		learn_press_get_template( 'profile/tabs/courses/progress.php', $args );
//	}
//}


//if ( ! function_exists( 'learn_press_output_user_profile_info' ) ) {
//	/**
//	 * Displaying user info
//	 *
//	 * @param $user
//	 */
//	function learn_press_output_user_profile_info( $user, $current, $tabs ) {
//		learn_press_get_template(
//			'profile/info.php',
//			array(
//				'user'    => $user,
//				'tabs'    => $tabs,
//				'current' => $current,
//			)
//		);
//	}
//}

/* QUIZ TEMPLATES */
//if ( ! function_exists( 'learn_press_single_quiz_title' ) ) {
//	/**
//	 * Output the title of the quiz
//	 */
//	function learn_press_single_quiz_title() {
//		learn_press_get_template( 'content-quiz/title.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_message' ) ) {
//	/**
//	 * Template to display the messages
//	 *
//	 * @param        $content
//	 * @param string  $type
//	 */
//	function learn_press_message( $content, $type = 'message' ) {
//		learn_press_get_template(
//			'global/message.php',
//			array(
//				'type'    => $type,
//				'content' => $content,
//			)
//		);
//	}
//}

//if ( ! function_exists( 'learn_press_course_overview_tab' ) ) {
//	/**
//	 * Output course overview
//	 *
//	 * @since 1.1
//	 */
//	function learn_press_course_overview_tab() {
//		learn_press_get_template( 'single-course/tabs/overview.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_course_curriculum_tab' ) ) {
//	/**
//	 * Output course curriculum
//	 *
//	 * @since 1.1
//	 */
//	function learn_press_course_curriculum_tab() {
//		learn_press_get_template( 'single-course/tabs/curriculum.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_course_instructor_tab' ) ) {
//	/**
//	 * Output course curriculum
//	 *
//	 * @since 1.1
//	 */
//	function learn_press_course_instructor_tab() {
//		learn_press_get_template( 'single-course/tabs/instructor.php' );
//	}
//}


//if ( ! function_exists( 'learn_press_content_item_header' ) ) {
//	function learn_press_content_item_header() {
//		learn_press_get_template( 'single-course/content-item/header.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_content_item_footer' ) ) {
//	function learn_press_content_item_footer() {
//		learn_press_get_template( 'single-course/content-item/footer.php' );
//	}
//}


//if ( ! function_exists( 'learn_press_profile_mobile_menu' ) ) {
//	function learn_press_profile_mobile_menu() {
//		learn_press_get_template( 'profile/mobile-menu.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_quiz_complete_button' ) ) {
//
//	function learn_press_quiz_complete_button() {
//		$course = learn_press_get_course();
//		$user   = learn_press_get_current_user();
//		$quiz   = LP_Global::course_item_quiz();
//
//		if ( $user->has_course_status( $course->get_id(), array( 'finished' ) ) || ! $user->has_quiz_status( 'started', $quiz->get_id(), $course->get_id() ) ) {
//			return;
//		}
//		learn_press_get_template( 'content-quiz/buttons/complete.php' );
//	}
//}

//if ( ! function_exists( 'learn_press_content_item_summary_question_explanation' ) ) {
//
//	/**
//	 * Render content if quiz question.
//	 */
//	function learn_press_content_item_summary_question_explanation() {
//		$quiz     = LP_Global::course_item_quiz();
//		$question = $quiz->get_viewing_question();
//		if ( $question ) {
//			$course      = learn_press_get_course();
//			$user        = learn_press_get_current_user();
//			$course_data = $user->get_course_data( $course->get_id() );
//			$user_quiz   = $course_data->get_item_quiz( $quiz->get_id() );
//
//			if ( ! $question->get_explanation() ) {
//				return;
//			}
//
//			if ( $user_quiz->has_checked_question( $question->get_id() ) || $user_quiz->is_answered_true( $question->get_id() ) ) {
//				learn_press_get_template( 'content-question/explanation.php', array( 'question' => $question ) );
//			}
//		}
//	}
//}

if ( ! function_exists( 'learn_press_breadcrumb' ) ) {
	/**
	 * Output the breadcrumb of archive courses
	 * Still using
	 *
	 * @param array $args
	 */
	function learn_press_breadcrumb( $args = array() ) {
		$args = wp_parse_args(
			$args,
			apply_filters(
				'learn_press_breadcrumb_defaults',
				array(
					'delimiter'   => '&nbsp;&#47;&nbsp;',
					'wrap_before' => '<nav class="learn-press-breadcrumb">',
					'wrap_after'  => '</nav>',
					'before'      => '',
					'after'       => '',
					'home'        => _x( 'Home', 'breadcrumb', 'learnpress' ),
				)
			)
		);

		$breadcrumbs = new LP_Breadcrumb();

		if ( $args['home'] ) {
			$breadcrumbs->add_crumb( $args['home'], apply_filters( 'learn_press_breadcrumb_home_url', home_url() ) );
		}

		$args['breadcrumb'] = $breadcrumbs->generate();

		learn_press_get_template( 'global/breadcrumb.php', $args );
	}
}

//if ( ! function_exists( 'learn_press_search_form' ) ) {
//	/**
//	 * Output the breadcrumb of archive courses
//	 *
//	 * @param array
//	 */
//	function learn_press_search_form() {
//		if ( ! empty( $_REQUEST['s'] ) && ! empty( $_REQUEST['ref'] ) && 'course' == $_REQUEST['ref'] ) {
//			$s = stripslashes_deep( $_REQUEST['s'] );
//		} else {
//			$s = '';
//		}
//
//		learn_press_get_template( 'search-form.php', array( 's' => $s ) );
//	}
//}

//if ( ! function_exists( 'learn_press_section_item_meta' ) ) {
//	/**
//	 * @param object
//	 * @param array
//	 * @param LP_Course
//	 */
//	function learn_press_section_item_meta( $item, $section ) {
//		learn_press_get_template(
//			'single-course/section/item-meta.php',
//			array(
//				'item'    => $item,
//				'section' => $section,
//			)
//		);
//	}
//}

//if ( ! function_exists( 'learn_press_order_details_table' ) ) {
//
//	/**
//	 * Displays order details in a table.
//	 *
//	 * @param mixed $order_id
//	 *
//	 * @subpackage    Orders
//	 */
//	function learn_press_order_details_table( $order_id ) {
//		if ( ! $order_id ) {
//			return;
//		}
//
//		learn_press_get_template(
//			'order/order-details.php',
//			array(
//				'order' => learn_press_get_order( $order_id ),
//			)
//		);
//	}
//}


//if ( ! function_exists( 'learn_press_profile_tab_courses_own' ) ) {
//	/**
//	 * Display user profile tabs
//	 *
//	 * @param LP_User
//	 */
//	function learn_press_profile_tab_courses_own( $user, $tab = null ) {
//		$args              = array(
//			'user'   => $user,
//			'subtab' => $tab,
//		);
//		$limit             = LP_Settings::instance()->get( 'profile_courses_limit', 10 );
//		$limit             = apply_filters( 'learn_press_profile_tab_courses_own_limit', $limit );
//		$courses           = $user->get( 'own-courses', array( 'limit' => $limit ) );
//		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
//		$args['courses']   = $courses;
//		$args['num_pages'] = $num_pages;
//		learn_press_get_template( 'profile/tabs/courses/own.php', $args );
//	}
//}


//if ( ! function_exists( 'learn_press_profile_tab_courses_learning' ) ) {
//	/**
//	 * Display user profile tabs
//	 *
//	 * @param LP_User
//	 */
//	function learn_press_profile_tab_courses_learning( $user, $tab = null ) {
//		$args              = array(
//			'user'   => $user,
//			'subtab' => $tab,
//		);
//		$limit             = LP_Settings::instance()->get( 'profile_courses_limit', 10 );
//		$limit             = apply_filters( 'learn_press_profile_tab_courses_learning_limit', $limit );
//		$courses           = $user->get(
//			'enrolled-courses',
//			array(
//				'status' => 'enrolled',
//				'limit'  => $limit,
//			)
//		);
//		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
//		$args['courses']   = $courses;
//		$args['num_pages'] = $num_pages;
//		learn_press_get_template( 'profile/tabs/courses/learning.php', $args );
//	}
//}

//if ( ! function_exists( 'learn_press_profile_tab_courses_purchased' ) ) {
//	/**
//	 * Display user profile tabs
//	 *
//	 * @param LP_User
//	 */
//	function learn_press_profile_tab_courses_purchased( $user, $tab = null ) {
//		$args              = array(
//			'user'   => $user,
//			'subtab' => $tab,
//		);
//		$limit             = LP_Settings::instance()->get( 'profile_courses_limit', 10 );
//		$limit             = apply_filters( 'learn_press_profile_tab_courses_purchased_limit', $limit );
//		$courses           = $user->get( 'purchased-courses', array( 'limit' => $limit ) );
//		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
//		$args['courses']   = $courses;
//		$args['num_pages'] = $num_pages;
//		learn_press_get_template( 'profile/tabs/courses/purchased.php', $args );
//	}
//}

//if ( ! function_exists( 'learn_press_profile_tab_courses_finished' ) ) {
//	/**
//	 * Display user profile tabs
//	 *
//	 * @param LP_User
//	 */
//	function learn_press_profile_tab_courses_finished( $user, $tab = null ) {
//		$args              = array(
//			'user'   => $user,
//			'subtab' => $tab,
//		);
//		$limit             = LP_Settings::instance()->get( 'profile_courses_limit', 10 );
//		$limit             = apply_filters( 'learn_press_profile_tab_courses_finished_limit', $limit );
//		$courses           = $user->get(
//			'enrolled-courses',
//			array(
//				'status' => 'finished',
//				'limit'  => $limit,
//			)
//		);
//		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
//		$args['courses']   = $courses;
//		$args['num_pages'] = $num_pages;
//		learn_press_get_template( 'profile/tabs/courses/finished.php', $args );
//	}
//}


//if ( ! function_exists( 'learn_press_output_user_profile_tabs' ) ) {
//	/**
//	 * Display user profile tabs
//	 *
//	 * @param LP_User
//	 */
//	function learn_press_output_user_profile_tabs( $user, $current, $tabs ) {
//		learn_press_get_template(
//			'profile/tabs.php',
//			array(
//				'user'    => $user,
//				'tabs'    => $tabs,
//				'current' => $current,
//			)
//		);
//	}
//}

//if ( ! function_exists( 'learn_press_profile_tab_courses_all' ) ) {
//	/**
//	 * Display user profile tabs
//	 *
//	 * @param LP_User
//	 */
//	function learn_press_profile_tab_courses_all( $user, $tab = null ) {
//		$args              = array(
//			'user'   => $user,
//			'subtab' => $tab,
//		);
//		$limit             = LP_Settings::instance()->get( 'profile_courses_limit', 10 );
//		$limit             = apply_filters( 'learn_press_profile_tab_courses_all_limit', $limit );
//		$courses           = $user->get( 'courses', array( 'limit' => $limit ) );
//		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
//		$args['courses']   = $courses;
//		$args['num_pages'] = $num_pages;
//		learn_press_get_template( 'profile/tabs/courses.php', $args );
//	}
//}

//if ( ! function_exists( 'learn_press_become_teacher_messages' ) ) {
//	function learn_press_become_teacher_messages() {
//		$messages = LP_Shortcode_Become_A_Teacher::get_messages();
//		if ( ! $messages ) {
//			return;
//		}
//
//		learn_press_get_template( 'global/become-teacher-form/message.php', array( 'messages' => $messages ) );
//	}
//}

//if ( ! function_exists( 'learn_press_become_teacher_heading' ) ) {
//
//	function learn_press_become_teacher_heading() {
//		return '';
//	}
//}

/*if ( ! function_exists( 'learn_press_become_teacher_button' ) ) {

	function learn_press_become_teacher_button() {
		$messages = LP_Shortcode_Become_A_Teacher::get_messages();
		if ( $messages ) {
			return;
		}

		learn_press_get_template( 'global/become-teacher-form/button.php' );
	}
}*/

/*if ( ! function_exists( 'learn_press_back_to_class_button' ) ) {
	function learn_press_back_to_class_button() {
		return '';
	}
}*/
