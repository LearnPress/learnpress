<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Services;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Filters\NoteFilter;
use LearnPress\Models\Note\NoteModel;
use LearnPress\Services\NoteService;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use ReflectionClass;

/**
 * Test double: replaces data lookups with in-memory fixtures.
 */
class FakeNoteService extends NoteService {
	/** @var array<int, object> course_id => fake course */
	public $courses = [];
	/** @var array<string, object> "user:course" => fake user course */
	public $user_courses = [];
	/** @var array<int, NoteModel> */
	public $notes = [];
	/** @var int[] */
	public $admins = [];
	/** @var NoteModel[] */
	public $saved = [];
	/** @var NoteModel[] */
	public $removed = [];
	/** @var array[] [ column, id ] */
	public $deleted_by = [];
	/** @var int[] */
	public $authored_course_ids = [];
	public $course_note_rows = [];
	public $course_note_total = 0;
	public $course_note_filter;
	public $course_notes_detail_filter;

	protected function query_course_notes( NoteFilter $filter, int &$total_rows ): array {
		if ( $filter->only_fields ) {
			$this->course_note_filter = $filter;
			$total_rows = $this->course_note_total;
			return array_map( static function ( $id ) { return (object) [ 'item_id' => $id ]; }, array_unique( array_column( $this->course_note_rows, 'item_id' ) ) );
		}
		$this->course_notes_detail_filter = $filter;
		return $this->course_note_rows;
	}

	protected function find_course( int $course_id ) {
		return $this->courses[ $course_id ] ?? false;
	}

	protected function find_user( int $user_id ) {
		return $user_id > 0 ? (object) [ 'ID' => $user_id ] : false;
	}

	protected function find_user_course( int $user_id, int $course_id ) {
		return $this->user_courses[ "$user_id:$course_id" ] ?? false;
	}

	protected function find_note( int $note_id ) {
		return $this->notes[ $note_id ] ?? false;
	}

	protected function is_admin( int $user_id ): bool {
		return in_array( $user_id, $this->admins, true );
	}

	protected function persist( NoteModel $note ): NoteModel {
		$note->validate();
		$this->saved[] = $note;

		return $note;
	}

	protected function remove( NoteModel $note ): bool {
		$this->removed[] = $note;

		return true;
	}

	protected function find_authored_course_ids( int $user_id ): array {
		return $this->authored_course_ids;
	}

	protected function delete_notes_by( string $column, int $id ): int {
		$this->deleted_by[] = [ $column, $id ];

		return 1;
	}
}

/**
 * Unit tests for NoteService permissions and save/delete rules.
 */
class NoteServiceTest extends BrainMonkeyTestCase {
	const STUDENT    = 5;
	const OTHER      = 6;
	const INSTRUCTOR = 7;
	const ADMIN      = 1;
	const COURSE     = 10;
	const LESSON     = 20;

