<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Filters;

use LearnPress\Filters\FilterBase;
use LearnPress\Filters\MaterialFilter;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for MaterialFilter.
 *
 * Verifies constants, property defaults, inheritance from FilterBase,
 * and the all_fields / field_count configuration.
 */
class MaterialFilterTest extends BrainMonkeyTestCase {

	#[Test]
	public function extends_filter_base(): void {
		$filter = new MaterialFilter();

		$this->assertInstanceOf( FilterBase::class, $filter );
	}

	#[Test]
	public function constants_map_to_correct_column_names(): void {
		$this->assertSame( 'file_id', MaterialFilter::COL_FILE_ID );
		$this->assertSame( 'file_name', MaterialFilter::COL_FILE_NAME );
		$this->assertSame( 'file_type', MaterialFilter::COL_FILE_TYPE );
		$this->assertSame( 'item_id', MaterialFilter::COL_ITEM_ID );
		$this->assertSame( 'item_type', MaterialFilter::COL_ITEM_TYPE );
		$this->assertSame( 'method', MaterialFilter::COL_METHOD );
		$this->assertSame( 'file_path', MaterialFilter::COL_FILE_PATH );
		$this->assertSame( 'orders', MaterialFilter::COL_ORDERS );
		$this->assertSame( 'created_at', MaterialFilter::COL_CREATED_AT );
	}

	#[Test]
	public function all_fields_contains_all_nine_columns(): void {
		$filter = new MaterialFilter();

		$expected = [
			'file_id',
			'file_name',
			'file_type',
			'item_id',
			'item_type',
			'method',
			'file_path',
			'orders',
			'created_at',
		];

		$this->assertSame( $expected, $filter->all_fields );
		$this->assertCount( 9, $filter->all_fields );
	}

	#[Test]
	public function field_count_defaults_to_primary_key(): void {
		$filter = new MaterialFilter();

		$this->assertSame( 'file_id', $filter->field_count );
	}

	#[Test]
	public function scalar_properties_default_to_null(): void {
		$filter = new MaterialFilter();

		$this->assertNull( $filter->file_id );
		$this->assertNull( $filter->file_name );
		$this->assertNull( $filter->file_type );
		$this->assertNull( $filter->item_id );
		$this->assertNull( $filter->item_type );
		$this->assertNull( $filter->method );
		$this->assertNull( $filter->file_path );
		$this->assertNull( $filter->orders );
		$this->assertNull( $filter->created_at );
	}

	#[Test]
	public function item_ids_defaults_to_empty_array(): void {
		$filter = new MaterialFilter();

		$this->assertIsArray( $filter->item_ids );
		$this->assertEmpty( $filter->item_ids );
	}

	#[Test]
	public function properties_are_assignable(): void {
		$filter = new MaterialFilter();

		$filter->file_id    = 42;
		$filter->file_name  = 'document.pdf';
		$filter->file_type  = 'pdf';
		$filter->item_id    = 100;
		$filter->item_ids   = [ 100, 200, 300 ];
		$filter->item_type  = 'lp_lesson';
		$filter->method     = 'upload';
		$filter->file_path  = '/uploads/document.pdf';
		$filter->orders     = 3;
		$filter->created_at = '2025-01-15 10:30:00';

		$this->assertSame( 42, $filter->file_id );
		$this->assertSame( 'document.pdf', $filter->file_name );
		$this->assertSame( 'pdf', $filter->file_type );
		$this->assertSame( 100, $filter->item_id );
		$this->assertSame( [ 100, 200, 300 ], $filter->item_ids );
		$this->assertSame( 'lp_lesson', $filter->item_type );
		$this->assertSame( 'upload', $filter->method );
		$this->assertSame( '/uploads/document.pdf', $filter->file_path );
		$this->assertSame( 3, $filter->orders );
		$this->assertSame( '2025-01-15 10:30:00', $filter->created_at );
	}

	#[Test]
	public function inherits_filter_base_defaults(): void {
		$filter = new MaterialFilter();

		$this->assertSame( 10, $filter->limit );
		$this->assertSame( 100, $filter->max_limit );
		$this->assertSame( 1, $filter->page );
		$this->assertSame( '', $filter->order_by );
		$this->assertSame( '', $filter->order );
		$this->assertSame( '', $filter->key_word );
		$this->assertTrue( $filter->run_query_count );
		$this->assertFalse( $filter->query_count );
		$this->assertSame( 'get_results', $filter->query_type );
	}
}
