<?php

namespace LearnPress\AI\Assistant;

use LearnPress\Services\OpenAiService;

/**
 * AI Assistant Agent - orchestrates the learner-facing conversation loop.
 *
 * Thin orchestrator that delegates every domain concern to extracted classes:
 * - IntentClassifier  — detects learner intent via OpenAI
 * - TokenQuotaGuard   — enforces daily token quota
 * - LanguageResolver  — builds language-guidance system prompt
 * - QuickQuizEngine    — generates and drives interactive quick quizzes
 * - ResponseNormalizer — decodes JSON output and normalizes text
 *
 * @package LearnPress\AI\Assistant
 * @since 4.3.5
 */
class Agent {

	private const MAX_TOOL_ITERATIONS       = 4;
	private const CHAT_HISTORY_LIMIT        = 3;
	private const HISTORY_CONTENT_MAX_CHARS = 400;

	private IntentClassifier $classifier;
	private TokenQuotaGuard $quota_guard;
	private LanguageResolver $language_resolver;
	private QuickQuizEngine $quiz_engine;
	private ResponseNormalizer $normalizer;

	public function __construct() {
		$this->normalizer        = new ResponseNormalizer();
		$this->quota_guard       = new TokenQuotaGuard();
		$this->language_resolver = new LanguageResolver();
		$this->classifier        = new IntentClassifier( $this->quota_guard, $this->normalizer );
		$this->quiz_engine       = new QuickQuizEngine( $this->quota_guard, $this->language_resolver, $this->normalizer );
	}

	/**
	 * Run the assistant agent loop.
	 *
	 * @param string $user_message  The learner's message.
	 * @param int    $item_id     Current lesson ID.
	 * @param int    $course_id     Current course ID.
	 * @param int    $user_id       Current user ID.
	 * @param array  $history       Previous conversation messages (role/content pairs).
	 * @param array  $active_quiz   Active quiz state for quiz-mode continuation.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 */
	public function run(
		string $user_message,
		int $item_id,
		int $course_id,
		int $user_id,
		array $history = array(),
		array $active_quiz = array(),
		?string $action_hint = null
	): array {

		$this->quota_guard->reset();
		$data_loaders = new DataLoaders();

		// Resume active quiz session.
		if ( ! empty( $active_quiz['is_active'] ) && empty( $active_quiz['completed'] ) ) {
			if ( ! AIAssistantController::is_action_enabled( IntentClassifier::INTENT_QUICK_QUIZ ) ) {
				return $this->get_disabled_action_response( IntentClassifier::INTENT_QUICK_QUIZ );
			}

			return $this->quiz_engine->continue_session( $user_message, $active_quiz );
		}

		// Use validated quick-action hint when present, otherwise classify intent via OpenAI.
		$intent = $this->resolve_intent( $user_message, $history, $item_id, $course_id, $user_id, $action_hint );
		if ( $this->quota_guard->is_blocked() ) {
			return $this->normalizer->build_response( $this->quota_guard->get_block_message() );
		}

		// Gate non-general intents behind admin toggles.
		if ( $this->requires_action_gate( $intent ) && ! AIAssistantController::is_action_enabled( $intent ) ) {
			return $this->get_disabled_action_response( $intent );
		}

		switch ( $intent ) {
			case IntentClassifier::INTENT_SUMMARIZE:
				return $this->handle_summarize( $data_loaders, $user_message, $item_id, $user_id, $history );

			case IntentClassifier::INTENT_EXPLAIN:
				return $this->handle_explain( $data_loaders, $user_message, $item_id, $user_id, $history );

			case IntentClassifier::INTENT_SMART_REVIEW:
				return $this->handle_smart_review( $data_loaders, $user_message, $user_id, $course_id, $item_id, $history );

			case IntentClassifier::INTENT_QUICK_QUIZ:
				return $this->quiz_engine->start( $data_loaders, $user_message, $item_id, $user_id, $history );

			case IntentClassifier::INTENT_GENERAL:
			default:
				return $this->handle_general( $data_loaders, $user_message, $item_id, $user_id, $history );
		}
	}

