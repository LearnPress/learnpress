<?php

namespace LearnPress\MCP\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Pagination helpers for LearnPress MCP list tools.
 *
 * Single home for pagination input clamping, output math, and the pagination /
 * list-output JSON schemas shared by the read abilities.
 */
class Pagination {

	/**
	 * Sanitize a page number (minimum 1).
	 *
	 * @param mixed $value Raw page value.
	 *
	 * @return int
	 */
	public static function page( $value ): int {
		$page = absint( $value );
		return $page > 0 ? $page : 1;
	}

	/**
	 * Sanitize a per-page number and clamp to a safe range (1..100, default 10).
	 *
	 * @param mixed $value Raw per-page value.
	 *
	 * @return int
	 */
	public static function per_page( $value ): int {
		$per_page = absint( $value );
		if ( $per_page < 1 ) {
			$per_page = 10;
		}
		if ( $per_page > 100 ) {
			$per_page = 100;
		}

		return $per_page;
	}

	/**
	 * Calculate total pages from total item count.
	 *
	 * @param int $total_items Total items.
	 * @param int $per_page    Items per page.
	 *
	 * @return int
	 */
	public static function total_pages( int $total_items, int $per_page ): int {
		return $per_page > 0 ? (int) ceil( $total_items / $per_page ) : 0;
	}

	/**
	 * Pagination schema used by list abilities.
	 *
	 * @return array
	 */
	public static function schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'page', 'per_page', 'total_items', 'total_pages' ),
			'properties'           => array(
				'page'        => array( 'type' => 'integer' ),
				'per_page'    => array( 'type' => 'integer' ),
				'total_items' => array( 'type' => 'integer' ),
				'total_pages' => array( 'type' => 'integer' ),
			),
		);
	}

	/**
	 * Build a list response schema with pagination.
	 *
	 * @param array $item_schema Schema for each list item.
	 *
	 * @return array
	 */
	public static function list_output( array $item_schema ): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'items', 'pagination' ),
			'properties'           => array(
				'items'      => array(
					'type'  => 'array',
					'items' => $item_schema,
				),
				'pagination' => self::schema(),
			),
		);
	}
}
