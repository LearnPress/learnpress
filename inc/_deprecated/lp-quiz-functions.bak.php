<?php
/**
 * THIS FILE CONTAINS THE FUNCTIONS OF OLD VERSION
 */
/**
 * Save the answer of current question in quiz
 *
 * @param $quiz_id
 * @param $question_id
 * @param $question_answer
 */
function lpr_save_question_answer( $quiz_id, $question_id, $question_answer ) {
	$user_id        = get_current_user_id();
	$student_answer = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );

	if ( !isset( $student_answer ) || !is_array( $student_answer ) ) {
		$student_answer = array();
	}
	if ( !isset( $student_answer[$quiz_id] ) || !is_array( $student_answer[$quiz_id] ) ) {
		$student_answer[$quiz_id] = array();
	}
	$student_answer[$quiz_id][$question_id] = $question_answer;
	update_user_meta( $user_id, '_lpr_quiz_question_answer', $student_answer );
}

/**
 * Check to see if the user is answered a question of a quiz or not
 *
 * @param $quiz_id
 * @param $question_id
 *
 * @return bool
 */
function lpr_check_is_question_answered( $quiz_id, $question_id ) {
	$user_id        = get_current_user_id();
	$student_answer = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );

	if ( !isset( $student_answer ) || !is_array( $student_answer ) ) {
		return false;
	}

	if ( empty( $student_answer[$quiz_id] ) ) return false;
	if ( !array_key_exists( $question_id, $student_answer[$quiz_id] ) || $student_answer[$quiz_id][$question_id] == '' ) {
		return false;
	}
	return true;
}

/**
 * @param $quiz_id
 * @param $question_id
 *
 * @return mixed|void
 */
function lpr_get_question_answer( $quiz_id, $question_id ) {
	$user_id        = get_current_user_id();
	$student_answer = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );
	if ( isset( $student_answer[$quiz_id] ) ) {
		if ( isset( $student_answer[$quiz_id][$question_id] ) )
			if ( $student_answer[$quiz_id][$question_id] != '' )
				return $student_answer[$quiz_id][$question_id];
	}
	return apply_filters( 'lpr_get_question_answer', __( 'You have not saved answer for this question yet', 'learn_press' ) );
}

/**
 * @param $quiz_id
 */
function lpr_reset_quiz_answer( $quiz_id ) {

	$user_id        = get_current_user_id();
	$student_answer = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );

	if ( isset( $student_answer[$quiz_id] ) ) {
		$questions = get_post_meta( $quiz_id, '_lpr_quiz_questions', true );
		if ( is_array( $questions ) ) {
			foreach ( $questions as $question ) {
				$student_answer[$quiz_id][$question] = '';
			}
		}
	}
}

/**
 * @param $quiz_id
 * @param $result
 */
function lpr_save_quiz_result( $quiz_id, $result ) {
	$user_id     = get_current_user_id();
	$quiz_result = get_user_meta( $user_id, '_lpr_quiz_completed', true );
	if ( !isset( $quiz_result ) || !is_array( $quiz_result ) ) {
		$quiz_result           = array();
		$quiz_result[$quiz_id] = array();
	} else {
		if ( !isset( $quiz_result ) || !is_array( $quiz_result ) ) {
			$quiz_result[$quiz_id] = array();
		}
	}
	array_push( $result, $quiz_result[$quiz_id] );
}

/**
 * @param $quiz_id
 *
 * @return mixed|void
 */
function lpr_get_quiz_result( $quiz_id ) {
	$user_id     = get_current_user_id();
	$quiz_result = get_user_meta( $user_id, '_lpr_quiz_completed', true );
	if ( $quiz_result ) {
		if ( isset( $quiz_result[$quiz_id] ) && is_array( $quiz_result[$quiz_id] ) ) {
			$count = sizeof( $quiz_result[$quiz_id] );
			return apply_filters( 'lpr_get_quiz_result', $quiz_result[$quiz_id][$count - 1] );
		}
	}
	return ( apply_filters( 'lpr_get_quiz_result', 'Have no quiz result' ) );
}


//learn_press_user_can_view_quiz()


add_filter( 'lpr_take_course_page', 'lpr_course_time_check', 10, 2 );
function lpr_course_time_check( $link, $course_id ) {
	$start_date = get_post_meta( $course_id, '_lpr_start_date', true );
	if ( isset( $start_date ) && strtotime( $start_date ) > current_time( 'timestamp' ) ) {
		return '#';
	}

	return $link;
}

