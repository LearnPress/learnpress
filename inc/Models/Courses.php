<?php

/**
 * Class Courses
 *
 * Handle all method about list courses
 *
 * @package LearnPress/Classes
 * @version 1.0.1
 * @since 4.2.5.4
 */

namespace LearnPress\Models;

use LearnPress\Databases\Course\CourseJsonDB;
use LearnPress\Filters\Course\CourseJsonFilter;
use LP_Cache;
use LP_Course_DB;
use LP_Course_Filter;
use LP_Courses_Cache;
use LP_Database;
use LP_Debug;
use LP_Helper;
use LP_Settings;
use Throwable;

class Courses {
	/**
	 * Count total courses free
	 *
	 * @param LP_Course_Filter $filter
	 *
	 * @return int
	 * @since 4.2.5.4
	 * @version 1.0.0
	 */
	public static function count_course_free( LP_Course_Filter $filter ): int {
		// Check cache
		$lang      = $_REQUEST['lang'] ?? '';
		$key_cache = 'count-courses-free-' . md5( json_encode( $filter ) . $lang );
		$count     = LP_Cache::cache_load_first( 'get', $key_cache );
		if ( false !== $count ) {
			return $count;
		}

		$lp_courses_cache = new LP_Courses_Cache( true );
		$count            = $lp_courses_cache->get_cache( $key_cache );
		if ( false !== $count ) {
			LP_Cache::cache_load_first( 'set', $key_cache, $count );

			return $count;
		}

		$lp_course_db = LP_Course_DB::getInstance();
		$count        = $lp_course_db->count_course_free( $filter );

		// Set cache
		$lp_courses_cache->set_cache( $key_cache, $count );
		$lp_courses_cache_keys = new LP_Courses_Cache( true );
		$lp_courses_cache_keys->save_cache_keys_count_courses_free( $key_cache );
		LP_Cache::cache_load_first( 'set', $key_cache, $count );

		return (int) $count;
	}

