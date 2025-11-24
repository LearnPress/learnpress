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
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render quiz status bar
	 *
	 * @param array $quiz_data Quiz data following the $js variable structure from content-quiz/js.php
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function status_html( $quiz_data = array() ) {
		try {
			// Extract quiz data with defaults, following $js variable structure from js.php.
			$current_page       = $this->get_array_value( $quiz_data, 'current_page', 1, 'int' );
			$questions_per_page = $this->get_array_value( $quiz_data, 'questions_per_page', 1, 'int' );
			$questions_count    = $this->get_array_value( $quiz_data, 'number_questions_to_do', 0, 'int' );
			$user_mark          = $this->get_array_value( $quiz_data, 'user_mark', 0 );
			$submitting         = $this->get_array_value( $quiz_data, 'submitting', false, 'bool' );
			$duration           = $this->get_array_value( $quiz_data, 'duration', 0 );
			$total_time         = $this->get_array_value( $quiz_data, 'total_time', 0, 'int' );
			$time_spend         = $this->get_array_value( $quiz_data, 'time_spend', 0, 'int' );

			// Calculate start and end indices.
			$start = ( ( $current_page - 1 ) * $questions_per_page ) + 1;
			$end   = $start + $questions_per_page - 1;
			$end   = min( $end, $questions_count );

			// Build index HTML.
			$index_html = '';
			if ( $end < $questions_count ) {
				if ( 1 < $questions_per_page ) {
					$index_html = sprintf(
						__( 'Question <span>%d to %d of %d</span>', 'learnpress' ),
						$start,
						$end,
						$questions_count
					);
				} else {
					$index_html = sprintf(
						__( 'Question <span>%d of %d</span>', 'learnpress' ),
						$start,
						$questions_count
					);
				}
			} else {
				$index_html = sprintf(
					__( 'Question <span>%d of %d</span>', 'learnpress' ),
					$start,
					$end
				);
			}

			// Build class names.
			$class_names = array( 'quiz-status' );
			if ( $submitting ) {
				$class_names[] = 'submitting';
			}

			// Build sections for Template::combine_components.
			$sections = array(
				'wrapper'             => sprintf( '<div class="%s">', esc_attr( implode( ' ', $class_names ) ) ),
				'inner_wrapper'       => '<div>',
				'questions_index'     => sprintf( '<div class="questions-index">%s</div>', $index_html ),
				'current_point'       => sprintf(
					'<div class="current-point">%s</div>',
					sprintf( __( 'Earned Point: %s', 'learnpress' ), $user_mark )
				),
				'actions_wrapper'     => '<div>',
				'submit_wrapper'      => '<div class="submit-quiz">',
				'submit_button'       => sprintf(
					'<button class="lp-button" id="button-submit-quiz">%s</button>',
					! $submitting ? esc_html__( 'Finish Quiz', 'learnpress' ) : esc_html__( 'Submitting…', 'learnpress' )
				),
				'submit_wrapper_end'  => '</div>',
				'timer'               => $this->timer_html(
					array(
						'duration'   => $duration,
						'total_time' => $total_time,
						'time_spend' => $time_spend,
					)
				),
				'actions_wrapper_end' => '</div>',
				'inner_wrapper_end'   => '</div>',
				'wrapper_end'         => '</div>',
			);

			return Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render quiz timer
	 *
	 * @param array $timer_data Timer data including duration, total_time, time_spend
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function timer_html( $timer_data = array() ) {
		try {
			$duration   = $this->get_array_value( $timer_data, 'duration', 0 );
			$total_time = $this->get_array_value( $timer_data, 'total_time', 0, 'int' );
			$time_spend = $this->get_array_value( $timer_data, 'time_spend', 0, 'int' );

			// Determine which time to display.
			$display_time = $time_spend ? $time_spend : $total_time;

			// Format time.
			$formatted_time = $this->format_timer_time( $display_time, $total_time );

			// Build sections.
			$sections = array(
				'wrapper'      => '<div class="countdown">',
				'icon'         => '<i class="lp-icon-stopwatch"></i>',
				'time'         => sprintf( '<span>%s</span>', esc_html( $formatted_time ) ),
				'hidden_input' => sprintf( '<input type="hidden" name="lp-quiz-time-spend" value="%d" />', $time_spend ),
				'wrapper_end'  => '</div>',
			);

			return Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Format timer time to display format
	 *
	 * @param int $seconds Seconds to format
	 * @param int $total_time Total time for calculation
	 *
	 * @return string Formatted time string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	private function format_timer_time( $seconds, $total_time ) {
		$time_parts = array();
		$separator  = ':';

		if ( 3600 > $total_time ) {
			// Less than 1 hour: show MM:SS.
			$minutes    = floor( $seconds / 60 );
			$secs       = $seconds % 60;
			$time_parts = array( $minutes, $secs );
		} else {
			// 1 hour or more: show HH:MM:SS.
			$hours      = floor( $seconds / 3600 );
			$remainder  = $seconds % 3600;
			$minutes    = floor( $remainder / 60 );
			$secs       = $remainder % 60;
			$time_parts = array( $hours, $minutes, $secs );
		}

		// Pad with zeros.
		$formatted_parts = array_map(
			function ( $part ) {
				return 10 > $part ? '0' . $part : $part;
			},
			$time_parts
		);

		return implode( $separator, $formatted_parts );
	}

	/**
	 * Safely get value from array with default fallback
	 *
	 * @param array  $array   Array to get value from
	 * @param string $key     Key to retrieve
	 * @param mixed  $default Default value if key doesn't exist
	 * @param string $type    Optional type casting: 'int', 'float', 'bool', 'string'
	 *
	 * @return mixed
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	private function get_array_value( $array, $key, $default = '', $type = '' ) {
		$value = isset( $array[ $key ] ) ? $array[ $key ] : $default;

		switch ( $type ) {
			case 'int':
				return (int) $value;
			case 'float':
				return floatval( $value );
			case 'bool':
				return (bool) $value;
			case 'string':
				return (string) $value;
			default:
				return $value;
		}
	}

	/**
	 * Build a statistic list item HTML
	 *
	 * @param string $class_suffix CSS class suffix
	 * @param string $label        Label text
	 * @param mixed  $value        Value to display
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	private function build_statistic_item( $class_suffix, $label, $value ) {
		return sprintf(
			'<li class="result-statistic-field result-%s">
				<span>%s</span>
				<p>%s</p>
			</li>',
			esc_attr( $class_suffix ),
			esc_html( $label ),
			esc_html( $value )
		);
	}

	/**
	 * Render quiz attempts table
	 *
	 * @param array $quiz_data Quiz data following the $js variable structure, particularly the 'attempts' array
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function attempts_html( $quiz_data = array() ) {
		try {
			// Get attempts from quiz data.
			$attempts = $this->get_array_value( $quiz_data, 'attempts', array() );

			// Return empty if no attempts.
			if ( empty( $attempts ) ) {
				return '';
			}

			// Build table header.
			$table_header = array(
				'header_wrapper'     => '<thead>',
				'header_row'         => '<tr>',
				'th_questions'       => sprintf( '<th class="quiz-attempts__questions">%s</th>', esc_html__( 'Questions', 'learnpress' ) ),
				'th_spend'           => sprintf( '<th class="quiz-attempts__spend">%s</th>', esc_html__( 'Time spent', 'learnpress' ) ),
				'th_marks'           => sprintf( '<th class="quiz-attempts__marks">%s</th>', esc_html__( 'Marks', 'learnpress' ) ),
				'th_grade'           => sprintf( '<th class="quiz-attempts__grade">%s</th>', esc_html__( 'Passing grade', 'learnpress' ) ),
				'th_result'          => sprintf( '<th class="quiz-attempts__result">%s</th>', esc_html__( 'Result', 'learnpress' ) ),
				'header_row_end'     => '</tr>',
				'header_wrapper_end' => '</thead>',
			);

			// Build table rows.
			$table_rows = '<tbody>';
			foreach ( $attempts as $key => $row ) {
				$question_correct = $this->get_array_value( $row, 'question_correct', 0, 'int' );
				$question_count   = $this->get_array_value( $row, 'question_count', 0, 'int' );
				$time_spend       = $this->get_array_value( $row, 'time_spend', '--' );
				$user_mark        = $this->get_array_value( $row, 'user_mark', 0 );
				$mark             = $this->get_array_value( $row, 'mark', 0 );
				$passing_grade    = $this->get_array_value( $row, 'passing_grade', '-' );
				$result           = $this->get_array_value( $row, 'result', 0, 'float' );
				$graduation_text  = $this->get_array_value( $row, 'graduation_text', '' );

				$table_rows .= sprintf(
					'<tr key="attempt-%d">
						<td class="quiz-attempts__questions">%s</td>
						<td class="quiz-attempts__spend">%s</td>
						<td class="quiz-attempts__marks">%s</td>
						<td class="quiz-attempts__grade">%s</td>
						<td class="quiz-attempts__result">%s <span>%s</span></td>
					</tr>',
					$key,
					esc_html( sprintf( '%d / %d', $question_correct, $question_count ) ),
					esc_html( $time_spend ),
					esc_html( sprintf( '%s / %s', $user_mark, $mark ) ),
					esc_html( $passing_grade ),
					esc_html( sprintf( '%.2f%%', $result ) ),
					esc_html( $graduation_text )
				);
			}
			$table_rows .= '</tbody>';

			// Build complete sections.
			$sections = array(
				'wrapper'      => '<div class="quiz-attempts">',
				'heading'      => sprintf( '<h4 class="attempts-heading">%s</h4>', esc_html__( 'Last Attempt', 'learnpress' ) ),
				'table_start'  => '<table>',
				'table_header' => Template::combine_components( $table_header ),
				'table_body'   => $table_rows,
				'table_end'    => '</table>',
				'wrapper_end'  => '</div>',
			);

			return Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render quiz result display
	 *
	 * @param array $quiz_data Quiz data following the $js variable structure, particularly the 'results' data
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function result_html( $quiz_data = array() ) {
		try {
			// Get results from quiz data.
			$results       = $this->get_array_value( $quiz_data, 'results', array() );
			$passing_grade = $this->get_array_value( $quiz_data, 'passing_grade', 0, 'float' );

			// Return empty if no results.
			if ( empty( $results ) ) {
				return '';
			}

			// Extract result data.
			$result_percent    = $this->get_array_value( $results, 'result', 0, 'float' );
			$passing_grade_val = $this->get_array_value( $results, 'passing_grade', $passing_grade, 'float' );
			$time_spend        = $this->get_array_value( $results, 'time_spend', '-' );
			$user_mark         = $this->get_array_value( $results, 'user_mark', 0 );
			$mark              = $this->get_array_value( $results, 'mark', 0 );
			$question_count    = $this->get_array_value( $results, 'question_count', 0, 'int' );
			$question_correct  = $this->get_array_value( $results, 'question_correct', 0, 'int' );
			$question_wrong    = $this->get_array_value( $results, 'question_wrong', 0, 'int' );
			$question_empty    = $this->get_array_value( $results, 'question_empty', 0, 'int' );
			$minus_point       = $this->get_array_value( $results, 'minus_point', null );

			// Determine graduation status.
			if ( isset( $results['graduation'] ) ) {
				$graduation = $results['graduation'];
			} elseif ( $result_percent >= $passing_grade_val ) {
				$graduation = 'passed';
			} else {
				$graduation = 'failed';
			}

			// Determine graduation message.
			if ( isset( $results['graduation_text'] ) ) {
				$message = $results['graduation_text'];
			} elseif ( 'passed' === $graduation ) {
				$message = __( 'Passed', 'learnpress' );
			} else {
				$message = __( 'Failed', 'learnpress' );
			}

			// Format percentage.
			$percent_result = is_int( $result_percent ) ? $result_percent : number_format( $result_percent, 2 );

			// SVG circle calculations.
			$border        = 10;
			$width         = 200;
			$radius        = $width / 2;
			$r             = ( $width - $border ) / 2;
			$circumference = $r * 2 * M_PI;
			$offset        = $circumference - ( $result_percent / 100 * $circumference );

			// Build class names.
			$class_names = array( 'quiz-result', $graduation );

			// Build statistics list using helper method.
			$statistics = '';
			$statistics .= $this->build_statistic_item( 'time-spend', __( 'Time spent', 'learnpress' ), $time_spend );
			$statistics .= sprintf(
				'<li class="result-statistic-field result-point">
					<span>%s</span>
					<p>%s / %s</p>
				</li>',
				esc_html__( 'Points', 'learnpress' ),
				esc_html( $user_mark ),
				esc_html( $mark )
			);
			$statistics .= $this->build_statistic_item( 'questions', __( 'Questions', 'learnpress' ), $question_count );
			$statistics .= $this->build_statistic_item( 'questions-correct', __( 'Correct', 'learnpress' ), $question_correct );
			$statistics .= $this->build_statistic_item( 'questions-wrong', __( 'Wrong', 'learnpress' ), $question_wrong );
			$statistics .= $this->build_statistic_item( 'questions-skipped', __( 'Skipped', 'learnpress' ), $question_empty );

			// Add minus point if available.
			if ( null !== $minus_point ) {
				$statistics .= $this->build_statistic_item( 'questions-minus', __( 'Minus points', 'learnpress' ), $minus_point );
			}

			// Build complete sections.
			$sections = array(
				'wrapper'                => sprintf( '<div class="%s">', esc_attr( implode( ' ', $class_names ) ) ),
				'heading'                => sprintf( '<h3 class="result-heading">%s</h3>', esc_html__( 'Your Result', 'learnpress' ) ),
				'grade_wrapper'          => '<div id="quizResultGrade" class="result-grade">',
				'svg_start'              => sprintf( '<svg class="circle-progress-bar" width="%d" height="%d">', $width, $width ),
				'circle'                 => sprintf(
					'<circle class="circle-progress-bar__circle" stroke="" stroke-width="%d" style="stroke-dasharray: %s %s; stroke-dashoffset: %s;" fill="transparent" r="%s" cx="%s" cy="%s"></circle>',
					$border,
					$circumference,
					$circumference,
					$offset,
					$r,
					$radius,
					$radius
				),
				'svg_end'                => '</svg>',
				'result_achieved'        => sprintf( '<span class="result-achieved">%s%%</span>', esc_html( $percent_result ) ),
				'result_require'         => sprintf( '<span class="result-require">%s%%</span>', esc_html( $passing_grade_val ? $passing_grade_val : '-' ) ),
				'grade_wrapper_end'      => '</div>',
				'message'                => sprintf( '<p class="result-message">%s</p>', esc_html( $message ) ),
				'statistics_wrapper'     => '<ul class="result-statistic">',
				'statistics'             => $statistics,
				'statistics_wrapper_end' => '</ul>',
				'wrapper_end'            => '</div>',
			);

			return Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render quiz buttons (start/retake, pagination, submit, review)
	 *
	 * @param array $quiz_data Quiz data following the $js variable structure
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function buttons_html( $quiz_data = array() ) {
		try {
			// Extract button-related data.
			$status            = $this->get_array_value( $quiz_data, 'status', '' );
			$is_reviewing      = $this->get_array_value( $quiz_data, 'is_reviewing', false, 'bool' );
			$show_review       = $this->get_array_value( $quiz_data, 'show_review', false, 'bool' );
			$can_retry         = $this->get_array_value( $quiz_data, 'can_retry', false, 'bool' );
			$retake_number     = $this->get_array_value( $quiz_data, 'retake_number', null );
			$required_password = $this->get_array_value( $quiz_data, 'required_password', false, 'bool' );
			$allow_retake      = $this->get_array_value( $quiz_data, 'allow_retake', false, 'bool' );
			$num_pages         = $this->get_array_value( $quiz_data, 'num_pages', 1, 'int' );
			$current_page      = $this->get_array_value( $quiz_data, 'current_page', 1, 'int' );
			$question_nav      = $this->get_array_value( $quiz_data, 'question_nav', '' );

			// Build class names.
			$class_names = array( 'quiz-buttons' );

			if ( 'started' === $status || $is_reviewing ) {
				$class_names[] = 'align-center';
			}

			if ( 'questionNav' === $question_nav ) {
				$class_names[] = 'infinity';
			}

			if ( 1 === $current_page ) {
				$class_names[] = 'is-first';
			}

			if ( $current_page === $num_pages ) {
				$class_names[] = 'is-last';
			}

			// Start button/Retake button.
			$start_button = '';
			if ( ( ( 'completed' === $status && $can_retry ) || in_array( $status, array( '', 'viewed' ), true ) ) && ! $is_reviewing && ! $required_password ) {
				$button_text = 'completed' === $status ? __( 'Retake', 'learnpress' ) : __( 'Start', 'learnpress' );

				if ( 'completed' === $status && ! $allow_retake && $retake_number ) {
					$button_text .= sprintf( ' (%d)', $retake_number );
				}

				$start_button = sprintf(
					'<button class="lp-button start">%s</button>',
					esc_html( $button_text )
				);
			}

			// Pagination.
			$pagination = '';
			if ( ( 'started' === $status || $is_reviewing ) && 1 < $num_pages ) {
				$pagination = $this->pagination_html( $quiz_data );
			}

			// Submit button.
			$submit_button = '';
			if ( 'started' === $status && ( 'infinity' === $question_nav || $current_page === $num_pages ) && ! $is_reviewing ) {
				$submit_button = sprintf(
					'<button class="lp-button submit-quiz">%s</button>',
					esc_html__( 'Finish Quiz', 'learnpress' )
				);
			}

			// Review button.
			$review_button = '';
			if ( 'completed' === $status && $show_review && ! $is_reviewing ) {
				$review_button = sprintf(
					'<button class="lp-button review-quiz">%s</button>',
					esc_html__( 'Review', 'learnpress' )
				);
			}

			// Back to result button.
			$back_button = '';
			if ( $is_reviewing && $show_review ) {
				$back_button = sprintf(
					'<button class="lp-button back-quiz">%s</button>',
					esc_html__( 'Result', 'learnpress' )
				);
			}

			// Build complete sections.
			$sections = array(
				'wrapper'          => sprintf( '<div class="%s">', esc_attr( implode( ' ', $class_names ) ) ),
				'button_left'      => '<div class="button-left">',
				'start_button'     => $start_button,
				'pagination'       => $pagination,
				'button_left_end'  => '</div>',
				'button_right'     => '<div class="button-right">',
				'submit_button'    => $submit_button,
				'back_button'      => $back_button,
				'review_button'    => $review_button,
				'button_right_end' => '</div>',
				'wrapper_end'      => '</div>',
			);

			return Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render quiz pagination
	 *
	 * @param array $quiz_data Quiz data with pagination info
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function pagination_html( $quiz_data = array() ) {
		try {
			$num_pages    = $this->get_array_value( $quiz_data, 'num_pages', 1, 'int' );
			$current_page = $this->get_array_value( $quiz_data, 'current_page', 1, 'int' );

			if ( 2 > $num_pages ) {
				return '';
			}

			// Pagination settings.
			$mid_size  = 1;
			$end_size  = 1;
			$prev_next = true;

			$pagination_html = '<div class="questions-pagination"><div class="nav-links">';

			// Previous button.
			if ( $prev_next && 1 < $current_page ) {
				$pagination_html .= sprintf(
					'<button class="page-numbers prev" data-type="question-navx">%s</button>',
					esc_html__( 'Prev', 'learnpress' )
				);
			}

			// Page numbers.
			$dots = false;
			for ( $number = 1; $number <= $num_pages; $number++ ) {
				if ( $number === $current_page ) {
					$dots            = true;
					$pagination_html .= sprintf(
						'<span class="page-numbers current">%d</span>',
						$number
					);
				} elseif ( $number <= $end_size || ( $number >= $current_page - $mid_size && $number <= $current_page + $mid_size ) || $number > $num_pages - $end_size ) {
					$dots            = true;
					$pagination_html .= sprintf(
						'<button class="page-numbers">%d</button>',
						$number
					);
				} elseif ( $dots ) {
					$dots            = false;
					$pagination_html .= '<span class="page-numbers dots">&hellip;</span>';
				}
			}

			// Next button.
			if ( $prev_next && $current_page < $num_pages ) {
				$pagination_html .= sprintf(
					'<button class="page-numbers next" data-type="question-navx">%s</button>',
					esc_html__( 'Next', 'learnpress' )
				);
			}

			$pagination_html .= '</div></div>';

			return $pagination_html;
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}
}