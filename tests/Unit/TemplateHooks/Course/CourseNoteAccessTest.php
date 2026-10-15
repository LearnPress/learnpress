<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Course;

use Brain\Monkey\Functions;
use LearnPress\Services\NoteService;
use LearnPress\TemplateHooks\Course\CourseNoteTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ReflectionClass;
use ReflectionProperty;

/**
 * Exercise the real lesson access resolver, rather than supplying a prebuilt render state.
 * Legacy WordPress controllers are isolated from the other unit-test fixtures.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
class CourseNoteAccessTest extends BrainMonkeyTestCase {
	private $viewer = 0;
	private $service;

	protected function setUp(): void {
		parent::setUp();
		defined( 'LP_PAGE_SINGLE_COURSE_CURRICULUM' ) || define( 'LP_PAGE_SINGLE_COURSE_CURRICULUM', 'lesson' );
		eval( 'class LP_Page_Controller { public static function page_current() { return LP_PAGE_SINGLE_COURSE_CURRICULUM; } }
			class LP_Global { public static $item; public static function course_item() { return self::$item; } }
			class LP_Settings { public static function get_option($key, $default = "") { return $default; } }' );
		\LP_Global::$item = new class() {
			public function get_course_id() { return 10; }
			public function get_id() { return 20; }
			public function get_item_type() { return LP_LESSON_CPT; }
		};
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'absint' )->alias( static fn( $value ) => abs( (int) $value ) );
		Functions\when( 'get_current_user_id' )->alias( fn() => $this->viewer );
		Functions\when( 'is_user_logged_in' )->alias( fn() => $this->viewer > 0 );
		$this->service = ( new ReflectionClass( LessonAccessServiceStub::class ) )->newInstanceWithoutConstructor();
		( new ReflectionProperty( NoteService::class, 'instance' ) )->setValue( null, $this->service );
	}

	private function template(): LessonAccessTemplateStub {
		return ( new ReflectionClass( LessonAccessTemplateStub::class ) )->newInstanceWithoutConstructor();
	}

	public function test_admin_and_course_instructor_resolve_to_own_notes_and_can_create(): void {
		foreach ( [ 1, 7 ] as $viewer ) {
			$this->viewer = $viewer;
			$template = $this->template();
			$state = $template->get_render_state();
			$this->assertSame( CourseNoteTemplate::MODE_EDIT, $state['mode'] );
			$this->assertSame( $viewer, $state['owner_id'] );
			$this->assertCount( 1, $state['notes'] );
			$this->assertSame( [ $viewer, 10, 20, LP_LESSON_CPT ], $this->service->request );
			$this->assertTrue( $template->js_data( $state )['canEdit'] );
			$this->assertTrue( $state['can_create'] );
			$this->assertTrue( $template->js_data( $state )['canCreate'] );
		}
	}

	public function test_student_resolves_to_own_notes_with_create_permission(): void {
		$this->viewer = 5;
		$template = $this->template();
		$state = $template->get_render_state();
		$this->assertSame( CourseNoteTemplate::MODE_EDIT, $state['mode'] );
		$this->assertSame( 5, $state['owner_id'] );
		$this->assertCount( 1, $state['notes'] );
		$this->assertTrue( $template->js_data( $state )['canCreate'] );
	}

	public function test_guest_and_instructor_of_another_course_are_rejected_before_loading_notes(): void {
		foreach ( [ 0, 8 ] as $viewer ) {
			$this->viewer = $viewer;
			$this->assertFalse( $this->template()->get_render_state() );
			$this->assertNull( $this->service->request );
		}
	}

	public function test_view_all_uses_current_user_for_every_role_and_ignores_client_owner(): void {
		Functions\when( 'wp_kses_post' )->returnArg();
		foreach ( [ 1 => 1, 7 => 7, 5 => 5, 8 => 8 ] as $viewer => $owner ) {
			$this->viewer = $viewer;
			CourseNoteTemplate::render_course_notes( [ 'course_id' => 10, 'owner_id' => 999, 'user_id' => 999, 'paged' => 2 ] );
			$this->assertSame( [ $viewer, $owner, 10, 2 ], $this->service->course_request );
		}
	}
}

class LessonAccessTemplateStub extends CourseNoteTemplate {
	public function js_data( array $state ): array {
		return $this->get_js_data( $state );
	}
}

class LessonAccessServiceStub extends NoteService {
	public $request;
	public $course_request;

	public function can_manage_course( int $viewer_id, int $course_id ): bool {
		return 1 === $viewer_id || ( 7 === $viewer_id && 10 === $course_id );
	}

	public function can_create( int $viewer_id, int $course_id, int $item_id, string $item_type = LP_LESSON_CPT ): bool {
		return 10 === $course_id && in_array( $viewer_id, [ 1, 5, 7 ], true );
	}

	public function get_lesson_notes( int $viewer_id, int $course_id, int $item_id, string $item_type = LP_LESSON_CPT ): array {
		$this->request = [ $viewer_id, $course_id, $item_id, $item_type ];
		return [ [ 'note_id' => 1 ] ];
	}

	public function get_course_notes( int $viewer_id, int $owner_id, int $course_id, int $page = 1 ): array {
		$this->course_request = [ $viewer_id, $owner_id, $course_id, $page ];
		return [ 'notes' => [], 'total_rows' => 0, 'page' => $page, 'per_page' => 10 ];
	}
}
