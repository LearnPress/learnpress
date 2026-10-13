<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Admin;

use Brain\Monkey\Functions;
use LearnPress\Models\Note\NoteModel;
use LearnPress\Services\NoteService;
use LearnPress\TemplateHooks\Admin\AdminStudentNotesTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use ReflectionClass;
use ReflectionProperty;

class AdminStudentNotesTemplateTest extends BrainMonkeyTestCase {
	private $original_service;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_textarea' )->alias( static fn( $text ) => htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ) );
		$property = new ReflectionProperty( NoteService::class, 'instance' );
		$this->original_service = $property->getValue();
		$service = ( new ReflectionClass( AdminNotesServiceStub::class ) )->newInstanceWithoutConstructor();
		$property->setValue( null, $service );
	}

	protected function tearDown(): void {
		( new ReflectionProperty( NoteService::class, 'instance' ) )->setValue( null, $this->original_service );
		parent::tearDown();
	}

	private function actions( int $viewer, int $course = 10 ): string {
		Functions\when( 'get_current_user_id' )->justReturn( $viewer );
		$template = ( new ReflectionClass( AdminStudentNotesTemplate::class ) )->newInstanceWithoutConstructor();
		$note = new NoteModel( [ 'note_id' => 9, 'user_id' => 5, 'course_id' => $course, 'item_id' => 20, 'content' => '</textarea><script>note</script>' ] );
		return $template->html_actions( $note, 'https://example.test/lesson/' );
	}

	public function test_admin_and_course_instructor_receive_edit_and_delete_actions_with_safe_content(): void {
		foreach ( [ 1, 7 ] as $viewer ) {
			$html = $this->actions( $viewer );
			$this->assertStringContainsString( 'data-note-template', $html );
			$this->assertStringContainsString( 'lp-student-notes__delete', $html );
			$this->assertStringContainsString( 'lp-be-btn--danger', $html );
			$this->assertStringContainsString( 'lp-be-btn--primary', $html );
			$this->assertStringContainsString( 'data-note-id="9"', $html );
			$this->assertStringContainsString( '&lt;/textarea&gt;&lt;script&gt;note&lt;/script&gt;', $html );
			$this->assertStringNotContainsString( '<script>', $html );
		}
		$this->assertStringContainsString( 'data-note-template', $this->actions( 1, 99 ) );
	}

	public function test_guests_students_and_instructors_of_other_courses_receive_no_mutating_actions(): void {
		foreach ( [ [ 0, 10 ], [ 5, 10 ], [ 7, 99 ] ] as [ $viewer, $course ] ) {
			$html = $this->actions( $viewer, $course );
			$this->assertStringNotContainsString( 'data-note-template', $html );
			$this->assertStringNotContainsString( 'lp-student-notes__delete', $html );
		}
	}
}

class AdminNotesServiceStub extends NoteService {
	protected function is_admin( int $user_id ): bool {
		return 1 === $user_id;
	}

	public function is_course_instructor( int $user_id, int $course_id ): bool {
		return 7 === $user_id && 10 === $course_id;
	}
}
