<?php
/**
 * Template for printing js code used for Quiz.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.x.x
 */

defined( 'ABSPATH' ) or die;

$user      = learn_press_get_current_user();
$course    = LP_Global::course();
$quiz      = LP_Global::course_item_quiz();
$questions = array();
$showHint  = $quiz->get_show_hint();
$showCheck = $quiz->get_show_check_answer();
$userJS    = array();


$userCourse       = $user->get_course_data( $course->get_id() );
$userQuiz         = $userCourse ? $userCourse->get_item( $quiz->get_id() ) : false;
$attempts         = $userQuiz->get_attempts( array(
	'limit'  => 1,
	'offset' => 1
) );/// get_attempts($quiz->get_id(), $course->get_id(), $user->get_id());
$answered         = array();
$status           = '';
$checkedQuestions = array();
$hintedQuestions  = array();

$cryptoJsAes = false;//function_exists( 'openssl_decrypt' );
$editable    = $user->is_admin() || get_post_field( $user->is_author_of( $course->get_id() ) );
if ( $userQuiz ) {
	$status           = $userQuiz->get_status();
	$quizResults      = $userQuiz->get_results( '' );
	$checkedQuestions = $userQuiz->get_checked_questions();
	$hintedQuestions  = $userQuiz->get_hint_questions();
	$expirationTime   = $userQuiz->get_expiration_time();

	// If expiration time is specific then calculate total time
	if ( $expirationTime && ! $expirationTime->is_null() ) {
		$totalTime = strtotime( $userQuiz->get_expiration_time() ) - strtotime( $userQuiz->get_start_time() );
	}

	$userJS = array(
		'status'            => $status,
		'attempts'          => $attempts,
		'checked_questions' => $checkedQuestions,
		'hinted_questions'  => $hintedQuestions,
		'start_time'        => $userQuiz->get_start_time()->toSql(),
	);

	if ( isset( $totalTime ) ) {
		$userJS['totalTime'] = $totalTime;
		$userJS['endTime']   = $expirationTime->toSql();
	}

	if ( $quizResults ) {
		$userJS['results'] = $quizResults->get();
		$answered          = $quizResults->getQuestions();// getAnswered();// $userQuiz->get_meta( '_question_answers' );
		$question_ids      = $quizResults->getQuestions( 'ids' );// $userQuiz->get_meta( 'questions' );
	} else {
		$question_ids = $quiz->get_question_ids();
	}
}

if ( ! isset( $question_ids ) || ! $question_ids ) {
	$question_ids = array();// $quiz->get_question_ids();
}

$questions = learn_press_rest_prepare_user_questions( $question_ids,
	array(
		'instant_hint'      => $showHint,
		'instant_check'     => $showCheck,
		'quiz_status'       => $status,
		'checked_questions' => $checkedQuestions,
		'hinted_questions'  => $hintedQuestions,
		'answered'          => $answered,
	)
);

$duration = $quiz->get_duration();

$js = array(
	'course_id'            => $course->get_id(),
	'nonce'                => wp_create_nonce( sprintf( 'user-quiz-%d', get_current_user_id() ) ),
	'id'                   => $quiz->get_id(),
	'title'                => $quiz->get_title(),
	'content'              => $quiz->get_content(),
	'questions'            => $questions,
	'question_ids'         => array_map( 'absint', array_values( $question_ids ) ),
	'current_question'     => absint( reset( $question_ids ) ),
	'question_nav'         => '',
	'status'               => '',
	'attempts'             => array(),
	'attempts_count'       => 10,
	'answered'             => $answered ? (object) $answered : new stdClass(),
	'passing_grade'        => $quiz->get_passing_grade(),
	'negative_marking'     => get_post_meta( $quiz->get_id(), '_lp_negative_marking', true ) === 'yes',
	'instant_check'        => get_post_meta( $quiz->get_id(), '_lp_instant_check', true ) === 'yes',
	'retry'                => get_post_meta( $quiz->get_id(), '_lp_retry', true ) === 'yes',
	'questions_per_page'   => absint( get_post_meta( $quiz->get_id(), '_lp_pagination', true ) ),
	'page_numbers'         => get_post_meta( $quiz->get_id(), '_lp_pagination_numbers', true ) === 'yes',
	'review_questions'     => $quiz->get_review_questions(),
	//'show_correct_answers' => $quiz->get_show_result(),
	'support_options'      => learn_press_get_question_support_answer_options(),
	'duration'             => $duration ? $duration->get() : false,
	'crypto'               => $cryptoJsAes,
	'edit_permalink'       => $editable ? get_edit_post_link( $quiz->get_id() ) : '',
	'results'              => array()
);

$js = array_merge( $js, $userJS );

//$duration = $quiz->get_duration();
//$userItem = learn_press_create_user_item_for_quiz( array(
//	'item_id'   => $quiz->get_id(),
//	'duration'  => $duration ? $duration->get() : 0,
//	'parent_id' => $userCourse ? $userCourse->get_user_item_id() : 0,
//    'create_meta' => array(
//            'ahihi'=>1
//    )
//), true );
?>
<div id="learn-press-quiz-app"></div>
<script>
    jQuery(function () {
        LP.Hook.addAction('course-ready', () => {
            LP.quiz.init(
                '#learn-press-quiz-app',
				<?php echo( json_encode( $js ) );?>
            );
        })
    })

</script>