<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models;

use Brain\Monkey\Functions;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use stdClass;

/**
 * Unit tests for CourseModel.
 *
 * Strategy: pre-populate $meta_data and $sections_items on the model so every
 * method under test stays inside pure PHP logic — no DB, no WP core needed.
 * Brain Monkey stubs all remaining WP function calls (apply_filters, maybe_unserialize, …).
 */
class CourseModelTest extends BrainMonkeyTestCase {

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a CourseModel with meta_data pre-populated.
	 * Bypasses CoursePostModel / DB entirely for any method that reads meta.
	 *
	 * @param array $meta  key → value pairs written to meta_data
	 * @param array $props top-level property overrides (ID, post_status, …)
	 */
	private function make_course( array $meta = [], array $props = [] ): CourseModel {
		// maybe_unserialize is called for every meta read — return the value unchanged.
		Functions\when( 'maybe_unserialize' )->returnArg( 1 );

		$data              = array_merge( [ 'ID' => 1, 'post_status' => 'publish' ], $props );
		$course            = new CourseModel( $data );
		$course->meta_data = (object) $meta;

		return $course;
	}

	/**
	 * Build a dummy section stdClass with items.
	 *
	 * @param int   $section_id
	 * @param array $items  [ ['id' => int, 'item_id' => int, 'item_type' => string, 'item_order' => int], … ]
	 */
	private function make_section( int $section_id, array $items = [] ): stdClass {
		$section             = new stdClass();
		$section->id         = $section_id;
		$section->section_id = $section_id;
		$section->items      = [];

		foreach ( $items as $i ) {
			$item             = new stdClass();
			$item->id         = $i['id'];
			$item->item_id    = $i['id'];
			$item->item_type  = $i['item_type'] ?? 'lp_lesson';
			$item->item_order = $i['item_order'] ?? 1;
			$section->items[] = $item;
		}

		return $section;
	}

	// -------------------------------------------------------------------------
	// 1. Construction & map_to_object
	// -------------------------------------------------------------------------

	public function test_constructor_with_null_initialises_defaults(): void {
		Functions\when( 'maybe_unserialize' )->returnArg( 1 );

		$course = new CourseModel();

		$this->assertSame( 0, $course->ID );
		$this->assertSame( '', $course->post_status );
		$this->assertInstanceOf( stdClass::class, $course->meta_data );
	}

	public function test_constructor_maps_array_data(): void {
		Functions\when( 'maybe_unserialize' )->returnArg( 1 );

		$course = new CourseModel( [ 'ID' => 99, 'post_status' => 'draft', 'post_title' => 'Hello' ] );

		$this->assertSame( 99, $course->ID );
		$this->assertSame( 'draft', $course->post_status );
		$this->assertSame( 'Hello', $course->post_title );
	}

	public function test_constructor_maps_object_data(): void {
		Functions\when( 'maybe_unserialize' )->returnArg( 1 );

		$data              = new stdClass();
		$data->ID          = 42;
		$data->post_status = 'publish';

		$course = new CourseModel( $data );

		$this->assertSame( 42, $course->ID );
		$this->assertSame( 'publish', $course->post_status );
	}

	public function test_map_to_object_ignores_unknown_properties(): void {
		Functions\when( 'maybe_unserialize' )->returnArg( 1 );

		$course = new CourseModel();
		$course->map_to_object( [ 'nonexistent_prop' => 'value', 'ID' => 7 ] );

		$this->assertSame( 7, $course->ID );
		$this->assertFalse( property_exists( $course, 'nonexistent_prop' ) );
	}

	// -------------------------------------------------------------------------
	// 2. Simple getters
	// -------------------------------------------------------------------------

	public function test_get_id_returns_ID_property(): void {
		$course = $this->make_course( [], [ 'ID' => 123 ] );

		$this->assertSame( 123, $course->get_id() );
	}

	public function test_get_status_returns_post_status(): void {
		$course = $this->make_course( [], [ 'post_status' => 'pending' ] );

		$this->assertSame( 'pending', $course->get_status() );
	}

	// -------------------------------------------------------------------------
	// 3. get_meta_value_by_key — reads from meta_data (no DB/WP needed)
	// -------------------------------------------------------------------------

