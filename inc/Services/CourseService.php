<?php

namespace LearnPress\Services;

use Exception;
use LearnPress\Databases\Course\CourseJsonDB;
use LearnPress\Filters\Course\CourseJsonFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\UserModel;
use LP_Debug;
use LP_Helper;
use LP_Settings;
use Throwable;

/**
 * Class CourseService
 *
 * Create course with data.
 *
 * @package LearnPress\Services
 * @since 4.3.0
 * @version 1.0.1
 */
class CourseService {
	use Singleton;

	public function init() {
	}

	/**
	 * Create course info main
	 * Data of columns posts
	 *
	 * meta_input for metadata
	 *
	 * @param array $data [ 'post_title' => '', 'post_content' => '', 'post_status' => '', 'post_author' => , 'meta_input' => [] ]
	 *
	 * @throws Exception
	 */
	public function create_info_main( array $data ): CoursePostModel {
		$coursePostModelNew = new CoursePostModel( $data );

		// Set meta data
		if ( isset( $data['meta_input'] ) ) {
			$coursePostModelNew->meta_data = (object) $data['meta_input'];
		}

		$coursePostModelNew->save();

		return $coursePostModelNew;
	}

	/**
	 * Update categories for course
	 * Create categories if not exists
	 *
	 * @param $course_id
	 * @param int[] $category_ids
	 *
	 * @return void
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public function update_categories( $course_id, array $category_ids ) {
		// Create categories if not exists
		foreach ( $category_ids as $category_id ) {
			$term_check = term_exists( $category_id, CoursePostModel::TAXONOMY_CATEGORY );
			if ( ! $term_check ) {
				wp_insert_term( $category_id, CoursePostModel::TAXONOMY_CATEGORY );
			}
		}

		wp_set_post_terms( $course_id, $category_ids, CoursePostModel::TAXONOMY_CATEGORY );
	}

	/**
	 * Update tags for course
	 * Create tags if not exists
	 *
	 * @param $course_id
	 * @param array $tag_ids
	 *
	 * @return void
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public function update_tags( $course_id, array $tag_ids ) {
		// Create tags if not exists
		foreach ( $tag_ids as $tag_id ) {
			$term_check = term_exists( $tag_id, CoursePostModel::TAXONOMY_TAG );
			if ( ! $term_check ) {
				wp_insert_term( $tag_id, CoursePostModel::TAXONOMY_TAG );
			}
		}

		wp_set_post_terms( $course_id, $tag_ids, CoursePostModel::TAXONOMY_TAG );
	}

	/**
	 * Duplicate course
	 *
	 * @throws Exception
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public function duplicate( CourseModel $courseModel ): CourseModel {
		$coursePostModel = new CoursePostModel( $courseModel );
		$coursePostModel->get_all_metadata();
		$coursePostModelNew             = new CoursePostModel( $coursePostModel );
		$coursePostModelNew->ID         = null;
		$coursePostModelNew->post_title = $coursePostModelNew->post_title . ' (Copy)';
		$coursePostModelNew->save();

		// Duplicate sections
		$sections = $courseModel->get_section_items();
		foreach ( $sections as $section ) {
			$section_name        = $section->section_name ?? $section->title ?? '';
			$section_description = $section->section_description ?? $section->description ?? '';

			$courseSectionModel = $coursePostModelNew->add_section(
				[
					'section_name'        => $section_name,
					'section_description' => $section_description,
				]
			);

			// Duplicate items for section
			$items = $section->items ?? [];
			foreach ( $items as $item ) {
				$item_title   = $item->title ?? '';
				$item_type    = $item->type ?? $item->item_type ?? '';
				$item_content = '';

				// Get item content from post
				$item_post = get_post( $item->item_id ?? $item->id ?? 0 );
				if ( $item_post ) {
					$item_content = $item_post->post_content ?? '';
				}

				$courseSectionModel->create_item_and_add(
					[
						'item_title'   => $item_title,
						'item_type'    => $item_type,
						'item_content' => $item_content,
					]
				);
			}
		}

		return $courseModel;
	}

	/**
	 * Handle params before query list courses on table learnpress_courses
	 *
	 * @param CourseJsonFilter $filter
	 * @param array $param
	 *
	 * @return void
	 * @since 4.3.7
	 * @version 1.0.0
	 */
	public static function handle_params_for_query_list_courses( CourseJsonFilter &$filter, array $param = [] ) {
		$filter->page       = absint( $param['paged'] ?? 1 );
		$filter->post_title = LP_Helper::sanitize_params_submitted( trim( $param['c_search'] ?? '' ) );
		$db                 = CourseJsonDB::getInstance();

		// Get Columns
		$fields_str = LP_Helper::sanitize_params_submitted( urldecode( $param['c_fields'] ?? '' ) );
		if ( ! empty( $fields_str ) ) {
			$fields = explode( ',', $fields_str );
			foreach ( $fields as $key => $field ) {
				$fields[ $key ] = $db->wpdb->prepare( '%i', $field );
			}
			$filter->fields = $fields;
		}

		// Get only columns
		$fields_only_str = LP_Helper::sanitize_params_submitted( urldecode( $param['c_only_fields'] ?? '' ) );
		if ( ! empty( $fields_only_str ) ) {
			$fields_only = explode( ',', $fields_only_str );
			foreach ( $fields_only as $key => $field ) {
				$fields_only[ $key ] = $db->wpdb->prepare( '%i', $field );
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

			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
				|| ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				$filter->post_status = [ PostModel::STATUS_PUBLISH ];
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
		$order_by = LP_Helper::sanitize_params_submitted( $param['order_by'] ?? 'post_date_gmt', 'key' );
		if ( $order_by === 'post_date' ) {
			$order_by = 'post_date_gmt';
		}
		$filter->order_by = $order_by;
		$filter->order    = LP_Helper::sanitize_params_submitted( $param['order'] ?? 'DESC', 'key' );
		$filter->limit    = $param['limit'] ?? LP_Settings::get_option( 'archive_course_limit', 10 );

		// For search suggest courses
		if ( ! empty( $param['c_suggest'] ) ) {
			$filter->only_fields = [ 'ID', 'post_title' ];
			$filter->limit       = apply_filters( 'learn-press/services/rest-api/courses/suggest-limit', 10 );
		}

		do_action( 'learn-press/services/courses/handle_params_for_query_list_courses', $filter, $param );
	}

	/**
	 * Get list courses on table learnpress_courses
	 *
	 * @param CourseJsonFilter $filter
	 * @param int $total_rows
	 *
	 * @return array
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.3.7
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
					$filter = apply_filters( 'lp/services/courses/filter/order_by/' . $filter->order_by, $filter );
					break;
			}

			// Query get results
			/**
			 * @var CourseJsonFilter $filter
			 */
			$filter  = apply_filters( 'lp/services/courses/filter', $filter );
			$courses = $db->get_courses( $filter, $total_rows );
		} catch ( Throwable $e ) {
			$courses = [];
			LP_Debug::error_log( $e );
		}

		return $courses;
	}

	/**
	 * Check item type is support of course
	 *
	 * @param string $item_type
	 * @return bool
	 */
	public static function check_is_item_type_of_course( string $item_type ): bool {
		$course_item_types = coursemodel::item_types_support();
		return in_array( $item_type, $course_item_types );
	}
}
