<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Gateways\Paypal;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use ReflectionClass;

/**
 * Tests for LP_Gateway_Paypal::normalize_subscription_event().
 *
 * This method is a pure data transformation: PayPal webhook payload → LP event array.
 * No HTTP calls, no DB — only sanitize_text_field and apply_filters.
 *
 * Test params mirror real PayPal webhook structures.
 */
class NormalizeSubscriptionEventTest extends BrainMonkeyTestCase {

	// -------------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------------

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if ( ! class_exists( 'LP_Abstract_Settings' ) ) {
			eval( 'abstract class LP_Abstract_Settings {}' );
		}
		if ( ! class_exists( 'LP_Gateway_Abstract' ) ) {
			require LP_INC_PATH . 'gateways/class-lp-gateway-abstract.php';
		}
		if ( ! class_exists( 'LP_Gateway_Paypal' ) ) {
			// LP_Gateway_Paypal uses Singleton — define LP_Gateways stub first if needed.
			if ( ! class_exists( 'LP_Gateways' ) ) {
				eval( 'class LP_Gateways { public static function instance() { return new self(); } }' );
			}
			require LP_INC_PATH . 'gateways/paypal/class-lp-gateway-paypal.php';
		}
	}

	protected function setUp(): void {
		parent::setUp();

		// apply_filters: just return first arg (the $event array).
		// apply_filters($tag, $value, ...) — return $value (arg index 1).
		Functions\when( 'apply_filters' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'absint' )->alias( 'intval' );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Create a LP_Gateway_Paypal instance bypassing the Singleton constructor.
	 */
	private function make_gateway(): \LP_Gateway_Paypal {
		return ( new ReflectionClass( \LP_Gateway_Paypal::class ) )
			->newInstanceWithoutConstructor();
	}

	/**
	 * Build a minimal PayPal BILLING.SUBSCRIPTION.* payload.
	 */
	private function billing_payload( string $event_type, string $subscription_id, string $status = 'ACTIVE', ?string $custom_id = null ): array {
		$resource = array(
			'id'     => $subscription_id,
			'status' => $status,
		);
		if ( null !== $custom_id ) {
			$resource['custom_id'] = $custom_id;
		}

		return array(
			'id'         => 'WH-UNIT-001',
			'event_type' => $event_type,
			'resource'   => $resource,
		);
	}

	/**
	 * Build a PAYMENT.SALE.* payload.
	 */
	private function payment_sale_payload( string $event_type, string $sale_id, string $billing_agreement_id, float $total = 19.99, string $currency = 'USD' ): array {
		return array(
			'id'         => 'WH-UNIT-002',
			'event_type' => $event_type,
			'resource'   => array(
				'id'                   => $sale_id,
				'billing_agreement_id' => $billing_agreement_id,
				'amount'               => array(
					'total'    => (string) $total,
					'currency' => $currency,
				),
				'state' => 'completed',
			),
		);
	}

	// -------------------------------------------------------------------------
	// BILLING.SUBSCRIPTION.ACTIVATED
	// -------------------------------------------------------------------------

	public function test_activated_maps_to_subscription_activated(): void {
		$gateway = $this->make_gateway();
		$payload = $this->billing_payload( 'BILLING.SUBSCRIPTION.ACTIVATED', 'I-SUBACT001' );

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 'subscription_activated', $event['event_type'] );
		$this->assertSame( 'I-SUBACT001', $event['subscription_id'] );
		$this->assertSame( 'active', $event['status'] );
		$this->assertSame( 'WH-UNIT-001', $event['event_id'] );
	}

	public function test_activated_with_custom_id_sets_parent_order_id(): void {
		$gateway = $this->make_gateway();
		$payload = $this->billing_payload( 'BILLING.SUBSCRIPTION.ACTIVATED', 'I-SUBACT002', 'ACTIVE', '42' );

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 42, $event['parent_order_id'] );
		$this->assertSame( '42', $event['metadata']['lp_order_id'] );
	}

	// -------------------------------------------------------------------------
	// BILLING.SUBSCRIPTION.UPDATED
	// -------------------------------------------------------------------------

	public function test_updated_maps_to_subscription_updated(): void {
		$gateway = $this->make_gateway();
		$payload = $this->billing_payload( 'BILLING.SUBSCRIPTION.UPDATED', 'I-SUBUPD001', 'ACTIVE' );

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 'subscription_updated', $event['event_type'] );
		$this->assertSame( 'I-SUBUPD001', $event['subscription_id'] );
		$this->assertSame( 'ACTIVE', $event['status'] );
	}

	// -------------------------------------------------------------------------
	// BILLING.SUBSCRIPTION.CANCELLED / SUSPENDED
	// -------------------------------------------------------------------------

	public function test_cancelled_maps_to_subscription_cancelled(): void {
		$gateway = $this->make_gateway();
		$payload = $this->billing_payload( 'BILLING.SUBSCRIPTION.CANCELLED', 'I-SUBCAN001' );

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 'subscription_cancelled', $event['event_type'] );
		$this->assertSame( 'cancelled', $event['status'] );
	}

	public function test_suspended_maps_to_subscription_cancelled(): void {
		$gateway = $this->make_gateway();
		$payload = $this->billing_payload( 'BILLING.SUBSCRIPTION.SUSPENDED', 'I-SUBSUS001' );

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 'subscription_cancelled', $event['event_type'] );
		$this->assertSame( 'cancelled', $event['status'] );
	}

	// -------------------------------------------------------------------------
	// BILLING.SUBSCRIPTION.EXPIRED
	// -------------------------------------------------------------------------

	public function test_expired_maps_to_subscription_expired(): void {
		$gateway = $this->make_gateway();
		$payload = $this->billing_payload( 'BILLING.SUBSCRIPTION.EXPIRED', 'I-SUBEXP001' );

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 'subscription_expired', $event['event_type'] );
		$this->assertSame( 'expired', $event['status'] );
	}

	// -------------------------------------------------------------------------
	// PAYMENT.SALE.COMPLETED  (renewal success — legacy API)
	// -------------------------------------------------------------------------

	public function test_payment_sale_completed_uses_billing_agreement_id_as_subscription_id(): void {
		$gateway = $this->make_gateway();
		$payload = $this->payment_sale_payload( 'PAYMENT.SALE.COMPLETED', 'SALE-001', 'I-SUB-RENEWAL' );

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 'renewal_payment_succeeded', $event['event_type'] );
		$this->assertSame( 'I-SUB-RENEWAL', $event['subscription_id'] );
		$this->assertSame( 'SALE-001', $event['transaction_id'] );
	}

	public function test_payment_sale_completed_extracts_amount_and_currency(): void {
		$gateway = $this->make_gateway();
		$payload = $this->payment_sale_payload( 'PAYMENT.SALE.COMPLETED', 'SALE-002', 'I-SUB-002', 29.99, 'EUR' );

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertEqualsWithDelta( 29.99, $event['amount'], 0.001 );
		$this->assertSame( 'EUR', $event['currency'] );
	}

	public function test_payment_sale_completed_sets_renewal_key(): void {
		$gateway = $this->make_gateway();
		$payload = $this->payment_sale_payload( 'PAYMENT.SALE.COMPLETED', 'SALE-003', 'I-SUB-003' );

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 'paypal_sale_SALE-003', $event['renewal_key'] );
	}

	// -------------------------------------------------------------------------
	// BILLING.SUBSCRIPTION.PAYMENT.COMPLETED  (renewal success — v3 API)
	// -------------------------------------------------------------------------

	public function test_billing_subscription_payment_completed_maps_to_renewal_succeeded(): void {
		$gateway = $this->make_gateway();
		$payload = array(
			'id'         => 'WH-UNIT-003',
			'event_type' => 'BILLING.SUBSCRIPTION.PAYMENT.COMPLETED',
			'resource'   => array(
				'id'                   => 'SALE-V3-001',
				'billing_agreement_id' => 'I-SUB-V3',
				'amount'               => array(
					'total'    => '9.99',
					'currency' => 'USD',
				),
			),
		);

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 'renewal_payment_succeeded', $event['event_type'] );
		$this->assertSame( 'I-SUB-V3', $event['subscription_id'] );
	}

	// -------------------------------------------------------------------------
	// PAYMENT.SALE.DENIED  (renewal failure)
	// -------------------------------------------------------------------------

	public function test_payment_sale_denied_maps_to_renewal_failed(): void {
		$gateway = $this->make_gateway();
		$payload = array(
			'id'         => 'WH-UNIT-004',
			'event_type' => 'PAYMENT.SALE.DENIED',
			'resource'   => array(
				'id'                   => 'SALE-FAIL-001',
				'billing_agreement_id' => 'I-SUB-FAIL',
			),
		);

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 'renewal_payment_failed', $event['event_type'] );
		$this->assertSame( 'I-SUB-FAIL', $event['subscription_id'] );
		$this->assertSame( 'SALE-FAIL-001', $event['transaction_id'] );
		$this->assertSame( 'paypal_sale_SALE-FAIL-001', $event['renewal_key'] );
	}

	// -------------------------------------------------------------------------
	// BILLING.SUBSCRIPTION.PAYMENT.FAILED
	// -------------------------------------------------------------------------

	public function test_billing_subscription_payment_failed_maps_to_renewal_failed(): void {
		$gateway = $this->make_gateway();
		$payload = array(
			'id'         => 'WH-UNIT-005',
			'event_type' => 'BILLING.SUBSCRIPTION.PAYMENT.FAILED',
			'resource'   => array(
				'billing_agreement_id' => 'I-SUB-FAIL2',
				'id'                   => 'SALE-FAIL-002',
			),
		);

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 'renewal_payment_failed', $event['event_type'] );
		$this->assertSame( 'I-SUB-FAIL2', $event['subscription_id'] );
	}

	// -------------------------------------------------------------------------
	// Unknown event type → ignored
	// -------------------------------------------------------------------------

	public function test_unknown_event_type_returns_ignored(): void {
		$gateway = $this->make_gateway();
		$payload = array(
			'id'         => 'WH-UNIT-006',
			'event_type' => 'CUSTOMER.DISPUTE.CREATED',  // not handled
			'resource'   => array(),
		);

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( 'ignored', $event['event_type'] );
	}

	// -------------------------------------------------------------------------
	// Base fields always present
	// -------------------------------------------------------------------------

	public function test_base_fields_are_always_returned(): void {
		$gateway = $this->make_gateway();
		$payload = $this->billing_payload( 'BILLING.SUBSCRIPTION.ACTIVATED', 'I-SUB-FIELDS' );

		$event = $gateway->normalize_subscription_event( $payload );

		$required_fields = array( 'event_id', 'event_type', 'subscription_id', 'customer_id', 'price_id', 'parent_order_id', 'amount', 'currency', 'metadata' );
		foreach ( $required_fields as $field ) {
			$this->assertArrayHasKey( $field, $event, "Missing field: $field" );
		}
	}

	// -------------------------------------------------------------------------
	// Edge: missing resource keys default gracefully
	// -------------------------------------------------------------------------

	public function test_missing_resource_fields_default_to_empty(): void {
		$gateway = $this->make_gateway();
		$payload = array(
			'id'         => 'WH-UNIT-007',
			'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
			'resource'   => array(), // intentionally empty
		);

		$event = $gateway->normalize_subscription_event( $payload );

		$this->assertSame( '', $event['subscription_id'] );
		$this->assertSame( 0, $event['parent_order_id'] );
	}
}