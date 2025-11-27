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
			} else {
				$show_check          = $quizPostModel->has_instant_check();
				$show_correct_review = $quizPostModel->has_show_correct_review();
				$question_ids        = $quizPostModel->get_question_ids();
				$retake_count        = $quizPostModel->get_retake_count();
				$status              = $userQuizModel->get_status();
				$quiz_results        = $userQuizModel->get_result();
				$checked_questions   = $userQuizModel->get_checked_questions();
				$retaken             = $userQuizModel->get_retaken_count();
				$can_retake_count    = $userQuizModel->get_remaining_retake();

				$user_data           = array(
					'status'            => $status,
					'attempts'          => $userQuizModel->get_history(),
					'start_time'        => $userQuizModel->get_start_time(),
					'can_retake_count'  => $can_retake_count,
					'total_time'        => $userQuizModel->get_time_remaining(),
					'results'           => $quiz_results,
				);

				$answered = QuizTemplateComponents::instance()->get_array_value( $quiz_results, 'questions', array() );

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
					'title'                  => $quizPostModel->get_the_title(),
					'questions'              => $questions,
					'question_ids'           => $question_ids,
					'number_questions_to_do' => $quizPostModel->count_questions(),
					'current_question'       => absint( reset( $question_ids ) ),
					'question_nav'           => '',
					'status'                 => '',
					'attempts'               => array(),
					'passing_grade'          => $quizPostModel->get_passing_grade(),
					'questions_per_page'     => $quizPostModel->get_question_perpage(),
					'enable_review'          => $quizPostModel->enable_review(),
					'duration'               => $quiz_item->get_duration() ? $quiz_item->get_duration()->get() : false,
					'results'                => array(),
					'required_password'      => post_password_required( $quiz_item->get_id() ),
					'allow_retake'           => $can_retake_count > 0 || $retake_count === -1 ,
					'quiz_description'       => $quizPostModel->get_the_content(),
					'num_pages'              => ceil( (int) $quizPostModel->count_questions() / $quizPostModel->get_question_perpage() ),
				);

				$quiz_data = array_merge( $quiz_data, $user_data );

				echo $this->doing_quiz_html( $quiz_data );
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

			if ( 0 < $quiz_questions_count ) {
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
				// 'header_wrapper'  => '<div class="quiz-start-header">',
				// 'title'           => sprintf( '<h2 class="quiz-title">%s</h2>', esc_html( $quiz_title ) ),
				// 'header_end'      => '</div>',
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
					'<button type="button" class="lp-button start" data-quiz-id="%s">%s</button>',
					esc_attr( $quiz_id ),
					esc_html__( 'Start Quiz', 'learnpress' )
				),
				'actions_end'     => '</div>',
				'wrapper_end'     => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}	

	/**
	 * Render complete quiz interface (during quiz)
	 *
	 * Mirrors the structure from quiz/index.js render method:
	 * - Result component (if completed and not reviewing)
	 * - Meta component (if not started)
	 * - Status component (if started)
	 * - Questions component (if started, completed, or reviewing)
	 * - Buttons component (always)
	 * - Attempts component (if completed, viewed, or not started and not reviewing)
	 *
	 * @param array $quiz_data Quiz data following the $js variable structure
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function doing_quiz_html( $quiz_data = array() ) {
		try {
			$quizComponents = QuizTemplateComponents::instance();
			// Extract key data points.
			$status       = $quizComponents->get_array_value( $quiz_data, 'status', '' );
			$num_pages    = $quizComponents->get_array_value( $quiz_data, 'num_pages', 1, 'int' );
			$is_reviewing = false; // TODO: Add reviewing mode support when implemented.
			// Determine display states based on JavaScript logic.
			$not_started = in_array( $status, array( '', 'viewed' ), true ) || ! $status;
			// Build sections following the JavaScript component structure.
			$sections = array(
				'wrapper' => '<div class="quiz-content-wrapper">',
			);
			// Result component: Show if completed and not reviewing.
			if ( ! $is_reviewing && 'completed' === $status ) {
				$sections['result'] = $quizComponents->result_html( $quiz_data );
			}
			// Meta component: Show if not started and not reviewing.
			if ( ! $is_reviewing && $not_started ) {
				$sections['meta'] = $quizComponents->introduction_html( $quiz_data );
			}
			// Status component: Show if started.
			if ( 'started' === $status ) {
				$sections['status'] = $quizComponents->status_html( $quiz_data );
			}
			// Questions component: Show if started, completed, or reviewing.
			if ( in_array( $status, array( 'completed', 'started' ), true ) || $is_reviewing ) {
				$sections['questions'] = $quizComponents->questions_html( $quiz_data );
			}
			if ( ( 'started' === $status || $is_reviewing ) && $num_pages > 1 ) {
				$sections['pagination'] = $quizComponents->pagination_html( $quiz_data );
			}
			// Buttons component: Always show.
			$sections['buttons'] = $quizComponents->buttons_html( $quiz_data );
			// Attempts component: Show if completed/viewed and not reviewing.
			if ( in_array( $status, array( '', 'completed', 'viewed' ), true ) && ! $is_reviewing ) {
				$sections['attempts'] = $quizComponents->attempts_html( $quiz_data );
			}
			$sections['wrapper_end'] = '</div>';
			return Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
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
	public function load_quiz_content_ajax( $quiz_id = 0, $mode = 'quiz', $user_id = 0 ) {
		try {
			// Step 1: Build $args array with required keys
			$args = array(
				'quiz_id' => $quiz_id,
				'mode'    => $mode, // 'quiz', 'result', or 'review'
				'user_id' => $user_id ? $user_id : get_current_user_id(),
				'nonce'   => wp_create_nonce( 'lp_quiz_ajax_' . $quiz_id ),
			);

			// Step 1: Define $callback function that will receive the AJAX response
			$callback = array(
				'class'  => self::class,
				'method' => 'render_quiz_content_callback',
			);

			// Step 2: Invoke AJAX loader (returns HTML with data attributes for JS)
			return TemplateAJAX::load_content_via_ajax( $args, $callback );
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
		$quiz_id = isset( $data['quiz_id'] ) ? absint( $data['quiz_id'] ) : 0;
		$mode    = isset( $data['mode'] ) ? sanitize_text_field( $data['mode'] ) : 'quiz';
		$user_id = isset( $data['user_id'] ) ? absint( $data['user_id'] ) : get_current_user_id();

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
		switch ( $mode ) {
			case 'not_started':
				$html .= $quizComponents->introduction_html( $quiz_data );
				break;
			case 'quiz':
			case 'review':
				// Quiz mode: render status, questions, and buttons
				$html .= $quizComponents->status_html( $quiz_data ); // Pass quiz_data
				$html .= $quizComponents->questions_html( $quiz_data ); // Pass quiz_data
				$html .= $quizComponents->questions_html( $quiz_data );
				$html .= $quizComponents->buttons_html( $quiz_data ); // Pass quiz_data
				break;

			case 'result':
				// Result mode: render result, buttons, and attempts
				$html .= $quizComponents->result_html( $quiz_data ); // Pass quiz_data
				$html .= $quizComponents->buttons_html( $quiz_data ); // Pass quiz_data
				$html .= $quizComponents->attempts_html( $quiz_data ); // Pass quiz_data
				break;

			default:
				throw new Exception( __( 'Invalid mode', 'learnpress' ) );
		}

		// Return response object
		$response          = new stdClass();
		$response->content = $html;

		return $response;
	}
}