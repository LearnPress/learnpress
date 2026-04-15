<?php

namespace LearnPress\AI\Assistant;

use LearnPress\Services\OpenAiService;
use LP_Settings;

/**
 * TokenQuotaGuard — per-user daily token quota tracking and enforcement.
 *
 * Wraps OpenAI chat requests with a quota check so that Agent.php is not
 * responsible for any quota bookkeeping.
 *
 * @package LearnPress\AI\Assistant
 * @since 4.3.5
 */
class TokenQuotaGuard {

	private const USER_META_DAILY_TOKEN_USAGE = '_lp_ai_assistant_daily_token_usage';

	/**
	 * Human-readable block message set after the first exhausted-quota call.
	 */
	private string $block_message = '';

	/**
	 * Send an OpenAI chat request guarded by the daily token quota.
	 *
	 * On quota exhaustion the guard sets the block message and returns an
	 * empty array instead of calling the API.
	 *
	 * @param OpenAiService $service  OpenAI service instance.
	 * @param array         $messages Chat messages payload.
	 * @param int           $user_id  Current user ID.
	 *
	 * @return array OpenAI response, or empty array when blocked.
	 * @throws \Throwable Re-throws underlying OpenAI errors.
	 */
	public function send_chat_with_guard( OpenAiService $service, array $messages, int $user_id ): array {

		if ( $this->has_reached_daily_token_limit( $user_id ) ) {
			$this->block_message = $this->build_block_message();
			return array();
		}

		$response = $service->send_chat_request( array( 'messages' => $messages ) );
		$this->track_token_usage_from_response( $user_id, $response );

		if ( $this->has_reached_daily_token_limit( $user_id ) ) {
			$this->block_message = $this->build_block_message();
		}

		return $response;
	}

	/**
	 * Whether the last send_chat_with_guard call was blocked by quota.
	 *
	 * @return bool
	 */
	public function is_blocked(): bool {
		return $this->block_message !== '';
	}

	/**
	 * Human-readable block message (empty string when not blocked).
	 *
	 * @return string
	 */
	public function get_block_message(): string {
		return $this->block_message;
	}

	/**
	 * Reset block state between runs.
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->block_message = '';
	}

	// ----------------------------------------------------------------
	// Private helpers
	// ----------------------------------------------------------------

	/**
	 * Track token usage from an OpenAI response usage payload.
	 *
	 * @param int   $user_id  Current user ID.
	 * @param array $response Raw OpenAI response.
	 *
	 * @return void
	 */
	private function track_token_usage_from_response( int $user_id, array $response ): void {

		$usage        = $response['usage'] ?? array();
		$total_tokens = absint( $usage['total_tokens'] ?? 0 );

		if ( $total_tokens <= 0 ) {
			return;
		}

		$this->increase_daily_token_usage( $user_id, $total_tokens );
	}

	/**
	 * Check whether the learner has reached their daily token limit.
	 *
	 * @param int $user_id Current user ID.
	 *
	 * @return bool
	 */
	private function has_reached_daily_token_limit( int $user_id ): bool {

		$limit = $this->get_daily_token_limit();
		if ( $limit <= 0 ) {
			return false;
		}

		return $this->get_daily_token_usage( $user_id ) >= $limit;
	}

	/**
	 * Get the configured daily token limit from plugin settings.
	 *
	 * @return int  0 means unlimited.
	 */
	private function get_daily_token_limit(): int {
		return absint( LP_Settings::get_option( 'ai_assistant_max_usage_tokens_per_day', 0 ) );
	}

	/**
	 * Read learner daily token usage from user meta.
	 *
	 * @param int $user_id Current user ID.
	 *
	 * @return int
	 */
	private function get_daily_token_usage( int $user_id ): int {

		if ( $user_id <= 0 ) {
			return 0;
		}

		$payload = get_user_meta( $user_id, self::USER_META_DAILY_TOKEN_USAGE, true );
		if ( ! is_array( $payload ) ) {
			return 0;
		}

		$current_date = $this->get_local_current_date();
		$stored_date  = (string) ( $payload['date'] ?? '' );
		if ( $stored_date !== $current_date ) {
			return 0;
		}

		return absint( $payload['total_tokens'] ?? 0 );
	}

	/**
	 * Increase learner daily token usage and persist to user meta.
	 *
	 * @param int $user_id Current user ID.
	 * @param int $tokens  Tokens consumed this call.
	 *
	 * @return void
	 */
	private function increase_daily_token_usage( int $user_id, int $tokens ): void {

		if ( $user_id <= 0 || $tokens <= 0 ) {
			return;
		}

		$current_total = $this->get_daily_token_usage( $user_id );
		$next_total    = $current_total + $tokens;

		update_user_meta(
			$user_id,
			self::USER_META_DAILY_TOKEN_USAGE,
			array(
				'date'         => $this->get_local_current_date(),
				'total_tokens' => $next_total,
			)
		);
	}

	/**
	 * Get local current date key for daily usage bucket.
	 *
	 * @return string
	 */
	private function get_local_current_date(): string {
		return (string) current_time( 'Y-m-d' );
	}

	/**
	 * Build user-facing quota exceeded message.
	 *
	 * @return string
	 */
	private function build_block_message(): string {

		$limit = $this->get_daily_token_limit();

		return sprintf(
			/* translators: %d: max usage tokens per learner per day. */
			__( 'Daily AI usage limit reached (%d tokens). Please try again tomorrow or contact the site administrator.', 'learnpress' ),
			$limit
		);
	}
}
