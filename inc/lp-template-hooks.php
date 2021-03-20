<?php
/**
 * Build courses content
 */

/*****************************************/
/**                                      */
/**            DOCUMENTATION             */
/**                                      */
/*****************************************/

/**
 * Core template classes: LP_Template_General, LP_Template_Profile, LP_Template_Course.
 *
 * + Get instance of a template: LP()->template( TYPE ) e.g: LP()->template( 'course' )
 * + LP()->template( TYPE )->func(CALLBACK) => hook to an action with function CALLBACK of TYPE class
 * + LP()->template( TYPE )->callback( TEMPLATE ) => hook to an action to c
 */


defined( 'ABSPATH' ) || exit();

/**
 * New functions since 3.0.0
 */

/**
 * Header and Footer
 *
 * @see LP_Template_General::template_header()
 * @see LP_Template_General::template_footer()
 */
add_action( 'learn-press/template-header', LP()->template( 'general' )->func( 'template_header' ) );
add_action( 'learn-press/template-footer', LP()->template( 'general' )->func( 'template_footer' ) );

/**
 * Course breadcrumb
 *
 * @see LP_Template_General::breadcrumb()
 */
add_action(
	'learn-press/before-main-content',
	LP()->template( 'general' )->text( '<div class="lp-archive-courses">', 'lp-archive-courses-open' ),
	- 100
);
add_action( 'learn-press/before-main-content', LP()->template( 'general' )->func( 'breadcrumb' ) );

add_action(
	'learn-press/after-main-content',
	LP()->template( 'general' )->text( '</div>', 'lp-archive-courses-close' ),
	100
);


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
learn_press_add_course_buttons();


/** BEGIN: Archive course */
add_action( 'learn-press/before-courses-loop', LP()->template( 'course' )->func( 'courses_top_bar' ), 10 );

/** BEGIN: Archive course loop item */
add_action(
	'learn-press/before-courses-loop-item',
	LP()->template( 'course' )->text( '<div class="course-wrap-thumbnail">', 'course-wrap-thumbnail-open' ),
	1
);
add_action(
	'learn-press/before-courses-loop-item',
	LP()->template( 'course' )->callback( 'loop/course/badge-featured' ),
	5
);
add_action(
	'learn-press/before-courses-loop-item',
	LP()->template( 'course' )->callback( 'loop/course/thumbnail.php' ),
	10
);
add_action(
	'learn-press/before-courses-loop-item',
	LP()->template( 'course' )->text( '</div>', 'course-wrap-thumbnail-close' ),
	1000
);

add_action(
	'learn-press/before-courses-loop-item',
	LP()->template( 'course' )->text(
		'<!-- START .course-content --> <div class="course-content">',
		'course-content-open'
	),
	1000
);
add_action(
	'learn-press/before-courses-loop-item',
	LP()->template( 'course' )->callback( 'loop/course/categories' ),
	1010
);
add_action(
	'learn-press/before-courses-loop-item',
	LP()->template( 'course' )->callback( 'loop/course/instructor' ),
	1010
);
add_action(
	'learn-press/courses-loop-item-title',
	LP()->template( 'course' )->callback( 'loop/course/title.php' ),
	20
);

/**
 * @see LP_Template_Course::courses_loop_item_meta()
 * @see LP_Template_Course::courses_loop_item_info_begin()
 * @see LP_Template_Course::clearfix()
 * @see LP_Template_Course::courses_loop_item_price()
 * @see LP_Template_Course::courses_loop_item_info_end()
 * @see LP_Template_Course::loop_item_user_progress()
 */

add_action(
	'learn-press/after-courses-loop-item',
	LP()->template( 'course' )->text(
		'<!-- START .course-content-meta --> <div class="course-wrap-meta">',
		'course-wrap-meta-open'
	),
	20
);
add_action(
	'learn-press/after-courses-loop-item',
	LP()->template( 'course' )->callback( 'single-course/meta/duration' ),
	20
);
add_action(
	'learn-press/after-courses-loop-item',
	LP()->template( 'course' )->callback( 'single-course/meta/level' ),
	20
);
add_action( 'learn-press/after-courses-loop-item', LP()->template( 'course' )->func( 'count_object' ), 20 );
add_action(
	'learn-press/after-courses-loop-item',
	LP()->template( 'course' )->text( '</div> <!-- END .course-content-meta -->', 'course-wrap-meta-close' ),
	20
);

