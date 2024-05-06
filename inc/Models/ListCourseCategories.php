<?php

/**
 * Class ListCourseCategories
 *
 * Handle lit course categories
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.6.5
 */

namespace LearnPress\Models;

use Throwable;

class ListCourseCategories {
	/**
	 * Get all course categories
	 *
	 * @param array $arg_query_terms
	 *
	 * @return array [ term_id => term_name ]
	 */
	public static function get_all_categories_id_name( array $arg_query_terms = [] ): array {
		$terms = [];

		try {
			$arg_query_terms_default = [
				'taxonomy' => LP_COURSE_CATEGORY_TAX,
				'fields'   => 'id=>name',
				'parent'   => 0,
				'orderby'  => 'term_order',
				'order'    => 'ASC',
			];

			$arg_query_terms = array_merge( $arg_query_terms_default, $arg_query_terms );

			$terms = get_terms( $arg_query_terms );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $terms;
	}
}
