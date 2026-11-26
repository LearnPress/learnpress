<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Services;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Exception;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPress\Services\UserCourseService;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;
use ReflectionClass;
use WP_Error;

/**
 * Fake cart: records calls.
 */
class FakeUserCourseCart {
	/** @var array[] */
	public $added = [];
	/** @var int */
	public $emptied = 0;
	/** @var string */
	public $cart_id = 'cart-1';

	public function empty_cart() {
		++$this->emptied;
	}

	public function add_to_cart( int $item_id = 0, int $quantity = 1, array $item_data = [] ) {
		$this->added[] = [ $item_id, $quantity, $item_data ];

		return $this->cart_id;
	}
}

/**
 * Fake order.
 */
class FakeUserCourseOrder {
	/** @var float */
	public $total = 0;
	/** @var int */
	public $completed = 0;

	public function get_total() {
		return $this->total;
	}

	public function payment_complete() {
		++$this->completed;
	}
}

/**
 * Service with seams replaced, no DB / cart / order of WordPress.
 */
class FakeUserCourseService extends UserCourseService {
	/** @var UserCourseModel|false */
	public $user_course = false;
	/** @var FakeUserCourseCart */
	public $cart;
	/** @var FakeUserCourseOrder */
	public $order;
	/** @var int[] */
	public $orders_created = [];
	/** @var array[] */
	public $mails = [];
	/** @var string */
	public $checkout_url = 'https://site.test/checkout/';

	protected function find_user_course( int $user_id, int $course_id ) {
		return $this->user_course;
	}

	protected function get_cart() {
		return $this->cart;
	}

	protected function get_checkout() {
		$service = $this;

		return new class( $service ) {
			private $service;

			public function __construct( $service ) {
				$this->service = $service;
			}

			public function create_order() {
				$this->service->orders_created[] = 99;

				return 99;
			}
		};
	}

	protected function get_order( $order_id ) {
		return $this->order;
	}

	protected function get_checkout_url(): string {
		return $this->checkout_url;
	}

	protected function link_no_cache( string $link ): string {
		return $link . '?no-cache=1';
	}

	protected function send_mail_enrolled( array $params ) {
		$this->mails[] = $params;
	}
}

/**
 * Unit tests for UserCourseService: enroll, purchase.
 */
class UserCourseServiceTest extends BrainMonkeyTestCase {

	/**
	 * @var FakeUserCourseService
	 */
	private $service;

	protected function setUp(): void {
		parent::setUp();

		defined( 'LP_COURSE_ENROLLED' ) || define( 'LP_COURSE_ENROLLED', 'enrolled' );
		defined( 'LP_COURSE_GRADUATION_IN_PROGRESS' ) || define( 'LP_COURSE_GRADUATION_IN_PROGRESS', 'in-progress' );
		if ( ! class_exists( 'LP_Datetime', false ) ) {
			eval( 'class LP_Datetime { public static $format = "Y-m-d H:i:s"; }' );
		}
		if ( ! class_exists( 'WP_Error', false ) ) {
			eval(
				'class WP_Error {
					private $message;
					public function __construct( $code = "", $message = "" ) { $this->message = $message; }
					public function get_error_message() { return $this->message; }
				}'
			);
		}

		$this->service        = ( new ReflectionClass( FakeUserCourseService::class ) )->newInstanceWithoutConstructor();
		$this->service->cart  = new FakeUserCourseCart();
		$this->service->order = new FakeUserCourseOrder();