add_action( 'learn-press/after-courses-loop-item', LP()->template( 'course' )->func( 'courses_loop_item_meta' ), 25 );
add_action(
	'learn-press/after-courses-loop-item',
	LP()->template( 'course' )->func( 'courses_loop_item_info_begin' ),
	20
);
add_action( 'learn-press/after-courses-loop-item', LP()->template( 'course' )->func( 'clearfix' ), 30 );

add_action(
	'learn-press/after-courses-loop-item',
	LP()->template( 'course' )->text(
		'<!-- START .course-content-footer --> <div class="course-footer">',
		'course-footer-open'
	),
	40
);
add_action( 'learn-press/after-courses-loop-item', LP()->template( 'course' )->func( 'courses_loop_item_price' ), 50 );
add_action(
	'learn-press/after-courses-loop-item',
	LP()->template( 'course' )->text( '</div> <!-- END .course-content-footer -->', 'course-footer-close' ),
	50
);
add_action( 'learn-press/after-courses-loop-item', LP()->template( 'course' )->func( 'course_readmore' ), 55 );

add_action(
	'learn-press/after-courses-loop-item',
	LP()->template( 'course' )->func( 'courses_loop_item_info_end' ),
	60
);
// add_action( 'learn-press/after-courses-loop-item', LP()->template( 'course' )->func( 'loop_item_user_progress' ), 70 );

add_action(
	'learn-press/after-courses-loop-item',
	LP()->template( 'course' )->text( '</div> <!-- END .course-content -->', 'course-content-close' ),
	1000
);

/** END: Archive course loop item */

/** Archive course pagination */
add_action(
	'learn-press/after-courses-loop',
	LP()->template( 'course' )->callback( 'loop/course/pagination.php' ),
	10
);
/** END: Archive course */

/** BEGIN: Main content of single course */


// Sidebar and content
add_action( 'learn-press/single-course-summary', LP()->template( 'course' )->callback( 'single-course/content' ), 10 );

// Content
add_action(
	'learn-press/course-content-summary',
	LP()->template( 'course' )->text(
		'<div class="course-detail-info"> <div class="lp-content-area"> <div class="course-info-left">',
		'course-info-left-open'
	),
	10
);
add_action(
	'learn-press/course-content-summary',
	LP()->template( 'course' )->callback( 'single-course/meta-primary' ),
	10
);
add_action( 'learn-press/course-content-summary', LP()->template( 'course' )->callback( 'single-course/title' ), 10 );
add_action(
	'learn-press/course-content-summary',
	LP()->template( 'course' )->callback( 'single-course/meta-secondary' ),
	10
);
add_action(
	'learn-press/course-content-summary',
	LP()->template( 'course' )->text( ' </div> </div> </div>', 'course-info-left-close' ),
	15
);

add_action(
	'learn-press/course-content-summary',
	LP()->template( 'course' )->text( '<div class="lp-entry-content lp-content-area">', 'lp-entry-content-open' ),
	30
);
add_action(
	'learn-press/course-content-summary',
	LP()->template( 'course' )->text( '<div class="entry-content-left">', 'entry-content-left-open' ),
	35
);
add_action(
	'learn-press/course-content-summary',
	LP()->template( 'course' )->func( 'course_extra_boxes_position_control' ),
	39
);
add_action( 'learn-press/course-content-summary', LP()->template( 'course' )->func( 'course_extra_boxes' ), 40 );
// add_action( 'learn-press/course-content-summary', LP()->template( 'course' )->callback( 'single-course/progress' ), 40 );
// add_action( 'learn-press/course-content-summary', LP()->template( 'course' )->func( 'remaining_time' ), 50 );
add_action(
	'learn-press/course-content-summary',
	LP()->template( 'course' )->callback( 'single-course/tabs/tabs' ),
	60
);
// appear at bottom after enrolled
add_action( 'learn-press/course-content-summary', LP()->template( 'course' )->func( 'course_extra_boxes' ), 70 );

