<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Ajax;

use Brain\Monkey\Functions;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Ajax\EditCurriculumAjax;
use LearnPress\Ajax\LoadContentViaAjax;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Fixture: class declares allow-list of actions.
 */
class AbstractAjaxDeclaredFixture extends AbstractAjax {
	/**
	 * @var string[]
	 */
	protected static $actions = [ 'do_allowed' ];

	/**
	 * @var string[] methods called
	 */
	public static $called = [];

	public function do_allowed() {
		self::$called[] = 'do_allowed';
	}

	public function helper_not_action() {
		self::$called[] = 'helper_not_action';
	}
}

/**
 * Fixture: class not declare allow-list (as addons), legacy behavior.
 */
class AbstractAjaxLegacyFixture extends AbstractAjax {
	/**
	 * @var string[] methods called
	 */
	public static $called = [];

	public function legacy_action() {
		self::$called[] = 'legacy_action';
	}
}

/**
 * Unit tests for AbstractAjax: allow-list of actions called via 'lp-load-ajax'.
 */
class AbstractAjaxTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'LP_Request', false ) ) {
			eval(
				'class LP_Request {
					public static array $params = array();
					public static function get_param( string $key, $default = null, $type = "", $method = "" ) {
						return self::$params[ $key ] ?? $default;
					}
				}'
			);
		}

		AbstractAjaxDeclaredFixture::$called = [];
		AbstractAjaxLegacyFixture::$called   = [];
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
	}

	protected function tearDown(): void {
		unset( $_REQUEST['lp-load-ajax'] );
		\LP_Request::$params = [];
		parent::tearDown();
	}

	/**
	 * Simulate request with param 'lp-load-ajax'.
	 *
	 * @param string $action
	 */
	private function request( string $action ): void {
		$_REQUEST['lp-load-ajax'] = $action;
		\LP_Request::$params      = [
			'lp-load-ajax' => $action,
			'nonce'        => 'nonce',
		];
	}

	public function test_is_action_allowed_declared_only_allow_list() {
		$this->assertTrue( AbstractAjaxDeclaredFixture::is_action_allowed( 'do_allowed' ) );
		$this->assertFalse( AbstractAjaxDeclaredFixture::is_action_allowed( 'helper_not_action' ) );
		$this->assertFalse( AbstractAjaxDeclaredFixture::is_action_allowed( 'catch_lp_ajax' ) );
		$this->assertFalse( AbstractAjaxDeclaredFixture::is_action_allowed( 'DO_ALLOWED' ) );
	}

	public function test_is_action_allowed_legacy_allow_public_method() {
		$this->assertTrue( AbstractAjaxLegacyFixture::is_action_allowed( 'legacy_action' ) );
		$this->assertFalse( AbstractAjaxLegacyFixture::is_action_allowed( 'not_exists' ) );
	}

	public function test_is_action_allowed_legacy_block_dispatcher_methods() {
		$this->assertFalse( AbstractAjaxLegacyFixture::is_action_allowed( 'catch_lp_ajax' ) );
		$this->assertFalse( AbstractAjaxLegacyFixture::is_action_allowed( 'Catch_LP_Ajax' ) );
		$this->assertFalse( AbstractAjaxLegacyFixture::is_action_allowed( 'is_action_allowed' ) );
		$this->assertFalse( AbstractAjaxLegacyFixture::is_action_allowed( '__construct' ) );
	}

	public function test_is_action_allowed_invalid_value() {
		$this->assertFalse( AbstractAjaxLegacyFixture::is_action_allowed( '' ) );
		$this->assertFalse( AbstractAjaxLegacyFixture::is_action_allowed( null ) );
		$this->assertFalse( AbstractAjaxLegacyFixture::is_action_allowed( [ 'legacy_action' ] ) );
	}

	public function test_catch_lp_ajax_call_allowed_action() {
		$this->request( 'do_allowed' );
		AbstractAjaxDeclaredFixture::catch_lp_ajax();

		$this->assertSame( [ 'do_allowed' ], AbstractAjaxDeclaredFixture::$called );
	}

	public function test_catch_lp_ajax_skip_method_not_in_allow_list() {
		$this->request( 'helper_not_action' );
		AbstractAjaxDeclaredFixture::catch_lp_ajax();

		$this->assertSame( [], AbstractAjaxDeclaredFixture::$called );
	}

	public function test_catch_lp_ajax_legacy_still_call_public_method() {
		$this->request( 'legacy_action' );
		AbstractAjaxLegacyFixture::catch_lp_ajax();

		$this->assertSame( [ 'legacy_action' ], AbstractAjaxLegacyFixture::$called );
	}

	public function test_catch_lp_ajax_not_call_itself() {
		// Before fix: infinite recursion (fatal).
		$this->request( 'catch_lp_ajax' );
		AbstractAjaxLegacyFixture::catch_lp_ajax();

		$this->assertSame( [], AbstractAjaxLegacyFixture::$called );
	}

	public function test_core_classes_reject_helper_methods() {
		$this->assertTrue( EditCurriculumAjax::is_action_allowed( 'course_add_section' ) );
		$this->assertFalse( EditCurriculumAjax::is_action_allowed( 'check_valid' ) );
		$this->assertTrue( LoadContentViaAjax::is_action_allowed( 'load_content_via_ajax' ) );
		$this->assertFalse( LoadContentViaAjax::is_action_allowed( 'catch_lp_ajax' ) );
	}
}
