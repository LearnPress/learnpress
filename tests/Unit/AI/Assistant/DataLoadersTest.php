<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\AI\Assistant;

use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class DataLoadersTest extends BrainMonkeyTestCase {

	private function load_data_loaders_with_stubs(): void {
		if ( ! function_exists( '\\LearnPress\\AI\\Assistant\\get_post_type' ) ) {
			eval(
				'namespace LearnPress\\AI\\Assistant;
				function get_post_type( $item_id ) {
					return \\LearnPress\\Tests\\Unit\\AI\\Assistant\\DataLoadersWPState::$post_types[ (int) $item_id ] ?? "";
				}
				function get_the_title( $item_id ) {
					return \\LearnPress\\Tests\\Unit\\AI\\Assistant\\DataLoadersWPState::$titles[ (int) $item_id ] ?? "";
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\Models\\LessonPostModel', false ) ) {
			eval(
				'namespace LearnPress\\Models;
				class LessonPostModel {
					public static array $items = array();
					public static function find( int $id, bool $cache = true ) {
						return self::$items[ $id ] ?? false;
					}
					public function __construct( public string $title, public string $content ) {}
					public function get_the_title(): string { return $this->title; }
					public function get_the_content(): string { return $this->content; }
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\Models\\CourseModel', false ) ) {
			eval(
				'namespace LearnPress\\Models;
				class CourseModel {
					public static array $items = array();
					public function __construct( public string $title, public array $sections = array() ) {}
					public static function find( int $id, bool $cache = true ) {
						return self::$items[ $id ] ?? false;
					}
					public function get_section_items(): array { return $this->sections; }
					public function get_the_title(): string { return $this->title; }
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\Models\\UserItems\\UserQuizModel', false ) ) {
			eval(
				'namespace LearnPress\\Models\\UserItems;
				class UserQuizModel {
					public static array $map = array();
					public static function find_user_item( int $user_id, int $item_id, string $item_type, int $ref_id, string $ref_type, bool $cache = true ) {
						return self::$map[ $item_id ] ?? false;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\Tests\\Unit\\AI\\Assistant\\QuizAttemptsStub', false ) ) {
			eval(
				'namespace LearnPress\\Tests\\Unit\\AI\\Assistant;
				class QuizAttemptsStub {
					public function __construct( private array $attempts ) {}
					public function get_attempts( int $limit = 3 ): array {
						return $this->attempts;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LearnPress\\AI\\Assistant\\DataLoaders', false ) ) {
			require_once dirname( __DIR__, 4 ) . '/inc/AI/Assistant/DataLoaders.php';
		}
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_get_lesson_content_returns_error_when_not_found(): void {
		$this->load_data_loaders_with_stubs();

		\LearnPress\Models\LessonPostModel::$items = array();
		$loader                                    = new \LearnPress\AI\Assistant\DataLoaders();

		$result = $loader->get_lesson_content( 99, 1 );

		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringContainsString( 'Lesson not found', $result['error'] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_get_course_outline_returns_sections_with_item_types(): void {
		$this->load_data_loaders_with_stubs();

		$section                               = (object) array(
			'section_id'   => 10,
			'section_name' => 'Section 1',
			'items'        => array(
				(object) array(
					'id'      => 100,
					'item_id' => 100,
				),
				(object) array(
					'id'      => 101,
					'item_id' => 101,
				),
			),
		);
		\LearnPress\Models\CourseModel::$items = array(
			5 => new \LearnPress\Models\CourseModel( 'Course A', array( $section ) ),
		);
		DataLoadersWPState::$post_types        = array(
			100 => 'lp_lesson',
			101 => LP_QUIZ_CPT,
		);
		DataLoadersWPState::$titles            = array(
			100 => 'Lesson 1',
			101 => 'Quiz 1',
		);

		$loader = new \LearnPress\AI\Assistant\DataLoaders();
		$result = $loader->get_course_outline( 5 );

		$this->assertSame( 'Course A', $result['title'] );
		$this->assertSame( 'lesson', $result['sections'][0]['items'][0]['type'] );
		$this->assertSame( 'quiz', $result['sections'][0]['items'][1]['type'] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_get_quiz_results_returns_normalized_attempts_for_quizzes_only(): void {
		$this->load_data_loaders_with_stubs();

		$section                               = (object) array(
			'section_id'   => 11,
			'section_name' => 'Section 2',
			'items'        => array(
				(object) array(
					'id'      => 201,
					'item_id' => 201,
				),
				(object) array(
					'id'      => 202,
					'item_id' => 202,
				),
			),
		);
		\LearnPress\Models\CourseModel::$items = array(
			7 => new \LearnPress\Models\CourseModel( 'Course B', array( $section ) ),
		);
		DataLoadersWPState::$post_types        = array(
			201 => LP_QUIZ_CPT,
			202 => 'lp_lesson',
		);
		DataLoadersWPState::$titles            = array(
			201 => 'Quiz Alpha',
			202 => 'Lesson Alpha',
		);

		\LearnPress\Models\UserItems\UserQuizModel::$map = array(
			201 => new QuizAttemptsStub(
				array(
					array(
						'result'     => array( 'mark' => '80' ),
						'graduation' => 'passed',
						'start_time' => '2026-01-01 00:00:00',
						'end_time'   => '2026-01-01 00:10:00',
						'time_spent' => '600',
					),
				)
			),
		);

		$loader = new \LearnPress\AI\Assistant\DataLoaders();
		$result = $loader->get_quiz_results( 3, 7 );

		$this->assertArrayHasKey( 'quizzes', $result );
		$this->assertSame( 1, count( $result['quizzes'] ) );
		$this->assertSame( 'Quiz Alpha', $result['quizzes'][0]['quiz_title'] );
		$this->assertSame( 'passed', $result['quizzes'][0]['attempts'][0]['graduation'] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_has_quiz_attempt_returns_true_when_any_attempt_exists(): void {
		$this->load_data_loaders_with_stubs();

		$section                               = (object) array(
			'section_id'   => 12,
			'section_name' => 'Section 3',
			'items'        => array(
				(object) array(
					'id'      => 301,
					'item_id' => 301,
				),
			),
		);
		\LearnPress\Models\CourseModel::$items = array(
			8 => new \LearnPress\Models\CourseModel( 'Course C', array( $section ) ),
		);
		DataLoadersWPState::$post_types        = array(
			301 => LP_QUIZ_CPT,
		);

		\LearnPress\Models\UserItems\UserQuizModel::$map = array(
			301 => new QuizAttemptsStub(
				array(
					array(
						'result' => array( 'mark' => '60' ),
					),
				)
			),
		);

		$loader = new \LearnPress\AI\Assistant\DataLoaders();

		$this->assertTrue( $loader->has_quiz_attempt( 3, 8 ) );
	}
}

class DataLoadersWPState {
	public static array $post_types = array();
	public static array $titles     = array();
}
