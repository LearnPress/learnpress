<?php
/**
 * All functions for LearnPress template
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !function_exists( 'learn_press_wrapper_start' ) ) {
	/**
	 * Wrapper Start
	 */
	function learn_press_wrapper_start() {
		learn_press_get_template( 'global/before-main-content.php' );
	}
}

if ( !function_exists( 'learn_press_wrapper_end' ) ) {
	/**
	 * wrapper end
	 */
	function learn_press_wrapper_end() {
		learn_press_get_template( 'global/after-main-content.php' );
	}
}

if ( !function_exists( 'learn_press_courses_loop_item_thumbnail' ) ) {
	/**
	 * Output the thumbnail of the course within loop
	 */
	function learn_press_courses_loop_item_thumbnail() {
		learn_press_get_template( 'loop/course/thumbnail.php' );
	}
}

if ( !function_exists( 'learn_press_courses_loop_item_title' ) ) {
	/**
	 * Output the title of the course within loop
	 */
	function learn_press_courses_loop_item_title() {
		learn_press_get_template( 'loop/course/title.php' );
	}
}

if ( !function_exists( 'learn_press_courses_loop_item_introduce' ) ) {
	/**
	 * Output the excerpt of the course within loop
	 */
	function learn_press_courses_loop_item_introduce() {
		learn_press_get_template( 'loop/course/introduce.php' );
	}
}

if ( !function_exists( 'learn_press_courses_loop_item_price' ) ) {
	/**
	 * Output the price of the course within loop
	 */
	function learn_press_courses_loop_item_price() {
		learn_press_get_template( 'loop/course/price.php' );
	}
}

if ( !function_exists( 'learn_press_courses_loop_item_students' ) ) {
	/**
	 * Output the students of the course within loop
	 */
	function learn_press_courses_loop_item_students() {
		learn_press_get_template( 'loop/course/students.php' );
	}
}

if ( !function_exists( 'learn_press_courses_loop_item_instructor' ) ) {
	/**
	 * Output the instructor of the course within loop
	 */
	function learn_press_courses_loop_item_instructor() {
		learn_press_get_template( 'loop/course/instructor.php' );
	}
}

if ( !function_exists( 'learn_press_courses_pagination' ) ) {
	/**
	 * Output the pagination of archive courses
	 */
	function learn_press_courses_pagination() {
		learn_press_get_template( 'loop/course/pagination.php' );
	}
}

if ( !function_exists( 'learn_press_breadcrumb' ) ) {
	/**
	 * Output the breadcrumb of archive courses
	 *
	 * @param array
	 */
	function learn_press_breadcrumb( $args = array() ) {
		$args = wp_parse_args( $args, apply_filters( 'learn_press_breadcrumb_defaults', array(
			'delimiter'   => '&nbsp;&#47;&nbsp;',
			'wrap_before' => '<nav class="learn-press-breadcrumb" ' . ( is_single() ? 'itemprop="breadcrumb"' : '' ) . '>',
			'wrap_after'  => '</nav>',
			'before'      => '',
			'after'       => '',
			'home'        => _x( 'Home', 'breadcrumb', 'learn_press' )
		) ) );

		$breadcrumbs = new LP_Breadcrumb();

		if ( $args['home'] ) {
			$breadcrumbs->add_crumb( $args['home'], apply_filters( 'learn_press_breadcrumb_home_url', home_url() ) );
		}

		$args['breadcrumb'] = $breadcrumbs->generate();

		learn_press_get_template( 'global/breadcrumb.php', $args );
	}
}

if ( !function_exists( 'learn_press_output_single_course_learning_summary' ) ) {
	/**
	 * Output the content of learning course content
	 */
	function learn_press_output_single_course_learning_summary() {
		learn_press_get_template( 'single-course/content-learning.php' );
	}
}

if ( !function_exists( 'learn_press_output_single_course_landing_summary' ) ) {
	/**
	 * Output the content of landing course content
	 */
	function learn_press_output_single_course_landing_summary() {
		learn_press_get_template( 'single-course/content-landing.php' );
	}
}

