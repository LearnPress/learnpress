<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Contract tests for the Themes page header and filter toolbar.
 */
class ThemesToolbarTest extends TestCase {

	#[Test]
	public function themes_header_has_no_subtitle_and_uses_the_shared_admin_renderer(): void {
		$template = file_get_contents( dirname( __DIR__, 3 ) . '/inc/admin/views/themes/html-themes.php' );

		$this->assertStringContainsString( 'AdminTemplate::html_on_wp_admin_screen', $template );
		$this->assertStringContainsString( 'Recommended Themes for LearnPress', $template );
		$this->assertStringNotContainsString( 'lp-themes-page-subtitle', $template );
	}

	#[Test]
	public function themes_header_and_filter_use_the_addons_course_builder_design(): void {
		$variables  = file_get_contents( dirname( __DIR__, 3 ) . '/assets/src/scss/course-builder/_variables.scss' );
		$stylesheet = file_get_contents( dirname( __DIR__, 3 ) . '/assets/src/scss/admin/themes.scss' );

		$this->assertStringContainsString( '$cb-font-4xl:           32px !default;', $variables );
		$this->assertStringContainsString( '> .wp-heading-inline', $stylesheet );
		$this->assertStringContainsString( 'font-size: $cb-font-4xl;', $stylesheet );
		$this->assertStringContainsString( 'padding: $cb-spacing-xl;', $stylesheet );
		$this->assertStringContainsString( 'margin: -$cb-spacing-xs;', $stylesheet );
		$this->assertStringContainsString( 'padding: $cb-spacing-xs;', $stylesheet );
		$this->assertStringContainsString( 'min-height: 49px;', $stylesheet );
		$this->assertStringContainsString( 'background: $cb-primary-bland;', $stylesheet );
		$this->assertStringContainsString( 'border-radius: $cb-radius-lg;', $stylesheet );
		$this->assertStringContainsString( 'overflow-x: auto;', $stylesheet );
	}
}
