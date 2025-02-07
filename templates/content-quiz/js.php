<?php
/**
 * Template for printing js code used for Quiz.
 * Call from hook 'learn-press/content-item-summary/lp_quiz'
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.1
 */

use LearnPress\Models\CourseModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LearnPress\Models\UserModel;

defined( 'ABSPATH' ) || exit;

global $lpCourseModel;
$courseModel = $lpCourseModel;
if ( ! $courseModel instanceof CourseModel ) {
	return;
}

$userModel = UserModel::find( get_current_user_id(), true );

$quiz = LP_Global::course_item_quiz();
if ( ! $quiz ) {
	return;
}

$quizPostModel = QuizPostModel::find( $quiz->get_id(), true );
if ( ! $quizPostModel instanceof QuizPostModel ) {
	return;
}

$total_question      = $quizPostModel->count_questions();
$questions           = array();
$show_check          = $quiz->get_instant_check();
$show_correct_review = $quiz->get_show_correct_review();
$question_ids        = $quiz->get_question_ids();
$user_js             = array();

$answered          = array();
$status            = '';
$checked_questions = array();

$crypto_js_aes = false;
$user          = learn_press_get_current_user();
$editable      = $user->is_admin() || get_post_field( $user->is_author_of( $courseModel->get_id() ) );
$max_retrying  = learn_press_get_quiz_max_retrying( $quiz->get_id(), $courseModel->get_id() );
$quiz_results  = null;
$userQuizModel = null;

if ( $userModel ) {
	$userQuizModel = UserQuizModel::find_user_item(
		$userModel->get_id(),
		$quiz->get_id(),
		LP_QUIZ_CPT,
		$courseModel->get_id(),
		LP_COURSE_CPT,
		true
	);

	if ( $userQuizModel instanceof UserQuizModel ) {
		$status       = $userQuizModel->get_status();
		$quiz_results = $userQuizModel->get_result();
		$user_js      = array(
			'status'            => $status,
			'attempts'          => $userQuizModel->get_history(),
			'checked_questions' => $userQuizModel->get_checked_questions(),
			'start_time'        => $userQuizModel->get_start_time(),
			'retaken'           => $userQuizModel->get_retaken_count(),
			'total_time'        => $userQuizModel->get_time_remaining(),
			'results'           => $quiz_results,
		);

		$answered = $quiz_results['questions'];
	}
}

if ( ! $userQuizModel ) {
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
	'course_id'              => $courseModel->get_id(),
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

$js = array_merge( $js, $user_js );

// To show data debug.
LP_Helper::print_inline_script_tag( 'lp_quiz_js_data', [ 'data' => $js ] );

if ( $total_question ) {
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
} else {
	esc_html_e( 'You haven\'t any question!', 'learnpress' );
}
