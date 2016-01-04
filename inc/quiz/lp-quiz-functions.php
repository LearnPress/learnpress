<?php
/**
 * Common functions to manipulate with the quiz
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
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
	if( !$quiz_id ){
		$quiz_id = get_the_ID();
	}
	return ( $quiz = LP_Quiz::get_quiz( $quiz_id ) ) ? $quiz->get_questions() : false;
}


function learn_press_question_class( $question = null ){
	if( ! $question ){
		$question = LP_Question_Factory::get_question(get_the_ID() );
	}elseif( is_numeric( $question ) ){
		$question = LP_Question_Factory::get_question($question );
	}
	if( $question ) {
		$class = "learn-press-question-wrap question-type-{$question->type} question-{$question->id}";
		$class = apply_filters( 'learn_press_question_class', $class, $question );
		$class = explode( " ", $class );
		$class = array_unique( $class );
		echo "class=\"" . join( ' ', $class ) . "\"";
	}
}
function learn_press_add_user_quiz_meta( $quiz_user_id, $meta_key, $meta_value, $prev_value = '' ){
	return add_metadata( 'learnpress_user_quiz', $quiz_user_id, $meta_key, $meta_value, $prev_value );
}

function learn_press_update_user_quiz_meta( $quiz_user_id, $meta_key, $meta_value, $prev_value = '' ){
	return update_metadata( 'learnpress_user_quiz', $quiz_user_id, $meta_key, $meta_value, $prev_value );
}

/**
 * @param int
 * @param int - since 0.9.5
 *
 * @return bool|int
 */
function learn_press_get_current_question( $quiz_id = null, $user_id = 0 ) {
	if( $user_id ){
		$user = learn_press_get_user( $user_id );
	}else{
		$user = LP()->user;
	}
	return $user->get_current_question( $quiz_id );
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
	_deprecated_function( __FUNCTION__, '1.0', 'LP_User() -> has_completed_quiz' );

	if( $user = learn_press_get_user( $user_id ) ){
		return $user->has_completed_quiz( $quiz_id );
	}
	return false;
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

/*
 * Get question url in a quiz for user
 *
 * @param int the ID of a quiz
 * @param int the ID of question - default is current question of quiz user is doing
 * @param int the ID of user - default is current user
 *
 * @return string
 */
function learn_press_get_user_question_url( $quiz_id, $current_question_id = 0, $user_id = 0 ) {
	if ( !$current_question_id ) {
		$current_question_id = learn_press_get_current_question( $quiz_id, $user_id );
	}
	$permalink     = get_the_permalink( $quiz_id );
	$question_name = get_post_field( 'post_name', $current_question_id );
	if ( '' != get_option( 'permalink_structure' ) ) {
		$permalink .= get_post_field( 'post_name', $current_question_id );
	} else {
		$permalink .= ( strpos( $permalink, '?' ) === false ? "?" : "&" ) . "question={$question_name}";
	}
	return apply_filters( 'learn_press_quiz_question_url', $permalink, $quiz_id, $current_question_id, $user_id );
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
	$user = $user_id ? learn_press_get_user( $user_id ) : LP()->user;
	return $user ? $user->has( 'started-quiz', $quiz_id ) : false;
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
	$user = $user_id ? learn_press_get_user( $user_id ) : LP()->user;
	return $user ? $user->get_quiz_status( $quiz_id ) : '';
}

/**
 * Redirect to question if user access to a quiz that user has started
 *
 * @param string
 *
 * @return string
 */
function learn_press_redirect_to_question( $template ) {
	global $post_type;
	if ( is_single() && $post_type == LP()->quiz_post_type ) {
		$user        = learn_press_get_current_user();
		$quiz_id     = get_the_ID();
		$quiz_status = $user->get_quiz_status( $quiz_id );
		if ( $quiz_status == 'started' && learn_press_get_quiz_time_remaining( $user->id, $quiz_id ) == 0 && get_post_meta( $quiz_id, '_lpr_duration', true ) ) {
			$user->finish_quiz( $quiz_id );
			$quiz_status = 'completed';
		}
		if ( learn_press_get_request( 'question' ) && $quiz_status == '' ) {
			wp_redirect( get_the_permalink( $quiz_id ) );
		} elseif ( $quiz_status == 'started' ) {
			if ( learn_press_get_request( 'question' ) ) {
			} else {
				wp_redirect( learn_press_get_user_question_url( $quiz_id ) );
				die();
			}
		} elseif ( $quiz_status == 'completed' && learn_press_get_request( 'question' ) ) {
			wp_redirect( get_the_permalink( $quiz_id ) );
			die();
		}
	}
	return $template;
}
add_action( 'template_redirect', 'learn_press_redirect_to_question' );
