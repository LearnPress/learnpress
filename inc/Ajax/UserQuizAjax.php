<?php
/**
 * Class UserQuizAjax
 *
 * Handle user actions on quiz: start, retake, submit, instant check.
 * Each action returns HTML (UserQuizTemplate) for JS (user-quiz.js) to display.
 * Replace REST API lp/v1/users/start-quiz, submit-quiz, check-answer.
 *
 * Nonce 'wp_rest' is verified on AbstractAjax::catch_lp_ajax.
 *
 * @since 4.5.0
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Helpers\Response;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\UserItem\UserCourseTemplate;
use LearnPress\TemplateHooks\UserItem\UserQuizTemplate;
use LP_Cache;
use LP_Debug;
use LP_Duration;
use LP_Helper;
use LP_Quiz_Cache;
use LP_Request;
use Throwable;

defined( 'ABSPATH' ) || exit;

class UserQuizAjax extends AbstractAjax {
	/**
	 * User start quiz.
	 * Guest (course no required enroll) is not saved, only get HTML doing quiz.
	 *
	 * @return void
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function user_start_quiz() {
		$response = new Response();

		try {
			$context = $this->get_context( $this->get_params() );
			/**
			 * @var CourseModel $courseModel
			 * @var QuizPostModel $quizPostModel
			 * @var UserModel|false $userModel
			 */
			[ 'courseModel' => $courseModel, 'quizPostModel' => $quizPostModel, 'userModel' => $userModel ] = $context;

			do_action( 'learn-press/user/before/start-quiz', $quizPostModel->get_id(), $courseModel->get_id(), get_current_user_id() );

			$userQuizModel = false;
			if ( ! $context['is_guest'] ) {
				$userQuizModel = $this->create_user_quiz( $userModel->get_id(), $quizPostModel->get_id(), $courseModel->get_id() );
				$userQuizModel->handle_start();
			}

			$response->data->content = $this->get_template()->html_doing_quiz( $courseModel, $quizPostModel, $userQuizModel );
			$response->data->status  = UserQuizModel::STATUS_STARTED;
			$response->status        = Response::STATUS_SUCCESS;
		} catch ( Throwable $e ) {
			$response->message = $this->get_error_message( $e );
		}

		wp_send_json( $response );
	}

	/**
	 * User retake quiz completed.
	 * Guest: retaken count is checked on browser, server only get HTML doing quiz.
	 *
	 * @return void
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function user_retake_quiz() {
		$response = new Response();

		try {
			$context = $this->get_context( $this->get_params() );
			/**
			 * @var CourseModel $courseModel
			 * @var QuizPostModel $quizPostModel
			 * @var UserModel|false $userModel
			 */
			[ 'courseModel' => $courseModel, 'quizPostModel' => $quizPostModel, 'userModel' => $userModel ] = $context;

			do_action( 'learn-press/user/before/start-quiz', $quizPostModel->get_id(), $courseModel->get_id(), get_current_user_id() );

			$userQuizModel = false;
			if ( ! $context['is_guest'] ) {
				$userQuizModel = UserQuizModel::find_user_item(
					$userModel->get_id(),
					$quizPostModel->get_id(),
					LP_QUIZ_CPT,
					$courseModel->get_id(),
					LP_COURSE_CPT
				);
				if ( ! $userQuizModel instanceof UserQuizModel ) {
					throw new Exception( esc_html__( 'You have not started the quiz.', 'learnpress' ) );
				}

				$userQuizModel->handle_retake();
			}

			$response->data->content = $this->get_template()->html_doing_quiz( $courseModel, $quizPostModel, $userQuizModel );
			$response->data->status  = UserQuizModel::STATUS_STARTED;
			$response->status        = Response::STATUS_SUCCESS;
		} catch ( Throwable $e ) {
			$response->message = $this->get_error_message( $e );
		}

		wp_send_json( $response );
	}

	/**
	 * User submit (finish) quiz.
	 * Guest: calculate result, not save.
	 *
	 * @return void
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function user_submit_quiz() {
		$response = new Response();

		try {
			$params  = $this->get_params();
			$context = $this->get_context( $params );
			/**
			 * @var CourseModel $courseModel
			 * @var QuizPostModel $quizPostModel
			 * @var UserModel|false $userModel
			 */
			[ 'courseModel' => $courseModel, 'quizPostModel' => $quizPostModel, 'userModel' => $userModel ] = $context;

			$answered      = $this->sanitize_answered( $params['answered'] ?? [], $quizPostModel->get_question_ids() );
			$userQuizModel = false;
			if ( ! $context['is_guest'] ) {
				$userQuizModel = UserQuizModel::find_user_item( $userModel->get_id(), $quizPostModel->get_id(), LP_QUIZ_CPT, $courseModel->get_id(), LP_COURSE_CPT );
				if ( ! $userQuizModel instanceof UserQuizModel ) {
					throw new Exception( esc_html__( 'User quiz is invalid!', 'learnpress' ) );
				}
			}

			$time_spend = $this->get_time_spend( absint( $params['time_spend'] ?? 0 ), $quizPostModel, $userQuizModel );

			if ( $userQuizModel ) {
				$result = $userQuizModel->handle_finish(
					[
						'answered'   => $answered,
						'time_spend' => $time_spend,
					]
				);
			} else {
				// Guest: calculate result, not save.
				$result               = $this->create_user_quiz( 0, $quizPostModel->get_id(), $courseModel->get_id() )->calculate_quiz_result( $answered );
				$result['time_spend'] = gmdate( 'H:i:s', $time_spend );
			}

			$graduation = $userQuizModel instanceof UserQuizModel ? $userQuizModel->get_graduation() : '';
			if ( empty( $graduation ) ) {
				$graduation = ! empty( $result['pass'] ) ? UserQuizModel::GRADUATION_PASSED : UserQuizModel::GRADUATION_FAILED;
			}

			$response->data->content    = $this->get_template()->html_completed( $courseModel, $quizPostModel, $result, $userQuizModel );
			$response->data->status     = UserItemModel::STATUS_COMPLETED;
			$response->data->graduation = $graduation;
			// Summary only, result of each question is not sent (review can be disabled).
			$response->data->results = array_diff_key( $result, array_flip( [ 'questions', 'answered', 'results', 'attempts' ] ) );
			// Button finish course when course can finish after this quiz, JS show on header (page not reload).
			$response->data->html_finish_course = $context['userCourseModel'] instanceof UserCourseModel
				? $this->html_btn_finish_course( $context['userCourseModel'] )
				: '';
			$response->status                   = Response::STATUS_SUCCESS;
		} catch ( Throwable $e ) {
			$response->message = $this->get_error_message( $e );
		}

		wp_send_json( $response );
	}

	/**
	 * User instant check a question.
	 * Only when quiz enable instant check, user quiz is doing.
	 *
	 * @return void
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function user_check_question() {
		$response = new Response();

		try {
			$params  = $this->get_params();
			$context = $this->get_context( $params );
			/**
			 * @var CourseModel $courseModel
			 * @var QuizPostModel $quizPostModel
			 * @var UserModel|false $userModel
			 */
			[ 'courseModel' => $courseModel, 'quizPostModel' => $quizPostModel, 'userModel' => $userModel ] = $context;

			if ( ! $quizPostModel->has_instant_check() ) {
				throw new Exception( esc_html__( 'Instant check is disable', 'learnpress' ) );
			}

			$question_id = absint( $params['question_id'] ?? 0 );
			if ( ! in_array( $question_id, array_map( 'absint', $quizPostModel->get_question_ids() ), true ) ) {
				throw new Exception( esc_html__( 'The quiz question is invalid', 'learnpress' ) );
			}

			$questionPostModel = $this->find_question( $question_id );
			if ( ! $questionPostModel instanceof QuestionPostModel ) {
				throw new Exception( esc_html__( 'The question is invalid', 'learnpress' ) );
			}

			$answered = $this->sanitize_answer( $params['answered'] ?? '' );

			if ( $context['is_guest'] ) {
				$checked             = $questionPostModel->check( $answered );
				$checked['answered'] = $answered;
			} else {
				$userQuizModel = UserQuizModel::find_user_item( $userModel->get_id(), $quizPostModel->get_id(), LP_QUIZ_CPT, $courseModel->get_id(), LP_COURSE_CPT );
				// Check on quiz completed will change result saved.
				if ( ! $userQuizModel instanceof UserQuizModel || ! $userQuizModel->has_started() ) {
					throw new Exception( esc_html__( 'User quiz is invalid!', 'learnpress' ) );
				}

				$checked = $userQuizModel->instant_check_question( $question_id, $answered );
			}

			$response->data->content = $this->get_template()->html_check_quiz( $courseModel, $quizPostModel, $questionPostModel, $checked );
			$response->data->correct = (bool) ( $checked['correct'] ?? false );
			$response->data->mark    = (float) ( $checked['mark'] ?? 0 );
			// Point of question default 1 when not set, same as display.
			$response->data->point = (float) $questionPostModel->get_mark() > 0 ? (float) $questionPostModel->get_mark() : 1.0;
			// Point minus when answer wrong and quiz enable negative marking, JS update Earned Point.
			$response->data->minus = ! $response->data->correct && $quizPostModel->has_negative_marking() ? $response->data->point : 0.0;
			$response->status      = Response::STATUS_SUCCESS;
		} catch ( Throwable $e ) {
			$response->message = $this->get_error_message( $e );
		}

		wp_send_json( $response );
	}

	/**
	 * Time spent (seconds) to save when finish quiz, not more than duration of quiz.
	 * User logged in: calculated on server from start time, not trust the time sent by browser.
	 * Guest (nothing is saved, no start time on server): keep the time sent by browser.
	 *
	 * @param int $time_spend sent by browser
	 * @param QuizPostModel $quizPostModel
	 * @param UserQuizModel|false $userQuizModel
	 *
	 * @return int
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	protected function get_time_spend( int $time_spend, QuizPostModel $quizPostModel, $userQuizModel ): int {
		if ( $userQuizModel instanceof UserQuizModel ) {
			$time_spend = max( 0, time() - strtotime( $userQuizModel->get_start_time() ) );
		}

		$duration = ( new LP_Duration( $quizPostModel->get_duration() ) )->get();

		return $duration > 0 ? min( $time_spend, $duration ) : $time_spend;
	}

	/**
	 * Message show to user: message of Exception (thrown by Model / this class for user),
	 * other errors (PHP Error...) are logged, not show to avoid exposing file paths.
	 *
	 * @param Throwable $e
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	protected function get_error_message( Throwable $e ): string {
		if ( $e instanceof Exception ) {
			return $e->getMessage();
		}

		LP_Debug::error_log( $e );

		return esc_html__( 'Something went wrong, please try again!', 'learnpress' );
	}

	/**
	 * Sanitize answers submitted, only keep questions of quiz.
	 *
	 * @param mixed $answered [ question_id => answer ]
	 * @param int[] $question_ids questions of quiz
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	protected function sanitize_answered( $answered, array $question_ids ): array {
		$sanitized = [];
		if ( ! is_array( $answered ) ) {
			return $sanitized;
		}

		$question_ids = array_map( 'absint', $question_ids );
		foreach ( $answered as $question_id => $answer ) {
			$question_id = absint( $question_id );
			if ( ! in_array( $question_id, $question_ids, true ) ) {
				continue;
			}

			$sanitized[ $question_id ] = $this->sanitize_answer( $answer );
		}

		return $sanitized;
	}

	/**
	 * Sanitize answer of a question: string (single choice), array of strings (multi choice, sorting),
	 * [ blank_id => fill ] (FIB). Keep text as user typed for compare, it is escaped when display.
	 *
	 * @param mixed $answer
	 *
	 * @return string|array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	protected function sanitize_answer( $answer ) {
		if ( is_scalar( $answer ) ) {
			return (string) $answer;
		}

		if ( ! is_array( $answer ) ) {
			return '';
		}

		$sanitized = [];
		foreach ( $answer as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$sanitized[ is_int( $key ) ? $key : sanitize_text_field( (string) $key ) ] = (string) $value;
		}

		return $sanitized;
	}

	/**
	 * Get params from request, key 'data' is JSON string sent by window.lpAJAXG.fetchAJAX.
	 *
	 * @return array
	 * @throws Exception
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	protected function get_params(): array {
		// Not sanitize_text_field JSON string, it changes user answers (FIB). Values are sanitized after decode.
		$data   = LP_Request::get_param( 'data', '', 'wp_check_invalid_utf8' );
		$params = is_string( $data ) ? LP_Helper::json_decode( $data, true ) : null;
		if ( ! is_array( $params ) ) {
			throw new Exception( esc_html__( 'Params invalid!', 'learnpress' ) );
		}

		$params['quiz_id']   = absint( $params['quiz_id'] ?? 0 );
		$params['course_id'] = absint( $params['course_id'] ?? 0 );
		if ( ! $params['quiz_id'] || ! $params['course_id'] ) {
			throw new Exception( esc_html__( 'Params invalid!', 'learnpress' ) );
		}

		return $params;
	}

	/**
	 * Check request valid and get models.
	 * Course valid, quiz is item of course, not password protected, user enrolled (or guest when course no required enroll).
	 *
	 * @param array $params [ quiz_id, course_id ]
	 *
	 * @return array [ courseModel, quizPostModel, userModel, userCourseModel, is_guest ]
	 * @throws Exception
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	protected function get_context( array $params ): array {
		$quiz_id   = absint( $params['quiz_id'] ?? 0 );
		$course_id = absint( $params['course_id'] ?? 0 );

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel instanceof CourseModel ) {
			throw new Exception( esc_html__( 'The course is invalid', 'learnpress' ) );
		}

		// Quiz must be item of course.
		$quizPostModel = $courseModel->get_item_model( $quiz_id, LP_QUIZ_CPT );
		if ( ! $quizPostModel instanceof QuizPostModel ) {
			throw new Exception( esc_html__( 'The quiz is invalid', 'learnpress' ) );
		}

		foreach ( [ $quiz_id, $course_id ] as $post_id ) {
			if ( post_password_required( $post_id ) ) {
				throw new Exception( esc_html__( 'This content is password protected.', 'learnpress' ) );
			}
		}

		$user_id   = get_current_user_id();
		$userModel = UserModel::find( $user_id, true );

		$context = [
			'courseModel'     => $courseModel,
			'quizPostModel'   => $quizPostModel,
			'userModel'       => $userModel,
			'userCourseModel' => false,
			'is_guest'        => false,
		];

		// Course no required enroll, guest can do quiz, not save to database.
		if ( ! $userModel instanceof UserModel ) {
			if ( ! $courseModel->has_no_enroll_requirement() ) {
				throw new Exception( esc_html__( 'You have to log in to start the quiz.', 'learnpress' ) );
			}

			$context['is_guest'] = true;

			return $context;
		}

		$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
		if ( ! $userCourseModel instanceof UserCourseModel ) {
			throw new Exception( esc_html__( 'User not enrolled course!', 'learnpress' ) );
		}

		$context['userCourseModel'] = $userCourseModel;

		return $context;
	}


	/**
	 * @param int $question_id
	 *
	 * @return QuestionPostModel|false
	 */
	protected function find_question( int $question_id ) {
		return QuestionPostModel::find( $question_id, true );
	}

	/**
	 * New user quiz (not saved), save when start.
	 *
	 * @param int $user_id
	 * @param int $quiz_id
	 * @param int $course_id
	 *
	 * @return UserQuizModel
	 */
	protected function create_user_quiz( int $user_id, int $quiz_id, int $course_id ): UserQuizModel {
		$userQuizModel          = new UserQuizModel();
		$userQuizModel->user_id = $user_id;
		$userQuizModel->item_id = $quiz_id;
		$userQuizModel->ref_id  = $course_id;

		return $userQuizModel;
	}

	/**
	 * HTML button finish course, empty when user course can not finish.
	 * Clear result of course calculated on this request, it must include quiz just finished.
	 *
	 * @param UserCourseModel $userCourseModel
	 *
	 * @return string
	 */
	protected function html_btn_finish_course( UserCourseModel $userCourseModel ): string {
		LP_Cache::cache_load_first( 'clear', sprintf( 'calculate_course/%d/%d', $userCourseModel->user_id, $userCourseModel->item_id ) );

		return UserCourseTemplate::instance()->html_btn_finish( $userCourseModel );
	}

	/**
	 * @return UserQuizTemplate
	 */
	protected function get_template(): UserQuizTemplate {
		return UserQuizTemplate::instance();
	}
}
