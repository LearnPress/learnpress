<?php
/**
 * Subscription orchestration service for gateway webhooks.
 *
 * @since 4.3.4
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Subscription_Manager' ) ) {
	class LP_Subscription_Manager {
		/**
		 * @var LP_Subscription_Manager|null
		 */
		protected static $_instance = null;

		const STATUS_TRIAL     = 'trial';
		const STATUS_ACTIVATED = 'activated';
		const STATUS_RENEWED   = 'renewed';
		const STATUS_CANCELLED = 'cancelled';
		const STATUS_EXPIRED   = 'expired';
		const STATUS_SUSPENDED = 'suspended';

		/**
		 * Get singleton instance of subscription manager.
		 *
		 * @return LP_Subscription_Manager
		 */
		public static function instance(): LP_Subscription_Manager {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new static();
			}

			return self::$_instance;
		}

		/**
		 * Process a normalized subscription webhook event.
		 *
		 * Flow:
		 * - Build deterministic event id.
		 * - Resolve parent order.
		 * - Check order state for duplicates (order-state-based idempotency).
		 * - Route by event type (activate, renew, fail, cancel, expire, update).
		 * - Sync subscription meta and update order state.
		 *
		 * @param LP_Gateway_Abstract $gateway
		 * @param array               $event Normalized payload from gateway::normalize_subscription_event().
		 *
		 * @return array{
		 *     status:string,
		 *     event_id:string,
		 *     event_type:string,
		 *     status_code:int,
		 *     order_id?:int,
		 *     renewal_order_id?:int,
		 *     message?:string
		 * }
		 * @deprecated 4.3.8 Use LP_Gateway_Abstract::capture_subscription_webhook() instead.
		 */
