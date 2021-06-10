<?php
/**
 * Common functions to manipulate with the quiz.
 *
 * @since 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

/**
 * Get quiz from anything is passed
 *
 * @param mixed $the_quiz
 *
 * @return bool|LP_Quiz
 */
function learn_press_get_quiz( $the_quiz ) {
	return LP_Quiz::get_quiz( $the_quiz );
}

/**
 * Get question from anything is passed
 *
 * @param mixed $the_question
 *
 * @return bool|LP_Question
 */
function learn_press_get_question( $the_question ) {
	return LP_Question::get_question( $the_question );
}

function learn_press_add_question_answer_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return add_metadata( 'learnpress_question_answer', $item_id, $meta_key, $meta_value, $prev_value );
}

function learn_press_update_question_answer_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'learnpress_question_answer', $item_id, $meta_key, $meta_value, $prev_value );
}

function learn_press_delete_question_answer_meta( $item_id, $meta_key, $meta_value, $delete_all = false ) {
	return delete_metadata( 'learnpress_question_answer', $item_id, $meta_key, $meta_value, $delete_all );
}

function learn_press_get_question_answer_meta( $item_id, $meta_key, $single = true ) {
	return get_metadata( 'learnpress_question_answer', $item_id, $meta_key, $single );
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
	if ( ! $quiz_id ) {
		$quiz_id = get_the_ID();
	}

	return ( $quiz = LP_Quiz::get_quiz( $quiz_id ) ) ? $quiz->get_questions() : false;
}

function learn_press_question_class( $question = null, $args = array() /*, $classes = null, $user_id = null, $context = null*/ ) {

	_deprecated_function( sprintf( '%s', __FUNCTION__ ), '4.0.0' );

	$course  = LP()->global['course'];
	$args    = wp_parse_args(
		$args,
		array(
			'user'    => LP()->user,
			'quiz'    => ! empty( LP()->global['course-item'] ) ? LP()->global['course-item'] : false,
			'classes' => '',
		)
	);
	$quiz    = $args['quiz'];
	$user    = $args['user'] ? $args['user'] : learn_press_get_current_user();
	$classes = $args['classes'];

	if ( ! $question ) {
		$question = LP_Question::get_question( get_the_ID() );
	} elseif ( is_numeric( $question ) ) {
		$question = LP_Question::get_question( $question );
	}
	if ( $question ) {
		if ( is_string( $classes ) ) {
			$classes = explode( ' ', $classes );
		}
		settype( $classes, 'array' );
		$classes = array_merge(
			$classes,
			array(
				'learn-press-question-wrap',
				"question-type-{$question->type}",
				"question-{$question->id}",
			)
		);
		if ( $quiz ) {
			$status = $user->get_quiz_status( $quiz->id );
		} else {
			$status = '';
		}
		if ( $status == 'completed' ) {
			$user_id     = learn_press_get_current_user_id();
			$user        = learn_press_get_user( $user_id );
			$answer_data = $user->get_answer_results( $question->id, $quiz->id );
			$classes[]   = 'question-results';
			if ( $user->is_answered_question( $question->id, $quiz->id ) ) {
				if ( $answer_data['correct'] ) {
					$classes[] = 'correct';
				} else {
					$classes[] = 'incorrect';
				}
			} else {
				$classes[] = 'skipped';
			}
		} elseif ( $status == 'started' && $question->id == $user->get_current_quiz_question( $quiz->id, $course->get_id() ) ) {
			$classes[] = 'current';
		}

		$classes = apply_filters( 'learn_press_question_class', $classes, $question, $quiz );
		$classes = array_unique( $classes );
		$classes = array_filter( $classes );
		echo 'class="' . join( ' ', $classes ) . '"';
	}
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
	if ( ! $id ) {
		global $quiz;
		$id = $quiz->id;
	}

	return $id;
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
	$user = learn_press_get_user( $user_id );

	if ( $user ) {
		return $user->has_completed_quiz( $quiz_id );
	}

	return false;
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
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	$quiz_id = $quiz_id ? $quiz_id : ( $quiz ? $quiz->id : 0 );
	if ( ! $quiz_id ) {
		return 0;
	}
	$meta            = get_user_meta( $user_id, '_lpr_quiz_start_time', true );
	$quiz_duration   = get_post_meta( $quiz_id, '_lpr_duration', true );
	$time_remaining  = $quiz_duration * 60;
	$quiz_start_time = ! empty( $meta[ $quiz_id ] ) ? $meta[ $quiz_id ] : 0;
	$quiz_start_time = apply_filters( 'learn_press_user_quiz_start_time', $quiz_start_time, $quiz_id, $user_id );

	if ( $quiz_duration && learn_press_user_has_started_quiz( $user_id, $quiz_id ) ) {
		$quiz_duration *= 60;
		$now            = current_time( 'timestamp' );

		if ( $now < $quiz_start_time + $quiz_duration ) {
			$time_remaining = $quiz_start_time + $quiz_duration - $now;
		} else {
			$time_remaining = 0;
		}
	}

	return apply_filters( 'learn_press_get_quiz_time_remaining', $time_remaining, $user_id, $quiz_id );
}


