<?php

namespace LearnPress\Databases\Course;

use Exception;
use LearnPress\Databases\DataBase;
use LearnPress\Filters\Course\CourseJsonFilter;
use LP_Helper;

defined( 'ABSPATH' ) || exit();

/**
 * Class CourseJsonDB
 *
 * Move from LP_Course_JSON_DB to here
 *
 * @since 4.3.2.3
 * @version 1.0.0
 */
class CourseJsonDB extends DataBase {
	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get Courses
	 *
	 * @param CourseJsonFilter $filter
	 * @param int $total_rows return total_rows
	 *
	 * @return array|int|string|null
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.2.6.9
	 */
	public function get_courses( CourseJsonFilter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_courses;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'c';
		}

		$ca = $filter->collection_alias;

		// Find ID
		if ( ! empty( $filter->ID ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.ID = %d", $filter->ID );
		}

		// Status
		$filter->post_status = (array) $filter->post_status;
		if ( ! empty( $filter->post_status ) ) {
			$post_status_format = LP_Helper::db_format_array( $filter->post_status, '%s' );
			$filter->where[]    = $this->wpdb->prepare( "AND $ca.post_status IN (" . $post_status_format . ')', $filter->post_status );
		}

		// Term ids
		if ( ! empty( $filter->term_ids ) ) {
			// Sanitize term ids
			$filter->term_ids = array_map( 'absint', $filter->term_ids );
			$term_ids_format  = join( ',', $filter->term_ids );
			$filter->join[]   = "INNER JOIN $this->tb_term_relationships AS r_term_p ON $ca.ID = r_term_p.object_id";
			$filter->join[]   = "INNER JOIN $this->tb_term_taxonomy AS tx_p ON r_term_p.term_taxonomy_id = tx_p.term_taxonomy_id";
			$filter->where[]  = "AND r_term_p.term_taxonomy_id IN ($term_ids_format)";
			$filter->where[]  = $this->wpdb->prepare( 'AND tx_p.taxonomy = %s', $filter->taxonomy );
		}

		// Course ids
		if ( ! empty( $filter->post_ids ) ) {
			$post_ids        = array_map( 'absint', $filter->post_ids );
			$post_ids_format = join( ',', $post_ids );
			$filter->where[] = "AND $ca.ID IN ($post_ids_format)";
		}

		// Title
		if ( ! empty( $filter->post_title ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_title LIKE %s", '%' . $filter->post_title . '%' );
		}

		// Name(slug)
		if ( ! empty( $filter->post_name ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_name = %s", $filter->post_name );
		}

		// Author
		if ( ! empty( $filter->post_author ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_author = %d", $filter->post_author );
		}

		// Authors
		if ( ! empty( $filter->post_authors ) ) {
			$post_authors_format = LP_Helper::db_format_array( $filter->post_authors, '%d' );
			$filter->where[]     = $this->wpdb->prepare( "AND $ca.post_author IN (" . $post_authors_format . ')', $filter->post_authors );
		}

		/**
		 * Type price
		 *
		 * if 'free' and 'paid' are not in the array or not null, we will filter the price
		 */
		if ( ! empty( $filter->type_price ) &&
			( ! in_array( 'free', $filter->type_price ) && ! in_array( 'paid', $filter->type_price ) ) ) {
			foreach ( $filter->type_price as $type_price ) {
				switch ( $type_price ) {
					case 'free':
						$filter->where[] = 'AND price_to_sort = 0';
						break;
					case 'paid':
						$filter->where[] = 'AND price_to_sort > 0';
						break;
				}
			}
		}

		// Is sale
		if ( ! empty( $filter->is_sale ) ) {
			$filter->where[] = 'AND is_sale = 1';
		}

		// Is feature
		if ( ! empty( $filter->is_feature ) ) {
			$filter = $this->get_courses_in_feature( $filter );
		}

		// Levels
		if ( ! empty( $filter->levels ) ) {
			$filter->join[]  = "INNER JOIN $this->tb_postmeta AS pml ON c.ID = pml.post_id";
			$filter->where[] = $this->wpdb->prepare( 'AND pml.meta_key = %s', '_lp_level' );
			$levels_format   = LP_Helper::db_format_array( $filter->levels, '%s' );
			$filter->where[] = $this->wpdb->prepare( 'AND pml.meta_value IN (' . $levels_format . ')', $filter->levels );
		}

		// Course type
		if ( ! empty( $filter->type ) && $filter->type !== 'all' ) {
			if ( $filter->type === 'offline' ) {
				$filter->join[]  = "INNER JOIN $this->tb_postmeta AS pm_off ON c.ID = pm_off.post_id";
				$filter->where[] = $this->wpdb->prepare( 'AND pm_off.meta_key = %s', '_lp_offline_course' );
				$filter->where[] = $this->wpdb->prepare( 'AND pm_off.meta_value = %s', 'yes' );
			} else {
				$filter->where[] = $this->wpdb->prepare(
					"AND c.ID NOT IN
					( SELECT id FROM $this->tb_posts as p1
					INNER JOIN $this->tb_postmeta as pm_ol on p1.ID = pm_ol.post_id
					WHERE pm_ol.meta_key = %s AND pm_ol.meta_value = %s )",
					'_lp_offline_course',
					'yes'
				);
			}
		}

		$filter = apply_filters( 'lp/courses-json/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}

	/**
	 * Get list courses is on popular
	 * Use "UNION" to merge 2 query
	 *
	 * @param CourseJsonFilter $filter
	 *
	 * @return  CourseJsonFilter
	 * @throws Exception
	 * @version 1.0.9
	 * @since 4.3.2.3
	 */
	public function get_courses_order_by_popular( CourseJsonFilter &$filter ): CourseJsonFilter {
		// Set list name columns get
		//$columns_table_posts = $this->get_cols_of_table( $this->tb_posts );
		//$filter->fields      = array_merge( $columns_table_posts, $filter->fields );
		$db = CourseJsonDB::getInstance();

		$filter_user_course       = clone $filter;
		$filter_course_not_attend = clone $filter;

		// Query get users total attend courses
		$fields_user_course_require = [ 'c.ID', 'COUNT(c.ID) AS total' ];
		$filter_user_course->fields = array( 'c.ID', 'COUNT(c.ID) AS total' );
		if ( ! empty( $filter_user_course->only_fields ) ) {
			$pattern = '#ID.*#';
			foreach ( $filter_user_course->only_fields as $k => $field ) {
				if ( preg_match( $pattern, $field ) ) {
					unset( $filter_user_course->only_fields[ $k ] );
				}
			}
			$filter_user_course->only_fields = array_unique( array_merge( $filter_user_course->only_fields, $fields_user_course_require ) );
		}

		$filter_user_course->join[]              = "INNER JOIN {$this->tb_lp_user_items} AS ui ON c.ID = ui.item_id";
		$filter_user_course->where[]             = $this->wpdb->prepare( 'AND ui.item_type = %s', LP_COURSE_CPT );
		$filter_user_course->where[]             = $this->wpdb->prepare(
			'AND (status = %s OR status = %s OR status = %s)',
			LP_COURSE_ENROLLED,
			LP_COURSE_PURCHASED,
			LP_COURSE_FINISHED
		);
		$filter_user_course->group_by            = 'c.ID';
		$filter_user_course->order_by            = '';
		$filter_user_course->return_string_query = true;

		$query_user_course = $db->get_courses( $filter_user_course );

		// Query get courses not attend
		$filter_user_course_cl              = clone $filter_user_course;
		$filter_user_course_cl->only_fields = array( 'c.ID' );
		$query_user_course_for_not_in       = $db->get_courses( $filter_user_course_cl );

		$fields_user_course_not_attend_require = [ 'c.ID', '0 AS total' ];
		$filter_course_not_attend->fields      = [ 'c.ID', '0 AS total' ];
		if ( ! empty( $filter_course_not_attend->only_fields ) ) {
			$pattern = '#ID.*#';
			foreach ( $filter_course_not_attend->only_fields as $k => $field ) {
				if ( preg_match( $pattern, $field ) ) {
					unset( $filter_course_not_attend->only_fields[ $k ] );
				}
			}
			$filter_course_not_attend->only_fields = array_unique( array_merge( $filter_course_not_attend->only_fields, $fields_user_course_not_attend_require ) );
		}
		$filter_course_not_attend->where[] = 'AND c.ID NOT IN(' . $query_user_course_for_not_in . ')';

		$filter_course_not_attend->order_by            = '';
		$filter_course_not_attend->return_string_query = true;

		$query_course_not_attend = $db->get_courses( $filter_course_not_attend );

		$filter->union[]  = $query_user_course;
		$filter->union[]  = $query_course_not_attend;
		$filter->order_by = 'total';
		$filter->order    = 'DESC';

		return $filter;
	}

	/**
	 * Get list courses is on feature
	 *
	 * @param CourseJsonFilter $filter
	 *
	 * @return  CourseJsonFilter
	 * @author tungnx
	 * @version 1.0.0
	 * @since 4.1.5
	 */
	public function get_courses_in_feature( CourseJsonFilter &$filter ): CourseJsonFilter {
		$filter->join[]  = "INNER JOIN $this->tb_postmeta AS pmf ON c.ID = pmf.post_id";
		$filter->where[] = $this->wpdb->prepare( 'AND pmf.meta_key = %s', '_lp_featured' );
		$filter->where[] = $this->wpdb->prepare( 'AND pmf.meta_value = %s', 'yes' );

		return $filter;
	}
}
