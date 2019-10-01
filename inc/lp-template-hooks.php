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
add_action( 'learn-press/single-course-summary', LP()->template()->func( 'course_sidebar' ), 5 );

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
add_action( 'learn-press/content-learning-summary', function () {
	learn_press_get_template( 'single-course/' );
}, 15 );
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
//add_action( 'learn-press/single-item-summary', 'learn_press_course_curriculum_tab', 5 );
//add_action( 'learn-press/single-item-summary', 'learn_press_single_course_content_item', 10 );
add_action( 'learn-press/single-item-summary', LP()->template()->func( 'popup_header' ), 5 );
add_action( 'learn-press/single-item-summary', LP()->template()->func( 'popup_sidebar' ), 10 );
add_action( 'learn-press/single-item-summary', LP()->template()->func( 'popup_content' ), 10 );
add_action( 'learn-press/single-item-summary', LP()->template()->func( 'popup_footer' ), 15 );

add_action( 'learn-press/popup-footer', LP()->template()->func( 'popup_footer_nav' ), 15 );

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
//add_action( 'learn-press/course-item-content-header', 'learn_press_content_item_header', 10 );
//add_action( 'learn-press/course-item-content-footer', 'learn_press_content_item_footer', 10 );
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
//add_action( 'learn-press/before-content-item-summary/lp_quiz', 'learn_press_content_item_quiz_intro', 10 );

/**
 * @see learn_press_content_item_summary_quiz_content
 * @see learn_press_content_item_summary_quiz_progress
 * @see learn_press_content_item_summary_quiz_result
 * @see learn_press_content_item_summary_quiz_countdown
 * @see learn_press_content_item_summary_quiz_question
 *
 */
//add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_progress', 5 );
//add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_result', 10 );
//add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_content', 15 );
//add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_countdown', 20 );
//add_action( 'learn-press/content-item-summary/lp_quiz', 'learn_press_content_item_summary_quiz_question', 25 );

function get_attempts( $quiz_id, $course_id, $user_id ) {
	$user       = learn_press_get_user( $user_id );
	$userCourse = $user->get_course_data( $course_id );
	$userQuiz   = $userCourse ? $userCourse->get_item( $quiz_id ) : false;
	$attempts   = array();
	if ( $userQuiz ) {
		if ( $rows = $userQuiz->get_history() ) {
			foreach ( $rows as $row ) {
				$attempts[] = $row;
			}
		}
	}

	learn_press_debug( LP_Object_Cache::instance() );

	var_dump( $rows );

	return $attempts;
}

