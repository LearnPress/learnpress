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


$userCourse = $user->get_course_data( $course->get_id() );
$userQuiz   = $userCourse ? $userCourse->get_item( $quiz->get_id() ) : false;
$attempts   = $userQuiz->get_attempts();/// get_attempts($quiz->get_id(), $course->get_id(), $user->get_id());
$answered   = array();
$status     = '';

$cryptoJsAes = false;//function_exists( 'openssl_decrypt' );
$editable    = $user->is_admin() || get_post_field( $user->is_author_of( $course->get_id() ) );

if ( $userQuiz ) {
	$status  = $userQuiz->get_status();
	$results = $userQuiz->get_results( '' );
	$userJS = array(
		'status'            => $status,
		'attempts'          => $attempts,
		'checked_questions' => $userQuiz->get_checked_questions(),
		'hinted_questions'  => $userQuiz->get_hint_questions()
	);

	$answered = $userQuiz->get_meta( '_question_answers' );
}

if ( $question_ids = $quiz->get_questions() ) {
	$checkedQuestions = isset( $userJS['checked_questions'] ) ? $userJS['checked_questions'] : array();
	$hintedQuestions  = isset( $userJS['hinted_questions'] ) ? $userJS['hinted_questions'] : array();

	foreach ( $question_ids as $id ) {
		$question       = learn_press_get_question( $id );
		$hasHint        = false;
		$hasExplanation = false;
		$canCheck       = false;
		$hinted         = false;
		$checked        = false;
		$theHint        = '';
		$theExplanation = '';

		if ( $showHint ) {
			$theHint = $question->get_hint();
			$hinted  = in_array( $id, $hintedQuestions );
			$hasHint = ! ! $theHint;
		}

		if ( $showCheck ) {
			$theExplanation = $question->get_explanation();
			$checked        = in_array( $id, $checkedQuestions );
			$hasExplanation = ! ! $theExplanation;
		}

		$questionData = array(
			'id'          => absint( $id ),
			'title'       => $question->get_title(),
			'content'     => $question->get_content(),
			'type'        => $question->get_type(),
			'hint'        => $hinted ? $theHint : '',
			'explanation' => $checked ? $theExplanation : ''
		);

		if ( $hasHint ) {
			$questionData['has_hint'] = $hasHint;

			if ( $hinted ) {
				$questionData['hint'] = $theHint;
			}
		}

		if ( $hasExplanation ) {
			$questionData['has_explanation'] = $hasExplanation;

			if ( $checked ) {
				$questionData['explanation'] = $theExplanation;
			}
		}

		$with_true_or_false = $checked || $status === 'completed';

		$questionData['options'] = xxx_get_question_options_for_js( $question, array( 'include_is_true' => $with_true_or_false ) );
		$questions[] = $questionData;
	}

	if ( $status !== 'completed' ) {
		if ( $checkedQuestions && $answered ) {

			$omitIds = array_diff( $question_ids, $checkedQuestions );

			if ( $omitIds ) {
				foreach ( $omitIds as $omitId ) {
					if ( ! empty( $answered[ $omitId ] ) ) {
						unset( $answered[ $omitId ] );
					}
				}
			}
		}
	}

}

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
	'question_nav'         => 'infinity',
	'status'               => '',
	'attempts'             => array(),
	'attempts_count'       => 10,
	'answered'             => (object) $answered,
	'passing_grade'        => $quiz->get_passing_grade(),
	'review_questions'     => $quiz->get_review_questions(),
	'show_correct_answers' => $quiz->get_show_result(),
	'show_check_answers'   => ! ! $quiz->get_show_check_answer(),
	'show_hint'            => ! ! $quiz->get_show_hint(),
	'support_options'      => apply_filters( 'learn-press/4.0/question-support-options', array(
		'true_or_false',
		'single_choice',
		'multi_choice'
	) ),
	'duration'             => $duration ? $duration->get() : false,
	'crypto'               => $cryptoJsAes,
	'edit_permalink'       => $editable ? get_edit_post_link( $quiz->get_id() ) : '',
	'questions_layout'     => 1
);

$js = array_merge( $js, $userJS );

?>
<div id="learn-press-quiz-app"></div>
<script>
    window.addEventListener('load', function () {
        setTimeout(function () {
            jQuery(($) => {
                LP.quiz.init(
                    '#learn-press-quiz-app',
					<?php echo( json_encode( $js ) );?>
                );
            })
        }, 300)
    });
//    var CryptoJSAesJson = {
//        stringify: function (cipherParams) {
//            var j = {ct: cipherParams.ciphertext.toString(CryptoJS.enc.Base64)};
//            if (cipherParams.iv) j.iv = cipherParams.iv.toString();
//            if (cipherParams.salt) j.s = cipherParams.salt.toString();
//            return JSON.stringify(j).replace(/\s/g, '');
//        },
//        parse: function (jsonStr) {
//            var j = JSON.parse(jsonStr);
//            var cipherParams = CryptoJS.lib.CipherParams.create({ciphertext: CryptoJS.enc.Base64.parse(j.ct)});
//            if (j.iv) cipherParams.iv = CryptoJS.enc.Hex.parse(j.iv);
//            if (j.s) cipherParams.salt = CryptoJS.enc.Hex.parse(j.s);
//            return cipherParams;
//        }
//    }

</script>