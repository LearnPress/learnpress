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
}
