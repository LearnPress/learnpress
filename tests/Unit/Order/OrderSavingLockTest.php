<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Order;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use RuntimeException;

/**
 * Lock of LP_Order::save() used to stop infinite loops of save_post.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class OrderSavingLockTest extends BrainMonkeyTestCase {

	private function load_order_with_stubs(): void {
		defined( 'LP_ORDER_CPT' ) || define( 'LP_ORDER_CPT', 'lp_order' );

		if ( ! class_exists( 'LP_Abstract_Post_Data', false ) ) {
			eval(
				'class LP_Abstract_Post_Data {
					protected $_id = 0;
					protected $_curd = null;
					public function get_id() {
						return $this->_id;
					}
					public function set_id( $id ) {
						$this->_id = $id;
					}
				}'
			);
		}

		if ( ! class_exists( 'LP_Order_CURD', false ) ) {
			eval(
				'class LP_Order_CURD {
					public static $on_update = null;
					public static $on_create = null;
					public function update( &$order ) {
						if ( self::$on_update ) {
							( self::$on_update )( $order );
						}
						return $order->get_id();
					}
					public function create( &$order ) {
						if ( self::$on_create ) {
							( self::$on_create )( $order );
						}
						return 99;
					}
				}'
			);
		}

		Functions\when( 'get_post_status' )->justReturn( 'lp-pending' );

		if ( ! class_exists( 'LP_Order', false ) ) {
			require_once dirname( __DIR__, 3 ) . '/inc/order/class-lp-order.php';
		}
	}

	private function make_order( int $id ): \LP_Order {
		$order = new \LP_Order();
		$order->set_id( $id );

		return $order;
	}

	public function test_save_marks_order_is_saving_only_while_updating(): void {
		$this->load_order_with_stubs();

		$seen                     = array();
		\LP_Order_CURD::$on_update = static function () use ( &$seen ) {
			$seen = array(
				'depth'     => \LP_Order::get_saving_depth( 5 ),
				'saving'    => \LP_Order::is_saving( 5 ),
				'other'     => \LP_Order::is_saving( 6 ),
				'other_num' => \LP_Order::get_saving_depth( 6 ),
			);
		};

		$this->assertFalse( \LP_Order::is_saving( 5 ) );
		$this->make_order( 5 )->save();

		$this->assertSame( 1, $seen['depth'] );
		$this->assertTrue( $seen['saving'] );
		$this->assertFalse( $seen['other'] );
		$this->assertSame( 0, $seen['other_num'] );
		$this->assertFalse( \LP_Order::is_saving( 5 ) );
		$this->assertSame( 0, \LP_Order::get_saving_depth( 5 ) );
	}

	public function test_save_marks_any_order_is_saving_while_creating(): void {
		$this->load_order_with_stubs();

		$saving_new_id             = null;
		\LP_Order_CURD::$on_create = static function () use ( &$saving_new_id ) {
			// New id is unknown until the post inserted, so every id is locked.
			$saving_new_id = \LP_Order::is_saving( 99 );
		};

		$order = new \LP_Order();
		$order->save();

		$this->assertTrue( $saving_new_id );
		$this->assertSame( 99, $order->get_id() );
		$this->assertFalse( \LP_Order::is_saving( 99 ) );
	}

	public function test_save_counts_depth_when_called_nested(): void {
		$this->load_order_with_stubs();

		$depths                    = array();
		\LP_Order_CURD::$on_update = function () use ( &$depths ) {
			$depths[] = \LP_Order::get_saving_depth( 5 );
			if ( count( $depths ) < 2 ) {
				// A hook of wp_update_post saves the order again.
				$this->make_order( 5 )->save();
			}
		};

		$this->make_order( 5 )->save();

		$this->assertSame( array( 1, 2 ), $depths );
		$this->assertSame( 0, \LP_Order::get_saving_depth( 5 ) );
	}

	public function test_save_releases_lock_when_curd_throws(): void {
		$this->load_order_with_stubs();

		\LP_Order_CURD::$on_update = static function () {
			throw new RuntimeException( 'DB error' );
		};

		try {
			$this->make_order( 5 )->save();
			$this->fail( 'Exception expected' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'DB error', $e->getMessage() );
		}

		$this->assertFalse( \LP_Order::is_saving( 5 ) );
	}
}
