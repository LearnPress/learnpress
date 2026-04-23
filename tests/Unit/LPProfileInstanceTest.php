<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;

class LPProfileInstanceTest extends BrainMonkeyTestCase {

	private function define_profile_dependencies(): void {
		if ( ! class_exists( '\\LearnPress\\Models\\UserModel', false ) ) {
			eval(
				'namespace LearnPress\\Models;
				class UserModel {
					const ROLE_ADMINISTRATOR = "administrator";
					public static array $findMap = [];
					private int $id;
					private string $prettySlug;
					public function __construct( int $id = 0, string $prettySlug = "" ) {
						$this->id = $id;
						$this->prettySlug = $prettySlug;
					}
					public static function find( int $userId, bool $checkCache = false ) {
						return self::$findMap[ $userId ] ?? null;
					}
					public function get_id(): int {
						return $this->id;
					}
					public function get_slug_link(): string {
						return $this->prettySlug;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\Services\\UserService', false ) ) {
			eval(
				'namespace LearnPress\\Services;
				class UserService {
					public static ?self $instance = null;
					public $userByPrettySlug = null;
					public static function instance(): self {
						if ( ! self::$instance ) {
							self::$instance = new self();
						}
						return self::$instance;
					}
					public function get_user_by_pretty_slug( string $slug ) {
						return $this->userByPrettySlug;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LP_Page_Controller', false ) ) {
			eval(
				'class LP_Page_Controller {
					public static bool $isProfilePage = true;
					public static function page_is( string $page ): bool {
						return self::$isProfilePage;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LP_User_CURD', false ) ) {
			eval( 'class LP_User_CURD {}' );
		}

		if ( ! class_exists( '\\LP_User', false ) ) {
			eval(
				'class LP_User {
					private int $id;
					public function __construct( int $id = 0 ) {
						$this->id = $id;
					}
					public function get_id(): int {
						return $this->id;
					}
					public function is_guest(): bool {
						return $this->id <= 0;
					}
					public function get_data( string $field ) {
						if ( strtolower( $field ) === "id" ) {
							return $this->id;
						}
						return "";
					}
				}'
			);
		}
	}

	private function load_profile_class(): void {
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', '/fake/wp/' );
		}

		$profile_file = dirname( __DIR__, 2 ) . '/inc/user/class-lp-profile.php';
		set_include_path( dirname( $profile_file ) . PATH_SEPARATOR . get_include_path() );
		require_once $profile_file;
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function resolves_profile_user_by_slug_when_target_user_has_no_pretty_slug(): void {
		$this->define_profile_dependencies();

		$target_user = (object) [ 'ID' => 55 ];

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'learn_press_get_current_user' )->justReturn( new \LP_User( 10 ) );
		Functions\when( 'learn_press_get_user' )->alias( static fn( $id ) => new \LP_User( (int) $id ) );
		Functions\when( 'get_current_user_id' )->justReturn( 10 );
		Functions\when( 'get_query_var' )->alias( static fn( $key ) => $key === 'user' ? 'target-slug' : '' );
		Functions\when( 'get_user_by' )->alias( static fn( $field, $value ) => $field === 'slug' && $value === 'target-slug' ? $target_user : false );
		Functions\when( 'current_user_can' )->justReturn( false );

		\LearnPress\Models\UserModel::$findMap = [
			10 => new \LearnPress\Models\UserModel( 10, 'current-user' ),
			55 => new \LearnPress\Models\UserModel( 55, '' ),
		];

		\LearnPress\Services\UserService::instance()->userByPrettySlug = null;

		$this->load_profile_class();

		$profile = \LP_Profile::instance();

		$this->assertSame( 55, $profile->get_user_data( 'id' ) );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function keeps_guest_profile_when_target_user_has_pretty_slug_and_viewer_is_not_admin_or_owner(): void {
		$this->define_profile_dependencies();

		$target_user = (object) [ 'ID' => 77 ];

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'learn_press_get_current_user' )->justReturn( new \LP_User( 10 ) );
		Functions\when( 'learn_press_get_user' )->alias( static fn( $id ) => new \LP_User( (int) $id ) );
		Functions\when( 'get_current_user_id' )->justReturn( 10 );
		Functions\when( 'get_query_var' )->alias( static fn( $key ) => $key === 'user' ? 'has-pretty-slug' : '' );
		Functions\when( 'get_user_by' )->alias( static fn( $field, $value ) => $field === 'slug' && $value === 'has-pretty-slug' ? $target_user : false );
		Functions\when( 'current_user_can' )->justReturn( false );

		\LearnPress\Models\UserModel::$findMap = [
			10 => new \LearnPress\Models\UserModel( 10, 'current-user' ),
			77 => new \LearnPress\Models\UserModel( 77, 'public-slug' ),
		];

		\LearnPress\Services\UserService::instance()->userByPrettySlug = null;

		$this->load_profile_class();

		$profile = \LP_Profile::instance();

		$this->assertSame( 0, $profile->get_user_data( 'id' ) );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function resolves_to_current_user_when_viewer_is_admin(): void {
		$this->define_profile_dependencies();

		$target_user = (object) [ 'ID' => 88 ];

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'learn_press_get_current_user' )->justReturn( new \LP_User( 10 ) );
		Functions\when( 'learn_press_get_user' )->alias( static fn( $id ) => new \LP_User( (int) $id ) );
		Functions\when( 'get_current_user_id' )->justReturn( 10 );
		Functions\when( 'get_query_var' )->alias( static fn( $key ) => $key === 'user' ? 'target-admin-view' : '' );
		Functions\when( 'get_user_by' )->alias( static fn( $field, $value ) => $field === 'slug' && $value === 'target-admin-view' ? $target_user : false );
		Functions\when( 'current_user_can' )->justReturn( true );

		\LearnPress\Models\UserModel::$findMap = [
			10 => new \LearnPress\Models\UserModel( 10, 'current-user' ),
			88 => new \LearnPress\Models\UserModel( 88, '' ),
		];

		\LearnPress\Services\UserService::instance()->userByPrettySlug = null;

		$this->load_profile_class();

		$profile = \LP_Profile::instance();

		$this->assertSame( 10, $profile->get_user_data( 'id' ) );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function keeps_guest_profile_when_slug_does_not_match_any_wp_user(): void {
		$this->define_profile_dependencies();

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'learn_press_get_current_user' )->justReturn( new \LP_User( 10 ) );
		Functions\when( 'learn_press_get_user' )->alias( static fn( $id ) => new \LP_User( (int) $id ) );
		Functions\when( 'get_current_user_id' )->justReturn( 10 );
		Functions\when( 'get_query_var' )->alias( static fn( $key ) => $key === 'user' ? 'missing-user' : '' );
		Functions\when( 'get_user_by' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );

		\LearnPress\Models\UserModel::$findMap = [
			10 => new \LearnPress\Models\UserModel( 10, 'current-user' ),
		];

		\LearnPress\Services\UserService::instance()->userByPrettySlug = null;

		$this->load_profile_class();

		$profile = \LP_Profile::instance();

		$this->assertSame( 0, $profile->get_user_data( 'id' ) );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function resolves_profile_user_from_user_service_pretty_slug(): void {
		$this->define_profile_dependencies();

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'learn_press_get_current_user' )->justReturn( new \LP_User( 10 ) );
		Functions\when( 'learn_press_get_user' )->alias( static fn( $id ) => new \LP_User( (int) $id ) );
		Functions\when( 'get_current_user_id' )->justReturn( 10 );
		Functions\when( 'get_query_var' )->alias( static fn( $key ) => $key === 'user' ? 'service-slug' : '' );
		Functions\when( 'get_user_by' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );

		\LearnPress\Models\UserModel::$findMap = [
			10 => new \LearnPress\Models\UserModel( 10, 'current-user' ),
		];

		\LearnPress\Services\UserService::instance()->userByPrettySlug = new \LearnPress\Models\UserModel( 44, 'service-slug' );

		$this->load_profile_class();

		$profile = \LP_Profile::instance();

		$this->assertSame( 44, $profile->get_user_data( 'id' ) );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function falls_back_to_current_user_when_profile_slug_is_empty(): void {
		$this->define_profile_dependencies();

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'learn_press_get_current_user' )->justReturn( new \LP_User( 10 ) );
		Functions\when( 'learn_press_get_user' )->alias( static fn( $id ) => new \LP_User( (int) $id ) );
		Functions\when( 'get_current_user_id' )->justReturn( 10 );
		Functions\when( 'get_query_var' )->justReturn( '' );
		Functions\when( 'get_user_by' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );

		\LearnPress\Models\UserModel::$findMap = [
			10 => new \LearnPress\Models\UserModel( 10, 'current-user' ),
		];

		\LearnPress\Services\UserService::instance()->userByPrettySlug = null;

		$this->load_profile_class();

		$profile = \LP_Profile::instance();

		$this->assertSame( 10, $profile->get_user_data( 'id' ) );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function resolves_to_current_user_when_viewing_own_profile_slug(): void {
		$this->define_profile_dependencies();

		$target_user = (object) [ 'ID' => 10 ];

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'learn_press_get_current_user' )->justReturn( new \LP_User( 10 ) );
		Functions\when( 'learn_press_get_user' )->alias( static fn( $id ) => new \LP_User( (int) $id ) );
		Functions\when( 'get_current_user_id' )->justReturn( 10 );
		Functions\when( 'get_query_var' )->alias( static fn( $key ) => $key === 'user' ? 'self-slug' : '' );
		Functions\when( 'get_user_by' )->alias( static fn( $field, $value ) => $field === 'slug' && $value === 'self-slug' ? $target_user : false );
		Functions\when( 'current_user_can' )->justReturn( false );

		\LearnPress\Models\UserModel::$findMap = [
			10 => new \LearnPress\Models\UserModel( 10, 'current-user' ),
		];

		\LearnPress\Services\UserService::instance()->userByPrettySlug = null;

		$this->load_profile_class();

		$profile = \LP_Profile::instance();

		$this->assertSame( 10, $profile->get_user_data( 'id' ) );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function reuses_profile_singleton_instance_for_same_profile_page_request(): void {
		$this->define_profile_dependencies();

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'learn_press_get_current_user' )->justReturn( new \LP_User( 10 ) );
		Functions\when( 'learn_press_get_user' )->alias( static fn( $id ) => new \LP_User( (int) $id ) );
		Functions\when( 'get_current_user_id' )->justReturn( 10 );
		Functions\when( 'get_query_var' )->justReturn( '' );
		Functions\when( 'get_user_by' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );

		\LearnPress\Models\UserModel::$findMap = [
			10 => new \LearnPress\Models\UserModel( 10, 'current-user' ),
		];

		\LearnPress\Services\UserService::instance()->userByPrettySlug = null;

		$this->load_profile_class();

		$profile_first  = \LP_Profile::instance();
		$profile_second = \LP_Profile::instance();

		$this->assertSame( $profile_first, $profile_second );
	}

	#[Test]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function reuses_cached_instance_per_user_id_when_not_on_profile_page(): void {
		$this->define_profile_dependencies();

		\LP_Page_Controller::$isProfilePage = false;

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'learn_press_get_current_user' )->justReturn( new \LP_User( 10 ) );
		Functions\when( 'learn_press_get_user' )->alias( static fn( $id ) => new \LP_User( (int) $id ) );
		Functions\when( 'get_current_user_id' )->justReturn( 10 );
		Functions\when( 'get_query_var' )->justReturn( '' );
		Functions\when( 'get_user_by' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );

		\LearnPress\Models\UserModel::$findMap = [
			10 => new \LearnPress\Models\UserModel( 10, 'current-user' ),
			22 => new \LearnPress\Models\UserModel( 22, 'u22' ),
			23 => new \LearnPress\Models\UserModel( 23, 'u23' ),
		];

		\LearnPress\Services\UserService::instance()->userByPrettySlug = null;

		$this->load_profile_class();

		$profile_a = \LP_Profile::instance( 22 );
		$profile_b = \LP_Profile::instance( 22 );
		$profile_c = \LP_Profile::instance( 23 );

		$this->assertSame( $profile_a, $profile_b );
		$this->assertNotSame( $profile_a, $profile_c );
	}
}

