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

if ( !function_exists( 'learn_press_course_content_lesson' ) ) {
	/**
	 * Display course description
	 */
	function learn_press_course_content_lesson() {
		learn_press_get_template( 'lesson/summary.php' );
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

if ( !function_exists( 'learn_press_course_lesson_data' ) ) {
	/**
	 * Display course lesson description
	 */
	function learn_press_course_lesson_data() {
		$course = LP()->course;
		if ( !$course ) {
			return;
		}
		if ( !( $lesson = $course->current_lesson ) ) {
			return;
		}
		?>
		<input type="hidden" name="learn-press-lesson-viewing" value="<?php echo $lesson->id; ?>" />
		<?php
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

		//learn_press_get_template( 'profile/tabs/orders.php', array( 'user' => $user, 'tabs' => $tabs, 'current' => $current ) );
	}
}

if ( !function_exists( 'learn_press_profile_tab_courses_all' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_all( $user, $tab ) {
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
		learn_press_get_template( 'profile/tabs/courses/all.php', $args );
	}
}

if ( !function_exists( 'learn_press_profile_tab_courses_learning' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_learning( $user, $tab ) {
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
	function learn_press_profile_tab_courses_purchased( $user, $tab ) {
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
	function learn_press_profile_tab_courses_finished( $user, $tab ) {
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
	function learn_press_profile_tab_courses_own( $user, $tab ) {
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
	function learn_press_after_profile_tab_loop_course() {
		global $post;
		if ( !empty( $post->course_status ) ) {
			echo '<span class="course-status ' . esc_attr( $post->course_status ) . '">' . $post->course_status . '</span>';
		}
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
			$user = get_user_by( 'id', get_current_user_id() );
		}
		$course_endpoint = LP()->settings->get( 'profile_endpoints.profile-courses' );
		if ( !$course_endpoint ) {
			$course_endpoint = 'profile-courses';
		}

		$quiz_endpoint = LP()->settings->get( 'profile_endpoints.profile-quizzes' );
		if ( !$quiz_endpoint ) {
			$quiz_endpoint = 'profile-quizzes';
		}

		$order_endpoint = LP()->settings->get( 'profile_endpoints.profile-orders' );
		if ( !$order_endpoint ) {
			$order_endpoint = 'profile-orders';
		}

		$view_order_endpoint = LP()->settings->get( 'profile_endpoints' );
		if ( !$view_order_endpoint ) {
			$view_order_endpoint = 'order';
		}

		$defaults = array(
			$course_endpoint => array(
				'title'    => __( 'Courses', 'learnpress' ),
				'callback' => 'learn_press_profile_tab_courses_content'
			),
			$quiz_endpoint   => array(
				'title'    => __( 'Quiz Results', 'learnpress' ),
				'callback' => 'learn_press_profile_tab_quizzes_content'
			),
			$order_endpoint  => array(
				'title'    => __( 'Orders', 'learnpress' ),
				'callback' => 'learn_press_profile_tab_orders_content'
			)
		);
		$tabs     = apply_filters( 'learn_press_user_profile_tabs', $defaults, $user );

		return $tabs;
	}
}

if ( !function_exists( 'learn_press_output_user_profile_info' ) ) {
	/**
	 * Displaying user info
	 *
	 * @param $user
	 */
	function learn_press_output_user_profile_info( $user, $current, $tabs ) {
		learn_press_get_template( 'profile/info.php', array( 'user' => $user, 'tabs' => $tabs, 'current' => $current ) );
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

if ( !function_exists( 'learn_press_single_quiz_preview_mode' ) ) {
	/**
	 * Output the title of the quiz
	 */
	function learn_press_single_quiz_preview_mode() {
		learn_press_get_template( 'single-quiz/preview-mode.php' );
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

if ( !function_exists( 'learn_press_after_quiz_question_title' ) ) {
	function learn_press_single_quiz_question_answer( $question_id = null, $quiz_id = null ) {
		learn_press_get_template( 'single-quiz/question-answer.php', array( 'question_id' => $question_id, 'quiz_id' => $quiz_id ) );
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
			'course-lesson course-item course-item-' . $lesson_id
		);
		if ( LP()->user->has( 'completed-item', $lesson_id ) ) {
			$classes[] = "item-completed";
		}
		if ( $lesson_id && LP()->course->is( 'current-item', $lesson_id ) ) {
			$classes[] = 'item-current';
		}
		if ( learn_press_is_course() ) {
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
			'course-quiz course-item course-item-' . $quiz_id
		);
		if ( LP()->user->has( 'completed-item', $quiz_id ) ) {
			$classes[] = "item-completed";
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

	$object = null;

	if ( is_int( $post ) )
		$post = get_post( $post );

	if ( !$post ) {
		return $object;
	}

	if ( $post->post_type == LP()->course_post_type ) {
		if ( isset( $GLOBALS['course'] ) ) {
			unset( $GLOBALS['course'] );
		}
		$object = $GLOBALS['course'] = learn_press_get_course( $post );
		LP()->set_object( '_course', $object );
	} elseif ( $post->post_type == LP()->quiz_post_type ) {
		if ( isset( $GLOBALS['quiz'] ) ) {
			unset( $GLOBALS['quiz'] );
		}
		$object = $GLOBALS['quiz'] = learn_press_get_quiz( $post );
		LP()->set_object( '_quiz', $object );
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

	// get all notices added into queue
	$notices = LP_Session::get( 'notices' );
	LP_Session::set( 'notices', null );

	// add new notice and display
	learn_press_add_notice( $message, $type );
	echo learn_press_get_notices( true );

	// store back notices
	LP_Session::set( 'notices', $notices );
}

function learn_press_get_message( $message, $type = 'success' ) {
	ob_start();
	learn_press_display_message( $message, $type );
	$message = ob_get_clean();
	return $message;
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
		global $wp;
		if ( isset( $wp->query_vars['lp-order-received'] ) ) {
			global $post;
			$post->post_title = __( 'Order received', 'learnpress' );
		}
		if ( is_single() ) {
			$user     = LP()->user;
			$redirect = false;
			$item_id  = 0;

			/*if ( is_single() && $post_type == LP()->quiz_post_type ) {
				$user        = learn_press_get_current_user();
				$quiz_id     = get_the_ID();
				$quiz_status = $user->get_quiz_status( $quiz_id );
				if ( $quiz_status == 'started' && learn_press_get_quiz_time_remaining( $user->id, $quiz_id ) == 0 && get_post_meta( $quiz_id, '_lpr_duration', true ) ) {
					$user->finish_quiz( $quiz_id );
					$quiz_status = 'completed';
				}
				$redirect = null;
				if ( learn_press_get_request( 'question' ) && $quiz_status == '' ) {
					$redirect = get_the_permalink( $quiz_id );
				} elseif ( $quiz_status == 'started' ) {
					if ( learn_press_get_request( 'question' ) ) {
					} else {
						$redirect = learn_press_get_user_question_url( $quiz_id );
					}
				} elseif ( $quiz_status == 'completed' && learn_press_get_request( 'question' ) ) {
					$redirect = get_the_permalink( $quiz_id );
				}
				if ( $redirect && !learn_press_is_current_url( $redirect ) ) {
					wp_redirect( $redirect );
					exit();
				}
			}*/
			switch ( get_post_type() ) {
				case LP()->quiz_post_type:
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
				case LP()->course_post_type:
					if ( $item_id = LP()->course->is( 'viewing-item' ) ) {
						if ( !LP()->user->can( 'view-item', $item_id ) ) {
							$redirect = apply_filters( 'learn_press_lesson_access_denied_redirect_permalink', LP()->course->permalink, $item_id, $user->id );
						}
					}
			}

			// prevent loop redirect
			if ( $redirect && !learn_press_is_current_url( $redirect ) ) {
				if ( $item_id && $error_message ) {
					$error_message = apply_filters( 'learn_press_course_item_access_denied_error_message', get_the_title( $item_id ) );
					if ( $error_message !== false ) {
						learn_press_add_notice( $error_message, 'error' );
					}
				}
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
		$template = locate_template( array( "{$slug}-{$name}.php", learn_press_template_path() . "/{$slug}-{$name}.php" ) );
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
		$template = $default_path . $template_name;
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
	return apply_filters( 'learn_press_template_path', 'learnpress' ) . ( $slash ? '/' : '' );
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

if ( !function_exists( 'learn_press_single_quiz_information' ) ) {
	/**
	 *
	 */
	function learn_press_single_quiz_information() {
		learn_press_get_template( 'single-quiz/intro.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_information' ) ) {
	/**
	 *
	 */
	function learn_press_single_quiz_information() {
		learn_press_get_template( 'single-quiz/intro.php' );
	}
}

if ( !function_exists( 'learn_press_single_quiz_sidebar_buttons' ) ) {
	/**
	 *
	 */
	function learn_press_single_quiz_sidebar_buttons() {
		learn_press_get_template( 'single-quiz/sidebar-buttons.php' );
	}
}

if ( !function_exists( 'learn_press_generate_template_information' ) ) {
	function learn_press_generate_template_information( $template_name, $template_path, $located, $args ) {
		$debug = learn_press_get_request( 'debug' );
		if ( $debug == 'on' ) {
			echo "<!-- Template Location:" . str_replace( array( LP_PLUGIN_PATH, ABSPATH ), '', $located ) . " -->";
		}
	}
}

add_filter( 'template_include', 'learn_press_permission_view_quiz', 100 );
function learn_press_permission_view_quiz( $template ) {

	// if is not in single quiz
	if ( !learn_press_is_quiz() ) {
		return $template;
	}
	$user = learn_press_get_current_user();
	// If user haven't got permission
	if ( !current_user_can( 'edit-lp_quiz' ) && !$user->can( 'view-quiz', get_the_ID() ) ) {
		switch ( LP()->settings->get( 'quiz_restrict_access' ) ) {
			case 'custom':
				$template = learn_press_locate_template( 'global/restrict-access.php' );
				break;
			default:
				learn_press_404_page();
		}
	}

	return $template;
}

add_filter( 'template_include', 'learn_press_template_loader' );
function learn_press_template_loader( $template ) {
	global $post;

	$file           = '';
	$theme_template = learn_press_template_path();
	if ( ( $page_id = learn_press_get_page_id( 'taken_course_confirm' ) ) && is_page( $page_id ) ) {
		if ( !learn_press_user_can_view_order( !empty( $_REQUEST['order_id'] ) ? $_REQUEST['order_id'] : 0 ) ) {
			learn_press_404_page();
		}
		global $post;
		$post->post_content = '[learn_press_confirm_order]';
	} elseif ( ( $page_id = learn_press_get_page_id( 'become_teacher_form' ) ) && is_page( $page_id ) ) {

		$post->post_content = '[learn_press_become_teacher_form]';
	} else {
		if ( is_post_type_archive( LP()->course_post_type ) || ( ( $page_id = learn_press_get_page_id( 'courses' ) ) && is_page( $page_id ) ) || ( is_tax( 'course_category' ) ) ) {
			$file   = 'archive-course.php';
			$find[] = $file;
			$find[] = "{$theme_template}/{$file}";
		} else {
			if ( learn_press_is_course() ) {

				$file   = 'single-course.php';
				$find[] = $file;
				$find[] = "{$theme_template}/{$file}";

			} elseif ( learn_press_is_quiz() ) {
				$file   = 'single-quiz.php';
				$find[] = $file;
				$find[] = "{$theme_template}/{$file}";
			}
		}
	}

	if ( $file ) {
		$template = locate_template( array_unique( $find ) );
		if ( !$template ) {
			$template = learn_press_plugin_path( 'templates/' ) . $file;
		}
	}

	return $template;
}