	/**
	 * Handle params before query courses
	 *
	 * @param array $param
	 * @param LP_Course_Filter $filter
	 *
	 * @return void
	 * @version 1.0.3
	 * @since 4.2.3.3 move from class LP_Course
	 */
	public static function handle_params_for_query_courses( LP_Course_Filter &$filter, array $param = [] ) {
		$filter->page       = absint( $param['paged'] ?? 1 );
		$filter->post_title = LP_Helper::sanitize_params_submitted( trim( $param['c_search'] ?? '' ) );

		// Get Columns
		$fields_str = LP_Helper::sanitize_params_submitted( urldecode( $param['c_fields'] ?? '' ) );
		if ( ! empty( $fields_str ) ) {
			$fields = explode( ',', $fields_str );
			foreach ( $fields as $key => $field ) {
				$fields[ $key ] = LP_Database::getInstance()->wpdb->prepare( '%i', $field );
			}
			$filter->fields = $fields;
		}

		// Get only columns
		$fields_only_str = LP_Helper::sanitize_params_submitted( urldecode( $param['c_only_fields'] ?? '' ) );
		if ( ! empty( $fields_only_str ) ) {
			$fields_only = explode( ',', $fields_only_str );
			foreach ( $fields_only as $key => $field ) {
				$fields_only[ $key ] = LP_Database::getInstance()->wpdb->prepare( '%i', $field );
			}
			$filter->only_fields = $fields_only;
		}

		// Exclude Columns
		$fields_exclude_str = LP_Helper::sanitize_params_submitted( urldecode( $param['c_exclude_fields'] ?? '' ) );
		if ( ! empty( $fields_exclude_str ) ) {
			$fields_exclude         = explode( ',', $fields_exclude_str );
			$filter->exclude_fields = $fields_exclude;
		}

		// Find by ids
		$course_ids_str = LP_Helper::sanitize_params_submitted( urldecode( $param['ids'] ?? '' ) );
		if ( ! empty( $course_ids_str ) ) {
			$course_ids       = explode( ',', $course_ids_str );
			$filter->post_ids = $course_ids;
		}

		// Author
		$c_author = LP_Helper::sanitize_params_submitted( $param['c_author'] ?? 0 );
		if ( ! empty( $c_author ) ) {
			$filter->post_author = $c_author;
		}
		$author_ids_str = LP_Helper::sanitize_params_submitted( $param['c_authors'] ?? '' );
		if ( ! empty( $author_ids_str ) ) {
			$author_ids           = explode( ',', $author_ids_str );
			$filter->post_authors = $author_ids;
		}

		// Find by status
		$post_status = LP_Helper::sanitize_params_submitted( $param['c_status'] ?? '' );
		if ( ! empty( $post_status ) ) {
			if ( 'all' !== $post_status ) {
				$filter->post_status = explode( ',', $post_status );
			}
		} else {
			$filter->post_status = [ 'publish' ];
		}

		/**
		 * Sort by
		 * 1. on_sale
		 * 2. on_free
		 * 3. on_paid
		 * 4. on_feature
		 */
		if ( ! empty( $param['sort_by'] ) ) {
			$filter->sort_by[] = $param['sort_by'];
		}

		// Sort by level
		$levels_str = LP_Helper::sanitize_params_submitted( urldecode( $param['c_level'] ?? '' ) );
		if ( ! empty( $levels_str ) ) {
			$levels = explode( ',', $levels_str );
			if ( in_array( 'all', $levels ) ) {
				$levels[] = '';
			}
			$filter->levels = $levels;
		}

		// Sort by type (oline/offline)
		$course_type = LP_Helper::sanitize_params_submitted( urldecode( $param['c_type'] ?? '' ) );
		if ( ! empty( $course_type ) ) {
			$course_type = explode( ',', $course_type );
			if ( in_array( 'online', $course_type ) && in_array( 'offline', $course_type ) ) {
				$filter->type = 'all';
			} else {
				$filter->type = $course_type[0];
			}
		}

		// Check is in category page.
		if ( ! empty( $param['page_term_id_current'] ) && empty( $param['term_id'] ) ) {
			$filter->term_ids[] = $param['page_term_id_current'];
		} // Check is in tag page.
		elseif ( ! empty( $param['page_tag_id_current'] ) && empty( $param['tag_id'] ) ) {
			$filter->tag_ids[] = $param['page_tag_id_current'];
		}

		// Find by category
		$term_ids_str = LP_Helper::sanitize_params_submitted( urldecode( $param['term_id'] ?? '' ) );
		if ( ! empty( $term_ids_str ) ) {
			$term_ids         = explode( ',', $term_ids_str );
			$filter->term_ids = array_merge( $filter->term_ids, $term_ids );
		}

		// Find by tag
		$tag_ids_str = LP_Helper::sanitize_params_submitted( urldecode( $param['tag_id'] ?? '' ) );
		if ( ! empty( $tag_ids_str ) ) {
			$tag_ids         = explode( ',', $tag_ids_str );
			$filter->tag_ids = array_merge( $filter->tag_ids, $tag_ids );
		}

		// Order by
		$filter->order_by = LP_Helper::sanitize_params_submitted( ! empty( $param['order_by'] ) ? $param['order_by'] : 'post_date', 'key' );
		$filter->order    = LP_Helper::sanitize_params_submitted( ! empty( $param['order'] ) ? $param['order'] : 'DESC', 'key' );
		$filter->limit    = $param['limit'] ?? LP_Settings::get_option( 'archive_course_limit', 10 );

		// For search suggest courses
		if ( ! empty( $param['c_suggest'] ) ) {
			$filter->only_fields = [ 'ID', 'post_title' ];
			$filter->limit       = apply_filters( 'learn-press/rest-api/courses/suggest-limit', 10 );
		}

		$return_type = $param['return_type'] ?? 'html';
		if ( 'json' !== $return_type ) {
			$filter->only_fields = array( 'DISTINCT(ID) AS ID' );
		}

		do_action( 'learn-press/courses/handle_params_for_query_courses', $filter, $param );
	}

