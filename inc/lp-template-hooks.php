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
 * + Get instance of a template: LearnPress::instance()->template( TYPE ) e.g: LearnPress::instance()->template( 'course' )
 * + LearnPress::instance()->template( TYPE )->func(CALLBACK) => hook to an action with function CALLBACK of TYPE class
 * + LearnPress::instance()->template( TYPE )->callback( TEMPLATE ) => hook to an action to c
 */


use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;

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
add_action( 'learn-press/template-header', LearnPress::instance()->template( 'general' )->func( 'template_header' ) );
add_action( 'learn-press/template-footer', LearnPress::instance()->template( 'general' )->func( 'template_footer' ) );

/**
 * Course breadcrumb
 *
 * @see LP_Template_General::breadcrumb()
 */
add_action(
	'learn-press/before-main-content',
	LearnPress::instance()->template( 'general' )->text( '<div class="lp-archive-courses">', 'lp-archive-courses-open' ),
	- 100
);
add_action( 'learn-press/before-main-content', LearnPress::instance()->template( 'general' )->func( 'breadcrumb' ) );

add_action(
	'learn-press/after-main-content',
	LearnPress::instance()->template( 'general' )->text( '</div>', 'lp-archive-courses-close' ),
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
add_action( 'learn-press/before-courses-loop', LearnPress::instance()->template( 'course' )->func( 'courses_top_bar' ), 10 );

/** BEGIN: Archive course loop item */
add_action(
	'learn-press/before-courses-loop-item',
	LearnPress::instance()->template( 'course' )->text( '<div class="course-wrap-thumbnail">', 'course-wrap-thumbnail-open' ),
	1
);
add_action(
	'learn-press/before-courses-loop-item',
	LearnPress::instance()->template( 'course' )->callback( 'loop/course/badge-featured' ),
	5
);
add_action(
	'learn-press/before-courses-loop-item',
	LearnPress::instance()->template( 'course' )->callback( 'loop/course/thumbnail.php' ),
	10
);
add_action(
	'learn-press/before-courses-loop-item',
	LearnPress::instance()->template( 'course' )->text( '</div>', 'course-wrap-thumbnail-close' ),
	1000
);

add_action(
	'learn-press/before-courses-loop-item',
	LearnPress::instance()->template( 'course' )->text(
		'<!-- START .course-content --> <div class="course-content">',
		'course-content-open'
	),
	1000
);
add_action(
	'learn-press/before-courses-loop-item',
	LearnPress::instance()->template( 'course' )->callback( 'loop/course/categories' ),
	1010
);
add_action(
	'learn-press/before-courses-loop-item',
	LearnPress::instance()->template( 'course' )->callback( 'loop/course/instructor' ),
	1010
);
add_action(
	'learn-press/courses-loop-item-title',
	LearnPress::instance()->template( 'course' )->callback( 'loop/course/title.php' ),
	20
);

/**
 * @see LP_Template_Course::courses_loop_item_meta()
 * @see LP_Template_Course::courses_loop_item_info_begin()
 * @see LP_Template_Course::clearfix()
 * @see LP_Template_Course::courses_loop_item_price()
 * @see LP_Template_Course::courses_loop_item_info_end()
 */

add_action(
	'learn-press/after-courses-loop-item',
	LearnPress::instance()->template( 'course' )->text(
		'<!-- START .course-content-meta --> <div class="course-wrap-meta">',
		'course-wrap-meta-open'
	),
	20
);
add_action(
	'learn-press/after-courses-loop-item',
	LearnPress::instance()->template( 'course' )->callback( 'single-course/meta/duration' ),
	20
);
add_action(
	'learn-press/after-courses-loop-item',
	LearnPress::instance()->template( 'course' )->callback( 'single-course/meta/level' ),
	20
);
/**
 * @see LP_Template_Course::count_object()
 */
add_action( 'learn-press/after-courses-loop-item', LearnPress::instance()->template( 'course' )->func( 'count_object' ), 20 );
add_action(
	'learn-press/after-courses-loop-item',
	LearnPress::instance()->template( 'course' )->text( '</div> <!-- END .course-content-meta -->', 'course-wrap-meta-close' ),
	20
);

add_action( 'learn-press/after-courses-loop-item', LearnPress::instance()->template( 'course' )->func( 'courses_loop_item_meta' ), 25 );
add_action(
	'learn-press/after-courses-loop-item',
	LearnPress::instance()->template( 'course' )->func( 'courses_loop_item_info_begin' ),
	20
);
add_action( 'learn-press/after-courses-loop-item', LearnPress::instance()->template( 'course' )->func( 'clearfix' ), 30 );

add_action(
	'learn-press/after-courses-loop-item',
	LearnPress::instance()->template( 'course' )->text(
		'<!-- START .course-content-footer --> <div class="course-footer">',
		'course-footer-open'
	),
	40
);
add_action( 'learn-press/after-courses-loop-item', LearnPress::instance()->template( 'course' )->func( 'courses_loop_item_price' ), 50 );
add_action(
	'learn-press/after-courses-loop-item',
	LearnPress::instance()->template( 'course' )->text( '</div> <!-- END .course-content-footer -->', 'course-footer-close' ),
	50
);
add_action( 'learn-press/after-courses-loop-item', LearnPress::instance()->template( 'course' )->func( 'course_readmore' ), 55 );

add_action(
	'learn-press/after-courses-loop-item',
	LearnPress::instance()->template( 'course' )->func( 'courses_loop_item_info_end' ),
	60
);

add_action(
	'learn-press/after-courses-loop-item',
	LearnPress::instance()->template( 'course' )->text( '</div> <!-- END .course-content -->', 'course-content-close' ),
	1000
);

/** END: Archive course loop item */

/** Archive course pagination */
if ( LP_Settings::theme_no_support_load_courses_ajax() ) {
	add_action(
		'learn-press/after-courses-loop',
		LearnPress::instance()->template( 'course' )->callback( 'loop/course/pagination.php' )
	);
} else {
	add_action(
		'learn-press/after-courses-loop',
		function() {
			$listCourseTemplate    = ListCoursesTemplate::instance();
			$pagination_type       = LP_Settings::get_option( 'course_pagination_type', 'number' );
			$enableAjaxLoadCourses = LP_Settings_Courses::is_ajax_load_courses();
			$enableNoLoadAjaxFirst = LP_Settings_Courses::is_no_load_ajax_first_courses();
			if ( $enableAjaxLoadCourses && $pagination_type !== 'number' ) {
				if ( $enableNoLoadAjaxFirst ) {
					if ( 'load-more' === $pagination_type ) {
						echo $listCourseTemplate->html_pagination_load_more();
					} elseif ( 'infinite' === $pagination_type ) {
						echo $listCourseTemplate->html_pagination_infinite();
					}
				}

				return;
			}

			if ( ! $enableAjaxLoadCourses || ( $enableAjaxLoadCourses && $enableNoLoadAjaxFirst ) ) {
				Template::instance()->get_frontend_template( 'loop/course/pagination.php' );
			}
		},
		10
	);
}
/** END: Archive course */

/** BEGIN: Main content of single course */


// Sidebar and content
add_action( 'learn-press/single-course-summary', LearnPress::instance()->template( 'course' )->callback( 'single-course/content' ), 10 );

// Content
add_action(
	'learn-press/course-content-summary',
	LearnPress::instance()->template( 'course' )->text(
		'<div class="course-detail-info"> <div class="lp-content-area"> <div class="course-info-left">',
		'course-info-left-open'
	),
	10
);
add_action(
	'learn-press/course-content-summary',
	LearnPress::instance()->template( 'course' )->callback( 'single-course/meta-primary' ),
	10
);
add_action( 'learn-press/course-content-summary', LearnPress::instance()->template( 'course' )->callback( 'single-course/title' ), 10 );
add_action(
	'learn-press/course-content-summary',
	LearnPress::instance()->template( 'course' )->callback( 'single-course/meta-secondary' ),
	10
);
add_action(
	'learn-press/course-content-summary',
	LearnPress::instance()->template( 'course' )->text( ' </div> </div> </div>', 'course-info-left-close' ),
	15
);

add_action(
	'learn-press/course-content-summary',
	LearnPress::instance()->template( 'course' )->text( '<div class="lp-entry-content lp-content-area">', 'lp-entry-content-open' ),
	30
);
add_action(
	'learn-press/course-content-summary',
	LearnPress::instance()->template( 'course' )->text( '<div class="entry-content-left">', 'entry-content-left-open' ),
	35
);
add_action(
	'learn-press/course-content-summary',
	LearnPress::instance()->template( 'course' )->func( 'course_extra_boxes_position_control' ),
	39
);
add_action( 'learn-press/course-content-summary', LearnPress::instance()->template( 'course' )->func( 'course_extra_boxes' ), 40 );
// add_action( 'learn-press/course-content-summary', LearnPress::instance()->template( 'course' )->callback( 'single-course/progress' ), 40 );
// add_action( 'learn-press/course-content-summary', LearnPress::instance()->template( 'course' )->func( 'remaining_time' ), 50 );
add_action(
	'learn-press/course-content-summary',
	LearnPress::instance()->template( 'course' )->callback( 'single-course/tabs/tabs' ),
	60
);
// appear at bottom after enrolled
add_action( 'learn-press/course-content-summary', LearnPress::instance()->template( 'course' )->func( 'course_extra_boxes' ), 70 );

add_action( 'learn-press/course-content-summary', LearnPress::instance()->template( 'course' )->func( 'course_comment_template' ), 75 );

add_action(
	'learn-press/course-content-summary',
	LearnPress::instance()->template( 'course' )->text( '<!-- end entry content left --> </div>', 'entry-content-left-close' ),
	80
);

add_action( 'learn-press/course-content-summary', LearnPress::instance()->template( 'course' )->callback( 'single-course/sidebar' ), 85 );

add_action(
	'learn-press/course-content-summary',
	LearnPress::instance()->template( 'course' )->text( ' </div>', 'lp-entry-content-close' ),
	100
);

// Meta
add_action(
	'learn-press/course-meta-primary-left',
	LearnPress::instance()->template( 'course' )->callback( 'single-course/meta/instructor' ),
	10
);
add_action(
	'learn-press/course-meta-primary-left',
	LearnPress::instance()->template( 'course' )->callback( 'single-course/meta/category' ),
	20
);

add_action(
	'learn-press/course-meta-secondary-left',
	LearnPress::instance()->template( 'course' )->callback( 'single-course/meta/duration' ),
	10
);
add_action(
	'learn-press/course-meta-secondary-left',
	LearnPress::instance()->template( 'course' )->callback( 'single-course/meta/level' ),
	20
);
/**
 * @see LP_Template_Course::count_object()
 */
add_action( 'learn-press/course-meta-secondary-left', LearnPress::instance()->template( 'course' )->func( 'count_object' ), 20 );


// Sidebar content
/**
 * @see LP_Template_Course::course_sidebar_preview()
 * @see LP_Template_Course::course_extra_key_features()
 * @see LP_Template_Course::course_extra_requirements()
 */
add_action( 'learn-press/course-summary-sidebar', LearnPress::instance()->template( 'course' )->func( 'course_sidebar_preview' ), 10 );
add_action( 'learn-press/course-summary-sidebar', LearnPress::instance()->template( 'course' )->func( 'course_featured_review' ), 20 );
// add_action( 'learn-press/course-summary-sidebar', LearnPress::instance()->template( 'course' )->func( 'course_extra_key_features' ), 20 );
// add_action( 'learn-press/course-summary-sidebar', LearnPress::instance()->template( 'course' )->func( 'course_extra_requirements' ), 30 );

/** END: Main content of single course */

/** BEGIN: Course section */
add_action(
	'learn-press/section-summary',
	LearnPress::instance()->template( 'course' )->callback( 'single-course/section/title.php', array( 'section' ) ),
	10
);
add_action(
	'learn-press/section-summary',
	LearnPress::instance()->template( 'course' )->callback( 'single-course/section/content.php', array( 'section' ) ),
	20
);

add_action(
	'learn-press/after-section-loop-item-title',
	LearnPress::instance()->template( 'course' )->callback(
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
	LearnPress::instance()->template( 'course' )->func( 'quiz_meta_questions' ),
	10
);
add_action(
	'learn-press/course-section-item/before-lp_quiz-meta',
	LearnPress::instance()->template( 'course' )->func( 'item_meta_duration' ),
	20
);
add_action(
	'learn-press/course-section-item/before-lp_quiz-meta',
	LearnPress::instance()->template( 'course' )->func( 'quiz_meta_final' ),
	30
);
/** END: Quiz item */

/** BEGIN: Lesson item */
add_action(
	'learn-press/course-section-item/before-lp_lesson-meta',
	LearnPress::instance()->template( 'course' )->func( 'item_meta_duration' ),
	10
);
/** END: Lesson item */

/** END: Course section */

/** BEGIN: Popup */
/**
 * @see single-button-toggle-sidebar
 */
add_action(
	'learn-press/single-button-toggle-sidebar',
	LearnPress::instance()->template( 'course' )->text( '<input type="checkbox" id="sidebar-toggle" title="Show/Hide curriculum" />', 'single-button-toggle-sidebar' ),
	5
);

/**
 * @see LP_Template_Course::popup_header()
 * @see LP_Template_Course::popup_sidebar()
 * @see LP_Template_Course::popup_content()
 * @see LP_Template_Course::popup_footer()
 */
add_action( 'learn-press/single-item-summary', LearnPress::instance()->template( 'course' )->func( 'popup_header' ), 10 );
add_action( 'learn-press/single-item-summary', LearnPress::instance()->template( 'course' )->func( 'popup_sidebar' ), 20 );
add_action( 'learn-press/single-item-summary', LearnPress::instance()->template( 'course' )->func( 'popup_content' ), 30 );
add_action( 'learn-press/single-item-summary', LearnPress::instance()->template( 'course' )->func( 'popup_footer' ), 40 );

/**
 * @see LP_Template_Course::popup_footer_nav()
 */
add_action( 'learn-press/popup-footer', LearnPress::instance()->template( 'course' )->func( 'popup_footer_nav' ), 10 );
/** END: Popup */

/** BEGIN: Popup quiz */
/**
 * @see LP_Template_Course::course_finish_button()
 */
add_action( 'learn-press/quiz-buttons', LearnPress::instance()->template( 'course' )->func( 'course_finish_button' ), 10 );
/** END: Popup quiz */

/** BEGIN: Popup lesson */

/**
 * @see LP_Template_Course::item_lesson_title()
 * @see LP_Template_Course::item_lesson_content()
 * @see LP_Template_Course::item_lesson_complete_button()
 * @see LP_Template_Course::course_finish_button()
 */
add_action(
	'learn-press/before-content-item-summary/lp_lesson',
	LearnPress::instance()->template( 'course' )->func( 'item_lesson_title' ),
	10
);
add_action(
	'learn-press/content-item-summary/lp_lesson',
	LearnPress::instance()->template( 'course' )->func( 'item_lesson_content' ),
	10
);
add_action(
	'learn-press/after-content-item-summary/lp_lesson',
	LearnPress::instance()->template( 'course' )->func( 'item_lesson_complete_button' ),
	10
);
add_action(
	'learn-press/after-content-item-summary/lp_lesson',
	LearnPress::instance()->template( 'course' )->func( 'item_lesson_material' ),
	12
);
add_action(
	'learn-press/after-content-item-summary/lp_lesson',
	LearnPress::instance()->template( 'course' )->func( 'course_finish_button' ),
	15
);
/** END: Popup lesson */

/**
 * @see LP_Template_Course::course_item_content()
 */
add_action( 'learn-press/course-item-content', LearnPress::instance()->template( 'course' )->func( 'course_item_content' ), 5 );

/** BEGIN: User profile */

/**
 * @see LP_Template_Profile::header()
 * @see LP_Template_Profile::tabs()
 * @see LP_Template_Profile::content()
 */
//add_action( 'learn-press/before-user-profile', LearnPress::instance()->template( 'profile' )->func( 'header' ), 10 );

add_action( 'learn-press/user-profile', LearnPress::instance()->template( 'profile' )->func( 'sidebar' ), 10 );
add_action( 'learn-press/user-profile', LearnPress::instance()->template( 'profile' )->func( 'content' ), 20 );

add_action( 'learn-press/user-profile/private', LearnPress::instance()->template( 'profile' )->func( 'sidebar' ), 10 );

add_action( 'learn-press/user-profile-account', LearnPress::instance()->template( 'profile' )->text( ' <div class="lp-profile-left">', 'user-profile-account-left-open' ), 5 );
add_action( 'learn-press/user-profile-account', LearnPress::instance()->template( 'profile' )->func( 'avatar' ), 10 );
add_action( 'learn-press/user-profile-account', LearnPress::instance()->template( 'profile' )->func( 'socials' ), 10 );
add_action( 'learn-press/user-profile-account', LearnPress::instance()->template( 'profile' )->text( ' </div>', 'user-profile-account-left-close' ), 15 );
add_action( 'learn-press/user-profile-account', LearnPress::instance()->template( 'profile' )->func( 'header' ), 20 );

add_action( 'learn-press/user-profile-tabs', LearnPress::instance()->template( 'profile' )->func( 'tabs' ), 10 );


add_action( 'learn-press/profile/orders', LearnPress::instance()->template( 'profile' )->callback( 'profile/tabs/orders/list.php' ), 10 );
add_action(
	'learn-press/profile/orders',
	LearnPress::instance()->template( 'profile' )->callback( 'profile/tabs/orders/recover-order.php' ),
	20
);

/**
 * @see LP_Template_Profile::order_details()
 * @see LP_Template_Profile::order_recover()
 * @see LP_Template_Profile::order_message()
 */
add_action( 'learn-press/profile/order-details', LearnPress::instance()->template( 'profile' )->func( 'order_details' ), 5 );
add_action( 'learn-press/profile/order-details', LearnPress::instance()->template( 'profile' )->func( 'order_recover' ), 10 );
add_action( 'learn-press/profile/order-details', LearnPress::instance()->template( 'profile' )->func( 'order_message' ), 15 );

/**
 * @see LP_Template_Profile::dashboard_logged_in()
 * @deprecated 4.1.6
 */
// add_action( 'learn-press/profile/before-dashboard', LearnPress::instance()->template( 'profile' )->func( 'dashboard_statistic' ), 10 );
add_action(
	'learn-press/profile/dashboard-summary',
	LearnPress::instance()->template( 'profile' )->func( 'dashboard_featured_courses' ),
	20
);
add_action(
	'learn-press/profile/dashboard-summary',
	LearnPress::instance()->template( 'profile' )->func( 'dashboard_latest_courses' ),
	30
);

/**
 * @see LP_Template_Profile::dashboard_not_logged_in()
 * @see LP_Template_Profile::login_form()
 * @see LP_Template_Profile::register_form()
 */
add_action( 'learn-press/user-profile', LearnPress::instance()->template( 'profile' )->func( 'dashboard_not_logged_in' ), 5 );
add_action( 'learn-press/user-profile', LearnPress::instance()->template( 'profile' )->func( 'login_form' ), 10 );
add_action( 'learn-press/user-profile', LearnPress::instance()->template( 'profile' )->func( 'register_form' ), 15 );

/** BEGIN: Checkout page */
/**
 * @see LP_Template_Checkout::review_order()
 */
add_action( 'learn-press/before-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'review_order' ), 10 );
add_action( 'learn-press/after-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'account_logged_in' ), 20 );
add_action( 'learn-press/after-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'account_register' ), 30 );
add_action( 'learn-press/after-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'account_login' ), 40 );
add_action( 'learn-press/after-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'guest_checkout' ), 50 );
add_action( 'learn-press/after-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'order_comment' ), 60 );
add_action( 'learn-press/after-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'payment' ), 70 );
add_action( 'learn-press/after-checkout-form', LearnPress::instance()->template( 'checkout' )->func( 'terms' ), 80 );

// ******************************************************************************************************************* //

//add_action( 'learn-press/content-item-summary-class', 'learn_press_content_item_summary_classes', 15 );
add_action(
	'learn-press/before-content-item-summary/lp_quiz',
	LearnPress::instance()->template( 'course' )->callback( 'content-quiz/title.php' ),
	5
);
add_action( 'learn-press/content-item-summary/lp_quiz', LearnPress::instance()->template( 'course' )->callback( 'content-quiz/js' ), 25 );
// add_action( 'learn-press/parse-course-item', 'learn_press_control_displaying_course_item', 5 ); // comment by tungnx
//add_action( 'learn-press/after-single-course', 'learn_press_single_course_args', 5 );
add_filter( 'document_title_parts', 'learn_press_single_document_title_parts', 5 );

add_filter( 'body_class', 'learn_press_body_classes', 10 );
add_filter( 'post_class', 'learn_press_course_class', 15, 3 );
//add_action( 'wp_head', 'learn_press_single_course_args', 5 );
add_action(
	'learn-press/before-checkout-order-review',
	LearnPress::instance()->template( 'course' )->callback( 'checkout/form-logged-in.php' ),
	10
);
add_filter( 'comments_template_query_args', 'learn_press_comments_template_query_args' );
//add_filter( 'get_comments_number', 'learn_press_filter_get_comments_number' );

add_filter( 'excerpt_length', 'learn_press_custom_excerpt_length', 999 );
// add_filter( 'learn_press_get_template', LearnPress::instance()->template( 'general' )->func( 'filter_block_content_template' ), 10, 5 );

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
