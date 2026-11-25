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
 * Fixture: class declares nonce for all its actions.
 */
class AbstractAjaxNonceFixture extends AbstractAjax {
	/**
	 * @var string[]
	 */
	protected static $actions = [ 'do_with_nonce' ];

	/**
	 * @var string
	 */
	protected static $action_nonce = 'lp_fixture_nonce';

	/**
	 * @var string[] methods called
	 */
	public static $called = [];

	public function do_with_nonce() {
		self::$called[] = 'do_with_nonce';
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
					public static array $types = array();
					public static function get_param( string $key, $default = null, $type = "", $method = "" ) {
						self::$types[ $key ] = $type;
						return self::$params[ $key ] ?? $default;
					}
				}'
			);
		}

		AbstractAjaxDeclaredFixture::$called = [];
		AbstractAjaxNonceFixture::$called    = [];
		AbstractAjaxLegacyFixture::$called   = [];
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'sanitize_text_field' )->returnArg();
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
	private function request( string $action, array $data = [] ): void {
		$_REQUEST['lp-load-ajax'] = $action;
		\LP_Request::$params      = [
			'lp-load-ajax' => $action,
			'nonce'        => 'nonce',
			'data'         => wp_json_encode( $data ),
		];
	}

	/**
	 * Stub nonce functions: valid nonce is "<nonce action>-ok".
	 *
	 * @return array verify calls, each item [ nonce, nonce action ].
	 */
	private function stub_action_nonce(): array {
		$calls = [];
		Functions\when( 'wp_create_nonce' )->alias(
			static function ( $nonce_action ) {
				return $nonce_action . '-ok';
			}
		);
		Functions\when( 'wp_verify_nonce' )->alias(
			static function ( $nonce, $nonce_action ) use ( &$calls ) {
				$calls[] = [ $nonce, $nonce_action ];
				return $nonce === $nonce_action . '-ok' ? 1 : false;
			}
		);

		return $calls;
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

	public function test_create_action_nonce_of_class() {
		$this->stub_action_nonce();

		$this->assertSame( 'lp_fixture_nonce-ok', AbstractAjaxNonceFixture::create_action_nonce() );
		$this->assertSame( '', AbstractAjaxDeclaredFixture::create_action_nonce() );
	}

	public function test_verify_action_nonce_class_not_declare_return_true() {
		$this->request( 'do_allowed' );

		$this->assertTrue( AbstractAjaxDeclaredFixture::verify_action_nonce() );
	}

	public function test_verify_action_nonce_valid() {
		$this->stub_action_nonce();
		$this->request( 'do_with_nonce', [ 'lp_action_nonce' => 'lp_fixture_nonce-ok' ] );

		$this->assertTrue( AbstractAjaxNonceFixture::verify_action_nonce() );
	}

	public function test_verify_action_nonce_read_data_not_sanitize_text() {
		$this->stub_action_nonce();
		$this->request( 'do_with_nonce', [ 'name' => 'a<b', 'url' => 'c>d', 'lp_action_nonce' => 'lp_fixture_nonce-ok' ] );
		if ( ! property_exists( \LP_Request::class, 'types' ) ) {
			$this->markTestSkipped( 'LP_Request stub of another test is loaded.' );
		}

		$this->assertTrue( AbstractAjaxNonceFixture::verify_action_nonce() );
		$this->assertSame( 'trim', \LP_Request::$types['data'] );
	}

	public function test_verify_action_nonce_missing_or_invalid() {
		$this->stub_action_nonce();

		$this->request( 'do_with_nonce' );
		$this->assertFalse( AbstractAjaxNonceFixture::verify_action_nonce() );

		// The general nonce 'wp_rest' is not accepted.
		$this->request( 'do_with_nonce', [ 'lp_action_nonce' => 'wp_rest-ok' ] );
		$this->assertFalse( AbstractAjaxNonceFixture::verify_action_nonce() );

		$this->request( 'do_with_nonce', [ 'lp_action_nonce' => [ 'lp_fixture_nonce-ok' ] ] );
		$this->assertFalse( AbstractAjaxNonceFixture::verify_action_nonce() );

		// Invalid JSON must not throw.
		\LP_Request::$params['data'] = '{invalid';
		$this->assertFalse( AbstractAjaxNonceFixture::verify_action_nonce() );
	}

	public function test_catch_lp_ajax_reject_action_without_action_nonce() {
		$this->stub_action_nonce();
		\LP_Request::$params = [];
		$sent                = null;
		Functions\when( 'wp_send_json' )->alias(
			static function ( $response ) use ( &$sent ) {
				$sent = $response;
			}
		);

		$_REQUEST['lp-load-ajax'] = 'do_with_nonce';
		\LP_Request::$params      = [
			'lp-load-ajax' => 'do_with_nonce',
			'nonce'        => 'wp_rest-ok',
			'data'         => json_encode( [] ),
		];
		AbstractAjaxNonceFixture::catch_lp_ajax();

		$this->assertSame( [], AbstractAjaxNonceFixture::$called );
		$this->assertSame( 'error', $sent->status );
	}

	public function test_catch_lp_ajax_call_action_with_valid_action_nonce() {
		$this->stub_action_nonce();
		$_REQUEST['lp-load-ajax'] = 'do_with_nonce';
		\LP_Request::$params      = [
			'lp-load-ajax' => 'do_with_nonce',
			'nonce'        => 'wp_rest-ok',
			'data'         => json_encode( [ 'lp_action_nonce' => 'lp_fixture_nonce-ok' ] ),
		];
		AbstractAjaxNonceFixture::catch_lp_ajax();

		$this->assertSame( [ 'do_with_nonce' ], AbstractAjaxNonceFixture::$called );
	}

	public function test_core_classes_declare_action_nonces() {
		$this->stub_action_nonce();

		$this->assertSame( 'lp_sample_data_nonce-ok', \LearnPress\Ajax\SampleDataAJAX::create_action_nonce() );
		$this->assertSame( 'lp_setup_wizard_nonce-ok', \LearnPress\Ajax\SetupWizardAjax::create_action_nonce() );
		$this->assertSame( 'lp_course_tools_nonce-ok', \LearnPress\Ajax\CourseToolsAjax::create_action_nonce() );
		$this->assertSame( 'lp_mcp_api_keys_nonce-ok', \LearnPress\Ajax\MCP\McpApiKeysAjax::create_action_nonce() );
		$this->assertSame( 'lp_webhooks_nonce-ok', \LearnPress\Ajax\Webhook\WebhooksAjax::create_action_nonce() );
		$this->assertSame( 'lp_course_builder_nonce-ok', \LearnPress\Ajax\CourseBuilder\CourseBuilderAjax::create_action_nonce() );
		$this->assertSame( '', \LearnPress\Ajax\LoadContentViaAjax::create_action_nonce() );
	}
}
