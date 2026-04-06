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

		/**
		 * Get singleton instance of subscription manager.
		 *
		 * @return LP_Subscription_Manager
		 */
		public static function instance(): LP_Subscription_Manager {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
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
		 * @param array $event Normalized payload from gateway::normalize_subscription_event().
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
		 */
		public function process_webhook_event( LP_Gateway_Abstract $gateway, array $event ): array {
			$gateway_id = $gateway->get_id();
			$event_id   = sanitize_text_field( (string) ( $event['event_id'] ?? '' ) );
			$event_type = sanitize_key( (string) ( $event['event_type'] ?? '' ) );

			if ( empty( $event_id ) ) {
				$event_id = md5( wp_json_encode( $event ) );
			}

			$response = array(
				'status'      => 'ignored',
				'event_id'    => $event_id,
				'event_type'  => $event_type,
				'status_code' => 200,
			);

			try {
				$parent_order_id = $this->resolve_parent_order_id( $event );
				$parent_order    = $parent_order_id ? learn_press_get_order( $parent_order_id ) : false;

				if ( $this->is_event_already_handled( $event_type, $event_id, $parent_order, $event ) ) {
					return array(
						'status'      => 'duplicate',
						'event_id'    => $event_id,
						'event_type'  => $event_type,
						'status_code' => 200,
					);
				}

				if ( $parent_order ) {
					$this->sync_subscription_meta( $parent_order->get_id(), $event );
				}

				switch ( $event_type ) {
					case 'subscription_activated':
					case 'initial_payment_succeeded':
						if ( ! $parent_order ) {
							throw new Exception( __( 'Parent subscription order not found.', 'learnpress' ) );
						}

						$this->update_subscription_status( $parent_order->get_id(), 'active', $event_id );
						$this->mark_parent_payment_completed( $parent_order, $event );
						$this->add_order_note( $parent_order, __( 'Subscription activated.', 'learnpress' ) );

						do_action( 'learn-press/subscription/activated', $parent_order->get_id(), $event, $gateway_id );

						$response['status']   = 'success';
						$response['order_id'] = $parent_order->get_id();
						break;
					case 'renewal_payment_succeeded':
						if ( ! $parent_order ) {
							throw new Exception( __( 'Parent subscription order not found.', 'learnpress' ) );
						}

						$this->update_subscription_status( $parent_order->get_id(), 'active', $event_id );
						$renewal_order = $this->create_renewal_order( $parent_order, $event, LP_ORDER_COMPLETED );
						$this->add_order_note( $parent_order, __( 'Subscription renewal payment succeeded.', 'learnpress' ) );
						do_action( 'learn-press/subscription/renewal-order-created', $renewal_order->get_id(), $parent_order->get_id(), $event, $gateway_id );
						do_action( 'learn-press/subscription/renewed', $parent_order->get_id(), $renewal_order->get_id(), $event, $gateway_id );

						$response['status']            = 'success';
						$response['order_id']          = $parent_order->get_id();
						$response['renewal_order_id']  = $renewal_order->get_id();
						break;
					case 'renewal_payment_failed':
						if ( ! $parent_order ) {
							throw new Exception( __( 'Parent subscription order not found.', 'learnpress' ) );
						}

						$this->update_subscription_status( $parent_order->get_id(), 'past_due', $event_id );
						$renewal_failed_order = $this->create_renewal_order( $parent_order, $event, LP_ORDER_FAILED );
						$this->add_order_note( $parent_order, __( 'Subscription renewal payment failed.', 'learnpress' ) );
						do_action( 'learn-press/subscription/renewal-order-created', $renewal_failed_order->get_id(), $parent_order->get_id(), $event, $gateway_id );
						do_action( 'learn-press/subscription/payment-failed', $parent_order->get_id(), $renewal_failed_order->get_id(), $event, $gateway_id );

						$response['status']           = 'failed';
						$response['order_id']         = $parent_order->get_id();
						$response['renewal_order_id'] = $renewal_failed_order->get_id();
						break;
					case 'subscription_cancelled':
						if ( ! $parent_order ) {
							throw new Exception( __( 'Parent subscription order not found.', 'learnpress' ) );
						}

						$this->update_subscription_status( $parent_order->get_id(), 'cancelled', $event_id );
						$this->add_order_note( $parent_order, __( 'Subscription cancelled.', 'learnpress' ) );
						do_action( 'learn-press/subscription/cancelled', $parent_order->get_id(), $event, $gateway_id );

						$response['status']   = 'cancelled';
						$response['order_id'] = $parent_order->get_id();
						break;
					case 'subscription_expired':
						if ( ! $parent_order ) {
							throw new Exception( __( 'Parent subscription order not found.', 'learnpress' ) );
						}

						$this->update_subscription_status( $parent_order->get_id(), 'expired', $event_id );
						$this->add_order_note( $parent_order, __( 'Subscription expired.', 'learnpress' ) );
						do_action( 'learn-press/subscription/expired', $parent_order->get_id(), $event, $gateway_id );

						$response['status']   = 'expired';
						$response['order_id'] = $parent_order->get_id();
						break;
					case 'subscription_updated':
						if ( $parent_order ) {
							$status = ! empty( $event['status'] ) ? sanitize_key( (string) $event['status'] ) : '';
							if ( ! empty( $status ) ) {
								$this->update_subscription_status( $parent_order->get_id(), $status, $event_id );
							}
							$this->add_order_note( $parent_order, __( 'Subscription updated.', 'learnpress' ) );
							$response['order_id'] = $parent_order->get_id();
						}
						$response['status'] = 'updated';
						break;
					default:
						$response['status'] = 'ignored';
						break;
				}

			} catch ( Throwable $e ) {
				$response['status']      = 'error';
				$response['status_code'] = 400;
				$response['message']     = $e->getMessage();
				error_log( 'LP_Subscription_Manager: ' . $e->getMessage() );
			}

			return $response;
		}

		/**
		 * Check whether the webhook event outcome is already reflected in order state.
		 *
		 * Replaces the old LP_Subscription_Event_Store transient-based approach with
		 * order-state checks: status, transaction_id, and event_id meta.
		 *
		 * @since 4.3.5
		 *
		 * @param string        $event_type   Normalized event type.
		 * @param string        $event_id     Provider event ID.
		 * @param LP_Order|false $parent_order Resolved parent order (false when not found).
		 * @param array         $event        Full normalized event payload.
		 *
		 * @return bool True if the event outcome is already reflected in the database.
		 */
		private function is_event_already_handled( string $event_type, string $event_id, $parent_order, array $event ): bool {
			if ( ! $parent_order instanceof LP_Order ) {
				return false;
			}

			$order_id = $parent_order->get_id();

			switch ( $event_type ) {
				case 'subscription_activated':
				case 'initial_payment_succeeded':
					$sub_status = get_post_meta( $order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS, true );
					return in_array( $sub_status, array( 'active', 'trialing' ), true )
						&& $parent_order->is_completed();

				case 'renewal_payment_succeeded':
				case 'renewal_payment_failed':
					$renewal_key = $this->get_renewal_key( $event );
					if ( ! empty( $renewal_key ) && $this->find_renewal_order_by_key( $order_id, $renewal_key ) ) {
						return true;
					}
					if ( ! empty( $event_id ) && $this->find_renewal_order_by_event( $order_id, $event_id ) ) {
						return true;
					}
					return false;

				case 'subscription_cancelled':
					$current = get_post_meta( $order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS, true );
					return 'cancelled' === $current;

				case 'subscription_expired':
					$current = get_post_meta( $order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS, true );
					return 'expired' === $current;

				case 'subscription_updated':
					$last_event = get_post_meta( $order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_LAST_EVENT_ID, true );
					return ! empty( $event_id ) && $last_event === $event_id;

				default:
					return false;
			}
		}

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
		 */
		public function resolve_parent_order_id( array $event ): int {
			$parent_order_id = absint( $event['parent_order_id'] ?? 0 );
			if ( $parent_order_id ) {
				return $parent_order_id;
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
		}

		/**
		 * Persist shared subscription meta onto a parent order.
		 *
		 * This keeps gateway/provider identifiers available for future lookups
		 * and reconciliation.
		 *
		 * @param int $order_id
		 * @param array $event Normalized event payload.
		 *
		 * @return void
		 */
		public function sync_subscription_meta( int $order_id, array $event ) {
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
		}

		/**
		 * Update current subscription status stored on parent order.
		 *
		 * @param int $order_id
		 * @param string $status Normalized status slug (active/past_due/cancelled/...).
		 * @param string $event_id Optional provider event id used as last-processed marker.
		 *
		 * @return void
		 */
		public function update_subscription_status( int $order_id, string $status, string $event_id = '' ) {
			update_post_meta( $order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS, sanitize_key( $status ) );
			if ( ! empty( $event_id ) ) {
				update_post_meta( $order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_LAST_EVENT_ID, sanitize_text_field( $event_id ) );
			}
		}

		/**
		 * Mark parent order paid when initial subscription activation succeeds.
		 *
		 * @param LP_Order $parent_order
		 * @param array $event Normalized event payload.
		 *
		 * @return void
		 */
		protected function mark_parent_payment_completed( LP_Order $parent_order, array $event ) {
			$transaction_id = sanitize_text_field( (string) ( $event['transaction_id'] ?? '' ) );
			if ( ! $parent_order->is_completed() ) {
				$parent_order->payment_complete( $transaction_id );
			}
		}

		/**
		 * Create or reuse a renewal child order for a renewal event.
		 *
		 * Idempotency strategy:
		 * - First by renewal_key (strongest provider-derived key).
		 * - Fallback by event_id.
		 *
		 * @param LP_Order $parent_order
		 * @param array $event Normalized event payload.
		 * @param string $target_status Target order status for renewal result.
		 *
		 * @return LP_Order Existing or newly-created renewal order.
		 * @throws Exception
		 */
		public function create_renewal_order( LP_Order $parent_order, array $event, string $target_status = LP_ORDER_PENDING ): LP_Order {
			$renewal_key = $this->get_renewal_key( $event );
			if ( ! empty( $renewal_key ) ) {
				$existing = $this->find_renewal_order_by_key( $parent_order->get_id(), $renewal_key );
				if ( $existing ) {
					return $existing;
				}
			}

			$event_id = sanitize_text_field( (string) ( $event['event_id'] ?? '' ) );
			if ( ! empty( $event_id ) ) {
				$existing = $this->find_renewal_order_by_event( $parent_order->get_id(), $event_id );
				if ( $existing ) {
					return $existing;
				}
			}

			$renewal_order = new LP_Order();
			$renewal_order->set_parent_id( $parent_order->get_id() );
			$renewal_order->set_user_id( $parent_order->get_user_id() );
			$renewal_order->set_checkout_email( $parent_order->get_checkout_email() );
			$renewal_order->set_status( LP_ORDER_PENDING );
			$renewal_order->set_created_via( 'subscription' );
			$renewal_order->set_currency( $parent_order->get_currency() );
			$renewal_order->set_total( $parent_order->get_total() );
			$renewal_order->set_subtotal( $parent_order->get_subtotal() );
			$renewal_order->set_data( 'payment_method', $parent_order->get_data( 'payment_method', '' ) );
			$renewal_order->set_data( 'payment_method_title', $parent_order->get_payment_method_title() );

			$renewal_order_id = $renewal_order->save();
			if ( empty( $renewal_order_id ) ) {
				throw new Exception( __( 'Cannot create renewal order.', 'learnpress' ) );
			}

			$this->copy_parent_order_items_to_renewal( $parent_order, $renewal_order );

			$renewal_amount = isset( $event['amount'] ) ? (float) $event['amount'] : 0;
			if ( $renewal_amount > 0 ) {
				$renewal_order->set_total( $renewal_amount );
				$renewal_order->set_subtotal( $renewal_amount );
				$renewal_order->save();
			}

			if ( ! empty( $event['currency'] ) ) {
				$renewal_order->set_currency( sanitize_text_field( (string) $event['currency'] ) );
				$renewal_order->save();
			}

			update_post_meta( $renewal_order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_PARENT_ORDER_ID, $parent_order->get_id() );
			if ( ! empty( $event_id ) ) {
				update_post_meta( $renewal_order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_EVENT_ID, $event_id );
			}

			if ( ! empty( $event['subscription_id'] ) ) {
				update_post_meta( $renewal_order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_ID, sanitize_text_field( (string) $event['subscription_id'] ) );
			}

			if ( ! empty( $event['transaction_id'] ) ) {
				update_post_meta( $renewal_order_id, '_transaction_id', sanitize_text_field( (string) $event['transaction_id'] ) );
			}
			if ( ! empty( $renewal_key ) ) {
				update_post_meta( $renewal_order_id, LP_Gateway_Abstract::META_SUBSCRIPTION_RENEWAL_KEY, $renewal_key );
			}

			if ( LP_ORDER_COMPLETED === $target_status ) {
				$renewal_order->payment_complete( (string) ( $event['transaction_id'] ?? '' ) );
			} else {
				$renewal_order->update_status( $target_status );
			}

			update_post_meta( $parent_order->get_id(), LP_Gateway_Abstract::META_SUBSCRIPTION_RENEWAL_ORDER_ID, $renewal_order_id );

			$this->add_order_note(
				$renewal_order,
				sprintf(
					/* translators: %s: parent order number */
					__( 'Subscription renewal generated from parent order %s.', 'learnpress' ),
					$parent_order->get_order_number()
				)
			);

			return $renewal_order;
		}

		/**
		 * Find renewal child order by parent id + provider event id.
		 *
		 * @param int $parent_order_id
		 * @param string $event_id
		 *
		 * @return LP_Order|false
		 */
		protected function find_renewal_order_by_event( int $parent_order_id, string $event_id ) {
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
		}

		/**
		 * Find renewal child order by parent id + renewal key.
		 *
		 * @param int $parent_order_id
		 * @param string $renewal_key
		 *
		 * @return LP_Order|false
		 */
		protected function find_renewal_order_by_key( int $parent_order_id, string $renewal_key ) {
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
		}

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
		 */
		protected function get_renewal_key( array $event ): string {
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
		}

		/**
		 * Clone parent order items/meta into a renewal child order.
		 *
		 * Copies quantity/subtotal/total from the parent item meta so renewal
		 * records remain auditable even when catalog prices change later.
		 *
		 * @param LP_Order $parent_order
		 * @param LP_Order $renewal_order
		 *
		 * @return void
		 */
		protected function copy_parent_order_items_to_renewal( LP_Order $parent_order, LP_Order $renewal_order ) {
			$parent_items = $parent_order->get_items();
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

				$new_item_id = $renewal_order->add_item(
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

				learn_press_update_order_item_meta( $new_item_id, '_quantity', $quantity > 0 ? $quantity : 1 );
				learn_press_update_order_item_meta( $new_item_id, '_subtotal', $subtotal );
				learn_press_update_order_item_meta( $new_item_id, '_total', $total );
			}
		}

		/**
		 * Append order note without interrupting webhook flow on failures.
		 *
		 * @param LP_Order $order
		 * @param string $note
		 *
		 * @return void
		 */
		protected function add_order_note( LP_Order $order, string $note ) {
			try {
				if ( ! empty( $note ) ) {
					$order->add_note( $note );
				}
			} catch ( Throwable $e ) {
				error_log( 'LP_Subscription_Manager note: ' . $e->getMessage() );
			}
		}
	}
}