add_action( 'learn-press/content-item-summary/lp_quiz', function () {
	$user      = learn_press_get_current_user();
	$course    = LP_Global::course();
	$quiz      = LP_Global::course_item_quiz();
	$questions = array();
	$showHint  = $quiz->get_show_hint();
	$showCheck = $quiz->get_show_check_answer();
	$userJS    = array();


	$userCourse = $user->get_course_data( $course->get_id() );
	$userQuiz   = $userCourse ? $userCourse->get_item( $quiz->get_id() ) : false;
	$attempts   = $userQuiz->get_attempts();/// get_attempts($quiz->get_id(), $course->get_id(), $user->get_id());
	$answered   = array();
	$status     = '';

	include_once LP_PLUGIN_PATH . '/inc/libraries/php-crypto.php';
	$cryptoJsAes = function_exists( 'openssl_decrypt' );
	$editable    = $user->is_admin() || get_post_field( $user->is_author_of( $course->get_id() ) );

	if ( $userQuiz ) {
		$status  = $userQuiz->get_status();
		$results = $userQuiz->get_results( '' );
		//$attempts = array_merge( $attempts, [ $results ] );

		$userJS = array(
			'status'            => $status,
			'attempts'          => $attempts,
			'checked_questions' => $userQuiz->get_checked_questions(),
			'hinted_questions'  => $userQuiz->get_hint_questions()
		);

		//if ( $status === 'completed' ) {
		$answered = $userQuiz->get_meta( '_question_answers' );
		//}
	}


	if ( $question_ids = $quiz->get_questions() ) {
		$checkedQuestions = isset( $userJS['checked_questions'] ) ? $userJS['checked_questions'] : array();
		$hintedQuestions  = isset( $userJS['hinted_questions'] ) ? $userJS['hinted_questions'] : array();

		foreach ( $question_ids as $id ) {
			$question       = learn_press_get_question( $id );
			$hasHint        = false;
			$hasExplanation = false;
			$canCheck       = false;
			$hinted         = false;
			$checked        = false;
			$theHint        = '';
			$theExplanation = '';

			if ( $showHint ) {
				$theHint = $question->get_hint();
				$hinted  = in_array( $id, $hintedQuestions );
				$hasHint = ! ! $theHint;
			}

			if ( $showCheck ) {
				$theExplanation = $question->get_explanation();
				$checked        = in_array( $id, $checkedQuestions );
				$hasExplanation = ! ! $theExplanation;
			}

			//$canHint  = $showHint ?  !in_array($id, $hintedQuestions) : false;
			//$canCheck = $showExplanation ? !in_array($id, $checkedQuestions) : false;
			$questionData = array(
				'id'          => absint( $id ),
				'title'       => $question->get_title(),
				'content'     => $question->get_content(),
				'type'        => $question->get_type(),
				'hint'        => $hinted ? $theHint : '',
				'explanation' => $checked ? $theExplanation : ''
			);

			if ( $hasHint ) {
				$questionData['has_hint'] = $hasHint;

				if ( $hinted ) {
					$questionData['hint'] = $theHint;
				}
			}

			if ( $hasExplanation ) {
				$questionData['has_explanation'] = $hasExplanation;

				if ( $checked ) {
					$questionData['explanation'] = $theExplanation;
				}
			}

			$with_true_or_false = $checked || $status === 'completed';

			if ( $cryptoJsAes ) {
				$options = array_values( $question->get_answer_options() );

				$key                     = uniqid();
				$questionData['options'] = array(
					'data' => cryptoJsAesEncrypt( $key, wp_json_encode( $options ) ),
					'key'  => $key
				);
			} else {
				$questionData['options'] = array_values( $question->get_answer_options( array( 'with_true_or_false' => $with_true_or_false ) ) );
			}

			$questions[] = $questionData;
		}

		if ( $status !== 'completed' ) {
			if ( $checkedQuestions && $answered ) {

				$omitIds = array_diff( $question_ids, $checkedQuestions );

				if ( $omitIds ) {
					foreach ( $omitIds as $omitId ) {
						if ( ! empty( $answered[ $omitId ] ) ) {
							unset( $answered[ $omitId ] );
						}
					}
				}
			}
		}

	}

	$duration = $quiz->get_duration();

	$js = array(
		'course_id'            => $course->get_id(),
		'nonce'                => wp_create_nonce( sprintf( 'user-quiz-%d', get_current_user_id() ) ),
		'id'                   => $quiz->get_id(),
		'title'                => $quiz->get_title(),
		'content'              => $quiz->get_content(),
		'questions'            => $questions,
		'question_ids'         => array_map( 'absint', array_values( $question_ids ) ),
		'current_question'     => absint( reset( $question_ids ) ),
		'question_nav'         => 'infinity',
		'status'               => '',
		'attempts'             => array(),
		'attempts_count'       => 10,
		'answered'             => (object) $answered,
		'passing_grade'        => $quiz->get_passing_grade(),
		'review_questions'     => $quiz->get_review_questions(),
		'show_correct_answers' => $quiz->get_show_result(),
		'show_check_answers'   => ! ! $quiz->get_show_check_answer(),
		'show_hint'            => ! ! $quiz->get_show_hint(),
		'support_options'      => apply_filters( 'learn-press/4.0/question-support-options', array(
			'true_or_false',
			'single_choice',
			'multi_choice'
		) ),
		'duration'             => $duration ? $duration->get() : false,
		'crypto'               => $cryptoJsAes,
		'edit_permalink'       => $editable ? get_edit_post_link( $quiz->get_id() ) : ''
	);

	$js = array_merge( $js, $userJS );


	?>
    <div id="learn-press-quiz-app"></div>
    <script>
        window.addEventListener('load', function () {
            jQuery(($) => {
                LP.quiz.init(
                    '#learn-press-quiz-app',
					<?php echo json_encode( $js, JSON_PRETTY_PRINT );?>
                );
            })
        });

        var CryptoJSAesJson = {
            stringify: function (cipherParams) {
                var j = {ct: cipherParams.ciphertext.toString(CryptoJS.enc.Base64)};
                if (cipherParams.iv) j.iv = cipherParams.iv.toString();
                if (cipherParams.salt) j.s = cipherParams.salt.toString();
                return JSON.stringify(j).replace(/\s/g, '');
            },
            parse: function (jsonStr) {
                var j = JSON.parse(jsonStr);
                var cipherParams = CryptoJS.lib.CipherParams.create({ciphertext: CryptoJS.enc.Base64.parse(j.ct)});
                if (j.iv) cipherParams.iv = CryptoJS.enc.Hex.parse(j.iv);
                if (j.s) cipherParams.salt = CryptoJS.enc.Hex.parse(j.s);
                return cipherParams;
            }
        }

    </script>
	<?php
}, 25 );

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
add_action( 'learn-press/before-courses-loop-item', 'learn_press_courses_loop_item_thumbnail', 10 );
//add_action( 'learn-press/before-courses-loop-item', 'learn_press_courses_loop_item_instructor', 5 );

