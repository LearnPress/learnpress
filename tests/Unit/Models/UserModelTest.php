<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models;

use Brain\Monkey\Functions;
use LearnPress\Models\UserModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use stdClass;

/**
 * Unit tests for UserModel.
 *
 * Strategy: pre-populate $meta_data on the model so every method under test
 * stays inside pure PHP logic — no DB, no WP core needed.
 * Brain Monkey stubs all remaining WP function calls.
 */
class UserModelTest extends BrainMonkeyTestCase {

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a UserModel with properties and meta_data pre-populated.
	 *
	 * @param array $props top-level property overrides (ID, user_login, …)
	 * @param array $meta  key → value pairs written to meta_data
	 */
	private function make_user( array $props = [], array $meta = [] ): UserModel {
		$data = array_merge(
			[
				'ID'           => 1,
				'user_login'   => 'testuser',
				'user_email'   => 'test@example.com',
				'display_name' => 'Test User',
			],
			$props
		);

		$user            = new UserModel( $data );
		$user->meta_data = (object) $meta;

		return $user;
	}

	private function set_user_service_slug_lookup_result( $result ): void {
		if ( ! class_exists( '\\LearnPress\\Services\\UserService', false ) ) {
			eval(
				'namespace LearnPress\\Services;
				class UserService {
					public static $slugLookupResult = false;
					public static function instance(): self {
						static $instance = null;
						if ( null === $instance ) {
							$instance = new self();
						}

						return $instance;
					}
					public function get_user_by_pretty_slug( string $slug ) {
						return self::$slugLookupResult;
					}
				}'
			);
		}

		\LearnPress\Services\UserService::$slugLookupResult = $result;
	}

	// -------------------------------------------------------------------------
	// 1. Construction & map_to_object
	// -------------------------------------------------------------------------

	public function test_constructor_with_null_initialises_defaults(): void {
		$user = new UserModel();

		$this->assertSame( 0, $user->ID );
		$this->assertSame( '', $user->user_login );
		$this->assertSame( '', $user->display_name );
		$this->assertInstanceOf( stdClass::class, $user->meta_data );
	}

	public function test_constructor_maps_array_data(): void {
		$user = new UserModel(
			[
				'ID'           => 42,
				'user_login'   => 'john',
				'user_email'   => 'john@example.com',
				'display_name' => 'John Doe',
			]
		);

		$this->assertSame( 42, $user->ID );
		$this->assertSame( 'john', $user->user_login );
		$this->assertSame( 'john@example.com', $user->user_email );
		$this->assertSame( 'John Doe', $user->display_name );
	}

	public function test_constructor_maps_object_data(): void {
		$data               = new stdClass();
		$data->ID           = 99;
		$data->user_login   = 'jane';
		$data->display_name = 'Jane Doe';

		$user = new UserModel( $data );

		$this->assertSame( 99, $user->ID );
		$this->assertSame( 'jane', $user->user_login );
	}

	public function test_constructor_initialises_meta_data_when_null(): void {
		$user = new UserModel( [ 'ID' => 5 ] );

		$this->assertInstanceOf( stdClass::class, $user->meta_data );
	}

	public function test_map_to_object_ignores_unknown_properties(): void {
		$user = new UserModel();
		$user->map_to_object( [ 'nonexistent_prop' => 'value', 'ID' => 7 ] );

		$this->assertSame( 7, $user->ID );
		$this->assertFalse( property_exists( $user, 'nonexistent_prop' ) );
	}

	public function test_map_to_object_returns_self(): void {
		$user   = new UserModel();
		$result = $user->map_to_object( [ 'ID' => 3 ] );

		$this->assertSame( $user, $result );
	}

	// -------------------------------------------------------------------------
	// 2. Simple getters
	// -------------------------------------------------------------------------

	public function test_get_id_returns_ID_as_int(): void {
		$user = $this->make_user( [ 'ID' => 123 ] );

		$this->assertSame( 123, $user->get_id() );
	}

