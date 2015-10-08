<?php
/**
 * LearnPress Course Functions
 *
 * @file
 *
 * Common functions to manipulate with course, lesson, quiz, questions, etc...
 * Author foobla
 * Created Mar 18 2015
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

/**
 * Get number of lesson in one course
 *
 * @param $course_id
 *
 * @return int
 */
function lpr_get_number_lesson( $course_id ) {
	$number_lesson     = 0;
	$course_curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
	if ( $course_curriculum ) {
		foreach ( $course_curriculum as $section ) {
			$number_lesson += sizeof( $section['lesson_quiz'] );
		}
	}
	return $number_lesson;
}

/**
 * Get final quiz for the course using final quiz assessment
 * [Modified by TuNguyen on May 18 2015]
 *
 * @param  int $course_id
 *
 * @return int
 */
function lpr_get_final_quiz( $course_id ) {
	$final = false;
	if ( get_post_meta( $course_id, '_lpr_course_final', true ) == 'yes' ) {
		$course_curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
		if ( $course_curriculum ) {
			$last_section = end( $course_curriculum );
			if ( $last_section && !empty( $last_section['lesson_quiz'] ) && $lesson_quiz = $last_section['lesson_quiz'] ) {
				$final = end( $lesson_quiz );
				if ( 'lpr_quiz' != get_post_type( $final ) ) {
					$final = false;
				}
			}
		}
	}
	return $final;
}

/**
 * Calculate the progress of a student in a course
 * [Modified by TuNguyen on May 18 2015]
 *
 * @param $course_id
 *
 * @return float|int
 */
function lpr_course_evaluation( $course_id ) {
	$user_id          = get_current_user_id();
	$lesson_completed = get_user_meta( $user_id, '_lpr_lesson_completed', true );
	$all_lessons      = learn_press_get_lessons_in_course( $course_id );
	$number_lesson    = sizeof( $all_lessons );

	if ( $lesson_completed && !empty( $lesson_completed[$course_id] ) && $number_lesson != 0 ) {
		$not_complete  = array_diff( $all_lessons, $lesson_completed[$course_id] );
		$course_result = ( $number_lesson - sizeof( $not_complete ) ) / $number_lesson;
	} else {
		$course_result = 0;
	}
	$return = $course_result * 100;
	return apply_filters( 'lpr_course_evaluation', $return, $course_id, $user_id );
}

/**
 * Get the results of a quiz
 *
 * @param      $quiz_id
 * @param null $user_id
 *
 * @return mixed
 */
function learn_press_quiz_evaluation( $quiz_id, $user_id = null ) {
	if ( !$user_id ) $user_id = get_current_user_id();
	$result = learn_press_get_quiz_result( $user_id, $quiz_id );

	$return = $result['mark_percent'] * 100;
	// @since 0.9.6
	return apply_filters( 'learn_press_quiz_evaluation', $return, $quiz_id, $user_id );
}

/**
 *
 * @param $course_id
 *
 * @return float|int
 */
function lpr_course_auto_evaluation( $course_id ) {
	$result            = - 1;
	$current           = current_time( 'timestamp' );
	$user_id           = get_current_user_id();
	$start_date_course = get_user_meta( $user_id, '_lpr_user_course_start_time', true );
	$start_date        = $start_date_course[$course_id];
	$course_duration   = get_post_meta( $course_id, '_lpr_course_duration', true );
	if ( ( $current - $start_date ) / ( 7 * 24 * 3600 ) > $course_duration ) {
		$result = lpr_course_evaluation( $course_id );
	}
	return $result;
}

/**
 * Check to see if user can preview the lesson
 *
 * @param $lesson_id
 *
 * @return bool
 */
function learn_press_is_lesson_preview( $lesson_id ) {
	$lesson_preview = get_post_meta( $lesson_id, '_lpr_lesson_preview', true );
	return $lesson_preview == 'preview';
}

/**
 * Returns the name of folder contains template files in theme
 */
function learn_press_template_path() {
	return apply_filters( 'learn_press_template_path', 'learnpress' );
}

/**
 * Prevent user access directly by calling the file from URL
 *
 * @author  TuNN
 */
function learn_press_prevent_access_directly() {
	if ( !defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}
}

/**
 * get template part
 *
 * @param   string $slug
 * @param   string $name
 *
 * @return  string
 */
function learn_press_get_template_part( $slug, $name = '' ) {
	$template = '';

	// Look in yourtheme/slug-name.php and yourtheme/learnpress/slug-name.php
	if ( $name ) {
		$template = locate_template( array( "{$slug}-{$name}.php", learn_press_template_path() . "/{$slug}-{$name}.php" ) );
	}

	// Get default slug-name.php
	if ( !$template && $name && file_exists( LPR_PLUGIN_PATH . "/templates/{$slug}-{$name}.php" ) ) {
		$template = LPR_PLUGIN_PATH . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/learnpress/slug.php
	if ( !$template ) {
		$template = locate_template( array( "{$slug}.php", learn_press_template_path() . "{$slug}.php" ) );
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

	$located = learn_press_locate_template( $template_name, $template_path, $default_path );

	if ( !file_exists( $located ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
		return;
	}
	// Allow 3rd party plugin filter template file from their plugin
	$located = apply_filters( 'learn_press_get_template', $located, $template_name, $args, $template_path, $default_path );

	do_action( 'learn_press_before_template_part', $template_name, $template_path, $located, $args );

	include( $located );

	do_action( 'learn_press_after_template_part', $template_name, $template_path, $located, $args );
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
	if ( !$template_path ) {
		$template_path = learn_press_template_path();
	}

	if ( !$default_path ) {
		$default_path = LPR_PLUGIN_PATH . '/templates/';
	}

	// Look within passed path within the theme - this is priority
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name
		)
	);

	// Get default template
	if ( !$template ) {
		$template = $default_path . $template_name;
	}

	// Return what we found
	return apply_filters( 'learn_press_locate_template', $template, $template_name, $template_path );
}

/**
 * Check if a user has permission to view a quiz
 * If not then redirect user to 404 page
 *
 * @author  TuNN
 *
 * @param   int $user_id The ID of user to check
 * @param   int $quiz_id The ID of a quiz to check
 *
 * @return  void
 */
function learn_press_redirect_quiz_auth( $user_id = null, $quiz_id = null ) {
	// if the user_id not passed then try to get it from current user
	if ( !$user_id ) {
		$user_id = get_current_user_id();

		// check again to ensure to ensure the user is already existing
		if ( !$user_id ) {
			wp_die( __( 'You have not permission to view this page', 'learn_press' ) );
		}
	}

	// if the quiz_id is not passed then try to get it from current post
	if ( !$quiz_id ) {
		global $post;
		$quiz_id = $post->ID;

		// check again to ensure the quiz is already existing
		if ( !$quiz_id ) {
			wp_die( __( 'You have not permission to view this page', 'learn_press' ) );
		}
	}

	// get permission for viewing quiz
	$preview_quiz = get_post_meta( $quiz_id, '_lpr_preview_quiz', true );
	if ( $preview_quiz == 'not_preview' ) {
		$course_take = get_user_meta( $user_id, '_lpr_user_course', true );
		$access      = false;
		if ( $course_take )
			foreach ( $course_take as $course ) {
				$quiz = get_post_meta( $course, '_lpr_course_lesson_quiz', true );
				if ( $quiz && in_array( $post->ID, $quiz ) ) {
					$access = true;
					break;
				}
			}
		// redirect if user has not permission to view quiz
		if ( !$access ) {
			learn_press_404_page();
		}
	}
}

/**
 * Check if user has completed a quiz or not
 *
 * @author  TuNN
 *
 * @param   int $user_id The ID of user need to check
 * @param   int $quiz_id The ID of quiz need to check
 *
 * @return  boolean
 */
function learn_press_user_has_completed_quiz( $user_id = null, $quiz_id = null ) {
	$completed = false;
	// if $user_id is not passed, try to get it from current user
	if ( !$user_id ) {
		$user_id = learn_press_get_current_user_id();
		if ( !$user_id ) $completed = false;
	}

	// if $quiz_id is not passed, try to get it from current quiz
	$quiz_id = learn_press_get_quiz_id( $quiz_id );

	$quiz_completed = get_user_meta( $user_id, '_lpr_quiz_completed', true );
	$retake         = get_user_meta( $user_id, '_lpr_quiz_retake', true );

	// if user can not retake a quiz or has already completed a quiz
	if ( ( !$retake || !in_array( $quiz_id, $retake ) ) && $quiz_completed && array_key_exists( $quiz_id, $quiz_completed ) ) {
		$completed = true;
	}
	return apply_filters( 'learn_press_user_has_completed_quiz', $completed, $user_id, $quiz_id );
}

/**
 * Get all questions of a quiz
 *
 * @author  TuNN
 *
 * @param   int     $quiz_id  The ID of a quiz to get all questions
 * @param   boolean $only_ids return an array of questions with IDs only or as post objects
 *
 * @return  array|null
 */
function learn_press_get_quiz_questions( $quiz_id = null, $only_ids = true ) {
	static $quiz_questions;
	if ( !$quiz_questions ) $quiz_questions = array();
	$quiz_id = learn_press_get_quiz_id( $quiz_id );
	if ( empty( $quiz_questions[$quiz_id] ) ) {
		$questions = get_post_meta( $quiz_id, '_lpr_quiz_questions', true );

		if ( is_array( $questions ) && count( $questions ) > 0 ) {
			$question_ids = array_keys( $questions );
			$query_args   = array(
				'posts_per_page' => - 999,
				'include'        => $question_ids,
				'post_type'      => 'lpr_question',
				'post_status'    => 'publish'
			);
			if ( $only_ids ) {
				$query_args['fields'] = 'ids';
			}

			$questions = array();
			// reorder as stored in database
			if ( $_questions = get_posts( $query_args ) ):
				$questions = array_flip( $question_ids );
				foreach ( $_questions as $q ) {
					$questions[$only_ids ? $q : $q->ID] = $q;
				}
			endif;
		}
		$quiz_questions[$quiz_id] = $questions;
	}
	return apply_filters( 'learn_press_get_quiz_questions', $quiz_questions[$quiz_id], $quiz_id, $only_ids );
}

/**
 * Check if a quiz have any question or not
 */
function learn_press_quiz_has_questions( $quiz_id = null ) {
	$questions = learn_press_get_quiz_questions( $quiz_id );
	return is_array( $questions ) ? count( $questions ) : false;
}

/**
 * redirect to plugin's template if needed
 *
 * @author  TuNN
 * @return  void
 */
function learn_press_template_include( $template ) {
	global $post_type;
	do_action( 'learn_press_before_template_redirect', $post_type );

	if ( !empty( $post_type ) ) {
		if ( false !== strpos( $post_type, 'lpr_' ) ) {

			$lpr_post_type = str_replace( 'lpr_', '', $post_type );
			$template      = '';
			if ( is_archive() ) {
				$template = learn_press_locate_template( 'archive', $lpr_post_type );
			} else {
				$template = learn_press_locate_template( 'single', $lpr_post_type );
			}
			// ensure the template loaded otherwise load default template

			//if ( $template && file_exists( $template ) ) exit();
		}
	}
	return $template;
}

/**
 * get the answers of a quiz
 *
 * @param null $user_id
 * @param null $quiz_id
 *
 * @return mixed
 */
function learn_press_get_question_answers( $user_id = null, $quiz_id = null ) {
	global $quiz;
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}

	if( ! $quiz_id ){
		$quiz_id = $quiz->id;
	}
	$answers = false;
	//$quiz_id ? $quiz_id : ( $quiz ? $quiz->ID : 0 );
	$quizzes = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );
	if ( is_array( $quizzes ) && isset( $quizzes[$quiz_id] ) ) {
		$answers = $quizzes[$quiz_id];
	}
	return apply_filters( 'learn_press_get_question_answers', $answers, $quiz_id, $user_id );
}