/**
 * @see learn_press_courses_loop_item_begin_meta
 * @see learn_press_courses_loop_item_price
 * @see learn_press_courses_loop_item_instructor
 * @see learn_press_courses_loop_item_end_meta
 * @see learn_press_course_loop_item_buttons
 * @see learn_press_course_loop_item_user_progress
 */
add_action( 'learn-press/before-courses-loop-item', function () {
	echo '<div class="course-content">';
}, 1000 );

add_action( 'learn-press/courses-loop-item-title', 'learn_press_courses_loop_item_title', 5 );


//add_action( 'learn-press/after-courses-loop-item', 'learn_press_courses_loop_item_begin_meta', 10 );

add_action( 'learn-press/after-courses-loop-item', LP()->template()->func( 'courses_loop_item_meta' ), 0 );
add_action( 'learn-press/after-courses-loop-item', LP()->template()->func( 'courses_loop_item_info_begin' ), 0 );

add_action( 'learn-press/after-courses-loop-item', LP()->template()->func( 'clearfix' ), 20 );
add_action( 'learn-press/after-courses-loop-item', LP()->template()->func( 'courses_loop_item_students' ), 20 );
add_action( 'learn-press/after-courses-loop-item', LP()->template()->func( 'courses_loop_item_price' ), 20 );
//add_action( 'learn-press/after-courses-loop-item', LP()->template()->callback( 'single-course/title' ), 20 );


//add_action( 'learn-press/after-courses-loop-item', LP()->template()->c( 'courses_loop_item_price' ), 20 );

//add_action( 'learn-press/after-courses-loop-item', 'learn_press_courses_loop_item_instructor', 25 );
//add_action( 'learn-press/after-courses-loop-item', 'learn_press_courses_loop_item_end_meta', 30 );

add_action( 'learn-press/after-courses-loop-item', LP()->template()->func( 'courses_loop_item_info_end' ), 99 );

add_action( 'learn-press/after-courses-loop-item', 'learn_press_course_loop_item_buttons', 35 );
add_action( 'learn-press/after-courses-loop-item', 'learn_press_course_loop_item_user_progress', 40 );

add_action( 'learn-press/after-courses-loop-item', function () {
	echo '</div>';
}, 1000 );
//add_action( 'learn-press/after-courses-loop-item', LP()->template()->func( 'course_button' ), 1000 );
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
//add_action( 'wp_head', 'learn_press_reset_single_item_summary_content' );

/**
 * 4.x.x
 */

add_action( 'learn-press/before-courses-loop', LP()->template()->func( 'courses_top_bar' ), 10 );

function learn_press_custom_excerpt_length( $length ) {
	return 20;
}

add_filter( 'excerpt_length', 'learn_press_custom_excerpt_length', 999 );


add_action( 'learn-press/course-summary-sidebar', LP()->template()->func( 'course_sidebar_preview' ), 10 );
add_action( 'learn-press/course-summary-sidebar', LP()->template()->func( 'course_extra_key_features' ), 10 );
add_action( 'learn-press/course-summary-sidebar', LP()->template()->func( 'course_extra_requirements' ), 10 );