//		public function process_webhook_event( LP_Gateway_Abstract $gateway, array $event ): array {
//			$gateway_id = $gateway->get_id();
//			$event_id   = sanitize_text_field( (string) ( $event['event_id'] ?? '' ) );
//			$event_type = sanitize_key( (string) ( $event['event_type'] ?? '' ) );
//
//			if ( empty( $event_id ) ) {
//				$event_id = md5( wp_json_encode( $event ) );
//			}
//
//			$response = array(
//				'status'      => 'ignored',
//				'event_id'    => $event_id,
//				'event_type'  => $event_type,
//				'status_code' => 200,
//			);
//
//			try {
//				$order_subscription_id = $this->resolve_parent_order_id( $event );
//				$order_subscription    = $order_subscription_id ? learn_press_get_order( $order_subscription_id ) : false;
//				$this->validate_parent_subscription_binding( $order_subscription, $event );
//
//				if ( $this->is_event_already_handled( $event_type, $event_id, $order_subscription, $event ) ) {
//					return array(
//						'status'      => 'duplicate',
//						'event_id'    => $event_id,
//						'event_type'  => $event_type,
//						'status_code' => 200,
//					);
//				}
//
//				if ( $order_subscription ) {
//						$this->sync_subscription_meta( $order_subscription->get_id(), $event );
//				}
//
//				switch ( $event_type ) {
//					case 'subscription_activated':
//						if ( ! $order_subscription ) {
//							throw new Exception( __( 'Order not found.', 'learnpress' ) );
//						}
//
//						$this->update_subscription_status( $order_subscription->get_id(), 'active' );
//						// Team convention: activated means the subscription order becomes completed.
//						$this->mark_parent_payment_completed( $order_subscription, $event );
//						$order_subscription->add_note( __( 'Subscription activated.', 'learnpress' ) );
//						do_action( 'learn-press/subscription/activated', $order_subscription->get_id(), $event, $gateway_id );
//
//						$response['status']   = 'success';
//						$response['order_id'] = $order_subscription->get_id();
//						break;
//					case 'initial_payment_succeeded':
//						if ( ! $order_subscription ) {
//							throw new Exception( __( 'Parent subscription order not found.', 'learnpress' ) );
//						}
//
//						$this->update_subscription_status( $order_subscription->get_id(), 'active' );
//						$this->mark_parent_payment_completed( $order_subscription, $event );
//							$order_subscription->add_note( __( 'Initial subscription payment succeeded.', 'learnpress' ) );
//						do_action( 'learn-press/subscription/initial-payment-succeeded', $order_subscription->get_id(), $event, $gateway_id );
//
//						$response['status']   = 'success';
//						$response['order_id'] = $order_subscription->get_id();
//						break;
//					case 'renewal_payment_succeeded':
//						if ( ! $order_subscription ) {
//							throw new Exception( __( 'Parent subscription order not found.', 'learnpress' ) );
//						}
//
//						$this->update_subscription_status( $order_subscription->get_id(), 'active' );
//						/*
//						 * PayPal may report the first successful charge as PAYMENT.SALE.COMPLETED.
//						 * If the parent subscription order is still not completed, treat this as
//						 * the initial payment instead of creating a renewal order.
//						 */
//						if ( 'paypal' === $gateway_id && ! $order_subscription->is_completed() ) {
//							$this->mark_parent_payment_completed( $order_subscription, $event );
//							$order_subscription->add_note( __( 'Initial subscription payment succeeded.', 'learnpress' ) );
//							do_action( 'learn-press/subscription/initial-payment-succeeded', $order_subscription->get_id(), $event, $gateway_id );
//
//							$response['status']   = 'success';
//							$response['order_id'] = $order_subscription->get_id();
//							break;
//						}
//
//						$order_renew = $this->create_renewal_order( $order_subscription, $event, LP_ORDER_COMPLETED );
//						$order_subscription->add_note( __( 'Subscription renewal payment succeeded.', 'learnpress' ) );
//						do_action( 'learn-press/subscription/renewal-order-created', $order_renew->get_id(), $order_subscription->get_id(), $event, $gateway_id );
//						do_action( 'learn-press/subscription/renewed', $order_subscription->get_id(), $order_renew->get_id(), $event, $gateway_id );
//
//						$response['status']           = 'success';
//						$response['order_id']         = $order_subscription->get_id();
//						$response['renewal_order_id'] = $order_renew->get_id();
//						break;
//					case 'renewal_payment_failed':
//						if ( ! $order_subscription ) {
//							throw new Exception( __( 'Parent subscription order not found.', 'learnpress' ) );
//						}
//
//						$this->update_subscription_status( $order_subscription->get_id(), 'past_due' );
//						$order_renew = $this->create_renewal_order( $order_subscription, $event, LP_ORDER_FAILED );
//						$order_subscription->add_note( __( 'Subscription renewal payment failed.', 'learnpress' ) );
//							do_action( 'learn-press/subscription/renewal-order-created', $order_renew->get_id(), $order_subscription->get_id(), $event, $gateway_id );
//						do_action( 'learn-press/subscription/payment-failed', $order_subscription->get_id(), $order_renew->get_id(), $event, $gateway_id );
//
//							$response['status']       = 'failed';
//						$response['order_id']         = $order_subscription->get_id();
//						$response['renewal_order_id'] = $order_renew->get_id();
//						break;
//					case 'subscription_cancelled':
//						if ( ! $order_subscription ) {
//							throw new Exception( __( 'Parent subscription order not found.', 'learnpress' ) );
//						}
//
//						$this->update_subscription_status( $order_subscription->get_id(), 'cancelled' );
//							$order_subscription->add_note( __( 'Subscription cancelled.', 'learnpress' ) );
//						do_action( 'learn-press/subscription/cancelled', $order_subscription->get_id(), $event, $gateway_id );
//
//						$response['status']       = 'cancelled';
//							$response['order_id'] = $order_subscription->get_id();
//						break;
//					case 'subscription_suspended':
//						if ( ! $order_subscription ) {
//							throw new Exception( __( 'Parent subscription order not found.', 'learnpress' ) );
//						}
//
//						$this->update_subscription_status( $order_subscription->get_id(), 'suspended' );
//							$order_subscription->add_note( __( 'Subscription suspended.', 'learnpress' ) );
//						do_action( 'learn-press/subscription/suspended', $order_subscription->get_id(), $event, $gateway_id );
//
//						$response['status']       = 'suspended';
//							$response['order_id'] = $order_subscription->get_id();
//						break;
//					case 'subscription_expired':
//						if ( ! $order_subscription ) {
//							throw new Exception( __( 'Parent subscription order not found.', 'learnpress' ) );
//						}
//
//						$this->update_subscription_status( $order_subscription->get_id(), 'expired' );
//						$order_subscription->add_note( __( 'Subscription expired.', 'learnpress' ) );
//						do_action( 'learn-press/subscription/expired', $order_subscription->get_id(), $event, $gateway_id );
//
//						$response['status']   = 'expired';
//						$response['order_id'] = $order_subscription->get_id();
//						break;
//					case 'subscription_updated':
//						if ( $order_subscription ) {
//							$order_subscription->add_note( __( 'Subscription updated.', 'learnpress' ) );
//							$response['order_id'] = $order_subscription->get_id();
//						}
//						$response['status'] = 'updated';
//						break;
//					default:
//						$response['status'] = 'ignored';
//						break;
//				}
//			} catch ( Throwable $e ) {
//				$response['status']      = 'error';
//				$response['status_code'] = 400;
//				$response['message']     = $e->getMessage();
//				error_log( 'LP_Subscription_Manager: ' . $e->getMessage() );
//			}
//
//			return $response;
//		}

		/**
		 * Check whether the webhook event outcome is already reflected in order state.
		 *
		 * Replaces the old LP_Subscription_Event_Store transient-based approach with
		 * order-state checks: status, transaction_id, and event_id meta.
		 *
		 * @since 4.3.5
		 *
		 * @param string         $event_type   Normalized event type.
		 * @param string         $event_id     Provider event ID.
		 * @param LP_Order|false $order_subscription Resolved subscription order (false when not found).
		 * @param array          $event        Full normalized event payload.
		 *
		 * @return bool True if the event outcome is already reflected in the database.
		 * @deprecated 4.3.8
		 */
		/*private function is_event_already_handled( string $event_type, string $event_id, $order_subscription, array $event ): bool {

			if ( ! $order_subscription instanceof LP_Order ) {
				return false;
			}

			$order_subscription_id = $order_subscription->get_id();

			switch ( $event_type ) {
				case 'subscription_activated':
					$sub_status = get_post_meta( $order_subscription_id, LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS, true );
					return in_array( $sub_status, array( 'active', 'trialing' ), true );

				case 'initial_payment_succeeded':
					$sub_status = get_post_meta( $order_subscription_id, LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS, true );
					if ( ! in_array( $sub_status, array( 'active', 'trialing' ), true ) || ! $order_subscription->is_completed() ) {
						return false;
					}

					$event_transaction_id = sanitize_text_field( (string) ( $event['transaction_id'] ?? '' ) );
					if ( '' === $event_transaction_id ) {
						return true;
					}

					$saved_transaction_id = sanitize_text_field(
						(string) get_post_meta( $order_subscription_id, '_transaction_id', true )
					);
					if ( '' === $saved_transaction_id ) {
						// Need one more pass to backfill transaction id into parent order.
						return false;
					}

					return $saved_transaction_id === $event_transaction_id;

				case 'renewal_payment_succeeded':
				case 'renewal_payment_failed':
					$event_transaction_id = sanitize_text_field( (string) ( $event['transaction_id'] ?? '' ) );
					if ( ! empty( $event_transaction_id ) ) {
						$parent_transaction_id = sanitize_text_field(
							(string) get_post_meta( $order_subscription_id, '_transaction_id', true )
						);
						if ( '' !== $parent_transaction_id && $parent_transaction_id === $event_transaction_id ) {
							return true;
						}
					}

					$renewal_key = $this->get_renewal_key( $event );
					if ( ! empty( $renewal_key ) && $this->find_renewal_order_by_key( $order_subscription_id, $renewal_key ) ) {
						return true;
					}
					if ( ! empty( $event_id ) && $this->find_renewal_order_by_event( $order_subscription_id, $event_id ) ) {
						return true;
					}
					return false;

				case 'subscription_cancelled':
					$current = get_post_meta( $order_subscription_id, LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS, true );
					return 'cancelled' === $current;

				case 'subscription_suspended':
					$current = get_post_meta( $order_subscription_id, LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS, true );
					return 'suspended' === $current;

				case 'subscription_expired':
					$current = get_post_meta( $order_subscription_id, LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS, true );
					return 'expired' === $current;

				case 'subscription_updated':
					$last_event = get_post_meta( $order_subscription_id, LP_Gateway_Abstract::META_SUBSCRIPTION_LAST_EVENT_ID, true );
					return ! empty( $event_id ) && $last_event === $event_id;
				default:
					return false;
			}
		}*/

		/**
		 * Resolve parent order id for an incoming event.
		 *
		 * Resolution priority:
		 * 1) event[parent_order_id]
		 * 2) event[metadata][lp_order_id]
		 * 3) lookup by subscription_id in order meta.
		 *
		 * @param array $event Normalized event payload.
		 *
		 * @return int Parent order id or 0 when not found.
		 * @deprecated 4.3.8
		 */
		/*public function resolve_parent_order_id( array $event ): int {

			$order_subscription_id = absint( $event['parent_order_id'] ?? 0 );
			if ( $order_subscription_id ) {
				return $order_subscription_id;
			}
			$metadata = (array) ( $event['metadata'] ?? array() );
			if ( ! empty( $metadata['lp_order_id'] ) ) {
				return absint( $metadata['lp_order_id'] );
			}

			$subscription_id = sanitize_text_field( (string) ( $event['subscription_id'] ?? '' ) );
			if ( empty( $subscription_id ) ) {
				return 0;
			}

			$order_ids = get_posts(
				array(
					'post_type'      => LP_ORDER_CPT,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'   => LP_Gateway_Abstract::META_SUBSCRIPTION_ID,
							'value' => $subscription_id,
						),
					),
				)
			);

			if ( empty( $order_ids ) ) {
				return 0;
			}

			return absint( $order_ids[0] );
		}*/

		/**
		 * Persist shared subscription meta onto a parent order.
		 *
		 * This keeps gateway/provider identifiers available for future lookups
		 * and reconciliation.
		 *
		 * @param int   $order_id
		 * @param array $event Normalized event payload.
		 *
		 * @return void
		 * @deprecated 4.3.8
		 */
		/*public function sync_subscription_meta( int $order_id, array $event ) {

			if ( ! empty( $event['subscription_id'] ) ) {
				update_post_meta( $order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_ID, sanitize_text_field( (string) $event['subscription_id'] ) );
			}
			if ( ! empty( $event['customer_id'] ) ) {
				update_post_meta( $order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_CUSTOMER_ID, sanitize_text_field( (string) $event['customer_id'] ) );
			}

			if ( ! empty( $event['price_id'] ) ) {
				update_post_meta( $order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_PLAN_ID, sanitize_text_field( (string) $event['price_id'] ) );
			}
			if ( ! empty( $event['event_id'] ) ) {
				update_post_meta( $order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_LAST_EVENT_ID, sanitize_text_field( (string) $event['event_id'] ) );
			}
			if ( ! empty( $event['metadata'] ) && is_array( $event['metadata'] ) ) {
				update_post_meta( $order_id, '_lp_subscription_metadata', $event['metadata'] );
			}
		}*/

		/**
		 * Update current subscription status stored on parent order.
		 *
		 * @param int    $order_id
		 * @param string $status Normalized status slug (active/past_due/cancelled/...).
		 * @return void
		 * @deprecated 4.3.8
		 */
		/*public function update_subscription_status( int $order_id, string $status ) {

			update_post_meta( $order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS, sanitize_key( $status ) );
		}*/
		/**
		 * Mark parent order paid when initial subscription activation succeeds.
		 *
		 * @param LP_Order $order_subscription
		 * @param array    $event Normalized event payload.
		 *
		 * @return void
		 * @deprecated 4.3.8
		 */
		/*protected function mark_parent_payment_completed( LP_Order $order_subscription, array $event ) {
			$transaction_id = sanitize_text_field( (string) ( $event['transaction_id'] ?? '' ) );
			if ( ! $order_subscription->is_completed() ) {
				$order_subscription->payment_complete( $transaction_id );
				return;
			}

			// Parent order is already completed (e.g. completed at ACTIVATED). Backfill
			// transaction id when later webhook provides it.
			if ( '' !== $transaction_id ) {
				$saved_transaction_id = sanitize_text_field(
					(string) get_post_meta( $order_subscription->get_id(), '_transaction_id', true )
				);
				if ( '' === $saved_transaction_id ) {
					update_post_meta( $order_subscription->get_id(), '_transaction_id', $transaction_id );
				}
			}
		}*/
		/**
		 * Create or reuse a renewal child order for a renewal event.
		 *
		 * Idempotency strategy:
		 * - First by renewal_key (strongest provider-derived key).
		 * - Fallback by event_id.
		 *
		 * @param LP_Order $order_subscription
		 * @param array    $event Normalized event payload.
		 * @param string   $target_status Target order status for renewal result.
		 *
		 * @return LP_Order Existing or newly-created renewal order.
		 * @throws Exception
		 * @deprecated 4.3.8
		 */