/**
 * Check to see if user not passed the ID of quiz try to get it from global
 * Only works in single quiz page
 *
 * @param $id
 *
 * @return mixed
 */
function learn_press_get_quiz_id( $id ) {
	if ( !$id ) {
		global $quiz;
		$id = $quiz->id;
	}
	return $id;
}

/**
 * Save the answer for a question
 *
 * @param int   $user_id
 * @param int   $quiz_id
 * @param int   $question_id
 * @param mixed $question_answer
 */
function learn_press_save_question_answer( $user_id = null, $quiz_id = null, $question_id, $question_answer ) {
	if ( !$user_id ) {
		$user_id = learn_press_get_current_user()->id;// get_current_user_id();
	}
	$quiz_id = learn_press_get_quiz_id( $quiz_id );
	$quizzes = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );
	if ( !is_array( $quizzes ) ) $quizzes = array();
	if ( !isset( $quizzes[$quiz_id] ) || !is_array( $quizzes[$quiz_id] ) ) {
		$quizzes[$quiz_id] = array();
	}
	$quizzes[$quiz_id][$question_id] = $question_answer;
	update_user_meta( $user_id, '_lpr_quiz_question_answer', $quizzes );
}

/**
 * Get quiz data stored in database of an user
 *
 * @param string $meta_key
 * @param int    $user_id
 * @param int    $quiz_id
 *
 * @return bool
 */
function learn_press_get_user_quiz_data( $meta_key, $user_id = null, $quiz_id = null ) {
	if ( !$user_id ) {
		$user_id = learn_press_get_current_user_id();
	}
	$quiz_id = learn_press_get_quiz_id( $quiz_id );
	$meta = get_user_meta( $user_id, $meta_key, true );
	return !empty( $meta[$quiz_id] ) ? $meta[$quiz_id] : false;
}

/**
 * Check if user has started a quiz or not
 *
 * @param int $user_id
 * @param int $quiz_id
 *
 * @return boolean
 */
function learn_press_user_has_started_quiz( $user_id = null, $quiz_id = null ) {
	$start_time = learn_press_get_user_quiz_data( '_lpr_quiz_start_time', $user_id, $quiz_id );

	$stared = $start_time ? true : false;
	return apply_filters( 'learn_press_user_has_started_quiz', $stared, $quiz_id, $user_id );
}

/**
 * Get current status of a quiz for user
 * Status:
 *      - null => User does not start quiz
 *        - Started => User has started quiz
 *        - Completed => User has finished quiz
 *
 * @param            $quiz_id
 * @param bool|false $user_id
 *
 * @return string
 */
function learn_press_get_user_quiz_status( $quiz_id, $user_id = false ) {
	$status = '';

	if ( learn_press_user_has_started_quiz( $user_id, $quiz_id ) ) {
		$status = 'Started';
	}

	if ( learn_press_user_has_completed_quiz( $user_id, $quiz_id ) ) {
		$status = 'Completed';
	}
	return $status;
}


if ( !function_exists( 'learn_press_setup_question_data' ) ) {
	/**
	 * setup question data if we see an ID or slug of a question in request params
	 *
	 * @author  TuNN
	 *
	 * @param   int|string $question_id_variable ID or slug of a quiz
	 * @param              string                The name of global variable to set
	 *
	 * @return  object|null
	 */
	function learn_press_setup_question_data( $question_id_variable = 'quiz_id', $global_variable = 'question' ) {
		global $post, $post_type;
		$question = false;
		// set question to post if we in a single page of a question
		if ( is_single() && 'lpr_question' == $post_type ) {
			$question = $post;
		} else {
			if ( !empty( $_REQUEST[$question_id_variable] ) ) {

				if ( isset( $GLOBALS[$global_variable] ) ) unset( $GLOBALS[$global_variable] );
				$question_id = $_REQUEST[$question_id_variable];

				// if the variable is a numeric we consider it is an ID
				if ( is_numeric( $question_id ) ) {
					$question = get_post( $question_id );
					if ( $question ) {
						$GLOBALS[$global_variable] = $question;
					}
				} else { // otherwise it is a slug
					$question = get_posts(
						array(
							'name'      => $question_id,
							'post_type' => 'lpr_quiz'
						)
					);
					if ( is_array( $question ) ) {
						$GLOBALS[$global_variable] = array_shift( $question );
					}
				}
			}
		}
		return $question;
	}
}

/**
 * initial some task before display our page
 */
function learn_press_process_frontend_action() {

	learn_press_setup_quiz_data( 'quiz_id' );
	learn_press_setup_question_data( 'question_id' );

	$action = !empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
	if ( $action ) {
		$action = preg_replace( '!^learn_press_!', '', $action );
	}
	do_action( 'learn_press_frontend_action' );

	if ( $action ) {
		do_action( 'learn_press_frontend_action_' . $action );
	}
}

add_action( 'template_redirect', 'learn_press_process_frontend_action' );

/**
 * retrieve the point of a question
 *
 * @author  TuNN
 *
 * @param   $question_id
 *
 * @return  int
 */
function learn_press_get_question_mark( $question_id ) {
	$mark = intval( get_post_meta( $question_id, '_lpr_question_mark', true ) );
	$mark = max( $mark, 1 );
	return apply_filters( 'learn_press_get_question_mark', $mark, $question_id );
}

/**
 * get the total mark of a quiz
 *
 * @author  TuNN
 *
 * @param   int $quiz_id
 *
 * @return  int
 */
function learn_press_get_quiz_mark( $quiz_id = null ) {
	$quiz_id   = learn_press_get_quiz_id( $quiz_id );
	$questions = learn_press_get_quiz_questions( $quiz_id );
	$mark      = 0;
	if ( $questions ) foreach ( $questions as $question_id => $opts ) {
		$mark += learn_press_get_question_mark( $question_id );
	}
	return apply_filters( 'learn_press_get_quiz_mark', $mark, $quiz_id );
}

/**
 * get the time remaining of a quiz has started by an user
 *
 * @param null $user_id
 * @param null $quiz_id
 *
 * @return int
 */
function learn_press_get_quiz_time_remaining( $user_id = null, $quiz_id = null ) {
	global $quiz;
	if ( !$user_id ) $user_id = get_current_user_id();
	$quiz_id = $quiz_id ? $quiz_id : ( $quiz ? $quiz->id : 0 );
	if ( !$quiz_id ) return 0;
	$meta            = get_user_meta( $user_id, '_lpr_quiz_start_time', true );
	$quiz_duration   = get_post_meta( $quiz_id, '_lpr_duration', true );
	$time_remaining  = $quiz_duration * 60;
	$quiz_start_time = !empty( $meta[$quiz_id] ) ? $meta[$quiz_id] : 0;
	$quiz_start_time = apply_filters( 'learn_press_user_quiz_start_time', $quiz_start_time, $quiz_id, $user_id );

	if ( $quiz_duration && learn_press_user_has_started_quiz( $user_id, $quiz_id ) ) {
		$quiz_duration *= 60;
		$now = current_time( 'timestamp' );

		if ( $now < $quiz_start_time + $quiz_duration ) {
			$time_remaining = $quiz_start_time + $quiz_duration - $now;
		} else {
			$time_remaining = 0;
		}

	}
	return apply_filters( 'learn_press_get_quiz_time_remaining', $time_remaining, $user_id, $quiz_id );
}