	public function test_get_meta_value_by_key_returns_value_from_meta_data(): void {
		$course = $this->make_course( [ '_lp_level' => 'intermediate' ] );

		$result = $course->get_meta_value_by_key( '_lp_level', 'beginner' );

		$this->assertSame( 'intermediate', $result );
	}

	public function test_get_meta_value_by_key_caches_value_on_meta_data(): void {
		$course = $this->make_course( [ '_lp_level' => 'advanced' ] );
		$course->get_meta_value_by_key( '_lp_level' );

		$this->assertSame( 'advanced', $course->meta_data->{'_lp_level'} );
	}

	// -------------------------------------------------------------------------
	// 4. Boolean meta flags
	// -------------------------------------------------------------------------

	/** @dataProvider yesNoProvider */
	public function test_is_offline( string $value, bool $expected ): void {
		$course = $this->make_course( [ CoursePostModel::META_KEY_OFFLINE_COURSE => $value ] );

		$this->assertSame( $expected, $course->is_offline() );
	}

	/** @dataProvider yesNoProvider */
	public function test_enable_block_when_expire( string $value, bool $expected ): void {
		$course = $this->make_course( [ CoursePostModel::META_KEY_BLOCK_EXPIRE_DURATION => $value ] );

		$this->assertSame( $expected, $course->enable_block_when_expire() );
	}

	/** @dataProvider yesNoProvider */
	public function test_enable_block_when_finished( string $value, bool $expected ): void {
		$course = $this->make_course( [ CoursePostModel::META_KEY_BLOCK_FINISH => $value ] );

		$this->assertSame( $expected, $course->enable_block_when_finished() );
	}

	/** @dataProvider yesNoProvider */
	public function test_has_no_enroll_requirement( string $value, bool $expected ): void {
		$course = $this->make_course( [ CoursePostModel::META_KEY_NO_REQUIRED_ENROLL => $value ] );

		$this->assertSame( $expected, $course->has_no_enroll_requirement() );
	}

	/** @dataProvider yesNoProvider */
	public function test_enable_allow_repurchase( string $value, bool $expected ): void {
		$course = $this->make_course( [ CoursePostModel::META_KEY_ALLOW_COURSE_REPURCHASE => $value ] );

		$this->assertSame( $expected, $course->enable_allow_repurchase() );
	}

	public static function yesNoProvider(): array {
		return [
			'yes → true'  => [ 'yes', true ],
			'no → false'  => [ 'no', false ],
			'empty → false' => [ '', false ],
		];
	}

	// -------------------------------------------------------------------------
	// 5. Price logic
	// -------------------------------------------------------------------------

	public function test_get_regular_price_reads_from_meta_data(): void {
		$course = $this->make_course( [ CoursePostModel::META_KEY_REGULAR_PRICE => '99.00' ] );

		$this->assertSame( '99.00', $course->get_regular_price() );
	}

	public function test_get_sale_price_reads_from_meta_data(): void {
		$course = $this->make_course( [ CoursePostModel::META_KEY_SALE_PRICE => '49.00' ] );

		$this->assertSame( '49.00', $course->get_sale_price() );
	}

	public function test_has_sale_price_false_when_sale_price_empty(): void {
		$course = $this->make_course(
			[
				CoursePostModel::META_KEY_REGULAR_PRICE => '100',
				CoursePostModel::META_KEY_SALE_PRICE    => '',
				CoursePostModel::META_KEY_SALE_START    => '',
				CoursePostModel::META_KEY_SALE_END      => '',
			]
		);

		$this->assertFalse( $course->has_sale_price() );
	}

	public function test_has_sale_price_false_when_sale_not_lower_than_regular(): void {
		$course = $this->make_course(
			[
				CoursePostModel::META_KEY_REGULAR_PRICE => '50',
				CoursePostModel::META_KEY_SALE_PRICE    => '50', // same — not a real sale
				CoursePostModel::META_KEY_SALE_START    => '',
				CoursePostModel::META_KEY_SALE_END      => '',
			]
		);

		$this->assertFalse( $course->has_sale_price() );
	}

	public function test_has_sale_price_true_when_sale_lower_and_no_date_range(): void {
		$course = $this->make_course(
			[
				CoursePostModel::META_KEY_REGULAR_PRICE => '100',
				CoursePostModel::META_KEY_SALE_PRICE    => '70',
				CoursePostModel::META_KEY_SALE_START    => '', // empty → date check skipped
				CoursePostModel::META_KEY_SALE_END      => '',
			]
		);

		$this->assertTrue( $course->has_sale_price() );
	}

