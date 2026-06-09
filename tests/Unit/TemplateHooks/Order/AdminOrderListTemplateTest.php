<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Order;

use LearnPress\TemplateHooks\Order\AdminOrderListTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\Test;

class AdminOrderListTemplateTest extends BrainMonkeyTestCase {

	#[Test]
	public function refund_requests_view_is_inserted_before_terminal_order_statuses(): void {
		defined( 'LP_ORDER_CANCELLED_DB' ) || define( 'LP_ORDER_CANCELLED_DB', 'lp-cancelled' );
		defined( 'LP_ORDER_REFUNDED_DB' ) || define( 'LP_ORDER_REFUNDED_DB', 'lp-refunded' );

		$views = array(
			'all'           => 'All',
			'lp-completed'  => 'Completed',
			'lp-processing' => 'Processing',
			'lp-cancelled'  => 'Cancelled',
			'lp-refunded'   => 'Refunded',
		);

		$result = AdminOrderListTemplate::insert_refund_requests_view( $views, 'Refund Requests' );

		$this->assertSame(
			array( 'all', 'lp-completed', 'lp-processing', 'refund-requests', 'lp-cancelled', 'lp-refunded' ),
			array_keys( $result )
		);
	}

	#[Test]
	public function refund_requests_view_is_appended_when_terminal_statuses_are_missing(): void {
		defined( 'LP_ORDER_CANCELLED_DB' ) || define( 'LP_ORDER_CANCELLED_DB', 'lp-cancelled' );
		defined( 'LP_ORDER_REFUNDED_DB' ) || define( 'LP_ORDER_REFUNDED_DB', 'lp-refunded' );

		$result = AdminOrderListTemplate::insert_refund_requests_view(
			array(
				'all'           => 'All',
				'lp-completed'  => 'Completed',
				'lp-processing' => 'Processing',
			),
			'Refund Requests'
		);

		$this->assertSame( array( 'all', 'lp-completed', 'lp-processing', 'refund-requests' ), array_keys( $result ) );
	}
}