/**
 * Get the time when user started a quiz
 *
 * @since 0.9.6
 *
 * @param int $quiz_id
 * @param int $user_id
 *
 * @return int
 */
function learn_press_get_quiz_start_time( $quiz_id, $user_id = null ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$quiz_start = learn_press_get_user_quiz_data( '_lpr_quiz_start_time', $user_id, $quiz_id );
	return apply_filters( 'learn_press_get_quiz_start_time', $quiz_start, $quiz_id, $user_id );
}

/**
 * Get the time when user finished a quiz
 *
 * @since 0.9.6
 *
 * @param int $quiz_id
 * @param int $user_id
 *
 * @return int
 */
function learn_press_get_quiz_end_time( $quiz_id, $user_id = null ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$quiz_start = learn_press_get_user_quiz_data( '_lpr_quiz_completed', $user_id, $quiz_id );
	return apply_filters( 'learn_press_get_quiz_end_time', $quiz_start, $quiz_id, $user_id );
}

/**
 * Get the total time user spent to finish a quiz
 *
 * @since 0.9.6
 *
 * @param int $quiz_id
 * @param int $user_id
 *
 * @return int
 */
function learn_press_get_user_quiz_time( $quiz_id, $user_id = null ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$start     = learn_press_get_quiz_start_time( $quiz_id, $user_id );
	$end       = learn_press_get_quiz_end_time( $quiz_id, $user_id );
	$time_diff = absint( $end - $start );
	return apply_filters( 'learn_press_get_user_quiz_time', $time_diff, $quiz_id, $user_id );
}

/**
 * Get the information result of a quiz
 *
 * @param int $user_id
 * @param int $quiz_id
 *
 * @return array
 */
function learn_press_get_quiz_result( $user_id = null, $quiz_id = null ) {
	global $quiz;

	if ( !$user_id ) $user_id = learn_press_get_current_user_id();


	$quiz_id   = $quiz_id ? $quiz_id : ( $quiz ? $quiz->id : 0 );
	$questions = learn_press_get_quiz_questions( $quiz_id );
	$answers   = learn_press_get_question_answers( $user_id, $quiz_id );

	$mark              = 0;
	$correct_questions = 0;
	$wrong_questions   = 0;
	$empty_questions   = 0;

	//$quiz_start = learn_press_get_quiz_start_time( $quiz_id, $user_id );//learn_press_get_user_quiz_data( '_lpr_quiz_start_time', $user_id, $quiz_id );
	//$quiz_end   = learn_press_get_quiz_end_time( $quiz_id, $user_id );//learn_press_get_user_quiz_data( '_lpr_quiz_completed', $user_id, $quiz_id );
	$mark_total = learn_press_get_quiz_mark( $quiz_id );
	$quiz_time  = learn_press_get_user_quiz_time( $quiz_id, $user_id );//( $quiz_end ? $quiz_end - $quiz_start : 0 );
	$info       = false;

	if ( $questions ) {
		foreach ( $questions as $question_id => $options ) {
			$ques_object = LPR_Question_Type::instance( $question_id );

			if ( $ques_object && isset( $answers[$question_id] ) ) {
				$check = $ques_object->check( array( 'answer' => $answers[$question_id] ) );
				if ( $check['correct'] ) {

					$correct_questions ++;
				} else {
					$wrong_questions ++;
				}
				$mark += isset( $check['mark'] ) ? $check['mark'] : 0;
			} else {
				$empty_questions ++;
			}
		}
		$question_count = count( $questions );
		if ( is_float( $mark ) ) {
			$mark = round( $mark, 1 );
		}
		$info = array(
			'mark'            => $mark,
			'correct'         => $correct_questions,
			'wrong'           => $wrong_questions,
			'empty'           => $empty_questions,
			'questions_count' => $question_count,
			'mark_total'      => round( $mark_total, 2 ),
			'mark_percent'    => round( $mark / $mark_total, 2 ),
			'correct_percent' => round( $correct_questions / $question_count * 100, 2 ),
			'wrong_percent'   => round( $wrong_questions / $question_count * 100, 2 ),
			'empty_percent'   => round( $empty_questions / $question_count * 100, 2 ),
			'quiz_time'       => $quiz_time
		);

	}

	return apply_filters( 'learn_press_get_quiz_result', $info, $user_id, $quiz_id );
}

/**
 * call this function when user hit "Start Quiz" and stores some
 * meta_key to mark that user has started this quiz
 */
function learn_press_frontend_action_start_quiz() {
	global $quiz;
	// should check user permission here to ensure user can start quiz
	$user_id = get_current_user_id();
	$quiz_id = $quiz->id;

	// @since 0.9.5
	if ( !apply_filters( 'learn_press_before_user_start_quiz', true, $quiz_id, $user_id ) ) {
		return;
	}
	$current_time = current_time( 'timestamp' );
	// update start time, this is the time user begin the quiz
	$meta = get_user_meta( $user_id, '_lpr_quiz_start_time', true );
	if ( !is_array( $meta ) ) $meta = array( $quiz_id => $current_time );
	else $meta[$quiz_id] = $current_time;
	update_user_meta( $user_id, '_lpr_quiz_start_time', $meta );

	// update questions
	if ( $questions = learn_press_get_quiz_questions( $quiz_id ) ) {

		// stores the questions
		$question_ids = array_keys( $questions );
		$meta         = get_user_meta( $user_id, '_lpr_quiz_questions', true );
		if ( !is_array( $meta ) ) $meta = array( $quiz_id => $question_ids );
		else $meta[$quiz_id] = $question_ids;
		update_user_meta( $user_id, '_lpr_quiz_questions', $meta );

		// stores current question
		$meta = get_user_meta( $user_id, '_lpr_quiz_current_question', true );
		if ( !is_array( $meta ) ) $meta = array( $quiz_id => $question_ids[0] );
		else $meta[$quiz_id] = $question_ids[0];
		update_user_meta( $user_id, '_lpr_quiz_current_question', $meta );

	}
	$course_id   = learn_press_get_course_by_quiz( $quiz_id );
	$course_time = get_user_meta( $user_id, '_lpr_course_time', true );
	if ( empty( $course_time[$course_id] ) ) {
		$course_time[$course_id] = array(
			'start' => $current_time,
			'end'   => null
		);
		update_user_meta( $user_id, '_lpr_course_time', $course_time );
	}


	// update answers
	$quizzes = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );
	if ( !is_array( $quizzes ) ) $quizzes = array();
	$quizzes[$quiz_id] = array();
	update_user_meta( $user_id, '_lpr_quiz_question_answer', $quizzes );

	// @since 0.9.5
	do_action( 'learn_press_user_start_quiz', $quiz_id, $user_id );
}

add_action( 'learn_press_frontend_action_start_quiz', 'learn_press_frontend_action_start_quiz' );
add_action( 'learn_press_frontend_action', 'learn_press_update_quiz_time' );

function learn_press_user_start_quiz( $quiz_id, $user_id ) {
	learn_press_send_json(
		array(
			'redirect' => learn_press_get_user_question_url( $quiz_id )
		)
	);
}

//add_action( 'learn_press_user_start_quiz', 'learn_press_user_start_quiz', 99, 2 );
/**
 * get position of current question is displaying in the quiz for user
 *
 * @param null $user_id
 * @param null $quiz_id
 * @param null $question_id
 *
 * @return int|mixed
 */
function learn_press_get_question_position( $user_id = null, $quiz_id = null, $question_id = null ) {
	$return = array(
		'position' => - 1,
		'id'       => 0
	);
	if ( !$user_id ) $user_id = get_current_user_id();
	if ( !$quiz_id ) {
		global $quiz;
		$quiz_id = $quiz ? $quiz->id : 0;
	}

	if ( !$question_id ) {
		$question_id = learn_press_get_current_question( $quiz_id, $user_id );
	}
	$pos = - 1;
	if ( $user_id && $quiz_id ) {

		/*if ( ! $question_id ) {
			$current_questions = get_user_meta($user_id, '_lpr_quiz_current_question', true);
			if ( ! empty ( $current_questions[ $quiz_id ] ) ) {
				$question_id = $current_questions[ $quiz_id ];
			}
		}*/

		$questions = get_user_meta( $user_id, '_lpr_quiz_questions', true );
		if ( !empty( $questions[$quiz_id] ) ) {
			$pos = array_search( $question_id, $questions[$quiz_id] );
			$pos = false !== $pos ? $pos : - 1;
		}
	}
	$return['position'] = $pos;
	$return['id']       = $question_id;
	// added since 0.9.5
	$return = apply_filters( 'learn_press_get_question_position', $return, $user_id, $quiz_id, $question_id );

	return $return;
}