add_action(
	'learn-press/course-content-summary',
	LP()->template( 'course' )->text( '<!-- end entry content left --> </div>', 'entry-content-left-close' ),
	80
);

add_action( 'learn-press/course-content-summary', LP()->template( 'course' )->callback( 'single-course/sidebar' ), 85 );

add_action(
	'learn-press/course-content-summary',
	LP()->template( 'course' )->text( ' </div>', 'lp-entry-content-close' ),
	100
);

// Meta
add_action(
	'learn-press/course-meta-primary-left',
	LP()->template( 'course' )->callback( 'single-course/meta/instructor' ),
	10
);
add_action(
	'learn-press/course-meta-primary-left',
	LP()->template( 'course' )->callback( 'single-course/meta/category' ),
	20
);

add_action(
	'learn-press/course-meta-secondary-left',
	LP()->template( 'course' )->callback( 'single-course/meta/duration' ),
	10
);
add_action(
	'learn-press/course-meta-secondary-left',
	LP()->template( 'course' )->callback( 'single-course/meta/level' ),
	20
);
add_action( 'learn-press/course-meta-secondary-left', LP()->template( 'course' )->func( 'count_object' ), 20 );


// Sidebar content
/**
 * @see LP_Template_Course::course_sidebar_preview()
 * @see LP_Template_Course::course_extra_key_features()
 * @see LP_Template_Course::course_extra_requirements()
 */
add_action( 'learn-press/course-summary-sidebar', LP()->template( 'course' )->func( 'course_sidebar_preview' ), 10 );
add_action( 'learn-press/course-summary-sidebar', LP()->template( 'course' )->func( 'course_featured_review' ), 20 );
// add_action( 'learn-press/course-summary-sidebar', LP()->template( 'course' )->func( 'course_extra_key_features' ), 20 );
// add_action( 'learn-press/course-summary-sidebar', LP()->template( 'course' )->func( 'course_extra_requirements' ), 30 );

/** END: Main content of single course */

/** BEGIN: Course section */
add_action(
	'learn-press/section-summary',
	LP()->template( 'course' )->callback( 'single-course/section/title.php', array( 'section' ) ),
	10
);
add_action(
	'learn-press/section-summary',
	LP()->template( 'course' )->callback( 'single-course/section/content.php', array( 'section' ) ),
	20
);

add_action(
	'learn-press/after-section-loop-item-title',
	LP()->template( 'course' )->callback(
		'single-course/section/item-meta.php',
		array(
			'item',
			'section',
		)
	),
	10,
	2
);

/** BEGIN: Quiz item */

/**
 * @see LP_Template_Course::quiz_meta_questions()
 * @see LP_Template_Course::item_meta_duration()
 * @see LP_Template_Course::quiz_meta_final()
 */
add_action(
	'learn-press/course-section-item/before-lp_quiz-meta',
	LP()->template( 'course' )->func( 'quiz_meta_questions' ),
	10
);
add_action(
	'learn-press/course-section-item/before-lp_quiz-meta',
	LP()->template( 'course' )->func( 'item_meta_duration' ),
	20
);
add_action(
	'learn-press/course-section-item/before-lp_quiz-meta',
	LP()->template( 'course' )->func( 'quiz_meta_final' ),
	30
);
/** END: Quiz item */

/** BEGIN: Lesson item */
add_action(
	'learn-press/course-section-item/before-lp_lesson-meta',
	LP()->template( 'course' )->func( 'item_meta_duration' ),
	10
);
/** END: Lesson item */

/** END: Course section */

/** BEGIN: Popup */

/**
 * @see LP_Template_Course::popup_header()
 * @see LP_Template_Course::popup_sidebar()
 * @see LP_Template_Course::popup_content()
 * @see LP_Template_Course::popup_footer()
 */
