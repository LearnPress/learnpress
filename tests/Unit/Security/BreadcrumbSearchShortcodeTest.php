<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Security;

use Brain\Monkey\Functions;
use LearnPress\Helpers\Template;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use LP_Breadcrumb;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ReflectionMethod;

/**
 * Regression: ?c_search=[audio src=...] must not reach the breadcrumb as shortcode syntax,
 * otherwise do_shortcode() on the_content executes it inside the Breadcrumb block.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
class BreadcrumbSearchShortcodeTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'LP_Request', false ) ) {
			eval(
				'class LP_Request {
					public static function get_param( $key, $default_value = "", $type = "text", $method = "" ) {
						return $_GET[ $key ] ?? $default_value;
					}
				}'
			);
		}

		Functions\when( 'is_search' )->justReturn( false );
		require_once LP_ABSPATH . 'inc/class-lp-breadcrumb.php';
	}

	protected function tearDown(): void {
		unset( $_GET['c_search'] );
		parent::tearDown();
	}

	private function search_crumbs(): array {
		$breadcrumb = new LP_Breadcrumb();
		$method     = new ReflectionMethod( LP_Breadcrumb::class, 'search_trail' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}
		$method->invoke( $breadcrumb );

		return $breadcrumb->get_breadcrumb();
	}

	public function test_c_search_strips_square_brackets(): void {
		$_GET['c_search'] = '[audio src=http://example.test/x.mp3]';

		$crumbs = $this->search_crumbs();

		$this->assertCount( 1, $crumbs );
		$this->assertStringNotContainsString( '[', $crumbs[0][0] );
		$this->assertStringNotContainsString( ']', $crumbs[0][0] );
		$this->assertStringContainsString( 'audio src=http://example.test/x.mp3', $crumbs[0][0] );
	}

	public function test_c_search_plain_text_kept(): void {
		$_GET['c_search'] = 'php';

		$crumbs = $this->search_crumbs();

		$this->assertCount( 1, $crumbs );
		$this->assertStringContainsString( 'php', $crumbs[0][0] );
	}

	public function test_c_search_only_brackets_adds_no_crumb(): void {
		$_GET['c_search'] = '[]';

		$this->assertSame( [], $this->search_crumbs() );
	}

	public function test_c_search_array_adds_no_crumb(): void {
		$_GET['c_search'] = [ '[audio]' ];

		$this->assertSame( [], $this->search_crumbs() );
	}

	public function test_neutralize_shortcode_brackets_uses_hex_entities(): void {
		$result = Template::neutralize_shortcode_brackets( '<span>[audio src=x]</span>' );

		$this->assertSame( '<span>&#x5B;audio src=x&#x5D;</span>', $result );
		// Must not use &#91; / &#93;, which unescape_invalid_shortcodes() converts back to [ ].
		$this->assertStringNotContainsString( '&#91;', $result );
		$this->assertStringNotContainsString( '&#93;', $result );
	}
}
