<?php
/**
 * Template hooks User Quiz.
 *
 * Render HTML of the quiz screens for user, replace the React app.
 * Markup keeps the class names of the old React app, so the old style still applies.
 *
 * @since 4.5.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\UserItem;

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
use LP_Debug;
use LP_Duration;
use LP_Global;
use LP_Settings;
use Throwable;

class UserQuizTemplate extends UserItemBaseTemplate {
	public static function instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Print HTML of the quiz is viewing, call from hook 'learn-press/content-item-summary/lp_quiz'.
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
	 * HTML of the quiz content, choose the screen by status of user quiz.
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param UserModel|false $userModel
	 * @param array $args
	 *
	 * @return string
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
		if ( post_password_required( $quiz_id )
			&& ! current_user_can( 'edit_post', $quiz_id ) ) {
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
	 * HTML of the screen by status of user quiz.
	 *
	 * @param array $args [ courseModel, quizPostModel, userQuizModel (UserQuizModel|false) ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
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
	 * HTML of the intro screen: meta, buttons and last attempts.
	 *
	 * @param array $args [ courseModel, quizPostModel, userQuizModel ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_intro( array $args ): string {
		$courseModel   = $args['courseModel'];
		$quizPostModel = $args['quizPostModel'];
		$userQuizModel = $args['userQuizModel'] ?? false;

		$section = [
			'meta'     => QuizTemplate::instance()->html_meta( $quizPostModel ),
			'buttons'  => sprintf(
				'<div class="quiz-buttons"><div class="button-left">%s</div><div class="button-right"></div></div>',
				$this->html_btn_start( $args )
			),
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-intro', $section, $courseModel, $quizPostModel, $userQuizModel )
		);
	}

	/**
	 * HTML button start quiz.
	 *
	 * @param array $args [ courseModel, quizPostModel ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_btn_start( array $args ): string {
		$courseModel   = $args['courseModel'];
		$quizPostModel = $args['quizPostModel'];

		if ( post_password_required( $quizPostModel->get_id() ) ) {
			return '';
		}

		$html = sprintf(
			'<button type="button" class="lp-button start" data-send="%s">%s</button>',
			Template::convert_data_to_json(
				[
					'id_url'    => 'user-start-quiz',
					'action'    => 'user_start_quiz', // UserQuizAjax::user_start_quiz
					'quiz_id'   => $quizPostModel->get_id(),
					'course_id' => $courseModel->get_id(),
				]
			),
			esc_html__( 'Start', 'learnpress' )
		);

		return apply_filters( 'learn-press/user/quiz/html-button-start', $html, $courseModel, $quizPostModel );
	}

	/**
	 * HTML of the doing quiz screen: status bar (timer), questions, pagination, button finish.
	 * Guest (course no required enroll) has not user quiz model, data is default.
	 *
	 * @param array $args [ courseModel, quizPostModel, userQuizModel ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
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
	 * HTML info bar when doing quiz: questions index, earned point, button finish, timer.
	 *
	 * @param array $args [ courseModel, quizPostModel ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
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
	 * HTML questions index of current page: "10 questions", "Question 1 of 10", "Question 1 to 5 of 10".
	 *
	 * @param array $args [ start, end, total, per_page, num_pages ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
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
	 * HTML timer when doing quiz.
	 * Duration > 0: count down from remaining time, else count up from time spend.
	 *
	 * @param array $args [ duration, remaining, time_spend ] all in seconds
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_timer( array $args ): string {
		$quizPostModel = $args['quizPostModel'];
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
		}

		$seconds = $duration > 0 ? $remaining : $time_spend;

		$html = sprintf(
			'<div class="countdown"
				data-duration="%1$d"
				data-remaining="%2$d"
				data-time-spend="%3$d">
				<i class="lp-icon-clock"></i>
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
	 * Format timer: MM:SS when total < 1 hour, else HH:MM:SS, same as old.
	 *
	 * @param array $args [ seconds, total ] total is used to choose format
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
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
	 * HTML list questions when doing quiz.
	 * Render all questions, questions not in first page are hidden, JS show by page.
	 * Questions checked (instant check) show result, same when reload page.
	 *
	 * @param array $args [ courseModel, quizPostModel, userQuizModel ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_questions( array $args ): string {
		$quizPostModel = $args['quizPostModel'];
		$userQuizModel = $args['userQuizModel'] ?? false;

		if ( ! $quizPostModel instanceof QuizPostModel ) {
			return '';
		}

		$checked_questions = [];
		$result_questions  = [];
		$question_ids      = $quizPostModel->get_question_ids();

		if ( $userQuizModel instanceof UserQuizModel ) {
			$checked_questions = array_map( 'absint', $userQuizModel->get_checked_questions() );
			$result_questions  = $userQuizModel->get_result()['questions'] ?? [];
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
	 * HTML of a question: title, content, answers, explanation, hint.
	 *
	 * Args extend parse_answer_args():
	 * index: number show before title (0 to hide).
	 * show_explanation: show explanation (question checked or quiz completed).
	 * page: page of question in quiz.
	 * hidden: hide question (not in current page).
	 *
	 * Default args are built for doing quiz from:
	 * position: position of question in quiz, start 0.
	 * result_questions: array of question results keyed by question_id, each has [answered, mark, correct].
	 * hidden: default hide when question not on first page.
	 * Other args (review...) override the default.
	 *
	 * @param array $args [ courseModel, quizPostModel, questionPostModel, position, result_questions, ...extra args above ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_question( array $args ): string {
		$courseModel       = $args['courseModel'];
		$quizPostModel     = $args['quizPostModel'];
		$questionPostModel = $args['questionPostModel'];
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
	 * HTML title of question: index, title, button hint, link edit.
	 *
	 * @param array $args [ questionPostModel, index, show_hint ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
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
	 * HTML button instant check question, only when quiz enable instant check.
	 *
	 * @param array $args [ courseModel, quizPostModel, questionPostModel ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_btn_instant_check( array $args ): string {
		$courseModel       = $args['courseModel'];
		$quizPostModel     = $args['quizPostModel'];
		$questionPostModel = $args['questionPostModel'];
		$result_questions  = $args['result_questions'];

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
	 * HTML button finish quiz.
	 *
	 * @param array $args [ courseModel, quizPostModel, hidden ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_btn_finish( array $args ): string {
		$courseModel   = $args['courseModel'];
		$quizPostModel = $args['quizPostModel'];

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
	 * HTML pagination of questions: one pagination per page (Template::html_pagination), JS only shows the one of current page.
	 * Link of page is '#page-{n}', JS reads the page number from it.
	 *
	 * @param array $args [ num_pages ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
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
	 * HTML of the completed quiz screen: result, buttons retake / review, history, review questions (hidden).
	 * Guest (course no required enroll) has not user quiz model, $result is calculated when submit.
	 *
	 * @param array $args [ courseModel, quizPostModel, result (@see UserQuizModel::get_result()), userQuizModel ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_completed( array $args ): string {
		$userQuizModel = $args['userQuizModel'] ?? false;

		$section = [
			'wrapper'     => '<div class="lp-quiz-completed">',
			'result_wrap' => '<div class="lp-quiz-result-wrap">',
			'result'      => $this->html_result( $args ),
			'buttons'     => sprintf(
				'<div class="quiz-buttons"><div class="button-left">%s</div><div class="button-right">%s</div></div>',
				$this->html_btn_retake( $args ),
				$this->html_btn_review( $args )
			),
			'history'     => $userQuizModel instanceof UserQuizModel ? $this->html_history( $args ) : '',
			'result_end'  => '</div>',
			'review'      => $this->html_review_result( $args ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-completed', $section, $args )
		);
	}

	/**
	 * HTML result of quiz: grade circle, message, statistic.
	 *
	 * @param array $args [ quizPostModel, result, userQuizModel ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_result( array $args ): string {
		$quizPostModel = $args['quizPostModel'];
		$result        = (array) ( $args['result'] ?? [] );
		$userQuizModel = $args['userQuizModel'] ?? false;

		$percent       = (float) ( $result['result'] ?? 0 );
		$passing_grade = (float) ( ! empty( $result['passing_grade'] ) ? $result['passing_grade'] : $quizPostModel->get_passing_grade() );

		$graduation = $userQuizModel instanceof UserQuizModel ? $userQuizModel->get_graduation() : '';
		if ( ! in_array( $graduation, [ UserItemModel::GRADUATION_PASSED, UserItemModel::GRADUATION_FAILED ], true ) ) {
			if ( isset( $result['pass'] ) ) {
				$graduation = $result['pass'] ? UserItemModel::GRADUATION_PASSED : UserItemModel::GRADUATION_FAILED;
			} else {
				$graduation = $percent >= $passing_grade ? UserItemModel::GRADUATION_PASSED : UserItemModel::GRADUATION_FAILED;
			}
		}

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
				esc_html( apply_filters( 'learn-press/user-quiz-graduation-text', learn_press_get_graduation_text( $graduation ) ) )
			),
			'statistic'   => sprintf( '<ul class="result-statistic">%s</ul>', $html_statistic ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-result', $section, $quizPostModel, $result, $userQuizModel )
		);
	}

	/**
	 * HTML button retake quiz.
	 * Retake count -1 is unlimited.
	 * Guest: retaken count is stored on browser, JS update label and hide button by data-retake-max.
	 *
	 * @param array $args [ courseModel, quizPostModel, userQuizModel ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_btn_retake( array $args ): string {
		$courseModel   = $args['courseModel'];
		$quizPostModel = $args['quizPostModel'];
		$userQuizModel = $args['userQuizModel'] ?? false;

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
			'<button type="button" class="lp-button start retake-quiz" data-retake-max="%1$d" data-title="%2$s" data-content="%3$s" data-send="%4$s">%5$s</button>',
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
	 * HTML button review quiz, only when quiz enable option review.
	 *
	 * @param array $args [ quizPostModel ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_btn_review( array $args ): string {
		$quizPostModel = $args['quizPostModel'];

		if ( ! $quizPostModel->has_review() ) {
			return '';
		}

		$html = sprintf( '<button type="button" class="lp-button review-quiz">%s</button>', esc_html__( 'Review', 'learnpress' ) );

		return apply_filters( 'learn-press/user/quiz/html-button-review', $html, $quizPostModel );
	}

	/**
	 * HTML review questions after completed quiz, hidden until user click button review.
	 * Only render when quiz enable option review, avoid sending answers when not allowed.
	 *
	 * @param array $args [ courseModel, quizPostModel, result ] result key questions has [answered, correct, mark] of each question
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_review_result( array $args ): string {
		$courseModel   = $args['courseModel'];
		$quizPostModel = $args['quizPostModel'];
		$result        = (array) ( $args['result'] ?? [] );

		if ( ! $quizPostModel->has_review() ) {
			return '';
		}

		$question_ids     = array_values( $quizPostModel->get_question_ids() );
		$result_questions = (array) ( $result['questions'] ?? [] );
		$per_page         = $quizPostModel->get_pagination();
		$num_pages        = $per_page > 0 ? (int) ceil( count( $question_ids ) / $per_page ) : 1;

		$html_questions = '';
		foreach ( $question_ids as $index => $question_id ) {
			$questionPostModel = QuestionPostModel::find( (int) $question_id, true );
			if ( ! $questionPostModel instanceof QuestionPostModel ) {
				continue;
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
			'wrapper'     => sprintf(
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
					<div class="button-left fixed">%1$s</div>
					<div class="button-right">
						<button type="button" class="lp-button back-quiz">%2$s</button>
					</div>
				</div>',
				$this->html_questions_pagination( [ 'num_pages' => $num_pages ] ),
				esc_html__( 'Result', 'learnpress' )
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-review', $section, $courseModel, $quizPostModel, $result )
		);
	}

	/**
	 * Class position of navigation (pagination) when doing / reviewing quiz, by setting 'navigation_position'.
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function get_nav_class(): string {
		return LP_Settings::get_option( 'navigation_position', 'yes' ) === 'no' ? 'nav-center' : 'fixed';
	}

	/**
	 * Format number: integer without decimals, other 2 decimals.
	 *
	 * @param array $args [ number ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function format_number( array $args ): string {
		return QuestionTemplate::instance()->format_point( (float) ( $args['number'] ?? 0 ) );
	}

	/**
	 * HTML of the last attempts (history) of user quiz.
	 *
	 * @param array $args [ userQuizModel ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.1.0
	 */
	public function html_history( array $args ): string {
		$userQuizModel = $args['userQuizModel'];
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
					esc_html( ! empty( $attempt['passing_grade'] ) ? $attempt['passing_grade'] : '-' ),
					esc_html( number_format( (float) ( $attempt['result'] ?? 0 ), 2, '.', '' ) ),
					esc_html( apply_filters( 'learn-press/user-quiz-graduation-text', learn_press_get_graduation_text( $graduation ) ) )
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
