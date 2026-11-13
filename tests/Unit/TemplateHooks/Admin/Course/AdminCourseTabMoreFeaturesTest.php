<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Admin\Course;

use Brain\Monkey\Functions;
use LearnPress\Services\AddonService;
use LearnPress\TemplateHooks\Admin\Course\AdminCourseTabMoreFeatures;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;
use ReflectionClass;
use stdClass;

/**
 * Test double: in-memory add-ons and installed plugins.
 */
class FakeAdminCourseTabMoreFeatures extends AdminCourseTabMoreFeatures {
	public $addons;
	public $installed = [];
	public $throw     = false;

	protected function get_installed_plugins(): array {
		return $this->installed;
	}
}

class AdminCourseTabMoreFeaturesTest extends BrainMonkeyTestCase {
	protected function tearDown(): void {
		$this->set_addon_service( null );
		parent::tearDown();
	}

	/**
	 * Replace the AddonService singleton (its constructor loads WP admin files).
	 */
	private function set_addon_service( $service ): void {
		$property = ( new ReflectionClass( AddonService::class ) )->getProperty( 'instance' );
		$property->setValue( null, $service );
	}

	private function make( array $addons ): FakeAdminCourseTabMoreFeatures {
		// Singleton constructor is final/protected: build without it.
		$fake         = ( new ReflectionClass( FakeAdminCourseTabMoreFeatures::class ) )->newInstanceWithoutConstructor();
		$fake->addons = new stdClass();
		foreach ( $addons as $slug => $data ) {
			$fake->addons->{$slug} = (object) $data;
		}

		$service = Mockery::mock( AddonService::class );
		$service->shouldReceive( 'get_addons' )->andReturnUsing(
			static function () use ( $fake ) {
				if ( $fake->throw ) {
					throw new \Exception( 'boom' );
				}
				return $fake->addons;
			}
		);
		$this->set_addon_service( $service );

		return $fake;
	}

	public function test_get_recommended_addons_skips_installed(): void {
		$fake            = $this->make(
			[
				'a' => [ 'name' => 'A', 'basename' => 'a/a.php' ],
				'b' => [ 'name' => 'B', 'basename' => 'b/b.php' ],
			]
		);
		$fake->installed = [ 'a/a.php' => [] ];

		$list = $fake->get_recommended_addons();

		$this->assertCount( 1, $list );
		$this->assertSame( 'b', $list[0]['slug'] );
	}

	public function test_get_recommended_addons_skips_invalid_entries(): void {
		$fake                  = $this->make( [ 'ok' => [ 'name' => 'OK' ] ] );
		$fake->addons->noname  = (object) [ 'description' => 'x' ];
		$fake->addons->notobj  = 'string';

		$list = $fake->get_recommended_addons();

		$this->assertSame( [ 'ok' ], array_column( $list, 'slug' ) );
	}

	public function test_get_recommended_addons_defaults_link(): void {
		$fake = $this->make( [ 'a' => [ 'name' => 'A' ] ] );

		$list = $fake->get_recommended_addons();

		$this->assertSame( AdminCourseTabMoreFeatures::URL_DISCOVER, $list[0]['link'] );
	}

	public function test_get_recommended_addons_returns_empty_on_error(): void {
		$fake        = $this->make( [] );
		$fake->throw = true;

		$this->assertSame( [], $fake->get_recommended_addons() );
	}

	public function test_html_more_features_renders_items_and_hides_over_limit(): void {
		Functions\stubs(
			[
				'esc_url' => static function ( $v ) {
					return $v;
				},
			]
		);
		$data = [];
		for ( $i = 1; $i <= 7; $i++ ) {
			$data[ 'addon-' . $i ] = [ 'name' => 'Addon ' . $i, 'link' => 'https://x.test/' . $i ];
		}
		$fake = $this->make( $data );

		$html = $fake->html_more_features();

		$this->assertSame( 7, substr_count( $html, 'class="lp-more-features__item"' ) );
		$this->assertSame( 2, preg_match_all( '/<li class="lp-more-features__item"[^>]* hidden>/', $html ) );
		$this->assertStringContainsString( 'data-slug="addon-1">', $html );
		$this->assertStringContainsString( 'data-slug="addon-6" hidden', $html );
		$this->assertStringContainsString( 'data-max="5"', $html );
		$this->assertStringContainsString( 'data-ttl-days="30"', $html );
		$this->assertStringContainsString( 'lp-more-features__reset" hidden', $html );
		$this->assertStringContainsString( 'target="_blank" rel="noopener noreferrer">Discover more options', $html );
		$this->assertStringContainsString( AdminCourseTabMoreFeatures::URL_DISCOVER, $html );
	}

	public function test_html_more_features_escapes_output(): void {
		Functions\stubs(
			[
				'esc_url' => static function ( $v ) {
					return $v;
				},
			]
		);
		$fake = $this->make( [ 'a' => [ 'name' => '<script>x</script>', 'description' => '<b>d</b>' ] ] );

		$html = $fake->html_more_features();

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<b>d</b>', $html );
	}

	public function test_html_more_features_shows_none_message_when_empty(): void {
		Functions\stubs(
			[
				'esc_url' => static function ( $v ) {
					return $v;
				},
			]
		);
		$fake = $this->make( [] );

		$html = $fake->html_more_features();

		$this->assertStringContainsString( 'lp-more-features__none', $html );
		$this->assertStringNotContainsString( 'lp-more-features__item"', $html );
	}
}
