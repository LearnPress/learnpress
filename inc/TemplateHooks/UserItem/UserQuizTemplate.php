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

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Learning\QuestionTemplate;
use LearnPress\TemplateHooks\Learning\QuizTemplate;
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
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_quiz_content( CourseModel $courseModel, QuizPostModel $quizPostModel, $userModel = false ): string {
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
				'<div id="learn-press-user-quiz" class="lp-user-quiz" data-quiz-id="%d" data-course-id="%d" data-status="%s">',
				$quiz_id,
				$courseModel->get_id(),
				esc_attr( $status )
			),
			'description' => $userQuizModel instanceof UserQuizModel ? '' : QuizTemplate::instance()->html_description( $quizPostModel ),
			'screen'      => $quizPostModel->count_questions() > 0
				? $this->html_screen( $courseModel, $quizPostModel, $userQuizModel )
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
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param UserQuizModel|false $userQuizModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_screen( CourseModel $courseModel, QuizPostModel $quizPostModel, $userQuizModel = false ): string {
		$status = $userQuizModel instanceof UserQuizModel ? $userQuizModel->get_status() : '';

		switch ( $status ) {
			case UserQuizModel::STATUS_STARTED:
				$html = $this->html_doing_quiz( $courseModel, $quizPostModel, $userQuizModel );
				break;
			case UserItemModel::STATUS_COMPLETED:
				$html = $this->html_completed( $courseModel, $quizPostModel, $userQuizModel->get_result(), $userQuizModel );
				break;
			default:
				$html = $this->html_intro( $courseModel, $quizPostModel, $userQuizModel );
				break;
		}

		return $html;
	}

	/**
	 * HTML of the intro screen: meta, buttons and last attempts.
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param UserQuizModel|false $userQuizModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_intro( CourseModel $courseModel, QuizPostModel $quizPostModel, $userQuizModel = false ): string {
		$section = [
			'meta'     => QuizTemplate::instance()->html_meta( $quizPostModel ),
			'buttons'  => sprintf(
				'<div class="quiz-buttons"><div class="button-left">%s</div><div class="button-right"></div></div>',
				$this->html_btn_start( $courseModel, $quizPostModel )
			),
			'attempts' => $userQuizModel instanceof UserQuizModel ? $this->html_history( $userQuizModel ) : '',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-intro', $section, $courseModel, $quizPostModel, $userQuizModel )
		);
	}

	/**
	 * HTML button start quiz.
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_btn_start( CourseModel $courseModel, QuizPostModel $quizPostModel ): string {
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
	 * HTML of the doing quiz screen: status bar (timer), questions, buttons.
	 * Guest (course no required enroll) has not user quiz model, data is default.
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param UserQuizModel|false $userQuizModel
	 * @param array $args
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_doing_quiz(
		CourseModel $courseModel,
		QuizPostModel $quizPostModel,
		$userQuizModel = false,
		array $args = []
	): string {
		$question_ids = $quizPostModel->get_question_ids();
		$total        = $quizPostModel->count_questions();
		$per_page     = $quizPostModel->get_pagination();
		$num_pages    = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;
		$duration     = (float) $quizPostModel->get_duration();
		$remaining    = $duration;
		$time_spend   = 0;
		$result       = [];

		if ( $userQuizModel instanceof UserQuizModel ) {
			$time_spend = max( 0, time() - strtotime( $userQuizModel->get_start_time() ) );
			if ( $duration > 0 ) {
				$remaining = $userQuizModel->get_time_remaining();
			}
			$result = $userQuizModel->get_result();
		}

		$section = [
			'wrapper'     => sprintf(
				'<div class="lp-quiz-doing" data-per-page="%d" data-total="%d" data-num-pages="%d">',
				$per_page,
				$total,
				$num_pages
			),
			'bar_info'    => $this->html_doing_bar_info(
				$courseModel,
				$quizPostModel,
				[
					'per_page'   => $per_page,
					'total'      => $total,
					'user_mark'  => $result['user_mark'] ?? 0,
					'duration'   => $duration,
					'remaining'  => $remaining,
					'time_spend' => $time_spend,
				]
			),
			'questions'   => $this->html_questions( $courseModel, $quizPostModel, $question_ids, $userQuizModel, $result ),
			'buttons'     => $this->html_doing_buttons( $courseModel, $quizPostModel, $num_pages ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-doing', $section, $courseModel, $quizPostModel, $userQuizModel )
		);
	}

	/**
	 * HTML info bar when doing quiz: questions index, earned point, button finish, timer.
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param array $data [ per_page, total, user_mark, duration, remaining, time_spend ]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_doing_bar_info( CourseModel $courseModel, QuizPostModel $quizPostModel, array $data ): string {
		$total = (int) ( $data['total'] ?? 0 );
		$end   = (int) ( $data['per_page'] ?? 0 ) > 0 ? min( (int) $data['per_page'], $total ) : $total;

		$section = [
			'wrapper'     => '<div class="quiz-doing-bar-info"><div>',
			'index'       => sprintf(
				'<div class="questions-index">%s</div>',
				$this->html_questions_index( 1, $end, $total )
			),
			'point'       => sprintf(
				'<div class="current-point">%s</div>',
				sprintf(
					/* translators: %s: point */
					esc_html__( 'Earned Point: %s', 'learnpress' ),
					sprintf(
						'<span class="current-point__value">%s</span>',
						esc_html( (string) ( $data['user_mark'] ?? 0 ) )
					)
				)
			),
			'submit'      => sprintf(
				'<div><div class="submit-quiz">%s</div>%s</div>',
				$this->html_btn_finish( $courseModel, $quizPostModel, 'button-submit-quiz' ),
				$this->html_timer( (int) ( $data['duration'] ?? 0 ), (int) ( $data['remaining'] ?? 0 ), (int) ( $data['time_spend'] ?? 0 ) )
			),
			'wrapper_end' => '</div></div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-doing-bar-info', $section, $courseModel, $quizPostModel, $data )
		);
	}

	/**
	 * HTML questions index of current page: "Question 1 of 10", "Question 1 to 5 of 10".
	 *
	 * @param int $start
	 * @param int $end
	 * @param int $total
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_questions_index( int $start, int $end, int $total ): string {
		if ( $end > $start ) {
			/* translators: 1: first question of page, 2: last question of page, 3: total questions */
			$text = sprintf( __( 'Question <span>%1$d to %2$d of %3$d</span>', 'learnpress' ), $start, $end, $total );
		} else {
			/* translators: 1: question number, 2: total questions */
			$text = sprintf( __( 'Question <span>%1$d of %2$d</span>', 'learnpress' ), $start, $total );
		}

		return wp_kses_post( $text );
	}

	/**
	 * HTML timer when doing quiz.
	 * Duration > 0: count down from remaining time, else count up from time spend.
	 *
	 * @param int $duration seconds
	 * @param int $remaining seconds
	 * @param int $time_spend seconds
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_timer( int $duration, int $remaining, int $time_spend ): string {
		$seconds = $duration > 0 ? $remaining : $time_spend;

		$html = sprintf(
			'<div class="countdown" data-duration="%1$d" data-remaining="%2$d" data-time-spend="%3$d"><i class="lp-icon-clock"></i><span>%4$s</span><input type="hidden" name="lp-quiz-time-spend" value="%3$d"/></div>',
			$duration,
			$remaining,
			$time_spend,
			esc_html( $this->format_timer( $seconds, $duration > 0 ? $duration : $seconds ) )
		);

		return apply_filters( 'learn-press/user/quiz/html-timer', $html, $duration, $remaining, $time_spend );
	}

	/**
	 * Format timer: MM:SS when total < 1 hour, else HH:MM:SS, same as old.
	 *
	 * @param int $seconds
	 * @param int $total seconds, use to choose format
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function format_timer( int $seconds, int $total ): string {
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
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param int[] $question_ids
	 * @param UserQuizModel|false $userQuizModel
	 * @param array|null $result result of user quiz, null to get from $userQuizModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_questions( CourseModel $courseModel, QuizPostModel $quizPostModel, array $question_ids, $userQuizModel = false, ?array $result = null ): string {
		$checked_questions = [];
		$result_questions  = [];

		if ( $userQuizModel instanceof UserQuizModel ) {
			$checked_questions = array_map( 'absint', $userQuizModel->get_checked_questions() );
			$result_questions  = ( $result ?? $userQuizModel->get_result() )['questions'] ?? [];
		}

		$html_questions = '';
		foreach ( array_values( $question_ids ) as $index => $question_id ) {
			$questionPostModel = QuestionPostModel::find( (int) $question_id, true );
			if ( ! $questionPostModel instanceof QuestionPostModel ) {
				continue;
			}

			$is_checked = in_array( (int) $question_id, $checked_questions, true );

			$html_questions .= $this->html_question_doing(
				$courseModel,
				$quizPostModel,
				$questionPostModel,
				$index,
				$is_checked ? (array) ( $result_questions[ $question_id ] ?? [] ) : null
			);
		}

		return sprintf( '<div class="quiz-questions">%s</div>', $html_questions );
	}

	/**
	 * HTML a question when doing quiz.
	 * $checked is result of instant check [answered, mark, correct], null when not checked.
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param QuestionPostModel $questionPostModel
	 * @param int $index position of question in quiz, start 0
	 * @param array|null $checked
	 * @param bool|null $hidden null: hide when question not on first page
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_question_doing( CourseModel $courseModel, QuizPostModel $quizPostModel, QuestionPostModel $questionPostModel, int $index, $checked = null, ?bool $hidden = null ): string {
		$is_checked = is_array( $checked );
		$page       = $this->get_page_of_question( $quizPostModel, $index );

		return QuestionTemplate::instance()->html_question(
			$questionPostModel,
			[
				'index'            => $index + 1,
				'page'             => $page,
				'hidden'           => $hidden ?? $page > 1,
				'status'           => UserQuizModel::STATUS_STARTED,
				'answered'         => $is_checked ? ( $checked['answered'] ?? null ) : null,
				'show_result'      => $is_checked,
				'show_correct'     => $is_checked && $quizPostModel->has_show_correct_review(),
				'correct'          => $checked['correct'] ?? null,
				'mark'             => $checked['mark'] ?? 0,
				'show_explanation' => $is_checked,
				'edit_link'        => $this->get_question_edit_link( $questionPostModel ),
				'footer'           => $is_checked ? '' : $this->html_btn_instant_check( $courseModel, $quizPostModel, $questionPostModel ),
			]
		);
	}

	/**
	 * HTML a question after user instant check, JS replace the question element by it.
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param QuestionPostModel $questionPostModel
	 * @param array $checked result of instant check [answered, mark, correct]
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_check_quiz( CourseModel $courseModel, QuizPostModel $quizPostModel, QuestionPostModel $questionPostModel, array $checked ): string {
		$index = array_search( $questionPostModel->get_id(), array_map( 'absint', $quizPostModel->get_question_ids() ), true );

		$html = $this->html_question_doing(
			$courseModel,
			$quizPostModel,
			$questionPostModel,
			false === $index ? 0 : (int) $index,
			$checked,
			false // User is viewing this question.
		);

		return apply_filters( 'learn-press/user/quiz/html-check-question', $html, $courseModel, $quizPostModel, $questionPostModel, $checked );
	}

	/**
	 * Page of question by position, all questions on page 1 when pagination is 0.
	 *
	 * @param QuizPostModel $quizPostModel
	 * @param int $index position of question in quiz, start 0
	 *
	 * @return int
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function get_page_of_question( QuizPostModel $quizPostModel, int $index ): int {
		$per_page = $quizPostModel->get_pagination();

		return $per_page > 0 ? (int) floor( $index / $per_page ) + 1 : 1;
	}

	/**
	 * Link edit question, only for user can edit.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function get_question_edit_link( QuestionPostModel $questionPostModel ): string {
		return current_user_can( 'edit_post', $questionPostModel->get_id() ) ? (string) $questionPostModel->get_edit_link() : '';
	}

	/**
	 * HTML button instant check question, only when quiz enable instant check.
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_btn_instant_check( CourseModel $courseModel, QuizPostModel $quizPostModel, QuestionPostModel $questionPostModel ): string {
		if ( ! $quizPostModel->has_instant_check() ) {
			return '';
		}

		$html = sprintf(
			'<button type="button" class="lp-button instant-check" data-send="%1$s"><span class="instant-check__icon"></span>%2$s<div class="instant-check__info">%3$s</div></button>',
			Template::convert_data_to_json(
				[
					'id_url'      => 'user-check-question',
					'action'      => 'user_check_question', // UserQuizAjax::user_check_question
					'quiz_id'     => $quizPostModel->get_id(),
					'course_id'   => $courseModel->get_id(),
					'question_id' => $questionPostModel->get_id(),
				]
			),
			esc_html__( 'Check answers', 'learnpress' ),
			esc_html__( 'You need to answer the question before checking the answer key.', 'learnpress' )
		);

		return apply_filters( 'learn-press/user/quiz/html-button-instant-check', $html, $courseModel, $quizPostModel, $questionPostModel );
	}

	/**
	 * HTML button finish quiz.
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param string $id_attr id of button, button on status bar has id 'button-submit-quiz' same as old
	 * @param bool $hidden
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_btn_finish( CourseModel $courseModel, QuizPostModel $quizPostModel, string $id_attr = '', bool $hidden = false ): string {
		$html = sprintf(
			'<button type="button" class="lp-button%1$s"%2$s%3$s data-title="%4$s" data-content="%5$s" data-send="%6$s">%7$s</button>',
			$id_attr ? '' : ' submit-quiz',
			$id_attr ? ' id="' . esc_attr( $id_attr ) . '"' : '',
			$hidden ? ' hidden' : '',
			// Confirm popup (SweetAlert) read data-title, data-content.
			esc_attr__( 'Finish Quiz', 'learnpress' ),
			esc_attr__( 'Are you sure to submit the quiz?', 'learnpress' ),
			Template::convert_data_to_json(
				[
					'id_url'    => 'user-submit-quiz',
					'action'    => 'user_submit_quiz', // UserQuizAjax::user_submit_quiz
					'quiz_id'   => $quizPostModel->get_id(),
					'course_id' => $courseModel->get_id(),
				]
			),
			esc_html__( 'Finish Quiz', 'learnpress' )
		);

		return apply_filters( 'learn-press/user/quiz/html-button-finish', $html, $courseModel, $quizPostModel, $id_attr, $hidden );
	}

	/**
	 * HTML pagination of questions: one pagination per page (Template::html_pagination), JS only shows the one of current page.
	 * Link of page is '#page-{n}', JS reads the page number from it.
	 *
	 * @param int $num_pages
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_questions_pagination( int $num_pages ): string {
		if ( $num_pages <= 1 ) {
			return '';
		}

		$html = '';
		for ( $page = 1; $page <= $num_pages; $page++ ) {
			$html .= sprintf(
				'<div class="questions-pagination__item" data-page="%1$d"%2$s>%3$s</div>',
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
	 * HTML buttons below questions when doing quiz: pagination, finish.
	 * Finish button shows on last page (JS).
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param int $num_pages
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_doing_buttons( CourseModel $courseModel, QuizPostModel $quizPostModel, int $num_pages ): string {
		$section = [
			'wrapper'     => sprintf(
				'<div class="quiz-buttons align-center is-first%s">',
				$num_pages <= 1 ? ' is-last' : ''
			),
			'left'        => sprintf(
				'<div class="button-left %1$s">%2$s</div>',
				$this->get_nav_class(),
				$this->html_questions_pagination( $num_pages )
			),
			'right'       => sprintf(
				'<div class="button-right">%s</div>',
				// Hidden when has many pages, JS shows on last page.
				$this->html_btn_finish( $courseModel, $quizPostModel, '', $num_pages > 1 )
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-doing-buttons', $section, $courseModel, $quizPostModel, $num_pages )
		);
	}

	/**
	 * HTML of the completed quiz screen: result, buttons retake / review, history, review questions (hidden).
	 * Guest (course no required enroll) has not user quiz model, $result is calculated when submit.
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param array $result result of quiz, @see UserQuizModel::get_result()
	 * @param UserQuizModel|false $userQuizModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_completed( CourseModel $courseModel, QuizPostModel $quizPostModel, array $result, $userQuizModel = false ): string {
		$section = [
			'wrapper'     => '<div class="lp-quiz-completed">',
			'result_wrap' => '<div class="lp-quiz-result-wrap">',
			'result'      => $this->html_result( $quizPostModel, $result, $userQuizModel ),
			'buttons'     => sprintf(
				'<div class="quiz-buttons"><div class="button-left">%s</div><div class="button-right">%s</div></div>',
				$this->html_btn_retake( $courseModel, $quizPostModel, $userQuizModel ),
				$this->html_btn_review( $quizPostModel )
			),
			'history'     => $userQuizModel instanceof UserQuizModel ? $this->html_history( $userQuizModel ) : '',
			'result_end'  => '</div>',
			'review'      => $this->html_review_result( $courseModel, $quizPostModel, $result ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/user/quiz/html-completed', $section, $courseModel, $quizPostModel, $result, $userQuizModel )
		);
	}

	/**
	 * HTML result of quiz: grade circle, message, statistic.
	 *
	 * @param QuizPostModel $quizPostModel
	 * @param array $result
	 * @param UserQuizModel|false $userQuizModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_result( QuizPostModel $quizPostModel, array $result, $userQuizModel = false ): string {
		$percent       = (float) ( $result['result'] ?? 0 );
		$passing_grade = (float) ( ! empty( $result['passing_grade'] ) ? $result['passing_grade'] : $quizPostModel->get_passing_grade() );

		$graduation = $userQuizModel instanceof UserQuizModel ? $userQuizModel->get_graduation() : '';
		if ( ! in_array( $graduation, [ UserQuizModel::GRADUATION_PASSED, UserQuizModel::GRADUATION_FAILED ], true ) ) {
			if ( isset( $result['pass'] ) ) {
				$graduation = $result['pass'] ? UserQuizModel::GRADUATION_PASSED : UserQuizModel::GRADUATION_FAILED;
			} else {
				$graduation = $percent >= $passing_grade ? UserQuizModel::GRADUATION_PASSED : UserQuizModel::GRADUATION_FAILED;
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
				sprintf( '%s / %s', $this->format_number( $result['user_mark'] ?? 0 ), $this->format_number( $result['mark'] ?? 0 ) ),
			],
			'result-questions'         => [ __( 'Questions', 'learnpress' ), $result['question_count'] ?? 0 ],
			'result-questions-correct' => [ __( 'Correct', 'learnpress' ), $result['question_correct'] ?? 0 ],
			'result-questions-wrong'   => [ __( 'Wrong', 'learnpress' ), $result['question_wrong'] ?? 0 ],
			'result-questions-skipped' => [ __( 'Skipped', 'learnpress' ), $result['question_empty'] ?? 0 ],
		];
		if ( $quizPostModel->has_negative_marking() || $quizPostModel->has_minus_skip_questions() ) {
			$statistic['result-questions-minus'] = [ __( 'Minus points', 'learnpress' ), $this->format_number( $result['minus_point'] ?? 0 ) ];
		}

		$html_statistic = '';
		foreach ( $statistic as $class => $field ) {
			$html_statistic .= sprintf(
				'<li class="result-statistic-field %1$s"><span>%2$s</span><p>%3$s</p></li>',
				esc_attr( $class ),
				esc_html( $field[0] ),
				esc_html( (string) $field[1] )
			);
		}

		$section = [
			'wrapper'     => sprintf( '<div class="quiz-result %s">', esc_attr( $graduation ) ),
			'heading'     => sprintf( '<h3 class="result-heading">%s</h3>', esc_html__( 'Your Result', 'learnpress' ) ),
			'grade'       => sprintf(
				'<div id="quizResultGrade" class="result-grade" data-result="%1$s"><svg class="circle-progress-bar" width="%2$d" height="%2$d"><circle class="circle-progress-bar__circle" stroke="" stroke-width="%3$d" style="stroke-dasharray: %4$s %4$s; stroke-dashoffset: %5$s;" fill="transparent" r="%6$s" cx="%7$s" cy="%7$s"></circle></svg><span class="result-achieved">%8$s%%</span><span class="result-require">%9$s%%</span></div>',
				esc_attr( (string) $percent ),
				$width,
				$border,
				esc_attr( (string) round( $circumference, 3 ) ),
				esc_attr( (string) round( $offset, 3 ) ),
				esc_attr( (string) $r ),
				esc_attr( (string) ( $width / 2 ) ),
				esc_html( $this->format_number( $percent ) ),
				esc_html( $this->format_number( $passing_grade ) )
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
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param UserQuizModel|false $userQuizModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_btn_retake( CourseModel $courseModel, QuizPostModel $quizPostModel, $userQuizModel = false ): string {
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
					'action'    => 'user_retake_quiz', // UserQuizAjax::user_retake_quiz
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
	 * @param QuizPostModel $quizPostModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_btn_review( QuizPostModel $quizPostModel ): string {
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
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param array $result result of quiz, key questions has [answered, correct, mark] of each question
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_review_result( CourseModel $courseModel, QuizPostModel $quizPostModel, array $result ): string {
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

			$question_result = (array) ( $result_questions[ $question_id ] ?? [] );
			$page            = $this->get_page_of_question( $quizPostModel, $index );

			$html_questions .= QuestionTemplate::instance()->html_question(
				$questionPostModel,
				[
					'index'            => $index + 1,
					'page'             => $page,
					'hidden'           => $page > 1,
					'status'           => UserQuizModel::STATUS_COMPLETED,
					'answered'         => $question_result['answered'] ?? null,
					'show_result'      => true,
					'show_correct'     => $quizPostModel->has_show_correct_review(),
					'correct'          => $question_result['correct'] ?? false,
					'mark'             => $question_result['mark'] ?? 0,
					'show_explanation' => true,
					'edit_link'        => $this->get_question_edit_link( $questionPostModel ),
				]
			);
		}

		$section = [
			'wrapper'     => sprintf(
				'<div class="lp-quiz-review" style="display: none;" data-per-page="%d" data-total="%d" data-num-pages="%d">',
				$per_page,
				count( $question_ids ),
				$num_pages
			),
			'questions'   => sprintf( '<div class="quiz-questions">%s</div>', $html_questions ),
			'buttons'     => sprintf(
				'<div class="quiz-buttons align-center"><div class="button-left %1$s">%2$s</div><div class="button-right"><button type="button" class="lp-button back-quiz">%3$s</button></div></div>',
				$this->get_nav_class(),
				$this->html_questions_pagination( $num_pages ),
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
	 * @param mixed $number
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function format_number( $number ): string {
		return QuestionTemplate::instance()->format_point( (float) $number );
	}

	/**
	 * HTML of the last attempts (history) of user quiz.
	 *
	 * @param UserQuizModel $userQuizModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_history( UserQuizModel $userQuizModel ): string {
		$html = '';

		try {
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
				'heading'     => sprintf( '<h4 class="attempts-heading">%s</h4>', esc_html__( 'Last Attempt', 'learnpress' ) ),
				'table'       => sprintf(
					'<table>
						<thead>
							<tr>
								<th class="quiz-attempts__questions">%1$s</th>
								<th class="quiz-attempts__spend">%2$s</th>
								<th class="quiz-attempts__marks">%3$s</th>
								<th class="quiz-attempts__grade">%4$s</th>
								<th class="quiz-attempts__result">%5$s</th>
							</tr>
						</thead>
						<tbody>%6$s</tbody>
					</table>',
					esc_html__( 'Questions', 'learnpress' ),
					esc_html__( 'Time spent', 'learnpress' ),
					esc_html__( 'Marks', 'learnpress' ),
					esc_html__( 'Passing grade', 'learnpress' ),
					esc_html__( 'Result', 'learnpress' ),
					$html_rows
				),
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components(
				apply_filters( 'learn-press/user/quiz/html-history', $section, $userQuizModel )
			);
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}
