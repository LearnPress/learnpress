<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Admin\InputSuggest;

use Brain\Monkey\Functions;
use LearnPress\Databases\UserDB;
use LearnPress\TemplateHooks\Admin\InputSuggest\AdminInputSearchUserSuggest;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Unit tests for AdminInputSearchUserSuggest: permissions, query and HTML of render_users().
 */
class AdminInputSearchUserSuggestTest extends BrainMonkeyTestCase {
	/**
	 * @var object Fake wpdb.
	 */
	private $wpdb;

	/**
	 * @var string[] Capabilities of the current user.
	 */
	private $caps = [];

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( '\\LP_Helper', false ) ) {
			require_once LP_INC_PATH . 'class-lp-helper.php';
		}

		$this->caps = [];

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'current_user_can' )->alias( fn( $cap ) => in_array( $cap, $this->caps, true ) );
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->alias( static fn( $text ) => trim( strip_tags( (string) $text ) ) );
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'absint' )->alias( static fn( $value ) => abs( (int) $value ) );

		$this->wpdb = new class() {
			public $prefix             = 'wp_';
			public $users              = 'wp_users';
			public $usermeta           = 'wp_usermeta';
			public $posts              = 'wp_posts';
			public $postmeta           = 'wp_postmeta';
			public $options            = 'wp_options';
			public $terms              = 'wp_terms';
			public $term_relationships = 'wp_term_relationships';
			public $term_taxonomy      = 'wp_term_taxonomy';
			public $last_error         = '';
			public $queries            = [];
			public $results            = [];

			public function hide_errors() {}

			public function has_cap( $cap ) {
				return false;
			}

			public function esc_like( $text ) {
				return addcslashes( $text, '_%\\' );
			}

			public function prepare( $query, ...$args ) {
				if ( isset( $args[0] ) && is_array( $args[0] ) ) {
					$args = $args[0];
				}
				$query = str_replace( [ '%d', '%s' ], [ '%d', "'%s'" ], $query );

				return vsprintf( $query, $args );
			}

			public function get_results( $query ) {
				$this->queries[] = $query;

				return $this->results;
			}

			public function get_var( $query ) {
				$this->queries[] = $query;

				return 0;
			}
		};

		$GLOBALS['wpdb'] = $this->wpdb;
		$this->reset_db();
	}

	protected function tearDown(): void {
		$this->reset_db();
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	private function reset_db(): void {
		$prop = new ReflectionProperty( UserDB::class, '_instance' );
		$prop->setValue( null, null );
	}

	/**
	 * Collapse the whitespace of the markup, it is written on several lines.
	 */
	private function normalize( string $html ): string {
		$html = preg_replace( '/\s+/', ' ', $html );

		return preg_replace( [ '/>\s+</', '/\s+<\/li>/' ], [ '><', '</li>' ], $html );
	}

	private function sql(): string {
		return preg_replace( '/\s+/', ' ', (string) ( $this->wpdb->queries[0] ?? '' ) );
	}

	public function test_render_users_admin_gets_users(): void {
		$this->caps          = [ 'administrator' ];
		$this->wpdb->results = [
			(object) [
				'ID'           => 3,
				'display_name' => 'John Doe',
				'user_email'   => 'john@example.test',
			],
		];

		$html = $this->normalize( AdminInputSearchUserSuggest::render_users( [ 'search' => ' john ' ] )->content );

		$this->assertSame(
			'<ul class="lp-search-suggest-list">'
			. '<li class="lp-search-suggest-item" data-id="3" data-text="John Doe">John Doe (john@example.test) (#3)</li>'
			. '</ul>',
			$html
		);
		$this->assertCount( 1, $this->wpdb->queries, 'No count query.' );
		$this->assertStringContainsString( 'SELECT ID,display_name,user_email FROM wp_users AS u', $this->sql() );
		$this->assertStringContainsString( "u.display_name LIKE '%john%'", $this->sql() );
		$this->assertStringContainsString( 'ORDER BY u.display_name ASC', $this->sql() );
		$this->assertStringContainsString( 'LIMIT 0, 5', $this->sql() );
	}

	/**
	 * @dataProvider provider_denied_caps
	 */
	public function test_render_users_non_admin_returns_error_without_query( array $caps ): void {
		$this->caps = $caps;

		$html = AdminInputSearchUserSuggest::render_users( [ 'search' => 'john' ] )->content;

		$this->assertStringContainsString( 'learn-press-message error', $html );
		$this->assertStringContainsString( 'You do not have permission to search users.', $html );
		$this->assertSame( [], $this->wpdb->queries );
	}

	public static function provider_denied_caps(): array {
		return [
			'instructor' => [ [ 'lp_teacher' ] ],
			'subscriber' => [ [ 'read' ] ],
			'guest'      => [ [] ],
		];
	}

	/**
	 * @dataProvider provider_empty_search
	 */
	public function test_render_users_empty_search_returns_empty_without_query( array $data ): void {
		$this->caps = [ 'administrator' ];

		$this->assertSame( '', AdminInputSearchUserSuggest::render_users( $data )->content );
		$this->assertSame( [], $this->wpdb->queries );
	}

	public static function provider_empty_search(): array {
		return [
			'missing' => [ [] ],
			'empty'   => [ [ 'search' => '' ] ],
			'spaces'  => [ [ 'search' => '   ' ] ],
			'array'   => [ [ 'search' => [ 'a' ] ] ],
		];
	}

	public function test_render_users_no_rows_prints_no_results(): void {
		$this->caps = [ 'administrator' ];

		$html = $this->normalize( AdminInputSearchUserSuggest::render_users( [ 'search' => 'zzz' ] )->content );

		$this->assertSame( '<ul class="lp-search-suggest-list"><li class="lp-search-suggest-empty">No results</li></ul>', $html );
	}

	public function test_render_users_escapes_name_and_email(): void {
		$this->caps          = [ 'administrator' ];
		$this->wpdb->results = [
			(object) [
				'ID'           => 3,
				'display_name' => '"><script>alert(1)</script>',
				'user_email'   => '<b>mail</b>',
			],
		];

		$html = AdminInputSearchUserSuggest::render_users( [ 'search' => 'a' ] )->content;

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<b>', $html );
		$this->assertStringContainsString( 'data-text="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html );
	}

	public function test_render_users_multiple_prints_checkboxes(): void {
		$this->caps          = [ 'administrator' ];
		$this->wpdb->results = [
			(object) [
				'ID'           => 3,
				'display_name' => 'John Doe',
				'user_email'   => 'john@example.test',
			],
		];

		$html = $this->normalize(
			AdminInputSearchUserSuggest::render_users(
				[
					'search'      => 'john',
					'type_select' => 'multiple',
				]
			)->content
		);

		$this->assertSame(
			'<ul class="lp-search-suggest-list">'
			. '<li class="lp-search-suggest-actions"><button type="button" class="lp-search-suggest-clear-all">Clear all</button></li>'
			. '<li class="lp-search-suggest-item lp-search-suggest-item--multiple" data-id="3" data-text="John Doe" data-type-select="multiple">'
			. '<input type="checkbox" class="lp-search-suggest-checkbox" tabindex="-1" value="3">John Doe (john@example.test) (#3)</li>'
			. '</ul>',
			$html
		);
	}

	public function test_render_users_multiple_non_admin_is_denied(): void {
		$this->caps = [ 'lp_teacher' ];

		$html = AdminInputSearchUserSuggest::render_users(
			[
				'search'      => 'john',
				'type_select' => 'multiple',
			]
		)->content;

		$this->assertStringContainsString( 'You do not have permission to search users.', $html );
		$this->assertSame( [], $this->wpdb->queries );
	}

	public function test_layout_multiple_sends_type_select(): void {
		Functions\when( 'wp_enqueue_script' )->justReturn();
		Functions\when( 'lp_skeleton_animation_html' )->justReturn();
		Functions\when( 'htmlentities2' )->returnArg();

		$component = ( new ReflectionClass( AdminInputSearchUserSuggest::class ) )->newInstanceWithoutConstructor();
		$html      = $this->normalize(
			$component->layout(
				[
					'name'           => 'user_ids',
					'type_select'    => 'multiple',
					'value'          => '3',
					'items_selected' => [
						[
							'id'    => 3,
							'text'  => 'John Doe',
							'label' => 'John Doe (john@example.test)',
						],
					],
				]
			)
		);

		$this->assertStringContainsString( 'data-type-select="multiple">', $html );
		$this->assertStringContainsString( 'name="user_ids" value="3">', $html );
		$this->assertStringContainsString( '&quot;type_select&quot;:&quot;multiple&quot;', $html );
		$this->assertStringContainsString( 'value="3" checked>John Doe (john@example.test) (#3)</li>', $html );
	}

	public function test_allow_callback_adds_render_users(): void {
		$component = ( new ReflectionClass( AdminInputSearchUserSuggest::class ) )->newInstanceWithoutConstructor();

		$this->assertSame(
			[ AdminInputSearchUserSuggest::class . ':render_users' ],
			$component->allow_callback( [] )
		);
	}

	public function test_layout_prints_target_without_load_ajax_first(): void {
		Functions\when( 'wp_enqueue_script' )->justReturn();
		Functions\when( 'lp_skeleton_animation_html' )->justReturn();
		Functions\when( 'htmlentities2' )->returnArg();

		$component = ( new ReflectionClass( AdminInputSearchUserSuggest::class ) )->newInstanceWithoutConstructor();
		$html      = $component->layout( [ 'name' => 'user_id' ] );

		$this->assertStringContainsString( 'placeholder="Search user..."', $html );
		$this->assertStringContainsString( 'name="user_id"', $html );
		$this->assertStringContainsString( '<div class="lp-target" data-id="lp-target-', $html );
		$this->assertStringContainsString( 'lp-search-user-suggest', $html );
		$this->assertStringContainsString( 'render_users', $html );
		$this->assertStringNotContainsString( 'lp-load-ajax-element', $html );
		$this->assertStringNotContainsString( 'loading-first', $html );
	}
}
