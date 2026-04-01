<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models;

use Exception;
use LearnPress\Models\CourseSectionItemModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use stdClass;

/**
 * Unit tests for CourseSectionItemModel.
 *
 * Strategy: test pure object behavior (construction, mapping, getters,
 * permission checks) without touching the database or WP core.
 * DB-dependent methods (find, save, delete, clean_caches) are excluded
 * from unit scope and belong to integration tests.
 */
class CourseSectionItemModelTest extends BrainMonkeyTestCase {

	// -------------------------------------------------------------------------
	// 1. Construction & defaults
	// -------------------------------------------------------------------------

	public function test_constructor_with_null_initialises_defaults(): void {
		$model = new CourseSectionItemModel();

		$this->assertSame( 0, $model->get_section_item_id() );
		$this->assertSame( '', $model->section_id );
		$this->assertSame( 0, $model->item_id );
		$this->assertSame( 0, $model->item_order );
		$this->assertSame( '', $model->item_type );
		$this->assertSame( 0, $model->section_course_id );
	}

	public function test_constructor_maps_array_data(): void {
		$model = new CourseSectionItemModel(
			[
				'section_item_id'   => 7,
				'section_id'        => 3,
				'item_id'           => 42,
				'item_order'        => 2,
				'item_type'         => 'lp_lesson',
				'section_course_id' => 99,
			]
		);

		$this->assertSame( 7, $model->get_section_item_id() );
		$this->assertSame( 3, $model->section_id );
		$this->assertSame( 42, $model->item_id );
		$this->assertSame( 2, $model->item_order );
		$this->assertSame( 'lp_lesson', $model->item_type );
		$this->assertSame( 99, $model->section_course_id );
	}

	public function test_constructor_maps_object_data(): void {
		$data                    = new stdClass();
		$data->section_item_id   = 15;
		$data->section_id        = 5;
		$data->item_id           = 88;
		$data->item_order        = 4;
		$data->item_type         = 'lp_quiz';
		$data->section_course_id = 200;

		$model = new CourseSectionItemModel( $data );

		$this->assertSame( 15, $model->get_section_item_id() );
		$this->assertSame( 5, $model->section_id );
		$this->assertSame( 88, $model->item_id );
		$this->assertSame( 4, $model->item_order );
		$this->assertSame( 'lp_quiz', $model->item_type );
		$this->assertSame( 200, $model->section_course_id );
	}

	// -------------------------------------------------------------------------
	// 2. map_to_object
	// -------------------------------------------------------------------------

	public function test_map_to_object_ignores_unknown_properties(): void {
		$model = new CourseSectionItemModel();

		$model->map_to_object(
			[
				'item_id'          => 55,
				'unknown_property' => 'should_be_ignored',
			]
		);

		$this->assertSame( 55, $model->item_id );
		$this->assertFalse( property_exists( $model, 'unknown_property' ) );
	}

	public function test_map_to_object_returns_self(): void {
		$model  = new CourseSectionItemModel();
		$result = $model->map_to_object( [ 'item_id' => 10 ] );

		$this->assertSame( $model, $result );
	}

	public function test_map_to_object_partial_update_preserves_other_fields(): void {
		$model           = new CourseSectionItemModel();
		$model->item_id  = 100;
		$model->item_type = 'lp_lesson';

		$model->map_to_object( [ 'item_order' => 3 ] );

		$this->assertSame( 100, $model->item_id );
		$this->assertSame( 'lp_lesson', $model->item_type );
		$this->assertSame( 3, $model->item_order );
	}

	// -------------------------------------------------------------------------
	// 3. get_section_item_id
	// -------------------------------------------------------------------------

	public function test_get_section_item_id_returns_zero_by_default(): void {
		$model = new CourseSectionItemModel();

		$this->assertSame( 0, $model->get_section_item_id() );
	}

	public function test_get_section_item_id_returns_value_set_via_constructor(): void {
		$model = new CourseSectionItemModel( [ 'section_item_id' => 123 ] );

		$this->assertSame( 123, $model->get_section_item_id() );
	}

	// -------------------------------------------------------------------------
	// 4. check_permission
	// -------------------------------------------------------------------------

	public function test_check_permission_passes_when_capability_is_true(): void {
		// Use a real CoursePostModel subclass stub that overrides check_capabilities_update.
		$course_post_model = $this->createStub( CoursePostModel::class );
		$course_post_model->method( 'check_capabilities_update' )->willReturn( true );

		$model = new class( $course_post_model ) extends CourseSectionItemModel {
			private ?CoursePostModel $course_post_model_for_test;

			public function __construct( ?CoursePostModel $course_post_model_for_test ) {
				$this->course_post_model_for_test = $course_post_model_for_test;
				parent::__construct();
			}

			public function get_course_post_model(): ?CoursePostModel {
				return $this->course_post_model_for_test;
			}
		};

		$this->expectNotToPerformAssertions();
		$model->check_permission();
	}

	public function test_check_permission_throws_exception_when_capability_is_false(): void {
		$course_post_model = $this->createStub( CoursePostModel::class );
		$course_post_model->method( 'check_capabilities_update' )->willReturn( false );

		$model = new class( $course_post_model ) extends CourseSectionItemModel {
			private ?CoursePostModel $course_post_model_for_test;

			public function __construct( ?CoursePostModel $course_post_model_for_test ) {
				$this->course_post_model_for_test = $course_post_model_for_test;
				parent::__construct();
			}

			public function get_course_post_model(): ?CoursePostModel {
				return $this->course_post_model_for_test;
			}
		};

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'You do not have permission to delete section item.' );

		$model->check_permission();
	}

	public function test_check_permission_throws_exception_when_course_post_model_is_null(): void {
		$model = new class() extends CourseSectionItemModel {
			public function get_course_post_model(): ?CoursePostModel {
				return null;
			}
		};

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'You do not have permission to delete section item.' );

		$model->check_permission();
	}
}
