<?php
/**
 * Class StatisticsScope
 *
 * @package LearnPress/Classes/Statistics
 * @since 4.4.2
 */

namespace LearnPress\Statistics;

use InvalidArgumentException;
use LP_Filter;

defined( 'ABSPATH' ) || exit();

/**
 * Scope statistics queries by instructor and/or course category.
 *
 * Single sanitation boundary for the global dashboard filters: request data
 * only enters via from_params(), everything downstream trusts the typed DTO.
 *
 * @since 4.4.2
 */
class StatisticsScope {
	/**
	 * @var int
	 */
	public $instructor_id = 0;
	/**
	 * @var int
	 */
	public $category_id = 0;

	/**
	 * Identifier allowlists: join fields are hardcoded per caller,
	 * never interpolated from request data.
	 * ui2 = parent course user_item row; s = learnpress_sections.
	 */
	private const COURSE_ID_FIELDS = array( 'oi.item_id', 'ui.item_id', 'p.ID', 'ui2.item_id', 's.section_course_id' );
	private const ORDER_ID_FIELDS  = array( 'p.ID' );

	/**
	 * Build a scope from request params. Negative/garbage values collapse to 0 (= unscoped).
	 *
	 * @param array $params Request params, may contain instructor_id/category_id.
	 * @return StatisticsScope
	 */
	public static function from_params( array $params ): StatisticsScope {
		$scope                = new self();
		$scope->instructor_id = absint( $params['instructor_id'] ?? 0 );
		$scope->category_id   = absint( $params['category_id'] ?? 0 );

		/**
		 * Filter the resolved statistics scope before it shapes any query.
		 *
		 * Fires once per request at the single scope sanitation boundary, so a
		 * handler here reaches every scoped query and report across all tabs
		 * ( e.g. force a teacher role to only their own courses ).
		 *
		 * @param StatisticsScope $scope  Resolved scope.
		 * @param array           $params Sanitized request params.
		 * @since 4.4.2
		 */
		$scope = apply_filters( 'learn-press/statistics/scope', $scope, $params );

		// Poka-yoke: a handler returning the wrong type falls back to an unscoped scope.
		return $scope instanceof self ? $scope : new self();
	}

	/**
	 * @return bool
	 */
	public function is_empty(): bool {
		return 0 === $this->instructor_id && 0 === $this->category_id;
	}

	/**
	 * Add scope joins/where to a query whose rows already carry a course id column.
	 *
	 * @param LP_Filter $filter          Query filter to extend.
	 * @param string    $course_id_field One of COURSE_ID_FIELDS.
	 * @return LP_Filter
	 * @throws InvalidArgumentException On a course id field outside the allowlist.
	 */
	public function apply( LP_Filter $filter, string $course_id_field ): LP_Filter {
		if ( ! in_array( $course_id_field, self::COURSE_ID_FIELDS, true ) ) {
			throw new InvalidArgumentException( 'Unknown course id field for statistics scope.' );
		}

		if ( $this->is_empty() ) {
			return $filter;
		}

		global $wpdb;

		if ( $this->instructor_id > 0 ) {
			$filter->join[]  = "INNER JOIN {$wpdb->posts} AS scope_p ON scope_p.ID = {$course_id_field}";
			$filter->where[] = $wpdb->prepare( 'AND scope_p.post_author = %d', $this->instructor_id );
		}

		if ( $this->category_id > 0 ) {
			$filter->join[]  = "INNER JOIN {$wpdb->term_relationships} AS scope_tr ON scope_tr.object_id = {$course_id_field}";
			$filter->join[]  = $wpdb->prepare(
				"INNER JOIN {$wpdb->term_taxonomy} AS scope_tt ON scope_tt.term_taxonomy_id = scope_tr.term_taxonomy_id AND scope_tt.taxonomy = %s",
				LP_COURSE_CATEGORY_TAX
			);
			$filter->where[] = $wpdb->prepare( 'AND scope_tt.term_id = %d', $this->category_id );
		}

		return $filter;
	}