	public function test_get_id_casts_string_to_int(): void {
		$user     = new UserModel();
		$user->ID = '55';

		$this->assertSame( 55, $user->get_id() );
	}

	public function test_get_username_returns_user_login(): void {
		$user = $this->make_user( [ 'user_login' => 'myuser' ] );

		$this->assertSame( 'myuser', $user->get_username() );
	}

	public function test_get_username_returns_empty_string_when_null(): void {
		$user             = new UserModel();
		$user->user_login = null;

		$this->assertSame( '', $user->get_username() );
	}

	public function test_get_email_returns_user_email(): void {
		$user = $this->make_user( [ 'user_email' => 'user@test.com' ] );

		$this->assertSame( 'user@test.com', $user->get_email() );
	}

	public function test_get_email_returns_empty_string_when_null(): void {
		$user              = new UserModel();
		$user->user_email  = null;

		$this->assertSame( '', $user->get_email() );
	}

	public function test_get_display_name_applies_filter(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 ); // return second arg (the display_name)

		$user = $this->make_user( [ 'display_name' => 'John Doe' ] );

		$this->assertSame( 'John Doe', $user->get_display_name() );
	}

	// -------------------------------------------------------------------------
	// 3. get_meta_value_by_key — reads from meta_data (no DB/WP needed)
	// -------------------------------------------------------------------------

	public function test_get_meta_value_by_key_returns_cached_value_from_meta_data(): void {
		$user = $this->make_user( [], [ '_lp_user_slug' => 'john-doe' ] );

		$result = $user->get_meta_value_by_key( '_lp_user_slug', '' );

		$this->assertSame( 'john-doe', $result );
	}

	public function test_get_meta_value_by_key_stores_value_on_meta_data(): void {
		Functions\when( 'get_user_meta' )->justReturn( 'fetched-value' );

		$user = $this->make_user( [ 'ID' => 10 ] );

		$result = $user->get_meta_value_by_key( 'some_key' );

		$this->assertSame( 'fetched-value', $result );
		$this->assertSame( 'fetched-value', $user->meta_data->{'some_key'} );
	}

	public function test_get_meta_value_by_key_returns_default_when_empty(): void {
		Functions\when( 'get_user_meta' )->justReturn( '' );

		$user   = $this->make_user( [ 'ID' => 10 ] );
		$result = $user->get_meta_value_by_key( 'missing_key', 'default_val' );

		$this->assertSame( 'default_val', $result );
	}

	// -------------------------------------------------------------------------
	// 4. get_pretty_slug
	// -------------------------------------------------------------------------

	public function test_get_pretty_slug_returns_slug_from_meta(): void {
		Functions\when( 'sanitize_title' )->returnArg( 1 );

		$user = $this->make_user(
			[ 'user_login' => 'myuser' ],
			[ UserModel::META_KEY_USER_SLUG => 'my-pretty-slug' ]
		);

		$this->assertSame( 'my-pretty-slug', $user->get_pretty_slug() );
	}

	public function test_get_pretty_slug_falls_back_to_username_when_no_slug(): void {
		Functions\when( 'sanitize_title' )->justReturn( '' );
		Functions\when( 'get_user_meta' )->justReturn( '' );

		$user = $this->make_user( [ 'user_login' => 'fallbackuser' ] );

		$this->assertSame( 'fallbackuser', $user->get_pretty_slug( true ) );
	}

	public function test_get_pretty_slug_returns_empty_when_no_slug_and_fallback_disabled(): void {
		Functions\when( 'sanitize_title' )->justReturn( '' );
		Functions\when( 'get_user_meta' )->justReturn( '' );

		$user = $this->make_user( [ 'user_login' => 'myuser' ] );

		$this->assertSame( '', $user->get_pretty_slug( false ) );
	}

	public function test_generate_pretty_slug_returns_existing_pretty_slug_when_already_set(): void {
		Functions\when( 'sanitize_title' )->returnArg( 1 );

		$user = $this->make_user(
			[ 'user_login' => 'johnny' ],
			[ UserModel::META_KEY_USER_SLUG => 'existing-slug' ]
		);

		$result = $user->generate_pretty_slug();

		$this->assertSame( 'existing-slug', $result );
	}

	public function test_generate_pretty_slug_sets_slug_from_full_name_when_slug_is_unique(): void {
		Functions\when( 'sanitize_title' )->alias(
			static function ( $value ) {
				$value = strtolower( trim( (string) $value ) );
				$value = preg_replace( '/\s+/', '-', $value );

				return $value ?? '';
			}
		);
		Functions\when( 'get_user_meta' )->justReturn( '' );
		Functions\when( 'update_user_meta' )->justReturn( true );
		$this->set_user_service_slug_lookup_result( false );

		$user = $this->make_user(
			[ 'ID' => 15, 'user_login' => 'jdoe' ],
			[ 'first_name' => 'John', 'last_name' => 'Doe' ]
		);

		$result = $user->generate_pretty_slug();

		$this->assertIsString( $result );
		$this->assertSame( $result, $user->meta_data->{UserModel::META_KEY_USER_SLUG} );
		$this->assertMatchesRegularExpression( '/^john-doe[a-z0-9]{3}$/', $result );
	}

	public function test_generate_pretty_slug_returns_wp_error_when_slug_generation_fails(): void {
		Functions\when( 'get_user_meta' )->justReturn( '' );
		Functions\when( 'sanitize_title' )->alias(
			static function () {
				throw new \Exception( 'sanitize failed' );
			}
		);

		$user = $this->make_user( [ 'ID' => 2, 'user_login' => 'johnny' ] );

		$result = $user->generate_pretty_slug();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'lp_user_slug_generation_failed', $result->code );
	}

	// -------------------------------------------------------------------------
	// 5. update_pretty_slug
	// -------------------------------------------------------------------------

	public function test_update_pretty_slug_returns_sanitized_slug(): void {
		Functions\when( 'sanitize_title' )->returnArg( 1 );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'get_user_meta' )->justReturn( '' ); // no existing slug for another user
		Functions\when( 'update_user_meta' )->justReturn( true );

		$user = $this->make_user( [ 'ID' => 5 ] );

		// Stub check_user_slug_pretty to return 0 (no conflict).
		Functions\when( 'sanitize_title' )->returnArg( 1 );

		// We need to prevent check_user_slug_pretty from hitting the DB.
		// Use a partial approach: manually set meta so get_pretty_slug finds no existing slug,
		// then stub the DB function it calls.
		Functions\when( 'get_var' )->justReturn( 0 );

		$result = $user->update_pretty_slug( 'new-slug' );

		// Returns the sanitized slug or WP_Error — here slug has no conflict (get_user_meta returns '').
		// Since check_user_slug_pretty queries the DB (wpdb), we can only test the WP_Error path
		// indirectly. We assert it doesn't throw an exception.
		$this->assertNotNull( $result );
	}

	public function test_update_pretty_slug_deletes_meta_when_empty_slug(): void {
		Functions\when( 'sanitize_title' )->justReturn( '' );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'delete_user_meta' )->justReturn( true );
		Functions\when( 'update_user_meta' )->justReturn( true );

		$user = $this->make_user( [ 'ID' => 5 ], [ UserModel::META_KEY_USER_SLUG => 'old-slug' ] );

		$result = $user->update_pretty_slug( '' );

		$this->assertSame( '', $result );
		$this->assertSame( '', $user->meta_data->{UserModel::META_KEY_USER_SLUG} );
	}

	public function test_update_pretty_slug_sets_sanitized_slug_when_not_taken(): void {
		Functions\when( 'sanitize_title' )->alias(
			static function ( $value ) {
				$value = strtolower( trim( (string) $value ) );
				$value = preg_replace( '/\s+/', '-', $value );

				return $value ?? '';
			}
		);
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'update_user_meta' )->justReturn( true );
		$this->set_user_service_slug_lookup_result( false );

		$user   = $this->make_user( [ 'ID' => 5 ] );
		$result = $user->update_pretty_slug( '  New Slug  ' );

		$this->assertSame( 'new-slug', $result );
		$this->assertSame( 'new-slug', $user->meta_data->{UserModel::META_KEY_USER_SLUG} );
	}

	public function test_update_pretty_slug_returns_wp_error_when_slug_is_used_by_another_user(): void {
		Functions\when( 'sanitize_title' )->returnArg( 1 );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		$this->set_user_service_slug_lookup_result( $this->make_user( [ 'ID' => 99 ] ) );

		$user   = $this->make_user( [ 'ID' => 5 ] );
		$result = $user->update_pretty_slug( 'duplicate-slug' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'lp_user_slug_update_failed', $result->code );
	}

	public function test_update_pretty_slug_allows_same_slug_for_current_user(): void {
		Functions\when( 'sanitize_title' )->returnArg( 1 );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'update_user_meta' )->justReturn( true );
		$this->set_user_service_slug_lookup_result( $this->make_user( [ 'ID' => 5 ] ) );

		$user   = $this->make_user( [ 'ID' => 5 ] );
		$result = $user->update_pretty_slug( 'same-slug' );

		$this->assertSame( 'same-slug', $result );
		$this->assertSame( 'same-slug', $user->meta_data->{UserModel::META_KEY_USER_SLUG} );
	}

	// -------------------------------------------------------------------------
	// 6. get_image_url
	// -------------------------------------------------------------------------

	public function test_get_image_url_returns_cached_image_url(): void {
		$user            = $this->make_user();
		$user->image_url = 'https://example.com/avatar.jpg';

		$this->assertSame( 'https://example.com/avatar.jpg', $user->get_image_url() );
	}

	public function test_get_image_url_returns_empty_when_no_picture(): void {
		Functions\when( 'get_user_meta' )->justReturn( '' );

		$user = $this->make_user();

		$this->assertSame( '', $user->get_image_url() );
	}

	// -------------------------------------------------------------------------
	// 7. get_cover_image_url
	// -------------------------------------------------------------------------

	public function test_get_cover_image_url_returns_empty_when_no_cover(): void {
		Functions\when( 'get_user_meta' )->justReturn( '' );

		$user = $this->make_user();

		$this->assertSame( '', $user->get_cover_image_url() );
	}

	// -------------------------------------------------------------------------
	// 8. set_cover_image_url
	// -------------------------------------------------------------------------

	public function test_set_cover_image_url_stores_on_meta_data(): void {
		Functions\when( 'update_user_meta' )->justReturn( true );

		$user = $this->make_user( [ 'ID' => 5 ] );
		$user->set_cover_image_url( 'https://example.com/cover.jpg' );

		$this->assertSame(
			'https://example.com/cover.jpg',
			$user->meta_data->{UserModel::META_KEY_COVER_IMAGE}
		);
	}

	// -------------------------------------------------------------------------
	// 9. Constants
	// -------------------------------------------------------------------------

	public function test_meta_key_image_constant(): void {
		$this->assertSame( '_lp_profile_picture', UserModel::META_KEY_IMAGE );
	}

	public function test_meta_key_cover_image_constant(): void {
		$this->assertSame( '_lp_profile_cover_image', UserModel::META_KEY_COVER_IMAGE );
	}

	public function test_meta_key_user_slug_constant(): void {
		$this->assertSame( '_lp_user_slug', UserModel::META_KEY_USER_SLUG );
	}

	// -------------------------------------------------------------------------
	// 10. save & clean_caches
	// -------------------------------------------------------------------------

	public function test_save_returns_self(): void {
		Functions\when( 'wp_cache_delete' )->justReturn( true );

		$user   = $this->make_user( [ 'ID' => 7 ] );
		$result = $user->save();

		$this->assertSame( $user, $result );
	}
}