function learn_press_reset_user_quiz( $user_id = null, $quiz_id = null ) {
	if ( empty( $user_id ) ) $user_id = get_current_user_id();
	$quiz_id = learn_press_get_quiz_id( $quiz_id );
	if ( !apply_filters( 'learn_press_reset_user_quiz', true, $quiz_id, $user_id ) ) {
		return;
	}

	$keys = array(
		'_lpr_quiz_start_time',
		'_lpr_quiz_completed',
		'_lpr_question_answer',
		'_lpr_quiz_questions',
		'_lpr_quiz_question_answer',
		'_lpr_quiz_current_question'
	);
	foreach ( $keys as $meta_key ) {
		$meta = get_user_meta( $user_id, $meta_key, true );
		if ( !empty( $meta[$quiz_id] ) ) {
			unset( $meta[$quiz_id] );
			if ( count( $meta ) ) {
				update_user_meta( $user_id, $meta_key, $meta );
			} else {
				delete_user_meta( $user_id, $meta_key );
			}
		}
	}
	if ( $course_id = learn_press_is_final_quiz( $quiz_id ) ) {
		$finished_courses = get_user_meta( $user_id, '_lpr_course_finished', true );
		if ( is_array( $finished_courses ) && ( $pos = array_search( $course_id, $finished_courses ) ) !== false ) {
			unset( $finished_courses[$pos] );
			update_user_meta( $user_id, '_lpr_course_finished', $finished_courses );
		}

		$user_finished = get_post_meta( $course_id, '_lpr_user_finished', true );
		if ( $user_finished ) {
			if ( false !== ( $position = array_search( $user_id, $user_finished ) ) ) {
				unset( $user_finished[$position] );
				update_post_meta( $course_id, '_lpr_user_finished', $user_finished );
			}
		}
	}
	do_action( 'learn_press_after_reset_user_quiz', $quiz_id, $user_id );
}

function learn_press_is_final_quiz( $quiz_id ) {
	$course_id = learn_press_get_course_by_quiz( $quiz_id );
	if ( lpr_get_final_quiz( $course_id ) == $quiz_id ) {
		return $course_id;
	}
	return false;
}

function learn_press_update_quiz_time() {
	global $post_type;
	if ( is_single() && LP()->quiz_post_type == $post_type ) {
		global $quiz;
		$user_id     = get_current_user_id();
		$retake_quiz = !empty( $_REQUEST['retake_quiz'] ) ? $_REQUEST['retake_quiz'] : 0;
		if ( $retake_quiz && learn_press_user_can_retake_quiz( $quiz->id, $user_id ) ) {
			learn_press_reset_user_quiz( $user_id, $quiz->id );
			//wp_redirect( get_permalink( $quiz->ID ) );
		}
	}
}

if ( !function_exists( 'learn_press_setup_quiz_data' ) ) {
	/**
	 * setup quiz data if we see an ID or slug of a quiz in request params
	 *
	 * @author  TuNN
	 *
	 * @param   int|string $quiz_id_variable ID or slug of a quiz
	 * @param              string            The name of global variable to set
	 *
	 * @return  object|null
	 */
	function learn_press_setup_quiz_data( $quiz_id_variable = 'quiz_id', $global_variable = 'quiz' ) {
		return;
		global $post, $post_type;
		$quiz = false;
		// set quiz variable to a post if we are in a single quiz

		if ( is_single() && LP()->quiz_post_type == $post_type ) {
			$quiz = $post;
		} else {
			if ( !empty( $_REQUEST[$quiz_id_variable] ) ) {

				if ( isset( $GLOBALS[$global_variable] ) ) unset( $GLOBALS[$global_variable] );
				$quiz_id = $_REQUEST[$quiz_id_variable];

				// if the variable is a numeric we consider it is an ID
				if ( is_numeric( $quiz_id ) ) {
					$quiz = get_post( $quiz_id );

				} else { // otherwise it is a slug
					$quiz = get_posts(
						array(
							'name'      => $quiz_id,
							'post_type' => LP()->quiz_post_type
						)
					);
					if ( is_array( $quiz ) ) {
						$quiz = array_shift( $quiz );
					}
				}
			}
		}
		if ( $quiz ) {
			$GLOBALS[$global_variable] = $quiz;
		}
		return $quiz;
	}
}



