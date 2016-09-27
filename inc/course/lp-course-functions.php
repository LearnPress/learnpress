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
	return $the_course ? LP_Course::get_course( $the_course ) : LP()->course;
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
		usort( $terms, '_usort_terms_by_ID' ); // order by ID

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
		$user_meta = learn_press_get_user_item_meta( $args['history_id'], '_quiz_question_answers', true );
		if ( $user_meta && array_key_exists( $args['question_id'], $user_meta ) ) {
			$answered = $user_meta[$args['question_id']];
		}
	} elseif ( $args['quiz_id'] && $args['course_id'] ) {
		$user    = learn_press_get_user( $args['user_id'] );
		$history = $user->get_quiz_results( $args['quiz_id'], $args['course_id'] );
		if ( $history ) {
			$user_meta = learn_press_get_user_item_meta( $history->history_id, '_quiz_question_answers', true );
			if ( $user_meta && array_key_exists( $args['question_id'], $user_meta ) ) {
				$answered = $user_meta[$args['question_id']];
			}
		}
	}
	return $answered;
}

//function learn_press_get_course_
/*
$course = LP()->global['course'];
$user   = learn_press_get_current_user();
$item   = isset( $item ) ? $item : LP()->global['course-item'];
$force  = isset( $force ) ? $force : false;
if ( !$item ) {
	return;
}
$quiz = LP_Quiz::get_quiz( $item->ID );
/*if ( $user->has( 'quiz-status', 'started', $item->id, $course->id ) ) {
	$question          = $quiz->get_current_question();
	$item_quiz_title   = $question->get_title();
	$item_quiz_content = $question->get_content();
} else
{
	$item_quiz_title   = $item->title;
	$item_quiz_content = $item->content;
}*/

function learn_press_setup_course_data( $the_course ) {
	$course = false;
	$post   = false;
	if ( is_numeric( $the_course ) ) {
		$post = get_post( $the_course );
	} elseif ( $the_course instanceof LP_Abstract_Course ) {
		$post = $the_course->post;
	} elseif ( isset( $the_course->ID ) ) {
		$post = $the_course;
	} elseif ( is_string( $the_course ) ) {
		$post = learn_press_get_post_by_name( $the_course );
	}

	if ( !$post || $post->post_type != LP_COURSE_CPT ) {
		return $course;
	}

	_learn_press_get_course_curriculum( $post->ID );
	do_action( 'learn_press_setup_course_data_' . $post->ID );
	learn_press_setup_user_course_data( get_current_user_id(), $post->ID );
}

