<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Profile;

use Brain\Monkey\Functions;
use LearnPress\Services\NoteService;
use LearnPress\TemplateHooks\Profile\ProfileNotesTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use ReflectionClass;
use ReflectionProperty;

class ProfileNotesTemplateTest extends BrainMonkeyTestCase {
	private $original_options;
	private $original_service;

	protected function setUp(): void {
		parent::setUp();
		if ( ! class_exists( 'LP_Settings', false ) ) {
			eval( 'class LP_Settings {
				public static array $options = array();
				public static function get_option( string $key, $default = "" ) {
					return self::$options[$key] ?? $default;
				}
			}' );
		}
		$this->original_options = \LP_Settings::$options;
		\LP_Settings::$options['enable_student_notes'] = 'yes';
		$property = new ReflectionProperty( NoteService::class, 'instance' );
		$this->original_service = $property->getValue();
		Functions\when( 'absint' )->alias( static fn( $value ) => abs( (int) $value ) );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'wp_kses_post' )->returnArg();
	}

	protected function tearDown(): void {
		\LP_Settings::$options = $this->original_options;
		( new ReflectionProperty( NoteService::class, 'instance' ) )->setValue( null, $this->original_service );
		parent::tearDown();
	}

	private function user( int $id ) {
		return new class( $id ) {
			private $id;
			public function __construct( int $id ) { $this->id = $id; }
			public function get_id(): int { return $this->id; }
		};
	}

	public function test_notes_tab_is_private_and_respects_the_notes_setting(): void {
		$template = ( new ReflectionClass( ProfileNotesTemplate::class ) )->newInstanceWithoutConstructor();
		$tabs = [ 'notes' => [ 'title' => 'Notes' ], 'orders' => [] ];
		$this->assertSame( $tabs, $template->display_profile_tab( $tabs, $this->user( 5 ), $this->user( 5 ) ) );
		foreach ( [ $this->user( 0 ), $this->user( 6 ), $this->user( 1 ), null ] as $viewer ) {
			$this->assertArrayNotHasKey( 'notes', $template->display_profile_tab( $tabs, $this->user( 5 ), $viewer ) );
		}
		\LP_Settings::$options['enable_student_notes'] = 'no';
		$this->assertSame( [ 'orders' => [] ], $template->display_profile_tab( $tabs, $this->user( 5 ), $this->user( 5 ) ) );
	}

	public function test_ajax_guests_and_disabled_notes_are_rejected(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		$this->assertStringContainsString( 'Notes are unavailable.', ProfileNotesTemplate::render_notes( [] )->content );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		\LP_Settings::$options['enable_student_notes'] = 'no';
		$this->assertStringContainsString( 'Notes are unavailable.', ProfileNotesTemplate::render_notes( [] )->content );
	}

	public function test_ajax_ignores_forged_owners_and_uses_shared_pagination(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 5 );
		Functions\when( 'add_query_arg' )->justReturn( '?paged=%#%' );
		Functions\expect( 'paginate_links' )->once()->with( \Mockery::on( static fn( $args ) => $args['current'] === 2 && $args['total'] === 3 ) )->andReturn( '<ul class="page-numbers"><li>2</li></ul>' );
		$service = ( new ReflectionClass( ProfileNotesServiceStub::class ) )->newInstanceWithoutConstructor();
		$service->notes = [ $this->note() ];
		( new ReflectionProperty( NoteService::class, 'instance' ) )->setValue( null, $service );
		$response = ProfileNotesTemplate::render_notes( [ 'user_id' => 6, 'owner_id' => 6, 'paged' => 2 ] );
		$this->assertSame( [ 5, 2 ], $service->request );
		$this->assertStringContainsString( 'lp-notes__card', $response->content );
		$this->assertStringContainsString( 'learn-press-pagination', $response->content );
		$service->notes = [];
		$this->assertStringContainsString( 'You have not added any notes yet.', ProfileNotesTemplate::render_notes( [] )->content );
	}

	private function note(): array {
		return [
			'course_id' => 10, 'item_id' => 20, 'course_title' => '<b>Course</b>', 'item_title' => 'Lesson',
			'course_url' => 'https://example.test/course/', 'item_url' => 'https://example.test/course/lessons/lesson/',
			'note_type' => 'highlight', 'highlight_text' => '<script>quote</script>', 'content' => '<img src=x>Note', 'created_at_display' => '2026-10-13',
		];
	}

	public function test_cards_escape_student_text_and_link_to_the_lesson(): void {
		$highlight = $this->note();
		$text = array_merge( $highlight, [ 'note_type' => 'text', 'course_title' => 'Other course', 'content' => 'Other note' ] );
		$html = ProfileNotesTemplate::html_notes( [ $highlight, $text ] );
		$this->assertSame( 2, substr_count( $html, '<article ' ) );
		$this->assertSame( 1, substr_count( $html, '<blockquote ' ) );
		$this->assertStringContainsString( '&lt;script&gt;quote&lt;/script&gt;', $html );
		$this->assertStringContainsString( '&lt;img src=x&gt;Note', $html );
		$this->assertStringContainsString( '&lt;b&gt;Course&lt;/b&gt;', $html );
		$this->assertStringContainsString( 'href="https://example.test/course/lessons/lesson/"', $html );
		$this->assertStringContainsString( 'Other course', $html );
		$this->assertStringNotContainsString( 'lp-notes__card-edit', $html );
	}
}

class ProfileNotesServiceStub extends NoteService {
	public $notes = [];
	public $request;

	public function get_user_notes( int $user_id, int $page = 1 ): array {
		$this->request = [ $user_id, $page ];
		return [ 'notes' => $this->notes, 'total_rows' => 25, 'page' => $page, 'per_page' => 10 ];
	}
}