	/**
	 * Scope an orders query that has no course id column: keep orders containing
	 * at least one course item matching the scope.
	 *
	 * Uses EXISTS instead of a join so an order with several scoped items still
	 * counts once — order-level COUNT/SUM fields stay correct without DISTINCT rewrites.
	 *
	 * @param LP_Filter $filter         Query filter to extend.
	 * @param string    $order_id_field One of ORDER_ID_FIELDS.
	 * @return LP_Filter
	 * @throws InvalidArgumentException On an order id field outside the allowlist.
	 */
	public function apply_to_orders( LP_Filter $filter, string $order_id_field ): LP_Filter {
		if ( ! in_array( $order_id_field, self::ORDER_ID_FIELDS, true ) ) {
			throw new InvalidArgumentException( 'Unknown order id field for statistics scope.' );
		}

		if ( $this->is_empty() ) {
			return $filter;
		}

		global $wpdb;

		$tb_order_items = $wpdb->prefix . 'learnpress_order_items';
		$joins          = '';
		$conditions     = '';

		if ( $this->instructor_id > 0 ) {
			$joins      .= " INNER JOIN {$wpdb->posts} AS scope_p ON scope_p.ID = scope_oi.item_id";
			$conditions .= $wpdb->prepare( ' AND scope_p.post_author = %d', $this->instructor_id );
		}

		if ( $this->category_id > 0 ) {
			$joins      .= " INNER JOIN {$wpdb->term_relationships} AS scope_tr ON scope_tr.object_id = scope_oi.item_id";
			$joins      .= $wpdb->prepare(
				" INNER JOIN {$wpdb->term_taxonomy} AS scope_tt ON scope_tt.term_taxonomy_id = scope_tr.term_taxonomy_id AND scope_tt.taxonomy = %s",
				LP_COURSE_CATEGORY_TAX
			);
			$conditions .= $wpdb->prepare( ' AND scope_tt.term_id = %d', $this->category_id );
		}

		$filter->where[] = "AND EXISTS ( SELECT 1 FROM {$tb_order_items} AS scope_oi{$joins} WHERE scope_oi.order_id = {$order_id_field}{$conditions} )";

		return $filter;
	}

	/**
	 * Prepared "AND EXISTS(...)" conditions for raw SQL queries (subselects,
	 * HAVING-grouped queries) where LP_Filter joins do not reach.
	 *
	 * EXISTS-based so it is alias-collision-free and never duplicates rows.
	 * Returns '' when the scope is empty.
	 *
	 * @param string $course_id_field One of COURSE_ID_FIELDS.
	 * @return string
	 * @throws InvalidArgumentException On a course id field outside the allowlist.
	 */
	public function sql_conditions( string $course_id_field ): string {
		if ( ! in_array( $course_id_field, self::COURSE_ID_FIELDS, true ) ) {
			throw new InvalidArgumentException( 'Unknown course id field for statistics scope.' );
		}

		if ( $this->is_empty() ) {
			return '';
		}

		global $wpdb;

		$conditions = '';

		if ( $this->instructor_id > 0 ) {
			$conditions .= $wpdb->prepare(
				" AND EXISTS ( SELECT 1 FROM {$wpdb->posts} AS scope_sp WHERE scope_sp.ID = {$course_id_field} AND scope_sp.post_author = %d )",
				$this->instructor_id
			);
		}

		if ( $this->category_id > 0 ) {
			$conditions .= $wpdb->prepare(
				" AND EXISTS ( SELECT 1 FROM {$wpdb->term_relationships} AS scope_str"
				. " INNER JOIN {$wpdb->term_taxonomy} AS scope_stt ON scope_stt.term_taxonomy_id = scope_str.term_taxonomy_id AND scope_stt.taxonomy = %s"
				. " WHERE scope_str.object_id = {$course_id_field} AND scope_stt.term_id = %d )",
				LP_COURSE_CATEGORY_TAX,
				$this->category_id
			);
		}

		return $conditions;
	}
}
