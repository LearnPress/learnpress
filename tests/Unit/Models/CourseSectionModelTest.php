<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models;

use Exception;
use LearnPress\Models\CourseSectionModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use stdClass;

/**
 * Unit tests for CourseSectionModel.
 *
 * Focus on pure object behavior and permission checks by mocking
 * model dependencies via anonymous subclasses.
 */
class CourseSectionModelTest extends BrainMonkeyTestCase {

	public function test_constructor_with_null_initialises_defaults(): void {
		$model = new CourseSectionModel();

		$this->assertSame( 0, $model->section_id );
		$this->assertSame( '', $model->section_name );
		$this->assertSame( 0, $model->section_course_id );
		$this->assertSame( 0, $model->section_order );
		$this->assertSame( '', $model->section_description );
	}

	public function test_constructor_maps_array_data(): void {
		$model = new CourseSectionModel(
			[
				'section_id'          => 12,
				'section_name'        => 'Section A',
				'section_course_id'   => 88,
				'section_order'       => 3,
				'section_description' => 'Desc',
			]
		);

		$this->assertSame( 12, $model->section_id );
		$this->assertSame( 'Section A', $model->section_name );
		$this->assertSame( 88, $model->section_course_id );
		$this->assertSame( 3, $model->section_order );
		$this->assertSame( 'Desc', $model->section_description );
	}

	public function test_constructor_maps_object_data(): void {
		$data                      = new stdClass();
		$data->section_id          = 9;
		$data->section_name        = 'Obj Section';
		$data->section_course_id   = 100;
		$data->section_order       = 2;
		$data->section_description = 'Object Desc';

		$model = new CourseSectionModel( $data );

		$this->assertSame( 9, $model->section_id );
		$this->assertSame( 'Obj Section', $model->section_name );
		$this->assertSame( 100, $model->section_course_id );
		$this->assertSame( 2, $model->section_order );
		$this->assertSame( 'Object Desc', $model->section_description );
	}

	public function test_map_to_object_ignores_unknown_properties(): void {
		$model = new CourseSectionModel();

		$model->map_to_object(
			[
				'section_id'       => 33,
				'unknown_property' => 'ignored',
			]
		);

		$this->assertSame( 33, $model->section_id );
		$this->assertFalse( property_exists( $model, 'unknown_property' ) );
	}

	public function test_get_section_id_returns_section_id_property(): void {
		$model             = new CourseSectionModel();
		$model->section_id = 321;

		$this->assertSame( 321, $model->get_section_id() );
	}

	public function test_check_permission_passes_when_capability_is_true(): void {
		$course_post_model = new class() {
			public function check_capabilities_update(): bool {
				return true;
			}
		};

		$model = new class( $course_post_model ) extends CourseSectionModel {
			private $course_post_model_for_test;

			public function __construct( $course_post_model_for_test ) {
				$this->course_post_model_for_test = $course_post_model_for_test;
				parent::__construct();
			}

			public function get_course_post_model() {
				return $this->course_post_model_for_test;
			}
		};

		$this->expectNotToPerformAssertions();
		$model->check_permission();
	}

	public function test_check_permission_throws_exception_when_capability_is_false(): void {
		$course_post_model = new class() {
			public function check_capabilities_update(): bool {
				return false;
			}
		};

		$model = new class( $course_post_model ) extends CourseSectionModel {
			private $course_post_model_for_test;

			public function __construct( $course_post_model_for_test ) {
				$this->course_post_model_for_test = $course_post_model_for_test;
				parent::__construct();
			}

			public function get_course_post_model() {
				return $this->course_post_model_for_test;
			}
		};

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'You do not have permission to delete section' );

		$model->check_permission();
	}

	public function test_check_permission_throws_exception_when_course_post_model_is_missing(): void {
		$model = new class() extends CourseSectionModel {
			public function get_course_post_model() {
				return false;
			}
		};

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'You do not have permission to delete section' );

		$model->check_permission();
	}
}
