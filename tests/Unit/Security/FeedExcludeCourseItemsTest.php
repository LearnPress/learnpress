<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Security;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ReflectionClass;

/**
 * Regression: course items (lesson, quiz, question) and their comments
 * must not be exposed through WP core RSS/Atom feeds.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
class FeedExcludeCourseItemsTest extends BrainMonkeyTestCase {
	private $controller;
	private $filter = 'posts_where';

	protected function setUp(): void {
		parent::setUp();
		defined( 'LP_QUESTION_CPT' ) || define( 'LP_QUESTION_CPT', 'lp_question' );

		if ( ! class_exists( 'WP_Query', false ) ) {
			eval( 'class WP_Query { public $feed = false; public function is_feed() { return $this->feed; } }' );
		}
		if ( ! class_exists( 'LP_Helper', false ) ) {
			eval( 'class LP_Helper { public static function format_query_IN( array $arr ): string { return join( ",", array_fill( 0, count( $arr ), "%s" ) ); } }' );
		}
		require_once LP_ABSPATH . 'inc/class-lp-page-controller.php';

		$GLOBALS['wpdb'] = new class() {
			public $posts    = 'wp_posts';
			public $comments = 'wp_comments';

			public function prepare( $query, $args ) {
				return vsprintf( str_replace( '%s', "'%s'", $query ), $args );
			}
		};

		Functions\when( 'current_filter' )->alias( fn() => $this->filter );
		Functions\when( 'sanitize_key' )->alias( static fn( $key ) => strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $key ) ) );

		$this->controller = ( new ReflectionClass( \LP_Page_Controller::class ) )->newInstanceWithoutConstructor();
	}

	private function query( bool $is_feed ): \WP_Query {
		$q       = new \WP_Query();
		$q->feed = $is_feed;

		return $q;
	}

	public function test_posts_where_excludes_course_items_on_feed(): void {
		$where = $this->controller->exclude_course_items_from_feed( ' AND 1=1', $this->query( true ) );

		$this->assertSame(
			" AND 1=1 AND wp_posts.post_type NOT IN ('lp_lesson','lp_quiz','lp_question','lp_assignment')",
			$where
		);
	}

	public function test_posts_where_untouched_when_not_feed(): void {
		$this->assertSame( ' AND 1=1', $this->controller->exclude_course_items_from_feed( ' AND 1=1', $this->query( false ) ) );
	}

	public function test_comment_feed_where_excludes_comments_of_course_items_without_posts_join(): void {
		$this->filter = 'comment_feed_where';
		$cwhere       = $this->controller->exclude_course_items_from_feed(
			"WHERE comment_post_ID = '5' AND comment_approved = '1'",
			$this->query( true )
		);

		$this->assertSame(
			"WHERE comment_post_ID = '5' AND comment_approved = '1' AND wp_comments.comment_post_ID NOT IN ( SELECT ID FROM wp_posts WHERE post_type IN ('lp_lesson','lp_quiz','lp_question','lp_assignment') )",
			$cwhere
		);
	}

	public function test_comment_feed_where_untouched_when_not_feed(): void {
		$this->filter = 'comment_feed_where';
		$this->assertSame( 'WHERE 1', $this->controller->exclude_course_items_from_feed( 'WHERE 1', $this->query( false ) ) );
	}

	public function test_filter_values_are_sanitized_and_empty_list_disables_exclusion(): void {
		Filters\expectApplied( 'learn-press/feed/exclude-post-types' )->once()->andReturn( [ "lp_lesson') OR 1=1 -- " ] );
		$this->assertSame(
			" AND wp_posts.post_type NOT IN ('lp_lessonor11--')",
			$this->controller->exclude_course_items_from_feed( '', $this->query( true ) )
		);

		Filters\expectApplied( 'learn-press/feed/exclude-post-types' )->once()->andReturn( [] );
		$this->assertSame( 'X', $this->controller->exclude_course_items_from_feed( 'X', $this->query( true ) ) );
	}
}
