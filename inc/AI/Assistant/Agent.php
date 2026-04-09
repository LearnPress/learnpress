<?php

namespace LearnPress\AI\Assistant;

use LearnPress\Services\OpenAiService;

/**
 * AI Assistant Agent - handles the OpenAI function-calling loop.
 *
 * Orchestrates multi-turn conversation with tool calls:
 * 1. Sends messages + tool definitions to OpenAI.
 * 2. If model returns tool_calls, executes them via DataLoaders.
 * 3. Appends tool results and re-sends until model returns final content.
 * 4. Normalizes output into the assistant response contract.
 *
 * @package LearnPress\AI\Assistant
 * @since 4.3.5
 */
class Agent {

	private const MAX_TOOL_ITERATIONS = 4;
	private const INTENT_SUMMARIZE    = 'summarize';
	private const INTENT_EXPLAIN      = 'explain';
	private const INTENT_MINI_QUIZ    = 'mini_quiz';
	private const INTENT_SMART_REVIEW = 'smart_review';
	private const INTENT_GENERAL      = 'general';

	/**
	 * System prompt for the assistant model.
	 */
	private function get_system_prompt(): string {
		return __(
			'You are a helpful AI learning assistant for an online course. You help learners understand lesson content, explain concepts, generate practice quizzes, and review their quiz performance. Always ground your answers in the actual course data provided by tools. Respond in the same language the learner uses.',
			'learnpress'
		);
	}

	/**
	 * Run the assistant agent loop.
	 *
	 * @param string $user_message  The learner's message.
	 * @param int    $lesson_id     Current lesson ID.
	 * @param int    $course_id     Current course ID.
	 * @param int    $user_id       Current user ID.
	 * @param array  $history       Previous conversation messages (role/content pairs).
	 * @param array  $active_quiz   Active quiz state for quiz-mode continuation.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 */
	public function run(
		string $user_message,
		int $lesson_id,
		int $course_id,
		int $user_id,
		array $history = array(),
		array $active_quiz = array()
	): array {
		$data_loaders = new DataLoaders();

		if ( ! empty( $active_quiz['is_active'] ) && empty( $active_quiz['completed'] ) ) {
			return $this->continue_interactive_quiz( $user_message, $active_quiz );
		}

		$intent = $this->detect_intent( $user_message );

		switch ( $intent ) {
			case self::INTENT_SUMMARIZE:
				return $this->handle_summarize( $data_loaders, $user_message, $lesson_id, $user_id, $history );

			case self::INTENT_EXPLAIN:
				return $this->handle_explain( $data_loaders, $user_message, $lesson_id, $user_id, $history );

			case self::INTENT_SMART_REVIEW:
				return $this->handle_smart_review( $data_loaders, $user_message, $user_id, $course_id, $history );

			case self::INTENT_MINI_QUIZ:
				return $this->start_interactive_quiz( $data_loaders, $lesson_id, $user_id, $history );

			case self::INTENT_GENERAL:
			default:
				return $this->handle_general( $data_loaders, $user_message, $lesson_id, $user_id, $history );
		}
	}

	/**
	 * Detect the learner intent from slash commands and natural language cues.
	 *
	 * @param string $message Learner input.
	 *
	 * @return string One of the INTENT_* constants.
	 */
	private function detect_intent( string $message ): string {
		$normalized = strtolower( trim( $message ) );

		if ( str_starts_with( $normalized, '/summarize' ) || str_contains( $normalized, 'summarize' ) || str_contains( $normalized, 'summarise' ) ) {
			return self::INTENT_SUMMARIZE;
		}

		if ( str_starts_with( $normalized, '/explain' ) || preg_match( '/\b(explain|clarify|what is|how does)\b/', $normalized ) ) {
			return self::INTENT_EXPLAIN;
		}

		if ( str_starts_with( $normalized, '/mini-quiz' ) || str_contains( $normalized, 'mini quiz' ) || str_contains( $normalized, 'quiz me' ) ) {
			return self::INTENT_MINI_QUIZ;
		}

		if ( str_starts_with( $normalized, '/smart-review' ) || str_contains( $normalized, 'smart review' ) || str_contains( $normalized, 'review my quiz' ) ) {
			return self::INTENT_SMART_REVIEW;
		}

		return self::INTENT_GENERAL;
	}