if ( !function_exists( 'learn_press_course_title' ) ) {
	/**
	 * Display the title for single course
	 */
	function learn_press_course_title() {
		learn_press_get_template( 'single-course/title.php' );
	}
}

if ( !function_exists( 'learn_press_course_thumbnail' ) ) {
	/**
	 * Display the title for single course
	 */
	function learn_press_course_thumbnail() {
		learn_press_get_template( 'single-course/thumbnail.php' );
	}
}

if ( !function_exists( 'learn_press_course_curriculum' ) ) {
	/**
	 * Display course curriculum
	 */
	function learn_press_course_curriculum() {
		learn_press_get_template( 'single-course/curriculum.php' );
	}
}


if ( !function_exists( 'learn_press_course_price' ) ) {
	/**
	 * Display course price
	 */
	function learn_press_course_price() {
		learn_press_get_template( 'single-course/price.php' );
	}
}

if ( !function_exists( 'learn_press_course_categories' ) ) {
	/**
	 * Display course categories
	 */
	function learn_press_course_categories() {
		learn_press_get_template( 'single-course/categories.php' );
	}
}

if ( !function_exists( 'learn_press_course_tags' ) ) {
	/**
	 * Display course tags
	 */
	function learn_press_course_tags() {
		learn_press_get_template( 'single-course/tags.php' );
	}
}

if ( !function_exists( 'learn_press_course_students' ) ) {
	/**
	 * Display course students
	 */
	function learn_press_course_students() {
		learn_press_get_template( 'single-course/students.php' );
	}
}

if ( !function_exists( 'learn_press_course_instructor' ) ) {
	/**
	 * Display course instructor
	 */
	function learn_press_course_instructor() {
		learn_press_get_template( 'single-course/instructor.php' );
	}
}

if ( !function_exists( 'learn_press_course_enroll_button' ) ) {
	/**
	 * Display course enroll button
	 */
	function learn_press_course_enroll_button() {
		learn_press_get_template( 'single-course/enroll-button.php' );
	}
}

if ( !function_exists( 'learn_press_course_payment_form' ) ) {
	/**
	 * Course payment form
	 */
	function learn_press_course_payment_form() {
		_deprecated_function( __FUNCTION__, '1.0', need_to_updating() );
		learn_press_get_template( 'single-course/payment-form.php' );

	}
}

if ( !function_exists( 'learn_press_course_status_message' ) ) {
	/**
	 * Display course status message
	 */
	function learn_press_course_status_message() {
		learn_press_get_template( 'single-course/course-pending.php' );
	}
}

if ( !function_exists( 'learn_press_course_thumbnail' ) ) {
	/**
	 * Display Course Thumbnail
	 */
	function learn_press_course_thumbnail() {
		learn_press_get_template( 'single-course/thumbnail.php' );
	}
}

if ( !function_exists( 'learn_press_course_status' ) ) {
	/**
	 * Display the title for single course
	 */
	function learn_press_course_status() {
		learn_press_get_template( 'single-course/status.php' );
	}
}

if ( !function_exists( 'learn_press_single_course_description' ) ) {
	/**
	 * Display course description
	 */
	function learn_press_single_course_description() {
		learn_press_get_template( 'single-course/description.php' );
	}
}

if ( !function_exists( 'learn_press_single_course_lesson_content' ) ) {
	/**
	 * Display lesson content
	 */
	function learn_press_single_course_content_lesson() {
		learn_press_get_template( 'single-course/content-lesson.php' );
	}
}

if ( !function_exists( 'learn_press_course_quiz_description' ) ) {
	/**
	 * Display lesson content
	 */
	function learn_press_course_quiz_description() {
		learn_press_get_template( 'single-course/content-quiz.php' );
	}
}

if ( !function_exists( 'learn_press_course_lesson_description' ) ) {
	/**
	 * Display course lesson description
	 */
	function learn_press_course_lesson_description() {
		learn_press_get_template( 'lesson/description.php' );
	}
}

