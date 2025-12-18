<?php

namespace LearnPress\TemplateHooks\Quiz;

defined( 'ABSPATH' ) || exit();

use LearnPress\Databases\DataBase;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use LearnPress\Models\CourseModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LP_Database;
use LP_Debug;
use Throwable;

/**
 * Class QuizTemplate
 *
 * @since 4.3.3
 * @version 1.0.0
 */
class CourseQuizTemplate {
	use Singleton;

	public function init() {
	}

	public function layout( CourseModel $courseModel, QuizPostModel $quizPostModel, array $data = [] ) {
		try {

			$user_id   = 0;
			$userModel = UserModel::find( get_current_user_id(), true );
			if ( $userModel instanceof UserModel ) {
				$user_id = $userModel->get_id();
			}

			$userQuizModel = UserQuizModel::find_user_item(
				$user_id,
				$quizPostModel->get_id(),
				LP_QUIZ_CPT,
				$courseModel->get_id(),
				LP_COURSE_CPT,
				true
			);

			$section = [
				'wrap'           => '<div class="lp-course-quiz-content">',
				'title'          => sprintf(
					'<h1 class="quiz-title course-item-title">%s</h1>',
					esc_html( $quizPostModel->get_the_title() )
				),
				'user_not_start' => $this->layout_user_not_start( $courseModel, $quizPostModel, $data ),
				'user_started'   => $this->layout_user_started( $quizPostModel, $userQuizModel, $data ),
				'user_submitted' => $this->layout_user_submitted( $userQuizModel, $data ),
				'wrap-end'       => '</div>',
			];

			echo Template::combine_components( $section );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	/**
	 * HTML layout for quiz not started by user
	 *
	 * @param CourseModel $courseModel
	 * @param QuizPostModel $quizPostModel
	 * @param array $data
	 *
	 * @return string
	 */
	public function layout_user_not_start(
		CourseModel $courseModel,
		QuizPostModel $quizPostModel,
		array $data = []
	): string {
		$html = '';

		$section = [
			'wrap'     => '<div class="lp-quiz-not-started-content">',
			'content'  => sprintf(
				'<div class="quiz-content quiz-description">%s</div>',
				$quizPostModel->get_the_content()
			),
			'info'     => $this->html_quiz_meta_info( $quizPostModel ),
			'wrap-end' => '</div>',
		];

		$html .= Template::combine_components( $section );

		return $html;
	}

	/**
	 * HTML layout for quiz started by user
	 *
	 * @param QuizPostModel $quizPostModel
	 * @param UserQuizModel|false $userQuizModel false for no requirement enroll
	 * @param array $data
	 *
	 * @return string
	 */
	public function layout_user_started( QuizPostModel $quizPostModel, $userQuizModel, array $data = [] ): string {
		$html = '';

		$section = [
			'content'   => '<div class="lp-quiz-started-content">',
			'timer'     => $this->html_bar_timer_counting( $quizPostModel, $userQuizModel ),
			'questions' => $this->html_list_questions( $quizPostModel, $userQuizModel ),
			'wrap-end'  => '</div>',
		];

		$html .= Template::combine_components( $section );

		return $html;
	}

	/**
	 * HTML layout for quiz submitted by user
	 *
	 * @param UserQuizModel|false $userQuizModel false for no requirement enroll
	 * @param array $data
	 *
	 * @return string
	 */
	public function layout_user_submitted( $userQuizModel, array $data = [] ): string {
		$html = '';

		$section = [
			'content'  => '<div class="lp-quiz-submitted-content">',
			'wrap-end' => '',
		];

		$html .= Template::combine_components( $section );

		return $html;
	}

	/**
	 * HTML for quiz meta info
	 *
	 * @param QuizPostModel $quizPostModel
	 *
	 * @return string
	 */
	public function html_quiz_meta_info( QuizPostModel $quizPostModel ): string {
		$quiz_info = apply_filters(
			'learn-press/course-quiz/info',
			[
				'count_questions' => [
					'label' => _n( 'Question', 'Questions', $quizPostModel->count_questions(), 'learnpress' ),
					'value' => esc_html( $quizPostModel->count_questions() ),
					'icon'  => 'lp-icon-question-circle',
				],
				'time_limit'      => [
					'label' => esc_html__( 'Time Limit', 'learnpress' ),
					'value' => $quizPostModel->get_duration(),
					'icon'  => 'lp-icon-clock',
				],
				'passing_grade'   => [
					'label' => esc_html__( 'Passing Grade', 'learnpress' ),
					'value' => esc_html( $quizPostModel->get_passing_grade() . '%' ),
					'icon'  => 'lp-icon-signal',
				],
			]
		);

		$html = '';
		foreach ( $quiz_info as $key => $info ) {
			$html .= sprintf(
				'<li class="quiz-intro-item">
					<i class="%s"></i>
					<span class="quiz-intro-label">%s:</span>
					<span class="quiz-intro-value">%s</span>
				</li>',
				esc_attr( $info['icon'] ),
				esc_html( $info['label'] ),
				esc_html( $info['value'] )
			);
		}

		return sprintf( '<ul class="quiz-intro quiz-meta-info">%s</ul>', $html );
	}

	/**
	 * HTML for quiz timer bar counting
	 *
	 * @param QuizPostModel $quizPostModel
	 * @param UserQuizModel|false $userQuizModel
	 *
	 * @return string
	 */
	public function html_bar_timer_counting( QuizPostModel $quizPostModel, $userQuizModel ): string {
		$html = '';

		$section = [
			'wrap'            => '<div class="lp-quiz-timer-bar quiz-status">',
			'questions-index' => sprintf(
				'<div class="questions-index">%s</div>',
				sprintf(
					__( 'Question <span class="questions-page">%1$d</span> of %2$d', 'learnpress' ),
					1,
					$quizPostModel->count_questions()
				)
			),
			'left'            => sprintf(
				'<div class="timer-counting">%s %s %s</div>',
				'<i class="lp-icon-stopwatch"></i>',
				'<span class="countdown">00:00:00</span>',
				sprintf(
					'<button class="lp-btn-submit-quiz lp-button" type="button">%s</button>',
					esc_html__( 'Finish Quiz', 'learnpress' )
				)
			),
			'wrap-end'        => '</div>',
		];

		$html .= Template::combine_components( $section );

		return $html;
	}

	public function html_list_questions( QuizPostModel $quizPostModel, $userQuizModel ): string {
		$html = '';

		$html_question = '';
		foreach ( $quizPostModel->get_question_ids() as $index => $question_id ) {
			$questionPostModel = QuestionPostModel::find( $question_id, true );
			if ( ! $questionPostModel instanceof QuestionPostModel ) {
				continue;
			}

			$html_question .= sprintf(
				'<div class="question quiz-question-item" data-question-id="%d">
					<div class="question-title">%s</div>
					<div class="question-answers">%s</div>
				</div>',
				$question_id,
				sprintf(
					'%s. %s %s',
					++ $index,
					esc_html( $questionPostModel->get_the_title() ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url_raw( $questionPostModel->get_edit_link() ),
						esc_html__(
							'(Edit Question)',
							'learnpress'
						)
					)
				),
				$this->html_answer_option( $questionPostModel )
			);
		}

		$number_question_on_page = $quizPostModel->get_meta_value_by_key( QuizPostModel::META_KEY_PAGINATION, 1 );
		$paged                   = 1;
		$total_pages             = DataBase::get_total_pages( $number_question_on_page, $quizPostModel->count_questions() );

		$html_pagination = '';
		if ( $total_pages > 1 ) {
			$html_pagination .= '<div class="questions-pagination">';
			for ( $i = 1; $i <= $total_pages; $i ++ ) {
				$html_pagination .= sprintf(
					'<button class="quiz-page-button %s" data-page="%d">%d</button>',
					( $i === $paged ) ? 'active' : '',
					$i,
					$i
				);
			}
			$html_pagination .= '</div>';
		}

		$section = [
			'wrap'       => '<div class="quiz-questions">',
			'content'    => '<p>' . esc_html__( 'Questions will be displayed here.', 'learnpress' ) . '</p>',
			'questions'  => $html_question,
			'pagination' => $html_pagination,
			'wrap-end'   => '</div>',
		];

		$html .= Template::combine_components( $section );

		return $html;
	}

	/**
	 * Get html edit question type.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 */
	public function html_answer_option( QuestionPostModel $questionPostModel ): string {
		$type = $questionPostModel->get_type();

		// For addon sorting choice old <= v4.0.1
		if ( class_exists( 'LP_Addon_Sorting_Choice_Preload' ) && $type === 'sorting_choice' ) {
			if ( version_compare( LP_ADDON_SORTING_CHOICE_VER, '4.0.1', '<=' ) ) {
				$type = 'sorting_choice_old';
			}
		}

		switch ( $type ) {
			case 'true_or_false':
				$html = $this->html_answer_type_true_or_false( $questionPostModel );
				break;
			case 'single_choice':
				$html = $this->html_answer_type_single_choice( $questionPostModel );
				break;
			case 'multi_choice':
				$html = $this->html_answer_type_multiple_choice( $questionPostModel );
				break;
			case 'fill_in_blanks':
				$html = $this->html_answer_type_fill_in_blanks( $questionPostModel );
				break;
			case 'sorting_choice_old':
				$html = $this->html_answer_type_sorting_choice_old( $questionPostModel );
				break;
			case $type:
				$html = apply_filters( 'learn-press/edit-question/type/html', '', $type, $questionPostModel );
				break;
			default:
				$html = '';
				break;
		}

		return $html;
	}

	public function html_answer_type_true_or_false( QuestionPostModel $questionPostModel ): string {
		$name_radio = 'lp-input-answer-set-true-' . $questionPostModel->ID;
		$answers    = $questionPostModel->get_answer_option();

		$html_answers = '';
		foreach ( $answers as $answer ) {
			$questionAnswerModel = new QuestionAnswerModel( $answer );

			$html_answers .= sprintf(
				'<li class="lp-question-answer-item" data-answer-id="%d">
					<label>
						<input type="radio" value="%s" name="%s">
						%s
					</label>
				</li>',
				$questionAnswerModel->question_answer_id,
				esc_attr( $questionAnswerModel->value ),
				esc_attr( $name_radio ),
				esc_html( $questionAnswerModel->title )
			);
		}

		$section = [
			'header'  => sprintf(
				'<div class="question-description">
					%s
				</div>',
				$questionPostModel->get_the_content()
			),
			'answers' => sprintf(
				'<ul>%s</ul>',
				$html_answers
			),
		];

		return Template::combine_components( $section );
	}

	public function html_answer_type_single_choice( QuestionPostModel $questionPostModel ): string {
		return '<p>' . esc_html__( 'Single choice answer options will be displayed here.', 'learnpress' ) . '</p>';
	}

	public function html_answer_type_multiple_choice( QuestionPostModel $questionPostModel ): string {
		return '<p>' . esc_html__( 'Multiple choice answer options will be displayed here.', 'learnpress' ) . '</p>';
	}

	public function html_answer_type_fill_in_blanks( QuestionPostModel $questionPostModel ): string {
		return '<p>' . esc_html__( 'Fill in the blanks answer options will be displayed here.', 'learnpress' ) . '</p>';
	}

	public function html_answer_type_sorting_choice_old( QuestionPostModel $questionPostModel ): string {
		return '<p>' . esc_html__( 'Sorting choice (old version) answer options will be displayed here.', 'learnpress' ) . '</p>';
	}
}
