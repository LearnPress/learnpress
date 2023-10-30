<?php
/**
 * All functions for LearnPress template
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\CourseMaterialTemplate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @see LP_Template_Course::button_retry()
 * @see LP_Template_Course::course_continue_button()
 * @see LP_Template_Course::course_external_button()
 */
if ( ! function_exists( 'learn_press_add_course_buttons' ) ) {
	function learn_press_add_course_buttons() {
		add_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'course_enroll_button' ), 5 );
		add_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'course_purchase_button' ), 10 );
		add_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'course_external_button' ), 15 );
		add_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'button_retry' ), 20 );
		add_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'course_continue_button' ), 25 );
		add_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'course_finish_button' ), 30 );
	}
}

if ( ! function_exists( 'learn_press_remove_course_buttons' ) ) {
	function learn_press_remove_course_buttons() {
		remove_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'course_enroll_button' ), 5 );
		remove_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'course_purchase_button' ), 10 );
		//remove_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'course_external_button' ), 15 );
		remove_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'button_retry' ), 20 );
		remove_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'course_continue_button' ), 25 );
		remove_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'course' )->func( 'course_finish_button' ), 30 );
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

		/**
		 * Show tab overview if
		 * 1. Course is preview
		 * OR
		 * 2. Course's content not empty
		 */
		if ( isset( $_GET['preview'] ) || $course ) {
			$defaults['overview'] = array(
				'title'    => esc_html__( 'Overview', 'learnpress' ),
				'priority' => 10,
				'callback' => LearnPress::instance()->template( 'course' )->callback( 'single-course/tabs/overview.php' ),
			);
		}

		$defaults['curriculum'] = array(
			'title'    => esc_html__( 'Curriculum', 'learnpress' ),
			'priority' => 30,
			'callback' => LearnPress::instance()->template( 'course' )->func( 'course_curriculum' ),
		);

		$defaults['instructor'] = array(
			'title'    => esc_html__( 'Instructor', 'learnpress' ),
			'priority' => 40,
			'callback' => LearnPress::instance()->template( 'course' )->callback( 'single-course/tabs/instructor.php' ),
		);

		if ( $course->get_faqs() ) {
			$defaults['faqs'] = array(
				'title'    => esc_html__( 'FAQs', 'learnpress' ),
				'priority' => 50,
				'callback' => LearnPress::instance()->template( 'course' )->func( 'faqs' ),
			);
		}

		$is_enrolled_course = false;
		if ( $user->has_course_status( $course->get_id(), array( LP_COURSE_ENROLLED ) )
			|| $user->is_instructor() || $user->is_admin() ) {
			$is_enrolled_course = true;
		}
		$file_per_page = LP_Settings::get_option( 'material_file_per_page', -1 );
		if ( $course->get_downloadable_material() && $is_enrolled_course && (int) $file_per_page != 0 ) {
			$defaults['materials'] = array(
				'title'    => esc_html__( 'Materials', 'learnpress' ),
				'priority' => 45,
				'callback' => LearnPress::instance()->template( 'course' )->func( 'metarials' ),
			);
		}

		$tabs = apply_filters( 'learn-press/course-tabs', $defaults );

		if ( $tabs ) {
			uasort( $tabs, 'learn_press_sort_list_by_priority_callback' );
			$request_tab = LP_Helper::sanitize_params_submitted( $_REQUEST['tab'] ?? '' );
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
				if ( $course && $user->has_course_status(
					$course->get_id(),
					array(
						'enrolled',
						'finished',
					)
				) && ! empty( $tabs['curriculum'] )
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

if ( ! function_exists( 'learn_press_content_item_summary_question' ) ) {
	function learn_press_content_item_summary_question() {
		$quiz     = LP_Global::course_item_quiz();
		$question = $quiz->get_viewing_question();

		if ( $question ) {
			$course      = learn_press_get_course();
			$user        = learn_press_get_current_user();
			$answered    = false;
			$course_data = $user->get_course_data( $course->get_id() );
			$user_quiz   = $course_data->get_item_quiz( $quiz->get_id() );

			if ( $user_quiz ) {
				$answered = $user_quiz->get_question_answer( $question->get_id() );
				$question->show_correct_answers(
					$user->has_checked_answer(
						$question->get_id(),
						$quiz->get_id(),
						$course->get_id()
					) ? 'yes' : false
				);
				$question->disable_answers( $user_quiz->get_status() == 'completed' ? 'yes' : false );
			}

			$question->render( $answered );
		}
	}
}


if ( ! function_exists( 'learn_press_content_item_body_class' ) ) {
	// Add more assets into page that displaying content of an item
	add_filter( 'body_class', 'learn_press_content_item_body_class', 10 );

	function learn_press_content_item_body_class( $classes ) {
		global $lp_course_item;

		if ( wp_is_mobile() ) {
			$sidebar_toggle_class = 'lp-sidebar-toggle__close';
		} else {
			$sidebar_toggle_class = learn_press_cookie_get( 'sidebar-toggle' ) ? 'lp-sidebar-toggle__close' : 'lp-sidebar-toggle__open';
		}

		if ( $lp_course_item ) {
			$classes[] = 'course-item-popup';
			$classes[] = 'viewing-course-item';
			$classes[] = 'viewing-course-item-' . $lp_course_item->get_id();
			$classes[] = 'course-item-' . $lp_course_item->get_item_type();
			$classes[] = $sidebar_toggle_class;
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

		/**
		 * Edit link for lesson/quiz or any other course's item.
		 */
		if ( $lp_course_item ) {
			$post_type_object = get_post_type_object( $lp_course_item->get_item_type() );

			if ( $post_type_object && current_user_can(
				'edit_post',
				$lp_course_item->get_id()
			) && $post_type_object->show_in_admin_bar && get_edit_post_link( $lp_course_item->get_id() ) ) {
				$type = get_post_type( $lp_course_item->get_id() );

				if ( apply_filters( 'learn-press/edit-admin-bar-button', true, $lp_course_item ) ) {
					$wp_admin_bar->add_menu(
						array(
							'id'    => 'edit-' . $type,
							'title' => $post_type_object->labels->edit_item,
							'href'  => get_edit_post_link( $lp_course_item->get_id() ),
						)
					);
				}
			}
		}

		/**
		 * Edit link for quiz's question.
		 */
		if ( $lp_quiz_question ) {
			$post_type_object = get_post_type_object( $lp_quiz_question->get_item_type() );
			$edit_post_link   = get_edit_post_link( $lp_quiz_question->get_id() );

			if ( $post_type_object && current_user_can(
				'edit_post',
				$lp_quiz_question->get_id()
			) && $post_type_object->show_in_admin_bar && $edit_post_link ) {
				$type = get_post_type( $lp_quiz_question->get_id() );
				$wp_admin_bar->add_menu(
					array(
						'id'    => 'edit-' . $type,
						'title' => $post_type_object->labels->edit_item,
						'href'  => $edit_post_link,
					)
				);
			}
		}
	}
}
add_filter( 'admin_bar_menu', 'learn_press_content_item_edit_links', 90 );

if ( ! function_exists( 'learn_press_single_quiz_args' ) ) {
	function learn_press_single_quiz_args() {
		$args = array();

		if ( LP_PAGE_QUIZ !== LP_Page_Controller::page_current() ) {
			return $args;
		}

		$quiz   = LP_Global::course_item_quiz();
		$course = learn_press_get_course();
		$user   = learn_press_get_current_user();
		if ( $quiz && $course ) {
			$course_id = $course->get_id();
			//$user_quiz = $user->get_item_data( $quiz->get_id(), $course_id );

			/*if ( $user_quiz ) {
				$remaining_time = $user_quiz->get_time_remaining();
			} else {
				$remaining_time = false;
			}*/

			$args = array(
				'id'                  => $quiz->get_id(),
				//'totalTime'           => -1,
				//'remainingTime'       => $remaining_time ? $remaining_time->get() : $quiz->get_duration()->get(),
				'status'              => $user->get_item_status( $quiz->get_id(), $course_id ),
				'checkNorequizenroll' => $course->is_no_required_enroll(),
				'navigationPosition'  => LP_Settings::get_option( 'navigation_position', 'yes' ),
			);
		}

		return apply_filters( 'learn-press/localize_script/quiz', $args, $user, $course, $quiz );
	}
}

if ( ! function_exists( 'learn_press_single_document_title_parts' ) ) {
	/**
	 * Custom document title depending on LP current page.
	 * E.g: Single course, profile, etc...
	 *
	 * @param array $title
	 *
	 * @return array
	 * @since 3.0.0
	 * @version 3.0.1
	 */
	function learn_press_single_document_title_parts( $title ) {
		if ( learn_press_is_course() ) {
			$item = LP_Global::course_item();

			if ( $item ) {
				$title['title'] = join(
					' ',
					apply_filters(
						'learn-press/document-course-title-parts',
						array(
							$title['title'],
							' &rarr; ',
							$item->get_title(),
						)
					)
				);
			}
		} elseif ( learn_press_is_courses() ) {
			if ( learn_press_is_search() ) {
				$title['title'] = esc_html__( 'Course Search Results', 'learnpress' );
			} else {
				$title['title'] = esc_html__( 'Courses', 'learnpress' );
			}
		} elseif ( LP_Page_Controller::is_page_profile() ) {
			$profile  = LP_Profile::instance();
			$tab_slug = $profile->get_current_tab();
			$tab      = $profile->get_tab_at( $tab_slug );
			$page_id  = learn_press_get_page_id( 'profile' );

			if ( $page_id ) {
				$page_title = get_the_title( $page_id );
			} else {
				$page_title = '';
			}

			if ( $tab instanceof LP_Profile_Tab ) {
				$title['title'] = join(
					' ',
					apply_filters(
						'learn-press/document-profile-title-parts',
						array(
							$page_title,
							'&rarr;',
							$tab->get( 'title' ),
						)
					)
				);
			}
		}

		return $title;
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
	 * @param int          $lesson_id
	 * @param int          $course_id
	 * @param array|string $class
	 * @param boolean      $echo
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
			'course-lesson course-item course-item-' . $lesson_id,
		);

		$user   = learn_press_get_current_user();
		$status = $user->get_item_status( $lesson_id );

		if ( $status ) {
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
	 * @param int          $quiz_id
	 * @param int          $course_id
	 * @param string|array $class
	 * @param boolean      $echo
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
			'course-quiz course-item course-item-' . $quiz_id,
		);

		$status = $user->get_item_status( $quiz_id );

		if ( $status ) {
			$classes[] = "item-has-status item-{$status}";
		}

		if ( $quiz_id && $course->is_current_item( $quiz_id ) ) {
			$classes[] = 'item-current';
		}

		/*
		if ( $user->can_view_item( $quiz_id, $course_id )->flag ) {
			$classes[] = 'viewable';
		}*/

		if ( $course->is_final_quiz( $quiz_id ) ) {
			$classes[] = 'final-quiz';
		}

		$classes = array_unique( array_merge( $classes, $class ) );

		if ( $echo ) {
			echo 'class="' . implode( ' ', $classes ) . '"';
		}

		return $classes;
	}
}

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

function learn_press_setup_user() {
	$GLOBALS['lp_user'] = learn_press_get_current_user();
}
add_action( 'init', 'learn_press_setup_user', 1000 );

/**
 * Display a message immediately with out push into queue
 *
 * @param        $message
 * @param string  $type
 * @Todo tungnx review code.
 */

function learn_press_display_message( $message, $type = 'success' ) {
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
 * @param string   $message
 * @param string   $type
 * @param array    $options
 * @param int|bool $current_user . @since 3.0.9 - add for current user only
 * @deprecated 4.2.2.1
 */
function learn_press_add_message( $message, $type = 'success', $options = array(), $current_user = true ) {
	if ( is_string( $options ) ) {
		$options = array( 'id' => $options );
	}

	$options = wp_parse_args(
		$options,
		array(
			'id' => '',
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

	$messages[ $type ][ $options['id'] ] = array(
		'content' => $message,
		'options' => $options,
	);

	learn_press_session_set( $key, $messages );
}

function learn_press_get_message( $message, $type = 'success' ) {
	ob_start();
	learn_press_display_message( $message, $type );
	$message = ob_get_clean();

	return $message;
}

/**
 * Set LP message to COOKIE.
 *
 * @param array $message_data ['status' => 'success/warning/error', 'content' => 'Message content']
 * @since 4.2.0
 * @version 1.0.0
 * @return void
 */
function learn_press_set_message( array $message_data = [] ) {
	if ( ! isset( $message_data ['status'] ) ) {
		error_log( 'Message data must have status' );
		return;
	}
	if ( ! isset( $message_data ['content'] ) ) {
		error_log( 'Message data must have content' );
		return;
	}

	$customer_id      = LP_Session_Handler::instance()->get_customer_id();
	$customer_message = [ $customer_id => $message_data ];
	update_option( 'lp-customer-message', $customer_message );
}

/**
 * Show message only one time.
 * @since 4.2.0
 * @version 1.0.0
 * @return void
 */
function learn_press_show_message() {
	try {
		$customer_id      = LP_Session_Handler::instance()->get_customer_id();
		$message_data     = get_option( 'lp-customer-message' ) ?? [];
		$customer_message = $message_data[ $customer_id ] ?? '';
		if ( ! $customer_message ) {
			return;
		}

		unset( $message_data[ $customer_id ] );
		update_option( 'lp-customer-message', $message_data );
		//delete_option( 'lp-message' );
		Template::instance()->get_frontend_template( 'global/lp-message.php', compact( 'customer_message' ) );
	} catch ( Throwable $e ) {
		error_log( $e->getMessage() );
	}
}
add_action( 'learn-press/before-course-item-content', 'learn_press_show_message', 50 );

/**
 * Remove message added into queue by id and/or type.
 *
 * @param string       $id
 * @param string|array $type
 *
 * @since 3.0.0
 */
function learn_press_remove_message( $id = '', $type = '' ) {
	$groups = learn_press_session_get( learn_press_session_message_id() );

	if ( ! $groups ) {
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

function learn_press_session_message_id() {
	return 'messages' . get_current_user_id();
}

if ( ! function_exists( 'learn_press_page_title' ) ) {

	/**
	 * learn_press_page_title function.
	 *
	 * @param boolean $echo
	 * @return string
	 */
	function learn_press_page_title( bool $echo = false ): string {
		$page_title = '';

		if ( is_search() ) {
			// Comment by tungnx
			/*$page_title = sprintf( __( 'Search Results for: &ldquo;%s&rdquo;', 'learnpress' ), get_search_query() );

			if ( get_query_var( 'paged' ) ) {
				$page_title .= sprintf( __( '&nbsp;&ndash; Page %s', 'learnpress' ), get_query_var( 'paged' ) );
			}*/
		} elseif ( is_tax() ) {
			$page_title = single_term_title( '', false );
		} else {
			$courses_page_id = learn_press_get_page_id( 'courses' );
			$page_title      = get_the_title( $courses_page_id );
		}

		return apply_filters( 'learn_press_page_title', $page_title );
	}
}

/**
 * Get template part.
 *
 * @param string $slug
 * @param string $name
 *
 * @return  string
 */
function learn_press_get_template_part( $slug, $name = '' ) {
	$template = '';

	if ( $name ) {
		$template = locate_template(
			array(
				"{$slug}-{$name}.php",
				learn_press_template_path() . "/{$slug}-{$name}.php",
			)
		);
	}

	// Get default slug-name.php
	if ( ! $template && $name && file_exists( LP_PLUGIN_PATH . "/templates/{$slug}-{$name}.php" ) ) {
		$template = LP_PLUGIN_PATH . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/learnpress/slug.php
	if ( ! $template ) {
		$template = locate_template( array( "{$slug}.php", learn_press_template_path() . "/{$slug}.php" ) );
	}
	// override path of child theme in parent theme - Fix for eduma by tuanta
	$file_ct_in_pr = apply_filters( 'learn_press_child_in_parrent_template_path', '' );
	if ( $file_ct_in_pr && $name ) {
		$template_child = locate_template(
			array(
				"{$slug}-{$name}.php",
				'lp-child-path/' . learn_press_template_path() . '/' . $file_ct_in_pr . "/{$slug}-{$name}.php",
			)
		);
		if ( $template_child && file_exists( $template_child ) ) {
			$template = $template_child;
		}
		// check in child theme if have filter learn_press_child_in_parrent_template_path

		$check_child_theme = get_stylesheet_directory() . '/' . learn_press_template_path() . "{$slug}-{$name}.php";
		if ( $check_child_theme && file_exists( $check_child_theme ) ) {
			$template = $check_child_theme;
		}
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
 * @param string $template_name .
 * @param array  $args (default: array()) .
 * @param string $template_path (default: '').
 * @param string $default_path (default: '').
 *
 * @return void
 */
function learn_press_get_template( $template_name = '', $args = array(), $template_path = '', $default_path = '' ) {
	if ( $args && is_array( $args ) ) {
		extract( $args );
	}

	if ( false === strpos( $template_name, '.php' ) ) {
		$template_name .= '.php';
	}

	$located = learn_press_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $located ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );

		$log = sprintf( 'TEMPLATE MISSING: Template %s doesn\'t exists.', $template_name );
		error_log( $log );

		if ( LP_Debug::is_debug() ) {
			echo sprintf( '<span title="%s" class="learn-press-template-warning"></span>', $log );
		}

		return;
	}

	// Allow 3rd party plugin filter template file from their plugin
	$located = apply_filters(
		'learn_press_get_template',
		$located,
		$template_name,
		$args,
		$template_path,
		$default_path
	);
	if ( $located != '' ) {
		do_action( 'learn_press_before_template_part', $template_name, $template_path, $located, $args );

		include $located;

		do_action( 'learn_press_after_template_part', $template_name, $template_path, $located, $args );
	}
}

/**
 * Get template content
 *
 * @param        $template_name
 * @param array         $args
 * @param string        $template_path
 * @param string        $default_path
 *
 * @return string
 * @uses learn_press_get_template();
 */
function learn_press_get_template_content( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	learn_press_get_template( $template_name, $args, $template_path, $default_path );

	return ob_get_clean();
}

/**
 * Locate a template and return the path for inclusion.
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

	/**
	 * Disable override templates in theme by default since LP 4.0.0
	 */
	if ( learn_press_override_templates() ) {
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name,
			)
		);
		// override path of child theme in parent theme - Fix for eduma by tuanta
		$file_ct_in_pr = apply_filters( 'learn_press_child_in_parrent_template_path', '' );
		if ( $file_ct_in_pr ) {
			$template_child = locate_template(
				array(
					trailingslashit( 'lp-child-path/' . $template_path . '/' . $file_ct_in_pr ) . $template_name,
					$template_name,
				)
			);
			if ( $template_child && file_exists( $template_child ) ) {
				$template = $template_child;
			}
			// check in child theme if have filter learn_press_child_in_parrent_template_path
			$check_child_theme = get_stylesheet_directory() . '/' . trailingslashit( $template_path ) . $template_name;
			if ( $check_child_theme && file_exists( $check_child_theme ) ) {
				$template = $check_child_theme;
			}
		}
	}
	if ( ! isset( $template ) || ! $template ) {
		$template = trailingslashit( $default_path ) . $template_name;
	}

	return apply_filters( 'learn_press_locate_template', $template, $template_name, $template_path );
}

/**
 * Returns the name of folder contains override template files in theme
 *
 * @return string
 * @since 3.0.0
 * @version 1.0.1
 */
function learn_press_template_path(): string {
	$lp_folder_name_override = apply_filters( 'learn_press_template_path', LP_PLUGIN_FOLDER_NAME );
	if ( ! is_string( $lp_folder_name_override ) ) {
		$lp_folder_name_override = LP_PLUGIN_FOLDER_NAME;
	}

	return $lp_folder_name_override;
}

/**
 * Disable override templates in theme by default
 *
 * @return bool
 * @since 4.0.0
 */
function learn_press_override_templates() {
	return apply_filters( 'learn-press/override-templates', false );
}

/**
 * Get html view path for admin to display
 *
 * @param $name
 * @param $plugin_file
 *
 * @return mixed
 */
function learn_press_get_admin_view( $name, $plugin_file = null ) {
	if ( ! preg_match( '/\.(html|php)$/', $name ) ) {
		$name .= '.php';
	}
	if ( $plugin_file ) {
		$view = dirname( $plugin_file ) . '/inc/admin/views/' . $name;
	} else {
		$view = LearnPress::instance()->plugin_path( 'inc/admin/views/' . $name );
	}

	return apply_filters( 'learn_press_admin_view', $view, $name );
}

function learn_press_admin_view_content( $name, $args = array() ) {
	return learn_press_admin_view( $name, $args, false, true );
}

/**
 * Find a full path of a view and display the content in admin
 *
 * @param            $name
 * @param array      $args
 * @param bool|false $include_once
 * @param bool
 *
 * @return bool
 */
function learn_press_admin_view( $name, $args = array(), $include_once = false, $return = false ) {
	$view = learn_press_get_admin_view( $name, ! empty( $args['plugin_file'] ) ? $args['plugin_file'] : null );

	if ( file_exists( $view ) ) {

		ob_start();

		is_array( $args ) && extract( $args );

		do_action( 'learn_press_before_display_admin_view', $name, $args );

		if ( $include_once ) {
			include_once $view;
		} else {
			include $view;
		}

		do_action( 'learn_press_after_display_admin_view', $name, $args );
		$output = ob_get_clean();

		if ( ! $return ) {
			learn_press_echo_vuejs_write_on_php( $output );
		}

		return $return ? $output : true;
	}

	return false;
}

if ( ! function_exists( 'learn_press_is_404' ) ) {
	/**
	 * Set header is 404
	 */
	function learn_press_is_404() {
		global $wp_query;
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
			echo '<!-- Template Location:' . str_replace( array( LP_PLUGIN_PATH, ABSPATH ), '', $located ) . ' -->';
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

if ( ! function_exists( 'learn_press_item_meta_type' ) ) {
	function learn_press_item_meta_type( $course, $item ) {
		?>

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

			<?php
		}
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

function learn_press_is_content_item_only() {
	return ! empty( $_REQUEST['content-item-only'] );
}

function learn_press_label_html( $label, $type = '' ) {
	?>
	<span class="lp-label label-<?php echo esc_attr( $type ? $type : $label ); ?>">
		<?php echo esc_html( $label ); ?>
	</span>
	<?php
}

/**
 * @param LP_Quiz $item
 * @deprecated 4.2.3.5
 */
function learn_press_quiz_meta_final( $item ) {
	_deprecated_function( __METHOD__, '4.2.3.5' );
	return;
	$course = learn_press_get_course();

	if ( ! $course->is_final_quiz( $item->get_id() ) ) {
		return;
	}
	echo '<span class="item-meta final-quiz">' . esc_html__( 'Final', 'learnpress' ) . '</span>';
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
				' SELECT count(*) '
				. " FROM {$wpdb->comments} "
				. ' WHERE comment_post_ID = %d '
				. ' AND comment_approved = 1 '
				. ' AND comment_type != %s ',
				$post_id,
				'review'
			);

			$count = $wpdb->get_var( $sql );

			// @since 3.0.0
			$count = apply_filters( 'learn-press/course-comments-number', $count, $post_id );
		}

		return $count;
	}
}

/**
 * Add custom classes to body tag class name
 *
 * @param array $classes
 *
 * @return array
 *
 * @since 3.0.0
 * @Todo tungnx review to remove
 * @deprecated 4.2.3
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

//add_filter( 'body_class', 'learn_press_body_classes', 10 );

/**
 * Return true if user is learning a course
 *
 * @param int $course_id
 *
 * @return bool
 * @since 3.0
 * @version 1.0.1
 */
function learn_press_is_learning_course( int $course_id = 0 ): bool {
	$is_learning = false;
	$user        = learn_press_get_current_user();
	$course      = learn_press_get_course( $course_id );
	if ( ! $course ) {
		return $is_learning;
	}

	if ( $user ) {
		return $user->has_enrolled_or_finished( $course_id );
	}

	if ( $course->is_no_required_enroll() ) {
		$is_learning = true;
	}

	return apply_filters( 'lp/is-learning-course', $is_learning, $course_id );
}

function learn_press_content_item_summary_class( $more = '', $echo = true ) {
	$classes = array( 'content-item-summary' );
	$classes = LP_Helper::merge_class( $classes, $more );
	$classes = apply_filters( 'learn-press/content-item-summary-class', $classes );
	$output  = 'class="' . esc_attr( join( ' ', $classes ) ) . '"';

	if ( $echo ) {
		echo wp_kses_post( $output );
	}

	return $output;
}

/**
 * @deprecated 4.2.3.1
 */
function learn_press_content_item_summary_classes( $classes ) {
	_deprecated_function( __FUNCTION__, '4.2.3.1' );
	$item = LP_Global::course_item();

	if ( ! $item ) {
		return $classes;
	}

	if ( $item->get_post_type() !== LP_LESSON_CPT ) {
		return $classes;
	}

	return $classes;
}

function learn_press_maybe_load_comment_js() {
	$item = LP_Global::course_item();

	if ( $item ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

add_action( 'wp_enqueue_scripts', 'learn_press_maybe_load_comment_js' );

/**
 * Get list layouts archive course setting
 *
 * @editor tungnx
 * @modify 4.1.3
 */
function learn_press_courses_layouts() {
	return apply_filters(
		'learnpress/archive-courses-layouts',
		[
			'grid' => 'Grid',
			'list' => 'List',
		]
	);
}

/**
 * Get layout template for archive course page.
 *
 * @return mixed
 * @since 3.3.0
 * @editor tungnx
 * @modify 4.1.3
 */
function learn_press_get_courses_layout() {
	$layout = LP_Request::get_cookie( 'courses-layout' );

	if ( ! $layout ) {
		$layout = LP_Settings::get_option( 'archive_courses_layout', 'list' );
	}

	return $layout;
}

function learn_press_register_sidebars() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Course Sidebar', 'learnpress' ),
			'id'            => 'course-sidebar',
			'description'   => esc_html__( 'Widgets in this area will be shown in a single course', 'learnpress' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widgettitle">',
			'after_title'   => '</h2>',
		)
	);
	register_sidebar(
		array(
			'name'          => esc_html__( 'All Courses', 'learnpress' ),
			'id'            => 'archive-courses-sidebar',
			'description'   => esc_html__( 'Widgets in this area will be shown on all course pages', 'learnpress' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widgettitle">',
			'after_title'   => '</h2>',
		)
	);
}

add_action( 'widgets_init', 'learn_press_register_sidebars' );

function learn_press_setup_theme() {
	$support = array(
		'widgets' => array(
			// Place three core-defined widgets in the sidebar area.
			'course-sidebar' => array(
				'xxx' => array( 'lp-widget-course-progress' ),
				'yyy' => array( 'lp-widget-course-info' ),
			),
		),
	);

	add_theme_support( 'starter-content', $support );
}

add_action( 'after_setup_theme', 'learn_press_setup_theme' );

/**
 * @param LP_Question $question
 * @param array       $args
 *
 * @return array
 * @since 4.x.x
 */
function learn_press_get_question_options_for_js( $question, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'cryptoJsAes'     => false,
			'include_is_true' => true,
			'answer'          => false,
		)
	);

	if ( $args['cryptoJsAes'] ) {
		$options = array_values( $question->get_answer_options() );

		$key     = uniqid();
		$options = array(
			'data' => cryptoJsAesEncrypt( $key, wp_json_encode( $options ) ),
			'key'  => $key,
		);
	} else {
		$exclude_option_key = array( 'question_id', 'order' );
		if ( ! $args['include_is_true'] ) {
			$exclude_option_key[] = 'is_true';
		}

		$options = array_values(
			$question->get_answer_options(
				array(
					'exclude' => $exclude_option_key,
					'map'     => array( 'question_answer_id' => 'uid' ),
					'answer'  => $args['answer'],
				)
			)
		);
	}

	return $options;
}

function learn_press_custom_excerpt_length( $length ) {
	return 20;
}

/**
 * Get post meta with key _lp_duration and translate.
 *
 * @param int    $post_id
 * @param string $default
 *
 * @return string
 * @since 4.0.0
 */
function learn_press_get_post_translated_duration( $post_id, $default = '' ) {
	$duration = get_post_meta( $post_id, '_lp_duration', true );

	$duration_arr = explode( ' ', $duration );
	$duration_str = '';

	if ( count( $duration_arr ) > 1 ) {
		$duration_number = $duration_arr[0];
		$duration_text   = $duration_arr[1];

		switch ( strtolower( $duration_text ) ) {
			case 'minute':
				$duration_str = sprintf(
					_n( '%s minute', '%s minutes', $duration_number, 'learnpress' ),
					$duration_number
				);
				break;
			case 'hour':
				$duration_str = sprintf(
					_n( '%s hour', '%s hours', $duration_number, 'learnpress' ),
					$duration_number
				);
				break;
			case 'day':
				$duration_str = sprintf( _n( '%s day', '%s days', $duration_number, 'learnpress' ), $duration_number );
				break;
			case 'week':
				$duration_str = sprintf(
					_n( '%s week', '%s weeks', $duration_number, 'learnpress' ),
					$duration_number
				);
				break;
			default:
				$duration_str = $duration;
		}
	}

	return empty( absint( $duration ) ) ? $default : $duration_str;
}

/**
 * Get level post meta.
 *
 * @param int $post_id
 *
 * @return string
 */
function learn_press_get_post_level( $post_id ) {
	$level = get_post_meta( $post_id, '_lp_level', true );

	return apply_filters(
		'learn-press/level-label',
		! empty( $level ) ? lp_course_level()[ $level ] : esc_html__( 'All levels', 'learnpress' ),
		$post_id
	);
}

function lp_course_level() {
	return apply_filters(
		'lp/template/function/course/level',
		array(
			''             => esc_html__( 'All levels', 'learnpress' ),
			'beginner'     => esc_html__( 'Beginner', 'learnpress' ),
			'intermediate' => esc_html__( 'Intermediate', 'learnpress' ),
			'expert'       => esc_html__( 'Expert', 'learnpress' ),
		)
	);
}

/**
 * Get slug for logout action in user profile.
 *
 * @return string
 * @since 4.0.0
 */
function learn_press_profile_logout_slug() {
	return apply_filters( 'learn-press/profile-logout-slug', 'lp-logout' );
}

function lp_get_email_content( $format, $meta = array(), $field = array() ) {
	if ( $meta && isset( $meta[ $format ] ) ) {
		$content = stripslashes( $meta[ $format ] );
	} else {
		$template      = ! empty( $field[ "template_{$format}" ] ) ? $field[ "template_{$format}" ] : null;
		$template_file = $field['template_base'] . $template;
		$content       = LP_WP_Filesystem::instance()->file_get_contents( $template_file );
	}

	return $content;
}

function lp_skeleton_animation_html( $count_li = 3, $width = 'random', $styleli = '', $styleul = '' ) {
	?>
	<ul class="lp-skeleton-animation" style="<?php echo esc_attr( $styleul ); ?>">
		<?php for ( $i = 0; $i < absint( $count_li ); $i++ ) : ?>
			<li style="width: <?php echo esc_attr( $width === 'random' ? wp_rand( 60, 100 ) . '%' : $width ); ?>; <?php echo ! empty( $styleli ) ? $styleli : ''; ?>"></li>
		<?php endfor; ?>
	</ul>

	<?php
}

add_filter( 'lp_format_page_content', 'wptexturize' );
add_filter( 'lp_format_page_content', 'convert_smilies' );
add_filter( 'lp_format_page_content', 'convert_chars' );
add_filter( 'lp_format_page_content', 'wpautop' );
add_filter( 'lp_format_page_content', 'shortcode_unautop' );
add_filter( 'lp_format_page_content', 'prepend_attachment' );
add_filter( 'lp_format_page_content', 'do_shortcode', 11 );
add_filter( 'lp_format_page_content', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 );

if ( function_exists( 'do_blocks' ) ) {
	add_filter( 'lp_format_page_content', 'do_blocks', 9 );
}

function lp_format_page_content( $raw_string ) {
	return apply_filters( 'lp_format_page_content', $raw_string );
}

if ( ! function_exists( 'lp_profile_page_content' ) ) {
	function lp_profile_page_content() {
		$profile_id = learn_press_get_page_id( 'profile' );

		if ( $profile_id ) {
			$profile_page = get_post( $profile_id );

			// remove_shortcode( 'learn_press_profile' );
			$description = lp_format_page_content( wp_kses_post( $profile_page->post_content ) );

			if ( $description ) {
				echo '<div class="lp-profile-page__content">' . $description . '</div>';
			}
		}
	}
}

if ( ! function_exists( 'lp_archive_course_page_content' ) ) {
	add_action( 'lp/template/archive-course/description', 'lp_archive_course_page_content' );

	function lp_archive_course_page_content() {
		if ( is_search() ) {
			return;
		}

		if ( is_post_type_archive( LP_COURSE_CPT ) && in_array( absint( get_query_var( 'paged' ) ), array( 0, 1 ), true ) ) {
			$profile_id = learn_press_get_page_id( 'courses' );

			if ( $profile_id ) {
				$profile_page = get_post( $profile_id );

				$description = lp_format_page_content( wp_kses_post( $profile_page->post_content ) );
				if ( $description ) {
					echo '<div class="lp-course-page__content">' . $description . '</div>';
				}
			}
		}
	}
}

if ( ! function_exists( 'lp_taxonomy_archive_course_description' ) ) {
	add_action( 'lp/template/archive-course/description', 'lp_taxonomy_archive_course_description' );

	function lp_taxonomy_archive_course_description() {

		if ( learn_press_is_course_tax() && 0 === absint( get_query_var( 'paged' ) ) ) {
			$term = get_queried_object();

			if ( $term && ! empty( $term->description ) ) {
				echo '<div class="lp-archive-course-term-description">' . lp_format_page_content( wp_kses_post( $term->description ) ) . '</div>';
			}
		}
	}
}

/**
 * Params to query courses on Archive Course.
 *
 * @return array
 */
function lp_archive_skeleton_get_args(): array {
	$args = [];

	if ( ! empty( $_GET ) ) {
		$args = $_GET;
	}

	global $wp_query;
	if ( ! empty( $wp_query->get( 'paged' ) ) ) {
		$args['paged'] = $wp_query->get( 'paged' );
	}

	if ( learn_press_is_course_category() || learn_press_is_course_tag() ) {
		$cat = get_queried_object();

		$args['term_id']  = $cat->term_id;
		$args['taxonomy'] = $cat->taxonomy;
	}

	$args = apply_filters( 'lp/template/archive-course/skeleton/args', $args );
	if ( ! is_array( $args ) ) {
		$args = [];
	}

	return LP_Helper::sanitize_params_submitted( $args, 'key' );
}
