<?php
/**
 * Common functions to manipulate with course, lesson, quiz, questions, etc...
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function lp_get_course( $the_course ) {
	return LP_Course::get_course( $the_course );
}

function lp_get_quiz( $the_quiz ) {
	return LP_Quiz::get_quiz( $the_quiz );
}

function learn_press_get_course( $the_course ) {
	return lp_get_course( $the_course );
}

/**
 * print out class for quiz body
 *
 * @param null $class
 * @return bool
 */
function learn_press_quiz_class( $class = null ) {
	$quiz = LP()->quiz;
	if( !$quiz ){
		return false;
	}
	if( $class && is_string( $class )){
		$class = explode( ' ', $class );
	}elseif( ! $class ){
		$class = array();
	}

	$class[] = "single-quiz";
	if ( learn_press_user_has_completed_quiz() ) {
		$class[] = "completed";
	} elseif ( learn_press_user_has_started_quiz() ) {
		$class[] = "started";
	}

	if( $quiz->has( 'questions' ) ){
		$class[] = 'has-questions';
	}

	$class[] = 'clearfix';

	$class = array_unique( $class );

	post_class( join( ' ', $class ) );
}

/**
 * Get the courses that a item is assigned to
 *
 * @param $item
 * @return mixed
 */
function learn_press_get_item_courses($item){
	global $wpdb;
	$query = $wpdb->prepare("
		SELECT c.*
		FROM {$wpdb->posts} c
			INNER JOIN {$wpdb->learnpress_sections} s ON c.ID = s.section_course_id
			INNER JOIN {$wpdb->learnpress_section_items} si ON si.section_id = s.section_id
			WHERE si.item_id = %d
	", $item);
	return $wpdb->get_results( $query );
}

/**
 * Get the quizzes that a question is assigned to
 *
 * @param $question_id
 * @return mixed
 */
function learn_press_get_question_quizzes( $question_id ){
	global $wpdb;
	$query = $wpdb->prepare("
		SELECT q.*
		FROM {$wpdb->posts} q
		INNER JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON q.ID = qq.quiz_id
		WHERE qq.question_id = %d
	", $question_id );
	return $wpdb->get_results( $query );
}

function learn_press_course_post_type_link( $permalink, $post ) {
	if ( $post->post_type !== 'lp_course' ) {
		return $permalink;
	}

	// Abort early if the placeholder rewrite tag isn't in the generated URL
	if ( false === strpos( $permalink, '%' ) ) {
		return $permalink;
	}

	// Get the custom taxonomy terms in use by this post
	$terms = get_the_terms( $post->ID, 'course_category' );

	if ( ! empty( $terms ) ) {
		usort( $terms, '_usort_terms_by_ID' ); // order by ID

		$category_object = apply_filters( 'learn_press_course_post_type_link_course_category', $terms[0], $terms, $post );
		$category_object = get_term( $category_object, 'course_category' );
		$course_category     = $category_object->slug;

		if ( $parent = $category_object->parent ) {
			$ancestors = get_ancestors( $category_object->term_id, 'course_category' );
			foreach ( $ancestors as $ancestor ) {
				$ancestor_object = get_term( $ancestor, 'course_category' );
				$course_category     = $ancestor_object->slug . '/' . $course_category;
			}
		}
	} else {
		// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
		$course_category = _x( 'uncategorized', 'slug', 'learn_press' );
	}

	$find = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		'%post_id%',
		'%category%',
		'%course_category%'
	);

	$replace = array(
		date_i18n( 'Y', strtotime( $post->post_date ) ),
		date_i18n( 'm', strtotime( $post->post_date ) ),
		date_i18n( 'd', strtotime( $post->post_date ) ),
		date_i18n( 'H', strtotime( $post->post_date ) ),
		date_i18n( 'i', strtotime( $post->post_date ) ),
		date_i18n( 's', strtotime( $post->post_date ) ),
		$post->ID,
		$course_category,
		$course_category
	);

	$permalink = str_replace( $find, $replace, $permalink );

	return $permalink;
}
add_filter( 'post_type_link', 'learn_press_course_post_type_link', 10, 2 );

/**
 * Get the final quiz for a course if it is existing
 *
 * @param $course_id
 *
 * @return mixed
 * @throws Exception
 */
function learn_press_get_final_quiz( $course_id ){
	$course = LP_Course::get_course( $course_id );
	if( ! $course ){
		throw new Exception( sprintf( __( 'The course %d does not exists', 'learn_press' ), $course_id ) );
	}
	$course_items = $course->get_curriculum_items();
	$final = false;
	if( $course_items ){
		$end = end( $course_items );
		if( $end->post_type == LP()->quiz_post_type ){
			$final = $end->ID;
		}
	}
	return apply_filters( 'learn_press_course_final_quiz', $final, $course_id );
}

function learn_press_item_meta_format( $item, $nonce = '' ){
	if( current_theme_supports( 'post-formats' ) ){
		$format = get_post_format( $item );
		if ( false === $format ) {
			$format = 'standard';
		}
		//return false to hide post format
		if( $format = apply_filters( 'learn_press_course_item_format', $format, $item ) ) {
			printf( '<span class="lp-label lp-label-format lp-label-format-%s">%s</span>', $format, ucfirst( $format ) );
		}else{
			echo $nonce;
		}
	}
}

function learn_press_course_item_format_exclude( $format, $item ){
	if( get_post_type( $item ) != LP()->lesson_post_type || ( $format == 'standard' ) ){
		$format = false;
	}
	return $format;
}
add_filter( 'learn_press_course_item_format', 'learn_press_course_item_format_exclude', 5, 2 );
/*******************************************************/
/*******************************************************/

/**
 * Get curriculum of a course
 *
 * @version 1.0
 *
 * @param $course_id
 *
 * @return mixed
 */
function learn_press_get_course_curriculum( $course_id ) {
	$course = LP_Course::get_course( $course_id );
	return $course->get_curriculum();
}

/**
 * Verify course access
 *
 * @param int $course_id
 * @param int $user_id
 *
 * @return boolean
 */
function learn_press_is_enrolled_course( $course_id = null, $user_id = null ) {
	_deprecated_function( __FUNCTION__, '1.0', need_to_updating());
	if ( !$course_id ) {
		$course_id = get_the_ID();
	}
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}


	$course_taken = get_user_meta( $user_id, '_lpr_user_course', true );
	if ( $course_taken ) {
		if ( in_array( $course_id, $course_taken ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Detect if a course is free or not
 *
 * @param null $course_id
 *
 * @return bool
 */
function learn_press_is_free_course( $course_id = null ) {
	if ( !$course_id ) {
		$course_id = get_the_ID();
	}
	return learn_press_get_course( $course_id )->is_free();
}

/**
 * get current status of user's course
 *
 * @author  Tunn
 *
 * @param   int $user_id
 * @param   int $course_id
 *
 * @return  string
 */
function learn_press_get_user_course_status( $user_id = null, $course_id = null ) {
	_deprecated_function( __FUNCTION__, '1.0', need_to_updating());

	$status = null;
	// try to get current user if not passed
	if ( !$user_id ) $user_id = get_current_user_id();

	// try to get course id if not passed
	if ( !$course_id ) {
		global $course;
		$course_id = $course ? $course->id : get_the_ID();
	}

	if ( $course_id && $user_id ) {
		//add_user_meta(  $user_id, '_lpr_order_id', 40 );
		$orders = get_user_meta( $user_id, '_lpr_order_id' );
		$orders = array_unique( $orders );
		if ( $orders ) {
			$order_id = 0;
			foreach ( $orders as $order ) {
				$order_items = get_post_meta( $order, '_learn_press_order_items', true );
				if ( $order_items && $order_items->products ) {
					if ( !empty( $order_items->products[$course_id] ) ) {
						$order_id = max( $order_id, $order );
					}
				}
			}

			if ( ( $order = get_post( $order_id ) ) && $order->post_status != 'lpr-draft' )
				$status = get_post_meta( $order_id, '_learn_press_transaction_status', true );
		}
	}
	return $status;
}


/**
 * Check to see if user can view a lesson or not
 *
 * @since 0.9.5
 *
 * @param int $lesson_id
 * @param int $course_id
 * @param int $user_id
 *
 * @return boolean
 */
function learn_press_user_can_view_lesson( $lesson_id, $course_id = 0, $user_id = null ) {
	if( $user_id ){
		$user = learn_press_get_user( $user_id );
	}else{
		$user = LP()->user;
	}
	return $user ? $user->can( 'view-lesson', $lesson_id, $course_id ) : false;
}

/**
 * Check to see if user can view a quiz or not
 *
 * @param int $quiz_id
 * @param int $course_id
 * @param int $user_id
 *
 * @return boolean
 */
function learn_press_user_can_view_quiz( $quiz_id = null, $course_id = 0, $user_id = null ) {
	if( $user_id ){
		$user = learn_press_get_user( $user_id );
	}else{
		$user = LP()->user;
	}
	return $user ? $user->can( 'view-quiz', $quiz_id, $course_id ) : false;
}

/**
 * Get course setting is enroll required or public
 *
 * @since 0.9.5
 *
 * @param int $course_id
 *
 * @return boolean
 */
function learn_press_course_enroll_required( $course_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );

	$required = ( 'yes' == get_post_meta( $course_id, '_lpr_course_enrolled_require', true ) );
	return apply_filters( 'learn_press_course_enroll_required', $required, $course_id );
}

/**
 * Checks to see that an user has finished a lesson or not yet
 * Function return the ID of a course if the user has completed a lesson
 * Otherwise, return false
 *
 * @author TuNguyen
 *
 * @param null $lesson_id
 * @param null $user_id
 *
 * @return mixed
 */
function learn_press_user_has_completed_lesson( $lesson_id = null, $user_id = null ) {
	_deprecated_function( __FUNCTION__, '1.0', need_to_updating());

	$lesson_id = learn_press_get_lesson_id( $lesson_id );
	if ( !$user_id ) $user_id = get_current_user_id();

	$completed_lessons = get_user_meta( $user_id, '_lpr_lesson_completed', true );

	if ( !$completed_lessons ) return false;
	foreach ( $completed_lessons as $courses ) {
		if ( is_array( $courses ) && in_array( $lesson_id, $courses ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Short function to check if a lesson id is not passed to a function
 * then try to get it from $_REQUEST
 *
 * @param null $lesson_id
 *
 * @return int|null
 */
function learn_press_get_lesson_id( $lesson_id = null ) {
	if ( !$lesson_id ) {
		$lesson_id = !empty( $_REQUEST['lesson'] ) ? $_REQUEST['lesson'] : 0;
	}
	return $lesson_id;
}

/////////////////////////
function need_to_updating(){
	ob_start();
	learn_press_display_message( 'This function need to updating' );
	return ob_get_clean();
}