	/**
	 * Get list course
	 * Order By: price, title, rating, date ...
	 * Order: ASC, DES
	 *
	 * @param LP_Course_Filter $filter
	 * @param int $total_rows
	 *
	 * @return object|null|string|int
	 * @author tungnx
	 * @version 1.0.1
	 * @sicne 4.1.5
	 */
	public static function get_courses( LP_Course_Filter $filter, int &$total_rows = 0 ) {
		$lp_course_db = LP_Course_DB::getInstance();

		try {
			// Order by
			switch ( $filter->order_by ) {
				case 'price':
				case 'price_low':
					if ( 'price_low' === $filter->order_by ) {
						$filter->order = 'ASC';
					} else {
						$filter->order = 'DESC';
					}

					$filter = $lp_course_db->get_courses_order_by_price( $filter );
					break;
				case 'popular':
					$filter = $lp_course_db->get_courses_order_by_popular( $filter );
					break;
				case 'post_title':
					$filter->order = 'ASC';
					break;
				case 'post_title_desc':
					$filter->order_by = 'post_title';
					$filter->order    = 'DESC';
					break;
				case 'menu_order':
					$filter->order_by = 'menu_order';
					$filter->order    = 'ASC';
					break;
				default:
					$filter = apply_filters( 'lp/courses/filter/order_by/' . $filter->order_by, $filter );
					break;
			}

			// Sort by
			$filter->sort_by = (array) $filter->sort_by;
			foreach ( $filter->sort_by as $sort_by ) {
				$filter_tmp                      = clone $filter;
				$filter_tmp->only_fields         = array( 'DISTINCT(ID)' );
				$filter_tmp->return_string_query = true;

				switch ( $sort_by ) {
					case 'on_sale':
						$filter_tmp = $lp_course_db->get_courses_sort_by_sale( $filter_tmp );
						break;
					case 'on_free':
						$filter_tmp = $lp_course_db->get_courses_sort_by_free( $filter_tmp );
						break;
					case 'on_paid':
						$filter_tmp = $lp_course_db->get_courses_sort_by_paid( $filter_tmp );
						break;
					case 'on_feature':
						$filter_tmp = $lp_course_db->get_courses_sort_by_feature( $filter_tmp );
						break;
					default:
						$filter_tmp = apply_filters( 'lp/courses/filter/sort_by/' . $sort_by, $filter_tmp );
						break;
				}

				$query_courses_str = $lp_course_db->get_courses( $filter_tmp );

				$filter->where[] = "AND ID IN ({$query_courses_str})";
			}

			// Query get results
			$filter  = apply_filters( 'lp/courses/filter', $filter );
			$courses = LP_Course_DB::getInstance()->get_courses( $filter, $total_rows );
		} catch ( Throwable $e ) {
			$courses = [];
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}

		return $courses;
	}

	/**
	 * Handle params before query list courses on table learnpress_courses
	 *
	 * @param CourseJsonFilter $filter
	 * @param array $param
	 *
	 * @return void
	 * @since 4.3.2.3
	 * @version 1.0.0
	 */
	public static function handle_params_for_query_list_courses( CourseJsonFilter &$filter, array $param = [] ) {
		$filter->page       = absint( $param['paged'] ?? 1 );
		$filter->post_title = LP_Helper::sanitize_params_submitted( trim( $param['c_search'] ?? '' ) );

		// Get Columns
		$fields_str = LP_Helper::sanitize_params_submitted( urldecode( $param['c_fields'] ?? '' ) );
		if ( ! empty( $fields_str ) ) {
			$fields = explode( ',', $fields_str );
			foreach ( $fields as $key => $field ) {
				$fields[ $key ] = LP_Database::getInstance()->wpdb->prepare( '%i', $field );
			}
			$filter->fields = $fields;
		}

		// Get only columns
		$fields_only_str = LP_Helper::sanitize_params_submitted( urldecode( $param['c_only_fields'] ?? '' ) );
		if ( ! empty( $fields_only_str ) ) {
			$fields_only = explode( ',', $fields_only_str );
			foreach ( $fields_only as $key => $field ) {
				$fields_only[ $key ] = LP_Database::getInstance()->wpdb->prepare( '%i', $field );
			}
			$filter->only_fields = $fields_only;
		}

		// Exclude Columns
		$fields_exclude_str = LP_Helper::sanitize_params_submitted( urldecode( $param['c_exclude_fields'] ?? '' ) );
		if ( ! empty( $fields_exclude_str ) ) {
			$fields_exclude         = explode( ',', $fields_exclude_str );
			$filter->exclude_fields = $fields_exclude;
		}

		// Find by ids
		$course_ids_str = LP_Helper::sanitize_params_submitted( urldecode( $param['ids'] ?? '' ) );
		if ( ! empty( $course_ids_str ) ) {
			$course_ids  = explode( ',', $course_ids_str );
			$filter->ids = $course_ids;
		}

		// Author
		$c_author = LP_Helper::sanitize_params_submitted( $param['c_author'] ?? 0 );
		if ( ! empty( $c_author ) ) {
			$filter->post_author = $c_author;
		}
		$author_ids_str = LP_Helper::sanitize_params_submitted( $param['c_authors'] ?? '' );
		if ( ! empty( $author_ids_str ) ) {
			$author_ids           = explode( ',', $author_ids_str );
			$filter->post_authors = $author_ids;
		}

		// Find by status
		$post_status = LP_Helper::sanitize_params_submitted( $param['c_status'] ?? '' );
		if ( ! empty( $post_status ) ) {
			if ( 'all' !== $post_status ) {
				$filter->post_status = explode( ',', $post_status );
			}
		}

		// Type price
		if ( ! empty( $param['c_type_price'] ) ) {
			$filter->type_price = explode( ',', LP_Helper::sanitize_params_submitted( urldecode( $param['c_type_price'] ) ) );
		}

		// On sale
		if ( isset( $param['on_sale'] ) ) {
			$filter->is_sale = 1;
		}

		// On feature
		if ( isset( $param['on_feature'] ) ) {
			$filter->is_feature = 1;
		}

		// Sort by level
		$levels_str = LP_Helper::sanitize_params_submitted( urldecode( $param['c_level'] ?? '' ) );
		if ( ! empty( $levels_str ) ) {
			$levels = explode( ',', $levels_str );
			if ( in_array( 'all', $levels ) ) {
				$levels[] = '';
			}
			$filter->levels = $levels;
		}

		// Sort by type (oline/offline)
		$course_type = LP_Helper::sanitize_params_submitted( urldecode( $param['c_type'] ?? '' ) );
		if ( ! empty( $course_type ) ) {
			$course_type = explode( ',', $course_type );
			if ( in_array( 'online', $course_type ) && in_array( 'offline', $course_type ) ) {
				$filter->type = 'all';
			} else {
				$filter->type = $course_type[0];
			}
		}

		// Check is in category page.
		if ( ! empty( $param['page_term_id_current'] ) && empty( $param['term_id'] ) ) {
			$filter->term_ids[] = $param['page_term_id_current'];
		} // Check is in tag page.
		elseif ( ! empty( $param['page_tag_id_current'] ) && empty( $param['tag_id'] ) ) {
			$filter->tag_ids[] = $param['page_tag_id_current'];
		}

		// Find by category
		$term_ids_str = LP_Helper::sanitize_params_submitted( urldecode( $param['term_id'] ?? '' ) );
		if ( ! empty( $term_ids_str ) ) {
			$term_ids         = explode( ',', $term_ids_str );
			$filter->term_ids = array_merge( $filter->term_ids, $term_ids );
		}

		// Find by tag
		$tag_ids_str = LP_Helper::sanitize_params_submitted( urldecode( $param['tag_id'] ?? '' ) );
		if ( ! empty( $tag_ids_str ) ) {
			$tag_ids         = explode( ',', $tag_ids_str );
			$filter->tag_ids = array_merge( $filter->tag_ids, $tag_ids );
		}

		// Order by
		$filter->order_by = LP_Helper::sanitize_params_submitted( ! empty( $param['order_by'] ) ? $param['order_by'] : 'post_date_gmt', 'key' );
		$filter->order    = LP_Helper::sanitize_params_submitted( ! empty( $param['order'] ) ? $param['order'] : 'DESC', 'key' );
		$filter->limit    = $param['limit'] ?? LP_Settings::get_option( 'archive_course_limit', 10 );

		// For search suggest courses
		if ( ! empty( $param['c_suggest'] ) ) {
			$filter->only_fields = [ 'ID', 'post_title' ];
			$filter->limit       = apply_filters( 'learn-press/rest-api/courses/suggest-limit', 10 );
		}

		do_action( 'learn-press/courses/handle_params_for_query_list_courses', $filter, $param );
	}