	/**
	 * Build a summary response grounded in the current lesson content.
	 */
	private function handle_summarize( DataLoaders $loaders, string $message, int $lesson_id, int $user_id, array $history ): array {
		$lesson = $loaders->get_lesson_content( $lesson_id, $user_id );
		if ( ! empty( $lesson['error'] ) ) {
			return $this->build_response( $lesson['error'] );
		}

		$instruction = __( 'Summarize this lesson clearly with key points, practical takeaways, and 3 quick review bullets.', 'learnpress' );
		$content     = $this->ask_openai_text( $history, $message, $instruction, array( 'lesson' => $lesson ) );

		return $this->build_response( $content );
	}

	/**
	 * Build a concept explanation response grounded in the current lesson.
	 */
	private function handle_explain( DataLoaders $loaders, string $message, int $lesson_id, int $user_id, array $history ): array {
		$lesson = $loaders->get_lesson_content( $lesson_id, $user_id );
		if ( ! empty( $lesson['error'] ) ) {
			return $this->build_response( $lesson['error'] );
		}

		$instruction = __( 'Explain the learner request using lesson context only. Give a short explanation, one concrete example, and one self-check question.', 'learnpress' );
		$content     = $this->ask_openai_text( $history, $message, $instruction, array( 'lesson' => $lesson ) );

		return $this->build_response( $content );
	}

	/**
	 * Build a personalized review plan using quiz attempts and course outline.
	 */
	private function handle_smart_review( DataLoaders $loaders, string $message, int $user_id, int $course_id, array $history ): array {
		$results = $loaders->get_quiz_results( $user_id, $course_id );
		$outline = $loaders->get_course_outline( $course_id );

		if ( ! empty( $results['error'] ) ) {
			return $this->build_response( $results['error'] );
		}

		$instruction = __( 'Create a smart review plan grounded in quiz attempts. Focus on weak concepts and recommend specific course sections to revisit.', 'learnpress' );
		$content     = $this->ask_openai_text(
			$history,
			$message,
			$instruction,
			array(
				'quiz_results'   => $results,
				'course_outline' => $outline,
			)
		);

		return $this->build_response( $content );
	}

	/**
	 * Handle open-ended chat requests with lesson-grounded context.
	 */
	private function handle_general( DataLoaders $loaders, string $message, int $lesson_id, int $user_id, array $history ): array {
		$lesson = $loaders->get_lesson_content( $lesson_id, $user_id );

		$instruction = __( 'Answer naturally and keep guidance grounded in the provided lesson context. If context is missing, say so clearly.', 'learnpress' );
		$content     = $this->ask_openai_text( $history, $message, $instruction, array( 'lesson' => $lesson ) );

		return $this->build_response( $content );
	}

	/**
	 * Send a text-generation request to OpenAI and normalize the first content response.
	 *
	 * @param array  $history      Prior role/content messages.
	 * @param string $user_message Learner input for this turn.
	 * @param string $instruction  Intent-specific guidance for the model.
	 * @param array  $context      Grounded lesson/course context payload.
	 *
	 * @return string
	 */
	private function ask_openai_text( array $history, string $user_message, string $instruction, array $context ): string {
		$service    = OpenAiService::instance();
		$messages   = array();
		$messages[] = array(
			'role'    => 'system',
			'content' => $this->get_system_prompt() . "\n" . $instruction,
		);

		$messages[] = array(
			'role'    => 'system',
			'content' => sprintf(
				/* translators: %s: JSON encoded learning context. */
				__( 'Grounded context (JSON): %s', 'learnpress' ),
				wp_json_encode( $context )
			),
		);

		foreach ( $history as $item ) {
			if ( ! empty( $item['role'] ) && isset( $item['content'] ) ) {
				$messages[] = array(
					'role'    => $item['role'],
					'content' => $item['content'],
				);
			}
		}

		$messages[] = array(
			'role'    => 'user',
			'content' => $user_message,
		);

		for ( $i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++ ) {
			$response_message = $service->send_chat_request( array( 'messages' => $messages ) );
			if ( ! empty( $response_message['content'] ) ) {
				return $this->normalize_text_content( (string) $response_message['content'] );
			}
		}

		return __( 'I was unable to complete the request. Please try again.', 'learnpress' );
	}