		Functions\when( 'post_password_required' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'learn_press_enable_cart' )->justReturn( false );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
	}

	/**
	 * @param array $args [ can_enroll, can_purchase, first_item_id, allow_repurchase, repurchase_type ]
	 *
	 * @return CourseModel|Mockery\MockInterface
	 */
	private function make_course( array $args = [] ) {
		$course = Mockery::mock( CourseModel::class );
		$course->shouldReceive( 'get_id' )->andReturn( 30 );
		$course->shouldReceive( 'get_title' )->andReturn( 'PHP' );
		$course->shouldReceive( 'can_enroll' )->andReturn( $args['can_enroll'] ?? true );
		$course->shouldReceive( 'can_purchase' )->andReturn( $args['can_purchase'] ?? true );
		$course->shouldReceive( 'get_first_item_id' )->andReturn( $args['first_item_id'] ?? 55 );
		$course->shouldReceive( 'get_item_link' )->with( 55 )->andReturn( 'https://site.test/course/php/lessons/l1/' );
		$course->shouldReceive( 'get_permalink' )->andReturn( 'https://site.test/course/php/' );
		$course->shouldReceive( 'enable_allow_repurchase' )->andReturn( $args['allow_repurchase'] ?? false );
		$course->shouldReceive( 'get_type_repurchase' )->andReturn( $args['repurchase_type'] ?? 'popup' );

		return $course;
	}

	private function make_user() {
		$user = Mockery::mock( UserModel::class );
		$user->shouldReceive( 'get_id' )->andReturn( 7 );

		return $user;
	}

	// -------------------------------------------------------------------------
	// enroll
	// -------------------------------------------------------------------------

	public function test_enroll_password_protected_throws(): void {
		Functions\when( 'post_password_required' )->justReturn( true );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'This content is password protected.' );

		$this->service->enroll( $this->make_course(), $this->make_user() );
	}

	public function test_enroll_password_protected_allowed_for_editor(): void {
		Functions\when( 'post_password_required' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );

		$result = $this->service->enroll( $this->make_course(), $this->make_user() );

		$this->assertSame( 'https://site.test/course/php/lessons/l1/', $result['redirect'] );
	}

	public function test_enroll_can_not_enroll_throws_message(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Course is out of stock' );

		$this->service->enroll(
			$this->make_course( [ 'can_enroll' => new WP_Error( 'course_out_of_stock', 'Course is out of stock' ) ] ),
			$this->make_user()
		);
	}

	public function test_enroll_user_purchased_sets_enrolled_and_sends_mail(): void {
		$userCourse         = Mockery::mock( UserCourseModel::class );
		$userCourse->ref_id = 88;
		$userCourse->shouldReceive( 'has_purchased' )->andReturn( true );
		$userCourse->shouldReceive( 'save' )->once();
		$this->service->user_course = $userCourse;

		Actions\expectDone( 'learnpress/user/course-enrolled' )->once()->with( 88, 30, 7 );

		$result = $this->service->enroll( $this->make_course(), $this->make_user() );

		$this->assertSame( 'enrolled', $userCourse->status );
		$this->assertSame( 'in-progress', $userCourse->graduation );
		$this->assertNotEmpty( $userCourse->start_time );
		$this->assertSame( [ [ 88, 30, 7 ] ], $this->service->mails );
		$this->assertSame( [], $this->service->cart->added );
		$this->assertSame( 'https://site.test/course/php/lessons/l1/', $result['redirect'] );
		$this->assertStringContainsString( 'enrolled in the course successfully', $result['message'] );
	}

	public function test_enroll_free_course_creates_and_completes_order(): void {
		$result = $this->service->enroll( $this->make_course(), $this->make_user() );

		$this->assertSame( [ [ 30, 1, [] ] ], $this->service->cart->added );
		$this->assertSame( [ 99 ], $this->service->orders_created );
		$this->assertSame( 1, $this->service->order->completed );
		$this->assertSame( 2, $this->service->cart->emptied );
		$this->assertSame( 'https://site.test/course/php/lessons/l1/', $result['redirect'] );
	}

	public function test_enroll_free_course_never_completes_paid_order(): void {
		$this->service->order->total = 10.5;

		try {
			$this->service->enroll( $this->make_course(), $this->make_user() );
			$this->fail( 'Exception expected' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Invalid enrollment: the order total is not zero.', $e->getMessage() );
		}

		$this->assertSame( 0, $this->service->order->completed );
		$this->assertSame( 2, $this->service->cart->emptied );
	}

	public function test_enroll_add_to_cart_fails_throws(): void {
		$this->service->cart->cart_id = '';

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error: The course cannot be added to the cart.' );

		$this->service->enroll( $this->make_course(), $this->make_user() );
	}

	public function test_enroll_course_no_items_redirects_to_course(): void {
		$result = $this->service->enroll( $this->make_course( [ 'first_item_id' => 0 ] ), $this->make_user() );

		$this->assertSame( 'https://site.test/course/php/', $result['redirect'] );
	}

	public function test_enroll_guest_adds_to_cart_and_redirects_checkout(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Filters\expectApplied( 'learnpress/rest-api/courses/enroll/redirect' )
			->once()
			->with( 'https://site.test/checkout/', 30 )
			->andReturnFirstArg();

		$result = $this->service->enroll( $this->make_course(), false );

		$this->assertSame( [ [ 30, 1, [] ] ], $this->service->cart->added );
		$this->assertSame( [], $this->service->orders_created );
		$this->assertSame( 'Redirecting...', $result['message'] );
		$this->assertSame( 'https://site.test/checkout/?no-cache=1', $result['redirect'] );
	}

	public function test_enroll_guest_without_checkout_page_throws(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		$this->service->checkout_url = '';

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error: Please set up a page for checkout.' );

		$this->service->enroll( $this->make_course(), false );
	}

	// -------------------------------------------------------------------------
	// purchase
	// -------------------------------------------------------------------------

	public function test_purchase_adds_to_cart_with_form_data_and_quantity(): void {
		Filters\expectApplied( 'learnpress/rest-api/courses/purchase/redirect' )
			->once()
			->with( 'https://site.test/checkout/', 30, 'cart-1' )
			->andReturnFirstArg();

		$result = $this->service->purchase(
			$this->make_course(),
			$this->make_user(),
			[
				'purchase-course' => '30',
				'quantity'        => '2',
				'addon_field'     => 'x',
			]
		);

		$this->assertSame( [ [ 30, 2, [ 'purchase-course' => '30', 'addon_field' => 'x' ] ] ], $this->service->cart->added );
		$this->assertSame( 1, $this->service->cart->emptied, 'cart is emptied when cart feature disabled' );
		// Message is escaped (esc_html__), JS decodes entities when display.
		$this->assertSame( '&quot;PHP&quot; has been added to your cart. Redirecting...', $result['message'] );
		$this->assertSame( 'https://site.test/checkout/?no-cache=1', $result['redirect'] );
	}

	public function test_purchase_invalid_quantity_is_one_and_keep_cart_when_enabled(): void {
		Functions\when( 'learn_press_enable_cart' )->justReturn( true );

		$this->service->purchase( $this->make_course(), $this->make_user(), [ 'quantity' => '0' ] );

		$this->assertSame( [ [ 30, 1, [] ] ], $this->service->cart->added );
		$this->assertSame( 0, $this->service->cart->emptied );
	}

	public function test_purchase_can_not_purchase_throws_message(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'You can not purchase' );

		$this->service->purchase(
			$this->make_course( [ 'can_purchase' => new WP_Error( 'x', 'You can not purchase' ) ] ),
			$this->make_user()
		);
	}

	public function test_purchase_invalid_repurchase_type_throws(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error: Invalid repurchase option.' );

		$this->service->purchase( $this->make_course(), $this->make_user(), [], 'drop-table' );
	}

	public function test_purchase_repurchase_popup_returns_options(): void {
		$this->service->user_course = Mockery::mock( UserCourseModel::class );

		$result = $this->service->purchase( $this->make_course( [ 'allow_repurchase' => true ] ), $this->make_user() );

		$this->assertSame( 'allow_repurchase', $result['type'] );
		$this->assertSame( 'Repurchase Options', $result['title'] );
		$this->assertStringContainsString( 'name="_lp_allow_repurchase_type" value="reset"', $result['html'] );
		$this->assertStringContainsString( 'value="keep" type="radio" checked="checked"', $result['html'] );
		$this->assertSame( [], $this->service->cart->added );
	}

	public function test_purchase_repurchase_not_popup_adds_to_cart(): void {
		$this->service->user_course = Mockery::mock( UserCourseModel::class );

		$result = $this->service->purchase(
			$this->make_course(
				[
					'allow_repurchase' => true,
					'repurchase_type'  => 'reset',
				]
			),
			$this->make_user()
		);

		$this->assertArrayHasKey( 'redirect', $result );
		$this->assertCount( 1, $this->service->cart->added );
	}

	public function test_purchase_with_repurchase_type_saves_user_item_meta(): void {
		$userCourse = Mockery::mock( UserCourseModel::class );
		$userCourse->shouldReceive( 'get_user_item_id' )->andReturn( 501 );
		$this->service->user_course = $userCourse;

		Functions\expect( 'learn_press_update_user_item_meta' )->once()->with( 501, '_lp_allow_repurchase_type', 'keep' );

		$result = $this->service->purchase( $this->make_course( [ 'allow_repurchase' => true ] ), $this->make_user(), [], 'keep' );

		$this->assertCount( 1, $this->service->cart->added );
		$this->assertArrayHasKey( 'redirect', $result );
	}

	public function test_purchase_add_to_cart_fails_throws(): void {
		$this->service->cart->cart_id = '';

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error: The course cannot be added to the cart.' );

		$this->service->purchase( $this->make_course(), $this->make_user() );
	}
}