if ( !function_exists( 'learn_press_course_lesson_complete_button' ) ) {
	/**
	 * Display lesson complete button
	 */
	function learn_press_course_lesson_complete_button() {
		learn_press_get_template( 'lesson/complete-button.php' );
	}
}

if ( !function_exists( 'learn_press_course_lesson_navigation' ) ) {
	/**
	 * Display lesson navigation
	 */
	function learn_press_course_lesson_navigation() {
		learn_press_get_template( 'lesson/navigation.php' );
	}
}

if ( !function_exists( 'learn_press_curriculum_section_title' ) ) {
	/**
	 * @param object
	 */
	function learn_press_curriculum_section_title( $section ) {
		learn_press_get_template( 'single-course/section/title.php', array( 'section' => $section ) );
	}
}

if ( !function_exists( 'learn_press_curriculum_section_content' ) ) {
	/**
	 * @param object
	 */
	function learn_press_curriculum_section_content( $section ) {
		learn_press_get_template( 'single-course/section/content.php', array( 'section' => $section ) );
	}
}

if ( !function_exists( 'learn_press_section_item_meta' ) ) {
	/**
	 * @param object
	 * @param array
	 * @param LP_Course
	 */
	function learn_press_section_item_meta( $item, $section, $course ) {
		learn_press_get_template( 'single-course/section/item-meta.php', array( 'item' => $item, 'section' => $section ) );
	}
}

if ( !function_exists( 'learn_press_order_review' ) ) {
	/**
	 * Output order details
	 *
	 * @param LP_Checkout object
	 */
	function learn_press_order_review( $checkout ) {
		learn_press_get_template( 'checkout/review-order.php', array( 'checkout' => $checkout ) );
	}
}

if ( !function_exists( 'learn_press_order_payment' ) ) {
	/**
	 * Output payment methods
	 *
	 * @param LP_Checkout object
	 */
	function learn_press_order_payment( $checkout ) {
		$available_gateways = LP_Gateways::instance()->get_available_payment_gateways();
		learn_press_get_template( 'checkout/payment.php', array( 'available_gateways' => $available_gateways ) );
	}
}

if ( !function_exists( 'learn_press_order_details_table' ) ) {

	/**
	 * Displays order details in a table.
	 *
	 * @param mixed $order_id
	 *
	 * @subpackage    Orders
	 */
	function learn_press_order_details_table( $order_id ) {
		if ( !$order_id ) return;
		learn_press_get_template( 'order/order-details.php', array(
			'order' => learn_press_get_order( $order_id )
		) );
	}
}

if ( !function_exists( 'learn_press_order_comment' ) ) {
	/**
	 * Output order comment input
	 *
	 * @param LP_Checkout object
	 */
	function learn_press_order_comment( $checkout ) {
		learn_press_get_template( 'checkout/order-comment.php' );
	}
}

if ( !function_exists( 'learn_press_checkout_user_form' ) ) {
	/**
	 * Output login/register form before order review if user is not logged in
	 */
	function learn_press_checkout_user_form() {
		learn_press_get_template( 'checkout/user-form.php' );
	}
}

if ( !function_exists( 'learn_press_checkout_user_form_login' ) ) {
	/**
	 * Output login form before order review if user is not logged in
	 */
	function learn_press_checkout_user_form_login() {
		learn_press_get_template( 'checkout/form-login.php' );
	}
}

if ( !function_exists( 'learn_press_checkout_user_form_register' ) ) {
	/**
	 * Output register form before order review if user is not logged in
	 */
	function learn_press_checkout_user_form_register() {
		learn_press_get_template( 'checkout/form-register.php' );
	}
}

if ( !function_exists( 'learn_press_checkout_user_logged_in' ) ) {
	/**
	 * Output message before order review if user is logged in
	 */
	function learn_press_checkout_user_logged_in() {
		learn_press_get_template( 'checkout/form-logged-in.php' );
	}
}