/**
 * Check if user has started a quiz or not.
 *
 * @param null $user_id
 * @param null $quiz_id
 *
 * @return bool|mixed
 * @throws Exception
 */
function learn_press_user_has_started_quiz( $user_id = null, $quiz_id = null ) {
	$user = $user_id ? learn_press_get_user( $user_id ) : learn_press_get_current_user();

	return $user ? $user->has_started_quiz( $quiz_id ) : false;
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
	$user = $user_id ? learn_press_get_user( $user_id ) : learn_press_get_current_user();

	return $user ? $user->get_quiz_status( $quiz_id ) : '';
}

function learn_press_get_quizzes( $user_id = 0, &$args = array() ) {
	// TODO: get all quizzes by user
}

/**
 * Add new type that LP question can support
 *
 * @param string|array $types
 * @param string|array $supports
 */
function learn_press_add_question_type_support( $types, $supports ) {
	if ( empty( $GLOBALS['learn_press_question_type_support'] ) ) {
		$GLOBALS['learn_press_question_type_support'] = array();
	}
	$_supports = $GLOBALS['learn_press_question_type_support'];
	settype( $types, 'array' );
	settype( $supports, 'array' );

	$types    = array_filter( $types );
	$supports = array_filter( $supports );

	if ( $types ) {
		foreach ( $types as $type ) {
			if ( ! $type ) {
				continue;
			}
			if ( empty( $_supports[ $type ] ) ) {
				$_supports[ $type ] = array();
			}
			if ( $supports ) {
				foreach ( $supports as $s ) {
					$_supports[ $type ][] = $s;
				}
			}
			$_supports[ $type ] = array_filter( $_supports[ $type ] );
		}
	}
	$GLOBALS['learn_press_question_type_support'] = $_supports;
}

/**
 * @param string $type
 *
 * @return array|mixed
 */
function learn_press_get_question_type_support( $type = '' ) {
	$supports = ! empty( $GLOBALS['learn_press_question_type_support'] ) ? $GLOBALS['learn_press_question_type_support'] : array();

	return $type && ! empty( $supports[ $type ] ) ? $supports[ $type ] : $supports;
}

/**
 * Check if a type question is supported.
 *
 * @param string $type
 *
 * @return bool
 */
function learn_press_is_support_question_type( $type ) {

	if ( is_array( $type ) ) {
		$type = reset( $type );
	}

	$supports = learn_press_get_question_type_support();

	// New type is supported?
	if ( $supports && ! array_key_exists( $type, $supports ) ) {
		return false;
	}

	return true;
}

function learn_press_question_type_support( $type, $features ) {
	settype( $features, 'array' );
	$features    = array_filter( $features );
	$supports    = learn_press_get_question_type_support( $type );
	$has_support = true;

	if ( $features ) {
		foreach ( $features as $feature ) {
			$has_support = $has_support && in_array( $feature, $supports );
		}
	}

	return $has_support;
}

function learn_press_default_question_type_support() {
	learn_press_add_question_type_support(
		array(
			'multi_choice',
			'single_choice',
			'true_or_false',
			'fill_in_blanks',
		),
		'check-answer'
	);
}

add_action( 'plugins_loaded', 'learn_press_default_question_type_support' );


if ( ! function_exists( 'learn_press_quiz_get_questions_order' ) ) {
	/**
	 * Get question order in quiz.
	 *
	 * @since 3.0.0
	 *
	 * @param array $questions
	 *
	 * @return array
	 */
	function learn_press_quiz_get_questions_order( $questions = array() ) {
		if ( ! $questions ) {
			return array();
		}

		global $wpdb;

		$ids = $orders = array();

		foreach ( $questions as $id => $question ) {
			$ids[] = $id;
		}

		$order = $wpdb->get_results( "SELECT q.question_id AS q_id, q.question_order AS q_order FROM $wpdb->learnpress_quiz_questions AS q", ARRAY_A );

		if ( $order ) {
			foreach ( $order as $id => $_order ) {
				$orders[ $_order['q_id'] ] = $_order['q_order'];
			}
		}

		return $orders;
	}
}

function learn_press_is_review_questions() {
	$item = LP_Global::course_item();
	$user = learn_press_get_current_user();

	if ( $item && $user ) {
		$quiz_data = $user->get_item_data( $item->get_id(), LP_Global::course( 'id' ) );

		return $quiz_data && $quiz_data->is_review_questions();
	}

	return false;
}