	/** @var FakeNoteService */
	private $service;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'absint' )->alias( static fn( $v ) => abs( (int) $v ) );
		Functions\when( 'sanitize_key' )->alias(
			static fn( $v ) => preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) )
		);
		// Mimic WP: a lone "<" becomes "&lt;" (wp_pre_kses_less_than), then tags are stripped.
		Functions\when( 'sanitize_textarea_field' )->alias(
			static fn( $v ) => strip_tags(
				preg_replace_callback(
					'%<[^>]*?((?=<)|>|$)%',
					static fn( $m ) => '>' === substr( $m[0], -1 ) ? $m[0] : str_replace( '<', '&lt;', $m[0] ),
					(string) $v
				)
			)
		);
		Functions\when( 'wp_check_invalid_utf8' )->returnArg();
		Functions\when( 'apply_filters' )->alias( static fn( $hook, $value ) => $value );

		// Singleton has a private constructor: build the double without calling it.
		$this->service = ( new ReflectionClass( FakeNoteService::class ) )->newInstanceWithoutConstructor();

		$this->service->courses[ self::COURSE ] = $this->fake_course( self::INSTRUCTOR, [ self::LESSON ] );
		$this->service->admins                  = [ self::ADMIN ];
		$this->service->user_courses[ self::STUDENT . ':' . self::COURSE ] = $this->fake_user_course( true );
		$this->service->user_courses[ self::OTHER . ':' . self::COURSE ]   = $this->fake_user_course( false );
	}

	private function fake_course( int $author_id, array $lesson_ids ) {
		return new class( $author_id, $lesson_ids ) {
			private $author_id;
			private $lesson_ids;

			public function __construct( $author_id, $lesson_ids ) {
				$this->author_id  = $author_id;
				$this->lesson_ids = $lesson_ids;
			}

			public function get_item_model( int $item_id, string $item_type ) {
				return 'lp_lesson' === $item_type && in_array( $item_id, $this->lesson_ids, true ) ? (object) [ 'ID' => $item_id ] : false;
			}

			public function check_user_is_author( $user ): bool {
				return (int) $user->ID === $this->author_id;
			}

		};
	}

	private function fake_user_course( bool $enrolled ) {
		return new class( $enrolled ) {
			private $enrolled;

			public function __construct( $enrolled ) {
				$this->enrolled = $enrolled;
			}

			public function has_enrolled_or_finished(): bool {
				return $this->enrolled;
			}
		};
	}

	private function existing_note( int $owner_id = self::STUDENT ): NoteModel {
		$note = new NoteModel(
			[
				'note_id'   => 99,
				'user_id'   => $owner_id,
				'course_id' => self::COURSE,
				'item_id'   => self::LESSON,
				'note_type' => NoteModel::TYPE_TEXT,
				'content'   => 'Old',
			]
		);

		$this->service->notes[99] = $note;

		return $note;
	}

	// -------------------------------------------------------------------------
	// can_create
	// -------------------------------------------------------------------------

	public function test_enrolled_student_can_create(): void {
		$this->assertTrue( $this->service->can_create( self::STUDENT, self::COURSE, self::LESSON ) );
	}

	public function test_guest_cannot_create(): void {
		$this->assertFalse( $this->service->can_create( 0, self::COURSE, self::LESSON ) );
	}

	public function test_not_enrolled_user_cannot_create(): void {
		$this->assertFalse( $this->service->can_create( self::OTHER, self::COURSE, self::LESSON ) );
		// No user course row at all (e.g. instructor / admin).
		$this->assertFalse( $this->service->can_create( self::INSTRUCTOR, self::COURSE, self::LESSON ) );
	}

	public function test_cannot_create_on_lesson_not_in_course(): void {
		$this->assertFalse( $this->service->can_create( self::STUDENT, self::COURSE, 404 ) );
	}

	public function test_cannot_create_on_unknown_course(): void {
		$this->assertFalse( $this->service->can_create( self::STUDENT, 404, self::LESSON ) );
	}

	public function test_cannot_create_on_quiz(): void {
		$this->assertFalse( $this->service->can_create( self::STUDENT, self::COURSE, self::LESSON, 'lp_quiz' ) );
	}

	public function test_can_create_filter_has_final_say(): void {
		Functions\when( 'apply_filters' )->alias(
			static fn( $hook, $value ) => 'learn-press/note/can-create' === $hook ? true : $value
		);

		// Not enrolled, but an add-on allows it.
		$this->assertTrue( $this->service->can_create( self::OTHER, self::COURSE, self::LESSON ) );
	}

	public function test_can_create_filter_can_deny_with_generic_message(): void {
		Functions\when( 'apply_filters' )->alias(
			static fn( $hook, $value ) => 'learn-press/note/can-create' === $hook ? false : $value
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'You do not have permission to add notes to this item.' );
		$this->service->check_can_create( self::STUDENT, self::COURSE, self::LESSON, 'lp_lesson' );
	}

	// -------------------------------------------------------------------------
	// can_view
	// -------------------------------------------------------------------------

	public function test_view_permissions(): void {
		$this->assertTrue( $this->service->can_view( self::STUDENT, self::STUDENT, self::COURSE ), 'owner' );
		$this->assertTrue( $this->service->can_view( self::ADMIN, self::STUDENT, self::COURSE ), 'admin' );
		$this->assertTrue( $this->service->can_view( self::INSTRUCTOR, self::STUDENT, self::COURSE ), 'course instructor' );
		$this->assertFalse( $this->service->can_view( self::OTHER, self::STUDENT, self::COURSE ), 'other student' );
		$this->assertFalse( $this->service->can_view( 0, self::STUDENT, self::COURSE ), 'guest' );
	}

	public function test_instructor_of_other_course_cannot_view(): void {
		$this->service->courses[11] = $this->fake_course( 8, [ self::LESSON ] );

		$this->assertFalse( $this->service->can_view( self::INSTRUCTOR, self::STUDENT, 11 ) );
	}

	public function test_get_item_notes_rejects_other_student(): void {
		$this->expectException( Exception::class );
		$this->service->get_item_notes( self::OTHER, self::STUDENT, self::COURSE, self::LESSON );
	}

	public function test_course_notes_scope_and_pagination_include_all_lessons(): void {
		Functions\when( 'get_option' )->justReturn( 'Y-m-d' );
		Functions\when( 'wp_date' )->justReturn( '2026-10-12' );
		$this->service->course_note_rows = [
			(object) [ 'note_id' => 1, 'user_id' => self::STUDENT, 'course_id' => self::COURSE, 'item_id' => 20, 'item_title' => 'Lesson A', 'content' => 'A', 'created_at' => '2026-10-12 00:00:00' ],
			(object) [ 'note_id' => 2, 'user_id' => self::STUDENT, 'course_id' => self::COURSE, 'item_id' => 21, 'item_title' => 'Lesson B', 'content' => 'B', 'created_at' => '2026-10-12 00:00:00' ],
		];
		$this->service->course_note_total = 25;
		$data = $this->service->get_course_notes( self::STUDENT, self::STUDENT, self::COURSE, 2 );
		$filter = $this->service->course_note_filter;
		$this->assertSame( self::STUDENT, $filter->user_id );
		$this->assertSame( self::COURSE, $filter->course_id );
		$this->assertNull( $filter->item_id );
		$this->assertSame( 2, $filter->page );
		$this->assertSame( 10, $filter->limit );
		$this->assertSame( [ 'DISTINCT n.item_id' ], $filter->only_fields );
		$details = $this->service->course_notes_detail_filter;
		$this->assertSame( -1, $details->limit );
		$this->assertTrue( $details->join_details );
		$this->assertFalse( $details->run_query_count );
		$this->assertSame( [ 'AND n.item_id IN (20,21)' ], $details->where );
		$this->assertSame( self::STUDENT, $details->user_id );
		$this->assertSame( self::COURSE, $details->course_id );
		$this->assertSame( 25, $data['total_rows'] );
		$this->assertSame( [ 'Lesson A', 'Lesson B' ], array_column( $data['notes'], 'item_title' ) );
		$this->assertArrayNotHasKey( 'item_url', $data['notes'][1] );
		$this->assertArrayNotHasKey( 'user_id', $data['notes'][0] );
	}

	public function test_course_notes_keep_a_large_lesson_group_together(): void {
		Functions\when( 'get_option' )->justReturn( 'Y-m-d' );
		Functions\when( 'wp_date' )->justReturn( '2026-10-12' );
		for ( $id = 1; $id <= 25; ++$id ) {
			$this->service->course_note_rows[] = (object) [ 'note_id' => $id, 'user_id' => self::STUDENT, 'course_id' => self::COURSE, 'item_id' => self::LESSON, 'item_title' => 'Lesson A', 'content' => 'Note ' . $id, 'created_at' => '2026-10-12 00:00:00' ];
		}
		$this->service->course_note_total = 1;
		$data = $this->service->get_course_notes( self::STUDENT, self::STUDENT, self::COURSE );
		$this->assertCount( 25, $data['notes'] );
		$this->assertSame( 1, $data['total_rows'] );
		$this->assertSame( 10, $data['per_page'] );
	}

	public function test_course_notes_reject_other_student_before_querying(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'You do not have permission to view these notes.' );
		$this->service->get_course_notes( self::OTHER, self::STUDENT, self::COURSE );
	}

	public function test_course_instructor_can_review_students_course_notes(): void {
		$data = $this->service->get_course_notes( self::INSTRUCTOR, self::STUDENT, self::COURSE, 0 );
		$this->assertSame( [], $data['notes'] );
		$this->assertSame( 1, $data['page'] );
	}

	public function test_course_notes_reject_unknown_course(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Course is invalid!' );
		$this->service->get_course_notes( self::STUDENT, self::STUDENT, 404 );
	}

	// -------------------------------------------------------------------------
	// Cleanup hooks
	// -------------------------------------------------------------------------

	public function test_deleting_user_deletes_their_notes(): void {
		$this->service->on_deleted_user( self::STUDENT );

		$this->assertSame( [ [ 'user_id', self::STUDENT ] ], $this->service->deleted_by );
	}

	public function test_deleting_course_or_lesson_deletes_notes(): void {
		$this->service->on_deleted_post( self::COURSE, (object) [ 'post_type' => 'lp_course' ] );
		$this->service->on_deleted_post( self::LESSON, (object) [ 'post_type' => 'lp_lesson' ] );

		$this->assertSame(
			[ [ 'course_id', self::COURSE ], [ 'item_id', self::LESSON ] ],
			$this->service->deleted_by
		);
	}

	public function test_deleting_other_posts_keeps_notes(): void {
		$this->service->on_deleted_post( 30, (object) [ 'post_type' => 'lp_quiz' ] );
		$this->service->on_deleted_post( 31, (object) [ 'post_type' => 'post' ] );

		$this->assertSame( [], $this->service->deleted_by );
	}

	// -------------------------------------------------------------------------
	// get_viewable_course_ids
	// -------------------------------------------------------------------------

	public function test_admin_can_view_all_courses(): void {
		$this->assertNull( $this->service->get_viewable_course_ids( self::ADMIN ) );
	}

	public function test_instructor_views_own_courses_plus_filtered(): void {
		$this->service->authored_course_ids = [ 10, 11 ];
		Functions\when( 'apply_filters' )->alias(
			static fn( $hook, $value ) => 'learn-press/note/viewable-course-ids' === $hook ? array_merge( $value, [ 12, 10 ] ) : $value
		);

		$this->assertSame( [ 10, 11, 12 ], $this->service->get_viewable_course_ids( self::INSTRUCTOR ) );
	}

	public function test_guest_views_no_course(): void {
		$this->assertSame( [], $this->service->get_viewable_course_ids( 0 ) );
	}

	// -------------------------------------------------------------------------
	// save_note
	// -------------------------------------------------------------------------

	public function test_create_note_forces_current_user_as_owner(): void {
		$note = $this->service->save_note(
			self::STUDENT,
			[
				'user_id'   => self::OTHER, // Must be ignored.
				'course_id' => self::COURSE,
				'item_id'   => self::LESSON,
				'content'   => 'Hello',
			]
		);

		$this->assertSame( self::STUDENT, $note->user_id );
		$this->assertSame( 'Hello', $note->content );
		$this->assertCount( 1, $this->service->saved );
	}

	public function test_create_highlight_note(): void {
		$note = $this->service->save_note(
			self::STUDENT,
			[
				'course_id' => self::COURSE,
				'item_id'   => self::LESSON,
				'note_type' => NoteModel::TYPE_HIGHLIGHT,
				'anchor'    => [
					'scope'    => NoteModel::SCOPE_LESSON_CONTENT,
					'quote'    => [ 'exact' => 'free plugin' ],
					'position' => [
						'start' => 1,
						'end'   => 12,
					],
				],
			]
		);

		$this->assertSame( 'free plugin', $note->highlight_text );
	}

	public function test_not_enrolled_user_cannot_create_note(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'You must enroll in this course to add notes.' );

		$this->service->save_note(
			self::OTHER,
			[
				'course_id' => self::COURSE,
				'item_id'   => self::LESSON,
				'content'   => 'Hello',
			]
		);
	}

	public function test_owner_updates_only_content(): void {
		$this->existing_note();

		$note = $this->service->save_note(
			self::STUDENT,
			[
				'note_id'   => 99,
				'item_id'   => 12345, // Must be ignored.
				'note_type' => NoteModel::TYPE_HIGHLIGHT, // Must be ignored.
				'content'   => 'New',
			]
		);

		$this->assertSame( 'New', $note->content );
		$this->assertSame( self::LESSON, $note->item_id );
		$this->assertSame( NoteModel::TYPE_TEXT, $note->note_type );
	}

	public function test_other_student_cannot_update_note(): void {
		$this->existing_note();
		$this->service->user_courses[ self::OTHER . ':' . self::COURSE ] = $this->fake_user_course( true );

		$this->expectException( Exception::class );
		$this->service->save_note(
			self::OTHER,
			[
				'note_id' => 99,
				'content' => 'Hacked',
			]
		);
	}

	public function test_admin_and_course_instructor_can_update_and_delete_student_notes(): void {
		foreach ( [ self::ADMIN, self::INSTRUCTOR ] as $viewer ) {
			$this->existing_note();
			$note = $this->service->save_note( $viewer, [ 'note_id' => 99, 'content' => 'Updated' ] );
			$this->assertSame( 'Updated', $note->content );
			$this->assertSame( self::STUDENT, $note->user_id );
			$this->assertTrue( $this->service->delete_note( $viewer, 99 ) );
		}
	}

	public function test_unrelated_instructor_cannot_manage_student_notes(): void {
		$note = $this->existing_note();
		$note->course_id = 404;
		$this->assertFalse( $this->service->can_manage( self::INSTRUCTOR, $note ) );
		$this->assertFalse( $this->service->can_manage( self::OTHER, $note ) );
		$this->assertFalse( $this->service->can_manage( 0, $note ) );
	}

	public function test_staff_course_view_does_not_filter_by_student(): void {
		foreach ( [ self::ADMIN, self::INSTRUCTOR ] as $viewer ) {
			$this->service->get_course_notes( $viewer, 0, self::COURSE );
			$this->assertNull( $this->service->course_note_filter->user_id );
			$this->assertSame( self::COURSE, $this->service->course_note_filter->course_id );
		}
	}

	public function test_student_cannot_request_all_students_notes(): void {
		$this->expectException( Exception::class );
		$this->service->get_course_notes( self::STUDENT, 0, self::COURSE );
	}

	public function test_update_unknown_note_fails(): void {
		$this->expectException( Exception::class );
		$this->service->save_note( self::STUDENT, [ 'note_id' => 12345 ] );
	}

	// -------------------------------------------------------------------------
	// delete_note
	// -------------------------------------------------------------------------

	public function test_owner_can_delete(): void {
		$this->existing_note();

		$this->assertTrue( $this->service->delete_note( self::STUDENT, 99 ) );
		$this->assertCount( 1, $this->service->removed );
	}

	public function test_other_user_cannot_delete(): void {
		$this->existing_note();

		foreach ( [ self::OTHER, 0 ] as $user_id ) {
			try {
				$this->service->delete_note( $user_id, 99 );
				$this->fail( "User $user_id should not delete the note." );
			} catch ( Exception $e ) {
				$this->assertSame( 'You do not have permission to delete this note.', $e->getMessage() );
			}
		}

		$this->assertSame( [], $this->service->removed );
	}

	public function test_owner_who_lost_enrollment_cannot_delete(): void {
		$this->existing_note();
		$this->service->user_courses[ self::STUDENT . ':' . self::COURSE ] = $this->fake_user_course( false );

		$this->expectException( Exception::class );
		$this->service->delete_note( self::STUDENT, 99 );
	}
}
