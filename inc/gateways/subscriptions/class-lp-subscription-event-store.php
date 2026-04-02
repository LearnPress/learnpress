<?php
/**
 * Subscription event idempotency storage.
 *
 * @since 4.3.4
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Subscription_Event_Store' ) ) {
	class LP_Subscription_Event_Store {
		/**
		 * @var LP_Subscription_Event_Store|null
		 */
		protected static $_instance = null;

		/**
		 * @var string
		 */
		protected $option_prefix = 'lp_subscription_event_';

		/**
		 * @var string
		 */
		protected $lock_prefix = 'lp_subscription_event_lock_';

		/**
		 * Lock TTL in seconds for in-flight webhook processing.
		 *
		 * @var int
		 */
		protected $lock_ttl = 300;

		/**
		 * Get singleton instance.
		 *
		 * @return LP_Subscription_Event_Store
		 */
		public static function instance(): LP_Subscription_Event_Store {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Check whether a provider event has already been fully processed.
		 *
		 * Uses a deterministic option key derived from gateway + event id.
		 *
		 * @param string $gateway_id
		 * @param string $event_id
		 *
		 * @return bool True if event was marked processed before.
		 */
		public function is_processed( string $gateway_id, string $event_id ): bool {
			$option_key = $this->build_event_option_key( $gateway_id, $event_id );
			return false !== get_option( $option_key, false );
		}

		/**
		 * Persist a processed marker for idempotency.
		 *
		 * This method intentionally uses add_option to avoid overwriting prior
		 * state when the same event arrives more than once.
		 *
		 * @param string $gateway_id
		 * @param string $event_id
		 * @param array $payload Summary payload stored for debugging/auditing.
		 *
		 * @return bool True when marker is stored, false when option already exists.
		 */
		public function mark_processed( string $gateway_id, string $event_id, array $payload = array() ): bool {
			$option_key = $this->build_event_option_key( $gateway_id, $event_id );
			$value      = array(
				'time'    => time(),
				'gateway' => $gateway_id,
				'event'   => $event_id,
				'payload' => $payload,
			);

			return add_option( $option_key, wp_json_encode( $value ), '', 'no' );
		}

		/**
		 * Acquire a short-lived processing lock for an event.
		 *
		 * Prevents concurrent requests from handling the same event in parallel.
		 *
		 * @param string $gateway_id
		 * @param string $event_id
		 *
		 * @return bool True when lock acquired, false when lock already exists.
		 */
		public function acquire_lock( string $gateway_id, string $event_id ): bool {
			$lock_key = $this->build_lock_key( $gateway_id, $event_id );
			if ( get_transient( $lock_key ) ) {
				return false;
			}

			return (bool) set_transient( $lock_key, 1, $this->lock_ttl );
		}

		/**
		 * Release processing lock for an event.
		 *
		 * @param string $gateway_id
		 * @param string $event_id
		 *
		 * @return void
		 */
		public function release_lock( string $gateway_id, string $event_id ) {
			$lock_key = $this->build_lock_key( $gateway_id, $event_id );
			delete_transient( $lock_key );
		}

		/**
		 * Build option key for processed-event marker.
		 *
		 * @param string $gateway_id
		 * @param string $event_id
		 *
		 * @return string Stable option key.
		 */
		protected function build_event_option_key( string $gateway_id, string $event_id ): string {
			$gateway_id = sanitize_key( $gateway_id );
			$hash       = md5( $gateway_id . '|' . $event_id );
			return $this->option_prefix . $hash;
		}

		/**
		 * Build transient key for in-flight processing lock.
		 *
		 * @param string $gateway_id
		 * @param string $event_id
		 *
		 * @return string Stable lock key.
		 */
		protected function build_lock_key( string $gateway_id, string $event_id ): string {
			$gateway_id = sanitize_key( $gateway_id );
			$hash       = md5( $gateway_id . '|' . $event_id );
			return $this->lock_prefix . $hash;
		}
	}
}

