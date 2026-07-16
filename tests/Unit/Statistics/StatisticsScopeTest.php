<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Statistics;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use InvalidArgumentException;
use LearnPress\Statistics\StatisticsScope;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use LP_Filter;

require_once dirname( __DIR__, 3 ) . '/inc/Filters/class-lp-filter.php';

if ( ! defined( 'LP_COURSE_CATEGORY_TAX' ) ) {
	define( 'LP_COURSE_CATEGORY_TAX', 'course_category' );
}

/**
 * @covers \LearnPress\Statistics\StatisticsScope
 */
class StatisticsScopeTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'absint' )->alias(
			static function ( $value ) {
				return abs( (int) $value );
			}
		);

		// Minimal wpdb double: table name properties + a literal-substituting prepare().
		global $wpdb;
		$wpdb = new class() {
			public $posts              = 'wp_posts';
			public $term_relationships = 'wp_term_relationships';
			public $term_taxonomy      = 'wp_term_taxonomy';
			public $prefix             = 'wp_';

			public function prepare( $query, ...$args ) {
				foreach ( $args as $arg ) {
					$query = preg_replace_callback(
						'/%[ds]/',
						static function ( $matches ) use ( $arg ) {
							return '%d' === $matches[0] ? (string) (int) $arg : "'" . $arg . "'";
						},
						$query,
						1
					);
				}

				return $query;
			}
		};
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	/*
	|--------------------------------------------------------------------------
	| from_params() / is_empty()
	|--------------------------------------------------------------------------
	*/

	public function test_from_params_reads_both_ids(): void {
		$scope = StatisticsScope::from_params( [ 'instructor_id' => '5', 'category_id' => 9 ] );

		$this->assertSame( 5, $scope->instructor_id );
		$this->assertSame( 9, $scope->category_id );
		$this->assertFalse( $scope->is_empty() );
	}

	public function test_from_params_garbage_collapses_to_unscoped(): void {
		$scope = StatisticsScope::from_params( [ 'instructor_id' => 'DROP TABLE', 'category_id' => [] ] );

		$this->assertSame( 0, $scope->instructor_id );
		$this->assertSame( 0, $scope->category_id );
		$this->assertTrue( $scope->is_empty() );
	}

	public function test_from_params_missing_keys_default_to_unscoped(): void {
		$scope = StatisticsScope::from_params( [] );

		$this->assertTrue( $scope->is_empty() );
	}

	public function test_from_params_honors_scope_filter(): void {
		Filters\expectApplied( 'learn-press/statistics/scope' )
			->once()
			->andReturnUsing(
				static function ( $scope ) {
					$scope->instructor_id = 42;
					return $scope;
				}
			);

		$scope = StatisticsScope::from_params( [ 'instructor_id' => 5 ] );

		$this->assertSame( 42, $scope->instructor_id );
	}

	public function test_from_params_falls_back_when_filter_returns_wrong_type(): void {
		Filters\expectApplied( 'learn-press/statistics/scope' )
			->once()
			->andReturn( 'not-a-scope' );

		$scope = StatisticsScope::from_params( [ 'instructor_id' => 5 ] );

		$this->assertInstanceOf( StatisticsScope::class, $scope );
		$this->assertTrue( $scope->is_empty() );
	}

	/*
	|--------------------------------------------------------------------------
	| apply()
	|--------------------------------------------------------------------------
	*/

	public function test_apply_rejects_field_outside_allowlist(): void {
		$scope                = new StatisticsScope();
		$scope->instructor_id = 5;

		$this->expectException( InvalidArgumentException::class );

		$scope->apply( new LP_Filter(), 'p.ID; DROP TABLE wp_posts' );
	}

	public function test_apply_validates_field_even_when_scope_is_empty(): void {
		$this->expectException( InvalidArgumentException::class );

		( new StatisticsScope() )->apply( new LP_Filter(), 'evil.field' );
	}

	public function test_apply_is_noop_when_scope_empty(): void {
		$filter = new LP_Filter();

		$result = ( new StatisticsScope() )->apply( $filter, 'oi.item_id' );

		$this->assertSame( [], $result->join );
		$this->assertSame( [], $result->where );
	}

	public function test_apply_instructor_adds_author_join_and_where(): void {
		$scope                = new StatisticsScope();
		$scope->instructor_id = 5;

		$filter = $scope->apply( new LP_Filter(), 'oi.item_id' );

		$this->assertCount( 1, $filter->join );
		$this->assertSame( 'INNER JOIN wp_posts AS scope_p ON scope_p.ID = oi.item_id', $filter->join[0] );
		$this->assertSame( [ 'AND scope_p.post_author = 5' ], $filter->where );
	}

	public function test_apply_category_adds_term_joins_and_where(): void {
		$scope              = new StatisticsScope();
		$scope->category_id = 9;

		$filter = $scope->apply( new LP_Filter(), 'ui.item_id' );

		$this->assertCount( 2, $filter->join );
		$this->assertSame( 'INNER JOIN wp_term_relationships AS scope_tr ON scope_tr.object_id = ui.item_id', $filter->join[0] );
		$this->assertSame(
			"INNER JOIN wp_term_taxonomy AS scope_tt ON scope_tt.term_taxonomy_id = scope_tr.term_taxonomy_id AND scope_tt.taxonomy = 'course_category'",
			$filter->join[1]
		);
		$this->assertSame( [ 'AND scope_tt.term_id = 9' ], $filter->where );
	}

	public function test_apply_both_ids_adds_all_fragments(): void {
		$scope                = new StatisticsScope();
		$scope->instructor_id = 5;
		$scope->category_id   = 9;

		$filter = $scope->apply( new LP_Filter(), 'p.ID' );

		$this->assertCount( 3, $filter->join );
		$this->assertCount( 2, $filter->where );
	}

	public function test_apply_preserves_existing_filter_fragments(): void {
		$scope                = new StatisticsScope();
		$scope->instructor_id = 5;

		$filter          = new LP_Filter();
		$filter->join[]  = 'INNER JOIN existing AS e ON e.ID = p.ID';
		$filter->where[] = "AND p.post_type='lp_course'";

		$filter = $scope->apply( $filter, 'p.ID' );

		$this->assertSame( 'INNER JOIN existing AS e ON e.ID = p.ID', $filter->join[0] );
		$this->assertSame( "AND p.post_type='lp_course'", $filter->where[0] );
		$this->assertCount( 2, $filter->join );
		$this->assertCount( 2, $filter->where );
	}

	/*
	|--------------------------------------------------------------------------
	| apply_to_orders()
	|--------------------------------------------------------------------------
	*/

	public function test_apply_to_orders_rejects_field_outside_allowlist(): void {
		$scope                = new StatisticsScope();
		$scope->instructor_id = 5;

		$this->expectException( InvalidArgumentException::class );

		$scope->apply_to_orders( new LP_Filter(), 'oi.item_id' );
	}

	public function test_apply_to_orders_is_noop_when_scope_empty(): void {
		$filter = ( new StatisticsScope() )->apply_to_orders( new LP_Filter(), 'p.ID' );

		$this->assertSame( [], $filter->where );
	}

	public function test_apply_to_orders_builds_exists_subquery_for_instructor(): void {
		$scope                = new StatisticsScope();
		$scope->instructor_id = 5;

		$filter = $scope->apply_to_orders( new LP_Filter(), 'p.ID' );

		$this->assertCount( 1, $filter->where );
		$this->assertSame( [], $filter->join, 'Order scoping must not add joins — an order with several scoped items would be counted twice.' );
		$this->assertSame(
			'AND EXISTS ( SELECT 1 FROM wp_learnpress_order_items AS scope_oi INNER JOIN wp_posts AS scope_p ON scope_p.ID = scope_oi.item_id WHERE scope_oi.order_id = p.ID AND scope_p.post_author = 5 )',
			$filter->where[0]
		);
	}

	public function test_apply_to_orders_builds_exists_subquery_for_category(): void {
		$scope              = new StatisticsScope();
		$scope->category_id = 9;

		$filter = $scope->apply_to_orders( new LP_Filter(), 'p.ID' );

		$this->assertStringContainsString( 'AND EXISTS ( SELECT 1 FROM wp_learnpress_order_items AS scope_oi', $filter->where[0] );
		$this->assertStringContainsString( 'INNER JOIN wp_term_relationships AS scope_tr ON scope_tr.object_id = scope_oi.item_id', $filter->where[0] );
		$this->assertStringContainsString( "scope_tt.taxonomy = 'course_category'", $filter->where[0] );
		$this->assertStringContainsString( 'AND scope_tt.term_id = 9', $filter->where[0] );
	}

	public function test_apply_to_orders_combines_both_conditions(): void {
		$scope                = new StatisticsScope();
		$scope->instructor_id = 5;
		$scope->category_id   = 9;

		$filter = $scope->apply_to_orders( new LP_Filter(), 'p.ID' );

		$this->assertCount( 1, $filter->where );
		$this->assertStringContainsString( 'AND scope_p.post_author = 5', $filter->where[0] );
		$this->assertStringContainsString( 'AND scope_tt.term_id = 9', $filter->where[0] );
	}
}
