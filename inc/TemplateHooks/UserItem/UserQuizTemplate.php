<?php
namespace LearnPress\TemplateHooks\UserItem;

use Exception;
use LearnPress\Ajax\UserQuizAjax;
use LearnPress\Databases\DataBase;
use LearnPress\Helpers\LPDateTime;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Learning\QuestionTemplate;
use LearnPress\TemplateHooks\Learning\QuizTemplate;
use LearnPress\TemplateHooks\Table\TableListTemplate;
use LP_Global;
use LP_Settings;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * template hooks user quiz.
 *
 * render html of the quiz screens for user, replace the react app.
 * markup keeps the class names of the old react app, so the old style still applies.
 *
 * @since 4.5.0
 * @version 1.0.0
 */
class UserQuizTemplate extends UserItemBaseTemplate {
	public static function instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Print HTML of the quiz content being viewed.
	 * Called from hook 'learn-press/content-item-summary/lp_quiz'.
	 *
	 * @return void
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function print_quiz_content(): void {
		global $lpCourseModel;

		$courseModel = $lpCourseModel;
		if ( ! $courseModel instanceof CourseModel ) {
			return;
		}

		$quiz = LP_Global::course_item_quiz();
		if ( ! $quiz ) {
			return;
		}

		$quizPostModel = QuizPostModel::find( $quiz->get_id(), true );
		if ( ! $quizPostModel instanceof QuizPostModel ) {
			return;
		}

		$userModel = UserModel::find( get_current_user_id(), true );

		echo $this->html_quiz_content( $courseModel, $quizPostModel, $userModel );
	}

