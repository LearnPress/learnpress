<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Order;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;

class AdminOrderListFilterTest extends BrainMonkeyTestCase {

	private function load_order_with_stubs(): void {
		defined( 'LP_ORDER_CPT' ) || define( 'LP_ORDER_CPT', 'lp_order' );
		defined( 'LP_ORDER_TRASH' ) || define( 'LP_ORDER_TRASH', 'trash' );

		if ( ! class_exists( 'LP_Abstract_Post_Data', false ) ) {
			eval( 'class LP_Abstract_Post_Data {}' );
		}

		if ( ! class_exists( '\LearnPress\Filters\PostFilter', false ) ) {
			eval(
				'namespace LearnPress\Filters;
				class PostFilter {
					public array $where = array();
					public array $join = array();
					public array $post_status = array();
					public string $order_by = "";
					public string $order = "";
					public int $limit = 20;
					public int $page = 1;
				}'
			);
		}

		if ( ! class_exists( '\LearnPress\Databases\PostDB', false ) ) {
			eval(
				'namespace LearnPress\Databases;
				class PostDB {
					public string $tb_postmeta = "wp_postmeta";
					public $wpdb;
					public static $instance;
					public function __construct() {
						$this->wpdb = new class() {
							public function prepare( string $query, ...$args ): string {
								return $query . " [" . implode( ",", $args ) . "]";
							}
						};
					}
					public static function getInstance(): self {
						return self::$instance ??= new self();
					}
				}'
			);
		}

		Functions\when( 'absint' )->alias(
			static fn( $value ) => abs( (int) $value )
		);
		Functions\when( 'sanitize_key' )->alias(
			static fn( $value ) => strtolower( preg_replace( '/[^a-z0-9_-]/i', '', (string) $value ) )
		);

		if ( ! class_exists( 'LP_Order', false ) ) {
			require_once dirname( __DIR__, 3 ) . '/inc/order/class-lp-order.php';
		}
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function refund_request_status_adds_prepared_exists_condition(): void {
		$this->load_order_with_stubs();

		$filter = new \LearnPress\Filters\PostFilter();
		\LP_Order::handle_params_query_list_orders(
			$filter,
			array(
				'post_status'          => 'all',
				'refund_request_status' => 'PENDING<script>',
			)
		);

		$where = implode( ' ', $filter->where );

		$this->assertStringContainsString( 'EXISTS', $where );
		$this->assertStringContainsString( '_lp_refund_request', $where );
		$this->assertStringContainsString( 'pendingscript', $where );
	}
}
