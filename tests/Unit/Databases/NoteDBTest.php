<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Databases;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Databases\NoteDB;
use LearnPress\Filters\NoteFilter;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use ReflectionProperty;

/**
 * Unit tests for NoteDB SQL building, using a fake wpdb that records queries.
 */
class NoteDBTest extends BrainMonkeyTestCase {

	/**
	 * @var object Fake wpdb.
	 */
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( '\\LP_Helper', false ) ) {
			require_once LP_INC_PATH . 'class-lp-helper.php';
		}

		Functions\when( 'apply_filters' )->alias( static fn( $hook, $value ) => $value );

		$this->wpdb = new class() {
			public $prefix     = 'wp_';
			public $users      = 'wp_users';
			public $posts      = 'wp_posts';
			public $postmeta   = 'wp_postmeta';
			public $options    = 'wp_options';
			public $terms      = 'wp_terms';
			public $term_relationships = 'wp_term_relationships';
			public $term_taxonomy      = 'wp_term_taxonomy';
			public $last_error = '';
			public $queries    = [];
			public $results    = [];
			public $affected   = 0;

			public function hide_errors() {}

			public function has_cap( $cap ) {
				return false;
			}

			public function esc_like( $text ) {
				return addcslashes( $text, '_%\\' );
			}

			public function prepare( $query, ...$args ) {
				if ( isset( $args[0] ) && is_array( $args[0] ) ) {
					$args = $args[0];
				}
				$query = str_replace( [ '%d', '%s' ], [ '%d', "'%s'" ], $query );

				return vsprintf( $query, $args );
			}

			public function get_results( $query ) {
				$this->queries[] = $query;

				return $this->results;
			}

			public function get_var( $query ) {
				$this->queries[] = $query;

				return 0;
			}

			public function query( $query ) {
				$this->queries[] = $query;

				return $this->affected;
			}
		};

		$GLOBALS['wpdb'] = $this->wpdb;

		// Reset singleton so it picks up the fake wpdb.
		$prop = new ReflectionProperty( NoteDB::class, '_instance' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	private function last_query(): string {
		return preg_replace( '/\s+/', ' ', (string) end( $this->wpdb->queries ) );
	}

	public function test_get_notes_builds_where_from_filter(): void {
		$filter                  = new NoteFilter();
		$filter->user_id         = 5;
		$filter->course_id       = 10;
		$filter->item_id         = 20;
		$filter->note_type       = 'highlight';
		$filter->run_query_count = false;

		NoteDB::getInstance()->get_notes( $filter );
		$sql = $this->last_query();

		$this->assertStringContainsString( 'FROM wp_learnpress_notes AS n', $sql );
		$this->assertStringContainsString( 'n.note_id,n.user_id', $sql );
		$this->assertStringContainsString( 'AND n.user_id = 5', $sql );
		$this->assertStringContainsString( 'AND n.course_id = 10', $sql );
		$this->assertStringContainsString( 'AND n.item_id = 20', $sql );
		$this->assertStringContainsString( "AND n.note_type = 'highlight'", $sql );
	}

	public function test_get_notes_restricts_to_course_ids_and_searches_text(): void {
		$filter                  = new NoteFilter();
		$filter->course_ids      = [ 3, 4 ];
		$filter->key_word        = '50%';
		$filter->run_query_count = false;

		NoteDB::getInstance()->get_notes( $filter );
		$sql = $this->last_query();

		$this->assertStringContainsString( 'AND n.course_id IN (3,4)', $sql );
		$this->assertStringContainsString( "n.content LIKE '%50\\%%'", $sql );
		$this->assertStringContainsString( "n.highlight_text LIKE '%50\\%%'", $sql );
	}

	public function test_join_details_selects_titles_and_searches_them(): void {
		$filter                  = new NoteFilter();
		$filter->join_details    = true;
		$filter->key_word        = 'abc';
		$filter->run_query_count = false;

		NoteDB::getInstance()->get_notes( $filter );
		$sql = $this->last_query();

		$this->assertStringContainsString( 'LEFT JOIN wp_users AS u ON u.ID = n.user_id', $sql );
		$this->assertStringContainsString( 'LEFT JOIN wp_posts AS c ON c.ID = n.course_id', $sql );
		$this->assertStringContainsString( 'LEFT JOIN wp_posts AS l ON l.ID = n.item_id', $sql );
		$this->assertStringContainsString( 'c.post_title AS course_title', $sql );
		$this->assertStringContainsString( "u.user_email LIKE '%abc%'", $sql );
		$this->assertStringContainsString( "l.post_title LIKE '%abc%'", $sql );
	}

	public function test_note_users_options_are_distinct_and_scoped(): void {
		$filter             = new NoteFilter();
		$filter->course_ids = [ 131 ];

		NoteDB::getInstance()->get_note_users( $filter );
		$sql = $this->last_query();

		$this->assertStringContainsString( 'SELECT DISTINCT n.user_id AS ID,u.display_name,u.user_email', $sql );
		$this->assertStringContainsString( 'AND n.course_id IN (131)', $sql );
		$this->assertStringNotContainsString( 'LIMIT', $sql );
	}

	public function test_get_stats_counts_distinct_students_and_courses(): void {
		$this->wpdb->results = [
			(object) [
				'total_notes'    => '4',
				'total_students' => '2',
				'total_courses'  => '1',
			],
		];

		$filter            = new NoteFilter();
		$filter->course_id = 10;
		$stats             = NoteDB::getInstance()->get_stats( $filter );
		$sql               = $this->last_query();

		$this->assertSame(
			[
				'total_notes'    => 4,
				'total_students' => 2,
				'total_courses'  => 1,
			],
			$stats
		);
		$this->assertStringContainsString( 'COUNT(DISTINCT n.user_id)', $sql );
		$this->assertStringContainsString( 'AND n.course_id = 10', $sql );
		$this->assertStringNotContainsString( 'LIMIT', $sql );
	}

	public function test_order_newest_first_breaks_ties_by_note_id(): void {
		$filter                  = new NoteFilter();
		$filter->run_query_count = false;
		NoteDB::getInstance()->order_newest_first( $filter );
		NoteDB::getInstance()->get_notes( $filter );

		$this->assertStringContainsString( 'ORDER BY n.created_at DESC, n.note_id DESC', $this->last_query() );
	}

	public function test_delete_notes_by_user(): void {
		$this->wpdb->affected = 3;

		$deleted = NoteDB::getInstance()->delete_notes_by( NoteFilter::COL_USER_ID, [ '7', 0, 8 ] );

		$this->assertSame( 3, $deleted );
		$this->assertStringContainsString( 'DELETE FROM wp_learnpress_notes WHERE 1=1 AND user_id IN (7,8)', $this->last_query() );
	}

	public function test_delete_notes_with_empty_ids_runs_no_query(): void {
		$this->assertSame( 0, NoteDB::getInstance()->delete_notes( [ 0, 'abc' ] ) );
		$this->assertSame( [], $this->wpdb->queries );
	}

	public function test_delete_notes_by_rejects_unknown_column(): void {
		$this->expectException( Exception::class );
		NoteDB::getInstance()->delete_notes_by( 'content', [ 1 ] );
	}
}