/**
 * print out class for quiz body
 *
 * @param null $class
 */
function learn_press_quiz_class( $class = null ) {
	$class .= " single-quiz clearfix";
	if ( learn_press_user_has_completed_quiz() ) {
		$class .= " quiz-completed";
	} elseif ( learn_press_user_has_started_quiz() ) {
		$class .= " quiz-started";
	}
	post_class( $class );
}

/**
 * display the seconds in time format h:i:s
 *
 * @param        $seconds
 * @param string $separator
 *
 * @return string
 */
function learn_press_seconds_to_time( $seconds, $separator = ':' ) {
	return sprintf( "%02d%s%02d%s%02d", floor( $seconds / 3600 ), $separator, ( $seconds / 60 ) % 60, $separator, $seconds % 60 );
}

/**
 * create a global variable $quiz if we found a request variable such as quiz_id
 */
/*function learn_press_init_quiz() {
	if ( !empty( $_REQUEST['quiz_id'] ) ) {
		$quiz = get_post( $_REQUEST['quiz_id'] );
		if ( $quiz ) $GLOBALS['quiz'] = $quiz;
	}
}
add_action( 'wp', 'learn_press_init_quiz' );*/

/**
 * create a global variable $course if we found a request variable such as course_id
 */
function learn_press_init_course() {
	global $post_type;
	$post_id = 0;
	if ( 'lpr_course' == $post_type ) {
		global $post;
		if ($post) {
			$post_id = $post->ID;
		}
	} else if ( !empty( $_REQUEST['course_id'] ) ) {
		$post_id = $_REQUEST['course_id'];
	}
	lp_setup_course_data( $post_id );
}
add_action( 'wp', 'learn_press_init_course' );

function learn_press_head() {
	if ( is_single() && 'lpr_course' == get_post_type() ) {
		wp_enqueue_script( 'tojson', LPR_PLUGIN_URL . '/assets/js/toJSON.js' );
	}
}

add_action( 'wp_head', 'learn_press_head' );

/**
 * Enqueue js code to print out
 *
 * @param string $code
 * @param bool   $script_tag - wrap code between <script> tag
 */
function learn_press_enqueue_script( $code, $script_tag = false ) {
	global $learn_press_queued_js, $learn_press_queued_js_tag;

	if ( $script_tag ) {
		if ( empty( $learn_press_queued_js_tag ) ) {
			$learn_press_queued_js_tag = '';
		}
		$learn_press_queued_js_tag .= "\n" . $code . "\n";
	} else {
		if ( empty( $learn_press_queued_js ) ) {
			$learn_press_queued_js = '';
		}

		$learn_press_queued_js .= "\n" . $code . "\n";
	}
}

/**
 * Print out js code in the queue
 */
function learn_press_print_script() {
	global $learn_press_queued_js, $learn_press_queued_js_tag;
	if ( !empty( $learn_press_queued_js ) ) {
		?>
		<!-- LearnPress JavaScript -->
		<script type="text/javascript">jQuery(function ($) {
				<?php
				// Sanitize
				$learn_press_queued_js = wp_check_invalid_utf8( $learn_press_queued_js );
				$learn_press_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $learn_press_queued_js );
				$learn_press_queued_js = str_replace( "\r", '', $learn_press_queued_js );

				echo $learn_press_queued_js;
				?>
			});
		</script>
		<?php
		unset( $learn_press_queued_js );
	}

	if ( !empty( $learn_press_queued_js_tag ) ) {
		echo $learn_press_queued_js_tag;
	}
}

add_action( 'wp_head', 'learn_press_head' );
add_action( 'wp_footer', 'learn_press_print_script' );
add_action( 'admin_footer', 'learn_press_print_script' );

/**
 * Gets duration of a quiz
 *
 * @param null $quiz_id
 *
 * @return mixed
 */
function learn_press_get_quiz_duration( $quiz_id = null ) {
	global $quiz;
	if ( !$quiz_id ) $quiz_id = $quiz ? $quiz->id : 0;
	$duration = intval( get_post_meta( $quiz_id, '_lpr_duration', true ) );
	return apply_filters( 'learn_press_get_quiz_duration', $duration, $quiz_id );
}

/**
 * Get the price of a course
 *
 * @author  Tunn
 *
 * @param   null $course_id
 *
 * @return  int
 */
function learn_press_get_course_price( $course_id = null, $with_currency = false ) {
	if ( !$course_id ) {
		global $post;
		$course_id = $post ? $post->ID : 0;
	}
	if ( !learn_press_is_free_course( $course_id ) ) {
		$price = floatval( get_post_meta( $course_id, '_lpr_course_price', true ) );
		if ( $with_currency ) {
			$price = learn_press_format_price( $price, true );
		}
	} else {
		$price = 0;
	}
	return apply_filters( 'learn_press_get_course_price', $price, $course_id );
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
		global $post;
		$course_id = $post ? $post->ID : 0;
	}
	return ( 'free' == get_post_meta( $course_id, '_lpr_course_payment', true ) ) || ( 0 >= floatval( get_post_meta( $course_id, '_lpr_course_price', true ) ) );
}

/**
 * Action when user press the "Take this course" button
 *
 * @param        $course_id
 * @param string $payment_method
 */
function learn_press_take_course( $course_id, $payment_method = '' ) {
	$user            = learn_press_get_current_user();
	$can_take_course = apply_filters( 'learn_press_before_take_course', true, $user->ID, $course_id, $payment_method );
	$course          = LP_Course::get_course( $course_id );

	if ( $can_take_course || !$course->id ) {
		if ( $course->is_free() ) {
			if ( $order_id = learn_press_add_transaction(
				array(
					'method'             => 'free',
					'method_id'          => '',
					'status'             => '',
					'user_id'            => $user->ID,
					'transaction_object' => learn_press_generate_transaction_object()
				)
			)
			) {
				learn_press_update_order_status( $order_id, 'Completed' );
				learn_press_add_message( 'message', __( 'Congratulations! You have enrolled this course', 'learn_press' ) );
				$json = array(
					'result'   => 'success',
					'redirect' => ( ( $confirm_page_id = learn_press_get_page_id( 'taken_course_confirm' ) ) && get_post( $confirm_page_id ) ) ? learn_press_get_order_confirm_url( $order_id ) : get_permalink( $course_id )
				);
				learn_press_send_json( $json );
			}
		} else {
			if ( has_filter( 'learn_press_take_course_' . $payment_method ) ) {
				$order  = null;
				$result = apply_filters( 'learn_press_take_course_' . $payment_method, $order );
				$result = apply_filters( 'learn_press_payment_result', $result, $order );
				if ( is_ajax() ) {
					learn_press_send_json( $result );
					exit;
				} else {
					wp_redirect( $result['redirect'] );
					exit;
				}
			} else {
				wp_die( __( 'Invalid payment method.', 'learn_press' ) );
			}

		}
	} else {
		learn_press_add_message( 'error', __( 'Sorry! You can not enroll to this course', 'learn_press' ) );
		$json = array(
			'result'   => 'error',
			'redirect' => get_permalink( $course_id )
		);
		learn_press_send_json( $json );
	}
}

add_filter( 'learn_press_take_course', 'learn_press_take_course', 5, 2 );

if ( !function_exists( 'is_ajax' ) ) {

	/**
	 * is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @access public
	 * @return bool
	 */
	function is_ajax() {
		return defined( 'DOING_AJAX' );
	}
}

/**
 * Check before user take course and if they are not logged in then redirect to login page
 *
 * @param $can_take
 * @param $user_id
 * @param $course_id
 * @param $payment_method
 */
function learn_press_require_login_to_take_course( $can_take, $user_id, $course_id, $payment_method ) {
	if ( !is_user_logged_in() ) {
		$login_url = learn_press_get_login_url( get_permalink( $course_id ) );
		learn_press_send_json(
			array(
				'result'   => 'success',
				'redirect' => $login_url
			)
		);
	}
}

add_filter( 'learn_press_before_take_course', 'learn_press_require_login_to_take_course', 5, 4 );

/**
 * Filter the login url so third-party can be customize
 *
 * @param null $redirect
 *
 * @return mixed
 */
function learn_press_get_login_url( $redirect = null ) {
	return apply_filters( 'learn_press_login_url', wp_login_url( $redirect ) );
}

/**
 * When user take a course clear the cart and add the new course into cart
 * Currently, user can be add only one course each time and do checkout right away
 *
 * @param $can_take
 * @param $user_id
 * @param $course_id
 * @param $payment_method
 *
 * @return mixed
 */
function learn_press_before_take_course( $can_take, $user_id, $course_id, $payment_method ) {
	// only one course in time
	LPR_Cart::instance()->empty_cart()->add_to_cart( $course_id );
	return $can_take;
}

add_filter( 'learn_press_before_take_course', 'learn_press_before_take_course', 5, 4 );

/**
 * @param $order_id
 *
 * @return array|bool
 */
function learn_press_get_transition_products( $order_id ) {
	$order_items = get_post_meta( $order_id, '_learn_press_order_items', true );
	$products    = false;
	if ( $order_items ) {
		if ( !empty( $order_items->products ) ) {
			$products = array();
			foreach ( $order_items->products as $pro ) {
				$product = get_post( $pro['id'] );
				if ( $product ) {
					$product->price    = $pro['price'];
					$product->quantity = $pro['quantity'];
					$product->amount   = learn_press_is_free_course( $pro['id'] ) ? 0 : ( $product->price * $product->quantity );
					$products[]        = $product;
				}
			}
		}
	}
	return $products;
}