function _learn_press_get_course_curriculum( $course_id, $force = false ) {
	$curriculum = LP_Cache::get_course_curriculum( false, array() );

	if ( !array_key_exists( $course_id, $curriculum ) || $force ) {
		global $wpdb;
		$query       = $wpdb->prepare( "
			SELECT s.*, si.*, p.*
			FROM {$wpdb->prefix}posts p
			INNER JOIN {$wpdb->prefix}learnpress_section_items si ON si.item_id = p.ID
			INNER JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id
			WHERE s.section_id IN(
				SELECT cc.section_id
					FROM {$wpdb->prefix}posts p
					INNER JOIN {$wpdb->prefix}learnpress_sections cc ON p.ID = cc.section_course_id
					WHERE p.ID = %d
					ORDER BY `section_order` ASC
			 )
			ORDER BY s.section_order, si.item_order ASC
		", $course_id );
		$_curriculum = array();
		$rows        = $wpdb->get_results( $query );
		if ( !$rows ) {
			return false;
		}
		if ( !function_exists( 'get_default_post_to_edit' ) ) {
			include_once ABSPATH . '/wp-admin/includes/post.php';
		}
		$empty_post = (array) get_default_post_to_edit();
		$section_id = 0;
		$item_ids   = array();
		$quiz_ids   = array();
		$lesson_ids = array();
		foreach ( $rows as $row ) {
			if ( $row->section_id != $section_id ) {
				$section_id = $row->section_id;
				$section    = new stdClass();
				foreach ( array( 'section_id', 'section_name', 'section_course_id', 'section_order', 'section_description' ) as $prop ) {
					$section->{$prop} = $row->{$prop};
				}
				$section->items           = array();
				$_curriculum[$section_id] = $section;
			}
			$item = new stdClass();
			foreach ( array( 'section_item_id', 'section_id', 'item_id', 'item_order', 'item_type' ) as $prop ) {
				$item->{$prop} = $row->{$prop};
			}

			//$item                             = new stdClass();
			foreach ( $empty_post as $prop => $value ) {
				if ( property_exists( $row, $prop ) ) {
					$item->{$prop} = $row->{$prop};
				}
			}
			$_curriculum[$section_id]->items[] = $item;
			$item_ids[]                        = $item->ID;
			if ( $item->post_type == LP_QUIZ_CPT ) {
				$quiz_ids[] = $item->ID;
			} elseif ( $item->post_type == LP_LESSON_CPT ) {
				$lesson_ids[] = $item->ID;
			}
			//wp_cache_delete( $item->ID, 'posts' );
			wp_cache_add( $item->ID, $item, 'posts' );
		}
		update_meta_cache( 'post', $item_ids );
		$course                   = get_post( $course_id );
		$course->curriculum_items = $item_ids;
		wp_cache_replace( $course_id, $course, 'posts' );

		if ( $quiz_ids ) {
			echo 'xxxxxxxxxxxxxxxx';
			echo $query = $wpdb->prepare( "
				SELECT qq.quiz_id, p.*, IF(pm.meta_value, pm.meta_value, 1) as mark
				FROM wp_learnpress_quiz_questions qq
				INNER JOIN wp_posts p ON p.ID = qq.question_id AND p.post_type = 'lp_question'
				INNER JOIN wp_posts q ON q.ID = qq.quiz_id AND q.post_type = 'lp_quiz'
				LEFT JOIN wp_postmeta pm ON pm.post_id = qq.question_id AND pm.meta_key = '_lp_mark'
				WHERE %d AND qq.quiz_id IN (" . join( ',', $quiz_ids ) . ")
				ORDER BY quiz_id, qq.question_order ASC
			", 1 );
			if ( $questions = $wpdb->get_results( $query, OBJECT_K ) ) {
				/*foreach($questions as $question){
					wp_cache_add( $question->ID, $question, 'posts' );
				}*/
				$question_ids = array_keys( $questions );

				$format_ids   = array_fill( 0, sizeof( $question_ids ), '%d' );
				$prepare_args = array_merge( array( '_lp_type', 'lp_question' ), $question_ids );
				echo $query = $wpdb->prepare( "
					SELECT qa.question_answer_id, ID as id, pm.meta_value as type, qa.answer_data as answer_data, answer_order
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
					INNER JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON qq.question_id = p.ID
					RIGHT JOIN {$wpdb->prefix}learnpress_question_answers qa ON qa.question_id = p.ID
					ORDER BY id, qq.question_order DESC, answer_order DESC
				", $prepare_args );
				$answers = $wpdb->get_results( $query );

				/*$answers = $this->_get_question_answers( $question_ids );
				foreach ( $questions as $id => $question ) {
					$answer_data = array( 'type' => 'true_or_false' );
					// Fetch answers for questions if exists
					if ( $answers ) {
						$question->answers = array();
						for ( $n = sizeof( $answers ), $i = $n - 1; $i >= 0; $i -- ) {
							if ( $answers[$i]->id != $question->ID ) {
								break;
							}
							$answers[$i]->answer_data                            = maybe_unserialize( $answers[$i]->answer_data );
							$answer_data                                         = array_merge( $answer_data, $answers[$i]->answer_data );
							$answer_data['id']                                   = $answers[$i]->question_answer_id;
							$answer_data['order']                                = $answers[$i]->answer_order;
							$answer_data['type']                                 = $answers[$i]->type;
							$question->answers[$answers[$i]->question_answer_id] = $answer_data;
							unset( $answers[$i] );
						}
					}
					$question->type = $answer_data['type'];
					// Add item to 'posts' cache group
					$item_post = wp_cache_get( $question->ID, 'posts' );
					if ( $item_post ) {
						wp_cache_delete( $question->ID, 'posts' );
					}
					$add = wp_cache_add( $question->ID, $question, 'posts' );

					// update mark of quiz
					$this->_mark += absint( $question->mark );
				}*/
			}
		}

		$curriculum[$course_id] = $_curriculum;
		LP_Cache::set_course_curriculum( $curriculum );
	} else {
		$_curriculum = $curriculum[$course_id];
	}
	return $_curriculum;
}

function learn_press_setup_user_course_data( $user_id, $course_id ) {
	if ( !did_action( 'learn_press_setup_course_data_' . $course_id ) ) {
		learn_press_setup_course_data( $course_id );
	}
	global $wpdb;
	$course   = get_post( $course_id );
	$item_ids = !empty( $course->curriculum_items ) ? $course->curriculum_items : array();
	if ( !$item_ids ) {
		return;
	}
	$in    = implode( ', ', $item_ids );
	$query = $wpdb->prepare( "
		SELECT * FROM {$wpdb->prefix}learnpress_user_items t1
		WHERE user_id = %d
		AND item_id = %d
		UNION
		(
			SELECT * FROM {$wpdb->prefix}learnpress_user_items t2
			WHERE user_id = %d AND ref_id = %d
			AND user_item_id = (
				SELECT MAX(user_item_id) FROM {$wpdb->prefix}learnpress_user_items
				WHERE user_id = %d and ref_id = %d
				AND item_id = t2.item_id
			)
			AND item_id IN({$in})
		)
		ORDER BY user_item_id ASC
	", $user_id, $course_id, $user_id, $course_id, $user_id, $course_id );
	$items = $wpdb->get_results( $query );
	if ( !$items ) {
		return;
	}
	$item_statuses = LP_Cache::get_item_statuses( false, array() );
	foreach ( $item_ids as $id ) {
		if ( !array_key_exists( $id, $item_statuses ) ) {
			$item_statuses[$user_id . '-' . $course_id . '-' . $id] = '';
		}
	}
	foreach ( $items as $item ) {
		$item_statuses[$user_id . '-' . $course_id . '-' . $item->item_id] = $item->status;
	}
	LP_Cache::set_item_statuses( $item_statuses );
}

/////////////////////////
function need_to_updating() {
	ob_start();
	learn_press_display_message( 'This function need to updating' );
	return ob_get_clean();
}

/* filter section item single course */
function lean_press_get_course_sections() {
	return apply_filters( 'lean_press_get_course_sections', array(
		'lp_lesson',
		'lp_quiz'
	) );
}

if ( !function_exists( 'learn_press_get_course_item_url' ) ) {
    function learn_press_get_course_item_url( $course_id = null, $item_id = null ) {
        $course = learn_press_get_course( $course_id );
        return $course->get_item_link( $item_id );
    }
}