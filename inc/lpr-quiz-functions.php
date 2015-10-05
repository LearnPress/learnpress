<?php
/**
 * Common functions to manipulate with the quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Save the answer of current question in quiz
 *
 * @param $quiz_id
 * @param $question_id
 * @param $question_answer
 */
function lpr_save_question_answer( $quiz_id, $question_id, $question_answer ) {
    $user_id            = get_current_user_id();
    $student_answer     = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );

    if( !isset( $student_answer ) || !is_array( $student_answer ) ) {
        $student_answer = array();
    }
    if( !isset( $student_answer[$quiz_id] ) || !is_array( $student_answer[$quiz_id] ) ){
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
 * @return bool
 */
function lpr_check_is_question_answered( $quiz_id, $question_id ) {
    $user_id = get_current_user_id();
    $student_answer = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );

    if( ! isset( $student_answer ) || ! is_array( $student_answer ) ) {
        return false;
    }

    if( empty( $student_answer[ $quiz_id ] ) ) return false;
    if( ! array_key_exists( $question_id, $student_answer[ $quiz_id ] ) || $student_answer[$quiz_id][$question_id] == '' ) {
        return false;
    }
    return true;
}

/**
 * @param $quiz_id
 * @param $question_id
 * @return mixed|void
 */
function lpr_get_question_answer( $quiz_id, $question_id ) {
    $user_id = get_current_user_id();
    $student_answer = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );
    if( isset($student_answer[$quiz_id]) ) {
        if( isset($student_answer[$quiz_id][$question_id]) )
            if( $student_answer[$quiz_id][$question_id] != '' )
                return $student_answer[$quiz_id][$question_id];
    }
    return apply_filters( 'lpr_get_question_answer', __('You have not saved answer for this question yet', 'learn_press') );
}

/**
 * @param $quiz_id
 */