add_action( 'learn-press/single-item-summary', LP()->template( 'course' )->func( 'popup_header' ), 10 );
add_action( 'learn-press/single-item-summary', LP()->template( 'course' )->func( 'popup_sidebar' ), 20 );
add_action( 'learn-press/single-item-summary', LP()->template( 'course' )->func( 'popup_content' ), 30 );
add_action( 'learn-press/single-item-summary', LP()->template( 'course' )->func( 'popup_footer' ), 40 );

/**
 * @see LP_Template_Course::popup_footer_nav()
 */
add_action( 'learn-press/popup-footer', LP()->template( 'course' )->func( 'popup_footer_nav' ), 10 );
/** END: Popup */

/** BEGIN: Popup quiz */
/**
 * @see LP_Template_Course::course_finish_button()
 */
add_action( 'learn-press/quiz-buttons', LP()->template( 'course' )->func( 'course_finish_button' ), 10 );
/** END: Popup quiz */

/** BEGIN: Popup lesson */

/**
 * @see LP_Template_Course::item_lesson_title()
 * @see LP_Template_Course::item_lesson_content()
 * @see LP_Template_Course::item_lesson_content_blocked()
 * @see LP_Template_Course::item_lesson_complete_button()
 * @see LP_Template_Course::course_finish_button()
 */
add_action(
	'learn-press/before-content-item-summary/lp_lesson',
	LP()->template( 'course' )->func( 'item_lesson_title' ),
	10
);
add_action(
	'learn-press/content-item-summary/lp_lesson',
	LP()->template( 'course' )->func( 'item_lesson_content' ),
	10
);
// add_action( 'learn-press/content-item-summary/lp_lesson',
// LP()->template( 'course' )->func( 'item_lesson_content_blocked' ), 15 );
add_action(
	'learn-press/after-content-item-summary/lp_lesson',
	LP()->template( 'course' )->func( 'item_lesson_complete_button' ),
	10
);
add_action(
	'learn-press/after-content-item-summary/lp_lesson',
	LP()->template( 'course' )->func( 'course_finish_button' ),
	15
);
/** END: Popup lesson */

/**
 * @see LP_Template_Course::course_item_content()
 */
add_action( 'learn-press/course-item-content', LP()->template( 'course' )->func( 'course_item_content' ), 5 );

/** BEGIN: User profile */

/**
 * @see LP_Template_Profile::header()
 * @see LP_Template_Profile::tabs()
 * @see LP_Template_Profile::content()
 */
add_action( 'learn-press/before-user-profile', LP()->template( 'profile' )->func( 'header' ), 10 );
add_action( 'learn-press/user-profile', LP()->template( 'profile' )->func( 'sidebar' ), 10 );
add_action( 'learn-press/user-profile', LP()->template( 'profile' )->func( 'content' ), 20 );

add_action( 'learn-press/user-profile-account', LP()->template( 'profile' )->func( 'avatar' ), 10 );
add_action( 'learn-press/user-profile-account', LP()->template( 'profile' )->func( 'socials' ), 10 );
add_action( 'learn-press/user-profile-tabs', LP()->template( 'profile' )->func( 'tabs' ), 10 );


add_action( 'learn-press/profile/orders', LP()->template( 'profile' )->callback( 'profile/tabs/orders/list.php' ), 10 );
add_action(
	'learn-press/profile/orders',
	LP()->template( 'profile' )->callback( 'profile/tabs/orders/recover-order.php' ),
	20
);

/**
 * @see LP_Template_Profile::order_details()
 * @see LP_Template_Profile::order_recover()
 * @see LP_Template_Profile::order_message()
 */
add_action( 'learn-press/profile/order-details', LP()->template( 'profile' )->func( 'order_details' ), 5 );
add_action( 'learn-press/profile/order-details', LP()->template( 'profile' )->func( 'order_recover' ), 10 );
add_action( 'learn-press/profile/order-details', LP()->template( 'profile' )->func( 'order_message' ), 15 );

/**
 * @see LP_Template_Profile::dashboard_logged_in()
 */
