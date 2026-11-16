<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Admin\InputSuggest;

use Brain\Monkey\Functions;
use LearnPress\Databases\Course\CourseJsonDB;
use LearnPress\Filters\Course\CourseJsonFilter;
use LearnPress\TemplateHooks\Admin\InputSuggest\AdminInputSearchCourseSuggest;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Unit tests for AdminInputSearchCourseSuggest: permissions, filter and HTML of render_courses().
 */
class AdminInputSearchCourseSuggestTest extends BrainMonkeyTestCase {
	/**
	 * @var object Fake wpdb.
	 */
	private $wpdb;

	/**
	 * @var string[] Capabilities of the current user.
	 */
	private $caps = [];

	/**
	 * @var CourseJsonFilter|null Filter passed to the query.
	 */
	private $filter;

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( '\\LP_Helper', false ) ) {
			require_once LP_INC_PATH . 'class-lp-helper.php';
		}

		$this->caps   = [];
		$this->filter = null;

		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				if ( 'lp/services/courses/filter' === $hook ) {
					$this->filter = $value;
				}

				return $value;
			}
		);
		Functions\when( 'current_user_can' )->alias( fn( $cap ) => in_array( $cap, $this->caps, true ) );
		Functions\when( 'get_current_user_id' )->justReturn( 7 );
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
		$prop = new ReflectionProperty( CourseJsonDB::class, '_instance' );
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

	public function test_render_courses_admin_gets_all_courses(): void {
		$this->caps          = [ 'administrator' ];
		$this->wpdb->results = [
			(object) [
				'ID'         => 15,
				'post_title' => 'Course A',
			],
			(object) [
				'ID'         => 16,
				'post_title' => 'Course B',
			],
		];

		$html = $this->normalize( AdminInputSearchCourseSuggest::render_courses( [ 'search' => ' Course ' ] )->content );

		$this->assertSame(
			'<ul class="lp-search-suggest-list">'
			. '<li class="lp-search-suggest-item" data-id="15" data-text="Course A">Course A (#15)</li>'
			. '<li class="lp-search-suggest-item" data-id="16" data-text="Course B">Course B (#16)</li>'
			. '</ul>',
			$html
		);
		$this->assertFalse( isset( $this->filter->post_author ) );
		$this->assertSame( 'Course', $this->filter->post_title );
		$this->assertSame( 5, $this->filter->limit );
		$this->assertSame( 1, $this->filter->page );
		$this->assertCount( 1, $this->wpdb->queries, 'No count query.' );
		$this->assertStringContainsString( 'SELECT ID,post_title FROM wp_learnpress_courses AS c', $this->sql() );
		$this->assertStringContainsString( "c.post_title LIKE '%Course%'", $this->sql() );
		$this->assertStringNotContainsString( 'post_author', $this->sql() );
		$this->assertStringContainsString( "c.post_status IN ('publish','private','draft','pending','future')", $this->sql() );
		$this->assertStringNotContainsString( "'trash'", $this->sql() );
		$this->assertStringContainsString( 'LIMIT 0, 5', $this->sql() );
	}

	public function test_render_courses_instructor_gets_own_courses_only(): void {
		$this->caps = [ 'lp_teacher' ];

		AdminInputSearchCourseSuggest::render_courses( [ 'search' => 'Course' ] );

		$this->assertSame( 7, $this->filter->post_author );
		$this->assertStringContainsString( 'AND c.post_author = 7', $this->sql() );
	}

	public function test_render_courses_no_permission_returns_error_without_query(): void {
		$html = AdminInputSearchCourseSuggest::render_courses( [ 'search' => 'Course' ] )->content;

		$this->assertStringContainsString( 'learn-press-message error', $html );
		$this->assertStringContainsString( 'You do not have permission to search courses.', $html );
		$this->assertSame( [], $this->wpdb->queries );
	}

	/**
	 * @dataProvider provider_empty_search
	 */
	public function test_render_courses_empty_search_returns_empty_without_query( array $data ): void {
		$this->caps = [ 'administrator' ];

		$this->assertSame( '', AdminInputSearchCourseSuggest::render_courses( $data )->content );
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

	public function test_render_courses_no_rows_prints_no_results(): void {
		$this->caps = [ 'administrator' ];

		$html = $this->normalize( AdminInputSearchCourseSuggest::render_courses( [ 'search' => 'zzz' ] )->content );

		$this->assertSame( '<ul class="lp-search-suggest-list"><li class="lp-search-suggest-empty">No results</li></ul>', $html );
	}

	public function test_render_courses_escapes_title(): void {
		$this->caps          = [ 'administrator' ];
		$this->wpdb->results = [
			(object) [
				'ID'         => 15,
				'post_title' => '"><script>alert(1)</script>',
			],
		];

		$html = AdminInputSearchCourseSuggest::render_courses( [ 'search' => 'a' ] )->content;

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( 'data-text="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html );
	}

	public function test_render_courses_multiple_prints_checkboxes(): void {
		$this->caps          = [ 'administrator' ];
		$this->wpdb->results = [
			(object) [
				'ID'         => 15,
				'post_title' => 'Course A',
			],
		];

		$html = $this->normalize(
			AdminInputSearchCourseSuggest::render_courses(
				[
					'search'      => 'Course',
					'type_select' => 'multiple',
				]
			)->content
		);

		$this->assertSame(
			'<ul class="lp-search-suggest-list">'
			. '<li class="lp-search-suggest-actions"><button type="button" class="lp-search-suggest-clear-all">Clear all</button></li>'
			. '<li class="lp-search-suggest-item lp-search-suggest-item--multiple" data-id="15" data-text="Course A" data-type-select="multiple">'
			. '<input type="checkbox" class="lp-search-suggest-checkbox" tabindex="-1" value="15">Course A (#15)</li>'
			. '</ul>',
			$html
		);
	}

	public function test_render_courses_invalid_type_select_is_single(): void {
		$this->caps          = [ 'administrator' ];
		$this->wpdb->results = [
			(object) [
				'ID'         => 15,
				'post_title' => 'Course A',
			],
		];

		$html = AdminInputSearchCourseSuggest::render_courses(
			[
				'search'      => 'Course',
				'type_select' => '"><script>',
			]
		)->content;

		$this->assertStringNotContainsString( 'checkbox', $html );
		$this->assertStringNotContainsString( 'script', $html );
	}

	public function test_layout_multiple_sends_type_select_and_prints_items_selected(): void {
		Functions\when( 'wp_enqueue_script' )->justReturn();
		Functions\when( 'lp_skeleton_animation_html' )->justReturn();
		Functions\when( 'htmlentities2' )->returnArg();

		$component = ( new ReflectionClass( AdminInputSearchCourseSuggest::class ) )->newInstanceWithoutConstructor();
		$html      = $this->normalize(
			$component->layout(
				[
					'name'           => 'course_ids',
					'type_select'    => 'multiple',
					'items_selected' => [
						[
							'id'   => 15,
							'text' => 'Course A',
						],
						[
							'id'   => 16,
							'text' => 'Course B',
						],
					],
				]
			)
		);

		$this->assertStringContainsString( '<div class=" lp-wrap-input-search-suggest " data-type-select="multiple">', $html );
		$this->assertStringContainsString( 'name="course_ids" value="15,16">', $html );
		$this->assertStringContainsString( '<div class="lp-search-suggest-result lp-hidden">', $html );
		$this->assertStringContainsString( '&quot;type_select&quot;:&quot;multiple&quot;', $html );
		$this->assertStringContainsString( 'lp-search-suggest-item--multiple is-selected" data-id="15"', $html );
		$this->assertStringContainsString( 'tabindex="-1" value="16" checked>Course B (#16)</li>', $html );
		$this->assertStringNotContainsString( 'lp-load-ajax-element', $html );
		// The list of items selected is not sent back via AJAX.
		$this->assertStringNotContainsString( 'html_no_load_ajax_first', $html );
	}

	public function test_layout_single_has_no_type_select(): void {
		Functions\when( 'wp_enqueue_script' )->justReturn();
		Functions\when( 'lp_skeleton_animation_html' )->justReturn();
		Functions\when( 'htmlentities2' )->returnArg();

		$component = ( new ReflectionClass( AdminInputSearchCourseSuggest::class ) )->newInstanceWithoutConstructor();
		$html      = $component->layout(
			[
				'name'           => 'course_id',
				'items_selected' => [
					[
						'id'   => 15,
						'text' => 'Course A',
					],
				],
			]
		);

		$this->assertStringNotContainsString( 'type_select', $html );
		$this->assertStringNotContainsString( 'data-type-select', $html );
		$this->assertStringNotContainsString( 'checkbox', $html );
		$this->assertStringNotContainsString( 'lp-hidden">', str_replace( 'lp-loading-change lp-hidden">', '', $html ) );
	}

	public function test_allow_callback_adds_render_courses(): void {
		$component = ( new ReflectionClass( AdminInputSearchCourseSuggest::class ) )->newInstanceWithoutConstructor();

		$this->assertSame(
			[ 'Other:method', AdminInputSearchCourseSuggest::class . ':render_courses' ],
			$component->allow_callback( [ 'Other:method' ] )
		);
	}

	public function test_layout_prints_target_without_load_ajax_first(): void {
		Functions\when( 'wp_enqueue_script' )->justReturn();
		Functions\when( 'lp_skeleton_animation_html' )->justReturn();
		Functions\when( 'htmlentities2' )->returnArg();

		$component = ( new ReflectionClass( AdminInputSearchCourseSuggest::class ) )->newInstanceWithoutConstructor();
		$html      = $this->normalize( $component->layout( [ 'name' => 'course_id' ] ) );

		$this->assertStringStartsWith( '<div class=" lp-wrap-input-search-suggest ">', $html );
		$this->assertStringContainsString( 'placeholder="Search course..."', $html );
		$this->assertStringContainsString( 'name="course_id"', $html );
		$this->assertStringContainsString( '<div class="lp-target" data-id="lp-target-', $html );
		$this->assertStringContainsString( 'lp-search-course-suggest', $html );
		$this->assertStringContainsString( 'render_courses', $html );
		// No wrapper .lp-load-ajax-element and no skeleton: loadAJAX.js does not fetch on page load.
		$this->assertStringNotContainsString( 'lp-load-ajax-element', $html );
		$this->assertStringNotContainsString( 'loading-first', $html );
	}
}
