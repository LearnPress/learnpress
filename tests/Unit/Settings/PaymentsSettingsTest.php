<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Settings;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for payment settings sections.
 */
class PaymentsSettingsTest extends BrainMonkeyTestCase {

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function refund_settings_use_their_own_section_and_keep_flat_option_names(): void {
		defined( 'LP_PLUGIN_PATH' ) || define( 'LP_PLUGIN_PATH', dirname( __DIR__, 3 ) );

		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias(
			static fn( $tag, $value ) => $value
		);
		Functions\when( 'learn_press_get_checkout_url' )->justReturn( 'https://example.test/checkout/' );

		eval(
			'class LP_Settings {
				public static function instance() {
					return new self();
				}
				public function get( $key, $default = null ) {
					return $default;
				}
			}'
		);
		eval(
			'class LP_Test_Settings_Menu {
				public static $section = "refund";
				public function get_active_tab() {
					return "payments";
				}
				public function get_active_section() {
					return self::$section;
				}
				public function get_sections() {
					return [];
				}
			}'
		);
		eval(
			'class LP_Admin_Menu {
				public static function instance() {
					return new self();
				}
				public function get_menu_items() {
					return [ "settings" => new LP_Test_Settings_Menu() ];
				}
			}'
		);
		eval(
			'class LP_Gateways {
				public static function instance() {
					return new self();
				}
				public function get_gateways() {
					return [];
				}
			}'
		);

		require_once dirname( __DIR__, 3 ) . '/inc/abstract-settings.php';
		require_once dirname( __DIR__, 3 ) . '/inc/settings/abstract-settings-page.php';
		$settings = require dirname( __DIR__, 3 ) . '/inc/admin/settings/class-lp-settings-payments.php';

		$sections = array_keys( $settings->get_sections() );
		$this->assertSame( [ 'general', 'refund' ], $sections );

		$general_ids = array_column( $settings->get_settings_general(), 'id' );
		$this->assertNotContains( 'enable_refund_requests', $general_ids );

		$refund_settings = $settings->get_settings_refund();
		$refund_defaults = array_column( $refund_settings, 'default', 'id' );

		$this->assertSame(
			[
				'enable_refund_requests'  => 'no',
				'auto_refund'             => 'no',
				'refund_time_limit'       => 30,
				'require_refund_reason'   => 'no',
				'allow_resend_after_rejected' => 'no',
				'refund_max_completion'   => 0,
			],
			$refund_defaults
		);
		$this->assertSame(
			'learn_press_enable_refund_requests',
			$settings->get_admin_field_name( 'enable_refund_requests' )
		);

		\LP_Test_Settings_Menu::$section = 'paypal';
		$this->assertSame( 'learn_press_paypal[enable]', $settings->get_admin_field_name( '[enable]' ) );
	}
}
