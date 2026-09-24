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
	public function page_header_uses_the_shared_wp_admin_screen_renderer(): void {
		$renderer   = file_get_contents( dirname( __DIR__, 3 ) . '/inc/TemplateHooks/Admin/AdminAddonsPage.php' );
		$stylesheet = file_get_contents( dirname( __DIR__, 3 ) . '/assets/src/scss/admin/_addons.scss' );

		$this->assertStringContainsString( 'AdminTemplate::html_on_wp_admin_screen', $renderer );
		$this->assertStringContainsString( 'lp-addons-page-subtitle', $renderer );
		$this->assertStringContainsString( 'LearnPress Add-ons', $renderer );
		$this->assertStringContainsString( 'Discover high-performance Premium & Education themes optimized 100% for LearnPress LMS.', $renderer );
		$this->assertStringNotContainsString( "'note-theme'", $renderer );
		$this->assertStringNotContainsString( "'note-addon'", $renderer );
		$this->assertStringNotContainsString( 'lp-addons-page-header', $renderer );
		$this->assertStringContainsString( '.learn-press-addons', $stylesheet );
		$this->assertStringContainsString( '.lp-addons-page-subtitle', $stylesheet );
		$this->assertStringContainsString( '$cb-font-4xl', $stylesheet );
	}

	#[Test]
	public function toolbar_uses_accessible_filter_controls_and_search(): void {
		$template = file_get_contents( dirname( __DIR__, 3 ) . '/inc/TemplateHooks/Admin/AdminAddonsPage.php' );

		$this->assertIsString( $template );
		$this->assertStringContainsString( 'lp-addons-toolbar', $template );
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
	public function toolbar_and_categories_follow_the_course_builder_filter_design(): void {
		$stylesheet = file_get_contents( dirname( __DIR__, 3 ) . '/assets/src/scss/admin/_addons.scss' );

		$this->assertStringContainsString( 'background: $cb-primary-bland;', $stylesheet );
		$this->assertStringContainsString( 'border-radius: $cb-radius-lg;', $stylesheet );
		$this->assertStringContainsString( 'border-color: transparent;', $stylesheet );
		$this->assertStringContainsString( 'color: $cb-text-placeholder;', $stylesheet );
		$this->assertStringContainsString( 'font-size: $cb-font-lg;', $stylesheet );
		$this->assertStringContainsString( 'min-height: 49px;', $stylesheet );
		$this->assertStringContainsString( 'min-height: 42px;', $stylesheet );
		$this->assertStringContainsString( 'overflow-x: auto;', $stylesheet );
		$this->assertStringContainsString( '-webkit-overflow-scrolling: touch;', $stylesheet );
		$this->assertStringContainsString( 'margin: -$cb-spacing-xs;', $stylesheet );
		$this->assertStringContainsString( 'padding: $cb-spacing-xs;', $stylesheet );
	}

	#[Test]
	public function addon_script_uses_the_shared_ready_handler_and_releases_failed_actions(): void {
		$script = file_get_contents( dirname( __DIR__, 3 ) . '/assets/src/js/admin/addons.js' );

		$this->assertIsString( $script );
		$this->assertStringContainsString( "import * as lpUtils from 'lpAssetsJsPath/utils.js';", $script );
		$this->assertStringContainsString( 'lpUtils.lpOnElementReady(', $script );
		$this->assertStringContainsString( 'releaseHandling();', $script );
	}

	#[Test]
	public function addon_prices_use_regular_and_sale_price_fields(): void {
		$data     = json_decode( file_get_contents( dirname( __DIR__, 3 ) . '/config/addons-data.json' ) );
		$template = file_get_contents( dirname( __DIR__, 3 ) . '/inc/TemplateHooks/Admin/AdminAddonsPage.php' );
		$styles   = file_get_contents( dirname( __DIR__, 3 ) . '/assets/src/scss/admin/_addons.scss' );

		$this->assertIsObject( $data );
		$this->assertIsString( $styles );
		foreach ( $data as $addon ) {
			$this->assertObjectHasProperty( 'regular_price', $addon );
			$this->assertObjectHasProperty( 'sale_price', $addon );
			$this->assertObjectNotHasProperty( 'price', $addon );
			$this->assertObjectNotHasProperty( 'old_price', $addon );
		}

		$this->assertStringContainsString( '$addon->regular_price', $template );
		$this->assertStringContainsString( '$addon->sale_price', $template );
		$this->assertStringContainsString( 'lp-addon-item__price-regular', $template );
		$this->assertStringContainsString( 'lp-addon-item__price-regular--current', $template );
		$this->assertStringContainsString( 'lp-addon-item__price-regular--free', $template );
		$this->assertStringContainsString( 'lp-addon-item__price-sale', $template );
		$this->assertStringContainsString( '&--current', $styles );
		$this->assertStringContainsString( '&--free', $styles );
		$this->assertStringNotContainsString( '&:not(del)', $styles );
		$this->assertStringNotContainsString( 'lp-addon-item__price-current', $template );
		$this->assertStringNotContainsString( 'lp-addon-item__price-old', $template );
	}

	#[Test]
	public function addon_categories_are_non_empty_arrays_using_the_thimpress_taxonomy(): void {
		$data               = json_decode( file_get_contents( dirname( __DIR__, 3 ) . '/config/addons-data.json' ) );
		$allowed_categories = array(
			'Create Course',
			'Engagement',
			'LearnPress',
			'Manage Course',
			'Marketing Optimization',
			'Monetize Course',
			'Payment',
		);

		$this->assertIsObject( $data );
		$category_counts = array_fill_keys( $allowed_categories, 0 );
		foreach ( $data as $slug => $addon ) {
			$this->assertIsArray( $addon->category, sprintf( '%s must use a category array.', $slug ) );
			$this->assertNotEmpty( $addon->category, sprintf( '%s must have at least one category.', $slug ) );
			$this->assertSame( $addon->category, array_values( array_unique( $addon->category ) ) );
			$this->assertSame( array(), array_diff( $addon->category, $allowed_categories ) );

			foreach ( $addon->category as $category ) {
				++$category_counts[ $category ];
			}
		}

		$this->assertSame(
			array(
				'Create Course'          => 9,
				'Engagement'             => 7,
				'LearnPress'             => 35,
				'Manage Course'          => 13,
				'Marketing Optimization' => 5,
				'Monetize Course'        => 13,
				'Payment'                => 10,
			),
			$category_counts
		);

		$this->assertSame(
			array( 'Create Course', 'Engagement', 'LearnPress' ),
			$data->{'learnpress-exams'}->category
		);
		$this->assertSame(
			array( 'LearnPress', 'Monetize Course', 'Payment' ),
			$data->{'learnpress-klarna-payment'}->category
		);
	}

	#[Test]
	public function addons_follow_the_thimpress_catalog_order(): void {
		$data = json_decode( file_get_contents( dirname( __DIR__, 3 ) . '/config/addons-data.json' ) );

		$this->assertSame(
			array(
				'learnpress-woo-payment',
				'learnpress-stripe',
				'learnpress-membership',
				'learnpress-exams',
				'learnpress-certificates',
				'learnpress-gradebook',
				'learnpress-upsell',
				'learnpress-assignments',
				'learnpress-paid-membership-pro',
				'learnpress-frontend-editor',
				'learnpress-content-drip',
				'learnpress-live',
				'learnpress-announcements',
				'learnpress-wpml',
				'learnpress-co-instructor',
				'learnpress-collections',
				'learnpress-commission',
				'learnpress-sorting-choice',
				'learnpress-2checkout-payment',
				'learnpress-authorizenet-payment',
				'learnpress-chat-room',
				'learnpress-klarna-payment',
				'learnpress-razorpay-payment',
				'learnpress-instamojo-payment',
				'learnpress-paystack-payment',
				'learnpress-qpay',
				'learnpress-payu-payment',
				'learnpress-interactive',
				'learnpress-h5p',
				'learnpress-random-quiz',
				'learnpress-mycred',
				'learnpress-scorm',
				'learnpress-students-list',
				'learnpress-coming-soon-courses',
				'learnpress-prerequisites-courses',
				'learnpress-course-review',
				'learnpress-wishlist',
				'learnpress-import-export',
				'learnpress-buddypress',
				'learnpress-bbpress',
				'learnpress-mobile-app',
				'learnpress-sepay-payment',
			),
			array_keys( get_object_vars( $data ) )
		);
	}

	#[Test]
	public function addons_contain_the_thimpress_discount_badges(): void {
		$data = json_decode( file_get_contents( dirname( __DIR__, 3 ) . '/config/addons-data.json' ) );
		$badges = array();

		foreach ( $data as $slug => $addon ) {
			$this->assertObjectHasProperty( 'badge', $addon, sprintf( '%s must contain a badge field.', $slug ) );

			if ( '' !== $addon->badge ) {
				$badges[ $slug ] = $addon->badge;
			}
		}

		$this->assertSame(
			array(
				'learnpress-membership'      => '20% off',
				'learnpress-exams'           => '50% off',
				'learnpress-upsell'          => '40% off',
				'learnpress-frontend-editor' => '20% off',
				'learnpress-chat-room'       => '45% off',
				'learnpress-klarna-payment'  => '45% off',
				'learnpress-paystack-payment' => '45% off',
				'learnpress-qpay'            => '45% off',
				'learnpress-payu-payment'    => '45% off',
				'learnpress-interactive'     => '35% off',
			),
			$badges
		);
	}

	#[Test]
	public function addon_badge_is_rendered_beside_the_wrapping_title(): void {
		$template   = file_get_contents( dirname( __DIR__, 3 ) . '/inc/TemplateHooks/Admin/AdminAddonsPage.php' );
		$stylesheet = file_get_contents( dirname( __DIR__, 3 ) . '/assets/src/scss/admin/_addons.scss' );

		$this->assertStringContainsString( 'lp-addon-item__heading', $template );
		$this->assertStringContainsString( 'lp-addon-item__badge', $template );
		$this->assertStringContainsString( '! empty( $addon->badge )', $template );
		$this->assertStringContainsString( '&__heading', $stylesheet );
		$this->assertStringContainsString( '&__badge', $stylesheet );
		$this->assertStringContainsString( 'flex-shrink: 0;', $stylesheet );
		$this->assertStringContainsString( 'color: $cb-bg;', $stylesheet );
		$this->assertStringContainsString( 'background: $cb-price-red;', $stylesheet );
	}
}
