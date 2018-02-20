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

/**
 * @param $the_course
 *
 * @return LP_Course|mixed
 */
function learn_press_get_course( $the_course = false ) {
	return $the_course ? LP_Course::get_course( $the_course ) : LP()->global['course'];
}

function learn_press_get_quiz( $the_quiz ) {
	return LP_Quiz::get_quiz( $the_quiz );
}

/**
 * print out class for quiz body
 *
 * @param null $class
 *
 * @return bool
 */
function learn_press_quiz_class( $class = null ) {
	$item = LP()->global['course-item'];
	$user = LP()->user;

	if ( !$item ) {
		return false;
	}

	$quiz = LP_Quiz::get_quiz( $item->ID );

	if ( $class && is_string( $class ) ) {
		$class = explode( ' ', $class );
	} elseif ( !$class ) {
		$class = array();
	}

	$class[] = "single-quiz";

	if ( $status = $user->get_quiz_status( $quiz->id ) ) {
		$class[] = 'quiz-' . $status;
	}

	if ( $quiz->has( 'questions' ) ) {
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
 *
 * @return mixed
 */
function learn_press_get_item_courses( $item ) {
	global $wpdb;
	$query = $wpdb->prepare( "
		SELECT c.*
		FROM {$wpdb->posts} c
			INNER JOIN {$wpdb->learnpress_sections} s ON c.ID = s.section_course_id
			INNER JOIN {$wpdb->learnpress_section_items} si ON si.section_id = s.section_id
			WHERE si.item_id = %d
	", $item );
	return $wpdb->get_results( $query );
}

/**
 * Get the quizzes that a question is assigned to
 *
 * @param $question_id
 *
 * @return mixed
 */
function learn_press_get_question_quizzes( $question_id ) {
	global $wpdb;
	$query = $wpdb->prepare( "
		SELECT q.*
		FROM {$wpdb->posts} q
		INNER JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON q.ID = qq.quiz_id
		WHERE qq.question_id = %d
	", $question_id );
	return $wpdb->get_results( $query );
}

function _learn_press_usort_terms_by_ID( $terms ) {
	$version = get_bloginfo( 'version' );
	if ( version_compare( $version, '4.7', '>=' ) ) {
		$terms = wp_list_sort( $terms, 'term_id' );
	} else {
		usort( $terms, '_usort_terms_by_ID' );
	}
	return $terms;
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

	if ( !empty( $terms ) ) {
		$terms           = _learn_press_usort_terms_by_ID( $terms ); // order by ID
		$category_object = apply_filters( 'learn_press_course_post_type_link_course_category', $terms[0], $terms, $post );
		$category_object = get_term( $category_object, 'course_category' );
		$course_category = $category_object->slug;

		if ( $parent = $category_object->parent ) {
			$ancestors = get_ancestors( $category_object->term_id, 'course_category' );
			foreach ( $ancestors as $ancestor ) {
				$ancestor_object = get_term( $ancestor, 'course_category' );
				$course_category = $ancestor_object->slug . '/' . $course_category;
			}
		}
	} else {
		// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
		$course_category = _x( 'uncategorized', 'slug', 'learnpress' );
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
function learn_press_get_final_quiz( $course_id ) {
	$course = LP_Course::get_course( $course_id );
	if ( !$course ) {
		throw new Exception( sprintf( __( 'The course %d does not exists', 'learnpress' ), $course_id ) );
	}
	$course_items = $course->get_curriculum_items();
	$final        = false;
	if ( $course_items ) {
		$end = end( $course_items );
		if ( $end->post_type == LP_QUIZ_CPT ) {
			$final = $end->ID;
		}
	}
	return apply_filters( 'learn_press_course_final_quiz', $final, $course_id );
}

function learn_press_item_meta_format( $item, $nonce = '' ) {
	if ( current_theme_supports( 'post-formats' ) ) {
		$format = get_post_format( $item );
		if ( false === $format ) {
			$format = 'standard';
		}

		//return false to hide post format
		if ( $format = apply_filters( 'learn_press_course_item_format', $format, $item ) ) {
			//printf( '<span class="lp-label lp-label-format lp-label-format-%s">%s</span>', $format, ucfirst( $format ) );
			printf( '<label for="post-format-0" class="post-format-icon post-format-%s" title="%s"></label>', $format, ucfirst( $format ) );
		} else {
			echo $nonce;
		}
	}
}

function learn_press_course_item_format_exclude( $format, $item ) {
	if ( get_post_type( $item ) != LP_LESSON_CPT || ( $format == 'standard' ) ) {
		$format = false;
	}
	return $format;
}

//add_filter( 'learn_press_course_item_format', 'learn_press_course_item_format_exclude', 5, 2 );
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
	//_deprecated_function( __FUNCTION__, '1.0', 'LP_User -> has_enrolled_course');
	if ( $course = LP_Course::get_course( $course_id ) && $user = learn_press_get_user( $user_id ) ) {
		return $user->has_enrolled_course( $course_id );
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
	//_deprecated_function( __FUNCTION__, '1.0', 'LP_User() -> get_course_status');
	if ( $course = LP_Course::get_course( $course_id ) && $user = learn_press_get_user( $user_id ) ) {
		return $user->get_course_status( $course_id );
	}
	return false;
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
	if ( $user_id ) {
		$user = learn_press_get_user( $user_id );
	} else {
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
	if ( $user_id ) {
		$user = learn_press_get_user( $user_id );
	} else {
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

	_deprecated_function( __FUNCTION__, '1.0', 'LP_User() -> has_completed_quiz' );
	if ( $user = learn_press_get_user( $user_id ) ) {
		return $user->has_completed_quiz( $lesson_id );
	}
	return false;

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

function learn_press_get_all_courses( $args = array() ) {
	$term    = '';
	$exclude = '';
	is_array( $args ) && extract( $args );
	$args  = array(
		'post_type'      => array( 'lp_course' ),
		'post_status'    => 'publish',
		'posts_per_page' => - 1,
		's'              => $term,
		'fields'         => 'ids',
		'exclude'        => $exclude
	);
	$args  = apply_filters( 'learn_press_get_courses_args', $args );
	$posts = get_posts( $args );
	return apply_filters( 'learn_press_get_courses', $posts, $args );
}

function learn_press_search_post_excerpt( $where = '' ) {
	global $wp_the_query, $wpdb;

	if ( empty( $wp_the_query->query_vars['s'] ) )
		return $where;

	$where = preg_replace(
		"/post_title\s+LIKE\s*(\'\%[^\%]+\%\')/",
		"post_title LIKE $1) OR ({$wpdb->posts}.post_excerpt LIKE $1", $where );

	return $where;
}

//add_filter( 'posts_where', 'learn_press_search_post_excerpt' );

/**
 * Return true if a course is required review before submit
 *
 * @param null $course_id
 * @param null $user_id
 *
 * @return bool
 */
function learn_press_course_is_required_review( $course_id = null, $user_id = null ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	if ( !$course_id ) {
		global $post;
		$course_id = $post->ID;
	}
	if ( get_post_type( $course_id ) != 'lp_course' ) {
		return false;
	}

	$user = learn_press_get_user( $user_id );
	if ( $user->is_admin() || ( ( $user_course = learn_press_get_user( get_post_field( 'post_author', $course_id ) ) ) && $user_course->is_admin() ) ) {
		return false;
	}

	$required_review       = LP()->settings->get( 'required_review' ) == 'yes';
	$enable_edit_published = LP()->settings->get( 'enable_edit_published' ) == 'yes';
	$is_publish            = get_post_status( $course_id ) == 'publish';

	return !( ( !$required_review ) || ( $required_review && $enable_edit_published && $is_publish ) );
}

function learn_press_get_course_user( $course_id = null ) {
	if ( !$course_id ) {
		$course_id = get_the_ID();
	}
	return learn_press_get_user( get_post_field( 'post_author', $course_id ) );
}

/**
 * Get item types support in course curriculum
 *
 * @return mixed|null|void
 */
function learn_press_course_get_support_item_types() {
	$types = array();
	if ( !empty( $GLOBALS['learn_press_course_support_item_types'] ) ) {
		$types = $GLOBALS['learn_press_course_support_item_types'];
	}
	return $types;
}

function learn_press_course_add_support_item_type() {
	if ( empty( $GLOBALS['learn_press_course_support_item_types'] ) ) {
		$GLOBALS['learn_press_course_support_item_types'] = array();
	}
	if ( func_num_args() == 1 && is_array( func_get_arg( 0 ) ) ) {
		foreach ( func_get_arg( 0 ) as $type => $label ) {
			learn_press_course_add_support_item_type( $type, $label );
		}
	} else if ( func_num_args() == 2 ) {
		$GLOBALS['learn_press_course_support_item_types'][func_get_arg( 0 )] = func_get_arg( 1 );
	}
}

learn_press_course_add_support_item_type(
	array(
		'lp_lesson' => __( 'Lesson', 'learnpress' ),
		'lp_quiz'   => __( 'Quiz', 'learnpress' )
	)
);

function learn_press_get_course_id() {
	$course_id = false;
	if ( learn_press_is_course() ) {
		$course_id = get_the_ID();
	}
	return $course_id;
}

function learn_press_course_item() {

}

function learn_press_get_the_course() {
	static $course;
	if ( !$course ) {
		$course_id = get_the_ID();
		if ( get_post_type( $course ) == LP_COURSE_CPT ) {
			$course = LP_Course::get_course( $course_id );
		}
	}
	if ( !$course ) {
		return new LP_Course( 0 );
	}
	return $course;
}

function learn_press_get_user_question_answer( $args = '' ) {
	$args     = wp_parse_args(
		$args,
		array(
			'question_id' => 0,
			'history_id'  => 0,
			'quiz_id'     => 0,
			'course_id'   => 0,
			'user_id'     => get_current_user_id()
		)
	);
	$answered = null;
	if ( $args['history_id'] ) {
		$user_meta = learn_press_get_user_item_meta( $args['history_id'], 'question_answers', true );
		if ( $user_meta && array_key_exists( $args['question_id'], $user_meta ) ) {
			$answered = $user_meta[$args['question_id']];
		}
	} elseif ( $args['quiz_id'] && $args['course_id'] ) {
		$user    = learn_press_get_user( $args['user_id'] );
		$history = $user->get_quiz_results( $args['quiz_id'], $args['course_id'] );
		if ( $history ) {
			$user_meta = learn_press_get_user_item_meta( $history->history_id, 'question_answers', true );
			if ( $user_meta && array_key_exists( $args['question_id'], $user_meta ) ) {
				$answered = $user_meta[$args['question_id']];
			}
		}
	}
	return $answered;
}

require_once LP_PLUGIN_PATH . "/inc/lp-init.php";

function need_to_updating() {
	ob_start();
	learn_press_display_message( 'This function need to updating' );
	return ob_get_clean();
}

/* filter section item single course */
function learn_press_get_course_sections() {
	return apply_filters( 'learn_press_get_course_sections', array(
		'lp_lesson',
		'lp_quiz'
	) );
}
function lean_press_get_course_sections() {
	_deprecated_function( __FUNCTION__, '2.1', 'learn_press_get_course_sections' );
	return learn_press_get_course_sections();
}

if ( !function_exists( 'learn_press_get_course_item_url' ) ) {
	function learn_press_get_course_item_url( $course_id = null, $item_id = null ) {
		$course = learn_press_get_course( $course_id );
		return $course->get_item_link( $item_id );
	}
}

if ( !function_exists( 'learn_press_get_sample_link_course_item_url' ) ) {

	function learn_press_get_sample_link_course_item_url( $item_id = null ) {

		if ( !$item_id ) {
			return;
		}


		$permalink = get_the_permalink( $item_id );
		$post_name = get_post_field( 'post_name', $item_id );

		if ( '' != get_option( 'permalink_structure' ) ) {
			$permalink = $post_name;
		} else {
			$key       = preg_replace( '!lp_!', '', get_post_type( $item_id ) );
			$permalink = add_query_arg( array( $key => $post_name ), $permalink );
		}
		return $permalink ;

	}
}

if ( !function_exists( 'learn_press_get_nav_course_item_url' ) ) {
	function learn_press_get_nav_course_item_url( $course_id = null, $item_id = null, $content_only = false ) {
		$course           = learn_press_get_course( $course_id );
		$curriculum_items = maybe_unserialize( $course->post->curriculum_items );
		$index            = array_search( $item_id, $curriculum_items );
		$return           = array( 'back' => '', 'next' => '' );
		if ( is_array( $curriculum_items ) ) {
			if ( array_key_exists( $index - 1, $curriculum_items ) ) {
				$back_item      = get_post( $curriculum_items[$index - 1] );
				$return['back'] = array(
					'id'    => $back_item->ID,
					'link'  => $course->get_item_link( $curriculum_items[$index - 1] ),
					'title' => $back_item->post_title,
				);
				if ( $content_only ) {
					$return['back']['link'] .= '?content-item-only=yes';
				}
			}
			if ( array_key_exists( $index + 1, $curriculum_items ) ) {
				$next_item      = get_post( $curriculum_items[$index + 1] );
				$return['next'] = array(
					'id'    => $next_item->ID,
					'link'  => $course->get_item_link( $curriculum_items[$index + 1] ),
					'title' => $next_item->post_title,
				);
				if ( $content_only ) {
					$return['next']['link'] .= '?content-item-only=yes';
				}
			}
		}
		return $return;
	}
}

if ( !function_exists( 'learn_press_course_nav_items' ) ) {
	/**
	 * Displaying course items navigation
	 *
	 * @param null $item_id
	 * @param null $course_id
	 */
	function learn_press_course_nav_items( $item_id = null, $course_id = null ) {
		learn_press_get_template( 'single-course/nav-items.php', array( 'course_id' => $course_id, 'item_id' => $item_id, 'content_only' => learn_press_is_content_item_only() ) );
	}
}
/**
 * Update url lesson & quiz in admin page
 */

if ( !function_exists( 'learn_press_get_item_course_id' ) ) {

	function learn_press_get_item_course_id( $post_id, $post_type ) {

		global $wpdb;

		/**
		 * Get Course id by Quiz post_id
		 *
		 * @global type $wpdb
		 *
		 * @param type  $post_id
		 *
		 * @return $course_id
		 */
		if ( $post_type == LP_QUIZ_CPT ) {
			$query = $wpdb->prepare( "SELECT section.section_course_id FROM {$wpdb->learnpress_sections} AS section"
				. " INNER JOIN {$wpdb->learnpress_section_items} AS item ON item.section_id = section.section_id"
				. " INNER JOIN {$wpdb->posts} AS course ON course.ID = section.section_course_id"
				. " WHERE course.post_type = %s"
				. " AND course.post_status = %s"
				. " AND item.item_type = %s"
				. " AND item.item_id = %d"
				. " LIMIT 1", LP_COURSE_CPT, 'publish', LP_QUIZ_CPT, $post_id );

			return apply_filters( 'learn_press_get_quiz_course_id', absint( $wpdb->get_var( $query ) ), $post_id );
		}

		/**
		 * Get Course id by Lesson post_id
		 *
		 * @global type $wpdb
		 *
		 * @param type  $post_id
		 *
		 * @return $course_id
		 */

		if ( $post_type == LP_LESSON_CPT ) {

			$query = $wpdb->prepare( "SELECT section.section_course_id FROM {$wpdb->learnpress_sections} AS section"
				. " INNER JOIN {$wpdb->learnpress_section_items} AS item ON item.section_id = section.section_id"
				. " INNER JOIN {$wpdb->posts} AS course ON course.ID = section.section_course_id"
				. " WHERE course.post_type = %s"
				. " AND course.post_status = %s"
				. " AND item.item_type = %s"
				. " AND item.item_id = %d"
				. " LIMIT 1", LP_COURSE_CPT, 'publish', LP_LESSON_CPT, $post_id );

			return apply_filters( 'learn_press_get_lesson_course_id', absint( $wpdb->get_var( $query ) ), $post_id );

		}

	}
}

add_filter( 'get_sample_permalink', 'learn_press_item_sample_permalink', 10, 5 );

if ( !function_exists( 'learn_press_item_sample_permalink' ) ) {

	function learn_press_item_sample_permalink( $permalink, $post_id, $title, $name, $post ) {

		if ( $post->post_type !== LP_QUIZ_CPT && $post->post_type !== LP_LESSON_CPT ) {
			return $permalink;
		}

//	    $permalink  = str_replace( $post->post_name, '%pagename%' , $permalink );
		$permalink[0] = str_replace( $post->post_name, '%pagename%', $permalink[0] );

		return $permalink;
	}

}

add_filter( 'post_type_link', 'learn_press_quiz_permalink', 10, 2 );

if ( !function_exists( 'learn_press_quiz_permalink' ) ) {

	function learn_press_quiz_permalink( $permalink, $post ) {

		if ( !empty( LP()->global['item_permalinks'][$post->ID] ) ) {
			return LP()->global['item_permalinks'][$post->ID];
		}
		remove_filter( 'post_type_link', 'learn_press_quiz_permalink', 10 );

		if ( $post->post_type !== LP_QUIZ_CPT && $post->post_type !== LP_LESSON_CPT ) {
			return $permalink;
		}

		$course_id = learn_press_get_item_course_id( $post->ID, $post->post_type );
		if ( $course_id ) {
			$permalink = learn_press_get_course_item_url( $course_id, $post->ID );
		} else {

			$permalink = learn_press_get_sample_link_course_item_url( $post->ID );
		}
		LP()->global['item_permalinks'][$post->ID] = $permalink;
		add_filter( 'post_type_link', 'learn_press_quiz_permalink', 10, 2 );
		return $permalink;
	}
}

add_filter( 'template_include', 'learn_press_prepare_archive_courses' );
function learn_press_prepare_archive_courses( $template ) {
	global $wp_query;
	$query = !empty( LP()->wp_query ) ? LP()->wp_query : $wp_query;
	if ( !$query || !$query->is_main_query() ) {
		return $template;
	}
	if ( !empty( $query->posts ) ) {
		$ids = array();
		foreach ( $query->posts as $_post ) {
			$ids[] = $_post->ID;
			$_post = sanitize_post( $_post, 'raw' );
			wp_cache_add( $_post->ID, $_post, 'posts' );
		}
		_learn_press_get_courses_curriculum( $ids, false, false );
		_learn_press_count_users_enrolled_courses( $ids );
	}
	return $template;
}

function learn_press_course_grade_html( $grade, $echo = true ) {
	$html = '';
	switch ( $grade ) {
		case 'passed':
			$html = __( 'Passed', 'learnpress' );
			break;
		case 'failed':
			$html = __( 'Failed', 'learnpress' );
			break;
		case 'in-progress':
			$html = __( 'In Progress', 'learnpress' );
			break;
	}
	$html = apply_filters( 'learn_press_course_grade_html', $html, $grade );
	if ( $echo ) echo $html;
	return $html;
}

function learn_press_get_course_results_tooltip( $course_id ) {
	$metabox = LP_Course_Post_Type::assessment_meta_box();
	$options = $metabox['fields'][0]['options'];
	$cr      = get_post_meta( $course_id, '_lp_course_result', true );
	$tooltip = !empty( $options[$cr] ) ? $options[$cr] : false;
	if ( $tooltip ) {
		if ( preg_match_all( '~<p.*>(.*)<\/p>~im', $tooltip, $matches ) ) {
			$tooltip = $matches[1][0];
		}
	}
	return $tooltip;
}