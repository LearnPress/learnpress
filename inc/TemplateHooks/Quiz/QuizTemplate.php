<?php
/**
 * Class QuizTemplate
 *
 * @since 4.2.7.2
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Quiz;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\Models\CourseModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LP_Profile;
use LP_Settings;
use LP_Global;
use LP_Quiz;
use Throwable;

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
		// Hook for rendering start quiz screen.
		// add_action( 'learn-press/quiz-start-screen', array( $this, 'start_quiz_screen' ) );
		add_action( 'learn-press/content-item-summary/lp_quiz', array( $this, 'quiz_content_item_summary' ) );
	}

	public function quiz_content_item_summary() {
		try {
			global $lpCourseModel;
			$courseModel = $lpCourseModel;
			if ( ! $courseModel instanceof CourseModel ) {
				return;
			}

			$userModel = UserModel::find( get_current_user_id(), true );

			$quiz_item = LP_Global::course_item_quiz();
			if ( ! $quiz_item ) {
				return;
			}

			$quizPostModel = QuizPostModel::find( $quiz_item->get_id(), true );
			if ( ! $quizPostModel instanceof QuizPostModel ) {
				return;
			}
			$userQuizModel = UserQuizModel::find_user_item(
				$userModel->get_id(),
				$quiz_item->get_id(),
				LP_QUIZ_CPT,
				$courseModel->get_id(),
				LP_COURSE_CPT,
				true
			);
			if ( ! $userQuizModel instanceof UserQuizModel ) {
				echo $this->start_quiz_screen( $quizPostModel, $quiz_item );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Render start quiz screen.
	 *
	 * @param QuizPostModel $quiz Quiz post model.
	 *
	 * @return void
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function start_quiz_screen( QuizPostModel $quizPostModel, LP_Quiz $quiz_item ) {
		try {
			$quiz_id              = $quizPostModel->get_id();
			$quiz_title           = $quizPostModel->get_the_title();
			$quiz_content         = $quizPostModel->get_the_content();
			$quiz_duration        = $quiz_item->get_duration();
			$duration_text        = $quiz_duration ? learn_press_seconds_to_time( $quiz_duration->get_seconds() ) : '-:-:-';
			$quiz_questions_count = $quizPostModel->count_questions();
			$quiz_passing_grade   = $quizPostModel->get_passing_grade();

			// Build quiz info items.
			$quiz_info_items = '';

			if ( $quiz_questions_count > 0 ) {
				$quiz_info_items .= sprintf(
					'<li class="quiz-intro-item quiz-intro-item--questions-count"><span class="info-label">%s</span><span class="info-value">%s</span></li>',
					esc_html__( 'Questions:', 'learnpress' ),
					esc_html( $quiz_questions_count )
				);
			}

			if ( $quiz_duration ) {
				$quiz_info_items .= sprintf(
					'<li class="quiz-intro-item quiz-intro-item--duration"><span class="info-label">%s</span><span class="info-value">%s</span></li>',
					esc_html__( 'Duration:', 'learnpress' ),
					esc_html( $duration_text )
				);
			}

			if ( $quiz_passing_grade ) {
				$quiz_info_items .= sprintf(
					'<li class="quiz-intro-item quiz-intro-item--passing-grade"><span class="info-label">%s</span><span class="info-value">%s</span></li>',
					esc_html__( 'Passing Grade:', 'learnpress' ),
					esc_html( $quiz_passing_grade . '%' )
				);
			}

			// Build sections array for Template::combine_components.
			$section = array(
				'wrapper'         => '<div class="lp-quiz-start-screen">',
				'header_wrapper'  => '<div class="quiz-start-header">',
				'title'           => sprintf( '<h2 class="quiz-title">%s</h2>', esc_html( $quiz_title ) ),
				'header_end'      => '</div>',
				'content_wrapper' => '<div class="quiz-start-content">',
				'description'     => ! empty( $quiz_content ) ? sprintf(
					'<div class="quiz-description">%s</div>',
					wp_kses_post( wpautop( $quiz_content ) )
				) : '',
				'meta_wrapper'    => '<div class="quiz-meta-info"><ul class="quiz-intro">',
				'meta_items'      => $quiz_info_items,
				'meta_end'        => '</ul></div>',
				'content_end'     => '</div>',
				'actions_wrapper' => '<div class="quiz-buttons is-first quiz-start-actions">',
				'start_button'    => sprintf(
					'<button type="button" class="lp-button lp-button-start-quiz" data-quiz-id="%s">%s</button>',
					esc_attr( $quiz_id ),
					esc_html__( 'Start Quiz', 'learnpress' )
				),
				'actions_end'     => '</div>',
				'wrapper_end'     => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			return '';
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}
}