/**
 * @param $can_take
 * @param $user_id
 * @param $course_id
 *
 * @return bool
 */
function learn_press_check_user_pass_prerequisite( $can_take, $user_id = null, $course_id = null ) {
	$prerequisite = learn_press_user_prerequisite_courses( $user_id, $course_id );
	return $prerequisite ? false : true;
}

add_filter( 'learn_press_before_take_course', 'learn_press_check_user_pass_prerequisite', 105, 3 );

/**
 * @param null $course_id
 *
 * @return int|null
 */
function learn_press_get_course_id( $course_id = null ) {
	if ( !$course_id ) {
		global $course;
		$course_id = $course ? $course->id : 0;
	}
	return $course_id;
}

/**
 * count the number of students has enrolled a course
 *
 * @author  TuNN
 *
 * @param   int $course_id
 *
 * @return  int
 */
function learn_press_count_students_enrolled( $course_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	$course    = LP_Course::get_course( $course_id );
	$student   = $course->course_student;// get_post_meta( $course_id, '_lpr_course_student', true );
	if ( $student ) {
		$count = $student;
	} else {
		$count = $course->count_users_enrolled();//( $users = get_post_meta( $course_id, '_lpr_course_user', true ) ) ? sizeof( $users ) : 0;
	}
	return apply_filters( 'learn_press_count_student_enrolled_course', $count, $course_id );
}

/**
 * count the number of students has passed a course
 *
 * @author  Ken
 *
 * @param   int $course_id
 *
 * @return  int
 */
