<?php

namespace LearnPress\AI\Assistant;

use LearnPress\Services\OpenAiService;

/**
 * QuickQuizEngine — generates and drives interactive quick quizzes.
 *
 * Handles quiz generation from lesson content (start) and stateful
 * answer evaluation across multiple turns (continue_session).
 *
 * @package LearnPress\AI\Assistant
 * @since 4.3.5
 */
class QuickQuizEngine {

	private const QUIZ_PROMPT_HISTORY_LIMIT = 3;
	private const HISTORY_CONTENT_MAX_CHARS = 400;

	private TokenQuotaGuard $quota_guard;
	private LanguageResolver $language_resolver;
	private ResponseNormalizer $normalizer;

	public function __construct(
		TokenQuotaGuard $quota_guard,
		LanguageResolver $language_resolver,
		ResponseNormalizer $normalizer
	) {
		$this->quota_guard       = $quota_guard;
		$this->language_resolver = $language_resolver;
		$this->normalizer        = $normalizer;
	}

	/**
	 * Start a new interactive quick quiz from lesson content.
	 *
	 * @param DataLoaders $loaders      Data loader instance.
	 * @param string      $user_message Learner input (may contain explicit count).
	 * @param int         $lesson_id    Current lesson ID.
	 * @param int         $user_id      Current user ID.
	 * @param array       $history      Conversation history.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 */
	public function start( DataLoaders $loaders, string $user_message, int $lesson_id, int $user_id, array $history ): array {

		$lesson = $loaders->get_lesson_content( $lesson_id, $user_id );
		if ( ! empty( $lesson['error'] ) ) {
			return $this->normalizer->build_response( $lesson['error'] );
		}

		$service            = OpenAiService::instance();
		$history_slice      = $this->slice_recent_history( $history, self::QUIZ_PROMPT_HISTORY_LIMIT );
		$question_count     = $this->extract_requested_quiz_count( $user_message );
		$has_explicit_count = null !== $question_count;
		$language_guidance  = $this->language_resolver->build_instruction( $user_message, $history_slice, $user_id );

		$system_instructions = $has_explicit_count
			? sprintf(
				/* translators: %d: requested number of quiz questions. */
				__( 'Create a quick quiz from lesson content. Return ONLY valid JSON with keys: intro (string), questions (array of exactly %d items). Each question must contain: question (string), options (array of 4 strings), correct_index (0-3 integer), explanation (string).', 'learnpress' ),
				$question_count
			)
			: __( 'Create a quick quiz from lesson content with 3-5 questions. Return ONLY valid JSON with keys: intro (string), questions (array of objects). Each question must contain: question (string), options (array of 4 strings), correct_index (0-3 integer), explanation (string).', 'learnpress' );

		$messages = array(
			array(
				'role'    => 'system',
				'content' => $system_instructions,
			),
			array(
				'role'    => 'system',
				'content' => $language_guidance,
			),
			array(
				'role'    => 'system',
				'content' => wp_json_encode( $lesson ),
			),
		);

		foreach ( $history_slice as $item ) {
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

		$response = $this->quota_guard->send_chat_with_guard( $service, $messages, $user_id );
		if ( $this->quota_guard->is_blocked() ) {
			return $this->normalizer->build_response( $this->quota_guard->get_block_message() );
		}

		$content   = (string) ( $response['content'] ?? '' );
		$decoded   = $this->normalizer->decode_json( $content );
		$questions = $this->sanitize_quiz_questions( $decoded['questions'] ?? array(), $question_count );

		if ( empty( $questions ) ) {
			return $this->normalizer->build_response( __( 'I could not generate a quiz right now. Please try again.', 'learnpress' ) );
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
			'message' => $decoded['intro'] ?? __( 'Quick quiz started. Answer each question to continue.', 'learnpress' ),
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
	public function continue_session( string $user_message, array $state ): array {

		$questions = $state['questions'] ?? array();
		$current   = absint( $state['current_index'] ?? 0 );
		$score     = absint( $state['score'] ?? 0 );

		if ( empty( $questions ) || ! isset( $questions[ $current ] ) ) {
			return $this->normalizer->build_response( __( 'Quiz state is invalid. Please start a new quick quiz.', 'learnpress' ) );
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
			'is_correct'      => $is_correct,
			'selected_index'  => $answer_i,
			'selected_answer' => $question['options'][ $answer_i ] ?? '',
			'correct_index'   => $correct_index,
			'correct_answer'  => $question['options'][ $correct_index ] ?? '',
			'explanation'     => $question['explanation'] ?? '',
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

	// ----------------------------------------------------------------
	// Private helpers
	// ----------------------------------------------------------------

	/**
	 * Keep only recent and valid chat history rows for quiz-generation prompts.
	 *
	 * @param array $history Chat history.
	 * @param int   $limit   Number of rows to keep from the end.
	 *
	 * @return array<int, array{role: string, content: string}>
	 */
	private function slice_recent_history( array $history, int $limit ): array {

		$sanitized = array();

		foreach ( $history as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$role = (string) ( $item['role'] ?? '' );
			if ( ! in_array( $role, array( 'user', 'assistant' ), true ) ) {
				continue;
			}

			$content = trim( (string) ( $item['content'] ?? '' ) );
			if ( '' === $content ) {
				continue;
			}

			$sanitized[] = array(
				'role'    => $role,
				'content' => mb_substr( $content, 0, self::HISTORY_CONTENT_MAX_CHARS, 'UTF-8' ),
			);
		}

		if ( empty( $sanitized ) ) {
			return array();
		}

		return array_slice( $sanitized, -1 * max( 1, $limit ) );
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
	 * Extract the requested number of quiz questions from the learner message.
	 *
	 * Language-agnostic heuristic:
	 * - Prefer explicit numeric forms (e.g. "/quick-quiz 5", "quiz 5", "5 quiz").
	 * - If only one standalone number is present, use it as requested count.
	 * - Return null when count cannot be inferred reliably.
	 *
	 * @param string $message Learner input.
	 *
	 * @return int|null
	 */
	private function extract_requested_quiz_count( string $message ): ?int {

		$normalized = trim( $message );
		if ( '' === $normalized ) {
			return null;
		}

		$normalized = mb_strtolower( $normalized, 'UTF-8' );
		$normalized = preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', $normalized );
		$normalized = is_string( $normalized ) ? trim( preg_replace( '/\s+/', ' ', $normalized ) ?? '' ) : '';
		if ( '' === $normalized ) {
			return null;
		}

		// Explicit command-like request: "/quick-quiz 5".
		if ( preg_match( '/\/(?:mini|quick)-?quiz\s+(\d{1,2})(?=\s|$)/u', $normalized, $matches ) ) {
			$count = (int) ( $matches[1] ?? 0 );
			return ( $count >= 1 && $count <= 20 ) ? $count : null;
		}

		// Number close to "quiz" token works for most mixed-language requests.
		if ( preg_match( '/(?:quiz[^\p{N}]{0,20}(\d{1,2})|(\d{1,2})[^\p{N}]{0,20}quiz)/u', $normalized, $matches ) ) {
			$count = (int) ( $matches[1] ?: $matches[2] ?: 0 );
			if ( $count >= 1 && $count <= 20 ) {
				return $count;
			}
		}

		// Fallback: if there is exactly one standalone number, treat it as count.
		if ( preg_match_all( '/(?<!\p{N})(\d{1,2})(?!\p{N})/u', $normalized, $matches ) && count( $matches[1] ) === 1 ) {
			$count = (int) $matches[1][0];
			if ( $count >= 1 && $count <= 20 ) {
				return $count;
			}
		}

		return null;
	}

	/**
	 * Sanitize and normalize generated quiz questions from model output.
	 *
	 * If $question_count is null, returns questions as-is (trusting OpenAI's output).
	 * If $question_count is set, caps to exactly that many questions.
	 *
	 * @param array    $questions      Raw question payload.
	 * @param int|null $question_count Maximum number of questions to keep, or null to trust model.
	 *
	 * @return array
	 */
	private function sanitize_quiz_questions( array $questions, ?int $question_count = null ): array {

		if ( empty( $questions ) || ! is_array( $questions ) ) {
			return array();
		}

		// If no explicit count, trust OpenAI's output (typically 3-5 questions).
		if ( $question_count === null ) {
			return array_filter(
				array_map( static fn( $q ) => is_array( $q ) ? $q : null, $questions ),
				static fn( $q ) => null !== $q
			);
		}

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

			if ( count( $sanitized ) >= max( 1, $question_count ) ) {
				break;
			}
		}

		return $sanitized;
	}
}
