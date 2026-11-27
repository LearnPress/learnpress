<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Services;

use Brain\Monkey\Functions;
use LearnPress\Services\CourseItemStatusService;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;

/**
 * Unit tests for CourseItemStatusService.
 * Collaborators (DB lookups, remove rows, refresh course) are protected methods stubbed via a partial mock.
 */
class CourseItemStatusServiceTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'apply_filters' )->alias( static fn( $hook, $value ) => $value );
		Functions\when( 'has_filter' )->justReturn( false );
	}

	/**
	 * @return CourseItemStatusService|Mockery\MockInterface
	 */
	private function make_service() {
		return Mockery::mock( CourseItemStatusService::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	private function post( int $id, string $type ): object {
		return (object) [
			'ID'        => $id,
			'post_type' => $type,
		];
	}

	public function test_on_transition_post_status_ignores_same_status(): void {
		$service = $this->make_service();
		$service->shouldNotReceive( 'get_course_ids_of_item' );

		$service->on_transition_post_status( 'publish', 'publish', $this->post( 1, 'lp_lesson' ) );

		$this->assertSame( [], $service->get_course_ids_queued() );
	}

	public function test_on_transition_post_status_ignores_non_item_type(): void {
		$service = $this->make_service();
		$service->shouldNotReceive( 'get_course_ids_of_item' );

		$service->on_transition_post_status( 'draft', 'publish', $this->post( 1, 'post' ) );

		$this->assertSame( [], $service->get_course_ids_queued() );
	}

	public function test_on_transition_post_status_any_status_queues_courses(): void {
		$service = $this->make_service();
		$service->shouldReceive( 'get_course_ids_of_item' )->with( 1, 'lp_lesson' )->andReturn( [ 10 ] );

		$service->on_transition_post_status( 'archived', 'draft', $this->post( 1, 'lp_lesson' ) );

		$this->assertSame( [ 10 ], $service->get_course_ids_queued() );
	}

	public function test_on_transition_post_status_draft_to_trash_queues_courses_for_edit_list(): void {
		$service = $this->make_service();
		$service->shouldReceive( 'get_course_ids_of_item' )->with( 1, 'lp_lesson' )->andReturn( [ 10 ] );

		$service->on_transition_post_status( 'trash', 'draft', $this->post( 1, 'lp_lesson' ) );

		$this->assertSame( [ 10 ], $service->get_course_ids_queued() );
	}

	public function test_on_post_updated_title_changed_queues_courses(): void {
		$service = $this->make_service();
		$service->shouldReceive( 'get_course_ids_of_item' )->with( 3, 'lp_quiz' )->andReturn( [ 20 ] );

		$after  = (object) [ 'post_type' => 'lp_quiz', 'post_title' => 'New' ];
		$before = (object) [ 'post_type' => 'lp_quiz', 'post_title' => 'Old' ];
		$service->on_post_updated( 3, $after, $before );

		$this->assertSame( [ 20 ], $service->get_course_ids_queued() );
	}

	public function test_on_post_updated_same_title_ignored(): void {
		$service = $this->make_service();
		$service->shouldNotReceive( 'get_course_ids_of_item' );

		$post = (object) [ 'post_type' => 'lp_quiz', 'post_title' => 'Same' ];
		$service->on_post_updated( 3, $post, clone $post );

		$this->assertSame( [], $service->get_course_ids_queued() );
	}

	public function test_on_transition_post_status_publish_to_trash_queues_courses_unique(): void {
		$service = $this->make_service();
		$service->shouldReceive( 'get_course_ids_of_item' )->with( 5, 'lp_lesson' )->andReturn( [ 10, 11 ] );
		$service->shouldReceive( 'get_course_ids_of_item' )->with( 6, 'lp_quiz' )->andReturn( [ 11, 12 ] );
		$service->shouldNotReceive( 'remove_item_from_sections' );

		$service->on_transition_post_status( 'trash', 'publish', $this->post( 5, 'lp_lesson' ) );
		$service->on_transition_post_status( 'publish', 'draft', $this->post( 6, 'lp_quiz' ) );

		$this->assertSame( [ 10, 11, 12 ], $service->get_course_ids_queued() );
	}

	public function test_on_before_delete_post_removes_item_and_queues_courses(): void {
		$service = $this->make_service();
		$service->shouldReceive( 'get_course_ids_of_item' )->with( 5, 'lp_lesson' )->andReturn( [ 10 ] );
		$service->shouldReceive( 'remove_item_from_sections' )->once()->with( 5, [ 10 ] );

		$service->on_before_delete_post( 5, $this->post( 5, 'lp_lesson' ) );

		$this->assertSame( [ 10 ], $service->get_course_ids_queued() );
	}

	public function test_on_before_delete_post_skips_item_not_assigned(): void {
		$service = $this->make_service();
		$service->shouldReceive( 'get_course_ids_of_item' )->andReturn( [] );
		$service->shouldNotReceive( 'remove_item_from_sections' );

		$service->on_before_delete_post( 5, $this->post( 5, 'lp_lesson' ) );

		$this->assertSame( [], $service->get_course_ids_queued() );
	}

	public function test_on_before_delete_post_ignores_non_item_type(): void {
		Functions\when( 'get_post_type' )->justReturn( 'page' );
		$service = $this->make_service();
		$service->shouldNotReceive( 'get_course_ids_of_item' );

		$service->on_before_delete_post( 5 );

		$this->assertSame( [], $service->get_course_ids_queued() );
	}

	public function test_refresh_courses_queued_refreshes_each_course_once_then_empties(): void {
		$service = $this->make_service();
		$service->shouldReceive( 'get_course_ids_of_item' )->andReturn( [ 10, 11 ] );
		$service->shouldReceive( 'refresh_course' )->once()->with( 10 );
		$service->shouldReceive( 'refresh_course' )->once()->with( 11 );

		$service->on_transition_post_status( 'draft', 'publish', $this->post( 5, 'lp_lesson' ) );
		$service->on_transition_post_status( 'publish', 'draft', $this->post( 5, 'lp_lesson' ) );
		$service->refresh_courses_queued();
		$service->refresh_courses_queued();

		$this->assertSame( [], $service->get_course_ids_queued() );
	}
}
