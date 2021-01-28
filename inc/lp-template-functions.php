<?php
/**
 * All functions for LearnPress template
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * New functions since 3.0.0
 */
if ( ! function_exists( 'learn_press_course_purchase_button' ) ) {
	/**
	 * Purchase course button.
	 */
	function learn_press_course_purchase_button() {
		$course = LP_Global::course();
		$user   = learn_press_get_user( get_current_user_id() );

		if ( empty( $user ) || ! $course ) {
			return;
		}

		$course_data       = $user->get_course_data( $course->get_id() );
		$is_finish         = $course_data->is_finished();
		$can_retake_course = $user->can_retake_course( $course->get_id() );
		$purchased         = $user->has_purchased_course( $course->get_id() );

		//  if free course
		if ( $course->is_free() ) {
			return;
		}

		// if has retake and has purchased
		if ( $can_retake_course > 0 && $purchased ) {
			return;
		}

		// if external link
		if ( $course->get_external_link() ) {
			return;
		}

		// If course is not published
		if ( ! $course->is_publish() ) {
			return;
		}

		// Course is not require enrolling
		if ( ! $course->is_required_enroll() ) {
			return;
		}

		//remove by tungnx 3.2.8.4
//		if ( $user->has_enrolled_course( $course->get_id() )) {
//
//		    return;
//        	}

		// If course is reached limitation.
		if ( ! $course->is_in_stock() ) {
			$message = apply_filters(
				'learn-press/maximum-students-reach',
				__( 'This course is out of stock', 'learnpress' )
			);
			learn_press_display_message( $message );


			return;
		}

		// User can not purchase course
		if ( ! $user->can_purchase_course( $course->get_id() ) ) {
			return;
		}


		// If user has already purchased course but has not finished yet.
		/*if ( $user->has_purchased_course( $course->get_id() ) && 'finished' !== $user->get_course_status( $course->get_id() ) ) {
			return;
		}*/


		// If the order contains course is processing
		if ( ( $order = $user->get_course_order( $course->get_id() ) ) && $order->get_status() === 'processing' ) {
			$message = apply_filters(
				'learn-press/order-processing-message',
				__( 'Your order is waiting for processing', 'learnpress' )
			);
			learn_press_display_message( $message );

			return;
		}

		$args_load_tmpl = array(
			'template_name' => 'single-course/buttons/purchase.php',
			'template_path' => '',
			'default_path'  => ''
		);

		$args_load_tmpl = apply_filters( 'learn-press/tmpl-button-purchase-course', $args_load_tmpl, $course );

		if ( $is_finish ) {

			//set for course finished
			if ( $course->is_allow_repurchase_course() ) {
				learn_press_get_template( $args_load_tmpl['template_name'], array( 'course' => $course ),
					$args_load_tmpl['template_path'], $args_load_tmpl['default_path'] );
			}

		} elseif ( ! $is_finish ) {
			// Set for unfinish course

			// case1: has purchase

			if ( $purchased ) {

				if ( $course->is_allow_repurchase_course() &&
	                         $user->user_check_blocked_duration( $course->get_id() ) == true &&
	     $course_data->get_status() == 'enrolled' ) {
					learn_press_get_template( $args_load_tmpl['template_name'], array( 'course' => $course ),
						$args_load_tmpl['template_path'], $args_load_tmpl['default_path'] );
				}

				// case2: not purchase
			} else {
				learn_press_get_template( $args_load_tmpl['template_name'], array( 'course' => $course ),
					$args_load_tmpl['template_path'], $args_load_tmpl['default_path'] );
			}

		}
	}
}

if ( ! function_exists( 'learn_press_course_enroll_button' ) ) {
	/**
	 * Enroll course button.
	 */
	function learn_press_course_enroll_button() {
		$user              = learn_press_get_user( get_current_user_id() );
		$course            = LP_Global::course();
		$purchased         = $user->has_purchased_course( $course->get_id() );
		$course_data       = $user->get_course_data( $course->get_id() );
		$is_finish         = $course_data->is_finished();
		$is_enrolled       = $course_data->is_enrolled();
		$can_retake_course = $user->can_retake_course( $course->get_id() );

		if ( empty( $user ) ) {
			return;
		}

		// if can retake course
		if ( $can_retake_course > 0 && $purchased ) {
			if ( $is_enrolled ) {
				return;
			}
		}

		if ( $course->get_external_link() ) {
			learn_press_show_log( 'Course has external link' );

			return;
		}

		// If course is not published
		if ( ! $course->is_publish() ) {
			learn_press_show_log( 'Course is not published' );

			return;
		}

		// Locked course for user
		if ( $user->is_locked_course( $course->get_id() ) ) {
			learn_press_show_log( 'Course is locked' );

			return;
		}

		// Course out of stock (full students)
		if ( ! $course->is_in_stock() ) {
			return;
		}
		// Course is not require enrolling
		if ( ! $course->is_required_enroll() ) {
			return;
		}
		// User can not enroll course
		if ( ! $user->can_enroll_course( $course->get_id() ) ) {
			return;
		}

		// For free course and user does not purchased
		if ( ! $is_finish ) {

			// set for course unfinished
			if ( $purchased && ! in_array( $course_data->get_status(), array( 'enrolled' ) ) ) {

				learn_press_get_template( 'single-course/buttons/enroll.php' );

			} elseif ( ! $purchased && ( ! in_array( $course_data->get_status(),
						array( 'enrolled' ) ) && $course->is_free() ) ) {

				learn_press_get_template( 'single-course/buttons/enroll.php' );

			}
		}
	}

}

if ( ! function_exists( 'learn_press_course_retake_button' ) ) {

	/**
	 * Retake course button
	 */
	function learn_press_course_retake_button() {

		if ( ! isset( $course ) ) {
			$course = learn_press_get_course();
		}

		if ( ! $course ) {
			return;
		}

		$retake_config = absint( get_post_meta( $course->get_id(), '_lp_retake_count', true ) );

		if ( $retake_config == 0 ) {
			return;
		}

		if ( ! learn_press_current_user_enrolled_course() && $course->get_external_link() ) {
			return;
		}

		if ( ! isset( $user ) ) {
			$user = learn_press_get_current_user();
		}

		if ( ! $user ) {
			return;
		}

		// Check user have turn retake course
		$can_retake_course = $user->can_retake_course( $course->get_id() );
		if ( ! $can_retake_course ) {
			return;
		}
		// Check user not enroll course
		if ( ! $user->has_enrolled_course( $course->get_id() ) ) {
			return;
		}

		// If user has not finished course
		if ( ! $user->has_finished_course( $course->get_id() ) ) {
			/**
			 * Check course duration not expire
			 *
			 * @author hungkv
			 * @since  3.2.7.7
			 */
			if ( ! $course->is_block_item_content_duration() ||
			     ( $course->is_block_item_content_duration() && $course->expires_to_milliseconds() > 0 ) ) {
				return;
			}
		}

		$args = array( 'course' => $course, 'user' => $user, 'count' => $can_retake_course );

		learn_press_get_template( 'single-course/buttons/retake.php', $args );
	}
}

if ( ! function_exists( 'learn_press_course_continue_button' ) ) {

	/**
	 * Retake course button
	 */
	function learn_press_course_continue_button() {
		$user   = LP_Global::user();
		$course = LP_Global::course();

		if ( ! learn_press_current_user_enrolled_course() && $course->get_external_link() ) {
			return;
		}

		if ( ! $user ) {
			return;
		}

		if ( false === ( $course_data = $user->get_course_data( $course->get_id() ) ) ) {
			return;
		}

		if ( ! $course_data->is_available() ) {
			return;
		}

		if ( $course_data->get_status() !== 'enrolled' ) {
			return;
		}

		if ( ! $course_data->get_item_at( 0 ) ) {
			return;
		}

		/**
		 * Check course duration expire
		 *
		 * @author hungkv
		 * @since  3.2.7.7
		 */
		if ( $course->is_block_item_content_duration() === true && $course->expires_to_milliseconds() <= 0 ) {
			return;
		}

		learn_press_get_template( 'single-course/buttons/continue.php' );
	}
}


if ( ! function_exists( 'learn_press_course_finish_button' ) ) {

	/**
	 * Retake course button
	 */
	function learn_press_course_finish_button() {
		$user   = LP_Global::user();
		$course = LP_Global::course();

		if ( ! learn_press_current_user_enrolled_course() && $course->get_external_link() ) {
			return;
		}

		if ( ! $user ) {
			return;
		}

		if ( false === ( $course_data = $user->get_course_data( $course->get_id() ) ) ) {
			return;
		}

		if ( ! $user->can_finish_course( $course->get_id() ) ) {
			return;
		}

		learn_press_get_template( 'single-course/buttons/finish.php' );
	}
}

if ( ! function_exists( 'learn_press_course_external_button' ) ) {

	/**
	 * Retake course button
	 */
	function learn_press_course_external_button() {
		$course = LP_Global::course();

		if ( ! $link = $course->get_external_link() ) {
			return;
		}

		$user = learn_press_get_current_user();

		if ( ! $user->has_enrolled_course( $course->get_id() ) ) {
			// Remove all other buttons
			learn_press_remove_course_buttons();
			learn_press_get_template( 'single-course/buttons/external-link.php' );
			// Add back other buttons for other courses
			add_action( 'learn-press/after-course-buttons', 'learn_press_add_course_buttons' );
		}
	}
}