/**
 * Get the permalink of a question with the quiz that contains the question
 *
 * @param int $quiz_id
 * @param int $current_question_id - optional
 * @param int $user_id             - option
 *
 * @return string
 */
function learn_press_get_user_prev_question_url( $quiz_id, $current_question_id = 0, $user_id = 0 ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	if ( !$current_question_id ) {
		if ( $current_questions = get_user_meta( $user_id, '_lpr_quiz_current_question', true ) ) {
			$current_question_id = !empty( $current_questions[$quiz_id] ) ? $current_questions[$quiz_id] : 0;
		}
	}
	$prev_id = learn_press_get_prev_question( $current_question_id, $quiz_id, $user_id );
	if ( $prev_id ) {
		$permalink     = get_the_permalink( $quiz_id );
		$question_name = get_post_field( 'post_name', $prev_id );
		if ( '' != get_option( 'permalink_structure' ) ) {
			$permalink .= get_post_field( 'post_name', $prev_id );
		} else {
			$permalink .= ( strpos( $permalink, '?' ) === false ? "?" : "&" ) . "question={$question_name}";
		}
	} else {
		$permalink = '';
	}
	return $permalink;
}

/**
 * Get the permalink of a question with the quiz that contains the question
 *
 * @param int $quiz_id
 * @param int $current_question_id - optional
 * @param int $user_id             - option
 *
 * @return string
 */
function learn_press_get_user_next_question_url( $quiz_id, $current_question_id = 0, $user_id = 0 ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	if ( !$current_question_id ) {
		if ( $current_questions = get_user_meta( $user_id, '_lpr_quiz_current_question', true ) ) {
			$current_question_id = !empty( $current_questions[$quiz_id] ) ? $current_questions[$quiz_id] : 0;
		}
	}
	$next_id = learn_press_get_next_question( $current_question_id, $quiz_id, $user_id );
	if ( $next_id ) {
		$permalink     = get_the_permalink( $quiz_id );
		$question_name = get_post_field( 'post_name', $next_id );
		if ( '' != get_option( 'permalink_structure' ) ) {
			$permalink .= get_post_field( 'post_name', $next_id );
		} else {
			$permalink .= ( strpos( $permalink, '?' ) === false ? "?" : "&" ) . "question={$question_name}";
		}
	} else {
		$permalink = '';
	}

	return apply_filters( 'learn_press_get_user_next_question_url', $permalink, $quiz_id, $current_question_id, $user_id );
}

function learn_press_get_next_question( $current_question_id, $quiz_id = false, $user_id = false ) {
	if ( !$quiz_id ) {
		$quiz_id = get_the_ID();
		if ( get_post_type( $quiz_id ) != LP()->quiz_post_type ) {
			return false;
		}
	}
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$next_id        = false;
	$quiz_questions = learn_press_get_user_quiz_questions( $quiz_id, $user_id );
	if ( ( is_array( $quiz_questions ) && ( $question_pos = array_search( $current_question_id, $quiz_questions ) ) !== false ) ) {
		$next_id = $question_pos < sizeof( $quiz_questions ) - 1 ? $quiz_questions[$question_pos + 1] : false;
	}
	return $next_id;
}

function learn_press_get_prev_question( $current_question_id, $quiz_id = false, $user_id = false ) {
	if ( !$quiz_id ) {
		$quiz_id = get_the_ID();
		if ( get_post_type( $quiz_id ) != LP()->quiz_post_type ) {
			return false;
		}
	}
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$quiz_questions = learn_press_get_user_quiz_questions( $quiz_id, $user_id );
	$prev_id        = false;
	if ( ( $question_pos = array_search( $current_question_id, $quiz_questions ) ) !== false ) {
		$prev_id = $question_pos > 0 ? $quiz_questions[$question_pos - 1] : false;
	}
	return apply_filters( 'learn_press_get_prev_question', $prev_id, $current_question_id, $quiz_id, $user_id );
}

function learn_press_get_user_quiz_questions( $quiz_id = null, $user_id = null ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	if ( !$quiz_id ) {
		$quiz_id = get_the_ID();
		if ( get_post_type( $quiz_id ) != LP()->quiz_post_type ) {
			return false;
		}
	}

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
	$quiz_questions = array_unique( array_merge( $user_quiz_questions, $quiz_questions ) );

	return apply_filters( 'learn_press_get_user_quiz_questions', $quiz_questions, $quiz_id, $user_id );
}