	/**
	 * Start a new interactive mini-quiz from lesson content.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 */
	private function start_interactive_quiz( DataLoaders $loaders, int $lesson_id, int $user_id, array $history ): array {
		$lesson = $loaders->get_lesson_content( $lesson_id, $user_id );
		if ( ! empty( $lesson['error'] ) ) {
			return $this->build_response( $lesson['error'] );
		}

		$service  = OpenAiService::instance();
		$messages = array(
			array(
				'role'    => 'system',
				'content' => __( 'Create a mini quiz from lesson content. Return ONLY valid JSON with keys: intro (string), questions (array 3 to 5). Each question must contain: question (string), options (array of 4 strings), correct_index (0-3 integer), explanation (string).', 'learnpress' ),
			),
			array(
				'role'    => 'system',
				'content' => wp_json_encode( $lesson ),
			),
		);

		foreach ( $history as $item ) {
			if ( ! empty( $item['role'] ) && isset( $item['content'] ) ) {
				$messages[] = array(
					'role'    => $item['role'],
					'content' => $item['content'],
				);
			}
		}

		$messages[] = array(
			'role'    => 'user',
			'content' => __( 'Start a mini quiz now.', 'learnpress' ),
		);

		$response = $service->send_chat_request( array( 'messages' => $messages ) );
		$content  = (string) ( $response['content'] ?? '' );
		$decoded  = $this->decode_json_content( $content );

		$questions = $this->sanitize_quiz_questions( $decoded['questions'] ?? array() );
		if ( empty( $questions ) ) {
			return $this->build_response( __( 'I could not generate a quiz right now. Please try again.', 'learnpress' ) );
		}

		$quiz_state = array(
			'is_active'     => true,
			'completed'     => false,
			'current_index' => 0,
			'score'         => 0,
			'total'         => count( $questions ),
			'questions'     => $questions,
		);

		return array(
			'type'    => 'quiz',
			'message' => $decoded['intro'] ?? __( 'Mini quiz started. Answer each question to continue.', 'learnpress' ),
			'quiz'    => $quiz_state,
		);
	}

	/**
	 * Process an answer for an active quiz session and advance quiz state.
	 *
	 * @param string $user_message Learner answer input.
	 * @param array  $state        Current quiz state.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 */
	private function continue_interactive_quiz( string $user_message, array $state ): array {
		$questions = $state['questions'] ?? array();
		$current   = absint( $state['current_index'] ?? 0 );
		$score     = absint( $state['score'] ?? 0 );

		if ( empty( $questions ) || ! isset( $questions[ $current ] ) ) {
			return $this->build_response( __( 'Quiz state is invalid. Please start a new mini quiz.', 'learnpress' ) );
		}

		$question = $questions[ $current ];
		$answer_i = $this->parse_answer_index( $user_message, $question['options'] ?? array() );

		if ( $answer_i === null ) {
			return array(
				'type'    => 'quiz',
				'message' => __( 'Please answer with option number (1-4), letter (A-D), or full option text.', 'learnpress' ),
				'quiz'    => $state,
			);
		}

		$correct_index = absint( $question['correct_index'] ?? 0 );
		$is_correct    = $answer_i === $correct_index;
		if ( $is_correct ) {
			++$score;
		}

		$next_index = $current + 1;
		$total      = count( $questions );

		$state['score']         = $score;
		$state['current_index'] = $next_index;
		$state['total']         = $total;
		$state['feedback']      = array(
			'is_correct'     => $is_correct,
			'correct_index'  => $correct_index,
			'correct_answer' => $question['options'][ $correct_index ] ?? '',
			'explanation'    => $question['explanation'] ?? '',
		);

		if ( $next_index >= $total ) {
			$state['is_active'] = false;
			$state['completed'] = true;

			return array(
				'type'    => 'quiz',
				'message' => sprintf(
					/* translators: 1: score, 2: total questions. */
					__( 'Quiz complete. You scored %1$d/%2$d.', 'learnpress' ),
					$score,
					$total
				),
				'quiz'    => $state,
			);
		}

		$state['is_active'] = true;
		$state['completed'] = false;

		$message = $is_correct
			? __( 'Correct. Great job! Moving to the next question.', 'learnpress' )
			: __( 'Not quite. Let us move to the next question.', 'learnpress' );

		return array(
			'type'    => 'quiz',
			'message' => $message,
			'quiz'    => $state,
		);
	}

	/**
	 * Parse learner answer into option index.
	 *
	 * Supports: numeric (1-4), letter (A-D), and exact option text.
	 *
	 * @param string $message Learner answer.
	 * @param array  $options Current question options.
	 *
	 * @return int|null
	 */
	private function parse_answer_index( string $message, array $options ): ?int {
		$input = strtolower( trim( $message ) );
		if ( $input === '' ) {
			return null;
		}

		if ( preg_match( '/^[1-4]$/', $input ) ) {
			return max( 0, (int) $input - 1 );
		}

		$letters = array(
			'a' => 0,
			'b' => 1,
			'c' => 2,
			'd' => 3,
		);
		if ( isset( $letters[ $input ] ) ) {
			return $letters[ $input ];
		}

		foreach ( $options as $index => $option ) {
			if ( strtolower( trim( (string) $option ) ) === $input ) {
				return (int) $index;
			}
		}

		return null;
	}

