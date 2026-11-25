<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Security;

use Exception;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;

/**
 * Regression: CoursePostModel::update_items_position() must not move an item
 * whose old/new section does not belong to the authorized course (IDOR,
 * lp-load-ajax=update_item_section_and_position).
 *
 * Static lookups are replaced with Mockery alias mocks, so each test runs in a
 * separate process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CourseItemsPositionIdorTest extends BrainMonkeyTestCase {

	const ATTACKER_COURSE_ID  = 10;
	const ATTACKER_SECTION_ID = 100;
	const VICTIM_SECTION_ID   = 200;
	const VICTIM_LESSON_ID    = 2001;

	/**
	 * @var Mockery\MockInterface
	 */
	private $section_model_alias;

	/**
	 * @var Mockery\MockInterface
	 */
	private $section_item_model_alias;

	protected function setUp(): void {
		parent::setUp();

		// Only sections of the attacker course exist for course ATTACKER_COURSE_ID.
		$this->section_model_alias = Mockery::mock( 'alias:LearnPress\Models\CourseSectionModel' );
		$section_model_alias       = $this->section_model_alias;
		$section_model_alias->shouldReceive( 'find' )->andReturnUsing(
			function ( $section_id, $course_id ) use ( $section_model_alias ) {
				if ( self::ATTACKER_SECTION_ID === $section_id && self::ATTACKER_COURSE_ID === $course_id ) {
					return $section_model_alias;
				}

				return false;
			}
		);

		$this->section_item_model_alias = Mockery::mock( 'alias:LearnPress\Models\CourseSectionItemModel' );
	}

	private function make_post_model(): CoursePostModel {
		$post_model = Mockery::mock( CoursePostModel::class )->makePartial();
		$post_model->shouldReceive( 'check_capabilities_update' )->andReturn( true );
		$post_model->shouldReceive( 'get_course_model' )->andReturn( Mockery::mock( CourseModel::class ) );
		$post_model->ID = self::ATTACKER_COURSE_ID;

		return $post_model;
	}

	public function test_update_items_position_rejects_old_section_of_other_course(): void {
		$this->section_item_model_alias->shouldNotReceive( 'get_item_model_from_db' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Section not found' );

		$this->make_post_model()->update_items_position(
			[
				'course_id'              => self::ATTACKER_COURSE_ID,
				'section_id_old_of_item' => self::VICTIM_SECTION_ID,
				'item_id_change'         => self::VICTIM_LESSON_ID,
				'section_id_new_of_item' => self::ATTACKER_SECTION_ID,
				'items_position'         => [ self::VICTIM_LESSON_ID ],
			]
		);
	}

	public function test_update_items_position_rejects_new_section_of_other_course(): void {
		$this->section_item_model_alias->shouldNotReceive( 'get_item_model_from_db' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Section not found' );

		$this->make_post_model()->update_items_position(
			[
				'section_id_old_of_item' => self::ATTACKER_SECTION_ID,
				'item_id_change'         => 5,
				'section_id_new_of_item' => self::VICTIM_SECTION_ID,
				'items_position'         => [ 5 ],
			]
		);
	}

	public function test_update_items_position_rejects_missing_section(): void {
		$this->section_item_model_alias->shouldNotReceive( 'get_item_model_from_db' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Section not found' );

		$this->make_post_model()->update_items_position(
			[
				'item_id_change' => 5,
				'items_position' => [ 5 ],
			]
		);
	}

	public function test_update_items_position_allows_sections_of_same_course(): void {
		// Guard passes; the item lookup runs (scoped to the course's own section).
		$this->section_item_model_alias->shouldReceive( 'get_item_model_from_db' )
			->once()
			->andReturnUsing(
				function ( $filter ) {
					$this->assertSame( self::ATTACKER_SECTION_ID, $filter->section_id );
					$this->assertSame( 5, $filter->item_id );

					return false;
				}
			);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Item not found in section' );

		$this->make_post_model()->update_items_position(
			[
				'section_id_old_of_item' => (string) self::ATTACKER_SECTION_ID,
				'item_id_change'         => '5',
				'section_id_new_of_item' => self::ATTACKER_SECTION_ID,
				'items_position'         => [ 5 ],
			]
		);
	}
}
