<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Statistics;

use LearnPress\Statistics\OrderExceptionsProvider;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

// Parent class of OrderExceptionsProvider (legacy, not PSR-4 autoloadable).
require_once dirname( __DIR__, 3 ) . '/inc/Databases/class-lp-db.php';

/**
 * Pure helpers of OrderExceptionsProvider. Query behavior is verified in a
 * WordPress runtime with seeded orders.
 *
 * @covers \LearnPress\Statistics\OrderExceptionsProvider
 */
class OrderExceptionsProviderTest extends BrainMonkeyTestCase {

	public function test_issue_text_uses_payment_method_and_status(): void {
		$this->assertSame(
			'PayPal - failed',
			OrderExceptionsProvider::issue_text( 'PayPal', 'failed' )
		);
	}

	public function test_issue_text_falls_back_when_payment_method_missing(): void {
		$this->assertSame(
			'Unknown payment method - cancelled',
			OrderExceptionsProvider::issue_text( '', 'cancelled' )
		);
	}

	public function test_severity_failed_paid_is_high(): void {
		$this->assertSame( 'high', OrderExceptionsProvider::severity( 'failed', 10.0 ) );
	}

	public function test_severity_cancelled_paid_is_medium(): void {
		$this->assertSame( 'medium', OrderExceptionsProvider::severity( 'cancelled', 10.0 ) );
	}

	public function test_severity_free_order_is_low(): void {
		$this->assertSame( 'low', OrderExceptionsProvider::severity( 'failed', 0.0 ) );
	}
}