	/**
	 * Sanitize and normalize generated quiz questions from model output.
	 *
	 * @param array $questions Raw question payload.
	 *
	 * @return array
	 */
	private function sanitize_quiz_questions( array $questions ): array {
		$sanitized = array();
		foreach ( $questions as $question ) {
			if ( ! is_array( $question ) ) {
				continue;
			}

			$options = $question['options'] ?? array();
			if ( ! is_array( $options ) || count( $options ) < 2 ) {
				continue;
			}

			$clean_options = array_values(
				array_map(
					static fn( $option ) => sanitize_text_field( (string) $option ),
					$options
				)
			);

			$correct_index = absint( $question['correct_index'] ?? 0 );
			if ( $correct_index >= count( $clean_options ) ) {
				$correct_index = 0;
			}

			$sanitized[] = array(
				'question'      => sanitize_text_field( (string) ( $question['question'] ?? '' ) ),
				'options'       => $clean_options,
				'correct_index' => $correct_index,
				'explanation'   => sanitize_textarea_field( (string) ( $question['explanation'] ?? '' ) ),
			);
		}

		return $sanitized;
	}

	/**
	 * Decode JSON content safely without throwing exceptions.
	 *
	 * Accepts either pure JSON or content containing a JSON object substring.
	 * Returns empty array when content is plain text or invalid JSON.
	 *
	 * @param string $content Raw model content.
	 *
	 * @return array
	 */
	private function decode_json_content( string $content ): array {
		$content = trim( $content );
		if ( '' === $content ) {
			return array();
		}

		$decoded = json_decode( $content, true );
		if ( is_array( $decoded ) && JSON_ERROR_NONE === json_last_error() ) {
			return $decoded;
		}

		$first_brace = strpos( $content, '{' );
		$last_brace  = strrpos( $content, '}' );
		if ( false === $first_brace || false === $last_brace || $last_brace <= $first_brace ) {
			return array();
		}

		$json_slice = substr( $content, $first_brace, $last_brace - $first_brace + 1 );
		if ( ! is_string( $json_slice ) || '' === $json_slice ) {
			return array();
		}

		$decoded = json_decode( $json_slice, true );

		return ( is_array( $decoded ) && JSON_ERROR_NONE === json_last_error() ) ? $decoded : array();
	}

	/**
	 * Convert model JSON output into readable assistant text.
	 *
	 * OpenAI chat requests in this flow use json_object response format,
	 * so content can be a JSON string like {"message":"..."}.
	 * This method extracts the best text candidate for frontend rendering.
	 *
	 * @param string $content Raw model content.
	 *
	 * @return string
	 */
	private function normalize_text_content( string $content ): string {
		$content = trim( $content );
		if ( '' === $content ) {
			return '';
		}

		$decoded = $this->decode_json_content( $content );
		if ( empty( $decoded ) ) {
			return $content;
		}

		$extracted = $this->extract_text_from_array( $decoded );

		return '' !== $extracted ? $extracted : $content;
	}

	/**
	 * Extract first meaningful text value from a decoded JSON object.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	private function extract_text_from_array( array $data ): string {
		$preferred_keys = array(
			'message',
			'answer',
			'content',
			'summary',
			'explanation',
			'text',
			'response',
			'result',
		);

		foreach ( $preferred_keys as $key ) {
			if ( empty( $data[ $key ] ) ) {
				continue;
			}

			$value = $data[ $key ];
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return trim( $value );
			}

			if ( is_array( $value ) ) {
				$nested = $this->extract_text_from_array( $value );
				if ( '' !== $nested ) {
					return $nested;
				}
			}
		}

		foreach ( $data as $value ) {
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return trim( $value );
			}

			if ( is_array( $value ) ) {
				$nested = $this->extract_text_from_array( $value );
				if ( '' !== $nested ) {
					return $nested;
				}
			}
		}

		return '';
	}

	/**
	 * Build normalized response from assistant content.
	 *
	 * @param string $content The raw assistant text content.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 */
	private function build_response( string $content ): array {
		return array(
			'type'    => 'text',
			'message' => $this->normalize_text_content( $content ),
			'quiz'    => null,
		);
	}
}