add_action( 'learn-press/profile/before-dashboard', LP()->template( 'profile' )->func( 'dashboard_statistic' ), 10 );
add_action(
	'learn-press/profile/dashboard-summary',
	LP()->template( 'profile' )->func( 'dashboard_featured_courses' ),
	20
);
add_action(
	'learn-press/profile/dashboard-summary',
	LP()->template( 'profile' )->func( 'dashboard_latest_courses' ),
	30
);

/**
 * @see LP_Template_Profile::dashboard_not_logged_in()
 * @see LP_Template_Profile::login_form()
 * @see LP_Template_Profile::register_form()
 */
add_action( 'learn-press/user-profile', LP()->template( 'profile' )->func( 'dashboard_not_logged_in' ), 5 );
add_action( 'learn-press/user-profile', LP()->template( 'profile' )->func( 'login_form' ), 10 );
add_action( 'learn-press/user-profile', LP()->template( 'profile' )->func( 'register_form' ), 15 );

/** BEGIN: Become teacher form */

/**
 * @see LP_Template_General::become_teacher_messages()
 * @see LP_Template_General::become_teacher_heading()
 * @see LP_Template_General::become_teacher_form_fields()
 * @see LP_Template_General::become_teacher_button()
 */
add_action(
	'learn-press/before-become-teacher-form',
	LP()->template( 'general' )->func( 'become_teacher_messages' ),
	10
);
add_action(
	'learn-press/before-become-teacher-form',
	LP()->template( 'general' )->func( 'become_teacher_heading' ),
	20
);
add_action( 'learn-press/become-teacher-form', LP()->template( 'general' )->func( 'become_teacher_form_fields' ), 10 );
add_action( 'learn-press/after-become-teacher-form', LP()->template( 'general' )->func( 'become_teacher_button' ), 10 );
/** END: Become teacher form */


/** BEGIN: Checkout page */
add_action( 'learn-press/before-checkout-form', LP()->template( 'checkout' )->func( 'review_order' ), 10 );
add_action( 'learn-press/after-checkout-form', LP()->template( 'checkout' )->func( 'account_logged_in' ), 20 );
add_action( 'learn-press/after-checkout-form', LP()->template( 'checkout' )->func( 'account_register' ), 30 );
add_action( 'learn-press/after-checkout-form', LP()->template( 'checkout' )->func( 'account_login' ), 40 );
add_action( 'learn-press/after-checkout-form', LP()->template( 'checkout' )->func( 'guest_checkout' ), 50 );
add_action( 'learn-press/after-checkout-form', LP()->template( 'checkout' )->func( 'order_comment' ), 60 );
add_action( 'learn-press/after-checkout-form', LP()->template( 'checkout' )->func( 'payment' ), 70 );
add_action( 'learn-press/after-checkout-form', LP()->template( 'checkout' )->func( 'terms' ), 80 );


// ******************************************************************************************************************* //

add_action( 'learn-press/content-item-summary-class', 'learn_press_content_item_summary_classes', 15 );
add_action(
	'learn-press/before-content-item-summary/lp_quiz',
	LP()->template( 'course' )->callback( 'content-quiz/title.php' ),
	5
);
add_action( 'learn-press/content-item-summary/lp_quiz', LP()->template( 'course' )->callback( 'content-quiz/js' ), 25 );
add_action( 'learn-press/parse-course-item', 'learn_press_control_displaying_course_item', 5 );
add_action( 'learn-press/after-single-course', 'learn_press_single_course_args', 5 );
add_filter( 'document_title_parts', 'learn_press_single_document_title_parts', 5 );

add_filter( 'body_class', 'learn_press_body_classes', 10 );
add_filter( 'post_class', 'learn_press_course_class', 15, 3 );
add_action( 'wp_head', 'learn_press_single_course_args', 5 );
add_action(
	'learn-press/before-checkout-order-review',
	LP()->template( 'course' )->callback( 'checkout/form-logged-in.php' ),
	10
);
add_filter( 'comments_template_query_args', 'learn_press_comments_template_query_args' );
add_filter( 'get_comments_number', 'learn_press_filter_get_comments_number' );

add_filter( 'excerpt_length', 'learn_press_custom_excerpt_length', 999 );
// add_filter( 'learn_press_get_template', LP()->template( 'general' )->func( 'filter_block_content_template' ), 10, 5 );