	/**
	 * Resolve final intent, prioritizing an explicit validated action hint.
	 *
	 * @param string      $user_message Learner input.
	 * @param array       $history      Conversation history.
	 * @param int         $item_id    Current lesson ID.
	 * @param int         $course_id    Current course ID.
	 * @param int         $user_id      Current user ID.
	 * @param string|null $action_hint  Optional quick-action hint from frontend.
	 *
	 * @return string
	 */
	private function resolve_intent(
		string $user_message,
		array $history,
		int $item_id,
		int $course_id,
		int $user_id,
		?string $action_hint
	): string {

		$hint_intent = $this->normalize_action_hint( $action_hint );
		if ( '' !== $hint_intent ) {
			return $hint_intent;
		}

		return $this->classifier->classify( $user_message, $history, $item_id, $course_id, $user_id );
	}

	/**
	 * Normalize optional action hint to a supported intent.
	 *
	 * @param string|null $action_hint Raw action hint.
	 *
	 * @return string
	 */
	private function normalize_action_hint( ?string $action_hint ): string {
		if ( ! is_string( $action_hint ) ) {
			return '';
		}

		$normalized = strtolower( trim( $action_hint ) );
		if ( '' === $normalized ) {
			return '';
		}

		$normalized = str_replace( '-', '_', $normalized );

		$aliases = array(
			'quick_quiz'   => IntentClassifier::INTENT_QUICK_QUIZ,
			'explain'      => IntentClassifier::INTENT_EXPLAIN,
			'summarize'    => IntentClassifier::INTENT_SUMMARIZE,
			'smart_review' => IntentClassifier::INTENT_SMART_REVIEW,
		);

		if ( isset( $aliases[ $normalized ] ) ) {
			$normalized = $aliases[ $normalized ];
		}

		return $this->classifier->is_supported_intent( $normalized ) ? $normalized : '';
	}

	// ----------------------------------------------------------------
	// Intent-specific handlers (thin wrappers around ask_openai_text)
	// ----------------------------------------------------------------

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
	 * Build a summary response grounded in the current lesson content.
	 */
	private function handle_summarize( DataLoaders $loaders, string $message, int $lesson_id, int $user_id, array $history ): array {

		$lesson = $loaders->get_lesson_content( $lesson_id, $user_id );
		if ( ! empty( $lesson['error'] ) ) {
			return $this->normalizer->build_response( $lesson['error'] );
		}

		$instruction = __( 'Summarize this lesson clearly with key points, practical takeaways, and 3 quick review bullets.', 'learnpress' );
		$content     = $this->ask_openai_text( $history, $message, $instruction, array( 'lesson' => $lesson ), $user_id );
		return $this->normalizer->build_response( $content );
	}

	/**
	 * Build a concept explanation response grounded in the current lesson.
	 */
	private function handle_explain( DataLoaders $loaders, string $message, int $lesson_id, int $user_id, array $history ): array {

		$lesson = $loaders->get_lesson_content( $lesson_id, $user_id );
		if ( ! empty( $lesson['error'] ) ) {
			return $this->normalizer->build_response( $lesson['error'] );
		}

		$instruction = __( 'Explain the learner request using lesson context only. Give a short explanation, one concrete example, and one self-check question.', 'learnpress' );
		$content     = $this->ask_openai_text( $history, $message, $instruction, array( 'lesson' => $lesson ), $user_id );
		return $this->normalizer->build_response( $content );
	}

	/**
	 * Build a personalized review for the current completed quiz item.
	 */
	private function handle_smart_review( DataLoaders $loaders, string $message, int $user_id, int $course_id, int $quiz_id, array $history ): array {

		$quiz_review = $loaders->get_quiz_review_result( $user_id, $course_id, $quiz_id );
		if ( ! empty( $quiz_review['error'] ) ) {
			return $this->normalizer->build_response( $quiz_review['error'] );
		}

		$instruction = __( 'Create a smart review for this completed quiz attempt. Summarize performance, identify weak concepts, and provide a concise next-step study plan.', 'learnpress' );
		$content     = $this->ask_openai_text(
			$history,
			$message,
			$instruction,
			array( 'quiz_review' => $quiz_review ),
			$user_id
		);
		return $this->normalizer->build_response( $content );
	}

