<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Order;

use Brain\Monkey\Functions;
use LearnPress\Ajax\RefundOrderAjax;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

/**
 * Unit tests for refund policy and calculation guards.
 */
class RefundPolicyTest extends BrainMonkeyTestCase {

	/**
	 * Boot minimal dependencies and refund functions once.
	 */
	private function boot_refund_dependencies(): void {
		if ( ! class_exists( 'LP_Settings', false ) ) {
			eval(
				'class LP_Settings {
					public static array $options = [];
					public static function get_option( $key, $default = null ) {
						return array_key_exists( $key, self::$options ) ? self::$options[ $key ] : $default;
					}
				}'
			);
		}

		if ( ! class_exists( 'LP_User_Course_Data', false ) ) {
			eval(
				'class LP_User_Course_Data {
					private float $progress;
					public function __construct( float $progress ) {
						$this->progress = $progress;
					}
					public function get_results( $prop = "result" ) {
						return $this->progress;
					}
				}'
			);
		}

		if ( ! class_exists( 'LP_User', false ) ) {
			eval(
				'class LP_User {
					private int $id = 0;
					public array $progress_map = [];
					public function __construct( array $progress_map = [], int $id = 0 ) {
						$this->progress_map = $progress_map;
						$this->id = $id;
					}
					public function get_course_data( int $course_id ) {
						if ( ! array_key_exists( $course_id, $this->progress_map ) ) {
							return null;
						}
						return new LP_User_Course_Data( (float) $this->progress_map[ $course_id ] );
					}
					public function get_id(): int {
						return $this->id;
					}
				}'
			);
		}

		if ( ! class_exists( 'LP_Order', false ) ) {
			eval(
				'class LP_Order {
					public int $id;
					public string $status = "completed";
					public bool $guest = false;
					public string $payment_method = "stripe";
					public array $user_ids = [1];
					public int $order_time = 0;
					public array $course_ids = [101];
					public float $total = 100.00;
					public array $notes = [];
					public string $currency = "USD";
					public string $order_key = "ORDERKEY";
					public function __construct( int $id = 1 ) {
						$this->id = $id;
					}
					public function get_id() {
						return $this->id;
					}
					public function get_item_ids() {
						return $this->course_ids;
					}
					public function has_status( $status ) {
						if ( is_array( $status ) ) {
							return in_array( $this->status, $status, true );
						}
						return $this->status === $status;
					}
					public function get_data( $key, $default = "" ) {
						if ( $key === "payment_method" ) {
							return $this->payment_method;
						}
						return $default;
					}
					public function is_guest() {
						return $this->guest;
					}
					public function get_user_id() {
						return $this->user_ids;
					}
					public function get_order_date( $format = "" ) {
						if ( $format === "timestamp" ) {
							return $this->order_time;
						}
						return "";
					}
					public function get_total() {
						return $this->total;
					}
					public function get_status() {
						return $this->status;
					}
					public function update_status( $status ) {
						$this->status = (string) $status;
						return true;
					}
					public function add_note( $note ) {
						$this->notes[] = (string) $note;
					}
					public function get_order_number() {
						return (string) $this->id;
					}
					public function get_order_key() {
						return $this->order_key;
					}
					public function get_currency() {
						return $this->currency;
					}
				}'
			);
		}

		if ( ! class_exists( '\LearnPress\Models\UserItems\UserCourseModel', false ) ) {
			eval(
				'namespace LearnPress\Models\UserItems;
				class UserCourseModel {
					public static array $result_map = [];
					private array $results = [];
					public function __construct( array $results = [] ) {
						$this->results = $results;
					}
					public static function find( int $user_id, int $course_id, bool $check_cache = false ) {
						$key = $user_id . ":" . $course_id;
						if ( ! array_key_exists( $key, self::$result_map ) ) {
							return false;
						}
						$results = self::$result_map[ $key ];
						if ( ! is_array( $results ) ) {
							$results = [ "result" => (float) $results ];
						}
						return new self( $results );
					}
					public function calculate_course_results( bool $force_cache = false ): array {
						return $this->results;
					}
				}'
			);
		}

		if ( ! class_exists( 'LP_Test_Refund_Gateway', false ) ) {
			eval(
				'class LP_Test_Refund_Gateway {
					public array $calls = [];
					public array $response = [ "result" => "success", "refund_id" => "R-TEST", "status" => "COMPLETED" ];
					public function refund( ...$args ) {
						$this->calls[] = $args;
						return $this->response;
					}
				}'
			);
		}

		if ( ! class_exists( 'LP_Gateways', false ) ) {
			eval(
				'class LP_Gateways {
					public static $instance;
					public array $gateways = [];
					public static function instance() {
						if ( ! self::$instance ) {
							self::$instance = new self();
						}
						return self::$instance;
					}
					public function __construct() {
						$gateway = new LP_Test_Refund_Gateway();
						$this->gateways["paypal"] = $gateway;
						$this->gateways["stripe"] = new LP_Test_Refund_Gateway();
						$this->gateways["offline-payment"] = new class() {
							public function process_payment() {
								return [];
							}
						};
					}
					public function get_gateways(): array {
						return $this->gateways;
					}
					public function get_gateway( $id ) {
						return $this->gateways[ $id ] ?? null;
					}
					public function set_gateway( $id, $gateway ): void {
						$this->gateways[ $id ] = $gateway;
					}
				}'
			);
		}

		if ( ! class_exists( 'WP_User', false ) ) {
			eval(
				'class WP_User {
					public string $display_name = "Admin User";
					public string $user_email = "admin@example.com";
				}'
			);
		}

		defined( 'LP_ORDER_COMPLETED' ) || define( 'LP_ORDER_COMPLETED', 'completed' );
		defined( 'LP_ORDER_REFUNDED' ) || define( 'LP_ORDER_REFUNDED', 'refunded' );

		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias(
			static fn( $tag, $value ) => $value
		);
		Functions\when( 'sanitize_key' )->alias(
			static fn( $value ) => strtolower( (string) $value )
		);
		Functions\when( 'wp_parse_args' )->alias(
			static function( $args, $defaults = array() ) {
				if ( is_object( $args ) ) {
					$args = get_object_vars( $args );
				}
				if ( ! is_array( $args ) ) {
					$args = array();
				}
				if ( is_object( $defaults ) ) {
					$defaults = get_object_vars( $defaults );
				}
				if ( ! is_array( $defaults ) ) {
					$defaults = array();
				}
				return array_merge( $defaults, $args );
			}
		);
		Functions\when( 'absint' )->alias(
			static fn( $value ) => abs( (int) $value )
		);
		Functions\when( 'current_time' )->alias(
			static fn( $type = 'timestamp' ) => $type === 'timestamp' ? 1_700_000_000 : '2024-01-01 00:00:00'
		);
		Functions\when( 'get_post_time' )->alias(
			static fn() => 1_700_000_000
		);
		Functions\when( '__' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->alias(
			static fn( $value ) => trim( (string) $value )
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_user_by' )->justReturn( null );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'admin_url' )->alias(
			static fn( $path = '' ) => 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' )
		);
		Functions\when( 'add_query_arg' )->alias(
			static function( $args, $url = '' ) {
				if ( ! is_array( $args ) || empty( $args ) ) {
					return (string) $url;
				}

				$glue = str_contains( (string) $url, '?' ) ? '&' : '?';
				return (string) $url . $glue . http_build_query( $args );
			}
		);
		Functions\when( 'learn_press_get_currency_symbol' )->justReturn( '$' );

		if ( ! function_exists( 'learn_press_get_refund_setting' ) ) {
			require_once dirname( __DIR__, 3 ) . '/inc/order/lp-order-functions.php';
		}

		if ( ! class_exists( '\LearnPress\Ajax\AbstractAjax', false ) ) {
			require_once dirname( __DIR__, 3 ) . '/inc/Ajax/AbstractAjax.php';
		}

		if ( ! class_exists( '\LearnPress\Ajax\RefundOrderAjax', false ) ) {
			require_once dirname( __DIR__, 3 ) . '/inc/Ajax/RefundOrderAjax.php';
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->boot_refund_dependencies();
		\LP_Settings::$options = [];
		\LearnPress\Models\UserItems\UserCourseModel::$result_map = [];
	}

	private function set_refund_settings( array $options ): void {
		\LP_Settings::$options = array_merge(
			array(
				'enable_refund_requests' => 'yes',
				'allow_resend_after_rejected' => 'no',
				'refund_time_limit'      => 30,
				'refund_max_completion'  => 0,
				'require_refund_reason'  => 'no',
			),
			$options
		);
	}

	private function set_course_result( int $user_id, int $course_id, float $result ): void {
		\LearnPress\Models\UserItems\UserCourseModel::$result_map[ $user_id . ':' . $course_id ] = array(
			'result' => $result,
		);
	}

	/**
	 * Invoke private customer refund processor via reflection.
	 *
	 * @throws \ReflectionException
	 */
	private function invoke_process_refund_order( int $order_id, string $reason ): array {
		$ajax       = new RefundOrderAjax();
		$reflection = new ReflectionClass( RefundOrderAjax::class );
		$method     = $reflection->getMethod( 'process_refund_order' );
		$method->setAccessible( true );

		return $method->invoke( $ajax, $order_id, $reason );
	}

	/**
	 * Invoke private static execute refund method via reflection.
	 *
	 * @throws \ReflectionException
	 */
	private function invoke_execute_order_refund( \LP_Order $order, array $context = array() ): array {
		$reflection = new ReflectionClass( RefundOrderAjax::class );
		$method     = $reflection->getMethod( 'execute_order_refund' );
		$method->setAccessible( true );

		return $method->invoke( null, $order, $context );
	}

	#[Test]
	public function completion_helper_returns_max_progress_across_courses(): void {
		$order            = new \LP_Order( 1001 );
		$order->course_ids = array( 101, 202, 303 );
		$this->set_course_result( 77, 101, 20 );
		$this->set_course_result( 77, 202, 65 );
		$this->set_course_result( 77, 303, 40 );

		$data = learn_press_get_order_refund_completion_data( $order, 77 );

		$this->assertSame( 65.0, $data['completion_percent'] );
		$this->assertTrue( $data['has_course_progress'] );
	}

	#[Test]
	public function eligibility_allows_when_completion_equals_threshold(): void {
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 30,
			)
		);

		$order               = new \LP_Order( 1002 );
		$order->payment_method = 'stripe';
		$order->status       = 'completed';
		$order->course_ids   = array( 101 );
		$order->user_ids     = array( 77 );
		$order->guest        = false;
		$order->order_time   = 1_699_990_000;
		$this->set_course_result( 77, 101, 30 );
		Functions\when( 'get_post_meta' )->alias(
			static fn() => ''
		);

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertTrue( $result['eligible'] );
		$this->assertSame( 'ok', $result['code'] );
	}

	#[Test]
	public function eligibility_rejects_when_completion_exceeds_threshold(): void {
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 30,
			)
		);

		$order                 = new \LP_Order( 10021 );
		$order->payment_method = 'stripe';
		$order->status         = 'completed';
		$order->course_ids     = array( 101 );
		$order->user_ids       = array( 77 );
		$order->guest          = false;
		$order->order_time     = 1_699_990_000;
		$this->set_course_result( 77, 101, 31 );

		Functions\when( 'get_post_meta' )->alias(
			static fn() => ''
		);

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertFalse( $result['eligible'] );
		$this->assertSame( 'completion_exceeded', $result['code'] );
	}

	#[Test]
	public function eligibility_rejects_unsupported_gateway(): void {
		$this->set_refund_settings( array() );

		$order               = new \LP_Order( 1003 );
		$order->payment_method = 'offline';
		$order->status       = 'completed';
		$order->user_ids     = array( 77 );

		Functions\when( 'get_post_meta' )->alias(
			static fn() => ''
		);

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertFalse( $result['eligible'] );
		$this->assertSame( 'unsupported_gateway', $result['code'] );
	}

	#[Test]
	public function eligibility_returns_already_refunded_before_invalid_status(): void {
		$this->set_refund_settings( array() );

		$order                 = new \LP_Order( 10030 );
		$order->status         = 'refunded';
		$order->payment_method = 'paypal';
		$order->user_ids       = array( 77 );
		$order->guest          = false;

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertFalse( $result['eligible'] );
		$this->assertSame( 'already_refunded', $result['code'] );
	}

	#[Test]
	public function eligibility_rejects_when_refund_feature_disabled(): void {
		$this->set_refund_settings(
			array(
				'enable_refund_requests' => 'no',
			)
		);

		$order             = new \LP_Order( 10031 );
		$order->status     = 'completed';
		$order->user_ids   = array( 77 );
		$order->guest      = false;
		$order->order_time = 1_699_990_000;

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertFalse( $result['eligible'] );
		$this->assertSame( 'refund_disabled', $result['code'] );
	}

	#[Test]
	public function eligibility_rejects_guest_order(): void {
		$this->set_refund_settings( array() );

		$order             = new \LP_Order( 10032 );
		$order->status     = 'completed';
		$order->payment_method = 'stripe';
		$order->guest      = true;

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertFalse( $result['eligible'] );
		$this->assertSame( 'guest_not_supported', $result['code'] );
	}

	#[Test]
	public function eligibility_rejects_non_owner(): void {
		$this->set_refund_settings( array() );

		$order                 = new \LP_Order( 10033 );
		$order->status         = 'completed';
		$order->payment_method = 'stripe';
		$order->user_ids       = array( 44 );
		$order->guest          = false;

		Functions\when( 'get_post_meta' )->alias(
			static fn() => ''
		);

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertFalse( $result['eligible'] );
		$this->assertSame( 'invalid_owner', $result['code'] );
	}

	#[Test]
	public function eligibility_rejects_pending_request(): void {
		$this->set_refund_settings( array() );

		$order                 = new \LP_Order( 10034 );
		$order->status         = 'completed';
		$order->payment_method = 'stripe';
		$order->user_ids       = array( 1 );

		Functions\when( 'get_post_meta' )->alias(
			static function( int $post_id, string $key ) {
				return '_lp_refund_request' === $key ? 'pending' : '';
			}
		);

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertFalse( $result['eligible'] );
		$this->assertSame( 'pending_request', $result['code'] );
	}

	#[Test]
	public function eligibility_rejects_rerequest_when_denied_and_not_allowed(): void {
		$this->set_refund_settings(
			array(
				'allow_resend_after_rejected' => 'no',
			)
		);

		$order                 = new \LP_Order( 10035 );
		$order->status         = 'completed';
		$order->payment_method = 'stripe';
		$order->user_ids       = array( 77 );

		Functions\when( 'get_post_meta' )->alias(
			static function( int $post_id, string $key ) {
				return '_lp_refund_request' === $key ? 'rejected' : '';
			}
		);

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertFalse( $result['eligible'] );
		$this->assertSame( 'rerequest_not_allowed', $result['code'] );
	}

	#[Test]
	public function eligibility_allows_rerequest_when_denied_and_setting_enabled(): void {
		$this->set_refund_settings(
			array(
				'allow_resend_after_rejected' => 'yes',
				'refund_max_completion'  => 80,
			)
		);

		$order                 = new \LP_Order( 10036 );
		$order->status         = 'completed';
		$order->payment_method = 'stripe';
		$order->user_ids       = array( 77 );
		$order->course_ids     = array( 101 );
		$order->guest          = false;
		$order->order_time     = 1_699_990_000;
		$this->set_course_result( 77, 101, 20 );
		Functions\when( 'get_post_meta' )->alias(
			static function( int $post_id, string $key ) {
				return '_lp_refund_request' === $key ? 'rejected' : '';
			}
		);

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertTrue( $result['eligible'] );
		$this->assertSame( 'ok', $result['code'] );
	}

	#[Test]
	public function eligibility_rejects_when_time_limit_exceeded(): void {
		$this->set_refund_settings(
			array(
				'refund_time_limit' => 7,
			)
		);

		$order                 = new \LP_Order( 10037 );
		$order->status         = 'completed';
		$order->payment_method = 'stripe';
		$order->user_ids       = array( 77 );
		$order->guest          = false;
		$order->order_time     = 1_699_000_000;

		Functions\when( 'get_post_meta' )->alias(
			static fn() => ''
		);

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertFalse( $result['eligible'] );
		$this->assertSame( 'time_limit_exceeded', $result['code'] );
	}

	#[Test]
	public function eligibility_reports_require_reason_flag_from_setting(): void {
		$this->set_refund_settings(
			array(
				'require_refund_reason' => 'yes',
				'refund_time_limit'     => 0,
			)
		);

		$order                 = new \LP_Order( 10038 );
		$order->status         = 'completed';
		$order->payment_method = 'stripe';
		$order->user_ids       = array( 77 );
		$order->guest          = false;

		Functions\when( 'get_post_meta' )->alias(
			static fn() => ''
		);

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertTrue( $result['eligible'] );
		$this->assertTrue( $result['require_reason'] );
		$this->assertSame( 10, $result['reason_min'] );
	}

	#[Test]
	public function eligibility_allows_when_threshold_is_hundred_and_completion_hundred(): void {
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 100,
				'refund_time_limit'     => 0,
			)
		);

		$order                 = new \LP_Order( 100381 );
		$order->status         = 'completed';
		$order->payment_method = 'stripe';
		$order->user_ids       = array( 77 );
		$order->course_ids     = array( 101 );
		$order->guest          = false;
		$this->set_course_result( 77, 101, 100 );

		Functions\when( 'get_post_meta' )->alias(
			static fn() => ''
		);

		$result = learn_press_get_order_refund_eligibility( $order, 77 );

		$this->assertTrue( $result['eligible'] );
		$this->assertSame( 'ok', $result['code'] );
	}

	#[Test]
	public function process_refund_order_rejects_when_feature_disabled(): void {
		$this->set_refund_settings(
			array(
				'enable_refund_requests' => 'no',
			)
		);

		Functions\when( 'is_admin' )->justReturn( false );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Refund requests are currently disabled.' );
		$this->invoke_process_refund_order( 10040, '' );
	}

	#[Test]
	public function process_refund_order_rejects_guest_user_request(): void {
		$this->set_refund_settings( array() );

		$order                 = new \LP_Order( 10041 );
		$order->status         = 'completed';
		$order->payment_method = 'stripe';
		$order->user_ids       = array( 77 );
		$order->guest          = false;

		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'learn_press_get_order' )->alias(
			static fn( $order_id ) => (int) $order_id === 10041 ? $order : null
		);
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'learn_press_get_current_user' )->justReturn( null );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Guest account cannot request refunds.' );
		$this->invoke_process_refund_order( 10041, '' );
	}

	#[Test]
	public function process_refund_order_rejects_empty_reason_when_required(): void {
		$this->set_refund_settings(
			array(
				'require_refund_reason' => 'yes',
				'refund_time_limit'     => 0,
				'refund_max_completion' => 0,
			)
		);

		$order                 = new \LP_Order( 10042 );
		$order->status         = 'completed';
		$order->payment_method = 'stripe';
		$order->user_ids       = array( 77 );
		$order->guest          = false;

		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'learn_press_get_order' )->alias(
			static fn( $order_id ) => (int) $order_id === 10042 ? $order : null
		);
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 77 );
		Functions\when( 'learn_press_get_current_user' )->justReturn( new \LP_User( array(), 77 ) );
		Functions\when( 'get_post_meta' )->alias(
			static fn() => ''
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Refund reason is required.' );
		$this->invoke_process_refund_order( 10042, '' );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function execute_refund_paypal_partial_refund_passes_amount_and_note_to_gateway(): void {
		$this->boot_refund_dependencies();
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 0,
			)
		);

		$order                 = new \LP_Order( 10043 );
		$order->status         = 'completed';
		$order->payment_method = 'paypal';
		$order->total          = 150.00;
		$order->user_ids       = array( 77 );

		$meta = array();
		Functions\when( 'get_post_meta' )->alias(
			static function( int $post_id, string $key ) use ( &$meta ) {
				return $meta[ $post_id ][ $key ] ?? '';
			}
		);
		Functions\when( 'update_post_meta' )->alias(
			static function( int $post_id, string $key, $value ) use ( &$meta ) {
				$meta[ $post_id ][ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_post_meta' )->alias(
			static function( int $post_id, string $key ) use ( &$meta ) {
				unset( $meta[ $post_id ][ $key ] );
				return true;
			}
		);

		$gateway = new \LP_Test_Refund_Gateway();
		\LP_Gateways::instance()->set_gateway( 'paypal', $gateway );

		$result = $this->invoke_execute_order_refund(
			$order,
			array(
				'actor_id'       => 5,
				'actor_type'     => 'admin',
				'request_status' => 'approved',
				'requested_by'   => 77,
				'requested_at'   => '2026-04-17 09:00:00',
				'reviewed_by'    => 5,
				'refund_amount'  => 75.00,
				'note'           => 'Approved by support.',
			)
		);

		$this->assertSame( 'success', $result['result'] );
		$this->assertCount( 1, $gateway->calls );
		$this->assertCount( 3, $gateway->calls[0] );
		$this->assertSame( 10043, $gateway->calls[0][0] );
		$this->assertSame( 75.0, $gateway->calls[0][1] );
		$this->assertSame( 'Approved by support.', $gateway->calls[0][2] );
		$this->assertSame( 75.0, $result['refund_amount'] );
		$this->assertSame( 50.0, $result['refund_percent'] );
		$this->assertFalse( $result['is_full_refund'] );
		$this->assertSame( 'refunded', $order->status );
		$this->assertSame( 'approved', $meta[10043]['_lp_refund_request'] );
		$this->assertSame( '2026-04-17 09:00:00', $meta[10043]['_lp_refund_requested_at'] );
		$this->assertSame( 5, $meta[10043]['_lp_refund_reviewed_by'] );
		$this->assertSame( 'Approved by support.', $meta[10043]['_lp_refund_note'] );
		$this->assertSame( 75.0, $meta[10043]['_lp_refund_amount'] );
		$this->assertSame( 50.0, $meta[10043]['_lp_refund_percent'] );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function execute_refund_paypal_completion_limited_passes_full_amount_to_gateway(): void {
		$this->boot_refund_dependencies();
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 80,
			)
		);

		$order                 = new \LP_Order( 10044 );
		$order->status         = 'completed';
		$order->payment_method = 'paypal';
		$order->total          = 200.00;
		$order->course_ids     = array( 101 );
		$order->user_ids       = array( 77 );

		$meta = array();
		$this->set_course_result( 77, 101, 40 );
		Functions\when( 'get_post_meta' )->alias(
			static function( int $post_id, string $key ) use ( &$meta ) {
				return $meta[ $post_id ][ $key ] ?? '';
			}
		);
		Functions\when( 'update_post_meta' )->alias(
			static function( int $post_id, string $key, $value ) use ( &$meta ) {
				$meta[ $post_id ][ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_post_meta' )->alias(
			static function( int $post_id, string $key ) use ( &$meta ) {
				unset( $meta[ $post_id ][ $key ] );
				return true;
			}
		);

		$gateway = new \LP_Test_Refund_Gateway();
		\LP_Gateways::instance()->set_gateway( 'paypal', $gateway );

		$result = $this->invoke_execute_order_refund(
			$order,
			array(
				'actor_id'       => 6,
				'actor_type'     => 'admin',
				'request_status' => 'approved',
				'requested_by'   => 77,
				'requested_at'   => '2026-04-17 09:05:00',
				'reviewed_by'    => 6,
			)
		);

		$this->assertSame( 'success', $result['result'] );
		$this->assertCount( 1, $gateway->calls );
		$this->assertCount( 3, $gateway->calls[0] );
		$this->assertSame( 10044, $gateway->calls[0][0] );
		$this->assertSame( 200.0, $gateway->calls[0][1] );
		$this->assertSame( '', $gateway->calls[0][2] );
		$this->assertSame( 'approved', $meta[10044]['_lp_refund_request'] );
		$this->assertSame( '2026-04-17 09:05:00', $meta[10044]['_lp_refund_requested_at'] );
		$this->assertSame( 6, $meta[10044]['_lp_refund_reviewed_by'] );
		$this->assertSame( 200.0, $meta[10044]['_lp_refund_amount'] );
		$this->assertSame( 100.0, $meta[10044]['_lp_refund_percent'] );
		$this->assertSame( 40.0, $meta[10044]['_lp_refund_completion'] );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function calculation_returns_full_refund_when_max_completion_disabled(): void {
		$this->boot_refund_dependencies();
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 0,
			)
		);

		$order             = new \LP_Order( 10039 );
		$order->total      = 120.50;
		$order->course_ids = array( 101 );
		$order->user_ids   = array( 77 );

		Functions\when( 'get_post_meta' )->alias(
			static fn() => 0
		);

		$reflection = new ReflectionClass( RefundOrderAjax::class );
		$method     = $reflection->getMethod( 'calculate_refund_amount_by_completion' );
		$method->setAccessible( true );

		$result = $method->invoke( null, $order, array( 'requested_by' => 77 ) );

		$this->assertSame( 120.5, $result['refund_amount'] );
		$this->assertSame( 100.0, $result['refund_percent'] );
		$this->assertTrue( $result['is_full_refund'] );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function calculation_accepts_admin_partial_refund_amount(): void {
		$this->boot_refund_dependencies();
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 0,
			)
		);

		$order        = new \LP_Order( 10040 );
		$order->total = 120.00;

		$reflection = new ReflectionClass( RefundOrderAjax::class );
		$method     = $reflection->getMethod( 'calculate_refund_amount_by_completion' );
		$method->setAccessible( true );

		$result = $method->invoke(
			null,
			$order,
			array(
				'actor_type'    => 'admin',
				'refund_amount' => 45.50,
			)
		);

		$this->assertSame( 45.5, $result['refund_amount'] );
		$this->assertSame( 37.92, $result['refund_percent'] );
		$this->assertFalse( $result['is_full_refund'] );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function calculation_rejects_refund_amount_above_order_total(): void {
		$this->boot_refund_dependencies();
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 0,
			)
		);

		$order        = new \LP_Order( 10041 );
		$order->total = 120.00;

		$reflection = new ReflectionClass( RefundOrderAjax::class );
		$method     = $reflection->getMethod( 'calculate_refund_amount_by_completion' );
		$method->setAccessible( true );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Refund amount must be greater than 0 and must not exceed the order total.' );
		$method->invoke(
			null,
			$order,
			array(
				'actor_type'    => 'admin',
				'refund_amount' => 120.01,
			)
		);
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function calculation_returns_full_amount_for_completion_limited_mode(): void {
		$this->boot_refund_dependencies();
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 80,
			)
		);

		$order               = new \LP_Order( 1004 );
		$order->total        = 200.00;
		$order->course_ids   = array( 101 );
		$order->user_ids     = array( 77 );

		$this->set_course_result( 77, 101, 40 );
		Functions\when( 'get_post_meta' )->alias(
			static fn() => 0
		);

		$reflection = new ReflectionClass( RefundOrderAjax::class );
		$method     = $reflection->getMethod( 'calculate_refund_amount_by_completion' );
		$method->setAccessible( true );

		$result = $method->invoke( null, $order, array( 'requested_by' => 77 ) );

		$this->assertSame( 100.0, $result['refund_percent'] );
		$this->assertSame( 200.0, $result['refund_amount'] );
		$this->assertTrue( $result['is_full_refund'] );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function calculation_rejects_when_requester_cannot_be_resolved(): void {
		$this->boot_refund_dependencies();
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 80,
			)
		);

		$order             = new \LP_Order( 1005 );
		$order->total      = 100.00;
		$order->course_ids = array( 101 );

		Functions\when( 'get_post_meta' )->alias(
			static fn() => 0
		);

		$reflection = new ReflectionClass( RefundOrderAjax::class );
		$method     = $reflection->getMethod( 'calculate_refund_amount_by_completion' );
		$method->setAccessible( true );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Could not determine refund requester for completion-based refund.' );
		$method->invoke( null, $order, array( 'actor_type' => 'admin' ) );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function calculation_rejects_when_completion_exceeds_threshold(): void {
		$this->boot_refund_dependencies();
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 30,
			)
		);

		$order             = new \LP_Order( 1006 );
		$order->total      = 200;
		$order->course_ids = array( 101 );
		$order->user_ids   = array( 77 );

		$this->set_course_result( 77, 101, 31 );
		Functions\when( 'get_post_meta' )->alias(
			static fn() => 0
		);

		$reflection = new ReflectionClass( RefundOrderAjax::class );
		$method     = $reflection->getMethod( 'calculate_refund_amount_by_completion' );
		$method->setAccessible( true );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Course completion exceeds the refund limit.' );
		$method->invoke( null, $order, array( 'requested_by' => 77 ) );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function calculation_does_not_require_requester_when_threshold_is_unlimited(): void {
		$this->boot_refund_dependencies();
		$this->set_refund_settings(
			array(
				'refund_max_completion' => 0,
			)
		);

		$order             = new \LP_Order( 10061 );
		$order->total      = 88.5;
		$order->course_ids = array( 101 );
		$order->user_ids   = array();

		$reflection = new ReflectionClass( RefundOrderAjax::class );
		$method     = $reflection->getMethod( 'calculate_refund_amount_by_completion' );
		$method->setAccessible( true );

		$result = $method->invoke( null, $order, array( 'actor_type' => 'admin' ) );

		$this->assertSame( 88.5, $result['refund_amount'] );
		$this->assertSame( 100.0, $result['refund_percent'] );
		$this->assertTrue( $result['is_full_refund'] );
	}
}