if ( ! function_exists( 'learn_press_add_course_buttons' ) ) {
	function learn_press_add_course_buttons() {
		add_action( 'learn-press/course-buttons', 'learn_press_course_purchase_button', 10 );
		add_action( 'learn-press/course-buttons', 'learn_press_course_enroll_button', 15 );
		add_action( 'learn-press/course-buttons', 'learn_press_course_retake_button', 20 );
		add_action( 'learn-press/course-buttons', 'learn_press_course_continue_button', 25 );
		add_action( 'learn-press/course-buttons', 'learn_press_course_finish_button', 30 );
	}
}

if ( ! function_exists( 'learn_press_remove_course_buttons' ) ) {
	function learn_press_remove_course_buttons() {
		remove_action( 'learn-press/course-buttons', 'learn_press_course_purchase_button', 10 );
		remove_action( 'learn-press/course-buttons', 'learn_press_course_enroll_button', 15 );
		remove_action( 'learn-press/course-buttons', 'learn_press_course_retake_button', 20 );
		remove_action( 'learn-press/course-buttons', 'learn_press_course_continue_button', 25 );
		remove_action( 'learn-press/course-buttons', 'learn_press_course_finish_button', 30 );
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
		$cart = learn_press_get_checkout_cart();
		learn_press_get_template( 'checkout/review-order.php', array( 'cart' => $cart ) );
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
		$checkout  = LP()->checkout();
		$is_exists = $checkout->checkout_email_exists();

		$args = array(
			'checkout'  => $checkout,
			'is_exists' => $is_exists
		);

		if ( $checkout->is_enable_guest_checkout() && ! is_user_logged_in() ) {
			learn_press_get_template( 'checkout/guest-email.php', $args );
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
		//learn_press_get_template( 'profile/footer.php', array( 'user' => $user ) );
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

		if ( ! $user ) {
			return;
		}

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

if ( ! function_exists( 'learn_press_get_course_tabs' ) ) {
	/**
	 * Return an array of tabs display in single course page.
	 *
	 * @return array
	 */
	function learn_press_get_course_tabs() {

		$course = learn_press_get_course();
		$user   = learn_press_get_current_user();

		$defaults = array();

		// Description tab - shows product content
		if ( $course && $course->get_content() ) {
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

		$defaults['instructor'] = array(
			'title'    => __( 'Instructor', 'learnpress' ),
			'priority' => 40,
			'callback' => 'learn_press_course_instructor_tab'
		);


		// Filter
		if ( $tabs = apply_filters( 'learn-press/course-tabs', $defaults ) ) {
			// Sort tabs by priority
			uasort( $tabs, 'learn_press_sort_list_by_priority_callback' );
			$request_tab = ! empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : '';
			$has_active  = false;
			foreach ( $tabs as $k => $v ) {
				$v['id'] = ! empty( $v['id'] ) ? $v['id'] : 'tab-' . $k;

				if ( $request_tab === $v['id'] ) {
					$v['active'] = true;
					$has_active  = $k;
				} elseif ( isset( $v['active'] ) && $v['active'] ) {
					$has_active = true;
				}
				$tabs[ $k ] = $v;
			}

			if ( ! $has_active ) {
				/**
				 * Active Curriculum tab if user has enrolled course
				 */
				if ( $course && $user->has_course_status( $course->get_id(), array(
						'enrolled',
						'finished'
					) ) && ! empty( $tabs['curriculum'] )
				) {
					$tabs['curriculum']['active'] = true;
				} elseif ( ! empty( $tabs['overview'] ) ) {
					$tabs['overview']['active'] = true;
				} else {
					$keys                         = array_keys( $tabs );
					$first_key                    = reset( $keys );
					$tabs[ $first_key ]['active'] = true;
				}
			}
		}

		return $tabs;
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

		if ( ! $user ) {
			return;
		}

		if ( ! $user->has_quiz_status( 'started', $quiz->get_id(), $course->get_id() ) ) {
			//return;
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
		$quiz = LP_Global::course_item_quiz();
		$user = LP_Global::user();

		if ( ! $user ) {
			return;
		}

		/**
		 * Check if not start quiz (is showing question)
		 * and not completed quiz
		 *
		 * @editor tungnx
		 */
		if ( ! $item->get_viewing_question() && ! $user->has_completed_quiz( $quiz->get_id(), get_the_ID() ) ) {
			learn_press_get_template( 'content-quiz/description.php' );
		}
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_question_title' ) ) {

	function learn_press_content_item_summary_question_title() {
		$quiz = LP_Global::course_item_quiz();

		if ( $question = $quiz->get_viewing_question() ) {
			$title = $question->get_title( 'display' );
			learn_press_get_template( 'content-question/title.php', array( 'title' => $title ) );
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

		if ( ! $user ) {
			return;
		}

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
			learn_press_get_template( 'content-question/content.php', array( 'question' => $question ) );
		}
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_question_content' ) ) {

	function learn_press_content_item_summary_question_content() {
		$quiz = LP_Global::course_item_quiz();

		if ( $question = $quiz->get_viewing_question() ) {

			$content = $question->get_content();

			if ( ! $content ) {
				return;
			}

			learn_press_get_template( 'content-question/description.php', array( 'content' => $content ) );
		}
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_question' ) ) {

	/**
	 * Render content if quiz question.
	 */
	function learn_press_content_item_summary_question() {
		$quiz = LP_Global::course_item_quiz();
		if ( $question = $quiz->get_viewing_question() ) {
			$course      = LP_Global::course();
			$user        = LP_Global::user();
			$answered    = false;
			$course_data = $user->get_course_data( $course->get_id() );

			if ( $user_quiz = $course_data->get_item_quiz( $quiz->get_id() ) ) {
				$answered = $user_quiz->get_question_answer( $question->get_id() );
				$question->show_correct_answers( $user->has_checked_answer( $question->get_id(), $quiz->get_id(),
					$course->get_id() ) ? 'yes' : false );
				$question->disable_answers( $user_quiz->get_status() == 'completed' ? 'yes' : false );
				$question->set_course( $course );
			}

			$question->render( $answered );
		}
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_question_explanation' ) ) {

	/**
	 * Render content if quiz question.
	 */
	function learn_press_content_item_summary_question_explanation() {
		$quiz = LP_Global::course_item_quiz();
		if ( $question = $quiz->get_viewing_question() ) {
			$course = LP_Global::course();
			$user   = LP_Global::user();

			if ( ! $user ) {
				return;
			}

			$course_data = $user->get_course_data( $course->get_id() );
			$user_quiz   = $course_data->get_item_quiz( $quiz->get_id() );
			$explanation = $question->get_explanation();

			if ( ! $explanation ) {
				return;
			}

			/**
			 * Show explanation of question if
			 *
			 * 1. Click check answer check button (Option 'Show Check Answer' value > 1)
			 * OR
			 * 2. Question answered is true
			 * OR
			 * 3. Option 'Review Questions' enable
			 * AND
			 * 3.1. Not retake OR Option 'Show Correct Answer' enable
			 */
			if ( $user_quiz->has_checked_question( $question->get_id() ) ||
			     $user_quiz->is_answered_true( $question->get_id() ) ||
			     ( learn_press_is_review_questions() &&
			       ( ! $user->can_retake_quiz( $quiz->get_id(), $course->get_id() ) ||
			         $quiz->get_show_result() ) ) ) {
				learn_press_get_template( 'content-question/explanation.php', array( 'explanation' => $explanation ) );
			}
		}
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_question_hint' ) ) {

	/**
	 * Render content if quiz question.
	 */
	function learn_press_content_item_summary_question_hint() {
		$quiz = LP_Global::course_item_quiz();
		if ( $question = $quiz->get_viewing_question() ) {
			$course = LP_Global::course();
			$user   = LP_Global::user();

			if ( ! $user ) {
				return;
			}

			$course_data = $user->get_course_data( $course->get_id() );
			$user_quiz   = $course_data->get_item_quiz( $quiz->get_id() );
			$hint        = $question->get_hint();

			if ( ! $hint || ! $user_quiz->has_hinted_question( $question->get_id() ) || $user_quiz->has_checked_question( $question->get_id() ) ) {
				return;
			}

			learn_press_get_template( 'content-question/hint.php', array( 'hint' => $hint ) );
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

		if ( ! $user ) {
			return;
		}

		if ( ! $user->has_quiz_status( array( 'started', 'completed' ), $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( ! $quiz->get_viewing_question() ) {
			return;
		}

		$questions = $quiz->get_questions();

		if ( ! $questions ) {
			return;
		}

		$questions = array_values( $questions );

		if ( count( $questions ) < 2 ) {
			return;
		}

		$args = array(
			'quiz'      => $quiz,
			'questions' => $questions
		);

		learn_press_get_template( 'content-quiz/question-numbers.php', $args );
	}
}

if ( ! function_exists( 'learn_press_content_item_summary_quiz_buttons' ) ) {

	function learn_press_content_item_summary_quiz_buttons() {
		learn_press_get_template( 'content-quiz/buttons.php' );
	}
}


if ( ! function_exists( 'learn_press_quiz_nav_buttons' ) ) {

	function learn_press_quiz_nav_buttons() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
		$quiz   = LP_Global::course_item_quiz();

		if ( ! $user ) {
			return;
		}

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

		if ( ! $user ) {
			return;
		}

		if ( $user->has_course_status( $course->get_id(), array( 'finished' ) ) || $user->has_quiz_status( array(
				'started',
				'completed'
			), $quiz->get_id(), $course->get_id() )
		) {
			return;
		}

		if ( ! $user->has_course_status( $course->get_id(),
				array( 'enrolled' ) ) && $course->is_required_enroll() && ! $quiz->get_preview() ) {
			return;
		}

		// Check quiz has any question
		if ( $quiz->count_questions() == 0 ) {
			return;
		}

		$args = array(
			'course' => $course,
			'quiz'   => $quiz,
		);

		learn_press_get_template( 'content-quiz/buttons/start.php', $args );
	}
}

if ( ! function_exists( 'learn_press_quiz_continue_button' ) ) {

	function learn_press_quiz_continue_button() {
		$course = LP_Global::course();
		$user   = LP_Global::user();
		$quiz   = LP_Global::course_item_quiz();

		if ( ! $user ) {
			return;
		}

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

		if ( ! $user ) {
			return;
		}

		if ( $user->has_course_status( $course->get_id(), array( 'finished' ) ) || ! $user->has_quiz_status( 'started',
				$quiz->get_id(), $course->get_id() ) ) {
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

		if ( ! $user ) {
			return;
		}

		if ( ! $user->has_quiz_status( 'completed', $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( $user->has_course_status( $course->get_id(),
				array( 'finished' ) ) || ! $user->can_retake_quiz( $quiz->get_id(), $course->get_id() ) ) {
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

		if ( ! $user ) {
			return;
		}

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

		if ( ! $user ) {
			return;
		}

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

		if ( ! $user ) {
			return;
		}

		if ( ! $quiz->is_viewing_question() ) {
			return;
		}

		if ( ! $user->can_check_answer( $quiz->get_id(), $course->get_id() ) ) {
			return;
		}

		if ( $user->has_course_status( $course->get_id(), array( 'finished' ) ) || ! $user->has_quiz_status( 'started',
				$quiz->get_id(), $course->get_id() ) ) {
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

		if ( ! $quiz ) {
			return;
		}

		if ( ! $quiz->is_viewing_question() ) {
			return;
		}

		$hint = $question->get_hint();
		if ( ! $hint ) {
			return;
		}

		if ( ! $user ) {
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

		learn_press_get_template( 'content-quiz/buttons/hint.php', array( 'hint' => $hint ) );
	}
}

if ( ! function_exists( 'learn_press_content_item_body_class' ) ) {

	/**
	 * Add custom classes into body tag in case user is viewing an item.
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	function learn_press_content_item_body_class( $classes ) {
		global $lp_course_item;

		if ( $lp_course_item ) {
			$classes[] = 'course-item-popup';
			$classes[] = 'viewing-course-item';
			$classes[] = 'viewing-course-item-' . $lp_course_item->get_id();
			$classes[] = 'course-item-' . $lp_course_item->get_item_type();
		}

		return $classes;
	}

}

if ( ! function_exists( 'learn_press_content_item_edit_links' ) ) {
	/**
	 * Add edit links for course item question to admin bar.
	 */
	function learn_press_content_item_edit_links() {
		global $wp_admin_bar, $post, $lp_course_item, $lp_quiz_question;

		if ( ! ( ! is_admin() && is_user_logged_in() ) ) {
			return;
		}

		if ( is_learnpress() && $post && $post->ID === 0 ) {
			// This also remove the 'Edit Category' link when viewing course category!!!
			//$wp_admin_bar->remove_node( 'edit' );
		}

		if ( ! is_user_member_of_blog() && ! is_super_admin() ) {
			//return;
		}

		/**
		 * Edit link for lesson/quiz or any other course's item.
		 */
		if ( $lp_course_item && ( $post_type_object = get_post_type_object( $lp_course_item->get_item_type() ) )
		     && current_user_can( 'edit_post', $lp_course_item->get_id() )
		     && $post_type_object->show_in_admin_bar
		     && $edit_post_link = get_edit_post_link( $lp_course_item->get_id() )
		) {
			$type = get_post_type( $lp_course_item->get_id() );

			if ( apply_filters( 'learn-press/edit-admin-bar-button', true, $lp_course_item ) ) {
				$wp_admin_bar->add_menu( array(
					'id'    => 'edit-' . $type,
					'title' => $post_type_object->labels->edit_item,
					'href'  => $edit_post_link
				) );
			}
		}

		/**
		 * Edit link for quiz's question.
		 */
		if ( $lp_quiz_question ) {
			if ( ( $post_type_object = get_post_type_object( $lp_quiz_question->get_item_type() ) )
			     && current_user_can( 'edit_post', $lp_quiz_question->get_id() )
			     && $post_type_object->show_in_admin_bar
			     && $edit_post_link = get_edit_post_link( $lp_quiz_question->get_id() )
			) {
				$type = get_post_type( $lp_quiz_question->get_id() );
				$wp_admin_bar->add_menu( array(
					'id'    => 'edit-' . $type,
					'title' => $post_type_object->labels->edit_item,
					'href'  => $edit_post_link
				) );
			}
		}


	}
}

add_filter( 'admin_bar_menu', 'learn_press_content_item_edit_links', 90 );

if ( ! function_exists( 'learn_press_control_displaying_course_item' ) ) {
	/**
	 * If user is viewing content of an item instead of the whole course
	 * then remove all content of course and replace with content of
	 * that item.
	 */
	function learn_press_control_displaying_course_item() {
		global $wp_filter;

		// Remove all hooks added to content of whole course.
		$hooks = array( 'content-learning-summary', 'content-landing-summary' );

		if ( empty( $wp_filter['learn-press-backup-hooks'] ) ) {
			$wp_filter['learn-press-backup-hooks'] = array();
		}

		foreach ( $hooks as $hook ) {
			if ( isset( $wp_filter["learn-press/{$hook}"] ) ) {
				// Move to backup to restore it if needed.
				$wp_filter['learn-press-backup-hooks']["learn-press/{$hook}"] = $wp_filter["learn-press/{$hook}"];

				// Remove the origin hook
				unset( $wp_filter["learn-press/{$hook}"] );
			}
		}

		// Add more assets into page that displaying content of an item
		add_filter( 'body_class', 'learn_press_content_item_body_class', 10 );
	}
}

if ( ! function_exists( 'learn_press_profile_tab_orders' ) ) {
	function learn_press_profile_tab_orders() {
		learn_press_get_template( 'profile/tabs/orders/list.php' );
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

if ( ! function_exists( 'learn_press_single_course_args' ) ) {
	function learn_press_single_course_args() {
		static $output = array();
		if ( ! $output ) {
			if ( ( $course = LP_Global::course() ) && $course->get_id() ) {
				$user = LP_Global::user();

				if ( ! $user ) {
					return;
				}

				if ( $course_data = $user->get_course_data( $course->get_id() ) ) {
					$output = $course_data->get_js_args();
				}
			}
		}

		return $output;
	}
}

if ( ! function_exists( 'learn_press_single_quiz_args' ) ) {
	function learn_press_single_quiz_args() {
		$args = array();

		if ( $quiz = LP_Global::course_item_quiz() ) {
			$user = LP_Global::user();

			if ( ! $user ) {
				return;
			}

			if ( $user_quiz = $user->get_item_data( $quiz->get_id(), LP_Global::course( true ) ) ) {
				$remaining_time = $user_quiz->get_time_remaining();
			} else {
				$remaining_time = false;
			}
			$args = array(
				'id'            => $quiz->get_id(),
				'totalTime'     => $quiz->get_duration()->get(),
				'remainingTime' => $remaining_time ? $remaining_time->get() : $quiz->get_duration()->get(),
				'status'        => $user->get_item_status( $quiz->get_id(), LP_Global::course( true ) ),
				'daysLeft'      => _x( 'days left', 'quiz_duration', 'learnpress' ),
				'dayLeft'       => _x( 'day left', 'quiz_duration', 'learnpress' )
			);
		}

		return $args;
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

if ( ! function_exists( 'learn_press_breadcrumb' ) ) {
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

if ( ! function_exists( 'learn_press_course_finish_button' ) ) {
	/**
	 * Display course curriculum
	 */
	function learn_press_course_finish_button() {
		learn_press_get_template( 'single-course/buttons/finish.php' );
	}
}

if ( ! function_exists( 'learn_press_course_curriculum' ) ) {
	/**
	 * Display course curriculum
	 */
	function learn_press_course_curriculum() {
		///learn_press_get_template( 'single-course/curriculum.php' );
	}
}

if ( ! function_exists( 'learn_press_course_categories' ) ) {
	/**
	 * Display course categories
	 */
	function learn_press_course_categories() {
		learn_press_get_template( 'single-course/categories.php' );
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

if ( ! function_exists( 'learn_press_course_buttons' ) ) {
	/**
	 * Display course retake button
	 */
	function learn_press_course_buttons() {
		learn_press_get_template( 'single-course/buttons.php' );
	}
}

if ( ! function_exists( 'learn_press_course_remaining_time' ) ) {

	function learn_press_course_remaining_time() {

		if ( ! $course = LP_Global::course() ) {
			return;
		}

		if ( ! $user = LP_Global::user() ) {
			return;
		}

		if ( false === ( $remain = $user->get_course_remaining_time( $course->get_id() ) ) ) {
			return;
		}

		if ( $user->has_finished_course( $course->get_id() ) ) {
			return;
		}

		learn_press_get_template( 'single-course/remaining-time.php', array( 'remaining_time' => $remain ) );
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
		//learn_press_get_template( 'single-course/content-lesson.php' );
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

if ( ! function_exists( 'learn_press_section_item_meta' ) ) {
	/**
	 * @param object
	 * @param array
	 * @param LP_Course
	 */
	function learn_press_section_item_meta( $item, $section ) {
		learn_press_get_template( 'single-course/section/item-meta.php', array(
			'item'    => $item,
			'section' => $section
		) );
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

		learn_press_get_template( 'order/order-details.php', array(
			'order' => learn_press_get_order( $order_id )
		) );
	}
}

if ( ! function_exists( 'learn_press_checkout_user_form' ) ) {
	/**
	 * Output login/register form before order review if user is not logged in
	 */
	function learn_press_checkout_user_form() {
		//learn_press_get_template( 'checkout/user-form.php' );
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

if ( ! function_exists( 'learn_press_enroll_script' ) ) {
	/**
	 */
	function learn_press_enroll_script() {
		learn_press_assets()->enqueue_script( 'learn-press-enroll', LP()->plugin_url( 'assets/js/frontend/enroll.js' ),
			array( 'learn-press-js' ) );
	}
}

if ( ! function_exists( 'learn_press_output_user_profile_tabs' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_output_user_profile_tabs( $user, $current, $tabs ) {
		learn_press_get_template( 'profile/tabs.php', array(
			'user'    => $user,
			'tabs'    => $tabs,
			'current' => $current
		) );
	}
}

if ( ! function_exists( 'learn_press_output_user_profile_order' ) ) {
	/**
	 * Display user profile tabs
	 *
	 * @param LP_User
	 */
	function learn_press_output_user_profile_order( $user, $current, $tabs ) {

		//		learn_press_get_template( 'profile/tabs/orders.php', array( 'user' => $user, 'tabs' => $tabs, 'current' => $current ) );
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

if ( ! function_exists( 'learn_press_profile_tab_courses_learning' ) ) {
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

if ( ! function_exists( 'learn_press_profile_tab_courses_purchased' ) ) {
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

if ( ! function_exists( 'learn_press_profile_tab_courses_finished' ) ) {
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

if ( ! function_exists( 'learn_press_profile_tab_courses_own' ) ) {
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

if ( ! function_exists( 'learn_press_after_profile_tab_loop_course' ) ) {
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


if ( ! function_exists( 'learn_press_output_user_profile_info' ) ) {
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
if ( ! function_exists( 'learn_press_single_quiz_title' ) ) {
	/**
	 * Output the title of the quiz
	 */
	function learn_press_single_quiz_title() {
		learn_press_get_template( 'content-quiz/title.php' );
	}
}

if ( ! function_exists( 'learn_press_course_loop_item_buttons' ) ) {
	function learn_press_course_loop_item_buttons() {
		learn_press_get_template( 'single-course/buttons.php' );
	}
}

if ( ! function_exists( 'learn_press_course_loop_item_user_progress' ) ) {
	function learn_press_course_loop_item_user_progress() {
		$course = LP_Global::course();
		$user   = LP_Global::user();

		if ( ! $course || ! $user ) {
			return;
		}

		if ( $user && $user->has_enrolled_course( $course->get_id() ) ) {
			$user->get_course_status( $course->get_id() );
		}
	}
}

if ( ! function_exists( 'learn_press_course_item_class' ) ) {
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

if ( ! function_exists( 'learn_press_course_lesson_class' ) ) {
	/**
	 * The class of lesson in course curriculum
	 *
	 * @param int $lesson_id
	 * @param int $course_id
	 * @param array|string $class
	 * @param boolean $echo
	 *
	 * @return mixed
	 */
	function learn_press_course_lesson_class( $lesson_id = null, $course_id = 0, $class = null, $echo = true ) {
		$user = learn_press_get_current_user();
		if ( ! $course_id ) {
			$course_id = get_the_ID();
		}

		$course = learn_press_get_course( $course_id );
		if ( ! $course ) {
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

		$user = learn_press_get_current_user();

		if ( $status = $user->get_item_status( $lesson_id ) ) {
			$classes[] = "item-has-status item-{$status}";
		}
		if ( $lesson_id && $course->is_current_item( $lesson_id ) ) {
			$classes[] = 'item-current';
		}
		if ( learn_press_is_course() ) {
			if ( $course->is_free() ) {
				$classes[] = 'free-item';
			}
		}
		$lesson = LP_Lesson::get_lesson( $lesson_id );
		if ( $lesson && $lesson->is_preview() ) {
			$classes[] = 'preview-item';
		}

		if ( $user->can_view_item( $lesson_id, $course_id ) ) {
			$classes[] = 'viewable';
		}

		$classes = array_unique( array_merge( $classes, $class ) );
		if ( $echo ) {
			echo 'class="' . implode( ' ', $classes ) . '"';
		}

		return $classes;
	}
}

if ( ! function_exists( 'learn_press_course_quiz_class' ) ) {
	/**
	 * The class of lesson in course curriculum
	 *
	 * @param int $quiz_id
	 * @param int $course_id
	 * @param string|array $class
	 * @param boolean $echo
	 *
	 * @return mixed
	 */
	function learn_press_course_quiz_class( $quiz_id = null, $course_id = 0, $class = null, $echo = true ) {
		$user = learn_press_get_current_user();
		if ( ! $course_id ) {
			$course_id = get_the_ID();
		}
		if ( is_string( $class ) && $class ) {
			$class = preg_split( '!\s+!', $class );
		} else {
			$class = array();
		}

		$course = learn_press_get_course( $course_id );
		if ( ! $course ) {
			return '';
		}

		$classes = array(
			'course-quiz course-item course-item-' . $quiz_id
		);

		if ( $status = $user->get_item_status( $quiz_id ) ) {
			$classes[] = "item-has-status item-{$status}";
		}

		if ( $quiz_id && $course->is_current_item( $quiz_id ) ) {
			$classes[] = 'item-current';
		}

		if ( $user->can_view_item( $quiz_id, $course_id ) ) {
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

if ( ! function_exists( 'learn_press_message' ) ) {
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


if ( ! function_exists( 'learn_press_course_class' ) ) {
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
		if ( ! $post_id || 'lp_course' !== get_post_type( $post_id ) ) {
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

	$object = null;

	if ( is_int( $post ) ) {
		$post = get_post( $post );
	}

	if ( ! $post ) {
		return $object;
	}

	if ( $post->post_type == LP_COURSE_CPT ) {
		if ( isset( $GLOBALS['course'] ) ) {
			unset( $GLOBALS['course'] );
		}

		$object = learn_press_get_course( $post );
		// $object->prepare();
		LP()->global['course'] = $GLOBALS['course'] = $GLOBALS['lp_course'] = $object;
	}


	return $object;
}

add_action( 'the_post', 'learn_press_setup_object_data' );

function learn_press_setup_user() {
	$GLOBALS['lp_user'] = learn_press_get_current_user();
}

if ( ! is_admin() ) {
	add_action( 'init', 'learn_press_setup_user', 1000 );
}

/**
 * Display a message immediately with out push into queue
 *
 * @param        $message
 * @param string $type
 */

function learn_press_display_message( $message, $type = 'success' ) {

	// get all messages added into queue
	$messages = learn_press_session_get( learn_press_session_message_id() );
	learn_press_session_set( learn_press_session_message_id(), null );

	// add new notice and display
	learn_press_add_message( $message, $type );
	echo learn_press_get_messages( true );

	// store back messages
	learn_press_session_set( learn_press_session_message_id(), $messages );
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

/**
 * Add new message into queue for displaying.
 *
 * @param string $message
 * @param string $type
 * @param array $options
 * @param int|bool $current_user . @since 3.0.9 - add for current user only
 */
function learn_press_add_message( $message, $type = 'success', $options = array(), $current_user = true ) {

	if ( is_string( $options ) ) {
		$options = array( 'id' => $options );
	}

	$options = wp_parse_args(
		$options,
		array(
			'id' => ''
		)
	);

	if ( $current_user ) {
		if ( true === $current_user ) {
			$current_user = get_current_user_id();
		}
	}

	$key = "messages{$current_user}";

	$messages = learn_press_session_get( $key );

	if ( empty( $messages[ $type ] ) ) {
		$messages[ $type ] = array();
	}

	$messages[ $type ][ $options['id'] ] = array( 'content' => $message, 'options' => $options );

	learn_press_session_set( $key, $messages );
}

function learn_press_get_message( $message, $type = 'success' ) {
	ob_start();
	learn_press_display_message( $message, $type );
	$message = ob_get_clean();

	return $message;
}

/**
 * Remove message added into queue by id and/or type.
 *
 * @param string $id
 * @param string|array $type
 *
 * @since 3.0.0
 *
 */
function learn_press_remove_message( $id = '', $type = '' ) {
	if ( ! $groups = learn_press_session_get( learn_press_session_message_id() ) ) {
		return;
	}

	settype( $type, 'array' );

	if ( $id ) {
		foreach ( $groups as $message_type => $messages ) {
			if ( ! sizeof( $type ) ) {
				if ( isset( $groups[ $message_type ][ $id ] ) ) {
					unset( $groups[ $message_type ][ $id ] );
				}
			} elseif ( in_array( $message_type, $type ) ) {
				if ( isset( $groups[ $message_type ][ $id ] ) ) {
					unset( $groups[ $message_type ][ $id ] );
				}
			}
		}
	} elseif ( sizeof( $type ) ) {
		foreach ( $type as $t ) {
			if ( isset( $groups[ $t ] ) ) {
				unset( $groups[ $t ] );
			}
		}
	} else {
		$groups = array();
	}

	learn_press_session_set( learn_press_session_message_id(), $groups );
}

/**
 * Print out the message stored in the queue
 *
 * @param bool
 */
function learn_press_print_messages( $clear = true ) {
	$messages = learn_press_session_get( learn_press_session_message_id() );
	learn_press_get_template( 'global/message.php', array( 'messages' => $messages ) );
	if ( $clear ) {
		learn_press_session_set( learn_press_session_message_id(), array() );
	}
}

function learn_press_message_count( $type = '' ) {
	$count    = 0;
	$messages = learn_press_session_get( learn_press_session_message_id(), array() );

	if ( isset( $messages[ $type ] ) ) {
		$count = absint( sizeof( $messages[ $type ] ) );
	} elseif ( empty( $type ) ) {
		foreach ( $messages as $message ) {
			$count += absint( sizeof( $message ) );
		}
	}

	return $count;
}

function learn_press_session_message_id() {
	return "messages" . get_current_user_id();
}

function learn_press_clear_messages() {
	_deprecated_function( __FUNCTION__, '3.0.0', 'learn_press_remove_message' );
	learn_press_remove_message();
}

/**
 * Displays messages before main content
 */
function _learn_press_print_messages() {
	$item = LP_Global::course_item();
	if ( ( 'learn_press_before_main_content' == current_action() ) && $item ) {
		return;
	}
	learn_press_print_messages( true );
}

add_action( 'learn_press_before_main_content', '_learn_press_print_messages', 50 );
add_action( 'learn-press/before-course-item-content', '_learn_press_print_messages', 50 );

if ( ! function_exists( 'learn_press_page_controller' ) ) {
	/**
	 * Check permission to view page
	 *
	 * @param file $template
	 *
	 * @return file
	 */
	function learn_press_page_controller( $template/*, $slug, $name*/ ) {
		die( __FUNCTION__ );
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
					if ( ! $user->can_view_quiz( $quiz->id ) ) {
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
					$redirect = apply_filters( 'learn_press_quiz_access_denied_redirect_permalink', $redirect,
						$quiz_status, $quiz->id, $user->get_id() );
					break;
				case LP_COURSE_CPT:
					if ( ( $course = learn_press_get_course() ) && $item_id = $course->is_viewing_item() ) {
						if ( ! LP()->user->can_view_item( $item_id ) ) {
							$redirect = apply_filters( 'learn_press_lesson_access_denied_redirect_permalink',
								$course->permalink, $item_id, $user->get_id() );
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

if ( ! function_exists( 'learn_press_page_title' ) ) {

	/**
	 * learn_press_page_title function.
	 *
	 * @param boolean $echo
	 *
	 * @return string
	 */
	function learn_press_page_title( $echo = true ) {

		if ( is_search() ) {
			$page_title = sprintf( __( 'Search Results for: &ldquo;%s&rdquo;', 'learnpress' ), get_search_query() );

			if ( get_query_var( 'paged' ) ) {
				$page_title .= sprintf( __( '&nbsp;&ndash; Page %s', 'learnpress' ), get_query_var( 'paged' ) );
			}

		} elseif ( is_tax() ) {

			$page_title = single_term_title( "", false );

		} else {

			$courses_page_id = learn_press_get_page_id( 'courses' );
			$page_title      = get_the_title( $courses_page_id );

		}

		$page_title = apply_filters( 'learn_press_page_title', $page_title );

		if ( $echo ) {
			echo $page_title;
		} else {
			return $page_title;
		}
	}
}

function learn_press_template_redirect() {
	global $wp_query, $wp;

	// When default permalinks are enabled, redirect shop page to post type archive url
	if ( ! empty( $_GET['page_id'] ) && get_option( 'permalink_structure' ) == "" && $_GET['page_id'] == learn_press_get_page_id( 'courses' ) ) {
		wp_safe_redirect( get_post_type_archive_link( 'lp_course' ) );
		exit;
	}
}

add_action( 'template_redirect', 'learn_press_template_redirect' );


/**
 * get template part
 *
 * @param string $slug
 * @param string $name
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
	if ( ! $template && $name && file_exists( LP_PLUGIN_PATH . "/templates/{$slug}-{$name}.php" ) ) {
		$template = LP_PLUGIN_PATH . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/learnpress/slug.php
	if ( ! $template ) {
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
 * @param array $args (default: array())
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 *
 * @return void
 */
function learn_press_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( $args && is_array( $args ) ) {
		extract( $args );
	}

	if ( false === strpos( $template_name, '.php' ) ) {
		$template_name .= '.php';
	}

	$located = learn_press_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $located ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );

		return;
	}
	// Allow 3rd party plugin filter template file from their plugin
	$located = apply_filters( 'learn_press_get_template', $located, $template_name, $args, $template_path,
		$default_path );
	if ( $located != '' ) {
		do_action( 'learn_press_before_template_part', $template_name, $template_path, $located, $args );

		include( $located );

		do_action( 'learn_press_after_template_part', $template_name, $template_path, $located, $args );
	}
}

/**
 * Get template content
 *
 * @param        $template_name
 * @param array $args
 * @param string $template_path
 * @param string $default_path
 *
 * @return string
 * @uses learn_press_get_template();
 *
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
 * @param string $default_path (default: '')
 *
 * @return string
 */
function learn_press_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = learn_press_template_path();
	}

	if ( ! $default_path ) {
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
	if ( ! $template ) {
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


if ( ! function_exists( 'learn_press_is_404' ) ) {
	/**
	 * Set header is 404
	 */
	function learn_press_is_404() {
		global $wp_query;
		if ( ! empty( $_REQUEST['debug-404'] ) ) {
			learn_press_debug( debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, $_REQUEST['debug-404'] ) );
		}
		$wp_query->set_404();
		status_header( 404 );
	}
}

if ( ! function_exists( 'learn_press_404_page' ) ) {
	/**
	 * Display 404 page
	 */
	function learn_press_404_page() {
		learn_press_is_404();
	}
}

if ( ! function_exists( 'learn_press_generate_template_information' ) ) {
	function learn_press_generate_template_information( $template_name, $template_path, $located, $args ) {
		$debug = learn_press_get_request( 'show-template-location' );
		if ( $debug == 'on' ) {
			echo "<!-- Template Location:" . str_replace( array( LP_PLUGIN_PATH, ABSPATH ), '', $located ) . " -->";
		}
	}
}

if ( ! function_exists( 'learn_press_course_remaining_time' ) ) {
	/**
	 * Show the time remain of a course
	 */
	function learn_press_course_remaining_time() {
		$user = learn_press_get_current_user();
		if ( ! $user->has_finished_course( get_the_ID() ) && $text = learn_press_get_course( get_the_ID() )->get_user_duration_html( $user->get_id() ) ) {
			learn_press_display_message( $text );
		}
	}
}

//add_filter( 'template_include', 'learn_press_permission_view_quiz', 100 );
function learn_press_permission_view_quiz( $template ) {
	$quiz = LP()->global['course-item'];
	// if is not in single quiz
	if ( ! learn_press_is_quiz() ) {
		return $template;
	}
	$user = learn_press_get_current_user();
	// If user haven't got permission
	if ( ! current_user_can( 'edit-lp_quiz' ) && ! $user->can_view_quiz( $quiz->id ) ) {
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


if ( ! function_exists( 'learn_press_item_meta_type' ) ) {
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
	_deprecated_function( __FUNCTION__, '3.0.0' );
	if ( ! learn_press_is_course() ) {
		return;
	}
	$user   = learn_press_get_current_user();
	$course = learn_press_get_course();
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
				'viewable'  => $item->post_type == 'lp_quiz' ? ( $user->can_view_quiz( $item->ID,
						$course->get_id() ) !== false ) : ( $user->can_view_lesson( $item->ID,
						$course->get_id() ) !== false )
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

if ( ! function_exists( 'learn_press_sort_course_tabs' ) ) {

	function learn_press_sort_course_tabs( $tabs = array() ) {
		uasort( $tabs, 'learn_press_sort_list_by_priority_callback' );

		return $tabs;
	}
}

if ( ! function_exists( 'learn_press_get_profile_display_name' ) ) {
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
		$id = '';
		if ( $user instanceof LP_Abstract_User ) {
			$id = $user->get_id();
		} elseif ( $user instanceof WP_User ) {
			$id = $user->ID;
		} elseif ( is_numeric( $user ) ) {
			$id = $user;
		}
		if ( ! isset( $id ) ) {
			return '';
		}
		$info = get_userdata( $id );

		return $info ? $info->display_name : '';
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
		if ( 'yes' !== LP()->settings()->get( 'enable_register_profile' ) ) {
			return;
		}

		$profile = LP_Global::profile();

		if ( ! $profile->get_user()->is_guest() ) {
			return;
		}

		if ( ! $fields = $profile->get_register_fields() ) {
			return;
		}

		learn_press_get_template( 'global/form-register.php', array( 'fields' => $fields ) );
	}
}

if ( ! function_exists( 'learn_press_content_item_lesson_title' ) ) {
	function learn_press_content_item_lesson_title() {
		$item         = LP_Global::course_item();
		$lesson_title = $item->get_title( 'display' );

		if ( ( 'standard' !== ( $format = $item->get_format() ) ) && file_exists( $format_template = learn_press_locate_template( "content-lesson/{$format}/title.php" ) ) ) {
			include $format_template;

			return;
		}

		if ( ! $lesson_title ) {
			return;
		}

		learn_press_get_template( 'content-lesson/title.php', array( 'title' => $lesson_title ) );
	}
}

if ( ! function_exists( 'learn_press_content_item_lesson_content' ) ) {
	function learn_press_content_item_lesson_content() {
		$item    = LP_Global::course_item();
		$content = $item->get_content();

		if ( ( 'standard' !== ( $format = $item->get_format() ) ) && file_exists( $format_template = learn_press_locate_template( "content-lesson/{$format}/content.php" ) ) ) {
			include $format_template;

			return;
		}

		do_action( 'learn-press/lesson-start', $item );
		learn_press_get_template( 'content-lesson/content.php', array( 'item' => $item, 'content' => $content ) );
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
		$user         = LP_Global::user();
		$course       = LP_Global::course();
		$item         = LP_Global::course_item();
		$completed    = $user->has_completed_item( $item->get_id(), $course->get_id() );
		$security     = $item->create_nonce( 'complete' );
		$has_enrolled = $user->has_enrolled_course( $course->get_id() );

		if ( ! $course->is_required_enroll() ) {
			return;
		}

		if ( ! $has_enrolled ) {
			return;
		}

		if ( ( $course_item = $user->get_course_data( $course->get_id() ) ) && $course_item->is_finished() ) {
			return;
		}

		$args = array(
			'course'    => $course,
			'item'      => $item,
			'completed' => $completed,
			'security'  => $security,
		);

		learn_press_get_template( 'content-lesson/button-complete.php', $args );
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

if ( ! function_exists( 'learn_press_content_item_review_quiz_title' ) ) {
	function learn_press_content_item_review_quiz_title() {
		if ( learn_press_is_review_questions() ) {
			learn_press_get_template( 'content-quiz/review-title.php' );
		}
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

		learn_press_get_template( 'global/become-teacher-form/form-fields.php',
			array( 'fields' => learn_press_get_become_a_teacher_form_fields() ) );
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

if ( ! function_exists( 'learn_press_content_item_comments' ) ) {

	function learn_press_content_item_comments() {

		$item = LP_Global::course_item();

		if ( ! $item ) {
			return;
		}

		if ( ! $item->is_support( 'comments' ) ) {
			return;
		}

		global $post;

		$post = get_post( $item->get_id() );

		setup_postdata( $post );

		if ( ! have_comments() ) {
			return;
		}

		comments_template();

		wp_reset_postdata();
	}
}

if ( ! function_exists( 'learn_press_content_item_nav' ) ) {
	function learn_press_content_item_nav() {
		$course    = LP_Global::course();
		$next_item = $prev_item = false;

		if ( $next_id = $course->get_next_item() ) {
			$next_item = $course->get_item( $next_id );
		}
		if ( $prev_id = $course->get_prev_item() ) {
			$prev_item = $course->get_item( $prev_id );
		}

		learn_press_get_template(
			'single-course/content-item/nav.php',
			array(
				'next_item' => $next_item,
				'prev_item' => $prev_item
			)
		);
	}
}

function learn_press_disable_course_comment_form() {
	add_filter( 'comments_template', 'learn_press_blank_comments_template', 999 );
}

function learn_press_course_comments_open( $open, $post_id ) {
	$post = get_post( $post_id );
	if ( LP_COURSE_CPT == $post->post_type ) {
		$open = false;
	}

	return $open;
}


if ( ! function_exists( 'learn_press_profile_mobile_menu' ) ) {
	function learn_press_profile_mobile_menu() {
		learn_press_get_template( 'profile/mobile-menu.php' );
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

function learn_press_is_content_item_only() {
	return ! empty( $_REQUEST['content-item-only'] );
}

function learn_press_label_html( $label, $type = '' ) {
	?>
    <span class="lp-label label-<?php echo esc_attr( $type ? $type : $label ); ?>">
         <?php echo $label; ?>
    </span>
	<?php
}

// Fix issue with course content is duplicated if theme use the_content instead of $course->get_description()
function learn_press_course_the_content( $content ) {
	_deprecated_function( __FUNCTION__, '3.0.0' );
	global $post;
	if ( $post && $post->post_type == 'lp_course' ) {
		$course = learn_press_get_course( $post->ID );
		if ( $course ) {
			remove_filter( 'the_content', 'learn_press_course_the_content', 99999 );
			$content = $course->get_content();
			add_filter( 'the_content', 'learn_press_course_the_content', 99999 );
		}
	}

	return $content;
}

//add_action( 'template_redirect', 'learn_press_check_access_lesson' );

function learn_press_check_access_lesson() {
	$queried_post_type = get_query_var( 'post_type' );
	if ( is_single() && 'lp_lesson' == $queried_post_type ) {
		$course = learn_press_get_course();
		if ( ! $course ) {
			learn_press_is_404();

			return;
		}
		$post     = get_post();
		$user     = learn_press_get_current_user();
		$can_view = $user->can_view_item( $post->ID, $course->get_id() );
		if ( ! $can_view ) {
			learn_press_is_404();

			return;
		}
	} elseif ( is_single() && 'lp_course' == $queried_post_type ) {
		$course = learn_press_get_course();
		$item   = LP()->global['course-item'];
		if ( is_object( $item ) && isset( $item->post->post_type ) && 'lp_lesson' === $item->post->post_type ) {
			$user     = learn_press_get_current_user();
			$can_view = $user->can_view_item( $item->id, $course->get_id() );
			if ( ! $can_view ) {
				learn_press_404_page();

				return;
			}
		}
	}
}

function learn_press_get_course_redirect( $link ) {

	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		return $link;
	}
	$referer = $_SERVER['HTTP_REFERER'];
	$info_a  = parse_url( $referer );
	$info_b  = parse_url( $link );

	$a = explode( '/', $info_a['path'] );
	$a = array_filter( $a );

	$b = explode( '/', $info_b['path'] );
	$b = array_filter( $b );

	$same = array_intersect_assoc( $a, $b );

	$a = array_diff_assoc( $a, $same );
	$b = array_diff_assoc( $b, $same );

	$a = array_values( $a );
	$b = array_values( $b );

	if ( array_shift( $a ) === 'popup' ) {
		unset( $a[0] );
		if ( ! ( array_diff_assoc( $a, $b ) ) ) {
			$link = '';
			foreach ( array( 'scheme', 'host', 'port', 'path' ) as $v ) {
				if ( ! isset( $info_a[ $v ] ) ) {
					continue;
				}

				if ( $v == 'scheme' ) {
					$sep = '://';
				} elseif ( $v == 'host' ) {
					$sep = '';
				} elseif ( $v == 'port' ) {
					$link .= ':';
					$sep  = '';
				} else {
					$sep = '/';
				}
				$link = $link . $info_a[ $v ] . $sep;
			}

			if ( ! empty( $info_b['query'] ) ) {
				$link .= '?' . $info_b['query'];
			}

			if ( ! empty( $info_b['fragment'] ) ) {
				$link .= '#' . $info_b['fragment'];
			}
		}
	}

	return $link;
}

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

/**
 * @param LP_Quiz $item
 */
function learn_press_quiz_meta_questions( $item ) {
	$count = $item->count_questions();
	echo '<span class="item-meta count-questions">' . sprintf( $count ? _n( '%d question', '%d questions', $count,
			'learnpress' ) : __( '%d question', 'learnpress' ), $count ) . '</span>';
}

/**
 * @param LP_Quiz|LP_Lesson $item
 */
function learn_press_item_meta_duration( $item ) {
	$duration = $item->get_duration();

	if ( is_a( $duration, 'LP_Duration' ) && $duration->get() ) {
		$format = array(
			'day'    => '%s ' . _x( 'day', 'duration', 'learnpress' ),
			'hour'   => '%s ' . _x( 'hour', 'duration', 'learnpress' ),
			'minute' => '%s ' . _x( 'min', 'duration', 'learnpress' ),
			'second' => '%s ' . _x( 'sec', 'duration', 'learnpress' ),
		);
		echo '<span class="item-meta duration">' . $duration->to_timer( $format, true ) . '</span>';
	} elseif ( is_string( $duration ) && strlen( $duration ) ) {
		echo '<span class="item-meta duration">' . $duration . '</span>';
	}
}

function learn_press_course_item_edit_link( $item_id, $course_id ) {
	$user = learn_press_get_current_user();
	if ( $user->can_edit_item( $item_id, $course_id ) ): ?>
        <p class="edit-course-item-link">
            <a href="<?php echo get_edit_post_link( $item_id ); ?>"><?php _e( 'Edit this item', 'learnpress' ); ?></a>
        </p>
	<?php endif;
}

function learn_press_comments_template_query_args( $comment_args ) {
	$post_type = get_post_type( $comment_args['post_id'] );
	if ( $post_type == LP_COURSE_CPT ) {
		$comment_args['type__not_in'] = 'review';
	}

	return $comment_args;
}

if ( ! function_exists( 'learn_press_filter_get_comments_number' ) ) {
	function learn_press_filter_get_comments_number( $count, $post_id = 0 ) {
		global $wpdb;

		if ( ! $post_id ) {
			$post_id = learn_press_get_course_id();
		}

		if ( ! $post_id ) {
			return $count;
		}

		if ( get_post_type( $post_id ) == LP_COURSE_CPT ) {
			$sql = $wpdb->prepare(
				" SELECT count(*) "
				. " FROM {$wpdb->comments} "
				. " WHERE comment_post_ID = %d "
				. " AND comment_approved = 1 "
				. " AND comment_type != %s ", $post_id, 'review' );

			$count = $wpdb->get_var( $sql );

			// @deprecated
			$count = apply_filters( 'learn_press_get_comments_number', $count, $post_id );

			// @since 3.0.0
			$count = apply_filters( 'learn-press/course-comments-number', $count, $post_id );
		}

		return $count;
	}
}

if ( ! function_exists( 'learn_press_back_to_class_button' ) ) {
	function learn_press_back_to_class_button() {
		$courses_link = learn_press_get_page_link( 'courses' );
		if ( ! $courses_link ) {
			return;
		}
		?>

        <a href="<?php echo learn_press_get_page_link( 'courses' ); ?>"><?php _e( 'Back to class',
				'learnpress' ); ?></a>
		<?php
	}
}

if ( ! function_exists( 'learn_press_reset_single_item_summary_content' ) ) {
	function learn_press_reset_single_item_summary_content() {
		if ( isset( $_REQUEST['content-only'] ) ) {
			global $wp_filter;
			if ( isset( $wp_filter['learn-press/single-item-summary'] ) ) {
				unset( $wp_filter['learn-press/single-item-summary'] );
			}

			$course = learn_press_get_course();
			$course->get_curriculum();

			add_action( 'learn-press/single-item-summary', 'learn_press_single_course_content_item', 10 );
		}
	}
}

//function learn_press_course_item_class( $defaults, $this->get_item_type(), $this->get_id()){
//	if ( $course = learn_press_get_course( $course_id ) ) {
//		if ( $this->is_preview() ) {
//			$status_classes[] = 'item-preview';
//		} elseif ( $course->is_free() && ! $course->is_required_enroll() ) {
//			$status_classes[] = 'item-free';
//		}
//	}
//
//	if ( $user = learn_press_get_user( $user_id, ! $user_id ) ) {
//		$item_status = $user->get_item_status( $this->get_id(), $course_id );
//		$item_grade  = $user->get_item_grade( $this->get_id(), $course_id );
//
//		if ( $item_status ) {
//			$status_classes[] = 'course-item-status';
//			///$status_classes[] = 'has-status';
//			$status_classes[] = 'item-' . $item_status;
//		}
//		switch ( $item_status ) {
//			case 'started':
//				break;
//			case 'completed':
//				$status_classes[] = $item_grade;
//		}
//	}
//}
//add_action('learn-press/course-item-class', 'learn_press_course_item_class');

/**
 * Add custom classes to body tag class name
 *
 * @param array $classes
 *
 * @return array
 *
 * @since 3.0.0
 */
function learn_press_body_classes( $classes ) {
	$pages = learn_press_static_page_ids();

	if ( $pages ) {
		$is_lp_page = false;
		settype( $classes, 'array' );

		foreach ( $pages as $slug => $id ) {
			if ( is_page( $id ) ) {
				$classes[]  = $slug;
				$is_lp_page = true;
			}
		}

		if ( $is_lp_page || is_learnpress() ) {
			$classes[] = get_stylesheet();
			$classes[] = 'learnpress';
			$classes[] = 'learnpress-page';
		}
	}

	return $classes;
}

add_filter( 'body_class', 'learn_press_body_classes', 10 );

/**
 * Return true if user is learning a course
 *
 * @param int $course_id
 *
 * @return bool|mixed
 * @since 3.0
 *
 */
function learn_press_is_learning_course( $course_id = 0 ) {
	$user        = learn_press_get_current_user();
	$course      = $course_id ? learn_press_get_course( $course_id ) : LP_Global::course();
	$is_learning = false;
	$has_status  = false;

	if ( $user && $course ) {
		$has_status = $user->has_course_status( $course->get_id(), array(
			'enrolled',
			'finished'
		) );
	}

	if ( $course && ( ! $course->is_required_enroll() || $has_status ) ) {
		$is_learning = true;
	}

	return apply_filters( 'learn-press/is-learning-course', $is_learning );
}

function learn_press_get_color_schemas() {
	$colors = array(
		array(
			'title'     => __( 'Popup links color', 'learnpress' ),
			'id'        => 'popup-links-color',
			'selectors' => array(
				'body.course-item-popup a' => "color"
			),
			'std'       => ''
		),
		array(
			'title'     => __( 'Popup heading background', 'learnpress' ),
			'id'        => 'popup-heading-bg',
			'selectors' => array(
				'#course-item-content-header' => "background-color"
			),
			'std'       => '#e7f7ff'
		),
		array(
			'title'     => __( 'Popup heading color', 'learnpress' ),
			'id'        => 'popup-heading-color',
			'selectors' => array(
				'#course-item-content-header a'                                      => "color",
				'#course-item-content-header .course-item-search input'              => "color",
				'#course-item-content-header .course-item-search input:focus'        => "color",
				'#course-item-content-header .course-item-search input::placeholder' => "color",
				'#course-item-content-header .course-item-search button'             => "color",
			),
			'std'       => ''
		),
		array(
			'title'     => __( 'Popup curriculum background', 'learnpress' ),
			'id'        => 'popup-curriculum-background',
			'selectors' => array(
				'body.course-item-popup .course-curriculum ul.curriculum-sections .section-content .course-item' => "background-color",
				'body.course-item-popup #learn-press-course-curriculum'                                          => "background-color",
			),
			'std'       => '#FFF'
		),
		array(
			'title'     => __( 'Popup item color', 'learnpress' ),
			'id'        => 'popup-item-color',
			'selectors' => array(
				'body.course-item-popup .course-curriculum ul.curriculum-sections .section-content .course-item a' => "color",
			),
			'std'       => ''
		),
		array(
			'title'     => __( 'Popup active item background', 'learnpress' ),
			'id'        => 'popup-active-item-background',
			'selectors' => array(
				'body.course-item-popup .course-curriculum ul.curriculum-sections .section-content .course-item.current' => "background-color",
			),
			'std'       => '#F9F9F9'
		),
		array(
			'title'     => __( 'Popup active item color', 'learnpress' ),
			'id'        => 'popup-active-item-color',
			'selectors' => array(
				'body.course-item-popup .course-curriculum ul.curriculum-sections .section-content .course-item.current a' => "color",
			),
			'std'       => ''
		),
		array(
			'title'     => __( 'Popup content background', 'learnpress' ),
			'id'        => 'popup-content-background',
			'selectors' => array(
				'body.course-item-popup #learn-press-content-item' => "background-color"
			),
			'std'       => '#FFF'
		),
		array(
			'title'     => __( 'Popup content color', 'learnpress' ),
			'id'        => 'popup-content-color',
			'selectors' => array(
				'body.course-item-popup #learn-press-content-item' => "color"
			),
			'std'       => ''
		),
		array(
			'title'     => __( 'Section heading background', 'learnpress' ),
			'id'        => 'section-heading-bg',
			'selectors' => array(
				'body.course-item-popup #learn-press-course-curriculum .section-header' => 'background'
			)
		),
		array(
			'title'     => __( 'Section heading color', 'learnpress' ),
			'id'        => 'section-heading-color',
			'selectors' => array(
				'body.course-item-popup #learn-press-course-curriculum .section-header' => 'color'
			)
		),
		array(
			'title'     => __( 'Section heading bottom color', 'learnpress' ),
			'id'        => 'section-heading-bottom-color',
			'selectors' => array(
				'.course-curriculum ul.curriculum-sections .section-header' => 'border-bottom: 1px solid %s'
			),
			'std'       => '#00adff'
		),
		array(
			'title'     => __( 'Lines color', 'learnpress' ),
			'id'        => 'lines-color',
			'selectors' => array(
				'#course-item-content-header'                                             => 'border-bottom: 1px solid %s',
				'.course-curriculum ul.curriculum-sections .section-content .course-item' => 'border-bottom: 1px solid %s',
				'body.course-item-popup #learn-press-course-curriculum'                   => 'border-right: 1px solid %s',
				'#course-item-content-header .toggle-content-item'                        => 'border-left: 1px solid %s'
			),
			'std'       => 'DDD'
		),
		array(
			'title'     => __( 'Profile cover background', 'learnpress' ),
			'id'        => 'profile-cover-bg',
			'selectors' => array(
				'#learn-press-profile-header' => 'background-color'
			),
			'std'       => '#f0defb'
		),
		array(
			'title'     => __( 'Scrollbar', 'learnpress' ),
			'id'        => 'scroll-bar',
			'selectors' => array(
				'.scrollbar-light > .scroll-element.scroll-y .scroll-bar' => 'background-color',
				'.scrollbar-light > .scroll-element .scroll-element_size' => 'background'
			),
			'std'       => '#12b3ff'
		),
		array(
			'title'     => __( 'Progress bar color', 'learnpress' ),
			'id'        => 'progress-bar-color',
			'selectors' => array(
				'.learn-press-progress .progress-bg' => 'background-color'
			),
			'std'       => '#DDDDDD'
		),
		array(
			'title'     => __( 'Progress bar active color', 'learnpress' ),
			'id'        => 'scroll-bar',
			'selectors' => array(
				'.learn-press-progress .progress-bg .progress-active' => 'background-color'
			),
			'std'       => '#00adff'
		),
	);

	return apply_filters( 'learn-press/color-schemas', $colors );
}

/**
 * Output custom css from settings
 *
 * @since 3.0.0
 */
function learn_press_print_custom_styles() {

	if ( 'yes' !== LP()->settings()->get( 'enable_custom_colors' ) ) {
		return;
	}

	if ( ! $schemas = LP()->settings()->get( 'color_schemas' ) ) {
		return;
	}

	// Get current
	$schema = reset( $schemas );
	$colors = learn_press_get_color_schemas();
	$css    = array();

	foreach ( $colors as $options ) {
		if ( array_key_exists( $options['id'], $schema ) ) {

			if ( empty( $options['selectors'] ) ) {
				continue;
			}

			foreach ( $options['selectors'] as $selector => $props ) {
				if ( empty( $css[ $selector ] ) ) {
					$css[ $selector ] = "";
				}
				if ( is_string( $props ) ) {
					if ( strpos( $props, '%s' ) !== false ) {
						$css[ $selector ] .= sprintf( $props, $schema[ $options['id'] ] ) . ";";
					} else {
						$css[ $selector ] .= "{$props}:" . $schema[ $options['id'] ] . ";";
					}
				} else {
					foreach ( $props as $prop ) {
						if ( strpos( $prop, '%s' ) !== false ) {
							$css[ $selector ] .= sprintf( $prop, $schema[ $options['id'] ] ) . ";";
						} else {
							$css[ $selector ] .= "{$prop}:" . $schema[ $options['id'] ] . ";";
						}
					}
				}
			}
		}
	}

	if ( ! $css ) {
		return;
	}

	?>
    <style id="learn-press-custom-css">
        <?php
		foreach($css as $selector => $props){
			echo "{$selector}{{$props}}\n";
		}
		?>
    </style>
	<?php
}

add_action( 'wp_head', 'learn_press_print_custom_styles' );

/**
 * Redirect to LP search page if user is searching a
 * course but current page is not for displaying results
 * of the courses.
 */
function learn_press_redirect_search() {
	if ( learn_press_is_search() ) {
		$search_page = learn_press_get_page_id( 'search' );
		if ( ! is_page( $search_page ) ) {
			global $wp_query;
			wp_redirect( add_query_arg( 's', $wp_query->query_vars['s'], get_the_permalink( $search_page ) ) );
			exit();
		}
	}
}

/**
 * Return TRUE if current user has already enroll course in single view.
 *
 * @return bool
 * @since 3.0.0
 *
 */
function learn_press_current_user_enrolled_course() {
	$user   = learn_press_get_current_user();
	$course = LP_Global::course();

	if ( ! $course ) {
		return false;
	}

	return $user->has_enrolled_course( $course->get_id() );
}

function learn_press_content_item_summary_class( $more = '', $echo = true ) {
	$classes = array( 'content-item-summary' );
	$classes = LP_Helper::merge_class( $classes, $more );
	$classes = apply_filters( 'learn-press/content-item-summary-class', $classes );
	$output  = 'class="' . join( ' ', $classes ) . '"';

	if ( $echo ) {
		echo $output;
	}

	return $output;
}

function learn_press_content_item_summary_classes( $classes ) {
	if ( ! $item = LP_Global::course_item() ) {
		return $classes;
	}

	if ( $item->get_post_type() !== LP_LESSON_CPT ) {
		return $classes;
	}

	if ( 'yes' !== LP()->settings->get( 'enable_lesson_video' ) ) {
		return $classes;
	}

	if ( $item->get_video() ) {
		$classes[] = 'content-item-video';
	}

	return $classes;
}

function learn_press_maybe_load_comment_js() {
	if ( $item = LP_Global::course_item() ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

add_action( 'wp_enqueue_scripts', 'learn_press_maybe_load_comment_js' );

//add_filter( 'learn-press/can-view-item', 'learn_press_filter_can_view_item', 10, 4 );
//
//function learn_press_filter_can_view_item( $view, $item_id, $user_id, $course_id ) {
//	$user = learn_press_get_user( $user_id );
//
//	if ( ! get_post_meta( $course_id, '_lp_submission', true ) ) {
//		update_post_meta( $course_id, '_lp_submission', 'yes' );
//	}
//
//	$_lp_submission = get_post_meta( $course_id, '_lp_submission', true );
//	if ( $_lp_submission === 'yes' ) {
//		if ( ! $user->is_logged_in() ) {
//			return 'not-logged-in';
//		} else if ( ! $user->has_enrolled_course( $course_id ) ) {
//			return 'not-enrolled';
//		}
//	}
//
//	return $view;
//}

/**
 * @editor     tungnx | comment code
 * @deprecated 3.2.7.3
 */
//add_filter( 'learn_press_get_template', 'learn_press_filter_block_content_template', 10, 5 );
/*function learn_press_filter_block_content_template( $located, $template_name, $args, $template_path, $default_path ) {
	$item = LP_Global::course_item();
	if ( $template_name == 'global/block-content.php' ) {
		//		$can_view_item = false;
		//		if ( !is_user_logged_in() ) {
		//			$can_view_item = 'not-logged-in';
		//		} elseif ( !learn_press_current_user_enrolled_course() ) {
		//			$can_view_item = 'not-enrolled';
		//		} elseif ( $item->is_blocked() ) {
		//			$can_view_item = 'is_blocked';
		//		}

		$user = learn_press_get_current_user();

		$can_view_item = $user->can_view_item( $item->get_id(), 0 );


		$located = learn_press_get_template( 'single-course/content-protected.php', array( 'can_view_item' => $can_view_item ) );
	}

	return $located;
}*/

function learn_press_term_conditions_template() {
	$page_id = learn_press_get_page_id( 'term_conditions' );
	if ( $page_id ) {
		$page_link = get_page_link( $page_id );
		learn_press_get_template( 'checkout/term-conditions.php', array( 'page_link' => $page_link ) );
	}
}

add_action( 'learn-press/after-payment-methods', 'learn_press_term_conditions_template' );

function learn_press_get_link_current_question_instead_of_continue_button( $link, $item ) {
	if ( get_post_type( $item->get_id() ) === LP_QUIZ_CPT ) {
		$user = LP_Global::user();

		if ( ! $user ) {
			return $link;
		}

		$course = $item->get_course();

		if ( ! $course ) {
			return $link;
		}

		$quiz_data = $user->get_item_data( $item->get_id(), $course->get_id() );
		if ( $quiz_data && $quiz_data->get_status() === 'started' ) {
			$link = $item->get_question_link( $quiz_data->get_current_question() );
		}
	}

	return $link;
}

add_filter( 'learn-press/course-item-link', 'learn_press_get_link_current_question_instead_of_continue_button', 10, 2 );

/**
 * @since 3.2.6
 */
function learn_press_define_debug_mode() {
	if ( ! learn_press_is_debug() ) {
		return;
	}
	?>
    <script>window.LP_DEBUG = true;</script>
	<?php
}

add_action( 'admin_print_scripts', 'learn_press_define_debug_mode' );
add_action( 'wp_print_scripts', 'learn_press_define_debug_mode' );