	/**
	 * Render HTML of the quiz content based on user quiz status.
	 * Chooses appropriate screen (intro, doing, or completed) based on user quiz status.
	 *
	 * @param CourseModel $courseModel The course model.
	 * @param QuizPostModel $quizPostModel The quiz post model.
	 * @param UserModel|false $userModel The user model, false for guest.
	 * @param array $args Additional arguments.
	 *
	 * @return string HTML of the quiz content.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_quiz_content(
		CourseModel $courseModel,
		QuizPostModel $quizPostModel,
		$userModel = false,
		array $args = []
	): string {
		$quiz_id = $quizPostModel->get_id();
		if ( post_password_required( $quiz_id ) ) {
			return '';
		}

		$userQuizModel = false;
		if ( $userModel instanceof UserModel ) {
			$userQuizModel = UserQuizModel::find_user_item(
				$userModel->get_id(),
				$quiz_id,
				LP_QUIZ_CPT,
				$courseModel->get_id(),
				LP_COURSE_CPT,
				true
			);
		}

		$status = $userQuizModel instanceof UserQuizModel ? $userQuizModel->get_status() : '';

		$section = [
			'wrapper'     => sprintf(
				'<div id="learn-press-user-quiz"
					class="lp-user-quiz"
					data-quiz-id="%d"
					data-course-id="%d"
					data-status="%s">',
				esc_attr( $quiz_id ),
				esc_attr( $courseModel->get_id() ),
				esc_attr( $status )
			),
			'title'       => sprintf(
				'<h1 class="course-item-title quiz-title">%s</h1>',
				$quizPostModel->get_the_title()
			),
			'description' => $userQuizModel instanceof UserQuizModel
				? ''
				: QuizTemplate::instance()->html_description( $quizPostModel ),
			'screen'      => $quizPostModel->count_questions() > 0
				? $this->html_screen(
					array_merge(
						$args,
						[
							'courseModel'   => $courseModel,
							'quizPostModel' => $quizPostModel,
							'userQuizModel' => $userQuizModel,
						]
					)
				)
				: Template::print_message( __( 'You haven\'t any question!', 'learnpress' ), 'info', false ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-content', $section, $courseModel, $quizPostModel, $userQuizModel )
		);
	}

	/**
	 * Render HTML of the appropriate screen based on user quiz status.
	 * Returns intro screen for not started, doing screen for started, completed screen for finished.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *   - userQuizModel: UserQuizModel instance or false.
	 *
	 * @return string HTML of the screen.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_screen( array $args ): string {
		$userQuizModel = $args['userQuizModel'] ?? false;
		$status        = $userQuizModel instanceof UserQuizModel ? $userQuizModel->get_status() : '';

		switch ( $status ) {
			case UserQuizModel::STATUS_STARTED:
				$html = $this->html_doing_quiz( $args );
				break;
			case UserItemModel::STATUS_COMPLETED:
				$html = $this->html_completed( array_merge( $args, [ 'result' => $userQuizModel->get_result() ] ) );
				break;
			default:
				$html = $this->html_intro( $args );
				break;
		}

		return $html;
	}

	/**
	 * Render HTML of the quiz intro screen.
	 * Displays quiz meta information, start button, and last attempts.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *   - userQuizModel: UserQuizModel instance or false.
	 *
	 * @return string HTML of the intro screen.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_intro( array $args ): string {
		$quizPostModel = $args['quizPostModel'] ?? false;
		if ( ! $quizPostModel instanceof QuizPostModel ) {
			return '';
		}

		$section = [
			'meta'     => QuizTemplate::instance()->html_meta( $quizPostModel ),
			'buttons'  => sprintf(
				'<div class="quiz-buttons">
					<div class="button-left">%s</div>
					<div class="button-right"></div>
				</div>',
				$this->html_btn_start( $args )
			),
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-intro', $section, $args )
		);
	}

	/**
	 * Render HTML of the start quiz button.
	 * Includes AJAX data for starting the quiz.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *
	 * @return string HTML of the start button.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_btn_start( array $args ): string {
		$courseModel   = $args['courseModel'] ?? false;
		$quizPostModel = $args['quizPostModel'] ?? false;

		if ( post_password_required( $quizPostModel->get_id() ) ) {
			return '';
		}

		$html = sprintf(
			'<button type="button"
					class="lp-button start"
					data-send="%s">%s</button>',
			Template::convert_data_to_json(
				[
					'id_url'    => 'user-start-quiz',
					/** @use UserQuizAjax::user_start_quiz */
					'action'    => 'user_start_quiz',
					'quiz_id'   => $quizPostModel->get_id(),
					'course_id' => $courseModel->get_id(),
				]
			),
			esc_html__( 'Start', 'learnpress' )
		);

		return apply_filters( 'learn-press/user/quiz/html-button-start', $html, $courseModel, $quizPostModel );
	}

	/**
	 * Render HTML of the quiz doing screen.
	 * Includes status bar with timer, questions list, pagination, and finish button.
	 * For guests (courses without enrollment), uses default data as there is no user quiz model.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *   - userQuizModel: UserQuizModel instance or false.
	 *
	 * @return string HTML of the doing quiz screen.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_doing_quiz( array $args ): string {
		$courseModel   = $args['courseModel'] ?? false;
		$quizPostModel = $args['quizPostModel'] ?? false;

		if ( ! $courseModel instanceof CourseModel
			|| ! $quizPostModel instanceof QuizPostModel ) {
			return '';
		}

		$total     = $quizPostModel->count_questions();
		$per_page  = $quizPostModel->get_pagination();
		$num_pages = $per_page > 0 ? DataBase::get_total_pages( $per_page, $total ) : 1;

		$section = [
			'wrap'     => sprintf(
				'<div class="lp-quiz-doing"
					data-per-page="%d"
					data-total="%d"
					data-num-pages="%d">',
				$per_page,
				$total,
				$num_pages
			),
			'bar-info'    => $this->html_doing_bar_info( $args ),
			'questions'   => $this->html_questions( $args ),
			'pagination'  => $this->html_questions_pagination( [ 'num_pages' => $num_pages ] ),
			'wrap-end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-doing', $section, $args )
		);
	}

	/**
	 * Render HTML of the info bar when doing quiz.
	 * Displays questions index, finish button, and countdown timer.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *
	 * @return string HTML of the info bar.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_doing_bar_info( array $args ): string {
		$courseModel   = $args['courseModel'] ?? false;
		$quizPostModel = $args['quizPostModel'] ?? false;

		if ( ! $courseModel instanceof CourseModel
			|| ! $quizPostModel instanceof QuizPostModel ) {
			return '';
		}

		$total     = $quizPostModel->count_questions();
		$per_page  = $quizPostModel->get_pagination();
		$num_pages = $per_page > 0 ? DataBase::get_total_pages( $per_page, $total ) : 1;

		$end = $per_page > 0 ? min( $per_page, $num_pages ) : $num_pages;

		$section = [
			'wrapper'     => '<div class="quiz-doing-bar-info">',
			'index'       => sprintf(
				'<div class="questions-index">%s</div>',
				$this->html_questions_index(
					[
						'start'     => 1,
						'end'       => $end,
						'total'     => $total,
						'per_page'  => $per_page,
						'num_pages' => $num_pages,
					]
				)
			),
			'submit'      => sprintf(
				'<div class="quiz-doing-bar-info__right">
					<div class="submit-quiz">%s</div>%s
				</div>',
				$this->html_btn_finish( $args ),
				$this->html_timer( $args )
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-doing-bar-info', $section, $courseModel, $quizPostModel, $args )
		);
	}

	/**
	 * Render HTML of the questions index for the current page.
	 * Shows different formats based on pagination:
	 * - "10 questions" when single page.
	 * - "Question 1 of 10" when per_page = 1.
	 * - "Question 1 to 5 of 10" when per_page > 1.
	 *
	 * @param array $args Arguments containing:
	 *   - start: Starting question number.
	 *   - end: Ending question number.
	 *   - total: Total number of questions.
	 *   - per_page: Questions per page.
	 *   - num_pages: Total number of pages.
	 *
	 * @return string HTML of the questions index.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_questions_index( array $args ): string {
		$start     = (int) ( $args['start'] ?? 1 );
		$end       = (int) ( $args['end'] ?? 0 );
		$total     = (int) ( $args['total'] ?? 0 );
		$per_page  = (int) ( $args['per_page'] ?? 0 );
		$num_pages = (int) ( $args['num_pages'] ?? 1 );

		$html = '';

		// Case 1: num_pages = 1 - show total questions
		$html .= sprintf(
			'<span class="index-total%s">%s</span>',
			$num_pages === 1 ? '' : ' lp-hidden',
			sprintf( _n( '%s question', '%s questions', $total, 'learnpress' ), $total )
		);

		// Case 2: per_page = 1 and num_pages > 1 - show single question index
		$html .= sprintf(
			'<span class="index-single%s">Question <span class="i-current">%d</span> of %d</span>',
			$num_pages > 1 && $per_page === 1 ? '' : ' lp-hidden',
			$start,
			$total
		);

		// Case 3: per_page > 1 and num_pages > 1 - show range
		$html .= sprintf(
			'<span class="index-range%s">Question <span class="i-start">%d</span> to <span class="i-end">%d</span> of %d</span>',
			$num_pages > 1 && $per_page > 1 ? '' : ' lp-hidden',
			$start,
			$end,
			$total
		);

		return $html;
	}

	/**
	 * Render HTML of the countdown timer when doing quiz.
	 * Counts down from remaining time if duration > 0, otherwise counts up from time spent.
	 *
	 * @param array $args Arguments containing:
	 *   - quizPostModel: QuizPostModel instance.
	 *   - userQuizModel: UserQuizModel instance or false.
	 *
	 * @return string HTML of the timer.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_timer( array $args ): string {
		$quizPostModel = $args['quizPostModel'] ?? false;
		$userQuizModel = $args['userQuizModel'] ?? false;
		$time_spend    = 0;
		$remaining     = 0;

		if ( ! $quizPostModel instanceof QuizPostModel ) {
			return '';
		}

		$duration = LPDateTime::get_duration_in_seconds( $quizPostModel->get_duration() );

		if ( $userQuizModel instanceof UserQuizModel ) {
			$time_spend = max( 0, time() - strtotime( $userQuizModel->get_start_time() ) );
			if ( $duration > 0 ) {
				$remaining = $userQuizModel->get_time_remaining();
			}
		} else {
			$remaining = $duration;
		}

		$seconds = $duration > 0 ? $remaining : $time_spend;

		$html = sprintf(
			'<div class="countdown"
				data-duration="%1$d"
				data-remaining="%2$d"
				data-time-spend="%3$d">
				<i class="lp-icon-stopwatch"></i>
				<span>%4$s</span>
				<input type="hidden" name="lp-quiz-time-spend" value="%3$d"/>
			</div>',
			esc_attr( $duration ),
			esc_attr( $remaining ),
			esc_attr( $time_spend ),
			esc_html(
				$this->format_timer(
					[
						'seconds' => $seconds,
						'total'   => $duration > 0 ? $duration : $seconds,
					]
				)
			)
		);

		return apply_filters( 'learn-press/user/quiz/html-timer', $html, $duration, $remaining, $time_spend );
	}

	/**
	 * Format timer display.
	 * Returns MM:SS format when total < 1 hour, otherwise HH:MM:SS format.
	 *
	 * @param array $args Arguments containing:
	 *   - seconds: Time in seconds to format.
	 *   - total: Total time in seconds (used to choose format).
	 *
	 * @return string Formatted time string.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function format_timer( array $args ): string {
		$seconds = (int) ( $args['seconds'] ?? 0 );
		$total   = (int) ( $args['total'] ?? 0 );

		if ( $seconds <= 0 ) {
			return '00:00';
		}

		if ( $total < 3600 ) {
			return sprintf( '%02d:%02d', floor( $seconds / 60 ), $seconds % 60 );
		}

		return sprintf( '%02d:%02d:%02d', floor( $seconds / 3600 ), floor( ( $seconds % 3600 ) / 60 ), $seconds % 60 );
	}

	/**
	 * Render HTML of all questions when doing quiz.
	 * All questions are rendered, with questions not on the first page hidden.
	 * JavaScript handles showing/hiding by page.
	 * Instant checked questions show results, persisting on page reload.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *   - userQuizModel: UserQuizModel instance or false.
	 *
	 * @return string HTML of the questions list.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_questions( array $args ): string {
		$quizPostModel = $args['quizPostModel'] ?? false;
		$userQuizModel = $args['userQuizModel'] ?? false;

		if ( ! $quizPostModel instanceof QuizPostModel ) {
			return '';
		}

		$result_questions = [];
		$question_ids     = $quizPostModel->get_question_ids();

		if ( $userQuizModel instanceof UserQuizModel ) {
			$result_questions = $userQuizModel->get_result()['questions'] ?? [];
		}

		$html_questions = '';
		foreach ( $question_ids as $index => $question_id ) {
			$questionPostModel = QuestionPostModel::find( (int) $question_id, true );
			if ( ! $questionPostModel instanceof QuestionPostModel ) {
				continue;
			}

			$args['questionPostModel'] = $questionPostModel;
			$args['position']          = $index;
			$args['result_questions']  = $result_questions;

			$html_questions .= $this->html_question( $args );
		}

		return apply_filters(
			'learn-press/user/quiz/html-questions',
			sprintf( '<div class="quiz-questions">%s</div>', $html_questions ),
			$args
		);
	}

	/**
	 * Render HTML of a single question.
	 * Includes title, content, answers, explanation, hint, and instant check button.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *   - questionPostModel: QuestionPostModel instance.
	 *   - position: Question position in quiz (0-indexed).
	 *   - result_questions: Array of question results keyed by question_id, each has [answered, mark, correct].
	 *   - userQuizModel: UserQuizModel instance or false.
	 *   - action_type: Action type (instant_check_question, submit_quiz, etc.).
	 *   - is_guest: Whether user is guest.
	 *   - show_result: Whether to show question result.
	 *   - show_correct: Whether to show correct answer.
	 *   - correct: Correct answer value.
	 *   - mark: Question mark.
	 *
	 * @return string HTML of the question.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_question( array $args ): string {
		$quizPostModel     = $args['quizPostModel'] ?? false;
		$questionPostModel = $args['questionPostModel'] ?? false;
		$userQuizModel     = $args['userQuizModel'] ?? false;
		$result_questions  = $args['result_questions'] ?? [];
		$action_type       = $args['action_type'] ?? '';
		$is_guest          = $args['is_guest'] ?? false;

		if ( ! $quizPostModel instanceof QuizPostModel
			|| ! $questionPostModel instanceof QuestionPostModel ) {
			return '';
		}

		$questionTemplate        = QuestionTemplate::instance();
		$question_answer_checked = $result_questions[ $questionPostModel->get_id() ] ?? false;

		$show_explanation = false;
		if ( $userQuizModel instanceof UserQuizModel ) {
			$show_explanation = $userQuizModel->has_finished()
				|| ( $question_answer_checked && ! empty( $question_answer_checked['answered'] ) );
		} elseif ( $is_guest
			&& ( $action_type === 'instant_check_question'
				|| $action_type === 'submit_quiz' ) ) {
			$show_explanation = true;
		}

		$index    = (int) ( $args['position'] ?? 0 );
		$per_page = $quizPostModel->get_pagination();
		$page     = $per_page > 0 ? (int) floor( $index / $per_page ) + 1 : 1;

		// Default args of question when doing quiz, caller can override (review...).
		$args = array_merge(
			[
				'show_result'      => $show_explanation, // Show result when question checked or quiz completed
				'show_correct'     => $show_explanation && $quizPostModel->has_show_correct_review(),
				'correct'          => $question_answer_checked['correct'] ?? null,
				'mark'             => $question_answer_checked['mark'] ?? 0,
			],
			$args
		);

		$classes = [ 'question', 'question-' . $questionPostModel->get_type() ];
		if ( $args['show_correct'] ) {
			$classes[] = 'question-answered';
		}

		if ( $action_type !== 'instant_check_question' ) {
			//$classes[] = 'lp-hidden';
		}

		$section = [
			'wrapper'     => sprintf(
				'<div class="%1$s" data-id="%2$d" data-page="%3$d">',
				esc_attr( implode( ' ', $classes ) ),
				$questionPostModel->get_id(),
				$page,
			),
			'title'       => $this->html_question_title( $args ),
			'hint'        => sprintf(
				'<div class="question-hint-content lp-hidden">%s</div>',
				$questionTemplate->html_hint( $questionPostModel )
			),
			'content'     => $questionTemplate->html_content( $questionPostModel ),
			'answers'     => $questionTemplate->html_answers( $questionPostModel, $args ),
			'explanation' => $show_explanation ? $questionTemplate->html_explanation( $questionPostModel ) : '',
			'btn-instant-check' => $this->html_btn_instant_check( $args ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/question/html-question', $section, $questionPostModel, $args )
		);
	}

	/**
	 * Render HTML of the question title.
	 * Includes question index, title, hint button, and edit link (if user can edit).
	 *
	 * @param array $args Arguments containing:
	 *   - questionPostModel: QuestionPostModel instance.
	 *   - position: Question position (0-indexed).
	 *   - userQuizModel: UserQuizModel instance or false.
	 *   - action_type: Action type.
	 *   - is_guest: Whether user is guest.
	 *
	 * @return string HTML of the question title.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_question_title( array $args ): string {
		$userQuizModel     = $args['userQuizModel'] ?? false;
		$questionPostModel = $args['questionPostModel'];
		$action_type       = $args['action_type'] ?? '';
		$is_guest          = $args['is_guest'] ?? false;
		$questionTemplate  = QuestionTemplate::instance();
		$index             = (int) ( $args['position'] ?? 0 );
		$index            += 1;

		if ( ! $questionPostModel instanceof QuestionPostModel ) {
			return '';
		}

		$show_hint = true;
		if ( $userQuizModel instanceof UserQuizModel ) {
			if ( ! $userQuizModel->has_started() ) {
				$show_hint = false;
			}
		} elseif ( $is_guest
			&& ( $action_type === 'instant_check_question'
				|| $action_type === 'submit_quiz' ) ) {
			$show_hint = false;
		}

		$section = [
			'wrapper'     => '<h4 class="user-question-title">',
			'index'       => $index > 0 ? sprintf( '<span class="question-index">%d.</span>', $index ) : '',
			'title'       => $questionTemplate->html_title( $questionPostModel ),
			'icon-hint'        => $show_hint && ! empty( $questionTemplate->html_hint( $questionPostModel ) )
				? sprintf(
					'<span class="btn-show-hint lp-tooltip" data-tip="%s" tabindex="0">
						<span class="lp-icon-question-circle"></span>
					</span>',
					esc_attr__( 'View hint', 'learnpress' )
				)
				: '',
			'edit_link'   => $questionPostModel->check_capabilities_update()
				? sprintf(
					'<span class="edit-link">
						<a href="%s">%s</a>
					</span>',
					esc_url( $questionPostModel->get_edit_link() ),
					esc_html__( 'Edit', 'learnpress' )
				)
				: '',
			'wrapper_end' => '</h4>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/question/html-title', $section, $args )
		);
	}

	/**
	 * Render HTML of the instant check question button.
	 * Only shown when quiz has instant check enabled and question hasn't been checked yet.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *   - questionPostModel: QuestionPostModel instance.
	 *   - result_questions: Array of question results.
	 *
	 * @return string HTML of the instant check button.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_btn_instant_check( array $args ): string {
		$courseModel       = $args['courseModel'] ?? false;
		$quizPostModel     = $args['quizPostModel'] ?? false;
		$questionPostModel = $args['questionPostModel'] ?? false;
		$result_questions  = $args['result_questions'] ?? [];

		if ( ! $courseModel instanceof CourseModel
			|| ! $quizPostModel instanceof QuizPostModel
			|| ! $questionPostModel instanceof QuestionPostModel
			|| ! $quizPostModel->has_instant_check() ) {
			return '';
		}

		if ( isset( $result_questions[ $questionPostModel->get_id() ] ) ) {
			return '';
		}

		$html = sprintf(
			'<button type="button" class="lp-button instant-check" data-send="%1$s">
				<span class="instant-check__icon"></span>%2$s
				<div class="instant-check__info">%3$s</div>
			</button>',
			Template::convert_data_to_json(
				[
					'id_url'      => 'user-check-question',
					/** @use UserQuizAjax::user_check_answer_question */
					'action'      => 'user_check_answer_question',
					'quiz_id'     => $quizPostModel->get_id(),
					'course_id'   => $courseModel->get_id(),
					'question_id' => $questionPostModel->get_id(),
				]
			),
			esc_html__( 'Check answers', 'learnpress' ),
			esc_html__( 'You need to answer the question before checking the answer key.', 'learnpress' )
		);

		return apply_filters( 'learn-press/user/quiz/html-button-instant-check', $html, $args );
	}

	/**
	 * Render HTML of the finish quiz button.
	 * Includes confirmation dialog data for SweetAlert.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *
	 * @return string HTML of the finish button.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_btn_finish( array $args ): string {
		$courseModel   = $args['courseModel'] ?? false;
		$quizPostModel = $args['quizPostModel'] ?? false;

		if ( ! $courseModel instanceof CourseModel ) {
			return '';
		}

		if ( ! $quizPostModel instanceof QuizPostModel ) {
			return '';
		}

		$html = sprintf(
			'<button type="button"
				class="lp-button submit-quiz"
				data-title="%1$s"
				data-content="%2$s"
				data-send="%3$s">%4$s
			</button>',
			// Confirm popup (SweetAlert) read data-title, data-content.
			esc_attr__( 'Finish Quiz', 'learnpress' ),
			esc_attr__( 'Are you sure to submit the quiz?', 'learnpress' ),
			Template::convert_data_to_json(
				[
					'id_url'    => 'user-submit-quiz',
					'action'    => 'user_submit_quiz',
					/** @use UserQuizAjax::user_submit_quiz */
					'quiz_id' => $quizPostModel->get_id(),
					'course_id' => $courseModel->get_id(),
				]
			),
			esc_html__( 'Finish Quiz', 'learnpress' )
		);

		return apply_filters( 'learn-press/user/quiz/html-button-finish', $html, $args );
	}

	/**
	 * Render HTML of the questions pagination.
	 * Creates one pagination per page, JavaScript shows only the current page's pagination.
	 * Page links use '#page-{n}' format for JavaScript to read.
	 *
	 * @param array $args Arguments containing:
	 *   - num_pages: Total number of pages.
	 *
	 * @return string HTML of the pagination.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_questions_pagination( array $args ): string {
		$num_pages = (int) ( $args['num_pages'] ?? 0 );

		if ( $num_pages <= 1 ) {
			return '';
		}

		$html = '';
		for ( $page = 1; $page <= $num_pages; $page++ ) {
			$html .= sprintf(
				'<div class="questions-pagination__item"
					data-page="%1$d"%2$s>%3$s
				</div>',
				$page,
				1 === $page ? '' : ' hidden',
				Template::instance()->html_pagination(
					[
						'total_pages' => $num_pages,
						'paged'       => $page,
						'base'        => '#page-%#%',
					]
				)
			);
		}

		return sprintf( '<div class="questions-pagination">%s</div>', $html );
	}

	/**
	 * Render HTML of the completed quiz screen.
	 * Displays quiz result, retake/review buttons, attempt history, and review questions (hidden by default).
	 * For guests, result is calculated on submit since there is no user quiz model.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *   - result: Quiz result array (see UserQuizModel::get_result()).
	 *   - userQuizModel: UserQuizModel instance or false.
	 *
	 * @return string HTML of the completed screen.
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.5.0
	 */
	public function html_completed( array $args ): string {
		$userQuizModel = $args['userQuizModel'] ?? false;

		$section = [
			'wrap'     => '<div class="lp-quiz-completed">',
			'result_wrap' => '<div class="lp-quiz-result-wrap">',
			'result'      => $this->html_result( $args ),
			'buttons'     => sprintf(
				'<div class="quiz-buttons">
					%s
					%s
				</div>',
				$this->html_btn_retake( $args ),
				$this->html_btn_review( $args )
			),
			'history'     => $userQuizModel instanceof UserQuizModel ? $this->html_history( $args ) : '',
			'result-end'  => '</div>',
			'review'      => $this->html_review_result( $args ),
			'wrap-end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-completed', $section, $args )
		);
	}

	/**
	 * Render HTML of the quiz result.
	 * Displays circular progress grade, pass/fail message, and statistics.
	 *
	 * @param array $args Arguments containing:
	 *   - quizPostModel: QuizPostModel instance.
	 *   - result: Quiz result array with keys: result, user_mark, mark, question_count, etc.
	 *   - userQuizModel: UserQuizModel instance or false.
	 *
	 * @return string HTML of the result.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_result( array $args ): string {
		$quizPostModel = $args['quizPostModel'] ?? false;
		$result        = (array) ( $args['result'] ?? [] );
		$userQuizModel = $args['userQuizModel'] ?? false;

		if ( ! $quizPostModel instanceof QuizPostModel ) {
			return '';
		}

		$percent       = (float) ( $result['result'] ?? 0 );
		$passing_grade = $quizPostModel->get_passing_grade();

		$graduation = $userQuizModel instanceof UserQuizModel ? $userQuizModel->get_graduation() : '';

		// Circle progress, same size as old.
		$border        = 10;
		$width         = 200;
		$r             = ( $width - $border ) / 2;
		$circumference = $r * 2 * M_PI;
		$offset        = $circumference - ( min( max( $percent, 0 ), 100 ) / 100 * $circumference );

		$statistic = [
			'result-time-spend'        => [ __( 'Time spent', 'learnpress' ), ! empty( $result['time_spend'] ) ? $result['time_spend'] : '--' ],
			'result-point'             => [
				__( 'Points', 'learnpress' ),
				sprintf( '%s / %s', $this->format_number( [ 'number' => $result['user_mark'] ?? 0 ] ), $this->format_number( [ 'number' => $result['mark'] ?? 0 ] ) ),
			],
			'result-questions'         => [ __( 'Questions', 'learnpress' ), $result['question_count'] ?? 0 ],
			'result-questions-correct' => [ __( 'Correct', 'learnpress' ), $result['question_correct'] ?? 0 ],
			'result-questions-wrong'   => [ __( 'Wrong', 'learnpress' ), $result['question_wrong'] ?? 0 ],
			'result-questions-skipped' => [ __( 'Skipped', 'learnpress' ), $result['question_empty'] ?? 0 ],
		];
		if ( $quizPostModel->has_negative_marking() || $quizPostModel->has_minus_skip_questions() ) {
			$statistic['result-questions-minus'] = [ __( 'Minus points', 'learnpress' ), $this->format_number( [ 'number' => $result['minus_point'] ?? 0 ] ) ];
		}

		$html_statistic = '';
		foreach ( $statistic as $class => $field ) {
			$html_statistic .= sprintf(
				'<li class="result-statistic-field %1$s">
					<span>%2$s</span>
					<p>%3$s</p>
				</li>',
				esc_attr( $class ),
				esc_html( $field[0] ),
				esc_html( (string) $field[1] )
			);
		}

		$section = [
			'wrapper'     => sprintf( '<div class="quiz-result %s">', esc_attr( $graduation ) ),
			'heading'     => sprintf( '<h3 class="result-heading">%s</h3>', esc_html__( 'Your Result', 'learnpress' ) ),
			'grade'       => sprintf(
				'<div id="quizResultGrade"
					class="result-grade" data-result="%1$s">
					<svg class="circle-progress-bar" width="%2$d" height="%2$d">
						<circle class="circle-progress-bar__circle"
							stroke=""
							stroke-width="%3$d"
							style="stroke-dasharray: %4$s %4$s;
							stroke-dashoffset: %5$s;"
							fill="transparent" r="%6$s" cx="%7$s" cy="%7$s"></circle>
					</svg>
					<span class="result-achieved">%8$s%%</span>
					<span class="result-require">%9$s%%</span>
				</div>',
				esc_attr( (string) $percent ),
				$width,
				$border,
				esc_attr( (string) round( $circumference, 3 ) ),
				esc_attr( (string) round( $offset, 3 ) ),
				esc_attr( (string) $r ),
				esc_attr( (string) ( $width / 2 ) ),
				esc_html( $this->format_number( [ 'number' => $percent ] ) ),
				esc_html( $this->format_number( [ 'number' => $passing_grade ] ) )
			),
			'message'     => sprintf(
				'<p class="result-message">%s</p>',
				esc_html(
					apply_filters(
						'learn-press/user-quiz-graduation-text',
						$userQuizModel ? $userQuizModel->get_graduation_label() : ''
					)
				)
			),
			'statistic'   => sprintf( '<ul class="result-statistic">%s</ul>', $html_statistic ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-result', $section, $quizPostModel, $result, $userQuizModel )
		);
	}

	/**
	 * Render HTML of the retake quiz button.
	 * Retake count of -1 means unlimited. For guests, retake count is stored in browser.
	 * JavaScript updates label and hides button based on data-retake-max.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *   - userQuizModel: UserQuizModel instance or false.
	 *
	 * @return string HTML of the retake button.
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.5.0
	 */
	public function html_btn_retake( array $args ): string {
		$courseModel   = $args['courseModel'] ?? false;
		$quizPostModel = $args['quizPostModel'] ?? false;
		$userQuizModel = $args['userQuizModel'] ?? false;

		if ( ! $courseModel instanceof CourseModel
			|| ! $quizPostModel instanceof QuizPostModel ) {
			return '';
		}

		if ( post_password_required( $quizPostModel->get_id() ) ) {
			return '';
		}

		$retake_max = $quizPostModel->get_retake_count();
		if ( 0 === $retake_max ) {
			return '';
		}

		$label = __( 'Retake', 'learnpress' );
		if ( $userQuizModel instanceof UserQuizModel ) {
			// Same check as retake action: retake count, course can impact item (not finished, not expired).
			$can_retake = $userQuizModel->can_retake();
			if ( is_wp_error( $can_retake ) || ! $can_retake ) {
				return '';
			}

			if ( -1 !== $retake_max ) {
				$label .= sprintf( ' (%d)', $userQuizModel->get_remaining_retake() );
			}
		}

		$html = sprintf(
			'<button type="button"
				class="lp-button start retake-quiz"
				data-retake-max="%1$d"
				data-title="%2$s"
				data-content="%3$s"
				data-send="%4$s">%5$s
			</button>',
			$retake_max,
			esc_attr__( 'Retake', 'learnpress' ),
			esc_attr__( 'Are you sure you want to retake the quiz?', 'learnpress' ),
			Template::convert_data_to_json(
				[
					'id_url'    => 'user-retake-quiz',
					/** @use UserQuizAjax::user_retake_quiz */
					'action'    => 'user_retake_quiz',
					'quiz_id'   => $quizPostModel->get_id(),
					'course_id' => $courseModel->get_id(),
				]
			),
			esc_html( $label )
		);

		return apply_filters( 'learn-press/user/quiz/html-button-retake', $html, $courseModel, $quizPostModel, $userQuizModel );
	}

	/**
	 * Render HTML of the review quiz button.
	 * Only shown when quiz review option is enabled.
	 *
	 * @param array $args Arguments containing:
	 *   - quizPostModel: QuizPostModel instance.
	 *
	 * @return string HTML of the review button.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_btn_review( array $args ): string {
		$quizPostModel = $args['quizPostModel'] ?? false;

		if ( ! $quizPostModel instanceof QuizPostModel
		|| ! $quizPostModel->has_review() ) {
			return '';
		}

		$html = sprintf(
			'<button type="button"
				class="lp-button review-quiz">%s
				</button>',
			esc_html__( 'Review', 'learnpress' )
		);

		return apply_filters( 'learn-press/user/quiz/html-button-review', $html, $args );
	}

	/**
	 * Get QuestionPostModel from snapshot in result without query.
	 * Snapshot preserves question data at save time (title, answers) to avoid changes from current question.
	 *
	 * @param array $question_result
	 *
	 * @return QuestionPostModel|false
	 */
	private function get_question_model_from_result( array $question_result ) {
		$snapshot = $question_result['questionSnapshot'] ?? false;
		if ( empty( $snapshot ) || ! is_array( $snapshot ) || empty( $snapshot['ID'] ) ) {
			return false;
		}

		$question_class = QuestionPostModel::get_question_obj_by_type(
			(string) ( $snapshot['question_type'] ?? '' )
		);
		if ( ! $question_class || ! class_exists( $question_class ) ) {
			return false;
		}

		// Snapshot keys differ from model properties.
		$snapshot['post_title']   = $snapshot['post_title'] ?? '';
		$snapshot['post_content'] = $snapshot['post_content'] ?? '';
		// Decoded JSON is array, model expects stdClass.
		$snapshot['meta_data'] = (object) ( $snapshot['meta_data'] ?? [] );

		/** @var QuestionPostModel $questionModel */
		$questionModel = new $question_class( $snapshot );

		return $questionModel;
	}

	/**
	 * Render HTML of the review questions section after quiz completion.
	 * Hidden until user clicks the review button.
	 * Only renders when quiz review option is enabled to avoid exposing answers when not allowed.
	 *
	 * @param array $args Arguments containing:
	 *   - courseModel: CourseModel instance.
	 *   - quizPostModel: QuizPostModel instance.
	 *   - result: Quiz result array with 'questions' key containing [answered, correct, mark] for each question.
	 *
	 * @return string HTML of the review section.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_review_result( array $args ): string {
		$quizPostModel = $args['quizPostModel'] ?? false;
		$result        = (array) ( $args['result'] ?? [] );

		if ( ! $quizPostModel instanceof QuizPostModel
			|| ! $quizPostModel->has_review() ) {
			return '';
		}

		$question_ids     = $quizPostModel->get_question_ids();
		$result_questions = (array) ( $result['questions'] ?? [] );
		$per_page         = $quizPostModel->get_pagination();
		$num_pages        = $per_page > 0
			? DataBase::get_total_pages( $per_page, $quizPostModel->count_questions() )
			: 1;

		$html_questions = '';
		foreach ( $question_ids as $index => $question_id ) {
			// Snapshot of question saved in result, keep rendering when question was deleted, no query.
			$question_result = (array) ( $result_questions[ $question_id ] ?? [] );
			// Load question data from result if not exists will query
			$questionPostModel = $this->get_question_model_from_result( $question_result );
			if ( ! $questionPostModel instanceof QuestionPostModel ) {
				$questionPostModel = QuestionPostModel::find( $question_id );
				if ( ! $questionPostModel instanceof QuestionPostModel ) {
					continue;
				}
			}

			$html_questions .= $this->html_question(
				array_merge(
					$args,
					[
						'questionPostModel' => $questionPostModel,
						'position'          => $index,
						'result_questions'  => $result_questions,
						'show_result'       => true,
						'show_correct'      => $quizPostModel->has_show_correct_review(),
					]
				)
			);
		}

		$section = [
			'wrap'     => sprintf(
				'<div class="lp-quiz-review lp-hidden"
						data-per-page="%d"
						data-total="%d"
						data-num-pages="%d">',
				$per_page,
				count( $question_ids ),
				$num_pages
			),
			'questions'   => sprintf( '<div class="quiz-questions">%s</div>', $html_questions ),
			'buttons'     => sprintf(
				'<div class="quiz-buttons">
					%1$s
					<button type="button" class="lp-button back-quiz">%2$s</button>
				</div>',
				$this->html_questions_pagination( [ 'num_pages' => $num_pages ] ),
				esc_html__( 'Result', 'learnpress' )
			),
			'wrap-end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-review', $section, $args )
		);
	}

	/**
	 * Format number for display.
	 * Returns integer without decimals for whole numbers, otherwise 2 decimal places.
	 *
	 * @param array $args Arguments containing:
	 *   - number: The number to format.
	 *
	 * @return string Formatted number string.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function format_number( array $args ): string {
		return QuestionTemplate::instance()->format_point( (float) ( $args['number'] ?? 0 ) );
	}

	/**
	 * Render HTML of the quiz attempt history table.
	 * Shows last attempts with questions, time spent, marks, passing grade, and result.
	 *
	 * @param array $args Arguments containing:
	 *   - userQuizModel: UserQuizModel instance.
	 *
	 * @return string HTML of the history table.
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_history( array $args ): string {
		$userQuizModel = $args['userQuizModel'] ?? false;
		$html          = '';

		try {
			if ( ! $userQuizModel instanceof UserQuizModel ) {
				return $html;
			}

			$attempts = $userQuizModel->get_history();
			if ( empty( $attempts ) ) {
				return $html;
			}

			$html_rows = '';
			foreach ( $attempts as $attempt ) {
				$attempt = (array) $attempt;

				$graduation = $attempt['graduation'] ?? '';
				if ( empty( $graduation ) ) {
					$graduation = empty( $attempt['pass'] ) ? 'failed' : 'passed';
				}

				$html_rows .= sprintf(
					'<tr>
						<td class="quiz-attempts__questions">%1$s / %2$s</td>
						<td class="quiz-attempts__spend">%3$s</td>
						<td class="quiz-attempts__marks">%4$s / %5$s</td>
						<td class="quiz-attempts__grade">%6$s</td>
						<td class="quiz-attempts__result">%7$s%% <span>%8$s</span></td>
					</tr>',
					esc_html( $attempt['question_correct'] ?? 0 ),
					esc_html( $attempt['question_count'] ?? 0 ),
					esc_html( ! empty( $attempt['time_spend'] ) ? $attempt['time_spend'] : '--' ),
					esc_html( $attempt['user_mark'] ?? 0 ),
					esc_html( $attempt['mark'] ?? 0 ),
					esc_html( ! empty( $attempt['passing_grade'] ) ? $attempt['passing_grade'] . '%' : '-' ),
					esc_html( number_format( (float) ( $attempt['result'] ?? 0 ), 2, '.', '' ) ),
					esc_html( $userQuizModel->get_graduation_label() )
				);
			}

			$section = [
				'wrapper'     => '<div class="quiz-attempts">',
				'heading'     => sprintf( '<div class="attempts-heading">%s</div>', esc_html__( 'Last Attempt', 'learnpress' ) ),
				'table'       => TableListTemplate::instance()->html_table(
					[
						'header' => [
							[
								'class' => 'quiz-attempts__questions',
								'title' => esc_html__( 'Questions', 'learnpress' ),
							],
							[
								'class' => 'quiz-attempts__spend',
								'title' => esc_html__( 'Time spent', 'learnpress' ),
							],
							[
								'class' => 'quiz-attempts__marks',
								'title' => esc_html__( 'Marks', 'learnpress' ),
							],
							[
								'class' => 'quiz-attempts__grade',
								'title' => esc_html__( 'Passing grade', 'learnpress' ),
							],
							[
								'class' => 'quiz-attempts__result',
								'title' => esc_html__( 'Result', 'learnpress' ),
							],
						],
						// Rows are prepared above, keep class of each cell.
						'body'   => [ 'rows_html' => $html_rows ],
					]
				),
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components(
				apply_filters( 'learn-press/user/quiz/html-history', $section, $userQuizModel )
			);
		} catch ( Throwable $e ) {
			Template::print_message(
				$e->getMessage(),
				'error',
				false
			);
		}

		return $html;
	}
}