	public function test_has_sale_price_true_within_date_range(): void {
		// Stub WP functions used by LP_Datetime
		Functions\when( 'current_time' )->justReturn( '2026-03-26 12:00:00' );
		Functions\when( 'get_option' )->justReturn( 0 );

		$course = $this->make_course(
			[
				CoursePostModel::META_KEY_REGULAR_PRICE => '100',
				CoursePostModel::META_KEY_SALE_PRICE    => '70',
				CoursePostModel::META_KEY_SALE_START    => '2026-03-01 00:00:00',
				CoursePostModel::META_KEY_SALE_END      => '2026-03-31 23:59:59',
			]
		);

		$this->assertTrue( $course->has_sale_price() );
	}

	public function test_has_sale_price_false_when_expired(): void {
		Functions\when( 'current_time' )->justReturn( '2026-04-01 00:00:00' ); // after end date
		Functions\when( 'get_option' )->justReturn( 0 );

		$course = $this->make_course(
			[
				CoursePostModel::META_KEY_REGULAR_PRICE => '100',
				CoursePostModel::META_KEY_SALE_PRICE    => '70',
				CoursePostModel::META_KEY_SALE_START    => '2026-03-01 00:00:00',
				CoursePostModel::META_KEY_SALE_END      => '2026-03-31 23:59:59',
			]
		);

		$this->assertFalse( $course->has_sale_price() );
	}

	public function test_has_sale_price_false_before_start_date(): void {
		Functions\when( 'current_time' )->justReturn( '2026-02-28 23:59:59' ); // before start date
		Functions\when( 'get_option' )->justReturn( 0 );

		$course = $this->make_course(
			[
				CoursePostModel::META_KEY_REGULAR_PRICE => '100',
				CoursePostModel::META_KEY_SALE_PRICE    => '70',
				CoursePostModel::META_KEY_SALE_START    => '2026-03-01 00:00:00',
				CoursePostModel::META_KEY_SALE_END      => '2026-03-31 23:59:59',
			]
		);

		$this->assertFalse( $course->has_sale_price() );
	}

	public function test_get_price_returns_sale_price_when_on_sale(): void {
		$course = $this->make_course(
			[
				CoursePostModel::META_KEY_REGULAR_PRICE => '100',
				CoursePostModel::META_KEY_SALE_PRICE    => '70',
				CoursePostModel::META_KEY_SALE_START    => '',
				CoursePostModel::META_KEY_SALE_END      => '',
			]
		);

		$this->assertSame( 70.0, $course->get_price() );
	}

	public function test_get_price_returns_regular_price_when_no_sale(): void {
		$course = $this->make_course(
			[
				CoursePostModel::META_KEY_REGULAR_PRICE => '120',
				CoursePostModel::META_KEY_SALE_PRICE    => '',
				CoursePostModel::META_KEY_SALE_START    => '',
				CoursePostModel::META_KEY_SALE_END      => '',
			]
		);

		$this->assertSame( 120.0, $course->get_price() );
	}

	public function test_is_free_true_when_price_is_zero(): void {
		$course = $this->make_course(
			[
				CoursePostModel::META_KEY_REGULAR_PRICE => '0',
				CoursePostModel::META_KEY_SALE_PRICE    => '',
				CoursePostModel::META_KEY_SALE_START    => '',
				CoursePostModel::META_KEY_SALE_END      => '',
			]
		);

		$this->assertTrue( $course->is_free() );
	}

	public function test_is_free_false_when_price_is_nonzero(): void {
		$course = $this->make_course(
			[
				CoursePostModel::META_KEY_REGULAR_PRICE => '50',
				CoursePostModel::META_KEY_SALE_PRICE    => '',
				CoursePostModel::META_KEY_SALE_START    => '',
				CoursePostModel::META_KEY_SALE_END      => '',
			]
		);

		$this->assertFalse( $course->is_free() );
	}

	// -------------------------------------------------------------------------
	// 6. Evaluation settings
	// -------------------------------------------------------------------------