function lpr_reset_quiz_answer( $quiz_id ) {

    $user_id = get_current_user_id();
    $student_answer = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );

    if( isset( $student_answer[$quiz_id] ) ) {
        $questions = get_post_meta( $quiz_id, '_lpr_quiz_questions', true );
        if( is_array( $questions ) ) {
            foreach( $questions as $question ) {
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
    $user_id = get_current_user_id();
    $quiz_result = get_user_meta( $user_id, '_lpr_quiz_completed', true );
    if( !isset($quiz_result) || !is_array($quiz_result)  ) {
        $quiz_result= array();
        $quiz_result[$quiz_id] = array();
    } else {
        if( !isset($quiz_result) || !is_array($quiz_result) ) {
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
function lpr_get_quiz_result( $quiz_id ){
    $user_id = get_current_user_id();
    $quiz_result = get_user_meta( $user_id, '_lpr_quiz_completed', true );
    if( $quiz_result ) {
        if( isset($quiz_result[$quiz_id]) && is_array($quiz_result[$quiz_id])) {
            $count = sizeof($quiz_result[$quiz_id]);
            return apply_filters( 'lpr_get_quiz_result', $quiz_result[$quiz_id][$count-1] );
        }
    }
    return ( apply_filters( 'lpr_get_quiz_result', 'Have no quiz result' ) );
}

add_action( 'lpr_before_single_assignment', 'lpr_assignment_start' );
function lpr_assignment_start() {
    global $post;
    if ( !isset( $_POST['security'] ) ) {
        return;
    }

    if ( !isset( $_POST['security'] ) || !wp_verify_nonce( $_POST['security'], 'assignment' . $post->ID ) ) {
        wp_die( __( 'Security check failed !', 'learn_press' ) );
        exit;
    }

    $user_id          = get_current_user_id();
    $assignment_taken = get_user_meta( $user_id, $post->ID, true );
    if ( isset( $_POST['start_assignment'] ) ) {

        if ( add_user_meta( $user_id, $post->ID, time() ) ) {
            return;
        } else {
            wp_die( __( 'Assignment can not be re-started', 'learn_press' ) );
        }
    }
    if ( isset( $_POST['continue_assignment'] ) ) { //Added Security measure, if someone renames the hidden field and submits
        $start_time                    = get_user_meta( $user_id, $post->ID, true );
        $time                          = get_post_meta( $post->ID, '_lpr_assignment_deadline' );
        $assignment_duration_parameter = apply_filters( 'lpr_assignment_duration_parameter', 86400 );
        $time_limit                    = intval( $start_time ) + intval( $time ) * $assignment_duration_parameter;
        if ( $time_limit > time() ) {
            return;
        } else {
            wp_die( __( 'TIME EXPIRED, PLEASE SUBMIT THE ASSIGNMENT', 'learn_press' ) );
        }

    }
    if ( isset( $_POST['submit_assignment'] ) ) {
        if ( add_post_meta( $post->ID, $user_id, 0 ) ) {

            if ( function_exists( 'messages_new_message' ) ) {
                $message = __( 'Assignment ', 'learn_press' ) . get_the_title( $post->ID ) . __( ' submitted by student ', 'learn_press' ) . get_edit_user_link( $user_id );
                messages_new_message( array(
                    'sender_id'  => $user_id,
                    'subject'    => __( 'Assignment submitted by Student', 'learn_press' ),
                    'content'    => $message,
                    'recipients' => $post->post_author
                ) );
            }

            return;
        }

        return;
    }
}

function assignment_start_button() { // Check on Start Values
    global $post;

    if ( !is_user_logged_in() ) {
        return;
    }

    $user_id          = get_current_user_id();
    $flag             = 1;
    $connected_course = get_post_meta( $post->ID, '_lpr_assignment_course', true );

    if ( isset( $connected_course ) && is_numeric( $connected_course ) ) {
        $expiry = get_user_meta( $user_id, $connected_course, true );
        if ( isset( $expiry ) && is_numeric( $expiry ) ) {
            if ( $expiry < time() ) {
                $flag = 0;
            }
        } else {
            $flag = 0;
        }
    }
    if ( $flag ) {
        echo '<form method="post">
              <input type="submit" name="start_assignment" class="btn btn-primary" value="' . __( 'START ASSIGNMENT', 'learn_press' ) . '" />';
        wp_nonce_field( 'assignment' . $post->ID, 'security' );
        echo '</form>';
    }
}

function assignment_continue_button() { // Check on Start Values
    global $post;
    if ( !is_user_logged_in() ) {
        return;
    }

    echo '<form method="post">
              <input type="submit" name="continue_assignment" class="btn btn-primary" value="' . __( 'CONTINUE ASSIGNMENT', 'learn_press' ) . '" />';
    wp_nonce_field( 'assignment' . $post->ID, 'security' );
    echo '</form>';
}

function assignment_results_button() {
//	echo '<a href="course/course-results/?action=' . get_the_ID() . '" class="btn btn-primary">' . __( 'CHECK RESULTS', 'learn_press' ) . '</a>';

    return;
}

function assignment_submit_button() {
    global $post;
    if ( !is_user_logged_in() ) {
        return;
    }

    $user_id = get_current_user_id();

    $evaluation = get_post_meta( $post->ID, '_lpr_assignment_evaluation', true );

    if ( isset( $evaluation ) ) {

        $course         = get_post_meta( $post->ID, '_lpr_assignment_course', true );
        $course_started = get_user_meta( $user_id, $course, true ); // Check if Connected course is started
        if ( isset( $course_started ) && $course_started > time() ) { // Course is Still active

            echo '<form method="post">
                  <input type="submit" name="submit_assignment" class="submit_assignment btn btn-primary" value="' . __( 'SUBMIT ASSIGNMENT', 'learn_press' ) . '" />';
            wp_nonce_field( 'assignment' . $post->ID, 'security' );
            echo '</form>';

        } else {
            echo '<a href="' . get_permalink( $course ) . '" class="btn btn-default full">' . __( 'INACTIVE COURSE ', 'learn_press' ) . '</a>';
        }
    } else {
        echo '<form method="post">
              <input type="submit" name="submit_assignment" class="btn btn-primary full" value="' . __( 'SUBMIT ASSIGNMENT', 'learn_press' ) . '" />';
        wp_nonce_field( 'assignment' . $post->ID, 'security' );
        echo '</form>';
    }
}

add_action( 'wp_ajax_clear_previous_submissions', 'clear_previous_submissions' );
function clear_previous_submissions() {
    global $wpdb, $post;
    $user_id = get_current_user_id();

    if ( isset( $_POST['security'] ) && wp_verify_nonce( $_POST['security'], 'user' . $user_id ) ) {
        $id = intval( $_POST['id'] );

        if ( get_post_type( $id ) != LPR_ASSIGNMENT_CPT ) {
            echo __( 'Invalid ID', 'learn_press' );
            die();
        }
        $rows = $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->comments SET comment_approved='trash' WHERE comment_post_ID=%d AND user_id=%d", $id, $user_id ) );
        echo $rows . __( ' previous submissions removed ! Reloading page ...', 'learn_press' );
    } else {
        echo __( 'Unable to remove previous submissions', 'learn_press' );
    }

    die();
}

if ( !function_exists( 'the_assignment_timer' ) ) {
    function the_assignment_timer( $hours, $start = null ) {
        global $post;

        $user_id                       = get_current_user_id();
        $assignment_taken              = get_user_meta( $user_id, $post->ID, true );
        $assignment_duration_parameter = apply_filters( 'lpr_assignment_duration_parameter', 86400 );

        $duration   = get_post_meta( $post->ID, '_lpr_assignment_duration', true );
        $time_limit = $assignment_taken + $duration * $assignment_duration_parameter;

        echo $assignment_taken;

        $remain_time = $time_limit - time();

//		if ( isset( $assignment_taken ) && $assignment_taken != '' ) {
//			if ( ( $assignment_taken + $hours * $assignment_duration_parameter ) > time() ) {
//				$start     = 1;
//				$remaining = ( $assignment_taken + $hours ) - time();
//			} else {
//				$remaining = 1;
//			}
//
//		} else {
//			if ( ! isset( $hours ) ) {
//				$hours = intval( get_post_meta( $post->ID, '_lpr_assignment_duration', true ) );
//			}
//
//			if ( ! $hours ) {
//				$hours = 1;
//				echo "Duration not Set";
//			}
//
//			$remaining = $hours; // Converting to seconds
//		}
//
//		$status = get_post_meta( $post->ID, $user_id, true );
//		if ( isset( $status ) && $status ) {
//			$start     = 0;
//			$remaining = 0;
//		}

//		if ( $remaining > 86400 ) {
//			echo '<div class="assignment_timer ' . ( ( $start ) ? 'start' : '' ) . '" data-time="' . $remaining . '">
//          <span class="timer" data-timer="' . $remaining . '"></span>
//          <span class="counttime">' . floor( $remaining / 86400 ) . '</span>
//          <span>' . __( 'Time Remaining', 'learn_press' ) . '</span>
//          <span>' . __( 'Days', 'learn_press' ) . '</span>
//          </div>';
//		} else {
//		echo $remain_time;
//		echo '<div class="assignment_timer ' . ( ( $start ) ? 'start' : '' ) . '" data-time="' . $remain_time . '">
//          <span class="timer" data-timer="' . $remain_time . '"></span>
//          <span class="countdown">' . seconds_to_hoursminutes( $remain_time ) . '</span>
//          <span>' . __( 'Time Remaining', 'learn_press' ) . '</span>
//          <span><strong>' . __( 'Hours', 'learn_press' ) . '</strong> ' . __( 'Minutes', 'learn_press' ) . '</span>
//          </div>';
////		}
    }
}

if ( !function_exists( 'seconds_to_hoursminutes' ) ) {
    function seconds_to_hoursminutes( $sec ) {
        if ( $sec > 3600 ) {
            $hours = floor( $sec / 3600 );
            $mins  = floor( ( $sec % 3600 ) / 60 );
            if ( $mins < 10 ) {
                $mins = '0' . $mins;
            }

            return $hours . ':' . $mins;
        } else {
            $mins = $sec;

            return '00:' . floor( $mins / 60 );
        }
    }
}


//////

if ( !function_exists( 'the_quiz' ) ) {
    function the_quiz( $args = null ) {

        $defaults = array(
            'quiz_id' => get_the_ID(),
            'ques_id' => ''
        );

        $r = wp_parse_args( $args, $defaults );
        extract( $r, EXTR_SKIP );

        $user_id = get_current_user_id();

        $questions = ( get_post_meta( $quiz_id, '_lpr_quiz_questions' . $user_id, false ) );
        if ( !isset( $questions ) || !is_array( $questions ) ) // Fallback for Older versions
        {
            $questions = ( get_post_meta( $quiz_id, '_lpr_quiz_questions', false ) );
        }

        if ( isset( $questions['ques'] ) && is_array( $questions['ques'] ) ) {
            $key = array_search( $ques_id, $questions['ques'] );
        }

        if ( $ques_id ) {
            $the_query = new WP_Query( array(
                'post_type' => 'question',
                'p'         => $ques_id
            ) );
            while ( $the_query->have_posts() ) : $the_query->the_post();
                the_question();

                echo '<div class="quiz_bar">';
                if ( $key == 0 ) { // FIRST QUESTION
                    if ( $key != ( count( $questions['ques'] ) - 1 ) ) // First But not the Last
                    {
                        echo '<a href="#" class="ques_link right quiz_question nextq" data-quiz="' . $quiz_id . '" data-qid="' . $questions['ques'][( $key + 1 )] . '">' . __( 'Next Question', 'learn_press' ) . ' &rsaquo;</a>';
                    }

                } elseif ( $key == ( count( $questions['ques'] ) - 1 ) ) { // LAST QUESTION

                    echo '<a href="#" class="ques_link left quiz_question prevq" data-quiz="' . $quiz_id . '" data-qid="' . $questions['ques'][( $key - 1 )] . '">&lsaquo; ' . __( 'Previous Question', 'learn_press' ) . '</a>';

                } else {
                    echo '<a href="#" class="ques_link left quiz_question prevq" data-quiz="' . $quiz_id . '" data-qid="' . $questions['ques'][( $key - 1 )] . '">&lsaquo; ' . __( 'Previous Question', 'learn_press' ) . '</a>';
                    echo '<a href="#" class="ques_link right quiz_question nextq" data-quiz="' . $quiz_id . '" data-qid="' . $questions['ques'][( $key + 1 )] . '">' . __( 'Next Question', 'learn_press' ) . ' &rsaquo;</a>';
                }

                echo '</div>';
            endwhile;
            wp_reset_postdata();
        } else {

            $quiz_taken = get_user_meta( $user_id, $quiz_id, true );

            if ( isset( $quiz_taken ) && $quiz_taken && ( $quiz_taken < time() ) ) {

                $message = get_post_meta( $quiz_id, '_lpr_quiz_message', true );
                echo '<div class="main_content">';
                echo $message;
                echo '</div>';
            } else {
                echo '<div class="main_content">';
                the_content();
                echo '</div>';
            }
        }
    }
}

if ( !function_exists( 'the_quiz_timer' ) ) {
    function the_quiz_timer( $start ) {
        global $post;

        $user_id   = get_current_user_id();
        $quiztaken = get_user_meta( $user_id, get_the_ID(), true );

        $minutes = intval( get_post_meta( $post->ID, '_lpr_duration', true ) );

        if ( $minutes > 9998 ) {
            return true;
        }

        if ( isset( $quiztaken ) && is_numeric( $quiztaken ) && $quiztaken ) {
            if ( $quiztaken > time() ) {
                $minutes = $quiztaken - time();
                $start   = 1;
            } else {
                $minutes = 1;
            }
        } else {
            if ( !$minutes ) {
                $minutes = 1;
                echo __( "Duration not Set", "learn_press" );
            } else {
                $start = 0;
            }
            $quiz_duration_parameter = apply_filters( 'lpr_quiz_duration_parameter', 60 );
            $minutes                 = $minutes * $quiz_duration_parameter;
        }


        echo '<div class="quiz_timer ' . ( ( $start ) ? 'start' : '' ) . '" data-time="' . $minutes . '">
      <span class="timer" data-timer="' . $minutes . '"></span>
      <span class="countdown">' . minutes_to_hms( $minutes ) . '</span>
      <span>' . __( 'Time Remaining', 'learn_press' ) . '</span>
      <span><strong>' . __( 'Mins', 'learn_press' ) . '</strong> ' . __( 'Secs', 'learn_press' ) . '</span>
      </div>';

    }
}

add_filter( 'lpr_take_course_page', 'lpr_course_time_check', 10, 2 );
function lpr_course_time_check( $link, $course_id ) {
    $start_date = get_post_meta( $course_id, '_lpr_start_date', true );
    if ( isset( $start_date ) && strtotime( $start_date ) > time() ) {
        return '#';
    }

    return $link;
}

function learn_press_reset_user_quiz($user_id = null, $quiz_id = null){
    if( empty( $user_id ) ) $user_id = get_current_user_id();
    $quiz_id = learn_press_get_quiz_id($quiz_id);
    if( ! apply_filters( 'learn_press_reset_user_quiz', true, $quiz_id, $user_id ) ){
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
    foreach ($keys as $meta_key) {
        $meta = get_user_meta( $user_id, $meta_key, true );
        if( !empty( $meta[$quiz_id] ) ){
            unset( $meta[$quiz_id] );
            if( count( $meta ) ) {
                update_user_meta($user_id, $meta_key, $meta );
            }else{
                delete_user_meta($user_id, $meta_key);
            }
        }
    }
    if( $course_id = learn_press_is_final_quiz( $quiz_id ) ){
        $finished_courses = get_user_meta( $user_id, '_lpr_course_finished', true );
        if( is_array( $finished_courses ) && ( $pos = array_search( $course_id, $finished_courses ) ) !== false ){
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
    if( lpr_get_final_quiz( $course_id ) == $quiz_id ){
        return $course_id;
    }
    return false;
}

function learn_press_update_quiz_time(){
    global $post_type;
    if( is_single() && 'lpr_quiz' == $post_type ) {
        global $quiz;
        $user_id = get_current_user_id();
        $retake_quiz = !empty($_REQUEST['retake_quiz']) ? $_REQUEST['retake_quiz'] : 0;
        if ($retake_quiz && learn_press_user_can_retake_quiz( $quiz->ID, $user_id ) ) {
            learn_press_reset_user_quiz( $user_id, $quiz->ID );
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
     * @param   string The name of global variable to set
     *
     * @return  object|null
     */
    function learn_press_setup_quiz_data( $quiz_id_variable = 'quiz_id', $global_variable = 'quiz' ) {
        global $post, $post_type;
        $quiz = false;
        // set quiz variable to a post if we are in a single quiz

        if ( is_single() && 'lpr_quiz' == $post_type ) {
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
                            'post_type' => 'lpr_quiz'
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

function learn_press_get_user_question_url( $quiz_id, $current_question_id = 0, $user_id = 0 ){
    if( ! $current_question_id ){
        $current_question_id = learn_press_get_current_question( $quiz_id, $user_id );
    }
    $permalink = get_the_permalink( $quiz_id );
    $question_name = get_post_field('post_name', $current_question_id);
    if( '' != get_option( 'permalink_structure' ) ) {
        $permalink .= get_post_field('post_name', $current_question_id);
    }else{
        $permalink .= ( strpos( $permalink, '?' ) === false ? "?" : "&" ) . "question={$question_name}";
    }
    return $permalink;
}
/**
 * Get the permalink of a question with the quiz that contains the question
 *
 * @param int $quiz_id
 * @param int $current_question_id - optional
 * @param int $user_id - option
 * @return string
 */
function learn_press_get_user_prev_question_url( $quiz_id, $current_question_id = 0, $user_id = 0 ){
    if( ! $user_id ){
        $user_id = get_current_user_id();
    }
    if( ! $current_question_id ){
        if( $current_questions = get_user_meta( $user_id, '_lpr_quiz_current_question', true ) ){
            $current_question_id = ! empty( $current_questions[ $quiz_id ] ) ? $current_questions[ $quiz_id ] : 0;
        }
    }
    $prev_id = learn_press_get_prev_question( $current_question_id, $quiz_id, $user_id );
    if( $prev_id ){
        $permalink = get_the_permalink( $quiz_id );
        $question_name = get_post_field('post_name', $prev_id);
        if( '' != get_option( 'permalink_structure' ) ) {
            $permalink .= get_post_field('post_name', $prev_id);
        }else{
            $permalink .= ( strpos( $permalink, '?' ) === false ? "?" : "&" ) . "question={$question_name}";
        }
    }else{
        $permalink = '';
    }
    return $permalink;
}

/**
 * Get the permalink of a question with the quiz that contains the question
 *
 * @param int $quiz_id
 * @param int $current_question_id - optional
 * @param int $user_id - option
 * @return string
 */
function learn_press_get_user_next_question_url( $quiz_id, $current_question_id = 0, $user_id = 0 ){
    if( ! $user_id ){
        $user_id = get_current_user_id();
    }
    if( ! $current_question_id ){
        if( $current_questions = get_user_meta( $user_id, '_lpr_quiz_current_question', true ) ){
            $current_question_id = ! empty( $current_questions[ $quiz_id ] ) ? $current_questions[ $quiz_id ] : 0;
        }
    }
    $next_id = learn_press_get_next_question( $current_question_id, $quiz_id, $user_id );

    if( $next_id ){
        $permalink = get_the_permalink( $quiz_id );
        $question_name = get_post_field('post_name', $next_id);
        if( '' != get_option( 'permalink_structure' ) ) {
            $permalink .= get_post_field('post_name', $next_id);
        }else{
            $permalink .= ( strpos( $permalink, '?' ) === false ? "?" : "&" ) . "question={$question_name}";
        }
    }else{
        $permalink = '';
    }

    return apply_filters( 'learn_press_get_user_next_question_url', $permalink, $quiz_id, $current_question_id , $user_id );
}

function learn_press_get_next_question( $current_question_id, $quiz_id = false, $user_id = false ){
    if( ! $quiz_id ){
        $quiz_id = get_the_ID();
        if( get_post_type( $quiz_id ) != 'lpr_quiz' ){
            return false;
        }
    }
    if( ! $user_id ){
        $user_id = get_current_user_id();
    }
    $next_id = false;
    $quiz_questions = learn_press_get_user_quiz_questions( $quiz_id, $user_id );
    if( ( is_array( $quiz_questions ) && ( $question_pos = array_search( $current_question_id, $quiz_questions ) ) !== false ) ){
        $next_id = $question_pos < sizeof( $quiz_questions ) - 1 ? $quiz_questions[ $question_pos + 1 ] : false;
    }
    return $next_id;
}

function learn_press_get_prev_question( $current_question_id, $quiz_id = false, $user_id = false ){
    if( ! $quiz_id ){
        $quiz_id = get_the_ID();
        if( get_post_type( $quiz_id ) != 'lpr_quiz' ){
            return false;
        }
    }
    if( ! $user_id ){
        $user_id = get_current_user_id();
    }
    $quiz_questions = learn_press_get_user_quiz_questions( $quiz_id, $user_id );
    $prev_id = false;
    if( ( $question_pos = array_search( $current_question_id, $quiz_questions ) ) !== false ){
        $prev_id = $question_pos > 0 ? $quiz_questions[ $question_pos - 1 ] : false;
    }
    return apply_filters( 'learn_press_get_prev_question', $prev_id, $current_question_id, $quiz_id, $user_id );
}

function learn_press_get_user_quiz_questions( $quiz_id = null, $user_id = null ){
    if( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    if( ! $quiz_id ){
        $quiz_id = get_the_ID();
        if( get_post_type( $quiz_id ) != 'lpr_quiz' ){
            return false;
        }
    }

    $questions = get_user_meta( $user_id, '_lpr_quiz_questions', true );
    $user_quiz_questions = array();
    $quiz_questions = array();

    if ( $questions && ! empty( $questions[$quiz_id] ) ) {
        $user_quiz_questions = $questions[$quiz_id];
    }
    if( $quiz_questions = (array)get_post_meta( $quiz_id, '_lpr_quiz_questions', true ) ) {
        if ( $quiz_questions ){
            $quiz_questions = array_keys( $quiz_questions );
        }else {
            $quiz_questions = array();
        }
    }
    $quiz_questions = array_unique( array_merge( $user_quiz_questions, $quiz_questions ) );

    return apply_filters( 'learn_press_get_user_quiz_questions', $quiz_questions, $quiz_id, $user_id );
}