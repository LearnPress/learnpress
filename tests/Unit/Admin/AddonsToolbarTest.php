<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Contract tests for the Add-ons toolbar markup and shared design tokens.
 */
class AddonsToolbarTest extends TestCase {

	#[Test]
	public function toolbar_uses_accessible_filter_controls_and_search(): void {
		$template = file_get_contents( dirname( __DIR__, 3 ) . '/inc/admin/views/addons.php' );

		$this->assertIsString( $template );
		$this->assertStringContainsString( 'class="lp-addons-toolbar"', $template );
		$this->assertStringContainsString( 'class="lp-addons-filter__item', $template );
		$this->assertStringContainsString( 'aria-pressed=', $template );
		$this->assertStringContainsString( 'type="search"', $template );
		$this->assertStringContainsString( 'lp-addons-search__icon lp-icon-search', $template );
		$this->assertStringContainsString( 'class="screen-reader-text"', $template );
	}

	#[Test]
	public function toolbar_styles_reuse_course_builder_tokens(): void {
		$stylesheet = file_get_contents( dirname( __DIR__, 3 ) . '/assets/src/scss/admin/_addons.scss' );

		$this->assertIsString( $stylesheet );
		$this->assertStringContainsString( '@import "../course-builder/variables";', $stylesheet );
		$this->assertStringContainsString( '.lp-addons-toolbar', $stylesheet );
		$this->assertStringContainsString( '$cb-primary', $stylesheet );
		$this->assertStringContainsString( '$cb-focus-ring', $stylesheet );
	}

	#[Test]
	public function addon_script_uses_the_shared_ready_handler_and_releases_failed_actions(): void {
		$script = file_get_contents( dirname( __DIR__, 3 ) . '/assets/src/js/admin/addons.js' );

		$this->assertIsString( $script );
		$this->assertStringContainsString( "import * as lpUtils from 'lpAssetsJsPath/utils.js';", $script );
		$this->assertStringContainsString( 'lpUtils.lpOnElementReady(', $script );
		$this->assertStringContainsString( 'releaseHandling( addonSlug );', $script );
	}

	#[Test]
	public function addon_prices_use_regular_and_sale_price_fields(): void {
		$data     = json_decode( file_get_contents( dirname( __DIR__, 3 ) . '/inc/admin/views/addons/addons-data.json' ) );
		$template = file_get_contents( dirname( __DIR__, 3 ) . '/inc/admin/views/addons.php' );

		$this->assertIsObject( $data );
		foreach ( $data as $addon ) {
			$this->assertObjectHasProperty( 'regular_price', $addon );
			$this->assertObjectHasProperty( 'sale_price', $addon );
			$this->assertObjectNotHasProperty( 'price', $addon );
			$this->assertObjectNotHasProperty( 'old_price', $addon );
		}

		$this->assertStringContainsString( '$addon->regular_price', $template );
		$this->assertStringContainsString( '$addon->sale_price', $template );
		$this->assertStringContainsString( 'lp-addon-item__price-regular', $template );
		$this->assertStringContainsString( 'lp-addon-item__price-sale', $template );
		$this->assertStringNotContainsString( 'lp-addon-item__price-current', $template );
		$this->assertStringNotContainsString( 'lp-addon-item__price-old', $template );
	}
}
