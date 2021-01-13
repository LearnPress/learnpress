<?php
/**
 * Handle renamed/removed hooks
 */
global $lp_map_deprecated_filters;

$lp_map_deprecated_filters = array(
	'learn_press_register_add_ons' => 'learn_press_loaded',
);

foreach ( $lp_map_deprecated_filters as $old => $new ) {
	add_filter( $old, 'lp_deprecated_filter_mapping', 9999999 );
}

function lp_deprecated_filter_mapping( $data, $arg_1 = '', $arg_2 = '', $arg_3 = '' ) {
	global $lp_map_deprecated_filters, $wp_filter;
	$filter = current_filter();
	if ( ! empty( $wp_filter[ $filter ] ) && count( $wp_filter[ $filter ] ) > 1 ) {
		_deprecated_function( 'The ' . $filter . ' hook', '1.0', $lp_map_deprecated_filters[ $filter ] );
	}

	return $data;
}

function learn_press_text_image( $text = null, $args = array() ) {
	_deprecated_function( __FUNCTION__, '1.0' );
	$width      = 200;
	$height     = 150;
	$font_size  = 1;
	$background = 'FFFFFF';
	$color      = '000000';
	$padding    = 20;

	extract( $args );

	// Output to browser
	if ( empty( $_REQUEST['debug'] ) ) {
		header( 'Content-Type: image/png' );
	}
	/*
	$uniqid = md5( serialize( array( 'width' => $width, 'height' => $height, 'text' => $text, 'background' => $background, 'color' => $color ) ) );
	@mkdir( LP_PLUGIN_PATH . '/cache' );
	$cache = LP_PLUGIN_PATH . '/cache/' . $uniqid . '.cache';
	if( file_exists( $cache ) ){
		readfile( $cache );
		die();
	}*/

	$im = imagecreatetruecolor( $width, $height );

	list( $r, $g, $b ) = sscanf( "#{$background}", '#%02x%02x%02x' );
	$background        = imagecolorallocate( $im, $r, $g, $b );

	list( $r, $g, $b ) = sscanf( "#{$color}", '#%02x%02x%02x' );
	$color             = imagecolorallocate( $im, $r, $g, $b );

	// Set the background to be white
	imagefilledrectangle( $im, 0, 0, $width, $height, $background );

	// Path to our font file
	$font = LP_PLUGIN_PATH . '/assets/fonts/Sumana-Regular.ttf';
	$x    = $width;
	$loop = 0;
	do {
		// First we create our bounding box for the first text
		$bbox = imagettfbbox( $font_size, 0, $font, $text );
		// This is our cordinates for X and Y
		$x = $bbox[0] + ( imagesx( $im ) / 2 ) - ( $bbox[4] / 2 );
		$y = $bbox[1] + ( imagesy( $im ) / 2 ) - ( $bbox[5] / 2 );
		$font_size ++;
		if ( $loop ++ > 100 ) {
			break;
		}
	} while ( $x > $padding );
	// Write it
	imagettftext( $im, $font_size, 0, $x - 5, $y, $color, $font, $text );
	imagepng( $im );
	// readfile( $cache );
	imagedestroy( $im );
}

/**
 * Get all lessons in a course
 *
 * @param $course_id
 *
 * @return array
 */
function learn_press_get_lessons( $course_id ) {
	_deprecated_function( __FUNCTION__, '1.0' );
	$lessons    = array();
	$curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
	if ( $curriculum ) {
		foreach ( $curriculum as $lesson_quiz_s ) {
			if ( array_key_exists( 'lesson_quiz', $lesson_quiz_s ) ) {
				foreach ( $lesson_quiz_s['lesson_quiz'] as $lesson_quiz ) {
					if ( get_post_type( $lesson_quiz ) == LP_LESSON_CPT ) {
						$lessons[] = $lesson_quiz;
					}
				}
			}
		}
	}

	return $lessons;
}

/**
 * @param $course_id
 *
 * @return array
 */
function learn_press_get_course_quizzes( $course_id ) {
	_deprecated_function( __FUNCTION__, '1.0' );
	$quizzes    = array();
	$curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
	if ( $curriculum ) {
		foreach ( $curriculum as $lesson_quiz_s ) {
			if ( array_key_exists( 'lesson_quiz', $lesson_quiz_s ) ) {
				foreach ( $lesson_quiz_s['lesson_quiz'] as $lesson_quiz ) {
					if ( get_post_type( $lesson_quiz ) == LP_QUIZ_CPT ) {
						$quizzes[] = $lesson_quiz;
					}
				}
			}
		}
	}

	return $quizzes;
}

// Deprecated template functions


if ( ! function_exists( 'learn_press_course_content_lesson' ) ) {
	/**
	 * Display course description
	 */
	function learn_press_course_content_lesson() {
		learn_press_get_template( 'content-lesson/summary.php' );
	}
}

