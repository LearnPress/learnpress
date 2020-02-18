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


if ( ! function_exists( 'learn_press_add_course_buttons' ) ) {
	function learn_press_add_course_buttons() {
		add_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'course_enroll_button' ), 5 );
		add_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'course_purchase_button' ), 10 );
		add_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'course_external_button' ), 15 );
		add_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'button_retry' ), 20 );
		add_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'course_continue_button' ), 25 );
		add_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'course_finish_button' ), 30 );
	}
}

if ( ! function_exists( 'learn_press_remove_course_buttons' ) ) {
	function learn_press_remove_course_buttons() {
		remove_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'course_enroll_button' ), 5 );
		remove_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'course_purchase_button' ), 10 );
		remove_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'course_external_button' ), 15 );
		remove_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'button_retry' ), 20 );
		remove_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'course_continue_button' ), 25 );
		remove_action( 'learn-press/course-buttons', LP()->template( 'course' )->func( 'course_finish_button' ), 30 );
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
				'callback' => LP()->template( 'course' )->callback( 'single-course/tabs/overview.php' )
			);
		}

		// Curriculum
		$defaults['curriculum'] = array(
			'title'    => __( 'Curriculum', 'learnpress' ),
			'priority' => 30,
			'callback' => LP()->template( 'course' )->callback( 'single-course/tabs/curriculum.php' )
		);

		$defaults['instructor'] = array(
			'title'    => __( 'Instructor', 'learnpress' ),
			'priority' => 40,
			'callback' => LP()->template( 'course' )->callback( 'single-course/tabs/instructor.php' )
		);

		if ( $course->get_faqs() ) {
			$defaults['faqs'] = array(
				'title'    => __( 'FAQs', 'learnpress' ),
				'priority' => 50,
				'callback' => LP()->template( 'course' )->func( 'faqs' )
			);
		}


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
				$question->show_correct_answers( $user->has_checked_answer( $question->get_id(), $quiz->get_id(), $course->get_id() ) ? 'yes' : false );
				$question->disable_answers( $user_quiz->get_status() == 'completed' ? 'yes' : false );
			}

			$question->render( $answered );
		}
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

if ( ! function_exists( 'learn_press_content_item_script' ) ) {
	/**
	 * Add custom scripts + styles into head
	 */
	function learn_press_content_item_script() {
		global $lp_course_item;

		if ( ! $lp_course_item ) {
			return;
		}
		?>
        <style type="text/css">
            html, body {
                overflow: hidden;
            }

            body {
                _opacity: 0;

            }

            body.course-item-popup #wpadminbar {
                _display: none;
            }

            /*body.course-item-popup #learn-press-course-curriculum {*/
            /*position: fixed;*/
            /*top: 60px;*/
            /*bottom: 0;*/
            /*left: 0;*/
            /*background: #FFF;*/
            /*border-right: 1px solid #DDD;*/
            /*overflow: auto;*/
            /*z-index: 9999;*/
            /*}*/

            /*body.course-item-popup #learn-press-content-item {*/
            /*position: fixed;*/
            /*z-index: 9999;*/
            /*background: #FFF;*/
            /*top: 60px;*/
            /*right: 0;*/
            /*bottom: 0;*/
            /*overflow: visible;*/
            /*}*/
        </style>
		<?php
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
		//add_action( 'wp_print_scripts', 'learn_press_content_item_script', 10 );
	}
}

