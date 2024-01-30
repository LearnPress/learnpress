<?php
/**
 * Template for printing js code used for Quiz.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$user   = learn_press_get_current_user();
$course = learn_press_get_course();
if ( ! $course ) {
	return;
}

$quiz = LP_Global::course_item_quiz();
if ( ! $quiz ) {
	return;
}

$total_question      = $quiz->count_questions();
$questions           = array();
$show_check          = $quiz->get_instant_check();
$show_correct_review = $quiz->get_show_correct_review();
$question_ids        = $quiz->get_question_ids();
$user_js             = array();


$user_course       = $user->get_course_attend( $course->get_id() );
$user_quiz         = $user_course ? $user_course->get_item_attend( $quiz->get_id(), LP_QUIZ_CPT ) : false;
$answered          = array();
$status            = '';
$checked_questions = array();

$crypto_js_aes = false;
$editable      = $user->is_admin() || get_post_field( $user->is_author_of( $course->get_id() ) );
$max_retrying  = learn_press_get_quiz_max_retrying( $quiz->get_id(), $course->get_id() );
$quiz_results  = null;

if ( $user_quiz ) {
	$status            = $user_quiz->get_status();
	$quiz_results      = $user_quiz->get_result();
	$checked_questions = $user_quiz->get_checked_questions();

	$user_js = array(
		'status'            => $status,
		'attempts'          => $user_quiz->get_attempts(),
		'checked_questions' => $checked_questions,
		'start_time'        => $user_quiz->start_time,
		'retaken'           => $user_quiz->get_retaken_count(),
	);

	try {
		$time_remaining = $user_quiz->get_timestamp_remaining();
	} catch ( Exception $e ) {
		$time_remaining = 0;
	}
	$user_js['total_time'] = $time_remaining;

	if ( $quiz_results ) {
		$user_js['results'] = $quiz_results;
		$answered           = $quiz_results['questions'];
	}
} else {
	// Display quiz content.
	echo '<div class="quiz-content">';
	learn_press_echo_vuejs_write_on_php( $quiz->get_content() );
	echo '</div>';
}

$questions = learn_press_rest_prepare_user_questions(
	$question_ids,
	array(
		'instant_check'       => $show_check,
		'quiz_status'         => $status,
		'checked_questions'   => $checked_questions,
		'answered'            => $answered,
		'show_correct_review' => $show_correct_review,
		'status'              => $status,
	)
);

$duration = $quiz->get_duration();

$js = array(
	'course_id'              => $course->get_id(),
	'nonce'                  => wp_create_nonce( sprintf( 'user-quiz-%d', get_current_user_id() ) ),
	'id'                     => $quiz->get_id(),
	'title'                  => $quiz->get_title(),
	'content'                => '',
	'questions'              => $questions,
	'question_ids'           => $question_ids,
	'number_questions_to_do' => $quiz->get_number_questions_to_do(),
	'current_question'       => absint( reset( $question_ids ) ),
	'question_nav'           => '',
	'status'                 => '',
	'attempts'               => array(),
	'answered'               => $answered ? (object) $answered : new stdClass(),
	'checked_questions'      => array(),
	'passing_grade'          => $quiz->get_passing_grade(),
	'negative_marking'       => $quiz->get_negative_marking(),
	'show_correct_review'    => $show_correct_review,
	'instant_check'          => $quiz->get_instant_check(),
	'retake_count'           => absint( $quiz->get_retake_count() ),
	'retaken'                => 0,
	'questions_per_page'     => $quiz->get_pagination(),
	'page_numbers'           => get_post_meta( $quiz->get_id(), '_lp_pagination_numbers', true ) === 'yes',
	'review_questions'       => $quiz->get_review_questions(),
	'support_options'        => learn_press_get_question_support_answer_options(),
	'duration'               => $duration ? $duration->get() : false,
	'crypto'                 => $crypto_js_aes,
	'edit_permalink'         => $editable ? get_edit_post_link( $quiz->get_id() ) : '',
	'results'                => array(),
	'required_password'      => post_password_required( $quiz->get_id() ),
	'allow_retake'           => $quiz->get_retake_count() == - 1,
	'quiz_description'       => $quiz->get_content(),
);

LP_Helper::print_inline_script_tag( 'lp_quiz_js_data', [ 'data' => $js ] );

$js = array_merge( $js, $user_js );

if ( $total_question || $user_quiz ) :
	?>
	<div id="learn-press-quiz-app"></div>

	<script>
		document.addEventListener( 'DOMContentLoaded', () => {
			if ( typeof LP !== 'undefined' ) {
				LP.Hook.addAction( 'course-ready', () => {
					LP.quiz.init(
						'#learn-press-quiz-app',
						<?php echo json_encode( $js ); ?>
					)
				} );
			}
		} );
	</script>

	<?php
else :
	esc_html_e( 'You haven\'t any question!', 'learnpress' );
	?>

<?php endif; ?>