if ( ! function_exists( 'learn_press_course_lesson_data' ) ) {
	/**
	 * Display course lesson description
	 */
	function learn_press_course_lesson_data() {
		$course = LP()->course;
		if ( ! $course ) {
			return;
		}
		if ( ! ( $lesson = $course->current_lesson ) ) {
			return;
		}
		?>
		<input type="hidden" name="learn-press-lesson-viewing" value="<?php echo $lesson->id; ?>"/>
		<?php
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

if ( ! function_exists( '_learn_press_default_course_tabs' ) ) {

	/**
	 * Add default tabs to course
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	function _learn_press_default_course_tabs( $tabs = array() ) {
		_deprecated_function( __FUNCTION__, '3.0.0', 'learn_press_get_course_tabs' );

		return learn_press_get_course_tabs();
	}
}


function learn_press_sanitize_json( $string ) {

	echo json_encode( $string );

	return $string;
}


function learn_press_get_subtabs_course() {
	$subtabs = array(
		'all'       => __( 'All', 'learnpress' ),
		'learning'  => __( 'Learning', 'learnpress' ),
		'purchased' => __( 'Purchased', 'learnpress' ),
		'finished'  => __( 'Finished', 'learnpress' ),
		'own'       => __( 'Owned', 'learnpress' ),
	);

	$subtabs = apply_filters( 'learn_press_profile_tab_courses_subtabs', $subtabs );

	return $subtabs;
}


// -------------------------------
// Validation Data Settings Page Before Save
add_filter( 'learn_press_update_option_value', 'learn_press_validation_data_before_save', 10, 2 );

function learn_press_validation_data_before_save( $value = '', $name = '' ) {
	if ( $name === 'learn_press_profile_endpoints' ) {

		if ( empty( $value['profile-courses'] ) ) {
			$value['profile-courses'] = 'courses';
		}
	}

	return $value;
}


// Show filters for students list
function learn_press_get_students_list_filter() {
	$filter = array(
		'all'         => esc_html__( 'All', 'learnpress' ),
		'in-progress' => esc_html__( 'In Progress', 'learnpress' ),
		'finished'    => esc_html__( 'Finished', 'learnpress' ),
	);

	return apply_filters( 'learn_press_get_students_list_filter', $filter );
}


function learn_press_get_request_args( $args = array() ) {
	$request = array();
	if ( $args ) {
		foreach ( $args as $key ) {
			$request[] = array_key_exists( $key, $_REQUEST ) ? $_REQUEST[ $key ] : false;
		}
	}

	return $request;
}


/**
 * Redirect to question if user access to a quiz that user has started
 *
 * @param string
 *
 * @return string
 */
function learn_press_redirect_to_question( $template ) {
	global $post_type;
	if ( is_single() && $post_type == LP_QUIZ_CPT ) {
		$user        = learn_press_get_current_user();
		$quiz_id     = get_the_ID();
		$quiz_status = $user->get_quiz_status( $quiz_id );
		if ( $quiz_status == 'started' && learn_press_get_quiz_time_remaining( $user->get_id(), $quiz_id ) == 0 && get_post_meta( $quiz_id, '_lpr_duration', true ) ) {
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
		if ( $redirect && ! learn_press_is_current_url( $redirect ) ) {
			wp_redirect( $redirect );
			exit();
		}
	}

	return $template;
}


function learn_press_output_question_nonce( $question ) {
	printf( '<input type="hidden" name="update-question-nonce" value="%s" />', wp_create_nonce( 'current-question-nonce-' . $question->id ) );
}

add_action( 'learn_press_after_question_wrap', 'learn_press_output_question_nonce' );


if ( ! function_exists( 'learn_press_course_nav_items' ) ) {
	/**
	 * Displaying course items navigation
	 *
	 * @param null $item_id
	 * @param null $course_id
	 */
	function learn_press_course_nav_items( $item_id = null, $course_id = null ) {
		learn_press_get_template(
			'single-course/nav-items.php',
			array(
				'course_id'    => $course_id,
				'item_id'      => $item_id,
				'content_only' => learn_press_is_content_item_only(),
			)
		);
	}
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
		LP()->template( 'course' )->course_purchase_button();
	}
}

if ( ! function_exists( 'learn_press_course_enroll_button' ) ) {
	/**
	 * Enroll course button.
	 */
	function learn_press_course_enroll_button() {
		_deprecated_function( __FUNCTION__, '3.3.0' );
		LP()->template( 'course' )->course_enroll_button();
	}
}


if ( ! function_exists( 'learn_press_course_retake_button' ) ) {

	/**
	 * Retake course button
	 */
	function learn_press_course_retake_button() {
		_deprecated_function( __FUNCTION__, '3.3.0' );
		LP()->template( 'course' )->func( 'course_retake_button' );
	}
}

if ( ! function_exists( 'learn_press_course_continue_button' ) ) {

	/**
	 * Retake course button
	 */
	function learn_press_course_continue_button() {
		_deprecated_function( __FUNCTION__, '4.0.0' );
		LP()->template( 'course' )->func( 'course_continue_button' );
	}
}


if ( ! function_exists( 'learn_press_course_finish_button' ) ) {

	/**
	 * Retake course button
	 *
	 * @deprecated 3.3.0
	 */
	function learn_press_course_finish_button() {
		learn_press_add_message(
			'Deprecated: ' . __FUNCTION__ . ' is <strong>deprecated</strong> since version 3.3.0 with no alternative available. Please update LP v4.0.0',
			'warning'
		);

		LP()->template( 'course' )->func( 'course_finish_button' );
	}
}

if ( ! function_exists( 'learn_press_course_external_button' ) ) {

	/**
	 * Retake course button
	 */
	function learn_press_course_external_button() {
		LP()->template( 'course' )->func( 'course_external_button' );
	}
}


if ( ! function_exists( 'learn_press_curriculum_section_title' ) ) {

	/**
	 * Section title
	 *
	 * @param LP_Course_Section $section
	 *
	 * @hooked learn-press/section-summary
	 */
	function learn_press_curriculum_section_title( $section ) {
		learn_press_get_template( 'single-course/section/title.php', array( 'section' => $section ) );
	}
}

if ( ! function_exists( 'learn_press_curriculum_section_content' ) ) {

	/**
	 * Section content
	 *
	 * @param LP_Course_Section $section
	 *
	 * @hooked learn-press/section-summary
	 */
	function learn_press_curriculum_section_content( $section ) {
		learn_press_get_template( 'single-course/section/content.php', array( 'section' => $section ) );
	}
}

if ( ! function_exists( 'learn_press_checkout_form_login' ) ) {

	/**
	 * Output login form before checkout form if user is not logged in
	 *
	 * @hooked learn-press/before-checkout-form
	 */
	function learn_press_checkout_form_login() {

		if ( ! LP()->checkout()->is_enable_login() ) {
			return;
		}

		learn_press_get_template( 'checkout/form-login.php' );
	}
}

if ( ! function_exists( 'learn_press_checkout_form_register' ) ) {

	/**
	 * Output register form before checkout form if user is not logged in.
	 *
	 * @hooked learn-press/before-checkout-form
	 */
	function learn_press_checkout_form_register() {

		if ( ! LP()->checkout()->is_enable_register() ) {
			return;
		}

		learn_press_get_template( 'checkout/form-register.php' );
	}
}

if ( ! function_exists( 'learn_press_order_review' ) ) {
	/**
	 * Output order details
	 *
	 * @hooked learn-press/checkout-order-review
	 */
	function learn_press_order_review() {
		learn_press_get_template( 'checkout/review-order.php' );
	}
}

if ( ! function_exists( 'learn_press_order_payment' ) ) {
	/**
	 * Output payment methods
	 *
	 * @hooked learn-press/checkout-order-review
	 */
	function learn_press_order_payment() {
		$available_gateways = LP_Gateways::instance()->get_available_payment_gateways();

		learn_press_get_template( 'checkout/payment.php', array( 'available_gateways' => $available_gateways ) );
	}
}

if ( ! function_exists( 'learn_press_order_guest_email' ) ) {
	/**
	 * Output payment methods
	 *
	 * @hooked learn-press/checkout-order-review
	 */
	function learn_press_order_guest_email() {
		$checkout = LP()->checkout();
		if ( $checkout->is_enable_guest_checkout() && ! is_user_logged_in() ) {
			learn_press_get_template( 'checkout/guest-email.php' );
		}
	}
}

if ( ! function_exists( 'learn_press_order_comment' ) ) {
	/**
	 * Output order comment input
	 *
	 * @hooked learn-press/checkout-order-review
	 */
	function learn_press_order_comment() {
		learn_press_get_template( 'checkout/order-comment.php' );
	}
}

if ( ! function_exists( 'learn_press_user_profile_header' ) ) {
	/**
	 * Output order comment input
	 *
	 * @hooked learn-press/before-user-profile
	 */
	function learn_press_user_profile_header( $user ) {
		$profile = LP_Global::profile();

		if ( $profile->get_user()->is_guest() ) {
			return;
		}

		learn_press_get_template( 'profile/profile-cover.php', array( 'user' => $user ) );
	}
}

if ( ! function_exists( 'learn_press_user_profile_content' ) ) {
	/**
	 * Output order comment input
	 *
	 * @hooked learn-press/user-profile
	 */
	function learn_press_user_profile_content( $user ) {
		$profile = LP_Global::profile();

		if ( $profile->get_user()->is_guest() ) {
			return;
		}

		learn_press_get_template( 'profile/content.php', array( 'user' => $user ) );
	}
}

if ( ! function_exists( 'learn_press_user_profile_footer' ) ) {
	/**
	 * Output order comment input
	 *
	 * @hooked learn-press/after-user-profile
	 */
	function learn_press_user_profile_footer( $user ) {
		// learn_press_get_template( 'profile/footer.php', array( 'user' => $user ) );
	}
}

if ( ! function_exists( 'learn_press_user_profile_tabs' ) ) {
	/**
	 * Get tabs for user profile
	 *
	 * @param $user
	 */
	function learn_press_user_profile_tabs( $user = null ) {
		$profile = LP_Global::profile();

		if ( $profile->get_user()->is_guest() ) {
			return;
		}

		learn_press_get_template( 'profile/tabs.php', array( 'user' => $user ) );
	}
}

if ( ! function_exists( 'learn_press_single_course_summary' ) ) {
	/**
	 * Display content of single course summary
	 */
	function learn_press_single_course_summary() {
		if ( learn_press_is_learning_course() ) {
			learn_press_get_template( 'single-course/content-learning.php' );
		} else {
			learn_press_get_template( 'single-course/content-landing.php' );
		}
	}
}

if ( ! function_exists( 'learn_press_course_price' ) ) {
	/**
	 * Display course price.
	 */
	function learn_press_course_price() {
		$user   = LP_Global::user();
		$course = LP_Global::course();

		if ( $user && $user->has_enrolled_course( $course->get_id() ) ) {
			return;
		}

		learn_press_get_template( 'single-course/price.php' );
	}
}

if ( ! function_exists( 'learn_press_course_meta_start_wrapper' ) ) {
	/**
	 * Output the thumbnail of the course within loop
	 */
	function learn_press_course_meta_start_wrapper() {
		learn_press_get_template( 'global/course-meta-start.php' );
	}
}

if ( ! function_exists( 'learn_press_course_meta_end_wrapper' ) ) {
	/**
	 * Output the thumbnail of the course within loop
	 */
	function learn_press_course_meta_end_wrapper() {
		learn_press_get_template( 'global/course-meta-end.php' );
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

if ( ! function_exists( 'learn_press_course_status' ) ) {
	/**
	 * Display the title for single course
	 */
	function learn_press_course_status() {
		learn_press_get_template( 'single-course/status.php' );
	}
}

if ( ! function_exists( 'learn_press_courses_loop_item_instructor' ) ) {
	/**
	 * Output the instructor of the course within loop
	 */
	function learn_press_courses_loop_item_instructor() {
		learn_press_get_template( 'loop/course/instructor.php' );
	}
}

if ( ! function_exists( 'learn_press_course_tabs' ) ) {
	/*
	 * Output course tabs
	 */

	function learn_press_course_tabs() {
		learn_press_get_template( 'single-course/tabs/tabs.php' );
	}
}

if ( ! function_exists( 'learn_press_course_item_content' ) ) {
	/**
	 * Get course item content template.
	 *
	 * @since 3.0.0
	 */
	function learn_press_course_item_content() {
		global $lp_course, $lp_course_item;

		$item = LP_Global::course_item();

		/**
		 * Fix only for WPBakery load style inline
		 * custom CSS is provided, load inline style.
		 *
		 * @editor tuanta
		 * @since 3.2.8.1
		 */
		$shortcodes_custom_css = get_post_meta( $item->get_id(), '_wpb_shortcodes_custom_css', true );

		if ( ! empty( $shortcodes_custom_css ) ) {
			$shortcodes_custom_css = strip_tags( $shortcodes_custom_css );
			echo '<style type="text/css" data-type="vc_shortcodes-custom-css">';
			echo $shortcodes_custom_css;
			echo '</style>';
		}
		// End

		$item_template_name = learn_press_locate_template( 'single-course/content-item-' . $item->get_item_type() . '.php' );

		if ( file_exists( $item_template_name ) ) {
			learn_press_get_template( 'single-course/content-item-' . $item->get_item_type() . '.php' );
		}
	}
}


if ( ! function_exists( 'learn_press_content_item_quiz_title' ) ) {
	function learn_press_content_item_quiz_title() {
		learn_press_get_template( 'content-quiz/title.php' );
	}
}

if ( ! function_exists( 'learn_press_content_item_quiz_intro' ) ) {
	function learn_press_content_item_quiz_intro() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
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
}

if ( ! function_exists( 'learn_press_content_item_summary_quiz_content' ) ) {

	function learn_press_content_item_summary_quiz_content() {
		$item = LP_Global::course_item();

		if ( ! $item->get_viewing_question() ) {
			learn_press_get_template( 'content-quiz/description.php' );
		}
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_question_title' ) ) {

	function learn_press_content_item_summary_question_title() {
		$quiz = LP_Global::course_item_quiz();

		if ( $question = $quiz->get_viewing_question() ) {
			learn_press_get_template( 'content-question/title.php' );
		}
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_quiz_progress' ) ) {

	function learn_press_content_item_summary_quiz_progress() {
		$course = LP_Global::course();
		$quiz   = LP_Global::course_item_quiz();
		$user   = LP_Global::user();

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
}

if ( ! function_exists( 'learn_press_content_item_summary_quiz_countdown' ) ) {

	function learn_press_content_item_summary_quiz_countdown() {
		$quiz = LP_Global::course_item_quiz();

		if ( $question = $quiz->get_viewing_question() ) {
			learn_press_get_template( 'content-quiz/countdown.php' );
		}
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_quiz_result' ) ) {

	function learn_press_content_item_summary_quiz_result() {
		$quiz = LP_Global::course_item_quiz();
		$user = LP_Global::user();
		if ( ! $user->has_completed_quiz( $quiz->get_id(), get_the_ID() ) ) {
			return;
		}
		learn_press_get_template( 'content-quiz/result.php' );
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_quiz_question' ) ) {

	function learn_press_content_item_summary_quiz_question() {
		$quiz = LP_Global::course_item_quiz();

		if ( $question = $quiz->get_viewing_question() ) {
			learn_press_get_template( 'content-question/content.php' );
		}
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_question_content' ) ) {

	function learn_press_content_item_summary_question_content() {
		$quiz = LP_Global::course_item_quiz();

		if ( $question = $quiz->get_viewing_question() ) {
			learn_press_get_template( 'content-question/description.php' );
		}
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_quiz_buttons' ) ) {

	function learn_press_content_item_summary_quiz_buttons() {
		_deprecated_function( __FUNCTION__, '3.3.0' );
		learn_press_get_template( 'content-quiz/buttons.php' );
	}
}

if ( ! function_exists( 'learn_press_profile_recover_order_form' ) ) {
	function learn_press_profile_recover_order_form( $order ) {
		learn_press_get_template( 'profile/tabs/orders/recover-order.php', array( 'order' => $order ) );
	}
}


if ( ! function_exists( 'learn_press_wrapper_start' ) ) {
	/**
	 * Wrapper Start
	 */
	function learn_press_wrapper_start() {
		learn_press_get_template( 'global/before-main-content.php' );
	}
}

if ( ! function_exists( 'learn_press_wrapper_end' ) ) {
	/**
	 * wrapper end
	 */
	function learn_press_wrapper_end() {
		learn_press_get_template( 'global/after-main-content.php' );
	}
}

if ( ! function_exists( 'learn_press_courses_loop_item_thumbnail' ) ) {
	/**
	 * Output the thumbnail of the course within loop
	 */
	function learn_press_courses_loop_item_thumbnail() {
		learn_press_get_template( 'loop/course/thumbnail.php' );
	}
}



if ( ! function_exists( 'learn_press_courses_loop_item_title' ) ) {
	/**
	 * Output the title of the course within loop
	 */
	function learn_press_courses_loop_item_title() {
		learn_press_get_template( 'loop/course/title.php' );
	}
}

if ( ! function_exists( 'learn_press_courses_loop_item_begin_meta' ) ) {
	/**
	 * Output the excerpt of the course within loop
	 */
	function learn_press_courses_loop_item_begin_meta() {
		learn_press_get_template( 'loop/course/meta-begin.php' );
	}
}

if ( ! function_exists( 'learn_press_courses_loop_item_end_meta' ) ) {
	/**
	 * Output the excerpt of the course within loop
	 */
	function learn_press_courses_loop_item_end_meta() {
		learn_press_get_template( 'loop/course/meta-end.php' );
	}
}

if ( ! function_exists( 'learn_press_courses_loop_item_introduce' ) ) {
	/**
	 * Output the excerpt of the course within loop
	 */
	function learn_press_courses_loop_item_introduce() {
		learn_press_get_template( 'loop/course/introduce.php' );
	}
}

if ( ! function_exists( 'learn_press_courses_loop_item_price' ) ) {
	/**
	 * Output the price of the course within loop
	 */
	function learn_press_courses_loop_item_price() {
		learn_press_get_template( 'loop/course/price.php' );
	}
}

if ( ! function_exists( 'learn_press_begin_courses_loop' ) ) {
	/**
	 * Output the price of the course within loop
	 */
	function learn_press_begin_courses_loop() {
		learn_press_get_template( 'loop/course/loop-begin.php' );
	}
}

if ( ! function_exists( 'learn_press_end_courses_loop' ) ) {
	/**
	 * Output the price of the course within loop
	 */
	function learn_press_end_courses_loop() {
		learn_press_get_template( 'loop/course/loop-end.php' );
	}
}

if ( ! function_exists( 'learn_press_courses_loop_item_students' ) ) {
	/**
	 * Output the students of the course within loop
	 */
	function learn_press_courses_loop_item_students() {
		echo '<div class="clearfix"></div>';
		learn_press_get_template( 'loop/course/students.php' );
	}
}

if ( ! function_exists( 'learn_press_courses_pagination' ) ) {
	/**
	 * Output the pagination of archive courses
	 */
	function learn_press_courses_pagination() {
		learn_press_get_template( 'loop/course/pagination.php' );
	}
}

if ( ! function_exists( 'learn_press_output_single_course_learning_summary' ) ) {
	/**
	 * Output the content of learning course content
	 */
	function learn_press_output_single_course_learning_summary() {
		learn_press_get_template( 'single-course/content-learning.php' );
	}
}


if ( ! function_exists( 'learn_press_output_single_course_landing_summary' ) ) {
	/**
	 * Output the content of landing course content
	 */
	function learn_press_output_single_course_landing_summary() {
		learn_press_get_template( 'single-course/content-landing.php' );
	}
}



if ( ! function_exists( 'learn_press_course_title' ) ) {
	/**
	 * Display the title for single course
	 */
	function learn_press_course_title() {
		learn_press_get_template( 'single-course/title.php' );
	}
}

if ( ! function_exists( 'learn_press_course_progress' ) ) {
	/**
	 * Display course curriculum
	 */
	function learn_press_course_progress() {
		learn_press_get_template( 'single-course/progress.php' );
	}
}

if ( ! function_exists( 'learn_press_course_curriculum' ) ) {
	/**
	 * Display course curriculum
	 */
	function learn_press_course_curriculum() {
		// learn_press_get_template( 'single-course/curriculum.php' );
	}
}

if ( ! function_exists( 'learn_press_course_categories' ) ) {
	/**
	 * Display course categories
	 */
	function learn_press_course_categories() {
		// learn_press_get_template( 'single-course/categories.php' );
	}
}

if ( ! function_exists( 'learn_press_course_tags' ) ) {
	/**
	 * Display course tags
	 */
	function learn_press_course_tags() {
		learn_press_get_template( 'single-course/tags.php' );
	}
}

if ( ! function_exists( 'learn_press_course_instructor' ) ) {
	/**
	 * Display course instructor
	 */
	function learn_press_course_instructor() {
		learn_press_get_template( 'single-course/instructor.php' );
	}
}

if ( ! function_exists( 'learn_press_course_buttons' ) ) {
	/**
	 * Display course retake button
	 */
	function learn_press_course_buttons() {
		// learn_press_get_template( 'single-course/buttons.php' );
	}
}


if ( ! function_exists( 'learn_press_course_thumbnail' ) ) {
	/**
	 * Display Course Thumbnail
	 */
	function learn_press_course_thumbnail() {
		learn_press_get_template( 'single-course/thumbnail.php' );
	}
}

if ( ! function_exists( 'learn_press_single_course_description' ) ) {
	/**
	 * Display course description
	 */
	function learn_press_single_course_description() {
		learn_press_get_template( 'single-course/description.php' );
	}
}

if ( ! function_exists( 'learn_press_single_course_lesson_content' ) ) {
	/**
	 * Display lesson content
	 */
	function learn_press_single_course_content_lesson() {
		// learn_press_get_template( 'single-course/content-lesson.php' );
	}
}

if ( ! function_exists( 'learn_press_single_course_content_item' ) ) {
	/**
	 * Display lesson content
	 */
	function learn_press_single_course_content_item() {
		learn_press_get_template( 'single-course/content-item.php' );
	}
}


if ( ! function_exists( 'learn_press_checkout_user_form' ) ) {
	/**
	 * Output login/register form before order review if user is not logged in
	 */
	function learn_press_checkout_user_form() {
		// learn_press_get_template( 'checkout/user-form.php' );
	}
}

if ( ! function_exists( 'learn_press_checkout_user_form_login' ) ) {
	/**
	 * Output login form before order review if user is not logged in
	 */
	function learn_press_checkout_user_form_login() {
		learn_press_get_template( 'checkout/form-login.php' );
	}
}

if ( ! function_exists( 'learn_press_checkout_user_form_register' ) ) {
	/**
	 * Output register form before order review if user is not logged in
	 */
	function learn_press_checkout_user_form_register() {
		learn_press_get_template( 'checkout/form-register.php' );
	}
}

if ( ! function_exists( 'learn_press_checkout_user_logged_in' ) ) {
	/**
	 * Output message before order review if user is logged in
	 */
	function learn_press_checkout_user_logged_in() {
		learn_press_get_template( 'checkout/form-logged-in.php' );
	}
}


if ( ! function_exists( 'learn_press_after_profile_tab_loop_course' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_after_profile_tab_loop_course( $user, $course_id ) {

		$args = array(
			'user'      => $user,
			'course_id' => $course_id,
		);
		learn_press_get_template( 'profile/tabs/courses/progress.php', $args );

	}
}


if ( ! function_exists( 'learn_press_output_user_profile_info' ) ) {
	/**
	 * Displaying user info
	 *
	 * @param $user
	 */
	function learn_press_output_user_profile_info( $user, $current, $tabs ) {
		learn_press_get_template(
			'profile/info.php',
			array(
				'user'    => $user,
				'tabs'    => $tabs,
				'current' => $current,
			)
		);
	}
}

/* QUIZ TEMPLATES */
if ( ! function_exists( 'learn_press_single_quiz_title' ) ) {
	/**
	 * Output the title of the quiz
	 */
	function learn_press_single_quiz_title() {
		learn_press_get_template( 'content-quiz/title.php' );
	}
}


if ( ! function_exists( 'learn_press_course_loop_item_buttons' ) ) {
	/**
	 * @deprecated
	 *
	 * @since 3.3.0
	 *
	 * @return bool
	 */
	function learn_press_course_loop_item_buttons() {

		return false;
		// learn_press_get_template( 'single-course/buttons.php' );
	}
}


if ( ! function_exists( 'learn_press_message' ) ) {
	/**
	 * Template to display the messages
	 *
	 * @param        $content
	 * @param string  $type
	 */
	function learn_press_message( $content, $type = 'message' ) {
		learn_press_get_template(
			'global/message.php',
			array(
				'type'    => $type,
				'content' => $content,
			)
		);
	}
}

if ( ! function_exists( 'learn_press_course_overview_tab' ) ) {
	/**
	 * Output course overview
	 *
	 * @since 1.1
	 */
	function learn_press_course_overview_tab() {
		learn_press_get_template( 'single-course/tabs/overview.php' );
	}
}

if ( ! function_exists( 'learn_press_course_curriculum_tab' ) ) {
	/**
	 * Output course curriculum
	 *
	 * @since 1.1
	 */
	function learn_press_course_curriculum_tab() {
		learn_press_get_template( 'single-course/tabs/curriculum.php' );
	}
}

if ( ! function_exists( 'learn_press_course_instructor_tab' ) ) {
	/**
	 * Output course curriculum
	 *
	 * @since 1.1
	 */
	function learn_press_course_instructor_tab() {
		learn_press_get_template( 'single-course/tabs/instructor.php' );
	}
}


if ( ! function_exists( 'learn_press_content_item_header' ) ) {
	function learn_press_content_item_header() {
		learn_press_get_template( 'single-course/content-item/header.php' );
	}
}

if ( ! function_exists( 'learn_press_content_item_footer' ) ) {
	function learn_press_content_item_footer() {
		learn_press_get_template( 'single-course/content-item/footer.php' );
	}
}


if ( ! function_exists( 'learn_press_profile_mobile_menu' ) ) {
	function learn_press_profile_mobile_menu() {
		learn_press_get_template( 'profile/mobile-menu.php' );
	}
}

/**
 * @deprecated
 *
 * @return bool
 */
function learn_press_is_content_only() {
	global $wp;

	return ! empty( $wp->query_vars['content-item-only'] );
}

if ( ! function_exists( 'learn_press_profile_localize_script' ) ) {

	/**
	 * @deprecated
	 *
	 * Translate javascript text
	 */
	function learn_press_profile_localize_script( $assets ) {
		$translate = array(
			'confirm_cancel_order' => array(
				'message' => __( 'Are you sure you want to cancel order?', 'learnpress' ),
				'title'   => __( 'Cancel Order', 'learnpress' ),
			),
		);
		// LP_Assets::add_localize( $translate );
	}
}
add_action( 'learn_press_enqueue_scripts', 'learn_press_profile_localize_script' );


/**
 * Redirect back to course if the order have one course in that
 *
 * @param $results
 * @param $order_id
 *
 * @return mixed
 */
function _learn_press_checkout_success_result( $results, $order_id ) {
	if ( $results['result'] == 'success' ) {
		if ( $order = learn_press_get_order( $order_id ) ) {
			$items              = $order->get_items();
			$enrolled_course_id = learn_press_auto_enroll_user_to_courses( $order_id );
			$course_id          = 0;
			if ( sizeof( $items ) == 1 ) {
				$item                = reset( $items );
				$course_id           = $item['course_id'];
				$results['redirect'] = get_the_permalink( $course_id );
			}
			if ( ! $course_id ) {
				$course_id = $enrolled_course_id;
			}

			if ( $course = learn_press_get_course( $course_id ) ) {
				learn_press_add_message( sprintf( __( 'Congrats! You\'ve enrolled the course "%s".', 'learnpress' ), $course->get_title() ) );
			}
		}
	}

	return $results;
}

if ( ! function_exists( 'learn_press_content_item_summary_question_hint' ) ) {

	/**
	 * Render content if quiz question.
	 */
	function learn_press_content_item_summary_question_hint() {
		$quiz = LP_Global::course_item_quiz();
		if ( $question = $quiz->get_viewing_question() ) {
			$course      = LP_Global::course();
			$user        = LP_Global::user();
			$course_data = $user->get_course_data( $course->get_id() );
			$user_quiz   = $course_data->get_item_quiz( $quiz->get_id() );

			if ( ! $question->get_hint() || ! $user_quiz->has_hinted_question( $question->get_id() ) || $user_quiz->has_checked_question( $question->get_id() ) ) {
				return;
			}

			learn_press_get_template( 'content-question/hint.php', array( 'question' => $question ) );
		}

	}
}

if ( ! function_exists( 'learn_press_content_item_summary_questions' ) ) {

	/**
	 * Render content if quiz question.
	 */
	function learn_press_content_item_summary_questions() {
		return;
		$quiz = LP_Global::course_item_quiz();
		if ( $questions = $quiz->get_questions() ) {
			$course      = LP_Global::course();
			$user        = LP_Global::user();
			$course_data = $user->get_course_data( $course->get_id() );
			$quiz_data   = $course_data->get_item_quiz( $quiz->get_id() );
			global $lp_quiz_question;
			foreach ( $questions as $question_id ) {
				$question         = LP_Question::get_question( $question_id );
				$lp_quiz_question = $question;

				learn_press_get_template( 'content-question/title.php' );
				learn_press_get_template( 'content-question/description.php' );
				$question->render( $quiz_data->get_question_answer( $question->get_id() ) );
				// learn_press_get_template('single-course/content-item-lp_quiz.php');
			}
		}
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_question_numbers' ) ) {

	function learn_press_content_item_summary_question_numbers() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
		$quiz   = LP_Global::course_item_quiz();

		if ( ! $quiz->get_show_hide_question() ) {
			return;
		}

		if ( ! $user->has_quiz_status( array( 'started', 'completed' ), $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( ! $quiz->get_viewing_question() ) {
			return;
		}
		learn_press_get_template( 'content-quiz/question-numbers.php' );
	}
}


if ( ! function_exists( 'learn_press_quiz_nav_buttons' ) ) {

	function learn_press_quiz_nav_buttons() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
		$quiz   = LP_Global::course_item_quiz();

		if ( ! $user->has_quiz_status( array( 'started', 'completed' ), $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( ! $quiz->get_viewing_question() ) {
			return;
		}

		learn_press_get_template( 'content-quiz/buttons/nav.php' );
	}
}

if ( ! function_exists( 'learn_press_quiz_start_button' ) ) {

	function learn_press_quiz_start_button() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
		$quiz   = LP_Global::course_item_quiz();

		if ( $user->has_course_status( $course->get_id(), array( 'finished' ) ) || $user->has_quiz_status(
			array(
				'started',
				'completed',
			),
			$quiz->get_id(),
			$course->get_id()
		)
		) {
			return;
		}

		if ( ! $user->has_course_status( $course->get_id(), array( learn_press_user_item_in_progress_slug(), /* deprecated */ 'enrolled' ) ) && $course->is_required_enroll() && ! $quiz->get_preview() ) {
			return;
		}
		learn_press_get_template( 'content-quiz/buttons/start.php' );
	}
}

if ( ! function_exists( 'learn_press_quiz_continue_button' ) ) {

	function learn_press_quiz_continue_button() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
		$quiz   = LP_Global::course_item_quiz();

		if ( ! $user->has_quiz_status( 'started', $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( $quiz->get_viewing_question() ) {
			return;
		}

		learn_press_get_template( 'content-quiz/buttons/continue.php' );
	}
}

if ( ! function_exists( 'learn_press_quiz_complete_button' ) ) {

	function learn_press_quiz_complete_button() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
		$quiz   = LP_Global::course_item_quiz();

		if ( $user->has_course_status( $course->get_id(), array( 'finished' ) ) || ! $user->has_quiz_status( 'started', $quiz->get_id(), $course->get_id() ) ) {
			return;
		}
		learn_press_get_template( 'content-quiz/buttons/complete.php' );
	}
}

if ( ! function_exists( 'learn_press_quiz_redo_button' ) ) {

	function learn_press_quiz_redo_button() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
		$quiz   = LP_Global::course_item_quiz();

		if ( ! $user->has_quiz_status( 'completed', $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( $user->has_course_status( $course->get_id(), array( 'finished' ) ) || ! $user->can_retake_quiz( $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		learn_press_get_template( 'content-quiz/buttons/redo.php' );
	}
}

if ( ! function_exists( 'learn_press_quiz_result_button' ) ) {

	function learn_press_quiz_result_button() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
		$quiz   = LP_Global::course_item_quiz();

		if ( ! $user->has_quiz_status( 'completed', $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( LP_Global::quiz_question() ) {
			return;
		}

		if ( ! $quiz->get_review_questions() ) {
			return;
		}

		learn_press_get_template( 'content-quiz/buttons/review.php' );
	}
}

if ( ! function_exists( 'learn_press_quiz_summary_button' ) ) {

	function learn_press_quiz_summary_button() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
		$quiz   = LP_Global::course_item_quiz();

		if ( ! $user->has_quiz_status( 'completed', $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( ! learn_press_is_review_questions() ) {
			return;
		}

		learn_press_get_template( 'content-quiz/buttons/summary.php' );
	}
}

if ( ! function_exists( 'learn_press_quiz_check_button' ) ) {

	function learn_press_quiz_check_button() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
		$quiz   = LP_Global::course_item_quiz();

		if ( ! $quiz->is_viewing_question() ) {
			return;
		}

		if ( ! $user->can_check_answer( $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( $user->has_course_status( $course->get_id(), array( 'finished' ) ) || ! $user->has_quiz_status( 'started', $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		learn_press_get_template( 'content-quiz/buttons/check.php' );
	}
}

if ( ! function_exists( 'learn_press_quiz_hint_button' ) ) {

	function learn_press_quiz_hint_button() {
		$course   = LP_Global::course();
		$user     = LP_Global::user();
		$quiz     = LP_Global::course_item_quiz();
		$question = LP_Global::quiz_question();

		if ( ! $quiz->is_viewing_question() ) {
			return;
		}

		if ( ! $question->get_hint() ) {
			return;
		}

		if ( ! $user->can_hint_answer( $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( ! $user->has_quiz_status( 'started', $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		$quiz_item = $user->get_quiz_data( $quiz->get_id(), $course->get_id() );

		if ( $quiz_item && ( $quiz_item->has_checked_question( $question->get_id() ) || $quiz_item->is_answered( $question->get_id() ) ) ) {
			return;
		}

		learn_press_get_template( 'content-quiz/buttons/hint.php' );
	}
}

if ( ! function_exists( 'learn_press_profile_order_details' ) ) {
	function learn_press_profile_order_details() {
		$profile = LP_Profile::instance();

		if ( false === ( $order = $profile->get_view_order() ) ) {
			return;
		}

		learn_press_get_template( 'order/order-details.php', array( 'order' => $order ) );
	}
}

if ( ! function_exists( 'learn_press_profile_order_recover' ) ) {
	function learn_press_profile_order_recover() {
		$profile = LP_Profile::instance();

		if ( false === ( $order = $profile->get_view_order() ) ) {
			return;
		}
		learn_press_get_template( 'profile/tabs/orders/recover-my-order.php', array( 'order' => $order ) );
	}
}

if ( ! function_exists( 'learn_press_profile_order_message' ) ) {
	function learn_press_profile_order_message() {
		$profile = LP_Profile::instance();

		if ( false === ( $order = $profile->get_view_order() ) ) {
			return;
		}
		learn_press_get_template( 'profile/tabs/orders/order-message.php', array( 'order' => $order ) );
	}
}




if ( ! function_exists( 'learn_press_profile_dashboard_logged_in' ) ) {
	function learn_press_profile_dashboard_logged_in() {
		learn_press_get_template( 'profile/dashboard-logged-in.php' );
	}
}

if ( ! function_exists( 'learn_press_profile_dashboard_user_bio' ) ) {
	function learn_press_profile_dashboard_user_bio() {
		$profile = LP_Profile::instance();

		if ( ! $user = $profile->get_user() ) {
			return;
		}

		learn_press_get_template( 'profile/user-bio.php' );
	}
}

if ( ! function_exists( 'learn_press_profile_dashboard_not_logged_in' ) ) {
	function learn_press_profile_dashboard_not_logged_in() {
		$profile = LP_Global::profile();

		if ( ! $profile->get_user()->is_guest() ) {
			return;
		}

		if ( 'yes' === LP()->settings()->get( 'enable_register_profile' ) || 'yes' === LP()->settings()->get( 'enable_login_profile' ) ) {
			return;
		}

		learn_press_get_template( 'profile/not-logged-in.php' );
	}
}

if ( ! function_exists( 'learn_press_profile_login_form' ) ) {
	function learn_press_profile_login_form() {
		$profile = LP_Global::profile();

		print_r( metadata_exists( 'user', $profile->get_user()->get_id(), '_lp_temp_user' ) );
		if ( ! $profile->get_user()->is_guest() ) {
			return;
		}

		if ( ! $fields = $profile->get_login_fields() ) {
			return;
		}

		if ( 'yes' !== LP()->settings()->get( 'enable_login_profile' ) ) {
			return;
		}

		learn_press_get_template( 'global/form-login.php', array( 'fields' => $fields ) );
	}
}

if ( ! function_exists( 'learn_press_profile_register_form' ) ) {
	function learn_press_profile_register_form() {
		$profile = LP_Global::profile();

		if ( ! $profile->get_user()->is_guest() ) {
			return;
		}

		if ( ! $fields = $profile->get_register_fields() ) {
			return;
		}

		if ( 'yes' !== LP()->settings()->get( 'enable_register_profile' ) ) {
			return;
		}

		learn_press_get_template( 'global/form-register.php', array( 'fields' => $fields ) );
	}
}


if ( ! function_exists( 'learn_press_content_item_lesson_title' ) ) {
	function learn_press_content_item_lesson_title() {
		$item = LP_Global::course_item();

		if ( ( 'standard' !== ( $format = $item->get_format() ) ) && file_exists( $format_template = learn_press_locate_template( "content-lesson/{$format}/title.php" ) ) ) {
			include $format_template;

			return;
		}
		learn_press_get_template( 'content-lesson/title.php' );
	}
}

if ( ! function_exists( 'learn_press_content_item_lesson_content' ) ) {
	function learn_press_content_item_lesson_content() {
		$item = LP_Global::course_item();

		if ( $item->is_blocked() ) {
			return;
		}

		if ( ( 'standard' !== ( $format = $item->get_format() ) ) && file_exists( $format_template = learn_press_locate_template( "content-lesson/{$format}/content.php" ) ) ) {
			include $format_template;

			return;
		}
		do_action( 'learn-press/lesson-start', $item );
		learn_press_get_template( 'content-lesson/content.php' );
	}
}

if ( ! function_exists( 'learn_press_content_item_lesson_content_blocked' ) ) {
	function learn_press_content_item_lesson_content_blocked() {
		$item = LP_Global::course_item();

		if ( ! $item->is_blocked() ) {
			return;
		}

		learn_press_get_template( 'global/block-content.php' );
	}
}

if ( ! function_exists( 'learn_press_content_item_lesson_complete_button' ) ) {
	function learn_press_content_item_lesson_complete_button() {
		$user   = LP_Global::user();
		$course = LP_Global::course();
		$lesson = LP_Global::course_item();

		if ( ! $course->is_required_enroll() ) {
			return;
		}

		if ( ( $course_item = $user->get_course_data( $course->get_id() ) ) && $course_item->is_finished() ) {
			return;
		}

		if ( ! $user->can_access_course( $course->get_id() ) ) {
			return;
		}

		learn_press_get_template( 'content-lesson/button-complete.php' );
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_question_explanation' ) ) {

	/**
	 * Render content if quiz question.
	 */
	function learn_press_content_item_summary_question_explanation() {
		$quiz = LP_Global::course_item_quiz();
		if ( $question = $quiz->get_viewing_question() ) {
			$course      = LP_Global::course();
			$user        = LP_Global::user();
			$course_data = $user->get_course_data( $course->get_id() );
			$user_quiz   = $course_data->get_item_quiz( $quiz->get_id() );

			if ( ! $question->get_explanation() ) {
				return;
			}

			if ( $user_quiz->has_checked_question( $question->get_id() ) || $user_quiz->is_answered_true( $question->get_id() ) ) {
				learn_press_get_template( 'content-question/explanation.php', array( 'question' => $question ) );
			}
		}
	}
}

if ( ! function_exists( 'learn_press_breadcrumb' ) ) {
	/**
	 * Output the breadcrumb of archive courses
	 *
	 * @param array
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

if ( ! function_exists( 'learn_press_search_form' ) ) {
	/**
	 * Output the breadcrumb of archive courses
	 *
	 * @param array
	 */
	function learn_press_search_form() {
		if ( ! empty( $_REQUEST['s'] ) && ! empty( $_REQUEST['ref'] ) && 'course' == $_REQUEST['ref'] ) {
			$s = stripslashes_deep( $_REQUEST['s'] );
		} else {
			$s = '';
		}

		learn_press_get_template( 'search-form.php', array( 's' => $s ) );
	}
}


if ( ! function_exists( 'learn_press_content_single_item' ) ) {
	function learn_press_content_single_item() {
		if ( $course_item = LP_Global::course_item() ) {
			// remove course comment form on singler item
			add_filter( 'comments_open', 'learn_press_course_comments_open', 10, 2 );
			learn_press_get_template( 'content-single-item.php' );
		}
	}
}

if ( ! function_exists( 'learn_press_content_single_course' ) ) {
	function learn_press_content_single_course() {

		if ( ! $course_item = LP_Global::course_item() ) {
			learn_press_get_template( 'content-single-course.php' );
		}
	}
}



if ( ! function_exists( 'learn_press_section_item_meta' ) ) {
	/**
	 * @param object
	 * @param array
	 * @param LP_Course
	 */
	function learn_press_section_item_meta( $item, $section ) {
		learn_press_get_template(
			'single-course/section/item-meta.php',
			array(
				'item'    => $item,
				'section' => $section,
			)
		);
	}
}

if ( ! function_exists( 'learn_press_order_details_table' ) ) {

	/**
	 * Displays order details in a table.
	 *
	 * @param mixed $order_id
	 *
	 * @subpackage    Orders
	 */
	function learn_press_order_details_table( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		learn_press_get_template(
			'order/order-details.php',
			array(
				'order' => learn_press_get_order( $order_id ),
			)
		);
	}
}


if ( ! function_exists( 'learn_press_profile_tab_courses_own' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_own( $user, $tab = null ) {
		$args              = array(
			'user'   => $user,
			'subtab' => $tab,
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


if ( ! function_exists( 'learn_press_profile_tab_courses_learning' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_learning( $user, $tab = null ) {
		$args              = array(
			'user'   => $user,
			'subtab' => $tab,
		);
		$limit             = LP()->settings->get( 'profile_courses_limit', 10 );
		$limit             = apply_filters( 'learn_press_profile_tab_courses_learning_limit', $limit );
		$courses           = $user->get(
			'enrolled-courses',
			array(
				'status' => 'enrolled',
				'limit'  => $limit,
			)
		);
		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
		$args['courses']   = $courses;
		$args['num_pages'] = $num_pages;
		learn_press_get_template( 'profile/tabs/courses/learning.php', $args );
	}
}

if ( ! function_exists( 'learn_press_profile_tab_courses_purchased' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_purchased( $user, $tab = null ) {
		$args              = array(
			'user'   => $user,
			'subtab' => $tab,
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

if ( ! function_exists( 'learn_press_profile_tab_courses_finished' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_finished( $user, $tab = null ) {
		$args              = array(
			'user'   => $user,
			'subtab' => $tab,
		);
		$limit             = LP()->settings->get( 'profile_courses_limit', 10 );
		$limit             = apply_filters( 'learn_press_profile_tab_courses_finished_limit', $limit );
		$courses           = $user->get(
			'enrolled-courses',
			array(
				'status' => 'finished',
				'limit'  => $limit,
			)
		);
		$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
		$args['courses']   = $courses;
		$args['num_pages'] = $num_pages;
		learn_press_get_template( 'profile/tabs/courses/finished.php', $args );
	}
}


if ( ! function_exists( 'learn_press_output_user_profile_tabs' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_output_user_profile_tabs( $user, $current, $tabs ) {
		learn_press_get_template(
			'profile/tabs.php',
			array(
				'user'    => $user,
				'tabs'    => $tabs,
				'current' => $current,
			)
		);
	}
}

if ( ! function_exists( 'learn_press_profile_tab_courses_all' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_profile_tab_courses_all( $user, $tab = null ) {
		$args              = array(
			'user'   => $user,
			'subtab' => $tab,
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

if ( ! function_exists( 'learn_press_become_teacher_messages' ) ) {
	function learn_press_become_teacher_messages() {
		$messages = LP_Shortcode_Become_A_Teacher::get_messages();
		if ( ! $messages ) {
			return;
		}

		learn_press_get_template( 'global/become-teacher-form/message.php', array( 'messages' => $messages ) );
	}
}

if ( ! function_exists( 'learn_press_become_teacher_heading' ) ) {

	function learn_press_become_teacher_heading() {
		$messages = LP_Shortcode_Become_A_Teacher::get_messages();
		if ( $messages ) {
			return;
		}
		?>
		<h3><?php _e( 'Fill out the form and send us your requesting.', 'learnpress' ); ?></h3>
		<?php
	}
}

if ( ! function_exists( 'learn_press_become_teacher_form_fields' ) ) {

	function learn_press_become_teacher_form_fields() {
		$messages = LP_Shortcode_Become_A_Teacher::get_messages();
		if ( $messages ) {
			return;
		}

		include_once LP_PLUGIN_PATH . 'inc/admin/meta-box/class-lp-meta-box-helper.php';

		learn_press_get_template( 'global/become-teacher-form/form-fields.php', array( 'fields' => learn_press_get_become_a_teacher_form_fields() ) );
	}
}

if ( ! function_exists( 'learn_press_become_teacher_button' ) ) {

	function learn_press_become_teacher_button() {
		$messages = LP_Shortcode_Become_A_Teacher::get_messages();
		if ( $messages ) {
			return;
		}

		learn_press_get_template( 'global/become-teacher-form/button.php' );
	}
}

if ( ! function_exists( 'learn_press_back_to_class_button' ) ) {
	function learn_press_back_to_class_button() {
		$courses_link = learn_press_get_page_link( 'courses' );
		if ( ! $courses_link ) {
			return;
		}
		?>

		<a href="<?php echo learn_press_get_page_link( 'courses' ); ?>"><?php _e( 'Back to class', 'learnpress' ); ?></a>
		<?php
	}
}

function learn_press_get_become_a_teacher_form_fields() {
	$user   = learn_press_get_current_user();
	$fields = array(
		'bat_name'    => array(
			'title'       => __( 'Name', 'learnpress' ),
			'type'        => 'text',
			'placeholder' => __( 'Your name', 'learnpress' ),
			'saved'       => $user->get_display_name(),
			'id'          => 'bat_name',
			'required'    => true,
		),
		'bat_email'   => array(
			'title'       => __( 'Email', 'learnpress' ),
			'type'        => 'email',
			'placeholder' => __( 'Your email address', 'learnpress' ),
			'saved'       => $user->get_email(),
			'id'          => 'bat_email',
			'required'    => true,
		),
		'bat_phone'   => array(
			'title'       => __( 'Phone', 'learnpress' ),
			'type'        => 'text',
			'placeholder' => __( 'Your phone number', 'learnpress' ),
			'id'          => 'bat_phone',
		),
		'bat_message' => array(
			'title'       => __( 'Message', 'learnpress' ),
			'type'        => 'textarea',
			'placeholder' => __( 'Your message', 'learnpress' ),
			'id'          => 'bat_message',
		),
	);
	$fields = apply_filters( 'learn_press_become_teacher_form_fields', $fields );

	return $fields;
}