if ( ! function_exists( 'learn_press_single_course_args' ) ) {
	function learn_press_single_course_args() {
		static $output = array();
		if ( ! $output ) {
			if ( ( $course = LP_Global::course() ) && $course->get_id() ) {
				$user = LP_Global::user();
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

			if ( $user_quiz = $user->get_item_data( $quiz->get_id(), LP_Global::course( true ) ) ) {
				$remaining_time = $user_quiz->get_time_remaining();
			} else {
				$remaining_time = false;
			}

			$args = array(
				'id'            => $quiz->get_id(),
				'totalTime'     => $quiz->get_duration()->get(),
				'remainingTime' => $remaining_time ? $remaining_time->get() : $quiz->get_duration()->get(),
				'status'        => $user->get_item_status( $quiz->get_id(), LP_Global::course( true ) )
			);
		}

		return $args;
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
	 *
	 */
	function learn_press_single_document_title_parts( $title ) {
		// Single course page
		if ( learn_press_is_course() ) {
			if ( $item = LP_Global::course_item() ) {
				$title['title'] = join(
					' ',
					apply_filters(
						'learn-press/document-course-title-parts',
						array(
							$title['title'],
							" &rarr; ",
							$item->get_title()
						)
					)
				);
			}
		} elseif ( learn_press_is_courses() ) {
			if ( learn_press_is_search() ) {
				$title['title'] = __( 'Course Search Results', 'learnpress' );
			} else {
				$title['title'] = __( 'Courses', 'learnpress' );
			}
		} elseif ( learn_press_is_profile() ) {
			$profile  = LP_Profile::instance();
			$tab_slug = $profile->get_current_tab();
			$tab      = $profile->get_tab_at( $tab_slug );
			if ( $page_id = learn_press_get_page_id( 'profile' ) ) {
				$page_title = get_the_title( $page_id );
			} else {
				$page_title = '';
			}
			if ( $tab ) {
				$title['title'] = join(
					' ',
					apply_filters(
						'learn-press/document-profile-title-parts',
						array(
							$page_title,
							'&rarr;',
							$tab['title']
						)
					)
				);
			}
		}

		return $title;
	}
}


///////////////////////////////////////


if ( ! function_exists( 'learn_press_enroll_script' ) ) {
	/**
	 */
	function learn_press_enroll_script() {
		learn_press_assets()->enqueue_script( 'learn-press-enroll', LP()->plugin_url( 'assets/js/frontend/enroll.js' ), array( 'learn-press-js' ) );
	}
}


if ( ! function_exists( 'learn_press_course_loop_item_user_progress' ) ) {
	function learn_press_course_loop_item_user_progress() {
		$course = LP_Global::course();
		$user   = LP_Global::user();

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
		///echo "123456";learn_press_debug($post, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));

		if ( isset( $GLOBALS['course'] ) ) {
			unset( $GLOBALS['course'] );
		}
		$object = learn_press_get_course( $post );
		//$object->prepare();
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
 * @param string   $message
 * @param string   $type
 * @param array    $options
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
 * @param string       $id
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
					$redirect = apply_filters( 'learn_press_quiz_access_denied_redirect_permalink', $redirect, $quiz_status, $quiz->id, $user->get_id() );
					break;
				case LP_COURSE_CPT:
					if ( ( $course = learn_press_get_course() ) && $item_id = $course->is_viewing_item() ) {
						if ( ! LP()->user->can_view_item( $item_id ) ) {
							$redirect = apply_filters( 'learn_press_lesson_access_denied_redirect_permalink', $course->permalink, $item_id, $user->get_id() );
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

	if ( false === strpos( $template_name, '.php' ) ) {
		$template_name .= '.php';
	}

	$located = learn_press_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $located ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );

		$log = sprintf( 'TEMPLATE MISSING: Template %s doesn\'t exists.', $template_name );
		error_log( $log );

		if ( learn_press_is_debug() ) {
			echo sprintf( '<span title="%s" class="learn-press-template-warning"></span>', $log );
		}

		return;
	}

	// Allow 3rd party plugin filter template file from their plugin
	$located = apply_filters( 'learn_press_get_template', $located, $template_name, $args, $template_path, $default_path );
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
 * @param array  $args
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
 * @param string $default_path  (default: '')
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
		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name
			)
		);
	}

	// Get default template
	if ( ! isset( $template ) || ! $template ) {
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

/**
 * Disable override templates in theme by default
 *
 * @return bool
 * @since 4.0.0
 *
 */
function learn_press_override_templates() {
	return apply_filters( 'learn-press/override-templates', false );
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
				'viewable'  => $item->post_type == 'lp_quiz' ? ( $user->can_view_quiz( $item->ID, $course->get_id() ) !== false ) : ( $user->can_view_lesson( $item->ID, $course->get_id() ) !== false )
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


if ( ! function_exists( 'learn_press_content_item_review_quiz_title' ) ) {
	function learn_press_content_item_review_quiz_title() {
		if ( learn_press_is_review_questions() ) {
			learn_press_get_template( 'content-quiz/review-title.php' );
		}
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
	echo '<span class="item-meta count-questions">' . sprintf( $count ? _n( '%d question', '%d questions', $count, 'learnpress' ) : __( '%d question', 'learnpress' ), $count ) . '</span>';
}

/**
 * @param LP_Quiz|LP_Lesson $item
 */
function learn_press_item_meta_duration( $item ) {
	$duration = $item->get_duration();

	$html = '';
	if ( is_a( $duration, 'LP_Duration' ) && $duration->get() ) {
		$format = array(
			'day'    => _x( '%s day', 'duration', 'learnpress' ),
			'hour'   => _x( '%s hour', 'duration', 'learnpress' ),
			'minute' => _x( '%s min', 'duration', 'learnpress' ),
			'second' => _x( '%s sec', 'duration', 'learnpress' ),
		);

		$seconds = $duration->get();

		if ( $seconds <= DAY_IN_SECONDS ) {
			$m = explode( ':', date( 'H:i:s', $seconds ) );

			if ( $m[2] === '00' ) {
				unset( $m[2] );
			}

			if ( $m[0] === '00' ) {
				$m[0] = '0';
			}

			$html = join( ':', $m );
		} else {
			$html = $duration->to_timer( $format, true );
		}
	} elseif ( is_string( $duration ) && strlen( $duration ) ) {
		$html = $duration;
	}

	if ( ! $html ) {
		return;
	}

	printf( '<span class="item-meta duration">%s</span>', $html );
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


//if ( ! function_exists( 'learn_press_reset_single_item_summary_content' ) ) {
//	function learn_press_reset_single_item_summary_content() {
//		if ( isset( $_REQUEST['content-only'] ) ) {
//			global $wp_filter;
//			if ( isset( $wp_filter['learn-press/single-item-summary'] ) ) {
//				unset( $wp_filter['learn-press/single-item-summary'] );
//			}
//
//			$course = learn_press_get_course();
//			$course->get_curriculum();
//
//			add_action( 'learn-press/single-item-summary', 'learn_press_single_course_content_item', 10 );
//		}
//	}
//}

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
			'title'     => __( 'Primary (#ffb606)', 'learnpress' ),
			'id'        => 'primary-color',
			'selectors' => array(
				'body.course-item-popup a' => "color"
			),
			'std'       => '#ffb606',
			'value'     => '#ffb606'
		),
		array(
			'title'     => __( 'Secondary (#442e66)', 'learnpress' ),
			'id'        => 'secondary-color',
			'selectors' => array(
				'body.course-item-popup a' => "color"
			),
			'std'       => '#442e66',
			'value'     => '#442e66'
		),

//		array(
//			'title'     => __( 'Popup heading background', 'learnpress' ),
//			'id'        => 'popup-heading-bg',
//			'selectors' => array(
//				'#course-item-content-header' => "background-color"
//			),
//			'std'       => '#e7f7ff'
//		),
//		array(
//			'title'     => __( 'Popup heading color', 'learnpress' ),
//			'id'        => 'popup-heading-color',
//			'selectors' => array(
//				'#course-item-content-header a'                                      => "color",
//				'#course-item-content-header .course-item-search input'              => "color",
//				'#course-item-content-header .course-item-search input:focus'        => "color",
//				'#course-item-content-header .course-item-search input::placeholder' => "color",
//				'#course-item-content-header .course-item-search button'             => "color",
//			),
//			'std'       => ''
//		),
//		array(
//			'title'     => __( 'Popup curriculum background', 'learnpress' ),
//			'id'        => 'popup-curriculum-background',
//			'selectors' => array(
//				'body.course-item-popup .course-curriculum ul.curriculum-sections .section-content .course-item' => "background-color",
//				'body.course-item-popup #learn-press-course-curriculum'                                          => "background-color",
//			),
//			'std'       => '#FFF'
//		),
//		array(
//			'title'     => __( 'Popup item color', 'learnpress' ),
//			'id'        => 'popup-item-color',
//			'selectors' => array(
//				'body.course-item-popup .course-curriculum ul.curriculum-sections .section-content .course-item a' => "color",
//			),
//			'std'       => ''
//		),
//		array(
//			'title'     => __( 'Popup active item background', 'learnpress' ),
//			'id'        => 'popup-active-item-background',
//			'selectors' => array(
//				'body.course-item-popup .course-curriculum ul.curriculum-sections .section-content .course-item.current' => "background-color",
//			),
//			'std'       => '#F9F9F9'
//		),
//		array(
//			'title'     => __( 'Popup active item color', 'learnpress' ),
//			'id'        => 'popup-active-item-color',
//			'selectors' => array(
//				'body.course-item-popup .course-curriculum ul.curriculum-sections .section-content .course-item.current a' => "color",
//			),
//			'std'       => ''
//		),
//		array(
//			'title'     => __( 'Popup content background', 'learnpress' ),
//			'id'        => 'popup-content-background',
//			'selectors' => array(
//				'body.course-item-popup #learn-press-content-item' => "background-color"
//			),
//			'std'       => '#FFF'
//		),
//		array(
//			'title'     => __( 'Popup content color', 'learnpress' ),
//			'id'        => 'popup-content-color',
//			'selectors' => array(
//				'body.course-item-popup #learn-press-content-item' => "color"
//			),
//			'std'       => ''
//		),
//		array(
//			'title'     => __( 'Section heading background', 'learnpress' ),
//			'id'        => 'section-heading-bg',
//			'selectors' => array(
//				'body.course-item-popup #learn-press-course-curriculum .section-header' => 'background'
//			)
//		),
//		array(
//			'title'     => __( 'Section heading color', 'learnpress' ),
//			'id'        => 'section-heading-color',
//			'selectors' => array(
//				'body.course-item-popup #learn-press-course-curriculum .section-header' => 'color'
//			)
//		),
//		array(
//			'title'     => __( 'Section heading bottom color', 'learnpress' ),
//			'id'        => 'section-heading-bottom-color',
//			'selectors' => array(
//				'.course-curriculum ul.curriculum-sections .section-header' => 'border-bottom: 1px solid %s'
//			),
//			'std'       => '#00adff'
//		),
//		array(
//			'title'     => __( 'Lines color', 'learnpress' ),
//			'id'        => 'lines-color',
//			'selectors' => array(
//				'#course-item-content-header'                                             => 'border-bottom: 1px solid %s',
//				'.course-curriculum ul.curriculum-sections .section-content .course-item' => 'border-bottom: 1px solid %s',
//				'body.course-item-popup #learn-press-course-curriculum'                   => 'border-right: 1px solid %s',
//				'#course-item-content-header .toggle-content-item'                        => 'border-left: 1px solid %s'
//			),
//			'std'       => 'DDD'
//		),
//		array(
//			'title'     => __( 'Profile cover background', 'learnpress' ),
//			'id'        => 'profile-cover-bg',
//			'selectors' => array(
//				'#learn-press-profile-header' => 'background-color'
//			),
//			'std'       => '#f0defb'
//		),
//		array(
//			'title'     => __( 'Scrollbar', 'learnpress' ),
//			'id'        => 'scroll-bar',
//			'selectors' => array(
//				'.scrollbar-light > .scroll-element.scroll-y .scroll-bar' => 'background-color',
//				'.scrollbar-light > .scroll-element .scroll-element_size' => 'background'
//			),
//			'std'       => '#12b3ff'
//		),
//		array(
//			'title'     => __( 'Progress bar color', 'learnpress' ),
//			'id'        => 'progress-bar-color',
//			'selectors' => array(
//				'.learn-press-progress .progress-bg' => 'background-color'
//			),
//			'std'       => '#DDDDDD'
//		),
//		array(
//			'title'     => __( 'Progress bar active color', 'learnpress' ),
//			'id'        => 'scroll-bar',
//			'selectors' => array(
//				'.learn-press-progress .progress-bg .progress-active' => 'background-color'
//			),
//			'std'       => '#00adff'
//		),
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

/**
 * Check if an user can access content of a course.
 *
 * @param int $course_id
 * @param int $user_id
 *
 * @return bool
 * @since 3.x.x
 *
 */
function learn_press_user_can_access_course( $course_id, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( $user = learn_press_get_user( $user_id ) ) {
		return $user->can_access_course( $course_id );
	}

	return false;
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

add_filter( 'learn-press/can-view-item', 'learn_press_filter_can_view_item', 10, 4 );

function learn_press_filter_can_view_item( $view, $item_id, $course_id, $user_id ) {
	$user = learn_press_get_user( $user_id );

	if ( ! get_post_meta( $course_id, '_lp_submission', true ) ) {
		update_post_meta( $course_id, '_lp_submission', 'yes' );
	}
	$_lp_submission = get_post_meta( $course_id, '_lp_submission', true );
	if ( $_lp_submission === 'yes' ) {
		if ( ! $user->is_logged_in() ) {
			return 'not-logged-in';
		} else if ( ! $user->has_enrolled_course( $course_id ) ) {
			return 'not-enrolled';
		}
	}

	return $view;
}


//function learn_press_get_link_current_question_instead_of_continue_button( $link, $item ) {
//	if ( get_post_type( $item->get_id() ) === LP_QUIZ_CPT ) {
//		$user      = LP_Global::user();
//		$course    = $item->get_course();
//		$quiz_data = $user->get_item_data( $item->get_id(), $course->get_id() );
//		if ( $quiz_data && $quiz_data->get_status() === 'started' ) {
//			$link = $item->get_question_link( $quiz_data->get_current_question() );
//		}
//	}
//
//	return $link;
//}

//add_filter( 'learn-press/course-item-link', 'learn_press_get_link_current_question_instead_of_continue_button', 10, 2 );

/** 3.3.0 */
add_filter( 'learn-press/can-view-item', function ( $viewable, $item_id, $course_id ) {
	return $viewable;
}, 10, 3 );

add_filter( 'learn-press/course-item-content-html', function ( $html, $item_id, $course_id ) {
	$user = learn_press_get_current_user();

	$course_blocking = LP()->settings()->get( 'course_blocking' );
	$course_data     = $user->get_course_data( $course_id );
	//$end_time        = $course_data->get_end_time_gmt();
	//$expired_time    = $course_data->get_expiration_time_gmt();
	ob_start();

	switch ( $course_blocking ) {
		case 'duration_expire':
			if ( $course_data->is_exceeded() ) {
				$html = __( 'Course duration is expired. Please contact admin site.', 'learnpress' );
			}

			break;
		case 'course_finished':

			if ( $user->has_finished_course( $course_id ) ) {
				$html = __( 'You finished this course. Please contact admin site.', 'learnpress' );
			}

			break;
		case 'duration_expire_or_course_finished':
			if ( $course_data->is_exceeded() || $user->has_finished_course( $course_id ) ) {
				$html = __( 'Course duration is expired or you finished course. Please contact admin site.', 'learnpress' );
			}

			var_dump( $course_data->is_exceeded(), $user->has_finished_course( $course_id ) );
		default:

	}
	if ( $html ) {
		echo $html;
	}
	$html = ob_get_clean();

	return $html ? $html : false;
}, 10, 3 );

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

/***************************/
/********** 3.3.0 **********/
/***************************/

function learn_press_courses_layouts() {
	return apply_filters( 'learn-press/courses-layouts', array( 'grid', 'list' ) );
}

/**
 * Get layout template for archive course page.
 *
 * @return mixed
 * @since 3.3.0
 *
 */
function learn_press_get_courses_layout() {
	$layouts = learn_press_courses_layouts();

	if ( ! $layout = LP_Request::get_cookie( 'courses-layout' ) ) {
		$layout = defined( 'LP_COURSES_LAYOUT' ) ? LP_COURSES_LAYOUT : LP()->settings()->get( 'course_layout' );
	}

	if ( ! $layout || ! in_array( $layout, $layouts ) ) {
		$layout = reset( $layouts );
	}

	return $layout;
}

function learn_press_course_categoriesx( $post = 0 ) {
	$post = get_post( $post );

	$terms = get_object_term_cache( $post->ID, 'course_category' );
	if ( false === $terms ) {
		$terms = wp_get_object_terms( $post->ID, 'course_category' );
	}

	if ( ! $terms ) {
		return;
	}

	?>
    <div class="course-categories">
		<?php foreach ( $terms as $term ) { ?>
            <a href="<?php echo esc_attr( get_term_link( $term ) ); ?>"><?php echo $term->name; ?></a>
		<?php } ?>
    </div>
	<?php
}

function learn_press_register_sidebars() {
	register_sidebar(
		array(
			'name'          => __( 'Course Sidebar', 'learnpress' ),
			'id'            => 'course-sidebar',
			'description'   => __( 'Widgets in this area will be shown in single course', 'learnpress' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widgettitle">',
			'after_title'   => '</h2>',
		)
	);
	register_sidebar(
		array(
			'name'          => __( 'All Courses', 'learnpress' ),
			'id'            => 'archive-courses-sidebar',
			'description'   => __( 'Widgets in this area will be shown in all courses page', 'learnpress' ),
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
				'yyy' => array( 'lp-widget-course-info' )
			)
		)
	);

	add_theme_support( 'starter-content', $support );

}

add_action( 'after_setup_theme', 'learn_press_setup_theme' );

if ( ! function_exists( 'learn_press_page_title' ) ) {
	function learn_press_page_title( $echo = true ) {

		if ( is_search() ) {
			$page_title = sprintf( __( 'Search results: &ldquo;%s&rdquo;', 'learnpress' ), get_search_query() );

			if ( get_query_var( 'paged' ) ) {
				$page_title .= sprintf( __( '&nbsp;&ndash; Page %s', 'learnpress' ), get_query_var( 'paged' ) );
			}
		} elseif ( is_tax() ) {

			$page_title = single_term_title( '', false );

		} else {

			$page_id    = learn_press_get_page_id( 'courses' );
			$page_title = get_the_title( $page_id );
		}

		$page_title = apply_filters( 'learn-press/page-title', $page_title );

		if ( $echo ) {
			echo $page_title;
		}

		return $page_title;
	}
}

/**
 * @param LP_Question $question
 * @param array       $args
 *
 * @return array
 * @since 4.x.x
 *
 */
function learn_press_get_question_options_for_js( $question, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'cryptoJsAes'     => false,
			'include_is_true' => true
		)
	);

	if ( $args['cryptoJsAes'] ) {
		$options = array_values( $question->get_answer_options() );

		$key     = uniqid();
		$options = array(
			'data' => cryptoJsAesEncrypt( $key, wp_json_encode( $options ) ),
			'key'  => $key
		);
	} else {
		$exclude_option_key = array( 'question_id', 'order' );
		if ( ! $args['include_is_true'] ) {
			$exclude_option_key[] = 'is_true';
		}

		$options = array_values( $question->get_answer_options(
			array(
				'exclude' => $exclude_option_key,
				'map'     => array( 'question_answer_id' => 'uid' )
			)
		) );
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
 *
 */
function learn_press_get_post_translated_duration( $post_id, $default = '' ) {
	if ( ! $duration = get_post_meta( $post_id, '_lp_duration', true ) ) {
		return $default;
	}

	return $duration;
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

	return apply_filters( 'learn-press/level-label', $level ? ucwords( $level ) : __( 'All levels', 'learnpress' ), $post_id );
}

function learn_press_is_preview_course() {
	$course_id = isset( $GLOBALS['preview_course'] ) ? $GLOBALS['preview_course'] : 0;

	return $course_id && get_post_type( $course_id ) === LP_COURSE_CPT;
}

/**
 * Get text of button 'Process' in checkout page.
 *
 * @return string
 * @since 4.0.0
 *
 */
function learn_press_get_checkout_proceed_button_text() {
	return apply_filters( 'learn-press/checkout-proceed-button-text', __( 'Proceed', 'learnpress' ) );
}

/**
 * Get slug for logout action in user profile.
 *
 * @return string
 * @since 4.0.0
 *
 */
function learn_press_profile_logout_slug() {
	return apply_filters( 'learn-press/profile-logout-slug', 'logout' );
}