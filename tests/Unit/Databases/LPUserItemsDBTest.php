<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Databases;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Unit tests for LP_User_Items_DB::remove_items_of_user_course, using a fake wpdb that records queries.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
class LPUserItemsDBTest extends BrainMonkeyTestCase {

	/**
	 * @var object Fake wpdb.
	 */
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();

		defined( 'ADMIN_ROLE' ) || define( 'ADMIN_ROLE', 'administrator' );

		Functions\when( 'apply_filters' )->alias( static fn( $hook, $value ) => $value );
		Functions\when( 'absint' )->alias( static fn( $value ) => abs( (int) $value ) );

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
			public $queries            = [];
			public $col_result         = [];

			public function hide_errors() {}

			public function has_cap( $cap ) {
				return false;
			}

			public function prepare( $query, ...$args ) {
				if ( isset( $args[0] ) && is_array( $args[0] ) ) {
					$args = $args[0];
				}

				return vsprintf( $query, $args );
			}

			public function get_col( $query ) {
				$this->queries[] = $query;

				return $this->col_result;
			}

			public function query( $query ) {
				$this->queries[] = $query;

				return 2;
			}
		};

		$GLOBALS['wpdb'] = $this->wpdb;

		// Loaded after the fake wpdb is set: the DB file calls LP_Course_DB::getInstance() at file level.
		require_once LP_INC_PATH . 'class-lp-helper.php';
		require_once LP_INC_PATH . 'Databases/class-lp-db.php';
		require_once LP_INC_PATH . 'Databases/class-lp-course-db.php';
		require_once LP_INC_PATH . 'Databases/class-lp-user-items-db.php';
		require_once LP_INC_PATH . 'Filters/class-lp-filter.php';
		require_once LP_INC_PATH . 'Filters/class-lp-user-items-filter.php';

		// Reset singleton so each test uses its own fake wpdb (PHPUnit 9 ignores process-isolation attributes).
		( new \ReflectionProperty( LP_User_Items_DB::class, '_instance' ) )->setValue( null, null );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	private function make_filter( int $limit = -1 ): LP_User_Items_Filter {
		$filter            = new LP_User_Items_Filter();
		$filter->parent_id = 100;
		$filter->user_id   = 5;
		$filter->limit     = $limit;

		return $filter;
	}

	private function normalize( string $sql ): string {
		return trim( preg_replace( '/\s+/', ' ', $sql ) );
	}

	public function test_remove_items_of_user_course_deletes_child_meta_then_child_rows(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 5 );

		$this->wpdb->col_result = [ '11', '12' ];

		$result = LP_User_Items_DB::getInstance()->remove_items_of_user_course( $this->make_filter() );

		$this->assertSame( 2, $result );
		$this->assertCount( 3, $this->wpdb->queries );
		$this->assertSame(
			'SELECT user_item_id FROM wp_learnpress_user_items WHERE parent_id = 100',
			$this->normalize( $this->wpdb->queries[0] )
		);
		$this->assertSame(
			'DELETE FROM wp_learnpress_user_itemmeta WHERE 1=1 AND learnpress_user_item_id IN(11,12)',
			$this->normalize( $this->wpdb->queries[1] )
		);
		$this->assertSame(
			'DELETE FROM wp_learnpress_user_items WHERE 1=1 AND user_item_id IN(11,12)',
			$this->normalize( $this->wpdb->queries[2] )
		);
	}

	public function test_remove_items_of_user_course_never_touches_parent_row_meta(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 5 );

		$this->wpdb->col_result = [ '11' ];

		LP_User_Items_DB::getInstance()->remove_items_of_user_course( $this->make_filter() );

		foreach ( array_slice( $this->wpdb->queries, 1 ) as $query ) {
			$this->assertStringNotContainsString( '100', $query, 'Parent user course row/meta must be kept (e.g. _lp_retaken_count).' );
		}
	}

	public function test_remove_items_of_user_course_no_children_runs_no_delete(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 5 );

		$this->wpdb->col_result = [];

		$result = LP_User_Items_DB::getInstance()->remove_items_of_user_course( $this->make_filter() );

		$this->assertSame( 0, $result );
		$this->assertCount( 1, $this->wpdb->queries );
	}

	public function test_remove_items_of_user_course_applies_limit_to_select(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 5 );

		$this->wpdb->col_result = [];

		LP_User_Items_DB::getInstance()->remove_items_of_user_course( $this->make_filter( 3 ) );

		$this->assertSame(
			'SELECT user_item_id FROM wp_learnpress_user_items WHERE parent_id = 100 LIMIT 3',
			$this->normalize( $this->wpdb->queries[0] )
		);
	}

	public function test_remove_items_of_user_course_other_user_throws(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 9 );

		$this->expectException( Exception::class );

		try {
			LP_User_Items_DB::getInstance()->remove_items_of_user_course( $this->make_filter() );
		} finally {
			$this->assertCount( 0, $this->wpdb->queries );
		}
	}
}