if ( !function_exists( 'learn_press_enroll_script' ) ) {
	/**
	 */
	function learn_press_enroll_script() {
		LP_Assets::enqueue_script( 'learn-press-enroll', LP()->plugin_url( 'assets/js/frontend/enroll.js' ), array( 'learn-press-js' ) );
	}
}

if ( !function_exists( 'learn_press_output_user_profile_tabs' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_output_user_profile_tabs( $user ) {
		learn_press_get_template( 'profile/tabs.php', array( 'user' => $user ) );
	}
}

if ( !function_exists( 'learn_press_output_user_profile_order' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_output_user_profile_order( $user ) {

		learn_press_get_template( 'profile/order.php', array( 'user' => $user ) );
	}
}

if ( !function_exists( 'learn_press_profile_tab_courses_enrolled' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_enrolled( $user ) {
		learn_press_get_template( 'profile/tabs/courses/enrolled.php', array( 'user' => $user ) );
	}
}

if ( !function_exists( 'learn_press_profile_tab_courses_finished' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_finished( $user ) {
		learn_press_get_template( 'profile/tabs/courses/finished.php', array( 'user' => $user ) );
	}
}

if ( !function_exists( 'learn_press_profile_tab_courses_own' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_own( $user ) {
		learn_press_get_template( 'profile/tabs/courses/own.php', array( 'user' => $user ) );
	}
}

if ( !function_exists( 'learn_press_user_profile_tabs' ) ) {
	/**
	 * Get tabs for user profile
	 *
	 * @param $user
	 *
	 * @return mixed
	 */
	function learn_press_user_profile_tabs( $user ) {
		$course_endpoint = LP()->settings->get( 'profile_tab_courses_endpoint' );
		if( !$course_endpoint ){
			$course_endpoint = 'courses';
		}

		$quiz_endpoint = LP()->settings->get( 'profile_tab_quizzes_endpoint' );
		if( !$quiz_endpoint ){
			$quiz_endpoint = 'courses';
		}

		$order_endpoint = LP()->settings->get( 'profile_tab_orders_endpoint' );
		if( !$order_endpoint ){
			$order_endpoint = 'orders';
		}

		$defaults        = array(
			$course_endpoint => array(
				'title'    => __( 'Courses', 'learn_press' ),
				'callback' => array( $user, 'tab_courses_content' )
			),
			$quiz_endpoint => array(
				'title'    => __( 'Quiz Results', 'learn_press' ),
				'callback' => array( $user, 'tab_quizzes_content' )
			),
			$order_endpoint => array(
				'title'    => __( 'Orders', 'learn_press' ),
				'callback' => array( $user, 'tab_orders_content' )
			)
		);
		return apply_filters( 'learn_press_user_profile_tabs', $defaults, $user );
	}
}

if ( !function_exists( 'learn_press_output_user_profile_info' ) ) {
	/**
	 * Displaying user info
	 *
	 * @param $user
	 */
	function learn_press_output_user_profile_info( $user ) {
		learn_press_get_template( 'profile/info.php', array( 'user' => $user ) );
	}
}

/* QUIZ TEMPLATES */
if ( !function_exists( 'learn_press_single_quiz_title' ) ) {
	/**
	 * Output the title of the quiz
	 */
	function learn_press_single_quiz_title() {
		learn_press_get_template( 'single-quiz/title.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_description' ) ) {
	/**
	 * Output the content of the quiz
	 */
	function learn_press_single_quiz_description() {
		learn_press_get_template( 'single-quiz/description.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_left_start_wrap' ) ) {
	function learn_press_single_quiz_left_start_wrap() {
		learn_press_get_template( 'single-quiz/left-start-wrap.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_left_end_wrap' ) ) {
	function learn_press_single_quiz_left_end_wrap() {
		learn_press_get_template( 'single-quiz/left-end-wrap.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_question' ) ) {
	/**
	 * Output the single question for quiz
	 */
	function learn_press_single_quiz_question() {
		learn_press_get_template( 'single-quiz/content-question.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_questions' ) ) {
	/**
	 * Output the list of questions for quiz
	 */
	function learn_press_single_quiz_questions() {
		learn_press_get_template( 'single-quiz/questions.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_result' ) ) {
	/**
	 * Output the result for the quiz
	 */
	function learn_press_single_quiz_result() {
		learn_press_get_template( 'single-quiz/result.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_history' ) ) {
	/**
	 * Output the history of a quiz
	 */
	function learn_press_single_quiz_history() {
		learn_press_get_template( 'single-quiz/history.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_sidebar' ) ) {
	/**
	 * Output the sidebar for a quiz
	 */
	function learn_press_single_quiz_sidebar() {
		learn_press_get_template( 'single-quiz/sidebar.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_timer' ) ) {
	/**
	 * Output the quiz countdown timer
	 */
	function learn_press_single_quiz_timer() {
		learn_press_get_template( 'single-quiz/timer.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_buttons' ) ) {
	/**
	 * Output the buttons for quiz actions
	 */
	function learn_press_single_quiz_buttons() {
		learn_press_get_template( 'single-quiz/buttons.php' );
	}
}

if ( !function_exists( 'learn_press_course_lesson_class' ) ) {
	/**
	 * The class of lesson in course curriculum
	 *
	 * @param int          $lesson_id
	 * @param array|string $class
	 */
	function learn_press_course_lesson_class( $lesson_id = null, $class = null ) {
		if ( is_string( $class ) && $class ) $class = preg_split( '!\s+!', $class );
		else $class = array();

		$classes = array(
			'course-lesson course-item'
		);
		if ( learn_press_user_has_completed_lesson( $lesson_id ) ) {
			$classes[] = "completed";
		}
		if ( $lesson_id && !empty( $_REQUEST['lesson'] ) && ( $lesson_id == $_REQUEST['lesson'] ) ) {
			$classes[] = 'current';
		}
		if ( is_course() ) {
			$course = LP()->course;
			if ( $course->is_free() ) {
				$classes[] = 'free-item';
			}
		}
		$lesson = LP_Lesson::get_lesson( $lesson_id );
		if ( $lesson && $lesson->is_previewable() ) {
			$classes[] = 'preview-item';
		}
		$classes = array_unique( array_merge( $classes, $class ) );
		echo 'class="' . implode( ' ', $classes ) . '"';
	}
}

if ( !function_exists( 'learn_press_course_quiz_class' ) ) {
	/**
	 * The class of lesson in course curriculum
	 *
	 * @param int          $quiz_id
	 * @param string|array $class
	 */
	function learn_press_course_quiz_class( $quiz_id = null, $class = null ) {
		if ( is_string( $class ) && $class ) $class = preg_split( '!\s+!', $class );
		else $class = array();

		$classes = array(
			'course-quiz course-item'
		);
		if ( learn_press_user_has_completed_quiz( null, $quiz_id ) ) {
			$classes[] = "completed";
		}
		$classes = array_unique( array_merge( $classes, $class ) );
		echo 'class="' . join( ' ', $classes ) . '"';
	}
}

if ( !function_exists( 'learn_press_message' ) ) {
	/**
	 * Template to display the messages
	 *
	 * @param        $content
	 * @param string $type
	 */
	function learn_press_message( $content, $type = 'message' ) {
		learn_press_get_template( 'global/message.php', array( 'type' => $type, 'content' => $content ) );
	}
}

/******************************/

if ( !function_exists( 'learn_press_body_class' ) ) {
	/**
	 * Append new class to body classes
	 *
	 * @param $classes
	 *
	 * @return array
	 */
	function learn_press_body_class( $classes ) {
		$classes = (array) $classes;

		if ( is_learnpress() ) {
			$classes[] = 'learnpress';
			$classes[] = 'learnpress-page';
		}

		return array_unique( $classes );
	}
}

if ( !function_exists( 'learn_press_course_class' ) ) {
	/**
	 * Custom new class for course classes
	 *
	 * @param $classes
	 *
	 * @return array
	 */
	function learn_press_course_class( $classes ) {
		if ( is_learnpress() ) {
			$classes = (array) $classes;

			if ( false !== ( $key = array_search( 'hentry', $classes ) ) ) {
				unset( $classes[$key] );
			}
		}
		return $classes;
	}
}
/**
 * When the_post is called, put course data into a global.
 *
 * @param mixed $post
 *
 * @return LP_Course
 */
function lp_setup_course_data( $post ) {
	if ( isset( $GLOBALS['course'] ) ) {
		unset( $GLOBALS['course'] );
	}
	if ( is_int( $post ) )
		$post = get_post( $post );

	if ( empty( $post->post_type ) || !in_array( $post->post_type, array( LP()->course_post_type ) ) )
		return;

	$GLOBALS['course'] = lp_get_course( $post );
	return $GLOBALS['course'];
}

add_action( 'the_post', 'lp_setup_course_data' );


/**
 * Display a message immediately with out push into queue
 *
 * @param        $message
 * @param string $type
 */
function learn_press_display_message( $message, $type = 'success' ) {

	// get all notices added into queue
	$notices = LP_Session::get( 'notices' );
	LP_Session::set( 'notices', null );

	// add new notice and display
	learn_press_add_notice( $message, $type );
	echo learn_press_get_notices( true );

	// store back notices
	LP_Session::set( 'notices', $notices );
}

/**
 * Print out the message stored in the queue
 */
function learn_press_print_messages() {
	$messages = get_transient( 'learn_press_message' );
	if ( $messages ) foreach ( $messages as $type => $message ) {
		foreach ( $message as $mess ) {
			echo '<div class="lp-message ' . $type . '">';
			echo $mess;
			echo '</div>';
		}
	}
	delete_transient( 'learn_press_message' );
}

add_action( 'learn_press_before_main_content', 'learn_press_print_messages', 50 );

if ( !function_exists( 'learn_press_page_controller' ) ) {
	/**
	 * Check permission to view page
	 *
	 * @param  file $template
	 *
	 * @return file
	 */
	function learn_press_page_controller( $template/*, $slug, $name*/ ) {
		if ( get_post_type() == LP()->quiz_post_type && is_single() ) {
			global $quiz;
			$user        = LP()->user;
			$quiz        = LP_Quiz::get_quiz( get_the_ID() );
			$quiz_status = LP()->user->get_quiz_status( get_the_ID() );
			$redirect    = false;
			if ( !$user->can( 'view-quiz', $quiz->id ) ) {
				if ( $course = $quiz->get_course() ) {
					$redirect = $course->permalink;
				}
			} elseif ( $quiz_status == 'started' && ( empty( $_REQUEST['question'] ) && $current_question = $user->get_current_quiz_question( $quiz->id ) ) ) {
				$redirect = $quiz->get_question_link( $current_question );
			} elseif ( $quiz_status == 'complete' && !empty( $_REQUEST['question'] ) ) {
				$redirect = get_the_permalink( $quiz->id );
			}
			$redirect = apply_filters( 'learn_press_quiz_redirect_permalink', $redirect, $quiz_status, $quiz->id, $user );
			// prevent loop redirect
			if ( $redirect && $redirect != learn_press_get_current_url() ) {
				wp_redirect( $redirect );
				exit();
			}

		}
		return $template;
	}
}
add_filter( 'template_include', 'learn_press_page_controller' );

if ( !function_exists( 'learn_press_page_title' ) ) {

	/**
	 * learn_press_page_title function.
	 *
	 * @param  boolean $echo
	 *
	 * @return string
	 */
	function learn_press_page_title( $echo = true ) {

		if ( is_search() ) {
			$page_title = sprintf( __( 'Search Results: &ldquo;%s&rdquo;', 'learn_press' ), get_search_query() );

			if ( get_query_var( 'paged' ) )
				$page_title .= sprintf( __( '&nbsp;&ndash; Page %s', 'learn_press' ), get_query_var( 'paged' ) );

		} elseif ( is_tax() ) {

			$page_title = single_term_title( "", false );

		} else {

			$courses_page_id = learn_press_get_page_id( 'courses' );
			$page_title      = get_the_title( $courses_page_id );

		}

		$page_title = apply_filters( 'learn_press_page_title', $page_title );

		if ( $echo )
			echo $page_title;
		else
			return $page_title;
	}
}

function learn_press_template_redirect() {
	global $wp_query, $wp;

	// When default permalinks are enabled, redirect shop page to post type archive url
	if ( !empty( $_GET['page_id'] ) && get_option( 'permalink_structure' ) == "" && $_GET['page_id'] == learn_press_get_page_id( 'courses' ) ) {
		wp_safe_redirect( get_post_type_archive_link( 'lp_course' ) );
		exit;
	}
}

add_action( 'template_redirect', 'learn_press_template_redirect' );


/**
 * get template part
 *
 * @param   string $slug
 * @param   string $name
 *
 * @return  string
 */
function learn_press_get_template_part( $slug, $name = '' ) {
	$template = '';

	// Look in yourtheme/slug-name.php and yourtheme/learnpress/slug-name.php
	if ( $name ) {
		$template = locate_template( array( "{$slug}-{$name}.php", learn_press_template_path() . "/{$slug}-{$name}.php" ) );
	}

	// Get default slug-name.php
	if ( !$template && $name && file_exists( LP_PLUGIN_PATH . "/templates/{$slug}-{$name}.php" ) ) {
		$template = LP_PLUGIN_PATH . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/learnpress/slug.php
	if ( !$template ) {
		$template = locate_template( array( "{$slug}.php", learn_press_template_path() . "{$slug}.php" ) );
	}

	// Allow 3rd party plugin filter template file from their plugin
	if ( $template ) {
		$template = apply_filters( 'learn_press_get_template_part', $template, $slug, $name );
	}
	if ( $template && file_exists( $template ) ) {
		load_template( $template, false );
	}

	return $template;
}

/**
 * Get other templates passing attributes and including the file.
 *
 * @param string $template_name
 * @param array  $args          (default: array())
 * @param string $template_path (default: '')
 * @param string $default_path  (default: '')
 *
 * @return void
 */
function learn_press_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( $args && is_array( $args ) ) {
		extract( $args );
	}

	$located = learn_press_locate_template( $template_name, $template_path, $default_path );

	if ( !file_exists( $located ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
		return;
	}
	// Allow 3rd party plugin filter template file from their plugin
	$located = apply_filters( 'learn_press_get_template', $located, $template_name, $args, $template_path, $default_path );

	do_action( 'learn_press_before_template_part', $template_name, $template_path, $located, $args );

	include( $located );

	do_action( 'learn_press_after_template_part', $template_name, $template_path, $located, $args );
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 *        yourtheme        /    $template_path    /    $template_name
 *        yourtheme        /    $template_name
 *        $default_path    /    $template_name
 *
 * @access public
 *
 * @param string $template_name
 * @param string $template_path (default: '')
 * @param string $default_path  (default: '')
 *
 * @return string
 */
function learn_press_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( !$template_path ) {
		$template_path = learn_press_template_path();
	}

	if ( !$default_path ) {
		$default_path = LP_PLUGIN_PATH . '/templates/';
	}

	// Look within passed path within the theme - this is priority
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name
		)
	);

	// Get default template
	if ( !$template ) {
		$template = $default_path . $template_name;
	}

	// Return what we found
	return apply_filters( 'learn_press_locate_template', $template, $template_name, $template_path );
}

/**
 * Returns the name of folder contains template files in theme
 */
function learn_press_template_path() {
	return apply_filters( 'learn_press_template_path', 'learnpress' );
}

if ( !function_exists( 'learn_press_single_quiz_questions_nav' ) ) {
	/**
	 * Output the navigation to next and previous questions
	 */
	function learn_press_single_quiz_questions_nav() {
		learn_press_get_template( 'single-quiz/nav.php' );
	}
}

if ( !function_exists( 'learn_press_404_page' ) ) {
	/**
	 * Display 404 page
	 */
	function learn_press_404_page() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		get_template_part( 404 );
		exit();
	}
}