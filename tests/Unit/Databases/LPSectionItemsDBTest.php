<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Databases;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use LP_Section_Items_DB;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Unit tests for LP_Section_Items_DB::delete_item_not_in_tb_post, using a fake wpdb that records queries.
 * Items not publish (draft, pending, private, future, trash) must keep their rows in sections.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
class LPSectionItemsDBTest extends BrainMonkeyTestCase {

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

			public function query( $query ) {
				$this->queries[] = $query;

				return 1;
			}
		};

		$GLOBALS['wpdb'] = $this->wpdb;

		require_once LP_INC_PATH . 'class-lp-helper.php';
		require_once LP_INC_PATH . 'Databases/class-lp-db.php';
		require_once LP_INC_PATH . 'Databases/class-lp-section-items-db.php';

		LP_Section_Items_DB::$_instance = null;
	}

	protected function tearDown(): void {
		LP_Section_Items_DB::$_instance = null;
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	public function test_delete_item_not_in_tb_post_only_deletes_items_not_exists(): void {
		LP_Section_Items_DB::getInstance()->delete_item_not_in_tb_post( 10 );

		$sql = trim( preg_replace( '/\s+/', ' ', (string) end( $this->wpdb->queries ) ) );

		$this->assertStringContainsString( 'DELETE si FROM wp_learnpress_section_items AS si', $sql );
		$this->assertStringContainsString( 'AND s.section_course_id = 10', $sql );
		$this->assertStringContainsString( 'LEFT JOIN wp_posts AS p ON si.item_id = p.ID', $sql );
		$this->assertStringContainsString( 'WHERE p.ID IS NULL', $sql );
		// Must not remove items by status (draft, trash...).
		$this->assertStringNotContainsString( 'post_status', $sql );
	}
}
