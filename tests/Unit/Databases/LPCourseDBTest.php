<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Databases;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use LP_Course_DB;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Unit tests for LP_Course_DB::get_sections_and_items_for_edit, using a fake wpdb that records queries.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
class LPCourseDBTest extends BrainMonkeyTestCase {

	/**
	 * @var object Fake wpdb.
	 */
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'apply_filters' )->alias( static fn( $hook, $value ) => $value );

		$this->wpdb = new class() {
			public $prefix             = 'wp_';
			public $users              = 'wp_users';
			public $usermeta           = 'wp_usermeta';
			public $posts              = 'wp_posts';
			public $postmeta           = 'wp_postmeta';
			public $options            = 'wp_options';
			public $terms              = 'wp_terms';
			public $term_relationships = 'wp_term_relationships';
			public $term_taxonomy      = 'wp_term_taxonomy';
			public $last_error         = '';
			public $queries            = [];
			public $results            = [];

			public function hide_errors() {}

			public function has_cap( $cap ) {
				return false;
			}

			public function prepare( $query, ...$args ) {
				if ( isset( $args[0] ) && is_array( $args[0] ) ) {
					$args = $args[0];
				}
				$query = str_replace( '%s', "'%s'", $query );

				return vsprintf( $query, $args );
			}

			public function get_results( $query ) {
				$this->queries[] = $query;

				return $this->results;
			}
		};

		$GLOBALS['wpdb'] = $this->wpdb;

		require_once LP_INC_PATH . 'class-lp-helper.php';
		require_once LP_INC_PATH . 'Databases/class-lp-db.php';
		require_once LP_INC_PATH . 'Databases/class-lp-course-db.php';

		// Reset singleton so each test uses its own fake wpdb.
		( new \ReflectionProperty( LP_Course_DB::class, '_instance' ) )->setValue( null, null );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	private function last_query(): string {
		return trim( preg_replace( '/\s+/', ' ', (string) end( $this->wpdb->queries ) ) );
	}

	public function test_get_sections_and_items_for_edit_gets_all_statuses(): void {
		$this->wpdb->results = [ (object) [ 'item_id' => 1 ] ];

		$rs  = LP_Course_DB::getInstance()->get_sections_and_items_for_edit( 10 );
		$sql = $this->last_query();

		$this->assertSame( $this->wpdb->results, $rs );
		$this->assertStringContainsString( 'p.post_status, p.post_title', $sql );
		$this->assertStringContainsString( 'WHERE s.section_course_id = 10', $sql );
		$this->assertStringContainsString( 'ORDER BY s.section_order, si.item_order', $sql );
		// No filter by status: items of all statuses are returned.
		$this->assertStringNotContainsString( 'p.post_status IN', $sql );
		$this->assertStringNotContainsString( 'p.post_status =', $sql );
	}
}
