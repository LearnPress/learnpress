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
use LearnPress\TemplateHooks\Question\QuestionTemplate;
use Throwable;

/**
 * QuizTemplateComponent class.
 */
class QuizTemplateComponents {
	use Singleton;

	/**
	 * Initialize hooks.
	 *
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function init() {
		// Hook for rendering start quiz screen.
	}

	/**
	 * Render quiz meta information (intro screen when quiz is not started)
	 *
	 * @param array $quiz_data Quiz data
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function introduction_html( $quiz_data = array() ) {
		try {
			// Get quiz meta data.
			$quiz_description = $this->get_array_value( $quiz_data, 'quiz_description', '' );
			$duration         = $this->get_array_value( $quiz_data, 'duration', 0 );
			$duration_text    = $duration ? learn_press_seconds_to_time( $duration ) : '-:-:-';
			$questions_count  = $this->get_array_value( $quiz_data, 'number_questions_to_do', 0, 'int' );
			$passing_grade    = $this->get_array_value( $quiz_data, 'passing_grade', 0 );
			// Build quiz info items.
			$quiz_info_items = '';
			if ( 0 < $questions_count ) {
				$quiz_info_items .= sprintf(
					'<li class="quiz-intro-item quiz-intro-item--questions-count"><span class="info-label">%s</span><span class="info-value">%s</span></li>',
					esc_html__( 'Questions:', 'learnpress' ),
					esc_html( $questions_count )
				);
			}
			if ( $duration ) {
				$quiz_info_items .= sprintf(
					'<li class="quiz-intro-item quiz-intro-item--duration"><span class="info-label">%s</span><span class="info-value">%s</span></li>',
					esc_html__( 'Duration:', 'learnpress' ),
					esc_html( $duration_text )
				);
			}
			if ( $passing_grade ) {
				$quiz_info_items .= sprintf(
					'<li class="quiz-intro-item quiz-intro-item--passing-grade"><span class="info-label">%s</span><span class="info-value">%s</span></li>',
					esc_html__( 'Passing Grade:', 'learnpress' ),
					esc_html( $passing_grade . '%' )
				);
			}
			// Build sections.
			$sections = array(
				'wrapper'      => '<div class="lp-quiz-start-screen"><div class="quiz-start-content">',
				'description'  => ! empty( $quiz_description ) ? sprintf(
					'<div class="quiz-description">%s</div>',
					wp_kses_post( wpautop( $quiz_description ) )
				) : '',
				'meta_wrapper' => '<div class="quiz-meta-info"><ul class="quiz-intro">',
				'meta_items'   => $quiz_info_items,
				'meta_end'     => '</ul></div>',
				'wrapper_end'  => '</div></div>',
			);
			return Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render quiz questions list
	 *
	 * @param array $quiz_data Quiz data with questions array
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function questions_html( $quiz_data = array() ) {
		try {
			// Get questions data.
			$questions           = $this->get_array_value( $quiz_data, 'questions', array() );
			$current_question    = $this->get_array_value( $quiz_data, 'current_question', 0, 'int' );
			$questions_per_page  = $this->get_array_value( $quiz_data, 'questions_per_page', 1, 'int' );
			$current_page        = $this->get_array_value( $quiz_data, 'current_page', 1, 'int' );
			$status              = $this->get_array_value( $quiz_data, 'status', '' );
			$instant_check       = $this->get_array_value( $quiz_data, 'instant_check', false );
			$is_reviewing        = false; // TODO: Add reviewing mode support.
			// Determine if questions should be shown.
			$is_show = true;
			if ( 'completed' === $status && ! $is_reviewing ) {
				$is_show = false;
			}
			// Render each question.
			$questions_html = '';
			if ( ! empty( $questions ) ) {
				$questionTemplate = QuestionTemplate::instance();
				foreach ( $questions as $index => $question ) {
					// Determine if this question is in the visible range.
					$question_index = $index + 1;
					$is_visible     = $current_page === (int) ceil( $question_index / $questions_per_page );
					if ( $is_visible ) {
						$show_index       = $questions_per_page > 1 ? $question_index : false;
						$questions_html .= $questionTemplate->render_question_html( $question, $show_index, $status, $instant_check );
					}
				}
			}

			// Build sections.
			$sections = array(
				'wrapper'      => sprintf(
					'<div class="quiz-questions" style="display: %s;">',
					$is_show ? 'block' : 'none'
				),
				'questions'    => $questions_html,
				'wrapper_end'  => '</div>',
			);
			return Template::combine_components( $sections );
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
			$time_spend = $duration > 0 ? $duration - $total_time : $total_time;

			// Determine which time to display.
			// $display_time = $time_spend ? $time_spend : $total_time;

			// Format time.
			$formatted_time = learn_press_seconds_to_time( $total_time );

			// Build sections.
			$sections = array(
				'wrapper'      => sprintf( '<div class="countdown" data-duration="%d" data-total-time=%d>', $duration, $total_time ),
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
	public function get_array_value( $array, $key, $default = '', $type = '' ) {
		if ( is_object( $array ) ) {
			$array = (array) $array;
		}
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
				$passed           = $this->get_array_value( $row, 'pass', 0 );
				$graduation_text  = $passed ? __( 'Passed', 'learnpress' ) : __( 'Failed', 'learnpress' );

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
			$passed            = $this->get_array_value( $results, 'pass', 0 );
			$graduation_text   = $passed ? __( 'Passed', 'learnpress' ) : __( 'Failed', 'learnpress' );

			// Determine graduation status.
			if ( isset( $results['graduation'] ) ) {
				$graduation = $results['graduation'];
			} else {
				$graduation = $passed ? 'passed' : 'failed';
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
				'message'                => sprintf( '<p class="result-message">%s</p>', esc_html( $graduation_text ) ),
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
			$enable_review     = $this->get_array_value( $quiz_data, 'enable_review', false, 'bool' );
			$can_retake_count  = $this->get_array_value( $quiz_data, 'can_retake_count', 0, 'int' );
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
			if ( ( ( 'completed' === $status && $allow_retake ) || in_array( $status, array( '', 'viewed' ), true ) ) && ! $is_reviewing && ! $required_password ) {
				$button_text = 'completed' === $status ? __( 'Retake', 'learnpress' ) : __( 'Start', 'learnpress' );

				if ( 'completed' === $status ) {
					$button_text .= sprintf( ' (%d)', $can_retake_count !== 0 ? ( $can_retake_count === -1 ? '' : $can_retake_count ): 0 );
				}

				$start_button = sprintf(
					'<button class="lp-button start">%s</button>',
					esc_html( $button_text )
				);
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
			if ( 'completed' === $status && $enable_review && ! $is_reviewing ) {
				$review_button = sprintf(
					'<button class="lp-button review-quiz">%s</button>',
					esc_html__( 'Review', 'learnpress' )
				);
			}

			// Back to result button.
			$back_button = '';
			if ( $is_reviewing && $enable_review ) {
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