/**
 * Filter to hide the section if there is no item.
 *
 * @param bool              $visible
 * @param LP_Course_Section $section
 * @param LP_Course         $course
 *
 * @return bool
 * @since 4.0.0
 */
function learn_press_filter_section_visible( $visible, $section, $course ) {
	if ( ! $section->get_items() ) {
		$visible = false;
	}

	return $visible;
}

add_filter( 'learn-press/section-visible', 'learn_press_filter_section_visible', 10, 3 );

function lp_check_addon_is_v4() {
	$active_plugins = get_option( 'active_plugins', array() );

	$all_plugins = get_plugins();

	$list_plugins = array();

	if ( ! empty( $active_plugins ) ) {
		foreach ( $active_plugins as $file ) {
			if ( preg_match( '/^learnpress-/', $file ) ) {
				$plugin = $all_plugins[ $file ];

				if ( version_compare( $plugin['Version'], '4.0', '<' ) ) {
					$list_plugins[] = $file;
				}
			}
		}
	}

	if ( ! empty( $list_plugins ) ) {
		global $wp_filter;

		if ( empty( $wp_filter['learn-press/ready'] ) ) {
			return;
		}

		$callbacks = $wp_filter['learn-press/ready']->callbacks;

		$lists = array(
			'learnpress-2checkout-payment/learnpress-2checkout-payment.php' => 'LP_Addon_2Checkout_Payment_Preload',
			'learnpress-announcements/learnpress-announcements.php' => 'LP_Addon_Announcements_Preload',
			'learnpress-assignments/learnpress-assignments.php' => 'LP_Addon_Assignment_Preload',
			'learnpress-authorizenet-payment/learnpress-authorizenet-payment.php' => 'LP_Addon_Authorizenet_Payment_Preload',
			'learnpress-avada-kit/learnpress-avada-kit.php' => 'LP_Addon_Avada_Preload',
			'learnpress-badgeos/learnpress-badgeos.php'   => 'LP_Addon_Badgeos_Preload',
			'learnpress-bbpress/learnpress-bbpress.php'   => 'LP_Addon_bbPress_Preload',
			'learnpress-buddypress/learnpress-buddypress.php' => 'LP_Addon_BuddyPress_Preload',
			'learnpress-cert-email/learnpress-cert-email.php' => 'LP_Addon_Cert_Email_Preload',
			'learnpress-certificates/learnpress-certificates.php' => 'LP_Addon_Certificates_Preload',
			'learnpress-co-instructor/learnpress-co-instructor.php' => 'LP_Co_Instructor_Preload',
			'learnpress-collections/learnpress-collections.php' => 'LP_Addon_Collections_Preload',
			'learnpress-coming-soon-courses/learnpress-coming-soon-courses.php' => 'LP_Addon_Coming_Soon_Courses_Preload',
			'learnpress-commission/learnpress-commission.php' => 'LP_Addon_Commission_Preload',
			'learnpress-content-drip/learnpress-content-drip.php' => 'LP_Addon_Content_Drip_Preload',
			'learnpress-coupon/learnpress-coupon.php'     => 'LP_Addon_Coupon_Preload',
			'learnpress-course-review/learnpress-course-review.php' => 'LP_Addon_Course_Review_Preload',
			'learnpress-dropdown-question/learnpress-dropdown-question.php' => 'LP_Addon_Dropdown_Preload',
			'learnpress-fill-in-blank/learnpress-fill-in-blank.php' => 'LP_Addon_Fill_In_Blank_Preload',
			'learnpress-fill-in-blank-advance/learnpress-fill-in-blank-advance.php' => 'LP_Addon_Fill_In_Blank_Advance_Preload',
			'learnpress-frontend-editor/learnpress-frontend-editor.php' => 'LP_Addon_Frontend_Editor_Preload',
			'learnpress-gradebook/learnpress-gradebook.php' => 'LP_Addon_Gradebook_Preload',
			'learnpress-h5p/learnpress-h5p.php'           => 'LP_Addon_H5p_Preload',
			'learnpress-helper/learnpress-helper.php'     => 'LP_Addon_Helper',
			'learnpress-import-export/learnpress-import-export.php' => 'LP_Addon_Import_Export_Preload',
			'learnpress-jevelin-kit/learnpress-jevelin-kit.php' => 'LP_Addon_Jevelin_Preload',
			'learnpress-memberships/learnpress-memberships.php' => 'LP_Addons_Membership_Preload',
			'learnpress-mycred/learnpress-mycred.php'     => 'LP_Addon_MyCred_Preload',
			'learnpress-offline-payment/learnpress-offline-payment.php' => 'LP_Addon_Offline_Payment_Preload',
			'learnpress-paid-membership-pro/learnpress-paid-memberships-pro.php' => 'LP_Addon_Paid_Memberships_Pro_Preload',
			'learnpress-payu-payment/learnpress-payu-payment.php' => 'LP_Addon_PayU_PaymentPreload',
			'learnpress-polylang/learnpress-polylang.php' => 'LP_Addon_Polylang_Preload',
			'learnpress-prerequisites-courses/learnpress-prerequisites-courses.php' => 'LP_Addon_Prerequisites_Courses_Preload',
			'learnpress-random-quiz/learnpress-random-quiz.php' => 'LP_Addon_Random_Quiz_Preload',
			'learnpress-sorting-choice/learnpress-sorting-choice.php' => 'LP_Addon_Sorting_Choice_Preload',
			'learnpress-stripe/learnpress-stripe.php'     => 'LP_Addon_Stripe_Payment_Preload',
			'learnpress-students-list/learnpress-students-list.php' => 'LP_Addon_Students_List_Preload',
			'learnpress-user-dashboard/learnpress-user-dashboard.php' => 'LP_Addon_User_Dashboard',
			'learnpress-wishlist/learnpress-wishlist.php' => 'LP_Addon_Wishlist_Preload',
			'learnpress-woo-payment/learnpress-woo-payment.php' => 'LP_Addon_Woo_Payment_Preload',
			'learnpress-wpml/learnpress-wpml.php'         => 'LP_Addon_WPML_Preload',
		);

		if ( $callbacks ) {
			foreach ( $callbacks as $key => $callback ) {
				foreach ( $callback as $function ) {
					if ( isset( $function['function'] ) ) {
						$functions = $function['function'];

						if ( $functions[1] !== 'load' ) {
							continue;
						}

						foreach ( $list_plugins as $plugin ) {
							if ( isset( $lists[ $plugin ] ) ) {
								$plugin_class = $lists[ $plugin ];

								if ( get_class( $functions[0] ) === $plugin_class ) {
									remove_action( 'learn-press/ready', $functions, $key );
								}
							}
						}
					}
				}
			}
		}

		if ( ! empty( $list_plugins ) ) {
			update_option( 'lp_plugins_notice_v4', $list_plugins );
		}
	}
}

add_action( 'learn_press_ready', 'lp_check_addon_is_v4' );

function lp_add_notice_update_v4() {
	if ( is_admin() ) {
		$list_notice = get_option( 'lp_plugins_notice_v4' );

		$all_plugins = get_plugins();

		if ( ! empty( $list_notice ) ) {
			foreach ( $list_notice as $plugin_file ) {
				if ( isset( $all_plugins[ $plugin_file ] ) ) {
					$plugin = $all_plugins[ $plugin_file ];

					if ( version_compare( $plugin['Version'], '4.0', '<' ) ) {
						add_action(
							'admin_notices',
							function() use ( $plugin ) {
								?>
						<div class="error">
							<p>
										<?php
										printf(
											__(
												'<strong>%1$s</strong> add-on version %2$s is not compatible with LearnPress latest version. Please update %3$s to version 4.x.',
												'learnpress'
											),
											$plugin['Name'],
											$plugin['Version'],
											$plugin['Name']
										);
										?>
							</p>
						</div>
								<?php
							}
						);
					}
				}
			}
		}
	}
}

add_action( 'admin_init', 'lp_add_notice_update_v4' );