function learn_press_count_students_passed( $course_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	$count     = ( $users = get_post_meta( $course_id, '_lpr_user_finished', true ) ) ? sizeof( $users ) : 0;
	return apply_filters( 'learn_press_count_student_passed_course', $count, $course_id );
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

function learn_press_count_student_enrolled_course( $course_id = null ) {
	return learn_press_count_students_enrolled( $course_id );
}

/**
 * Get the max number of students can enroll a course
 *
 * @param null $course_id
 *
 * @return mixed
 */
function learn_press_get_limit_student_enroll_course( $course_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	$count     = intval( get_post_meta( $course_id, '_lpr_max_course_number_student', true ) );
	return apply_filters( 'learn_press_get_limit_student_enroll_course', $count, $course_id );
}

function learn_press_increment_user_enrolled( $course_id = null, $count = false ) {
	return;
	$course_id = learn_press_get_course_id( $course_id );

	if ( is_bool( $count ) && !$count ) {
		$count = learn_press_count_student_enrolled_course( $course_id );
		$count ++;
	} else {
		$count = intval( $count );
	}
	$max_enroll = learn_press_get_limit_student_enroll_course( $course_id );
	if ( $max_enroll && $count > $max_enroll ) {
		$count = $max_enroll;
	}
	update_post_meta( $course_id, '_lpr_course_number_student', $count );
}

function learn_press_decrement_user_enrolled( $course_id = null, $count = false ) {
	return;
	$course_id = learn_press_get_course_id( $course_id );

	if ( is_bool( $count ) && !$count ) {
		$count = learn_press_count_student_enrolled_course( $course_id );
		$count --;
	} else {
		$count = intval( $count );
	}
	if ( $count < 0 ) {
		$count = 0;
	}
	update_post_meta( $course_id, '_lpr_course_number_student', $count );
}

/**
 * Check to see if a user can retake a quiz they have completed
 * Only for the quiz that allows user can retake
 *
 * @param null $quiz_id
 * @param null $user_id
 *
 * @return bool
 */
function learn_press_user_can_retake_quiz( $quiz_id = null, $user_id = null ) {
	$quiz_id = learn_press_get_quiz_id( $quiz_id );
	if ( !$user_id ) {
		$user_id = learn_press_get_current_user_id();
	}


	if ( !$quiz_id || !$user_id ) {
		return apply_filters( 'learn_press_anonymous_user_can_retake_quiz', false, $quiz_id );
	}
	if ( !learn_press_user_has_completed_quiz( $user_id, $quiz_id ) ) {
		//apply_filters( 'learn_press_user_can_retake_quiz', $can_retake, $quiz_id, $user_id, $taken, $available );
		return false;
	}

	$available = get_post_meta( $quiz_id, '_lpr_retake_quiz', true );//learn_press_settings( 'pages', 'quiz.retake_quiz' );

	if ( !$available ) return false;

	global $wpdb;
	$query = $wpdb->prepare( "
        SELECT count(meta_key)
        FROM {$wpdb->usermeta}
        WHERE user_id = %d
        AND meta_key = %s
        AND meta_value = %d
    ", $user_id, '_lpr_quiz_taken', $quiz_id );
	$taken = $wpdb->get_var( $query );

	$can_retake = $taken < $available;
	// added 0.9.5
	return apply_filters( 'learn_press_user_can_retake_quiz', $can_retake, $quiz_id, $user_id, $taken, $available );
}

/**
 * Check to see if user can retake a course they have finished
 * Only for the course that allows user can retake
 *
 * @param null $course_id
 * @param null $user_id
 *
 * @return bool
 */
function learn_press_user_can_retake_course( $course_id = null, $user_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	if ( !$course_id || !$user_id ) return false;

	if ( !learn_press_user_has_finished_course( $course_id, $user_id ) ) return false;

	$available = get_post_meta( $course_id, '_lpr_retake_course', true );//learn_press_settings( 'pages', 'course.retake_course' );
	if ( !$available ) return false;

	global $wpdb;
	$query = $wpdb->prepare( "
        SELECT count(meta_key)
        FROM {$wpdb->usermeta}
        WHERE user_id = %d
        AND meta_key = %s
        AND meta_value = %d
    ", $user_id, '_lpr_course_taken', $course_id );
	$taken = $wpdb->get_var( $query );
	return $taken < $available;
}

/**
 * Add a message into queue and then print out
 *
 * @param $type
 * @param $message
 */
function learn_press_add_message( $type, $message ) {
	$messages = get_transient( 'learn_press_message' );
	if ( !$messages ) $messages = array();
	if ( empty( $messages[$type] ) ) $messages[$type] = array();
	$messages[$type][] = $message;
	set_transient( 'learn_press_message', $messages, HOUR_IN_SECONDS );
}

/**
 * Print out the message stored in the queue
 */
function learn_press_show_message() {
	$messages = get_transient( 'learn_press_message' );
	if ( $messages ) foreach ( $messages as $type => $message ) {
		foreach ( $message as $mess ) {
			echo '<div class="lp-message ' . $type . '">';
			echo $mess;
			echo '</div>';
		}
	}
	delete_transient( 'learn_press_message' );
}

add_action( 'learn_press_before_main_content', 'learn_press_show_message', 50 );

/**
 * Check to see if user can view a quiz or not
 *
 * @param int $user_id
 * @param int $quiz_id
 *
 * @return boolean
 */
function learn_press_user_can_view_quiz( $quiz_id = null, $user_id = null ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	if ( !$quiz_id ) {

		$quiz_id = get_the_ID();
	}

	if ( !$quiz_id ) return false;
	$course_id = get_post_meta( $quiz_id, '_lpr_course', true );

	$return           = false;
	$enrolled_require = get_post_meta( $course_id, '_lpr_course_enrolled_require', true );
	// check enrolled require
	if ( !$enrolled_require || $enrolled_require == 'no' ) {
		$return = true;
	} else {
		if ( learn_press_is_enrolled_course( $course_id ) ) { // user has enrolled course
			$return = true;
		}
	}
	return apply_filters( 'learn_press_user_can_view_quiz', $return, $quiz_id, $user_id );
}

/**
 * Check to see if user can view a assignment or not
 *
 * @param int $user_id
 * @param int $assignment_id
 *
 * @return boolean
 */
function learn_press_user_can_view_assignment( $assignment_id = null, $user_id = null ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	if ( !$assignment_id ) {
		global $assignment;
		$assignment_id = $assignment ? $assignment->ID : 0;
	}

	if ( !$assignment_id ) return false;
	$course_id = get_post_meta( $assignment_id, '_lpr_course', true );

	$return           = false;
	$enrolled_require = get_post_meta( $course_id, '_lpr_course_enrolled_require', true );

	// check enrolled require
	if ( !$enrolled_require || $enrolled_require == 'no' ) {
		$return = true;
	} else {
		if ( learn_press_is_enrolled_course( $course_id ) ) { // user has enrolled course
			$return = true;
		}
	}
	return apply_filters( 'learn_press_user_can_view_assignment', $return, $assignment_id, $user_id );
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

/**
 * Get page id from admin settings page
 *
 * @param string $name
 *
 * @return int
 */
function learn_press_get_page_id( $name ) {
	$settings = LPR_Settings::instance( 'pages' );
	return $settings->get( "general.{$name}_page_id", false );
}

/**
 * Get path of the plugin include the sub path if passed
 *
 * @param string $sub
 *
 * @return string
 */
function learn_press_plugin_path( $sub = null ) {
	return $sub ? LPR_PLUGIN_PATH . '/' . untrailingslashit( $sub ) . '/' : LPR_PLUGIN_PATH;
}

/**
 * @param int
 * @param int - since 0.9.5
 *
 * @return bool|int
 */
function learn_press_get_current_question( $quiz_id = null, $user_id = 0 ) {
	$quiz_id = learn_press_get_quiz_id( $quiz_id );
	if ( !$user_id ) $user_id = get_current_user_id();
	if ( !$quiz_id ) return false;
	if ( $question_id = learn_press_get_request( 'question' ) ) {

	} else {
		$questions = get_user_meta( $user_id, '_lpr_quiz_current_question', true );
		if ( !empty( $questions ) && !empty( $questions[$quiz_id] ) ) {
			$question_id = $questions[$quiz_id];
		} else {
			$questions   = (array) learn_press_get_user_quiz_questions( $quiz_id, $user_id );
			$question_id = reset( $questions );
		}
	}
	// ver 0.9.5
	$question_id = apply_filters( 'learn_press_get_current_question', $question_id, $quiz_id, $user_id );
	return $question_id;
}

/**
 * get the course of a lesson
 *
 * @author TuNguyen
 *
 * @param int     $lesson_id
 * @param boolean $id_only
 *
 * @return mixed
 */
function learn_press_get_course_by_lesson( $lesson_id, $id_only = true ) {
	$course = get_post_meta( $lesson_id, '_lpr_course' );
	if ( $course ) $course = end( $course );
	if ( !$id_only ) {
		$course = get_post( $course );
	}
	return $course;
}

/**
 * Retrieves the course that a quiz is assigned to
 *
 * @param      $quiz_id
 * @param bool $id_only
 *
 * @return mixed
 */
function learn_press_get_course_by_quiz( $quiz_id, $id_only = true ) {
	$course = get_post_meta( $quiz_id, '_lpr_course', true );
	if ( !$id_only ) {
		$course = get_post( $course );
	}
	return $course;
}

/**
 * mark a lesson is completed for a user
 *
 * @author TuNguyen
 *
 * @param int $lesson_id
 * @param int $user_id
 *
 * @return boolean
 */
function learn_press_mark_lesson_complete( $lesson_id, $user_id = null ) {
	if ( !$user_id ) $user_id = get_current_user_id();
	if ( !$lesson_id ) return false;
	$lesson_completed = get_user_meta( $user_id, '_lpr_lesson_completed', true );
	if ( !$lesson_completed ) {
		$lesson_completed = array();
	}

	$course_id = learn_press_get_course_by_lesson( $lesson_id );
	if ( !isset( $lesson_completed[$course_id] ) || !is_array( $lesson_completed[$course_id] ) ) {
		$lesson_completed[$course_id] = array();
	}
	$lesson_completed[$course_id][] = $lesson_id;
	// ensure that doesn't store duplicate values
	$lesson_completed[$course_id] = array_unique( $lesson_completed[$course_id] );
	update_user_meta( $user_id, '_lpr_lesson_completed', $lesson_completed );

	if ( !learn_press_user_has_finished_course( $course_id ) ) {
		if ( learn_press_user_has_completed_all_parts( $course_id, $user_id ) ) {
			learn_press_finish_course( $course_id, $user_id );
		}
	}
	return true;
}

/**
 * Check to see if a user can be finished a course
 *
 * @param null $course_id
 * @param null $user_id
 * @param int  $passing_condition
 *
 * @return bool
 */
function learn_press_user_can_finish_course( $course_id = null, $user_id = null, $passing_condition = 0 ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) $user_id = get_current_user_id();

	$passing_condition = $passing_condition ? $passing_condition : learn_press_get_course_passing_condition( $course_id );
	if ( get_post_meta( $course_id, '_lpr_course_final', true ) == 'yes' ) {
		$final_quiz = lpr_get_final_quiz( $course_id );
		$passed     = learn_press_quiz_evaluation( $final_quiz, $user_id );
		return $passed && ( $passed >= $passing_condition );
	} else {
		$passed = lpr_course_evaluation( $course_id );
		return $passed && ( $passed >= $passing_condition );
	}
	return false;
}

/**
 * Check to see if user already learned all lessons or completed final quiz
 *
 * @param null $course_id
 * @param null $user_id
 *
 * @return bool
 */
function learn_press_user_has_completed_all_parts( $course_id = null, $user_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) $user_id = get_current_user_id();
	if ( get_post_meta( $course_id, '_lpr_course_final', true ) == 'yes' ) {
		$final_quiz = lpr_get_final_quiz( $course_id );
		return learn_press_user_has_completed_quiz( $user_id, $final_quiz );
	} else {
		return lpr_course_evaluation( $course_id ) == 100;
	}
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
 * Get all lessons in a course by ID
 *
 * @param null $course_id
 */
function learn_press_get_lessons_in_course( $course_id = null ) {
	static $lessons = array();
	if ( is_null( $course_id ) ) return array();
	if ( empty( $lessons[$course_id] ) ) {
		$course_lessons = array();
		$curriculum     = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
		if ( $curriculum ) foreach ( $curriculum as $lesson_quiz ) {
			if ( array_key_exists( 'lesson_quiz', $lesson_quiz ) && is_array( $lesson_quiz['lesson_quiz'] ) ) {
				$posts = get_posts(
					array(
						'post_type'   => 'lpr_lesson',
						'include'     => $lesson_quiz['lesson_quiz'],
						'post_status' => 'publish',
						'fields'      => 'ids',
						'numberposts' => - 1
					)
				);
				if ( $posts ) {
					// sorting as in the curriculum section
					foreach ( $lesson_quiz['lesson_quiz'] as $pid ) {
						if ( in_array( $pid, $posts ) ) {
							$course_lessons[] = $pid;
						}
					}
				}
			}
		}
		$lessons[$course_id] = array_unique( $course_lessons );
	}

	return $lessons[$course_id];
}

/**
 * Get all lessons and quizzes of a course
 *
 * @param null $course_id
 * @param bool $id_only
 *
 * @return array
 */
function learn_press_get_lessons_quizzes( $course_id = null, $id_only = true ) {
	$course_id = learn_press_get_course_id( $course_id );
	$sections  = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
	$posts     = array();
	if ( $sections ) {
		foreach ( $sections as $section ) {
			if ( !empty( $section['lesson_quiz'] ) && is_array( $section['lesson_quiz'] ) ) {
				$posts = array_merge( $posts, $section['lesson_quiz'] );
			}
		}
	}
	$posts = array_unique( $posts );
	if ( !$id_only ) {
		$posts = get_posts(
			array(
				'post_type' => array( 'lpr_lesson', 'lpr_quiz' ),
				'include'   => $posts
			)
		);
	}
	return $posts;
}

/**
 * Mark a quiz is completed for an user
 *
 * @param int $quiz_id
 * @param int $user_id
 */
function learn_press_mark_quiz_complete( $quiz_id = null, $user_id = null ) {
	$quiz_id = learn_press_get_quiz_id( $quiz_id );
	$time = current_time( 'timestamp' );
	if ( !learn_press_user_has_completed_quiz( $quiz_id ) ) {
		if ( !$user_id ) $user_id = get_current_user_id();

		// update quiz start time if not set
		$quiz_start_time = get_user_meta( $user_id, '_lpr_quiz_start_time', true );
		if ( empty( $quiz_start_time[$quiz_id] ) ) {
			$quiz_start_time[$quiz_id] = $time;
			update_user_meta( $user_id, '_lpr_quiz_start_time', $quiz_start_time );
		}

		// update questions
		if ( $questions = learn_press_get_quiz_questions( $quiz_id ) ) {

			// stores the questions
			$question_ids = array_keys( $questions );
			$meta         = get_user_meta( $user_id, '_lpr_quiz_questions', true );
			if ( !is_array( $meta ) ) {
				$meta = array( $quiz_id => $question_ids );
			}

			if ( empty( $meta[$quiz_id] ) ) {
				$meta[$quiz_id] = $question_ids;
			}
			update_user_meta( $user_id, '_lpr_quiz_questions', $meta );

			// stores current question
			$meta = get_user_meta( $user_id, '_lpr_quiz_current_question', true );
			if ( !is_array( $meta ) ) $meta = array( $quiz_id => $question_ids[0] );
			if ( empty( $meta[$quiz_id] ) ) $meta[$quiz_id] = end( $question_ids );
			update_user_meta( $user_id, '_lpr_quiz_current_question', $meta );

		}

		// update answers
		$quizzes = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );
		if ( !is_array( $quizzes ) ) {
			$quizzes = array();
		}
		if ( empty( $quizzes[$quiz_id] ) ) {
			$quizzes[$quiz_id] = array();
			update_user_meta( $user_id, '_lpr_quiz_question_answer', $quizzes );
		}
		// update the quiz's ID to the completed list
		$quiz_completed = get_user_meta( $user_id, '_lpr_quiz_completed', true );
		if ( !$quiz_completed ) {
			$quiz_completed = array();
		}
		if ( empty( $quiz_completed[$quiz_id] ) ) {
			$quiz_completed[$quiz_id] = $time;
			update_user_meta( $user_id, '_lpr_quiz_completed', $quiz_completed );
		}
		// count
		//add_user_meta($user_id, '_lpr_quiz_taken', $quiz_id);
	}
}

/**
 * Finish a course by ID of an user
 * When a course marked is finished then also mark all lessons, quizzes as completed
 *
 * @param int $course_id
 * @param int $user_id
 *
 * @return array
 */
function learn_press_finish_course( $course_id = null, $user_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) $user_id = get_current_user_id();

	$can_finish = ! learn_press_get_current_user()->is('guest');
	if( ! apply_filters( 'lp_before_finish_course', $can_finish, $course_id, $user_id ) ){
		return;
	}
	$course_finished = get_user_meta( $user_id, '_lpr_course_finished', true );
	if ( !$course_finished ) $course_finished = array();
	$course_finished[] = $course_id;
	$course_finished   = array_unique( $course_finished );
	update_user_meta( $user_id, '_lpr_course_finished', $course_finished );

	$course_time = get_user_meta( $user_id, '_lpr_course_time', true );
	if ( !$course_time ) $course_time = array();
	if ( !empty( $course_time[$course_id] ) ) {
		$course_time[$course_id]['end'] = current_time( 'timestamp' );
	}
	update_user_meta( $user_id, '_lpr_course_time', $course_time );

	//learn_press_output_file( $course_time, 'finish_course.txt' );

	$user_finished = get_post_meta( $course_id, '_lpr_user_finished', true );
	if ( !$user_finished ) $user_finished = array();
	$user_finished[] = $user_id;
	update_post_meta( $course_id, '_lpr_user_finished', $user_finished );

	$lesson_quiz = learn_press_get_lessons_quizzes( $course_id, false );

	if ( $lesson_quiz ) foreach ( $lesson_quiz as $post ) {
		if ( 'lpr_lesson' == $post->post_type ) {
			learn_press_mark_lesson_complete( $post->ID );
		} else {
			learn_press_mark_quiz_complete( $post->ID );
		}
	}
	do_action( 'learn_press_user_finished_course', $course_id, $user_id );
	return array(
		'finish'  => true,
		'message' => ''
	);
}

/**
 * Check to see if an user has finish course
 *
 * @author TuNguyen
 *
 * @param null $course_id
 * @param null $user_id
 *
 * @return bool
 */
function learn_press_user_has_finished_course( $course_id = null, $user_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) $user_id = get_current_user_id();

	if ( !$user_id || !$course_id ) return false;
	$courses  = get_user_meta( $user_id, '_lpr_course_finished', true );
	$finished = is_array( $courses ) && in_array( $course_id, $courses );

	return $finished;
}

