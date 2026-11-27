<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models;

use Brain\Monkey\Functions;
use LearnPress\Models\CourseModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use LP_Course_DB;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Unit tests for CourseModel::get_section_items_for_edit().
 * LP_Course_DB singleton is replaced by a Mockery mock returning fixed rows.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
class CourseModelSectionItemsForEditTest extends BrainMonkeyTestCase {

	/**
	 * @var Mockery\MockInterface
	 */
	private $db;

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['wpdb'] = new class() {
			public $prefix = 'wp_';
		};

		if ( ! class_exists( '\\LP_Helper', false ) ) {
			require_once LP_INC_PATH . 'class-lp-helper.php';
		}
		require_once LP_INC_PATH . 'Databases/class-lp-db.php';
		require_once LP_INC_PATH . 'Databases/class-lp-course-db.php';

		Functions\when( 'apply_filters' )->alias( static fn( $hook, $value ) => $value );

		$this->db = Mockery::mock( LP_Course_DB::class );
		( new \ReflectionProperty( LP_Course_DB::class, '_instance' ) )->setValue( null, $this->db );
	}

	protected function tearDown(): void {
		( new \ReflectionProperty( LP_Course_DB::class, '_instance' ) )->setValue( null, null );
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	private function section( int $id, int $order, string $name = '' ): object {
		return (object) [
			'section_id'          => $id,
			'section_order'       => $order,
			'section_name'        => $name ? $name : "Section $id",
			'section_description' => '',
		];
	}

	private function row( int $section_id, int $section_order, int $item_id, int $item_order, string $status ): object {
		return (object) [
			'section_id'          => $section_id,
			'section_order'       => $section_order,
			'section_name'        => "Section $section_id",
			'section_description' => '',
			'item_id'             => $item_id,
			'item_order'          => $item_order,
			'item_type'           => 'lp_lesson',
			'post_status'         => $status,
			'post_title'          => "Item $item_id &amp; co",
		];
	}

	public function test_get_section_items_for_edit_keeps_hidden_items_in_order_with_status(): void {
		$this->db->shouldReceive( 'get_sections' )->with( 5 )->andReturn(
			[
				1 => $this->section( 1, 1 ),
				2 => $this->section( 2, 2 ),
			]
		);
		$this->db->shouldReceive( 'get_sections_and_items_for_edit' )
			->with( 5 )
			->andReturn(
				[
					$this->row( 1, 1, 11, 1, 'publish' ),
					$this->row( 1, 1, 13, 3, 'publish' ),
					$this->row( 1, 1, 12, 2, 'draft' ),
					$this->row( 2, 2, 21, 1, 'trash' ),
				]
			);
		$this->db->shouldNotReceive( 'get_full_sections_and_items_course' );

		$course   = new CourseModel( [ 'ID' => 5 ] );
		$sections = $course->get_section_items_for_edit();

		$this->assertCount( 2, $sections );
		$this->assertSame( [ 11, 12, 13 ], array_map( static fn( $i ) => $i->item_id, $sections[0]->items ) );
		$this->assertSame( 'draft', $sections[0]->items[1]->post_status );
		$this->assertSame( 'Item 12 & co', $sections[0]->items[1]->title );
		$this->assertSame( 'trash', $sections[1]->items[0]->post_status );
	}

	public function test_get_section_items_for_edit_keeps_empty_sections_and_sets_edit_key(): void {
		$this->db->shouldReceive( 'get_sections' )->andReturn(
			[
				1 => $this->section( 1, 1 ),
				2 => $this->section( 2, 2, 'Empty' ),
			]
		);
		$this->db->shouldReceive( 'get_sections_and_items_for_edit' )->once()->andReturn(
			[ $this->row( 1, 1, 11, 1, 'pending' ) ]
		);

		$course   = new CourseModel( [ 'ID' => 5 ] );
		$sections = $course->get_section_items_for_edit();

		$this->assertCount( 2, $sections );
		$this->assertSame( 'Empty', $sections[1]->section_name );
		$this->assertSame( [], $sections[1]->items );
		// Stored on key sections_items_edit (saved to json), never on frontend sections_items.
		$this->assertSame( $sections, $course->sections_items_edit );
		$this->assertNull( $course->sections_items );
	}

	public function test_get_section_items_for_edit_reads_edit_key_when_sections_items_set(): void {
		$this->db->shouldNotReceive( 'get_sections' );
		$this->db->shouldNotReceive( 'get_sections_and_items_for_edit' );

		$cached = [ (object) [ 'section_id' => 1, 'items' => [] ] ];
		$course = new CourseModel(
			[
				'ID'                  => 5,
				'sections_items'      => [],
				'sections_items_edit' => $cached,
			]
		);

		$this->assertSame( $cached, $course->get_section_items_for_edit() );
	}

	public function test_get_section_items_for_edit_queries_db_when_sections_items_reset(): void {
		$this->db->shouldReceive( 'get_sections' )->once()->andReturn( [ 1 => $this->section( 1, 1 ) ] );
		$this->db->shouldReceive( 'get_sections_and_items_for_edit' )->once()->andReturn(
			[ $this->row( 1, 1, 11, 1, 'draft' ) ]
		);

		// sections_items null = curriculum changed, edit key is stale.
		$course = new CourseModel(
			[
				'ID'                  => 5,
				'sections_items'      => null,
				'sections_items_edit' => [ (object) [ 'section_id' => 99, 'items' => [] ] ],
			]
		);

		$sections = $course->get_section_items_for_edit();

		$this->assertSame( 1, $sections[0]->section_id );
		$this->assertSame( 'draft', $sections[0]->items[0]->post_status );
	}
}
