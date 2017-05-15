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

if ( !function_exists( 'learn_press_single_course_args' ) ) {
	function learn_press_single_course_args() {
		$course = LP()->global['course'];
		if ( $course && $course->id ) {
			$course->output_args();
		}
	}
}

if ( !function_exists( 'learn_press_course_meta_start_wrapper' ) ) {
	/**
	 * Output the thumbnail of the course within loop
	 */
	function learn_press_course_meta_start_wrapper() {
		learn_press_get_template( 'global/course-meta-start.php' );
	}
}

if ( !function_exists( 'learn_press_course_meta_end_wrapper' ) ) {
	/**
	 * Output the thumbnail of the course within loop
	 */
	function learn_press_course_meta_end_wrapper() {
		learn_press_get_template( 'global/course-meta-end.php' );
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

if ( !function_exists( 'learn_press_courses_loop_item_begin_meta' ) ) {
	/**
	 * Output the excerpt of the course within loop
	 */
	function learn_press_courses_loop_item_begin_meta() {
		learn_press_get_template( 'loop/course/meta-begin.php' );
	}
}

if ( !function_exists( 'learn_press_courses_loop_item_end_meta' ) ) {
	/**
	 * Output the excerpt of the course within loop
	 */
	function learn_press_courses_loop_item_end_meta() {
		learn_press_get_template( 'loop/course/meta-end.php' );
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

if ( !function_exists( 'learn_press_begin_courses_loop' ) ) {
	/**
	 * Output the price of the course within loop
	 */
	function learn_press_begin_courses_loop() {
		learn_press_get_template( 'loop/course/loop-begin.php' );
	}
}

if ( !function_exists( 'learn_press_end_courses_loop' ) ) {
	/**
	 * Output the price of the course within loop
	 */
	function learn_press_end_courses_loop() {
		learn_press_get_template( 'loop/course/loop-end.php' );
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
			'home'        => _x( 'Home', 'breadcrumb', 'learnpress' )
		) ) );

		$breadcrumbs = new LP_Breadcrumb();

		if ( $args['home'] ) {
			$breadcrumbs->add_crumb( $args['home'], apply_filters( 'learn_press_breadcrumb_home_url', home_url() ) );
		}

		$args['breadcrumb'] = $breadcrumbs->generate();

		learn_press_get_template( 'global/breadcrumb.php', $args );
	}
}

if ( !function_exists( 'learn_press_search_form' ) ) {
	/**
	 * Output the breadcrumb of archive courses
	 *
	 * @param array
	 */
	function learn_press_search_form() {
		if ( !empty( $_REQUEST['s'] ) && !empty( $_REQUEST['ref'] ) && 'course' == $_REQUEST['ref'] ) {
			$s = $_REQUEST['s'];
		} else {
			$s = '';
		}

		learn_press_get_template( 'search-form.php', array( 's' => $s ) );
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


if ( !function_exists( 'learn_press_course_progress' ) ) {
	/**
	 * Display course curriculum
	 */
	function learn_press_course_progress() {
		learn_press_get_template( 'single-course/progress.php' );
	}
}

if ( !function_exists( 'learn_press_course_finish_button' ) ) {
	/**
	 * Display course curriculum
	 */
	function learn_press_course_finish_button() {
		learn_press_get_template( 'single-course/finish-button.php' );
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

if ( !function_exists( 'learn_press_course_retake_button' ) ) {
	/**
	 * Display course retake button
	 */
	function learn_press_course_retake_button() {
		learn_press_get_template( 'single-course/retake-button.php' );
	}
}

if ( !function_exists( 'learn_press_course_buttons' ) ) {
	/**
	 * Display course retake button
	 */
	function learn_press_course_buttons() {
		learn_press_get_template( 'single-course/buttons.php' );
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
		//learn_press_get_template( 'single-course/content-lesson.php' );
	}
}

if ( !function_exists( 'learn_press_single_course_content_item' ) ) {
	/**
	 * Display lesson content
	 */
	function learn_press_single_course_content_item() {
		learn_press_get_template( 'single-course/content-item.php' );
	}
}

if ( !function_exists( 'learn_press_course_students_list' ) ) {
	/**
	 * Display list of students enrolled to the course
	 */
	function learn_press_course_students_list() {
		learn_press_get_template( 'single-course/students-list.php' );
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
	function learn_press_output_user_profile_tabs( $user, $current, $tabs ) {
		learn_press_get_template( 'profile/tabs.php', array( 'user' => $user, 'tabs' => $tabs, 'current' => $current ) );
	}
}

if ( !function_exists( 'learn_press_output_user_profile_order' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_output_user_profile_order( $user, $current, $tabs ) {

//		learn_press_get_template( 'profile/tabs/orders.php', array( 'user' => $user, 'tabs' => $tabs, 'current' => $current ) );
	}
}
if ( !function_exists( 'learn_press_profile_tab_courses_all' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_all( $user, $tab = null ) {
		$args              = array(
			'user'   => $user,
			'subtab' => $tab
		);
		$limit             = LP()->settings->get( 'profile_courses_limit', 10 );
		$limit             = apply_filters( 'learn_press_profile_tab_courses_all_limit', $limit );
		$courses           = $user->get( 'courses', array( 'limit' => $limit ) );
		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
		$args['courses']   = $courses;
		$args['num_pages'] = $num_pages;
		learn_press_get_template( 'profile/tabs/courses.php', $args );
	}
}

if ( !function_exists( 'learn_press_profile_tab_courses_learning' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_learning( $user, $tab = null ) {
		$args              = array(
			'user'   => $user,
			'subtab' => $tab
		);
		$limit             = LP()->settings->get( 'profile_courses_limit', 10 );
		$limit             = apply_filters( 'learn_press_profile_tab_courses_learning_limit', $limit );
		$courses           = $user->get( 'enrolled-courses', array( 'status' => 'enrolled', 'limit' => $limit ) );
		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
		$args['courses']   = $courses;
		$args['num_pages'] = $num_pages;
		learn_press_get_template( 'profile/tabs/courses/learning.php', $args );
	}
}

if ( !function_exists( 'learn_press_profile_tab_courses_purchased' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_purchased( $user, $tab = null ) {
		$args              = array(
			'user'   => $user,
			'subtab' => $tab
		);
		$limit             = LP()->settings->get( 'profile_courses_limit', 10 );
		$limit             = apply_filters( 'learn_press_profile_tab_courses_purchased_limit', $limit );
		$courses           = $user->get( 'purchased-courses', array( 'limit' => $limit ) );
		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
		$args['courses']   = $courses;
		$args['num_pages'] = $num_pages;
		learn_press_get_template( 'profile/tabs/courses/purchased.php', $args );
	}
}

if ( !function_exists( 'learn_press_profile_tab_courses_finished' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_finished( $user, $tab = null ) {
		$args              = array(
			'user'   => $user,
			'subtab' => $tab
		);
		$limit             = LP()->settings->get( 'profile_courses_limit', 10 );
		$limit             = apply_filters( 'learn_press_profile_tab_courses_finished_limit', $limit );
		$courses           = $user->get( 'enrolled-courses', array( 'status' => 'finished', 'limit' => $limit ) );
		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
		$args['courses']   = $courses;
		$args['num_pages'] = $num_pages;
		learn_press_get_template( 'profile/tabs/courses/finished.php', $args );
	}
}

if ( !function_exists( 'learn_press_profile_tab_courses_own' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_own( $user, $tab = null ) {
		$args              = array(
			'user'   => $user,
			'subtab' => $tab
		);
		$limit             = LP()->settings->get( 'profile_courses_limit', 10 );
		$limit             = apply_filters( 'learn_press_profile_tab_courses_own_limit', $limit );
		$courses           = $user->get( 'own-courses', array( 'limit' => $limit ) );
		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
		$args['courses']   = $courses;
		$args['num_pages'] = $num_pages;
		learn_press_get_template( 'profile/tabs/courses/own.php', $args );
	}
}

if ( !function_exists( 'learn_press_after_profile_tab_loop_course' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_after_profile_tab_loop_course( $user, $course_id ) {

		$args = array(
			'user'      => $user,
			'course_id' => $course_id
		);
		learn_press_get_template( 'profile/tabs/courses/progress.php', $args );

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
	function learn_press_user_profile_tabs( $user = null ) {
		if ( !$user ) {
			$user = learn_press_get_current_user();
		}
		return LP_Profile::instance( $user->id )->get_tabs();
	}
}

if ( !function_exists( 'learn_press_output_user_profile_info' ) ) {
	/**
	 * Displaying user info
	 *
	 * @param $user
	 */
	function learn_press_output_user_profile_info( $user, $current, $tabs ) {
		learn_press_get_template( 'profile/info.php', array(
			'user'    => $user,
			'tabs'    => $tabs,
			'current' => $current
		) );
	}
}

/* QUIZ TEMPLATES */
if ( !function_exists( 'learn_press_single_quiz_title' ) ) {
	/**
	 * Output the title of the quiz
	 */
	function learn_press_single_quiz_title() {
		learn_press_get_template( 'content-quiz/title.php' );
	}
}


if ( !function_exists( 'learn_press_after_quiz_question_title' ) ) {
	function learn_press_single_quiz_question_answer( $question_id = null, $quiz_id = null ) {
		learn_press_get_template( 'content-quiz/question-answer.php', array(
			'question_id' => $question_id,
			'quiz_id'     => $quiz_id
		) );
	}
}


if ( !function_exists( 'learn_press_course_item_class' ) ) {
	function learn_press_course_item_class( $item_id, $course_id = 0, $class = null ) {
		switch ( get_post_type( $item_id ) ) {
			case 'lp_lesson':
				learn_press_course_lesson_class( $item_id, $course_id, $class );
				break;
			case 'lp_quiz':
				learn_press_course_quiz_class( $item_id, $course_id, $class );
				break;
		}
	}
}

if ( !function_exists( 'learn_press_course_lesson_class' ) ) {
	/**
	 * The class of lesson in course curriculum
	 *
	 * @param int          $lesson_id
	 * @param int          $course_id
	 * @param array|string $class
	 * @param boolean      $echo
	 *
	 * @return mixed
	 */
	function learn_press_course_lesson_class( $lesson_id = null, $course_id = 0, $class = null, $echo = true ) {
		$user = learn_press_get_current_user();
		if ( !$course_id ) {
			$course_id = get_the_ID();
		}

		$course = learn_press_get_course( $course_id );
		if ( !$course ) {
			return '';
		}

		if ( is_string( $class ) && $class ) {
			$class = preg_split( '!\s+!', $class );
		} else {
			$class = array();
		}

		$classes = array(
			'course-lesson course-item course-item-' . $lesson_id
		);
		if ( $status = LP()->user->get_item_status( $lesson_id ) ) {
			$classes[] = "item-has-status item-{$status}";
		}
		if ( $lesson_id && $course->is( 'current-item', $lesson_id ) ) {
			$classes[] = 'item-current';
		}
		if ( learn_press_is_course() ) {
			if ( $course->is_free() ) {
				$classes[] = 'free-item';
			}
		}
		$lesson = LP_Lesson::get_lesson( $lesson_id );
		if ( $lesson && $lesson->is_previewable() ) {
			$classes[] = 'preview-item';
		}

		if ( $user->can( 'view-item', $lesson_id, $course_id ) ) {
			$classes[] = 'viewable';
		}

		$classes = array_unique( array_merge( $classes, $class ) );
		if ( $echo ) {
			echo 'class="' . implode( ' ', $classes ) . '"';
		}
		return $classes;
	}
}

if ( !function_exists( 'learn_press_course_quiz_class' ) ) {
	/**
	 * The class of lesson in course curriculum
	 *
	 * @param int          $quiz_id
	 * @param int          $course_id
	 * @param string|array $class
	 * @param boolean      $echo
	 *
	 * @return mixed
	 */
	function learn_press_course_quiz_class( $quiz_id = null, $course_id = 0, $class = null, $echo = true ) {
		$user = learn_press_get_current_user();
		if ( !$course_id ) {
			$course_id = get_the_ID();
		}
		if ( is_string( $class ) && $class ) {
			$class = preg_split( '!\s+!', $class );
		} else {
			$class = array();
		}

		$course = learn_press_get_course( $course_id );
		if ( !$course ) {
			return '';
		}

		$classes = array(
			'course-quiz course-item course-item-' . $quiz_id
		);

		if ( $status = LP()->user->get_item_status( $quiz_id ) ) {
			$classes[] = "item-has-status item-{$status}";
		}

		if ( $quiz_id && $course->is( 'current-item', $quiz_id ) ) {
			$classes[] = 'item-current';
		}

		if ( $user->can( 'view-item', $quiz_id, $course_id ) ) {
			$classes[] = 'viewable';
		}

		if ( $course->is_final_quiz( $quiz_id ) ) {
			$classes[] = 'final-quiz';
		}

		$classes = array_unique( array_merge( $classes, $class ) );

		if ( $echo ) {
			echo 'class="' . join( ' ', $classes ) . '"';
		}
		return $classes;
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
	 * Append new class to course post type
	 *
	 * @param $classes
	 * @param $class
	 * @param $post_id
	 *
	 * @return string
	 */
	function learn_press_course_class( $classes, $class, $post_id = '' ) {
		if ( is_learnpress() ) {
			$classes = (array) $classes;
			if ( false !== ( $key = array_search( 'hentry', $classes ) ) ) {
				//unset( $classes[$key] );
			}
		}
		if ( $post_id === 0 ) {
			$classes[] = 'page type-page';
		}
		if ( !$post_id || 'lp_course' !== get_post_type( $post_id ) ) {
			return $classes;
		}
		$classes[] = 'course';
		return apply_filters( 'learn_press_course_class', $classes );
	}
}
/**
 * When the_post is called, put course data into a global.
 *
 * @param mixed $post
 *
 * @return LP_Course
 */
function learn_press_setup_object_data( $post ) {

	global $course;
	$object = null;

	if ( is_int( $post ) )
		$post = get_post( $post );

	if ( !$post ) {
		return $object;
	}

	if ( $post->post_type == LP_COURSE_CPT ) {
		if ( isset( $GLOBALS['course'] ) ) {
			unset( $GLOBALS['course'] );
		}
		$object                = learn_press_get_course( $post );
		LP()->global['course'] = $course = $object;
		LP()->set_object( '_course', $object );
	}
	return $object;
}

add_action( 'the_post', 'learn_press_setup_object_data' );


/**
 * Display a message immediately with out push into queue
 *
 * @param        $message
 * @param string $type
 */

function learn_press_display_message( $message, $type = 'success' ) {

	// get all messages added into queue
	$messages = learn_press_session_get( 'messages' );
	learn_press_session_set( 'messages', null );

	// add new notice and display
	learn_press_add_message( $message, $type );
	echo learn_press_get_messages( true );

	// store back messages
	learn_press_session_set( 'messages', $messages );
}

/**
 * Returns all notices added
 *
 * @param bool $clear
 *
 * @return string
 */
function learn_press_get_messages( $clear = false ) {
	ob_start();
	learn_press_print_messages( $clear );
	return ob_get_clean();
}

function learn_press_add_message( $message, $type = 'success', $autoclose = false ) {
	$messages = learn_press_session_get( 'messages' );
	if ( empty( $messages[$type] ) ) {
		$messages[$type] = array();
	}
	$messages[$type][] = array( 'content' => $message, 'autoclose' => $autoclose );
	learn_press_session_set( 'messages', $messages );
}

function learn_press_get_message( $message, $type = 'success' ) {
	ob_start();
	learn_press_display_message( $message, $type );
	$message = ob_get_clean();
	return $message;
}

/**
 * Print out the message stored in the queue
 *
 * @param bool
 */
function learn_press_print_messages( $clear = true ) {
	$messages = learn_press_session_get( 'messages' );
	learn_press_get_template( 'global/message.php', array( 'messages' => $messages ) );
	if ( $clear ) {
		learn_press_session_set( 'messages', array() );
	}
}

/**
 * Displays messages before main content
 */
function _learn_press_print_messages() {
	learn_press_print_messages( true );
}

add_action( 'learn_press_before_main_content', '_learn_press_print_messages', 50 );

if ( !function_exists( 'learn_press_page_controller' ) ) {
	/**
	 * Check permission to view page
	 *
	 * @param  file $template
	 *
	 * @return file
	 */
	function learn_press_page_controller( $template/*, $slug, $name*/ ) {
		global $wp;
		if ( isset( $wp->query_vars['lp-order-received'] ) ) {
			global $post;
			$post->post_title = __( 'Order received', 'learnpress' );
		}
		if ( is_single() ) {
			$user     = LP()->user;
			$redirect = false;
			$item_id  = 0;

			switch ( get_post_type() ) {
				case LP_QUIZ_CPT:
					$quiz          = LP()->quiz;
					$quiz_status   = LP()->user->get_quiz_status( get_the_ID() );
					$redirect      = false;
					$error_message = false;
					if ( !$user->can( 'view-quiz', $quiz->id ) ) {
						if ( $course = $quiz->get_course() ) {
							$redirect      = $course->permalink;
							$error_message = sprintf( __( 'Access denied "%s"', 'learnpress' ) );
						}
					} elseif ( $quiz_status == 'started' && ( empty( $_REQUEST['question'] ) && $current_question = $user->get_current_quiz_question( $quiz->id ) ) ) {
						$redirect = $quiz->get_question_link( $current_question );
					} elseif ( $quiz_status == 'completed'/* && !empty( $_REQUEST['question'] )*/ ) {
						$redirect = get_the_permalink( $quiz->id );
					} elseif ( learn_press_get_request( 'question' ) && $quiz_status == '' ) {
						$redirect = get_the_permalink( $quiz->id );
					}
					$item_id  = $quiz->id;
					$redirect = apply_filters( 'learn_press_quiz_access_denied_redirect_permalink', $redirect, $quiz_status, $quiz->id, $user->id );
					break;
				case LP_COURSE_CPT:
					if ( $item_id = LP()->course->is( 'viewing-item' ) ) {
						if ( !LP()->user->can( 'view-item', $item_id ) ) {
							$redirect = apply_filters( 'learn_press_lesson_access_denied_redirect_permalink', LP()->course->permalink, $item_id, $user->id );
						}
					}
			}

			// prevent loop redirect
			/*if ( $redirect && !learn_press_is_current_url( $redirect ) ) {
				if ( $item_id && $error_message ) {
					$error_message = apply_filters( 'learn_press_course_item_access_denied_error_message', get_the_title( $item_id ) );
					if ( $error_message !== false ) {
						learn_press_add_notice( $error_message, 'error' );
					}
				}
				wp_redirect( $redirect );
				exit();
			}*/
		}

		return $template;
	}
}
//add_filter( 'template_include', 'learn_press_page_controller' );

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
			$page_title = sprintf( __( 'Search Results: &ldquo;%s&rdquo;', 'learnpress' ), get_search_query() );

			if ( get_query_var( 'paged' ) )
				$page_title .= sprintf( __( '&nbsp;&ndash; Page %s', 'learnpress' ), get_query_var( 'paged' ) );

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
		$template = locate_template( array(
			"{$slug}-{$name}.php",
			learn_press_template_path() . "/{$slug}-{$name}.php"
		) );
	}

	// Get default slug-name.php
	if ( !$template && $name && file_exists( LP_PLUGIN_PATH . "/templates/{$slug}-{$name}.php" ) ) {
		$template = LP_PLUGIN_PATH . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/learnpress/slug.php
	if ( !$template ) {
		$template = locate_template( array( "{$slug}.php", learn_press_template_path() . "/{$slug}.php" ) );
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
 * Get template content
 *
 * @uses learn_press_get_template();
 *
 * @param        $template_name
 * @param array  $args
 * @param string $template_path
 * @param string $default_path
 *
 * @return string
 */
function learn_press_get_template_content( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	learn_press_get_template( $template_name, $args, $template_path, $default_path );
	return ob_get_clean();
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
		$default_path = LP_PLUGIN_PATH . 'templates/';
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
		$template = trailingslashit( $default_path ) . $template_name;
	}

	// Return what we found
	return apply_filters( 'learn_press_locate_template', $template, $template_name, $template_path );
}

/**
 * Returns the name of folder contains template files in theme
 *
 * @param bool
 *
 * @return string
 */
function learn_press_template_path( $slash = false ) {
	return apply_filters( 'learn_press_template_path', 'learnpress', $slash ) . ( $slash ? '/' : '' );
}


if ( !function_exists( 'learn_press_is_404' ) ) {
	/**
	 * Set header is 404
	 */
	function learn_press_is_404() {
		global $wp_query;
		if ( !empty( $_REQUEST['debug-404'] ) ) {
			learn_press_debug( debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, $_REQUEST['debug-404'] ) );
		}
		$wp_query->set_404();
		status_header( 404 );
	}
}

if ( !function_exists( 'learn_press_404_page' ) ) {
	/**
	 * Display 404 page
	 */
	function learn_press_404_page() {
		learn_press_is_404();
	}
}

if ( !function_exists( 'learn_press_course_curriculum_popup' ) ) {
	function learn_press_course_curriculum_popup() {
		learn_press_get_template( 'global/js-template.php' );
	}
}

if ( !function_exists( 'learn_press_generate_template_information' ) ) {
	function learn_press_generate_template_information( $template_name, $template_path, $located, $args ) {
		$debug = learn_press_get_request( 'show-template-location' );
		if ( $debug == 'on' ) {
			echo "<!-- Template Location:" . str_replace( array( LP_PLUGIN_PATH, ABSPATH ), '', $located ) . " -->";
		}
	}
}

if ( !function_exists( 'learn_press_course_remaining_time' ) ) {
	/**
	 * Show the time remain of a course
	 */
	function learn_press_course_remaining_time() {
		$user = learn_press_get_current_user();
		if ( !$user->has_finished_course( get_the_ID() ) && $text = learn_press_get_course( get_the_ID() )->get_user_duration_html( $user->id ) ) {
			learn_press_display_message( $text );
		}
	}
}

add_filter( 'template_include', 'learn_press_permission_view_quiz', 100 );
function learn_press_permission_view_quiz( $template ) {
	$quiz = LP()->global['course-item'];
	// if is not in single quiz
	if ( !learn_press_is_quiz() ) {
		return $template;
	}
	$user = learn_press_get_current_user();
	// If user haven't got permission
	if ( !current_user_can( 'edit-lp_quiz' ) && !$user->can( 'view-quiz', $quiz->id ) ) {
		switch ( LP()->settings->get( 'quiz_restrict_access' ) ) {
			case 'custom':
				$template = learn_press_locate_template( 'global/restrict-access.php' );
				break;
			default:
				learn_press_is_404();
		}
	}

	return $template;
}

function learn_press_course_item_content( $item ) {
	if ( $item ) {
		$item_template_name = learn_press_locate_template( 'single-course/content-item-' . $item->post->post_type . '.php' );
		if ( file_exists( $item_template_name ) ) {
			require $item_template_name;
		}
	}
}


if ( !function_exists( 'learn_press_item_meta_type' ) ) {
	function learn_press_item_meta_type( $course, $item ) { ?>

		<?php if ( $item->post_type == 'lp_quiz' ) { ?>

			<span class="lp-label lp-label-quiz"><?php _e( 'Quiz', 'learnpress' ); ?></span>

			<?php if ( $course->final_quiz == $item->ID ) { ?>

				<span class="lp-label lp-label-final"><?php _e( 'Final', 'learnpress' ); ?></span>

			<?php } ?>

		<?php } elseif ( $item->post_type == 'lp_lesson' ) { ?>

			<span class="lp-label lp-label-lesson"><?php _e( 'Lesson', 'learnpress' ); ?></span>
			<?php if ( get_post_meta( $item->ID, '_lp_preview', true ) == 'yes' ) { ?>

				<span class="lp-label lp-label-preview"><?php _e( 'Preview', 'learnpress' ); ?></span>

			<?php } ?>

		<?php } else { ?>

			<?php do_action( 'learn_press_item_meta_type', $course, $item ); ?>

		<?php }
	}
}

function learn_press_single_course_js() {
	if ( !learn_press_is_course() ) {
		return;
	}
	$user   = LP()->user;
	$course = LP()->course;
	$js     = array( 'url' => $course->get_permalink(), 'items' => array() );
	if ( $items = $course->get_curriculum_items() ) {
		foreach ( $items as $item ) {
			$item          = array(
				'id'        => absint( $item->ID ),
				'type'      => $item->post_type,
				'title'     => get_the_title( $item->ID ),
				'url'       => $course->get_item_link( $item->ID ),
				'current'   => $course->is_viewing_item( $item->ID ),
				'completed' => false,
				'viewable'  => $item->post_type == 'lp_quiz' ? ( $user->can_view_quiz( $item->ID, $course->id ) !== false ) : ( $user->can_view_lesson( $item->ID, $course->id ) !== false )
			);
			$js['items'][] = $item;
		}
	}
	echo '<script type="text/javascript">';
	echo 'var SingleCourse_Params = ' . json_encode( $js );
	echo '</script>';
}

///add_action( 'wp_head', 'learn_press_single_course_js' );

/*
 *
 */

if ( !function_exists( 'learn_press_course_tabs' ) ) {
	/*
	 * Output course tabs
	 */

	function learn_press_course_tabs() {
		learn_press_get_template( 'single-course/tabs/tabs.php' );
	}
}

if ( !function_exists( '_learn_press_default_course_tabs' ) ) {

	/**
	 * Add default tabs to course
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	function _learn_press_default_course_tabs( $tabs = array() ) {
		$course = LP()->global['course'];
		$user   = learn_press_get_current_user();

		$defaults = array();

		// Description tab - shows product content
		if ( $course->post->post_content ) {
			$defaults['overview'] = array(
				'title'    => __( 'Overview', 'learnpress' ),
				'priority' => 10,
				'callback' => 'learn_press_course_overview_tab'
			);
		}

		// Curriculum
		$defaults['curriculum'] = array(
			'title'    => __( 'Curriculum', 'learnpress' ),
			'priority' => 30,
			'callback' => 'learn_press_course_curriculum_tab'
		);

		/**
		 * Active Curriculum tab if user has enrolled course
		 */
		if ( $user->has_course_status( $course->id, array( 'enrolled' ) ) ) {
			$defaults['curriculum']['active'] = true;
		}

		$tabs = array_merge( $tabs, $defaults );

		return $tabs;
	}
}

if ( !function_exists( 'learn_press_course_overview_tab' ) ) {
	/**
	 * Output course overview
	 *
	 * @since 1.1
	 */
	function learn_press_course_overview_tab() {
		learn_press_get_template( 'single-course/tabs/overview.php' );
	}
}

if ( !function_exists( 'learn_press_course_curriculum_tab' ) ) {
	/**
	 * Output course curriculum
	 *
	 * @since 1.1
	 */
	function learn_press_course_curriculum_tab() {
		learn_press_get_template( 'single-course/tabs/curriculum.php' );
	}
}

if ( !function_exists( 'learn_press_sort_course_tabs' ) ) {

	function learn_press_sort_course_tabs( $tabs = array() ) {
		uasort( $tabs, '_learn_press_sort_course_tabs_callback' );
		return $tabs;
	}
}

if ( !function_exists( '_learn_press_sort_course_tabs_callback' ) ) {
	function _learn_press_sort_course_tabs_callback( $a, $b ) {
		if ( $a['priority'] === $b['priority'] )
			return 0;
		return ( $a['priority'] < $b['priority'] ) ? - 1 : 1;
	}
}

if ( !function_exists( 'learn_press_get_profile_display_name' ) ) {
	/**
	 * Get Display name publicly as in Profile page
	 *
	 * @param $user
	 *
	 * @return string
	 */
	function learn_press_get_profile_display_name( $user ) {
		if ( empty( $user ) ) {
			return '';
		}
		$info = get_userdata( $user->ID );
		return $info ? $info->display_name : '';
	}
}
function learn_press_is_content_item_only() {
	return !empty( $_REQUEST['content-item-only'] );
}

/**
 * Load course item content only
 */
function learn_press_load_content_item_only( $name ) {
	if ( learn_press_is_content_item_only() ) {
		if ( LP()->global['course-item'] ) {
			remove_action( 'get_header', 'learn_press_load_content_item_only' );
			learn_press_get_template( 'single-course/content-item-only.php' );
			die();
		}
	}
}

add_action( 'get_header', 'learn_press_load_content_item_only' );


// Fix issue with course content is duplicated if theme use the_content instead of $course->get_description()
add_filter( 'the_content', 'learn_press_course_the_content', 99999 );
function learn_press_course_the_content( $content ) {
	global $post;
	if ( $post && $post->post_type == 'lp_course' ) {
		$course = LP_Course::get_course( $post->ID );
		if ( $course ) {
			remove_filter( 'the_content', 'learn_press_course_the_content', 99999 );
			$content = $course->get_description();
			add_filter( 'the_content', 'learn_press_course_the_content', 99999 );
		}
	}
	return $content;
}

add_action( 'template_redirect', 'learn_press_check_access_lesson' );

function learn_press_check_access_lesson() {
	$queried_post_type = get_query_var( 'post_type' );
	if ( is_single() && 'lp_lesson' == $queried_post_type ) {
		$course = learn_press_get_course();
		if ( !$course ) {
			learn_press_is_404();
			return;
		}
		$post     = get_post();
		$user     = learn_press_get_current_user();
		$can_view = $user->can_view_item( $post->ID, $course->id );
		if ( !$can_view ) {
			learn_press_is_404();
			return;
		}
	} elseif ( is_single() && 'lp_course' == $queried_post_type ) {
		$course = learn_press_get_course();
		$item   = LP()->global['course-item'];
		if ( is_object( $item ) && isset( $item->post->post_type ) && 'lp_lesson' === $item->post->post_type ) {
			$user     = learn_press_get_current_user();
			$can_view = $user->can_view_item( $item->id, $course->id );
			if ( !$can_view ) {
				learn_press_404_page();
				return;
			}
		}
	}
}

function learn_press_fontend_js_template() {
	learn_press_get_template( 'global/js-template.php' );
}

add_action( 'wp_footer', 'learn_press_fontend_js_template' );