/**
 * Get the passing condition set in a course
 *
 * @param null $course_id
 *
 * @return int
 */
function learn_press_get_course_passing_condition( $course_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	return intval( get_post_meta( $course_id, '_lpr_course_condition', true ) );
}

/**
 * Check to see if a user has enrolled course or not
 *
 * @param null $course_id
 * @param null $user_id
 *
 * @return bool
 */
function learn_press_user_has_enrolled_course( $course_id = null, $user_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) $user_id = get_current_user_id();

	$courses = learn_press_get_user_courses( $user_id );
	return is_array( $courses ) && in_array( $course_id, $courses );
}

/**
 * Get the array of the courses that user has enrolled
 *
 * @param $user_id
 *
 * @return mixed
 */
function learn_press_get_user_courses( $user_id ) {
	$courses = get_user_meta( $user_id, '_lpr_user_course', true );
	return $courses;
}

/**
 * Auto eval the result of the course and mark it as finished if the time is over
 */
function learn_press_auto_evaluation_course() {
	$user_id = get_current_user_id();
	$courses = learn_press_get_user_courses( $user_id );

	if ( !$courses ) return;
	$now = current_time( 'timestamp' );
	foreach ( $courses as $course_id ) {
		if ( learn_press_user_has_finished_course( $course_id ) ) continue;
		$course_duration = intval( get_post_meta( $course_id, '_lpr_course_duration', true ) ) * 7 * 24 * 3600;
		$course_time     = get_user_meta( $user_id, '_lpr_course_time', true );
		if ( empty( $course_time[$course_id] ) ) {
			$course_time[$course_id] = array(
				'start' => $now,
				'end'   => null
			);
			update_user_meta( $user_id, '_lpr_course_time', $course_time );

		}

		$course_time = $course_time[$course_id];
		$start_time  = intval( $course_time['start'] );

		if ( $course_duration && ( $start_time + $course_duration <= $now ) ) {
			learn_press_finish_course( $course_id, $user_id );
		} else {
			//echo "Time to finish: " . ( ( $start_time + $course_duration - $now ) / ( 7 * 24 * 3600 ) );
		}
	}
}

add_action( 'learn_press_frontend_action', 'learn_press_auto_evaluation_course' );

/**
 * Update course into user's metadata if order status is "Completed" -> user enrolled course
 * Otherwise, remove course from user's metadata -> user hasn't enrolled course
 *
 * @param $status
 * @param $order_id
 */
function learn_press_active_user_course( $status, $order_id ) {
	$order            = new LPR_Order( $order_id );
	$user             = $order->get_user();
	$course_id        = learn_press_get_course_by_order( $order_id );
	$user_course_time = get_user_meta( $user->ID, '_lpr_course_time', true );

	if ( strtolower( $status ) == 'completed' ) {
		if ( empty( $user_course_time[$course_id] ) ) {
			$user_course_time[$course_id] = array(
				'start' => current_time( 'timestamp' ),
				'end'   => null
			);
		}
	} else {
		if ( !empty( $user_course_time[$course_id] ) ) {
			unset( $user_course_time[$course_id] );
		}
	}
	if ( $user_course_time ) {
		update_user_meta( $user->ID, '_lpr_course_time', $user_course_time );
	} else {
		delete_user_meta( $user->ID, '_lpr_course_time' );
	}
}

add_action( 'learn_press_update_order_status', 'learn_press_active_user_course', 10, 2 );

/**
 * Calculate the time remain for a course from it is started to now
 *
 * @param null $course_id
 * @param null $user_id
 *
 * @return bool|int|string
 */
function learn_press_get_course_remaining_time( $course_id = null, $user_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) $user_id = get_current_user_id();

	$course_duration = intval( get_post_meta( $course_id, '_lpr_course_duration', true ) ) * 7 * 24 * 3600;
	$course_time     = get_user_meta( $user_id, '_lpr_course_time', true );
	$remain          = false;
	if ( !empty( $course_time[$course_id] ) ) {
		$now         = time();
		$course_time = $course_time[$course_id];
		$start_time  = intval( $course_time['start'] );

		if ( $start_time + $course_duration <= $now ) {

		} else {
			$remain = $start_time + $course_duration - $now;
			$remain = learn_press_seconds_to_weeks( $remain );
		}
	}
	return $remain;
}

/**
 * Get questions from quiz for user
 *
 * @param  int $quiz_id
 * @param  int $user_id
 *
 * @return array
 */
function learn_press_get_user_quiz_questions_deprecated( $quiz_id = null, $user_id = null ) {
	$quiz_id = learn_press_get_quiz_id( $quiz_id );
	if ( !$user_id ) $user_id = get_current_user_id();

	$questions           = get_user_meta( $user_id, '_lpr_quiz_questions', true );
	$user_quiz_questions = array();
	$quiz_questions      = array();

	if ( $questions && !empty( $questions[$quiz_id] ) ) {
		$user_quiz_questions = $questions[$quiz_id];
	}
	if ( $quiz_questions = (array) get_post_meta( $quiz_id, '_lpr_quiz_questions', true ) ) {
		if ( $quiz_questions ) {
			$quiz_questions = array_keys( $quiz_questions );
		} else {
			$quiz_questions = array();
		}
	}
	return array_unique( array_merge( $user_quiz_questions, $quiz_questions ) );
}

/**
 * Check if user has passed the passing condition or not
 *
 * @param  int $course_id
 * @param  int $user_id
 *
 * @return boolean
 */
function learn_press_user_has_passed_conditional( $course_id = null, $user_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) $user_id = get_current_user_id();
	if ( !$course_id || !$user_id ) return false;

	$has_finished = learn_press_user_has_finished_course( $course_id, $user_id );
	if ( get_post_meta( $course_id, '_lpr_course_final', true ) == 'yes' && $quiz = lpr_get_final_quiz( $course_id ) ) {
		$passed            = learn_press_quiz_evaluation( $quiz, $user_id );
		$passing_condition = learn_press_get_course_passing_condition( $course_id );
	} else {
		$passed            = lpr_course_evaluation( $course_id );
		$passing_condition = 100;
	}
	$return = ( $passed >= $passing_condition ) && ( !$has_finished && $passing_condition < 100 );
	return apply_filters( 'learn_press_user_passed_conditional', $return, $course_id, $user_id, $passed );
}

/**
 * Return if a student passes course or not
 *
 * @param  $course_id int
 * @param  $user_id   int
 *
 * @return boolean
 */
function learn_press_user_has_passed_course( $course_id = null, $user_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) $user_id = get_current_user_id();

	if ( !$course_id ) return 0;
	if ( !$user_id ) {
		// @since 0.9.6
		return apply_filters( 'learn_press_user_has_passed_course', 0, $course_id, 0 );
	}
	$has_finished = learn_press_user_has_finished_course( $course_id, $user_id );

	if ( ( get_post_meta( $course_id, '_lpr_course_final', true ) == 'yes' ) && ( $quiz = lpr_get_final_quiz( $course_id ) ) ) {
		$passed            = learn_press_quiz_evaluation( $quiz, $user_id );
		$passing_condition = learn_press_get_course_passing_condition( $course_id );

	} else {
		$passed            = lpr_course_evaluation( $course_id );
		$passing_condition = 0;
	}
	$user_passed = $passing_condition ? ( $passed >= $passing_condition ? $passed : 0 ) : ( $passed == 100 );
	// @since 0.9.6
	return apply_filters( 'learn_press_user_has_passed_course', $user_passed, $course_id, $user_id );
}

