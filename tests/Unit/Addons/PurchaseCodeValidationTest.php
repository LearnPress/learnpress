<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Addons;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

/**
 * Tests for validating an add-on purchase code before it is stored.
 */
class PurchaseCodeValidationTest extends BrainMonkeyTestCase {
	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function bundled_addons_data_is_valid_json(): void {
		defined( 'ABSPATH' ) || define( 'ABSPATH', '/fake/wp/' );
		defined( 'LP_PLUGIN_URL' ) || define( 'LP_PLUGIN_URL', 'https://example.test/learnpress/' );
		defined( 'LP_PLUGIN_PATH' ) || define( 'LP_PLUGIN_PATH', dirname( __DIR__, 3 ) . '/' );

		require_once LP_PLUGIN_PATH . 'inc/class-lp-manager-addons.php';
		$manager = ( new ReflectionClass( \LP_Manager_Addons::class ) )->newInstanceWithoutConstructor();
		$data    = json_decode( $manager->get_addons_data() );

		$this->assertNotNull( $data );
		$this->assertSame( JSON_ERROR_NONE, json_last_error() );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function invalid_purchase_code_is_not_saved(): void {
		defined( 'ABSPATH' ) || define( 'ABSPATH', '/fake/wp/' );
		defined( 'LP_PLUGIN_URL' ) || define( 'LP_PLUGIN_URL', 'https://example.test/learnpress/' );
		defined( 'LP_PLUGIN_PATH' ) || define( 'LP_PLUGIN_PATH', dirname( __DIR__, 3 ) . '/' );

		Functions\when( 'wp_remote_post' )->justReturn(
			array(
				'body' => '{}',
			)
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'site_url' )->justReturn( 'https://example.test/' );
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static fn( array $response ): string => $response['body']
		);

		eval(
			'namespace { class LP_Settings {
				public static array $updates = array();
				public static function get_option( $key, $default = array() ) { return $default; }
				public static function update_option( $key, $value ) { self::$updates[] = array( $key, $value ); }
			} }'
		);
		eval(
			'namespace { class LP_Helper {
				public static function json_decode( $value ) { return json_decode( $value ); }
			} }'
		);

		require_once dirname( __DIR__, 3 ) . '/inc/class-lp-manager-addons.php';
		$manager = ( new ReflectionClass( \LP_Manager_Addons::class ) )->newInstanceWithoutConstructor();

		try {
			$manager->validate_and_save_purchase_code( 'learnpress-membership', 'invalid-code' );
			$this->fail( 'An invalid purchase code must be rejected.' );
		} catch ( Exception $e ) {
			$this->assertStringContainsString( 'invalid', strtolower( $e->getMessage() ) );
		}

		$this->assertSame( array(), \LP_Settings::$updates );
	}
}
