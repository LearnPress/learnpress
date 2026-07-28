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
	 * Prefix of the per-user/per-day advisory lock option.
	 */
	private const LOCK_OPTION_PREFIX = '_lp_ai_assistant_quota_lock_';

	/**
	 * Seconds after which a held lock is considered abandoned.
	 *
	 * Must exceed the slowest realistic OpenAI round trip, otherwise a second request
	 * could steal the lock while the first is still awaiting a response.
	 */
	private const LOCK_TTL = 120;

	/**
	 * Human-readable block message set after the first exhausted-quota call.
	 */
	private string $block_message = '';

	/**
	 * Send an OpenAI chat request guarded by the daily token quota.
	 *
	 * Check-call-record runs inside a per-user/per-day lock. Without it, N concurrent
	 * requests all read the same pre-call usage figure, all pass the check, and all
	 * spend tokens — the quota is bypassed by exactly the amount of concurrency.
	 *
	 * On quota exhaustion, or when the lock cannot be taken, the guard sets a block
	 * message and returns an empty array instead of calling the API (fail closed).
	 *
	 * @param OpenAiService $service  OpenAI service instance.
	 * @param array         $messages Chat messages payload.
	 * @param int           $user_id  Current user ID.
	 *
	 * @return array OpenAI response, or empty array when blocked.
	 * @throws \Throwable Re-throws underlying OpenAI errors.
	 */
	public function send_chat_with_guard( OpenAiService $service, array $messages, int $user_id ): array {

		// 0 means unlimited: nothing to serialize, so do not pay for the lock.
		if ( $this->get_daily_token_limit() <= 0 ) {
			$response = $service->send_chat_request( array( 'messages' => $messages ) );
			$this->track_token_usage_from_response( $user_id, $response );

			return $response;
		}

		if ( ! $this->acquire_quota_lock( $user_id ) ) {
			$this->block_message = $this->build_busy_message();

			return array();
		}

		try {
			// Re-read under the lock: another request may have consumed the remaining
			// budget between our first look and acquiring it.
			if ( $this->has_reached_daily_token_limit( $user_id, true ) ) {
				$this->block_message = $this->build_block_message();

				return array();
			}

			$response = $service->send_chat_request( array( 'messages' => $messages ) );

			// Record actual usage before releasing, so the next waiter reads a current total.
			$this->track_token_usage_from_response( $user_id, $response );

			if ( $this->has_reached_daily_token_limit( $user_id ) ) {
				$this->block_message = $this->build_block_message();
			}

			return $response;
		} finally {
			$this->release_quota_lock( $user_id );
		}
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
	// Quota lock
	// ----------------------------------------------------------------

	/**
	 * Option name of the per-user/per-day lock.
	 *
	 * Scoped by date so a lock abandoned on a previous day can never block today.
	 *
	 * @param int $user_id Current user ID.
	 *
	 * @return string
	 */
	private function get_lock_option_name( int $user_id ): string {
		return self::LOCK_OPTION_PREFIX . $user_id . '_' . $this->get_local_current_date();
	}

	/**
	 * Acquire the quota lock for a user.
	 *
	 * Uses INSERT IGNORE against the unique option_name index, which is atomic across
	 * concurrent PHP workers. add_option()/get_option() cannot be used here: they
	 * read-then-write, leaving exactly the race this lock exists to close. Mirrors the
	 * approach in WP core's WP_Upgrader::create_lock().
	 *
	 * @param int $user_id Current user ID.
	 *
	 * @return bool True when the lock is held by this request.
	 */
	private function acquire_quota_lock( int $user_id ): bool {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return false;
		}

		$lock_option = $this->get_lock_option_name( $user_id );
		$now         = time();

		$acquired = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES ( %s, %s, 'no' )",
				$lock_option,
				(string) $now
			)
		);

		if ( 1 === (int) $acquired ) {
			return true;
		}

		// Someone holds it. Recover only if it is older than the TTL, i.e. the holder
		// died before releasing (fatal error, timeout, killed worker).
		$held_since = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT `option_value` FROM `$wpdb->options` WHERE `option_name` = %s LIMIT 1", $lock_option )
		);

		if ( $held_since > 0 && ( $now - $held_since ) < self::LOCK_TTL ) {
			return false;
		}

		/**
		 * Stale (or unreadable) lock: delete and re-attempt exactly once. The retry is
		 * still an atomic INSERT IGNORE, so if several requests detect the same stale
		 * lock simultaneously only one of them wins.
		 */
		$wpdb->delete( $wpdb->options, array( 'option_name' => $lock_option ) );
		wp_cache_delete( $lock_option, 'options' );

		$acquired = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES ( %s, %s, 'no' )",
				$lock_option,
				(string) $now
			)
		);

		return 1 === (int) $acquired;
	}

	/**
	 * Release the quota lock for a user.
	 *
	 * @param int $user_id Current user ID.
	 *
	 * @return void
	 */
	private function release_quota_lock( int $user_id ): void {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return;
		}

		$lock_option = $this->get_lock_option_name( $user_id );

		$wpdb->delete( $wpdb->options, array( 'option_name' => $lock_option ) );
		wp_cache_delete( $lock_option, 'options' );
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
	 * @param int  $user_id      Current user ID.
	 * @param bool $bypass_cache Re-read usage from the database instead of the object
	 *                           cache. Required for the recheck under the lock, where a
	 *                           value cached earlier in this request would be stale.
	 *
	 * @return bool
	 */
	private function has_reached_daily_token_limit( int $user_id, bool $bypass_cache = false ): bool {

		$limit = $this->get_daily_token_limit();
		if ( $limit <= 0 ) {
			return false;
		}

		if ( $bypass_cache && $user_id > 0 ) {
			wp_cache_delete( $user_id, 'user_meta' );
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

		// Read through to the database: the caller holds the quota lock, and a value
		// cached earlier in this request would undercount the running total.
		wp_cache_delete( $user_id, 'user_meta' );

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
	 * Build user-facing message for a request that could not take the quota lock.
	 *
	 * Reached when another request from the same learner is mid-flight, or a stale lock
	 * has not yet aged past its TTL. Failing closed keeps the quota authoritative.
	 *
	 * @return string
	 */
	private function build_busy_message(): string {
		return __( 'Another AI Assistant request is still running. Please wait a moment and try again.', 'learnpress' );
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
