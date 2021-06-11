<?php
/**
 * Template for printing js code used for Quiz.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$user                = learn_press_get_current_user();
$course              = LP_Global::course();
$quiz                = LP_Global::course_item_quiz();
$total_question      = $quiz->count_questions();
$questions           = array();
$show_check          = $quiz->get_instant_check();
$show_correct_review = $quiz->get_show_correct_review();
$user_js             = array();


$user_course       = $user->get_course_data( $course->get_id() );
$user_quiz         = $user_course ? $user_course->get_item( $quiz->get_id() ) : false;
$answered          = array();
$status            = '';
$checked_questions = array();

$crypto_js_aes = false;
$editable      = $user->is_admin() || get_post_field( $user->is_author_of( $course->get_id() ) );
$max_retrying  = learn_press_get_quiz_max_retrying( $quiz->get_id(), $course->get_id() );

$question_ids = array();

if ( $user_quiz ) {
	$status            = $user_quiz->get_status();
	$quiz_results      = $user_quiz->get_results( '' );
	$checked_questions = $user_quiz->get_checked_questions();
	$expiration_time   = $user_quiz->get_expiration_time();

	// If expiration time is specific then calculate total time
	if ( $expiration_time && ! $expiration_time->is_null() ) {
		$total_time = strtotime( $user_quiz->get_expiration_time() ) - strtotime( $user_quiz->get_start_time() );
	}

	$user_js = array(
		'status'            => $status,
		'attempts'          => $user_quiz->get_attempts(),
		'checked_questions' => $checked_questions,
		'start_time'        => $user_quiz->get_start_time()->toSql( false ),
		'retaken'           => absint( $user_quiz->get_retaken_count() ),
	);

	if ( isset( $total_time ) ) {
		$user_js['total_time'] = $total_time;
		$user_js['endTime']    = $expiration_time->toSql( false );
	}

	if ( $quiz_results ) {
		$user_js['results'] = $quiz_results->get();
		$answered           = $quiz_results->getQuestions();
		$question_ids       = $quiz_results->getQuestions( 'ids' );
	} else {
		$question_ids = $quiz->get_question_ids();
	}
}

$questions = learn_press_rest_prepare_user_questions(
	$question_ids,
	array(
		'instant_check'       => $show_check,
		'quiz_status'         => $status,
		'checked_questions'   => $checked_questions,
		'answered'            => $answered,
		'show_correct_review' => $show_correct_review,
	)
);

if ( empty( $question_ids ) ) {
	$question_ids = $quiz->get_question_ids();
}

$duration = $quiz->get_duration();

$js = array(
	'course_id'           => $course->get_id(),
	'nonce'               => wp_create_nonce( sprintf( 'user-quiz-%d', get_current_user_id() ) ),
	'id'                  => $quiz->get_id(),
	'title'               => $quiz->get_title(),
	'content'             => $quiz->get_content(),
	'questions'           => $questions,
	'question_ids'        => array_map( 'absint', array_values( $question_ids ) ),
	'current_question'    => absint( reset( $question_ids ) ),
	'question_nav'        => '',
	'status'              => '',
	'attempts'            => array(),
	'answered'            => $answered ? (object) $answered : new stdClass(),
	'passing_grade'       => $quiz->get_passing_grade(),
	'negative_marking'    => $quiz->get_negative_marking(),
	'show_correct_review' => $show_correct_review,
	'instant_check'       => $quiz->get_instant_check(),
	'retake_count'        => absint( $quiz->get_retake_count() ),
	'retaken'             => 0,
	'questions_per_page'  => $quiz->get_pagination(),
	'page_numbers'        => get_post_meta( $quiz->get_id(), '_lp_pagination_numbers', true ) === 'yes',
	'review_questions'    => $quiz->get_review_questions(),
	'support_options'     => learn_press_get_question_support_answer_options(),
	'duration'            => $duration ? $duration->get() : false,
	'crypto'              => $crypto_js_aes,
	'edit_permalink'      => $editable ? get_edit_post_link( $quiz->get_id() ) : '',
	'results'             => array(),
);

if ( $course->is_no_required_enroll() ) {
	$cookie_user_status = 'quiz_submit_status_' . $course->get_id() . '_' . $quiz->get_id() . '';
	if ( ! empty( $_COOKIE[ $cookie_user_status ] ) ) {
		$js_status    = $_COOKIE[ $cookie_user_status ];
		$js['status'] = $js_status;
	}
}

$js = array_merge( $js, $user_js );

if ( $total_question ) :
	?>
	<div id="learn-press-quiz-app"></div>

	<script>
	document.addEventListener( 'DOMContentLoaded', () => {
		if ( typeof LP !== 'undefined' ) {
			LP.Hook.addAction('course-ready', () => {
				LP.quiz.init(
					'#learn-press-quiz-app',
					<?php echo json_encode( $js ); ?>
				)
			});
		}
	});
	</script>

	<?php
else :
	esc_html_e( 'You haven\'t any question!', 'learnpress' );
	?>

<?php endif; ?>
