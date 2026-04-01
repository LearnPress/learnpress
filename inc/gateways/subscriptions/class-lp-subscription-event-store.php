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
		 * @var int
		 */
		protected $lock_ttl = 300;

		/**
		 * @return LP_Subscription_Event_Store
		 */
		public static function instance(): LP_Subscription_Event_Store {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * @param string $gateway_id
		 * @param string $event_id
		 *
		 * @return bool
		 */
		public function is_processed( string $gateway_id, string $event_id ): bool {
			$option_key = $this->build_event_option_key( $gateway_id, $event_id );
			return false !== get_option( $option_key, false );
		}

		/**
		 * @param string $gateway_id
		 * @param string $event_id
		 * @param array $payload
		 *
		 * @return bool
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
		 * @param string $gateway_id
		 * @param string $event_id
		 *
		 * @return bool
		 */
		public function acquire_lock( string $gateway_id, string $event_id ): bool {
			$lock_key = $this->build_lock_key( $gateway_id, $event_id );
			if ( get_transient( $lock_key ) ) {
				return false;
			}

			return (bool) set_transient( $lock_key, 1, $this->lock_ttl );
		}

		/**
		 * @param string $gateway_id
		 * @param string $event_id
		 */
		public function release_lock( string $gateway_id, string $event_id ) {
			$lock_key = $this->build_lock_key( $gateway_id, $event_id );
			delete_transient( $lock_key );
		}

		/**
		 * @param string $gateway_id
		 * @param string $event_id
		 *
		 * @return string
		 */
		protected function build_event_option_key( string $gateway_id, string $event_id ): string {
			$gateway_id = sanitize_key( $gateway_id );
			$hash       = md5( $gateway_id . '|' . $event_id );
			return $this->option_prefix . $hash;
		}

		/**
		 * @param string $gateway_id
		 * @param string $event_id
		 *
		 * @return string
		 */
		protected function build_lock_key( string $gateway_id, string $event_id ): string {
			$gateway_id = sanitize_key( $gateway_id );
			$hash       = md5( $gateway_id . '|' . $event_id );
			return $this->lock_prefix . $hash;
		}
	}
}