	/**
	 * Get list courses on table learnpress_courses
	 *
	 * @param CourseJsonFilter $filter
	 * @param int $total_rows
	 *
	 * @return array
	 * @since 4.3.2.3
	 * @version 1.0.0
	 */
	public static function get_list_courses( CourseJsonFilter $filter, int &$total_rows = 0 ): array {
		$db = CourseJsonDB::getInstance();

		try {
			$is_order_by_popular = 'popular' === $filter->order_by;

			// Order by
			switch ( $filter->order_by ) {
				case 'price':
				case 'price_low':
					if ( 'price_low' === $filter->order_by ) {
						$filter->order = 'ASC';
					} else {
						$filter->order = 'DESC';
					}

					$filter->order_by = 'price_to_sort';
					break;
				case 'popular':
					$filter = $db->get_courses_order_by_popular( $filter );
					break;
				case 'post_title':
					$filter->order = 'ASC';
					break;
				case 'post_title_desc':
					$filter->order_by = 'post_title';
					$filter->order    = 'DESC';
					break;
				case 'menu_order':
					$filter->order_by = 'menu_order';
					$filter->order    = 'ASC';
					break;
				default:
					$filter = apply_filters( 'lp/courses-json/filter/order_by/' . $filter->order_by, $filter );
					break;
			}

			// Query get results
			/**
			 * @var CourseJsonFilter $filter
			 */
			$filter  = apply_filters( 'lp/courses-json/filter', $filter );
			$courses = $db->get_courses( $filter, $total_rows );
		} catch ( Throwable $e ) {
			$courses = [];
			LP_Debug::error_log( $e );
		}

		return $courses;
	}
}