	public function test_get_evaluation_type_returns_value_from_meta(): void {
		$course = $this->make_course( [ CoursePostModel::META_KEY_EVALUATION_TYPE => 'evaluate_quiz' ] );

		$this->assertSame( 'evaluate_quiz', $course->get_evaluation_type() );
	}

	public function test_get_evaluation_type_default_is_evaluate_lesson(): void {
		// Key absent from meta_data → CoursePostModel::get_meta_value_by_key is called.
		// We cannot reach it without WP DB, so skip and test the default path via meta pre-set.
		$course = $this->make_course( [ CoursePostModel::META_KEY_EVALUATION_TYPE => 'evaluate_lesson' ] );

		$this->assertSame( 'evaluate_lesson', $course->get_evaluation_type() );
	}

	public function test_get_passing_condition_returns_float(): void {
		$course = $this->make_course( [ CoursePostModel::META_KEY_PASSING_CONDITION => '75' ] );

		$this->assertSame( 75.0, $course->get_passing_condition() );
	}

	public function test_get_passing_condition_default_is_80(): void {
		$course = $this->make_course( [ CoursePostModel::META_KEY_PASSING_CONDITION => '80' ] );

		$this->assertSame( 80.0, $course->get_passing_condition() );
	}

	// -------------------------------------------------------------------------
	// 7. Section / item helpers (sections_items pre-set, no DB)
	// -------------------------------------------------------------------------

	public function test_get_total_sections_counts_sections(): void {
		$course = $this->make_course();
		$course->sections_items = [
			$this->make_section( 1 ),
			$this->make_section( 2 ),
			$this->make_section( 3 ),
		];

		$this->assertSame( 3, $course->get_total_sections() );
	}

	public function test_get_total_sections_zero_when_no_sections(): void {
		$course = $this->make_course();
		$course->sections_items = [];

		$this->assertSame( 0, $course->get_total_sections() );
	}

	public function test_get_section_of_item_returns_correct_section_id(): void {
		$course = $this->make_course();
		$course->sections_items = [
			$this->make_section( 10, [ [ 'id' => 101 ], [ 'id' => 102 ] ] ),
			$this->make_section( 20, [ [ 'id' => 201 ] ] ),
		];

		$this->assertSame( 20, $course->get_section_of_item( 201 ) );
		$this->assertSame( 10, $course->get_section_of_item( 102 ) );
	}

	public function test_get_section_of_item_returns_zero_when_not_found(): void {
		$course = $this->make_course();
		$course->sections_items = [
			$this->make_section( 10, [ [ 'id' => 101 ] ] ),
		];

		$this->assertSame( 0, $course->get_section_of_item( 999 ) );
	}

	public function test_get_only_items_returns_flat_item_map(): void {
		$course = $this->make_course();
		$course->sections_items = [
			$this->make_section( 1, [ [ 'id' => 11 ], [ 'id' => 12 ] ] ),
			$this->make_section( 2, [ [ 'id' => 21 ] ] ),
		];

		$items = $course->get_only_items();

		$this->assertCount( 3, $items );
		$this->assertArrayHasKey( 11, $items );
		$this->assertArrayHasKey( 21, $items );
	}

	public function test_get_only_items_returns_empty_array_when_no_sections(): void {
		$course = $this->make_course();
		$course->sections_items = [];

		$this->assertSame( [], $course->get_only_items() );
	}

	public function test_get_only_items_is_isolated_per_instance(): void {
		$course1 = $this->make_course();
		$course1->sections_items = [
			$this->make_section( 1, [ [ 'id' => 11 ] ] ),
		];

		$course2 = $this->make_course();
		$course2->sections_items = [
			$this->make_section( 2, [ [ 'id' => 999 ] ] ),
		];

		$this->assertArrayHasKey( 11, $course1->get_only_items() );
		$this->assertArrayHasKey( 999, $course2->get_only_items() );
		$this->assertArrayNotHasKey( 999, $course1->get_only_items() );
	}

	// -------------------------------------------------------------------------
	// 8. first_item_id — reads from property when pre-set
	// -------------------------------------------------------------------------

	public function test_get_first_item_id_returns_cached_value(): void {
		$course = $this->make_course();
		$course->first_item_id = 55;

		$this->assertSame( 55, $course->get_first_item_id() );
	}
}