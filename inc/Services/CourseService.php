<?php

namespace LearnPress\Services;

use Exception;
use LearnPress\Databases\CourseSectionDB;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionModel;
use LP_Helper;
use LP_Section_DB;
use LP_Settings;
use stdClass;

/**
 * Class CourseService
 *
 * Create course with data.
 *
 * @package LearnPress\Services
 * @since 4.3.0
 * @version 1.0.0
 */
class CourseService {
	use Singleton;

	public function init() {
	}

	/**
	 * Create course info main
	 *
	 * @param array $data [ 'post_title' => '', 'post_content' => '', 'post_status' => '', 'post_author' => , ... ]
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
}
