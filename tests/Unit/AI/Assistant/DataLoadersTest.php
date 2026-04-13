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
					public function __construct( private string $status = "completed", private array $result = array() ) {}
					public static function find_user_item( int $user_id, int $item_id, string $item_type, int $ref_id, string $ref_type, bool $cache = true ) {
						return self::$map[ $item_id ] ?? false;
					}
					public function get_status(): string {
						return $this->status;
					}
					public function get_result(): array {
						return $this->result;
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
	public function test_get_quiz_review_result_returns_completed_quiz_result_for_current_item(): void {
		$this->load_data_loaders_with_stubs();
		if ( ! defined( 'LP_ITEM_COMPLETED' ) ) {
			define( 'LP_ITEM_COMPLETED', 'completed' );
		}

		$section                               = (object) array(
			'section_id'   => 13,
			'section_name' => 'Section 4',
			'items'        => array(
				(object) array(
					'id'        => 401,
					'item_id'   => 401,
					'item_type' => LP_QUIZ_CPT,
					'title'     => 'Quiz Gamma',
				),
			),
		);
		\LearnPress\Models\CourseModel::$items = array(
			9 => new \LearnPress\Models\CourseModel( 'Course D', array( $section ) ),
		);

		\LearnPress\Models\UserItems\UserQuizModel::$map = array(
			401 => new \LearnPress\Models\UserItems\UserQuizModel(
				LP_ITEM_COMPLETED,
				array(
					'mark'   => 80,
					'result' => 0.8,
				)
			),
		);

		$loader = new \LearnPress\AI\Assistant\DataLoaders();
		$result = $loader->get_quiz_review_result( 3, 9, 401 );

		$this->assertArrayHasKey( 'quiz', $result );
		$this->assertSame( 401, $result['quiz']['quiz_id'] );
		$this->assertSame( 'Quiz Gamma', $result['quiz']['quiz_title'] );
		$this->assertSame( 80, $result['quiz']['result']['mark'] );
	}

}

class DataLoadersWPState {
	public static array $post_types = array();
	public static array $titles     = array();
}
