<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Services;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Services\SetupDemoCourseService;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

class SetupDemoCourseServiceTest extends BrainMonkeyTestCase {
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
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function test_load_courses_normalizes_valid_json(): void {
		$service = $this->make_service( array( $this->course_data() ) );

		$courses = $service->load_courses();

		$this->assertCount( 1, $courses );
		$this->assertSame( 'Course One', $courses[0]['title'] );
		$this->assertArrayNotHasKey( 'slug', $courses[0] );
		$this->assertCount( 5, $courses[0]['section']['lessons'] );
	}

	public function test_load_courses_rejects_invalid_lesson_count(): void {
		$data = $this->course_data();
		array_pop( $data['section']['lessons'] );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'exactly five lessons' );
		$this->make_service( array( $data ) )->load_courses();
	}

	public function test_load_courses_allows_duplicate_titles(): void {
		$data = $this->course_data();

		$courses = $this->make_service( array( $data, $data ) )->load_courses();

		$this->assertCount( 2, $courses );
		$this->assertSame( $courses[0]['title'], $courses[1]['title'] );
	}

	public function test_import_course_returns_dynamic_progress(): void {
		$json_data = json_encode( array( 'courses' => array( $this->course_data(), $this->course_data( 'course-two' ) ) ) );
		$service   = new class( $json_data ) extends SetupDemoCourseService {
			public function __construct( string $json_data ) {
				$this->json_data = $json_data;
			}

			protected function persist_course( array $course ): int { return 123; }
		};

		$result = $service->import_course( 0 );

		$this->assertSame( 2, $result['total'] );
		$this->assertSame( 50, $result['percent'] );
		$this->assertSame( 'created', $result['result'] );
		$this->assertFalse( $result['complete'] );
	}

	private function make_service( array $courses ): SetupDemoCourseService {
		return new class( json_encode( array( 'courses' => $courses ) ) ) extends SetupDemoCourseService {
			public function __construct( string $json_data ) {
				$this->json_data = $json_data;
			}
		};
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
