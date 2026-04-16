<?php

namespace LearnPress\AI\Assistant;

use LearnPress\Services\OpenAiService;

/**
 * IntentClassifier — classifies learner intent via OpenAI.
 *
 * Accepts raw learner input + conversation history and returns one of the
 * supported intent slugs (summarize, explain, quick_quiz, smart_review, general).
 *
 * @package LearnPress\AI\Assistant
 * @since 4.3.5
 */
class IntentClassifier {

	public const INTENT_SUMMARIZE           = 'summarize';
	public const INTENT_EXPLAIN             = 'explain';
	public const INTENT_QUICK_QUIZ          = 'quick_quiz';
	public const INTENT_SMART_REVIEW        = 'smart_review';
	public const INTENT_GENERAL             = 'general';
	private const HISTORY_LIMIT             = 3;
	private const HISTORY_CONTENT_MAX_CHARS = 300;

	private TokenQuotaGuard $quota_guard;
	private ResponseNormalizer $normalizer;

	public function __construct( TokenQuotaGuard $quota_guard, ResponseNormalizer $normalizer ) {
		$this->quota_guard = $quota_guard;
		$this->normalizer  = $normalizer;
	}

	/**
	 * Classify learner intent.
	 *
	 * Returns self::INTENT_GENERAL when classifier is inconclusive or on error.
	 *
	 * @param string $message   Learner input.
	 * @param array  $history   Conversation history.
	 * @param int    $item_id   Current item ID (lesson or quiz).
	 * @param int    $course_id Current course ID.
	 * @param int    $user_id   Current user ID.
	 *
	 * @return string One of the INTENT_* constants.
	 */
	public function classify( string $message, array $history, int $item_id, int $course_id, int $user_id ): string {

		$detected = $this->classify_with_openai( $message, $history, $item_id, $course_id, $user_id );
		if ( $this->is_supported_intent( $detected ) ) {
			return $detected;
		}

		return self::INTENT_GENERAL;
	}

	/**
	 * Return whether intent is one of the known values.
	 *
	 * @param string $intent Intent slug.
	 *
	 * @return bool
	 */
	public function is_supported_intent( string $intent ): bool {

		return in_array(
			$intent,
			array(
				self::INTENT_SUMMARIZE,
				self::INTENT_EXPLAIN,
				self::INTENT_QUICK_QUIZ,
				self::INTENT_SMART_REVIEW,
				self::INTENT_GENERAL,
			),
			true
		);
	}

	// ----------------------------------------------------------------
	// Private helpers
	// ----------------------------------------------------------------

	/**
	 * Ask OpenAI to classify learner intent from natural language.
	 *
	 * @param string $message   Learner input.
	 * @param array  $history   Conversation history.
	 * @param int    $item_id   Current item ID.
	 * @param int    $course_id Current course ID.
	 * @param int    $user_id   Current user ID.
	 *
	 * @return string
	 */
	private function classify_with_openai( string $message, array $history, int $item_id, int $course_id, int $user_id ): string {

		$service            = OpenAiService::instance();
		$item_type          = (string) get_post_type( $item_id );
		$conversation_slice = $this->slice_recent_history( $history, self::HISTORY_LIMIT );
		$context_payload    = array(
			'item_id'   => $item_id,
			'course_id' => $course_id,
			'item_type' => $item_type,
		);

		$messages = array(
			array(
				'role'    => 'system',
				'content' => __( 'You classify learner intent for LearnPress AI Assistant.', 'learnpress' ),
			),
			array(
				'role'    => 'system',
				'content' => __( 'Return ONLY valid JSON object in this exact shape: {"intent":"<value>"}. Allowed values: summarize, explain, quick_quiz, smart_review, general. Do not add extra keys.', 'learnpress' ),
			),
			array(
				'role'    => 'system',
				'content' => __( 'Rules: use smart_review only when the current item type is quiz and the learner asks for quiz-result feedback. If uncertain, return general.', 'learnpress' ),
			),
			array(
				'role'    => 'system',
				'content' => sprintf(
					/* translators: %s: JSON context payload for intent classification. */
					__( 'Intent context (JSON): %s', 'learnpress' ),
					wp_json_encode( $context_payload )
				),
			),
		);

		foreach ( $conversation_slice as $item ) {
			$messages[] = array(
				'role'    => $item['role'],
				'content' => $item['content'],
			);
		}

		$messages[] = array(
			'role'    => 'user',
			'content' => $message,
		);

		try {
			$response = $this->quota_guard->send_chat_with_guard( $service, $messages, $user_id );
		} catch ( \Throwable $e ) {
			return '';
		}

		return $this->parse_intent_from_response( (string) ( $response['content'] ?? '' ) );
	}

	/**
	 * Parse classifier JSON response into a supported intent string.
	 *
	 * @param string $content Raw OpenAI content.
	 *
	 * @return string
	 */
	private function parse_intent_from_response( string $content ): string {

		$content = trim( $content );
		if ( $content === '' ) {
			return '';
		}

		$decoded = $this->normalizer->decode_json( $content );
		if ( ! empty( $decoded ) ) {
			$intent_raw = '';
			foreach ( array( 'intent', 'action', 'type' ) as $key ) {
				if ( isset( $decoded[ $key ] ) && is_string( $decoded[ $key ] ) ) {
					$intent_raw = $decoded[ $key ];
					break;
				}
			}

			if ( $intent_raw !== '' ) {
				return $this->normalize_intent_value( $intent_raw );
			}
		}

		if ( preg_match( '/\b(summarize|summary|explain|quick[\s_-]*quiz|quickquiz|smart[\s_-]*review|general)\b/ui', $content, $matches ) ) {
			return $this->normalize_intent_value( (string) $matches[1] );
		}

		return '';
	}

	/**
	 * Normalize classifier intent value to one of supported constants.
	 *
	 * @param string $intent Raw intent value from classifier.
	 *
	 * @return string
	 */
	private function normalize_intent_value( string $intent ): string {

		$normalized = strtolower( trim( $intent ) );
		$normalized = preg_replace( '/\s+/', '_', $normalized ) ?? $normalized;
		$normalized = str_replace( '-', '_', $normalized );

		$aliases = array(
			'quickquiz'    => self::INTENT_QUICK_QUIZ,
			'quick_quiz'   => self::INTENT_QUICK_QUIZ,
			'quiz'         => self::INTENT_QUICK_QUIZ,
			'review'       => self::INTENT_SMART_REVIEW,
			'summary'      => self::INTENT_SUMMARIZE,
			'smartreview'  => self::INTENT_SMART_REVIEW,
			'smart_review' => self::INTENT_SMART_REVIEW,
		);

		if ( isset( $aliases[ $normalized ] ) ) {
			$normalized = $aliases[ $normalized ];
		}

		return $this->is_supported_intent( $normalized ) ? $normalized : '';
	}

	/**
	 * Keep only recent and valid chat history rows.
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

			$role    = (string) ( $item['role'] ?? '' );
			$content = trim( (string) ( $item['content'] ?? '' ) );

			if ( ! in_array( $role, array( 'user', 'assistant' ), true ) || $content === '' ) {
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
}
