<?php

function learn_press_is_public_quiz( $course_id ){
    return ( false == learn_press_course_enroll_required( $course_id ) && ! is_user_logged_in() );
}

function learn_press_after_reset_user_quiz( $quiz_id, $user_id ){
    $course_id = learn_press_get_course_by_quiz( $quiz_id );
    if( learn_press_is_public_quiz( $course_id ) ){
        if( ! empty( $_SESSION['learn_press']['anonymous_user_quiz'] ) ){
            unset( $_SESSION['learn_press']['anonymous_user_quiz'] );
        }
    }
}
add_action( 'learn_press_after_reset_user_quiz', 'learn_press_after_reset_user_quiz', 99, 2 );

function learn_press_before_nav_question_form( $question_id, $course_id ){

    if( learn_press_is_public_quiz( $course_id ) ){
        if( ! empty( $_POST['data']['user_questions'] ) ){
            $questions = explode( ',', $_POST['data']['user_questions'] );
        }else{
            $questions = learn_press_get_questions_for_anonymous_user();
        }
        if( $questions ) {
            echo '<input type="text" name="user_questions" value="' . join(',', $questions) . '" />';
        }
    }
}
add_action( 'learn_press_before_nav_question_form', 'learn_press_before_nav_question_form', 99, 2 );

function learn_press_get_questions_for_anonymous_user(){
    $questions = false;
    $session = LPR_Session::instance();
    $quiz = $session->get( 'anonymous_quiz' );
    if( $quiz ){
        $questions = $quiz['questions'];
    }
    return $questions;
}
//add_filter( 'learn_press_get_quiz_questions', 'learn_press_anonymous_get_quiz_questions', 99999, 3 );

function learn_press_anonymous_get_question_position( $return, $user_id, $quiz_id, $question_id ){
    $course_id = learn_press_get_course_by_quiz( $quiz_id );

    if( learn_press_is_public_quiz( $course_id ) ){
        if( $questions = learn_press_get_questions_for_anonymous_user() ) {
            if ( ! $question_id ) {
                $question_id = ! empty( $_POST['current'] ) ? $_POST['current'] : 0;
            }

            $pos = array_search( $question_id, $questions );
            $pos = false !== $pos ? $pos : 0;
            print_r($questions);
            $return['position'] = $pos;
            $return['id'] = $questions[ $pos ];

        }
    }
    return $return;
}
add_filter( 'learn_press_get_question_position', 'learn_press_anonymous_get_question_position', 99, 4 );

function learn_press_anonymous_get_question_answers( $answer, $quiz_id, $user_id ){
    return $answer;
}
add_filter( 'learn_press_get_question_answers', 'learn_press_anonymous_get_question_answers', 99, 3 );

function learn_press_anonymous_get_quiz_questions( $questions, $quiz_id, $only_ids ){
    $course_id = learn_press_get_course_by_quiz( $quiz_id );
    if( learn_press_is_public_quiz( $course_id ) ) {
        if( $_questions = learn_press_get_questions_for_anonymous_user() ){
            $questions = $_questions;
        }
    }
    return $questions;
}

/**
 * Quiz for anonymous users
 *
 * @param boolean
 * @param int
 * @param int
 * @return boolean
 */
function learn_press_do_quiz_for_anonymous_user( $continue, $quiz_id, $user_id ){
    $course_id = learn_press_get_course_by_quiz( $quiz_id );
    if( learn_press_is_public_quiz( $course_id ) ){
        $session = LPR_Session::instance();
        $session->set(
            'anonymous_quiz',
            array(
                'questions' => array_values( learn_press_get_quiz_questions( $quiz_id ) )
            )
        );

        $continue = false;
    }
    return $continue;
}
add_filter( 'learn_press_before_user_start_quiz', 'learn_press_do_quiz_for_anonymous_user', 999, 3 );