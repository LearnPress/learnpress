<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Databases;

use Brain\Monkey\Functions;
use LearnPress\Databases\UserDB;
use LearnPress\Filters\UserFilter;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ReflectionProperty;

/**
 * Unit tests for UserDB SQL building, using a fake wpdb that records queries.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
class UserDBTest extends BrainMonkeyTestCase {

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
			public $usermeta   = 'wp_usermeta';
			public $posts      = 'wp_posts';
			public $postmeta   = 'wp_postmeta';
			public $options    = 'wp_options';
			public $terms      = 'wp_terms';
			public $term_relationships = 'wp_term_relationships';
			public $term_taxonomy      = 'wp_term_taxonomy';
			public $last_error = '';
			public $queries    = [];
			public $results    = [];

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

				return 7;
			}
		};

		$GLOBALS['wpdb'] = $this->wpdb;

		$prop = new ReflectionProperty( UserDB::class, '_instance' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	private function sql( int $index = 0 ): string {
		return preg_replace( '/\s+/', ' ', (string) $this->wpdb->queries[ $index ] );
	}

	public function test_roles_join_usermeta_capabilities(): void {
		$filter                  = new UserFilter();
		$filter->roles           = [ 'lp_teacher', 'administrator' ];
		$filter->run_query_count = false;

		UserDB::getInstance()->get_users( $filter );
		$sql = $this->sql();

		$this->assertStringContainsString( "INNER JOIN wp_usermeta AS um ON um.user_id = u.ID AND um.meta_key = 'wp_capabilities'", $sql );
		$this->assertStringContainsString( "um.meta_value LIKE '%\"lp\\_teacher\"%' OR um.meta_value LIKE '%\"administrator\"%'", $sql );
	}

	public function test_search_and_exclude(): void {
		$filter                   = new UserFilter();
		$filter->search           = 'a_b';
		$filter->exclude_ids      = [ 1, 2 ];
		$filter->run_query_count  = false;

		UserDB::getInstance()->get_users( $filter );
		$sql = $this->sql();

		$this->assertStringContainsString( "u.user_login LIKE '%a\\_b%'", $sql );
		$this->assertStringContainsString( "u.display_name LIKE '%a\\_b%'", $sql );
		$this->assertStringContainsString( 'AND u.ID NOT IN (1, 2)', $sql );
	}

	public function test_order_by_whitelist_and_pagination(): void {
		$filter                  = new UserFilter();
		$filter->order_by        = 'display_name';
		$filter->order           = 'asc';
		$filter->limit           = 10;
		$filter->page            = 3;
		$filter->run_query_count = false;

		UserDB::getInstance()->get_users( $filter );
		$sql = $this->sql();

		$this->assertStringContainsString( 'ORDER BY u.display_name ASC', $sql );
		$this->assertStringContainsString( 'LIMIT 20, 10', $sql );
	}

	public function test_order_by_unknown_column_is_dropped(): void {
		$filter                  = new UserFilter();
		$filter->order_by        = 'user_pass; DROP TABLE x';
		$filter->run_query_count = false;

		UserDB::getInstance()->get_users( $filter );

		$this->assertStringNotContainsString( 'ORDER BY', $this->sql() );
		$this->assertStringNotContainsString( 'DROP', $this->sql() );
	}

	public function test_total_rows_returned_by_reference(): void {
		$filter = new UserFilter();
		$total  = 0;

		UserDB::getInstance()->get_users( $filter, $total );

		$this->assertSame( 7, $total );
	}

	public function test_caller_join_and_alias_column_order_by_pass_through(): void {
		$filter                  = new UserFilter();
		$filter->join[]          = 'INNER JOIN wp_posts AS p ON p.post_author = u.ID';
		$filter->fields[]        = 'p.ID';
		$filter->order_by        = 'lp_c.total_courses';
		$filter->order           = 'DESC';
		$filter->run_query_count = false;

		UserDB::getInstance()->get_users( $filter );
		$sql = $this->sql();

		$this->assertStringContainsString( 'INNER JOIN wp_posts AS p ON p.post_author = u.ID', $sql );
		$this->assertStringContainsString( 'ORDER BY lp_c.total_courses DESC', $sql );
	}

	public function test_order_by_unsafe_alias_column_is_dropped(): void {
		$filter                  = new UserFilter();
		$filter->order_by        = 'a.b, (SELECT 1)';
		$filter->run_query_count = false;

		UserDB::getInstance()->get_users( $filter );

		$this->assertStringNotContainsString( 'ORDER BY', $this->sql() );
	}
}
