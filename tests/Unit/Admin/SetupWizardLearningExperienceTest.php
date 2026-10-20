<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Contract tests for the setup wizard learning experience choices.
 */
class SetupWizardLearningExperienceTest extends TestCase {

	#[Test]
	public function layout_choice_icons_follow_the_checked_radio_color(): void {
		$template = file_get_contents( dirname( __DIR__, 3 ) . '/inc/admin/views/setup/steps/learning-experience.php' );

		$this->assertIsString( $template );
		$this->assertStringContainsString( 'fill="currentColor"', $template );
		$this->assertStringContainsString( 'stroke="currentColor"', $template );
		$this->assertStringNotContainsString( 'stroke="#626262"', $template );
		$this->assertStringNotContainsString( 'fill="#626262"', $template );
		$this->assertStringNotContainsString( 'fill="#7067ED"', $template );
	}

	#[Test]
	public function course_listing_uses_the_supplied_grid_and_list_icons(): void {
		$template   = file_get_contents( dirname( __DIR__, 3 ) . '/inc/admin/views/setup/steps/learning-experience.php' );
		$stylesheet = file_get_contents( dirname( __DIR__, 3 ) . '/assets/src/scss/admin/setup-wizard.scss' );

		$this->assertIsString( $template );
		$this->assertIsString( $stylesheet );
		$this->assertStringContainsString( 'M10.5 15.1667', $template );
		$this->assertStringContainsString( 'M5.25 20.4168', $template );
		$this->assertSame( 2, substr_count( $template, 'lp-setup-choice__svg--solid' ) );
		$this->assertStringContainsString( '.lp-setup-choice__svg--solid', $stylesheet );
		$this->assertStringContainsString( 'fill: currentColor;', $stylesheet );
		$this->assertStringContainsString( 'stroke: none;', $stylesheet );
		$this->assertStringNotContainsString( '<span class="lp-icon-th"></span>', $template );
		$this->assertStringNotContainsString( '<span class="lp-icon-th-list"></span>', $template );
	}
}
