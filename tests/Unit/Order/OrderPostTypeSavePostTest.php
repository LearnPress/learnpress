<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Order;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * LP_Order_Post_Type::save_post() only handles saving from the edit screen on Backend.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class OrderPostTypeSavePostTest extends BrainMonkeyTestCase {

	private bool $is_admin = false;

	private function load_post_type_with_stubs(): \LP_Order_Post_Type {
		defined( 'LP_ORDER_CPT' ) || define( 'LP_ORDER_CPT', 'lp_order' );
		defined( 'LP_ORDER_CREATED_VIA_MANUAL' ) || define( 'LP_ORDER_CREATED_VIA_MANUAL', 'manual' );

		eval(
			'class WP_Post {
				public $ID = 0;
				public $post_status = "";
			}
			class LP_Abstract_Post_Type {
				public function __construct() {}
			}
			class LP_Request {
				public static function get_param( string $key, $default_value = "", string $sanitize_type = "text", string $method = "" ) {
					$value = $_POST[ $key ] ?? $default_value;
					return "int" === $sanitize_type ? (int) $value : $value;
				}
			}
			class LP_Order {
				public static $saving = false;
				public static function is_saving( int $order_id ): bool {
					return self::$saving;
				}
			}'
		);

		Functions\when( 'is_admin' )->alias( fn() => $this->is_admin );
		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_verify_nonce' )->alias(
			static fn( $nonce, $action ) => 'valid:' . $action === $nonce ? 1 : false
		);

		$_GET  = array();
		$_POST = array();

		require_once dirname( __DIR__, 3 ) . '/inc/custom-post-types/order.php';

		return \LP_Order_Post_Type::instance();
	}

	private function make_post( int $id, string $status ): \WP_Post {
		$post              = new \WP_Post();
		$post->ID          = $id;
		$post->post_status = $status;

		return $post;
	}

	public function test_save_post_skips_when_order_is_saving_by_lp_order(): void {
		$post_type         = $this->load_post_type_with_stubs();
		$this->is_admin    = true;
		\LP_Order::$saving = true;
		$_POST             = array(
			'post_ID'  => 7,
			'_wpnonce' => 'valid:update-post_7',
		);

		Functions\expect( 'learn_press_get_order' )->never();

		$post_type->save_post( 7, $this->make_post( 7, 'lp-pending' ), true );
	}

	public function test_save_post_skips_save_from_code_outside_admin(): void {
		$post_type = $this->load_post_type_with_stubs();
		$_POST     = array(
			'post_ID'  => 7,
			'_wpnonce' => 'valid:update-post_7',
		);

		Functions\expect( 'learn_press_get_order' )->never();

		$post_type->save_post( 7, $this->make_post( 7, 'draft' ), false );
	}

	public function test_save_post_skips_when_form_is_of_another_post(): void {
		$post_type      = $this->load_post_type_with_stubs();
		$this->is_admin = true;
		// Ex: admin saves a WooCommerce order, an LP Order is inserted by code in the same request.
		$_POST = array(
			'post_ID'  => 3,
			'_wpnonce' => 'valid:update-post_3',
		);

		Functions\expect( 'learn_press_get_order' )->never();

		$post_type->save_post( 7, $this->make_post( 7, 'draft' ), false );
	}

	public function test_save_post_skips_when_nonce_invalid(): void {
		$post_type      = $this->load_post_type_with_stubs();
		$this->is_admin = true;
		$_POST          = array(
			'post_ID'  => 7,
			'_wpnonce' => 'wrong',
		);

		Functions\expect( 'learn_press_get_order' )->never();

		$post_type->save_post( 7, $this->make_post( 7, 'lp-pending' ), true );
	}

	public function test_save_post_handles_form_edit_order_on_backend(): void {
		$post_type      = $this->load_post_type_with_stubs();
		$this->is_admin = true;
		$_POST          = array(
			'post_ID'  => 7,
			'_wpnonce' => 'valid:update-post_7',
		);

		Functions\expect( 'learn_press_get_order' )->once()->with( 7 )->andReturn( false );

		$post_type->save_post( 7, $this->make_post( 7, 'lp-pending' ), true );
	}

	public function test_save_post_handles_auto_draft_of_screen_add_new_order(): void {
		$post_type      = $this->load_post_type_with_stubs();
		$this->is_admin = true;

		Functions\expect( 'learn_press_get_order' )->once()->with( 7 )->andReturn( false );

		$post_type->save_post( 7, $this->make_post( 7, 'auto-draft' ), false );
	}
}