//		public function create_renewal_order( LP_Order $order_subscription, array $event, string $target_status = LP_ORDER_PENDING ): LP_Order {
//
//			$renewal_key = $this->get_renewal_key( $event );
//			if ( ! empty( $renewal_key ) ) {
//				$existing = $this->find_renewal_order_by_key( $order_subscription->get_id(), $renewal_key );
//				if ( $existing ) {
//					return $existing;
//				}
//			}
//
//			$event_id = sanitize_text_field( (string) ( $event['event_id'] ?? '' ) );
//			if ( ! empty( $event_id ) ) {
//				$existing = $this->find_renewal_order_by_event( $order_subscription->get_id(), $event_id );
//				if ( $existing ) {
//					return $existing;
//				}
//			}
//
//			$renewal_total    = isset( $event['amount'] ) && (float) $event['amount'] > 0 ? (float) $event['amount'] : (float) $order_subscription->get_total();
//			$renewal_subtotal = isset( $event['amount'] ) && (float) $event['amount'] > 0 ? (float) $event['amount'] : (float) $order_subscription->get_subtotal();
//			$renewal_currency = ! empty( $event['currency'] ) ? sanitize_text_field( (string) $event['currency'] ) : $order_subscription->get_currency();
//
//			$order_renew = new LP_Order();
//			$order_renew->set_parent_id( $order_subscription->get_id() );
//			$order_renew->set_user_id( $order_subscription->get_user_id() );
//			$order_renew->set_checkout_email( $order_subscription->get_checkout_email() );
//			$order_renew->set_status( LP_ORDER_PENDING );
//			$order_renew->set_created_via( 'subscription' );
//			$order_renew->set_currency( $renewal_currency );
//			$order_renew->set_total( $renewal_total );
//			$order_renew->set_subtotal( $renewal_subtotal );
//			$order_renew->set_data( 'payment_method', $order_subscription->get_data( 'payment_method', '' ) );
//			$order_renew->set_data( 'payment_method_title', $order_subscription->get_payment_method_title() );
//
//			$renewal_order_id = $order_renew->save();
//			if ( empty( $renewal_order_id ) ) {
//				throw new Exception( __( 'Cannot create renewal order.', 'learnpress' ) );
//			}
//
//			$this->copy_parent_order_items_to_renewal( $order_subscription, $order_renew );
//			if ( ! empty( $event_id ) ) {
//						update_post_meta( $renewal_order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_EVENT_ID, $event_id );
//			}
//			if ( ! empty( $event['subscription_id'] ) ) {
//				update_post_meta( $renewal_order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_ID, sanitize_text_field( (string) $event['subscription_id'] ) );
//			}
//
//			if ( LP_ORDER_COMPLETED !== $target_status && ! empty( $event['transaction_id'] ) ) {
//				update_post_meta( $renewal_order_id, '_transaction_id', sanitize_text_field( (string) $event['transaction_id'] ) );
//			}
//			if ( ! empty( $renewal_key ) ) {
//				update_post_meta( $renewal_order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_RENEWAL_KEY, $renewal_key );
//			}
//
//			if ( LP_ORDER_COMPLETED === $target_status ) {
//				$order_renew->payment_complete( (string) ( $event['transaction_id'] ?? '' ) );
//			} else {
//				$order_renew->update_status( $target_status );
//			}
//
//			$order_renew->add_note(
//				sprintf(
//					/* translators: %s: parent order number */
//					__( 'Subscription renewal generated from parent order %s.', 'learnpress' ),
//					$order_subscription->get_order_number()
//				)
//			);
//
//			return $order_renew;
//		}

		/**
		 * Ensure webhook subscription id matches the resolved parent order binding.
		 *
		 * This prevents applying a callback payload to a parent order when provider
		 * identifiers do not match (e.g. billing_agreement_id mismatch on PayPal).
		 *
		 * @param LP_Order|false $order_subscription Resolved subscription order.
		 * @param array          $event Normalized event payload.
		 *
		 * @return void
		 * @throws Exception
		 * @deprecated 4.3.8
		 */
		/*protected function validate_parent_subscription_binding( $order_subscription, array $event ) {

			if ( ! $order_subscription instanceof LP_Order ) {
				return;
			}

			$event_subscription_id = sanitize_text_field( (string) ( $event['subscription_id'] ?? '' ) );
			if ( empty( $event_subscription_id ) ) {
				return;
			}

			$saved_subscription_id = sanitize_text_field(
				(string) get_post_meta( $order_subscription->get_id(), LP_Gateway_Abstract::META_SUBSCRIPTION_ID, true )
			);
			if ( ! empty( $saved_subscription_id ) && $saved_subscription_id !== $event_subscription_id ) {
				throw new Exception( __( 'Subscription id does not match parent order.', 'learnpress' ) );
			}
		}*/
		/**
		 * Find renewal child order by parent id + provider event id.
		 *
		 * @param int    $parent_order_id
		 * @param string $event_id
		 *
		 * @return LP_Order|false
		 * @deprecated 4.3.8
		 */
		/*protected function find_renewal_order_by_event( int $parent_order_id, string $event_id ) {
			$order_ids = get_posts(
				array(
					'post_type'      => LP_ORDER_CPT,
					'post_status'    => 'any',
					'post_parent'    => $parent_order_id,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'   => LP_Gateway_Abstract::META_SUBSCRIPTION_EVENT_ID,
							'value' => $event_id,
						),
					),
				)
			);

			if ( empty( $order_ids ) ) {
				return false;
			}

			return learn_press_get_order( absint( $order_ids[0] ) );
		}*/

		/**
		 * Find renewal child order by parent id + renewal key.
		 *
		 * @param int    $parent_order_id
		 * @param string $renewal_key
		 *
		 * @return LP_Order|false
		 * @deprecated 4.3.8
		 */
		/*protected function find_renewal_order_by_key( int $parent_order_id, string $renewal_key ) {
			$order_ids = get_posts(
				array(
					'post_type'      => LP_ORDER_CPT,
					'post_status'    => 'any',
					'post_parent'    => $parent_order_id,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'   => LP_Gateway_Abstract::META_SUBSCRIPTION_RENEWAL_KEY,
							'value' => $renewal_key,
						),
					),
				)
			);

			if ( empty( $order_ids ) ) {
				return false;
			}

			return learn_press_get_order( absint( $order_ids[0] ) );
		}*/

		/**
		 * Build stable renewal dedupe key from event payload.
		 *
		 * Priority:
		 * - explicit `renewal_key` provided by gateway mapping.
		 * - fallback `subscription_id|transaction_id` composite.
		 *
		 * @param array $event Normalized event payload.
		 *
		 * @return string Non-empty dedupe key when enough data exists.
		 * @deprecated 4.3.8
		 */
		/*protected function get_renewal_key( array $event ): string {
			$renewal_key = sanitize_text_field( (string) ( $event['renewal_key'] ?? '' ) );
			if ( ! empty( $renewal_key ) ) {
				return $renewal_key;
			}

			$subscription_id = sanitize_text_field( (string) ( $event['subscription_id'] ?? '' ) );
			$transaction_id  = sanitize_text_field( (string) ( $event['transaction_id'] ?? '' ) );
			if ( ! empty( $subscription_id ) && ! empty( $transaction_id ) ) {
				return $subscription_id . '|' . $transaction_id;
			}

			return '';
		}*/

		/**
		 * Clone parent order items/meta into a renewal child order.
		 *
		 * Copies quantity/subtotal/total from the parent item meta so renewal
		 * records remain auditable even when catalog prices change later.
		 *
		 * @param LP_Order $order_subscription
		 * @param LP_Order $order_renew
		 *
		 * @return void
		 * @deprecated 4.3.8
		 */
		/*protected function copy_parent_order_items_to_renewal( LP_Order $order_subscription, LP_Order $order_renew ) {
			$parent_items = $order_subscription->get_items();
			if ( empty( $parent_items ) || ! is_array( $parent_items ) ) {
				return;
			}

			foreach ( $parent_items as $parent_item ) {
				$parent_item_id = absint( is_array( $parent_item ) ? ( $parent_item['id'] ?? 0 ) : ( $parent_item->order_item_id ?? 0 ) );
				$item_id        = absint( is_array( $parent_item ) ? ( $parent_item['item_id'] ?? 0 ) : ( $parent_item->item_id ?? 0 ) );
				$item_type      = is_array( $parent_item ) ? ( $parent_item['item_type'] ?? '' ) : ( $parent_item->item_type ?? '' );
				$item_name      = is_array( $parent_item ) ? ( $parent_item['name'] ?? '' ) : ( $parent_item->order_item_name ?? '' );

				if ( empty( $item_id ) || empty( $item_type ) ) {
					continue;
				}

				$quantity = 1;
				$subtotal = 0;
				$total    = 0;

				if ( $parent_item_id > 0 ) {
					$quantity_meta = learn_press_get_order_item_meta( $parent_item_id, '_quantity', true );
					$subtotal_meta = learn_press_get_order_item_meta( $parent_item_id, '_subtotal', true );
					$total_meta    = learn_press_get_order_item_meta( $parent_item_id, '_total', true );

					if ( '' !== $quantity_meta ) {
						$quantity = (float) $quantity_meta;
					}
					if ( '' !== $subtotal_meta ) {
						$subtotal = (float) $subtotal_meta;
					}
					if ( '' !== $total_meta ) {
						$total = (float) $total_meta;
					}
				}

				$new_item_id = $order_renew->add_item(
					array(
						'item_id'         => $item_id,
						'item_type'       => $item_type,
						'order_item_name' => $item_name ? $item_name : get_the_title( $item_id ),
						'quantity'        => $quantity > 0 ? $quantity : 1,
						'subtotal'        => $subtotal,
						'total'           => $total,
					)
				);

				if ( ! $new_item_id ) {
						continue;
				}
			}
		}*/
	}
}
