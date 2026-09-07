<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Services;

use Exception;
use LearnPress\Services\SetupDemoCourseService;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

class SetupDemoCourseServiceTest extends BrainMonkeyTestCase {
	private string $json_file;

	protected function setUp(): void {
		parent::setUp();
		$this->json_file = tempnam( sys_get_temp_dir(), 'lp-demo-' );
	}

	protected function tearDown(): void {
		if ( is_file( $this->json_file ) ) {
			unlink( $this->json_file );
		}
		parent::tearDown();
	}

	public function test_load_courses_normalizes_valid_json(): void {
		file_put_contents( $this->json_file, json_encode( array( 'courses' => array( $this->course_data() ) ) ) );
		$service = $this->make_service();

		$courses = $service->load_courses();

		$this->assertCount( 1, $courses );
		$this->assertSame( 'course-one', $courses[0]['slug'] );
		$this->assertCount( 5, $courses[0]['section']['lessons'] );
	}

	public function test_load_courses_rejects_invalid_lesson_count(): void {
		$data = $this->course_data();
		array_pop( $data['section']['lessons'] );
		file_put_contents( $this->json_file, json_encode( array( 'courses' => array( $data ) ) ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'exactly five lessons' );
		$this->make_service()->load_courses();
	}

	public function test_load_courses_rejects_duplicate_slugs(): void {
		$data = $this->course_data();
		file_put_contents( $this->json_file, json_encode( array( 'courses' => array( $data, $data ) ) ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'unique slug' );
		$this->make_service()->load_courses();
	}

	public function test_import_course_returns_dynamic_progress(): void {
		file_put_contents( $this->json_file, json_encode( array( 'courses' => array( $this->course_data(), $this->course_data( 'course-two' ) ) ) ) );
		$service = new class( $this->json_file, 'file_get_contents' ) extends SetupDemoCourseService {
			protected function find_existing_course( string $slug ): int { return 0; }
			protected function persist_course( array $course ): int { return 123; }
		};

		$result = $service->import_course( 0 );

		$this->assertSame( 2, $result['total'] );
		$this->assertSame( 50, $result['percent'] );
		$this->assertSame( 'created', $result['result'] );
		$this->assertFalse( $result['complete'] );
	}

	private function make_service(): SetupDemoCourseService {
		return new SetupDemoCourseService( $this->json_file, 'file_get_contents' );
	}

	private function course_data( string $slug = 'course-one' ): array {
		$lessons = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$lessons[] = array( 'title' => "Lesson {$i}", 'content' => "Content {$i}" );
		}

		return array(
			'slug'    => $slug,
			'title'   => ucwords( str_replace( '-', ' ', $slug ) ),
			'content' => 'Course content',
			'section' => array( 'title' => 'Introduction', 'lessons' => $lessons ),
		);
	}
}
