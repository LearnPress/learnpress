<?php
/**
 * Class QuizTemplate
 *
 * @since 4.2.9.4
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Quiz;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\Models\CourseModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\TemplateHooks\Question\QuestionTemplate;
use LearnPress\TemplateHooks\Quiz\QuizTemplateComponents;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Global;
use LP_Quiz;
use Throwable;
use stdClass;
use Exception;

/**
 * QuizTemplate class.
 */
class QuizTemplate {
	use Singleton;

	/**
	 * Initialize hooks.
	 *
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function init() {
		// Hook for rendering start quiz  screen.
		// add_action( 'learn-press/quiz-start-screen', array( $this, 'start_quiz_screen' ) );
		add_action( 'learn-press/content-item-summary/lp_quiz', array( $this, 'quiz_content_item_summary' ) );
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
	}

	/**
	 * Allow callback for content quiz
	 *
	 * @param array $callbacks.
	 *
	 * @return array
	 */
	public function allow_callback( $callbacks ) {
		/**
		 * @uses self::render_courses()
		 */
		$callbacks[] = get_class( $this ) . ':render_quiz_content_callback';

		return $callbacks;
	}

	public function quiz_content_item_summary() {
		try {
			global $lpCourseModel;
			$courseModel = $lpCourseModel;
			if ( ! $courseModel instanceof CourseModel ) {
				return;
			}
			// return;

			$course_id = $courseModel->get_id();
			$user_id   = get_current_user_id();
			$quiz_item = LP_Global::course_item_quiz();
			if ( ! $quiz_item ) {
				return;
			}

			$quiz_id       = $quiz_item->get_id();
			$quizPostModel = QuizPostModel::find( $quiz_id, true );
			if ( ! $quizPostModel instanceof QuizPostModel ) {
				return;
			}

			$content   = $this->load_quiz_content_ajax( $user_id, $course_id, $quiz_id );
			echo $content;
		} catch ( Throwable $e ) {
			echo $e->getMessage();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Example: Prepare AJAX arguments for quiz content rendering via AJAX
	 *
	 * This demonstrates step 1 of the /render-quiz-ajax workflow:
	 * Building the $args array and $callback for TemplateAJAX::load_content_via_ajax()
	 *
	 * @param int    $quiz_id Current quiz identifier
	 * @param string $mode    Mode: 'quiz' for questions, 'result' for results, 'review' to toggle
	 * @param int    $user_id Optional user ID for context
	 *
	 * @return string HTML with AJAX loading wrapper
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function load_quiz_content_ajax( $user_id = 0, $course_id = 0, $quiz_id = 0 ) {
		try {
			
			// Step 1: Build $args array with required keys
			$args = array(
				'quiz_id'   => $quiz_id,
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'nonce'     => wp_create_nonce( 'lp_quiz_ajax_' . $quiz_id ),
				'is_review' => false,
			);

			// Step 1: Define $callback function that will receive the AJAX response
			$callback = array(
				'class'  => self::class,
				'method' => 'render_quiz_content_callback',
			);
			// return '';

			$content = TemplateAJAX::load_content_via_ajax( $args, $callback );
			return $content;
			// $args['html_no_load_ajax_first'] TODO
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Example: Server-side callback for AJAX quiz content rendering
	 *
	 * This demonstrates step 3 of the /render-quiz-ajax workflow:
	 * Server-side callback logic that detects mode and renders appropriate components
	 *
	 * @param array $data AJAX request data containing 'quiz_id', 'mode', etc.
	 *
	 * @return \stdClass Object with 'content' property containing rendered HTML
	 * @throws \Exception If quiz not found or user lacks permission
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public static function render_quiz_content_callback( $data ) {

		$quiz_id   = isset( $data['quiz_id'] ) ? absint( $data['quiz_id'] ) : 0;
		$course_id = isset( $data['course_id'] ) ? absint( $data['course_id'] ) : 0;
		$user_id   = isset( $data['user_id'] ) ? absint( $data['user_id'] ) : get_current_user_id();

		$courseModel = CourseModel::find( $course_id );
		if ( ! $courseModel ) {
			throw new Exception( 'Course is not existed', 'learnpress' );
		}

		$quizPostModel = QuizPostModel::find( $quiz_id );
		if ( ! $quizPostModel ) {
			throw new Exception( __( 'Quiz is not existed', 'learnpress' ) );
		}

		$user_data           = array();
		$answered            = array();
		$status              = '';
		$retake_count        = $quizPostModel->get_retake_count();
		$can_retake_count    = $retake_count;
		$show_check          = $quizPostModel->has_instant_check();
		$show_correct_review = $quizPostModel->has_show_correct_review();
		$question_ids        = $quizPostModel->get_question_ids();
		$duration            = $quizPostModel->get_duration();
		$duration_timestamp  = strtotime( '+' . $duration, 0 ) ?: false;
		$duration_text       = $duration_timestamp ? learn_press_seconds_to_time( $duration_timestamp ) : '-:-:-';
		$total_time          = $duration_timestamp;
		$questions_per_page  = $quizPostModel->get_question_perpage();
		$userModel           = UserModel::find( $user_id, true );
		$checked_questions   = array();
		if ( $userModel ) {
			$userQuizModel = UserQuizModel::find_user_item(
				$userModel->get_id(),
				$quiz_id,
				LP_QUIZ_CPT,
				$course_id,
				LP_COURSE_CPT,
				true
			);
			if ( $userQuizModel ) {
				$status              = $userQuizModel->get_status();
				$quiz_results        = $userQuizModel->get_result();
				$checked_questions   = $userQuizModel->get_checked_questions();
				$retaken             = $userQuizModel->get_retaken_count();
				$can_retake_count    = $userQuizModel->get_remaining_retake();
				$total_time          = $userQuizModel->get_time_remaining();

				$user_data           = array(
					'status'            => $status,
					'attempts'          => $userQuizModel->get_history(),
					'start_time'        => $userQuizModel->get_start_time(),
					'results'           => $quiz_results,
				);

				$answered = QuizTemplateComponents::instance()->get_array_value( $quiz_results, 'questions', array() );
			}
		}

		$questions = array();
		if ( ! empty( $question_ids ) ) {
			foreach ( $question_ids as $question_id ) {
				$questions[] = QuestionPostModel::prepare_render_data(
					$question_id,
					array(
						'instant_check'       => $show_check,
						'quiz_status'         => $status,
						'checked_questions'   => $checked_questions,
						'answered'            => $answered,
						'show_correct_review' => $show_correct_review,
						'status'              => $status,
					)
				);
			}	
		}
		$quiz_data = array(
			'quiz_id'                => $quiz_id,
			'course_id'              => $course_id,
			'user_id'                => $user_id,
			'title'                  => $quizPostModel->get_the_title(),
			'questions'              => $questions,
			'question_ids'           => $question_ids,
			'number_questions_to_do' => $quizPostModel->count_questions(),
			'current_question'       => absint( reset( $question_ids ) ),
			'question_nav'           => '',
			'status'                 => '',
			'attempts'               => array(),
			'passing_grade'          => $quizPostModel->get_passing_grade(),
			'questions_per_page'     => $questions_per_page,
			'enable_review'          => $quizPostModel->enable_review(),
			'duration'               => $duration_timestamp,
			'total_time'             => $total_time,
			'results'                => array(),
			'required_password'      => post_password_required( $quiz_id ),
			'allow_retake'           => $can_retake_count > 0 || $retake_count === -1 ,
			'can_retake_count'       => $can_retake_count,
			'quiz_description'       => $quizPostModel->get_the_content(),
			'num_pages'              => $questions_per_page !== 0 ? ceil( (int) $quizPostModel->count_questions() / $quizPostModel->get_question_perpage() ) : 1,
		);

		$quiz_data = array_merge( $quiz_data, $user_data );
		if ( ! $quiz_id ) {
			throw new Exception( __( 'Quiz ID is required', 'learnpress' ) );
		}

		// Verify nonce
		$nonce = isset( $data['nonce'] ) ? $data['nonce'] : '';
		if ( ! wp_verify_nonce( $nonce, 'lp_quiz_ajax_' . $quiz_id ) ) {
			throw new Exception( __( 'Security check failed', 'learnpress' ) );
		}

		// Load quiz data (simplified - you'd need full quiz data here)
		$quizPostModel = QuizPostModel::find( $quiz_id, true );
		if ( ! $quizPostModel instanceof QuizPostModel ) {
			throw new Exception( __( 'Quiz not found', 'learnpress' ) );
		}

		$quizComponents = QuizTemplateComponents::instance();
		$html           = '';

		// Step 3: Detect mode and render appropriate components
		if ( $status === LP_ITEM_STARTED ) {
			$mode = 'quiz';
		} else if ( $status === LP_ITEM_COMPLETED ) {
			$mode = ! empty( $data['is_review'] ) ? 'review' : 'result';
		} else {
			$mode = 'intro';
		}
		switch ( $mode ) {
			case 'quiz':
			case 'review':
				// Quiz mode: render status, questions, and buttons
				$html .= $quizComponents->status_html( $quiz_data ); // Pass quiz_data
				$html .= $quizComponents->questions_html( $quiz_data ); // Pass quiz_data
				$html .= $quizComponents->pagination_html( $quiz_data );
				$html .= $quizComponents->buttons_html( $quiz_data ); // Pass quiz_data
				break;
			case 'result':
				// Result mode: render result, buttons, and attempts
				$html .= $quizComponents->result_html( $quiz_data ); // Pass quiz_data
				$html .= $quizComponents->buttons_html( $quiz_data ); // Pass quiz_data
				$html .= $quizComponents->attempts_html( $quiz_data ); // Pass quiz_data
				break;
			default:
				$html .= $quizComponents->introduction_html( $quiz_data );
				$html .= $quizComponents->buttons_html( $quiz_data );
				break;
		}

		// Return response object
		$response          = new stdClass();
		$response->content = $html;

		return $response;
	}
}