function learn_press_get_course_result( $course_id = null, $user_id = null ) {
	$course_id = learn_press_get_course_id( $course_id );
	if ( !$user_id ) $user_id = get_current_user_id();
	if ( !$course_id || !$user_id ) return 0;

	if ( ( get_post_meta( $course_id, '_lpr_course_final', true ) == 'yes' ) && ( $quiz = lpr_get_final_quiz( $course_id ) ) ) {
		$passed = learn_press_quiz_evaluation( $quiz, $user_id );
		//$passing_condition = learn_press_get_course_passing_condition( $course_id );
	} else {
		$passed = lpr_course_evaluation( $course_id );
		//$passing_condition = 0;
	}
	return $passed;
}

/**
 * Translate text used for js code
 */
function learn_press_frontent_script() {
	if ( defined( 'DOING_AJAX' ) || is_admin() ) return;
	$translate = array(
		'confirm_retake_course'     => __( 'Be sure you want to retake this course! All your data will be deleted.', 'learn_press' ),
		'confirm_retake_quiz'       => __( 'Be sure you want to retake this quiz! All your data will be deleted.', 'learn_press' ),
		'confirm_finish_quiz'       => __( 'Are you sure you want to finish this quiz?', 'learn_press' ),
		'confirm_complete_lesson'   => __( 'Are you sure you want to mark this lesson as completed?', 'learn_press' ),
		'confirm_finish_course'     => __( 'Are you sure you want to finish this course?', 'learn_press' ),
		'no_payment_method'         => __( 'Please select a payment method', 'learn_press' ),
		'you_are_instructor_now'    => __( 'You are an instructor now', 'learn_press' ),
		'quiz_time_is_over_message' => __( 'The time is over!', 'learn_press' ),
		'quiz_time_is_over_title'   => __( 'Time up!', 'learn_press' )
	);
	LPR_Assets::add_localize( $translate );
}

add_action( 'wp', 'learn_press_frontent_script' );
if ( !empty( $_REQUEST['payment_method'] ) ) {
	add_action( 'learn_press_frontend_action', array( 'LPR_AJAX', 'take_course' ) );
}

/**
 * Include js template
 */
function learn_press_admin_js_template() {
	if ( 'lpr_lesson' == get_post_type() ) {
		require_once LPR_PLUGIN_PATH . '/inc/lpr-js-template.php';
	}
}

add_action( 'admin_print_scripts', 'learn_press_admin_js_template' );

/**
 * Get related courses
 *
 * @param  int $number number of related courses you want to get
 *
 * @return array         id of related courses
 */
function learn_press_get_related_courses( $number ) {
	if ( is_single() && 'lpr_course' == get_post_type() ) {
		$course_id       = get_the_id();
		$terms           = get_the_terms( $course_id, 'course_category' );
		$related_courses = array();
		if ( $terms ) {
			$terms_args = array();
			foreach ( $terms as $term ) {
				array_push( $terms_args, $term->slug );
			}
			$args            = array(
				'post_type'      => 'lpr_course',
				'tax_query'      => array(
					array(
						'taxonomy' => 'course_category',
						'field'    => 'slug',
						'terms'    => $terms_args
					)
				),
				'posts_per_page' => $number,
			);
			$related_courses = get_posts( $args );
		} else {
			global $wpdb;
			$arr_query = array(
				'post_type'   => 'lpr_course',
				'post_status' => 'publish',
			);
			$courses   = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT p.ID, pm.meta_value FROM $wpdb->posts AS p
					INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
					WHERE pm.meta_key = %s",
					'_lpr_course_user'
				)
			);
			$course_in = array();
			if ( $courses ) {
				foreach ( $courses as $course ) {
					$course_in[$course->ID] = count( unserialize( $course->meta_value ) );
				}
				arsort( $course_in );
			}
			$courses               = array_slice( $course_in, 0, 4, true );
			$arr_query['post__in'] = array_keys( $courses );
			$related_courses       = get_posts( $arr_query );
		}
		return $related_courses;
	}
	return;
}

/**
 * tinymce options to process the keys user pressed
 *
 * @param  array
 *
 * @return array
 */
function learn_press_tiny_mce_before_init( $initArray ) {
	global $post_type;
	if ( !in_array( $post_type, array( 'lpr_lesson' ) ) ) return $initArray;
	$initArray['setup'] = <<<JS
[function(ed) {
    ed.on('keyup', function(e) {
        var ed = tinymce.activeEditor,
            c = window.char_code,
            ed = tinymce.activeEditor;
        if( c == undefined ) c = [];
        if( e.keyCode == 76 || e.keyCode == 50 ){
            c.push(e.keyCode);
            console.log( c )
            if(e.keyCode == 50){
                //ed.execCommand('mceInsertContent', false,'<span id="quick_add_link_bookmark"></span>');
            }else if( e.keyCode == 76 ){
                var a = c.pop(), b = c.pop();
                if( b != 50 ){
                    do{
                        b = c.pop();
                    }while( b == 16 )
                }
                if( b == 50 && a == 76 ){
                    LearnPress.showLessonQuiz(null, ed);
                }
                c = [];
            }
        }else{
            if( e.keyCode != 16 && jQuery.inArray(50, c) != -1 ){
                c = []
            }
        }
        window.char_code = c;
    });

}][0]
JS;
	return $initArray;
}

add_filter( 'tiny_mce_before_init', 'learn_press_tiny_mce_before_init' );

/**
 * Prevent access directly to lesson by using lesson permalink
 *
 * @since  0.9.5
 *
 * @param string
 *
 * @return $string
 */
function learn_press_prevent_access_lesson( $template ) {
	// if we are in single lesson page
	if ( is_single() && 'lpr_lesson' == get_post_type() ) {
		learn_press_404_page();
	}
	return $template;
}
add_filter( 'template_include', 'learn_press_prevent_access_lesson' );

/**
 * Check to see if user can view a lesson or not
 *
 * @since 0.9.5
 *
 * @param int $lesson_id
 * @param int $user_id
 *
 * @return boolean
 */
function learn_press_user_can_view_lesson( $lesson_id, $user_id = null ) {
	$return = false;
	if ( $user_id = null ) {
		$user_id = get_current_user_id();
	}

	$course_id = learn_press_get_course_by_lesson( $lesson_id );

	$enrolled_require = get_post_meta( $course_id, '_lpr_course_enrolled_require', true );

	// check enrolled require
	if ( !$enrolled_require || $enrolled_require == 'no' ) {
		$return = true;
	} elseif ( learn_press_is_lesson_preview( $lesson_id ) ) { // lesson can preview
		$return = true;
	} else {
		if ( learn_press_is_enrolled_course() ) { // user has enrolled course
			$return = true;
		}
	}

	return apply_filters( 'learn_press_user_can_view_lesson', $return, $lesson_id, $user_id );
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

// functions for anonymous user with quiz
//require_once( LPR_PLUGIN_PATH . '/inc/lpr-anonymous-user-quiz-functions.php' );

/**
 * Reset all data of a course including user, quiz, etc...
 *
 * @param $course_id
 */
function learn_press_reset_course_data( $course_id ) {

	if ( get_post_type( $course_id ) != 'lpr_course' ) return;
	$course_users = get_post_meta( $course_id, '_lpr_course_user', true );
	if ( is_array( $course_users ) ) {
		foreach ( $course_users as $user_id ) {
			$user_courses = get_user_meta( $user_id, '_lpr_user_course', true );
			if ( is_array( $user_courses ) && in_array( $course_id, $user_courses ) ) {
				unset( $user_courses[$course_id] );
			}
			if ( sizeof( $user_courses ) ) {
				update_user_meta( $user_id, '_lpr_user_course', $user_courses );
			} else {
				delete_user_meta( $user_id, '_lpr_user_course' );
			}
		}
	}
	$order_id = learn_press_get_course_order( $course_id );
	if ( $order_id ) {
		// delete order with it's meta data
		wp_delete_post( $order_id );
	}
	delete_post_meta( $course_id, '_lpr_course_user' );
}


/**
 * Reset all data of an user
 *
 * @param $course_id
 */
function learn_press_reset_user_data( $user_id ) {

	$use_courses = get_user_meta( $user_id, '_lpr_user_course', true );

	if ( is_array( $use_courses ) ) {
		foreach ( $use_courses as $course_id ) {
			$course_users = get_post_meta( $course_id, '_lpr_course_user', true );
			if ( is_array( $course_users ) && in_array( $user_id, $course_users ) ) {
				unset( $course_users[$user_id] );
			}
			if ( sizeof( $course_users ) ) {
				update_post_meta( $course_id, '_lpr_course_user', $course_users );
			} else {
				update_post_meta( $course_id, '_lpr_course_user' );
			}

			$order_id = learn_press_get_course_order( $course_id );
			if ( $order_id ) {
				// delete order with it's meta data
				wp_delete_post( $order_id );
			}
		}
	}
}
