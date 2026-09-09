<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Services;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Services\SetupDemoCourseService;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

class SetupDemoCourseServiceTest extends BrainMonkeyTestCase {
	private string $json_file;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_title' )->alias(
			static fn( $value ) => strtolower( trim( preg_replace( '/[^a-z0-9]+/i', '-', (string) $value ), '-' ) )
		);
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'admin_url' )->alias( static fn( $path ) => 'https://example.test/wp-admin/' . $path );
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
		$this->assertSame( 'Course One', $courses[0]['title'] );
		$this->assertArrayNotHasKey( 'slug', $courses[0] );
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

	public function test_load_courses_rejects_duplicate_titles(): void {
		$data = $this->course_data();
		file_put_contents( $this->json_file, json_encode( array( 'courses' => array( $data, $data ) ) ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'unique title' );
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

	private function course_data( string $title = 'Course One' ): array {
		$lessons = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$lessons[] = array( 'title' => "Lesson {$i}", 'content' => "Content {$i}" );
		}

		return array(
			'title'   => $title,
			'content' => 'Course content',
			'section' => array( 'title' => 'Introduction', 'lessons' => $lessons ),
		);
	}
}