	/**
	 * Handle open-ended chat requests with lesson-grounded context.
	 */
	private function handle_general( DataLoaders $loaders, string $message, int $lesson_id, int $user_id, array $history ): array {

		$lesson      = $loaders->get_lesson_content( $lesson_id, $user_id );
		$instruction = __( 'Answer naturally and keep guidance grounded in the provided lesson context. If context is missing, say so clearly.', 'learnpress' );
		$content     = $this->ask_openai_text( $history, $message, $instruction, array( 'lesson' => $lesson ), $user_id );
		return $this->normalizer->build_response( $content );
	}

	// ----------------------------------------------------------------
	// Core agentic loop
	// ----------------------------------------------------------------

	/**
	 * Send a text-generation request to OpenAI and normalize the first content response.
	 *
	 * @param array  $history      Prior role/content messages.
	 * @param string $user_message Learner input for this turn.
	 * @param string $instruction  Intent-specific guidance for the model.
	 * @param array  $context      Grounded lesson/course context payload.
	 * @param int    $user_id      Current user ID.
	 *
	 * @return string
	 */
	private function ask_openai_text( array $history, string $user_message, string $instruction, array $context, int $user_id ): string {

		$service       = OpenAiService::instance();
		$history_slice = $this->slice_recent_history( $history, self::CHAT_HISTORY_LIMIT );
		$messages      = array();
		$messages[]    = array(
			'role'    => 'system',
			'content' => $this->get_system_prompt() . "\n" . $instruction,
		);
		$messages[]    = array(
			'role'    => 'system',
			'content' => $this->language_resolver->build_instruction( $user_message, $history_slice, $user_id ),
		);
		$messages[]    = array(
			'role'    => 'system',
			'content' => __( 'Output contract: return ONLY valid JSON with exactly one key "message" (string). Put the full reply text inside "message". Do not include keys like language, locale, intent, type, or key_points.', 'learnpress' ),
		);
		$messages[]    = array(
			'role'    => 'system',
			'content' => sprintf(
				/* translators: %s: JSON encoded learning context. */
				__( 'Grounded context (JSON): %s', 'learnpress' ),
				wp_json_encode( $context )
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

		for ( $i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++ ) {
			$response_message = $this->quota_guard->send_chat_with_guard( $service, $messages, $user_id );
			if ( $this->quota_guard->is_blocked() ) {
				return $this->quota_guard->get_block_message();
			}

			if ( ! empty( $response_message['content'] ) ) {
				return $this->normalizer->normalize( (string) $response_message['content'] );
			}
		}

		return __( 'I was unable to complete the request. Please try again.', 'learnpress' );
	}

	/**
	 * Keep only recent and valid chat history rows for text-generation requests.
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

	// ----------------------------------------------------------------
	// Action gate helpers
	// ----------------------------------------------------------------

	/**
	 * Determine whether the detected intent maps to a gated assistant action.
	 *
	 * @param string $intent Detected intent.
	 *
	 * @return bool
	 */
	private function requires_action_gate( string $intent ): bool {
		return in_array(
			$intent,
			array(
				IntentClassifier::INTENT_SUMMARIZE,
				IntentClassifier::INTENT_EXPLAIN,
				IntentClassifier::INTENT_QUICK_QUIZ,
				IntentClassifier::INTENT_SMART_REVIEW,
			),
			true
		);
	}

	/**
	 * Build a user-facing response for a disabled assistant action.
	 *
	 * @param string $intent Disabled action intent.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 */
	private function get_disabled_action_response( string $intent ): array {
		$action_labels = array(
			IntentClassifier::INTENT_SUMMARIZE    => __( 'Summarize Lesson', 'learnpress' ),
			IntentClassifier::INTENT_EXPLAIN      => __( 'Explain Concept', 'learnpress' ),
			IntentClassifier::INTENT_QUICK_QUIZ   => __( 'Quick Quiz', 'learnpress' ),
			IntentClassifier::INTENT_SMART_REVIEW => __( 'Smart Review', 'learnpress' ),
		);

		return $this->normalizer->build_response(
			sprintf(
				/* translators: %s: assistant action label. */
				__( 'The %s action is currently disabled by the site administrator.', 'learnpress' ),
				$action_labels[ $intent ] ?? __( 'requested', 'learnpress' )
			)
		);
	}
}
