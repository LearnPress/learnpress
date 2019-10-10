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
	$userJS  = array(
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
			'type'        => $question->get_type(),
			'hint'        => $hinted ? $theHint : '',
			'explanation' => $checked ? $theExplanation : ''
		);

		if ( $content = $question->get_content() ) {
			$questionData['content'] = $content;
		}

		if ( $hinted && $theHint ) {
			$questionData['hint'] = $theHint;
		}

		if ( $checked && $theExplanation ) {
			$questionData['explanation'] = $theExplanation;
		}

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

		if ( $question->is_support( 'answer-options' ) ) {
			$questionData['options'] = xxx_get_question_options_for_js( $question, array( 'include_is_true' => $with_true_or_false ) );
		} elseif ( $question->get_type() === 'fill_in_blanks' ) {
			$blanks          = xxx_get_question_options_for_js( $question, array( 'include_is_true' => $with_true_or_false ) );
			$blankFillsStyle = get_post_meta( $id, '_lp_blank_fills_style', true );

			foreach ( $blanks as $k => $blank ) {
				$blanks[ $k ]['text'] = preg_replace( '/\{\{([^\{\"\'].*?)\}\}/', '{{BLANK}}', $blank['text'] );
				$blankOptions         = learn_press_get_question_answer_meta( $blank['uid'], '_blanks', true );

				if ( in_array( $blankFillsStyle, array( 'select', 'enumeration' ) ) ) {
					$blanks[ $k ]['words'] = isset( $blankOptions['words'] ) ? $blankOptions['words'] : array();
				}

				if ( isset( $blankOptions['tip'] ) ) {
					$blanks[ $k ]['tip'] = $blankOptions['tip'];
				}
			}
			$questionData['options']         = $blanks;
			$questionData['blankFillsStyle'] = $blankFillsStyle;
			$questionData['blanksStyle']     = get_post_meta( $id, '_lp_blanks_style', true );
		}

		$questions[] = apply_filters( 'learn-press/single-quiz-js/question-data', $questionData, $question->get_type(), $question->get_id(), $question );
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
	'answered'             => $answered ? (object) $answered : new stdClass(),
	'passing_grade'        => $quiz->get_passing_grade(),
	'negative_marking'     => get_post_meta( $quiz->get_id(), '_lp_negative_marking', true ) === 'yes',
	'instant_check'        => get_post_meta( $quiz->get_id(), '_lp_instant_check', true ) === 'yes',
	'retry'                => get_post_meta( $quiz->get_id(), '_lp_retry', true ) === 'yes',
	'questions_layout'     => 100,//get_post_meta( $quiz->get_id(), '_lp_pagination', true ),
	'review_questions'     => $quiz->get_review_questions(),
	////
	'show_correct_answers' => $quiz->get_show_result(),
	'show_check_answers'   => ! ! $quiz->get_show_check_answer(),
	'show_hint'            => ! ! $quiz->get_show_hint(),
	////
	'support_options'      => learn_press_get_question_support_answer_options(),
	'duration'             => $duration ? $duration->get() : false,
	'crypto'               => $cryptoJsAes,
	'edit_permalink'       => $editable ? get_edit_post_link( $quiz->get_id() ) : '',
);

$js       = array_merge( $js, $userJS );
$duration = $quiz->get_duration();
var_dump($duration->get());
print_r( learn_press_create_user_item_for_quiz( array(
	'item_id'   => $quiz->get_id(),
	'duration'  => $duration ? $duration->get() : 0,
	'parent_id' => $userCourse ? $userCourse->get_user_item_id() : 0,
    'create_meta' => array(
            'ahihi'=>1
    )
), true ) );

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